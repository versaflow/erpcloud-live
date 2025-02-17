<?php

class ErpInstance
{
    public function upgradeInstanceConfig($instance_id)
    { 
        //return false;
        // cannot run from cloned instances, cannot update main_instance
        if ($instance_id == 1 || !is_main_instance()) {
            return false;
        }

        // if ($instance_id != 12) {
        //     return true;
        // }
        $instance = DB::table('erp_instances')->where('id', $instance_id)->get()->first();

        // sync needs to be allowed on instance
        if (!$instance->sync_erp) {
            return true;
        }

        // get installed instance apps
        $installed_app_ids = DB::table('erp_instance_apps')->where('instance_id', $instance_id)->pluck('app_id')->toArray();
        $system_tasks = \DB::connection('system')->table('crm_staff_tasks')->where('instance_id','!=',1)->get();  
        // get main config for installed apps

        $modules_query = DB::table('erp_cruds')->where('custom', 0);
        /*
        $overwritten_defualt_modules = DB::connection($instance->db_connection)
        ->table('erp_cruds')
        ->where('custom', 1)
        ->where('main_instance_id', '>', '')
        ->pluck('main_instance_id')->toArray();
        if(!empty($overwritten_defualt_modules) && is_array($overwritten_defualt_modules) && count($overwritten_defualt_modules) > 0){
            $modules_query->whereNotIn('id', $overwritten_defualt_modules);
        }
        */
        $modules = $modules_query->whereIn('app_id', $installed_app_ids)->get();
        $module_ids = $modules->pluck('id')->toArray();
        $grids = DB::table('erp_module_fields')->where('custom', 0)->whereIn('module_id', $module_ids)->get();
        $forms = DB::table('erp_forms')->whereIn('module_id', $module_ids)->get();
        $buttons = DB::table('erp_grid_buttons')->where('custom', 0)->whereIn('module_id', $module_ids)->get();
        $gridviews = DB::table('erp_grid_views')->where('custom', 0)->whereIn('module_id', $module_ids)->get();
        $pinned_tabs = DB::table('erp_favorites')->get();
        $emails = DB::table('crm_email_manager')->where('custom', 0)->whereIn('module_id', $module_ids)->get();
        $events = DB::table('erp_form_events')->where('custom', 0)->whereIn('module_id', $module_ids)->get();
        $grid_styles = DB::table('erp_grid_styles')->whereIn('module_id', $module_ids)->get();
        $reports = DB::table('erp_reports')->where('custom', 0)->whereIn('module_id', $module_ids)->get();
        $user_roles = DB::table('erp_user_roles')->where('custom', 0)->get();
        $field_types = DB::table('erp_form_field_types')->get();
        $sidebar_modules = DB::table('erp_related_modules')->get();
        
        $menus_query = DB::table('erp_menu')->where('custom', 0);
        $menus_query->where(function ($menus_query) use ($module_ids, $installed_app_ids) {
            $menus_query->whereIn('module_id', $module_ids);
            $menus_query->orWhereIn('app_id', $installed_app_ids);
        });
        $menus = $menus_query->get();
        $menu_ids = $menus->pluck('id')->toArray();
        $menu_permissions = DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->get();

        // get custom instance config
        // all custom configs are assigned new ids
        // main instance configs ids are maintained
        $custom_modules = DB::connection($instance->db_connection)->table('erp_cruds')->where('custom', 1)->get();
        $custom_roles = DB::connection($instance->db_connection)->table('erp_user_roles')->where('custom', 1)->get();
        $custom_module_ids = $custom_modules->pluck('id')->toArray();
        $custom_grids = DB::connection($instance->db_connection)->table('erp_module_fields')->whereIn('module_id', $custom_module_ids)->orwhere('custom', 1)->get();
        $custom_grid_module_ids = $custom_grids->pluck('module_id')->unique()->filter()->toArray();
        $custom_forms = DB::connection($instance->db_connection)->table('erp_forms')->whereIn('module_id', $custom_module_ids)->orWhereIn('module_id', $custom_grid_module_ids)->get();

        $custom_buttons = DB::connection($instance->db_connection)->table('erp_grid_buttons')->whereIn('module_id', $custom_module_ids)->orwhere('custom', 1)->get();

        $custom_gridviews = DB::connection($instance->db_connection)->table('erp_grid_views')->whereIn('module_id', $custom_module_ids)->orwhere('custom', 1)->get();
        $custom_emails = DB::connection($instance->db_connection)->table('crm_email_manager')->whereIn('module_id', $custom_module_ids)->orwhere('custom', 1)->get();
        $custom_events = DB::connection($instance->db_connection)->table('erp_form_events')->whereIn('module_id', $custom_module_ids)->orwhere('custom', 1)->get();
        $custom_reports = DB::connection($instance->db_connection)->table('erp_reports')->whereIn('module_id', $custom_module_ids)->orwhere('custom', 1)->get();
        $custom_menus = DB::connection($instance->db_connection)->table('erp_menu')->whereIn('module_id', $custom_module_ids)->orwhere('custom', 1)->get();
        $custom_menu_ids = $custom_menus->pluck('id')->toArray();
        $custom_menu_permissions = DB::connection($instance->db_connection)->table('erp_menu_role_access')->whereIn('menu_id', $custom_menu_ids)->orderBy('menu_id','desc')->get();
    
        DB::connection($instance->db_connection)->statement('SET FOREIGN_KEY_CHECKS = 0');

        // truncate config tables
        $backups = [];
        $config_tables = [
            'erp_cruds',
            'erp_module_fields',
            'erp_forms',
            'erp_grid_buttons',
            'erp_grid_views',
            'erp_form_events',
            'erp_menu',
            'erp_menu_role_access',
            'crm_email_manager',
            'erp_reports',
            'erp_grid_styles',
            'erp_form_field_types',
            'erp_favorites',
        ];

        foreach ($config_tables as $table) {
            $backups[$table] = DB::connection($instance->db_connection)->table($table)->get();
            DB::connection($instance->db_connection)->table($table)->truncate();
            DB::connection($instance->db_connection)->raw('ALTER TABLE '.$table.' AUTO_INCREMENT = 1');
        }
        
        if($instance_id != 20){ //Communica
            $instance_roles = DB::connection($instance->db_connection)->table('erp_user_roles')->get();
            DB::connection($instance->db_connection)->table('erp_user_roles')->truncate();
        }

        try {
            $field_type_data = [];
            foreach ($field_types as $field_type) {
                $field_type_data[] = (array)$field_type;
            }
            DB::connection($instance->db_connection)->table('erp_form_field_types')->insert($field_type_data);
            
            aa($instance_id);
            if($instance_id != 20){
                $disable_customer_login = DB::connection($instance->db_connection)->table('erp_admin_settings')->where('id',1)->pluck('disable_customer_login')->first();
                $roles = [];
                foreach ($user_roles as $user_role) {
                    if($disable_customer_login && $user_role->level!='Admin'){
                    continue;
                    }
                    $default_module = $instance_roles->where('id',$user_role->id)->pluck('default_module')->first();
                    if($default_module){
                        $user_role->default_module = $default_module;
                    }
                    $roles[] = (array)$user_role;
                }
                foreach ($roles as $role) {
                    aa($role);
                    DB::connection($instance->db_connection)->table('erp_user_roles')
                        ->updateOrInsert(
                            ['id' => $role->id],
                            (array)$role
                        );
                }
                foreach ($custom_roles as $custom_role) {
                    DB::connection($instance->db_connection)->table('erp_user_roles')
                        ->insert((array)$custom_role);
                }
            }
            
            /*
            $aging_settings_exists = DB::connection($instance->db_connection)->table('crm_debtor_status')->count();
            if (!$aging_settings_exists) {
                $aging_settings = DB::table('crm_debtor_status')->get();
                foreach ($aging_settings as $aging_setting) {
                    $aging_setting_data = (array) $aging_setting;
                    DB::connection($instance->db_connection)->table('crm_debtor_status')->insert($aging_setting_data);
                }
            }

            if doctypes needs to be synced then all ledger accounts needs to be synced aswell
            $doctypes = DB::table('acc_doctypes')->get();
            foreach ($doctypes as $doctype) {
                $doctype = (array) $doctype;
                DB::connection($instance->db_connection)->table('acc_doctypes')->insert($doctype);
            }

            $doctype_details = DB::table('acc_doctype_details')->get();
            foreach ($doctype_details as $doctype_detail) {
                $doctype_detail = (array) $doctype_detail;
                DB::connection($instance->db_connection)->table('acc_doctype_details')->insert($doctype_detail);
            }
            */
            // insert main config
            
            $pinned_tab_rows = [];
            foreach ($pinned_tabs as $pinned_tab) {
                $copy_instance_ids = explode(',',$pinned_tab->instance_id);
                if(in_array($instance_id,$copy_instance_ids)){
                $data = (array) $pinned_tab;
                $pinned_tab_rows[] = (array)$data;
                }
            }

            $pinned_tab_collections = collect($pinned_tab_rows);
            foreach ($pinned_tab_collections->chunk(50) as $pinned_tab_collection) {
                DB::connection($instance->db_connection)->table('erp_favorites')->insert($pinned_tab_collection->toArray());
            }

            $module_rows = [];
            foreach ($modules as $module) {
                $data = (array) $module;
                $data['main_instance_id'] = $data['id'];
                $module_rows[] = (array)$data;
            }
            $module_collections = collect($module_rows);
            foreach ($module_collections->chunk(50) as $module_collection) {
                DB::connection($instance->db_connection)->table('erp_cruds')->insert($module_collection->toArray());
            }

            $email_rows = [];
            foreach ($emails as $email) {
                $data = (array) $email;
                $data['main_instance_id'] = $data['id'];
                $email_rows[] = (array)$data;
            }

            $email_collections = collect($email_rows);
            foreach ($email_collections->chunk(50) as $email_collection) {
                DB::connection($instance->db_connection)->table('crm_email_manager')->insert($email_collection->toArray());
            }

            $grid_rows = [];
            foreach ($grids as $grid) {
                $data = (array) $grid;
                $data['main_instance_id'] = $data['id'];
                $grid_rows[] = (array)$data;
            }

            $grid_collections = collect($grid_rows);
            foreach ($grid_collections->chunk(50) as $grid_collection) {
                DB::connection($instance->db_connection)->table('erp_module_fields')->insert($grid_collection->toArray());
            }

            $form_rows = [];
            foreach ($forms as $form) {
                $data = (array) $form;
                $data['main_instance_id'] = $data['id'];
                $form_rows[] = (array)$data;
            }

            $form_collections = collect($form_rows);
            foreach ($form_collections->chunk(50) as $form_collection) {
                DB::connection($instance->db_connection)->table('erp_forms')->insert($form_collection->toArray());
            }

            $button_rows = [];
            foreach ($buttons as $button) {
                $data = (array) $button;
                $data['main_instance_id'] = $data['id'];
                $button_rows[] = (array)$data;
            }
            $button_collections = collect($button_rows);
            foreach ($button_collections->chunk(50) as $button_collection) {
                DB::connection($instance->db_connection)->table('erp_grid_buttons')->insert($button_collection->toArray());
            }

            $gridview_rows = [];
            foreach ($gridviews as $gridview) {
                $data = (array) $gridview;
                $data['main_instance_id'] = $data['id'];
                $gridview_rows[] = (array)$data;
            }

            $gridview_collections = collect($gridview_rows);
            foreach ($gridview_collections->chunk(50) as $gridview_collection) {
                DB::connection($instance->db_connection)->table('erp_grid_views')->insert($gridview_collection->toArray());
            }
            
            $event_rows = [];
            foreach ($events as $event) {
                $data = (array) $event;
                unset($data['last_failed']);
                if(empty($data['error'])){
                    $data['error'] = '';
                }
                $data['main_instance_id'] = $data['id'];
                $event_rows[] = (array)$data;
            }

            $event_collections = collect($event_rows);
            foreach ($event_collections->chunk(50) as $event_collection) {
                DB::connection($instance->db_connection)->table('erp_form_events')->insert($event_collection->toArray());
            }
            
            $grid_style_rows = [];
            foreach ($grid_styles as $grid_style) {
                $data = (array) $grid_style;
                $grid_style_rows[] = (array)$data;
            }

            $grid_style_collections = collect($grid_style_rows);
            foreach ($grid_style_collections->chunk(50) as $grid_style_collection) {
                DB::connection($instance->db_connection)->table('erp_grid_styles')->insert($grid_style_collection->toArray());
            }

            $report_rows = [];
            foreach ($reports as $report) {
                $data = (array) $report;
                $data['main_instance_id'] = $data['id'];
                $report_rows[] = (array)$data;
            }

            $report_collections = collect($report_rows);
            foreach ($report_collections->chunk(50) as $report_collection) {
                DB::connection($instance->db_connection)->table('erp_reports')->insert($report_collection->toArray());
            }

            $inserted_menu_ids = [];
            $menu_rows = [];
            foreach ($menus as $menu) {
               
                $data = (array) $menu;
                $data['main_instance_id'] = $data['id'];
                $menu_rows[] = (array)$data;
            }

            $menu_collections = collect($menu_rows);
            foreach ($menu_collections->chunk(50) as $menu_collection) {
                DB::connection($instance->db_connection)->table('erp_menu')->insert($menu_collection->toArray());
            }

            // aa($menu_permissions);
            $menu_permission_rows = [];
            foreach ($menu_permissions as $menu_permission) {
                $data = (array) $menu_permission;
                $data['main_instance_id'] = $data['id'];
                $menu_permission_rows[] = (array)$data;
            }


            $menu_permission_collections = collect($menu_permission_rows);
            foreach ($menu_permission_collections->chunk(50) as $menu_permission_collection) {
                DB::connection($instance->db_connection)->table('erp_menu_role_access')->insert($menu_permission_collection->toArray());
            }
            /*
            DB::connection($instance->db_connection)->table('erp_related_modules')->truncate();
            foreach ($sidebar_modules as $related_module) {
                $menu_item_id = DB::connection($instance->db_connection)->table('erp_menu')->where('main_instance_id', $related_module->menu_id)->pluck('id')->first();
                $related_menu_item_id = DB::connection($instance->db_connection)->table('erp_menu')->where('main_instance_id', $related_module->related_menu_id)->pluck('id')->first();

                if ($menu_item_id && $related_menu_item_id) {
                    $data = [
                        'menu_id' => $menu_item_id,
                        'related_menu_id' => $related_menu_item_id,
                        'sort_order' => $related_module->sort_order,
                    ];

                    DB::connection($instance->db_connection)->table('erp_related_modules')->insert($data);
                }
            }
            */

            $new_module_ids = [];
            $new_menu_ids = [];
            $new_email_ids = [];
            $last_module_id = DB::connection($instance->db_connection)->table('erp_cruds')->orderby('id', 'desc')->pluck('id')->first();
            $last_menu_id = DB::connection($instance->db_connection)->table('erp_menu')->orderby('id', 'desc')->pluck('id')->first();
            $last_module_id += 1000;
            $last_menu_id += 1000;
            // insert custom config
            foreach ($custom_modules as $module) {
                if (!empty($module->main_instance_id)) {
                    $data = (array)$module;
                    DB::connection($instance->db_connection)->table('erp_cruds')->where('id', $module->main_instance_id)->update($data);
                } else {
                    $old_id = $module->id;
                    $data = (array)$module;
                    $data['id'] = $last_module_id;
                    $new_id = DB::connection($instance->db_connection)->table('erp_cruds')->insertGetId($data);
                   
                    $new_module_ids[$old_id] = $new_id;
                    $last_module_id++;
                }
            }
            
            arsort($new_module_ids, SORT_NUMERIC);
            
            foreach ($new_module_ids as $old_id => $new_id) { 
                DB::connection($instance->db_connection)->table('crm_approvals')->where('module_id', $old_id)->update(['module_id'=>$new_id]);
                DB::connection($instance->db_connection)->table('erp_cruds')->where('detail_module_id', $old_id)->update(['detail_module_id'=>$new_id]);
                DB::connection($instance->db_connection)->table('erp_user_roles')->where('default_module',$old_id)->update(['default_module'=>$new_id]);
                DB::connection($instance->db_connection)->table('crm_staff_tasks')->where('module_id',$old_id)->update(['module_id'=>$new_id]);
            }

            foreach ($custom_emails as $email) {
                $data = (array)$email;
                foreach ($new_module_ids as $old_id => $new_id) {
                    if ($email->module_id == $old_id) {
                        $data['module_id'] = $new_id;
                    }
                }

                if (!empty($email->main_instance_id)) {
                    DB::connection($instance->db_connection)->table('crm_email_manager')->where('id', $email->main_instance_id)->update($data);
                } else {
                    unset($data['id']);
                    $new_id = DB::connection($instance->db_connection)->table('crm_email_manager')->insertGetId($data);
                    $new_email_ids[$email->id] = $new_id;
                }
            }


            $sort_module_ids = $custom_grids->pluck('module_id')->unique()->toArray();
            $sort = [];
            foreach ($sort_module_ids as $sort_module_id) {
                $sort[$sort_module_id] = DB::connection($instance->db_connection)->table('erp_module_fields')->where('module_id', $sort_module_id)->max('sort_order');
            }

            foreach ($custom_grids as $grid) {
                $sort[$grid->module_id]++;
                $data = (array)$grid;
                foreach ($data as $key => $value) {
                    if (str_contains($key, 'module_id')) {
                        foreach ($new_module_ids as $old_id => $new_id) {
                            if ($grid->{$key} == $old_id) {
                                $data[$key] = $new_id;
                            }
                        }
                    }
                }

                if (!empty($grid->main_instance_id)) {
                    DB::connection($instance->db_connection)->table('erp_module_fields')->where('id', $grid->main_instance_id)->update($data);
                } else {
                    unset($data['id']);
                    if ($grid->custom) {
                        $data['sort_order'] = $sort[$grid->module_id];
                    }
                    DB::connection($instance->db_connection)->table('erp_module_fields')->insert($data);
                }
            }
            $sort_module_ids = $custom_grids->pluck('module_id')->unique()->toArray();

            /*
            foreach ($custom_buttons as $button) {
                $data = (array)$button;
                foreach ($data as $key => $value) {
                    if (str_contains($key, 'module_id')) {
                        foreach ($new_module_ids as $old_id => $new_id) {
                            if ($button->{$key} == $old_id) {
                                $data[$key] = $new_id;
                            }
                        }
                    }
                }

                if (!empty($button->main_instance_id)) {
                    DB::connection($instance->db_connection)->table('erp_grid_buttons')->where('id', $button->main_instance_id)->update($data);
                } else {
                    unset($data['id']);
                    DB::connection($instance->db_connection)->table('erp_grid_buttons')->insert($data);
                }
            }
            */
            $view_ids = [];
            foreach ($custom_gridviews as $gridview) {
                $data = (array)$gridview;
                foreach ($data as $key => $value) {
                    if (str_contains($key, 'module_id')) {
                        foreach ($new_module_ids as $old_id => $new_id) {
                            if ($gridview->{$key} == $old_id) {
                                $data[$key] = $new_id;
                            }
                        }
                    }
                }

                if (!empty($gridview->main_instance_id)) {
                    DB::connection($instance->db_connection)->table('erp_grid_views')->where('id', $gridview->main_instance_id)->update($data);
                } else {
                    unset($data['id']);
                    $new_id = \DB::connection($instance->db_connection)->table('erp_grid_views')->insertGetId($data);
                    $view_ids[$gridview->id] = $new_id;
                }
            }
            if(count($view_ids) > 0){
                foreach($view_ids as $old_id => $new_id){
                    
                    $task_update_data = ['layout_id' => $new_id];
                    $user_id = $system_tasks->where('instance_id',$instance->id)->where('layout_id',$old_id)->pluck('user_id')->first();
                    $role_id = $system_tasks->where('instance_id',$instance->id)->where('layout_id',$old_id)->pluck('role_id')->first();
                    $sort_order = $system_tasks->where('instance_id',$instance->id)->where('layout_id',$old_id)->pluck('sort_order')->first();
                    $parent_id = $system_tasks->where('instance_id',$instance->id)->where('layout_id',$old_id)->pluck('parent_id')->first();
                    if($user_id){
                        $task_update_data['user_id'] = $user_id;
                    }
                    if($role_id){
                        $task_update_data['role_id'] = $role_id;
                    }
                    if($sort_order){
                        $task_update_data['sort_order'] = $sort_order;
                    }
                    if($parent_id){
                        $task_update_data['parent_id'] = $parent_id;
                    }
                    \DB::connection('system')->table('crm_staff_tasks')->where('instance_id',$instance->id)->where('layout_id',$old_id)->update($task_update_data);
                    
                }
            }




            foreach ($custom_forms as $form) {
                $data = (array)$form;
                foreach ($data as $key => $value) {
                    if (str_contains($key, 'module_id')) {
                        foreach ($new_module_ids as $old_id => $new_id) {
                            if ($form->{$key} == $old_id) {
                                $data[$key] = $new_id;
                            }
                        }
                    }
                }

                if (!empty($form->main_instance_id)) {
                    DB::connection($instance->db_connection)->table('erp_forms')->where('id', $form->main_instance_id)->update($data);
                } else {
                    unset($data['id']);
                    DB::connection($instance->db_connection)->table('erp_forms')->insert($data);
                }
                formio_create_form_from_db($data['module_id'],true,$instance->db_connection);
            }
           
            $main_instance_domain_name = DB::table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
            $existing_forms = DB::connection($instance->db_connection)->table('erp_forms')->get();
            foreach ($existing_forms as $form) {
                $form_json = str_replace($main_instance_domain_name, $instance->domain_name, $form->form_json);
                DB::connection($instance->db_connection)->table('erp_forms')->where('id', $form->id)->update(['form_json'=>$form_json]);
            }

            foreach ($custom_events as $event) {
                $data = (array)$event;
                foreach ($data as $key => $value) {
                    if (str_contains($key, 'module_id')) {
                        foreach ($new_module_ids as $old_id => $new_id) {
                            if ($event->{$key} == $old_id) {
                                $data[$key] = $new_id;
                            }
                        }
                    }
                }

                if (!empty($event->main_instance_id)) {
                    DB::connection($instance->db_connection)->table('erp_form_events')->where('id', $event->main_instance_id)->update($data);
                } else {
                    unset($data['id']);
                    DB::connection($instance->db_connection)->table('erp_form_events')->insert($data);
                }
            }
            

            foreach ($custom_reports as $report) {
                $data = (array)$report;
                foreach ($data as $key => $value) {
                    if (str_contains($key, 'module_id')) {
                        foreach ($new_module_ids as $old_id => $new_id) {
                            if ($report->{$key} == $old_id) {
                                $data[$key] = $new_id;
                            }
                        }
                    }
                }

                if (!empty($report->main_instance_id)) {
                    DB::connection($instance->db_connection)->table('erp_reports')->where('id', $report->main_instance_id)->update($data);
                } else {
                    unset($data['id']);
                    DB::connection($instance->db_connection)->table('erp_reports')->insert($data);
                }
            }

            $parent_menu_ids = [];
            foreach ($custom_menus as $i => $menu) {
                if (!empty($menu->main_instance_id)) {
                    $data = (array)$menu;
                    DB::connection($instance->db_connection)->table('erp_menu')->where('id', $menu->main_instance_id)->update($data);
                } else {
                    $old_menu_id = $menu->id;
                    $data = (array)$menu;
                    foreach ($data as $key => $value) {
                        if (str_contains($key, 'module_id')) {
                            foreach ($new_module_ids as $old_id => $new_id) {
                                if ($menu->{$key} == $old_id) {
                                    $data[$key] = $new_id;
                                }
                            }
                        }
                    }

                    $data['id'] = $last_menu_id;
                    if ($data['parent_id']) {
                        $parent_menu_ids[] = $data['parent_id'];
                    }

                    $new_menu_id = DB::connection($instance->db_connection)->table('erp_menu')->insertGetId($data);

                    DB::connection($instance->db_connection)->table('erp_related_modules')->where('menu_id', $old_menu_id)->update(['menu_id'=> $new_menu_id]);
                    DB::connection($instance->db_connection)->table('erp_related_modules')->where('related_menu_id', $old_menu_id)->update(['related_menu_id'=> $new_menu_id]);
                    DB::connection($instance->db_connection)->table('erp_menu')->where('parent_id', $old_menu_id)->update(['parent_id'=> $new_menu_id]);
                    DB::connection($instance->db_connection)->table('erp_menu_role_access')->where('menu_id', $old_menu_id)->update(['menu_id'=> $new_menu_id]);
                    $new_menu_ids[$old_menu_id] = $new_menu_id;
                    $last_menu_id++;
                }
            }

            $parent_menu_ids = collect($parent_menu_ids)->unique()->toArray();
            foreach ($parent_menu_ids as $parent_menu_id) {
                foreach ($new_menu_ids as $key => $val) {
                    if ($key == $parent_menu_id) {
                        DB::connection($instance->db_connection)->table('erp_menu')->where('parent_id', $key)->update(['parent_id'=>$val]);
                    }
                }
            }

            
            foreach ($custom_menu_permissions as $menu_permission) {
               
                $data = (array)$menu_permission;
                foreach ($new_menu_ids as $old_menu_id => $new_menu_id) {
                    if ($menu_permission->menu_id == $old_menu_id) {
                        $data['menu_id'] = $new_menu_id;
                        unset($data['id']);
                        DB::connection($instance->db_connection)->table('erp_menu_role_access')->updateOrInsert(['role_id'=>$menu_permission->role_id,'menu_id'=>$old_menu_id],$data);
                    }
                }
            }
            

            $email_reference_tables = [];
            $tables = get_tables_from_schema($instance->db_connection);
            foreach ($tables as $table) {
                $cols = get_columns_from_schema($table, null, $instance->db_connection);
                if (in_array('email_id', $cols)) {
                    $email_reference_tables[] = $table;
                }
               
            }
            
            
            // Sort the mapping array numerically in descending order
            arsort($new_email_ids, SORT_NUMERIC);
           

            foreach ($new_email_ids as $old_email_id => $new_email_id) {
                foreach ($email_reference_tables as $email_reference_table) {
                    DB::connection($instance->db_connection)->table($email_reference_table)->where('email_id', $old_email_id)->update(['email_id'=>$new_email_id]);
                }
            }
            
            foreach($module_ids as $module_id){
                $update_data = ['instance_id'=>$instance->id,'module_id'=>$module_id,'last_update'=> date('Y-m-d H:i:s')];
                DB::connection('system')->table('erp_module_updates')->updateOrInsert(['instance_id'=>$instance->id,'module_id'=>$module_id], $update_data);
            }

            DB::connection($instance->db_connection)->statement('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\Throwable $ex) {  
            exception_log($ex);
            foreach ($backups as $table => $rows) {
                DB::connection($instance->db_connection)->table($table)->truncate();
                foreach ($rows as $row) {
                    $row = (array) $row;
                    DB::connection($instance->db_connection)->table($table)->insert($row);
                }
            }

            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
            debug_email('Config update failed. '.$instance->name.' '.$error);
            return 'Config update failed.';
        }
        return true;
    }

    public function beforesave($request)
    {
        $result = $this->validateName($request);
        if ($result !== true) {
            return $result;
        }
        $result = $this->validateDatabase($request);
        if ($result !== true) {
            return $result;
        }
    }

    private function validateName($request)
    {
        $erp_name = $request->name;
        $erp_name = str_replace(' ', '', strtolower($erp_name));

        if (empty($erp_name) || !ctype_alpha($erp_name)) {
            return 'Invalid name. '.$erp_name;
        }

        if (empty($request->id)) {
            $exists = DB::table('erp_instances')->where('name', $erp_name)->count();
            if ($exists > 0) {
                return 'ERP with name already exists.';
            }
        }

        if (!empty($instance->id)) {
            $exists = DB::table('erp_instances')->where('id', '!=', $request->id)->where('name', $erp_name)->count();
            if ($exists > 0) {
                return 'ERP with name already exists.';
            }
        }

        return true;
    }

    private function validateDatabase($request)
    {
        $valid_db = db_conn_exists($request->db_connection);

        if (!$valid_db) {
            return 'Invalid DB Connection';
        }

        if (empty($request->id)) {
            //// check if already exists
            $exists = DB::table('erp_instances')->where('db_connection', $request->db_connection)->count();
            if ($exists > 0 || 'default' == $request->db_connection) {
                return 'DB connection already in use';
            }
        }

        if (!empty($request->id)) {
            $exists = DB::table('erp_instances')->where('id', '!=', $request->id)->where('db_connection', $request->db_connection)->count();
            if ($exists > 0 || 'default' == $request->db_connection) {
                return 'DB connection already in use';
            }
        }
        return true;
    }

    public function validateDirectories()
    {
        $instances = \DB::connection('system')->table('erp_instances')->where('installed', 1)->get();
        foreach ($instances as $instance) {
            $instance_dir = strtolower(str_replace(' ', '_', $instance->name));
            $uploads_dir = public_path().'/uploads/'.$instance_dir.'/';
            $attachments_dir = public_path().'/attachments/'.$instance_dir.'/';
            $logs_dir = base_path().'/storage/logs/'.$instance_dir.'/';
            $emailimages_dir = public_path().'/emails/images/'.$instance_dir.'/';

            if (!File::isDirectory($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
                shell_exec('chmod 777 '.$uploads_dir.' -R');
            } else {
                shell_exec('chmod 777 '.$uploads_dir.' -R');
            }

            if (!File::isDirectory($attachments_dir)) {
                mkdir($attachments_dir, 0777, true);
                shell_exec('chmod 777 '.$attachments_dir.' -R');
            } else {
                shell_exec('chmod 777 '.$attachments_dir.' -R');
            }

            if (!File::isDirectory($logs_dir)) {
                mkdir($logs_dir, 0777, true);
                shell_exec('chmod 777 '.$logs_dir.' -R');
            } else {
                shell_exec('chmod 777 '.$logs_dir.' -R');
            }

            if (!File::isDirectory($emailimages_dir)) {
                mkdir($emailimages_dir, 0777, true);
                shell_exec('chmod 777 '.$emailimages_dir.' -R');
            } else {
                shell_exec('chmod 777 '.$emailimages_dir.' -R');
            }
        }
    }

    public function install($request)
    {
        if (session('instance')->id == 1) {
            return false;
        }

        if (config('database.connections.default.database') == config('database.connections.system.database') || config('database.connections.default.database') == 'flexerp_portal') {
            abort(403, 'Invalid DB');
        }


        //validate balances
        $balance = 0;
        foreach ($request->all() as $key => $value) {
            if (str_contains($key, 'ledger_account')) {
                $balance += $request->{$key};
            }
        }

        if (0 != $balance) {
            return json_alert('Invalid opening balance. Opening balance needs to be zero.', 'warning');
        }

        // check required fields
        $required_fields = ['company', 'contact', 'email', 'phone', 'address', 'username', 'password', 'bank_details'];
        foreach ($required_fields as $field) {
            if (empty($request->{$field})) {
                return json_alert('The '.$field.' field is required.', 'warning');
            }
            if ('email' == $field) {
                if (!erp_email_valid($request->{$field})) {
                    return json_alert('The '.$field.' field is invalid.', 'warning');
                }
            }
        }

        $main = get_main_connection();
        $conn = get_instance_connection();
        $tables = get_tables_from_schema();




        // import data from main instance

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        $tables = get_tables_from_schema();
        foreach ($tables as $table) {
            Schema::connection('default')->dropIfExists($table);
        }
        //empty current db
        $cmd = 'mysqldump --no-data -u remote -pWebmin@786 -h 156.0.96.71 '.$main['database'].' | mysql -u remote -pWebmin@786 -h 156.0.96.71 '.$conn['database'];
        shell_exec($cmd);
        
        
        $tables_to_import = [
            'acc_doctypes',
            'acc_doctype_details',
            'erp_apps',
            'acc_periods',
            'acc_ledger_account_categories',
            'acc_ledger_accounts',
            'erp_cruds',
            'erp_module_fields',
            'erp_menu',
            'erp_form_events',
            'erp_grid_buttons',
            'erp_user_roles',
            'erp_menu_role_access',
            'erp_forms',
            'crm_email_manager',
            'erp_form_field_types',
            'erp_admin_settings',
        ];
        $tables = get_tables_from_schema();
        foreach ($tables as $table) {
            if (!in_array($table, $tables_to_import)) {
                DB::table($table)->truncate();
                DB::raw('ALTER TABLE '.$table.' AUTO_INCREMENT = 1');
            }
        }
        foreach($tables_to_import as $table){
            DB::table($table)->truncate();
            $table_data = \DB::connection('system')->table($table)->get();
            foreach($table_data as $row){
                if($table == 'acc_ledger_accounts'){
                    if(!empty($row->limit_to_instances_id)){
                       $limit_to_instances_id = explode(',',$row->limit_to_instances_id);
                        if(in_array($instance_id,$limit_to_instances_id)){
                            $data = (array) $row;
                            \DB::table($table)->insert($data);
                        }
                    }else{
                        $data = (array) $row;
                        \DB::table($table)->insert($data);
                    }
                }else{
                    $data = (array) $row;
                    \DB::table($table)->insert($data);
                }
            }
        }
   
        
        
        $installed_app_ids = DB::table('erp_instance_apps')->where('instance_id', $instance_id)->pluck('app_id')->toArray();
        // delete modules and menus
        $module_ids = [];
       // $isp_modules = DB::table('erp_cruds')->where('db_table', 'LIKE', 'isp_%')->pluck('id')->toArray();
       // $module_ids = array_merge($module_ids, $isp_modules);

        foreach ($request->all() as $key => $value) {
            if (str_contains($key, 'app_') && !empty($value)) {
                $app_arr = explode('_', $key);
                $app_id = $app_arr[1];
                $subscription_modules = DB::table('erp_cruds')->where('app_id', $app_id)->pluck('id')->toArray();
                $module_ids = array_merge($module_ids, $subscription_modules);
            }
        }

        $menu_ids = DB::table('erp_menu')->whereIn('module_id', $module_ids)->pluck('id')->toArray();

        DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->delete();
        DB::table('erp_menu')->whereIn('id', $menu_ids)->delete();
        DB::table('erp_cruds')->whereIn('id', $module_ids)->delete();
        DB::table('erp_forms')->whereIn('module_id', $module_ids)->delete();
        DB::table('erp_form_events')->whereIn('module_id', $module_ids)->delete();
        DB::table('erp_module_fields')->whereIn('module_id', $module_ids)->delete();
        DB::table('erp_grid_buttons')->whereIn('module_id', $module_ids)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        //set opening balances


        // create uploads folder
        if (!file_exists(uploads_path())) {
            mkdir(uploads_path(), 0777, true);
        }

        // create admin account
        $account = [
            'id' => 1,
            'company' => $request->company,
            'contact' => $request->contact,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'created_at' => date('Y-m-d H:i:s'),
            'type' => 'reseller',
        ];
        dbinsert('crm_accounts', $account);

        \DB::connection('default')->table('erp_users')->truncate();
        /// create superuser
        $superuser = \DB::connection('system')->table('erp_users')->orderby('id', 'asc')->get()->first();
        $user = [
            'id' => 1,
            'account_id' => 1,
            'role_id' => $superuser->role_id,
            'username' => $superuser->username,
            'email' => $request->email,
            'password' => $superuser->password,
            'active' => 1,
            'full_name' => $request->contact,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        \DB::connection('default')->table('erp_users')->insert($user);
        
        
        /// create superuser
        $system_user = \DB::connection('system')->table('erp_users')->where('username','system')->orderby('id', 'asc')->get()->first();
        $user = (array) $system_user;
        unset($user['id']);
        \DB::connection('default')->table('erp_users')->insert($user);

        $logo = 'default_logo.png';
        if (!empty($request->file('logo'))) {
            $file = $request->file('logo');
            $destinationPath = uploads_settings_path();
            $filename = $file->getClientOriginalName();

            $filename = str_replace([' ', ','], '_', $filename);
            $uploadSuccess = $file->move($destinationPath, $filename);
            if ($uploadSuccess) {
                $logo = $filename;
            }
        }
        /// create admin settings
        $settings = [
            'account_id' => 1,
            'invoice_footer' => $request->bank_details,
            'vat_enabled' => (!empty($request->vat_enabled)) ? 1 : 0,
            'disable_signup' => (empty($request->enable_signup)) ? 1 : 0,
            'logo' => $logo,
            'whitelabel_domain' => $_SERVER['HTTP_HOST'],
        ];
        dbinsert('crm_account_partner_settings', $settings);

        \DB::connection('system')->table('erp_instances')->where('id', session('instance')->id)->update(['installed'=>1]);
        return json_alert('Installation Complete.', 'success', ['reload' => url('/')]);
    }

}
