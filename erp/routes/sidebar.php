<?php



Route::any('call_center_call_completed/{id?}', function ($id) {
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.form_name = crm_accounts.form_name,crm_opportunities.ad_form_id = crm_accounts.form_id,crm_opportunities.source = crm_accounts.source,crm_opportunities.phone = crm_accounts.phone,crm_opportunities.email = crm_accounts.email,crm_opportunities.last_call = crm_accounts.last_call";
    \DB::statement($sql);
    $opp = \DB::table('crm_opportunities')->where('id',$id)->get()->first();
    $campaign = \DB::table('crm_call_center_campaigns')->where('id',$opp->call_center_campaign_id)->get()->first();
    $account_phone = \DB::table('crm_accounts')->where('id',$opp->account_id)->pluck('phone')->first();
    if($campaign->email_id > 0){
        $email_sent = \DB::table('erp_communication_lines')->where('account_id',$opp->account_id)->where('email_id',$campaign->email_id)->count();
        if(!$email_sent){
            return json_alert('Email was not sent','warning');
        }
        if($account_phone && empty($opp->last_call)){
            return json_alert('Last call not set','warning');
        }
    }else{
        if($account_phone && empty($opp->last_call)){
            return json_alert('Last call not set','warning');
        }
    }
    
   
    update_salesboard_stats($opp->sales_board_id);
    return json_alert('Call Completed');
});

Route::post('call_center_queue_next', function () {
  
    // Access POST data using the request() helper function
    $id = request('id'); 
    $opp = \DB::table('crm_opportunities')->where('id',$id)->get()->first();
    $call_comments = request('call_comments');
   
    if(empty($call_comments)){
        return json_alert('Call comments required','warning');
    }
    
    $status = request('call_status');
    if(empty($status)){
        return json_alert('Status required','warning');
    }
    
    if($status == 'Demo Scheduled' && empty($opp->booked_call_date)){
        return json_alert('Booked call date required','warning');
    }
    
    add_module_note(1923,$id,$call_comments);
    \DB::table('crm_opportunities')->where('id',$id)->update(['last_note_date' => date('Y-m-d H:i:s'),'called'=>1,'last_note'=>$call_comments,'status'=>$status]);
    return json_alert('Comment added, queueing next call');
});

Route::any('app_sidebar_callcenter/{module_id?}/{row_id?}/{account_id?}',function($module_id = false, $row_id = false, $account_id = false){
    if($module_id != 1923){
        return '';
    }
    
    $account = dbgetaccount($account_id);
    try{
        $number = phone($account->phone, ['ZA','US','Auto']);
        $number = $number->formatForMobileDialingInCountry('ZA');
    }catch(\Throwable $ex){
        $number = '';
    }
    if($number){
    \DB::table('crm_accounts')->where('id',$account_id)->update(['phone'=>$number]);
    }
    $account = dbgetaccount($account_id);
    $account->balance = currency_formatted($account->balance, $account->currency);
    $opp = \DB::table('crm_opportunities')->where('id',$row_id)->get()->first();
  
    $statuses = \DB::table('erp_module_fields')->where('module_id',1923)->where('field','status')->pluck('opts_values')->first();
   
   
    
    $status_options = explode(',',$statuses);
   
    
    $data = [
      'account'=> $account,
      'id' => $row_id,
      'opp_status' => $opp->status,
      'status_options' => $status_options
    ];
 
    return view('__app.grids.sidebar.call_center',$data)->render();
});


