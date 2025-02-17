<?php

function schedule_payfast_reconcile()
{
    // $sql = "SELECT count(*) as total FROM `telecloud`.`acc_cashbook_transactions` where cashbook_id=5 and api_status='Complete' and api_id!=103404415 and api_id > 0 group by api_id  having count(*) > 2";
    // $records = \DB::select($sql);
    // if(count($records) > 1){
    //     debug_email("Payfast account has duplicate api_id");
    // }

    $payment_option = get_payment_option('Payfast');
    $payfast_enabled = $payment_option->enabled;
    if (!$payfast_enabled) {
        return false;
    }
    // aa('1');
    // shopify_import_payments();
    $unreconciled_count = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 5)->where('reconciled', 0)->count();
    if (!$unreconciled_count) {
          return true;
    }

    if (!$payment_option->enabled || empty($payment_option->payfast_id) || empty($payment_option->payfast_key) && empty($payment_option->payfast_pass_phrase)) {
        return false;
    }
    aa('2');

    $payfast_transactions = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 5)->where('api_id', '>', 0)->where('reference', 'NOT LIKE', 'Payfast Fee%')->get();
    // aa($payfast_transactions);
    foreach ($payfast_transactions as $payfast_transaction) {
        aa($payfast_transaction->api_id);
        \DB::table('acc_cashbook_transactions')->where('reference', 'Payfast Fee '.$payfast_transaction->api_id)->update(['api_id' => $payfast_transaction->api_id]);
    }
    aa('3');
    $cashbook_transactions = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 5)->where('api_id','<>',0)->where('api_id','<>',null)->orderBy('docdate', 'desc')->orderBy('id', 'desc')->get();
    // aa($cashbook_transactions);
    foreach ($cashbook_transactions as $cashbook_transaction) {
        // aa($cashbook_transaction);
        if (isset($cashbook_transaction->api_balance) && isset($cashbook_transaction->balance) && $cashbook_transaction->api_balance == $cashbook_transaction->balance) {
            \DB::table('acc_cashbook_transactions')
            ->where('cashbook_id', 5)
            ->where('api_id','<>',0)
            ->where('id', '=', $cashbook_transaction->id)
            ->where('docdate', '=', $cashbook_transaction->docdate)
            ->update(['reconciled' => 1]);
            break;
        }
    }
    aa('4');
    
    $date = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 5)->where('reconciled', 0)->orderBy('docdate', 'asc')->pluck('docdate')->first();
    if (empty($date)) {
        $date = date('Y-m-d');
    }

    // $transactions = [];
    $current_date = date('Y-m-d');
    $i = 0;

    while (date('Y-m', strtotime($date)) <= date('Y-m', strtotime($current_date))) {
        $lookup_date = date('Y-m-01', strtotime($date));
        \DB::table('acc_periods')->where('period', date('Y-m', strtotime($date)))->update(['status' => 'Open']);
        $date_transactions = payfast_get_transactions($lookup_date);
        foreach ($date_transactions as $trx) {
            $transactions[] = $trx;
        }
        $date = date('Y-m-01', strtotime($date.' +1 month'));
        ++$i;
        if ($i >= 8) {
            return false;
        }
    }
    aa('5');

    // payouts are set to last month - include last months transactions to update payout dates
    //$lastmonth_transactions = payfast_get_transactions(date('Y-m-d',strtotime($date.' -1 month')));
    // foreach($lastmonth_transactions as $trx){
    //    $transactions[] = $trx;
    // }

    // dddd($transactions);

    if ($transactions[0] != "400" && !empty($transactions) && is_array($transactions) && count($transactions) > 0) {
        foreach ($transactions as $api_trx) {
            if ($api_trx['Type'] = 'PAYOUT') {
                $total = currency($api_trx['Gross']) * -1;
                \DB::table('acc_cashbook_transactions')
                ->where('total', $total)
                ->where('cashbook_id', 5)
                ->where('docdate', '>', date('Y-m-d', strtotime($api_trx['Date'].' - 2 days')))
                ->where('docdate', '<', date('Y-m-d', strtotime($api_trx['Date'].' + 2 days')))
                ->update(['api_id' => $api_trx['PF Payment ID'], 'docdate' => $api_trx['Date']]);
            } else {
                $total = currency($api_trx['Gross']);
                \DB::table('acc_cashbook_transactions')
                ->where('total', $total)
                ->where('cashbook_id', 5)
                ->where('docdate', date('Y-m-d', strtotime($api_trx['Date'])))
                ->where('Description', '<>', $ref)
                ->orderBy(['date', 'api_id'])
                ->update(['api_id' => $api_trx['PF Payment ID']]);
            }
            $ref = $api_trx['Description'];
            // $date1 = date('Y-m-d',strtotime($api_trx["Date"]));

            if (str_contains($api_trx['Name'], 'Cloud Telecoms #')) {
                $shop_order_id = (int) str_replace('Cloud Telecoms #', '', $api_trx['Name']);
                \DB::table('acc_cashbook_transactions')->where('total', $api_trx['Gross'])->where('reference', 'Cloud Telecoms Website Order '.$shop_order_id)->update(['api_id' => $api_trx['PF Payment ID']]);
            }

            if (str_contains($api_trx['Name'], 'Your New Shop #')) {
                $shop_order_id = (int) str_replace('Your New Shop #', '', $api_trx['Name']);
                \DB::table('acc_cashbook_transactions')->where('total', $api_trx['Gross'])->where('reference', 'Cloudtools Website Order '.$shop_order_id)->update(['api_id' => $api_trx['PF Payment ID']]);
            }

            if (str_contains($api_trx['Name'], 'Cloudtools #')) {
                $shop_order_id = (int) str_replace('Cloudtools #', '', $api_trx['Name']);
                \DB::table('acc_cashbook_transactions')->where('total', $api_trx['Gross'])->where('reference', 'Cloudtools Website Order '.$shop_order_id)->update(['api_id' => $api_trx['PF Payment ID']]);
            }
            if ($api_trx['Type'] != 'FUNDS_RECEIVED_REVERSAL' && $api_trx['PF Payment ID']) {
                \DB::table('acc_cashbook_transactions')->where('api_id', $api_trx['PF Payment ID'])->where('reference', 'not like', '%REVERSAL%')->update(['docdate' => $api_trx['Date']]);
            }

            // $api_ids[] .= $api_trx["PF Payment ID"];
        }
        aa('6');

        // foreach ($transactions as $api_trx) {
        //     create_payfast_payout_transaction($api_trx);
        //     if($api_trx['Type']=='FUNDS_RECEIVED_REVERSAL'){
        //         \DB::table('acc_cashbook_transactions')->where('reference', 'like','%Reversal%')->where('api_id', $api_trx["PF Payment ID"])->update(['api_balance' => currency($api_trx["Balance"])]);
        //     } else {
        //         \DB::table('acc_cashbook_transactions')->where('api_id', $api_trx["PF Payment ID"])->update(['api_balance' => currency($api_trx["Balance"])]);
        //     }

        // }

        cashbook_reconcile(5);
    }
}





