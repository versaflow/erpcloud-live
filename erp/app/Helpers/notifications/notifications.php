<?php
function getVariablesFromView($contents)
{
    // $templatePath = '/resources/views/mails/greeting.blade.php'
    

    $re = '/{{[\ ]*\$[a-zA-Z]+[\ ]*}}/m';
    preg_match_all($re, $contents, $matches, PREG_SET_ORDER, 0);
    $flatternArray = [];
    array_walk_recursive($matches, function ($a) use (&$flatternArray) {$flatternArray[] = $a;});
    $variables = str_replace(['{{', '}}', '$', ' '], '', $flatternArray);

    return $variables;
    // ['customerFirstName', 'customerLastName']
}

function get_email_variables($input){


  $pattern = '/\$\w+(?:->\w+)?/'; // Regular expression pattern to match strings starting with $

  preg_match_all($pattern, $input, $matches); // Perform a global regular expression match

  $result = $matches[0]; // Extract the matched strings

  return $result;

    
}

function button_send_test_notification($request)
{
    $email = \DB::connection('default')->table('crm_email_manager')->where('id', $request->id)->get()->first();
    $data['subject'] = $email->name;
    $data['message'] = $email->message;
    $data['msg'] = $email->message;

    $user_email = \DB::table('erp_users')->where('id',session('user_id'))->where('account_id',1)->pluck('email')->first();
    $data['from_email'] = 'helpdesk@telecloud.co.za';
    $data['to_email'] = $user_email;
    if(!is_dev())
    $data['bcc_email'] = 'ahmed@telecloud.co.za';


    $data['notification_id'] = $request->id;
    //$data['formatted'] = 1;
    $data['form_submit'] = 1;
   // $data['test_debug'] = 1;

   // $data['disable_blend'] = 1;

    $variableNames = get_email_variables($email->message);
    
    foreach($variableNames as $variable){
        if(str_contains($variable,'customer') || str_contains($variable,'partner')){
            continue;    
        }
        $variable = str_replace('$','',$variable);
        if(str_contains($variable,'->')){
            $obj_var_arr = explode('->',$variable); 
            $obj_name = $obj_var_arr[0];
         
            if(!isset($data[$obj_var_arr[0]])){
              $data[$obj_var_arr[0]] = (object) [];
            }
        }else{
            $data[$variable] = '['.$variable.']';
        }    
    }
    
    foreach($variableNames as $variable){
        if(str_contains($variable,'customer') || str_contains($variable,'partner')){
            continue;    
        }
        $variable = str_replace('$','',$variable);
        if(str_contains($variable,'->')){
            $obj_var_arr = explode('->',$variable); 
            $obj_name = $obj_var_arr[0];
            $obj_field = $obj_var_arr[1];
            
            $data[$obj_name]->{$obj_field} = '['.str_replace('->','_',$variable).']';
     
        }   
    }

//  return json_alert('Testing');



    $result = erp_email_send(1, $data);

    if ($result == 'Sent') {
        return json_alert('Sent');
    } else {
        return json_alert($result, 'error');
    }
}

function button_send_test_activation($request)
{
    $activation_plan = \DB::connection('default')->table('sub_activation_plans')->where('id', $request->id)->get()->first();
    $email = \DB::connection('default')->table('crm_email_manager')->where('id', $activation_plan->email_id)->get()->first();
    $data['subject'] = $email->name;
    $data['message'] = $email->message;
    $data['msg'] = $email->message;

    $user_email = \DB::table('erp_users')->where('id',session('user_id'))->where('account_id',1)->pluck('email')->first();
    $data['from_email'] = 'helpdesk@telecloud.co.za';
    $data['to_email'] = $user_email;
    $data['bcc_email'] = 'ahmed@telecloud.co.za';

    $data['notification_id'] = $activation_plan->email_id;
    //$data['formatted'] = 1;
    $data['form_submit'] = 1;
   // $data['test_debug'] = 1;

    $data['disable_blend'] = 1;

    $result = erp_email_send(1, $data);

    if ($result == 'Sent') {
        return json_alert('Sent');
    } else {
        return json_alert($result, 'error');
    }
}



