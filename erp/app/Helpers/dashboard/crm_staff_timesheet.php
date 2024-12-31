<?php
/*
function schedule_task_duration_check_max_duration(){

   $tasks = \DB::connection('default')->table('crm_staff_tasks')->where('user_id','!=',0)->where('is_deleted',0)->where('overdue',0)->where('max_duration','>',0)->where('progress_status','In Progress')->get();

   $users = \DB::table('erp_users')->where('account_id',1)->get();
    foreach($tasks as $task){
        $date = \Carbon\Carbon::parse($task->start_time);
        $now = \Carbon\Carbon::now();

        $duration = $date->diffInMinutes($now);
        if($duration > $task->max_duration){

            \DB::connection('default')->table('crm_staff_tasks')
            ->where('id', $task->id)
            ->update(['overdue'=>1]);
            $user_name = $users->where('id',$task->user_id)->pluck('full_name')->first();

            //staff_email($user->id, 'Task overdue for '$user_name.'.', 'Task overdue: '.$task->name.'<br>User: '.$user_name,'ahmed@telecloud.co.za');

        }
    }


    \DB::connection('default')->table('crm_staff_tasks')->where('is_deleted',0)->whereIn('progress_status',['Done','Task Done'])->update(['overdue'=>0]);
    $tasks = \DB::connection('default')->table('crm_staff_tasks')->where('user_id','!=',0)->where('is_deleted',0)->where('overdue',1)->get();
    foreach($tasks as $task){

        $user_name = $users->where('id',$task->user_id)->pluck('full_name')->first();
        erp_notify('task'.$task->id, $task->user_id,'Task overdue', $task->name);
       // erp_notify('task'.$task->id, 1,'Task overdue', $task->name.'<br>'.$user_name);
    }
}
*/
function button_timesheet_view_layout($request)
{
    $task_id = \DB::connection('default')->table('crm_staff_timesheet')->where('id', $request->id)->pluck('row_id')->first();
    $process = \DB::connection('default')->table('crm_staff_tasks')->where('id', $task_id)->get()->first();

    if ($process->instance_id > 1) {
        $instance = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $process->instance_id)->get()->first();
        $module_id = \DB::connection($instance->db_connection)
            ->table('erp_grid_views')
            ->where('id', $process->layout_id)
            ->pluck('module_id')->first();
        $module_slug = \DB::connection($instance->db_connection)
            ->table('erp_cruds')
            ->where('id', $module_id)
            ->pluck('slug')->first();

        $url = 'https://'.$instance->domain_name.'/user/admin_login?user_id='.session('user_id').'&redirect_page='.$module_slug;

        $url .= '?layout_id='.$process->layout_id;

        if ($process->assigned_user_id > 0) {
            $layout_tracking_per_user = \DB::connection($instance->db_connection)
                ->table('erp_cruds')
                ->where('id', $module_id)
                ->pluck('layout_tracking_per_user')->first();
            if ($layout_tracking_per_user) {
                $url .= '&assigned_user_id='.$process->assigned_user_id;
            }
        }

    } else {

        $url = get_menu_url_from_module_id($process->module_id);

        $url .= '?layout_id='.$process->layout_id;

        if ($process->assigned_user_id > 0) {
            $layout_tracking_per_user = \DB::connection('default')
                ->table('erp_cruds')
                ->where('id', $process->module_id)
                ->pluck('layout_tracking_per_user')->first();
            if ($layout_tracking_per_user) {
                $url .= '&assigned_user_id='.$process->assigned_user_id;
            }
        }
    }

    return redirect()->to($url);

}

function schedule_timesheet_rejected_notifications()
{
    /*
    $users = \DB::table('erp_users')->where('account_id',1)->get();

    $rejected = \DB::connection('default')->table('crm_staff_timesheet')->where('rejected',1)->where('notified',0)->get();
    foreach($rejected as $row){

        $user_name = $users->where('id',$row->user_id)->pluck('full_name')->first();
        erp_notify('timesheet'.$row->id, $row->user_id,'Timesheet item rejected', $row->name);
        \DB::connection('default')->table('crm_staff_timesheet')->where('id',$row->id)->update(['notified'=>1]);
    }
    */
}

