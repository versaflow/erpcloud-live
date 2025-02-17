<?php

Route::any('subscription_bill_annually/{id?}', function ($id) {
    \DB::table('sub_services')->where('id', $id)->update(['bill_frequency' => 12]);

    return json_alert('Done');
});

Route::any('subscription_migrate_form/{id?}', function ($id) {
    $subscription = \DB::table('sub_services')->where('id', $id)->get()->first();
    if ($subscription->provision_type == 'airtime_contract') {
        $package_amount = \DB::table('crm_products')->where('id', $subscription->product_id)->pluck('provision_package')->first();
        $domain = \DB::connection('pbx')->table('v_domains')->select('lastmonth_total', 'lastmonth_minutes_total')->where('account_id', $subscription->account_id)->get()->first();
        $data = [
            'subscription' => $subscription,
            'subscription_id' => $id,
            'package_amount' => $package_amount,
            'lastmonth_rands_total' => (! empty($domain->lastmonth_total)) ? $domain->lastmonth_total : 0,
            'lastmonth_minutes_total' => (! empty($domain->lastmonth_minutes_total)) ? $domain->lastmonth_minutes_total : 0,
        ];

        return view('__app.button_views.migrate_airtime', $data);
    } else {
        $sub = new ErpSubs;
        $available_products = $sub->getAvailableMigrateProducts($id);
        if (! is_array($available_products)) {
            return json_alert($available_products, 'warning');
        }
        if (is_array($available_products) && count($available_products) == 0) {
            return json_alert('No products available', 'warning');
        }
        $data = [
            'subscription' => $subscription,
            'subscription_id' => $id,
            'available_products' => $available_products,
        ];

        return view('__app.button_views.migrate_subscription', $data);
    }
});

Route::get('telecloud_sidebar_hosting_panels', function () {
    $account_id = false;

    if (! empty(request('telecloud_filter_account'))) {
        $account_id = $request->telecloud_filter_account;
    }
    if (! empty(request('telecloud_filter_domain'))) {
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request('telecloud_filter_domain'))->pluck('account_id')->first();
    }

    $hosting = \Erp::hosting_panels($account_id);

    return $hosting;
});

Route::get('voice_settings_form', function () {
    $pbx_domain = false;

    if (! empty(request('telecloud_filter_account'))) {
        $pbx_domain = \DB::connection('pbx')->table('v_domains')->where('account_id', request('telecloud_filter_account'))->get()->first();
    }
    if (! empty(request('telecloud_filter_domain'))) {
        $pbx_domain = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request('telecloud_filter_domain'))->get()->first();
    }
    if (! $pbx_domain) {
        return json_alert('No domain selected', 'warning');
    }
    $data = ['telecloud_form' => 1];
    $url = get_menu_url(539).'/edit/'.$pbx_domain->id;

    return redirect()->to($url);
});

Route::get('get_module_cards/{module_id?}', function ($module_id) {
    $html = '';
    $module_cards = \Erp::getModuleCards($module_id);
    foreach ($module_cards as $i => $card) {
        $html .= '<div class="col-lg-3 mb-1">
        <div class="card h-100 mb-1 mx-0 border module-card'.$module_id.'" data-attr-id="'.$card->id.'">
        <div class="card-body p-3">
        <div class="row m-0 p-0">
        <div class="col-12 p-0">
        <div class="numbers">
       
        <h6 class="font-weight-bolder mb-0" style="font-size:14px !important;">'.$card->title.'</h6>
        <p class="text-sm mb-0 text-capitalize font-weight-bold" style="font-size:12px !important;">'.$card->result.'</p>
        </div>
        </div>
        </div>
        </div>
        </div>
        </div>';
    }

    return response()->json(['html' => $html]);
});

Route::get('get_module_footer_cards/{module_id?}/{role_id?}', function ($module_id, $role_id = false) {
    $html = '';
    $module_cards = \Erp::getModuleCards($module_id, 1);
    $module_card_lines = collect($module_cards)->sortBy('footer_line')->groupBy('footer_line');
    foreach ($module_card_lines as $line => $module_cards) {
        foreach ($module_cards as $key => $module_footer_card) {
            $html .= '<b>'.$module_footer_card->title.': </b>&nbsp;<span>'.$module_footer_card->result.'</span>';
            if ($key < count($module_cards) - 1) {
                $html .= '&nbsp;&nbsp;';
            }
        }
        $html .= '<br>';
    }
    $incentive_footer_html = '';

    if (! $role_id) {
        $role_id = session('role_id');
    }

    if ($role_id) {
        $incentive_footer_html = get_incentive_footer($role_id);
        aa($incentive_footer_html);
    }
    $task_in_progress = 0;
    if ($module_id == 2018) {
        if ($role_id != session('role_id')) {
            $task_in_progress = \DB::table('crm_staff_tasks')->where('role_id', $role_id)->where('is_deleted', 0)->where('progress_status', 'In Progress')->count();
        } else {
            $task_in_progress = \DB::table('crm_staff_tasks')->where('user_id', session('user_id'))->where('is_deleted', 0)->where('progress_status', 'In Progress')->count();
        }

        if ($task_in_progress > 0) {
            $task_in_progress = 1;
        }
    }
    $data = ['html' => $html, 'incentive_footer_html' => $incentive_footer_html, 'task_in_progress' => $task_in_progress];

    return response()->json($data);
});

