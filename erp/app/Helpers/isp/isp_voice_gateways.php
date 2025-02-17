<?php

function schedule_gateway_status()
{

    if (! is_main_instance()) {
        return false;
    }
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sofia_status_gateway');
    // dd($result);
    $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

    $json = json_encode($xml);
    $gateways = json_decode($json, true);

    // CHECK GATEWAYS

    if (! empty($result) && ! str_starts_with($result, 'SSH')) {
        $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

        $json = json_encode($xml);
        $gateways = json_decode($json, true);

        $gateway_errors = [];
        $processed_gateways = [];
        \DB::connection('pbx')->table('v_gateways')->update(['state' => null, 'status' => '']);
        \DB::connection('pbx')->table('v_gateways')->where('enabled', 'true')->update(['status' => 'Stopped']);
        foreach ($gateways['gateway'] as $gateway) {

            if (in_array($gateway['name'], $processed_gateways)) {
                continue;
            }
            $processed_gateways[] = $gateway['name'];
            $dbgateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->get()->first();

            if ($dbgateway->enabled == 'true') {
                \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['state' => $gateway['state']]);
                if ($gateway['status'] == 'UP') {
                    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['status' => 'Running']);
                }
            }
        }
    }
    //return false;
    /*
    $pbx = new FusionPBX();
    $result = $pbx->portalCmd('portal_sofia_status_gateway');
    $xml = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);

    $json = json_encode($xml);
    $gateways = json_decode($json, true);

    // RESTART GATEWAYS
    $gateways_restarted = false;
    if (!empty($result) && !str_starts_with($result,'SSH')) {
        $xml = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);

        $json = json_encode($xml);
        $gateways = json_decode($json, true);

        $gateway_errors = [];
        $processed_gateways = [];
        foreach ($gateways['gateway'] as $gateway) {

            if(in_array($gateway['name'],$processed_gateways)){
                continue;
            }
            $processed_gateways[] = $gateway['name'];
            $dbgateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->get()->first();
            if ($dbgateway->enabled == 'true' && ($dbgateway->use_rate || $dbgateway->use_rate_international)){

                if ($gateway['state'] != 'REGISTER' && $gateway['state']!='REGED' && $gateway['state']!='NOREG') {
                        $pbx->portalCmd('portal_gateway_stop',$gateway['name']);
                        $gateways_restarted = true;

                }
            }
        }
    }

    if($gateways_restarted){

        $pbx->portalCmd('portal_gateway_start');
    }
    */

    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sofia_status_gateway');
    $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

    $json = json_encode($xml);
    $gateways = json_decode($json, true);

    // CHECK GATEWAYS

    if (! empty($result) && ! str_starts_with($result, 'SSH')) {
        $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

        $json = json_encode($xml);
        $gateways = json_decode($json, true);

        $gateway_errors = [];
        $processed_gateways = [];
        foreach ($gateways['gateway'] as $gateway) {

            if (in_array($gateway['name'], $processed_gateways)) {
                continue;
            }
            $processed_gateways[] = $gateway['name'];
            $dbgateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->get()->first();

            if ($dbgateway->enabled == 'true' && ($dbgateway->use_rate || $dbgateway->use_rate_international)) {

                if ($gateway['state'] != 'REGISTER' && $gateway['state'] != 'REGED' && $gateway['state'] != 'NOREG') {

                    admin_email('Active gateway: '.$dbgateway->gateway.' is down. STATE is '.$gateway['state']);
                    queue_sms(12, 'Active gateway: '.$dbgateway->gateway.' is down. STATE is '.$gateway['state'].date('Y-m-d H:i:s'), 1, 1);
                }
            }
        }
    }

    return false;
    /*
    try {
        $pbx = new FusionPBX();


        $result = $pbx->portalCmd('portal_sofia_status_gateway');
        // RESTART GATEWAYS
        $gateways_restarted = false;
        if (!empty($result) && !str_starts_with($result,'SSH')) {
            $xml = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);

            $json = json_encode($xml);
            $gateways = json_decode($json, true);

            $gateway_errors = [];
            $processed_gateways = [];
            foreach ($gateways['gateway'] as $gateway) {
                if(in_array($gateway['name'],$processed_gateways)){
                    continue;
                }
                $processed_gateways[] = $gateway['name'];
                $gateway_enabled = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->where('enabled','true')->count();
                $gateway_profile = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->pluck('profile')->first();

                if ($gateway_enabled && $gateway['state'] != 'REGISTER' && $gateway['state']!='REGED' && $gateway['state']!='NOREG') {
                    if($gateway_profile == 'external'){
                        $pbx->portalCmd('portal_gateway_external_stop',$gateway['name']);
                    }else{
                        $pbx->portalCmd('portal_gateway_stop',$gateway['name']);
                    }
                    $gateways_restarted = true;
                }
            }
        }

        if($gateways_restarted){

            $pbx->portalCmd('portal_gateway_start');

        }


        $result = $pbx->portalCmd('portal_sofia_status_gateway');

        if (!empty($result) && !str_starts_with($result,'SSH')) {
            $xml = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);

            $json = json_encode($xml);
            $gateways = json_decode($json, true);

            $gateway_errors = [];
            $processed_gateways = [];
            foreach ($gateways['gateway'] as $gateway) {
                if(in_array($gateway['name'],$processed_gateways)){
                    continue;
                }
                $processed_gateways[] = $gateway['name'];
                $gateway_enabled = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->where('enabled','true')->count();
                $gateway_last_uptime = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->pluck('last_uptime_check')->first();


               if ($gateway_enabled && $gateway['state'] != 'REGISTER' && $gateway['state']!='REGED' && $gateway['state']!='NOREG') {
                    $gateway_name = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->pluck('gateway')->first();
                    $gateway_error = 'Freeswitch CLI - '.$gateway_name.' register status is '.$gateway['state'].', last uptime:'.$gateway_last_uptime.', gateway disabled.';
                    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['enabled'=>'false']);
                    admin_rates_summary_set_lowest_active();

                    $data = [];
                    $data['gateway_errors'] = $gateway_error;
                    $data['subject'] = $gateway_name.' registered status changed';
                    $data['function_name'] = __FUNCTION__;

                    $data['cc_email'] = 'ahmed@telecloud.co.za';
                    //$data['test_debug'] = 1;
                    erp_process_notification(1, $data);
                }elseif (!$gateway_enabled && ($gateway['state'] == 'REGISTER' || $gateway['state']=='REGED' || $gateway['state']=='NOREG')) {
                    $gateway_name = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->pluck('gateway')->first();
                    $gateway_error = 'Freeswitch CLI - '.$gateway_name.' register status is '.$gateway['state'].', gateway enabled.';
                    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['enabled'=>'true']);
                    admin_rates_summary_set_lowest_active();

                    $data = [];
                    $data['gateway_errors'] = $gateway_error;
                    $data['subject'] = $gateway_name.' registered status changed';
                    $data['function_name'] = __FUNCTION__;
                    $data['cc_email'] = 'ahmed@telecloud.co.za';
                    //$data['test_debug'] = 1;
                    erp_process_notification(1, $data);
                }
            }
        }
    } catch (\Throwable $ex) {  exception_log($ex);
    }
    */

    /*
    $active_gateways = \DB::connection('pbx')->table('v_gateways')->where('enabled','true')->get();
    foreach($active_gateways as $gateway){
        if($gateway->use_rate || $gateway->use_rate_international){
            $gateway_response = $pbx->portalCmd('portal_gateway_status', $gateway->gateway_uuid);

            if ($gateway_response == "Invalid Gateway!") {
                $gateway_name = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->pluck('gateway')->first();
                $gateway_last_uptime = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->pluck('last_uptime_check')->first();
                $gateway_error = 'Freeswitch CLI - sofia error '.$gateway_response.' '.$gateway->gateway.', last uptime:'.$gateway_last_uptime.', gateway disabled.';
                \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->update(['enabled'=>'false']);
                admin_rates_summary_set_lowest_active();

                $data = [];
                $data['gateway_errors'] = $gateway_error;
                $data['subject'] = $gateway->gateway.' Invalid Gateway!';
                $data['function_name'] = __FUNCTION__;

                $data['cc_email'] = 'ahmed@telecloud.co.za';
                //$data['test_debug'] = 1;
                erp_process_notification(1, $data);
            }
        }
    }
    */

}

