<?php

function provision_virtual_server($provision, $input, $customer, $product)
{
    if (empty($input['ip_addr'])) {
        return 'ip addr is required';
    }
    if (empty($input['server_username'])) {
        return 'Server Username is required';
    }
    if (empty($input['server_pass'])) {
        return 'Server Password is required';
    }

    if (empty($input['admin_url'])) {
        return 'admin_url is required';
    }
    if (empty($input['admin_username'])) {
        return 'username is required';
    }
    if (empty($input['admin_password'])) {
        return 'password is required';
    }

    if (empty($input['server_os'])) {
        return 'Server OS is required';
    }
    $table_data = $input;
    unset($table_data['date_activated']);
    $table_data['account_id'] = $provision->account_id;
    $table_data['product_id'] = $provision->product_id;

    $sub_data = ['detail' => $input['ip_addr'], 'table_data' => ['isp_virtual_servers' => $table_data]];

    if (! empty($input['date_activated'])) {
        $info['date_activated'] = $input['date_activated'];
        $info['renews_at'] = date('Y-m-d H:i:s', strtotime($info['date_activated'].' +1 month'));
        $sub_data['info'] = $info;
    }

    return $sub_data;
}

function beforesave_activation_type_set_name($request)
{
    request()->merge(['name' => str_replace([' ', '-'], '_', strtolower($request->name))]);
}

function beforesave_activation_check_step($request)
{
    if ($request->id) {
        $step_used = \DB::table('sub_activation_plans')->where('id', '!=', $request->id)->where('activation_type_id', $request->activation_type_id)->where('status', '!=', 'Deleted')->where('step', $request->step)->count();
    } else {
        $step_used = \DB::table('sub_activation_plans')->where('activation_type_id', $request->activation_type_id)->where('status', '!=', 'Deleted')->where('step', $request->step)->count();
    }
    if ($step_used) {
        return 'Activation step number already in use.';
    }
}

function provision_get_table_data($id, $service_table)
{
    $arr = [];
    $table_data = \DB::table('sub_activation_steps')->where('service_table', $service_table)->where('provision_id', $id)->whereNotNull('table_data')->get();
    foreach ($table_data as $td) {
        $td_arr = json_decode($td->table_data, true);
        $arr = array_merge_recursive($arr, $td_arr);
    }

    return $arr;
}

function beforesave_provision_function_check($request)
{
    if (empty($request->name)) {
        return 'Name required';
    }
    if (! str_contains($request->name, '_copy')) {
        if ($request->type == 'Function') {
            $function_name = function_format('provision_'.$request->name);
            if (! function_exists($function_name) || ! function_exists($function_name.'_form')) {
                return 'Function definition Invalid.';
            }
        }
    }
}

function provision_number_porting($provision, $input, $customer, $product)
{
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    }
    $customer = dbgetaccount($customer->id);
    $phone_number = (! empty($input['phone_number'])) ? $input['phone_number'] : '';
    $number_routing = (! empty($input['number_routing'])) ? $input['number_routing'] : '';
    if (empty($phone_number)) {
        return 'Empty phone number';
    }

    if (! str_starts_with($phone_number, 27)) {
        return 'Number has to start with 27';
    }

    try {
        $number = phone($phone_number, ['ZA']);

        $number = $number->formatForMobileDialingInCountry('ZA');
        $number = '27'.substr($number, 1);
        if ($phone_number != $number) {
            return 'Invalid phone number';
        }
    } catch (\Throwable $ex) {
        exception_log($ex);

        return 'Invalid phone number';
    }

    $exists = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $phone_number)->count();
    if ($exists) {
        return 'The requested number already exists.';
    }

    if (substr($phone_number, 0, 4) == '2787' || substr($phone_number, 0, 3) == '087') {
        $subscription_product = 127; // 087
    } else {
        if (str_starts_with($phone_number, '2712786')) { // 012786
            $subscription_product = 176;
        } elseif (str_starts_with($phone_number, '2710786')) { // 010786
            $subscription_product = 176;
        } else { // geo
            $subscription_product = 128;
        }
    }

    $prefix = substr($phone_number, 0, 4);
    $prefix = str_replace('27', 0, $prefix);

    $routing_type = null;
    $customer = dbgetaccount($customer->id);
    if (! empty($customer->domain_uuid) && ! empty($number_routing)) {
        $routing_type = get_routing_type($customer->domain_uuid, $number_routing);
    }
    $gateway_uuid = \DB::connection('pbx')->table('v_gateways')->where('number_porting', 1)->pluck('gateway_uuid')->first();
    $table_data = ['p_phone_numbers' => [
        'domain_uuid' => $customer->domain_uuid,
        'number_uuid' => pbx_uuid('p_phone_numbers', 'number_uuid'),
        'number' => $phone_number,
        'prefix' => $prefix,
        'number_routing' => $number_routing,
        'routing_type' => $routing_type,
        'gateway_uuid' => $gateway_uuid,
    ],
    ];
    update_caller_id($customer->domain_uuid, $provision->invoice_id);

    $info_data = ['invoice_id' => $provision->invoice_id, 'provision_type' => 'phone_number', 'product_id' => $subscription_product];
    if (! empty(session('user_id'))) {
        $info_data['created_by'] = session('user_id');
    }

    return ['detail' => $phone_number, 'info' => $info_data, 'table_data' => $table_data];
}

function provision_phone_number($provision, $input, $customer, $product)
{
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    }

    $customer = dbgetaccount($customer->id);
    $phone_number = (! empty($input['phone_number'])) ? $input['phone_number'] : '';
    $number_routing = (! empty($input['number_routing'])) ? $input['number_routing'] : '';

    if (empty($phone_number)) {
        return 'Select a phone number';
    }
    if (empty($number_routing)) {
        return 'Number routing required';
    }

    pbx_add_number($customer->pabx_domain, $phone_number, $number_routing);

    update_caller_id($customer->domain_uuid, $provision->invoice_id);

    return ['detail' => $phone_number];
}

function provision_pbx_domain($provision, $input, $customer, $product)
{
    /// CREATE VOIP DOMAIN
    try {
        if (! empty($customer->pabx_domain)) {
            return 'PBX already exists';
        }

        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            return 'DNS create failed';
        }

        pbx_add_domain($pbx_domain, $customer->id);
        $customer = dbgetaccount($customer->id);

        return ['detail' => $pbx_domain];
    } catch (\Throwable $ex) {
        exception_log($ex);
        exception_email($ex, 'pbx create error');

        return 'Could not create cname and pabx domain';
    }
}

function provision_pbx_extension_default($account, $invoice_id = 0)
{
    \DB::connection('default')->table('crm_accounts')->where('id', $account->id)->where('type', 'lead')->update(['type' => 'customer']);
    $extension_subscription = \DB::table('sub_services')->whereIn('provision_type', ['pbx_extension', 'sip_trunk'])->where('status', '!=', 'Deleted')->where('account_id', $account->id)->count();
    //check if invoice includes extensions
    $invoice_includes_extensions = false;
    if (! $extension_subscription && $invoice_id > 0) {
        $pbx_extension_product_ids = get_activation_type_product_ids('pbx_extension');
        $lines = \DB::table('sub_activations')->where('invoice_id', $invoice_id)->get();
        foreach ($lines as $l) {
            if (in_array($l->product_id, $pbx_extension_product_ids)) {
                $invoice_includes_extensions = true;
            }
        }
    }

    if (! $extension_subscription && ! $invoice_includes_extensions) {
        $sub = new ErpSubs;
        //CREATE DEFAULT EXTENSION
        $extension_info = pbx_add_extension($account);

        if (empty($extension_info['extension'])) {
            return false;
        }

        update_caller_id($account->domain_uuid);

        $ext = \DB::connection('pbx')->table('v_extensions')->where('extension', $extension_info['extension'])->where('domain_uuid', $account->domain_uuid)->get()->first();
        aftersave_extensions($ext);
        $product_id = 674;
        $sub->createSubscription($account->id, $product_id, $extension_info['extension'], $invoice_id);
        set_mobile_app_number_extension($account->id, $account->phone, $extension_info['extension']);

        return $extension_info['extension'];
    }
    schedule_update_extension_count();
}

