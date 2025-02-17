<?php

function format_module_field_conf(&$module_field)
{
    $module_field = (object) $module_field;
    $module_field->opt_db_dependent_fields = collect(explode(',', $module_field->opt_db_dependent_fields))->filter()->toArray();
    $module_field->opt_db_sortorder = collect(explode(',', $module_field->opt_db_sortorder))->filter()->toArray();
    $module_field->opt_db_display = collect(explode(',', $module_field->opt_db_display))->filter()->toArray();
    $module_field->opts_values = collect(explode(',', $module_field->opts_values))->filter()->toArray();
}


function formio_get_module_form_json($module_id)
{
    $form_json = \DB::connection('default')->table('erp_forms')->where('module_id', $module_id)->pluck('form_json')->first();
}

function formio_build_all()
{
    $module_ids = \DB::table('erp_cruds')->pluck('id')->toArray();
    foreach ($module_ids as $module_id) {
        formio_create_form_from_db($module_id);
    }
}

function formio_create_form_from_db($module_id, $is_update = false, $conn ='default')
{
    $current_conn = \DB::getDefaultConnection();
    set_db_connection($conn);
    validate_form_field_types();
    $form_json = (object) [
        "display" => "form",
        "components" => []
    ];
    $db_table = \DB::table('erp_cruds')->where('id', $module_id)->pluck('db_table')->first();

    $tabs = \DB::table('erp_module_fields')->where('module_id', $module_id)->orderBy('sort_order')->pluck('tab')->filter()->unique()->toArray();

    $tabs = array_values($tabs);
    if (count($tabs) > 0) {
        $form_json->components[] = (object) [
            "label" => "Tabs",
            "components" => [],
            "key" => "tabs",
            "type" => "tabs",
            "input" => false,
            "tableView" => false
        ];

        foreach ($tabs as $i => $tab) {
            $tab_components = [];

            $module_fields_query = \DB::table('erp_module_fields')
            ->where('alias', $db_table)
            ->where('module_id', $module_id)
            ->where('tab', $tab)
            ->where('aliased_field', 0);
            
            $module_fields_query->whereIn('visible', ['Add','Edit','Add and Edit']);

            $module_fields = $module_fields_query->where('field_type', 'not like', '%hidden%')->orderBy('sort_order')->get();
          
            $hidden_query = \DB::table('erp_module_fields')
            ->where('alias', $db_table)
            ->where('module_id', $module_id)
            ->where('tab', $tab)
            ->where('aliased_field', 0);
            $hidden_query->where(function ($hidden_query) {
                $hidden_query->where('field_type', 'like', '%hidden%');
                $hidden_query->orWhere('field_type', 'none');
            });
            
            $hidden_query->whereIn('visible', ['Add','Edit','Add and Edit']);
            $hidden_fields = $hidden_query->where('field_type', 'like', '%hidden%')->orderBy('sort_order')->get();

            foreach ($module_fields as $i => $module_field) {
                $field_json = formio_get_dbfield_json($module_field);
                if ($field_json) {
                    $tab_components[] = $field_json;
                }
            }


            $hidden_components = [];
            foreach ($hidden_fields as $module_field) {
                $field_json = formio_get_dbfield_json($module_field);
                if ($field_json) {
                    $hidden_components[] = $field_json;
                }
            }

            if (count($hidden_components) > 0) {
                $hidden_fields_container = (object) [
                    "legend" => "Hidden Fields",
                    "label" => "Hidden Fields",
                    "components" => $hidden_components,
                    "key" => "fieldSet",
                    "type" => "fieldSet",
                    "input" => false,
                    "tableView" => false,
                    "hidden" => true
                ];
                $tab_components[] = $hidden_fields_container;
            }

            $form_json->components[0]->components[] = (object) [
                "label" => $tab,
                "key" => "tab".$i,
                "components" => $tab_components
            ];
        }

        $form_json->components[] = json_decode('{
            "type": "button",
            "label": "Submit",
            "key": "submit",
            "disableOnInvalid": true,
            "input": true,
            "size": "sm",
            "customClass": "float-right mr-2",
            "tableView": false
        }');
    } else {
        $module_fields = \DB::table('erp_module_fields')->where('alias', $db_table)->where('module_id', $module_id)->where('aliased_field', 0)->orderBy('sort_order')->get();
        foreach ($module_fields as $module_field) {
            $field_json = formio_get_dbfield_json($module_field);
            if ($field_json) {
                $form_json->components[] = $field_json;
            }
        }

        $form_json->components[] = json_decode('{
            "type": "button",
            "label": "Submit",
            "key": "submit",
            "disableOnInvalid": true,
            "input": true,
            "size": "sm",
            "customClass": "float-right mr-2",
            "tableView": false
        }');
    }

    if ($is_update) {
        $data = [
            'form_json' => json_encode($form_json),
        ];
        \DB::table('erp_forms')->where('module_id', $module_id)->update($data);
    } else {
        
      
        $data = [
            'module_id' => $module_id,
            'form_json' => json_encode($form_json),
            'role_id' => 1,
            'is_add' => 1,
            'is_edit' => 1,
            'is_view' => 1,
            'is_delete' => 1,
        ];
        \DB::table('erp_forms')->updateOrInsert(['module_id'=>$module_id,'role_id'=>1], $data);
        $data = [
            'module_id' => $module_id,
            'form_json' => json_encode($form_json),
            'role_id' => 58,
            'is_add' => 1,
            'is_edit' => 1,
            'is_view' => 1,
            'is_delete' => 1,
        ];
        \DB::table('erp_forms')->updateOrInsert(['module_id'=>$module_id,'role_id'=>34], $data);
    }
    
    set_db_connection($current_conn);
}

