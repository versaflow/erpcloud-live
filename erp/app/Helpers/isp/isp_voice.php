<?php

function schedule_low_asr_caller()
{
    \DB::connection('pbx')->table('mon_low_asr_caller')->update(['is_deleted'=>1]);
    // $sql = "UPDATE p_phone_numbers set blocked = 0 where blocked = 1";
    // $check = \DB::connection('pbx')->update($sql);

    $unanswered_call_count = 0;
    $answered_call_count = 0;
    $call_count = 0;
    $duration = 0;
    $previous_caller_id_number = '';
    $previous_destination = '';

    $data_from = date('Y-m-d H:i:s', strtotime('-6 hour'));
    $sql = "select distinct caller_id_number, id, ani, domain_name, destination, duration, hangup_cause, hangup_time, callee_id_number from call_records_outbound where 
    type = 'volume' and hangup_cause not like 'BLOCKED%' and (hangup_cause = 'NO_USER_RESPONSE' or hangup_cause <> 'CALL_REJECTED') and hangup_time > '".$data_from."' order by caller_id_number desc";
    $records = DB::connection('pbx_cdr')->select($sql);
    // aa($records);
    foreach ($records as $record) {
        if ($previous_caller_id_number == '') {
            $previous_caller_id_number = $record->caller_id_number;
        }

        if ($record->caller_id_number == $previous_caller_id_number) {
            $call_count = $call_count + 1;
            if ($record->duration < 20) {
                $unanswered_call_count = $unanswered_call_count + 1;
            } else {
                $answered_call_count = $answered_call_count + 1;
                $duration = $duration + $record->duration;
            }
        }

        if ($record->caller_id_number <> $previous_caller_id_number) {
            if ($call_count > 20) {
                // aa($call_count);
                $asr = currency(($answered_call_count / $call_count) * 100);
                // aa($asr);
                if ($answered_call_count > 0) {
                    $acd = currency($duration / $answered_call_count);
                } else {
                    $acd = 0;
                }
                // aa($acd);
                if ($asr < 10 or $acd < 10) {
                    $data = [
                        'domain_name' => $previous_domain_name,
                        'ani' => $previous_ani,
                        'destination' => $previous_destination,
                        'hangup_cause' => $previous_hangup_cause,
                        'time' => $previous_hangup_time,
                        'caller_id_number' => $previous_caller_id_number,
                        'callee_id_number' => $previous_callee_id_number,
                        'asr' => (integer) $asr,
                        'acd' => (integer) $acd,
                        'num_calls' => $call_count,
                        'num_answered_calls' => $answered_call_count,
                    ];
                    $check = \DB::connection('pbx')->table('mon_low_asr_caller')->insertGetId($data);
                    aa($check);

                    // $sql = "UPDATE p_phone_numbers set blocked = 1 where number = '". $record->caller_id_number ."'";
                    // $check = \DB::connection('pbx')->update($sql);
                }
                $unanswered_call_count = 0;
                $answered_call_count = 0;
                $call_count = 0;
                $duration = 0;
            }
        }

        $previous_callee_id_number = $record->callee_id_number;
        $previous_caller_id_number = $record->caller_id_number;
        $previous_destination = $record->destination;
        $previous_hangup_cause = $record->hangup_cause;
        $previous_hangup_time = $record->hangup_time;
        $previous_ani = $record->ani;
        $previous_domain_name = $record->domain_name;
    }
    
}


function schedule_server_monitor_disks(){
    $disk_percentage_threshold = '90';
    $cmd = "df -h | grep -v tmpfs | awk '$5 > ".$disk_percentage_threshold." {print}'";
    $host1_result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    $host1_result_arr = collect(explode(PHP_EOL,$host1_result))->filter()->toArray();
    $host2_result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $host2_result_arr = collect(explode(PHP_EOL,$host2_result))->filter()->toArray();
    $pbx_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $pbx_result_arr = collect(explode(PHP_EOL,$pbx_result))->filter()->toArray();
    $msg = '';
    if(count($host1_result_arr) > 1){
        $msg .= 'HOST1 Disk usage: '.PHP_EOL.$host1_result.PHP_EOL;
    }
    if(count($host2_result_arr) > 1){
        $msg .= 'HOST2 Disk usage: '.PHP_EOL.$host2_result.PHP_EOL;
    }
    if(count($pbx_result_arr) > 1){
        $msg .= 'PBX Disk usage: '.PHP_EOL.$pbx_result.PHP_EOL;
    }
    if($msg > ''){
        admin_email('Server disk space above '.$disk_percentage_threshold.'%',$msg);   
    }
}


function schedule_zero_registrations_notification()
{
    $domains = \DB::connection('pbx')->table('v_domains')->where('account_id','>',0)->where('cost_calculation','!=','volume')->get();
    foreach($domains as $domain){
        
        $total_exts = \DB::connection('pbx')->table('v_extensions')
        ->where('domain_uuid',$domain->domain_uuid)
        ->count();
        
        $registered_exts = \DB::connection('pbx')->table('v_extensions')
        ->where('domain_uuid',$domain->domain_uuid)
        ->where('registration_status','>','')->count();
        
        $cidr_exts = \DB::connection('pbx')->table('v_extensions')
        ->where('domain_uuid',$domain->domain_uuid)
        ->where('cidr','>','')->count();
        
        $registered_in_week_exts = \DB::connection('pbx')->table('v_extensions')
        ->where('domain_uuid',$domain->domain_uuid)
        ->where('last_register_time','>',date('Y-m-d H:i:s',strtotime('-1 week')))->count();
        if($cidr_exts > 0 || $registered_exts > 0 || $registered_in_week_exts > 0 || $total_exts == 0){
            continue;
        }
        
        $data = [];
        $data['domain_name'] = $domain->domain_name;
        $data['domain_company'] = $domain->company;
        
        $send_to_id = $domain->account_id;
        if($domain->partner_id!=1){
            $send_to_id = $domain->partner_id;
        }
        $data['internal_function'] = 'schedule_zero_registrations_notification';
       
        erp_process_notification($send_to_id,$data);
    }
}

function schedule_update_pbx_domains_airtime_subscription_process(){
    $domains = \DB::connection('default')->table('isp_voice_pbx_domains')->get();
    $provision_types = ['unlimited_channel','phone_number','pbx_extension','pbx_extension_recording','sip_trunk' ,'airtime_prepaid','airtime_unlimited','airtime_contract'];
    foreach($domains as $domain){
        \DB::table('sub_services')->whereIn('provision_type', $provision_types)->where('account_id',$domain->account_id)->update(['pbx_domain'=>$domain->pabx_domain]);  
    }
    $airtime_contract_ids = get_activation_type_product_ids('airtime_contract');
    $airtime_channel_ids = get_activation_type_product_ids('unlimited_channel');

    $product_ids = array_merge($airtime_channel_ids,$airtime_contract_ids);
    $pbx_domains = \DB::table('sub_services')->where('status','!=','Deleted')->whereIn('product_id',$product_ids)->pluck('pbx_domain')->toArray();
    \DB::connection('pbx')->table('v_domains')->whereNotIn('domain_name',$pbx_domains)->update(['has_airtime_subscription'=>0]);
    \DB::connection('pbx')->table('v_domains')->whereIn('domain_name',$pbx_domains)->update(['has_airtime_subscription'=>1]);
    $phone_number_domains = \DB::connection('pbx')->table('p_phone_numbers')->where('status','!=','Deleted')->pluck('domain_uuid')->unique()->toArray();
    \DB::connection('pbx')->table('v_domains')->whereNotIn('domain_uuid',$phone_number_domains)->update(['has_phone_number'=>0]);
    \DB::connection('pbx')->table('v_domains')->whereIn('domain_uuid',$phone_number_domains)->update(['has_phone_number'=>1]);
}

function schedule_registrations_update()
{
    $registrations = \DB::connection('freeswitch')->table('registrations')->get();
    $domains = \DB::connection('pbx')->table('v_domains')->get();
   
    \DB::connection('pbx')->table('v_extensions')->update(['registration_status' => '','network_ip' => '']);
    
    $location_data = [];
    foreach ($registrations as $row) {
        $domain_uuid = $domains->where('domain_name',$row->realm)->pluck('domain_uuid')->first();
        $data = ['registration_status' => 'Registered '.$row->network_proto];
        $data['last_register_time'] = date('Y-m-d H:i:s');
        try {
            if (!empty($row->network_ip)) {
                if (isset($location_data[$row->network_ip])) {
                    $geolocation = $location_data[$row->network_ip];
                } else {
                    $geolocation = Location::get($row->network_ip);
                    $location_data[$row->network_ip] = $geolocation;
                }
                $data['network_ip'] = $row->network_ip;
                $data['geolocation_address'] = $geolocation->cityName.', '.$geolocation->regionName.', '.$geolocation->countryName.', '.$geolocation->zipCode;
                $data['geolocation_latitude'] = $geolocation->latitude;
                $data['geolocation_longitude'] = $geolocation->longitude;
            }
        } catch (\Throwable $ex) {  
            exception_log($ex);
        }
    
        \DB::connection('pbx')->table('v_extensions')->where('extension', $row->reg_user)->where('domain_uuid', $domain_uuid)->update($data);
    
    }
    
    $sql = "UPDATE v_domains
    SET active_registrations = (
    SELECT COUNT(e.registration_status) AS active_registrations
    FROM v_extensions e
    WHERE e.domain_uuid = v_domains.domain_uuid
    AND e.registration_status <> ''
    );";
    \DB::connection('pbx')->statement($sql);
}