function schedule_customer_control_set_rand_amounts()
{
    \DB::table('acc_cashbook_transactions')->where('document_currency', 'ZAR')->whereRaw('rand_total!=total')->update(['rand_total' => \DB::raw('total')]);
    $usd_transactions = \DB::table('acc_cashbook_transactions')->where('document_currency', '!=', 'ZAR')->where('rand_total', 0)->where('total', '!=', 0)->get();
    foreach ($usd_transactions as $usd_transaction) {
        $rand_total = currency_to_zar($usd_transaction->document_currency, $usd_transaction->total, $usd_transaction->docdate);
        \DB::table('acc_cashbook_transactions')->where('id', $usd_transaction->id)->update(['rand_total' => $rand_total]);
    }
    \DB::table('crm_documents')->where('document_currency', 'ZAR')->whereRaw('rand_total!=total')->update(['rand_total' => \DB::raw('total')]);
    $usd_transactions = \DB::table('crm_documents')->where('document_currency', '!=', 'ZAR')->where('rand_total', 0)->where('total', '!=', 0)->get();
    foreach ($usd_transactions as $usd_transaction) {
        $rand_total = currency_to_zar($usd_transaction->document_currency, $usd_transaction->total, $usd_transaction->docdate);
        \DB::table('crm_documents')->where('id', $usd_transaction->id)->update(['rand_total' => $rand_total]);
    }
}

function button_cashbook_import_ofx($request)
{
    $data = [];
    $data['cashbook_id'] = $request->id;

    return view('__app.button_views.bank_import', $data);
}

//## EVENTS
/// CASH
function beforesave_allocate_cash_payment_check($request)
{
    if ($request->cashbook_id == 8 && $request->supplier_id > '' && $request->total > 0) {
        //  return 'Supplier payments need to be negative';
    }
}

