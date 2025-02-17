<?php

function button_reports_open_report($request){
    $url = get_menu_url_from_module_id(488);
    $url.='/view_report/'.$request->id;
    return redirect()->to(url($url));
}

function get_report_col_defs($report_id)
{
    $report = \DB::connection('default')->table('erp_reports')->where('id', $report_id)->get()->first();
    $query_data = unserialize($report->query_data);
    $colDefs = [];

    $table_aliases = [];
    foreach ($query_data['db_tables'] as $table) {
        $alias = '';
        $table_name_arr = explode('_', $table);
        foreach ($table_name_arr as $table_name_slice) {
            $alias .= $table_name_slice[0];
        }

        if (in_array($alias, $table_aliases)) {
            $i = 1;
            while (in_array($alias, $table_aliases)) {
                $alias .= $table_name_slice[$i];
                $i++;
            }
        }
        if (str_contains($table, 'call_records')) {
            $alias = 'cdr';
        }

        $table_aliases[$table] = $alias;
    }

    $cols_added = [];

    foreach ($query_data['db_tables'] as $table) {
        foreach ($query_data['db_columns'] as $i => $col) {
            $col_arr = explode('.', $col);
            if ($table == $col_arr[0]) {
                if (!in_array($col_arr[1], $cols_added)) {
                    $cols_added[] = $col_arr[1];
                    $label = $col_arr[1];
                } else {
                    $label = $table_aliases[$col_arr[0]] . ' ' . $col_arr[1];
                    $label = str_replace('records_lastmonth ', '', $label);
                    $label = str_replace('records ', '', $label);
                }

                $sql_label = $table_aliases[$col_arr[0]] . ' ' . $col_arr[1];

                $colDef =  [
                    'field' => $sql_label,
                    'headerName' => $label,
                ];

                $colDefs[] = $colDef;
            }
        }
    }
    return $colDefs;
}



function aftersave_reports_set_default($request)
{
    if ($request->default) {
        \DB::connection('default')->table('erp_reports')->where('module_id', $request->module_id)->where('id', '!=', $request->id)->update(['default' => 0]);
    }
    $module_ids = \DB::connection('default')->table('erp_reports')->pluck('module_id')->toArray();
    foreach ($module_ids as $module_id) {
        $default_set = \DB::connection('default')->table('erp_reports')->where('module_id', $module_id)->where('default', 1)->count();
        if (!$default_set) {
            $id = \DB::connection('default')->table('erp_reports')->where('module_id', $module_id)->pluck('id')->first();
            \DB::connection('default')->table('erp_reports')->where('id', $id)->update(['default' => 1]);
        }
    }
}

function aftersave_reports_default_query($request)
{
    if (!empty($request->new_record)) {
        $module = \DB::connection('default')->table('erp_cruds')->where('id', $request->module_id)->get()->first();
        $sql_query = $module->db_sql;
        if (empty($module->db_sql)) {
            $sql_query = 'SELECT '.$module->db_table.'.* FROM '.$module->db_table;
        }

        $sql_where = $module->db_where;
        if (!empty($module->db_where)) {
            $sql_query .= ' '.$module->db_where;
        }

        $query_data['db_conn'] = $module->connection;
        $query_data['db_tables'] = [$module->db_table];
        $query_data['db_columns'] = [];

        $cols = get_columns_from_schema($module->db_table, null, $module->connection);
        foreach ($cols as $c) {
            if (str_contains($module->db_table, 'call_records') && $c == 'variables') {
                continue;
            }
            $query_data['db_columns'][] = $module->db_table.'.'.$c;
        }

        $query_data = serialize($query_data);
        $data = [
            'sql_query' => $sql_query,
            'sql_where' => $sql_where,
            'connection' => $module->connection,
            'query_data' => $query_data,
        ];

        \DB::connection('default')->table('erp_reports')->where('id', $request->id)->update($data);


        $erp_reports = new \ErpReports();
        $erp_reports->setErpConnection(session('instance')->db_connection);
        $sql = $erp_reports->reportSQL($request->id);

        if ($sql) {
            \DB::connection('default')->table('erp_reports')->where('id', $request->id)->update(['sql_query' => $sql]);
        }
    }
}

