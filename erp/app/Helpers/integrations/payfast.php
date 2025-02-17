<?php

function schedule_payfast_update_totals()
{
    $payment_option = get_payment_option('Payfast');

    $payfast_subscription = new PayfastSubscription();
    $payfast_subscription->setCredentials($payment_option->payfast_id, $payment_option->payfast_key, $payment_option->payfast_pass_phrase);

    $subs = \DB::table('acc_payfast_subscriptions')->where('status', '!=', 'Deleted')->orderBy('id', 'desc')->get();
    $account_ids = [];
    foreach ($subs as $sub) {
        // remove duplicate payfast subscriptions
        if (in_array($sub->account_id, $account_ids)) {
            $payfast_subscription->cancel($sub->token);
            \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->where('id', '!=', $pf_sub->id)->update(['status' => 'Deleted']);
            continue;
        }
        $account_ids[] = $sub->account_id;

        // if ($sub->paused) {
        //     $response = $payfast_subscription->unpause($sub->token);

        //     if ($response && $response['code'] == 200 && !empty($response['data']) && $response['data']['response'] === true) {
        //         \DB::table('acc_payfast_subscriptions')->where('id', $sub->id)->update(['paused' => 0]);
        //     }
        // }

        // $response = $payfast_subscription->get($sub->token);

        // if ($response['code'] == 200 && $response['data']['response']['status_text'] == 'ACTIVE') {
        //     \DB::table('acc_payfast_subscriptions')->where('id', $sub->id)->update(['status' => 'Enabled']);
        //     process_account_declined_payfast_subscription($account_id);
        // } else {
        //     \DB::table('acc_payfast_subscriptions')->where('id', $sub->id)->update(['status' => $response['data']['response']['status_text']]);
        // }
    }
    \DB::table('acc_payfast_subscriptions')->where('status', 'Enabled')->update(['paused' => 0]);
    $subs = \DB::table('acc_payfast_subscriptions')->where('status', 'Enabled')->get();

    $ErpSubs = new ErpSubs();
    foreach ($subs as $sub) {
        $ErpSubs->updatePayfastSubscription($sub->account_id);
    }
}


function process_account_declined_payfast_subscription($account_id)
{
    \DB::table('crm_accounts')->where('id', $account_id)->update(['payment_method' => 'Bank']);

    $service_account_ids = [$account_id];
    $account = dbgetaccount($account_id);
    if ($account->type == 'reseller') {
        $service_account_ids = \DB::table('crm_accounts')->where('partner_id', $account_id)->pluck('id')->toArray();
    }
    \DB::table('sub_services')->whereIn('account_id', $service_account_ids)->update(['contract_period' => 0]);
    $sub = new ErpSubs();
    foreach ($service_account_ids as $service_account_id) {
        $sub->updateProductPricesByAccount($service_account_id);
    }
}

function payfast_set_subscription_totals()
{
    $payment_option = get_payment_option('Payfast');
    $payfast_subscription = new PayfastSubscription();
    $payfast_subscription->setCredentials($payment_option->payfast_id, $payment_option->payfast_key, $payment_option->payfast_pass_phrase);
    $subs = \DB::table('acc_payfast_subscriptions')->where('status', 'Enabled')->get();
    foreach ($subs as $sub) {
        $response = $payfast_subscription->get($sub->token);

        if ($response['code'] == 200 && $response['data']['response']['status_text'] != 'ACTIVE') {
            \DB::table('acc_payfast_subscriptions')->where('id', $sub->id)->update(['status' => $response['data']['response']['status_text']]);
        }
    }

    $subs = \DB::table('acc_payfast_subscriptions')->where('status', 'Enabled')->get();
    $ErpSubs = new ErpSubs();
    foreach ($subs as $sub) {
        $ErpSubs->updatePayfastSubscription($sub->account_id);
    }
}