function provision_pbx_extension($provision, $input, $customer, $product)
{
    if (! empty($input['mobile_app_number'])) {
        $check = check_mobile_app_number_extension($input['mobile_app_number']);
        if (! empty($check)) {
            return $check;
        }
    }
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            return 'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
    }

    $customer = dbgetaccount($customer->id);

    /// PROVISION EXTENSION
    $extension_info = pbx_add_extension($customer);

    if (empty($extension_info) || empty($extension_info['extension'])) {
        return 'Could not provision extension';
    }
    update_caller_id($customer->domain_uuid, $provision->invoice_id);
    $ext = \DB::connection('pbx')->table('v_extensions')->where('extension', $extension_info['extension'])->where('domain_uuid', $customer->domain_uuid)->get()->first();
    if (! empty($input['mobile_app_number'])) {
        set_mobile_app_number_extension($customer->id, $input['mobile_app_number'], $extension_info['extension']);
    }

    aftersave_extensions($ext);
    schedule_update_extension_count();

    return ['detail' => $extension_info['extension']];
}

function provision_sip_trunk($provision, $input, $customer, $product)
{
    if (! empty($input['mobile_app_number'])) {
        $check = check_mobile_app_number_extension($input['mobile_app_number']);
        if (! empty($check)) {
            return $check;
        }
    }
    $pbx_domain = $customer->pabx_domain;

    if (empty($pbx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
    }

    $customer = dbgetaccount($customer->id);

    /// PROVISION EXTENSION
    $extension_info = pbx_add_extension($customer);

    if (empty($extension_info) || empty($extension_info['extension'])) {
        return 'Could not provision extension';
    }
    update_caller_id($customer->domain_uuid, $provision->invoice_id);
    $ext = \DB::connection('pbx')->table('v_extensions')->where('extension', $extension_info['extension'])->where('domain_uuid', $customer->domain_uuid)->get()->first();
    if (! empty($input['mobile_app_number'])) {
        set_mobile_app_number_extension($customer->id, $input['mobile_app_number'], $extension_info['extension']);
    }

    if (! empty($input['cidr'])) {
        pbx_add_cidr_extension($customer->id, $pbx_domain, $extension_info['extension'], $input['cidr']);
    }

    aftersave_extensions($ext);

    schedule_update_extension_count();

    return ['detail' => $extension_info['extension']];
}

function provision_pbx_extension_recording($provision, $input, $customer, $product)
{
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    }

    $customer = dbgetaccount($customer->id);
    if (empty($input['extension'])) {
        return 'Extension is required';
    }

    $customer = dbgetaccount($customer->id);
    pbx_enable_recording($customer->pabx_domain, $input['extension']);

    return ['detail' => $input['extension']];
}

function provision_airtime_contract($provision, $input, $customer, $product)
{
    if (empty(session('instance')) || empty(session('instance')->directory)) {
        return 'Session Expired';
    }

    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    }
    $customer = dbgetaccount($customer->id);

    if (empty($product->provision_package)) {
        return 'Invalid Package';
    }
    $pbx_connection = get_pbx_connection($customer->pabx_domain);
    $balance = \DB::connection('pbx')->table('v_domains')->where('account_id', $customer->id)->pluck('balance')->first();

    $package_amount = $provision->provision_amount;

    $total_days = intval(date('t'));

    $prorata_amount = ($package_amount / $total_days) * ($total_days - intval(date('d')));

    $balance += $prorata_amount;

    $airtime_history = [
        'created_at' => date('Y-m-d H:i:s'),
        'domain_uuid' => $customer->domain_uuid,
        'total' => $prorata_amount,
        'balance' => $balance,
        'type' => 'contract',
    ];

    \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
    \DB::connection('pbx')->table('v_domains')->where('account_id', $customer->id)->update(['balance' => $balance]);

    $usage_type = 'Rand';

    $current_usage = $balance;
    $usage_allocation = $package_amount;

    $subscription_id = \DB::table('sub_services')
        ->where('account_id', $customer->id)->where('product_id', $product->id)
        ->where('status', '!=', 'Deleted')->pluck('id')->first();

    if (! empty($subscription_id)) {
        $current_usage_allocation = \DB::table('sub_services')
            ->where('account_id', $customer->id)->where('product_id', $product->id)
            ->where('status', '!=', 'Deleted')->pluck('usage_allocation')->first();
        $usage_allocation = $current_usage_allocation + $package_amount;
    }

    $detail = intval($usage_allocation * 2).' minutes';
    $info = [
        'current_usage' => $current_usage,
        'usage_type' => $usage_type,
        'usage_allocation' => $usage_allocation,
        'detail' => $detail,
    ];

    return ['detail' => $detail, 'info' => $info];
}

function provision_unlimited_channel($provision, $input, $customer, $product)
{
    // return 'Unavailable';
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    }

    $customer = dbgetaccount($customer->id);

    $info = [
        'current_usage' => 1,
        'usage_type' => 'Channel',
        'usage_allocation' => 1,
    ];

    return ['detail' => 'channel_'.$provision->id, 'info' => $info];
}

function provision_airtime_unlimited($provision, $input, $customer, $product)
{
    // return 'Unavailable';
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    }

    $customer = dbgetaccount($customer->id);

    if (empty($product->provision_package)) {
        return 'Invalid Package';
    }

    $info = [
        'current_usage' => 1,
        'usage_type' => 'Channel',
        'usage_allocation' => 1,
    ];

    return ['detail' => $product->provision_package.'_'.$provision->id, 'info' => $info];
}

function provision_airtime_prepaid($provision, $input, $customer, $product)
{
    if (empty(session('instance')) || empty(session('instance')->directory)) {
        return 'Session Expired';
    }
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    } else {
        $extension_count = \DB::table('sub_services')
            ->where('account_id', $customer->id)->where('provision_type', 'pbx_extension')->where('status', '!=', 'Deleted')
            ->count();
        $sip_count = \DB::table('sub_services')
            ->where('account_id', $customer->id)->where('provision_type', 'sip_trunk')->where('status', '!=', 'Deleted')
            ->count();
        if (! $extension_count && ! $sip_count) {
            provision_pbx_extension_default($customer, $provision->invoice_id);
        }
    }
    $customer = dbgetaccount($customer->id);
    $pbx_connection = get_pbx_connection($customer->pabx_domain);
    $balance = \DB::connection('pbx')->table('v_domains')->where('account_id', $customer->id)->pluck('balance')->first();

    $airtime = $provision->provision_amount;

    $balance += $airtime;
    $usage_type = 'Rand';

    $airtime_history = [
        'created_at' => date('Y-m-d H:i:s'),
        'domain_uuid' => $customer->domain_uuid,
        'total' => $airtime,
        'balance' => $balance,
        'type' => 'prepaid',
    ];

    \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);

    \DB::connection('pbx')->table('v_domains')->where('account_id', $customer->id)->update(['balance' => $balance]);
    $info = [
        'current_usage' => $balance,
        'usage_type' => $usage_type,
        'usage_allocation' => 0,
    ];
    $customer = dbgetaccount($customer->id);

    set_subscription_topup_amounts();

    return ['detail' => $customer->pabx_domain, 'info' => $info];
}

function provision_bulk_sms($provision, $input, $customer, $product)
{
    //get sms balance
    $total_days = intval(date('t'));
    $package_amount = $provision->provision_amount;
    $prorata_amount = ($package_amount / $total_days) * ($total_days - intval(date('d')));

    $info = [
        'current_usage' => $prorata_amount,
        'usage_type' => 'SMS',
        'usage_allocation' => $package_amount,
    ];

    set_subscription_topup_amounts();

    \DB::table('isp_sms_message_queue')
        ->where('account_id', $customer->id)
        ->where('error_description', 'Customer out of credit')
        ->where('status', 'Error')
        ->update(['status' => 'Queued', 'error_description' => '']);

    return ['detail' => 'Bulk SMS '.$package_amount, 'info' => $info];
}

