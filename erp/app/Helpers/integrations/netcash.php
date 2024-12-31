<?php

function schedule_debit_orders_set_batch_totals()
{
    $enabled = get_admin_setting('enable_debit_orders');
    if (! $enabled) {
        return false;
    }
    $batches = \DB::table('acc_debit_order_batch')->get();
    foreach ($batches as $batch) {
        $declined_total = \DB::table('acc_cashbook_transactions')
            ->where('debit_order_batch_id', $batch->id)
            ->where('api_status', 'Declined')
            ->where('account_id', '>', 0)
            ->sum('total');
        $allocated_total = \DB::table('acc_cashbook_transactions')
            ->where('debit_order_batch_id', $batch->id)
            ->where('api_status', 'Complete')
            ->where('account_id', '>', 0)
            ->sum('total');
        \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update(['declined_total' => abs($declined_total), 'allocated_total' => $allocated_total]);
    }
}

function schedule_netcash_statement_get_polling_ids()
{
    $enabled = get_admin_setting('enable_debit_orders');
    if (! $enabled) {
        return false;
    }
    $netcash = new NetCash;
    $from_date = date('Y-m-d', strtotime('-1 week'));
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    while ($from_date <= $yesterday) {
        $exists = \DB::table('acc_netcash_statement')->where('statement_date', $from_date)->where('polling_id', '>', '')->count();
        if (! $exists) {
            $polling_id = $netcash->getStatementPollingId($from_date);

            if ($polling_id && is_numeric($polling_id)) {
                \DB::table('acc_netcash_statement')->insert(['statement_date' => $from_date, 'polling_id' => $polling_id]);
            }
        }
        $from_date = date('Y-m-d', strtotime($from_date.'+1 day'));
    }
}

function schedule_netcash_statement_get_statement()
{
    $enabled = get_admin_setting('enable_debit_orders');
    if (! $enabled) {
        return false;
    }

    $netcash = new NetCash;
    \DB::table('acc_netcash_statement')->where('statement', 'FILE NOT READY')->update(['statement' => '']);
    $statements = \DB::table('acc_netcash_statement')->where('statement', '')->where('polling_id', '>', '')->get();
    foreach ($statements as $statement) {
        if (! empty($statement->polling_id) && is_numeric($statement->polling_id)) {
            $result = $netcash->retrieveStatement($statement->polling_id);
            $statement_result = $result->RetrieveMerchantStatementResult;

            $data = ['statement' => $statement_result];
            if (str_contains($statement_result, 'DRU') || str_contains($statement_result, 'DCU')) {
                $data['has_unpaids'] = 1;
            }
            \DB::table('acc_netcash_statement')->where('id', $statement->id)->update($data);
        }
    }

    \DB::table('acc_netcash_statement')->update(['has_transactions' => 0]);
    $statements = \DB::table('acc_netcash_statement')->where('statement', '>', '')->where('polling_id', '>', '')->get();
    foreach ($statements as $statement) {
        $file = trim($statement->statement);
        $lines = explode(PHP_EOL, $file);

        if (count($lines) > 2) {
            \DB::table('acc_netcash_statement')->where('id', $statement->id)->update(['has_transactions' => 1]);
        }
    }

    // process unpaids
    $unpaids = \DB::table('acc_netcash_statement')->where('has_unpaids', 1)->where('unpaids_processed', 0)->get();
    foreach ($unpaids as $unpaid) {
        $file = trim($unpaid->statement);
        $lines = explode(PHP_EOL, $file);
        $transactions_processed = false;
        foreach ($lines as $line) {
            $transaction = explode("\t", $line);
            if ($transaction[1] == 'DRU' || $transaction[1] == 'DCU') {
                $transaction_date = $transaction[0];
                $reference_arr = explode('-', $transaction[3]);
                $account_id = trim($reference_arr[0]);
                $total = $transaction[4];

                $transaction_id = \DB::table('acc_cashbook_transactions')
                    ->where('cashbook_id', 1)
                    ->where('total', $total)
                    ->where('account_id', $account_id)
                    ->where('docdate', '<', $transaction_date)
                    ->where('docdate', '>=', date('Y-m-d', strtotime($transaction_date.'- 6 weeks')))
                    ->pluck('id')->first();

                if (empty($transaction_id)) {
                    $transactions_processed = false;
                    debug_email('Unpaid debit order could not be declined, ID:'.$unpaid->id);
                } else {
                    $transactions_processed = true;
                    \DB::table('acc_cashbook_transactions')->where('id', $transaction_id)->update(['api_status' => 'Declined']);
                    process_account_declined_debit_order($account_id);
                    (new DBEvent)->setAccountAging($account_id);
                }
            }
        }
        if ($transactions_processed) {
            \DB::table('acc_netcash_statement')->where('id', $unpaid->id)->update(['unpaids_processed' => 1]);
        }
    }
    create_declined_debit_order_transactions();

    // CREATE CASHBOOK FEES
    $transaction_codes = [
        'TDD',
        'TDC',
        'DRU',
        'SDD',
        'SDC',
        'DCU',
        'OBL',
        'CBL',
        'BTR',
        'ABR',
    ];
    $trxs = \DB::table('acc_netcash_statement')->where('has_transactions', 1)->orderBy('id', 'desc')->limit(2)->get();

    foreach ($trxs as $trx) {

        if ($trx->statement > '') {

            $lines = explode(PHP_EOL, $trx->statement);

            $transactions_processed = false;
            foreach ($lines as $line) {

                $transaction = explode("\t", $line);

                $code = $transaction[1];

                if (! empty($transaction[0]) && ! in_array($code, $transaction_codes)) {

                    $data = [
                        'docdate' => $transaction[0],
                        'doctype' => 'Cashbook Fee',
                        'total' => $transaction[4],
                        'reference' => $transaction[1].' '.$transaction[3],
                        'api_status' => 'Complete',
                        'cashbook_id' => 1,
                        'ledger_account_id' => 22,
                    ];
                    if ($transaction[5] == '-') {
                        $data['total'] = currency(abs($data['total']) * -1);
                    }

                    $exists = \DB::table('acc_cashbook_transactions')
                        //->where('total', $data['total'])
                        ->where('reference', $data['reference'])
                        ->where('docdate', $data['docdate'])
                        ->where('cashbook_id', $data['cashbook_id'])
                        ->count();

                    if (! $exists) {

                        \DB::table('acc_cashbook_transactions')->insert($data);
                    }
                }
            }
        }
    }

    // RECONCILE
    $last_batch = \DB::table('acc_debit_order_batch')->orderBy('id', 'desc')->get()->first();
    if ($last_batch->id && $last_batch->action_date) {
        \DB::table('acc_cashbook_transactions')->whereNull('docdate')->where('debit_order_batch_id', $last_batch->id)->where('cashbook_id', 1)->update(['docdate' => date('Y-m-d', strtotime($last_batch->action_date))]);
    }

    $trxs = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 1)->where('reconciled', 0)->orderBy('docdate', 'asc')->get();

    foreach ($trxs as $trx) {
        $polling_date = date('Y-m-d', strtotime($trx->docdate));
        $statement = \DB::table('acc_netcash_statement')->where('statement_date', $polling_date)->where('polling_id', '>', '')->get()->first();
        if ($statement->statement > '') {
            $lines = explode(PHP_EOL, $statement->statement);
            $transactions_processed = false;
            foreach ($lines as $line) {
                $transaction = explode("\t", $line);
                if ($transaction[1] == 'CBL') {
                    $closing_balance = $transaction[4];
                }
            }
            \DB::table('acc_cashbook_transactions')->where('docdate', $polling_date)->where('cashbook_id', 1)->update(['api_balance' => $closing_balance]);
        }
    }

    cashbook_reconcile(1);

}

