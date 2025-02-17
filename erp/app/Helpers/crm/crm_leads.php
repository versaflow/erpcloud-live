<?php

function onload_leads_check_documents(){
    $accounts = \DB::table('crm_accounts')->where('type','lead')->where('status','!=','Deleted')->get();
    foreach($accounts as $account){
        $has_order = DB::table('crm_documents')->where('account_id', $account->id)->where('doctype', 'Order')->where('reversal_id', 0)->count();
        $has_invoice = DB::table('crm_documents')->where('account_id', $account->id)->where('doctype', 'Tax Invoice')->where('reversal_id', 0)->count();
        
        if ($has_invoice || $has_order) {
        if (1 == $account->partner_id) {
        DB::table('crm_accounts')->where('id', $account->id)->update(['type' => 'customer', 'status' => 'Enabled']);
        } else {
        DB::table('crm_accounts')->where('id', $account->id)->update(['type' => 'reseller_user', 'status' => 'Enabled']);
        }
        create_account_settings($account->id);
        }    
    }
    
}

function aftersave_leads_send_notification($request){
    if(!empty($request->new_record)){
        $lead_data = '';
        foreach($request->all() as $k => $v){
            if(in_array($k,['new_record','id'])){
                continue;    
            }
            if(!empty($v)){
                if($k == 'user_id'){
                    $username = \DB::table('erp_users')->where('id',$v)->pluck('full_name')->first();
                    $lead_data .= 'User: '.ucwords($username).'<br>'; 
                }else{
                    $lead_data .= ucwords(str_replace('_',' ',$k)).': '.ucwords($v).'<br>';    
                }
            }
        }
        
        admin_email('New Lead created', $lead_data);
    }
}

function button_coldcalling_copy_to_lead($request)
{
    $lead = \DB::table('crm_cold_calling_list')->where('id', $request->id)->get()->first();

    $db = new DBEvent();
    $data = (array) $lead;
    unset($data['id']);
    $data['type'] = 'prospect';
    $data['marketing_channel_id'] = 41;
    $db->setTable('crm_accounts');
    $result = $db->save($data);
    if (!is_array($result) || empty($result['id'])) {
        return $result;
    }
    return json_alert('Done');
}

function button_coldcalling_call_number($request)
{
    $lead = \DB::table('crm_cold_calling_list')->where('id', $request->id)->get()->first();
    if (empty($lead->phone)) {
        return json_alert('Invalid Number', 'warning');
    }
    try {
        $number = phone($lead->phone, ['ZA','US','Auto']);

        $number = $number->formatForMobileDialingInCountry('ZA');
        if (strlen($number) != 10) {
            return json_alert('Invalid Number', 'warning');
        }
    } catch (\Throwable $ex) {  exception_log($ex);
        return json_alert('Invalid Number', 'warning');
    }
    pbx_call($number, $request->id, 'lead');
    return json_alert('Call sent to pbx');
}

function button_coldcalling_send_email($request)
{
    $lead = \DB::table('crm_cold_calling_list')->where('id', $request->id)->get()->first();
    $url = '/email_form/lead/'.$lead->id.'/'.$lead->email;

    return redirect()->to($url);
}
