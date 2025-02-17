<?php



function add_indexes_to_module($module_id){
    $modules = \DB::connection('default')->table('erp_cruds')->select('id','db_table')->where('connection','default')->where('id',$module_id)->get();
    foreach($modules as $module){
        $fields = \DB::connection('default')->table('erp_module_fields')->where('opts_multiple',0)->where('aliased_field',0)->where('module_id',$module->id)->where('field_type','select_module')->pluck('field')->toArray();
        foreach($fields as $field){
            $tableName = $module->db_table;
            $fieldName = $field;
            $indexName = "{$tableName}_{$fieldName}_index";
            
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $field)) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes($tableName);
              
                if(!array_key_exists($indexName, $indexesFound)){
                    Schema::table($tableName, function (Illuminate\Database\Schema\Blueprint $table) use ($fieldName) {
                       
                        $table->index($fieldName);
                    });
                }
            }
        }
    }
}

function remove_indexes_from_module($module_id){
    $modules = \DB::connection('default')->table('erp_cruds')->select('id','db_table')->where('connection','default')->where('id',$module_id)->get();
    foreach($modules as $module){
        $fields = \DB::connection('default')->table('erp_module_fields')->where('opts_multiple',0)->where('aliased_field',0)->where('module_id',$module->id)->where('field_type','select_module')->pluck('field')->toArray();
        foreach($fields as $field){
            $tableName = $module->db_table;
            $fieldName = $field;
            $indexName = "{$tableName}_{$fieldName}_index";
            
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $field)) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes($tableName);
              
                if(!array_key_exists($indexName, $indexesFound)){
                    Schema::table($tableName, function (Illuminate\Database\Schema\Blueprint $table) use ($fieldName) {
                       
                        $table->dropIndex($fieldName);
                    });
                }
            }
        }
    }
}

function clear_select_options_cache($db_table, $field_id = false){
    if(session('instance') && session('instance')->id){
        if($field_id){
            Cache::forget('select_optionsform'.$field_id.session('instance')->id);
            Cache::forget('select_optionsgrid'.$field_id.session('instance')->id);
        }else{
            $field_ids = \DB::connection('default')->table('erp_module_fields')->where('field_type','select_module')->where('opt_db_table','>','')->pluck('id')->toArray();
            foreach($field_ids as $field_id){
                Cache::forget('select_optionsform'.$field_id.session('instance')->id);
                Cache::forget('select_optionsgrid'.$field_id.session('instance')->id);
            }
        }
    }
}

function aftersave_select_options_optimize($request){
    
    $beforesave_row = session('event_db_record');
    $module_conn = \DB::connection('default')->table('erp_cruds')->where('id',$request->module_id)->pluck('connection')->first();
    $aliased_field = \DB::connection('default')->table('erp_module_fields')->where('id',$request->id)->pluck('aliased_field')->first();
    if($module_conn =='default' && !$aliased_field){
        if($request->field_type == 'select_module' && $request->opts_multiple == 0){
            if($request->opt_db_table){
                clear_select_options_cache($request->opt_db_table, $request->id);
            }
            
            $tableName = \DB::connection('default')->table('erp_cruds')->where('id',$request->module_id)->pluck('db_table')->first();
            $fieldName = $request->field;
            $indexName = "{$tableName}_{$fieldName}_index";
            
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $field)) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes($tableName);
              
                if(!array_key_exists($indexName, $indexesFound)){
                    Schema::table($tableName, function (Illuminate\Database\Schema\Blueprint $table) use ($fieldName) {
                        $table->index($fieldName);
                    });
                }
            }
        }elseif($beforesave_row->field_type == 'select_module'){
            
            $tableName = \DB::connection('default')->table('erp_cruds')->where('id',$request->module_id)->pluck('db_table')->first();
            $fieldName = $request->field;
            $indexName = "{$tableName}_{$fieldName}_index";
            
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $field)) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes($tableName);
              
                if(!array_key_exists($indexName, $indexesFound)){
                    Schema::table($tableName, function (Illuminate\Database\Schema\Blueprint $table) use ($fieldName) {
                        $table->dropIndex($fieldName);
                    });
                }
            }
        }
    }
    
    
    
}

