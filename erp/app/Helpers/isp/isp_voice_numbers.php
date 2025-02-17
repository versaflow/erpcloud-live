<?php

function remove_unused_087_numbers(){
   // return false;
    $r = 0;
    $account_ids = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','!=','volume')->pluck('account_id')->toArray();
    $accounts = \DB::table('crm_accounts')->whereIn('id',$account_ids)->where('status','!=','Deleted')->get();
    $subs = \DB::table('sub_services')->whereIn('account_id',$account_ids)->where('product_id',127)->where('status','!=','Deleted')->get();
    foreach($subs as $sub){
        $geo_subscription_exists = \DB::table('sub_services')
        ->where('account_id',$sub->account_id)
        ->where('product_id','!=',127)
        ->where('provision_type','phone_number')
        ->where('status','!=','Deleted')
        ->count();
        if($geo_subscription_exists){
            // check cdr
            $outbound_cdr_records = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('domain_name',$sub->pbx_domain)
            ->where('caller_id_number',$sub->detail)
            ->count();
            $inbound_cdr_records = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('domain_name',$sub->pbx_domain)
            ->where('callee_id_number',$sub->detail)
            ->count();
            $used_in_routing = \DB::connection('pbx')->table('p_phone_numbers')->where('routing_type','>','')->where('routing_type','!=','extension')->where('number',$s->detail)->count();
                $company = $accounts->where('id',$sub->account_id)->pluck('company')->first();
            if(!$used_in_routing && !$outbound_cdr_records && !$inbound_cdr_records){
               
                $r++;
                //pbxnumbers_unallocate($sub->detail);
            }
        }
        
    }

}


function schedule_export_available_numbers(){
    $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->where('enabled', 'true')->pluck('gateway_uuid')->toArray();
        
    $numbers = \DB::connection('pbx')->table('p_phone_numbers')->select('prefix', 'number')->whereIn('gateway_uuid',$gateway_uuids)->whereNull('domain_uuid')->where('status','Enabled')->orderBy('number')->get();

    $file_title = 'Available Phone Numbers';
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $excel_list = [];

    foreach ($numbers as $n) {
        $excel_list[] = (array) $n;
    }


    $export = new App\Exports\CollectionExport();
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
}

function button_unallocate_087_numbers($request){
    $subs = \DB::table('sub_services')
    ->where('product_id',127)
    ->where('date_activated','<',date('Y-m-d',strtotime('-1 month')))
    ->where('status','!=','Deleted')
    ->get();
    
    $domains = \DB::connection('pbx')->table('v_domains')->get();
    
    foreach($subs as $s){
        $domain_uuid = $domains->where('account_id',$s->account_id)->pluck('domain_uuid')->first();
        $total_numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid',$domain_uuid)->count();
        if($total_numbers === 1){
            continue;
        }
        $sub_number = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid',$domain_uuid)->where('number',$s->detail)->get()->first();
        if(!$sub_number->last_inbound_call && !$sub_number->last_outbound_call){
          unallocate_pbx_number($sub_number->id);
        }
    }
    return json_alert('087 numbers removed');
}

function unallocate_pbx_number($id){
    $deleted_at = date('Y-m-d H:i:s');
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $id)->get()->first();
    if ($num->domain_uuid > '') {
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('account_id')->first();
        \DB::table('sub_services')->where('detail', $num->number)->where('status','Deleted')->delete();
        \DB::table('sub_services')->where('detail', $num->number)->where('account_id', $account_id)->update(['status'=>'Deleted','deleted_at'=>$deleted_at]);
    }
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $id)->where('status', 'Deleted')->update(['domain_uuid' => null,'number_routing' => null,'routing_type'=> null,'wholesale_ext'=> 0]);
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $id)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled','number_routing' => null,'routing_type'=> null,'wholesale_ext'=> 0]);

    /// clear extension cache
    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $num->domain_uuid)->get();
    foreach ($extensions as $ext) {
        $pbx = new FusionPBX();
        $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
        $pbx->portalCmd('portal_aftersave_extension', $key);
    }
    
    update_caller_id($num->domain_uuid);
}

