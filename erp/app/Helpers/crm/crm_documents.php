<?php

function aftersave_credit_note_remove_activations($request)
{
    $document = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
    if ($document->doctype == 'Credit Note') {

        // CREDIT PENDING ACTIVATIONS
        $document_lines = \DB::table('crm_document_lines')
            ->where('document_id', $document->id)
            ->get();
        foreach ($document_lines as $document_line) {
            \DB::table('sub_activations')->where('invoice_id', $document->reversal_id)->where('status', 'Pending')->where('invoice_line_id', $document_line->original_line_id)->update(['status' => 'Credited', 'credit_note_line_id' => $document_line->id]);
            $product_type = \DB::table('crm_products')->where('id', $line->product_id)->pluck('type')->first();
            if ($product_type == 'Stock') {
                $product_delivered = \DB::table('sub_activations')->where('invoice_id', $document->reversal_id)->where('status', 'Enabled')->where('invoice_line_id', $document_line->original_line_id)->count();
                if ($product_delivered) {
                    $ret_data = [
                        'product_id' => $document_line->product_id,
                        'invoice_line_id' => $document_line->original_line_id,
                        'credit_note_line_id' => $document_line->id,
                        'qty' => $document_line->qty,
                        'account_id' => $document->account_id,
                        'invoice_id' => $document->id,
                        'status' => 'Pending',
                        'created_by' => $document->created_by,
                        'created_at' => date('Y-m-d H:i:s'),
                        'provision_type' => 'product_return',
                    ];

                    dbinsert('sub_activations', $ret_data);
                }
            }
            // CREDIT SUBSCRIPTIONS FROM ORIGINAL INVOICE
            $subscription_ids = \DB::table('sub_services')->where('invoice_id', $document->reversal_id)->where('status', '!=', 'Deleted')->where('invoice_line_id', $document_line->original_line_id)->pluck('id')->toArray();
            foreach ($subscription_ids as $subscription_id) {
                $sub = \DB::table('sub_services')->where('id', $subscription_id)->get()->first();
                $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
                if ($product->deactivate_plan_id > 0) {
                    $name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();
                    $deactivation_data = [
                        'provision_type' => $name,
                        'account_id' => $sub->account_id,
                        'product_id' => $sub->product_id,
                        'qty' => ($sub->qty) ? $sub->qty : 1,
                        'detail' => $sub->detail,
                        'subscription_id' => $sub->id,
                        'status_provision_type' => 1,
                    ];
                    $deactivation_exists = \DB::table('sub_activations')->where($deactivation_data)->count();

                    if (! $deactivation_exists) {
                        $insert_data = $deactivation_data;
                        $insert_data['status'] = 'Pending';
                        $insert_data['created_at'] = date('Y-m-d H:i:s');

                        \DB::table('sub_activations')->insert($insert_data);

                    }
                }
            }
            // CREDIT SUBSCRIPTIONS FROM MONTHLY BILLING INVOICE
            if ($document_line->subscription_id) {
                $subscription_ids = \DB::table('sub_services')->where('id', $document_line->subscription_id)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
                foreach ($subscription_ids as $subscription_id) {
                    $sub = \DB::table('sub_services')->where('id', $subscription_id)->get()->first();
                    $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
                    if ($product->deactivate_plan_id > 0) {
                        $name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();
                        $deactivation_data = [
                            'provision_type' => $name,
                            'account_id' => $sub->account_id,
                            'product_id' => $sub->product_id,
                            'qty' => ($sub->qty) ? $sub->qty : 1,
                            'detail' => $sub->detail,
                            'subscription_id' => $sub->id,
                            'status_provision_type' => 1,
                        ];
                        $deactivation_exists = \DB::table('sub_activations')->where($deactivation_data)->count();

                        if (! $deactivation_exists) {
                            $insert_data = $deactivation_data;
                            $insert_data['status'] = 'Pending';
                            $insert_data['created_at'] = date('Y-m-d H:i:s');
                            \DB::table('sub_activations')->insert($insert_data);
                        }

                    }
                }
            }
        }

        // remove airtime

        $airtime_product_ids = get_activation_type_product_ids('airtime_prepaid');
        $airtime_contract_product_ids = get_activation_type_product_ids('airtime_contract');
        $has_prepaid_airtime = \DB::table('crm_document_lines')
            ->where('document_id', $document->id)
            ->whereIn('product_id', $airtime_product_ids)
            ->count();
        $has_contract_airtime = \DB::table('crm_document_lines')
            ->where('document_id', $document->id)
            ->whereIn('product_id', $airtime_contract_product_ids)
            ->count();
        if ($has_prepaid_airtime || $has_contract_airtime) {
            $processed = \DB::connection('pbx')->table('p_airtime_history')->where('type', 'airtime_credited_'.$document->id)->count();
            if (! $processed) {
                if ($has_prepaid_airtime) {
                    $invoice_lines = \DB::table('crm_document_lines')
                        ->where('document_id', $document->id)
                        ->whereIn('product_id', $airtime_product_ids)
                        ->get();
                }
                if ($has_contract_airtime) {
                    $invoice_lines = \DB::table('crm_document_lines')
                        ->where('document_id', $document->id)
                        ->whereIn('product_id', $airtime_contract_product_ids)
                        ->get();
                }
                $expired_total = 0;
                foreach ($invoice_lines as $invoice_line) {
                    $expired_total .= ($invoice_line->qty * $invoice_line->price);
                }
                $service_account_id = ($document->reseller_user > 0) ? $document->reseller_user : $document->account_id;
                $account = dbgetaccount($service_account_id);
                if ($account->domain_uuid) {
                    $airtime_balance = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $account->domain_uuid)->pluck('balance')->first();
                    if ($document->tax > 0) {
                        $expired_total = $expired_total * 1.15;
                    }
                    $new_balance = $airtime_balance - $expired_total;
                    $airtime_history = [
                        'created_at' => date('Y-m-d'),

                        'domain_uuid' => $account->domain_uuid,
                        'total' => $expired_total * -1,
                        'balance' => $new_balance,
                        'type' => 'airtime_credited_'.$document->id,
                    ];
                    \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
                    \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $account->domain_uuid)->decrement('balance', $expired_total);
                }

            }
        }
    }
}

function beforesave_documents_check_debtor_status($request)
{
    // if(!str_contains($request->doctype,'Credit Note') && !empty(session('user_id'))  && !str_contains($request->reference,'Migrate')){
    //     $account = dbgetaccount($request->account_id);
    //     if($account->type!='lead' and $account->balance > 1 and $account->commitment == 0){
    //         return 'Debtors not on current - Account on hold - cannot place orders';
    //     }
    // }
}

function beforesave_documents_call_center_commitment($request)
{
    if (session('role_level') == 'Admin' && $request->doctype == 'Order' && empty($request->commitment_date)) {
        return 'Commitment Date Required';
    }
}

function invoice_requires_contract_debit_order($document_id)
{
    $contract_lines = \DB::table('crm_document_lines')->where('document_id', $document_id)->where('contract_period', 12)->count();
    if ($contract_lines > 0) {
        return true;
    }

    return false;
}

function invoice_requires_debit_order($document_id)
{
    $account_id = \DB::table('crm_documents')->where('id', $document_id)->pluck('account_id')->first();
    $active_debit_order = account_has_debit_order($account_id);
    if ($active_debit_order) {
        return false;
    }

    $contract_period_set = \DB::table('crm_documents')->where('id', $document_id)->where('contract_period', '>', 0)->count();
    if ($contract_period_set) {
        return true;
    }

    $activation_type_ids = \DB::table('sub_activation_plans')->where('type', 'Debitorder')->where('status', 'Enabled')->pluck('activation_type_id')->unique()->toArray();
    if (count($activation_type_ids) > 0) {
        $debit_order_product_ids = \DB::table('crm_products')->where('status', 'Enabled')->whereIn('provision_plan_id', $activation_type_ids)->pluck('id')->toArray();
        $document_requires_debit_order = \DB::table('crm_document_lines')->where('document_id', $document_id)->whereIn('product_id', $debit_order_product_ids)->count();
        if ($document_requires_debit_order > 0) {
            return true;
        }
    }

    $contract_product_ids = \DB::table('crm_products')->where('status', 'Enabled')->where('contract_period', '>', 0)->pluck('id')->toArray();
    if (count($contract_product_ids) > 0) {
        $document_requires_debit_order = \DB::table('crm_document_lines')->where('document_id', $document_id)->whereIn('product_id', $contract_product_ids)->count();
        if ($document_requires_debit_order > 0) {
            return true;
        }
    }

    return false;
}

function update_sales_commissions($document_id = false)
{
    $query = "UPDATE crm_document_lines
            JOIN crm_documents ON crm_document_lines.document_id = crm_documents.id
            JOIN crm_products ON crm_document_lines.product_id = crm_products.id
            SET crm_document_lines.commission = 
                CASE 
                    WHEN crm_products.type = 'stock' THEN 
                        CASE 
                            WHEN crm_documents.doctype = 'Credit Note' THEN -0.025 * crm_document_lines.zar_sale_total
                            ELSE 0.025 * crm_document_lines.zar_sale_total
                        END
                    ELSE 
                        CASE 
                            WHEN crm_documents.doctype = 'Credit Note' THEN -0.05 * crm_document_lines.zar_sale_total
                            ELSE 0.05 * crm_document_lines.zar_sale_total
                        END
                END;";
    if ($document_id) {
        $query .= ' WHERE crm_document_lines.document_id = '.$document_id;
    }
    \DB::statement($query);
}

function schedule_update_sales_commissions()
{
    update_sales_commissions();
}

function beforesave_documents_check_moq($request)
{
    if (! str_contains($request->doctype, 'Credit Note') && ! str_contains($request->reference, 'Migrate') && empty($request->billing_type)) {
        foreach ($request->product_id as $index => $id) {

            $product = \DB::table('crm_products')->select('code', 'name', 'moq')->where('id', $id)->get()->first();
            if ($product->moq > 0 && $product->moq > $request->qty[$index]) {
                return $product->name.' minimum order quantity: '.$product->moq;
            }
        }
    }
}