function ajax_select_options_module_options($request){
  
    if(!empty($request->opt_module_id)){
    
        $field_conf = get_select_database_values($request->opt_module_id);
     
        $response = [
            'opt_db_table'=>$field_conf->opt_db_table,
            'opt_db_key'=>$field_conf->opt_db_key,
            'opt_db_display'=>$field_conf->opt_db_display,
            'opt_db_where'=> '',
            'opt_db_sortorder' => $field_conf->opt_db_sortorder,
        ];
        foreach($response as $k => $v){
            if(empty($v)){
                $response[$k] = '';
            }
        }
        return $response;
    }
}

function ajax_select_database_auto_select_from_label($request){
    if (empty($request->id) && !empty($request->label)) {
        $response = [];
        $field = clean($request->label);
        $field = str_replace([' ', '-'], '_', strtolower($field));
        if($request->field_type == 'select_module'){
        if (!str_ends_with($field,'_id')){
            $field .= '_id';    
        }
        }
        $response['field'] = $field;
        //attempt to match select_db field on name
        
        if($request->field_type == 'select_module'){
        $label = str_replace('-',' ',strtolower(clean($request->label)));
        $module_conn =  \DB::connection('default')->table('erp_cruds')->where('id',$request->module_id)->pluck('connection')->first();
        if($module_conn == 'default'){
            $opt_module_id = \DB::connection('default')->table('erp_cruds')->where('foreign_field_name',$label)->pluck('id')->first();
            if($opt_module_id){
              
                $response['opt_module_id'] = $opt_module_id;
                $field_conf = get_select_database_values($opt_module_id);
     
                $field_conf_response = [
                    'opt_db_table'=>$field_conf->opt_db_table,
                    'opt_db_key'=>$field_conf->opt_db_key,
                    'opt_db_display'=>$field_conf->opt_db_display,
                    'opt_db_where'=> '',
                    'opt_db_sortorder' => $field_conf->opt_db_sortorder,
                ];
                $response = array_merge($response,$field_conf_response);
              
            }
        }
        }
        
        return $response;
    }
}

function aftercommit_modulefields_default_grid_styles($request){
    $module_fields = \DB::table('erp_module_fields')->whereIn('field', ['is_deleted','status'])->get();
    foreach($module_fields as $f){
        $where = [
          'module_id' => $f->module_id, 
          'condition_operator' => '==',
          'condition_value' => ($f->field == 'status') ? 'Deleted' : 1,
          'field_id' => $f->id,
        ]; 
        $data = [
          'module_id' => $f->module_id, 
          'condition_operator' => '==',
          'condition_value' => ($f->field == 'status') ? 'Deleted' : 1,
          'field_id' => $f->id,
          'whole_row' => 1,
          'background_color' => '#c62828',
        ]; 
        
        \DB::table('erp_grid_styles')->updateOrInsert($where,$data);
    }
}

function beforesave_modulefields_check_inline_editing($request)
{
    if($request->cell_editing && !in_array($request->field_type,['date','datetime','currency','text','boolean','integer','select_module','select_custom'])){
        return 'Cell editing not set up for field type.';
    }
}

function beforesave_module_field_set_db_field($request)
{
    if (empty($request->id)) {
        $field = clean($request->label);
        $field = str_replace([' ', '-'], '_', strtolower($field));
        if ($request->field_type == 'select_module' && !str_ends_with($field,'_id')){
            $field .= '_id';    
        }
        request()->merge(['field' => $field]);
    }
    
     if ($request->field_type == 'select_module' && empty($request->opt_module_id)){
    
        //attempt to match select_db field on name
        
        $label = str_replace('-',' ',strtolower(clean($request->label)));
        $module_conn =  \DB::connection('default')->table('erp_cruds')->where('id',$request->module_id)->pluck('connection')->first();
        if($module_conn == 'default'){
        $opt_module_id = \DB::connection('default')->table('erp_cruds')->where('foreign_field_name',$label)->pluck('id')->first();
        if($opt_module_id){
            $request->opt_module_id = $opt_module_id;
          
            request()->merge(['opt_module_id' => $opt_module_id]);
        }
        }
     }
     
}


function beforesave_module_field_check_soft_delete($request)
{
    if ($request->field_type == 'file') {
        $status_field = \DB::table('erp_module_fields')->where('field','status')->where('module_id',$request->module_id)->count();
        $soft_delete = \DB::table('erp_cruds')->where('soft_delete',1)->where('id',$request->module_id)->count();
        if($status_field || $soft_delete){
            
        }else{
            return 'Module requires soft delete to use file field type';    
        }
    }

}

