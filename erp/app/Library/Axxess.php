<?php

use Httpful\Request as ApiRequest;

class Axxess extends ApiCurl
{
    /*
    FIBRE ORDER PROCESS
    render map for latlong and address
    checkFibreAvailability
    getNetworkProviders
    getNetworkProviderProducts
    getAddressTypes

    createClient / get $guidClientId
    createFibreComboService

    */

    public function __construct($debug = false)
    {
        $this->service_url = 'https://rcp.axxess.co.za/';
        $this->username = 'TUR40';
        $this->password = 'Ao@147896';
        $this->authuser = 'ResellerAdmin';
        $this->authpass = 'jFbd5lg7Djfbn48idmlf4Kd';
        $this->providers_table = 'isp_data_products';
        $this->debug = $debug;
    }

    public function setDebug()
    {
        $this->service_url = 'https://apitest.axxess.co.za/';
        $this->debug = 'output';

        return $this;
    }

    /// IMPORT ALL ACTIVE ACCOUNTS
    public function import_lte()
    {
        $clients = $this->getAllClients()->arrClients;

        foreach ($clients as $client) {
            $services = $this->getServicesByClient($client->guidClientId);
        }
    }

    public function import()
    {
        return false;
        \DB::table('isp_data_fibre')->update(['line_speed' => null]);
        $clients = $this->getAllClients()->arrClients;
        foreach ($clients as $client) {
            $services = $this->getServicesByClient($client->guidClientId);

            if (! empty($services->arrServices) && is_array($services->arrServices) && count($services->arrServices) > 0) {
                $combo_services = [];
                $combo_service_details = [];

                foreach ($services->arrServices as $service) {
                    // get combo service and product ids
                    if (empty($service->strDateEnd) && $service->intSuspendReasonId == null && $service->guidLinkedServiceId == null) {
                        $combo_services[] = $service;
                    }
                }

                if (count($combo_services) > 0) {
                    // get the combo service details
                    foreach ($combo_services as $combo_service) {
                        $description_arr = explode('/', $combo_service->strDescription);
                        $speed_arr = explode(' ', $description_arr[0]);
                        $speed = end($speed_arr);
                        $speed .= 'Mbps';
                        $combo_service->speed = $speed;
                        foreach ($services->arrServices as $service) {
                            if (str_contains($service->strDescription, '@ct.co.za') && $service->guidLinkedServiceId == $combo_service->guidServiceId) {
                                $combo_service->username = $service->strDescription;
                            }
                        }
                        $combo_service_details[] = $combo_service;
                    }

                    foreach ($combo_service_details as $combo_service_detail) {
                        $account = \DB::table('crm_accounts')->where('guidClientId', $client->guidClientId)->get()->first();
                        if (! $account || $account->status == 'Deleted') {
                            continue 2;
                        }

                        $fibre_account = \DB::table('isp_data_fibre')->where('guidClientId', $client->guidClientId)->where('username', $combo_service_detail->username)->get()->first();
                        if (! $fibre_account) {
                            continue 2;
                        }

                        $fibre_subscription = \DB::table('sub_services')->where('detail', $combo_service_detail->username)->get()->first();
                        if (! $fibre_subscription || $fibre_subscription->status == 'Deleted') {
                            continue 2;
                        }
                        \DB::table('isp_data_fibre')
                            ->where('guidClientId', $client->guidClientId)
                            ->where('username', $combo_service_detail->username)
                            ->update(['line_speed' => $combo_service_detail->speed, 'guidServiceId' => $combo_service_detail->guidServiceId, 'guidProductId' => $combo_service_detail->guidProductId]);
                    }
                }
            }
        }
    }

    /// CLIENTS
    public function getProvinces()
    {
        return $this->curl('getProvinces');
    }

    public function getAllClients()
    {
        return $this->curl('getAllClients');
    }

    public function getClientById($guidClientId)
    {
        $args['guidClientId'] = $guidClientId;

        return $this->curl('getClientById', $args);
    }

    public function createClient($data, $input = false)
    {
        $args = $this->setClientData($data, $input);

        /*
        returns
        guidClientId String(36) The id that is used to retrieve a client or retrieve services of a client
        strUsername String (50) The client code
        strPassword String (50) The password
        */
        return $this->curl('createClient', $args, 'put');
    }

