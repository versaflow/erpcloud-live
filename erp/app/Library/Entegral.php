<?php

/*
Herewith details on your new Sync API sandbox account. The Sync API allows for listing syndication to popular portals via one source.



Username: Ahmed Sync API Sandbox

Password: 4c6f5fdb-1f28-4b19-aa49-01e2c969bda1

SourceID: 37

Endpoint for both sandbox and live environments: http://sync.entegral.net/api/

Documentation: https://sync2.docs.apiary.io/#introduction/getting-started



Support:

·  We provide email support during business hours (8am-4pm) and will normally respond within 1 business day to any queries.

·  Please send all support requests to support@entegral.net so we can assign to an available developer.

Testing:

·  We recommend testing using portal 'flex' under your dev account to create offices, agents and listings.

·  If you use the FlexTL platform, the website can be linked to one or more offices you've created in the Sync API platform. Simply send through the ClientOfficeID's value(s) and we will link it up. You will then be able to view records created on the FlexTL platform.

·  If you are going to use Sync API for only one office, we recommend just manually creating that office using a tool like https://www.getpostman.com/ and then focus your development on the agent+listing calls.

·  If you are going to do a data takeon for clients who are on paid portals like Property24 and Private Property, let our support team know, as you will be required to link the existing listing references on those portals with your listings via the PortalListing.ID field.

·  Supply us with the webhook url we can setup that will allow for JSON postbacks to your system as per documentation.



Once you are ready to go live:

·  A month-to-month contract will need to be signed with Entegral, we will then supply live credentials to update listings to live portals.

·  Clients will need to sign contracts with the respective paid portals before going live. In these cases portals will provide credentials which can be updated via the Sync API.

·  Entegral will charge a monthly fee per 50 listings as per fee structure on http://www.entegral.net/sync/. The fee is applicable to each individual office created in the Sync API platform.


Dillon Gray / SysOps Engineer
dillon@entegral.biz


*/


use Httpful\Request as ApiRequest;

class Entegral extends ApiCurl
{
    public function __construct($debug = false)
    {
        $this->api_url = 'http://sync.entegral.net/api/';
        $this->master_api_username = 'Ahmed Sync API Sandbox';
        $this->master_password = '4c6f5fdb-1f28-4b19-aa49-01e2c969bda1';
        $this->master_source_id = 37;
        $this->debug = $debug;


        $this->client_office_id = 1;
        $this->office_api_username = 'Ahmed Sync API Sandbox';
        $this->office_api_key = '4c6f5fdb-1f28-4b19-aa49-01e2c969bda1';
    }

    public function setSandbox()
    {
        $this->master_api_username = 'Ahmed Sync API Sandbox';
        $this->master_password = '4c6f5fdb-1f28-4b19-aa49-01e2c969bda1';
        $this->master_source_id = 37;
    }

    public function setDebug()
    {
        $this->debug = 'output';
        return $this;
    }

    public function createAdminOffice($ApiUserName, $ClientOfficeId)
    {
        $post_data = [
            'ApiUserName' => $ApiUserName,
            'ClientOfficeId' => $ClientOfficeId,
            'SourceId' => $this->master_source_id,
        ];

        return $this->curl('admin', $post_data, 'post');
        /**
        result

        {#1164 ▼
        +"apiKey": "4c6f5fdb-1f28-4b19-aa49-01e2c969bda1"
        +"apiId": "4c6f5fdb"
        +"apiUserName": "Ahmed Sync API Sandbox"
        +"clientOfficeId": "1"
        +"isMaster": "0"
        +"sourceId": "37"
        }

        */
    }

    public function saveSettings($settings)
    {
        return $this->curl('settings', $settings, 'post');
    }

    public function setAdminOffice($client_office_id, $office_api_key, $office_api_username)
    {
        $this->client_office_id = $client_office_id;
        $this->office_api_key = $office_api_key;
        $this->office_api_username = $office_api_username;
    }