Route::get('dashboard_widget_remove/{id?}', function ($id) {
    if (is_superadmin()) {
        \DB::connection('default')->table('erp_grid_views')
            ->where('id', $id)
            ->update(['show_on_dashboard' => 0]);

        return json_alert('Widget removed', 'success', ['callback_function' => 'remove_chart_accordion']);
    }
});

Route::any('content_sidebar_related_layouts/{module_id?}', function ($module_id) {
    $layouts = \DB::connection('default')->table('erp_grid_views')->where('sidebar_module_id', $module_id)->orderBy('module_id')->orderBy('name')->get();
    if (count($layouts) == 0) {
        return '';
    }

    $html = ' <h6 class="ps-0  ms-2 mt-2 text-sm font-weight-bolder opacity-6">Related Layouts</h6>
     <div class="e-listbox-wrapper e-wrapper e-lib">
     <ul class="list-group e-list-parent e-ul e-lib">';
    foreach ($layouts as $layout) {
        $module = app('erp_config')['modules']->where('id', $layout->module_id)->first();
        $html .= '<li class="e-list-item list-group-item d-flex justify-content-between align-items-center">';
        $html .= '<a href="'.$module->slug.'?layout_id='.$layout->id.'" target="_blank"  class="stretched-link">'.$module->name.' '.$layout->name.'</a>';
        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
});

Route::any('workboard_reports/{project_id?}', function ($project_id = false) {
    if (! $project_id) {
        return response()->json([]);
    }
    $json = Erp::getWorkboardReports($project_id);

    return response()->json($json);
});

Route::any('content_sidebar_forms/{module_id?}', function ($module_id) {
    $json = Erp::getGridForms($module_id);

    return response()->json($json);
});

Route::any('content_sidebar_grids/{module_id?}/{type?}/{view_id?}', function ($module_id, $type, $view_id = false) {
    $json = Erp::getContentSidebar($module_id, $type, $view_id);

    return response()->json($json);
});

Route::any('get_sidebar_charts', function () {
    $data = [];
    $data['charts'] = [];

    $response_items = [];

    $layouts = \DB::connection('default')->table('erp_grid_views')->where('is_deleted', 0)->where('show_on_dashboard', 1)->where('project_id', '>', '')->orderBy('module_id')->orderBy('name')->get();
    foreach ($layouts as $i => $chart) {
        $chart->chart_data = get_chart_data($chart->id);
        if ($panel->widget_type == 'Pyramid') {
            $chart->chart_data = array_values($chart->chart_data);
        }
        $data['charts'][] = $chart;
    }

    return view('__app.layouts.partials.content_sidebar_charts', $data)->render();
});

Route::any('kbview/{id?}', function ($id) {
    $faq = \DB::table('hd_customer_faqs')->where('id', $id)->pluck('content')->first();
    echo '<div class="card"><div class="card-body">'.$faq.'</div></div>';
});

Route::any('get_sidebar_knowledge_base_list_view/{internal?}/{account_id?}', function ($internal = 0, $account_id = 0) {
    $response_items = [];
    $types = [];

    if ($internal && ! is_superadmin()) {
        return response()->json(['items' => []]);
    }
    $internal = ($internal) ? 1 : 0;

    if (is_superadmin()) {
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->where('internal', $internal)->where('is_deleted', 0)->orderBy('sort_order')->get();
    } elseif (session('role_level') == 'Admin') {
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('level', ['Admin', 'Customer', 'Reseller'])->where('internal', 0)->where('is_deleted', 0)->orderBy('sort_order')->get();
    } elseif (session('role_level') == 'Partner') {
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('level', ['Customer', 'Reseller'])->where('internal', 0)->where('is_deleted', 0)->orderBy('sort_order')->get();
    } elseif (session('role_level') == 'Customer') {
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('level', ['Customer'])->where('internal', 0)->where('is_deleted', 0)->orderBy('sort_order')->get();
    }
    foreach ($faqs as $faq) {
        $response_items[] = [
            'id' => 'faq'.$faq->id,
            'type' => $faq->type,
            'text' => $faq->name,
            'faq_id' => $faq->id,
            'cssClass' => 'kbitem_context',
        ];
        if (! in_array($faq->type, $types)) {
            $types[] = $faq->type;
        }
    }

    $type_response_items = [];
    foreach ($types as $i => $type) {
        $type_response_items[] = [
            'id' => 'faqtype'.$i,
            'type' => $type,
            'text' => $type,
            'faq_id' => 0,
            'cssClass' => 'kbtypeitem',
            'items' => array_values(collect($response_items)->where('type', $type)->toArray()),
        ];
    }

    return response()->json(['items' => $type_response_items]);
});

Route::any('get_sidebar_knowledge_base', function () {
    $response_items = [];
    $types = [];
    $faqs = \DB::table('hd_customer_faqs')->where('is_deleted', 0)->where('internal', 0)->orderBy('type')->orderBy('name')->get();
    foreach ($faqs as $faq) {
        $response_items[] = [
            'type' => $faq->type,
            'header' => strtolower($faq->name),
            'content' => $faq->content,
            'faq_id' => $faq->id,
            'cssClass' => 'kbitem',
        ];
        if (! in_array($faq->type, $types)) {
            $types[] = $faq->type;
        }
    }
    if (is_superadmin()) {
        $faqs = \DB::table('hd_customer_faqs')->where('is_deleted', 0)->where('internal', 1)->orderBy('type')->orderBy('name')->get();

        foreach ($faqs as $faq) {
            $response_items[] = [
                'type' => $faq->type.' Internal',
                'header' => strtolower($faq->name),
                'content' => $faq->content,
                'faq_id' => $faq->id,
                'cssClass' => 'kbitem',
            ];
            if (! in_array($faq->type.' Internal', $types)) {
                $types[] = $faq->type.' Internal';
            }
        }
    }
    $type_response_items = [];
    foreach ($types as $i => $type) {
        $type_response_items[] = [
            'type' => $type,
            'content_div' => 'kbtype_'.$i,
            'header' => $type,
            'content' => '<div id="kbtype_'.$i.'"></div>',
            'cssClass' => 'kbaccord',
        ];
    }

    return response()->json(['types' => $type_response_items, 'items' => $response_items]);
});

Route::any('get_sidebar_module_guides/{module_id?}/{role_id?}', function ($module_id, $role_id = false) {
    $response_items = [];
    $global_response_items = [];
    $project_id = false;

    // if($module_id == 2018){
    // $guides = \DB::table('crm_training_guides')->where('is_deleted',0)->where('role_id',0)->orderBy('sort_order')->get();
    // foreach($guides as $guide){
    //     $content = $guide->guide;
    //     $cssClass = ($guide->needs_attention) ? 'flagged' : '';
    //     $global_response_items[] = (object) ['project_id' => $project_id, 'id'=>$guide->id,'header'=>$guide->name,'content'=>$content,'cssClass'=>$cssClass];

    // }
    // }

    if (! $role_id) {
        $role_id = session('role_id');
    }

    // $guides = \DB::table('crm_training_guides')->where('is_deleted',0)->orderBy('sort_order')->get();
    $guides = \DB::table('crm_training_guides')->whereIn('role_id', [$role_id, 0])->where('is_deleted', 0)->orderBy('sort_order')->get();
    // $guides = array_merge($guides, $guides2);

    foreach ($guides as $guide) {
        $content = $guide->guide;
        $cssClass = ($guide->needs_attention) ? 'flagged' : '';
        $response_items[] = (object) ['role_id' => $role_id, 'module_id' => $module_id, 'id' => $guide->id, 'header' => $guide->name, 'content' => $content, 'cssClass' => $cssClass];
    }

    if (is_superadmin() && count($response_items) == 0) {
        $guides_url = get_menu_url_from_module_id(1875);
        $response_items[] = (object) [
            'module_id' => $module_id,
            'role_id' => $role_id,
            'id' => $guide->id,
            'header' => 'Add New',
            'content' => '<a href="'.$guides_url.'/edit?role_id='.$role_id.'" data-target="form_modal" class="btn btn-default" >Create Guide</a>',
        ];
    }
    //aa($response_items);

    return response()->json(['global_accordion' => $global_response_items, 'accordion' => $response_items]);
});

Route::any('get_sidebar_row_info/{module_id?}/{row_id?}', function ($module_id, $row_id) {
    $users = app('erp_config')['users'];
    $module = app('erp_config')['modules']->where('id', $module_id)->first();
    $module_fields = app('erp_config')['module_fields']->where('module_id', $module_id)->pluck('field')->toArray();
    $interactions_response_items = [];
    $newsletter_response_items = [];
    $rowhistory_response_items = [];
    $products_response_items = [];

    $response_items = [];
    $row = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $row_id)->get()->first();

    if ($module->db_table == 'crm_accounts') {
        $type = 'account';
        $account_id = $row_id;
    } elseif ($module->db_table == 'crm_suppliers') {
        $type = 'supplier';
        $account_id = $row_id;
    } elseif (in_array('account_id', $module_fields)) {
        if ($row->account_id) {
            $type = 'account';
            $account_id = $row->account_id;
        }
    } elseif (in_array('domain_uuid', $module_fields)) {
        if ($row->domain_uuid) {
            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $row->domain_uuid)->pluck('account_id')->first();
            if ($account_id) {
                $type = 'account';
            }
        }
    } elseif (in_array('supplier_id', $module_fields)) {
        if ($row->supplier_id) {
            $type = 'supplier';
            $account_id = $row->supplier_id;
        }
    }
    $product_id = 0;
    if ($module->db_table == 'crm_products') {
        $product_id = $row_id;
    } elseif (in_array('product_id', $module_fields)) {
        if ($row->product_id) {
            $product_id = $row->product_id;
        }
    }

    $time_stamp_info = '';

    if (in_array('requested_by', $module_fields)) {
        $name = $users->where('id', $row->requested_by)->pluck('full_name')->first();
        $time_stamp_info .= 'Requested By: '.$name.'<br>';
    } elseif (in_array('created_by', $module_fields)) {
        $name = $users->where('id', $row->created_by)->pluck('full_name')->first();
        $time_stamp_info .= 'Created By: '.$name.'<br>';
    }

    if (in_array('created_at', $module_fields)) {
        $time_stamp_info .= 'Created At: '.$row->created_at.'<br>';
    }

    if (in_array('updated_by', $module_fields)) {
        $name = $users->where('id', $row->updated_by)->pluck('full_name')->first();
        $time_stamp_info .= 'Updated By: '.$name.'<br>';
    }

    if (in_array('updated_at', $module_fields)) {
        $time_stamp_info .= 'Updated At: '.$row->updated_at.'<br>';
    }

    $deleted_at_added = false;
    $deleted_by_added = false;
    if (in_array('deleted_by', $module_fields) && ! empty($row->deleted_by)) {
        $name = $users->where('id', $row->deleted_by)->pluck('full_name')->first();
        $time_stamp_info .= 'Deleted By: '.$name.'<br>';
        $deleted_by_added = true;
    }

    if (in_array('deleted_at', $module_fields) && ! empty($row->deleted_at)) {
        $time_stamp_info .= 'Deleted At: '.$row->deleted_at.'<br>';
        $deleted_at_added = true;
    }

    if ($row && $row->id) {
        $delete_log = \DB::connection('default')->table('erp_module_log')->where('row_id', $row->id)->where('module_id', $module_id)->where('action', 'deleted')->get()->first();
        if ($delete_log) {
            $name = $users->where('id', $delete_log->created_by)->pluck('full_name')->first();
            if (! $deleted_by_added) {
                $time_stamp_info .= 'Deleted By: '.$name.'<br>';
            }
            if (! $deleted_at_added) {
                $time_stamp_info .= 'Deleted At: '.$delete_log->created_at.'<br>';
            }
        }
    }

    $items = [];
    $account_info = '';
    $supplier_info = '';
    $product_info = '';
    $sales_html = '';
    $statement_html = '';
    $subscriptions_html = '';
    $services_balances = '';
    $sidebar_title = '';
    $sidebar_accountid = 0;

    $kb_listview = [];
    $sales_call_info = false;

    // Customer Tab
    if ($account_id && $type == 'account') {
        $accounts_url = get_menu_url_from_table('crm_accounts');

        $account = dbgetaccount($account_id);
        $sidebar_title = $account->company;
        // if($module_id == 1923){

        //     try{
        //         $number = phone($account->phone, ['ZA','US','Auto']);
        //         $number = $number->formatForMobileDialingInCountry('ZA');
        //     }catch(\Throwable $ex){
        //         $number = '';
        //     }
        //     if($number){
        //     \DB::table('crm_accounts')->where('id',$account_id)->update(['phone'=>$number]);
        //     }
        //     $account = dbgetaccount($account_id);
        //     $account->balance = currency_formatted($account->balance, $account->currency);
        //     $opp = \DB::table('crm_opportunities')->where('id',$row_id)->get()->first();

        //     $statuses = \DB::table('erp_module_fields')->where('module_id',1923)->where('field','status')->pluck('opts_values')->first();

        //     // get last call cdr record
        //     $recording_file_link = '';

        //     $recording_file = \DB::table('erp_call_history')->where('account_id', $account_id)->where('recording_file', '>', '')->orderBy('id','desc')->pluck('recording_file')->first();
        //     if ($recording_file) {
        //         $recording_file_link = url('http://156.0.96.60/recordings/'.str_replace('/var/lib/freeswitch/', '', $recording_file));
        //     }

        //     $status_options = explode(',',$statuses);

        //     $data = [
        //       'account'=> $account,
        //       'id' => $row_id,
        //       'opp_status' => $opp->status,
        //       'recording_file_link' => $recording_file_link,
        //       'status_options' => $status_options
        //     ];

        //     $sales_html =  view('__app.grids.sidebar.call_center',$data)->render();

        // }

        $account = dbgetaccount($account_id);

        $reseller = \DB::connection('default')->table('crm_accounts')->where('id', $account->partner_id)->pluck('company')->first();

        $account_info = '<div id="sidebar_account_info" class="sidebar_account_info p-2" data-id="'.$account->id.'" data-partner-id="'.$account->partner_id.'">';
        //$account_info .= '<div class="row p-0"><div class="col"><h6><a href="'.$accounts_url.'?id='.$account->id.'" target="_blank">'.$account->company.'</a></h6></div> </div>';

        $account_info .= '<b>'.$account->company.'</b><br>';
        $account_info .= '<b>'.$reseller.' (Reseller)</b><br>';

        $account_details = $account;
        if ($account->partner_id != 1) {
            $account_details = dbgetaccount($account->partner_id);
        }
        $sidebar_accountid = $account_details->id;
        $industry = \DB::connection('default')->table('crm_industries')->where('id', $account_details->industry_id)->pluck('name')->first();

        $account_info .= 'Contact: '.$account_details->contact.'<br>';

        if ($account->email_verified) {
            $account_info .= 'Email: '.'<a href="/email_form/default/'.$account_details->id.'/'.$account_details->email.'" target="_blank" data-target="form_modal">'.$account_details->email.'</a> Verified<br>';
        } else {
            $account_info .= 'Email: '.'<a href="/email_form/default/'.$account_details->id.'/'.$account_details->email.'" target="_blank" data-target="form_modal">'.$account_details->email.'</a> Unverified<br>';
        }
        //if(session('instance')->directory != 'moviemagic'){
        $account_info .= 'Phone: '.'<a href="/pbx_call/'.$account_details->phone.'/'.$account_details->id.'" data-target="ajax">'.$account_details->phone.'</a><br>';
        //}

        $account_info .= 'Industry: '.$industry.'<br>';
        $account_info .= 'Address: '.$account_details->address.'<br>';
        // $account_info .= '<br>Balance: '.currency_formatted($account->balance,$account->currency).'<br>';
        if ($account->account_status == 'Cancelled') {
            $account_info .= 'Status: '.$account_details->status.' & '.$account_details->account_status.'<br>';
        } else {
            $account_info .= 'Status: '.$account_details->status.'<br>';
        }
        $account_info .= '<br>';
        $account_info .= 'Payment Terms: '.$account_details->payment_terms.'<br>';
        $account_info .= 'Payment Method: '.$account_details->payment_method.'<br>';

        // get last call cdr record
        $recording_file_link = '';

        $last_call = \DB::table('erp_call_history')->where('account_id', $account_id)->where('recording_file', '>', '')->orderBy('id', 'desc')->get()->first();
        if ($last_call) {
            $recording_file_link = url('http://156.0.96.60/recordings/'.str_replace('/var/lib/freeswitch/', '', $last_call->recording_file));
            $account_info .= '<br><a href="'.$recording_file_link.'" target="_blank"><b>Call Recording - '.date('Y-m-d H:i', strtotime($last_call->created_at)).'</b></a><br>';
        }

        if (! empty($account->pabx_domain)) {
            $domain_info = \DB::connection('pbx')->table('v_domains')->where('account_id', $account->id)->get()->first();

            $account_info .= '<b>'.$domain_info->domain_name.'</b><br>';
            $account_info .= 'Airtime Balance: '.currency_formatted($domain_info->balance, $domain_info->currency);
            $account_info .= '<br>';

            $unlimited_channels = $domain_info->unlimited_channels;
            if ($unlimited_channels > 0) {
                $account_info .= '<br>Unlimited Channels: '.$unlimited_channels;
                $account_info .= '<br>Unlimited Channels Usage: '.$domain_info->unlimited_channels_usage;
                $account_info .= '<br>Unlimited Channels Average: '.currency($domain_info->unlimited_channels_usage / $unlimited_channels);
            }
        }

        $account_info .= '<br>';
        $account_info .= sidebar_get_account_cards($account_details->id);
        $account_info .= '</div>';

        $kb_listview = sidebar_get_kb_listview($module_id, $row_id, $account_id);
    } else {
        $kb_listview = sidebar_get_kb_listview($module_id, $row_id);
    }

    if (! empty($sales_html)) {
        $account_info .= $sales_html;
    }

    if ($account_id && $type == 'supplier') {
        $suppliers_url = get_menu_url_from_table('crm_suppliers');
        $account = dbgetsupplier($account_id);
        $sidebar_title = $account->company;
        $supplier_info = '<h6><a href="'.$suppliers_url.'?id='.$account->id.'" target="_blank">'.$account->company.'</a></h6>';
        $supplier_info .= 'Contact: '.$account->contact.'<br>';
        $supplier_info .= 'Email: '.$account->email.'<br>';
        $supplier_info .= 'Phone: '.$account->phone.'<br>';
        $supplier_info .= 'Balance: '.currency_formatted($account->balance, $account->currency);
        $supplier_info .= '<br>Status: '.$account->status.'<br>';
        $response_items[] = (object) ['header' => 'Supplier Info', 'content' => $supplier_info];
        $response_items[] = (object) ['header' => 'Customer Contacts', 'content' => sidebar_get_contacts($type, $account_id)];
    }

    if ($product_id) {
        $products_url = get_menu_url_from_table('crm_products');

        $product = \DB::connection('default')->table('crm_products')->where('id', $product_id)->get()->first();

        $total_sales = \DB::connection('default')->table('crm_document_lines')->where('product_id', $product_id)->sum('zar_sale_total');
        $monthly_sales = \DB::connection('default')->table('crm_document_lines')
            ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
            ->where('docdate', 'like', date('Y-m').'%')
            ->where('product_id', $product_id)->sum('zar_sale_total');

        $last_sale = \DB::connection('default')->table('crm_document_lines')
            ->select('crm_documents.docdate')
            ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
            ->where('crm_document_lines.product_id', $product_id)
            ->orderBy('crm_documents.docdate', 'desc')
            ->pluck('docdate')->first();

        if (empty($total_sales)) {
            $total_sales = 0;
        }
        if (empty($last_sale)) {
            $last_sale = 'None';
        }

        $product_info = '<h6><a href="'.$products_url.'?id='.$product->id.'" target="_blank">'.$product->name.'</a></h6>';
        $product_info .= 'Monthly Sales: R '.currency($monthly_sales).'<br>';
        $product_info .= 'Total Sales: R '.currency($total_sales).'<br>';
        $product_info .= 'Last Sale: '.$last_sale.'<br>';
    }

    $sidebar_notes = sidebar_get_notes($module_id, $row_id);
    if ($module_id != 1923) {
        $notes_html = '<div id="notes_form">
    <textarea id="sidebar_note" name="sidebar_note" class="form-control" placeholder="Enter note here"></textarea>
    <button type="button" class="btn btn-sm w-100 mt-1" id="addnotebtn">Add Note</button>
    </div>';
    }

    if ($row_id) {
        $notes_html .= $sidebar_notes['html'];
    }
    $sidebar_files = sidebar_get_files($type, $account_id);

    if (! $account_info) {
        $interactions_response_items[] = (object) ['header' => 'Notes ('.$sidebar_notes['count'].')', 'content' => $notes_html];
    }
    if ($sales_call_info) {
        $interactions_response_items[] = $sales_call_info;
    }

    if ($account_info && $type == 'account') {
        $interactions_response_items[] = (object) ['header' => 'Notes ('.$sidebar_notes['count'].')', 'content' => $notes_html];
        //$response_items[] = (object) ['header'=>'Customer Info','content'=>$account_info];
        $response_items[] = (object) ['header' => 'Customer', 'content' => '', 'cssClass' => 'sidebar-acc-header', 'disabled' => true];
        $response_items[] = (object) ['header' => 'Customer Info', 'content' => $account_info];
        $response_items[] = (object) ['header' => 'Customer Statement', 'content' => sidebar_get_statement($type, $account_id)];
        $response_items[] = (object) ['header' => 'Customer Contacts', 'content' => sidebar_get_contacts($type, $account_id)];
        //$subscriptions_accordion = sidebar_get_subscriptions($module_id,$row_id,$account_id);

        $subscriptions_listview = sidebar_get_subscriptions_listview($module_id, $row_id, $account_id);
    } else {
        $subscriptions_listview = sidebar_get_subscriptions_listview($module_id, $row_id);
    }

    if (session('role_level') == 'Admin') {
        $response_items[] = (object) ['header' => 'Marketing Material', 'content' => '', 'cssClass' => 'sidebar-acc-header', 'disabled' => true];
        $interactions_account_id = ($account_id) ? $account_id : 1;
        $newsletter_lists = sidebar_get_newsletters_emails_list($module_id, $row_id, $interactions_account_id);
        foreach ($newsletter_lists as $l) {
            $response_items[] = $l;
        }
        $response_items[] = (object) ['header' => 'Pricing Emails', 'content' => sidebar_get_pricing_emails($module_id, $row_id, $interactions_account_id)];

        $response_items[] = (object) ['header' => 'Debtor Emails', 'content' => sidebar_get_debtor_emails($module_id, $row_id, $interactions_account_id)];
        $response_items[] = (object) ['header' => 'Notifications', 'content' => sidebar_get_email_form_emails($module_id, $row_id, $interactions_account_id)];

        if (in_array(7, session('app_ids'))) {
            $response_items[] = (object) ['header' => 'Knowledge Base', 'content' => sidebar_get_faqs($module_id, $row_id, $interactions_account_id)];
        }
    }

    $response_items[] = (object) ['header' => 'Communication History', 'content' => '', 'cssClass' => 'sidebar-acc-header', 'disabled' => true];
    $response_items[] = (object) ['header' => 'Customer Emails', 'content' => sidebar_get_interactions_emails($account_id)];
    $response_items[] = (object) ['header' => 'Customer Calls', 'content' => sidebar_get_interactions_calls($account_id)];
    $response_items[] = (object) ['header' => 'Customer SMS', 'content' => sidebar_get_interactions_smses($account_id)];

    //Supplier Tab
    if ($supplier_info && $type == 'supplier') {
        //$response_items[] = (object) ['header'=>'Supplier Info','content'=>$supplier_info];
        $rowhistory_response_items[] = (object) ['header' => 'Supplier Contacts', 'content' => sidebar_get_contacts($type, $account_id)];
        $rowhistory_response_items[] = (object) ['header' => 'Supplier Statement', 'content' => sidebar_get_statement($type, $account_id)];
    }

    if ($product_info && $product_id) {
        $rowhistory_response_items[] = (object) ['header' => 'Product Info', 'content' => $product_info];

        $products_response_items[] = (object) ['header' => 'Product Stock History', 'content' => sidebar_get_product_stock_history($product_id), 'cssClass' => 'sidebar-acc-header'];
        //$products_response_items[] = (object) ['header'=>'Product Invoices','content'=>sidebar_get_product_invoices($product_id),'cssClass'=>'sidebar-acc-header'];
        //$products_response_items[] = (object) ['header'=>'Product Subscriptions','content'=> sidebar_get_product_subscriptions($product_id),'cssClass'=>'sidebar-acc-header'];
    }

    if ($time_stamp_info > '') {
        $rowhistory_response_items[] = (object) ['header' => 'Row History', 'content' => $time_stamp_info];
    }
    $rowhistory_response_items[] = (object) ['header' => 'Files ('.$sidebar_files['count'].')', 'content' => $sidebar_files['html']];

    $docs_url = get_menu_url_from_module_id(353);
    if (in_array('document_id', $module_fields) && in_array('instance_id', $module_fields)) {
        $row = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $row_id)->get()->first();
        $domain_name = \DB::connection('system')->table('erp_instances')->where('id', $row->instance_id)->pluck('domain_name')->first();
        $url = 'https://'.$domain_name.'/'.$docs_url.'?id='.$row->document_id;
        $link = '<a href="'.$url.'" target="_blank" class="btn btn-sm">View Document</a>';
        $response_items[] = (object) ['header' => 'Document', 'content' => $link];
    } elseif (in_array('document_id', $module_fields)) {
        $row = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $row_id)->get()->first();
        $url = $docs_url.'?id='.$row->document_id;
        $link = '<a href="'.$url.'" target="_blank" class="btn btn-sm">View Document</a>';
        $response_items[] = (object) ['header' => 'Document', 'content' => $link];
    }

    $linked_records = sidebar_get_linked_modules($module_id, $row_id);

    //$response_items[] = (object) ['header'=>'Related Modules','content'=>$linked_records];
    if ($services_balances > '') {
        $services_balances = '<div class="p-3">'.$services_balances.'</div>';
    }
    $json = [
        'rowinfo_accordion' => $response_items,
        'row_history_html' => $time_stamp_info,
        'row_files_html' => $sidebar_files['html'],
        'newsletters_accordion' => $newsletter_response_items,
        'interactions_accordion' => $interactions_response_items,
        'rowhistory_accordion' => $rowhistory_response_items,
        'products_accordion' => $products_response_items,
        'services_balances' => $services_balances,
        'sidebar_title' => $sidebar_title,
        'sidebar_accountid' => $sidebar_accountid,
        'telecloud_listview' => '',
    ];

    if ($account_id && $type == 'account') {
        if (empty(session('telecloud_listview_account_id')) || (session('telecloud_listview_account_id') != $account_id)) {
            $json['telecloud_listview'] = sidebar_get_services_menu($module_id, $account_id);

            session(['telecloud_listview_account_id' => $account_id]);
        }
    }

    if (! empty($subscriptions_accordion)) {
        $json['subscriptions_accordion'] = $subscriptions_accordion;
    }
    $json['subscriptions_listview'] = [];
    if (! empty($subscriptions_listview)) {
        $json['subscriptions_listview'] = $subscriptions_listview;
    }
    $json['kb_listview'] = [];
    if (! empty($kb_listview)) {
        $json['kb_listview'] = $kb_listview;
    }

    if ($account_info) {
        $json['rowinfo_html'] = $account_info;
    }
    if ($supplier_info) {
        $json['rowinfo_html'] = $supplier_info;
    }
    if ($sales_html) {
        $json['sales_html'] = $sales_html;
    }
    //aa($linked_records);
    if ($linked_records) {
        $json['linked_records_html'] = $linked_records['html'];
        $json['linked_records_json'] = $linked_records['json'];
        $json['linked_records_count'] = $linked_records['count'];
    }

    return response()->json($json);
});