function provision_bulk_sms_prepaid($provision, $input, $customer, $product)
{
    //get sms balance
    $sms_sub = \DB::table('sub_services')
        ->where(['account_id' => $customer->id, 'product_id' => $product->id, 'status' => 'Enabled'])
        ->get()->first();

    $balance = 0;

    if (! empty($sms_sub)) {
        $balance = $sms_sub->current_usage;
    }

    $balance += $provision->provision_amount;

    $info = [
        'current_usage' => $balance,
        'usage_type' => 'SMS',
        'usage_allocation' => 0,
    ];

    set_subscription_topup_amounts();

    \DB::table('isp_sms_message_queue')
        ->where('account_id', $customer->id)
        ->where('error_description', 'Customer out of credit')
        ->where('status', 'Error')
        ->update(['status' => 'Queued', 'error_description' => '']);

    return ['detail' => 'Bulk SMS', 'info' => $info];
}

function provision_lte_sim_card($provision, $input, $customer, $product)
{
    if (empty($input['msisdn'])) {
        return 'MSISDN Number required';
    }
    $detail = $input['msisdn'];
    $package_amount = $product->provision_package;

    $info = [
        'current_usage' => $package_amount,
        'usage_type' => 'GB',
        'usage_allocation' => $package_amount,
    ];

    $table_data = [
        'account_id' => $customer->id,
        'product_id' => $product->id,
        'created_at' => date('Y-m-d'),
        'msisdn' => $detail,
        'status' => 'Enabled',
    ];

    return ['detail' => $detail, 'info' => $info, 'table_data' => ['isp_data_lte_vodacom_accounts' => $table_data]];
}

function provision_save_lte_number($provision, $input, $customer, $product)
{
    $table_data = [
        'phone_number' => $input['phone_number'],
    ];

    return ['ref' => $input['phone_number'], 'table_data' => ['isp_data_lte_vodacom_accounts' => $table_data]];
}

function provision_fibre_product($provision, $input, $customer, $product)
{
    if (empty($input['fibre_username'])) {
        return 'Username required.';
    }
    if (empty($input['fibre_password'])) {
        return 'Password required.';
    }
    $coverage_address = \DB::table('crm_documents')->where('id', $provision->invoice_id)->pluck('coverage_address')->first();
    // return $coverage_address;
    if (strtolower($input['address']) != strtolower($coverage_address)) {
        return 'Address does not match address used on Invoice';
    }

    $table_data = [
        'username' => $input['fibre_username'],
        'fibre_password' => $input['fibre_password'],
        'address' => $input['address'],
        'b_number' => $input['b_number'],
        'full_name' => $input['full_name'],
        'phone_number' => $input['phone_number'],
        'line_speed' => $input['line_speed'],
        'account_id' => $customer->id,
        'provider' => 'Session',
    ];

    return ['detail' => $table_data['username'], 'table_data' => ['isp_data_fibre' => $table_data]];
}

function provision_fibre($provision, $input, $customer, $product)
{
    if (empty($input['strLatLong']) || empty($input['strAddress'])) {
        return false;
    }
    if (empty($input['strSuburb'])) {
        return 'Suburb required.';
    }
    if (empty($input['strCity'])) {
        return 'Suburb required.';
    }
    if (empty($input['strCode'])) {
        return 'Postal code required.';
    }

    $latlong_arr = explode(',', $input['strLatLong']);

    $axxess = new Axxess;

    $available = $axxess->checkFibreAvailability($latlong_arr[0], $latlong_arr[1], $input['strAddress']);

    if ($available->intCode != 200 || count($available->arrAvailableProvidersGuids) == 0) {
        return 'A fibre provider not available for this location.';
    }

    $available_products = '';
    foreach ($available->arrAvailableProvidersGuids as $provider) {
        if ($provider->intPreOrder == 0 && ! empty($provider->guidNetworkProviderId)) {
            $guidProductId = \DB::table('isp_data_products')
                ->where('guidNetworkProviderId', $provider->guidNetworkProviderId)
                ->where('product_id', $provision->product_id)
                ->where('status', 'Enabled')
                ->pluck('guidProductId')->first();
            if (! empty($guidProductId)) {
                $guidNetworkProviderId = $provider->guidNetworkProviderId;
            }

            $available_products_arr = \DB::table('isp_data_products')
                ->where('guidNetworkProviderId', $provider->guidNetworkProviderId)
                ->where('product_id', '!=', 0)
                ->where('status', 'Enabled')
                ->get();
            foreach ($available_products_arr as $ap) {
                $available_products .= PHP_EOL.$ap->provider.': '.$ap->product.PHP_EOL;
            }
        }
    }

    if (empty($guidNetworkProviderId)) {
        return 'Fibre product is not available for this location.'.$available_products;
    }

    $guidClientId = \DB::table('crm_accounts')->where('id', $customer->id)->pluck('guidClientId')->first();
    if (empty($guidClientId)) {
        $client = $axxess->createClient($customer, $input);

        if (! empty($client) && ! empty($client->guidClientId)) {
            $guidClientId = $client->guidClientId;
        }
        if (empty($guidClientId)) {
            return 'Failed to create client account.';
        }

        \DB::table('crm_accounts')->where('id', $customer->id)->update(['guidClientId' => $guidClientId]);
    }

    $result = $axxess->createFibreComboService($guidClientId, $guidProductId, $guidNetworkProviderId, $input);

    if ($result->intCode != 200 || $result->strStatus != 'OK' || empty($result->arrServices) || empty($result->arrServices[0]) || empty($result->arrServices[0]->guidServiceId)) {
        debug_email('Error creating fibre service for '.$customer->company);
        if (! empty($result->strMessage) && ! empty($result->intCode)) {
            return 'Axxess error code:'.$result->intCode.': '.$result->strMessage;
        } else {
            return 'Error creating fibre service';
        }
    }

    $guidServiceId = $result->arrServices[0]->guidServiceId;

    $fibre_details = $axxess->getServiceDetails($guidClientId, $guidServiceId, $guidNetworkProviderId, true);

    if (empty($fibre_details) || ! is_array($fibre_details)) {
        return 'Failed to set fibre account.';
    }
    $table_data = $fibre_details;
    $table_data['account_id'] = $customer->id;
    $table_data['date_submitted'] = date('Y-m-d');
    $table_data['address'] = $input['strAddress'];
    $table_data['provider'] = 'Axxess';

    return ['detail' => $table_data['username'], 'table_data' => ['isp_data_fibre' => $table_data]];
}

function provision_fibre_addon($provision, $input, $customer, $product)
{
    if (empty($input['fibre_username'])) {
        return 'Fibre account required';
    }

    return ['detail' => $input['fibre_username'].' '.$product->provision_package];
}