function staff_log_time($type, $start_time, $duration, $row_id, $instance_id = 0, $module_id = 0, $progress_status = '')
{

    if (! $instance_id) {
        $instance_id = session('instance')->id;
    }

    $instance = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $instance_id)->get()->first();

    if (empty($start_time)) {
        $db_start_time = \DB::connection($instance->db_connection)->table('crm_staff_tasks')->where('id', $row_id)->pluck('start_time')->first();

        return false;
    }
    if (empty($row_id)) {
        $db_start_time = \DB::connection($instance->db_connection)->table('crm_staff_tasks')->where('id', $row_id)->pluck('start_time')->first();

        return false;
    }

    if ($type == 'workflow') {
        $name = \DB::connection($instance->db_connection)->table('crm_workflow_tracking')->where('id', $row_id)->pluck('name')->first();

    }
    if ($type == 'task') {
        $task = \DB::connection($instance->db_connection)->table('crm_staff_tasks')->where('id', $row_id)->get()->first();
        $name = $task->name;
        if (! $progress_status) {
            $progress_status = $task->progress_status;
        }
    }
    if (empty($project_id)) {
        $project_id = 0;
    }
    $stop_time = date('Y-m-d H:i:s');
    if (! empty($task->stop_time)) {
        $stop_time = date('Y-m-d H:i:s', strtotime($task->stop_time));
    }

    $data = [
        'start_time' => $start_time,
        'duration' => $duration,
        'name' => $name,
        'progress_status' => $progress_status,
        'instance_id' => $instance_id,
        'created_at' => $stop_time,
        'created_day' => date('Y-m-d', strtotime($stop_time)),
        'created_by' => get_user_id_default(),
        'type' => ucfirst($type),
        'module_id' => $module_id,
        'row_id' => $row_id,
        'result_start' => $task->result_before_start,
        'result_end' => $task->result,
        'overdue' => $task->overdue,
    ];
    \DB::connection($instance->db_connection)->table('crm_staff_tasks')->where('id', $row_id)->increment('actual_duration', $duration);
    $data['is_core'] = 0;
    if ($type == 'workflow') {
        $data['user_id'] = \DB::connection($instance->db_connection)->table('crm_workflow_tracking')->where('id', $row_id)->pluck('user_id')->first();

    }

    if ($type == 'task') {
        $data['user_id'] = \DB::connection($instance->db_connection)->table('crm_staff_tasks')->where('id', $row_id)->pluck('user_id')->first();
    }
    if ($instance_id != 1) {
        $username = \DB::connection($instance->db_connection)->table('erp_users')->where('id', $data['user_id'])->pluck('username')->first();
        $data['user_id'] = \DB::connection('system')->table('erp_users')->where('username', $username)->pluck('id')->first();
    }
    /*
    if(empty($data['user_id']) && !empty(session('user_id'))){
        $data['user_id'] = session('user_id');
    }
    */

    \DB::connection('system')->table('crm_staff_timesheet')->insert($data);

    \DB::connection('default')->table('crm_staff_tasks')->whereIn('progress_status', ['Done', 'Task Done'])->update(['done_at' => \DB::raw('stop_time')]);

}

