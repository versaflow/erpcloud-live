<?php

class Erp
{
    public static function panel_domains($panel_id)
    {
        $pbx = new FusionPBX;
        if ($panel_id == 1) {
            return $pbx->pbx_panels();
        }

        if ($panel_id == 3) {
            return $pbx->sms_panels();
        }
    }

    public static function panel_info($panel_id)
    {
        $info = [];
        if ($panel_id == 1) {
            if (session('pbx_account_id') == 1) {
                return [];
            }

            $domain = \DB::connection('pbx')->table('v_domains')
                ->select('currency', 'balance', 'partner_id')
                ->where('account_id', session('pbx_account_id'))
                ->get()->first();

            if ($domain->partner_id != 1 && session('original_role_id') <= 11) {
                $call_profits = \DB::connection('pbx')->table('p_partners')->where('partner_id', $domain->partner_id)->pluck('voice_prepaid_profit')->first();

                $info['Call Profits'] = 'R '.currency($call_profits);
            }

            $info['Balance'] = currency($domain->balance);
            if ($domain->currency == 'ZAR') {
                $info['Balance'] = 'R '.$info['Balance'];
            }
            if ($domain->currency == 'USD') {
                $info['Balance'] = '$ '.$info['Balance'];
            }
        }

        if ($panel_id == 3) {
            $balance = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')
                ->where('account_id', session('sms_account_id'))
                ->where('provision_type', 'like', '%sms%')
                ->sum('current_usage');

            $info['Balance'] = intval($balance);
        }

        return $info;
    }

    public static function ssh($server, $user, $pass, $cmd, $port = 22)
    {
        try {
            $ssh = new phpseclib\Net\SSH2($server, $port);
            if (! $ssh->login($user, $pass)) {
                return 'SSH connection failed';
            }
            $ssh->setTimeout(0);
            $result = $ssh->exec($cmd);

            return $result;
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();

            return 'error - '.$error;
        }
    }

    /// CORE
    public static function reorder_menu($location, $parent_id = 0, $count = 0)
    {
        if ($location == 'pbx_menu') {
            if (! $parent_id) {
                $parent_id = null;
            }
            $menus = \DB::connection('pbx')->table('v_menu_items')->where('menu_item_parent_uuid', $parent_id)->orderby('menu_item_order')->get();

            foreach ($menus as $sort => $menu) {
                $count++;
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $menu->menu_item_uuid)->update(['menu_item_order' => $count]);
                $sub_items = \DB::connection('pbx')->table('v_menu_items')->where('menu_item_parent_uuid', $menu->menu_item_uuid)->count();
                if ($sub_items) {
                    $count = self::reorder_menu($location, $menu->menu_item_uuid, $count);
                }
            }
        } else {
            $menus = \DB::connection('default')->table('erp_menu')->where('location', $location)->where('parent_id', $parent_id)->orderby('sort_order')->get();

            foreach ($menus as $sort => $menu) {
                $count++;
                \DB::connection('default')->table('erp_menu')->where('location', $location)->where('id', $menu->id)->update(['sort_order' => $count]);
                $sub_items = \DB::connection('default')->table('erp_menu')->where('parent_id', $menu->id)->count();
                if ($sub_items) {
                    $count = self::reorder_menu($location, $menu->id, $count);
                }
            }
        }