function provision_sitebuilderaddon($provision, $input, $customer, $product)
{
    $domain_name = (! empty($input['domain_name'])) ? strtolower($input['domain_name']) : '';
    if (empty($domain_name)) {
        return 'Please select a domain';
    }

    $ix = new Interworx;
    $ix->setDomain($domain_name);
    $ix->installSitebuilder();

    $ftp = $ix->siteBuilderFTP();
    if (! $ftp) {
        return 'Interworx create error';
    }

    return ['detail' => $domain_name];
}
/*
function provision_sitebuilder($provision, $input, $customer, $product)
{
    $domain_name = (!empty($input['domain_name'])) ? strtolower($input['domain_name']) : '';
    $domain_name = str_replace([' ','www.','http://','https://'], '', $domain_name);
    $domain_action = (!empty($input['domain_action'])) ? ucfirst($input['domain_action']) : '';
    $domain_epp = (!empty($input['domain_epp'])) ? ucfirst($input['domain_epp']) : '';
    $email_address_1 = (!empty($input['email_address_1'])) ? strtolower($input['email_address_1']) : '';
    $email_address_2 = (!empty($input['email_address_2'])) ? strtolower($input['email_address_2']) : '';
    $email_address_3 = (!empty($input['email_address_3'])) ? strtolower($input['email_address_3']) : '';
    $email_password_1 = (!empty($input['email_password_1'])) ? strtolower($input['email_password_1']) : '';
    $email_password_2 = (!empty($input['email_password_2'])) ? strtolower($input['email_password_2']) : '';
    $email_password_3 = (!empty($input['email_password_3'])) ? strtolower($input['email_password_3']) : '';
    $ftp_account = (!empty($input['ftp_account'])) ? strtolower($input['ftp_account']) : '';
    $ftp_password = (!empty($input['ftp_password'])) ? strtolower($input['ftp_password']) : '';

    if (empty($domain_name) || empty($domain_action)) {
        return 'Fill required inputs';
    }

    $tld_from_input = get_tld($domain_name);

    if ($customer->id != 12) {
        $supported_tld = valid_tld($domain_name);
        if (!$supported_tld) {
            return 'Tld not supported';
        }
    }
    if ('Transfer' == $domain_action) {
        if (str_ends_with($domain_name, '.com') || str_ends_with($domain_name, '.net') || str_ends_with($domain_name, '.org')) {
            if (empty($domain_epp)) {
                return 'EPP key required';
            }
        }
    }


    if ('Register' == $domain_action) {
        $available = domain_available($domain_name);
        if ($available == "Premium") {
            return 'Premium domain names cannot be ordered.';
        }
        if ($available == "No") {
            return 'Domain name unavailable.';
        }
    } else {
        $available = domain_available($domain_name);
        if ($available == "Premium") {
            return 'Premium domain names cannot be ordered.';
        }
        if ($available == "Yes") {
            return 'Domain is not registered, so cannot be transferred.';
        }
    }

    $table_data = [
        'account_id' => $customer->id,
        'created_at' => date('Y-m-d'),
        'domain' => $domain_name,
        'package' => $product->provision_package,
        'auto_renew' => 0,
        'server' => 'host2',
        'hosted' => 1,
    ];
    if ('Register' == $domain_action) {
        $table_data['to_register'] = 1;
    } elseif ('Transfer' == $domain_action) {
        $table_data['transfer_in'] = 1;
    }

    siteworx_register($domain_name, $product->provision_package, $customer->id);


        $interworx = new Interworx();
        $ftp = $interworx->setServer('host2')->setDomain($domain_name)->siteBuilderFTP();
        if (!$ftp) {
            return 'Interworx create error';
        }

        $interworx->setServer('host2')->setDomain($domain_name)->installSitebuilder();
        $table_data['ftp_user'] = $ftp['user'];
        $table_data['ftp_pass'] =  $ftp['pass'];


    if (!empty($email_address_1)) {
        if (!str_contains($email_address_1, '@'.$domain_name)) {
            return 'Email address 1 needs to include @'.$domain_name;
        }

        if (empty($email_password_1) || strlen($email_password_1) < 6) {
            return 'Email password 1 needs to be atleast 6 characters.';
            $result = (new \Interworx())->setServer('host1')->setDomain($domain_name)->createEmail($email_address_1, $email_password_1);
        }
    }

    if (!empty($email_address_2)) {
        if (!str_contains($email_address_2, '@'.$domain_name)) {
            return 'Email address 2 needs to include @'.$domain_name;
        }
        if (empty($email_password_2) || strlen($email_password_2) < 6) {
            return 'Email password 2 needs to be atleast 6 characters.';
            $result = (new \Interworx())->setServer('host1')->setDomain($domain_name)->createEmail($email_address_2, $email_password_2);
        }
    }

    if (!empty($email_address_3)) {
        if (!str_contains($email_address_3, '@'.$domain_name)) {
            return 'Email address 3 needs to include @'.$domain_name;
        }
        if (empty($email_password_3) || strlen($email_password_3) < 6) {
            return 'Email password 3 needs to be atleast 6 characters.';
            $result = (new \Interworx())->setServer('host1')->setDomain($domain_name)->createEmail($email_address_3, $email_password_3);
        }
    }

    if (!empty($ftp_account)) {
        $ftp_account = str_replace('@'.$domain_name, '', $ftp_account);
        if (empty($ftp_password) || strlen($ftp_password) < 6) {
            return 'FTP password needs to be atleast 6 characters.';
            $result = (new \Interworx())->setServer('host1')->setDomain($domain_name)->createFtp($ftp_account, $ftp_password);
        }
    }


    return ['detail' => $domain_name, 'table_data' => ['isp_host_websites' => $table_data]];
}
*/

function provision_hosting($provision, $input, $customer, $product)
{
    // aa('provision_hosting');
    $domain_name = (! empty($input['domain_name'])) ? strtolower($input['domain_name']) : '';
    $domain_name = str_replace([' ', 'www.', 'http://', 'https://'], '', $domain_name);
    $domain_action = (! empty($input['domain_action'])) ? ucfirst($input['domain_action']) : '';
    $domain_epp = (! empty($input['domain_epp'])) ? ucfirst($input['domain_epp']) : '';
    $email_address_1 = (! empty($input['email_address_1'])) ? strtolower($input['email_address_1']) : '';
    $email_address_2 = (! empty($input['email_address_2'])) ? strtolower($input['email_address_2']) : '';
    $email_address_3 = (! empty($input['email_address_3'])) ? strtolower($input['email_address_3']) : '';
    $email_password_1 = (! empty($input['email_password_1'])) ? strtolower($input['email_password_1']) : '';
    $email_password_2 = (! empty($input['email_password_2'])) ? strtolower($input['email_password_2']) : '';
    $email_password_3 = (! empty($input['email_password_3'])) ? strtolower($input['email_password_3']) : '';
    $ftp_account = (! empty($input['ftp_account'])) ? strtolower($input['ftp_account']) : '';
    $ftp_password = (! empty($input['ftp_password'])) ? strtolower($input['ftp_password']) : '';

    if (empty($domain_name) || empty($domain_action)) {
        return 'Fill required inputs';
    }

    $tld_from_input = get_tld($domain_name);

    $tlds_to_process = get_allowed_tld_types_from_invoice($provision->invoice_id);

    // if($tlds_to_process['local'] <= 0 && is_local_domain($domain_name)){
    //     return 'Invoice does not permit any more local domains';
    // }
    // if($tlds_to_process['international'] <= 0 && !is_local_domain($domain_name)){
    //     return 'Invoice does not permit any more international domains';
    // }

    if ($customer->id != 12) {
        $supported_tld = valid_tld($domain_name);
        if (! $supported_tld) {
            return 'Tld not supported';
        }
    }
    if ($domain_action == 'Transfer') {
        if (str_ends_with($domain_name, '.com') || str_ends_with($domain_name, '.net') || str_ends_with($domain_name, '.org')) {
            if (empty($domain_epp)) {
                return 'EPP key required';
            }
        }
    }

    if ($domain_action == 'Register') {
        $available = domain_available($domain_name);
        if ($available == 'Premium') {
            return 'Premium domain names cannot be ordered.';
        }
        if ($available == 'No') {
            return 'Domain name unavailable.';
        }
    } else {
        $available = domain_available($domain_name);
        if ($available == 'Premium') {
            return 'Premium domain names cannot be ordered.';
        }
        if ($available == 'Yes') {
            return 'Domain is not registered, so cannot be transferred.';
        }
    }

    $table_data = [
        'account_id' => $customer->id,
        'created_at' => date('Y-m-d'),
        'domain' => $domain_name,
        'package' => $product->provision_package,
        'auto_renew' => 0,
        'server' => 'host2',
        'hosted' => 1,
    ];
    if ($domain_action == 'Register') {
        $table_data['to_register'] = 1;
    } elseif ($domain_action == 'Transfer') {
        $table_data['transfer_in'] = 1;
    }
    $result = siteworx_register($domain_name, $product->provision_package, $customer->id);

    if (! empty($email_address_1)) {
        if (! str_contains($email_address_1, '@'.$domain_name)) {
            return 'Email address 1 needs to include @'.$domain_name;
        }

        if (empty($email_password_1) || strlen($email_password_1) < 6) {
            return 'Email password 1 needs to be atleast 6 characters.';
            $result = (new \Interworx)->setServer('host2')->setDomain($domain_name)->createEmail($email_address_1, $email_password_1);
        }
    }

    if (! empty($email_address_2)) {
        if (! str_contains($email_address_2, '@'.$domain_name)) {
            return 'Email address 2 needs to include @'.$domain_name;
        }
        if (empty($email_password_2) || strlen($email_password_2) < 6) {
            return 'Email password 2 needs to be atleast 6 characters.';
            $result = (new \Interworx)->setServer('host2')->setDomain($domain_name)->createEmail($email_address_2, $email_password_2);
        }
    }

    if (! empty($email_address_3)) {
        if (! str_contains($email_address_3, '@'.$domain_name)) {
            return 'Email address 3 needs to include @'.$domain_name;
        }
        if (empty($email_password_3) || strlen($email_password_3) < 6) {
            return 'Email password 3 needs to be atleast 6 characters.';
            $result = (new \Interworx)->setServer('host2')->setDomain($domain_name)->createEmail($email_address_3, $email_password_3);
        }
    }

    if (! empty($ftp_account)) {
        $ftp_account = str_replace('@'.$domain_name, '', $ftp_account);
        if (empty($ftp_password) || strlen($ftp_password) < 6) {
            return 'FTP password needs to be atleast 6 characters.';
            $result = (new \Interworx)->setServer('host2')->setDomain($domain_name)->createFtp($ftp_account, $ftp_password);
        }
    }

    return ['detail' => $domain_name, 'table_data' => ['isp_host_websites' => $table_data]];
}