function timer_inprogress($user_id, $reset = false, $return_task = false)
{

    $username = \DB::connection('default')->table('erp_users')->where('id', $user_id)->pluck('username')->first();
    $instances = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', 1)->orWhere('sync_erp', 1)->get();
    foreach ($instances as $instance) {

        $instance_user_id = \DB::connection($instance->db_connection)->table('erp_users')->where('username', $username)->pluck('id')->first();

        if (\Schema::connection($instance->db_connection)->hasTable('crm_staff_tasks')) {

            $task_in_progress = \DB::connection($instance->db_connection)->table('crm_staff_tasks')
                ->where('progress_status', 'In Progress')
                ->where('is_deleted', 0)
                ->where('user_id', $instance_user_id)
                ->count();
            if ($return_task && $task_in_progress) {
                $task = \DB::connection($instance->db_connection)->table('crm_staff_tasks')
                    ->where('progress_status', 'In Progress')
                    ->where('is_deleted', 0)
                    ->where('user_id', $instance_user_id)
                    ->get()->first();
                if (! $task || ! $task->id) {
                    $response = [
                        'task' => '',
                        'task_start_time' => '',
                        'layout' => '',
                        'module' => '',
                        'instance' => '',
                    ];
                    return $response;
                }
                $layout_name = \DB::connection($instance->db_connection)->table('erp_grid_views')
                    ->where('id', $task->layout_id)
                    ->pluck('name')->first();
                $module_id = \DB::connection($instance->db_connection)->table('erp_grid_views')
                    ->where('id', $task->layout_id)
                    ->pluck('module_id')->first();
                $module_name = \DB::connection($instance->db_connection)->table('erp_cruds')
                    ->where('id', $module_id)
                    ->pluck('name')->first();
                $response = [
                    'task_id' => $task->id,
                    'project_id' => isset($task->project_id) ? $task->project_id : 0,
                    'task' => $task->name,
                    'task_start_time' => $task->start_time,
                    'layout' => $layout_name,
                    'module' => $module_name,
                    'instance' => $instance->name,
                ];

                return $response;
            }
            if ($reset && $task_in_progress) {
                $task = \DB::connection($instance->db_connection)->table('crm_staff_tasks')
                    ->where('progress_status', 'In Progress')
                    ->where('is_deleted', 0)
                    ->where('user_id', $instance_user_id)
                    ->get()->first();
                \DB::connection($instance->db_connection)->table('crm_staff_tasks')
                    ->where('progress_status', 'In Progress')
                    ->where('is_deleted', 0)
                    ->where('user_id', $instance_user_id)
                    ->update(['progress_status' => 'Not Done']);

                $date = \Carbon\Carbon::parse($task->start_time);

                $now = \Carbon\Carbon::now();
                $stop_time = $now->toDateTimeString();
                $duration = $date->diffInMinutes($now);
                \DB::connection($instance->db_connection)->table('crm_staff_tasks')
                    ->where('id', $task->id)
                    ->increment('duration', $duration);
                staff_log_time('task', $task->start_time, $duration, $task->id, $instance->id);
            }
        }

        if ($task_in_progress) {

            break;
        }
    }

    if ($return_task && ! $task_in_progress) {
        $response = [
            'task' => '',
            'task_start_time' => '',
            'layout' => '',
            'module' => '',
            'instance' => '',
        ];

        return $response;
    }

    return $task_in_progress;
}

function schedule_pause_tasks_old()
{

    $tasks = \DB::connection('default')->table('crm_staff_tasks')->where('progress_status', 'In Progress')->get();
    foreach ($tasks as $task) {
        $date = \Carbon\Carbon::parse($task->start_time);
        $now = \Carbon\Carbon::now();

        if (date('Y-m-d', strtotime($task->start_time)) == date('Y-m-d', strtotime('-1 day'))) {
            $stop_time = date('Y-m-d 17:15:00', strtotime('-1 day'));
            $now = \Carbon\Carbon::parse($stop_time);
        }

        $duration = $date->diffInMinutes($now);

        \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
        \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
        staff_log_time('task', $task->start_time, $duration, $task->id);
    }
    \DB::connection('system')->table('crm_staff_timesheet')->where('active_task', 1)->delete();
}

