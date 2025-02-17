<?php

function schedule_activations_not_processed()
{
    // emails every 10 minutes
    if (! is_main_instance()) {
        return false;
    }
    if (! is_working_hours()) {
        return false;
    }
    $activations = \DB::table('sub_activations')
        ->join('crm_products', 'crm_products.id', '=', 'sub_activations.product_id')
        ->join('crm_accounts', 'crm_accounts.id', '=', 'sub_activations.account_id')
        ->select('sub_activations.id', 'crm_accounts.company', 'crm_products.name')
        ->where('sub_activations.status', 'Pending')
        ->where('sub_activations.is_deleted', 0)
        ->where('sub_activations.partner_id', 1)
        ->where('sub_activations.created_at', '<', date('Y-m-d H:i', strtotime('-1 hour')))
        ->orderBy('sub_activations.account_id')
        ->orderBy('sub_activations.product_id')
        ->get();

    $message = '';
    foreach ($activations as $activation) {
        $message .= $activation->company.' - '.$activation->name.'<br>';
    }

    if ($message > '') {
        $msg = 'The following activations has not been processed within one hour:<br><br>'.$message;

        //staff_email(3696, 'Activations needs to be processed', $msg);
        staff_email(4194, 'Activations needs to be processed', $msg, 'ahmed@telecloud.co.za');
    }
}

function button_operations_activations_process($request)
{
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();

    // if product activation does not have debit order and product is contract return warning
    $contract_product_ids = \DB::table('crm_products')->where('status', 'Enabled')->where('contract_period', '>', 0)->pluck('id')->toArray();
    if (in_array($sub->product_id, $contract_product_ids)) {

        // if(session('instance')->directory == 'moviemagic'){
        //     $payfast_subscription_active = account_has_payfast_subscription($sub->account_id);
        //     if(!$payfast_subscription_active){
        //         return json_alert('Account requires active PayFast subscription.','warning');
        //     }
        // }

        if (session('instance')->directory == 'telecloud') {
            $active_debit_order = account_has_debit_order($sub->account_id);
            if (! $active_debit_order) {
                $product_has_debit_order_activation_step = false;
                $activation_type_ids = \DB::table('sub_activation_plans')->where('type', 'Debitorder')->where('status', 'Enabled')->pluck('activation_type_id')->unique()->toArray();
                if (count($activation_type_ids) > 0) {
                    $debit_order_product_ids = \DB::table('crm_products')->where('status', 'Enabled')->whereIn('provision_plan_id', $activation_type_ids)->pluck('id')->toArray();
                    if (in_array($sub->product_id, $debit_order_product_ids)) {
                        $product_has_debit_order_activation_step = true;
                    }
                }

                // if(!$product_has_debit_order_activation_step){
                //     return json_alert('Account requires active debit order.','warning');
                // }
            }
        }

    }

    if ($sub->status == 'Awaiting Third-Party') {
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Pending']);
    }

    $is_deactivation = \DB::table('sub_activation_types')->where('type', 'Deactivation')->where('name', $sub->provision_type)->count();

    if ($is_deactivation) {

        return redirect()->to('service_deactivate?id='.$request->id);
    }

    if ($sub->provision_type == 'deactivate') {

        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        if (! $sub->subscription_id) {
            return json_alert('Subscription Id not set', 'warning');
        }
        $ErpSubs = new ErpSubs;
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);
        $result = $ErpSubs->deleteSubscription($sub->subscription_id);
        if ($result !== true) {
            return json_alert($result, 'warning');
        }

        module_log(334, $sub->subscription_id, 'deactivated');

        return json_alert('Done');

    } elseif ($sub->provision_type == 'product_return') {
        $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);

        module_log(334, $sub->subscription_id, ' product return');

        return json_alert('Done');
    } elseif ($sub->provision_type == 'status_enabled') {
        $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        if (! $sub->subscription_id) {
            return json_alert('Subscription Id not set', 'warning');
        }
        \DB::table('sub_services')->where('id', $sub->subscription_id)->update(['status' => 'Enabled']);
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);

        module_log(334, $sub->subscription_id, 'enabled');

        return json_alert('Done');
    } elseif ($sub->provision_type == 'status_disabled') {
        $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        if (! $sub->subscription_id) {
            return json_alert('Subscription Id not set', 'warning');
        }
        \DB::table('sub_services')->where('id', $sub->subscription_id)->update(['status' => 'Disabled']);
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);
        module_log(334, $sub->subscription_id, 'disabled');

        return json_alert('Done');
    } else {
        $product_plan = \DB::table('crm_products')->where('id', $sub->product_id)->pluck('provision_plan_id')->first();
        $activation_plan_exists = \DB::table('sub_activation_types')->where('id', $product_plan)->count();
        if (! $activation_plan_exists) {
            return json_alert('Activation plan does not exists, please contact admin.', 'warning');
        }
    }

    return redirect()->to('provision?type=operations&id='.$request->id);
}

