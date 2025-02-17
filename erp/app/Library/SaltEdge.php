<?php

class SaltEdge extends ApiCurl
{
    public function __construct($debug = false)
    {
        $this->service_url = 'https://www.saltedge.com/api/v5/';
        $this->app_id = 'D4EFhi7rdvdJX8dUxxUoHbYq2SzVha5Tx0wGLLLYwNw';
        $this->secret = 'ODRjeuz8ppgH_hiOYEZiYNCAX9lDRAtbi6bfrOJ0ce8';
        $this->debug = $debug;
    }
    
    public function getCustomers()
    {
        return $this->curl('customers');
    }
    
    public function postCustomer($identifier)
    {
        $data['data'] = ['identifier' => $identifier];
        return $this->curl('customers', $data, 'post');
    }
    
    public function getOrCreateCustomer($customer_identifier)
    {
        $customer_data = $this->getCustomers();
        foreach ($customer_data->data as $c) {
            if ($c->identifier == $customer_identifier) {
                $customer_id = $c->id;
            }
        }
        if ($customer_id) {
            return $customer_id;
        }
        $result = $this->postCustomer($customer_identifier);
        return $result->data->id;
    }
    
    public function setCustomerID($customer_id)
    {
        $this->customer_id = $customer_id;
    }
    
    public function getConnections()
    {
        return $this->curl('connections', ['customer_id'=>$this->customer_id]);
    }
    
    public function getProviders($country_code = false)
    {
        if ($country_code) {
            return  $this->curl('providers', ['country_code' => $country_code]);
        }
        return $this->curl('providers');
    }
    
    public function oauthConnect($customer_identifier, $country_code, $provider_code, $from_date)
    {
        $customer_id = $this->getOrCreateCustomer($customer_identifier);
        $data['data'] = [
            'customer_id' => $customer_id,
            'country_code' => $country_code,
            'provider_code' => $provider_code,
            'consent' =>  [
                'from_date' => $from_date,
                'scopes' => [
                    'account_details',
                    'transactions_details'
                ]
            ],
            'attempt' => [
                'return_to' => url(),
                'from_date' => $from_date,
                'fetch_scopes' => [
                    'accounts',
                    'transactions'
                ],
            ],
        ];
      
        return $this->curl('oauth_providers/create', $data, 'post');
    }
    
    public function setupConnection($customer_identifier, $country_code, $provider_code, $from_date, $username, $password)
    {
        $customer_id = $this->getOrCreateCustomer($customer_identifier);
        $data['data'] = [
            'customer_id' => $customer_id,
            'country_code' => $country_code,
            'provider_code' => $provider_code,
            'consent' =>  [
                'from_date' => $from_date,
                'scopes' => [
                    'account_details',
                    'transactions_details'
                ]
            ],
            'attempt' => [
                'from_date' => $from_date,
                'fetch_scopes' => [
                    'accounts',
                    'transactions'
                ],
            ],
            'credentials' => [
                'login' => $username,
                'password' => $password
            ]
        ];
      
        return $this->curl('connections', $data, 'post');
    }
    
    public function setConnectionID($connection_id)
    {
        $this->connection_id = $connection_id;
    }
    
    public function getAccounts()
    {
        if (empty($this->connection_id)) {
            return 'Connection ID not set';
        }
        return $this->curl('accounts', ['connection_id'=>$this->connection_id]);
    }
    
    public function setAccountID($account_id)
    {
        $this->account_id = $account_id;
    }
    
    public function setConnectionFromID($id)
    {
        $connection_data = \DB::connection('default')->table('acc_bank_connections')->where('id', $id)->get()->first();
        if (!$connection_data || empty($connection_data)) {
            return false;
        }
        
        if (empty($connection_data->customer_id)) {
            return false;
        }
        
        if (empty($connection_data->connection_id)) {
            return false;
        }
        
        if (empty($connection_data->bank_account_id)) {
            return false;
        }
        
        $this->setCustomerID($connection_data->customer_id);
        $this->setConnectionID($connection_data->connection_id);
        $this->setAccountID($connection_data->bank_account_id);
        return $this;
    }
    
    public function refreshConnection()
    {
        $data['data'] = [
            'daily_refresh' => true,
        ];
      
        $refresh_result =  $this->curl('connections/'.$this->connection_id.'/refresh', $data, 'put');
        $this->refresh_result = $refresh_result;
        return $refresh_result;
    }
    
