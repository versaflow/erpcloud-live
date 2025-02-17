<?php

function schedule_fnb_import_transactions($cashbook_id = 0)
{
    $imports = false;
    if ($cashbook_id == 0) {
        $cashbooks = \DB::table('acc_cashbook')->where('fnb_username', '>', '')->where('fnb_password', '>', '')->where('fnb_account_no', '>', '')->get();
    } else {
        $cashbooks = \DB::table('acc_cashbook')->where('id', $cashbook_id)->get();
    }

    foreach ($cashbooks as $cashbook) {
        // vd($cashbook);
        $transactions = fnb_get_transactions($cashbook);

        if (is_array($transactions) && count($transactions) > 0) {
            foreach ($transactions as $trx) {
                if ($trx->status == 'Successful' && $trx->amount != 0) {
                    if (empty($trx->balance) || $trx->balance == 0) {
                        continue;
                    }
                    // remove duplicate declined transactions
                    // reference changes on the api
                    if (str_contains($trx->description, 'DECLINED')) {
                        $data = [
                            'cashbook_id' => $cashbook->id,
                            'docdate' => date('Y-m-d', strtotime($trx->date)),
                            'reference' => $trx->description,
                            'total' => currency($trx->amount / 100),
                            'api_balance' => currency($trx->balance / 100),
                            'api_status' => $trx->status,
                            'api_data' => json_encode($trx),
                            'document_currency' => $cashbook->currency,
                        ];
                        if (empty($data['api_balance']) || $data['api_balance'] == 0) {
                            continue;
                        }

                        $ref_id_exists = \DB::connection('default')->table('acc_cashbook_transactions')
                        ->where('cashbook_id', $cashbook->id)
                        ->where('docdate', $data['docdate'])
                        ->where('total', currency($data['total']))
                        ->where('reference', '!=', $data['reference'])
                        ->where('reference', 'LIKE', '%DECLINED%')
                        ->pluck('id')->first();

                        if ($ref_id_exists) {
                            \DB::connection('default')->table('acc_cashbook_transactions')->where('id', $ref_id_exists)->delete();
                        }
                    }
                }
            }

            foreach ($transactions as $trx) {
                if ($trx->status == 'Successful' && $trx->amount != 0) {
                    if (empty($trx->balance) || $trx->balance == 0) {
                        continue;
                    }

                    $data = [
                      'cashbook_id' => $cashbook->id,
                      'api_data' => json_encode($trx),
                    ];

                    $data = [
                        'cashbook_id' => $cashbook->id,
                        'docdate' => date('Y-m-d', strtotime($trx->date)),
                        'reference' => $trx->description,
                        'total' => currency($trx->amount / 100),
                        'api_balance' => currency($trx->balance / 100),
                        'api_status' => $trx->status,
                        'api_data' => json_encode($trx),
                        'document_currency' => $cashbook->currency,
                    ];

                    if (empty($data['api_balance']) || $data['api_balance'] == 0) {
                        continue;
                    }

                    $ofx_exists = \DB::connection('default')->table('acc_cashbook_transactions')
                            ->where('cashbook_id', $cashbook->id)
                            ->where('docdate', $data['docdate'])
                            ->whereRaw('REPLACE(reference, " ", "") ="'.str_replace(' ', '', $data['reference']).'"')
                            ->where('api_status', '')
                            ->where('total', currency($data['total']))->count();

                    $trx_exists = \DB::connection('default')->table('acc_cashbook_transactions')
                            ->where('cashbook_id', $cashbook->id)
                            ->where('docdate', $data['docdate'])
                            ->whereRaw('REPLACE(reference, " ", "") ="'.str_replace(' ', '', $data['reference']).'"')
                            ->where('total', currency($data['total']))->count();

                    if (!$ofx_exists && !$trx_exists) {
                        if (!empty($data['api_balance'])) {
                            \DB::connection('default')->table('acc_cashbook_transactions')->insert($data);
                        }
                    }
                }
            }
            $imports = true;
            cashbook_reconcile($cashbook->id);
        }
        \DB::connection('default')->table('acc_cashbook')->where('id', $cashbook->id)->update(['fnb_last_import' => date('Y-m-d H:i:s')]);
    }

    if ($imports) {
        allocate_bank_transactions();
    }
}