function process_account_declined_debit_order($account_id)
{

    \DB::table('acc_debit_orders')->where('account_id', $account_id)->update(['status' => 'Disabled']);
    \DB::table('crm_accounts')->where('id', $account_id)->update(['payment_method' => 'Bank']);

    $service_account_ids = [$account_id];
    $account = dbgetaccount($account_id);
    if ($account->type == 'reseller') {
        $service_account_ids = \DB::table('crm_accounts')->where('partner_id', $account_id)->pluck('id')->toArray();
    }
    \DB::table('sub_services')->whereIn('account_id', $service_account_ids)->update(['contract_period' => 0]);
    $sub = new ErpSubs;
    foreach ($service_account_ids as $service_account_id) {
        $sub->updateProductPricesByAccount($service_account_id);
    }
}

function button_netcash_statement_unpaid_codes($request)
{
    return view('__app.button_views.netcash_unpaid_codes');
}

function aftersave_debitorders_set_payment_method($request)
{
    if ($request->status == 'Enabled') {
        \DB::table('crm_accounts')->where('id', $request->account_id)->update(['payment_method' => 'Debit Order']);
        \DB::table('sub_services')->where('account_id', $request->account_id)->where('status', '!=', 'Deleted')->update(['contract_period' => 12]);
    }

    if ($request->status == 'Disabled' || $request->status == 'Deleted') {
        \DB::table('crm_accounts')->where('id', $request->account_id)->update(['payment_method' => 'Bank']);
        (new DBEvent)->setAccountAging($request->account_id);
    }
    $account = dbgetaccount($request->account_id);
    if ($account->type == 'reseller_user') {
        \DB::table('acc_debit_orders')->where('id', $request->id)->update(['account_id' => $account->partner_id]);
    }
}

function aftersave_set_debit_order_balance($request) {}

