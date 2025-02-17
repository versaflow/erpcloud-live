<?php

Route::get('service_account_filter/{account_id?}', function ($account_id = 0) {
    \Erp::set_service_account_session($account_id);
});

Route::any('email_verification/{link_data?}', function ($link_data) {
    $link_data = \Erp::decode($link_data);
    if (empty($link_data['account_id'])) {
        $response = [
            'status' => 'warning',
            'message' => 'Verification link invalid.',
        ];

        return redirect()->to('/')->with($response);
    }

    \DB::table('crm_accounts')->where('id', $link_data['account_id'])->update(['email_verified' => 1]);
    $verified = \DB::table('crm_accounts')->where('id', $link_data['account_id'])->pluck('email_verified')->first();
    if (! $verified) {
        $response = [
            'status' => 'warning',
            'message' => 'Verification link invalid.',
        ];

        return redirect()->to('/')->with($response);
    }

    $response = [
        'status' => 'success',
        'message' => 'Your email is now verified.',
    ];

    return redirect()->to('/')->with($response);

});

Route::any('process_approval/{external_id?}/{user_id?}', function ($external_id, $user_id) {

    $user = \DB::connection('system')->table('erp_users')->where('id', $user_id)->get()->first();

    if ($user->is_deleted) {
        \Auth::logout();

        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.');
    }

    if (! empty($user) && $user->active) {

        $role = \DB::connection('system')->table('erp_user_roles')->where('id', $user->role_id)->get()->first();

        if ($role->level == 'Admin') {
            $logged_in = \DB::connection('system')->table('erp_user_sessions')->where('user_id', $user->id)->count();

            if ($logged_in) {
                $row = \DB::connection('default')->table('erp_users')->where('username', $user->username)->get()->first();

                if ($row) {
                    // check admin instance access
                    $user_id = \DB::connection('system')->table('erp_users')->where('username', $row->username)->pluck('id')->first();

                    $role = \DB::connection('system')->table('erp_user_roles')->where('id', $row->role_id)->get()->first();
                    $instance_access = get_admin_instance_access($user->username);
                    if (! empty(session('instance')->id)) {
                        $instance_id = session('instance')->id;
                    } else {
                        $instance_id = \DB::connection('system')->table('erp_instances')->where('domain_name', str_replace('https://', '', request()->root()))->pluck('id')->first();
                    }
                    if (! in_array($instance_id, $instance_access)) {

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'No Access.')->withInput();
                    }

                    set_session_data($row->id);
                    session(['original_role_id' => $role->id]);
                    session(['app_ids' => get_installed_app_ids()]);
                }
            }
        }
    }

    $approval = \DB::table('crm_approvals')->where('id', $external_id)->get()->first();

    $response = process_approval($approval);

    return $response;
});

Route::get('payroll_approve/{payroll_end_date?}', function ($payroll_end_date) {
    if (is_superadmin()) {
        payroll_approve($payroll_end_date);
        $url = get_menu_url_from_module_id(807);

        return redirect()->to($url)->with('message', 'Payroll Approved. Payslips emailed.')->with('status', 'success');
    }
});

Route::get('email_template_test', function () {
    erp_email_test();
});

Route::post('github_issue', function () {
    $title = trim(request('title'));
    $description = trim(request('description'));

    if (empty($description)) {
        return json_alert('Fill all fields', 'warning');
    }

    $user_id = get_user_id_default();
    $user = \DB::table('erp_users')->where('id', $user_id)->get()->first();
    if ($user->account_id == 1) {
        $submitted_by = $user->full_name;
    } else {
        $account = dbgetaccount($user->account_id);
        $submitted_by = $account->company;
    }

    $description = $description.PHP_EOL.'Submitted by:'.$submitted_by;
    create_github_issue($title, $description);

    return json_alert('Submitted for review.');
});

Route::get('github_issue', function () {
    $data = [];
    $data['title'] = 'Github Issue';
    $data['menu_name'] = 'Github Issue';

    return view('__app.components.pages.github_report', $data);
});

Route::post('form_tabs_sort/{module_id?}', function ($module_id) {
    $tabs = request()->tabs;
    $sort_order = 0;

    foreach ($tabs as $tab) {
        if ($tab == 'Other') {
            $tab = '';
        }
        $fields = \DB::connection('default')->table('erp_module_fields')->select('id')->where('module_id', $module_id)->where('tab', $tab)->orderBy('sort_order')->get();

        foreach ($fields as $f) {
            \DB::connection('default')->table('erp_module_fields')->where('id', $f->id)->update(['sort_order' => $sort_order]);
            $sort_order++;
        }
    }

    return json_alert('Done');
});

Route::post('update_fields_sort/{module_id?}', function ($module_id) {
    $fields = request()->fields;

    foreach ($fields as $i => $f) {
        \DB::connection('default')->table('erp_module_fields')->where('id', $f)->update(['sort_order' => $i]);
    }

    update_fields_sort($module_id);

    return json_alert('Done');
});

Route::get('iptv_api', function () {
    iptv_get_packages();
});

Route::post('ipranges_test_ranges_update', function () {
    $account_id = request('account_id');
    $gateway = request('gateway');

    if (empty($account_id)) {
        $data = ['test_expiry' => null, 'account_id' => 0, 'gateway' => 'DISABLED', 'router_object_status' => 'Disabled'];
    } else {
        $data = ['test_expiry' => date('Y-m-d', strtotime('+3 days')), 'account_id' => $account_id, 'gateway' => $gateway, 'router_object_status' => 'Enabled', 'type' => 'Tunnel'];
    }

    \DB::table('isp_data_ip_ranges')->where('subscription_id', 0)->where('is_deleted', 0)->update($data);

    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('subscription_id', 0)->where('is_deleted', 0)->get();
    foreach ($ip_ranges as $ip_range) {

        $name = '';
        if (! empty($ip_range->account_id)) {
            $company = dbgetaccount($ip_range->account_id);
            $name = $company->company;
        }
        if ($ip_range->type == 'Route Object') {
            $name .= ' (ROUTE OBJECT)';
        }

        if (! $ip_range->subscription_id) {

            $name .= ' (TEST)';
        }

        $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';

        $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd, '9222');
        //echo $ip_range->ip_range.'- Comment Result:'.$result.'<br>';

        if ($ip_range->type != 'Route Object') {
            $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
            $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2, '9222');
            //echo $ip_range->ip_range.'- Gateway Result:'.$result2.'<br><br>';
        }

        set_ip_subscription_status($ip_range->ip_range, $ip_range->router_object_status);
    }
});

Route::any('email_template', function () {

    if (session('role_level') == 'Admin') {
        $data = [];
        $data['id'] = $id;
        $data['client_id'] = '2d51adde-29f4-412f-bb94-5385e46e77dc';
        $data['client_secret'] = 'INtalPnxglc2RFfUEQIbQiBEStcfbBdnNMhLgunbGhJ8XURfPlje';

        try {
            $html = \Storage::disk('templates')->get(session('instance')->directory.'/notification_html.txt');
            if (! empty($html)) {
                $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
                $html = str_replace('://'.$main_instance.'/get_email_logo', '://'.session('instance')->domain_name.'/get_email_logo', $html);
                $data['email_html'] = $html;
            } else {
                $data['email_html'] = '';
            }
        } catch (\Throwable $e) {
            $data['email_html'] = '';
        }

        return view('__app.components.email_template', $data);
    }
});

Route::any('reseller_email_template', function () {

    if (session('role_level') == 'Admin') {
        $data = [];
        $data['id'] = $id;
        $data['client_id'] = '2d51adde-29f4-412f-bb94-5385e46e77dc';
        $data['client_secret'] = 'INtalPnxglc2RFfUEQIbQiBEStcfbBdnNMhLgunbGhJ8XURfPlje';

        try {
            $html = \Storage::disk('templates')->get(session('instance')->directory.'/notification_reseller_html.txt');
            if (! empty($html)) {
                $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
                $html = str_replace('://'.$main_instance.'/get_email_logo', '://'.session('instance')->domain_name.'/get_email_logo', $html);
                $data['email_html'] = $html;
            } else {
                $data['email_html'] = '';
            }
        } catch (\Throwable $e) {
            $data['email_html'] = '';
        }

        return view('__app.components.email_template_reseller', $data);
    }
});

Route::any('email_template_reseller_save', function () {
    // aa('email_template_reseller_save');
    if (session('role_level') == 'Admin') {
        $id = request('id');
        $html = request('email_html');

        //if (!str_contains($html, 'get_email_logo')) {
        //    return json_alert('Logo url cannot be changed', 'warning');
        // }

        $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
        $html = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $html);
        $html = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $html);

        //$json = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $json);
        //$json = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $json);

        \Storage::disk('templates')->put(session('instance')->directory.'/notification_reseller_html.txt', $html);

        //\Storage::disk('templates')->put(session('instance')->directory.'/notification_json.txt', $json);
        //\Storage::disk('templates')->put(session('instance')->directory.'/notification_css.txt', '');
        return json_alert('Default template saved.');
    }
});

Route::any('email_template_save', function () {
    // aa('email_template_save');
    if (session('role_level') == 'Admin') {
        $id = request('id');
        $html = request('email_html');

        // aa($html);
        //if (!str_contains($html, 'get_email_logo')) {
        //    return json_alert('Logo url cannot be changed', 'warning');
        // }

        $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
        $html = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $html);
        $html = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $html);

        //$json = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $json);
        //$json = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $json);

        $r = \Storage::disk('templates')->put(session('instance')->directory.'/notification_html.txt', $html);

        // aa($r);
        //\Storage::disk('templates')->put(session('instance')->directory.'/notification_json.txt', $json);
        //\Storage::disk('templates')->put(session('instance')->directory.'/notification_css.txt', '');
        return json_alert('Default template saved.');
    }
});

Route::any('beefree_builder/{id?}', function ($id = false) {

    if (session('role_level') == 'Admin') {
        $data = [];
        $data['id'] = $id;
        $data['client_id'] = '2d51adde-29f4-412f-bb94-5385e46e77dc';
        $data['client_secret'] = 'INtalPnxglc2RFfUEQIbQiBEStcfbBdnNMhLgunbGhJ8XURfPlje';

        if ($id == 'default') {
            try {
                $html = \Storage::disk('templates')->get(session('instance')->directory.'/notification_json.txt');
                if (! empty($html)) {
                    $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
                    $html = str_replace('://'.$main_instance.'/get_email_logo', '://'.session('instance')->domain_name.'/get_email_logo', $html);
                    $data['template_json'] = $html;
                } else {
                    $data['template_json'] = '';
                }
            } catch (\Throwable $e) {
                $data['template_json'] = '';
            }
        } else {
            $beefree_builder_html = \DB::table('crm_newsletters')->where('id', $id)->pluck('beefree_builder_json')->first();
            if (! empty($beefree_builder_html)) {
                $html = \Erp::decode($beefree_builder_html);
                $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
                $html = str_replace('://'.$main_instance.'/get_email_logo', '://'.session('instance')->domain_name.'/get_email_logo', $html);
                $data['template_json'] = $html;
            }
        }

        return view('__app.components.pages.beefree', $data);
    }
});

Route::any('beefree_builder_save_default', function () {
    // aa('beefree_builder_save_default');
    if (session('role_level') == 'Admin') {
        $id = request('id');
        $html = request('html_file');
        $json = request('json_file');

        //if (!str_contains($html, 'get_email_logo')) {
        //    return json_alert('Logo url cannot be changed', 'warning');
        // }

        $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
        $html = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $html);
        $html = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $html);

        $json = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $json);
        $json = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $json);
        // aa($html);
        // aa($json);
        \Storage::disk('templates')->put(session('instance')->directory.'/notification_html.txt', $html);
        \Storage::disk('templates')->put(session('instance')->directory.'/notification_json.txt', $json);
        \Storage::disk('templates')->put(session('instance')->directory.'/notification_css.txt', '');

        return json_alert('Default template saved.');
    }
});

Route::any('beefree_builder_save', function () {
    if (session('role_level') == 'Admin') {
        $id = request('id');
        $html = request('html_file');
        $json = request('json_file');

        if (empty($id)) {
            return json_alert('Id required', 'error');
        }
        $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
        $html = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $html);
        $html = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $html);
        $json = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $json);
        $json = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $json);

        $html_file = \Erp::encode($html);
        $json = \Erp::encode($json);
        $data = [
            'beefree_builder_html' => $html_file,
            'beefree_builder_json' => $json,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => get_user_id_default(),
        ];

        \DB::connection('default')->table('crm_newsletters')->where('id', $id)->update($data);

        return json_alert('Newsletter saved.');
    }
});

Route::post('conditional_update_rule_save', function () {
    $id = request('id');
    $update_rules = request('update_rules');
    $sql_where = request('sql_where');

    $row = \DB::table('erp_conditional_updates')->where('id', $id)->get()->first();
    if (empty($row) || empty($row->id)) {
        return json_alert('Row not set', 'warning');
    }

    $module = \DB::table('erp_cruds')->where('id', $row->module_id)->get()->first();
    $table = $module->db_table;
    $module_conn = $module->connection;
    $date_values = get_condition_update_date_values();
    $date_columns = get_columns_from_schema($table, 'date', $module_conn);
    foreach ($date_columns as $field) {
        foreach ($date_values as $date_value) {
            if (str_contains($sql_where, $field) && str_contains($sql_where, $date_value)) {
                $replace_sql = get_condition_update_date_sql($field, $date_value);
                $search_sql = "$field = '$date_value'";
                $sql_where = str_replace($search_sql, $replace_sql, $sql_where);
            }
        }
    }

    $datetime_columns = get_columns_from_schema($table, 'datetime', $module_conn);
    foreach ($datetime_columns as $field) {

        foreach ($date_values as $date_value) {
            if (str_contains($sql_where, $field) && str_contains($sql_where, $date_value)) {
                $replace_sql = get_condition_update_date_sql($field, $date_value);
                $search_sql = "$field = '$date_value'";
                $sql_where = str_replace($search_sql, $replace_sql, $sql_where);
            }
        }
    }

    \DB::table('erp_conditional_updates')->where('id', $id)->update(['update_rules' => $update_rules, 'sql_where' => $sql_where]);

    return json_alert('Saved');
});