        return $count;
    }

    public static function reorder_events_menu($parent_id = 0, $count = 0, $module_id = 0)
    {
        if ($module_id) {
            $module_ids = [$module_id];
        } else {
            $module_ids = \DB::connection('default')->table('erp_menu')->where('location', 'grid_menu')->pluck('render_module_id')->filter()->unique()->toArray();
        }
        foreach ($module_ids as $module_id) {
            $menus = \DB::connection('default')->table('erp_menu')->where('render_module_id', $module_id)->where('parent_id', $parent_id)->orderby('sort_order')->get();

            foreach ($menus as $sort => $menu) {
                $count++;
                \DB::connection('default')->table('erp_menu')->where('id', $menu->id)->update(['sort_order' => $count]);
                $sub_items = \DB::connection('default')->table('erp_menu')->where('parent_id', $menu->id)->count();
                if ($sub_items) {
                    $count = self::reorder_events_menu($menu->id, $count);
                }
            }
        }

        return $count;
    }

    public static function menu_merge_session($menu)
    {
        $formatted_menu = [];

        foreach ($menu as $menu_item) {
            if (! empty($menu_item->url) && is_string($menu_item->url)) {
                $menu_item->url = str_replace('{{$account_id}}', session('account_id'), $menu_item->url);
                $menu_item->url = str_replace('{{$role_id}}', session('role_id'), $menu_item->url);
            }
            if (! empty($menu_item->items) && count($menu_item->items) > 0) {
                $menu_item->items = self::menu_merge_session($menu_item->items, $app_id, $module_id, $menu_id);
            }
            $formatted_menu[] = $menu_item;
        }

        return $formatted_menu;
    }

    public static function menu_add_header_key($menu, $header_key)
    {
        $formatted_menu = [];
        if (! empty($menu) && is_array($menu) && count($menu) > 0) {
            foreach ($menu as $menu_item) {
                $menu_item->header_key = $header_key;

                if (! empty($menu_item->items) && count($menu_item->items) > 0) {
                    $menu_item->items = self::menu_add_header_key($menu_item->items, $header_key);
                }
                $formatted_menu[] = $menu_item;
            }
        }

        return $formatted_menu;
    }

    public static function menu_merge_params($menu, $app_id, $module_id, $menu_id, $connection = false)
    {
        $top_level_menu_id = get_toplevel_menu_id($menu_id);

        $formatted_menu = [];
        if (! empty($menu) && is_array($menu) && count($menu) > 0) {
            foreach ($menu as $menu_item) {
                if (empty($menu_item->cssClass)) {
                    $menu_item->cssClass = '';
                }
                if ($menu_item->menu_id == $top_level_menu_id) {
                    $menu_item->cssClass .= ' active-item';
                }
                if ($menu_item->menu_id == 1439 && request()->segment(1) == 'calendar') {
                    $menu_item->cssClass .= ' active-item';
                }
                if ($menu_item->menu_id == 1439 && request()->segment(1) == 'workspace') {
                    $menu_item->cssClass .= ' active-item';
                }

                if (! empty($menu_item->url_params)) {
                    $menu_item->url .= $menu_item->url_params;
                }

                if (! empty($menu_item->url) && is_string($menu_item->url)) {
                    $menu_item->url = str_replace('{{$app_id}}', $app_id, $menu_item->url);
                    $menu_item->url = str_replace('{{$module_id}}', $module_id, $menu_item->url);
                    $menu_item->url = str_replace('{{$menu_id}}', $menu_id, $menu_item->url);
                    $account_id = session('account_id');

                    if ($account_id == 1 && is_main_instance() && $menu_item->module_id != 512 && $menu_item->module_id != 343 && $menu_item->module_id != 348) {
                        $account_id = 12;
                    }

                    $menu_item->url = str_replace('{{$account_id}}', $account_id, $menu_item->url);
                    if ($connection) {
                        $menu_item->url = str_replace('{{$connection}}', $connection, $menu_item->url);
                    }
                }
                if (! empty($menu_item->items) && count($menu_item->items) > 0) {
                    $menu_item->items = self::menu_merge_params($menu_item->items, $app_id, $module_id, $menu_id, $connection);
                }
                $formatted_menu[] = $menu_item;
            }
        }

        return $formatted_menu;
    }

    public static function _sort($a, $b)
    {
        if (isset($b['sortlist'])) {
            return strnatcmp($a['sortlist'], $b['sortlist']);
        }
    }

    public static function _sortorder($a, $b)
    {
        if (isset($b['sort_order'])) {
            return strnatcmp($a['sort_order'], $b['sort_order']);
        }
    }

    public static function data_panels()
    {
        $panels = [];

        if (check_access('1,3,6,7')) {
            $menu_name = get_menu_url_from_table('isp_data_lte_vodacom_accounts');
            if (is_main_instance()) {
                $panels[] = (object) [
                    'type' => 'Admin',
                    'url' => url($menu_name),
                    'name' => 'LTE Vodacom Accounts',
                ];

                $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');
                $panels[] = (object) [
                    'type' => 'Admin',
                    'url' => url($menu_name),
                    'name' => 'LTE Telkom Accounts',
                ];

                $menu_name = get_menu_url_from_table('isp_data_lte_axxess_products');
                $panels[] = (object) [
                    'type' => 'Admin',
                    'url' => url($menu_name),
                    'name' => 'LTE Telkom Products',
                ];
            }

            $menu_name = get_menu_url_from_table('isp_data_ip_ranges');
            $ip_range_count = \DB::connection('default')->table('sub_services')->where('provision_type', 'like', 'ip_range%')->where('status', '!=', 'Deleted')->count();
            if ($ip_range_count) {
                $panels[] = (object) [
                    'type' => 'Admin',
                    'url' => url($menu_name),
                    'name' => 'IP Ranges',
                ];
            }

            $menu_name = get_menu_url_from_table('isp_data_fibre');
            $fibre_count = \DB::connection('default')->table('sub_services')->where('provision_type', 'fibre')->where('status', '!=', 'Deleted')->count();
            if ($fibre_count) {
                $panels[] = (object) [
                    'type' => 'Admin',
                    'url' => url($menu_name),
                    'name' => 'Fibre Accounts',
                ];
            }
            if (is_main_instance()) {
                $menu_name = get_menu_url_from_table('isp_data_products');
                $panels[] = (object) [
                    'type' => 'Admin',
                    'url' => url($menu_name),
                    'name' => 'Fibre Products',
                ];
            }
        }

        if (! $fibre_count && ! $ip_range_count && ! $lte_count) {
            $panels = [];
        }

        return $panels;
    }

    public static function admin_panels()
    {
        $panels = [];

        if (session('role_level') == 'Admin') {
            $panels[] = (object) [
                'type' => 'Admin',
                'url' => url('pbx_panel_login'),
                'name' => 'PBX Admin ',
                'company' => 'Admin',
            ];

            $panels[] = (object) [
                'type' => 'Admin',
                'url' => url('sms_panel'),
                'name' => 'SMS Admin',
                'company' => 'Admin',
            ];

            $panels[] = (object) [
                'type' => 'Admin',
                'url' => url('host_2'),
                'name' => 'Host 2 Server',
            ];
            $panels[] = (object) [
                'type' => 'Admin',
                'url' => url('host_3'),
                'name' => 'Host 3 Server',
            ];
        }

        return $panels;
    }

    public static function hosting_panels($account_id = false)
    {

        $filter_by_account = false;
        if ($account_id) {
            $filter_by_account = true;
        } else {
            $account_id = session('account_id');
        }
        $current_conn = \DB::getDefaultConnection();
        set_db_connection();

        $panels = [];

        if (is_superadmin() && session('role_level') == 'Admin') {
            $panels[] = [
                'type' => 'Admin',
                'url' => url('host_1'),
                'name' => 'Host 1 Server',
                'text' => 'Host 1 Server',
            ];
            $panels[] = [
                'type' => 'Admin',
                'url' => url('host_2'),
                'name' => 'Host 2 Server',
                'text' => 'Host 2 Server',
            ];
        }
        /*
           $hosting_url = get_menu_url_from_table('isp_host_websites');

           $panels[] = (object) [
               'type' => 'Admin',
               'url' => url($hosting_url),
               'name' => 'Hosting Accounts',
           ];

           $domains_url = get_menu_url_from_table('sub_domains_reconcile');
           $panels[] = (object) [
               'type' => 'Admin',
               'url' => url($domains_url),
               'name' => 'Domains Reconciliation',
           ];

           $zacr_url = get_menu_url_from_table('isp_host_zacr');
           $panels[] = (object) [
               'type' => 'Admin',
               'url' => url($zacr_url),
               'name' => 'ZACR Poll',
           ];

           $tld_pricing_url = get_menu_url_from_table('isp_hosting_tlds');
           $panels[] = (object) [
               'type' => 'Admin',
               'url' => url($tld_pricing_url),
               'name' => 'TLD Pricing',
           ];
           */

        $hosting_logins_query = \DB::table('sub_services as s')
            ->select('s.account_id', 's.detail', 'c.company', 'h.id as domain_id')
            ->join('crm_accounts as c', 'c.id', '=', 's.account_id')
            ->join('isp_host_websites as h', 'h.domain', '=', 's.detail')
            ->where('s.provision_type', 'hosting')
            ->where('s.status', 'Enabled');
        if ($filter_by_account) {

            $hosting_logins_query->where('c.id', $account_id);
        } elseif (session('role_level') == 'Admin') {

        } elseif (session('role_level') == 'Partner') {

            $hosting_logins_query->where('c.partner_id', $account_id);
        } elseif (session('role_level') == 'Customer') {

            $hosting_logins_query->where('c.id', $account_id);
        } else {
            $hosting_logins_query->whereRaw('1=0');
        }

        $hosting_logins = $hosting_logins_query->orderby('s.detail')->get();
        foreach ($hosting_logins as $hosting_login) {
            $panels[] = [
                'type' => $hosting_login->company.' Hosting',
                'url' => url('hosting_login/'.$hosting_login->account_id.'/'.$hosting_login->domain_id),
                'name' => $hosting_login->detail,
                'text' => $hosting_login->detail,
            ];
        }

        $sitebuilder_panels_query = \DB::table('sub_services as s')
            ->select('s.account_id', 's.detail', 'c.company', 'h.id as domain_id')
            ->join('crm_accounts as c', 'c.id', '=', 's.account_id')
            ->join('isp_host_websites as h', 'h.domain', '=', 's.detail')
            ->where('s.provision_type', 'sitebuilder')
            ->where('s.status', 'Enabled');

        if ($filter_by_account) {
            $sitebuilder_panels_query->where('c.id', $account_id);
        } elseif (session('role_level') == 'Admin') {

        } elseif (session('role_level') == 'Partner') {
            $sitebuilder_panels_query->where('c.partner_id', $account_id);
        } elseif (session('role_level') == 'Customer') {
            $sitebuilder_panels_query->where('c.id', $account_id);
        } else {
            $sitebuilder_panels_query->whereRaw('1=0');
        }
        $sitebuilder_panels = $sitebuilder_panels_query
            ->orderby('s.detail')->get();
        foreach ($sitebuilder_panels as $sitebuilder_panel) {
            $panels[] = [
                'type' => $sitebuilder_panel->company.' Sitebuilder',
                'url' => url('sitebuilder_panel/'.$sitebuilder_panel->account_id.'/'.$sitebuilder_panel->domain_id),
                'name' => $sitebuilder_panel->company.' - '.$sitebuilder_panel->detail,
                'text' => $sitebuilder_panel->company.' - '.$sitebuilder_panel->detail,
            ];
        }
        if (! is_dev()) {
            return [];

        }

        if (is_dev()) {
            foreach ($panels as $i => $panel) {
                $panels[$i]['id'] = 'hostingpanel'.$i;
                $panels[$i]['value'] = $panel['name'];
            }
            $formatted_panels = [];
            $panel_groups = collect($panels)->pluck('type')->unique()->toArray();
            foreach ($panel_groups as $i => $panel_group) {
                $formatted_panels[] = [
                    'id' => 'hostingpanelgroup'.$i,
                    'name' => $panel_group,
                    'text' => $panel_group,
                    'items' => array_values(collect($panels)->where('type', $panel_group)->toArray()),
                ];
            }
            $panels = $formatted_panels;

        }

        set_db_connection($current_conn);
        if (empty($panels) || count($panels) == 0) {
            return [];
        }

        return $panels;
    }

    public static function gridViews($menu_id, $module_id, $grid_id, $view_id = false)
    {

        $grid_views = [];
        $json = [];
        $reports_json = [];
        $cards_json = [];
        $tracking_json = [];
        $kanban_json = [];
        $layouts_json = [];
        $mod = app('erp_config')['modules']->where('id', $module_id)->first();
        $grid_views = \DB::connection('default')->table('erp_grid_views')
            ->where('module_id', $module_id)
            ->where('is_deleted', 0)
            ->orderby('global_default', 'desc')
            ->orderby('sort_order')->get();

        $current_layout_name = $mod->name;
        if (! empty($view_id)) {
            $current_layout = $grid_views->where('id', $view_id)->first();
        }

        $grid_id = str_replace('grid_', '', $grid_id);
        $grid_id = str_replace('detail', '', $grid_id);

        $menu_name = app('erp_config')['menus']->where('id', $menu_id)->pluck('menu_name')->first();
        if (empty($menu_name)) {
            $menu_name = app('erp_config')['modules']->where('id', $module_id)->pluck('name')->first();
        }

        if (count($grid_views) > 0) {
            foreach ($grid_views as $view) {

                $name = $view->name;
                if ($view->global_default) {
                    $name .= ' (D)';
                }
                if ($view->track_layout) {
                    $name .= ' (P)';
                }

                $class = '';

                if ($view_id == $view->id) {
                    $class = 'layout_active k-button';
                } else {
                    $class = 'k-button';
                }

                if ($view->kanban_default) {
                    $layout = (object) ['show_on_dashboard' => $view->show_on_dashboard, 'track_layout' => $view->track_layout, 'layout_type' => $view->layout_type, 'text' => $name, 'id' => 'layoutsbtnload'.$grid_id.'_'.$view->id, 'cssClass' => $class.' layoutitem'.$grid_id, 'is_kanban' => 1, 'is_group' => 0, 'view_id' => $view->id];
                } elseif ($view->layout_type == 'Report') {
                    $layout = (object) ['show_on_dashboard' => $view->show_on_dashboard, 'track_layout' => $view->track_layout, 'layout_type' => $view->layout_type, 'text' => $name, 'id' => 'layoutsbtnload'.$grid_id.'_'.$view->id, 'cssClass' => $class.' reportitem'.$grid_id, 'is_kanban' => 0, 'is_group' => 0, 'view_id' => $view->id];
                } else {
                    $layout = (object) ['show_on_dashboard' => $view->show_on_dashboard, 'track_layout' => $view->track_layout, 'layout_type' => $view->layout_type, 'text' => $name, 'id' => 'layoutsbtnload'.$grid_id.'_'.$view->id, 'cssClass' => $class.' layoutitem'.$grid_id, 'is_kanban' => 0, 'is_group' => 0, 'view_id' => $view->id];
                }
                //if($view->track_layout){
                //    $tracking_json[] = $layout;
                // }
                if ($view->kanban_default) {
                    $kanban_json[] = $layout;
                } elseif ($view->layout_type == 'Report') {
                    $reports_json[] = $layout;
                } elseif ($view->show_card) {
                    $cards_json[] = $layout;
                } else {
                    $layouts_json[] = $layout;
                }

            }
        }

        //if(is_dev()){
        $json = [];

        if (count($layouts_json) > 0) {
            $items = $layouts_json;
            $json[] = (object) ['text' => 'Layouts'.' ('.count($items).')', 'id' => 'layout_items'.$grid_id, 'cssClass' => 'k-button  layout-header', 'items' => $items, 'is_group' => 1];
        } else {
            $json[] = (object) ['text' => 'Layouts'.' (0)', 'id' => 'layout_items'.$grid_id, 'cssClass' => 'k-button  layout-header', 'items' => [], 'is_group' => 1, 'enabled' => false];
        }
        /*
        if( count($tracking_json) > 0){
            $items = $tracking_json;
            $json[] = (object) ['text'=> 'Processes'.' ('.count($items).')', 'id'=> 'layout_items'.$grid_id,'cssClass' => 'k-button layout-header layoutitem'.$grid_id, 'items' => $items, 'is_group' => 1 ];

        }else{
            $json[] = (object) ['text'=> 'Processes'.' (0)', 'id'=> 'layout_items'.$grid_id,'cssClass' => 'k-button layout-header layoutitem'.$grid_id, 'items' => [], 'is_group' => 1, 'enabled'=>false];
        }
        */
        if (count($kanban_json) > 0) {
            $items = $kanban_json;
            $json[] = (object) ['text' => 'Kanban'.' ('.count($items).')', 'id' => 'layout_items'.$grid_id, 'cssClass' => 'k-button layout-header', 'items' => $items, 'is_group' => 1];

        } else {
            //   $json[] = (object) ['text'=> 'Kanban'.' (0)', 'id'=> 'layout_items'.$grid_id,'cssClass' => 'k-button layout-header layoutitem'.$grid_id, 'items' => [], 'is_group' => 1, 'enabled'=>false];
        }

        if (count($cards_json) > 0) {
            $items = $cards_json;
            foreach ($items as $i => $item) {
                $items[$i]->cssClass .= ' is_card';
            }

            $json[] = (object) ['text' => 'Cards'.' ('.count($items).')', 'id' => 'card_items'.$grid_id, 'cssClass' => 'k-button layout-header is_card', 'items' => $items, 'is_group' => 1];

        } else {
            //    $json[] = (object) ['text'=> 'Cards'.' (0)', 'id'=> 'card_items'.$grid_id,'cssClass' => 'k-button layout-header is_card layoutitem'.$grid_id, 'items' => [], 'is_group' => 1, 'enabled'=>false];
        }

        if (count($reports_json) > 0) {
            $items = $reports_json;
            $json[] = (object) ['text' => 'Reports'.' ('.count($items).')', 'id' => 'report_items'.$grid_id, 'cssClass' => 'k-button layout-header', 'items' => $items, 'is_group' => 1];
        } else {
            $json[] = (object) ['text' => 'Reports'.' (0)', 'id' => 'report_items'.$grid_id, 'cssClass' => 'k-button layout-header', 'items' => [], 'is_group' => 1, 'enabled' => false];
        }
        //  }

        if (count($json) == 0) {
            return '';
        }
        $result = $json;

        //$result = [(object) ['text'=> $current_layout->name,'show_on_dashboard'=>$current_layout->show_on_dashboard,'track_layout'=>$current_layout->track_layout,'layout_type'=>$current_layout->layout_type, 'id'=> 'layout_header'.$grid_id,'cssClass' => 'k-button layout-current layoutitem'.$grid_id, 'items' => $json, 'is_group' => 1 ]];
        return $result;
    }

    public static function getSidebarReports($module_id)
    {
        $json = [];

        $reports = \DB::connection('default')->table('erp_reports')->where('module_id', $module_id)->where('sql_query', '>', '')->orderBy('sort_order')->get();
        $uncategorized = [];
        if (count($reports) > 0) {
            foreach ($reports as $report) {
                $name = $report->name;
                if ($report->default) {
                    $name .= ' (Default)';
                }
                $url = url('/flexmonster/'.$report->id);
                $report_link = [
                    'id' => 'report'.$report->id,
                    'report_url' => $url,
                    'text' => $name,
                    'report_id' => $report->id,
                ];
                $json[] = (object) $report_link;
            }
        } else {
            $report_access = get_menu_access_from_module(488);
            /*
            if($report_access['is_add']){
                $report_link = [
                    'id' => 'newreport',
                    'report_url' => 'create_report',
                    'text' => 'Create Report',
                    'report_id' => '',
                ];
                $json[] = (object) $report_link;
            }
            */
        }

        return $json;
    }

    public static function getLinkedModules($module_data, $row = false)
    {

        $has_linked_modules = false;
        $has_linked_module_access = false;
        $links = [];
        if (in_array(session('role_level'), ['Admin', 'Superadmin'])) {
            foreach ($module_data['module_fields'] as $field) {
                if ($field['field_type'] == 'select_module') {
                    $linked_module = app('erp_config')['modules']->where('db_table', $field['opt_db_table'])->first();

                    $is_detail_module = app('erp_config')['modules']->where('detail_module_id', $module_data['module_id'])->where('db_table', $field['opt_db_table'])->count();
                    if ($is_detail_module) {
                        continue;
                    }
                    $access = get_menu_access_from_module($linked_module->id);
                    if ($access['is_view']) {
                        $url = get_menu_url($linked_module->id);
                        if ($row && $row->{$field['field']}) {
                            $url .= '?'.$linked_module->db_key.'='.$row->{$field['field']};
                        }

                        $menu_name = app('erp_config')['menus']->where('module_id', $linked_module->id)->pluck('menu_name')->first();
                        $links[] = ['url' => $url, 'text' => $menu_name, 'data_target' => 'view_modal'];
                    }
                }
            }
        }

        return $links;
    }

    public static function getSidebarForms($module_id)
    {
        $json = [];

        if (is_superadmin()) {
            $forms = \DB::connection('default')->table('erp_forms')->select('erp_forms.*', 'erp_user_roles.name')
                ->join('erp_user_roles', 'erp_forms.role_id', '=', 'erp_user_roles.id')
                ->where('erp_forms.module_id', $module_id)->orderBy('erp_user_roles.sort_order')->get();

            foreach ($forms as $form) {
                $name = $form->name.' (';
                if ($form->is_view) {
                    $name .= 'V';
                }
                if ($form->is_add) {
                    $name .= 'A';
                }
                if ($form->is_edit) {
                    $name .= 'E';
                }
                if ($form->is_delete) {
                    $name .= 'D';
                }
                $name .= ')';
                $form_link = [
                    'id' => 'form'.$form->id,
                    'text' => $name,
                    'url' => '#',
                    'builder_id' => $form->id,
                    'role_id' => $form->role_id,
                    'cssClass' => 'form_builder_btn',

                ];
                $json[] = (object) $form_link;
            }
        }

        return $json;
    }

    public static function encode($arr)
    {
        $str = json_encode($arr);
        $enc = base64_encode($str);
        $enc = strtr($enc, 'poligamI123456', '123456poligamI');

        return $enc;
    }

    public static function decode($str)
    {
        $dec = strtr($str, '123456poligamI', 'poligamI123456');
        $dec = base64_decode($dec);
        $obj = json_decode($dec, true);

        return $obj;
    }

    public static function blend($str, $data)
    {
        $src = $rep = [];

        foreach ($data as $k => $v) {
            $src[] = '{'.$k.'}';
            $rep[] = $v;
        }

        if (is_array($str)) {
            foreach ($str as $st) {
                $res[] = trim(str_ireplace($src, $rep, $st));
            }
        } else {
            $res = str_ireplace($src, $rep, $str);
        }

        return $res;
    }

    public static function alert($task, $message)
    {
        if ($task == 'error') {
            $alert = '
			<div class="alert alert-danger  fade in block-inner">
				<button data-dismiss="alert" class="close" type="button"> x </button>
			<i class="icon-cancel-circle"></i> '.$message.' </div>
			';
        } elseif ($task == 'success') {
            $alert = '
			<div class="alert alert-success fade in block-inner">
				<button data-dismiss="alert" class="close" type="button"> x </button>
			<i class="icon-checkmark-circle"></i> '.$message.' </div>
			';
        } elseif ($task == 'warning') {
            $alert = '
			<div class="alert alert-warning fade in block-inner">
				<button data-dismiss="alert" class="close" type="button"> x </button>
			<i class="icon-warning"></i> '.$message.' </div>
			';
        } else {
            $alert = '
			<div class="alert alert-info  fade in block-inner">
				<button data-dismiss="alert" class="close" type="button"> x </button>
			<i class="icon-info"></i> '.$message.' </div>
			';
        }

        return $alert;
    }

    public static function runRequestFunction($type, $module_id, $request)
    {
        $helpers = \DB::select('select function_name from erp_form_events where module_id = '.$module_id.' and type = "'.$type.'" and active = 1 order by sort_order');
        foreach ($helpers as $helper) {
            try {
                $function = $helper->function_name;
                if (! function_exists($function)) {
                    exception_email($type.' function missing: '.$helper->function_name);

                    return response()->json(['status' => 'error', 'message' => 'An error occurred']);
                }

                return $function($request);
            } catch (\Throwable $ex) {
                exception_log($ex);
                exception_email($ex, $type.' '.$helper->name);
            }
        }
    }

    public static function getHomePageLink()
    {
        $module_id = \DB::connection('default')->table('erp_user_roles')->where('id', session('role_id'))->pluck('default_module')->first();
        if (empty($module_id)) {
            $default_page = '/';
        } else {
            $default_page = get_menu_url($module_id);
        }

        return $default_page;
    }

    public static function setDBTables()
    {
        $tables = \DB::getDoctrineSchemaManager()->listTableNames();
        foreach ($tables as $table) {
            $exists = \DB::table('erp_instance_tables')->where('table_name', $table)->count();
            if (! $exists) {
                $module_ids = \DB::table('erp_cruds')->where('db_table', $table)->pluck('id')->toArray();
                $data = [
                    'module_ids' => implode(',', $module_ids),
                    'table_name' => $table,
                ];
                \DB::table('erp_instance_tables')->insert($data);
            } else {
                $module_ids = \DB::table('erp_cruds')->where('db_table', $table)->pluck('id')->toArray();
                $data = [
                    'module_ids' => implode(',', $module_ids),
                ];
                \DB::table('erp_instance_tables')->where('table_name', $table)->update($data);
            }
        }
        \DB::table('erp_instance_tables')->whereNotIn('table_name', $tables)->delete();
    }

    public static function getDashboardGrids($role_ids, $instance_ids)
    {

        if (empty($instance_ids) || count($instance_ids) == 0) {
            return [];
        }

        $layouts_url = get_menu_url_from_table('erp_grid_views');
        $charts = [];
        $instances = \DB::connection('system')->table('erp_instances')->whereIn('id', $instance_ids)->get();
        foreach ($instances as $instance) {
            $instance_role_charts = [];
            $conn = $instance->db_connection;
            $modules = \DB::connection($conn)->table('erp_cruds')->select('id', 'slug', 'name')->get();
            foreach ($role_ids as $role_id) {
                $role_charts = [];
                $layouts = \DB::connection($conn)->table('erp_grid_views')
                    ->select('id', 'module_id', 'name', 'chart_model', 'dashboard_row', 'dashboard_col', 'dashboard_sizex', 'dashboard_sizey', 'chart_role_id')
                    ->where('chart_role_id', $role_id)
                    ->where('is_deleted', 0)
                    ->where('show_on_dashboard', 1)
                    ->get();

                foreach ($layouts as $i => $layout) {
                    $slot_in_use = \DB::connection($conn)->table('erp_grid_views')
                        ->where('id', '!=', $layout->id)
                        ->where('dashboard_row', $layout->dashboard_row)->where('dashboard_col', $layout->dashboard_col)
                        ->where('chart_role_id', $role_id)->where('is_deleted', 0)->where('show_on_dashboard', 1)
                        ->count();

                    if ($slot_in_use) {
                        $duplicate_ids = \DB::connection($conn)->table('erp_grid_views')
                            ->where('id', '!=', $layout->id)
                            ->where('dashboard_row', $layout->dashboard_row)->where('dashboard_col', $layout->dashboard_col)
                            ->where('chart_role_id', $role_id)->where('is_deleted', 0)->where('show_on_dashboard', 1)
                            ->pluck('id')->toArray();

                        $max_row = \DB::connection($conn)->table('erp_grid_views')
                            ->select('id', 'module_id', 'name', 'chart_model', 'dashboard_row', 'dashboard_col', 'dashboard_sizex', 'dashboard_sizey')
                            ->where('chart_role_id', $role_id)
                            ->where('is_deleted', 0)
                            ->where('show_on_dashboard', 1)->max('dashboard_row');
                        \DB::connection('default')->table('erp_grid_views')->whereIn('id', $duplicate_ids)->update(['dashboard_row' => $max_row + 1]);
                    }
                }

                $layouts = \DB::connection($conn)->table('erp_grid_views')
                    ->select('id', 'module_id', 'name', 'chart_model', 'dashboard_row', 'dashboard_col', 'dashboard_sizex', 'dashboard_sizey', 'chart_role_id')
                    ->where('chart_role_id', $role_id)
                    ->where('is_deleted', 0)
                    ->where('show_on_dashboard', 1)
                    ->get();

                foreach ($layouts as $layout) {
                    $slug = $modules->where('id', $layout->module_id)->pluck('slug')->first();
                    $name = $modules->where('id', $layout->module_id)->pluck('name')->first();
                    $role_charts[] = (object) [
                        'slug' => $slug,
                        'cssClass' => 'sidebar_chart',
                        'module_id' => $layout->module_id,
                        'is_chart' => ($layout->chart_model > '') ? 1 : 0,
                        'layout_url' => $slug.'?layout_id='.$layout->id,
                        'edit_url' => $layouts_url.'/edit/'.$layout->id,
                        'text' => $name.': '.$layout->name,
                        'value' => $layout->id,
                        'id' => $layout->id,
                        'row' => $layout->dashboard_row,
                        'col' => $layout->dashboard_col,
                        'sizex' => $layout->dashboard_sizex,
                        'sizey' => $layout->dashboard_sizey,
                        'role_id' => $layout->chart_role_id,
                        'instance_id' => $instance->id,
                        'cidb' => $instance->db_connection,
                    ];
                }
                $instance_role_charts[$role_id] = $role_charts;
            }

            $charts[$instance->id] = $instance_role_charts;
        }

        return $charts;
    }

    public static function getModuleCards($module_id, $grid_footer = 0, $role_id = false)
    {
        $module_cards = [];
        if (session('role_level') != 'Admin') {
            return $module_cards;
        }

        // workspace role user filter
        $user_id = 0;

        if ($module_id == 2018) {
            $role_id = session('role_id');

            $cards = \DB::connection('default')->table('crm_module_cards')->where('grid_footer', $grid_footer)->where('role_id', $role_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
        } else {
            $cards = \DB::connection('default')->table('crm_module_cards')->where('grid_footer', $grid_footer)->where('module_id', $module_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
        }

        if ($cards->count() > 0) {

            foreach ($cards as $card) {
                $session_account_id = session('account_id');
                if ($session_account_id == 1) {
                    $session_account_id = 12;
                }
                $card->sql_query = str_replace("'session_account_id'", $session_account_id, $card->sql_query);
                if (! empty($card->sql_query) && $card->query_all_companies && $card->sql_connection == 'default') {
                    $instances = \DB::connection('system')->table('erp_instances')->whereIn('id', [1, 2, 11])->get();
                    $instances_result = [];
                    foreach ($instances as $instance) {
                        try {
                            $result_arr = [];

                            $rows = \DB::connection($instance->db_connection)->select($card->sql_query);
                            foreach ($rows as $row) {
                                $line = 0;
                                foreach ($row as $k => $v) {
                                    if (is_numeric($v) && str_contains($v, '.')) {
                                        $v = currency($v);
                                    }

                                    if ($k == 'result' && isset($row->name)) {
                                        $line = $row->name.': '.$v;
                                    } elseif ($k == 'result') {
                                        $line = $v;
                                    }
                                }

                                if (empty($line)) {
                                    $line = 0;
                                }
                                if (! empty($card->target)) {
                                    $line .= ' / '.$card->target;
                                }
                                $result_arr[] = $line;
                            }
                            $result = implode(' ', $result_arr);
                            if (empty($result)) {
                                $result = 0;

                                if (! empty($card->target)) {
                                    $result .= ' / '.$card->target;
                                }
                            }
                            $instances_result[] = str_replace("'", '', $instance->name.' '.$result);

                        } catch (\Throwable $ex) {

                        }
                    }

                    $result = implode(' ', $instances_result);

                    $data = (array) $card;
                    if (empty($data['icon'])) {
                        $data['icon'] = 'fas fa-info-circle';
                    }
                    $data['result'] = $result;
                    $module_cards[] = (object) $data;
                } elseif (! empty($card->sql_query)) {
                    try {
                        $result_arr = [];

                        $rows = \DB::connection($card->sql_connection)->select($card->sql_query);
                        foreach ($rows as $row) {
                            $line = 0;
                            foreach ($row as $k => $v) {
                                if (is_numeric($v) && str_contains($v, '.')) {
                                    $v = currency($v);
                                }
                                if ($k == 'result' && isset($row->name)) {
                                    $line = $row->name.': '.$v;
                                } elseif ($k == 'result') {
                                    $line = $v;
                                }
                            }

                            if (empty($line)) {
                                $line = 0;
                            }
                            if (! empty($card->target)) {
                                $line .= ' / '.$card->target;
                            }
                            $result_arr[] = str_replace("'", '', $line);
                        }
                        $result = implode(' ', $result_arr);
                        if (empty($result)) {
                            $result = 0;

                            if (! empty($card->target)) {
                                $result .= ' / '.$card->target;
                            }
                        }

                        $data = (array) $card;
                        if (empty($data['icon'])) {
                            $data['icon'] = 'fas fa-info-circle';
                        }
                        $data['result'] = $result;
                        $module_cards[] = (object) $data;
                    } catch (\Throwable $ex) {

                    }
                } elseif (! empty($card->function_name)) {
                    $fn = $card->function_name;

                    if (! empty($fn) && function_exists($fn)) {
                        $data = (array) $card;
                        if (empty($data['icon'])) {
                            $data['icon'] = 'fas fa-info-circle';
                        }
                        $data['result'] = $fn();

                        if (empty($data['result'])) {
                            $data['result'] = 0;
                        }

                        if (! empty($card->target)) {
                            $data['result'] .= ' / '.$card->target;
                        }
                        $module_cards[] = (object) $data;
                    }
                }
            }
        }

        return $module_cards;
    }

    public static function getLayoutCards($module_id)
    {
        $module_cards = [];

        return [];
        if (session('role_level') == 'Admin') {
            $cards = \DB::connection('default')->table('erp_grid_views')->where('module_id', $module_id)->where('is_deleted', 0)->where('show_card', 1)->orderBy('sort_order')->get();

            if ($cards->count() > 0) {

                foreach ($cards as $card) {

                    $data = (array) $card;
                    $data['title'] = $card->name;

                    $data['color'] = $card->card_color;
                    $data['icon'] = $card->card_icon;

                    if (empty($data['icon'])) {
                        $data['icon'] = 'fas fa-info-circle';
                    }
                    if (empty($data['color'])) {
                        $data['color'] = 'primary';
                    }
                    $result = workboard_layout_row_count($card->id);
                    if (empty($result)) {
                        $result = 0;
                    }
                    $data['result'] = $result;

                    $module_cards[] = (object) $data;

                }
            }
        }

        return $module_cards;
    }

    public static function getSidebarCards()
    {

        // AGGREGATES
        $sidebar_cards = [];
        $module_ids = \DB::connection('default')->table('crm_aggregate_cards')->orderBy('sort_order')->pluck('module_id')->unique()->filter()->toArray();
        foreach ($module_ids as $module_id) {
            $module_name = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->pluck('Name')->first();
            $module_cards = [];
            $aggregate_cards = \DB::connection('default')->table('crm_aggregate_cards')->where('module_id', $module_id)->orderBy('sort_order')->get();

            $module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
            $fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $module_id)->get();
            foreach ($aggregate_cards as $aggregate_card) {

                $sql_where = (! empty($aggregate_card->sql_where)) ? $aggregate_card->sql_where : '1=1';
                $has_is_deleted = $fields->where('field', 'is_deleted')->count();
                $has_status = $fields->where('field', 'status')->count();
                if ($has_is_deleted) {
                    $sql_where .= ' and is_deleted=0';
                } elseif ($has_status) {
                    $sql_where .= ' and status!="Deleted"';
                }

                $totals = [];
                if ($aggregate_card->type == 'Count') {
                    $totals = \DB::connection($module->connection)->table($module->db_table)->whereRaw($sql_where)->selectRaw($aggregate_card->group_by_field.' as cardtitle, count(*) as total')->groupBy($aggregate_card->group_by_field)->limit(6)->get();
                }
                if ($aggregate_card->type == 'Sum') {
                    $sum_fields = explode(',', $aggregate_card->card_fields);
                    $sum_fields_selects = [];
                    foreach ($sum_fields as $f) {
                        $sum_fields_selects[] = 'sum('.$f.') as '.$f;
                    }
                    $sum_fields_query = implode(',', $sum_fields_selects);
                    $totals = \DB::connection($module->connection)->table($module->db_table)->whereRaw($sql_where)->selectRaw($aggregate_card->group_by_field.' as cardtitle, '.$sum_fields_query)->groupBy($aggregate_card->group_by_field)->limit(6)->get();
                }
                if ($aggregate_card->type == 'RowData') {

                    $sum_fields = explode(',', $aggregate_card->card_fields);
                    $sum_fields_query = implode(',', $sum_fields);
                    $totals = \DB::connection($module->connection)->table($module->db_table)->whereRaw($sql_where)->selectRaw($aggregate_card->group_by_field.' as cardtitle, '.$sum_fields_query)->groupBy($aggregate_card->group_by_field)->limit(6)->get();
                }

                $is_foreign_key = \DB::connection('default')->table('erp_module_fields')->where('field_type', 'select_module')->where('field', $aggregate_card->group_by_field)->where('module_id', $module_id)->get()->first();
                if (count($totals) == 0) {
                    continue 2;
                }

                foreach ($totals as $total) {
                    $data = (array) $card;
                    $data['title'] = $total->cardtitle;

                    if ($is_foreign_key) {
                        $data['title'] = get_foreign_field_display_val($module_id, $aggregate_card->group_by_field, $total->cardtitle);

                    }
                    $data['is_aggregate'] = 1;

                    if (empty($data['icon'])) {
                        $data['icon'] = 'fas fa-info-circle';
                    }
                    if (empty($data['color'])) {
                        $data['color'] = 'primary';
                    }
                    if (isset($total->total)) {
                        $data['result'] = $total->total;
                    }
                    $data['details'] = [];
                    if ($aggregate_card->type != 'Count') {
                        foreach ($sum_fields as $f) {
                            $label = $fields->where('field', $f)->pluck('label')->first();
                            $data['details'][$label] = $total->{$f};
                        }
                    }
                    $module_cards[] = (object) $data;
                }
            }
            $sidebar_cards[$module_name] = $module_cards;
        }

        return $sidebar_cards;
    }

    public static function getDashboardCards()
    {
        $module_cards = [];

        if (! session('role_level') == 'Admin') {
            return $module_cards;
        }
        // PROCESSES

        $process_cards = \DB::connection('default')->table('erp_grid_views')->where('is_deleted', 0)->where('show_card', 1)->orderBy('sort_order')->get();

        if ($process_cards->count() > 0) {
            foreach ($process_cards as $card) {
                $data = (array) $card;
                $data['title'] = $card->name;

                $data['color'] = $card->card_color;
                $data['icon'] = $card->card_icon;

                if (empty($data['icon'])) {
                    $data['icon'] = 'fas fa-info-circle';
                }
                if (empty($data['color'])) {
                    $data['color'] = 'primary';
                }
                $result = workboard_layout_row_count($card->id);
                if (empty($result)) {
                    $result = 0;
                }
                $data['result'] = $result;
                $data['details'] = [];
                //$data['details'] = ['Score'=> $result,'target'=>$card->target];
                $data['layout_url'] = get_menu_url_from_module_id($card->module_id).'?layout_id='.$card->id;
                $module_cards[] = (object) $data;
            }
        }

        return $module_cards;
    }

    public static function getAggregateCards($module_id, $dashboard = false)
    {
        $module_cards = [];

        if (! session('role_level') == 'Admin') {
            return $module_cards;
        }
        // PROCESSES
        if ($dashboard) {
            $process_cards = \DB::connection('default')->table('erp_grid_views')->where('is_deleted', 0)->where('show_card', 1)->orderBy('sort_order')->get();
        } else {
            $process_cards = \DB::connection('default')->table('erp_grid_views')->where('card_module_id', $module_id)->where('is_deleted', 0)->where('show_card', 1)->orderBy('sort_order')->get();
        }
        if ($process_cards->count() > 0) {
            foreach ($process_cards as $card) {
                $data = (array) $card;
                $data['title'] = $card->name;

                $data['color'] = $card->card_color;
                $data['icon'] = $card->card_icon;

                if (empty($data['icon'])) {
                    $data['icon'] = 'fas fa-info-circle';
                }
                if (empty($data['color'])) {
                    $data['color'] = 'primary';
                }
                $result = workboard_layout_row_count($card->id);
                if (empty($result)) {
                    $result = 0;
                }
                $data['result'] = $result;
                $data['details'] = [];
                //$data['details'] = ['Score'=> $result,'target'=>$card->target];
                $data['layout_url'] = get_menu_url_from_module_id($card->module_id).'?layout_id='.$card->id;
                $module_cards[] = (object) $data;
            }
        }

        /*
                // AGGREGATES
                $aggregate_cards = \DB::connection('default')->table('crm_aggregate_cards')->where('module_id',$module_id)->get();


                $module = \DB::connection('default')->table('erp_cruds')->where('id',$module_id)->get()->first();
                $fields = \DB::connection('default')->table('erp_module_fields')->where('module_id',$module_id)->get();
                foreach($aggregate_cards as $aggregate_card){

                    $sql_where = (!empty($aggregate_card->sql_where)) ? $aggregate_card->sql_where : "1=1";
                    $has_is_deleted = $fields->where('field','is_deleted')->count();
                    $has_status = $fields->where('field','status')->count();
                    if($has_is_deleted){
                        $sql_where .= ' and is_deleted=0';
                    }elseif($has_status){
                        $sql_where .= ' and status!="Deleted"';
                    }

                    $totals = [];
                    if($aggregate_card->type == 'Count'){
                        $totals = \DB::connection($module->connection)->table($module->db_table)->whereRaw($sql_where)->selectRaw($aggregate_card->group_by_field.' as cardtitle, count(*) as total')->groupBy($aggregate_card->group_by_field)->limit(6)->get();
                    }
                    if($aggregate_card->type == 'Sum'){
                        $sum_fields = explode(',',$aggregate_card->card_fields);
                        $sum_fields_selects = [];
                        foreach($sum_fields as $f){
                        $sum_fields_selects[] = 'sum('.$f.') as '.$f;
                        }
                        $sum_fields_query  = implode(',',$sum_fields_selects);
                        $totals = \DB::connection($module->connection)->table($module->db_table)->whereRaw($sql_where)->selectRaw($aggregate_card->group_by_field.' as cardtitle, '.$sum_fields_query)->groupBy($aggregate_card->group_by_field)->limit(6)->get();
                    }
                    if($aggregate_card->type == 'RowData'){

                        $sum_fields = explode(',',$aggregate_card->card_fields);
                        $sum_fields_query = implode(',',$sum_fields);
                        $totals = \DB::connection($module->connection)->table($module->db_table)->whereRaw($sql_where)->selectRaw($aggregate_card->group_by_field.' as cardtitle, '.$sum_fields_query)->groupBy($aggregate_card->group_by_field)->limit(6)->get();
                    }

                    $is_foreign_key = \DB::connection('default')->table('erp_module_fields')->where('field_type','select_module')->where('field',$aggregate_card->group_by_field)->where('module_id',$module_id)->get()->first();
                    foreach($totals as $total){
                        $data = (array) $card;
                        $data['title'] = $total->cardtitle;

                        if($is_foreign_key){
                            $data['title'] = get_foreign_field_display_val($module_id, $aggregate_card->group_by_field,$total->cardtitle);

                        }
                        $data['is_aggregate'] = 1;


                        if(empty($data['icon'])){
                            $data['icon'] = 'fas fa-info-circle';
                        }
                        if(empty($data['color'])){
                            $data['color'] = 'primary';
                        }
                        if(isset($total->total)){
                            $data['result'] = $total->total;
                        }
                        $data['details'] = [];
                        if($aggregate_card->type != 'Count'){
                            foreach($sum_fields as $f){
                                $label = $fields->where('field',$f)->pluck('label')->first();
                                $data['details'][$label] = $total->{$f};
                            }
                        }
                        $module_cards[] = (object) $data;
                    }
                }
                */
        return $module_cards;
    }

    public static function getContentSidebar($module_id, $type, $view_id = false)
    {
        $mod = app('erp_config')['modules']->where('id', $module_id)->first();
        $grid_views_query = \DB::connection('default')->table('erp_grid_views')
            ->where('module_id', $module_id)
            ->where('is_deleted', 0);
        if ($type == 'Chart') {
            $grid_views_query->where('chart_model', '>', '');
        } else {
            $grid_views_query->where(function ($grid_views_query) {
                $grid_views_query->whereNull('chart_model');
                $grid_views_query->orWhere('chart_model', '');
            });

            if ($type == 'dashboard') {
                $grid_views_query->where('show_on_dashboard', 1);
            } else {
                $grid_views_query->where('layout_type', $type);
            }
        }

        $grid_views = $grid_views_query->orderby('global_default', 'desc')
            ->orderby('sort_order')
            ->get();

        $json = [];

        foreach ($grid_views as $view) {

            $name = $view->name;
            if ($view->global_default) {
                $name .= ' *';
            }
            if ($view->track_layout || $view->show_on_dashboard) {
                $name .= ' (T)';
            }

            if ($view->custom) {
                $name .= ' (C)';
            }

            $class = 'grid_layout';
            if ($view_id == $view->id) {
                $class .= ' layout_active';
            }

            $json[] = (object) [
                'htmlAttributes' => (object) [
                    'data-show_on_dashboard' => $view->show_on_dashboard,
                    'data-track_layout' => ($view->track_layout) ? 1 : 0,
                    'data-auto_form' => $view->auto_form,
                    'data-view_id' => $view->id,
                    'data-layout_type' => $view->layout_type,
                    'data-has_chart' => ($view->chart_model > '') ? 1 : 0,
                ],
                'text' => $name,
                'value' => $view->id,
                'id' => $view->id,
                'cssClass' => $class,
            ];
        }

        return $json;
    }

    public static function getWorkboardReports($project_id)
    {
        $modules = app('erp_config')['modules']->where('id', $module_id)->first();
        $grid_views_query = \DB::connection('default')->table('erp_grid_views')
            ->where('project_id', $project_id)
            ->where('layout_type', 'Report')
            ->where('is_deleted', 0);

        $grid_views = $grid_views_query->orderby('module_id')
            ->orderby('global_default', 'desc')
            ->orderby('sort_order')
            ->get();
        $json = [];

        foreach ($grid_views as $view) {
            $module = app('erp_config')['modules']->where('id', $view->module_id)->first();
            $name = $view->name;
            if ($view->global_default) {
                //   $name .= ' (D)';
            }
            if ($view->track_layout) {
                //   $name .= ' (P)';
            }

            $class = 'workboardreports_context';

            $json[] = (object) [
                'htmlAttributes' => (object) [
                    'data-attr-id' => $view->id,
                    'data-attr-layout-link' => $module->slug.'?layout_id='.$view->id,
                ],
                'module' => $module->name,
                'text' => $name,
                'value' => $view->id,
                'id' => $view->id,
                'cssClass' => $class,
                'url' => $module->slug.'?layout_id='.$view->id,
            ];
        }

        return $json;
    }

    public static function getGridForms($module_id)
    {
        $mod = app('erp_config')['modules']->where('id', $module_id)->first();
        $grid_views_query = \DB::connection('default')->table('erp_grid_views')
            ->where('module_id', $module_id)
            ->where('is_deleted', 0)
            ->where('auto_form', 1);

        $grid_views = $grid_views_query->orderby('global_default', 'desc')
            ->orderby('sort_order')
            ->get();
        $json = [];

        $json[] = (object) [
            'htmlAttributes' => (object) [
            ],
            'text' => 'Save Current Layout as Form',
            'value' => 'save_layout_as_form',
            'id' => 'save_layout_as_form',
            'cssClass' => 'form_layout_new',
        ];

        foreach ($grid_views as $view) {

            $name = $view->name;
            if ($view->global_default) {
                $name .= ' (D)';
            }
            if ($view->track_layout) {
                $name .= ' (P)';
            }

            $class = 'form_layout';

            $json[] = (object) [
                'htmlAttributes' => (object) [
                    'data-show_on_dashboard' => $view->show_on_dashboard,
                    'data-track_layout' => $view->track_layout,
                    'data-auto_form' => $view->auto_form,
                    'data-view_id' => $view->id,
                    'data-layout_type' => $view->layout_type,
                ],
                'text' => $name,
                'value' => $view->id,
                'id' => $view->id,
                'cssClass' => $class,
            ];
        }

        return $json;
    }

    public static function services_accounts()
    {
        $panels = [];

        $pbx_domains = \DB::connection('pbx')->table('v_domains')->orderBy('domain_name')->get();
        $accounts_query = \DB::connection('default')->table('sub_services')
            ->select('crm_accounts.id', 'crm_accounts.company')
            ->join('crm_accounts', 'crm_accounts.id', '=', 'sub_services.account_id')
            ->where('sub_services.status', '!=', 'Deleted');

        if (session('role_level') == 'Partner') {
            $accounts_query->where('crm_accounts.partner_id', session('account_id'));
        }

        if (session('role_level') == 'Customer') {
            $accounts_query->where('crm_accounts.id', session('account_id'));
        }
        $accounts_query->groupBy('sub_services.account_id');
        $accounts_query->orderBy('crm_accounts.partner_id');
        $accounts_query->orderBy('crm_accounts.company');

        $accounts = $accounts_query->get();

        foreach ($accounts as $i => $account) {
            $accounts[$i]->domain_uuid = $pbx_domains->where('account_id', $account->id)->pluck('domain_uuid')->first();
            $accounts[$i]->domain_name = $pbx_domains->where('account_id', $account->id)->pluck('domain_name')->first();
        }
        if (session('role_level') == 'Admin' || session('role_level') == 'Partner') {

            $panels[] = (object) [
                'id' => 0,
                'domain_name' => null,
                'domain_uuid' => null,
                'name' => 'All Customers',
            ];
        }

        foreach ($accounts as $account) {

            $panels[] = (object) [

                'id' => $account->id,
                'domain_name' => $account->domain_name,
                'domain_uuid' => $account->domain_uuid,
                'name' => $account->company,
            ];
        }

        return $panels;
    }

    public static function set_service_account_session($account_id = 0)
    {

        if (session('role_level') == 'Customer') {
            $account_id = session('account_id');
        }
        if ($account_id == 0) {

            session()->forget('service_account_id');

            session()->forget('service_account_domain_uuid');

            session()->forget('service_account_domain_name');
        } else {

            session(['service_account_id' => $account_id]);
            $pbx_domain = \DB::connection('pbx')->table('v_domains')->select('domain_name', 'domain_uuid')->where('account_id', $account_id)->get()->first();
            if ($pbx_domain->domain_uuid) {
                session(['service_account_domain_uuid' => $pbx_domain->domain_uuid]);
            } else {
                session()->forget('service_account_domain_uuid');
            }
            if ($pbx_domain->domain_name) {
                session(['service_account_domain_name' => $pbx_domain->domain_name]);
            } else {
                session()->forget('service_account_domain_name');
            }
        }
    }
}
