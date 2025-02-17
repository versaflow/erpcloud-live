<?php

function ns2_soap($domain, $controller, $action, $input = null)
{
    ini_set('default_socket_timeout', 600);
    $connect = false;
    $x = 0;
    $client = new \SoapClient('https://156.0.96.72:2443/soap?wsdl', [
        'keep_alive' => false,
        'stream_context' => stream_context_create(
            [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]
        ),
    ]);
    // if ($domain == '')
    //     $key = array('email' => 'ahmed@telecloud.co.za', 'password' => 'Webmin321');
    // else
    //     $key = array('email' => 'helpdesk@telecloud.co.za', 'password' => 'superlicious', 'domain' => $domain);

    if (str_contains($controller, 'siteworx')) {
        $key = ['domain' => $domain,
            'apikey' => '-----BEGIN INTERWORX API KEY-----
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
-----END INTERWORX API KEY-----', ];
    } elseif (str_contains($controller, 'nodeworx')) {
        $key = '-----BEGIN INTERWORX API KEY-----
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

    if ($input) {
        $result = $client->route($key, $controller, $action, $input);
    } else {
        $result = $client->route($key, $controller, $action);
    }

    return $result;
}

function soap($domain, $controller, $action, $input = null)
{
    ini_set('default_socket_timeout', 600);
    $connect = false;
    $x = 0;
    $client = new \SoapClient('http://156.0.96.71:2080/soap?wsdl', [
        'stream_context' => stream_context_create(
            [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]
        ),
    ]);
    // if ($domain == '')
    //     $key = array('email' => 'ahmed@telecloud.co.za', 'password' => 'Webmin321');
    // else
    //     $key = array('email' => 'helpdesk@telecloud.co.za', 'password' => 'superlicious', 'domain' => $domain);
    $is_delete = ($controller == '/nodeworx/siteworx' && $action == 'delete') ? 1 : 0;

    if (! $is_delete && str_contains($controller, 'siteworx')) {
        $key = ['domain' => $domain,
            'apikey' => '-----BEGIN INTERWORX API KEY-----
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
-----END INTERWORX API KEY-----', ];
    } elseif (str_contains($controller, 'nodeworx')) {
        $key = '-----BEGIN INTERWORX API KEY-----
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

    if ($input) {
        $result = $client->route($key, $controller, $action, $input);
    } else {
        $result = $client->route($key, $controller, $action);
    }

    return $result;
}

function siteworx_logout()
{
    $controller = 'siteworx/logout';
    $action = 'logout';
    $result = soap('amabombom.com', $controller, $action);

    return $result;
}

function nodeworx_logout()
{
    $controller = 'nodeworx/logout';
    $action = 'logout';
    $result = soap('', $controller, $action);

    $controller = 'nodeworx/logout';
    $action = 'logout';
    $result = ns2_soap('', $controller, $action);

    return $result;
}

function panel_to_siteworx_all()
{

    $product_ids = \DB::table('isp_host_websites')->pluck('product_id')->filter()->unique()->toArray();
    foreach ($product_ids as $product_id) {
        $package = \DB::table('crm_products')->where('id', $product_id)->pluck('provision_package')->first();
        \DB::table('isp_host_websites')->where('product_id', $product_id)->update(['package' => $package]);
    }
    $sites = \DB::table('isp_host_websites')->where('server', 'host2')->get();
    foreach ($sites as $row) {
        $result = panel_to_siteworx($row->account_id, $row->domain, $row->package);
        echo $row->domain.'<BR>';
    }
}

function panel_to_siteworx($account_id, $domain, $package)
{
    //aa($account_id);
    //aa($domain);
    //aa($package);
    $customer = dbgetaccount($account_id);
    $site = \DB::table('isp_host_websites')->where('domain', $domain)->get()->first();
    if (empty($site) || $site->server != 'host2') {
        return false;
    }
    $email = $customer->email;
    $password = substr(\Erp::encode($domain), 0, 20);

    if (strpos($package, 'monthly') !== false) {
        $package_arr = explode('_', $package);
        $package = $package_arr[0].'_'.$package_arr[1];
    }
    $up = \DB::table('isp_host_websites')->where('domain', $domain)->update(['username' => $email, 'password' => $password]);

    $active = ($customer->status == 'Enabled') ? 1 : 0;

    $input = ['domain' => $domain, 'user' => $email, 'email' => $email, 'password' => $password, 'confirm_password' => $password, 'packagetemplate' => $package, 'status' => $active];
    if ($domain == 'smartsites.co.za') {
        unset($input['packagetemplate']);
    }

    $iw = new \Interworx;
    $r = $iw->setServer($site->server)->setDomain($site->domain)->editAccount($input);

    return true;
}

function siteworx_add_pointer($host, $domain_name, $points_to = 'cloudtelecoms.cloudsoftware.cc')
{
    $controller = 'siteworx/domains/pointer';
    $action = 'add';
    $input = [
        'domain' => $domain_name,
        'redir_type' => 'server_alias',
        'points_to' => $points_to,
    ];
    $result = ns2_soap($host, $controller, $action, $input);

    return $result;
}

function siteworx_list_pointers($host)
{
    $controller = 'siteworx/domains/pointer';
    $action = 'list';
    $result = ns2_soap($host, $controller, $action);

    return $result;
}

function nodeworx_list_dns_zones()
{
    $controller = '/nodeworx/dns/zone';
    $action = 'listZones';
    $input['domain'] = 'nserver.co.za';
    $result = soap('', $controller, $action, '');
    echo '<pre>';
    var_dump($result);
    echo '</pre>';
    exit;
}

function nodeworx_edit_soa_records()
{
    $controller = '/nodeworx/dns/record';
    $action = 'listRecords';
    $result = soap('', $controller, $action);
    foreach ($result['payload'] as $row) {
        if ($row->type == 'SOA') {
            $input['nameserver'] = 'host2.cloudtools.co.za';
            $input['record_id'] = $row->record_id;
            $controller = '/nodeworx/dns/record';
            $action = 'editSOA';
            $result = soap('', $controller, $action, $input);
        }
    }
}

function nodeworx_delete_dns_records()
{
    $controller = '/nodeworx/dns/record';
    $action = 'listRecords';
    $result = soap('', $controller, $action);
    foreach ($result['payload'] as $row) {
        if ($row->type == 'TXT' and $row->target == 'v=spf1 MX A') {
            $input['record_id'] = $row->record_id;
            $controller = '/nodeworx/dns/record';
            $action = 'delete';
            $result = soap('', $controller, $action, $input);
        }
    }
}

function nodeworx_add_dns_records()
{
    $controller = '/nodeworx/dns/zone';
    $action = 'listZoneIds';
    // $input['zone_id'] = $zone_id;
    // $input['host'] = $prefix.'.'.$domain;
    // $input['alias'] = $alias;
    $results = soap('', $controller, $action, $input);
    foreach ($results['payload'] as $result) {
        $zone_id = $result[0];
        $host = $result[1];

        //if ($host != '1.dns-template.com') {
        $result = nodeworx_add_dns_record('TXT', $zone_id, $host, 'v=spf1 MX A');
        //dd($result);
        //	ns1.nserver.co.za hostmaster@1pricemobile.co.za 7200 300 1209600 10800
        //}
    }
}

function nodeworx_delete_cname_record($cname)
{
    $controller = '/nodeworx/dns/record';
    $action = 'listRecords';
    $result = soap('', $controller, $action);
    $records = [];

    foreach ($result['payload'] as $row) {
        if ($row->target == 'pbx.cloudtools.co.za' || $row->target == 'pbx.telecloud.co.za') {
            $records[] = $row;
        }
    }
    foreach ($records as $record) {
        $pbx_domains = \DB::connection('pbx')
            ->table('v_domains')
            ->pluck('domain_name')->toArray();

        if (in_array($record->host, $pbx_domains)) {
            continue;
        }

        if ($record->host == $cname) {
            $input['record_id'] = $record->record_id;
            $controller = '/nodeworx/dns/record';
            $action = 'delete';
            $result = soap('', $controller, $action, $input);
        }
    }
}

function nodeworx_dns_set_ttl($domain = 'cloudtelecoms.co.za', $ttl = 60)
{
    $controller = '/nodeworx/dns/record';
    $action = 'listRecords';
    $result = soap('', $controller, $action);
    $records = [];

    foreach ($result['payload'] as $row) {
        if ($row->domain == $domain) {
            $records[] = $row;
        }
    }
    foreach ($records as $record) {
        $controller = '/nodeworx/dns/record';
        $action = 'edit';
        $input['record_id'] = $record->record_id;
        $input['ttl'] = $ttl;
        $result = soap('', $controller, $action, $input);
    }
}

function nodeworx_delete_unsused_cname_records()
{
    $pbx_domains = \DB::connection('pbx')
        ->table('v_domains')
        ->pluck('domain_name')->toArray();

    $controller = '/nodeworx/dns/record';
    $action = 'listRecords';
    $result = soap('', $controller, $action);
    $records = [];

    foreach ($result['payload'] as $row) {
        if ($row->target == 'pbx.cloudtelecoms.co.za' || $row->target == 'cloudtools.versaflow.io') {
            $records[] = $row;
        }
    }
    foreach ($records as $record) {
        if (in_array($record->host, $pbx_domains)) {
            continue;
        }

        $input['record_id'] = $record->record_id;
        $controller = '/nodeworx/dns/record';
        $action = 'delete';
        $result = soap('', $controller, $action, $input);
    }
}

function nodeworx_add_dns_record($type, $zone_id, $host, $target)
{
    $controller = '/nodeworx/dns/record';
    $action = 'add';
    $input['zone_id'] = $zone_id;
    $input['host'] = $host;
    $input['type'] = $type;
    $input['target'] = $target;
    $input['ttl'] = '60';
    $result = soap('', $controller, $action, $input);

    return $result;
}

function siteworx_add_cname($zone_id, $prefix, $domain, $alias)
{
    $controller = '/siteworx/dns';
    $action = 'addCNAME';
    $input['zone_id'] = $zone_id;
    $input['host'] = $prefix.'.'.$domain;
    $input['alias'] = $alias;
    $result = soap($domain, $controller, $action, $input);

    return $result;
}

function siteworx_add_sitebuilder_dns($domain)
{
    $domain_arr = explode('.', $domain);
    $subdomain = $domain_arr[0];
    $action = 'add';
    $controller = '/siteworx/domains/sub';
    $input['prefix'] = $subdomain;
    $result = ns2_soap('cloudtools.co.za', $controller, $action, $input);

    return $result;
}

function siteworx_delete_sitebuilder_dns($domain)
{
    $domain_arr = explode('.', $domain);
    $subdomain = $domain_arr[0];
    $action = 'delete';
    $controller = '/siteworx/domains/sub';
    $input['prefix'] = $subdomain;
    $input['delete_dir'] = 1;
    $result = soap('smartsites.co.za', $controller, $action, $input);

    return $result;
}

function ns2_siteworx_add_cname($zone_id, $prefix, $domain, $alias)
{
    $controller = '/siteworx/dns';
    $action = 'addCNAME';
    $input['zone_id'] = $zone_id;
    $input['host'] = $prefix.'.'.$domain;
    $input['alias'] = $alias;
    $result = ns2_soap($domain, $controller, $action, $input);

    return $result;
}

function nodeworx_add_cname($zone_id, $prefix, $domain, $alias)
{
    $controller = '/siteworx/dns';
    $action = 'addCNAME';
    $input['zone_id'] = $zone_id;
    $input['host'] = $prefix.'.'.$domain;
    $input['alias'] = $alias;
    $result = soap($domain, $controller, $action, $input);

    return $result;
}

function nodeworx_dns_search($host, $type)
{
    $controller = '/nodeworx/dns/record';
    $action = 'listRecords';
    $result = soap('', $controller, $action, '');
    foreach ($result['payload'] as $row) {
        if ($row->host == $host && $row->type == $type) {
            return $row->record_id;
        }
    }
}

function nodeworx_dns_editA($record_id, $host, $ipaddress)
{
    $controller = '/nodeworx/dns/record';
    $action = 'editA';
    $input['record_id'] = $record_id;
    $input['host'] = $host;
    $input['ipaddress'] = $ipaddress;
    $result = soap('', $controller, $action, $input);

    return $result;
}

function nodeworx_dns_editCNAME($record_id, $host, $alias)
{
    $controller = '/nodeworx/dns/record';
    $action = 'editCNAME';
    $input['record_id'] = $record_id;
    $input['host'] = $host;
    $input['alias'] = $alias;
    $result = soap('', $controller, $action, $input);

    return $result;
}

function ns2_nodeworx_dns_editCNAME($record_id, $host, $alias)
{
    $controller = '/nodeworx/dns/record';
    $action = 'editCNAME';
    $input['record_id'] = $record_id;
    $input['host'] = $host;
    $input['alias'] = $alias;
    $result = soap('', $controller, $action, $input);

    return $result;
}

function nodeworx_list_packages($server)
{
    $controller = '/nodeworx/packages';
    $action = 'listPackages';
    $result = interworx_soap_client('', $server, $controller, $action);

    return $result['payload'];
}

function ns2_siteworx_info($domain = '6feetunder.co.za')
{
    $email = 'helpdesk@telecloud.co.za';
    $password = 'superlicious';
    $controller = '/nodeworx/siteworx';
    $action = 'querySiteworxAccountDetails';

    $input = ['domain' => $domain];

    $result = ns2_soap('', $controller, $action, $input);

    if ($result['status'] == 0 && ! empty($result['payload'])) {
        return $result['payload'];
    } else {
        return false;
    }
}
function siteworx_info($domain = '6feetunder.co.za')
{
    $email = 'helpdesk@telecloud.co.za';
    $password = 'superlicious';
    $controller = '/nodeworx/siteworx';
    $action = 'querySiteworxAccountDetails';

    $input = ['domain' => $domain];
    $result = soap('', $controller, $action, $input);
    if ($result['status'] == 0 && ! empty($result['payload'])) {
        return $result['payload'];
    } else {
        return false;
    }
}

function siteworx_update_emails_all()
{
    $rows = DB::select("select * from isp_host_websites join crm_accounts on (isp_host_websites.account_id = crm_accounts.id) where status = 'Enabled' order by domain");
    foreach ($rows as $row) {
        echo $row->domain.'<BR>';
        siteworx_setcell($row->domain, 'email', $row->email);
    }
}

function siteworx_setcell($domain, $field, $value)
{
    $controller = '/nodeworx/siteworx';
    $action = 'edit';
    $input[$field] = $value;
    $input['domain'] = $domain;
    $result = soap($domain, $controller, $action, $input);

    return $result;
}

function siteworx_update_logins()
{
    $rows = dbgetrows('isp_host_websites');
    foreach ($rows as $row) {
        $active = dbgetcell('erp_users', 'id', $row->account_id, 'active');
        $email = 'helpdesk@telecloud.co.za';
        $password = 'superlicious';

        $package = str_replace('_builder_', '_', $package);
        if (strpos($row->package, 'monthly') !== false) {
            $package_arr = explode('_', $row->package);
            $row->package = $package_arr[0].'_'.$package_arr[1];
        }

        $controller = '/nodeworx/siteworx';
        $action = 'edit';
        $input = ['domain' => $row->domain, 'user' => $email, 'email' => $email, 'password' => $password, 'confirm_password' => $password, 'packagetemplate' => $row->package, 'status' => $active];
        $result = soap('', $controller, $action, $input);

        if ($result['payload'] == 'SiteWorx account edited successfully') {
            echo $row->domain.' - '.$result['payload'].'<br>';
        } else {
            echo '|'.$row->domain.' - Unsuccessful.<br>';
        }
    }
}

function siteworx_list()
{
    \DB::select('update isp_host_websites set status="none"');
    $controller = '/nodeworx/siteworx';
    $action = 'listAccounts';

    foreach ($servers as $server) {
        $packages = nodeworx_list_packages($server);

        $response = interworx_soap_client('', $server, $controller, $action);

        foreach ($response['payload'] as $row) {
            unset($panel_data);
            unset($panel_email);
            unset($panel_company);
            unset($data);
            $domain_account_id = null;

            $panel_data = dbgetrows('isp_host_websites', 'domain', $row->domain);
            if ($panel_data) {
                $panel_data = $panel_data->row();
            }

            $panel_email = '';
            $panel_company = '';
            if ($panel_data->account_id > 0) {
                $panel_email = dbgetcell('erp_users', 'id', $panel_data->account_id, 'email');
                $panel_company = dbgetcell('erp_users', 'id', $panel_data->account_id, 'company');
            }

            $data->server = $server;
            $data->status = $row->status;
            $data->max_storage = $row->max_storage;
            $data->space_used = $row->storage;
            $data->date_created = date('Y-m-d H:i:s', $row->date_created);

            foreach ($packages as $package) {
                $package_storage = siteworx_list_extract_package_storage($package->options);
                if ($data->max_storage == $package_storage) {
                    $data->package = $package->name;
                }
            }

            //check if package match
            $siteworx_updated = 0;
            $package_exists = 0;
            if ($panel_data) {
                if ($panel_data->package && ($panel_data->package != $data->package)) {
                    //check if package on correct server

                    foreach ($packages as $package) {
                        if ($package->name == $panel_data->package) {
                            $package_exists = 1;
                        }
                    }
                    if ($package_exists) {
                        $package = str_replace('_builder_', '_', $package);
                        if (strpos($panel_data->package, 'monthly') !== false) {
                            $package_arr = explode('_', $panel_data->package);
                            $panel_data->package = $package_arr[0].'_'.$package_arr[1];
                        }
                        siteworx_setcell($row->domain, 'packagetemplate', $panel_data->package);
                        $siteworx_updated = 1;
                    }
                }

                if ($panel_email > '' && ($panel_email != $row->email)) {
                    siteworx_setcell($row->domain, 'email', $panel_email);
                }
                if ($panel_company > '' && ($panel_company != $row->nickname)) {
                    siteworx_setcell($row->domain, 'nickname', $panel_company);
                }
            }

            if (! $siteworx_updated) {
                if (! $panel_data) {
                    $data->domain = $row->domain;
                    $data->account_id = 2;
                    insert('isp_host_websites', $data);
                } else {
                    set('isp_host_websites', 'domain', $row->domain, $data);
                }
            }
        }
    }
}

function siteworx_list_extract_package_storage($options)
{
    $opt_arr = explode(',', $options);
    $storage_arr = explode('=', $opt_arr[0]);

    return $storage_arr[1];
}

function siteworx_create_database($name, $username, $password, $domain)
{
    $controller = '/siteworx/mysql/db';
    $action = 'add';
    $input['name'] = $name;
    $input['create_user'] = 1;
    $input['user'] = $username;
    $input['password'] = $password;
    $input['confirm_password'] = $password;
    $result = soap($domain, $controller, $action, $input);

    return $result;
}

function is_valid_domain_name($domain_name)
{
    return preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match('/^.{1,253}$/', $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name); //length of each label
}

function delete_sitebuilder($domain)
{
    $domain_details = \DB::table('isp_host_websites')->where('domain', $domain)->get()->first();

    $domain_arr = explode('.', $domain);
    $subdomain = $domain_arr[0];
    $server = $domain_details->server;

    if ($server == 'host2') {
        $unix_user = ns2_siteworx_info($domain)['unixuser'];
    } else {
        $unix_user = siteworx_info($domain)['unixuser'];
    }

    if (! $unix_user) {
        return false;
    }
    $site_url = 'http://www.'.$domain;
    $site_path = '/home/'.$unix_user.'/'.$domain.'/html/';
    $builder_path = '/home/cloudso1/builder.cloudsoftware.cc/html/'.$subdomain.'/';
    $server = $domain_details->server;

    $command = 'rm '.$builder_path.' -Rf;';

    $output = shell_exec($command.' 2>&1; echo $?');

    return true;
}

// function siteworx_update_package($account_id, $domain, $package)
// {
//     $customer = dbgetaccount($account_id);

//     $controller = '/nodeworx/siteworx';
//     $action = 'edit';

//     $package = str_replace('_builder_', '_', $package);
//     if (false !== strpos($package, 'monthly')) {
//         $package_arr = explode('_', $package);
//         $package = $package_arr[0].'_'.$package_arr[1];
//     }

//     $active = ('Enabled' == $customer->status) ? 1 : 0;
//     $input = array('domain' => $domain, 'packagetemplate' => $package, 'status' => $active);
//     $result = soap('', $controller, $action, $input);
//     if ('SiteWorx account edited successfully' == $result['payload']) {
//         return true;
//     } else {
//         return false;
//     }
// }

// function siteworx_emailbox_get($data)
// {
//     $CI = &get_instance();
//     $controller = '/siteworx/email/box';
//     $action = 'listEmailBoxes';
//     $key = array('email' => $data->email, 'password' => $data->password, 'domain' => $data->domain);

//     $result = interworx_soap_client($key, $data->server, $controller, $action);
//     //x($result); exit;
//     if ('SiteWorx account edited successfully' == $result['payload']) {
//         set('isp_host_websites', 'domain', $domain, 'password', $password);

//         return true;
//     //$subject = "You Hosting Account has been '".$hosting->status ."'";
//         //$message = "Your Hosting Account for '" . $hosting->domain . "' has been '".$hosting->status."'<br/>
//         //Please contact us if you have any questions.";
//         //send_email($account_id, $subject, $message, $email);
//     } else {
//         return $result;
//     }
// }

// function siteworx_mailbox_edit($data)
// {
//     if ('true' == $data->on_vacation) {
//         $data->on_vacation = 1;
//     } else {
//         $data->on_vacation = 0;
//     }

//     $CI = &get_instance();
//     $controller = '/siteworx/email/box';
//     $action = 'edit';
//     $data->quota = '';
//     $input = array('username' => $data->username, 'password' => $data->password, 'confirm_password' => $data->password2);

//     $key = array('email' => $data->customer_email, 'password' => $data->customer_password, 'domain' => $data->domain);
//     $result = interworx_soap_client($key, $data->server, $controller, $action, $input);

//     if (0 == $result['status']) {
//         return true;
//     } else {
//         return $result['payload'];
//     }
// }

// function siteworx_email_add($data)
// {
//     //x($data); exit;
//     $siteworx = get('isp_host_websites', 'domain', $data['domain'], 1)->row();

//     $controller = '/siteworx/email/box';
//     $action = 'add';
//     $input['username'] = $data['username'];
//     $input['password'] = $data['password'];
//     $input['confirm_password'] = $data['password'];

//     $key = array('email' => $siteworx->email, 'password' => $siteworx->password, 'domain' => $data['domain']);
//     $result = interworx_soap_client($key, $siteworx->server, $controller, $action, $input);
//     if (0 == $result['status']) {
//         return true;
//     } else {
//         return $result['payload'];
//     }
// }

// function siteworx_email_delete($params)
// {
//     //extract($params);
//     //var_dump($params);
//     //var_dump("This here");
//     //exit();

//     $input['username'] = $params['username'];

//     $controller = '/siteworx/email/box';
//     $action = 'delete';

//     $siteworx = get('isp_host_websites', 'domain', $params['domain'], 1)->row();
//     $key = array('email' => $siteworx->email, 'password' => $siteworx->password, 'domain' => $params['domain']);
//     $result = interworx_soap_client($key, $siteworx->server, $controller, $action, $input);
//     //x($result);
//     if (0 == $result['status']) {
//         return true;
//     } else {
//         return $result['payload'];
//     }
// }
