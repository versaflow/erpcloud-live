<?php

use App\Http\Controllers\IntegrationsController;
use Illuminate\Support\Facades\Route;

Route::post('import_facebook_leads', function () {
    if (session('role_level') != 'Admin') {
        return false;
    }
    $form_id = request('form_id');
    if (empty($form_id)) {
        return json_alert('Form Id required', 'warning');
    }
    $result = facebook_import_leads_from_form_id($form_id);

    return json_alert($result['message'], $result['status']);
});

Route::get('ctxphone_setup/{extension_id?}', function ($extension_uuid) {
    $ext = \DB::connection('pbx')->table('v_extensions')->where('extension_uuid', $extension_uuid)->get()->first();
    $user = (object) [
        //  User Name
        'User' => $ext->extension,
        //  Password
        'Pass' => $ext->password,
        //  Auth Realm
        'Realm' => $ext->accountcode,
        // Display Name
        'Display' => $ext->extension,
        // WebSocket URL
        'WSServer' => 'wss://pbx.cloudtools.co.za:7443',
    ];
    $user_encoded = \Erp::encode($user);
    $url = url('/ctxphone?user='.$user_encoded);

    return redirect()->to($url);
});
Route::get('supplier_cdr_import', function () {
    return view('__app.button_views.cdr_supplier_import');
});

Route::post('supplier_cdr_import', function () {
    $file = request()->file('import');
    $gateway = request('gateway');

    if (empty($gateway)) {
        return json_alert('Gateway required', 'warning');
    }
    $gateway = strtoupper($gateway);

    if (empty($file)) {
        return json_alert('File required', 'warning');
    }

    $file = request()->file('import');
    $file_extension = $file->getClientOriginalExtension();
    $destinationPath = storage_path('cdr_imports');
    $filename = $gateway.date('Y-m').'.'.$file_extension;
    $import_file = $destinationPath.'/'.$filename;
    $uploadSuccess = $file->move($destinationPath, $filename);

    $gateway_copy_table = 'call_records_outbound_supplier';

    $gateway_compare_table = 'supplier_cdr';

    // copy the cdr to a new table for import update
    if (\Schema::connection('pbx_cdr')->hasTable($gateway_copy_table)) {
        \Schema::connection('pbx_cdr')->dropIfExists($gateway_copy_table);
    }
    $new_table = $gateway_copy_table;
    if (! empty(request('currentmonth'))) {
        $table = 'call_records_outbound';
    } else {
        $table = 'call_records_outbound_lastmonth';
    }
    // $table = 'call_records_outbound_2024may';

    schema_clone_db_table($new_table, $table, 'pbx_cdr');

    set_time_limit(900);
    if ($gateway == 'BITCO') {
        \DB::connection('pbx_cdr')->table($gateway_compare_table)->where('gateway', $gateway)->where('call_date', 'LIKE', date('Y-m').'%')->delete();
        \DB::connection('pbx_cdr')->table($gateway_compare_table)->where('gateway', $gateway)->where('call_date', 'LIKE', date('Y-m', strtotime('-1 month')).'%')->delete();
    } elseif (! empty(request('currentmonth'))) {
        \DB::connection('pbx_cdr')->table($gateway_compare_table)->where('gateway', $gateway)->where('call_date', 'LIKE', date('Y-m').'%')->delete();
    } else {
        \DB::connection('pbx_cdr')->table($gateway_compare_table)->where('gateway', $gateway)->where('call_date', 'LIKE', date('Y-m', strtotime('-1 month')).'%')->delete();
    }
    //\DB::connection('pbx_cdr')->table('vox_cdr')->truncate();
    //return false;

    $csv = (new Rap2hpoutre\FastExcel\FastExcel)->import($import_file);
    foreach ($csv as $line) {
        if ($gateway == 'VOX') {
            $cost = trim(str_replace('R', '', $line['BillCost']));
            if (empty($cost)) {
                $cost = 0;
            }

            $data = [
                'call_date' => $line['CallConnected'],
                'caller_id_number' => trim($line['CallSource']),
                'callee_id_number' => trim($line['DialledNumber']),
                'supplier_duration' => $line['Seconds'],
                'supplier_rate' => 0,
                'supplier_destination' => $line['Description'],
                'supplier_cost' => $cost,
                'record_exists' => 0,
                'cdr_duration' => 0,
                'cdr_cost' => 0,
                'cost_match' => 0,
                'duration_match' => 0,
                'cdr_rate' => 0,
                'gateway' => $gateway,
            ];
        }

        if ($gateway == 'BVS') {
            $cost = trim(str_replace('R', '', $line['Cost']));
            if (empty($cost)) {
                $cost = 0;
            }

            $data = [
                'call_date' => $line['Setup Time'],
                'callee_id_number' => $line['CLD'],
                'caller_id_number' => $line['CLI'],
                'supplier_duration' => $line['Duration, sec'],
                'supplier_rate' => $line['Minute Rate'],
                'supplier_destination' => $line['Description'],
                'supplier_cost' => $cost,
                'record_exists' => 0,
                'cdr_duration' => 0,
                'cdr_cost' => 0,
                'cost_match' => 0,
                'duration_match' => 0,
                'cdr_rate' => 0,
                'gateway' => $gateway,
            ];
        }

        if ($gateway == 'BITCO') {
            $cost = trim(str_replace('R', '', $line['Cost']));
            if (empty($cost)) {
                $cost = 0;
            }

            $data = [
                'call_date' => $line['Date'],
                'callee_id_number' => $line['Destination Number'],
                'caller_id_number' => str_replace('+', '', $line['Source Number']),
                'supplier_duration' => $line['Duration'],
                'supplier_rate' => 0,
                'supplier_destination' => '',
                'supplier_cost' => $cost,
                'record_exists' => 0,
                'cdr_duration' => 0,
                'cdr_cost' => 0,
                'cost_match' => 0,
                'duration_match' => 0,
                'cdr_rate' => 0,
                'gateway' => $gateway,
            ];
        }

        $insert_data[] = $data;
    }

    $nlist = collect($insert_data); // Make a collection to use the chunk method

    // it will chunk the dataset in smaller collections containing 500 values each.
    // Play with the value to get best result
    $chunks = $nlist->chunk(500);

    foreach ($chunks as $chunk) {
        \DB::connection('pbx_cdr')->table($gateway_compare_table)->insert($chunk->toArray());
    }

    // rate not set on file
    if ($gateway == 'VOX' || $gateway == 'BITCO') {
        \DB::connection('pbx_cdr')->table($gateway_compare_table)
            ->where('gateway', $gateway)
            ->where('supplier_duration', '>', 0)
            ->where('supplier_cost', '>', 0)
            ->update(['supplier_rate' => \DB::raw('(supplier_cost/supplier_duration)*60')]);
    }

    $sql = 'UPDATE '.$gateway_compare_table.'
    JOIN '.$gateway_copy_table.' ON '.$gateway_compare_table.'.caller_id_number = '.$gateway_copy_table.'.caller_id_number
    AND '.$gateway_compare_table.'.callee_id_number = '.$gateway_copy_table.'.callee_id_number
    AND ABS(TIMESTAMPDIFF(SECOND, '.$gateway_compare_table.'.call_date, '.$gateway_copy_table.'.start_time)) <= 60
    AND '.$gateway_copy_table.'.duration > 0 
    AND '.$gateway_copy_table.".gateway = '".$gateway."'
    SET ".$gateway_compare_table.'.cdr_cost = '.$gateway_copy_table.'.gateway_cost,
    '.$gateway_compare_table.'.cdr_rate = '.$gateway_copy_table.'.gateway_rate,
    '.$gateway_compare_table.'.cdr_destination = '.$gateway_copy_table.'.destination,
    '.$gateway_compare_table.'.cdr_summary_destination = '.$gateway_copy_table.'.summary_destination,
    '.$gateway_compare_table.'.cdr_duration = '.$gateway_copy_table.'.duration,
    '.$gateway_compare_table.'.checked = 1
    WHERE '.$gateway_compare_table.'.checked = 0;';
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE '.$gateway_compare_table.'
    JOIN '.$gateway_copy_table.' ON '.$gateway_compare_table.'.caller_id_number = '.$gateway_copy_table.'.caller_id_number
    AND '.$gateway_compare_table.'.callee_id_number = '.$gateway_copy_table.'.callee_id_number
    AND ABS(TIMESTAMPDIFF(SECOND, '.$gateway_compare_table.'.call_date, '.$gateway_copy_table.'.answer_time)) <= 60
    AND '.$gateway_copy_table.'.duration > 0 
    AND '.$gateway_copy_table.".gateway = '".$gateway."'
    SET ".$gateway_compare_table.'.cdr_cost = '.$gateway_copy_table.'.gateway_cost,
    '.$gateway_compare_table.'.cdr_rate = '.$gateway_copy_table.'.gateway_rate,
    '.$gateway_compare_table.'.cdr_destination = '.$gateway_copy_table.'.destination,
    '.$gateway_compare_table.'.cdr_summary_destination = '.$gateway_copy_table.'.summary_destination,
    '.$gateway_compare_table.'.cdr_duration = '.$gateway_copy_table.'.duration,
    '.$gateway_compare_table.'.checked = 1
    WHERE '.$gateway_compare_table.'.checked = 0;';
    \DB::connection('pbx_cdr')->statement($sql);

    // international numbers
    $sql = 'UPDATE '.$gateway_compare_table.'
    JOIN '.$gateway_copy_table.' ON '.$gateway_compare_table.'.caller_id_number = '.$gateway_copy_table.'.caller_id_number
    AND '.$gateway_compare_table.'.callee_id_number = SUBSTRING('.$gateway_copy_table.'.callee_id_number,3)
    AND ABS(TIMESTAMPDIFF(SECOND, '.$gateway_compare_table.'.call_date, '.$gateway_copy_table.'.start_time)) <= 60
    AND '.$gateway_copy_table.'.duration > 0 
    AND '.$gateway_copy_table.".gateway = '".$gateway."'
    SET ".$gateway_compare_table.'.cdr_cost = '.$gateway_copy_table.'.gateway_cost,
    '.$gateway_compare_table.'.cdr_rate = '.$gateway_copy_table.'.gateway_rate,
    '.$gateway_compare_table.'.cdr_destination = '.$gateway_copy_table.'.destination,
    '.$gateway_compare_table.'.cdr_summary_destination = '.$gateway_copy_table.'.summary_destination,
    '.$gateway_compare_table.'.cdr_duration = '.$gateway_copy_table.'.duration,
    '.$gateway_compare_table.'.checked = 1
    WHERE '.$gateway_compare_table.'.checked = 0;';
    //aa($sql);
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE '.$gateway_compare_table.' set difference=supplier_cost-cdr_cost';
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE '.$gateway_compare_table.' set record_exists=1 where checked=1;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE '.$gateway_compare_table.' set cost_match=1 where cdr_cost=supplier_cost;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE '.$gateway_compare_table.' set duration_match=1 where cdr_duration=supplier_duration;';
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_gnp_1 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '271%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_gnp_2 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '272%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_gnp_3 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '273%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_gnp_4 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '274%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_gnp_5 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '275%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_crdb_6 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '276%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_crdb_7 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '277%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE cdr.'.$gateway_compare_table." AS b
    JOIN porting.p_ported_numbers_crdb_8 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '278%';";
    \DB::connection('pbx_cdr')->statement($sql);

    return json_alert('Import complete', 'success');
});

