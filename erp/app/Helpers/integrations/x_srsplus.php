<?php

/*
Your Staging Account is ready.

URL: https://staging-services.rxportalexpress.com/V1.0/
GUID: fe47191b-3c96-4157-8804-deafe909b453
Staging Portal: https://staging.rxportalexpress.com
Staging Portal UN: cloudtelecomsx
Staging Portal PW: Cloudtele1
*/

class srsplus
{
    public $api_guid;

    public $api_url;

    public function __construct($production = true)
    {
        if (! $production) {
            $this->api_guid = 'fe47191b-3c96-4157-8804-deafe909b453';
            $this->api_url = 'https://staging-services.rxportalexpress.com/V1.0/';
        } else {
            $this->api_guid = '2f7ed8e5-d3a2-4bcf-95e6-9698563ce4f5';
            $this->api_url = 'https://services.rxportalexpress.com/V1.0/';
        }
    }

    protected function api_call($url, $xml_data = null, $post = true)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, $post);
        if (! empty($xml_data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        } // Your array field
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        $result = curl_exec($ch);
        if ($result === false) {
            return false;
        }
        curl_close($ch);

        return $result;
    }

    protected function post_request($command, $data)
    {
        $xml_post = $this->build_xml($command, $data);

        return $this->api_call($this->api_url, $xml_post);
    }

    protected function build_xml($command, $xml_array)
    {
        $xml_node = new SimpleXMLElement('<serviceRequest></serviceRequest>');
        $request_array = [
            'command' => $command,
            'client' => [
                'applicationGuid' => $this->api_guid,
                'clientRef' => 'clientRef',
            ],
            'request' => $xml_array,
        ];
        $xml = $this->array_to_xml($request_array, $xml_node);
        $xml = substr($xml, strpos($xml, '?'.'>') + 2);

        $dom = new DomDocument;
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        $dom->loadXML($xml);
        $xml = $dom->saveXML();

        return $xml;
    }

    protected function array_to_xml($data, &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_string($key)) {
                    if (strpos($key, '_') !== false) {
                        $key_arr = explode('_', $key);
                        $key = $key_arr[0];
                    }
                    $subnode = $xml_data->addChild($key);
                    $this->array_to_xml($value, $subnode);
                } else {
                    foreach ($value as $k => $v) {
                        if (strpos($k, '_') !== false) {
                            $k_arr = explode('_', $k);
                            $k = $k_arr[0];
                        }
                        $xml_data->addChild("$k", htmlspecialchars("$v"));
                    }
                }
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }

        return $xml_data->asXML();
    }

    protected function parse_xml($response)
    {
        if ($response != '') {
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (libxml_get_errors()) {
                $msg = var_export(libxml_get_errors(), true);
                libxml_clear_errors();
                throw new srsplus_exception('HTTP XML response is not parsable: '.$msg);
            }

            return json_decode(json_encode((array) $xml), true);
        } else {
            throw new srsplus_exception('HTTP response is empty');
        }
    }

    protected function parse_schema($xsdstring)
    {
        $xml_array = [];

        $xsd = simplexml_load_string($xsdstring, 'SimpleXMLElement', LIBXML_NOCDATA);

        $xsd->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
        /// get schema fields
        $elements = $xsd->xpath("/xs:schema//xs:element[@name='request']//xs:element");

        foreach ($elements as $element) {
            $el = strval($element['ref']);

            $xml_array[$el] = ['min' => strval($element['minOccurs']), 'max' => strval($element['maxOccurs'])];

            $sub_els = $xsd->xpath("/xs:schema//xs:element[@name='".$el."']//xs:element");

            if (! empty($sub_els)) { // get sub elements
                foreach ($sub_els as $sub_el) {
                    if (! empty($sub_el['ref'])) {
                        $xml_array[$el][strval($sub_el['ref'])] = ['min' => strval($sub_el['minOccurs']), 'max' => strval($sub_el['maxOccurs'])];
                    }
                    if (! empty($sub_el['name'])) {
                        $xml_array[$el][strval($sub_el['name'])] = ['min' => strval($sub_el['minOccurs']), 'max' => strval($sub_el['maxOccurs'])];
                    }
                }
            }
        }

        return $xml_array;
    }

    public function get_schema($command)
    {
        $schema_url = $this->api_url.'schemas/'.$command.'.xsd';
        $schema = $this->api_call($schema_url, false, false);

        return $this->parse_schema($schema);
    }

    public function post_command($command, $data)
    {
        $response = $this->post_request($command, $data);
        $result = $this->parse_xml($response);

        return $result;
    }
}

