<?php

// List of endpoints
// https://radiusmanager.cloudtelecoms.co.za/ws/rest/v1/application.wadl?detail=true
// https://radiusmanager.cloudtelecoms.co.za/ws/rest/v1/application.wadl/xsd0.xsd

function postClient($url)
{
    global $serviceKey,$vaadinIpAddress,$vaadinUserAgent;
    $client = \Httpful\Request::post($url)
        ->addHeader('service_key', $serviceKey)
        ->addHeader('vaadinIpAddress', $vaadinIpAddress)
        ->addHeader('vaadinUserAgent', $vaadinUserAgent)
        ->sendsJson();

    return $client;
}

function getClient($url)
{
    global $serviceKey,$vaadinIpAddress,$vaadinUserAgent;
    $client = \Httpful\Request::get($url)
        ->addHeader('service_key', $serviceKey)
        ->addHeader('vaadinIpAddress', $vaadinIpAddress)
        ->addHeader('vaadinUserAgent', $vaadinUserAgent)
        ->sendsJson();

    return $client;
}

function curlRadius($endpoint, $params = null, $type)
{
    $ch = curl_init();
    if ('POST' == $type) {
        $data_string = json_encode($params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_URL, 'https://radiusmanager.cloudtelecoms.co.za/ws/rest/v1/'.$endpoint);
    } elseif ('PUT' == $type) {
        $data_string = json_encode($params);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $url = 'https://radiusmanager.cloudtelecoms.co.za/ws/rest/v1/'.$endpoint.'?'.http_build_query($params);
        // curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_URL, 'https://radiusmanager.cloudtelecoms.co.za/ws/rest/v1/'.$endpoint);
    } elseif ('GET' == $type) {
        if ($params) {
            $url = 'https://radiusmanager.cloudtelecoms.co.za/ws/rest/v1/'.$endpoint.'?'.http_build_query($params);
        } else {
            $url = 'https://radiusmanager.cloudtelecoms.co.za/ws/rest/v1/'.$endpoint;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'service_key: '.'3b91cab8-926f-49b6-ba00-920bcf934c2a',
        'vaadinIpAddress: '.$_SERVER['REMOTE_ADDR'],
        'vaadinUserAgent: '.'PHP-API',
        'auth_token: '.$_SESSION['radiustoken'],
    ));

    $result = curl_exec($ch);

    if (false === $result) {
        printf(
            "cUrl error (#%d): %s<br>\n",
            curl_errno($ch),
            htmlspecialchars(curl_error($ch))
        );
    }
    curl_close($ch);

    return $result;
}

function radius_connect()
{
    $endpoint = 'authentication/loginapi';
    $params = array(
        'username' => 'ahmed',
        'password' => 'u0EJS0MFXYdePpJbIuMjqgi1Hfre1QPS',
    );
    $result = json_decode(curlRadius($endpoint, $params, 'POST'));
    if ('SUCCESS' == $result->status) {
        $authtoken = $result->authToken;
        $_SESSION['radiustoken'] = $authtoken;
    } else {
    }
    // print_r($_SESSION['radiustoken']."<BR>");

    $endpoint = 'authentication/session';
    $result = json_decode(curlRadius($endpoint, $params, 'GET'));
    if ('SUCCESS' == $result->status) {
        $authtoken = $result->authToken;
    } else {
    }
    // print_r($result->status."<BR>");
}

