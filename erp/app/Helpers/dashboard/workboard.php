<?php

function schedule_workboard_processes_set_results()
{

    \DB::table('crm_staff_tasks')->where('progress_status', 'Complete')->update(['progress_status' => 'Not Done']);
    $process_ids = \DB::table('crm_staff_tasks')->where('layout_id', '>', 0)->pluck('id')->toArray();

    \DB::table('crm_accounts')->whereRaw('debtor_status_id!=accountability_current_status_id')->update(['accountability_match' => 0]);
    \DB::table('crm_accounts')->whereRaw('debtor_status_id=accountability_current_status_id')->update(['accountability_match' => 1]);

    \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', '>', 0)->whereNull('progress_status')->update(['progress_status' => 'Not Done']);

    $processes = \DB::connection('default')->table('crm_staff_tasks')
        ->where('layout_id', '>', 0)
        ->where('is_deleted', 0)
        ->get();

    if (is_main_instance()) {
        foreach ($processes as $process) {
            // vd($process);
            update_workboard_layout_tracking_result($process->layout_id, $process->instance_id, $process->assigned_user_id);
        }
        //workboard_populate_bottlenecks();
    }

}

function get_layout_sql($layout_id)
{
    $layout = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();
    $module = \DB::table('erp_cruds')->where('id', $layout->module_id)->get()->first();
    $model = new \App\Models\ErpModel;

    $model = new \App\Models\ErpModel;

    $model->setModelData($layout->module_id, $conn);

    $layout_state = json_decode($layout->aggrid_state);
    if (empty($layout_state->filterState)) {
        $filter_state = [];
    } else {
        $filter_state = (array) json_decode(json_encode($layout_state->filterState), true);
    }

    $model->setWorkboardQuery(true);
    $request_object = new \Illuminate\Http\Request;
    $request_object->setMethod('POST');
    $request_object->request->add(['layout_tracking' => 1]);
    $request_object->request->add(['rowGroupCols' => []]);
    $request_object->request->add(['valueCols' => []]);
    $request_object->request->add(['groupKeys' => []]);

    if ($filter_state) {

        foreach ($filter_state as $col => $state) {
            if ($state['filterType'] == 'set') {
                $field_display = \DB::connection('default')->table('erp_module_fields')->where('field', $col)->where('module_id', $layout->module_id)->pluck('opt_db_display')->first();
                $field_display_count = 0;
                if (! empty($field_display)) {
                    $field_display_arr = explode(',', $field_display);
                    $field_display_count = count($field_display_arr);
                }
                foreach ($state['values'] as $i => $val) {

                    if ($field_display_count > 1) {
                        $val_arr = explode(' - ', $val);

                        $filter_state[$col]['values'][$i] = $val_arr[0];
                    }
                }
            }
        }

    }

    if (! empty($filter_state) && is_array($filter_state) && count($filter_state) > 0) {
        $request_object->request->add(['filterModel' => $filter_state]);
    } else {
        $request_object->request->add(['filterModel' => []]);
    }

    if (! empty($layout_state->searchtext) && $layout_state->searchtext != ' ') {
        $request_object->request->add(['search' => $layout_state->searchtext]);
    }

    $request_object->request->add(['return_sql' => 1]);

    $SQL = $model->buildSql($request_object, 'data');

    return $SQL;
}

function schedule_workboard_log_start()
{
    \DB::table('crm_staff_tasks')->update(['start_time' => null, 'duration' => 0, 'yesterday_duration' => 0]);
    \DB::table('crm_staff_tasks')->update(['actual_duration' => 0]);
    $previous_day = get_previous_workday();
    $yesterday_totals = \DB::table('crm_staff_timesheet')
        ->select('row_id', \DB::raw('SUM(duration) as total_duration'))
        ->where('created_at', 'like', $previous_day.'%')
        ->groupBy('row_id')
        ->get();
    foreach ($yesterday_totals as $total) {
        \DB::table('crm_staff_tasks')->where('id', $total->row_id)->update(['yesterday_duration' => $total->total_duration]);
    }

}

function schedule_workboard_log_end()
{

    set_todays_score();

    \DB::table('crm_staff_tasks')->update(['duration' => 0]);

    \DB::table('crm_staff_tasks')->update(['before_yesterday_result' => \DB::raw('yesterday_result')]);
    \DB::table('crm_staff_tasks')->update(['yesterday_result' => \DB::raw('result')]);
    \DB::table('crm_staff_tasks')->update(['yesterday_difference' => \DB::raw('todays_score')]);

}

function button_workboard_templates_copy_to_workboard($request)
{
    $group = \DB::table('crm_workboard_templates')->where('id', $request->id)->pluck('group')->first();
    $groups = \DB::table('crm_workboard_templates')->where('group', $group)->where('is_deleted', 0)->orderBy('sort_order')->get()->groupBy('group');
    $sort_order = 0;
    foreach ($groups as $group => $list) {
        $data = [];

        $data['name'] = $group;

        $data['role_id'] = 35;
        $data['instance_id'] = session('instance')->id;
        $data['type'] = 'Task';
        $data['progress_status'] = 'Not Done';
        $data['sort_order'] = $sort_order;

        $group_id = \DB::table('crm_staff_tasks')->insertGetId($data);

        foreach ($list as $channel) {
            $data = [];

            $data['name'] = $channel->name;
            $data['parent_id'] = $group_id;

            $data['role_id'] = 35;
            $data['instance_id'] = session('instance')->id;
            $data['type'] = 'Task';
            $data['progress_status'] = 'Not Done';
            $data['sort_order'] = $sort_order;

            \DB::table('crm_staff_tasks')->insert($data);
            $sort_order++;
        }
        $sort_order++;
    }

    return json_alert('Done');
}

function update_instance_workboards()
{
    if (! is_main_instance()) {
        return false;
    }
    $tasks = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->where('layout_id', '>', 0)->get();
    $db_conns = ['moviemagic', 'eldooffice'];
    foreach ($db_conns as $c) {
        foreach ($tasks as $task) {
            \DB::connection($c)->table('crm_staff_tasks')->where('module_id', $task->module_id)->where('layout_id', $task->layout_id)->update(['role_id' => $task0->role_id, 'is_deleted' => 0]);
        }
    }
}

function beforesave_workboard_check_title_unique($request)
{

    // required for workboard node grouping
    if (! empty($request->new_record)) {
        $e = \DB::table('crm_staff_tasks')->where('name', $request->name)->count();
        if ($e) {
            return 'Title already in use';
        }
    } elseif (empty($request->new_record)) {
        $e = \DB::table('crm_staff_tasks')->where('id', '!=', $request->id)->where('name', $request->name)->count();
        if ($e) {
            return 'Title already in use';
        }
    }
}

function onload_organize_workboard_company_nodes()
{
    if (! is_main_instance()) {
        return;
    }
    $tasks = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->get();
    $role_ids = $tasks->pluck('role_id')->unique()->toArray();
    $instances = \DB::table('erp_instances')->orderBy('sort_order')->get();
    $company_node_sort = 0;
    foreach ($role_ids as $role_id) {

        foreach ($instances as $j => $i) {
            $company_node_sort++;
            $modules_order = \DB::connection($i->db_connection)->table('erp_menu')->where('location', 'main_menu')->where('unlisted', 0)->orderBy('sort_order')->pluck('module_id')->toArray();
            $c = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->where('type', '!=', 'Task')->where('role_id', $role_id)->where('instance_id', $i->id)->count();
            if (strtoupper($i->name) == 'CLOUD TELECOMS') {
                $i->name = 'TELECLOUD';
            }
            if ($c > 0) {
                $company_node_id = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->where('company_node_id', $i->id.$role_id)->pluck('id')->first();
                if (! $company_node_id) {
                    $data = [
                        'type' => 'Task',
                        'name' => strtoupper($i->name),
                        'instance_id' => $i->id,
                        'progress_status' => 'Not Done',
                        'role_id' => $role_id,
                        'company_node_id' => $i->id.$role_id,
                        'parent_id' => 0,
                        //'sort_order' => $company_node_sort,
                    ];
                    $company_node_id = \DB::table('crm_staff_tasks')->insertGetId($data);
                } else {
                    \DB::table('crm_staff_tasks')->where('is_deleted', 0)->where('company_node_id', $i->id.$role_id)->update(['parent_id' => 0]);
                }
                \DB::table('crm_staff_tasks')->where('type', '!=', 'Task')->where('is_deleted', 0)->where('company_node_id', 0)->where('role_id', $role_id)->where('instance_id', $i->id)->update(['parent_id' => $company_node_id]);

                // sort node processes by menu order
                /*
                if(is_superadmin()){
                    $tasks = \DB::table('crm_staff_tasks')->where('is_deleted',0)->where('parent_id',$company_node_id)->get();
                    $sort = $tasks->min('sort_order');
                    $processed_task_ids = [];
                    foreach($modules_order as $k => $id){
                        $module_tasks = $tasks->where('module_id',$id)->all();
                        foreach($module_tasks as $task){
                            $processed_task_ids[] = $task->id;
                            \DB::table('crm_staff_tasks')->where('id',$task->id)->update(['sort_order' => $sort]);
                            $sort++;
                        }
                    }
                    foreach($tasks as $t){
                        if(!in_array($t->id, $processed_task_ids)){
                            \DB::table('crm_staff_tasks')->where('id',$t->id)->update(['sort_order' => $sort]);
                            $sort++;
                        }
                    }
                }
                */

            } else {

                $node_id = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->where('company_node_id', $i->id.$role_id)->pluck('id')->first();

                if ($node_id) {
                    \DB::table('crm_staff_tasks')->where('id', $node_id)->delete();
                }
            }
        }
    }
    // update_workboard_sorting();

}