function provision_channel_partner($provision, $input, $customer, $product)
{
    $account = dbgetaccount($customer->id);
    if ($account->type != 'reseller') {
        convert_to_partner($customer->id, false);
    }

    return true;
}

function provision_ip_range_deactivation($provision, $input, $customer, $product)
{
    return [];
}

function provision_number_porting_deactivation($provision, $input, $customer, $product)
{
    $sub = \DB::table('sub_services')->where('id', $provision->subscription_id)->get()->first();
    if (empty($sub->detail)) {
        return 'Invalid number';
    }

    $exists = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->count();
    if (! $exists) {
        return 'Number not found';
    }
    $deleted_at = date('Y-m-d H:i:s');
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->get()->first();
    \DB::table('sub_services')->where('id', $sub->id)->where('status', '!=', 'Deleted')->update(['status' => 'Deleted', 'deleted_at' => $deleted_at]);
    \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->update(['domain_uuid' => null, 'status' => 'Deleted', 'deleted_at' => $deleted_at, 'number_routing' => null, 'routing_type' => null]);

    // \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->delete();
    return ['detail' => $sub->detail];
}

function provision_ip_range_gateway($provision, $input, $customer, $product)
{
    if (empty($input['ip_address'])) {
        return 'IP Address is required';
    }

    if (empty($input['gateway'])) {
        return 'Gateway is required';
    }
    if (empty($input['date_activated'])) {
        $input['date_activated'] = date('Y-m-d H:i:s');
    }

    $exists = \DB::table('sub_services')
        ->where('provision_type', 'ip_range_gateway')->where('detail', $input['ip_address'])->where('status', '!=', 'Deleted')->where('status', '!=', 'Reserved')
        ->count();

    if ($exists) {
        return 'IP Address in use';
    }
    /*

    if (!empty($input['auth_letter'])) {
        $email_body .= '<br><br><b>Authorization letter.</b><br> The client requires an authorization letter.';
    } else {
        $email_body .= '<br><br><b>Authorization letter.</b><br> The client does not require an authorization letter.';
    }

    if (!empty($input['route_object'])) {
        $email_body .= '<br><br><b>Route Object</b>
        route:'.$input['ip_address'].'
        descr: '.$input['company'].'
        origin: '.$input['as_number'].'
        org: ORG-CTL3-AFRINIC
        mnt-by: CLOUDTELECOMS-MNT
        changed: helpdesk@telecloud.co.za
        source: AFRINIC';
    }

    if (!empty($input['rkpi'])) {
        $email_body .= '<br><br><b>Route Origin Authorisation (RKPI)</b>
        AS Number: '.$input['as_number'].'
        IPv4 prefixes: '.$input['ip_address'].'
        Not Valid Before: current date
        Not Valid After: 1 year from now';
    }

    support_email('IP Range Activation', $email_body);
    */
    $data = [
        'account_id' => $customer->id,
        'expiry_date' => date('Y-m-d', strtotime('+1 month')),
        'type' => 'Tunnel',
        'ip_range' => $input['ip_address'],
        'renew' => 1,
        //'is_test'=>0,
    ];
    if (! empty($input['loa_as_number'])) {
        $data['loa_as_number'] = $input['loa_as_number'];
    }

    if (! empty($input['gateway'])) {
        $data['gateway'] = $input['gateway'];
    }

    \DB::table('isp_data_ip_ranges')->where('ip_range', $input['ip_address'])->update($data);

    $ip_range = \DB::table('isp_data_ip_ranges')->where('ip_range', $input['ip_address'])->get()->first();
    update_iprange_winbox($ip_range);

    return ['detail' => $input['ip_address'], 'info' => ['date_activated' => date('Y-m-d')], 'table_data' => $data];
}

function provision_ip_range_route($provision, $input, $customer, $product)
{
    if (empty($input['ip_address'])) {
        return 'IP Address is required';
    }
    if (empty($input['loa_company'])) {
        return 'loa_company is required';
    }
    if (empty($input['loa_as_number'])) {
        return 'loa_as_number is required';
    }
    if (empty($input['date_activated'])) {
        $input['date_activated'] = date('Y-m-d H:i:s');
    }

    $exists = \DB::table('sub_services')
        ->where('provision_type', 'ip_range_route')->where('detail', $input['ip_address'])->where('status', '!=', 'Deleted')->where('status', '!=', 'Reserved')
        ->count();

    if ($exists) {
        return 'IP Address in use';
    }
    /*

    if (!empty($input['auth_letter'])) {
        $email_body .= '<br><br><b>Authorization letter.</b><br> The client requires an authorization letter.';
    } else {
        $email_body .= '<br><br><b>Authorization letter.</b><br> The client does not require an authorization letter.';
    }

    if (!empty($input['route_object'])) {
        $email_body .= '<br><br><b>Route Object</b>
        route:'.$input['ip_address'].'
        descr: '.$input['company'].'
        origin: '.$input['as_number'].'
        org: ORG-CTL3-AFRINIC
        mnt-by: CLOUDTELECOMS-MNT
        changed: helpdesk@telecloud.co.za
        source: AFRINIC';
    }

    if (!empty($input['rkpi'])) {
        $email_body .= '<br><br><b>Route Origin Authorisation (RKPI)</b>
        AS Number: '.$input['as_number'].'
        IPv4 prefixes: '.$input['ip_address'].'
        Not Valid Before: current date
        Not Valid After: 1 year from now';
    }

    support_email('IP Range Activation', $email_body);
    */
    $data = [
        'account_id' => $customer->id,
        'expiry_date' => date('Y-m-d', strtotime('+1 month')),
        'type' => 'Route Object',
        'ip_range' => $input['ip_address'],
        'renew' => 1,
        // 'is_test'=>0,
    ];

    if (! empty($input['loa_as_number'])) {
        $data['loa_as_number'] = $input['loa_as_number'];
    }

    if (! empty($input['loa_company'])) {
        $data['loa_company'] = $input['loa_company'];
    }
    if (! empty($input['gateway'])) {
        $data['gateway'] = $input['gateway'];
    }

    \DB::table('isp_data_ip_ranges')->where('ip_range', $input['ip_address'])->update($data);

    $ip_range = \DB::table('isp_data_ip_ranges')->where('ip_range', $input['ip_address'])->get()->first();
    update_iprange_winbox($ip_range);

    return ['detail' => $input['ip_address'], 'info' => ['date_activated' => date('Y-m-d')], 'table_data' => $data];
}

