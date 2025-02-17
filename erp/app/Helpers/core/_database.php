<?php

function test_connection($name)
{
    try {
        \DB::connection($name)->getPdo();
    } catch (\Exception $e) {
        return false;
    }
    return true;
}


function schema_clone_db_table($new_table, $table, $conn = 'default')
{
    \Schema::connection($conn)->dropIfExists($new_table);
    \DB::connection($conn)->statement('CREATE TABLE '.$new_table.' LIKE '.$table);
    \DB::connection($conn)->statement('INSERT '.$new_table.' SELECT * FROM '.$table);
}

function schema_clone_db_table_from_db($sourceConnection,$destinationConnection,$sourceTable, $copy_data = false){

    $destinationTable = $sourceTable;
    if(Schema::connection($destinationConnection)->hasTable($sourceTable)){
        return false;    
    }
    // Get the table columns and their properties from the source table
    $tableColumns = Schema::connection($sourceConnection)->getColumnListing($sourceTable);
    $tableProperties = [];
    
    foreach ($tableColumns as $column) {
        $columnDetails = Schema::connection($sourceConnection)->getColumnType($sourceTable, $column);
        $tableProperties[$column] = $columnDetails;
    }
    
    // Create the new table in the destination database with the same schema
    Schema::connection($destinationConnection)->create($destinationTable, function ($table) use ($tableProperties) {
        foreach ($tableProperties as $column => $type) {
            $table->$type($column);
        }
    });    
    
    if($copy_data){
        // Get the data from the source table
        $data = DB::connection($sourceConnection)->table($sourceTable)->get();
        
        // Insert the data into the destination table
        foreach ($data as $row) {
            DB::connection($destinationConnection)->table($destinationTable)->insert((array)$row);
        }
    
    }
}

function erp_db($name)
{
    return new \App\Database\OTF([
        'host' => 'ns2.nserver.co.za',
        'driver' => 'mysql',
        'database' => $name,
        'username' => 'root',
        'password' => 'Webmin321', ]);
}

function dbgetaccounts($type = '')
{
    $type = strtolower($type);
    if ('reseller' != $type) {
        return \DB::table('crm_accounts')->where('type', $type)->orderby('id')->get();
    } else {
        return \DB::table('crm_accounts')
            ->join('crm_account_partner_settings', 'crm_account_partner_settings.account_id', '=', 'crm_accounts.id')
            ->orderby('id')
            ->get();
    }
}

function dbgetaccountcell($id, $field)
{
    $account = dbgetaccount($id);

    if (!empty($account->$field)) {
        return $account->$field;
    }

    return false;
}

function dbgetsubaccounts($id)
{
    $account_ids = \DB::connection('default')->table('crm_accounts')->where('partner_id', $id)->pluck('id')->toArray();
    $accounts = [];
    foreach ($account_ids as $account_id) {
        $accounts[] = dbgetaccount($account_id);
    }
    return $accounts;
}

function dbgetaccount($id, $conn = 'default')
{
    $type = \DB::connection($conn)->table('crm_accounts')->where('id', $id)->pluck('type')->first();
    
    if ('customer' == $type || 'reseller_user' == $type) {
        $account = \DB::connection($conn)->table('crm_accounts')
            ->select('*', 'crm_accounts.id as id')
            ->leftJoin('isp_voice_pbx_domains', 'isp_voice_pbx_domains.account_id', '=', 'crm_accounts.id')
            ->where('crm_accounts.id', $id)->get()->first();
    } elseif ('reseller' == $type) {
        if ($id == 1) {
            $account = \DB::connection($conn)->table('crm_accounts')
                ->select('*', 'crm_accounts.id as id')
                ->join('crm_account_partner_settings', 'crm_account_partner_settings.account_id', '=', 'crm_accounts.id')
                ->join('erp_admin_settings', 'crm_account_partner_settings.id', '=', 'erp_admin_settings.id')
                ->where('crm_accounts.id', $id)->get()->first();
        } else {
            $account = \DB::connection($conn)->table('crm_accounts')
                ->select('*', 'crm_accounts.id as id')
                ->join('crm_account_partner_settings', 'crm_account_partner_settings.account_id', '=', 'crm_accounts.id')
                ->where('crm_accounts.id', $id)->get()->first();
        }
    }

    if (empty($account)) {
        $account = \DB::connection($conn)->table('crm_accounts')->where('id', $id)->get()->first();
    }

    return $account;
}