function update_workboard_sorting()
{
    // $role_ids = \DB::table('crm_staff_tasks')->where('is_deleted',0)->orderBy('sort_order','asc')->pluck('role_id')->unique()->toArray();

    // foreach($role_ids as $role_id) {
    //     $sort_order = 0;
    //     $rows = \DB::table('crm_staff_tasks')->select('name','id','sort_order','parent_id')->where('role_id',$role_id)->where('parent_id',0)->where('is_deleted',0)->orderBy('sort_order','desc')->get();
    //     foreach ($rows as $i => $r) {
    //         \DB::table('crm_staff_tasks')->where('id',$r->id)->update(['sort_order' => $sort_order]);
    //         $sort_order++;

    //         $sub_rows = \DB::table('crm_staff_tasks')->select('name','id','sort_order','parent_id')->where('role_id',$role_id)->where('parent_id',$r->id)->where('is_deleted',0)->orderBy('sort_order','desc')->get();
    //         foreach($sub_rows as $sr) {
    //             \DB::table('crm_staff_tasks')->where('id',$sr->id)->update(['sort_order' => $sort_order]);
    //             $sort_order++;
    //         }
    //     }
    // }
    /*
    $role_ids = \DB::table('crm_staff_tasks')->where('is_deleted',0)->orderBy('sort_order','asc')->pluck('role_id')->unique()->toArray();

    foreach($role_ids as $role_id){
        $sort_order = 0;
        $rows = \DB::table('crm_staff_tasks')->select('name','id','sort_order','parent_id')->where('company_node_id','>',0)->where('role_id',$role_id)->where('parent_id',0)->where('is_deleted',0)->orderBy('sort_order','asc')->get();

        foreach ($rows as $i => $r) {

            \DB::table('crm_staff_tasks')->where('id',$r->id)->update(['sort_order' => $sort_order]);
            $sort_order++;
            $sub_rows = \DB::table('crm_staff_tasks')->select('name','id','sort_order','parent_id')->where('role_id',$role_id)->where('parent_id',$r->id)->where('is_deleted',0)->orderBy('sort_order','asc')->get();

            foreach($sub_rows as $sr){

                \DB::table('crm_staff_tasks')->where('id',$sr->id)->update(['sort_order' => $sort_order]);
                $sort_order++;
            }
        }
        $rows = \DB::table('crm_staff_tasks')->select('name','id','sort_order','parent_id')->where('company_node_id',0)->where('role_id',$role_id)->where('parent_id',0)->where('is_deleted',0)->orderBy('sort_order','asc')->get();

        foreach ($rows as $i => $r) {

            \DB::table('crm_staff_tasks')->where('id',$r->id)->update(['sort_order' => $sort_order]);
            $sort_order++;
            $sub_rows = \DB::table('crm_staff_tasks')->select('name','id','sort_order','parent_id')->where('role_id',$role_id)->where('parent_id',$r->id)->where('is_deleted',0)->orderBy('sort_order','asc')->get();

            foreach($sub_rows as $sr){

                \DB::table('crm_staff_tasks')->where('id',$sr->id)->update(['sort_order' => $sort_order]);
                $sort_order++;
            }
        }
    }
    */
}

function afterdelete_workboard_validate_nodes($request)
{
    $role_ids = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->pluck('role_id')->unique()->toArray();

    foreach ($role_ids as $role_id) {
        $parent_ids = \DB::table('crm_staff_tasks')->where('role_id', $role_id)->where('is_deleted', 0)->where('parent_id', 0)->pluck('id')->filter()->unique()->toArray();
        foreach ($parent_ids as $parent_id) {
            \DB::table('crm_staff_tasks')->where('role_id', '!=', $role_id)->where('is_deleted', 0)->where('parent_id', $parent_id)->update(['role_id' => $role_id]);
        }

        $parent_ids = \DB::table('crm_staff_tasks')->where('role_id', $role_id)->where('is_deleted', 0)->pluck('parent_id')->filter()->unique()->toArray();
        foreach ($parent_ids as $parent_id) {
            $e = \DB::table('crm_staff_tasks')->where('role_id', $role_id)->where('is_deleted', 0)->where('id', $parent_id)->count();
            if (! $e) {
                $deleted = \DB::table('crm_staff_tasks')->where('role_id', $role_id)->where('is_deleted', 1)->where('id', $parent_id)->count();
                if ($deleted) {
                    \DB::table('crm_staff_tasks')->where('role_id', $role_id)->where('is_deleted', 0)->where('parent_id', $parent_id)->update(['is_deleted' => 1]);
                }
            }
        }
    }
}

function schedule_update_workboard_stats()
{
    if (is_main_instance()) {

        $rows = \DB::table('crm_staff_timesheet')->where('created_day', date('Y-m-d'))->get();

        foreach ($rows as $row) {
            $date = \Carbon\Carbon::parse(date('Y-m-d H:i', strtotime($row->start_time)));
            $now = \Carbon\Carbon::parse(date('Y-m-d H:i', strtotime($row->created_at)));
            $stop_time = $now->toDateTimeString();
            $duration = $date->diffInMinutes($now);
            \DB::table('crm_staff_timesheet')->where('id', $row->id)->update(['duration' => $duration]);
        }

        \DB::table('crm_staff_timesheet')->update(['duration_hours' => \DB::raw('duration/60')]);
        update_workboard_stats();
        update_workboard_parent_progress_status();

    }
}

function schedule_workboard_check_in_progress()
{
    return false;
    $user_ids = \DB::table('crm_staff_tasks')->whereNotIn('user_id', [1, 3696])->where('start_time', 'LIKE', date('Y-m-d').'%')->pluck('id')->toArray();
    foreach ($user_ids as $user_id) {
        $task_in_progress = \DB::table('crm_staff_tasks')->where('user_id', $user_id)->where('is_deleted', 0)->where('progress_status', 'In Progress')->count();
        if (! $task_in_progress) {
            $current_conn = \DB::getDefaultConnection();
            $user = \DB::table('erp_users')->where('id', $user_id)->pluck('email')->first();
            $user_email = $user->email;
            $subject = 'No task in progress for '.$user->full_name;
            set_db_connection();
            if (empty($var)) {
                $var = $subject;
            }

            $function_variables = get_defined_vars();
            $data['internal_function'] = 'debug_email';
            $data['exception_email'] = true;
            $data['to_email'] = $user_email;
            $data['cc_email'] = 'ahmed@telecloud.co.za';
            $data['bcc_email'] = 'ahmed@telecloud.co.za';
            $data['form_submit'] = 1;
            $data['subject'] = $subject;
            $data['var'] = nl2br($var);
            erp_process_notification(1, $data, $function_variables);
            set_db_connection($current_conn);
        }
    }
}

function button_update_workboard($request)
{
    schedule_workboard_processes_set_results();

    return json_alert('Done');
}

function workboard_results_inprogress_duration($rows)
{

    $staff_stats = get_staff_current_tasks();
    foreach ($staff_stats as $staff_stat) {
        if (isset($staff_stat['task_id'])) {
            $date = \Carbon\Carbon::parse($staff_stat['task_start_time']);
            $now = \Carbon\Carbon::now();
            $duration = $date->diffInMinutes($now);
            foreach ($rows as $i => $row) {
                if ($row->id == $staff_stat['task_id']) {
                    $rows[$i]->duration += $duration;
                    $rows[$i]->actual_duration += $duration;
                }
            }
        }
    }

    return $rows;
}

function afterdelete_process_delete_layout($request)
{

    if ($request->layout_id > 0 && $request->instance_id != session('instance')->id) {
        $db_conn = \DB::connection('system')->table('erp_instances')->where('id', $request->instance_id)->pluck('db_connection')->first();
        \DB::connection($db_conn)->table('erp_grid_views')->where('id', $request->layout_id)->update(['track_layout' => 0]);
        \DB::connection($db_conn)->table('crm_staff_tasks')->where('layout_id', $request->layout_id)->update(['is_deleted' => 1]);
    } else {
        \DB::table('erp_grid_views')->where('id', $request->layout_id)->update(['track_layout' => 0]);
    }
}