function button_gateways_update_lcr($request)
{

    rates_complete_set_lowest_rate();
    admin_rates_summary_set_lowest_active();

    return json_alert('LCR Updated');
}

function button_gateways_local_rates_on($request)
{

    $gateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->get()->first();
    if ($gateway->use_rate) {
        return json_alert('Local rates already enabled', 'warning');
    }
    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update(['use_rate' => 1]);
    import_rates_summary_from_rates_complete($gateway->gateway_uuid);
    rates_complete_set_lowest_rate();
    admin_rates_summary_set_lowest_active();

    return json_alert('Local rates enabled');

}
function button_gateways_local_rates_off($request)
{

    $gateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->get()->first();
    if (! $gateway->use_rate) {
        return json_alert('Local rates already disabled', 'warning');
    }
    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update(['use_rate' => 0]);
    import_rates_summary_from_rates_complete($gateway->gateway_uuid);
    rates_complete_set_lowest_rate();
    admin_rates_summary_set_lowest_active();

    return json_alert('Local rates disabled');

}

function button_gateways_international_rates_on($request)
{

    $gateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->get()->first();
    if ($gateway->use_rate_international) {
        return json_alert('International rates already enabled', 'warning');
    }
    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update(['use_rate_international' => 1]);
    import_rates_summary_from_rates_complete($gateway->gateway_uuid);
    rates_complete_set_lowest_rate();
    admin_rates_summary_set_lowest_active();

    return json_alert('International rates enabled');
}
function button_gateways_international_rates_off($request)
{

    $gateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->get()->first();
    if (! $gateway->use_rate_international) {
        return json_alert('International rates already disabled', 'warning');
    }
    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update(['use_rate_international' => 0]);
    import_rates_summary_from_rates_complete($gateway->gateway_uuid);
    rates_complete_set_lowest_rate();
    admin_rates_summary_set_lowest_active();

    return json_alert('International rates disabled');
}