Route::any('status_dropdown_update/{module_id?}/{status_field?}/{status_key?}/{row_id?}', function ($module_id, $status_field, $status_key, $row_id) {
    return status_dropdown_ajax($module_id, $status_field, $status_key, $row_id);
});

Route::any('freelancercode_checked', function () {
    system_log('backup', 'freelancercode checked', 'success', 'freelancercode', 'daily');
});

Route::any('service_setup_email/{id?}', function ($id) {
    try {
        $sub = \DB::table('sub_services')->where('id', $id)->get()->first();
        $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

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

        $customer = dbgetaccount($sub->account_id);

        $data['detail'] = $sub->detail;
        $data['product'] = ucwords(str_replace('_', ' ', $product->code));
        $data['product_code'] = $product->code;
        $data['product_description'] = $product->name;
        $data['portal_url'] = get_whitelabel_domain($customer->partner_id);

        $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer);

        if (! empty($activation_data) && count($activation_data) > 0) {
            foreach ($activation_data as $k => $v) {
                $data[$k] = $v;
            }
        }

        $data['activation_email'] = true;

        return email_form($email_id, $sub->account_id, $data);
    } catch (\Throwable $ex) {
        exception_log($ex);
        //dev_email('subscription details email error');
        exception_log($ex->getMessage());
        exception_log($ex->getTraceAsString());

        return json_alert('Subscription details error', 'warning');
    }

});

Route::any('account_edit_pbx_domain/{domain_uuid?}', function ($domain_uuid) {
    $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('account_id')->first();
    if ($account_id) {
        $accounts_url = get_menu_url_from_module_id(343);

        return redirect()->to($accounts_url.'/edit/'.$account_id);
    }
});

Route::any('ledger_links/{layout_id?}', function ($layout_id) {});

Route::any('transaction_links/{layout_id?}', function ($layout_id) {});

Route::any('preview_email/{id?}', function ($id) {
    if (session('role_level') == 'Admin') {
        $message = \DB::table('crm_email_manager')->where('id', $id)->get()->first();
        $data = [
            'year' => date('Y'),
            'partner_company' => dbgetaccount(1)->company,
            'customer' => dbgetaccount(12),
            'reseller' => dbgetaccount(1),
        ];
        $message->message = str_replace('{{', '[', $message->message);
        $message->message = str_replace('}}', ']', $message->message);
        $data['msg'] = erp_email_blend(nl2br($message->message), $data);

        $data['html'] = get_email_html(1, 1, $data, $message);
        $data['css'] = '';
        $template_file = '_emails.gjs';

        return view($template_file, $data);
    }
});

Route::any('communications_view_email/{id?}', function ($id) {
    $email = \DB::table('erp_communication_lines')->where('id', $id)->get()->first();
    $attachments = explode(',', $email->attachments);
    $view = '<div class="container">';
    $view .= '<div class="card my-3">';
    $view .= '<div class="card-body" style="background-color: #f9f9f9;">';
    $view .= '<div class="row">';

    $view .= '<div class="col">';

    if (! empty($email->created_at)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>Date Sent</b></div><div class="col">'.$email->created_at.'</div></div>';
    }

    if (! empty($email->source)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>From Address</b></div><div class="col">'.$email->source.'</div></div>';
    }
    if (! empty($email->subject)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>Subject</b></div><div class="col">'.$email->subject.'</div></div>';
    }

    if (! empty($email->destination)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>To Address</b></div><div class="col">'.$email->destination.'</div></div>';
    }

    if (! empty($email->cc_email)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>CC Address</b></div><div class="col">'.$email->cc_email.'</div></div>';
    }

    if (! empty($email->bcc_email)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>BCC Address</b></div><div class="col">'.$email->bcc_email.'</div></div>';
    }

    if (count($attachments) > 0) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>Attachments</b></div><div class="col"> ';
        foreach ($attachments as $a) {
            $view .= '<a href="'.url(attachments_url().$a).'" target="_blank">'.$a.'</a><br>';
        }
        $view .= '</div></div>';
    }

    $view .= '</div>';
    $view .= '</div>';

    $view .= '</div>';

    $view .= '<div class="card-body border-top">';
    $view .= $email->message;

    $view .= '</div>';
    $view .= '</div>';
    $view .= '</div>';
    echo $view;
});

Route::any('edit_event_code/{id?}', function ($id) {
    $event_function = \DB::connection('default')->table('erp_form_events')->where('id', $id)->pluck('function_name')->first();

    $code_edit_url = get_menu_url_from_table('erp_code_edits');

    return redirect()->to('/'.$code_edit_url.'/edit?tab_load=1&function_name='.$event_function);
});

Route::any('download_branding_pricelist/{pricelist_id?}/{storefront_id?}', function ($pricelist_id, $storefront_id) {
    $file_name = export_pricelist_storefront($pricelist_id, $storefront_id);
    //if(is_dev()){
    //    return $file_name;
    //}

    $file_path = uploads_path().'/pricing_exports/'.$file_name;

    return response()->download($file_path, $file_name);
});

Route::any('layout_row_tracking_details/{module_id?}/{row_id?}/{layout_id?}', function ($module_id, $row_id, $layout_id) {
    $task = \DB::table('crm_workflow_tracking')->select('timer_status', 'start_time', 'duration')->where('layout_id', $layout_id)->where('module_id', $module_id)->where('row_id', $row_id)->where('row_id', $row_id)->get()->first();
    if (empty($task)) {
        $task = (object) [
            'timer_status' => '',
            'start_time' => '',
            'duration' => '',
        ];
    }

    return response()->json($task);
});

Route::any('refresh_color_scheme/{grid_id?}', function ($grid_id) {

    $color_scheme = [];
    $color_scheme['sidebar_color'] = (! empty(session('instance')->sidebar_color)) ? session('instance')->sidebar_color : '#e9e9e9';
    $color_scheme['sidebar_text_color'] = (! empty(session('instance')->sidebar_text_color)) ? session('instance')->sidebar_text_color : '#e9e9e9';

    $color_scheme['first_row_color'] = (! empty(session('instance')->first_row_color)) ? session('instance')->first_row_color : '#e9e9e9';
    $color_scheme['first_row_buttons_color'] = (! empty(session('instance')->first_row_buttons_color)) ? session('instance')->first_row_buttons_color : '#e9e9e9';

    $color_scheme['second_row_color'] = (! empty(session('instance')->second_row_color)) ? session('instance')->second_row_color : '#e9e9e9';
    $color_scheme['second_row_text_color'] = (! empty(session('instance')->second_row_text_color)) ? session('instance')->second_row_text_color : '#000000';
    $color_scheme['second_row_buttons_color'] = (! empty(session('instance')->second_row_buttons_color)) ? session('instance')->second_row_buttons_color : '#e9e9e9';

    $styles = '
        #app_toolbar .k-button {
            background: '.$color_scheme['first_row_buttons_color'].' !important;
        }
        
        #gridheadertoolbar'.$grid_id.' .k-button {
            background: '.$color_scheme['second_row_buttons_color'].' !important;
        }
        
        #adminheader, #app_toolbar, #app_toolbar .e-toolbar-items {
            background: '.$color_scheme['first_row_color'].';
        }
        
        #gridheadertoolbar'.$grid_id.', #gridheadertoolbar'.$grid_id.' .e-toolbar-items{
            background-color: '.$color_scheme['second_row_color'].';
        }
        
        #topicon_menu,#topicon_menu li a,#topicon_menu li .e-menu-icon,#topicon_menu  .e-menu-item:hover,#topicon_menu .e-menu-item.e-selected {
            background-color: '.$color_scheme['sidebar_color'].';
            color: white; 
        }
        .sidebar-menu, .sidebar-menu ul {
            background: '.$color_scheme['sidebar_color'].' !important;
        }
        .sidebar-menu .dock-menu .e-menu-wrapper, .sidebar-menu .dock-menu.e-menu-wrapper, .sidebar-menu .dock-menu.e-menu-wrapper ul>*, .sidebar-menu .dock-menu .e-menu-wrapper ul>* {
            background: '.$color_scheme['sidebar_color'].' !important;
        }
        #customer_toolbar, #customer_toolbar .e-toolbar-items{
            background: '.$color_scheme['sidebar_color'].';
        }
        
        .dock-menu .e-menu-wrapper,
        .dock-menu.e-menu-wrapper,
        .dock-menu.e-menu-wrapper ul>*,
        .dock-menu .e-menu-wrapper ul>* {
        background: '.$color_scheme['sidebar_color'].' !important;
        }
        .sidebar-menu .e-menu-wrapper ul .e-menu-item .e-menu-url, .sidebar-menu .e-menu-container ul .e-menu-item .e-menu-url, .sidebar-menu .e-menu-wrapper ul .e-menu-item .e-menu-icon, .sidebar-menu .e-menu-container ul .e-menu-item .e-menu-icon, .sidebar-menu .e-menu-wrapper ul .e-menu-item .e-menu-icon::before, .sidebar-menu .e-menu-container ul .e-menu-item .e-menu-icon::before, .sidebar-menu .e-menu-wrapper ul .e-menu-item .e-caret, .sidebar-menu .e-menu-container ul .e-menu-item .e-caret {
        
        color:  '.$color_scheme['sidebar_text_color'].' !important;
        
        }
        .dock-menu.e-menu-wrapper .e-ul .e-menu-item .e-menu-url, .dock-menu.e-menu-wrapper ul .e-menu-item .e-caret {
        color:  '.$color_scheme['sidebar_text_color'].' !important;
        }
        
        #toolbar_template_title'.$grid_id.', #toolbar_template_title'.$grid_id.' span{
            font-family: "Lato", Arial, Sans-serif !important;
            font-weight: bold;
            color: '.$color_scheme['second_row_text_color'].';
        }
    ';
    echo $styles;
});

Route::any('download_available_numbers', function () {

    $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->where('enabled', 'true')->pluck('gateway_uuid')->toArray();

    $numbers = \DB::connection('pbx')->table('p_phone_numbers')->select('prefix', 'number')->whereIn('gateway_uuid', $gateway_uuids)->whereNull('domain_uuid')->where('is_spam', 0)->where('status', 'Enabled')->orderBy('number')->get();

    $file_title = 'Available Phone Numbers';
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $excel_list = [];

    foreach ($numbers as $n) {
        $excel_list[] = (array) $n;
    }

    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
    $file_path = attachments_path().$file_name;

    return response()->download($file_path, $file_name);
});

Route::any('save_sidebar_state', function () {
    if (! is_superadmin()) {
        return json_alert('No Access', 'warning');
    }
    $module_id = request('module_id');
    $sidebar_state = request('sidebar_state');

    if (empty($module_id) || empty($sidebar_state)) {
        return json_alert('Empty Post Data', 'warning');
    }
    \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->update(['sidebar_state' => $sidebar_state]);

    return json_alert('Done');

});

Route::any('download_available_ip_ranges', function () {

    $ranges = \DB::connection('default')->table('isp_data_ip_ranges')->where('is_deleted', 0)->orderBy('sort_order')->get();

    $available_ranges = [];

    foreach ($ranges as $range) {
        if ($range->subscription_id == 0) {
            $available = 'yes';

            $available_ranges[] = ['ip_range' => $range->ip_range, 'available' => $available];
        } else {

            $cancelled = \DB::table('sub_services')->where('detail', $range->ip_range)->where('status', '!=', 'Deleted')->where('to_cancel', 1)->get()->first();
            if (! empty($cancelled) && (! empty($cancelled->id))) {
                $available = date('Y-m-d', strtotime($cancelled->renews_at.' +1 day'));
                $available_ranges[] = ['ip_range' => $range->ip_range, 'available' => $available];
            }

        }
    }
    if (count($available_ranges) == 0) {
        return 'No IP Ranges are currently available';
    }

    $file_title = 'Available IP Ranges';
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $excel_list = [];

    foreach ($available_ranges as $n) {
        foreach ($n as $k => $v) {
            $n[ucwords(str_replace('_', ' ', $k))] = $v;
        }
        $excel_list[] = (array) $n;
    }

    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
    $file_path = attachments_path().$file_name;

    return response()->download($file_path, $file_name);
});

Route::post('bank_ofx_import', 'CustomController@bankofxImport');

Route::any('download_pricelist_zar', function () {
    $pricelist = \DB::connection('default')->table('crm_pricelists')->where('partner_id', 1)->where('currency', 'ZAR')->get()->first();

    export_pricelist($pricelist->id, 'pdf', true, 'Pricelist_'.$pricelist->currency.'.pdf');
    File::copy(uploads_path().'/'.$instance_dir.'/pricing_exports/Pricelist_'.$pricelist->currency.'.pdf', public_path().'/attachments/'.$instance_dir.'/Pricelist_'.$pricelist->currency.'.pdf');

    return response()->download(uploads_path().'/pricing_exports/Pricelist_ZAR.pdf');
});
Route::any('download_pricelist_usd', function () {
    $pricelist = \DB::connection('default')->table('crm_pricelists')->where('partner_id', 1)->where('currency', 'USD')->get()->first();

    export_pricelist($pricelist->id, 'pdf', true, 'Pricelist_'.$pricelist->currency.'.pdf');
    File::copy(uploads_path.'/'.$instance_dir.'/pricing_exports/Pricelist_'.$pricelist->currency.'.pdf', public_path().'/attachments/'.$instance_dir.'/Pricelist_'.$pricelist->currency.'.pdf');

    return response()->download(uploads_path().'/pricing_exports/Pricelist_USD.pdf');
});

