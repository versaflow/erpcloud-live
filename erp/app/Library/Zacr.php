<?php

use App\Library\EPPTCPTransport;

class Zacr extends EPPTCPTransport
{
    protected $debug;
    protected $debug_file;
    protected $session_uuid;
    protected $production;
    protected $nameservers;
    protected $alt_nameservers;
    protected $registrar_id;
    protected $registrar_password;
    protected $registrar_prefix;
    protected $registrar_address_line_1;
    protected $registrar_address_line_2;
    protected $suburb;
    protected $province;
    protected $registrar_phone;
    protected $server;
    protected $port;
    protected $loggedin;

    public function __construct($tld = '', $production = true)
    {
        $this->production = $production;
        $this->debug = false;
 // epp sld endpoint update
        if(!in_array($tld,[ 'co.za', 'org.za','net.za', 'web.za'])){
            echo 'Invalid TLD';
            exit;
        }
        
        $this->registrar_id = 'cloudtel6r2k8g';
        $this->registrar_password = '79f9a51292';
        $this->server = 'epp.zarc.net.za';
        $this->port = 700;
        
        /*
        if ('co.za' == $tld) {
            //Cloud Telecoms: EPP LIVE Account Details for co.za
            $this->registrar_id = 'cloudtel6r2k8g';
            $this->registrar_password = '79f9a51292';
            $this->server = 'epp.coza.net.za';
            $this->port = 3121;
        } elseif ('org.za' == $tld) {
            //Cloud Telecoms: EPP LIVE Account Details for org.za
            $this->registrar_id = 'cloudtel6r2k8g';
            $this->registrar_password = '579feace28';
            $this->server = 'org-epp.registry.net.za';
            $this->port = 3121;
        } elseif ('net.za' == $tld) {
            //Cloud Telecoms: EPP LIVE Account Details for net.za
            $this->registrar_id = 'cloudtel6r2k8g';
            $this->registrar_password = 'e703ead801';
            $this->server = 'net-epp.registry.net.za';
            $this->port = 3121;
        } elseif ('web.za' == $tld) {
            //Cloud Telecoms: EPP LIVE Account Details for web.za
            $this->registrar_id = 'cloudtel6r2k8g';
            $this->registrar_password = '5aa615d86a';
            $this->server = 'web-epp.registry.net.za';
            $this->port = 3121;
        } else {
            echo 'Invalid TLD';
            exit;
        }
        */
        if (!$production) {
            $this->registrar_id = 'cloudtel6r2k8g';
            $this->registrar_password = 'c4d3dd513f';
            $this->server = 'regphase3.dnservices.co.za';
            $this->port = 3121;

            //switch to second user
            //$this->registrar_id = 'cloudtel6r2k8g-2';
            //$this->registrar_prefix = 'CTD';
            //$this->registrar_contact_id = 'CTD1';
        }
        parent::__construct($this->server, $this->port);

        $this->debug_file = '/home/erp/storage/logs/debug.log';
        $this->debug = false;

        $this->nameservers = array(
            'host1.cloudtools.co.za',
            'host2.cloudtools.co.za',
        );

        /*
        There are several global nameservers that provide authority; please use ns1.sedoparking.com and ns2.sedoparking.com as nameservers.
        */
        $this->alt_nameservers = array(
            'ns1.sedoparking.com',
            'ns2.sedoparking.com',
        );

        if (!$this->production) {
            $this->nameservers = $this->alt_nameservers;
        }

        $this->registrar_prefix = 'telecloud';
        $this->registrar_contact_id = 'CT1';
        $this->registrar_address_line_1 = substr('1257 Willem Botha Street', 0, 64);
        $this->registrar_address_line_2 = substr('Wierdapark', 0, 64);
        $this->registrar_suburb = 'Centurion';
        $this->registrar_province = 'Gauteng';
        $this->registrar_phone = '+27.105007500';

        $this->session_uuid();
        $this->login();
    }

    private function login()
    {
        /*
        1000: Access Granted
        2501: Incorrect Credentials
        2307: Accessing Services That Are Unavailable
        2202: Already Authenticated
        */

        /*
        1. The <login> element specifies that a login command is being attempted.
        2. Within the <login> element, various child elements must be included.
        3. The <clID> must be populated. The <clID> element references the registrar's ID that was given as part of the Live Credentials.
        4. The <pw> element must be populated with the registrar's password.
        5. The <version> element refers to the version of the XML schema to be used. co.za will inform registrars of schema version changes if the schema version is increased.
        6. The <lang> element specifies the language to be used. For most registrars this will be en indicating English.
        7. The <svcs> element will include which services the registrar wants to access. In the <objURI> element, the service can be specified. Available services will be made available to registrars.
        8. The <clTRID> element must be populated with a unique identifier. The length of the identifier must be a minimum of 3 characters and a maximum of 64 characters. For every login, the <clTRID> must be populated with a new identifier to avoid idempotentcy.
        */

        $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
        <command>
        <login>
        <clID>'.$this->registrar_id.'</clID>
        <pw>'.$this->registrar_password.'</pw>
        <options>
        <version>1.0</version>
        <lang>en</lang>
        </options>
        <svcs>
        <objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>
        <objURI>urn:ietf:params:xml:ns:contact-1.0</objURI>
         <svcExtension>
                <extURI>http://www.unitedtld.com/epp/charge-1.0</extURI>
         </svcExtension>
        </svcs>
        </login>
        </command>
        </epp>';

        //  <svcExtension>
        //         <extURI>http://www.unitedtld.com/epp/charge-1.0</extURI>
        //  </svcExtension>
        $xml_response = $this->chat($xml);
        //dd($xml_response);
        $xmlData = $this->xmlstr_to_array($xml_response);

        if (isset($xmlData['epp:response']['epp:result']) && '1000' == $xmlData['epp:response']['epp:result']['@attributes']['code'] ||
        '2202' == $xmlData['epp:response']['epp:result']['@attributes']['code']) {
            $this->loggedin = true;
        } else {
            $this->loggedin = false;
            echo 'Access Denied - '.json_encode($xmlData['epp:response']['epp:result']);
            exit;
        }
    }

    public function logout()
    {
        /*
        1500: Logout Successful
        */

        /*
        1. To log out of the system, an EPP Logout command has to be issued to the server. To achieve this, the <logout/> element has to be included within the <command> element. The <logout/> element does not require any further information.
        2. The final required element is the <clTRID> element. Within this element, a unique string must be specified. The string must be a minimum of 3 characters and a maximum of 64.
        */

        $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
        <command>
        <logout/>
        </command>
        </epp>';
        $this->chat($xml);
        $this->loggedin = false;
        $this->close();
    }

    /*
    ZACR RESPONSE FORMAT
    $result = array('code','message','data'); - direct response from zacr
    $result['response'] = custom definded response, array of expected data;
    */

    /*
    DOMAIN OPERATIONS
    */