/*
   // copy sales layouts
    $user_id = 75;
    $copy_user_id = 56;

    $processes = \DB::table('crm_staff_tasks')->where('user_id',$user_id)->where('is_deleted',0)->where('layout_id','>',0)->get();
    foreach($processes as $process){
        $layout_id = $process->layout_id;
        $layout = \DB::table('erp_grid_views')->where('id',$layout_id)->get()->first();
        $data = (array) $layout;
        $data['name'] = str_replace(' - Nani','',$layout->name).' - Jibril';
        unset($data['id']);
        $new_id = \DB::table('erp_grid_views')->insertGetId($data);
        $process_data = (array) $process;
        $process_data['layout_id'] = $new_id;
        $process_data['user_id'] = $copy_user_id;
        unset($process_data['id']);


        \DB::table('crm_staff_tasks')->insert($process_data);

    }
*/

function aftersave_workboard_set_target_success($request)
{
    $task = \DB::table('crm_staff_tasks')->where('id', $request->id)->get()->first();
    if ($task->layout_id > 0 && $task->instance_id == session('instance')->id) {
        \DB::table('erp_grid_views')->where('id', $task->layout_id)->update(['result_field' => $task->result_field, 'target' => $task->target, 'target_success' => $task->target_success]);
    }
    if ($task->layout_id > 0 && $task->instance_id != session('instance')->id) {
        $instance = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $task->instance_id)->get()->first();
        \DB::connection($instance->db_connection)->table('erp_grid_views')->where('id', $task->layout_id)->update(['result_field' => $task->result_field, 'target' => $task->target, 'target_success' => $task->target_success]);
    }
}

function button_process_add_layout_dashboard($request)
{
    $dashboard_sort_order = \DB::connection('default')->table('erp_grid_views')->max('dashboard_sort_order');
    $dashboard_sort_order++;
    \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['show_on_dashboard' => 1, 'join_chart_role_id' => 1, 'dashboard_sort_order' => $dashboard_sort_order]);

    return json_alert('Layout added to Dashboard');
}

function button_process_remove_layout_dashboard($request)
{
    \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['show_on_dashboard' => 0, 'join_chart_role_id' => 0]);

    return json_alert('Layout removed from Dashboard');
}

function status_button_ajax($request)
{
    $status_key = $request->button_data->status_key;
    $status_field = $request->button_data->status_field;
    $status_function = $request->button_data->status_function;
    $status_function = trim($status_function);
    if ($status_function) {
        if (! function_exists($status_function)) {
            return json_alert($status_function.' does not exists', 'warning');
        }

        return $status_function($request);
    }
    if (! $status_key) {
        return json_alert('Status value key not set on button', 'warning');
    }
    if (! $status_field) {
        return json_alert('Status field not set on button', 'warning');
    }

    $module = \DB::connection('default')->table('erp_cruds')->where('id', $request->mod_id)->get()->first();
    \DB::connection($module->connection)->table($request->db_table)->where($module->db_key, $request->id)->update([$status_field => $status_key]);

    return json_alert('Done');
}

// dashboards
function get_dashboard_module_links()
{
    $module_ids = \DB::table('erp_grid_views')->where('show_on_dashboard', 1)->pluck('module_id')->unique()->toArray();
    $modules = \DB::table('erp_cruds')->whereIn('id', $module_ids)->get();
    $list = [];

    $i = 10000;
    foreach ($modules as $module) {
        $list[] = ['url' => $module->slug, 'menu_name' => $module->name, 'menu_icon' => '', 'menu_type' => 'module_filter', 'module_id' => $module->id, 'id' => $i, 'new_tab' => 1, 'childs' => []];
        $i++;
    }

    return $list;
}

// PROCESSES BUTTONS
function button_workboard_update_process_score($request)
{
    $process = \DB::table('crm_staff_tasks')->where('id', $request->id)->get()->first();

    update_workboard_layout_tracking_result($process->layout_id, $process->instance_id, $process->assigned_user_id);
    //workboard_populate_bottlenecks($request->id);

}

function button_workboard_view_layout($request)
{

    $process = \DB::table('crm_staff_tasks')->where('id', $request->id)->get()->first();
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

// PROCESSES EVENTS

function afterdelete_workboard_stop_tracking($request)
{

    $task = \DB::connection('default')->table('crm_staff_tasks')->where('id', $request->id)->get()->first();
    if ($task->progress_status == 'In Progress') {
        $date = \Carbon\Carbon::parse($task->start_time);

        $now = \Carbon\Carbon::now();
        $stop_time = $now->toDateTimeString();
        $duration = $date->diffInMinutes($now);

        \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
        \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);

        $module_id = 1898;
        staff_log_time('task', $task->start_time, $duration, $task->id, session('instance')->id, $module_id);
    }

}

function set_todays_score()
{

    $layout_row_ids = \DB::table('crm_staff_tasks')->where('start_time', 'like', date('Y-m-d').'%')->where('layout_id', '>', 0)->pluck('id')->toArray();
    $task_scores = \DB::table('crm_staff_timesheet')
        ->select('name', 'row_id')
        ->where('created_at', 'like', date('Y-m-d').'%')
        ->whereNotIn('row_id', $layout_row_ids)
        ->groupBy('row_id')->get();

    foreach ($task_scores as $score) {
        $last_task_update = \DB::table('crm_staff_timesheet')->where('row_id', $score->row_id)->orderBy('id', 'desc')->get()->first();
        $todays_score = $last_task_update->result_start - $last_task_update->result_end;
        if ($todays_score < 0) {
            $todays_score = 0;
        }
        \DB::table('crm_staff_tasks')->where('id', $score->row_id)->update(['todays_score' => $todays_score]);
    }
    $layout_row_ids = \DB::table('crm_staff_tasks')->where('start_time', 'like', date('Y-m-d').'%')->where('layout_id', '>', 0)->pluck('id')->toArray();
    $layout_scores = \DB::table('crm_staff_timesheet')
        ->select('name', 'row_id', \DB::raw('SUM(result_start)-SUM(result_end) as score'))
        ->where('created_at', 'like', date('Y-m-d').'%')
        ->whereIn('row_id', $layout_row_ids)
        ->groupBy('row_id')->get();

    foreach ($layout_scores as $score) {
        \DB::table('crm_staff_tasks')->where('id', $score->row_id)->update(['todays_score' => $score->score]);
    }
}