Route::any('get_notifications', function () {
    $unread_result = '';
    $all_result = '';

    if (auth()->user()->unreadNotifications->count() > 0) {

        foreach (auth()->user()->unreadNotifications as $n) {
            $unread_result .= '<div class="text-reset notification-item d-block dropdown-item position-relative">
        <div class="d-flex">';
            if ($n->data['message_url']) {
                $unread_result .= '<div class="avatar-xs me-3">
            <span class="avatar-title bg-soft-info text-info rounded-circle fs-16">
            <a title="View" href="'.url($n->data['message_url']).'" target="_blank"><i class="ri-arrow-right-circle-line"></i></a>
            </span>
            </div>';
            }
            $unread_result .= '<div class="flex-1">
        '.$n->data['message'].'
        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
        <span><i class="mdi mdi-clock-outline"></i> '.$n->created_at->diffForHumans().'</span>
        </p>
        </div>
        <div class="px-2 fs-15">
        <input class="form-check-input read_notification" data-id="'.$n->id.'" type="checkbox">
        </div>
        </div>
        </div>';
        }
    } else {
        $unread_result .= '<div class="w-25 w-sm-50 pt-3 mx-auto">
        <img src="'.public_path().('/assets/velzon/images/svg/bell.svg').'" class="img-fluid" alt="user-pic">
        </div>
        <div class="text-center pb-5 mt-2">
        <h6 class="fs-18 fw-semibold lh-base">Hey! You have no unread notifications </h6>
        </div>';
    }

    if (auth()->user()->notifications->count() > 0) {
        foreach (auth()->user()->notifications as $n) {
            $all_result .= '<div class="text-reset notification-item d-block dropdown-item position-relative">
        <div class="d-flex">';
            if ($n->data['message_url']) {
                $all_result .= '<div class="avatar-xs me-3">
            <span class="avatar-title bg-soft-info text-info rounded-circle fs-16">
            <a title="View" href="'.url($n->data['message_url']).'" target="_blank"><i class="ri-arrow-right-circle-line"></i></a>
            </span>
            </div>';
            }
            $all_result .= '<div class="flex-1">
        '.$n->data['message'].'
        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
        <span><i class="mdi mdi-clock-outline"></i> '.$n->created_at->diffForHumans().'</span>
        </p>
        </div>
        </div>
        </div>';
        }
    } else {
        $all_result .= '<div class="w-25 w-sm-50 pt-3 mx-auto">
        <img src="'.public_path().('/assets/velzon/images/svg/bell.svg').'" class="img-fluid" alt="user-pic">
        </div>
        <div class="text-center pb-5 mt-2">
        <h6 class="fs-18 fw-semibold lh-base">Hey! You have no notifications </h6>
        </div>';
    }
    $unread_result = '<div data-simplebar style="max-height: 300px;" class="pe-2">'.$unread_result.'</div>';
    $all_result = '<div data-simplebar style="max-height: 300px;" class="pe-2">'.$all_result.'</div>';
    $data = [
        'unread_result' => $unread_result,
        'all_result' => $all_result,
        'unread_total' => auth()->user()->unreadNotifications->count(),
        'all_total' => auth()->user()->notifications->count(),
    ];

    return $data;
});

Route::any('read_notification/{notification_id?}', function ($notification_id) {
    $userUnreadNotification = auth()->user()
        ->unreadNotifications
        ->where('id', $notification_id)
        ->first();

    if ($userUnreadNotification) {
        $userUnreadNotification->markAsRead();
    }
});

Route::any('main_menu_datasource/{module_id?}/{role_id?}', function ($module_id, $role_id) {
    $current_module = \DB::connection('default')->table('erp_cruds')->select('id', 'connection', 'app_id')->where('id', $module_id)->get()->first();
    $menu_params = [];
    // setup menu params
    if ($current_module) {

        $current_menu = \DB::connection('default')->table('erp_menu')->select('id', 'module_id', 'location', 'url')
            ->where('module_id', $current_module->id)->where('menu_type', 'module')->where('active', 1)->get()->first();

        $menu_params = [];
        $menu_params['menu_url'] = $current_menu->url;
        $menu_params['module_id'] = $current_menu->module_id;
        $menu_params['connection'] = $current_module->connection;
        $menu_params['app_id'] = $current_module->app_id;
        $menu_params['workspace_role_id'] = $role_id;

        if ($current_menu->id) {
            $menu_params['menu_id'] = $current_menu->id;
        }
    }

    $main_menu = \ErpMenu::build_menu('main_menu', $menu_params);

    return response()->json($main_menu);
});

Route::any('policies_datasource/{module_id?}/{grid_id?}', function ($module_id, $grid_id) {
    $kb_url = get_menu_url_from_module_id(1875);
    $policies = \DB::connection('default')->table('crm_training_guides')->where('is_deleted', 0)->where('module_id', $module_id)->get();

    $items = [];
    if (! empty($policies)) {
        foreach ($policies as $policy) {
            $items[] = (object) [
                'id' => 'policy'.$policy->id,
                'cssClass' => 'policyitem'.$grid_id,
                'text' => $policy->name,
                'url' => '/kb_content/'.$policy->id,
                'data_target' => 'view_modal',
                'view_id' => $policy->id,
            ];
        }
    } else {
        $items[] = (object) [
            'id' => 'policyplaceholder',
            'cssClass' => 'policyplaceholder',
            'text' => '',
            'url' => '#',
            'data_target' => '',
            'view_id' => '',
        ];
    }

    $datasource = [(object) [
        'id' => 'policyheader',
        'text' => 'Guides ('.count($policies).')',
        'cssClass' => 'policy-header policyitem'.$grid_id,
        'url' => '#',
        'view_id' => 'header',
        'items' => $items,
    ]];

    $response = ['dropdown' => $datasource];

    return response()->json($response);
});

Route::any('tab_rename', function () {
    if (is_superadmin()) {
        $before_name = request()->before_name;
        $after_name = request()->after_name;
        $module_id = request()->module_id;
        if (empty($module_id)) {
            return json_alert('module_id not set', 'warning');
        }
        if (empty($before_name)) {
            return json_alert('current tab name not set', 'warning');
        }
        if (empty($after_name)) {
            return json_alert('new tab name not set', 'warning');
        }
        \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('tab', $before_name)->update(['tab' => $after_name]);
        $response = ['status' => 'success', 'message' => 'Field tab updated.', 'callback_function' => 'reload_active_form'.$module_id];

        return response()->json($response);
    }
});

Route::any('field_tab_update/{module_id?}/{field_id?}/{tab?}', function ($module_id, $field_id, $tab) {
    if (is_superadmin()) {
        if ($tab == 'Other') {
            $tab = '';
        }

        \DB::table('erp_module_fields')->where('id', $field_id)->update(['tab' => $tab]);
        if ($tab == 'New Tab') {
            $max_sort = \DB::table('erp_module_fields')->where('module_id', $module_id)->max('sort_order');
            $max_sort++;
            \DB::table('erp_module_fields')->where('id', $field_id)->update(['sort_order' => $max_sort]);
        }
        Cache::forget(session('instance')->id.'module_fields'.$module_id);

        $tabs = \DB::table('erp_module_fields')->where('module_id', $module_id)->orderBy('sort_order')->pluck('tab')->unique()->toArray();

        foreach ($tabs as $tab) {
            if ($tab == 'Other') {
                $tab = '';
            }
            $fields = \DB::connection('default')->table('erp_module_fields')->select('id')->where('module_id', $module_id)->where('tab', $tab)->orderBy('sort_order')->get();

            foreach ($fields as $f) {
                \DB::connection('default')->table('erp_module_fields')->where('id', $f->id)->update(['sort_order' => $sort_order]);
                $sort_order++;
            }
        }

        $response = ['status' => 'success', 'message' => 'Field tab updated.', 'callback_function' => 'reload_active_form'.$module_id];

        return response()->json($response);
    }
});

Route::any('field_visible_setting/{module_id?}/{field_id?}/{setting?}', function ($module_id, $field_id, $setting) {
    if (session('role_level') == 'Admin') {

        $setting = ucfirst($setting);

        if ($setting == 'both') {
            $setting = 'Add and Edit';
        }
        $setting = ucfirst($setting);
        \DB::table('erp_module_fields')->where('id', $field_id)->update(['visible' => $setting]);
        Cache::forget(session('instance')->id.'module_fields'.$module_id);
        $response = ['status' => 'success', 'message' => 'Field visibility updated.', 'callback_function' => 'reload_active_form'.$module_id];

        return response()->json($response);
    }
});

Route::any('set_field_style_template/{module_id?}/{field?}/{template?}', function ($module_id, $field, $template) {
    if (session('role_level') == 'Admin') {

        $field_id = \DB::table('erp_module_fields')->where('module_id', $module_id)->where('field', $field)->pluck('id')->first();
        if ($template == 'None') {

            \DB::table('erp_grid_styles')->where('module_id', $module_id)->where('field_id', $field_id)->delete();
        } elseif ($template == 'bold') {
            $bold = \DB::table('erp_grid_styles')->where('module_id', $module_id)->where('field_id', $field_id)->pluck('text_bold')->first();

            \DB::table('erp_grid_styles')->where('module_id', $module_id)->where('field_id', $field_id)->update(['text_bold' => ($bold) ? 0 : 1]);
        } else {

            $colors = get_site_colors_templates();
            $data = $colors[$template];
            $data['template'] = $template;
            $data['field_id'] = $field_id;
            $data['module_id'] = $module_id;

            $exists = \DB::table('erp_grid_styles')->where('module_id', $module_id)->where('field_id', $field_id)->count();
            if ($exists) {
                \DB::table('erp_grid_styles')->where('module_id', $module_id)->where('field_id', $field_id)->update($data);
            } else {
                \DB::table('erp_grid_styles')->insert($data);
            }
        }

        $refresh_module_id = $module_id;
        $master_module_id = \DB::table('erp_cruds')->where('detail_module_id', $refresh_module_id)->pluck('id')->first();
        if ($master_module_id) {

            $refresh_module_id = $master_module_id;
        }
        $response = ['status' => 'success', 'message' => 'Record Saved.', 'reload_conditional_styles' => true, 'module_id' => $refresh_module_id];

        return response()->json($response);
    }
});

Route::any('linkedrecords/{module_id?}/{row_id?}/{field?}', function ($module_id, $row_id, $field) {
    if (session('role_level') == 'Admin') {
        $field = str_replace('join_', '', $field);
        $module = \DB::table('erp_cruds')->select('connection', 'db_table', 'db_key')->where('id', $module_id)->get()->first();
        $record_id = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $row_id)->pluck($field)->first();
        $field_conf = \DB::table('erp_module_fields')->where('field', $field)->where('module_id', $module_id)->get()->first();
        $url = \DB::table('erp_cruds')->where('db_table', $field_conf->opt_db_table)->pluck('slug')->first();

        if ($url > '') {
            return redirect()->to($url.'?id='.$record_id);
        } else {
            $module = 'detailmodule_'.$field_module_id;

            return redirect()->to($url.'?id='.$record_id);
        }

        return redirect()->to($url.'?id='.$record_id);
    }
});
Route::any('create_default_report/{module_id?}', function ($module_id) {
    if (is_superadmin()) {
        $id = create_default_report($module_id);
        $url = get_menu_url_from_table('erp_reports');

        return redirect()->to($url.'?id='.$id);
    }
});

Route::any('update_instances', function () {});

Route::any('toggle_accounts_role', function () {
    if (session('role_level') == 'Admin') {

        if (session('original_role_id') == session('role_id')) {
            session(['toggle_accounts_role' => 1]);
            session(['role_id' => 35]);
        } else {
            session()->forget('toggle_accounts_role');
            session(['role_id' => session('original_role_id')]);
        }
    }

    return redirect()->to('/');
});

Route::any('toggle_workboard_completed_tasks', function () {
    if (! empty(session('toggle_workboard_completed_tasks'))) {
        session()->forget('toggle_workboard_completed_tasks');
    } else {
        session(['toggle_workboard_completed_tasks' => 1]);
    }

    return json_alert('ok');
});

Route::any('filter_soft_delete/{module_id?}/{status?}', function ($module_id, $status) {

    session(['show_deleted'.$module_id => $status]);

    return json_alert('ok');
});

Route::any('update_workspace_user_filter/{module_id?}/{user_id?}', function ($module_id, $user_id) {

    session(['workspace_user_filter'.$module_id => $user_id]);
});

Route::any('update_workspace_role_filter/{module_id?}/{user_id?}', function ($module_id, $role_id) {

    //session(['workspace_role_id' => $role_id]);

});

Route::any('cdr_variables/{table}/{cdr_id}', function ($table, $id) {
    $cdr = \DB::connection('pbx_cdr')->table($table.'_variables')->where('call_records_outbound_id', $id)->first();
    if ($table == 'call_records_inbound') {
        $data = [
            'variables' => $cdr->variables,
        ];
    } else {
        $data = [
            'variables' => $cdr->variables,
            'callflow' => $cdr->callflow,
            'applog' => $cdr->app_log,
        ];
    }

    return view('__app.button_views.cdr_details', $data);
});