function schedule_wholesale_numbers_uptime()
{

    // system uptime
    // All wholesale numbers have at least 10 normal clearing calls.
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','volume')->pluck('domain_uuid')->toArray();
    
    $volume_numbers = \DB::connection('pbx')->table('p_phone_numbers')
    ->where('status', 'Enabled')
    ->whereIn('domain_uuid', $volume_domains)
    ->pluck('number')->toArray();
    $result = '';
    $cdr_total = 0;
    $date = date('Y-m-d H');
    //$date = date('Y-m-d',strtotime('-1 day'));
    foreach ($volume_numbers as $volume_number) {
        $destinations =   $cdr_counts = \DB::connection('pbx_cdr')->table('call_records_outbound')->select('destination')
        ->where('hangup_cause', 'not like', 'BLOCKED_%')
        ->where('hangup_time', 'like', $date.'%')
        ->where('caller_id_number', $volume_number)->groupBy('destination')->pluck('destination')->filter()->unique()->toArray();
        $destination_result = '';
        foreach ($destinations as $destination) {
            $call_rejected_count = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('hangup_cause', 'CALL_REJECTED')
            ->where('destination', $destination)
            ->where('hangup_time', 'like', $date.'%')
            ->where('caller_id_number', $volume_number)->count();
            $origininator_cancel_count = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('hangup_cause', 'ORIGINATOR_CANCEL')
            ->where('destination', $destination)
            ->where('hangup_time', 'like', $date.'%')
            ->where('caller_id_number', $volume_number)->count();
            $total_not_rejected = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('hangup_cause', '!=', 'CALL_REJECTED')
            ->where('hangup_cause', '!=', 'ORIGINATOR_CANCEL')
            ->where('destination', $destination)
            ->where('hangup_time', 'like', $date.'%')
            ->where('caller_id_number', $volume_number)->count();
            if ($call_rejected_count > $total_not_rejected && $total_not_rejected > 20) {
                $gateway = \DB::connection('pbx_cdr')->table('call_records_outbound')
                ->select('gateway')
                ->where('destination', $destination)
                ->where('hangup_time', 'like', $date.'%')
                ->where('caller_id_number', $volume_number)->pluck('gateway')->first();
                $destination_result .= '<p>'.$gateway.' '.$destination.' - CALL_REJECTED:'.$call_rejected_count.' | ORIGINATOR_CANCEL:'.$origininator_cancel_count.' | OTHER:'.$total_not_rejected.'</p><br>';

                $cdr_total++;
            }
        }
        if ($destination_result > '') {
            $result .= '<b>'.$volume_number.'<b><br>'.$destination_result;
            //dd($result);
        }
    }

    if ($result > '') {
        $data = [];
        $data['function_name'] =__FUNCTION__;
        $data['uptime_result'] = $result;
        //$data['test_debug'] =1;
        erp_process_notification(1, $data);
    }
}



function aftersave_phone_numbers_wholesale_ext($request){
      $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->pluck('domain_uuid')->toArray(); 
    foreach($volume_domains as $volume_domain){
        \DB::connection('pbx')->table('p_phone_numbers')->where('number_routing','>','')->where('domain_uuid', $volume_domain)->where('wholesale_ext', 0)->update(['wholesale_ext'=>\DB::raw('number_routing')]);
    }
    \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid','0abfd39b-294e-40e1-a813-79799077edf0')->where('wholesale_ext',101)->update(['number_routing'=>0]);
}

function phone_numbers_set_volume_outbound(){
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->pluck('domain_uuid')->toArray(); 
    foreach($volume_domains as $volume_domain){
        \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $volume_domain)->where('wholesale_ext', '')->update(['wholesale_ext'=>\DB::raw('number_routing')]);
        \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $volume_domain)->whereNull('wholesale_ext')->update(['wholesale_ext'=>\DB::raw('number_routing')]);
    }
  \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid','0abfd39b-294e-40e1-a813-79799077edf0')->where('wholesale_ext',101)->update(['number_routing'=>0]);
}

function schedule_pbx_phone_numbers_update_subscriptions(){
    $pbx = new FusionPBX;
    $pbx->verify_number_subscriptions();
}