function update_workboard_stats()
{
    set_todays_score();
    \DB::table('crm_staff_tasks')->whereNotIn('progress_status', ['In Progress', 'Postponed'])->where('result', 0)->update(['user_id' => 0]);
    \DB::table('crm_staff_tasks')->where('type', 'Task')->where('progress_status', 'Done')->update(['progress_status' => 'Task Done']);
    $layout_ids = \DB::table('crm_staff_tasks')->where('layout_id', '>', 0)->where('is_deleted', 0)->pluck('layout_id')->toArray();
    $report_layout_ids = \DB::table('erp_grid_views')->whereIn('id', $layout_ids)->where('layout_type', 'Report')->pluck('id')->toArray();
    \DB::table('crm_staff_tasks')->whereIn('layout_id', $report_layout_ids)->update(['layout_type' => 'Report']);
    \DB::table('crm_staff_tasks')->whereIn('layout_id', $report_layout_ids)->where('report_update_frequency', '')->update(['report_update_frequency' => 'Daily']);
    \DB::table('crm_staff_tasks')->whereNotIn('layout_id', $report_layout_ids)->update(['layout_type' => 'Layout', 'report_update_frequency' => '', 'report_last_update' => null]);

    \DB::table('crm_staff_tasks')->where('layout_id', 0)->update(['type' => 'Task']);
    \DB::table('crm_staff_tasks')->where('layout_id', '>', 0)->update(['type' => 'Layout']);
    \DB::table('crm_staff_tasks')->where('progress_status', 'Done')->where('type', 'Layout')->update(['progress_status' => 'Done']);
    $layout_ids = \DB::table('crm_staff_tasks')->where('layout_id', '>', 0)->where('is_deleted', 0)->pluck('layout_id')->toArray();
    $report_ids = \DB::table('erp_grid_views')->whereIn('id', $layout_ids)->where('layout_type', 'Report')->where('is_deleted', 0)->pluck('id')->toArray();
    \DB::table('crm_staff_tasks')->whereIn('layout_id', $report_ids)->where('is_deleted', 0)->update(['type' => 'Report']);

    \DB::table('crm_staff_tasks')->where('type', 'Task')->where('progress_status', '')->update(['progress_status' => 'Not Done']);
    \DB::table('crm_staff_tasks')->where('type', 'Task')->whereNull('progress_status')->update(['progress_status' => 'Not Done']);

    $task_ids = \DB::connection('system')->table('crm_staff_timesheet')->where('created_day', date('Y-m-d'))->where('instance_id', session('instance')->id)->pluck('row_id')->toArray();
    if (count($task_ids) == 0) {
        $task_ids = [0];
    }

    //$sql to set task name from module and layout name

    // update total worked hours
    $current_tasks = get_staff_current_tasks();
    $users = \DB::connection('system')->table('erp_users')->where('id', '!=', 1)->where('account_id', 1)->where('is_deleted', 0)->get();
    foreach ($users as $user) {
        //  \DB::connection('system')->table('crm_staff_timesheet')->update(['duration_hours' => \DB::raw('duration/60')]);
        $task_stats = \DB::connection('system')->table('crm_staff_timesheet')
            ->select(\DB::raw('count(*) as completed'), \DB::raw('sum(duration_hours) as hours_spent'))
            ->where('crm_staff_timesheet.created_at', 'like', date('Y-m-d').'%')
            ->where('crm_staff_timesheet.user_id', $user->id)
            ->get()->first();

        // if todays date is monday return last friday
        $previous_day = get_previous_workday();

        $previous_task_stats = \DB::connection('system')->table('crm_staff_timesheet')
            ->select(\DB::raw('count(*) as completed'), \DB::raw('sum(duration_hours) as hours_spent'))
            ->where('crm_staff_timesheet.created_at', 'like', $previous_day.'%')
            ->where('crm_staff_timesheet.user_id', $user->id)
            ->get()->first();
        if (! empty($previous_task_stats->hours_spent)) {
            $yesterday_hours_worked = $previous_task_stats->hours_spent;
        } else {
            $yesterday_hours_worked = 0;
        }
        if (! empty($task_stats->hours_spent)) {
            $total_hours_worked = $task_stats->hours_spent;
        } else {
            $total_hours_worked = 0;
        }
        $current_task_start_time = collect($current_tasks)->where('main_user_id', $user->id)->pluck('task_start_time')->first();
        if (! empty($current_task_start_time)) {
            $date = \Carbon\Carbon::parse($current_task_start_time);
            $now = \Carbon\Carbon::now();
            $duration = $date->diffInMinutes($now);
            if ($duration) {

                $total_hours_worked += $duration / 60;
            }
        }

    }

    $system_users = \DB::connection('system')->table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->get();
    $external_tasks = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->where('layout_id', '>', 0)->get();
    foreach ($external_tasks as $t) {

        $i = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $t->instance_id)->get()->first();
        $instance_conn = $i->db_connection;
        $layout = \DB::connection($instance_conn)->table('erp_grid_views')->select('name', 'module_id')->where('id', $t->layout_id)->get()->first();
        $layout_name = \DB::connection($instance_conn)->select("SELECT  CONCAT(module.name, ': ', erp_grid_views.name) as name
        FROM erp_grid_views
        JOIN erp_cruds AS module ON erp_grid_views.module_id = module.id
        WHERE erp_grid_views.id=".$t->layout_id.';')[0]->name;
        $menu_id = \DB::connection($instance_conn)->table('erp_menu')->where('unlisted', 0)->where('location', 'main_menu')->where('module_id', $t->module_id)->pluck('id')->first();
        if ($menu_id) {
            $toplevel_menu_id = get_toplevel_menu_id($menu_id, $instance_conn);
            if ($toplevel_menu_id) {
                $menu_name = \DB::connection($instance_conn)->table('erp_menu')->where('id', $toplevel_menu_id)->pluck('menu_name')->first();
                if ($menu_name) {
                    $layout_name = $menu_name.' - '.$layout_name;
                }
            }
        }

        $instance_prefix = '';
        if ($instance_conn == 'moviemagic') {
            $instance_prefix = 'MM';
        }
        if ($instance_conn == 'eldooffice') {
            $instance_prefix = 'EO';
        }
        if ($instance_conn == 'telecloud') {
            $instance_prefix = 'TC';
        }
        $name = $instance_prefix.' - '.$layout_name;
        if ($t->assigned_user_id) {
            $username = $system_users->where('id', $t->assigned_user_id)->pluck('full_name')->first();
            if ($username) {
                $name .= ' - '.$username;
            }
        }
        \DB::table('crm_staff_tasks')->where('id', $t->id)->update(['name' => $name]);
    }

    \DB::table('crm_staff_tasks')->where('type', 'Task')->where('is_deleted', 0)->where('progress_status', '!=', 'Task Done')->update(['result' => 1]);
    \DB::table('crm_staff_tasks')->where('type', 'Task')->where('is_deleted', 0)->where('progress_status', 'Task Done')->update(['result' => 0]);

    // SET ROW COLORS

    \DB::table('crm_staff_tasks')->where('is_deleted', 0)->where('progress_status', 'Done')->update(['row_color' => 'process_complete']);

    // workboard recon
    $layout_ids = \DB::table('crm_staff_tasks')->where('instance_id', session('instance')->id)->where('is_deleted', 0)->where('layout_id', '>', 0)->pluck('layout_id')->toArray();

    \DB::table('erp_grid_views')->update(['on_workboard' => 0]);
    \DB::table('erp_grid_views')->whereIn('id', $layout_ids)->update(['on_workboard' => 1]);

    // // clear deadlines for done items
    // \DB::table('crm_staff_tasks')->where('deadline','>','')->whereIn('progress_status',['Done','Task Done'])->update(['deadline' => null]);

}

function schedule_set_accountability_match()
{

    \DB::table('crm_accounts')->whereRaw('debtor_status_id!=accountability_current_status_id')->update(['accountability_match' => 0]);
    \DB::table('crm_accounts')->whereRaw('debtor_status_id=accountability_current_status_id')->update(['accountability_match' => 1]);
}

// PROCESSES FUNCTIONS
// LAYOUT TRACKING
function workboard_layout_set_tracking_per_user($instance_id)
{
    if (! is_main_instance()) {
        return false;
    }
    $conn = \DB::connection('system')->table('erp_instances')->where('id', $instance_id)->pluck('db_connection')->first();
    $modules = \DB::connection($conn)->table('erp_cruds')->where('layout_tracking_per_user', 1)->get();
    $layouts = \DB::connection($conn)
        ->table('erp_grid_views')
        ->selectRaw('*,erp_grid_views.id as id,erp_grid_views.name as name')
        ->join('erp_cruds', 'erp_cruds.id', '=', 'erp_grid_views.module_id')
        ->where('erp_cruds.layout_tracking_per_user', 1)
        ->where('erp_grid_views.track_layout', 1)
        ->where('erp_grid_views.is_deleted', 0)
        ->get();

    $system_users = \DB::connection('system')->table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->get();
    $conn_users = \DB::connection($conn)->table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->get();

    foreach ($layouts as $layout) {

        if (! empty($layout->main_instance_id)) {
            $system_task = $system_tasks->where('module_id', $layout->module_id)->where('layout_id', $layout->id)->where('instance_id', 1)->first();
            if (! empty($system_task)) {

                if (! empty($system_task->limit_instance_id)) {
                    $limit_instance_ids = explode(',', $system_task->limit_instance_id);
                    if (! in_array($instance_id, $limit_instance_ids)) {
                        \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->update(['is_deleted' => 1]);
                        \DB::connection($conn)->table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->update(['is_deleted' => 1]);

                        continue;
                    }
                }
            }
        }

        $layout_id = $layout->id;
        if (count($conn_users) > 0) {
            $role_users = $system_users;
            if ($layout->module_id == 1923) {
                $role_users = $system_users->where('role_id', 62)->all();
                $role_user_ids = collect($role_users)->pluck('id')->toArray();
                \DB::table('crm_staff_tasks')
                    ->where('layout_id', $layout_id)
                    ->where('instance_id', $instance_id)
                    ->where('assigned_user_id', '>', 0)
                    ->whereNotIn('assigned_user_id', $role_user_ids)
                    ->delete();
            }
            foreach ($role_users as $system_user) {
                $exists = \DB::table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->where('assigned_user_id', $system_user->id)->count();
                if (! $exists) {
                    $unasssigned_task_id = \DB::table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->where('assigned_user_id', 0)->pluck('id')->first();
                    if ($unasssigned_task_id) {
                        $existing_id = \DB::table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', session('instance')->id)->pluck('id')->first();
                        $update_data = [
                            'is_deleted' => 0,
                            'progress_status' => 'Not Done',
                            'assigned_user_id' => $system_user->id,
                            'parent_id' => 0,
                            'role_id' => $system_user->role_id,
                        ];

                        \DB::table('crm_staff_tasks')->where('id', $unasssigned_task_id)->update($update_data);
                    } else {
                        $data = [];
                        $module_name = \DB::connection($conn)->table('erp_cruds')->where('id', $layout->module_id)->pluck('name')->first();

                        if ($instance_id == 11) {
                            $instance_prefix = 'MM';
                        }
                        if ($instance_id == 2) {
                            $instance_prefix = 'EO';
                        }
                        if ($instance_id == 1) {
                            $instance_prefix = 'TC';
                        }

                        $data['name'] = $instance_prefix.' - '.$module_name.' - '.$layout->name.' - '.$system_user->full_name;
                        $data['layout_id'] = $layout->id;
                        $data['module_id'] = $layout->module_id;
                        $data['assigned_user_id'] = $system_user->id;
                        $data['role_id'] = $system_user->role_id;

                        $data['instance_id'] = $instance_id;
                        $data['type'] = 'Layout';

                        $existing_id = \DB::table('crm_staff_tasks')->insertGetId($data);
                    }
                } else {
                    $module_name = \DB::connection($conn)->table('erp_cruds')->where('id', $layout->module_id)->pluck('name')->first();

                    if ($instance_id == 11) {
                        $instance_prefix = 'MM';
                    }
                    if ($instance_id == 2) {
                        $instance_prefix = 'EO';
                    }
                    if ($instance_id == 1) {
                        $instance_prefix = 'TC';
                    }

                    $name = $instance_prefix.' - '.$module_name.' - '.$layout->name.' - '.$system_user->full_name;
                    \DB::table('crm_staff_tasks')
                        ->where('layout_id', $layout_id)
                        ->where('instance_id', $instance_id)
                        ->where('assigned_user_id', $system_user->id)
                        ->update(['is_deleted' => 0, 'role_id' => $system_user->role_id, 'name' => $name]);
                }
            }
        }
    }
}

