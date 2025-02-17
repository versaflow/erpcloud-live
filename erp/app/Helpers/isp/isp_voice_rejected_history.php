<?php

function schedule_call_temp_blocked_truncate()
{
    return false;

    $date = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $query = \DB::connection('pbx_cdr')->table('mon_blacklist')
        ->where('hangup_time', '<', $date);
    $query->update(['is_deleted'=>'1']);
    
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','volume')->pluck('domain_name')->toArray();
    
    foreach($volume_domains as $volume_domain){
        $sql = "INSERT IGNORE INTO mon_blacklist (duration, gateway, domain_name, hangup_time, hangup_cause, caller_id_number, callee_id_number, destination, ani, ani_source, callee_source)
        SELECT 
        duration,
        gateway,
        domain_name,
        hangup_time,
        hangup_cause,
        caller_id_number,
        callee_id_number,
        destination,
        ani,
        ani_source,
        callee_source
        FROM call_records_outbound
        WHERE domain_name ='".$volume_domain."' and (duration=6 or duration=7 or duration=8) and hangup_time>'".$date."'";
       
        \DB::connection('pbx_cdr')->statement($sql);
    }
    
    $ids = \DB::connection('pbx_cdr')->table('mon_blacklist')->where('callee_source','')->limit(10000)->pluck('id')->toArray();
    if(count($ids)>0){
        $sql = "UPDATE mon_blacklist
        JOIN (
        SELECT network, prefix
        FROM p_numbering_plan
        ) AS subquery
        SET mon_blacklist.callee_source = subquery.network
        WHERE
        mon_blacklist.callee_id_number LIKE CONCAT(subquery.prefix, '%')
        AND mon_blacklist.callee_source = '' AND mon_blacklist.id IN (".implode(',',$ids).");";
      
        \DB::connection('pbx_cdr')->statement($sql);
    }
}

function blacklist_update_from_teleshield()
{
    /*
    SELECT call_records_outbound.*, 
    100 * p_hangup_causes.normal_hangup/p_hangup_causes.id as normal_hangup,
    100 * p_hangup_causes.internal_issue/p_hangup_causes.id as internal_issue,
    100 * p_hangup_causes.supplier_issue/p_hangup_causes.id as supplier_issue,
    p_teleshield_routing.original_network as teleshield_network,
    p_teleshield_routing.cc as teleshield_iso_country_code,
    p_teleshield_routing.error as teleshield_error,
    p_teleshield_routing.ts as teleshield_fraud_score
    FROM call_records_outbound
    LEFT JOIN p_hangup_causes on p_hangup_causes.name = call_records_outbound.hangup_cause
    LEFT JOIN p_teleshield_routing on p_teleshield_routing.number = call_records_outbound.ani
    */

    \DB::connection('pbx_cdr')->table('mon_blacklist')->truncate();
    
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','volume')->pluck('domain_name')->toArray();
    
    foreach($volume_domains as $volume_domain){
        $sql = "INSERT IGNORE INTO mon_blacklist (duration, gateway, domain_name, hangup_time, hangup_cause, caller_id_number, callee_id_number, destination, ani, ani_source, callee_source,ts,teleshield_error)
        SELECT 
        duration,
        gateway,
        domain_name,
        hangup_time,
        hangup_cause,
        caller_id_number,
        callee_id_number,
        destination,
        ani,
        ani_source,
        callee_source,
        p_teleshield_routing.ts,
        p_teleshield_routing.error
        FROM call_records_outbound
        JOIN p_teleshield_routing on p_teleshield_routing.number = call_records_outbound.ani
        WHERE
        (p_teleshield_routing.original_network = 'teleshield_error' 
        OR p_teleshield_routing.ts > 0) 
        AND domain_name ='".$volume_domain."'";
      
        \DB::connection('pbx_cdr')->statement($sql);
        
    }
    
    $ids = \DB::connection('pbx_cdr')->table('mon_blacklist')->where('callee_source','')->where('is_deleted',0)->pluck('id')->toArray();
    if(count($ids)>0){
        $sql = "UPDATE mon_blacklist
        JOIN (
        SELECT network, prefix
        FROM p_numbering_plan
        ) AS subquery
        SET mon_blacklist.callee_source = subquery.network
        WHERE
        mon_blacklist.callee_id_number LIKE CONCAT(subquery.prefix, '%')
        AND mon_blacklist.callee_source = '' AND mon_blacklist.id IN (".implode(',',$ids).");";
      
        \DB::connection('pbx_cdr')->statement($sql);
    }
}