function dbgetsupplier($id)
{
    return \DB::connection('default')->table('crm_suppliers')->where('id', $id)->get()->first();
}

function dbgetrow($table, $wherefield = '', $wherevalue = '')
{
    $sql = "SELECT * FROM $table WHERE $wherefield = '".$wherevalue."'";
    $rows = \DB::select($sql);
    if ($rows && count($rows) > 0) {
        return $rows[0];
    } else {
        return false;
    }
}

function dbgetrows($table, $wherefield = '', $wherevalue = '')
{
    $sql = 'SELECT * FROM '.$table;
    if ($wherefield > '') {
        $sql .= " WHERE $wherefield = '".$wherevalue."'";
    }
    $rows = \DB::select($sql);
    if ($rows && count($rows) > 0) {
        return (array) $rows;
    } else {
        return false;
    }
}

function dbgetcell($table, $wherefield, $wherevalue, $getfield)
{
    $sql = "SELECT $getfield FROM $table WHERE $wherefield = '".$wherevalue."'";
    $rows = \DB::select($sql);

    if ($rows) {
        $row = $rows[0]->$getfield;

        return $row;
    }
}

function dbset($table, $wherefield, $wherevalue, $data)
{
    $cols = get_columns_from_schema($table);
    if(in_array('updated_by',$cols) && empty($data['updated_by'])){
       $data['updated_by'] = get_user_id_default(); 
    }
    if(in_array('updated_at',$cols) && empty($data['updated_at'])){
       $data['updated_at'] = date('Y-m-d H:i:s');
    }
    $module = \DB::connection('default')->table('erp_cruds')->where('db_table', $table)->get()->first();
    if ($module && $module->id) {
        $result = \DB::connection($module->connection)->table($module->db_table)->where($wherefield, $wherevalue)->update($data);
        $row_id = \DB::connection($module->connection)->table($module->db_table)->where($wherefield, $wherevalue)->pluck($module->db_key)->first();
        module_log($module->id, $row_id, 'updated');
    } else {
        $result = \DB::table($table)->where($wherefield, $wherevalue)->update($data);
    }
    return $result;
}

function dbupdate($table, $wheredata, $data)
{
    $cols = get_columns_from_schema($table);
    if(in_array('updated_by',$cols) && empty($data['updated_by'])){
       $data['updated_by'] = get_user_id_default(); 
    }
    if(in_array('updated_at',$cols) && empty($data['updated_at'])){
       $data['updated_at'] = date('Y-m-d H:i:s');
    }
    $module = \DB::connection('default')->table('erp_cruds')->where('db_table', $table)->get()->first();
    if ($module && $module->id) {
        $result = \DB::connection($module->connection)->table($module->db_table)->where($wheredata)->update($data);
        $row_id = \DB::connection($module->connection)->table($module->db_table)->where($wheredata)->pluck($module->db_key)->first();
        module_log($module->id, $row_id, 'updated');
    } else {
        $result = \DB::table($table)->where($wheredata)->update($data);
    }
    return $result;
}

