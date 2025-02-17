<?php

function afterdelete_delete_yodlee_account($request)
{
    $y = new Yodlee;
    $user = str_replace('_', '', session('instance')->directory);
    $y->setLoginName($user);
    $result = $y->deleteAccount($request->id);
}

function button_yodlee_link_account($request)
{
    $y = new Yodlee('production');

    $user = str_replace('_', '', session('instance')->directory);

    $y->setLoginName($user);

    $data['accounts'] = [];
    $accounts = $y->getAccounts();
    if (! empty($accounts) && ! empty($accounts->account)) {
        foreach ($accounts->account as $acc) {
            $data['accounts'][] = $acc;
        }
    }
    $data['access_token'] = $y->token->accessToken;
    $data['fastlink_url'] = $y->fastlink_url;

    return view('__app.button_views.yodlee_link_account', $data);
}

function button_yodlee_import_transactions()
{

    return json_alert('Yodlee Not Active');
    $y = new Yodlee;

    $user = str_replace('_', '', session('instance')->directory);

    $y->setLoginName($user);

    $accounts = $y->getProviderAccounts();

    if (! empty($accounts->providerAccount) && is_array($accounts->providerAccount)) {
        foreach ($accounts->providerAccount as $account) {
            $result = $y->updateProviderAccounts($account->id);
        }
    }
    //sleep(5);

    $y->import(date('Y-m-d', strtotime('-4 days')), date('Y-m-d'));
    // $y->import('2022-12-17', date('Y-m-d'));

    allocate_bank_transactions();

    return json_alert('Done');
}

function button_yodlee_view_transactions()
{
    $y = new Yodlee;
    $user = str_replace('_', '', session('instance')->directory);

    $y->setLoginName($user);

    $table = '<h4 class="p-2"> Yodlee Transactions - Last 2 months</h4><div class="table-responsive"><table class="table table-bordered">';
    $table .= '<thead>';
    $table .= '<tr><th>Date</th><th>Type</th><th>Reference</th><th>Amount</th><th>Currency</th><th>Status</th><th>Running Balance</th></tr>';
    $table .= '</thead>';
    $table .= '<tbody>';
    $transactions = $y->getTransactionsFromDate(date('Y-m-d', strtotime('- 2 months')));

    if (! empty($transactions) && ! empty($transactions->transaction)) {
        $transactions = $transactions->transaction;
        foreach ($transactions as $trx) {
            $table .= '<tr><td>'.$trx->transactionDate.'</td><td>'.$trx->baseType.'</td><td>'.$trx->description->original.'</td><td>'.$trx->amount->amount.'</td><td>'.$trx->amount->currency.'</td><td>'.$trx->status.'</td><td>'.$trx->runningBalance->currency.'</td></tr>';
        }
    }
    $table .= '</tbody></table></div>';
    echo $table;
}
function button_yodlee_update($request)
{
    $user = str_replace('_', '', session('instance')->directory);
    $y = new Yodlee;
    $y->setLoginName($user);
    $accounts = $y->getProviderAccounts();
    foreach ($accounts->providerAccount as $account) {
        $result = $y->updateProviderAccounts($account->id);
    }
    if ($result->errorMessage) {
        return json_alert(json_encode($result), 'error');
    } else {
        return json_alert(json_encode($result), 'success');
    }
}

function schedule_yodlee_import_transactions()
{
    if (date('l') != 'Sunday') {
        $y = new Yodlee;

        $cashbook_bank_ids = \DB::table('acc_cashbook')->where('yodlee_account_id', '>', '')->pluck('id')->toArray();
        $total_transactions = \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids)->count();
        $user = str_replace('_', '', session('instance')->directory);
        $y->setLoginName($user);
        $y->import(date('Y-m-d', strtotime('-8 days')), date('Y-m-d'));
        //$y->import('2022-11-08', date('Y-m-d'));
        allocate_bank_transactions();

        $total_transactions_after_import = \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids)->count();

        $new_transactions_count = $total_transactions_after_import - $total_transactions;
        $data = [];
        $data['internal_function'] = 'yodlee_bank_import';
        $data['transaction_count'] = $new_transactions_count;
        $data['transaction_details'] = '';
        if ($new_transactions_count > 0) {
            $new_transactions = \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids)->orderBy('id', 'desc')->limit($new_transactions_count)->get();
            foreach ($new_transactions as $trx) {
                $data['transaction_details'] .= 'Reference: '.$trx->reference.' | Docdate: '.$trx->docdate.' | Total: '.$trx->total.PHP_EOL;
            }
        } else {
            $data['transaction_details'] = 'No new transactions imported';
        }

        erp_process_notification(1, $data);
    }
}