function aftercommit_update_formio_field($request)
{
    
    $form_field = \DB::connection('default')->table('erp_module_fields')->where('id', $request->id)->get()->first();

    $form_count = \DB::connection('default')->table('erp_forms')->where('module_id', $form_field->module_id)->count();

    if (!$form_count) {
        formio_create_form_from_db($form_field->module_id);
    } else {
        formio_create_form_from_db($form_field->module_id, true);
    }


    if ($form_field->field_type == 'select_module' && $form_field->opts_multiple == 0) {
        $field = $form_field;
       
        $data = ['join_1' => $field->alias.'.'.$field->field,'join_2' => $field_conf->opt_db_table.'.'.$field_conf->opt_db_key, 'type' => 'Field'];

        $exists = \DB::table('erp_report_joins')->where('join_1', $data['join_1'])->where('join_2', $data['join_2'])->count();
        if (!$exists) {
            \DB::table('erp_report_joins')->insert($data);
        }
    }
    
}


function afterdelete_remove_formio_field($request)
{
    $form = \DB::connection('default')->table('erp_forms')->where('module_id', $request->module_id)->get()->first();
    $form_json = json_decode($form->form_json);
    $form_json_array =  json_decode(json_encode($form_json), true);

    $form_component_data = formio_json_find_component($form_json, $request->field);
    $form_component = $form_component_data['component'];
    formio_unset_json_val($form_component_data['path'], $form_json_array);
    $form_json_array =  json_encode($form_json_array);
    \DB::connection('default')->table('erp_forms')->where('module_id', $request->module_id)->update(['form_json' => $form_json_array]);
}




function beforesave_module_fields_validate($request)
{
    $module_connection = \DB::table('erp_cruds')->where('id', $request->id)->pluck('connection')->first();
    if (str_ends_with($request->field, 'uuid')) {
        if (!in_array($request->field_type, ['select_module','hidden_uuid', 'hidden'])) {
            return 'Field type needs to be set to select database or hidden or hidden_uuid';
        }
    }
    
    if($request->field_type == 'select_module' && empty($request->opt_db_sortorder)){
        return 'Select dropdown sort order required';
    }

    if (!empty($request->new_record)) {
        $exists = \DB::table('erp_module_fields')->where('module_id', $request->module_id)->where('field', $request->field)->count();
    } else {
        $exists = \DB::table('erp_module_fields')->where('module_id', $request->module_id)->where('id', '!=', $request->id)->where('field', $request->field)->count();
    }

    if ($exists) {
        return 'Field already exists';
    }
}


function aftercommit_modulefields_update_sort($request)
{
    if (!empty($request->opts_values)) {
        $vals = $request->opts_values;
        if (!is_array($vals)) {
            $vals = explode(',', $request->opts_values);
            $vals = collect($vals)->filter()->toArray();
        }
        if (is_array($vals) && count($vals) >1) {
            \DB::table('erp_module_fields')->where('id', $request->id)->update(['opts_values'=>implode(',', $vals)]);
        }
    }

    $form_field = \DB::table('erp_module_fields')->where('id', $request->id)->get()->first();
    $row_module_id = $form_field->module_id;

            

    $field_tabs = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('tab', '!=', 'General')->orderby('sort_order')->pluck('tab')->filter()->unique()->toArray();
    array_unshift($field_tabs, 'General');
    $sort_order = 0;

    if (!empty($field_tabs) && count($field_tabs) > 0) {
     
        foreach ($field_tabs as $tab) {
            $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('tab', $tab)->orderby('sort_order')->get();
        
            foreach ($fields as $field) {
                \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                ++$sort_order;
            }
        }

        $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->whereNull('tab')->orderby('sort_order')->get();

        foreach ($fields as $field) {
            \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
            ++$sort_order;
        }
        $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('tab', '')->orderby('sort_order')->get();

        foreach ($fields as $field) {
            \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
            ++$sort_order;
        }
    } else {
        $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->orderby('sort_order')->get();

      
        foreach ($fields as $field) {
            \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
            ++$sort_order;
        }
    }
}