function dbinsert($table, $data, $conn = 'default')
{
    $cols = get_columns_from_schema($table, null, $conn);
    if(in_array('created_by',$cols) && empty($data['created_by'])){
       $data['created_by'] = get_user_id_default(); 
    }
    if(in_array('created_at',$cols) && empty($data['created_at'])){
       $data['created_at'] = date('Y-m-d H:i:s');
    }
    $id = \DB::table($table)->insertGetId($data);
    if ($id > 0) {
        $module = \DB::connection('default')->table('erp_cruds')->where('db_table', $table)->get()->first();
        if ($module && $module->id) {
            module_log($module->id, $id, 'created');
        }
        return $id;
    } else {
        return false;
    }
}

function dbdelete($table, $wherefield, $wherevalue)
{
    $module = \DB::connection('default')->table('erp_cruds')->where('db_table', $table)->get()->first();
    if ($module) {
        $row_id = \DB::connection($module->connection)->table($module->db_table)->where($wherefield, $wherevalue)->pluck($module->db_key)->first();
        $result = \DB::connection($module->connection)->table($module->db_table)->where($wherefield, $wherevalue)->delete();
        module_log($module->id, $row_id, 'deleted');
    } else {
        $result = \DB::table($table)->where($wherefield, $wherevalue)->delete();
    }

    return $result;
}

function dbcount($table, $where)
{
    return DB::table($table)->where($where)->count();
}

function pbxgetrows($table, $wherefield, $wherevalue)
{
    $sql = "SELECT * FROM $table WHERE $wherefield = '".$wherevalue."'";
    $rows = \DB::connection('pbx')->select($sql);
    if ($rows && count($rows) > 0) {
        return $rows;
    } else {
        return false;
    }
}

function pbxgetcell($table, $wherefield, $wherevalue, $getfield)
{
    $sql = "SELECT $getfield FROM $table WHERE $wherefield = '".$wherevalue."'";
    $rows = \DB::connection('pbx')->select($sql);

    if ($rows) {
        $row = $rows[0]->$getfield;

        return $row;
    }
}

function pbxset($table, $wherefield, $wherevalue, $setarray)
{
    $result = \DB::connection('pbx')->table($table)->where($wherefield, $wherevalue)->update($setarray);

    return $result;
}

function pbxinsert($table, $data)
{
    $id = \DB::connection('pbx')->table($table)->insertGetId($data);
    if ($id > 0) {
        return $id;
    } else {
        return false;
    }
}

function pbxdelete($table, $wherefield, $wherevalue)
{
    $result = \DB::connection('pbx')->table($table)->where($wherefield, $wherevalue)->delete();
    return $result;
}