function provision_telkom_lte_sim_card($provision, $input, $customer, $product)
{
    $axxess = new Axxess;
    $guidClientId = \DB::table('crm_accounts')->where('id', $customer->id)->pluck('guidClientId')->first();
    if (empty($guidClientId)) {
        $client = $axxess->createClient($customer, $input);

        if (! empty($client) && ! empty($client->guidClientId)) {
            $guidClientId = $client->guidClientId;
        }
        if (empty($guidClientId)) {
            return 'Failed to create client account.';
        }

        \DB::table('crm_accounts')->where('id', $customer->id)->update(['guidClientId' => $guidClientId]);
    }

    $simcards = $axxess->getTelkomLteSims();
    if ($simcards->intCode == 200 && count($simcards->arrTelkomLteAvailableSims) > 0) {
        $simcard_product = $simcards->arrTelkomLteAvailableSims[0];
    } else {
        return 'Error no available simcards, please place an order for simcards';
    }
    $guidProductId = \DB::table('isp_data_lte_axxess_products')->where('product_id', $provision->product_id)->pluck('guidProductId')->first();
    if (empty($guidProductId)) {
        return 'LTE product not linked with provider product.';
    }
    $result = $axxess->purchaseTelkomLteService($guidClientId, $guidProductId, $simcard_product->guidServiceId);

    if ($result->intCode != 200) {
        $err = 'Error creating telkom lte service for '.$customer->company;
        if (! empty($result->strMessage)) {
            $err .= PHP_EOL.$result->strMessage;
        }

        debug_email($err);
        if (! empty($result->strMessage) && ! empty($result->intCode)) {
            return 'Axxess error code:'.$result->intCode.': '.$result->strMessage;
        } else {
            return 'Error creating telkom lte service';
        }
    }

    $guidServiceId = $result->guidServiceId;
    $services = $axxess->getServicesByClient($guidClientId);

    if ($services->intCode != 200 || empty($services->arrServices) || count($services->arrServices) == 0) {
        return 'Failed to set telkom lte account.';
    }
    $assigned_guidServiceId = $guidServiceId;

    foreach ($services->arrServices as $service) {
        if ($service->guidServiceId == $assigned_guidServiceId) {
            $lte_details = $service;
        }
    }

    foreach ($services->arrServices as $service) {
        if (str_contains($service->strDescription, 'your') && $service->guidLinkedServiceId == $lte_details->guidServiceId) {
            $simcard_product_reference = $service;
        }
    }

    if (empty($lte_details)) {
        return 'Failed to set lte details.';
    }
    $customer = dbgetaccount($customer->id);
    $table_data = [];
    $table_data['guidServiceId'] = $lte_details->guidServiceId;
    $table_data['guidProductId'] = $lte_details->guidProductId;
    $table_data['guidClientId'] = $customer->guidClientId;

    $table_data['account_id'] = $customer->id;
    $table_data['network'] = 'Telkom';
    $table_data['product_id'] = $provision->product_id;
    $table_data['created_at'] = date('Y-m-d H:i:s');
    $table_data['sim_guidServiceId'] = $simcard_product->guidServiceId;
    $table_data['sim_serialNumber'] = $simcard_product->strSerialNumber;
    $table_data['reference'] = $simcard_product_reference->strDescription;

    return ['detail' => $simcard_product->strSerialNumber, 'table_data' => ['isp_data_lte_axxess_accounts' => $table_data]];
}

function provision_mtn_lte_sim_card($provision, $input, $customer, $product)
{
    $axxess = new Axxess;
    $guidClientId = \DB::table('crm_accounts')->where('id', $customer->id)->pluck('guidClientId')->first();
    if (empty($guidClientId)) {
        $client = $axxess->createClient($customer, $input);

        if (! empty($client) && ! empty($client->guidClientId)) {
            $guidClientId = $client->guidClientId;
        }
        if (empty($guidClientId)) {
            return 'Failed to create client account.';
        }

        \DB::table('crm_accounts')->where('id', $customer->id)->update(['guidClientId' => $guidClientId]);
    }

    $simcards = $axxess->getMtnFixedLteSims();
    if ($simcards->intCode == 200 && count($simcards->arrMtnFixedLteAvailableSims) > 0) {
        $simcard_product = $simcards->arrMtnFixedLteAvailableSims[0];
    } else {
        return 'Error no available simcards, please place an order for simcards';
    }
    $guidProductId = \DB::table('isp_data_lte_axxess_products')->where('product_id', $provision->product_id)->pluck('guidProductId')->first();
    if (empty($guidProductId)) {
        return 'LTE product not linked with provider product.';
    }

    $result = $axxess->purchaseMtnFixedLteService($guidClientId, $guidProductId, $simcard_product->guidServiceId);

    if ($result->intCode != 200) {
        $err = 'Error creating mtn lte service for '.$customer->company;
        if (! empty($result->strMessage)) {
            $err .= PHP_EOL.$result->strMessage;
        }

        debug_email($err);
        if (! empty($result->strMessage) && ! empty($result->intCode)) {
            return 'Axxess error code:'.$result->intCode.': '.$result->strMessage;
        } else {
            return 'Error creating mtn lte service';
        }
    }

    $guidServiceId = $result->guidServiceId;
    $services = $axxess->getServicesByClient($guidClientId);

    if ($services->intCode != 200 || empty($services->arrServices) || count($services->arrServices) == 0) {
        return 'Failed to set mtn lte account.';
    }
    $assigned_guidServiceId = $guidServiceId;

    foreach ($services->arrServices as $service) {
        if ($service->guidServiceId == $assigned_guidServiceId) {
            $lte_details = $service;
        }
    }

    foreach ($services->arrServices as $service) {
        if (str_contains($service->strDescription, 'your') && $service->guidLinkedServiceId == $lte_details->guidServiceId) {
            $simcard_product_reference = $service;
        }
    }

    if (empty($lte_details)) {
        return 'Failed to set lte details.';
    }
    $customer = dbgetaccount($customer->id);
    $table_data = [];
    $table_data['guidServiceId'] = $lte_details->guidServiceId;
    $table_data['guidProductId'] = $lte_details->guidProductId;
    $table_data['guidClientId'] = $customer->guidClientId;

    $table_data['account_id'] = $customer->id;
    $table_data['network'] = 'MTN';
    $table_data['product_id'] = $provision->product_id;
    $table_data['created_at'] = date('Y-m-d H:i:s');
    $table_data['sim_guidServiceId'] = $simcard_product->guidServiceId;
    $table_data['sim_serialNumber'] = $simcard_product->strSerialNumber;
    $table_data['reference'] = $simcard_product_reference->strDescription;

    return ['detail' => $simcard_product->strSerialNumber, 'table_data' => ['isp_data_lte_axxess_accounts' => $table_data]];
}