function aftersave_gateways($request)
{
    $gateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->get()->first();
    $beforesave_row = session('event_db_record');
    if ($beforesave_row && (($beforesave_row->use_rate != $request->use_rate) || ($beforesave_row->use_rate_international != $request->use_rate_international))) {
        import_rates_summary_from_rates_complete($gateway->gateway_uuid);
        rates_complete_set_lowest_rate();
        admin_rates_summary_set_lowest_active();
    }
    $pbx = new FusionPBX;
    $pbx->portalCmd('portal_aftersave_gateways');
}

function beforedelete_gateway_numbers_check($request)
{
    $exists = \DB::connection('pbx')->table('p_phone_numbers')->where('gateway_uuid', $request->id)->count();
    if ($exists) {
        return 'Supplier cannot be deleted, supplier is linked to active numbers';
    }
}

function schedule_gateway_set_last_register_time()
{

    $pbx = new FusionPBX;
    \DB::connection('pbx')->table('v_gateways')->update(['uptime' => null]);
    $gateways = \DB::connection('pbx')->table('v_gateways')->where('enabled', 'true')->get();
    foreach ($gateways as $gateway) {

        $response = $pbx->portalCmd('portal_gateway_register_status', $gateway->gateway_uuid);
        $response_lines = explode(PHP_EOL, $response);

        foreach ($response_lines as $line) {
            if (str_contains($line, 'Status')) {
                $status = trim(str_replace(['Status', "\t", "\n"], '', $line));

                if ($status == 'UP') {
                    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->update(['last_uptime_check' => date('Y-m-d H:i:s')]);
                }
            }

            if (str_contains($line, 'Uptime')) {

                $uptime = trim(str_replace(['Uptime', "\t", "\n"], '', $line));

                \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->update(['uptime' => $uptime]);
            }
        }

    }
}

function schedule_gateways_retry_gateways_enable()
{
    /*
    if(date('H') == 4 || date('H') == 11){
        $gateways = \DB::connection('pbx')->table('v_gateways')->where('enabled','false')->get();
        foreach($gateways as $gateway){
            if($gateway->use_rate || $gateway->use_rate_international){
                \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid',$gateway->gateway_uuid)->update(['enabled'=>'true']);
                //freeswitch> sofia profile <profile_name> killgw <gateway_name> freeswitch> sofia profile <profile_name> rescan

                $pbx = new FusionPBX();
                $gateway_profile = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->pluck('profile')->first();


                if($gateway_profile == 'external'){
                    $pbx->portalCmd('portal_gateway_external_stop',$gateway->gateway_uuid);
                }else{
                    $pbx->portalCmd('portal_gateway_stop',$gateway->gateway_uuid);
                }

                $result = $pbx->portalCmd('portal_gateway_start');

            }
        }
    }
    */
}

function schedule_gateways_volume_number_allocation()
{

    $volume_number_results = allocate_volume_phone_numbers();
    if (count($volume_number_results) > 0) {
        $data = [];
        $data['gateway_errors'] = implode('<br>', $volume_number_results);
        $data['subject'] = 'Gateway volume domains number allocation';
        $data['function_name'] = __FUNCTION__;
        //$data['test_debug'] = 1;
        $data['cc_email'] = 'ahmed@telecloud.co.za';
        erp_process_notification(1, $data);
    }

}

