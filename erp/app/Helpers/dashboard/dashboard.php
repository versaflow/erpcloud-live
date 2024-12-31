<?php

function get_chart_data($layout_id, $instance_id = 1)
{

    try {
        if (session['role_level'] == 'Admin' && empty(session('pbx_account_id'))) {
            session(['pbx_account_id' => 1]);
        }

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
            return [];
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
        $sortModel = [];
        if (! empty($layout_state) && ! empty($layout_state->colState)) {

            foreach ($layout_state->colState as $col) {
                $col = (array) $col;
                if ($col['sortIndex'] !== '' && $col['colId'] != 'ag-Grid-AutoColumn') {

                    $sortModel[$col['sortIndex']] = [
                        'sort' => $col['sort'],
                        'colId' => $col['colId'],
                    ];
                }
            }
        }

        if (empty($layout_state->filterState)) {
            $filter_state = [];
        } else {
            $filter_state = (array) json_decode(json_encode($layout_state->filterState), true);
        }

        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');
        $request_object->request->add(['dashboard_tracking' => 1]);
        //$request_object->request->add(['layout_tracking' => 1]);
        $request_object->request->add(['rowGroupCols' => []]);
        $request_object->request->add(['valueCols' => []]);
        $request_object->request->add(['groupKeys' => []]);

        if (count($sortModel) > 0) {
            ksort($sortModel);
            $request_object->request->add(['sortModel' => $sortModel]);
        }

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

        $y_field = '';
        $result = [];
        // if($layout->layout_type == 'Report'){

        $x_field = $group_cols->pluck('colId')->first();
        $y_field = $layout->sum_field;

        $valueCols = [];
        if ($y_field) {
            $pivot_state = json_decode($layout->aggrid_pivot_state);

            if (! empty($pivot_state) && ! empty($pivot_state->colState)) {
                foreach ($pivot_state->colState as $colState) {

                    if ($y_field == $colState->colId) {

                        $request_object->request->add(['result_field_agg_func' => $colState->aggFunc]);
                        $request_object->request->add(['result_field' => $y_field]);
                        $valueCols = [[
                            'id' => $colState->colId,
                            'aggFunc' => $colState->aggFunc,
                            'displayName' => $colState->colId,
                            'field' => $colState->colId,
                        ]];
                    }
                }
            }
        }

        //if(is_dev())
        //$request_object->request->add(['return_sql' => 1]);
        $request_object->request->add(['return_all_rows' => 1]);
        $request_object->request->add(['valueCols' => $valueCols]);

        if ($layout->widget_type == 'Stacked Column') {
            $pivot_col = collect($layout_state->colState)->where('pivot', 'true')->pluck('colId')->first();
            if ($pivot_col) {
                $request_object->request->add(['pivot_col' => $pivot_col]);
            }
            $data = $model->getData($request_object);
            $data = collect($data['rows'])->groupBy($x_field);
            $series = [];
            foreach ($data as $group => $totals) {
                $datasource = [];
                foreach ($totals as $row) {
                    $chart_row = ['x' => $row->{$pivot_col}, 'y' => currency(abs($row->{$y_field})), 'text' => $row->{$pivot_col}.' '.currency(abs($row->{$y_field}))];
                    if ($row->{$y_field} < 0) {
                        // $chart_row['x'] .= ' NEGATIVE';
                        $chart_row['text'] .= ' NEGATIVE';
                    }
                    $datasource[] = $chart_row;
                }
                $series_row = (object) [
                    'type' => 'StackingColumn',
                    'name' => $group,
                    'xName' => 'x',
                    'yName' => 'y',
                    'width' => 2,
                    'dataSource' => $datasource,
                    'columnWidth' => 0.6,
                    'border' => (object) ['width' => 1, 'color' => 'white'],
                ];
                $series[] = $series_row;
            }

            $series = array_reverse($series);
            $result = $series;

        }

        if ($layout->widget_type == 'Line') {
            $data = $model->getData($request_object);

            $chart_data = [];
            if ($layout->widget_type == 'Funnel') {
                $data['rows'] = collect($data['rows'])->take(10);
            }
            foreach ($data['rows'] as $row) {
                $label = $row->{$x_field};

                $chart_row = ['x' => $row->{$x_field}, 'y' => currency(abs($row->{$y_field})), 'text' => $row->{$x_field}.' '.currency(abs($row->{$y_field}))];
                if ($row->{$y_field} < 0) {
                    $chart_row['x'] .= ' NEGATIVE';
                    $chart_row['text'] .= ' NEGATIVE';
                }
                $chart_data[] = $chart_row;

            }
            if ($layout->widget_type == 'Funnel') {
                $chart_data = array_reverse($chart_data);
            }

            //$chart_data = collect($chart_data)->sortBy('y')->toArray();
            //if($layout_id == 2267)
            //dd($chart_data,$data,$x_field,$y_field,$group_cols,$rowGroupCols);

            $result = $chart_data;

        }

        if ($layout->widget_type == 'Pyramid' || $layout->widget_type == 'Donut' || $layout->widget_type == 'Funnel') {
            $data = $model->getData($request_object);

            $chart_data = [];
            if ($layout->widget_type == 'Funnel') {
                $data['rows'] = collect($data['rows'])->take(10);
            }
            foreach ($data['rows'] as $row) {
                $label = $row->{$x_field};

                $chart_row = ['x' => $row->{$x_field}, 'y' => currency(abs($row->{$y_field})), 'text' => $row->{$x_field}.' '.currency(abs($row->{$y_field}))];
                if ($row->{$y_field} < 0) {
                    $chart_row['x'] .= ' NEGATIVE';
                    $chart_row['text'] .= ' NEGATIVE';
                }
                $chart_data[] = $chart_row;

            }
            if ($layout->widget_type == 'Funnel') {
                $chart_data = array_reverse($chart_data);
            }

            //$chart_data = collect($chart_data)->sortBy('y')->toArray();
            //if($layout_id == 2267)
            //dd($chart_data,$data,$x_field,$y_field,$group_cols,$rowGroupCols);

            $result = $chart_data;
        }
        //dddd(11);
        if ($layout->widget_type == 'Speedometer') {
            //dddd(1);
            if (! $y_field) {
                $data = $model->getData($request_object);
                //if(is_dev()){dd(11,$data);}
                $total = $data['lastRow'];
            } else {
                $data = $model->getRowTotals($request_object);
                //if(is_dev()){dd($y_field,$data,$data[0]->{$y_field});}
                $total = $data[0]->{$y_field};
            }
            $target = $layout->target;
            if ($target == 0) {
                $percentage = 100;
            } else {
                $percentage = intval(($total / $target) * 100);
            }
            $chart_data = [
                'total' => intval($total),
                'target' => $target,
                'percentage' => $percentage,
            ];

            $result = $chart_data;
        }
        // }

        // if(is_dev())
        //if(session('instance')->id == 2 ){
        // }

        //  if(is_dev()){
        //     }
        set_db_connection('default');

        return $result;
    } catch (\Throwable $ex) {

        //if(is_dev()){
        //}
        // if(is_dev())
        //dd($ex->getMessage(),$ex->getTraceAsString(),$sortModel,$layout,$y_field);

        set_db_connection('default');

        return [];
    }

}
