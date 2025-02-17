<?php


function export_default_ratesheet($type, $currency, $complete = true, $name = false)
{
    ini_set("memory_limit", "1024M");
    if ($complete) {
        $rates = \DB::connection('pbx')->table('p_rates_complete')->where('lowest_rate', 1)->where('status', 'Enabled')
        ->orderBy('country')->orderBy('destination')
        ->get()
        ->unique(function ($item){
            return $item->country . $item->destination;
        });
        $file_title = 'Complete Ratesheet '.ucfirst($type).' '.strtoupper($currency);
    } else {
        $rates = \DB::connection('pbx')->table('p_rates_summary')->where('lowest_rate', 1)->orderBy('country')->orderBy('destination')->get();
        $file_title = 'Ratesheet '.ucfirst($type).' '.strtoupper($currency);
    }
    if ($name) {
        $file_title = $name;
    }
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $excel_list = [];

    $list_rates = [];

    if ($complete) {
        $summary_countries =[];
        $complete_rates =[];
        $summary_rates = \DB::connection('pbx')->table('p_rates_summary')->where('lowest_rate', 1)->orderBy('country')->orderBy('destination')->get();
        foreach ($summary_rates as $summary_rate) {
            if ($summary_rate->country == 'south africa') {
                $summary_countries[] = $summary_rate->country;
                $rate = $summary_rate;
                $rate->destination_id = '';
                $list_rates[] = $rate;
            }
        }

        foreach ($summary_rates as $summary_rate) {
            if ($summary_rate->country != 'south africa') {
                $summary_countries[] = $summary_rate->country;
                $rate = $summary_rate;
                $rate->destination_id = '';
                $list_rates[] = $rate;
            }
        }

        foreach ($rates as $rate) {
            if (!in_array($rate->country, $summary_countries)) {
                $list_rates[] = $rate;
            }
        }
    }

    if (!$complete) {
        foreach ($rates as $rate) {
            if ($rate->country == 'south africa') {
                $list_rates[] = $rate;
            }
        }
        foreach ($rates as $rate) {
            if ($rate->country != 'south africa') {
                $list_rates[] = $rate;
            }
        }
    }

    foreach ($list_rates as $rate) {
        if ($type == 'retail' && $currency == 'ZAR') {
            if ($rate->retail_rate_zar) {
                $price = $rate->retail_rate_zar;
            }
        }
        if ($type == 'wholesale' && $currency == 'ZAR') {
            $price = $rate->wholesale_rate_zar;
        }
        if ($type == 'retail' && $currency == 'USD') {
            $price = $rate->retail_rate_usd;
        }
        if ($type == 'wholesale' && $currency == 'USD') {
            $price = $rate->wholesale_rate_usd;
        }
        if ($complete) {
            $excel_list[] = [
                'Country' => $rate->country,
                'Destination' => $rate->destination,
                'Dial Code' => $rate->destination_id,
                'Rate' => currency($price, 3),
                'Rate Incl' => currency(($price * 1.15), 3),
            ];
        } else {
            $excel_list[] = [
                'Country' => $rate->country,
                'Destination' => $rate->destination,
                'Rate' => currency($price, 3),
                'Rate Incl' => currency(($price * 1.15), 3),
            ];
        }
    }

    $export = new App\Exports\CollectionExport();
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');


    return $file_name;
}


function schedule_generate_default_ratesheets()
{
    export_default_ratesheet('retail', 'ZAR');
    export_default_ratesheet('wholesale', 'ZAR');
    export_default_ratesheet('retail', 'USD');
    export_default_ratesheet('wholesale', 'USD');
}

//button functons
function button_partner_rates_export_summary($request)
{
    $file_name = export_partner_rates_summary($request->id);
    $file_path = attachments_path().$file_name;
    return response()->download($file_path, $file_name);
}

function button_partner_rates_export($request)
{
    $file_name = export_partner_rates($request->id);
    $file_path = attachments_path().$file_name;
    return response()->download($file_path, $file_name);
}



// event functions

