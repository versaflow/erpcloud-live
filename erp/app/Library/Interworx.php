<?php

class Interworx
{
    protected $apikey;

    protected $server_ip;

    protected $server;

    protected $domain;

    protected $debug;

    public function __construct($server = 'host2', $domain = false)
    {
        $this->debug = false;
        if ($server) {
            $this->setServer($server);
        }
        if ($domain) {
            $this->setDomain($domain);
        }
    }

    public function setDebug()
    {
        $this->debug = true;

        return $this;
    }

    private function setApiKey()
    {
        if ($this->server == 'host1') {
            $this->server_ip = '156.0.96.71';
            $this->apikey = '-----BEGIN INTERWORX API KEY-----
MXwkZilkWjJmLGJ7LUc2cUhMcn5UbUVZSWYxZ0NrXkxiKj9FfkdjeyxxaF1A
tTEdKN0xVYzJjVWhNY241VWJVVlpTV1l4WjBOclhreGlLajlGZmtkamV5eHh
eU05a1RDeFp3WypbRXg6SzBpfk1qRjpiKm45YlImQCR+dSh2c3QrbH4lOSVR
zV3lwYlJYZzZTekJwZmsxcVJqcGlLbTQ1WWxJbVFDUitkU2gyYzNRcmJINGx
LWZ7TnBOLENLIX5BTGVuaUsya1doJH0oRi5GWHhZNWMzaVtjaHp4WSssNiVM
MSVg1QlRHVnVhVXN5YTFkb0pIMG9SaTVHV0hoWk5XTXphVnRqYUhwNFdTc3N
Q1FII209WE5BPzIraWtwWko8eD0qPHY/Ylomdi4jcjdIMyRfQkJ1KXUwVnA0
CUHpJcmFXdHdXa284ZUQwcVBIWS9ZbG9tZGk0amNqZElNeVJmUWtKMUtYVXd
N0pAKURIOF9dTypbWCVoJkV+NHNZdzkzNkAlU3NSLmdjXXUtSS5yPWlba09I
kVHlwYldDVm9Ka1YrTkhOWmR6a3pOa0FsVTNOU0xtZGpYWFV0U1M1eVBXbGJ
aWVkRHB+MDgxJTJ3Jl82Riw5LERVWXk8KGUlQ3Y6OlorNGkuRmpyOUBjRyZS
4SlRKM0psODJSaXc1TEVSVldYazhLR1VsUTNZNk9sb3JOR2t1Um1weU9VQmp
WXdYOG5yI1hFVnd5NnooZCFeczxvLD01fkFec1V9I3F4WEs5Sj1VPSg1XUNh
GVm5kNU5ub29aQ0ZlY3p4dkxEMDFma0ZlYzFWOUkzRjRXRXM1U2oxVlBTZzF
T0xLOHNsTlpEMnFUMEoqblJJdntCWzNGP1p9JENpJjg9Skp4ODhMSE1YbV1q
FTW5GVU1Fb3FibEpKZG50Q1d6TkdQMXA5SkVOcEpqZzlTa3A0T0RoTVNFMVl
QUFOZClNKFE5ITw4IWoxMHdWMCEwJWprZXM5bUJtTGUsUVsxKGY4ZlRra3I9
1SVR3NElXb3hNSGRXTUNFd0pXcHJaWE01YlVKdFRHVXNVVnN4S0dZNFpsUnJ
OVYhX1F1LVZZNypOVjJ0XWlHMSE1PFd5PGElRmpdSH5Yci1BeSMwJVlXOmV4
aTnlwT1ZqSjBYV2xITVNFMVBGZDVQR0VsUm1wZFNINVljaTFCZVNNd0pWbFh
KyxHfjI6N3t0UF42QXJqaGpodylqM191Qi1mW3tpSzd2N0FpOndUWzhORFJd
wVUY0MlFYSnFhR3BvZHlscU0xOTFRaTFtVzN0cFN6ZDJOMEZwT25kVVd6aE9
fXNudl9hQn51cUA/U2FoaHdNYWJxRXRRX2JSS2U5Py5TeCQ2QmhNQ0kpVSFq
xY1VBL1UyRm9hSGROWVdKeFJYUlJYMkpTUzJVNVB5NVRlQ1EyUW1oTlEwa3B
VUhjdFZeLlouSj9APVBnamhpQ0FwdTApV1ZASigkV3VRKURNPlUofTRnLCxA
1U2o5QVBWQm5hbWhwUTBGd2RUQXBWMVpBU2lna1YzVlJLVVJOUGxVb2ZUUm5
R308PSxtVUZ9eHBzXzZObCMwTDl1K2dMckhdRSZiMQ==
-----END INTERWORX API KEY-----';
        }

        if ($this->server == 'host2') {
            $this->server_ip = '156.0.96.72';
            $this->apikey = '-----BEGIN INTERWORX API KEY-----
MXw8Pnl1eUstUCRQWTkpPkZ1V1FPYiMhdD5sWXZmLD5Xb3hwLE1LdzVeMmJy
0VUNSUVdUa3BQa1oxVjFGUFlpTWhkRDVzV1habUxENVhiM2h3TEUxTGR6VmV
IWtyOXAkb21XWE5xTXkoMG5Qei1+R3khaHouOlJXI31SRj9ATzE9eHJtRG9K
YV0U1eFRYa29NRzVRZWkxK1Iza2hhSG91T2xKWEkzMVNSajlBVHpFOWVISnR
QGQ1VzEuaF4tV05EQ1swTWZnOHA9SFB0JFQscTd3VUEyI24jSzBWam1QUGw0
0VjA1RVExc3dUV1puT0hBOVNGQjBKRlFzY1RkM1ZVRXlJMjRqU3pCV2FtMVF
YVpBcjRDcUApQGk3aT9+LGRqI2Noe3hWZGoxckFCfjFqIUo/dk4ySTFSSWxi
wUUdrM2FUOStMR1JxSTJOb2UzaFdaR294Y2tGQ2ZqRnFJVW8vZGs0eVNURlN
ITFIIXJZNy5WKWZqT11BQmpAME52bUVHYXc4M31bOTJYeWhfKGc+YmstTChf
XS1dacVQxMUJRbXBBTUU1MmJVVkhZWGM0TTMxYk9USlllV2hmS0djK1ltc3R
XVUyLkBtV1s2aUUobXJvZyZyZUpeeyM9eG02MC03NTh9KU0sRF85QDpKYzw4
yYVVVb2JYSnZaeVp5WlVwZWV5TTllRzAyTUMwM05UaDlLVTBzUkY4NVFEcEt
LHctWzomSjY1Rz95YmM8RzdRK0l4UFhWKV8xMj1DdGR1ejxkMUJkUV9uUVVQ
xUno5NVltTThSemRSSzBsNFVGaFdLVjh4TWoxRGRHUjFlanhrTVVKa1VWOXV
KCZGX0hJR35tIV9pZSxiLHZpXUttezk/JXg5eWlTdlosK0t4Vm8oUGY1Qy1y
0SVY5cFpTeGlMSFpwWFV0dGV6ay9KWGc1ZVdsVGRsb3NLMHQ0Vm04b1VHWTF
dmoyUC1MNH4mSUFVU1EjPGtUMWNpLmNpLnY+Pl9tI1p2LkJoTV9RXW1eSnVj
tU1VGVlUxRWpQR3RVTVdOcExtTnBMblkrUGw5dEkxcDJMa0pvVFY5UlhXMWV
LCs1R104M3Q5Iz4uX3ElWDk3fSxmbXh1RHZdVXs8eTd7b1ptLCRzb2lxMSVb
1SXo0dVgzRWxXRGszZlN4bWJYaDFSSFpkVlhzOGVUZDdiMXB0TENSemIybHh
UGppVnAhdig/fnBnJEMxQS1GKCZiUz4zRU1KJHtJa1JoPClUfT1ScyNCcmJa
vZm5CbkpFTXhRUzFHS0NaaVV6NHpSVTFLSkh0SmExSm9QQ2xVZlQxU2N5TkN
X15rLWU6JiVjMjxPdnBidjpLSXQxeF04KWdiJTp5LWM6PStKcEZWPExJMmVi
qTWp4UGRuQmlkanBMU1hReGVGMDRLV2RpSlRwNUxXTTZQU3RLY0VaV1BFeEp
QjFJTmJaRUtZVX1SUGEqdGZJT2UjJF1bTFN6b3NBIzNfb1JvLSpoRHlUTnNP
aVlgxU1VHRXFkR1pKVDJVakpGMWJURk42YjNOQkl6TmZiMUp2TFNwb1JIbFV
aShtPW5JVlV2VElWUnMjaCo8KSNMMCwwTyNeZmItRDhyKkZASSFlW0pEUnJz
yVkVsV1VuTWphQ284S1NOTU1Dd3dUeU5lWm1JdFJEaHlLa1pBU1NGbFcwcEV
Wzh1bQ==
-----END INTERWORX API KEY-----';
        }

        if ($this->domain) {
            $this->apikey = [
                'domain' => $this->domain,
                'apikey' => $this->apikey,
            ];
        }
    }

