<?php

function beforesave_layout_name_unique($request)
{
    $exists = 0;
    if ($request->new_record) {
        $exists = \DB::table('erp_grid_views')->where('name', $request->name)->Where('is_deleted', 0)->where('module_id', $request->module_id)->count();
    } else {
        $exists = \DB::table('erp_grid_views')->where('id', '!=', $request->id)->Where('is_deleted', 0)->where('name', $request->name)->where('module_id', $request->module_id)->count();
    }
    if ($exists) {
        return 'Layout name already in use';
    }
}
function beforedelete_layout_check_default($request)
{
    $default = DB::table('erp_grid_views')->where('id', $request->id)->where('global_default', 1)->count();
    if ($default) {
        return 'Default Layout cannot be deleted';
    }
}

function schedule_export_layouts()
{
    $layouts = \DB::table('erp_grid_views')->where('export_layout_frequency', '!=', 'None')->get();
    $first_week_day = date('Y-m-d', strtotime('monday this week'));

    foreach ($layouts as $layout) {
        $frequency = $layout->export_layout_frequency;
        if (date('Y-m-d') != date('Y-m-01') && $frequency == 'Monthly') {
            continue;
        }
        if (date('Y-m-d') != $first_week_day && $frequency == 'Weekly') {
            continue;
        }
        export_layout($layout->id);
    }
}

function export_layout($layout_id)
{
    if (! is_dev()) {
        return false;
    }
    if (! in_array(session('instance')->id, [1, 2, 11])) {
        return false;
    }
    $layout = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();

    $module = \DB::table('erp_cruds')->where('id', $layout->module_id)->get()->first();
    $total_fields = \DB::table('erp_module_fields')->where('module_id', $layout->module_id)->whereIn('field_type', ['currency', 'decimal', 'integer'])->pluck('label')->toArray();
    $model = new \App\Models\ErpModel;
    $model->setModelData($layout->module_id);

    $grid_data = $model->info;
    $layout_state = json_decode($layout->aggrid_state);
    if (empty($layout_state->colState)) {
        return json_alert('Layout state not set. Save layout and try again.', 'warning');
    }
    if (empty($layout_state->filterState)) {
        $filter_state = [];
    } else {
        $filter_state = (array) json_decode(json_encode($layout_state->filterState), true);
    }

    if ($layout->module_id == 1888) {
        $billing_id = \DB::table('acc_billing')->where('billing_type', 'Monthly')->orderBy('id', 'desc')->pluck('id')->first();
        $filter_state['billing_id'] = [
            'filterType' => 'number',
            'type' => 'equals',
            'filter' => $billing_id,
        ];
    }

    $sortModel = [];
    $sort_fields = collect($layout_state->colState);

    $sort_fields = collect($layout_state->colState)->where('sortIndex', '!=', '')->sortBy('sortIndex');
    if (! empty($sort_fields) && count($sort_fields) > 0) {
        foreach ($sort_fields as $col) {
            if ($col->sortIndex != '') {
                $sortModel[] = [
                    'sort' => $col->sort,
                    'colId' => $col->colId,
                ];
            }
        }
    }

    $request_object = new \Illuminate\Http\Request;
    $request_object->setMethod('POST');
    $request_object->request->add(['return_all_rows' => 1]);
    $request_object->request->add(['startRow' => 0]);
    $request_object->request->add(['endRow' => 100000]);
    $request_object->request->add(['rowGroupCols' => []]);
    $request_object->request->add(['valueCols' => []]);
    $request_object->request->add(['groupKeys' => []]);
    $request_object->request->add(['sortModel' => $sortModel]);
    if ($filter_state) {
        $request_object->request->add(['filterModel' => $filter_state]);
    } else {
        $request_object->request->add(['filterModel' => []]);
    }
    if (! empty($layout_state->searchtext) && $layout_state->searchtext != ' ') {
        $request_object->request->add(['search' => $layout_state->searchtext]);
    }

    $sql_data = $model->getData($request_object);

    $grid = new \ErpGrid($grid_data);
    $rows = $grid->formatAgGridData($sql_data['rows']);

    // format data for export
    $excel_data = [];
    $module_fields = collect($grid_data['module_fields']);
    if ($layout_state->colState) {
        foreach ($layout_state->colState as $col) {
            if ($col->hide == 'false') {
                foreach ($rows as $i => $excel_row) {
                    foreach ($excel_row as $k => $v) {
                        if ($k == $col->colId) {
                            $m_field = str_replace('join_', '', $k);
                            $label = $module_fields->where('field', $m_field)->pluck('label')->first();
                            $field_type = $module_fields->where('field', $m_field)->pluck('field_type')->first();
                            if ($field_type == 'boolean' && $v) {
                                $v = 'Yes';
                            }
                            if ($field_type == 'boolean' && ! $v) {
                                $v = 'No';
                            }
                            $excel_data[$i][$label] = $v;
                        }
                    }
                }
            }
        }
    }

    $file_title = $layout->name;
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $export = new App\Exports\CollectionExport;
    $export->setTotalFields($total_fields);
    $export->setData($excel_data);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
    $file_path = attachments_path().$file_name;
    $url = get_menu_url_from_module_id($layout->module_id);
    $data['layout_link'] = '<a href="https://'.session('instance')->domain_name.'/'.$url.'?layout_id='.$layout_id.'">Click to open layout</a>';
    $data['internal_function'] = 'layout_export';
    $data['test_debug'] = 1;
    $data['attachments'][] = $file_name;
    $data['file_name'] = str_replace('.xlsx', '', $file_name);
    $data['module_name'] = $module->name;
    $data['layout_name'] = $layout->name;
    $data['instance_name'] = session('instance')->name;
    // $data['force_to_email'] = 'ahmed@telecloud.co.za';

    // $data['test_debug'] = 1;
    erp_process_notification(1, $data);
}