function aftersave_allocate_cash_payment($request)
{
    if (!in_array($trx->cashbook_id, [8, 14, 16, 17])) {
        \DB::table('acc_cashbook_transactions')->where('id', $request->id)->update(['approved' => 1]);
    }

    $trx = \DB::table('acc_cashbook_transactions')->where('id', $request->id)->get()->first();
    $cashbook = \DB::table('acc_cashbook')->where('id', $trx->cashbook_id)->get()->first();
    \DB::table('acc_cashbook_transactions')->where('id', $request->id)->update(['document_currency' => $cashbook->currency]);
    if ($trx->cashbook_id == 8) {
        $erp = new \DBEvent();

        if ($trx->ledger_account_id) {
            delete_journal_entry_by_cashbook_transaction_id($trx->id);
        }
        $trx_data = (array) $trx;
        if (0 == $trx->total) {
            return json_alert('Cannot allocate zero total transactions', 'error');
        }

        \DB::table('acc_cashbook_transactions')
        ->where('id', $trx->id)
        ->update([
            'document_currency' => 'ZAR',
        ]);

        if (!empty($request->account_id)) {
            $trx_data['doctype'] = 'Cashbook Customer Receipt';
        } elseif (!empty($request->supplier_id)) {
            $trx_data['doctype'] = 'Cashbook Supplier Payment';
        } elseif (!empty($request->ledger_account_id)) {
            $trx_data['doctype'] = 'Cashbook Expense';
        }
        if (!empty($request->new_record)) {
            $trx_data['approved'] = 0;
        }
        if (is_superadmin()) {
            $trx_data['approved'] = 1;
        }

        \DB::table('acc_cashbook_transactions')->where('id', $trx->id)->update($trx_data);
        if (!empty($request->account_id)) {
            (new DBEvent())->setDebtorBalance($request->account_id);
        }
        cashbook_reconcile(8);
    }

    if ($trx->cashbook_id == 14) {
        $erp = new \DBEvent();

        if ($trx->ledger_account_id) {
            delete_journal_entry_by_cashbook_transaction_id($trx->id);
        }
        $trx_data = (array) $trx;
        if (0 == $trx->total) {
            return json_alert('Cannot allocate zero total transactions', 'error');
        }

        \DB::table('acc_cashbook_transactions')
        ->where('id', $trx->id)
        ->update([
            'document_currency' => 'USD',
        ]);

        if (!empty($request->account_id)) {
            $trx_data['doctype'] = 'Cashbook Customer Receipt';
        } elseif (!empty($request->supplier_id)) {
            $trx_data['doctype'] = 'Cashbook Supplier Payment';
        } elseif (!empty($request->ledger_account_id)) {
            $trx_data['doctype'] = 'Cashbook Expense';
        }
        if (!empty($request->new_record)) {
            $trx_data['approved'] = 0;
        }
        if (is_superadmin()) {
            $trx_data['approved'] = 1;
        }

        \DB::table('acc_cashbook_transactions')->where('id', $trx->id)->update($trx_data);
        if (!empty($request->account_id)) {
            (new DBEvent())->setDebtorBalance($request->account_id);
        }
        cashbook_reconcile(14);
    }

    if ($trx->cashbook_id == 16) {
        $erp = new \DBEvent();

        if ($trx->ledger_account_id) {
            delete_journal_entry_by_cashbook_transaction_id($trx->id);
        }
        $trx_data = (array) $trx;
        if (0 == $trx->total) {
            return json_alert('Cannot allocate zero total transactions', 'error');
        }

        \DB::table('acc_cashbook_transactions')
        ->where('id', $trx->id)
        ->update([
            'document_currency' => 'USD',
        ]);

        if (!empty($request->account_id)) {
            $trx_data['doctype'] = 'Cashbook Customer Receipt';
        } elseif (!empty($request->supplier_id)) {
            $trx_data['doctype'] = 'Cashbook Supplier Payment';
        } elseif (!empty($request->ledger_account_id)) {
            $trx_data['doctype'] = 'Cashbook Expense';
        }
        if (!empty($request->new_record)) {
            $trx_data['approved'] = 0;
        }
        if (is_superadmin()) {
            $trx_data['approved'] = 1;
        }

        \DB::table('acc_cashbook_transactions')->where('id', $trx->id)->update($trx_data);
        if (!empty($request->account_id)) {
            (new DBEvent())->setDebtorBalance($request->account_id);
        }
        cashbook_reconcile(16);
    }

    if ($trx->cashbook_id == 17) {
        $erp = new \DBEvent();

        if ($trx->ledger_account_id) {
            delete_journal_entry_by_cashbook_transaction_id($trx->id);
        }
        $trx_data = (array) $trx;
        if (0 == $trx->total) {
            return json_alert('Cannot allocate zero total transactions', 'error');
        }

        \DB::table('acc_cashbook_transactions')
        ->where('id', $trx->id)
        ->update([
            'document_currency' => 'USD',
        ]);

        if (!empty($request->account_id)) {
            $trx_data['doctype'] = 'Cashbook Customer Receipt';
        } elseif (!empty($request->supplier_id)) {
            $trx_data['doctype'] = 'Cashbook Supplier Payment';
        } elseif (!empty($request->ledger_account_id)) {
            $trx_data['doctype'] = 'Cashbook Expense';
        }
        if (!empty($request->new_record)) {
            $trx_data['approved'] = 0;
        }
        if (is_superadmin()) {
            $trx_data['approved'] = 1;
        }

        \DB::table('acc_cashbook_transactions')->where('id', $trx->id)->update($trx_data);
        if (!empty($request->account_id)) {
            (new DBEvent())->setDebtorBalance($request->account_id);
        }
        cashbook_reconcile(17);
    }

    // if(session('role_id') == 1){
    //     \DB::table('acc_cashbook_transactions')->where('id', $request->id)->update(['approved'=>1]);
    // }

    $cash_trxs = \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', [8, 14, 16, 17])->where('approved', 0)->get();
    foreach ($cash_trxs as $trx) {
        $cashbook_name = \DB::table('acc_cashbook')->where('id', $trx->cashbook_id)->pluck('name')->first();
        $exists = \DB::table('crm_approvals')->where('module_id', 1837)->where('row_id', $trx->id)->count();
        if (!$exists) {
            $title = $trx->docdate.' '.$trx->total;
            if ($trx->account_id) {
                $name = dbgetcell('crm_accounts', 'id', $trx->account_id, 'company');
                $title .= ' '.$name;
            }
            if ($trx->supplier_id) {
                $name = dbgetcell('crm_suppliers', 'id', $trx->supplier_id, 'company');
                $title .= ' '.$name;
            }
            if ($trx->ledger_account_id) {
                $name = dbgetcell('acc_ledger_accounts', 'id', $trx->ledger_account_id, 'name');
                $title .= ' '.$name;
            }
            $title = $title.' '.$cashbook_name;
            $data = [
                'module_id' => 1837,
                'row_id' => $trx->id,
                'title' => $title,
                'reference' => $cashbook_name.' '.$trx->total,
                'processed' => 0,
                'requested_by' => get_user_id_default(),
            ];
            (new \DBEvent())->setTable('crm_approvals')->save($data);
        }
    }
}

