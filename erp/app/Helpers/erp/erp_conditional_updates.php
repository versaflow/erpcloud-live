<?php

function button_conditional_update_rules($request)
{

    if (empty($request->id)) {
        return json_alert('Id not set', 'warning');
    }
    $row = \DB::connection('default')->table('erp_conditional_updates')->where('id', $request->id)->get()->first();
    if (empty($row) || empty($row->id)) {
        return json_alert('Row not set', 'warning');
    }

    $filter_columns = query_builder_get_column_data($row->module_id);
    $data = [
        'id' => $request->id,
        'filter_columns' => $filter_columns,
        'update_rules' => $row->update_rules,
    ];

    return view('__app.button_views.conditional_update_rules', $data);
}

function button_conditional_update_check_affected_rows($request)
{
    $updates = \DB::connection('default')->table('erp_conditional_updates')->where('id', $request->id)->where('is_deleted', 0)->get();
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    $module_is_deleted_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'is_deleted')->get();
    $module_status_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'status')->get();
    $affected_rows = 0;
    $total_rows = 0;
    foreach ($updates as $update) {
        $module = $modules->where('id', $update->module_id)->first();
        $has_is_deleted = $module_is_deleted_fields->where('module_id', $update->module_id)->count();
        $has_status = $module_status_fields->where('module_id', $update->module_id)->count();
        if ($has_is_deleted) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->count();
        } elseif ($has_status) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->count();
        } else {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->count();
        }
    }
    \DB::connection('default')->table('erp_conditional_updates')->where('id', $request->id)->update(['affected_rows_checked' => 1]);

    return json_alert($affected_rows.' out of '.$total_rows.' rows will be updated');
}

function schedule_process_conditional_updates_daily()
{
    $updates = \DB::connection('default')->table('erp_conditional_updates')->where('sql_where', '>', '')->where('schedule', 'Daily')->where('is_deleted', 0)->get();
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    $module_is_deleted_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'is_deleted')->get();
    $module_status_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'status')->get();
    foreach ($updates as $update) {
        if (! $update->affected_rows_checked) {
            continue;
        }
        $module = $modules->where('id', $update->module_id)->first();
        $has_is_deleted = $module_is_deleted_fields->where('module_id', $update->module_id)->count();
        $has_status = $module_status_fields->where('module_id', $update->module_id)->count();
        if ($has_is_deleted) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->count();
            \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } elseif ($has_status) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->count();
            \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } else {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->count();
            \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        }
        $last_update_result = $affected_rows.' out of '.$total_rows.' rows updated';
        \DB::connection('default')->table('erp_conditional_updates')->where('id', $update->id)->update(['last_run' => date('Y-m-d H:i:s'), 'last_update_result' => $last_update_result]);
    }
}

function schedule_process_conditional_updates_hourly()
{
    $updates = \DB::connection('default')->table('erp_conditional_updates')->where('sql_where', '>', '')->where('schedule', 'Hourly')->where('is_deleted', 0)->get();
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    $module_is_deleted_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'is_deleted')->get();
    $module_status_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'status')->get();
    foreach ($updates as $update) {
        if (! $update->affected_rows_checked) {
            continue;
        }
        $module = $modules->where('id', $update->module_id)->first();
        $has_is_deleted = $module_is_deleted_fields->where('module_id', $update->module_id)->count();
        $has_status = $module_status_fields->where('module_id', $update->module_id)->count();
        if ($has_is_deleted) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->count();
            \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } elseif ($has_status) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->count();
            \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } else {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->count();
            \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        }
        $last_update_result = $affected_rows.' out of '.$total_rows.' rows updated';
        \DB::connection('default')->table('erp_conditional_updates')->where('id', $update->id)->update(['last_run' => date('Y-m-d H:i:s'), 'last_update_result' => $last_update_result]);
    }
}

function process_conditional_updates_aftersave($module_id)
{
    $updates = \DB::connection('default')->table('erp_conditional_updates')->where('sql_where', '>', '')->where('module_id', $module_id)->where('schedule', 'Aftersave')->where('is_deleted', 0)->get();
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    $module_is_deleted_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'is_deleted')->get();
    $module_status_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'status')->get();
    foreach ($updates as $update) {
        if (! $update->affected_rows_checked) {
            continue;
        }
        $module = $modules->where('id', $update->module_id)->first();
        $has_is_deleted = $module_is_deleted_fields->where('module_id', $update->module_id)->count();
        $has_status = $module_status_fields->where('module_id', $update->module_id)->count();
        if ($has_is_deleted) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->count();
            \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } elseif ($has_status) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->count();
            \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } else {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->count();
            \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        }
        $last_update_result = $affected_rows.' out of '.$total_rows.' rows updated';
        \DB::connection('default')->table('erp_conditional_updates')->where('id', $update->id)->update(['last_run' => date('Y-m-d H:i:s'), 'last_update_result' => $last_update_result]);
    }
}