function button_debit_order_transactions_decline($request)
{
    $db = new DBEvent;
    $debit_order_transaction = \DB::table('acc_cashbook_transactions')->where('id', $request->id)->get()->first();
    if ($debit_order_transaction->account_id > 0) {
        \DB::table('acc_cashbook_transactions')->where('id', $request->id)->update(['api_status' => 'Declined']);
    }
    $allocated_total = \DB::table('acc_cashbook_transactions')
        ->where('debit_order_batch_id', $request->debit_order_batch_id)
        ->where('account_id', '>', 0)
        ->where('api_status', 'Complete')
        ->sum('total');
    $declined_total = \DB::table('acc_cashbook_transactions')
        ->where('debit_order_batch_id', $request->debit_order_batch_id)
        ->where('account_id', '>', 0)
        ->where('api_status', 'Declined')
        ->sum('total');
    \DB::table('acc_debit_order_batch')->where('id', $request->debit_order_batch_id)->update(['allocated_total' => $allocated_total, 'declined_total' => $declined_total]);
}

function create_declined_debit_order_transactions()
{
    $declined_transactions_payments = \DB::table('acc_cashbook_transactions')->where('debit_order_batch_id', '>', 0)->where('api_status', 'Declined')->get();
    $account_ids_balances = [];
    $ledger_ids = [];

    foreach ($declined_transactions_payments as $debit_order_transaction) {

        $ledger_ids[] = $debit_order_transaction->id;
        $account_ids_balances[] = $debit_order_transaction->account_id;
    }

    $declined_transactions = \DB::table('acc_cashbook_transactions')->where('debit_order_batch_id', '>', 0)->where('api_status', 'Declined')->get();

    foreach ($declined_transactions as $declined_transaction) {
        $reversal_exists = \DB::table('acc_cashbook_transactions')
            ->where('debit_order_batch_id', $declined_transaction->debit_order_batch_id)
            ->where('account_id', $declined_transaction->account_id)
            ->where('api_status', 'Debit Order Declined')->count();
        $reversal_fee_exists = \DB::table('acc_cashbook_transactions')
            ->where('debit_order_batch_id', $declined_transaction->debit_order_batch_id)
            ->where('account_id', $declined_transaction->account_id)
            ->where('api_status', 'Debit Order Declined Fee')->count();

        if (! $reversal_exists) {

            $data = (array) $declined_transaction;
            unset($data['id']);
            $data['api_status'] = 'Debit Order Declined';

            $data['total'] = currency(($declined_transaction->total) * -1);
            $data['doctype'] = 'Cashbook Customer Receipt';
            $trx_id = \DB::table('acc_cashbook_transactions')->insertGetId($data);
            $ledger_ids[] = $trx_id;
            $account_ids_balances[] = $declined_transaction->account_id;
        }

        if (! $reversal_fee_exists) {

            $data = (array) $declined_transaction;
            unset($data['id']);
            $data['api_status'] = 'Debit Order Declined Fee';
            $data['reference'] = 'Debit Order Declined Fee';

            $data['doctype'] = 'Cashbook Customer Receipt';
            $data['total'] = -50;
            $trx_id = \DB::table('acc_cashbook_transactions')->insertGetId($data);
            $ledger_ids[] = $trx_id;
            \DB::table('acc_debit_orders')->where('account_id', $declined_transaction->account_id)->update(['status' => 'Deleted']);
            $account_ids_balances[] = $declined_transaction->account_id;
        }
    }

    if (count($account_ids_balances) > 0) {
        $account_ids_balances = collect($account_ids_balances)->unique()->toArray();
        foreach ($account_ids_balances as $account_id) {
            (new DBEvent)->setAccountAging($account_id, 1);
        }
    }
    if (count($ledger_ids) > 0) {
        $db = new DBEvent;
        foreach ($ledger_ids as $ledger_id) {
            $db->setTable('acc_cashbook_transactions')->postDocument($ledger_id);
        }
        $db->postDocumentCommit();
    }
}

function button_debit_orders_validate_bank_details($request)
{
    sleep(2);
    $debit_order = \DB::table('acc_debit_orders')->where('id', $request->id)->get()->first();
    $netcash = new \NetCash;
    $response = $netcash->validateBankAccount($debit_order);

    \DB::table('acc_debit_orders')->where('id', $request->id)->update($response);
    if ($response['validated']) {
        return json_alert($response['validate_message']);
    } else {
        return json_alert($response['validate_message'], 'warning');
    }
}

function button_debit_orders_create($request)
{
    $account_id = \DB::table('acc_debit_orders')->where('id', $request->id)->pluck('account_id')->first();
    create_single_debit_order($account_id);

    return json_alert('Debit order created');
}

function button_debit_order_batch_create($request)
{
    return view('__app.button_views.debitorders');
}