function schedule_network_temp_blocked_notification()
{
    return false;
    $domains = \DB::connection('pbx')->table('v_domains')
    ->where('cost_calculation', 'volume')->get();

    foreach ($domains as $domain) {
        $data = [];
        $data['attachments'] = [];

        $blocked_calls = \DB::connection('pbx')->table('mon_block_temporary')->where('domain_name', $domain->domain_name)->where('duration', 7)->get();

        if (count($blocked_calls) > 0) {
            $file_title = str_replace('.', '', $domain->domain_name).'_blocked';
            $file_path = attachments_path().$file_title.'.xlsx';
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $blocked_calls =  json_decode(json_encode($blocked_calls), true);

            foreach ($blocked_calls as $i => $arr) {
                unset($blocked_calls[$i]['id']);
                unset($blocked_calls[$i]['gateway']);
            }


            $export = new App\Exports\CollectionExport();
            $export->setData($blocked_calls);

            Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

            $data['attachments'][] = $file_title.'.xlsx';


            // $data['function_name'] = __FUNCTION__;
            // if ($domain->domain_name == 'vca.cloudtools.co.za') {
            //     $data['force_to_email'] = 'paul@vca.co.za';
            // }
            //$data['test_debug'] = 1;
            erp_process_notification($domain->account_id, $data);
        }
    }
}


function get_available_pbx_phone_numbers()
{
    $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->pluck('gateway_uuid')->toArray();
    return \DB::connection('pbx')->table('p_phone_numbers')
    ->select('id', 'number', 'prefix')
    ->whereIn('gateway_uuid', $gateway_uuids)
    ->whereNull('domain_uuid')
    ->get();
}

function schedule_phone_number_routing_check()
{

   // $domains = \DB::connection('pbx')->table('v_domains')->get();
    // foreach
    $numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('number_routing', '>', '')->whereNotNull('domain_uuid')->where('status', '!=', 'Deleted')->get();

    foreach ($numbers as $num) {
        $routing_type = get_routing_type($num->domain_uuid, $num->number_routing);

        if (empty($routing_type)) {
            \DB::connection('pbx')->table('p_phone_numbers')->where('id', $num->id)->update(['number_routing' => null,'routing_type' => null]);
        } else {
            \DB::connection('pbx')->table('p_phone_numbers')->where('id', $num->id)->update(['routing_type' => $routing_type]);
        }
    }

    $numbers = \DB::connection('pbx')->table('p_phone_numbers')->whereNotNull('domain_uuid')->where('status', '!=', 'Deleted')->get();

    foreach ($numbers as $num) {
        if (empty($num->number_routing)) {
            $ext = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $num->domain_uuid)->pluck('extension')->first();
            if (!empty($ext)) {
                \DB::connection('pbx')->table('p_phone_numbers')->where('id', $num->id)->update(['number_routing' => $ext,'routing_type' => 'extension']);
            }
        }
    }
}


function schedule_phone_numbers_set_lastcall_date()
{
    $numbers = \DB::connection('pbx')->table('p_phone_numbers')->pluck('number')->toArray();
    foreach ($numbers as $number) {
        $destinations = ['fixed telkom','fixed liquid','mobile mtn','mobile vodacom','mobile cellc','mobile telkom'];
        $cdr_records = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
            ->select('destination', 'hangup_time')
            ->where('hangup_cause', 'NORMAL_CLEARING')
            ->whereIn('destination', $destinations)
            ->where('caller_id_number', $number)
            ->groupBy('destination')->orderBy('hangup_time')->get();
        foreach ($cdr_records as $cdr_record) {
            $date_field = str_replace(' ', '_', $cdr_record->destination).'_lastcall';
            \DB::connection('pbx')->table('p_phone_numbers')->where('number', $number)->whereNull($date_field)->update([$date_field => $cdr_record->hangup_time]);
        }
    }
}




function get_porting_table_from_number($number){
    
    if (substr($number, 0, 2) != '27') {
        return false;
    }
    $prefix = substr($number, 0, 3);

    $table = '';
    if ($prefix == '271') {
        $table = 'p_ported_numbers_gnp_1';
    }
    if ($prefix == '272') {
        $table = 'p_ported_numbers_gnp_2';
    }
    if ($prefix == '273') {
        $table = 'p_ported_numbers_gnp_3';
    }
    if ($prefix == '274') {
        $table = 'p_ported_numbers_gnp_4';
    }
    if ($prefix == '275') {
        $table = 'p_ported_numbers_gnp_5';
    }
    if ($prefix == '276') {
        $table = 'p_ported_numbers_crdb_6';
    }
    if ($prefix == '277') {
        $table = 'p_ported_numbers_crdb_7';
    }
    if ($prefix == '278') {
        $table = 'p_ported_numbers_crdb_8';
    }
    if (empty($table)) {
        return false;
    }
    return $table;
}

