<?php


function sidebar_get_services_menu($module_id, $account_id = false){
    $current_module = \DB::connection('default')->table('erp_cruds')->select('id','connection','app_id')->where('id', $module_id)->get()->first();
    $menu_params = [];
    // setup menu params
    if($current_module){
       
        
        $current_menu = \DB::connection('default')->table('erp_menu')->select('id','module_id','location','url')
        ->where('module_id', $current_module->id)->where('menu_type', 'module')->where('active', 1)->get()->first();
        
        $menu_params = [];
        $menu_params['menu_url'] = $current_menu->url;
        $menu_params['module_id'] = $current_menu->module_id;
        $menu_params['connection'] = $current_module->connection;
        $menu_params['app_id'] = $current_module->app_id;
        if($account_id){
            $subscription_product_ids = \DB::connection('default')->table('sub_services')
            ->select('product_id')
            ->where('status','!=','Deleted')
            ->where('account_id',$account_id)->pluck('product_id')->unique()->filter()->toArray();
            $menu_params['subscription_product_ids'] = $subscription_product_ids;
        }
     
        if($current_menu->id){
            $menu_params['menu_id'] = $current_menu->id;
        }
    }
    $services_listview = [];
    
    $telecloud_menu = \ErpMenu::build_menu('telecloud_menu', $menu_params); 
    $sms_menu = \ErpMenu::build_menu('sms_menu', $menu_params); 
    $hosting_panels = \Erp::hosting_panels($account_id);
    
    $has_sms = true;
    $has_hosting = true;
    $has_voice = true;
    if($account_id){
        $has_sms = \DB::connection('default')->table('sub_services')
            ->select('detail')
            ->where('provision_type','bulk_sms_prepaid')
            ->where('status','!=','Deleted')
            ->where('account_id',$account_id)
            ->count();
        $has_hosting = \DB::connection('default')->table('sub_services')
            ->select('detail')
            ->where('provision_type','hosting')
            ->where('status','!=','Deleted')
            ->where('account_id',$account_id)
            ->count();
        $has_voice = \DB::connection('pbx')->table('v_domains')
            ->where('account_id',$account_id)
            ->count();
    }
    if($has_voice){
        $telecloud_menu = \ErpMenu::build_menu('telecloud_menu', $menu_params); 
     
        if(!empty($telecloud_menu) && count($telecloud_menu) > 0){
            $services_listview[] = ['id' => 'voicemenu','text'=>'Voice','items' => $telecloud_menu];
        }
    }
    if($has_hosting){
        $hosting_panels = \Erp::hosting_panels($account_id);
        if(!empty($hosting_panels) && count($hosting_panels) > 0){
            $services_listview[] = ['id' => 'hostingmenu','text'=>'Hosting','items' => $hosting_panels];
        }
    }
    if($has_sms){
        $sms_menu = \ErpMenu::build_menu('sms_menu', $menu_params); 
        if(!empty($sms_menu) && count($sms_menu) > 0){
            $services_listview[] = ['id' => 'smsmenu','text'=>'SMS','items' => $sms_menu];
        }
    }
 
    return $services_listview;
}

function sidebar_get_account_cards($account_id){
    $account = dbgetaccount($account_id);
    $html = '
    <div class="row mb-4 mx-0 px-0">
    <div class="col-sm-12">
        <div class="card border my-1">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">'.currency_formatted($account->balance,$account->currency).'</h4>
                <p class="text-sm font-weight-bold mb-0">
                Amount Due
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="ni ni-money-coins text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
    <!--<div class="col-sm-12">
        <div class="card border my-1">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">'.$account->subs_count.'</h4>
                <p class="text-sm font-weight-bold mb-0">
                Subscriptions
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="ni ni-app text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>-->';
    $pbx_domain = false;
    
    if(!empty($account->domain_uuid)){
        $pbx_domain = \DB::connection('pbx')->table('v_domains')->where('domain_uuid',$account->domain_uuid)->get()->first();
    }
    
    if(!empty($pbx_domain)){
  
        $html .='<div class="col-sm-12">
            <div class="card border my-1">
            <div class="card-body p-3 position-relative">
            <div class="row">
                <div class="col-7 text-start">
                    <h4 class="mb-1 text-capitalize font-weight-bolder">'.$pbx_domain->balance.'</h4>
                    <p class="text-sm font-weight-bold mb-0">
                    Airtime Balance
                    </p>
                </div>
                <div class="col-5 d-flex justify-content-end">
                    <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                    <i class="fas fa-phone text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="card border my-1">
            <div class="card-body p-3 position-relative">
            <div class="row">
                <div class="col-7 text-start">
                    <h4 class="mb-1 text-capitalize font-weight-bolder">'.$pbx_domain->monthly_usage.'</h4>
                    <p class="text-sm font-weight-bold mb-0">
                    Airtime Usage
                    </p>
                </div>
                <div class="col-5 d-flex justify-content-end">
                    <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                    <i class="fas fa-server text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </div>';
    }
    $html .='</div>';
    return $html;
}

