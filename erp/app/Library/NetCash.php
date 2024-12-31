<?php

// https://api.netcash.co.za/integration/Inbound%20Payments/debit_order
/*
https://ws.netcash.co.za/NIWS/niws_validation.svc
https://ws.netcash.co.za/NIWS/niws_nif.svc
https://ws.netcash.co.za/NIWS/niws_help.svc
*/

class NetCash
{
    public $service_key; // debit order service key

    public $account_service_key;

    public $software_vendor_code;

    public $storage_dir;

    public $header_record;

    public $key_record;

    public $batch_date;

    public $limit_id;

    public function __construct($batch_date = false)
    {

        $payment_option = get_payment_option('NetCash');

        $enabled = $payment_option->enabled;
        if (! $enabled) {
            return false;
        }
        $this->batch_date = (! $batch_date) ? date('YmdHi') : date('YmdHi', strtotime($batch_date));
        $this->service_key = $payment_option->netcash_service_key;
        $this->account_service_key = $payment_option->netcash_account_service_key;
        $this->software_vendor_code = $payment_option->netcash_software_vendor_code;
        $this->storage_dir = storage_path('debit_orders');
        $this->setHeaderRecord('Cloudtel'.$this->batch_date, 'Twoday', 1);
        $this->setKeyRecord([101, 102, 131, 132, 133, 134, 135, 136, 162]);
    }

    public function setActionDate($date, $type = 'Twoday')
    {
        $this->action_date = date('Ymd', strtotime($date));
        $this->setHeaderRecord('Cloudtel'.$this->batch_date, $type, 1);
    }

    public function setLimitID($debit_id)
    {
        $this->limit_id = $debit_id;
    }

    public function setAccountLimit($account_id)
    {
        $this->limit_account_id = $account_id;
    }

    public function setAmountLimit($amount_limit)
    {
        $this->amount_limit = $amount_limit;
    }

    public function setHeaderRecord($batch_name, $instruction, $version)
    {
        /*
        1	Record Identifier	AN	H
        2	Service Key	AN	Debit Order Service Key
        3	Version	AN	1
        4	Instruction	AN	Purpose of the file
        5	Batch name	AN	Your identifier
        6	Action date	N	CCYYMMDD
        7	Software vendor code	AN	The key issued by Sage Pay to identify the software origin of transactions. (only used by Sage Pay business partners else use default value: 24ade73c-98cf-47b3-99be-cc7b867b3080)
        */
        if (! empty($this->action_date)) {
            $action_date = $this->action_date;
        } else {
            $action_date = date('Ymd', strtotime('+5 day'));
        }

        $this->header_record = "H\t".$this->service_key."\t".$version."\t".$instruction."\t".$batch_name."\t".$action_date."\t".$this->software_vendor_code;
    }

    public function setKeyRecord($keys)
    {
        /*
        101	Account reference
        102	Account name
        103	Account active
        104	Delete this account
        131	Banking detail type
        132	Bank account name/Credit card holder name as it appears on the front of the card
        133	Bank account type/Credit card type
        134	Branch code/Expiry month
        135	Filler/Expiry year
        136	Bank account number/Credit card token
        137	Masked credit card number
        161	Default debit amount
        162	Amount
        201	Email address
        202	Mobile number
        281	Debit masterfile group
        301	Extra 1
        302	Extra 2
        303	Extra 3
        */
        $this->key_record = "K\t".implode("\t", $keys);
    }

    public function setTransactionRecord($debit_order, $amount)
    {
        if ($debit_order->bank_account_type == 'Current') {
            $account_type = 1;
        } elseif ($debit_order->bank_account_type == 'Savings') {
            $account_type = 2;
        } elseif ($debit_order->bank_account_type == 'Transmisson') {
            $account_type = 3;
        } elseif ($debit_order->bank_account_type == 'Bond') {
            $account_type = 4;
        }
        $record = "T\t";
        $record .= $debit_order->account_id."\t"; //101
        $record .= str_limit($debit_order->company, 25)."\t"; //102
        $record .= "1\t"; //131 Banking detail type
        $record .= str_limit($debit_order->bank_name, 30)."\t"; //132 Bank account name
        $record .= $account_type."\t"; //133 Account Type
        $record .= str_limit($debit_order->bank_branch_code, 6)."\t"; //134 Bank branch
        $record .= "0\t"; //135 Filler
        $record .= str_limit($debit_order->bank_account_number, 11)."\t"; //136 Bank account number
        $record .= abs($amount)."\t"; //162 Amount

        return $record.PHP_EOL;
    }