    public function updateClient($data)
    {
        $args = $this->setClientData($data);

        /*
        returns
        intReturnCode
        */
        return $this->curl('updateClient', $args, 'get');
    }

    /// SERVICES
    public function getServiceDetails($guidClientId, $guidServiceId, $guidNetworkProviderId, $reset_password = false)
    {
        $data = [
            'guidClientId' => $guidClientId,
            'guidServiceId' => $guidServiceId,
            'guidNetworkProviderId' => $guidNetworkProviderId,
        ];

        $services = $this->getServicesByClient($guidClientId);

        if ($services->intCode != 200 || empty($services->arrServices) || count($services->arrServices) == 0) {
            return 'Failed to set fibre account - service details.';
        }
        foreach ($services->arrServices as $service) {
            if ($service->guidLinkedServiceId == $guidServiceId && str_contains($service->strDescription, '@ct.co.za')) {
                // data service
                $data['data_guidServiceId'] = $service->guidServiceId;
                $data['data_guidProductId'] = $service->guidProductId;
                if ($reset_password) {
                    $data['username'] = $service->strDescription;
                    $password_result = $this->funcPasswordReset($service->guidServiceId);
                    $data['fibre_password'] = $password_result->strPassword;
                }
            }
            if ($service->guidLinkedServiceId == $guidServiceId && ! str_contains($service->strDescription, '@ct.co.za')) {
                // line service
                $data['line_guidServiceId'] = $service->guidServiceId;
                $data['line_guidProductId'] = $service->guidProductId;
            }

            if ($guidServiceId == $service->guidServiceId) {
                $data['guidProductId'] = $service->guidProductId;
                $description_arr = explode('/', $service->strDescription);
                $speed_arr = explode(' ', $description_arr[0]);
                $data['line_speed'] = end($speed_arr).'Mbps';
            }
        }

        if ($reset_password) {
            if (empty($data['username']) || empty($data['fibre_password'])) {
                return 'Failed to set fibre account - password.';
            }
        }

        return $data;
    }

    public function getProducts()
    {
        return $this->curl('getProducts');
    }

    public function getServicesByClient($guidClientId)
    {
        $args['guidClientId'] = $guidClientId;

        return $this->curl('getServicesByClient', $args);
    }

    public function getServiceById($guidServiceId)
    {
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('getServiceById', $args);
    }

    public function getServiceSessionDetailsById($guidServiceId)
    {
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('getServiceSessionDetailsById', $args);
    }

    public function getPreviousMonthUsageById($guidServiceId)
    {
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('getPreviousMonthUsageById', $args);
    }

    public function getRadiusServiceBandwidth($guidServiceId)
    {
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('getRadiusServiceBandwidth', $args);
    }

    public function getServiceUsageDetailsById($guidServiceId)
    {
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('getServiceUsageDetailsById', $args);
    }

    /// CREATE SERVICE
    public function createService($guidClientId, $guidProductId)
    {
        $args['guidClientId'] = $guidClientId;
        $args['guidProductId'] = $guidProductId;

        return $this->curl('createService', $args, 'put');
    }

    public function getServiceChangeHistory($guidServiceId, $strDate)
    {
        $args['guidServiceId'] = $guidServiceId;
        $args['strDate'] = $strDate;

        return $this->curl('getServiceChangeHistory', $args);
    }

    /// FIBRE
    public function getAddressTypes()
    {
        return $this->curl('getAddressTypes');
    }

    public function getNetworkProviders()
    {
        return $this->curl('getNetworkProviders');
    }

    public function getNetworkProviderProducts($guidNetworkProviderId)
    {
        $args['guidNetworkProviderId'] = $guidNetworkProviderId;

        return $this->curl('getNetworkProviderProducts', $args);
    }

    public function checkFibreAvailability($strLatitude, $strLongitude, $strAddress)
    {
        $args['strLatitude'] = $strLatitude;
        $args['strLongitude'] = $strLongitude;
        $args['strAddress'] = $strAddress;

        return $this->curl('checkFibreAvailability', $args);
    }

