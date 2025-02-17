<?php

class ErpMenu
{
    public static $is_context_menu = false;

    public static $modules;

    public static $forms;

    public static $roles;

    public static $menu_role_access;

    public static function build_menu($location, $menu_params = [], $render_module_id = false, $return_db_menu = false, $processed_descending_ids = [])
    {

        self::$modules = app('erp_config')['modules'];
        self::$forms = app('erp_config')['forms'];
        self::$menu_role_access = app('erp_config')['menu_access'];
        self::$roles = \DB::connection('default')->table('erp_user_roles')->get();

        if (! empty($menu_params) && count($menu_params) > 0) {
            foreach ($menu_params as $key => $val) {
                if ($key == 'menu_id' && empty($val)) {
                    $menu_params['menu_id'] = 0;
                }
            }
        }

        $use_cache = true;
        if (! empty(request()->cidb)) {
            $use_cache = false;
        }
        if ($use_cache) {
            $lookup_complete = false;
            $is_workspace_menu = false;
            $enable_workspace_menu_from_left_menu = false;
            if ($enable_workspace_menu_from_left_menu && $location == 'module_menu' && is_main_instance()) {
                $workspace_ids = self::$modules->where('is_workspace_module', 1)->pluck('id')->toArray();
                $workspace_ids[] = 334;
                if (in_array($menu_params['module_id'], $workspace_ids)) {
                    $location = 'main_menu';
                    $is_workspace_menu = true;

                    $events_menus = Cache::rememberForever(session('instance')->id.'events_menus'.$render_module_id.$location, function () use ($render_module_id) {
                        return \DB::connection('default')->table('erp_menu')
                            ->select('erp_menu.*')
                            ->where('location', 'main_menu')
                            ->where('erp_menu.unlisted', 0)
                            ->where('erp_menu.module_id', '>', 0)
                            ->where('erp_menu.workspace_render_id', $render_module_id)
                            ->orderBy('require_grid_id', 'desc')
                            ->orderBy('sort_order')->get();
                    });

                    $link_menus = Cache::rememberForever(session('instance')->id.'linkmenus'.$render_module_id.$location, function () use ($render_module_id) {
                        return \DB::connection('default')->table('erp_menu')
                            ->where('location', 'main_menu')
                            ->where('erp_menu.unlisted', 0)
                            ->whereNull('erp_menu.module_id')
                            ->where('erp_menu.workspace_render_id', $render_module_id)
                            ->orderBy('sort_order')->get();
                    });
                    $lookup_complete = true;
                } else {
                    $workspace_render_id = \DB::connection('default')->table('erp_menu')->where('workspace_render_id', '>', 0)->where('module_id', $render_module_id)->pluck('workspace_render_id')->first();
                    if ($workspace_render_id) {

                        $location = 'main_menu';
                        $is_workspace_menu = true;
                        $events_menus = Cache::rememberForever(session('instance')->id.'events_menus'.$workspace_render_id.$location, function () use ($workspace_render_id) {
                            return \DB::connection('default')->table('erp_menu')
                                ->select('erp_menu.*')
                                ->where('location', 'main_menu')
                                ->where('erp_menu.unlisted', 0)
                                ->where('erp_menu.module_id', '>', 0)
                                ->where('erp_menu.workspace_render_id', $workspace_render_id)
                                ->orderBy('require_grid_id', 'desc')
                                ->orderBy('sort_order')->get();
                        });

                        $link_menus = Cache::rememberForever(session('instance')->id.'linkmenus'.$workspace_render_id.$location, function () use ($workspace_render_id) {
                            return \DB::connection('default')->table('erp_menu')
                                ->where('location', 'main_menu')
                                ->where('erp_menu.unlisted', 0)
                                ->whereNull('erp_menu.module_id')
                                ->where('erp_menu.workspace_render_id', $workspace_render_id)
                                ->orderBy('sort_order')->get();
                        });
                        $lookup_complete = true;
                    }
                }
            }

            if (! $lookup_complete) {
                if ($render_module_id) {

                    $events_menus = Cache::rememberForever(session('instance')->id.'events_menus'.$render_module_id.$location, function () use ($location, $render_module_id) {
                        return \DB::connection('default')->table('erp_menu')
                            ->select('erp_menu.*')
                            ->where('location', $location)
                            ->where('erp_menu.unlisted', 0)
                            ->where('erp_menu.module_id', '>', 0)
                            ->where('erp_menu.render_module_id', $render_module_id)
                            ->orderBy('require_grid_id', 'desc')
                            ->orderBy('sort_order')->get();
                    });

                    $link_menus = Cache::rememberForever(session('instance')->id.'linkmenus'.$render_module_id.$location, function () use ($location, $render_module_id) {
                        return \DB::connection('default')->table('erp_menu')
                            ->where('location', $location)
                            ->where('erp_menu.unlisted', 0)
                            ->whereNull('erp_menu.module_id')
                            ->where('erp_menu.render_module_id', $render_module_id)
                            ->orderBy('sort_order')->get();
                    });

                } else {
                    $events_menus = Cache::rememberForever(session('instance')->id.'events_menus'.$location, function () use ($location) {
                        return \DB::connection('default')->table('erp_menu')
                            ->select('erp_menu.*')
                            ->where('location', $location)
                            ->where('erp_menu.unlisted', 0)
                            ->where('erp_menu.module_id', '>', 0)
                            ->orderBy('sort_order')->get();
                    });

                    $link_menus = Cache::rememberForever(session('instance')->id.'linkmenus'.$location, function () use ($location) {
                        return \DB::connection('default')->table('erp_menu')
                            ->where('location', $location)
                            ->where('erp_menu.unlisted', 0)
                            ->whereNull('erp_menu.module_id')
                            ->orderBy('sort_order')->get();
                    });
                }
            }
        } else {

            $lookup_complete = false;
            $is_workspace_menu = false;
            $enable_workspace_menu_from_left_menu = false;
            if ($enable_workspace_menu_from_left_menu && $location == 'module_menu' && is_main_instance()) {
                $workspace_ids = self::$modules->where('is_workspace_module', 1)->pluck('id')->toArray();
                $workspace_ids[] = 334;
                if (in_array($menu_params['module_id'], $workspace_ids)) {
                    $location = 'main_menu';
                    $is_workspace_menu = true;

                    $events_menus = \DB::connection('default')->table('erp_menu')
                        ->select('erp_menu.*')
                        ->where('location', 'main_menu')
                        ->where('erp_menu.unlisted', 0)
                        ->where('erp_menu.module_id', '>', 0)
                        ->where('erp_menu.workspace_render_id', $render_module_id)
                        ->orderBy('require_grid_id', 'desc')
                        ->orderBy('sort_order')->get();

                    $link_menus = \DB::connection('default')->table('erp_menu')
                        ->where('location', 'main_menu')
                        ->where('erp_menu.unlisted', 0)
                        ->whereNull('erp_menu.module_id')
                        ->where('erp_menu.workspace_render_id', $render_module_id)
                        ->orderBy('sort_order')->get();

                    $lookup_complete = true;
                } else {
                    $workspace_render_id = \DB::connection('default')->table('erp_menu')->where('workspace_render_id', '>', 0)->where('module_id', $render_module_id)->pluck('workspace_render_id')->first();
                    if ($workspace_render_id) {

                        $location = 'main_menu';
                        $is_workspace_menu = true;
                        $events_menus = \DB::connection('default')->table('erp_menu')
                            ->select('erp_menu.*')
                            ->where('location', 'main_menu')
                            ->where('erp_menu.unlisted', 0)
                            ->where('erp_menu.module_id', '>', 0)
                            ->where('erp_menu.workspace_render_id', $workspace_render_id)
                            ->orderBy('require_grid_id', 'desc')
                            ->orderBy('sort_order')->get();

                        $link_menus = \DB::connection('default')->table('erp_menu')
                            ->where('location', 'main_menu')
                            ->where('erp_menu.unlisted', 0)
                            ->whereNull('erp_menu.module_id')
                            ->where('erp_menu.workspace_render_id', $workspace_render_id)
                            ->orderBy('sort_order')->get();

                        $lookup_complete = true;
                    }
                }
            }

            if (! $lookup_complete) {
                if ($render_module_id) {

                    $events_menus = \DB::connection('default')->table('erp_menu')
                        ->select('erp_menu.*')
                        ->where('location', $location)
                        ->where('erp_menu.unlisted', 0)
                        ->where('erp_menu.module_id', '>', 0)
                        ->where('erp_menu.render_module_id', $render_module_id)
                        ->orderBy('require_grid_id', 'desc')
                        ->orderBy('sort_order')->get();

                    $link_menus = \DB::connection('default')->table('erp_menu')
                        ->where('location', $location)
                        ->where('erp_menu.unlisted', 0)
                        ->whereNull('erp_menu.module_id')
                        ->where('erp_menu.render_module_id', $render_module_id)
                        ->orderBy('sort_order')->get();

                } else {
                    $events_menus = \DB::connection('default')->table('erp_menu')
                        ->select('erp_menu.*')
                        ->where('location', $location)
                        ->where('erp_menu.unlisted', 0)
                        ->where('erp_menu.module_id', '>', 0)
                        ->orderBy('sort_order')->get();

                    $link_menus = \DB::connection('default')->table('erp_menu')
                        ->where('location', $location)
                        ->where('erp_menu.unlisted', 0)
                        ->whereNull('erp_menu.module_id')
                        ->orderBy('sort_order')->get();
                }
            }
        }

        if ($location == 'module_menu' && ! $render_module_id) {
            $events_menus = collect([]);
            $link_menus = collect([]);
        }
        /*
        if($location == 'module_menu' && !is_main_instance()){
            $workspace_ids = self::$modules->where('is_workspace_module',1)->pluck('id')->toArray();
            if(!in_array($menu_params['module_id'],$workspace_ids)){
                $events_menus = collect([]);
                $link_menus = collect([]);
            }
        }
        */

        if (is_main_instance()) {
            if ($location == 'top_left_menu' && session('role_level') == 'Admin') {

                if (empty($menu_params) || empty($menu_params['url']) || $menu_params['url'] != 'dashboard') {
                    if (! empty($menu_params['module_id'])) {
                        $events_menus = collect([]);
                        $link_menus = collect([]);
                    }
                }
            }
        }
        $role_ids = session('role_ids');
        if (! empty($menu_params) && ! empty($menu_params['workspace_role_id'])) {
            $role_ids = [$menu_params['workspace_role_id']];
        }
        $forms = self::$forms;
        $menu_role_access = app('erp_config')['menu_access'];
        foreach ($events_menus as $i => $events_menu) {
            $events_menus[$i]->is_allow = $forms->where('module_id', $events_menu->module_id)->where('is_view', 1)->whereIn('role_id', $role_ids)->count();
        }
        foreach ($link_menus as $i => $link_menu) {
            $link_menus[$i]->is_allow = $menu_role_access->where('menu_id', $link_menu->id)->where('is_menu', 1)->whereIn('role_id', $role_ids)->count();

        }

        if ($location == 'grid_menu') {
            if (! self::$is_context_menu) {
                // toolbar grid menu - check if customer has access
                foreach ($events_menus as $i => $events_menu) {
                    $customer_access = $forms->where('module_id', $events_menu->module_id)->where('is_view', 1)->where('role_id', 21)->count();
                    if ($customer_access) {
                        $events_menus[$i]->customer_access = 1;
                    }
                    // $events_menus[$i]->is_allow = $customer_access;
                }
                foreach ($link_menus as $i => $link_menu) {
                    $customer_access = $menu_role_access->where('menu_id', $link_menu->id)->where('is_menu', 1)->where('role_id', 21)->count();
                    if ($customer_access) {

                        $link_menus[$i]->customer_access = 1;
                    }
                    // $link_menus[$i]->is_allow = $customer_access;
                }

            } else {
                // admin only context menu
                /*
                foreach($events_menus as $i => $events_menu){
                    $customer_is_allow = $forms->where('module_id',$events_menu->module_id)->where('is_view',1)->where('role_id',21)->count();
                    if($customer_is_allow){
                        $events_menus[$i]->is_allow = 0;
                    }else{
                        $events_menus[$i]->is_allow = $events_menu->is_allow;
                    }
                }
                foreach($link_menus as $i => $link_menu){
                    $customer_is_allow = $menu_role_access->where('menu_id',$link_menu->id)->where('is_menu',1)->where('role_id',21)->count();

                    if($customer_is_allow){
                        $link_menus[$i]->is_allow = 0;
                    }else{
                        $link_menus[$i]->is_allow = $link_menu->is_allow;
                    }

                }
                */
            }
        }

        $combined_menu = $events_menus->merge($link_menus)->sortBy('sort_order');
        $menu = [];
        $is_superadmin = is_superadmin();

        if ($is_workspace_menu) {
            $top_menu_id = $combined_menu->where('parent_id', 0)->pluck('id')->first();
            foreach ($combined_menu as $i => $m) {

                $combined_menu[$i]->menu_icon = '';
                if ($m->parent_id == $top_menu_id) {
                    $combined_menu[$i]->parent_id = 0;
                }

                if ($m->id == $top_menu_id) {
                    unset($combined_menu[$i]);
                }
            }
        }

        $subscription_product_ids = session('subscription_product_ids');
        if (! empty($menu_params['subscription_product_ids'])) {

            $subscription_product_ids = $menu_params['subscription_product_ids'];
        }
        // SET ACCESS BASED ON SUBSCRIPTIONS
        if ((session('role_level') != 'Admin' && $location == 'services_menu') || ! empty($menu_params['subscription_product_ids'])) {
            //dddd(session('subscription_product_ids'));
            foreach ($combined_menu as $i => $m) {

                if (! empty($m->subscription_product_id)) {

                    if (empty($subscription_product_ids) || (is_array($subscription_product_ids) && count($subscription_product_ids) == 0)) {
                        $combined_menu[$i]->is_allow = false;

                        continue;
                    }
                    $menu_product_ids = explode(',', $m->subscription_product_id);
                    $allow = false;
                    foreach ($menu_product_ids as $menu_product_id) {
                        if (in_array($menu_product_id, $subscription_product_ids)) {
                            $allow = true;
                            break;
                        }
                    }

                    if (! $allow) {
                        //$combined_menu[$i]->is_allow = false;
                        unset($combined_menu[$i]);
                    }
                }
            }
        }

        // SET ACCESS RESELLER USERS
        if (session('customer_type') == 'reseller_user') {
            foreach ($combined_menu as $i => $m) {

                if (! empty($m->hide_for_reseller_users)) {
                    $combined_menu[$i]->is_allow = false;
                }
            }
        }

        if ($location == 'module_actions' || $location == 'grid_menu') {
            foreach ($combined_menu as $i => $m) {
                if (! empty($m->access_role_ids)) {
                    $ari = explode(',', $m->access_role_ids);
                    if (! in_array(session('role_id'), $ari)) {
                        $combined_menu[$i]->is_allow = false;
                    }
                }
            }
        }

        if (session('role_level') == 'Admin' && in_array(2, session('app_ids'))) {

            foreach ($combined_menu as $m) {
                $menu_enabled = false;

                if ($m->is_allow) {
                    $menu_enabled = true;
                }
                if ($location == 'grid_menu' && ! self::$is_context_menu && ! $menu_enabled) {
                    continue;
                }

                if ($location == 'customer_menu' && ! $menu_enabled) {
                    continue;
                }

                $m->menu_enabled = $menu_enabled;
                $menu[] = $m;
            }
        } else {

            foreach ($combined_menu as $m) {
                if ($m->is_allow) {
                    $menu[] = $m;
                }
            }
        }

        $menu = collect($menu);

        foreach ($menu as $i => $m) {
            if (! empty($m->submenu_function) && $m->submenu_function != 'generate_pbx_menu') {
                $submenu_function = $m->submenu_function;

                if (function_exists($submenu_function)) {

                    $submenu = $submenu_function($menu_params);

                    if (empty($submenu) || count($submenu) == 0) {
                        //  unset($menu[$i]);
                    } else {
                        foreach ($submenu as $sm) {
                            $sm = (object) $sm;
                            $sm->parent_id = $m->id;
                            $sm->location = $m->location;
                            $sm->sort_order = $m->sort_order;
                            $menu[] = $sm;
                        }
                    }
                } else {
                    unset($menu[$i]);
                }
            }
        }

        // add edit_url to menus
        $forms = app('erp_config')['forms'];
        $modules_collection = collect(self::$modules);
        foreach ($menu as $i => $m) {
            if ($m->menu_type == 'module' || $m->menu_type == 'module_filter') {
                $role_access = $forms->where('module_id', $m->module_id)->where('is_add', 1)->where('role_id', session('role_id'))->count();
                $module_permission = $modules_collection->where('id', $m->module_id)->pluck('permissions')->first();
                if ($role_access && in_array($module_permission, ['Write', 'Write and Modify', 'All'])) {
                    $slug = $modules_collection->where('id', $m->module_id)->pluck('slug')->first();
                    $menu[$i]->add_url = $slug.'/edit';
                }
            }
        }

        $menu = collect($menu)->sortBy('sort_order');
        if ($return_db_menu) {
            return $menu;
        }
        // format menu to syncfusion properties
        $menu = $menu->map(function ($item) use ($menu_params, $location) {
            return self::format_menu_item_syncfusion($item, $menu_params, $location);
        });

        // map menu to treedata hierachy
        $tree = function ($elements, $parentId = 0) use (&$tree) {
            $branch = [];
            foreach ($elements as $element) {

                if ($element->parent_id == $parentId) {

                    $items = $tree($elements, $element->menu_id);
                    if ($items) {
                        $element->items = $items;
                    }
                    $branch[] = $element;
                }
            }

            return $branch;
        };

        $formatted_menu = $tree($menu);
        if ($location == 'grid_menu' && count($formatted_menu) > 0) {

            $grid_menu_menu = [];
            $grid_menu_top = [];
            foreach ($formatted_menu as $i => $menu_item) {

                if ($menu_item->show_directly_on_toolbar) {
                    $grid_menu_top[] = $menu_item;
                    unset($formatted_menu[$i]);
                }
            }

            foreach ($grid_menu_top as $grid_menu) {
                $grid_menu_menu[] = $grid_menu;
            }

            $formatted_menu = array_values($formatted_menu);
            if (count($formatted_menu) > 0) {
                $grid_menu_menu[] = (object) [
                    'text' => '',
                    'iconCss' => 'fas fa-caret-down',
                    'title' => 'Grid Menu ('.count($formatted_menu).')',
                    'id' => 'actions_menu_top',
                    'cssClass' => 'btn grid_menu'.$render_module_id,
                    'items' => $formatted_menu,
                    'url' => '#'];
            }

            $formatted_menu = $grid_menu_menu;

        }

        if ($location == 'module_actions' && count($formatted_menu) > 0) {

            $formatted_menu = [(object) [
                'text' => '',
                'iconCss' => 'fas fa-caret-square-down',
                'title' => 'Module Actions ('.count($formatted_menu).')',
                'id' => 'actions_menu_top',
                'cssClass' => 'btn',
                'items' => $formatted_menu,
                'url' => '#']];
        }

        if ($location == 'pbx_menu' && count($formatted_menu) > 0) {

            $formatted_menu = [(object) [
                'text' => 'PBX ('.count($formatted_menu).')',
                'title' => 'PBX',
                'id' => 'actions_menu_top',
                'cssClass' => 'btn',
                'items' => $formatted_menu,
                'url' => '#']];
        }

        if ($location == 'related_items_menu' && count($formatted_menu) > 0) {

            $formatted_menu = [(object) [
                'text' => 'Links ('.count($formatted_menu).')',
                'title' => 'Links',
                'id' => 'related_items_menumenutop',
                'cssClass' => 'btn',
                'items' => $formatted_menu,
                'url' => '#']];

        }

        /*
          if($location == 'pbx_admin_menu' && count($formatted_menu)>0){

            $formatted_menu = [(object) [
                'text'=> 'PBX Admin ('.count($formatted_menu).')',
                'title'=> 'PBX Admin',
                'id'=> 'related_items_menumenutop',
                'cssClass' => 'k-button',
                'items' => $formatted_menu,
                'url' => '#' ]];
        }
        */
        /*
        if($location == 'related_items_menu' && count($formatted_menu)>0){

            $formatted_menu = [(object) [
                'text'=> 'Related Modules ('.count($formatted_menu).')',
                'title'=> 'Links',
                'id'=> 'related_items_menumenutop',
                'cssClass' => 'k-button',
                'items' => $formatted_menu,
                'url' => '#' ]];
        }

         if( $location == 'grid_menu' && count($formatted_menu)>0){

            $formatted_menu = [(object) [
                'text'=> 'Grid Menu ('.count($formatted_menu).')',
                'title'=> 'Grid Menu',
                'id'=> 'grid_menumenutop',
                'cssClass' => 'k-button',
                'items' => $formatted_menu,
                'url' => '#' ]];
        }
        */

        return $formatted_menu;
    }