function workboard_layout_tracking_enable($layout_id)
{

    $layout = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();
    if (! empty($layout->chart_model)) {

        $role = get_workspace_role_from_module_id($layout->module_id);
        \DB::table('erp_grid_views')->where('id', $layout_id)->update(['track_layout' => 1, 'chart_role_id' => $role->id]);
    } else {
        \DB::table('erp_grid_views')->where('id', $layout_id)->update(['track_layout' => 1]);
        $role = get_workspace_role_from_module_id($layout->module_id);
        if ($role && $role->id) {
            $role_id = $role->id;
        } else {
            $role_id = 1;
        }
        $exists = \DB::table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', session('instance')->id)->count();
        if ($exists) {
            $existing_id = \DB::table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', session('instance')->id)->pluck('id')->first();
            $update_data = ['is_deleted' => 0, 'progress_status' => 'Not Done', 'parent_id' => 0];
            $update_data['role_id'] = $role_id;
            \DB::table('crm_staff_tasks')->where('id', $existing_id)->update($update_data);
        } else {
            $data = [];
            $module_name = \DB::table('erp_cruds')->where('id', $layout->module_id)->pluck('name')->first();

            if (session('instance')->directory == 'moviemagic') {
                $instance_prefix = 'MM';
            }
            if (session('instance')->directory == 'eldooffice') {
                $instance_prefix = 'EO';
            }
            if (session('instance')->directory == 'telecloud') {
                $instance_prefix = 'TC';
            }

            $data['name'] = $instance_prefix.' - '.$module_name.' - '.$layout->name;
            $data['layout_id'] = $layout->id;
            $data['module_id'] = $layout->module_id;
            $data['role_id'] = $role_id;
            $data['instance_id'] = session('instance')->id;
            $data['type'] = 'Layout';
            $existing_id = \DB::table('crm_staff_tasks')->insertGetId($data);
        }

        set_workboard_permissions();
        import_processes_to_main();

        \DB::table('erp_grid_views')->where('id', $layout_id)->update(['track_layout' => 1]);
        update_workboard_layout_tracking_result($layout_id, session('instance')->id);
    }
    workboard_layout_set_tracking_per_user(session('instance')->id);
}

function aftersave_workboard_check_limit_instances($request)
{
    if (is_main_instance()) {
        $beforesave_row = session('event_db_record');

        if ($beforesave_row->limit_instance_id != $request->limit_instance_id) {

            $row = \DB::connection($conn)->table('crm_staff_tasks')->where('id', $request->id)->get()->first();
            if ($row->type != 'Task' && $row->layout_id > 0) {
                $beforesave_arr = explode(',', $beforesave_row->limit_instance_id);
                $afteresave_arr = [];
                if (! empty($request->limit_instance_id) && ! is_array($request->limit_instance_id)) {
                    $afteresave_arr = explode(',', $request->limit_instance_id);
                }
                if (! empty($request->limit_instance_id) && is_array($request->limit_instance_id)) {
                    $afteresave_arr = $request->limit_instance_id;
                }
                //aa($request->limit_instance_id);
                //aa($beforesave_arr);
                //aa($afteresave_arr);
                if (count($beforesave_arr) != count($afteresave_arr)) {
                    foreach ($beforesave_arr as $instance_id) {
                        if (! in_array($instance_id, $afteresave_arr) && $instance_id != session('instance')->id) {
                            \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $row->layout_id)->where('instance_id', $instance_id)->update(['is_deleted' => 1]);
                        }
                    }

                    foreach ($afteresave_arr as $instance_id) {
                        if (! in_array($instance_id, $beforesave_arr)) {
                            if ($instance_id != session('instance')->id) {
                                import_processes_to_main($instance_id);
                            }
                        }
                    }
                }

            }
        }
    }
}

function validate_external_processes($instance_id)
{
    if ($instance_id == 1) {
        return false;
    }
    $conn = \DB::connection('system')->table('erp_instances')->where('id', $instance_id)->pluck('db_connection')->first();
    $st = \DB::connection($conn)->table('crm_staff_tasks')->where('layout_id', '>', 0)->where('is_deleted', 0)->get();
    foreach ($st as $t) {
        $r = \DB::connection($conn)->table('erp_grid_views')->where('id', $t->layout_id)->where('is_deleted', 0)->where('track_layout', 1)->count();
        if (! $r) {
            \DB::connection($conn)->table('crm_staff_tasks')->where('id', $t->id)->delete();
        }
    }

    $system_tasks = \DB::connection('system')->table('crm_staff_tasks')->where('is_deleted', 0)->where('layout_id', '>', 0)->get();
    $system_layouts = \DB::connection('system')->table('erp_grid_views')->where('is_deleted', 0)->where('track_layout', 1)->get();

    foreach ($system_layouts as $system_layout) {
        \DB::connection($conn)->table('erp_grid_views')->where('main_instance_id', $system_layout->id)->update(['track_layout' => 1]);
    }

    $layouts = \DB::connection($conn)->table('erp_grid_views')->where('is_deleted', 0)->where('track_layout', 1)->get();
    foreach ($layouts as $layout) {
        $system_task = false;
        $layout_id = $layout->id;

        $role_id = 1;
        if (! empty($layout->main_instance_id)) {
            $system_task = $system_tasks->where('module_id', $layout->module_id)->where('layout_id', $layout->id)->where('instance_id', 1)->first();
            if (! empty($system_task)) {

                if (! empty($system_task->limit_instance_id)) {
                    $limit_instance_ids = explode(',', $system_task->limit_instance_id);
                    if (! in_array($instance_id, $limit_instance_ids)) {
                        \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->update(['is_deleted' => 1]);
                        \DB::connection($conn)->table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->update(['is_deleted' => 1]);

                        continue;
                    }
                }
                if (! empty($system_task->role_id)) {
                    $role_id = $system_task->role_id;
                }
            }
        }

        $exists = \DB::connection($conn)->table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->count();
        if (! $exists) {
            $data = [];
            $module_name = \DB::connection($conn)->table('erp_cruds')->where('id', $layout->module_id)->pluck('name')->first();
            $data['name'] = $module_name.' - '.$layout->name;

            $data['role_id'] = $role_id;
            $data['layout_id'] = $layout->id;
            $data['module_id'] = $layout->module_id;
            $data['instance_id'] = $instance_id;
            $data['type'] = 'Layout';
            $data['progress_status'] = 'Not Done';

            $existing_id = \DB::connection($conn)->table('crm_staff_tasks')->insertGetId($data);
        } else {
            //dd($layout,$system_task,$system_tasks,$role_id);
            \DB::connection($conn)->table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', $instance_id)->update(['role_id' => $role_id, 'is_deleted' => 0]);
        }

    }

}