    public function checkTelkomLteAvailability($strLatitude, $strLongitude, $strAddress)
    {
        //$args['strSessionId'] = $this->strSessionId;
        $args['strLatitude'] = $strLatitude;
        $args['strLongitude'] = $strLongitude;
        $args['strAddress'] = $strAddress;

        return $this->curl('checkTelkomLteAvailability', $args, 'post');
    }

    public function checkMtnFixedLteAvailability($strLatitude, $strLongitude, $strAddress, $strBBox, $strWidth, $strHeight, $strICoOrdinate, $strJCoOrdinate)
    {
        //$args['strSessionId'] = $this->strSessionId;
        $args['strLatitude'] = $strLatitude;
        $args['strLongitude'] = $strLongitude;
        $args['strAddress'] = $strAddress;
        $args['strBBox'] = $strBBox;
        $args['strWidth'] = $strWidth;
        $args['strHeight'] = $strHeight;
        $args['strICoOrdinate'] = $strICoOrdinate;
        $args['strJCoOrdinate'] = $strJCoOrdinate;

        /*
        strBBox String Retrieved from map render
        strWidth String Retrieved from map render
        strHeight String Retrieved from map render
        strICoOrdinate String Retrieved from map render
        strJCoOrdinate String Retrieved from map render
        */
        return $this->curl('checkMtnFixedLteAvailability', $args, 'post');
    }

    public function createFibreComboService($guidClientId, $guidProductId, $guidNetworkProviderId, $data)
    {
        $args = $data;
        $args['guidClientId'] = $guidClientId;
        $args['guidProductId'] = $guidProductId;
        $args['guidNetworkProviderId'] = $guidNetworkProviderId;

        return $this->curl('createFibreComboService', $args);
    }

    public function createFibreComboPreOrder($guidClientId, $guidProductId, $guidNetworkProviderId, $data)
    {
        $args = $data;
        $args['guidClientId'] = $guidClientId;
        $args['guidProductId'] = $guidProductId;
        $args['guidNetworkProviderId'] = $guidNetworkProviderId;

        return $this->curl('createFibreComboPreOrder', $args);
    }

    /// FUNCTIONS
    public function funcServiceChanges($guidServiceId, $guidProductId, $strDateStart = false, $intQuantity = 1)
    {
        if (! $strDateStart) {
            $strDateStart = date('Y-m-d');
        } else {
            $strDateStart = date('Y-m-d', strtotime($strDateStart));
        }

        $args['guidServiceId'] = $guidServiceId;
        $args['guidProductId'] = $guidProductId;
        $args['strDateStart'] = $strDateStart;
        $args['intQuantity'] = $intQuantity;

        return $this->curl('funcServiceChanges', $args, 'put');
    }

    public function funcSuspend($guidServiceId)
    {
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('funcSuspend', $args);
    }

    public function funcLiftSuspend($guidServiceId)
    {
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('funcLiftSuspend', $args);
    }

    public function funcExpireService($guidServiceId, $strDate)
    {
        $args['guidServiceId'] = $guidServiceId;
        $args['strDate'] = $strDate;

        return $this->curl('funcExpireService', $args);
    }

    public function funcPasswordReset($guidServiceId, $password = null)
    {
        $args['guidServiceId'] = $guidServiceId;
        if (! $password) {
            $args['strPassword'] = $password;
        }

        return $this->curl('funcPasswordReset', $args);
    }

    /// HELPERS
    public function setAxxessProviderProducts()
    {
        $providers = $this->getNetworkProviders()->arrNetworkProviders;
        $guidProductIds = [];
        foreach ($providers as $provider) {
            $products = $this->getNetworkProviderProducts($provider->guidNetworkProviderId)->arrNetworkProviderProducts;

            if (! empty($products) && count($products) > 0) {
                foreach ($products as $product) {
                    $exists = \DB::table($this->providers_table)->where('guidProductId', $product->guidProductId)->count();
                    if (! $exists) {
                        $data = [
                            'guidNetworkProviderId' => $provider->guidNetworkProviderId,
                            'provider' => $provider->strName,
                            'guidProductId' => $product->guidProductId,
                            'product' => $product->strName,
                            'status' => 'Enabled',
                        ];
                        \DB::table($this->providers_table)->insert($data);
                    } else {
                        $data = [
                            'guidNetworkProviderId' => $provider->guidNetworkProviderId,
                            'provider' => $provider->strName,
                            'guidProductId' => $product->guidProductId,
                            'product' => $product->strName,
                        ];
                        \DB::table($this->providers_table)->where('guidProductId', $product->guidProductId)->update($data);
                    }
                    $guidProductIds[] = $product->guidProductId;
                }
            }
        }
        \DB::table($this->providers_table)->whereNotIn('guidProductId', $guidProductIds)->update(['status' => 'Deleted']);
    }