function create_default_report($module_id)
{
    $name = \DB::connection('default')->table('erp_menu')->where('module_id', $module_id)->pluck('menu_name')->first();
    if (!$name) {
        $name = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->pluck('name')->first();
    }
    $data = [
        'name' => $name,
        'module_id' => $module_id,
    ];

    $id = \DB::connection('default')->table('erp_reports')->insertGetId($data);
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    $sql_query = $module->db_sql;
    if (empty($module->db_sql)) {
        $sql_query = 'SELECT '.$module->db_table.'.* FROM '.$module->db_table;
    }

    $sql_where = $module->db_where;
    if (!empty($module->db_where)) {
        $sql_query .= ' '.$module->db_where;
    }

    $query_data['db_conn'] = $module->connection;
    $query_data['db_tables'] = [$module->db_table];
    $query_data['db_columns'] = [];

    $cols = get_columns_from_schema($module->db_table, null, $module->connection);
    foreach ($cols as $c) {
        if (str_contains($module->db_table, 'call_records') && $c == 'variables') {
            continue;
        }
        $query_data['db_columns'][] = $module->db_table.'.'.$c;
    }

    $query_data = serialize($query_data);
    $data = [
        'sql_query' => $sql_query,
        'sql_where' => $sql_where,
        'connection' => $module->connection,
        'query_data' => $query_data,
        'default' => 1,
    ];

    \DB::connection('default')->table('erp_reports')->where('id', $id)->update($data);


    $erp_reports = new \ErpReports();
    $erp_reports->setErpConnection(session('instance')->db_connection);
    $sql = $erp_reports->reportSQL($id);

    if ($sql) {
        \DB::connection('default')->table('erp_reports')->where('id', $id)->update(['sql_query' => $sql]);
    }
    return $id;
}

function button_reports_copy_report($request)
{
    if (!empty(session('instance')->id)) {
        $report = \DB::connection('default')->table('erp_reports')->where('id', $request->id)->get()->first();

        if ($report->custom) {
            return json_alert('Custom reports cannot be copied.', 'warning');
        }


        if (is_main_instance()) {
            $instances = \DB::connection('system')->table('erp_instances')->where('installed',1)->where('installed', 1)->get();
            foreach ($instances as $instance) {
                if ($instance->id != session('instance')->id) {
                    $data = (array) $report;
                    unset($data['id']);
                    $exists = \DB::connection($instance->db_connection)->table('erp_reports')->where('main_instance_id', $report->id)->count();
                    if ($exists) {
                        \DB::connection($instance->db_connection)->table('erp_reports')->where('main_instance_id', $report->id)->update($data);
                    } else {
                        $data['main_instance_id'] = $report->id;
                        \DB::connection($instance->db_connection)->table('erp_reports')->insert($data);
                    }
                }
            }
        } else {
            $instances = \DB::connection('system')->table('erp_instances')->where('installed',1)->where('installed', 1)->get();
            foreach ($instances as $instance) {
                if ($instance->id == 1) {
                    $data = (array) $report;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    if (empty($report->main_instance_id)) {
                        $report->main_instance_id = \DB::connection($instance->db_connection)->table('erp_reports')->insertGetId($data);
                        \DB::connection('default')->table('erp_reports')->where('id', $request->id)->update(['main_instance_id' => $report->main_instance_id]);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_reports')->where('id', $report->main_instance_id)->update($data);
                    }
                }
            }


            foreach ($instances as $instance) {
                if ($instance->id != session('instance')->id && $instance->id != 1) {
                    $data = (array) $report;
                    unset($data['id']);
                    $exists = \DB::connection($instance->db_connection)->table('erp_reports')->where('main_instance_id', $report->main_instance_id)->count();
                    if ($exists) {
                        \DB::connection($instance->db_connection)->table('erp_reports')->where('main_instance_id', $report->main_instance_id)->update($data);
                    } else {
                        $data['main_instance_id'] = $report->main_instance_id;
                        \DB::connection($instance->db_connection)->table('erp_reports')->insert($data);
                    }
                }
            }
        }
    }

    return json_alert('Report copied');
}