function beforesave_documents_address_required($request)
{
    if (session('instance')->id == 1) {
        $account = dbgetaccount($request->account_id);
        if (empty($account->address)) {
            return 'Company address required';
        }
        if (strlen($account->address) < 15) {
            return 'Company address invalid, address needs to be at least 15 characters';
        }
    }
}
function beforesave_documents_contract_required($request)
{
    if (! empty(session('user_id')) && empty($request->bill_frequency) && ! str_contains($request->reference, 'Migrate')) {
        return 'Bill Frequency Required';
    }

    if (! empty(session('user_id')) && ! str_contains($request->reference, 'Migrate') && ! $request->type = 'Credit Note Draft') {
        $contract_products = \DB::table('crm_products')->where('status', 'Enabled')->where('contract_period', '>', 0)->get();
        $contract_product_ids = $contract_products->pluck('id')->toArray();
        $has_contract_product = false;
        foreach ($request->product_id as $index => $value) {
            if (in_array($value, $contract_product_ids) && empty($request->contract_period[$index])) {
                $name = $contract_products->where('id', $value)->pluck('code')->first();

                return $name.' requires contract';
            }
        }

        if ($request->bill_frequency == 1) {
            $annual_product_ids = \DB::table('crm_products')->where('status', 'Enabled')->where('product_bill_frequency', '>', 1)->pluck('id')->toArray();
            $requires_contract = false;

            foreach ($request->product_id as $product_id) {
                if (in_array($product_id, $annual_product_ids)) {
                    $requires_contract = true;
                }
            }

            if ($requires_contract) {
                return 'Annual Required';
            }
        }

    }

}

function beforedelete_document_quotes_check_is_billing($request)
{
    $doc = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
    if ($doc->doctype == 'Quotation' && $doc->billing_type == 'Monthly') {
        return 'Monthly billing quotes cannot be deleted';
    }
}

function button_quotations_update_price_and_resend($request)
{
    $quotes = \DB::table('crm_documents')->where('doctype', 'Quotation')->get();
    foreach ($quotes as $quote) {

        //update pricing
        $lines = \DB::table('crm_document_lines')->where('document_id', $quote->id)->get();
        $subtotal = 0;
        foreach ($lines as $l) {
            $pricing = pricelist_get_price($quote->account_id, $l->product_id);
            $subtotal += $pricing->full_price * $l->qty;
            \DB::table('crm_document_lines')->where('id', $l->id)->update(['price' => $pricing->full_price, 'full_price' => $pricing->full_price]);
        }

        $admin = dbgetaccount(1);
        $total = $subtotal;
        $tax = 0;
        if ($admin->vat_enabled) {
            $total = $subtotal * 1.15;
            $tax = $total - $subtotal;
        }
        \DB::table('crm_documents')->where('id', $quote->id)->update(['tax' => $tax, 'total' => $total]);

        //send email
        //if(date('Y-m-d',strtotime($quote->docdate)) < date('Y-m-01')){
        email_document_pdf($quote->id);
        // }
    }

    return 'Quotes sent';
}

function aftersave_documents_check_custom_prices($request)
{
    /*
    //aa($request->all());
    $document = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
    if(!$document->custom_prices_approved && empty($request->billing_type)){
        $has_custom_prices = 0;

        foreach ($request->product_id as $i => $product_id){

            $pricelist_price = pricelist_get_price($request->account_id, $product_id, $request->qty[$i]);
            $pricelist_price = currency($pricelist_price->price);
            if($pricelist_price != $request->price[$i]){
               // aa($product_id);
              //  aa($pricelist_price);
              //  aa($request->price[$i]);
                $has_custom_prices = 1;
            }

        }

        if($has_custom_prices){
            \DB::table('crm_documents')->where('id',$request->id)->where('doctype','Order')->update(['doctype' => 'Quotation']);
            \DB::table('crm_documents')->where('id',$request->id)->update(['custom_prices' => 1]);
            $exists = \DB::table('crm_approvals')->where('title','like','%Custom Price%')->where('row_id',$request->id)->where('module_id',353)->where('processed',0)->count();
            if(!$exists){

                $data = [
                'module_id' => 353,
                'row_id' => $request->id,
                'title' => $request->doctype.' #'.$request->id. ' Custom Price',
                'processed' => 0,
                'requested_by' => get_user_id_default(),
                ];
                (new \DBEvent())->setTable('crm_approvals')->save($data);
            }
        }else{
            \DB::table('crm_documents')->where('id',$request->id)->update(['custom_prices' => 0]);
        }
    }
    */
}

function button_documents_request_approval($request)
{

    $doctype = \DB::table('crm_documents')->where('id', $request->id)->pluck('doctype')->first();
    if ($doctype == 'Quotation') {
        \DB::table('crm_documents')->where('id', $request->id)->update(['doctype' => 'Order']);
        $doctype = 'Order';
    }

    if ($doctype == 'Order' || $doctype == 'Credit Note Draft') {
        \DB::table('crm_documents')->where('id', $request->id)->update(['approval_requested_by' => session('user_id')]);
        process_document_approvals();

        return json_alert('Request submitted');
    } else {

        return json_alert('Invalid doctype', 'warning');
    }
}

function button_documents_view_zar_invoice($request)
{
    $row = \DB::table('crm_documents')->where('id', $request->id)->get()->first();

    $pdf = document_zar_pdf($row->id);
    $file = str_replace(' ', '_', ucfirst($row->doctype).' '.$row->id).'_rands.pdf';
    $filename = attachments_path().$file;

    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);

    $filename = attachments_url().$file;

    $data['pdf'] = $filename;
    $data['menu_name'] = $file;
    $data['doc_id'] = $request->id;

    return view('__app.components.pdf', $data);
}

function button_quote_followup_email($request)
{
    $id = $request->id;
    $document = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
    if ($document->doctype != 'Quotation') {
        return json_alert('Invalid doctype', 'warning');
    }

    $account = dbgetaccount($document->account_id);
    $data = [];
    $data['internal_function'] = 'quote_followup';
    $data['reference'] = '#'.$document->id;
    //$data['test_debug'] = 1;
    if (! empty($quote->reference)) {
        $data['reference'] .= ' '.$document->reference;
    }

    $pdf = document_pdf($id);

    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $document->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $document->doctype;
    }

    $document->doctype = $doctype_label;

    $file = $document->doctype.'_'.$document->id.'.pdf';
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);
    $attachments[] = $file;

    $data['attachments'] = $attachments;
    erp_process_notification($document->account_id, $data);

    return json_alert('Quote follow-up email sent', 'success');
}

function aftersave_documents_set_salesman($request)
{

    $salesmanIds = get_salesman_user_ids();

    if (in_array(session('user_id'), $salesmanIds)) {

        \DB::table('crm_documents')
            ->where('id', $request->id)
            ->update(['salesman_id' => session('user_id')]);
        \DB::table('crm_opportunities')
            ->where('account_id', $request->account_id)
            ->update(['salesman_id' => session('user_id')]);
        \DB::table('crm_accounts')
            ->where('id', $request->account_id)
            ->update(['salesman_id' => session('user_id')]);
    }

}

function aftersave_documents_send_for_approval($request)
{
    if ($request->send_approve_on_submit) {
        $data = ['id' => $request->id];
        $url = get_menu_url_from_module_id(353).'/approve';

        $request = Request::create($url, 'post', $data);
        $result = app(\App\Http\Controllers\ModuleController::class)->postApproveTransaction($request);
        \DB::table('crm_documents')->where('id', $request->id)->update(['send_approve_on_submit' => 0]);
    }
}

function beforesave_documents_check_credit_note_reason($request)
{
    if (! empty(session('user_id')) && session('role_level') == 'Admin' && str_contains($request->doctype, 'Credit Note') && empty($request->credit_note_reason)) {
        return 'Credit note reason must be filled out.';
    }
}

function beforesave_documents_voice_package_required($request)
{
    try {

        $requires_pbx_products_check = false;
        $airtime_package_active = false;
        $number_active = false;
        $extension_active = false;
        $sip_trunk_product_ids = get_activation_type_product_ids('sip_trunk');
        $extension_product_ids = get_activation_type_product_ids('pbx_extension');
        $phone_number_product_ids = get_activation_type_product_ids('phone_number');
        $pbx_extension_recording_product_ids = get_activation_type_product_ids('pbx_extension_recording');
        $number_porting_product_ids = get_activation_type_product_ids('number_porting');

        $pbx_product_ids = array_merge($sip_trunk_product_ids, $extension_product_ids, $phone_number_product_ids, $pbx_extension_recording_product_ids, $number_porting_product_ids);

        $airtime_contract_product_ids = get_activation_type_product_ids('airtime_contract');
        $unlimited_channel_product_ids = get_activation_type_product_ids('unlimited_channel');

        $airtime_product_ids = array_merge($airtime_contract_product_ids, $unlimited_channel_product_ids);
        $account_type = \DB::table('crm_accounts')->where('id', $request->account_id)->pluck('type')->first();
        if ($account_type != 'reseller' && ! str_contains($request->reference, 'Migrate')) {

            if ($request->doctype != 'Credit Note' && $request->doctype != 'Credit Note Draft') {

                foreach ($request->product_id as $i => $product_id) {
                    if (is_array($pbx_product_ids) && count($pbx_product_ids) > 0) {

                        if (in_array($product_id, $pbx_product_ids)) {
                            $requires_pbx_products_check = true;
                        }
                    }
                }

                if ($requires_pbx_products_check) {

                    foreach ($request->product_id as $i => $product_id) {
                        if (is_array($airtime_product_ids) && count($airtime_product_ids) > 0) {

                            if (in_array($product_id, $airtime_product_ids)) {
                                $airtime_package_active = true;
                            }
                        }
                        if (is_array($phone_number_product_ids) && count($phone_number_product_ids) > 0) {

                            if (in_array($product_id, $phone_number_product_ids)) {
                                $number_active = true;
                            }
                        }
                        if (is_array($extension_product_ids) && count($extension_product_ids) > 0) {

                            if (in_array($product_id, $extension_product_ids)) {
                                $extension_active = true;
                            }
                        }
                    }
                }

                // check subscriptions if airtime product not on invoice
                if ($requires_pbx_products_check) {
                    $account_id = ($request->reseller_user > 0) ? $request->reseller_user : $request->account_id;
                    if (! $airtime_package_active) {
                        $subs_exists = \DB::table('sub_services')->whereIn('product_id', $airtime_product_ids)->where('account_id', $account_id)->where('status', '!=', 'Deleted')->count();
                        if ($subs_exists > 0) {
                            $airtime_package_active = true;
                        }
                    }
                    if (! $number_active) {
                        $subs_exists = \DB::table('sub_services')->whereIn('product_id', $phone_number_product_ids)->where('account_id', $account_id)->where('status', '!=', 'Deleted')->count();
                        if ($subs_exists > 0) {
                            $number_active = true;
                        }
                    }
                    if (! $extension_active) {
                        $subs_exists = \DB::table('sub_services')->whereIn('product_id', $extension_product_ids)->where('account_id', $account_id)->where('status', '!=', 'Deleted')->count();
                        if ($subs_exists > 0) {
                            $extension_active = true;
                        }
                    }
                }

                if ($requires_pbx_products_check && ! $airtime_package_active) {
                    return 'Monthly voice package or unlimited channel required';
                }

                if ($requires_pbx_products_check && ! $number_active) {
                    return 'Phone number subscription required';
                }

                if ($requires_pbx_products_check && ! $extension_active) {
                    return 'Extension subscription required';
                }
            }
        }

    } catch (\Throwable $ex) {
        exception_log($ex);

        return json_alert('An error occured validating document lines.', 'warning');
    }
}

