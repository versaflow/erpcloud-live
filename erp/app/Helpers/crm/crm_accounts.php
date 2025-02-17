<?php

function schedule_debtors_send_notification()
{
    // sends email an sms to debtors with outstanding balances
    $accounts = \DB::table('crm_accounts')
    ->where('partner_id', 1)
    ->where('status', '!=', 'Deleted')
    ->whereIn('type', ['reseller', 'customer'])
    ->where('debtor_status_id', '!=', 1)
    ->where('aging', '>', 10)
    ->where('balance', '>', 0)
    ->get();
    foreach ($accounts as $account) {
        if (!account_has_debtor_commitment($account->id)) {
            $data['account_id'] = $account->id;
            $data['internal_function'] = 'statement_email';
            erp_process_notification($account->id, $data);
        }
    }
}


function schedule_set_company_names()
{
    $accounts = \DB::table('crm_accounts')->get();
    foreach ($accounts as $account) {
        $company = trim($account->company);
        \DB::table('crm_accounts')->where('id', $account->id)->update(['company' => $company]);
    }
}


function schedule_delete_cancelled_accounts()
{
    $accounts = \DB::table('crm_accounts')
        ->where('id', '!=', 12)
        ->where('id', '!=', 1)
        ->where('status', '!=', 'Deleted')
        ->where('account_status', 'Cancelled')
        ->where('cancel_date', '<=', date('Y-m-d'))
        ->get();

    foreach ($accounts as $account) {
        delete_account($account->id);
    }
}

function send_email_verification_link_all()
{
    return false;
    $account_ids = \DB::table('crm_accounts')->where('type', '!=', 'reseller_user')->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    foreach ($account_ids as $account_id) {
        send_email_verification_link($account_id);
    }
}

function send_email_verification_link($account_id)
{
    $verified = \DB::table('crm_accounts')->where('id', $account_id)->pluck('email_verified')->first();
    if (!$verified) {
        $link_data = ['account_id' => $account_id];

        $data['internal_function'] = 'email_verification';
        $data['verification_link'] = '<a href="'.url('email_verification/'.\Erp::encode($link_data)).'">Verify Email</a>';
        //$data['test_debug'] = 1;
        erp_process_notification($account_id, $data);
    }
}

function aftersave_customers_set_postpaid($request)
{
    $has_pbx = false;
    if ($request->type != 'reseller') {
        $has_pbx = \DB::connection('pbx')->table('v_domains')->where('account_id', $request->id)->count();

        if ($has_pbx) {
            if ($request->payment_type == 'Postpaid30Days' || $request->payment_type == 'Internal') {
                \DB::connection('pbx')->table('v_domains')->where('account_id', $request->id)->update(['is_postpaid' => 1, 'postpaid_limit' => 3000]);
            } else {
                \DB::connection('pbx')->table('v_domains')->where('account_id', $request->id)->update(['is_postpaid' => 0]);
            }
        }
    }
}

function schedule_accounts_set_is_deleted()
{
    \DB::connection('default')->table('crm_accounts')->where('status', 'Deleted')->update(['is_deleted' => 1]);
    \DB::connection('default')->table('crm_accounts')->where('account_status', '!=', 'Cancelled')->update(['account_status' => \DB::raw('status')]);

    $account_ids = \DB::connection('default')->table('crm_accounts')->where('status', 'Deleted')->pluck('id')->toArray();
    \DB::table('acc_debit_orders')->whereIn('account_id', $account_ids)->update(['status' => 'Deleted']);
}

function schedule_deleted_leads_delete_quotes()
{
    $draft_doctypes = ['Quotation', 'Credit Note Draft'];
    $draft_doctype_account_ids = \DB::table('crm_documents')->whereIn('doctype', $draft_doctypes)->pluck('account_id')->toArray();
    $account_ids = \DB::table('crm_accounts')->whereIn('id', $draft_doctype_account_ids)->where('type', 'lead')->where('is_deleted', 1)->pluck('id')->toArray();

    foreach ($account_ids as $account_id) {
        $draft_doctypes = ['Quotation', 'Credit Note Draft'];
        $doc_ids = \DB::table('crm_documents')->where('account_id', $account_id)->whereIn('doctype', $draft_doctypes)->pluck('id')->toArray();
        $doc_module_ids = \DB::table('erp_cruds')->where('db_table', 'crm_documents')->pluck('id')->toArray();
        foreach ($doc_ids as $id) {
            \DB::table('crm_document_lines')->where('document_id', $id)->delete();
            \DB::table('crm_documents')->where('id', $id)->delete();

            \DB::table('crm_approvals')->where('row_id', $id)->whereIn('module_id', $doc_module_ids)->delete();
        }
    }
}

function button_accounts_set_currency_to_usd($request)
{
    $account_id = $request->id;
    $account = dbgetaccount($account_id);
    if ($account->partner_id != 1) {
        return json_alert('Resseller user accounts cannot be converted', 'warning');
    }
    if ($account->currency == 'USD') {
        return json_alert('Account already set to USD', 'warning');
    }
    $doc_count = \DB::table('crm_documents')->where('account_id', $account_id)->count();
    if ($doc_count) {
        return json_alert('Only accounts without documents can be converted to USD', 'warning');
    }
    $doc_count = \DB::table('acc_cashbook_transactions')->where('account_id', $account_id)->count();
    if ($doc_count) {
        return json_alert('Only accounts without transactions can be converted to USD', 'warning');
    }

    \DB::table('crm_accounts')
    ->where('id', $account_id)
    ->update(['currency' => 'USD', 'pricelist_id' => 2]);

    return json_alert('Account converted to USD');
}

function button_accounts_send_iptv_reseller_logins($request)
{
    $account_id = $request->id;

    $provision_plan = \DB::table('sub_activation_types')->where('name', 'iptv_reseller')->get()->first();
    $provision_plan_name = $provision_plan->name;
    $provision_plan_id = $provision_plan->id;

    if (empty($provision_plan_name)) {
        return json_alert('Invalid activation plan', 'error');
    }
    $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $provision_plan_id)->where('email_id', '>', '')->orderby('step', 'desc')->pluck('email_id')->first();
    if (empty($email_id)) {
        return json_alert('No setup instructions available for this product', 'error');
    }

    $data['activation_email'] = true;

    return email_form($email_id, $request->id, $data);
}

function button_accounts_send_logins($request)
{
    $account_id = $request->id;
    if ($account_id == 1 || $account_id == 12) {
        return json_alert('Cannot send for admin account', 'warning');
    }
    $user = \DB::table('erp_users')->where('account_id', $account_id)->get()->first();
    $id = $user->id;
    /////// SEND NEW LOGIN DETAILS

    $pass = generate_strong_password();
    $hashed_password = \Hash::make($pass);
    \DB::table('erp_users')->where('id', $user->id)->update(['password' => $hashed_password]);
    $user_email = $user->email;
    $account = dbgetaccount($user->account_id);
    if (1 == $account->partner_id) {
        $portal = 'http://'.$_SERVER['HTTP_HOST'];
    } else {
        $portal = 'http://'.session('instance')->alias;
    }
    $function_variables = get_defined_vars();
    $data['internal_function'] = 'create_account_settings';

    $data['username'] = $user->username;

    $data['login_url'] = $portal;

    $data['password'] = $pass;

    $reseller = dbgetaccount($account->partner_id);
    $data['portal_name'] = $reseller->company;

    erp_process_notification($user->account_id, $data, $function_variables);

    \DB::table('erp_user_sessions')->where('user_id', $id)->delete();

    return json_alert('Done');
}

function schedule_wholesale_accounts_pay_account_from_airtime()
{
    return false;
    /*
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','volume')->get();
    foreach($volume_domains as $volume_domain){
        $account_id = $volume_domain->account_id;
        $result = (new DBEvent())->setDebtorBalance($account_id);
        $account = dbgetaccount($account_id);
        if($account->balance <= 0){
            continue;
        }

        $pbx = \DB::connection('pbx')->table('v_domains')->where('account_id',$account->id)->get()->first();
        if($pbx->balance < $account->balance){
            continue;
        }
        $cash_amount = $account->balance;
        $cash_id = create_cash_transaction($account->id, $cash_amount,'Account paid from airtime balance');
        if(!$cash_id){
            continue;
        }

        \DB::table('acc_cashbook_transactions')->where('id',$cash_id)->update(['approved'=>1]);
        (new DBEvent())->setDebtorBalance($account->id);
        $airtime_history = [
        'created_at' => date('Y-m-d'),
        'domain_uuid' => $pbx->domain_uuid,
        'total' => $cash_amount,
        'balance' => $pbx->balance-$cash_amount,
        'type' => 'account_balance_paid',
        ];
        \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);


        \DB::connection('pbx')->table('v_domains')
        ->where('domain_uuid', $pbx->domain_uuid)
        ->update(['balance' => $pbx->balance-$cash_amount]);
    }
    */
}

function button_accounts_pay_account_from_airtime($request)
{
    return false;
    $result = (new DBEvent())->setDebtorBalance($request->id);
    $account = dbgetaccount($request->id);
    if ($account->balance <= 0) {
        return json_alert('Customer account balance is not in arrears', 'warning');
    }
    $has_pbx_domain = \DB::connection('pbx')->table('v_domains')->where('account_id', $account->id)->count();
    if (!$has_pbx_domain) {
        return json_alert('Customer does not have an active pbx domain', 'warning');
    }
    $pbx = \DB::connection('pbx')->table('v_domains')->where('account_id', $account->id)->get()->first();
    if ($pbx->balance < $account->balance) {
        return json_alert('Insufficient airtime balance', 'warning');
    }
    $cash_amount = $account->balance;
    $cash_id = create_cash_transaction($account->id, $cash_amount, 'Account paid from airtime balance');
    if (!$cash_id) {
        return json_alert('Error creating cash transaction', 'warning');
    }

    \DB::table('acc_cashbook_transactions')->where('id', $cash_id)->update(['approved' => 1]);
    (new DBEvent())->setDebtorBalance($account->id);
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

    return json_alert('Cash transaction created', 'success');
}

function afterdelete_accounts_set_is_deleted()
{
    \DB::connection('default')->table('crm_accounts')->where('status', 'Deleted')->update(['is_deleted' => 1]);
}

function send_account_cancel_email($account_id)
{
    $account = dbgetaccount($account_id);
    if ($account->partner_id == 1) {
        if ($account->type == 'reseller') {
            $reseller_account_ids = \DB::connection('default')->table('crm_accounts')->where('partner_id', $account_id)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
            $subscriptions = \DB::connection('default')->table('sub_services')->where('account_id', $reseller_account_ids)->where('status', '!=', 'Deleted')->get();
        } else {
            $subscriptions = \DB::connection('default')->table('sub_services')->where('account_id', $account_id)->where('status', '!=', 'Deleted')->get();
        }
        foreach ($subscriptions as $subscription) {
            $data['internal_function'] = 'service_status_change';
            $data['status_change'] = 'Cancelled';
            $data['status_description'] = 'Your '.$subscription->provision_type.' '.$subscription->detail.' has been cancelled.';
            erp_process_notification($subscription->account_id, $data);
        }
    }
}

function send_account_delete_email($account_id)
{
    $account = dbgetaccount($account_id);

    if ($account->partner_id == 1) {
        if ($account->type == 'reseller') {
            $reseller_account_ids = \DB::connection('default')->table('crm_accounts')->where('partner_id', $account_id)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
            $subscriptions = \DB::connection('default')->table('sub_services')->where('account_id', $reseller_account_ids)->where('status', '!=', 'Deleted')->get();
        } else {
            $subscriptions = \DB::connection('default')->table('sub_services')->where('account_id', $account_id)->where('status', '!=', 'Deleted')->get();
        }
        foreach ($subscriptions as $subscription) {
            $data['internal_function'] = 'service_status_change';
            $data['status_change'] = 'Deleted';
            $data['status_description'] = 'Your '.$subscription->provision_type.' '.$subscription->detail.' has been cancelled.';
            erp_process_notification($subscription->account_id, $data);
        }
    }
}

function schedule_accounts_set_documents_last_call()
{
    $sql = 'UPDATE crm_invalid_contacts 
    JOIN crm_accounts ON crm_invalid_contacts.account_id=crm_accounts.id
    SET crm_invalid_contacts.last_call = crm_accounts.last_call
    WHERE crm_accounts.last_call is not null';
    \DB::statement($sql);
}

