<?php

class Xprs
{
    /*
    https://docs.google.com/document/d/1k6ddDqHygp_PEdUwyY5nSo6YUIabaKSGXKi-yBCv_fI/edit#
    */

    public function __construct($debug = false)
    {
        $this->api_token = 'XPRS-c3b7088033db79d';
        $this->label = 'smartsites';
        $this->service_url = 'http://www.cloudtools.co.za';
        $this->debug = $debug;
    }

    public function provisionSitebuilder($account_id, $domain, $theme_id)
    {
        $username = \DB::table('isp_host_sitebuilders')->where('account_id', $account_id)->pluck('username')->first();
        if (! $username) {
            $account = dbgetaccount($account_id);
            $username = 'ct_'.$account_id;
            $result = $this->createUser($username, $account->email, $password, $account->phone, true);
            if ($result->STATUS != 'SUCCESS') {
                return false;
            }
        }

        $result = $this->createSite($username, $domain, $theme_id);
        $site_id = $result->SITE_ID;
        if (! $site_id || $result->STATUS != 'SUCCESS') {
            return false;
        }

        $result = $this->createLicense($username, $site_id, $domain);
        if ($result->STATUS != 'SUCCESS') {
            return false;
        }

        return ['username' => $username, 'name' => $domain, 'sitebuilder_id' => $site_id, 'account_id' => $account_id, 'domain' => $domain, 'licensed' => 1, 'created_at' => date('Y-m-d')];
    }

    /// USERS

    // Creating a New User - Method: POST
    public function createUser($nickname, $email, $password, $phone, $send_email = false)
    {
        $params = [
            'nickname' => $nickname, //nickname: The new user’s nickname (pattern will not be validated)
            'email' => $email, //email (optional): The new user’s email address (pattern will not be validated)
            'password' => $password, //password (optional):The new user’s password (pattern will not be validated)
            'phone' => $phone, //phone (optional): The new user’s phone (pattern will not be validated)
            'send_email' => $send_email, //send_email (optional): Set to true in order to send the label’s registration mail to the user otherwise omit the parameter
        ];

        return $this->curl('/api/v1/users', $params, 'post');
    }

    public function editUserPassword($nickname, $password, $send_email = false)
    {
        $params = [ //nickname: The new user’s nickname (pattern will not be validated)
            'password' => $password, //password (optional):The new user’s password (pattern will not be validated)
            'send_email' => $send_email, //send_email (optional): Set to true in order to send the label’s registration mail to the user otherwise omit the parameter
        ];

        return $this->curl('/api/v1/users/'.$nickname, $params, 'post');
    }

    // Suspending a User
    public function suspendUser($nickname)
    {
        return $this->curl('/api/v1/users/'.$nickname.'/suspend', [], 'post');
    }

    // Restoring a User
    public function restoreUser($nickname)
    {
        return $this->curl('/api/v1/users/'.$nickname.'/restore', [], 'post');
    }

    public function readUser($nickname)
    {
        return $this->curl('/api/v1/users/'.$nickname);
    }

    /// SITE
    public function createSite($nickname, $domain, $theme_id)
    {
        $params = [
            'sitename' => $domain,
            'theme_id' => $theme_id,
        ];

        return $this->curl('/api/v1/users/'.$nickname.'/sites', $params, 'post');
    }

    // create license
    public function createLicense($nickname, $vbid, $domain)
    {
        $params = [
            'vbid' => $vbid,
            'domain' => $domain,
            'connect_domain' => true,
        ];

        return $this->curl('/api/v1/users/'.$nickname.'/licenses', $params);
    }

    // revoke license
    public function revokeLicense($nickname, $vbid)
    {
        return $this->curl('/api/v1/users/'.$nickname.'/licenses/'.$vbid, [], 'delete');
    }

    //publish site
    public function publishSite($nickname, $vbid)
    {
        $params = [
            'nickname' => $nickname,
            'vbid' => $vbid,
        ];

        return $this->curl('/api/publish_site', $params);
    }

    // List user’s sites
    public function getSites($nickname)
    {
        $result = $this->curl('/api/v1/users/'.$nickname.'/sites');
        if ($result->STATUS == 'SUCCESS' && ! empty($result->USER_SITES)) {
            return $result->USER_SITES;
        } else {
            return false;
        }
    }

    public function getThemes($num_themes = 10)
    {
        return $this->curl('/api/v1/themes/'.$num_themes);
    }

    private function curl($endpoint, $args = [], $method = 'get')
    {
        $args['api_token'] = $this->api_token;
        $args['label'] = $this->label;

        $url = $this->service_url.$endpoint;

        if ($this->debug == 'output') {
        }

        if ($this->debug == 'log') {
            exception_log($url);
            exception_log($method);
            exception_log($args);
        }

        if ($method == 'post') {
            $url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::post($url)
                ->body($args)
                ->withoutStrictSsl()
                ->send();
        }

        if ($method == 'put') {
            $url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::put($url)
                ->body($args)
                ->withoutStrictSsl()
                ->send();
        }

        if ($method == 'get') {
            $url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::get($url)
                ->withoutStrictSsl()
                ->send();
        }

        if ($this->debug == 'output') {
        }

        if ($this->debug == 'log') {
            exception_log($response);
        }

        if (! empty($response->body)) {
            return json_decode($response->body);
        } else {
            return (object) ['intCode' => $response->code];
        }

        return $response;
    }

    private function buildUrl($url, $data = [])
    {
        return $url.(empty($data) ? '' : '?'.http_build_query($data));
    }
}

/*

    $xprs = new Xprs();
    $csv = (new \Rap2hpoutre\FastExcel\FastExcel())->import(public_path('/smartsites_users_list.xlsx'));
    foreach ($csv as $s) {
        $sites = $xprs->getSites($s['nickname']);
        if ($sites) {
            foreach ($sites as $site) {
                $suspended = 0;
                if (str_contains($s['nickname'], 'suspended_')) {
                    $suspended = 1;
                }
                $s['nickname'] = str_replace('suspended_', '', $s['nickname']);
                $date = \Carbon\Carbon::createFromFormat('d-m-Y H:i:s', $site->last_modified);
                $created = $date->format('Y-m-d H:i:s');


                $data = [
                   'username' => $s['nickname'],
                   'email' => $s['email'],
                   'name' => $site->name,
                   'phone' => $s['phone'],
                   'sitebuilder_id' => $site->id,
                   'created_at' => $created,
                   'updated_at' => $created,
                   'licensed' => ($site->licensed) ? 1 : 0,
                   'suspended' => $suspended,
               ];

                dbinsert('isp_host_sitebuilders', $data);
            }
        }
    }
*/