    public function generate($storage_file)
    {
        /*
            H  78e30d1e-aa36-0000-XXXX-dcd68866XXXX 1  TwoDay   Batch_Name  20131204  24ade73c-98cf-XXXX-XXXX-cc7b867b3080
            K  101   102   131   132   133   134   135   136   162
            T  CUS001    A Customer  1  AB Customer    1  632005  0    40600000004 110200
            T  CUS002    X Customer  1  X Customer  2  198765  0    2060000001   200000
            F  2  310200  9999
        */
        if (! empty($this->limit_id)) {
            $debit_orders = \DB::table('acc_debit_orders as d')
                ->join('crm_accounts as a', 'a.id', '=', 'd.account_id')
                ->select('d.*', 'a.company', 'a.balance')
                ->where('d.validated', 1)
                ->where('d.id', $this->limit_id)
                ->where('a.partner_id', 1)
                ->where('d.status', 'Enabled')
                ->where('a.status', '!=', 'Deleted')
                ->where('a.account_status', '!=', 'Cancelled')
                ->get();
        } elseif (! empty($this->limit_account_id)) {
            $debit_orders = \DB::table('acc_debit_orders as d')
                ->join('crm_accounts as a', 'a.id', '=', 'd.account_id')
                ->select('d.*', 'a.company', 'a.balance')
                ->where('d.validated', 1)
                ->where('a.id', $this->limit_account_id)
                ->where('a.partner_id', 1)
                ->where('d.status', 'Enabled')
                ->where('a.status', '!=', 'Deleted')
                ->where('a.account_status', '!=', 'Cancelled')
                ->get();
        } else {
            $debit_orders = \DB::table('acc_debit_orders as d')
                ->join('crm_accounts as a', 'a.id', '=', 'd.account_id')
                ->select('d.*', 'a.company', 'a.balance')
                ->where('d.validated', 1)
                ->where('a.partner_id', 1)
                ->where('d.status', 'Enabled')
                ->where('a.status', '!=', 'Deleted')
                ->where('a.account_status', '!=', 'Cancelled')
                ->get();
        }

        // check cancel date
        if (empty($debit_orders)) {
            return false;
        }
        $transactions_count = 0;
        $transactions_total = 0;
        $file = '';

        // Header Record
        $file .= $this->header_record.PHP_EOL;

        // Key Record
        $file .= $this->key_record.PHP_EOL;

        //$sub = new \ErpSubs();
        // Transaction Records
        foreach ($debit_orders as $debit_order) {
            (new DBEvent)->setAccountAging($debit_order->account_id);
            //$sub->updateSubscriptionsTotal($debit_order->account_id);
            $account = dbgetaccount($debit_order->account_id);

            // if schedule set before monthly billing invoices
            /*
            $next_month_billing = \DB::table('crm_documents')
            ->where('account_id',$debit_order->account_id)
            ->where('billing_type','Monthly')
            ->where('docdate','like',date('Y-m',strtotime('first day of +1 month')).'%')
            ->count();
            if($next_month_billing){
                $amount = 0;
            }else{
                $amount = abs($account->subs_total);
                $amount_tax = $amount;
                $amount = $amount + $amount_tax;
            }
            */

            // debit orders processed after monthly billing approved
            $amount = 0;

            if (! empty($this->limit_id)) {
                $pending_total = \DB::table('sub_services as s')
                    ->join('crm_documents as d', 's.invoice_id', '=', 'd.id')
                    ->where('d.doctype', 'Order')
                    ->where('s.account_id', $account->id)
                    ->where('s.status', 'Pending')
                    ->sum('s.price_incl');

                if (! empty($pending_total)) {
                    $amount += $pending_total;
                }
            }
            $account_balance = account_get_full_balance($debit_order->account_id);

            if ($account_balance < 0) {
                $amount -= abs($account_balance);
            }
            if ($account_balance > 0) {
                $amount += $account_balance;
            }

            if ($this->amount_limit && $this->amount_limit < $amount) {
                $amount = $this->amount_limit;
            }

            if (! empty($this->limit_account_id) && $amount <= 0) {
                $amount = 100; // debit R100 if used for activation
            }

            if ($amount > 50) {
                $amount = currency($amount) * 100; // amount in cents;
                $file .= $this->setTransactionRecord($debit_order, $amount);
                $transactions_total += abs($amount);
                $transactions_count++;
            }
        }
        // Footer Record
        /*
        1	Record Identifier	AN	F
        2	No of transactions	N	A count of the transaction records
        3	Sum of amounts	N	The sum of the monetary fields (in cents)
        4	End-of-file indicator	N	9999
        */
        $file .= "F\t".$transactions_count."\t".$transactions_total."\t9999";

        $filename = $storage_file;

        \Storage::disk('debit_orders')->put($filename, $file);

        return $transactions_total;
    }