Route::any('newsletter_view/{newsletter_id}', function ($newsletter_id) {
    $newsletter = \DB::table('crm_newsletters')->where('id', $newsletter_id)->get()->first();
    $template_file = '_emails.newsletter';

    // mail data
    $data = [];
    if ($newsletter->use_beefree_builder) {
        $data['html'] = \Erp::decode($newsletter->beefree_builder_html);
        $data['css'] = '';
    } else {
        $data['html'] = \Erp::decode($newsletter->stripo_html);
        $data['css'] = \Erp::decode($newsletter->stripo_css);
    }
    $data['html'] = $newsletter->email_html;
    $data['menu_name'] = $newsletter->name;
    //$data['html'] = str_ireplace('[newsletter_footer]','',$data['html']);

    $newsletter_footer = get_admin_setting('newsletter_footer');
    // $newsletter_footer .= PHP_EOL.'[browserlink] | [unsubscribe]';
    // ubsubscribe link

    $link_params = \Erp::encode(['account_id' => 12]);
    $unsubscribe_url = request()->root().'/mail_unsubscribe/'.$link_params;
    $unsubscribe_url = str_replace('http://', 'https://', $unsubscribe_url);
    //$unsubscribe_text = '<a href="'.$unsubscribe_url.'" target="_blank" style="font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #000; font-weight: bold; text-decoration: none; border-radius: 5px; background-color: #fff; border-top: 2px solid #fff; border-bottom: 2px solid #fff; border-right: 8px solid #fff; border-left: 8px solid #fff; display: inline-block;">Unsubscribe</a>';

    $newsletter_footer = str_ireplace('https://#unsubscribe', $unsubscribe_url, $newsletter_footer);

    $browser_link = '<a href='.url('newsletter_view/'.$newsletter->id).'" target="_blank" style="font-size: 12px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 5px; background-color: #6666ff; border-top: 2px solid #6666ff; border-bottom: 2px solid #6666ff; border-right: 8px solid #6666ff; border-left: 8px solid #6666ff; display: inline-block;">View in browser</a>';

    $newsletter_footer = str_ireplace('[browserlink]', $browser_link, $newsletter_footer);

    $data['html'] = str_ireplace('[newsletter_footer]', $newsletter_footer, $data['html']);

    return view($template_file, $data);
});

Route::any('rest_api_documentation', function () {
    return view('__app.components.pages.openapi');
});

Route::any('module_update_instances/{instance_module_id?}', function ($instance_module_id) {
    if (session('role_level') == 'Admin') {
        try {
            // FIELDS
            $module_fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $instance_module_id)->get();
            foreach ($module_fields as $module_field) {
                $row_id = $module_field->id;
                $update_id = $row_id;
                if (! is_main_instance()) {
                    $module_field = \DB::connection('default')->table('erp_module_fields')->where('main_instance_id', $row_id)->get()->first();
                    if (empty($module_field) || empty($module_field->main_instance_id)) {
                        $data = (array) $module_field;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = \DB::connection('system')->table('erp_module_fields')->insertGetId($data);

                        \DB::connection('default')->table('erp_module_fields')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                    } else {
                        $data = (array) $module_field;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = $module_field->main_instance_id;
                        \DB::connection('system')->table('erp_module_fields')->where('id', $update_id)->update($data);
                    }
                }
                $module_field = \DB::connection('system')->table('erp_module_fields')->where('id', $update_id)->get()->first();
                $db_conns = db_conns_excluding_main();
                foreach ($db_conns as $db_conn) {
                    $data = (array) $module_field;
                    unset($data['id']);
                    $data['main_instance_id'] = $update_id;
                    $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $module_field->module_id)->pluck('id')->first();
                    $exists = \DB::connection($db_conn)->table('erp_module_fields')->where('main_instance_id', $update_id)->count();
                    if (! $exists) {
                        \DB::connection($db_conn)->table('erp_module_fields')->insert($data);
                    } else {
                        \DB::connection($db_conn)->table('erp_module_fields')->where('main_instance_id', $update_id)->update($data);
                    }
                }
            }

            // LAYOUTS
            $grid_views = \DB::connection('default')->table('erp_grid_views')->where('module_id', $instance_module_id)->get();
            foreach ($grid_views as $grid_view) {
                $row_id = $grid_view->id;
                $update_id = $row_id;
                if (! is_main_instance()) {
                    $grid_view = \DB::connection('default')->table('erp_grid_views')->where('main_instance_id', $row_id)->get()->first();
                    if (empty($grid_view) || empty($grid_view->main_instance_id)) {
                        $data = (array) $grid_view;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = \DB::connection('system')->table('erp_grid_views')->insertGetId($data);

                        \DB::connection('default')->table('erp_grid_views')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                    } else {
                        $data = (array) $grid_view;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = $grid_view->main_instance_id;
                        \DB::connection('system')->table('erp_grid_views')->where('id', $update_id)->update($data);
                    }
                }
                $grid_view = \DB::connection('system')->table('erp_grid_views')->where('id', $update_id)->get()->first();
                $db_conns = db_conns_excluding_main();
                foreach ($db_conns as $db_conn) {
                    $data = (array) $grid_view;
                    unset($data['id']);
                    $data['main_instance_id'] = $update_id;
                    $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $grid_view->module_id)->pluck('id')->first();
                    $exists = \DB::connection($db_conn)->table('erp_grid_views')->where('main_instance_id', $update_id)->count();

                    if (! $exists) {
                        \DB::connection($db_conn)->table('erp_grid_views')->insert($data);
                    } else {
                        \DB::connection($db_conn)->table('erp_grid_views')->where('main_instance_id', $update_id)->update($data);
                    }
                }
            }

            // FORMS
            $forms = \DB::connection('default')->table('erp_forms')->where('module_id', $instance_module_id)->get();
            foreach ($forms as $form) {
                $row_id = $form->id;
                $update_id = $row_id;
                if (! is_main_instance()) {
                    $form = \DB::connection('default')->table('erp_forms')->where('main_instance_id', $row_id)->get()->first();
                    if (empty($form) || empty($form->main_instance_id)) {
                        $data = (array) $form;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = \DB::connection('system')->table('erp_forms')->insertGetId($data);

                        \DB::connection('default')->table('erp_forms')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                    } else {
                        $data = (array) $form;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = $form->main_instance_id;
                        \DB::connection('system')->table('erp_forms')->where('id', $update_id)->update($data);
                    }
                }
                $form = \DB::connection('system')->table('erp_forms')->where('id', $update_id)->get()->first();
                $db_conns = db_conns_excluding_main();
                foreach ($db_conns as $db_conn) {
                    $data = (array) $form;
                    unset($data['id']);
                    $data['main_instance_id'] = $update_id;
                    $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $form->module_id)->pluck('id')->first();
                    $exists = \DB::connection($db_conn)->table('erp_forms')->where('main_instance_id', $update_id)->count();
                    if (! $exists) {
                        \DB::connection($db_conn)->table('erp_forms')->insert($data);
                    } else {
                        \DB::connection($db_conn)->table('erp_forms')->where('main_instance_id', $update_id)->update($data);
                    }
                }
            }

            // REPORTS
            $reports = \DB::connection('default')->table('erp_reports')->where('module_id', $instance_module_id)->get();
            foreach ($reports as $report) {
                $row_id = $report->id;
                $update_id = $row_id;
                if (! is_main_instance()) {
                    $report = \DB::connection('default')->table('erp_reports')->where('main_instance_id', $row_id)->get()->first();
                    if (empty($report) || empty($report->main_instance_id)) {
                        $data = (array) $report;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = \DB::connection('system')->table('erp_reports')->insertGetId($data);

                        \DB::connection('default')->table('erp_reports')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                    } else {
                        $data = (array) $report;
                        unset($data['id']);
                        unset($data['main_instance_id']);
                        $update_id = $report->main_instance_id;
                        \DB::connection('system')->table('erp_reports')->where('id', $update_id)->update($data);
                    }
                }
                $report = \DB::connection('system')->table('erp_reports')->where('id', $update_id)->get()->first();
                $db_conns = db_conns_excluding_main();
                foreach ($db_conns as $db_conn) {
                    $data = (array) $report;
                    unset($data['id']);
                    $data['main_instance_id'] = $update_id;
                    $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $report->module_id)->pluck('id')->first();
                    $exists = \DB::connection($db_conn)->table('erp_reports')->where('main_instance_id', $update_id)->count();
                    if (! $exists) {
                        \DB::connection($db_conn)->table('erp_reports')->insert($data);
                    } else {
                        \DB::connection($db_conn)->table('erp_reports')->where('main_instance_id', $update_id)->update($data);
                    }
                }
            }

            return json_alert('Copied');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage(), 'error');
        }
    }
});

Route::any('sipphone', function () {
    if (session('role_level') == 'Admin') {
        $user = \DB::table('erp_users')->where('id', session('user_id'))->get()->first();

        if ($user->pbx_extension) {
            $ext = \DB::connection('pbx')->table('v_extensions')->where('accountcode', 'pbx.cloudtools.co.za')->where('extension', $user->pbx_extension)->get()->first();
            //   $server = '156.0.96.62';
            $server = 'pbx.cloudtools.co.za';
            if ($ext) {
                return redirect()->to('http://pbx.cloudtools.co.za/webphone/viciphone.php?phone_login='.base64_encode($ext->extension).'&phone_pass='.base64_encode($ext->password).'&server_ip='.base64_encode($server));
            }
        }
    }
});

Route::any('pricing_edit/{pricelist_id?}/{product_id?}', function ($pricelist_id, $product_id) {
    if (session('role_level') == 'Admin') {
        $pricelist_currency = \DB::table('crm_pricelists')->where('id', $pricelist_id)->pluck('currency')->first();
        $item_id = \DB::table('crm_pricelist_items')->where('pricelist_id', $pricelist_id)->where('product_id', $product_id)->pluck('id')->first();
        if ($pricelist_currency == 'USD') {
            $menu_name = get_menu_url_from_module_id(1894);
        } else {
            $menu_name = get_menu_url_from_module_id(508);
        }

        $url = $menu_name.'?id='.$item_id;

        return redirect()->to($url);
    }
});

Route::any('policy_edit/{module_id?}', function ($module_id) {
    if (session('role_level') == 'Admin') {

        $menu_name = get_menu_url_from_module_id(1875);
        $policy = \DB::connection('default')->table('crm_training_guides')->where('is_deleted', 0)->where('module_id', $module_id)->get()->first();
        if ($policy->id) {
            $url = $menu_name.'/edit/'.$policy->id;
        } else {
            $url = $menu_name.'/edit?module_id='.$module_id;
        }

        return redirect()->to($url);
    }
});

Route::any('get_document_currency/{id?}/{supplier?}', function ($id, $supplier = false) {
    if (! $supplier) {
        $currency = get_account_currency($id);
        $exchange_rate = get_exchange_rate(date('Y-m-d'), 'ZAR', $currency);
    } else {
        $currency = get_supplier_currency($id);
        $exchange_rate = get_exchange_rate(date('Y-m-d'), 'ZAR', $currency);
    }

    return response()->json(['exchange_currency' => $currency, 'exchange_rate' => $exchange_rate]);
});

Route::any('report_server_start', function () {
    $flexmonster = new \Flexmonster;
    $flexmonster->loadIndexes();
    $result = $flexmonster->dataServerRestart();
    if ($result == true) {
        return json_alert('Server restarted');
    } else {
        return json_alert('Server could not be restarted');
    }
});
// formio adhoc forms
Route::any('formio_view/{id?}', function ($id) {
    $form_json = \DB::connection('default')->table('erp_forms')->where('id', $id)->pluck('form_json')->first();
    //dd(json_decode($form_json));

    $data = [
        'id' => $id,
        'form_json' => $form_json,
        'form_data' => ['number222' => 33],

    ];

    $data['form_change_events'] = formio_get_events_from_json(json_decode($form_json));

    return view('__app.forms.module_form', $data);
});

Route::any('formio_adhoc_save', 'CustomController@formioAdhocSave');
Route::any('formio_adhoc_submit', 'CustomController@formioAdhocSubmit');

// formio module forms
Route::post('formio_calculated_values', 'CustomController@formioCalculatedValues');
Route::any('formio_builder/{id?}', function ($id) {
    if (is_superadmin()) {
        $form = \DB::connection('default')->table('erp_adhoc_forms')->where('id', $id)->get()->first();
        $available_fields = formio_get_available_fields($form->module_id, $form->form_json);
        //dd(json_encode($available_fields));

        $roles = \DB::connection('default')->table('erp_user_roles')->select('id', 'name')->get()->toArray();
        $data = [
            'id' => $form->id,
            'module_id' => $form->module_id,
            'form_json' => $form->form_json,
            'available_fields' => $available_fields,
        ];

        return view('__app.forms.module_form_builder', $data);
    }
});

Route::any('formio_builder_new/{module_id?}', function ($module_id) {
    if (check_access('1')) {
        $form = \DB::connection('default')->table('erp_forms')->where('module_id', $module_id)->get()->first();
        $available_fields = formio_get_available_fields($form->module_id, $form->form_json);
        $roles = \DB::connection('default')->table('erp_user_roles')->select('id', 'name')->get()->toArray();
        //dd(json_encode($available_fields));
        $data = [
            'module_id' => $form->module_id,
            'form_json' => $form->form_json,
            'roles' => $roles,
            'available_fields' => $available_fields,
        ];

        return view('__app.forms.module_form_builder', $data);
    }
});

Route::any('formio_builder_ajax_fields/{id?}', function ($id) {
    // aa('formio_builder_ajax_fields');
    //aa(request()->all());
    $form = \DB::connection('default')->table('erp_forms')->where('id', $id)->get()->first();
    $available_fields = formio_get_available_fields($form->module_id, request()->form_json);

    return response()->json(['components' => $available_fields, 'component_order' => array_keys($available_fields)]);
});

