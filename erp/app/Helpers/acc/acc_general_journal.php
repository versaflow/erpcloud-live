<?php

function aftersave_general_journal_account_payment($request)
{
    if (!empty($request->account_id)) {
        (new DBEvent())->setAccountAging($request->account_id);
    }
}

function afterdelete_general_journal_delete_account_payment($request)
{
    if (!empty($request->account_id)) {
        (new DBEvent())->setAccountAging($request->account_id);
    }
}

function beforesave_journal_check_ledger_accounts($request)
{
    if (empty($request->ledger_account_id)) {
        return 'Ledger account required';
    }
}


function aftersave_journal_update_transaction_totals($request)
{
    if (!empty($request->transaction_id)) {
        $docids = \DB::table('acc_general_journals')->where('transaction_id', $request->transaction_id)->pluck('id')->toArray();
        \DB::table('acc_ledgers')->where('doctype', 'General Journal')->whereIn('docid', $docids)->delete();
        \DB::table('acc_general_journal_transactions')->where('id', $request->transaction_id)->update(['posted' => 0]);
        \DB::table('acc_general_journals')->where('transaction_id', $request->transaction_id)->update(['posted' => 0]);
        $credit_total = \DB::table('acc_general_journals')->where('transaction_id', $request->transaction_id)->sum('credit_amount');
        $debit_total = \DB::table('acc_general_journals')->where('transaction_id', $request->transaction_id)->sum('debit_amount');
        \DB::table('acc_general_journal_transactions')->where('id', $request->transaction_id)->update(['credit_total' => $credit_total,'debit_total' => $debit_total]);
    }
}

function beforesave_control_accounts_allocated($request)
{
    if (($request->ledger_account_id == 5) && empty($request->account_id)) {
        return 'Customer control adjustments needs to be assigned to a customer';
    }
    if (($request->ledger_account_id == 6) && empty($request->supplier_id)) {
        return 'Supplier control adjustments needs to be assigned to a supplier';
    }
}


function button_general_journal_transaction_post_journals($request)
{
    $credit_total = \DB::table('acc_general_journals')->where('transaction_id', $request->id)->sum('credit_amount');
    $debit_total = \DB::table('acc_general_journals')->where('transaction_id', $request->id)->sum('debit_amount');
    \DB::table('acc_general_journal_transactions')->where('id', $request->id)->update(['credit_total' => $credit_total,'debit_total' => $debit_total]);

    $trx = \DB::table('acc_general_journal_transactions')->where('id', $request->id)->get()->first();

    $balance =  currency($trx->debit_total) - abs(currency($trx->credit_total)) ;
    if (empty($trx->credit_total)) {
        return json_alert('Credit total cannot be zero. Transaction could not be posted.', 'warning');
    }
    if (empty($trx->debit_total)) {
        return json_alert('Debit total cannot be zero. Transaction could not be posted.', 'warning');
    }

    if ($balance != 0) {
        return json_alert('Totals do not balance. '.$balance. ' difference.', 'warning');
    }
    \DB::table('acc_general_journal_transactions')->where('id', $request->id)->update(['posted' => 1]);
    $journals = \DB::table('acc_general_journals')->where('transaction_id', $trx->id)->get();


    $docids = \DB::table('acc_general_journals')->where('transaction_id', $trx->id)->pluck('id')->toArray();
    $erp = new DBEvent();
    $erp->setTable('acc_general_journals');
    foreach ($docids as $docid) {
        $erp->postDocument($docid);
    }

    $erp->postDocumentCommit();
    return json_alert('Transaction posted.');
}

function aftersave_journals_generate_header_transactions($request)
{
    journals_generate_header_transactions();
}

function journals_generate_header_transactions()
{
    $conn = 'default';

    $journal_dates = \DB::connection($conn)->table('acc_general_journals')->where('transaction_id', 0)->where('posted', 1)->get();


    foreach ($journal_dates as $identifier) {
        $identifier = (object) $identifier;
        $data = [
            'doctype' => 'General Journal',
            'docdate' => date('Y-m-d'),
            'name' => $identifier->reference,
            'posted' => 1,
        ];

        $trx_id = \DB::connection($conn)->table('acc_general_journal_transactions')->insertGetId($data);

        \DB::connection($conn)->table('acc_general_journals')
            ->where('id', $identifier->id)
            ->update(['transaction_id' => $trx_id]);
    }
    $transaction_ids = \DB::connection($conn)->table('acc_general_journals')->pluck('transaction_id')->toArray();
    foreach ($transaction_ids as $transaction_id) {
        $credit_total = \DB::table('acc_general_journals')->where('transaction_id', $transaction_id)->sum('credit_amount');
        $debit_total = \DB::table('acc_general_journals')->where('transaction_id', $transaction_id)->sum('debit_amount');
        \DB::connection($conn)->table('acc_general_journal_transactions')
            ->where('id', $transaction_id)
            ->update(['credit_total' => $credit_total, 'debit_total' => $debit_total]);
    }
}