function formio_get_dbfield_json($module_field)
{
    format_module_field_conf($module_field);


    $json_template = \DB::connection('system')->table('erp_form_field_types')->where('field_type', $module_field->field_type)->pluck('json_template')->first();


    $json_template = str_replace("[label]", $module_field->label, $json_template);
    $json_template = str_replace("[field]", $module_field->field, $json_template);
    $json_template = str_replace("[field_id]", $module_field->id, $json_template);
    $json_template = str_replace("[module_id]", $module_field->module_id, $json_template);



    $field_json = json_decode($json_template);
    $field_json->customClass = 'data-tab-'.$module_field->tab.' data-field-'.$module_field->field.' data-id-'.$module_field->id;
    if (str_contains($module_field->field_type, 'select')) {
        $dependent_fields = $module_field->opt_db_dependent_fields;
        $field_json->data = formio_select_datasource($module_field);

        if (!empty($dependent_fields)) {
            if (count($dependent_fields) >= 1) {
                $field_json->refreshOn = $dependent_fields[0];
                $field_json->clearOnRefresh = true;
            }
        }

        if ($module_field->opts_multiple) {
            $field_json->multiple = true;
        }

       // $field_json->lazyLoad = true;
    }
    if ($module_field->field_type=='file' || $module_field->field_type == 'image') {
        if ($module_field->opts_multiple) {
            $field_json->multiple = true;
        }
    }

    if ($module_field->field_type == 'currency' && $module_field->currency_symbol) {
        $field_json->prefix = $module_field->currency_symbol;
    }
    if ($module_field->required) {
        $field_json->validate = (object) ['required' => true];
    }

    if ($module_field->tooltip) {
        $field_json->tooltip = $module_field->tooltip;
    }


    if ($field_json && $module_field->readonly == 'Add and Edit') {
        $field_json->disabled = true;
    }

    if (!empty($module_field->default_value) && $module_field->field_type != 'date' && $module_field->field_type != 'datetime') {
        $field_json->defaultValue = $module_field->default_value;
    }
    
   
    
    

    /// DISPLAY
    if (!empty($module_field->display_logic)) {
        $condition = str_replace('{{', 'data.', $module_field->display_logic);
        $condition = str_replace('}}', '', $condition);
        $field_json->customConditional = "if(".$condition."){\n  show = 1;\n}else{\n  show = 0;\n}\n";
    }

    /// READONLY
    if(!empty($field_json)){
        
    
    $field_json->logic = [];
    if (!empty($module_field->read_only_logic)) {
        $disable_json_template = '{
        "name": "set_disabled_[field]",
        "trigger": {
        "type": "javascript",
        "javascript": "result = ([condition])"
        },
        "actions": [
        {
        "name": "set_field_disabled",
        "type": "property",
        "property": {
        "label": "Disabled",
        "value": "disabled",
        "type": "boolean"
        },
        "state": true
        }]
        }';

        $condition = str_replace('{{', 'data.', $module_field->read_only_logic);
        $condition = str_replace('}}', '', $condition);
        $trigger = str_replace('[field]', $module_field->field, $disable_json_template);
        $trigger = str_replace('[condition]', $condition, $trigger);

        $trigger = json_decode($trigger);


        $field_json->logic[] = $trigger;
    }
    
    if (!empty($module_field->required_logic)) {
        $required_json_template = '{
        "name": "set_required_[field]",
        "trigger": {
        "type": "javascript",
        "javascript": "result = ([condition])"
        },
        "actions": [
        {
        "name": "set_field_required",
        "type": "property",
        "property": {
        "label": "Required",
        "value": "validate.required",
        "type": "boolean"
        },
        "state": true
        }]
        }';

        $condition = str_replace('{{', 'data.', $module_field->required_logic);
        $condition = str_replace('}}', '', $condition);
        
        
        $trigger = str_replace('[field]', $module_field->field, $required_json_template);
        $trigger = str_replace('[condition]', $condition, $trigger);

        $trigger = json_decode($trigger);


        $field_json->logic[] = $trigger;

    }
    

    /// CHANGE
    if (!empty($module_field->change_logic)) {
        if (str_starts_with($module_field->change_logic, 'function|')) {
            $ajax_function = str_replace('function|', '', $module_field->change_logic);
            $change_logic = '
            {
              "name": "change_logic_'.$module_field->field.'",
              "trigger": {
                "type": "event",
                "event": "change_event_'.$module_field->field.'"
              },
              "actions": [
                {
                  "name": "change_action_'.$module_field->field.'",
                  "type": "customAction",
                  "customAction": "var post_data = data;\npost_data.changed_field = component.key;\n$.ajax({\n  method: \"post\",\n  url:\"/form_change_ajax/'.$ajax_function.'\",\n  data: post_data,\n  success:function(response){\n    $.each(response, function(k, v) {\n      if(k != component.key){\n        instance._currentForm.getComponent(k).setValue(v);\n      }\n    });\n  }\n});"
                }
              ]
            }';

            $trigger = json_decode($change_logic);
            
            $field_json->logic[] = $trigger;
        }
    }
    }

    return $field_json;
}