Route::any('formio_save', 'CustomController@formioSave');
Route::any('formio_submit_file/{field_id?}', 'CustomController@formioSubmitFile');
Route::any('formio_select_options/{field_id?}/{row_val?}', 'CustomController@formioSelectOptions');
Route::any('syncfusion_select_options/{field_id?}/{row_val?}', 'CustomController@syncfusionSelectOptions');

Route::any('dashboard_reports/{module_id?}', function ($module_id) {
    $companies_datasource = \DB::connection('system')->table('erp_instances')->select('id', 'name')->get()->toArray();
    $data = [
        'companies_datasource' => $companies_datasource,
        'module_id' => $module_id,
    ];

    return view('__app.dashboard.module_reports', $data);
});

Route::any('process_reports_tabs/{report_id?}/{company_id?}', function ($report_id, $company_id) {
    $report = \DB::connection('system')->table('erp_reports')->where('id', $report_id)->get()->first();

    $instances = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $company_id)->get();
    $report_list = [];
    foreach ($instances as $instance) {
        $exists = true;
        if ($instance->id != 1) {
            $exists = \DB::connection($instance->db_connection)->table('erp_reports')->where('main_instance_id', $report_id)->count();
        }
        if ($exists) {
            $company_id = $instance->id;
            $report_name = $instance->name;
            $report_id = $report->id;
            $report_list[] = ['header' => (object) ['text' => $report_name],
                'content' => '<div class="p-2"><div class="e-btn-group d-flex k-button-group mb-2 ml-2" role="group">
            <button title="Refresh Data" onClick="refreshReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_refresh k-button"><span  class="e-btn-icon fa fa-sync-alt"></span></button>
            <button title="Edit Record" onClick="editReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_edit k-button" ><span  class="e-btn-icon fa fa-edit"></span></button>
            <button title="Flexmonster" onClick="openReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_flexmonster report_refresh k-button" ><span  class="e-btn-icon fa fa-cogs"></span></button>
            <button title="Query" onClick="editReportSQL('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_sql k-button"><span  class="e-btn-icon fa fa fa-database"></span></button>
            <button title="Print" onClick="printReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_print k-button" ><span  class="e-btn-icon fas fa-print"></span></button>
            </div>
            <div id="report_content'.$report_id.$company_id.'" class="report_content p-2" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'">
            </div></div>', ];
        }
    }

    return response()->json($report_list);
});

Route::any('dashboard_reports_tabs/{company_id?}/{module_id?}', function ($company_id, $module_id) {
    $db_conn = \DB::connection('system')->table('erp_instances')->where('id', $id)->pluck('db_connection')->first();
    $reports = \DB::connection($db_conn)->table('erp_reports')->select('id', 'name', 'main_instance_id')->where('module_id', $module_id)->orderBy('name')->get();
    $report_list = [];
    foreach ($reports as $report) {
        $report_name = $report->name;
        $report_id = $report->id;
        $report_list[] = ['header' => (object) ['text' => $report_name],
            'content' => '<div class="p-2"><div class="e-btn-group d-flex k-button-group mb-2 ml-2" role="group">
        <button title="Refresh Data" onClick="refreshReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_refresh k-button"><span  class="e-btn-icon fa fa-sync-alt"></span></button>
        <button title="Edit Record" onClick="editReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_edit k-button" ><span  class="e-btn-icon fa fa-edit"></span></button>
        <button title="Flexmonster" onClick="openReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_flexmonster report_refresh k-button" ><span  class="e-btn-icon fa fa-cogs"></span></button>
        <button title="Query" onClick="editReportSQL('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_sql k-button"><span  class="e-btn-icon fa fa fa-database"></span></button>
        <button title="Print" onClick="printReport('.$report_id.')" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'" class="rd_print k-button" ><span  class="e-btn-icon fas fa-print"></span></button>
        </div>
        <div id="report_content'.$report_id.$company_id.'" class="report_content p-2" data-report-id="'.$report_id.'" data-company-id="'.$company_id.'">
        </div></div>', ];
    }

    return response()->json($report_list);
});

Route::any('task_reports_load/{report_id?}/{company_id?}/{refresh?}', function ($report_id, $company_id = 1, $refresh = 0) {
    $instance = \DB::connection('system')->table('erp_instances')->where('id', $company_id)->get()->first();

    $report = \DB::connection($instance->db_connection)->table('erp_reports')->where('id', $report_id)->get()->first();

    $report_conn = $report->connection;
    if ($report_conn == 'default') {
        $report_conn = $instance->db_connection;
    }

    $invalid_query = 0;
    $report_index = $report_conn.'_'.$report->id;
    if (empty($report->sql_query)) {
        $invalid_query = 1;
        $query_error = 'Empty SQL';
    } else {
        $sql_query = str_replace(PHP_EOL, ' ', $report->sql_query);
        try {
            $sql = $sql_query.' LIMIT 1';

            $result = \DB::connection($report_conn)->select($sql);
        } catch (\Throwable $ex) {
            exception_log($ex);
            $invalid_query = 1;
            $query_error = $ex->getMessage();
        }
    }

    if ($invalid_query) {
        return json_alert('Query Error', 'query_error', ['id' => $report_id, 'html' => '']);
    }

    if (! $invalid_query && (empty($result) || (is_array($result) && count($result) == 0))) {
        return json_alert('Report query does not return any results', 'empty_error', ['id' => $report_id, 'html' => '']);
    }

    $storage_name = $company_id.'/'.$report_id.'.html';
    if ($refresh) {
        \Storage::disk('reports')->delete($storage_name);
        $result = flexmonster_export($report_id, 'html', $company_id);
    }

    $exists = \Storage::disk('reports')->exists($storage_name);
    if ($exists) {
        $html = \Storage::disk('reports')->get($storage_name);

        return json_alert('File loaded', 'success', ['id' => $report_id, 'html' => $html]);
    } else {
        return json_alert('File not ready', 'file_not_ready', ['id' => $report_id]);
    }
});

Route::any('global_search', 'CustomController@globalSearch');

Route::any('stripo_html/{id?}', function ($id) {
    if ($id == 'default') {
        $html = \Storage::disk('templates')->get(session('instance')->directory.'/notification_html.txt');
        if (! empty($html)) {

            $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
            $html = str_replace('://'.$main_instance.'/get_email_logo', '://'.session('instance')->domain_name.'/get_email_logo', $html);
            echo $html;
        } else {
            return redirect()->to('/assets/libraries/stripo/template.html');
        }
    } else {
        $stripo_html = \DB::table('crm_newsletters')->where('id', $id)->pluck('stripo_html')->first();
        if (! empty($stripo_html)) {
            $html = \Erp::decode($stripo_html);
            $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
            $html = str_replace('://'.$main_instance.'/get_email_logo', '://'.session('instance')->domain_name.'/get_email_logo', $html);
            echo $html;
        } else {
            return redirect()->to('/assets/libraries/stripo/template.html');
        }
    }
});

Route::any('stripo_css/{id?}', function ($id) {
    if ($id == 'default') {
        $css = \Storage::disk('templates')->get(session('instance')->directory.'/notification_css.txt');
        if (! empty($css)) {
            echo $css;
        } else {
            return redirect()->to('/assets/libraries/stripo/template.css');
        }
    } else {
        $stripo_css = \DB::table('crm_newsletters')->where('id', $id)->pluck('stripo_css')->first();
        if (! empty($stripo_css)) {
            $css = \Erp::decode($stripo_css);
            echo $css;
        } else {
            return redirect()->to('/assets/libraries/stripo/template.css');
        }
    }
});

Route::any('stripo/{id}', function ($id) {
    $data = [
        'pluginId' => 'f1810aea44284c319396542382898e60',
        'secretKey' => 'e978c97168134195be7001594f47d839',
        'email_id' => $id,
    ];

    return view('__app.components.pages.stripo', $data);
});
Route::any('stripo_default', function () {
    if (session('role_level') == 'Admin') {
        $html = \Storage::disk('templates')->get(session('instance')->directory.'/notification_html.txt');
        $css = \Storage::disk('templates')->get(session('instance')->directory.'/notification_css.txt');
        $data = [
            'pluginId' => 'f1810aea44284c319396542382898e60',
            'secretKey' => 'e978c97168134195be7001594f47d839',
            'html' => $html,
            'css' => $css,
        ];

        return view('__app.components.pages.stripo_default', $data);
    }
});
Route::any('stripo_save', 'CustomController@stripoSave');
Route::any('stripo_save_default', 'CustomController@stripoSaveDefault');

Route::get('airtime_form', 'CustomController@airtimeForm');
Route::post('airtime_form_post', 'CustomController@airtimeFormPost');

Route::any('make_payment', function () {
    if (! session('account_id')) {
        return Redirect::to('/')->with('message', 'Please login to make a payment.')->with('status', 'warning');
    }
    $customer = dbgetaccount(session('account_id'));
    $data['customer'] = $customer;
    if ($customer->partner_id != 1) {
        return Redirect::to('/')->with('message', 'Invalid account.')->with('status', 'warning');
    }
    if ($customer->status == 'Deleted') {
        return Redirect::to('/')->with('message', 'Account deleted.')->with('status', 'warning');
    }
    $reseller = dbgetaccount($customer->partner_id);
    $data['reseller'] = $reseller;
    $data['amount'] = 100;
    $data['payfast_subscription_enabled'] = true;
    $pending_total = \DB::table('sub_services as s')
        ->join('crm_documents as d', 's.invoice_id', '=', 'd.id')
        ->where('d.doctype', 'Order')
        ->where('s.account_id', $account->id)
        ->where('s.status', 'Pending')
        ->sum('s.price_incl');

    if (! empty($pending_total)) {
        $amount += $pending_total;
    }
    $data['menu_name'] = 'Make Payment - '.$reseller->company;
    $data['logo'] = '';
    if ($reseller->logo > '' && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo'] = settings_url().$reseller->logo;
    }

    $webform_data = [];
    $webform_data['module_id'] = 390;
    $webform_data['account_id'] = session('account_id');

    $link_data = \Erp::encode($webform_data);
    $data['debit_order_link'] = request()->root().'/webform/'.$link_data;

    return view('__app.components.pages.make_payment', $data);
});

Route::any('getgridstyles/{module_id?}', function ($module_id) {
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();

    $cell_styles = \DB::connection('default')->table('erp_grid_styles')
        ->where('module_id', $module_id)->where('whole_row', 0)->get();
    $row_styles = \DB::connection('default')->table('erp_grid_styles')
        ->where('module_id', $module_id)->where('whole_row', 1)->get();
    $master_detail = false;

    if ($module->detail_module_id > 0 && ! empty($module->detail_module_key)) {
        $master_detail = true;
        $detail_cell_styles = \DB::connection('default')->table('erp_grid_styles')
            ->where('module_id', $module->detail_module_id)->where('whole_row', 0)->get();
        $detail_row_styles = \DB::connection('default')->table('erp_grid_styles')
            ->where('module_id', $module->detail_module_id)->where('whole_row', 1)->get();
    }

    $styles = '';
    foreach ($cell_styles as $cell_style) {
        $styles .= '.ag-cell-style'.$cell_style->id.' {';
        if ($cell_style->text_bold) {
            $styles .= 'font-weight: bold;';
        }
        if ($cell_style->text_italics) {
            $styles .= 'font-style: italic;';
        }
        if ($cell_style->background_color) {
            $styles .= 'background-color: '.$cell_style->background_color.'  !important;';
        }
        if ($cell_style->text_color) {
            $styles .= 'color: '.$cell_style->text_color.'  !important;';
        }
        $styles .= '}';
    }

    foreach ($row_styles as $row_style) {
        $styles .= '.ag-row-style'.$row_style->id.' {';
        if ($row_style->text_bold) {
            $styles .= 'font-weight: bold;';
        }
        if ($row_style->text_italics) {
            $styles .= 'font-style: italic;';
        }
        if ($row_style->background_color) {
            $styles .= 'background-color: '.$row_style->background_color.'  !important;';
        }
        if ($row_style->text_color) {
            $styles .= 'color: '.$row_style->text_color.'  !important;';
        }
        $styles .= '}';
    }

    if ($master_detail) {
        foreach ($detail_cell_styles as $cell_style) {
            $styles .= '.ag-cell-style'.$cell_style->id.' {';
            if ($cell_style->text_bold) {
                $styles .= 'font-weight: bold;';
            }
            if ($cell_style->text_italics) {
                $styles .= 'font-style: italic;';
            }
            if ($cell_style->background_color) {
                $styles .= 'background-color: '.$cell_style->background_color.'  !important;';
            }
            if ($cell_style->text_color) {
                $styles .= 'color: '.$cell_style->text_color.'  !important;';
            }
            $styles .= '}';
        }

        foreach ($detail_row_styles as $row_style) {
            $styles .= '.ag-row-style'.$row_style->id.' {';
            if ($row_style->text_bold) {
                $styles .= 'font-weight: bold;';
            }
            if ($row_style->text_italics) {
                $styles .= 'font-style: italic;';
            }
            if ($row_style->background_color) {
                $styles .= 'background-color: '.$row_style->background_color.'  !important;';
            }
            if ($row_style->text_color) {
                $styles .= 'color: '.$row_style->text_color.'  !important;';
            }
            $styles .= '}';
        }
    }

    echo $styles;
});

Route::any('test_number/{network?}/{number?}', function ($network, $number) {
    if ($network == 'fixedtelkom') {
        $cdr_num = cdr_last_dialed_number_by_network('fixed telkom');
        pbx_call($cdr_num, 12, 'account', $number);
    }
    if ($network == 'mobiletelkom') {
        $cdr_num = cdr_last_dialed_number_by_network('mobile telkom');
        pbx_call($cdr_num, 12, 'account', $number);
    }
    if ($network == 'vodacom') {
        $cdr_num = cdr_last_dialed_number_by_network('mobile vodacom');
        pbx_call($cdr_num, 12, 'account', $number);
    }
    if ($network == 'mtn') {
        $cdr_num = cdr_last_dialed_number_by_network('mobile mtn');
        pbx_call($cdr_num, 12, 'account', $number);
    }
    if ($network == 'cellc') {
        $cdr_num = cdr_last_dialed_number_by_network('mobile cellc');
        pbx_call($cdr_num, 12, 'account', $number);
    }

    return json_alert('Done');
});

Route::any('user_filter/{id?}', function ($id = false) {
    if (! empty($id)) {
        \DB::table('erp_users')->where('id', session('user_id'))->update(['calendar_session_user_id' => $id]);
    } else {
        \DB::table('erp_users')->where('id', session('user_id'))->update(['calendar_session_user_id' => 0]);
    }
});

Route::any('report_reset_module_sql/{id?}', function ($id) {
    $report = \DB::connection('default')->table('erp_reports')->where('id', $id)->get()->first();
    if (! $report || ! $report->module_id) {
        return false;
    }
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $report->module_id)->get()->first();
    $sql_query = $module->db_sql;
    if (empty($module->db_sql)) {
        $sql_query = 'SELECT '.$module->db_table.'.* FROM '.$module->db_table;
    }

    $sql_where = $module->db_where;
    if (! empty($module->db_where)) {
        $sql_query .= ' '.$module->db_where;
    }

    $query_data['db_conn'] = $module->connection;
    $query_data['db_tables'] = [$module->db_table];
    $query_data['db_columns'] = [];

    $cols = get_columns_from_schema($module->db_table, null, $module->connection);
    foreach ($cols as $c) {
        if (str_contains($module->db_table, 'call_records') && $c == 'variables') {
            continue;
        }
        $query_data['db_columns'][] = $module->db_table.'.'.$c;
    }

    $query_data = serialize($query_data);
    $data = [
        'sql_query' => $sql_query,
        'sql_where' => $sql_where,
        'connection' => $module->connection,
        'query_data' => $query_data,
    ];

    \DB::connection('default')->table('erp_reports')->where('id', $id)->update($data);

    $erp_reports = new \ErpReports;
    $erp_reports->setErpConnection(session('instance')->db_connection);
    $sql = $erp_reports->reportSQL($id);

    if ($sql) {
        \DB::connection('default')->table('erp_reports')->where('id', $id)->update(['sql_query' => $sql]);
    }

    return json_alert('Done');
});