function get_ported_number_network($number){
    
    $table = get_porting_table_from_number($number);
   
    if(!$table){
        return  '';    
    }
    
    return \DB::connection('pbx_cdr')->table($table)->where('msisdn',$number)->pluck('network')->first();
}

function number_sms_sent($number)
{
    $exists = \DB::connection('default')->table('isp_sms_message_queue')->where('number', $number)->count();
    if ($exists) {
        return true;
    }
    return false;
}



function button_phone_numbers_call_number($request)
{
    $number = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->pluck('telkom')->first();
    $result = pbx_call($number, session('account_id'));
    if (true === $result) {
        return json_alert('Call sent to PBX');
    } else {
        return json_alert($result, 'error');
    }
}


function button_phone_numbers_assign_to_session($request)
{
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update(['gateway_uuid' => 'c924db4e-a881-44e8-b8da-a150e3cf4c52']);
    return json_alert('Number assigned to SESSION');
}


function button_phone_numbers_set_admin_caller_id($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $data['outbound_caller_id_number'] = $num->number;
    $user_extension = \DB::table('erp_users')->where('id', session('user_id'))->whereNotNull('pbx_extension')->pluck('pbx_extension')->first();
    \DB::connection('pbx')->table('v_extensions')->where('extension', $user_extension)->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    $pbx = new FusionPBX();

    $key = 'directory:'.$user_extension.'@'.'pbx.cloudtools.co.za';
    $pbx->portalCmd('portal_aftersave_extension', $key);
    //\DB::connection('pbx')->table('v_extensions')->where('extension', '101')->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    return json_alert('Done');
}




function aftersave_phonenumbers_set_defaults($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();

    if (empty($num->number_uuid)) {
        $number_uuid = pbx_uuid('p_phone_numbers', 'number_uuid');
        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update(['number_uuid' => $number_uuid]);
    }
    if (empty($num->status)) {
        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update(['status' => 'Enabled']);
    }

    $prefix = substr($num->number, 0, 4);
    $prefix = str_replace('27', 0, $prefix);
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update(['prefix' => $prefix]);
}

function button_pbxnumbers_view_routing($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $domain =  \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('domain_name')->first();
    $api_key = \DB::connection('pbx')->table('v_users as vu')->where('domain_uuid', $num->domain_uuid)->pluck('api_key')->first();

    if ($num->routing_type == 'ring_group') {
        $ring_group_uuid = \DB::connection('pbx')->table('v_ring_groups')
            ->where('domain_uuid', $num->domain_uuid)
            ->where('ring_group_extension', $num->number_routing)
            ->pluck('ring_group_uuid')->first();
        if ($ring_group_uuid) {
            $url = 'http://'.$domain.'/app/ring_groups/ring_group_edit.php?id='.$ring_group_uuid.'&key='.$api_key;
            return redirect()->to($url);
        }
    }
    if ($num->routing_type == 'ivr_menu') {
        $ivr_menu_uuid = \DB::connection('pbx')->table('v_ivr_menus')
            ->where('domain_uuid', $num->domain_uuid)
            ->where('ivr_menu_extension', $num->number_routing)
            ->pluck('ivr_menu_uuid')->first();
        if ($ivr_menu_uuid) {
            $url = 'http://'.$domain.'/app/ivr_menus/ivr_menu_edit.php?id='.$ivr_menu_uuid.'&key='.$api_key;
            return redirect()->to($url);
        }
    }

    if ($num->routing_type == 'time_condition') {
        $time_condition_uuid = \DB::connection('pbx')->table('v_dialplans')
            ->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')
            ->where('domain_uuid', $num->domain_uuid)
            ->where('dialplan_number', $num->number_routing)
            ->pluck('dialplan_uuid')->first();
        if ($time_condition_uuid) {
            $url = 'http://'.$domain.'/app/time_conditions/time_condition_edit.php?id='.$time_condition_uuid.'&key='.$api_key.'&app_uuid=4b821450-926b-175a-af93-a03c441818b1';
            return redirect()->to($url);
        }
    }

}

