<?php

function schedule_supportboard_email_piping(){
  
    $SB_HOST_NAME = session('instance')->domain_name;
 
    require(public_path().'/helpdesk/include/functions.php');
   
    sb_email_piping(true);
}


function aftersave_users_set_supportboard_agents($request) {
    if(in_array(7,session('app_ids')) && session('role_level') == 'Admin'){
        $user = \DB::table('erp_users')->where('id',$request->id)->get()->first();
        $support_department = \DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('id',$user->role_id)->pluck('support_department_id')->first();
        $sb_id = \DB::connection('helpdesk')->table('sb_users')->whereIn('user_type',['agent','admin'])->where('email',$user->email)->pluck('id')->first();
        
        if($sb_id == null && !empty($user->webmail_email) && !empty($user->webmail_password)){
            $name_parts = explode(' ',$user->full_name);
            // Extract the last word as surname
            $surname = array_pop($name_parts);
            
            // The rest of the parts as first name
            $first_name = implode(' ', $name_parts);
            
            $data = [
                'token' => 'b7a0e33dc6067f7aadd57b68b41826ef94b6159a', 
                'function' => 'add-user', 
                'first_name' => $first_name, 
                'last_name' => $surname,
                'email' => $user->webmail_email, 
                'password' => $user->webmail_password,
                'user_type' => 'agent',  
            ];
            $user_result = support_board_api($data);
        } else {
            if ($sb_id == 1)
                $user_type = 'admin';
            else
                $user_type = 'agent';
                
            $data = [
                'token' => 'b7a0e33dc6067f7aadd57b68b41826ef94b6159a', 
                'function' => 'update-user', 
                'user_id' => $sb_id,
                'first_name' => $first_name, 
                'last_name' => $surname,
                'email' => $user->webmail_email, 
                'password' => $user->webmail_password, 
                'user_type' => $user_type
            ];
            $user_result = support_board_api($data);
        }
        \DB::connection('helpdesk')->table('sb_users')->whereIn('user_type',['agent','admin'])->where('email',$user->email)->update(['department' => $support_department]);
    }
}



function support_board_api ($settings) {
    $sb_hostname = 'helpdesk.telecloud.co.za';
    $ch = curl_init('https://'.$sb_hostname.'/include/api.php');
    $parameters = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Support Board',
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POSTFIELDS => http_build_query($settings)
    ];
    curl_setopt_array($ch, $parameters); 
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}


function afterdelete_set_supportboard_departments($request){
   $response = update_supportboard_departments();
   if($response !== true){
       return $response;
   }
}


function aftersave_set_supportboard_departments($request){
    
   $response = update_supportboard_departments();
   if($response !== true){
       return $response;
   }
}


function supportboard_view_data(){
    $conversations = \DB::connection('helpdesk')->table('sb_conversations')->get();
    $messages = \DB::connection('helpdesk')->table('sb_messages')->get();
    
    $data = [
        'conversations' => $conversations,
        'messages' => $messages,
        /*
        .. add view data
        */
    ];
    return $data;
}

function supportboard_view_data_ajax($request){
    $conversations = \DB::connection('helpdesk')->table('sb_conversations')->get();
    $messages = \DB::connection('helpdesk')->table('sb_messages')->get();
    
    $data = [
        'conversations' => $conversations,
        'messages' => $messages,
        /*
        .. add view data
        */
    ];
    return response()->json($data);
}