function beforesave_activations_check_commitment_date($request)
{
    $activation = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
    if (! empty($request->awaiting_third_party)) {
        $has_commitment = false;
        if (! empty($request->commitment_date) || ! empty($activation->commitment_date)) {
            $has_commitment = true;
        }
        if (! $has_commitment) {
            return json_alert('Commitment date required', 'warning');
        }
    }
}

function button_submit_number_porting_from($request)
{
    $activation = \DB::table('sub_activations')->where('id', $request->id)->where('status', 'Pending')->get()->first();

    $mail_data = [];
    //$mail_data['test_debug'] = 1;
    $mail_data['record_id'] = $activation->id;
    $mail_data['subscription_id'] = $activation->id;
    $mail_data['internal_function'] = 'number_porting_form';

    $webform_data = [];
    $webform_data['module_id'] = 577;
    $webform_data['account_id'] = $activation->account_id;
    $webform_data['id'] = $activation->id;
    $webform_data['record_id'] = $activation->id;
    $webform_data['subscription_id'] = $activation->id;

    $link_data = \Erp::encode($webform_data);
    $url = '/webform/'.$link_data;

    return redirect()->to($url);

}

function update_admin_only_activations()
{
    $activations = \DB::table('sub_activations')->where('is_deleted', 0)->where('status', 'Pending')->get();
    foreach ($activations as $activation) {

        $admin_only = \DB::table('sub_activation_plans')
            ->where('activation_type_id', $activation->activation_type_id)
            ->where('step', $activation->step)
            ->pluck('admin_only')->first();
        \DB::table('sub_activations')->where('id', $activation->id)->update(['admin_only_step' => $admin_only]);
    }
}

function schedule_update_admin_only_activations()
{
    update_admin_only_activations();
}

function aftersave_activation_types_set_admin_only($request)
{
    update_admin_only_activations();
}

function schedule_reset_awaiting_third_party()
{
    \DB::table('sub_activations')->where('awaiting_third_party', 1)->update(['awaiting_third_party' => 0]);
}