function pbxnumbers_unallocate($number)
{
    $deleted_at = date('Y-m-d H:i:s');
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $number)->get()->first();
    $id = $num->id;
    if ($num->domain_uuid > '') {
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('account_id')->first();
        \DB::table('sub_services')->where('detail', $num->number)->where('status','Deleted')->delete();
        \DB::table('sub_services')->where('detail', $num->number)->where('account_id', $account_id)->update(['status'=>'Deleted','deleted_at'=>$deleted_at]);
    }
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $id)->where('status', 'Deleted')->update(['domain_uuid' => null,'number_routing' => null,'routing_type'=> null,'wholesale_ext'=> 0]);
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $id)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled','number_routing' => null,'routing_type'=> null,'wholesale_ext'=> 0]);

    /// clear extension cache
    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $num->domain_uuid)->get();
    foreach ($extensions as $ext) {
        $pbx = new FusionPBX();
        $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
        $pbx->portalCmd('portal_aftersave_extension', $key);
    }
    return json_alert('Done');
}

function button_pbxnumbers_unallocate($request)
{
    $deleted_at = date('Y-m-d H:i:s');
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    if ($num->domain_uuid > '') {
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('account_id')->first();
        \DB::table('sub_services')->where('detail', $num->number)->where('status','Deleted')->delete();
        \DB::table('sub_services')->where('detail', $num->number)->where('account_id', $account_id)->update(['status'=>'Deleted','deleted_at'=>$deleted_at]);
    }
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->where('status', 'Deleted')->update(['domain_uuid' => null,'number_routing' => null,'routing_type'=> null,'wholesale_ext'=> 0]);
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled','number_routing' => null,'routing_type'=> null,'wholesale_ext'=> 0]);

    /// clear extension cache
    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $num->domain_uuid)->get();
    foreach ($extensions as $ext) {
        $pbx = new FusionPBX();
        $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
        $pbx->portalCmd('portal_aftersave_extension', $key);
    }
    return json_alert('Done');
}

function button_pbxnumbers_change_number($request)
{
    $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->pluck('gateway_uuid')->toArray();
    // session only
    $data = [];
    $gateway_uuid = '0d0d2b47-af57-4b02-80ff-5cb787c865c0';
    // match number with gateway
    $gateway_uuid = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->pluck('gateway_uuid')->first();
    $gateway_name = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->pluck('gateway')->first();
    $number = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->pluck('number')->first();
    $current_domain_uuid = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->pluck('domain_uuid')->first();
    $numbers = \DB::table('sub_services')->where('provision_type','phone_number')->where('status','!=','Deleted')->pluck('detail')->toArray();
    
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->pluck('domain_uuid')->toArray();
    if(in_array($current_domain_uuid,$volume_domains)){
        $p_phone_numbers = \DB::connection('pbx')->table('p_phone_numbers')
            ->join('v_gateways','v_gateways.gateway_uuid','=','p_phone_numbers.gateway_uuid')
            ->select('p_phone_numbers.id','p_phone_numbers.prefix','p_phone_numbers.number','v_gateways.gateway')
            ->whereNull('p_phone_numbers.domain_uuid')
            ->whereNotIn('p_phone_numbers.number',$numbers)
            ->where('p_phone_numbers.status', 'Enabled')
            //->whereIn('p_phone_numbers.gateway_uuid', $gateway_uuids)
            ->where('p_phone_numbers.gateway_uuid', $gateway_uuid)
            ->where('p_phone_numbers.is_spam', 0)
            ->orderby('p_phone_numbers.prefix','desc')->orderby('p_phone_numbers.number')
            ->get();
    }else{
        $p_phone_numbers = \DB::connection('pbx')->table('p_phone_numbers')
            ->join('v_gateways','v_gateways.gateway_uuid','=','p_phone_numbers.gateway_uuid')
            ->select('p_phone_numbers.id','p_phone_numbers.prefix','p_phone_numbers.number','v_gateways.gateway')
            ->whereNull('p_phone_numbers.domain_uuid')
            ->whereNotIn('p_phone_numbers.number',$numbers)
            ->where('p_phone_numbers.status', 'Enabled')
            ->whereIn('p_phone_numbers.gateway_uuid', $gateway_uuids)
            //->where('p_phone_numbers.gateway_uuid', $gateway_uuid)
            ->where('p_phone_numbers.is_spam', 0)
            ->orderby('p_phone_numbers.prefix','desc')->orderby('p_phone_numbers.number')
            ->get();
    }
    
   
        
    $data['numbers'] = [];
    foreach($p_phone_numbers as $p_phone_number){
    $data['numbers'][] = ['text'=>$p_phone_number->number.' '.$p_phone_number->gateway,'id'=>$p_phone_number->id,'prefix'=>$p_phone_number->prefix];
    }
    
    $data['id'] = $request->id;
    $data['number'] = $number;
    $data['gateway_name'] = $gateway_name;
    return view('__app.button_views.pbx_number_change', $data);
}