Route::any('admin_login_customer', function () {
    if (check_access('1,31')) {
        return redirect()->to('/user/loginas/12');
    }
});

Route::any('admin_login_reseller', function () {
    if (check_access('1,31')) {
        return redirect()->to('/user/loginas/1969');
    }
});

Route::any('delete_yodlee_account/{id?}', function ($id) {
    if (check_access('1,31')) {
        $y = new Yodlee;
        $user = str_replace('_', '', session('instance')->directory);
        $y->setLoginName($user);
        $result = $y->deleteAccount($id);
        if (! empty($result)) {
            $error = 'errorMessage: '.$result->errorMessage.'<br>errorCode: '.$result->errorCode.'<br>referenceCode: '.$result->referenceCode;

            return json_alert($error, 'error');
        } else {
            return json_alert('Success', 'success', ['close_dialog' => 1]);
        }
    } else {
        return json_alert('No access', 'error');
    }
});
Route::any('delete_yodlee_provider_account/{id?}', function ($id) {
    if (check_access(1)) {
        $y = new Yodlee;
        $user = str_replace('_', '', session('instance')->directory);
        $y->setLoginName($user);
        $result = $y->deleteProviderAccount($id);
        if (! empty($result)) {
            $error = 'errorMessage: '.$result->errorMessage.'<br>errorCode: '.$result->errorCode.'<br>referenceCode: '.$result->referenceCode;

            return json_alert($error, 'error');
        } else {
            return json_alert('Success', 'success', ['close_dialog' => 1]);
        }

        return json_alert('Success', 'success', ['close_dialog' => 1]);
    } else {
        return json_alert('No access', 'error');
    }
});