function schedule_accounts_set_invoice_days()
{
    $accounts = \DB::table('crm_accounts')->get();
    foreach ($accounts as $account) {
        if ($account->partner_id == 1) {
            $aging_date = \DB::connection('default')->table('crm_documents')->where('doctype', 'Tax Invoice')->where('account_id', $account->id)->orderby('docdate', 'desc')->pluck('docdate')->first();
        } else {
            $aging_date = \DB::connection('default')->table('crm_documents')->where('doctype', 'Tax Invoice')->where('reseller_user', $account->id)->orderby('docdate', 'desc')->pluck('docdate')->first();
        }
        $data['invoice_days'] = 0;
        if (!empty($aging_date)) {
            if (date('Y-m-d', strtotime($aging_date)) < date('Y-m-d')) {
                $date = Carbon\Carbon::parse($aging_date);
                $now = Carbon\Carbon::today();

                $data['invoice_days'] = $date->diffInDays($now);
            }
            $data['last_invoice_date'] = $aging_date;
        } else {
            $data['invoice_days'] = 0;
            $data['last_invoice_date'] = null;
        }
        \DB::table('crm_accounts')->where('id', $account->id)->update($data);
    }

    \DB::connection('default')->table('crm_accounts')->update(['last_invoice_date' => '0000-00-00']);
    $accounts = \DB::connection('default')->table('crm_accounts')->where('partner_id', 1)->where('status', '!=', 'Deleted')->get();
    foreach ($accounts as $account) {
        $docdate = \DB::connection('default')->table('crm_documents')->where('doctype', 'Tax Invoice')->where('account_id', $account->id)->orderBy('docdate', 'desc')->pluck('docdate')->first();
        if ($docdate) {
            \DB::connection('default')->table('crm_accounts')->where('id', $account->id)->update(['last_invoice_date' => $docdate]);
        }
    }
}

function button_accounts_delete_services($request)
{
    $account = \DB::table('crm_accounts')->where('id', $request->id)->get()->first();
    if ($account->type == 'reseller') {
        $reseller_account_ids = \DB::table('crm_accounts')->where('partner_id', $request->id)->pluck('id')->toArray();
        $subscription_ids = \DB::table('sub_services')->whereIn('account_id', $reseller_account_ids)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    } else {
        $subscription_ids = \DB::table('sub_services')->where('account_id', $request->id)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    }
    $subs = new ErpSubs();
    if (!empty($subscription_ids) && count($subscription_ids) > 0) {
        foreach ($subscription_ids as $subscription_id) {
            $subs->deleteSubscription($subscription_id);
        }
    }

    return json_alert('Done');
}

function generate_refferal_link($account_id)
{
    $account = dbgetaccount($account_id);

    if (empty($account) || 1 != $account->partner_id || ('customer' != $account->type && 'reseller' != $account->type) || 'Deleted' == $account->status) {
        return '';
    }

    $data = ['account_id' => $account_id];

    $encoded_link = url('/user/register?referral_code=').\Erp::encode($data);

    return $encoded_link;
}

function copy_turnkey_to_cloud($account_id)
{
    //return false;
    $from_connection = 'turnkey';
    $to_connection = 'default';
    $schema = get_complete_schema();
    try {
        \DB::connection($from_connection)->beginTransaction();
        \DB::connection($to_connection)->beginTransaction();

        // copy customer
        $customer = \DB::connection($from_connection)->table('crm_accounts')->where('id', $account_id)->get()->first();
        $customer_data = (array) $customer;
        unset($customer_data['id']);
        $customer_data['currency'] = 'USD';
        $new_account_id = \DB::connection($to_connection)->table('crm_accounts')->insertGetId($customer_data);

        // copy user
        $users = \DB::connection($from_connection)->table('erp_users')->where('account_id', $customer->id)->get();
        foreach ($users as $user) {
            $users_data = (array) $user;
            unset($users_data['id']);
            $users_data['account_id'] = $new_account_id;
            \DB::connection($to_connection)->table('erp_users')->where('username', $users_data['username'])->delete();
            \DB::connection($to_connection)->table('erp_users')->insert($users_data);
        }

        // copy documents
        $docs = \DB::connection($from_connection)->table('crm_documents')->where('account_id', $customer->id)->get();
        foreach ($docs as $doc) {
            $doc_data = (array) $doc;
            unset($doc_data['id']);
            $doc_data['account_id'] = $new_account_id;
            foreach ($doc_data as $k => $v) {
                if ($v === null) {
                    unset($doc_data[$k]);
                }
            }
            $new_doc_id = \DB::connection($to_connection)->table('crm_documents')->insertGetId($doc_data);

            $doclines = \DB::connection($from_connection)->table('crm_document_lines')->where('document_id', $doc->id)->get();
            foreach ($doclines as $docline) {
                $docline_data = (array) $docline;
                unset($docline_data['id']);
                $docline_data['document_id'] = $new_doc_id;
                foreach ($docline_data as $k => $v) {
                    if ($v === null) {
                        unset($docline_data[$k]);
                    }
                }
                \DB::connection($to_connection)->table('crm_document_lines')->insert($docline_data);
            }
        }

        // copy subscriptions
        $subs = \DB::connection($from_connection)->table('sub_services')->where('account_id', $customer->id)->where('status', '!=', 'Deleted')->get();
        foreach ($subs as $sub) {
            $sub_data = (array) $sub;
            unset($sub_data['id']);
            $sub_data['account_id'] = $new_account_id;
            foreach ($sub_data as $k => $v) {
                if ($v === null) {
                    unset($sub_data[$k]);
                }
            }
            \DB::connection($to_connection)->table('sub_services')->insert($sub_data);
        }

        $domains = \DB::connection($from_connection)->table('isp_voice_pbx_domains')->where('account_id', $customer->id)->get();
        foreach ($domains as $domain) {
            $domains_data = (array) $domain;
            unset($domains_data['id']);
            $domains_data['account_id'] = $new_account_id;

            \DB::connection($to_connection)->table('isp_voice_pbx_domains')->insert($domains_data);
        }

        \DB::connection($from_connection)->commit();
        \DB::connection($to_connection)->commit();

        return $new_account_id;
    } catch (\Throwable $ex) {
        exception_log($ex);
        \DB::connection($from_connection)->rollBack();
        \DB::connection($to_connection)->rollBack();
    }

    return false;
}

function copy_cloud_to_energy($account_id)
{
    //return false;
    $from_connection = 'default';
    $to_connection = 'energy';
    $schema = get_complete_schema();
    try {
        \DB::connection($from_connection)->beginTransaction();
        \DB::connection($to_connection)->beginTransaction();

        // copy customer
        $customer = \DB::connection($from_connection)->table('crm_accounts')->where('id', $account_id)->get()->first();
        $customer_data = (array) $customer;
        unset($customer_data['id']);
        $customer_data['currency'] = 'USD';
        $new_account_id = \DB::connection($to_connection)->table('crm_accounts')->insertGetId($customer_data);

        // copy user
        $users = \DB::connection($from_connection)->table('erp_users')->where('account_id', $customer->id)->get();
        foreach ($users as $user) {
            $users_data = (array) $user;
            unset($users_data['id']);
            $users_data['account_id'] = $new_account_id;
            \DB::connection($to_connection)->table('erp_users')->where('username', $users_data['username'])->delete();
            \DB::connection($to_connection)->table('erp_users')->insert($users_data);
        }

        // copy documents
        $docs = \DB::connection($from_connection)->table('crm_documents')->where('account_id', $customer->id)->get();
        foreach ($docs as $doc) {
            $doc_data = (array) $doc;
            unset($doc_data['id']);
            $doc_data['account_id'] = $new_account_id;
            foreach ($doc_data as $k => $v) {
                if ($v === null) {
                    unset($doc_data[$k]);
                }
            }
            $new_doc_id = \DB::connection($to_connection)->table('crm_documents')->insertGetId($doc_data);

            $doclines = \DB::connection($from_connection)->table('crm_document_lines')->where('document_id', $doc->id)->get();
            foreach ($doclines as $docline) {
                $docline_data = (array) $docline;
                unset($docline_data['id']);
                $docline_data['document_id'] = $new_doc_id;
                foreach ($docline_data as $k => $v) {
                    if ($v === null) {
                        unset($docline_data[$k]);
                    }
                }
                \DB::connection($to_connection)->table('crm_document_lines')->insert($docline_data);
            }
        }

        \DB::connection($from_connection)->commit();
        \DB::connection($to_connection)->commit();

        return $new_account_id;
    } catch (\Throwable $ex) {
        exception_log($ex);
        \DB::connection($from_connection)->rollBack();
        \DB::connection($to_connection)->rollBack();
    }

    return false;
}

function copy_energy_to_cloud($account_id)
{
    //return false;
    $from_connection = 'energy';
    $to_connection = 'telecloud';
    $schema = get_complete_schema();
    try {
        \DB::connection($from_connection)->beginTransaction();
        \DB::connection($to_connection)->beginTransaction();
        $products = \DB::connection($from_connection)->table('crm_products')->get();
        // copy customer
        $customer = \DB::connection($from_connection)->table('crm_accounts')->where('id', $account_id)->get()->first();
        $customer_data = (array) $customer;
        unset($customer_data['id']);

        $new_account_id = \DB::connection($to_connection)->table('crm_accounts')->insertGetId($customer_data);

        // copy user
        $users = \DB::connection($from_connection)->table('erp_users')->where('account_id', $customer->id)->get();
        foreach ($users as $user) {
            $users_data = (array) $user;
            unset($users_data['id']);
            $users_data['account_id'] = $new_account_id;
            \DB::connection($to_connection)->table('erp_users')->where('username', $users_data['username'])->delete();
            \DB::connection($to_connection)->table('erp_users')->insert($users_data);
        }

        // copy documents
        $docs = \DB::connection($from_connection)->table('acc_cashbook_transactions')->where('account_id', $customer->id)->get();
        foreach ($docs as $doc) {
            $doc_data = (array) $doc;
            unset($doc_data['id']);
            $doc_data['account_id'] = $new_account_id;
            foreach ($doc_data as $k => $v) {
                if ($v === null) {
                    unset($doc_data[$k]);
                }
            }
            $new_doc_id = \DB::connection($to_connection)->table('acc_cashbook_transactions')->insertGetId($doc_data);
        }

        // copy documents
        $docs = \DB::connection($from_connection)->table('crm_documents')->where('account_id', $customer->id)->get();
        foreach ($docs as $doc) {
            $doc_data = (array) $doc;
            $doc_data['reference'] = 'China warehouse: '.$doc_data['reference'];
            unset($doc_data['id']);
            $doc_data['account_id'] = $new_account_id;
            foreach ($doc_data as $k => $v) {
                if ($v === null) {
                    unset($doc_data[$k]);
                }
            }
            $new_doc_id = \DB::connection($to_connection)->table('crm_documents')->insertGetId($doc_data);

            $doclines = \DB::connection($from_connection)->table('crm_document_lines')->where('document_id', $doc->id)->get();
            foreach ($doclines as $docline) {
                $docline_data = (array) $docline;
                unset($docline_data['id']);
                $docline_data['document_id'] = $new_doc_id;
                foreach ($docline_data as $k => $v) {
                    if ($v === null) {
                        unset($docline_data[$k]);
                    }
                }
                $product_code = $products->where('id', $docline->product_id)->pluck('code')->first();
                $docline_data['product_id'] = \DB::connection($to_connection)->table('crm_products')->where('code', $product_code)->pluck('id')->first();
                \DB::connection($to_connection)->table('crm_document_lines')->insert($docline_data);
            }
        }

        \DB::connection($from_connection)->commit();
        \DB::connection($to_connection)->commit();

        return $new_account_id;
    } catch (\Throwable $ex) {
        exception_log($ex);
        \DB::connection($from_connection)->rollBack();
        \DB::connection($to_connection)->rollBack();
    }

    return false;
}

function button_accounts_update_debtor_status($request)
{
    $erp = new DBEvent();
    $erp->setDebtorBalance($request->id);
    $account = dbgetaccount($request->id);
    switch_account($account->id, $account->status, false, true);

    return json_alert('Done');
}