function formio_select_datasource($module_field)
{
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_field->module_id)->get()->first();
    $datasource = (object) ['values' => []];

    if ($module_field->field_type == 'select_custom') {
        $values = $module_field->opts_values;
        $datasource = (object) ['values' => []];
        if (!empty($values)) {
            foreach ($values as $opt) {
                $datasource->values[] = (object) ["label" => ucwords(str_replace('_',' ',$opt)), "value" => $opt];
            }
        }
        
        
    } else {
        if ($module_field->field_type == 'select_function' || !empty($module_field->opt_db_dependent_fields)) {
            $url = '/formio_select_options/'.$module_field->id.'?row_id={{data.'.$module->db_key.'}}';
            $dependent_fields = $module_field->opt_db_dependent_fields;

            if (!empty($dependent_fields)) {
                foreach ($dependent_fields as $dependent_field) {
                    $url .= '&'.$dependent_field.'={{data.'.$dependent_field.'}}';
                }
            }
        } else {
            $url = '/formio_select_options/'.$module_field->id;
        }
        $url = str_replace('http://', 'https://', $url);
        $datasource = (object) ['url' => $url];
    }


    return $datasource;
}

function validate_form_field_types()
{
    $field_types = \DB::table('erp_form_field_types')->pluck('field_type')->toArray();
    \DB::table('erp_module_fields')->whereNotIn('field_type', $field_types)->update(['field_type'=>'text']);
}

function formio_get_events_from_json($form_config, &$events = [], $field_key = false)
{
    $form_config =  json_decode(json_encode($form_config), true);
    foreach ($form_config as $key => $value) {
        if (!empty($value['key'])) {
            $field_key = $value['key'];
        }
        if ($key == 'trigger' && $value['type'] == 'event') {
            $events[$field_key] = $value['event'];
        }
        if (is_array($value)) {
            $events = formio_get_events_from_json($value, $events, $field_key);
        }
    }
    return $events;
}

function formio_get_fields_from_json($form_config, &$fields = [], $field_key = false)
{
    $form_config =  json_decode(json_encode($form_config), true);
    foreach ($form_config as $key => $value) {
        if ($field_key == 'components' && !empty($value['key']) && !empty($value['type'])) {
            if ($value['key'] != 'submit' && $value['key'] != 'tabs' && $value['key'] != 'columns') {
                $fields[$value['key']] = $value;
            }
        }

        if (is_array($value)) {
            $fields = formio_get_fields_from_json($value, $fields, $key);
        }
    }
    return $fields;
}

function formio_get_file_original_name($field_id, $value)
{
    if (empty($value)) {
        return [];
    }
    $field = \DB::connection('default')->table('erp_module_fields')->where('id', $field_id)->get()->first();
    $destinationPath = uploads_path($field->module_id);

    $file_names = explode(',', $value);
    $files = [];

    foreach ($file_names as $file_name) {
        $file_path = $destinationPath.'/'.$file_name;
        $file_info = \DB::connection('default')->table('erp_form_files')->where('field_id', $field_id)->where('name', $file_name)->get()->first();
        if (!empty($file_info)) {
            $file_data = [

                'name' => $file_info->name,

                'originalName' => $file_info->original_name,
            ];
            $files[] = $file_data;
        } else {
            $file_data = [

                'name' => $file_name,

                'originalName' => $file_name
            ];
            $files[] = $file_data;
        }
    }

    return $files;
}

function formio_get_file_info($field_id, $value)
{
    $field = \DB::connection('default')->table('erp_module_fields')->where('id', $field_id)->get()->first();
    $destinationPath = uploads_path($field->module_id);

    $file_names = explode(',', $value);
    $files = [];

    foreach ($file_names as $file_name) {
        $file_path = $destinationPath.'/'.$file_name;
        $file_info = \DB::connection('default')->table('erp_form_files')->where('field_id', $field_id)->where('name', $file_name)->get()->first();
        if (!empty($file_info)) {
            $file_data = [
                'storage' => 'url',
                'name' => $file_info->name,
                'url' => url('/formio_submit_file/'.$field_id.'?form='.$file_info->name),
                'size' => $file_info->file_size,
                'type' => $file_info->file_mime_type,
                'originalName' => $file_info->original_name,
            ];
            $files[] = $file_data;
        } else {
            $file_data = [
                'storage' => 'url',
                'name' => $file_name,
                'url' => url('/formio_submit_file/'.$field_id.'?form='.$file_name),
                'size' => filesize($file_path),
                'type' => mime_content_type($file_path),
                'originalName' => $file_name
            ];
            $files[] = $file_data;
        }
    }

    return $files;
}