Route::any('app_sidebar_users_datasource/{module_id?}',function($module_id = false){
 
    //$staff_stats = session('staff_stats');
 
    //if(empty($staff_stats)){
        $staff_stats = get_staff_current_tasks();
        session(['staff_stats' => $staff_stats]); 
   // }
    $items = [];
    $requires_redirect = 0;
    foreach($staff_stats as $staff_stat){
        if(empty($staff_stat['start_time'])){
            $staff_stat['start_time'] = 'ABSENT';    
        }
        if(session('user_id') != 1 && $staff_stat['user_id'] == session('user_id')){
            if(empty($staff_stat['task'])){
                $requires_redirect = 1;    
            }    
        }
        $header = $staff_stat['full_name'];
        $content = '<div class="py-2 px-1">
        <div class="row">
        <div class="col">
        Task:
        </div>
        <div class="col-auto">
        '.$staff_stat['task'].'
        </div>
        </div>
        <div class="row">
        <div class="col">
        Layout:
        </div>
        <div class="col-auto">
        '.$staff_stat['layout'].'
        </div>
        </div>
        <div class="row">
        <div class="col">
        Module:
        </div>
        <div class="col-auto">
        '.$staff_stat['module'].'
        </div>
        </div>
        <div class="row">
        <div class="col">
        Instance:
        </div>
        <div class="col-auto">
        '.$staff_stat['instance'].'
        </div>
        </div>
        <div class="row">
        <div class="col">
        Start Time:
        </div>
        <div class="col-auto">
        '.$staff_stat['start_time'].'
        </div>
        </div>
        </div>
        </div>';
        $cssClass = '';
        if($staff_stat['start_time'] == 'ABSENT'){
            $cssClass = 'staff-absent';
        }
        $items[] = (object) ['header'=>$header,'cssClass'=>$cssClass,'content'=>$content];
    }
    
  
   
    
    return response()->json(['items' => $items, 'task_warning'=>$requires_redirect]);

});

Route::any('app_sidebar_guides_datasource/{module_id?}/{row_id?}',function($module_id = false, $row_id = false){
 
    $content = '';
    $content .= '<div class="card">';
    $content .= '<div class="card-body">';
  
    if($module_id){
        if(is_dev() || is_superadmin()){
            $module_manager_url = get_menu_url_from_table('erp_cruds');
            $content .= '<a href="'.$module_manager_url.'/edit/'.$module_id.'" class="k-button mb-4" title="Edit Guide" data-target="form_modal">Edit Guide</a><br>';
        }
        $guide = \DB::table('erp_cruds')->select('guide')->where('id',$module_id)->get()->first();
        if($guide->guide){
            $guide_content = $guide->guide;
            $guide_content = str_replace('<ol>','<ul class="bullet-ul">',$guide_content);
            $guide_content = str_replace('</ol>','</ul>',$guide_content);
            
          
            
            $content .= str_replace('<p><br></p>','<br>',$guide_content);
        }
    }
    $content .= '</div>
    </div>';
    echo $content;
});

Route::any('sidebar_test',function(){
 
    return view('__app.test.sidebar',[]);
});
Route::any('app_sidebar_performance_datasource/{module_id?}',function($module_id = false){
 
    $performance_cards = sidebar_get_performance_cards($module_id);
  
    echo $performance_cards;
});

Route::any('app_sidebar_files_datasource/{type?}/{account_id?}',function($type = false, $account_id = false){
    $html = '';
  
    if($account_id){
        
        if($type == 'pbx'){
            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid',$account_id)->pluck('account_id')->first();  
            $type = 'account';
        }
        
        $sidebar_files = sidebar_get_files($type,$account_id);
        $html = $sidebar_files['html'];
    }
   
    return $html;
});
Route::any('app_sidebar_notes_datasource/{module_id?}/{row_id?}',function($module_id = false, $row_id = false){
    $html = '';
  
    if($row_id){
        
        $notes = sidebar_get_notes($module_id,$row_id);
        $html = $notes['html'];
    }
   
    return $html;
});

Route::any('app_sidebar_linked_modules_datasource/{module_id?}/{row_id?}',function($module_id = false, $row_id = false){
    $html = '';
 
    if($row_id){
        $html = sidebar_get_linked_modules($module_id,$row_id);
    }
   
    return $html;
});



Route::any('app_sidebar_emails_datasource/{module_id?}/{row_id?}',function($module_id = false, $row_id = false){
    $html = '';
  
    if($row_id){
        $html = sidebar_get_email_templates($module_id,$row_id);
    }
   
    return $html;
});

