<?php

function aftercommit_ledger_account_categories_update_instances($request)
{
    $db_conns = db_conns_excluding_current();
    $data = \DB::table('acc_ledger_account_categories')->where('id', $request->id)->get()->first();
    $data = (array) $data;
    foreach ($db_conns as $c) {
        \DB::connection($c)->table('acc_ledger_account_categories')->updateOrInsert(['id' => $data['id']], $data);
    }
}

function aftercommit_ledger_accounts_update_instances($request)
{
    $db_conns = db_conns_excluding_current();
    $data = \DB::table('acc_ledger_accounts')->where('id', $request->id)->get()->first();
    $data = (array) $data;
    foreach ($db_conns as $c) {
        \DB::connection($c)->table('acc_ledger_accounts')->updateOrInsert(['id' => $data['id']], $data);
    }
}

function beforedelete_delete_ledger_references($request)
{
    $has_transactions = \DB::table('acc_ledgers')->where('ledger_account_id', $request->id)->count();

    if ($has_transactions) {
        return 'Ledger account in use, all transactions should be re-allocated first';
    }
}

function button_ledger_accounts_set_targets($request)
{
    $accounts = \DB::table('acc_ledger_accounts')->get();
    foreach ($accounts as $account) {
        $target_three_months = \DB::table('acc_ledger_totals')
            ->where('ledger_account_id', $account->id)
            ->where('period', '<', date('Y-m-01'))
            ->where('period', '>=', date('Y-m-01', strtotime('-3 months')))
            ->sum('total');
        $target = ($target_three_months != 0) ? $target_three_months / 3 : 0;

        \DB::table('acc_ledger_accounts')->where('id', $account->id)->update(['target' => $target]);
    }
}

function get_ledger_account_balance_at_date($ledger_account_id, $date)
{
    return \DB::table('acc_ledgers')->where('ledger_account_id', $ledger_account_id)->where('docdate', '<=', $date)->sum('amount');
}
