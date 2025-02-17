<?php

function schedule_import_approvals()
{
    return false;
    if (! is_main_instance()) {
        return false;
    }
    $system_user_id = get_system_user_id();

    $instances = \DB::table('erp_instances')->whereIn('id', [2, 11])->get();
    foreach ($instances as $i) {
        $approvals = \DB::connection($i->db_connection)->table('crm_approvals')->where('processed', 0)->where('is_deleted', 0)->get();
        foreach ($approvals as $a) {
            $e = \DB::table('crm_approvals')->where('instance_id', $i->id)->where('external_id', $a->id)->count();
            if (! $e) {
                $d = (array) $a;
                unset($d['id']);
                $d['external_id'] = $a->id;
                $d['instance_id'] = $i->id;
                $d['requested_by'] = $system_user_id;
                dbinsert('crm_approvals', $d);
            }
        }
    }

    $approvals = \DB::table('crm_approvals')->whereIn('instance_id', [2, 11])->where('processed', 0)->get();
    foreach ($approvals as $a) {
        $instance = \DB::table('erp_instances')->where('id', $a->instance_id)->get()->first();
        $external_approval = \DB::connection($instance->db_connection)->table('crm_approvals')->where('id', $a->external_id)->get()->first();
        $data = (array) $external_approval;
        unset($data['id']);
        unset($data['external_id']);
        unset($data['instance_id']);
        \DB::table('crm_approvals')->where('external_id', $a->external_id)->where('instance_id', $a->instance_id)->update($data);
    }

}

function onload_update_approval_notes()
{

    $system_user_id = get_system_user_id();
    \DB::table('crm_approvals')->where('requested_by', 0)->update(['requested_by' => $system_user_id]);

    $modules = \DB::table('erp_cruds')->get();
    $approvals = \DB::table('crm_approvals')->where('processed', 0)->get();

    \DB::table('crm_approvals')->where('instance_id', 1)->where('processed', 0)->where('module_id', 343)->update(['account_id' => \DB::raw('row_id')]);
    \DB::table('crm_approvals')->where('instance_id', 1)->where('processed', 0)->where('module_id', 334)->update(['account_id' => \DB::raw('(select account_id from sub_services where id=row_id)')]);
    \DB::table('crm_approvals')->where('instance_id', 1)->where('processed', 0)->where('module_id', 353)->update(['account_id' => \DB::raw('(select account_id from crm_documents where id=row_id)')]);
    \DB::table('crm_approvals')->where('instance_id', 1)->where('processed', 0)->where('module_id', 1923)->update(['account_id' => \DB::raw('(select account_id from crm_opportunities where id=row_id)')]);
    // set references

    foreach ($approvals as $approval) {

        if ($approval->module_id == 353 && str_contains($approval->title, 'Order')) {
            $converted = \DB::table('crm_documents')->where('id', $approval->row_id)->where('doctype', 'Tax Invoice')->count();
            if ($converted) {
                dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_by' => get_system_user_id(), 'processed_at' => date('Y-m-d H:i:s')]);

                continue;
            }
        }

        $references_arr = [];

        $main_conn = 'default';
        if (session('instance')->id != $approval->instance_id) {
            $instance = \DB::table('erp_instances')->where('id', $approval->instance_id)->get()->first();
            $main_conn = $instance->db_connection;
        }
        $module = \DB::connection($main_conn)->table('erp_cruds')->where('id', $approval->module_id)->first();
        if ($module) {
            $module_conn = $module->connection;
            if ($module->connection == 'default') {
                $module_conn = $main_conn;
            }
            $cols = get_columns_from_schema($module->db_table, '', $module_conn);
            $account_id = false;
            if ($module->db_table == 'crm_accounts') {
                $account_id = $approval->row_id;
            } else {
                if (in_array('account_id', $cols)) {
                    $account_id = \DB::connection($module_conn)->table($module->db_table)->where($module->db_key, $approval->row_id)->pluck('account_id')->first();
                }
            }

            if ($account_id) {
                $references_arr[] = \DB::connection($main_conn)->table('crm_accounts')->where('id', $account_id)->pluck('company')->first();

            }
            if ($module->db_table == 'crm_documents') {
                $doc = \DB::connection($main_conn)->table('crm_documents')->select('credit_note_reason', 'total')->where('id', $approval->row_id)->get()->first();
                if ($doc->total) {
                    $references_arr[] = 'Doc Total: '.$doc->total;
                }
                if ($doc->credit_note_reason) {
                    $references_arr[] = 'Credit note reason: '.$doc->credit_note_reason;
                }
            } elseif ($account_id) {
                $balance = \DB::connection($main_conn)->table('crm_accounts')->where('id', $account_id)->pluck('balance')->first();
                $references_arr[] = 'Account Balance: '.$balance;
            }

            if (in_array('reason', $cols)) {
                $reason = \DB::connection($module_conn)->table($module->db_table)->where($module->db_key, $approval->row_id)->pluck('reason')->first();
                $references_arr[] = 'Reason: '.$reason;
            }

            if (count($references_arr) > 0) {
                \DB::table('crm_approvals')->where('id', $approval->id)->update(['reference' => implode(' | ', $references_arr)]);
            }
        }
    }

    foreach ($approvals as $approval) {
        $notes = \DB::connection('default')->table('erp_module_notes')
            ->where('module_id', $approval->module_id)
            ->where('row_id', $approval->row_id)
            ->where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
        foreach ($notes as $note) {
            $e = \DB::connection('default')->table('erp_module_notes')->where('module_id', 1859)->where('row_id', $approval->id)->where('note', 'like', '#'.$note->id.'%')->count();
            if (! $e) {
                $data = (array) $note;
                $data['note'] = '#'.$note->id.' '.$note->note;
                $data['row_id'] = $approval->id;
                $data['module_id'] = 1859;
                unset($data['id']);

                \DB::connection('default')->table('erp_module_notes')->insert($data);
            }
        }

        $module = $modules->where('id', $approval->module_id)->first();
        if ($module) {
            $cols = get_columns_from_schema($module->db_table, '', $module->connection);
            if (in_array('is_deleted', $cols)) {
                $exists = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $approval->row_id)->where('is_deleted', 0)->count();
            } else {
                $exists = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $approval->row_id)->count();
            }
            if (! $exists && ! str_contains($approval->title, 'Add')) {
                dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_by' => get_system_user_id(), 'processed_at' => date('Y-m-d H:i:s')]);
            }

        }

    }

}