function erp_process_notification($account_id, $data = [], $function_variables = [], $conn = false)
{
    $app_ids = get_installed_app_ids();
    if (!empty($data['activation_email'])) {
        // ACTIVATION
        $notification = \DB::connection('default')->table('crm_email_manager')->where('id', $data['notification_id'])->get()->first();
    } elseif (!empty($data['function_name'])) {
        // EVENT
        $data['notification_id'] = \DB::connection($lookup_conn)->table('erp_form_events')->where('function_name', $data['function_name'])->pluck('email_id')->first();
        $notification = \DB::connection('default')->table('crm_email_manager')->where('id', $data['notification_id'])->get()->first();
    } elseif (!empty($data['internal_function'])) {
        // FUNCTION
        $data['notification_id'] = \DB::connection($lookup_conn)->table('crm_email_manager')->where('internal_function', $data['internal_function'])->pluck('id')->first();
        $notification = \DB::connection('default')->table('crm_email_manager')->where('id', $data['notification_id'])->get()->first();
    } elseif (!empty($data['notification_id'])) {
        // FORM
        $notification = \DB::connection('default')->table('crm_email_manager')->where('id', $data['notification_id'])->get()->first();
    }
    $is_debtor_email = $notification->debtor_email;
    if(!$is_debtor_email)
        $is_debtor_email = \DB::connection('default')->table('crm_debtor_status')->where('email_id',$notification->id)->count();
    
    if(!in_array(session('instance')->id,[1,2])){
        $is_debtor_email = 0;
    }
    
    if (is_array($function_variables) && count($function_variables) > 0) {
        $data = array_merge($function_variables, $data);
    }
   
    $notification_type = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('notification_type')->first();
    
    $send_email = true;
    $send_sms = false;
    if($notification_type == 'sms'){
        $send_email = false;
        $send_sms = true;
    }
    
    if($notification_type == 'email'){
        $send_email = true;
        $send_sms = false;
    }
    
    
    if($is_debtor_email){
        $debtor_notifications = get_admin_setting('debtor_notifications');
        if($debtor_notifications == 'both'){
            $send_email = true;
            $send_sms = true;
            
        }elseif($debtor_notifications == 'email'){
            $send_email = true;
            $send_sms = false;
            
        }elseif($debtor_notifications == 'sms'){
            $send_email = false;
            $send_sms = true;
        }
    }
    
    if(!in_array(14, $app_ids)){
        $send_sms = false;
        $send_email = true;
    }
    
    $use_sms = false;
    
    if ($send_sms) {
        $partner_id = \DB::table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();
        if ($partner_id == 1 && !empty($notification->message)) {
            if(!empty($data['stack_trace'])){
                $data['stack_trace'] = '';
            }
            
            $account_company = \DB::table('crm_accounts')->where('id', $account_id)->pluck('company')->first();
            $partner_company = \DB::table('crm_accounts')->where('id', 1)->pluck('company')->first();
            //aa($sms_message);
            // debtor sms
            if($is_debtor_email && $notification->internal_function != 'outstanding_balance_email'){
                $sms_message = $notification->name.'. '.strip_tags($notification->message).' View your statement here: https://'.session('instance')->domain_name.'/statement_download/'.$account_id;
            }
            $email_id = 0;
            if($data['notification_id']){
                $email_id = $data['notification_id'];
            }
            $sms_message = $notification->message;
            aa($data['attachments']);
            if (!empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    aa($attachment);
                   $sms_message .= ' '.attachments_url().$attachment;
                }
            }
            if (!empty($data['attachment'])) {
                $sms_message .= ' '.attachments_url().$data['attachment'];
            }
            
            $sms_message = erp_email_blend('Hi '.$account_company.',<br /><br />'.$sms_message.'<br /><br />Regards,<br />'.$partner_company, $data);
            $sms_message = str_replace(['<br />','<br>','<br/>'],PHP_EOL,$sms_message);
            $sms_message = str_replace(['&nbsp;'],' ',$sms_message);
            $sms_message = preg_replace('/[^\x0A\x20-\x7E\xC0-\xD6\xD8-\xF6\xF8-\xFF]/','',$sms_message);
            $sms_message = str_replace(chr(194)," ",$sms_message);
            $sms_message = strip_tags($sms_message);
            
           // aa($sms_message);
            
            if ($data['test_debug'] ) {
                $phone_number = '0824119555';
                queue_sms(1, $phone_number, $sms_message, 1, 1, $email_id, $account_id);
               
            } elseif ($data['sms_phone_number']) {
                $phone_number = $data['sms_phone_number'];
                queue_sms(1, $phone_number, $sms_message, 1, 1, $email_id, $account_id);
            } else {
                $phone_number = valid_za_mobile_number($account->phone);
                if (!$phone_number) {
                    $account_contacts = get_account_contacts($account_id);
                    foreach($account_contacts as $account_contact){
                        $phone_number = valid_za_mobile_number($account_contact->phone);
                        if($phone_number){
                            break;
                        }
                    }
                }

                if ($phone_number) {
                    if($is_debtor_email){
                        queue_sms(1, $phone_number, $sms_message, 1, 1, $email_id, $account_id);
                    }else{
                        $opt_out = \DB::connection('default')->table('isp_sms_optout')->where('number', $phone_number)->count();
                        if (!$opt_out) {
                            queue_sms(1, $phone_number, $sms_message, 1, 1, $email_id, $account_id);
                        }
                    }
                }
            }
        }
    }
    
    if($send_email){
        $result = erp_email_send($account_id, $data, $function_variables, $conn);
        return $result;
    }

    return 'Sent';
}
