<?php

function beforesave_check_drilldown_rowmodel($request)
{
    if ($request->drill_down && $request->serverside_model) {
        return 'Drill down only active on client side row model';
    }
}

function get_status_dropdown($module_id)
{
    $opts_values = [];
    $field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->whereIn('field_type', ['select_custom', 'select_module'])->where('show_grid_buttons', 1)->get()->first();

    if (isset($field) && $field->field_type == 'select_module') {
        if (empty($field) || empty($field->id)) {
            return false;
        }

        $opts_values = get_module_field_options($module_id, $field->field);
    }

    if (isset($field) && $field->field_type == 'select_custom') {
        $field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('field_type', 'select_custom')->where('show_grid_buttons', 1)->get()->first();

        if (empty($field) || empty($field->id)) {
            return false;
        }

        $opts_values = collect(explode(',', $field->opts_values))->filter()->unique()->toArray();
        if ($module_id == '1898') {
            foreach ($opts_values as $i => $v) {
                // if($v == 'Done'){
                //     unset($opts_values[$i]);
                //  }
                // if($v == 'Not Done'){
                //     unset($opts_values[$i]);
                // }
            }
            $opts_values = array_values($opts_values);
        }
    }
    if (count($opts_values) == 0) {
        return false;
    } else {

        return ['label' => $field->label, 'status_key' => $field->field, 'options' => $opts_values];
    }
}

function status_dropdown_ajax($module_id, $status_field, $status_val, $row_id)
{

    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    $field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('field', $status_field)->get()->first();
    $opts_values = collect(explode(',', $field->opts_values))->filter()->unique()->toArray();
    $status_function = false;
    if (! empty($field->status_functions)) {
        $status_functions = collect(explode(PHP_EOL, $field->status_functions))->filter()->unique()->toArray();
        foreach ($status_functions as $line) {
            $line_arr = explode('|', $line);
            if ($line_arr[0] == $status_val) {
                $status_function = $line_arr[1];
            }
        }
    }

    if ($module->db_table == 'sub_activations') {
        $row = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $row_id)->get()->first();
        if ($row->status == 'Enabled' || $row->status == 'Deleted') {
            return json_alert('Status cannot be changed', 'warning');
        }
    }

    if ($status_function) {
        $status_function = trim($status_function);
        if (! function_exists($status_function)) {
            $response = json_alert($status_function.' does not exists', 'warning');
        }

        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');
        $request_object->request->add(['id' => $row_id]);

        $response = $status_function($request_object);
    } elseif (! $status_val) {
        $response = json_alert('Status value key not set on button', 'warning');
    } elseif (! $status_field) {
        $response = json_alert('Status field not set on button', 'warning');
    } else {

        \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $row_id)->update([$status_field => $status_val]);

        $response = json_alert($field->label.' updated');
    }

    $newData = ['row_id' => $row_id, 'module_id' => $module_id];
    $master_module_id = \DB::connection('default')->table('erp_cruds')->where('detail_module_id', $module_id)->pluck('id')->first();

    if (! empty($master_module_id)) {
        $newData['master_module_id'] = $master_module_id;
    }
    $data = $response->getData(true);

    $data = array_merge($data, $newData);
    $response->setData($data);

    return $response;
}

function aftersave_create_grid_status_buttons($request)
{

    if ($request->field_type == 'select_custom' && $request->show_grid_buttons) {
        // aa('aftersave_create_grid_status_buttons');
        $field = \DB::connection('default')->table('erp_module_fields')->where('id', $request->id)->get()->first();
        $opts_values = collect(explode(',', $field->opts_values))->filter()->unique()->toArray();
        $status_functions = collect(explode(PHP_EOL, $field->status_functions))->filter()->unique()->toArray();
        foreach ($opts_values as $i => $value) {

            $status_function = '';
            foreach ($status_functions as $line) {
                $line_arr = explode('|', $line);
                if ($line_arr[0] == $value) {
                    $status_function = $line_arr[1];
                }
            }
            $where_data = [
                'location' => 'status_buttons',
                'render_module_id' => $request->module_id,
                'status_field' => $request->field,
                'status_key' => $value,
            ];
            \DB::connection('default')->table('erp_menu')->where($where_data)->whereNotIn('status_key', $opts_values)->delete();
            $data = [
                'location' => 'status_buttons',
                'menu_name' => $value,
                'menu_type' => 'link',
                'url' => '#',
                'sort_order' => $i,
                'active' => 1,
                'render_module_id' => $field->module_id,
                'require_grid_id' => 1,
                'action_type' => 'ajax',
                'ajax_function_name' => 'status_button_ajax',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => get_system_user_id(),
                'status_field' => $field->field,
                'status_key' => $value,
                'status_function' => $status_function,
            ];
            if (! is_main_instance()) {
                $e = \DB::connection('default')->table('erp_menu')->where($where_data)->get()->first();
                if (empty($e) || empty($e->id)) {
                    $data['custom'] = 1;
                } elseif (empty($e->main_instance_id)) {
                    $data['custom'] = 1;
                }
            }

            \DB::connection('default')->table('erp_menu')->updateOrInsert($where_data, $data);
        }
    }

    if (! $request->show_grid_buttons) {
        \DB::connection('default')->table('erp_menu')->where('location', 'status_buttons')->where('render_module_id', $request->module_id)->where('status_field', $request->field)->delete();
    }

}

function update_module_permissions()
{
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    foreach ($modules as $module) {
        try {
            if (! empty($module->permissions) && $module->permissions != 'Full Access') {
                $data = [];
                if ($module->permissions == 'View') {
                    $data['is_add'] = 0;
                    $data['is_edit'] = 0;
                    $data['is_delete'] = 0;
                }
                if ($module->permissions == 'Add') {
                    $data['is_edit'] = 0;
                    $data['is_delete'] = 0;
                }
                if ($module->permissions == 'Edit') {
                    $data['is_add'] = 0;
                    $data['is_delete'] = 0;
                }

                if ($module->permissions == 'Add & Edit') {
                    $data['is_delete'] = 0;
                }
                if ($module->db_table == 'crm_accounts' || $module->db_table == 'sub_services') {
                    $data['is_delete'] = 0;
                }

                \DB::table('erp_forms')->where('module_id', $module->id)->update($data);

                if ($module->db_table == 'crm_accounts' || $module->db_table == 'sub_services') {
                    \DB::connection('default')->table('erp_forms')->where('module_id', $module->id)->whereIn('role_id', [1, 35])->update(['is_delete' => 1]);
                }
            }
        } catch (\Throwable $ex) {
            exception_log($module);
            exception_log($module->id);
            exception_log($ex - getMessage());
            exception_log($ex - getTraceAsString());
        }
    }
}

function aftersave_module_update_permissions($request)
{
    update_module_permissions();
}

function aftersave_module_update_menu_names($request)
{
    $menus = \DB::table('erp_menu')->where('menu_type', 'module')->get();
    foreach ($menus as $m) {
        $module_name = \DB::table('erp_cruds')->where('id', $m->module_id)->pluck('name')->first();
        if ($m->menu_name != $module_name) {
            \DB::table('erp_menu')->where('id', $m->id)->update(['menu_name' => $module_name]);
        }
    }
}

function aftersave_module_fields_set_row_total_fields($request)
{

    \DB::table('erp_module_fields')->update(['pinned_row_total' => 0]);
    \DB::table('erp_module_fields')->where('field', 'Not Like', '%\_id')->where('field', '!=', 'doc_no')->where('field', '!=', 'id')->whereIn('field_type', ['decimal', 'currency', 'integer'])->update(['pinned_row_total' => 1]);
}

function update_recent_modules($module_id)
{
    //crm_recent_modules
    /*
    if(!empty(session('user_id'))){
        $data = [
            'user_id' => session('user_id'),
            'module_id' => $module_id,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => get_system_user_id(),
        ];
        \DB::connection('default')->table('crm_recent_modules')->insert($data);
    }
    */
}

function get_linked_module_fields($module_id)
{
    return \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('field_type', 'select_module')->get();
}

function get_recent_modules()
{
    $modules = \DB::connection('default')->table('crm_recent_modules')->where('user_id', session('user_id'))->groupBy('module_id')->orderBy('created_at', 'desc')->limit(3)->get();

    return $modules;
}