    public function upload()
    {
        /*
        Web service: https://ws.netcash.co.za/NIWS/niws_nif.svc
        Method: BatchFileUpload
        Service key: Debit order service key
        */

        $filename = $this->batch_date.'.txt';

        $file = \Storage::disk('debit_orders')->get($filename);

        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/niws_nif.svc?wsdl');
        $result = $client->BatchFileUpload(['ServiceKey' => $this->service_key, 'File' => $file]);
        $filename = $this->batch_date.'result.txt';

        \Storage::disk('debit_orders')->put($filename, $result->BatchFileUploadResult);
    }

    public function report()
    {
        /*
        Web service: https://ws.netcash.co.za/NIWS/niws_nif.svc
        Method: RequestFileUploadReport
        Service key: Debit Order Service key
        */

        $filename = $this->batch_date.'result.txt';
        $file = \Storage::disk('debit_orders')->get($filename);
        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/niws_nif.svc?wsdl');

        $result = $client->RequestFileUploadReport(['ServiceKey' => $this->service_key, 'FileToken' => $file]);

        return $result;
    }

    public function validateBankAccount($debit_order)
    {
        /*
        https://ws.netcash.co.za/NIWS/NIWS_Validation.svc?wsdl
        method	ValidateBankAccount	Parameters:
        ServiceKey (Sage Pay Account Service Key)
        AccountNumber
        BranchCode
        AccountType
        */

        if ($debit_order->bank_account_type == 'Current') {
            $account_type = 1;
        } elseif ($debit_order->bank_account_type == 'Savings') {
            $account_type = 2;
        } elseif ($debit_order->bank_account_type == 'Transmisson') {
            $account_type = 3;
        } elseif ($debit_order->bank_account_type == 'Bond') {
            $account_type = 4;
        }
        $params = [
            'ServiceKey' => $this->account_service_key,
            'AccountNumber' => $debit_order->bank_account_number,
            'BranchCode' => $debit_order->bank_branch_code,
            'AccountType' => $account_type,
        ];
        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/NIWS_Validation.svc?wsdl');
        $result = $client->ValidateBankAccount($params)->ValidateBankAccountResult;

        if ($result == 0) {
            $validate_msg = 'Bank account details valid';
        } elseif ($result == 1) {
            $validate_msg = 'Invalid branch code';
        } elseif ($result == 2) {
            $validate_msg = 'Account number failed check digit validation';
        } elseif ($result == 3) {
            $validate_msg = 'Invalid account type';
        } elseif ($result == 4) {
            $validate_msg = 'Input data incorrect';
        } elseif ($result == 100) {
            $validate_msg = 'Authentication failed';
        } elseif ($result == 200) {
            $validate_msg = 'Web service error contact support@netcash.co.za';
        } else {
            $validate_msg = 'Unknown error.';
        }

        $validated = ($validate_msg == 'Bank account details valid') ? 1 : 0;

        return ['validated' => $validated, 'validate_message' => $validate_msg];
    }

    public function validateServiceKey()
    {
        /*
        1	Debit orders
        2	Creditor payments
        3	Risk reports
        5	Account service
        10	Salary payments
        14	Pay Now
        */
    }