function update_fields_sort($module_id){
    $field_tabs = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('tab', '!=', 'General')->orderby('sort_order')->pluck('tab')->filter()->unique()->toArray();
    array_unshift($field_tabs, 'General');
    $sort_order = 0;

    if (!empty($field_tabs) && count($field_tabs) > 0) {
        foreach ($field_tabs as $tab) {
            $fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('tab', $tab)->orderby('sort_order')->get();

            foreach ($fields as $field) {
                \DB::connection('default')->table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                ++$sort_order;
            }
        }

        $fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->whereNull('tab')->orderby('sort_order')->get();

        foreach ($fields as $field) {
            \DB::connection('default')->table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
            ++$sort_order;
        }
        $fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('tab', '')->orderby('sort_order')->get();

        foreach ($fields as $field) {
            \DB::connection('default')->table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
            ++$sort_order;
        }
    } 
}



function afterdelete_delete_db_field($request)
{
    $module =  \DB::table('erp_cruds')->where('id', $request->module_id)->get()->first();
    
    
    if ($module) {
        if ($request->alias == $module->db_table) {
            $erp = new DBEvent();
            $erp->setTable('erp_instance_migrations');
            $data = [
                'action' => 'column_drop',
                'connection' => $module->connection,
                'table_name' => $module->db_table,
                'field_name' => $request->field,
            ];

            $erp->save($data);
        }
    }
}

function aftersave_module_fields_insert_at_db_field($request){
   
     
 
    if (!empty($request->new_record) &&!empty($request->insert_at_db_field)){
        $sort_order = \DB::connection('default')->table('erp_module_fields')->where('id',$request->insert_at_db_field)->pluck('sort_order')->first();
        $tab = \DB::connection('default')->table('erp_module_fields')->where('id',$request->insert_at_db_field)->pluck('tab')->first();
     
        \DB::connection('default')->table('erp_module_fields')->where('module_id',$request->module_id)->where('sort_order','>=',$sort_order)->increment('sort_order');
        \DB::connection('default')->table('erp_module_fields')->where('module_id',$request->module_id)->where('id',$request->id)->update(['sort_order' => $sort_order,'tab'=>$tab]);
        $sort = \DB::connection('default')->table('erp_module_fields')->where('module_id',$request->module_id)->where('id',$request->id)->pluck('sort_order')->first();
     
     
    
        // update layout colstate 
         
        if(!empty($request->layout_id)){
         
            $state = \DB::connection('default')->table('erp_grid_views')->where('id',$request->layout_id)->pluck('aggrid_state')->first();
            $state = json_decode($state);
            $searchName = \DB::connection('default')->table('erp_module_fields')->where('id',$request->insert_at_db_field)->pluck('field')->first();
            
            if($state && $state->colState){
                $conf_updated = 0;
                // The new element to insert
                $newElement = (object)[
                    "colId" => $request->field,
                    "width" => "150",
                    "hide" => "false",
                    "pinned" => "",
                    "sort" => "",
                    "sortIndex" => "",
                    "rowGroup" => "false",
                    "rowGroupIndex" => "",
                    "pivot" => "false",
                    "pivotIndex" => "",
                    "flex" => ""
                ];

                $colState = $state->colState;
                $conf_updated = false;
                foreach ($colState as $index => $item) {
                    if ($item->colId === $searchName) {
                        $conf_updated = 1;
                        array_splice($colState, $index, 0, [$newElement]);
                    }
                }
                
                 
                if($conf_updated){
                    $state->colState = $colState;
                    $state = json_encode($state);
                    \DB::connection('default')->table('erp_grid_views')->where('id',$request->layout_id)->update(['aggrid_state' => $state]);
                }
            }
        }
    }
}

function aftersave_update_form_db_keys($request)
{
    
    $config_fields = ['created_at','created_by','updated_at','updated_by','is_deleted'];
    \DB::connection('default')->table('erp_module_fields')->whereIn('field',$config_fields)->update(['visible'=>'None']); 
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    foreach($modules as $m){
         \DB::connection('default')->table('erp_module_fields')->where('module_id',$m->id)->where('field',$m->db_key)->update(['visible'=>'Add and Edit']);
    }
   
}

function aftersave_fields_move_required_fields($request)
{   
    \DB::connection('default')->table('erp_module_fields')->where('module_id',$request->module_id)->where('tab','')->where('required',1)->update(['tab'=>'General']);
       
}