function get_recent_modules_menu()
{
    $module_ids = \DB::connection('default')->table('crm_recent_modules')->where('user_id', session('user_id'))->groupBy('module_id')->orderBy('created_at', 'desc')->limit(3)->pluck('module_id')->toArray();
    $i = 20000;

    foreach ($module_ids as $module_id) {
        $mod = app('erp_config')['modules']->where('id', $module_id)->first();
        $list[] = ['url' => $mod->slug, 'menu_name' => $mod->name, 'menu_icon' => '', 'action_type' => 'view', 'menu_type' => 'module_filter', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        $i++;
    }

    return $list;
}

function get_recent_modules_toolbar()
{
    $module_ids = \DB::connection('default')->table('crm_recent_modules')->where('user_id', session('user_id'))->groupBy('module_id')->orderBy('created_at', 'desc')->limit(3)->pluck('module_id')->toArray();

    $recent_modules = [];
    foreach ($module_ids as $module_id) {
        $recent_modules[] = app('erp_config')['modules']->where('id', $module_id)->first();
    }

    return $recent_modules;
}

function schedule_clear_recent_modules()
{
    \DB::table('crm_recent_modules')->where('created_at', '<', date('Y-m-d', strtotime('-7 days')))->delete();
}

function button_module_update_instances($request)
{
    try {
        $instance_module_id = $request->module_id;
        // FIELDS
        $module_fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $instance_module_id)->get();
        foreach ($module_fields as $module_field) {
            $row_id = $module_field->id;
            $update_id = $row_id;
            if (! is_main_instance()) {
                $module_field = \DB::connection('default')->table('erp_module_fields')->where('main_instance_id', $row_id)->get()->first();
                if (empty($module_field) || empty($module_field->main_instance_id)) {
                    $data = (array) $module_field;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = \DB::connection('system')->table('erp_module_fields')->insertGetId($data);

                    \DB::connection('default')->table('erp_module_fields')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                } else {
                    $data = (array) $module_field;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = $module_field->main_instance_id;
                    \DB::connection('system')->table('erp_module_fields')->where('id', $update_id)->update($data);
                }
            }
            $module_field = \DB::connection('system')->table('erp_module_fields')->where('id', $update_id)->get()->first();
            $db_conns = db_conns_excluding_main();
            foreach ($db_conns as $db_conn) {
                $data = (array) $module_field;
                unset($data['id']);
                $data['main_instance_id'] = $update_id;
                $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $module_field->module_id)->pluck('id')->first();
                $exists = \DB::connection($db_conn)->table('erp_module_fields')->where('main_instance_id', $update_id)->count();
                if (! $exists) {
                    \DB::connection($db_conn)->table('erp_module_fields')->insert($data);
                } else {
                    \DB::connection($db_conn)->table('erp_module_fields')->where('main_instance_id', $update_id)->update($data);
                }
            }
        }

        // FORMS
        $forms = \DB::connection('default')->table('erp_forms')->where('module_id', $instance_module_id)->get();
        foreach ($forms as $form) {
            $row_id = $form->id;
            $update_id = $row_id;
            if (! is_main_instance()) {
                $form = \DB::connection('default')->table('erp_forms')->where('main_instance_id', $row_id)->get()->first();
                if (empty($form) || empty($form->main_instance_id)) {
                    $data = (array) $form;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = \DB::connection('system')->table('erp_forms')->insertGetId($data);

                    \DB::connection('default')->table('erp_forms')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                } else {
                    $data = (array) $form;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = $form->main_instance_id;
                    \DB::connection('system')->table('erp_forms')->where('id', $update_id)->update($data);
                }
            }
            $form = \DB::connection('system')->table('erp_forms')->where('id', $update_id)->get()->first();
            $db_conns = db_conns_excluding_main();
            foreach ($db_conns as $db_conn) {
                $data = (array) $form;
                unset($data['id']);
                $data['main_instance_id'] = $update_id;
                $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $form->module_id)->pluck('id')->first();
                $exists = \DB::connection($db_conn)->table('erp_forms')->where('main_instance_id', $update_id)->count();
                if (! $exists) {
                    \DB::connection($db_conn)->table('erp_forms')->insert($data);
                } else {
                    \DB::connection($db_conn)->table('erp_forms')->where('main_instance_id', $update_id)->update($data);
                }
            }
        }

        // LAYOUTS
        $grid_views = \DB::connection('default')->table('erp_grid_views')->where('module_id', $instance_module_id)->get();
        foreach ($grid_views as $grid_view) {
            $row_id = $grid_view->id;
            $update_id = $row_id;
            if (! is_main_instance()) {
                $grid_view = \DB::connection('default')->table('erp_grid_views')->where('main_instance_id', $row_id)->get()->first();
                if (empty($grid_view) || empty($grid_view->main_instance_id)) {
                    $data = (array) $grid_view;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = \DB::connection('system')->table('erp_grid_views')->insertGetId($data);

                    \DB::connection('default')->table('erp_grid_views')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                } else {
                    $data = (array) $grid_view;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = $grid_view->main_instance_id;
                    \DB::connection('system')->table('erp_grid_views')->where('id', $update_id)->update($data);
                }
            }
            $grid_view = \DB::connection('system')->table('erp_grid_views')->where('id', $update_id)->get()->first();
            $db_conns = db_conns_excluding_main();
            foreach ($db_conns as $db_conn) {
                $data = (array) $grid_view;
                unset($data['id']);
                $data['main_instance_id'] = $update_id;
                $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $grid_view->module_id)->pluck('id')->first();
                $exists = \DB::connection($db_conn)->table('erp_grid_views')->where('main_instance_id', $update_id)->count();
                if (! $exists) {
                    \DB::connection($db_conn)->table('erp_grid_views')->insert($data);
                } else {
                    \DB::connection($db_conn)->table('erp_grid_views')->where('main_instance_id', $update_id)->update($data);
                }
            }
        }

        // REPORTS
        $reports = \DB::connection('default')->table('erp_reports')->where('module_id', $instance_module_id)->get();
        foreach ($reports as $report) {
            $row_id = $report->id;
            $update_id = $row_id;
            if (! is_main_instance()) {
                $report = \DB::connection('default')->table('erp_reports')->where('main_instance_id', $row_id)->get()->first();
                if (empty($report) || empty($report->main_instance_id)) {
                    $data = (array) $report;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = \DB::connection('system')->table('erp_reports')->insertGetId($data);

                    \DB::connection('default')->table('erp_reports')->where('id', $row_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
                } else {
                    $data = (array) $report;
                    unset($data['id']);
                    unset($data['main_instance_id']);
                    $update_id = $report->main_instance_id;
                    \DB::connection('system')->table('erp_reports')->where('id', $update_id)->update($data);
                }
            }
            $report = \DB::connection('system')->table('erp_reports')->where('id', $update_id)->get()->first();
            $db_conns = db_conns_excluding_main();
            foreach ($db_conns as $db_conn) {
                $data = (array) $report;
                unset($data['id']);
                $data['main_instance_id'] = $update_id;
                $data['module_id'] = \DB::connection($db_conn)->table('erp_cruds')->where('main_instance_id', $report->module_id)->pluck('id')->first();
                $exists = \DB::connection($db_conn)->table('erp_reports')->where('main_instance_id', $update_id)->count();
                if (! $exists) {
                    \DB::connection($db_conn)->table('erp_reports')->insert($data);
                } else {
                    \DB::connection($db_conn)->table('erp_reports')->where('main_instance_id', $update_id)->update($data);
                }
            }
        }

        return json_alert('Copied');
    } catch (\Throwable $ex) {
        exception_log($ex);

        return json_alert($ex->getMessage(), 'error');
    }
}

function beforesave_set_app_db_table($request)
{
    if (empty($request->id) && empty($request->db_table)) {
        $app_prefix = \DB::connection('system')->table('erp_apps')->where('id', $request->app_id)->pluck('prefix')->first();

        if (! empty($app_prefix)) {
            $request->request->add(['db_table' => $app_prefix.str_replace(' ', '_', strtolower($request->name))]);
        } else {
            return 'App prefix not set.';
        }
    }
}

