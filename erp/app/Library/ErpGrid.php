<?php

class ErpGrid
{
    protected $data;

    public function __construct($data, $params = [])
    {
        $this->data = $data;
        $this->params = $params;

        if (isset($this->data['module_fields'][0]['sort_order'])) {
            usort($this->data['module_fields'], [$this, 'sortGrid']);
        }
    }

    public function sortGrid($a, $b)
    {
        if (isset($b['sort_order'])) {
            return strnatcmp($a['sort_order'], $b['sort_order']);
        }
    }

    public function sortGridView($a, $b)
    {
        if (! $a->visible && ! $b->visible && isset($b->sort_order)) {
            return strnatcmp($a->sort_order, $b->sort_order);
        }
    }

    public function getFields()
    {
        return $this->data['module_fields'];
    }

    public function setGridReference($reference)
    {

        $this->data['grid_id'] = $reference;
    }

    public function getColumns()
    {
        $remove_tax_fields = get_admin_setting('remove_tax_fields');

        $tabs = $this->data['db_module_fields']->sortBy('sort_order')->pluck('tab')->unique()->toArray();
        $has_sort_field = $this->data['db_module_fields']->where('field', 'sort_order')->count();
        $has_document_currency_field = $this->data['db_module_fields']->where('field', 'document_currency')->count();
        $has_currency_field = $this->data['db_module_fields']->where('field', 'currency')->count();
        $tab_groups = [];
        $all_columns = [];
        foreach ($tabs as $tab) {
            $columns = [];
            foreach ($this->data['module_fields'] as $i => $field) {
                if ($field['tab'] != $tab) {
                    continue;
                }
                $styles = $this->data['module_styles'];
                $cell_styles = $styles->where('field_id', $field['id'])->where('whole_row', 0);
                $row_styles = $styles->where('field_id', $field['id'])->where('whole_row', 1);

                $filter_datasource = null;
                if ($field['field_type'] == 'none') {
                    continue;
                }
                if ($field['hide_column']) {
                    continue;
                }

                //if(in_array($field['field'],['created_at','created_by','updated_at','updated_by'])){
                //continue;
                // }

                if ($remove_tax_fields && in_array($field['field'], ['wholesale_price_tax', 'tax'])) {
                    continue;
                }
                $column = null;
                $field_show = $this->gridAccess($field);

                if ($field_show) {
                    $column = [
                        'field' => $field['field'],
                        // 'clipMode' => 'EllipsisWithTooltip',
                        'sortable' => true,
                        'db_field' => $field['field'],
                        'tooltipField' => $field['field'],
                        'headerName' => $field['label'],
                        'type' => 'defaultField',
                        'resizable' => true,
                        'enablePivot' => true,
                        'dbid' => $field['id'],
                        'dbtype' => $field['field_type'],
                        'headerTooltip' => ($field['tooltip']) ? $field['tooltip'] : $field['label'],
                        'flex' => 1,
                        'pinned_row_total' => $field['pinned_row_total'],
                        'floatingFilter' => false,
                        'cellClass' => '',
                    ];

                    if (session('role_level') == 'Admin') {
                        // aa($this->data['inline_editing']);
                        if ((is_dev() && $this->data['inline_editing']) || $field['cell_editing']) {
                            $level_access = $this->formAccess($field);

                            if ($level_access) {

                                $column['editable'] = true;
                                //if(str_contains($field['field_type'],'select')){

                                $column['cellEditor'] = 'SyncFusionCellEditor'.str_replace('grid_', '', $this->data['grid_id']);

                                $column['cellClass'] .= ' grid-editable-cell';

                            }
                        }
                    }

                    if ($field['field'] == 'status' || $field['label'] == 'Status') {
                        $column['cellClass'] .= ' grid-status-field';
                    }

                    if ($has_sort_field && $field['field'] == 'process_sort_order') {
                        $column['sortable'] = true;
                    } elseif ($has_sort_field && $field['field'] == 'sort_order') {
                        $column['sortable'] = true;
                    }

                    // if($has_sort_field && $field['field'] != 'sort_order' && $field['field'] != 'process_sort_order'){
                    //     $column['sortable'] = false;
                    // }

                    if (str_contains($field['field_type'], 'text')) {
                        $column['tooltipField'] = $field['field'];
                    }

                    if ($field['field_type'] == 'select_module') {
                        $column['tooltipField'] = 'join_'.$field['field'];
                    }
                    if ($field['field_type'] == 'select_module') {
                        $column['db_field'] = 'join_'.$field['field'];
                    }
                    if (! empty($field['cell_expression'])) {
                        $column['valueGetter'] = $field['cell_expression'];
                    }

                    if ($field['tooltip']) {
                        $column['headerComponentParams'] = [
                            'menuIcon' => 'fa-bars',
                            'template' => '<div class="ag-cell-label-container" role="presentation">'.
                            '  <span ref="eMenu" class="ag-header-icon ag-header-cell-menu-button"></span>'.
                            '  <div ref="eLabel" class="ag-header-cell-label" role="presentation">'.
                            '    <span ref="eSortOrder" class="ag-header-icon ag-sort-order" ></span>'.
                            '    <span ref="eSortAsc" class="ag-header-icon ag-sort-ascending-icon" ></span>'.
                            '    <span ref="eSortDesc" class="ag-header-icon ag-sort-descending-icon" ></span>'.
                            '    <span ref="eSortNone" class="ag-header-icon ag-sort-none-icon" ></span>'.
                            '    <span class="far fa-question-circle mr-2 mt-1"></span> <span ref="eText" class="ag-header-cell-text" role="columnheader"></span>'.
                            '    <span ref="eFilter" class="ag-header-icon ag-filter-icon"></span>'.
                            '  </div>'.
                            '</div>',
                        ];
                    }

                    if ($field['aggfunc']) {
                        $column['aggFunc'] = $field['aggfunc'];
                    } else {
                        // if($field['pinned_row_total']){
                        if ($field['field_type'] == 'currency' || $field['field_type'] == 'decimal' || $field['field_type'] == 'integer') {
                            $column['aggFunc'] = 'sum';
                            $column['defaultAggFunc'] = 'sum';
                        } else {

                            $column['aggFunc'] = 'value';
                            $column['defaultAggFunc'] = 'value';
                            /*
                                if($this->data['serverside_model']){
                            $column['aggFunc'] = 'max';
                            $column['defaultAggFunc'] = 'max';
                                }else{
                            $column['aggFunc'] = 'value';
                            $column['defaultAggFunc'] = 'value';
                                }
                                */
                        }
                        //  }
                    }

                    if ($field['field_type'] == 'select_module') {
                        $column['field'] = 'join_'.$field['field'];
                    }

                    if (! empty($field['level_access']) && session('role_level') == 'Admin') {
                        $column['headerClass'] = 'field-levelaccess';
                    }

                    if (! empty($field['level_access']) && session('role_level') == 'Admin') {
                        if ($this->data['module_id'] != 499 && $field['field'] == 'sort_order' && session('role_level') == 'Admin') {
                            $column['headerClass'] = 'ag-right-aligned-header field-levelaccess';
                        }
                    }

                    if ($field['field'] == 'id') {
                        $column['type'] = 'idField';
                    } elseif ($this->data['module_id'] != 499 && $field['field'] == 'sort_order' && session('role_level') == 'Admin') {
                        $column['type'] = 'sortField';
                    } elseif ($field['field_type'] == 'phone_number') {
                        $column['type'] = 'phoneField';
                    } elseif ($field['field_type'] == 'email') {
                        $column['type'] = 'emailField';
                    } elseif ($field['field_type'] == 'date' || $field['field_type'] == 'datetime') {
                        $column['type'] = 'dateField';
                    } elseif ($field['field_type'] == 'decimal') {
                        $column['type'] = 'decimalField';
                    } elseif ($field['field_type'] == 'currency') {
                        $column['type'] = 'currencyField';
                        $column['currency_decimals'] = ($field['currency_decimals']) ? $field['currency_decimals'] : 2;
                        $column['currency_symbol'] = ($field['currency_symbol']) ? $field['currency_symbol'] : 'R';
                        $column['row_data_currency'] = $field['row_data_currency'];
                        //if(empty($column['row_data_currency']) && $has_document_currency_field){
                        //    $column['row_data_currency'] = 'document_currency';
                        //}
                        //if(empty($column['row_data_currency']) && $has_currency_field){
                        //    $column['row_data_currency'] = 'currency';
                        //}
                    } elseif ($field['field_type'] == 'image') {
                        $column['type'] = 'imageField';
                    } elseif ($field['field_type'] == 'file') {
                        $column['type'] = 'fileField';
                    } elseif ($field['field_type'] == 'boolean') {
                        $column['type'] = 'booleanField';

                    } elseif (str_contains($field['field_type'], 'textarea')) {
                        $column['type'] = 'textareaField';

                    } elseif ($field['field_type'] == 'hidden' && $field['field'] == 'id') {
                        $column['type'] = 'intField';

                    } elseif ($field['field_type'] == 'decimal' || $field['field_type'] == 'percentage' || $field['field_type'] == 'integer') {
                        $column['type'] = 'intField';
                        //} elseif (($field['field_type'] == 'select_module' ) || $field['field_type'] == 'select_custom') {

                    } elseif (($field['field_type'] == 'select_module' && ($field['checkbox_filters'] || ! empty(request()->query($field['field'])))) || $field['field_type'] == 'select_custom') {
                        /*
                        if($this->data['serverside_model']){
                            $column['type'] = 'defaultField';
                        }else{
                            $column['type'] = 'checkboxField';
                            $column['filterParams'] = ['values' => null,'refreshValuesOnOpen' => true];
                        }
                        */

                        if (! $this->data['serverside_model']) {
                            $column['type'] = $column['field'].$this->data['module_id'].'Field';
                        } else {
                            $column['type'] = 'checkboxField';
                        }

                        try {
                            if ($field['field_type'] == 'select_module' && $this->data['module']['app_id'] == 14) {
                                $filter_datasource = null;
                            } elseif ($field['field_type'] == 'select_module' && $this->data['module']['connection'] == 'pbx_cdr') {
                                $filter_datasource = null;
                            } else {
                                // $filter_datasource = get_module_field_options($this->data['module_id'], $field['field'], false, true);
                                //$filter_datasource = null;
                                //if($field['field_type'] == 'select_module'){
                                //$column['type'] = 'defaultField';
                                //$filter_datasource = null;
                                //}else{
                                $filter_datasource = get_module_field_options($this->data['module_id'], $field['field'], false, true);
                                //}

                            }
                            if ($filter_datasource == 'formonly') {
                                $filter_datasource = null;
                            }
                        } catch (\Throwable $ex) {
                            exception_log($ex);
                            $filter_datasource = null;
                            exception_log($this->data['module_id']);
                            exception_log($field['field']);
                            exception_log($ex->getMessage());
                            exception_log($ex->getTraceAsString());
                        }

                        $column['filter_options'] = $filter_datasource;
                        if ($column['filter_options'] instanceof \Illuminate\Support\Collection && isset($column['filter_options'][0]->text)) {
                            $column['filter_options'] = $column['filter_options']->pluck('text')->toArray();
                        }
                        if (is_array($column['filter_options']) && $field['field'] == 'status' && ! in_array('Deleted', $column['filter_options'])) {
                            $column['filter_options'][] = 'Deleted';
                        }

                        $filterParams = $column['filter_options'];
                        $has_null_value = false;
                        if (! $field['alias']) {
                            if ($this->data['soft_delete']) {
                                $has_null_value = \DB::connection($this->data['connection'])->table($this->data['db_table'])->whereRaw('is_deleted=0 and ('.$field['field'].'="" or '.$field['field'].' is null)')->count();
                            } else {
                                $has_null_value = \DB::connection($this->data['connection'])->table($this->data['db_table'])->whereNull($field['field'])->orWhere($field['field'], '')->count();
                            }
                            if ($has_null_value) {
                                $filterParams[] = null;
                            }
                        }

                        $column['filterParams'] = ['values' => $filterParams, 'refreshValuesOnOpen' => true];

                    }

                }

                if (! empty(request()->drilldown_field) && request()->drilldown_field == $field['field']) {
                    $column['type'] = 'checkboxField';

                    $column['filterParams'] = ['values' => explode(',', request()->{request()->drilldown_field}), 'refreshValuesOnOpen' => true];
                }
                if ($field['checkbox_filters'] && empty($column['filterParams']) && $field['field_type'] != 'boolean' && $field['field_type'] != 'file') {
                    $column['type'] = 'checkboxField';
                    if ($field['field'] == 'category_department') {
                        $departments = \DB::connection('default')->table('crm_product_categories')->where('is_deleted', 0)->pluck('department')->unique()->toArray();

                        $column['filterParams'] = ['values' => array_values($departments), 'refreshValuesOnOpen' => true];
                    } else {

                        $dbvalues = get_module_field_options($field['module_id'], $field['field'], 0, 1);

                        if ($dbvalues && is_array($dbvalues) && count($dbvalues) > 0) {
                            $column['filterParams'] = ['values' => array_values($dbvalues), 'refreshValuesOnOpen' => true];
                        }
                    }
                }

                $cellClassRules = [];
                foreach ($cell_styles as $cell_style) {
                    if ($field['id'] == $cell_style->field_id) {
                        $condition_field_param = 'value';
                        if ($cell_style->condition_field_id) {
                            $condition_field_name = collect($this->data['module_fields'])->where('id', $cell_style->condition_field_id)->pluck('field')->first();
                            if ($condition_field_name) {
                                $condition_field_param = 'data && data.'.$condition_field_name;
                            }
                        }

                        if (empty($cell_style->condition_operator) && empty($cell_style->condition_value)) {
                            $cellClassRules['ag-cell-style'.$cell_style->id] = 'x == x';
                        } else {
                            $cellClassRules['ag-cell-style'.$cell_style->id] = $condition_field_param.$cell_style->condition_operator.'"'.$cell_style->condition_value.'"';
                        }
                    }
                }

                if (isset($cell_style) && $field_show && $field['id'] == $cell_style->field_id) {
                    $column['cellClassRules'] = $cellClassRules;
                }

                $rowClassRules = [];
                foreach ($row_styles as $row_style) {

                    $condition_field_name = collect($this->data['module_fields'])->where('id', $row_style->field_id)->pluck('field')->first();
                    $condition_field_param = 'data && data.'.$condition_field_name;
                    if ($row_style->condition_field_id) {
                        $condition_field_name = collect($this->data['module_fields'])->where('id', $row_style->condition_field_id)->pluck('field')->first();
                        if ($condition_field_name) {
                            $condition_field_param = 'data && data.'.$condition_field_name;
                        }
                    }

                    if ($row_style->condition_value == 'now') {
                        $rowClassRules['ag-row-style'.$row_style->id] = $condition_field_param.$row_style->condition_operator.'"'.date('Y-m-d').'" &&  data.'.$condition_field_name.' > ""';
                    } elseif ($field['id'] == $row_style->field_id) {
                        $rowClassRules['ag-row-style'.$row_style->id] = $condition_field_param.$row_style->condition_operator.'"'.$row_style->condition_value.'"';
                    }
                }

                if (isset($row_style) && $field_show && $field['id'] == $row_style->field_id) {
                    $column['rowClassRules'] = $rowClassRules;
                }

                if ($this->data['detail_module_id'] > 0 && ! empty($this->data['detail_module_key'])) {
                    if ($field['display_field']) {
                        $column['cellRenderer'] = 'agGroupCellRenderer';
                        $column['cellRendererParams'] = [
                            'suppressDoubleClickExpand' => true,
                        ];

                        $column['cellClass'] .= ' detail-expand-field';
                    }
                } elseif ($this->data['detail_cell_renderer'] > '') {
                    if ($field['display_field']) {
                        $column['cellRenderer'] = 'agGroupCellRenderer';
                        $column['cellRendererParams'] = [
                            'suppressDoubleClickExpand' => true,
                        ];

                        $column['cellClass'] .= ' detail-expand-field';
                    }
                } elseif ($field['display_field']) {
                    $column['cellClass'] .= ' name-field';
                }

                if (! empty($field['cell_renderer'])) {

                    $column['cellRenderer'] = $field['cell_renderer'];
                }

                $column['row_tooltip'] = $field['row_tooltip'];
                $row_tooltip = collect($this->data['module_fields'])->where('row_tooltip')->count();
                if ($row_tooltip) {
                    $column['tooltipComponent'] = 'rowtooltip'.$field['module_id'];
                    $column['tooltipField'] = $field['field'];
                }

                if (! empty($field['cell_renderer_params'])) {

                    $column['cellRendererParams'] = json_decode($field['cell_renderer_params'], false);
                }
                if ($field['field_type'] == 'decimal' || $field['field_type'] == 'currency' || $field['field_type'] == 'percentage' || $field['field_type'] == 'integer') {
                    $column['headerClass'] = 'ag-right-aligned-header';
                    $column['cellClass'] .= ' ag-right-aligned-cell';
                }

                if ($field['field_type'] == 'select_custom' || $field['field_type'] == 'select_function') {
                    $db_field_type = get_column_type($this->data['db_table'], $field['field'], $this->data['connection']);

                    if ($db_field_type == 'currency' || $db_field_type == 'integer') {
                        $column['headerClass'] = 'ag-right-aligned-header';
                        $column['cellClass'] .= ' ag-right-aligned-cell';
                    }
                }

                if ($column) {
                    if ($field['field'] == 'sort_order') {
                        $sort_field = $column;
                    } else {
                        $columns[] = $column;
                        $all_columns[] = $column;
                    }
                }
            }

            $tab_groups[] = ['headerName' => $tab, 'children' => $columns];
        }

        if (! empty($sort_field)) {
            $tab_groups[] = $sort_field;
            $all_columns[] = $sort_field;
        }
        /*
        if($return_tab_groups){
            return ['tab_groups'=>$tab_groups,'all_columns'=>$all_columns];
        }

        if((in_array('General',$tabs) && count($tabs)==1)){
            return $all_columns;
        }

        return $tab_groups;
        */
        /*
        if(!empty($this->data['tree_data_field'])) {
            foreach($all_columns as $i => $c){
                foreach($c as $k => $v){
                    if(!in_array($k,['field','HeaderName'])){
                       unset($c[$k]);
                    }
                }
                $all_columns[$i] = $c;
            }
        }
        */

        return $all_columns;
    }

