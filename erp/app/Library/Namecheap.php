<?php

class Namecheap extends ApiCurl
{
    public function __construct($debug = false, $sandbox = false)
    {
        //https://www.namecheap.com/support/api/methods/
        $this->api_user = 'cloudtelecoms';
        $this->api_key = '69ba6d68a178451285ab81a578f7dc24';
        $this->service_url = 'https://api.namecheap.com/xml.response';
        if ($sandbox) {
            $this->service_url = 'https://api.sandbox.namecheap.com/xml.response';
        }
        $this->debug = $debug;
    }

    public function getAdminContact()
    {
        $contact = [
            'firstName' => 'Ahmed',
            'lastName' => 'Omar',
            'emailAddress' => 'helpdesk@telecloud.co.za',
            'phone' => '+27.105007500',
            'address' => '1257 Willem Botha Street, Wierdapark',
            'city' => 'Centurion',
            'province' => 'Gauteng',
            'postalCode' => '0182',
            'country' => 'South Africa',
        ];

        return $contact;
    }

    public function getAccountContact($account_id)
    {
        $contact_admin = $this->getAdminContact();
        $user = dbgetaccount($account_id);

        $name = explode(' ', $user->contact);
        if (count($name) >= 2 && ! empty($name[0]) && ! empty($name[1]) && ! is_numeric($name[0]) && ! is_numeric($name[1])) {
            $firstname = $name[0];
            $lastname = $name[1];
        } else {
            $firstname = $contact_admin['firstName'];
            $lastname = $contact_admin['lastName'];
        }
        $contact = [
            'firstName' => $firstname,
            'lastName' => $lastname,
            'emailAddress' => (! empty($user->email)) ? $user->email : $contact_admin['emailAddress'],
            'phone' => (! empty($user->phone) && tel_e164_format($user->phone)) ? tel_e164_format($user->phone) : $contact_admin['telephoneNumber'],
            'address' => (! empty($user->address)) ? $user->address : $contact_admin['address'],
            'city' => (! empty($user->suburb)) ? $user->suburb : $contact_admin['city'],
            'province' => (! empty($user->province)) ? $user->province : $contact_admin['province'],
            'postalCode' => '0182',
            'country' => 'South Africa',
        ];

        return $contact;
    }

    public function register($domain, $account_id, $nameservers = 'localhost,host2.cloudtools.co.za')
    {
        //https://www.namecheap.com/support/api/methods/domains/create/

        $contact_admin = $this->getAdminContact();
        $contact_user = $this->getAccountContact($account_id);

        // basic info
        $data = [];
        $data['DomainName'] = $domain;
        $data['Years'] = 1;
        $data['Nameservers'] = $nameservers;
        // contacts

        $contact_types = ['Registrant', 'Admin', 'Tech', 'AuxBilling'];
        foreach ($contact_types as $type) {
            if ($type == 'Registrant') {
                $contact = $contact_admin;
            } else {
                $contact = $contact_user;
            }
            $data[$type.'FirstName'] = $contact['firstName'];
            $data[$type.'LastName'] = $contact['lastName'];
            $data[$type.'Address1'] = $contact['address'];
            $data[$type.'City'] = $contact['city'];
            $data[$type.'StateProvince'] = $contact['province'];
            $data[$type.'PostalCode'] = $contact['postalCode'];
            $data[$type.'Country'] = $contact['country'];
            $data[$type.'Phone'] = $contact['phone'];
            $data[$type.'EmailAddress'] = $contact['emailAddress'];
        }

        // extra attributes
        //https://www.namecheap.com/support/api/extended-attributes/
        /*
        Required for .us, .eu, .ca, .co.uk, .org.uk, .me.uk, .nu , .com.au, .net.au, .org.au, .es, .nom.es, .com.es, .org.es, .de, .fr TLDs only
        */
        $tld = get_tld($domain);
        if ($tld == 'ai') {
            $data['Years'] = 2;
        }
        if ($tld == 'us') {
            $data['RegistrantNexus'] = 'C31';
            $data['RegistrantPurpose'] = 'P1';
        }
        if ($tld == 'eu') {
            $data['EUAgreeWhoisPolicy'] = 'YES';
            $data['EUAgreeDeletePolicy'] = 'YES';
        }
        if ($tld == 'co.uk') {
            $data['COUKLegalType'] = 'FIND';
            $data['COUKRegisteredfor'] = $contact_user['firstName'].' '.$contact_user['lastName'];
        }
        if ($tld == 'me.uk') {
            $data['MEUKLegalType'] = 'FIND';
            $data['MEUKRegisteredfor'] = $contact_user['firstName'].' '.$contact_user['lastName'];
        }
        if ($tld == 'org.uk') {
            $data['ORGUKLegalType'] = 'FIND';
            $data['ORGUKRegisteredfor'] = $contact_user['firstName'].' '.$contact_user['lastName'];
        }

        return $this->curl('namecheap.domains.create', $data, 'get');
    }