function schedule_check_daily_tasks()
{
    if (date('Y-m-d H:i') > date('Y-m-d 17:30')) {

        $tasks = \DB::connection('default')->table('crm_staff_tasks')->where('progress_status', 'In Progress')->get();
        foreach ($tasks as $task) {

            $employee = \DB::table('hr_employees')->where('status', '!=', 'Deleted')->where('user_id', $task->user_id)->get()->first();
            $stop_time = date('Y-m-d H:i', strtotime($employee->end_time.' '.$employee->timezone));
            $date = \Carbon\Carbon::parse($task->start_time);
            $now = \Carbon\Carbon::parse($stop_time);

            if (date('Y-m-d', strtotime($task->start_time)) == date('Y-m-d', strtotime('-1 day'))) {
                $stop_time = date('Y-m-d H:i', strtotime($stop_time.' -1 day'));
                $now = \Carbon\Carbon::parse($stop_time);
            }

            $duration = $date->diffInMinutes($now);

            \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
            \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
            staff_log_time('task', $task->start_time, $duration, $task->id);

        }
    }
}

function schedule_pause_tasks_timezone()
{

    $tasks = \DB::connection('default')->table('crm_staff_tasks')->where('progress_status', 'In Progress')->get();
    foreach ($tasks as $task) {
        $date = \Carbon\Carbon::parse($task->start_time);
        $now = \Carbon\Carbon::now();
        $stop_time = $now->toDateTimeString();
        $duration = $date->diffInMinutes($now);
        $employee = \DB::table('hr_employees')->where('status', '!=', 'Deleted')->where('user_id', $task->user_id)->get()->first();
        $pause_tasks = false;
        if ($employee) {

            if (! empty($employee->lunch_time)) {
                $lunch_time = date('Y-m-d H:i', strtotime($employee->lunch_time.' '.$employee->timezone));

                if (date('Y-m-d H:i') >= $lunch_time && date('Y-m-d H:i') < date('Y-m-d H:i', strtotime($lunch_time.' + 20 minutes'))) {
                    $pause_tasks = true;
                }
            }

            if (! empty($employee->end_time)) {
                $end_time = date('Y-m-d H:i', strtotime($employee->end_time.' '.$employee->timezone));
                if (date('Y-m-d H:i') >= $end_time) {
                    $pause_tasks = true;
                }
            }
        }

        if ($pause_tasks) {
            \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
            \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
            staff_log_time('task', $task->start_time, $duration, $task->id);
        }
    }
    \DB::connection('system')->table('crm_staff_timesheet')->where('active_task', 1)->delete();
}

function schedule_pause_tasks_lunch_old()
{

    $tasks = \DB::connection('default')->table('crm_staff_tasks')->where('progress_status', 'In Progress')->get();
    foreach ($tasks as $task) {
        $date = \Carbon\Carbon::parse($task->start_time);
        $now = \Carbon\Carbon::parse(date('Y-m-d 13:30'));
        $stop_time = $now->toDateTimeString();
        $duration = $date->diffInMinutes($now);

        \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
        \DB::connection('default')->table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
        staff_log_time('task', $task->start_time, $duration, $task->id);
    }
    \DB::connection('system')->table('crm_staff_timesheet')->where('active_task', 1)->delete();

}

function schedule_set_task_duration_check()
{
    return false;
    if (is_main_instance() && is_working_hours()) {
        //get loggedin users
        $user_ids = \DB::table('hr_timesheet')->where('user_id', '!=', 1)->where('user_id', '!=', 3696)->where('created_date', date('Y-m-d'))->pluck('user_id')->toArray();

        foreach ($user_ids as $user_id) {
            if (in_array($user_id, [1])) {
                continue;
            }

            $task_in_progress = timer_inprogress($user_id);
            $user = \DB::table('erp_users')->where('id', $user_id)->get()->first();
            if (! $task_in_progress) {
                //   staff_email($user_id, 'Task timer not set for '.$user->full_name.'.', 'Task timer not set for '.$user->full_name.'.<br>Please set task to In Progress.','ahmed@telecloud.co.za');
            }
        }

    }

}