/* kb articles aftersave - start */
function aftersave_kb_supportboard_articles($request){
    if(is_main_instance()){
    \DB::connection('helpdesk')->table('sb_articles')->truncate();
    
    $faqs = \DB::Table('hd_customer_faqs')->where('internal',0)->where('is_deleted',0)->where('level','Customer')->get();
    foreach($faqs as $faq){
    $kb = [
    'title' => $faq->name,
    'content' => $faq->content,
    'parent_category' => seo_string($faq->type),
    'language' => "",
    //'parent_category' => 'support',
    'slug'=> seo_string($faq->name),
    'update_time' => ($faq->updated_at) ? $faq->updated_at : $faq->created_at,
    ];
    \DB::connection('helpdesk')->table('sb_articles')->insert($kb);
    }
    
    $SB_HOST_NAME = session('instance')->domain_name;
    $sb_hostname = session('instance')->supportboard_domain;
    // sb_set_conf($sb_hostname);
    $categories = $faqs->pluck('type')->unique()->filter()->toArray();
    
    $categories = array_values($categories);
    $categories_array = [];
    foreach($categories as $cat){
    $categories_array[] = ['id' => seo_string($cat), 'title' => $cat,'parent'=> true];
    }
    //$categories_array[] = ['id' => 'support', 'title' => 'Support','parent'=>true];
    // sb_save_articles_categories($categories_array);
    }
}
/* kb articles aftersave - end */

/* roles aftersave - start */


/* users aftersave - start */
function users_afterdelete_set_supportboard_agents($request){
    if(in_array(7,session('app_ids')) && session('role_level') == 'Admin'){
       
        // delete agents  
        $role_ids = \DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('support_department',1)->pluck('id')->toArray();
        $erp_users_emails = \DB::connection('default')->table('erp_users')->whereIn('role_id',$role_ids)->where('is_deleted',0)->pluck('email')->toArray();
        \DB::connection('helpdesk')->table('sb_users')->where('user_type','agent')->whereNotIn('email',$erp_users_emails)->delete();
    }
}
/* users aftersave - end */

function update_supportboard_users(){
    
      if(in_array(7,session('app_ids')) && session('role_level') == 'Admin'){
        $users = \DB::table('erp_users')->where('is_deleted',0)->where('account_id',1)->where('role_id','>',58)->get();
        foreach($users as $user){
            $support_department = \DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('id',$user->role_id)->pluck('support_department')->first();
            $support_department_id = \DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('id',$user->role_id)->pluck('support_department_id')->first();
            $exists = \DB::connection('helpdesk')->table('sb_users')->whereIn('user_type',['agent','admin'])->where('email',$user->email)->count();
            if(!$exists && $support_department && !empty($user->webmail_email) && !empty($user->webmail_password)){
               
                $name_parts = explode(' ',$user->full_name);
                // Extract the last word as surname
                $surname = array_pop($name_parts);
                
                // The rest of the parts as first name
                $first_name = implode(' ', $name_parts);
                
                $data = [
                'token' => 'b7a0e33dc6067f7aadd57b68b41826ef94b6159a', 
                'function' => 'add-user', 
                'first_name' => $first_name, 
                'last_name' => $surname,
                'email' => $user->webmail_email, 
                'password' => $user->webmail_password, 
                'user_type' => 'agent',  
                ];
               
                $user_result = support_board_api($data);
            }
            
            
            \DB::connection('helpdesk')->table('sb_users')->whereIn('user_type',['agent','admin'])->where('email',$user->email)->update(['department' => $support_department_id]);
      
        }
    }
    
}