function button_process_conditional_update($request)
{
    $updates = \DB::connection('default')->table('erp_conditional_updates')->where('sql_where', '>', '')->where('id', $request->id)->where('is_deleted', 0)->get();
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    $module_is_deleted_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'is_deleted')->get();
    $module_status_fields = \DB::connection('default')->table('erp_module_fields')->where('field', 'status')->get();
    // aa($updates);
    foreach ($updates as $update) {
        if (! $update->affected_rows_checked) {
            return json_alert('Affected rows not checked', 'warning');
        }
        $module = $modules->where('id', $update->module_id)->first();
        $has_is_deleted = $module_is_deleted_fields->where('module_id', $update->module_id)->count();
        $has_status = $module_status_fields->where('module_id', $update->module_id)->count();
        if ($has_is_deleted) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->count();
            \DB::connection($module->connection)->table($module->db_table)->where('is_deleted', 0)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } elseif ($has_status) {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->count();
            \DB::connection($module->connection)->table($module->db_table)->where('status', '!=', 'Deleted')->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        } else {
            $affected_rows = \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->count();
            $total_rows = \DB::connection($module->connection)->table($module->db_table)->count();
            \DB::connection($module->connection)->table($module->db_table)->whereRaw($update->sql_where)->update([$update->target_field => $update->update_value]);
        }
        $last_update_result = $affected_rows.' out of '.$total_rows.' rows updated';
        // aa($last_update_result);
        \DB::connection('default')->table('erp_conditional_updates')->where('id', $update->id)->update(['last_run' => date('Y-m-d H:i:s'), 'last_update_result' => $last_update_result]);
    }

    return json_alert('Done');
}

function get_condition_update_date_values()
{
    $date_values = [
        'currentDay',
        'currentWeek',
        'currentMonth',
        'currentYear',
        'lessEqualToday',
        'greaterEqualToday',
        'lessEqualToday',
        'previousDay',
        'previoulessEqualNextMonthsDay',
        'notCurrentMonth',
        'notlastThreeDays',
        'notlastSevenDays',
        'notlastThirtyFiveDays',
        'notlastThirtyDays',
        'notlastSixtyDays',
        'lastMonth',
        'lastThreeDays',
        'lastMonth',
        'lastThreeMonths',
        'lastSixMonths',
        'lastTwelveMonths',
    ];

    return $date_values;
}

function get_condition_update_date_sql($key, $value)
{

    $curdate_fn = 'CURDATE()';
    $previousday_fn = 'SUBDATE(CURDATE(),1)';
    $notCurrentMonthDate = date('Y-m-d', strtotime('last day of last month'));
    switch ($value) {

        case 'notCurrentMonth':
            return ' (DATE('.$key.") <  '".$notCurrentMonthDate."') ";
            break;
        case 'currentMonth':
            return ' ( YEAR('.$key.') = YEAR(NOW()) AND MONTH('.$key.') = MONTH(NOW()))';
            break;
        case 'currentYear':
            return ' (YEAR('.$key.') = YEAR(NOW())) ';
            break;
        case 'lastMonth':
            return ' (DATE('.$key.") >= DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y/%m/01' ) AND DATE(".$key.") < DATE_FORMAT( CURRENT_DATE, '%Y/%m/01' )) ";
            break;
        case 'currentWeek':
            return ' YEARWEEK('.$key.') = YEARWEEK(NOW())';
            break;
        case 'currentDay':
            return ' DATE('.$key.') = '.$curdate_fn.' ';
            break;
        case 'previousDay':
            return ' DATE('.$key.') = '.$previousday_fn.' ';
            break;
        case 'lastThreeDays':
            return $key.' >= ( '.$curdate_fn.' - INTERVAL 3 DAY) ';
            break;
        case 'lessEqualToday':
            return $key.' <= ( '.$curdate_fn.') ';
            break;
        case 'lessEqualNextMonth':
            return $key." <= '".date('Y-m-d', strtotime('last day of next month'))."' ";
            break;
        case 'greaterEqualToday':
            return $key.' >= ( '.$curdate_fn.') ';
            break;
        case 'notlastThreeDays':
            return '(('.$key.' is NULL or '.$key.' ="") or '.$key.' < ( '.$curdate_fn.' - INTERVAL 3 DAY)) ';
            break;
        case 'notlastSevenDays':
            return '(('.$key.' is NULL or '.$key.' ="") or '.$key.' < ( '.$curdate_fn.' - INTERVAL 7 DAY)) ';
            break;
        case 'notlastThirtyDays':
            return '(('.$key.' is NULL or '.$key.' ="") or '.$key.' < ( '.$curdate_fn.' - INTERVAL 30 DAY)) ';
            break;
        case 'notlastThirtyFiveDays':
            return '(('.$key.' is NULL or '.$key.' ="") or '.$key.' < ( '.$curdate_fn.' - INTERVAL 35 DAY)) ';
            break;
        case 'notlastSixtyDays':
            return '(('.$key.' is NULL or '.$key.' ="") or '.$key.' < ( '.$curdate_fn.' - INTERVAL 60 DAY)) ';
            break;
        case 'lastThreeMonths':
            return ' '.$key.' >= ( '.$curdate_fn.' - INTERVAL 3 MONTH) ';
            break;
        case 'lastSixMonths':
            return ' '.$key.' >= ( '.$curdate_fn.' - INTERVAL 6 MONTH) ';
            break;
        case 'lastTwelveMonths':
            return ' '.$key.' >= ( '.$curdate_fn.' - INTERVAL 12 MONTH) ';
            break;
        default:
            //logger('unknown text filter type: ' . $item['dateFrom']);
            return '';
    }
}