function get_report_month_filter_options($row)
{
    if (empty($row['sql_query'])) {
        return [];
    }

    $sql = strtolower($row['sql_query']);

    $select_arr = explode('from', $sql);

    $select_fields = explode(' ', $select_arr[0]);

    $tables = [];
    foreach ($select_fields as $select_field) {
        if (str_contains($select_field, '.')) {
            $select_field_arr = explode('.', $select_field);
            $tables[] = $select_field_arr[0];
        }
    }

    $tables = array_unique($tables);

    $select_list = [];
    foreach ($tables as $t) {
        $date_fields = get_columns_from_schema($t, 'date');
        $datetime_fields = get_columns_from_schema($t, 'datetime');
        foreach ($date_fields as $f) {
            $select_list[$t.'.'.$f] = $t.'.'.$f;
        }
        foreach ($datetime_fields as $f) {
            $select_list[$t.'.'.$f] = $t.'.'.$f;
        }
    }

    return $select_list;
}

function save_img_to_pdf($file_path)
{
    if (str_ends_with($file_path, '.jpg') || str_ends_with($file_path, '.jpeg') || str_ends_with($file_path, '.png')) {
        $pdf_html = '<img src="data:image/jpeg;base64,'. base64_encode(@file_get_contents($file_path)) .'">';

        $pdf = PDF::loadHtml($pdf_html);
        $options = [
            'orientation' => 'portrait',
            'encoding' => 'UTF-8',
            'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
            'footer-font-size' => 8,
        ];

        $pdf->setOptions($options);
        $file_path = str_replace(['.jpg','.jpeg','.png'], '.pdf', $file_path);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $pdf->save($file_path);
        return true;
    }
    return false;
}



function button_reports_joins_update_from_schema($request)
{
    $join_list = [];
    $tables = get_tables_from_schema();
    foreach ($tables as $table) {
        $foreign_keys = get_all_foreign_keys_from_schema($table);
        foreach ($foreign_keys as $key => $ref) {
            $data = ['join_1' => $key,'join_2' => $ref['table'].'.'.$ref['key'], 'type' => 'Schema'];

            $exists = \DB::table('erp_report_joins')->where('join_1', $data['join_1'])->where('join_2', $data['join_2'])->count();
            if (!$exists) {
                \DB::table('erp_report_joins')->insert($data);
            }
        }
    }

    $fields = \DB::table('erp_module_fields')->where('field_type', 'select_module')->where('opts_multiple', 0)->get();

    foreach ($fields as $field) {
        $data = ['join_1' => $field->alias.'.'.$field->field,'join_2' => $field->opt_db_table.'.'.$field->opt_db_key, 'type' => 'Field'];

        $exists = \DB::table('erp_report_joins')->where('join_1', $data['join_1'])->where('join_2', $data['join_2'])->count();
        if (!$exists) {
            \DB::table('erp_report_joins')->insert($data);
        }
    }
    return json_alert('Done');
}

function aftercommit_set_fds_reports($request)
{
    $beforesave_row = session('event_db_record');

    if (!empty($beforesave_row) && $request->fds && $request->fds != $beforesave_row->fds) {
        $flexmonster = new \Flexmonster();
        $flexmonster->loadIndexes();
        $flexmonster->dataServerRestart();
    }
}



function button_reports_restart_fds($request)
{
    $flexmonster = new \Flexmonster();
    $flexmonster->loadIndexes();
    $result = $flexmonster->dataServerRestart();
    if ($result == true) {
        return json_alert('Server restarted');
    } else {
        return json_alert('Server could not be restarted');
    }
}

function schedule_daily_reportserver_restart()
{ 
    $flexmonster = new \Flexmonster();
    $flexmonster->testReportQueries();
    
    $erp_reports = new \ErpReports();
    $erp_reports->setErpConnection(session('instance')->db_connection);
    $report_ids = \DB::connection('default')->table('erp_reports')->where('invalid_query',1)->pluck('id')->toArray();
    foreach($report_ids as $report_id){
        $sql = $erp_reports->reportSQL($report_id);
      
        if ($sql) {
            \DB::connection('default')->table('erp_reports')->where('id', $report_id)->update(['sql_query' => $sql]);
        }
    }
    $flexmonster->testReportQueries();
    
    
    $flexmonster->loadIndexes();
    $flexmonster->dataServerRestart();
}