function import_processes_to_main($instance_id = false)
{

    if (! $instance_id) {
        $instance_id = session('instance')->id;
    }
    if ($instance_id > 1) {
        $conn = \DB::connection('system')->table('erp_instances')->where('id', $instance_id)->pluck('db_connection')->first();

        // add tracking
        validate_external_processes($instance_id);

        $conn_users = \DB::connection($conn)->table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->get();
        $system_users = \DB::connection('system')->table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->get();
        $instance_user_ids = \DB::connection('system')->table('erp_instance_user_access')->where('instance_id', $instance_id)->pluck('user_id')->toArray();
        $instance_role_ids = \DB::connection('system')->table('erp_users')->whereIn('id', $instance_user_ids)->pluck('role_id')->toArray();

        $ts = \DB::connection($conn)->table('crm_staff_tasks')->whereIn('type', ['Layout'])->where('is_deleted', 0)->get();
        foreach ($ts as $t) {
            $username = $conn_users->where('id', $t->user_id)->pluck('username')->first();

            $system_user_id = $system_users->where('username', $username)->plucK('id')->first();

            if (! $system_user_id) {
                $system_user_id = $system_users->where('role_id', 1)->plucK('id')->first();
            }

            $data = (array) $t;
            unset($data['id']);
            $data['instance_id'] = $instance_id;

            $data['user_id'] = $system_user_id;

            // get role and user from main instance
            $main_instance_id = \DB::connection($conn)->table('erp_grid_views')->where('id', $t->layout_id)->pluck('main_instance_id')->first();
            if ($main_instance_id) {
                $main_instance_task = \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $main_instance_id)->where('instance_id', 1)->get()->first();

                if (! $main_instance_task) {
                    $custom = \DB::connection($conn)->table('erp_grid_views')->where('id', $t->layout_id)->pluck('custom')->first();
                    $module_custom = \DB::connection($conn)->table('erp_cruds')->where('id', $t->module_id)->pluck('custom')->first();

                    if (! $custom && ! $module_custom) {
                        \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $data['layout_id'])->where('instance_id', $instance_id)->delete();

                        continue;
                    }
                }
                if ($main_instance_task && $main_instance_task->user_id) {
                    $data['user_id'] = $main_instance_task->user_id;
                }
                if ($main_instance_task && $main_instance_task->role_id) {
                    $data['role_id'] = $main_instance_task->role_id;
                }
                if ($main_instance_task && $main_instance_task->sort_order) {
                    $data['sort_order'] = $main_instance_task->sort_order;
                }
                if ($main_instance_task && $main_instance_task->parent_id) {
                    $data['parent_id'] = $main_instance_task->parent_id;
                }
            }

            if (! in_array($data['role_id'], $instance_role_ids)) {

                foreach ($instance_role_ids as $instance_role_id) {
                    if (! in_array($instance_role_id, [1, 58])) {
                        $data['role_id'] = $instance_role_id;
                        break;
                    }
                }
            }

            $exists = \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $data['layout_id'])->where('instance_id', $instance_id)->count();

            if (! $exists) {

                try {

                    \DB::connection('system')->table('crm_staff_tasks')->insert($data);
                } catch (\Throwable $ex) {
                }
            } else {

                // get role and user from main instance
                $update_data = [
                    'is_deleted' => 0,
                ];
                // get role and user from main instance
                $main_instance_id = \DB::connection($conn)->table('erp_grid_views')->where('id', $t->layout_id)->pluck('main_instance_id')->first();

                if ($main_instance_id) {
                    $main_instance_task = \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $main_instance_id)->where('instance_id', 1)->get()->first();

                    if ($main_instance_task && $main_instance_task->user_id) {
                        $update_data['user_id'] = $main_instance_task->user_id;
                    }
                    if ($main_instance_task && $main_instance_task->role_id) {
                        $update_data['role_id'] = $main_instance_task->role_id;
                    }
                    if ($main_instance_task && $main_instance_task->sort_order) {
                        $update_data['sort_order'] = $main_instance_task->sort_order;
                    }
                    if ($main_instance_task && $main_instance_task->parent_id) {
                        $update_data['parent_id'] = $main_instance_task->parent_id;
                    }
                }

                if (! in_array($update_data['role_id'], $instance_role_ids)) {

                    foreach ($instance_role_ids as $instance_role_id) {
                        if (! in_array($instance_role_id, [1, 58])) {
                            $update_data['role_id'] = $instance_role_id;
                            break;
                        }
                    }

                }

                \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $data['layout_id'])->where('instance_id', $data['instance_id'])->update($update_data);
            }
        }

        $ts_layout_ids = collect($ts)->pluck('layout_id')->toArray();
        \DB::connection('system')->table('crm_staff_tasks')
            ->whereNotIn('layout_id', $ts_layout_ids)
            ->where('instance_id', $instance_id)
            ->delete();

    }

}

function workboard_layout_tracking_disable($layout_id)
{

    \DB::table('erp_grid_views')->where('id', $layout_id)->update(['track_layout' => 0]);
    \DB::table('crm_staff_tasks')->where('layout_id', $layout_id)->where('instance_id', session('instance')->id)->update(['is_deleted' => 1]);
    $task = \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout_id)->get()->first();
    if ($task->progress_status == 'In Progress') {
        $date = \Carbon\Carbon::parse($task->start_time);

        $now = \Carbon\Carbon::now();
        $stop_time = $now->toDateTimeString();
        $duration = $date->diffInMinutes($now);

        \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
        \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);

        $module_id = 1898;
        staff_log_time('task', $task->start_time, $duration, $task->id, session('instance')->id, $module_id);
    }

}

function get_workboard_layout_progress_status($layout_id, $instance_id = 1, $assigned_user_id = false)
{

    $instance_conn = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $instance_id)->pluck('db_connection')->first();
    $layout = \DB::connection($instance_conn)->table('erp_grid_views')
        ->where('id', $layout_id)
        ->get()->first();
    if (empty($layout)) {

        $progress_status = 'Not Done';

        return $progress_status;
    }

    try {
        $result = workboard_layout_row_count($layout->id, $instance_id, $assigned_user_id);

        set_db_connection('default');
        if ($result === 'error') {
            return false;
        }
        $result = intval($result);
        // progress_status based on target
        if ($layout->target_success == 'greater') {
            if ($result > $layout->target) {
                $progress_status = 'Done';
            } else {
                $progress_status = 'Not Done';
            }
        } elseif ($layout->target_success == 'less') {
            if ($result < $layout->target) {
                $progress_status = 'Done';
            } else {
                $progress_status = 'Not Done';
            }
        } else {
            if ($result == $layout->target) {
                $progress_status = 'Done';
            } else {
                $progress_status = 'Not Done';
            }
        }
        // aa($progress_status);

    } catch (\Throwable $ex) {
        exception_log($ex);
    }

    return $progress_status;
}

function update_workboard_layout_tracking_result($layout_id, $instance_id = 1, $assigned_user_id = false)
{
    $instance_conn = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $instance_id)->pluck('db_connection')->first();
    $layout = \DB::connection($instance_conn)->table('erp_grid_views')
        ->where('id', $layout_id)
        ->get()->first();
    $current_task = \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->get()->first();

    if (empty($layout)) {
        return false;
    }

    try {
        $result = workboard_layout_row_count($layout->id, $instance_id, $assigned_user_id);
        set_db_connection('default');
        // aa($result);
        if ($result === 'error') {
            return false;
        }
        $result = intval($result);
        // progress_status based on target
        $reset_duration = false;

        // aa($current_task);
        if ($current_task->target_success == 'greater') {
            if ($result < $current_task->result) {
                $reset_duration = true;
            }
            if ($result > $current_task->target) {
                $progress_status = 'Done';
            } else {
                $progress_status = 'Not Done';
            }
        } elseif ($current_task->target_success == 'less') {
            if ($result > $layout->result) {
                $reset_duration = true;
            }

            if ($result < $current_task->target) {
                $progress_status = 'Done';
            } else {
                $progress_status = 'Not Done';
            }
        } else {
            if ($result > $current_task->result) {
                $reset_duration = true;
            }

            if ($result == $current_task->target) {
                $progress_status = 'Done';
            } else {
                $progress_status = 'Not Done';
            }
        }

        if ($reset_duration && $current_task->result_before_start == $result) {
            $reset_duration = false;
        }

        if (! empty(session('user_id'))) {
            $log = [
                'layout_id' => $layout->id,
                'result' => $result,
                'status' => $progress_status,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => get_user_id_default(),
            ];

            dbinsert('crm_layout_log', $log);
        }

        $current_task = \DB::connection('default')->table('crm_staff_tasks')->select('id', 'progress_status', 'result', 'layout_type', 'report_update_frequency', 'report_last_update')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->get()->first();
        $current_progress_status = $current_task->progress_status;

        $update_status = true;

        // prevent update for report processes
        if ($current_progress_status == 'Done' && $current_task->layout_type == 'Report') {
            if (! empty($current_task->report_last_update)) {
                if ($current_task->report_update_frequency == 'Daily') {
                    if (date('Y-m-d H:i') < date('Y-m-d H:i', strtotime($current_task->report_last_update.'+1 day'))) {
                        $update_status = false;
                    }
                }
                if ($current_task->report_update_frequency == 'Weekly') {
                    if (date('Y-m-d H:i') < date('Y-m-d H:i', strtotime($current_task->report_last_update.'+1 week'))) {
                        $update_status = false;
                    }
                }
                if ($current_task->report_update_frequency == 'Monthly') {
                    if (date('Y-m-d H:i') < date('Y-m-d H:i', strtotime($current_task->report_last_update.'+1 month'))) {
                        $update_status = false;
                    }
                }
            }
        }

        if ($update_status) {
            $current_result = $current_task->result;

            //if($current_progress_status != 'In Progress'){
            \DB::connection('default')->table('crm_staff_tasks')->where('result', '!=', trim($result))->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->update(['result' => trim($result)]);
            //}

            if ($progress_status == 'Not Done') {

                \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->whereNotIn('progress_status', ['In Progress', 'Postponed'])->update(['progress_status' => $progress_status]);
                \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->update(['result' => trim($result)]);

                \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->where('result', '!=', trim($result))->whereNotIn('progress_status', ['In Progress', 'Postponed'])->update(['duration' => 0]);

            }
            if ($progress_status == 'Done') {
                $inprogress = \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->where('progress_status', 'In Progress')->count();
                if ($inprogress) {

                    $task = \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->where('progress_status', 'In Progress')->get()->first();
                    if ($task->start_time && $task->progress_status == 'In Progress') {
                        $date = \Carbon\Carbon::parse($task->start_time);

                        $now = \Carbon\Carbon::now();
                        $stop_time = $now->toDateTimeString();
                        $duration = $date->diffInMinutes($now);

                        \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Done', 'duration' => 0]);
                        \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);

                        $module_id = 1898;
                        staff_log_time('task', $task->start_time, $duration, $task->id, session('instance')->id, $module_id);
                    }
                }

                \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->update(['result' => trim($result), 'progress_status' => $progress_status]);
            } elseif ($reset_duration && $current_progress_status == 'In Progress') {
                $task = \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->whereIn('type', ['Layout', 'Report'])->where('progress_status', 'In Progress')->get()->first();
                if ($task->start_time && $task->progress_status == 'In Progress') {
                    $date = \Carbon\Carbon::parse($task->start_time);

                    $now = \Carbon\Carbon::now();
                    $stop_time = $now->toDateTimeString();
                    $duration = $date->diffInMinutes($now);

                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Done', 'duration' => 0]);
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);

                    $module_id = 1898;
                    staff_log_time('task', $task->start_time, $duration, $task->id, session('instance')->id, $module_id);

                    // reset and put task In Progress
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['start_time' => date('Y-m-d H:i:s'), 'progress_status' => 'In Progress', 'duration' => 0]);

                }
            }
            if ($reset_duration && $current_progress_status != 'In Progress') {
                \DB::table('crm_staff_tasks')->where('id', $current_task->id)->update(['duration' => 0]);
            }

            if ($current_task->layout_type == 'Report') {
                \DB::connection('default')->table('crm_staff_tasks')->where('id', $current_task->id)->update(['report_last_update' => date('Y-m-d H:i:s')]);
            }
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
    }

    if (date('Y-m-d') < date('Y-m-25') && $instance_id == 1 && $layout_id == 1152) {
        \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout->id)->where('instance_id', $instance_id)->update(['progress_status' => 'Done', 'result' => 0]);
    }

}