Route::get('supportboard_ticket', function () {
    if (session('role_level') == 'Admin') {
        return view('__app.components.pages.supportboard_ticket');
    }
});

Route::get('pbx_registrations', function () {
    if (is_superadmin()) {
        $pbx = new FusionPBX;
        $results = $pbx->portalCmd('portal_registrations');
        $rows = json_decode($results);
        foreach ($rows as $row) {
            echo $row->sip_user.'@'.$row->sip_host.' '.$row->status.'<br><br>';
        }
    }
});

Route::get('monthly_billing_approval/{encoded_token?}', function ($encoded_token) {
    $decoded_data = \Erp::decode($encoded_token);

    if (empty(session('instance'))) {
        $hostname = $_SERVER['HTTP_HOST'];
        $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();

        $instance_dir = $instance->db_connection;
        $instance->directory = $instance_dir;

        session(['instance' => $instance]);
    }

    if ($decoded_data['instance_id'] != session('instance')->id) {
        echo 'Instance id does not match';
    }
    if ($decoded_data['token'] != session('instance')->directory.'1') {
        echo 'Instance token does not match';
    }

    if (empty($decoded_data['billing_id'])) {
        echo 'Billing ID not set';
    }

    $billing = \DB::table('acc_billing')->where('id', $decoded_data['billing_id'])->get()->first();
    // dd($billing);
    if ($billing->num_emails_success > 0) {
        echo 'Billing cannot be changed, billing emails already sent';
    }

    if (date('d') > 2 && date('d') < 20) {
        //    return 'Billing cannot be changed mid month';
    }

    if ($decoded_data['approve'] == 1) {
        \DB::table('crm_documents')->where('docdate', $billing->billing_date)->where('billing_type', 'Monthly')->where('reversal_id', 0)->whereIn('doctype', ['Quotation', 'Order'])->update(['doctype' => 'Tax Invoice']);

        if (session('instance')->id == 2) {
            //credit rentals
            $credit_rental_space_ids = \DB::table('crm_rental_spaces')->where('has_lease', 'Internal')->pluck('id')->toArray();
            $credit_rental_account_ids = \DB::table('crm_rental_leases')->whereIn('rental_space_id', $credit_rental_space_ids)->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();

            $cloudtelecoms_invoice_ids = \DB::table('crm_documents')
                ->where('reversal_id', 0)
                ->whereIn('account_id', $credit_rental_account_ids)
                ->where('docdate', $billing->billing_date)
                ->where('doctype', 'Tax Invoice')
                ->where('billing_type', 'Monthly')
                ->pluck('id')->toArray();
            foreach ($cloudtelecoms_invoice_ids as $invoice_ids) {
                create_credit_note_from_invoice($invoice_ids);
            }
        }

        \DB::table('acc_billing')->where('id', $billing->id)->update(['approved' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        \DB::table('crm_approvals')->where('row_id', $billing->id)->where('module_id', 744)->update(['processed_at' => date('Y-m-d H:i:s'), 'processed' => 1, 'processed_by' => get_user_id_default()]);
        echo $billing->id;
        email_monthly_billing($billing->id);
        verify_billing_summary($billing->id);
        process_monthly_debit_orders();
        echo 'Billing approved and emailed to customers';
    }
});

Route::get('renewal_billing_approval/{encoded_token?}', function ($encoded_token) {
    $decoded_data = \Erp::decode($encoded_token);

    if (empty(session('instance'))) {
        $hostname = $_SERVER['HTTP_HOST'];
        $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();

        $instance_dir = $instance->db_connection;
        $instance->directory = $instance_dir;

        session(['instance' => $instance]);
    }

    if ($decoded_data['instance_id'] != session('instance')->id) {
        return 'Instance id does not match';
    }
    if ($decoded_data['token'] != session('instance')->directory.'1') {
        return 'Instance token does not match';
    }

    if (empty($decoded_data['billing_id'])) {
        return 'Billing ID not set';
    }

    $billing = \DB::table('acc_billing')->where('id', $decoded_data['billing_id'])->get()->first();
    if ($billing->num_emails_success > 0) {
        return 'Billing cannot be changed, billing emails already sent';
    }
    if (date('d') > 2 && date('d') < 20) {
        //    return 'Billing cannot be changed mid month';
    }

    if (! empty($decoded_data['approve']) && $decoded_data['approve'] == 1) {
        \DB::table('crm_documents')->where('docdate', $billing->billing_date)->where('billing_type', 'Renewal')->where('reversal_id', 0)->whereIn('doctype', ['Quotation', 'Order'])->update(['doctype' => 'Tax Invoice']);

        if (session('instance')->id == 2) {
            //credit rentals
            $credit_rental_space_ids = \DB::table('crm_rental_spaces')->where('has_lease', 'Internal')->pluck('id')->toArray();
            $credit_rental_account_ids = \DB::table('crm_rental_leases')->whereIn('rental_space_id', $credit_rental_space_ids)->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();

            $cloudtelecoms_invoice_ids = \DB::table('crm_documents')
                ->where('reversal_id', 0)
                ->whereIn('account_id', $credit_rental_account_ids)
                ->where('docdate', $billing->billing_date)
                ->where('doctype', 'Tax Invoice')
                ->where('billing_type', 'Renewal')
                ->pluck('id')->toArray();
            foreach ($cloudtelecoms_invoice_ids as $invoice_ids) {
                create_credit_note_from_invoice($invoice_ids);
            }
        }

        \DB::table('acc_billing')->where('id', $billing->id)->update(['approved' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        \DB::table('crm_approvals')->where('row_id', $billing->id)->where('module_id', 744)->update(['processed_at' => date('Y-m-d H:i:s'), 'processed' => 1, 'processed_by' => get_user_id_default()]);
        email_monthly_billing($billing->id);
        verify_billing_summary($billing->id);
        process_monthly_debit_orders();

        return 'Billing approved and emailed to customers';
    }
});

Route::any('leads_kanban', function () {});

Route::any('leads_kanban_data', function () {});
Route::any('leads_kanban_update', function () {});

Route::any('layout_email/{layout_id?}', function ($layout_id) {
    export_layout($layout_id);

    return json_alert('Done');
});

Route::any('form_set_tabs_from_layout/{layout_id?}/{module_id?}/{detail_module_id?}', function ($layout_id, $module_id, $is_detail_module = 0) {
    form_set_tabs_from_layout($layout_id, $module_id, $is_detail_module);

    return json_alert('Done');
});

Route::any('layout_reset_to_default/{layout_id?}', function ($layout_id) {
    $grid_view = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();
    $default_grid_view = \DB::table('erp_grid_views')->where('global_default', 1)->where('module_id', $grid_view->module_id)->get()->first();
    if (empty($default_grid_view)) {
        return json_alert('Default layout not found', 'warning');
    }
    $data = [
        'aggrid_state' => $default_grid_view->aggrid_state,
        'detail_aggrid_state' => $default_grid_view->detail_aggrid_state,
        'aggrid_pivot_state' => $default_grid_view->aggrid_pivot_state,
        'pivot_mode' => $default_grid_view->pivot_mode,
    ];

    \DB::table('erp_grid_views')
        ->where('id', $layout_id)
        ->update($data);

    return json_alert('Done');
});

Route::any('layout_set_default/{layout_id?}', function ($layout_id) {
    $grid_view = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();
    if ($grid_view->layout_type == 'Report') {
        //  return json_alert('Reports cannot be set as default');
    }
    \DB::table('erp_grid_views')
        ->where('id', $layout_id)
        ->update(['global_default' => 1]);

    \DB::table('erp_grid_views')
        ->where('id', '!=', $layout_id)
        ->where('module_id', $grid_view->module_id)
        ->where('global_default', 1)
        ->update(['global_default' => 0]);

    $id = \DB::table('erp_grid_views')
        ->where('module_id', $grid_view->module_id)
        ->where('global_default', 1)
        ->pluck('id')->first();

    return json_alert('Done');
});

Route::any('zapier_facebook_comments', [IntegrationsController::class, 'zapierFacebookComments']);
Route::any('whatsapp_webhook', [IntegrationsController::class, 'whatsappWebhook']);
Route::any('zapier_webhooks', [IntegrationsController::class, 'zapierWebhooks']);
Route::any('zapier_instagram', [IntegrationsController::class, 'zapierInstagram']);
Route::any('zapier_zendesk', [IntegrationsController::class, 'zapierZendesk']);

Route::any('layout_tracking_enable/{layout_id?}', function ($layout_id) {
    if (session('role_level') == 'Admin') {
        $layout_type = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->pluck('layout_type')->first();
        if ($layout_type == 'Layout') {
            workboard_layout_tracking_enable($layout_id);

            return json_alert('Layout tracking enabled');
        } else {
            if (is_superadmin()) {
                $dashboard_sort_order = \DB::connection('default')->table('erp_grid_views')->max('dashboard_sort_order');
                $dashboard_sort_order++;

                $layout = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->get()->first();
                // aa($layout);
                $role = get_workspace_role_from_module_id($layout->module_id);

                if ($role && $role->id) {
                    $role_id = $role->id;
                } else {
                    $role_id = 1;
                }

                \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['show_on_dashboard' => 1, 'dashboard_sort_order' => $dashboard_sort_order, 'chart_role_id' => $role_id]);

                return json_alert('Dashboard tracking enabled');
            }
        }

        return json_alert('Layout tracking enabled');
    }
});

Route::any('layout_tracking_disable/{layout_id?}', function ($layout_id) {
    if (session('role_level') == 'Admin') {
        $layout_type = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->pluck('layout_type')->first();
        if ($layout_type == 'Layout') {
            workboard_layout_tracking_disable($layout_id);

            return json_alert('Layout tracking disabled');
        } else {
            if (is_superadmin()) {
                \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['show_on_dashboard' => 0]);

                return json_alert('Dashboard tracking disabled');
            }
        }
    }
});

Route::any('layout_convert_to_report/{layout_id?}', function ($layout_id) {
    if (session('role_level') == 'Admin') {
        $data = ['layout_type' => 'Report'];
        $layout = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();
        if (! empty($layout->aggrid_state)) {
            $layout_state = json_decode($layout->aggrid_state);
            if (! empty($layout_state->colState)) {
                foreach ($layout_state->colState as $i => $colstate) {
                    $layout_state->colState[$i]->hide = 'true';
                }
                $data['aggrid_state'] = json_encode($layout_state);
            }
        }
        \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update($data);

        return json_alert('Done');
    }
});

Route::any('layout_convert_to_layout/{layout_id?}', function ($layout_id) {
    if (session('role_level') == 'Admin') {
        \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['layout_type' => 'Layout']);

        return json_alert('Done');
    }
});

Route::any('chart_remove/{layout_id?}', function ($layout_id) {
    if (session('role_level') == 'Admin') {
        \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['chart_model' => '']);

        return json_alert('Done');
    }
});