function schedule_gateways_cdr_check()
{
    // aa($processed_gateways);
    // $gateway_uuids = ['0d0d2b47-af57-4b02-80ff-5cb787c865c0', '3322dd55-e145-41fa-9f63-911f8b3118e8','57e4b17a-4f45-4901-9030-6fc53159ca19'];
    /*
    $vodacom_gateway = \DB::connection('pbx')->table('v_gateways')->where('gateway', 'LIKE', "VODACOM%")->get()->first();
    $pbx = new FusionPBX;


    //check vodacom
    if($vodacom_gateway){
        $data_from = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $vodacom_calls = \DB::connection('pbx_cdr')->table('call_records_outbound')
        ->where('gateway',$vodacom_gateway->gateway)
        ->where('hangup_time','>',$data_from)
        ->orderBy('id','desc')->limit('20')->get();

        $vodacom_error_count = 0;
        $caller_ids = [];
        foreach($vodacom_calls as $c){
            if($c->hangup_cause == 'ORIGINATOR_CANCEL'){
                $caller_ids[] = $c->caller_id_number;
                $vodacom_error_count++;
            }
        }

        if($vodacom_error_count == 20){
         //   \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid',$vodacom_gateway->gateway_uuid)->update(['enabled'=>'false']);
         //   admin_rates_summary_set_lowest_active();
            $data = [];
            $data['gateway_errors'] = $vodacom_gateway->gateway.' Gateway is down, 20 calls set to ORIGINATOR_CANCEL';

            $data['gateway_errors'] .= '<br><br>Caller Id Numbers: <br><br>';
            foreach($caller_ids as $i => $caller_id){
                if($i<10){
                $caller_id_error_count = \DB::connection('pbx_cdr')
                                ->table('call_records_outbound')
                                ->select('hangup_cause','duration')
                                ->where('gateway',$vodacom_gateway->gateway)
                                ->where('caller_id_number',$caller_id)
                                ->where('hangup_cause','ORIGINATOR_CANCEL')
                                ->where('hangup_time','LIKE',date('Y-m-d').'%')
                                ->count();
                $data['gateway_errors'] .= $caller_id.': '.$caller_id_error_count.'<br>';
                }
            }
            $data['function_name'] = __FUNCTION__;
            $data['subject'] = $vodacom_gateway->gateway.' ORIGINATOR_CANCEL errors';
            $data['cc_email'] = 'ahmed@telecloud.co.za';
            //$data['test_debug'] = 1;
            erp_process_notification(1, $data);
        }
    }
    */

    $pbx = new FusionPBX;
    $active_gateways = \DB::connection('pbx')->table('v_gateways')->get();

    $data_from = date('Y-m-d H:i:s', strtotime('-1 hour'));
    foreach ($active_gateways as $active_gateway) {

        // $gateway_response = $pbx->portalCmd('portal_gateway_status', $active_gateway->gateway_uuid);
        // if($gateway_response == "Invalid Gateway!"){
        // continue;
        //  }

        $calls = \DB::connection('pbx_cdr')
            ->table('call_records_outbound')
            ->select('hangup_cause', 'duration', 'caller_id_number')
            ->where('gateway', $active_gateway->gateway)
            ->where('hangup_time', '>', $data_from)
            ->orderBy('id', 'desc')->limit('20')->get();
        $caller_ids = [];
        if ($calls->count() < 20) {
            //$data = [];
            //$data['gateway_errors'] = $active_gateway->gateway.' Gateway is down, Total calls is less than 20';
            //$data['function_name'] = __FUNCTION__;
            //$data['test_debug'] = 1;
            //erp_process_notification(1, $data);
        } else {
            $error_count = 0;
            foreach ($calls as $c) {
                if ($c->hangup_cause == 'NORMAL_CLEARING' && $c->duration == 0) {
                    $error_count++;
                    $caller_ids[] = $c->caller_id_number;
                }
            }
            if ($error_count == 20) {
                $data = [];
                $data['gateway_errors'] = $active_gateway->gateway.' Gateway is down, 20 calls set to NORMAL_CLEARING with zero duration.';
                $data['gateway_errors'] .= '<br><br>Caller Id Numbers: <br><br>';
                foreach ($caller_ids as $i => $caller_id) {
                    if ($i < 10) {
                        $caller_id_error_count = \DB::connection('pbx_cdr')
                            ->table('call_records_outbound')
                            ->select('hangup_time', 'hangup_cause', 'duration')
                            ->where('gateway', $active_gateway->gateway)
                            ->where('caller_id_number', $caller_id)
                            ->where('hangup_cause', 'NORMAL_CLEARING')
                            ->where('duration', 0)
                            ->where('hangup_time', '>', $data_from)
                            ->count();
                        $data['gateway_errors'] .= $caller_id.': '.$caller_id_error_count.'<br>';
                    }
                }
                $data['function_name'] = __FUNCTION__;
                $data['subject'] = $active_gateway->gateway.' NORMAL_CLEARING errors';
                $data['cc_email'] = 'ahmed@telecloud.co.za';
                //$data['test_debug'] = 1;
                erp_process_notification(1, $data);
            }

        }
    }

}
function pbx_update_gateways_status()
{
    if (! is_main_instance()) {
        return false;
    }
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sofia_status_gateway');
    $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

    $json = json_encode($xml);
    $gateways = json_decode($json, true);

    // CHECK GATEWAYS

    if (! empty($result) && ! str_starts_with($result, 'SSH')) {
        $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

        $json = json_encode($xml);
        $gateways = json_decode($json, true);

        $gateway_errors = [];
        $processed_gateways = [];
        \DB::connection('pbx')->table('v_gateways')->update(['state' => null, 'status' => '']);
        \DB::connection('pbx')->table('v_gateways')->where('enabled', 'true')->update(['status' => 'Stopped']);
        foreach ($gateways['gateway'] as $gateway) {

            if (in_array($gateway['name'], $processed_gateways)) {
                continue;
            }
            $processed_gateways[] = $gateway['name'];
            $dbgateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->get()->first();

            if ($dbgateway->enabled == 'true') {
                \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['state' => $gateway['state']]);
                if ($gateway['status'] == 'UP') {
                    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['status' => 'Running']);
                }
            }
        }
    }
}

