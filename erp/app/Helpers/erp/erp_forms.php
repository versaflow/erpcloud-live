<?php

function form_logic_query_builder_get_column_data($module_id)
{

    $date_values = get_condition_update_date_values();

    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    $table = $module->db_table;
    $module_conn = $module->connection;

    $integer_columns = get_columns_from_schema($table, 'integer', $module_conn);

    foreach ($integer_columns as $field) {
        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'number', 'validation' => (object) ['min' => '-9999999999']];
    }

    $boolean_columns = get_columns_from_schema($table, 'boolean', $module_conn);

    foreach ($boolean_columns as $field) {

        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'boolean', 'values' => ['0', '1']];

    }

    $decimal_columns = get_columns_from_schema($table, 'decimal', $module_conn);
    foreach ($decimal_columns as $field) {
        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'number', 'validation' => (object) ['min' => '-9999999999']];

    }

    $decimal_columns = get_columns_from_schema($table, 'float', $module_conn);
    foreach ($decimal_columns as $field) {

        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'number', 'validation' => (object) ['min' => '-9999999999']];

    }

    $date_columns = get_columns_from_schema($table, 'date', $module_conn);
    foreach ($date_columns as $field) {

        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'template' => '<input id = ${ruleID}_valuekey>', 'operators' => [['value' => 'equal', 'key' => 'Equals']], 'type' => 'string', 'values' => $date_values];

    }

    $datetime_columns = get_columns_from_schema($table, 'datetime', $module_conn);
    foreach ($datetime_columns as $field) {

        $columns[] = $field;
        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'template' => '<input id = ${ruleID}_valuekey>', 'operators' => [['value' => 'equal', 'key' => 'Equals']], 'type' => 'string', 'values' => $date_values];

    }

    $filter_column_names = collect($filter_columns)->pluck('field')->toArray();
    $table_columns = get_columns_from_schema($table, null, $module_conn);
    foreach ($table_columns as $field) {
        $column_name = $field;

        if (! in_array($field, $filter_column_names)) {
            $show_values = false;
            if ($module_conn != 'pbx_cdr' && $table != 'erp_communication_lines') {
                $values = \DB::connection($module_conn)->table($table)->select($column_name)->groupBy($column_name)->pluck($column_name)->toArray();
                if ($column_name == 'doctype') {
                }
                if (count($values) > 0 && count($values) < 50) {
                    $show_values = true;
                }
            }

            if ($show_values) {
                $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'string', 'values' => $values];
            } else {
                $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'string'];
            }
        }
    }
    // aa($filter_columns);
    usort($filter_columns, function ($a, $b) {
        return strnatcasecmp($a->label, $b->label);
    });

    // aa($filter_columns);
    return $filter_columns;
}

function query_builder_get_column_data($module_id)
{

    $date_values = get_condition_update_date_values();

    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    $table = $module->db_table;
    $module_conn = $module->connection;

    $integer_columns = get_columns_from_schema($table, 'integer', $module_conn);

    foreach ($integer_columns as $field) {
        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'number', 'validation' => (object) ['min' => '-9999999999']];
    }

    $boolean_columns = get_columns_from_schema($table, 'boolean', $module_conn);

    foreach ($boolean_columns as $field) {

        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'boolean', 'values' => ['0', '1']];

    }

    $decimal_columns = get_columns_from_schema($table, 'decimal', $module_conn);
    foreach ($decimal_columns as $field) {
        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'number', 'validation' => (object) ['min' => '-9999999999']];

    }

    $decimal_columns = get_columns_from_schema($table, 'float', $module_conn);
    foreach ($decimal_columns as $field) {

        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'number', 'validation' => (object) ['min' => '-9999999999']];

    }

    $date_columns = get_columns_from_schema($table, 'date', $module_conn);
    foreach ($date_columns as $field) {

        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'template' => '<input id = ${ruleID}_valuekey>', 'operators' => [['value' => 'equal', 'key' => 'Equals']], 'type' => 'string', 'values' => $date_values];

    }

    $datetime_columns = get_columns_from_schema($table, 'datetime', $module_conn);
    foreach ($datetime_columns as $field) {

        $columns[] = $field;
        $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'template' => '<input id = ${ruleID}_valuekey>', 'operators' => [['value' => 'equal', 'key' => 'Equals']], 'type' => 'string', 'values' => $date_values];

    }

    $filter_column_names = collect($filter_columns)->pluck('field')->toArray();
    $table_columns = get_columns_from_schema($table, null, $module_conn);
    foreach ($table_columns as $field) {
        $column_name = $field;

        if (! in_array($field, $filter_column_names)) {
            $show_values = false;
            if ($module_conn != 'pbx_cdr' && $table != 'erp_communication_lines') {
                $values = \DB::connection($module_conn)->table($table)->select($column_name)->groupBy($column_name)->pluck($column_name)->toArray();
                if ($column_name == 'doctype') {
                }
                if (count($values) > 0 && count($values) < 50) {
                    $show_values = true;
                }
            }

            if ($show_values) {
                $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'string', 'values' => $values];
            } else {
                $filter_columns[] = (object) ['id' => $field, 'field' => $field, 'label' => $field, 'type' => 'string'];
            }
        }
    }

    usort($filter_columns, function ($a, $b) {
        return strnatcasecmp($a->label, $b->label);
    });

    return $filter_columns;
}

