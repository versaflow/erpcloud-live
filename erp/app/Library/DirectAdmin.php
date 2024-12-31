<?php

//https://www.directadmin.com/api.php

class DirectAdmin
{
    public function __construct()
    {
        $this->server_ip = '156.0.96.73'; //IP that User is assigned to
        $this->server_login = 'admin';
        $this->server_pass = 'Webmin@786';
        $this->server_host = '156.0.96.73'; //where the API connects to
        $this->server_ssl = 'Y';
        $this->server_port = 2222;
    }

    public function call($endpoint, $data = [], $login_as = false, $return_raw = false)
    {
        $sock = new HTTPSocket;
        if ($this->server_ssl == 'Y') {
            $sock->connect('ssl://'.$this->server_host, $this->server_port);
        } else {
            $sock->connect($this->server_host, $this->server_port);
        }
        $username = $this->server_login;
        if ($login_as) {
            $username .= '|'.$login_as;
        }

        $sock->set_login($username, $this->server_pass);

        $sock->query('/'.$endpoint, $data);
        if ($return_raw) {
            $result = $sock->fetch_body();
        } else {
            $result = $sock->fetch_parsed_body();
        }

        return $result;
    }

    public function provision($account_id, $domain, $package = false)
    {
        $account = dbgetaccount($account_id);
        $users = $this->getUsers();
        if (! empty($users) && ! empty($users['list']) && is_array($users['list']) && in_array('da'.$account_id, $users['list'])) {
            $this->setUserQuotas($account_id);
            $this->createDomain($account_id, $domain);
            $this->createKeyFile($account_id, $domain);
            $this->addSPF($account_id, $domain);
        } else {
            $pass = generate_strong_password();
            \DB::table('crm_accounts')->where('id', $account_id)->update(['da_pass' => $pass]);
            $this->createUser($account_id, $account->email, $pass, $domain);
            $this->createKeyFile($account_id, $domain);
        }
        $this->sitebuilderInstall($account_id, $domain);
    }

    public function sitebuilderInstall($account_id, $domain)
    {
        $install_dir = '/home/da'.$account_id.'/domains/'.$domain.'/public_html';
        $cmd = 'rm '.$install_dir.'/index.html';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        $cmd = 'cp -r /usr/local/kopage4/kopage_files/. '.$install_dir.'/';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);

        $kopage_conf = [];
        $sitebuilders = \DB::table('sub_services')->where('provision_type', 'sitebuilder')->where('account_id', $account_id)->where('status', '!=', 'Deleted')->pluck('detail')->toArray();
        foreach ($sitebuilders as $sitebuilder) {
            $sitebuilder_dir = '/home/da'.$account_id.'/domains/'.$sitebuilder.'/public_html';
            $kopage_conf[$sitebuilder_dir] = $sitebuilder;
        }

        $kopage_conf = serialize($kopage_conf);