function generate_all_records_layout($module_id)
{
    $exists = \DB::table('erp_grid_views')->where('module_id', $module_id)->where('name', 'All Records')->count();
    if (! $exists) {
        $data = [
            'group' => 'Default',
            'module_id' => $module_id,
            'name' => 'All Records',
            'global_default' => 0,
            'system_layout' => 1,
            'sort_order' => 0,
        ];

        $default_layout = \DB::table('erp_grid_views')->where('module_id', $module_id)->where('global_default', 1)->pluck('aggrid_state')->first();
        if ($default_layout > '') {
            $layout = json_decode($default_layout);
        } else {
            $layout = (object) [];
        }
        $layout->filterState = [];

        $status_field = \DB::table('erp_module_fields')->where('module_id', $module_id)->where('field', 'status')->get()->first();
        if ($status_field && $status_field->id) {
            $field = $status_field->field;
            if ($status_field->field_type == 'select_custom') {
                $opt_vals = explode(',', $status_field->opts_values);
                $values = array_diff($opt_vals, ['Deleted']);

                $layout->filterState[$field] = ['values' => $values, 'filterType' => 'set'];
            } else {
                $layout->filterState[$field] = ['filterType' => 'text', 'type' => 'notEqual', 'filter' => 'Deleted'];
            }
            $data['aggrid_state'] = json_encode($layout);
        }
        $layout_id = \DB::table('erp_grid_views')->insertGetId($data);
        \DB::table('erp_grid_views')->where('module_id', $module_id)->where('id', '!=', $layout_id)->update(['global_default' => 0]);
    } else {
        $layout_id = \DB::table('erp_grid_views')->where('module_id', $module_id)->where('name', 'All Records')->pluck('id')->first();
    }

    return $layout_id;
}

// EVENT FUNCTIONS
function aftersave_grid_views_default_permission($request)
{
    $grid_view = \DB::table('erp_grid_views')->where('id', $request->id)->get()->first();

    if ($grid_view->global_default) {
        \DB::table('erp_grid_views')
            ->where('id', '!=', $request->id)
            ->where('module_id', $grid_view->module_id)
            ->where('global_default', 1)
            ->update(['global_default' => 0]);
    }
}

function afterdelete_grid_views_set_default($request)
{
    $default_set = \DB::table('erp_grid_views')
        ->where('module_id', $request->module_id)
        ->where('global_default', 1)
        ->count();
    if (! $default_set) {
        \DB::table('erp_grid_views')
            ->where('module_id', $request->module_id)
            ->where('name', 'All Records')
            ->update(['global_default' => 1]);
    }

    /*
        $module_ids = \DB::table('erp_grid_views')->pluck('module_id')->unique()->toArray();
    foreach($module_ids as $mid){
 $default_set = \DB::table('erp_grid_views')
        ->where('module_id', $mid)
        ->where('global_default', 1)
        ->count();
    if(!$default_set){
    \DB::table('erp_grid_views')
        ->where('module_id', $mid)
        ->where('name', 'All Records')
        ->update(['global_default' => 1]);
    }
    }
    */
}

function afterdelete_grid_views_remove_process($request)
{
    \DB::connection('system')->table('crm_staff_tasks')->where('layout_id', $request->id)->where('instance_id', session('instance')->id)->update(['is_deleted' => 1]);
}

function remove_timestamp_fields_from_layouts($layout_id = false)
{
    if ($layout_id) {
        $layouts = \DB::table('erp_grid_views')->where('id', $layout_id)->where('aggrid_state', 'like', '%created_at%')->get();
    } else {
        $layouts = \DB::table('erp_grid_views')->where('aggrid_state', 'like', '%created_at%')->get();
    }

    foreach ($layouts as $l) {
        $state = json_decode($l->aggrid_state);

        if ($state->colState && is_array($state->colState) && count($state->colState) > 0) {
            foreach ($state->colState as $i => $col) {
                if (in_array($col->colId, ['created_at', 'created_by', 'updated_at', 'updated_by'])) {
                    if (! isset($state->filterState->{$col->colId})) {
                        $state->colState[$i]->hide = true;
                    }
                }
            }
        }
        $aggrid_state = json_encode($state);
        \DB::table('erp_grid_views')->where('id', $l->id)->update(['aggrid_state' => $aggrid_state]);
    }
}