function button_accounts_convert_to_lead($request)
{
    $has_order = DB::table('crm_documents')->where('account_id', $request->id)->where('doctype', 'Order')->where('reversal_id', 0)->count();
    $has_invoice = DB::table('crm_documents')->where('account_id', $request->id)->where('doctype', 'Tax Invoice')->where('reversal_id', 0)->count();
    $has_payment = DB::table('acc_cashbook_transactions')->where('account_id', $request->id)->count();

    if ($has_invoice || $has_order) {
        return json_alert('Customer cannot be converted, already invoiced.', 'warning');
    }
    if ($has_payment) {
        return json_alert('Customer cannot be converted, already made payment.', 'warning');
    }

    \DB::table('crm_accounts')->where('id', $request->id)->update(['type' => 'lead']);

    return json_alert('Done');
}

function get_account($account_id)
{
    return \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->get()->first();
}

function get_account_user($account_id)
{
    return \DB::connection('default')->table('erp_users')->where('account_id', $account_id)->get()->first();
}

function button_accounts_email($request)
{
    $menu = get_menu_url_from_table('crm_accounts');

    return redirect()->to(url('/context_menu/'.$menu.'/email/'.$request->id));
}
function button_accounts_phone($request)
{
    $menu = get_menu_url_from_table('crm_accounts');

    return redirect()->to(url('/context_menu/'.$menu.'/call/'.$request->id));
}

function button_accounts_airtime_invoice($request)
{
    airtime_invoice_from_balance($request->id);

    return json_alert('Done');
}

function schedule_account_cancellation_warning()
{
    $accounts = \DB::table('crm_accounts')
        ->where('id', '!=', 12)
        ->where('id', '!=', 1)
        ->where('partner_id', 1)
        ->where('status', '!=', 'Deleted')
        ->where('account_status', 'Cancelled')
        ->where('cancel_date', '>', date('Y-m-d'))
        ->get();

    foreach ($accounts as $account) {
        if ($account->cancel_date > date('Y-m-d') && date('Y-m-d') > date('Y-m-d', strtotime($account->cancel_date.' - 7 days'))) {
            $data = [];
            $data['function_name'] = 'schedule_account_cancellation_warning';
            $data['cancel_date'] = $account->cancel_date;
            erp_process_notification($account->id, $data);
        }
    }
}

function button_accounts_write_off_account($request)
{
    $account = dbgetaccount($request->id);
    if ($account->status != 'Deleted') {
        return json_alert('Only Deleted accounts can be written off.');
    }

    (new DBEvent())->setAccountAging($request->id, 1);
    write_off_account($request->id);
    (new DBEvent())->setAccountAging($request->id, 1);

    return json_alert('Account debt written off.');
}

function button_accounts_statement($request)
{
    return redirect()->to('/statement_pdf/'.$request->id);
}

function button_accounts_pricelist($request)
{
    $account = dbgetaccount($request->id);
    if ($account->type == 'reseller' && empty($account->pricelist_id)) {
        return redirect()->to('/products');
    }

    return redirect()->to('/pricelist_items?pricelist_id='.$account->pricelist_id);
}

function button_accounts_override_debtor_status($request)
{
    $overwritten_id = \DB::table('crm_debtor_status')->where('is_deleted', 0)->where('aging', -1)->pluck('id')->first();
    \DB::table('crm_accounts')->where('id', $request->id)->update(['debtor_status_id' => $overwritten_id]);

    return json_alert('Account updated.');
}

function button_accounts_reset_debtor_status($request)
{
    \DB::table('crm_accounts')->where('id', $request->id)->update(['debtor_status_id' => 0]);
    (new DBEvent())->setAccountAging($request->id);

    return json_alert('Account updated.');
}

function afterdelete_accounts_delete_services($request)
{
    delete_account($request->id);
}

function button_accounts_send_blacklist_letter($request)
{
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'blacklist_letter')->pluck('id')->first();
    $account = dbgetaccount($request->id);
    $data['account'] = $account;
    $data['aging'] = $account->aging;

    return email_form($email_id, $request->id, $data);
}

function button_accounts_send_letter_of_demand($request)
{
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'letter_of_demand')->pluck('id')->first();
    $account = dbgetaccount($request->id);
    $data['account'] = $account;
    $data['aging'] = $account->aging;

    return email_form($email_id, $request->id, $data);
}

function beforesave_check_deal_date($request)
{
    $account = \DB::table('crm_accounts')->where('id', $request->id)->get()->first();
    if ($account->deal_date != null && $account->deal_date != $request->deal_date) {
        return 'Cannot change deal date once set.';
    }
}

function is_internal_user()
{
    if (session('gid') < 10) {
        return true;
    } else {
        return false;
    }
}

function get_partner_company($account_id)
{
    $account = dbgetaccount($account_id);
    $reseller = dbgetaccount($account->partner_id);

    return $reseller->company;
}

function is_customer_active($account_id)
{
    return (1 == \DB::connection('default')->table('crm_accounts')->where(['id' => $account_id, 'status' => 'Enabled'])->count()) ? 1 : 0;
}

function is_partner_active($partner_id)
{
    return (1 == \DB::connection('default')->table('crm_accounts')->where(['id' => $partner_id, 'status' => 'Enabled'])->count()) ? 1 : 0;
}

function account_has_debit_order($account_id)
{
    $exists = \DB::table('acc_debit_orders')->where('account_id', $account_id)->where('status', 'Enabled')->count();
    if ($exists > 0) {
        return true;
    }

    return false;
}

function account_has_payfast_subscription($account_id)
{
    $exists = \DB::table('acc_payfast_subscriptions')->where('account_id', $account_id)->where('status', 'Enabled')->count();
    if ($exists > 0) {
        return true;
    }

    return false;
}

//// get_debtor_transactions

function get_debtor_transactions_sql($account_id)
{
    $partner_id = \DB::table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();

    $payments_table = 'acc_cashbook_transactions';
    if (1 == $partner_id) {
        $total_field = 'total';
        $account_field = 'account_id';
    } else {
        $total_field = 'service_total';
        $account_field = 'reseller_user';
    }

    $sql = 'select id, docdate, doctype, total, account_id, reference, reversal_id, cashbook_id,document_currency,doc_no from
    (select  '.$payments_table.'.id, docdate, "Cashbook Customer Receipt" as doctype, total *-1 as total, account_id, reference, 0 as reversal_id, cashbook_id,document_currency,doc_no from '.$payments_table.' 
    where  '.$payments_table.".account_id = '".$account_id."'  and api_status!='Invalid' and approved=1
    UNION ALL
    select doctable.id, docdate, doctype, ".$total_field.' *-1 as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no from crm_documents as doctable
    where doctable.'.$account_field." = '".$account_id."' and doctype = 'Credit Note' 
    UNION ALL
    select crm_documents.id, docdate, doctype, ".$total_field.' as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no from crm_documents 
    where crm_documents.'.$account_field." = '".$account_id."' and (doctype = 'Tax Invoice') 
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id,aj.document_currency,doc_no 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and debit_amount > 0 and aj.ledger_account_id=5
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id ,aj.document_currency,doc_no
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and credit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    ) crm_documents order by docdate, doctype asc, reference asc";
    // if(is_dev()){
    // print_r($sql);
    //exit;
    //}
    return $sql;
}
function get_debtor_transactions($account_id, $conn = 'default')
{
    $partner_id = \DB::connection($conn)->table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();

    $payments_table = 'acc_cashbook_transactions';
    if (1 == $partner_id) {
        $total_field = 'total';
        $account_field = 'account_id';
    } else {
        $total_field = 'service_total';
        $account_field = 'reseller_user';
    }

    $sql = 'select id, docdate, doctype, total, account_id, reference, reversal_id, cashbook_id,document_currency,doc_no,salesman_id from
    (select  '.$payments_table.'.id, docdate, "Cashbook Customer Receipt" as doctype, total *-1 as total, account_id, reference, 0 as reversal_id, cashbook_id,document_currency,doc_no,0 as salesman_id from '.$payments_table.' 
    where  '.$payments_table.".account_id = '".$account_id."'  and api_status!='Invalid' and approved=1
    UNION ALL
    select doctable.id, docdate, doctype, ".$total_field.' *-1 as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents as doctable
    where doctable.'.$account_field." = '".$account_id."' and doctype = 'Credit Note' 
    UNION ALL
    select crm_documents.id, docdate, doctype, ".$total_field.' as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents 
    where crm_documents.'.$account_field." = '".$account_id."' and (doctype = 'Tax Invoice') 
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id,aj.document_currency,doc_no,0 as salesman_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and debit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id ,aj.document_currency,doc_no,0 as salesman_id
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and credit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    ) crm_documents order by docdate, doctype asc, reference asc";
    //if(is_dev()){
    //print_r($sql);
    //exit;
    //}
    return \DB::connection($conn)->select($sql);
}

function get_debtor_transactions_including_pending($account_id)
{
    $partner_id = \DB::table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();

    $payments_table = 'acc_cashbook_transactions';
    if (1 == $partner_id) {
        $total_field = 'total';
        $account_field = 'account_id';
    } else {
        $total_field = 'service_total';
        $account_field = 'reseller_user';
    }
    if (is_dev()) {
        $sql = 'select id, docdate, doctype, total, account_id, reference, reversal_id, cashbook_id,document_currency,doc_no,salesman_id, @running_total:=@running_total + total AS running_total from
    (select  '.$payments_table.'.id, docdate, "Cashbook Customer Receipt" as doctype, total *-1 as total, account_id, reference, 0 as reversal_id, cashbook_id,document_currency,doc_no,0 as salesman_id from '.$payments_table.' 
    where  '.$payments_table.".account_id = '".$account_id."'  and api_status!='Invalid' and approved=1
    UNION ALL
    select doctable.id, docdate, doctype, ".$total_field.' *-1 as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents as doctable
    where doctable.'.$account_field." = '".$account_id."' and doctype IN ('Credit Note','Credit Note') 
    UNION ALL
    select crm_documents.id, docdate, doctype, ".$total_field.' as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents 
    where crm_documents.'.$account_field." = '".$account_id."'and doctype IN ('Tax Invoice','Quotation','Order')
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id,aj.document_currency,doc_no,0 as salesman_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and debit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id ,aj.document_currency,doc_no,0 as salesman_id
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and credit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    ) crm_documents
    JOIN (SELECT @running_total := 0 AS tmpvar) tmpvar 
    order by docdate, doctype asc, reference asc";
    } else {
        $sql = 'select id, docdate, doctype, total, account_id, reference, reversal_id, cashbook_id,document_currency,doc_no,salesman_id from
    (select  '.$payments_table.'.id, docdate, "Cashbook Customer Receipt" as doctype, total *-1 as total, account_id, reference, 0 as reversal_id, cashbook_id,document_currency,doc_no,0 as salesman_id from '.$payments_table.' 
    where  '.$payments_table.".account_id = '".$account_id."'  and api_status!='Invalid' and approved=1
    UNION ALL
    select doctable.id, docdate, doctype, ".$total_field.' *-1 as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents as doctable
    where doctable.'.$account_field." = '".$account_id."' and doctype IN ('Credit Note','Credit Note') 
    UNION ALL
    select crm_documents.id, docdate, doctype, ".$total_field.' as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents 
    where crm_documents.'.$account_field." = '".$account_id."'and doctype IN ('Tax Invoice','Quotation','Order')
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id,aj.document_currency,doc_no,0 as salesman_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and debit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id ,aj.document_currency,doc_no,0 as salesman_id
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and credit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    ) crm_documents order by docdate, doctype asc, reference asc";
    }
    //if(is_dev()){
    //print_r($sql);
    //exit;
    //}
    return \DB::select($sql);
}

