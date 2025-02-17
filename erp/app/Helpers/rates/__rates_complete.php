<?php

function schedule_set_lcr_rates(){
    rates_complete_set_lowest_rate();
    admin_rates_summary_set_lowest_active();
}


function update_destinations_from_operators(){
    $teleshield_operators = \DB::connection('pbx_cdr')
    ->table('p_teleshield_operators')
    ->where('iso2','!=', 'ZA')
    ->where('network_id','NOT LIKE','27%')
    ->orderByRaw('LENGTH(network_id) DESC')
    ->get();
    foreach($teleshield_operators as $teleshield_operator){
      
        \DB::connection('pbx')->table('p_rates_destinations')->where('id',$teleshield_operator->network_id)->update(['destination' => $teleshield_operator->full_name]);
        
    }
}

function afterdelete_update_summary($request)
{
    if (!empty(session('event_db_record'))) {
        $beforedelete_row = session('event_db_record');
        $rates_count = \DB::connection('pbx')->table('p_rates_complete')
        ->where('gateway_uuid', $beforedelete_row->gateway_uuid)
        ->where('country', $beforedelete_row->country)
        ->where('destination', $beforedelete_row->destination)->count();
        if (!$rates_count) {
            \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $beforedelete_row->gateway_uuid)
            ->where('country', $beforedelete_row->country)
            ->where('destination', $beforedelete_row->destination)->delete();
        }
    }
}

function aftersave_update_summary($request)
{
    // $gateway = \DB::connection('pbx')->table('v_gateways')->where('id', $request->gateway_uuid)->get()->first();
    // if ($gateway->enabled == 'true' && ($gateway->use_rate || $gateway->use_rate_international)) {
    //     import_rates_summary_from_rates_complete($gateway->gateway_uuid);
    // }
    // admin_rates_summary_set_lowest_active();
}

function button_rates_complete_set_lowest_rates($request)
{
    rates_complete_set_lowest_rate();
    return json_alert('Done');
}

function rates_complete_set_country(){
    $sql = "UPDATE p_rates_complete
    JOIN p_rates_destinations ON p_rates_complete.destination_id = p_rates_destinations.id
    SET p_rates_complete.destination = p_rates_destinations.destination;";
    \DB::connection('pbx')->statement($sql);
    
    $sql = "UPDATE p_rates_complete
    JOIN p_rates_destinations ON p_rates_complete.destination_id = p_rates_destinations.id
    SET p_rates_complete.country = p_rates_destinations.country
    WHERE p_rates_complete.country IS NULL or p_rates_complete.country = '';";
    \DB::connection('pbx')->statement($sql);
    
    $sql = "UPDATE p_rates_complete 
    SET country = (
    SELECT p_rates_destinations.country
    FROM p_rates_destinations
    WHERE p_rates_complete.destination_id LIKE '%' || p_rates_destinations.id || '%'
    ORDER BY LENGTH(p_rates_destinations.id) DESC
    LIMIT 1
    )
    WHERE p_rates_complete.country IS NULL;";

    \DB::connection('pbx')->statement($sql);
    \DB::connection('pbx')->table('p_rates_complete')->where('destination_id', 'LIKE', '27%')->where('country', '!=', 'south africa')->update(['country' => 'south africa']);  
}

function afterimport_update_rates_complete()
{
    rates_complete_set_country();
    rates_complete_set_lowest_rate();
}


function rates_complete_set_lowest_rate(){
    try {
        $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->pluck('gateway_uuid')->toArray();
        \DB::connection('pbx')->table('p_rates_complete')->whereNotIn('gateway_uuid', $gateway_uuids)->delete();
        $international_gateways = \DB::connection('pbx')->table('v_gateways')->where('use_rate_international',1)->pluck('gateway_uuid')->toArray();
   
        $disabled_international_gateways = \DB::connection('pbx')->table('v_gateways')->where('enabled','false')->where('use_rate_international',1)->pluck('gateway_uuid')->toArray();
        \DB::connection('pbx')->table('p_rates_complete')->update(['lowest_rate' => 0,'status'=>'Disabled']);
        \DB::connection('pbx')->table('p_rates_complete')->whereNotIn('gateway_uuid',$disabled_international_gateways)->whereIn('gateway_uuid',$international_gateways)->update(['status'=>'Enabled']);
        \DB::connection('pbx')->table('p_rates_complete')->whereIn('gateway_uuid',$disabled_international_gateways)->update(['status'=>'GATEWAY_DISABLED']);
     
        \DB::connection('pbx')->statement("UPDATE p_rates_complete
        JOIN (
        SELECT destination_id, MIN(cost) AS mincost
        FROM p_rates_complete
        WHERE cost > 0
        AND gateway_uuid IN  ('".implode("','",$international_gateways)."')
        GROUP BY destination_id
        ) AS p_rates_complete_min ON p_rates_complete.destination_id = p_rates_complete_min.destination_id AND p_rates_complete.cost = p_rates_complete_min.mincost
        SET p_rates_complete.lowest_rate = 1;"); 
  
        \DB::connection('pbx')->statement("UPDATE p_rates_complete
        JOIN (
        SELECT destination_id, MIN(cost) AS mincost
        FROM p_rates_complete
        WHERE cost > 0
        AND gateway_uuid IN ('".implode("','",$international_gateways)."')
        GROUP BY destination_id
        ) AS p_rates_complete_min ON p_rates_complete.destination_id = p_rates_complete_min.destination_id AND p_rates_complete.cost = p_rates_complete_min.mincost
        SET p_rates_complete.status = 'Enabled'
        WHERE p_rates_complete.status != 'GATEWAY_DISABLED';"); 
     
    }catch(\Throwable $ex){
        admin_email('Rates complete set lowest rate failed',$ex->getMessage());
    }
    
}