function button_debit_order_batch_generate($request)
{
    $batch_date = date('YmdHi');

    $action_date = date('Y-m-d', strtotime('monday next week'));
    $action_date_days = date('Y-m-d', strtotime('+5 days'));
    if ($action_date_days > $action_date) {
        $action_date = $action_date_days;
    }

    while ($action_date < date('Y-m-t')) {
        $action_date = date('Y-m-d', strtotime('monday next week', strtotime($action_date)));
    }

    // $action_date = '2020-11-11';
    $netcash = new \NetCash($batch_date);
    // $netcash->setAmountLimit(2000);
    // $netcash->setAccountLimit(300071);
    $netcash->setActionDate($action_date, 'Twoday');
    // $netcash->setActionDate($action_date, 'Sameday');
    $storage_file = date('YmdHi').'.txt';
    $total = $netcash->generate($storage_file);
    $invoice_amount = currency($total / 100);

    $batch = \Storage::disk('debit_orders')->get($storage_file);
    $batch = [
        'batch' => $batch,
        'batch_file' => $storage_file,
        'action_date' => date('Ymd', strtotime($action_date)),
        'created_at' => date('Y-m-d H:i:s', strtotime($batch_date)),
        'total' => $total / 100,
    ];
    \DB::table('acc_debit_order_batch')->insert($batch);

    return json_alert('Debit Orders generated.');
}

function button_debit_order_batch_upload($request)
{
    $batch = \DB::table('acc_debit_order_batch')->where('id', $request->id)->get()->first();
    if ($batch->uploaded) {
        return json_alert('Batch already uploaded.', 'warning');
    }
    $batch_name_arr = explode('.', $batch->batch_file);
    $batch_name = $batch_name_arr[0];
    $netcash = new \NetCash($batch_name);
    $netcash->upload();
    $result_file = $batch_name.'result.txt';
    $result_token = \Storage::disk('debit_orders')->get($result_file);
    $update = [
        'result_file' => $result_file,
        'result_token' => $result_token,
        'uploaded' => 1,
    ];

    \DB::table('acc_debit_order_batch')->where('id', $request->id)->update($update);

    return json_alert('Batch uploaded.');
}

function button_debit_order_batch_report($request)
{
    $batch = \DB::table('acc_debit_order_batch')->where('id', $request->id)->get()->first();
    if (! empty($batch->result) && $batch->result != 'FILE NOT READY') {
        return json_alert('Batch report already generated.', 'warning');
    }
    $batch_name_arr = explode('.', $batch->batch_file);
    $batch_name = $batch_name_arr[0];
    $netcash = new \NetCash($batch_name);
    $result = $netcash->report();

    \DB::table('acc_debit_order_batch')->where('id', $request->id)->update(['result' => $result->RequestFileUploadReportResult]);
    $batch = \DB::table('acc_debit_order_batch')->where('id', $request->id)->get()->first();
    if (str_contains($batch->result, 'SUCCESSFUL') && ! str_contains($batch->result, 'UNSUCCESSFUL')) {
        $complete = $netcash->createTransactions($batch->id);
        if ($complete) {
            return json_alert('Transactions Created.');
        } else {
            return json_alert('Transactions already created.', 'warning');
        }
    }

    return json_alert('Batch report updated.');
}

function button_debit_order_batch_authorise($request)
{
    $batch = \DB::table('acc_debit_order_batch')->where('id', $request->id)->get()->first();
    if (! empty($batch->authorise_result) && $batch->authorise_result == 'Debit order autorise') {
        return json_alert('Debit order already authorised.', 'warning');
    }
    $batch_name_arr = explode('.', $batch->batch_file);
    $batch_name = $batch_name_arr[0];
    $netcash = new \NetCash($batch_name);

    $result = $netcash->authoriseBatch($request->id);
    \DB::table('acc_debit_order_batch')->where('id', $request->id)->update(['authorise_result' => $result]);
    if ($result == 'Debit order authorised') {
        return json_alert($result);
    } else {
        return json_alert($result, 'error');
    }
}

function beforedelete_debitorderbatch_check_upload($request)
{
    $processed = \DB::table('acc_debit_order_batch')->where('id', $request->id)->where('result', 'like', '% SUCCESSFUL%')->count();
    if ($processed) {
        return 'Processed debit orders cannot be deleted.';
    }
    $debit_order_transactions = \DB::table('acc_cashbook_transactions')->where('debit_order_batch_id', $request->id)->count();
    if ($debit_order_transactions > 0) {
        return 'Processed debit orders cannot be deleted, Transactions already created.';
    }
}