    public function getProductFromName($product_name)
    {
        $product_code = 'fibre_'.str_replace('/', '_', preg_replace("/[^0-9\/]/", '', $product_name));

        return \DB::table('crm_products')->where('code', $product_code)->get()->first();
    }

    public function getProductIdFromName($product_name)
    {
        $product_code = 'fibre_'.str_replace('/', '_', preg_replace("/[^0-9\/]/", '', $product_name));

        return \DB::table('crm_products')->where('code', $product_code)->pluck('id')->first();
    }

    public function deleteComboService($guidClientId, $guidServiceId, $date = false)
    {
        if (! $date) {
            $date = date('Y-m-t');
        }

        $services = $this->getServicesByClient($guidClientId)->arrServices;
        $service_ids = [];
        $guidLinkedServiceId = false;
        foreach ($services as $service) {
            if ($service->guidServiceId == $guidServiceId) {
                if (! empty($service->guidLinkedServiceId)) {
                    $guidLinkedServiceId = $service->guidLinkedServiceId;
                    $service_ids[] = $service->guidServiceId;
                }
            }
        }

        foreach ($services as $service) {
            if ($service->guidServiceId == $guidLinkedServiceId || $guidLinkedServiceId == $service->guidLinkedServiceId) {
                $service_ids[] = $service->guidServiceId;
            }
        }
        $service_ids = collect($service_ids)->unique()->toArray();
        $services_cancelled = true;
        $api_results = [];
        foreach ($service_ids as $service_id) {
            $result = $this->funcExpireService($service_id, $date);
            $api_results[$service_id] = $result;
            if (empty($result) || $result['intReturnCode'] != 200) {
                $services_cancelled = false;
            }
        }

        return ['services_cancelled' => $services_cancelled, 'api_results' => $api_results];
    }

    private function setClientData($data, $input = false)
    {
        $args = (object) [];
        $data = (object) $data;
        if ($data->partner_id != 1) {
            $data->contact = $data->contact.' (reseller user)';
        }

        $args->strName = $data->company;
        $args->strFirstName = $data->company;
        $args->strLastName = $data->contact;
        $args->strEmail = $data->email;
        $args->strCell = $data->mobile;
        $args->strAddress = $data->address;
        if (! empty($data->guidClientId)) {
            $args->guidClientId = $data->guidClientId;
        }
        if (! empty($input['strSuburb'])) {
            $args->strSuburb = $input['strSuburb'];
        }
        if (! empty($input['strCity'])) {
            $args->strCity = $input['strCity'];
        }
        if (! empty($input['intPostalCode'])) {
            $args->intPostalCode = $input['intPostalCode'];
        }
        /*
        strSessionId String(36) (*Required) Session identifier
        strName String(50) (*Required) Name First name of the client
        strLastName String(50) (*Required) Last Name Last name of the client
        strCell String(10) Cellphone number 0812244522
        strIdNumber String(13) Identification number
        strCompanyName String(50) Company of the client
        strAddress String(50) Address of the client
        strSuburb String(50) Suburb of the client
        strCity String(50) City of the client
        strEmail String(50) Email of the client
        strHomeTel String(10) Home telephone number of the client 0413968000
        strWorkTel String(10) Work telephone number of the client 0413968000
        intProvinceId Integer The id linked to the province.
        intPostalCode Integer Postal Code of the client
        */
        $args = (array) $args;

        return $args;
    }