    public function createTransactions($debit_order_batch_id)
    {
        $db = new DBEvent;
        $docdate = date('Y-m-d', strtotime($this->batch_date));

        $debit_order_batch = \DB::table('acc_debit_order_batch')->where('id', $debit_order_batch_id)->get()->first();
        if (empty($debit_order_batch->limit_account_id)) {
            $processed = \DB::table('acc_cashbook_transactions')->where('debit_order_batch_id', $debit_order_batch_id)->count();

            if ($processed) {
                return false;
            }
        }

        $docdate = date('Y-m-d', strtotime($this->batch_date));

        if (! str_contains($debit_order_batch->result, 'SUCCESSFUL')) {
            return false;
        }

        $filename = $this->batch_date.'.txt';
        $file = \Storage::disk('debit_orders')->get($filename);

        $lines = explode(PHP_EOL, $file);

        foreach ($lines as $line) {
            $fields = explode("\t", $line);
            if ($fields[0] == 'T') {
                $account_number = $fields[8];
                $amount = currency($fields[9] / 100);
                $account_id = \DB::table('acc_debit_orders')->where('bank_account_number', $account_number)->where('status', '!=', 'Deleted')->pluck('account_id')->first();

                $transaction = [
                    'docdate' => date('Y-m-d', strtotime($docdate)),
                    'account_id' => $account_id,
                    'total' => $amount,
                    'cashbook_id' => 1,
                    'debit_order_batch_id' => $debit_order_batch->id,
                    'reference' => 'Debit Order',
                    'api_status' => 'Complete',
                    'doctype' => 'Cashbook Customer Receipt',
                ];
                $exists = \DB::table('acc_cashbook_transactions')->where('account_id', $account_id)->where('debit_order_batch_id', $debit_order_batch->id)->count();
                if (! $exists) {
                    $db->setTable('acc_cashbook_transactions')->save($transaction);
                }

                // email customer action date and amount

                $mail = [];
                $mail['internal_function'] = 'debit_order_submitted';
                $mail['action_date'] = date('Y-m-d', strtotime($debit_order_batch->action_date));
                $mail['amount'] = $amount;
                erp_process_notification($account_id, $mail);
            }
        }

        return true;
    }

    /**** AUTHORISE DEBIT ORDER ****/

    public function retrieveUnauthorisedBatches()
    {
        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/NIWS_NIF.svc?wsdl');
        $result = $client->RetrieveUnauthorisedBatches(['ServiceKey' => $this->service_key]);

        return $result;
    }

    public function requestBatchAuthorise($guid)
    {
        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/NIWS_NIF.svc?wsdl');
        $result = $client->RequestBatchAuthorise(['ServiceKey' => $this->service_key, 'BatchIndicator' => $guid, 'ReleaseFunds' => true]);

        return $result;
    }

    public function requestBatchUnuthorised($guid)
    {
        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/NIWS_NIF.svc?wsdl');
        $result = $client->RequestBatchUnuthorised(['ServiceKey' => $this->service_key, 'BatchIndicator' => $guid, 'ReleaseFunds' => true]);

        return $result;
    }