function workboard_layout_row_count($layout_id, $instance_id = 1, $assigned_user_id = false, $return_sql = false)
{

    try {
        session(['pbx_account_id' => 1]);

        $conn = 'default';

        $current_conn = \DB::getDefaultConnection();
        if ($instance_id != 1) {
            $conn = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $instance_id)->pluck('db_connection')->first();

            set_db_connection($conn);
        }

        $layout = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();

        session(['show_deleted'.$layout->module_id => 0]);
        $result_field = $layout->result_field;
        if (! $layout) {
            return 0;
        }
        $module = \DB::table('erp_cruds')->where('id', $layout->module_id)->get()->first();
        $model = new \App\Models\ErpModel;

        if ($instance_id != 1) {
            $conn = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $instance_id)->pluck('db_connection')->first();
            set_db_connection($conn);
            $model = new \App\Models\ErpModel(false, $conn);
        } else {
            $model = new \App\Models\ErpModel;
        }
        $model->setModelData($layout->module_id, $conn);
        if ($instance_id != 1) {
            if ($module->connection == 'default') {
                $module->connection = $conn;
            }

            set_db_connection($conn);
        }
        $layout_state = json_decode($layout->aggrid_state);
        if (empty($layout_state->filterState)) {
            $filter_state = [];
        } else {
            $filter_state = (array) json_decode(json_encode($layout_state->filterState), true);
        }

        $model->setWorkboardQuery(true);
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');
        $request_object->request->add(['layout_tracking' => 1]);
        $request_object->request->add(['rowGroupCols' => []]);
        $request_object->request->add(['valueCols' => []]);
        $request_object->request->add(['groupKeys' => []]);

        // add grouping
        $rowGroupCols = [];
        $group_cols = collect($layout_state->colState)->where('rowGroup', 'true')->sortBy('rowGroupIndex');
        foreach ($group_cols as $group_col) {

            $rowGroupCols[] = [
                'id' => $group_col->colId,
                'aggFunc' => 'max',
                'displayName' => $group_col->colId,
                'field' => $group_col->colId,
            ];
        }
        if (count($rowGroupCols) > 0) {
            $request_object->request->add(['rowGroupCols' => $rowGroupCols]);
            $valueCols = [[
                'id' => $module->db_key,
                'aggFunc' => 'count',
                'displayName' => $module->db_key,
                'field' => $module->db_key,
            ]];
            $request_object->request->add(['valueCols' => $valueCols]);
        }

        if ($filter_state) {
            foreach ($filter_state as $col => $state) {
                if ($state['filterType'] == 'set') {
                    $field_display = \DB::connection('default')->table('erp_module_fields')->where('field', $col)->where('module_id', $layout->module_id)->pluck('opt_db_display')->first();
                    $field_display_count = 0;
                    if (! empty($field_display)) {
                        $field_display_arr = explode(',', $field_display);
                        $field_display_count = count($field_display_arr);
                    }
                    foreach ($state['values'] as $i => $val) {
                        if ($field_display_count > 1) {
                            $val_arr = explode(' - ', $val);
                            $filter_state[$col]['values'][$i] = $val_arr[0];
                        }
                    }
                }
            }
        }

        if ($module->layout_tracking_per_user && $assigned_user_id) {
            // add assigned user filter
            $conn = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', $instance_id)->pluck('db_connection')->first();
            $has_is_deleted_field = \DB::connection($conn)->table('erp_module_fields')->where('module_id', $layout->module_id)->where('field', 'is_deleted')->count();

            $has_salesman_field = \DB::connection($conn)->table('erp_module_fields')->where('module_id', $layout->module_id)->where('field', 'salesman_id')->count();
            if ($has_salesman_field) {
                $user_field_name = 'salesman_id';
            }
            $has_users_field = \DB::connection($conn)->table('erp_module_fields')->where('module_id', $layout->module_id)->where('field', 'user_id')->count();
            if ($has_users_field) {
                $user_field_name = 'user_id';
            }

            if ($user_field_name) {
                if (! $filter_state) {
                    $filter_state = [];
                }
                $username = \DB::connection('system')->table('erp_users')->where('id', $assigned_user_id)->pluck('full_name')->first();
                $filter_state['join_'.$user_field_name] = [
                    'values' => [$username],
                    'filterType' => 'set',
                ];

            }
        }

        if (! empty($filter_state) && is_array($filter_state) && count($filter_state) > 0) {
            $request_object->request->add(['filterModel' => $filter_state]);
        } else {
            $request_object->request->add(['filterModel' => []]);
        }

        if (! empty($layout_state->searchtext) && $layout_state->searchtext != ' ') {
            $request_object->request->add(['search' => $layout_state->searchtext]);
        }

        if ($return_sql) {
            $request_object->request->add(['return_sql' => 1]);
        }

        if ($result_field) {
            if ($layout->layout_type == 'Report') {
                $valueCols = [];
                $pivot_state = json_decode($layout->aggrid_pivot_state);

                foreach ($pivot_state->colState as $colState) {

                    if ($result_field == $colState->colId) {

                        $request_object->request->add(['result_field_agg_func' => $colState->aggFunc]);
                        $valueCols = [[
                            'id' => $colState->colId,
                            'aggFunc' => $colState->aggFunc,
                            'displayName' => $colState->colId,
                            'field' => $colState->colId,
                        ]];
                    }
                }
                $request_object->request->add(['valueCols' => $valueCols]);
            }

            $request_object->request->add(['result_field' => $result_field]);
            $request_object->request->add(['rowTotals' => 1]);
            $totals_result = $model->getRowTotals($request_object);

            $result = collect($totals_result)->pluck($result_field)->first();
            //if(is_dev())
            //dd($totals_result,$result,$filter_state);

        } else {

            $count_SQL = $model->buildSql($request_object, 'count');
            // if(is_dev())

            if (str_contains($count_SQL, $module->db_table.'.*,') && str_contains($count_SQL, $module->db_table.'.'.$module->db_key.',')) {
                $count_SQL = str_replace($module->db_table.'.'.$module->db_key.',', '', $count_SQL);
            }

            $count_SQL = str_replace($module->db_table.'.*,', $module->db_table.'.'.$module->db_key.',', $count_SQL);
            $count_SQL = str_replace('*,', '', $count_SQL);
            //if(is_dev())
            // aa($count_SQL);
            // $count_SQL = str_replace(".is_deleted in ('0', '1')",".is_deleted = 0",$count_SQL);
            if ($return_sql) {
                return $count_SQL;
            }

            $row_count_result = \DB::connection($module->connection)->select($count_SQL);
            $result = collect($row_count_result)->pluck('lastrow')->first();
        }
        //   if($layout_id == 3376){
        // aa($count_SQL,true);
        //aa($row_count_result,true);
        //aa($result,true);
        //   }
        // if(is_dev())
        //if(session('instance')->id == 2 ){
        // }

        //  if(is_dev()){
        //     }
        set_db_connection('default');

        return $result;
    } catch (\Throwable $ex) {

        set_db_connection('default');

        return 'error';
    }
}