function beforesave_documents_validate_lines($request)
{
    try {

        // if ($request->doctype != 'Credit Note' && $request->doctype != 'Credit Note Draft') {
        //     $account_status = \DB::table('crm_accounts')->where('id',$request->account_id)->pluck('account_status')->first();
        //     if($account_status == 'Cancelled'){
        //     //    return 'Orders cannot be created for cancelled accounts';
        //     }
        // }

        $requires_coverage_check = false;
        $requires_description = false;
        $airtime_package_active = false;
        $fiber_ids = [];
        $lte_product_ids = [];

        $account_id = $request->account_id;
        if (! empty($request->reseller_user)) {
            $account_id = $request->reseller_user;
        }

        $fiber_ids = get_activation_type_product_ids('fibre');

        $fibre_product_ids = get_activation_type_product_ids('fibre_product');

        $ip_range_gateway_ids = get_activation_type_product_ids('ip_range_gateway');
        $ip_range_route_ids = get_activation_type_product_ids('ip_range_route');

        $lte_product_ids = get_activation_type_product_ids('lte_sim_card');
        $lte_product_mtn_ids = get_activation_type_product_ids('mtn_lte_sim_card');
        $lte_product_telkom_ids = get_activation_type_product_ids('telkom_lte_sim_card');
        if (! str_contains($request->reference, 'Migrate')) {

            if ($request->doctype != 'Credit Note' && $request->doctype != 'Credit Note Draft') {

                // rental deposit check to make sure invoice does not have other products
                if (session('instance')->directory == 'eldooffice') {
                    if (in_array(11, $request->product_id) && count($request->product_id) > 1) {
                        return 'Rental deposit invoices cannot contain other products';
                    }
                }

                foreach ($request->product_id as $i => $product_id) {
                    if (in_array($product_id, $ip_range_route_ids) || in_array($product_id, $ip_range_gateway_ids)) {
                        if (empty($request->description[$i])) {
                            return 'Description required';
                        }
                    }

                    if (is_array($fibre_product_ids) && count($fibre_product_ids) > 0) {

                        if (in_array($product_id, $fibre_product_ids)) {

                            $requires_coverage_check = true;
                        }
                    }
                    if (is_array($fiber_ids) && count($fiber_ids) > 0) {
                        if (in_array($product_id, $fiber_ids)) {

                            $requires_coverage_check = true;
                        }
                    }

                    if (is_array($lte_product_ids) && count($lte_product_ids) > 0) {
                        if (in_array($product_id, $lte_product_ids)) {

                            $requires_coverage_check = true;
                        }
                    }

                    if (is_array($lte_product_mtn_ids) && count($lte_product_mtn_ids) > 0) {
                        if (in_array($product_id, $lte_product_mtn_ids)) {

                            $requires_coverage_check = true;
                        }
                    }

                    if (is_array($lte_product_telkom_ids) && count($lte_product_telkom_ids) > 0) {
                        if (in_array($product_id, $lte_product_telkom_ids)) {

                            $requires_coverage_check = true;
                        }
                    }
                }
            }

            if ($requires_coverage_check) {
                if (empty($request->coverage_address) || empty($request->coverage_confirmed)) {
                    return json_alert('Please check and confirm that your address has coverage. Enter the address exactly as its entered on the coverage map.', 'warning');
                }
            }
        }

    } catch (\Throwable $ex) {
        exception_log($ex);

        return json_alert('An error occured validating document lines.', 'warning');
    }
}

function email_monthly_billing($billing_id, $account_id = false)
{

    $billing = \DB::table('acc_billing')->where('id', $billing_id)->get()->first();

    if (! $billing->approved) {
        return false;
    }
    $billing_type = $billing->billing_type;
    $docdate = \DB::table('crm_documents')->where('doctype', 'Tax Invoice')->where('docdate', $billing->billing_date)->where('billing_type', $billing_type)->orderby('id', 'desc')->pluck('docdate')->first();

    if ($account_id) {
        $account_ids = [$account_id];
    } else {
        $account_ids = \DB::table('crm_accounts')->whereIn('type', ['customer', 'reseller'])->where('partner_id', 1)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    }

    //$account_ids = [8];

    $docs = \DB::table('crm_documents')->where('reversal_id', 0)->where('doctype', 'Tax Invoice')->whereIn('account_id', $account_ids)->where('docdate', $docdate)->where('billing_type', $billing_type)->get();
    foreach ($docs as $doc) {
        $e = \DB::table('erp_communication_lines')->where('account_id', $doc->account_id)->where('success', 1)->where('attachments', 'like', '%Invoice_'.$doc->id.'%')->count();
        if ($e) {
            continue;
        }

        $data = [];
        $pdf = document_pdf($doc->id);
        $file = 'Invoice_'.$doc->id.'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $data['attachments'][] = $file;

        if ($reseller->id == 1) {
            $data['attach_statement'] = true;
        }
        $account = dbgetaccount($doc->account_id);
        $data['account_balance'] = $account->balance;
        $data['invoice_reference'] = $doc->reference;
        $data['invoice_id'] = $doc->id;
        $data['invoice_total'] = $doc->total;
        $data['due_date'] = date('Y-m-d', strtotime($doc->docdate).' +7 days');

        $data['billing_email_footer'] = \DB::table('crm_account_partner_settings')->where('id', $account->partner_id)->pluck('billing_email_footer')->first();
        $data['billing_email_footer'] = '';

        $data['account_id'] = $doc->account_id;
        $data['function_name'] = 'schedule_monthly_billing';

        $function_variables = get_defined_vars();
        unset($function_variables['pdf']);

        // cc support contact - vca
        $support_email = get_account_contact_email($doc->account_id, 'Support');
        if ($support_email > '') {
            $data['cc_support_email'] = $support_email;
        }

        //$data['bcc_email'] ='ahmed@telecloud.co.za';
        //  $data['test_debug']=1;
        // $data['use_alt_smtp'] =1;
        email_queue_add($doc->account_id, $data, []);
        // $r = erp_process_notification($doc->account_id, $data,[]);
    }
}

function schedule_monthly_billing_email_report()
{
    $date = \DB::table('crm_documents')->where('billing_type', 'Monthly')->orderBy('docdate', 'desc')->pluck('docdate')->first();

    $bills = \DB::table('crm_documents')->where('docdate', $date)->where('billing_type', 'Monthly')->get();
    $call_profits = \DB::table('crm_documents')->where('docdate', $date)->where('reference', 'like', 'Call Profits%')->get();
    $bill_email_count = 0;
    $bill_email_success = 0;
    $bill_email_error = 0;
    $email_errors = [];
    foreach ($bills as $b) {
        $e = \DB::table('erp_communication_lines')->where('account_id', $b->account_id)->where('attachments', 'like', '%Invoice_'.$b->id.'%')->get()->first();
        if (! empty($e)) {
            $bill_email_count++;
            if ($e->success == 1) {
                $bill_email_success++;
            } else {
                $bill_email_error++;
                $account = dbgetaccount($e->account_id);
                $err = $account->company.' - '.$e->destination;
                if (! empty($e->error)) {
                    $err .= ' - '.$e->error;
                }
                $email_errors[] = $err;
            }
        } else {
            $bill_email_error++;
        }
    }

    $c_count = 0;
    $c_success = 0;
    $c_error = 0;
    $c_errors = [];
    foreach ($call_profits as $b) {
        $e = \DB::table('erp_communication_lines')->where('account_id', $b->account_id)->where('attachments', 'like', '%Invoice_'.$b->id.'%')->get()->first();
        if (! empty($e)) {
            $c_count++;
            if ($e->success == 1) {
                $c_success++;
            } else {
                $c_error++;
                $account = dbgetaccount($e->account_id);
                $err = $account->company.' - '.$e->destination;
                if (! empty($e->error)) {
                    $err .= ' - '.$e->error;
                }
                $c_errors[] = $err;
            }
        } else {
            $c_error++;
        }
    }

    $smses = \DB::table('isp_sms_messages')
        ->where('message', 'LIKE', '%Cloud Telecoms%')
        ->where('message', 'LIKE', '%Please settle to prevent service suspension%')
        ->where('queuetime', 'LIKE', $date.'%')
        ->get();
    $sms_success = 0;
    $sms_failure = 0;
    $sms_undelivered = [];
    foreach ($smses as $sms) {
        $status = \DB::table('isp_sms_message_queue')->where('isp_sms_messages_id', $sms->id)->pluck('status')->first();
        if ($status == 'Delivered') {
            $sms_success++;
        } else {
            $sms_failure++;
            $account_id = false;
            $message = explode(PHP_EOL, $sms->message);
            foreach ($message as $line) {
                if (str_contains($line, 'Invoice')) {
                    $id_arr = explode('#', $line);
                    $invoice_id = trim($id_arr[1]);
                    $account_id = \DB::table('crm_documents')->where('id', $invoice_id)->pluck('account_id')->first();
                }
            }
            if ($account_id) {
                $account = dbgetaccount($account_id);
                $sms_undelivered[] = $account->company.' '.$sms->numbers.' '.$status;
            }
        }
    }

    $msg .= 'Monthly Invoices: '.count($bills).'<br><br>';
    $msg .= 'SMS sent: '.count($smses).'<br>';
    $msg .= 'SMS delivered: '.$sms_success.'<br>';
    $msg .= 'SMS failed: '.$sms_failure.'<br>';
    if ($sms_failure > 0) {
        foreach ($sms_undelivered as $e) {
            $msg .= $e.'<br>';
        }
    }

    $msg .= '<br>Emails sent: '.$bill_email_count.'<br>';
    $msg .= 'Emails delivered: '.$bill_email_success.'<br>';
    $msg .= 'Emails failed: '.$bill_email_error.'<br>';
    if (count($email_errors) > 0) {
        foreach ($email_errors as $e) {
            $msg .= $e.'<br>';
        }
    }

    $msg .= '<br>Monthly Call Profit Invoices: '.count($bills).'<br><br>';

    $msg .= 'Emails sent: '.$c_count.'<br>';
    $msg .= 'Emails delivered: '.$c_success.'<br>';
    $msg .= 'Emails failed: '.$c_error.'<br>';
    if (count($c_errors) > 0) {
        foreach ($c_errors as $e) {
            $msg .= $e.'<br>';
        }
    }

    directmail('ahmed@telecloud.co.za', 'Billing Emails Summary - '.session('instance')->name, $msg, '');
    directmail('kola@telecloud.co.za', 'Billing Emails Summary - '.session('instance')->name, $msg, '');
}