    // TELKOM START
    public function getTelkomLteHardware()
    {
        return $this->curl('getTelkomLteHardwareProductOptions');
    }

    public function getTelkomLteProducts($guidProductId = '46e3f5fa-e425-11e9-93c7-0050568d6656')
    {
        $args['guidProductId'] = $guidProductId;

        return $this->curl('getTelkomLteProductsForPurchase', $args);
    }

    public function getTelkomLteSims()
    {
        return $this->curl('getTelkomLteAvailableSims');
    }

    public function getTelkomLteProductsForPurchase()
    {
        return $this->curl('getTelkomLteProductsForPurchase');
    }

    public function getTelkomLteTopupProducts()
    {
        return $this->curl('getTelkomLteTopupProducts');
    }

    public function purchaseTelkomLteTopup($guidServiceId, $guidProductId)
    {
        $args['guidProductId'] = $guidProductId;
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('purchaseTelkomLteTopup', $args, 'post');
    }

    public function getTelkomLteUsage($guidServiceId)
    {
        $args['strSessionId'] = $this->strSessionId;
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('getTelkomLteBandwidth', $args);
    }

    public function purchaseTelkomLteService($guidClientId, $guidProductId, $guidServiceId)
    {
        //$args['strSessionId'] = $this->strSessionId;
        $args['guidClientId'] = $guidClientId;
        $args['guidProductId'] = $guidProductId;

        $args['guidServiceId'] = $guidServiceId;

        $args['intSimOnly'] = 1;

        return $this->curl('purchaseTelkomLteService', $args, 'post');
    }

    // TELKOM END

    // MtnFixed START
    public function getMtnFixedLteHardware()
    {
        return $this->curl('getMtnFixedLtePurchaseOptions');
    }

    public function getMtnFixedLteProducts($guidProductId)
    {
        $args['guidProductId'] = $guidProductId;

        return $this->curl('getMtnFixedLtePurchaseProducts', $args);
    }

    public function getMtnFixedLtePurchaseOptions()
    {
        return $this->curl('getMtnFixedLtePurchaseOptions');
    }

    public function getMtnFixedLteSims()
    {
        return $this->curl('getMtnFixedLteAvailableSims');
    }

    public function getMtnFixedLteTopupProducts()
    {
        return $this->curl('getMtnFixedLteTopupProducts');
    }

    public function purchaseMtnFixedLteTopup($guidServiceId, $guidProductId)
    {
        $args['guidProductId'] = $guidProductId;
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('purchaseMtnFixedLteTopup', $args, 'post');
    }

    public function getMtnFixedLteUsage($guidServiceId)
    {
        $args['strSessionId'] = $this->strSessionId;
        $args['guidServiceId'] = $guidServiceId;

        return $this->curl('getMtnFixedLteBandwidth', $args);
    }

    public function purchaseMtnFixedLteService($guidClientId, $guidProductId, $guidServiceId)
    {
        //$args['strSessionId'] = $this->strSessionId;
        $args['guidClientId'] = $guidClientId;
        $args['guidProductId'] = $guidProductId;

        $args['guidServiceId'] = $guidServiceId;

        $args['intSimOnly'] = 1;

        return $this->curl('purchaseMtnFixedLteService', $args, 'post');
    }

    // MtnFixed END

    // MTNFIXED 5G START

    public function checkMtn5GAvailability($strLatitude, $strLongitude, $strAddress, $strBBox, $strWidth, $strHeight, $strICoOrdinate, $strJCoOrdinate)
    {
        //$args['strSessionId'] = $this->strSessionId;
        $args['strLatitude'] = $strLatitude;
        $args['strLongitude'] = $strLongitude;
        $args['strAddress'] = $strAddress;
        $args['strBBox'] = $strBBox;
        $args['strWidth'] = $strWidth;
        $args['strHeight'] = $strHeight;
        $args['strICoOrdinate'] = $strICoOrdinate;
        $args['strJCoOrdinate'] = $strJCoOrdinate;

        /*
        strBBox String Retrieved from map render
        strWidth String Retrieved from map render
        strHeight String Retrieved from map render
        strICoOrdinate String Retrieved from map render
        strJCoOrdinate String Retrieved from map render
        */
        return $this->curl('checkMtn5GAvailability', $args, 'post');
    }

