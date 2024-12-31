<?php

function schedule_set_accounts_aging()
{
    \DB::connection('default')->table('crm_accounts')->where('status', '')->update(['status' => 'Enabled']);
    $account_ids = \DB::connection('default')->table('crm_accounts')
        ->where('status', '!=', 'Deleted')
        ->where('type', '!=', 'lead')
        ->where('partner_id', 1)
        ->pluck('id')->toArray();
    $db = new DBEvent;
    $db->setDebtorBalance($account_ids);

    \DB::table('crm_accounts')->where('debtor_status_id', 1)->update(['accountability_current_status_id' => 1]);
    schedule_populate_written_off();
}

function process_aging_actions($account_id, $process_status = true, $process_events = true)
{

    $balance = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('balance')->first();
    $customer_currency = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('currency')->first();
    $currency_check = ['both', strtolower($customer_currency)];

    $account_status = \DB::table('crm_accounts')->where('id', $account_id)->pluck('status')->first();
    if ($account_status == 'Deleted') {
        set_deleted_account_aging($account_id);
    }

    $payment_type = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('payment_type')->first();
    if ($payment_type == 'Internal') {
        $payment_terms = -1;
    }
    if ($payment_type == 'Prepaid') {
        $payment_terms = 0;
    }
    if ($payment_type == 'Postpaid30Days') {
        $payment_terms = 30;
    }

    if ($process_status && $payment_type == 'Internal') {
        \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => 1]);
        switch_account($account_id, 'Enabled');

        return false;
    }

    $aging_balance = \DB::table('crm_accounts')->where('id', $account_id)->pluck('aging')->first();

    if ($payment_terms > 0) {
        $aging_balance = $aging_balance - $payment_terms;
    }
    if (empty($aging_balance)) {
        $aging_balance = 0;
    }

    if ($process_status && $aging_balance <= 0) {
        \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => 1]);
        switch_account($account_id, 'Enabled');

        return false;
    }

    if ($account_status == 'Deleted') {
        $balance = get_debtor_balance($account_id, true);
        $process_status = false;
        $process_events = false;
    }

    if ($balance > 0) {
        $debtor_status = \DB::table('crm_debtor_status')
            ->where('name', '!=', 'Debit Order')
            ->where('is_deleted', 0)
            ->where('aging', '>=', 0)
            ->whereIn('customer_currency', $currency_check)
            ->where('aging', '<=', $aging_balance)
            ->where('balance', '<', $balance)
            ->orderby('aging', 'desc')->orderby('balance', 'desc')->get()->first();
    } else {
        $debtor_status = \DB::table('crm_debtor_status')
            ->where('name', '!=', 'Debit Order')
            ->where('is_deleted', 0)
            ->where('aging', '>=', 0)
            ->whereIn('customer_currency', $currency_check)
            ->where('aging', '<=', $aging_balance)
            ->orderby('aging', 'desc')->get()->first();
    }

    if (account_has_debtor_commitment($account_id) && isset($debtor_status) && isset($debtor_status->commitment_date_allowed) && ! $debtor_status->commitment_date_allowed) {
        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => 0]);
        \DB::table('crm_commitment_dates')
            ->where('expired', 0)
            ->where('account_id', $account_id)
            ->update(['expired' => 1]);
    }

    $commitment_debtor_status_id = \DB::connection('default')->table('crm_debtor_status')->where('is_deleted', 0)
        ->where('name', 'Commitment')
        ->where('aging', '<', 0)
        ->pluck('id')->first();

    $debtor_status_id = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('debtor_status_id')->first();
    $account_debtor_status = \DB::connection('default')->table('crm_debtor_status')->where('id', $debtor_status_id)->get()->first();

    if ($debtor_status_id == $commitment_debtor_status_id && ! account_has_debtor_commitment($account_id)) {
        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->where('debtor_status_id', $commitment_debtor_status_id)->update(['debtor_status_id' => 1]);
    }

    if (account_has_debtor_commitment($account_id)) {
        \DB::connection('default')->table('crm_approvals')->where('row_id', $account_id)->where('module_id', 343)->where('title', 'like', '%Delete%')->update(['is_deleted' => 1]);

        $commitment_debtor_status_id = \DB::connection('default')->table('crm_debtor_status')->where('is_deleted', 0)
            ->where('name', 'Commitment')
            ->where('aging', '<', 0)
            ->pluck('id')->first();

        if (! $commitment_debtor_status_id) {
            $commitment_debtor_status_id = $debtor_status->id;
        }

        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => $commitment_debtor_status_id, 'commitment' => 1]);
        $account_status = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('status')->first();
        if ($account_status == 'Disabled') {
            switch_account($account_id, 'Enabled');
        }
    } else {
        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['commitment' => 0]);

        $debtor_status_id = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('debtor_status_id')->first();
        $account_debtor_status = \DB::connection('default')->table('crm_debtor_status')->where('id', $debtor_status_id)->get()->first();

        $account_balance = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('balance')->first();
        $account_status = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('status')->first();

        if ($debtor_status_id <= 1 ||
                (($account_debtor_status->aging != -1 || ($account_debtor_status->aging == -1 && $account_balance <= 0))
                &&
                ($debtor_status->id != $debtor_status_id || $account_status != $debtor_status->account_status))
        ) {

            $commitment_allowed = false;
            if (account_has_debtor_commitment($account_id) && $debtor_status->id == 1) {
                $commitment_allowed = true;
            } elseif (! account_has_debtor_commitment($account_id)) {
                $commitment_allowed = true;
            }

            if ($commitment_allowed) {
                if ($process_events) {
                    if ($debtor_status->action == 'function') {
                        $function_name = $debtor_status->function_name;
                        $function_name($account);
                    }
                }

                if ($process_status) {
                    if ($debtor_status->account_status == 'Enabled' || $debtor_status->account_status == 'Disabled') {

                        switch_account($account_id, $debtor_status->account_status);
                    }
                    if ($debtor_status->account_status == 'Cancelled') {

                        cancel_account($account_id);

                        \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_process_cancellation' => 1]);
                    }
                    if ($debtor_status->account_status == 'Deleted') {

                        delete_account($account_id);
                        \DB::table('crm_accounts')->where('id', $account_id)->update(['debtor_process_cancellation' => 1]);
                    }
                }

                set_account_debtor_status($account_id, $debtor_status->id);

                if ($process_status && $debtor_status->id == 4) {
                    //dont delete accounts, disabling and enabling is much easier than delete all pbx systems and restore
                }

                if ($process_events) {
                    if ($debtor_status->id != $debtor_status_id && $debtor_status->action == 'email') {
                        $data['aging'] = $aging;
                        $data['notification_id'] = $debtor_status->email_id;
                        erp_process_notification($account_id, $data);
                    }
                }
            }
        }
    }
}