Route::post('favorite_add', function () {
    try {
        $link_url = request('link_url');
        $layout_id = request('layout_id');
        $data = [];
        $data['user_id'] = session('user_id');
        if (! empty($link_url)) {
            $data['title'] = $link_url;
            $data['link_url'] = 'http://'.str_replace(['http://', 'https://'], '', trim($link_url));
        }
        if (! empty($layout_id)) {
            $layout_data = \DB::table('erp_grid_views')
                ->select('erp_cruds.slug', 'erp_cruds.name as m_name', 'erp_grid_views.name as l_name')
                ->join('erp_cruds', 'erp_cruds.id', '=', 'erp_grid_views.module_id')
                ->where('erp_grid_views.id', $layout_id)
                ->get()->first();
            $data['title'] = $layout_data->m_name.' '.$layout_data->l_name;
            $data['link_url'] = url($layout_data->slug).'?layout_id='.$layout_id;
            $data['layout_id'] = $layout_id;
        }

        \DB::table('erp_favorites')->insert($data);
    } catch (\Throwable $ex) {
        exception_log($ex->getMessage());
    }
});

Route::post('favorite_delete', function () {
    $link_id = request('link_id');
    \DB::table('erp_favorites')->where('id', $link_id)->delete();
});

Route::get('get_favorites_list', function () {
    $favorites = \DB::table('erp_favorites')->where('user_id', session('user_id'))->get();
    $html = '<ul class="list-group">';
    foreach ($favorites as $f) {
        $html .= '<li class="list-group-item d-flex justify-content-between align-items-center">';
        $html .= '<a href="'.$f->link_url.'" target="_blank"  >'.$f->title.'</a>';

        $html .= '<button data-link-id="'.$f->id.'" type="button" class="favorites_delete btn btn-xs btn-danger p-2 px-3 mt-1 mb-0 float-end"><i class="fa fa-trash"></i></button>';

        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
});

Route::post('guides_sort', function () {
    $guides = request()->guides;
    //aa($guides);
    foreach ($guides as $i => $g) {
        \DB::table('crm_training_guides')->where('id', $g['id'])->update(['sort_order' => $i]);
    }
    $rows = \DB::table('crm_training_guides')->orderBy('role_id', 'asc')->orderBy('sort_order', 'asc')->get();

    foreach ($rows as $i => $r) {
        \DB::table('crm_training_guides')->where('id', $r->id)->update(['sort_order' => $i]);
    }
});

Route::post('layouts_sort', function () {
    $ids = request()->ids;

    foreach ($ids as $i => $id) {
        \DB::connection('default')->table('erp_grid_views')->where('id', $id)->update(['sort_order' => $i]);
    }
});