    public function getLayout($layout_id = false)
    {
        $layout_data = [];
        if ($layout_id) {
            $layout_data['new_layout'] = false;
            $layout_data['layout_id'] = $layout_id;
        }

        if (! $layout_data['layout_id']) {
            $module_layouts = $this->data['module_layouts'];

            foreach ($module_layouts as $module_layout) {
                $role_defaults = collect(explode(',', $module_layout->role_default))->filter()->unique()->toArray();
                if (in_array(session('role_id'), $role_defaults)) {
                    $layout_id_default = $module_layout->id;
                }
            }
            if (empty($layout_id_default)) {
                $layout_id_default = $this->data['module_layouts']->where('global_default', 1)->pluck('id')->first();
            }

            $layout_data['new_layout'] = false;
            $layout_data['layout_id'] = $layout_id_default;
        }

        $grid_view = $this->data['module_layouts']->where('id', $layout_data['layout_id'])->first();
        $layout_data['layout_tracking'] = \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout_data['layout_id'])->where('is_deleted', 0)->count();
        $layout = $this->data['module_layouts']->where('id', $layout_data['layout_id'])->pluck('aggrid_state')->first();
        $db_columns = $this->data['db_module_fields']->pluck('field')->toArray();
        $tabs = $this->data['db_module_fields']->sortBy('sort_order')->pluck('tab')->unique()->toArray();

        $layout = json_decode($layout);
        $auto_group_col_sort = '';