function schedule_registrations_update_sqllite()
{
    // sql lite version
    return false;
    //
    $pbx = new FusionPBX();
    $results = $pbx->portalCmd('portal_registrations');


    if (str_contains($results, 'Fatal error')) {
        throw new \ErrorException("Portal.php error");
    }
    $results = json_decode($results, true);
    if (!empty($results) && is_array($results) && count($results) > 0) {
        foreach ($results as $i => $row) {
            $results[$i]['domain_uuid'] = \DB::connection('pbx')->table('v_domains')->where('domain_name', $row['sip_host'])->pluck('domain_uuid')->first();
        }
    }

    $cols = get_columns_from_schema('mon_registrations', null, 'pbx');


    if (!empty($results)) {
        \DB::connection('pbx')->table('mon_registrations')->truncate();
        foreach ($results as $result) {
            $data = [];
            foreach ($result as $key => $val) {
                if (in_array($key, $cols)) {
                    $data[$key] = $val;
                }
            }

            \DB::connection('pbx')->table('mon_registrations')->insert($data);
        }
    }

    if (!empty($results)) {
        \DB::connection('pbx')->table('v_extensions')->update(['registration_status' => '']);

        $location_data = [];
        foreach ($results as $row) {
            $data = [];
         
            $data['registration_status'] = $row['status'];
            if(str_contains($row['status'],'Registered')){
            $data['last_registration_time'] = date('Y-m-d H:i:s');
            }
            try {
                if (!empty($row->network_ip)) {
                    if (isset($location_data[$row->network_ip])) {
                        $geolocation = $location_data[$row->network_ip];
                    } else {
                        $geolocation = Location::get($row->network_ip);
                        $location_data[$row->network_ip] = $geolocation;
                    }
                    $data['network_ip'] = $row->network_ip;
                    $data['geolocation_address'] = $geolocation->cityName.', '.$geolocation->regionName.', '.$geolocation->countryName.', '.$geolocation->zipCode;
                    $data['geolocation_latitude'] = $geolocation->latitude;
                    $data['geolocation_longitude'] = $geolocation->longitude;
                }
            } catch (\Throwable $ex) {  exception_log($ex);
            }

            \DB::connection('pbx')->table('v_extensions')->where('extension', $row['sip_user'])->where('domain_uuid', $row['domain_uuid'])->update($data);
        }
    }
}


function schedule_servers_pbx_clear_logs(){
   
    if(!is_main_instance()){
        return false;
    }
    $cmd = "rm /var/log/fail2ban.log.* -Rf";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
    $cmd = "cat > /var/log/fail2ban.log";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
}






function schedule_daily_pbx_disk_usage(){
    
	$cmd = "df /home 2>&1";
	$tmp = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

	$tmp = explode("\n", $tmp);
	$tmp = preg_replace('!\s+!', ' ', $tmp[1]); // multiple > single space
	$tmp = explode(' ', $tmp);

	$percent_disk_usage = '';
	foreach ($tmp as $stat) {
		if (substr_count($stat, '%') > 0) { $percent_disk_usage = rtrim($stat,'%'); break; }
	}

	
	$role_id = 1;
    
   
  
    if($percent_disk_usage > 80){
      
	
        $role_id = 1;
        
     
        $data = [
            'name' => 'PBX disk usage above 80',
            'details' => 'Current usage: '.$percent_disk_usage,
            'user_id' => 1,
            'role_id' => 1,
            'result' => 1,
            'progress_status' => 'Not Done',
            'type' => 'Task'
        ];
        dbinsert('crm_staff_tasks',$data);
        
    }
}

function schedule_pbx_clear_cache(){
    $cmd = 'rm -rf /var/www/cache/*';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
}

function button_pbx_clear_cache(){
    $cmd = 'rm -rf /var/www/cache/*';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    return json_alert('Cache Deleted');
}


function schedule_low_asr_callee()
{
    \DB::connection('pbx')->table('mon_low_asr_callee')->update(['is_deleted'=>1]);
    // $sql = "UPDATE p_phone_numbers set blocked = 0 where blocked = 1";
    // $check = \DB::connection('pbx')->update($sql);

    $unanswered_call_count = 0;
    $answered_call_count = 0;
    $call_count = 0;
    $duration = 0;
    $previous_caller_id_number = '';
    $previous_destination = '';

    $data_from = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $sql = "select distinct caller_id_number, id, ani, domain_name, destination, duration, hangup_cause, callee_id_number from call_records_outbound where 
    type = 'volume' and hangup_cause not like '%BLOCKED%' and hangup_cause <> 'SWITCH_CONGESTION' and hangup_cause <> 'NO_USER_RESPONSE' and hangup_cause <> 'NORMAL_CLEARING' and hangup_cause <> 'ORIGINATOR_CANCEL' and hangup_cause not like '%NORMAL_TEMPORARY_FAILURE%' and hangup_cause <> 'CALL_REJECTED' and hangup_cause <> 'LOW ASR CALLEE' and hangup_time > '".$data_from."' order by callee_id_number desc";
    $records = DB::connection('pbx_cdr')->select($sql);
    foreach ($records as $record) {
        if ($previous_callee_id_number == '') {
            $previous_callee_id_number = $record->callee_id_number;
        }

        if ($record->callee_id_number == $previous_callee_id_number) {
            $call_count = $call_count + 1;
            if ($record->duration < 20) {
                $unanswered_call_count = $unanswered_call_count + 1;
            } else {
                $answered_call_count = $answered_call_count + 1;
                $duration = $duration + $record->duration;
            }
        }

        if ($record->callee_id_number <> $previous_callee_id_number) {
            if ($call_count > 50) {
                $asr = currency($answered_call_count / $call_count * 100);

                if ($answered_call_count > 0) {
                    $acd = currency($duration / $answered_call_count);
                } else {
                    $acd = 0;
                }

                if ($asr < 10 or $acd < 10) {
                    $data = [
                        'domain_name' => $previous_domain_name,
                        'ani' => $previous_ani,
                        'destination' => $previous_destination,
                        'hangup_cause' => $previous_hangup_cause,
                        'caller_id_number' => $previous_caller_id_number,
                        'callee_id_number' => $previous_callee_id_number,
                        'asr' => (integer)$asr,
                        'acd' => (integer)$acd,
                        'num_calls' => $call_count,
                        'num_answered_calls' => $answered_call_count,
                    ];
                    $check = \DB::connection('pbx')->table('mon_low_asr_callee')->insertGetId($data);

                    // $sql = "UPDATE p_phone_numbers set blocked = 1 where number = '". $record->caller_id_number ."'";
                    // $check = \DB::connection('pbx')->update($sql);
                }
                $unanswered_call_count = 0;
                $answered_call_count = 0;
                $call_count = 0;
                $duration = 0;
            }
        }

        $previous_callee_id_number = $record->callee_id_number;
        $previous_caller_id_number = $record->caller_id_number;
        $previous_destination = $record->destination;
        $previous_hangup_cause = $record->hangup_cause;
        $previous_ani = $record->ani;
        $previous_domain_name = $record->domain_name;
    }
}

function aftersave_pbx_domain_call_forwarding($request)
{   
    if (session('role_level') == 'Admin') {
        if(!empty($request->call_forward_number) && !empty($request->domain_uuid)){
           
            if($request->call_forward_all ||
            $request->call_forward_on_busy ||
            $request->call_forward_no_answer ||
            $request->call_forward_not_registered){
                $data = [
                    'forward_all_enabled' => ($request->call_forward_all) ? 'true' : 'false',
                    'forward_user_not_registered_enabled' => ($request->call_forward_not_registered) ? 'true' : 'false',
                    'forward_no_answer_enabled' => ($request->call_forward_no_answer) ? 'true' : 'false',
                    'forward_busy_enabled' => ($request->call_forward_on_busy) ? 'true' : 'false',
                    'forward_all_destination' => $request->call_forward_number,
                    'forward_user_not_registered_destination' => $request->call_forward_number,
                    'forward_busy_destination' => $request->call_forward_number,
                    'forward_no_answer_destination' => $request->call_forward_number,
                ];
               
                \DB::connection('pbx')->table('v_extensions')->where('domain_uuid',$request->domain_uuid)->update($data);
            }
        }    
        
    }
}

function aftersave_pbx_domain_rename($request)
{
    if (check_access('1')) {
        $beforesave_row = session('event_db_record');
        if ($beforesave_row->domain_name != $request->domain_name) {
            \DB::table('isp_voice_pbx_domains')->where('pabx_domain', $beforesave_row->domain_name)->update(['pabx_domain' => $request->domain_name]);
            $ix = new Interworx();
            $dns_records = $ix->getPbxDnsRecords();
            $dns_records = collect($dns_records)->pluck('host')->toArray();

            if (!in_array($request->domain_name, $dns_records)) {
                $result = $ix->addPbxDns($request->domain_name);
            }

            // $result = $ix->deletePbxDnsRecord($beforesave_row->domain_name);
        }
    }
}

function aftersave_pbx_domain_set_balance_notification($request)
{
    \DB::connection('pbx')->table('v_domains')->where('unlimited_channels', '>', 0)->update(['balance_notification'=>'None']);
}



function aftersave_pbx_domain_check_cost_calculation($request)
{
    $beforesave_row = session('event_db_record');
    if($beforesave_row && $beforesave_row->cost_calculation != $request->cost_calculation && $request->cost_calculation == 'volume'){
        $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('id',$request->id)->pluck('domain_uuid')->first();
        \DB::connection('pbx')->table('v_voicemails')->where('domain_uuid',$domain_uuid)->delete();
        \DB::connection('pbx')->table('v_extensions')->where('domain_uuid',$domain_uuid)->update(['outbound_caller_id_number'=>'']);
        $exts = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid',$domain_uuid)->get();
        $pbx = new FusionPBX();
        foreach($exts as $ext){
            $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
            $result = $pbx->portalCmd('portal_aftersave_extension', $key);
        
            if (!empty($ext->cidr)) {
                $pbx->portalCmd('portal_reloadacl');
            }
        }
    }
}


function onload_pbx_panel_set_partner()
{
    $partners = \DB::table('crm_accounts')->where('type', 'reseller')->get();
    foreach ($partners as $reseller) {
        \DB::connection('pbx')->table('v_domains')->where('partner_id', $reseller->id)->update(['partner_company'=>$reseller->company]);
    }
}