function button_pbxnumbers_edit_extension($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $extension_id = \DB::connection('pbx')->table('v_extensions')->where('extension', $num->number_routing)->where('domain_uuid', $num->domain_uuid)->pluck('id')->first();

    $menu_name = get_menu_url_from_table('v_extensions');
    return redirect()->to($menu_name.'/edit/'.$extension_id);
}




function afterdelete_pbxnumbers_delete_subscription($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $domain_uuid = $num->domain_uuid;
    $exists = \DB::table('sub_services')->where('detail', $num->number)->where('status', '!=', 'Deleted')->count();

    if ($exists) {
       \DB::table('sub_services')->where('detail', $num->number)->where('status','Deleted')->delete();
       \DB::table('sub_services')->where('detail', $num->number)->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s')]);
    }
    \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update(['domain_uuid' => null, 'status' => 'Deleted','number_routing' => null,'routing_type'=> null,'wholesale_ext'=>0]);
    
   
    if($domain_uuid){
        update_all_caller_ids($domain_uuid);
    }
}

function number_routing_select($row)
{
    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $domain_uuid = $row->domain_uuid;
    $routing = [];

    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
    foreach ($extensions as $ext) {
        $ext_desc_arr = [$ext->extension,$ext->effective_caller_id_name,$ext->description];
        $ext_desc = implode(' ',$ext_desc_arr);
        $routing[$ext->extension] = 'Extension - '.$ext_desc;
    }

    $ring_groups = \DB::connection('pbx')->table('v_ring_groups')->where('domain_uuid', $domain_uuid)->orderby('ring_group_extension')->get();
    foreach ($ring_groups as $ext) {
        $routing[$ext->ring_group_extension] = 'Ring Group - '.$ext->ring_group_name.' '.$ext->ring_group_extension;
    }

    $ivr_menus = \DB::connection('pbx')->table('v_ivr_menus')->where('domain_uuid', $domain_uuid)->orderby('ivr_menu_extension')->get();
    foreach ($ivr_menus as $ext) {
        $routing[$ext->ivr_menu_extension] = 'IVR Menu - '.$ext->ivr_menu_name.' '.$ext->ivr_menu_extension;
    }

    $ivr_menus = \DB::connection('pbx')->table('v_dialplans')->where('domain_uuid', $domain_uuid)->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->orderby('dialplan_number')->get();
    foreach ($ivr_menus as $ext) {
        $routing[$ext->dialplan_number] = 'Time Condition - '.$ext->dialplan_name.' '.$ext->dialplan_number;
    }

    return $routing;
}

function get_routing_type($domain_uuid, $extension = false)
{
    $routing_type = null;
    if (trim($extension) > '') {
        $extensions = \DB::connection('pbx')->table('v_extensions')
            ->where('domain_uuid', $domain_uuid)->where('extension', $extension)->count();
        if ($extensions) {
            $routing_type = 'extension';
        }

        $ring_groups = \DB::connection('pbx')->table('v_ring_groups')
            ->where('domain_uuid', $domain_uuid)->where('ring_group_extension', $extension)->count();
        if ($ring_groups) {
            $routing_type = 'ring_group';
        }


        $ivr_menus = \DB::connection('pbx')->table('v_ivr_menus')
            ->where('domain_uuid', $domain_uuid)->where('ivr_menu_extension', $extension)->count();
        if ($ivr_menus) {
            $routing_type = 'ivr_menu';
        }

        $time_condition = \DB::connection('pbx')->table('v_dialplans')
            ->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->where('domain_uuid', $domain_uuid)->where('dialplan_number', $extension)->count();
        if ($time_condition) {
            $routing_type = 'time_condition';
        }
    }

    return $routing_type;
}