function aftercommit_save_db_field($request)
{  
    
    if(!empty($field->cell_expression)){
        \DB::table('erp_module_fields')->where('id', $request->id)->update(['aliased_field'=>1]);
    }
    
    $field = \DB::table('erp_module_fields')->where('id', $request->id)->get()->first();
    if($field->field == 'status'){
        \DB::table('erp_module_fields')->where('id', $request->id)->update(['required'=>1]);    
    }
    if(!$field->aliased_field){
  
    if (!empty($request->new_record)) {
       
        $module =  \DB::table('erp_cruds')->where('id', $request->module_id)->get()->first();
        $erp = new DBEvent();
        $erp->setTable('erp_instance_migrations');

        $type = 'Varchar';
        $default_value = '';
        $field_length = '';


        if (str_contains($request->field_type, 'textarea')) {
            $type = 'Text';
        }
        if ($request->field_type == 'textarea_editor' || $request->field_type == 'textarea_code') {
            $type = 'longText';
        }

        if ($request->field_type == 'date') {
            $type = 'Date';
        }
        if ($request->field_type == 'datetime') {
            $type = 'DateTime';
        }
        if ($request->field_type == 'boolean') {
            $type = 'Tiny Integer';
            $field_length = 1;
            $default_value = 0;
        }
        
        if ($request->field_type == 'integer' || (!$request->opts_multiple && $request->field_type == 'select_module')) {
            $type = 'Integer';
            $field_length = 11;
            $default_value = 0;
        }

        if ($request->field_type == 'currency' || $request->field_type == 'decimal') {
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
            'field_name' => $request->field,
            'field_type' => $type,
            'default_value' => $default_value,
            'field_length' => $field_length
        ];
        $erp->save($data);

        $tab_group = $field->tab;
        if(!$tab_group){
        $tab_group = \DB::table('erp_module_fields')->where('module_id', $request->module_id)->orderBy('sort_order')->pluck('tab')->first();    
        }
        $last_sort_order  = \DB::table('erp_module_fields')->where('module_id', $request->module_id)->where('tab', $tab_group)->orderBy('sort_order', 'desc')->pluck('sort_order')->first();
        $last_sort_order++;
       
        \DB::table('erp_module_fields')->where('id', $request->id)->update(['tab' => $tab_group]);
        if(empty($request->insert_at_db_field)){
            \DB::table('erp_module_fields')->where('id', $request->id)->update(['sort_order' => $last_sort_order]);
        }
   
        update_module_fields_sort($request->module_id);
      
        update_module_config_from_schema($request->module_id);
      
        
    
    } else {
        
        $beforesave_row = session('event_db_record');
       
        if ($field->field_type!='unixtime' && $field->field_type != $beforesave_row->field_type) {
            $module =  \DB::table('erp_cruds')->where('id', $request->module_id)->get()->first();
            $erp = new DBEvent();
            $erp->setTable('erp_instance_migrations');

            $type = 'Varchar';
            $default_value = '';
            $field_length = '';


            if (str_contains($request->field_type, 'textarea')) {
                $type = 'Text';
            }
            if ($request->field_type == 'textarea_editor' || $request->field_type == 'textarea_code') {
                $type = 'longText';
            }

            if ($request->field_type == 'date') {
                $type = 'Date';
            }
            if ($request->field_type == 'datetime') {
                $type = 'DateTime';
            }
            if ($request->field_type == 'boolean') {
                $type = 'Tiny Integer';
                $field_length = 1;
                $default_value = 0;
            }

            if ($request->field_type == 'integer') {
                $type = 'Integer';
                $field_length = 11;
                $default_value = 0;
            }

            if ($request->field_type == 'currency') {
                $type = 'Decimal';
                $default_value = 0;
            }
            if (!empty($field->default_value)) {
                $default_value = $field->default_value;
            }

            $data = [
                'action' => 'column_type',
                'connection' => $module->connection,
                'table_name' => $module->db_table,
                'field_name' => $request->field,
                'field_type' => $type,
                'default_value' => $default_value,
                'field_length' => $field_length
            ];
            $erp->save($data);
        }

        if ($field->opts_multiple && !$beforesave_row->opts_multiple) {
            $module =  \DB::table('erp_cruds')->where('id', $request->module_id)->get()->first();
            $erp = new DBEvent();
            $erp->setTable('erp_instance_migrations');

            $type = 'Varchar';
            $default_value = '';
            $field_length = '';


            if (str_contains($request->field_type, 'textarea')) {
                $type = 'Text';
            }
            if ($request->field_type == 'textarea_editor' || $request->field_type == 'textarea_code') {
                $type = 'longText';
            }

            if ($request->field_type == 'select_module') {
                $type = 'Varchar';
            } else {
                $type = 'Text';
            }

            if (!empty($field->default_value)) {
                $default_value = $field->default_value;
            }

            $data = [
                'action' => 'column_type',
                'connection' => $module->connection,
                'table_name' => $module->db_table,
                'field_name' => $request->field,
                'field_type' => $type,
                'default_value' => $default_value,
                'field_length' => $field_length
            ];
            $erp->save($data);
        }
    }
    
    }
   
}