function aftercommit_module_check_table($request)
{

    \DB::connection('default')->table('erp_module_fields')->update(['opt_db_sortorder' => '']);
    \DB::connection('default')->table('erp_module_fields')->whereNull('opt_db_sortorder')->update(['opt_db_sortorder' => '']);
    $opt_module_ids = \DB::connection('default')->table('erp_module_fields')
        ->where('field_type', 'select_module')
        ->where('opt_db_sortorder', '')
        ->pluck('opt_module_id')
        ->unique()
        ->toArray();
    foreach ($opt_module_ids as $opt_module_id) {
        $has_sort_order = \DB::connection('default')->table('erp_module_fields')->where('module_id', $opt_module_id)->where('field', 'sort_order')->count();
        if ($has_sort_order) {
            \DB::connection('default')->table('erp_module_fields')->where('opt_db_sortorder', '')->where('opt_module_id', $opt_module_id)->update(['opt_db_sortorder' => 'sort_order']);
        } else {
            $sort_fields = [];
            $conf = \DB::table('erp_grid_views')->where('module_id', $opt_module_id)->where('global_default', 1)->pluck('aggrid_state')->first();
            $conf = json_decode($conf);
            if (isset($conf->colState) && count($conf->colState) > 0) {
                $sort_fields = collect($conf->colState)->where('sort', '>', '')->sortBy('sortIndex')->pluck('colId')->toArray();
            }
            if (count($sort_fields) > 0) {
                foreach ($sort_fields as $i => $sf) {
                    if (str_starts_with($sf, 'join_')) {
                        $sort_fields[$i] = str_replace('join_', '', $sf);
                    }
                }
                \DB::connection('default')->table('erp_module_fields')->where('opt_db_sortorder', '')->where('opt_module_id', $opt_module_id)->update(['opt_db_sortorder' => implode(',', $sort_fields)]);
            } else {
                $key = \DB::connection('default')->table('erp_cruds')->where('id', $opt_module_id)->pluck('db_key')->first();
                \DB::connection('default')->table('erp_module_fields')->where('opt_db_sortorder', '')->where('opt_module_id', $opt_module_id)->update(['opt_db_sortorder' => $key]);
            }
        }
    }

    if (! empty($request->new_record) && ! \Schema::connection($request->connection)->hasTable($request->table_name)) {
        $erp = new DBEvent;
        $erp->setTable('erp_instance_migrations');
        $data = [
            'action' => 'table_add',
            'connection' => $request->connection,
            'table_name' => $request->db_table,
            'custom' => $request->custom,
        ];
        $erp->save($data);
    }

    if ($request->soft_delete && $request->connection == 'default') {
        $module = \DB::table('erp_cruds')->where('id', $request->id)->get()->first();
        if (! \Schema::connection($module->connection)->hasColumn($module->db_table, 'soft_delete')) {
            $erp = new DBEvent;
            $erp->setTable('erp_instance_migrations');
            $data = [
                'action' => 'column_add',
                'connection' => $module->connection,
                'table_name' => $module->db_table,
                'field_name' => 'is_deleted',
                'field_type' => 'Tiny Integer',
                'default_value' => 0,
                'field_length' => 1,
            ];
            $erp->save($data);
        }
    }

    if ($request->time_stamps && $request->connection == 'default') {
        $system_user_id = get_system_user_id();
        $module = \DB::table('erp_cruds')->where('id', $request->id)->get()->first();
        if (! \Schema::connection($module->connection)->hasColumn($module->db_table, 'created_at')) {
            $erp = new DBEvent;
            $erp->setTable('erp_module_fields');
            $data = [
                'field' => 'created_at',
                'label' => 'Created At',
                'tab' => '',
                'module_id' => $module->id,
                'visible' => 'None',
                'field_type' => 'datetime',
                'alias' => $module->db_table,
                'custom' => (is_main_instance()) ? 0 : 1,
                'readonly' => 'Add and Edit',
            ];
            $erp->save($data);
            \DB::connection($module->connection)->table($module->db_table)->update(['created_at' => date('Y-m-d H:i:s')]);
        }
        if (! \Schema::connection($module->connection)->hasColumn($module->db_table, 'updated_at')) {
            $erp = new DBEvent;
            $erp->setTable('erp_module_fields');
            $data = [
                'field' => 'updated_at',
                'label' => 'Updated At',
                'tab' => '',
                'module_id' => $module->id,
                'visible' => 'None',
                'field_type' => 'datetime',
                'alias' => $module->db_table,
                'custom' => (is_main_instance()) ? 0 : 1,
                'readonly' => 'Add and Edit',
            ];
            $erp->save($data);
            \DB::connection($module->connection)->table($module->db_table)->update(['updated_at' => date('Y-m-d H:i:s')]);
        }

        if (! \Schema::connection($module->connection)->hasColumn($module->db_table, 'created_by')) {
            $erp = new DBEvent;
            $erp->setTable('erp_module_fields');
            $data = [
                'field' => 'created_by',
                'label' => 'Created By',
                'tab' => '',
                'module_id' => $module->id,
                'visible' => 'None',
                'field_type' => 'select_module',
                'opt_db_sortorder' => 'sort_order',
                'opt_module_id' => 228,
                'alias' => $module->db_table,
                'custom' => (is_main_instance()) ? 0 : 1,
                'readonly' => 'Add and Edit',
            ];
            $erp->save($data);

            \DB::connection($module->connection)->table($module->db_table)->update(['created_by' => $system_user_id]);
        }
        if (! \Schema::connection($module->connection)->hasColumn($module->db_table, 'updated_by')) {
            $erp = new DBEvent;
            $erp->setTable('erp_module_fields');
            $data = [
                'field' => 'updated_by',
                'label' => 'Updated By',
                'tab' => '',
                'module_id' => $module->id,
                'visible' => 'None',
                'field_type' => 'select_module',
                'opt_module_id' => 228,
                'opt_db_sortorder' => 'sort_order',
                'alias' => $module->db_table,
                'custom' => (is_main_instance()) ? 0 : 1,
                'readonly' => 'Add and Edit',
            ];
            $erp->save($data);

            \DB::connection($module->connection)->table($module->db_table)->update(['updated_by' => $system_user_id]);
        }
    }

    update_module_config_from_schema($request->id);

    if (! empty($request->new_record)) {
        \DB::table('erp_module_fields')->where('field', 'id')->update(['visible' => 'Add and Edit', 'field_type' => 'hidden']);

        formio_create_form_from_db($request->id);
    }
}

function rowdata_hierarchy($row_data, $module_id)
{
    //return $row_data;
    $module = \DB::table('erp_cruds')->where('id', $module_id)->get()->first();

    $module_fields = \DB::table('erp_module_fields')->select('field', 'display_field')->where('module_id', $module_id)->get();
    /*
    $display_field = $module_fields->where('display_field',1)->pluck('field')->first();
    if(!$display_field){
        $display_field = $module_fields->where('field','name')->pluck('field')->first();
    }
    if(!$display_field){
        $display_field = $module_fields->where('field','title')->pluck('field')->first();
    }
    if(!$display_field){
        $display_field = $module->tree_data_field;
    }
  */

    $display_field = $module->db_key;

    // get all parent rows
    $collection = collect($row_data);
    $hierarchy = $collection->map(function ($item, $key) use ($collection, $module, $display_field) {
        $hierarchy = rowdata_get_hierarchy_node($item, $collection, $module, $display_field);

        $item->hierachy = $hierarchy;

        return $item;
    });

    return $hierarchy->all();
}

function rowdata_get_hierarchy_node($item, $collection, $module, $display_field, $hierarchy = [])
{
    if (count($hierarchy) == 0) {
        $hierarchy = [$item->{$display_field}];
    }

    if ($item->{$module->tree_data_key} > 0) {
        $parent_node = $collection->where($module->db_key, $item->{$module->tree_data_key})->first();

        array_unshift($hierarchy, $parent_node->{$display_field});

        if ($parent_node->{$module->tree_data_key} > 0) {
            $hierarchy = rowdata_get_hierarchy_node($parent_node, $collection, $module, $display_field, $hierarchy);
        }
    }

    return $hierarchy;
}

