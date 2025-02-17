<?php

function aftersave_ratesheets_set_currency($request){
$ratesheets = \DB::connection('pbx')->table('p_rates_partner')->get();
    foreach($ratesheets as $ratesheet){
        \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id',$ratesheet->id)->update(['currency' => $ratesheet->currency]);
    }    
}

function onload_set_ratesheet_allocated_count()
{
    $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->get();
    foreach ($ratesheets as $ratesheet) {
        $count = \DB::connection('pbx')->table('v_domains')->where('ratesheet_id', $ratesheet->id)->count();
        \DB::connection('pbx')->table('p_rates_partner')->where('id', $ratesheet->id)->update(['allocated_count'=>$count]);
    }
}

function schedule_pbx_set_daily_usages()
{

    $domains = \DB::connection('pbx')->table('v_domains')->where('account_id', '>', 0)->get();
    foreach ($domains as $domain) {
        if($domain->domain_name == 'lti.cloudtools.co.za'){
            $yesterday_usage = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('domain_name', $domain->domain_name)
            ->where('hangup_time','like',date('Y-m-d',strtotime('-1 day')).'%')
            ->where('extension', '!=', 103)
            ->sum(\DB::raw('cost'));
            \DB::connection('pbx')->table('v_domains')
            ->where('domain_name', $domain->domain_name)
            ->update(['yesterday_usage'=>$yesterday_usage]);
        }else{
            $yesterday_usage = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('domain_name', $domain->domain_name)
            ->where('hangup_time','like',date('Y-m-d',strtotime('-1 day')).'%')
            ->sum(\DB::raw('cost'));
            \DB::connection('pbx')->table('v_domains')
            ->where('domain_name', $domain->domain_name)
            ->update(['yesterday_usage'=>$yesterday_usage]);    
        }
    }
}

function schedule_pbx_set_montly_usages()
{

    $domains = \DB::connection('pbx')->table('v_domains')->where('account_id', '>', 0)->get();

    $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', 1)->get();
    $partner_ids = \DB::connection('pbx')->table('v_domains')
    ->where('partner_id', '>', 1)
    ->pluck('partner_id')
    ->unique()->filter()->toArray();
    
    
    foreach ($domains as $domain) {
        if($domain->domain_name == 'lti.cloudtools.co.za'){
            $lastmonth_total = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
            ->where('domain_name', $domain->domain_name)
            ->where('extension', '!=', 103)
            ->sum(\DB::raw('cost'));
            \DB::connection('pbx')->table('v_domains')
            ->where('domain_name', $domain->domain_name)
            ->update(['lastmonth_total'=>$lastmonth_total]);
        }else{
            $lastmonth_total = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
            ->where('domain_name', $domain->domain_name)
            ->sum(\DB::raw('cost'));
            \DB::connection('pbx')->table('v_domains')
            ->where('domain_name', $domain->domain_name)
            ->update(['lastmonth_total'=>$lastmonth_total]);    
        }
    }

    foreach ($partner_ids as $partner_id) {
        $reseller_lastmonth_total = \DB::connection('pbx')->table('v_domains')
        ->where('partner_id', $partner_id)
        ->sum(\DB::raw('lastmonth_total'));
        \DB::connection('pbx')->table('v_domains')
        ->where('partner_id', $partner_id)
        ->update(['reseller_lastmonth_total'=>$reseller_lastmonth_total]);
    }

    foreach ($domains as $domain) {
        if($domain->domain_name == 'lti.cloudtools.co.za'){
            $sec_total = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
            ->where('domain_name', $domain->domain_name)
            ->where('extension', '!=', 103)
            ->where('extension', '!=', 105)
            ->sum(\DB::raw('duration'));
            $lastmonth_minutes_total = $sec_total/60;
            \DB::connection('pbx')->table('v_domains')
            ->where('domain_name', $domain->domain_name)
            ->update(['lastmonth_minutes_total'=>$lastmonth_minutes_total]);
            
        }else{
            $sec_total = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
            ->where('domain_name', $domain->domain_name)
            ->sum(\DB::raw('duration'));
            $lastmonth_minutes_total = $sec_total/60;
            \DB::connection('pbx')->table('v_domains')
            ->where('domain_name', $domain->domain_name)
            ->update(['lastmonth_minutes_total'=>$lastmonth_minutes_total]);
        }
    }

    foreach ($partner_ids as $partner_id) {
        $reseller_lastmonth_minutes_total = \DB::connection('pbx')->table('v_domains')
        ->where('partner_id', $partner_id)
        ->sum(\DB::raw('lastmonth_minutes_total'));
     
        \DB::connection('pbx')->table('v_domains')
        ->where('partner_id', $partner_id)
        ->update(['reseller_lastmonth_minutes_total'=>$reseller_lastmonth_minutes_total]);
    }
    /*
    \DB::connection('pbx')->table('v_domains')->update(['volume_rate'=>'rate']);
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','volume')->get();
    foreach($volume_domains as $volume_domain){
        if($volume_domain->partner_id == 1){
           $usage = $volume_domain->lastmonth_minutes_total; 
        }else{
           $usage = $volume_domain->reseller_lastmonth_minutes_total; 
        }
        $volume_rate = 'rate';
        if($usage >= 100000){
            $volume_rate = 'rate_100k';
        }elseif($usage >= 50000){
            $volume_rate = 'rate_50k';
        }elseif($usage >= 10000){
            $volume_rate = 'rate_10k';
        }elseif($usage >= 5000){
            $volume_rate = 'rate_5k';
        }
        
        \DB::connection('pbx')->table('v_domains')->where('domain_uuid',$volume_domain->domain_uuid)->update(['volume_rate'=>$volume_rate]);
    }
    */
  
}