function button_update_gateways_status($request)
{
    if (! is_main_instance()) {
        return false;
    }
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sofia_status_gateway');
    $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

    $json = json_encode($xml);
    $gateways = json_decode($json, true);

    // CHECK GATEWAYS

    if (! empty($result) && ! str_starts_with($result, 'SSH')) {
        $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

        $json = json_encode($xml);
        $gateways = json_decode($json, true);

        $gateway_errors = [];
        $processed_gateways = [];
        \DB::connection('pbx')->table('v_gateways')->update(['state' => null, 'status' => '']);
        \DB::connection('pbx')->table('v_gateways')->where('enabled', 'true')->update(['status' => 'Stopped']);
        foreach ($gateways['gateway'] as $gateway) {

            if (in_array($gateway['name'], $processed_gateways)) {
                continue;
            }
            $processed_gateways[] = $gateway['name'];
            $dbgateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->get()->first();

            if ($dbgateway->enabled == 'true') {
                \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['state' => $gateway['state']]);
                if ($gateway['status'] == 'UP') {
                    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway['name'])->update(['status' => 'Running']);
                }
            }
        }
    }

    return json_alert('Done');
}

function button_reset_volume_numbers_to_min($request)
{
    $gateways = \DB::connection('pbx')->table('v_gateways')->where('volume_numbers_required', '>', 0)->where('enabled', 'true')->get();
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->get();
    $allocated_numbers = [];

    foreach ($gateways as $gateway) {
        $numbers_per_domain = $gateway->volume_numbers_required;

        foreach ($volume_domains as $volume_domain) {
            $num_extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $volume_domain->domain_uuid)->count();
            $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $volume_domain->domain_uuid)->get();
            foreach ($extensions as $ext) {

                $allocated_count = \DB::connection('pbx')->table('p_phone_numbers')
                    ->where('status', 'Enabled')
                    ->where('wholesale_ext', $ext->extension)
                    ->where('domain_uuid', $volume_domain->domain_uuid)
                    ->where('gateway_uuid', $gateway->gateway_uuid)->count();
                if ($allocated_count > $numbers_per_domain) {
                    $allocated_ids = \DB::connection('pbx')->table('p_phone_numbers')
                        ->where('status', 'Enabled')
                        ->where('wholesale_ext', $ext->extension)
                        ->where('domain_uuid', $volume_domain->domain_uuid)
                        ->where('gateway_uuid', $gateway->gateway_uuid)->limit($numbers_per_domain)->pluck('id')->toArray();
                    $numbers_to_remove = \DB::connection('pbx')->table('p_phone_numbers')
                        ->where('status', 'Enabled')
                        ->where('wholesale_ext', $ext->extension)
                        ->where('domain_uuid', $volume_domain->domain_uuid)
                        ->where('gateway_uuid', $gateway->gateway_uuid)
                        ->whereNotIn('id', $allocated_ids)
                        ->get();
                    foreach ($numbers_to_remove as $num) {
                        // aa($volume_domain->domain_name);
                        // aa($num->number);

                        $deleted_at = date('Y-m-d H:i:s');
                        if ($num->domain_uuid > '') {
                            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('account_id')->first();
                            \DB::table('sub_services')->where('detail', $num->number)->where('status', 'Deleted')->delete();
                            \DB::table('sub_services')->where('detail', $num->number)->where('account_id', $account_id)->update(['status' => 'Deleted', 'deleted_at' => $deleted_at]);
                        }
                        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $num->id)->where('status', 'Deleted')->update(['domain_uuid' => null, 'number_routing' => null, 'routing_type' => null, 'wholesale_ext' => 0]);
                        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $num->id)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled', 'number_routing' => null, 'routing_type' => null, 'wholesale_ext' => 0]);

                    }
                }
            }
        }
    }

    return json_alert('Done');
}