    public function getList($list_type = 'ALL', $page_number = 1)
    {
        //https://www.namecheap.com/support/api/methods/domains/get-list/
        $data['ListType'] = $list_type;
        $data['PageSize'] = 100;
        $data['Page'] = $page_number;

        return $this->curl('namecheap.domains.getList', $data);
    }

    public function getContacts($domain)
    {
        //https://www.namecheap.com/support/api/methods/domains/get-list/
        $data['DomainName'] = $domain;

        return $this->curl('namecheap.domains.getContacts', $data);
    }

    public function getTldList()
    {
        //https://www.namecheap.com/support/api/methods/domains/get-tld-list/
        return $this->curl('namecheap.domains.getTldList');
    }

    public function setContacts($account_id, $domain)
    {
        //https://www.namecheap.com/support/api/methods/domains/set-contacts/

        $contact_admin = $this->getAdminContact();
        $contact_user = $this->getAccountContact($account_id);

        // basic info
        $data = [];
        $data['DomainName'] = $domain;

        $contact_types = ['Registrant', 'Admin', 'Tech', 'AuxBilling'];
        foreach ($contact_types as $type) {
            if ($type == 'Registrant') {
                $contact = $contact_admin;
            } else {
                $contact = $contact_user;
            }
            $data[$type.'FirstName'] = $contact['firstName'];
            $data[$type.'LastName'] = $contact['lastName'];
            $data[$type.'Address1'] = $contact['address'];
            $data[$type.'City'] = $contact['city'];
            $data[$type.'StateProvince'] = $contact['province'];
            $data[$type.'PostalCode'] = $contact['postalCode'];
            $data[$type.'Country'] = $contact['country'];
            $data[$type.'Phone'] = $contact['phone'];
            $data[$type.'EmailAddress'] = $contact['emailAddress'];
        }

        return $this->curl('namecheap.domains.setContacts');
    }

    public function check($domain_list)
    {
        //https://www.namecheap.com/support/api/methods/domains/check/
        $data['DomainList'] = $domain_list;

        return $this->curl('namecheap.domains.check', $data);
    }

    public function reactivate($domain)
    {
        //https://www.namecheap.com/support/api/methods/domains/reactivate/
        $data['DomainName'] = $domain;

        return $this->curl('namecheap.domains.reactivate', $data);
    }

    public function renew($domain)
    {
        //https://www.namecheap.com/support/api/methods/domains/renew/
        $data['DomainName'] = $domain;
        $data['Years'] = 1;

        return $this->curl('namecheap.domains.renew', $data);
    }

    public function getRegistrarLock($domain)
    {
        //https://www.namecheap.com/support/api/methods/domains/get-registrar-lock/
        $data['DomainName'] = $domain;

        return $this->curl('namecheap.domains.getRegistrarLock', $data);
    }

    public function setRegistrarLock($domain, $lock)
    {
        //https://www.namecheap.com/support/api/methods/domains/set-registrar-lock/
        $data['DomainName'] = $domain;
        $data['LockAction'] = ($lock) ? 'LOCK' : 'UNLOCK';

        return $this->curl('namecheap.domains.setRegistrarLock', $data);
    }