function sidebar_get_statement($type,$account_id){
    
    $users = \DB::table('erp_users')->where('account_id',1)->get();
    $html = '';
    
    if($type == 'supplier'){
        $html .= '<div class="text-center"><a href="/supplier_statement_download/'.$account_id.'" target="_blank" class="btn btn-default">Download Statement</a></div>';
    }else{
        $html .= '<div class="text-center"><a href="/statement_download/'.$account_id.'" target="_blank" class="btn btn-default">Download Statement</a></div>';
    }
    $cashbooks = \DB::table('acc_cashbook')->get();
    if($type == 'account'){
        $documents_url = get_menu_url_from_module_id(353);
        
       
        $cashbook_url = get_menu_url_from_module_id(1837);
        $currency = get_account_currency($account_id);

        $html .= '<ul class="list-group">';
        $docs = get_debtor_transactions_including_pending($account_id);
        $docs = collect($docs)->sortByDesc('docdate')->take(10);
        $account = dbgetaccount($account_id);
        if($account->partner_id !=1){
            $account = dbgetaccount($account->partner_id);
        }
        $balance = $account->balance;
        foreach($docs as $d){
            $salesman_name = '';
            if($d->salesman_id){
                $salesman_name = $users->where('id',$d->salesman_id)->pluck('full_name')->first();
            }
            $html .= ' <li class="list-group-item">';
            $html .= ' <div class="row m-0 p-0"><div class="col m-0 p-0">';
            $html .= '<span class="">'.$d->docdate.' #'.$d->doc_no.'</span>';
            
            $html .= '</br>'.currency_formatted($d->total,$currency).'</br>';
            if($d->reference > ''){
                $html .= $d->reference;
            }
            
            //if($salesman_name > ''){
            //    $html .= 'Salesman: '.$salesman_name.'</br>';
            //} 
            if($d->doctype == 'Cashbook Customer Receipt'){
                $html .= '<a class="btn btn-sm sidebar-btn stretched-link p-0 mb-0" style="line-height:0px !important; height:0px !important; opacity:0" href="/'.$cashbook_url.'?id='.$d->id.'" target="_blank"></a>';
            }elseif($d->doctype != 'Cashbook Customer Receipt' && $d->doctype != 'General Journal'){
                $html .= '<a class="btn btn-sm sidebar-btn stretched-link p-0 mb-0" style="line-height:0px !important; height:0px !important; opacity:0" href="/'.$documents_url.'?id='.$d->id.'" target="_blank"></a>';
            }
            $html .= '</div><div class="col col-auto text-end">';
           
            if($d->doctype == 'Cashbook Customer Receipt'){
                $cashbook_name = $cashbooks->where('id',$d->cashbook_id)->pluck('name')->first();
                $html .= $d->doctype.'</br>';
                $html .= '<b>'.$cashbook_name.'</b></br>';
            }else{
                $html .= $d->doctype.'</br>';
            }
            
            $html .= '<br>Balance: '. currency_formatted($balance,$currency).'<br>';
            $html .= '</div></div>';
            
           
            $html .= '</li>';
            
            if($d->doctype == 'Cashbook Customer Receipt'){
                $balance += $d->total;
            }elseif($d->doctype == 'Tax Invoice'){
                $balance -= $d->total;
            }elseif($d->doctype == 'Credit Note'){
                $balance += $d->total;
            }else{
                $balance -= $d->total;
            }
        }
        
        $html .= '</ul>';
        
    }
    if($type == 'supplier'){
    $documents_url = get_menu_url_from_module_id(354);
        $currency = get_supplier_currency($account_id);
        $html .= '<div class="k-widget k-button-group"><a class="btn btn-default sidebar-btn" href="/supplier_statement_pdf/'.$account_id.'" data-target="view_modal">View Statement</a>';
        $html .= '<a class="k-button sidebar-btn btn btn-default" href="/'.$documents_url.'/edit?supplier_id='.$account_id.'" data-target="sidebarform">Create Order</a></div>';
        $html .= '<ul class="list-group">';
        $docs = get_creditor_transactions($account_id);
        $docs = collect($docs)->sortByDesc('docdate')->take(10);
        foreach($docs as $d){
            
            $html .= ' <li class="list-group-item">
            '.$d->doctype.' '.$d->doc_no.' '.$d->reference.'</br>
            '.currency_formatted($d->total,$currency).'</br>
            <span class="text-muted">'.$d->docdate.'</span></br>';
            if($d->doctype != 'Cashbook Supplier Payment' && $d->doctype != 'General Journal'){
                $html .= '<a class="k-button btn btn-sm sidebar-btn" href="/'.$documents_url.'?id='.$d->id.'" target="_blank">View</a>';
            }
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        
    }
    return $html;
    
}


function sidebar_get_statement_listview_json($type,$account_id){
 
    $listview = [];
    
    $users = \DB::table('erp_users')->where('account_id',1)->get();
   
    $cashbooks = \DB::table('acc_cashbook')->get();
    if($type == 'account'){
        $documents_url = get_menu_url_from_module_id(353);
        
       
        $cashbook_url = get_menu_url_from_module_id(1837);
        $currency = get_account_currency($account_id);

        $html .= '<ul class="list-group">';
        $docs = get_debtor_transactions_including_pending($account_id);
        
        $docs = collect($docs)->map(function ($item) {
        $item->period = Carbon::parse($item->docdate)->format('Y-m');
        return $item;
        });
        
        
        $account = dbgetaccount($account_id);
        if($account->partner_id !=1){
            $account = dbgetaccount($account->partner_id);
        }
        $balance = $account->balance;
        foreach($docs as $d){
            $salesman_name = '';
            if($d->salesman_id){
                $salesman_name = $users->where('id',$d->salesman_id)->pluck('full_name')->first();
            }
            $html .= ' <li class="list-group-item">';
            $html .= ' <div class="row m-0 p-0"><div class="col m-0 p-0">';
            $html .= '<span class="">'.$d->docdate.' #'.$d->doc_no.'</span>';
            
            $html .= '</br>'.currency_formatted($d->total,$currency).'</br>';
            if($d->reference > ''){
                $html .= $d->reference;
            }
            
            //if($salesman_name > ''){
            //    $html .= 'Salesman: '.$salesman_name.'</br>';
            //} 
            if($d->doctype == 'Cashbook Customer Receipt'){
                $html .= '<a class="btn btn-sm sidebar-btn stretched-link p-0 mb-0" style="line-height:0px !important; height:0px !important; opacity:0" href="/'.$cashbook_url.'?id='.$d->id.'" target="_blank"></a>';
            }elseif($d->doctype != 'Cashbook Customer Receipt' && $d->doctype != 'General Journal'){
                $html .= '<a class="btn btn-sm sidebar-btn stretched-link p-0 mb-0" style="line-height:0px !important; height:0px !important; opacity:0" href="/'.$documents_url.'?id='.$d->id.'" target="_blank"></a>';
            }
            $html .= '</div><div class="col col-auto text-end">';
           
            if($d->doctype == 'Cashbook Customer Receipt'){
                $cashbook_name = $cashbooks->where('id',$d->cashbook_id)->pluck('name')->first();
                $html .= $d->doctype.'</br>';
                $html .= '<b>'.$cashbook_name.'</b></br>';
            }else{
                $html .= $d->doctype.'</br>';
            }
            
            $html .= '<br>Balance: '. currency_formatted($balance,$currency).'<br>';
            $html .= '</div></div>';
            
           
            $html .= '</li>';
            
            if($d->doctype == 'Cashbook Customer Receipt'){
                $balance += $d->total;
            }elseif($d->doctype == 'Tax Invoice'){
                $balance -= $d->total;
            }elseif($d->doctype == 'Credit Note'){
                $balance += $d->total;
            }else{
                $balance -= $d->total;
            }
        }
        
        $html .= '</ul>';
        
    }
    if($type == 'supplier'){
    $documents_url = get_menu_url_from_module_id(354);
        $currency = get_supplier_currency($account_id);
        $html .= '<div class="k-widget k-button-group"><a class="btn btn-default sidebar-btn" href="/supplier_statement_pdf/'.$account_id.'" data-target="view_modal">View Statement</a>';
        $html .= '<a class="k-button sidebar-btn btn btn-default" href="/'.$documents_url.'/edit?supplier_id='.$account_id.'" data-target="sidebarform">Create Order</a></div>';
        $html .= '<ul class="list-group">';
        $docs = get_creditor_transactions($account_id);
        $docs = collect($docs)->sortByDesc('docdate')->take(10);
        foreach($docs as $d){
            
            $html .= ' <li class="list-group-item">
            '.$d->doctype.' '.$d->doc_no.' '.$d->reference.'</br>
            '.currency_formatted($d->total,$currency).'</br>
            <span class="text-muted">'.$d->docdate.'</span></br>';
            if($d->doctype != 'Cashbook Supplier Payment' && $d->doctype != 'General Journal'){
                $html .= '<a class="k-button btn btn-sm sidebar-btn" href="/'.$documents_url.'?id='.$d->id.'" target="_blank">View</a>';
            }
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        
    }
    return $html;
    
}

function sidebar_get_statement_listview($type,$account_id){
    /*
             'id' => 'sub'.$s->id,
                                'text' => $s->detail,
                                'cssClass' => 'sidebar_subscription_info',
                                'accountid' => $s->account_id,
                                'subid' => $s->id,
                                'tocancel' => $s->to_cancel,
                                'provisiontype' => $s->provision_type,
                                'bill_frequency' => $s->bill_frequency,
                                
                            ];
                        }
                        $product_list[] = [
                            'id' => 'subproduct'.$j,
                            'text' => $product,
                            'items' => $product_sub_list,
                        ];
                    }
                }
                
                $subs_listview[] = [
                    'id' => 'subdepartment'.$i,
                    'text' => $department,
                    'items' => $product_list,
                ];
    */
    $listview = [];
    
    $users = \DB::table('erp_users')->where('account_id',1)->get();
   
    $cashbooks = \DB::table('acc_cashbook')->get();
    if($type == 'account'){
        $documents_url = get_menu_url_from_module_id(353);
        
       
        $cashbook_url = get_menu_url_from_module_id(1837);
        $currency = get_account_currency($account_id);

        $html .= '<ul class="list-group">';
        $docs = get_debtor_transactions_including_pending($account_id);
        $docs = collect($docs)->sortByDesc('docdate')->take(10);
        $account = dbgetaccount($account_id);
        if($account->partner_id !=1){
            $account = dbgetaccount($account->partner_id);
        }
        $balance = $account->balance;
        foreach($docs as $d){
            $salesman_name = '';
            if($d->salesman_id){
                $salesman_name = $users->where('id',$d->salesman_id)->pluck('full_name')->first();
            }
            $html .= ' <li class="list-group-item">';
            $html .= ' <div class="row m-0 p-0"><div class="col m-0 p-0">';
            $html .= '<span class="">'.$d->docdate.' #'.$d->doc_no.'</span>';
            
            $html .= '</br>'.currency_formatted($d->total,$currency).'</br>';
            if($d->reference > ''){
                $html .= $d->reference;
            }
            
            //if($salesman_name > ''){
            //    $html .= 'Salesman: '.$salesman_name.'</br>';
            //} 
            if($d->doctype == 'Cashbook Customer Receipt'){
                $html .= '<a class="btn btn-sm sidebar-btn stretched-link p-0 mb-0" style="line-height:0px !important; height:0px !important; opacity:0" href="/'.$cashbook_url.'?id='.$d->id.'" target="_blank"></a>';
            }elseif($d->doctype != 'Cashbook Customer Receipt' && $d->doctype != 'General Journal'){
                $html .= '<a class="btn btn-sm sidebar-btn stretched-link p-0 mb-0" style="line-height:0px !important; height:0px !important; opacity:0" href="/'.$documents_url.'?id='.$d->id.'" target="_blank"></a>';
            }
            $html .= '</div><div class="col col-auto text-end">';
           
            if($d->doctype == 'Cashbook Customer Receipt'){
                $cashbook_name = $cashbooks->where('id',$d->cashbook_id)->pluck('name')->first();
                $html .= $d->doctype.'</br>';
                $html .= '<b>'.$cashbook_name.'</b></br>';
            }else{
                $html .= $d->doctype.'</br>';
            }
            
            $html .= '<br>Balance: '. currency_formatted($balance,$currency).'<br>';
            $html .= '</div></div>';
            
           
            $html .= '</li>';
            
            if($d->doctype == 'Cashbook Customer Receipt'){
                $balance += $d->total;
            }elseif($d->doctype == 'Tax Invoice'){
                $balance -= $d->total;
            }elseif($d->doctype == 'Credit Note'){
                $balance += $d->total;
            }else{
                $balance -= $d->total;
            }
        }
        
        $html .= '</ul>';
        
    }
    if($type == 'supplier'){
    $documents_url = get_menu_url_from_module_id(354);
        $currency = get_supplier_currency($account_id);
        $html .= '<div class="k-widget k-button-group"><a class="btn btn-default sidebar-btn" href="/supplier_statement_pdf/'.$account_id.'" data-target="view_modal">View Statement</a>';
        $html .= '<a class="k-button sidebar-btn btn btn-default" href="/'.$documents_url.'/edit?supplier_id='.$account_id.'" data-target="sidebarform">Create Order</a></div>';
        $html .= '<ul class="list-group">';
        $docs = get_creditor_transactions($account_id);
        $docs = collect($docs)->sortByDesc('docdate')->take(10);
        foreach($docs as $d){
            
            $html .= ' <li class="list-group-item">
            '.$d->doctype.' '.$d->doc_no.' '.$d->reference.'</br>
            '.currency_formatted($d->total,$currency).'</br>
            <span class="text-muted">'.$d->docdate.'</span></br>';
            if($d->doctype != 'Cashbook Supplier Payment' && $d->doctype != 'General Journal'){
                $html .= '<a class="k-button btn btn-sm sidebar-btn" href="/'.$documents_url.'?id='.$d->id.'" target="_blank">View</a>';
            }
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        
    }
    return $html;
    
}

function sidebar_get_interactions_emails($account_id){
    $html = '<ul class="list-group">';
    $emails = \DB::connection('default')->table('erp_communication_lines')->select('destination','created_at','id','subject', 'success', 'error')->where('account_id',$account_id)->orderBy('created_at','desc')->limit(20)->get();
    foreach($emails as $email){
        
        $html .= ' <li class="list-group-item">
        '.$email->destination.'</br>
        '.$email->subject.'</br>';
        if($email->success){
            $html .= 'Email Sent</br>';
        }else{
            $html .= 'Email Error</br>
            '.$email->error.'</br>';
        }
        
        $html .= '<span class="text-muted">'.$email->created_at.'</span></br>
        <a class="k-button btn btn-sm sidebar-btn" href="/communications_view_email/'.$email->id.'" data-target="view_modal">View Email</a>
        </li>';
    }
    
    $html .= '</ul>';
    
    return $html;
    
}
function sidebar_get_interactions_calls($account_id){
    $html = '<ul class="list-group">';
   
    $calls = \DB::connection('default')->table('erp_call_history')->select('destination','created_at','type','source')->where('account_id',$account_id)->orderBy('created_at','desc')->limit(20)->get();
   
    foreach($calls as $call){
        $html .= ' <li class="list-group-item">
        '.$call->type.'</br>
        '.$call->source.' - '.$call->destination.'</br>
        <span class="text-muted">'.$call->created_at.'</span>
        </li>';
    }
    $html .= '</ul>';
    /*
    
        <span class="text-muted">'.Carbon\Carbon::parse($email->created_at)->toDateTimeString().'</span>
    */
    
    return $html;
    
}
function sidebar_get_interactions_smses($account_id){
    $html = '<ul class="list-group">';
    $smses = \DB::connection('default')->table('isp_sms_messages')->select('queuetime','numbers', 'message')->where('to_account_id',$account_id)->orderBy('queuetime','desc')->limit(20)->get();
   
    foreach($smses as $sms){
        
        $html .= ' <li class="list-group-item">
        '.$sms->numbers.'</br>
        '.substr($sms->message,0,30).'...</br>';
        
        
        $html .= '<span class="text-muted">'.$sms->queuetime.'</span>
        </li>';
    }
    /*
    
        <span class="text-muted">'.Carbon\Carbon::parse($email->created_at)->toDateTimeString().'</span>
    */
    
    return $html;
    
}
function sidebar_get_subscriptions($module_id,$row_id,$account_id){
   
    if($module_id == 353){
        $reseller_user = \DB::connection('default')->table('crm_documents')->where('id',$row_id)->pluck('reseller_user')->first();
        if($reseller_user){
            $account_id = $reseller_user;
        }
    }
  
    if($module_id == 334){
        $subs = \DB::connection('default')->table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
        ->select('crm_product_categories.department as department','crm_product_categories.name as category','crm_products.name','provision_type','crm_products.code','sub_services.detail','sub_services.id','sub_services.product_id','sub_services.status','sub_services.provision_type','sub_services.to_cancel','sub_services.account_id','sub_services.pbx_domain','sub_services.price_incl','sub_services.bill_frequency')
        ->where('sub_services.status','!=','Deleted')
        ->where('account_id',$account_id)
        ->where('sub_services.id',$row_id)
        ->orderBy('crm_products.sort_order')->orderBy('sub_services.detail')->get();
    }else{
        $subs = \DB::connection('default')->table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
        ->select('crm_product_categories.department as department','crm_product_categories.name as category','crm_products.name','provision_type','crm_products.code','sub_services.detail','sub_services.id','sub_services.product_id','sub_services.status','sub_services.provision_type','sub_services.to_cancel','sub_services.account_id','sub_services.pbx_domain','sub_services.price_incl','sub_services.bill_frequency')
        ->where('sub_services.status','!=','Deleted')
        ->where('account_id',$account_id)
        ->orderBy('crm_products.sort_order')->orderBy('sub_services.detail')->get();
    }
    $subs = $subs->groupBy('department');
    
    $subscriptions_url = get_menu_url_from_module_id(334);
    $delete_access = \DB::table('erp_forms')->where('role_id',session('role_id'))->where('module_id',334)->pluck('is_delete')->first();
    if(is_superadmin()){$delete_access=1;}
    $account = dbgetaccount($account_id);
    
   
    $pbx_type = '';
    if(!empty($account->pabx_domain)){
        $pbx_type =$account->pabx_type;
    }
   
   $currency = get_account_currency($account_id);
    
   
    // subscriptions accordion by category
    $subs_accordion = [];
    foreach($subs as $group => $list){
        $html = '<ul class="list-group">';
        foreach($list as $sub){
            $html .= ' <li class="list-group-item sidebar_subscription_info"';
            if(!empty($sub->pbx_domain)){
            $html .= ' data-domain_uuid="'.$account->domain_uuid.'" data-pbx_domain="'.$sub->pbx_domain.'"  data-pbx_type="'.$pbx_type.'" ';
            }else{
            $html .= ' data-pbx_domain=""  data-pbx_type="" ';
            }
            $html .= ' data-account-id="'.$sub->account_id.'" data-sub-id="'.$sub->id.'" data-tocancel="'.$sub->to_cancel.'" data-provision_type="'.$sub->provision_type.'">';
            $html .= ' <div class="row m-0 p-0"><div class="col m-0 p-0">';
            $html .= $sub->name.' <br> '.$sub->code.' <br> Status: '.$sub->status;
            if($sub->to_cancel){
               $html .= ' & Cancelled';  
            }
            
            $html .= '</div><div class="col col-auto text-end">';
            $html .= $sub->detail;
            
            if($sub->provision_type == 'pbx_extension' || $sub->provision_type == 'sip_trunk'){
                $reg = \DB::connection('pbx')->table('v_extensions')->where('accountcode',$sub->pbx_domain)->where('extension',$sub->detail)->pluck('registration_status')->first();
                if(empty($reg)){
                    $reg = 'Not registered';
                }
                $html .= '<br>'.$reg;
            }
            
            
            $html .= '<br>Price Incl: '.currency_formatted($sub->price_incl,$currency);
            if($sub->bill_frequency == 12){
            $html .= '<br>Bill Frequency: Annually' ;
                
            }elseif($sub->bill_frequency == 1){
            $html .= '<br>Bill Frequency: Monthly' ;
            }else{
            $html .= '<br>Bill Frequency: Every '.$sub->bill_frequency.' months' ;
            }
            
            /*
            $html .= '<br><a target="_blank" href="'. url('manage_service/'.$sub->id) .'" class="me-3" data-bs-toggle="tooltip" title="Manage service">
            <i class="fas fa-wrench text-secondary"></i>
            </a>
            <a href="'. url('service_setup_email/'.$sub->id) .'" data-target="form_modal" class="me-3" data-bs-toggle="tooltip" title="Setup instructions">
            <i class="fas fa-envelope text-secondary"></i>
            </a>
            
            <a href="'. url($subscriptions_url.'/cancel/'.$sub->id) .'" class="me-3" data-target="ajaxconfirm" href="javascript:;" data-bs-toggle="tooltip" itle="Cancel subscription">
            <i class="fas fa-trash text-secondary"></i>
            </a>';
            if($sub->status != 'Deleted' && in_array($sub->provision_type,['hosting' ,'airtime_contract'  ,'pbx_extension','sip_trunk'])){
             $html .= '<a href="'. url('subscription_migrate_form/'.$sub->id) .'" data-target="form_modal"  data-bs-toggle="tooltip" title="Migrate subscription">
            <i class="fas fa-box-open text-secondary"></i>
            </a>';
            }*/
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        
        $subs_accordion[] = (object) ['header'=>$group.' Subscriptions','content'=>$html,'cssClass'=>'sidebar-acc-header'];
    }
    
   
   
    return $subs_accordion;
    
}


function sidebar_get_subscriptions_listview($module_id,$row_id,$account_id = false){
   
    if($module_id == 353){
        $reseller_user = \DB::connection('default')->table('crm_documents')->where('id',$row_id)->pluck('reseller_user')->first();
        if($reseller_user){
            $account_id = $reseller_user;
        }
    }
    if(!$account_id && session('role_level') != 'Admin'){
        return [];
    }
    
    if(!$account_id && session('role_level') == 'Admin'){
        $subs = \DB::connection('default')->table('sub_services')
        ->join('crm_accounts','crm_accounts.id','=','sub_services.account_id')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
        ->select('crm_accounts.company','sub_services.id','sub_services.bill_frequency','crm_product_categories.department as department','crm_products.name','provision_type','sub_services.detail','sub_services.to_cancel','sub_services.account_id')
        ->where('sub_services.status','!=','Deleted')
        ->orderBy('crm_products.sort_order')->orderBy('crm_accounts.company')->orderBy('sub_services.detail')->get();
  
    }elseif($module_id == 334){
        $subs = \DB::connection('default')->table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
        ->select('sub_services.id','sub_services.bill_frequency','crm_product_categories.department as department','crm_products.name','provision_type','sub_services.detail','sub_services.to_cancel','sub_services.account_id')
        ->where('sub_services.status','!=','Deleted')
        ->where('account_id',$account_id)
        ->where('sub_services.id',$row_id)
        ->orderBy('crm_products.sort_order')->orderBy('sub_services.detail')->get();
    }else{
        $subs = \DB::connection('default')->table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
        ->select('sub_services.id','sub_services.bill_frequency','crm_product_categories.department as department','crm_products.name','provision_type','sub_services.detail','sub_services.to_cancel','sub_services.account_id')
        ->where('sub_services.status','!=','Deleted')
        ->where('account_id',$account_id)
        ->orderBy('crm_products.sort_order')->orderBy('sub_services.detail')->get();
    }
  
    
    $subscriptions_url = get_menu_url_from_module_id(334);
    $delete_access = \DB::table('erp_forms')->where('role_id',session('role_id'))->where('module_id',334)->pluck('is_delete')->first();
    if(is_superadmin()){$delete_access=1;}
    $account = dbgetaccount($account_id);
    
   
    $pbx_type = '';
    if(!empty($account->pabx_domain)){
        $pbx_type =$account->pabx_type;
    }
   
   $currency = get_account_currency($account_id);
    
           
    // subscriptions accordion by category
    $subs_listview = [];
    if($module_id == 334){
         
        foreach($subs as $s){
           
            $subs_listview[] = [
                'id' => 'sub'.$s->id,
                'text' => $s->detail,
                'cssClass' => 'sidebar_subscription_info',
                'accountid' => $s->account_id,
                'subid' => $s->id,
                'tocancel' => $s->to_cancel,
                'provisiontype' => $s->provision_type,
                'bill_frequency' => $s->bill_frequency,
                
            ];
        }
     }else{
         
         if(!$account_id){
          
            $departments = $subs->pluck('department')->unique()->toArray();
         
            foreach($departments as $i => $department){
                $company_list = [];
                $account_ids = $subs->where('department',$department)->pluck('account_id')->unique()->toArray();
                if(count($account_ids) > 0){
                    foreach($account_ids as $account_id){
                        $products = $subs->where('account_id',$account_id)->where('department',$department)->pluck('name')->unique()->toArray();
                        if(count($products) > 0){
                            $product_list = [];
                            foreach($products as $j => $product){
                                $product_subs = $subs->where('account_id',$account_id)->where('name',$product)->all();
                              
                                if(count($product_subs) > 0){
                                    $product_sub_list = [];
                                    foreach($product_subs as $s){
                                       
                                        $product_sub_list[] = [
                                            'id' => 'sub'.$s->id,
                                            'text' => $s->detail,
                                            'cssClass' => 'sidebar_subscription_info',
                                            'accountid' => $s->account_id,
                                            'subid' => $s->id,
                                            'tocancel' => $s->to_cancel,
                                            'provisiontype' => $s->provision_type,
                                            'bill_frequency' => $s->bill_frequency,
                                            
                                        ];
                                    }
                                    $product_list[] = [
                                        'id' => 'subproduct'.$j,
                                        'text' => $product,
                                        'items' => $product_sub_list,
                                    ];
                                }
                            }
                            
                            $company = $subs->where('account_id',$account_id)->pluck('company')->first();
                            $company_list[] = [
                                'id' => 'subaccount'.$account_id,
                                'text' => $company,
                                'items' => $product_list,
                            ];
                        }
                    }
                    
                    if(count($company_list) > 0){         
                        $subs_listview[] = [
                            'id' => 'subdepartment'.$i,
                            'text' => $department,
                            'items' => $company_list,
                        ];
                    }
                }
            }
             
         }else{
         
            $departments = $subs->pluck('department')->unique()->toArray();
            foreach($departments as $i => $department){
                $products = $subs->where('department',$department)->pluck('name')->unique()->toArray();
                if(count($products) > 0){
                    $product_list = [];
                    foreach($products as $j => $product){
                        $product_subs = $subs->where('name',$product)->all();
                      
                        if(count($product_subs) > 0){
                            $product_sub_list = [];
                            foreach($product_subs as $s){
                               
                                $product_sub_list[] = [
                                    'id' => 'sub'.$s->id,
                                    'text' => $s->detail,
                                    'cssClass' => 'sidebar_subscription_info',
                                    'accountid' => $s->account_id,
                                    'subid' => $s->id,
                                    'tocancel' => $s->to_cancel,
                                    'provisiontype' => $s->provision_type,
                                    'bill_frequency' => $s->bill_frequency,
                                    
                                ];
                            }
                            $product_list[] = [
                                'id' => 'subproduct'.$j,
                                'text' => $product,
                                'items' => $product_sub_list,
                            ];
                        }
                    }
                    
                    $subs_listview[] = [
                        'id' => 'subdepartment'.$i,
                        'text' => $department,
                        'items' => $product_list,
                    ];
                }
            }
        }
    }
   
   
    return $subs_listview;
    
}


function sidebar_get_subscriptions_listview_all($module_id,$row_id){
   
    
    if(session('role_level') == 'Customer'){
        return sidebar_get_subscriptions_listview($module_id,$row_id,session('account_id'));
    }
    if(session('role_level') == 'Admin'){
        $account_ids = \DB::connection('default')->table('sub_services')->where('sub_services.status','!=','Deleted')->pluck('account_id')->unique()->toArray();
    }elseif(session('role_level') == 'Partner'){
        $partner_account_ids = \DB::connection('default')->table('crm_accounts')->where('partner_id',session('account_id'))->pluck('id')->unique()->toArray();
        $account_ids = \DB::connection('default')->table('sub_services')->whereIn('account_id',$partner_account_ids)->where('sub_services.status','!=','Deleted')->pluck('account_id')->unique()->toArray();
        
    } 
    
    $subs = \DB::connection('default')->table('sub_services')
    ->join('crm_products','crm_products.id','=','sub_services.product_id')
    ->join('crm_accounts','crm_accounts.id','=','sub_services.account_id')
    ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
    ->select('crm_accounts.company','sub_services.id','sub_services.bill_frequency','crm_product_categories.department as department','crm_products.name','provision_type','sub_services.detail','sub_services.to_cancel','sub_services.account_id')
    ->where('sub_services.status','!=','Deleted')
    ->whereIn('account_id',$account_ids)
    ->orderBy('crm_accounts.company')->orderBy('crm_products.sort_order')->orderBy('sub_services.detail')->get();
    
  
    
    $subscriptions_url = get_menu_url_from_module_id(334);
    $delete_access = \DB::table('erp_forms')->where('role_id',session('role_id'))->where('module_id',334)->pluck('is_delete')->first();
    if(is_superadmin()){$delete_access=1;}
    $account = dbgetaccount($account_id);
    
   
    $pbx_type = '';
    if(!empty($account->pabx_domain)){
        $pbx_type =$account->pabx_type;
    }
   
   $currency = get_account_currency($account_id);
    
           
    // subscriptions accordion by category
    $subs_listview = [];
     
    $companies = $subs->pluck('company','account_id')->unique()->toArray();
    foreach($companies as $account_id => $company){
        
        $departments = $subs->where('account_id',$account_id)->pluck('department')->unique()->toArray();
        $departments_list = [];
        foreach($departments as $i => $department){
            $products = $subs->where('account_id',$account_id)->where('department',$department)->pluck('name')->unique()->toArray();
            if(count($products) > 0){
                $product_list = [];
                foreach($products as $j => $product){
                    $product_subs = $subs->where('account_id',$account_id)->where('name',$product)->all();
                  
                    if(count($product_subs) > 0){
                        $product_sub_list = [];
                        foreach($product_subs as $s){
                           
                            $product_sub_list[] = [
                                'id' => 'sub'.$s->id,
                                'text' => $s->detail,
                                'cssClass' => 'sidebar_subscription_info',
                                'accountid' => $s->account_id,
                                'subid' => $s->id,
                                'tocancel' => $s->to_cancel,
                                'provisiontype' => $s->provision_type,
                                'bill_frequency' => $s->bill_frequency,
                                
                            ];
                        }
                        $product_list[] = [
                            'id' => 'subproduct'.$j,
                            'text' => $product,
                            'items' => $product_sub_list,
                        ];
                    }
                }
                
                $departments_list[] = [
                    'id' => 'subdepartment'.$i,
                    'text' => $department,
                    'items' => $product_list,
                ];
            }
        }
        
            
                
        $subs_listview[] = [
            'id' => 'subcompany'.$account_id,
            'text' => $company,
            'items' => $departments_list,
        ];
    }
    
   
    return $subs_listview;
    
}

function sidebar_get_subscription_emails($module_id,$row_id,$account_id){
    
     if($module_id == 353){
        $reseller_user = \DB::connection('default')->table('crm_documents')->where('id',$row_id)->pluck('reseller_user')->first();
        if($reseller_user){
            $account_id = $reseller_user;
        }
    }
    $html = '<ul class="list-group">';
    if($module_id == 334){
        $subs = \DB::connection('default')->table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->select('crm_products.name','provision_type','crm_products.code','sub_services.detail','sub_services.id','sub_services.product_id','sub_services.status','sub_services.provision_type','sub_services.to_cancel')
        ->where('sub_services.status','!=','Deleted')
        ->where('account_id',$account_id)
        ->where('sub_services.id',$row_id)
        ->orderBy('sub_services.provision_type')->orderBy('sub_services.detail')->get();
    }else{
        $subs = \DB::connection('default')->table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->select('crm_products.name','provision_type','crm_products.code','sub_services.detail','sub_services.id','sub_services.product_id','sub_services.status','sub_services.provision_type','sub_services.to_cancel')
        ->where('sub_services.status','!=','Deleted')
        ->where('account_id',$account_id)
        ->orderBy('sub_services.provision_type')->orderBy('sub_services.detail')->get();
    }

    $html = '<ul class="list-group">';
 
    
    foreach($subs as $sub){
        $html .= ' <li class="list-group-item">
        <div class="row">
       <div class="col"> '.$sub->name.' - '.$sub->code.' - '.$sub->detail.'</div>';
        $html .= '<div class="col-auto">';
        $url = '/service_setup_email/'.$sub->id;
        
        $html .='<a class="stretched-link btn btn-icon float-end mb-0 p-2" href="'.$url.'" data-target="form_modal" title="Send"><i class="fas fa-envelope"></i></a>';
        
        $html .= '</div></div>';
        $html .= '</li>';
    }
    
   
    $html .= '</ul>';
    
    return $html;
    
}

function sidebar_get_newsletters_emails_list($module_id,$row_id,$account_id){
    
    
    $list = [];
    $newsletters = \DB::connection('default')->table('crm_newsletters')->where('is_deleted',0)->where('type','Advertising')->orderBy('name')->get();
    $types = $newsletters->pluck('type')->unique()->toArray();
    $newsletters_url = get_menu_url_from_table('crm_newsletters');
    foreach($types as $type){

        $html = '<ul class="list-group">';
     
        
        foreach($newsletters as $newsletter){
            $html .= ' <li class="list-group-item newsletter_context" data-route_url="'.$newsletters_url.'" data-edit_url="'.$newsletters_url.'/edit/'.$newsletter->id.'" data-edit_id="'.$newsletter->id.'">
            '.$newsletter->name.'';
            
            $url = '/email_form/default/'.$account_id.'?newsletter_id='.$newsletter->id;
            
            $html .='<a class="stretched-link btn btn-icon float-end mb-0 p-2" href="'.$url.'" data-target="form_modal" title="Send"><i class="fas fa-envelope"></i></a>';
            
            $html .= '</li>';
        }
        
       
        $html .= '</ul>';
        $list[] = (object) ['header'=>'Newsletter - '.$type,'content'=>$html];
    }
    return $list;
    
}
function sidebar_get_debtor_emails($module_id,$row_id,$account_id){
    
    $newsletters = \DB::connection('default')->table('crm_email_manager')->where('debtor_email',1)->orderBy('name')->get();
    $html = '<ul class="list-group">';
    
    $newsletters_url = get_menu_url_from_table('crm_email_manager');
    foreach($newsletters as $newsletter){
        $html .= ' <li class="list-group-item newsletter_context" data-route_url="'.$newsletters_url.'" data-edit_url="'.$newsletters_url.'/edit/'.$newsletter->id.'" data-edit_id="'.$newsletter->id.'">
        '.$newsletter->name.'';
        if($newsletter->attach_letter_of_demand){
            $html.= ' (Letter of demand)';
        }
        
        $url = '/email_form/default/'.$account_id.'?notification_id='.$newsletter->id;
        
        $html .='<a class="stretched-link btn btn-icon float-end mb-0 p-2" href="'.$url.'" data-target="form_modal" title="Send"><i class="fas fa-envelope"></i></a>';
        
        $html .= '</li>';
    }
   
    $html .= '</ul>';
    
    return $html;
}

function sidebar_get_pricing_emails($module_id,$row_id,$account_id){
    
    $newsletters = \DB::connection('default')->table('crm_email_manager')->whereIn('id',[478,591,590,589])->orderBy('name')->get();
    $html = '<ul class="list-group">';
    $newsletters_url = get_menu_url_from_table('crm_email_manager');
    
    foreach($newsletters as $newsletter){
        $html .= ' <li class="list-group-item newsletter_context" data-route_url="'.$newsletters_url.'" data-edit_url="'.$newsletters_url.'/edit/'.$newsletter->id.'" data-edit_id="'.$newsletter->id.'">
        '.$newsletter->name.'';
        
        $url = '/email_form/default/'.$account_id.'?notification_id='.$newsletter->id;
        
        $html .='<a class="stretched-link btn btn-icon float-end mb-0 p-2" href="'.$url.'" data-target="form_modal" title="Send"><i class="fas fa-envelope"></i></a>';
        
        $html .= '</li>';
    }
   
    $html .= '</ul>';
    
    return $html;
    
}

function sidebar_get_email_form_emails($module_id,$row_id,$account_id){
    
    $newsletters = \DB::connection('default')->table('crm_email_manager')->where('email_form',1)->whereNotIn('id',[478,591,590,589])->orderBy('name')->get();
    $html = '<ul class="list-group">';
    $newsletters_url = get_menu_url_from_table('crm_email_manager');
    
    foreach($newsletters as $newsletter){
        $html .= ' <li class="list-group-item newsletter_context" data-route_url="'.$newsletters_url.'" data-edit_url="'.$newsletters_url.'/edit/'.$newsletter->id.'" data-edit_id="'.$newsletter->id.'">
        '.$newsletter->name.'';
        
        $url = '/email_form/default/'.$account_id.'?notification_id='.$newsletter->id;
        
        $html .='<a class="stretched-link btn btn-icon float-end mb-0 p-2" href="'.$url.'" data-target="form_modal" title="Send"><i class="fas fa-envelope"></i></a>';
        
        $html .= '</li>';
    }
    $newsletter = \DB::connection('default')->table('crm_email_manager')->where('internal_function','create_account_settings')->get()->first();
    if($account_id > 0){
        $users = \DB::connection('default')->table('erp_users')->where('account_id',$account_id)->where('is_deleted',0)->get();
        
        foreach($users as $user){
            $html .= ' <li class="list-group-item newsletter_context" data-route_url="'.$newsletters_url.'" data-edit_url="'.$newsletters_url.'/edit/'.$newsletter->id.'" data-edit_id="'.$newsletter->id.'">
            Reset and send password ('.$user->full_name.')';
            
            $url = '/email_form/default/'.$account_id.'?user_id='.$user->id.'&notification_id='.$newsletter->id;
            
            $html .='<a class="stretched-link btn btn-icon float-end mb-0 p-2" href="'.$url.'" data-target="form_modal" title="Send"><i class="fas fa-envelope"></i></a>';
            
            $html .= '</li>';
        }
    }
   
    $html .= '</ul>';
    
    return $html;
}

function sidebar_get_faqs($module_id,$row_id,$account_id){
    
    
    if(is_superadmin()){
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->where('is_deleted',0)->orderBy('type')->orderBy('name')->get();
    }elseif(session('role_level') == 'Admin'){
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('level',['Admin','Customer','Reseller'])->where('internal',0)->where('is_deleted',0)->orderBy('type')->orderBy('name')->get();
    }elseif(session('role_level') == 'Partner'){
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('level',['Customer','Reseller'])->where('internal',0)->where('is_deleted',0)->orderBy('type')->orderBy('name')->get();
    }elseif(session('role_level') == 'Customer'){
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('level',['Customer'])->where('internal',0)->where('is_deleted',0)->orderBy('type')->orderBy('name')->get();
    }

    $html = '<ul class="list-group">';
    $newsletters_url = get_menu_url_from_table('hd_customer_faqs');
    
    foreach($faqs as $faq){
        $html .= ' <li class="list-group-item  newsletter_context" data-route_url="'.$newsletters_url.'" data-edit_url="'.$newsletters_url.'/edit/'.$newsletter->id.'" data-edit_id="'.$newsletter->id.'">
        '.$faq->type . ' - '.$faq->name.'';
        
        $url = '/email_form/default/'.$account_id.'?faq_id='.$faq->id;
        
        $html .='<a class="stretched-link btn btn-icon float-end mb-0 p-2" href="'.$url.'" data-target="form_modal" title="Send"><i class="fas fa-envelope"></i></a>';
        
        $html .= '</li>';
    }
    
   
    $html .= '</ul>';
    
    return $html;
    
}


function sidebar_get_kb_listview($module_id,$row_id,$account_id = false){
    $response_items = [];
    $type_response_items = [];
    $types = [];
    
    if($internal && !is_superadmin()){
        return response()->json(['items' => [] ]);
    }
    $internal = ($internal) ? 1 : 0;
    
    $departments = [];
    
    if(!$account_id && session('role_level') != 'Admin'){
        return [];
    }
    
    if(!$account_id && session('role_level') == 'Admin'){
        $departments = \DB::table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
        ->where('sub_services.status','!=','Deleted')
        ->pluck('crm_product_categories.department')->unique()->filter()->toArray();
    }else{
        $departments = \DB::table('sub_services')
        ->join('crm_products','crm_products.id','=','sub_services.product_id')
        ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
        ->where('sub_services.account_id',$account_id)
        ->where('sub_services.status','!=','Deleted')
        ->pluck('crm_product_categories.department')->unique()->filter()->toArray();
    }
    $departments[] = 'Customer Info Request';
    if(count($departments) > 0){
        if(is_superadmin()){
            $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('type',$departments)->where('internal',$internal)->where('is_deleted',0)->orderBy('sort_order')->get();
        }elseif(session('role_level') == 'Admin'){
            $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('type',$departments)->whereIn('level',['Admin','Customer','Reseller'])->where('internal',0)->where('is_deleted',0)->orderBy('sort_order')->get();
        }elseif(session('role_level') == 'Partner'){
            $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('type',$departments)->whereIn('level',['Customer','Reseller'])->where('internal',0)->where('is_deleted',0)->orderBy('sort_order')->get();
        }elseif(session('role_level') == 'Customer'){
            $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('type',$departments)->whereIn('level',['Customer'])->where('internal',0)->where('is_deleted',0)->orderBy('sort_order')->get();
        }
        foreach($faqs as $faq){
            $response_items[] = [
                'id' => 'faq'.$faq->id,
                'type' => $faq->type,
                'text' => $faq->name,
                'faq_id' => $faq->id,
                'cssClass' => 'kbitem_context',
            ];
            if(!in_array($faq->type,$types)){
                $types[] = $faq->type;
            }
        }
     
        $type_response_items = [];
        foreach($types as $i => $type){
            $type_response_items[] = [
                'id' => 'faqtype'.$i,
                'type' => $type,
                'text' => $type,
                'faq_id' => 0,
                'cssClass' => 'kbtypeitem',
                'items' => array_values(collect($response_items)->where('type',$type)->toArray()),
            ];
        }
    }

    return $type_response_items;
}

function sidebar_get_product_subscriptions($product_id){
    $html = '<ul class="list-group">';
  
    $subs = \DB::connection('default')->table('sub_services')
    ->join('crm_accounts','crm_accounts.id','=','sub_services.account_id')
    ->select('crm_accounts.company','provision_type','sub_services.detail','sub_services.id','sub_services.product_id','sub_services.status','sub_services.provision_type','sub_services.to_cancel')
    ->where('sub_services.status','!=','Deleted')
    ->where('product_id',$product_id)
    ->orderBy('sub_services.account_id')->orderBy('sub_services.provision_type')->orderBy('sub_services.detail')->get();
    
    $url = get_menu_url_from_module_id(334);
   
   
    
    foreach($subs as $sub){
        $html .= ' <li class="list-group-item">
        <a class="hide-link stretched-link" href="'.$url.'?id='.$sub->id.'" target="_blank">'.$sub->company.' - '.$sub->detail.'</a>
        </li>';
    }
    
   
    $html .= '</ul>';
    
    return $html;
}

function sidebar_get_product_stock_history($product_id){
    
   
    $documents_url = get_menu_url_from_table('crm_documents');
    $supplier_documents_url = get_menu_url_from_table('crm_supplier_documents');
    $inventory_url = get_menu_url_from_table('acc_inventory');
    $product = \DB::table('crm_products')->where('id',$product_id)->get()->first();
    $qty_history = get_product_qty_history($product_id);
    $cost_history = get_product_cost_history($product_id);
   
    $stock_data = get_stock_balance($product_id);


    $stock_data['stock_value'] = $stock_data['qty_on_hand'] * $stock_data['cost_price'];
    if ('Stock' != $product->type) {
        $stock_data['qty_on_hand'] = 0;
        $stock_data['stock_value'] = 0;
    }

    $view .= '<div class="p-1 text-left sidebartables">';
    $view .= '<h3>'.$product->code.'</h3>';

    $view .= '<h6>Quantity History</h6>';
    if(count($qty_history) == 0){
        $view .= '<p>No records to display</p>';
    }else{
        $view .= '<div class="table-responsive">
        <table class="table table-sm">';
        $view .= '<thead><tr>';
        $view .= '<th>Document<th><th>Docdate</th><th class="text-end">Quantity</th><th class="text-end">Balance</th>';
        $view .= '</tr></thead><tbody>';
        foreach ($qty_history as $d) {
            if (!empty($d->customer_doctype)) {
                $view .= '<tr><td><a href="/'.$documents_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">'.$d->customer_doctype.' #'.$d->document_id.'</a><td><td>'.date('Y-m-d',strtotime($d->docdate)).'</td><td class="text-end">'.$d->qty_change.'</td><td class="text-end">'.currency($d->qty_new).'</td></tr>';
            } elseif (!empty($d->supplier_doctype)) {
                $view .= '<tr><td><a href="/'.$supplier_documents_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">'.$d->supplier_doctype.' #'.$d->supplier_document_id.'</a><td><td>'.date('Y-m-d',strtotime($d->docdate)).'</td><td class="text-end">'.$d->qty_change.'</td><td class="text-end">'.currency($d->qty_new).'</td></tr>';
            } else{
                $view .= '<tr><td><a href="/'.$inventory_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">Inventory adjustment '.$d->id.'</a><td><td>'.date('Y-m-d',strtotime($d->docdate)).'</td><td class="text-end">'.$d->qty_change.'</td><td class="text-end">'.currency($d->qty_new).'</td></tr>';
            }
        }
        $view .= '</tbody></table></div>';
    }
    
   
    $view .= '<h6>Cost History</h6>';
    if(count($cost_history) == 0){
        $view .= '<p>No records to display</p>';
    }else{
        $view .= '<div class="table-responsive"><table class="table table-sm">';
        $view .= '<thead><tr>';
        $view .= '<th>Document<th><th>Docdate</th><th class="text-end">Cost Change</th><th class="text-end">Balance</th>';
        $view .= '</tr></thead><tbody>';
        foreach ($cost_history as $d) {
            if (!empty($d->customer_doctype)) {
                $view .= '<tr><td><a href="/'.$documents_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">'.$d->customer_doctype.' #'.$d->document_id.'</a><td><td>'.date('Y-m-d',strtotime($d->docdate)).'</td><td class="text-end">'.$d->cost_change.'</td><td class="text-end">'.currency($d->cost_new).'</td></tr>';
            } elseif (!empty($d->supplier_doctype)) {
                $view .= '<tr><td><a href="/'.$supplier_documents_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">'.$d->supplier_doctype.' #'.$d->supplier_document_id.'</a><td><td>'.date('Y-m-d',strtotime($d->docdate)).'</td><td class="text-end">'.$d->cost_change.'</td><td class="text-end">'.currency($d->cost_new).'</td></tr>';
            } else{
                $view .= '<tr><td><a href="/'.$inventory_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">Inventory adjustment '.$d->id.'</a><td><td>'.date('Y-m-d',strtotime($d->docdate)).'</td><td class="text-end">'.$d->cost_change.'</td><td class="text-end">'.currency($d->cost_new).'</td></tr>';
            }
        }
        $view .= '</tbody></table></div>';
    }
 
 
    $view .= '<h6>Inventory Current Value</h6>';

    $view .= '<div class="table-responsive"><table class="table table-sm">';
    $view .= '<thead><tr>';
    $view .= '<th>Stock Value</th><th>Qty on Hand</th><th>Cost Price Excl</th> <th>Cost Price Incl</th>';
    $view .= '</tr></thead><tbody>';

    $view .= '<tr><td>'.currency($stock_data['stock_value']).'</td><td>'.$stock_data['qty_on_hand'].'</td><td>'.currency($stock_data['cost_price']).'</td><td>'.currency($stock_data['cost_price']*1.15).'</td></tr>';

    $view .= '</tbody></table></div>';
    $view .= '</div>';
    return $view;
}

function sidebar_get_product_invoices($product_id){
    $html = '<ul class="list-group">';
  
    $invoices = \DB::connection('default')->table('crm_documents')
    ->join('crm_accounts','crm_accounts.id','=','crm_documents.account_id')
    ->join('crm_document_lines','crm_document_lines.document_id','=','crm_documents.id')
    ->select('crm_accounts.company','docdate','crm_documents.id','crm_document_lines.zar_sale_total')
    ->where('crm_document_lines.product_id',$product_id)
    ->where('crm_documents.doctype','Tax Invoice')
    ->orderBy('crm_documents.docdate','desc')->get();
    
    $url = get_menu_url_from_table('crm_documents');
    
    foreach($invoices as $inv){
        $html .= ' <li class="list-group-item">
        <a class="hide-link stretched-link" href="'.$url.'?id='.$inv->id.'" target="_blank">Tax Invoice #'.$inv->id.'</a>
        <br> '.$inv->company.' <br> '.$inv->docdate.'</li>';
    }
    
   
    $html .= '</ul>';
    
    return $html;
    
}

function sidebar_get_subscriptions_new($account_id){
    $html = '<ul class="list-group">';
    $subs = \DB::connection('default')->table('sub_services')
    ->join('crm_products','crm_products.id','=','sub_services.product_id')
    ->select('crm_products.name','provision_type','crm_products.code','sub_services.detail','sub_services.id','sub_services.product_id','sub_services.status','sub_services.provision_type','sub_services.to_cancel')
    ->where('sub_services.status','!=','Deleted')

    ->where('account_id',$account_id)
    ->orderBy('sub_services.provision_type')->orderBy('sub_services.detail')->get();
    $url = get_menu_url_from_module_id(334);
    $delete_access = \DB::table('erp_forms')->where('role_id',session('role_id'))->where('module_id',334)->pluck('is_delete')->first();
    if(is_superadmin()){
        $delete_access=1;
    }
    $html .= '<div class="k-widget k-button-group">';
    $html .= '<a class="k-button btn btn-sm sidebar-btn d-none" id="sidebar_sub_btn_view" href="/'.$url.'?id='.$sub->id.'" data-target="view_modal">View Subscription</a>';
    $html .= '<a class="k-button btn btn-sm sidebar-btn d-none" id="sidebar_sub_btn_send" href="/service_setup_email/'.$sub->id.'" data-target="form_modal">Send Service Setup Email</a>';
    
    $html .= '<a class="k-button btn btn-sm sidebar-btn d-none" id="sidebar_sub_btn_migrate" href="/subscription_migrate/'.$sub->id.'" data-target="form_modal">Migrate</a> '; 
    if($delete_access){
        $html .= '<a class="k-button btn btn-sm sidebar-btn d-none" id="sidebar_sub_btn_restore" href="'.$url.'/restore_subscription/'.$sub->id.'" data-target="ajaxconfirm" confirm-text="Are you sure?">Undo Cancel</a> ';
        $html .= '<a class="k-button btn btn-sm sidebar-btn d-none" id="sidebar_sub_btn_cancel" href="'.$url.'/cancel?id='.$sub->id.'" data-target="ajaxconfirm" confirm-text="Are you sure?">Cancel</a> ';
    }
    
    $html .= '</div>';  
    foreach($subs as $sub){
       
        $li_class_list = 'list-group-item sublist_item ';
        if($delete_access){
            if($sub->status == 'Enabled' && in_array($sub->provision_type,['hosting' ,'airtime_contract'  ,'pbx_extension'])){
                $li_class_list .= 'can_migrate ';
            }
            if($sub->to_cancel){
                $li_class_list .= 'can_restore ';
            }else{
                $li_class_list .= 'can_cancel ';
            }
        }
        
        $html .= ' <li class="'.$li_class_list.'" data-sub-id="'.$sub->id.'">
        '.$sub->name.' - '.$sub->code.' - '.$sub->detail.'</br>';
        $html .= '</li>';
    }
    
   
    $html .= '</ul>';
    
    return $html;
    
}

function sidebar_get_files($type, $account_id){
  
    if($type == 'account'){
        $module_id = 343;    
    }
    
    if($type == 'supplier'){
        $module_id = 78;    
    }
    $files = \DB::connection('default')->table('erp_module_files')
    ->select('erp_module_files.*','erp_users.full_name as username')
    ->leftJoin('erp_users','erp_users.id','=','erp_module_files.created_by')
    ->where('module_id',$module_id)
    ->where('row_id',$account_id)
    ->orderBy('created_at','desc')->get();
    
    $filePath = uploads_path($module_id);
    $fileUrl = uploads_url($module_id);
    foreach($files as $i => $f){
        $files[$i]->url = $fileUrl.$f->file_name;
    }
    
    $html = '
    <ul class="list-group">';
    $super_admin = is_superadmin();
   
    foreach($files as $i => $f){
        
        $html .= ' <li class="list-group-item">
        <a href="'.$f->url.'" target="_blank">'.$f->file_name.'</a><br>';
        if(!empty($f->created_at)){
            $html .= '<span class="text-muted">'.Carbon\Carbon::parse($f->created_at)->toDateTimeString().'</span><br>';
        }
        if($f->username){
        $html .= '<span class="text-muted">Uploaded by '.$f->username.'</span><br>';
        }
        if($super_admin){
        $html .= '<button data-file-id="'.$f->id.'" type="button" class="deletefiletbtn btn btn-xs btn-danger m-0"><i class="fa fa-trash"></i></button>';
        }
        $html .= '</li>';
    }
    
   
    $html .= '</ul>';
    return ['html'=>$html,'count'=>count($files)];
}

function sidebar_get_notes($module_id,$row_id){
  
  
    $account_id_module_ids = \DB::table('erp_module_fields')->where('field','account_id')->pluck('module_id')->toArray();
    $account_id_module_ids[] = 343;
    $account_id_modules = \DB::table('erp_cruds')->select('id','name')->whereIn('id',$account_id_module_ids)->get();
    
    $is_account_module = false;
    if(in_array($module_id,$account_id_module_ids)){
        $is_account_module = true;
    }
        
    if($is_account_module){
        $notes = \DB::connection('default')->table('erp_module_notes')
        ->select('erp_module_notes.*','erp_users.full_name as username')
        ->leftJoin('erp_users','erp_users.id','=','erp_module_notes.created_by')
        ->whereIn('module_id',$account_id_module_ids)
        ->where('row_id',$row_id)
        ->where('erp_module_notes.is_deleted',0)
        ->orderBy('created_at','desc')->get();
    }else{
        $notes = \DB::connection('default')->table('erp_module_notes')
        ->select('erp_module_notes.*','erp_users.full_name as username')
        ->leftJoin('erp_users','erp_users.id','=','erp_module_notes.created_by')
        ->where('module_id',$module_id)
        ->where('row_id',$row_id)
        ->where('erp_module_notes.is_deleted',0)
        ->orderBy('created_at','desc')->get();
    }
    
  
    $html = '<ul class="list-group">';
    $super_admin = is_superadmin();
    foreach($notes as $i => $f){
        $module_name = $account_id_modules->where('id',$f->module_id)->pluck('name')->first();
        $html .= ' <li class="list-group-item">
        '.str_replace(PHP_EOL,'<br>',$f->note).'<br>';
        //if($is_account_module){
        $html .= ' <span class="text-muted">Module: '.$module_name.'</span><br>';
        //}
        $html .= ' <span class="text-muted">'.Carbon\Carbon::parse($f->created_at)->toDateTimeString().'</span><br>';
        if($f->username){
        $html .= '<span class="text-muted">Created by '.$f->username.'</span><br>';
        }
        
        if($super_admin){
        $html .= '<button data-note-id="'.$f->id.'" type="button" class="deletenotebtn btn btn-xs btn-danger p-2 px-3 mt-1 mb-0 float-end"><i class="fa fa-trash"></i></button>';
        }
       
        $html .= '</li>';
    }
    
   
    $html .= '</ul>';
    return ['html' => $html,'count' => count($notes)];
    
}


function sidebar_get_linked_modules($module_id,$row_id){
  
    $module = \DB::connection('default')->table('erp_cruds')->select('db_table','connection','db_key')->where('id',$module_id)->get()->first();
    $row = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key,$row_id)->get()->first();
    $fields = get_linked_module_fields($module_id);
    
    $html = '<ul class="list-group">';
    $json = [];
    $super_admin = is_superadmin();
    $inventory_url = get_menu_url_from_table('acc_inventory');
    $count = 0;
    foreach($fields as $i => $f){
        if(!empty($f->opt_module_id) && !empty($row->{$f->field})){
            $module_name = \DB::connection('default')->table('erp_cruds')->select('name')->where('id',$f->opt_module_id)->pluck('name')->first();
            $url = get_menu_url_from_module_id($f->opt_module_id);
            $html .= ' <li class="list-group-item sidebar_linked_module" data-list-url="'.$url.'" data-filtered-list-url="'.$url.'?id='.$row->{$f->field}.'" data-add-url="'.$url.'/edit" data-edit-url="'.$url.'/edit/'.$row->{$f->field}.'"><div class="row w-100 mb-1 ps-2">'.$f->label.' - '.$module_name.'</div>';
            $html .= '</div>';
            $html .= '</li>';
            $json[] = [
                'htmlAttributes' => [
                    'data-list-url' => $url,
                    'data-filtered-list-url' => $url.'?id='.$row->{$f->field},
                    'data-add-url' => $url.'/edit',
                    'data-edit-url' => $url.'/edit/'.$row->{$f->field},
                ],
                'text' => $f->label.' - '.$module_name,
                'value' => $f->label.' - '.$module_name,
                'cssClass' => 'sidebar_linked_module',
                'list_url' => $url.'?id='.$row->{$f->field},
            ];
            $count++;
        }
    }
    
   
  
    $html .= '</ul>';
    
  
    return ['json'=>$json,'html'=>$html,'count'=>$count];
}

function sidebar_get_module_events_json($module_id){
    $module = \DB::connection('default')->table('erp_cruds')->select('db_table','connection','db_key')->where('id',$module_id)->get()->first();
    $json = [];
  
    $super_admin = is_superadmin();
    $events_url = get_menu_url_from_table('erp_form_events');
    $events = \DB::connection('default')->table('erp_form_events')->select('id','function_name','type','last_run')->where('module_id',$module_id)->orderBy('type','DESC')->orderBy('function_name')->get()->groupBy('type');
    foreach($events as $type => $list){
        foreach($list as $l){
            $json[] = [
                'htmlAttributes' => [
                    'data-id' => $l->id,
                ],
                'text' => $l->function_name,
                'value' => $l->function_name,
                'type' => $type,
                'last_run' => $l->last_run,
                'cssClass' => 'event_context',
                'list_url' => $events_url.'?id='.$l->id,
            ];
        }
    }
  
    return $json;
}

function sidebar_get_module_events($module_id){
  
    $module = \DB::connection('default')->table('erp_cruds')->select('db_table','connection','db_key')->where('id',$module_id)->get()->first();
  
    $html = '';
  
    $super_admin = is_superadmin();
    $events_url = get_menu_url_from_table('erp_form_events');
    $events = \DB::connection('default')->table('erp_form_events')->select('id','function_name','type','last_run')
    ->where('module_id',$module_id)->orderBy('type')->orderBy('function_name')->get()->groupBy('type');
    foreach($events as $type => $list){
        $html .= '<h6 class="ps-0  ms-2 mt-2 text-sm font-weight-bolder opacity-6">'.$type.'</h6>';
        $html .= '<ul class="list-group">';
        foreach($list as $l){
            $html .= ' <li class="list-group-item event_context" data-id="'.$l->id.'"><a class="row w-100 mb-1 ps-2" href="/'.$events_url.'?id='.$l->id.'" target="_blank">'.$l->function_name.' <br><span class="text-muted p-0">Last run: '.$l->last_run.'</span></a>';
           
            $html .= '</li>';
        }
        $html .= '</ul>';
    }
    
   
  
    return $html;
}


function sidebar_get_email_templates($module_id,$row_id){
    $html = '';
    $email_manager_url = get_menu_url_from_module_id(556);
    $super_admin = is_superadmin();
    $has_email_id_field = app('erp_config')['module_fields']->where('field', 'email_id')->where('module_id', $module_id)->count();
   
    if($has_email_id_field){
        $module = app('erp_config')['modules']->where('id', $module_id)->first();
        $db_row = \DB::connection($module->connection)->table($module->db_table)->where('id',$row_id)->get()->first();
      
        if(!empty($db_row) && !empty($db_row->email_id)){
            $row_email = \DB::connection('default')->table('crm_email_manager')->where('id',$db_row->email_id)->get()->first();
        }
    }
    
    if(!empty($row_email) && !empty($row_email->id)){
        $html .= '<h5 class="m-2">Row Email</h5>';
        $html .= '<ul class="list-group">';
        $html .= ' <li class="list-group-item">
        '.$row_email->name.'<br>';
        if(!empty($row_email->created_at)){
        $html .= '<span class="text-muted">Created '.Carbon\Carbon::parse($row_email->created_at)->toDateTimeString().'</span><br>';
        }
        /*
        if(!empty($row_email->created_by)){
        $username =  app('erp_config')['users']->where('id', $row_email->created_by)->pluck('username')->first();
        if($username){
        $html .= '<span class="text-muted">Created by '.$username.'</span><br>';
        }
        }
        */
        $html .= '<div class="k-widget k-button-group">';
        $html .= '<a class="k-button sidebar-btn" href="/'.$email_manager_url.'/edit/'.$row_email->id.'" data-target="sidebarform">Edit</a>';
        $html .= '<a class="k-button sidebar-btn" href="/preview_email/'.$row_email->id.'" data-target="view_modal">Preview</a>';
        $html .= '</div>';
        $html .= '</li>';
        $html .= '</ul>';
    }
    
    $module_emails = \DB::connection('default')->table('crm_email_manager')->where('module_id',$module_id)->orderBy('created_at','desc')->get();
    if(count($module_emails) > 0){
        $html .= '<h5 class="m-2">Module Emails</h5>';
        $html .= '<ul class="list-group">';
       
        foreach($module_emails as $row_email){
            $html .= ' <li class="list-group-item">
            '.$row_email->name.'<br>';
            if(!empty($row_email->created_at)){
            $html .= '<span class="text-muted">Created '.Carbon\Carbon::parse($row_email->created_at)->toDateTimeString().'</span><br>';
            }
         
            $html .= '<div class="k-widget k-button-group">';
            $html .= '<a class="k-button sidebar-btn" href="/'.$email_manager_url.'/edit/'.$row_email->id.'" data-target="sidebarform">Edit</a>';
            $html .= '<a class="k-button sidebar-btn" href="/preview_email/'.$row_email->id.'" data-target="view_modal">Preview</a>';
            $html .= '</div>';
            $html .= '</li>';
        }
       
        $html .= '</ul>';
    }
    return $html;
}

function sidebar_get_contacts($type, $account_id){
    
    if($type == 'account'){
        $contacts = get_account_contacts($account_id); 
        $contacts_url = get_menu_url_from_module_id(228);
    }
    if($type == 'supplier'){
        $contacts = get_supplier_contacts($account_id); 
        $contacts_url = get_menu_url_from_module_id(1811);   
    }
    $html = '<ul class="list-group">';
    $super_admin = is_superadmin();
   
    foreach($contacts as $i => $f){
        $html .= ' <li class="list-group-item sidebar_contact_item" data-contact-id="'.$f->id.'">';
        $html .= 'Name: '.$f->full_name.'</br>';
        $html .= 'Email: '.$f->email.'</br>';
        
        $html .= 'Phone: '.$f->phone.'</br>';
        
        $html .= '<span class="text-muted">Type: '.$f->type.'</span></br>';
        $html .= '<a class="hide-link stretched-link" href="'.$contacts_url.'/edit/'.$f->id.'" data-target="sidebarform">Edit</a>';
        $html .= '</li>';
    }
    
   
    $html .= '</ul>';
    if($type == 'supplier'){
        $html .= '<a type="button" class="btn btn-sm" href="'.$contacts_url.'/edit?supplier_id='.$account_id.'" data-target="sidebarform">Add New</a>';
    }else{ 
        $html .= '<a type="button" class="btn btn-sm" href="'.$contacts_url.'/edit?account_id='.$account_id.'" data-target="sidebarform">Add New</a>';
    }
    return $html;
    
}

function get_staff_current_tasks($refresh = 0){
   // if(is_dev())
  //  return [];
    $cards = [];
    $admin_users = \DB::connection('default')->table('erp_users')
    ->select('erp_users.*')
    ->where('account_id',1)->where('is_deleted',0)->where('erp_users.id','!=',1)->get();
   
    foreach($admin_users as $user){
        $data = timer_inprogress($user->id,false,true);
        
        if(empty($data)){
        $data = [];    
        }
        if(!is_main_instance()){
        $main_user_id = \DB::connection('system')->table('erp_users')->where('username',$user->username)->pluck('id')->first(); 
        }else{
        $main_user_id = $user->id;    
        }
        $start_time = \DB::connection('system')->table('hr_timesheet')->where('user_id',$main_user_id)->where('created_date', date('Y-m-d'))->pluck('start_time')->first();
        $data['full_name'] = $user->full_name;
        $data['user_id'] = $user->id;
        $data['main_user_id'] = $main_user_id;
        $data['start_time'] = $start_time;
        
        if(empty($data['task'])){
           
        }
         
        $cards[] = $data;
    }

    return $cards;
}