    public function getTransactions($from_id = false)
    {
        if (empty($this->account_id)) {
            return 'Account ID not set';
        }
        if (empty($this->connection_id)) {
            return 'Connection ID not set';
        }
        
        $args = ['connection_id'=>$this->connection_id,'account_id'=>$this->account_id];
        if ($from_id) {
            $args['from_id'] = $from_id;
        }
      
        return $this->curl('transactions', $args);
    }
    
    
    public function getPendingTransactions($from_id = false)
    {
        if (empty($this->account_id)) {
            return 'Account ID not set';
        }
        if (empty($this->connection_id)) {
            return 'Connection ID not set';
        }
        
        $args = ['connection_id'=>$this->connection_id,'account_id'=>$this->account_id];
        if ($from_id) {
            $args['from_id'] = $from_id;
        }
        
        return $this->curl('transactions/pending', $args);
    }
    
    
    public function getDuplicateTransactions($from_id = false)
    {
        if (empty($this->account_id)) {
            return 'Account ID not set';
        }
        if (empty($this->connection_id)) {
            return 'Connection ID not set';
        }
        
        $args = ['connection_id'=>$this->connection_id,'account_id'=>$this->account_id];
        if ($from_id) {
            $args['from_id'] = $from_id;
        }
        
        return $this->curl('transactions/duplicates', $args);
    }
    
    
    public function fixBankReferences()
    {
        $bank = \DB::connection('default')->table('acc_register_bank')->get();
        foreach ($bank as $b) {
            $ref = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $b->reference)));
            $ref = str_replace('&amp;', '&', $ref);
            \DB::connection('default')->table('acc_register_bank')->where('id', $b->id)->update(['reference' => $ref]);
        }
        $refs = \DB::connection('default')->table('acc_bank_references')->get();
        foreach ($refs as $b) {
            $ref = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $b->reference)));
            $ref = str_replace('&amp;', '&', $ref);
            \DB::connection('default')->table('acc_bank_references')->where('id', $b->id)->update(['reference' => $ref]);
        }
    }
    
    public function bankImport()
    {
        $imported_transactions = 0;
        $unallocated_transations = 0;
        $this->refreshConnection();
        $this->fixBankReferences();
        if (empty($this->account_id)) {
            return 'Account ID not set';
        }
        
        if (empty($this->connection_id)) {
            return 'Connection ID not set';
        }
        $standard_import = \DB::connection('default')->table('acc_register_bank')->where('salt_edge_id', '>', '')->count();
        $transaction_count = \DB::connection('default')->table('acc_register_bank')->count();
   
        if ($transaction_count <= 1) {
            $standard_import = 1;
        }
        
        if (!$standard_import) {
            // intital import only updates existing records
            // match transactions on date, amount, ref
            $next_id = 'start';
           
            while ($next_id != null) {
                if ($next_id == 'start') {
                    $transactions = $this->getTransactions();
                } else {
                    $transactions = $this->getTransactions($next_id);
                }
                $trx_count = count($transactions->data);
                foreach ($transactions->data as $trx) {
                    $trx_count =  \DB::connection('default')->table('acc_register_bank')
                        ->where('docdate', $trx->made_on)
                        ->where('reference', $trx->description)
                        ->where('total', currency($trx->amount))
                        ->count();
                
                    if ($trx_count === 1) {
                        \DB::connection('default')->table('acc_register_bank')
                            ->where('docdate', $trx->made_on)
                            ->where('reference', $trx->description)
                            ->where('total', currency($trx->amount))
                            ->update(['salt_edge_id' => $trx->id]);
                    }
                }
                $next_id = $transactions->meta->next_id;
            }
            
            // match pending transactions
            $next_id = 'start';
           
            while ($next_id != null) {
                if ($next_id == 'start') {
                    $transactions = $this->getPendingTransactions();
                } else {
                    $transactions = $this->getPendingTransactions($next_id);
                }
                $trx_count = count($transactions->data);
                foreach ($transactions->data as $trx) {
                    $trx_count =  \DB::connection('default')->table('acc_register_bank')
                        ->where('docdate', $trx->made_on)
                        ->where('reference', $trx->description)
                        ->where('total', currency($trx->amount))
                        ->count();
                
                    if ($trx_count === 1) {
                        \DB::connection('default')->table('acc_register_bank')
                            ->where('docdate', $trx->made_on)
                            ->where('reference', $trx->description)
                            ->where('total', currency($trx->amount))
                            ->update(['salt_edge_id' => $trx->id]);
                    }
                }
                $next_id = $transactions->meta->next_id;
            }
        }
      
        if ($standard_import) {
          
            // standard import, inserts new records and allocates transactions
            $last_import_date =  \DB::connection('default')->table('acc_register_bank')->where('reconciled', 1)->where('salt_edge_id', '>', '')->orderby('docdate', 'desc')->pluck('docdate')->first();
           
            $from_id = \DB::connection('default')->table('acc_register_bank')->where('salt_edge_id', '>', '')->where('docdate', $last_import_date)->orderby('salt_edge_id', 'asc')->pluck('salt_edge_id')->first();
        
            $transactions = $this->getTransactions($from_id);
           
            $transactions = collect($transactions->data);
            $pending_transactions = $this->getPendingTransactions($from_id);
            if (is_array($pending_transactions->data) && count($pending_transactions->data) > 0) {
                $pending_transactions = collect($pending_transactions->data);
         
                $transactions = $transactions->merge($pending_transactions);
            }
            $transactions = collect($transactions)->sortBy('id');
            
            foreach ($transactions as $trx) {
                if ($trx->category == "provider_fee" && !str_starts_with($trx->description, "#")) {
                    continue;
                }
                
                $closing_balance =  $trx->extra->closing_balance;
                if (!empty($closing_balance)) {
                    // check if salt_edge_id exists
                    $exists = \DB::connection('default')->table('acc_register_bank')
                        ->where('docdate', $trx->made_on)
                        ->where('reference', $trx->description)
                        ->where('total', currency($trx->amount))->count();
                  
                    if (!$exists) {
                        $data = [
                            'docdate' => $trx->made_on,
                            'reference' => $trx->description,
                            'total' => $trx->amount,
                            'salt_edge_id' => $trx->id,
                        ];
                        \DB::connection('default')->table('acc_register_bank')->insert($data);
                        $imported_transactions++;
                    }
                } else {
                    $unallocated_transations++;
                }
            }
        }
        
        $api_payments = \DB::table('acc_register_payment_gateways')->get();
        foreach ($api_payments as $api_payment) {
            \DB::table('acc_register_bank')->where('payment_id', $api_payment->payment_id)->whereNull('api_id')->update(['api_id' => $api_payment->id]);
        }
    
        if ($imported_transactions > 0) {
            return true;
        } elseif ($unallocated_transations > 0) {
            return 'Unallocated transactions: '.$unallocated_transations;
        } else {
            return $this->refresh_result->next_refresh_possible_at;
        }
    }
    
    public function bankReconcile($from_id = false)
    {
        rebuild_balances('acc_register_bank');
        if (!$from_id) {
            $last_import_date =  \DB::connection('default')->table('acc_register_bank')->where('reconciled', 1)->where('salt_edge_id', '>', '')->orderby('docdate', 'desc')->pluck('docdate')->first();
           
            $from_id = \DB::connection('default')->table('acc_register_bank')->where('salt_edge_id', '>', '')->where('docdate', $last_import_date)->orderby('salt_edge_id', 'asc')->pluck('salt_edge_id')->first();
        }
     
        $transactions = $this->getTransactions($from_id);
        $transactions = collect($transactions->data);
        $pending_transactions = $this->getPendingTransactions($from_id);
        if (is_array($pending_transactions->data) && count($pending_transactions->data) > 0) {
            $pending_transactions = collect($pending_transactions->data);
            $transactions = $transactions->merge($pending_transactions);
        }
        $transactions = collect($transactions)->sortBy('id');
      
        foreach ($transactions as $trx) {
            if ($trx->category == "provider_fee" && !str_starts_with($trx->description, "#")) {
                continue;
            }
            $closing_balance =  $trx->extra->closing_balance;
            if (!empty($closing_balance)) {
                // check if salt_edge_id exists
                $db_trx = \DB::connection('default')->table('acc_register_bank')
                    ->where('docdate', $trx->made_on)
                    ->where('balance', currency($closing_balance))->get()->first();
                if (!empty($db_trx) && !empty($db_trx->id)) {
                    \DB::connection('default')->table('acc_register_bank')->where('id', '<=', $db_trx->id)->update(['reconciled' => 1]);
                }
            }
        }
    }
    
    protected function setCurlAuth($api_request)
    {
        $api_request->addHeader('App-id', $this->app_id);
        $api_request->addHeader('Secret', $this->secret);
        return $api_request;
    }
}