function beforedelete_pettycash_delete_payment($request)
{
    $trx = \DB::table('acc_cashbook_transactions')->where('id', $request->id)->get()->first();
    $erp = new \DBEvent();

    if ($trx->ledger_account_id) {
        delete_journal_entry_by_cashbook_transaction_id($trx->id);
    }
}

function beforesave_cash_register_check_allocate($request)
{
    if ($request->cashbook_id == 8) {
        $allocations = 0;
        if (!empty($request->account_id)) {
            ++$allocations;
        }
        if (!empty($request->supplier_id)) {
            ++$allocations;
        }
        if (!empty($request->ledger_account_id)) {
            ++$allocations;
        }

        if ($allocations == 0) {
            return 'Please select an account to allocate to.';
        }

        if ($allocations > 1) {
            return 'Cannot create cash transaction for multiple allocations.';
        }
    }
}

/// BANK
function beforedelete_bank_check_allocation($request)
{
    $trx = \DB::table('acc_cashbook_transactions')->where('id', $request->id)->get()->first();

    if ($trx->account_id > 0 || $trx->ledger_account_id > 0 || $trx->supplier_id > 0) {
        return json_alert('Cannot delete allocated transactions.', 'error');
    }
}
function afterdelete_bank_rebuild_balances($request)
{
    cashbook_reconcile($request->cashbook_id);
}

//## BUTTONS