function schedule_pbx_validate_ratesheets()
{
    $default_ratesheet_id = \DB::connection('pbx')->table('p_rates_partner')->where('is_default', 1)->pluck('id')->first();
    $ratesheet_ids = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', 1)->pluck('id')->toArray();
    \DB::connection('pbx')->table('v_domains')->whereNotIn('ratesheet_id', $ratesheet_ids)->update(['ratesheet_id' => $default_ratesheet_id]);
    
    /*

    // delete invalid ratesheets
    $partner_ids = \DB::connection('pbx')->table('v_domains')->where('partner_id', '!=', 0)->where('partner_id', '!=', 1)->pluck('partner_id')->toArray();
    $deleted_pricelist_ids =  \DB::connection('pbx')->table('p_rates_partner')
    ->where('partner_id', '!=', 1)
    ->whereNotIn('partner_id', $partner_ids)
    ->pluck('id')->toArray();
    \DB::connection('pbx')->table('p_rates_partner_items')->whereIn('ratesheet_id', $deleted_pricelist_ids)->delete();
    \DB::connection('pbx')->table('p_rates_partner')->whereIn('id', $deleted_pricelist_ids)->delete();


    // validate partner ratesheets
    $domains = \DB::connection('pbx')->table('v_domains')->where('partner_id', '!=', 0)->where('partner_id', '!=', 1)->get()->unique('partner_id');
    foreach ($domains as $domain) {
        $cost_rates = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id', $domain->ratesheet_id)->get();
        $exists = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', $domain->partner_id)->count();
        if (!$exists) {
            // create partner ratesheets
            $company = \DB::connection('default')->table('crm_accounts')->select('company')->where('id', $domain->partner_id)->pluck('company')->first();
            $ratesheet_data = ['partner_id'=>$domain->partner_id,'name'=>$company];
            $new_ratesheet_id = \DB::connection('pbx')->table('p_rates_partner')->insertGetId($ratesheet_data);
            foreach ($cost_rates as $cost_rate) {
                $data = (array) $cost_rate;
                $data['ratesheet_id'] = $new_ratesheet_id;
                $data['cost_price'] = $cost_rate->rate;
                $data['rate'] = $data['cost_price']*1.2;
                unset($data['id']);
                \DB::connection('pbx')->table('p_rates_partner_items')->insert($data);
            }
        } else {
            // insert ratesheet items / update costprices
            $partner_ratesheet_ids = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', $domain->partner_id)->pluck('id')->toArray();
            foreach ($partner_ratesheet_ids as $partner_ratesheet_id) {
                $partner_ratesheet_items = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id', $partner_ratesheet_id)->get();

                //delete old routes - cost rate deleted
                foreach ($partner_ratesheet_items as $partner_ratesheet_item) {
                    $cost_exists = $cost_rates->where('country', $partner_ratesheet_item->country)->where('destination', $partner_ratesheet_item->destination)->count();
                    if (!$cost_exists) {
                        \DB::connection('pbx')->table('p_rates_partner_items')->where('id', $partner_ratesheet_item->id)->delete();
                    }
                }
                //update cost_prices
                foreach ($cost_rates as $cost_rate) {
                    $data = ['cost_price' => $cost_rate->rate];

                    \DB::connection('pbx')->table('p_rates_partner_items')
                    ->where('ratesheet_id', $partner_ratesheet_id)
                    ->where('country', $cost_rate->country)
                    ->where('destination', $cost_rate->destination)
                    ->update($data);
                }
            }
        }
    }

    // update ratesheet markups
    \DB::connection('pbx')->table('p_rates_partner_items')->update(['markup' => 0]);
    \DB::connection('pbx')->table('p_rates_partner_items')
    ->where('cost_price', '>', 0)
    ->update([
        'markup' => \DB::raw('(rate - cost_price) * 100 / cost_price'),
    ]);
    
    // apply partner_user_ratesheet_ids
    foreach ($partner_ids as $partner_id) {
        $ratesheet_ids = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', $partner_id)->pluck('id')->toArray();
        \DB::connection('pbx')->table('v_domains')
        ->where('partner_id', $partner_id)
        ->whereNotIn('partner_user_ratesheet_id', $ratesheet_ids)
        ->update(['partner_user_ratesheet_id' => $ratesheet_ids[0]]);
    }
    */
    
    ratesheets_set_volume_pricing();    
}