function beforesave_debitorders_account_unique($request)
{
    if (! empty($request->id)) {
        $exists = \DB::table('acc_debit_orders')->where('id', '!=', $request->id)->where('account_id', $request->account_id)->where('status', 'Enabled')->count();
    } elseif (! empty($request->account_id)) {
        $exists = \DB::table('acc_debit_orders')->where('account_id', $request->account_id)->where('status', 'Enabled')->count();
    }
    if ($exists) {
        return 'Debit order accounts needs to be unique.';
    }

    if (empty($request->id_number)) {
        return 'ID Number required';
    }
    $id_verified = verify_za_id_number($request->id_number);
    if (! $id_verified) {
        return 'Please enter a valid South African ID Number';
    }
}

function aftersave_debitorders_set_branch_code($request)
{
    if ($request->bank_branch == 'Absa Bank') {
        $bank_branch_code = '632005';
    } elseif ($request->bank_branch == 'Capitec Bank') {
        $bank_branch_code = '470010';
    } elseif ($request->bank_branch == 'First National Bank') {
        $bank_branch_code = '250655';
    } elseif ($request->bank_branch == 'Investec Bank') {
        $bank_branch_code = '580105';
    } elseif ($request->bank_branch == 'Nedbank') {
        $bank_branch_code = '198765';
    } elseif ($request->bank_branch == 'Nedbank Corporate') {
        $bank_branch_code = '720026';
    } elseif ($request->bank_branch == 'Postbank') {
        $bank_branch_code = '460005';
    } elseif ($request->bank_branch == 'Standard Bank') {
        $bank_branch_code = '051001';
    } elseif ($request->bank_branch == 'Sasfin Bank') {
        $bank_branch_code = '683000';
    }

    $account_type = 'Current';
    if ($request->bank_branch == 'Postbank') {
        $account_type = 'Savings';
    }

    if (str_contains($request->bank_branch, 'Nedbank')) {
        if (str_starts_with($request->bank_account_number, 1)) {
            $request->bank_branch == 'Nedbank';
            $bank_branch_code = '198765';
        }
        if (str_starts_with($request->bank_account_number, 2)) {
            $request->bank_branch == 'Nedbank';
            $account_type = 'Savings';
            $bank_branch_code = '198765';
        }
        if (str_starts_with($request->bank_account_number, 9)) {
            $request->bank_branch == 'Nedbank Corporate';
            $account_type = 'Savings';
            $bank_branch_code = '720026';
        }
    }
    $data = [
        'bank_branch_code' => $bank_branch_code,
        'bank_branch' => $request->bank_branch,
        'bank_account_type' => $account_type,
    ];

    \DB::table('acc_debit_orders')->where('id', $request->id)->update($data);
    $debit_order = \DB::table('acc_debit_orders')->where('id', $request->id)->get()->first();
    $netcash = new \NetCash;
    $response = $netcash->validateBankAccount($debit_order);

    \DB::table('acc_debit_orders')->where('id', $request->id)->update($response);
}

function process_monthly_debit_orders()
{
    generate_monthly_debit_orders();
}