function getoptions($table, $id, $display, $entry_by_only = 0, $account_id = '', $where = '', $lookup_sort = '', $lookup_default_val = '', $filter_function = '')
{
    if ('' == $account_id) {
        $account_id = session('account_id');
    }

    $display = explode('|', $display);
    $html = '<option value=""> </option>';
    $account_id_script = '';
    if (1 == $entry_by_only) {
        if ('crm_accounts' == $table) {
            $account_id_script = ' and id in (select id from crm_accounts where partner_id = '.session('account_id').") and status='Enabled'";
        } else {
            $account_id_script = ' and account_id in (select id from crm_accounts  where id = '.session('account_id')." and status='Enabled')";
        }
    }

    if ($where > '') {
        $where = ' and '.$where.' ';
    }
    if (3 == count($display)) {
        $order_by = (!empty($lookup_sort)) ? ' order by '.$lookup_sort : ' order by '.$display[0].','.$display[1].','.$display[2];
        $options = \DB::select('select * from '.$table.' where 1=1 '.$where.$account_id_script.$order_by);
    } elseif (2 == count($display)) {
        $order_by = (!empty($lookup_sort)) ? ' order by '.$lookup_sort : ' order by '.$display[0].','.$display[1];
        $options = \DB::select('select * from '.$table.' where 1=1 '.$where.$account_id_script.$order_by);
    } else {
        $order_by = (!empty($lookup_sort)) ? ' order by '.$lookup_sort : ' order by '.$display[0];
        $options = \DB::select('select * from '.$table.' where 1=1 '.$where.$account_id_script.$order_by);
    }

    if (!empty($filter_function) && function_exists($filter_function)) {
        $options = $filter_function($options);
    }

    if ($options) {
        foreach ($options as $option) {
            $selected = (!empty($lookup_default_val) && !empty($option->$id) && $option->$id == $lookup_default_val) ? ' selected="selected"' : '';
            if ($selected) {
                $show_balance = ('crm_accounts' == $table) ? ' ('.get_debtor_balance($option->$id).') ' : '';
            }

            if ('module_id' == $display[0]) {
                $option->{$display[0]} = \DB::table('erp_cruds')->where('id', $option->{$display[0]})->pluck('name')->first();
            }
            if ('module_id' == $display[1]) {
                $option->{$display[1]} = \DB::table('erp_cruds')->where('id', $option->{$display[1]})->pluck('name')->first();
            }
            if ('module_id' == $display[2]) {
                $option->{$display[2]} = \DB::table('erp_cruds')->where('id', $option->{$display[2]})->pluck('name')->first();
            }
            if (3 == count($display)) {
                $html .= '<option value='.$option->$id.$selected.'>'.$option->{$display[0]}.' - '.$option->{$display[1]}.' - '.$option->{$display[2]}.$show_balance.'</option>';
            } elseif (2 == count($display)) {
                $html .= '<option value='.$option->$id.$selected.'>'.$option->{$display[0]}.' - '.$option->{$display[1]}.$show_balance.'</option>';
            } else {
                $html .= '<option value='.$option->$id.$selected.'>'.$option->{$display[0]}.$show_balance.'</option>';
            }
        }
    }

    return $html;
}