Route::any('update_yodlee_accounts', function () {
    if (session('role_level') == 'Admin') {
        $y = new Yodlee('production');
        $user = str_replace('_', '', session('instance')->db_connection);

        $y->setLoginName($user);
        $provider_accounts = $y->getProviderAccounts();

        $accounts = $y->getAccounts();

        if (! empty($accounts) && ! empty($accounts->account)) {
            foreach ($accounts->account as $account) {
                $data = [
                    'id' => $account->id,
                    'provider_account_id' => $account->providerAccountId,
                    'account_name' => $account->accountName,
                    'account_status' => $account->accountStatus,
                    'account_number' => $account->accountNumber,
                    'provider_id' => $account->providerId,
                    'provider_name' => $account->providerName,
                    'created_date' => date('Y-m-d H:i', strtotime($account->createdDate)),
                    'additional_status' => $account->dataset[0]->additionalStatus,
                    'update_eligibility' => $account->dataset[0]->updateEligibility,
                    'last_updated' => date('Y-m-d H:i', strtotime($account->dataset[0]->lastUpdated)),
                    'last_update_attempt' => date('Y-m-d H:i', strtotime($account->dataset[0]->lastUpdateAttempt)),
                    'next_update_scheduled' => date('Y-m-d H:i', strtotime($account->dataset[0]->nextUpdateScheduled)),
                    'currency' => $account->currentBalance->currency,
                ];

                \DB::table('acc_yodlee_accounts')->updateOrInsert(['id' => $account->id], $data);
            }
        }

        return redirect()->to('yodlee_accounts');
    }
});

Route::any('pbx_tts', [IntegrationsController::class, 'pbxTextToSpeech']);

/// website contact forms
Route::any('contact_form_netstream', [IntegrationsController::class, 'contactFormNetstream']);
Route::any('contact_form_cloudtelecoms', [IntegrationsController::class, 'contactFormCloudtelecoms']);

Route::get('ticket_system', function () {
    if (session('role_level') == 'Admin') {
        $data = [
            'created_at' => date('Y-m-d H:i'),
            'username' => session('username'),
        ];
        $auth_token = Erp::encode($data);
        $url = 'https://newflex.flexerp.io/weblogin?authtoken='.$auth_token;

        return redirect()->to($url);
    }
});

Route::get('ticket_system_compose/{email?}', function ($email) {
    if (session('role_level') == 'Admin') {
        $data = [
            'created_at' => date('Y-m-d H:i'),
            'username' => session('username'),
            'from_email' => get_admin_setting('smtp_username'),
            'to_email' => $email,
        ];
        $auth_token = Erp::encode($data);
        $url = 'https://newflex.flexerp.io/weblogin?authtoken='.$auth_token;

        return redirect()->to($url);
    }
});

/// PBX START
Route::any('pbx_gateway_beforesave/{gateway_uuid?}/{volume_numbers_required?}/{token?}', function ($gateway_uuid, $volume_numbers_required, $token) {
    $token = Erp::decode($token);
    if ($token == 'cloudpbx'.date('Ymd')) {
        $result = allocate_volume_phone_numbers($gateway_uuid, $volume_numbers_required);

        $numbers_per_gateway = $volume_numbers_required;
        $volume_number_results = [];
        $gateways = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->get();
        $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->get();
        $allocated_numbers = [];
        foreach ($gateways as $gateway) {
            foreach ($volume_domains as $volume_domain) {
                $num_extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $volume_domain->domain_uuid)->count();
                $numbers_per_domain = $numbers_per_gateway * $num_extensions;
                $allocated_count = \DB::connection('pbx')->table('p_phone_numbers')
                    ->where('status', 'Enabled')
                    ->where('domain_uuid', $volume_domain->domain_uuid)
                    ->where('gateway_uuid', $gateway->gateway_uuid)->count();
                if ($allocated_count < $numbers_per_domain) {
                    throw new \ErrorException('Not enough numbers allocated to volume domains.');
                }
            }
        }
    }
});

Route::any('pbx_gateway_update/{token?}', function ($token) {
    $token = Erp::decode($token);
    if ($token == 'cloudpbx'.date('Ymd')) {
        rates_complete_set_lowest_rate();
        admin_rates_summary_set_lowest_active();
    }
});

Route::any('pbx_gateway_import/{gateway_uuid?}/{token?}', function ($gateway_uuid, $token) {
    $token = Erp::decode($token);
    if ($token == 'cloudpbx'.date('Ymd')) {
        $gateways = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->get();
        import_rates_summary_from_rates_complete($gateway_uuid);
        rates_complete_set_lowest_rate();
        admin_rates_summary_set_lowest_active();
    }
});

Route::any('pbx_gateway_restart/{gateway_uuid?}/{token?}', function ($gateway_uuid, $token) {
    $token = Erp::decode($token);
    if ($token == 'cloudpbx'.date('Ymd')) {
        $gateway = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->get()->first();
        $pbx = new FusionPBX;

        $gateway_profile = $gateway->profile;

        if ($gateway_profile == 'external') {
            $r = $pbx->portalCmd('portal_gateway_external_stop', $gateway_profile);
        } else {
            $r = $pbx->portalCmd('portal_gateway_stop', $gateway_profile);
        }

        $r = $pbx->portalCmd('portal_gateway_start');
    }
});
Route::any('pbx_gateway_status/{token?}', function ($token) {
    $token = Erp::decode($token);
    if ($token == 'cloudpbx'.date('Ymd')) {
        $pbx = new FusionPBX;

        $result = $pbx->portalCmd('portal_sofia_status_gateway');
        // RESTART GATEWAYS
        $gateways_restarted = false;
        if (! empty($result) && ! str_starts_with($result, 'SSH')) {
            $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            $json = json_encode($xml);
            $gateways = json_decode($json, true);
        }
    }
});
/// PBX END

