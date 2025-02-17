<?php

function schedule_teleshield_running_check(){
   
    $last_success = \DB::table('erp_form_events')->where('function_name','schedule_teleshield_call_rejected_check')->pluck('last_success')->first();
   
    if($last_success < date('Y-m-d H:i:s',strtotime('-240 minutes'))){
        $numbers_count = \DB::connection('pbx_cdr')->table('call_records_outbound')
        ->select('call_records_outbound.id')
        ->where('hangup_cause','CALL_REJECTED')
        ->leftJoin('p_teleshield_routing','p_teleshield_routing.formatted_number','=','call_records_outbound.ani')
        ->whereNull('p_teleshield_routing.original_network')
        ->whereRaw('LENGTH(ani) > 3')
        ->orderBy('call_records_outbound.hangup_time','asc')
        ->groupBy('ani')->get()->count();
     
        admin_email('Teleshield lookup not running. '.$numbers_count. ' left to check. Last run '.$last_success);    
        queue_sms(12, '0824119555', 'Teleshield lookup not running. '.$numbers_count. ' left to check. Last run '.$last_success, 1, 1);
    }else{
        if(date('Y-m-d H:i:s',strtotime('-20 minutes')) < $last_success){
            $numbers_count = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->select('call_records_outbound.id')
            ->where('hangup_cause','CALL_REJECTED')
            ->leftJoin('p_teleshield_routing','p_teleshield_routing.formatted_number','=','call_records_outbound.ani')
            ->whereNull('p_teleshield_routing.original_network')
            ->whereRaw('LENGTH(ani) > 3')
            ->orderBy('call_records_outbound.hangup_time','asc')
            ->groupBy('ani')->get()->count();
            if($numbers_count > 300){
     
               admin_email('Teleshield lookup processing slow. '.$numbers_count. ' left to check. Last run '.$last_success);    
               queue_sms(12, '0824119555', 'Teleshield lookup processing slow. '.$numbers_count. ' left to check. Last run '.$last_success, 1, 1);
            }
        }
    }
}


function schedule_teleshield_call_rejected_check(){
    if(!is_main_instance()){
        return false;
    }
    
    
    // limit to volume domains
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation','volume')->pluck('domain_name')->toArray();
    
    
    $numbers = \DB::connection('pbx_cdr')->table('call_records_outbound')
    ->whereIn('domain_name',$volume_domains)
    ->whereIn('hangup_cause',['CALL_REJECTED','NUMBER_NOT_FOUND'])
    ->leftJoin('p_teleshield_routing','p_teleshield_routing.formatted_number','=','call_records_outbound.ani')
    ->whereNull('p_teleshield_routing.original_network')
    ->whereRaw('LENGTH(ani) > 3')
    ->orderBy('call_records_outbound.hangup_time','asc')
    ->limit(200)->groupBy('ani')->pluck('ani')->unique()->toArray();


    if(!empty($numbers) && count($numbers)){
        foreach($numbers as $n){
            teleshield_routing($n);
        }
    }
}


function teleshield_import_operators(){
    \DB::connection('pbx_cdr')->table('p_teleshield_operators')->truncate();
    $arr =  file_to_array(public_path().'/0ALL_coverage.csv',';');
    
    $chunks = $arr->chunk(500);
    
    foreach ($chunks as $chunk){
        \DB::connection('pbx_cdr')->table('p_teleshield_operators')->insert($chunk->toArray());
    }
}