function get_debtor_transactions_excluding_writeoff($account_id)
{
    $partner_id = \DB::table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();

    $payments_table = 'acc_cashbook_transactions';
    if (1 == $partner_id) {
        $total_field = 'total';
        $account_field = 'account_id';
    } else {
        $total_field = 'service_total';
        $account_field = 'reseller_user';
    }

    $sql = 'select id, docdate, doctype, total, account_id, reference, reversal_id, cashbook_id,document_currency,doc_no,salesman_id from
    (select  '.$payments_table.'.id, docdate, "Cashbook Customer Receipt" as doctype, total *-1 as total, account_id, reference, 0 as reversal_id, cashbook_id,document_currency,doc_no,0 as salesman_id from '.$payments_table.' 
    where  '.$payments_table.".account_id = '".$account_id."'  and api_status!='Invalid' and approved=1
    UNION ALL
    select doctable.id, docdate, doctype, ".$total_field.' *-1 as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents as doctable
    where doctable.'.$account_field." = '".$account_id."' and doctype = 'Credit Note' 
    UNION ALL
    select crm_documents.id, docdate, doctype, ".$total_field.' as total, account_id, reference, reversal_id, 0 as cashbook_id,document_currency,doc_no,salesman_id from crm_documents 
    where crm_documents.'.$account_field." = '".$account_id."' and (doctype = 'Tax Invoice') 
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id,aj.document_currency,doc_no,0 as salesman_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and debit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1 and aj.reference!='Bad Debt Written Off' and aj.reference!='Account Restored'
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, account_id, reference, 0 as reversal_id, 0 as cashbook_id ,aj.document_currency,doc_no,0 as salesman_id
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and credit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1 and aj.reference!='Bad Debt Written Off' and aj.reference!='Account Restored'
    ) crm_documents order by docdate, doctype asc, reference asc";
    // if(is_dev()){
    // print_r($sql);
    //exit;
    //}
    return \DB::select($sql);
}

function customer_control_sql()
{
    $sql = "select id, docdate, doctype, total, account_id, reference, reversal_id from
    (select  acc_cashbook_transactions.id, docdate, 'Cashbook Customer Receipt' as doctype, total *-1 as total, account_id, reference, 0 as reversal_id from acc_cashbook_transactions
    where api_status!='Invalid' and approved=1 and account_id>0
    UNION ALL
    select doctable.id, docdate, doctype, total *-1 as total, account_id, reference, reversal_id from crm_documents as doctable
    where doctype = 'Credit Note' 
    UNION ALL
    select crm_documents.id, docdate, doctype,  total, account_id, reference, reversal_id from crm_documents 
    where (doctype = 'Tax Invoice') 
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, account_id, reference, 0 as reversal_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where  debit_amount > 0 and aj.ledger_account_id=5
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, account_id, reference, 0 as reversal_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where credit_amount > 0 and aj.ledger_account_id=5 and ajt.approved=1
    ) crm_documents order by docdate, doctype asc, reference asc";

    return $sql;
}

function get_pending_debtor_transactions($account_id)
{
    $partner_id = \DB::table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();

    $payments_table = 'acc_cashbook_transactions';
    if (1 == $partner_id) {
        $total_field = 'total';
        $account_field = 'account_id';
    } else {
        $total_field = 'service_total';
        $account_field = 'reseller_user';
    }

    $sql = 'select id, docdate, doctype, total, account_id, reference, reversal_id from
    (select  '.$payments_table.'.id, docdate, "Cashbook Customer Receipt" as doctype, total *-1 as total, account_id, reference, 0 as reversal_id from '.$payments_table.' 
    where  '.$payments_table.".account_id = '".$account_id."'  and api_status!='Invalid' 
    UNION ALL
    select doctable.id, docdate, doctype, ".$total_field.' *-1 as total, account_id, reference, reversal_id from crm_documents as doctable
    where doctable.'.$account_field." = '".$account_id."' and doctype = 'Credit Note' 
    UNION ALL
    select crm_documents.id, docdate, doctype, ".$total_field.' as total, account_id, reference, reversal_id from crm_documents 
    where crm_documents.'.$account_field." = '".$account_id."' and (doctype = 'Tax Invoice') 
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, account_id, reference, 0 as reversal_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and debit_amount > 0 and aj.ledger_account_id=5
    UNION ALL
    select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, account_id, reference, 0 as reversal_id 
    from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
    where account_id = '".$account_id."' and credit_amount > 0 and aj.ledger_account_id=5 
    ) crm_documents order by docdate, doctype asc, reference asc";
    // if(is_dev()){
    // print_r($sql);
    //exit;
    //}
    return \DB::select($sql);
}

function switch_account($account_id, $status = 'Enabled', $manual = false, $update_services = false)
{
    if ($account_id == 300992) {
        return false;
    }
    if (session('instance')->directory == 'telecloud' && $account_id == 12) {
        return false;
    }
    if ($account_id == 0) {
        return false;
    }

    $account = dbgetaccount($account_id);
    $account_status = $account->status;
    $payfast = account_has_payfast_subscription($account_id);
    $debit_order = account_has_debit_order($account_id);

    $internal = ($account->payment_type == 'Internal') ? true : false;

    if ('Deleted' == $account_status || $account_status == $status) {
        return false;
    }
    if (!$update_services) {
        if (!$manual && $account->debtor_status_id == 7) {
            return false;
        }

        if ($account->partner_id == 1) {
            if ($manual && check_access('1,31')) {
                if ($status == 'Enabled') {
                    if ($account->debtor_status_id == 7) {
                        \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => 0]);
                        process_aging_actions($account_id, false);
                        $account->debtor_status_id = 0;
                    }
                }
                if ($status == 'Disabled') {
                    \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => 7]);
                }
            }

            if ($account->debtor_status_id == 7 && $status == 'Enabled') {
                // return false;
                \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => 0]);
                process_aging_actions($account_id, false);
            }

            if (($internal) && $status == 'Disabled') {
                return false;
            }

            if ($account_type == 'Postpaid30Days') {
                if ($account->aging < 30 && $status == 'Disabled') {
                    return false;
                }
            }
        }

        $type = $account->type;
        if ('customer' == $type || 'reseller_user' == $type) {
            if ($status == 'Enabled') {
                $billing_on_hold = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('billing_on_hold', 1)->where('account_id', $account_id)->count();
                if ($billing_on_hold) {
                    return false;
                }
            }
        }
    }
    \DB::table('crm_accounts')->where('id', $account_id)->update(['status' => $status]);
    set_accounts_services_statuses($account_id);
    \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->where('account_status', '!=', 'Cancelled')->update(['account_status' => $status]);

    // VOICE & SMS

    if ($account->domain_uuid) {
        \DB::connection('pbx')->table('v_domains')
            ->where('domain_uuid', $account->domain_uuid)
            ->update(['status' => $status]);

        $domain_enabled = ($status == 'Disabled') ? 'false' : 'true';
        \DB::connection('pbx')->table('v_domains')
            ->where('domain_uuid', $account->domain_uuid)
            ->update(['status' => $status, 'domain_enabled' => $domain_enabled]);

        $extension_status = ($status == 'Disabled') ? 'false' : 'true';
        \DB::connection('pbx')->table('v_extensions')
            ->where('domain_uuid', $account->domain_uuid)
            ->update(['enabled' => $extension_status]);

        $pbx = new FusionPBX();
        $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $account->domain_uuid)->get();
        foreach ($extensions as $ext) {
            $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
            $pbx->portalCmd('portal_aftersave_extension', $key);
            if (!empty($ext->cidr)) {
                $pbx->portalCmd('portal_reloadacl');
            }
        }
    }

    if ('reseller' == $type && 1 != $account_id) {
        $partner_customers = \DB::table('crm_accounts')->where('partner_id', $account_id)->where('status', '!=', 'Deleted')->where('status', '!=', 'Disabled by Reseller')->get();
        if (!empty($partner_customers)) {
            foreach ($partner_customers as $customer) {
                switch_account($customer->id, $status);
            }
        }
    }

    if ('reseller_user' == $type && !empty(session('role_id')) && session('role_id') == 11) {
        if ($status == 'Disabled') {
            \DB::table('crm_accounts')->where('id', $account_id)->update(['status' => 'Disabled by Reseller']);
        }
    }

    try {
        if ($account_status != $status) {
            if ('customer' == $type || 'reseller_user' == $type) {
                $erp_subscriptions = new ErpSubs();
                $erp_subscriptions->setStatus($account_id, $status);
            }

            if ($account->partner_id == 1) {
                $attachments = [];

                $data['status_change'] = $status;
                if ($status == 'Disabled') {
                    $data['internal_function'] = 'account_status_disabled';
                }
                if ($status == 'Enabled') {
                    $data['internal_function'] = 'account_status_enabled';
                }

                if ($status == 'Disabled') {
                    $pdf = statement_pdf($account->id);
                    $file = 'Statement_'.$account->id.'_'.date('Y_m_d').'.pdf';
                    $filename = attachments_path().$file;
                    if (file_exists($filename)) {
                        unlink($filename);
                    }
                    $pdf->setTemporaryFolder(attachments_path());
                    $pdf->save($filename);

                    $attachments[] = $file;

                    //$data['test_debug'] = 1;
                    $data['attachments'] = $attachments;
                }

                //erp_process_notification($account->id, $data);
            }
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        exception_email($ex, 'account status update error');
    }

    $ip_customer = \DB::table('isp_data_ip_ranges')->where('account_id', $account_id)->count();
    if ($ip_customer) {
        schedule_import_ip_ranges();
    }
    module_log(343, $account_id, 'updated', 'account status '.$status);

    //  \DB::connection('pbx')->statement('UPDATE `cloudpbx`.`v_extensions`
    //  JOIN `cloudpbx`.`v_domains` ON v_extensions.domain_uuid = v_domains.domain_uuid
    //  SET v_extensions.enabled = v_domains.domain_enabled;');

    return true;
}

function beforedelete_check_account_services($request)
{
    $lte_accounts = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'lte_sim_card'])->where('status', '!=', 'Deleted')->get();
    if (!empty($lte_accounts)) {
        return 'Cannot delete account with active lte subscriptions';
    }
    $phone_numbers = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'phone_number'])->where('status', '!=', 'Deleted')->get();
    if (!empty($phone_numbers)) {
        return 'Cannot delete account with active phone number subscriptions';
    }
}

function cancel_account($account_id)
{
    $data_product_ids = get_data_product_ids();
    $account_has_data = \DB::connection('default')->table('sub_services')->whereIn('product_id', $data_product_ids)->where('status', '!=', 'Deleted')->where('account_id', $id)->count();
    $cancellation_period = get_admin_setting('cancellation_schedule');

    if ($cancellation_period == 'Immediately') {
        $cancel_date = date('Y-m-d');
        if ($account_has_data) {
            $cancel_date = date('Y-m-t', strtotime('+1 month'));
        }
    } elseif ($cancellation_period == 'This Month') {
        $cancel_date = date('Y-m-t');
        if ($account_has_data) {
            $cancel_date = date('Y-m-t', strtotime('+1 month'));
        }
    } elseif ($cancellation_period == 'Next Month') {
        $cancel_date = date('Y-m-t', strtotime('+1 month'));
    }
    \DB::table('crm_accounts')->where('id', $account_id)->update(['account_status' => 'Cancelled', 'cancel_date' => $cancel_date]);
}

