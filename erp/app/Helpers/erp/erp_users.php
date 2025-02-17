<?php

function button_edit_account_user($request){
    $user = \DB::table('erp_users')->where('account_id', $request->id)->get()->first();
    return redirect()->to(get_menu_url_from_table('erp_users').'/edit/'.$user->id);
}

function sync_admin_users(){
    
    if(is_main_instance()){
        $admin_users = \DB::table('erp_users')->where('account_id', 1)->get();
        
        $admin_usernames = $admin_users->where('is_deleted',0)->pluck('username')->toArray();
        $conns = \DB::table('erp_instances')->where('installed',1)->where('sync_erp',1)->where('id','!=',1)->pluck('db_connection')->toArray();
        foreach ($conns as $c) {
            \DB::connection($c)->table('erp_users')->where('account_id',1)->whereNotIn('username',$admin_usernames)->delete();
        }
        foreach($admin_users as $user){
          
            $instance_ids = \DB::table('erp_instance_user_access')->where('user_id', $user->id)->pluck('instance_id')->toArray();
            $conns = \DB::table('erp_instances')->where('installed',1)->where('sync_erp',1)->where('id','!=',1)->whereIn('id', $instance_ids)->pluck('db_connection')->toArray();
            foreach ($conns as $c) {
               
                
                $exists = \DB::connection($c)->table('erp_users')->where('username', $user->username)->count();
                if (!$exists) {
                    $data = (array) $user;
                    unset($data['id']);
                    \DB::connection($c)->table('erp_users')->insert($data);
                }else{
                     \DB::connection($c)->table('erp_users')->where('username', $user->username)->update(['full_name'=>$user->full_name,'pbx_extension'=>$user->pbx_extension,'password'=>$user->password,'email'=>$user->email,'role_id'=>$user->role_id,'is_deleted'=>$user->is_deleted,'active'=>$user->active]);   
                }
                
            } 
        }
    
        $instances = \DB::table('erp_instances')->where('installed',1)->where('sync_erp',1)->where('id', '!=',1)->get();
        foreach($instances as $i){
            $users = \DB::connection($i->db_connection)->table('erp_users')->where('account_id',1)->get();
            foreach($users as $user){
                $access_ids = get_admin_instance_access($user->username);  
                if(!in_array($i->id,$access_ids)){
                  
                   // \DB::connection($i->db_connection)->table('erp_users')->where('id',$user->id)->delete();
                }
            }
        }
    }        
}


function aftersave_users_set_access($request){
    /*
    if(!empty($request->new_record)){
        if($request->role_id!=54){
        $instance_ids = [1,2,11];
        foreach($instance_ids as $instance_id){
            \DB::table('erp_instance_user_access')->updateOrInsert(['instance_id'=>$instance_id,'user_id'=>$request->id],['instance_id'=>$instance_id,'user_id'=>$request->id]);
        }
        }
    }
    */
}



function afterdelete_users_set_access($request){
    \DB::table('erp_users')->where('account_id', 1)->where('is_deleted',1)->update(['active'=>0]);
}

function afterdelete_user_access_copy_admin_users(){
    sync_admin_users();
}

function aftersave_user_access_copy_admin_users(){
    sync_admin_users();
}

function aftersave_user_copy_admin_users(){
    sync_admin_users();
}

function aftersave_users_update_pbx_extensions($request)
{
    update_admin_pbx_extensions();
}


function afterdelete_users_update_pbx_extensions($request)
{
    update_admin_pbx_extensions();
}


function update_admin_pbx_extensions(){
    if (session('instance')->id == 1) {
        $admin = dbgetaccount(1);
        \DB::connection('pbx')->table('v_extensions')
            ->where('accountcode', 'pbx.cloudtools.co.za')
            ->update(['outbound_caller_id_name'=>$admin->company]);
            
        $admin_users_extensions = \DB::table('erp_users')->where('is_deleted', 0)->where('account_id', 1)->where('pbx_extension', '>', '')->pluck('pbx_extension')->toArray();
       
        $unused_ext_update_data = [
            'effective_caller_id_name'=>'Not Used',
            //'mobile_app_number'=>null,
            'forward_user_not_registered_enabled' => 'false',
            'forward_user_not_registered_destination' => '',
            'forward_busy_destination' => '',
            'forward_no_answer_destination' => '',
        ];
        \DB::connection('pbx')->table('v_extensions')
        ->where('accountcode', 'pbx.cloudtools.co.za')
        ->whereNotIn('extension', $admin_users_extensions)
        ->where('extension','<',500)
        ->update($unused_ext_update_data);
        
        $admin_users = \DB::table('erp_users')->where('is_deleted', 0)->where('account_id', 1)->where('pbx_extension', '>', '')->get();
      
        foreach ($admin_users as $admin_user) {
            
            $forward_number = $admin_user->phone;
            $employee_mobile = \DB::table('hr_employees')->where('user_id',$admin_user->id)->where('status','!=','Deleted')->pluck('mobile')->first();
            if($employee_mobile){
                $forward_number = $employee_mobile;
            }
            
            
            \DB::connection('pbx')->table('v_extensions')
            ->where('accountcode', 'pbx.cloudtools.co.za')
            ->where('extension', $admin_user->pbx_extension)
            ->update(['effective_caller_id_name' => $admin_user->full_name,'effective_caller_id_number'=>$admin_user->pbx]);

            if ($forward_number) {
                //\DB::connection('pbx')->table('v_extensions')
                //->where('mobile_app_number', $forward_number)
                //->update(['mobile_app_number' => null]);

                //\DB::connection('pbx')->table('v_extensions')
                //->where('accountcode', 'pbx.cloudtools.co.za')
                //->where('extension', $admin_user->pbx_extension)
                //->update(['mobile_app_number' => $forward_number]);

                $call_forward = [
                    'forward_user_not_registered_enabled' => 'true',
                    'forward_all_destination' => $forward_number,
                    'forward_user_not_registered_destination' => $forward_number,
                    'forward_busy_destination' => $forward_number,
                    'forward_no_answer_destination' => $forward_number,
                ];

                \DB::connection('pbx')->table('v_extensions')
                ->where('accountcode', 'pbx.cloudtools.co.za')
                ->where('extension', $admin_user->pbx_extension)
                ->update($call_forward);
            }
                $pbx = new FusionPBX();
                $ext = \DB::connection('pbx')->table('v_extensions')
                ->where('accountcode', 'pbx.cloudtools.co.za')
                ->where('extension', $admin_user->pbx_extension)
                ->get()->first();

                $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
                $result = $pbx->portalCmd('portal_aftersave_extension', $key);
            
        }
    }
}