function ratesheets_set_volume_pricing(){
    $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->get();
    foreach($ratesheets as $ratesheet){
        \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id',$ratesheet->id)->update(['currency'=>$ratesheet->currency]);
    }
    
    \DB::connection('pbx')->table('p_rates_partner_items')
    ->where('country','!=','south africa')
    ->update([
        'rate_5k' => \DB::raw('rate'),
        'rate_10k' => \DB::raw('rate'),
        'rate_50k' => \DB::raw('rate'),
        'rate_100k' => \DB::raw('rate'),
    ]);
   
    $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->get();
    foreach($ratesheets as $ratesheet){
        if($ratesheet->discount_5k > 0){
            \DB::connection('pbx')->table('p_rates_partner_items')
            ->where('country','!=','south africa')
            ->where('ratesheet_id',$ratesheet->id)
            ->update(['rate_5k' => \DB::raw('rate-((rate/100)*'.$ratesheet->discount_5k.')')]);
        }
        if($ratesheet->discount_10k > 0){
            \DB::connection('pbx')->table('p_rates_partner_items')
            ->where('country','!=','south africa')
            ->where('ratesheet_id',$ratesheet->id)
            ->update(['rate_10k' => \DB::raw('rate-((rate/100)*'.$ratesheet->discount_10k.')')]);
        }
        if($ratesheet->discount_50k > 0){
            \DB::connection('pbx')->table('p_rates_partner_items')
            ->where('country','!=','south africa')
            ->where('ratesheet_id',$ratesheet->id)
            ->update(['rate_50k' => \DB::raw('rate-((rate/100)*'.$ratesheet->discount_50k.')')]);
        }
        if($ratesheet->discount_100k > 0){
            \DB::connection('pbx')->table('p_rates_partner_items')
            ->where('country','!=','south africa')
            ->where('ratesheet_id',$ratesheet->id)
            ->update(['rate_100k' => \DB::raw('rate-((rate/100)*'.$ratesheet->discount_100k.')')]);
        }
    }
    
    \DB::connection('pbx')->table('p_rates_partner_items')
    ->where('cost_price', '>', 0)
    ->update([
        'markup_5k' => \DB::raw('(rate_5k - cost_price) * 100 / cost_price'),
    ]);
    \DB::connection('pbx')->table('p_rates_partner_items')
    ->where('cost_price', '>', 0)
    ->update([
        'markup_10k' => \DB::raw('(rate_10k - cost_price) * 100 / cost_price'),
    ]);

    \DB::connection('pbx')->table('p_rates_partner_items')
    ->where('cost_price', '>', 0)
    ->update([
        'markup_50k' => \DB::raw('(rate_50k - cost_price) * 100 / cost_price'),
    ]);

    \DB::connection('pbx')->table('p_rates_partner_items')
    ->where('cost_price', '>', 0)
    ->update([
        'markup_100k' => \DB::raw('(rate_100k - cost_price) * 100 / cost_price'),
    ]);

}