function aftersave_set_uploads_dir($request)
{
    $modules = \DB::table('erp_cruds')->pluck('id')->toArray();
    foreach ($modules as $module_id) {
        $uploads_dir = uploads_path($module_id);
        if (! File::isDirectory($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
            shell_exec('chmod 777 '.$uploads_dir.' -R');
        }
    }
}

function sort_grid_columns($rows)
{
    $rows = json_decode(json_encode($rows), true);
    if (! empty($rows)) {
        usort($rows, function ($a, $b) {
            $asort = $a['visible_sort_order'];
            $bsort = $b['visible_sort_order'];

            if ($asort === $bsort) {
                $asort = $a['sort_order'];
                $bsort = $b['sort_order'];
            }

            return $asort <=> $bsort;
        });
        $rows = json_decode(json_encode($rows), false);
    }

    return $rows;
}

function aftersave_module_set_slug($request)
{

    $module = \DB::table('erp_cruds')->where('id', $request->id)->get()->first();
    $slug = strtolower(str_replace(['_', ' '], '-', string_clean($module->name)));
    $slug_exists = \DB::table('erp_cruds')->where('id', '!=', $module->id)->where('slug', $slug)->count();
    if ($slug_exists) {
        $slug .= $module->id;
    }
    \DB::table('erp_cruds')->where('id', $module->id)->update(['slug' => $slug]);
    /*
    $detail_module_ids = \DB::table('erp_cruds')->where('detail_module_id','>',0)->pluck('detail_module_id')->toArray();
    $detail_modules = \DB::table('erp_cruds')->whereIn('id',$detail_module_ids)->get();

    foreach($detail_modules as $module){
       $menu = \DB::table('erp_menu')->where('location','!=','related_items_menu')->whereIn('menu_type',['module','module_filter'])->where('module_id',$module->id)->where('unlisted',0)->count();
       if(!$menu){
           $module_name = \DB::table('erp_cruds')->where('detail_module_id',$module->id)->pluck('name')->first();
           if(!empty($module_name)){
               $module_name .= ' Details';
               $slug = strtolower(str_replace(['_',' '], '-', string_clean($module_name)));
               \DB::table('erp_cruds')->where('id',$module->id)->update(['name'=>$module_name,'slug'=>$slug]);
           }
       }
    }
    */
    if (! empty($request->new_record)) {
        \DB::table('erp_module_fields')->where('field', 'id')->update(['visible' => 'Add and Edit', 'field_type' => 'hidden']);

        $app_prefixes = \DB::table('erp_apps')->pluck('prefix')->unique()->filter()->toArray();
        $modules = \DB::table('erp_cruds')->where('id', $request->id)->where('connection', 'default')->orderBy('id')->get()->unique('db_table');

        foreach ($modules as $module) {
            $foreign_field_name = str_replace($app_prefixes, '', $module->db_table);

            $foreign_field_name = str_replace('_', ' ', $foreign_field_name);
            if (str_plural($foreign_field_name) == $foreign_field_name) {
                $foreign_field_name = str_singular($foreign_field_name);
            }

            \DB::table('erp_cruds')->where('id', $module->id)->update(['foreign_field_name' => $foreign_field_name]);

        }
    }
}

function module_set_foreign_field_names()
{
    $app_prefixes = \DB::table('erp_apps')->pluck('prefix')->unique()->filter()->toArray();
    $modules = \DB::table('erp_cruds')->where('connection', 'default')->orderBy('id')->get()->unique('db_table');

    foreach ($modules as $module) {
        $foreign_field_name = str_replace($app_prefixes, '', $module->db_table);

        $foreign_field_name = str_replace('_', ' ', $foreign_field_name);
        if (str_plural($foreign_field_name) == $foreign_field_name) {
            $foreign_field_name = str_singular($foreign_field_name);
        }

        \DB::table('erp_cruds')->where('id', $module->id)->update(['foreign_field_name' => $foreign_field_name]);

    }
}

function modules_set_routing()
{
    $db_conns = db_conns();
    foreach ($db_conns as $c) {
        $modules = \DB::connection($c)->table('erp_cruds')->get();
        foreach ($modules as $module) {
            $slug = strtolower(str_replace(['_', ' '], '-', string_clean($module->name)));
            $slug_exists = \DB::connection($c)->table('erp_cruds')->where('id', '!=', $module->id)->where('slug', $slug)->count();
            if ($slug_exists) {
                $slug .= $module->id;
            }
            \DB::connection($c)->table('erp_cruds')->where('id', $module->id)->update(['slug' => $slug]);
        }
    }
}

function aftersave_create_menu($request)
{
    $exclude_module_ids = \DB::table('erp_cruds')->where('custom', 1)->pluck('id')->toArray();
    \DB::table('erp_menu')->whereIn('module_id', $exclude_module_ids)->update(['custom' => 1]);
    $modules = \DB::table('erp_cruds')->get();
    foreach ($modules as $m) {
        \DB::table('erp_menu')->where('render_module_id', $m->id)->update(['app_id' => $m->app_id]);
    }
    $modules = \DB::table('erp_cruds')->get();
    foreach ($modules as $m) {
        \DB::table('erp_menu')->where('module_id', $m->id)->update(['app_id' => $m->app_id]);
    }

    \DB::table('erp_form_events')->where('module_id', $request->id)->update(['app_id' => $request->app_id]);
    // $name = str_replace(' ', '_', strtolower($request->name));
    // \DB::table('erp_cruds')->where('id', $request->id)->update(['name' => $name]);
    $menu_exists = \DB::table('erp_menu')->where('module_id', $request->id)->count();

    if (! $menu_exists) {

        $module = \DB::table('erp_cruds')->where('id', $request->id)->get()->first();
        $menu = [
            'menu_name' => ucwords(str_replace('_', ' ', $module->name)),
            'menu_type' => 'module',
            'module_id' => $module->id,
            'active' => 1,
            'location' => 'main_menu',
            'parent_id' => 0,
        ];

        $erp = new \DBEvent;
        $result = $erp->setTable('erp_menu')->save($menu);
    }
    generate_all_records_layout($request->id);
}

function afterdelete_modules_delete_log($request)
{
    \DB::table('erp_module_log')->where('module_id', $request->id)->delete();
}

function afterdelete_modules_delete_fields($request)
{
    \DB::table('erp_module_fields')->where('module_id', $request->id)->delete();
    \DB::table('erp_module_fields')->where('opt_module_id', $request->id)->update(['field_type' => 'integer']);
}

function afterdelete_modules_delete_menus($request)
{
    $menu_ids = \DB::table('erp_menu')->where('module_id', $request->id)->pluck('id')->toArray();
    if (! empty($menu_ids) && count($menu_ids) > 0) {
        \DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->delete();
        \DB::table('erp_menu')->whereIn('id', $menu_ids)->delete();
    }

    $menu_ids = \DB::table('erp_menu')->where('render_module_id', $request->id)->pluck('id')->toArray();
    if (! empty($menu_ids) && count($menu_ids) > 0) {
        \DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->delete();
        \DB::table('erp_menu')->whereIn('id', $menu_ids)->delete();
    }
}

function build_workflow_helpers() {}

function build_general_helpers() {}

function get_module_formjs($module_id)
{
    $formjs = \DB::table('erp_form_events')->where('module_id', $module_id)->where('type', 'formjs')->orderby('type')->get();
    $formjs_code = '';
    foreach ($formjs as $js) {
        $formjs_code .= $js->code;
    }

    return $formjs_code;
}

function get_module_gridjs($module_id)
{
    $gridjs = \DB::table('erp_form_events')->where('module_id', $module_id)->where('type', 'gridjs')->orderby('type')->get();
    $gridjs_code = '';
    foreach ($gridjs as $js) {
        $gridjs_code .= $js->code;
    }

    return $gridjs_code;
}

function get_workflow_functions($module_id)
{
    $default_functions = ['indextop', 'beforesave', 'aftersave', 'beforedelete', 'afterdelete'];
    $functions = \DB::table('erp_form_events')->where('module_id', $module_id)
        ->whereNotNull('code')
        ->where('code', '>', '')
        ->whereIn('type', $default_functions)->orderBy('type')->orderBy('sort')->get();

    return $functions;
}

function get_custom_functions($module_id)
{
    $functions = \DB::table('erp_form_events')->where('module_id', $module_id)
        ->whereNotNull('code')
        ->where('code', '>', '')
        ->where('type', 'customfunction')->orderBy('type')->orderBy('sort')->get();

    return $functions;
}

function custom_field_exists($table, $field)
{
    return \DB::table('erp_custom_fields')->where('table_name', $table)->where('field_name', $field)->count();
}

function custom_field_add($table, $field)
{
    \DB::table('erp_custom_fields')->insert(['table_name' => $table, 'field_name' => $field]);
}

function custom_field_update($table, $field, $old_field)
{
    \DB::table('erp_custom_fields')->where('table_name', $table)->where('field_name', $old_field)->update(['field_name' => $field]);
}

function custom_field_delete($table, $field)
{
    \DB::table('erp_custom_fields')->where('table_name', $table)->where('field_name', $field)->delete();
}

function sort_wf_rows($rows)
{
    $rows = json_decode(json_encode($rows), true);

    usort($rows, function ($a, $b) {
        $asort = \DB::table('erp_user_roles')->where('id', $a['group_id'])->pluck('name')->first();
        $bsort = \DB::table('erp_user_roles')->where('id', $b['group_id'])->pluck('name')->first();
        if ($asort === $bsort) {
            $asort = $a['subject'];
            $bsort = $b['subject'];
        }

        return $asort <=> $bsort;
    });
    $rows = json_decode(json_encode($rows), false);

    return $rows;
}

function sort_grid_rows($rows, $params)
{
    //sort by display values
    $sorts = [$params['orderby'] => $params['ordertype'], $params['orderby2'] => $params['ordertype2'], $params['orderby3'] => $params['ordertype3']];

    $rows = json_decode(json_encode($rows), true);
    usort($rows, function ($a, $b) use ($sorts) {
        foreach ($sorts as $field => $direction) {
            if ($a[$field] != $b[$field]) {
                if ($direction == 'asc') {
                    return $a[$field] < $b[$field] ? -1 : 1;
                }

                return $a[$field] < $b[$field] ? 1 : -1;
            }
        }

        return 0;
    });
    $rows = json_decode(json_encode($rows), false);

    return $rows;
}

function sort_product_rows($rows)
{

    $rows = json_decode(json_encode($rows), true);

    usort($rows, function ($a, $b) {
        $asort = \DB::table('crm_product_categories')->where('id', $a['product_category_id'])->pluck('sort_order')->first();
        $bsort = \DB::table('crm_product_categories')->where('id', $b['product_category_id'])->pluck('sort_order')->first();

        if ($asort === $bsort) {
            if (empty($a['sort_order'])) {
                $asort = \DB::table('crm_products')->where('id', $a['product_id'])->pluck('sort_order')->first();
                $bsort = \DB::table('crm_products')->where('id', $b['product_id'])->pluck('sort_order')->first();
            } else {
                $asort = $a['sort_order'];
                $bsort = $b['sort_order'];
            }
        }

        return $asort <=> $bsort;
    });
    $rows = json_decode(json_encode($rows), false);

    return $rows;
}

function sort_document_lines($rows)
{
    $rows = json_decode(json_encode($rows), true);

    usort($rows, function ($a, $b) {
        $asort = \DB::table('crm_product_categories')->where('id', $a['product_category_id'])->pluck('sort_order')->first();
        $bsort = \DB::table('crm_product_categories')->where('id', $b['product_category_id'])->pluck('sort_order')->first();

        if ($asort === $bsort) {
            $asort = $a['sort_order'];
            $bsort = $b['sort_order'];
        }

        return $asort <=> $bsort;
    });
    $rows = json_decode(json_encode($rows), false);

    return $rows;
}

function sql_filter_render_module_ids($rows, $record, $field = '')
{
    if ($record['location'] == 'grid_menu') {
        return $rows;
    }

    if (str_contains($record['location'], 'module_menu')) {
        $filtered_rows = [];
        $workboard_module_ids = \DB::table('erp_cruds')->where('is_workspace_module', 1)->pluck('id')->unique()->filter()->toArray();

        foreach ($rows as $row) {
            if (! in_array($row->id, $workboard_module_ids)) {
                continue;
            }
            $filtered_rows[] = $row;
        }

        return $filtered_rows;
    }

    $detail_module_ids = \DB::table('erp_cruds')->where('detail_module_id', '>', 0)->pluck('detail_module_id')->unique()->filter()->toArray();
    $filtered_rows = [];
    foreach ($rows as $row) {
        if (in_array($row->id, $detail_module_ids)) {
            continue;
        }
        $filtered_rows[] = $row;
    }

    return $filtered_rows;
}

function sql_filter_filter_users($rows)
{
    $filtered_rows = [];
    foreach ($rows as $row) {
        if ($row->email != 'rnd@telecloud.co.za') {
            continue;
        }
        $filtered_rows[] = $row;
    }

    return $filtered_rows;
}

function sort_workflow_rows($rows)
{
    $rows = json_decode(json_encode($rows), true);

    usort($rows, function ($a, $b) {
        $amodule = \DB::table('erp_cruds')->where('id', $a['module_id'])->get()->first();
        $bmodule = \DB::table('erp_cruds')->where('id', $b['module_id'])->get()->first();
        $asort = $amodule->package.' - '.$amodule->module_name;
        $bsort = $bmodule->package.' - '.$bmodule->module_name;

        if ($asort === $bsort) {
            $asort = $a['type'];
            $bsort = $b['type'];
        }

        if ($asort === $bsort) {
            $asort = $a['name'];
            $bsort = $b['name'];
        }

        if ($asort === $bsort) {
            $asort = $a['sort'];
            $bsort = $b['sort'];
        }

        return $asort <=> $bsort;
    });
    $rows = json_decode(json_encode($rows), false);

    return $rows;
}

function replace_code_inserts($search, $replace)
{
    //uncomment to replace table AND column names
    $tables = \DB::select('SHOW TABLES');
    foreach ($tables as $table) {
        $table_name = $table->Tables_in_portal;
        if (strpos($table_name, $search) !== false) {
            $new_table_name = str_replace($search, $replace, $table_name);
            \DB::statement('RENAME TABLE '.$table_name.' TO '.$new_table_name);
        }
        $cols = Schema::getColumnListing($table_name);
        foreach ($cols as $col) {
            if ($col == $search) {
                Schema::table($table_name, function ($table) use ($search, $replace) {
                    $table->renameColumn($search, $replace);
                });
            }
        }
    }

    $code_inserts = \DB::table('erp_cruds')->get();
    foreach ($code_inserts as $code) {
        $module_config = \Erp::decode($code->module_config);
        if (! empty($module_config)) {
            array_walk_recursive(
                $module_config,
                function (&$value, $count, $params) {
                    $value = str_replace($params['search'], $params['replace'], $value);
                },
                ['search' => $search, 'replace' => $replace]
            );

            $module_config = \Erp::encode($module_config);
            $module_db = str_replace($search, $replace, $code->module_db);
            \DB::table('erp_cruds')->where('id', $code->module_id)->update(['module_config' => $module_config, 'module_db' => $module_db]);
        }
    }

    \DB::table('erp_cruds')->whereNotNull('beforesave')->update(['beforesave' => DB::raw("REPLACE(beforesave, '".$search."', '".$replace."')")]);
    \DB::table('erp_cruds')->whereNotNull('aftersave')->update(['aftersave' => DB::raw("REPLACE(aftersave, '".$search."', '".$replace."')")]);
    \DB::table('erp_cruds')->whereNotNull('afterdelete')->update(['afterdelete' => DB::raw("REPLACE(afterdelete, '".$search."', '".$replace."')")]);
    \DB::table('erp_cruds')->whereNotNull('indextop')->update(['indextop' => DB::raw("REPLACE(indextop, '".$search."', '".$replace."')")]);
    \DB::table('erp_cruds')->whereNotNull('customfunction')->update(['customfunction' => DB::raw("REPLACE(customfunction, '".$search."', '".$replace."')")]);
    \DB::table('erp_form_events')->whereNotNull('code')->update(['code' => DB::raw("REPLACE(code, '".$search."', '".$replace."')")]);
    \DB::table('erp_form_events')->whereNotNull('helper_functions')->update(['helper_functions' => DB::raw("REPLACE(helper_functions, '".$search."', '".$replace."')")]);
}

function get_toplevel_menu_access($menu_id)
{
    $menu = \DB::table('erp_menu')->where('id', $menu_id)->get()->first();
    $menu_parent_id = $menu->parent_id;
    $parent_id = $menu->id;
    if ($menu_parent_id != 0) {
        while ($menu_parent_id != 0) {
            $menu = \DB::table('erp_menu')->where('id', $menu_parent_id)->get()->first();
            $menu_parent_id = $menu->parent_id;
            $parent_id = $menu->id;
        }
    }

    return \DB::table('erp_menu')->where('id', $parent_id)->pluck('role_access')->first();
}

function get_toplevel_menu_name($menu_id)
{
    $menu = \DB::table('erp_menu')->where('id', $menu_id)->get()->first();
    $menu_parent_id = $menu->parent_id;
    $parent_id = $menu->id;
    if ($menu_parent_id != 0) {
        while ($menu_parent_id != 0) {
            $menu = \DB::table('erp_menu')->where('id', $menu_parent_id)->get()->first();
            $menu_parent_id = $menu->parent_id;
            $parent_id = $menu->id;
        }
    }

    return \DB::table('erp_menu')->where('id', $parent_id)->pluck('menu_name')->first();
}

function get_permission_table($id = '', $type = '')
{
    $menu = \DB::table('erp_menu')->where('id', $id)->get()->first();
    $access_items = [
        'is_menu' => 'Menu',
        'is_view' => 'View',
        'is_add' => 'Create',
        'is_edit' => 'Edit',
        'is_delete' => 'Delete',
    ];

    $access_items = [
        'is_menu' => 'Menu',
    ];

    if ($menu->module_id == 0) {
        $access_items = [
            'is_menu' => 'Show Menu',
        ];
    }

    return $access_items;
}

function get_foreign_keys_from_schema($table)
{
    $foreign_keys = [];
    $schema = \DB::getDoctrineSchemaManager();
    $shemaKeys = $schema->listTableForeignKeys($table);

    foreach ($shemaKeys as $fk) {
        $local_field = $fk->getColumns()[0];
        $foreign_field = $fk->getForeignColumns()[0];
        $foreign_table = $fk->getForeignTableName();
        $foreign_keys[$local_field] = ['table' => $foreign_table, 'key' => $foreign_field];
    }

    return $foreign_keys;
}

function get_all_foreign_keys_from_schema($table, $connection = null)
{
    $foreign_keys = [];

    if (! $connection) {
        $schema = \DB::getDoctrineSchemaManager();
    } else {
        $schema = \DB::connection($connection)->getDoctrineSchemaManager();
    }
    $shemaKeys = $schema->listTableForeignKeys($table);

    foreach ($shemaKeys as $fk) {
        $local_field = $fk->getColumns()[0];
        $foreign_field = $fk->getForeignColumns()[0];
        $foreign_table = $fk->getForeignTableName();
        $foreign_keys[$table.'.'.$local_field] = ['table' => $foreign_table, 'key' => $foreign_field];
        $linked_keys = get_all_foreign_keys_from_schema($foreign_table);
        if (count($linked_keys) > 0 && ! empty($linked_keys)) {
            $foreign_keys = array_merge($foreign_keys, $linked_keys);
        }
    }

    return $foreign_keys;
}

function get_linked_tables_from_schema($table)
{
    $foreign_keys = get_foreign_keys_from_schema($table);
    $tables = [];
    foreach ($foreign_keys as $fk) {
        $tables[] = $fk['table'];
    }

    return $tables;
}

function get_all_linked_tables_from_schema($table)
{
    $all_tables = [];
    $tables = get_linked_tables_from_schema($table);

    foreach ($tables as $table) {
        $all_tables[] = $table;
        $linked_tables = get_all_linked_tables_from_schema($table);
        $all_tables = array_merge($all_tables, $linked_tables);
    }

    return array_unique($all_tables);
}

function get_table_schema($table, $connection = null)
{
    if (! $connection) {
        $schema = \DB::getDoctrineSchemaManager();
    } else {
        $schema = \DB::connection($connection)->getDoctrineSchemaManager();
    }
    $columns = $schema->listTableColumns($table); //get table columns

    $fields = [];
    foreach ($columns as $column) {
        $field_name = $column->getName();
        $type = $column->getType();
        $nullable = false;
        $default = $column->getDefault();

        if ($column->getNotnull() === false && $column->getDefault() === null) {
            $nullable = true;
        }

        $fields[$field_name] = ['type' => $type, 'nullable' => $nullable, 'default' => $default];
    }

    return $fields;
}

function get_nullable_from_schema($table, $connection = null)
{
    if (! $connection) {
        $schema = \DB::getDoctrineSchemaManager();
    } else {
        $schema = \DB::connection($connection)->getDoctrineSchemaManager();
    }
    $columns = $schema->listTableColumns($table); //get table columns

    $fields = [];
    foreach ($columns as $column) {
        if ($column->getNotnull() === false && $column->getDefault() === null) {
            $fields[] = $column->getName();
        }
    }

    return $fields;
}

function get_columns_from_schema($table, $types = null, $connection = null)
{
    if (! $connection) {
        $schema = \DB::getDoctrineSchemaManager();
    } else {
        $schema = \DB::connection($connection)->getDoctrineSchemaManager();
    }
    $columns = $schema->listTableColumns($table); //get table columns

    if (! empty($types) && ! is_array($types)) {
        $types = [$types];
    }
    $fields = [];
    foreach ($columns as $column) { //run loop
        if (empty($types)) {
            $fields[] = $column->getName();
        } else {
            if (in_array($column->getType()->getName(), $types)) {
                $fields[] = $column->getName();
            }
        }
    }

    return $fields;
}

function get_doctrine_column_type($table, $column, $connection = null)
{

    try {
        $table_schema = get_table_schema($table, $connection);

        return $table_schema[$column]['type'];

    } catch (\Throwable $ex) {
        exception_log($ex);
        aa($ex->getMessage());

        return false;
    }
}

function get_column_type($table, $column, $connection = null)
{
    $type = 'text';
    try {
        $table_schema = get_table_schema($table, $connection);
        $field_type = $table_schema[$column]['type'];
        $field_type = strtolower($field_type);

        if (str_contains($field_type, 'double') || str_contains($field_type, 'decimal') || str_contains($field_type, 'float')) {
            $type = 'currency';
        } elseif (str_contains($field_type, 'tinyint') || str_contains($field_type, 'boolean')) {
            $type = 'boolean';
        } elseif (str_contains($field_type, 'int')) {
            $type = 'integer';
        } elseif (str_contains($field_type, 'date')) {
            $type = 'date';
        } elseif (str_contains($field_type, 'datetime')) {
            $type = 'datetime';
        }

    } catch (\Throwable $ex) {
        exception_log($ex);
        aa($ex->getMessage());
    }

    return $type;
}

function get_complete_schema($connection = null)
{
    if (! $connection) {
        $tables = \DB::getDoctrineSchemaManager()->listTableNames();
    } else {
        $tables = \DB::connection($connection)->getDoctrineSchemaManager()->listTableNames();
    }

    $schema = [];

    foreach ($tables as $table) {
        $schema[$table] = get_columns_from_schema($table, null, $connection);
    }

    return $schema;
}

function get_tables_from_schema($connection = null)
{
    if (! $connection) {
        return \DB::getDoctrineSchemaManager()->listTableNames();
    } else {
        return \DB::connection($connection)->getDoctrineSchemaManager()->listTableNames();
    }
}

function view_blend($blend, $data)
{
    return view(['template' => $blend], $data)->render();
}

function get_module_field_options($module_id, $field, $row = false, $from_grid = false)
{

    if (! $row) {
        $row = [];
    }
    $options = [];
    $field = app('erp_config')['module_fields']->where('module_id', $module_id)->where('field', $field)->first();

    $module = app('erp_config')['modules']->where('id', $module_id)->first();
    if ($field->field_type == 'select_module' && $module->connection == 'pbx_cdr') {

        return [];
    }
    if (! $from_grid) {
        if (! str_contains($field->field_type, 'select')) {
            return false;
        }
    }

    if ($field->field_type == 'select_custom') {
        $select_options = explode(',', $field->opts_values);
        $select_options = collect($select_options)->filter()->toArray();
        //if($from_grid){
        //    $select_options = [];
        //}
        //if($module->serverside_model){
        $db_options = \DB::connection($module->connection)->table($module->db_table)->select($field->field)->where($field->field, '>', '')->groupBy($field->field)->pluck($field->field)->filter()->unique()->toArray();

        foreach ($db_options as $db_option) {
            if (! in_array($db_option, $select_options)) {
                if (! empty($db_option)) {
                    $select_options[] = $db_option;
                }
            }
        }
        //}
        array_unshift($select_options, ['']);
        $select_options = array_values($select_options);

        return $select_options;
    }

    if ($field->field_type == 'text') {

        if ($field->alias != $module->db_table) {
            $module = \DB::connection('default')->table('erp_cruds')->where('db_table', $field->alias)->get()->first();
        }
        //$select_options = explode(',', $field->opts_values);
        //$select_options = collect($select_options)->filter()->toArray();
        //if($from_grid){
        //    $select_options = [];
        //}
        $select_options = [];
        $db_options = \DB::connection($module->connection)->table($module->db_table)->select($field->field)->where($field->field, '>', '')->groupBy($field->field)->pluck($field->field)->filter()->unique()->toArray();

        foreach ($db_options as $db_option) {
            if (! in_array($db_option, $select_options)) {
                if (! empty($db_option)) {
                    $select_options[] = $db_option;
                }
            }
        }
        $select_options = array_values($select_options);

        return $select_options;
    }

    if ($field->field_type == 'select_function') {
        $opts_function = $field->opts_function;
        $conn = $module->connection;
        if (! empty(request()->row_id)) {
            $row_module_id = app('erp_config')['module_fields']->where('id', request()->row_id)->pluck('module_id')->first();
            $conn = app('erp_config')['modules']->where('id', $row_module_id)->pluck('connection')->first();
        }

        $arr = $opts_function($row, $conn);

        $datasource = [];
        foreach ($arr as $k => $v) {
            $datasource[] = (object) ['text' => $v, 'value' => (string) $k];
        }

        return collect($datasource);
    }

    if ($field->field_type == 'select_connections') {
        $conns = get_db_connections();

        foreach ($conns as $conn) {
            $datasource[] = (object) ['text' => $conn, 'value' => (string) $conn];
        }

        return collect($datasource);
    }

    if ($field->field_type == 'select_tables') {
        if ($dependant_val) {
            $tables = get_tables_from_schema($dependant_val);
        } elseif (! empty($row) && ! empty($row['connection'])) {
            $tables = get_tables_from_schema($row['connection']);
        } else {
            $tables = get_tables_from_schema();
        }
        foreach ($tables as $table) {
            $datasource[] = (object) ['text' => $table, 'value' => (string) $table];
        }

        return collect($datasource);
    }

    if ($field->field_type == 'select_module') {

        if (session('instance') && session('instance')->id && empty(request()->{$field->field})) {
            if ($from_grid) {
                $from_grid_cache = ($from_grid) ? 'grid' : 'form';
                $datasource = Cache::get('select_options'.$from_grid_cache.$field->id.session('instance')->id);
                if ($field->field != 'account_id') {
                    if ($datasource) {
                        return $datasource;
                    }
                }
            }

        }

        $conn = app('erp_config')['modules']->where('id', $module_id)->pluck('connection')->first();
        $display_values = explode(',', $field->opt_db_display);
        $tables = get_tables_from_schema($conn);
        if (! in_array($field->opt_db_table, $tables)) {
            return false;
        }
        $select_query = \DB::connection($conn)->table($field->opt_db_table);
        $select_fields = $display_values;
        $select_fields[] = $field->opt_db_key;

        $select_query->select($select_fields);

        if (empty($row['currency']) && str_contains($field->opt_db_where, 'currency =')) {
            $field->opt_db_where = '';
        }
        if ($field->opt_db_where) {
            $add_where = 0;
            if ($from_grid && empty(request()->{$field->field})) {
                // if(!str_contains($field->opt_db_where, 'is_deleted') && !str_contains($field->opt_db_where, 'status')){
                $add_where = 1;
                //}
            }
            if (! $from_grid) {
                $add_where = 1;
            }

            if ($add_where) {
                $where = $field->opt_db_where;
                if ($from_grid && str_contains($field->opt_db_where, '{{$partner_id}}')) {
                    return 'formonly';
                }
                if (empty($row['module_id'])) {
                    $row['module_id'] = 0;
                }
                $where = view(['template' => $where])->with($row)->render();

                $where = str_replace('[module_id]', $module_id, $where);
                foreach (session()->all() as $k => $v) {
                    if (! is_object($v)) {
                        if ($field == 'sms_list_id' && $v == 1) {
                            $v = 12;
                        }
                        $where = str_replace('[session_'.$k.']', json_encode($v), $where);
                    }
                }

                $select_query->whereRaw($where);
            }
        }

        if (! $from_grid) {
            if ($field->opt_db_table != 'v_gateways') {
                if (\Schema::connection($conn)->hasColumn($field->opt_db_table, 'is_deleted')) {
                    $select_query->where('is_deleted', 0);

                } elseif (\Schema::connection($conn)->hasColumn($field->opt_db_table, 'status')) {
                    $select_query->where('status', '!=', 'Deleted');

                }
            }
        }
        /*
        // limit filter options to grid data
        if ($from_grid) {
            if (\Schema::connection($module->connection)->hasColumn($module->db_table, $field->field)) {
                $foreign_ids = \DB::connection($module->connection)->table($module->db_table)->select($field->field)->groupBy($field->field)->pluck($field->field)->unique()->filter()->toArray();
                if(!empty(request()->{$field->field})){
                    $foreign_ids[] = request()->{$field->field};
                }
                $select_query->whereIn($field->opt_db_key, $foreign_ids);
            }
        }
        */

        if ($from_grid && isset($row[$field->field])) {
            $select_query->where($field->opt_db_key, $row[$field->field]);
        }

        $filter_val = session('account_id');
        if ($field->opt_db_table == 'crm_pricelists') {
            $columns = get_columns_from_schema($field->opt_db_table, null, $conn);
            if (in_array('partner_id', $columns) && session('role_id') == 11) {
                $select_query->where('partner_id', $filter_val);
            }
        } else {
            if (! empty(session('sms_account_id'))) {
                $filter_val = session('sms_account_id');
            } else {
                $filter_val = session('account_id');
            }
            $columns = get_columns_from_schema($field->opt_db_table, null, $conn);
            if (in_array('partner_id', $columns) && session('role_id') == 11) {
                $select_query->where('partner_id', $filter_val);
            } elseif (in_array('account_id', $columns) && session('role_level') != 'Admin') {
                $select_query->where('account_id', $filter_val);
            }
        }

        $db_sort = explode(',', $field->opt_db_sortorder);
        if (! empty($db_sort) && count($db_sort) > 0) {
            $orderbys = $db_sort;
            foreach ($orderbys as $sort) {
                if (! empty($sort)) {
                    $select_query->orderby(trim($sort));
                }
            }
        } elseif (! empty($display_values[0])) {
            $select_query->orderby($display_values[0]);
        }

        //$select_list = $select_query->get();

        $select_list = $select_query->get();
        if (! empty($field->opt_db_filter_function)) {
            $filter_function = \DB::table('erp_form_events')->where('id', $field->opt_db_filter_function)->pluck('function_name')->first();
            if ($filter_function && function_exists($filter_function)) {
                //aa($select_list);
                //aa($row);
                $select_list = $filter_function($select_list, $row);
            }
        }

        $datasource = [];
        if (! in_array($field->opt_db_table, ['crm_accounts'])) {
            $datasource[] = (object) ['text' => '', 'value' => 'None'];
        }
        foreach ($select_list as $list_item) {
            $option_label = '';
            foreach ($display_values as $display_value) {
                $field_type = null;

                $join_module_id = app('erp_config')['modules']->where('db_table', $field->opt_db_table)->pluck('id')->first();
                if ($join_module_id) {
                    $field_type = app('erp_config')['module_fields']->where('module_id', $join_module_id)->where('field', $display_value)->pluck('field_type')->first();
                }

                $list_label = $list_item->{$display_value};

                if ($display_value == 'account_id') {
                    $account = dbgetaccount($list_label);
                    $list_label = $account->company;
                } elseif (str_ends_with($display_value, '_id')) {
                    $list_label = get_module_field_join_display($field->opt_db_table, $display_value, $list_label);
                } elseif ($field_type == 'select_module') {
                    $list_label = get_module_field_join_display($field->opt_db_table, $display_value, $list_label);

                }
                $option_label .= $list_label.' - ';
            }
            //$option_label = rtrim($option_label, ' - ');

            $option_label = str_replace_last(' - ', '', $option_label);
            // $option_label =rtrim($option_label, ' - ');
            //$option_label = rtrim($option_label);
            if ($field->opt_db_table == 'erp_cruds') {
                $option_label = ucwords(str_replace('_', ' ', $option_label));
            }
            $datasource[] = (object) ['text' => trim($option_label), 'value' => (string) $list_item->{$field->opt_db_key}];

        }

        if (session('instance') && session('instance')->id) {
            if ($from_grid) {
                $from_grid_cache = ($from_grid) ? 'grid' : 'form';
                Cache::put('select_options'.$from_grid_cache.$field->id.session('instance')->id, collect($datasource));
            }
        }

        return collect($datasource);

    }

    return $options;
}

function get_module_field_join_display($table, $field, $val)
{

    $grid_field = app('erp_config')['module_fields']->where('field_type', 'select_module')->where('field', $field)->where('alias', $table)->first();

    if ($grid_field->opt_db_display && $grid_field->opt_db_table) {
        $select_fields = explode(',', $grid_field->opt_db_display);

        $query = \DB::table($grid_field->opt_db_table);
        $query->select($select_fields);
        $query->where($grid_field->opt_db_key, $val);
        $result = $query->get()->first();

        if (count($select_fields) == 1) {

            return $result->{$select_fields[0]};
        } else {

            foreach ($select_fields as $select_field) {

                $formatted_val .= $result->{$select_field}.' - ';
            }

            $formatted_val = str_replace_last(' - ', '', $formatted_val);

            return $formatted_val;
        }
    }

    return (string) $val;
}

function isIframeDisabled($src)
{
    try {
        $headers = get_headers($src, 1);
        $headers = array_change_key_case($headers, CASE_LOWER);
        // Check Content-Security-Policy
        if (! empty($headers['content-security-policy'])) {
            return true;
        }
        // Check X-Frame-Options
        if (! empty($headers['x-frame-options'])) {
            $x_options = strtoupper($headers['x-frame-options']);
            if ($x_options == 'ALLOW' || $x_options == strtoupper('ALLOW-FROM http://portal.telecloud.co.za/')) {
                return false;
            } else {
                return true;
            }
        }
    } catch (Exception $ex) {
        // Ignore error
    }

    return false;
}

function is_account_active()
{
    if (session('role_level') == 'Admin') {
        return true;
    }

    if (check_access('11')) {
        return is_partner_active(session('account_id'));
    } else {
        return is_customer_active(session('account_id'));
    }
}

function module_access_subscriptions($module_name = '')
{
    if (check_access('21')) {
        if (str_contains($module_name, 'sms')) {
            $sms_subscription = \DB::connection('default')->table('sub_services')
                ->where('account_id', session('account_id'))->where('status', '!=', 'Deleted')->where('provision_type', 'LIKE', '%sms%')
                ->count();

            if (! $sms_subscription) {
                return 0;
            }
        }
    }

    return 1;
}

function menu_access_inactive_account($module_name = '')
{
    return 1;
    $allowed_access = 1;
    if (! empty($module_name) && ! is_account_active()) {
        $allowed_menus = \DB::table('erp_menu')
            ->join('erp_menu_role_access', 'erp_menu.id', '=', 'erp_menu_role_access.id')
            ->where('erp_menu_role_access.role_id', session('role_id'))
            ->pluck('erp_menu.menu_name')
            ->toArray();

        if (! in_array($module_name, $allowed_menus)) {
            $allowed_access = 0;
        }
    }

    return $allowed_access;
}

function generate_import_sample($module_id)
{
    $form_configs = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->whereIn('visible', ['Add and Edit', 'Add'])->get();

    $file_title = 'Import Sample';

    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    $excel_list = [];

    $gateway_rates_fields = ['destination_id', 'country', 'destination', 'admin_rate'];
    $row = [];
    foreach ($form_configs as $form_config) {
        if ($form_config->field == 'id') {
            continue;
        }
        if ($module_id == 646) {
            if (in_array($form_config->field, $gateway_rates_fields)) {
                $row[$form_config->field] = '';
            }
        } else {
            $row[$form_config->field] = '';
        }
    }
    $excel_list[] = $row;

    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

    return $file_name;
}

function update_module_config_from_schema($module_id = false, $table = false)
{
    $current_conn = \DB::getDefaultConnection();

    set_db_connection('default');
    if (! is_dev()) {
        if (! check_access('1,34')) {
            set_db_connection($current_conn);

            return true;
        }
    }

    if (! empty($module_id)) {
        $ids = [$module_id];
    }

    if (! empty($table)) {
        $ids = \DB::table('erp_cruds')->where('db_table', $table)->pluck('id')->toArray();
    }

    if (empty($ids) || (is_array($ids) && count($ids) == 0)) {
        set_db_connection($current_conn);

        return false;
    }

    foreach ($ids as $id) {
        $module_ids = \DB::connection('default')->table('erp_cruds')->pluck('id')->toArray();
        \DB::connection('default')->table('erp_module_fields')->whereNotIn('module_id', $module_ids)->delete();
        $module = \DB::table('erp_cruds')->where('id', $id)->get()->first();

        $grid_fields = App\Models\ModuleManager::getFields($id);

        $db_fields = get_columns_from_schema($module->db_table, null, $module->connection);

        $main_instance = is_main_instance();

        if (! empty($module->db_sql) || ! empty($module->sql_function)) {
            if (! empty($module->sql_function)) {
                $function = $module->sql_function;

                $sql = $function();

                $db_columns = App\Models\ModuleManager::getTableFieldsFromSQL($id, $sql);
            } else {
                $db_columns = App\Models\ModuleManager::getTableFieldsFromSQL($id, $module->db_sql);
            }
            if (! empty($db_columns) && is_array($db_columns) && count($db_columns) > 0) {
                foreach ($db_columns as $db_column) {
                    if (in_array($db_column, $db_fields)) {
                        \DB::connection('default')->table('erp_module_fields')->where('module_id', $id)->where('field', $db_column)->update(['alias' => $module->db_table]);
                    } else {
                        // get db alias from sql
                        $mod_sql = str_replace(',', ', ', $module->db_sql);
                        $sql_arr = explode(' ', $mod_sql);
                        foreach ($sql_arr as $sql_txt) {
                            if (str_contains($sql_txt, '.'.$db_column)) {
                                $join_table_arr = explode('.', $sql_txt);
                                $alias_table = trim(preg_replace('/\s\s+/', '', $join_table_arr[0]));

                                \DB::connection('default')->table('erp_module_fields')->where('module_id', $id)->where('field', $db_column)->update(['alias' => $alias_table]);
                            }
                        }
                    }
                }
            }
        }

        if (empty($db_columns)) {
            $db_columns = App\Models\ModuleManager::getTableFields($id, $module->db_table);
        }
        if (! $main_instance) {
            $app_ids = get_installed_app_ids();
        }
        // add new fields
        foreach ($db_columns as $field) {
            if (! $main_instance) {
            }
            $label = ucwords(str_replace('_', ' ', $field));
            if (str_contains($label, ' Id')) {
                $label = str_replace(' Id', '', $label);
            }
            $field_data = [
                'module_id' => $id,
                'field' => $field,
                'label' => $label,
                'visible' => 'Add and Edit',
                'type' => get_column_type($module->db_table, $field, $module->connection),
                'sort_order' => 1000,
            ];

            if (! in_array($field, $grid_fields)) {

                App\Models\ModuleManager::saveField($field_data, $module->db_table);
            }
        }

        // delete removed grid fields
        foreach ($grid_fields as $field) {
            if (! in_array($field, $db_columns)) {

                $aliased_field = \DB::table('erp_module_fields')->where('module_id', $id)->where('field', $field)->where('aliased_field', 1)->count();
                if (! $aliased_field) {
                    App\Models\ModuleManager::deleteField($id, $field);
                }
            }
        }
        \DB::table('erp_module_fields')->where('module_id', $id)->where('alias', '')->update(['alias' => $module->db_table]);

        $boolean_fields = get_columns_from_schema($module->db_table, 'boolean', $module->connection);
        if (! empty($boolean_fields) && is_array($boolean_fields) && count($boolean_fields) > 0) {
            \DB::table('erp_module_fields')->where('module_id', $id)->whereIn('field', $boolean_fields)->where('field_type', 'text')->update(['field_type' => 'boolean']);
        }

        $integer_fields = get_columns_from_schema($module->db_table, 'integer', $module->connection);
        if (! empty($integer_fields) && is_array($integer_fields) && count($integer_fields) > 0) {
            \DB::table('erp_module_fields')->where('module_id', $id)->whereIn('field', $integer_fields)->where('field_type', 'text')->update(['field_type' => 'integer']);
        }

        $decimal_fields = get_columns_from_schema($module->db_table, ['float', 'double', 'decimal'], $module->connection);
        if (! empty($decimal_fields) && is_array($decimal_fields) && count($decimal_fields) > 0) {
            \DB::table('erp_module_fields')->where('module_id', $id)->whereIn('field', $decimal_fields)->where('field_type', 'text')->update(['field_type' => 'decimal']);
        }

        $date_fields = get_columns_from_schema($module->db_table, 'date', $module->connection);
        if (! empty($date_fields) && is_array($date_fields) && count($date_fields) > 0) {
            \DB::table('erp_module_fields')->where('module_id', $id)->whereIn('field', $date_fields)->where('field_type', 'text')->update(['field_type' => 'date']);
        }

        $date_fields = get_columns_from_schema($module->db_table, 'datetime', $module->connection);
        if (! empty($date_fields) && is_array($date_fields) && count($date_fields) > 0) {
            \DB::table('erp_module_fields')->where('module_id', $id)->whereIn('field', $date_fields)->where('field_type', 'text')->update(['field_type' => 'datetime']);
        }
        if (! str_contains($module->db_sql, 'UNION ALL')) {
            \DB::table('erp_module_fields')->where('module_id', $id)->where('alias', $module->db_table)->update(['aliased_field' => 0]);
            $db_fields = get_columns_from_schema($module->db_table, null, $module->connection);

            \DB::table('erp_module_fields')->where('module_id', $id)->where('alias', $module->db_table)->whereNotIn('field', $db_fields)->update(['aliased_field' => 1]);
        }
    }

    set_db_connection($current_conn);
}

function generate_api_routes()
{
    \DB::table('erp_cruds')->update(['api_route' => \DB::raw('slug')]);
}