function allocate_volume_phone_numbers($gateway_uuid = false, $volume_numbers_required = false)
{

    $debug = false;
    // $debug = true;
    $volume_number_results = [];
    if ($gateway_uuid) {
        $gateways = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->get();
    } else {
        $gateways = \DB::connection('pbx')->table('v_gateways')->where('volume_numbers_required', '>', 0)->get();
    }
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('domain_name', '!=', 'lti.cloudtools.co.za')->where('cost_calculation', 'volume')->get();
    $allocated_numbers = [];

    foreach ($gateways as $gateway) {
        $numbers_per_domain = $gateway->volume_numbers_required;
        if ($gateway_uuid && $volume_numbers_required && $gateway_uuid == $gateway->gateway_uuid) {
            $numbers_per_domain = $volume_numbers_required;
        }
        $numbers_required = $numbers_per_domain;
        foreach ($volume_domains as $volume_domain) {
            $num_extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $volume_domain->domain_uuid)->count();
            $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $volume_domain->domain_uuid)->get();
            foreach ($extensions as $ext) {
                $numbers_per_domain = $numbers_required;

                $allocated_count = \DB::connection('pbx')->table('p_phone_numbers')
                    ->where('status', 'Enabled')
                    ->where('wholesale_ext', $ext->extension)
                    ->where('domain_uuid', $volume_domain->domain_uuid)
                    ->where('gateway_uuid', $gateway->gateway_uuid)->count();

                if ($allocated_count < $numbers_per_domain) {
                    $available_count = \DB::connection('pbx')->table('p_phone_numbers')
                        ->whereNotIn('number', $allocated_numbers)
                        ->where('status', 'Enabled')
                        ->where('is_spam', 0)
                        ->whereNull('domain_uuid')
                        ->where('gateway_uuid', $gateway->gateway_uuid)->count();
                    $provision_count = $numbers_per_domain - $allocated_count;

                    if ($available_count < $provision_count) {
                        $volume_number_results[] = $gateway->gateway.' does not have enough numbers to allocate to volume domains. Available: '.$available_count;

                        continue 2;
                    } else {
                        for ($i = 1; $i <= $provision_count; $i++) {
                            $num = \DB::connection('pbx')->table('p_phone_numbers')
                                ->where('status', 'Enabled')
                                ->whereNotIn('number', $allocated_numbers)
                                ->where('is_spam', 0)
                                ->whereNull('domain_uuid')
                                ->where('gateway_uuid', $gateway->gateway_uuid)->get()->first();
                            if (! $debug) {
                                \DB::connection('pbx')->table('p_phone_numbers')->where('id', $num->id)
                                    ->update(['routing_type' => 'extension', 'number_routing' => $ext->extension, 'wholesale_ext' => $ext->extension, 'domain_uuid' => $volume_domain->domain_uuid]);
                            }
                            $num->domain_uuid = $volume_domain->domain_uuid;

                            $account_id = $volume_domain->account_id;

                            $subs_count = \DB::connection('default')->table('sub_services')->where('detail', $num->number)->count();

                            $phone_number = $num->number;
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

                            $subscription_data = [
                                'account_id' => $account_id,
                                'status' => 'Enabled',
                                'bill_frequency' => 1,
                                'provision_type' => 'phone_number',
                                'detail' => $phone_number,
                                'product_id' => $subscription_product,
                                'created_at' => date('Y-m-d H:i:s'),
                                'date_activated' => date('Y-m-d H:i:s'),
                            ];
                            $allocated_numbers[] = $phone_number;
                            if ($debug) {
                                $volume_number_results[] = $phone_number.' allocated to volume domain. Gateway: '.$gateway->gateway.'. Domain: '.$volume_domain->domain_name.'. Extension: '.$ext->extension;
                            } else {

                                if ($subs_count == 0) {
                                    \DB::connection('default')->table('sub_services')->insert($subscription_data);
                                } elseif ($subs_count == 1) {
                                    \DB::connection('default')->table('sub_services')->where('detail', $num->number)->update($subscription_data);
                                } else {
                                    \DB::connection('default')->table('sub_services')->where('detail', $num->number)->delete();
                                    \DB::connection('default')->table('sub_services')->insert($subscription_data);
                                }
                                $volume_number_results[] = $phone_number.' allocated to volume domain. Gateway: '.$gateway->gateway.'. Domain: '.$volume_domain->domain_name;
                                $sub_id = \DB::connection('default')->table('sub_services')->where('detail', $num->number)->pluck('id')->first();

                                $activation_data = [
                                    'account_id' => $account_id,
                                    'qty' => 1,
                                    'product_id' => $subscription_product,
                                    'status' => 'Enabled',
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'detail' => $phone_number,
                                    'provision_type' => 'phone_number',
                                    'bill_frequency' => 1,
                                    'subscription_id' => $sub_id,
                                ];
                                $activation_id = \DB::connection('default')->table('sub_activations')->insertGetId($activation_data);
                                module_log(554, $activation_id, 'number auto allocated for volume domain');
                                module_log(334, $sub_id, 'number auto allocated for volume domain');

                                $data = [];
                                $data['internal_function'] = 'wholesale_number_allocated';
                                $data['new_number'] = $phone_number;
                                erp_process_notification($volume_domain->account_id, $data);
                            }
                        }

                    }
                }
            }
        }
    }

    if (! $debug) {
        foreach ($gateways as $gateway) {
            $numbers_per_domain = $gateway->volume_numbers_required;
            foreach ($volume_domains as $volume_domain) {
                $allocated_count = \DB::connection('pbx')->table('p_phone_numbers')
                    ->where('status', 'Enabled')
                    ->where('domain_uuid', $volume_domain->domain_uuid)
                    ->where('gateway_uuid', $gateway->gateway_uuid)->count();
                if ($allocated_count < $numbers_per_domain) {
                    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->update(['enabled' => 'true']);
                    $volume_number_results[] = $gateway->gateway.' numbers not allocated to '.$volume_domain->domain_name.'. Gateway disabled.';
                }
            }
        }
    }

    return $volume_number_results;
}