    public function authoriseBatch($batch_id)
    {
        // https://api.netcash.co.za/value-added-services/auto-auth-batch/
        $debit_order = \DB::table('acc_debit_order_batch')->where('id', $batch_id)->get()->first();
        if (empty($debit_order)) {
            return 'Debit order does not exists';
        }
        if (empty($debit_order->uploaded) || empty($debit_order->result)) {
            return 'Debit order not uploaded and/or result file not generated';
        }
        /*
        Action date	The action date of the batch (yyyyMMdd)
        GUID	The unique identifier assigned to the batch. Use this value when submitting an authorisation request for this batch.
        Line count	The number of transaction rows in the batch
        Batch value	The total value of the batch
        Checksum	The checksum of all the account numbers and masked credit card numbers in the batch
        Batch name	The batch name that was sent in the file
        */
        $unauthorised_batches = $this->retrieveUnauthorisedBatches();
        $batch_to_authorise = false;
        if (! empty($unauthorised_batches) && ! empty($unauthorised_batches->RetrieveUnauthorisedBatchesResult)) {
            $batches = [];

            \DB::table('acc_debit_order_batch')->where('id', $batch_id)->update(['unauthorised_batches_result' => $unauthorised_batches->RetrieveUnauthorisedBatchesResult]);
            $batch_text = explode("\r\n", $unauthorised_batches->RetrieveUnauthorisedBatchesResult);
            $batch_text = collect($batch_text)->filter()->toArray();

            foreach ($batch_text as $batch) {
                $line = explode("\t", $batch);

                $trx = [
                    'action_date' => trim($line[0]),
                    'guid' => trim($line[1]),
                    'line_count' => trim($line[2]),
                    'bacth_value' => trim($line[3]),
                    'checksum' => trim($line[4]),
                    'batch_name' => trim($line[5]),
                ];

                if (str_contains($debit_order->result, $trx['batch_name']) && str_contains($debit_order->result, 'SUCCESSFUL') && date('Y-m-d', strtotime($debit_order->action_date)) == date('Y-m-d', strtotime($trx['action_date']))) {
                    $batch_to_authorise = $trx;
                }
            }
        }

        if ($batch_to_authorise) {
            $result = $this->requestBatchAuthorise($batch_to_authorise['guid']);
            if (empty($result) || ! isset($result->RequestBatchAuthoriseResult)) {
                return 'Debit order could not be authorised';
            }
            if (! empty($result) && isset($result->RequestBatchAuthoriseResult)) {
                \DB::table('acc_debit_order_batch')->where('id', $batch_id)->update(['request_authorise_result' => $result->RequestBatchAuthoriseResult]);
            }
            if (! empty($result) && isset($result->RequestBatchAuthoriseResult) && $result->RequestBatchAuthoriseResult != 000) {
                if ($result->RequestBatchAuthoriseResult == '100') {
                    return 'Authentication failed';
                } elseif ($result->RequestBatchAuthoriseResult == '200') {
                    return 'Web service error contact support@netcash.co.za';
                } elseif ($result->RequestBatchAuthoriseResult == '316') {
                    return 'Account is not compliant';
                } elseif ($result->RequestBatchAuthoriseResult == '317') {
                    return 'Invalid batch GUID';
                } else {
                    return 'Debit order could not be authorised';
                }
            }

            if (! empty($result) && isset($result->RequestBatchAuthoriseResult) && $result->RequestBatchAuthoriseResult == 000) {
                return 'Debit order authorised';
            }
        }

        return 'Debit order could not be authorised could not find guid';
    }

    public function getStatementPollingId($from_date = false)
    {
        if (! $from_date) {
            $from_date = date('Y-m-01');
        }
        $from_date = date('Ymd', strtotime($from_date));
        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/NIWS_NIF.svc?wsdl');
        $result = $client->RequestMerchantStatement(['ServiceKey' => $this->account_service_key, 'FromActionDate' => $from_date]);
        if ($result->RequestMerchantStatementResult == 100) {
            return '100	Authentication failure. Ensure that the service key in the method call is correct';
        }
        if ($result->RequestMerchantStatementResult == 101) {
            return '101	Date format error. If the string contains a date it should be in the format CCYYMMDD';
        }
        if ($result->RequestMerchantStatementResult == 102) {
            return '102	Invalid date. Full daily statement not available for the requested date.';
        }
        if ($result->RequestMerchantStatementResult == 200) {
            return '200	General code exception. Please contact Netcash Technical Support.';
        }
        $polling_id = $result->RequestMerchantStatementResult;

        return $polling_id;
    }

    public function retrieveStatement($polling_id)
    {
        $client = new \SoapClient('https://ws.netcash.co.za/NIWS/NIWS_NIF.svc?wsdl');

        $result = $client->RetrieveMerchantStatement(['ServiceKey' => $this->account_service_key, 'PollingId' => $polling_id]);

        return $result;
    }

    public function getStatement($from_date = false)
    {
        if (! $from_date) {
            $from_date = date('Y-m-01');
        }
        $from_date = date('Ymd', strtotime($from_date));

        $polling_id = $this->getStatementPollingId($from_date);
        if (! is_numeric($polling_id)) {
            return $polling_id;
        }
        sleep(2);
        $statement = $this->retrieveStatement($polling_id);
        if ($statement->RetrieveMerchantStatementResult == 'FILE NOT READY') {
            sleep(2);
            $statement = $this->retrieveStatement($polling_id);
        }

        return $statement;
    }
}
