<?php

function work_start_times_dropdown(){
    $list = [];
    $i = 20000;
    $timesheets = \DB::connection('default')->table('hr_timesheet')
    ->select('erp_users.full_name','hr_timesheet.start_time')
    ->join('erp_users','erp_users.id','=','hr_timesheet.user_id')
    ->where('created_date', date('Y-m-d'))
    ->get();
    foreach ($timesheets as $timesheet) {
        $list[] = ['url' =>'#', 'menu_name' => $timesheet->full_name.': '.$timesheet->start_time, 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 0, 'childs' => []];
        $i++;
    }
    
    return $list;
}

function schedule_timesheet_log_minutes()
{
    \DB::connection('default')->table('hr_timesheet')->whereRaw("start_time NOT LIKE CONCAT('%', created_date, '%')")->update(['start_time' => \DB::raw('CONCAT(created_date," ",start_time)')]);
    \DB::connection('default')->table('hr_timesheet')->whereNotNull('end_time')->whereRaw("end_time NOT LIKE CONCAT('%', created_date, '%')")->update(['end_time' => \DB::raw('CONCAT(created_date," ",end_time)')]);
    \DB::connection('default')->table('hr_timesheet')->whereNotNull('end_time')->whereRaw("end_time=created_date")->update(['end_time' => \DB::raw('CONCAT(created_date," 17:00:00")')]);  
    \DB::connection('default')->table('hr_timesheet')->whereNull('end_time')->update(['minutes_logged' => \DB::raw('TIMESTAMPDIFF(MINUTE, start_time, NOW())')]);
    \DB::connection('default')->table('hr_timesheet')->whereNull('end_time')->update(['hours_logged' => \DB::raw('TIMESTAMPDIFF(HOUR, start_time, NOW())')]);
    \DB::connection('default')->table('hr_timesheet')->whereNotNull('end_time')->update(['minutes_logged' => \DB::raw('TIMESTAMPDIFF(MINUTE, start_time, end_time)')]);
    \DB::connection('default')->table('hr_timesheet')->whereNotNull('end_time')->update(['hours_logged' => \DB::raw('TIMESTAMPDIFF(HOUR,start_time, end_time)')]);
}

function timesheet_in()
{
    timesheet_out();

    if (session('role_level') == 'Admin') {
        $record_exists = \DB::connection('default')->table('hr_timesheet')->where(['user_id' => session('user_id'), 'created_date' => date('Y-m-d')])->count();
        if ($record_exists) {
            \DB::connection('default')->table('hr_timesheet')->where(['user_id' => session('user_id'), 'created_date' => date('Y-m-d')])->update(['end_time' => null]);
        } else {
            \DB::connection('default')->table('hr_timesheet')->insert(['user_id' => session('user_id'), 'created_date' => date('Y-m-d H:i:s'), 'start_time' => date('Y-m-d H:i:s'), 'period' => date('Y-m')]);
        }
    }
}

function timesheet_out()
{
    if (session('role_level') == 'Admin') {
      
        \DB::connection('default')->table('hr_timesheet')->where('end_time', '')->update(['end_time'=>null]);
        \DB::connection('default')->table('hr_timesheet')->where('user_id', session('user_id'))->whereNull('end_time')->update(['end_time' => date('H:i:s')]);
    }
}

function timesheet_merge()
{
    $day = date('Y-m-d');
    $users = \DB::connection('default')->table('hr_timesheet')->where('created_date', date('Y-m-d'))->groupby('user_id')->pluck('user_id')->toArray();
    foreach ($users as $user) {
        $count = \DB::connection('default')->table('hr_timesheet')->where(['user_id' => $user, 'created_date' => $day])->count();
        if ($count > 1) {
            $first = \DB::connection('default')->table('hr_timesheet')->where(['user_id' => $user, 'created_date' => $day])->pluck('id')->first();

            $start_time = \DB::connection('default')->table('hr_timesheet')
                ->where(['user_id' => $user, 'created_date' => $day])
                ->orderby('start_time', 'asc')->pluck('start_time')->first();

            $end_time = \DB::connection('default')->table('hr_timesheet')
                ->where(['user_id' => $user, 'created_date' => $day])
                ->orderby('end_time', 'desc')->pluck('end_time')->first();

            if (empty($end_time) && date('H:i:s') < '17:30:00') {
                $end_time = '17:00:00';
            }

            if (empty($end_time) && date('H:i:s') > '17:30:00') {
                $end_time = date('H:i:s');
            }

            \DB::connection('default')->table('hr_timesheet')
                ->where(['user_id' => $user, 'created_date' => $day])
                ->update(['start_time' => $start_time, 'end_time' => $end_time]);

            \DB::connection('default')->table('hr_timesheet')->where(['user_id' => $user, 'created_date' => $day])->where('id', '!=', $first)->delete();
        }
    }
}

function timesheet_avg($timesheet_id)
{
    $timesheet = \DB::connection('default')->table('hr_timesheet')->where('id', $timesheet_id)->get()->first();

    $time_total = 0;
    if (!empty($timesheet->end_time)) {
        $to_time = strtotime($timesheet->end_time);
        $from_time = strtotime($timesheet->start_time);
        $time_total = ($to_time - $from_time) / 60 / 60;
    }

    return number_format($time_total, 2, '.', ',');
}