//// Delete account
function delete_account($account_id, $deleted_at = false)
{
    $deleted_at = date('Y-m-d H:i:s');
    $account = dbgetaccount($account_id);
    // approval check
    if ($account->partner_id == 1) {
        if (!$account->cancel_approved) {
            $e = \DB::table('crm_approvals')->where('module_id', 343)->where('is_deleted', 0)->where('row_id', $account->id)->where('title', 'like', 'Account Delete%')->count();
            if (!$e) {
                if ($account->account_status == 'Cancelled') {
                    $delete_reason = 'Delete reason: Account Cancelled';
                } else {
                    $delete_reason = 'Delete reason: Debtor Process';
                }
                $data = [
                    'notes' => $delete_reason,
                    'module_id' => 343,
                    'row_id' => $account->id,
                    'title' => 'Account Delete '.$account->company.' #'.$account->id,
                    'processed' => 0,
                    'requested_by' => get_system_user_id(),
                ];
                (new \DBEvent())->setTable('crm_approvals')->save($data);
            }

            return false;
        }
    }

    $type = $account->type;
    if ($account->partner_id == 1) {
        \DB::table('acc_debit_orders')->where('account_id', $account->id)->update(['status' => 'Deleted']);
    }

    if ('reseller' == $type) {
        \DB::table('crm_accounts')->where('id', $account_id)->where('status', '!=', 'Deleted')->update(['status' => 'Deleted', 'deleted_at' => $deleted_at]);
        \DB::table('crm_account_partner_settings')->where('account_id', $account_id)->delete();
        $pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', $account_id)->pluck('id')->toArray();
        \DB::table('crm_pricelist_items')->whereIn('pricelist_id', $pricelist_ids)->delete();
        \DB::table('crm_pricelists')->where('partner_id', $account_id)->delete();
        \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', $account_id)->delete();
        \DB::connection('pbx')->table('p_rates_partner_items')->where('partner_id', $account_id)->delete();
        $partner_customers = \DB::table('crm_accounts')->where('partner_id', $account_id)->where('status', '!=', 'Deleted')->get();

        if (!empty($partner_customers)) {
            foreach ($partner_customers as $customer) {
                delete_account($customer->id, $deleted_at);
            }
        }
    }

    if ('customer' == $type || 'reseller_user' == $type) {
        if (in_array(8, session('app_ids'))) {
            if ('reseller_user' == $type) {
                \DB::table('crm_accounts')->where('id', $account_id)->where('status', '!=', 'Deleted')->update(['aging' => 0, 'balance' => 0]);
            }

            /// DELETE SERVICES

            // HOSTING
            $websites = \DB::table('isp_host_websites')
                ->where('account_id', $account_id)
                ->update(['to_update_nameservers' => 0, 'to_update_contact' => 0, 'to_register' => 0, 'to_delete' => 1, 'transfer_in' => 0, 'transfer_out' => 0]);

            // IP
            \DB::table('isp_data_ip_ranges')->where('account_id', $account_id)->update(['account_id' => 0]);

            // SMS
            \DB::table('isp_sms_messages')->where('account_id', $account_id)->delete();
            \DB::table('isp_sms_message_queue')->where('account_id', $account_id)->delete();
            \DB::table('isp_sms_templates')->where('account_id', $account_id)->delete();
            $sms_list_ids = \DB::table('isp_sms_lists')->where('account_id', $account_id)->pluck('id')->toArray();
            if (!empty($sms_list_ids) && is_array($sms_list_ids) && count($sms_list_ids) > 0) {
                \DB::table('isp_sms_list_numbers')->whereIn('sms_list_id', $sms_list_ids)->delete();
            }
            \DB::table('isp_sms_lists')->where('account_id', $account_id)->delete();
            \DB::table('isp_sms_inbox')->where('account_id', $account_id)->delete();
            \DB::table('isp_sms_email')->where('account_id', $account_id)->delete();

            // LTE
            $lte_accounts = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'lte_sim_card'])->where('status', '!=', 'Deleted')->get();
            if (!empty($lte_accounts)) {
                foreach ($lte_accounts as $lte_account) {
                    $data['detail'] = $lte_account->detail;
                    $data['account_company'] = $account->company;
                    $data['internal_function'] = 'lte_sim_card_cancel';
                    $data['cc_email'] = 'neliswa.sango@vodacom.co.za';
                    erp_process_notification(1, $data);
                }
            }

            // TELKOM LTE
            $lte_accounts = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'telkom_lte_sim_card'])->where('status', '!=', 'Deleted')->get();

            if (!empty($lte_accounts)) {
                foreach ($lte_accounts as $lte_account) {
                    $account = dbgetaccount($lte_account->account_id);
                    $data['detail'] = $lte_account->detail.' Telkom LTE Simcard';
                    $data['account_company'] = $account->company;
                    $data['internal_function'] = 'lte_sim_card_cancel';
                    //$data['cc_email'] = 'neliswa.sango@vodacom.co.za';
                    erp_process_notification(1, $data);
                }
            }
            /*
            $fibre_accounts =  \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'fibre'])->get();
            if (!empty($fibre_accounts)) {
                $axxess = new Axxess();
                foreach ($fibre_accounts as $fibre_sub) {
                    $fibre = \DB::table('isp_data_fibre')->where('username', $fibre_sub->detail)->get()->first();
                    $axxess->deleteComboService($fibre->guidClientId, $fibre->guidServiceId, date('Y-m-d'));
                }
            }
            */
            $fibre_accounts = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'fibre'])->get();
            if (!empty($fibre_accounts)) {
                foreach ($fibre_accounts as $fibre_sub) {
                    fibre_status_email($fibre_sub, 'Deleted');
                }
            }

            // VOIP
            if (!empty($account->pabx_domain)) {
                \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $account->domain_uuid)->where('status', 'Deleted')->update(['domain_uuid' => null, 'number_routing' => null, 'routing_type' => null]);
                \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $account->domain_uuid)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled', 'number_routing' => null, 'routing_type' => null]);

                pbx_delete_domain($account->pabx_domain, $account_id);
            }
        }
        /// DELETE SUBSCRIPTIONS

        // send_account_delete_email($account_id);

        \DB::table('sub_services')->where('account_id', $account_id)->where('status', 'Deleted')->delete();
        \DB::table('sub_services')->where('account_id', $account_id)->update(['status' => 'Deleted', 'deleted_at' => $deleted_at]);

        // add to email list
        if (is_main_instance()) {
            $email_list_id = \DB::table('crm_email_lists')->where('name', 'Deleted Accounts')->pluck('id')->first();
            if ($email_list_id) {
                $data = [
                    'email_list_id' => $email_list_id,
                    'account_id' => $account->id,
                    'name' => $account->company,
                    'email' => $account->email,
                ];
                \DB::table('crm_email_list_records')->insert($data);
            }
        }

        \DB::table('isp_voice_pbx_domains')->where('account_id', $account_id)->delete();
    }

    // DELETE PAYFAST SUBSCRIPTION
    if ('reseller_user' != $type) {
        $sub = new ErpSubs();
        $sub->deletePayfastSubscription($account_id);
    }

    \DB::table('erp_users')->where('account_id', $account_id)->update(['active' => 0]);

    \DB::table('acc_bank_references')->where('account_id', $account_id)->update(['is_deleted' => 1]);

    \DB::table('crm_accounts')->where('id', $account_id)->update(['is_deleted' => 1, 'deleted_at' => $deleted_at, 'status' => 'Deleted', 'cancel_date' => null, 'account_status' => 'Deleted']);

    $draft_doctypes = ['Quotation', 'Credit Note Draft'];
    $doc_ids = \DB::table('crm_documents')->where('account_id', $account_id)->whereIn('doctype', $draft_doctypes)->pluck('id')->toArray();
    $doc_module_ids = \DB::table('erp_cruds')->where('db_table', 'crm_documents')->pluck('id')->toArray();
    foreach ($doc_ids as $id) {
        \DB::table('crm_document_lines')->where('document_id', $id)->delete();
        \DB::table('crm_documents')->where('id', $id)->delete();

        \DB::table('crm_approvals')->where('row_id', $id)->whereIn('module_id', $doc_module_ids)->delete();
    }

    $doc_ids = \DB::table('crm_documents')->where('account_id', $account_id)->where('doctype', 'Order')->pluck('id')->toArray();
    foreach ($doc_ids as $id) {
        \DB::table('crm_document_lines')->where('document_id', $id)->delete();
        \DB::table('crm_documents')->where('id', $id)->delete();
        \DB::table('crm_approvals')->where('row_id', $id)->whereIn('module_id', $doc_module_ids)->delete();
    }

    if ('reseller' == $type || 'customer' == $type) {
        $debtor_status = \DB::table('crm_debtor_status')->where('id', $account->debtor_status_id)->get()->first();
        if ($debtor_status->write_off) {
            write_off_account($account_id);
        }
    }
}

//// write_off_account
function write_off_account($account_id)
{
    (new DBEvent())->setAccountAging($account_id, 1, 0);
    $account = dbgetaccount($account_id);
    $account->balance = currency($account->balance);
    $pending_balance = get_pending_debtor_balance($account_id);

    if (0 != $pending_balance && 'Deleted' == $account->status && 'lead' != $account->type && 1 == $account->partner_id) {
        $trx_data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'General Journal',
            'name' => 'Bad Debt Written Off',
            'approved' => 1,
        ];
        $amount = $account->balance;

        $transaction_id = \DB::table('acc_general_journal_transactions')->insertGetId($trx_data);

        $data = [
            'transaction_id' => $transaction_id,
            'account_id' => $account_id,
            'debit_amount' => $amount,
            'reference' => 'Bad Debt Written Off',
            'ledger_account_id' => 51,
        ];

        $is_credit = false;
        if ($amount < 0) {
            $is_credit = true;
            $data['credit_amount'] = abs($amount);
            $data['debit_amount'] = 0;
        }

        \DB::table('acc_general_journals')->insert($data);
        $data['ledger_account_id'] = 5;

        if ($is_credit) {
            $data['debit_amount'] = abs($amount);
            $data['credit_amount'] = 0;
        } else {
            $data['credit_amount'] = $amount;
            $data['debit_amount'] = 0;
        }

        \DB::table('acc_general_journals')->insert($data);
        //if(!$account->cancel_approved){
        //\DB::table('acc_general_journal_transactions')->where('id',$transaction_id)->update(['approved'=>0]);
        //}
        if ('reseller' == $account->type) {
            $reseller_users = \DB::table('crm_accounts')->where('partner_id', $account->id)->get();
            foreach ($reseller_users as $sa) {
                \DB::table('crm_accounts')->where('id', $sa->id)->update(['balance' => 0]);
            }
        }

        $credit_total = \DB::table('acc_general_journals')->where('transaction_id', $transaction_id)->sum('credit_amount');
        $debit_total = \DB::table('acc_general_journals')->where('transaction_id', $transaction_id)->sum('debit_amount');
        \DB::table('acc_general_journal_transactions')
            ->where('id', $transaction_id)
            ->update(['credit_total' => $credit_total, 'debit_total' => $debit_total]);

        (new DBEvent())->setDebtorBalance($account->id, 1);

        $journals = \DB::table('acc_general_journal_transactions')->where('approved', 0)->get();
        foreach ($journals as $journal) {
            $exists = \DB::table('crm_approvals')->where('module_id', 730)->where('row_id', $journal->id)->count();
            $account_id = \DB::table('acc_general_journals')->where('transaction_id', $journal->id)->pluck('account_id')->first();

            $company = dbgetcell('crm_accounts', 'id', $account_id, 'company');
            if (!$exists) {
                $data = [
                    'module_id' => 730,
                    'row_id' => $journal->id,
                    'title' => 'General Journal #'.$journal->id.' - '.$company.' - R '.$journal->debit_total,
                    'processed' => 0,
                    'requested_by' => get_user_id_default(),
                ];
                (new \DBEvent())->setTable('crm_approvals')->save($data);
            }
        }
    }
}

function button_debtors_restore_last_write_off($request)
{
    $debtor = \DB::table('crm_written_off')->where('id', $request->id)->get()->first();
    if (empty($debtor) || empty($debtor->account_id)) {
        return json_alert('Account id not set', 'warning');
    }
    $account_id = $debtor->account_id;
    $debt_total = \DB::table('acc_general_journals')->where('account_id', $account_id)->where('ledger_account_id', 5)->where('reference', 'Bad Debt Written Off')->orderBy('id', 'desc')->pluck('credit_amount')->first();
    if (!$debt_total) {
        return json_alert('Account does not have a write off to restore', 'warning');
    }
    restore_account_debt_last_write_off($debtor->account_id);

    return json_alert('Done');
}

function restore_account_debt_last_write_off($account_id)
{
    $debt_total = \DB::table('acc_general_journals')->where('account_id', $account_id)->where('ledger_account_id', 5)->where('reference', 'Bad Debt Written Off')->orderBy('id', 'desc')->pluck('credit_amount')->first();

    if (0 != $debt_total) {
        $trx_data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'General Journal',
            'name' => 'Account Restored',
        ];

        $transaction_id = \DB::table('acc_general_journal_transactions')->insertGetId($trx_data);

        $amount = $debt_total;
        $data = [
            'transaction_id' => $transaction_id,
            'account_id' => $account_id,
            'debit_amount' => $amount,
            'reference' => 'Account Restored',
            'ledger_account_id' => 5,
        ];

        $db = new DBEvent();
        $result = $db->setTable('acc_general_journals')->save($data);

        $data['credit_amount'] = $data['debit_amount'];
        $data['debit_amount'] = 0;
        $data['ledger_account_id'] = 51;
        $result = $db->setTable('acc_general_journals')->save($data);

        (new DBEvent())->setAccountAging($account_id, 1, false);
    }
}
function restore_account_debt($account_id)
{
    $debt_total = \DB::table('acc_general_journals')->where('account_id', $account_id)->where('ledger_account_id', 5)->where('reference', 'Bad Debt Written Off')->sum('credit_amount');

    if (0 != $debt_total) {
        $trx_data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'General Journal',
            'name' => 'Account Restored',
        ];

        $transaction_id = \DB::table('acc_general_journal_transactions')->insertGetId($trx_data);

        $amount = $debt_total;
        $data = [
            'transaction_id' => $transaction_id,
            'account_id' => $account_id,
            'debit_amount' => $amount,
            'reference' => 'Account Restored',
            'ledger_account_id' => 5,
        ];

        $db = new DBEvent();
        $result = $db->setTable('acc_general_journals')->save($data);

        $data['credit_amount'] = $data['debit_amount'];
        $data['debit_amount'] = 0;
        $data['ledger_account_id'] = 51;
        $result = $db->setTable('acc_general_journals')->save($data);

        (new DBEvent())->setAccountAging($account_id, 1, false);
    }
}