function duplicate_row($table, $id, $copy_field = 'name')
{
    try {
        if ('crm_products' == $table) {
            $copy_field = 'code';
        }
        if ('isp_host_erp_websites' == $table) {
            $copy_field = 'domain';
        }
        if ('isp_host_websites' == $table) {
            $copy_field = 'domain';
        }

        if ('erp_users' == $table) {
            $copy_field = 'username';
        }

        $cols = get_columns_from_schema($table);
        if (!in_array($copy_field, $cols)) {
            $copy_field = '';
        }

        $module = \DB::connection('default')->table('erp_cruds')->where('db_table', $table)->get()->first();
        $exclude_fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module->id)->where('exclude_duplicate',1)->pluck('field')->toArray();
        $exclude_fields[] = $module->db_key;
        $key_field = $module->db_key;
        $conn = $module->connection;

        $row = \DB::connection($conn)->table($table)->where($key_field, $id)->get()->first();
        if (!empty($row)) {
            unset($row->{$key_field});


            if (!empty($copy_field)) {
                $row->$copy_field .= '_copy';
            }

            $data = (array) $row;
            if(isset($data['created_at'])){
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (!empty($row->main_instance_id)) {
                unset($data['main_instance_id']);
            }


            if (str_contains($key_field, 'uuid')) {
                $data[$key_field] = pbx_uuid($table, $key_field);
                \DB::connection($conn)->table($table)->insert($data);
                return json_alert('Duplicated');
            } elseif ('crm_staff_tasks' == $table) {
                $project_id = $id;
                $project =  \DB::connection('default')->table('crm_staff_tasks')->where('id', $project_id)->get()->first();
                if($project->type == 'Layout' || $project->type == 'Report'){
                return json_alert('Processes cannot be duplicated','error');
                }
                $data = (array) $project;
                $data['name'] .= ' copy';
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
               
                $copy_project_id = \DB::connection('default')->table('crm_staff_tasks')->insertGetId($data);
                
                return json_alert('Duplicated');
            }  elseif ('crm_product_bundles' == $table) {
                $bundle_id = $id;
                $bundle =  \DB::connection('default')->table('crm_product_bundles')->where('id', $bundle_id)->get()->first();
                $data = (array) $bundle;
                $data['name'] .= ' copy';
                unset($data['id']);
                unset($data['default_bundle']);
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
               
                $copy_bundle_id = \DB::connection('default')->table('crm_product_bundles')->insertGetId($data);
                $bundle_details = \DB::connection('default')->table('crm_product_bundle_details')->where('product_bundle_id', $bundle_id)->get();
                foreach ($bundle_details as $r) {
                    $data = (array) $r;
                    $data['product_bundle_id'] = $copy_bundle_id;
                    unset($data['id']);
                    \DB::connection('default')->table('crm_product_bundle_details')->insert($data);
                }
                return json_alert('Duplicated');
            } elseif ('crm_pricelists' == $table) {
                $pricelist_id = $id;
                $ratesheet =  \DB::connection('default')->table('crm_pricelists')->where('id', $pricelist_id)->get()->first();
                $data = (array) $ratesheet;
                $data['name'] .= ' copy';
                unset($data['id']);
                unset($data['default_pricelist']);
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
               
                $copy_pricelist_id = \DB::connection('default')->table('crm_pricelists')->insertGetId($data);
                $rates = \DB::connection('default')->table('crm_pricelist_items')->where('pricelist_id', $pricelist_id)->get();
                foreach ($rates as $r) {
                    $data = (array) $r;
                    $data['pricelist_id'] = $copy_pricelist_id;
                    unset($data['id']);
                    \DB::connection('default')->table('crm_pricelist_items')->insert($data);
                }
                return json_alert('Duplicated');
            } elseif ('crm_documents' == $table) {
                $header =  \DB::connection('default')->table('crm_documents')->where('id', $id)->get()->first();
                $data = (array) $header;
                unset($data['id']);
                unset($data['doc_no']);
                unset($data['reference']);
                unset($data['billing_type']);
                $data['completed'] = 0;
                $data['reversal_id'] = 0;
                $data['doctype'] = 'Quotation';
                $data['docdate'] = date('Y-m-d');
                $data['docdate_month'] = date('Y-m-01');
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
                $document_id = \DB::connection('default')->table('crm_documents')->insertGetId($data);
                
                $lines = \DB::connection('default')->table('crm_document_lines')->where('document_id', $id)->groupBy('product_id')->get();
                foreach ($lines as $r) {
                    $data = (array) $r;
                    $data['document_id'] = $document_id;
                    unset($data['id']);
                    \DB::connection('default')->table('crm_document_lines')->insert($data);
                }
                return json_alert('Duplicated');
            } elseif ('p_rates_partner' == $table) {
                $ratesheet_id = $id;
                $ratesheet =  \DB::connection('pbx')->table('p_rates_partner')->where('id', $ratesheet_id)->get()->first();
                $data = (array) $ratesheet;
                $data['name'] .= ' copy';
                unset($data['id']);
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
               
                $copy_ratesheet_id = \DB::connection('pbx')->table('p_rates_partner')->insertGetId($data);
                $rates = \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id', $ratesheet_id)->get();
                foreach ($rates as $r) {
                    $data = (array) $r;
                    $data['ratesheet_id'] = $copy_ratesheet_id;
                    unset($data['id']);
                    \DB::connection('pbx')->table('p_rates_partner_items')->insert($data);
                }
                return json_alert('Duplicated');
            }  elseif ('erp_user_roles' == $table) {
                $insert_id = DB::table($table)->insertGetId($data);
                $permissions = \DB::table('erp_menu_role_access')->where('role_id', $id)->get();
                foreach ($permissions as $permission) {
                    $permission_data = (array) $permission;
                    unset($permission_data['id']);
                    $permission_data['role_id'] = $insert_id;
                    \DB::table('erp_menu_role_access')->insert($permission_data);
                }
                $fs = \DB::table('erp_forms')->where('role_id', $id)->get();
                foreach ($fs as $f) {
                    $d = (array) $f;
                    unset($d['id']);
                    $d['role_id'] = $insert_id;
                    \DB::table('erp_forms')->insert($d);
                }
                return json_alert('Duplicated');
            } elseif ('erp_cruds' == $table) {
                $data['slug'] = strtolower(str_replace(['_',' '], '-', string_clean($data['name'])));
 
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
                $insert_id = DB::table($table)->insertGetId($data);
                $permissions = \DB::table('erp_module_fields')->where('module_id', $id)->get();
                foreach ($permissions as $permission) {
                    $permission_data = (array) $permission;
                    unset($permission_data['id']);
                    $permission_data['module_id'] = $insert_id;
                    \DB::table('erp_module_fields')->insert($permission_data);
                }
                $permissions = \DB::table('erp_forms')->where('module_id', $id)->get();
                foreach ($permissions as $permission) {
                    $permission_data = (array) $permission;
                    unset($permission_data['id']);
                    $permission_data['module_id'] = $insert_id;
                    \DB::table('erp_forms')->insert($permission_data);
                }
                
                $grid_styles = \DB::table('erp_grid_styles')->where('module_id', $id)->get();
                foreach ($grid_styles as $grid_style) {
                    $grid_style_data = (array) $grid_style;
                    unset($grid_style_data['id']);
                    $grid_style_data['module_id'] = $insert_id;
                    \DB::table('erp_grid_styles')->insert($grid_style_data);
                }
                
                $layouts = \DB::table('erp_grid_views')->where('module_id', $id)->get();
                foreach ($layouts as $layout) {
                    $layout_data = (array) $layout;
                    unset($layout_data['id']);
                    $layout_data['module_id'] = $insert_id;
                    //$layout_data['layout_type'] = 'Layout';
                    $layout_data['track_layout'] = 0;
                    $layout_data['show_on_dashboard'] = 0;
                    unset($layout_data['duration']);
                   
                    unset($layout_data['timer_status']);
                    unset($layout_data['global_default']);
                    unset($layout_data['role_default']);
                    unset($layout_data['system_layout']);
                    unset($layout_data['main_instance_id']);
                    $layout_data['export_layout_frequency'] = 'None';
                    \DB::table('erp_grid_views')->insert($layout_data);
                }
                $events = \DB::table('erp_form_events')->where('module_id', $id)->where('type', '!=', 'schedule')->get();
                foreach ($events as $event) {
                    $event_data = (array) $event;
                    unset($event_data['id']);
                    $event_data['module_id'] = $insert_id;
                    \DB::table('erp_form_events')->insert($event_data);
                }
                
                $menus = \DB::table('erp_menu')->where('module_id', $id)->get();
                foreach ($menus as $menu) {
                    $menu_data = (array) $menu;
                    unset($menu_data['id']);
                    $menu_data['module_id'] = $insert_id;
                    $menu_insert_id = \DB::table('erp_menu')->insertGetId($menu_data);
                    $permissions = \DB::table('erp_menu_role_access')->where('menu_id', $menu->id)->get();
                    foreach ($permissions as $permission) {
                        $permission_data = (array) $permission;
                        unset($permission_data['id']);
                        $permission_data['menu_id'] = $menu_insert_id;
                        \DB::table('erp_menu_role_access')->insert($permission_data);
                    }
                }
                
                $buttons = \DB::table('erp_menu')->where('render_module_id', $id)->get();
                foreach ($buttons as $button) {
                    $button_data = (array) $button;
                    unset($button_data['id']);
                    $button_data['render_module_id'] = $insert_id;
                    $menu_insert_id = \DB::table('erp_menu')->insertGetId($button_data);
               
                    $permissions = \DB::table('erp_menu_role_access')->where('menu_id', $button->id)->get();
                    foreach ($permissions as $permission) {
                        $permission_data = (array) $permission;
                        unset($permission_data['id']);
                        $permission_data['menu_id'] = $menu_insert_id;
                        \DB::table('erp_menu_role_access')->insert($permission_data);
                    }
                }
              
                cache_clear();
                return json_alert('Duplicated');
            }elseif ('erp_menu' == $table) {
                
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
                $insert_id = DB::table($table)->insertGetId($data);
                $permissions = \DB::table('erp_menu_role_access')->where('menu_id', $id)->get();
                foreach ($permissions as $permission) {
                    $permission_data = (array) $permission;
                    unset($permission_data['id']);
                    $permission_data['menu_id'] = $insert_id;
                    \DB::table('erp_menu_role_access')->insert($permission_data);
                }

                
                cache_clear();
                return json_alert('Duplicated');
            } elseif ('crm_ad_campaigns' == $table) {
                unset($data['facebook_campaign_id']);
                unset($data['form_id']);
                $insert_id = DB::table($table)->insertGetId($data);
                return json_alert('Duplicated');
            } elseif ('crm_newsletters' == $table) {
                $data['sent_test'] = 0;
                $data['sent_to_customers'] = 0;
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
                $insert_id = DB::table($table)->insertGetId($data);
                return json_alert('Duplicated');
            } elseif ('erp_forms' == $table) {
                
                
                $data['role_id'] = 1;
                $data['default'] = 0;
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
                $insert_id = DB::table($table)->insertGetId($data);
                return json_alert('Duplicated');
            } elseif ('erp_grid_views' == $table) {
                $data['global_default'] = 0;
                $data['track_layout'] = 0;
                $data['show_on_dashboard'] = 0;
                unset($data['duration']);
               
                unset($data['timer_status']);
                unset($data['global_default']);
                unset($data['role_default']);
                unset($data['system_layout']);
                unset($data['main_instance_id']);
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
             
                $data['export_layout_frequency'] = 'None';
                $insert_id = DB::table($table)->insertGetId($data);
                
                return json_alert('Duplicated');
            } elseif ('erp_reports' == $table) {
                $insert_id = DB::table($table)->insertGetId($data);
                $update =[];
                $update['report_config'] = str_replace('_'.$id, '_'.$insert_id, $data['report_config']);
                
                \DB::table($table)->where('id', $insert_id)->update($update);


                return json_alert('Duplicated');
            } elseif ('erp_users' == $table) {
                unset($data['password']);
                unset($data['pbx_extension']);
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
                $insert_id = DB::table($table)->insertGetId($data);
                return json_alert('Duplicated');
            }else {
                $db = new DBEvent();
                unset($data['website_id']);
                if ($table == 'sub_activation_plans') {
                    $data['step'] = 1000;
                }
                if ($table == 'erp_module_fields') {
                    $data['field'] .= '_copy';
                    $data['label'] .= ' Copy';
                }
                
                foreach($exclude_fields as $field){
                    unset($data[$field]);
                }
                $result =  $db->setTable($table)->save($data);

                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    return $result;
                } elseif (!is_array($result) || empty($result['id'])) {
                    return response()->json(['status' => 'error', 'message' => $result]);
                }
                if ($result['id']) {
                    if ($table == 'erp_module_fields') {
                        return json_alert('Duplicated.', 'success', ['reload_grid_config' => 'reload_grid_config'.$data['module_id']]);
                    } else {
                        return json_alert('Duplicated');
                    }
                }
            }
        }
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_email($ex, 'Duplicate Failed');

        return json_alert('Duplicate Failed', 'error');
    }
}

