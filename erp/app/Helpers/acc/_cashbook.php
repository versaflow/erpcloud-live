<?php

//## EVENTS

function beforedelete_payment_provider_check($request)
{
    $provider = \DB::table('acc_cashbook')->where('id', $request->id)->get()->first();
    if ($provider->id == 8 || str_contains($provider->name, 'Cash')) {
        return 'Cash Register cannot be deleted';
    }
    if ($provider->id == 9 || str_contains($provider->name, 'Bank')) {
        return 'Cash Register cannot be deleted';
    }
    try {
        $balance = \DB::table('acc_cashbook')->where('id', $request->id)->pluck('balance')->first();
        if ($balance != 0) {
            return 'Provider cannot be deleted. Balance needs to be zero.';
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        Log::Debug($error);
    }
}
//## BUTTONS
function button_cashbook_reconcile($request)
{
    cashbook_reconcile($request->id);

    return json_alert('Done');
}

//## HELPERS

function is_cashbook_ledger_account($ledger_account_id)
{
    $account_verified = \DB::table('acc_cashbook')
        ->where('ledger_account_id', $ledger_account_id)
        ->count();
    if ($account_verified > 0) {
        return true;
    } else {
        return false;
    }
}

function cashbook_control_transfer($credit_account_id, $total, $docdate = false, $cashbook_transaction_id = false)
{
    // verify both ledger accounts are payment providers

    if (is_cashbook_ledger_account($credit_account_id)) {
        $erp = new DBEvent;
        $erp->setTable('acc_cashbook_transactions');

        $cashbook_control_id = 57;
        if (! $docdate) {
            $docdate = date('Y-m-d');
        }

        $cashbook_id = \DB::table('acc_cashbook')->where('ledger_account_id', $credit_account_id)->pluck('id')->first();

        $data = [
            'cashbook_id' => $cashbook_id,
            'ledger_account_id' => $cashbook_control_id,
            'doctype' => 'Cashbook Control Payment',
            'total' => $total,
            'docdate' => $docdate,
            'api_status' => 'Complete',
        ];

        if ($cashbook_transaction_id) {
            if (! str_contains($reference, 'Recovery')) {
                $data['cashbook_transaction_id'] = $cashbook_transaction_id;
                $reference = \DB::connection('default')->table('acc_cashbook_transactions')->where('id', $cashbook_transaction_id)->pluck('reference')->first();
                $data['reference'] = 'Cashbook Transaction ID:'.$bank_id.' '.$reference;
            }
        }

        $result = \DB::connection('default')->table('acc_cashbook_transactions')->insertGetId($data);

    }
}

function cashbook_control_transfer_fee($credit_account_id, $total, $docdate = false, $cashbook_transaction_id = false)
{
    // verify both ledger accounts are payment providers
    if (is_cashbook_ledger_account($credit_account_id)) {
        $erp = new DBEvent;
        $erp->setTable('acc_cashbook_transactions');

        $cashbook_control_id = 22;
        if (! $docdate) {
            $docdate = date('Y-m-d');
        }

        $cashbook_id = \DB::table('acc_cashbook')->where('ledger_account_id', $credit_account_id)->pluck('id')->first();
        $data = [
            'cashbook_id' => $cashbook_id,
            'ledger_account_id' => $cashbook_control_id,
            'doctype' => 'Cashbook Control Payment',
            'total' => $total,
            'docdate' => $docdate,
            'api_status' => 'Complete',
        ];

        if ($cashbook_transaction_id) {
            if (! str_contains($reference, 'Recovery')) {
                $data['cashbook_transaction_id'] = $cashbook_transaction_id;
                $reference = \DB::connection('default')->table('acc_cashbook_transactions')->where('id', $cashbook_transaction_id)->pluck('reference')->first();
                $data['reference'] = 'Cashbook Transaction ID:'.$bank_id.' '.$reference;
            }
        }
        $result = $erp->save($data);
    }
}

function delete_journal_entry_by_cashbook_transaction_id($cashbook_transaction_id)
{
    \DB::table('acc_cashbook_transactions')->where('cashbook_transaction_id', $cashbook_transaction_id)->delete();
    $journals = \DB::table('acc_general_journals')->where('cashbook_transaction_id', $cashbook_transaction_id)->get();
    foreach ($journals as $journal) {
        \DB::table('acc_general_journal_transactions')->where('id', $journal->transaction_id)->delete();
        \DB::table('acc_ledgers')->where('docid', $journal->id)->where('doctype', 'General Journal')->delete();
        \DB::table('acc_general_journals')->where('id', $journal->id)->delete();
    }
}