function create_RadiusUser($product, $account)
{
    // private Long radiusUserNumber;
    // @NotNull
    // @Size(min = 1)
    // private String username;
    // @NotNull
    // @Size(min = 1)
    // private String password;
    // private String ipAddress;
    // private String currentIpAddress;
    // private boolean active;
    // private String status;
    // @NotNull
    // private JSONPayment payment;
    // private String capStatus;
    // private String memo;
    // @NotNull
    // @Size(min = 1)
    // private String radiusRealm;
    // @NotNull
    // @Size(min = 1)
    // private String radiusProfile;
    // private String activeRadiusProfile;
    // @NotNull
    // @Size(min = 1)
    // private String systemUser;
    // private final List<JSONRadiusUserAttribute> attributes = new ArrayList<>();
    // private final List<JSONRadiusUserIP> ips = new ArrayList<>();
    // private final Set<String> notifications = new HashSet<>();
    // private String customerCode;
    /*
    0 MBps
    04MBps Uncapped
    04MBps Uncapped Business Shield
    04MBps Uncapped Home Shield
    10MBps Uncapped
    10MBps Uncapped Business Shield
    10MBps Uncapped Home Shield
    20MBps Uncapped
    20MBps Uncapped Business Shield
    20MBps Uncapped Home Shield
    30MBps Uncapped
    */
    radius_connect();

    $username = strtolower($account->company);
    $username_arr = preg_split('/\s+/', $username);
    $username = $username_arr[0];
    if (!empty($username_arr[1])) {
        $username .= $username_arr[1];
    }
    $username = preg_replace("/[\W_]+/u", '', $username);
    $username = substr($username, 0, 30);

    $username_unique = \DB::table('sub_services')->where('detail', $username)->count();
    $i = 1;
    while ($username_unique > 0) {
        $username = $username.$i;
        $username_unique = \DB::table('sub_services')->where('detail', $username)->count();
        ++$i;
    }

    $endpoint = 'radiususer';
    $code = $product->provision_package;
    $params['username'] = $username; //auto
    $params['password'] = generate_strong_password(); //auto
    $params['payment'] = array('paymentNumber' => '2');
    //$params['paymentNumber'] = 2;
    $params['radiusRealm'] = 'cloudtelecoms.co.za';
    if (1 != $account->partner_id) {
        $params['radiusRealm'] = 'cloudtools.co.za';
    }
    $params['activeRadiusProfile'] = $product->provision_package; //product
    $params['systemUser'] = 'ahmed';
    $params['active'] = 'true';
    $params['capStatus'] = 'none';
    $params['status'] = 'Active';
    $params['memo'] = '';
    //$params['radiusProfile'] = '20MBps Uncapped Home Shield';
    $params['radiusProfile'] = $product->provision_package; //product
    //$params['ips'] = '';
    //$params['notifications'] = '';
    //$params['customerCode'] = $account->id;

    $result = curlRadius($endpoint, $params, 'POST');

    $result = json_decode($result, true);
    if (!empty($result['ok']) && true == $result['ok']) {
        return ['username' => $params['username'], 'password' => $params['password']];
    }

    return false;
}

function updateRadiusAll()
{
    $subs = \DB::Table('sub_services')->where('provision_type', 'fibre')->get();
    foreach ($subs as $sub) {
        $account = dbgetaccount($sub->account_id);
        echo $account->company.' '.$sub->panel_package.'<br>';
        update_RadiusUserProfile($sub->panel_username, $sub->panel_package);
    }
}

function update_RadiusUserProfile($username, $package)
{
    radius_connect();
    $endpoint = 'radiususerschedulechange';
    $sub = \DB::table('sub_services')->where('detail', $username)->where('provision_type', 'fibre')->get()->first();

    $account = dbgetaccount($sub->account_id);
    $params = [];
    $params['scheduleDate'] = date('U', strtotime('+ 1 minute')) * 1000;
    $params['radiusUser'] = $username;
    if (1 == $account->partner_id) {
        $params['radiusRealm'] = 'cloudtelecoms.co.za';
    } else {
        $params['radiusRealm'] = 'cloudtools.co.za';
    }
    $params['radiusProfile'] = $package;
    $params['systemUser'] = 'ahmed';

    $json = json_encode($params);
    $result = curlRadius($endpoint, $params, 'POST');
}

function radiusUserStatus($username, $status = 0)
{
    radius_connect();

    $endpoint = 'radiususer';
    $params = radiusUserGet($username);

    $params['active'] = ($status) ? true : false;
    $result = curlRadius($endpoint, $params, 'PUT');
}

function radiusUserGet($username = 'home')
{
    radius_connect();
    $endpoint = 'radiususer';

    $result = curlRadius($endpoint, null, 'GET');
    $result = collect(json_decode($result, true));
    foreach ($result as $user) {
        if ($user['username'] == $username) {
            return $user;
        }
    }
}