function schedule_cdr_update_hangup_causes()
{
    $cc = \DB::connection('pbx_cdr')->table('call_records_outbound')->select('hangup_cause')->groupBy('hangup_cause')->pluck('hangup_cause')->filter()->toArray();
    foreach ($cc as $c) {
        $e = \DB::connection('pbx_cdr')->table('p_hangup_causes')->where('name', $c)->count();
        if (! $e) {
            \DB::connection('pbx_cdr')->table('p_hangup_causes')->insert(['name' => $c]);
        }
    }
}

function button_gateways_rescan($request)
{
    $pbx = new FusionPBX;
    sleep(1);
    $result = $pbx->portalCmd('portal_sip_profile_rescan', $request->profile);

    return json_alert($response);

    // $data = [];
    // if ($response == "Invalid Gateway!") {
    //     //not running
    //     $data['status'] = 'Stopped';
    //     $data['state'] = '';
    // } else {
    //     //running
    //     $data['status'] = 'Running';
    //     $data['state'] = $response;
    // }

    // \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update($data);

    // return json_alert($data['status']);
}

function button_pbx_sip_status($request)
{
    $sql = 'select g.domain_uuid, g.gateway, g.gateway_uuid, d.domain_name ';
    $sql .= 'from v_gateways as g left outer join v_domains as d on d.domain_uuid = g.domain_uuid';
    $gateways = \DB::connection('pbx')->select($sql);
    $pbx = new FusionPBX;

    $xml_response = $pbx->portalCmd('portal_sofia_status');
    try {
        $xml = new SimpleXMLElement($xml_response);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    $cmd = 'api sofia xmlstatus gateway';
    $xml_response = $pbx->portalCmd('portal_sofia_status_gateway');
    try {
        $xml_gateways = new SimpleXMLElement($xml_response);
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    echo "<div id='sofia_status' class='p-4'>";
    echo "<table class='table table-bordered' width='100%' cellspacing='0' border='0'>\n";
    echo "<tr>\n";
    echo "<th>Name</th>\n";
    echo "<th>Type</th>\n";
    echo "<th>Data</th>\n";
    echo "<th>State</th>\n";
    echo "</tr>\n";
    foreach ($xml->profile as $row) {
        echo "<tr>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->name)."</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->type)."</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->data)."</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->state)."</td>\n";
        echo "</tr>\n";
        if ($c == 0) {
            $c = 1;
        } else {
            $c = 0;
        }
    }
    foreach ($xml_gateways->gateway as $row) {
        $gateway_name = '';
        $gateway_domain_name = '';
        foreach ($gateways as $field) {
            if ($field->gateway_uuid == strtolower($row->name)) {
                $gateway_name = $field->gateway;
                $gateway_domain_name = $field->domain_name;
                break;
            }
        }
        echo "<tr>\n";
        echo "	<td class='".$row_style[$c]."'>";
        if ($_SESSION['domain_name'] == $gateway_domain_name) {
            echo escape($gateway_name).'@'.escape($gateway_domain_name);
        } elseif ($gateway_domain_name == '') {
            echo $gateway_name ? $gateway_name : $row->name;
        } else {
            echo $gateway_name.'@'.$gateway_domain_name;
        }
        echo "	</td>\n";
        echo "	<td class='".$row_style[$c]."'>Gateway</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->to)."</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->state)."</td>\n";
        echo "</tr>\n";
        if ($c == 0) {
            $c = 1;
        } else {
            $c = 0;
        }
    }
    foreach ($xml->alias as $row) {
        //print_r($row);
        echo "<tr>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->name)."</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->type)."</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->data)."</td>\n";
        echo "	<td class='".$row_style[$c]."'>".escape($row->state)."</td>\n";
        echo "</tr>\n";
        if ($c == 0) {
            $c = 1;
        } else {
            $c = 0;
        }
    }
    echo "</table>\n";

    $response = $pbx->portalCmd('portal_command', 'status');

    echo "<div id='status' style='margin-top: 20px; font-size: 9pt;'>";
    echo '<pre>';
    echo trim(escape($response));
    echo "</pre>\n";
    echo '</div>';
    echo "</div>\n";
}

/// PROFILES
function button_sip_profile_start($request)
{
    $conn = session('mod_conn');
    $profile_name = \DB::connection($conn)->table('v_sip_profiles')->where('sip_profile_uuid', $request->id)->pluck('sip_profile_name')->first();
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sip_profile_start', $profile_name);

    return json_alert($result, 'info');
}

function button_sip_profile_stop($request)
{
    $conn = session('mod_conn');
    $profile_name = \DB::connection($conn)->table('v_sip_profiles')->where('sip_profile_uuid', $request->id)->pluck('sip_profile_name')->first();
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sip_profile_stop', $profile_name);

    return json_alert($result, 'info');
}