function button_verify_billing_emails($request)
{
    $date = \DB::table('crm_documents')->where('billing_type', 'Monthly')->where('doctype', 'Tax Invoice')->orderBy('id', 'desc')->pluck('docdate')->first();

    $bills = \DB::table('crm_documents')->where('docdate', $date)->where('billing_type', 'Monthly')->get();
    $call_profits = \DB::table('crm_documents')->where('docdate', $date)->where('reference', 'like', 'Call Profits%')->get();
    $bill_email_count = 0;
    $bill_email_success = 0;
    $bill_email_error = 0;
    $email_errors = [];
    foreach ($bills as $b) {
        verify_bill_emails($b->id);
        // $e = \DB::table('erp_communication_lines')->where('account_id', $b->account_id)->where('attachments', 'like', '%Invoice_'.$b->id.'%')->get()->first();
        // if (!empty($e)) {
        //     $bill_email_count++;
        //     if ($e->success == 1) {
        //         $bill_email_success++;
        //     } else {
        //         $bill_email_error++;
        //         $account = dbgetaccount($e->account_id);
        //         $err = $account->company.' - '.$e->destination;
        //         if (!empty($e->error)) {
        //             $err .= ' - '.$e->error;
        //         }
        //         $email_errors[] = $err;
        //     }
        // } else {
        //     $bill_email_error++;
        // }
    }

    // $c_count = 0;
    // $c_success = 0;
    // $c_error = 0;
    // $c_errors = [];
    // foreach ($call_profits as $b) {
    //     $e = \DB::table('erp_communication_lines')->where('account_id', $b->account_id)->where('attachments', 'like', '%Invoice_'.$b->id.'%')->get()->first();
    //     if (!empty($e)) {
    //         $c_count++;
    //         if ($e->success == 1) {
    //             $c_success++;
    //         } else {
    //             $c_error++;
    //             $account = dbgetaccount($e->account_id);
    //             $err = $account->company.' - '.$e->destination;
    //             if (!empty($e->error)) {
    //                 $err .= ' - '.$e->error;
    //             }
    //             $c_errors[] = $err;
    //         }
    //     } else {
    //         $c_error++;
    //     }
    // }

    // $smses = \DB::table('isp_sms_messages')
    //     ->where('message', 'LIKE', '%Cloud Telecoms%')
    //     ->where('message', 'LIKE', '%Please settle to prevent service suspension%')
    //     ->where('queuetime', 'LIKE', $date.'%')
    //     ->get();
    // $sms_success = 0;
    // $sms_failure = 0;
    // $sms_undelivered = [];
    // foreach ($smses as $sms) {
    //     $status = \DB::table('isp_sms_message_queue')->where('isp_sms_messages_id', $sms->id)->pluck('status')->first();
    //     if ($status == 'Delivered') {
    //         $sms_success++;
    //     } else {
    //         $sms_failure++;
    //         $account_id = false;
    //         $message = explode(PHP_EOL, $sms->message);
    //         foreach ($message as $line) {
    //             if (str_contains($line, 'Invoice')) {
    //                 $id_arr = explode('#', $line);
    //                 $invoice_id = trim($id_arr[1]);
    //                 $account_id = \DB::table('crm_documents')->where('id', $invoice_id)->pluck('account_id')->first();
    //             }
    //         }
    //         if ($account_id) {
    //             $account = dbgetaccount($account_id);
    //             $sms_undelivered[] = $account->company.' '.$sms->numbers.' '.$status;
    //         }
    //     }
    // }

    // $msg .= 'Monthly Invoices: '.count($bills).'<br><br>';
    // $msg .= 'SMS sent: '.count($smses).'<br>';
    // $msg .= 'SMS delivered: '.$sms_success.'<br>';
    // $msg .= 'SMS failed: '.$sms_failure.'<br>';
    // if ($sms_failure > 0) {
    //     foreach ($sms_undelivered as $e) {
    //         $msg.= $e.'<br>';
    //     }
    // }

    // $msg .= '<br>Emails sent: '.$bill_email_count.'<br>';
    // $msg .= 'Emails delivered: '.$bill_email_success.'<br>';
    // $msg .= 'Emails failed: '.$bill_email_error.'<br>';
    // if (count($email_errors) > 0) {
    //     foreach ($email_errors as $e) {
    //         $msg.= $e.'<br>';
    //     }
    // }

    // // verify_bill_emails($billing->id);
    // $msg .= '<br>Monthly Call Profit Invoices: '.count($bills).'<br><br>';

    // $msg .= 'Emails sent: '.$c_count.'<br>';
    // $msg .= 'Emails delivered: '.$c_success.'<br>';
    // $msg .= 'Emails failed: '.$c_error.'<br>';
    // if (count($c_errors) > 0) {
    //     foreach ($c_errors as $e) {
    //         $msg.= $e.'<br>';
    //     }
    // }

    // // directmail('kola@telecloud.co.za', 'Billing Emails Summary', $msg, '');
    // directmail('ahmed@telecloud.co.za', 'Billing Emails Summary', $msg, '');
    return json_alert('Email summary sent.');
}

function beforesave_check_stock_availability($request)
{
    if (! str_contains($request->reference, 'Migrate')) {
        $id = (! empty($request->id)) ? $request->id : null;

        $products = $request->product_id;

        if (empty($products) || count($products) == 0) {
            return 'Invalid document lines.';
        }

        for ($i = 0; $i < count($products); $i++) {
            if (empty($request->qty[$i]) || $request->qty[$i] == 0) {
                return 'Quantity required';
            }
        }

        $hosting_product_ids = get_activation_type_product_ids('hosting');
        $sitebuilder_product_ids = get_activation_type_product_ids('sitebuilder');
        $sitebuilderaddon_product_ids = get_activation_type_product_ids('sitebuilderaddon');
        $domain_name_product_ids = get_activation_type_product_ids('domain_name');

        // get product bundle activations
        $bundle_lines = [];
        foreach ($products as $i => $product_id) {
            $product = dbgetrow('crm_products', 'id', $product_id);
            if ($product->is_bundle) {

                $activation_products = \DB::table('crm_product_bundle_activations')->where('bundle_product_id', $product_id)->get();
                foreach ($activation_products as $activation_product) {
                    $bundle_lines[] = $activation_product->product_id;
                }
                unset($products[$i]);
            }
        }

        foreach ($bundle_lines as $bundle_line) {
            $products[] = $bundle_line;
        }

        $products = array_values($products);
        if (! in_array($request->doctype, ['Credit Note', 'Credit Note Draft'])) {
            $customer = dbgetaccount($request->account_id);
            $parent = dbgetaccount($customer->partner_id);

            $total = 0;

            for ($i = 0; $i < count($products); $i++) {
                $product = \DB::table('crm_products')->where('id', $products[$i])->get()->first();

                if (empty($product)) {

                    return 'Invalid Product';
                }

                if (! empty($product->activation_product_id) && ! in_array($product->activation_product_id, $products)) {
                    $product_code = \DB::table('crm_products')->where('id', $product->id)->pluck('code')->first();
                    $activation_product_code = \DB::table('crm_products')->where('id', $product->activation_product_id)->pluck('code')->first();

                    return $product_code.' requires the activation product '.$activation_product_code;
                }
                $has_hosting_product = false;
                foreach ($products as $id) {
                    if (in_array($id, $hosting_product_ids)) {
                        $has_hosting_product = true;
                    }
                }

                if (! str_contains($request->reference, 'Migrate')) {
                    if ($has_hosting_product) {
                        $has_domain_product = false;
                        foreach ($products as $id) {
                            if (in_array($id, $domain_name_product_ids)) {
                                $has_domain_product = true;
                            }
                        }

                        if (! $has_domain_product) {

                            return 'Requires a domain name product.';
                        }
                    }
                }
                if ($product->provision_plan_id == 8) {
                    $has_hosting_product = false;
                    foreach ($products as $id) {
                        if (in_array($id, $hosting_product_ids)) {
                            $has_hosting_product = true;
                        }
                    }
                    if (! $has_hosting_product) {
                        $product_code = \DB::table('crm_products')->where('id', $product->id)->pluck('code')->first();

                        return $product_code.' requires a hosting product.';
                    }
                }

                if (in_array($product->id, $sitebuilderaddon_product_ids)) {
                    $has_hosting_product = false;
                    foreach ($products as $id) {
                        if (in_array($id, $hosting_product_ids)) {
                            $has_hosting_product = true;
                        }
                    }

                    if (! $has_hosting_product) {
                        $product_code = \DB::table('crm_products')->where('id', $product->id)->pluck('code')->first();
                        $service_account_id = ($request->reseller_user) ? $request->reseller_user : $request->account_id;
                        $has_active_hosting_subscription = false;
                        if ($service_account_id) {
                            $hosting_subscription_count = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('provision_type', 'hosting')->where('account_id', $service_account_id)->count();
                            $has_active_hosting_subscription = ($hosting_subscription_count > 0) ? true : false;
                        }

                        if (! $has_active_hosting_subscription) {
                            return $product_code.' requires a hosting product.';
                        }
                    }
                }

                //  if(strtolower($product->code)!='vehicledbcredits'){
                //     if (!$product->is_subscription && 'Stock' == $product->type && str_contains($request->doctype, 'Invoice')) {
                //         $new_product_qty = $product->qty_on_hand - $request->qty[$i];
                //         if ($new_product_qty < 0) {
                //             return 'Insufficient Stock - '.$product->code.'. Please contact us.';
                //         }
                //     }
                //  }
            }
        }
    }
}