function create_credit_note($account_id, $amount, $reference, $reversal_id = null)
{
    $db = new DBEvent();

    $account = dbgetaccount($account_id);

    if ('Deleted' != $account->status && 'lead' != $account->type && 1 == $account->partner_id) {
        $doctype = 'Credit Note';

        $data = [
            'docdate' => date('Y-m-d'),
            'doctype' => $doctype,
            'account_id' => $account_id,
            'total' => $amount,
            'reference' => $reference,
            'qty' => [1],
            'price' => [$amount],
            'full_price' => [$amount],
            'product_id' => [147],
            'subscription_created' => 1,
            'reversal_id' => $reversal_id,
        ];
        $result = $db->setTable('crm_documents')->setProperties(['validate_document' => 1])->save($data);

        $id = \DB::table('crm_documents')->where('reversal_id', $reversal_id)->pluck('id')->first();
        \DB::table('crm_documents')->where('id', $reversal_id)->update(['reversal_id' => $id]);
    }
}

function create_credit_note_draft_from_invoice($invoice_id, $post_document = false)
{
    $invoice = \DB::table('crm_documents')->where('id', $invoice_id)->get()->first();
    if (empty($invoice->reversal_id)) {
        $lines = \DB::table('crm_document_lines')->where('document_id', $invoice_id)->get();
        $data = (array) $invoice;
        $data['docdate'] = (date('Y-m-d') > $invoice->docdate) ? date('Y-m-d') : $invoice->docdate;
        $data['doctype'] = 'Credit Note Draft';
        $data['credit_note_reason'] = 'System generated';
        $data['reversal_id'] = $invoice_id;
        unset($data['id']);
        $credit_note_id = \DB::table('crm_documents')->insertGetId($data);
        foreach ($lines as $line) {
            $line_data = (array) $line;
            $line_data['document_id'] = $credit_note_id;
            $line_data['original_line_id'] = $line->id;
            unset($line_data['id']);
            \DB::table('crm_document_lines')->insert($line_data);
        }

        \DB::table('crm_documents')->where('id', $invoice_id)->update(['reversal_id' => $credit_note_id]);

        if ($post_document) {
            $db = new DBEvent();
            $db->setTable('crm_documents');
            $db->postDocument($credit_note_id);
            $db->postDocumentCommit();
        }

        return $credit_note_id;
    }

    return false;
}

function create_credit_note_from_invoice($invoice_id, $post_document = true)
{
    $invoice = \DB::table('crm_documents')->where('id', $invoice_id)->get()->first();
    if (empty($invoice->reversal_id)) {
        $lines = \DB::table('crm_document_lines')->where('document_id', $invoice_id)->get();
        $data = (array) $invoice;
        $data['docdate'] = (date('Y-m-d') > $invoice->docdate) ? date('Y-m-d') : $invoice->docdate;
        $data['doctype'] = 'Credit Note';
        $data['credit_note_reason'] = 'System generated';
        $data['reversal_id'] = $invoice_id;
        unset($data['id']);
        $credit_note_id = \DB::table('crm_documents')->insertGetId($data);
        foreach ($lines as $line) {
            $line_data = (array) $line;
            $line_data['document_id'] = $credit_note_id;
            $line_data['original_line_id'] = $line->id;
            unset($line_data['id']);
            \DB::table('crm_document_lines')->insert($line_data);
        }

        \DB::table('crm_documents')->where('id', $invoice_id)->update(['reversal_id' => $credit_note_id]);

        if ($post_document) {
            $db = new DBEvent();
            $db->setTable('crm_documents');
            $db->postDocument($credit_note_id);
            $db->postDocumentCommit();
        }

        return $credit_note_id;
    }

    return false;
}

//// create_customer
function create_customer($data, $type = 'customer', $create_settings = true, $user_arr = false)
{
    if (is_array($data)) {
        $data = (object) $data;
    }
    if (!$data->partner_id) {
        $data->partner_id = 1;
    }
    $customer = [];
    $customer['partner_id'] = $data->partner_id;

    $partner_pricelist_id = \DB::table('crm_pricelists')->where('partner_id', $data->partner_id)->where('default_pricelist', 1)->pluck('id')->first();

    $customer['pricelist_id'] = $partner_pricelist_id;
    $customer['company'] = $data->company;
    $customer['contact'] = $data->contact;
    if (!empty($data->newsletter)) {
        $customer['newsletter'] = 1;
    } else {
        $customer['newsletter'] = 0;
    }
    if (!empty($data->email)) {
        $customer['email'] = $data->email;
    }

    if (!empty($data->phone)) {
        $customer['phone'] = $data->phone;
    }
    if (!empty($data->marketing_channel_id)) {
        $customer['marketing_channel_id'] = $data->marketing_channel_id;
    }
    $customer['notification_type'] = $data->notification_type;
    if (empty($data->notification_type)) {
        $customer['notification_type'] = 'email';
    }

    if ('reseller' == $type) {
        $customer['type'] = 'reseller';
    } elseif (1 == $customer['partner_id']) {
        if ($type == 'customer') {
            $customer['type'] = 'customer';
        } else {
            $customer['type'] = 'lead';
        }
    } else {
        $customer['type'] = 'reseller_user';
    }
    $customer['status'] = 'Enabled';
    $customer['created_by'] = get_user_id_default();

    $customer['address'] = isset($data->address) ? $data->address : '';
    $customer['created_at'] = date('Y-m-d H:i:s');

    $account_id = dbinsert('crm_accounts', (array) $customer);
    if ($create_settings) {
        create_account_settings($account_id, $user_arr);
    }

    return $account_id;
}

//// convert_to_partner
function convert_to_partner($id, $json = true)
{
    $erp = new DBEvent();
    $customer = \DB::table('crm_accounts')->where('id', $id)->get()->first();

    // create reseller domain
    if (1 == $customer->partner_id && ('lead' == $customer->type || 'customer' == $customer->type)) {
        $partner_id = create_customer($customer, 'reseller', true);
        $new_user_id = \DB::table('erp_users')->where('account_id', $partner_id)->pluck('id')->first();

        \DB::table('erp_users')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);
        \DB::table('erp_users')->where('account_id', $partner_id)->where('id', '!=', $new_user_id)->update(['role_id' => 21, 'account_id' => $customer->id]);
        \DB::table('erp_users')->where('account_id', $partner_id)->where('id', $new_user_id)->update(['role_id' => 11]);

        //move pbx to partner
        \DB::connection('pbx')->table('v_domains')->where('account_id', $customer->id)->update(['partner_id' => $partner_id]);

        $settings['id'] = $partner_id;
        $settings['account_id'] = $partner_id;

        $settings['logo'] = 'default_logo.png';
        $settings['afriphone_signup_code'] = mt_rand(100000, 999999);
        $settings_id = \DB::table('crm_account_partner_settings')->where('account_id', $partner_id)->update($settings);

        \DB::table('crm_accounts')->where('id', $partner_id)->update(['type' => 'reseller', 'pricelist_id' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        module_log(343, $partner_id, 'created', 'converted to reseller');
        $partner_pricelist_id = setup_new_pricelist($partner_id);

        \DB::table('crm_accounts')->where('id', $customer->id)
            ->update(['partner_id' => $partner_id, 'pricelist_id' => $partner_pricelist_id, 'company' => $customer->company.' (customer)']);

        // update documents and payments
        \DB::table('crm_documents')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);

        \DB::table('acc_payfast_subscriptions')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);
        \DB::table('erp_communication_lines')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);

        \DB::table('acc_cashbook_transactions')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);
        \DB::table('acc_general_journals')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);
        \DB::table('acc_bank_references')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);
        \DB::table('acc_debit_order_batch')->where('limit_account_id', $customer->id)->update(['limit_account_id' => $partner_id]);
        \DB::table('acc_debit_orders')->where('account_id', $customer->id)->update(['account_id' => $partner_id]);

        if ('lead' == $customer->type) {
            \DB::table('crm_accounts')->where('id', $id)->update(['type' => 'customer']);
            create_account_settings($customer->id);
        }
        // swap user accounts
        //$c_id = \DB::table('erp_users')->where('account_id', $customer->id)->pluck('id')->first();
        //$p_id = \DB::table('erp_users')->where('account_id', $partner_id)->pluck('id')->first();

        //\DB::table('erp_users')->where('id', $c_id)->update(['account_id' => $partner_id, 'role_id' => 11]);
        //\DB::table('erp_users')->where('id', $p_id)->update(['account_id' => $customer->id, 'role_id' => 21]);

        \DB::table('crm_accounts')->where('id', $customer->id)->update(['type' => 'reseller_user']);
        $erp->setDebtorBalance($partner_id);
        $erp->setDebtorBalance($id);

        //$data = [];
        //$data['internal_function'] = 'send_wholesale_pricelist';
        //$data['test_debug'] = 1;
        //erp_process_notification($partner_id, $data);

        // $data = [];
        // $data['internal_function'] = 'send_wholesale_ratesheet';
        //$data['test_debug'] = 1;
        // erp_process_notification($partner_id, $data);
        if ($json) {
            return json_alert('Account converted');
        }

        return $partner_id;
    }
}

function get_debtor_balance_upto_date($account_id, $conn, $date)
{
    $balance = 0;

    $rows = get_debtor_transactions($account_id, $conn);

    if ($rows) {
        foreach ($rows as $row) {
            if ($row->docdate < $date) {
                $balance += $row->total;
            }
            //if(is_dev()){
            //}
        }
    }

    return currency($balance);
}

function get_debtor_balance($account_id = 0, $include_write_offs = false)
{
    $balance = 0;
    if ($include_write_offs) {
        $rows = get_debtor_transactions_excluding_writeoff($account_id);
    } else {
        $rows = get_debtor_transactions($account_id);
    }
    if ($rows) {
        foreach ($rows as $row) {
            $balance += $row->total;
            //if(is_dev()){
            //}
        }
    }

    return currency($balance);
}

function get_pending_debtor_balance($account_id = 0)
{
    $balance = 0;

    $rows = get_pending_debtor_transactions($account_id);

    if ($rows) {
        foreach ($rows as $row) {
            $balance += $row->total;
            //if(is_dev()){
           // }
        }
    }

    return currency($balance);
}

