<?php

// username to use email or phone, unique per customer type
// reseller_users to use username

function schedule_set_user_active_from_accounts(){
    $sql = "UPDATE erp_users 
    JOIN crm_accounts ON erp_users.account_id=crm_accounts.id
    SET active=1 
    WHERE erp_users.account_id > 1 and crm_accounts.status='Enabled'";
    \DB::statement($sql);
    $sql = "UPDATE erp_users 
    JOIN crm_accounts ON erp_users.account_id=crm_accounts.id
    SET active=0 
    WHERE erp_users.account_id > 1 and crm_accounts.status='Deleted'";
    \DB::statement($sql);
}

function send_username_update_email($account, $username, $temp_username = false,$reseller_user = false){
    $data = [];
    if (!$reseller_user) {
        $data['login_url'] = 'http://'.$_SERVER['HTTP_HOST'];
    } else {
        $data['login_url'] = 'http://'.session('instance')->alias;
    }
  
    $data['internal_function'] = 'username_update';

    $data['username'] = $username;
    $data['temp_username'] = $temp_username; 
    $data['reseller_user'] = $reseller_user; 
    $data['test_debug'] = 1; 
    $data['company_name'] = $account->company;
    $send_to_id = $account->id;
    if($reseller_user){
        $send_to_id = $account->partner_id;
    }

    erp_process_notification($send_to_id, $data);
}

function set_user_to_temp_username($user_id){
    $u = generate_strong_password().rand(0, 9).rand(0, 9).'@example.com';
    $u_exists = \DB::table('erp_users')->where('username',$u)->count();
    while($u_exists){
        $u = generate_strong_password().rand(0, 9).rand(0, 9).'@example.com';
        $u_exists = \DB::table('erp_users')->where('username',$u)->count();
    }
    \DB::table('erp_users')->where('id',$user_id)->update(['username'=>$u]);
    return $u;
}

function reset_usernames_from_email_and_phone(){
 
    $deleted_account_ids = \DB::table('crm_accounts')->where('status','Deleted')->pluck('id')->toArray();
    \DB::table('erp_users')->whereIn('account_id',$deleted_account_ids)->update(['active'=>0,'is_deleted'=>1]);
    
    // update deleted account usernames

    $deleted_users = \DB::table('erp_users')->whereIn('account_id',$deleted_account_ids)->get();
    foreach($deleted_users as $deleted_user){
       set_user_to_temp_username($deleted_user->id);
    }
    
    // update active account usernames
    
    $account_types = ['reseller','customer','reseller_user'];
    $accounts_active = [1,0];
    foreach($accounts_active as $account_active){
        foreach($account_types as $account_type){
            if($account_active){
                $accounts = \DB::table('crm_accounts')
                ->where('id','!=',1)
                ->select('id','phone','email','company','partner_id')
                ->where('type',$account_type)
                ->where('status','!=','Deleted')
                ->get();
            }else{
                $accounts = \DB::table('crm_accounts')
                ->where('id','!=',1)
                ->select('id','phone','email','company','partner_id')
                ->where('type',$account_type)
                ->where('status','Deleted')
                ->get();
            }
            $account_ids = $accounts->pluck('id')->toArray();
           
            foreach($accounts as $account){
                \DB::table('erp_users')
                ->where('account_id',$account->id)
                ->update([
                    'email'=>$account->email,
                    'phone'=>$account->phone,
                ]);
                
                $users = \DB::table('erp_users')->where('account_id',$account->id)->get();
                foreach($users as $user){
                  
                    if($user->username != $user->email && $user->username != $user->phone){
                       
                        $username_updated = false;
                        if(!empty($account->email)){
                            if($account_type == 'reseller' && $account_active){
                                $duplicate_users = \DB::table('erp_users')->where('username',$user->email)->where('account_id','!=',$account->id)->get();
                                foreach($duplicate_users as $duplicate_user){
                                    \DB::table('erp_users')->where('id',$duplicate_user->id)->update(['username'=>generate_strong_password().rand(0, 9).rand(0, 9).'@example.com']);
                                }
                            }
                            
                            $email_taken = \DB::table('erp_users')->where('username',$user->email)->count();
                            if(!$email_taken){
                                
                                \DB::table('erp_users')->where('id',$user->id)->update(['username'=>$user->email]);
                                if($account_active && $account_type!='reseller_user'){
                                    send_username_update_email($account,$user->email);
                                }
                                if($account_active && $account_type=='reseller_user'){
                                    send_username_update_email($account,$user->email,0,1);
                                }
                                $username_updated = true;
                            }
                        }
                        if(!$username_updated){
                            if(!empty($account->phone)){
                                $phone_taken = \DB::table('erp_users')->where('username',$user->phone)->count();
                                if(!$phone_taken){
                                    
                                    \DB::table('erp_users')->where('id',$user->id)->update(['username'=>$user->phone]);
                                    if($account_active && $account_type!='reseller_user'){
                                        send_username_update_email($account,$user->phone);
                                    }
                                    if($account_active && $account_type=='reseller_user'){
                                        send_username_update_email($account,$user->phone,0,1);
                                    }
                                    $username_updated = true;
                                }
                            }
                        }
                        if(!$username_updated){
                            $username = set_user_to_temp_username($user->id);
                            \DB::table('erp_users')->where('id',$user->id)->update(['username'=>$username]);
                            
                            if($account_active && $account_type!='reseller_user'){
                                send_username_update_email($account,$username,1);
                            }
                            if($account_active && $account_type=='reseller_user'){
                                send_username_update_email($account,$username,1,1);
                            }
                            $username_updated = true;
                        }
                    }else{
                        if($account_active && $account_type!='reseller_user'){
                            send_username_update_email($account,$user->username);
                        }
                    }
                }
            }
        }
    }
}