Route::any('gitcommit', function () {
    if (session('role_id') == 1) {
        //manual git commit
        $cmd = '/home/_admin/hourly.sh';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
});

Route::get('support_helpdesk', 'IntegrationsController@helpdesk');
/*
Route::get('helpdesk', function () {
    return redirect()->to('freescout_login');
    $data = [
        'menu_name' => 'Helpdesk',
        'iframe_url' => 'freescout_login'
    ];
    return view('__app.components.iframe', $data);
});
*/
Route::get('freescout_login', function () {
    if (empty(session('user_id'))) {
        echo 'Login required';
        exit;
    }

    $user = \DB::table('erp_users')->where('id', session('user_id'))->get()->first();
    $freescout_user = \DB::connection('freescout')->table('users')->where('email', $user->email)->get()->first();
    if (empty($freescout_user)) {
        echo 'Freescout user does not exists';
        exit;
    }

    $token = \Erp::encode(['erp_user_id' => $user->id, 'freescout_user_id' => $freescout_user->id]);

    return redirect()->to('https://freescout.turnkeyerp.io/erp_login/'.$token);
});

Route::any('validate_session', function () {
    if (empty(session('user_id'))) {
        return 'logout';
    } else {
        return 'valid';
    }
});

Route::any('run_scheduled_event/{event_id?}', function ($event_id) {
    if (! is_superadmin()) {
        return json_alert('No access', 'error');
    }

    $time_start = microtime(true);
    $workflow = \DB::table('erp_form_events')->where('id', $event_id)->get()->first();
    $module = \DB::table('erp_cruds')->where('id', $workflow->module_id)->get()->first();
    $function_name = $workflow->function_name;
    $error = false;
    try {
        $function_name();
    } catch (\Throwable $ex) {
        $error = $ex->getMessage();
    }
    $time_end = microtime(true);
    $duration = $time_end - $time_start;
    $data = ['last_run' => date('Y-m-d H:i:s')];
    if ($error) {
        $data['last_failed'] = date('Y-m-d H:i:s');
        $data['last_success'] = null;
        $data['error'] = $error;
    } else {
        $data['last_failed'] = null;
        $data['last_success'] = date('Y-m-d H:i:s');
        $data['error'] = '';
    }
    \DB::table('erp_form_events')->where('id', $event_id)->update($data);

    return json_alert('Function called');
});

Route::any('gitcommit', function () {
    if (check_access('1,31')) {
        $cmd = '/home/_admin/hourly.sh && echo "success: $?" || echo "fail: $?"';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
});

Route::any('se_ajax_connections/{customer_id}', function ($customer_id) {
    $connections = se_get_connections_select_list_ajax($customer_id);

    return response()->json($connections);
});

Route::any('ajax_field_list/{module_id}', function ($module_id) {
    $fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->pluck('field')->toArray();
    $datasource = [];
    foreach ($fields as $field) {
        $datasource[] = (object) ['text' => $field, 'value' => (string) $field];
    }

    return response()->json($datasource);
});

Route::any('ajax_table_list/{connection}', function ($connection) {
    $tables = get_tables_from_schema($connection);
    $datasource = [];
    foreach ($tables as $table) {
        $datasource[] = (object) ['text' => $table, 'value' => (string) $table];
    }

    return response()->json($datasource);
});

Route::any('ajax_extension_list/{domain_uuid}', function ($domain_uuid) {
    $exts = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->get();
    $datasource = [];
    foreach ($exts as $ext) {
        $datasource[] = (object) ['text' => $ext->extension, 'value' => (string) $ext->extension_uuid];
    }

    return response()->json($datasource);
});

Route::any('se_ajax_accounts/{connection_id}', function ($connection_id) {
    $accounts = se_get_accounts_select_list_ajax($connection_id);

    return response()->json($accounts);
});

Route::any('se_ajax_providers/{country_code}', function ($country_code) {
    $providers = se_get_providers_select_list_ajax($country_code);

    return response()->json($providers);
});

Route::get('staff_policy', function () {
    $content = get_admin_setting('staff_policy');

    $admin_settings_url = get_menu_url_from_table('erp_admin_settings');
    $html = '<div class="card"><div class="card-body">';
    if (is_superadmin()) {
        $html .= '<a style="float:right" data-target="form_modal" href="/'.$admin_settings_url.'/edit/1" class="e-btn"> Edit</a><br>';
    }
    $html .= '
    <b>'.$admin->company.'</b>';

    $html .= '<br>';
    $html .= $content;
    $html .= '</div></div>';
    echo $html;
});

Route::get('company_info', function () {
    $admin = dbgetaccount(1);
    $sidebar = '<div class="card"><div class="card-header">Company Info</div><div class="card-body">';
    if (is_superadmin()) {
        $sidebar .= '<a data-target="form_modal" href="/company_info_edit" class="e-btn"> Edit</a><br>';
    }
    $sidebar .= '
    <b>'.$admin->company.'</b>';

    $bank_details = get_payment_option('Bank Details')->payment_instructions;

    $sidebar .= '<br>';
    $sidebar .= '<b>Vat No: </b>'.$admin->vat_number.'<br><br>';
    $sidebar .= '<b>Co Reg No: </b>'.$admin->company_registration_number.'<br><br>';
    $sidebar .= '<b>Address<br></b>'.nl2br($admin->address).'<br><br>';
    $sidebar .= '<b>Bank details<br></b>'.nl2br($bank_details).'<br><br>';
    $sidebar .= '<b>Extra Info<br></b>'.nl2br($admin->company_extra_info).'';
    $sidebar .= '</div></div>';
    echo $sidebar;
});

Route::get('company_info_edit', function () {
    if (is_superadmin()) {
        $data = (array) dbgetaccount(1);

        return view('__app.button_views.company_info', $data);
    }
});
Route::post('company_info_edit', 'CustomController@companyInfoEdit');

Route::post('electricity_recovered', 'CustomController@electricityRecovered');
Route::post('ledger_rebuild', 'CustomController@ledgerRebuild');

/** STATEMENTS **/
Route::any('document_download/{file?}', function ($file = false) {
    $filename = attachments_path().$file;

    return response()->download($filename, $file);
});

Route::any('statement_download/{id?}/{complete?}', function ($id = false, $complete = false) {
    if (! $id) {
        $id = session('account_id');
    }

    $account_id = $id;
    if (! $complete) {
        $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    } else {
        $file = 'Full_Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    }

    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_pdf($account_id, $complete);

    $pdf->save($filename);

    return response()->download($filename, $file);
});

Route::any('statement_email/{id?}/{complete?}', function ($id = false, $complete = false) {
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    if ($complete == 1) {
        $data['internal_function'] = 'full_statement_email';
    } else {
        $data['internal_function'] = 'statement_email';
    }
    $result = erp_process_notification($account_id, $data);
    if ($result == 'Sent') {
        return json_alert('Statement Sent');
    } else {
        return json_alert('Send error', 'error');
    }
});

Route::any('reversal_statement_email/{id?}/{complete?}', function ($id = false, $complete = false) {
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    if ($complete == 1) {
        $data['internal_function'] = 'full_statement_email';
    } else {
        $data['internal_function'] = 'statement_email';
    }
    $data['include_statement_reversals'] = true;
    $result = erp_process_notification($account_id, $data);
    if ($result == 'Sent') {
        return json_alert('Statement Sent');
    } else {
        return json_alert('Send error', 'error');
    }
});

Route::any('statement_webview/{id?}/{is_mobile?}', function ($account_id, $is_mobile = 0) {

    if ($is_mobile == 0 && session('account_id') != 1) {
        if (session('account_id') != $account_id) {
            return false;
        }
    }
    $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    $filename = attachments_path().$file;
    $file_url = attachments_url().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_pdf($account_id);

    $pdf->save($filename);
    $show_rand_statement = (get_account_currency($account_id) != 'ZAR') ? true : false;
    $data = [
        'iframe' => 1,
        'account_id' => $account_id,
        'show_rand_statement' => $show_rand_statement,
        'file_url' => $file_url,
    ];

    return view('__app.components.statement_webview', $data);
});

Route::any('statement_pdf/{id?}', function ($id = false) {
    if (empty(session('user_id'))) {
        return false;
    }
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    $filename = attachments_path().$file;
    $file_url = attachments_url().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_pdf($account_id);

    $pdf->save($filename);
    $show_rand_statement = (get_account_currency($account_id) != 'ZAR') ? true : false;
    $data = [
        'iframe' => 1,
        'account_id' => $account_id,
        'show_rand_statement' => $show_rand_statement,
        'file_url' => $file_url,
    ];

    return view('__app.components.statement', $data);
});
Route::any('statement_pdf_reversals/{id?}', function ($id = false) {
    if (empty(session('user_id'))) {
        return false;
    }
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    $filename = attachments_path().$file;
    $file_url = attachments_url().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_pdf($account_id, 0, 0, 1);

    $pdf->save($filename);
    $show_rand_statement = (get_account_currency($account_id) != 'ZAR') ? true : false;
    $data = [
        'iframe' => 1,
        'account_id' => $account_id,
        'show_rand_statement' => $show_rand_statement,
        'file_url' => $file_url,
    ];

    return view('__app.components.statement', $data);
});

Route::any('full_statement_pdf/{id?}', function ($id = false) {
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    $filename = attachments_path().$file;
    $file_url = attachments_url().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_pdf($account_id, 1);

    $pdf->save($filename);
    $show_rand_statement = (get_account_currency($account_id) != 'ZAR') ? true : false;
    $data = [
        'account_id' => $account_id,
        'show_rand_statement' => $show_rand_statement,
        'file_url' => $file_url,
    ];

    return view('__app.components.statement', $data);
});

Route::any('statement_zar_pdf/{id?}', function ($id = false) {
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'_rands.pdf';
    $filename = attachments_path().$file;
    $file_url = attachments_url().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_zar_pdf($account_id);

    $pdf->save($filename);

    $data = [
        'account_id' => $account_id,
        'show_rand_statement' => false,
        'file_url' => $file_url,
    ];

    return view('__app.components.statement', $data);
});

/** SUPPLIER STATEMENTS **/
Route::any('supplier_statement_download/{id?}/{complete?}', function ($id = false, $complete = true) {
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    if (! $complete) {
        $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    } else {
        $file = 'Full_Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    }
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_pdf($account_id, true, true);

    $pdf->save($filename);

    return response()->download($filename, $file);
});

Route::any('supplier_statement_pdf/{id?}', function ($id = false) {
    if (! $id) {
        $id = session('account_id');
    }
    $account_id = $id;
    $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    $filename = attachments_path().$file;
    $file_url = attachments_url().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf = statement_pdf($account_id, false, true);

    $pdf->save($filename);

    $data = [
        'account_id' => $account_id,
        'file_url' => $file_url,
    ];

    return view('__app.components.statement_supplier', $data);
});

Route::any('restore_subscription/{id?}', function ($id) {
    $sub = new ErpSubs;
    $result = $sub->undoCancel($id);
    if ($result !== true) {
        return $result;
    }

    return json_alert('Cancellation request deleted.');
});

Route::any('hold_subscription/{id?}', function ($id) {
    $sub = new ErpSubs;
    $result = $sub->hold($id);
    if ($result !== true) {
        return $result;
    }

    return json_alert('Subscription will not be billed, set to on hold.');
});

Route::any('unhold_subscription/{id?}', function ($id) {
    $sub = new ErpSubs;
    $result = $sub->unhold($id);
    if ($result !== true) {
        return $result;
    }

    return json_alert('Billing on hold cancelled.');
});

Route::any('set_status/{module_id?}/{id?}/{status?}', function ($module_id, $id, $status) {
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    $conn = $module->connection;
    $table = $module->db_table;
    $cols = get_columns_from_schema($table, null, $conn);

    $row = \DB::connection($conn)->table($table)->where('id', $id)->get()->first();
    if (! in_array('id', $cols)) {
        return json_alert('No id field set', 'error');
    }

    $data = (array) $row;
    $data['status'] = $status;

    if ($module_id == 343) {
        switch_account($id, $data['status']);
    } else {
        $db = new DBEvent;
        $result = $db->setModule($module_id)->save($data);
        if (! is_array($result) || empty($result['id'])) {
            return $result;
        }

        module_log($module_id, $id, 'updated', 'status '.$data['status']);
    }

    return json_alert('Status updated.');
});

Route::any('switch_status/{module_id?}/{id?}', function ($module_id, $id) {
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    $conn = $module->connection;
    $table = $module->db_table;
    $cols = get_columns_from_schema($table, null, $conn);

    $row = \DB::connection($conn)->table($table)->where('id', $id)->get()->first();
    if (! in_array('id', $cols)) {
        return json_alert('No id field set', 'error');
    }

    $data = (array) $row;

    if ($row->status == 'Enabled') {
        $data['status'] = 'Disabled';
    }
    if ($row->status == 'Disabled') {
        $data['status'] = 'Enabled';
    }
    if ($module_id == 343) {
        switch_account($id, $data['status']);
    } else {
        $db = new DBEvent;
        $result = $db->setModule($module_id)->save($data);
        if (! is_array($result) || empty($result['id'])) {
            return $result;
        }

        module_log($module_id, $id, 'updated', 'status '.$data['status']);
    }

    return json_alert('Status updated.');
});

Route::any('switch_account/{id?}', function ($id) {
    if (session('role_level') == 'Admin' || parent_of($id)) {
        $account = dbgetaccount($id);

        if ($account->type == 'customer' || $account->type == 'reseller_user') {
            if ($account->status == 'Disabled') {
                $billing_on_hold = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('billing_on_hold', 1)->where('account_id', $id)->count();
                if ($billing_on_hold) {
                    return json_alert('Account cannot be enabled when billing is on hold.', 'warning');
                }
            }
        }

        if ($account->status == 'Deleted') {
            return json_alert('Account deleted.', 'error');
        }

        if ($account->status == 'Disabled') { //} || $account->status == 'Disabled by Reseller') {
            switch_account($id, 'Enabled', true);

            return json_alert('Account enabled.');
        } elseif ($account->status == 'Enabled') {
            $result = switch_account($id, 'Disabled', true);
            if ($result) {
                return json_alert('Account disabled.');
            }
        }
    }
});
Route::any('switch_supplier/{id?}', function ($id) {
    if (session('role_level') == 'Admin') {
        $supplier = \DB::connection('default')->table('crm_suppliers')->where('id', $id)->get()->first();
        if ($supplier->status == 'Deleted') {
            return json_alert('Supplier is deleted.', 'error');
        }

        if ($supplier->status == 'Disabled') {
            \DB::connection('default')->table('crm_suppliers')->where('id', $id)->update(['status' => 'Enabled']);

            module_log(78, $account_id, 'updated', 'supplier status enabled');

            return json_alert('Supplier enabled.');
        } elseif ($supplier->status == 'Enabled') {
            \DB::connection('default')->table('crm_suppliers')->where('id', $id)->update(['status' => 'Disabled']);
            module_log(78, $account_id, 'updated', 'supplier status disabled');

            return json_alert('Supplier disabled.');
        }
    }
});

Route::any('tree_grid_sample', function () {
    //  /versaflo/erp.versaflow.io/erp/resources/views/__app/grids/tree_grid_sample.blade.php
    return view('__app.grids.tree_grid_sample');

});

Route::any('send_user_password/{account_id?}/{user_id?}', function ($account_id, $user_id = false) {
    if (session('role_level') == 'Admin') {

        if ($user_id) {
            $user = \DB::table('erp_users')->where('account_id', $account_id)->where('id', $user_id)->get()->first();
        } else {
            $user = \DB::table('erp_users')->where('account_id', $account_id)->get()->first();
        }
        /////// SEND NEW LOGIN DETAILS

        $pass = generate_strong_password();
        $hashed_password = \Hash::make($pass);
        \DB::table('erp_users')->where('id', $user->id)->update(['password' => $hashed_password]);
        $user_email = $user->email;
        $account = dbgetaccount($user->account_id);
        if ($account->partner_id == 1) {
            $portal = 'http://'.$_SERVER['HTTP_HOST'];
        } else {
            $portal = 'http://'.session('instance')->alias;
        }
        $function_variables = get_defined_vars();
        $data['internal_function'] = 'create_account_settings';

        $data['username'] = $user->username;

        $data['login_url'] = $portal;

        $data['password'] = $pass;
        if ($account->partner_id == 1) {
            $data['portal_name'] = 'Cloud Telecoms';
        } else {
            $reseller = dbgetaccount($account->partner_id);
            $data['portal_name'] = $reseller->company;
        }

        erp_process_notification($user->account_id, $data, $function_variables);
        \DB::table('erp_user_sessions')->where('user_id', $id)->delete();

        return json_alert('Done');
    }
});

Route::any('restore_account/{id?}', function ($id) {
    if (is_superadmin() || parent_of($id)) {
        $account_id = $id;
        \DB::table('crm_accounts')->where('id', $account_id)->update(['bank_allocate_airtime' => 0]);
        $cancelled = \DB::table('crm_accounts')->where('id', $account_id)->where('status', '!=', 'Deleted')->where('account_status', 'Cancelled')->count();
        if ($cancelled) {
            \DB::table('crm_accounts')->where('id', $account_id)->update(['cancelled' => 0, 'cancel_approved' => 0, 'account_status' => \DB::raw('status'), 'cancel_date' => null]);
            $account = dbgetaccount($id);
            if ($account->partner_id == 1) {
                $data['internal_function'] = 'account_status_restored';

                erp_process_notification($account->id, $data);
            }

            return json_alert('Cancellation stopped.');
        }

        $account = \DB::table('crm_accounts')->where('id', $account_id)->where('status', 'Deleted')->get()->first();

        if (empty($account)) {
            return json_alert('Account active.', 'error');
        }

        $deleted_at = \DB::table('crm_accounts')->where('id', $account_id)->pluck('deleted_at')->first();
        \DB::table('crm_accounts')->where('id', $account_id)->update(['cancel_approved' => 0, 'is_deleted' => 0, 'status' => 'Enabled', 'deleted_at' => null]);
        \DB::table('erp_users')->where('account_id', $account_id)->update(['active' => 1]);

        $pass = generate_strong_password();
        $password = \Hash::make($pass);
        \DB::table('erp_users')->where('account_id', $account_id)->update(['active' => 1, 'password' => $password]);
        \DB::table('erp_users')->where('account_id', $account_id)->get()->first();

        /////// SEND LOGIN DETAILS
        try {
            if ($account->notification_type == 'email') {
                $data['username'] = $user->username;
                $data['password'] = $pass;
                $data['login_url'] = get_whitelabel_domain($account->partner_id);

                $reseller = dbgetaccount($account->partner_id);
                $data['portal_name'] = $reseller->company;

                $data['internal_function'] = 'create_account_settings';
                erp_process_notification($account->id, $data);
            }

            if ($account->notification_type == 'sms') {
                queue_sms(12, $account->phone, 'Register success. '.url('/').'.User: '.$user->username.', Pass: '.$pass);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }

        if ($account->type == 'reseller') {
            \DB::table('crm_accounts')->where('deleted_at', 'like', date('Y-m-d', strtotime($deleted_at)).'%')->where('partner_id', $account_id)->update(['cancel_approved' => 0, 'is_deleted' => 0, 'status' => 'Enabled', 'deleted_at' => null]);
            setup_new_pricelist($account_id);

            validate_partner_pricelists($account_id);
        }

        $partner_id = \DB::table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();
        if ($partner_id == 1) {
            restore_account_debt($account_id);
            $account = dbgetaccount($account_id);
            if ($account->balance > 0) {
                $commit_data = [
                    'account_id' => $account_id,
                    'commitment_date' => date('Y-m-d', strtotime('+1 week')),
                    'created_at' => date('Y-m-d H:i:s'),
                    'notes' => 'account restored',
                    'created_by_user_id' => session('user_id'),
                ];
                \DB::table('crm_commitment_dates')->insert($commit_data);
                $commitment_debtor_status_id = 6;
                \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => $commitment_debtor_status_id]);
            }
        }

        if ($account->partner_id == 1) {
            $data['internal_function'] = 'account_status_restored';

            erp_process_notification($account->id, $data);
        }

        return json_alert('Account restored.');
    }
});

Route::any('restore_supplier/{id?}', function ($id) {
    if (check_access('1,31')) {
        \DB::connection('default')->table('crm_suppliers')->where('id', $id)->update(['status' => 'Enabled']);

        return json_alert('Supplier restored.');
    }
});

Route::post('supplier_recon', 'CustomController@supplierRecon');

Route::get('bulkemailprogress', function () {
    $percentage = \Storage::disk('local')->get('bulkemailprogress');
    echo currency($percentage);
});

Route::middleware('auth')->group(function () {
    Route::get('logs', 'LogController@index');
});

Route::any('systemchangespopup/{id?}', function ($account_id) {
    $json_data = [];
    $customer = dbgetaccount($account_id);
    $subject = 'Subject';

    $reseller = dbgetaccount($customer->partner_id);
    if (! empty($reseller->popup_message_id)) {
        if ($reseller->id == 1) {
            $data['partner_company'] = 'Cloud Telecoms';
            $data['parent_company'] = 'Cloud Telecoms';
            $data['partner_email'] = 'no-reply@telecloud.co.za';
        } else {
            $data['partner_company'] = $reseller->company;
            $data['parent_company'] = $reseller->company;
            $data['partner_email'] = $reseller->email;
        }
        $data['customer'] = $customer;

        $notification_id = $reseller->popup_message_id;

        $newsletter = \DB::connection('default')->table('crm_email_manager')->where('id', $notification_id)->get()->first();
        $subject = $newsletter->name;

        $data['html'] = get_email_html($customer->id, $reseller->id, $data, $newsletter);

        $data['css'] = '';
        $template_file = '_emails.gjs';

        $json_data['msg'] = view($template_file, $data)->render();

        echo $json_data['msg'];
    }
});

Route::get('getemailarticle/{id?}/{id2?}', function ($account_id, $article_id = null) {});

Route::get('paynowlink', function () {
    $url = generate_paynow_link(session('account_id'));

    return Redirect::to($url);
});

Route::get('getemailtemplate/{account_id?}/{type?}/{notification_id?}', function ($account_id, $type, $notification_id = 0) {

    $notification_id = str_replace('newsletter', '', $notification_id);
    $notification_id = str_replace('notification', '', $notification_id);
    $notification_id = str_replace('faq', '', $notification_id);
    $notification_id = (int) $notification_id;
    $json_data = [];
    $customer = dbgetaccount($account_id);

    $json_data['attachment'] = '';
    $subject = 'Subject';

    $reseller = dbgetaccount($customer->partner_id);

    if ($reseller->id == 1) {
        $data['partner_company'] = 'Cloud Telecoms';
        $data['parent_company'] = 'Cloud Telecoms';
        $data['partner_email'] = 'no-reply@telecloud.co.za';
    } else {
        $data['partner_company'] = $reseller->company;
        $data['parent_company'] = $reseller->company;
        $data['partner_email'] = $reseller->email;
    }
    $data['customer'] = $customer;
    $data['paynow_button'] = '';
    $paynow_link = generate_paynow_link($customer->id);
    if ($reseller->id == 1 && is_main_instance()) {
        if ($data['show_debit_order_link']) {
            $data['paynow_button'] = '<br>Debit order required, click the link to submit your debit order and complete your order.<br> '.$data['webform_link'];
        } else {
            $data['paynow_button'] = generate_paynow_button($account_id, 100);
        }
    }
    if (empty($notification_id)) {

        $data['msg'] = erp_email_blend($data['message'], $data);

        $view_data['html'] = get_email_html($customer->id, $reseller->id, $data);

        $view_data['css'] = '';
        $template_file = '_emails.gjs';
        $json_data['msg'] = view($template_file, $view_data)->render();
    } elseif ($type == 'faq') {
        if (is_superadmin()) {
            $faq = \DB::connection('default')->table('hd_customer_faqs')->where('id', $notification_id)->get()->first();
        } else {
            $faq = \DB::connection('default')->table('hd_customer_faqs')->where('internal', 0)->where('id', $notification_id)->get()->first();
        }
        $subject = $faq->name;
        $data['msg'] = $faq->content;

        $view_data['html'] = get_email_html($customer->id, $reseller->id, $data);

        $view_data['css'] = '';
        $template_file = '_emails.gjs';
        $json_data['msg'] = view($template_file, $view_data)->render();

    } elseif ($type == 'notification') {
        $newsletter = \DB::connection('default')->table('crm_email_manager')->where('id', $notification_id)->get()->first();

        $data['html'] = get_email_html($account_id, $reseller->id, $data, $newsletter);
        if (empty($json_data['msg'])) {
            $subject = $newsletter->name;
            // webform link
            if ($newsletter->webform_module_id > 0) {
                $webform_data = [];
                $webform_data['module_id'] = $newsletter->webform_module_id;
                $webform_data['account_id'] = $account_id;
                if (! empty($data['record_id'])) {
                    $webform_data['id'] = $data['record_id'];
                }
                if (! empty($data['subscription_id'])) {
                    $webform_data['subscription_id'] = $data['subscription_id'];
                }
                if ($newsletter->internal_function == 'debit_order_contract') {
                    $webform_data['is_contract'] = 1;
                }

                $link_data = \Erp::encode($webform_data);
                $link_name = \DB::connection('default')->table('erp_cruds')->where('id', $newsletter->webform_module_id)->pluck('name')->first();
                if ($newsletter->webform_module_id == 390) {
                    $link_name = 'Service Contract';
                }
                $data['webform_link'] = '<a href="'.request()->root().'/webform/'.$link_data.'" >'.$link_name.'</a>';
            }

            $data['msg'] = erp_email_blend($newsletter->message, $data);
            $data['html'] = get_email_html($account_id, $reseller->id, $data, $newsletter);

            $data['css'] = '';
            $template_file = '_emails.gjs';

            $json_data['msg'] = view($template_file, $data)->render();
        }

        if (session('instance')->id == 1) {

            if ($newsletter->id == 590) {
                $json_data['attachment'] = 'Available Phone Numbers.xlsx';
            }

        }

        if (! empty($newsletter->attach_statement)) {
            $pdf = statement_pdf($account_id);
            $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
            $filename = attachments_path().$file;
            if (file_exists($filename)) {
                unlink($filename);
            }
            $pdf->save($filename);
            $json_data['attachments'][] = $file;
        }

        if (! empty($newsletter->attach_full_statement)) {
            $pdf = statement_pdf($account_id, true);
            $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
            $filename = attachments_path().$file;
            if (file_exists($filename)) {
                unlink($filename);
            }
            $pdf->save($filename);
            $json_data['attachments'][] = $file;
        }

        if (session('instance')->id != 11 && ! empty($newsletter->attach_letter_of_demand)) {
            $pdf = collectionspdf($account_id, $newsletter->id);
            $name = ucfirst(str_replace(' ', '_', $newsletter->name));
            $file = $name.'_'.$account_id.'_'.date('Y_m_d').'.pdf';
            $filename = attachments_path().$file;

            if (file_exists($filename)) {
                unlink($filename);
            }
            $pdf->save($filename);
            $json_data['attachments'][] = $file;
        }
        if (! empty($newsletter->attach_cancellation_letter)) {
            $pdf = cancellationpdf($account_id);
            $file = 'Cancellation_letter_'.$account_id.'_'.date('Y_m_d').'.pdf';
            $filename = attachments_path().$file;
            if (file_exists($filename)) {
                unlink($filename);
            }

            $pdf->save($filename);

            $json_data['attachments'][] = $file;
        }

        if ($json_data['attachment']) {
            $json_data['attachments'][] = $json_data['attachment'];
        }
        if (! empty($json_data['attachments']) && count($json_data['attachments']) > 0) {
            $json_data['attachment'] = implode(',', $json_data['attachments']);
        }

    } elseif ($type == 'newsletter') {
        $newsletter = \DB::connection('default')->table('crm_newsletters')->where('id', $notification_id)->get()->first();

        if (empty($json_data['msg'])) {
            $subject = $newsletter->name;
            // webform link
            if ($newsletter->webform_module_id > 0) {
                $webform_data = [];
                $webform_data['module_id'] = $newsletter->webform_module_id;
                $webform_data['account_id'] = $account_id;
                if (! empty($data['record_id'])) {
                    $webform_data['id'] = $data['record_id'];
                }
                if (! empty($data['subscription_id'])) {
                    $webform_data['subscription_id'] = $data['subscription_id'];
                }

                if ($newsletter->internal_function == 'debit_order_contract') {
                    $webform_data['is_contract'] = 1;
                }
                $link_data = \Erp::encode($webform_data);
                $link_name = \DB::connection('default')->table('erp_cruds')->where('id', $newsletter->webform_module_id)->pluck('name')->first();
                if ($newsletter->webform_module_id == 390) {
                    $link_name = 'Service Contract';
                }
                $data['webform_link'] = '<a href="'.request()->root().'/webform/'.$link_data.'" >'.$link_name.'</a>';
            }

            $data['msg'] = erp_email_blend($newsletter->message, $data);

            // $data['html'] = \Erp::decode($newsletter->stripo_html);

            $data['html'] = $newsletter->email_html;
            $data['html'] = erp_email_blend($data['html'], $data);
            $data['css'] = \Erp::decode($newsletter->stripo_css);
            $template_file = '_emails.gjs';

            $json_data['msg'] = view($template_file, $data)->render();
        }

        if (! empty($newsletter->attachment_file)) {
            if (file_exists(uploads_path(768).$newsletter->attachment_file)) {
                if (file_exists(attachments_path().$newsletter->attachment_file)) {
                    unlink(attachments_path().$newsletter->attachment_file);
                }
                copy(uploads_path(768).$newsletter->attachment_file, attachments_path().$newsletter->attachment_file);
                $json_data['attachment'] = $newsletter->attachment_file;
            }
        }
    }

    $json_data['subject'] = $subject;

    echo json_encode($json_data);
});

/// Custom Views

Route::get('axxess_map_provision', function () {
    return view('__app.components.pages.axxess_map_provision', ['menu_name' => 'Coverage Maps']);
});
Route::get('axxess_map_mtn5g_provision', function () {
    return view('__app.components.pages.axxess_map_mtn5g_provision', ['menu_name' => 'MTN 5G Coverage Map']);
});
Route::get('axxess_map', function () {
    return view('__app.components.pages.axxess_map', ['menu_name' => 'Coverage Maps']);
});

Route::get('axxess_map_mtn', function () {
    return view('__app.components.pages.axxess_map_mtn_lte', ['menu_name' => 'Coverage Maps']);
});

Route::get('axxess_map_telkom', function () {
    return view('__app.components.pages.axxess_map_telkom_lte', ['menu_name' => 'Coverage Maps']);
});

Route::get('coverage_maps', function () {
    return view('__app.components.pages.coverage_maps', ['menu_name' => 'Coverage Maps']);
});
Route::get('coverage_lte_map', function () {
    return view('__app.components.pages.coverage_lte_map', ['menu_name' => 'Coverage LTE']);
});
Route::get('coverage_fibre_map', function () {
    return view('__app.components.pages.coverage_fibre_map', ['menu_name' => 'Coverage Fibre']);
});

/// header
// product search pricelist view
Route::any('product_search_view/{product_id}', function ($product_id) {
    $list_item_id = \DB::connection('default')->table('crm_pricelist_items')->where('pricelist_id', 1)->where('product_id', $product_id)->pluck('id')->first();
    if (empty($list_item_id)) {
        return json_alert('No Pricing for product found', 'error');
    }

    return redirect()->to('/pricelist_manager/view/'.$list_item_id);
});

/// Transaction form

Route::any('form_reseller_users/{partner_id?}', function ($partner_id) {
    $accounts = \DB::connection('default')->table('crm_accounts')
        ->select('id', 'company')
        ->where('type', 'reseller_user')
        ->where('partner_id', $partner_id)
        ->where('status', '!=', 'Deleted')
        ->orderBy('company')
        ->get();

    return response()->json($accounts);
});

Route::any('form_accounts/{module_id?}', function ($module_id) {

    if ($module_id == 1917) {
        $accounts = \DB::table('crm_accounts')
            ->select('id', 'company', 'type', 'payment_method', 'currency')
            ->where('partner_id', 1)
            ->where('status', '!=', 'Deleted')
            ->where('id', '!=', 1)
            ->where('type', 'lead')
            ->orderBy('type')
            ->orderBy('company')
            ->get();
    } else {
        $accounts = \DB::table('crm_accounts')
            ->select('id', 'company', 'type', 'payment_method', 'currency')
            ->where('partner_id', 1)
            ->where('status', '!=', 'Deleted')
            ->where('id', '!=', 1)
            ->where('type', '!=', 'lead')
            ->orderBy('type')
            ->orderBy('company')
            ->get();
    }

    return response()->json($accounts);
});
Route::any('form_products/{account_id?}/{type?}', function ($account_id, $type) {
    $product_list = get_transaction_products($account_id, $type, 0);

    return response()->json($product_list);
});

Route::any('supplier_products/{account_id?}/{type?}', function ($account_id, $type) {
    $product_list = get_transaction_products($account_id, $type, 1);

    return response()->json($product_list);
});

Route::post('form_products_update', 'CustomController@formProductsUpdate');

Route::any('delete_grid_config/{config_id}', function ($config_id) {
    $grid_view = \DB::table('erp_grid_views')->where('id', $config_id)->get()->first();
    if ($grid_view->global_default) {
        return json_alert('Default layout cannot be deleted', 'warning');
    }
    $db = new DBEvent;
    $result = $db->setTable('erp_grid_views')->deleteRecord(['id' => $config_id]);
    $default_id = \DB::table('erp_grid_views')->where('global_default', 1)->where('module_id', $grid_view->module_id)->pluck('id')->first();
    \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $config_id)->update(['is_deleted' => 1]);
    if ($default_id) {
        return json_alert('Deleted', 'success', ['default_id' => $default_id]);
    } else {
        return json_alert('Deleted');
    }
});

Route::any('delete_module_file/{args?}', function ($args) {
    $args = \Erp::decode($args);
    $module = \DB::table('erp_cruds')->where('id', $args['module_id'])->get()->first();
    \DB::connection($module->connection)->table($args['table'])->where($module->db_key, $args['id'])->update([$args['field'] => '']);
});

Route::get('password_confirm_account/{route?}/{action?}/{account_id?}', function ($route, $action, $account_id) {
    $data = [
        'menu_route' => $route,
        'action' => $action,
        'account_id' => $account_id,
    ];

    return view('__app.components.pages.validate_password', $data);
});

Route::get('password_confirm_subscription/{route?}/{action?}/{subscription_id?}', function ($route, $action, $subscription_id) {
    $data = [
        'menu_route' => $route,
        'action' => $action,
        'subscription_id' => $subscription_id,
    ];

    return view('__app.components.pages.validate_password', $data);
});

Route::get('download_import_file_sample/{module_id}', function ($module_id) {
    $file_name = generate_import_sample($module_id);

    $file_path = attachments_path().$file_name;

    return response()->download($file_path, $file_name);
});

Route::post('cdr_archive_table', 'CustomController@setCDRArchive');
Route::post('process_billing', 'CustomController@processBilling');
Route::post('save_grid_config', 'CustomController@saveGridView');

Route::get('module_field_toggle_totals/{status?}/{field_id?}', function ($status, $field_id) {
    \DB::table('erp_module_fields')->where('id', $field_id)->update(['pinned_row_total' => $status]);

    $module_id = \DB::table('erp_module_fields')->where('id', $field_id)->pluck('module_id')->first();
    Cache::forget(session('instance')->id.'module_fields'.$module_id);

    return json_alert('Done');
});

Route::get('delete_test_account', function () {
    if (is_main_instance() && check_access('7,9')) {
        $account_id = \DB::table('crm_accounts')->where('company', 'apitest')->pluck('id')->first();
        if ($account_id) {
            delete_account($account_id);
            \DB::table('sub_services')->where('account_id', $account_id)->delete();
            \DB::table('crm_accounts')->where('company', 'apitest')->delete();
        }
    }

    return json_alert('done');
});

Route::get('pdfpath', function () {
    $r = base_path('vendor/dompdf/dompdf/');
});