//// Account Settings helper functions
function create_account_settings($account_id, $user_arr = false)
{
    $account = \DB::table('crm_accounts')->where('id', $account_id)->get()->first();

    if (!empty($account)) {
        send_email_verification_link($account_id);

        if (empty($account->type)) {
            if (1 == $account->partner_id) {
                \DB::table('crm_accounts')->where('id', $account_id)->update(['type' => 'lead']);
                $type = 'lead';
                $account->type = 'lead';
            } else {
                \DB::table('crm_accounts')->where('id', $account_id)->update(['type' => 'reseller_user']);
                $type = 'reseller_user';
                $account->type = 'reseller_user';
            }
        } else {
            $type = $account->type;
            if ('reseller' == $type) {
                $settings_exists = \DB::table('crm_account_partner_settings')->where('account_id', $account_id)->count();

                if (!$settings_exists) {
                    dbinsert('crm_account_partner_settings', ['id' => $account_id, 'account_id' => $account_id]);
                }
            }
        }

        if ($account->type != 'lead') {
            $user_exists = \DB::table('erp_users')->where('account_id', $account_id)->count();
            $username = false;
            if (!empty($account->email)) {
                $account->email = preg_replace('/\s+/', '', $account->email);
                $username_exists = \DB::table('erp_users')->where('username', $account->email)->count();
                if (!$username_exists) {
                    $username = $account->email;
                }
            }

            if (!$username && !empty($account->phone)) {
                $username_exists = \DB::table('erp_users')->where('username', $account->phone)->count();
                if (!$username_exists) {
                    $username = $account->phone;
                }
            }

            if (!empty($account->email)) {
                \DB::table('crm_accounts')->where('id', $account_id)->update(['notification_type' => 'email']);
            } else {
                \DB::table('crm_accounts')->where('id', $account_id)->update(['notification_type' => 'sms']);
            }
            $disable_customer_login = get_admin_setting('disable_customer_login');
            if (!$user_exists && !$disable_customer_login) {
                $user = new \stdClass();
                $user->account_id = $account_id;
                if ('reseller' == $account->type) {
                    $user->role_id = 11;
                }

                if ('reseller_user' == $account->type) {
                    $user->role_id = 21;
                }

                if ('customer' == $account->type || 'lead' == $account->type) {
                    $user->role_id = 21;
                }

                if (empty($username)) {
                    $username = generate_strong_password().'@example.com';
                }

                $user->username = $username;
                $user->full_name = $username;
                $user->email = $account->email;
                $user->phone = $account->phone;
                if (!empty($account->contact)) {
                    $user->full_name = $account->contact;
                }
                $pass = generate_strong_password();
                $user->password = \Hash::make($pass);
                $user->active = 1;
                $user->created_at = date('Y-m-d H:i:s');
                dbinsert('erp_users', (array) $user);

                /////// SEND LOGIN DETAILS
                try {
                    $account = dbgetaccount($account_id);
                    if ($account->notification_type == 'email') {
                        $data['username'] = $user->username;
                        $data['password'] = $pass;
                        $data['login_url'] = get_whitelabel_domain($account->partner_id);
                        $data['login_url'] = '<a href="'.$data['login_url'].'">'.$data['login_url'].'</a>';
                        $reseller = dbgetaccount($account->partner_id);
                        $data['portal_name'] = $reseller->company;

                        $data['internal_function'] = 'create_account_settings';
                        if ($account->type == 'reseller') {
                            $data['attachments'][] = export_pricelist($account->pricelist_id);
                        }
                        erp_process_notification($account->id, $data);
                    }

                    if ($account->notification_type == 'sms') {
                        queue_sms(12, $account->phone, 'Register success. '.url('/').'.User: '.$user->username.', Pass: '.$pass);
                    }
                } catch (\Throwable $ex) {
                    exception_log($ex);
                }
            }
        }
    }
}

function beforesave_pricelist_check($request)
{
    $account = \DB::table('crm_accounts')->where('id', $request->id)->get()->first();
    if ($account->type != 'reseller') {
        if (!empty($request->pricelist_id) && (session('role_level') == 'Admin' || check_access('11'))) {
            if (!empty($request->id)) {
                if ('customer' == $request->type || 'lead' == $request->type || 'reseller_user' == $request->type) {
                    $pricelist_type = 'retail';
                } else {
                    $pricelist_type = 'wholesale';
                }

                $pricelist_currency = \DB::table('crm_pricelists')->where('id', $request->pricelist_id)->pluck('currency')->first();

                $account_partner_id = dbgetcell('crm_accounts', 'id', $request->id, 'partner_id');
                $pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', $account_partner_id)->pluck('id')->toArray();
                if (!in_array($request->pricelist_id, $pricelist_ids)) {
                    return 'Invalid Pricelist';
                }
            }
        }
    }
}

function aftersave_debtors_set_accountability_match()
{
    \DB::table('crm_accounts')->update(['accountability_match' => 0]);
    \DB::table('crm_accounts')->whereRaw('debtor_status_id=accountability_current_status_id')->update(['accountability_match' => 1]);
}

function aftersave_account_settings($request)
{
    $id = (!empty($request->id)) ? $request->id : null;
    $new_record = (!empty($request->new_record)) ? 1 : 0;
    $request->request->remove('new_record');
    if ($id) {
        set_account_product_category($id);
    }
    if ($new_record && $id) {
        $type = (1 == session('account_id')) ? 'lead' : 'reseller_user';

        \DB::table('crm_accounts')->where('id', $id)->update(['type' => $type, 'partner_id' => session('account_id')]);
        if (empty($request->pricelist_id)) {
            $partner_pricelist_id = \DB::table('crm_pricelists')->where('partner_id', session('account_id'))->where('default_pricelist', 1)->pluck('id')->first();
            \DB::table('crm_accounts')->where('id', $id)->update(['pricelist_id' => $partner_pricelist_id]);
        }

        create_account_settings($id);
    }

    $user = \DB::table('erp_users')->where('account_id', $id)->count();
    if (!$user) {
        create_account_settings($id);
    }

    $request->request->add(['new_record' => $new_record]);
    $partners = \DB::table('crm_accounts')->where('type', 'reseller')->get();
    foreach ($partners as $p) {
        $reseller_users = \DB::table('crm_accounts')->where('partner_id', $p->id)->where('status', '!=', 'Deleted')->count();
        \DB::table('crm_accounts')->where('id', $p->id)->update(['reseller_users' => $reseller_users]);
    }
}

function button_accounts_documents($request)
{
    $menu_name = get_menu_url_from_table('crm_documents');
    $account = dbgetaccount($request->id);
    if (1 == $account->partner_id) {
        return Redirect::to($menu_name.'?account_id='.$request->id);
    } else {
        return Redirect::to($menu_name.'?reseller_user='.$request->id);
    }
}

function button_accounts_convert_to_partner($request)
{
    return convert_to_partner($request->id);
}

function aftersave_set_pbx_name($request)
{
    $account = dbgetaccount($request->id);
    if ($account->pabx_domain) {
        add_rollback_connection('pbx');
        \DB::connection('pbx')->table('v_domains')->where('account_id', $request->id)->update(['status' => $account->status, 'company' => $request->company, 'partner_id' => $account->partner_id]);
    }
}

///////SCHEDULE

function schedule_set_pbx_name()
{
    $pbx_domains = \DB::connection('pbx')->table('v_domains')->where('account_id', '>', 0)->get();
    foreach ($pbx_domains as $pbx_domain) {
        $account = dbgetaccount($pbx_domain->account_id);
        $partner_company = \DB::connection('default')->table('crm_accounts')->where('id', $account->partner_id)->pluck('company')->first();
        \DB::connection('pbx')->table('v_domains')
        ->where('domain_uuid', $pbx_domain->domain_uuid)
        ->update([
            'status' => $account->status,
            'company' => $account->company,
            'partner_id' => $account->partner_id,
            'partner_company' => $account->partner_company,
        ]);
    }
}

function schedule_clear_sessions()
{
    /*
    $db_conns = db_conns();
    foreach($db_conns as $c){
        \DB::connection($c)->table('erp_user_sessions')->delete();
    }
    */
    \DB::table('erp_user_sessions')->whereNull('user_id')->delete();
    \DB::table('erp_user_sessions')->where('user_id', '!=', 1)->delete();

    $timesheets = \DB::table('hr_timesheet')->whereNull('end_time')->get();
    foreach ($timesheets as $timesheet) {
        $endtime = date('Y-m-d 17:00:00', strtotime($timesheet->start_time));

        \DB::table('hr_timesheet')->where('id', $timesheet->id)->update(['end_time' => $endtime]);
    }
}

function button_delete_quotations($request)
{
    /*
    $quotes = \DB::table('crm_documents')->where('doctype', 'Quotation')->get();
    foreach ($quotes as $quote) {
        $account = \DB::table('crm_accounts')->where('id', $quote->account_id)->get()->first();
        $quote_total = \DB::connection('default')->table('crm_documents')->where('account_id', $quote->account_id)->where('doctype', 'Quotation')->sum('total');
        \DB::connection('default')->table('crm_accounts')->where('id', $quote->account_id)->update(['quote_total' => $quote_total]);

        \DB::table('crm_document_lines')->where('document_id', $quote->id)->delete();
        \DB::table('crm_documents')->where('id', $quote->id)->delete();


        $quote_total = \DB::connection('default')->table('crm_documents')->where('account_id', $quote->account_id)->where('doctype', 'Quotation')->sum('total');
        \DB::connection('default')->table('crm_accounts')->where('id', $quote->account_id)->update(['quote_total' => $quote_total]);
    }


    $check_date = date('Y-m-d', strtotime('-6 months'));


    $leads = \DB::table('crm_accounts')->where('created_at', '<', $check_date)->where('status', '!=', 'Deleted')->where('type', 'lead')->where('partner_id', 1)->where('subs_count', 0)->where('quote_total', 0)->get();

    foreach ($leads as $lead) {
        delete_account($lead->id);
    }

    return json_alert('Quotations deleted.');
    */
}

function restore_quotations()
{
    $quotes = \DB::connection('backup_erp')->table('crm_documents')->where('doctype', 'Quotation')->get();
    foreach ($quotes as $q) {
        $exists = \DB::table('crm_documents')->where('id', $q->id)->count();
        if (!$exists) {
            $data = (array) $q;
            \DB::table('crm_documents')->insert($data);
            $lines = \DB::connection('backup_erp')->table('crm_document_lines')->where('document_id', $q->id)->get();
            foreach ($lines as $l) {
                $data = (array) $l;
                \DB::table('crm_document_lines')->insert($data);
            }
        }
    }
}

function button_accounts_convert_partner_to_customer($request)
{
    $account_id = $request->id;
    $account = \DB::table('crm_accounts')->where('id', $account_id)->get()->first();
    if ($account->type != 'reseller') {
        return json_alert('Invalid account type', 'error');
    }

    $default_pricelist_id = \DB::table('crm_pricelists')->where('partner_id', 1)->where('default_pricelist', 1)->pluck('id')->first();
    $sub_accounts = \DB::table('crm_accounts')->where('partner_id', $account_id)->get();

    $sub = new ErpSubs();

    $sub->updateProductPrices();

    $sub->updateSubscriptionsTotal($account_id);

    foreach ($sub_accounts as $sub_account) {
        $data = [
            'partner_id' => 1,
            'type' => 'customer',
            'pricelist_id' => $default_pricelist_id,
        ];
        \DB::table('crm_accounts')->where('id', $sub_account->id)->update($data);

        $sub->updateSubscriptionsTotal($sub_account->id);
        (new DBEvent())->setAccountAging($sub_account->id);
    }

    (new DBEvent())->setAccountAging($account_id);
    \DB::table('crm_accounts')->where('id', $account_id)->update(['pricelist_id' => $default_pricelist_id, 'type' => 'customer', 'notes' => 'Converted Partner Account']);
    \DB::table('erp_users')->where('account_id', $account_id)->update(['role_id' => 11]);

    return json_alert('Done');
}

function set_account_product_category_all()
{
    $account_ids = \DB::table('crm_accounts')->pluck('id')->toArray();
    foreach ($account_ids as $account_id) {
        set_account_product_category($account_id);
    }
}