function provision_mtn5g_lte_sim_card($provision, $input, $customer, $product)
{
    $axxess = new Axxess;
    $guidClientId = \DB::table('crm_accounts')->where('id', $customer->id)->pluck('guidClientId')->first();
    if (empty($guidClientId)) {
        $client = $axxess->createClient($customer, $input);

        if (! empty($client) && ! empty($client->guidClientId)) {
            $guidClientId = $client->guidClientId;
        }
        if (empty($guidClientId)) {
            return 'Failed to create client account.';
        }

        \DB::table('crm_accounts')->where('id', $customer->id)->update(['guidClientId' => $guidClientId]);
    }

    $simcards = $axxess->getMtn5GAvailableSims();
    if ($simcards->intCode == 200 && count($simcards->arrMtn5GAvailableSims) > 0) {
        $simcard_product = $simcards->arrMtn5GAvailableSims[0];
    } else {
        return 'Error no available simcards, please place an order for simcards';
    }
    $guidProductId = \DB::table('isp_data_lte_axxess_products')->where('product_id', $provision->product_id)->pluck('guidProductId')->first();
    if (empty($guidProductId)) {
        return 'LTE product not linked with provider product.';
    }

    $result = $axxess->purchaseMtn5GService($guidClientId, $guidProductId, $simcard_product->guidServiceId, $input['strLatLon'], $input['strAddress'], $input['strSuburb'], $input['strCity'], $input['strCode'], $input['strProvince']);

    if ($result->intCode != 200) {
        $err = 'Error creating mtn lte service for '.$customer->company;
        if (! empty($result->strMessage)) {
            $err .= PHP_EOL.$result->strMessage;
        }

        debug_email($err);
        if (! empty($result->strMessage) && ! empty($result->intCode)) {
            return 'Axxess error code:'.$result->intCode.': '.$result->strMessage;
        } else {
            return 'Error creating mtn lte service';
        }
    }

    $guidServiceId = $result->guidServiceId;
    $services = $axxess->getServicesByClient($guidClientId);

    if ($services->intCode != 200 || empty($services->arrServices) || count($services->arrServices) == 0) {
        return 'Failed to set mtn lte account.';
    }
    $assigned_guidServiceId = $guidServiceId;

    foreach ($services->arrServices as $service) {
        if ($service->guidServiceId == $assigned_guidServiceId) {
            $lte_details = $service;
        }
    }

    foreach ($services->arrServices as $service) {
        if (str_contains($service->strDescription, 'your') && $service->guidLinkedServiceId == $lte_details->guidServiceId) {
            $simcard_product_reference = $service;
        }
    }

    if (empty($lte_details)) {
        return 'Failed to set lte details.';
    }
    $customer = dbgetaccount($customer->id);
    $table_data = [];
    $table_data['guidServiceId'] = $lte_details->guidServiceId;
    $table_data['guidProductId'] = $lte_details->guidProductId;
    $table_data['guidClientId'] = $customer->guidClientId;

    $table_data['account_id'] = $customer->id;
    $table_data['network'] = 'MTN 5G';
    $table_data['product_id'] = $provision->product_id;
    $table_data['created_at'] = date('Y-m-d H:i:s');
    $table_data['sim_guidServiceId'] = $simcard_product->guidServiceId;
    $table_data['sim_serialNumber'] = $simcard_product->strSerialNumber;
    $table_data['reference'] = $simcard_product_reference->strDescription;

    return ['detail' => $simcard_product->strSerialNumber, 'table_data' => ['isp_data_lte_axxess_accounts' => $table_data]];
}

function provision_telkom_lte_topup($provision, $input, $customer, $product)
{
    if (empty($input['guidServiceId'])) {
        return 'LTE Account is required';
    }
    $guidServiceId = $input['guidServiceId'];
    $guidProductId = \DB::table('isp_data_lte_axxess_products')->where('product_id', $product->id)->pluck('guidProductId')->first();

    if (empty($guidProductId)) {
        return 'LTE Topup product not found';
    }
    $axxess = new Axxess('log');
    $result = $axxess->purchaseTelkomLteTopup($guidServiceId, $guidProductId);
    if ($result->intCode != 200 || $result->strStatus != 'OK') {
        debug_email('Error purchasing telkom lte topup for '.$customer->company.PHP_EOL.json_encode($result));

        return 'Error purchasing telkom lte topup';
    }

    $subscription_id = \DB::table('isp_data_lte_axxess_accounts')->where('guidServiceId', $guidServiceId)->pluck('subscription_id')->first();
    $topup_data = [
        'account_id' => $customer->id,
        'invoice_id' => $provision->invoice_id,
        'created_by' => $provision->user_id,
        'subscription_id' => $subscription_id,
        'product_id' => $provision->product_id,
        'status' => 'Enabled',
        'created_at' => date('Y-m-d H:i:s'),
    ];
    \DB::table('sub_service_topups')->insert($topup_data);
}

function update_caller_id($domain_uuid, $invoice_id = false)
{
    $sub = new ErpSubs;

    $d = \DB::connection('pbx')->table('v_domains')
        ->where('domain_uuid', $domain_uuid)
        ->where('cost_calculation', '!=', 'volume')
        ->where('domain_name', '!=', '156.0.96.60')
        ->where('domain_name', '!=', '156.0.96.69')
        ->get()->first();

    // UNALLOCATE AUTO PROVISIONED 087
    $geo_phone_numbers = \DB::connection('pbx')->table('p_phone_numbers')
        ->where('status', 'Enabled')
        ->where('domain_uuid', $d->domain_uuid)
        ->where('number', 'NOT LIKE', '2787%')
        ->count();
    if ($geo_phone_numbers) {
        $subs = \DB::connection('default')->table('sub_services')
            ->where('auto_allocated', 1)
            ->where('product_id', 127)
            ->where('account_id', $d->account_id)
            ->where('status', '!=', 'Deleted')
            ->get();
        foreach ($subs as $s) {
            pbxnumbers_unallocate($s->detail);
        }
    }

    // UPDATE CALLER IDS, AUTO PROVISION 087

    $invoice_has_number = false;
    if ($invoice_id) {
        $geo_numbers = \DB::connection('default')->table('crm_document_lines')->where('document_id', $invoice_id)->whereIn('product_id', [127, 128])->count();
        if ($geo_numbers > 0) {
            $invoice_has_number = true;
        }
    }

    $allocated = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $d->domain_uuid)->where('status', 'Enabled')->count();
    if (! $allocated) {
        if (! $invoice_has_number) {
            $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->pluck('gateway_uuid')->toArray();
            $phone_number = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
                ->select('number', 'prefix')
                ->where('is_spam', 0)
                ->where('number', 'LIKE', '2787%')->whereNull('domain_uuid')
                ->whereIn('gateway_uuid', $gateway_uuids)
                ->orderby('number')->pluck('number')->first();
            if (! empty($phone_number)) {
                pbx_add_number($d->domain_name, $phone_number);
                $sub->createSubscription($d->account_id, 127, $phone_number);
                \DB::connection('default')->table('sub_services')->where('detail', $phone_number)->update(['auto_allocated' => 1]);
            }
        }
    } else {
        $phone_number = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $d->domain_uuid)->where('status', 'Enabled')->pluck('number')->first();
        $phone_numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $d->domain_uuid)->pluck('number')->toArray();
        $caller_id = [
            'outbound_caller_id_number' => $phone_number,
        ];
        \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)
            ->whereNull('outbound_caller_id_number')->update($caller_id);
        \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)
            ->where('outbound_caller_id_number', '')->update($caller_id);
        \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)
            ->whereNotIn('outbound_caller_id_number', $phone_numbers)->update($caller_id);
    }
}

function update_all_caller_ids($domain_uuid = false)
{
    $sub = new ErpSubs;

    if ($domain_uuid) {
        $domains = \DB::connection('pbx')->table('v_domains')
            ->where('domain_uuid', $domain_uuid)
            ->where('cost_calculation', '!=', 'volume')
            ->where('domain_name', '!=', '156.0.96.60')
            ->where('domain_name', '!=', '156.0.96.69')
            ->get();
    } else {
        $domains = \DB::connection('pbx')->table('v_domains')
            ->where('cost_calculation', '!=', 'volume')
            ->where('domain_name', '!=', '156.0.96.60')
            ->where('domain_name', '!=', '156.0.96.69')->get();
    }

    foreach ($domains as $d) {
        $allocated = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $d->domain_uuid)->where('status', 'Enabled')->count();
        if (! $allocated) {
            $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->pluck('gateway_uuid')->toArray();
            $phone_number = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
                ->select('number', 'prefix')
                ->where('number', 'LIKE', '2787%')->whereNull('domain_uuid')
                ->whereIn('gateway_uuid', $gateway_uuids)
                ->where('is_spam', 0)
                ->orderby('number')->pluck('number')->first();
            if (! empty($phone_number)) {
                pbx_add_number($d->domain_name, $phone_number);
                $sub_id = $sub->createSubscription($d->account_id, 127, $phone_number);
                \DB::connection('default')->table('sub_services')->where('id', $sub_id)->update(['auto_allocated' => 1]);
            }
        } else {
            $phone_number = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $d->domain_uuid)->where('status', 'Enabled')->pluck('number')->first();
            $caller_id = [
                'outbound_caller_id_number' => $phone_number,
            ];
            \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)
                ->whereNull('outbound_caller_id_number')->update($caller_id);
            \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)
                ->where('outbound_caller_id_number', '')->update($caller_id);
        }
    }
}