/// FLEXMONSTER START
Route::any('flexmonster/{id}/{company_id?}', [IntegrationsController::class, 'flexmonster']);
Route::any('flexmonster_load', [IntegrationsController::class, 'flexmonsterLoad']);
Route::any('flexmonster_save_state', [IntegrationsController::class, 'flexmonsterSaveState']);
Route::any('flexmonster_export_save', [IntegrationsController::class, 'flexmonsterExportSave']);
Route::any('flexmonster_export/{report_id}/{format?}', function ($report_id, $format = 'html') {
    $report = \DB::connection('default')->table('erp_reports')->where('id', $report_id)->get()->first();
    $report_conn = $report->connection;
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
    $storage_name = session('instance')->id.'/'.$report_id.'.'.$format;
    $exists = \Storage::disk('reports')->exists($storage_name);
    if ($exists && ! $report->fds) {
        \Storage::disk('reports')->delete($storage_name);
    }
    flexmonster_export($report_id);

    return json_alert('Done', 'success', ['id' => $report_id]);
});

Route::any('flexmonster_report_html/{report_id?}/{company_id?}', function ($report_id, $company_id = 1) {
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
    $exists = \Storage::disk('reports')->exists($storage_name);
    if ($exists) {
        $html = \Storage::disk('reports')->get($storage_name);

        return json_alert('File loaded', 'success', ['id' => $report_id, 'html' => $html]);
    } else {
        return json_alert('File not ready', 'file_not_ready', ['id' => $report_id]);
    }
});
/// FLEXMONSTER END

Route::get('osticket', function () {
    if (session('role_level') == 'Admin') {
        $user = \DB::connection('default')->table('erp_users')->where('id', session('user_id'))->get()->first();
        \DB::connection('osticket')->table('ost9q_staff')->where('email', $user->email)->update(['passwd' => $user->password]);
        $url = 'https://helpdesk.flexerp.io/scp/login.php?userid='.$user->email.'&passwd='.$user->password;

        return redirect()->to($url);
    }
});

Route::get('faq', function () {
    return view('_api.faq', []);
})->name('faq');

Route::get('download_product_stocktake_file', function () {
    $file_name = 'stock.xlsx';
    $file_path = public_path('/downloads/'.session('instance')->directory.'/'.$file_name);

    $products = \DB::table('crm_products')->select('code', 'name', \DB::raw('"" as qty_on_hand'))->where('type', 'Stock')->where('status', 'Enabled')->orderBy('sort_order')->get()->toArray();
    $products = json_decode(json_encode($products), true);

    $export = new App\Exports\CollectionExport;
    $export->setData($products);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'downloads');

    return response()->download($file_path, $file_name);
});
//Route::get('kernel_list', 'CoreController@kernelList');
Route::any('mail_manager', [IntegrationsController::class, 'mailManager']);
// REPORT ROUTES START
Route::any('report_query/{id?}', [IntegrationsController::class, 'reportQuery']);
Route::post('report_query_save', [IntegrationsController::class, 'reportQuerySave']);
Route::any('report_query_date_filter', [IntegrationsController::class, 'reportQueryDateFilter']);
Route::any('report_query_reset', [IntegrationsController::class, 'reportQueryReset']);
// REPORT ROUTES ENDS

Route::get('code_edit/{function_name}', [IntegrationsController::class, 'getFunctionCode']);
Route::post('code_edit_save', [IntegrationsController::class, 'postFunctionCode']);

Route::any('flowchart/{id?}', [IntegrationsController::class, 'diagram']);

Route::get('flowchart_edit/{id?}', function ($id) {
    $diagram = \DB::table('crm_flowcharts')->where('id', $id)->get()->first();
    $data = (array) $diagram;
    $data['menu_name'] = ucwords($diagram->name).' Diagram';
    $data['edit'] = true;

    return view('__app.components.diagram', $data);
});
Route::get('flowchart_edit2/{id?}', function ($id) {
    $diagram = \DB::table('crm_flowcharts')->where('id', $id)->get()->first();
    $data = (array) $diagram;
    $data['menu_name'] = ucwords($diagram->name).' Diagram';
    $data['edit'] = true;

    return view('__app.components.diagram_xml', $data);
});
Route::post('diagram_save', [IntegrationsController::class, 'diagramSave']);

Route::get('gateway_start/{gateway_uuid?}', function ($gateway_uuid) {
    if (! is_superadmin()) {
        return json_alert('No Access', 'error');
    }

    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_gateway_start');

    return json_alert($result);
});
Route::get('gateway_stop/{gateway_uuid?}', function ($gateway_uuid) {
    if (! is_superadmin()) {
        return json_alert('No Access', 'error');
    }

    $pbx = new FusionPBX;
    $gateway_profile = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->pluck('profile')->first();
    if ($gateway_profile == 'external') {
        $pbx->portalCmd('portal_gateway_external_stop', $gateway_uuid);
    } else {
        $pbx->portalCmd('portal_gateway_stop', $gateway_uuid);
    }

    return json_alert($result);
});

Route::get('pbx_userguide', function () {
    $data = ['menu_name' => 'User Guide'];
    $feature_codes = \DB::connection('pbx')->table('p_feature_codes')->orderby('category')->orderby('code')->get();
    $data['feature_codes'] = collect($feature_codes)->groupBy('category');

    return view('__app.components.pages.pbx_userguide', $data);
});

Route::get('dbninja', function () {
    if (is_superadmin()) {
        $params = [
            'uname' => 'admin',
            'passwd' => 'Webmin786',
        ];
        $loginkey = \Erp::encode($params);
        $db_ninja_url = 'http://mysql.cloudtelecoms.co.za?loginkey='.$loginkey;

        return redirect()->to($db_ninja_url);
    }
});

Route::get('nfig', function () {});

Route::post('exportcdrbygateway', [IntegrationsController::class, 'exportCdrByGateway']);

Route::get('download_pricelist_new', function () {
    $file_name = export_pricelist(1, 'xlsx');
    //return $file_name;
    $file_path = attachments_path().$file_name;

    return response()->download($file_path, $file_name);
});

Route::get('download_wholesale_pricelist', function () {
    $file_name = export_pricelist(1);
    //  ddd($file_name);
    $file_path = attachments_path().$file_name;

    //  ddd($file_path);
    return response()->download($file_path, $file_name);
});

Route::get('download_pricelist/{id?}/{type?}', function ($id, $type) {
    $file_name = export_pricelist($id);
    // aa($file_name);
    $file_path = attachments_path().$file_name;

    // aa($file_path);
    return response()->download($file_path, $file_name);
});

Route::get('download_wholesale_rates', function () {
    $file_name = 'Ratesheet Wholesale ZAR.xlsx';
    $file_path = attachments_path().'Ratesheet Wholesale ZAR.xlsx';

    return response()->download($file_path, $file_name);
});

Route::get('download_retail_rate_complete', function () {
    $file_name = export_partner_rates(1);
    $file_path = attachments_path().$file_name;

    return response()->download($file_path, $file_name);
});

Route::get('download_retail_rates', function () {
    if (! empty(session('pbx_domain')) && session('pbx_domain') != '156.0.96.60' && (session('pbx_domain_level') === true || check_access('21'))) {
        $file_name = export_partner_rates_summary(session('pbx_ratesheet_id'));
        $file_path = attachments_path().$file_name;
    } else {
        $file_name = export_partner_rates_summary(1);
        $file_path = attachments_path().'Ratesheet Retail ZAR.xlsx';
    }

    return response()->download($file_path, $file_name);
});

Route::get('download_call_rates_zar', function () {
    $file_path = uploads_path().'/pricing_exports/Call_Rates_Popular_ZAR.xlsx';
    $file_name = 'Call_Rates_Popular_ZAR.xlsx';

    return response()->download($file_path, $file_name);
});

Route::get('download_call_rates_usd', function () {
    $file_path = uploads_path().'/pricing_exports/Call_Rates_Popular_USD.xlsx';
    $file_name = 'Call_Rates_Popular_USD.xlsx';

    return response()->download($file_path, $file_name);
});

Route::get('download_wholesale_rates_usd', function () {
    $file_name = 'Ratesheet Wholesale USD.xlsx';
    $file_path = public_path().'/attachments/telecloud/Ratesheet Wholesale USD.xlsx';

    return response()->download($file_path, $file_name);
});

Route::get('download_retail_rates_usd', function () {
    $file_name = 'Ratesheet Retail USD.xlsx';
    $file_path = public_path().'/attachments/telecloud/Ratesheet Retail USD.xlsx';

    return response()->download($file_path, $file_name);
});

Route::get('download_international_rates_wholesale', function () {
    $file_path = public_path().'/attachments/telecloud/Ratesheet Wholesale.xlsx';

    return response()->download($file_path, $file_name);
});

Route::get('download_international_rates_retail', function () {
    $file_path = public_path().'/attachments/telecloud/Ratesheet Retail ZAR.xlsx';

    return response()->download($file_path, $file_name);
});

