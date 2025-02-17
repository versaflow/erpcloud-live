<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Redirect;

class DashboardController extends BaseController
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! session()->has('user_id') || empty(session('user_id')) ||
            ! session()->has('account_id') || empty(session('account_id')) ||
            ! session()->has('role_id') || empty(session('role_id'))) {
                \Auth::logout();
                \Session::flush();

                return Redirect::to('/');
            }

            return $next($request);
        });
        $this->middleware('globalviewdata');
    }

    public function index(Request $request)
    {

        if (session('instance')->id == 20) {
            return redirect()->to('/support');
        }

        if (session('role_level') == 'Admin') {
            return $this->aggridCharts($request);
        }

        //if( session('role_level') == 'Customer' || session('role_level') == 'Partner'){
        return $this->customerDashboard($request);
        // }

    }

    public function pinnned(Request $request)
    {
        if (session('instance')->id == 20) {
            return redirect()->to('/support');
        }
        if (session('role_level') != 'Admin' && session('role_level') != 'SuperAdmin') {
            return redirect()->to('dashboard');
        }
        //if(!is_main_instance()){
        //    return redirect()->to('/');
        //}
        $data = [];
        $data['menu_name'] = 'Pinned';
        $data['module_id'] = 500;
        $data['pinnned_tabs_url'] = get_menu_url_from_module_id(1986);

        $data['hide_content_sidebar'] = 1;

        $tabs = \DB::table('erp_favorites')->orderBy('sort_order')->get();
        $roles = \DB::table('erp_user_roles')->orderBy('sort_order')->get();
        $data['pinnned_tabs'] = [];

        $module_ids = [];
        foreach ($tabs as $tab) {
            if (! empty($tab->module_id)) {
                // if(in_array($tab->module_id,$module_ids)){
                //     continue;
                // }
                $e = \DB::table('erp_cruds')->where('id', $tab->module_id)->count();
                if (! $e) {
                    continue;
                }
                $name = \DB::table('erp_cruds')->where('id', $tab->module_id)->pluck('name')->first();
                $url = get_menu_url_from_module_id($tab->module_id);
                $url = url($url);
                //if(!empty($tab->layout_id)){
                //    $url .= '?layout_id='.$tab->layout_id;
                //}
                // if(is_superadmin() && $tab->module_id != 2018){
                //     $role = get_workspace_role_from_module_id($tab->module_id);
                //     if($role && isset($role->name))
                //     $name .= ' ('.ucfirst($role->name[0]).')';
                // }
                $data['pinnned_tabs'][] = (object) [
                    'title' => $name,
                    'url' => $url,
                ];
                $module_ids[] = $tab->module_id;
            } elseif (! empty($tab->link_url)) {
                $title = $tab->title;

                if ($tab->link_url == 'dashboard') {
                    $data['pinnned_tabs'][] = (object) [
                        'title' => $title,
                        'url' => url('/dashboard'),
                    ];
                } else {
                    if ($tab->link_url == 'https://helpdesk.telecloud.co.za/admin.php') {
                        try {
                            $conversations_count = sbdb_get_conversations_count();
                        } catch (\Throwable $ex) {
                            $conversations_count = 0;
                        }
                        //$title .= ' ('.$conversations_count.')';
                    }

                    $data['pinnned_tabs'][] = (object) [
                        'title' => $title,
                        'url' => url('/pinned_iframe/'.$tab->id),
                    ];
                }
            }
        }

        return view('__app.grids.grid_tabs', $data);
    }

    public function aggridCharts(Request $request)
    {

        if (! empty($request->tab_load)) {
            $request->request->remove('tab_load');
        }

        $data = [];
        $data['menu_name'] = 'Dashboard';
        $data['module_id'] = 500;

        $data['hide_content_sidebar'] = 1;

        $data['dashboard_role_datasource'] = [];

        if (is_superadmin()) {
            $roles = \DB::connection('default')->table('erp_user_roles')->select('id', 'name')->where('level', 'Admin')->orderBy('sort_order')->get();
        } else {
            $roles = \DB::connection('default')->table('erp_user_roles')->whereIn('id', session('role_ids'))->select('id', 'name')->where('level', 'Admin')->orderBy('sort_order')->get();
        }
        if (is_dev()) {
            $roles = \DB::connection('default')->table('erp_user_roles')->select('id', 'name')->where('level', 'Admin')->orderBy('sort_order')->get();
        }
        $role_ids = $roles->pluck('id')->toArray();
        // $access_instance_ids = get_admin_instance_access();

        $instance_ids = [session('instance')->id];
        // foreach($instance_ids as $i => $instance_id){
        //     if(!in_array($instance_id,$access_instance_ids)){
        //         unset($instance_ids[$i]);
        //    }
        //  }
        $instance_ids = array_values($instance_ids);
        $data['instance_ids'] = $instance_ids;
        $instances = \DB::connection('system')->table('erp_instances')->whereIn('id', $instance_ids)->get();
        $data['instances'] = $instances;
        $data['roles'] = $roles;
        $data['role_ids'] = $role_ids;
        $data['aggrid_charts'] = \Erp::getDashboardGrids($role_ids, $instance_ids);

        if (count($roles) > 1) {
            $data['dashboard_role_datasource'] = $roles;
            $data['dashboard_role_placeholder'] = $roles->where('id', session('role_id'))->pluck('name')->first();
            $data['dashboard_role_selected'] = $roles->where('id', session('role_id'))->pluck('id')->first();
        }
        if (count($instances) > 1) {
            $data['dashboard_instance_datasource'] = $instances;
            $data['dashboard_instance_placeholder'] = $instances->where('id', 1)->pluck('name')->first();
            $data['dashboard_instance_selected'] = 1;
        }
        $data['instances_placeholder'] = $data['instances']->where('id', 1)->pluck('name')->first();
        $data['layouts_url'] = get_menu_url_from_table('erp_grid_views');

        return view('__app.charts.dashboard', $data);
    }

    public function customerDashboard(Request $request)
    {
        $account_id = session('account_id');
        if (is_dev() && session('account_id') == 1) {
            $account_id = 12;
        }
        //$account_id = 305;
        $account = dbgetaccount($account_id);
        $data['account_id'] = $account_id;
        $data['account'] = $account;
        if (! empty($account->domain_uuid)) {
            $data['pbx_domain'] = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $account->domain_uuid)->get()->first();
        }

        $data['menu_name'] = 'Dashboard';
        $data['module_id'] = 500;
        $data['subscriptions_url'] = get_menu_url_from_table('sub_services');
        $data['documents_url'] = get_menu_url_from_table('crm_documents');
        $account_field = 'account_id';
        if ($account->type == 'reseller_user') {
            $account_field = 'reseller_user';
        }
        $data['invoices_count'] = \DB::table('crm_documents')->where($account_field, $account_id)->where('doctype', 'Tax Invoice')->count();
        $data['orders_count'] = \DB::table('crm_documents')->where($account_field, $account_id)->where('doctype', 'Order')->count();

        $line_balance = $account->balance;
        if ($account->type != 'reseller_user') {
            $debtor_transactions = collect(get_debtor_transactions($account_id))->reverse()->take(20);

            if ($account->type == 'reseller') {
                foreach ($debtor_transactions as $i => $trx) {
                    if ($trx->doctype == 'Tax Invoice' || $trx->doctype == 'Credit Note') {
                        $reseller_user = \DB::table('crm_accounts')
                            ->join('crm_documents', 'crm_documents.reseller_user', '=', 'crm_accounts.id')
                            ->where('crm_documents.id', $trx->id)
                            ->pluck('company')->first();

                        $debtor_transactions[$i]->service_company = $reseller_user;
                    } else {
                        $debtor_transactions[$i]->service_company = '';
                    }
                    $debtor_transactions[$i]->balance = $line_balance;
                    $line_balance -= $trx->total;
                }
            }
            $statement_transactions = $debtor_transactions;
            $data['statement_transactions'] = $statement_transactions;
        }

        $subs_query = \DB::table('sub_services')
            ->select('sub_services.id', 'sub_services.detail', 'sub_services.status', 'sub_services.provision_type', 'sub_services.to_cancel', 'crm_products.name as product_name', 'crm_products.code as product_code', 'crm_accounts.company as company_name')
            ->join('crm_products', 'crm_products.id', '=', 'sub_services.product_id')
            ->join('crm_accounts', 'crm_accounts.id', '=', 'sub_services.account_id')
            ->where('sub_services.to_cancel', 0)
            ->where('sub_services.status', '!=', 'Deleted');
        if ($account->type == 'reseller') {
            $account_ids = \DB::table('crm_accounts')->where('partner_id', $account_id)->pluck('id')->toArray();
            $subs_query = $subs_query->whereIn('account_id', $account_ids);
        } else {
            $subs_query = $subs_query->where('account_id', $account_id);
        }
        $subs_query->orderBy('account_id')->orderBy('product_id')->orderBy('detail');
        $data['subscriptions'] = $subs_query->get();

        $activations_query = \DB::table('sub_activations')
            ->select('sub_activations.id', 'sub_activations.detail', 'sub_activations.status', 'sub_activations.provision_type', 'crm_products.name as product_name', 'crm_products.code as product_code', 'crm_accounts.company as company_name')
            ->join('crm_products', 'crm_products.id', '=', 'sub_activations.product_id')
            ->join('crm_accounts', 'crm_accounts.id', '=', 'sub_activations.account_id')
            ->where('sub_activations.status', 'Pending');
        if ($account->type == 'reseller') {
            $account_ids = \DB::table('crm_accounts')->where('partner_id', $account_id)->pluck('id')->toArray();
            $activations_query = $activations_query->whereIn('account_id', $account_ids);
        } else {
            $activations_query = $activations_query->where('account_id', $account_id);
        }
        $activations_query->orderBy('account_id')->orderBy('product_id')->orderBy('detail');
        $data['activations'] = $activations_query->get();

        $data['hide_content_sidebar'] = 1;

        $data['pbx_domains'] = [];
        if ($account->type == 'reseller') {
            $data['pbx_domains'] = \DB::connection('pbx')->table('v_domains')->where('partner_id', $account->id)->get();
        }

        return view('__app.dashboard.customer_dashboard', $data);
    }

    public function adminDashboard(Request $request)
    {
        try {
            if (session('role_level') != 'Admin') {
                return redirect()->to('/');
            }
            \DB::table('erp_grid_views')->where('widget_type', 'Speedometer')->where('sum_field', '')->where('result_field', '>', '')->update(['sum_field' => \DB::raw('result_field')]);
            $data = [];
            $modules = \DB::table('erp_cruds')->get();
            $panels = \DB::table('erp_grid_views')
                ->select(\DB::raw('erp_grid_views.*'), \DB::raw('CONCAT(erp_cruds.name," - ",erp_grid_views.name) as panel_name'))
                ->join('erp_cruds', 'erp_cruds.id', '=', 'erp_grid_views.module_id')
                ->where('show_on_dashboard', 1)
                ->where('is_deleted', 0)
                ->where('widget_type', '>', '')
            //->where('widget_type','Line')
                ->orderBy('dashboard_sort_order')->get();
            $panel_ids = $panels->pluck('id')->toArray();

            $dashboard_state_json = unserialize(get_admin_setting('dashboard_state'));

            $dashboard_state = collect($dashboard_state_json);

            if ($dashboard_state->count() > 0) {
                $dashboard_state_ids = $dashboard_state->pluck('id')->toArray();
            } else {
                $dashboard_state_ids = [];
            }
            foreach ($panels as $i => $panel) {
                $panels[$i]->layout_url = $modules->where('id', $panel->module_id)->pluck('slug')->first();
                $panels[$i]->layout_url .= '?layout_id='.$panel->id;
            }

            $data['panels'] = $panels;

            // REMOVE DELETED LAYOUTS FROM DASHBOARD STATE
            foreach ($dashboard_state as $i => $dashboard_panel) {
                $dashboard_panel = (object) $dashboard_panel;
                if (! in_array($dashboard_panel->id, $panel_ids)) {
                    unset($dashboard_state[$i]);
                }
            }

            // SET CONTENT AND CHARTDATA ON DASHBOARD STATE
            foreach ($dashboard_state as $i => $dashboard_panel) {
                $dashboard_panel = (object) $dashboard_panel;
                foreach ($panels as $panel) {
                    if ($dashboard_panel->id == $panel->id) {

                        $panel_data = (object) $dashboard_panel;
                        $panel_data->id = (string) $panel->id;
                        $panel_data->row = (int) $panel_data->row;
                        $panel_data->col = (int) $panel_data->col;
                        $panel_data->sizeX = (int) $panel_data->sizeX;
                        $panel_data->sizeY = (int) $panel_data->sizeY;
                        $panel_data->content = '<div id="panelcontent'.$panel->id.'" class="content panelchart" data-report-link="'.$panel->layout_url.'" data-report-id="'.$panel->id.'"><div id="panel'.$panel->id.'"></div></div>';

                        $dashboard_state[$i] = $panel_data;

                    }
                }
            }

            // ADD NEW LAYOUTS TO DASHBOARD STATE
            $row_count = collect($dashboard_state)->max('row');
            if (! $row_count) {
                $row_count = 0;
            } else {
                $row_count++;
            }
            $col_count = 0;
            foreach ($panels as $i => $panel) {

                if (! in_array($panel->id, $dashboard_state_ids)) {
                    $panel_data = (object) [
                        'id' => (string) $panel->id,
                        'sizeX' => 1,
                        'sizeY' => 1,
                        'row' => $row_count,
                        'col' => $col_count,
                        'content' => '<div id="panelcontent'.$panel->id.'" class="content panelchart" data-report-link="'.$panel->layout_url.'" data-report-id="'.$panel->id.'"><div id="panel'.$panel->id.'"></div></div>',
                    ];
                    $dashboard_state[] = $panel_data;
                    $col_count++;
                }
                if ($col_count == 4) {
                    $col_count = 0;
                    $row_count++;
                }
            }

            $data['dashboard_state'] = array_values(collect($dashboard_state)->toArray());

            foreach ($data['panels'] as $i => $panel) {
                $data['panels'][$i]->chart_data = get_chart_data($panel->id);
                if ($panel->widget_type == 'Pyramid') {
                    $data['panels'][$i]->chart_data = array_values($data['panels'][$i]->chart_data);
                }
            }

            $data['menu_name'] = 'Dashboard';
            $data['module_id'] = 500;
            $data['layouts_url'] = get_menu_url_from_table('erp_grid_views');
            $data['hide_content_sidebar'] = 1;

            return view('__app.dashboard.dashboard', $data);
        } catch (\Throwable $ex) {
        }
    }

    public function getChartData($id)
    {
        try {
            return get_chart_data($id);
        } catch (\Throwable $ex) {
        }
    }

    public function removeChart($id)
    {
        try {
            \DB::table('erp_grid_views')->where('id', $id)->update(['show_on_dashboard' => 0]);

            return json_alert('Done');
        } catch (\Throwable $ex) {
        }
    }

    public function saveDashboardState(Request $request)
    {
        /*
           $dashboard_state = $request->dashboard_state;
           $updated = \DB::table('erp_admin_settings')->update(['dashboard_state' => serialize($dashboard_state)]);

           if($updated){
               return json_alert('Saved');
           }else{
               return json_alert('Not saved','warning');
           }
           */
    }

    public function saveDashboardPanels(Request $request)
    {
        //aa('saveDashboarPanels');
        //aa($request->all());

        $c = \DB::connection('system')->table('erp_instances')->where('id', $request->instance_id)->pluck('db_connection')->first();
        //aa($c);
        if (! empty($request->dashboard_state) && is_array($request->dashboard_state) && count($request->dashboard_state) > 0) {
            foreach ($request->dashboard_state as $l) {
                $id = str_replace('chartpanel', '', $l['id']);
                $data = [
                    'dashboard_row' => $l['row'],
                    'dashboard_col' => $l['col'],
                    'dashboard_sizex' => $l['sizeX'],
                    'dashboard_sizey' => $l['sizeY'],
                ];
                \DB::connection($c)->table('erp_grid_views')->where('id', $id)->update($data);
            }
        }
    }

    public function indexBackup(Request $request)
    {
        if (session('role_level') != 'Admin') {
            return redirect()->to('/');
        }

        $cards = \Erp::getDashboardCards();

        $instances = \DB::connection('system')->table('erp_instances')->whereIn('id', [1, 2, 11])->get();
        $data['instances'] = [];

        $data['user_stats'] = [];
        $users = \DB::connection('system')->table('erp_users')->where('id', '!=', 1)->where('account_id', 1)->where('is_deleted', 0)->orderBy('sort_order')->get();
        $roles = \DB::connection('system')->table('erp_user_roles')->where('level', 'Admin')->orderBy('sort_order')->get();

        $data['roles'] = $roles;

        $ledger_url = get_menu_url_from_module_id(180);
        $documents_url = get_menu_url_from_module_id(353);
        $banking_details_url = get_menu_url_from_module_id(1837);
        $processes_url = get_menu_url_from_module_id(1945);

        foreach ($instances as $instance) {
            // second row - kpi cards
            // ROW 2 - 5 KPIS per row - Quotes, Orders, Sales, Expenses, Net Profit
            /*
            $dashboard_layouts = \DB::connection($instance->db_connection)->table('erp_grid_views')
            ->where('show_on_dashboard',1)
            ->where('is_deleted',0)
            ->orderBy('dashboard_sort_order')
            ->get();
            */
            $dashboard_layouts = [];
            foreach ($dashboard_layouts as $i => $layout) {

                $dashboard_layouts[$i]->dashboard_sql = workboard_layout_row_count($layout->id, 1, 0, true);

                $module_url = \DB::table('erp_cruds')->where('id', $layout->module_id)->pluck('slug')->first();
                $dashboard_link = $module_url.'?layout_id='.$layout->id;
                $dashboard_layouts[$i]->dashboard_link = $dashboard_link;
            }
            $kpis = [];
            foreach ($dashboard_layouts as $i => $layout) {
                $totals_result = \DB::connection($instance->db_connection)->select($layout->dashboard_sql);

                $result_field = 'lastrow';
                if ($layout->total_field > '') {
                    $result_field = $layout->total_field;
                }

                $total = collect($totals_result)->pluck($result_field)->first();
                if (str_contains($layout->total_field, 'total')) {
                    $total = currency_formatted($total);
                }

                $dashboard_link = $layout->dashboard_link;
                $dashboard_link = 'https://'.$instance->domain_name.'/'.$layout->dashboard_link;

                if ($instance->id != 1 && $dashboard_link > '') {
                    $dashboard_link .= '&admin_user_id='.session('user_id');
                }

                $trx_link = '';
                $ledger_link = '';
                if ($layout->name == 'Quotes') {
                    $trx_link = 'https://'.$instance->domain_name.'/'.$documents_url.'?remove_layout_filters=1&docdate=currentMonth&doctype=Quotation';

                }
                if ($layout->name == 'Orders') {
                    $trx_link = 'https://'.$instance->domain_name.'/'.$documents_url.'?remove_layout_filters=1&docdate=currentMonth&doctype=Order';

                }
                if ($layout->name == 'Sales') {
                    $trx_link = 'https://'.$instance->domain_name.'/'.$documents_url.'?remove_layout_filters=1&docdate=currentMonth&doctype=Tax%20Invoice,Credit%20Note';
                    $ledger_link = 'https://'.$instance->domain_name.'/'.$ledger_url.'?remove_layout_filters=1&docdate=currentMonth&doctype=Tax%20Invoice,Credit%20Note';
                }
                if ($layout->name == 'Cost of Sales') {
                    $ledger_link = 'https://'.$instance->domain_name.'/'.$ledger_url.'?remove_layout_filters=1&docdate=currentMonth&ledger_account_category_id=15';
                }
                if ($layout->name == 'Expenses') {
                    $ledger_link = 'https://'.$instance->domain_name.'/'.$ledger_url.'?remove_layout_filters=1&docdate=currentMonth&ledger_account_category_id=20,21';
                    $ledger_account_ids = \DB::connection($instance->db_connection)->table('acc_ledger_accounts')->whereIn('ledger_account_category_id', [20, 21])->pluck('id')->toArray();
                    $trx_link = 'https://'.$instance->domain_name.'/'.$banking_details_url.'?remove_layout_filters=1&docdate=currentMonth&ledger_account_id='.implode(',', $ledger_account_ids);
                }
                if ($layout->name == 'Profit') {
                    $ledger_link = 'https://'.$instance->domain_name.'/'.$ledger_url.'?remove_layout_filters=1&docdate=currentMonth&ledger_account_category_id=10,20,21';
                }

                if ($instance->id != 1 && $trx_link > '') {
                    $trx_link .= '&admin_user_id='.session('user_id');
                }
                if ($instance->id != 1 && $ledger_link > '') {
                    $ledger_link .= '&admin_user_id='.session('user_id');
                }

                $kpis[] = (object) ['name' => $layout->name, 'total' => $total, 'layout_link' => $dashboard_link, 'layout_id' => $layout->id, 'trx_link' => $trx_link, 'ledger_link' => $ledger_link];

            }
            $instance->kpis = $kpis;

            // current assets
            $assets = [];
            $banking_ledger_accounts = \DB::connection($instance->db_connection)->table('acc_cashbook')->where('status', 'Enabled')->pluck('ledger_account_id')->toArray();
            $ledger_accounts = \DB::connection($instance->db_connection)->table('acc_ledger_accounts')->whereIn('id', $banking_ledger_accounts)->orderBy('sort_order')->get();
            $cashflow_total = 0;
            foreach ($ledger_accounts as $ledger_account) {
                $total = \DB::connection($instance->db_connection)->table('acc_ledgers')->where('ledger_account_id', $ledger_account->id)->sum('amount');
                $cashflow_total += $total;
                if ($total == 0) {
                    //    continue;
                }
                $total = currency_formatted($total);
                $cashbbok_id = \DB::connection($instance->db_connection)->table('acc_cashbook')->where('ledger_account_id', $ledger_account->id)->pluck('id')->first();
                if ($cashbbok_id) {
                    $trx_link = 'https://'.$instance->domain_name.'/'.$banking_details_url.'?remove_layout_filters=1&cashbook_id='.$cashbbok_id;
                } else {
                    $trx_link = 'https://'.$instance->domain_name.'/'.$banking_details_url.'?remove_layout_filters=1&ledger_account_id='.$ledger_account->id;
                }

                $ledger_link = 'https://'.$instance->domain_name.'/'.$ledger_url.'?remove_layout_filters=1&ledger_account_id='.$ledger_account->id;

                if ($instance->id != 1 && $trx_link > '') {
                    $trx_link .= '&admin_user_id='.session('user_id');
                }
                if ($instance->id != 1 && $ledger_link > '') {
                    $ledger_link .= '&admin_user_id='.session('user_id');
                }
                $assets[] = (object) ['name' => $ledger_account->name, 'total' => $total, 'trx_link' => $trx_link, 'ledger_link' => $ledger_link];
            }

            $instance->assets = $assets;
            $instance->cashflow_total = currency_formatted($cashflow_total);
            // processes

            $processes = [];

            $instance->processes = $processes;

            // company logo
            $logo = \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('account_id', 1)->pluck('logo')->first();
            $instance->brand_logo = 'https://'.$instance->domain_name.'/uploads/'.$instance->db_connection.'/348/'.$logo;

            $data['instances'][] = $instance;

        }

        $links = [
            ['name' => 'VersaOffice', 'url' => 'https://cloudtelecoms.onice.io/webmail/'],
            ['name' => 'ZenDesk', 'url' => 'https://cloudtelecoms.zendesk.com/'],
        ];

        $iframes = [];
        $iframe_modules = \DB::table('erp_grid_views')->where('show_on_dashboard', 1)->orderBy('dashboard_sort_order')->get();

        foreach ($iframe_modules as $iframe_module) {
            $access = \DB::table('erp_forms')->where('role_id', session('role_id'))->where('module_id', $iframe_module->module_id)->where('is_view', 1)->count();
            if ($access) {
                $module_name = \DB::table('erp_cruds')->where('id', $iframe_module->module_id)->pluck('name')->first();
                $url = get_menu_url_from_module_id($iframe_module->module_id).'?layout_id='.$iframe_module->id;
                $iframe_url = get_menu_url_from_module_id($iframe_module->module_id).'?layout_id='.$iframe_module->id;

                $iframes[] = (object) ['id' => $iframe_module->id, 'name' => strtoupper($module_name).' - '.$iframe_module->name, 'url' => $url, 'iframe_url' => $iframe_url.'&from_iframe=1&hide_toolbar_items=1', 'external_url' => $iframe_url];
            }
        }

        $data['links'] = $links;
        $data['iframes'] = $iframes;

        $data['menu_name'] = 'Dashboard';
        $data['module_id'] = 500;

        $data['cards'] = $cards;
        $data['pyramids'] = [['id' => 1], ['id' => 2]];

        if (! empty($request->return_container)) {
            return view('__app.dashboard.dashboard_container', $data);
        }

        return view('__app.dashboard.dashboard', $data);
    }

    public function staffDashboard(Request $request)
    {

        if (session('role_level') != 'Admin') {
            return redirect()->to('/');
        }
        $data = [];
        $links = [
            ['name' => 'VersaOffice', 'url' => 'https://cloudtelecoms.onice.io/webmail/'],
            ['name' => 'ZenDesk', 'url' => 'https://app.respond.io/'],
        ];

        $iframes = [];
        if (is_superadmin() || is_manager()) {
            $iframes[] = ['name' => 'Approvals', 'url' => 'https://'.session('instance')->domain_name.'/'.get_menu_url_from_module_id(1859)];
        }

        $iframes[] = ['name' => 'Support Center', 'url' => 'https://'.session('instance')->domain_name.'/'.get_menu_url_from_module_id(334)];
        $iframes[] = ['name' => 'Processes', 'url' => 'https://'.session('instance')->domain_name.'/'.get_menu_url_from_module_id(1945)];
        $iframes[] = ['name' => 'Projects', 'url' => 'https://'.session('instance')->domain_name.'/'.get_menu_url_from_module_id(1944)];

        $data['links'] = $links;
        $data['iframes'] = $iframes;
        $data['menu_name'] = 'Staff Dashboard';
        $data['module_id'] = 500;

        $logo = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', 1)->pluck('logo')->first();
        $data['brand_logo'] = 'https://'.session('instance')->domain_name.'/uploads/'.session('instance')->directory.'/348/'.$logo;

        return view('__app.dashboard.staff_dashboard', $data);
    }
}
