<?php

function aftersave_layouts_set_dashboard_sort()
{

    $layout = \DB::table('erp_grid_views')->where('id', $request->id)->get()->first();
    $beforesave_row = session('event_db_record');
    if (! empty($request->new_record) && $request->show_on_dashboard) {
        $dashboard_sort_order = \DB::connection('default')->table('erp_grid_views')->max('dashboard_sort_order');
        $dashboard_sort_order++;
        \DB::connection('default')->table('erp_grid_views')->where('id', $request->id)->update(['dashboard_sort_order' => $dashboard_sort_order]);
    } elseif (empty($request->new_record) && $beforesave_row->show_on_dashboard != $request->show_on_dashboard) {
        if ($request->show_on_dashboard) {
            $dashboard_sort_order = \DB::connection('default')->table('erp_grid_views')->max('dashboard_sort_order');
            $dashboard_sort_order++;
            \DB::connection('default')->table('erp_grid_views')->where('id', $request->id)->update(['dashboard_sort_order' => $dashboard_sort_order]);
        } else {
            \DB::connection('default')->table('erp_grid_views')->where('id', $request->id)->update(['dashboard_sort_order' => 0]);
        }
    }
}

function button_workflow_view_record($request)
{

    $row = \DB::table('crm_workflow_tracking')->select('row_id', 'module_id')->where('id', $request->id)->get()->first();
    $url = get_menu_url_from_module_id($row->module_id);

    return redirect()->to($url.'?id='.$row->row_id);
}

function get_workflow_sql($layout)
{
    $current_conn = \DB::getDefaultConnection();

    $model = new \App\Models\ErpModel;
    $model->setModelData($layout->module_id);
    $layout_state = json_decode($layout->aggrid_state);

    $filter_state = (array) json_decode(json_encode($layout_state->filterState), true);

    $request_object = new \Illuminate\Http\Request;
    $request_object->setMethod('POST');
    $request_object->request->add(['workflow_tracking_sql' => 1]);
    $request_object->request->add(['workflow_tracking_layout_id' => $layout->id]);
    $request_object->request->add(['rowGroupCols' => []]);
    $request_object->request->add(['valueCols' => []]);
    $request_object->request->add(['groupKeys' => []]);
    if (! empty($layout_state->filterState)) {
        foreach ($filter_state as $col => $state) {
            if ($state['filterType'] == 'set') {
                foreach ($state['values'] as $i => $val) {

                    if (str_contains($val, ' - ')) {
                        $val_arr = explode(' - ', $val);

                        $filter_state[$col]['values'][$i] = $val_arr[0];
                    }
                }
            }
        }

        $request_object->request->add(['filterModel' => $filter_state]);
    }

    $sql = $model->buildSql($request_object, 'data');
    set_db_connection($current_conn);

    return $sql;
}

function onload_workflow_tracking_timer_status($request)
{
    \DB::table('crm_workflow_tracking')->whereNull('timer_status')->orWhere('timer_status', '')->update(['timer_status' => 'Open']);
}

function button_workflow_tracking_time_start($request)
{
    $task = \DB::table('crm_workflow_tracking')->where('id', $request->id)->get()->first();

    if ($task->timer_status == 'In Progress') {
        return json_alert('Task timer_status already set to In Progress', 'warning');
    }

    $inprogress_count = timer_inprogress($task->user_id, true);
    //  if ($inprogress_count) {
    //      return json_alert('Only one task can be set to In Progress', 'warning');
    // }

    \DB::table('crm_workflow_tracking')->where('id', $request->id)->update(['start_time' => date('Y-m-d H:i'), 'timer_status' => 'In Progress']);

    return json_alert('Task started');
}

function button_workflow_tracking_incomplete($request)
{
    if (is_superadmin()) {
        $task = \DB::table('crm_workflow_tracking')->where('id', $request->id)->get()->first();
        \DB::table('crm_workflow_tracking')->where('id', $request->id)->update(['stop_time' => null, 'timer_status' => 'Incomplete']);

        return json_alert('Task opened');
    } else {
        return json_alert('Only admin can set it to incomplete');
    }
}

function button_workflow_tracking_time_complete($request)
{
    $task = \DB::table('crm_workflow_tracking')->where('id', $request->id)->get()->first();

    if ($task->timer_status == 'Resolved') {
        return json_alert('Task timer_status already set to Resolved', 'warning');
    }

    $date = \Carbon\Carbon::parse($task->start_time);

    $now = \Carbon\Carbon::now();
    $stop_time = $now->toDateTimeString();
    $duration = $date->diffInMinutes($now);

    \DB::table('crm_workflow_tracking')->where('id', $request->id)->update(['stop_time' => $stop_time, 'timer_status' => 'Resolved']);
    \DB::table('crm_workflow_tracking')->where('id', $request->id)->increment('duration', $duration);
    staff_log_time('workflow', $task->start_time, $duration, $task->id);

    $tasks = \DB::table('crm_workflow_tracking')->select('id')->where('timer_status', 'Resolved')->get();
    $open_tasks = \DB::table('crm_workflow_tracking')->select('id')->where('timer_status', '!=', 'Resolved')->get();

    return json_alert('Task completed');
}