function pbxrun($sql)
{
    return \DB::connection('pbx')->select($sql);
}

function webmailrun($sql)
{
    return \DB::connection('webmail')->select($sql);
}

function webmailgetrow($table, $wherefield = '', $wherevalue = '')
{
    $sql = "SELECT * FROM $table WHERE $wherefield = '".$wherevalue."'";
    $rows = \DB::connection('webmail')->select($sql);

    if ($rows && count($rows) > 0) {
        return $rows[0];
    } else {
        return false;
    }
}

function webmailgetrows($table, $wherefield, $wherevalue)
{
    $sql = "SELECT * FROM $table WHERE $wherefield = '".$wherevalue."'";
    $rows = \DB::connection('webmail')->select($sql);
    if ($rows && count($rows) > 0) {
        return $rows;
    } else {
        return false;
    }
}

function webmailinsert($table, $data)
{
    $id = \DB::connection('webmail')->table($table)->insertGetId($data);
    if ($id > 0) {
        return $id;
    } else {
        return false;
    }
}

function module_has_menu($modules)
{
    $linked_modules = \DB::table('erp_menu')->pluck('module_id')->toArray();
    $arr = [];
    foreach ($modules as $module) {
        if (!in_array($module->module_id, $linked_modules)) {
            $arr[] = $module;
        }
    }

    return $arr;
}