function aftersave_debtors_update_accounts($request)
{

    \DB::connection('default')->table('crm_accounts')->where('status', '')->update(['status' => 'Enabled']);
    $account_ids = \DB::connection('default')->table('crm_accounts')
        ->where('status', '!=', 'Deleted')
        ->where('type', '!=', 'lead')
        ->where('partner_id', 1)
        ->pluck('id')->toArray();
    $db = new DBEvent;
    $db->setDebtorBalance($account_ids);
}

function aftersave_set_debtor_emails($request)
{
    $email_ids = \DB::connection('default')->table('crm_debtor_status')->pluck('email_id')->unique()->filter()->toArray();
    \DB::connection('default')->table('crm_email_manager')->whereIn('id', $email_ids)->update(['debtor_email' => 1, 'delivery_confirmation' => 1]);
}

function button_debtor_statuses_create_email($request)
{

    $debtor_status = \DB::connection('default')->table('crm_debtor_status')->where('id', $request->id)->get()->first();
    if (! empty($debtor_status->email_id)) {
        return json_alert('Email already created', 'warning');
    }
    $data = [
        'name' => $debtor_status->name,
        'module_id' => 619,
        'debtor_email' => 1,
        'delivery_confirmation' => 1,
        'message' => '<p><strong>Hi {{$customer->contact}},</strong></p><p><br></p><p>Your account status has changed to '.$debtor_status->name.' </p><p><br></p><p><strong>Regards,</strong></p><p><strong>{{$partner_company}}</strong></p>',
        'from_email' => 'Accounting',
        'to_email' => 'Account - Manager',
        'type' => 'System',
    ];
    $email_id = \DB::connection('default')->table('crm_email_manager')->insertGetId($data);
    \DB::connection('default')->table('crm_debtor_status')->where('id', $request->id)->update(['email_id' => $email_id]);
    $url = get_menu_url_from_module_id(556).'/edit/'.$email_id;

    return redirect()->to($url);

}
function button_debtor_statuses_edit_email($request)
{
    $debtor_status = \DB::connection('default')->table('crm_debtor_status')->where('id', $request->id)->get()->first();
    if (empty($debtor_status->email_id)) {
        return json_alert('Email not created', 'warning');
    }
    $email_id = $debtor_status->email_id;

    $url = get_menu_url_from_module_id(556).'/edit/'.$email_id;

    return redirect()->to($url);
}