Route::any('app_sidebar_subscription_datasource/{module_id?}/{row_id?}/{account_id?}',function($module_id = false, $row_id = false, $account_id = false){
   
    $html = '';
  
    if($row_id){
        $html = sidebar_get_subscriptions($module_id,$row_id,$account_id);
    }
   
    return $html;
});

Route::any('app_sidebar_account_datasource/{type?}/{account_id?}',function($type = false, $account_id = false){
 
    if($type == 'pbx'){
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid',$account_id)->pluck('account_id')->first();  
        $type = 'account';
    }
    $items = [];
    $account_info = '';
    /**
    Interactions (SMS or Email) (with Notes)
    Statement
    Subscriptions
    Files */
    $subscription_info = '';
    if($account_id){
       
      
        if($type == 'account'){
            $items[] = (object) ['header'=>'Contacts','content'=>sidebar_get_contacts($type, $account_id)];
            $items[] = (object) ['header'=>'Interactions','content'=>sidebar_get_interactions($account_id)];
            
            $items[] = (object) ['header'=>'Statement','content'=>sidebar_get_statement($type, $account_id)];
           
            
            
            $account = dbgetaccount($account_id);
            $account_info = '<div class="row p-0"><div class="col"><h6>'.$account->company.'</h6></div> <div class="col-auto text-right"><a class="k-button" href="javascript:void()" onClick="refresh_account_accordion()">Refresh</a></div></div>'; 
            $account_info .= 'Contact: '.$account->contact.'<br>';
            $account_info .= 'Email: '.'<a href="/email_form/default/'.$account->id.'/'.$account->email.'" target="_blank" data-target="form_modal">'.$account->email.'</a><br>';
            //if(session('instance')->directory != 'moviemagic'){
            $account_info .= 'Phone: '.'<a href="javascript:void(0);" onclick="gridAjax(\'/pbx_call/'.$account->phone.'/'.$account->id.'\')">'.$account->phone.'</a><br>';
            //}
            $account_info .= 'Balance: '.currency_formatted($account->balance,$account->currency).'<br>';
            $account_info .= 'Status: '.$account->status.'<br>';
            if(!empty($account->pabx_domain)){
               
                $domain_info = \DB::connection('pbx')->table('v_domains')->where('account_id',$account->id)->get()->first();
              
                $account_info .= 'Airtime Balance: '.currency_formatted($domain_info->balance,$domain_info->currency);
                
                $unlimited_channels = $domain_info->unlimited_channels;
                if($unlimited_channels > 0){
                    $account_info .= '<br>Unlimited Channels: '.$unlimited_channels;
                    $account_info .= '<br>Unlimited Channels Usage: '.$domain_info->unlimited_channels_usage;
                    $account_info .= '<br>Unlimited Channels Average: '.currency($domain_info->unlimited_channels_usage/$unlimited_channels);
                }
            }
            
            if(session('role_level') == 'Admin'){
                $accounts_url = get_menu_url_from_module_id(343);
                $account = dbgetaccount($account_id);
                if($account->partner_id == 1){
                $account_info .= '<a onClick="cancelAccount( '.$account_id.')" href="javascript:void(0)" class="k-button">Cancel</a>';
                
                }
            }
          
           
        }
        if($type == 'supplier'){
            $items[] = (object) ['header'=>'Contacts','content'=>sidebar_get_contacts($type, $account_id)];
            $items[] = (object) ['header'=>'Statement','content'=>sidebar_get_statement($type, $account_id)];
            $account = dbgetsupplier($account_id);
            $account_info = '<h4>'.$account->company.'</h4>';
            $account_info .= 'Contact: '.$account->contact.'<br>';
            $account_info .= 'Email: '.$account->email.'<br>';
            $account_info .= 'Phone: '.$account->phone.'<br>';
            $account_info .= 'Balance: '.currency_formatted($account->balance,$account->currency);
            $account_info .= 'Status: '.$account->status.'<br>';
        }
    }
    
    return response()->json(['accordion' => $items,'info' => $account_info]);
});