function afterdelete_employee_user($request)
{
    $employee = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
    $db = new DBEvent;
    $db->setTable('erp_users');
    $data = [];
    $data['id'] = $employee->user_id;
    $request = new \Illuminate\Http\Request($data);
    $request->setMethod('POST');
    $db->deleteRecord($request);
    \DB::table('hr_employees')->where('id', $request->id)->update(['user_id' => 0]);
}

function afterdelete_employee_delete_payroll($request)
{
   
    \DB::table('hr_loans')->where('employee_id', $request->id)->update(['is_deleted'=>1]);
    \DB::table('acc_payroll')->where('employee_id', $request->id)->update(['is_deleted'=>1]);
    \DB::table('hr_leave')->where('employee_id', $request->id)->update(['is_deleted'=>1]);
}

function aftersave_set_user_fullname($request)
{
 
    $users = \DB::table('erp_users')->where('full_name', '')->get();
    foreach ($users as $user) {
        $account = dbgetaccount($user->account_id);
        if (!empty($account->contact)) {
            $full_name = $account->contact;
        } else {
            $full_name = $account->company;
        }
        \DB::table('erp_users')->where('id', $user->id)->update(['full_name' => $full_name]);
    }
}

function aftersave_send_login_details($request)
{
    $id = $request->id;
    $user = \DB::table('erp_users')->where('id', $id)->get()->first();
    /////// SEND NEW LOGIN DETAILS
    if (isset($request->password) && $request->password > '') {
        $pass = $request->password;

        if (!empty($user)) {
            $hashed_password = \Hash::make($pass);

            \DB::table('erp_users')->where('id', $user->id)->update(['password' => $hashed_password]);
            $user_email = $user->email;
            $account = dbgetaccount($user->account_id);
            if (1 == $account->partner_id) {
                $portal = 'http://'.$_SERVER['HTTP_HOST'];
            } else {
                $portal = 'http://'.session('instance')->alias;
            }
            $function_variables = get_defined_vars();
            $data['function_name'] = __FUNCTION__;

            $data['username'] = $user->username;

            $data['login_url'] = $portal;

            $data['password'] = $pass;
            if (1 == $account->partner_id) {
                $data['portal_name'] = session('instance')->name;
            } else {
                $reseller = dbgetaccount($account->partner_id);
                $data['portal_name'] = $reseller->company;
            }

            erp_process_notification($user->account_id, $data, $function_variables);
            \DB::table('erp_user_sessions')->where('user_id', $id)->delete();
        }
    }

    if (empty($user->role_id)) {
        $account = dbgetaccount($user->account_id);
        if (1 == $account->id) {
            $role_id = '3';
        } elseif ('reseller_user' == $account->type || 'customer' == $account->type) {
            $role_id = '21';
        } elseif ('reseller' == $account->type) {
            $role_id = '11';
        }
        \DB::table('erp_users')->where('id', $id)->update(['role_id' => $role_id]);
    }
}

function beforesave_check_username_unique($request)
{
    $id = (!empty($request->id)) ? $request->id : null;
    if (!$id && empty($request->password)) {
        return 'Password required';
    }
    if ($id) {
        $exists = \DB::table('erp_users')->where('id', '!=', $request->id)->where('username', $request->username)->count();
    } else {
        $exists = \DB::table('erp_users')->where('username', $request->username)->count();
    }
    
    
    if ($exists) {
        return 'Username not unique.  Please try another one.';
    }
    /*
    $phone_as_username = false;
    if($id){
        $user = \DB::table('erp_users')->where('id', $request->id)->get()->first();
        $account = dbgetaccount($user->account_id);
        if($account->phone == $request->username){
            $phone_as_username = true;
        }
    }
    if(!$phone_as_username){
        if (empty($request->username) || !filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            return 'Username must be set to phone or email';
        }
    }
    */
}