    public function getInfo($domain)
    {
        //https://www.namecheap.com/support/api/methods/domains/get-info/
        $data['DomainName'] = $domain;

        return $this->curl('namecheap.domains.getInfo', $data);
    }

    public function setNameservers($domain, $nameservers = 'host3.cloudtools.co.za,host4.cloudtools.co.za')
    {
        //https://www.namecheap.com/support/api/methods/domains-dns/set-custom/
        $tld = get_tld($domain);
        $domain_arr = explode('.', $domain);
        $sld = $domain_arr[0];
        $data['SLD'] = $sld;
        $data['TLD'] = $tld;
        $data['Nameservers'] = $nameservers;

        return $this->curl('namecheap.domains.dns.setCustom', $data);
    }

    public function transfer($domain, $epp_code)
    {
        //https://www.namecheap.com/support/api/methods/domains-transfer/create/
        /*You can only transfer .biz, .ca, .cc, .co, .com, .com.es, .com.pe, .es, .in, .info,
        .me, .mobi, .net, .net.pe, .nom.es, .org, .org.es, .org.pe, .pe, .tv, .us domains through API at this time.*/
        $data['DomainName'] = $domain;
        $data['Years'] = 1;
        $data['EPPCode'] = $epp_code;

        return $this->curl('namecheap.domains.transfer.create', $data);
    }

    public function getTransferStatus($transfer_id)
    {
        //https://www.namecheap.com/support/api/methods/domains-transfer/get-status/
        $data['TransferID'] = $transfer_id;

        return $this->curl('namecheap.domains.transfer.getStatus', $data);
    }

    public function updateTransferStatus($transfer_id)
    {
        //https://www.namecheap.com/support/api/methods/domains-transfer/update-status/s/
        $data['TransferID'] = $transfer_id;
        $data['Resubmit'] = true;

        return $this->curl('namecheap.domains.transfer.updateStatus', $data);
    }

    public function getTransferList($list_type = 'ALL')
    {
        //https://www.namecheap.com/support/api/methods/domains-transfer/get-list/
        $data['ListType'] = $list_type;

        return $this->curl('namecheap.domains.transfer.getList', $data);
    }

    public function getBalances()
    {
        //https://www.namecheap.com/support/api/methods/users/get-balances/
        return $this->curl('namecheap.users.getBalances');
    }

    public function getPricing($tld = false, $product_type = 'DOMAIN')
    {
        //https://www.namecheap.com/support/api/methods/users/get-pricing/
        $data['ProductType'] = $product_type;
        if ($tld) {
            $data['ProductName'] = $tld;
        }

        return $this->curl('namecheap.users.getPricing', $data);
    }