function aftersave_pbxnumbers_set_routing($request)
{
    if (!empty($request->number_routing)) {
        $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
        $routing_type = get_routing_type($num->domain_uuid, $num->number_routing);

        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update(['routing_type' => $routing_type]);
    }

    if (empty($request->number_routing) || empty($request->domain_uuid)) {
        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update(['routing_type' => null]);
    }
}

function button_pbxcdr_details($request)
{
    $cdr = \DB::connection('pbx_cdr')->table('call_records_outbound_variables')->where('call_records_outbound_id', $request->id)->get()->first();

    $data = [
        'variables' => $cdr->variables,
    ];
    return view('__app.button_views.cdr_details', $data);
}

function button_pbx_number_import($request)
{
    $data['gateways'] = \DB::connection('pbx')->table('v_gateways')->select('gateway','gateway_uuid')->get();
    $data['conn'] = 'pbx';

    return view('__app.button_views.pbx_number_import', $data);
}

function delete_all_inbound_routes()
{
    $numbers = \DB::connection('pbx')->table('p_phone_numbers')->get();
    foreach ($numbers as $n) {
        $phone_number = $n->number;

        \DB::connection('pbx')->table('v_destinations')->where('destination_number', $phone_number)->delete();
        \DB::connection('pbx')->table('v_destination_numbers')->where('id', $phone_number)->delete();
        $inbound_dialplans =  \DB::connection('pbx')->table('v_dialplans')
            ->where('dialplan_number', $phone_number)
            ->where('dialplan_context', 'public')
            ->where('app_uuid', 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4')
            ->get();
        foreach ($inbound_dialplans as $dialplan) {
            \DB::connection('pbx')->table('v_dialplan_details')->where('dialplan_uuid', $dialplan->dialplan_uuid)->delete();
            \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $dialplan->dialplan_uuid)->delete();
        }
    }
}

function aftersave_phonenumbers_set_subscription_data($request)
{
    $c = session('mod_conn');

    $num = \DB::connection($c)->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $routing_type =  \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('cost_calculation')->first();

    //if ($routing_type == 'product') {
        if (!empty($num->domain_uuid)) {
            $account_id =  \DB::connection($c)->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('account_id')->first();
         
            $subs_count = \DB::connection($sub_conn)->table('sub_services')->where('detail', $num->number)->count();

            $phone_number = $num->number;
            if ('2787' == substr($phone_number, 0, 4) || '087' == substr($phone_number, 0, 3)) {
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
                'provision_type' => 'phone_number',
                'detail' => $phone_number,
                'product_id' => $subscription_product,
                'created_at' => date('Y-m-d H:i:s'),
                'date_activated' => date('Y-m-d H:i:s'),
                'bill_frequency' => 1,
                'renews_at' => date('Y-m-d H:i:s',strtotime('+1 month'))
            ];

            if ($subs_count == 0) {
                \DB::connection($sub_conn)->table('sub_services')->insert($subscription_data);
            } elseif ($subs_count == 1) {
                \DB::connection($sub_conn)->table('sub_services')->where('detail', $num->number)->update($subscription_data);
            } else {
                \DB::connection($sub_conn)->table('sub_services')->where('detail', $num->number)->delete();
                \DB::connection($sub_conn)->table('sub_services')->insert($subscription_data);
            }
            
            update_all_caller_ids($num->domain_uuid);
        } else {
            \DB::connection($sub_conn)->table('sub_services')->where('detail', $num->number)->where('status', '!=', 'Pending')->delete();
        }
    //}
}