Route::any('get_email_logo.png/{account_id?}', function ($account_id = false) {
    if ($account_id) {
        $partner_id = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();
    } else {
        $partner_id = 1;
    }

    $partner_settings = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', $partner_id)->get()->first();

    if (! session('instance')) {
        $hostname = str_replace(['http://', 'https://'], '', request()->root());
        $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();
        $instance_dir = $instance->db_connection;
        $instance->directory = $instance_dir;
        session(['instance' => $instance]);
    }

    $settings_path = uploads_settings_path();

    if (! empty($partner_settings->logo) && file_exists($settings_path.$partner_settings->logo)) {
        $email_logo = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', $partner_id)->pluck('logo')->first();
    } else {
        $email_logo = '';
    }
    //$img = file_get_contents($settings_path.$email_logo);
    //return response($img)->header('Content-type','image/png');
    //aa($settings_path.$email_logo);
    $pathToFile = $settings_path.$email_logo;
    $headers = ['Content-type' => 'image/png'];

    return response()->file($pathToFile, $headers);
});

Route::any('get_email_logo/{account_id?}', function ($account_id = 0) {
    if ($account_id) {
        $partner_id = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();
    } else {
        $partner_id = 1;
    }

    $partner_settings = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', $partner_id)->get()->first();

    $settings_path = uploads_settings_path();

    if (! empty($partner_settings->logo) && file_exists($settings_path.$partner_settings->logo)) {
        $email_logo = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', $partner_id)->pluck('logo')->first();
    } else {
        $email_logo = '';
    }

    return redirect()->to(settings_url().$email_logo);
});

Route::any('build_api_documentation', function () {
    $cmd = 'nvm use 14.19.2 && apidoc -i '.app_path().'/Http/Controllers -o '.public_path().'/app_documentation  -c /home/_admin/apidoc.json';

    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);

    return redirect()->to('/app_documentation');
});

Route::any('c9host2', function () {
    if (is_superadmin() || is_dev()) {
        $cmd = 'cd /home/_admin && ./cloud9.sh status';
        $result = \Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        if ($result !== 'process is running') {
            $cmd = 'cd /home/_admin && ./cloud9.sh start';
            \Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        }

        return redirect()->to('http://host2.cloudtools.co.za:88/ide.html');
    }
});

Route::any('c9host2_stop', function () {
    if (is_superadmin() || is_dev()) {
        $cmd = 'cd /home/_admin && ./cloud9.sh stop';
        $result = \Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    }
});

