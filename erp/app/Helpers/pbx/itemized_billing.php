<?php

function schedule_send_cdr_itemized_billing(){
  
    $subs = \DB::connection('default')->table('sub_services')
    ->where('status','!=','Deleted')
    ->where('product_id',1537)
    ->get();
  
    foreach($subs as $s){
        $filename = export_cdr('pbx_cdr',$s->account_id,date('Y-m-d',strtotime('last month')));
        
        $mail_data = [];
        $mail_data['attachment'] = $filename;
        $mail_data['internal_function'] = 'cdr_itemized_billing';
        $mail_data['billing_period'] = date('F Y',strtotime('last month'));
        //$mail_data['test_debug'] =1;
        erp_process_notification($s->account_id,$mail_data);
    }
}


function export_cdr_summary($connection, $account_id, $month = null, $hangup_cause = null)
{
    $table = 'call_records_outbound';
    if (!$month) {
        $month = date('Y-m-d');
    }

    $month_name = date('F Y',strtotime($month));
    if (date('Y-m') == date('Y-m', strtotime($month))) {
        $table = 'call_records_outbound';
        
    }elseif (date('Y-m', strtotime('-1 month')) == date('Y-m', strtotime($month))) {
        $table = 'call_records_outbound_lastmonth';
    } elseif (date('Y-m-01', strtotime($month)) != date('Y-m-01')) {
        $table = $table.'_'.strtolower(date('M', strtotime($month)));
    }

    $date_start = date('Y-m-01', strtotime($month));
    $date_end = date('Y-m-d 23:59');
    if ($account_id == 0) {
        $domain = 'all_domains';
        $domain_name = 'all_domains';
    } else {
        $domain = \DB::table('isp_voice_pbx_domains')->where('account_id', $account_id)->pluck('pabx_domain')->first();
        $domain_name = $domain;
    }

    $file_title = str_replace(['-',' '], '_', 'Call Records '.$domain_name.' '.$month_name);

    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;

    $call_records_query = \DB::connection($connection)->table($table)
        ->select('summary_destination',  \DB::raw('SUM(duration) AS duration '),  \DB::raw('SUM(cost) AS cost '))
        ->where('direction', 'outbound')
        ->where('hangup_time', '>=', $date_start)
        ->where('hangup_time', '<=', $date_end);

    if ($domain != 'all_domains') {
        $call_records_query->where('domain_name', $domain);
    }

    if ($hangup_cause) {
        $call_records_query->where('hangup_cause', $hangup_cause);
    } else {
        $call_records_query->where('duration', '>', 0);
    }
    $call_records_query->orderby('hangup_time');
    $call_records_query->groupBy('summary_destination');
    $call_records = $call_records_query->get();

    foreach ($call_records as $item) {
        $excel_list[] = [
            'Destination' => $item->summary_destination,
            'Duration' => $item->duration,
            'Cost' => $item->cost,
        ];
    }

    if (empty($excel_list)) {
        $excel_list[] = [
            'Destination' => '',
            'Duration' => '',
            'Cost' => '',
        ];
    }

    $account_currency = get_account_currency($account_id);
    $export = new App\Exports\CollectionExport();
    $export->seCurrencyColumns(['E','F','G'], $account_currency);
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

    return $file_name;
}