function button_sip_profile_restart($request)
{
    $conn = session('mod_conn');
    $profile_name = \DB::connection($conn)->table('v_sip_profiles')->where('sip_profile_uuid', $request->id)->pluck('sip_profile_name')->first();
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sip_profile_restart', $profile_name);

    return json_alert($result, 'info');
}

function button_sip_profile_rescan($request)
{
    $conn = session('mod_conn');
    $profile_name = \DB::connection($conn)->table('v_sip_profiles')->where('sip_profile_uuid', $request->id)->pluck('sip_profile_name')->first();
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sip_profile_rescan', $profile_name);

    return json_alert($result, 'info');
}

function button_sip_profile_flush($request)
{
    $conn = session('mod_conn');
    $profile_name = \DB::connection($conn)->table('v_sip_profiles')->where('sip_profile_uuid', $request->id)->pluck('sip_profile_name')->first();
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_sip_profile_flush', $profile_name);

    return json_alert($result, 'info');
}

/// GATEWAYS

function button_gateways_restart($request)
{
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_gateway_stop', $request->id);
    aa($result);

    $result = $pbx->portalCmd('portal_gateway_start');
    aa($result);

    $response = $pbx->portalCmd('portal_gateway_status', $request->id);

    $data = [];
    if ($response == 'Invalid Gateway!') {
        //not running
        $data['status'] = 'Stopped';
        $data['state'] = '';
    } else {
        //running
        $data['status'] = 'Running';
        $data['state'] = $response;
    }

    aa($response);
    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update($data);
    sleep(2);
    pbx_update_gateways_status();
    aa($result);

    return json_alert($result, 'info');
}

function button_gateways_start($request)
{
    $pbx = new FusionPBX;

    $result = $pbx->portalCmd('portal_gateway_start');

    $response = $pbx->portalCmd('portal_gateway_status', $request->id);

    $data = [];
    if ($response == 'Invalid Gateway!') {
        //not running
        $data['status'] = 'Stopped';
        $data['state'] = '';
    } else {
        //running
        $data['status'] = 'Running';
        $data['state'] = $response;
    }

    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update($data);
    sleep(2);
    pbx_update_gateways_status();

    return json_alert($result, 'info');
}

function button_gateways_stop($request)
{
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_gateway_stop', $request->id);

    $response = $pbx->portalCmd('portal_gateway_status', $request->id);

    $data = [];
    if ($response == 'Invalid Gateway!') {
        //not running
        $data['status'] = 'Stopped';
        $data['state'] = '';
    } else {
        //running
        $data['status'] = 'Running';
        $data['state'] = $response;
    }

    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update($data);
    sleep(2);
    pbx_update_gateways_status();

    return json_alert($result, 'info');
}

function button_gateways_status($request)
{
    $pbx = new FusionPBX;
    $response = $pbx->portalCmd('portal_gateway_status', $request->id);

    $data = [];
    if ($response == 'Invalid Gateway!') {
        //not running
        $data['status'] = 'Stopped';
        $data['state'] = '';
    } else {
        //running
        $data['status'] = 'Running';
        $data['state'] = $response;
    }

    \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->id)->update($data);

    return json_alert($result, 'info');
}

function button_gateways_update_all_status($request)
{
    $pbx = new FusionPBX;
    \DB::connection('pbx')->table('v_gateways')->update(['status' => '', 'state' => '']);
    $gateways = \DB::connection('pbx')->table('v_gateways')->where('enabled', 'true')->get();

    foreach ($gateways as $gateway) {
        $data = [];
        $response = $pbx->portalCmd('portal_gateway_status', $gateway->gateway_uuid);

        if ($response == 'Invalid Gateway!') {
            //not running
            $data['status'] = 'Stopped';
            $data['state'] = '';
        } else {
            //running
            $data['status'] = 'Running';
            $data['state'] = $response;
        }

        \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway->gateway_uuid)->update($data);
    }

    return json_alert('Done');
}

function select_get_gateway_countries()
{
    $countries = \DB::connection('pbx')->table('p_rates_destinations')->groupby('country')->pluck('country')->toArray();
    $list = [];
    foreach ($countries as $c) {
        $list[$c] = $c;
    }

    return $list;
}

function select_options_local_destinations()
{
    $opt = [];
    $dest = \DB::connection('pbx')->table('p_rates_destinations')->groupby('destination')->pluck('destination')->toArray();
    foreach ($dest as $d) {
        if (str_starts_with($d, 'fixed ')) {
            $opt[] = $d;
        } elseif (str_starts_with($d, 'mobile ')) {
            $opt[] = $d;
        } elseif (str_contains($d, 'liquid')) {
            $opt[] = $d;
        } elseif (str_contains($d, 'multisource')) {
            $opt[] = $d;
        } elseif (str_contains($d, 'huge')) {
            $opt[] = $d;
        }
    }

    return $opt;
}