function provision_products_monthly($provision, $input, $customer, $product)
{
    $sub = \DB::table('sub_activations')->where('id', $provision->id)->get()->first();
    if (! $sub->printed) {
        return json_alert('Invoice needs to be printed.', 'warning');
    }
    if (empty($sub->pod_file)) {
        return json_alert('POD file needs to be uploaded.', 'warning');
    }

    $detail = $provision->product_id.'_'.$provision->account_id;
    $activation_detail_exists = \DB::table('sub_activations')->where('detail', $detail)->get()->first();
    $detail_exists = \DB::table('sub_services')->where('detail', $detail)->get()->first();

    $i = 1;
    $new_detail = false;
    while ($activation_detail_exists == 1 || $detail_exists == 1) {
        $new_detail = $detail.'_'.$i;
        $activation_detail_exists = \DB::table('sub_activations')->where('detail', $new_detail)->get()->first();
        $detail_exists = \DB::table('sub_services')->where('detail', $new_detail)->get()->first();
        $i++;
    }
    if ($new_detail) {
        $detail = $new_detail;
    }

    return ['detail' => $detail];
}

function provision_iptv_trial($provision, $input, $customer, $product)
{
    /*
     if (empty($input['iptv_id'])) {
         return 'IPTV account is required';
     }
     $iptv_account = \DB::table('isp_data_iptv')->where('id',$input['iptv_id'])->get()->first();
    */
    $iptv_account = \DB::table('isp_data_iptv')
        ->where('trial', 1)
        ->where('product_id', $provision->product_id)
        ->where('subscription_id', 0)
        ->get()->first();

    if (empty($iptv_account)) {
        return 'Activation requires iptv line for '.$product->code.' '.$product->id.', please renew an expired line or create a new trial line.';
    }
    $table_data['username'] = $iptv_account->username;
    $table_data['password'] = $iptv_account->password;
    $table_data['account_id'] = $provision->account_id;

    return ['detail' => $iptv_account->username, 'table_data' => ['isp_data_iptv' => $table_data]];
}

function provision_iptv_reseller_check_account($provision, $input, $customer, $product)
{
    // function to run before other iptv reseller activation steps
    // check if account info set on accounts table
    // if reseller account is already setup
    // create task to provision credits
    $account_exists = \DB::table('crm_accounts')->where('id', $customer->id)->where('iptv_username', '>', '')->get();
    $qty = \DB::table('crm_document_lines')->where('document_id', $provision->invoice_id)->where('product_id', $product->id)->sum('qty');

    if ($account_exists) {
        $task = [
            'role_id' => 1,
            'user_id' => 1,
            'name' => 'Qty: '.$qty.', '.$product->code.' '.$product->name.' needs to be loaded on iptv portal',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 5365,
            'instance_id' => 11,
            'type' => 'Task',
        ];
        \DB::connection('system')->table('crm_staff_tasks')->insert($task);
        \DB::table('sub_activations')->where('id', $provision->id)->update(['status' => 'Enabled']);
    }

    return true;
}

function provision_iptv_reseller($provision, $input, $customer, $product)
{
    /*
    if (empty($input['iptv_username'])) {
        return 'Username is required';
    }
    if (empty($input['iptv_password'])) {
        return 'Password is required';
    }

    $account = dbgetaccount($customer->id);

    \DB::table('crm_accounts')->where('id',$customer->id)->update(['iptv_username'=>$input['iptv_username'],'iptv_password'=>$input['iptv_password']]);
    return true;
    */
    $account = dbgetaccount($customer->id);
    if ($account->type != 'reseller') {
        convert_to_partner($customer->id, false);
    }

    return true;
}

function provision_iptv($provision, $input, $customer, $product)
{
    /*
     if (empty($input['iptv_id'])) {
         return 'IPTV account is required';
     }
     $iptv_account = \DB::table('isp_data_iptv')->where('id',$input['iptv_id'])->get()->first();
    */
    $iptv_account = \DB::table('isp_data_iptv')
        ->where('trial', 0)
        ->where('subscription_status', 'Enabled')
        ->where('product_id', $provision->product_id)
        ->where('subscription_id', 0)
        ->get()->first();
    if (empty($iptv_account)) {
        return 'Activation requires iptv line for '.$product->code.', please renew an expired line or create a new line.';
    }
    $table_data['username'] = $iptv_account->username;
    $table_data['password'] = $iptv_account->password;
    $table_data['account_id'] = $provision->account_id;

    return ['detail' => $iptv_account->username, 'table_data' => ['isp_data_iptv' => $table_data]];
}

function provision_iptv_addon($provision, $input, $customer, $product)
{
    if (empty($input['iptv_id'])) {
        return 'IPTV account is required';
    }
    $iptv_account = \DB::table('isp_data_iptv')->where('id', $input['iptv_id'])->get()->first();

    $detail = $iptv_account->username.'_addon_'.$product->id;

    return ['detail' => $detail];
}

function provision_iptv_global($provision, $input, $customer, $product)
{
    if (empty($input['iptv_id'])) {
        return 'IPTV account is required';
    }
    $iptv_account = \DB::table('isp_data_iptv')->where('id', $input['iptv_id'])->get()->first();

    $table_data['username'] = $iptv_account->username;
    $table_data['password'] = $iptv_account->password;

    $table_data['account_id'] = $provision->account_id;

    return ['detail' => $iptv_account->username, 'table_data' => ['isp_data_iptv' => $table_data]];
}

function provision_airtime_postpaid($provision, $input, $customer, $product)
{
    if (empty(session('instance')) || empty(session('instance')->directory)) {
        return 'Session Expired';
    }
    if (empty($customer->pabx_domain)) {
        $pbx_domain = pbx_create_domain_company_name($customer);
        if (! $pbx_domain) {
            'PBX add failed. DNS create failed on '.__FUNCTION__;
        }

        pbx_add_domain($pbx_domain, $customer->id);
        provision_pbx_extension_default($customer, $provision->invoice_id);
    } else {
        $extension_count = \DB::table('sub_services')
            ->where('account_id', $customer->id)->where('provision_type', 'pbx_extension')->where('status', '!=', 'Deleted')
            ->count();
        $sip_count = \DB::table('sub_services')
            ->where('account_id', $customer->id)->where('provision_type', 'sip_trunk')->where('status', '!=', 'Deleted')
            ->count();
        if (! $extension_count && ! $sip_count) {
            provision_pbx_extension_default($customer, $provision->invoice_id);
        }
    }
    $customer = dbgetaccount($customer->id);
    $pbx_connection = get_pbx_connection($customer->pabx_domain);
    \DB::connection('pbx')->table('v_domains')->where('account_id', $customer->id)->update(['is_postpaid' => 1, 'postpaid_limit' => 1000]);
    $balance = \DB::connection('pbx')->table('v_domains')->where('account_id', $customer->id)->pluck('balance')->first();

    $info = [
        'current_usage' => $balance,
        'usage_type' => $usage_type,
        'usage_allocation' => 0,
    ];
    $customer = dbgetaccount($customer->id);

    return ['detail' => $customer->pabx_domain, 'info' => $info];
}

function provision_teamoffice($provision, $input, $customer, $product)
{
    if (empty($input['username'])) {
        return 'Username is required';
    }
    if (empty($input['password'])) {
        return 'Password is required';
    }

    if (empty($input['domain_name'])) {
        return 'Domain name is required';
    }

    $table_data['username'] = $input['username'];
    $table_data['password'] = $input['password'];
    $table_data['domain_name'] = $input['domain_name'];

    $table_data['account_id'] = $provision->account_id;

    return ['detail' => $input['username'], 'table_data' => ['crm_team_office_accounts' => $table_data]];
}

function provision_noip($provision, $input, $customer, $product)
{
    return ['detail' => 'noip'.$provision->id];
}