function get_db_connections()
{
    $list = [];
    $conns = Config::get('database');
    foreach ($conns['connections'] as $name => $c) {
        if ('system' == $name) {
            continue;
        }
        $list[] = $name;
    }
    return $list;
}

function get_default_connection()
{
    return 'default';
}

function get_instance_connection()
{
    $list = [];
    $conns = Config::get('database');
    foreach ($conns['connections'] as $name => $c) {
        if ('default' == $name) {
            return $c;
        }
    }
}

function get_main_connection()
{
    $list = [];
    $conns = Config::get('database');
    foreach ($conns['connections'] as $name => $c) {
        if ('system' == $name) {
            return $c;
        }
    }
}

function db_conn_exists($db_conn)
{
    $connections = get_db_connections();

    return in_array($db_conn, $connections);
}

function set_db_connection($conn = false)
{
    if (!$conn) {
        $conn = 'default';
    }
    $current_conn = DB::getDefaultConnection();
    if ($current_conn == $conn) {
        return true;
    }


    $conns = get_db_connections();
    if (in_array($conn, $conns)) {
        Config::set('database.default', $conn);

        return true;
    }

    return false;
}

function schedule_optimize_tables()
{
    if(!is_main_instance()){
        return false;
    }
    
   
    $conns = ['telecloud','eldooffice','moviemagic'];
    foreach ($conns as $c) {
       
        $tables = get_tables_from_schema($c);
        foreach ($tables as $t) {
            \DB::connection($c)->statement("OPTIMIZE TABLE ".$t);
        }
    }
}