function update_supportboard_departments(){
    if(in_array(7,session('app_ids')) && session('role_level') == 'Admin'){
       
        $departments_set = DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('support_department',1)->count();
        if(!$departments_set){
            return 'No support department is not set';
        }
        
        $default_departments_set = DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('default_support_department',1)->count();
        if(!$default_departments_set){
            return 'Default support department is not set';
        }
        
        $settings = \DB::connection('helpdesk')->table('sb_settings')->where('name','settings')->pluck('value')->first();
        $settings = json_decode($settings);
        $roles = \DB::connection('default')->table('erp_user_roles')->orderBy('sort_order')->where('level','Admin')->where('support_department',1)->get();
        $existing_department_ids = [];
        $departments = $settings->departments[0];
       
        foreach($roles as $role){
            $add_role = true;
            //Check if exists
            foreach($departments as $i => $d){
                if($d->{'department-id'} == $role->support_department_id){
                    $add_role = false;
                    //Update Department Name
                    $departments[$i]->{'department-name'} = $role->name;
                }
                
                //Ahmed to test
                $department_id = [
                    "id" => $d->{'department-id'},
                ];
                $department_name = [
                    "name" => $d->{'department-name'},
                ];
                \DB::connection('helpdesk')->table('aa_departments')->updateOrInsert($department_id, $department_name);
            }
           
            if($add_role){
                $max_id = collect($departments)->max('department-id');
                $id = $max_id+1;
                $department = (object) [
                    "department-name"=> $role->name,
                    "department-color"=> "",
                    "department-image"=> "",
                    "department-id"=> (string) $id,
                ];
                \DB::connection('default')->table('erp_user_roles')->where('id',$role->id)->update(['support_department_id'=>$id]);
                $departments[] = $department;
            }

        }
        //update settings json
        $settings->departments[0] = $departments;
        $settings = json_encode($settings);
        \DB::connection('helpdesk')->table('sb_settings')->where('name','settings')->update(['value'=>$settings]);
        
        //role removed, remove department from user and update tickets to default department
        $removed_roles = \DB::connection('default')->table('erp_user_roles')->select('support_department_id')->where('level','Admin')->where('support_department',0)->where('support_department_id','>',0)->get();
        $default_department_id = DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('default_support_department',1)->pluck('support_department_id')->first();
        if (!$default_department_id) {
            $default_department_id = DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('support_department',1)->pluck('support_department_id')->first();
        }
        
        foreach($removed_roles as $role){
            \DB::connection('helpdesk')->table('sb_conversations')->where('department',$role->support_department_id)->update(['department'=>$default_department_id]);
            \DB::connection('helpdesk')->table('sb_users')->where('department',$role->support_department_id)->update(['department'=>null]);
        }
    }
    
    // delete agents  
    $role_ids = \DB::connection('default')->table('erp_user_roles')->where('level','Admin')->where('support_department',1)->pluck('id')->toArray();
    $erp_users_emails = \DB::connection('default')->table('erp_users')->whereIn('role_id',$role_ids)->where('is_deleted',0)->pluck('email')->toArray();
    if (count($erp_users_emails) > 0) {
        \DB::connection('helpdesk')->table('sb_users')->where('user_type','agent')->whereNotIn('email',$erp_users_emails)->delete();
    }
    
    return true;
}

function sbdb_get_conversations_count(){
 // Status codes: live = 0, waiting answer from user = 1, waiting answer from agent = 2, archive = 3, trash = 4.
    if(is_superadmin()){
        $count = \DB::connection('helpdesk')->table('sb_conversations')->whereIn('status_code',[0])->count();
    }else{
        $department = \DB::connection('default')->table('erp_user_roles')->where('id',session('role_id'))->pluck('support_department_id')->first();
        if($department){
            $count = \DB::connection('helpdesk')->table('sb_conversations')->where('department',$department)->whereIn('status_code',[0])->where('department',)->count();
        }
        
        $count = \DB::connection('helpdesk')->table('sb_conversations')->whereIn('status_code',[0])->count();
    }
    return ' ('.$count.')';
}

function sbapi_get_conversations(){
    
    $query = [
        'token' => 'b7a0e33dc6067f7aadd57b68b41826ef94b6159a', 
        'function' => 'get-conversations',   
    ];
    $conversations = support_board_api($query);
    

    return $conversations;
}

function sbapi_get_conversations_count(){
 
    $conversations = sbapi_get_conversations();
    $count = 0;
    foreach ($conversations as $conversation)
    if(!empty($conversations['response']) && is_array($conversations['response'])){
        $count = count($conversations['response']);
    }
    return $count;
}

function sbapi_get_user_conversations(){
    
    $sb_ids = \DB::connection('helpdesk')->table('sb_users')->whereIn('user_type',['agent','admin'])->pluck('id');
    foreach ($sb_ids as $sb_id) {
        $query = [
            'token' => 'b7a0e33dc6067f7aadd57b68b41826ef94b6159a', 
            'function' => 'get-user-conversations',
            'user-id' => $sb_id,
        ];
        $conversations = support_board_api($query);
    }

    return $conversations;
}