function aftersave_pbx_ratesheet_set_volume_pricing(){
    update_rates_selling_prices();    
}
function aftersave_pbx_ratesheet_item_set_volume_pricing(){
    ratesheets_set_volume_pricing();    
}

function beforesave_rates_check_volume_rate($request){
    
    $rate = \DB::connection('pbx')->table('p_rates_partner_items')->where('id',$request->id)->get()->first();
    $ratesheet = \DB::connection('pbx')->table('p_rates_partner')->where('id',$rate->ratesheet_id)->get()->first();
   
    $rate_5k = $request->rate-(($request->rate/100)*$ratesheet->discount_5k);
    $rate_10k = $request->rate-(($request->rate/100)*$ratesheet->discount_10k);
    $rate_50k = $request->rate-(($request->rate/100)*$ratesheet->discount_50k);
    $rate_100k = $request->rate-(($request->rate/100)*$ratesheet->discount_100k);
    
    if($rate_5k < $rate->cost_price){
        return 'Rate 5k below cost. '.currency($rate_5k,3);
    }
    
    if($rate_10k < $rate->cost_price){
        return 'Rate 10k below cost. '.currency($rate_10k,3);
    }
    
    if($rate_50k < $rate->cost_price){
        return 'Rate 50k below cost. '.currency($rate_50k,3);
    }
    
    if($rate_100k < $rate->cost_price){
        return 'Rate 100k below cost. '.currency($rate_100k,3);
    }
}

function beforesave_ratesheet_check_volume_discount($request){
    
   
    $rates = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id',$request->id)->get();
   
    foreach($rates as $rate){
    
        $rate_5k = $rate->rate-(($rate->rate/100)*$request->discount_5k);
        $rate_10k = $rate->rate-(($rate->rate/100)*$request->discount_10k);
        $rate_50k = $rate->rate-(($rate->rate/100)*$request->discount_50k);
        $rate_100k = $rate->rate-(($rate->rate/100)*$request->discount_100k);
        
        if($rate_5k < $rate->cost_price){
            return $rate->country.' '.$rate->destination.' Rate 5k below cost.'.currency($rate_5k,3);
        }
        
        if($rate_10k < $rate->cost_price){
            return $rate->country.' '.$rate->destination.' Rate 10k below cost.'.currency($rate_10k,3);
        }
        
        if($rate_50k < $rate->cost_price){
            return $rate->country.' '.$rate->destination.' Rate 50k below cost.'.currency($rate_50k,3);
        }
        
        if($rate_100k < $rate->cost_price){
            return $rate->country.' '.$rate->destination.' Rate 100k below cost.'.currency($rate_100k,3);
        }
    }
    
}