function update_module_fields_sort($row_module_id)
{

    $field_tabs = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('tab')->filter()->unique()->toArray();

    $sort_order = 0;

    if (!empty($field_tabs) && count($field_tabs) > 0) {
        foreach ($field_tabs as $tab) {
            $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('tab', $tab)->orderby('sort_order')->get();

            foreach ($fields as $field) {
                \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                ++$sort_order;
            }
        }
        $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('tab', '')->orderby('sort_order')->get();

        foreach ($fields as $field) {
            \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
            ++$sort_order;
        }
    } else {
        $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->orderby('sort_order')->get();

        foreach ($fields as $field) {
            \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
            ++$sort_order;
        }
    }


    $rows = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->orderBy('sort_order')->get();

    foreach ($rows as $i => $r) {
        \DB::table('erp_module_fields')->where('id', $r->id)->update(['sort_order' => $i]);
    }
}

function select_options_module_keys($row)
{
    if (!empty($row['detail_module_id'])) {
        $fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $row['detail_module_id'])->pluck('field')->toArray();
        $result = array_combine($fields, $fields);
        return $result;
    }
    return  [];
}

function select_options_module_date_filter_fields($row)
{
    $fields = get_columns_from_schema($row['db_table'], 'date', $row['connection']);
    $result = array_combine($fields, $fields);
    $fields2 = get_columns_from_schema($row['db_table'], 'datetime', $row['connection']);
    $result2 = array_combine($fields2, $fields2);
    return array_merge($result, $result2);
}

function select_options_module_expand_field($row)
{
    $fields = get_columns_from_schema($row['db_table'], null, $row['connection']);
    $result = array_combine($fields, $fields);
    return $result;
}

function select_options_tracking_fields($row)
{
    return \DB::connection('default')->table('erp_module_fields')->where('module_id', $row['module_id'])->orderBy('label')->pluck('label','field')->toArray();
}


function select_options_db_tables($row, $connection)
{
    if (!empty($row) && !empty($row['connection'])) {
        $connection = $row['connection'];
    }
    if (!empty($row) && !empty($row['module_id'])) {
        $connection = \DB::connection('default')->table('erp_cruds')->where('id', $row['module_id'])->pluck('connection')->first();
    }

    $tables = get_tables_from_schema($connection);
    if(!empty($row['table_name']) && !in_array($row['table_name'],$tables)){
        $tables[] = $row['table_name'];
    }
    
    $result = array_combine($tables, $tables);
    return $result;
}


function select_options_kanban_module_fields($row, $conn)
{
    $result = [];
    if (!empty($row) && !empty($row['module_id'])) {
        $opt_db_table = \DB::connection('default')->table('erp_cruds')->where('id', $row['module_id'])->pluck('db_table')->first();
        $fields = get_columns_from_schema($opt_db_table, null, 'default');
        $result = array_combine($fields, $fields);
    }

    return $result;
}

function select_options_aggregate_cards_module_fields($row, $conn)
{
    $result = [];
    if (!empty($row) && !empty($row['module_id'])) {
        $opt_db_table = \DB::connection('default')->table('erp_cruds')->where('id', $row['module_id'])->pluck('db_table')->first();
        $fields = get_columns_from_schema($opt_db_table, null, 'default');
        $result = array_combine($fields, $fields);
    }

    return $result;
}