        if (! empty($layout) && is_object($layout)) {
            foreach ($layout as $state_type => $cols) {
                if (is_object($layout->colState)) {
                    $layout->colState = (array) $layout->colState;
                }
                if ($state_type == 'colState') {
                    foreach ($cols as $i => $col) {
                        $layout->colState[$i]->resizable = true;
                        if (! isset($layout->colState[$i]->rowGroup)) {
                            $layout->colState[$i]->rowGroup = false;
                        }
                        //set boolean values
                        foreach ($col as $key => $val) {
                            if ($val === 'true') {
                                $layout->colState[$i]->{$key} = true;
                            }
                            if ($val === 'false') {
                                $layout->colState[$i]->{$key} = false;
                            }
                            if ($val === '') {
                                unset($layout->colState[$i]->{$key});
                            }
                        }
                        $colId = $col->colId;
                        if (str_starts_with($colId, 'join_')) {
                            $colId = str_replace('join_', '', $colId);
                        }
                        if (str_contains($colId, 'AutoColumn')) {
                            $auto_group_col_sort = $col->sort;

                            continue;
                        }
                        if (! in_array($colId, $db_columns)) {
                            unset($layout->colState[$i]);
                        } else {
                            foreach ($this->data['module_fields'] as $field) {
                                if ($field['field'] == $colId) {
                                    $field_show = $this->gridAccess($field);

                                    if (! $field_show || $field['field_type'] == 'none') {
                                        unset($layout->colState[$i]);
                                    }

                                }
                            }
                        }

                        // remove row pinning
                        //if (isset($layout->colState[$i])) {
                        // $layout->colState[$i]->pinned = false;
                        // }

                        // if ($grid_view->layout_type == 'Layout' && !$this->data['serverside_model'] && session('role_level') == 'Admin') {
                        //if ($layout->colState[$i]->colId == 'sort_order') {
                        //    $sort_field = $layout->colState[$i];
                        //    $sort_field->pinned = false;
                        //    $sort_field->hide = false;

                        // $sort_field->type ='rightAligned';
                        //    $sort_field->headerClass = 'ag-right-aligned-header';
                        //     $sort_field->cellClass = 'ag-right-aligned-cell';
                        //$sort_field->cellClass ='sort-order-cell';
                        //    unset($layout->colState[$i]);
                        // }
                        //  }

                        /*
                        if(session('role_level') == 'Admin'){

                            if ($layout->colState[$i]->colId == 'notes') {
                                $notes_field = $layout->colState[$i];
                                $notes_field->pinned = 'right';
                                $notes_field->hide = false;
                                unset($layout->colState[$i]);
                            }

                            if ($layout->colState[$i]->colId == 'status') {
                                $status_field = $layout->colState[$i];
                                $status_field->pinned = 'right';
                                $status_field->hide = false;
                                unset($layout->colState[$i]);
                            }
                        }
                        */
                    }
                    /*
                    if(!empty($notes_field)){
                        $layout->colState[] = $notes_field;
                    }
                    if(!empty($status_field)){
                        $layout->colState[] = $status_field;
                    }
                    */
                    if (! empty($sort_field)) {
                        $layout->colState[] = $sort_field;
                    }

                    $layout->colState = array_values($layout->colState);
                }
                if (is_dev()) {
                    //dd($layout->colState);
                }

                if (isset($layout->sortState) && is_object($layout->sortState)) {
                    $layout->sortState = (array) $layout->sortState;
                }

                if ($state_type == 'sortState') {
                    foreach ($cols as $i => $col) {
                        $colId = $col->colId;
                        if (str_starts_with($colId, 'join_')) {
                            $colId = str_replace('join_', '', $colId);
                        }

                        if (str_contains($colId, 'AutoColumn')) {
                            continue;
                        }
                        if (! in_array($colId, $db_columns)) {
                            unset($layout->sortState[$i]);
                        } else {
                            foreach ($this->data['module_fields'] as $field) {
                                if ($field['field'] == $colId) {
                                    $field_show = $this->gridAccess($field);
                                    if (! $field_show || $field['field_type'] == 'none') {
                                        unset($layout->sortState[$i]);
                                    }
                                }
                            }
                        }
                    }

                    $layout->sortState = array_values($layout->sortState);
                }

                if (is_object($layout->filterState)) {
                    $layout->filterState = (array) $layout->filterState;
                }
                if ($state_type == 'filterState') {
                    foreach ($cols as $filter_field => $filter) {
                        if (str_starts_with($filter_field, 'join_')) {
                            $filter_field = str_replace('join_', '', $filter_field);
                        }

                        if (str_contains($filter_field, 'AutoColumn')) {
                            continue;
                        }
                        if (! in_array($filter_field, $db_columns)) {
                            unset($layout->filterState[$filter_field]);
                        } else {
                            foreach ($this->data['module_fields'] as $field) {
                                if ($field['field'] == $filter_field) {

                                    if ($this->data['serverside_model'] == 0 && $field['field_type'] == 'boolean') {
                                        $vals = [];
                                        $replaced = false;

                                        if (empty($filter->values) || count($filter->values) == 0) {
                                            continue;
                                        }

                                        foreach ($filter->values as $v) {
                                            if ($v == 'Yes') {
                                                $replaced = true;
                                                $vals[] = '1';
                                            }
                                            if ($v == 'No') {
                                                $replaced = true;
                                                $vals[] = '0';
                                            }
                                        }
                                        if ($replaced) {
                                            $layout->filterState[$filter_field]->values = $vals;
                                        }
                                    }

                                    if ($this->data['serverside_model'] == 0 && $field['field_type'] == 'boolean') {
                                        $vals = [];
                                        $replaced = false;

                                        if (empty($filter->values) || count($filter->values) == 0) {
                                            continue;
                                        }

                                        foreach ($filter->values as $v) {
                                            if ($v == 'Yes') {
                                                $replaced = true;
                                                $vals[] = '1';
                                            }
                                            if ($v == 'No') {
                                                $replaced = true;
                                                $vals[] = '0';
                                            }
                                        }
                                        if ($replaced) {
                                            $layout->filterState[$filter_field]->values = $vals;
                                        }
                                    }

                                    $field_show = $this->gridAccess($field);

                                    if (! $field_show || $field['field_type'] == 'none') {
                                        unset($layout->filterState[$filter_field]);
                                    }

                                    if (isset($layout->filterState[$filter_field]) && $layout->filterState[$filter_field]->filterType == 'set') {
                                        if (! empty($layout->filterState[$filter_field]->values)) {
                                            foreach ($layout->filterState[$filter_field]->values as $i => $v) {
                                                if ($v == '') {
                                                    $layout->filterState[$filter_field]->values[$i] = null;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (! empty(request()->query_string) && is_array(request()->query_string)) {
            $qs = request()->query_string;
        } elseif (! empty(request()->getQueryString())) {
            parse_str(request()->getQueryString(), $qs);
        } else {
            $qs = [];
        }

        foreach ($this->data['module_fields'] as $field) {
            if ($field['default_value'] == 'session_user_id') {
                // $qs['user_id'] = session('user_id');
            }
        }

        // sort colstate based on field state and tabs
        /*
             if ($grid_view->global_default && $this->data['module_fields'] && !empty($layout->colState) && is_array($layout->colState)) {

                 $sortedColState = [];
                 $advancedsortedColState = [];
                 $beforeSortState = $layout->colState;
                 $processed_fields = [];
                 foreach ($tabs as $tab) {
                     $pinned_cols = [];
                     foreach ($this->data['module_fields'] as $field) {
                         $field_name = $field['field'];
                         if ($field['field_type'] == 'select_module') {
                             $field_name = 'join_'.$field_name;
                         }
                         foreach ($layout->colState as $col) {
                             if ($col->colId == $field_name && !in_array($col->colId,$processed_fields)) {
                                 $processed_fields[] = $col->colId;
                                 if ($field_name == 'sort_order') {
                                     $sort_field = $col;
                                 } elseif (!empty($col->pinned)) {
                                     if ($field['tab'] != 'General') {
                                         $col->hide = true;
                                     }


                                     $pinned_cols[] = $col;
                                 } else {
                                     if ($field['tab'] != 'General') {
                                         $col->hide = true;
                                     }

                                     if ($field['tab'] == 'General' && $col->hide == false) {
                                         $sortedColState[] = $col;
                                     } else {
                                         $col->label = $field['label'];
                                         $advancedsortedColState[] = $col;
                                     }
                                 }
                             }
                         }
                     }
                 }


                 foreach ($pinned_cols as $pinned_col) {
                     $sortedColState[] = $pinned_col;
                 }

                 $advancedsortedColState = collect($advancedsortedColState)->sortBy('label');
                 foreach ($advancedsortedColState as $col) {
                     unset($col->label);
                     $sortedColState[] = $col;
                 }

                 if ($sort_field) {
                     $sortedColState[] = $sort_field;
                 }
                 $layout->colState = $sortedColState;
             }
*/

        if (is_dev()) {
        }

        request()->query_string = $qs;

        /*
        $master_module_count = \DB::connection('default')->table('erp_cruds')->where('detail_module_id', $this->data['module_id'])->count();
        if($master_module_count){
           $qs = [];
           request()->query_string = [];
        }
        */

        // set filters from query string
        if (! empty($grid_view->global_default) || ! empty($qs)) {
            if (! isset($layout)) {
                $layout = (object) [];
            }
            // if (!isset($layout->filterState)) {
            //     $layout->filterState = (object) [];
            // }

            foreach ($qs as $field => $value) {
                if (isset($layout->filterState[$field])) {
                    unset($layout->filterState[$field]);
                }
            }

            if (! empty($layout->filterState) && (! empty(request()->remove_layout_filters) || ! empty(request()->id) || ! empty(request()->account_id))) {
                foreach ($layout->filterState as $f => $fl) {
                    unset($layout->filterState[$f]);
                }
            }

            if (! empty($layout->filterState) && (! empty(request()->remove_layout_filters) || ! empty(request()->account_id))) {
                foreach ($layout->filterState as $f => $fl) {
                    unset($layout->filterState[$f]);
                }
            }

            foreach ($qs as $field => $value) {
                if ($field == 'layout_id') {
                    continue;
                }
                if ($field == 'domain_uuid' && ! in_array('domain_uuid', $db_columns) && in_array('domain_name', $db_columns)) {
                    $field = 'domain_name';
                    $value = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $value)->pluck('domain_name')->first();
                }
                foreach ($this->data['module_fields'] as $i => $grid_field) {

                    if ($grid_field['field'] == 'is_deleted' && $field == 'id') {
                        $layout->filterState['is_deleted'] = ['values' => [0, 1], 'filterType' => 'set'];
                    } elseif ($grid_field['field'] == $field) {
                        $field_show = $this->gridAccess($grid_field);
                        if ($field_show && $grid_field['field_type'] != 'none') {
                            if ($grid_field['field_type'] == 'boolean') {
                                if ($value == 1) {
                                    $layout->filterState[$field] = ['values' => [1], 'filterType' => 'set'];
                                } else {
                                    $layout->filterState[$field] = ['values' => [0], 'filterType' => 'set'];
                                }
                            } elseif ($grid_field['field_type'] == 'select_module') {

                                if (empty($value) || $value == 'null') {
                                    $layout->filterState['join_'.$field] = ['values' => [null], 'filterType' => 'set'];

                                    continue;
                                }
                                if (str_contains($value, ',')) {
                                    $values = explode(',', $value);
                                } else {
                                    $values = [$value];
                                }
                                $lookup_vals = [];
                                foreach ($values as $value) {
                                    $text_val = '';
                                    if ($grid_field['alias'] == $this->data['db_table']) {
                                        $exists = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($grid_field['field'], $value)->count();
                                    } else {
                                        $exists = 1;
                                    }

                                    //if(!$exists){

                                    //   $lookup_vals[] = null;
                                    //}else{
                                    //if (('reseller_user' == $field || str_ends_with($field, 'id')) && is_numeric($value)) {
                                    if (! empty($grid_field['opt_db_table'])) {
                                        $display = explode(',', $grid_field['opt_db_display']);
                                        $total = count($display) - 1;
                                        $text_val = '';
                                        foreach ($display as $i => $lookup) {

                                            $text_val .= \DB::connection($this->data['connection'])->table($grid_field['opt_db_table'])->where($grid_field['opt_db_key'], $value)->pluck($lookup)->first();

                                            if ($i != $total) {
                                                $text_val .= ' - ';
                                            }
                                        }
                                        //  }
                                        // }
                                        if ($grid_field['opt_db_table'] == 'erp_cruds') {
                                            $text_val = ucwords(str_replace('_', ' ', $text_val));
                                        }
                                        $text_val = trim($text_val);
                                        $lookup_vals[] = $text_val;
                                    }

                                    if ($this->data['serverside_model']) {
                                        // $layout->filterState["join_".$field] = ["filterType" => "text", "type" => "equals", "filter" => $text_val];
                                        $layout->filterState['join_'.$field] = ['values' => $lookup_vals, 'filterType' => 'set'];
                                    } else {
                                        $layout->filterState['join_'.$field] = ['values' => $lookup_vals, 'filterType' => 'set'];
                                    }

                                }

                            } elseif (! empty(request()->drilldown_field) && request()->drilldown_field == $grid_field['field']) {

                                if (str_contains($value, ',')) {
                                    $values = explode(',', $value);
                                } else {
                                    $values = [$value];
                                }
                                $layout->filterState[$field] = ['values' => $values, 'filterType' => 'set'];
                            } elseif ($grid_field['field_type'] == 'select_custom') {
                                if (str_contains($value, ',')) {
                                    $values = explode(',', $value);
                                } else {
                                    $values = [$value];
                                }
                                $layout->filterState[$field] = ['values' => $values, 'filterType' => 'set'];
                            } elseif ($grid_field['field_type'] == 'date') {
                                $layout->filterState[$field] = ['dateFrom' => '', 'dateTo' => '', 'filterType' => 'date', 'type' => $value];

                            } else {
                                $layout->filterState[$field] = ['filterType' => 'text', 'type' => 'equals', 'filter' => $value];
                            }
                        }
                    }
                }
            }
        }

        // if ($layout) {
        //     if (!isset($layout->filterState)) {
        //         $layout->filterState = [];
        //     }
        //     if (!isset($layout->sortState)) {
        //         $layout->sortState = [];
        //     }
        // }
        /*
                if (!empty($layout->filterState)) {
                    foreach ($layout->filterState as $key => $filter_state) {
                        if ($filter_state->filterType == 'set' && count($filter_state->values) > 10) {
                         //   unset($layout->filterState[$key]);
                        }
                    }
                }
        */
        if (! empty(request()->query_string) && isset($layout->colState) && is_countable($layout->colState) && count($layout->colState) > 0) {
            foreach ($layout->colState as $i => $col) {
                foreach (request()->query_string as $field => $value) {
                    if ($field == $col->colId || 'join_'.$field == $col->colId) {
                        $layout->colState[$i]->hide = false;
                    }
                }
            }
        }

        if (isset($layout->colState) && is_countable($layout->colState) && count($layout->colState) > 0) {
            foreach ($layout->colState as $col) {
                if ($col->hide == true && isset($layout->filterState[$col->colId])) {
                    // unset($layout->filterState[$col->colId]);
                }
            }
        }

        if (isset($layout->filterState) && is_countable($layout->filterState) && count($layout->filterState) > 0) {
            foreach ($layout->filterState as $col => $filter) {
                if ($filter->filterType == 'set') {
                    if (! empty($filter->values)) {
                        foreach ($filter->values as $i => $v) {

                            if ($v === '') {
                                $layout->filterState[$col]->values[$i] = null;
                            }
                        }
                    }
                }
            }
        }

        // if(is_dev()){

        //dd($layout,$layout_data);
        //}

        $layout_data['auto_group_col_sort'] = $auto_group_col_sort;
        $layout_data['layout'] = $layout;
        $layout_data['pivotState'] = '';
        if ($grid_view->pivot_mode) {
            $layout_data['pivotState'] = json_decode($grid_view->aggrid_pivot_state);
        }

        return $layout_data;
    }

    public function getDetailLayout($layout_id = false)
    {

        $this->data['module_layouts'] = \DB::connection('default')->table('erp_grid_views')->where('module_id', $this->data['module_id'])->get();
        $detail_module_id = $this->data['detail_module_id'];

        $layout_data = [];
        if ($layout_id) {
            $layout_data['new_layout'] = false;
            $layout_data['layout_id'] = $layout_id;
        }

        if (! $layout_data['layout_id']) {
            $module_layouts = $this->data['module_layouts'];

            foreach ($module_layouts as $module_layout) {
                $role_defaults = collect(explode(',', $module_layout->role_default))->filter()->unique()->toArray();
                if (in_array(session('role_id'), $role_defaults)) {
                    $layout_id_default = $module_layout->id;
                }
            }
            if (empty($layout_id_default)) {
                $layout_id_default = $this->data['module_layouts']->where('global_default', 1)->pluck('id')->first();
            }

            $layout_data['new_layout'] = false;
            $layout_data['layout_id'] = $layout_id_default;
        }

        $module_fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $detail_module_id)->orderBy('sort_order')->get();

        $db_module_fields = $module_fields;
        $module_fields = json_decode(json_encode($module_fields, true), true);

        $grid_view = $this->data['module_layouts']->where('id', $layout_data['layout_id'])->first();
        $layout_data['layout_tracking'] = \DB::connection('default')->table('crm_staff_tasks')->where('layout_id', $layout_data['layout_id'])->where('is_deleted', 0)->count();
        $layout = $this->data['module_layouts']->where('id', $layout_data['layout_id'])->pluck('detail_aggrid_state')->first();

        $db_columns = $db_module_fields->pluck('field')->toArray();
        $tabs = $db_module_fields->sortBy('sort_order')->pluck('tab')->unique()->toArray();

        $layout = json_decode($layout);

        $auto_group_col_sort = '';

        if (! empty($layout) && is_object($layout)) {
            foreach ($layout as $state_type => $cols) {
                if (is_object($layout->colState)) {
                    $layout->colState = (array) $layout->colState;
                }
                if ($state_type == 'colState') {
                    foreach ($cols as $i => $col) {
                        $layout->colState[$i]->resizable = true;
                        if (! isset($layout->colState[$i]->rowGroup)) {
                            $layout->colState[$i]->rowGroup = false;
                        }
                        //set boolean values
                        foreach ($col as $key => $val) {
                            if ($val === 'true') {
                                $layout->colState[$i]->{$key} = true;
                            }
                            if ($val === 'false') {
                                $layout->colState[$i]->{$key} = false;
                            }
                            if ($val === '') {
                                unset($layout->colState[$i]->{$key});
                            }
                        }
                        $colId = $col->colId;
                        if (str_starts_with($colId, 'join_')) {
                            $colId = str_replace('join_', '', $colId);
                        }
                        if (str_contains($colId, 'AutoColumn')) {
                            $auto_group_col_sort = $col->sort;

                            continue;
                        }
                        if (! in_array($colId, $db_columns)) {
                            unset($layout->colState[$i]);
                        } else {
                            foreach ($module_fields as $field) {
                                if ($field['field'] == $colId) {
                                    $field_show = $this->gridAccess($field);

                                    if (! $field_show || $field['field_type'] == 'none') {
                                        unset($layout->colState[$i]);
                                    }

                                }
                            }
                        }

                        // remove row pinning
                        //if (isset($layout->colState[$i])) {
                        // $layout->colState[$i]->pinned = false;
                        //}

                        // if ($grid_view->layout_type == 'Layout' && !$this->data['serverside_model'] && session('role_level') == 'Admin') {
                        // if ($layout->colState[$i]->colId == 'sort_order') {
                        //   $sort_field = $layout->colState[$i];
                        //   $sort_field->pinned = false;
                        //   $sort_field->hide = false;

                        // $sort_field->type ='rightAligned';
                        //   $sort_field->headerClass = 'ag-right-aligned-header';
                        //   $sort_field->cellClass = 'ag-right-aligned-cell';
                        //    //$sort_field->cellClass ='sort-order-cell';
                        //    unset($layout->colState[$i]);
                        // }
                        // }

                        /*
                        if(session('role_level') == 'Admin'){

                            if ($layout->colState[$i]->colId == 'notes') {
                                $notes_field = $layout->colState[$i];
                                $notes_field->pinned = 'right';
                                $notes_field->hide = false;
                                unset($layout->colState[$i]);
                            }

                            if ($layout->colState[$i]->colId == 'status') {
                                $status_field = $layout->colState[$i];
                                $status_field->pinned = 'right';
                                $status_field->hide = false;
                                unset($layout->colState[$i]);
                            }
                        }
                        */
                    }
                    /*
                    if(!empty($notes_field)){
                        $layout->colState[] = $notes_field;
                    }
                    if(!empty($status_field)){
                        $layout->colState[] = $status_field;
                    }
                    */
                    if (! empty($sort_field)) {
                        $layout->colState[] = $sort_field;
                    }

                    $layout->colState = array_values($layout->colState);
                }
                if (is_dev()) {
                    //dd($layout->colState);
                }

                if (isset($layout->sortState) && is_object($layout->sortState)) {
                    $layout->sortState = (array) $layout->sortState;
                }

                if ($state_type == 'sortState') {
                    foreach ($cols as $i => $col) {
                        $colId = $col->colId;
                        if (str_starts_with($colId, 'join_')) {
                            $colId = str_replace('join_', '', $colId);
                        }

                        if (str_contains($colId, 'AutoColumn')) {
                            continue;
                        }
                        if (! in_array($colId, $db_columns)) {
                            unset($layout->sortState[$i]);
                        } else {
                            foreach ($module_fields as $field) {
                                if ($field['field'] == $colId) {
                                    $field_show = $this->gridAccess($field);
                                    if (! $field_show || $field['field_type'] == 'none') {
                                        unset($layout->sortState[$i]);
                                    }
                                }
                            }
                        }
                    }

                    $layout->sortState = array_values($layout->sortState);
                }

                if (isset($layout->filterState)) {
                    $layout->filterState = (array) $layout->filterState;
                }
                if ($state_type == 'filterState') {
                    foreach ($cols as $filter_field => $filter) {
                        if (str_starts_with($filter_field, 'join_')) {
                            $filter_field = str_replace('join_', '', $filter_field);
                        }

                        if (str_contains($filter_field, 'AutoColumn')) {
                            continue;
                        }
                        if (! in_array($filter_field, $db_columns)) {
                            unset($layout->filterState[$filter_field]);
                        } else {
                            foreach ($module_fields as $field) {
                                if ($field['field'] == $filter_field) {

                                    if ($this->data['serverside_model'] == 0 && $field['field_type'] == 'boolean') {
                                        $vals = [];
                                        $replaced = false;

                                        if (empty($filter->values) || count($filter->values) == 0) {
                                            continue;
                                        }

                                        foreach ($filter->values as $v) {
                                            if ($v == 'Yes') {
                                                $replaced = true;
                                                $vals[] = '1';
                                            }
                                            if ($v == 'No') {
                                                $replaced = true;
                                                $vals[] = '0';
                                            }
                                        }
                                        if ($replaced) {
                                            $layout->filterState[$filter_field]->values = $vals;
                                        }
                                    }

                                    if ($this->data['serverside_model'] == 0 && $field['field_type'] == 'boolean') {
                                        $vals = [];
                                        $replaced = false;

                                        if (empty($filter->values) || count($filter->values) == 0) {
                                            continue;
                                        }

                                        foreach ($filter->values as $v) {
                                            if ($v == 'Yes') {
                                                $replaced = true;
                                                $vals[] = '1';
                                            }
                                            if ($v == 'No') {
                                                $replaced = true;
                                                $vals[] = '0';
                                            }
                                        }
                                        if ($replaced) {
                                            $layout->filterState[$filter_field]->values = $vals;
                                        }
                                    }

                                    $field_show = $this->gridAccess($field);

                                    if (! $field_show || $field['field_type'] == 'none') {
                                        unset($layout->filterState[$filter_field]);
                                    }

                                    if ($layout->filterState[$filter_field]->filterType == 'set') {
                                        foreach ($layout->filterState[$filter_field]->values as $i => $v) {
                                            if ($v == '') {
                                                $layout->filterState[$filter_field]->values[$i] = null;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (! empty(request()->query_string) && is_array(request()->query_string)) {
            $qs = request()->query_string;
        } elseif (! empty(request()->getQueryString())) {
            parse_str(request()->getQueryString(), $qs);
        } else {
            $qs = [];
        }

        foreach ($module_fields as $field) {
            if ($field['default_value'] == 'session_user_id') {
                // $qs['user_id'] = session('user_id');
            }
        }

        // sort colstate based on field state and tabs
        /*
             if ($grid_view->global_default && $module_fields && !empty($layout->colState) && is_array($layout->colState)) {

                 $sortedColState = [];
                 $advancedsortedColState = [];
                 $beforeSortState = $layout->colState;
                 $processed_fields = [];
                 foreach ($tabs as $tab) {
                     $pinned_cols = [];
                     foreach ($module_fields as $field) {
                         $field_name = $field['field'];
                         if ($field['field_type'] == 'select_module') {
                             $field_name = 'join_'.$field_name;
                         }
                         foreach ($layout->colState as $col) {
                             if ($col->colId == $field_name && !in_array($col->colId,$processed_fields)) {
                                 $processed_fields[] = $col->colId;
                                 if ($field_name == 'sort_order') {
                                     $sort_field = $col;
                                 } elseif (!empty($col->pinned)) {



                                     $pinned_cols[] = $col;
                                 } else {


                                     if ($field['tab'] == 'General' && $col->hide == false) {
                                         $sortedColState[] = $col;
                                     } else {
                                         $col->label = $field['label'];
                                         $advancedsortedColState[] = $col;
                                     }
                                 }
                             }
                         }
                     }
                 }


                 foreach ($pinned_cols as $pinned_col) {
                     $sortedColState[] = $pinned_col;
                 }

                 $advancedsortedColState = collect($advancedsortedColState)->sortBy('label');
                 foreach ($advancedsortedColState as $col) {
                     unset($col->label);
                     $sortedColState[] = $col;
                 }

                 if ($sort_field) {
                     $sortedColState[] = $sort_field;
                 }
                 $layout->colState = $sortedColState;
             }
*/

        if (is_dev()) {
        }

        request()->query_string = $qs;

        /*
        $master_module_count = \DB::connection('default')->table('erp_cruds')->where('detail_module_id', $this->data['module_id'])->count();
        if($master_module_count){
           $qs = [];
           request()->query_string = [];
        }
        */

        // set filters from query string
        /*
        if (!empty($grid_view->global_default) || !empty($qs)) {
            if (!isset($layout)) {
                $layout = (object) [];
            }
            if (!isset($layout->filterState)) {
                $layout->filterState = [];
            }


            foreach ($qs as $field => $value) {
                if (isset($layout->filterState[$field])) {
                    unset($layout->filterState[$field]);
                }
            }

            foreach ($qs as $field => $value) {
                foreach ($module_fields as $i => $grid_field) {

                    if ($grid_field['field'] == $field) {
                        $field_show = $this->gridAccess($grid_field);
                        if ($field_show && $grid_field['field_type'] != 'none') {
                            if ($grid_field['field_type'] == "boolean") {
                                if ($value == 1) {
                                    $layout->filterState[$field] =  ["values" => [1],"filterType" => "set"];
                                } else {
                                    $layout->filterState[$field] =  ["values" => [0],"filterType" => "set"];
                                }
                            } elseif ($grid_field['field_type'] == 'select_module') {
                                $text_val = '';
                                if($grid_field['alias'] == $this->data['db_table']){
                                    $exists = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($grid_field['field'], $value)->count();
                                }else{
                                    $exists = 1;
                                }

                                if(!$exists){

                                    $layout->filterState["join_".$field] =  ["values" => [null],"filterType" => "set"];
                                }else{
                                //if (('reseller_user' == $field || str_ends_with($field, 'id')) && is_numeric($value)) {
                                if (!empty($grid_field['opt_db_table'])) {
                                    $display = explode(',', $grid_field['opt_db_display']);
                                    $total = count($display) - 1;
                                    $text_val = '';
                                    foreach ($display as $i => $lookup) {
                                        $text_val .= \DB::connection($this->data['connection'])->table($grid_field['opt_db_table'])->where($grid_field['opt_db_key'], $value)->pluck($lookup)->first();

                                        if ($i != $total) {
                                            $text_val .= ' - ';
                                        }
                                    }
                                }
                                // }
                                if ($grid_field['opt_db_table'] == 'erp_cruds') {
                                    $text_val = ucwords(str_replace('_', ' ', $text_val));
                                }
                                $text_val = trim($text_val);

                                if($this->data['serverside_model']){
                                   // $layout->filterState["join_".$field] = ["filterType" => "text", "type" => "equals", "filter" => $text_val];
                                    $layout->filterState["join_".$field] =  ["values" => [$text_val],"filterType" => "set"];
                                }else{
                                    $layout->filterState["join_".$field] =  ["values" => [$text_val],"filterType" => "set"];
                                }

                                }
                            } elseif ($grid_field['field_type'] == "select_custom") {
                                $layout->filterState[$field] =  ["values" => [$value],"filterType" => "set"];
                            } else {
                                $layout->filterState[$field] = ["filterType" => "text", "type" => "equals", "filter" => $value];
                            }
                        }
                    }
                }
            }
        }
*/
        if ($layout) {
            if (! isset($layout->filterState)) {
                $layout->filterState = [];
            }
            if (! isset($layout->sortState)) {
                $layout->sortState = [];
            }
        }
        /*
                if (!empty($layout->filterState)) {
                    foreach ($layout->filterState as $key => $filter_state) {
                        if ($filter_state->filterType == 'set' && count($filter_state->values) > 10) {
                         //   unset($layout->filterState[$key]);
                        }
                    }
                }
        */
        if (! empty(request()->query_string) && isset($layout->colState) && is_countable($layout->colState) && count($layout->colState) > 0) {
            foreach ($layout->colState as $i => $col) {
                foreach (request()->query_string as $field => $value) {
                    if ($field == $col->colId || 'join_'.$field == $col->colId) {
                        $layout->colState[$i]->hide = false;
                    }
                }
            }
        }

        if (isset($layout->colState) && is_countable($layout->colState) && count($layout->colState) > 0) {
            foreach ($layout->colState as $col) {
                if ($col->hide == true && isset($layout->filterState[$col->colId])) {
                    unset($layout->filterState[$col->colId]);
                }
            }
        }

        if (isset($layout->filterState) && is_countable($layout->filterState) && count($layout->filterState) > 0) {
            foreach ($layout->filterState as $col => $filter) {
                if ($filter->filterType == 'set') {
                    foreach ($filter->values as $i => $v) {

                        if ($v === '') {
                            $layout->filterState[$col]->values[$i] = null;
                        }
                    }
                }
            }
        }

        // if(is_dev()){

        //dd($layout,$layout_data);
        //}

        $layout_data['auto_group_col_sort'] = $auto_group_col_sort;
        $layout_data['layout'] = $layout;
        $layout_data['pivotState'] = '';
        if ($grid_view->pivot_mode) {
            $layout_data['pivotState'] = json_decode($grid_view->aggrid_pivot_state);
        }

        return $layout_data;
    }

    public function getGrid($grid_layout_id = false, $sub_grid = false)
    {
        $frozen_columns = 0;
        $data = $this->data;

        if (! empty($this->params['layout_id'])) {
            $grid_layout_id = $this->params['layout_id'];

        }

        $data['layout_settings'] = null;

        $module_layouts = \DB::connection('default')->table('erp_grid_views')->where('module_id', $this->data['module_id'])->get();

        foreach ($module_layouts as $module_layout) {
            $role_defaults = collect(explode(',', $module_layout->role_default))->filter()->unique()->toArray();
            if (in_array(session('role_id'), $role_defaults)) {
                $grid_layout_id_default = $module_layout->id;
            }
        }
        if (empty($grid_layout_id_default)) {
            $grid_layout_id_default = $module_layouts->where('global_default', 1)->pluck('id')->first();
        }

        if (empty($grid_layout_id_default)) {
            $layout_count = $module_layouts->count();
            if (! $layout_count) {
                $default_layout_data = [
                    'name' => $this->data['menu_name'],
                    'module_id' => $this->data['module_id'],
                    'global_default' => 1,
                ];
                $grid_layout_id_default = \DB::connection('default')->table('erp_grid_views')->insertGetId($default_layout_data);
                $this->data['module_layouts'] = \DB::connection('default')->table('erp_grid_views')->where('module_id', $this->data['module_id'])->get();
            } else {

                $grid_layout_id_default = $module_layouts->where('name', 'All records')->pluck('id')->first();
                if (! $grid_layout_id_default) {
                    $grid_layout_id_default = $module_layouts->pluck('id')->first();
                }
                \DB::connection('default')->table('erp_grid_views')->where('id', $grid_layout_id_default)->update(['global_default' => 1]);

            }
        }

        $visible_columns = [];
        $layout_cols = [];

        $data['init_layout_type'] = 'Layout';
        $db_fields = $this->data['db_module_fields']->pluck('field')->toArray();

        if ($grid_layout_id) {
            $grid_layout = $module_layouts->where('id', $grid_layout_id)->first();

            if ($grid_layout) {
                $data['grid_layout_id'] = $grid_layout_id;
                $data['grid_layout_type'] = 'custom';
                $data['init_layout_type'] = $grid_layout->layout_type;
            }
        } elseif ($grid_layout_id_default) {
            $data['grid_layout_id'] = $grid_layout_id_default;
            $data['grid_layout_type'] = 'default';
            $grid_layout = $module_layouts->where('id', $grid_layout_id_default)->first();
            $data['init_layout_type'] = $grid_layout->layout_type;
        }

        $data['grid_layout_id_default'] = $this->data['module_layouts']->where('global_default', 1)->pluck('id')->first();
        $layout_data = null;
        if ($data['grid_layout_type'] != 'default_new') {
            $data['layout_settings'] = $this->getLayout($data['grid_layout_id']);
            $layout_data = $data['layout_settings']['layout'];
        }

        if (! empty($data['grid_layout_id'])) {
            if (isset($this->data['menu_id'])) {
                $data['sidebar_layouts'] = \Erp::gridViews($this->data['menu_id'], $this->data['module_id'], $data['grid_id'], $data['grid_layout_id']);
            }

            if (session('role_level') == 'Admin') {
                $data['sidebar_reports'] = \Erp::getSidebarReports($this->data['module_id']);
                $data['sidebar_forms'] = \Erp::getSidebarForms($this->data['module_id']);
            }
            if (empty($data['sidebar_layouts'])) {
                $data['layout_count'] = 0;
            } else {
                $data['layout_count'] = count($data['sidebar_layouts']);
            }
            if (empty($data['sidebar_forms'])) {
                $data['form_count'] = 0;
            } else {
                $data['form_count'] = count($data['sidebar_forms']);
            }

            if (empty($data['sidebar_reports'])) {
                $data['report_count'] = 0;
            } else {
                $data['report_count'] = count($data['sidebar_reports']);
            }

            $data['layout_title'] = $this->data['module_layouts']->where('id', $data['grid_layout_id'])->pluck('name')->first();
        }
        $grid_filters = $this->getDefaultGridFilters();

        if (! empty($data['grid_layout_id']) && ! empty($grid_filters['filters'])) {
            foreach ($grid_filters['filters'] as $filter) {
                if (! empty($layout_data->filterSettings->columns)) {
                    foreach ($layout_data->filterSettings->columns as $i => $saved_filter) {
                        if ($saved_filter->field == $filter->field) {
                            unset($layout_data->filterSettings->columns[$i]);
                        }
                    }
                }
            }

            foreach ($grid_filters['filters'] as $filter) {
                $col_filter = (object) [
                    'matchCase' => false,
                    'predicate' => 'and',
                    'field' => $filter->field,
                    'operator' => $filter->filter_operator,
                    'value' => $filter->filter_value,
                ];
                if (! is_array($layout_data->filterSettings->columns) && isset($layout_data->filterSettings->columns) && ! empty($layout_data->filterSettings->columns)) {
                    $arr = [];

                    foreach ($layout_data->filterSettings->columns as $c) {
                        $arr[] = $c;
                    }
                    $layout_data->filterSettings->columns = $arr;
                }
                if (empty($layout_data)) {
                    $layout_data = (object) [];
                }
                if (empty($layout_data->filterSettings)) {

                    $layout_data->filterSettings = (object) ['columns' => []];
                }
                $layout_data->filterSettings->columns[] = $col_filter;

            }
            $layout_data->filterSettings->columns = array_values($layout_data->filterSettings->columns);
        }

        $query_string = '';
        if (! empty(request()->getQueryString())) {
            parse_str(request()->getQueryString(), $query_string);
        }
        $data['query_string'] = json_encode($query_string);

        $data['columns'] = [];
        $data['enable_sorting'] = false;
        $data['group_fields'] = [];
        $data['sort_fields'] = [];

        $data['filter_fields'] = $grid_filters['filters'];
        $data['query_values'] = isset($grid_filters['query_values']) ? $grid_filters['query_values'] : "";
        $data['query_params'] = request()->query();
        $data['detail_columns'] = [];

        $visible_count = 0;
        foreach ($this->data['module_fields'] as $i => $field) {
            $column = null;
            $field_show = $this->gridAccess($field);

            if ($this->data['module_id'] != 588 && ($field['field'] == 'sort_order' || $field['field'] == 'menu_item_order' || $field['field'] == 'dialplan_order' || $field['field'] == 'dialplan_detail_order') && session('role_level') == 'Admin') {
                $data['enable_sorting'] = true;
            }

            if (isset($field['groupby']) && $field_show) {
                $data['group_fields'][] = $field['field'];
            }

            if (isset($field['orderby']) && $field['orderby'] != 'select' && $field_show) {
                $data['sort_fields'][] = ['field' => $field['field'], 'direction' => ($field['orderby'] == 'asc') ? 'ascending' : 'descending'];
            }

            if (isset($field_show))  {
                $visible_count++;
                if ($field['field'] == 'id') {
                    $column = [
                        'field' => $field['field'],
                        'headerText' => $field['label'],
                        'isPrimaryKey' => 'true',
                    ];
                } else {
                    if ($field['field_type'] == 'currency' || $field['field_type'] == 'percentage' || $field['field_type'] == 'integer') {
                        $column = [
                            'field' => $field['field'],
                            'headerText' => $field['label'],
                            // 'clipMode' => 'EllipsisWithTooltip',
                            'textAlign' => 'Right',

                            'type' => 'number',
                        ];
                    } elseif ($field['field_type'] == 'textarea') {
                        $column = [
                            'field' => $field['field'],
                            'headerText' => $field['label'],
                        ];
                    } elseif ($field['field_type'] == 'image') {
                        $column = [
                            'field' => $field['field'],
                            'headerText' => $field['label'],
                            'disableHtmlEncode' => false,
                            'customAttributes' => ['class' => 'imagetd'],
                        ];
                    } elseif ($field['field_type'] == 'file' || $field['field_type'] == 'password_hover' || $field['field_type'] == 'phone_number' || $field['field_type'] == 'email' || $field['field_type'] == 'link') {
                        $column = [
                            'field' => $field['field'],
                            'headerText' => $field['label'],
                            'textAlign' => isset($field['align']) ? $field['align'] : 'left',
                            'disableHtmlEncode' => false,
                        ];
                    } else {
                        $column = [
                            'field' => $field['field'],
                            'headerText' => $field['label'],
                            'textAlign' => isset($field['align']) ? $field['align'] : 'left',
                        ];
                    }
                }
            } elseif ($field_show && ($field['detail_visible'])) {
                $column = [
                    'field' => $field['field'],
                    'headerText' => $field['label'],
                    'visible' => false,
                ];
                if ($field['field_type'] == 'image' || $field['field_type'] == 'file' || $field['field_type'] == 'password_hover' || $field['field_type'] == 'phone_number' || $field['field_type'] == 'email' || $field['field_type'] == 'link') {
                    $column['disableHtmlEncode'] = false;
                }
            }

            if ($field_show && isset($field['detail_visible'])) {
                /*
                if ($frozen_columns == 0) {
                    $data['detail_columns'][] = [
                        'field' => $field['field'],
                        'label' => $field['label'],
                    ];
                }
                */
            }

            if ($field_show) {
                if (! empty($visible_columns) && ! in_array($field['field'], $visible_columns)) {
                    $column['visible'] = false;
                }
            }

            if ($column) {

                if (! empty($field['locked_column'])) {
                    //  $column['lockColumn'] = true;

                    //   $column['customAttributes'] = (object) ['class' => 'locked_col'];
                }
                /*
                $column['allowEditing'] = 'false';
                if($form->formAccess($field['field'])){
                    $column['allowEditing'] = 'true';
                }
                */
                if (! empty($layout_data->columns) && ! in_array($field['field'], $layout_cols)) {
                    $column['visible'] = false;
                    $column['visible_sort_order'] = 1000;
                    $layout_data->columns[] = (object) $column;
                }
                $data['columns'][] = $column;
            }
        }

        $cols = collect(isset($layout_data->columns) ? $layout_data->columns : '');

        $max_visible_sort = $cols->where('visible', 1)->max('index');

        $col_chooser = $cols->where('visible', 0)->sortBy('headerText');
        foreach ($col_chooser as $col) {
            foreach (isset($layout_data->columns) ? $layout_data->columns : [] as $i => $c) {
                if ($col->field == $c->field) {
                    $max_visible_sort++;
                    $layout_data->columns[$i]->sort_order = $max_visible_sort;
                }
            }
        }

        if (! empty($layout_data) && ! empty($layout_data->columns)) {
            $layout_data->columns = sort_grid_columns($layout_data->columns);

            foreach ($layout_data->columns as $i => $col) {
                $layout_data->columns[$i]->index = $i;
                $layout_data->columns[$i]->uid = 'grid-column'.$i;
                //$layout_data->columns[$i]->maxWidth = '250px';
                if (empty($layout_data->columns[$i]->width) && empty($layout_data->columns[$i]->commands)) {
                    $layout_data->columns[$i]->width = '150px';
                    $layout_data->columns[$i]->maxWidth = '250px';
                }
                if (! empty($layout_data->columns[$i]->width)) {
                    $layout_data->columns[$i]->width = str_replace('px', '', $layout_data->columns[$i]->width);
                }
                foreach ($layout_data->filterSettings->columns as $j => $filter_col) {
                    if ($filter_col->field == $col->field) {
                        $layout_data->filterSettings->columns[$j]->uid = 'grid-column'.$i;
                    }
                }
            }
        }
        $data['allow_sort'] = true;
        foreach ($this->data['module_fields'] as $i => $field) {
            if ($field['field'] == 'sort_order') {
                if (isset($layout_data->sortSettings)) {
                    unset($layout_data->sortSettings);
                    //$data['allow_sort'] = false;
                }
            }
        }

        unset($data['module_fields']);

        $data['group_fields'] = [];
        $data['layout_settings']['layout'] = $layout_data;

        return $data;
    }

    public function formatGridData($rows)
    {
        if (empty($rows) || (is_array($rows) && count($rows) == 0)) {
            return $rows;
        }
        $formatted_rows = [];

        if (! is_main_instance() && ! check_access('1,31') && $this->data['db_table'] == 'erp_users') {
            foreach ($rows as $i => $row) {
                if ($row->username == 'superuser') {
                    unset($rows[$i]);
                }
            }
        }

        if (empty($rows) || (is_array($rows) && count($rows) == 0)) {
            return $rows;
        }

        $ledger_tables = \DB::connection('default')->table('acc_doctypes')->pluck('doctable')->unique()->toArray();
        $format_doctypes = false;
        if (in_array($this->data['db_table'], $ledger_tables)) {
            $format_doctypes = true;
            $doctypes = \DB::connection('default')->table('acc_doctypes')->get();
            $doctype_labels = [];
            foreach ($doctypes as $d) {
                if (! empty($d->doctype_label)) {
                    $doctype_labels[$d->doctype] = $d->doctype_label;
                } else {
                    $doctype_labels[$d->doctype] = $d->doctype;
                }
            }
        }

        foreach ($rows as $row) {
            $formatted_row = [];
            $formatted_row['rowId'] = $row->{$this->data['db_key']};
            foreach ($this->data['module_fields'] as $field) {
                $col_name = strtolower($field['field']);

                if ($this->data['connection'] != 'default') {
                    if ($field['field'] == 'account_id') {
                        /*
                        if (empty($row->{$field['field']})) {
                            $formatted_row[$field['field']] = '';
                        } else {
                            $formatted_row[$field['field']] = dbgetaccountcell($row->{$col_name}, 'company');
                        }
                        */
                        $formatted_row[$field['field']] = $this->formatField($row->{$col_name}, $field, $row);
                    } elseif ($field['field'] == 'partner_id') {
                        if (empty($row->{$field['field']})) {
                            $formatted_row[$field['field']] = '';
                        } else {
                            $formatted_row[$field['field']] = dbgetaccountcell($row->{$col_name}, 'company');
                        }
                    } else {
                        $formatted_row[$field['field']] = $this->formatField($row->{$col_name}, $field, $row);
                    }
                } else {
                    $formatted_row[$field['field']] = $this->formatField($row->{$col_name}, $field, $row);
                }
            }
            if ($format_doctypes) {
                $formatted_row['doctype'] = $doctype_labels[$row->doctype];
            }

            $formatted_rows[] = $formatted_row;
        }

        return $formatted_rows;
    }

    public function formatAgGridData($rows)
    {

        if (empty($rows) || (is_array($rows) && count($rows) == 0)) {
            return $rows;
        }

        $format_grid_rows_function = \DB::connection('default')->table('erp_form_events')
            ->where('module_id', $this->data['module_id'])
            ->where('type', 'format_grid_rows')
            ->pluck('function_name')->first();
        if ($format_grid_rows_function && function_exists($format_grid_rows_function)) {

            $rows = $format_grid_rows_function($rows);
        }

        $formatted_rows = [];

        if (! is_main_instance() && ! check_access('1,31') && $this->data['db_table'] == 'erp_users') {
            foreach ($rows as $i => $row) {
                if ($row->username == 'superuser') {
                    unset($rows[$i]);
                }
            }
        }

        if (empty($rows) || (is_array($rows) && count($rows) == 0)) {
            return $rows;
        }

        $ledger_tables = \DB::connection('default')->table('acc_doctypes')->pluck('doctable')->unique()->toArray();
        $format_doctypes = false;
        if (in_array($this->data['db_table'], $ledger_tables)) {
            $format_doctypes = true;
            $doctypes = \DB::connection('default')->table('acc_doctypes')->get();
            $doctype_labels = [];
            foreach ($doctypes as $d) {
                if (! empty($d->doctype_label)) {
                    $doctype_labels[$d->doctype] = $d->doctype_label;
                } else {
                    $doctype_labels[$d->doctype] = $d->doctype;
                }
            }
        }

        foreach ($rows as $i => $row) {

            $formatted_row = [];
            $formatted_row['rowId'] = $row->{$this->data['db_key']};
            if (! $formatted_row['rowId']) {
                $formatted_row['rowId'] = $i;
            }
            foreach ($this->data['module_fields'] as $field) {

                if ($field['hide_column']) {
                    continue;
                }

                //  if(in_array($field['field'],['created_at','created_by','updated_at','updated_by'])){
                // continue;
                //  }

                $col_name = $field['field'];
                $field_key = $field['field'];
                if ($field['field_type'] == 'select_module') {
                    $formatted_row[$field['field']] = $row->{$col_name};
                    $field_key = 'join_'.$field['field'];
                }
                // select dbkey with db display for treedata module
                if ($this->data['tree_data_key'] == $field['field']) {
                    $formatted_row[$field['field']] = $row->{$field['field']};
                }

                if ($this->data['connection'] != 'default') {
                    if ($field['field'] == 'account_id') {
                        /*
                        if (empty($row->{$field['field']})) {
                            $formatted_row[$field['field']] = '';
                        } else {
                            $formatted_row[$field['field']] = dbgetaccountcell($row->{$col_name}, 'company');
                        }
                        */
                        $formatted_row[$field_key] = $this->formatAgField($row->{$col_name}, $field, $row);
                    } elseif ($field['field'] == 'partner_id') {
                        if (empty($row->{$field_key})) {
                            $formatted_row[$field_key] = '';
                        } else {
                            $formatted_row[$field_key] = dbgetaccountcell($row->{$col_name}, 'company');
                        }
                    } else {
                        $formatted_row[$field_key] = $this->formatAgField($row->{$col_name}, $field, $row);
                    }
                } else {
                    $formatted_row[$field_key] = $this->formatAgField($row->{$col_name}, $field, $row);
                }
            }

            if ($format_doctypes) {
                $formatted_row['doctype'] = $doctype_labels[$row->doctype];
            }

            $formatted_rows[] = (object) $formatted_row;
        }

        return $formatted_rows;
    }

    private function formatAgField($value, $field, $row)
    {

        switch ($field['field_type']) {

            case 'date':

                // if ($value != null && isTimestamp($value)) {
                //     $value = date('Y-m-d', $value);
                // } else {
                $value = (empty($value) || $value == '0000-00-00') ? '' : date('Y-m-d', strtotime($value));
                // }

                break;

            case 'boolean':
                if ($value === 1 || $value === 'true' || $value === true) {
                    return 1;
                } else {
                    return 0;
                }
                break;

            case 'unixtime':

                if ($value != null && isTimestamp($value)) {
                    $value = date('Y-m-d H:i:s', $value);
                } else {
                    $value = (empty($value) || $value == '0000-00-00 00:00:00') ? '' : date('Y-m-d H:i:s', strtotime($value));
                }

                break;

            case 'datetime':
                // if ($value != null && isTimestamp($value)) {
                //     $value = date('Y-m-d H:i:s', $value);
                // } else {
                $value = (empty($value) || $value == '0000-00-00 00:00:00') ? '' : date('Y-m-d H:i:s', strtotime($value));
                // }
                break;

            case 'phone_number':

                break;
            case 'integer':
                $value = intval($value);
                break;
            case 'decimal':
                $value = floatval(number_format((float) $value, 2, '.', ''));
                break;
            case 'select_function':
                if ($this->data['connection'] == 'pbx') {

                    if (! empty($field['opts_function'])) {
                        $fn = $field['opts_function'];

                        $fn_row = (array) $row;
                        $options = $fn($fn_row, $this->data['connection']);
                        if (array_is_assoc($options)) {
                            $value = $options[$value];
                        }
                    }
                }
                break;
            case 'select_module':

                if (! empty($field['opts_multiple'])) {
                    $multi_values = [];
                    $multi_lookup_values = explode(',', $value);

                    foreach ($multi_lookup_values as $mlv) {
                        if (! empty($mlv)) {
                            $multi_values[] = $this->formatDBField($mlv, $field);
                        }
                    }

                    $value = $multi_values;
                } else {
                    $value = trim(rtrim($row->{'join_'.$field['field']}, ' - '));
                }
                if (empty($value)) {
                    $value = '';
                }

                if (empty($field['opts_multiple']) && $field['opt_module_id']) {
                    $display_vals = explode(',', $field['opt_db_display']);
                    $has_foreign_field = false;
                    $value_arr = explode(' - ', $value);
                    foreach ($display_vals as $i => $v) {
                        if (str_ends_with($v, '_id')) {
                            $field_conf = \DB::table('erp_module_fields')->where('module_id', $field['opt_module_id'])->where('field', $v)->get()->first();
                            $foreign_display_vals = explode(',', $field_conf->opt_db_display);
                            $display_foreign_vals = [];
                            foreach ($foreign_display_vals as $j => $fdv) {
                                $foreign_val = \DB::table($field_conf->opt_db_table)->where($field_conf->opt_db_key, $value_arr[$i])->pluck($fdv)->first();

                                $display_foreign_vals[] = $foreign_val;
                            }
                            $value_arr[$i] = implode(' - ', $display_foreign_vals);
                        }
                    }
                    $value = implode(' - ', $value_arr);
                }

                break;
            case 'file':
                $value = str_replace('#', '%23', $value);
                break;
            case 'ticket_attachment':

                $files = json_decode($value);

                if (! empty($files)) {
                    $files_html = [];
                    foreach ($files as $file) {
                        $files_html[] = '<a target="new" href="'.$file[1].'"> '.$file[0].' </a> ';
                    }
                    $value = implode(' ', $files_html);
                }
                break;
                /*
                case 'file':
                        $value = (!empty($value)) ? formio_get_file_original_name($field['id'], $value) : $value;
                break;
                */
            default:
                $value = $value;
                break;
        }

        return $value;
    }

    private function formatField($value, $field, $row)
    {
        switch ($field['field_type']) {
            case 'text':
                $value = nl2br($value);
                if ($field['field'] == 'message') {
                    $value = strip_tags($value);
                }

                $value = strip_tags($value);
                break;

            case 'textarea':
                $value = nl2br($value);
                break;

            case 'link':
                if (! empty($value)) {
                    if (str_starts_with($value, '<a')) {
                        $value = $value;
                    } elseif (str_starts_with($value, 'http')) {
                        $value = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
                    } else {
                        $value = '<a href="http://'.$value.'" target="_blank">'.$value.'</a>';
                    }
                }
                break;

            case 'email':
                if (! empty($value)) {
                    if ($this->data['module_id'] == 343) {
                        $value = '<a href="/email_form/default/'.$row->id.'/'.$value.'" target="_blank" data-target="form_modal">'.$value.'</a>';
                    } else {
                        $value = '<a href="mailto:'.$value.'" target="_blank">'.$value.'</a>';
                    }
                }
                break;

            case 'phone_number':
                if (! empty($value) && session('role_level') == 'Admin') {
                    if ($this->data['module_id'] == 343) {
                        $value = '<a href="javascript:void(0);" onclick="gridAjax(\'/pbx_call/'.$value.'/'.$row->id.'\')">'.$value.'</a>';
                    } elseif ($row->account_id) {
                        $value = '<a href="javascript:void(0);" onclick="gridAjax(\'/pbx_call/'.$value.'/'.$row->account_id.'\')">'.$value.'</a>';
                    }
                }
                break;

            case 'password_hover':
                $value = '<span 
                onmouseover="this.innerHTML=\''.$value.'\';"
                onmouseout="this.innerHTML=\'***********\';">
                ***********
                </span>';
                break;

            case 'image':
                $links = '';
                $files = explode(',', $value);
                $module_name = strtolower(str_replace(' ', '_', $this->data['name']));

                foreach ($files as $file) {
                    if (! empty($file) && file_exists(uploads_path($this->data['module_id']).$file)) {
                        $links .= "<img src='".uploads_url($this->data['module_id']).$file."' class='gridimage' height='10px' style='margin-left:10px' /> ";
                    }
                }
                $value = $links;
                break;

            case 'file':
                $links = '';
                $files = explode(',', $value);
                $module_name = strtolower(str_replace(' ', '_', $this->data['name']));

                foreach ($files as $file) {
                    $filename = \DB::connection('default')->table('erp_form_files')->where('name', $file)->pluck('original_name')->first();

                    if (! empty($file) && file_exists(uploads_path($this->data['module_id']).$file)) {
                        $links .= '<a target="new" href="'.uploads_url($this->data['module_id']).$file.'"> '.$filename.' </a> ';
                    }
                    if ($this->data['module_id'] == 365 && ! empty($file) && file_exists(attachments_path().$file)) {
                        $links .= '<a target="new" href="'.attachments_url().$file.'"> '.$filename.' </a> ';
                    }
                }

                $value = $links;
                break;

            case 'boolean':
                if ($value === 'true' || $value === 'false') {
                    $value = ($value === 'true') ? 'Yes' : 'No';
                } else {
                    $value = (! empty($value)) ? 'Yes' : 'No';
                }
                break;

            case 'date':
                if (isDateTime($value)) {
                    $value = date('Y-m-d', $value);
                } else {
                    $value = (empty($value) || $value == '0000-00-00') ? '' : date('Y-m-d', strtotime($value));
                }
                break;

            case 'datetime':
                if (isDateTime($value)) {
                    $value = date('Y-m-d H:i:s', $value);
                } else {
                    $value = (empty($value) || $value == '0000-00-00 00:00:00') ? '' : date('Y-m-d H:i:s', strtotime($value));
                }
                break;

            case 'integer':
                $value = intval($value);
                break;
            case 'decimal':
                $value = number_format((float) $value, 2, '.', '');
                break;

            case 'currency':
                if ($field['currency_decimals'] == 0) {
                    $value = intval($value);
                } elseif ($field['currency_decimals']) {
                    $value = currency($value, $field['currency_decimals']);
                } else {
                    $value = currency($value);
                }

                break;

            case 'percentage':
                $value = currency($value).' %';
                break;
            case 'select_module':

                if (! empty($field['opts_multiple'])) {
                    $multi_values = [];
                    $multi_lookup_values = explode(',', $value);

                    foreach ($multi_lookup_values as $mlv) {
                        if (! empty($mlv)) {
                            $multi_values[] = $this->formatDBField($mlv, $field);
                        }
                    }

                    $value = implode(',', $multi_values);
                } else {
                    $value = trim(rtrim($row->{'join_'.$field['field']}, ' - '));
                }
                if (empty($value)) {
                    $value = '';
                }
                break;
            case 'function':
                $val = $field['format_value'];

                $c = explode('|', $val);

                if (isset($c[0]) && function_exists($c[0])) {
                    $args = explode(':', $c[1]);
                    $params = [];
                    foreach ($args as $arg) {
                        $params[] = $row->{$arg};
                    }
                    if (count($params) == 1) {
                        $value = call_user_func($c[0], $params[0]);
                    } else {
                        $value = call_user_func_array($c[0], $params);
                    }
                }
                break;
            case 'ticket_attachment':
                $files = json_decode($value);
                // aa($value);
                // aa($files);
                foreach ($files as $file) {
                    $links .= '<a target="new" href="'.$file[1].'"> '.$file[0].' </a> ';
                }
                break;

            default:

                $value = $value;
                break;
        }

        return $value;
    }

    private function formatDBField($val, $field)
    {
        $formatted_val = '';

        $select_fields = explode(',', $field['opt_db_display']);

        $query = \DB::connection($this->data['connection'])->table($field['opt_db_table']);

        $query->select($select_fields);
        $query->where($field['opt_db_key'], $val);
        $result = $query->get()->first();

        foreach ($select_fields as $select_field) {
            $formatted_val .= $result->{$select_field}.' - ';
        }

        if ($field['opt_db_table'] == 'erp_cruds' && ! empty($formatted_val)) {
            $text_val = ucwords(str_replace('_', ' ', $formatted_val));
        }

        return trim(rtrim($formatted_val, ' - '));
    }

    private function gridAccess($field)
    {
        if (session('role_level') == 'Admin' && (is_superadmin() || is_dev())) {
            return true;
        }
        if (! empty($field['level_access'])) {
            $role_levels = explode(',', $field['level_access']);

            if (! in_array(session('role_level'), $role_levels)) {
                return false;
            }

        }

        return true;
    }

    public function formAccess($field, $import_form = false)
    {

        if (! empty($field['level_access'])) {
            $level_access = explode(',', $field['level_access']);
            $role_level = session('role_level');
            $role_id = session('role_id');

            $remove_field = 0;
            if (is_superadmin()) {
                $role_level = 'Superadmin';
            }
            if ($role_level == 'Superadmin') {
                $remove_field = 1;
                if (in_array('Admin', $level_access) || in_array('Superadmin', $level_access)) {
                    $remove_field = 0;
                }
            } else {
                if (! in_array($role_level, $level_access)) {
                    $remove_field = 1;
                }
            }

            if ($remove_field) {
                return false;
            }
        }

        return true;
    }

    private function getDefaultGridFilters()
    {
        $get_filters = $this->params;

        $query_values = [];
        $filters = [];
        if (! empty($this->data['module_fields'])) {
            foreach ($this->data['module_fields'] as $i => $field) {
                $filter = null;

                $field_show = $this->gridAccess($field);
                if (! $field_show) {
                    continue;
                }

                if (! empty($get_filters)) {
                    foreach ($get_filters as $key => $val) {
                        if ($val == 'All') {
                            continue;
                        }

                        if ($field['field'] == $key) {
                            if ($this->data['module_id'] == 334 && $key == 'product_id') {
                                $filter = (object) [
                                    'field' => $key,
                                    'filter_operator' => 'contains',
                                    'filter_value' => $val,
                                ];

                                $filters[] = $filter;
                                if (! empty($text_val)) {
                                    $query_values[$key] = $text_val;
                                }
                            } else {
                                if (($key == 'reseller_user' || str_ends_with($key, 'id')) && is_numeric($val)) {
                                    if (! empty($field['opt_db_table'])) {
                                        $display = explode(',', $field['opt_db_display']);
                                        $total = count($display) - 1;
                                        $text_val = '';
                                        foreach ($display as $i => $lookup) {
                                            $text_val .= \DB::connection($this->data['connection'])->table($field['opt_db_table'])->where($field['opt_db_key'], $val)->pluck($lookup)->first();
                                            if ($i != $total) {
                                                $text_val .= ' - ';
                                            }
                                        }
                                    }
                                }

                                if (str_starts_with($val, 'not')) {
                                    $val = str_replace('not', '', $val);
                                    $filter = (object) [
                                        'field' => $key,
                                        'filter_operator' => 'notequal',
                                        'filter_value' => $val,
                                    ];
                                } else {
                                    $filter = (object) [
                                        'field' => $key,
                                        'filter_operator' => 'equal',
                                        'filter_value' => $val,
                                    ];
                                }

                                $filters[] = $filter;

                                if ($field['opt_db_table'] == 'erp_cruds' && ! empty($text_val)) {
                                    $text_val = ucwords(str_replace('_', ' ', $text_val));
                                }
                                if (! empty($text_val)) {
                                    $query_values[$key] = $text_val;
                                }
                            }
                        }
                    }
                }
            }
        }

        $response = [];
        if (! empty($query_values)) {
            $response['query_values'] = $query_values;
        }
        $response['filters'] = $filters;

        return $response;
    }
}
