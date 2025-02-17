<?php

function schedule_zammad_create_organizations(){
    if(!is_main_instance()){
        return false;
    }
     
    $client = new \ZammadAPIClient\Client([
        'url'           => 'https://helpdesk.cloudtelecoms.co.za', // URL to your Zammad installation
        'username'      => 'gustaf@telecloud.co.za',  // Username to use for authentication
        'password'      => 'WEBmin@321',           // Password to use for authentication
        // 'timeout'       => 15,                  // Sets timeout for requests, defaults to 5 seconds, 0: no timeout
        // 'debug'         => true,                // Enables debug output
        // 'verify'        => true,                // Enabled SSL verification. You can also give a path to a CA bundle file. Default is true.
    ]);
   
    
    $zammad_account_ids = \DB::table('crm_zammad_accounts')->pluck('account_id')->toArray();
    $accounts = \DB::table('crm_accounts')
    ->whereNotIn('id',$zammad_account_ids)
    ->where('id','!=',1)
    ->where('partner_id',1)
    ->whereIn('type',['reseller','customer'])
    ->where('status','!=','Deleted')
    ->limit(40)->get();
    
    foreach($accounts as $account){
        $user = \DB::table('erp_users')->where('account_id',$account->id)->get()->first();
        $organizationData = [
            'name' => $account->company,
            // Add other organization details as needed
        ];
        $org = $client->resource( ZammadAPIClient\ResourceType::ORGANIZATION );
        $org->setValues( $organizationData );
        
        $org_result = $org->save(); 
        if($org_result->getError() > ''){
            \DB::table('crm_zammad_accounts')->insert(['account_id'=>$account->id,'zammad_id'=>0,'error'=>$org_result->getError()]);
            continue;
        }
        
        $org_data = $org_result->getValues();
        
        $userData = [
            'login' => $user->username,
            'firstname' => $account->contact,
            'lastname' => '-',
            'email' => $account->email,
            'phone' => $account->phone,
            'organization_id' => $org_data['id'], // Associate the user with the organization
            // Add other user details as needed
        ];
        
        $user = $client->resource( ZammadAPIClient\ResourceType::USER );
        $user->setValues( $userData );
        
        $user_result = $user->save(); 
        
        if($user_result->getError() > ''){
            \DB::table('crm_zammad_accounts')->insert(['account_id'=>$account->id,'zammad_id'=>0,'error'=>$user_result->getError()]);
            continue;
        }
        $user_data = $user_result->getValues();
        try{
        \DB::table('crm_zammad_accounts')->insert(['account_id'=>$account->id,'zammad_id'=>$user_data['id']]);
        }catch(\Throwable $e){}
       
    }
   
}


function schedule_zammad_import_tickets(){
    if(!is_main_instance()){
        return false;
    }
    $client = new \ZammadAPIClient\Client([
        'url'           => 'https://helpdesk.cloudtelecoms.co.za', // URL to your Zammad installation
        'username'      => 'gustaf@telecloud.co.za',  // Username to use for authentication
        'password'      => 'WEBmin@321',           // Password to use for authentication
        // 'timeout'       => 15,                  // Sets timeout for requests, defaults to 5 seconds, 0: no timeout
        // 'debug'         => true,                // Enables debug output
        // 'verify'        => true,                // Enabled SSL verification. You can also give a path to a CA bundle file. Default is true.
    ]);
  
    $zammad_ids = [];
    // Set the limit of tickets per page
    $limit = 20;
    
    // Start with the first page (offset 0)
    $page_number = 1;
    $check_next_page = true;
    while($check_next_page){
        // Fetch tickets with the specified limit and offset
        $tickets = $client->resource(ZammadAPIClient\ResourceType::TICKET)->search('state.name:open OR state.name:new',$page_number,$limit);
          
        if(empty($tickets) || count($tickets) < 20){
            $check_next_page = false;
        }else{
            $page_number++;
            
        }
        // Process the tickets on the current page
        foreach ($tickets as $ticket) {
            
            $ticket_data = (object) $ticket->getValues();
            if($ticket_data->state == 'closed'){
                continue;
            }
          
            $created_at = date('Y-m-d H:i:s',strtotime($ticket_data->created_at));
            $zammad_id = $ticket_data->id;
            $subject = $ticket_data->title;
            if(empty($ticket_data->owner) || $ticket_data->owner == '-'){
                $user_id = 0;
                // assign ticket to staff
                if($ticket_data->customer){
                    $customer = \DB::table('crm_accounts')->where('email',$ticket_data->customer)->get()->first();
                    if($customer){
                        $user = \DB::table('erp_users')->where('id',$customer->salesman_id)->where('account_id',1)->get()->first();
                        if($user){
                            $ticket->setValue( 'owner', $user->email );
                            $ticket->save();
                            $user_id = $user->id;
                        }
                    }
                }
               
            }else{
                $user_id = \DB::table('erp_users')->where('email',$ticket_data->owner)->where('account_id',1)->pluck('id')->first();
            }
            $zammad_ids[] = $zammad_id;
            $data = [
                'user_id' => $user_id,
                'subject' => $subject,
                'zammad_id' => $zammad_id,
                'created_at' => date('Y-m-d H:i:s',strtotime($created_at)),
                'completed'=> 0,
                'ticket_data' => json_encode($ticket_data)
            ];
           
            \DB::table('crm_tickets')->updateOrInsert(['zammad_id' => $zammad_id],$data);
        }
    }
    
    \DB::table('crm_tickets')->whereNotIn('zammad_id',$zammad_ids)->update(['completed'=>1]);
}

