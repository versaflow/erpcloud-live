<?php

function beforesave_commitment_date_check_debtor_status($request)
{
    if (empty($request->amount)) {
        return 'Amount cannot be zero.';
    }
    if (empty($request->commitment_date)) {
        return 'Date required.';
    }

    $account_id = $request->account_id;
    $account = dbgetaccount($account_id);

    $commitment_date_allowed = \DB::connection('default')->table('crm_debtor_status')->where('id', $account->debtor_status_id)->pluck('commitment_date_allowed')->first();

    if (! $commitment_date_allowed) {
        return 'Current Debtor status does not allow commitment dates.';
    }

    $date = Carbon\Carbon::parse($request->commitment_date);
    $now = Carbon\Carbon::today();
    $num_commitment_days = $date->diffInDays($now);
    if ($num_commitment_days > 7) {
        return 'Commitment date cannot be more than 7 days.';
    }

    $max_debtor_aging = \DB::connection('default')->table('crm_debtor_status')->where('is_deleted', 0)->where('commitment_date_allowed', 0)->orderby('aging', 'asc')->pluck('aging')->first();
    if ($max_debtor_aging) {
        $total_aging_days = $account->aging + $num_commitment_days;

        if ($total_aging_days > $max_debtor_aging) {
            $max_commitment_days = $max_debtor_aging - $account->aging - 1;
            if ($max_commitment_days > 0) {
                $max_commitment_date = date('Y-m-d', strtotime('+ '.$max_commitment_days.' days'));

                return 'Commitment date will expire, maximum allowed commitment date is '.$max_commitment_date.'.';
            } else {
                return 'Commitment date cannot be set, account aging exceeds maximum debtor status aging.';
            }
        }
    }
}

function check_commitments_paid($account_id = false)
{
    if ($account_id === 1) {
        return false;
    }
    if ($account_id) {
        $commitments = \DB::connection('default')->table('crm_commitment_dates')
            ->where('account_id', $account_id)
            ->where('expired', 0)
            ->where('commitment_fulfilled', 0)->get();
    } else {
        $commitments = \DB::connection('default')->table('crm_commitment_dates')
            ->where('expired', 0)
            ->where('commitment_fulfilled', 0)->get();
    }
    $processed_account_ids = [];
    foreach ($commitments as $commitment) {
        if (in_array($commitment->account_id, $processed_account_ids)) {
            continue;
        }
        $processed_account_ids[] = $commitment->account_id;
        $amount_paid = \DB::connection('default')
            ->table('acc_cashbook_transactions')
            ->where('docdate', '>', $commitment->created_at)
            ->where('account_id', $account_id)
            ->sum('total');
        if ($amount_paid >= $commitment->amount) {
            $data = ['commitment_fulfilled' => 1, 'fulfilled_at' => date('Y-m-d H:i:s')];
            \DB::connection('default')->table('crm_commitment_dates')
                ->where('expired', 0)
                ->where('commitment_fulfilled', 0)
                ->where('account_id', $account_id)
                ->update($data);
        }
    }
}

function schedule_expire_commitment_dates()
{
    $account_ids = \DB::table('crm_accounts')->where('is_deleted', 0)->pluck('id')->toArray();
    foreach ($account_ids as $account_id) {
        (new DBEvent)->setAccountAging($account_id);
    }

    \DB::table('crm_commitment_dates')
        ->where('expired', 0)
        ->where('commitment_fulfilled', 0)
        ->where('commitment_date', '<=', date('Y-m-d'))
        ->update(['expired' => 1]);

    $account_ids = \DB::table('crm_accounts')->where('commitment', 1)->pluck('id')->toArray();

    foreach ($account_ids as $account_id) {
        if (! account_has_debtor_commitment($account_id)) {
            \DB::table('crm_accounts')->where('id', $account_id)->update(['commitment' => 0]);
            (new DBEvent)->setAccountAging($account_id);
        }
    }

    $commitments = \DB::table('crm_commitment_dates')
        ->where('expired', 0)
        ->where('approved', 1)
        ->where('commitment_fulfilled', 0)
        ->where('commitment_date', '<=', date('Y-m-d'))
        ->get();
    foreach ($commitments as $commitment) {
        $data = [];
        $account = dbgetaccount($commitment->account_id);
        $account_id = $account->id;
        $currency_symbol = get_account_currency_symbol($account_id);
        $data['function_name'] = 'aftersave_commitment_date_debtor_status';
        $data['date'] = date('Y-m-d');
        $data['commitment_date'] = $commitment->commitment_date;
        $data['commitment_amount'] = $currency_symbol.' '.currency($account->balance);
        //$data['test_debug'] = 1;
        erp_process_notification($account_id, $data, []);
    }
}