function generate_monthly_debit_orders()
{

    $enabled = get_admin_setting('enable_debit_orders');
    if (! $enabled) {
        return false;
    }
    //return false;
    // schedule_voice_monthly monthly 1st 6:40
    // schedule_monthly_billing  monthly 1st 7:20
    // schedule to run on the monthly 1st after billing and call profits 8:35
    // generate batch -> upload batch -> get batch result
    // if batch fails, delete batch set different action date and repeat process
    if (empty(session('debit_order_retry_count'))) {
        session(['debit_order_retry_count' => 0]);
    }

    $today = date('Y-m-d');
    $batch_exists = \DB::table('acc_debit_order_batch')->where('created_at', 'LIKE', $today.'%')->count();
    if ($batch_exists) {
        // check batch status
        $batch = \DB::table('acc_debit_order_batch')->where('created_at', 'LIKE', $today.'%')->get()->first();
        if (! $batch->uploaded) {
            // upload batch
            $batch_name_arr = explode('.', $batch->batch_file);
            $batch_name = $batch_name_arr[0];
            $netcash = new \NetCash($batch_name);
            $netcash->upload();
            $result_file = $batch_name.'result.txt';
            $result_token = \Storage::disk('debit_orders')->get($result_file);
            $update = [
                'result_file' => $result_file,
                'result_token' => $result_token,
                'uploaded' => 1,
            ];

            \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update($update);
            sleep(1);
            generate_monthly_debit_orders();
        } else {
            // get batch result
            $batch_name_arr = explode('.', $batch->batch_file);
            $batch_name = $batch_name_arr[0];
            $netcash = new \NetCash($batch_name);
            $result = $netcash->report();

            $batch_result = $result->RequestFileUploadReportResult;

            \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update(['result' => $batch_result]);
            if ($batch_result == 'FILE NOT READY') {
                sleep(1);
                generate_monthly_debit_orders();
            } elseif (str_contains($batch_result, 'UNSUCCESSFUL')) {

                // invalid action date, delete batch, create new batch with different action date
                // retry to create batch maximum 5 times
                if (session('debit_order_retry_count') < 5) {
                    $retry_count = session('debit_order_retry_count') + 1;
                    $action_date = date('Y-m-d', strtotime(session('debit_order_action_date').' + 1 day'));
                    session(['debit_order_retry_count' => $retry_count]);
                    session(['debit_order_action_date' => $action_date]);
                    \DB::table('acc_debit_order_batch')->where('id', $batch->id)->delete();
                    \Storage::disk('debit_orders')->delete($batch->batch_file);
                    generate_monthly_debit_orders();
                } else {
                    dev_email('Debit Order process failed. Please process debit orders manually');
                    admin_email('Debit Order process failed. Please process debit orders manually');
                }
            } elseif (str_contains($batch_result, 'SUCCESSFUL')) {
                // batch success, end process
                $netcash->createTransactions($batch->id);
                \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update(['result' => $batch_result]);
                $authorise_result = $netcash->authoriseBatch($batch->id);
                \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update(['authorise_result' => $authorise_result]);

                debit_order_created_email($batch->id);
                $account_ids = \DB::table('acc_debit_orders')->pluck('account_id')->toArray();
                foreach ($account_ids as $account_id) {
                    (new DBEvent)->setAccountAging($account_id);
                }
            }
        }
    } else {

        if (empty(session('debit_order_action_date'))) {
            if (date('d') > 25) {
                $action_date = date('Y-m-01', strtotime('next month'));
            } else {
                $action_date = date('Y-m-d', strtotime('+ 2 days'));
            }

            session(['debit_order_action_date' => $action_date]);
        }
        // batch does not exists create a new one
        $batch_date = date('YmdHi');
        $action_date = session('debit_order_action_date');

        $netcash = new \NetCash($batch_date);
        $netcash->setActionDate($action_date, 'Twoday');
        $storage_file = date('YmdHi').'.txt';
        $total = $netcash->generate($storage_file);
        $invoice_amount = currency($total / 100);

        $batch = \Storage::disk('debit_orders')->get($storage_file);
        $batch = [
            'batch' => $batch,
            'batch_file' => $storage_file,
            'action_date' => date('Ymd', strtotime($action_date)),
            'created_at' => date('Y-m-d H:i:s', strtotime($batch_date)),
            'total' => $total / 100,
        ];
        \DB::table('acc_debit_order_batch')->insert($batch);
        generate_monthly_debit_orders();
    }
}

function create_single_debit_order($account_id, $retry_date = false)
{
    if (! $retry_date) {
        session(['debit_order_retry_count' => 0]);
    }
    // schedule_voice_monthly monthly 1st 6:40
    // schedule_monthly_billing  monthly 1st 7:20
    // schedule to run on the monthly 1st after billing and call profits 8:35
    // generate batch -> upload batch -> get batch result
    // if batch fails, delete batch set different action date and repeat process
    $action_date = date('Y-m-d', strtotime('+ 3 days'));
    if ($retry_date) {
        $action_date = $retry_date;
    }

    // batch does not exists create a new one
    $batch_date = date('YmdHi');

    $netcash = new \NetCash($batch_date);
    $netcash->setActionDate($action_date, 'Twoday');
    $netcash->setAccountLimit($account_id);
    $storage_file = date('YmdHi').'.txt';
    $total = $netcash->generate($storage_file);

    if ($total > 0) {
        $batch = \Storage::disk('debit_orders')->get($storage_file);
        $batch = [
            'batch' => $batch,
            'batch_file' => $storage_file,
            'action_date' => date('Ymd', strtotime($action_date)),
            'limit_account_id' => $account_id,
            'created_at' => date('Y-m-d H:i:s', strtotime($batch_date)),
            'total' => $total / 100,
        ];
        $batch_id = \DB::table('acc_debit_order_batch')->insertGetId($batch);
        authorize_single_debit_order($batch_id);
    }
}