function button_cashbook_transaction_allocate($request)
{
    $bank = \DB::table('acc_cashbook_transactions')->where('id', $request->id)->get()->first();
    $cashbook = \DB::table('acc_cashbook')->where('id', $bank->cashbook_id)->get()->first();
    if (!is_dev() && !$cashbook->allow_allocate) {
        return json_alert('Manual allocations are not allowed for this cashbook.', 'error');
    }
    $period = date('Y-m', strtotime($bank->docdate));
    $period_status = dbgetcell('acc_periods', 'period', $period, 'status');

    if ('Open' != $period_status) {
        return json_alert('Period closed', 'warning');
    }
    if ($bank->account_id > '') {
        $account = dbgetaccount($bank->account_id);
        if ($account->bank_allocate_airtime && $bank->total > 0) {
            // return json_alert('Allocated to a airtime customer.', 'warning');
        }
    }
    $data['bank'] = $bank;

    if ($bank->account_id > 0) {
        $bank_reference = \DB::table('acc_bank_references')->where('account_id', $bank->account_id)->get()->first();
        $data['reference_match'] = $bank_reference->reference;
        $data['reference_match_id'] = $bank_reference->id;
    }
    if ($ban->supplier_id > 0) {
        $bank_reference = \DB::table('acc_bank_references')->where('supplier_id', $bank->supplier_id)->get()->first();
        $data['reference_match'] = $bank_reference->reference;
        $data['reference_match_id'] = $bank_reference->id;
    }
    if ($bank->ledger_account_id > 0) {
        $bank_reference = \DB::table('acc_bank_references')->where('ledger_account_id', $bank->ledger_account_id)->get()->first();
        $data['reference_match'] = $bank_reference->reference;
        $data['reference_match_id'] = $bank_reference->id;
    }

    $control_account_ids = \DB::table('acc_cashbook')->pluck('ledger_account_id')->toArray();

    $accounts = \DB::table('crm_accounts')
        ->select('id', 'company', 'type')
        ->where('status', '!=', 'Deleted')
        ->where('type', '!=', 'lead')
        ->where('type', '!=', 'reseller_user')
        ->where('id', '!=', 1)
        ->where('currency', $cashbook->currency)
        ->where('partner_id', 1)->orderBy('company')->get()->toArray();

    $suppliers = \DB::table('crm_suppliers')
        ->select('id', 'company')
        ->where('status', 'Enabled')
        ->orderBy('company')->get()->toArray();

    $ledgers = \DB::table('acc_ledger_accounts as l')
        ->join('acc_ledger_account_categories as c', 'c.id', '=', 'l.ledger_account_category_id')
        ->select('l.id', \DB::raw('l.name AS name'), 'l.taxable', 'c.category')
        ->whereNotIn('l.id', $control_account_ids)
        ->where('l.allow_payments', 1)
        ->where('c.id', '!=', 20)
        ->where('c.id', '!=', 21)
        ->where('l.id', '!=', 4)
        ->orderBy('c.id')
        ->orderBy('l.name')->get()->toArray();

    $ledgers_expenses = \DB::table('acc_ledger_accounts as l')
        ->join('acc_ledger_account_categories as c', 'c.id', '=', 'l.ledger_account_category_id')
        ->select('l.id', \DB::raw('l.name AS name'), 'l.taxable', 'c.category')
        ->whereNotIn('l.id', $control_account_ids)
        ->where('l.allow_payments', 1)
        ->where('l.id', '!=', 4)
        ->orderBy('c.id')
        ->orderBy('l.name')->get()->toArray();

    $control_accounts = \DB::table('acc_ledger_accounts as l')
        ->join('acc_cashbook as p', 'l.id', '=', 'p.ledger_account_id')
        ->join('acc_ledger_account_categories as c', 'c.id', '=', 'l.ledger_account_category_id')
        ->select('l.id', \DB::raw('l.name AS name'), 'l.taxable', 'c.category')
        //->where('l.id', '!=', 2)
        ->where('l.id', '!=', 4)
        ->where('p.status', '!=', 'Deleted')
        ->orderBy('c.id')
        ->orderBy('l.name')->get()->toArray();

    $data['accounts'][] = (object) ['id' => 0, 'company' => (string) '', 'type' => (string) ''];

    foreach ($accounts as $a) {
        $data['accounts'][] = (object) ['id' => $a->id, 'company' => (string) $a->company, 'type' => (string) $a->type];
    }
    $data['suppliers'][] = (object) ['id' => 0, 'company' => (string) ''];
    foreach ($suppliers as $a) {
        $data['suppliers'][] = (object) ['id' => $a->id, 'company' => (string) $a->company];
    }
    $data['ledgers'][] = (object) ['id' => 0, 'name' => (string) '', 'taxable' => (string) ''];
    foreach ($ledgers as $a) {
        $data['ledgers'][] = (object) ['id' => $a->id, 'name' => (string) $a->name, 'taxable' => (string) $a->taxable, 'category' => $a->category];
    }

    $data['ledgers_expenses'][] = (object) ['id' => 0, 'name' => (string) '', 'taxable' => (string) ''];
    foreach ($ledgers_expenses as $a) {
        $data['ledgers_expenses'][] = (object) ['id' => $a->id, 'name' => (string) $a->name, 'taxable' => (string) $a->taxable, 'category' => $a->category];
    }

    $data['control_accounts'][] = (object) ['id' => 0, 'name' => (string) '', 'taxable' => (string) ''];
    foreach ($control_accounts as $a) {
        $data['control_accounts'][] = (object) ['id' => $a->id, 'name' => (string) $a->name, 'taxable' => (string) $a->taxable, 'category' => $a->category];
    }

    return view('__app.button_views.cashbook_allocate', $data);
}

function button_cash_register_write_off($request)
{
    //aa($request->all());
    $document_currency = \DB::table('acc_cashbook')->where('id', $request->id)->pluck('currency')->first();
    $status = \DB::table('acc_cashbook')->where('id', $request->id)->pluck('status')->first();
    $cashbook_id = $request->id;
    if ($status != 'Deleted') {
        if ($cashbook_id != 8 && $cashbook_id != 14) {
            return json_alert('Write off is only for cash register');
        }
    }
    $balance = \DB::table('acc_cashbook_transactions')->where('cashbook_id', $cashbook_id)->orderby('docdate', 'desc')->orderby('id', 'desc')->pluck('balance')->first();
    if ($balance == 0) {
        return json_alert('Nothing to process');
    }

    if ($balance < 0) {
        $cash_trx = [
            'doctype' => 'Cashbook Expense',
            'docdate' => date('Y-m-d'),
            'reference' => 'Directors Loan Write off',
            'total' => $balance * -1,
            'ledger_account_id' => 10,
            'cashbook_id' => $cashbook_id,
            'document_currency' => $document_currency,
        ];
        $trx = (new DBEvent())->setTable('acc_cashbook_transactions')->save($cash_trx);

        return json_alert('Allocated to Directors Loan');
    } else {
        $cash_trx = [
            'doctype' => 'Cashbook Expense',
            'docdate' => date('Y-m-d'),
            'reference' => 'Directors Loan Write off',
            'total' => $balance * -1,
            'ledger_account_id' => 10,
            'cashbook_id' => $cashbook_id,
            'document_currency' => $document_currency,
        ];
        $trx = (new DBEvent())->setTable('acc_cashbook_transactions')->save($cash_trx);

        return json_alert('Allocated to Directors Loan');
    }
    cashbook_reconcile($cashbook_id);
}

