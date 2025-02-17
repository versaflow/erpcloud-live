<?php

function button_copy_rows_to_debug_cdr($request){
    
   \DB::connection('pbx_cdr')->table('call_records_debug_variables')->truncate();
   \DB::connection('pbx_cdr')->table('call_records_debug')->truncate();
    $cdr = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('id',$request->id)->get()->first();   
    $caller_id_number = $cdr->caller_id_number;
   
    $records = \DB::connection('pbx_cdr')->table('call_records_outbound')
    //->where('caller_id_number',$caller_id_number)
    ->where('id','<=',$cdr->id)
    ->orderBy('id','desc')->limit(100)->get();
    $record_ids = $records->pluck('id')->toArray();
    foreach($records as $record){
        $row = (array) $record;
        \DB::connection('pbx_cdr')->table('call_records_debug')->insert($row);    
    }
    $variables = \DB::connection('pbx_cdr')->table('call_records_outbound_variables')->whereIn('call_records_outbound_id',$record_ids)->get();
    foreach($variables as $record){
        $row = (array) $record;
        \DB::connection('pbx_cdr')->table('call_records_debug_variables')->insert($row);    
    }
    return json_alert('Done');
    
}


function button_call_records_debug_details($request)
{
   // aa($request->all());
    $cdr = \DB::connection('pbx_cdr')->table('call_records_debug_variables')->where('call_records_outbound_id', $request->id)->first();
    if(empty($cdr) || empty($cdr->id) || empty($cdr->variables)){
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


function button_call_records_debug_truncate()
{
   \DB::connection('pbx_cdr')->table('call_records_debug_variables')->truncate();
   \DB::connection('pbx_cdr')->table('call_records_debug')->truncate();
   return json_alert('Done');
}


function button_cdr_set_caller_id($request)
{
    $num = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('id', $request->id)->get()->first();
    $data['outbound_caller_id_number'] = $num->caller_id_number;
    $user_extension = \DB::table('erp_users')->where('id', session('user_id'))->whereNotNull('pbx_extension')->pluck('pbx_extension')->first();
    \DB::connection('pbx')->table('v_extensions')->where('extension', $user_extension)->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    // \DB::connection('pbx')->table('v_extensions')->where('extension', '300')->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    $pbx = new FusionPBX();

    $key = 'directory:'.$user_extension.'@'.'pbx.cloudtools.co.za';
    $pbx->portalCmd('portal_aftersave_extension', $key);
    return json_alert('Done');
}




function button_phone_numbers_reset_admin_caller_id($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $data['outbound_caller_id_number'] = '27105007500';
    $user_extension = \DB::table('erp_users')->where('id', session('user_id'))->whereNotNull('pbx_extension')->pluck('pbx_extension')->first();
    \DB::connection('pbx')->table('v_extensions')->where('extension', $user_extension)->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    $pbx = new FusionPBX();

    $key = 'directory:'.$user_extension.'@'.'pbx.cloudtools.co.za';
    $pbx->portalCmd('portal_aftersave_extension', $key);
    //\DB::connection('pbx')->table('v_extensions')->where('extension', '101')->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    return json_alert('Done');
}

function set_admin_caller_id()
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $data['outbound_caller_id_number'] = '27105007500';
    $user_extension = \DB::table('erp_users')->where('id', session('user_id'))->whereNotNull('pbx_extension')->pluck('pbx_extension')->first();
    \DB::connection('pbx')->table('v_extensions')->where('extension', $user_extension)->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    $pbx = new FusionPBX();

    $key = 'directory:'.$user_extension.'@'.'pbx.cloudtools.co.za';
    $pbx->portalCmd('portal_aftersave_extension', $key);
}

function cdr_last_dialed_number_by_prefix($prefix)
{
    return \DB::connection('pbx_cdr')->table('call_records_outbound')->select('callee_id_number')->where('duration', '>', 0)->where('hangup_cause', 'NORMAL_CLEARING')->where('callee_id_number', 'like', $prefix.'%')->orderBy('id', 'desc')->pluck('callee_id_number')->first();
}
function cdr_last_dialed_number_by_network($network)
{
    return \DB::connection('pbx_cdr')->table('call_records_outbound')->select('callee_id_number')->where('duration', '>', 0)->where('hangup_cause', 'NORMAL_CLEARING')->where('summary_destination', $network)->orderBy('id', 'desc')->pluck('callee_id_number')->first();
}

function button_call_test_fixed_telkom($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $cdr_num = cdr_last_dialed_number_by_network('fixed telkom');
    if (!$cdr_num) {
        return json_alert('fixed telkom number not found', 'error');
    }
    $result = pbx_call($cdr_num, 12, 'account', $num->number);

    if (true === $result) {
        return json_alert('Call sent to PBX');
    } else {
        return json_alert($result, 'error');
    }
}

function button_call_test_fixed_sharecall($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $cdr_num = cdr_last_dialed_number_by_network('fixed sharecall');
    if (!$cdr_num) {
        return json_alert('fixed sharecall not found', 'error');
    }
    $result = pbx_call($cdr_num, 12, 'account', $num->number);

    if (true === $result) {
        return json_alert('Call sent to PBX');
    } else {
        return json_alert($result, 'error');
    }
}

function button_call_test_mobile_telkom($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $cdr_num = cdr_last_dialed_number_by_network('mobile telkom');
    if (!$cdr_num) {
        return json_alert('mobile telkom number not found', 'error');
    }
    $result = pbx_call($cdr_num, 12, 'account', $num->number);

    if (true === $result) {
        return json_alert('Call sent to PBX');
    } else {
        return json_alert($result, 'error');
    }
}

function button_call_test_mobile_vodacom($request)
{
   
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $cdr_num = cdr_last_dialed_number_by_network('mobile vodacom');
    if (!$cdr_num) {
        return json_alert('mobile vodacom number not found', 'error');
    }
    $result = pbx_call($cdr_num, 12, 'account', $num->number);

    if (true === $result) {
        return json_alert('Call sent to PBX');
    } else {
        return json_alert($result, 'error');
    }
}

function button_call_test_mobile_mtn($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $cdr_num = cdr_last_dialed_number_by_network('mobile mtn');
    if (!$cdr_num) {
        return json_alert('mobile mtn number not found', 'error');
    }
    $result = pbx_call($cdr_num, 12, 'account', $num->number);

    if (true === $result) {
        return json_alert('Call sent to PBX');
    } else {
        return json_alert($result, 'error');
    }
}

function button_call_test_mobile_cellc($request)
{
    $num = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $cdr_num = cdr_last_dialed_number_by_network('mobile cellc');
    if (!$cdr_num) {
        return json_alert('mobile cellc number not found', 'error');
    }
    $result = pbx_call($cdr_num, 12, 'account', $num->number);

    if (true === $result) {
        return json_alert('Call sent to PBX');
    } else {
        return json_alert($result, 'error');
    }
}


function aftersave_pbx_debug_set_active($request)
{
    if (!empty($request->active) && $request->active == 1) {
        \DB::connection('pbx')->table('p_debug_templates')->where('id', '!=', $request->id)->update(['active' => 0]);
    }
    if (!empty($request->sql_active) && $request->sql_active == 1) {
        \DB::connection('pbx')->table('p_debug_templates')->where('id', '!=', $request->id)->update(['sql_active' => 0]);
    }
}



function button_pbx_debug_clear()
{
    $file_text = '';
    $file = "/var/www/html/debug.log";
    $cmd = 'echo -n "'.$file_text.'" > '.$file;

    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    return json_alert('Debug file cleared');
}

function button_pbx_debug_view()
{
    $debug = \DB::connection('pbx')->table('p_debug_templates')->where('active', 1)->get()->first();
    $data = [];
    if (!empty($debug)) {
        $data = (array) $debug;
    }

    return view('__app.button_views.cdr_log', $data);
}

function button_pbx_debug_set_active($request)
{
    \DB::connection('pbx')->table('p_debug_templates')->where('id', $request->id)->update(['active' => 1]);
    \DB::connection('pbx')->table('p_debug_templates')->where('id', '!=', $request->id)->update(['active' => 0]);
    return json_alert('Done');
}

function button_pbx_debug_set_deactive($request)
{
    \DB::connection('pbx')->table('p_debug_templates')->update(['active' => 0]);
    return json_alert('Done');
}