    public static function format_menu_item_syncfusion($item, $menu_params, $location)
    {
        $modules_collection = collect(self::$modules);
        if ($location == 'main_menu') {
            $item->menu_name = ucfirst(strtolower($item->menu_name));
        }
        if ($location == 'main_menu') {
            $access_letters = '';
            if ($item->module_id && session('role_level') == 'Admin') {

                $customer_access = self::$forms->where('module_id', $item->module_id)->where('is_view', 1)->where('role_id', 21)->count();
                $reseller_access = self::$forms->where('module_id', $item->module_id)->where('is_view', 1)->where('role_id', 11)->count();
                if ($reseller_access && $customer_access) {
                    $access_letters .= 'CR';
                } elseif ($customer_access) {
                    $access_letters .= 'C';
                } elseif ($reseller_access) {
                    $access_letters .= 'R';
                }

                $manual_role_ids = self::$forms->where('module_id', $item->module_id)->where('is_view', 1)->where('is_manual', 1)->pluck('role_id')->toArray();
                foreach ($manual_role_ids as $manual_role_id) {
                    $role_name = self::$roles->where('id', $manual_role_id)->pluck('name')->first();
                    if (! str_contains($access_letters, $role_name[0])) {
                        $access_letters .= $role_name[0];
                    }
                }
                $workboard_role_ids = self::$forms->where('module_id', $item->module_id)->where('is_view', 1)->where('is_workboard', 1)->pluck('role_id')->toArray();
                foreach ($workboard_role_ids as $workboard_role_id) {
                    $role_name = self::$roles->where('id', $workboard_role_id)->pluck('name')->first();
                    if (! str_contains($access_letters, $role_name[0])) {
                        $access_letters .= $role_name[0];
                    }
                }

            } elseif ($item->menu_type == 'link' && session('role_level') == 'Admin') {
                $customer_access = self::$menu_role_access->where('menu_id', $item->id)->where('is_menu', 1)->where('role_id', 21)->count();
                $reseller_access = self::$menu_role_access->where('menu_id', $item->id)->where('is_menu', 1)->where('role_id', 11)->count();
                if ($reseller_access && $customer_access) {
                    $item->menu_name .= ' (CR)';
                } elseif ($customer_access) {
                    $item->menu_name .= ' (C)';
                } elseif ($reseller_access) {
                    $item->menu_name .= ' (R)';
                }
            }

            if ($access_letters > '') {
                $item->menu_name .= ' ('.$access_letters.')';
            }
        }
        $location = $item->location;
        $menu_item = (object) [];
        $menu_item->id = 'menuitem_'.$item->id;
        $menu_item->value = 'menuitem_'.$item->id;
        $menu_item->menu_id = $item->id;
        $menu_item->parent_id = $item->parent_id;
        $menu_item->text = $item->menu_name;
        $menu_item->location = $item->location;
        $menu_item->show_directly_on_toolbar = $item->show_directly_on_toolbar;
        $menu_item->title = $item->menu_name;
        $menu_item->menu_title = $item->menu_name;
        $menu_item->url_params = $item->url_params;
        $menu_item->navlink = strtolower(str_replace(' ', '-', $item->menu_name));
        $menu_item->submenu_function = $item->submenu_function;
        $menu_item->enabled = true;

        if (! empty($item->badge_function)) {
            $badge_function = $item->badge_function;
            if (function_exists($badge_function)) {
                $badge_function_result = $badge_function();

                if ($badge_function_result > '') {
                    $menu_item->text .= ' '.$badge_function_result;
                    $menu_item->title .= ' '.$badge_function_result;
                    $menu_item->menu_title .= ' '.$badge_function_result;
                }
            }
        }

        if (session('role_level') == 'Admin' && isset($item->menu_enabled)) {
            /*
            if($item->menu_type=='link' && $item->url =='#'){
                $menu_item->enabled = true;
            }elseif(!$item->menu_enabled){
                $menu_item->enabled = false;
            }*/
            if (! $item->menu_enabled) {
                $menu_item->enabled = false;
            }
        }

        if ($item->module_id > 0) {
            $item->slug = $modules_collection->where('id', $item->module_id)->pluck('slug')->first();
        }
        if ($item->add_url) {
            $menu_item->add_url = $item->add_url;
        }

        $menu_item->data_target = '';

        if (empty($item->parent_id) && ! empty($item->menu_icon) && $item->show_icon_only) {
            $menu_item->text = '';
            $menu_item->menu_title = '';
        }

        $menu_item->menu_id = $item->id;
        $menu_item->button_function = $item->ajax_function_name;

        $menu_item->cssClass = $location.'btn';

        if ($item->render_module_id && $location != 'module_menu') {
            $menu_item->cssClass .= $item->render_module_id;
        }
        if (session('instance')->id == 12 || (! empty(session('use_dev_views')) && session('use_dev_views'))) {
            if ($location != 'createmenu' && $location != 'main_menu' && $location != 'customer_menu' && ! str_contains($location, 'context')) {
                $menu_item->cssClass .= '';
            }
        } else {
            if ($location != 'createmenu' && $location != 'main_menu' && $location != 'top_right_menu' && $location != 'top_left_menu' && $location != 'customer_menu' && ! str_contains($location, 'context')) {
                $menu_item->cssClass .= ' ';
            }
        }
        if (! empty($item->customer_access)) {

            $menu_item->cssClass .= ' customer-access';
        }

        if (! empty($item->menu_icon) && ! empty($item->parent_id)) {
            $item->menu_icon = '';
        }
        if (! empty($item->menu_icon) && $location == 'dashboard_menu') {
            $item->menu_icon = '';
        }
        if (! empty($item->menu_icon) && $location == 'grid_menu') {
            $item->menu_icon = '';
        }
        if (! empty($item->menu_icon) && $location == 'module_actions') {
            $item->menu_icon = '';
        }

        if (! empty($item->menu_icon) && $item->parent_id == 0) {
            $menu_item->iconCss = $item->menu_icon;
        }

        if (! empty($item->velzon_icon) && is_dev()) {
            $menu_item->velzon_icon = $item->velzon_icon;
        }

        if (! empty($item->module_id)) {
            $menu_item->module_id = $item->module_id;
        }
        if (! empty($item->url) && ! is_string($item->url)) {
            return null;
        }
        if ($item->module_id > 0 && empty($item->slug)) {
            $item->url = '#';
        }

        if (empty($item->url)) {
            $item->url = '';
        }

        if ($item->menu_type == 'sidebarview') {
            $menu_item->url = 'sidebarview(\''.$item->menu_name.'\', \'/modalview/'.$item->url.'\')';
        } elseif ($item->menu_type == 'sidebarview') {
            $menu_item->url = 'loadModalLink(\''.$item->menu_name.'\', \'/modalview/'.$item->url.'\')';
        } elseif ($item->menu_type == 'module_form') {
            $events_menu = $item->slug;

            if (! $menu_item->url_params) {
                $menu_item->url_params = '/edit';
            }
            $menu_item->url = url($events_menu);
        } elseif (stripos($item->url, 'modal') !== false) {
            $menu_item->url = 'loadModalLink(\''.$item->menu_name.'\', \''.$item->url.'\')';
        } elseif ($item->menu_type == 'link') {
            $menu_item->url = url($item->url);
        } elseif ($item->menu_type == 'modal_link') {
            $menu_item->url = 'loadModal(\''.$item->menu_name.'\', \''.$item->url.'\')';
        } elseif ($item->menu_type == 'iframe') {
            $menu_item->url = '/iframe/'.$item->slug;
        } elseif ($item->menu_type == 'none') {
            $menu_item->url = '#';
        } else {
            $menu_item->url = url($item->slug);
        }
        $menu_item->slug = $item->slug;

        if ($item->url == 'update_instance_config' && $location == 'internal' && ! is_main_instance()) {
            return null;
        }

        if ($location == 'status_buttons' || $location == 'grid_menu' || $location == 'related_items_menu' || $location == 'module_actions') {
            if ($location == 'pbx_menu') {
                $item->render_module_id = $menu_params['module_id'];
            }

            $menu_item->confirm_text = $item->confirm_text;
            $menu_item->require_grid_id = $item->require_grid_id;
            $route_name = get_menu_url($item->render_module_id);
            $detail_module = $modules_collection->where('detail_module_id', $item->render_module_id)->count();
            if ($detail_module) {
                $route_name = 'detailmodule_'.$item->render_module_id;
            } else {
                $route_name = get_menu_url_from_module_id($item->render_module_id);
            }

            $menu_item->original_url = '/'.$route_name.'/button/'.$item->id.'/';
            $menu_item->url = '/'.$route_name.'/button/'.$item->id.'/';
            if ($item->in_iframe) {
                $menu_item->in_iframe = 1;
            } else {
                $menu_item->in_iframe = 0;
            }

            if (empty($item->action_type)) {
                $menu_item->url = '#';
                $menu_item->original_url = '#';
            }
            if ($item->url == '#' && $item->menu_type == 'link' && $item->action_type == 'view') {
                $menu_item->url = '#';
                $menu_item->original_url = '#';
                $item->action_type = '';
            }
        }

        if (empty($item->action_type) && $location == 'grid_menu' && $item->menu_type != 'link') {
            $menu_item->data_target = 'view_modal';

        }
        if ($item->action_type == 'view') {

            $menu_item->data_target = 'view_modal';
        }

        if ($item->module_id > '' && $item->menu_type != 'link' && $item->action_type == 'form') {
            $menu_item->data_target = 'sidebarform';
        } elseif ($item->menu_type == 'link' && $item->action_type == 'form') {
            $menu_item->data_target = 'form_modal';
        }
        if ($item->action_type == 'transaction') {
            $menu_item->data_target = 'transaction_modal';
        }
        if ($item->action_type == 'ajax' && ! empty($item->confirm_text)) {
            $menu_item->data_target = 'ajaxconfirm';
            $menu_item->confirm_text = $item->confirm_text;
        }
        if ($item->action_type == 'ajax' && empty($item->confirm_text)) {
            $menu_item->data_target = 'ajax';
        }

        if ($item->action_type == 'javascript') {
            $menu_item->data_target = 'javascript';
            $menu_item->url = $item->url;
        }

        if (! empty($item->new_tab)) {
            $menu_item->new_tab = 1;
        } else {
            $menu_item->new_tab = 0;
        }

        if ($location == 'dashboard_menu') {
            $menu_item->new_tab = 1;
        }

        if (! in_array($location, ['grid_menu', 'module_actions', 'context_builder'])) {
            if ($item->menu_type == 'module' || $item->menu_type == 'module_filter') {
                if ($item->action_type != 'transaction') {

                    $menu_item->data_target = 'redirect';
                    $menu_item->new_tab = 1;

                }
            }
        }
        if (in_array($location, ['grid_menu', 'module_actions'])) {
            if ($item->menu_type == 'module' || $item->menu_type == 'module_filter') {
                if ($item->action_type != 'transaction') {

                    $menu_item->data_target = 'redirect';
                    $menu_item->new_tab = 1;

                }
            }
        }
        if ($item->action_type == 'form') {
            $menu_item->data_target = 'form_modal';
        }

        if (! empty($item->add_divider) && $location != 'related_items_menu') {
            $menu_item->border_top = 1;
        } else {
            $menu_item->border_top = 0;
        }

        // merge menu params

        if (is_string($menu_item->url) && is_string($menu_item->url_params)) {
            $menu_item->url .= $menu_item->url_params;
        }
        $account_id = session('account_id');
        if ($account_id == 1 && is_main_instance() && $menu_item->module_id != 512 && $menu_item->module_id != 343 && $menu_item->module_id != 348) {
            $account_id = 12;
        }

        if (is_string($menu_item->url)) {

            $menu_params['account_id'] = $account_id;
            $menu_params['role_id'] = session('role_id');
            $menu_params['user_id'] = session('user_id');
            $menu_params['instance_dir'] = session('instance')->directory;
            if (! empty($menu_params['module_id'])) {
                $menu_params['slug'] = $modules_collection->where('id', $menu_params['module_id'])->pluck('slug')->first();
            }
            if (! empty(session('active_user_id')) && session('active_user_id') != session('user_id')) {
                $menu_params['user_id'] = session('active_user_id');
            }

            foreach ($menu_params as $key => $val) {
                $menu_item->url = str_replace('{{$'.$key.'}}', $val, $menu_item->url);
            }
        }

        if ($location == 'customer_menu' && session('role_level') == 'Admin' && $item->menu_type != 'link' && $menu_item->parent_id != 3246) {
            $menu_item->url = '/user/loginas/12/'.str_replace('https://'.session('instance')->domain_name.'/', '', $menu_item->url);
        }

        return $menu_item;
    }

