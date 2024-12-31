<?php

function schedule_validate_instance_user_access()
{
    if (! is_main_instance()) {
        return false;
    }

    $user_ids = \DB::table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->pluck('id')->toArray();
    $instance_ids = \DB::table('erp_instances')->where('installed', 1)->where('sync_erp', 1)->pluck('id')->toArray();
    $instance_ids[] = 1;

    \DB::table('erp_instance_user_access')->whereNotIn('user_id', $user_ids)->delete();
    \DB::table('erp_instance_user_access')->whereNotIn('instance_id', $instance_ids)->delete();

    $mobile_app_user_ids = \DB::table('erp_users')->where('account_id', 1)->where('role_id', 54)->where('is_deleted', 0)->pluck('id')->toArray();
    foreach ($mobile_app_user_ids as $mobile_app_user_id) {
        \DB::table('erp_instance_user_access')->where('instance_id', '!=', 1)->where('user_id', $mobile_app_user_id)->delete();
    }
}

function schedule_instances_update_config()
{
    if (is_main_instance()) {
        validate_menu_permissions();
        $modules = \DB::table('erp_cruds')->get();
        foreach ($modules as $m) {
            \DB::table('erp_menu')->where('module_id', $m->id)->update(['app_id' => $m->app_id, 'custom' => $m->custom]);
        }

        $erp = new \ErpInstance;
        $instances = \DB::connection('default')->table('erp_instances')->where('installed', 1)->where('id', '!=', 1)->where('sync_erp', 1)->get();
        foreach ($instances as $instance) {
            $result = $erp->upgradeInstanceConfig($instance->id);

            if ($result !== true) {
                return false;
            }
            // restore tracking layouts
            // get tracking layouts
            $layouts = \DB::connection($instance->db_connection)->table('erp_grid_views')->where('track_layout', 1)->get();
            foreach ($layouts as $layout) {
                $data = [
                    'process_sort_order' => $layout->process_sort_order,
                    'failure_reason' => $layout->failure_reason,
                ];
                \DB::connection($instance->db_connection)->table('erp_grid_views')->where('main_instance_id', $layout->main_instance_id)->update($data);
            }
        }
        $instances = \DB::connection('default')->table('erp_instances')->where('installed', 1)->whereIn('id', [2, 11])->where('sync_erp', 1)->get();
        foreach ($instances as $instance) {
            import_processes_to_main($instance->id);
            workboard_layout_set_tracking_per_user($instance->id);
        }

        return true;
    }
    cache_clear();
}

