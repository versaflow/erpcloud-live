<?php

class Yodlee extends ApiCurl
{
    public function __construct($mode = 'production', $debug = false)
    {
        $this->setMode($mode);
        $this->debug = $debug;
    }

    private function setMode($mode)
    {
        if ($mode == 'sandbox') {
            $this->service_url = 'https://sandbox.api.yodlee.com/ysl/';
            $this->fastlink_url = 'https://fl4.sandbox.yodlee.com/authenticate/restserver/fastlink';

            $this->adminName = 'a698476b-81f0-455d-82f7-8b47bec8427a_ADMIN';
            $this->client_id = 'YmZ4aIHmGWjodmYHAspcZkzicDEmOwA4';
            $this->client_secret = 'Ch9z8AmAmOffiB5e';

            $this->setLoginName('sbMem5f2d56c84f5ca1'); // sandbox user
        }
        if ($mode == 'development') {
            $this->service_url = 'https://development.api.yodlee.com/ysl/';
            $this->fastlink_url = 'https://fl4.preprod.yodlee.com/authenticate/USDevexPreProd4-1/fastlink?channelAppName=usdevexpreprod4';

            $this->adminName = '86decd7c-c822-4f2d-a49a-adc0b2479654_ADMIN';
            $this->client_id = 'JyttoJBKl9pOtB7N75ulvpAQVOje9kQd';
            $this->client_secret = 'kKKlfXgE9clFrjZr';
        }
        if ($mode == 'production') {
            $this->service_url = 'https://production.api.yodlee.com/ysl/';
            $this->fastlink_url = 'https://fl4.prod.yodlee.com/authenticate/USDevexProd4-83/fastlink?channelAppName=usdevexprod4';

            $this->adminName = '32b70a52-c3cd-4f4b-8bc0-b4130aa9d1fb_ADMIN';
            $this->client_id = 'Ahh8RI7HAMXoz2pCHAZxG6HqoBwORjDU';
            $this->client_secret = 'UEGxZqwtgcgnGUxw';
        }
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setLoginName($loginName)
    {
        $this->loginName = $loginName;
    }

    public function addUser($login_name, $email = false, $currency = false, $locale = false, $first_name = false, $last_name = false)
    {
        $data = [
            'user' => [
                'loginName' => $login_name,
            ],
        ];

        if ($email) {
            $data['user']['email'] = $email;
        }

        if ($currency && $locale) {
            $data['user']['preferences'] = [
                'currency' => $currency,
                'locale' => $locale,
            ];
        }

        if ($first_name && $last_name) {
            $data['user']['name'] = [
                'first' => $first_name,
                'last' => $last_name,
            ];
        }

        $user = json_encode($data);

        return $this->curl('user/register', $user, 'post');
    }

    public function getUser($loginName)
    {
        $this->setLoginName($loginName);

        return $this->curl('user');
    }

    public function deleteUser($loginName)
    {
        $this->setLoginName($loginName);

        return $this->curl('user/unregister', false, 'delete');
    }

    public function getAccounts()
    {
        return $this->curl('accounts');
    }

    public function getAccount()
    {
        return $this->curl('accounts/'.$id);
    }

    public function updateAccount($id)
    {
        return $this->curl('accounts/'.$id, false, 'put');
    }

    public function deleteAccount($id)
    {
        return $this->curl('accounts/'.$id, false, 'delete');
    }

    public function getTransactions($params = false)
    {
        if ($params) {
            return $this->curl('transactions', $params);
        } else {
            return $this->curl('transactions');
        }
    }

    public function getTransactionsByDate($date)
    {
        $transaction_params = [
            'container' => 'bank',
            'fromDate' => $date,
            'toDate' => $date,
        ];

        return $this->curl('transactions', $transaction_params);
    }

    public function getTransactionsFromDate($date, $yodlee_account_id = false)
    {
        $transaction_params = [
            'container' => 'bank',
            'fromDate' => $date,
        ];
        if ($yodlee_account_id) {
            $transaction_params['accountId'] = $yodlee_account_id;
        }

        return $this->curl('transactions', $transaction_params);
    }

    public function getTransactionsRange($date, $todate, $yodlee_account_id = false)
    {
        $transaction_params = [
            'container' => 'bank',
            'fromDate' => $date,
            'toDate' => $todate,
        ];
        if ($yodlee_account_id) {
            $transaction_params['accountId'] = $yodlee_account_id;
        }

        return $this->curl('transactions', $transaction_params);
    }

    public function getProviders()
    {
        return $this->curl('providers', ['priority' => 'popular']);
    }

    public function getProvider($provider_id)
    {
        return $this->curl('providers/'.$provider_id);
    }

    public function getProviderAccounts()
    {
        return $this->curl('providerAccounts', ['include' => 'preferences']);
    }

    public function updateProviderAccounts($providerAccountId)
    {
        $provider_params = [
            'preferences' => ['isAutoRefreshEnabled' => 'true'],
        ];

        return $this->curl('providerAccounts?providerAccountIds='.$providerAccountId, false, 'put');
    }

    public function deleteProviderAccount($providerAccountId)
    {
        return $this->curl('providerAccounts/'.$providerAccountId, false, 'delete');
    }

    protected function setCurlAuth($api_request)
    {
        $valid_token = false;

        if (! empty($this->token)) {
            $expires_at = date('Y-m-d H:i', strtotime($this->token->issuedAt) + $this->token->expiresIn);
            if (date('Y-m-d H:i') < $expires_at) {
                $valid_token = true;
            }
        }

        if (! $valid_token) {
            $token_body = [
                'clientId' => $this->client_id,
                'secret' => $this->client_secret,
            ];

            if (empty($this->loginName)) {
                $token_headers = ['Api-Version' => 1.1, 'loginName' => $this->adminName];
            } else {
                $token_headers = ['Api-Version' => 1.1, 'loginName' => $this->loginName];
            }

            $token_request = \Httpful\Request::post($this->service_url.'auth/token');

            $token_response = $token_request->body($token_body)
                ->addHeaders($token_headers)
                ->sendsType(\Httpful\Mime::FORM)
                ->withoutStrictSsl()
                ->send();
            //dd($token_response);
            if (! empty($token_response->body->errorCode)) {

                //dd($token_body,$token_headers,$token_response);
                throw new \ErrorException('Error code: '.$token_response->body->errorCode);
            }
            if (! empty($token_response->body->errorMessage)) {
                throw new \ErrorException('Error message: '.$token_response->body->errorMessage);
            }
            if (empty($token_response->body->token)) {
                throw new \ErrorException('Token could not be generated.');
            } else {
                $this->token = $token_response->body->token;
            }

            $api_request->addHeaders(['Api-Version' => 1.1]);
            $api_request->withAuthorization('Bearer '.$this->token->accessToken);
        } else {
            $api_request->addHeaders(['Api-Version' => 1.1]);
            $api_request->withAuthorization('Bearer '.$this->token->accessToken);
        }

        if (! empty($this->locale)) {
            $api_request->addHeaders(['locale' => $this->locale]);
        }

        return $api_request;
    }

    public function import($from_date = false, $to_date = false, $restrict_yodlee_account_id = false)
    {
        //return false;
        try {
            $register_table = 'acc_cashbook_transactions';
            $yodlee_accounts = $this->getAccounts();
            $yodlee_accounts = collect($yodlee_accounts->account);

            foreach ($yodlee_accounts as $yodlee_account) {
                if ($restrict_yodlee_account_id && $yodlee_account->id != $restrict_yodlee_account_id) {
                    continue;
                }

                $cashbook = \DB::table('acc_cashbook')->where('yodlee_account_id', $yodlee_account->id)->get()->first();
                $transaction_params = [
                    'container' => 'bank',
                ];

                if ($from_date) {
                    //$from_date = '2021-04-17';
                    $transaction_params['fromDate'] = $from_date;
                }

                if ($to_date) {
                    $transaction_params['toDate'] = $to_date;
                }

                $transaction_params['accountId'] = $yodlee_account->id;

                $yodlee_count = \DB::connection('default')->table($register_table)->where('cashbook_id', $cashbook->id)->where('api_id', '!=', 0)->count();
                if ($yodlee_count == 0) {
                    $transaction_params['fromDate'] = date('Y-m-d', strtotime('-1 month'));
                }

                $transactions = $this->getTransactions($transaction_params);

                $transactions_processed = collect([]);
                if (! empty($transactions) && ! empty($transactions->transaction)) {
                    $transactions = $transactions->transaction;

                    $transactions = array_reverse($transactions);
                    $transactions_collection = collect([]);

                    foreach ($transactions as $trx) {
                        $unique_duplicate = false;

                        $yodlee_account_id = $yodlee_account->id;
                        $document_currency = $yodlee_account->balance->currency;

                        if (empty($document_currency)) {
                            continue;
                        }

                        if ($trx->amount->amount == 0) {
                            continue;
                        }

                        if ($trx->status != 'POSTED') {
                            continue;
                        }

                        foreach ($transactions_processed as $processed) {
                            if ($processed->id == $trx->id) {
                                continue 2;
                            }
                        }
                        if (str_contains($trx->description->original, 'Forex') && str_contains($trx->description->original, 'Fee')) {
                            continue;
                        }

                        $transactions_processed[] = $trx;
                        $data = [
                            'cashbook_id' => $cashbook->id,
                            'docdate' => $trx->transactionDate,
                            'reference' => $trx->description->original,
                            'total' => $trx->amount->amount,
                            'api_id' => $trx->id,
                            'api_balance' => $trx->runningBalance->amount,
                            'api_status' => $trx->status,
                            'api_data' => json_encode($trx),
                            'document_currency' => $document_currency,
                        ];

                        if ($transactions_collection->count() > 0) {
                            $duplicate = $transactions_collection
                                ->where('docdate', $data['docdate'])
                                ->where('reference', $data['reference'])
                                ->where('total', $data['total'])
                                ->count();

                            if ($duplicate) {
                                $unique_duplicate = true;

                                $data['reference'] = $data['reference'].date('i_s');
                                $trx->description->original = $data['reference'];
                                sleep(1);
                            }
                        }
                        $transactions_collection->push($data);

                        if (empty($data['api_balance'])) {
                            $data['api_balance'] = 0;
                        }

                        if ($trx->baseType == 'DEBIT') {
                            $data['total'] = $data['total'] * -1;
                        }

                        $ofx_exists = \DB::connection('default')->table($register_table)
                            ->where('cashbook_id', $cashbook->id)
                            ->where('docdate', $trx->transactionDate)
                            ->where('reference', $trx->description->original)
                            ->where('api_status', '')
                            ->where('total', currency($data['total']))->count();
                        $exists = \DB::connection('default')->table($register_table)
                            ->where('cashbook_id', $cashbook->id)
                            ->where('docdate', $trx->transactionDate)
                            ->where('reference', $trx->description->original)
                            ->whereIn('api_status', ['PENDING', 'POSTED'])
                            ->where('total', currency($data['total']))->count();
                        $api_id_exists = \DB::connection('default')->table($register_table)
                            ->where('cashbook_id', $cashbook->id)
                            ->where('api_id', $trx->id)->count();
                        $bank_id = \DB::connection('default')->table($register_table)
                            ->where('cashbook_id', $cashbook->id)
                            ->where('docdate', $trx->transactionDate)
                            ->where('reference', $trx->description->original)
                            ->whereIn('api_status', ['PENDING', 'POSTED'])
                            ->where('total', currency($data['total']))->pluck('id')->first();
                        $prepayment_id = \DB::connection('default')->table($register_table)
                            ->where('cashbook_id', $cashbook->id)
                            ->where('docdate', $trx->transactionDate)
                            ->where('prepayment', 1)
                            ->where('total', currency($data['total']))->pluck('id')->first();
                        $trx_exists = \DB::connection('default')->table($register_table)
                            ->where('cashbook_id', $cashbook->id)
                            ->where('docdate', $trx->transactionDate)
                            ->where('reference', $trx->description->original)
                            ->where('total', currency($data['total']))->count();

                        if ($prepayment_id) {

                            $data['prepayment'] = 0;
                            \DB::connection('default')->table($register_table)
                                ->where('cashbook_id', $cashbook->id)
                                ->where('id', $prepayment_id)
                                ->update($data);
                        } elseif ($bank_id) {
                            unset($data['reference']);
                            \DB::connection('default')->table($register_table)
                                ->where('cashbook_id', $cashbook->id)
                                ->where('id', $bank_id)
                                ->update($data);
                        } elseif ($api_id_exists) {
                            unset($data['reference']);
                            \DB::connection('default')->table($register_table)
                                ->where('cashbook_id', $cashbook->id)
                                ->where('api_id', $trx->id)
                                ->update($data);
                        } elseif (! $bank_id && ! $ofx_exists && ! $api_id_exists) {
                            $exists = \DB::connection('default')->table($register_table)
                                ->where('docdate', $trx->transactionDate)
                                ->where('cashbook_id', $cashbook->id)
                                ->where('api_id', $trx->id)
                                ->where('total', currency($data['total']))->count();

                            if (! $trx_exists && ! $exists) {
                                \DB::connection('default')
                                    ->table($register_table)
                                    ->insert($data);
                            }

                            if ($trx_exists && $unique_duplicate && ! $exists) {
                                \DB::connection('default')
                                    ->table($register_table)
                                    ->insert($data);
                            }
                        }
                    }
                }

                $bank_trxs = \DB::table('acc_cashbook_transactions')->where('cashbook_id', $cashbook->id)->where('api_id', '>', '')->orderBy('id', 'desc')->get();
                $processed_ids = [];
                foreach ($bank_trxs as $trx) {
                    $c = \DB::table('acc_cashbook_transactions')->where('cashbook_id', $cashbook->id)->where('id', '!=', $trx->id)->where('api_id', $trx->api_id)->count();
                    if ($c && ! in_array($trx->api_id, $processed_ids)) {
                        $processed_ids[] = $trx->api_id;
                        if ($trx->api_id > '') {
                            $duplicates = \DB::table('acc_cashbook_transactions')->where('cashbook_id', $cashbook->id)->where('id', '!=', $trx->id)->where('api_id', $trx->api_id)->get();
                            foreach ($duplicates as $duplicate) {
                                \DB::table('acc_cashbook_transactions')->where('api_id', '!=', 0)->where('api_id', '>', '')->where('id', $duplicate->id)->delete();
                                \DB::table('acc_cashbook_transactions')->where('cashbook_transaction_id', $duplicate->id)->delete();
                            }
                        }
                    }
                }
            }

            $cashbooks = \DB::table('acc_cashbook')->where('yodlee_account_id', '>', '')->get();
            foreach ($cashbooks as $cashbook) {
                cashbook_reconcile($cashbook->id);
            }

            return true;
        } catch (\Throwable $ex) {
            exception_log($ex);

            return false;
        }
    }
}