    public static function getAggridContextMenu($render_module_id, $menu_params = [])
    {

        self::$is_context_menu = true;

        $context_js = '';
        $buttons = self::build_menu('grid_menu', $menu_params, $render_module_id, true);
        $buttons = collect($buttons)->where('is_allow', 1);

        $right_click_buttons = $buttons;
        $group_ids = $buttons->where('parent_id', '>', 0)->pluck('parent_id')->unique()->toArray();

        $right_click_groups = $buttons->whereIn('id', $group_ids)->all();

        foreach ($right_click_buttons as $btn) {

            $context_js .= 'var disabled'.$btn->id.' = false;
            ';
            if (! empty($btn->grid_logic)) {
                $context_js .= '
               
                if('.$btn->grid_logic.'){
                    var disabled'.$btn->id.' = false;
                }else{
                    var disabled'.$btn->id.' = true;
                }
                
                ';
            }
        }
        $button_js = 'var contextbuttons = [';
        $route_name = get_menu_url_from_module_id($render_module_id);

        $right_click_buttons = $right_click_buttons->where('parent_id', '0');

        $previous_require_grid_id = 'start';
        if (! empty($right_click_buttons)) {
            foreach ($right_click_buttons as $button) {

                if ($previous_require_grid_id !== 'start' && $previous_require_grid_id != $button->require_grid_id) {
                    $button_js .= "'separator',";
                }
                $button_js .= self::getAggridContextMenuAction($route_name, $button, $buttons);
                $previous_require_grid_id = $button->require_grid_id;
            }
        }

        $button_js .= '
        ];
        result.push(...contextbuttons);
        ';