    public function importPricing()
    {
        $pricing = $this->getPricing();
        if (empty($pricing)) {
            return false;
        }
        try {
            $pricing = $this->parseXml($pricing);
        } catch (\Throwable $ex) {
            return false;
        }
        foreach ($pricing['CommandResponse']['UserGetPricingResult']['ProductType']['ProductCategory'] as $product_category) {
            $action = $product_category['@attributes']['Name'];
            foreach ($product_category['Product'] as $tld_info) {
                $tld = $tld_info['@attributes']['Name'];
                $price_info = (isset($tld_info['Price'][0])) ? $tld_info['Price'][0]['@attributes'] : $tld_info['Price']['@attributes'];
                $duration = $price_info['Duration'];
                $duration_type = $price_info['DurationType'];
                $price = $price_info['YourPrice'];
                $currency = $price_info['Currency'];

                $exists = \DB::connection('default')->table('isp_hosting_tlds')
                    ->where('action', $action)->where('tld', $tld)->where('provider', 'Namecheap')
                    ->count();

                $data = [
                    'tld' => $tld,
                    'action' => $action,
                    'duration' => $duration,
                    'duration_type' => $duration_type,
                    'price' => $price,
                    'currency' => $currency,
                    'provider' => 'Namecheap',
                ];

                if ($exists) {
                    \DB::connection('default')->table('isp_hosting_tlds')
                        ->where('action', $action)->where('tld', $tld)->where('provider', 'Namecheap')
                        ->update($data);
                } else {
                    \DB::connection('default')->table('isp_hosting_tlds')
                        ->insert($data);
                }
            }
        }

        $tld_info = $this->getTldList();
        $tld_info = $this->parseXml($tld_info);
        foreach ($tld_info['CommandResponse']['Tlds']['Tld'] as $tld_row) {
            $info = $tld_row['@attributes'];
            $tld = $info['Name'];
            $data = [
                'api_register' => ($info['IsApiRegisterable'] == 'true') ? 1 : 0,
                'api_transfer' => ($info['IsApiTransferable'] == 'true') ? 1 : 0,
                'api_renew' => ($info['IsApiRenewable'] == 'true') ? 1 : 0,
                'epp_required' => ($info['IsEppRequired'] == 'true') ? 1 : 0,
            ];

            \DB::connection('default')->table('isp_hosting_tlds')
                ->where('tld', $tld)->where('provider', 'Namecheap')
                ->update($data);
        }

        $currencies = \DB::connection('default')->table('isp_hosting_tlds')->groupBy('currency')->pluck('currency')->toArray();
        foreach ($currencies as $currency) {
            if ($currency == 'ZAR') {
                \DB::connection('default')->statement('UPDATE isp_hosting_tlds SET price_zar = price where currency="'.$currency.'"');
            } else {
                $exchange_rate = get_exchange_rate(null, $currency, 'ZAR');
                \DB::connection('default')->statement('UPDATE isp_hosting_tlds SET price_zar = price*'.$exchange_rate.' where currency="'.$currency.'"');
            }
        }

        \DB::connection('default')->statement('UPDATE isp_hosting_tlds SET retail_price = price_zar + ((price_zar/100)* retail_markup)');
        \DB::connection('default')->statement('UPDATE isp_hosting_tlds SET wholesale_price = price_zar + ((price_zar/100)* wholesale_markup)');
    }

    public function parseXml($xml)
    {
        $doc = new DOMDocument;
        $doc->loadXML($xml);
        $root = $doc->documentElement;
        $output = $this->domnode_to_array($root);
        $output['@root'] = $root->tagName;

        return $output;
    }

    private function domnode_to_array($node)
    {
        try {
            $output = [];

            switch ($node->nodeType) {
                case XML_CDATA_SECTION_NODE:
                case XML_TEXT_NODE:
                    $output = trim($node->textContent);
                    break;
                case XML_ELEMENT_NODE:
                    for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                        $child = $node->childNodes->item($i);
                        $v = $this->domnode_to_array($child);

                        if (isset($child->tagName) && $child->tagName != 'Categories') {
                            $t = $child->tagName;
                            if (! isset($output[$t])) {
                                $output[$t] = [];
                            }
                            $output[$t][] = $v;
                        } elseif ($v || $v === '0') {
                            $output = (string) $v;
                        }
                    }

                    if ($node->attributes->length && ! is_array($output)) {
                        $output = ['@content' => $output];
                    }

                    if (is_array($output)) {
                        if ($node->attributes->length) {
                            $a = [];
                            foreach ($node->attributes as $attrName => $attrNode) {
                                $a[$attrName] = (string) $attrNode->value;
                            }

                            $output['@attributes'] = $a;
                        }

                        foreach ($output as $t => $v) {
                            if (is_array($v) && count($v) == 1 && $t != '@attributes') {
                                $output[$t] = $v[0];
                            }
                        }
                    }

                    break;
            }

            return $output;
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
        }
    }

    protected function setCurlParams($endpoint, $args, $method)
    {
        // https://www.namecheap.com/support/api/global-parameters/
        $endpoint_url = $this->service_url;
        $args['ApiUser'] = $this->api_user;
        $args['ApiKey'] = $this->api_key;
        $args['Command'] = $endpoint;
        $args['UserName'] = $this->api_user;
        $args['ClientIp'] = '156.0.96.73';

        return ['endpoint_url' => $endpoint_url, 'args' => $args];
    }
}