function process_subscription_payments()
{
    return false;
    $payment_option = get_payment_option('Payfast');

    $payfast = new Payfast();
    $payfast->setCredentials($payment_option->payfast_id, $payment_option->payfast_key, $payment_option->payfast_pass_phrase);
    $transactions = $payfast->getTransactionsDaily(date('Y-m-d'));

    $transactions = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($transactions)));
    $keys = $transactions[0];
    unset($transactions[0]);
    $transactions = collect($transactions)->filter()->toArray();
    foreach ($keys as $i => $k) {
        $key = str_replace(' ', '_', strtolower($k));
        if ($key == 'fee' || $key == 'gross' || $key == 'net') {
            $key = 'amount_'.$key;
        }
        $keys[$i] = $key;
    }

    foreach ($transactions as $i => $t) {
        $transactions[$i] = array_combine($keys, $t);
    }

    foreach ($transactions as $i => $t) {
        if ($t['name'] != 'Cloud Telecoms Subscription') {
            unset($transactions[$i]);
        }
    }

    foreach ($transactions as $request) {
        $request = (object) $request;
        // \DB::table('acc_cashbook_transactions')->where('api_id',$request->pf_payment_id)->delete();
        $post_data = $request;
        $created_at = date('Y-m-d H:i:s', strtotime($request->date));
        $db = new \DBEvent();
        $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();

        $reference = $request->m_payment_id.'_'.$request->pf_payment_id;
        $account_id = $request->custom_int1;
        $amount = currency($request->amount_gross);

        if (empty($account_id)) {
            throw new \ErrorException('account not found.'.$reference);
        }

        $account = dbgetaccount($account_id);
        if ($account->partner_id != 1) {
            return true; // reseller users payments
        }

        if ($account_id == 12) {
            throw new \ErrorException('PayFast response is for demo, cloudtelecoms customer account.');
        }

        $payment_data = [
        'docdate' => date('Y-m-d', strtotime($created_at)),
        'total' => $amount,
        'reference' => $reference,
        'account_id' => $account_id,
        'source' => 'PayFast',
    ];

        $pre_payment_exists = \DB::table('acc_cashbook_transactions')->where('reference', $reference)->count();

        if (!$pre_payment_exists) {
            if (isset($post_data->amount_fee)) {
                $fee_data = [
                'ledger_account_id' => 22,
                'cashbook_id' => $cashbook->id,
                'total' => abs($post_data->amount_fee),
                'api_id' => $post_data->pf_payment_id,
                'reference' => 'Payfast Fee '.$payment_id,
                'api_status' => 'Complete',
                'doctype' => 'Cashbook Control Payment',
                'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
            ];

                $fee_result = $db->setTable('acc_cashbook_transactions')->save($fee_data);

                if (!is_array($fee_result) || empty($fee_result['id'])) {
                    throw new \ErrorException('Error inserting Payfast Fee into journals.'.json_encode($fee_result));
                }
            }

            $api_data = [
            'api_status' => 'Complete',
            'account_id' => $account_id,
            'reference' => $reference,
            'cashbook_id' => $cashbook->id,
            'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
            'api_data' => serialize($post_data),
            'api_id' => $post_data->pf_payment_id,
        ];

            $result = $db->setTable('acc_cashbook_transactions')->save($api_data);

            if (!is_array($result) || empty($result['id'])) {
                if ($fee_result['id']) {
                    $db->setTable('acc_general_journals')->deleteRecord(['id' => $fee_result['id']]);
                }
                throw new \ErrorException('Error inserting to acc_cashbook_transactions.'.json_encode($result));
            }

            $subscription_data = [
            'last_billed_time' => date('Y-m-d H:i:s', strtotime($created_at)),
            'last_bill_amount' => $request->amount_gross,
            'last_billed_status' => 'Completed',
            'api_id' => $post_data->pf_payment_id,
        ];
            \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->update($subscription_data);
            \DB::table('acc_debit_orders')->where('account_id', $account_id)->update(['status' => 'Deleted']);
        }
    }
}

function payfast_get_transactions_range($from, $to)
{
    $payment_option = get_payment_option('Payfast');
    $api = new PayFast\PayFastApi([
        'merchantId' => $payment_option->payfast_id,
        'passPhrase' => $payment_option->payfast_pass_phrase,
        'testMode' => false,
    ]);

    return $api->transactionHistory->range(['from' => $from, 'to' => $to]);
}

function get_pending_invoices_total($account_id)
{
    $pending_total = \DB::table('sub_services as s')
                    ->join('crm_documents as d', 's.invoice_id', '=', 'd.id')
                    ->where('d.doctype', 'Order')
                    ->where('s.account_id', $account_id)
                    ->where('s.status', 'Pending')
                    ->sum('s.price_incl');

    return $pending_total;
}

function payfast_get_transactions_month($date)
{
    $payfast = new Payfast();
    $response = $payfast->getTransactionHistory(date('Y-m-01', strtotime($date)), date('Y-m-t', strtotime($date)));
    if (empty($response->body)) {
        return [];
    }

    $transactions = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($response->body)));
    $keys = array_shift($transactions);
    foreach ($transactions as $i => $t) {
        $transactions[$i] = array_combine($keys, $t);
    }

    return $transactions;
}

function payfast_get_transactions_day($date)
{
    $payfast = new Payfast();
    $response = $payfast->getTransactionHistory($date, $date);
    if (empty($response->body)) {
        return [];
    }

    $transactions = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($response->body)));
    $keys = array_shift($transactions);
    foreach ($transactions as $i => $t) {
        $transactions[$i] = array_combine($keys, $t);
    }

    return $transactions;
}