function account_has_debtor_commitment($account_id)
{
    $commitments = \DB::connection('default')->table('crm_commitment_dates')
        ->where('account_id', $account_id)
        ->where('expired', 0)
        ->where('commitment_fulfilled', 0)
        ->where('approved', 1)
        ->count();
    if ($commitments) {
        return true;
    } else {
        return false;
    }
}

function ajax_commitment_date_get_amount($request)
{
    $response = [];
    if (! empty($request->account_id)) {
        $response['amount'] = abs(dbgetaccount($request->account_id)->balance);
    }

    return $response;
}

function beforesave_commitment_dates_required($request)
{
    if (empty($request->amount)) {
        return 'Amount cannot be zero.';
    }
    if (empty($request->commitment_date)) {
        return 'Date required.';
    }
}

function beforesave_commitment_date_resellers($request)
{
    if (! check_access('1')) {
        return json_alert('Debtor commitment dates can only be added by admin.', 'warning');
    }

    if (date('Y-m-d', strtotime($request->commitment_date)) > date('Y-m-t')) {
        return json_alert('Debtor commitment dates cannot be for the following month.', 'warning');
    }
    $account_id = $request->account_id;
    $account = dbgetaccount($account_id);
    $commitment_date_allowed = \DB::table('crm_debtor_status')->where('id', $account->debtor_status_id)->pluck('commitment_date_allowed')->first();
    if (! $commitment_date_allowed) {
        return json_alert('Commitment date not allowed for current debtor status.', 'warning');
    }
    if ($account->type == 'reseller') {
        return json_alert('Debtor commitment dates cannot be set for resellers.', 'warning');
    }
}

function aftersave_commitment_date_debtor_status($request)
{
    $account_id = $request->account_id;
    $currency_symbol = get_account_currency_symbol($account_id);
    $commitment_date = $request->commitment_date;
    // $approved = \DB::table('crm_commitment_dates')->where('id', $request->id)->pluck('approved')->first();

    $commitment = \DB::table('crm_commitment_dates')->where('id', $request->id)->get()->first();
    (new DBEvent)->setAccountAging($account_id);
    $balance = \DB::table('crm_accounts')->where('id', $account_id)->pluck('balance')->first();

    if (! empty(session('event_db_record'))) {
        $beforesave_row = session('event_db_record');

        if (! empty($commitment_date) && $beforesave_row->commitment_date != $commitment_date) {
            if (date('Y-m-d') <= date('Y-m-d', strtotime($commitment_date))) {
                \DB::table('crm_accounts')->where('id', $account_id)->update(['commitment' => 1]);
                switch_account($account_id, 'Enabled');

                $data['function_name'] = __FUNCTION__;
                $data['date'] = date('Y-m-d');
                $data['commitment_date'] = $commitment_date;
                $data['commitment_amount'] = $currency_symbol.' '.currency($commitment->amount);
                $data['test_debug'] = 1;
                erp_process_notification($account_id, $data, []);
            }
        }
    } elseif (! empty($commitment_date) && date('Y-m-d') <= date('Y-m-d', strtotime($commitment_date))) {
        \DB::table('crm_accounts')->where('id', $account_id)->update(['commitment' => 1]);
        switch_account($account_id, 'Enabled');

        $data['function_name'] = __FUNCTION__;
        $data['date'] = date('Y-m-d');
        $data['commitment_date'] = $commitment_date;
        $data['commitment_amount'] = $currency_symbol.' '.currency($commitment->amount);
        $data['test_debug'] = 1;
        erp_process_notification($account_id, $data, []);
    }
    $sql = 'UPDATE crm_accounts 
    JOIN crm_commitment_dates ON crm_commitment_dates.account_id=crm_accounts.id
    SET crm_accounts.commitment_date = crm_commitment_dates.commitment_date
    WHERE crm_commitment_dates.expired=0 AND crm_commitment_dates.approved=1  AND crm_commitment_dates.commitment_fulfilled=0';
    \DB::statement($sql);
}