function admin_user_login($user_id, $instance_id = false)
{
    if (!$instance_id) {
        $instance_id = session('instance')->id;
    }
    if (!$instance_id) {
        $instance_id = 1;
    }

    if ($instance_id != 1) {
        $row = \DB::connection('system')->table('erp_users')->where('id', $user_id)->get()->first();
        $row = \DB::connection(session('instance')->db_connection)->table('erp_users')->where('username', $row->username)->get()->first();
    } else {
        $row = \DB::connection('system')->table('erp_users')->where('id', $user_id)->get()->first();
    }

    if ($row) {
        $level = \DB::connection('system')->table('erp_user_roles')->where('id', $user->role_id)->pluck('level')->first();

        if ($level == 'Admin') {
            Auth::loginUsingId($row->id);
            set_session_data($row->id);
        }
    }
}

function schedule_log_slow_queries(){
    
    if(is_main_instance()){
        $rows = \DB::connection('core')->select("SELECT * FROM mysql.slow_log where start_time >= '".date('Y-m-d',strtotime('-1 day'))."%'");
     
        foreach($rows as $row){
            $data = (array) $row;
            \DB::table('erp_slow_queries')->insert($data);
        }
    }
}

/*
get foreign keys

SELECT
  TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
FROM
  INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
  REFERENCED_TABLE_SCHEMA = '<database>' AND
  REFERENCED_TABLE_NAME = '<table>' AND
  REFERENCED_COLUMN_NAME = '<column>';
*/