function button_update_registration_status($request){
    schedule_registrations_update();
    return json_alert('Done');
}


function onload_active_calls_update()
{
    \DB::connection('pbx')->table('mon_active_calls')->truncate();
    $pbx = new FusionPBX();
    $results = $pbx->portalCmd('portal_active_calls');

    $results = json_decode($results, true);
    if (!empty($results) && is_array($results) && count($results) > 0) {
        foreach ($results as $i => $row) {
            $results[$i]['domain_uuid'] = \DB::connection('pbx')->table('v_domains')->where('domain_name', $row['accountcode'])->pluck('domain_uuid')->first();
        }

        \DB::connection('pbx')->table('mon_active_calls')->insert($results);
    }
}

function schedule_asr_acd_stats()
{
    $domains = \DB::connection('pbx')->table('v_domains')->select('domain_name')->pluck('domain_name')->toArray();

    foreach ($domains as $domain) {
        $destinations =   $call_stats = \DB::connection('pbx_cdr')
            ->table('call_records_outbound')
            ->where('domain_name', $domain)->select('destination')->groupBy('destination')->pluck('destination')->toArray();
        foreach ($destinations as $destination) {
            $call_stats = \DB::connection('pbx_cdr')
                ->table('call_records_outbound')
                ->where('domain_name', $domain)
                ->where('destination', $destination)
                ->where('duration', '>', 0)
                ->where('hangup_cause', 'NOT LIKE', "BLOCKED%")
                ->where('hangup_cause', '!=', "CALL_REJECTED")
                ->select(\DB::raw("sum(duration) as total_duration,(SUM(duration)/count(id)) as average_call_duration,count(id) as total_answered_calls"))
                ->get()
                ->first();

            $call_count = \DB::connection('pbx_cdr')
                ->table('call_records_outbound')
                ->where('destination', $destination)
                ->where('domain_name', $domain)
                ->count();


            $answer_seizure_ratio = 0;
            if ($call_stats->total_answered_calls > 0 && $call_count > 0) {
                $answer_seizure_ratio = currency(($call_stats->total_answered_calls / $call_count) * 100);
            }

            $data = [
                'answer_seizure_ratio' => $answer_seizure_ratio,
                'average_call_duration' => $call_stats->average_call_duration,
                'total_calls' => $call_count,
                'total_answered_calls' => $call_stats->total_answered_calls,
                'total_duration' => $call_stats->total_duration,
                'domain_name' => $domain,
                'destination' => $destination,
                'period' => date('Y-m'),
            ];

            foreach ($data as $k => $v) {
                if (empty($v)) {
                    $data[$k] = 0;
                }
            }

            $c = \DB::connection('pbx')->table('mon_asr_acd_stats')->where(['domain_name' => $domain,'destination' => $destination,'period' => date('Y-m')])->count();
            if (!$c) {
                \DB::connection('pbx')->table('mon_asr_acd_stats')->insert($data);
            } else {
                \DB::connection('pbx')->table('mon_asr_acd_stats')->where(['domain_name' => $domain,'destination' => $destination,'period' => date('Y-m')])->update($data);
            }
        }
    }
}

function button_call_records_details($request)
{
   // aa($request->all());
    $cdr = \DB::connection('pbx_cdr')->table('call_records_outbound_variables')->where('call_records_outbound_id', $request->id)->get()->first();
    if(empty($cdr) || empty($cdr->variables)){
        return json_alert('CDR variables not saved','error');    
    }
    foreach($cdr as $k => $v){
        $cdr->{$k} = str_replace('"Forbidden"",','Forbidden",',$v);  
       
    }
    
    $data = [
        'variables' => json_encode(json_decode($cdr->variables), JSON_PRETTY_PRINT),
    ];
    foreach($data as $k => $v){
        if($v == null || $v == "null"){
            $data[$k] = $cdr->{$k};    
        }    
    }
  $data['menu_name'] = 'Variables';
    return view('__app.button_views.cdr_details', $data);
   
}



function schedule_update_extension_register_time()
{
    $registrations = \DB::connection('pbx')->table('mon_registrations')->get();
    foreach ($registrations as $registration) {
        $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $registration->sip_realm)->pluck('domain_uuid')->first();
        \DB::connection('pbx')->table('v_extensions')
            ->where('domain_uuid', $domain_uuid)
            ->where('extension', $registration->reg_user)
            ->update(['last_register_time' => date('Y-m-d H:i:s')]);
    }
}

function schedule_rejected_history_clear()
{
    \DB::connection('pbx')->table('mon_rejected_7_seconds')->where('duration', 7)->delete();
}

function schedule_freeswitch_sync_clock(){

    $cmd = "/usr/local/freeswitch/bin/fs_cli -x 'fsctl sync_clock'";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
}

function schedule_freeswitch_weekly_remove_logs(){

  $cmd = "rm /var/log/freeswitch/freeswitch.log* -Rf  ";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
}

function schedule_servers_reboot_pbx_check(){
    
    
    if(!is_main_instance()){
        return false;
    }
    $cmd = "uptime -s";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $last_boot = date("Y-m-d",strtotime(trim(str_replace("\n","",$result))));
   
    if($last_boot != date('Y-m-d')){
        admin_email("PBX server did not reboot: last reboot - ".$last_boot);    
        //queue_sms(12, '0824119555', "PBX server did not reboot: last reboot - ".$last_boot, 1, 1);
    }
}

function schedule_servers_reboot_host2()
{
    
    
    if(!is_main_instance()){
        return false;
    }
    // reboot freeswitch
    //$cmd = "/usr/bin/fs_cli -p Ahmed786 -x 'fsctl shutdown restart' ";
    //$result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
    //reboot pbx
    // $cmd = "reboot";
    // $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    // reboot host2
    $cmd = "reboot";
    $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

}


function schedule_servers_reboot_host1()
{
    
    
    if(!is_main_instance()){
        return false;
    }
    // reboot freeswitch
    //$cmd = "/usr/bin/fs_cli -p Ahmed786 -x 'fsctl shutdown restart' ";
    //$result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
    //reboot pbx
    // $cmd = "reboot";
    // $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

  
    $cmd = "reboot";
    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);

}

function schedule_monitor_cdr()
{
    $cmd = 'grep -w "CDR Exception" /var/www/html/debug.log ';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    if (!empty($result)) {
        //  queue_sms(12,'0824119555','CDR Error exception, please check debug log.');
     //   queue_sms(12,'0646839468','CDR Error exception, please check debug log.');
    }
}

function button_populate_rejected_history()
{
    // \DB::connection('pbx')->table('mon_rejected')->where('destination', 'mobile vodacom')->delete();
    // \DB::connection('pbx')->table('mon_rejected')->where('destination', 'mobile cellc')->delete();
    // \DB::connection('pbx')->table('mon_rejected')->where('destination', 'fixed other')->delete();
    // $sql = "UPDATE p_phone_numbers set vodacom_rejected = 0 where vodacom_rejected = 0";
    // $check = \DB::connection('pbx')->update($sql);
    // $sql = "UPDATE p_phone_numbers set cellc_rejected = 0 where cellc_rejected = 0";
    // $check = \DB::connection('pbx')->update($sql);

    $asr_msg = '';
    // $data_from = date('Y-m-d H:i:s', strtotime('-30 day'));
    $data_from = '2020-08-20';
    $sql = 'select caller_id_number, id, type, hangup_time, callee_id_number, ani, ani_source, gateway, domain_name, destination, duration, hangup_cause from call_records_outbound 
    where type = "wholesale" and destination = "fixed telkom" and hangup_cause in ("CALL_REJECTED") and hangup_time > "'.$data_from.'" group by caller_id_number order by hangup_time desc';
    $records = DB::connection('pbx_cdr')->select($sql);
    foreach ($records as $record) {
        $sql = "INSERT INTO mon_rejected set gateway = '". $record->gateway ."', domain_name = '". $record->domain_name ."', hangup_time = '". $record->hangup_time ."', hangup_cause = '". $record->hangup_cause ."', caller_id_number = '". $record->caller_id_number ."', callee_id_number = '". $record->callee_id_number ."', destination = '". $record->destination ."', ani = '". $record->ani ."', ani_source = '". $record->ani_source ."'";
        DB::connection('pbx')->insert($sql);
    }
}

function schedule_update_blocked_calls()
{
    $sql = "UPDATE v_domains set vodacom_rejected = 0, mtn_rejected = 0, telkom_rejected = 0, cellc_rejected = 0";
    $check = \DB::connection('pbx')->update($sql);

    $sql = "select count(id) as counter, caller_id_number, destination, domain_name from mon_rejected where destination in ('fixed telkom','fixed liquid','mobile cellc','mobile mtn','mobile vodacom','mobile telkom') group by domain_name";
    $records = DB::connection('pbx')->select($sql);
    foreach ($records as $record) {
        $sql = "select count(distinct caller_id_number) as counter, caller_id_number, destination, domain_name from mon_rejected where domain_name = '". $record->domain_name ."' and destination in ('fixed telkom','fixed liquid','mobile cellc','mobile mtn','mobile vodacom') group by destination";
        $destinations = DB::connection('pbx')->select($sql);
        foreach ($destinations as $destination) {
            $rejected_field = str_replace('mobile ', '', $destination->destination);
            $rejected_field = str_replace('fixed ', '', $rejected_field).'_rejected';
            $sql = "UPDATE v_domains set ".$rejected_field." = ".$destination->counter." where domain_name = '". $destination->domain_name ."'";
            $check = \DB::connection('pbx')->update($sql);
        }
    }
}

function button_callerid_ani_create_network_complaints($request)
{
    $row = \DB::connection('pbx')->table('mon_rejected')->where('id', $request->id)->get()->first();

    $records = \DB::connection('pbx')->table('mon_rejected')->where('domain_name', $row->domain_name)->get();
    foreach ($records as $record) {
        $data =[
            'ani' => $record->ani,
            'destination' => $record->destination,
            'source_network' => $record->source_network,
        ];
        \DB::connection('pbx')->table('mon_network_complaint')->insert($data);
    }
    \DB::connection('pbx')->table('mon_rejected')->where('domain_name', $row->domain_name)->update(['status' => 'Deleted']);

    return json_alert('Network complaints created.');
}