    public function setServer($server)
    {
        $this->server = $server;
        $this->setApiKey();

        return $this;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
        $this->setApiKey();

        return $this;
    }

    public function curl($controller, $action, $input = null)
    {
        if ($this->server == 'external') {
            return 'Invalid Server';
        }

        // if('host1' == $this->server && $controller!='/siteworx/email/box') {
        //     return 'Invalid Server';
        // }

        ini_set('default_socket_timeout', 600);

        $client = new \SoapClient('http://'.$this->server_ip.':2080/soap?wsdl', [
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]
            ),
        ]);

        if ($this->debug) {
            exception_log($controller);
            exception_log($action);
            exception_log($input);
        }

        if ($input) {
            $result = $client->route($this->apikey, $controller, $action, $input);
        } else {
            $result = $client->route($this->apikey, $controller, $action);
        }

        if ($this->debug) {
            exception_log($result);
        }

        return $result;
    }

    public function getDBRecord()
    {
        return \DB::table('isp_host_websites')->where('domain', $this->domain)->get()->first();
    }

    public function getDBAccount()
    {
        $site = $this->getDBRecord();

        return \DB::table('crm_accounts')->where('id', $site->account_id)->get()->first();
    }

    public function getAccountInfo()
    {
        $result = $this->curl('/nodeworx/siteworx', 'querySiteworxAccountDetails', ['domain' => $this->domain]);

        return $result['payload'];
    }

    public function addAccount($input)
    {
        return $this->curl('/nodeworx/siteworx', 'add', $input);
    }

    public function editAccount($input)
    {
        $result = $this->curl('/nodeworx/siteworx', 'edit', $input);

        return $result;
    }

    public function deleteAccount()
    {
        $input = [
            'domain' => $this->domain,
            'confirm_action' => '1',
        ];

        return $this->curl('/nodeworx/siteworx', 'delete', $input);
    }

    public function listResellers()
    {
        return $this->curl('/nodeworx/reseller', 'listResellers', $input);
    }

    public function setResellerIds()
    {
        $resellers = $this->listResellers();
        foreach ($resellers['payload'] as $reseller) {
            $name_arr = explode('_', $reseller->nickname);
            $id = $name_arr[0];
            \DB::table('crm_account_partner_settings')->where('account_id', $id)->update(['hosting_id' => $reseller->reseller_id]);
        }
    }

    public function addReseller($partner_id)
    {
        $account = dbgetaccount($partner_id);
        $pass = generate_strong_password();

        $input = [
            'nickname' => $reseller->id.'_'.$reseller->company,
            'email' => $reseller->email,
            'password' => $password,
            'confirm_password' => $password,
            'status' => 'active',
            'packagetemplate' => 'resellers',
        ];
        \DB::table('crm_account_partner_settings')->where('account_id', $partner_id)->update(['hosting_pass' => $pass]);
        $result = $this->curl('/nodeworx/reseller', 'add', $input);
        $this->setResellerIds();

        return $result;
    }

    public function editReseller($reseller, $password, $reseller_id)
    {
        $input = [
            'reseller_id' => $reseller_id,
            'nickname' => $reseller->id.'_'.$reseller->company,
            'email' => $reseller->email,
            'password' => $password,
            'confirm_password' => $password,
            'status' => 'active',
            'packagetemplate' => 'resellers',
        ];

        return $this->curl('/nodeworx/reseller', 'edit', $input);
    }

    public function updatePbxDomainDns($pbx_domain, $target)
    {
        $this->setServer('host1');
        $this->setDomain('cloudtools.co.za');
        $dns_records = $this->queryDnsRecords();
        $record = false;
        foreach ($dns_records['payload'] as $dns_record) {
            if ($dns_record->type == 'CNAME' && $dns_record->host == $pbx_domain) {
                $record = $dns_record;
            }
        }
        if (! $record) {
            return 'DNS record not found.';
        }
        $input = (array) $record;
        $input['target'] = $target;

        return $this->curl('/nodeworx/dns/record', 'editCNAME', $input);
    }

    public function getPbxDnsRecords()
    {
        $this->setDomain('cloudtools.co.za');
        $dns_records = $this->queryDnsRecords();

        $records = [];
        foreach ($dns_records['payload'] as $dns_record) {
            if ($dns_record->type == 'CNAME' && $dns_record->target == 'pbx.cloudtools.co.za') {
                $records[] = $dns_record;
            }
        }

        return $records;
    }

    public function getAutomatedPbxDnsRecords()
    {
        $this->setServer('host1');
        $this->setDomain('cloudtools.co.za');
        $dns_records = $this->queryDnsRecords(101);
        $records = [];
        foreach ($dns_records['payload'] as $dns_record) {
            if ($dns_record->type == 'CNAME' && $dns_record->target == 'pbx.cloudtools.co.za' && str_starts_with($dns_record->host, 'um')) {
                $records[] = $dns_record;
            }
        }

        return $records;
    }

    public function getAutomatedPbxDnsRecordsTC()
    {
        $this->setServer('host1');
        $this->setDomain('telecloud.co.za');
        $dns_records = $this->queryDnsRecords(96);
        $records = [];
        foreach ($dns_records['payload'] as $dns_record) {
            if ($dns_record->type == 'CNAME' && $dns_record->target == 'pbx.cloudtools.co.za' && str_starts_with($dns_record->host, 'tc')) {
                $records[] = $dns_record;
            }
        }

        return $records;
    }

    public function addWhiteLabelDomain($domain)
    {
        $this->setServer('host2');
        $this->setDomain('cloudsoftware.cc');
        $input['domain'] = $domain;
        $input['redir_type'] = 'server_alias';
        $input['points_to'] = 'cloudtelecoms.cloudsoftware.cc';

        return $this->curl('/siteworx/domains/pointer', 'add', $input);
    }

    public function deleteInvalidWhiteLabelDomains()
    {
        if (session('instance')->directory == 'telecloud') {
            $instances = \DB::connection('default')->table('erp_instances')->get();
            $valid_hostnames = [];
            $instance_hostnames = [];
            foreach ($instances as $instance) {
                $instance_hostnames[] = $instance->domain_name;
                $valid_hostnames[] = $instance->domain_name;
                if (! empty($instance->alias)) {
                    $valid_hostnames[] = $instance->alias;
                    $instance_hostnames[] = $instance->alias;
                }
                $whitelabel_domains = \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('whitelabel_domain', '>', '')->pluck('whitelabel_domain')->toArray();
                foreach ($whitelabel_domains as $w_domain) {
                    $valid_hostnames[] = $w_domain;
                }
            }

            if (count($valid_hostnames) > 0) {
                $valid_hostnames = collect($valid_hostnames)->unique()->toArray();

                $this->setServer('host2');
                $this->setDomain('cloudtelecoms.io');
                $pointers = $this->curl('/siteworx/domains/pointer', 'list');
                foreach ($pointers['payload'] as $pointer_domain) {
                    if (in_array($pointer_domain, $instance_hostnames)) {
                        continue;
                    }
                    if (! in_array($pointer_domain, $valid_hostnames)) {
                        $input = ['domain' => $pointer_domain];
                        $this->curl('/siteworx/domains/pointer', 'delete', $input);
                    }
                }
            }
        }
    }

    public function listWhiteLabelDomains()
    {
        $this->setServer('host2');
        $this->setDomain('cloudsoftware.cc');

        return $this->curl('/siteworx/domains/pointer', 'list');
    }

    public function deletePbxDnsRecord($host_name)
    {
        $this->setServer('host1');
        $this->setDomain('cloudtools.co.za');

        return $this->deleteDnsRecord($host_name);
        $record = false;
        foreach ($dns_records['payload'] as $dns_record) {
            if ($dns_record->type == 'CNAME' && $dns_record->host == $pbx_domain) {
                $record = $dns_record;
            }
        }
        if (! $record) {
            return 'DNS record not found.';
        }
        $input = (array) $record;
        $input['target'] = $target;

        return $this->curl('/nodeworx/dns/record', 'editCNAME', $input);
    }

    public function deleteDnsRecord($host_name)
    {
        $record = false;
        $dns_records = $this->queryDnsRecords();
        if (! $dns_records) {
            foreach ($dns_records['payload'] as $dns_record) {
                if ($dns_record->type == 'CNAME' && $dns_record->host == $host_name) {
                    $record = $dns_record;
                }
            }
        }
        if (! $record) {
            return 'DNS record not found.';
        }
        $input['record_id'] = $record->record_id;

        return $this->curl('/nodeworx/dns/record', 'delete', $input);
    }

    public function deleteDnsRecordById($record_id)
    {
        $input['record_id'] = $record_id;

        return $this->curl('/nodeworx/dns/record', 'delete', $input);
    }

    public function removeSiteBuilderDns()
    {
        $dns_records = $this->queryDnsRecords();
        foreach ($dns_records['payload'] as $dns_record) {
            if ($dns_record->type == 'A' && $dns_record->host == $this->domain) {
                $a_record_id = $dns_record->record_id;
            }
            if ($dns_record->type == 'CNAME' && $dns_record->host == 'www.'.$this->domain) {
                $cname_record_id = $dns_record->record_id;
            }
        }
        if (! empty($a_record_id)) {
            $this->editDnsRecordA($a_record_id, '156.0.96.71');
        }
        if (! empty($cname_record_id)) {
            $this->editDnsRecordCname($cname_record_id, $this->domain);
        }
    }

    public function queryZones()
    {
        $input = [
            'domain' => $this->domain,
        ];

        $result = $this->curl('/nodeworx/dns/zone', 'queryZones', $input);

        return $result;
    }

    public function queryRecords($zone_id = false)
    {
        if (! $zone_id) {
            $zones = $this->queryZones();
            $zone_id = $zones['payload'][0]->zone_id;
        }
        $input = [
            'zone_id' => $zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'queryRecords', $input);
    }

    public function queryRecordsByType($type)
    {
        $input = [
            'type' => $type,
        ];

        return $this->curl('/nodeworx/dns/record', 'queryRecords', $input);
    }

    public function queryDnsRecords($zone_id = false)
    {
        if (! $zone_id) {
            $zones = $this->queryZones();
            $zone_id = $zones['payload'][0]->zone_id;
        }
        if ($zone_id != '101') {
            $input = [
                'zone_id' => $zone_id,
            ];
        } else {
            $input = '';
        }

        return $this->curl('/siteworx/dns', 'queryDnsRecords', $input);
    }

    public function editDnsRecordNS($input)
    {
        $response = $this->curl('/nodeworx/dns/record', 'editNS', $input);

        return $response;
    }

    public function editDnsRecordSOA($input)
    {
        $response = $this->curl('/nodeworx/dns/record', 'editSOA', $input);

        return $response;
    }

    public function getSoaRecords()
    {
        $response = $this->curl('/nodeworx/dns/record', 'queryRecords');

        return collect($response['payload'])->where('is_template', 0)->where('type', 'SOA');
    }

    public function updateAllSoaRecords()
    {
        $soa_records = $this->getSoaRecords();
        foreach ($soa_records as $record) {
            if (! str_contains($record->target, 'host2.cloudtools.co.za')) {
                $input = ['record_id' => $record->record_id, 'nameserver' => 'host2.cloudtools.co.za'];
                $this->editDnsRecordSOA($input);
            }
        }

        return true;
    }

    public function editDnsRecordA($record_id, $ipaddress, $domain = false)
    {
        $zones = $this->queryZones();
        if (! $domain) {
            $domain = $this->domain;
        }
        $zone_id = $zones['payload'][0]->zone_id;
        $input = [
            'record_id' => $record_id,
            'ipaddress' => $ipaddress,
            'host' => $domain,
            'zone_id' => $zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'editA', $input);
    }

    public function editDnsRecordCname($record_id, $alias, $domain = false)
    {
        if (! $domain) {
            $domain = 'www.'.$this->domain;
        }

        $zones = $this->queryZones();
        $zone_id = $zones['payload'][0]->zone_id;
        $input = [
            'record_id' => $record_id,
            'alias' => $alias,
            'host' => $domain,
            'zone_id' => $zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'editCNAME', $input);
    }

    public function addMailDNS()
    {
        $zones = $this->queryZones();
        if (! $domain) {
            $domain = $this->domain;
        }
        $zone_id = $zones['payload'][0]->zone_id;
        $input = [
            'all' => '~all',
            'use_mx' => 1,
            'zone_id' => $zone_id,
        ];

        $spf_result = $this->curl('/nodeworx/dns/record', 'addSPF', $input);

        $input = [
            'domain' => $this->domain,
            'testing_mode' => 0,
        ];
        $domainkey_result = $this->curl('/siteworx/email/domainkeys', 'add', $input);
    }

    public function addDnsRecordA($ipaddress, $domain = false)
    {
        $zones = $this->queryZones();
        if (! $domain) {
            $domain = $this->domain;
        }
        $zone_id = $zones['payload'][0]->zone_id;
        $input = [
            'ipaddress' => $ipaddress,
            'host' => $domain,
            'zone_id' => $zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'addA', $input);
    }

    public function addDnsRecordSPF()
    {
        $zones = $this->queryZones();
        if (! $domain) {
            $domain = $this->domain;
        }
        $zone_id = $zones['payload'][0]->zone_id;
        $input = [
            'domain' => $domain,
            'use_mx' => 1,
            'zone_id' => $zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'addSPF', $input);
    }

    public function addDnsRecordTxt($alias, $host)
    {
        $zones = $this->queryZones();

        $zone_id = $zones['payload'][0]->zone_id;
        $input = [
            'text' => $alias,
            'host' => $host,
            'zone_id' => $zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'addTXT', $input);
    }

    public function addDnsRecordCname($alias, $domain = false, $zone_id = false)
    {
        if (! $domain) {
            $domain = 'www.'.$this->domain;
        }

        if (! $zone_id) {
            $zones = $this->queryZones();
            $zone_id = $zones['payload'][0]->zone_id;
        }

        $input = [
            'alias' => $alias,
            'host' => $domain,
            'zone_id' => $zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'addCNAME', $input);
    }

    public function addPbxDns($domain_name)
    {
        $this->setServer('host1');
        $this->setDomain('cloudtools.co.za');

        $zones = $this->queryZones();
        $zone_id = $zones['payload'][0]->zone_id;

        $input = [
            'alias' => 'pbx.cloudtools.co.za',
            'host' => $domain_name,
            'zone_id' => 101, //$zone_id,
        ];

        return $this->curl('/nodeworx/dns/record', 'addCNAME', $input);
    }

    public function listEmailBoxes()
    {
        return $this->curl('/siteworx/email/box', 'listEmailBoxes');
    }

    public function editEmail($username, $password)
    {
        if (str_contains($username, '@')) {
            $username_arr = explode('@', $username);
            $username = $username_arr[0];
        }
        $input = [
            'username' => $username,
            'password' => $password,
            'confirm_password' => $password,
        ];

        return $this->curl('/siteworx/email/box', 'edit', $input);
    }

    public function createEmail($username, $password)
    {
        if (str_contains($username, '@')) {
            $username_arr = explode('@', $username);
            $username = $username_arr[0];
        }
        $input = [
            'username' => $username,
            'password' => $password,
            'confirm_password' => $password,
        ];

        // aa($input);
        return $this->curl('/siteworx/email/box', 'add', $input);
    }

    public function deleteEmail($username)
    {
        $input = [
            'username' => $username,
        ];

        return $this->curl('/siteworx/email/box', 'delete', $input);
    }

    public function setDefaultFTPPass()
    {
        $pass = generate_strong_password().generate_strong_password();
        $accounts = $this->listFtpAccounts();
        foreach ($accounts as $ftp) {
            if ($ftp->username == 'ftp') {
                $this->editFtp($ftp->username, $pass, $ftp->homedir);
            }
        }

        return $pass;
    }

    public function listFtpAccounts()
    {
        return $this->curl('/siteworx/ftp', 'listFtpAccounts');
    }

    public function siteBuilderFTP()
    {
        $sitebuilder_ftp = false;
        $siteworx = $this->getAccountInfo();
        if (empty($siteworx) || empty($siteworx['unixuser'])) {
            return false;
        }
        $unix_user = $siteworx['unixuser'];
        $domain = $this->domain;
        $site_path = '/home/'.$unix_user.'/'.$domain.'/html/';

        //site path needs to unix user root, otherwise kopage login does not work
        $site_path = '/home/'.$unix_user;

        $ftp_accounts = $this->listFtpAccounts();
        if (! empty($ftp_accounts['payload']) && count($ftp_accounts['payload']) > 0) {
            foreach ($ftp_accounts['payload'] as $ftp) {
                if ($ftp->username == 'sitebuilder') {
                    $sitebuilder_ftp = $ftp;
                }
            }
        }
        $pass = generate_strong_password().generate_strong_password();

        if ($sitebuilder_ftp) {
            $result = $this->editFtp('sitebuilder', $pass, $site_path);
        } else {
            $result = $this->createFtp('sitebuilder', $pass, $site_path);
        }

        \DB::table('isp_host_websites')->where('domain', $domain)->update(['ftp_user' => 'sitebuilder@'.$domain, 'ftp_pass' => $pass]);

        return ['user' => 'sitebuilder@'.$domain, 'pass' => $pass];
    }

    public function editFtp($username, $password, $dir)
    {
        $input = [
            'user' => $username,
            'password' => $password,
            'confirm_password' => $password,
            'homedir' => $dir,
        ];

        return $this->curl('/siteworx/ftp', 'edit', $input);
    }

    public function createFtp($username, $password, $dir)
    {
        $input = [
            'user' => $username,
            'password' => $password,
            'confirm_password' => $password,
            'homedir' => $dir,
        ];

        return $this->curl('/siteworx/ftp', 'add', $input);
    }

    public function deleteFtp($username)
    {
        $input = [
            'user' => $username,
        ];

        return $this->curl('/siteworx/ftp', 'delete', $input);
    }

    public function suspend($domain)
    {
        $input = [
            'domain' => $domain,
        ];

        return $this->curl('/nodeworx/siteworx', 'suspend', $input);
    }

    public function unsuspend($domain)
    {
        $input = [
            'domain' => $domain,
        ];

        return $this->curl('/nodeworx/siteworx', 'unsuspend', $input);
    }

    public function syncAccount($package)
    {
        $account = $this->getDBAccount();
        $domain = $this->domain;

        $email = (erp_email_valid($account->email)) ? $account->email : 'helpdesk@telecloud.co.za';
        if ($account->id == 12) {
            $email = 'helpdesk@telecloud.co.za';
        }
        $active = ($account->status == 'Enabled') ? 1 : 0;
        $input = [
            'domain' => $domain,
            'user' => $email,
            'email' => $email,
            'status' => $active,
        ];

        if ($domain != 'rentanything.io') {
            $account_id = \DB::table('isp_host_websites')->where('domain', $domain)->pluck('account_id')->first();

            $password = substr(\Erp::encode($domain), 0, 20);

            \DB::table('isp_host_websites')->where('domain', $domain)->update(['username' => $email, 'password' => $password]);
            $input['confirm_password'] = $password;
            $input['password'] = $password;
        }

        if ($domain == 'musa.org.za') {
            $input['email'] = 'info@musa.org.za';
            $input['user'] = 'info@musa.org.za';
            $input['confirm_password'] = 'musa@456';
            $input['password'] = 'musa@456';
        }

        if ($package) {
            $package = str_replace('_builder_', '_', $package);
            if (strpos($package, 'monthly') !== false) {
                $package_arr = explode('_', $package);
                $package = $package_arr[0].'_'.$package_arr[1];
            }
            $input_arr['packagetemplate'] = $package;
        }
        if ($domain == 'cloudtelecoms.co.za' || $domain == 'energyforafrica.co.za') {
            $input_arr['OPT_STORAGE'] = 200000;
        }

        if ($input_arr && is_array($input_arr)) {
            $input = array_merge($input, $input_arr);
        }

        return $this->editAccount($input);
    }

    public function listAccounts()
    {
        return $this->curl('/nodeworx/siteworx', 'listAccounts');
    }

    public function listAllAccounts()
    {
        $results = $this->setServer('host2')->listAccounts();

        return $results;
    }

    public function listAllDomains($include_ip_domain = false)
    {
        $results = $this->listAllAccounts();
        if ($include_ip_domain) {
            return collect($results['payload'])->pluck('domain')->toArray();
        } else {
            return collect($results['payload'])->where('domain', '!=', '96.0.156.in-addr.arpa')->pluck('domain')->toArray();
        }
    }

    public function installSSL()
    {
        $input = ['domain' => $this->domain, 'chain' => 1];

        return $this->curl('/siteworx/ssl', 'install', $input);
    }

    public function listAllBackups()
    {
        return $this->curl('/siteworx/backup', 'listAllBackups');
    }

    public function createBackup($input)
    {
        return $this->curl('/siteworx/backup', 'create', $input);
    }

    public function deleteBackup($input)
    {
        return $this->curl('/siteworx/backup', 'delete', $input);
    }

    public function deleteFailedBackups()
    {
        \DB::table('isp_host_websites')->update(['backup_date' => null, 'backup_status' => null]);
        $sites = \DB::table('isp_host_websites')->where('hosted', 1)->get();

        foreach ($sites as $site) {
            $this->setServer($site->server);
            $this->setDomain($site->domain);
            $result = $this->listAllBackups();
            if ($result && is_array($result) && isset($result['payload'])) {
                $payload = $result['payload'];

                $delete_backups = [];
                $keep_backups = [];

                if (is_array($payload) && ! empty($payload) && count($payload) > 0) {
                    $payload = array_reverse($payload);
                    $status = 'None';
                    foreach ($payload as $backup) {
                        $backup = (array) $backup;
                        if (count($keep_backups) < 2 && $backup['complete'] && $backup['complete'] == true) {
                            if (count($keep_backups) == 0) {
                                $backup_date = gmdate('Y-m-d', $backup['filedate']);
                                \DB::table('isp_host_websites')->where('domain', $site->domain)->update(['backup_date' => $backup_date]);
                            }
                            $keep_backups[] = $backup['filename'];
                        }
                        $status = ($backup['complete'] == true) ? 'Complete' : 'Failed';
                    }
                    \DB::table('isp_host_websites')->where('domain', $site->domain)->update(['backup_status' => $status]);
                    foreach ($payload as $backup) {
                        $backup = (array) $backup;
                        if (! in_array($backup['filename'], $keep_backups)) {
                            $delete_backups[] = $backup['filename'];
                        }

                        if (str_contains($site->package, 'domain_parking')) {
                            $delete_backups[] = $backup['filename'];
                        }
                    }
                }
            }
            if (! empty($delete_backups)) {
                foreach ($delete_backups as $backup) {
                    $input['backups'] = $backup;
                    $this->deleteBackup($input);
                }
            }
        }
    }

    public function rebuildBackupSchedule()
    {
        \DB::table('isp_host_websites')->update(['backup_schedule' => '']);
        $sites = \DB::table('isp_host_websites')->where('hosted', 1)->get();

        foreach ($sites as $site) {
            $this->setServer($site->server);
            $this->setDomain($site->domain);
            $schedule = $this->backupScheduleList();

            if (str_contains($site->package, 'domain_parking')) {
                \DB::table('isp_host_websites')->where('id', $site->id)->update(['backup_schedule' => '']);
                if (! empty($schedule['payload'][0]) && ! empty($schedule['payload'][0]->id)) {
                    $this->backupScheduleDelete($schedule['payload'][0]->id);
                }
            }

            if (! empty($schedule['payload'][0]) && ! empty($schedule['payload'][0]->frequency)) {
                $this->backupScheduleDelete($schedule['payload'][0]->id);
            }

            if (! str_contains($site->package, 'domain_parking')) {
                $this->backupScheduleAdd($site->account_id);
                \DB::table('isp_host_websites')->where('id', $site->id)->update(['backup_schedule' => 'weekly']);
            }
        }
    }

    public function backupScheduleList()
    {
        return $this->curl('/siteworx/backup/schedule', 'listScheduled');
    }

    public function backupScheduleDelete($schedule_id)
    {
        return $this->curl('/siteworx/backup/schedule', 'delete', ['scheduled' => $schedule_id]);
    }

    public function backupScheduleAdd($account_id)
    {
        $frequency = 'weekly';
        $rotate = 1;
        $input = [
            'type' => 'partial',
            'frequency' => $frequency,
            'location' => 'siteworx',
            'rotate' => $rotate,
            'hour' => '0',
            'day_of_week' => '0',
            'backup_dbs' => '1',
            'backup_web' => '1',
        ];

        if ($account_id == 12) {
            // https://forums.interworx.com/t/daily-backup-emails/14240/4
            $input['email_address'] = 'helpdesk@telecloud.co.za';
        } else {
            $account = dbgetaccount($account_id);
            $valid_email = true;

            if (empty($account->email) || ! filter_var($account->email, FILTER_VALIDATE_EMAIL)) {
                $valid_email = false;
            }

            if ($valid_email) {
                $input['email_address'] = $account->email;
            } else {
                $input['email_address'] = 'none@none.url';
            }
        }

        return $this->curl('/siteworx/backup/schedule', 'create', $input);
    }

    public function setHosted()
    {
        $this->updateAllSoaRecords();
        \DB::table('isp_host_websites')->where('server', '!=', 'host1')->update(['hosted' => 0, 'server' => 'external']);
        \DB::table('isp_host_erp_websites')->where('server', '!=', 'host1')->update(['hosted' => 0, 'server' => 'external']);
        $servers = ['host2'];
        foreach ($servers as $server) {
            $sites = $this->setServer($server)->listAccounts();

            foreach ($sites['payload'] as $row) {
                $package = \DB::table('isp_host_websites')->where('domain', $row->domain)->pluck('package')->first();
                $this->setDomain($row->domain)->syncAccount($package);
                if ($server == 'host1') {
                    \DB::table('isp_host_erp_websites')->where('domain', $row->domain)->update(['hosted' => 1, 'server' => $server]);
                } else {
                    \DB::table('isp_host_websites')->where('domain', $row->domain)->update(['hosted' => 1, 'server' => $server]);
                }
            }
        }
    }

    public function installSitebuilder()
    {
        $input = [
            'domain' => $this->domain,
            'php_available' => ['system-php', '/opt/remi/php73'],
            'php_version' => '/opt/remi/php73',
        ];
        $result = $this->editAccount($input);

        $siteworx_input = [
            'domain' => $this->domain,
            'php_version' => '/opt/remi/php73',
        ];
        $this->curl('/siteworx/domains/php', 'edit', $siteworx_input);

        $siteworx = $this->getAccountInfo();

        $unix_user = $siteworx['unixuser'];
        $code = substr(rand(), 0, 7);
        $password = random();
        $domain = $this->domain;
        $server = $this->server;
        $domain_arr = explode('.', $domain);
        $subdomain = $domain_arr[0];

        $site_url = 'http://www.'.$domain;
        $site_path = '/home/'.$unix_user.'/'.$domain.'/html/';
        $builder_path = '/home/_admin/kopage/kopage_files/';
        $pre_path = '/home/_admin/kopage/pre.php';

        $command = 'rm -r '.$site_path.'*
        cp '.$builder_path.'* '.$site_path.' -Rf;
        cp '.$pre_path.' '.$site_path.' -Rf;
        chown '.$unix_user.':'.$unix_user.' '.$site_path.' -Rf;';

        $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $command);

        $command = '~iworx/bin/varpermsfix.pex --siteworx '.$domain;
        $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $command);

        return true;
    }

    public function getSitebuilderAutoLoginUrl()
    {

        if (! $this->hasSitebuilderInstalled()) {
            return 'http://'.$this->domain;
        }

        $siteworx = $this->getAccountInfo();

        $unix_user = $siteworx['unixuser'];

        $domain = $this->domain;

        $site_url = 'http://www.'.$domain;
        $site_path = '/home/'.$unix_user.'/'.$domain.'/html/';
        $builder_data_path = '/home/'.$unix_user.'/'.$domain.'/html/data';
        $pre_path = '/home/_admin/kopage/pre.php';

        $command = 'cd '.$builder_data_path.';
        touch key.1.txt;
        chown '.$unix_user.':'.$unix_user.' '.$builder_data_path.'/key.1.txt;';

        $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $command);

        return 'http://'.$this->domain.'/admin.php?key=1';
    }

    public function hasSitebuilderInstalled()
    {
        $siteworx = $this->getAccountInfo();
        try {
            $unix_user = $siteworx['unixuser'];
            $code = substr(rand(), 0, 7);
            $password = random();
            $domain = $this->domain;
            $server = $this->server;
            $domain_arr = explode('.', $domain);
            $subdomain = $domain_arr[0];

            $site_url = 'http://www.'.$domain;
            $kopage_file_path = '/home/'.$unix_user.'/'.$domain.'/html/pre.php';
            $kopage_image_dir = '/home/'.$unix_user.'/'.$domain.'/html/editor_images';

            $command = '[ -d "'.$kopage_image_dir.'" ] && echo "Directory exists" || echo "Directory does not exist"';
            $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $command);

            if (str_contains($result, 'Directory exists')) {
                return true;
            } else {
                $command = 'cat '.$kopage_file_path;
                $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $command);

                if (str_contains($result, 'kopageID')) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $ex) {
            return 'error '.$ex->getMessage();
        }
    }

    public function deleteSitebuilder()
    {
        /*
        $siteworx = $this->getAccountInfo();

        $unix_user = $siteworx['unixuser'];
        $code = substr(rand(), 0, 7);
        $password = random();
        $domain = $this->domain;
        $server = $this->server;
        $domain_arr = explode('.', $domain);
        $subdomain = $domain_arr[0];
        $site_url = 'http://www.'.$domain;
        $site_path = '/home/'.$unix_user.'/'.$domain.'/html/';
        $builder_path = '/home/cloudso1/builder.cloudsoftware.cc/html/'.$subdomain.'/';
        $server = $domain_details->server;

        $command = 'rm '.$builder_path.' -Rf;';

        $output = shell_exec($command.' 2>&1; echo $?');

        return true;
    */
    }

    public function addSSL()
    {
        if ($this->domain == 'cloudtools.co.za') {
            return false;
        }
        $altNames = [$this->domain, 'mail.'.$this->domain, 'ftp.'.$this->domain, 'www.'.$this->domain];

        $input = [
            'domain' => $this->domain,
            'commonName' => $this->domain,
            'subjectAltName' => $altNames,
        ];

        return $this->curl('/siteworx/ssl', 'generateLetsEncrypt', $input);
    }

    public function addMailOnlySSL()
    {
        $input = [
            'domain' => $this->domain,
            'commonName' => 'mail.'.$this->domain,
        ];

        return $this->curl('/siteworx/ssl', 'generateLetsEncrypt', $input);
    }

    public function checkSSL()
    {
        return $this->curl('/siteworx/ssl', 'listSslInfo');
    }

    public function hasMailSSL()
    {
        $ssl_info = $this->curl('/siteworx/ssl', 'listSslInfo');

        if (! $ssl_info['payload']['config_exists']) {
            return 'no ssl';
        }

        if (! in_array('mail.'.$this->domain, $ssl_info['payload']['alt_names']) && 'mail.'.$this->domain != $ssl_info['payload']['ssl_domain']) {
            return 'missing mail ssl';
        }
        if (time() > $ssl_info['payload']['expiry']['valid_to']) {
            return 'expired ssl';
        }

        return 'valid mail ssl';
    }

    public function verifySubscriptions()
    {
        $domains = $this->listAllDomains();
        foreach ($domains as $domain) {
            $exists = \DB::table('sub_services')->where('detail', $domain)->where('status', '!=', 'Deleted')->count();
            $activation_exists = \DB::table('sub_activations')->where('detail', $domain)->where('status', 'Pending')->count();
            if (! $exists && ! $activation_exists) {
                // siteworx_delete($domain);
            }
        }
    }

    public function verifyAllSSL()
    {
        $payload = $this->listAllAccounts();
        foreach ($payload['payload'] as $record) {
            $domain = $record->domain;

            if ($domain == '96.0.156.in-addr.arpa') {
                continue;
            }
            if ($domain == 'cloudtools.co.za') {
                continue;
            }
            $this->setDomain($domain);

            if ($record->status != 'active') {
                continue;
            }

            $this->setDomain($domain);
            $mail_ssl = $this->hasMailSSL();
            if ($mail_ssl != 'valid mail ssl') {
                $result = $this->addSSL();

                if ($result['status'] == 11) {
                    $mailonly_result = $this->addMailOnlySSL();
                    if ($mailonly_result['status'] == 11) {
                        // debug_email('SSL certificate could not be generated for '.$domain);
                    }
                }
            }
        }
    }
}