function onload_activation_types_set_current_step()
{
    $pending = \DB::table('sub_activations')->where('status', 'Pending')->get();

    foreach ($pending as $p) {
        $step = \DB::table('sub_activation_plans')->where('activation_type_id', $p->activation_type_id)->where('step', $p->step)->get()->first();
        $current_step = $step->step.' '.$step->name.' '.$step->type;
        \DB::table('sub_activations')->where('id', $p->id)->update(['current_step' => $current_step]);
    }

    \DB::statement('UPDATE sub_activations
    SET created_days = DATEDIFF(NOW(), created_at);');

    $expired_activations = \DB::table('sub_activations')->where('status', 'Pending')->where('created_days', '>', 30)->get();
    foreach ($expired_activations as $expired_activation) {
        $system_user_id = get_system_user_id();
        $data = [
            'updated_by' => $system_user_id,
            'updated_at' => date('Y-m-d H:i:s'),
            'expired' => 1,
            // 'is_deleted' => 1,
        ];
        \DB::table('sub_activations')->where('id', $expired_activation->id)->update($data);
    }

}

function schedule_set_form_submitted_field()
{
    \DB::table('sub_activations')->update(['form_status' => '']);
    $pending = \DB::table('sub_activations')->where('status', 'Pending')->get();
    $email_steps = \DB::table('sub_activation_plans')->where('type', 'Email')->pluck('id')->toArray();

    foreach ($pending as $p) {
        $form_sent = \DB::table('sub_activation_steps')->where('provision_id', $p->id)->whereIn('provision_plan_id', $email_steps)->count();
        if ($form_sent) {
            \DB::table('sub_activations')->where('id', $p->id)->update(['form_status' => 'Form Sent']);
        }
    }
    $ids = \DB::connection('default')->table('sub_forms_number_porting')->pluck('subscription_id')->toArray();
    \DB::table('sub_activations')->whereIn('id', $ids)->update(['form_status' => 'Form Received']);
    $ids = \DB::connection('default')->table('sub_forms_ecommerce')->pluck('subscription_id')->toArray();
    \DB::table('sub_activations')->whereIn('id', $ids)->update(['form_status' => 'Form Received']);

}

function schedule_resend_pending_number_porting_emails()
{
    $activations = \DB::table('sub_activations')->where('product_id', 126)->where('status', 'Pending')->where('form_status', 'Form Sent')->get();
    foreach ($activations as $activation) {
        $mail_data = [];
        //$mail_data['test_debug'] = 1;
        $mail_data['record_id'] = $activation->id;
        $mail_data['subscription_id'] = $activation->id;
        $mail_data['internal_function'] = 'number_porting_form';

        $webform_data = [];
        $webform_data['module_id'] = 577;
        $webform_data['account_id'] = $activation->account_id;
        $webform_data['id'] = $activation->id;
        $webform_data['record_id'] = $activation->id;
        $webform_data['subscription_id'] = $activation->id;

        $link_data = \Erp::encode($webform_data);
        $mail_data['webform_link'] = '<a href="https://'.session('instance')->domain_name.'/webform/'.$link_data.'" >Number Porting Form</a>';
        erp_process_notification($activation->account_id, $mail_data);

    }
}

function buttton_activations_credit_invoice_line($request)
{
    if (session('role_level') != 'Admin') {
        return json_alert('No Access', 'warning');
    }

    $activation = \DB::table('sub_activations')->where('id', $request->id)->get()->first();

    if ($activation->status != 'Pending') {
        return json_alert('Only pending activations can be credited', 'warning');
    }

    if (empty($activation->invoice_id)) {
        return json_alert('Invoice Id not set', 'warning');
    }

    $line_id = $activation->invoice_line_id;
    $invoice_id = $activation->invoice_id;
    $credited = \DB::table('crm_document_lines')->where('original_line_id', $invoice_id)->count();
    if ($credited) {
        return json_alert('Activation already credited', 'warning');
    }

    $invoice = \DB::table('crm_documents')->where('id', $invoice_id)->get()->first();
    $invoice_line = \DB::table('crm_document_lines')->where('document_id', $invoice_id)->where('id', $line_id)->get()->first();
    if (empty($invoice) || empty($invoice_line)) {
        return json_alert('Original invoice not set', 'warning');
    }
    $data = (array) $invoice;
    $data['docdate'] = (date('Y-m-d') > $invoice->docdate) ? date('Y-m-d') : $invoice->docdate;
    $data['doctype'] = 'Credit Note';
    $data['credit_note_reason'] = 'Activation deleted';
    $data['reversal_id'] = $invoice_id;
    unset($data['id']);

    // set credit not total from line
    $document_subtotal = $invoice_line->qty * $invoice_line->price;
    $document_total = $document_subtotal;
    $document_tax = 0;
    if ($invoice->tax > 0) {
        $document_total = $document_subtotal * 1.15;
        $document_tax = ($document_subtotal * 1.15) - $document_subtotal;
    }
    $data['total'] = $document_total;
    $data['tax'] = $document_tax;

    $credit_note_id = \DB::table('crm_documents')->insertGetId($data);

    $line_data = (array) $invoice_line;
    $line_data['document_id'] = $credit_note_id;
    $line_data['original_line_id'] = $line_id;
    unset($line_data['id']);
    \DB::table('crm_document_lines')->insert($line_data);

    \DB::table('crm_documents')->where('id', $invoice_id)->update(['reversal_id' => $credit_note_id]);

    $db = new DBEvent;
    $db->setTable('crm_documents');
    $db->postDocument($credit_note_id);
    $db->postDocumentCommit();

    \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Credited', 'deleted_at' => date('Y-m-d H:i:s')]);

    return json_alert('Activation credited', 'success');
}

function aftersave_activation_types_set_is_manual($request)
{

    $sql = 'UPDATE sub_activation_plans 
    JOIN sub_activation_types ON sub_activation_types.id=sub_activation_plans.activation_type_id
    SET sub_activation_plans.admin_only = sub_activation_types.is_manual';
    \DB::statement($sql);
}

function aftersave_activation_type_set_name($request)
{
    $sql = 'UPDATE sub_activation_plans 
    JOIN sub_activation_types ON sub_activation_types.id=sub_activation_plans.activation_type_id
    SET sub_activation_plans.name = sub_activation_types.name';
    \DB::statement($sql);

    $plan = \DB::table('sub_activation_types')->where('id', $request->id)->get()->first();
    $provision_type = $plan->name;

    $product_ids = \DB::table('crm_products')->where('provision_plan_id', $plan->id)->pluck('id')->toArray();

    if (empty($provision_type)) {
        $provision_type = '';
    }

    $activation_ids = \DB::table('sub_activations')
        ->where('status_provision_type', 0)
        ->where('subscription_id', 0)
        ->where('status', 'Pending')
        ->where('provision_type', '!=', $provision_type)
        ->whereIn('product_id', $product_ids)
        ->pluck('id')->toArray();

    \DB::table('sub_activations')
        ->whereIn('id', $activation_ids)
        ->update(['step' => 1, 'provision_type' => $provision_type]);

    \DB::table('sub_activation_steps')
        ->whereIn('provision_id', $activation_ids)
        ->where('service_table', 'sub_activations')->delete();
}

function schedule_delete_test_activations()
{
    $ids = \DB::table('sub_activations')->where('is_test')->pluck('id')->toArray();
    if (count($ids) > 0) {
        \DB::table('sub_activation_steps')->whereIn('provision_id', $ids)->where('service_table', 'sub_activations')->delete();
    }
    \DB::table('sub_activations')->where('is_test', 1)->delete();
}

function button_activation_types_create_test_activation($request)
{

    $plan = \DB::table('sub_activation_types')->where('id', $request->id)->get()->first();

    $product_id = \DB::table('crm_products')->where('provision_plan_id', $plan->id)->where('status', '!=', 'Deleted')->pluck('id')->first();
    if (! $product_id) {
        return json_alert('No product assigned', 'warning');
    }
    //  if(str_contains($plan->name,'airtime')){
    //      return json_alert('Airtime activation cannot be created','warning');
    //  }
    $data = [
        'account_id' => 1,
        'product_id' => $product_id,
        'bill_frequency' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'Pending',
        'created_by' => get_user_id_default(),
        'provision_type' => $plan->name,
        'step' => 1,
        'is_test' => 1,
    ];

    $test_id = dbinsert('sub_activations', $data);

    return redirect()->to(get_menu_url_from_module_id(554).'?id='.$test_id);
}

function button_operations_service_activate($request)
{
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();

    if ($sub->provision_type == 'deactivate') {

        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        if (! $sub->subscription_id) {
            return json_alert('Subscription Id not set', 'warning');
        }
        $ErpSubs = new ErpSubs;
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);
        $result = $ErpSubs->deleteSubscription($sub->subscription_id);
        if ($result !== true) {
            return json_alert($result, 'warning');
        }

        module_log(334, $sub->subscription_id, 'deactivated');

        return json_alert('Done');

    } elseif ($sub->provision_type == 'product_return') {
        $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);

        module_log(334, $sub->subscription_id, ' product return');

        return json_alert('Done');
    } elseif ($sub->provision_type == 'status_enabled') {
        $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        if (! $sub->subscription_id) {
            return json_alert('Subscription Id not set', 'warning');
        }
        \DB::table('sub_services')->where('id', $sub->subscription_id)->update(['status' => 'Enabled']);
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);

        module_log(334, $sub->subscription_id, 'enabled');

        return json_alert('Done');
    } elseif ($sub->provision_type == 'status_disabled') {
        $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }

        if (! $sub->subscription_id) {
            return json_alert('Subscription Id not set', 'warning');
        }
        \DB::table('sub_services')->where('id', $sub->subscription_id)->update(['status' => 'Disabled']);
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);
        module_log(334, $sub->subscription_id, 'disabled');

        return json_alert('Done');

    } else {
        $product_plan = \DB::table('crm_products')->where('id', $sub->product_id)->pluck('provision_plan_id')->first();
        $activation_plan_exists = \DB::table('sub_activation_types')->where('id', $product_plan)->count();
        if (! $activation_plan_exists) {
            return json_alert('Activation plan does not exists, please contact admin.', 'warning');
        }
    }

    return redirect()->to('provision?type=operations&id='.$request->id);
}