class srsplus_exception extends Exception {}

function tel_e164_format($number)
{
    if (strlen($number) >= 10) {
        if (substr($number, 0, 1) == '0') {
            return '+27.'.substr($number, 1);
        }

        if (substr($number, 0, 3) == '+27') {
            return '+27.'.substr($number, 3);
        }
    }

    return false;
}

function srs_getschema()
{
    $srsapi = new srsplus;

    return $srsapi->get_schema('userAdd');
}

function srs_user_get($user = '', $domain = '')
{
    if (! empty($user) && ! empty($user->id)) {
        $data = [
            'userId' => 'customer'.$user->id,
        ];
    } elseif (! empty($domain)) {
        $data = [
            'domainName' => $domain,
        ];
    } else {
        return false;
    }

    $srsapi = new srsplus;
    $result = $srsapi->post_command('userGet', $data);
    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['users']['user']['userId'];
    }

    return false;
}

function srs_user_add($user)
{
    $admin_firstname = $firstname = 'Ahmed';
    $admin_lastname = $lastname = 'Omar';
    $admin_address1 = $address1 = '1257 Willem Botha Street, Wierdapark';
    $admin_city = $city = 'Centurion';
    $admin_province = $province = 'Gauteng';
    $admin_mobile = $mobile = '0105007500';
    $admin_email = $email = 'helpdesk@telecloud.co.za';

    $contact_admin = [
        'firstName' => 'Ahmed',
        'lastName' => 'Omar',
        'emailAddress' => 'helpdesk@telecloud.co.za',
        'telephoneNumber' => '+27.105007500',
        'addressLine1' => '1257 Willem Botha Street, Wierdapark',
        'city' => 'Centurion',
        'province' => 'Gauteng',
        'postalCode' => '0182',
        'countryCode' => 'ZA',
        'contactType' => 'Administration',
    ];
    $name = explode(' ', $user->contact);
    if (count($name) >= 2 && ! is_numeric($name[0]) && ! is_numeric($name[1])) {
        $firstname = $name[0];
        $lastname = $name[1];
    } else {
        $firstname = $contact_admin['firstName'];
        $lastname = $contact_admin['lastName'];
    }
    $contact_user = [
        'firstName' => $firstname,
        'lastName' => $lastname,
        'emailAddress' => (! empty($user->email)) ? $user->email : $contact_admin['emailAddress'],
        'telephoneNumber' => (! empty($user->phone) && tel_e164_format($user->phone)) ? tel_e164_format($user->phone) : $contact_admin['telephoneNumber'],
        'addressLine1' => (! empty($user->address)) ? $user->address : $contact_admin['addressLine1'],
        'city' => (! empty($user->suburb)) ? $user->suburb : $contact_admin['city'],
        'province' => (! empty($user->province)) ? $user->province : $contact_admin['province'],
        'postalCode' => '0182',
        'countryCode' => 'ZA',
        'contactType' => 'Registration',
    ];

    $data = [
        'userId' => 'customer'.$user->id,
        'userAccountName' => 'customer'.$user->id,
        'contacts' => ['contact' => $contact_user],
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('userAdd', $data);

    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['userId'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_user_modify($user)
{
    $admin_firstname = $firstname = 'Ahmed';
    $admin_lastname = $lastname = 'Omar';
    $admin_address1 = $address1 = '1257 Willem Botha Street, Wierdapark';
    $admin_city = $city = 'Centurion';
    $admin_province = $province = 'Gauteng';
    $admin_mobile = $mobile = '0105007500';
    $admin_email = $email = 'helpdesk@telecloud.co.za';

    $contact_admin = [
        'firstName' => 'Ahmed',
        'lastName' => 'Omar',
        'emailAddress' => 'helpdesk@telecloud.co.za',
        'telephoneNumber' => '+27.105007500',
        'addressLine1' => '1257 Willem Botha Street, Wierdapark',
        'city' => 'Centurion',
        'province' => 'Gauteng',
        'postalCode' => '0182',
        'countryCode' => 'ZA',
        'contactType' => 'Administration',
    ];

    $contact_user = [
        'firstName' => (count($name) >= 2) ? $name[0] : $contact_admin['firstName'],
        'lastName' => (count($name) >= 2) ? $name[1] : $contact_admin['lastName'],
        'emailAddress' => (! empty($user->email)) ? $user->email : $contact_admin['emailAddress'],
        'telephoneNumber' => (! empty($user->phone) && tel_e164_format($user->phone)) ? tel_e164_format($user->phone) : $contact_admin['telephoneNumber'],
        'addressLine1' => (! empty($user->address)) ? $user->address : $contact_admin['addressLine1'],
        'city' => (! empty($user->suburb)) ? $user->suburb : $contact_admin['city'],
        'province' => (! empty($user->province)) ? $user->province : $contact_admin['province'],
        'postalCode' => '0182',
        'countryCode' => 'ZA',
        'contactType' => 'Registration',
    ];

    $data = [
        'userId' => 'customer'.$user->id,
        'userAccountName' => 'customer'.$user->id,
        'contacts' => ['contact' => $contact_user],
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('userModify', $data);

    if ($result['status']['statusCode'] == '1000') {
        return true;
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_user_status($user_id, $suspend = 1)
{
    $data = [
        'userId' => 'customer'.$user_id,
        'suspend' => $suspend,
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('userSuspend', $data);
    if ($result['status']['statusCode'] == '1000') {
        return true;
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_autorenew($domain_id, $autorenew = 1)
{
    if ($autorenew) {
        $autorenew = 'true';
    } else {
        $autorenew = 'false';
    }
    $data = [
        'productId' => $domain_id,
        'autoRenew ' => $autorenew,
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainAutoRenew', $data);
    if ($result['status']['statusCode'] == '1000') {
        return true;
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_check($domain_name, $tlds = ['com', 'net', 'org', 'biz', 'io', 'cc'])
{
    $data = ['sld' => $domain_name, 'extensions' => []];
    $tld_arr = [];
    foreach ($tlds as $tld) {
        $tld_arr[] = ['extension' => $tld];
    }
    $data['extensions'] = $tld_arr;

    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainCheck', $data);
    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['domain'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_get($domain_name)
{
    $data = ['domains' => ['domainName' => $domain_name]];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainGet', $data);

    /*
     "response" => array:1 [▼
     "domainGet" => array:4 [▼
       "domain" => array:7 [▼
         "userId" => []
         "domainInfo" => array:13 [▼
           "domainName" => "cloudtel.com"
           "productId" => "21373265"
           "domainStatus" => "Active"
           "startDate" => "14-Jun-2018"
           "expiryDate" => "14-Jun-2019"
           "autoRenew" => "Off"
           "registrarLock" => "On"
           "privacy" => "Off"
           "password" => "a1#nu2lpkt8"
           "expiryDateTime" => "2019-06-14T00:00:00-04:00"
           "dnsType" => "CustomDNS"
           "domainexpiryprotection" => "Unavailable"
           "whoisAccuracyDomainStatus" => "VerificationSuccess"
         ]
    */
    if ($result['status']['statusCode'] == '1000') {
        if (isset($result['response']['domainGet']['domain']['domainInfo'])) {
            return $result['response']['domainGet']['domain']['domainInfo'];
        } elseif (isset($result['response']['domainGet']['domain']) && is_array($result['response']['domainGet']['domain'])) {
            foreach ($result['response']['domainGet']['domain'] as $d) {
                $domain_info = $d['domainInfo'];
            }

            return $domain_info;
        }
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_get_status($domain_name)
{
    $data = ['domains' => ['domainName' => $domain_name]];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainGet', $data);
    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['domainGet']['domain']['domainInfo']['domainStatus'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_get_reference($domain_name)
{
    $data = ['domains' => ['domainName' => $domain_name]];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainGet', $data);
    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['domainGet']['domain']['domainInfo']['productId'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_register($domain, $user)
{
    $srs_user_id = srs_user_get($user);

    if (! $srs_user_id) {
        $srs_user_id = srs_user_add($user);
    }

    $nameserver1 = [
        'nsType' => 'Primary',
        'nsName' => 'host1.cloudtools.co.za',
    ];
    $nameserver2 = [
        'nsType' => 'Secondary',
        'nsName' => 'host2.cloudtools.co.za',
    ];

    /*
    returns response->productId which is used as domain identifier for subsequent requests
    */

    $data = [
        'userId' => $srs_user_id,
        'domainName' => $domain,
        'term' => 1,
        'nameservers' => [
            'nameserver_1' => $nameserver1,
            'nameserver_2' => $nameserver2,
        ],
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainAdd', $data);
    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['productId'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_transfer($domain, $user)
{
    /*
        DESCRIPTION
        Transfer a domain from another provider, optionally assigned to a pre-defined user account.
        Transfers are processed off-line. Poll domainGet to confirm final result; if domain status is "Active"
        transfer has succeeded, else "TransferFailed".
        Up to five days should typically be allowed for the transfer process.
        Some registries extend the domain upon transfer (e.g. TLDs are treated in this way), in which case the
        expiry date will be updated for one year (charged as a renewal); otherwise the expiry date will not
        change.
        If contacts are supplied then these are used; otherwise default contacts are retained.
        The current name servers are retained during the transfer (this can be updated by domainModify after
        transfer is complete).
    */

    $srs_user_id = srs_user_get($user);

    if (! $srs_user_id) {
        $srs_user_id = srs_user_add($user);
    }

    /*
    returns response->productId which is used as domain identifier for subsequent requests
    */

    $data = [
        'userId' => $srs_user_id,
        'domainName' => $domain,
    ];

    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainTransferIn', $data);
    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['productId'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_transfer_auth($domain_id, $authcode)
{
    $data = [
        'productId' => $domain_id,
        'authCode' => $authcode,
    ];

    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainTransferUpdateAuthCode', $data);

    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['productId'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_transfer_cancel($domain_id)
{
    $data = [
        'productId' => $domain_id,
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainTransferCancel', $data);

    if ($result['status']['statusCode'] == '1000') {
        return true;
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_edit($domain_id, $user)
{
    $srs_user_id = srs_user_get($user);
    if (! $srs_user_id) {
        $srs_user_id = srs_user_add($user);
    }

    $nameserver1 = [
        'nsType' => 'Primary',
        'nsName' => 'host1.cloudtools.co.za',
    ];
    $nameserver2 = [
        'nsType' => 'Secondary',
        'nsName' => 'host2.cloudtools.co.za',
    ];

    $data = [
        'productId' => $domain_id,
        'nameservers' => [
            'nameserver_1' => $nameserver1,
            'nameserver_2' => $nameserver2,
        ],
    ];

    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainModify', $data);

    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['productId'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_lock($domain_id, $lock = true)
{
    $lock_text = 'False';
    if ($lock) {
        $lock_text = 'True';
    }

    $data = [
        'productId' => $domain_id,
        'registrarLock' => $lock_text,
    ];

    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainLock', $data);
    if ($result['status']['statusCode'] == '1000') {
        return $result['response']['productId'];
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_cancel($domain_id)
{
    $data = ['productId' => $domain_id];

    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainCancel', $data);
    if ($result['status']['statusCode'] == '1000') {
        return true;
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}

function srs_domain_restore($srs_user_id, $domain_id)
{
    $data = [
        'userId' => $srs_user_id,
        'productId' => $domain_id,
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainRestore', $data);
    if ($result['status']['statusCode'] == '1000') {
        return true;
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}
function srs_domain_renew($domain_id)
{
    $data = [
        'productId' => $domain_id,
    ];
    $srsapi = new srsplus;
    $result = $srsapi->post_command('domainRestore', $data);
    if ($result['status']['statusCode'] == '1000') {
        return true;
    }

    return 'Error: '.$result['status']['statusCode'].' - '.$result['status']['statusDescription'];
}