function authorize_single_debit_order($batch_id)
{
    $today = date('Y-m-d');
    $batch_exists = \DB::table('acc_debit_order_batch')->where('id', $batch_id)->count();
    if ($batch_exists) {
        // check batch status
        $batch = \DB::table('acc_debit_order_batch')->where('id', $batch_id)->get()->first();

        $batch_name_arr = explode('.', $batch->batch_file);
        $batch_name = $batch_name_arr[0];
        $netcash = new \NetCash($batch_name);
        if (! $batch->uploaded) {
            // upload batch
            $netcash->upload();
            $result_file = $batch_name.'result.txt';
            $result_token = \Storage::disk('debit_orders')->get($result_file);
            $update = [
                'result_file' => $result_file,
                'result_token' => $result_token,
                'uploaded' => 1,
            ];
            \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update($update);

            authorize_single_debit_order($batch_id);
            sleep(1);
        } else {
            // get batch result
            $result = $netcash->report();

            $batch_result = $result->RequestFileUploadReportResult;

            \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update(['result' => $batch_result]);
            if ($batch_result == 'FILE NOT READY') {
                authorize_single_debit_order($batch_id);
                sleep(1);
            } elseif (str_contains($batch_result, 'UNSUCCESSFUL')) {

                // invalid action date, delete batch, create new batch with different action date
                // retry to create batch maximum 5 times
                if (session('debit_order_retry_count') < 5) {
                    $retry_count = session('debit_order_retry_count') + 1;
                    $action_date = date('Y-m-d', strtotime($batch->action_date.' + 1 day'));
                    session(['debit_order_retry_count' => $retry_count]);

                    \DB::table('acc_debit_order_batch')->where('id', $batch->id)->delete();
                    \Storage::disk('debit_orders')->delete($batch->batch_file);
                    create_single_debit_order($batch->limit_account_id, $action_date);
                } else {
                    session()->forget('debit_order_retry_count');
                    dev_email('Debit Order process failed. Please process debit orders manually');
                    admin_email('Debit Order process failed. Please process debit orders manually');
                }
            } elseif (str_contains($batch_result, 'SUCCESSFUL')) {
                session()->forget('debit_order_retry_count');

                // batch success, end process
                $netcash->createTransactions($batch->id);
                \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update(['result' => $batch_result]);
                $authorise_result = $netcash->authoriseBatch($batch->id);
                \DB::table('acc_debit_order_batch')->where('id', $batch->id)->update(['authorise_result' => $authorise_result]);
                debit_order_created_email($batch->id);
            }
        }
    }
}

function account_has_authorised_debit_order($account_id)
{
    $count = \DB::table('acc_cashbook_transactions')
        ->join('acc_debit_order_batch', 'acc_cashbook_transactions.debit_order_batch_id', '=', 'acc_debit_order_batch.id')
        ->where('acc_cashbook_transactions.account_id', $account_id)
        ->where('acc_debit_order_batch.authorise_result', 'Debit order authorised')
        ->count();
    if ($count > 0) {
        return true;
    }

    return false;
}