Route::any('c9host1_stop', function () {
    if (is_superadmin() || is_dev()) {
        $cmd = 'cd /home/_admin && ./cloud9.sh stop';
        $result = \Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
});

Route::any('c9host1', function () {
    if (is_superadmin() || is_dev()) {
        $cmd = 'cd /home/_admin && ./cloud9.sh status';
        $result = \Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        if ($result !== 'process is running') {
            $cmd = 'cd /home/_admin/ && ./cloud9.sh start';
            \Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        }

        return redirect()->to('http://localhost:88/ide.html');
    }
});

Route::any('c9host1_dev', function () {
    if (session('role_level') == 'Admin') {
        $cmd = 'cd /home/_admin && ./cloud9_dev.sh status';
        $result = \Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        if ($result !== 'process is running') {
            $cmd = 'cd /home/_admin/ && ./cloud9_dev.sh start';
            \Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        }

        return redirect()->to('http://localhost:89/ide.html');
    }
});

Route::any('c9pbx', function () {
    if (is_superadmin() || is_dev()) {
        //$cmd = 'node  /var/www/_admin/server.js -l 0.0.0.0 -p 88 -a admin:Webmin321 -w /var/www/html/ &>/dev/null';
        $cmd = 'cd /var/www/_admin/ && ./cloud9.sh status';
        $result = \Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

        if ($result !== 'process is running') {
            $cmd = 'cd /var/www/_admin/ && ./cloud9.sh start';
            $result = \Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        }

        return redirect()->to('http://pbx.cloudtools.co.za:88/ide.html');
    }
});

Route::any('c9pbx_dev', function () {
    if (session('role_level') == 'Admin') {
        //$cmd = 'node  /var/www/_admin/server.js -l 0.0.0.0 -p 88 -a admin:Webmin321 -w /var/www/html/ &>/dev/null';
        $cmd = 'cd /var/www/_admin/ && node /var/www/_admin/server.js -l 0.0.0.0 -p 89 -a frontend:Cloud@786 -w /var/www/dev/ >/dev/null 2>&1 &';
        \Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

        return redirect()->to('http://pbx.cloudtools.co.za:89/ide.html');
    }
});

Route::any('c9dev', function () {});

Route::any('lte_simswop', [IntegrationsController::class, 'lteSimswop']);
Route::any('clear_callee_id_number', [IntegrationsController::class, 'clearCalleeIDNumber']);

Route::any('knowledgebase', [IntegrationsController::class, 'knowledgebase']);

Route::any('kb_content/{kb_id?}', function ($id) {
    $guide = \DB::table('crm_training_guides')->where('id', $id)->pluck('guide')->first();

    return '<div class="p-3">'.$guide.'</div>';
});

Route::any('submitticket', [IntegrationsController::class, 'submitTicket']);

Route::any('pbx_number_change', [IntegrationsController::class, 'pbxNumberChange']);

Route::any('pbx_number_import', [IntegrationsController::class, 'pbxNumberImport']);
Route::any('domains_import', [IntegrationsController::class, 'domainsImport']);

Route::any('download_invoice/{type?}/{id?}', function ($type, $id) {
    if (! empty(session('invoice_list')) && session('invoice_list') === true) {
        if ($type == 'supplier_documents') {
            $invoice_file = \DB::table('crm_supplier_documents')->where('id', $id)->pluck('invoice_file')->first();

            $pathToFile = uploads_supplier_documents_path().$invoice_file;
        }
        if ($type == 'general_journal') {
            $invoice_file = \DB::table('acc_general_journals')->where('id', $id)->pluck('invoice_file')->first();

            $pathToFile = uploads_path(181).$invoice_file;
        }

        return response()->download($pathToFile);
    }
});

Route::any('accountant_access', [IntegrationsController::class, 'invoiceList']);

Route::any('pbx_test_call', [IntegrationsController::class, 'pbxTestCall']);
Route::any('pbx_test_call_form/{number}', function ($number) {
    if (session('role_id') > 10) {
        return json_alert('No Access', 'warning');
    }
    $data = [];
    $data['outbound_caller_id'] = $number;

    return view('__app.button_views.pbx_call_form', $data);
});

Route::any('pbx_call/{number}/{account_id?}', function ($number, $account_id = 1) {
    if (session('role_level') == 'Admin') {
        $result = pbx_call($number, $account_id);
        if ($result === true) {
            return json_alert('Call sent to PBX');
        } else {
            return json_alert($result, 'error');
        }
    }
});

Route::any('fnb_api', function () {
    if (is_superadmin()) {
        return view('__app.test.fnbapi');
    }
});

Route::post('check_lte_coverage', [IntegrationsController::class, 'checkAxxessLteCoverage']);
Route::post('check_fibre_coverage', [IntegrationsController::class, 'checkFibreCoverage']);
Route::any('mail_unsubscribe/{encoded_link?}', [IntegrationsController::class, 'mailUnsubscribe']);

Route::get('document_popup/{id?}', [IntegrationsController::class, 'documentPopup']);
Route::post('update_doc_delivery', [IntegrationsController::class, 'updateDocDelivery']);

//webform route validate encoded url and redirect to webform
Route::any('webform/{encoded_link?}', [IntegrationsController::class, 'webForm']);

Route::any('cdr_export', [IntegrationsController::class, 'cdrExport']);

Route::any('builder_notes/{id?}', function ($id) {
    $notes = \DB::table('crm_email_manager')->where('id', $id)->pluck('notes')->first();
    echo 'Variables:<br> {{ $customer }}, {{ $parent_company }}, '.$notes;
});

Route::get('check_fail2ban/{account_id?}', function ($account_id = 0) {
    $data = [];
    $data['account_id'] = $account_id;

    return view('__app.button_views.unblock_pbx_ip', $data);
});

Route::post('check_fail2ban', [IntegrationsController::class, 'checkFail2Ban']);

Route::get('flush_fail2ban', function () {
    $pbx = new FusionPBX;
    $result = $pbx->flushFail2Ban();
    if (empty($result)) {
        return redirect()->back()->with('status', 'success')->with('message', 'Flush Complete.');
    } else {
        return redirect()->back()->with('status', 'warning')->with('message', $result);
    }
});

Route::any('debit_order_create', [IntegrationsController::class, 'debitOrderCreate']);
Route::any('debit_order_upload', [IntegrationsController::class, 'debitOrderUpload']);
Route::any('debit_order_report', [IntegrationsController::class, 'debitOrderReport']);

Route::any('interworx_email', [IntegrationsController::class, 'interworxEmail']);

/// STRIPE

Route::any('stripe_payment/{account_id?}/{amount?}', function ($account_id, $amount) {
    $stripe_amount = $amount * 100;
    $url = stripe_payment_link($account_id, $stripe_amount);

    return Redirect::to($url);
});
Route::any('stripe_webhook', [IntegrationsController::class, 'stripeWebhook']);
Route::any('stripe_webhook_test', [IntegrationsController::class, 'stripeWebhookTestMode']);
Route::any('stripe_return', function () {
    return Redirect::to('/')->with('message', 'Payment Successful')->with('status', 'success');
});
Route::any('stripe_cancel', function () {
    return Redirect::to('/')->with('message', 'Payment Cancelled')->with('status', 'warning');
});

/// PAYFAST START
Route::any('payfast_return', function () {
    return Redirect::to('/')->with('message', 'Payment Successful')->with('status', 'success');
});
Route::any('payfast_cancel', function () {
    return Redirect::to('/')->with('message', 'Payment Cancelled')->with('status', 'warning');
});
Route::any('payfast_notify', [IntegrationsController::class, 'payfastResponse']);
Route::any('apple_notify', [IntegrationsController::class, 'appleResponse']);
Route::any('payfast_subscription_notify', [IntegrationsController::class, 'payfastSubscriptionResponse']);

Route::any('payfast_subscription_signup_notify', [IntegrationsController::class, 'payfastSignupSubscriptionResponse']);

Route::any('payfast_netstream_signup_form', [IntegrationsController::class, 'payfastNetstreamSignupForm']);

Route::any('integrations/payfast_button/{account_id?}/{amount?}/{redirect?}', function ($account_id, $amount, $redirect = false) {
    $account = dbgetaccount($account_id);
    if ($account->partner_id != 1) {
        return false;
    }
    $reseller = dbgetaccount($account->partner_id);
    $payment_option = get_payment_option('Payfast');

    $item_name = urlencode($reseller->company.' Services');
    $redirect_url = 'https://www.payfast.co.za/eng/process?cmd=_paynow&receiver='.$payment_option->payfast_id.'&item_name='.$item_name.'&amount='.currency($amount);

    if (! $redirect) {
        return $redirect_url;
    }

    return Redirect::to($redirect_url);
});

Route::any('integrations/payfast_get_signature/{account_id?}/{amount?}', function ($account_id, $amount) {
    $payfast = new Payfast;
    $customer = dbgetaccount($account_id);
    $reseller = dbgetaccount($customer->partner_id);

    if (is_dev()) {
        $payfast->setDebug();
    } else {
        $payfast->setCredentials($reseller->payfast_id, $reseller->payfast_key, $reseller->payfast_pass_phrase);
    }
    $payfast->setPaymentID($customer->id);
    $payfast->getSignature($amount);

    return $signature;
});
/// PAYFAST END

Route::get('paynow/{encoded_link?}', [IntegrationsController::class, 'payNow']);
Route::get('payment_options/{encoded_link?}', [IntegrationsController::class, 'payNow']);
Route::any('domain_search/{any?}', [IntegrationsController::class, 'domainSearch']);
Route::any('domain_search_website', [IntegrationsController::class, 'domainSearchWebsite']);

/// Panels
Route::any('iframe/{menu_slug?}', function ($menu_slug) {
    $menu = \DB::connection('default')->table('erp_menu')->where('slug', $menu_slug)->where('menu_type', 'iframe')->get()->first();
    if (empty($menu)) {
        return redirect()->back()->with('message', 'Invalid URL')->with('status', 'error');
    }

    if (! str_contains($menu->url, 'http://') && function_exists($menu->url)) {
        $function = $menu->url;
        $data = [];
        $data['hide_page_header'] = 1;
        $data['favicon'] = $menu->favicon;

        return view('__app.components.iframe', $data);
    }

    $invalid_src = isIframeDisabled($menu->url);
    // if($invalid_src)
    // return redirect()->back()->with('message','Iframe blocked')->with('status','error');
    if ($menu->url == 'http://projects.cloudsoftware.cc/') {
        $email = \DB::connection('default')->table('erp_users')->where('id', session('user_id'))->pluck('email')->first();
        $menu->url .= 'access/autologin?email='.$email;
    }
    $data = [
        'hide_page_header' => 1,
        'menu_name' => $menu->menu_name,
        'iframe_url' => $menu->url,
        'favicon' => $menu->favicon,
    ];

    return view('__app.components.iframe', $data);
});
Route::any('iframe_edit/{menu_slug?}', function ($menu_slug) {
    $menu = \DB::connection('default')->table('erp_menu')->where('slug', $menu_slug)->where('menu_type', 'iframe')->get()->first();
    if (empty($menu)) {
        return redirect()->back()->with('message', 'Invalid URL')->with('status', 'error');
    }

    if (! str_contains($menu->url, 'http://') && function_exists($menu->url)) {
        $function = $menu->url.'/edit';
        $data = [];
        $data['hide_page_header'] = 1;
        $data['favicon'] = $menu->favicon;

        return view('__app.components.iframe', $data);
    }
    $invalid_src = isIframeDisabled($menu->url);
    // if($invalid_src)
    // return redirect()->back()->with('message','Iframe blocked')->with('status','error');
    if ($menu->url == 'http://projects.cloudsoftware.cc/') {
        $email = \DB::connection('default')->table('erp_users')->where('id', session('user_id'))->pluck('email')->first();
        $menu->url .= 'access/autologin?email='.$email;
    }
    $data = [
        'hide_page_header' => 1,
        'menu_name' => $menu->menu_name,
        'iframe_url' => $menu->url.'/edit',
        'favicon' => $menu->favicon,
    ];

    return view('__app.components.iframe', $data);
});

Route::get('pbx/{title?}', function ($title = false) {
    if (empty(session('pbx_domain')) || ! $title) {
        return redirect()->to('/');
    }
    $title = str_replace('_', ' ', $title);
    $menu = \DB::connection('pbx')->table('v_menu_items')->where('menu_item_title', $title)->pluck('menu_item_link')->first();
    $pbx_domain = session('pbx_domain');

    if (str_contains($menu, 'xml_cdr_details') || str_contains($menu, 'dialplans.php?app_uuid')) {
        $url = 'http://'.$pbx_domain.$menu.'&key='.session('pbx_api_key');
    } else {
        $url = 'http://'.$pbx_domain.$menu.'?key='.session('pbx_api_key');
    }

    $data['menu_name'] = ucwords(str_replace('_', ' ', $title));
    $data['iframe_url'] = $url;
    $data['hide_page_header'] = 1;
    $favicon = \DB::connection('default')->table('erp_menu')->where('location', 'pbx')->pluck('favicon')->first();
    $data['favicon'] = uploads_url(499).$favicon;
    $data['is_pbx'] = true;

    return view('__app.components.iframe', $data);
});

Route::get('pbx_menuedit/{id?}', function ($id = false) {
    $title = str_replace('_', ' ', $title);
    if ($id) {
        $url = '/core/menu/menu_item_edit.php?id=b4750c3f-2a86-b00d-b7d0-345c14eca286&menu_uuid=b4750c3f-2a86-b00d-b7d0-345c14eca286&menu_item_uuid='.$id.'&key='.session('pbx_api_key');
    } else {
        $url = '/core/menu/menu_item_edit.php?id=b4750c3f-2a86-b00d-b7d0-345c14eca286'.'&key='.session('pbx_api_key');
    }

    if (str_contains($menu, 'xml_cdr_details') || str_contains($menu, 'dialplans.php?app_uuid')) {
        $url = 'http://'.session('pbx_domain').$url;
    } else {
        $url = 'http://'.session('pbx_domain').$url;
    }

    $data['menu_name'] = 'PBX Panel';
    $data['iframe_url'] = $url;
    $data['hide_page_header'] = 1;
    $data['favicon'] = 'pbx.ico';

    return view('__app.components.iframe', $data);
});

Route::get('pbx_menu_route_old/{menu_item_uuid?}/{module_id?}/{id?}', function ($menu_item_uuid, $module_id = 0, $row_id = 0) {
    try {
        $menu_item = \DB::connection('pbx')->table('v_menu_items')
            ->where('menu_item_uuid', $menu_item_uuid)
            ->get()->first();
        $menu_item_link = $menu_item->menu_item_link;

        update_pbx_group_permissions();

        $item_uuids = ['65c7b855-10e5-4cd3-a391-b3b6ab8eada9', 'bc96d773-ee57-0cdd-c3ac-2d91aba61b55'];
        if (in_array($menu_item->menu_item_uuid, $item_uuids) || in_array($menu_item->menu_item_parent_uuid, $item_uuids)) {
            if (empty($module_id) || empty($row_id)) {
                return redirect()->back()->with('Invalid Id');
            }

            $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
            $conn = $module->connection;
            $table = $module->db_table;

            $row = \DB::connection($conn)->table($table)->where($module->db_key, $row_id)->get()->first();

            if ($row->account_id) {
                $account_id = $row->account_id;
            } elseif ($row->domain_uuid) {
                $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $row->domain_uuid)->pluck('account_id')->first();
            } elseif ($row->domain_name) {
                $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_name', $row->domain_name)->pluck('account_id')->first();
            }

            if (empty($account_id)) {
                return redirect()->back()->with('Invalid Account Id');
            }
            $pbx_row = \DB::connection('pbx')->table('v_users as vu')
                ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
                ->where('vd.account_id', $account_id)
                ->get()->first();
        } else {
            if (session('role_level') == 'Admin') {
                $pbx_row = (object) [];
                $pbx_row->domain_name = '156.0.96.60';
                $pbx_row->api_key = 'e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
            }
        }
        /*
         if ($account_id == 12 && (is_superadmin() || is_dev())) {
             $pbx_row->domain_name ='156.0.96.60';
             $pbx_row->api_key ='e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
         }
         */
        if ($pbx_row->api_key && $pbx_row->domain_name) {
            $url = 'http://'.$pbx_row->domain_name.$menu_item_link.'?key='.$pbx_row->api_key;
        }

        return redirect()->to($url);
    } catch (\Throwable $e) {
        if (is_dev()) {
        } else {
            return redirect()->back()->with('An error occurred');
        }
    }
});