function button_refresh_blocked_calls($request)
{
    schedule_update_blocked_calls();
    return json_alert('Done');
}

function button_allocate_wholesale_numbers($request)
{
    schedule_allocate_wholesale_numbers();
    return json_alert('Done');
}



function grid_add_local_rates($rows)
{
    $formatted_rows = [];
    if (isset(request()->skip) && request()->skip === 0 && (session('pbx_domain_level') === true || check_access('21'))) {
        // add local rates on first page
        $local_rates = \DB::connection(session('pbx_server'))->table('p_rates_partner_items')->where('ratesheet_id', session('pbx_ratesheet_id'))->get();
        foreach ($local_rates as $local_rate) {
            $data = (object) [
                'id' => '27',
                'destination' => $local_rate->destination,
                'admin_rate' => 0,
                'wholesale_rate' => 0,
                'retail_rate' => $local_rate->rate,
                'admin_rate_usd' => 0,
                'wholesale_rate_usd' => 0,
                'retail_rate_usd' => 0,
            ];
            $formatted_rows[] = $data;
        }
    }
    if (count($formatted_rows) > 0) {
        $rows = collect($rows);
        $formatted_rows = collect($formatted_rows);
        $rows = $formatted_rows->merge($rows);
    }
    return $rows;
}

function get_pbx_connection($domain_name)
{
    return 'pbx';
}

function set_airtime_balances_from_pbx()
{
    if(is_main_instance()){
        $app_ids = get_installed_app_ids();

        $balances = \DB::connection('pbx')->table('v_domains')->get();
        foreach ($balances as $b) {
            \DB::connection($b->erp)->table('isp_voice_pbx_domains')->where('account_id', $b->account_id)->update(['pbx_balance'=>$b->balance]);
        }
    }
    
}

function schedule_set_airtime_balances_from_pbx(){
    set_airtime_balances_from_pbx();
}


function button_cdrstatistics_view_cdr($request)
{
    $pbx = new FusionPBX();
    $pbx->pbx_login();
    $cdr_stat = \DB::table('isp_voice_cdr_stats')->where('id', $request->id)->get()->first();

    $menu_name = get_menu_url_from_table('call_records_outbound');
    return redirect()->to($menu_name.'?domain_name='.urlencode($cdr_stat->domain_name).'&destination='.urlencode($cdr_stat->destination));
}

function button_update_least_cost_routes($request)
{
    update_least_cost_routes();
    return json_alert('Done');
}

function update_least_cost_routes()
{
    pbxdelete('v_least_cost_route', 'id', 1);
    $gateways = \DB::connection('pbx')->select("SELECT * FROM v_gateways where gateway = 'SESSION'");
    foreach ($gateways as $gateway) {
        $data['id'] = 1; //ITR
        $data['rate_fixed_telkom'] = $gateway->rate_fixed_telkom;
        $data['gateway_fixed_telkom'] = $gateway->gateway;
        $data['rate_fixed_liquid'] = $gateway->rate_fixed_liquid;
        $data['gateway_fixed_liquid'] = $gateway->gateway;
        $data['rate_mobile_vodacom'] = $gateway->rate_mobile_vodacom;
        $data['gateway_mobile_vodacom'] = $gateway->gateway;
        $data['rate_mobile_mtn'] = $gateway->rate_mobile_mtn;
        $data['gateway_mobile_mtn'] = $gateway->gateway;
        $data['rate_mobile_cellc'] = $gateway->rate_mobile_cellc;
        $data['gateway_mobile_cellc'] = $gateway->gateway;
        $data['rate_mobile_telkom'] = $gateway->rate_mobile_telkom;
        $data['gateway_mobile_telkom'] = $gateway->gateway;
    }
    pbxinsert('v_least_cost_route', $data);

    unset($data);
    pbxdelete('v_least_cost_route', 'id', 2);
    $gateways = \DB::connection('pbx')->select("SELECT * FROM v_gateways where enabled = 'true'");
    foreach ($gateways as $gateway) {
        $data['id'] = 2;
        if ($gateway->rate_fixed_telkom > 0 and ($data['rate_fixed_telkom'] == null or $data['rate_fixed_telkom'] > $gateway->rate_fixed_telkom)) {
            $data['rate_fixed_telkom'] = $gateway->rate_fixed_telkom;
            $data['gateway_fixed_telkom'] = "VOX"; //TEST
        }
        if ($gateway->rate_fixed_liquid > 0 and ($data['rate_fixed_liquid'] == null or $data['rate_fixed_liquid'] > $gateway->rate_fixed_liquid)) {
            $data['rate_fixed_liquid'] = $gateway->rate_fixed_liquid;
            $data['gateway_fixed_liquid'] = "VOX"; //TEST
        }
        if ($gateway->rate_mobile_vodacom > 0 and ($data['rate_mobile_vodacom'] == null or $data['rate_mobile_vodacom'] > $gateway->rate_mobile_vodacom)) {
            $data['rate_mobile_vodacom'] = $gateway->rate_mobile_vodacom;
            $data['gateway_mobile_vodacom'] = "VOX"; //TEST
        }
        if ($gateway->rate_mobile_mtn > 0 and ($data['rate_mobile_mtn'] == null or $data['rate_mobile_mtn'] > $gateway->rate_mobile_mtn)) {
            $data['rate_mobile_mtn'] = $gateway->rate_mobile_mtn;
            $data['gateway_mobile_mtn'] = "VOX"; //TEST
        }
        if ($gateway->rate_mobile_cellc > 0 and ($data['rate_mobile_cellc'] == null or $data['rate_mobile_cellc'] > $gateway->rate_mobile_cellc)) {
            $data['rate_mobile_cellc'] = $gateway->rate_mobile_cellc;
            $data['gateway_mobile_cellc'] = "VOX"; //TEST
        }
        if ($gateway->rate_mobile_telkom > 0 and ($data['rate_mobile_telkom'] == null or $data['rate_mobile_telkom'] > $gateway->rate_mobile_telkom)) {
            $data['rate_mobile_telkom'] = $gateway->rate_mobile_telkom;
            $data['gateway_mobile_telkom'] = "VOX"; //TEST
        }
    }
    pbxinsert('v_least_cost_route', $data);
    //echo "Done";
}

function button_call_records_outbound_lastmonth_recording($request)
{
    $recording_file = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->where('id', $request->id)->where('duration', '>', 0)->pluck('recording_file')->first();
    if (empty($recording_file)) {
        return 'Recording file not found';
    }
    $url = url('http://156.0.96.60/recordings/'.str_replace('/var/lib/freeswitch/', '', $recording_file));
    return redirect()->to($url);
}

function button_call_records_inbound_recording($request)
{
    $recording_file = \DB::connection('pbx_cdr')->table('call_records_inbound')->where('id', $request->id)->where('duration', '>', 0)->pluck('recording_file')->first();
    if (empty($recording_file)) {
        return 'Recording file not found';
    }
    $url = url('http://156.0.96.60/recordings/'.str_replace('/var/lib/freeswitch/', '', $recording_file));
    return redirect()->to($url);
}

function button_call_records_recording($request)
{
    $recording_file = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('id', $request->id)->where('duration', '>', 0)->pluck('recording_file')->first();
    if (empty($recording_file)) {
        return 'Recording file not found';
    }
    $url = url('http://156.0.96.60/recordings/'.str_replace('/var/lib/freeswitch/', '', $recording_file));
    return redirect()->to($url);
    // cant load http on https
    /*
    $filename_arr = explode('/',$recording_file);
    $filename = end($filename_arr);
    $ext_arr = explode('.',$filename);
    $ext = end($ext_arr);

   echo ' <script>
    audiojs.events.ready(function() {
    var as = audiojs.createAll();
    });
    </script>
   */
}

function get_allocated_phone_numbers($row)
{
    $row = (object) $row;
 
    if (!empty($row) && !empty($row->domain_uuid)) {
        $numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $row->domain_uuid)->pluck('number')->toArray();
    }

    $options = [];

    if (!empty($numbers) && count($numbers) > 0) {
        foreach ($numbers as $n) {
            $options[$n] = $n;
        }
    }

    return $options;
}

function button_voice_restart_fail2ban($request)
{
    $cmd = "service fail2ban restart";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    return json_alert('Command sent to pbx, output: '.$result);
}

function button_call_records_inbound_details($request)
{   
   
    
    $cdr = \DB::connection('pbx_cdr')->table('call_records_inbound_variables')->where('call_records_inbound_id', $request->id)->get()->first();
  
    
  
    if(empty($cdr) || empty($cdr->variables)){
        return json_alert('CDR variables not saved','error');    
    }
   
 
    foreach($cdr as $k => $v){
        $v = str_replace('"Forbidden"",','Forbidden",',$v); 
        $v = str_replace('.mp3",record','.mp3,record',$v);  
        $v = str_replace('answer%3D"uuid','answer%3Duuid',$v);
        $v = str_replace('_path%3D"','_path%3D',$v); 
        $v = str_replace('",record_n',',record_n',$v);
        $cdr->{$k} = $v;
    }
    
  
    $data = [
        'variables' => json_encode(json_decode($cdr->variables), JSON_PRETTY_PRINT),
    ];
    foreach($data as $k => $v){
        if($v == null || $v == "null"){
            $data[$k] = $cdr->{$k};    
        }    
    }
  $data['menu_name'] = 'Variables';
    return view('__app.button_views.cdr_details', $data);
}