function update_instances_layout($layout_id)
{
    $update_id = $layout_id;
    if (! is_main_instance()) {
        $grid_view = \DB::connection('default')->table('erp_grid_views')->where('main_instance_id', $layout_id)->get()->first();
        if (empty($grid_view) || empty($grid_view->main_instance_id)) {
            $data = (array) $grid_view;
            unset($data['id']);
            unset($data['main_instance_id']);
            $update_id = \DB::connection('system')->table('erp_grid_views')->insertGetId($data);

            \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['custom' => 0, 'main_instance_id' => $update_id]);
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

function button_instances_view_instance($request)
{
    $url = \DB::table('erp_instances')->where('installed', 1)->where('id', $request->id)->pluck('domain_name')->first();
    $portal_url = 'http://'.$url;

    return redirect()->to($portal_url);
}

function schedule_format_php_code()
{
    $cmd = '';
    $erp_path = base_path();
    if (File::isDirectory($erp_path.'/app/Helpers')) {
        $views = \File::allFiles($erp_path.'/app/Helpers/');

        foreach ($views as $path) {
            $file = $path->getRealPath();
            if (! str_ends_with($file, '.php')) {
                continue;
            }
            $cmd .= 'php-cs-fixer fix "'.$file.'" --rules=@PSR2,array_indentation,method_chaining_indentation;';
        }
    }

    if (File::isDirectory($erp_path.'/app/Http')) {
        $views = \File::allFiles($erp_path.'/app/Http/');

        foreach ($views as $path) {
            $file = $path->getRealPath();
            if (! str_ends_with($file, '.php')) {
                continue;
            }
            $cmd .= 'php-cs-fixer fix "'.$file.'" --rules=@PSR2,array_indentation,method_chaining_indentation;';
        }
    }

    if (File::isDirectory($erp_path.'/app/Library')) {
        $views = \File::allFiles($erp_path.'/app/Library/');

        foreach ($views as $path) {
            $file = $path->getRealPath();
            if (! str_ends_with($file, '.php')) {
                continue;
            }
            $cmd .= 'php-cs-fixer fix "'.$file.'" --rules=@PSR2,array_indentation,method_chaining_indentation;';
        }
    }

    if (File::isDirectory($erp_path.'/app/Models')) {
        $views = \File::allFiles($erp_path.'/app/Models/');

        foreach ($views as $path) {
            $file = $path->getRealPath();
            if (! str_ends_with($file, '.php')) {
                continue;
            }
            $cmd .= 'php-cs-fixer fix "'.$file.'" --rules=@PSR2,array_indentation,method_chaining_indentation;';
        }
    }

    if (File::isDirectory($erp_path.'/routes')) {
        $views = \File::allFiles($erp_path.'/routes/');

        foreach ($views as $path) {
            $file = $path->getRealPath();
            if (! str_ends_with($file, '.php')) {
                continue;
            }
            $cmd .= 'php-cs-fixer fix "'.$file.'" --rules=@PSR2,array_indentation,method_chaining_indentation;';
        }
    }

    if (File::isDirectory($erp_path.'/config')) {
        $views = \File::allFiles($erp_path.'/config/');

        foreach ($views as $path) {
            $file = $path->getRealPath();
            if (! str_ends_with($file, '.php')) {
                continue;
            }
            $cmd .= 'php-cs-fixer fix "'.$file.'" --rules=@PSR2,array_indentation,method_chaining_indentation;';
        }
    }

    Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
}

function beforesave_db_migrations_check_processed($request)
{
    if (! empty($request->id)) {
        $migration = \DB::table('erp_instance_migrations')->where('id', $request->id)->get()->first();

        if ($migration->processed) {
            //   return 'Already processed';
        }
    }
}
function button_instance_migrations_reset($request)
{
    \DB::table('erp_instance_migrations')->where('id', $request->id)->update(['processed' => 0, 'completed' => 0, 'error_result' => '', 'success_result' => '']);

    return json_alert('Reset complete.');
}

function aftersave_db_migrations_process($request)
{
    try {
        $erp = new ErpMigrations;
        $result = $erp->processMigration($request->id);

        if ($result !== true) {
            // aa($result);
            return $result;
        }
        if ($request->action != 'table_drop') {
            //  update_module_config_from_schema(false, $request->table_name);
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();

        // aa($error);
        // aa($ex->getTraceAsString());
        return $error;
    }
}

function beforesave_validate_erp_name($request)
{
    if ($request->id != 1) {
        $erp = new ErpInstance;
        $result = $erp->beforesave($request);
        if ($result) {
            return $result;
        }
    }
}

function button_update_instances_config($request)
{
    try {
        if (! is_main_instance()) {
            return json_alert('No Access', 'warning');
        }
        if (! is_superadmin()) {
            return json_alert('No Access', 'warning');
        }
        if (! is_main_instance()) {
            return json_alert('No Access', 'warning');
        }
        validate_menu_permissions();
        $modules = \DB::table('erp_cruds')->get();
        foreach ($modules as $m) {
            \DB::table('erp_menu')->where('module_id', $m->id)->update(['app_id' => $m->app_id, 'custom' => $m->custom]);
        }

        $erp = new \ErpInstance;
        $instances = \DB::connection('default')->table('erp_instances')->where('installed', 1)->where('id', '!=', 1)->where('sync_erp', 1)->get();
        foreach ($instances as $instance) {
            // get tracking layouts
            $layouts = \DB::connection($instance->db_connection)->table('erp_grid_views')->where('track_layout', 1)->get();
            $result = $erp->upgradeInstanceConfig($instance->id);
            if ($result !== true) {
                return false;
            }
            // restore tracking layouts
            foreach ($layouts as $layout) {
                $data = [
                    'process_sort_order' => $layout->process_sort_order,
                    'failure_reason' => $layout->failure_reason,
                ];
                \DB::connection($instance->db_connection)->table('erp_grid_views')->where('main_instance_id', $layout->main_instance_id)->update($data);
            }
        }

        cache_clear();

        return json_alert('Config updated.');
    } catch (\Throwable $ex) {
        // aa($ex->getMessage());
        // aa($ex->getTraceAsString());
        return json_alert('Config not updated.', 'warning');
    }
}

function replace_code_references($search, $replace)
{
    if (! empty($search) && ! empty($replace)) {
        $cmd = 'cd '.base_path().' && find . -name "*.php" | xargs -n 1 sed -i -e "s|\b'.$search.'\b|'.$replace.'|g"';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
}

function replace_code_references_controllers($search, $replace)
{
    if (! empty($search) && ! empty($replace)) {
        $cmd = 'cd '.app_path().'/Http && find . -name "*.php" | xargs -n 1 sed -i -e "s|\b'.$search.'\b|'.$replace.'|g"';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
}
function replace_code_references_helpers($search, $replace)
{
    if (! empty($search) && ! empty($replace)) {
        $cmd = 'cd '.app_path().'/Helpers && find . -name "*.php" | xargs -n 1 sed -i -e "s|\b'.$search.'\b|'.$replace.'|g"';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
}

function copy_erp_module($module_id)
{
    try {
        if (empty(session('instance')->id)) {
            return false;
        }

        $instances = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', '!=', session('instance')->id)->get();
        $module = DB::table('erp_cruds')->where('id', $module_id)->get()->first();
        if ($module->custom) {
            return false;
        }

        if (is_main_instance()) {
            $main_module_id = $module_id;
            foreach ($instances as $instance) {
                $data = (array) $module;
                unset($data['id']);
                $data['main_instance_id'] = $module_id;
                $exists = \DB::connection($instance->db_connection)->table('erp_cruds')->where('main_instance_id', $module_id)->count();
                if (! $exists) {
                    \DB::connection($instance->db_connection)->table('erp_cruds')->insert($data);
                } else {
                    \DB::connection($instance->db_connection)->table('erp_cruds')->where('main_instance_id', $module_id)->update($data);
                }
            }
        } else {
            $main_module_id = $module->main_instance_id;

            $main_module_exists = \DB::connection('system')->table('erp_cruds')->where('id', $main_module_id)->count();
            if (empty($main_module_id) || ! $main_module_exists) {
                $data = (array) $module;
                unset($data['id']);
                unset($data['main_instance_id']);
                $main_module_id = \DB::connection('system')->table('erp_cruds')->insertGetId($data);
                DB::table('erp_cruds')->where('id', $module_id)->update(['main_instance_id' => $main_module_id]);
            } else {
                $data = (array) $module;
                unset($data['id']);
                unset($data['main_instance_id']);
                \DB::connection('system')->table('erp_cruds')->where('id', $main_module_id)->update($data);
            }

            $main_module = \DB::connection('system')->table('erp_cruds')->where('id', $main_module_id)->get()->first();
            foreach ($instances as $instance) {
                if ($instance->id == 1) {
                    continue;
                }
                $data = (array) $main_module;
                unset($data['id']);
                $data['main_instance_id'] = $main_module->id;
                $exists = \DB::connection($instance->db_connection)->table('erp_cruds')->where('main_instance_id', $main_module->id)->count();
                if (! $exists) {
                    \DB::connection($instance->db_connection)->table('erp_cruds')->insert($data);
                } else {
                    \DB::connection($instance->db_connection)->table('erp_cruds')->where('main_instance_id', $main_module->id)->update($data);
                }
            }
        }

        if (empty($main_module_id)) {
            return false;
        }

        $module_fields = DB::table('erp_module_fields')->where('custom', 0)->where('module_id', $module_id)->get();

        $field_buttons = DB::table('erp_grid_buttons')->where('custom', 0)->where('module_id', $module_id)->get();
        $field_views = DB::table('erp_grid_views')->where('custom', 0)->where('module_id', $module_id)->get();
        $form_events = DB::table('erp_form_events')->where('custom', 0)->where('module_id', $module_id)->get();

        $emails = DB::table('crm_email_manager')->where('custom', 0)->where('module_id', $module_id)->get();
        $menus = DB::table('erp_menu')->where('custom', 0)->where('module_id', $module_id)->get();
        $menu_ids = $menus->pluck('id')->toArray();
        $menu_permissions = DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->get();

        if (is_main_instance()) {
            foreach ($instances as $instance) {
                $instance_module_id = \DB::connection($instance->db_connection)->table('erp_cruds')->where('main_instance_id', $main_module_id)->pluck('id')->first();
                foreach ($module_fields as $field) {
                    $data = (array) $field;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_module_fields')->where('main_instance_id', $field->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_module_fields')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_module_fields')->where('main_instance_id', $field->id)->update($data);
                    }
                }

                foreach ($field_buttons as $field_button) {
                    $data = (array) $field_button;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_grid_buttons')->where('main_instance_id', $field_button->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_grid_buttons')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_grid_buttons')->where('main_instance_id', $field_button->id)->update($data);
                    }
                }

                foreach ($field_views as $field_view) {
                    $data = (array) $field_view;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_grid_views')->where('main_instance_id', $field_view->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_grid_views')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_grid_views')->where('main_instance_id', $field_view->id)->update($data);
                    }
                }

                foreach ($form_events as $form_event) {
                    $data = (array) $form_event;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_form_events')->where('main_instance_id', $form_event->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_form_events')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_form_events')->where('main_instance_id', $form_event->id)->update($data);
                    }
                }

                foreach ($emails as $email) {
                    $data = (array) $email;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('crm_email_manager')->where('main_instance_id', $email->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('crm_email_manager')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('crm_email_manager')->where('main_instance_id', $email->id)->update($data);
                    }
                }
            }
        } else {
            foreach ($instances as $instance) {
                if ($instance->id == 1) {
                    $connect_field = 'id';
                    $instance_module_id = $main_module_id;
                } else {
                    $connect_field = 'main_instance_id';
                    $instance_module_id = \DB::connection($instance->db_connection)->table('erp_cruds')->where('main_instance_id', $main_module_id)->pluck('id')->first();
                }

                foreach ($module_fields as $field) {
                    $data = (array) $field;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_module_fields')->where($connect_field, $field->main_instance_id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_module_fields')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_module_fields')->where($connect_field, $field->main_instance_id)->update($data);
                    }
                }

                foreach ($field_buttons as $field_button) {
                    $data = (array) $field_button;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_grid_buttons')->where($connect_field, $field_button->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_grid_buttons')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_grid_buttons')->where($connect_field, $field_button->id)->update($data);
                    }
                }

                foreach ($field_views as $field_view) {
                    $data = (array) $field_view;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_grid_views')->where($connect_field, $field_view->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_grid_views')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_grid_views')->where($connect_field, $field_view->id)->update($data);
                    }
                }

                foreach ($form_events as $form_event) {
                    $data = (array) $form_event;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('erp_form_events')->where($connect_field, $form_event->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('erp_form_events')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('erp_form_events')->where($connect_field, $form_event->id)->update($data);
                    }
                }

                foreach ($emails as $email) {
                    $data = (array) $email;
                    unset($data['id']);
                    $data['module_id'] = $instance_module_id;
                    $exists = \DB::connection($instance->db_connection)->table('crm_email_manager')->where($connect_field, $email->id)->count();
                    if (! $exists) {
                        \DB::connection($instance->db_connection)->table('crm_email_manager')->insert($data);
                    } else {
                        \DB::connection($instance->db_connection)->table('crm_email_manager')->where($connect_field, $email->id)->update($data);
                    }
                }
            }
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        exception_email($ex, __FUNCTION__.' error');
    }
}