function account_has_processed_debit_order($account_id, $retry = false)
{
    $valid = \DB::table('acc_debit_orders')->where('account_id', $account_id)->where('validated', 1)->where('status', 'Enabled')->count();
    if (! $valid) {
        return 'No Debit order details exists for this account. Please verify debit order status and bank details';
    }
    /*
    Debit order process - provisioning
    First check if account has authorised debit order
    Wait 3 days after debit order action date, to check if debit order declined
    returns success after 3 days if the debit order has not been unpaid on the statement
    */
    $account = dbgetaccount($account_id);
    if ($account->partner_id != 1) {
        $account_id = $account->partner_id;
    }

    $authed = account_has_authorised_debit_order($account_id);
    $authed_debit_orders = \DB::table('acc_cashbook_transactions')
        ->join('acc_debit_order_batch', 'acc_cashbook_transactions.debit_order_batch_id', '=', 'acc_debit_order_batch.id')
        ->where('acc_cashbook_transactions.account_id', $account_id)
        ->where('acc_cashbook_transactions.api_status', 'Complete')
        ->where('acc_debit_order_batch.authorise_result', 'Debit order authorised')
        ->count();

    if ($authed_debit_orders > 1) { // multiple debit orders
        return 'Debit order processed.';
    }
    if (! $authed) {
        $debit_order_details_exists = \DB::table('acc_debit_orders')->where('account_id', $account_id)->count();
        if (! $debit_order_details_exists) {
            return 'Debit order bank details does not exists, customer needs to fill out debit order form.';
        } else {
            $debit_order_details_validated = \DB::table('acc_debit_orders')->where('account_id', $account_id)->where('validated', 1)->count();
            if (! $debit_order_details_validated) {
                $debit_order = \DB::table('acc_debit_orders')->where('account_id', $account_id)->get()->first();
                $netcash = new \NetCash;
                $response = $netcash->validateBankAccount($debit_order);

                \DB::table('acc_debit_orders')->where('id', $debit_order->id)->update($response);
            }

            $debit_order_details_validated = \DB::table('acc_debit_orders')->where('account_id', $account_id)->where('validated', 1)->count();
            if (! $debit_order_details_validated) {
                return 'Debit order bank details not verified. Please verify bank details manually.';
            }

            $debit_order_created = \DB::table('acc_debit_order_batch')
                ->where('limit_account_id', $account_id)
                ->count();
            if (! $debit_order_created) {
                create_single_debit_order($account_id);
            }

            $debit_order_created = \DB::table('acc_debit_order_batch')
                ->where('limit_account_id', $account_id)
                ->count();
            if (! $debit_order_created) {
                return 'Debit order could not be created for this account.';
            }

            return 'Debit order created for this account.';
        }
    }

    $debit_order_count = \DB::table('acc_cashbook_transactions')
        ->join('acc_debit_order_batch', 'acc_cashbook_transactions.debit_order_batch_id', '=', 'acc_debit_order_batch.id')
        ->where('acc_cashbook_transactions.account_id', $account_id)
        ->whereIn('acc_cashbook_transactions.api_status', ['Complete', 'Declined'])
        ->where('acc_debit_order_batch.authorise_result', 'Debit order authorised')
        ->count();

    if ($debit_order_count == 1) { // first debit order
        $first_debit_order = \DB::table('acc_cashbook_transactions')
            ->join('acc_debit_order_batch', 'acc_cashbook_transactions.debit_order_batch_id', '=', 'acc_debit_order_batch.id')
            ->where('acc_cashbook_transactions.account_id', $account_id)
            ->whereIn('acc_cashbook_transactions.api_status', ['Complete', 'Declined'])
            ->where('acc_debit_order_batch.authorise_result', 'Debit order authorised')
            ->get()->first();

        if ($first_debit_order->api_status == 'Declined') {
            if ($retry) {
                create_single_debit_order($account_id);

                return 'Previous Debit Order declined. New Debit Order submitted.';
            } else {
                $form = 'Debit Order Declined.<br>';
                $form .= '<label for="retry_debit_order" >Retry Debit Order</label><br>';
                $form .= '<input type="checkbox" name="retry_debit_order" id="retry_debit_order"  ><br>';

                return $form;
            }

            return 'Debit Order Declined.';
        }

        $check_processed_date = date('Y-m-d', strtotime($first_debit_order->action_date.' +3 days'));
        if (date('Y-m-d') < $check_processed_date) {
            return 'Debit order submitted. Please wait until '.$check_processed_date.' to verify that it is paid.';
        } else {
            return 'Debit order processed.';
        }
    } elseif ($debit_order_count > 1) {
        $first_debit_order = \DB::table('acc_cashbook_transactions')
            ->join('acc_debit_order_batch', 'acc_cashbook_transactions.debit_order_batch_id', '=', 'acc_debit_order_batch.id')
            ->where('acc_cashbook_transactions.account_id', $account_id)
            ->whereIn('acc_cashbook_transactions.api_status', ['Complete', 'Declined'])
            ->where('acc_debit_order_batch.authorise_result', 'Debit order authorised')
            ->orderBy('acc_cashbook_transactions.id', 'desc')
            ->get()->first();

        if ($first_debit_order->api_status == 'Declined') {
            return 'Debit Order Declined.';
        }

        $check_processed_date = date('Y-m-d', strtotime($first_debit_order->action_date.' +3 days'));
        if (date('Y-m-d') < $check_processed_date) {
            return 'Debit order submitted. Please wait until '.$check_processed_date.' to verify that it is paid.';
        } else {
            return 'Debit order processed.';
        }
    }

    return 'Debit order error.';
}

function schedule_verify_all_debit_orders()
{
    $enabled = get_admin_setting('enable_debit_orders');
    if (! $enabled) {
        return false;
    }
    //return false;
    $netcash = new \NetCash;
    $debit_orders = \DB::table('acc_debit_orders')->where('status', '!=', 'Deleted')->get();
    foreach ($debit_orders as $debit_order) {
        $response = $netcash->validateBankAccount($debit_order);
        \DB::table('acc_debit_orders')->where('id', $debit_order->id)->update($response);

        $account = dbgetaccount($debit_order->account_id);
        if ($account->balance > 0) {
            account_has_processed_debit_order($account->id);
        }
    }

    foreach ($debit_orders as $debit_order) {
        if (! $debit_order->validated) {
            $account = dbgetaccount($debit_order->account_id);
            $data['debit_company'] = $account->company;
            $data['function_name'] = __FUNCTION__;

            erp_process_notification(1, $data);
        }
    }
}

function debit_order_created_email($batch_id)
{
    $debit_order = \DB::table('acc_debit_order_batch')->where('id', $batch_id)->get()->first();
    $lines = \DB::table('acc_cashbook_transactions')->where('debit_order_batch_id', $batch_id)->get();
    $debit_order_details = 'Batch transactions:<br>';
    foreach ($lines as $line) {
        $account = dbgetaccount($line->account_id);
        $debit_order_details .= 'Company: '.$account->company.' | Total: R'.currency($line->total);
    }
    $debit_order_details .= '<br> <b>Batch Total: '.currency($debit_order->total).'</b>';
    $data['debit_order_details'] = $debit_order_details;
    $data['action_date'] = date('Y-m-d', strtotime($debit_order->action_date));
    $data['internal_function'] = 'debit_order_created';
    $data['authorise_result'] = $debit_order->authorise_result;
    //$data['test_debug'] = 1;
    erp_process_notification(1, $data);
}