function schedule_check_selling_rates_below_cost(){
    $rates_msg = '';
    $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id',1)->get();
    foreach($ratesheets as $ratesheet){
        $rate_errors = [];
        $rate_items = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id',$ratesheet->id)->get();
        foreach($rate_items as $r){
            if($r->rate < $r->cost_price){
                $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate: '.$r->rate;    
            }
            
            if($r->rate_5k < $r->cost_price){
                $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate5k: '.$r->rate_5k;    
            }
            if($r->rate_10k < $r->cost_price){
                $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate10k: '.$r->rate_10k;    
            }
            if($r->rate_50k < $r->cost_price){
                $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate50k: '.$r->rate_50k;    
            }
            if($r->rate_100k < $r->cost_price){
                $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate100k: '.$r->rate_100k;    
            }
        }
        if(count($rate_errors) > 0){
            $rates_msg .= '<br><br><b>'.$ratesheet->name.' below cost</b>'.'<br>'.implode('<br>',$rate_errors);
        }
    }
    
    if($rates_msg > ''){
        update_rates_selling_prices();    
    
    
        $rates_msg = '';
        $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id',1)->get();
        foreach($ratesheets as $ratesheet){
            $rate_errors = [];
            $rate_items = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id',$ratesheet->id)->get();
            foreach($rate_items as $r){
                if($r->rate < $r->cost_price){
                    $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate: '.$r->rate;    
                }
                
                if($r->rate_5k < $r->cost_price){
                    $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate5k: '.$r->rate_5k;    
                }
                if($r->rate_10k < $r->cost_price){
                    $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate10k: '.$r->rate_10k;    
                }
                if($r->rate_50k < $r->cost_price){
                    $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate50k: '.$r->rate_50k;    
                }
                if($r->rate_100k < $r->cost_price){
                    $rate_errors[] = $r->country.' - '.$r->destination.', cost: '.$r->cost_price.', rate100k: '.$r->rate_100k;    
                }
            }
            if(count($rate_errors) > 0){
                $rates_msg .= '<br><br><b>'.$ratesheet->name.' below cost</b>'.'<br>'.implode('<br>',$rate_errors);
            }
        }
        
        if($rates_msg > ''){
            $data['function_name'] = 'schedule_check_selling_rates_below_cost';
            $data['rates_msg'] = $rates_msg;
           // $data['test_debug'] = 1;
            erp_process_notification(1,$data);
        }
    }
}

// fix rates
function rates_cdr_roundup($amount, $currency)
{
	if (strtolower($currency) == 'zar') {
	    $amount = str_replace(',', '', $amount);
	    $amount = ceil($amount*100)/100;
	    return number_format((float) $amount, 2, '.', '');
	} else {
	    $amount = str_replace(',', '', $amount);
	    $amount = ceil($amount*1000)/1000;
	    return number_format((float) $amount, 3, '.', '');
	}
}