function select_options_select_database_fields($row, $conn)
{
    $result = [];
   
    if (!empty($row) && !empty($row['opt_db_table'])) {
        $conn = \DB::table('erp_cruds')->where('db_table',$row['opt_db_table'])->pluck('connection')->first();
     
        $fields = get_columns_from_schema($row['opt_db_table'], null, $conn);
      
        $result = array_combine($fields, $fields);
    }

    return $result;
}


function select_options_module_db_conns($row)
{
    $list = [];
    $list[] = 'default';
    if(is_main_instance()){
        $list[] = 'pbx';
        $list[] = 'pbx_cdr';
        $list[] = 'pbx_porting';
        $list[] = 'freeswitch';
        $list[] = 'tickets';
        $list[] = 'helpdesk';
    }
    $result = array_combine($list, $list);
    return $result;
}

function select_options_db_conns($row)
{
    $list = [];
    $conns = \DB::connection('system')->table('erp_instances')->where('installed',1)->pluck('db_connection')->toArray();
    foreach ($conns as $c) {
        if ($c == session('instance')->db_connection) {
            $c = 'default';
        }
        $list[$c] = $c;
    }
    $list[] = 'pbx';
    $list[] = 'pbx_cdr';
    $list[] = 'freeswitch';
    $result = array_combine($list, $list);
    return $result;
}

function schedule_update_module_fields_sort_all()
{
    $module_ids = \DB::table('erp_cruds')->pluck('id')->toArray();
    foreach ($module_ids as $module_id) {
        update_module_fields_sort($module_id);
    }
}
function select_options_dropdown_templates($row)
{
    $list = [];

    $list[] = 'statuses';
    $list[] = 'duedays';
    $result = array_combine($list, $list);
    return $result;
}

function aftersave_module_fields_set_display_field($request)
{
    
       $sort_save =  \DB::table('erp_module_fields')->where('id', $request->id)->pluck('sort_order')->first();
 
    if($request->display_field){
        \DB::connection('default')->table('erp_module_fields')
        ->where('id','!=', $request->id)
        ->where('module_id', $request->module_id)
        ->update(['display_field'=>0]);
    }
    if($request->aggregate_cards){
        \DB::connection('default')->table('erp_module_fields')
        ->where('id','!=', $request->id)
        ->where('module_id', $request->module_id)
        ->update(['aggregate_cards'=>0]);
    }
   
}


function aftersave_module_fields_set_opt_values_from_template($request)
{
  
    //if(!empty($request->default_value) && in_array($request->field_type,['text','boolean','select_custom'])){
        //if(empty($request->new_record)){
        //$module = \DB::table('erp_cruds')->where('id',$request->module_id)->get()->first();
        //$field_name = $request->field;
        //\DB::connection($module->connection)->table($module->db_table)->where($field_name,'')->update([$field_name => $request->default_value]);
        //}
    //}
    
    if ($request->field_type == 'select_custom' && !empty($request->opts_value_templates)) {
        if ($request->opts_value_templates == 'statuses') {
            \DB::connection('default')->table('erp_module_fields')
            ->where('id', $request->id)
            ->update(['opts_values'=>'Enabled,Disabled,Deleted']);
        }
        if ($request->opts_value_templates == 'duedays') {
            \DB::connection('default')->table('erp_module_fields')
            ->where('id', $request->id)
            ->update(['opts_values'=>'Overdue,Today,Tomorrow,Next Week,Not Due']);
        }
    }
    
  



    if ($request->field_type =='select_module' && $request->opt_module_id > 0) {
    
        $opt_db_table = \DB::connection('default')->table('erp_module_fields')
        ->where('id', $request->id)->pluck('opt_db_table')->first();
      
        if(empty($opt_db_table)){
        
            $field_conf = get_select_database_values($request->opt_module_id);
         
            \DB::connection('default')->table('erp_module_fields')
            ->where('id', $request->id)
            ->update([
                'opt_db_table'=>$field_conf->opt_db_table,
                'opt_db_key'=>$field_conf->opt_db_key,
                'opt_db_display'=>$field_conf->opt_db_display,
            ]);
        }
     
        $display_fields = explode(',',$field_conf->opt_db_display);
        $display_field = $display_fields[0];
        $conn = \DB::connection('default')->table('erp_cruds')->where('id', $request->module_id)->pluck('connection')->first();
        $cols = get_columns_from_schema($field_conf->opt_db_table, null, $conn);
        if (in_array('sort_order', $cols)) {
            \DB::connection('default')->table('erp_module_fields')
            ->where('id', $request->id)
            ->update(['opt_db_sortorder'=>'sort_order']);
        } elseif (in_array($display_field, $cols)) {
            \DB::connection('default')->table('erp_module_fields')
            ->where('id', $request->id)
            ->update(['opt_db_sortorder'=>$display_field]);
        }
        
        
        
        
       
        if (in_array('is_deleted',$cols) && !str_contains($request->opt_db_where,'is_deleted')) {
            $opt_db_where = $request->opt_db_where;
            if(empty($opt_db_where)){
               $opt_db_where.='is_deleted=0'; 
            }else{
               $opt_db_where.=' and is_deleted=0'; 
            }
            \DB::connection('default')->table('erp_module_fields')
            ->where('id', $request->id)
            ->update(['opt_db_where'=>$opt_db_where]);
        }
    }
    
    
}



