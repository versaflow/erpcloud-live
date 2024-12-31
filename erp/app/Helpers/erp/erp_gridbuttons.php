<?php

function beforesave_check_button_function($request)
{
    if (empty($request->module_id)) {
        return 'Module required.';
    }
    if (! empty($request->id)) {
        $name_exists = \DB::table('erp_grid_buttons')->where('id', '!=', $request->id)->where('module_id', $request->module_id)->where('type', $request->type)->where('name', $request->name)->count();
    } else {
        $name_exists = \DB::table('erp_grid_buttons')->where('type', $request->type)->where('module_id', $request->module_id)->where('name', $request->name)->count();
    }
    if ($name_exists) {
        //   return 'Button Names needs to be unique.';
    }

    $module_name = \DB::table('erp_cruds')->where('id', $request->module_id)->pluck('name')->first();
    if (empty($module_name)) {
        return 'Invalid module';
    }
    $button = (object) $request->all();
    if ($button->type != 'modal_view') {
        if (empty($button->function_name)) {
            if (empty($button->redirect_module_id) && empty($button->redirect_url)) {
                $function = function_format('button_'.$module_name.'_'.$request->name);

                if (! function_exists($function)) {
                    $added = add_code_definition($function, 'request');
                    if (! $added) {
                        return 'Could not create function definition.';
                    }
                }
            }
        } elseif (! empty($button->function_name)) {
            if (! function_exists($button->function_name)) {
                $added = add_code_definition($button->function_name, 'request');
                if (! $added) {
                    return 'Could not create function definition.';
                }
            }
        }
    }
}

function aftersave_set_button_definitions($request)
{
    $buttons = \DB::table('erp_grid_buttons')->get();
    foreach ($buttons as $button) {
        $access = collect(explode(',', $button->access))->filter()->toArray();
        $context_menu = 0;
        if (! in_array(11, $access) && ! in_array(21, $access)) {
            $context_menu = 1;
        }
        $admin_button = 0;
        if (! in_array(11, $access) && ! in_array(21, $access)) {
            $admin_button = 1;
        }
        if (count($access) == 0) {
            $admin_button = 0;
            $context_menu = 0;
        }
        $function_definition = '';
        $function_name = $button->function_name;
        if (empty($button->redirect_module_id) && empty($button->redirect_url)) {
            if (function_exists($function_name)) {
                $r = new \ReflectionFunction($function_name);
                $file = $r->getFileName();
                $helper = end(explode('/', $file));
                $startLine = $r->getStartLine();
                $function_definition = $helper.':'.$startLine.' - '.$function_name;
            }
        } else {
            $function_name = '';
        }

        \DB::table('erp_grid_buttons')->where('id', $button->id)->update(['admin_button' => $admin_button, 'context_menu' => $context_menu, 'button_definition' => $function_definition, 'function_name' => $function_name]);
    }
}

function button_redirect($button, $request)
{
    $args = [];

    $args['id'] = $request->id;

    if ($button->require_grid_id) {
        $module = \DB::connection('default')->table('erp_cruds')->where('id', $button->module_id)->get()->first();
        $conn = $module->connection;
        $table = $module->db_table;
        $cols = get_columns_from_schema($table, null, $conn);

        $row = null;
        if (in_array('id', $cols)) {
            $args = \DB::connection($conn)->table($table)->where('id', $request->id)->get()->first();
            $args = (array) $args;
        } elseif (in_array($module->db_key, $cols)) {
            $args = \DB::connection($conn)->table($table)->where($module->db_key, $request->id)->get()->first();
            $args = (array) $args;
        }
    }

    if (! empty($button->redirect_module_id)) {
        $url = get_menu_url($button->redirect_module_id);
    } elseif (! empty($button->redirect_url)) {
        $url = $button->redirect_url;
    } else {

        return false;
    }

    if (! empty($url)) {
        foreach ($request->all() as $k => $v) {
            $args[$k] = $v;
        }

        if (! empty(session('statement_account_id'))) {
            $args['statement_account_id'] = session('statement_account_id');
        }

        if (! empty(session('statement_supplier_id'))) {
            $args['statement_supplier_id'] = session('statement_supplier_id');
        }

        $url = view(['template' => $url])->with($args)->render();
    }

    if (! empty($button->redirect_params)) {
        foreach ($request->all() as $k => $v) {
            $args[$k] = $v;
        }

        if (! empty(session('statement_account_id'))) {
            $args['statement_account_id'] = session('statement_account_id');
        }

        if (! empty(session('statement_supplier_id'))) {
            $args['statement_supplier_id'] = session('statement_supplier_id');
        }

        $params = view(['template' => $button->redirect_params])->with($args)->render();
        $url .= $params;
    }

    if (! empty($button->in_iframe)) {
        if (str_contains($url, '?')) {
            $url .= '&from_iframe=1';
        } else {
            $url .= '?from_iframe=1';
        }
    }

    return Redirect::to($url);
}

function button_menu_redirect($button, $request)
{

    $args = [];

    $args['id'] = $request->id;

    if ($button->require_grid_id) {
        $module = \DB::connection('default')->table('erp_cruds')->where('db_table', $request->db_table)->get()->first();
        $conn = $module->connection;
        $table = $module->db_table;
        $cols = get_columns_from_schema($table, null, $conn);

        $row = null;
        if (in_array($module->db_key, $cols)) {
            $args = \DB::connection($conn)->table($table)->where($module->db_key, $request->id)->get()->first();
            $args = (array) $args;
        } elseif (in_array('id', $cols)) {
            $args = \DB::connection($conn)->table($table)->where('id', $request->id)->get()->first();
            $args = (array) $args;
        }
    }

    if (! empty($button->module_id)) {
        $url = get_menu_url_from_module_id($button->module_id);
    } elseif (! empty($button->url)) {
        $url = $button->url;
    } else {
        return false;
    }

    if ($button->location == 'pbx_menu' || $button->generated_from_pbx_menu == 1) {
        $args['module_id'] = \DB::connection('default')->table('erp_cruds')->where('db_table', $request->db_table)->pluck('id')->first();
    }

    //  aa($args);
    //  aa($button);
    if (! empty($url)) {
        foreach ($request->all() as $k => $v) {
            $args[$k] = $v;
        }

        if (! empty(session('statement_account_id'))) {
            $args['statement_account_id'] = session('statement_account_id');
        }

        if (! empty(session('statement_supplier_id'))) {
            $args['statement_supplier_id'] = session('statement_supplier_id');
        }

        $url = view(['template' => $url])->with($args)->render();
    }

    if (! empty($button->url_params)) {
        foreach ($request->all() as $k => $v) {
            $args[$k] = $v;
        }

        if (! empty(session('statement_account_id'))) {
            $args['statement_account_id'] = session('statement_account_id');
        }

        if (! empty(session('statement_supplier_id'))) {
            $args['statement_supplier_id'] = session('statement_supplier_id');
        }

        $params = view(['template' => $button->url_params])->with($args)->render();

        $url .= $params;
    }

    if (! empty($button->in_iframe)) {
        if (str_contains($url, '?')) {
            $url .= '&from_iframe=1';
        } else {
            $url .= '?from_iframe=1';
        }
    }

    //aa($url);
    return Redirect::to($url);
}
