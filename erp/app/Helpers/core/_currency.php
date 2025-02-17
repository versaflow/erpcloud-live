<?php

function get_supplier_currency_symbol($supplier_id)
{
    $currency =  \DB::connection('default')->table('crm_suppliers')->where('id', $supplier_id)->pluck('currency')->first();
    $fmt = new NumberFormatter("en-us@currency=$currency", NumberFormatter::CURRENCY);
    $currency_symbol = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    if ($currency_symbol == "ZAR") {
        $currency_symbol = "R";
    }
    return $currency_symbol;
}

function get_account_currency_symbol($account_id)
{
    $currency = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('currency')->first();
    $fmt = new NumberFormatter("en-us@currency=$currency", NumberFormatter::CURRENCY);
    $currency_symbol = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    if ($currency_symbol == "ZAR") {
        $currency_symbol = "R";
    }
    return $currency_symbol;
}

function get_currency_symbol($currency)
{
    $fmt = new NumberFormatter("en-us@currency=$currency", NumberFormatter::CURRENCY);
    $currency_symbol = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    if ($currency_symbol == "ZAR") {
        $currency_symbol = "R";
    }
    return $currency_symbol;
}

function get_supplier_currency($supplier_id)
{
    return \DB::connection('default')->table('crm_suppliers')->where('id', $supplier_id)->pluck('currency')->first();
}

function get_account_currency($account_id)
{
    return \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('currency')->first();
}

function get_exchange_rate($date = null, $base_currency = 'ZAR', $currency = 'USD')
{
    if (!$date) {
        $exchange_date = date('Y-m-d');
    } else {
        $exchange_date = $date;
    }
    
    
    if($exchange_date > date('Y-m-d')){
        $exchange_date = date('Y-m-d');
    }
    
   
    if ($base_currency == $currency) {
        return 1;
    }
  
    $row = \DB::connection('system')->table('crm_exchange_rates')
    ->where('base_currency', $base_currency)
    ->where('currency', $currency)
    ->where('exchange_date', $exchange_date)
    ->get()->first();
   
    if (!empty($row) && !empty($row->rate)) {
        return $row->rate;
    }
    $currency_rate = convert_currency(1, $base_currency, $currency, $exchange_date);
    $data = [
        'base_currency' => $base_currency,
        'currency' => $currency,
        'rate' => $currency_rate,
        'exchange_date' => $exchange_date,
    ];
    
    $where_data = [
        'base_currency' => $base_currency,
        'currency' => $currency,
        'exchange_date' => $exchange_date,
    ];
    
    
    \DB::connection('system')->table('crm_exchange_rates')->updateOrInsert($where_data,$data);
  
    $connections = array_keys(config('database')['connections']);
    // @pbxoffline
    if (in_array('pbx', $connections)) {
        \DB::connection('pbx')->table('p_exchange_rates')->insert($data);
    }
    return $currency_rate;
}

function convert_currency_zar_to_usd($amount = false, $date = false)
{
    if(empty($amount)){
        return 0;    
    }
    return convert_currency($amount, 'ZAR', 'USD', $date);
}

function convert_currency_usd_to_zar($amount = false, $date = false)
{  
    if(empty($amount)){
        return 0;    
    }
    return convert_currency($amount, 'USD', 'ZAR', $date);
}


function currency_to_zar($currency, $amount, $date = false)
{
    if (!$date) {
        $date = date('Y-m-d');
    }
    if($date > date('Y-m-d')){
        $date = date('Y-m-d');
    }
    
    $exchange_rate = get_exchange_rate($date, $currency, 'ZAR');
   
    $total = $amount * $exchange_rate;

    return currency($total);
}

function convert_currency($amount = false, $from = 'ZAR', $to = 'USD', $date = false)
{
    
      
    if(empty($amount)){
        return 0;    
    }
    if($date === false){
        $exchange_date = date('Y-m-d');
    }else{
        $exchange_date = $date;
    }
    
    if($exchange_date > date('Y-m-d')){
        $exchange_date = date('Y-m-d');
    }
  
    $row = \DB::connection('system')->table('crm_exchange_rates')
    ->where('base_currency', $from)
    ->where('currency', $to)
    ->where('exchange_date', $exchange_date)
    ->get()->first();
    if (!empty($row) && !empty($row->rate)) {
        $exchange_rate = $row->rate;
    }else{
        if(date('Y-m-d',strtotime($exchange_date)) == date('Y-m-d')){
            $usd_rate = Swap::latest('USD/ZAR')->getValue();
        }else{
            $usd_rate = Swap::historical('USD/ZAR', Carbon::parse($exchange_date))->getValue();
        }
        $where_data = [
            'base_currency' => 'USD',
            'currency' => 'ZAR',
            'exchange_date' => $exchange_date,
        ];
        $data = [
            'base_currency' => 'USD',
            'currency' => 'ZAR',
            'rate' => $usd_rate,
            'exchange_date' => $exchange_date,
        ];
        \DB::connection('system')->table('crm_exchange_rates')->updateOrInsert($where_data,$data);
       
        $zar_rate = 1/$usd_rate;
        $where_data = [
            'base_currency' => 'ZAR',
            'currency' => 'USD',
            'exchange_date' => $exchange_date,
        ];
        $data = [
            'base_currency' => 'ZAR',
            'currency' => 'USD',
            'rate' => $zar_rate,
            'exchange_date' => $exchange_date,
        ];
        \DB::connection('system')->table('crm_exchange_rates')->updateOrInsert($where_data,$data);
        if($from == 'ZAR'){
            $exchange_rate = $zar_rate;
        }else{
            $exchange_rate = $usd_rate;
        }
    }
    
    
    
    
    $negative_amount = ($amount < 0) ? true  : false;
    $amount = abs($amount);
    $amount = $amount * $exchange_rate;
    if($negative_amount)
    return $amount*-1;
    
    return $amount;
}
function convert_currency_old($amount = false, $from = 'ZAR', $to = 'USD', $date = false)
{
    
      
    if(empty($amount)){
        return 0;    
    }
    
    $negative_amount = ($amount < 0) ? true  : false;
    $amount = abs($amount);
    $currency = Currency::convert()
        ->from($from)
        ->to($to);
    if ($date) {
        $currency->date($date);
    }
    
    if ($amount) {
        $currency->amount($amount);
    } else {
        $currency->amount(1);
    }
    $amount = abs($amount);
   
    $amount = $currency->get();
    if($negative_amount)
    return $amount*-1;
    
    return $amount;
}

function schedule_set_exchange_rate()
{
    if(!is_main_instance()){
        return false;
    }
    \DB::connection('system')->table('crm_exchange_rates')->whereNull('rate')->delete();
    get_exchange_rate();
    
}