function get_select_database_values($module_id){
    $field_conf = (object) [];
    $module = \DB::table('erp_cruds')->select('db_table','db_key')->where('id',$module_id)->get()->first();
    
    $field_conf->opt_db_table = $module->db_table;
    $field_conf->opt_db_key = $module->db_key;
    $field_conf->opt_db_display = \DB::table('erp_module_fields')->where('display_field',1)->where('module_id',$module_id)->pluck('field')->first();
    
    $has_sort_order = \DB::table('erp_module_fields')->where('field','sort_order')->where('module_id',$module_id)->pluck('field')->first();
    $field_conf->opt_db_sortorder = ($has_sort_order) ? 'sort_order' : $field_conf->opt_db_display;
    
    return $field_conf;
}


function get_foreign_field_display_val($module_id,$field,$val){
    
    $field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->where('field', $field)->get()->first();
    $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    $conn = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->pluck('connection')->first();
    $display_values = explode(',', $field->opt_db_display);
    
    $select_query = \DB::connection($conn)->table($field->opt_db_table);
    $select_fields = $display_values;
    $select_fields[] = $field->opt_db_key;

    $select_query->select($select_fields);
    $select_query->where($field->opt_db_key,$val);
    $select_list = $select_query->get()->first();
 
    return $select_list->{$display_values[0]};
}

function set_last_note_all(){
    
    
    $modules = \DB::table('erp_cruds')->get();
    $module_fields_all = \DB::table('erp_module_fields')->get();
    $account_id_module_ids = \DB::connection('default')->table('erp_module_fields')->where('field','account_id')->pluck('module_id')->toArray();
    $account_id_module_ids[] = 343;
    foreach($modules as $module){
        $module_fields = $module_fields_all->where('module_id',$module->id)->pluck('field')->toArray();
        
        if(in_array('last_note',$module_fields)){
          
            //if(in_array('last_note_date',$module_fields)){
            //    \DB::connection($module->connection)->table($module->db_table)->update(['last_note_date'=>null,'last_note' => '']);
            //}else{
            //    \DB::connection($module->connection)->table($module->db_table)->update(['last_note' => '']);
            //}
            
            if(in_array('is_deleted',$module_fields)){
                $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->where('is_deleted',0)->get();
            } elseif(in_array('status',$module_fields)){
                $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->where('status','!=','Deleted')->get();
            }else{
                $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->get();
            }
           
            foreach($records as $record){
                $last_note = \DB::table('erp_module_notes')->where('module_id',$module->id)->where('row_id',$record->id)->orderBy('id','desc')->get()->first();
                if($last_note){
                   
                    //if(in_array($this->data['module_id'],$account_id_module_ids)){
                        $module_name = $modules->where('id',$last_note->module_id)->pluck('name')->first();
                        $last_note->note = $module_name.': '.$last_note->note;
                   // }
                    if(in_array('last_note_date',$module_fields)){
                        \DB::connection($module->connection)->table($module->db_table)->where($module->db_key,$record->id)->update(['last_note_date' => $last_note->created_at, 'last_note' => date('Y-m-d',strtotime($last_note->created_at)).' '.$last_note->note]);
                    }else{
                        \DB::connection($module->connection)->table($module->db_table)->where($module->db_key,$record->id)->update(['last_note' => date('Y-m-d',strtotime($last_note->created_at)).' '.$last_note->note]);
                    }
                }
            }
        }
    }
}