    public function domain_check($domain)
    {
        /*
        1000: Domain Check Command Completed Successfully
        */

        /*
        1. The check command will indicate if a domain name is currently reserved in a list.
        2. The check command will indicate that a domain name is available for registration if the requesting registrar has reserved the name.
        */
        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <check>
            <domain:check xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>
            </domain:check>
            </check>
            </command>
            </epp>';
            $result = $this->process($xml);
            //dd($result);
            if ('1000' == $result['code']) {
                $response['available'] = $result['data']['domain:chkData']['domain:cd']['domain:name']['@attributes']['avail'];
                if (0 == $response['available']) {
                    $response['reason'] = $result['data']['domain:chkData']['domain:cd']['domain:reason'];
                }
                $result['response'] = $response;
            }

            return $result;
        }
    }

    public function domain_create($domain, $customer, $nameservers = false)
    {
        /*
        1000: Domain Creation Successful
        2302: Domain or Subordinate Nameserver Duplication
        2303: Associated Contact Errors
        2306: Policy Restriction Errors
        */

        /*
        1. The ZACR makes use of the <domain:hostAttr> elements for the creation of domains.
        2. When creating a domain with subordinate hosts, the IP address must be specified for the nameservers in the <domain:hostAttr ip='v4'> (or v6) element. IPv4 and IVP6 are supported.
        3. When creating a domain with delegated hosts the IP address in not required as the IP address has already been  specified on creation of the parent domain.
        4. Multiple IPv4 and IPv6 addresses may be specified by duplicating the <domain:hostAttr ip=''> element for each nameserver.
        5. The <domain:contact type=''> element must be specified 3 times, providing the contact ID for the "admin", "billing" and "tech" contacts for the domain name.
        6. A <domain:pw> must be specified. This password will be used for transfers between registrars. The CO.ZA namespace requires that this element must have a value of "coza" ONLY.
        7. The <domain:period> element may be used to specify the initial duration of the registration. If this element is not provided, the registry will default to a 1 year registration. Please note that for the CO.ZA namespace, this element must have a value of "1".
        8. The ZACR WILL perform nameserver checks on the successful creation of a domain. Unsuccessful checks will result in the domain name having a "serverHold" message attached. Registrars are encouraged to have responsive nameservers prior to registration of the domain.
        9. The ZACR will send poll message notifications to registars for a continuous week if the nameservers are still unresponsive. Please reference the Poll Messages section for an example of the messages.
        */
        if ($nameservers) {
            $this->nameservers = $nameservers;
        }
        if ($domain) {
            $customer_check = $this->contact_check($customer->id);
            //dd($this->registrar_prefix.$customer->id);
            if (0 == $customer_check['response']['available']) {
                $account_id = $this->registrar_prefix.$customer->id;
            } else {
                $account_id = $this->contact_create($customer);
            }

            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp:epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <epp:command>
            <epp:create>
            <domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
            <domain:name>'.$domain.'</domain:name>
            <domain:period unit="y">1</domain:period>
            <domain:ns>';

            foreach ($this->nameservers as $nameserver) {
                $xml .= '<domain:hostAttr>
                <domain:hostName>'.$nameserver.'</domain:hostName>
                </domain:hostAttr>';
            }
            $xml .= '</domain:ns>
            <domain:registrant>'.$account_id.'</domain:registrant>
            <domain:contact type="admin">'.$this->registrar_prefix.$customer->id.'</domain:contact>
            <domain:contact type="tech">'.$this->registrar_prefix.$customer->id.'</domain:contact>
            <domain:contact type="billing">'.$this->registrar_prefix.$customer->id.'</domain:contact>
            <domain:authInfo>
            <domain:pw>coza</domain:pw>
            </domain:authInfo>
            </domain:create>
            </epp:create>
            </epp:command>
            </epp:epp>';

            /*
            add before  </epp:command> for premium domain
            charge amount retrieve from domain check
            // https://registry.net.za/content2.php?wiki=1&contentid=155&title=Price+Charge+Extension
            
            <epp:extension>
            <charge:agreement xmlns:charge="http://www.unitedtld.com/epp/charge-1.0">
            <charge:set>
            <charge:category name="AAAA">PREMIUM</charge:category>
            <charge:type>price</charge:type>
            <charge:amount command="create">345.00</charge:amount>
            </charge:set>
            </charge:agreement>
            </epp:extension>
            */
            $result = $this->process($xml);
            if ('1000' == $result['code']) {
                $result['response'] = true;
            } else {
                $result['response'] = false;
            }

            return $result;
        }
    }

    public function domain_info($domain, $return_expiry = false)
    {
        /*
        1000: Command Completed Successfully
        2303: Domain Does Not Exist
        */

        /*
        1. As the Registrar of Record, all information regarding the domain will be presented on request.
        2. As a registrar that is not the Registrar of Record, certain information regarding the domain will be omitted.
        3. The information provided in the responses below includes certain elements taht are not available in certain ZA Namespaces. Please reference the respective Published Policies and Procedures for information that will be provided.
        4. The ZA Namespaces do not make use of the authInfo password. Declaring this element will return a response code of 2306.
        */

        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp:epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" 
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xmlns:cozadomain="http://co.za/epp/extensions/cozadomain-1-0" 
            xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <epp:command>
            <epp:info>
            <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name hosts="all">'.$domain.'</domain:name>
            </domain:info>
            </epp:info>
            <epp:extension>
            <cozadomain:info 
            xmlns:cozadomain="http://co.za/epp/extensions/cozadomain-1-0" 
            xsi:schemaLocation="http://co.za/epp/extensions/cozadomain-1-0 coza-domain-1.0.xsd">
            </cozadomain:info>
            </epp:extension>
            </epp:command>
            </epp:epp>';

            $result = $this->process($xml);

            if ($return_expiry) {
                $date = Carbon\Carbon::parse($result['data']['domain:infData']['domain:exDate'])->format('Y-m-d');
               
                return $date;
            } else {
                return $result;
            }
        }
    }

    public function domain_update_contact($domain, $customer)
    {
        /*
        1. Add the new nameservers if required, and remove the older nameservers if required. Addition of the nameservers can be seen here , while deletion of the nameservers can be seen here.
        The reason for the removal of the nameserver first is because if the nameservers are working properly, the update will be almost instant instead of having to wait for the 5 day policy period.
        The removal and addition of the nameservers can be done in 1 command as documented here.
        2. Create the new contact that will be applied to the domain as the new registrant. Creation of a contact can be seen here.
        3. Apply the new contact to the domain as the new domain registrant. Changing the registrant can be seen here.
        4. Delete the now unused old contact. The old registrant of the domain is a copy from the previous registrar's records. Deletion of the old contact can be seen here.
        5. The application of the new registrant to the domain will take a total of 5 days. After 5 days, perform an EPP Domain Info command to verify that all the changes are correct. The way to do this can be seen here.
        */

        if ($domain) {
            //$result = $this->domain_cancel_action($domain,'PendingUpdate');
            //dd($result);
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }
            $customers_to_delete = array();
            $domain_info = $this->domain_info($domain);
            $response = $this->contact_create($customer);
            if (false == $response['response']['created']) {
                $response = $this->contact_update($customer, 1);
                $new_contact_id = $this->registrar_prefix.$customer->id;
            } else {
                $new_contact_id = $response['response']['contactid'];
            }
            $result = $this->domain_update_contact_info($domain, $customer);

            return $result;
        }
    }

    public function domain_update_contact_info($domain, $customer)
    {
        if ($domain) {
            $customer_check = $this->contact_check($customer->id);
            if (0 == $customer_check['response']['available']) {
                $new_contact_id = $this->registrar_prefix.$customer->id;
            } else {
                $new_contact = $this->contact_create($customer);
                $new_contact_id = $this->registrar_prefix.$customer->id;
            }

            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <update>
            <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>
            <domain:add>
                <domain:contact type="admin">'.$this->registrar_prefix.$customer->id.'</domain:contact>
                <domain:contact type="tech">'.$this->registrar_prefix.$customer->id.'</domain:contact>
                <domain:contact type="billing">'.$this->registrar_prefix.$customer->id.'</domain:contact></domain:add>
            <domain:rem></domain:rem>';

          

            $xml .= '</domain:update>
            </update>
            </command>
            </epp>';

            $result = $this->process($xml);

            return $result;
        }
    }
    
    

    public function domain_update_registrant($domain, $customer)
    {
        /*
        1. Add the new nameservers if required, and remove the older nameservers if required. Addition of the nameservers can be seen here , while deletion of the nameservers can be seen here.
        The reason for the removal of the nameserver first is because if the nameservers are working properly, the update will be almost instant instead of having to wait for the 5 day policy period.
        The removal and addition of the nameservers can be done in 1 command as documented here.
        2. Create the new contact that will be applied to the domain as the new registrant. Creation of a contact can be seen here.
        3. Apply the new contact to the domain as the new domain registrant. Changing the registrant can be seen here.
        4. Delete the now unused old contact. The old registrant of the domain is a copy from the previous registrar's records. Deletion of the old contact can be seen here.
        5. The application of the new registrant to the domain will take a total of 5 days. After 5 days, perform an EPP Domain Info command to verify that all the changes are correct. The way to do this can be seen here.
        */

        if ($domain) {
            //$result = $this->domain_cancel_action($domain,'PendingUpdate');
            //dd($result);
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }
            $customers_to_delete = array();
            $domain_info = $this->domain_info($domain);
            $response = $this->contact_create($customer);
            if (false == $response['response']['created']) {
                $response = $this->contact_update($customer, 1);
                $new_contact_id = $this->registrar_prefix.$customer->id;
            } else {
                $new_contact_id = $response['response']['contactid'];
            }
            $result = $this->domain_update_registrant_info($domain, $customer);

            return $result;
        }
    }
    
    public function domain_update_registrant_info($domain, $customer)
    {
        if ($domain) {
            $customer_check = $this->contact_check($customer->id);
            if (0 == $customer_check['response']['available']) {
                $new_contact_id = $this->registrar_prefix.$customer->id;
            } else {
                $new_contact = $this->contact_create($customer);
                $new_contact_id = $this->registrar_prefix.$customer->id;
            }

            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <update>
            <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>
            <domain:add>
                <domain:contact type="admin">'.$this->registrar_prefix.$customer->id.'</domain:contact>
                <domain:contact type="tech">'.$this->registrar_prefix.$customer->id.'</domain:contact>
                <domain:contact type="billing">'.$this->registrar_prefix.$customer->id.'</domain:contact></domain:add>
            <domain:rem></domain:rem>';

            $xml .= '<domain:chg>
                <domain:registrant>'.$new_contact_id.'</domain:registrant>
            <domain:authInfo>
            <domain:pw>coza</domain:pw>
            </domain:authInfo>
            </domain:chg>';

            $xml .= '</domain:update>
            </update>
            </command>
            </epp>';

            $result = $this->process($xml);

            return $result;
        }
    }

    public function nameserver_update_flexerp($domain, $nameservers = [])
    {
        $domain_info = $this->domain_info($domain);
        $domain_add = '';
        $domain_remove = '';

        //remove old nameservers add new nameservers
        if (isset($domain_info['data']) && is_array($domain_info['data']['domain:infData']['domain:ns']['domain:hostAttr'])) {
            $domain_remove .= '<domain:ns>';
            foreach ($domain_info['data']['domain:infData']['domain:ns']['domain:hostAttr'] as $attr) {
                $domain_remove .= '<domain:hostAttr>';
                foreach ($attr as $key => $val) {
                    if ('domain:hostName' == $key) {
                        $domain_remove .= '<'.$key.'>'.$val.'</'.$key.'>';
                    }
                }
                $domain_remove .= '</domain:hostAttr>';
            }
            $domain_remove .= '</domain:ns>';
        }

        // $domain_add .= '<domain:ns>
        // <domain:hostAttr>
        // <domain:hostName>ns1.nserver.co.za</domain:hostName>
        // </domain:hostAttr>
        // <domain:hostAttr>
        // <domain:hostName>ns2.nserver.co.za</domain:hostName>
        // </domain:hostAttr>
        // </domain:ns>';
        if (count($nameservers) == 0) {
            $domain_add .= '<domain:ns>
            <domain:hostAttr>
            <domain:hostName>localhost</domain:hostName>
            </domain:hostAttr>
            <domain:hostAttr>
            <domain:hostName>host2.cloudtools.co.za</domain:hostName>
            </domain:hostAttr>
            </domain:ns>';
        } else {
            $domain_add .= '<domain:ns>';
            foreach ($nameservers as $ns) {
                $domain_add .= '<domain:hostAttr>
            <domain:hostName>'.$ns.'</domain:hostName>
            </domain:hostAttr>';
            }
            $domain_add .= '</domain:ns>';
        }

        $result = $this->domain_update($domain, $domain_add, $domain_remove);

        return $result;
    }

    public function nameserver_update($domain, $nameservers = [])
    {
        $domain_info = $this->domain_info($domain);
        $domain_add = '';
        $domain_remove = '';

        //remove old nameservers add new nameservers
        if (isset($domain_info['data']) && is_array($domain_info['data']['domain:infData']['domain:ns']['domain:hostAttr'])) {
            $domain_remove .= '<domain:ns>';
            foreach ($domain_info['data']['domain:infData']['domain:ns']['domain:hostAttr'] as $attr) {
                $domain_remove .= '<domain:hostAttr>';
                foreach ($attr as $key => $val) {
                    if ('domain:hostName' == $key) {
                        $domain_remove .= '<'.$key.'>'.$val.'</'.$key.'>';
                    }
                }
                $domain_remove .= '</domain:hostAttr>';
            }
            $domain_remove .= '</domain:ns>';
        }

        // $domain_add .= '<domain:ns>
        // <domain:hostAttr>
        // <domain:hostName>ns1.nserver.co.za</domain:hostName>
        // </domain:hostAttr>
        // <domain:hostAttr>
        // <domain:hostName>ns2.nserver.co.za</domain:hostName>
        // </domain:hostAttr>
        // </domain:ns>';
        if (count($nameservers) == 0) {
            $domain_add .= '<domain:ns>
            <domain:hostAttr>
            <domain:hostName>host1.cloudtools.co.za</domain:hostName>
            </domain:hostAttr>
            <domain:hostAttr>
            <domain:hostName>host2.cloudtools.co.za</domain:hostName>
            </domain:hostAttr>
            <domain:hostAttr>
            <domain:hostName>host3.cloudtools.co.za</domain:hostName>
            </domain:hostAttr>
            <domain:hostAttr>
            <domain:hostName>host4.cloudtools.co.za</domain:hostName>
            </domain:hostAttr>
            </domain:ns>
            ';
        } else {
            $domain_add .= '<domain:ns>';
            foreach ($nameservers as $ns) {
                $domain_add .= '<domain:hostAttr>
            <domain:hostName>'.$ns.'</domain:hostName>
            </domain:hostAttr>';
            }
            $domain_add .= '</domain:ns>';
        }

        $result = $this->domain_update($domain, $domain_add, $domain_remove);

        return $result;
    }


    public function nameserver_update_glue($domain)
    {
        $domain_info = $this->domain_info($domain);
        $domain_add = '';
        $domain_remove = '';

        //remove old nameservers add new nameservers
        if (isset($domain_info['data']) && is_array($domain_info['data']['domain:infData']['domain:ns']['domain:hostAttr'])) {
            $domain_remove .= '<domain:ns>';
            foreach ($domain_info['data']['domain:infData']['domain:ns']['domain:hostAttr'] as $attr) {
                $domain_remove .= '<domain:hostAttr>';
                foreach ($attr as $key => $val) {
                    if (!is_array($val)) {
                        $domain_remove .= '<'.$key.'>'.$val.'</'.$key.'>';
                    } else {
                        $domain_remove .= '<'.$key.' ip="v4">'.$val['@content'].'</'.$key.'>';
                    }
                }
                $domain_remove .= '</domain:hostAttr>';
            }
            $domain_remove .= '</domain:ns>';
        }
        // $domain_remove = '<domain:ns><domain:hostAttr><domain:hostName>host3.cloudtools.co.za</domain:hostName><domain:hostAddr ip="v4">156.0.96.73</domain:hostAddr></domain:hostAttr><domain:hostAttr><domain:hostName>host4.cloudtools.co.za</domain:hostName><domain:hostAddr ip="v4">156.0.96.74</domain:hostAddr></domain:hostAttr></domain:ns>';
        // $domain_add = '<domain:ns><domain:hostAttr><domain:hostName>host3.versaflow.io</domain:hostName><domain:hostAddr ip="v4">156.0.96.73</domain:hostAddr></domain:hostAttr><domain:hostAttr><domain:hostName>host4.versaflow.io</domain:hostName><domain:hostAddr ip="v4">156.0.96.74</domain:hostAddr></domain:hostAttr></domain:ns>';

        // $domain_add .= '<domain:ns>
        // <domain:hostAttr>
        // <domain:hostName>host1.cloudtools.co.za</domain:hostName>
        // </domain:hostAttr>
        // <domain:hostAttr>
        // <domain:hostName>host2.cloudtools.co.za</domain:hostName>
        // </domain:hostAttr>
        // </domain:ns>';

        $domain_add .= '<domain:ns>
        <domain:hostAttr>
        <domain:hostName>host1.cloudtools.co.za</domain:hostName>
        <domain:hostAddr ip="v4">156.0.96.71</domain:hostAddr>
        </domain:hostAttr>
        <domain:hostAttr>
        <domain:hostName>host2.cloudtools.co.za</domain:hostName>
        <domain:hostAddr ip="v4">156.0.96.72</domain:hostAddr>
        </domain:hostAttr>
        </domain:ns>';
        $result = $this->domain_update($domain, $domain_add, $domain_remove);
        return $result;
    }

    public function domain_lock($domain, $lock = false)
    {
        /*
        convenience function extension of domain update

        1. "A domain update cannot result in less than 2 nameservers" means that if the update was to be accepted, the resulting domain would have less than 2 associated nameservers. The minimum required nameservers for a domain at any time is 2. Please review the <domain:rem> element.

        2. "[STATUS] not supported" means that the status that was attempted to be added through the<domain:add> element is not supported by the ZA Namespace. The following statuses may not be applied to aZA Namespace domain:

        - clientDeleteProhibited
        - clientUpdateProhibited
        - clientRenewProhibited
        - clientTransferProhibited

        The only client side status that can be applied is clientHold.
        */

        if ($domain) {
            $domain_add = false;
            $domain_remove = false;
            if ($lock) {
                $domain_add = '<domain:status s="clientHold"/>';
            } else {
                $domain_remove = '<domain:status s="clientHold"/>';
            }

            return $this->domain_update($domain, $domain_add, $domain_remove);
        }
    }

    public function domain_autorenew($domain, $status = 'true')
    {
        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp:epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" 
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xmlns:cozadomain="http://co.za/epp/extensions/cozadomain-1-0" 
            xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <epp:command>
            <epp:update>
            <domain:update xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
            <domain:name>'.$domain.'</domain:name>
            </domain:update>
            </epp:update>
            <epp:extension>
            <cozadomain:update xsi:schemaLocation="http://co.za/epp/extensions/cozadomain-1-0 coza-domain-1.0.xsd">
            <cozadomain:chg><cozadomain:autorenew>'.$status.'</cozadomain:autorenew></cozadomain:chg></cozadomain:update>
            </epp:extension>
            </epp:command>
            </epp:epp>';

            $result = $this->process($xml);

            if ('1001' == $result['code'] || '2304' == $result['code']) {
                $result['response'] = true;
            } else {
                $result['response'] = false;
            }

            return $result;
        }
    }

    public function domain_update($domain, $domain_add = false, $domain_remove = false, $domain_chg = false)
    {
        /*
        1001: Domain Pending Update
        2303: Object Existence Errors
        2306: Policy Restriction Errors
        2201: Authentication Errors
        2304: Domain Status Prohibits Operation
        */

        /*
        1. To add or remove a status on the domain include the status in the <domain:add> or <domain:rem> elements. Some ZA Namespaces only support certain statuses. Please reference the respective Published Policies and Procedures for further information.
        2. Associated contacts cannot be removed through use of the <domain:rem> element, only updated through use of the <domain:chg> element. Some ZA Namespaces do not support certain contact types. Please reference the respective Published Policies and Procedures for further information.
        3. The ZACR uses the <domain:hostAttr> element to update associated nameservers through the <domain:add> and <domain:rem> elements.
        4. To update the IP address of a subordinate nameserver, the current nameserver must be overwritten in the <domain:add> element, with the new IP address being specified.
        5. To change a host name, the old host must be deleted in the <domain:rem> element and the new host must be added in the <domain:add> element.
        6. The order of elements is important in the XML template. It must follow <domain:add>, <domain:rem>, <domain:chg>.
        7. Multiple updates may be performed through combining the <domain:add>, <domain:rem> and <domain:chg> elements.
        8. Domain may not be updated if they have either of the statuses "pendingUpdate", "clientUpdateProhibited", "pendingDelete".
        9. All update requests will yield a Poll Message for collection. The message will indicate the result of the update.
        10. All update requests will take a certain duration of time as outlined in the respective ZA Namespace Published Policies and Procedures.
        11. The ZA Namespaces do not make use of the <domain:authInfo> element for Update commands.
        12. All update requests will notify the associated domain name Registrant via email.
        */
        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <update>
            <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>';

            if ($domain_add) {
                $xml .= '<domain:add>'.$domain_add.'</domain:add>';
            }

            if ($domain_remove) {
                $xml .= '<domain:rem>'.$domain_remove.'</domain:rem>';
            }

            $xml .= '</domain:update>
            </update>
            </command>
            </epp>';
            // var_dump($xml);
            $result = $this->process($xml);
            //dd($result);
            if ('1001' == $result['code'] || '2304' == $result['code']) {
                $result['response'] = true;
            } else {
                $result['response'] = false;
            }

            return $result;
        }
    }

    public function domain_cancel_action($domain, $action_to_cancel = '')
    {
        /*
        PendingManualSuspension: Action performed when deleting a domain using the <domain:delete> element. The action can only be performed when the status "pendingDelete" is set to the domain and the domain has not expired.
        PendingUpdate: Action performed when updating a domain using the <domain:update> element. The action can only be performed when the status "pendingUpdate" is set to the domain and the domain is not is an ADR process.
        PendingManualDeletion: Action performed after the PendingManualSuspension event, when the domain enters the final phase of deletion. The action can only be performed when the statuses "pendingDelete" and "inactive" are both set on the domain and the domain has not expired.
        PendingGracePeriodSuspension : Action performed when deleting a domain using <domain:delete> element while the domain is still within the Grace period. The action can only be performed when the status "pendingDelete" is set on the domain and the domain is being deleted within it's Grace Period.
        PendingSuspension: Action performed automatically by the registry when domain has expired and enters the Pending Suspension Phase. The action can only be performed when the status "pendingDelete" is set to the domain.
        PendingDeletion: Action performed automatically by the registry when domain has expired and enters the Pending Deletion Phase. The action can only be performed when the statuses "pendingDelete" and "inactive" are both set on the domain.
        PendingClosedRedemption : Action performed when a domain has entered the Closed Redemption Phase. The action can only be performed once a domain has gone through the PendingDeletion Phase and has expired. Canceling the action will result in a Closed Redemption Fee being charged and the domain reinstated for a further year from its expiry.
        */

        if ($domain && $action_to_cancel) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp:epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" 
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xmlns:cozadomain="http://co.za/epp/extensions/cozadomain-1-0" 
            xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <epp:command>
            <epp:update>
            <domain:update xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
            <domain:name>'.$domain.'</domain:name>
            </domain:update>
            </epp:update>
            <epp:extension>
            <cozadomain:update xsi:schemaLocation="http://co.za/epp/extensions/cozadomain-1-0 coza-domain-1.0.
            xsd" cancelPendingAction="'.$action_to_cancel.'">
            </cozadomain:update>
            </epp:extension>
            </epp:command>
            </epp:epp>';

            $result = $this->process($xml);

            if ('1000' == $result['code']) {
                $result['response']['updated'] = true;
            } else {
                $result['response']['updated'] = false;
            }

            return $result;
        }
    }

    public function domain_transfer_query($domain)
    {
        /*
        ZA Transfer Query

        Once a transfer request has been sent and acknowledged by the registry, the Gaining Registrar can verify the status of the transfer.
        To get the status of the transfer, the Gaining Registrar or the Registrar of Record can issue a transfer query on the domain.
        To issue a transfer query an EPP transfer request must be issued with 2 specific elements as follows:
        1. Transfer operation for the transfer must be a "query"; <epp:transfer op="query">
        2. Name of the domain to be queried; <domain:name>
        The <epp:transfer op="query"> element tells the server that the RAR wants to perform a transfer command where the operation must be a "query". Therefore "querying a transfer". In the <domain:name> element, specify the name of the domain that must be queried.

        POSSIBLE RESPONSES

        Below are the possible responses that a server will return when performing a transfer query on a domain.

        1000: Query completed successfully
        A response code of 1000 means that the command was successfully processed by the registry.
        The following information is represented in the result:
        1. The <domain:trnData> parent element contains all the transfer data child elements.
        2. The <domain:trStatus> element shows "pending". This means that the transfer is still awaiting approval from the Registrar of Record, or cancellation from the gaining Registrar.
        3. The <domain:reID> and <domain:reDate> elements show who sent the Transfer request and the date that it was issued.
        4. The <domain:acID> and <domain:acDate> elements show who was informed of the transfer request and the end date by when a response is required.

        2301: Domain not pending transfer
        A response code of 2301 means that the domain is not pending a transfer. This could for the following reasons:
        1. The transfer was already approved and the domain was transferred.
        2. The transfer was canceled by the Gaining Registrar.
        3. The initial transfer request was never sent.
        */

        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp:epp xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" 
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <epp:command>
            <epp:transfer op="query">
            <domain:transfer>
            <domain:name>'.$domain.'</domain:name>
            </domain:transfer>
            </epp:transfer>
            </epp:command>
            </epp:epp>';

            $result = $this->process($xml);

            if ('1000' == $result['code']) {
                $result['response']['updated'] = true;
            } else {
                $result['response']['updated'] = false;
            }

            return $result;
        }
    }

    public function domain_transfer_approve($domain)
    {
        /*
        1. A successful Transfer Approve will instantly transfer the domain to the Gaining Registrar.
        2. Successful transfers will set the renewal period of a domain to 1 year.
        3. Successful transfers will change the status of a domain's Autorenew flag to "false". This is to accommodate the OPTIONAL nature of the Autorenew Extension.
        4. Successful transfers will increase the domain's expiry date by 1 additional year if possible. This will charge the Gaining Registrar's account as outlined in the Fee Schedule.
        5. If the resulting expiry date is 10 years from the date of successful transfer, the expiry date will not be increased and the Gaining Registrar's account will not be charged.
        */
        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }
            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <transfer op="approve">
            <domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>
            </domain:transfer>
            </transfer>
            </command>
            </epp>';

            $result = $this->process($xml);

            return $result;
        }
    }

    public function domain_transfer_cancel($domain)
    {
        /*
        1. A successful Transfer Cancel will immediately cancel an existing transfer request if the domain is pending a transfer.
        2. A successful Transfer Cancel will notify the Registrar of Record via a Poll Message indicating that the transfer request was cancelled.
        3. The Poll Message sent to the Registrar of Record will include a status of "clientRejected" in the transfer data status element.
        */
        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }
            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
                      <command>
                        <transfer op="cancel">
                          <domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                            <domain:name>'.$domain.'</domain:name>
                          </domain:transfer>
                        </transfer>
                      </command>
                    </epp>';

            $result = $this->process($xml);

            return $result;
        }
    }

    public function domain_transfer_reject($domain)
    {
        /*
        1. A successful Transfer Approve will instantly transfer the domain to the Gaining Registrar.
        2. Successful transfers will set the renewal period of a domain to 1 year.
        3. Successful transfers will change the status of a domain's Autorenew flag to "false". This is to accommodate the OPTIONAL nature of the Autorenew Extension.
        4. Successful transfers will increase the domain's expiry date by 1 additional year if possible. This will charge the Gaining Registrar's account as outlined in the Fee Schedule.
        5. If the resulting expiry date is 10 years from the date of successful transfer, the expiry date will not be increased and the Gaining Registrar's account will not be charged.
        */
        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }
            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <transfer op="reject">
            <domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>
            </domain:transfer>
            </transfer>
            </command>
            </epp>';

            $result = $this->process($xml);

            return $result;
        }
    }

    public function domain_transfer_request($domain)
    {
        /*
        1001: Domain Queued for Transfer
        2300: Transfer Already Pending
        */

        /*
        1. Sending a transfer request is performed by the registrar who wants the domain to be under their sponsorship as requested by the domain registrant
        2. Sending an EPP Transfer request requires the following important elements:
        - The transfer operation type <epp:transfer op="request">
        - Name of the domain that must be transferred <domain:name>
        */

        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp:epp xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <epp:command>
            <epp:transfer op="request">
            <domain:transfer>
            <domain:name>'.$domain.'</domain:name>
            </domain:transfer>
            </epp:transfer>
            </epp:command>
            </epp:epp>';

            $result = $this->process($xml);

            if ('1001' == $result['code'] || '2300' == $result['code']) {
                $result['response'] = true;
            } else {
                $result['response'] = false;
            }

            return $result;
        }
    }

    public function domain_renew($domain)
    {
      
        /*
        1000: Successful Renew
        2105: Cannot Renew Domain Past 10 years
        2201: Incorrect Owner
        2303: Incorrect Element Values
        */

        /*
        1. The <domain:period> element may be specified with an attribute value of "y" only. A value between 1 (one) and 10 (ten) years may be specified in the element.
        2. Not specifying the <domain:period> element will default the renewal period to the initial registration period.
        3. Specifying the <domain:period> element with a new value will set the renewal period for the domain to the specified value.
        4. Some ZA Namespaces do not allow the use of the <domain:period> element. Please reference the respective Published Policies and Procedures for more informaiton on allowed renewal periods.
        5. The <domain:curExpDate> must be the exact date of expiration. The simplest way to obtain the date is to perform a Domain Info request.
        6. A successful Renew request will charge the Registrar of Record a Renewal Fee for the domain, multiplied by the amount of years specified in the <domain:period> element, if the element was specified.
        7. A successful Renew request for a domain that is in Closed Redemption will charge the Registrar of Record a Closed Redemption fee.
        8. Domains may not be renewed for more than 10 (ten) years from their current expiry date. Please note that some ZA Namespaces do not allow renewals for up to 10 (ten) years. Reference the respective Published Policies and Procedures for more informaiton on allowed renewal periods.
        */

        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }
            $session_uuid = $this->session_uuid;

            $expiry_date = $this->domain_info($domain, 1);

            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <renew>
            <domain:renew xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>
            <domain:curExpDate>'.$expiry_date.'</domain:curExpDate> 
            <domain:period unit="y">1</domain:period>
            </domain:renew>
            </renew>
            </command>
            </epp>';

            $result = $this->process($xml);
            $success = false;
            if ('1000' == $result['code'] || '2201' == $result['code']) {
                $success = true;
            } 

            return ['success'=>$success,'result'=>$result];
        }
    }

    public function domain_delete($domain)
    {
        /*
        1001: Command Completed Successfully
        2303: Domain Not Found
        2304: Domain Status Prohibits Operation
        2305: Host Dependencies
        */

        /*
        1. A domain that has entered deletion will be suspended for 5 (five) days, then enter the deletion phase for a further 5 (five) days, before finally being deleted.
        2. A domain that has expired and enters deletion automatically will enter the Closed Redemption Phase after the initial 10 (ten) days of suspension and deletion. The Closed Redemption Phase lasts a total of 20 (twenty) days.
        3. Domains in Closed Redemption may not be transferred away or deleted, only renewed.
        4. A domain that has expired (not manually deleted as below) will queue a poll message.
        5. On successful suspension, deletion or entry into Closed Redemption, an email notification will be sent to the Registrant of the domain to notify them of the upcoming changes.
        */

        if ($domain) {
            if (!$this->production && false === strpos($domain, '.test.dnservices.co.za')) {
                $domain .= '.test.dnservices.co.za';
            }

            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <delete>
            <domain:delete xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
            <domain:name>'.$domain.'</domain:name>
            </domain:delete>
            </delete>
            </command>
            </epp>';

            $result = $this->process($xml);

            if ('1001' == $result['code'] || '2201' == $result['code']) {
                $result['response'] = true;
            } else {
                $result['response'] = false;
            }

            return $result;
        }
    }

    /*
    CONTACT OPERATIONS
    */

    public function contact_check($account_id)
    {
        /*
        <contact:id>  maxLength '16'
        1000: Contact Check Command Completed Successfully
        */

        /*
        1. The check command will indicate if a contact object is currently reserved in a list.
        2. The check command will indicate that a contact object is available for registration if the requesting registrar has reserved the name.
        */

        if ($account_id) {
            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <check>
            <contact:check
            xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
            <contact:id>'.$this->registrar_prefix.$account_id.'</contact:id>
            </contact:check>
            </check>
            </command>
            </epp>';

            $result = $this->process($xml);

            if ('1000' == $result['code']) {
                $response['available'] = $result['data']['contact:chkData']['contact:cd']['contact:id']['@attributes']['avail'];
                if (0 == $response['available']) {
                    $response['reason'] = $result['data']['contact:chkData']['contact:cd']['contact:reason'];
                }
                $result['response'] = $response;
            }

            return $result;
        }
    }

    public function contact_create($customer, $contact_id = false, $email = false)
    {
        /*
        1000: Contact Creation Successful
        2302: Contact Exists
        2004: Internationalised contact address must only contain ascii characters
        */

        /*
        1. The <contact:id> element in the unique identifier for the contact object. A registrar decides a contact's unique ID.
        2. The <contact:postalInfo type="loc"> element. Within this element the localised contact information may be provided. Localised information may be in UTF8 format, representing the formal version of the information partaining to the locale of the contact.
        3. The <contact:postalInfo type="int"> element. Within this element the internationalised contact information may be provided. Internationalised information must be in ASCII format, representing the internationally readable format of the contact information.
        4. The <contact:name> element must contain the full name of the registrant.
        5. The <contact:addr> element. Within this element, child elements will be included that specify address information about the registrant.
        6. Within the <contact:addr> element, the following child elements must be populated with the relative registrant information: <contact:street>, an OPTIONAL secondary <contact:street> for the suburb, <contact:city>, OPTIONAL <contact:sp> to indicate the province, OPTONAL <contact:pc> to indicate the postal code, <contact:cc> to indicate the country code.
        7. The <contact:voice> to indicate the telephone number of the registrant. The syntax for the value must be a "+" followed by the country dialing code such as "27" for South Africa, followed by a ".", followed by the rest of the digits in the number.
        8. The <contact:fax> element. This tag is OPTIONAL as not all registrants have faxes.
        9. The <contact:email> element to indicate the registrant's e-mail address.
        10. The <contact:authInfo> element is not used by any ZA Namespace therefore it is not shown below.
        11. The <contact:disclose> element is not used by any ZA Namespace therefore it is not shown below.
        */

        if ($customer) {
            if (!$contact_id) {
                $contact_id = $this->registrar_prefix.$customer->id;
            }
            if (!$email) {
                $email = $customer->email;
            }
            $customer->contact = str_replace('&', '', $customer->contact);
            $customer->company = str_replace('&', '', $customer->company);

            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
            <create>
            <contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">
            <contact:id>'.$contact_id.'</contact:id>';

            $xml .= '<contact:postalInfo type="loc">
            <contact:name>'.$customer->contact.'</contact:name>
            <contact:org>'.$customer->company.'</contact:org>
            <contact:addr>';
            $address = (!empty($customer->address)) ? $customer->address : $this->registrar_address_line_1;
            $suburb = (!empty($customer->suburb)) ? $customer->suburb : $this->registrar_suburb;
            $province = (!empty($customer->province)) ? $customer->province : $this->registrar_province;
            $xml .= '<contact:street>'.$address.'</contact:street>';
            $xml .= '<contact:city>'.$suburb.'</contact:city>';
            $xml .= '<contact:sp>'.$province.'</contact:sp>';
            $xml .= '<contact:cc>ZA</contact:cc>
            </contact:addr>
            </contact:postalInfo>
            <contact:voice>'.$this->format_phone_number($customer->phone).'</contact:voice>
            <contact:email>'.$email.'</contact:email>';
            $password = generate_strong_password();
            $xml .= '<contact:authInfo>
            <contact:pw>'.$password.'</contact:pw>
            </contact:authInfo>
            </contact:create>
            </create>
            </command>
            </epp>';

            $result = $this->process($xml);

            if ('1000' == $result['code']) {
                $result['response']['contactid'] = $this->registrar_prefix.$customer->id;
                $result['response'] = true;
            } else {
                $result['response'] = false;
            }

            return $result;
        }
    }

    public function contact_info($account_id)
    {
        /*
        1000: Contact Info Command Completed Successfully
        2303: Contact Does Not Exist
        */

        /*
        1. As the Registrar of Record, all information regarding the contact object will be presented on request.
        2. All ZA Namespaces do not make use of the <contact:pw> or <contact:disclose> elements. This means that regardless of the requesting registrar, all the informaiton pertaining to a contact object will be returned on a successful request.
        */

        if ($account_id) {
            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <info>
            <contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
            <contact:id>'.$this->registrar_prefix.$account_id.'</contact:id>
            </contact:info>
            </info>
            </command>
            </epp>';

            $result = $this->process($xml);

            return $result;
        }
    }

    public function contact_update($customer, $remove_linked_status = false)
    {
        /*
        1000: Contact Update Successful
        1001: Contact Update action 'PendingUpdate' pending in 5 minutes
        2303: Contact Does Not Exist
        2004: Internationalized Address Requires Only ASCII
        2306: Address Information Cannot Be Empty
        2201: Incorrect Owner
        */

        /*
        1. The <contact:id> element is the unique identification code for the contact that must be updated.
        2. The <contact:update> element specifies to the server that an update command must be performed.
        3. The <contact:chg> element specifies to the server that a change has to be applied to the contact.
        4. The <contact:add> element specifies the statuses that must be applied to the contact.
        5. The <contact:rem> element specifies the statuses that must be removed from the contact. Certain statuses may clash and have to be removed in specific order.
        6. All associated information may be updated except for the <contact:id> element.
        7. All ZA Namespaces do not make use of the <contact:disclose> element.
        8. All updates to contact objects must include the <contact:cc> element.
        9. All updated will send a notification email to the contact email address.
        10. The update will only be executed at the end of a 5 day period.
        */

        if ($customer) {
            if ($customer->email == 'ahmed@telecloud.co.za') {
                $customer->email = 'helpdesk@telecloud.co.za';
            }
            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <update>
            <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
            <contact:id>'.$this->registrar_prefix.$customer->id.'</contact:id>';

            $xml .= '
            <contact:chg>';

            $xml .= '<contact:postalInfo type="loc">
            <contact:name>'.clean($customer->contact).'</contact:name>
            <contact:org>'.clean($customer->company).'</contact:org>
            <contact:addr>';
            if (!$customer->address) {
                $customer->address = clean($this->registrar_address_line_1);
            }
            if (!$customer->city) {
                $customer->city = clean($this->registrar_suburb);
            }
            if (!$customer->province) {
                $customer->province = clean($this->registrar_province);
            }
            $xml .= '<contact:street>'.clean($customer->address).'</contact:street>';
            $xml .= '<contact:city>'.clean($customer->city).'</contact:city>';
            $xml .= '<contact:sp>'.clean($customer->province).'</contact:sp>';

            $xml .= '<contact:cc>ZA</contact:cc>
            </contact:addr>
            </contact:postalInfo>
            <contact:voice>'.$this->format_phone_number($customer->phone).'</contact:voice>
            <contact:fax/>
            <contact:email>'.$customer->email.'</contact:email>
            </contact:chg>
            </contact:update>
            </update>
            </command>
            </epp>';
            $result = $this->process($xml);

            if ('1000' == $result['code'] || '1001' == $result['code']) {
                $result['response']['updated'] = true;
            } else {
                $result['response']['updated'] = false;
            }

            return $result;
        }
    }

    public function contact_delete($account_id, $account_id_complete = false)
    {
        /*
        1000: Contact Deletion Successful
        2303: Contact Does Not Exist
        2304: Contact Status Prohibits Deletion
        2305: Domain Dependencies Prohibit Deletion
        2201: Incorrect Owner
        */

        /*
        1. The <contact:id> must be populated with the unique ID of the associated contact object.
        */

        if ($account_id) {
            if (!$account_id_complete) {
                $account_id = $this->registrar_prefix.$account_id;
            }
            $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
            <delete>
            <contact:delete xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
            <contact:id>'.$account_id.'</contact:id>
            </contact:delete>
            </delete>
            </command>
            </epp>';

            $result = $this->process($xml);

            if ('1000' == $result['code']) {
                $result['response']['deleted'] = true;
            } else {
                $result['response']['deleted'] = false;
            }

            return $result;
        }
    }

    public function poll_check()
    {
        /*https://www.registry.net.za/content.php?wiki=1&contentid=17&title=Polling%20and%20Acking%20Messages#transfer_request*/
        $xml = 
        '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
                <poll op="req"/>
            </command>
        </epp>';

        $result = $this->process($xml);

        if ('1301' == $result['code']) {
            $result['response']['poll_contains_message'] = true;
        } else {
            $result['response']['poll_contains_message'] = false;
        }

        return $result;
    }

    public function poll_message_acknowledge($msg_id)
    {
        /*https://www.registry.net.za/content.php?wiki=1&contentid=17&title=Polling%20and%20Acking%20Messages#transfer_request*/
        $xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
            <command>
                <poll op="ack" msgID="'.$msg_id.'"/>
            </command>
        </epp>';

        $result = $this->process($xml);
        if ('1000' == $result['code']) {
            $result['response'] = true;
        } else {
            $result['response'] = false;
        }

        return $result;
    }

    public function get_registrar_id()
    {
        return $this->registrar_id;
    }

    public function get_registrar_balance()
    {
        $xml = '<epp:epp xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" 
        xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" 
        xmlns:cozacontact="http://co.za/epp/extensions/cozacontact-1-0">
        <epp:command>
        <epp:info>
        <contact:info>
        <contact:id>'.$this->registrar_contact_id.'</contact:id>
        </contact:info>
        </epp:info>
        <epp:extension>
        <cozacontact:info>
        <cozacontact:balance>true</cozacontact:balance></cozacontact:info>
        </epp:extension>
        </epp:command>
        </epp:epp>';

        $result = $this->process($xml);

        return $result;
    }

    private function process($xml)
    {
        if ($xml) {
            if ($this->loggedin) {
                if ($this->debug) {
                    $this->log_file($this->greeting);
                }

                $xml_response = $this->chat($xml);
                if ($this->debug) {
                    $this->log_file($xml_response);
                }

                $result = $this->xmlstr_to_array($xml_response);
                $response['code'] = $result['epp:response']['epp:result']['@attributes']['code'];
                $response['message'] = $result['epp:response']['epp:result']['epp:msg'];
                if (isset($result['epp:response']['epp:resData'])) {
                    $response['data'] = $result['epp:response']['epp:resData'];
                }
                if (isset($result['epp:response']['epp:msgQ'])) {
                    $response['poll_queue'] = $result['epp:response']['epp:msgQ'];
                }
                if (isset($result['epp:response']['epp:extension'])) {
                    $response['extension_data'] = $result['epp:response']['epp:extension'];
                }

                return $response;
            }
        }
    }

    public function debug($status = true)
    {
        $this->debug = $status;
    }

    public function log_file($result, $append = true)
    {
        if (!is_string($result)) {
            ob_start();
            var_dump($result);
            $result = ob_get_clean();
        }

        $result = '['.date('Y-m-d H:i:s').'] local.INFO: '.$result;

        if ($append) {
            file_put_contents($this->debug_file, $result.PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents($this->debug_file, $result.PHP_EOL);
        }
    }

    private function session_uuid()
    {
        $randmax_bits = strlen(base_convert(mt_getrandmax(), 10, 2));
        $x = '';

        while (strlen($x) < 128) {
            $maxbits = (128 - strlen($x) < $randmax_bits) ? 128 - strlen($x) : $randmax_bits;
            $x .= str_pad(base_convert(mt_rand(0, pow(2, $maxbits)), 10, 2), $maxbits, '0', STR_PAD_LEFT);
        }

        $a = array();
        $a['time_low_part'] = substr($x, 0, 32);
        $a['time_mid'] = substr($x, 32, 16);
        $a['time_hi_and_version'] = substr($x, 48, 16);
        $a['clock_seq'] = substr($x, 64, 16);
        $a['node_part'] = substr($x, 80, 48);

        $a['time_hi_and_version'] = substr_replace($a['time_hi_and_version'], '0100', 0, 4);
        $a['clock_seq'] = substr_replace($a['clock_seq'], '10', 0, 2);

        $this->session_uuid = $this->registrar_prefix.sprintf(
            '%s-%s-%s-%s-%s',
            str_pad(base_convert($a['time_low_part'], 2, 16), 8, '0', STR_PAD_LEFT),
            str_pad(base_convert($a['time_mid'], 2, 16), 4, '0', STR_PAD_LEFT),
            str_pad(base_convert($a['time_hi_and_version'], 2, 16), 4, '0', STR_PAD_LEFT),
            str_pad(base_convert($a['clock_seq'], 2, 16), 4, '0', STR_PAD_LEFT),
            str_pad(base_convert($a['node_part'], 2, 16), 12, '0', STR_PAD_LEFT)
        );
    }

    private function encode_string($string)
    {
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->registrar_prefix), $string, MCRYPT_MODE_CBC, md5(md5($this->registrar_prefix))));
    }

    private function decode_string($string)
    {
        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->registrar_prefix), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($this->registrar_prefix))), "\0");
    }

    private function xmlstr_to_array($xmlstr)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmlstr);
        $root = $doc->documentElement;
        $output = $this->domnode_to_array($root);
        $output['@root'] = $root->tagName;

        return $output;
    }

    private function domnode_to_array($node)
    {
        $output = array();

        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
            $output = trim($node->textContent);
            break;
            case XML_ELEMENT_NODE:
            for ($i = 0, $m = $node->childNodes->length; $i < $m; ++$i) {
                $child = $node->childNodes->item($i);
                $v = $this->domnode_to_array($child);
                if (isset($child->tagName)) {
                    $t = $child->tagName;
                    if (!isset($output[$t])) {
                        $output[$t] = array();
                    }
                    $output[$t][] = $v;
                } elseif ($v || '0' === $v) {
                    $output = (string) $v;
                }
            }

            if ($node->attributes->length && !is_array($output)) {
                $output = array('@content' => $output);
            }

            if (is_array($output)) {
                if ($node->attributes->length) {
                    $a = array();
                    foreach ($node->attributes as $attrName => $attrNode) {
                        $a[$attrName] = (string) $attrNode->value;
                    }

                    $output['@attributes'] = $a;
                }

                foreach ($output as $t => $v) {
                    if (is_array($v) && 1 == count($v) && '@attributes' != $t) {
                        $output[$t] = $v[0];
                    }
                }
            }

            break;
        }

        return $output;
    }

    private function format_phone_number($number = false)
    {
        if ($number) {
            $formatted_number = $this->valid_phone_number($number);
            if ($formatted_number) {
                if ('270' == substr($formatted_number, 0, 3)) {
                    $formatted_number = substr($formatted_number, 3);
                }
                if ('27' == substr($formatted_number, 0, 2)) {
                    $formatted_number = substr($formatted_number, 2);
                }
                if ('0' == substr($formatted_number, 0, 1)) {
                    $formatted_number = substr($formatted_number, 1);
                }

                return '+27.'.$formatted_number;
            }
        }

        return $this->registrar_phone;
    }

    private function valid_phone_number($number = false)
    {
        if ($number) {
            $number = str_replace(array(' ', '+', '(', ')', '-', '.', ',', '?'), '', $number);
            if (is_numeric($number) && (intval($number) == $number) && strlen($number) >= 9 && intval($number) > 0) {
                return $this->get_numerics($number);
            }
        }

        return null;
    }

    private function get_numerics($str)
    {
        preg_match_all('/\d+/', $str, $matches);

        return implode($matches[0]);
    }
}