function fix_volume_rates_cdr(){
    return false;
 $cdr_table = 'call_records_outbound';
 //$cdr_table = 'call_records_outbound_lastmonth';
 $domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','volume')->get();
    
    
    $changes = [];
    foreach($domains as $d){
        $unlimited_exts =  \DB::connection('pbx')->table('v_extensions')->where('is_unlimited',1)->where('domain_uuid',$d->domain_uuid)->pluck('extension')->toArray();
        $rates = \DB::connection('pbx')->table('p_rates_partner_items')->where('country','south africa')->where('ratesheet_id',$d->ratesheet_id)->get();
        $airtime_diff = 0;
       
        foreach($rates as $rate){
            $selling_rate = rates_cdr_roundup($rate->{$d->volume_rate},$d->currency);
            // 2nd
            $current_duration = \DB::connection('pbx_cdr')->table($cdr_table)
            ->where('domain_name',$d->domain_name)
            ->where('country',$rate->country)
            ->where('start_time','>=','2023-08-24')
            ->whereNotIn('extension',$unlimited_exts)
            ->where('summary_destination',$rate->destination)
            ->where('duration','>',0)
            ->where('rate','!=',$selling_rate)
            ->sum(\DB::raw('duration'));
            $current_cost = \DB::connection('pbx_cdr')->table($cdr_table)
            ->where('domain_name',$d->domain_name)
            ->where('country',$rate->country)
            ->where('start_time','>=','2023-08-24')
            ->whereNotIn('extension',$unlimited_exts)
            ->where('summary_destination',$rate->destination)
            ->where('duration','>',0)
            ->where('rate','!=',$selling_rate)
            ->sum(\DB::raw('cost'));
            
            $new_cost = $current_duration * ($selling_rate / 60);
            $diff_cost = $new_cost-$current_cost;
            $changes[] = ['domain_name' => $d->domain_name,'current_cost'=>$current_cost,'new_cost'=>$new_cost,'diff_cost'=>$new_cost-$current_cost,'destination'=>$rate->destination];
            
            if($diff_cost!=0){
                /*
                $cdr_record = \DB::connection('pbx_cdr')->table($cdr_table)
                ->where('domain_name',$d->domain_name)
                ->where('country',$rate->country)
                ->where('hangup_time','>','2023-02-28')
                ->where('summary_destination',$rate->destination)
                ->where('duration','>',0)
                ->where('rate','!=',$selling_rate)
                ->orderBy('id','desc')
                ->get()
                ->first();
                */
               // if($selling_rate!=$cdr_record->rate)
            }
            
            
            // update cdr
            /*
                \DB::connection('pbx_cdr')->table('call_records_outbound')
                ->where('domain_name',$d->domain_name)
                ->where('country',$rate->country)
                ->where('hangup_time','>','2023-02-28')
                ->where('summary_destination',$rate->destination)
                ->where('duration','>',0)
                ->where('rate','!=',$selling_rate)
                ->update(['rate' => $selling_rate]);
            */
            
            
        }
       /*
        \DB::connection('pbx_cdr')->table('call_records_outbound')
        ->where('domain_name',$d->domain_name)
        ->where('hangup_time','>','2023-02-28')
        ->where('duration','>',0)
        ->update(['cost' => \DB::raw('(rate/60)*duration')]);
      */
        
    }
    //dd($changes);
  
    //dd(1);
    $changes = collect($changes)->groupBy('domain_name');
 
    foreach($domains as $d){
        
      
        $total = 0;
        foreach($changes as $domain => $c){
            if($d->domain_name == $domain){
                
                foreach($c as $cdr){
                    $total+=$cdr['diff_cost'];
                   
                }
            }
        }
        if($total == 0){
            continue;
        }
        if($total > 0){
        
        $airtime_history = [
            'created_at' => date('Y-m-d H:i:s'),
           
            'domain_uuid' => $d->domain_uuid,
            'total' => $total*-1,
            'balance' => $d->balance - $total,
            'type' => 'airtime_correction',

        ];
         //   \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
         //   \DB::connection('pbx')->table('v_domains')->where('domain_name', $d->domain_name)->decrement('balance', $total);  
        }else{
            $total = $total*-1;
            $airtime_history = [
                'created_at' => date('Y-m-d H:i:s'),
             
                'domain_uuid' => $d->domain_uuid,
                'total' => $total,
                'balance' => $d->balance + $total,
                'type' => 'airtime_correction',
    
            ];
           // \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
         //   \DB::connection('pbx')->table('v_domains')->where('domain_name', $d->domain_name)->increment('balance', $total);  
        }
        
    }
}



