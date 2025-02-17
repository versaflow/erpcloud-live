<?php

/*
https://developers.payfast.co.za/documentation/#credit-card-transaction-query
https://github.com/PayFast/payfast-php-sdk
*/
use PayFast\PayFastApi as PayFastApi;

class Payfast
{
    public function __construct()
    {
        $this->sandbox_merchant_id = '10000100';
        $this->sandbox_merchant_key = '46f0cd694581a';
        $this->sandbox_merchant_passphrase = 'jt7NOE43FZPn';
        if (session('instance')->id == 11) {
            $this->sandbox_merchant_id = '10031307';
            $this->sandbox_merchant_key = 'egtvae67byv2k';
            $this->sandbox_merchant_passphrase = 'egtvae67byv2k';
        }
        $this->sandbox_url = 'https://sandbox.payfast.co.za';
        $this->production_url = 'https://www.payfast.co.za';
        $this->api_url = 'https://www.payfast.co.za';
        $this->return_url = url('/payfast_return');
        $this->cancel_url = url('/payfast_cancel');
        $this->notify_url = url('/payfast_notify');
        $this->debug = false;
        $payment_option = get_payment_option('Payfast');
        $this->setCredentials($payment_option->payfast_id, $payment_option->payfast_key, $payment_option->payfast_pass_phrase);
    }

    public function setDebug($debug = true)
    {
        $this->debug = $debug;
        if ($debug) {
            $this->setCredentials($this->sandbox_merchant_id, $this->sandbox_merchant_key, $this->sandbox_merchant_passphrase);
            $this->api_url = $this->sandbox_url;
        }

        return $this;
    }

    public function setCredentials($merchant_id, $merchant_key, $pass_phrase = false)
    {
        $this->merchant_id = $merchant_id;
        $this->merchant_key = $merchant_key;
        $this->pass_phrase = $pass_phrase;

        return $this;
    }

    public function setPaymentID($account_id)
    {
        $this->account = dbgetaccount($account_id);
        $this->reseller = dbgetaccount($this->account->partner_id);
        $this->m_payment_id = $account_id.'_'.date('U');
        session()->forget('payfast_form_data');

        return $this;
    }

    public function getFormData($amount = 100)
    {
        if (! empty(session('payfast_form_data'))) {
            $form_data = session('payfast_form_data');
        } else {
            if (empty($this->m_payment_id)) {
                return 'Payment ID is not set';
            }
            if (empty($this->merchant_id)) {
                return 'Merchant ID is not set';
            }
            if (empty($this->account->id)) {
                return 'Account ID is not set';
            }

            $form_data = [
                'merchant_id' => $this->merchant_id,
                'merchant_key' => $this->merchant_key,
                'return_url' => $this->return_url,
                'cancel_url' => $this->cancel_url,
                'notify_url' => $this->notify_url,
                'm_payment_id' => $this->m_payment_id,
                'amount' => currency($amount),
                'item_name' => $this->reseller->company.' Services',
                'custom_int1' => $this->account->id,
                //"payment_method" => "cc",
                'signature' => '',
            ];
        }

        $form_data['amount'] = currency($amount);

        if (empty(session('payfast_form_data'))) {
            session(['payfast_form_data' => $form_data]);
        }

        return $form_data;
    }

    public function getForm()
    {
        // hidden payfast form
        $form = '<form action="'.$this->api_url.'/eng/process" method="POST" id="payfast_form">';
        $form_data = $this->getFormData();
        if (! is_array($form_data)) {
            return $form_data;
        }
        foreach ($form_data as $key => $value) {
            if ($key == 'amount') {
                $form .= '<input type="hidden" name="'.$key.'" id="payfast_amount" value="'.$value.'">';
            } else {
                $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
            }
        }

        $form .= '</form>';

        // amount input form to set signature via ajax and submit payfast form
        return $form;
    }

    public function getSignature($amount)
    {
        // ajax to get md5 signature

        $form_data = $this->getFormData($amount);

        // Create parameter string
        $encoded_form_data = '';
        if (! empty($form_data) && is_array($form_data) && count($form_data) > 0) {
            foreach ($form_data as $key => $val) {
                if (! empty($val)) {
                    $encoded_form_data .= $key.'='.urlencode(trim($val)).'&';
                }
            }
        }
        // Remove last ampersand
        $form_string = substr($encoded_form_data, 0, -1);
        //Uncomment the next line and add a passphrase if there is one set on the account

        if (! empty($this->pass_phrase)) {
            $form_string .= '&passphrase='.urlencode(trim($this->pass_phrase));
        }
        $signature = md5($form_string);

        return $signature;
    }

    public function validate(Request $request)
    {
        $validHosts = [
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
        ];

        $validIps = [];

        foreach ($validHosts as $pfHostname) {
            $ips = gethostbynamel($pfHostname);
            if ($ips !== false) {
                $validIps = array_merge($validIps, $ips);
            }
        }

        // Remove duplicates
        $validIps = array_unique($validIps);

        if (! in_array($_SERVER['REMOTE_ADDR'], $validIps)) {
            return false;
        }
    }

