<?php

function schedule_populate_written_off()
{
    $system_user_id = get_system_user_id();
    \DB::table('crm_written_off')->update(['is_deleted' => 1]);

    // $debtor_status_ids = \DB::table('crm_debtor_status')->where('aging','>',0)->where('is_deleted', 0)->pluck('id')->toArray();
    $debtor_status_ids = \DB::table('crm_debtor_status')->where('id', '!=', 1)->where('is_deleted', 0)->pluck('id')->toArray();

    $account_ids = \DB::table('crm_accounts')->where('partner_id', 1)->where('payment_type', '!=', 'Internal')->whereIn('debtor_status_id', $debtor_status_ids)->pluck('id')->toArray();

    $debt_account_ids = \DB::table('acc_general_journals')->where('ledger_account_id', 5)->where('credit_amount', '>', 0)->where('reference', 'Bad Debt Written Off')->pluck('account_id')->unique()->filter()->toArray();
    $debt_account_ids = collect($debt_account_ids);
    $account_ids = collect($account_ids)->merge($debt_account_ids)->unique()->toArray();

    foreach ($account_ids as $account_id) {
        $debt_total = \DB::table('acc_general_journals')->where('account_id', $account_id)->where('ledger_account_id', 5)->where('reference', 'Bad Debt Written Off')->sum('credit_amount');
        //if($debt_total > 0){

        $account_data = \DB::table('crm_accounts')->where('id', $account_id)->get()->first();
        if ($account_data->status == 'Deleted') {

            process_aging_actions($account_id, false, false);
            $account_data = \DB::table('crm_accounts')->where('id', $account_id)->get()->first();
        }

        if ($account_data->status != 'Deleted' && ! in_array($account_data->debtor_status_id, $debtor_status_ids)) {
            continue;
        }
        $aging = $account_data->aging;

        $data = [
            'account_id' => $account_id,
            'written_off_balance' => $debt_total,
            'aging' => $aging,
            'aging_group' => $account_data->aging_group,
            'payment_type' => $account_data->payment_type,
            'account_status' => $account_data->status,
            'debtor_status_id' => $account_data->debtor_status_id,
            'account_balance' => $account_data->balance,
            'account_deleted_at' => $account_data->deleted_at,
            'currency' => $account_data->currency,
            'has_address' => (! empty($account_data->address)) ? 1 : 0,
            'account_type' => $account_data->type,
            'demand_sent' => $account_data->demand_sent,
            'is_deleted' => 0,
        ];

        if (strtolower($account_data->currency) == 'usd') {
            $data['is_deleted'] = 1;
        }

        $commitment_date = \DB::table('crm_commitment_dates')->where('account_id', $account_id)->where('expired', 0)->where('approved', 1)->orderBy('commitment_date', 'desc')->pluck('commitment_date')->first();
        if ($commitment_date) {
            $data['commitment_date'] = $commitment_date;
        }

        $data['aging_group'] = '0-30';

        if ($aging > 90) {
            $data['aging_group'] = '90+';
        } elseif ($aging > 60) {
            $data['aging_group'] = '61-90';
        } elseif ($aging > 30) {
            $data['aging_group'] = '31-60';
        }
        $e = \DB::table('crm_written_off')->where('account_id', $account_id)->count();
        if (! $e) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = $system_user_id;
        }

        \DB::table('crm_written_off')->updateOrInsert(['account_id' => $account_id], $data);
        //}
    }

    \DB::table('crm_written_off')->update(['last_note_date' => null, 'last_note' => '']);
    $ws = \DB::table('crm_written_off')->get();
    foreach ($ws as $w) {
        $last_note = \DB::table('erp_module_notes')->where('module_id', 1982)->where('row_id', $w->id)->orderBy('id', 'desc')->get()->first();
        if ($last_note) {
            \DB::table('crm_written_off')->where('id', $w->id)->update(['last_note_date' => $last_note->created_at, 'last_note' => $last_note->created_at.' '.$last_note->note]);
        }
    }

    \DB::table('crm_written_off')->update(['accountability_match' => 0]);

    \DB::table('crm_written_off')
        ->join('crm_accounts', 'crm_accounts.id', '=', 'crm_written_off.account_id')
        ->whereRaw('crm_written_off.accountability_current_status_id=crm_accounts.debtor_status_id')
        ->update(['crm_written_off.accountability_match' => 1]);

    // $account_ids = \DB::table('crm_written_off')->where('account_status','Deleted')->pluck('account_id')->toArray();
    // foreach($account_ids as $account_id){
    //     $balance_owed = get_debtor_balance($account_id, true);

    //  //   \DB::table('crm_written_off')->where('account_id',$account_id)->update(['account_balance' => $balance_owed]);
    //     if($balance_owed <=0){
    //         \DB::table('crm_written_off')->where('account_id',$account_id)->update(['is_deleted' => 1]);
    //     }
    // }

    $salesmanIds = get_salesman_user_ids();

    $totalSalesmen = count($salesmanIds);
    if ($totalSalesmen > 0) {

        $account_ids = \DB::table('crm_written_off')->whereNotIn('salesman_id', $salesmanIds)->where('is_deleted', 0)->pluck('account_id')->unique()->toArray();

        $index = 0;
        foreach ($account_ids as $account_id) {

            \DB::table('crm_written_off')->where('account_id', $account_id)->update(['salesman_id' => $salesmanIds[$index]]);
            \DB::table('crm_accounts')->where('id', $account_id)->update(['salesman_id' => $salesmanIds[$index]]);
            $index++;
            if (! isset($salesmanIds[$index])) {
                $index = 0;
            }
        }
    }

    \DB::table('crm_written_off')->where('payment_type', 'Internal')->update(['is_deleted' => 1]);
    \DB::table('crm_written_off')->where('debtor_status_id', 1)->update(['is_deleted' => 1]);
    \DB::table('crm_written_off')->where('payment_type', '!=', 'Internal')->where('debtor_status_id', '!=', 1)->update(['is_deleted' => 0]);

}

function onload_set_accountability_status()
{
    \DB::table('crm_written_off')
        ->join('crm_accounts', 'crm_accounts.id', '=', 'crm_written_off.account_id')->update(['crm_written_off.debtor_status_id' => \DB::raw('crm_accounts.debtor_status_id')]);
    \DB::table('crm_written_off')->update(['accountability_match' => 0]);
    \DB::table('crm_written_off')
        ->join('crm_accounts', 'crm_accounts.id', '=', 'crm_written_off.account_id')
        ->whereRaw('accountability_current_status_id=crm_accounts.debtor_status_id')->update(['accountability_match' => 1]);
    \DB::table('crm_written_off')->where('payment_type', 'Internal')->update(['is_deleted' => 1]);
    \DB::table('crm_written_off')->where('debtor_status_id', 1)->update(['is_deleted' => 1]);
    \DB::table('crm_written_off')->where('payment_type', '!=', 'Internal')->where('debtor_status_id', '!=', 1)->update(['is_deleted' => 0]);
}

function aftersave_set_accountability_match($request)
{

    \DB::table('crm_written_off')
        ->join('crm_accounts', 'crm_accounts.id', '=', 'crm_written_off.account_id')->update(['crm_written_off.debtor_status_id' => \DB::raw('crm_accounts.debtor_status_id')]);
    \DB::table('crm_written_off')->update(['accountability_match' => 0]);
    \DB::table('crm_written_off')
        ->join('crm_accounts', 'crm_accounts.id', '=', 'crm_written_off.account_id')
        ->whereRaw('accountability_current_status_id=crm_accounts.debtor_status_id')->update(['accountability_match' => 1]);
}