Route::get('pbx_menu_route/{menu_item_uuid?}/{module_id?}/{id?}', function ($menu_item_uuid, $module_id = 0, $row_id = 0) {
    try {
        $menu_item = \DB::connection('pbx')->table('v_menu_items')
            ->where('menu_item_uuid', $menu_item_uuid)
            ->get()->first();
        $menu_item_link = $menu_item->menu_item_link;

        update_pbx_group_permissions();

        $item_uuids = ['65c7b855-10e5-4cd3-a391-b3b6ab8eada9', 'bc96d773-ee57-0cdd-c3ac-2d91aba61b55'];
        if (in_array($menu_item->menu_item_uuid, $item_uuids) || in_array($menu_item->menu_item_parent_uuid, $item_uuids) && ! empty($module_id)) {
            if (empty($module_id) || empty($row_id)) {
                return redirect()->back()->with('Invalid Id');
            }

            $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
            $conn = $module->connection;
            $table = $module->db_table;

            $row = \DB::connection($conn)->table($table)->where($module->db_key, $row_id)->get()->first();

            if ($row->account_id) {
                $account_id = $row->account_id;
            } elseif ($row->domain_uuid) {
                $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $row->domain_uuid)->pluck('account_id')->first();
            } elseif ($row->domain_name) {
                $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_name', $row->domain_name)->pluck('account_id')->first();
            }

            if (empty($account_id)) {
                return redirect()->back()->with('Invalid Account Id');
            }
            $pbx_row = \DB::connection('pbx')->table('v_users as vu')
                ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
                ->where('vd.account_id', $account_id)
                ->get()->first();
        } else {
            if (session('role_level') == 'Admin') {
                $pbx_row = (object) [];
                $pbx_row->domain_name = '156.0.96.60';
                $pbx_row->api_key = 'e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
            }
        }
        /*
         if ($account_id == 12 && (is_superadmin() || is_dev())) {
             $pbx_row->domain_name ='156.0.96.60';
             $pbx_row->api_key ='e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
         }
         */
        if ($pbx_row->api_key && $pbx_row->domain_name) {
            $url = 'https://'.$pbx_row->domain_name.$menu_item_link.'?key='.$pbx_row->api_key;
        }

        return redirect()->to($url);
        if (is_dev()) {
            if (str_contains($url, '156.0.96.60')) {
                return redirect()->to($url);
            }
            $data = [
                'menu_name' => $menu_item->menu_name,
                'iframe_url' => $url,
            ];

            return view('__app.components.iframe', $data);
        } else {
            return redirect()->to($url);
        }
    } catch (\Throwable $e) {
        if (is_dev()) {
        } else {
            return redirect()->back()->with('An error occurred');
        }
    }
})->middleware('globalviewdata');

Route::get('airtime_history_account/{account_id?}', function ($account_id) {
    $domain_uuid = \DB::connection('pbx')->table('v_domains')
        ->where('account_id', $account_id)
        ->pluck('domain_uuid')->first();

    $url = get_menu_url_from_module_id(589).'?domain_uuid='.$domain_uuid;

    return redirect()->to($url);
});

Route::get('pbx_login/{account_id?}', function ($account_id) {
    update_pbx_group_permissions();
    $pbx_row = \DB::connection('pbx')->table('v_users as vu')
        ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
        ->where('vd.account_id', $account_id)
        ->get()->first();

    if ($pbx_row->api_key && $pbx_row->domain_name) {
        $url = 'http://'.$pbx_row->domain_name.'/app/extensions/extensions.php?key='.$pbx_row->api_key;
    }

    return redirect()->to($url);
});

Route::get('pbx_panel_login/{account_id?}', function ($account_id = null) {
    $pbx = new FusionPBX;
    $pbx->pbx_login($account_id);

    if (! empty(request()->query('return_to'))) {
        return redirect()->to(request()->query('return_to'));
    }

    return redirect()->back();
});

Route::get('pbx_admin_login', function () {
    if (is_superadmin()) {
        $url = 'http://156.0.96.60/app/extensions/extensions.php?key=e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';

        //$url = 'https://pbx.cloudtools.co.za/core/user_settings/user_dashboard.php?key=c41db17b-c29d-42a9-8776-6f1397359d04';
        return redirect()->to($url);
    }
});

Route::get('pbx_admin_iframe', function () {
    if (session('role_level') == 'Admin') {
        //$url = 'http://156.0.96.60/core/user_settings/user_dashboard.php?key=e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
        $url = 'https://pbx.cloudtools.co.za/app/extensions/extensions.php?key=c41db17b-c29d-42a9-8776-6f1397359d04';
        $data = [
            'menu_name' => 'PBX Admin',
            'iframe_url' => $url,
        ];

        return view('__app.components.iframe', $data);
    }
})->middleware('globalviewdata');

Route::get('pbx_advanced', function () {
    if (session('instance')->directory == 'telecloud') {
        $url = url('/');
        if (session('pbx_api_key')) {
            $url = 'http://'.session('pbx_domain').'/core/user_settings/user_dashboard.php?key='.session('pbx_api_key');
        }

        return redirect()->to($url);
    } else {
        $url = 'http://156.0.96.60/core/user_settings/user_dashboard.php?key=e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
        $domain = session('pbx_domain');
        if ($domain == '156.0.96.60') {
            $domain = '156.0.96.61';
        }
        if (session('pbx_api_key')) {
            $url = 'http://'.$domain.'/core/user_settings/user_dashboard.php?key='.session('pbx_api_key');
        }

        return redirect()->to($url);
    }
});

Route::get('pbx_debug', function () {
    return redirect()->to('http://156.0.96.63/app/extensions/extensions.php?key=e2e4e9a0-c678-45a2-97a2-e24f9f2481fa&domain_uuid=4ae2a2de-6473-4bc1-b307-a35a507a98b2');
});

Route::get('sms_panel_login/{account_id?}', function ($account_id = null) {
    $pbx = new FusionPBX;

    return $pbx->sms_login($account_id);
});

Route::get('host_1', function () {
    if (session('role_level') == 'Admin') {
        return redirect()->to('https://localhost:2443/nodeworx/index?action=login&email=ahmed@telecloud.co.za&password=Webmin786');
    } else {
        return redirect()->back()->with('message', 'No Access')->with('status', 'error');
    }
});

Route::get('host_2', function () {
    if (session('role_level') == 'Admin') {
        return redirect()->to('https://host2.cloudtools.co.za:2443/nodeworx/index?action=login&email=ahmed@telecloud.co.za&password=Webmin786');
    } else {
        return redirect()->back()->with('message', 'No Access')->with('status', 'error');
    }
});

Route::get('manage_service/{id?}', function ($id) {
    $sub = \DB::table('sub_services')->where('id', $id)->get()->first();

    if ($sub->provision_type == 'hosting' || $sub->provision_type == 'domain_name' || $sub->provision_type == 'sitebuilder') {
        $domain = \DB::table('isp_host_websites')->where('domain', $sub->detail)->get()->first();

        return redirect()->to('hosting_login/'.$sub->account_id.'/'.$domain->id);
    }

    if ($sub->provision_type == 'lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_vodacom_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'mtn_lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'telkom_lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'iptv' || $sub->provision_type == 'iptv_global') {
        $menu_name = get_menu_url_from_table('isp_data_iptv');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'fibre') {
        $menu_name = get_menu_url_from_table('isp_data_fibre');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }

    if ($sub->provision_type == 'airtime_prepaid' || $sub->provision_type == 'airtime_unlimited' || $sub->provision_type == 'airtime_contract') {
        $menu_name = get_menu_url_from_table('v_domains');
        $pbx = new FusionPBX;
        $pbx->pbx_login($sub->account_id);
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('domain_name')->first();

        return redirect()->to($menu_name.'?domain_name='.$domain_name);
    }
    if ($sub->provision_type == 'phone_number') {
        $menu_name = get_menu_url_from_table('p_phone_numbers');
        $pbx = new FusionPBX;
        $pbx->pbx_login($sub->account_id);

        return redirect()->to($menu_name.'?number='.$sub->detail);
    }
    if ($sub->provision_type == 'pbx_extension' || $sub->provision_type == 'sip_trunk') {
        $menu_name = get_menu_url_from_table('v_extensions');
        $pbx = new FusionPBX;
        $pbx->pbx_login($sub->account_id);

        return redirect()->to($menu_name.'?extension='.$sub->detail);
    }

    if ($sub->provision_type == 'bulk_sms' || $sub->provision_type == 'bulk_sms_prepaid') {
        return redirect()->to('sms_panel/'.$sub->account_id);
    }
});