//// void_transaction
function void_transaction($transaction_table, $transaction_id, $doctype, $complete_transaction = false, $revert = false)
{
    $transaction_lines_table = substr_replace($transaction_table, '', -1).'_lines';

    $line_id = '';

    if ($transaction_table == 'crm_documents' || $transaction_table == 'crm_supplier_documents') {
        $line_id = 'document_id';
    }

    $transaction = \DB::table($transaction_table)->where('id', $transaction_id)->get()->first();
    if ($transaction_table == 'crm_supplier_documents' && $revert) {
        \DB::table($transaction_table)->where('id', $transaction_id)->update(['doctype' => 'Supplier Order']);

        return $transaction_id;
    }

    if ($doctype == 'Order' || $doctype == 'Quotation' || $doctype == 'Credit Note Draft') {
        $description = '';
        if (! empty($line_id)) {
            // copy lines
            $transaction_lines = \DB::table($transaction_lines_table)->where($line_id, $transaction_id)->get();
            foreach ($transaction_lines as $transaction_line) {
                $product = \DB::table('crm_products')->where('id', $transaction_line->product_id)->get()->first();
                $description .= $product->code.', price:'.$transaction_line->price.',  qty:'.$transaction_line->qty.PHP_EOL;
            }
            if (! empty($transaction_lines)) {
                \DB::table($transaction_lines_table)->where($line_id, $transaction_id)->delete();
            }
        }
        if ($doctype == 'Credit Note Draft') {
            \DB::table('crm_documents')->where('reversal_id', $transaction_id)->update(['reversal_id' => 0]);
        }

        \DB::table($transaction_table)->where('id', $transaction_id)->delete();

        if ($doctype == 'Quotation') {
            if (empty($transaction->created_by)) {
                $transaction->created_by = get_user_id_default();
            }
            $deleted_quote = [
                'created_at' => date('Y-m-d H:i:s'),
                'docdate' => $transaction->docdate,
                'account_id' => $transaction->account_id,
                'created_by' => $transaction->created_by,
                'total' => $transaction->total,
                'description' => $description,
            ];
            \DB::table('crm_cancelled_quotes')->insert($deleted_quote);
        }

        return 'draft';
    }

    if (strstr($transaction_table, '_documents')) {
        $transaction = \DB::table($transaction_table)->where('id', $transaction_id)->get()->first();
        if (! empty($transaction->reversal_id)) {
            return 'reversal_id';
        }
        if ($transaction->doctype == 'Tax Invoice' || $transaction->doctype == 'Supplier Invoice') {
            //only transactions with totals can be reversed, payments and transactions
            if (! empty($transaction) && ! empty($transaction->total)) {
                $transaction->docdate = date('Y-m-d');
                $transaction->doctype = 'Credit Note';

                if ($transaction_table == 'crm_documents') {
                    $transaction->completed = 1;
                    $transaction->credit_note_reason = 'Credited from Tax Invoice #'.$transaction->id;
                }

                if ($transaction_table == 'crm_supplier_documents') {
                    $transaction->doctype = 'Supplier Debit Note';

                } else {
                    $transaction->doctype = 'Credit Note Draft';
                }
                if (isset($transaction->payment_status)) {
                    $transaction->payment_status = 'Complete';
                }
                if (isset($transaction->subscription_created)) {
                    $transaction->subscription_created = 1;
                }
                $transaction->total = $transaction->total;
                if (! empty($transaction->tax)) {
                    $transaction->tax = $transaction->tax;
                }

                unset($transaction->id);
                $transaction_insert_data = (array) $transaction;

                $transaction_insert_data['created_by'] = get_user_id_default();

                $new_transaction_id = dbinsert($transaction_table, $transaction_insert_data);
                $transaction->id = $new_transaction_id;
                $db = new DBEvent;
                $db->setTable($transaction_table)->postDocument($transaction->id);
                $db->postDocumentCommit();
                //if transaction copied

                if (! empty($line_id) && strstr($transaction_table, '_documents')) {
                    // copy lines
                    $transaction_lines = \DB::table($transaction_lines_table)->where($line_id, $transaction_id)->get();
                    if (! empty($transaction_lines)) {
                        foreach ($transaction_lines as $line) {
                            $line->{$line_id} = $new_transaction_id;
                            $line->price = $line->price;
                            $line->qty = $line->qty;
                            $line_insert_data = (array) $line;
                            $line_insert_data['original_line_id'] = $line->id;
                            unset($line_insert_data['id']);
                            dbinsert($transaction_lines_table, $line_insert_data);
                        }
                    }
                }

                \DB::table($transaction_table)->where('id', $transaction_id)->update(['reversal_id' => $new_transaction_id]);
                \DB::table($transaction_table)->where('id', $new_transaction_id)->update(['created_at' => date('Y-m-d H:i:s'), 'created_by' => get_user_id_default(), 'reversal_id' => $transaction_id]);

                $voided_transaction = ['table_name' => $transaction_table, 'transaction_id' => $transaction_id, 'transaction_voided_id' => $new_transaction_id];

                return $new_transaction_id;
            }
        }
    }

    return false;
}

function balance_check_override()
{
    if (check_access('1,31')) {
        session(['balance_check_override' => 1]);
    }
}

function remove_balance_check_override()
{
    session()->forget('balance_check_override');
}

function button_documents_view_credited_invoice($request)
{
    $doc = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
    if (empty($doc->reversal_id)) {
        return 'No reversal id set';
    }
    $row = \DB::table('crm_documents')->where('id', $doc->reversal_id)->get()->first();
    $account = dbgetaccount($row->account_id);
    $reseller = dbgetaccount($account->partner_id);

    $pdf = document_pdf($row->id);
    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $row->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $row->doctype;
    }

    $row->doctype = $doctype_label;

    $file = str_replace(' ', '_', ucfirst($row->doctype).' '.$row->id).'.pdf';
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);

    $filename = attachments_url().$file;

    $data['pdf'] = $filename;
    $data['menu_name'] = $file;
    $data['doc_id'] = $request->id;

    return view('__app.components.pdf', $data);
}

function button_documents_view_service_invoice($request)
{
    $row = \DB::table('crm_documents')->where('id', $request->id)->get()->first();

    $pdf = servicedocument_pdf($row->id);
    $file = 'Service_'.str_replace(' ', '_', ucfirst($row->doctype).' '.$row->id).'.pdf';
    $filename = attachments_path().$file;

    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);

    $filename = attachments_url().$file;

    $data['pdf'] = $filename;
    $data['menu_name'] = $file;
    $data['doc_id'] = $request->id;
    $data['service_invoice'] = 1;

    return view('__app.components.pdf', $data);
}

function button_documents_view_credit_note($request)
{
    $doc = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
    $row = \DB::table('crm_documents')->where('id', $doc->reversal_id)->get()->first();
    $account = dbgetaccount($row->account_id);
    $reseller = dbgetaccount($account->partner_id);

    $pdf = document_pdf($row->id);
    $file = str_replace(' ', '_', ucfirst($row->doctype).' '.$row->id).'.pdf';
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);

    $filename = attachments_url().$file;

    $data['pdf'] = $filename;
    $data['menu_name'] = $file;
    $data['doc_id'] = $request->id;

    return view('__app.components.pdf', $data);
}

function email_document_pdf($id, $pricing_changed = false)
{
    $document = \DB::table('crm_documents')->where('id', $id)->get()->first();
    if ($document->custom_prices && ! $document->custom_prices_approved) {
        return false;
    }
    if ($document->billing_type == 'Monthly') {
        //return false;
    }
    $doctype_send = \DB::table('acc_doctypes')->where('send_email_on_create', 1)->where('doctype', $document->doctype)->count();
    if (! $doctype_send) {
        if (! $document->send_email_on_submit) {
            return false;
        }
    }

    if (empty($document)) {
        return false;
    }
    if ($document->reference == 'Bad Debt Written Off') {
        return false;
    }
    $doctype = $document->doctype;
    $customer = dbgetaccount($document->account_id);
    $reseller = dbgetaccount($customer->partner_id);

    $attachments = [];

    $pdf = document_pdf($id);

    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $document->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $document->doctype;
    }

    $document->doctype = $doctype_label;

    $file = $document->doctype.'_'.$id.'.pdf';
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);
    $attachments[] = $file;

    if ($document->reference == 'Bad Debt Written Off') {
        $pdf = statement_pdf($document->account_id);
        $file = 'Statement_'.$document->account_id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);

        $attachments[] = $file;
    }

    $data['attachments'] = $attachments;
    $function_variables = get_defined_vars();
    if ($document->doctype == 'Invoice' || $document->doctype == 'Tax Invoice' || $document->doctype == 'Credit Note') {
        $data['attach_statement'] = true;
    }
    if ($pricing_changed) {
        $data['internal_function'] = 'document_pricing_changed_email';
    } else {
        $data['internal_function'] = 'document_email';
    }
    // if invoice monthly price and account does not have debit order show debit order form
    $existing_debit_order = \DB::table('acc_debit_orders')->where('account_id', $account_id)->where('status', '!=', 'Deleted')->count();
    $requires_debit_order = invoice_requires_debit_order($id);
    $requires_contract_debit_order = invoice_requires_contract_debit_order($id);
    $data['show_debit_order_link'] = false;

    if (is_main_instance() && ! $existing_debit_order && $requires_contract_debit_order) {
        $webform_data = [];
        $webform_data['module_id'] = 390;
        $webform_data['account_id'] = $account_id;
        $webform_data['is_contract'] = 1;

        $link_data = \Erp::encode($webform_data);
        $data['webform_link'] = '<a href="'.request()->root().'/webform/'.$link_data.'" >Service Contract/a>';
        $data['show_debit_order_link'] = true;
    } elseif (is_main_instance() && ! $existing_debit_order && $requires_debit_order) {
        $webform_data = [];
        $webform_data['module_id'] = 390;
        $webform_data['account_id'] = $account_id;

        $link_data = \Erp::encode($webform_data);
        $data['webform_link'] = '<a href="'.request()->root().'/webform/'.$link_data.'" >Debit Order/a>';
        $data['show_debit_order_link'] = true;
    }
    $data['docreference'] = $document->reference;
    $data['doctype'] = $document->doctype;
    $data['docid'] = $document->id;
    if (! empty($document->reseller_user)) {
        $reseller_user_company = \DB::table('crm_accounts')->where('id', $document->reseller_user)->pluck('company')->first();
        $data['reseller_user_company'] = $reseller_user_company;
    }
    email_queue_add($customer->id, $data, $function_variables);
    if (! empty($document->reseller_user) && $customer->bill_customers) {
        $data['show_debit_order_link'] = false;
        $data['webform_link'] = false;
        $attachments = [];
        $pdf = servicedocument_pdf($id);

        $file = $document->doctype.'_s'.$id.'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);

        $attachments[] = $file;
        $data['attachments'] = $attachments;
        $function_variables = get_defined_vars();
        $data['attach_statement'] = false;
        $data['internal_function'] = 'document_email';

        email_queue_add($document->reseller_user, $data, $function_variables);
    }
}

function email_monthly_document_pdf($id)
{
    $document = \DB::table('crm_documents')->where('id', $id)->get()->first();
    if ($document->billing_type != 'Monthly') {
        return false;
    }

    if (empty($document)) {
        return false;
    }
    if ($document->reference == 'Bad Debt Written Off') {
        return false;
    }
    $doctype = $document->doctype;
    $customer = dbgetaccount($document->account_id);
    $reseller = dbgetaccount($customer->partner_id);
    $attachments = [];

    $pdf = document_pdf($id);

    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $document->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $document->doctype;
    }

    $document->doctype = $doctype_label;

    $file = $document->doctype.'_'.$id.'.pdf';
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);
    $attachments[] = $file;

    if ($document->reference == 'Bad Debt Written Off') {
        $pdf = statement_pdf($document->account_id);
        $file = 'Statement_'.$document->account_id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);

        $attachments[] = $file;
    }

    $data['attachments'] = $attachments;
    $function_variables = get_defined_vars();
    if ($document->doctype == 'Invoice' || $document->doctype == 'Tax Invoice' || $document->doctype == 'Credit Note') {
        $data['attach_statement'] = true;
    }
    $data['internal_function'] = 'document_email';

    // if invoice monthly price and account does not have debit order show debit order form
    $existing_debit_order = \DB::table('acc_debit_orders')->where('account_id', $account_id)->where('status', '!=', 'Deleted')->count();
    $monthly_products = \DB::table('crm_products')->where('type', 'Service')->where('is_subscription', 1)->pluck('id')->toArray();
    $quote_has_monthly_products = \DB::table('crm_document_lines')->where('document_id', $id)->whereIn('product_id', $monthly_products)->pluck('id')->toArray();
    $data['show_debit_order_link'] = false;
    if (is_main_instance() && $doctype == 'Quotation' && ! $existing_debit_order && $quote_has_monthly_products) {
        $webform_data = [];
        $webform_data['module_id'] = 390;
        $webform_data['account_id'] = $account_id;

        $link_data = \Erp::encode($webform_data);
        $data['webform_link'] = '<a href="'.request()->root().'/webform/'.$link_data.'" >Service Contract</a>';
        $data['show_debit_order_link'] = true;
    }
    $data['docreference'] = $document->reference;
    $data['doctype'] = $document->doctype;
    $data['docid'] = $document->id;
    email_queue_add($customer->id, $data, $function_variables);
    if (! empty($document->reseller_user) && $customer->bill_customers) {
        $attachments = [];
        $pdf = servicedocument_pdf($id);

        $file = $document->doctype.'_s'.$id.'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $attachments[] = $file;
        $data['attachments'] = $attachments;
        $function_variables = get_defined_vars();
        $data['attach_statement'] = false;
        $data['internal_function'] = 'document_email';

        email_queue_add($document->reseller_user, $data, $function_variables);
    }
}