/*
function workboard_populate_bottlenecks($process_id = false){

    if(!$process_id){

    \DB::table('crm_staff_tasks')->where('layout_id','>',0)->update(['details'=>'']);
    $processes = \DB::table('crm_staff_tasks')->where('progress_status','!=','Done')->where('layout_id','>',0)->where('is_deleted',0)->get();
    }else{
    $processes = \DB::table('crm_staff_tasks')->where('progress_status','!=','Done')->where('id',$process_id)->where('layout_id','>',0)->where('is_deleted',0)->get();
    }

    foreach($processes as $process){
        $conn = 'default';
        $current_conn = \DB::getDefaultConnection();
        if($process->instance_id != 1){
            $conn = \DB::connection('system')->table('erp_instances')->where('installed',1)->where('id',$process->instance_id)->pluck('db_connection')->first();

            set_db_connection($conn);
        }

        try{

         if($instance_id != 1){
            $erp_conn = \DB::connection('system')->table('erp_instances')->where('installed',1)->where('id',$instance_id)->pluck('db_connection')->first();
            $layout = \DB::connection($erp_conn)->table('erp_grid_views')->where('main_instance_id',$process->layout_id)->get()->first();
            if(!$layout){
                $layout = \DB::connection($erp_conn)->table('erp_grid_views')->where('id',$process->layout_id)->get()->first();
            }
        }else{
            $erp_conn = 'default';
            $layout = \DB::connection('default')->table('erp_grid_views')->where('id',$process->layout_id)->get()->first();
        }

        $layout_id = $layout->id;
        $instance_id = $process->instance_id;
        $result_field = $layout->result_field;
        if(!$layout){
            //aa('continue');
            //aa($process);
            continue;
        }
        $module = \DB::connection($erp_conn)->table('erp_cruds')->where('id',$layout->module_id)->get()->first();
        if($module->connection == 'default' && $instance_id != 1){
            $module->connection = $erp_conn;
        }


        if(!\Schema::connection($module->connection)->hasTable($module->db_table)){
            continue;
        }
        $display_field = \DB::connection($erp_conn)->table('erp_module_fields')->where('module_id',$layout->module_id)->where('display_field',1)->get()->first();
        if(empty($display_field)){
            $display_field = \DB::connection($erp_conn)->table('erp_module_fields')->where('module_id',$layout->module_id)->where('label','Name')->get()->first();
        }

        if(empty($display_field)){
            $display_field_name = 'id';
        }else{

            $display_field_name = $display_field->field;
            if($display_field->field_type == 'select_module'){
                $display_field_name = 'join_'.$display_field->field;
            }
        }



         if($instance_id != 1){
            $conn = \DB::connection('system')->table('erp_instances')->where('installed',1)->where('id',$instance_id)->pluck('db_connection')->first();

            set_db_connection($conn);
            $model = new \App\Models\ErpModel(false,$conn);
        }else{
            $model = new \App\Models\ErpModel;
        }
        $model->setModelData($layout->module_id,$conn);

        if($instance_id != 1){
            if($module->connection == 'default'){
            $module->connection = $conn;
            }

            set_db_connection($conn);
        }
        $layout_state = json_decode($layout->aggrid_state);
        if (empty($layout_state->filterState)) {
            $filter_state = [];
        }else {
            $filter_state = (array) json_decode(json_encode($layout_state->filterState),true);
        }


        $request_object = new \Illuminate\Http\Request();
        $request_object->setMethod('POST');
        $request_object->request->add(['return_all_rows' => 1]);
        $request_object->request->add(['layout_tracking' => 1]);
        $request_object->request->add(['rowGroupCols' => []]);
        $request_object->request->add(['valueCols' => []]);
        $request_object->request->add(['groupKeys' => []]);

        // add grouping
        $rowGroupCols = [];
        $group_cols = collect($layout_state->colState)->where('rowGroup','true')->sortBy('rowGroupIndex');
        foreach($group_cols as $group_col){

            $rowGroupCols[] = [
                          'id' => $group_col->colId,
                          'aggFunc' => 'max',
                          'displayName' => $group_col->colId,
                          'field' => $group_col->colId,
                        ];
        }
        if(count($rowGroupCols) > 0){
            $request_object->request->add(['rowGroupCols' => $rowGroupCols]);

                    $valueCols = [[
                      'id' => $module->db_key,
                      'aggFunc' => 'count',
                      'displayName' => $module->db_key,
                      'field' =>  $module->db_key,
                    ]];
            $request_object->request->add(['valueCols' => $valueCols]);

        }
        if ($filter_state) {

            foreach($filter_state as $col => $state){
                if($state['filterType'] == 'set'){
                    $field_display = \DB::connection('default')->table('erp_module_fields')->where('field',$col)->where('module_id',$layout->module_id)->pluck('opt_db_display')->first();
                    $field_display_count = 0;
                    if(!empty($field_display)){
                        $field_display_arr = explode(',',$field_display);
                        $field_display_count = count($field_display_arr);
                    }
                    foreach($state['values'] as $i => $val){

                        if($field_display_count > 0){
                            $val_arr = explode(" - ",$val);

                            $filter_state[$col]['values'][$i] = $val_arr[0];
                        }
                    }
                }
            }

            $request_object->request->add(['filterModel' => $filter_state]);
        }else{
            $request_object->request->add(['filterModel' => []]);
        }
        if(!empty($layout_state->searchtext) && $layout_state->searchtext!=" "){
            $request_object->request->add(['search' => $layout_state->searchtext]);
        }

        // $current_conn = \DB::getDefaultConnection();

        $data = $model->getData($request_object,$module->connection);

        if(count($rowGroupCols) > 0){
               $display_field_name = collect($layout_state->colState)->where('rowGroup','true')->sortBy('rowGroupIndex')->pluck('colId')->first();
        }

       // if(is_dev()){
        //}

       // aa($display_field);
       // aa($display_field_name);
       // aa($data);

        $bottlenecks = '';
        if(!empty($data['rows'])){
            $rows = collect($data['rows'])->take(100)->toArray();
            foreach($rows as $result){
                $line_set = false;
                if($display_field_name == 'id'){
                    // add company names if display field is set to id
                     $has_supplier_id_field = \DB::connection('default')->table('erp_module_fields')->where('module_id',$layout->module_id)->where('field','supplier_id')->count();
                     if($has_supplier_id_field){
                         $bottlenecks .= \DB::connection('default')->table('crm_suppliers')->where('id',$result->supplier_id)->pluck('company')->first();
                         $bottlenecks .= ' - ';
                     }

                     $has_account_id_field = \DB::connection('default')->table('erp_module_fields')->where('module_id',$layout->module_id)->where('field','account_id')->count();
                     if($has_account_id_field){
                         $bottlenecks .= \DB::connection('default')->table('crm_accounts')->where('id',$result->account_id)->pluck('company')->first();
                         $bottlenecks .= ' - ';
                     }

                     $has_status_field = \DB::connection('default')->table('erp_module_fields')->where('module_id',$layout->module_id)->where('field','status')->count();
                     if($has_status_field){
                         $bottlenecks .= \DB::connection('default')->table($module->db_table)->where('id',$result->id)->pluck('status')->first();
                         $bottlenecks .= PHP_EOL;
                         $line_set = true;
                     }
                }
                if(!$line_set)
                $bottlenecks .= $result->{$display_field_name}.PHP_EOL;
            }
        }
   //if(is_dev()){
     // }
        set_db_connection('default');


        \DB::table('crm_staff_tasks')->where('layout_id',$layout_id)->where('instance_id',$process->instance_id)->update(['details'=>$bottlenecks]);
        $task = \DB::table('crm_staff_tasks')->where('layout_id',$layout_id)->where('instance_id',$process->instance_id)->get()->first();

        if(empty($task->details)){
            \DB::table('crm_task_checklist')->where('task_id',$task->id)->delete();
        }
        $lines = collect(explode(PHP_EOL,$task->details))->filter()->toArray();
        foreach($lines as $i => $line){
            $lines[$i] = str_replace( ' - done', '', $line);
        }
        \DB::table('crm_task_checklist')->where('task_id',$task->id)->whereNotIn('name',$lines)->delete();
        foreach($lines as $i => $line){
            $line = str_replace( ' - done', '', $line);
            $where_data = ['task_id'=>$task->id,'name'=>$line];
            $data = ['task_id'=>$task->id,'name'=>$line,'sort_order'=>$i];
            \DB::table('crm_task_checklist')->updateOrInsert($where_data,$data);
        }


        }catch(\Throwable $ex){

            $current_conn = \DB::getDefaultConnection();
            aa('current conn');
            aa($current_conn);
            aa(session('instance'));
            aa($ex->getMessage());
            aa($ex->getTraceAsString());
        }
    }
}
*/

function schedule_enable_recurring_projects()
{
    \DB::table('crm_staff_tasks')
        ->where('recurring_monthly', 1)
        ->where('type', 'Task')
        ->update(['progress_status' => 'Not Done', 'duration' => 0, 'is_deleted' => 0]);
}