function aftersave_debitordertransaction_createpayment($request)
{
    // aa($request->debit_order_batch_id);
    if (!empty($request->debit_order_batch_id)) {
        $debit_order_transaction = \DB::table('acc_cashbook_transactions')->where('id', $request->id)->get()->first();
        if ($debit_order_transaction->cashbook_id == 1 && $debit_order_transaction->debit_order_batch_id > 0 && $debit_order_transaction->account_id > 0 && $debit_order_transaction->api_status == 'Complete') {
            $allocated_total = \DB::table('acc_cashbook_transactions')
                ->where('debit_order_batch_id', $request->debit_order_batch_id)
                ->where('api_status', 'Complete')
                ->where('account_id', '>', 0)
                ->sum('total');
            $declined_total = \DB::table('acc_cashbook_transactions')
                ->where('debit_order_batch_id', $request->debit_order_batch_id)
                ->where('api_status', 'Declined')
                ->where('account_id', '>', 0)
                ->sum('total');
            \DB::table('acc_debit_order_batch')->where('id', $request->debit_order_batch_id)->update(['allocated_total' => $allocated_total, 'declined_total' => abs($declined_total)]);
        }
    }
}

function aftersave_payments_convert_quote($request)
{
    if (!empty($request->account_id)) {
        $total = currency($request->total);
        $order = \DB::table('crm_documents')->where('total', $total)->where('docdate', '<=', date('Y-m-d'))->where('billing_type', '')->where('doctype', 'Order')->where('account_id', $request->account_id)->count();
        $quote = \DB::table('crm_documents')->where('total', $total)->where('docdate', '<=', date('Y-m-d'))->where('billing_type', '')->where('doctype', 'Quotation')->where('account_id', $request->account_id)->get()->first();
        if (!empty($quote) && !$order) {
            $quote->doctype = 'Order';
            $quote->product_id = \DB::table('crm_document_lines')->where('document_id', $quote->id)->orderby('product_id')->pluck('product_id')->toArray();
            $quote->qty = \DB::table('crm_document_lines')->where('document_id', $quote->id)->orderby('product_id')->pluck('qty')->toArray();
            $quote->price = \DB::table('crm_document_lines')->where('document_id', $quote->id)->orderby('product_id')->pluck('price')->toArray();
            $quote->full_price = \DB::table('crm_document_lines')->where('document_id', $quote->id)->orderby('product_id')->pluck('full_price')->toArray();
            $result = (new \DBEvent())->setTable('crm_documents')->save($quote);
            if (!is_array($result) || empty($result['id'])) {
                return $result;
            }
        }
    }
}

function aftersave_send_receipt_process_orders($request)
{
    if (!empty($request->account_id) && !empty($request->approved)) {
        $account = dbgetaccount($request->account_id);
        if ($account->partner_id == 1) {
            $function_variables = get_defined_vars();
            $data['function_name'] = __FUNCTION__;
            $data['attach_statement'] = true;
            erp_process_notification($request->account_id, $data, $function_variables);
        }
    }
}

function schedule_cashbooks_reconcile()
{
    $cashbooks = \DB::table('acc_cashbook')->where('status', 'Enabled')->get();
    foreach ($cashbooks as $cashbook) {
        cashbook_reconcile($cashbook->id);
    }
}