function button_banking_fnb_import_payments($request)
{
    schedule_fnb_import_transactions($request->id);
    // $imports = false;
    // $cashbooks = \DB::table('acc_cashbook')->where('id',$request->id)->where('fnb_username','>','')->where('fnb_password','>','')->where('fnb_account_no','>','')->get();
    // foreach($cashbooks as $cashbook){
    //     $transactions = fnb_get_transactions($cashbook);
    //     if(is_array($transactions) && count($transactions) > 0){
    //         foreach($transactions as $trx){
    //             if($trx->status == 'Successful' && $trx->amount!=0){

    //                 $data = [
    //                   'cashbook_id' => $cashbook->id,
    //                   'api_data' => json_encode($trx),
    //                 ];

    //                 if(empty($trx->balance) || $trx->balance == 0){
    //                     continue;
    //                 }

    //                 $data = [
    //                     'cashbook_id' => $cashbook->id,
    //                     'docdate' => date('Y-m-d',strtotime($trx->date)),
    //                     'reference' => $trx->description,
    //                     'total' => currency($trx->amount/100),
    //                     'api_balance' => currency($trx->balance/100),
    //                     'api_status' => $trx->status,
    //                     'api_data' => json_encode($trx),
    //                     'document_currency' => $cashbook->currency,
    //                 ];
    //                 if(empty($data['api_balance']) || $data['api_balance'] == 0){
    //                     continue;
    //                 }

    //                 $ofx_exists = \DB::connection('default')->table('acc_cashbook_transactions')
    //                         ->where('cashbook_id', $cashbook->id)
    //                         ->where('docdate', $data['docdate'])
    //                         ->whereRaw('REPLACE(reference, " ", "") ="'.str_replace(' ','',$data['reference']).'"')
    //                         ->where('api_status', '')
    //                         ->where('total', currency($data['total']))->count();

    //                 $trx_exists = \DB::connection('default')->table('acc_cashbook_transactions')
    //                         ->where('cashbook_id', $cashbook->id)
    //                         ->where('docdate', $data['docdate'])
    //                         ->where('api_data', json_encode($trx))
    //                         ->where('total', currency($data['total']))->count();

    //                 if(!$ofx_exists && !$trx_exists){
    //                     if(!empty($data['api_balance'])){
    //                         \DB::connection('default')->table('acc_cashbook_transactions')->insert($data);
    //                     }
    //                 }

    //             }

    //         }
    //         /*
    //         $trxs = \DB::connection('default')->table('acc_cashbook_transactions')->where('id',$cashbook->id)->where('api_data','>','')->get();
    //         foreach($trxs as $trx){
    //             $api_data = json_decode($trx->api_data);
    //             if($api_data->balance > 0){
    //                 $api_data->balance = 0;
    //                 $lookup_json = json_encode($api_data);
    //                 $no_balance_id = \DB::connection('default')->table('acc_cashbook_transactions')->where('id',$cashbook->id)->where('id','!=',$trx->id)->where('api_data',$lookup_json)->pluck('id')->first();
    //                 if($no_balance_id){
    //                     \DB::connection('default')->table('acc_cashbook_transactions')->where('id',$cashbook->id)->where('id',$no_balance_id)->delete();
    //                 }
    //             }
    //         }
    //         */
    //         \DB::connection('default')->table('acc_cashbook')->where('id',$cashbook->id)->update(['fnb_last_import'=>date('Y-m-d H:i:s')]);

    //         $imports = true;
    //         cashbook_reconcile($cashbook->id);
    //     } else {
    //         return json_alert('Api error, no data returned','error');
    //     }
    // }
    // if($imports){
    //     allocate_bank_transactions();
    return json_alert('Transactions imported', 'success');
    // }else{
    //     return json_alert('No new transactions from api','error');
    // }
}

// https://github.com/bitshiftza/fnb-api
function aftersave_cashbook_encode_fnb_pass($request)
{
    if (!empty($request->fnb_password)) {
        \DB::table('acc_cashbook')->where('id', $request->id)->update(['fnb_password' => \Erp::encode($request->fnb_password)]);
    }
}

function aftersave_cashbook_create_ledger_account($request)
{
    if (!empty($request->new_record)) {
        $data = [
            'name' => $request->name,
            'ledger_account_category_id' => 31,
        ];

        $ledger_account_id = dbinsert('acc_ledger_accounts', $data);

        \DB::table('acc_cashbook')->where('id', $request->id)->update(['ledger_account_id' => $ledger_account_id]);
    }
}

function fnb_get_transactions($cashbook)
{
    // nvm install 17.9.1
    // nvm alias default 17.9.1
    // nvm use 17.9.1
    // apt install -y libx11-xcb1 libxcomposite1 libxcursor1 libxdamage1 libxi-dev libxtst-dev libnss3 libxss1 libxrandr2 libatk1.0-0 libatk-bridge2.0-0 libpangocairo-1.0-0 libgtk-3-bin libgbm1

    $fnb_username = $cashbook->fnb_username;
    $fnb_password = \Erp::decode($cashbook->fnb_password);
    $fnb_account_no = $cashbook->fnb_account_no;

    $cmd = "cd /home/erpcloud-live/htdocs/erp/zadmin/fnb_api && node fnb-get-transactions '".$fnb_username."' '".$fnb_password."' '".$fnb_account_no."';";
    // dd($cmd);
    $transactions = \Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    $pos = strpos($transactions, '[{');
    $transactions = substr($transactions, $pos, strlen($transactions));
    $transactions = json_decode(trim($transactions));
    // dd($transactions);
    return $transactions;
}