        return $context_js.$button_js;
    }

    public static function getAggridContextMenuAction($route_name, $button, $buttons)
    {

        $button_js = '
            {
            name: "'.$button->menu_name.'",
            disabled: disabled'.$button->id.',';

        $has_sub_menu = $buttons->where('parent_id', $button->id)->count();
        if ($has_sub_menu) {

            $button_js .= 'subMenu: [';
            $group_buttons = $buttons->where('parent_id', $button->id)->all();
            foreach ($group_buttons as $button) {

                $button_js .= self::getAggridContextMenuAction($route_name, $button, $buttons);
            }
            $button_js .= '
            ],
            ';
        }
        $button_js .= 'action: function () {
        ';

        $button->url = '/'.$route_name.'/button/'.$button->id.'/';

        if (! empty($button->confirm_text)) {
            $button_js .= '
			var confirmation = confirm("'.$button->confirm_text.'");
            if (confirmation) {
			';
        }

        $button_js .= "
		
			
			var url = '".$button->url."'+selected.id;
			";

        if ($button->action_type == 'redirect') {
            $button_js .= '
			window.open(url);
			';
        }

        if ($button->action_type == 'ajax') {
            $button_js .= '
            console.log(grid_filters);
                if(typeof grid_filters === "undefined"){
                grid_filters = null;
                }
                gridAjax(url,{grid_filters:grid_filters},"post");
			';
        }

        if ($button->action_type == 'javascript') {
            $button_js .= '
    	
			'.$button->url.'();
			';
        }

        if ($button->action_type == 'transaction' || $button->action_type == 'form' || $button->action_type == 'view') {
            $height = 'auto';
            if ($button->action_type == 'transaction_modal') {
                $modal_type = 'transactionDialog';
            }
            if ($button->action_type == 'form') {
                if ($button->module_id > '' && $button->menu_type != 'link') {
                    $modal_type = 'sidebarform';
                } else {
                    $modal_type = 'formDialog';
                }

            }

            if ($button->action_type == 'view') {
                $modal_type = 'viewDialog';
            }

            $button_js .= '
				'.$modal_type.'("'.$button->id.'" ,url,"'.$button->menu_name.'");
				';

        }

        if (! empty($button->confirm_text)) {
            $button_js .= '
            }
			';
        }
        $button_js .= '
        }
        },
        ';

        return $button_js;
    }
}