function button_call_records_onnet_details($request)
{
    $cdr = \DB::connection('pbx_cdr')->table('call_records_onnet_variables')->where('call_records_onnet_id', $request->id)->get()->first();
    if(empty($cdr)  || empty($cdr->variables)){
        return json_alert('CDR variables not saved','error');    
    }
    foreach($cdr as $k => $v){
        $cdr->{$k} = str_replace('"Forbidden"",','Forbidden",',$v);  
       
    }
    
    $data = [
        'variables' => json_encode(json_decode($cdr->variables), JSON_PRETTY_PRINT),
    ];
    foreach($data as $k => $v){
        if($v == null || $v == "null"){
            $data[$k] = $cdr->{$k};    
        }    
    }
  $data['menu_name'] = 'Variables';
    return view('__app.button_views.cdr_details', $data);
}

function get_call_profits($partner_id)
{
    $call_profits = \DB::connection('pbx')->table('p_partners')->where('partner_id', $partner_id)->pluck('voice_prepaid_profit')->first();
    if (empty($call_profits)) {
        return 0;
    }
    return $call_profits;
}

function create_postpaid_invoice($account_id, $amount, $reference)
{
    try {
        $account = dbgetaccount($account_id);
        $product_id = 867;
        $db = new DBEvent();
        $data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'Tax Invoice',
            'completed' => 1,
            'bill_frequency' => 1,
            'subscription_created' => 1,
            'account_id' => $account_id,
            'total' => $amount,
            'tax' => $amount - ($amount/1.15),
            'reference' => $reference,
            'billing_type' => '',
            'qty' => [1],
            'price' => [$amount/1.15],
            'full_price' => [$amount/1.15],
            'product_id' => [$product_id],
            //'description' => ['Postpaid Airtime'],
        ];
        if ($account->type == 'reseller_user') {
            $data['account_id'] = $account->partner_id;
            $data['reseller_user'] = $account_id;
        }

        $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

        if (!is_array($result) || empty($result['id'])) {
            return false;
        }
        return true;
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_log($ex->getMessage());
        return false;
    }
}

function update_unlimited_channels_usage(){
    $domains = \DB::connection('pbx')->table('v_domains')->where('unlimited_channels','>',0)->get();
    foreach($domains as $d){
        $unlimited_usage = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('domain_name',$d->domain_name)->where('billing_method','unlimited')->sum('duration');
        $unlimited_usage = currency($unlimited_usage/60);
        \DB::connection('pbx')->table('v_domains')->where('domain_name',$d->domain_name)->update(['unlimited_channels_usage' => $unlimited_usage]);
    }    
}

function schedule_voice_monthly()
{
    /*
        retail_cost ADMIN DEFUALT RETAIL RATE
        partner_profits used to be calculated on retail_cost - partner_cost per call to increment partner_prepaid_profit
        and then create a credit note to payout and reset that amount monthly
    */
 
    // bill postpaid negative balances
    
    $postpaid_domains = \DB::connection('pbx')->table('v_domains')->where('is_postpaid', 1)->get();
    if(count($postpaid_domains) > 0){
        set_airtime_balances_from_pbx();
        foreach ($postpaid_domains as $postpaid_domain) {
            if($postpaid_domain->balance < 0){
                $amount = abs($postpaid_domain->balance);
                if($amount > 0){
                    $reference = date("Y-m").' Postpaid Airtime';
                    $result = create_postpaid_invoice($postpaid_domain->account_id, $amount, $reference);
                    if ($result === true) {
                        \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $postpaid_domain->domain_uuid)->update(['balance' => 0]);
                    }
                }
            }
        }
    }

    set_airtime_balances_from_pbx();

    $voice_packages = \DB::table('sub_services')
        ->where('provision_type', 'airtime_contract')
        ->where('status', '!=', 'Deleted')
        ->get();

    $domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($domains as $domain) {
        $airtime_usage = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
            ->where('domain_name', $domain->domain_name)->where('duration', '>', 0)
            ->sum(\DB::raw('cost'));

        if (empty($airtime_usage)) {
            $airtime_usage = 0;
        }

        $airtime_history = [
            'created_at' => date('Y-m-d H:i:s'),
            'domain_uuid' => $domain->domain_uuid,
            'total' => $airtime_usage,
            'balance' => $domain->balance,
            'type' => 'airtime_usage_cdr',
        ];
        \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
    }

    foreach ($voice_packages as $sub) {
        $account = dbgetaccount($sub->account_id);

        $airtime = $sub->usage_allocation;
        // expire unused airtime
        expire_airtime($account->domain_uuid);

        $balance = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $account->domain_uuid)->pluck('balance')->first();
        if (empty($balance)) {
            $balance = 0;
        }

        $new_balance = $balance + $airtime;

        \DB::connection('pbx')->table('v_domains')
            ->where('account_id', $sub->account_id)
            ->increment('balance', $airtime);

        $airtime_history = [
            'created_at' => date('Y-m-d H:i:s'),
            'domain_uuid' => $account->domain_uuid,
            'total' => $airtime,
            'balance' => $new_balance,
            'type' => 'contract',

        ];
        \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);

        \DB::table('sub_services')->where('id', $sub->id)->update(['current_usage' => $new_balance]);
    }


    // clear usage stats
    \DB::connection('pbx')->table('v_domains')->update(['monthly_usage' => 0]);
    
    //clear unlimited usage
    \DB::connection('pbx')->table('v_domains')->update(['unlimited_channels_usage' => 0]);
}

function expire_airtime($domain_uuid)
{
    
    // airtime should only expire 12 months
    $account = dbgetaccount($account_id);

    $topups = \DB::connection('pbx')->table('p_airtime_history')
        ->where('domain_uuid', $domain_uuid)
        ->where('created_at', '<=', date('Y-m-01', strtotime(' - 3 months')).'%')
        ->where('type', 'contract')->get();

    foreach ($topups as $topup) {
        $processed = \DB::connection('pbx')->table('p_airtime_history')
            ->where('domain_uuid', $domain_uuid)
            ->where('airtime_contract_id', $topup->id)
            ->where('type', 'airtime_expired')
            ->count();
        if (!$processed) {
            $balance = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('balance')->first();
            $usage = \DB::connection('pbx')->table('p_airtime_history')
                ->where('domain_uuid', $domain_uuid)
                ->where('created_at', '>=', date('Y-m-01', strtotime($topup->created_at.' + 1 month')).'%')
                ->where('created_at', '<=', date('Y-m-01', strtotime($topup->created_at.' + 3 month')).'%')
                ->where('type', 'airtime_usage_cdr')
                ->pluck('total')->first();
            if (!$usage) {
                $usage = 0;
            }
            $expired_total = $topup->total - $usage;
            if ($expired_total < 0) {
                $expired_total = 0;
            }
            $new_balance = $balance - $expired_total;
            $airtime_history = [
                'created_at' => date('Y-m-01', strtotime($topup->created_at.' + 3 months')),
                'domain_uuid' => $domain_uuid,
                'total' => $expired_total,
                'balance' => $new_balance,
                'type' => 'airtime_expired',
                'airtime_contract_id' => $topup->id,
                'three_months_usage' => $usage,

            ];
            \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);

            if ($new_balance < 0) {
                \DB::connection('pbx')->table('v_domains')
                    ->where('domain_uuid', $domain_uuid)
                    ->update(['balance' =>$expired_total]);
            } else {
                \DB::connection('pbx')->table('v_domains')
                    ->where('domain_uuid', $domain_uuid)
                    ->decrement('balance', $expired_total);
            }
        }
    }

    $balance = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('balance')->first();
    $history = \DB::connection('pbx')->table('p_airtime_history')
        ->where('domain_uuid', $domain_uuid)
        ->orderBy('created_at', 'desc')->get();
    foreach ($history as $h) {
        \DB::connection('pbx')->table('p_airtime_history')->where('id', $h->id)->update(['balance'=>$balance]);
        if ($h->type == 'airtime_usage_cdr' || $h->type == 'airtime_expired') {
            $balance+=$h->total;
        }
        if ($h->type == 'prepaid' || $h->type == 'contract' || $h->type == 'app signup') {
            $balance-=$h->total;
        }
        if ($h->type == 'airtime_migration') {
            if ($h->total < 0) {
                $balance+=abs($h->total);
            } else {
                $balance-=$h->total;
            }
        }
    }
}


function voip_login()
{
    if (1 == session('account_id')) {
        $pabx_domain = '156.0.96.60';
        $pabx_type = 'root';
    } else {
        $customer = dbgetaccount(session('account_id'));
        $pabx_domain = $customer->pabx_domain;
        $pabx_type = $customer->pabx_type;
    }

    if ('' != $pabx_domain) {
        $domain_uuid = \DB::connection('pbx')->select("select domain_uuid from v_domains where domain_name = '".$pabx_domain."'");
        if ($domain_uuid) {
            $domain_uuid = $domain_uuid[0]->domain_uuid;
        } else {
            error('The voip system does not exist.');
        }
        if ($domain_uuid && '' != $domain_uuid) {
            if (1 == session('account_id')) {
                $sql = "select api_key from v_users where domain_uuid = '".$domain_uuid."' and username='root'";
            } else {
                $sql = "select api_key from v_users where domain_uuid = '".$domain_uuid."' and username='primary'";
            }

            if (\DB::connection('pbx') && \DB::connection('pbx')->select($sql)[0]) {
                $key = \DB::connection('pbx')->select($sql)[0]->api_key;
            } else {
                return 'User not created.';
            }

            return 'http://'.$pabx_domain.'/core/user_settings/user_dashboard.php?key='.$key;
        }
    }
}




function schedule_import_pbx_domains()
{
    $pbx = new FusionPBX();
    $pbx->importDomains();
}

function schedule_set_pbxtype()
{
    $domains = \DB::connection('pbx')->table('v_domains')->get();
    $pbx = new FusionPBX();
    foreach ($domains as $domain) {
        $pbx->setPbxType($domain->domain_uuid);
    }
    $pbx->validateGroups();
    update_all_caller_ids();
}

///////////////////////////////////////////////////////////////////////////////////////////


function pbx_get_extension($extension, $pabx_domain)
{
    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    $extension = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->where('extension', $extension)->get()->first();

    return $extension;
}