function button_rejected_move_to_permanent_rejections($request)
{
    $rejected = \DB::connection('pbx')->table('mon_rejected')->where('id', $request->id)->get()->first();
    $rejected_calls = \DB::connection('pbx')->table('mon_rejected')->where('domain_name', $rejected->domain_name)->get();
    foreach ($rejected_calls as $rejected_call) {
        $data = (array) $rejected_call;
        unset($data['id']);
        \DB::connection('pbx')->table('mon_rejected_permanent')->insert($data);
        \DB::connection('pbx')->table('mon_rejected')->where('id', $rejected_call->id)->delete();
    }

    return json_alert('Done');
}

function schedule_network_blocked_notification()
{
    $domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->get();

    foreach ($domains as $domain) {
        $data = [];
        $data['attachments'] = [];

        $blocked_calls = \DB::connection('pbx')->table('mon_block_test_calls')->where('domain_name', $domain->domain_name)->where('duration', 7)->get();

        if (count($blocked_calls) > 0) {
            $file_title = str_replace('.', '', $domain->domain_name).'_blocked';
            $file_path = attachments_path().$file_title.'.xlsx';
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $blocked_calls =  json_decode(json_encode($blocked_calls), true);

            foreach ($blocked_calls as $i => $arr) {
                unset($blocked_calls[$i]['id']);
                unset($blocked_calls[$i]['gateway']);
            }


            $export = new App\Exports\CollectionExport();
            $export->setData($blocked_calls);

            Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

            $data['attachments'][] = $file_title.'.xlsx';


            $data['function_name'] = __FUNCTION__;
            if ($domain->domain_name == 'vca.cloudtools.co.za') {
                $data['force_to_email'] = 'paul@vca.co.za';
            }
            //$data['test_debug'] = 1;
            erp_process_notification($domain->account_id, $data);
        }
    }
}

function set_phone_number_product_codes(){
    
    $phone_numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('product_code','')->get();
    
    foreach($phone_numbers as $num){
        $phone_number = $num->number;
        $prefix = substr($phone_number, 0, 4);
        $prefix = str_replace('27', 0, $prefix);
        
        if ('2787' == substr($phone_number, 0, 4) || '087' == substr($phone_number, 0, 3)) {
            $product_id = 127; // 087
        } else {
            if (str_starts_with($phone_number, '2712786')) { // 012786
                $product_id = 176;
            } elseif (str_starts_with($phone_number, '2710786')) { // 010786
                $product_id = 176;
            } else { // geo
                $product_id = 128;
            }
        }    
        
        $product_code = \DB::table('crm_products')->where('id',$product_id)->pluck('code')->first();
        \DB::connection('pbx')->table('p_phone_numbers')->where('id',$num->id)->update(['product_code'=>$product_code,'product_id'=>$product_id,'prefix'=>$prefix]);
    }
}

function validate_pbx_numbers(){
      $numbers = \DB::connection('pbx')->table('p_phone_numbers')
  ->select('p_phone_numbers.number','v_domains.account_id')
  ->join('v_domains','v_domains.domain_uuid','=','p_phone_numbers.domain_uuid')
  ->whereNotNull('p_phone_numbers.domain_uuid')
  ->get();
  foreach($numbers as $n){
        $r = \DB::table('sub_services')->where('account_id',$n->account_id)->where('detail',$n->number)->where('status','!=','Deleted')->count();
        if(!$r){
        }
     }
     $ids = [];
  $numbers = \DB::connection('default')->table('sub_services')
  ->select('id','detail','account_id')
  ->where('status','!=','Deleted')
  ->where('provision_type','phone_number')
  ->get();
  foreach($numbers as $n){
        $r = \DB::connection('pbx')->table('p_phone_numbers')
  ->join('v_domains','v_domains.domain_uuid','=','p_phone_numbers.domain_uuid')
  ->whereNotNull('p_phone_numbers.domain_uuid')
  ->where('p_phone_numbers.number',$n->detail)
  ->where('v_domains.account_id',$n->account_id)
  ->count();
        if(!$r){
            $ids[] = $n->id;
        }
     }
     
    $numbers = \DB::connection('default')->table('sub_services')->whereIn('id',$ids)->get(); 
    $account_ids = $numbers->pluck('account_id')->toArray();
    $accounts = \DB::table('crm_accounts')->wherein('id',$account_ids)->pluck('company')->toArray();
}