        $cmd = 'echo "'.$kopage_conf.'" | cat > /home/da'.$account_id.'/.kopage';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }

    public function getUsers()
    {
        return $this->call('CMD_API_SHOW_ALL_USERS');
    }

    public function setUserStatus($account_id, $enable = 1)
    {
        $username = 'da'.$account_id;

        $users = $this->getUsers();
        if (! empty($users['list']) && is_array($users['list']) && count($users['list']) > 0) {
            if (in_array($username, $users['list'])) {
                $data = [
                    'location' => 'CMD_SELECT_USERS',
                    'select0' => $username,
                    'suspend' => ($enable == 1) ? 'Unsuspend' : 'Suspend',
                ];

                return $this->call('CMD_API_SELECT_USERS', $data);
            }
        }
    }

    public function getDomains($user)
    {
        $data = [
            'user' => $user,
        ];

        return $this->call('CMD_API_SHOW_USER_DOMAINS', $data);
    }

    public function getDomainList()
    {
        $users = $this->getUsers();

        $domain_list = [];
        foreach ($users['list'] as $user) {
            $domain_list[$user] = $this->getDomains($user);
        }

        return $domain_list;
    }

    public function getDomainData($user, $domain)
    {
        //6757.4:unlimited:0.000356674:93.5:2:no:unlimited:ON:ON:ON
        //where the data is
        //bandwidth used:bandwidth limit:disk usage for the domain:log usage for the domain:# of subdomains:suspended:quota:ssl:cgi:php

        $data_keys = [
            'bandwidth_used',
            'bandwidth_limit',
            'disk_usage',
            'log_usage',
            'subdomains',
            'suspended',
            'quota',
            'ssl',
            'cgi',
            'php',
        ];
        $result = $this->getDomains($user);
        foreach ($result as $domain_name => $data) {
            $formatted_domain = str_replace('_', '.', $domain_name);
            if ($formatted_domain == $domain) {
                $data_arr = explode(':', $data);

                return array_combine($data_keys, $data_arr);
            }
        }
    }

    public function createUser($account_id, $email, $pass, $domain)
    {
        $username = 'da'.$account_id;
        $data = [
            'action' => 'create',
            'add' => 'Submit',
            'username' => $username,
            'email' => $email,
            'passwd' => $pass,
            'passwd2' => $pass,
            'domain' => $domain,
            'package' => 'sitebuilder',
            'ip' => $this->server_ip,
            'notify' => 'yes',
        ];

        return $this->call('CMD_API_ACCOUNT_USER', $data);
    }

    public function setUserQuotas($account_id)
    {
        $username = 'da'.$account_id;

        $subscriptions = \DB::connection('default')->table('sub_services')
            ->select('sub_services.detail', 'crm_products.provision_package')
            ->join('crm_products', 'crm_products.id', '=', 'sub_services.product_id')
            ->where('account_id', $account_id)
            ->where('sub_services.provision_type', 'sitebuilder')->where('sub_services.status', '!=', 'Deleted')->get();
        $quota = 0;
        $domain_count = 0;
        foreach ($subscriptions as $sub) {
            $package_arr = explode('_', $sub->provision_package);
            $quota += $package_arr[1];
            $domain_count++;
        }
        if ($account_id == 12) {
            $domain_count += 10;
        }
        if ($account_id == 3248) {
            $quota = 2;
            $domain_count = 2;
        }
        $data = [
            'action' => 'customize',
            'user' => $username,
            'quota' => $quota * 1000,
            'nemails' => $quota * 10,
            'unemails' => 'OFF',
            'mysql' => $quota,
            'umysql' => 'OFF',
            'ssl' => 'ON',
            'php' => 'ON',
            'spam' => 'ON',
            'vdomains' => $domain_count,
        ];

        return $this->call('CMD_API_MODIFY_USER', $data);
    }

    public function setUserPackage($account_id, $package)
    {
        $username = 'da'.$account_id;
        $data = [
            'action' => 'package',
            'user' => $username,
            'package' => $package,
        ];

        return $this->call('CMD_API_MODIFY_USER', $data);
    }

    public function createDomain($account_id, $domain)
    {
        $data = [
            'action' => 'create',
            'domain' => $domain,
            'ssl' => 'ON',
            'php' => 'ON',
        ];

        return $this->call('CMD_API_DOMAIN', $data, 'da'.$account_id);
    }

    public function setPHP($account_id, $domain)
    {
        $data = [
            'action' => 'php_selector',
            'domain' => $domain,
            'php1_select' => 1,
            'php2_select' => 1,
        ];

        return $this->call('CMD_API_DOMAIN', $data, 'da'.$account_id);
    }

    public function deleteDomain($account_id, $domain)
    {
        $data = [
            'confirmed' => 'Confirm',
            'delete' => 'yes',
            'select0' => $domain,
        ];

        return $this->call('CMD_API_DOMAIN', $data, 'da'.$account_id);
    }

    public function createKeyFile($account_id, $domain)
    {
        // /admin.php?key=1
        // http://afriphone.co.za/admin.php?key=1
        $username = 'da'.$account_id;

        $cmd = 'cd /home/'.$username.'/domains/'.$domain.'/public_html/ && touch key.1.txt';

        return Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }

    public function getEmails($account_id, $domain)
    {
        $data = [
            'action' => 'list',
            'domain' => $domain,
        ];

        return $this->call('CMD_API_POP', $data, 'da'.$account_id);
    }

    public function createEmail($account_id, $domain, $user, $pass)
    {
        $data = [
            'action' => 'create',
            'domain' => $domain,
            'user' => $user,
            'passwd' => $pass,
            'passwd2' => $pass,
        ];

        return $this->call('CMD_API_POP', $data, 'da'.$account_id);
    }

    public function editEmail($account_id, $domain, $user, $pass)
    {
        $data = [
            'action' => 'modify',
            'domain' => $domain,
            'user' => $user,
            'passwd' => $pass,
            'passwd2' => $pass,
        ];

        return $this->call('CMD_API_POP', $data, 'da'.$account_id);
    }

    public function deleteEmail($account_id, $domain, $user)
    {
        $data = [
            'action' => 'delete',
            'domain' => $domain,
            'user' => $user,
        ];

        return $this->call('CMD_API_POP', $data, 'da'.$account_id);
    }

    public function addSPF($account_id, $domain)
    {
        $data = [
            'action' => 'add',
            'domain' => $domain,
            'type' => 'TXT',
            'name' => $domain.'.',
            'value' => '"v=spf1 a mx ip4:156.0.96.73 ~all"',
        ];

        return $this->call('CMD_API_DNS_CONTROL', $data, 'da'.$account_id);
        //"v=spf1 a mx ip4:156.0.96.73 ~all"
        //CMD_API_DNS_CONTROL?domain=domain.com&action=add&type=A|NS|MX|CNAME|PTR&name=namevalue&value=recordvalue
    }

    public function getDns($domain)
    {
        $data = [
            'domain' => $domain,
            'json' => 'yes',
        ];
        $result = $this->call('CMD_API_DNS_ADMIN', $data, 0, 1);
        $result = json_decode($result);

        return $result->records;
    }

    public function getPbxDns()
    {
        $data = [
            'domain' => 'cloudtools.co.za',
            'urlencoded' => 'yes',
        ];
        $result = $this->call('CMD_API_DNS_ADMIN', $data);
        $predefined_domains = [];
        foreach ($result as $key => $val) {
            if (str_starts_with($key, 'um') && $val == 'pbx.cloudtools.co.za.') {
                $predefined_domains[] = rtrim(str_replace('_', '.', $key), '.');
            }
        }

        return $predefined_domains;
    }

    public function addPbxDns($host)
    {
        $host = $host.'.';
        $this->addDns(12, 'cloudtools.co.za', 'CNAME', $host, 'pbx.cloudtools.co.za.');
    }

    public function deletePbxDns($host)
    {
        $host = $host.'.';
        $this->deleteDns(12, 'cloudtools.co.za', 'CNAME', $host, 'pbx.cloudtools.co.za.');
    }

    public function addDns($account_id, $domain, $type, $host, $value)
    {
        $data = [
            'action' => 'add',
            'domain' => $domain,
            'type' => $type,
            'name' => $host,
            'value' => $value,
        ];

        return $this->call('CMD_API_DNS_CONTROL', $data, 'da'.$account_id);
    }

    public function deleteDns($account_id, $domain, $type, $host, $value)
    {
        $data = [
            'action' => 'select',
            'delete' => 'delete',
            'domain' => $domain,
        ];

        if ($type == 'A') {
            $data['arecs0'] = 'name='.$host.'&value='.$value;
        }
        if ($type == 'CNAME') {
            $data['cnamerecs0'] = 'name='.$host.'&value='.$value;
        }
        if ($type == 'NS') {
            $data['nsrecs0'] = 'name='.$host.'&value='.$value;
        }
        if ($type == 'MX') {
            $data['mxrecs0'] = 'name='.$host.'&value='.$value;
        }
        if ($type == 'PTR') {
            $data['ptrrecs0'] = 'name='.$host.'&value='.$value;
        }

        return $this->call('CMD_API_DNS_CONTROL', $data, 'da'.$account_id);
    }

    public function getUserBackups($account_id, $domain)
    {
        $data = [
            'action' => 'list',
            'domain' => $domain,
        ];

        return $this->call('CMD_API_SITE_BACKUP', $data, 'da'.$account_id);
    }

    public function createUserBackup($account_id, $domain)
    {
        $data = [
            'action' => 'backup',
            'domain' => $domain,
        ];

        return $this->call('CMD_API_SITE_BACKUP', $data, 'da'.$account_id);
    }

    public function deleteUserBackups()
    {
        $cmd = 'find /home/*/backups/*  -name "*.gz" -mtime +14 -exec rm {} \; && echo "success: $?" || echo "fail: $?"';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);

        return $result;
    }

    public function deleteAdminBackups()
    {
        $backup_dirs = File::directories('/home/_admin/admin_backups');
        $backup_count = count($backup_dirs);
        $backup_dirs = array_reverse($backup_dirs);
        foreach ($backup_dirs as $i => $path) {
            if ($i > 1) {
                File::deleteDirectory($path);
            }
        }
    }

    public function getLoginUrl($user)
    {
        //https://www.directadmin.com/features.php?id=2463
        ///usr/local/directadmin/directadmin --create-login-url user=da12

        $cmd = '/usr/local/directadmin/directadmin --create-login-url user='.$user;
        if ($user == 'admin') {
            $cmd .= ' redirect-url=/reseller/users';
        }
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        //dd($result);
        $url = str_replace([PHP_EOL, 'URL: '], '', $result);
        $url = str_replace('https://156.0.96.73', 'https://host3.cloudtools.co.za', $url);

        return $url;
    }
}