function pbx_enable_recording($pabx_domain, $extension)
{
    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->where('extension', $extension)->update(['user_record' => 'all']);
}

function pbx_disable_recording($pabx_domain, $extension)
{
    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->where('extension', $extension)->update(['user_record' => '']);
}

function pbx_add_extension($customer, $extension_number = false)
{
    $conn = 'pbx';
    // \DB::connection('default')->table('crm_accounts')->where('id', $customer->id)->where('type','lead')->update(['type'=>'customer']);
    $customer = dbgetaccount($customer->id);
    
    $mobile = $customer->phone;
    $email = $customer->email;
    $pabx_type = $customer->pabx_type;
    $pabx_domain = $customer->pabx_domain;
    $server = $customer->server;
    
    if (empty($pabx_domain) || empty($server)) {
        return false;
    }
    
    $account_id = $customer->id;
    $domain_uuid = \DB::connection($conn)->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    $cost_calculation = \DB::connection($conn)->table('v_domains')->where('domain_name', $pabx_domain)->pluck('cost_calculation')->first();

    if (empty($domain_uuid)) {
  
        return false;
    }

    $extensions = \DB::connection($conn)->table('v_extensions')->where('domain_uuid', $domain_uuid)->count();
    ++$extensions;
    $extension_uuid = pbx_uuid('v_extensions', 'extension_uuid');
    if (!$extension_number) {
        $extension = pbx_generate_extension($domain_uuid);
    } else {
        $extension = $extension_number;
    }
    
    $pass = mt_rand(10000, 99999);
    pbxrun('update v_domain_settings set domain_setting_value ='.$extensions." where domain_uuid = '".$domain_uuid."'");

    $default_number = \DB::connection($conn)->table('p_phone_numbers')->where('domain_uuid', $domain_uuid)->where('status', 'Enabled')->pluck('number')->first();
    $default_caller_id = (!empty($default_number)) ? $default_number : '';
    $limit_max = ($cost_calculation == 'volume') ? 100 : 10;
    $extension_data =
    ['domain_uuid' => $domain_uuid,
        'extension_uuid' => $extension_uuid,
        'extension' => $extension,
        'password' => $pass,
        'accountcode' => $pabx_domain,
        'dial_domain' => $pabx_domain,
        'directory_visible' => 'true',
        'directory_exten_visible' => 'true',
        'limit_max' => $limit_max,
        'limit_destination' => 'error/user_busy',
        'user_context' => $pabx_domain,
        'call_timeout' => '15',
        'user_record' => '',  // all, local, inbound, outbound
        'hold_music' => 'local_stream://default',
        'enabled' => 'true',
        'effective_caller_id_number' => $extension,
        'outbound_caller_id_name' => $customer->company,
        'outbound_caller_id_number' => $default_caller_id,
        'forward_user_not_registered_enabled' => 'true',
        'forward_no_answer_enabled' => 'true',
        'forward_busy_enabled' => 'false',
        'forward_all_enabled' => 'false',
        'forward_all_destination' => $mobile,
        'forward_user_not_registered_destination' => $mobile,
        'forward_busy_destination' => $mobile,
        'forward_no_answer_destination' => $mobile,
    ];
    
    $volume_domain = \DB::connection($conn)->table('v_domains')->where('domain_uuid',$domain_uuid)->where('cost_calculation','volume')->count();
    
    if($volume_domain){
        $forward_data = [
            'forward_user_not_registered_enabled' => 'false',
            'forward_no_answer_enabled' => 'false',
            'forward_busy_enabled' => 'false',
            'forward_all_enabled'=> 'false',
            'forward_all_destination' => '',
            'forward_user_not_registered_destination' => '',
            'forward_busy_destination' => '',
            'forward_no_answer_destination' => '',
        ];
        $extension_data = array_merge($extension_data,$forward_data);
    }
   
    \DB::connection($conn)->table('v_extensions')->insert($extension_data);

    // add voicemail
    $cost_calculation = \DB::connection($conn)->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('cost_calculation')->first();
    if($cost_calculation != 'volume'){
        $extension_uuid = \DB::connection($conn)->table('v_extensions')->where('domain_uuid', $domain_uuid)->where('extension', $extension)->pluck('extension_uuid')->first();
        if (!empty($extension_uuid)) {
            $voicemail_uuid = pbx_uuid('v_voicemails', 'voicemail_uuid');
            $voicemail_data =
            [   'domain_uuid' => $domain_uuid,
                'voicemail_uuid' => $voicemail_uuid,
                'voicemail_id' => $extension,
                'voicemail_password' => '',
                'voicemail_mail_to' => $email,
                'voicemail_enabled' => 'true',
                'voicemail_local_after_email' => 'true',
                'voicemail_file' => 'attach',
            ];
    
            \DB::connection($conn)->table('v_voicemails')->insert($voicemail_data);
        }
    }
    
    update_pbx_group_permissions();
    return ['extension' => $extension, 'password' => $pass, 'pbx_domain' => $pabx_domain];
}


function pbx_add_cidr_extension($account_id, $domain, $extension, $ip, $server = 'pbx')
{
    $ip .= '/32';

    $domain_uuid = \DB::connection($server)->table('v_domains')->where('account_id', $account_id)->pluck('domain_uuid')->first();
    \DB::connection($server)->table('v_extensions')->where('extension', $extension)->where('domain_uuid', $domain_uuid)->update(['cidr' => $ip]);
    $access_control_node = [
        'access_control_uuid' => '95c16fa8-6c69-4fd8-8090-890cfc83fe17',
        'access_control_node_uuid' => switch_uuid('v_access_control_nodes', 'access_control_node_uuid'),
        'node_type' => 'allow',
        'node_cidr' => $ip,
        'node_domain' => $domain
    ];
    \DB::connection($server)->table('v_access_control_nodes')->insert($access_control_node);

    $cmd = 'php -f /var/www/html/lua/portal.php portal_reloadacl';
    $result = Erp::ssh('156.0.96.61', 'root', 'Ao@147896', $cmd);
}

function pbx_remove_number($number)
{
    \DB::connection('pbx')->table('p_phone_numbers')->where('number', $number)->where('status', 'Deleted')->update(['domain_uuid' => null,'number_routing' => null,'routing_type'=> null]);
    \DB::connection('pbx')->table('p_phone_numbers')->where('number', $number)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled','number_routing' => null,'routing_type'=> null]);
}

function pbx_add_number($pabx_domain, $phone_number, $extension = false)
{
    $v_domain = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->get()->first();
    $domain_uuid = $v_domain->domain_uuid;
    $account_id = $v_domain->account_id;

    if (empty($domain_uuid)) {
        return false;
    }

    $number_exists = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $domain_uuid)->count();

    if (!$number_exists) {
        $caller_id = [
            'outbound_caller_id_number' => $phone_number,
        ];
        \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)
            ->whereNull('outbound_caller_id_number')->update($caller_id);
        \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)
            ->where('outbound_caller_id_number', '')->update($caller_id);
    }

    $routing_type = null;
    if (!empty($extension)) {
        $routing_type = get_routing_type($domain_uuid, $extension);
    }
    //link to domain
    $exists = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $phone_number)->count();

    if (!empty($exists) && $exists >= 1) {
        \DB::connection('pbx')->table('p_phone_numbers')->where('number', $phone_number)->update(['domain_uuid' => $domain_uuid,'number_routing'=>$extension,'routing_type'=>$routing_type]);
    } else {
        $number_uuid = pbx_uuid('p_phone_numbers', 'number_uuid');

        \DB::connection('pbx')->table('p_phone_numbers')->insertGetId(['status' => 'Enabled', 'number_uuid' => "'".$number_uuid."'", 'domain_uuid' => $domain_uuid, 'number' => $phone_number,'number_routing'=>$extension,'routing_type'=>$routing_type]);
    }
}

function pbx_generate_extension($domain_uuid, $extension_num = 101)
{
    $extension = false;
    while (!$extension) {
       
        $extension = $extension_num;
        $exists = \DB::connection('pbx')->table('v_extensions')->where(['domain_uuid' => $domain_uuid, 'extension' => $extension])->count();
        if ($exists) {
            $extension = false;
        }
        $exists = \DB::connection('pbx')->table('v_ring_groups')->where(['domain_uuid' => $domain_uuid, 'ring_group_extension' => $extension])->count();
        if ($exists) {
            $extension = false;
        }
        $exists = \DB::connection('pbx')->table('v_ivr_menus')->where(['domain_uuid' => $domain_uuid, 'ivr_menu_extension' => $extension])->count();
        if ($exists) {
            $extension = false;
        }

        // Time Conditions
        $exists = \DB::connection('pbx')->table('v_dialplans')
        ->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')
        ->where('domain_uuid', $domain_uuid)
        ->where('dialplan_number', $extension)
        ->count();
        if ($exists) {
            $extension = false;
        }
        ++$extension_num;
    }

    return $extension;
}

function switch_generate_extension($domain_uuid)
{
    $extension_num = 100;
    $extension = false;
    while (!$extension) {
        ++$extension_num;

        $extension = $extension_num;

        $exists = \DB::connection('pbx')->table('v_extensions')->where(['domain_uuid' => $domain_uuid, 'extension' => $extension])->count();
        if ($exists) {
            $extension = false;
        }
    }

    return $extension;
}

function get_cloudtools_cname($account_id)
{
    $cname = 'pbx'.$account_id.'.cloudtools.co.za';

    $create_cnames -= $account_id % 20;

    if (0 === $create_cnames) {
        for ($i = 1; $i <= 20; ++$i) {
            $id = $account_id + $i;
            $pbx_domain_prefix = 'pbx'.$id;
            siteworx_add_cname('195', $pbx_domain_prefix, 'cloudtools.co.za', 'pbx.cloudtools.co.za');
        }
    }

    return $cname;
}

function pbx_uuid($table, $key)
{
    $uuid = false;
    while (!$uuid) {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $exists = \DB::connection('pbx')->table($table)->where($key, $uuid)->count();
        if ($exists) {
            $uuid = false;
        }
    }

    return $uuid;
}