function payfast_get_transactions($date)
{
    $payfast = new Payfast();
    $response = $payfast->getTransactionHistoryPeriod($date);

    if (empty($response->body)) {
        return [];
    }

    if (is_string($response->body)) {
        $transactions = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($response->body)));
    } else {
        $transactions = $response->body;
    }

    if (!empty($transactions) && is_array($transactions)) {
        $keys = array_shift($transactions);
        foreach ($transactions as $i => $t) {
            $transactions[$i] = array_combine($keys, $t);
        }
    }

    return $transactions;
}

function payfast_get_energy_transactions($date)
{
    $payfast = new Payfast();
    $payfast->setCredentials('21667228', 'go4op6pugjt2o', 'CloudTelecoms786');
    $response = $payfast->getTransactionHistoryPeriod($date);

    if (empty($response->body)) {
        return [];
    }
    if (is_string($response->body)) {
        $transactions = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($response->body)));
    } else {
        $transactions = $response->body;
    }

    $keys = array_shift($transactions);
    foreach ($transactions as $i => $t) {
        $transactions[$i] = array_combine($keys, $t);
    }

    return $transactions;
}

function create_payfast_payout_transaction($api_trx)
{
    if ($api_trx['Type'] == 'PAYOUT') {
        $exists = \DB::table('acc_cashbook_transactions')->where('reference', 'PayFast payout fee '.$api_trx['PF Payment ID'])->count();
    } elseif ($api_trx['Type'] == 'FUNDS_RECEIVED_REVERSAL') {
        $exists = \DB::table('acc_cashbook_transactions')->where('reference', 'Payfast Fee Reversal '.$api_trx['PF Payment ID'])->count();
    } else {
        $exists = \DB::table('acc_cashbook_transactions')->where('reference', 'LIKE', 'Payfast Fee%')->where('api_id', $api_trx['PF Payment ID'])->count();
    }

    if (!$exists) {
        if ($api_trx['Type'] == 'PAYOUT') {
            $payout_exists = \DB::table('acc_cashbook_transactions')->where('reference', 'LIKE', 'Cashbook Transaction ID%')->where('docdate', date('Y-m-d', strtotime($api_trx['Date'])))->count();
            if (!$payout_exists) {
                return false;
            }
            $reference = 'Payfast payout fee '.$api_trx['PF Payment ID'];
        } else {
            $reference = 'Payfast Fee '.$api_trx['PF Payment ID'];
        }
        if ($api_trx['Type'] == 'FUNDS_RECEIVED_REVERSAL') {
            $reference = 'Payfast Fee Reversal '.$api_trx['PF Payment ID'];
        }

        if (currency($api_trx['Fee']) != 0) {
            $db = new DBEvent();
            $fee_data = [
                'ledger_account_id' => 22,
                'cashbook_id' => 5,
                'total' => abs(currency($api_trx['Fee'])),
                'api_id' => $api_trx['PF Payment ID'],
                'reference' => $reference,
                'doctype' => 'Cashbook Control Payment',
                'api_status' => 'Complete',
                'docdate' => date('Y-m-d H:i:s', strtotime($api_trx['Date'])),
            ];
            $r = $db->setTable('acc_cashbook_transactions')->save($fee_data);
        }
    } else {
        if ($api_trx['Type'] == 'PAYOUT') {
            \DB::table('acc_cashbook_transactions')
                ->where('reference', 'PayFast payout fee '.$api_trx['PF Payment ID'])
                ->update(['total' => abs(currency($api_trx['Fee']))]);
        } else {
            if ($api_trx['Type'] != 'FUNDS_RECEIVED_REVERSAL') {
                \DB::table('acc_cashbook_transactions')->where('reference', 'NOT LIKE', '%REVERSAL%')
                ->where('reference', 'LIKE', 'Payfast Fee%')->where('api_id', $api_trx['PF Payment ID'])
                ->update(['total' => abs(currency($api_trx['Fee']))]);
            } else {
                $reference = 'Payfast Fee Reversal '.$api_trx['PF Payment ID'];
                \DB::table('acc_cashbook_transactions')
                ->where('reference', $reference)->where('api_id', $api_trx['PF Payment ID'])
                ->update(['total' => currency($api_trx['Fee']) * -1]);
            }
        }
    }

    if ($api_trx['Type'] != 'PAYOUT') {
        if ($api_trx['Sign'] == 'CREDIT') {
            $trx_exists = \DB::table('acc_cashbook_transactions')->where('doctype', 'Cashbook Customer Receipt')->where('api_id', $api_trx['PF Payment ID'])->count();

            if (!$trx_exists) {
                $data = [
                    'ledger_account_id' => 22,
                    'cashbook_id' => 5,
                    'total' => abs(currency($api_trx['Gross'])),
                    'api_id' => $api_trx['PF Payment ID'],
                    'reference' => $api_trx['Description'].' '.$api_trx['Party'],
                    'doctype' => 'Cashbook Customer Receipt',
                    'api_status' => 'Complete',
                    'docdate' => date('Y-m-d H:i:s', strtotime($api_trx['Date'])),
                ];
                if ($api_trx['Type'] == 'TOPUP') {
                    $data['ledger_account_id'] = 12;
                    $data['doctype'] = 'Cashbook Expense';
                }
                if ($api_trx['Type'] == 'FUNDS_RECEIVED') {
                    unset($data['ledger_account_id']);
                    $data['doctype'] = 'Cashbook Customer Receipt';
                    $data['account_id'] = $api_trx['custom int1'];
                    // if(str_contains($api_trx["custom str4"],'shopify')){
                    //     $data['account_id'] = \DB::table('crm_shopify_links')->where('shopify_id',$api_trx["custom int1"])->where('type','customer')->pluck('erp_id')->first();
                    // }
                    if (empty($data['account_id'])) {
                        // wordpress orders
                        $order_id = \DB::table('crm_wordpress_links')->where('integration_id', 2)->where('type', 'order')->where('transaction_id', $data['api_id'])->pluck('erp_value')->first();
                        if ($order_id) {
                            $account_id = \DB::table('crm_documents')->where('id', $order_id)->pluck('account_id')->first();
                            if ($account_id) {
                                $data['account_id'] = $account_id;
                            }
                        }
                    }
                }
                \DB::table('acc_cashbook_transactions')->insert($data);
            }
        } else {
            if ($api_trx['Type'] == 'FUNDS_SENT') {
                $trx_exists = \DB::table('acc_cashbook_transactions')
                ->where('total', abs(currency($api_trx['Gross'])) * -1)->where('api_id', $api_trx['PF Payment ID'])->count();

                if (!$trx_exists) {
                    $data = [
                    'ledger_account_id' => 12,
                    'cashbook_id' => 5,
                    'total' => abs(currency($api_trx['Gross'])) * -1,
                    'api_id' => $api_trx['PF Payment ID'],
                    'reference' => $api_trx['Description'].' '.$api_trx['Party'],
                    'doctype' => 'Cashbook Expense',
                    'api_status' => 'Complete',
                    'docdate' => date('Y-m-d H:i:s', strtotime($api_trx['Date'])),
                    ];
                    \DB::table('acc_cashbook_transactions')->insert($data);
                }
            }

            if ($api_trx['Type'] == 'FUNDS_RECEIVED_REVERSAL') {
                $trx_exists = \DB::table('acc_cashbook_transactions')
                ->where('total', abs(currency($api_trx['Gross'])) * -1)->where('api_id', $api_trx['PF Payment ID'])->count();
                if (!$trx_exists) {
                    $data = [
                    'account_id' => $api_trx['custom int1'],
                    'cashbook_id' => 5,
                    'total' => abs(currency($api_trx['Gross'])) * -1,
                    'api_id' => $api_trx['PF Payment ID'],
                    'reference' => $api_trx['Description'].' '.$api_trx['Party'].'REVERSAL',
                    'doctype' => 'Cashbook Customer Receipt',
                    'api_status' => 'Complete',
                    'docdate' => date('Y-m-d H:i:s', strtotime($api_trx['Date'])),
                    ];

                    \DB::table('acc_cashbook_transactions')->insert($data);
                }

                $reference = 'Payfast Fee Reversal '.$api_trx['PF Payment ID'];
                $fee_trx_exists = \DB::table('acc_cashbook_transactions')->where('reference', $reference)->where('api_id', $api_trx['PF Payment ID'])->count();
                if (!$fee_trx_exists && currency($api_trx['Fee']) > 0) {
                    $db = new DBEvent();
                    $fee_data = [
                    'ledger_account_id' => 22,
                    'cashbook_id' => 5,
                    'total' => currency($api_trx['Fee']) * -1,
                    'api_id' => $api_trx['PF Payment ID'],
                    'reference' => $reference,
                    'doctype' => 'Cashbook Control Payment',
                    'api_status' => 'Complete',
                    'docdate' => date('Y-m-d H:i:s', strtotime($api_trx['Date'])),
                    ];

                    $db->setTable('acc_cashbook_transactions')->save($fee_data);
                }
            }
        }
    }
}