function button_yodlee_update_account_data($request)
{
    $y = new Yodlee('production');
    $user = str_replace('_', '', session('instance')->db_connection);

    $y->setLoginName($user);
    $provider_accounts = $y->getProviderAccounts();

    $accounts = $y->getAccounts();
    if (! empty($accounts) && ! empty($accounts->account)) {
        foreach ($accounts->account as $account) {
            $data = [
                'id' => $account->id,
                'provider_account_id' => $account->providerAccountId,
                'account_name' => $account->accountName,
                'account_status' => $account->accountStatus,
                'account_number' => $account->accountNumber,
                'provider_id' => $account->providerId,
                'provider_name' => $account->providerName,
                'created_date' => date('Y-m-d H:i', strtotime($account->createdDate)),
                'additional_status' => $account->dataset[0]->additionalStatus,
                'update_eligibility' => $account->dataset[0]->updateEligibility,
                'last_updated' => date('Y-m-d H:i', strtotime($account->dataset[0]->lastUpdated)),
                'last_update_attempt' => date('Y-m-d H:i', strtotime($account->dataset[0]->lastUpdateAttempt)),
                'next_update_scheduled' => date('Y-m-d H:i', strtotime($account->dataset[0]->nextUpdateScheduled)),
                'currency' => $account->currentBalance->currency,
            ];
            \DB::table('acc_yodlee_accounts')->updateOrInsert(['id' => $account->id], $data);
        }
    }

    return json_alert('Done');
}

function schedule_yodlee_update_accounts()
{
    $yodlee_status = '';

    $yodlee_count = \DB::table('acc_cashbook')->where('status', 'Enabled')->where('yodlee_account_id', '>', '')->count();
    if (! $yodlee_count) {
        //return true;
    }

    $y = new Yodlee('production');
    $user = str_replace('_', '', session('instance')->db_connection);

    $y->setLoginName($user);
    $provider_accounts = $y->getProviderAccounts();

    $accounts = $y->getAccounts();

    if (! empty($accounts) && ! empty($accounts->account)) {
        foreach ($accounts->account as $account) {
            $data = [
                'id' => $account->id,
                'provider_account_id' => $account->providerAccountId,
                'account_name' => $account->accountName,
                'account_status' => $account->accountStatus,
                'account_number' => $account->accountNumber,
                'provider_id' => $account->providerId,
                'provider_name' => $account->providerName,
                'created_date' => date('Y-m-d H:i', strtotime($account->createdDate)),
                'additional_status' => $account->dataset[0]->additionalStatus,
                'update_eligibility' => $account->dataset[0]->updateEligibility,
                'last_updated' => date('Y-m-d H:i', strtotime($account->dataset[0]->lastUpdated)),
                'last_update_attempt' => date('Y-m-d H:i', strtotime($account->dataset[0]->lastUpdateAttempt)),
                'next_update_scheduled' => date('Y-m-d H:i', strtotime($account->dataset[0]->nextUpdateScheduled)),
                'currency' => $account->currentBalance->currency,
            ];

            \DB::table('acc_yodlee_accounts')->updateOrInsert(['id' => $account->id], $data);

        }
    }

    if (! empty($provider_accounts) && ! empty($provider_accounts->providerAccount)) {
        foreach ($provider_accounts->providerAccount as $provider_account) {
            $last_updated = date('Y-m-d', strtotime($provider_account->dataset[0]->lastUpdated));

            if ($provider_account->dataset[0]->updateEligibility != 'ALLOW_UPDATE' && date('Y-m-d') != $last_updated) {
                $yodlee_status .= 'Instance Name: '.$instance->name.'<br><br>';
                $yodlee_status .= 'ID: '.$provider_account->id.'<br>';
                $yodlee_status .= 'Created Date: '.$provider_account->createdDate.'<br>';
                $yodlee_status .= 'Status: '.$provider_account->status.'<br>';

                $yodlee_status .= 'Type: '.$provider_account->dataset[0]->name.'<br>';
                $yodlee_status .= 'additionalStatus: '.$provider_account->dataset[0]->additionalStatus.'<br>';
                $yodlee_status .= 'updateEligibility: '.$provider_account->dataset[0]->updateEligibility.'<br>';
                $yodlee_status .= 'lastUpdated: '.$provider_account->dataset[0]->lastUpdated.'<br>';
                $yodlee_status .= 'lastUpdateAttempt: '.$provider_account->dataset[0]->lastUpdateAttempt.'<br>';
                $yodlee_status .= 'nextUpdateScheduled: '.$provider_account->dataset[0]->nextUpdateScheduled.'<br><br>';
            }
        }
    }

    if ($yodlee_status > '') {
        $data = [
            'yodlee_status' => $yodlee_status,
            'function_name' => 'schedule_yodlee_monitor',
        ];
        erp_process_notification(1, $data);
    }

    if (date('l') != 'Sunday') {
        $accounts = $y->getProviderAccounts();
        if (! empty($accounts->providerAccount)) {
            foreach ($accounts->providerAccount as $account) {
                if (date('Y-m-d') > date('Y-m-d', strtotime($account->dataset[0]->nextUpdateScheduled))) {
                    $y->updateProviderAccounts($account->id);
                } elseif (date('Y-m-d') != date('Y-m-d', strtotime($account->dataset[0]->lastUpdated))) {
                    $y->updateProviderAccounts($account->id);
                }
            }
        }
    }
}