function get_transaction_categories_usd()
{
    if (session('role_level') == 'Admin') {
        $products = \DB::table('crm_products as products')
            ->select('products.*', 'category.not_for_sale', 'category.id as category_id', 'category.name as category', 'category.department as department')
            ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
            ->where('products.status', 'Enabled')
            ->where('category.usd_active', 1)
            ->where('category.not_for_sale', 0)
            ->where('products.not_for_sale', 0)
            ->where('category.is_deleted', 0)
            ->where('products.type', '!=', 'Bundle')
            ->orderBy('category.sort_order')
            ->orderBy('products.sort_order')
            ->groupBy('category_id')
            ->get();
    } else {
        $products = \DB::table('crm_products as products')
            ->select('products.*', 'category.not_for_sale', 'category.id as category_id', 'category.name as category', 'category.department as department')
            ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
            ->where('products.status', 'Enabled')
            ->where('category.customer_access', 1)
            ->where('category.usd_active', 1)
            ->where('category.not_for_sale', 0)
            ->where('products.not_for_sale', 0)
            ->where('category.is_deleted', 0)
            ->where('products.type', '!=', 'Bundle')
            ->orderBy('category.sort_order')
            ->orderBy('products.sort_order')
            ->groupBy('category_id')
            ->get();
    }

    $product_list = [];

    foreach ($products as $product) {
        $list_product = (object) [];
        $list_product->id = $product->category_id;
        $list_product->category = $product->department.' - '.$product->category;

        $product_list[] = $list_product;
    }

    return $product_list;
}

function get_transaction_categories()
{
    if (session('role_level') == 'Admin') {
        $products = \DB::table('crm_products as products')
            ->select('products.*', 'category.not_for_sale', 'category.id as category_id', 'category.name as category', 'category.department as department')
            ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
            ->where('products.status', 'Enabled')
            ->where('category.not_for_sale', 0)
            ->where('products.not_for_sale', 0)
            ->where('category.is_deleted', 0)
            ->where('category.disable_on_documents', 0)
            ->where('products.type', '!=', 'Bundle')
            ->orderBy('category.sort_order')
            ->orderBy('products.sort_order')
            ->groupBy('category_id')
            ->get();
    } else {
        $products = \DB::table('crm_products as products')
            ->select('products.*', 'category.not_for_sale', 'category.id as category_id', 'category.name as category', 'category.department as department')
            ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
            ->where('products.status', 'Enabled')
            ->where('category.customer_access', 1)
            ->where('category.not_for_sale', 0)
            ->where('products.not_for_sale', 0)
            ->where('category.disable_on_documents', 0)
            ->where('category.is_deleted', 0)
            ->where('products.type', '!=', 'Bundle')
            ->orderBy('category.sort_order')
            ->orderBy('products.sort_order')
            ->groupBy('category_id')
            ->get();
    }

    $product_list = [];

    foreach ($products as $product) {
        $list_product = (object) [];
        $list_product->id = $product->category_id;
        $list_product->category = $product->department.' - '.$product->category;

        $product_list[] = $list_product;
    }

    return $product_list;
}

function get_transaction_products($account_id, $type = 'account', $usd = false)
{
    if ($type == 'account') {
        $account = dbgetaccount($account_id);
        if (empty($account)) {
            return false;
        }
        // validate_partner_pricelists($account->partner_id);
        $pabx_domain = (! empty($account->pabx_domain)) ? $account->pabx_domain : '';
        $pabx_type = (! empty($account->pabx_type)) ? $account->pabx_type : '';
    }
    //if (session('role_level') == 'Reseller' && $account->type == 'reseller_user') {
    //    $account = dbgetaccount($account->partner_id);
    //}
    if (session('role_level') == 'Admin') {
        if ($type == 'supplier') {
            $products = \DB::table('crm_products as products')
                ->select('products.*', 'category.id as category_id', 'category.name as category', 'category.department as department')
                ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
                ->where('products.status', 'Enabled')
                ->orderBy('category.sort_order')
                ->orderBy('products.sort_order')
                ->get();
        } else {

            $products = \DB::table('crm_products as products')
                ->select('products.*', 'category.not_for_sale', 'category.id as category_id', 'category.name as category', 'category.department as department')
                ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
                ->where('products.status', 'Enabled')
                ->where('category.is_deleted', 0)
                ->where('category.not_for_sale', 0)
                ->where('category.disable_on_documents', 0)
                ->where('products.not_for_sale', 0)
                ->orderBy('category.sort_order')
                ->orderBy('products.sort_order')
                ->get();

        }
    } else {

        $products = \DB::table('crm_products as products')
            ->select('products.*', 'category.not_for_sale', 'category.id as category_id', 'category.name as category', 'category.department as department')
            ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
            ->where('products.status', 'Enabled')
            ->where('category.not_for_sale', 0)
            ->where('products.not_for_sale', 0)
            ->where('category.disable_on_documents', 0)
            ->where('category.customer_access', 1)
            ->where('products.type', '!=', 'Bundle')
            ->where('category.is_deleted', 0)
            ->orderBy('category.sort_order')
            ->orderBy('products.sort_order')
            ->get();
    }

    $products = sort_product_rows($products);
    $list_products = [];

    $airtime_prepaid_ids = get_activation_type_product_ids('airtime_prepaid');
    // @pbxoffline

    if (is_main_instance()) {
        $pbx_cost_calculation = \DB::connection('pbx')->table('v_domains')
            ->where('account_id', $account_id)
            ->pluck('cost_calculation')->first();
    } else {
        $pbx_cost_calculation = 'product';
    }

    $telkom_lte_product_ids = get_activation_type_product_ids('telkom_lte_sim_card');
    $mtn_lte_product_ids = get_activation_type_product_ids('mtn_lte_sim_card');

    $lte_product_ids = array_merge($telkom_lte_product_ids, $mtn_lte_product_ids);

    $linked_lte_product_ids = \DB::table('isp_data_lte_axxess_products')->pluck('product_id')->unique()->filter()->toArray();

    foreach ($products as $row) {

        if (in_array($row->id, $lte_product_ids) && ! in_array($row->id, $linked_lte_product_ids)) {
            continue;
        }

        $code = $row->code;
        $row->code = ucwords(str_replace('_', ' ', $row->code)).' - '.$row->name;
        if (str_starts_with($code, 'calltime')) {

            if ($pbx_cost_calculation == 'volume' && $code != 'calltimevolume') {

                continue;
            }
            if ((empty($pbx_cost_calculation) || $pbx_cost_calculation == 'product') && $code == 'calltimevolume') {
                continue;
            }
        }

        if ($row->type == 'Bundle') {
            if ($account->partner_id == 1) {
                $list_products[] = $row;
            }
        } elseif ($row->provision_function == 'ltetopup') {
            // check if customer has a lte account
            $lte_accounts_count = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'lte_sim_card', 'status' => 'Enabled'])->count();
            if ($lte_accounts_count > 0) {
                $list_products[] = $row;
            }
        } else {
            $list_products[] = $row;
        }
    }

    $product_list = [];

    $currency_rate = get_exchange_rate();
    foreach ($list_products as $product) {
        if ($type == 'account') {
            $status = \DB::table('crm_pricelist_items')->where('pricelist_id', $account->pricelist_id)->where('product_id', $product->id)->pluck('status')->first();
            if ($status == 'Deleted') {

                continue;
            }
        }
        if ($type == 'account') {
            $pricing = pricelist_get_price($account_id, $product->id);
        } elseif ($type == 'lead') {
            $pricing = pricelist_get_lead_price($product->id);
        } else {
            $pricing = pricelist_get_supplier_price($product->id, $account_id);
        }

        $list_product = (object) [];
        $list_product->id = $product->id;
        $list_product->category_id = $product->category_id;
        $list_product->type = $product->type;
        $list_product->bill_frequency = $product->bill_frequency;
        $list_product->contract_period = $product->contract_period;
        $list_product->is_subscription = $product->is_subscription;
        $list_product->code = $product->code;
        $list_product->description = $product->name.' '.strip_tags(str_ireplace(['<br />', '<br>', '<br/>'], "\r\n", $product->description));
        $list_product->category = $product->department.' - '.$product->category;
        $list_product->price = currency($pricing->price);
        $list_product->activation_description = '';
        $list_product->img_url = ($product->upload_file) ? uploads_url(71).$product->upload_file : '';
        // if (!empty($product->activation_fee)) {
        //  $list_product->activation_description = 'Activation fee.'.PHP_EOL.'The service will be invoiced fully upon activation :';
        // $list_product->price = currency($product->activation_fee/1.15);
        // }
        if (! empty($product->provision_plan_id)) {
            $list_product->provision_type = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
        } else {
            $list_product->provision_type = '';
        }

        $list_product->full_price = currency($pricing->full_price);
        $list_product->full_price_incl = $pricing->full_price_incl;

        // overwrite with special price
        if ($account->partner_id == 1 && $product->special_price_incl > 0) {
            $list_product->full_price_incl = $product->special_price_incl;
            $list_product->full_price = currency($product->special_price_incl / 1.15);
            $list_product->price = currency($product->special_price_incl / 1.15);
        }

        if ($usd) {

            $list_product->price = currency($list_product->price * $currency_rate);
            $list_product->full_price = currency($list_product->full_price * $currency_rate);
            $list_product->full_price_incl = $list_product->full_price_incl * $currency_rate;
        }
        $product_list[] = $list_product;
    }

    return $product_list;
}