function teleshield_update_lastmonth(){
    if(!is_main_instance()){
        return false;
    }
    
    
    return false;
    $numbers = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
    ->whereIn('hangup_cause',['NUMBER_NOT_FOUND'])
    ->leftJoin('p_teleshield_routing','p_teleshield_routing.formatted_number','=','call_records_outbound_lastmonth.ani')
    ->whereNull('p_teleshield_routing.original_network')
    ->whereRaw('LENGTH(ani) > 3')
    ->orderBy('call_records_outbound_lastmonth.hangup_time','asc')
    ->groupBy('ani')->pluck('ani')->unique()->toArray();
   
dd($numbers);
    if(!empty($numbers) && count($numbers)){
        foreach($numbers as $n){
            teleshield_routing($n);
        }
    }
    
    
    
    return false;
    $numbers = \DB::connection('pbx_cdr')->table('p_teleshield_routing')->whereNull('tl')->where('original_network','!=','teleshield_error')->limit(400)->pluck('number')->toArray();
    foreach($numbers as $n){
        teleshield_routing($n,true);
    }
    
    
    
    /*
    $anis = \DB::connection('pbx_cdr')->table('call_records_outbound')
    ->where('hangup_cause','CALL_REJECTED')
    ->leftJoin('p_teleshield_routing','p_teleshield_routing.number','=','call_records_outbound.ani')
    ->whereNull('p_teleshield_routing.original_network')
    ->where('ani_source','operator_not_found')
    ->whereRaw('LENGTH(ani) > 3')
    ->groupBy('ani')->pluck('ani')->unique()->toArray();
    
    // 6154 
   
    $anis = \DB::connection('pbx_cdr')->table('call_records_outbound')
    ->where('hangup_cause','CALL_REJECTED')
    ->leftJoin('p_teleshield_routing','p_teleshield_routing.number','=','call_records_outbound.ani')
    ->whereNull('p_teleshield_routing.original_network')
    ->whereRaw('LENGTH(ani) > 3')
    ->where('ani_source','operator_not_found')->limit(500)->groupBy('ani')->pluck('ani')->unique()->toArray();
    foreach($anis as $ani){
        if(is_numeric($ani))
        teleshield_routing($ani);
    }
    */
}

function teleshield_set_ani_source(){
    
   // \DB::connection('pbx_cdr')->table('call_records_outbound')->where('hangup_cause','CALL_REJECTED')->update(['ani_source' => '']);
   
 /*
    $rows = \DB::connection('pbx_cdr')->table('call_records_outbound')->select('id')->where('hangup_cause','CALL_REJECTED')->where('ani_source','')->limit(5000)->get();
    foreach($rows as $r){
       
            $sql = "UPDATE call_records_outbound AS c
            SET c.ani_source = (
            SELECT p.full_name
            FROM p_teleshield_operators AS p
            WHERE (
            (c.ani LIKE CONCAT(p.network_id, '%'))
            OR
            (c.ani LIKE CONCAT('00', p.network_id, '%'))
            )
            )
            WHERE c.id=".$r->id." AND c.ani_source = '' AND LENGTH(c.ani) > 3;";
            \DB::connection('pbx_cdr')->statement($sql);
            \DB::connection('pbx_cdr')->table('call_records_outbound')->where('id',$r->id)->where('ani_source','')->update(['ani_source'=>'operator_not_found']);
        }
   */ 
   
   $anis = \DB::connection('pbx_cdr')->table('call_records_outbound')
   ->where('hangup_cause','CALL_REJECTED')
   ->leftJoin('p_teleshield_routing','p_teleshield_routing.number','=','call_records_outbound.ani')
   ->whereNull('p_teleshield_routing.original_network')
   ->where('ani_source','operator_not_found')->limit(500)->groupBy('ani')->pluck('ani')->unique()->toArray();
   
 
  
   foreach($anis as $ani){
       
        teleshield_routing($ani);
   }
    
    /*
    $sql = "UPDATE call_records_outbound AS c
    SET c.ani_source = (
        SELECT p.network
        FROM p_teleshield_operators AS p
        WHERE (
            (c.ani LIKE CONCAT(p.network_id, '%'))
            OR
            (c.ani LIKE CONCAT('00', p.network_id, '%'))
        )
    )
    WHERE c.ani_source = '' AND LENGTH(c.ani) > 3;";
    \DB::connection('pbx_cdr')->statement($sql);
    */
    /*
    \DB::connection('pbx_cdr')->table('call_records_outbound')
    ->where('ani_source', '')
    ->whereRaw('LENGTH(ani) > 3')
    ->join('p_teleshield_operators', function ($join) {
        $join->on('ani', 'like', \DB::raw('CONCAT(p_teleshield_operators.network_id, "%")'))
            ->orWhere('ani', 'like', \DB::raw('CONCAT("00", p_teleshield_operators.network_id, "%")'));
    })
    ->orderByRaw('LENGTH(p_teleshield_operators.network_id) DESC')
    ->update(['ani_source' => \DB::raw('p_teleshield_operators.full_name')]);
    */
    /*
    $sql = 'SELECT network_id, full_name
    FROM p_teleshield_operators
    ORDER BY LENGTH(network_id) DESC LIMIT 1000;';
    $rows = \DB::connection('pbx_cdr')->select($sql);
    foreach($rows as $row){
        $q = \DB::connection('pbx_cdr')->table('call_records_outbound');
        $q->where(function ($q) {
            $q->where('ani','like',$row->network_id.'%');
            $q->orWhere('ani','like','00'.$row->network_id.'%');
        });
     
        $q->whereRaw('(ani LIKE "'.$row->network_id.'%" or ani LIKE "00'.$row->network_id.'%")');
        $q->where('ani_source','');
        $q->whereRaw('LENGTH(ani) > 3');
        $q->update(['ani_source' => $row->full_name]);
    }
    */
    
}