    public function getMtn5GPurchaseProducts()
    {
        return $this->curl('getMtn5GPurchaseProducts');
    }

    public function getMtn5GAvailableSims()
    {
        return $this->curl('getMtn5GAvailableSims');
    }

    public function purchaseMtn5GService($guidClientId, $guidProductId, $guidServiceId, $strLatLon, $strAddress, $strSuburb, $strCity, $intPostalCode, $strProvince)
    {
        /*
            strSessionId String Session identifier
            guidClientId String Reseller client identifier
            guidProductId String Product identifier
            guidServiceId String Sim service identifier
            strLatLon String Lat and lon of usage -00.00000, 00.00000
            strAddress String Address of usage
            strSuburb String Suburb of usage
            strCity String City of usage
            intPostalCode Int Postal code of usage
            strProvince String Province of usage
        */

        $args['guidClientId'] = $guidClientId;
        $args['guidProductId'] = $guidProductId;
        $args['guidServiceId'] = $guidServiceId;
        $args['strLatLon'] = $strLatLon;
        $args['strAddress'] = $strAddress;
        $args['strSuburb'] = $strSuburb;
        $args['strCity'] = $strCity;
        $args['intPostalCode'] = $intPostalCode;
        $args['strProvince'] = $strProvince;

        return $this->curl('purchaseMtn5GService', $args, 'post');
    }

    public function getMtn5GServiceChangeProducts()
    {
        return $this->curl('getMtn5GServiceChangeProducts');
    }

    public function purchaseMtn5GServiceChange()
    {
        return $this->curl('purchaseMtn5GServiceChange');
    }

    public function getMtn5GBandwidth()
    {
        return $this->curl('getMtn5GBandwidth');
    }
    // MTNFIXED 5G END

    public function lte_products_import()
    {
        $guidProductIds = [];
        $telkom_products = $this->getTelkomLteHardware();

        if ($telkom_products->intCode == 200 && $telkom_products->strStatus == 'OK') {
            foreach ($telkom_products->arrTelkomLteHardwareProductOptions as $telkom_products_product) {
                $lte = $this->getTelkomLteProducts($telkom_products_product->guidProductId);

                if ($lte->intCode == 200 && $lte->strStatus == 'OK') {
                    foreach ($lte->arrTelkomLteSmartComboProducts as $lte_product) {
                        $data = [
                            'hardware_name' => $telkom_products_product->strName,
                            'hardware_guidProductId' => $telkom_products_product->guidProductId,
                            'name' => $lte_product->strName,
                            'guidProductId' => $lte_product->guidProductId,
                            'network' => 'Telkom',
                        ];
                        $guidProductIds[] = $lte_product->guidProductId;
                        \DB::connection('default')->table('isp_data_lte_axxess_products')->updateOrInsert(['guidProductId' => $lte_product->guidProductId], $data);
                    }
                }
            }
        }

        $mtn_products = $this->getMtnFixedLteHardware();

        if ($mtn_products->intCode == 200 && $mtn_products->strStatus == 'OK') {
            foreach ($mtn_products->arrMtnFixedLtePurchaseOptions as $mtn_products_product) {
                $lte = $this->getMtnFixedLteProducts($mtn_products_product->guidProductId);

                if ($lte->intCode == 200 && $lte->strStatus == 'OK') {
                    foreach ($lte->arrMtnFixedLtePurchaseProducts as $lte_product) {
                        $data = [
                            'hardware_name' => $mtn_products_product->strName,
                            'hardware_guidProductId' => $mtn_products_product->guidProductId,
                            'name' => $lte_product->strName,
                            'guidProductId' => $lte_product->guidProductId,
                            'network' => 'MTN',
                        ];
                        $guidProductIds[] = $lte_product->guidProductId;
                        \DB::connection('default')->table('isp_data_lte_axxess_products')->updateOrInsert(['guidProductId' => $lte_product->guidProductId], $data);
                    }
                }
            }
        }

        $mtn_5g_products = $this->getMtn5GPurchaseProducts();

        if ($mtn_5g_products->intCode == 200 && $mtn_5g_products->strStatus == 'OK') {
            foreach ($mtn_5g_products->arrMtn5GPurchaseProducts as $mtn_5g_products_product) {
                $data = [
                    'hardware_name' => $mtn_5g_products_product->strName,
                    'hardware_guidProductId' => $mtn_5g_products_product->guidProductId,
                    'name' => $mtn_5g_products_product->strName,
                    'guidProductId' => $mtn_5g_products_product->guidProductId,
                    'network' => 'MTN',
                    'lte_5g' => 1,
                ];
                $guidProductIds[] = $mtn_5g_products_product->guidProductId;
                \DB::connection('default')->table('isp_data_lte_axxess_products')->updateOrInsert(['guidProductId' => $mtn_5g_products_product->guidProductId], $data);
            }

        }

        \DB::connection('default')->table('isp_data_lte_axxess_products')->where('guidProductId', '>', '')->whereNotIn('guidProductId', $guidProductIds)->delete();

    }

