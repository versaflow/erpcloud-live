<?php

function schedule_flexmonster_export()
{
    $reports = \DB::table('erp_reports')->where('invalid_query', 0)->pluck('id')->toArray();
    foreach ($reports as $report) {
        flexmonster_export($report);
    }
}

function flexmonster_export($report_id, $format = 'html', $instance_id = false, $uniq_id = false)
{
    if (!$instance_id) {
        $instance_id = session('instance')->id;
    }

    $data = [
        'instance_id' => $instance_id,
        'uniq_id' => $uniq_id,
        'report_id' => $report_id,
        'user_id' => 1,
    ];



    $token = \Erp::encode($data);
    $cmd = 'cd /home/cloudtel/cloudtelecoms.io/html/reports && node pivot.js '.$token.' '.$format.';';
    //aa($report_id);
    //dd($cmd);
    $result = \Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    //aa($result);
    
    //dd($cmd,$result);
    return $result;
}

function flexmonster_download($report_id, $format = 'html', $delete = true)
{
    // export needs to be seperated from download
    // ajax -> export -> setinterval ajax check file ready -> show download link

    $report_name = \DB::table('erp_reports')->where('id', $report_id)->pluck('name')->first();
    $file_name = $report_name.'.'.$format;
    $file_path = storage_path('reports').'/'.session('instance')->id.'/'.$report_id.'.'.$format;

    $headers = [
        'Content-Type: application/octet-stream',
    ];
    if ($delete) {
        return response()->download($file_path, $file_name, $headers)->deleteFileAfterSend(true);
    } else {
        return response()->download($file_path, $file_name, $headers);
    }
}
