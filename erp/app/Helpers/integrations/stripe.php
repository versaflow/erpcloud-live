<?php

function stripe_payment_link($account_id,$amount_cents){
    // https://stripe.com/docs/payments/quickstart?lang=php
    //composer require stripe/stripe-php
    // /usr/bin/php74 /usr/local/bin/composer require stripe/stripe-php
    
    $stripe_payment_option = get_payment_option('Stripe');
    $stripe_enabled = $stripe_payment_option->enabled;
    if($stripe_enabled){
        
        
        $stripe_api_key = $stripe_payment_option->stripe_api_key;
        $stripe_test_key = $stripe_payment_option->stripe_test_api_key;
        $account = dbgetaccount($account_id);
        if($account->currency == 'ZAR'){
            return false;   
        }
        
        $stripe = new \Stripe\StripeClient($stripe_api_key);
        
        
        $checkout_session = $stripe->checkout->sessions->create([
            'line_items' => [[
            'price_data' => [
            'currency' => strtolower($account->currency),
            'product_data' => [
            'name' => session('instance')->name.' Services',
            ],
            'unit_amount' => $amount_cents,
            ],
            'quantity' => 1,
            ]],
            'mode' => 'payment',
            'client_reference_id' => $account_id,
            'success_url' => url('/'),
            'cancel_url' => url('/'),
        ]);
        
        return $checkout_session->url;
    }
}

function schedule_stripe_reconcile(){
    
    $stripe_payment_option = get_payment_option('Stripe');
    $stripe_enabled = $stripe_payment_option->enabled;
    if(!$stripe_enabled){
        return false;
    }
    $stripe_api_key = $stripe_payment_option->stripe_api_key;
    $stripe_test_key = $stripe_payment_option->stripe_test_api_key;
    $stripe = new \Stripe\StripeClient($stripe_api_key);
    
    // checkout session links to paymentintents
    // charges references paymentintents
    // balance transactions references charges
    // stripe_ids needs to be updated on cashbook to reconcile stripe balances
    
    // get charges id to update payment_intent_ids on cashbook
    
    $reconcile_from = strtotime('first day of this month');

    $charges = $stripe->charges->all(['created' => ['gte'=>$reconcile_from]]);
   
    foreach($charges->data as $charge){
        \DB::table('acc_cashbook_transactions')->where('stripe_id',$charge->payment_intent)->update(['stripe_id'=>$charge->id]);
    }
    
    // create stripe fee
    $transactions = $stripe->balanceTransactions->all(['created'=> ['gte'=>$reconcile_from]]);
    $transaction_data = array_reverse($transactions->data);
   
    foreach($transaction_data as $transaction){
      
        if($transaction->fee > 0){
            $trx_created = \DB::table('acc_cashbook_transactions')->where('stripe_id',$transaction->source)->count();
            $fee_created = \DB::table('acc_cashbook_transactions')->where('stripe_id',$transaction->source)->where('reference','Stripe processing fees')->count();
     
            if(!$fee_created && $trx_created){
                $db = new DBEvent();
                $fee_data = [
                    'ledger_account_id' => 22,
                    'cashbook_id' =>  13,
                    'total' =>  $transaction->fee/100,
                    'stripe_id' => $transaction->source,
                    'document_currency' =>  strtoupper($transaction->currency),
                    'reference' => 'Stripe processing fees',
                    'doctype' => 'Cashbook Control Payment',
                    'api_status' => 'Complete',
                    'docdate' => date('Y-m-d', $transaction->created),
                ];
               
                $db->setTable('acc_cashbook_transactions')->save($fee_data);
                
            }
        }
    }
    
    
    // stripe does not have rolling balance on statement, needs to be rebuilt from current and pending balance
    $stripe_balance = $stripe->balance->retrieve([]);
    $closing_balance = 0;
    foreach($stripe_balance->available as $available_balance){
        $closing_balance += $available_balance->amount;
    }
    foreach($stripe_balance->pending as $pending_balance){
        $closing_balance += $pending_balance->amount;
    }
    $stripe_balance = $closing_balance;
    
    $cashbook = \DB::table('acc_cashbook_transactions')->where('cashbook_id',13)->orderBy('docdate','desc')->orderBy('id','desc')->get();
    foreach($cashbook as $trx){
        $reconciled = ($trx->balance == $closing_balance) ? 1 : 0;    
        \DB::table('acc_cashbook_transactions')->where('id',$trx->id)->update(['api_balance'=>$closing_balance, 'reconciled' => $reconciled]);
        $closing_balance -= $trx->total;
    }
    
    cashbook_reconcile(13);
    
}