function switch_uuid($table, $key)
{
    $uuid = false;
    while (!$uuid) {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $exists = \DB::connection('pbx')->table($table)->where($key, $uuid)->count();
        if ($exists) {
            $uuid = false;
        }
    }

    return $uuid;
}

function pbx_call_enabled()
{
    if (session('role_level') == 'Admin') {
        $pabx_domain = 'pbx.cloudtools.co.za';
    } else {
        $pabx_domain = \DB::table('crm_accounts')->where('id', session('account_id'))->pluck('pabx_domain')->first();
    }

    if (!$pabx_domain) {
        return false;
    }
}
function pbx_call($dial_number, $id, $type = 'account', $outbound_caller_id = false)
{
    /*
    originate {origination_caller_id_number=27813608644}user/101@pbx.cloudtools.co.za &bridge({origination_caller_id_number=27824119555}sofia/gateway/7441e9fa-0d98-422c-a1b6-e89ea78c84f0/27824119555)
    originate {origination_caller_id_number=9005551212}sofia/default/whatever@wherever 19005551212 XML default CALLER_ID_NAME CALLER_ID_NUMBER
    */

    if ('account' == $type) {
        $account_str = 'account_id='.$id;
    }

    // validate number
    $number = za_number_format($dial_number);
    if (!$number) {
        $number = za_number_numerics($dial_number);
    }
    // get extension
    $user_extension = \DB::connection('default')->table('erp_users')->where('id', session('user_id'))->whereNotNull('pbx_extension')->pluck('pbx_extension')->first();
    // $overwrite_click_to_call_extension = get_admin_setting('overwrite_click_to_call_extension');
    // if($overwrite_click_to_call_extension){
    //     $user_extension = $overwrite_click_to_call_extension;
    // }
    
    if (!$user_extension) {
        return 'Invalid User Extension2';
    }

    if (session('role_level') == 'Admin') {
        $pabx_domain = 'pbx.cloudtools.co.za';
    } else {
        $pabx_domain = \DB::connection('default')->table('crm_accounts')->where('id', session('account_id'))->whereNotNull('pabx_domain')->pluck('pabx_domain')->first();
    }

    if (!$pabx_domain) {
        return 'Invalid PBX Domain';
    }

    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    $ext = \DB::connection('pbx')->table('v_extensions')->where(['domain_uuid' => $domain_uuid, 'extension' => $user_extension])->get()->first();

    if (empty($ext)) {
        return 'Invalid User Extension';
    }

    $gateway_uuid = '7441e9fa-0d98-422c-a1b6-e89ea78c84f0';
    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    $caller_id = $ext->outbound_caller_id_number;
    
    // execute freeswitch command
    $pbx = new FusionPBX();

    $call_data = ['type' => 'call', 'source' => $user_extension, 'destination' => $number, 'success' => 1, 'created_at' => date('Y-m-d H:i:s')];
    if ('account' == $type) {
        $call_data['account_id'] = $id;
    }
    $call_data['user_id'] = session('user_id');
    $click_to_call_number = get_admin_setting('click_to_call_number');
    
    // $click_to_call_number = \DB::connection('system')->table('crm_business_plan')->where('instance_id',session('instance')->id)->pluck('phone_number')->first();
    
    if(!empty($click_to_call_number)){
        $caller_id = $click_to_call_number;
    }
    
    // test network calls override caller_id
    if($outbound_caller_id){
        $caller_id = $outbound_caller_id;    
    }
    
    $call_string = $id.'__'.$user_extension.'__'.$pabx_domain.'__'.$caller_id.'__'.$number;
    
    $result = $pbx->portalCmd('pbx_call', $call_string);
    return true;
}

function za_number_format($number = false)
{
    if ($number) {
        $formatted_number = za_number_valid($number);
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

            if (9 != strlen($formatted_number)) {
                return false;
            } else {
                return '27'.$formatted_number;
            }
        }
    }

    return false;
}

function za_number_valid($number = false)
{
    if ($number) {
        $number = str_replace(array(' ', '+', '(', ')', '-', '.', ',', '?'), '', $number);
        if (is_numeric($number) && (intval($number) == $number) && strlen($number) >= 9 && intval($number) > 0) {
            return za_number_numerics($number);
        }
    }

    return null;
}

function za_number_numerics($str)
{
    preg_match_all('/\d+/', $str, $matches);

    return implode($matches[0]);
}

function clear_call_records_session()
{
    //vca.cloudtools.co.za
    //lti.cloudtelecoms.co.za
    session()->forget('airtime_invoice_ids');
    session()->forget('last_cdr_id');
}

function fix_call_records1()
{
    session(['last_cdr_id' => 0]);
    session(['airtime_invoice_ids' => 0]);

    return true;
}

function fix_call_records($from_id = '1', $domain_name = 'vca.cloudtools.co.za')
{
    //session(['last_cdr_id' => 0]);
    //session(['airtime_invoice_ids' => 0]);

    //return true;
    //dd(session('last_cdr_id'));
    $account_id = \DB::table('isp_voice_pbx_domains')->where('pabx_domain', $domain_name)->pluck('account_id')->first();

    $airtime_invoices = \DB::table('crm_documents as cd')
        ->join('crm_document_lines as cdl', 'cdl.document_id', '=', 'cd.id')
        ->where('cdl.product_id', 437)
        ->where('cd.account_id', $account_id)
        ->get();
    //dd($airtime_invoices);
    $invoice_ids = [];
    if (!empty(session('airtime_invoice_ids')) && count(session('airtime_invoice_ids')) > 0) {
        $invoice_ids = session('airtime_invoice_ids');
    }
    if (!empty(session('last_cdr_id'))) {
        $from_id = session('last_cdr_id');
    }

    ini_set('memory_limit', '1024M'); // memory default 128
    set_time_limit(0);
    $first_record_date = \DB::connection('pbx_cdr')->table('call_records_outbound')
        ->where('id', '>=', $from_id)->where('domain_name', $domain_name)
        ->where('duration', '>', 0)
        ->where('direction', 'outbound')
        ->orderby('hangup_time')->pluck('hangup_time')->first();

    $call_records = \DB::connection('pbx_cdr')->table('call_records_outbound')->select(['id', 'rate', 'duration', 'balance', 'hangup_time', 'destination'])
        ->where('id', '>=', $from_id)->where('domain_name', $domain_name)
        ->where('duration', '>', 0)
        ->where('direction', 'outbound')
        ->limit(50000)
        ->orderby('hangup_time')->get();

    $count = count($call_records);

    $balance = null;
    foreach ($call_records as $call) {
        $rate = $call->rate;
        /*
        if ('0.46' == $rate) {
            $rate = '0.30';
        }
        if ('South Africa Mobile Telkom Mobile' == $call->destination) {
            $rate = '0.40';
        }
*/
        $cost = currency(($rate / 60) * $call->duration);
        if (null === $balance) {
            $balance = $call->balance;
        }
        foreach ($airtime_invoices as $inv) {
            if (date('Y-m-d', strtotime($call->hangup_time)) >= $inv->docdate && $inv->docdate > $first_record_date) {
                if (0 == count($invoice_ids) || !in_array($inv->document_id, $invoice_ids)) {
                    $inv->price = currency($inv->price);
                    $balance = currency($balance + $inv->price);

                    $invoice_ids[] = $inv->document_id;
                }
            }
        }

        $balance -= $cost;
        \DB::connection('pbx_cdr')->table('call_records_outbound')->where('id', $call->id)->update(['rate' => $rate, 'cost' => $cost, 'balance' => $balance]);
        session(['last_cdr_id' => $call->id]);
    }

    session(['airtime_invoice_ids' => $invoice_ids]);
    // 169850.43
    // 181072.29
    // \DB::statement('UPDATE sub_services SET current_usage = '.$balance." WHERE provision_type = 'airtime_prepaid' and detail = '".$domain_name."'");
}

function export_cdr($connection, $account_id, $month = null, $hangup_cause = null)
{
    $table = 'call_records_outbound';
    if (!$month) {
        $month = date('Y-m-d');
    }

    $month_name = date('F Y',strtotime($month));
    if (date('Y-m') == date('Y-m', strtotime($month))) {
        $table = 'call_records_outbound';
        
    }elseif (date('Y-m', strtotime('-1 month')) == date('Y-m', strtotime($month))) {
        $table = 'call_records_outbound_lastmonth';
    } elseif (date('Y-m-01', strtotime($month)) != date('Y-m-01')) {
        $table = $table.'_'.strtolower(date('M', strtotime($month)));
    }

    $date_start = date('Y-m-01', strtotime($month));
    $date_end = date('Y-m-d 23:59');
    if ($account_id == 0) {
        $domain = 'all_domains';
        $domain_name = 'all_domains';
    } else {
        $domain = \DB::table('isp_voice_pbx_domains')->where('account_id', $account_id)->pluck('pabx_domain')->first();
        $domain_name = $domain;
    }

    $file_title = str_replace(['-',' '], '_', 'Call Records '.$domain_name.' '.$month_name);

    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;

    $call_records_query = \DB::connection($connection)->table($table)
        ->select('hangup_time', 'hangup_cause', 'domain_name', 'extension', 'caller_id_number', 'callee_id_number', 'destination', 'duration', 'rate', 'cost', 'balance')
        ->where('direction', 'outbound')
        ->where('hangup_time', '>=', $date_start)
        ->where('hangup_time', '<=', $date_end);

    if ($domain != 'all_domains') {
        $call_records_query->where('domain_name', $domain);
    }

    if ($hangup_cause) {
        $call_records_query->where('hangup_cause', $hangup_cause);
    } else {
        $call_records_query->where('duration', '>', 0);
    }
    $call_records_query->orderby('hangup_time');
    $call_records = $call_records_query->get();

    foreach ($call_records as $item) {
        $excel_list[] = [
            'Hangup Time' => $item->hangup_time,
            //    'Domain Name' => $item->domain_name,
            'Extension' => $item->extension,
            'Caller Id' => $item->callee_id_number,
            'Callee Id' => $item->callee_id_number,
            'Destination' => $item->destination,
            'Duration' => $item->duration,
            'Rate' => $item->rate,
            'Cost' => $item->cost,
            'Balance' => $item->balance,
            'Hangup Cause' => $item->hangup_cause,
        ];
    }

    if (empty($excel_list)) {
        $excel_list[] = [
            'Hangup Time' => '',
            //   'Domain Name' => '',
            'Extension' => '',
            'Caller Id' => '',
            'Callee Id' => '',
            'Destination' => '',
            'Duration' => '',
            'Rate' => '',
            'Cost' => '',
            'Balance' => '',
            'Hangup Cause' => '',
        ];
    }

    $account_currency = get_account_currency($account_id);
    $export = new App\Exports\CollectionExport();
    $export->seCurrencyColumns(['E','F','G'], $account_currency);
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

    return $file_name;
}