function formio_form_data_remove_defaults($form_type, $form_json, $form_data, $module_fields)
{
    $form_json = json_decode($form_json);
    $form_json_array =  json_decode(json_encode($form_json), true);
    
    foreach ($module_fields as $j => $field) {
        if($field['module_id'] == 1837){
            $form_data['total'] = 0;    
        }
    }

    foreach ($form_data as $key => $val) {
        if (!empty($val) || $form_type == 'edit') {
            $form_component_data = formio_json_find_component($form_json, $key);
            $component = $form_component_data['component'];
            unset($component['defaultValue']);
            formio_set_json_val($form_component_data['path'], $form_json_array, $component);
        }
        if ($form_type == 'edit') {
            
            foreach ($module_fields as $j => $field) {
               
                if (!empty($field['row_data_currency']) && !empty($form_data[$field['row_data_currency']]) 
                && $field['field_type'] == 'currency' && $key == $field['field']) {
                   
                    $form_component_data = formio_json_find_component($form_json, $key);
                    $component = $form_component_data['component'];
                    if($form_data[$field['row_data_currency']] == 'USD'){
                        $component['decimalLimit'] = 3;
                        $component['prefix'] = '$';
                    }  
                   
                    if($form_data[$field['row_data_currency']] == 'ZAR'){
                        $component['decimalLimit'] = 2;
                        $component['prefix'] = 'R';
                    }  
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
              
                
                if (!empty($field['row_data_currency']) && $field['module_id'] == 588 
                && $field['field_type'] == 'currency' && $key == $field['field']) {
                    $currency = \DB::connection('pbx')->table('p_rates_partner')->select('currency')->where('id',$form_data['ratesheet_id'])->pluck('currency')->first();
                    $form_component_data = formio_json_find_component($form_json, $key);
                    $component = $form_component_data['component'];
                    
                    if($currency == 'USD'){
                        $component['decimalLimit'] = 3;
                        $component['prefix'] = '$';
                    }  
                   
                    if($currency == 'ZAR'){
                        $component['decimalLimit'] = 2;
                        $component['prefix'] = 'R';
                    }  
                   
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
                
                if (!empty($field['row_data_currency']) && $field['module_id'] == 1837 
                && $field['field_type'] == 'currency' && $key == $field['field']) {
                    $currency = \DB::connection('default')->table('acc_cashbook')->select('currency')->where('id',$form_data['cashbook_id'])->pluck('currency')->first();
                    $form_component_data = formio_json_find_component($form_json, $key);
                    $component = $form_component_data['component'];
                    
                    if($currency == 'USD'){
                        $component['decimalLimit'] = 3;
                        $component['prefix'] = '$';
                    }  
                   
                    if($currency == 'ZAR'){
                        $component['decimalLimit'] = 2;
                        $component['prefix'] = 'R';
                    }  
                   
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
                
                if (!empty($field['row_data_currency']) && $field['module_id'] == 508 
                && $field['field_type'] == 'currency' && $key == $field['field']) {
                    $currency = \DB::connection('default')->table('crm_pricelists')->select('currency')->where('id',$form_data['pricelist_id'])->pluck('currency')->first();
                    $form_component_data = formio_json_find_component($form_json, $key);
                    $component = $form_component_data['component'];
                    
                    if($currency == 'USD'){
                        $component['decimalLimit'] = 3;
                        $component['prefix'] = '$';
                    }  
                   
                    if($currency == 'ZAR'){
                        $component['decimalLimit'] = 2;
                        $component['prefix'] = 'R';
                    }  
                   
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
            }
        }else{
            
            
            foreach ($module_fields as $j => $field) {
             
                if (!empty($field['row_data_currency']) && $field['module_id'] == 1837 
                && $field['field_type'] == 'currency' && $key == $field['field']) {
                    
                    $currency = \DB::connection('default')->table('acc_cashbook')->select('currency')->where('id',$form_data['cashbook_id'])->pluck('currency')->first();
                  
                    $form_component_data = formio_json_find_component($form_json, $key);
                    $component = $form_component_data['component'];
                    
                    if($currency == 'USD'){
                        $component['decimalLimit'] = 3;
                        $component['prefix'] = '$';
                    }  
                   
                    if($currency == 'ZAR'){
                        $component['decimalLimit'] = 2;
                        $component['prefix'] = 'R';
                    }  
              
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }  
            }
        }
    }

    return json_encode($form_json_array);
}

function formio_updated_tabs_from_layout($module_id, $layout_id, $module_fields, $is_detail_layout = false)
{
    try {
        $layout = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->get()->first();
        $form_json = (object) [
        "display" => "form",
        "components" => []
    ];
        $db_table = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->pluck('db_table')->first();
        if($layout->hide_auto_form_tabs){
            $tabs = ['Basic'];
        }else{
            $tabs = ['Basic','Advanced'];
        }
        $hidden_field_types = \DB::connection('default')->table('erp_module_fields')->where('field_type', 'like', '%hidden%')->groupBy('field_type')->pluck('field_type')->filter()->unique()->toArray();
        
        
        $module_tabs = \DB::connection('default')->table('erp_module_fields')->whereNotIn('tab', ['Basic','Advanced'])->where('module_id', $module_id)->orderBy('sort_order')->pluck('tab')->filter()->unique()->toArray();
        if(!$layout->hide_auto_form_tabs){
            foreach ($module_tabs as $module_tab) {
                $tabs[] = $module_tab;
            }
        }
        $tabs = array_values($tabs);

        $layout_state = (!$is_detail_layout) ? $layout->aggrid_state : $layout->detail_aggrid_state;

        if ($layout_state) {
            $state = json_decode($layout_state);
            if ($state && $state->colState) {
                $sorted_columns = [];
                $last_sort = 0;
                foreach ($module_fields as $j => $field) {
                    $field_name = $field['field'];
                    if ($field['field_type'] == 'select_module') {
                        $field_name = 'join_'.$field_name;
                    }
                    
                    foreach ($state->colState as $i => $col) {
                        if ($col->colId == $field_name) {
                            $sorted_columns[] = $field['field'];
                            if ($field['tab'] == 'Basic' && $col->hide === "true") {
                                $module_fields[$j]['tab'] = 'Advanced';
                            }
                            if ($field['tab'] != 'Basic' && $col->hide === "false") {
                            //    $module_fields[$j]['tab'] = 'Basic';
                            }

                            $module_fields[$j]['sort_order'] = $i;
                            $last_sort = $i;
                        }
                    }
                }
            
                foreach ($module_fields as $j => $field) {
                    if(!in_array($field['field'], $sorted_columns)){
                     
                        $last_sort++;
                        $module_fields[$j]['sort_order'] = $last_sort;
                    }
                }
            }

        }
     
        foreach ($module_fields as $i => $data) {
            $module_fields[$i] = (object) $data;
        }
        $module_fields = collect($module_fields);


        $form_json->components[] = (object) [
        "label" => "Tabs",
        "components" => [],
        "key" => "tabs",
        "type" => "tabs",
        "input" => false,
        "tableView" => false
    ];

        foreach ($tabs as $i => $tab) {
            $tab_components = [];



            $tab_fields = $module_fields->where('alias', $db_table)->where('tab', $tab)->whereIn('visible', ['Add','Edit','Add and Edit'])->whereNotIn('field_type', $hidden_field_types)->sortBy('sort_order');

            $hidden_tab_fields = $module_fields->where('alias', $db_table)->where('tab', $tab)->whereIn('field_type', $hidden_field_types)->sortBy('sort_order');



            foreach ($tab_fields as $i => $module_field) {
                $field_json = formio_get_dbfield_json($module_field);
                if ($field_json) {
                    $tab_components[] = $field_json;
                }
            }

            

            $hidden_components = [];
            foreach ($hidden_tab_fields as $module_field) {
                $field_json = formio_get_dbfield_json($module_field);
                if ($field_json) {
                    $hidden_components[] = $field_json;
                }
            }
            
            if($layout->hide_auto_form_tabs){
            
                $hidden_auto_tab_fields = $module_fields->where('alias', $db_table)->where('tab', '!=', $tab)->sortBy('sort_order');
            
                foreach ($hidden_auto_tab_fields as $module_field) {
                    $field_json = formio_get_dbfield_json($module_field);
                    if ($field_json) {
                        $hidden_components[] = $field_json;
                    }
                }
            
            }

            if (count($hidden_components) > 0) {
                $hidden_fields_container = (object) [
                "legend" => "Hidden Fields",
                "label" => "Hidden Fields",
                "components" => $hidden_components,
                "key" => "fieldSet",
                "type" => "fieldSet",
                "input" => false,
                "tableView" => false,
                "hidden" => true
            ];
                $tab_components[] = $hidden_fields_container;
            }

            $form_json->components[0]->components[] = (object) [
            "label" => $tab,
            "key" => "tab".$i,
            "components" => $tab_components
        ];
        }

        $form_json->components[] = json_decode('{
        "type": "button",
        "label": "Submit",
        "key": "submit",
        "disableOnInvalid": true,
        "input": true,
        "size": "sm",
        "customClass": "float-right mr-2",
        "tableView": false
    }');

        return json_encode($form_json);
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_email($ex, 'Error updated form from layout');
        return false;
    }
}
function formio_format_hidden_fields($module_id, $module_fields)
{
    try {
     
        $form_json = (object) [
            "display" => "form",
            "components" => []
        ];
        $db_table = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->pluck('db_table')->first();
      
        $hidden_field_types = \DB::connection('default')->table('erp_module_fields')->where('field_type', 'like', '%hidden%')->groupBy('field_type')->pluck('field_type')->filter()->unique()->toArray();
        $module_tabs = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->orderBy('sort_order')->pluck('tab')->filter()->unique()->toArray();
        foreach ($module_tabs as $module_tab) {
            $tabs[] = $module_tab;
        }
        $tabs = array_values($tabs);


        foreach ($module_fields as $i => $data) {
            $module_fields[$i] = (object) $data;
        }
        $module_fields = collect($module_fields);


        $form_json->components[] = (object) [
        "label" => "Tabs",
        "components" => [],
        "key" => "tabs",
        "type" => "tabs",
        "input" => false,
        "tableView" => false
    ];

        foreach ($tabs as $i => $tab) {
            $tab_components = [];



            $tab_fields = $module_fields->where('alias', $db_table)->where('tab', $tab)->whereIn('visible', ['Add','Edit','Add and Edit'])->whereNotIn('field_type', $hidden_field_types)->sortBy('sort_order');
 
            $hidden_tab_fields = $module_fields->where('alias', $db_table)->where('tab', $tab)->whereIn('visible', ['Add','Edit','Add and Edit'])->whereIn('field_type', $hidden_field_types)->sortBy('sort_order');



            foreach ($tab_fields as $i => $module_field) {
                $field_json = formio_get_dbfield_json($module_field);
                if ($field_json) {
                    $tab_components[] = $field_json;
                }
            }


            $hidden_components = [];
            foreach ($hidden_tab_fields as $module_field) {
                $field_json = formio_get_dbfield_json($module_field);
                if ($field_json) {
                    $hidden_components[] = $field_json;
                }
            }

            if (count($hidden_components) > 0) {
                $hidden_fields_container = (object) [
                "legend" => "Hidden Fields",
                "label" => "Hidden Fields",
                "components" => $hidden_components,
                "key" => "fieldSet",
                "type" => "fieldSet",
                "input" => false,
                "tableView" => false,
                "hidden" => true
            ];
                $tab_components[] = $hidden_fields_container;
            }

            $form_json->components[0]->components[] = (object) [
            "label" => $tab,
            "key" => "tab".$i,
            "components" => $tab_components
        ];
        }

        $form_json->components[] = json_decode('{
        "type": "button",
        "label": "Submit",
        "key": "submit",
        "disableOnInvalid": true,
        "input": true,
        "size": "sm",
        "customClass": "float-right mr-2",
        "tableView": false
    }');

        return json_encode($form_json);
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_email($ex, 'Error updated form from layout');
        return false;
    }
}


function formio_apply_form_permissions($form_type, $form_json, $module_fields)
{
    $form_json = json_decode($form_json);
    $form_json_array =  json_decode(json_encode($form_json), true);
    $reindex = false;

    $remove_tax_fields = get_admin_setting('remove_tax_fields');
    foreach ($module_fields as $module_field) {
        if ($remove_tax_fields && in_array($module_field['field'], ['price_tax','wholesale_price_tax','tax'])) {
            $form_component_data = formio_json_find_component($form_json, $module_field['field']);
            if ($form_component_data && $form_component_data['component']) {
                $form_component = $form_component_data['component'];

                formio_unset_json_val($form_component_data['path'], $form_json_array);
                $reindex = true;
            }
        } elseif ($form_type == 'add' && (!in_array($module_field['visible'], ['Edit','Add and Edit']))) {
            $form_component_data = formio_json_find_component($form_json, $module_field['field']);
            if ($form_component_data && $form_component_data['component']) {
                $form_component = $form_component_data['component'];

                formio_unset_json_val($form_component_data['path'], $form_json_array);
                $reindex = true;
            }
        } elseif ($form_type == 'edit' && (!in_array($module_field['visible'], ['Edit','Add and Edit']))) {
            $form_component_data = formio_json_find_component($form_json, $module_field['field']);
            if ($form_component_data && $form_component_data['component']) {
                $form_component = $form_component_data['component'];

                formio_unset_json_val($form_component_data['path'], $form_json_array);
                $reindex = true;
            }
        } elseif (!empty($module_field['level_access'])) {
            $level_access = explode(',', $module_field['level_access']);
            $role_level = session('role_level');
            $role_id = session('role_id');

            if (check_access('1') && !empty(request()->form_role_id)) {
                $form_role = \DB::connection('default')->table('erp_user_roles')->where('id', request()->form_role_id)->get()->first();
                $role_level = $form_role->level;
                $role_id = $form_role->id;
            }

            $remove_field = false;
            if (is_superadmin() || is_dev()) {
                $role_level = 'Superadmin';
            }
            if ($role_level == 'Superadmin') {
                $remove_field = true;
                if (in_array('Admin', $level_access) || in_array('Superadmin', $level_access)) {
                    $remove_field = false;
                }
            } else {
                if (!in_array($role_level, $level_access)) {
                    $remove_field = true;
                }
            }

            if ($remove_field) {
                $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                if ($form_component_data && $form_component_data['component']) {
                    $form_component = $form_component_data['component'];
                    formio_unset_json_val($form_component_data['path'], $form_json_array);
                    $reindex = true;
                }
            }else{
                
                if ($form_type == 'edit' && ($module_field['readonly'] == 'Edit' || $module_field['readonly'] == 'Add and Edit')) {
                    $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                    $component = $form_component_data['component'];
                    $component['disabled'] = true;
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
                if ($form_type == 'add' && ($module_field['readonly'] == 'Add' || $module_field['readonly'] == 'Add and Edit')) {
                    $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                    $component = $form_component_data['component'];
                    $component['disabled'] = true;
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
                if ($module_field['required']) {
                    $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                    $component = $form_component_data['component'];
                    $component['required'] = true;
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
    
                if (str_contains($module_field['field_type'], 'select')) {
                    $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                    $component = $form_component_data['component'];
                    //$component['lazyLoad'] = true;
                    formio_set_json_val($form_component_data['path'], $form_json_array, $component);
                }
            }
        } else {
            if ($form_type == 'edit' && ($module_field['readonly'] == 'Edit' || $module_field['readonly'] == 'Add and Edit')) {
                $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                $component = $form_component_data['component'];
                $component['disabled'] = true;
                formio_set_json_val($form_component_data['path'], $form_json_array, $component);
            }
            if ($form_type == 'add' && ($module_field['readonly'] == 'Add' || $module_field['readonly'] == 'Add and Edit')) {
                $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                $component = $form_component_data['component'];
                $component['disabled'] = true;
                formio_set_json_val($form_component_data['path'], $form_json_array, $component);
            }
            if ($module_field['required']) {
                $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                $component = $form_component_data['component'];
                $component['required'] = true;
                formio_set_json_val($form_component_data['path'], $form_json_array, $component);
            }

            if (str_contains($module_field['field_type'], 'select')) {
                $form_component_data = formio_json_find_component($form_json, $module_field['field']);
                $component = $form_component_data['component'];
                //$component['lazyLoad'] = true;
                formio_set_json_val($form_component_data['path'], $form_json_array, $component);
            }
        }
        
        $form_json = $form_json_array;
    }

    //remove empty tabs

    foreach ($form_json_array["components"][0]["components"] as $i => $tab) {
        $component_count = 0;
        if (count($tab['components']) > 0) {
            $component_count++;
        }


        if ($component_count == 0) {
            formio_unset_json_val("components/0/components/".$i, $form_json_array);
            $reindex = true;
        }
        if ($tab['components'][0]['legend'] == 'Hidden Fields' && count($tab['components']) == 1) {
            formio_unset_json_val("components/0/components/".$i, $form_json_array);
            $reindex = true;
        }
    }

    if ($reindex) {
        $form_json_array = formio_reindex_json($form_json_array);
    }

    return $form_json_array;
}




function formio_reindex_json($array)
{
    $index = 0;
    $return = [];

    foreach ($array as $key => $value) {
        if (is_string($key)) {
            $newKey = $key;
        } else {
            $newKey = $index;
            ++$index;
        }

        $return[$newKey] = is_array($value) ? formio_reindex_json($value) : $value;
    }

    // Sort alphabetically, numeric first then alpha
    ksort($return, SORT_NATURAL);

    return $return;
}

function formio_format_form_data($form_type, $form_data, $module_fields, $db_table, $db_key)
{
    if ($form_type == 'add') {
        foreach ($module_fields as $module_field) {
            $field = $module_field['field'];
            if (($db_table == 'v_ring_groups' && $field == 'ring_group_extension')
            || ($db_table == 'v_ivr_menus' && $field == 'ivr_menu_extension')
            || ($db_table == 'v_extensions'  && $field == 'extension')) {
                $readonly = 'Add and Edit';
                if (!empty(request()->domain_uuid)) {
                    $form_data[$field] = pbx_generate_extension(request()->domain_uuid, 900);
                }
            }


            if (!empty(request()->{$field}) && $field != 'layout_id' && $field != $db_key) {
                
                    $form_data[$field] = request()->{$field};
                
            }

            if (!empty($module_field['default_value']) && $module_field['field_type'] == 'date') {
                if ($module_field['default_value'] == 'Now') {
                    $form_data[$field] = date('Y-m-d');
                } else {
                    $form_data[$field] = date('Y-m-d', strtotime($module_field['default_value']));
                }
            } elseif (!empty($module_field['default_value']) && $module_field['field_type'] == 'datetime') {
                if ($module_field['default_value'] == 'Now') {
                    $form_data[$field] = date('Y-m-d H:i:s');
                } else {
                    $form_data[$field] = date('Y-m-d H:i:s', strtotime($module_field['default_value']));
                }
            } elseif ($module_field['default_value'] == 'session_user_id') {
                $form_data[$field] = session('user_id');
            } elseif ($module_field['default_value'] == 'session_sms_account_id') {
                $form_data[$field] = session('sms_account_id');
            } elseif ($module_field['default_value'] == 'session_account_id') {
                $form_data[$field] = session('account_id');
            } elseif (!empty($module_field['default_value'])) {
                if($module_field['field_type'] == 'select_custom'){
                    $opts_values = explode(',',$module_field['opts_values']);
                    if(in_array($module_field['default_value'],$opts_values)){
                        $form_data[$field] = $module_field['default_value'];
                    }
                }else{
                    $form_data[$field] = $module_field['default_value'];
                }
            }elseif($field == 'domain_uuid' && !empty(session('pbx_domain_uuid'))){
                $form_data[$field] = session('pbx_domain_uuid');
            }

            if ($module_field['field'] != 'layout_id' && empty($form_data[$field]) && empty($module_field['default_value']) && !empty(session('filter_model'))) {
                foreach (session('filter_model') as $filter_field => $fv) {
                    $filter_field_db = (str_starts_with($filter_field, 'join_')) ? substr($filter_field, 5) : $filter_field;
                    if ($filter_field_db == $field) {

                        if ($fv->filterType == 'set' && !$module_field['opts_multiple'] && count($fv->values) == 1) {
                            if (str_contains($module_field['field_type'], 'select')) {
                                $field_opts =  get_module_field_options($module_field['module_id'], $module_field['field']);
                                $form_data[$field] = collect($field_opts)->where('text', $fv->values[0])->pluck('value')->first();
                            }
                        }

                        if ($fv->filterType == 'text' && ($fv->type == 'contains' || $fv->type == 'equals') && !empty($fv->filter)) {
                            $form_data[$field] = $fv->filter;
                        }
                    }
                }
            }
        }
        
    } else {
        foreach ($module_fields as $module_field) {
            $field = $module_field['field'];
            if(is_bool($form_data[$field])){
                $form_data[$field] = ($form_data[$field]) ? 1 : 0;
            }else{
                if ($module_field['field_type'] == 'boolean' && $form_data[$field] === "true") {
                    $form_data[$field] = 1;
                }
                if ($module_field['field_type'] == 'boolean' && $form_data[$field] === "false") {
                    $form_data[$field] = 0;
                }
            }
            if ($module_field['field_type'] == 'password') {
                unset($form_data[$field]);
            }

            if (str_contains($module_field['field_type'], 'select') && $module_field['opts_multiple']) {
                $form_data[$field] = explode(',', $form_data[$field]);
                if ($module_field['field_type'] == 'select_module') {
                    $field_opts =  get_module_field_options($module_field['module_id'], $module_field['field']);
                    $selected = [];
                    foreach ($form_data[$field] as $selected_val) {
                        $selected_val = (string) $selected_val;
                        $selected[$selected_val] = (string) $field_opts->where('value', $selected_val)->pluck('text')->first();
                    }
                }
            } elseif (str_contains($module_field['field_type'], 'select') && !$module_field['opts_multiple']) {
                $form_data[$field] = (string) $form_data[$field];
            }
           
        }
    }
    
    $row = $form_data;
    foreach ($module_fields as $module_field) {
        
        $field = $module_field['field'];
        $value_function = $module_field['value_function'];
        if (!empty($value_function) && !empty($row) && function_exists($value_function)) {
            $form_data[$field] = $value_function($row);
        }elseif(!empty($value_function)  && function_exists($value_function)){
            $form_data[$field] = $value_function();
        }
    }


    return $form_data;
}
/*
function formio_update_db_schema_from_json($form_id, $from_json, $changed_keys = false)
{
    $erp = new DBEvent();
    $erp->setTable('erp_instance_migrations');
    $form = \DB::connection('default')->table('erp_forms')->where('id', $form_id)->get()->first();
    if (!$form) {
        return false;
    }
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $form->module_id)->get()->first();
    if (!$module) {
        return false;
    }

    $cols = get_columns_from_schema($module->db_table, null, $module->connection);


    $json_fields = formio_get_fields_from_json(json_decode($form->form_json));

    $json_field_keys =array_keys($json_fields);
    $fields_to_drop = [];
    $fields_to_add = [];

    foreach ($cols as $col) {
        if (!in_array($col, $json_field_keys)) {
            $fields_to_drop[] = $col;
        }
    }

    foreach ($json_field_keys as $json_field_key) {
        if (!in_array($json_field_key, $cols)) {
            $fields_to_add[] = $json_field_key;
        }
    }

    foreach ($fields_to_drop as $drop_field) {
        $data = [
            'action' => 'column_drop',
            'connection' => $module->connection,
            'table_name' => $module->db_table,
            'field_name' => $drop_field,
        ];

        $erp->save($data);
    }

    foreach ($fields_to_add as $add_field) {
        $type = 'Varchar';
        $default_value = '';
        $field_length = '';

        $field_type = $json_fields[$add_field]["type"];

        if ($field_type == 'textarea') {
            $type = 'Text';
        }

        if ($field_type == 'datetime' && isset($json_fields[$add_field]["format"]) && $json_fields[$add_field]["format"] == "yyyy-MM-dd") {
            $type = 'Date';
        }

        if ($field_type == 'datetime' && (!isset($json_fields[$add_field]["format"]) || $json_fields[$add_field]["format"] == "yyyy-MM-dd hh:mm")) {
            $type = 'DateTime';
        }

        if ($field_type == 'datetime' && isset($json_fields[$add_field]["format"]) && $json_fields[$add_field]["format"] == "hh:mm a") {
            $type = 'Time';
        }

        if ($field_type == 'checkbox') {
            $type = 'Tiny Integer';
            $field_length = 1;
            $default_value = 0;
        }

        if ($field_type == 'number') {
            $type = 'Integer';
            $field_length = 11;
            $default_value = 0;
        }

        if ($field_type == 'currency') {
            $type = 'Decimal';
            $default_value = 0;
        }
        if (!empty($field->default_value)) {
            $default_value = $field->default_value;
        }
        $data = [
            'action' => 'column_add',
            'connection' => $module->connection,
            'table_name' => $module->db_table,
            'field_name' => $add_field,
            'field_type' => $type,
            'default_value' => $default_value,
            'field_length' => $field_length
        ];

        $erp->save($data);
    }
    if ($changed_keys) {
        $changed_keys = json_decode($changed_keys);
        foreach ($changed_keys as $key => $new_key) {
            if (!in_array($new_key, $fields_to_add)) {
                $data = [
                    'action' => 'column_rename',
                    'connection' => $module->connection,
                    'table_name' => $module->db_table,
                    'field_name' => $request->field,
                    'new_name' => $request->field,
                ];
                $erp->save($data);
            }
        }
    }
}
*/
function formio_json_update_label($form_config, $field, $label)
{
    $form_config =  json_decode(json_encode($form_config), true);

    foreach ($form_config as $key => $value) {
        if (isset($value['key']) && $value['key'] == $field) {
            $form_config[$key]['label'] = $label;
        }

        if (is_array($value)) {
            $form_config[$key] = formio_json_update_label($value, $field, $label);
        }
    }
    return $form_config;
}


// JSON GETTER AND SETTERS

function formio_json_find_component($json, $field)
{
    $form_json =  json_decode(json_encode($json), true);

    $json = formio_get_flat_json($form_json);

    foreach ($json as $key => $val) {
        if (str_ends_with($key, 'key') && str_contains($key, 'components') && $val == $field) {
            $component_path = substr($key, 0, -4);

            $result = [
                'path' => $component_path,
                'keypath' => $key,
                'component' => formio_get_json_val($component_path, $form_json)
            ];
            return $result;
        }
    }
}

function formio_get_json_val($path, $array)
{
    $path = explode('/', $path); //if needed
    $temp =& $array;

    foreach ($path as $key) {
        $temp =& $temp[$key];
    }
    return $temp;
}

function formio_set_json_val($path, &$array=array(), $value=null)
{
    $path = explode('/', $path); //if needed
    $temp =& $array;

    foreach ($path as $key) {
        $temp =& $temp[$key];
    }
    $temp = $value;
}

function formio_get_flat_json($array, $path="")
{
    $output = array();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $output = array_merge($output, formio_get_flat_json($value, (!empty($path)) ? $path.$key."/" : $key."/"));
        } else {
            $output[$path.$key] = $value;
        }
    }
    return $output;
}

function formio_unset_json_val($path, &$array)
{
    $path = explode('/', $path); //if needed
    $path_count = count($path) - 1;
    $temp =& $array;

    foreach ($path as $i => $key) {
        if ($temp[$key] && $i == $path_count) {
            unset($temp[$key]);
        } else {
            $temp =& $temp[$key];
        }
    }
}

function formio_get_available_fields($module_id, $form_json)
{
    $builder_db_fields = [];
    $module_fields = \DB::table('erp_module_fields')->where('module_id', $module_id)->get();

    foreach ($module_fields as $field) {
        if ($field->field == 'sort_order') {
            // continue;
        }
        if (!str_contains($form_json, '"key":"'.$field->field.'"')) {
            $builder_db_fields[$field->field] =(object)[
                'title' => $field->label,
                'key' => $field->field,
                'icon' => 'terminal',
                'schema' => formio_get_dbfield_json($field)
            ];
        }
    }
    return $builder_db_fields;
}

function formio_get_form_id($module_id)
{
    if (check_access('1') && !empty(request()->form_role_id)) {
        $form_id = \DB::connection('default')->table('erp_forms')->where('module_id', $module_id)->where('role_id', request()->form_role_id)->pluck('id')->first();
    } else {
        $form_id = \DB::connection('default')->table('erp_forms')->where('module_id', $module_id)->whereIn('role_id', session('role_ids'))->pluck('id')->first();
    }

    return $form_id;
}