function create_cash_transaction($account_id, $amount, $reference = 'Cash', $approved = 0, $docdate = false)
{
    $account = dbgetaccount($account_id);
    $cashbook_id = 8;
    if ($account->currency == 'USD') {
        $cashbook_id = 14;
    }
    if (! $docdate) {
        $docdate = date('Y-m-d');
    }

    if ($account->partner_id == 1) {
        $cash_trx = [
            'doctype' => 'Cashbook Customer Receipt',
            'docdate' => date('Y-m-d'),
            'reference' => $reference,
            'total' => $amount,
            'account_id' => $account->id,
            'cashbook_id' => $cashbook_id,
            'approved' => $approved,
        ];

        $trx = (new DBEvent)->setTable('acc_cashbook_transactions')->save($cash_trx);

        if (! is_array($trx) || empty($trx['id'])) {
            return false;
        }

        return $trx['id'];
    }

    return false;
}

function create_supplier_cash_transaction($supplier_id, $amount, $reference = 'Cash')
{
    $cash_trx = [
        'doctype' => 'Cashbook Customer Receipt',
        'docdate' => date('Y-m-d'),
        'reference' => $reference,
        'total' => $amount,
        'supplier_id' => $supplier_id,
        'cashbook_id' => 8,
    ];

    $trx = (new DBEvent)->setTable('acc_cashbook_transactions')->save($cash_trx);

    if (! is_array($trx) || empty($trx['id'])) {
        return false;
    }

    return $trx['id'];
}

function schedule_pay_account_from_airtime()
{

    return false;

    $auto_pay_from_airtime_accounts = \DB::table('crm_accounts')->where('bank_allocate_airtime', 1)->where('status', 'Enabled')->where('balance', '>', 0)->pluck('id')->toArray();
    foreach ($auto_pay_from_airtime_accounts as $auto_pay_account) {

        $result = (new DBEvent)->setDebtorBalance($auto_pay_account);
        $account = dbgetaccount($auto_pay_account);
        if ($account->balance <= 0) {
            return false;
        }
        $has_pbx_domain = \DB::connection('pbx')->table('v_domains')->where('account_id', $account->id)->count();
        if (! $has_pbx_domain) {
            return false;
        }
        $pbx = \DB::connection('pbx')->table('v_domains')->where('account_id', $account->id)->get()->first();
        if ($pbx->balance < $account->balance) {
            return false;
        }
        $cash_amount = $account->balance;
        $cash_id = create_cash_transaction($account->id, $cash_amount, 'Account paid from airtime balance', 1);
        if (! $cash_id) {
            return false;
        }

        \DB::table('acc_cashbook_transactions')->where('id', $cash_id)->update(['approved' => 1]);
        (new DBEvent)->setDebtorBalance($account->id);
        $airtime_history = [
            'created_at' => date('Y-m-d'),
            'domain_uuid' => $pbx->domain_uuid,
            'total' => $cash_amount,
            'balance' => $pbx->balance - $cash_amount,
            'type' => 'account_balance_paid',
        ];
        \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);

        \DB::connection('pbx')->table('v_domains')
            ->where('domain_uuid', $pbx->domain_uuid)
            ->update(['balance' => $pbx->balance - $cash_amount]);
    }

}

function airtime_invoice_from_balance($account_id, $total = 0)
{
    if ($total) {
        $total = $total * -1;
    }

    $pbx_cost_calculation = \DB::connection('pbx')->table('v_domains')
        ->where('account_id', $account_id)
        ->pluck('cost_calculation')->first();

    if ($pbx_cost_calculation == 'volume') {
        $airtime_product_id = 992;
    } else {
        $airtime_product_id = 913;
    }

    $account = dbgetaccount($account_id);
    if (! $total) {
        $total = $account->balance;
    }

    if ($total < 0 && ($account->type == 'customer')) {
        $total = abs($total);
        if ($account->currency == 'ZAR') {
            $line_total = $total / 1.15;
            $tax = $total - $line_total;
        } else {
            $line_total = $total;
            $tax = 0;
        }

        $db = new DBEvent;
        $data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'Tax Invoice',
            'completed' => 1,
            'account_id' => $account_id,
            'total' => $total,
            'tax' => $tax,
            'reference' => 'Auto Airtime Allocation',
            'qty' => [1],
            'bill_frequency' => 1,
            'price' => [abs($line_total)],
            'full_price' => [abs($line_total)],
            'product_id' => [$airtime_product_id],
            'document_currency' => ($account->currency == 'USD') ? 'USD' : 'ZAR',
        ];

        $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

        if (! is_array($result) || empty($result['id'])) {
            return $result;
        }
    }
}

function schedule_set_lines_gp()
{

    $doc_ids = \DB::table('crm_document_lines')->join('crm_documents', 'crm_document_lines.document_id', '=', 'crm_documents.id')->where('docdate', 'LIKE', date('Y-m').'%')->pluck('document_id')->unique()->toArray();

    foreach ($doc_ids as $id) {
        set_document_lines_gp($id);
    }

    // $doc_ids = \DB::table('crm_document_lines')->join('crm_documents','crm_document_lines.document_id','=','crm_documents.id')->where('docdate','LIKE',date('Y-m').'%')->where('zar_sale_total',0)->where('price','!=',0)->pluck('document_id')->unique()->toArray();

    //foreach($doc_ids as $id){
    //    set_document_lines_gp($id);
    //}
}

function set_document_lines_gp_usd()
{
    $account_ids = \DB::table('crm_accounts')->where('currency', 'USD')->pluck('id')->toArray();
    $doc_ids = \DB::table('crm_documents')->where('docdate', 'LIKE', date('Y-m').'%')->whereIn('account_id', $account_ids)->pluck('id')->toArray();

    foreach ($doc_ids as $id) {
        set_document_lines_gp($id);
    }
}

function set_document_lines_gp($document_id = null, $docdate = null, $conn = 'default')
{
    if ($document_id) {
        $documents = \DB::connection($conn)->table('crm_documents')->select('id', 'doctype', 'document_currency', 'docdate', 'bill')->where('id', $document_id)->get();
    } elseif ($docdate) {
        $documents = \DB::connection($conn)->table('crm_documents')->select('id', 'doctype', 'document_currency', 'docdate', 'bill')->where('docdate', $docdate)->get();
    } else {
        $documents = \DB::connection($conn)->table('crm_documents')->select('id', 'doctype', 'document_currency', 'docdate', 'bill')->get();
    }
    $stock_products = \DB::connection($conn)->table('crm_products')->select('id')->where('is_supplier_product', 1)->orWhere('type', 'Stock')->pluck('id')->toArray();

    $airtime_products = \DB::connection($conn)->table('crm_products')->select('id')->where('code', 'LIKE', '%airtime%')->pluck('id')->toArray();
    foreach ($documents as $document) {
        $document_lines = \DB::connection($conn)->table('crm_document_lines')->where('document_id', $document->id)->get();
        foreach ($document_lines as $document_line) {
            $data = [];

            $data['cost_price'] = get_document_cost_price($document, $document_line);

            $data['cost_total'] = $data['cost_price'] * $document_line->qty;
            $data['sale_total'] = $document_line->price * $document_line->qty;
            if (! in_array($document_line->product_id, $stock_products)) {
                $data['cost_price'] = 0;
                $data['cost_total'] = 0;
            }
            if ($document->doctype == 'Credit Note') {
                $data['cost_total'] = $data['cost_total'] * -1;
                $data['sale_total'] = $data['sale_total'] * -1;
            }

            if ($document->document_currency != 'ZAR') {
                $data['zar_sale_total'] = currency_to_zar($document->document_currency, $data['sale_total'], $document->docdate);
                $data['zar_cost_total'] = currency_to_zar($document->document_currency, $data['cost_total'], $document->docdate);
                $data['zar_price'] = currency_to_zar($document->document_currency, $document_line->price, $document->docdate);
            } else {
                $data['zar_sale_total'] = $data['sale_total'];
                $data['zar_cost_total'] = $data['cost_total'];
                $data['zar_price'] = $document_line->price;
            }

            $data['gp'] = currency($data['zar_sale_total'] - $data['zar_cost_total']);

            $data['gpp'] = ($data['zar_sale_total'] != 0) ? $data['gp'] / $data['zar_sale_total'] : 0;
            /*
            if ($document_line->product_id == 147 || in_array($document_line->product_id,$airtime_products)) {
                $data['cost_price'] = $data['sale_total'];
                $data['zar_cost_total'] = $data['zar_sale_total'];
                $data['gp'] = 0;
                $data['gpp'] = 0;
            }
            */

            \DB::connection($conn)->table('crm_document_lines')->where('id', $document_line->id)->update($data);
        }

    }
}

function set_document_lines_gp_period($docdate, $conn = 'default')
{
    $document_ids = \DB::connection($conn)->table('crm_documents')
        ->select('id', 'doctype', 'document_currency', 'docdate', 'bill')
        ->where('docdate', 'like', date('Y-m', strtotime($docdate)).'%')
        ->pluck('id')->toArray();
    foreach ($document_ids as $document_id) {
        set_document_lines_gp($document_id);
    }
}

function create_subscription_cancellation_invoice($subscription_id)
{
    $sub = \DB::table('sub_services')->where('id', $subscription_id)->get()->first();
    if ($sub->status == 'Deleted') {
        return false;
    }

    if (date('Y-m-d') < date('Y-m-d', strtotime($sub->date_activated.' +3 months'))) {
        $cancellation_product_id = 1395;
        $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
        $months = 0;
        if (date('Y-m') == date('Y-m', $sub->date_activated)) {
            $months = 2;
        }
        if (date('Y-m') == date('Y-m', $sub->date_activated.' +1 month')) {
            $months = 1;
        }
        if ($months) {
            $amount = currency($sub->price_incl * $months);
            $account_id = $sub->account_id;
            $reference = 'Cancellation Fee: '.$product->name.' '.$months.' remaining';
            $db = new DBEvent;
            $data = [
                'docdate' => date('Y-m-d'),
                'doctype' => 'Tax Invoice',
                'completed' => 1,
                'subscription_created' => 1,
                'account_id' => $account_id,
                'total' => $amount,
                'tax' => $amount - ($amount / 1.15),
                'reference' => $reference,
                'billing_type' => 'Cancellation',
                'qty' => [1],
                'price' => [$amount / 1.15],
                'full_price' => [$amount / 1.15],
                'product_id' => [$cancellation_product_id],
            ];

            $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

            if (! is_array($result) || empty($result['id'])) {
                return false;
            }
            \DB::table('crm_document_lines')->where('document_id', $result['id'])->update(['description' => $product->name.' - '.$product->code.': '.$sub->detail, 'subscription_id' => $sub->id]);

            return true;
        }
    }

    return false;
}

function undo_create_subscription_cancellation_invoice($subscription_id)
{
    $document_id = \DB::table('crm_document_lines')
        ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.id')
        ->where('crm_documents.billing_type', 'Cancellation')
        ->where('crm_documents.reversal_id', 0)
        ->where('subscription_id', $subscription_id)
        ->pluck('document_id')->first();
    if ($document_id) {
        create_credit_note_from_invoice($document_id);
    }
}