Route::get('hosting_login/{account_id?}/{domain_id?}', function ($account_id, $domain_id) {
    if (session('role_level') == 'Admin' || (session('account_id') == $account_id || parent_of($account_id))) {
        $domain = \DB::connection('default')->table('isp_host_websites')->where('id', $domain_id)->get()->first();

        if (! $domain) {
            return redirect()->back()->with('message', 'No Access')->with('status', 'error');
        }
        $product_package = \DB::table('crm_products')->where('id', $domain->product_id)->pluck('provision_package')->first();
        \DB::connection('default')->table('isp_host_websites')->where('id', $domain->id)->update(['package' => $product_package]);

        panel_to_siteworx($domain->account_id, $domain->domain, $product_package);
        $domain = \DB::connection('default')->table('isp_host_websites')->where('id', $domain->id)->get()->first();
        $username = $domain->username;
        $password = $domain->password;
        if ($domain->server == 'host2') {
            $url = 'https://host2.cloudtools.co.za:2443/siteworx/index?action=login&email='.$username.'&password='.$password.'&domain='.$domain->domain;
        } else {
            $url = 'https://localhost:2443/siteworx/index?action=login&email='.$username.'&password='.$password.'&domain='.$domain->domain;
        }

        return redirect()->to($url);
        $data['menu_name'] = 'Hosting Panel';
        $data['iframe_url'] = $url;
        $data['iframe_help'] = 'Page not responding? Press F12, right-click reload button, select empty cache.';
        $data['hide_page_header'] = 1;
        $data['favicon'] = 'interworx.png';

        return view('__app.components.iframe', $data);
    } else {
        return redirect()->back()->with('message', 'No Access')->with('status', 'error');
    }
});

Route::get('sitebuilder_panel/{account_id?}/{domain_id?}', function ($account_id, $domain_id) {
    if (is_superadmin() || (session('account_id') == $account_id || parent_of($account_id))) {
        $domain = \DB::connection('default')->table('isp_host_websites')->where('id', $domain_id)->where('sitebuilder', 1)->get()->first();

        if (! $domain) {
            return redirect()->back()->with('message', 'No Access')->with('status', 'error');
        }

        // redirect to /admin.php?key=1
        $ix = new Interworx;
        $ix->setDomain($domain->domain);
        $url = $ix->getSitebuilderAutoLoginUrl();

        return redirect()->to($url);
    } else {
        return redirect()->back()->with('message', 'No Access')->with('status', 'error');
    }
});

Route::any('check_porting_ftp', function () {
    try {
        $directories = Storage::disk('porting_ftp')->allDirectories();
        ddd($directories);
        $directories = Storage::disk('porting_ftp_mnp')->allDirectories();
        ddd($directories);
    } catch (\Throwable $ex) {
        exception_log($ex);
        ddd($ex->getMessage());
        ddd($ex->getTraceAsString());
    }
});

Route::get('service_availability', function () {
    return view('__app.components.pages.service_availability', ['menu_name' => 'Service Availability']);
});

Route::get('dashboard_favicon/{letter?}', function ($letter = 'D') {
    $im = imagecreate(32, 32);

    /* Preserve the PNG Image Transparency */
    imagealphablending($im, false);
    imagesavealpha($im, true);

    /* Background Color for the Favicon */
    $bg = imagecolorallocate($im, 255, 255, 255); // 255,255,255 = white

    $grey = imagecolorallocate($im, 128, 128, 128);
    $black = imagecolorallocate($im, 0, 0, 0);

    //imagestring($im, 5, 12, 8, $letter, $color);

    $font = public_path().'/assets/fonts/TitilliumWeb-Bold.ttf';

    // Add some shadow to the text
    imagettftext($im, 20, 0, 7, 26, $grey, $font, $letter);

    // Add the text
    imagettftext($im, 20, 0, 7, 25, $black, $font, $letter);

    /* Send the Image to the Browser */
    header('Content-type: image/png');
    //header('Content-Disposition: attachment; filename="favicon.jpg"');
    imagepng($im);

    /* Clear Memory from Image Data*/
    imagedestroy($im);
});

Route::get('get_cdr_log', function () {
    $file = '/var/www/html/debug.log';
    $cmd = 'cat '.$file;
    $result = \Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    if (empty($result)) {
        echo 'Debug file is empty';
    } else {
        echo nl2br($result);
    }
});

Route::any('workspace_old_user/{user_id?}', [IntegrationsController::class, 'kanban']);

Route::any('tinymce_images', [IntegrationsController::class, 'tinymceImages']);

Route::any('sms_result', [IntegrationsController::class, 'smsResult']);

Route::any('reamaze_log_call', [IntegrationsController::class, 'reamazeLogCall']);

Route::get('registration_failures_cmd_ajax', function () {
    if (session('role_level') == 'Admin') {
        if (! empty(request()->domain_name)) {
            $cmd = 'cat /var/log/freeswitch/freeswitch.log | grep failure | grep '.request()->domain_name;
        } else {
            $cmd = 'cat /var/log/freeswitch/freeswitch.log | grep failure ';
        }

        $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        $result_arr = explode(PHP_EOL, $result);
        echo '<div class="card card-body">';
        foreach ($result_arr as $r) {
            echo '<code>'.$r.'</code>';
        }
        echo '</div>';
    } else {
        echo 'No Access';
    }
});

Route::get('registration_failures_cmd', function () {
    if (session('role_level') == 'Admin') {
        return view('__app.button_views.registration_failures');
    } else {
        echo 'No Access';
    }
});

Route::get('download_pbx_recordings/{domain_name?}', function ($domain_name = false) {
    if (! empty(request()->domain_uuid)) {
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request()->domain_uuid)->pluck('domain_name')->first();
    }

    if (! empty(request()->domain_name)) {
        $domain_name = request()->domain_name;
    }

    if (empty($domain_name)) {
        return json_alert('Domain name required', 'warning');
    }
    // check for active subscription
    $product_id = 996; // pbxextrec
    $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain_name)->pluck('account_id')->first();
    $recording_subscription = \DB::connection('default')->table('sub_services')->where('account_id', $account_id)->where('product_id', $product_id)->where('status', '!=', 'Deleted')->count();
    if (! $recording_subscription) {
        return json_alert('No active extension recording subscription found', 'warning');
    }

    if (empty(session('account_id'))) {
        // add account session check
        return json_alert('No access', 'error');
    }
    $ssh = new \phpseclib\Net\SSH2('pbx.cloudtools.co.za');

    if (! $ssh->login('root', 'Ahmed777')) {
        return json_alert('Unavailable', 'error');
    }
    $cdr_type = 'outbound';
    $cdr_count = \DB::connection('pbx_cdr')->table('call_records_'.$cdr_type)
        ->where('domain_name', $domain_name)
        ->where('recording_file', '>', '')
        ->where('hangup_time', '>=', date('Y-m-d', strtotime('-7 days')))
        ->count();
    if (! $cdr_count) {
        return json_alert('No recordings found', 'error');
    }
    $cdr = \DB::connection('pbx_cdr')->table('call_records_'.$cdr_type)
        ->where('domain_name', $domain_name)
        ->where('recording_file', '>', '')
        ->where('hangup_time', '>=', date('Y-m-d', strtotime('-7 days')))
        ->get();

    $pbx_recordings_path = \Storage::disk('pbx_recordings')->path('');

    $zip_filename = $domain_name.'_'.date('Ymd').'_'.$cdr_type.'_recordings.zip';
    $zip = new ZipArchive;
    $delete_items = [];
    if (true === ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
        foreach ($cdr as $c) {
            if ($cdr_type == 'inbound') {
                $filename = $c->caller_id_number.'_'.date('Ymd_H:i', strtotime($c->hangup_time)).'.mp3';
            } else {
                $filename = $c->callee_id_number.'_'.date('Ymd_H:i', strtotime($c->hangup_time)).'.mp3';
            }
            $local_file = $pbx_recordings_path.$filename;
            $remote_file = '/var/lib/freeswitch/recordings'.$c->recording_file;
            $delete_items[] = $filename;
            $scp = new \phpseclib\Net\SCP($ssh);
            $result = $scp->get($remote_file, $local_file);

            if ($result) {
                $zip->addFile($local_file, $filename);
            }
        }

        $zip->close();

        foreach ($delete_items as $delete_item) {
            \Storage::disk('pbx_recordings')->delete($delete_item);
        }

        return response()->download(public_path($zip_filename), $zip_filename);
    } else {
        return json_alert('Zip error', 'error');
    }
});

Route::get('download_document_new/{document_id?}', function ($document_id) {
    $doc = \DB::table('crm_documents')->where('id', $document_id)->get()->first();

    $pdf = document_pdf_new($doc->id);

    $file = $doc->doctype.'_'.$doc->id.'.pdf';
    $filepath = attachments_path().$file;
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    $pdf = document_pdf_new($doc->id);
    $pdf->save($filepath);

    return response()->download($filepath, $file);
});

Route::get('download_document/{document_id?}', function ($document_id) {
    $doc = \DB::table('crm_documents')->where('id', $document_id)->get()->first();

    $pdf = document_pdf($doc->id);
    $file = $doc->doctype.'_'.$doc->id.'.pdf';
    $filepath = attachments_path().$file;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    $pdf = document_pdf($doc->id);
    $pdf->save($filepath);

    return response()->download($filepath, $file);
});

Route::get('download_documents/{account_id?}', function ($account_id) {
    $account = dbgetaccount($account_id);
    $zip_filename = $account->company.' documents.zip';
    $zip = new ZipArchive;
    $documents = \DB::table('crm_documents')->where('account_id', $account_id)->get();
    if (true === ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
        foreach ($documents as $doc) {
            $pdf = document_pdf($doc->id);
            $file = $doc->doctype.'_'.$doc->id.'.pdf';
            $filepath = attachments_path().$file;
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            $zip->addFile($filepath, $file);
        }

        $zip->close();

        return response()->download(public_path($zip_filename), $zip_filename);
    } else {
        return json_alert('Zip error', 'error');
    }
});

Route::get('download_vat_reports/{vat_id?}', function ($vat_id) {});
