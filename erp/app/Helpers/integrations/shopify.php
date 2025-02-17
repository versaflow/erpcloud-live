<?php

function schedule_shopify_update_shop()
{
    $integration_ids = \DB::table('crm_business_plan')->where('api_key', '>', '')->pluck('id')->toArray();
    foreach ($integration_ids as $integration_id) {
        try {
            $shopify = new ShopifyIntegration($integration_id);
            $shopify->updateShop();
            $shopify->updateProductMpn();
        } catch (\Throwable $e) {
            debug_email('schedule_shopify_update_shop '.$e->getMessage());
        }
    }
}

function schedule_shopify_import_orders()
{
    $integration_ids = \DB::table('crm_business_plan')->where('api_key', '>', '')->pluck('id')->toArray();
    foreach ($integration_ids as $integration_id) {
        $shopify = new ShopifyIntegration($integration_id);
        $shopify->importOrders();
        $shopify->importQuotations();
    }
}

function button_shopify_update_shop()
{
    $integration_ids = \DB::table('crm_business_plan')->where('api_key', '>', '')->pluck('id')->toArray();
    foreach ($integration_ids as $integration_id) {
        try {
            $shopify = new ShopifyIntegration($integration_id);
            $shopify->updateShop();
            $shopify->updateProductMpn();
        } catch (\Throwable $e) {
            debug_email('schedule_shopify_update_shop '.$e->getMessage());
        }
    }
}

function shopify_import_payments()
{

    $transactions = payfast_get_transactions(date('Y-m-d'));

    $transactions = collect($transactions);
    $integrations = \DB::table('crm_business_plan')->where('api_key', '>', '')->get();
    foreach ($integrations as $integration) {
        $shopify = new ShopifyIntegration($integration->id);
        $order_links = \DB::table('crm_shopify_links')->where('store_url', $integration->store_url)->where('type', 'order')->where('payment_method', 'PayFast')->where('payment_status', 'paid')->where('payment_imported', 0)->orderBy('id', 'desc')->get();

        foreach ($order_links as $order_link) {
            $shop_order = \DB::table('crm_documents')->where('id', $order_link->erp_id)->get()->first();

            $shop_payment = $shopify->getOrderTransaction($order_link->shopify_id);
            if (! $shop_payment) {
                continue;
            }
            $shop_payment_id = $shop_payment['transactions'][0]['receipt']['payment_id'];
            $transaction = $transactions->where('M Payment ID', $shop_payment_id)->first();
            if (! $transaction) {
                continue;
            }
            if (! str_contains($transaction['custom str4'], 'shopify')) {
                continue;
            }

            if ($shop_payment_id && $transaction['M Payment ID'] == $shop_payment_id) {

                $account_id = $shop_order->account_id;

                $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();
                $payment_not_applied = \DB::table('acc_cashbook_transactions')->where('doctype', 'Cashbook Customer Receipt')->where('account_id', 0)->where('api_id', $transaction['PF Payment ID'])->count();
                if ($payment_not_applied) {
                    \DB::table('acc_cashbook_transactions')->where('doctype', 'Cashbook Customer Receipt')->where('account_id', 0)->where('api_id', $transaction['PF Payment ID'])->delete();
                    \DB::table('crm_shopify_links')->where('id', $order_link->id)->update(['payment_imported' => 0]);
                }
                $exists = \DB::table('acc_cashbook_transactions')->where('doctype', 'Cashbook Customer Receipt')->where('account_id', '>', 0)->where('api_id', $transaction['PF Payment ID'])->count();
                if ($exists) {
                    \DB::table('crm_shopify_links')->where('id', $order_link->id)->update(['payment_imported' => 1]);

                    continue;
                }

                if (! empty($transaction['PF Payment ID'])) {
                    \DB::table('acc_cashbook_transactions')->where('cashbook_id', 5)->where('doctype', 'Cashbook Customer Receipt')->where('api_id', $transaction['PF Payment ID'])->delete();
                }

                if (isset($transaction['Fee'])) {

                    $exists = \DB::table('acc_cashbook_transactions')->where('reference', 'LIKE', 'Payfast Fee%')->where('api_id', $transaction['PF Payment ID'])->count();
                    if (! $exists) {
                        $fee_data = [
                            'ledger_account_id' => 22,
                            'cashbook_id' => $cashbook->id,
                            'total' => abs($transaction['Fee']),
                            'api_id' => $transaction['PF Payment ID'],
                            'reference' => 'Payfast Fee '.$transaction['PF Payment ID'],
                            'api_status' => 'Complete',
                            'doctype' => 'Cashbook Control Payment',
                            'docdate' => date('Y-m-d H:i:s', strtotime($shop_order->docdate)),
                        ];

                        $fee_result = (new \DBEvent)->setTable('acc_cashbook_transactions')->save($fee_data);
                        if (! is_array($fee_result) || empty($fee_result['id'])) {

                            debug_email('Error processing payfast website order fee.', 'Error processing payfast website order.'.json_encode($fee_result));
                            throw new \ErrorException('Error inserting Payfast Fee into journals.'.json_encode($fee_result));
                        }
                    }
                }

                $api_data = [
                    'doctype' => 'Cashbook Customer Receipt',
                    'api_status' => 'Complete',
                    'account_id' => $account_id,
                    'reference' => $shop_order->reference,
                    'total' => $shop_order->total,
                    'cashbook_id' => $cashbook->id,
                    'ledger_account_id' => null,
                    'docdate' => date('Y-m-d H:i:s', strtotime($shop_order->docdate)),
                    'api_id' => $transaction['PF Payment ID'],
                    'api_balance' => currency($transaction['Balance']),
                ];

                $result = (new \DBEvent)->setTable('acc_cashbook_transactions')->save($api_data);

                if (! is_array($result) || empty($result['id'])) {
                    debug_email('Error processing payfast website order.', 'Error processing payfast website order.'.json_encode($result));
                }
            }

        }
    }
}