function cashbook_reconcile($cashbook_id)
{
    $update_data = [];
    \DB::table('acc_cashbook_transactions')->where('account_id', '')->update(['account_id' => null]);
    \DB::table('acc_cashbook_transactions')->where('ledger_account_id', '')->update(['ledger_account_id' => null]);
    \DB::table('acc_cashbook_transactions')->where('supplier_id', '')->update(['supplier_id' => null]);
    \DB::table('acc_cashbook_transactions')->where('account_id', 0)->update(['account_id' => null]);
    \DB::table('acc_cashbook_transactions')->where('ledger_account_id', 0)->update(['ledger_account_id' => null]);
    \DB::table('acc_cashbook_transactions')->where('supplier_id', 0)->update(['supplier_id' => null]);

    if ($cashbook_id == 1) {
        $check_date = date('Y-m-d', strtotime('-1 day'));
    } else {
        $check_date = date('Y-m-d');
    }

    // $cashbook_yodlee_account_id = \DB::table('acc_cashbook')->where('id',$cashbook_id)->pluck('yodlee_account_id')->first();

    $records = get_cashbook_balance_records($cashbook_id);

    foreach ($records as $record) {
        $balance = $record->running_total;
        if ($record->doctype == 'General Journal') {
            \DB::table('acc_general_journals')->where('id', $record->id)->update(['register_balance' => $balance]);
        } else {
            \DB::table('acc_cashbook_transactions')->where('id', $record->id)->update(['balance' => $balance]);
        }
    }

    $update_data['balance'] = $balance;
    $reconciled = 0;
    // \DB::table('acc_cashbook_transactions')
    // ->where('cashbook_id',$cashbook_id)
    // ->update(['reconciled'=>0]);

    $transactions = \DB::table('acc_cashbook_transactions')
    ->select('id', 'docdate', 'api_balance')
    ->where('cashbook_id', $cashbook_id)
    ->where('docdate', '<=', $check_date)
    ->orderBy('docdate', 'desc')->orderBy('id', 'desc')
    ->get();

    foreach ($transactions as $trx) {
        $reconciled_transaction = \DB::table('acc_cashbook_transactions')
        ->select('id', 'docdate')
        ->where('cashbook_id', $cashbook_id)
        ->where('balance', $trx->api_balance)
        ->where('docdate', $trx->docdate)
        ->orderBy('docdate', 'desc')->orderBy('id', 'desc')
        ->get()->first();
        if ($reconciled_transaction) {
            break;
        }
    }

    if ($reconciled_transaction && $reconciled_transaction->id) {
        \DB::table('acc_cashbook_transactions')
        ->where('cashbook_id', $cashbook_id)
        ->where('docdate', '<=', $reconciled_transaction->docdate)
        ->update(['reconciled' => 1]);
    }

    $reconciled = \DB::table('acc_cashbook_transactions')
    ->select('reconciled')
    ->where('cashbook_id', $cashbook_id)
    ->where('docdate', '<=', $check_date)
    ->orderBy('docdate', 'desc')->orderBy('id', 'desc')
    ->pluck('reconciled')->first();

    //Reconcile is cashbook has no transactions
    if (!$reconciled) {
        $trx_count = \DB::table('acc_cashbook_transactions')
        ->where('cashbook_id', $cashbook_id)
        ->count();
        if (!$trx_count) {
            $reconciled = 1;
        }
    }

    $update_data['reconciled'] = $reconciled;
    $unallocated_transactions = \DB::table('acc_cashbook_transactions')
    ->where('cashbook_id', $cashbook_id)
    ->whereNull('account_id')
    ->whereNull('ledger_account_id')
    ->whereNull('supplier_id')
    ->count();

    $cashbook_always_reconciled = \DB::table('acc_cashbook')->where('id', $cashbook_id)->pluck('always_reconciled')->first();
    $allocated_reconciled = 1;
    if ($unallocated_transactions || (!$cashbook_always_reconciled && !$reconciled)) {
        $allocated_reconciled = 0;
    }
    $update_data['allocated_reconciled'] = $allocated_reconciled;

    if ($reconciled) {
        $last_reconcile_date = date('Y-m-d H:i:s');
        // if($cashbook_yodlee_account_id > 0){
        //     $last_reconcile_date = \DB::table('acc_yodlee_accounts')->where('id',$cashbook_yodlee_account_id)->pluck('last_updated')->first();
        // }
        $update_data['last_reconcile_date'] = $last_reconcile_date;
    }

    $ledger_account_id = \DB::table('acc_cashbook')->where('id', $cashbook_id)->pluck('ledger_account_id')->first();
    $first_docdate = \DB::table('acc_cashbook_transactions')->where('cashbook_id', $cashbook_id)->orderby('docdate', 'asc')->pluck('docdate')->first();

    $debit_balance_query = \DB::table('acc_general_journals as aj')
    ->join('acc_general_journal_transactions as ajt', 'aj.transaction_id', '=', 'ajt.id')
    ->where('ledger_account_id', $ledger_account_id)
    ->where('debit_amount', '>', 0);

    $credit_balance_query = \DB::table('acc_general_journals as aj')
    ->join('acc_general_journal_transactions as ajt', 'aj.transaction_id', '=', 'ajt.id')
    ->where('ledger_account_id', $ledger_account_id)
    ->where('credit_amount', '>', 0);

    if ($first_docdate) {
        $debit_balance = $debit_balance_query->where('ajt.docdate', '<', $first_docdate)->sum('debit_amount');
        $credit_balance = $credit_balance_query->where('ajt.docdate', '<', $first_docdate)->sum('credit_amount');
    } else {
        $debit_balance = $debit_balance_query->sum('debit_amount');
        $credit_balance = $credit_balance_query->sum('credit_amount');
    }

    $opening_balance = $debit_balance - $credit_balance;
    $update_data['opening_balance'] = $opening_balance;

    $cashbook_ledger_account_id = \DB::table('acc_cashbook')->where('id', $cashbook_id)->pluck('ledger_account_id')->first();
    $update_data['last_payout'] = \DB::table('acc_cashbook_transactions')->where('control_account_id', $cashbook_ledger_account_id)->orderBy('docdate', 'desc')->pluck('total')->first();

    if ($cashbook_id == 8 || $cashbook_id == 14) {
        \DB::table('acc_cashbook_transactions')->where('cashbook_id', $cashbook_id)->update(['reconciled' => 1]);
        if ($cashbook_id == 14) {
            \DB::table('acc_cashbook_transactions')->where('cashbook_id', $cashbook_id)->update(['document_currency' => 'USD']);
        }
        $update_data['allocated_reconciled'] = 1;
        $update_data['reconciled'] = 1;
    }

    if ($cashbook_id == 1) {
        $latest_debit_order_batch = \DB::table('acc_debit_order_batch')->orderBy('id', 'desc')->pluck('action_date')->first();

        if (date('Y-m-d') <= date('Y-m-d', strtotime($latest_debit_order_batch))) {
            $update_data['reconciled'] = 1;
            $update_data['allocated_reconciled'] = 1;
        } else {
            unset($update_data['reconciled']);
            unset($update_data['allocated_reconciled']);
        }
    }
    \DB::table('acc_cashbook')->where('id', $cashbook_id)->update($update_data);
    \DB::table('acc_cashbook')->where('id', $cashbook_id)->where('always_reconciled', 1)->update(['reconciled' => 1]);
}