// https://github.com/zammad/zammad-api-client-php

// /usr/bin/php74 /usr/local/bin/composer require zammad/zammad-api-client-php
    /*
     $tables =  [
        "knowledge_bases",
        "knowledge_base_locales",
        "knowledge_base_categories",
        "knowledge_base_category_translations",
        "knowledge_base_answers",
        "knowledge_base_answer_translations",
        "knowledge_base_answer_translation_contents"
    ];
    
    $results = [];
    foreach($tables as $table){
        $rows = \DB::connection('zammad')->table($table)->get();
        $results[$table] = $rows;
    }
    */
    
function button_zammad_import(){
   schedule_zammad_import_tickets();
   return json_alert('Done');
}

function aftersave_zammad_create_kb($request){
    /*
    \DB::connection('zammad')->table('knowledge_base_answer_translations')->delete();
    \DB::connection('zammad')->table('knowledge_base_answer_translation_contents')->delete();
    \DB::connection('zammad')->table('knowledge_base_answers')->delete();
    \DB::connection('zammad')->table('knowledge_base_category_translations')->delete();
    \DB::connection('zammad')->table('knowledge_base_categories')->delete();
    $timestamp = date('Y-m-d H:i:s', strtotime('-4 hours'));
    $faqs = \DB::table('hd_customer_faqs')->where('is_deleted',0)->where('internal',0)->orderBy('type')->orderBy('name')->get();
    $categories = \DB::table('hd_customer_faqs')->where('is_deleted',0)->where('internal',0)->pluck('type')->unique()->toArray();
    $category_ids = [];
    foreach($categories as $i => $category){
        $data = [
            'knowledge_base_id' => 1,
            'category_icon' => "f003",
            'position' => $i,
            'created_at' => $timestamp,
            'updated_at' => $timestamp
            
        ];
        $category_id = \DB::connection('zammad')->table('knowledge_base_categories')->insertGetId($data);
        $data = [
            'kb_locale_id' => 1,
            'title' => $category,
            'category_id' => $category_id,
            'created_at' => $timestamp,
            'updated_at' => $timestamp
            
        ];
        \DB::connection('zammad')->table('knowledge_base_category_translations')->insert($data);
        
    }
    $db_categories = \DB::connection('zammad')->table('knowledge_base_category_translations')->get();
    foreach($faqs as $i => $faq){
        $category_id = $db_categories->where('title',$faq->type)->pluck('category_id')->first();
        $data = [
            'category_id' => $category_id,
            'position' => $i,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'published_at' => $timestamp,
            'published_by_id' => 3,
        ];
        $answer_id = \DB::connection('zammad')->table('knowledge_base_answers')->insertGetId($data);
        $data = [
            'body' => $faq->content,
        ];
        $answer_content_id = \DB::connection('zammad')->table('knowledge_base_answer_translation_contents')->insertGetId($data);
        $data = [
            'kb_locale_id' => 1,
            'title' => $faq->type.' - '.$faq->name,
            'answer_id' => $answer_id,
            'content_id' => $answer_content_id,
            'created_by_id' => 25,
            'updated_by_id' => 25,
            'created_at' => $timestamp,
            'updated_at' => $timestamp
            
        ];
        \DB::connection('zammad')->table('knowledge_base_answer_translations')->insert($data);
    }
    */
}