// helper functions
function export_partner_rates_summary($ratesheet_id, $pricing_exports = false, $file_name = false)
{
    $rates_sql = "SELECT country,destination, rate, rate_5k, rate_10k, rate_50k, rate_100k
        FROM p_rates_partner_items WHERE destination!='premium' and ratesheet_id=".$ratesheet_id." and destination > '' ORDER BY country,destination,rate";

    $rates = \DB::connection('pbx')->select($rates_sql);
    

    $file_title = 'CT Ratesheet '.date('Y-m-d');
  
    if(!$file_name){
        $file_name = $file_title.'.xlsx';
    }
     $instance_dir = session('instance')->directory;
    if($pricing_exports){
        $file_dir = attachments_path();
    }else{
        $file_dir = public_path().'/pricing_exports/'.$instance_dir;
    }
    
    $file_path = $file_dir.$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $excel_list = [];

    foreach ($rates as $rate) {
        if($rate->country == 'south africa'){
        $item = [
            'Country' => $rate->country,
            'Destination' => $rate->destination,
            'Rate' => currency($rate->rate, 3),
        ];
        
      
            $item['Rate 5k'] = currency($rate->rate_5k, 3);
            $item['Rate 10k'] = currency($rate->rate_10k, 3);
            $item['Rate 50k'] = currency($rate->rate_50k, 3);
            $item['Rate 100k'] = currency($rate->rate_100k, 3);
      
        $excel_list[] = $item;
        }
    }
    
    foreach ($rates as $rate) {
        if($rate->country != 'south africa'){
        $item = [
            'Country' => $rate->country,
            'Destination' => $rate->destination,
            'Rate' => currency($rate->rate, 3),
        ];
        
      
            $item['Rate 5k'] = currency($rate->rate_5k, 3);
            $item['Rate 10k'] = currency($rate->rate_10k, 3);
            $item['Rate 50k'] = currency($rate->rate_50k, 3);
            $item['Rate 100k'] = currency($rate->rate_100k, 3);
    
        $excel_list[] = $item;
        }
    }



    $export = new App\Exports\CollectionExport();
    $export->setData($excel_list);

    $ratesheet_currency = \DB::connection('pbx')->table('p_rates_partner')->where('id',$ratesheet_id)->pluck('currency')->first();
  
   
    $export->seCurrencyColumns(['C','D','E','F','G'], $ratesheet_currency);
    $instance_dir = session('instance')->directory;
    if($pricing_exports){
        Excel::store($export, $file_name,  'pricing_exports');
    }else{
        Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
    }

    return $file_name;
}

function export_partner_rates($ratesheet_id, $pricing_exports = false, $file_name = false)
{
    try {
        $ratesheet = \DB::connection('pbx')->table('p_rates_partner')->where('id', $ratesheet_id)->get()->first();
      
        $rates = \DB::connection('pbx')->table('p_rates_complete')->where('cost', '>', 0)->where('lowest_rate', 1)->orderBy('country')->orderBy('destination')->get()->unique('destination_id');

      
        $file_title = 'CT Ratesheet Complete '.date('Y-m-d');
        if(!$file_name){
        $file_name = $file_title.'.xlsx';
        }
        
     $instance_dir = session('instance')->directory;
        if($pricing_exports){
            $file_dir = attachments_path();
        }else{
            $file_dir = public_path().'/pricing_exports/'.$instance_dir;
        }

        $file_path = $file_dir.$file_name;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $excel_list = [];
        
        $usd_exchange =  get_exchange_rate();

        foreach ($rates as $rate) {
            
            if ($ratesheet->currency == 'USD') {
                $price = $rate->cost * 1.8;
                $price = $price * $usd_exchange;
            }
            if ($ratesheet->currency == 'ZAR') {
                $price = $rate->cost * 1.8;
            }


            $excel_list[] = [
            'Prefix' => $rate->destination_id,
            'Country' => $rate->country,
            'Destination' => $rate->destination,
            'Rate' => currency($price, 3),
        ];
        }
  
        $export = new App\Exports\CollectionExport();
        $export->setData($excel_list);

        $export->seCurrencyColumns(['D'], $ratesheet->currency);
       
     $instance_dir = session('instance')->directory;
        if($pricing_exports){
            Excel::store($export,$file_name, 'pricing_exports');
        }else{
            Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
        }
        return $file_name;
    } catch (\Throwable $ex) {  exception_log($ex);
        aa($ratesheet_id);
        aa($pricing_exports); 
        aa($file_name); 
        aa($ex->getMessage()); 
        aa($ex->getTraceAsString());
        return false;
    }
}


function schedule_send_ratesheets_wholesale()
{
    $v_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->get();
   // $v_domains = \DB::connection('pbx')->table('v_domains')->where('domain_name','lti.cloudtools.co.za')->where('cost_calculation', 'volume')->get();
  
    foreach ($v_domains as $domain) {
        $data = [];
        $file = export_partner_rates_summary($domain->ratesheet_id);
        $data['files'][] = public_path('attachments/'.session('instance')->directory.'/').$file;
        $data['domain_name'] = $domain->domain_name;
        $data['function_name'] = __FUNCTION__;
        $account = dbgetaccount($domain->account_id);
        $account_id = $account->id;
        if ($account->partner_id != 1) {
            $account_id = $account->partner_id;
        }
        //$data['test_debug'] =1;
        $function_variables =[];
        $conn = $domain->erp;
        $data['volume_rate'] = $domain->volume_rate;
        $data['airtime_balance'] = $domain->balance;
        $sa_rates = \DB::connection('pbx')->table('p_rates_partner_items')->select($domain->volume_rate,'destination')->where('country','south africa')->where('ratesheet_id',$domain->ratesheet_id)->get();
        $local_rates = '<br>Local Rates: <br><br>';
        foreach($sa_rates as $rate){
            $local_rates .= $rate->destination.': '.$rate->{$domain->volume_rate}.' <br>';
        }
        
        $data['local_rates'] = $local_rates;
        $data['bcc_admin'] = true;
        //$data['cc_admin'] = true;
       // $data['test_debug'] = 1;
        erp_process_notification($account_id, $data, $function_variables, $conn);
      
    }
}