function get_cashbook_balance_records($cashbook_id)
{
    try {
        $ledger_account_id = \DB::table('acc_cashbook')->where('id', $cashbook_id)->pluck('ledger_account_id')->first();

        $journal_transactions = \DB::table('acc_general_journals')->where('ledger_account_id', $ledger_account_id)->count();

        if ($journal_transactions > 0) {
            $sql = ' select total, doctype, docdate, acc_cashbook_transactions.id, @running_total:=@running_total + total AS running_total from
            (
            select  total*-1 as total, doctype, docdate, id from acc_cashbook_transactions where (ledger_account_id > 0 || account_id > 0 || supplier_id > 0) and doctype="Cashbook Control Payment" and api_status!="Invalid" and api_status!="Debit Order Declined Fee" and cashbook_id='.$cashbook_id.'
            UNION ALL
            select  total, doctype, docdate, id from acc_cashbook_transactions where (ledger_account_id > 0 || account_id > 0 || supplier_id > 0) and doctype!="Cashbook Control Payment" and api_status!="Invalid" and api_status!="Debit Order Declined Fee" and cashbook_id='.$cashbook_id.'
            UNION ALL
            select  0 as total, doctype, docdate, id from acc_cashbook_transactions where (ledger_account_id > 0 || account_id > 0 || supplier_id > 0) and doctype!="Cashbook Control Payment" and api_status="Debit Order Declined Fee" and cashbook_id='.$cashbook_id.'
            UNION ALL
            select   debit_amount as total, "General Journal" as doctype, ajt.docdate, aj.id from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
            where ledger_account_id='.$ledger_account_id.' and debit_amount > 0
            UNION ALL
            select   credit_amount*-1 as total, "General Journal" as doctype, ajt.docdate, aj.id from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
            where ledger_account_id='.$ledger_account_id.' and credit_amount > 0
            ) acc_cashbook_transactions
            JOIN (SELECT @running_total := 0 AS tmpvar) tmpvar';
        } else {
            $sql = ' select total, doctype, docdate, acc_cashbook_transactions.id, @running_total:=@running_total + total AS running_total from
            (
            select  total*-1 as total, doctype, docdate, id from acc_cashbook_transactions where (ledger_account_id > 0 || account_id > 0 || supplier_id > 0) and doctype="Cashbook Control Payment" and api_status!="Invalid" and api_status!="Debit Order Declined Fee" and cashbook_id='.$cashbook_id.'
            UNION ALL
            select  total, doctype, docdate, id from acc_cashbook_transactions where (ledger_account_id > 0 || account_id > 0 || supplier_id > 0) and doctype!="Cashbook Control Payment" and api_status!="Invalid" and api_status!="Debit Order Declined Fee" and cashbook_id='.$cashbook_id.'
            UNION ALL
            select  0 as total, doctype, docdate, id from acc_cashbook_transactions where (ledger_account_id > 0 || account_id > 0 || supplier_id > 0) and doctype!="Cashbook Control Payment" and api_status="Debit Order Declined Fee" and cashbook_id='.$cashbook_id.'
            ) acc_cashbook_transactions
            JOIN (SELECT @running_total := 0 AS tmpvar) tmpvar';
        }

        $sql .= ' where docdate<="'.date('Y-m-d').'" order by docdate,id ';

        $records = \DB::select($sql);
    } catch (\Throwable $ex) {
        exception_log($ex);
        Log::Debug($error);
        $records = [];
    }

    return $records;
}