function form_set_tabs_from_layout($layout_id, $module_id, $is_detail_module = 0)
{
    $layout = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->get()->first();
    if ($is_detail_module) {
        $state = json_decode($layout->detail_aggrid_state);
    } else {
        $state = json_decode($layout->aggrid_state);
    }

    if ($state->colState && is_array($state->colState) && count($state->colState) > 0) {
        $visible_fields = [];
        foreach ($state->colState as $i => $col) {
            if ($state->colState[$i]->hide == 'false' && $col->colId != 'ag-Grid-AutoColumn') {
                $field = $col->colId;
                if (str_starts_with($field, 'join_')) {
                    $field = str_replace('join_', '', $field);
                }
                $visible_fields[] = $field;
            }
        }
    }

    \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->whereIn('field', $visible_fields)->whereIn('tab', ['General', ''])->update(['tab' => 'General']);
    \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->whereNotIn('field', $visible_fields)->whereIn('tab', ['General', ''])->update(['tab' => '']);
    foreach ($visible_fields as $i => $field) {
        \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('field', $field)->update(['sort_order' => $i]);
    }
}

function events_get_function_code($row = false)
{
    if (! $row) {
        return '';
    }
    $code = get_function_code($row['function_name']);
    if (! $code) {
        return '';
    }

    return $code;
}

function aftersave_events_set_code($request)
{

    try {
        //if(!empty($request->id)){
        $function_name = $request->function_name;
        $code = $request->function_code;
        if (! function_exists($function_name)) {
            if (in_array($request->type, ['aftersave', 'beforesave', 'beforedelete', 'afterdelete', 'aftercommit'])) {
                $result = add_code_definition($function_name, 'request');
            } else {
                $result = add_code_definition($function_name, 'function');
            }
            if (! $result) {
                return json_alert('Function definition not added', 'error');
            }
        }
        $result = true;

        if (! empty($code)) {
            $result = set_function_code($function_name, $code);
        }
        if (! $result) {
            return json_alert('Function code not added', 'error');
        }
        //}
    } catch (\Throwable $ex) {
        return json_alert($ex->getMessage(), 'error');
    }

}

function aftersave_forms_update_menu_permissions($request)
{
    $menu_ids = \DB::Table('erp_menu')->where('module_id', $request->module_id)->pluck('id')->toArray();
    foreach ($menu_ids as $menu_id) {
        $toplevel_id = get_toplevel_menu_id($menu_id);

        set_menulink_permissions_from_submenu($toplevel_id);
    }

    $form = \DB::table('erp_forms')->where('id', $request->id)->get()->first();
    if (empty($form->form_json)) {
        $form_json = \DB::table('erp_forms')->where('module_id', $form->module_id)->where('role_id', 1)->pluck('form_json')->first();
        if (! empty($form_json)) {
            \DB::table('erp_forms')->where('id', $request->id)->update(['form_json' => $form_json]);
        }
    }

    $module_ids = \DB::table('erp_cruds')->pluck('id')->toArray();
    $role_ids = \DB::table('erp_user_roles')->pluck('id')->toArray();
    foreach ($module_ids as $module_id) {
        foreach ($role_ids as $role_id) {
            $form_count = \DB::table('erp_forms')->where('module_id', $module_id)->where('role_id', $role_id)->count();
            if ($form_count > 1) {
                $form_id = \DB::table('erp_forms')->where('module_id', $module_id)->where('role_id', $role_id)->orderBy('id')->pluck('id')->first();
                if ($form_id) {
                    \DB::table('erp_forms')->where('id', '!=', $form_id)->where('module_id', $module_id)->where('role_id', $role_id)->delete();
                }
            }
        }
    }
}