    public function createOffice()
    {
        $officeData = (object) [
            'clientOfficeID' => 1,
            'name' => 'Kingmans Realty',
            'portalOffice' => [(object)['name' => 'southafrica']],
            'country' => 'South Africa',
            'tel' => '0105007500',
            'email' => 'info@kingmansrealty.co.za',
            'website' => 'https://kingmansrealty.co.za',
            'action' => 'create',
        ];

        return $this->curl('offices', $officeData, 'post');
    }

    public function setOfficePortals($portalData)
    {
        /*
        BoB	Bid-or-Buy	portalName: bob, enabled: 1 or 0, package: -1 (uncapped default), userid={from portal}
        flex	Entegral Flex Websites	portalName: flex, enabled: 1 or 0
        gumtree	Gumtree	portalName: gumtree, enabled: 1 or 0, package: 0 or 20 (20 listings package) or -1 (uncapped package)
        immoAfrica	ImmoAfrica	portalName: immoafrica, enabled: 1 or 0
        mpSA	MyProperty South Africa	portalName: mpsa, enabled: 1 or 0
        mpNam	MyProperty Namibia	portalName: mpnam, enabled: 1 or 0
        iol	IOL Property (Now Property360)	portalName: iol, enabled: 1 or 0
        ppl	Private Property	portalName: ppl, enabled: 1 or 0, guiId: {from portal}
        namibia	Namibia bundle includes HouseFinder	portalName: namibia, enabled: 1 or 0
        southafrica	South Africa bundle includes Ananzi, Residentialpeople, Commercialpeople	portalName: southafrica, enabled: 1 or 0
        propertymatcher	Property Matcher	portalName: propertymatcher, enabled: 1 or 0
        PriceCheck	Includes GotProperty (JunkMail) and Locanto (PriceCheck been removed)	portalName: PriceCheck, enabled: 1 or 0
        flowliving	Flow	portalName: flowliving, enabled: 1 or 0
        p24	Property24	portalName: p24, enabled: 1 or 0, username: {from portal}, password: {from portal}, agencyId: {from portal}
        qwengo	Qwengo	portalName: qwengo, enabled: 1 or 0
        zoopla	Zoopla	portalName: zoopla, enabled: 1 or 0, branchID: {from portal}
        */
    }

    public function createAgent($listing)
    {
        return $this->curl('agents', $listing, 'post');
    }

    public function createListing($listing)
    {
        return $this->curl('listings', $listing, 'post');
    }

    public function getListings($type = false)
    {
        $args = [];
        if ($type) {
            $args['type'] = $type;
        }

        return $this->curl('listings', $args, 'get');
    }


    protected function curl($endpoint, $args = [], $method = 'get')
    {
        if ($endpoint != 'admin' && empty($this->office_api_key)) {
            return 'Office API key required';
        }

        if ($endpoint == 'admin') {
            /*
                if endpoint is admin use master password
                all other endpoints use the apikey returned by admin endpoint;
            */
            $auth_user = $this->master_api_username;
            $auth_key = $this->master_password;
        } else {
            $auth_user = $this->office_api_username;
            $auth_key = $this->office_api_key;
        }

        $url = $this->api_url . $endpoint;
        if ($this->debug == 'output') {
        }

        if ($this->debug == 'log') {
            exception_log($url);
            exception_log($method);
            exception_log($args);
        }


        if ($method == 'post') {
            //$url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::post($url)
                ->sendsJson()
                ->authenticateWith($auth_user, $auth_key)
                ->body($args)
                ->withoutStrictSsl()
                ->send();
        }

        if ($method == 'put') {
            // $url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::put($url)
                ->sendsJson()
                ->authenticateWith($auth_user, $auth_key)
                ->body($args)
                ->withoutStrictSsl()
                ->send();
        }

        if ($method == 'get') {
            $url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::get($url)
                ->sendsJson()
                ->authenticateWith($auth_user, $auth_key)
                ->send();
        }

        if ($this->debug == 'output') {
        }

        if ($this->debug == 'log') {
            exception_log($response);
        }

        if (is_array($response->body) || !empty($response->body)) {
            return $response->body;
        } else {
            return (object) ['intCode' => $response->code];
        }
        return $response;
    }

    protected function buildUrl($url, $data = array())
    {
        return $url . (empty($data) ? '' : '?' . http_build_query($data));
    }
}