function schedule_debtors_set_written_off()
{
    //written_off
    $account_ids = \DB::table('crm_accounts')->where('type', '!=', 'lead')->where('partner_id', 1)->pluck('id')->toArray();
    foreach ($account_ids as $account_id) {
        $restored_total = \DB::table('acc_general_journals')->where('account_id', $account_id)->where('ledger_account_id', 5)->where('reference', 'Account Restored')->sum('debit_amount');
        $debt_total = \DB::table('acc_general_journals')->where('account_id', $account_id)->where('ledger_account_id', 5)->where('reference', 'Bad Debt Written Off')->sum('credit_amount');

        if ($debt_total > 0 && $debt_total > $restored_total) {
            $written_off = 1;
        } else {
            $written_off = 0;
        }

        \DB::table('crm_accounts')->where('id', $account_id)->update(['written_off' => $written_off]);
        \DB::table('crm_accounts')->where('partner_id', $account_id)->update(['written_off' => $written_off]);
    }
}

function beforesave_aging_settings_required_fields($request)
{
    if (empty($request->action)) {
        $request->request->add(['email_id' => 0]);
        $request->request->add(['function_name' => '']);
    }
    if ($request->action == 'function') {
        $request->request->add(['email_id' => 0]);
    }

    if ($request->action == 'email') {
        $request->request->add(['function_name' => '']);
    }

    if (! empty($request->function_name) && ! function_exists($request->function_name)) {
        return 'Function does not exists.';
    }
}

function set_account_debtor_status($account_id, $debtor_status_id)
{

    \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['debtor_status_id' => $debtor_status_id]);

    return true;
}

function set_aging_data($account_id)
{
    $data = build_aging($account_id, 'customer');

    if (! empty($data) && is_array($data)) {
        $data['aging'] = 0;
        if ($data['balance'] > 50) {
            $doctypes = ['Tax Invoice'];
            $aging_date = \DB::connection('default')->table('crm_documents')
                ->whereIn('doctype', $doctypes)
                ->where('payment_status', 'Awaiting Payment')
                ->where('account_id', $account_id)
                ->where('docdate', '<=', date('Y-m-d'))
                ->orderby('docdate')->pluck('docdate')->first();

            if (! empty($aging_date)) {
                $date = Carbon\Carbon::parse($aging_date);
                $now = Carbon\Carbon::today();

                $data['aging'] = $date->diffInDays($now);
            }
        }

        $accounts_data = [
            'aging' => $data['aging'],
            'balance' => currency($data['balance']),
        ];

        dbset('crm_accounts', 'id', $account_id, $accounts_data);

        $account_currency = get_account_currency($account_id);
        if ($account_currency == 'ZAR') {
            \DB::table('crm_accounts')->where('id', $account_id)->update(['zar_balance' => \DB::raw('balance')]);
        }
        if ($account_currency == 'USD') {
            $zar_balance = currency_to_zar('USD', $data['balance']);
            \DB::table('crm_accounts')->where('id', $account_id)->update(['zar_balance' => $zar_balance]);
        }
    }
}