function button_edit_activation_plan($request)
{
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
    $id = \DB::table('sub_activation_types')->where('type', 'Activation')->where('name', $sub->provision_type)->pluck('id')->first();

    return redirect()->to(get_menu_url_from_table('sub_activation_types').'?id='.$id);
}

function onload_activation_types_set_product_count($request)
{
    \DB::statement('UPDATE sub_activation_types SET product_count=(SELECT count(*) FROM crm_products where provision_plan_id=sub_activation_types.id and status!="Deleted")');
    \DB::statement('UPDATE sub_activation_types SET creates_subscription= CASE 
        WHEN (SELECT COUNT(*) FROM sub_activation_plans 
              WHERE activation_type_id = sub_activation_types.id 
              AND add_subscription = 1 
              AND status != "Deleted") > 0 
        THEN 1 
        ELSE 0 
    END;');
}

function get_activation_type_id($provision_name)
{

    $plan_id = \DB::table('sub_activation_types')->where('name', $provision_name)->pluck('id')->first();

    return $plan_id;
}

function get_activation_type_product_ids($provision_name)
{

    $plan_id = \DB::table('sub_activation_types')->where('name', $provision_name)->pluck('id')->first();
    if (! $plan_id) {
        return [];
    }
    $product_ids = \DB::table('crm_products')->where('provision_plan_id', $plan_id)->pluck('id')->toArray();

    return $product_ids;
}

function button_activation_view_email($request)
{
    $message = \DB::table('crm_email_manager')->where('id', $request->id)->get()->first();
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

function button_activation_email_send_test($request)
{
    $user_email = \DB::table('erp_users')->where('id', session('user_id'))->pluck('email')->first();
    $user_phone = \DB::table('erp_users')->where('id', session('user_id'))->pluck('erp_app_number')->first();
    $data = [
        'activation_email' => true,
        'test_debug' => 1,
        'force_to_email' => $user_email,
        'sms_phone_number' => $user_phone,
        'notification_id' => $request->id,
        'escape_email_variables' => true,
    ];
    $result = erp_process_notification(1, $data);
    if ($result == 'Sent') {
        return json_alert('Sent');
    } else {
        return json_alert('Error');
    }
}

function aftersave_activation_email_remove_encoded_characters($request)
{
    $email = \DB::table('crm_email_manager')->where('id', $request->id)->get()->first();
    $message = str_replace('&lt;', '<', str_replace('&gt;', '>', $email->message));
    $message = str_replace("\n", '<br />', $message);
    \DB::table('crm_email_manager')->where('id', $request->id)->update(['message' => $message]);
}

function onload_activation_emails_update_category_id()
{
    $categories = \DB::table('crm_product_categories')->where('is_deleted', 0)->pluck('id')->toArray();
    \DB::table('crm_email_manager')->whereNotIn('product_category_id', $categories)->update(['product_category_id' => 0]);
}

function aftersave_numberporting_update_activations($request)
{
    \DB::table('sub_activations')->update(['form_status' => '']);
    $pending = \DB::table('sub_activations')->where('status', 'Pending')->get();
    $email_steps = \DB::table('sub_activation_plans')->where('type', 'Email')->pluck('id')->toArray();

    foreach ($pending as $p) {
        $form_sent = \DB::table('sub_activation_steps')->where('provision_id', $p->id)->whereIn('provision_plan_id', $email_steps)->count();
        if ($form_sent) {
            \DB::table('sub_activations')->where('id', $p->id)->update(['form_status' => 'Form Sent']);
        }
    }
    $ids = \DB::connection('default')->table('sub_forms_number_porting')->pluck('subscription_id')->toArray();
    \DB::table('sub_activations')->whereIn('id', $ids)->update(['form_status' => 'Form Received']);
    $ids = \DB::connection('default')->table('sub_forms_ecommerce')->pluck('subscription_id')->toArray();
    \DB::table('sub_activations')->whereIn('id', $ids)->update(['form_status' => 'Form Received']);
}

function button_operations_submission($request)
{
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
    if ($sub->provision_type == 'number_porting') {
        $id = \DB::connection('default')->table('sub_forms_number_porting')->where('subscription_id', $request->id)->pluck('id')->first();
        if (! $id) {
            return json_alert('Form submission does not exists.', 'warning');
        }
        $porting_url = get_menu_url_from_table('sub_forms_number_porting');

        return redirect()->to($porting_url.'?id='.$id);
    }
    if ($sub->provision_type == 'ecommerce') {
        $id = \DB::connection('default')->table('sub_forms_ecommerce')->where('subscription_id', $request->id)->pluck('id')->first();
        if (! $id) {
            return json_alert('Form submission does not exists.', 'warning');
        }
        $ecommerce_url = get_menu_url_from_table('sub_forms_ecommerce');

        return redirect()->to($ecommerce_url.'?id='.$id);
    }
}

function button_operations_open_submission($request)
{
    // 571;
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
    if ($sub->provision_type == 'number_porting') {
        $id = \DB::connection('default')->table('sub_forms_number_porting')->where('subscription_id', $request->id)->pluck('id')->first();
        $webform_data = [];

        $webform_data['module_id'] = 577;
        $webform_data['account_id'] = $sub->account_id;
        if ($id) {
            $webform_data['id'] = $id;
        }

        $webform_data['subscription_id'] = $sub->id;

        $link_data = \Erp::encode($webform_data);
        $url = '/webform/'.$link_data;

        return redirect()->to($url);
    }
    if ($sub->provision_type == 'ecommerce') {
        $id = \DB::connection('default')->table('sub_forms_ecommerce')->where('subscription_id', $request->id)->pluck('id')->first();
        $webform_data = [];

        $webform_data['module_id'] = 571;
        $webform_data['account_id'] = $sub->account_id;
        if ($id) {
            $webform_data['id'] = $id;
        }

        $webform_data['subscription_id'] = $sub->id;

        $link_data = \Erp::encode($webform_data);
        $url = '/webform/'.$link_data;

        return redirect()->to($url);
    }
}