    public function getTransactionHistory($from = false, $to = false)
    {
        $params = [];
        if ($from) {
            $params['from'] = $from;
        }
        if ($to) {
            $params['to'] = $to;
        }
        $url = 'https://api.payfast.co.za/transactions/history';
        $url = $url.(empty($params) ? '' : '?'.http_build_query($params));
        $api_request = \Httpful\Request::get($url);

        $headers = [
            'merchant-id' => $this->merchant_id,
            'timestamp' => date(DATE_ISO8601),
            'version' => 'v1',
        ];
        $signature = $this->apiSignature($headers, $params);
        $headers['signature'] = $signature;
        foreach ($headers as $key => $val) {
            $api_request->addHeader($key, $val);
        }

        $response = $api_request->send();

        return $response;
    }

    public function getSanboxTransactionsDaily($date = false)
    {
        if (! $date) {
            $date = date('Y-m-d');
        }
        $api = new PayFastApi(
            [
                'merchantId' => $this->sandbox_merchant_id,
                'merchantKey' => $this->sandbox_merchant_key,
                'passPhrase' => $this->sandbox_merchant_passphrase,
                'testMode' => true,
            ]
        );

        // $rangeArray = $api->transactionHistory->range(['from' => '2020-08-01', 'to' => '2020-08-07']);

        // $dailyArray = $api->transactionHistory->daily(['date' => '2020-08-07']);

        // $weeklyArray = $api->transactionHistory->weekly(['date' => '2020-08-07']);

        $daily = $api->transactionHistory->daily(['date' => date('Y-m-d', strtotime($date))]);

        return $daily;
    }

    public function getTransactionsDaily($date = false)
    {
        if (! $date) {
            $date = date('Y-m-d');
        }
        $api = new PayFastApi(
            [
                'merchantId' => $this->merchant_id,
                'passPhrase' => $this->pass_phrase,
                'testMode' => false,
            ]
        );

        // $rangeArray = $api->transactionHistory->range(['from' => '2020-08-01', 'to' => '2020-08-07']);

        // $dailyArray = $api->transactionHistory->daily(['date' => '2020-08-07']);

        // $weeklyArray = $api->transactionHistory->weekly(['date' => '2020-08-07']);

        $daily = $api->transactionHistory->daily(['date' => date('Y-m-d', strtotime($date))]);

        return $daily;
    }

    public function getTransactionsMonthly($date)
    {
        $api = new PayFastApi(
            [
                'merchantId' => $this->merchant_id,
                'passPhrase' => $this->pass_phrase,
                'testMode' => false,
            ]
        );

        // $rangeArray = $api->transactionHistory->range(['from' => '2020-08-01', 'to' => '2020-08-07']);

        // $dailyArray = $api->transactionHistory->daily(['date' => '2020-08-07']);

        // $weeklyArray = $api->transactionHistory->weekly(['date' => '2020-08-07']);

        $monthlyArray = $api->transactionHistory->monthly(['date' => date('Y-m', strtotime($date))]);

        return $monthlyArray;
    }

    public function getTransactionHistoryPeriod($date = false)
    {
        $params = [];
        if ($date) {

            //$url = 'https://api.payfast.co.za/transactions/history/monthly';
            //$params['date'] = date('Y-m', strtotime($date));
            $params['from'] = date('Y-m-01', strtotime($date));
            $params['to'] = date('Y-m-t', strtotime($date));
        }

        $url = 'https://api.payfast.co.za/transactions/history';
        $url = $url.(empty($params) ? '' : '?'.http_build_query($params));

        $api_request = \Httpful\Request::get($url);

        $headers = [
            'merchant-id' => $this->merchant_id,
            'timestamp' => date(DATE_ISO8601),
            'version' => 'v1',
        ];

        $signature = $this->apiSignature($headers, $params);
        $headers['signature'] = $signature;

        foreach ($headers as $key => $val) {
            $api_request->addHeader($key, $val);
        }

        $response = $api_request->send();

        return $response;
    }

    public function apiSignature($headers = [], $params = [])
    {
        $post_data = ['passphrase' => $this->pass_phrase];
        $post_data = array_merge($post_data, $headers);

        if (count($params)) {
            $post_data = array_merge($post_data, $params);
        }
        ksort($post_data);
        foreach ($post_data as $key => $val) {
            if (! empty($val)) {
                $encoded_form_data .= $key.'='.urlencode(trim($val)).'&';
            }
        }
        // Remove last ampersand
        $form_string = substr($encoded_form_data, 0, -1);
        //Uncomment the next line and add a passphrase if there is one set on the account
        $signature = md5($form_string);

        return $signature;
    }
}