    /// CURL
    private function login()
    {
        $args['strUserName'] = $this->username;
        $args['strPassword'] = $this->password;

        $response = $this->curl('getSession', $args);

        if ($response->intCode != 200) {
            return false;
        }

        $this->strSessionId = $response->strSessionId;
    }

    private function checkSession()
    {
        if (! empty($this->strSessionId)) {
            $response = $this->curl('checkSession', $args);
            if ($this->strSessionId != $response->strSessionId) {
                $this->login();
            }
        } else {
            $this->login();
        }
    }

    protected function setCurlParams($endpoint, $args, $method)
    {
        if ($endpoint != 'getSession' && $endpoint != 'checkSession') {
            $this->checkSession();
        }

        $endpoint_args = $args;
        $args = [];
        if ($endpoint != 'getSession') {
            $args['strSessionId'] = $this->strSessionId;
            $session['strSessionId'] = $this->strSessionId;
        }
        foreach ($endpoint_args as $k => $v) {
            $args[$k] = $v;
        }

        $endpoint_url = $this->service_url.'calls/rsapi/'.$endpoint.'.json';

        return ['endpoint_url' => $endpoint_url, 'args' => $args];
    }

    protected function setCurlAuth($api_request)
    {
        $api_request->authenticateWith($this->authuser, $this->authpass);

        return $api_request;
    }

    protected function curl($endpoint, $args = [], $method = 'get')
    {
        try {
            $curl_params = $this->setCurlParams($endpoint, $args, $method);
            $url = $curl_params['endpoint_url'];
            $args = $curl_params['args'];

            if ($this->debug == 'output') {
            }

            if ($this->debug == 'log') {
                exception_log($url);
                exception_log($method);
                exception_log($args);
            }

            if ($method == 'post') {
                // $url = $this->buildUrl($url, $args);
                $api_request = ApiRequest::post($url);
                $api_request = $this->setCurlAuth($api_request);
                //$api_request->sendsJson();
                $api_request->method(\Httpful\Http::POST);
                // if ($endpoint == 'purchaseTelkomLteService' || $endpoint == 'purchaseMtnFixedLteService') {
                $api_request->sendsType(\Httpful\Mime::FORM);
                // }

                $response = $api_request->body($args)
                    ->send();
            }

            if ($method == 'put') {
                $url = $this->buildUrl($url, $args);
                $api_request = ApiRequest::put($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->body($args)
                    ->withoutStrictSsl()
                    ->send();
            }

            if ($method == 'get') {
                $url = $this->buildUrl($url, $args);

                $api_request = ApiRequest::get($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->send();
            }

            if ($method == 'delete') {
                $api_request = ApiRequest::delete($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->send();
            }

            if ($this->debug == 'output') {
            }

            if ($this->debug == 'log') {
                exception_log($response);
            }

            if (! empty($response->body)) {
                return $response->body;
            } else {
                return (object) ['intCode' => $response->code];
            }

            return $response;
        } catch (\Throwable $ex) {
            exception_log($ex);
            if ($this->debug == 'output') {
            }

            if ($this->debug == 'log') {
                exception_log($ex->getMessage());
                exception_log($ex->getTraceAsString());
            }
        }
    }
}