function button_users_reset_login($request)
{
    $user = \DB::table('erp_users')->where('id', $request->id)->get()->first();


    if (erp_email_valid($user->email)) {
        $data['user_email'] = $user->email;
    }
    $account = dbgetaccount($user->account_id);
    if (1 == $account->partner_id) {
        $portal = 'http://'.$_SERVER['HTTP_HOST'];
    } else {
        $portal = 'http://'.session('instance')->alias;
    }
    $token = bin2hex(random_bytes(36));
    \DB::table('erp_users')->where('id', $request->id)->update(['activation_reset' => $token]);
    $data['reset_link'] = '<a href="'.$portal.'/user/reset/'.$token.'" target="_blank" >Reset Password</a>';
    $data['username'] = $user->username;
    $function_variables = get_defined_vars();
    $data['portal'] = $portal;
    $data['internal_function'] = 'reset_password_token';
    $result = erp_process_notification($user->account_id, $data, $function_variables);
    if (str_contains($result, 'Sent')) {
        return json_alert('Reset link sent.');
    } else {
        return json_alert($result, 'warning');
    }
}

function button_users_send_new_password($request)
{
    $id = $request->id;
    $user = \DB::table('erp_users')->where('id', $id)->get()->first();
    /////// SEND NEW LOGIN DETAILS

    $pass = generate_strong_password();
    $hashed_password = \Hash::make($pass);
    \DB::table('erp_users')->where('id', $user->id)->update(['password' => $hashed_password]);
    $user_email = $user->email;
    $account = dbgetaccount($user->account_id);
    if (1 == $account->partner_id) {
        $portal = 'http://'.$_SERVER['HTTP_HOST'];
    } else {
        $portal = 'http://'.session('instance')->alias;
    }
    $function_variables = get_defined_vars();
    $data['internal_function'] = 'create_account_settings';

    $data['username'] = $user->username;

    $data['login_url'] = $portal;

    $data['password'] = $pass;
    if (1 == $account->partner_id) {
        $data['portal_name'] = 'Cloud Telecoms';
    } else {
        $reseller = dbgetaccount($account->partner_id);
        $data['portal_name'] = $reseller->company;
    }

    erp_process_notification($user->account_id, $data, $function_variables);
    \DB::table('erp_user_sessions')->where('user_id', $id)->delete();
    return json_alert('Done');
}

function restore_customer_users(){
    $users = \DB::table('erp_users')->where('account_id','>',1)->where('active',0)->get();
    foreach($users as $user){
        $active = \DB::table('crm_accounts')->where('id',$user->account_id)->where('status','!=','Deleted')->count();
        if($active){
            \DB::table('erp_users')->where('id',$user->id)->update(['active'=>1]);    
        }
    }    
}


function aftersave_user_role_change_logout($request){
    if(empty($request->new_record)){
        $beforesave_row = session('event_db_record');
        if($beforesave_row->role_id != $request->role_id){
            workboard_layout_set_tracking_per_user(session('instance')->id);
      
            \DB::table('erp_user_sessions')->where('user_id', $request->id)->delete();
        }
    }
}

function button_users_send_admin_extension_details($request){
    $id = $request->id;
    $user = \DB::table('erp_users')->where('id', $id)->get()->first();
    if($user->account_id != 1){
        return json_alert('No access', 'warning');
    }
    if(empty($user->pbx_extension)){
        return json_alert('No extension set', 'warning');
    }
    
   
    
    $product = \DB::table('crm_products')->where('id', 130)->get()->first();
    
    if (empty($product)) {
    return json_alert('Invalid product', 'error');
    }
    $provision_plan = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->get()->first();
    $provision_plan_name = $provision_plan->name;
    $provision_plan_id = $provision_plan->id;
    
    if (empty($provision_plan_name)) {
    return json_alert('Invalid activation plan', 'error');
    }
    $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $provision_plan_id)->where('email_id', '>', '')->orderby('step', 'desc')->pluck('email_id')->first();
    if (empty($email_id)) {
    return json_alert('No setup instructions available for this product', 'error');
    }
    
    $customer = dbgetaccount(12);
    
    $data['detail'] = $user->pbx_extension;
    $data['product'] = ucwords(str_replace('_', ' ', $product->code));
    $data['product_code'] = $product->code;
    $data['product_description'] = $product->name;
    $data['portal_url'] = get_whitelabel_domain(1);
    
    $ext = \DB::connection('pbx')->table('v_extensions')->where('extension',$user->pbx_extension)->where('accountcode','pbx.cloudtools.co.za')->get()->first();
    $data['username'] = $ext->extension;
    $data['password'] = $ext->password;  
    $data['domain_name'] = $ext->accountcode;
    $data['user_email'] = $user->email;
    $data['force_to_email'] = $user->email;
    
    $data['activation_email'] = true;
    return email_form($email_id, 12, $data);    
    

}