function block_temporary_set_ani_source(){
    $cdr_tables = get_tables_from_schema('pbx_cdr');
    $outbound_tables = ['call_records_outbound_lastmonth'];
    /*
    foreach($cdr_tables as $table){
        if(str_contains($table,'call_records_outbound')  && str_contains($table,'archive') && !str_contains($table,'variables')){
            $outbound_tables[] = $table;
        }  
    }
    */
   
    foreach($outbound_tables as $outbound_table){
        $records = \DB::connection('pbx_cdr')->table('mon_block_temporary')->select('id','ani')->where('ani_source','')->get()->unique('ani');
        foreach($records as $row){
            $ani_source = \DB::connection('pbx_cdr')->table($outbound_table)->select('ani_source')->where('ani',$row->ani)->limit(1)->pluck('ani_source')->first();
            if($ani_source){
                \DB::connection('pbx_cdr')->table('mon_block_temporary')->where('ani_source','')->where('ani',$row->ani)->update(['ani_source'=>$ani_source]); 
            }
        }
        // cross database queries is not implemented in postgres
        /*
        $sql = "UPDATE fusionpbx.mon_block_temporary
        SET ani_source=subquery.ani_source,
        FROM (
        SELECT ani_source FROM cdr.".$outbound_table." 
        GROUP BY ani) AS subquery
        WHERE 
        fusionpbx.mon_block_temporary.ani_source = ''
        fusionpbx.mon_block_temporary.ani=subquery.ani;";
        */  
    }
}








function consecutive_temp_blocked(){
   // return false;
    $date_5min = date('Y-m-d H:i:s', strtotime('-5 minutes'));
   
    // Block calls with 3 consecutive calls to the same destination with the same duration > 0
    // gateway,ani,domain_name,hangup_time,hangup_cause,caller_id_number,callee_id_number,destination
    
    $calls = \DB::connection('pbx_cdr')->table('call_records_outbound')
    ->select(\DB::raw('destination, MAX(callee_id_number), MAX(callee_source), MAX(duration) as duration, MAX(gateway) as gateway, MAX(ani) as ani, MAX(domain_name) as domain_name, MAX(hangup_time) as hangup_time, MAX(caller_id_number), MAX(hangup_cause) as hangup_cause, MAX(caller_id_number) as caller_id_number'))
    ->where('duration','>',0)
    ->where('hangup_time','like',date('Y-m-d').'%')
    ->groupBy('destination')
    ->havingRaw('COUNT(destination) > 2 AND COUNT(DISTINCT duration) = 1')
    ->get();

    foreach($calls as $call){
        $records = \DB::connection('pbx_cdr')->table('call_records_outbound')
        ->where('hangup_time','>',$date_5min)
        ->where('callee_id_number',$call->callee_id_number)
        ->where('duration',$call->duration)
        ->get();
        foreach($records as $r){
            $data = [
                'gateway' => $r->gateway,
                'ani' => $r->ani,
                'domain_name' => $r->domain_name,
                'hangup_time' => $r->hangup_time,
                'hangup_cause' => $r->hangup_cause,
                'caller_id_number' => $r->caller_id_number,
                'callee_id_number' => $r->callee_id_number,
                'callee_source' => $r->callee_source,
                'duration' => $r->duration,
                'destination' =>  $r->destination
            ]; 
           
            \DB::connection('pbx_cdr')->table('mon_block_temporary')->insert($data);
            
        }
    }
}

function button_rejected_history_delete_callee_id_number($request)
{
    return view('__app.button_views.clear_callee_id_number');
}