function set_account_product_category($account_id = false)
{
    if (!$account_id) {
        return false;
    }
    $category_id = 0;
    $type = \DB::table('crm_accounts')->where('id', $account_id)->pluck('type')->first();

    if ($type == 'reseller') {
        $customer_ids = \DB::table('crm_accounts')->where('partner_id', $account_id)->pluck('id')->toArray();
        $sub_count = \DB::table('sub_services')->whereIn('sub_services.account_id', $customer_ids)->count();
        if ($sub_count > 0) {
            $category_id = \DB::table('sub_services')
                ->join('crm_products', 'crm_products.id', '=', 'sub_services.product_id')
                ->whereIn('sub_services.account_id', $customer_ids)
                ->groupBy('crm_products.product_category_id')
                ->orderByRaw('count(*) DESC')
                ->value('crm_products.product_category_id');
        } else {
            $category_id = \DB::table('crm_document_lines')
                ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
                ->join('crm_products', 'crm_products.id', '=', 'crm_document_lines.product_id')
                ->where('crm_documents.account_id', $account_id)
                ->groupBy('crm_products.product_category_id')
                ->orderByRaw('count(*) DESC')
                ->value('crm_products.product_category_id');
        }
    } elseif ($type == 'customer') {
        $sub_count = \DB::table('sub_services')->where('sub_services.account_id', $account_id)->count();
        if ($sub_count > 0) {
            $category_id = \DB::table('sub_services')
                ->join('crm_products', 'crm_products.id', '=', 'sub_services.product_id')
                ->where('sub_services.account_id', $account_id)
                ->groupBy('crm_products.product_category_id')
                ->orderByRaw('count(*) DESC')
                ->value('crm_products.product_category_id');
        } else {
            $category_id = \DB::table('crm_document_lines')
                ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
                ->join('crm_products', 'crm_products.id', '=', 'crm_document_lines.product_id')
                ->where('crm_documents.account_id', $account_id)
                ->groupBy('crm_products.product_category_id')
                ->orderByRaw('count(*) DESC')
                ->value('crm_products.product_category_id');
        }
    } elseif ($type == 'reseller_user') {
        $sub_count = \DB::table('sub_services')->where('sub_services.account_id', $account_id)->count();
        if ($sub_count > 0) {
            $category_id = \DB::table('sub_services')
                ->join('crm_products', 'crm_products.id', '=', 'sub_services.product_id')
                ->where('sub_services.account_id', $account_id)
                ->groupBy('crm_products.product_category_id')
                ->orderByRaw('count(*) DESC')
                ->value('crm_products.product_category_id');
        } else {
            $category_id = \DB::table('crm_document_lines')
                ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
                ->join('crm_products', 'crm_products.id', '=', 'crm_document_lines.product_id')
                ->where('crm_documents.reseller_user', $account_id)
                ->groupBy('crm_products.product_category_id')
                ->orderByRaw('count(*) DESC')
                ->value('crm_products.product_category_id');
        }
    } else {
        $category_id = \DB::table('crm_document_lines')
            ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
            ->join('crm_products', 'crm_products.id', '=', 'crm_document_lines.product_id')
            ->where('crm_documents.reseller_user', $account_id)
            ->groupBy('crm_products.product_category_id')
            ->orderByRaw('count(*) DESC')
            ->value('crm_products.product_category_id');
    }
    if (empty($category_id)) {
        $category_id = 0;
    }

    \DB::table('crm_accounts')->where('id', $account_id)->update(['product_category_id' => $category_id]);
}

function schedule_set_accounts_services_statuses()
{
    // Status Priority: Disabled/Dormant/PBX Low Airtime

    // DISABLED
    \DB::table('crm_accounts')->where('status', 'Deleted')->update(['service_status' => 'Deleted']);
    \DB::table('crm_accounts')->where('status', 'Disabled')->update(['service_status' => 'Disabled']);

    $account_ids = \DB::table('crm_accounts')->where('is_deleted', 0)->pluck('id')->toArray();
    foreach ($account_ids as $account_id) {
        set_accounts_services_statuses($account_id);
    }
}

function set_accounts_services_statuses($account_id)
{
    $service_status = 'Normal';
    $account = dbgetaccount($account_id);
    if ($account->status == 'Deleted') {
        $service_status = 'Deleted';
    }
    if ($account->status == 'Disabled') {
        $service_status = 'Disabled';
    }
    if ($account->subs_count == 0) {
        $service_status = 'Product Only';
    }
    // DORMANT
    $annual_subscriptions = \DB::table('sub_services')->where('account_id', $account_id)->where('bill_frequency', '>=', 12)->where('status', '!=', 'Deleted')->count();
    if (!$annual_subscriptions) {
        if ($account->invoice_days > 80) {
            $service_status = 'Dormant';
        }
        if ($account->invoice_days > 365) {
            $service_status = 'Dormant';
        }
    } else {
        if ($account->invoice_days > 365) {
            $service_status = 'Dormant';
        } else {
            $service_status = 'Annual';
        }
    }
    if ($account_id == 302025) {
        $service_status = 'Annual';
    }

    \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['service_status' => $service_status]);
    \DB::connection('default')->table('crm_accounts')->where('partner_id', $account_id)->update(['service_status' => $service_status]);

    if (!empty(session('app_ids')) && in_array(12, session('app_ids')) && !empty($account->pabx_domain)) {
        $pbx_service_status = $service_status;
        // PBX No Balance
        $no_balance = \DB::connection('pbx')->table('v_domains')->where('account_id', $account_id)->where('unlimited_channels', 0)->where('balance', '<=', 0)->where('partner_id', 1)->count();

        if ($no_balance) {
            $pbx_service_status = 'No Balance';
        }

        // PBX Low Airtime
        $low_airtime = \DB::connection('pbx')->table('v_domains')->where('account_id', $account_id)->where('unlimited_channels', 0)->where('balance', '<=', 10)->where('partner_id', 1)->count();
        if ($low_airtime) {
            $pbx_service_status = 'Low Balance';
        }

        $prepaid_domain = \DB::connection('pbx')->table('v_domains')->where('account_id', $account_id)->where('unlimited_channels', 0)->count();
        if ($prepaid_domain > 0) {
            // BLOCKED_NO_AIRTIME
            $blocked_airtime = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('hangup_cause', 'BLOCKED_NO_AIRTIME')
            ->where('domain_name', $account->pabx_domain)
            ->where('hangup_date', '>=', date('Y-m-d 00:00', strtotime('-1 day')))
            ->count();

            if ($blocked_airtime) {
                $pbx_service_status = 'BLOCKED_NO_AIRTIME';
            }
        }

        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->where('service_status', 'Normal')->update(['service_status' => $pbx_service_status]);
    }
}

function schedule_update_primary_users()
{
    $leads = \DB::table('crm_accounts')->where('type', 'lead')->pluck('id')->toArray();
    \DB::table('erp_users')->whereIn('account_id', $leads)->delete();
    $accounts = \DB::table('crm_accounts')->where('type', '!=', 'lead')->where('id', '>', 1)->where('status', '!=', 'Deleted')->get();
    foreach ($accounts as $account) {
        $data = [
            'type' => 'Manager',
            'full_name' => $account->contact,
            'phone' => $account->phone,
            'email' => $account->email,
            'created_at' => $account->created_at,
            'created_by' => $account->created_by,
        ];
        $where_data = [
            'account_id' => $account->id,
            'type' => 'Manager',
        ];
        \DB::table('erp_users')->where($where_data)->update($data);
    }
}

function get_salesman_user_ids()
{
    $salesman_roles = [62];

    $salesmanIds = \DB::table('erp_users')->whereIn('role_id', $salesman_roles)->where('is_deleted', 0)->pluck('id')->toArray();
    //if(session('instance')->id == 1){
    // kola
    //    $salesmanIds[] = 4194;
    //}
    return $salesmanIds;
}

function aftersave_account_set_salesman($request)
{
    $salesmanIds = get_salesman_user_ids();

    $account = \DB::table('crm_accounts')->where('id', $request->id)->get()->first();

    if (empty($account->salesman_id) && in_array(session('user_id'), $salesmanIds)) {
        \DB::table('crm_accounts')
        ->where('id', $request->id)
        ->update(['salesman_id' => session('user_id')]);
    }
}

function schedule_assign_customers_to_salesman()
{
    // Retrieve all salesman IDs
    $salesmanIds = get_salesman_user_ids();

    // $system_id = get_system_user_id();
    //\DB::table('crm_accounts')->where('status', 'Deleted')->update(['salesman_id' => $system_id]);

    \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->whereNotIn('salesman_id', $salesmanIds)->update(['salesman_id' => 0]);

    $totalSalesmen = count($salesmanIds);
    if (!$totalSalesmen) {
        return false;
    }

    // Retrieve all account IDs
    $accounts = DB::table('crm_accounts')
        ->where('status', '!=', 'Deleted')
        ->where('salesman_id', '!=', 4194)
        ->whereNotIn('salesman_id', $salesmanIds)
        ->orderBy('created_at')
        ->get();

    // remove last allocated salesman id if only 1 new lead to prevent allocating to same saleman repeatedly
    if ($accounts->count() == 1) {
        $last_salesman_id = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('salesman_id', '!=', 0)->orderBy('id', 'desc')->pluck('salesman_id')->first();
        $salesmanIds = array_diff($salesmanIds, [$last_salesman_id]);
    }

    $totalSalesmen = count($salesmanIds);

    if ($totalSalesmen > 0 && $accounts->count() > 0) {
        // Group accounts by month
        $accountsByMonth = $accounts->groupBy(function ($account) {
            return \Carbon\Carbon::parse($account->created_at)->format('Y-m'); // Convert to Carbon date object Group by year and month
        });

        // Distribute accounts to salesmen
        $currentSalesmanIndex = 0;
        $currentMonth = null;

        foreach ($accountsByMonth as $month => $accountsInMonth) {
            $salesmanId = $salesmanIds[$currentSalesmanIndex];

            // Update salesmen for accounts in this month
            $accountIds = $accountsInMonth->pluck('id')->toArray();
            DB::table('crm_accounts')
                ->whereIn('id', $accountIds)
                ->update(['salesman_id' => $salesmanId]);

            // Move to the next salesman
            $currentSalesmanIndex = ($currentSalesmanIndex + 1) % $totalSalesmen;

            // Update the current month
            $currentMonth = $month;
        }

        // For any remaining accounts, distribute them evenly among salesmen
        if ($currentMonth) {
            $remainingAccounts = $accounts->where('created_at', '>', $currentMonth)->pluck('id')->toArray();

            foreach ($remainingAccounts as $accountId) {
                $salesmanId = $salesmanIds[$currentSalesmanIndex];
                DB::table('crm_accounts')
                    ->where('id', $accountId)
                    ->update(['salesman_id' => $salesmanId]);
                $currentSalesmanIndex = ($currentSalesmanIndex + 1) % $totalSalesmen;
            }
        }
    }
}

function schedule_assign_customers_to_salesman_old()
{
    // function to split customer to multiple users on the sales role
    //return false;
    $system_id = get_system_user_id();
    \DB::table('crm_accounts')->where('status', 'Deleted')->update(['salesman_id' => $system_id]);
    \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->update(['salesman_id' => 0]);

    // Retrieve all salesman IDs
    $salesmanIds = get_salesman_user_ids();

    $totalSalesmen = count($salesmanIds);
    if (!$totalSalesmen) {
        return false;
    }
    // Retrieve all account IDs
    $accountIds = DB::table('crm_accounts')->where('status', '!=', 'Deleted')->whereNotIn('salesman_id', $salesmanIds)->orderBy('form_name')->pluck('id')->toArray();
    if (count($accountIds) > 0) {
        $totalAccounts = count($accountIds);
        $accountsPerSalesman = (int) ($totalAccounts / $totalSalesmen);
        $remainder = $totalAccounts % $totalSalesmen;
        $accountsBySalesman = [];

        $accountIndex = 0;
        foreach ($salesmanIds as $salesmanId) {
            $numAccounts = $accountsPerSalesman;

            if ($remainder > 0) {
                ++$numAccounts;
                --$remainder;
            }

            $accountSubset = array_slice($accountIds, $accountIndex, $numAccounts);
            $accountsBySalesman[$salesmanId] = $accountSubset;

            $accountIndex += $numAccounts;
        }

        foreach ($accountsBySalesman as $salesmanId => $accountSubset) {
            DB::table('crm_accounts')
                ->whereIn('id', $accountSubset)
                ->update(['salesman_id' => $salesmanId]);
        }
    }
}

function assign_customer_to_partner($customer_id, $partner_id)
{
    if (!$customer_id || !$partner_id) {
        return 'Invalid ID';
    }
    $current_partner_id = \DB::table('crm_accounts')->where('id', $customer_id)->pluck('partner_id')->first();
    if ($current_partner_id != 1) {
        return 'Already assigned to reseller account';
    }
    $pricelist_id = \DB::table('crm_pricelists')->where('partner_id', $partner_id)->pluck('id')->first();
    \DB::table('crm_accounts')->where('id', $customer_id)->update(['partner_id' => $partner_id, 'type' => 'reseller_user', 'pricelist_id' => $pricelist_id]);

    \DB::table('acc_general_journals')->where('account_id', $customer_id)->update(['account_id' => $partner_id]);
    \DB::table('acc_cashbook_transactions')->where('account_id', $customer_id)->update(['account_id' => $partner_id]);
    \DB::table('crm_documents')->where('account_id', $customer_id)->update(['account_id' => $partner_id, 'reseller_user' => $customer_id]);
    \DB::table('acc_bank_references')->where('account_id', $customer_id)->update(['is_deleted' => 1]);
    \DB::table('acc_debit_orders')->where('account_id', $customer_id)->delete();
    \DB::table('erp_communication_lines')->where('account_id', $customer_id)->update(['account_id' => $partner_id]);
    \DB::table('acc_ledgers')->where('account_id', $customer_id)->update(['account_id' => $partner_id]);

    (new DBEvent())->setAccountAging($partner_id);

    return 'Done';
}