function export_cdr_table($type, $account_id, $table = 'call_records_outbound', $where = false)
{
    ini_set('memory_limit', '4048M');
    ini_set('max_execution_time', 360);

    $file_type = 'xlsx';
    if ($type == 'reseller') {
        $file_type = 'pdf';
    }

    $file_type = 'xlsx';
    $model = new \App\Models\ErpModel($table);
    $model->setAliasSearch(0);
    $sql_where = '';
// aa($where);
    if ($where) {
     
        $sql = $model->buildSql($where);
    //    aa($sql);
        $sql = strtolower($sql);

        $sql_arr = explode('where', $sql);
        if (!str_contains($sql_arr[1], ' limit ') && !str_contains($sql_arr[1], ' order by ')) {
            $sql_where = $sql_arr[1];
        } elseif (str_contains($sql_arr[1], ' order by ')) {
            $sql_arr2 = explode('order', $sql_arr[1]);
            $sql_where = $sql_arr2[0];
        } elseif (str_contains($sql_arr[1], ' limit ')) {
            $sql_arr2 = explode('limit', $sql_arr[1]);
            $sql_where = $sql_arr2[0];
        }
    }

    if ($table=='call_records_outbound_lastmonth' && !empty(session('cdr_archive_table'))) {
        $table = session('cdr_archive_table');
    }
    $account_id = session('account_id');
    if ($type == 'reseller') {
        $domains = \DB::connection('pbx')->table('v_domains')->where('partner_id', $account_id)->pluck('domain_name')->toArray();
    } else {
        if (is_main_instance() && $account_id == 1) {
            $account_id = 12;
        }
        $domains = \DB::connection('pbx')->table('v_domains')->where('account_id', $account_id)->pluck('domain_name')->toArray();
    }

    $file_title = ucwords(str_replace('_', ' ', $table));

    $file_name = $file_title.'.'.$file_type;
    $file_path = attachments_path().$file_name;

    $columns_export = ['hangup_time', 'hangup_cause', 'domain_name', 'extension', 'caller_id_number', 'callee_id_number', 'destination', 'duration', 'rate', 'cost', 'balance'];
    if ($type == 'reseller') {
        $columns_export[] = 'partner_rate';
        $columns_export[] = 'partner_cost';
        $columns_export[] = 'partner_profit';
        $columns_export[] = 'partner_balance';
    }
    foreach ($columns_export as $i => $col) {
        if (!\Schema::connection('pbx_cdr')->hasColumn($table, $col)) {
            unset($columns_export[$i]);
        }
    }


    $decimal_column = get_columns_from_schema($table, ['decimal','float','double'], 'pbx_cdr');
    $currency_cols = [];
    foreach ($columns_export as $i => $col) {
        if (in_array($col, $decimal_column)) {
            $currency_cols[] = $i;
        }
    }

    foreach ($currency_cols as $i => $currency_col) {
        $currency_cols[$i] = excelColNum($currency_col);
    }

    $call_records_query = \DB::connection('pbx_cdr')->table($table)
        ->select($columns_export);
// aa($sql_where);
    if ($sql_where) {
        $call_records_query->whereRaw($sql_where);
    } else {
        $call_records_query->where('duration', '>', 0);
    }
    //if (session('pbx_account_id') != 1 && session('orig_role_id') < 10) {
    //    $call_records_query->whereIn('domain_name', $domains);
    //}
    if (session('role_level') != 'Admin') {
        $call_records_query->whereIn('domain_name', $domains);
    }

    $call_records_query->orderby('hangup_time');
    //  $sql = querybuilder_to_sql($call_records_query);
    //   aa($sql);
    $call_records = $call_records_query->get();

    foreach ($call_records as $item) {
        $row = [];
        foreach ($columns_export as $col) {
            $key = ucwords(str_replace('_', ' ', $col));
            $val = $item->{$col};
            $row[$key] = $val;
        }
        $excel_list[] = $row;
    }

    if (empty($excel_list)) {
        $row = [];
        foreach ($columns_export as $col) {
            $key = ucwords(str_replace('_', ' ', $col));

            $row[$key] = '';
        }
        $excel_list[] = $row;
    }

    $export = new App\Exports\CollectionExport();
    $export->setData($excel_list);
    if (count($currency_cols) > 0) {
        $account_currency = get_account_currency($account_id);
        $export->seCurrencyColumns($currency_cols, $account_currency);
    }
    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

    return $file_name;
}

function excelColNum($n)
{
    for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
        $r = chr($n%26 + 0x41) . $r;
    }
    return $r;
}

function create_numbers()
{
    $x = 27100556681;
    while ($x < 27100556689) {
        $sql3 = 'insert into p_phone_numbers set number = "'. $record->ani .'"';
        $check = \DB::connection('pbx')->select($sql3)[0];
    }
}

function schedule_pbx_validate_partners()
{

    // reseller data
    $partners = \DB::table('crm_accounts')->where('type', 'reseller')->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    foreach ($partners as $reseller) {
        $exists = \DB::connection('pbx')->table('p_partners')->where('partner_id', $reseller)->count();
        if (!$exists) {
            \DB::connection('pbx')->table('p_partners')->insert(['partner_id' => $reseller]);
        }
    }
}

function aftersave_pbx_ratesheet_set_defaults($request)
{
    $ratesheet_id = $request->id;
    $partner_id =  session('account_id');
    $data = [
        'partner_id' => $partner_id,
    ];
    $partner_set = \DB::connection('pbx')->table('p_rates_partner')->where('id', $ratesheet_id)->where('partner_id', '>', 0)->count();
    if (!$partner_set) {
        \DB::connection('pbx')->table('p_rates_partner')->where('id', $ratesheet_id)->update($data);
    }

    $rates_exists = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id', $ratesheet_id)->count();
    if (!$rates_exists) {
        $rates = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id', 1)->get();
        foreach ($rates as $r) {
            $data = (array) $r;
            $data['ratesheet_id'] = $ratesheet_id;
            $data['partner_id'] = $partner_id;
            unset($data['id']);
            \DB::connection('pbx')->table('p_rates_partner_items')->insert($data);
        }
    }
}

function verify_pbx_status()
{
    $domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($domains as $domain) {
        $account_status = \DB::connection($domain->erp)->table('crm_accounts')->where('id', $domain->account_id)->pluck('status')->first();
        if ($account_status != $domain->status) {
            \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain->domain_uuid)->update(['status' => $account_status]);
        }
    }
}

function schedule_verify_voicemails()
{
    $domain_uuids = \DB::connection('pbx')->table('v_domains')->pluck('domain_uuid')->toArray();
    \DB::connection('pbx')->table('v_voicemails')->whereNotIn('domain_uuid',$domain_uuids)->delete();
    $domains = \DB::connection('pbx')->table('v_domains')->where('account_id','>',1)->get();
    foreach ($domains as $domain) {
        $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->get();
        $account = \DB::connection($domain->erp)->table('crm_accounts')->where('id', $domain->account_id)->get()->first();
        $extensions_numbers = $extensions->pluck('extension')->toArray();
        if(count($extensions_numbers) > 0){
            \DB::connection('pbx')->table('v_voicemails')->where('domain_uuid', $domain->domain_uuid)->whereNotIn('voicemail_id', $extensions_numbers)->delete();
        }else{
            \DB::connection('pbx')->table('v_voicemails')->where('domain_uuid', $domain->domain_uuid)->delete();
        }
        foreach ($extensions as $extension) {
            $voicemail = \DB::connection('pbx')->table('v_voicemails')->where('domain_uuid', $domain->domain_uuid)->where('voicemail_id', $extension->extension)->count();
            if (!$voicemail) {
                $voicemail_uuid = pbx_uuid('v_voicemails', 'voicemail_uuid');
                $voicemail_data =[
                    'domain_uuid' => $domain->domain_uuid,
                    'voicemail_uuid' => $voicemail_uuid,
                    'voicemail_id' => $extension->extension,
                    'voicemail_password' => '',
                    'voicemail_mail_to' => $account->email,
                    'voicemail_enabled' => 'true',
                    'voicemail_local_after_email' => 'true',
                    'voicemail_file' => 'link',
                ];
                \DB::connection('pbx')->table('v_voicemails')->insert($voicemail_data);
            }else{
              
                \DB::connection('pbx')->table('v_voicemails')
                ->where('voicemail_mail_to','')
                ->where('voicemail_id',$extension->extension)
                ->where('domain_uuid',$domain->domain_uuid)
                ->update(['voicemail_mail_to' => $account->email]);
                \DB::connection('pbx')->table('v_voicemails')
                ->whereNull('voicemail_mail_to')
                ->where('voicemail_id',$extension->extension)
                ->where('domain_uuid',$domain->domain_uuid)
                ->update(['voicemail_mail_to' => $account->email]);
            }
        }
    }
    
}


function schedule_freeswitch_import_log(){
    
     $cmd = 'cat /var/log/freeswitch/freeswitch.log | grep error';
     $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
     
}