function button_approvals_approve_all($request)
{
    $approvals = \DB::table('crm_approvals')->where('is_deleted', 0)->where('processed', 0)->orderBy('id', 'desc')->get();
    foreach ($approvals as $approval) {
        $response = process_approval($approval);
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            if ($response->getData()->status != 'success') {
                return $response;
            }
        }
    }
}

function process_approval($request)
{

    $approval = \DB::table('crm_approvals')->where('id', $request->id)->get()->first();

    $approvals_url = get_menu_url_from_table('crm_approvals');
    if (is_main_instance() && $approval->instance_id != session('instance')->id) {

        $instance = \DB::table('erp_instances')->where('id', $approval->instance_id)->get()->first();

        $response = (new \GuzzleHttp\Client)->get('https://'.$instance->domain_name.'/process_approval/'.$approval->external_id.'/'.session('user_id'));

        schedule_import_approvals();

        return response()->json(['status' => 'success', 'message' => 'Processed']);
    }

    if (! is_superadmin()) {
        if (empty($approval->notes)) {
            return json_alert('Note required', 'warning');
        }
    }

    if (! empty($approval->post_data)) {
        $request = json_decode($approval->post_data);

        $db = new \DBEvent($approval->module_id, $settings);

        $result = $db->save($request);
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            rebuild_approval_notifications();

            return $result;
        } elseif (! is_array($result) || empty($result['id'])) {
            rebuild_approval_notifications();

            return response()->json(['status' => 'warning', 'message' => $result]);
        } else {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            rebuild_approval_notifications();

            return response()->json(['status' => 'success', 'message' => 'Record saved']);
        }
    }

    // WORKBOARD MAX DURATION
    if ($approval->module_id == 1898 && ! str_contains($approval->title, 'Delete')) {
        $task = \DB::table('crm_staff_tasks')->where('id', $approval->row_id)->get()->first();
        \DB::table('crm_staff_tasks')->where('id', $approval->row_id)->update(['max_duration' => \DB::raw('new_max_duration')]);
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        rebuild_approval_notifications();

        return json_alert('Approved');
    }
    // BILLING
    if ($approval->module_id == 744) {
        $billing = \DB::table('acc_billing')->where('id', $approval->row_id)->get()->first();

        if ($billing->approved) {
            \DB::table('crm_approvals')->where('row_id', $billing->id)->where('module_id', 744)->update(['processed_at' => date('Y-m-d H:i:s'), 'processed' => 1, 'processed_by' => get_user_id_default()]);

            return json_alert('Billing already approved');
        }

        if ($billing->num_emails_success > 0) {
            return json_alert('Billing cannot be changed, billing emails already sent');
        }
        \DB::table('crm_documents')->where('docdate', $billing->billing_date)->where('billing_type', $billing->billing_type)->where('reversal_id', 0)->whereIn('doctype', ['Quotation', 'Order'])->update(['doctype' => 'Tax Invoice']);

        if (session('instance')->id == 2) {
            //credit rentals
            $credit_rental_space_ids = \DB::table('crm_rental_spaces')->where('has_lease', 'Internal')->pluck('id')->toArray();
            $credit_rental_account_ids = \DB::table('crm_rental_leases')->whereIn('rental_space_id', $credit_rental_space_ids)->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();

            $cloudtelecoms_invoice_ids = \DB::table('crm_documents')
                ->where('reversal_id', 0)
                ->whereIn('account_id', $credit_rental_account_ids)
                ->where('docdate', $billing->billing_date)
                ->where('doctype', 'Tax Invoice')
                ->where('billing_type', $billing->billing_type)
                ->pluck('id')->toArray();
            foreach ($cloudtelecoms_invoice_ids as $invoice_ids) {
                create_credit_note_from_invoice($invoice_ids);
            }
        }

        \DB::table('acc_billing')->where('id', $approval->row_id)->update(['approved' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        email_monthly_billing($billing->id);
        module_log($approval->module_id, $id, 'approved');
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        rebuild_approval_notifications();

        return json_alert('Approved');

    }

    // STOCK ADJUSTMENTS
    if ($approval->module_id == 703) {
        $id = $approval->row_id;
        \DB::table('acc_inventory')->where('id', $id)->update(['approved' => 1]);
        $inv = \DB::table('acc_inventory')->where('id', $id)->get()->first();

        $data = (array) $inv;
        $erp = new DBEvent;
        $erp->setTable('acc_inventory');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('acc_inventory')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            pricelist_set_discounts();
            rebuild_approval_notifications();

            return json_alert('Stock adjustment approved.');
        } else {
            \DB::table('acc_inventory')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        }
    }
    // STOCK TAKE
    if ($approval->module_id == 1939) {

        $id = $approval->row_id;
        \DB::table('crm_stock_take')->where('id', $id)->update(['approved' => 1]);

        module_log($approval->module_id, $id, 'approved');
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

        rebuild_approval_notifications();

        return json_alert('Stock take approved.');
    }

    // LEAVE REQUESTS
    if ($approval->module_id == 533) {
        $id = $approval->row_id;
        \DB::table('hr_leave')->where('id', $id)->update(['approved' => 1]);
        $leave = \DB::table('hr_leave')->where('id', $id)->get()->first();

        $data = (array) $leave;
        $erp = new DBEvent;
        $erp->setTable('hr_leave');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('hr_leave')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            rebuild_approval_notifications();

            return json_alert('Leave request approved.');
        } else {
            \DB::table('hr_leave')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        }
    }

    if ($approval->module_id == 1866) {
        $id = $approval->row_id;
        \DB::table('hr_loans')->where('id', $id)->update(['approved' => 1]);
        $leave = \DB::table('hr_loans')->where('id', $id)->get()->first();

        $data = (array) $leave;
        $erp = new DBEvent;
        $erp->setTable('hr_loans');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('hr_loans')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            rebuild_approval_notifications();

            return json_alert('Loan approved.');
        } else {
            \DB::table('hr_loans')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        }
    }
    // DEBTORS COMMITMENTS
    if ($approval->module_id == 709) {
        $id = $approval->row_id;
        \DB::table('crm_commitment_dates')->where('id', $id)->update(['approved' => 1]);
        $row = \DB::table('crm_commitment_dates')->where('id', $id)->get()->first();

        $data = (array) $row;
        $erp = new DBEvent;
        $erp->setTable('crm_commitment_dates');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('crm_commitment_dates')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

            rebuild_approval_notifications();

            return json_alert('Commitment approved.');
        } else {
            \DB::table('crm_commitment_dates')->where('id', $id)->update(['approved' => 0]);
            rebuild_approval_notifications();

            return $result;
        }
    }

    // IMPORT SUPPLIER DOCUMENTS
    if ($approval->module_id == 1861) {
        $id = $approval->row_id;
        \DB::table('crm_import_shipments')->where('id', $id)->update(['processed' => 1]);
        $inv = \DB::table('crm_import_shipments')->where('id', $id)->get()->first();

        $data = (array) $inv;
        $erp = new DBEvent;
        $erp->setModule(1861);
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('crm_import_shipments')->where('id', $id)->update(['processed' => 0]);
            rebuild_approval_notifications();

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            pricelist_set_discounts();
            rebuild_approval_notifications();

            return json_alert('Import Shipment approved.');
        } else {
            \DB::table('crm_import_shipments')->where('id', $id)->update(['processed' => 0]);
            rebuild_approval_notifications();

            return $result;
        }
    }

    // OrderS, CREDIT NOTES
    $approval_module_db_table = \DB::table('erp_cruds')->where('id', $approval->module_id)->pluck('db_table')->first();
    if ($approval_module_db_table == 'crm_documents' && str_contains($approval->title, 'Custom Price')) {
        $id = $approval->row_id;
        \DB::table('crm_documents')->where('id', $id)->update(['custom_prices_approved' => 1]);
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        rebuild_approval_notifications();

        return json_alert('Custom Prices approved.');
    }

    if ($approval_module_db_table == 'crm_documents' && ! str_contains($approval->title, 'Delete')) {
        $id = $approval->row_id;
        $doctype = \DB::table('crm_documents')->where('id', $id)->pluck('doctype')->first();
        $data = ['id' => $id];
        $url = get_menu_url_from_module_id($approval->module_id).'/approve';

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postApproveTransaction($request);
        $new_doctype = \DB::table('crm_documents')->where('id', $id)->pluck('doctype')->first();

        if ($doctype != $new_doctype) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            \DB::table('crm_approvals')->where('processed', 0)->where('module_id', $approval->module_id)->where('row_id', $approval->row_id)->delete();
            rebuild_approval_notifications();

            return json_alert('Document approved.');

        } else {

            rebuild_approval_notifications();

            return $result;
        }
    }

    // SUPPLIER ORDERS
    if ($approval->module_id == 354) {
        $id = $approval->row_id;
        $doctype = \DB::table('crm_supplier_documents')->where('id', $id)->pluck('doctype')->first();
        $data = ['id' => $id];
        $url = get_menu_url_from_table('crm_supplier_documents').'/approve';

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postApproveTransaction($request);

        $new_doctype = \DB::table('crm_supplier_documents')->where('id', $id)->pluck('doctype')->first();

        if ($doctype != $new_doctype) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

            rebuild_approval_notifications();

            return json_alert('Document approved.');

        } else {

            rebuild_approval_notifications();

            return $result;
        }
    }

    // JOURNAL TRANSACTIONS - BAD DEBT WRITTEN OFF
    if ($approval->module_id == 730) {
        $id = $approval->row_id;
        \DB::table('acc_general_journal_transactions')->where('id', $id)->update(['approved' => 1, 'posted' => 1]);
        $details = \DB::table('acc_general_journals')->where('transaction_id', $id)->get();
        $db = new DBEvent;
        $db->setTable('acc_general_journals');
        foreach ($details as $d) {
            $db->postDocument($d->id);
        }
        $db->postDocumentCommit();
        $account_id = $details->pluck('account_id')->first();
        $db->setDebtorBalance($account_id, true);
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

        rebuild_approval_notifications();

        return json_alert('Document approved.');
    }

    // CASH PAYMENTS
    if ($approval->module_id == 1837) {
        $id = $approval->row_id;
        \DB::table('acc_cashbook_transactions')->where('id', $id)->update(['approved' => 1]);
        $trx = \DB::table('acc_cashbook_transactions')->where('id', $id)->get()->first();
        $db = new DBEvent;
        $db->setTable('acc_cashbook_transactions');
        $db->postDocument($trx->id);

        $db->postDocumentCommit();
        if ($trx->account_id) {
            $db->setDebtorBalance($trx->account_id, true);
        }
        if ($trx->supplier_id) {
            $db->setCreditorBalance($trx->supplier_id);
        }
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

        rebuild_approval_notifications();

        return json_alert('Document approved.');
    }

    // PRODUCT DELETE
    if ($approval->module_id == 71 && str_contains($approval->title, ' delete')) {

        $id = $approval->row_id;
        $data = ['id' => $id, 'manager_override' => 1];
        $url = get_menu_url_from_table('crm_products').'/delete';

        \DB::table('crm_products')->where('id', $id)->update(['delete_approved' => 1]);

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postDelete($request);
        $result_data = $result->getData();

        if ($result_data->message && str_contains($result_data->message, 'Deleted')) {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }
        rebuild_approval_notifications();

        return $result;
    }

    // ACCOUNT CANCEL
    if ($approval->module_id == 343 && str_contains($approval->title, 'Cancel')) {

        $id = $approval->row_id;
        $data = ['id' => $id, 'manager_override' => 1];
        $url = get_menu_url_from_table('crm_accounts').'/cancel';

        \DB::table('crm_accounts')->where('id', $id)->update(['cancel_approved' => 1]);

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postCancel($request);
        $result_data = $result->getData();
        if ($result_data->status == 'success') {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }
        if ($result_data->message && str_contains($result_data->message, 'Account already deleted.')) {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }

        rebuild_approval_notifications();

        return $result;
    }
    // ACCOUNT DELETE
    if ($approval->module_id == 343 && str_contains($approval->title, 'Delete')) {

        $id = $approval->row_id;
        \DB::table('crm_accounts')->where('id', $id)->update(['cancel_approved' => 1]);
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        delete_account($id);
        rebuild_approval_notifications();

        return $result;
    }

    // ALL MODULE DELETIONS
    if (str_contains($approval->title, 'Delete')) {
        $id = $approval->row_id;
        $data = ['id' => $id, 'manager_override' => 1];
        $url = get_menu_url($approval->module_id).'/delete';

        \DB::connection('default')->table('crm_approvals')->where('id', $approval->id)->update(['approved' => 1]);

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postDelete($request);
        $result_data = $result->getData();

        if ($result_data->message && str_contains($result_data->message, 'Deleted')) {
            \DB::connection('default')->table('crm_approvals')->where('id', $approval->id)->update(['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }
        rebuild_approval_notifications();

        return $result;
    }
}

function button_approvals_process($request)
{

    $approval = \DB::table('crm_approvals')->where('id', $request->id)->get()->first();

    if (empty($approval) || empty($approval->id)) {
        return json_alert('Invalid Id', 'warning');
    }
    $approvals_url = get_menu_url_from_table('crm_approvals');
    if (is_main_instance() && $approval->instance_id != session('instance')->id) {
        $instance = \DB::table('erp_instances')->where('id', $approval->instance_id)->get()->first();
        $response = (new \GuzzleHttp\Client)->get('https://'.$instance->domain_name.'/process_approval/'.$approval->external_id.'/'.session('user_id'));

        schedule_import_approvals();

        return response()->json(['status' => 'success', 'message' => 'Processed']);
    }

    if (! is_superadmin()) {
        if (empty($approval->notes)) {
            return json_alert('Note required', 'warning');
        }
    }

    if (! empty($approval->post_data)) {
        $request = json_decode($approval->post_data);

        $db = new \DBEvent($approval->module_id, $settings);

        $result = $db->save($request);
        if ($result instanceof \Illuminate\Http\JsonResponse) {

            return $result;
        } elseif (! is_array($result) || empty($result['id'])) {

            return response()->json(['status' => 'warning', 'message' => $result]);
        } else {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

            return response()->json(['status' => 'success', 'message' => 'Record saved']);
        }
    }

    // BILLING
    if ($approval->module_id == 744) {
        $billing = \DB::table('acc_billing')->where('id', $approval->row_id)->get()->first();

        if ($billing->approved) {
            \DB::table('crm_approvals')->where('row_id', $billing->id)->where('module_id', 744)->update(['processed_at' => date('Y-m-d H:i:s'), 'processed' => 1, 'processed_by' => get_user_id_default()]);

            return json_alert('Billing already approved');
        }

        if ($billing->num_emails_success > 0) {
            return json_alert('Billing cannot be changed, billing emails already sent');
        }
        \DB::table('crm_documents')->where('docdate', $billing->billing_date)->where('billing_type', $billing->billing_type)->where('reversal_id', 0)->whereIn('doctype', ['Quotation', 'Order'])->update(['doctype' => 'Tax Invoice']);

        if (session('instance')->id == 2) {
            //credit rentals
            $credit_rental_space_ids = \DB::table('crm_rental_spaces')->where('has_lease', 'Internal')->pluck('id')->toArray();
            $credit_rental_account_ids = \DB::table('crm_rental_leases')->whereIn('rental_space_id', $credit_rental_space_ids)->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();

            $cloudtelecoms_invoice_ids = \DB::table('crm_documents')
                ->where('reversal_id', 0)
                ->whereIn('account_id', $credit_rental_account_ids)
                ->where('docdate', $billing->billing_date)
                ->where('doctype', 'Tax Invoice')
                ->where('billing_type', $billing->billing_type)
                ->pluck('id')->toArray();
            foreach ($cloudtelecoms_invoice_ids as $invoice_ids) {
                create_credit_note_from_invoice($invoice_ids);
            }
        }

        \DB::table('acc_billing')->where('id', $approval->row_id)->update(['approved' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        email_monthly_billing($billing->id);
        send_billing_summary($approval->row_id);
        module_log($approval->module_id, $id, 'approved');
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        if ($billing->billing_type == 'Monthly') {
            process_monthly_debit_orders();
        }

    }

    // STOCK TAKE
    if ($approval->module_id == 1939) {

        $id = $approval->row_id;
        \DB::table('crm_stock_take')->where('id', $id)->update(['approved' => 1]);

        module_log($approval->module_id, $id, 'approved');
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

        return json_alert('Stock take approved.');
    }

    // STOCK ADJUSTMENTS
    if ($approval->module_id == 703) {
        $id = $approval->row_id;
        \DB::table('acc_inventory')->where('id', $id)->update(['approved' => 1]);
        $inv = \DB::table('acc_inventory')->where('id', $id)->get()->first();

        $data = (array) $inv;
        $erp = new DBEvent;
        $erp->setTable('acc_inventory');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('acc_inventory')->where('id', $id)->update(['approved' => 0]);

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            pricelist_set_discounts();

            return json_alert('Stock adjustment approved.');
        } else {
            \DB::table('acc_inventory')->where('id', $id)->update(['approved' => 0]);

            return $result;
        }
    }

    // LEAVE REQUESTS
    if ($approval->module_id == 533) {
        $id = $approval->row_id;
        \DB::table('hr_leave')->where('id', $id)->update(['approved' => 1]);
        $leave = \DB::table('hr_leave')->where('id', $id)->get()->first();

        $data = (array) $leave;
        $erp = new DBEvent;
        $erp->setTable('hr_leave');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('hr_leave')->where('id', $id)->update(['approved' => 0]);

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

            return json_alert('Leave request approved.');
        } else {
            \DB::table('hr_leave')->where('id', $id)->update(['approved' => 0]);

            return $result;
        }
    }

    if ($approval->module_id == 1866) {
        $id = $approval->row_id;
        \DB::table('hr_loans')->where('id', $id)->update(['approved' => 1]);
        $leave = \DB::table('hr_loans')->where('id', $id)->get()->first();

        $data = (array) $leave;
        $erp = new DBEvent;
        $erp->setTable('hr_loans');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('hr_loans')->where('id', $id)->update(['approved' => 0]);

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

            return json_alert('Loan approved.');
        } else {
            \DB::table('hr_loans')->where('id', $id)->update(['approved' => 0]);

            return $result;
        }
    }
    // DEBTORS COMMITMENTS
    if ($approval->module_id == 709) {
        $id = $approval->row_id;
        \DB::table('crm_commitment_dates')->where('id', $id)->update(['approved' => 1]);
        $row = \DB::table('crm_commitment_dates')->where('id', $id)->get()->first();

        $data = (array) $row;
        $erp = new DBEvent;
        $erp->setTable('crm_commitment_dates');
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('crm_commitment_dates')->where('id', $id)->update(['approved' => 0]);

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

            return json_alert('Commitment approved.');
        } else {
            \DB::table('crm_commitment_dates')->where('id', $id)->update(['approved' => 0]);

            return $result;
        }
    }

    // IMPORT SUPPLIER DOCUMENTS
    if ($approval->module_id == 1861) {
        $id = $approval->row_id;
        \DB::table('crm_import_shipments')->where('id', $id)->update(['processed' => 1]);
        $inv = \DB::table('crm_import_shipments')->where('id', $id)->get()->first();

        $data = (array) $inv;
        $erp = new DBEvent;
        $erp->setModule(1861);
        $result = $erp->save($data);

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            \DB::table('crm_import_shipments')->where('id', $id)->update(['processed' => 0]);

            return $result;
        } elseif ($result && $result['id']) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            pricelist_set_discounts();

            return json_alert('Import Shipment approved.');
        } else {
            \DB::table('crm_import_shipments')->where('id', $id)->update(['processed' => 0]);

            return $result;
        }
    }

    // OrderS, CREDIT NOTES
    $approval_module_db_table = \DB::table('erp_cruds')->where('id', $approval->module_id)->pluck('db_table')->first();
    if ($approval_module_db_table == 'crm_documents' && str_contains($approval->title, 'Custom Price')) {
        $id = $approval->row_id;
        \DB::table('crm_documents')->where('id', $id)->update(['custom_prices_approved' => 1]);
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

        return json_alert('Custom Prices approved.');
    }

    if ($approval_module_db_table == 'crm_documents' && ! str_contains($approval->title, 'Delete')) {
        $id = $approval->row_id;
        $doctype = \DB::table('crm_documents')->where('id', $id)->pluck('doctype')->first();
        $data = ['id' => $id];
        $url = get_menu_url_from_module_id($approval->module_id).'/approve';

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postApproveTransaction($request);
        $new_doctype = \DB::table('crm_documents')->where('id', $id)->pluck('doctype')->first();

        if ($doctype != $new_doctype) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
            \DB::table('crm_approvals')->where('processed', 0)->where('module_id', $approval->module_id)->where('row_id', $approval->row_id)->delete();

            return json_alert('Document approved.');

        } else {

            return $result;
        }
    }

    // SUPPLIER ORDERS
    if ($approval->module_id == 354) {
        $id = $approval->row_id;
        $doctype = \DB::table('crm_supplier_documents')->where('id', $id)->pluck('doctype')->first();
        $data = ['id' => $id];
        $url = get_menu_url_from_table('crm_supplier_documents').'/approve';

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postApproveTransaction($request);

        $new_doctype = \DB::table('crm_supplier_documents')->where('id', $id)->pluck('doctype')->first();

        if ($doctype != $new_doctype) {
            module_log($approval->module_id, $id, 'approved');
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

            return json_alert('Document approved.');

        } else {

            return $result;
        }
    }

    // JOURNAL TRANSACTIONS - BAD DEBT WRITTEN OFF
    if ($approval->module_id == 730) {
        $id = $approval->row_id;
        \DB::table('acc_general_journal_transactions')->where('id', $id)->update(['approved' => 1, 'posted' => 1]);
        $details = \DB::table('acc_general_journals')->where('transaction_id', $id)->get();
        $db = new DBEvent;
        $db->setTable('acc_general_journals');
        foreach ($details as $d) {
            $db->postDocument($d->id);
        }
        $db->postDocumentCommit();
        $account_id = $details->pluck('account_id')->first();
        $db->setDebtorBalance($account_id, true);
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

        return json_alert('Document approved.');
    }

    // CASH PAYMENTS
    if ($approval->module_id == 1837) {
        $id = $approval->row_id;
        \DB::table('acc_cashbook_transactions')->where('id', $id)->update(['approved' => 1]);
        $trx = \DB::table('acc_cashbook_transactions')->where('id', $id)->get()->first();
        $db = new DBEvent;
        $db->setTable('acc_cashbook_transactions');
        $db->postDocument($trx->id);

        $db->postDocumentCommit();
        if ($trx->account_id) {
            $db->setDebtorBalance($trx->account_id, true);
        }
        if ($trx->supplier_id) {
            $db->setCreditorBalance($trx->supplier_id);
        }
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);

        return json_alert('Document approved.');
    }

    // PRODUCT DELETE
    if ($approval->module_id == 71 && str_contains($approval->title, ' delete')) {

        $id = $approval->row_id;
        $data = ['id' => $id, 'manager_override' => 1];
        $url = get_menu_url_from_table('crm_products').'/delete';

        \DB::table('crm_products')->where('id', $id)->update(['delete_approved' => 1]);

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postDelete($request);
        $result_data = $result->getData();

        if ($result_data->message && str_contains($result_data->message, 'Deleted')) {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }

        return $result;
    }

    // ACCOUNT CANCEL
    if ($approval->module_id == 343 && str_contains($approval->title, 'Cancel')) {

        $id = $approval->row_id;
        $data = ['id' => $id, 'manager_override' => 1];
        $url = get_menu_url_from_table('crm_accounts').'/cancel';

        \DB::table('crm_accounts')->where('id', $id)->update(['cancel_approved' => 1]);

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postCancel($request);
        $result_data = $result->getData();
        if ($result_data->status == 'success') {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }
        if ($result_data->message && str_contains($result_data->message, 'Account already deleted.')) {
            dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }

        return $result;
    }

    // ACCOUNT DELETE
    if ($approval->module_id == 343 && str_contains($approval->title, 'Delete')) {

        $id = $approval->row_id;
        \DB::table('crm_accounts')->where('id', $id)->update(['cancel_approved' => 1]);
        dbset('crm_approvals', 'id', $approval->id, ['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        delete_account($id);

        return $result;
    }

    // ALL MODULE DELETIONS
    if (str_contains($approval->title, 'Delete')) {
        $id = $approval->row_id;
        $data = ['id' => $id, 'manager_override' => 1];
        $url = get_menu_url($approval->module_id).'/delete';

        \DB::connection('default')->table('crm_approvals')->where('id', $approval->id)->update(['approved' => 1]);

        $request = Request::create($url, 'post', $data);
        $result = app('App\Http\Controllers\ModuleController')->postDelete($request);
        $result_data = $result->getData();

        if ($result_data->message && str_contains($result_data->message, 'Deleted')) {
            \DB::connection('default')->table('crm_approvals')->where('id', $approval->id)->update(['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => session('user_id')]);
        }

        return $result;
    }
}

function button_approvals_view_record($request)
{

    $approval = \DB::table('crm_approvals')->where('id', $request->id)->get()->first();
    $url = get_menu_url($approval->module_id);
    $url .= '?id='.$approval->row_id;
    if ($approval->module_id == 343) {
        $account = dbgetaccount($approval->row_id);
        $url .= '&status='.$account->status;
    }

    return redirect()->to($url);
}

function aftersave_inventory_add_approvals($request)
{
    $stock_adjustments = \DB::table('acc_inventory')->where('approved', 0)->get();
    foreach ($stock_adjustments as $stock_adjustment) {
        // only add qty adjustments to approvals
        if ($stock_adjustment->qty_new != $stock_adjustment->qty_current) {
            $exists = \DB::table('crm_approvals')->where('created_at', 'like', date('Y-m-d').'%')->where('module_id', 703)->where('row_id', $stock_adjustment->id)->count();
            if (! $exists) {
                $product_code = dbgetcell('crm_products', 'id', $stock_adjustment->product_id, 'code');
                $data = [
                    'module_id' => 703,
                    'row_id' => $stock_adjustment->id,
                    'title' => 'Stock Adjustment '.$product_code.' #'.$stock_adjustment->id,
                    'processed' => 0,
                    'requested_by' => get_user_id_default(),
                ];
                (new \DBEvent)->setTable('crm_approvals')->save($data);
            }
        } elseif (empty($stock_adjustment->supplier_document_id) && $stock_adjustment->cost_new != $stock_adjustment->cost_current) {

            // auto approve cost adjustments
            // \DB::table('acc_inventory')->where('id',$stock_adjustment->id)->update(['approved'=>1]);
            $exists = \DB::table('crm_approvals')->where('created_at', 'like', date('Y-m-d').'%')->where('module_id', 703)->where('row_id', $stock_adjustment->id)->count();
            if (! $exists) {
                $product_code = dbgetcell('crm_products', 'id', $stock_adjustment->product_id, 'code');
                $data = [
                    'module_id' => 703,
                    'row_id' => $stock_adjustment->id,
                    'title' => 'Stock Cost Adjustment '.$product_code.' #'.$stock_adjustment->id,
                    'processed' => 0,
                    'requested_by' => get_user_id_default(),
                ];
                (new \DBEvent)->setTable('crm_approvals')->save($data);
            }
        } else {
            // auto approve cost adjustments
            // \DB::table('acc_inventory')->where('id',$stock_adjustment->id)->update(['approved'=>1]);
        }
    }
}

function account_cancel_approvals()
{
    $accounts = \DB::table('crm_accounts')->where('account_status', 'Cancelled')->where('cancel_approved', 0)->get();
    foreach ($accounts as $account) {
        $exists = \DB::table('crm_approvals')->where('module_id', 343)->where('row_id', $account->id)->count();
        if (! $exists) {

            $name = $account->company;
            $data = [
                'module_id' => 343,
                'row_id' => $account->id,
                'title' => 'Account Cancel '.$name.' #'.$account->id,
                'processed' => 0,
                'requested_by' => get_user_id_default(),
            ];
            (new \DBEvent)->setTable('crm_approvals')->save($data);
        }
    }
}

function aftersave_hrleave_add_approvals($request)
{
    if (is_superadmin()) {
        \DB::table('hr_leave')->where('id', $request->id)->update(['approved' => 1]);
    } else {

        $hr_leaves = \DB::table('hr_leave')->where('approved', 0)->get();
        foreach ($hr_leaves as $hr_leave) {
            $exists = \DB::table('crm_approvals')->where('module_id', 533)->where('row_id', $hr_leave->id)->count();
            if (! $exists) {
                $name = dbgetcell('hr_employees', 'id', $hr_leave->employee_id, 'name');
                $title = $name.' '.$hr_leave->date_from;
                if (! empty($hr_leave->date_to) && $hr_leave->date_from != $hr_leave->date_to) {
                    $title .= ' - '.$hr_leave->date_to;
                }
                $data = [
                    'module_id' => 533,
                    'row_id' => $hr_leave->id,
                    'title' => $title,
                    'processed' => 0,
                    'requested_by' => get_user_id_default(),
                ];
                (new \DBEvent)->setTable('crm_approvals')->save($data);
            }
        }
    }
}

function aftersave_commitment_dates_add_approvals($request)
{
    \DB::table('crm_commitment_dates')->where('id', $request->id)->update(['approved' => 1]);
    /*
    if(is_superadmin()){
        \DB::table('crm_commitment_dates')->where('id',$request->id)->update(['approved'=> 1]);
    }else{
        $commitment_dates = \DB::table('crm_commitment_dates')->where('approved',0)->get();
        foreach($commitment_dates as $commitment_date){
            $exists = \DB::table('crm_approvals')->where('module_id',709)->where('row_id',$commitment_date->id)->count();
            if(!$exists){
                $name = dbgetcell('crm_accounts', 'id', $commitment_date->account_id, 'company');
                $title = $name.' '.$commitment_date->commitment_date;

                $data = [
                    'module_id' => 709,
                    'row_id' => $commitment_date->id,
                    'title' => $title,
                    'processed' => 0,
                    'requested_by' => get_user_id_default()
                ];
                (new \DBEvent())->setTable('crm_approvals')->save($data);
            }
        }
    }
    */
}

function aftersave_import_shipments_add_approvals($request)
{

    $import_shipments = \DB::table('crm_import_shipments')->where('processed', 0)->get();

    foreach ($import_shipments as $import_shipment) {
        $exists = \DB::table('crm_approvals')->where('module_id', 1861)->where('row_id', $import_shipment->id)->count();

        if (! $exists) {
            $data = [
                'module_id' => 1861,
                'row_id' => $import_shipment->id,
                'title' => 'Import Shipment #'.$import_shipment->id,
                'processed' => 0,
                'requested_by' => get_user_id_default(),
            ];

            (new \DBEvent)->setTable('crm_approvals')->save($data);
        }
    }
}

function afterdelete_approvals_rebuild_notifications($request)
{

    rebuild_approval_notifications();
}

function afterdelete_approvals_remove_billing($request)
{

    $approval = \DB::table('crm_approvals')->where('id', $request->id)->get()->first();
    if ($approval->module_id == 744) {
        if (str_contains($approval->title, 'Monthly')) {
            $billing = \DB::table('acc_billing')->where('id', $approval->row_id)->get()->first();
            if (! $billing->approved) {
                $doc_ids = \DB::table('crm_documents')
                    ->whereIn('doctype', ['Quotation'])
                    ->where('docdate', $billing->billing_date)
                    ->where('billing_type', 'Monthly')
                    ->pluck('id')->toArray();

                if (count($doc_ids) > 0) {
                    foreach ($doc_ids as $doc_id) {
                        \DB::table('crm_document_lines')->where('document_id', $doc_id)->delete();
                        \DB::table('crm_documents')->where('id', $doc_id)->delete();
                    }
                }
            }
        }
    }
}

function afterdelete_remove_credit_note_draft($request)
{

    $approval = \DB::table('crm_approvals')->where('id', $request->id)->get()->first();

    if (str_contains($approval->title, 'Credit Note Draft')) {
        $doc = \DB::table('crm_documents')->where('id', $approval->row_id)->get()->first();

        if ($doc->doctype == 'Credit Note Draft') {
            void_transaction('crm_documents', $doc->id, $doc->doctype);
        }
    }
}
function afterdelete_remove_inventory_adjustment($request)
{

    $approval = \DB::table('crm_approvals')->where('id', $request->id)->get()->first();

    if ($approval->module_id == 703) {
        \DB::table('acc_inventory')->where('id', $approval->row_id)->delete();
    }
}

function afterdelete_remove_account_cancellation($request)
{

    $approval = \DB::table('crm_approvals')->where('id', $request->id)->get()->first();

    if ($approval->module_id == 343 && str_contains($approval->title, 'Cancel')) {
        $account_id = $approval->row_id;
        \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_process_cancellation' => 0, 'cancelled' => 0, 'cancel_approved' => 0, 'account_status' => \DB::raw('status'), 'cancel_date' => null]);
    }
}

function process_document_approvals()
{

    // update automatically approved documents
    $system_user_id = get_system_user_id();
    $approval_doctypes = \DB::table('acc_doctypes')->where('approve_manager', 1)->get();
    foreach ($approval_doctypes as $approval_doctype) {

        $module_id = \DB::table('erp_cruds')->where('db_table', $approval_doctype->doctable)->pluck('id')->first();
        $doc_module_id = $module_id;

        $document_approvals = \DB::table('crm_approvals')->where('module_id', $module_id)->where('title', 'not like', '%Custom Price%')->where('title', 'like', '%'.$approval_doctype->doctype.'%')->where('processed', 0)->get();

        foreach ($document_approvals as $appoval) {
            $exists = \DB::table($approval_doctype->doctable)->where('id', $appoval->row_id)->count();
            if (! $exists) {
                \DB::table('crm_approvals')->where('id', $appoval->id)->where('title', 'not like', '%Custom Price%')->delete();
            } else {
                $converted = \DB::table($approval_doctype->doctable)->where('id', $appoval->row_id)->where('doctype', '!=', $approval_doctype->doctype)->count();

                if ($converted) {
                    \DB::table('crm_approvals')->where('title', 'not like', '%Custom Price%')->where('id', $appoval->id)->update(['processed' => 1, 'processed_at' => date('Y-m-d H:i:s'), 'processed_by' => $system_user_id]);
                }
            }
        }
        // insert new approvals
        $new_approvals = \DB::table($approval_doctype->doctable)->where('doctype', $approval_doctype->doctype)->get();
        foreach ($new_approvals as $approval) {
            $account_type = dbgetcell('crm_accounts', 'id', $approval->account_id, 'type');

            $add_approval = false;

            $exists = \DB::table('crm_approvals')->where('title', 'not like', '%Custom Price%')->where('module_id', $module_id)->where('row_id', $approval->id)->count();
            if (! $exists && ! empty($approval->approval_requested_by)) {
                $add_approval = true;
            }

            if ($add_approval) {
                $title = $approval->doctype.' #'.$approval->id.' - '.$approval->docdate;
                if (! empty($approval->account_id)) {
                    $title .= ' - '.dbgetcell('crm_accounts', 'id', $approval->account_id, 'company');
                }
                if (! empty($approval->supplier_id)) {
                    $title .= ' - '.dbgetcell('crm_suppliers', 'id', $approval->supplier_id, 'company');
                }
                $requested_by = 0;
                if (! empty($approval->approval_requested_by)) {
                    $requested_by = $approval->approval_requested_by;
                } elseif (! empty($approval->user_id)) {
                    $requested_by = $approval->user_id;
                }

                $data = [
                    'module_id' => $module_id,
                    'row_id' => $approval->id,
                    'title' => $title,
                    'processed' => 0,
                    'requested_by' => $requested_by,
                ];

                (new \DBEvent)->setTable('crm_approvals')->save($data);
            }
        }
    }
}

function aftersave_approval_send_notification($request)
{
    /*
    if($request->new_record){
        $approve_url = get_menu_url_from_table('crm_approvals');
        $module_name = app('erp_config')['modules']->where('id', $request->module_id)->pluck('name')->first();
        $extra_data = ['row_id' => $request->id, 'type'=>'approval'];
         // $link =  url($approve_url.'?id='.$approval->id);

        $link_url = get_menu_url_from_module_id($approval->module_id);
        $link =  url($link_url.'?id='.$approval->row_id);
        $extra_data['reject_link'] =  url($approve_url.'/delete/'.$request->id);
        $button_id = \DB::table('erp_menu')->where('render_module_id',1859)->where('ajax_function_name','button_approvals_process')->pluck('id')->first();
        $approve_link = '/'.$approve_url.'/button/'.$button_id.'/'.$request->id;

        $extra_data['approve_link'] = url($approve_link);

        erp_notify('approval'.$request->id, 1,'New Approval',$module_name.'<br>'.$request->title,$link,$extra_data);
    }
    */
}

function aftersave_approvals_assign_to_user($request)
{

    $beforesave_row = session('event_db_record');
    if ($request->called && $beforesave_row && ($beforesave_row->called != $request->called)) {
        $approval = \DB::connection('default')->table('crm_approvals')->where('id', $request->id)->get()->first();
        if (empty($approval->notes)) {
            return 'Note required';
        }

        $last_call = \DB::connection('default')->table('crm_accounts')->where('id', $approval->row_id)->pluck('last_call')->first();
        \DB::connection('default')->table('crm_approvals')->where('id', $request->id)->update(['last_call' => $last_call]);
    }

    approvals_assign_to_user();
}

function approvals_assign_to_user()
{
    $approvals = \DB::connection('default')->table('crm_approvals')->where('module_id', 343)->where('processed', 0)->where('is_deleted', 0)->get();
    $accounting_user_id = \DB::connection('default')->table('erp_users')->where('role_id', 68)->where('is_deleted', 0)->pluck('id')->first();
    if (! $accounting_user_id) {
        $accounting_user_id = 1;
    }

    foreach ($approvals as $approval) {
        if (str_contains($approval->title, 'Delete') || str_contains($approval->title, 'Cancel')) {
            $account_type = \DB::connection('default')->table('crm_accounts')->where('id', $approval->row_id)->pluck('type')->first();
            if ($account_type == 'lead' || $approval->called) {

                \DB::connection('default')->table('crm_approvals')->where('id', $approval->id)->update(['assigned_to_id' => 1]);
            } else {
                \DB::connection('default')->table('crm_approvals')->where('id', $approval->id)->update(['assigned_to_id' => $accounting_user_id]);
            }
        }
    }
}

function rebuild_approval_notifications()
{
    return false;
    $approvals = \DB::connection('default')->table('crm_approvals')->where('processed', 0)->where('is_deleted', 0)->get();
    $approval_ids = $approvals->pluck('id')->toArray();
    $collection = collect($approval_ids);

    $prefixedIds = $collection->map(function ($id) {
        return 'approval'.$id;
    });

    $references = $prefixedIds->toArray();
    \DB::connection('default')->table('notifications')->where('reference', 'LIKE', '%approval%')->delete();
    //\DB::connection('default')->table('notifications')->where('reference','LIKE','%approval%')->whereNotIn('reference',$references)->delete();

    // rebuild

    foreach ($approvals as $approval) {
        $approve_url = get_menu_url_from_table('crm_approvals');
        $module_name = app('erp_config')['modules']->where('id', $approval->module_id)->pluck('name')->first();
        $extra_data = ['row_id' => $approval->id, 'type' => 'approval'];
        // $link =  url($approve_url.'?id='.$approval->id);

        $link_url = get_menu_url_from_module_id($approval->module_id);
        $link = url($link_url.'?id='.$approval->row_id);

        $extra_data['reject_link'] = url($approve_url.'/delete/'.$approval->id);
        $button_id = \DB::connection('default')->table('erp_menu')->where('render_module_id', 1859)->where('ajax_function_name', 'button_approvals_process')->pluck('id')->first();
        $approve_link = '/'.$approve_url.'/button/'.$button_id.'/'.$approval->id;

        $extra_data['approve_link'] = url($approve_link);

        erp_notify('approval'.$approval->id, 1, 'New Approval', $module_name.'<br>'.$approval->title, $link, $extra_data);
    }

}
