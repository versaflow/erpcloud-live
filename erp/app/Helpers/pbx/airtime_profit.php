<?php 

function schedule_populate_airtime_profitability(){
    $rows = \DB::connection('pbx_cdr')->select('
    SELECT
    gateway,
    hangup_cause,
    billing_method,
    hangup_date AS call_date,
    COUNT(id) AS num_calls,
    SUM(duration_mins) AS total_duration,
    SUM(cost) AS sale,
    SUM(admin_cost) AS cost,
    SUM(cost - admin_cost) AS profit
    FROM
       call_records_outbound_lastmonth
    GROUP BY
    hangup_cause,
    billing_method,
    gateway,
    hangup_date;');
    foreach($rows as $row){
        $data = (array) $row;
        $wdata = ['gateway' => $row->gateway,'call_date' => $row->call_date,'hangup_cause' => $row->hangup_cause,'billing_method' => $row->billing_method];
        \DB::connection('pbx_cdr')->table('p_airtime_profitability')->updateOrInsert($wdata,$data);
    }
    $rows = \DB::connection('pbx_cdr')->select('
    SELECT
    gateway,
    hangup_cause,
    billing_method,
    hangup_date AS call_date,
    COUNT(id) AS num_calls,
    SUM(duration_mins) AS total_duration,
    SUM(cost) AS sale,
    SUM(admin_cost) AS cost,
    SUM(cost - admin_cost) AS profit
    FROM
       call_records_outbound
    GROUP BY
    hangup_cause,
    billing_method,
    gateway,
    hangup_date;');
    foreach($rows as $row){
        $data = (array) $row;
        $wdata = ['gateway' => $row->gateway,'call_date' => $row->call_date,'hangup_cause' => $row->hangup_cause,'billing_method' => $row->billing_method];
       
        \DB::connection('pbx_cdr')->table('p_airtime_profitability')->updateOrInsert($wdata,$data);
    }
    // set gpp
    $rows = \DB::connection('pbx_cdr')->table('p_airtime_profitability')->get();
    foreach($rows as $row){
        $gpp = ($row->cost != 0) ? $row->profit / $row->cost : 0;
        
        \DB::connection('pbx_cdr')->table('p_airtime_profitability')->where('id',$row->id)->update(['gpp'=>$gpp]);
    }
}