function create_vehicledb_invoice($account_id, $amount, $qty, $reference, $billing_type = '')
{
    $price = ($amount / $qty);
    $db = new DBEvent;

    aa('Quatity Received', $qty);
    $data = [
        'docdate' => date('Y-m-d'),
        'doctype' => 'Tax Invoice',
        'completed' => 1,
        'account_id' => $account_id,
        'total' => $amount,
        'tax' => $amount - ($amount / 1.15),
        'reference' => $reference,
        'billing_type' => $billing_type,
        'qty' => [$qty],
        'price' => [$price / 1.15],
        'full_price' => [$price / 1.15],
        'product_id' => [148],
    ];
    $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);
    if (! is_array($result) || empty($result['id'])) {
        return false;
    }

    $credits_balance = \DB::table('crm_purchase_history')->where('account_id', $account_id)->sum('charge');
    if (empty($credits_balance)) {
        $credits_balance = 0;
    }
    $data = [
        'account_id' => $account_id,
        'report_type' => 'credits_purchase',
        'charge' => $qty,
        'balance' => $credits_balance + $qty,
    ];
    dbinsert('crm_purchase_history', $data);

    return true;
}

function create_various_invoice($account_id, $amount, $reference, $billing_type = '')
{
    $db = new DBEvent;
    $data = [
        'docdate' => date('Y-m-d'),
        'doctype' => 'Tax Invoice',
        'completed' => 1,
        'account_id' => $account_id,
        'total' => $amount,
        'tax' => $amount - ($amount / 1.15),
        'reference' => $reference,
        'billing_type' => $billing_type,
        'qty' => [1],
        'price' => [$amount / 1.15],
        'full_price' => [$amount / 1.15],
        'product_id' => [147],
    ];

    $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

    if (! is_array($result) || empty($result['id'])) {
        return false;
    }

    return true;
}

function create_various_credit_note($account_id, $amount, $reference, $billing_type = '')
{
    $db = new DBEvent;
    $data = [
        'docdate' => date('Y-m-d'),
        'doctype' => 'Credit Note',
        'completed' => 1,
        'account_id' => $account_id,
        'total' => $amount,
        'tax' => $amount - ($amount / 1.15),
        'reference' => $reference,
        'billing_type' => $billing_type,
        'qty' => [1],
        'price' => [$amount / 1.15],
        'full_price' => [$amount / 1.15],
        'product_id' => [147],
    ];

    $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

    if (! is_array($result) || empty($result['id'])) {
        return false;
    }

    return true;
}

function get_document_cost_price($document, $line)
{
    // eldo office - remove all invoice costs
    if (session('instance')->id == 2) {
        return 0;
    }
    $product_id = $line->product_id;
    $line_price = $line->price;

    $stock_data = get_stock_balance($product_id, $document->docdate);

    $cost_price = $stock_data['cost_price'];

    if (empty($cost_price)) {
        $cost_price = 0;
    }

    if ($product_id == 147) { // various
        $cost_price = $line_price;
    } elseif ($line->prorata_difference > 0) { // prorata cost_price

        if ($cost_price > 0) {

            $line_total = $line->prorata_difference + $line->price;
            $percentage = ($line->price / $line_total) * 100;

            $cost_price = ($cost_price / 100) * $percentage;

        }
    }

    // usd
    if ($cost_price > 0 && $document->document_currency == 'USD') {
        $cost_price = convert_currency_zar_to_usd($cost_price, $document->docdate);
    }

    return $cost_price;
}

function schedule_subs_set_last_invoice_date()
{
    \DB::table('sub_services')->update(['created_month' => \DB::raw(" DATE_FORMAT(created_at, '%Y-%m')")]);
    \DB::table('sub_services')->where('status', 'Deleted')->update(['deleted_month' => \DB::raw(" DATE_FORMAT(deleted_at, '%Y-%m')")]);
    \DB::table('sub_services')->update(['last_invoice_date' => \DB::raw('created_at')]);
    \DB::table('sub_services')->where('price', 0)->where('status', '!=', 'Deleted')->update(['last_invoice_date' => date('Y-m-01')]);
    $subscriptions = \DB::table('sub_services')->where('price', '>', 0)->where('status', '!=', 'Deleted')->get();
    foreach ($subscriptions as $s) {
        $docdate = null;
        $account = dbgetaccount($s->account_id);
        if ($account->type == 'reseller_user') {
            $docdate = \DB::table('crm_documents as cd')
                ->join('crm_document_lines as cdl', 'cdl.document_id', '=', 'cd.id')
                ->where('cdl.description', 'LIKE', '%'.$s->detail.'%')
                ->where('reseller_user', $s->account_id)
                ->where('account_id', $account->partner_id)
                ->where('billing_type', '>', '')
                ->orderBy('docdate', 'desc')
                ->pluck('docdate')->first();
        } else {
            $docdate = \DB::table('crm_documents as cd')
                ->join('crm_document_lines as cdl', 'cdl.document_id', '=', 'cd.id')
                ->where('cdl.description', 'LIKE', '%'.$s->detail.'%')
                ->where('account_id', $s->account_id)
                ->where('billing_type', '>', '')
                ->orderBy('docdate', 'desc')
                ->pluck('docdate')->first();
        }

        if ($docdate) {
            \DB::table('sub_services')->where('id', $s->id)->update(['last_invoice_date' => $docdate]);
        }
    }
}

function schedule_quotes_update_document_totals()
{
    $docs = \DB::table('crm_documents')->where('doctype', 'Quotation')->get();
    foreach ($docs as $d) {

        refresh_document_total($d->id);
        $new_total = \DB::table('crm_documents')->where('id', $d->id)->pluck('total')->first();
        if (currency($new_total) != currency($d->total)) {

            email_document_pdf($d->id, true);
        }
    }
}

//// Update Quotes ProRata Pricing helper functions
function refresh_billing_document_total($document_id, $product_id = false)
{

    $document = \DB::table('crm_documents')->where('id', $document_id)->get()->first();
    $document_lines = \DB::table('crm_document_lines')->where('document_id', $document_id)->get();

    $subtotal = 0;
    $tax = 0;
    foreach ($document_lines as $line) {

        $updated_price = pricelist_get_price($document->account_id, $line->product_id, $line->qty);
        $updated_price->price;

        if ($line->subscription_id) {
            $line_data = (array) $line;
            $line_data['price'] = $updated_price->price;
            $line_data['full_price'] = $updated_price->full_price;
            $subscription = \DB::table('sub_services')->where('id', $line->subscription_id)->where('status', '!=', 'Deleted')->get()->first();

            // DISCOUNT FOR INITIAL INVOICE

            if ($subscription && $subscription->invoice_id && date('Y-m', strtotime($subscription->date_activated)) == date('Y-m', strtotime($document->docdate.' -1 month'))) {
                // calculate prorata days from initial invoice
                $invoice_doc_date = \DB::table('crm_documents')->where('id', $subscription->invoice_id)->pluck('docdate')->first();
                $invoice_date_days = intval(date('t', strtotime($invoice_doc_date)));
                $prorata_price = ($line_data['price'] / $invoice_date_days) * (intval(date('d', strtotime($invoice_doc_date))));

                $line_data['prorata_difference'] = $prorata_price;
                if ($prorata_price > 0) {
                    $line_data['price'] -= $line_data['prorata_difference'] / $line_data['qty'];
                    if (! str_contains($line_data['description'], '1st month prorata')) {
                        $line_data['description'] .= PHP_EOL.get_currency_symbol($document->document_currency).' '.currency($line_data['prorata_difference'] / $line_data['qty']).' 1st month prorata.';
                    }
                }
                $line_data['full_price'] = $updated_price->full_price;

                $subtotal += ($line->qty * $line_data['price']);
            } else {
                $subtotal += ($line->qty * $updated_price->full_price);
            }

            \DB::table('crm_document_lines')->where('id', $line->id)->update($line_data);
        } else {
            $subtotal += ($line->qty * $updated_price->full_price);
            if (currency($line->price) != currency($updated_price->full_price)) {
                \DB::table('crm_document_lines')->where('id', $line->id)->update(['price' => $updated_price->full_price, 'full_price' => $updated_price->full_price]);

            }
        }
    }

    if ($document->tax > 0) {
        $tax = $subtotal * 0.15;
    }
    $total = $subtotal + $tax;
    \DB::table('crm_documents')->where('id', $document_id)->update(['total' => $total, 'tax' => $tax]);
    set_document_lines_gp($document_id);

    return $pricing_changed;
}

function refresh_document_total($document_id, $product_id = false)
{
    $pricing_changed = false;
    $document = \DB::table('crm_documents')->where('id', $document_id)->get()->first();
    $document_lines = \DB::table('crm_document_lines')->where('document_id', $document_id)->get();

    $subtotal = 0;
    $tax = 0;
    foreach ($document_lines as $line) {
        if ($line->subscription_id) {
            $price = \DB::table('sub_services')->where('id', $line->subscription_id)->pluck('price')->first();
            \DB::table('crm_document_lines')->where('id', $line->id)->update(['price' => $price]);
            $pricing_changed = true;
            $subtotal += ($line->qty * $price);
        } else {
            $updated_price = pricelist_get_price($document->account_id, $line->product_id, $line->qty, $document->bill_frequency, $document->contract_period);

            $subtotal += ($line->qty * $updated_price->full_price);
            if (currency($line->price) != currency($updated_price->full_price)) {
                \DB::table('crm_document_lines')->where('id', $line->id)->update(['price' => $updated_price->full_price]);
                $pricing_changed = true;
            }
        }
    }
    if ($pricing_changed) {
        //if ($document->tax > 0) {
        $tax = $subtotal * 0.15;
        //}
        $total = $subtotal + $tax;

        \DB::table('crm_documents')->where('id', $document_id)->update(['total' => $total, 'tax' => $tax]);
    }

    return $pricing_changed;
}

function update_document_total($document_id, $product_id)
{
    $document = \DB::table('crm_documents')->where('id', $document_id)->get()->first();
    $document_lines = \DB::table('crm_document_lines')->where('document_id', $document_id)->get();

    $subtotal = 0;
    $tax = 0;
    foreach ($document_lines as $line) {
        if ($product_id == $line->product_id) {
            $updated_price = pricelist_get_price($document->account_id, $line->product_id, $line->qty);
            $subtotal += ($line->qty * $updated_price->full_price);
            \DB::table('crm_document_lines')->where('document_id', $document_id)->where('product_id', $line->product_id)->update(['price' => $updated_price->full_price]);
        } else {
            $subtotal += ($line->qty * $line->price);
        }
    }

    //  if ($document->tax > 0) {
    $tax = $subtotal * 0.15;
    // }
    $total = $subtotal + $tax;

    \DB::table('crm_documents')->where('id', $document_id)->update(['total' => $total, 'tax' => $tax]);
}
