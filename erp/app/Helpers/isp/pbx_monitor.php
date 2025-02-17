<?php

function schedule_update_pbx_whitelist_porting(){
 
    $porting_db_tables = [
        'p_ported_numbers_gnp_1',
        'p_ported_numbers_gnp_2',
        'p_ported_numbers_gnp_3',
        'p_ported_numbers_gnp_4',
        'p_ported_numbers_gnp_5',
        'p_ported_numbers_crdb_6',
        'p_ported_numbers_crdb_7',
        'p_ported_numbers_crdb_8',
    ];
    foreach($porting_db_tables as $porting_db_table){
        \DB::connection('pbx_cdr')->statement("
            INSERT IGNORE INTO p_callee_whitelist (callee_id_number,source)
            SELECT msisdn,'ported_number'
            FROM porting.".$porting_db_table."
            GROUP BY msisdn;
        ");
    }
}

function schedule_update_pbx_whitelist_cdr(){
    // \DB::connection('pbx_cdr')->table('p_callee_whitelist')->truncate();   
    /*
    \DB::connection('pbx_cdr')->statement("
    INSERT IGNORE INTO p_callee_whitelist (callee_id_number)
    SELECT REPLACE(callee_id_number, '+', '')
    FROM call_records_outbound
    WHERE duration > 0 and duration <> 6 and duration <> 7 
    AND hangup_time > '".date('Y-m-d H:i',strtotime('-1 day'))."'
    GROUP BY REPLACE(callee_id_number, '+', '');
    ");
    */
    \DB::connection('pbx_cdr')->statement("
        INSERT IGNORE INTO p_callee_whitelist (callee_id_number)
        SELECT REPLACE(callee_id_number, '+', '')
        FROM call_records_outbound
        WHERE duration > 0 and duration <> 6 and duration <> 7 
        GROUP BY REPLACE(callee_id_number, '+', '');
    ");
    
    \DB::connection('pbx_cdr')->statement("
        INSERT IGNORE INTO p_callee_whitelist (callee_id_number)
        SELECT REPLACE(callee_id_number, '+', '')
        FROM call_records_outbound_lastmonth
        WHERE duration > 0 and duration <> 6 and duration <> 7 
        GROUP BY REPLACE(callee_id_number, '+', '');
    ");
  
  
}

function schedule_pbx_whitelist_delete_old_records(){
    
    $date = date('Y-m-d',strtotime('-3 months'));
    $date = date('Y-m-d', strtotime($data.' -1 weeks'));
    \DB::connection('pbx_cdr')->table('p_callee_whitelist')->where('created_at','<',$date)->delete();
}