function teleshield_enhanced_routing($number, $force_update = false){
   
   
    $formatted_number = ltrim($number,'0');
    $client = new GuzzleHttp\Client(['allow_redirects' => true]);
    $URI = 'https://api.tmtanalysis.com/e-teleshield/'.$formatted_number;
    $params['headers'] = ['X-Api-Key' => '9292b34c94c28f', 'X-Api-Secret' => 'c025708b24c5c9 ' ];
  
    $response = $client->post($URI, $params);
 
}

function teleshield_routing($number, $force_update = false){
    $formatted_number = ltrim($number,'0');
    if(empty($formatted_number) || strlen($formatted_number) < 3 || !is_numeric($formatted_number)){
        $err_data = [];
        $err_data['lookup_date'] = date('Y-m-d');
        $err_data['formatted_number'] = $formatted_number;
        $err_data['number'] = $number;
        $err_data['original_network'] = 'teleshield_error';
        $err_data['error'] = 'no lookup performed, non numeric ani';
        $err_data['created_at'] = date('Y-m-d H:i:s');
        
        \DB::connection('pbx_cdr')->table('p_teleshield_routing')->insert($err_data);
        return false;
    }
    $lookup = \DB::connection('pbx_cdr')->table('p_teleshield_routing')->where('formatted_number',$formatted_number)->get()->first();
    if(!$force_update && !empty($lookup) && !empty($lookup->id)){
        return $lookup;
    }
    
    /*
    curl -L -X POST -H 'X-Api-Key: apikey' -H 'X-Api-Secret: 
    apisecret' https://api.tmtanalysis.com/r-teleshield/40766610060
    */
   
    $client = new GuzzleHttp\Client(['allow_redirects' => true]);
    $URI = 'https://api.tmtanalysis.com/f-teleshield/'.$formatted_number;
    $params['headers'] = ['X-Api-Key' => '9292b34c94c28f', 'X-Api-Secret' => 'c025708b24c5c9 ' ];
  
    $response = $client->post($URI, $params);
 
   
    if($response->getStatusCode() == 200){
        $data = json_decode($response->getBody()->getContents(), true);
       
        if(!empty($data['error_message'])){
            $err_data = [];
            $err_data['lookup_date'] = date('Y-m-d');
            $err_data['formatted_number'] = $formatted_number;
            $err_data['number'] = $number;
            $err_data['original_network'] = 'teleshield_error';
            $err_data['error'] = $data['error_message'];
            $err_data['created_at'] = date('Y-m-d H:i:s');
       
     
            \DB::connection('pbx_cdr')->table('p_teleshield_routing')->insert($err_data);
            return $data['error_message'];
        }
        $data['lookup_date'] = date('Y-m-d');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['formatted_number'] = $formatted_number;
        $data['number'] = $number;
        if($data['itype'] == 1){
        $data['itype'] = 'mobile';    
        }
        if($data['itype'] == 2){
        $data['itype'] = 'fixed';    
        }
        
        $etypes = [
            1 => 'Audio Text',
            2 => 'Calling Cards',
            3 => 'Electronic Services',
            4 => 'Freephone – Tollfree',
            5 => 'Geographic',
            6 => 'Intermodal Number',
            7 => 'Internet Service Provider',
            8 => 'Local Rate',
            9 => 'Machine to Machine',
            10 => 'Mobile',
            11 => 'Mobile (CDMA)',
            12 => 'Mobile to Mobile',
            13 => 'National Geographic',
            14 => 'National Rate',
            15 => 'Non-intermodal CC',
            16 => 'Paging',
            17 => 'Payphone',
            18 => 'Personal',
            19 => 'Prefix type unknown',
            20 => 'Premium rate',
            21 => 'Routing code',
            22 => 'Satellite',
            23 => 'Shared Cost',
            24 => 'Short Codes Commercial',
            25 => 'Specialized mobile radio',
            26 => 'Telegram',
            27 => 'Universal Access',
            28 => 'Videotex',
            29 => 'Virtual Private Network',
            30 => 'Voicemail (geographic)',
            31 => 'Voicemail (mobile)',
            32 => 'VoIP',
            33 => 'Wireless Geographic',
        ];
        
        if($data['etype']){
            $data['etype'] = $etypes[$data['etype']];    
        }else{
            $data['etype'] = '';
        }
        $score_levels = [
            'L' => 'Low',
            'M' => 'Medium',
            'H' => 'High',
        ];
        if($data['tl']){
            $data['tl'] = $score_levels[$data['tl']];    
        }else{
            $data['tl'] = '';
        }
        $fields = app('erp_config')['module_fields']->where('module_id', 1971)->pluck('field')->toArray();
        if(empty($data['msrn_number_first_seen']) && !empty($data['msrn_first_seen'])){
            $data['msrn_number_first_seen'] = $data['msrn_first_seen'];
        }
        if(empty($data['msrn_number_last_seen']) && !empty($data['msrn_last_seen'])){
            $data['msrn_number_last_seen'] = $data['msrn_last_seen'];
        }
        if(empty($data['iprn_number_first_seen']) && !empty($data['iprn_first_seen'])){
            $data['iprn_number_first_seen'] = $data['iprn_first_seen'];
        }
        if(empty($data['iprn_number_last_seen']) && !empty($data['iprn_last_seen'])){
            $data['iprn_number_last_seen'] = $data['iprn_last_seen'];
        }
        foreach($data as $k => $v){
            if(!in_array($k,$fields)){
                unset($data[$k]);
            }
        }
        if($force_update){
            \DB::connection('pbx_cdr')->table('p_teleshield_routing')->updateOrInsert(['number' => $data['number']],$data);
        }else{
            \DB::connection('pbx_cdr')->table('p_teleshield_routing')->insert($data);
        }
    }
    
    $lookup = \DB::connection('pbx_cdr')->table('p_teleshield_routing')->where('formatted_number',$formatted_number)->get()->first();
    if(!empty($lookup) && !empty($lookup->id)){
        return $lookup;
    }
    
    return false;
}

function button_outbound_cdr_ani_teleshield_check($request){
    $cdr = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('id',$request->id)->get()->first();
    if(empty($cdr) || empty($cdr->ani)){
        return json_alert('Ani is not set','warning');
    }
    $data = teleshield_routing($cdr->ani);
    if(!is_object($data)){
        if($data){
            return json_alert($data,'error');
        }else{
            return json_alert('Lookup failed','error');
        }
    }
    
    $html = '<div class="card">';
    $html .= '<div class="card-body">ANI Number: '.$cdr->ani.'</div>';
    $html .= '<div class="card-body">';
    foreach($data as $k => $v){
    $html .= '<p><b>'.$k.'</b> '.$v.'</p>';
    }
    $html .= '</div>';
    $html .= '</div>';
    return $html;

}