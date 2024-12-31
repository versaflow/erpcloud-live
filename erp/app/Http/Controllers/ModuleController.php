<?php

namespace App\Http\Controllers;

use App\Models\ErpModel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Rap2hpoutre\FastExcel\FastExcel;
use Redirect;

class ModuleController extends BaseController
{
    use ValidatesRequests;

    public $data;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! empty(request()->admin_user_id) && empty(session('role_id'))) {
                $user = \DB::connection('system')->table('erp_users')->where('id', request()->admin_user_id)->get()->first();

                if ($user->is_deleted) {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.');
                }

                if (! session('instance')->customer_erp && ! empty($user) && $user->active) {
                    $role = \DB::connection('system')->table('erp_user_roles')->where('id', $user->role_id)->get()->first();

                    if ($role->level == 'Admin') {
                        $logged_in = \DB::connection('system')->table('erp_user_sessions')->where('user_id', $user->id)->where('ip_address', request()->ip())->count();

                        if ($logged_in) {
                            $row = \DB::connection('default')->table('erp_users')->where('username', $user->username)->get()->first();

                            if ($row) {
                                // check admin instance access
                                $user_id = \DB::connection('system')->table('erp_users')->where('username', $row->username)->pluck('id')->first();

                                $role = \DB::connection('system')->table('erp_user_roles')->where('id', $row->role_id)->get()->first();
                                $instance_access = get_admin_instance_access($user->username);
                                if (! empty(session('instance')->id)) {
                                    $instance_id = session('instance')->id;
                                } else {
                                    $instance_id = \DB::connection('system')->table('erp_instances')->where('domain_name', str_replace('https://', '', request()->root()))->pluck('id')->first();
                                }
                                if (! in_array($instance_id, $instance_access)) {
                                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'No Access.')->withInput();
                                }

                                set_session_data($row->id);
                                session(['original_role_id' => $role->id]);
                            }
                        }
                    }
                }
            }

            if (empty(session('webform_module_id'))) {
                if (! session()->has('user_id') || empty(session('user_id')) || ! session()->has('account_id')
                || empty(session('account_id')) || ! session()->has('role_id') || empty(session('role_id'))) {
                    \Auth::logout();
                    \Session::flush();

                    return Redirect::to('/');
                }
            }

            $initModel = $this->initModel($request->segment(1));
            if ($initModel !== true) {
                return $initModel;
            }

            if (! request()->ajax()) {
                session()->forget('email_result');
                session()->forget('email_message');
                session()->forget('email_mod_id');

                \Erp::set_service_account_session();
            }

            return $next($request);
        });

        if (! request()->ajax()) {
            $this->middleware('globalviewdata')->only(['index', 'getEdit', 'getTransactionEdit', 'button', 'aggridReport']);
        } else {
            $this->middleware('globalviewdata')->only(['index', 'getEdit', 'getTransactionEdit', 'aggridReport']);
        }
        $this->middleware('tasksactive')->only(['index']);
    }

    public function initModel($url)
    {
        // if(is_dev()){
        //     $this->model = new \App\Models\ErpModelDev();
        // }else{
        $this->model = new ErpModel;
        // }

        $this->model->setMenuData($url);
        if (empty($this->model->module_name)) {
            $response = [
                'status' => 'error',
                'message' => 'Route Not Found',
            ];
            // return response()->json($response);
            if (request()->ajax()) {
                return response()->json($response);
            } else {
                return redirect()->to('/')->with($response);
            }
        }

        $this->data = $this->model->info;

        if ($this->data['app_id'] == 14 && empty(session('sms_account_id'))) {
            $pbx = new \FusionPBX;

            return $pbx->sms_login(session('account_id'));
        }

        session(['mod_id' => $this->data['module_id']]);
        session(['mod_conn' => $this->data['connection']]);

        if (! empty(session('webform_module_id')) && session('webform_module_id') != $this->data['module_id']) {
            session()->forget('webform_module_id');
            session()->forget('webform_id');
            session()->forget('webform_account_id');
            session()->forget('webform_subscription_id');
        }

        $check_access = true;

        if (! empty(session('webform_module_id'))) {
            $check_access = false;
        }
        if (session('user_id') == 5035) {
            //dd($this->data['access']);
        }

        if ($check_access) {
            if (! $this->data['access'] || $this->data['access'] == 'subscription') {
                $response = [
                    'status' => 'error',
                    'message' => 'No Access',
                ];

                if (request()->ajax()) {
                    return response()->json($response);
                } else {
                    return redirect()->back()->with($response);
                }
            }
        }

        return true;
    }

    public function index(Request $request)
    {
        session(['remove_show_deleted'.$this->data['module_id'] => 0]);
        $tab_load = 0;
        if (! empty($request->tab_load)) {
            $request->request->remove('tab_load');
            $tab_load = 1;
        }

        session()->forget('form_builder_redirect');

        if (! empty(session('show_deleted'.$this->data['module_id']))) {
            session(['show_deleted'.$this->data['module_id'] => 0]);
        }
        if (! empty(request()->id)) {
            session(['show_deleted'.$this->data['module_id'] => 1]);
        }

        $erp = new \DBEvent($this->data['module_id']);
        $erp->processOnLoad();

        try {
            \DB::connection($this->data['connection']);
        } catch (\Throwable $ex) {
            exception_log($ex);
            abort(500, 'DB connection error');
        }
        update_recent_modules($this->data['module_id']);
        $default_grid_report_id = \DB::connection('default')->table('erp_reports')->where('module_id', $this->data['module_id'])->where('default', 1)->pluck('id')->first();
        if (empty($default_grid_report_id)) {
            // $default_grid_report_id = create_default_report($this->data['module_id']);
        }

        if (! empty($request->app_id)) {
            session(['app_id_lookup' => $request->app_id]);
        } else {
            session()->forget('app_id_lookup');
        }

        if (! $this->data['access']['is_menu']) {
            $response = [
                'status' => 'error',
                'message' => 'No List Access',
            ];

            if (request()->ajax()) {
                return response()->json($response);
            } else {
                if (! empty(session('is_api_session'))) {
                    return 'No Access';
                }

                return redirect()->back()->with($response);
            }
        }

        if (! empty($request->module_id) && ($this->data['db_table'] == 'erp_module_fields')) {
            update_module_config_from_schema($request->module_id);
        } else {
            if (! $this->data['serverside_model']) {
                try {
                    $sql = $this->model->getClientSql($request);

                    $data = \DB::connection($this->data['connection'])->select($sql.' limit 1');
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    update_module_config_from_schema($this->data['module_id']);
                }
            }
        }

        // guides

        //if($this->data['module_id'] == 2018){
        $this->data['has_module_guides'] = 1;
        //}

        $grid_id = $this->data['module_id'].'_'.date('U');
        // aa('tabload');
        //  aa($this->data['module_id']);
        // aa($grid_id);
        $this->data['grid_id'] = $grid_id;
        $this->data['master_grid_id'] = $grid_id;
        $grid = new \ErpGrid($this->data, $request->all());
        $data = $grid->getGrid();
        $data['tab_load'] = $tab_load;

        $workspace_role = get_workspace_role_from_module_id($this->data['module_id']);

        $data['workspace_role_name'] = $workspace_role->name;

        $branding_logo = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', session('parent_id'))->pluck('logo')->first();

        $branding_logo = settings_url().$branding_logo;

        // if(!$branding_logo){

        //     $branding_logo = '';
        // }
        //$pbx_logo = \DB::connection('default')->table('erp_instances')->where('id', session('instance')->id)->pluck('pbx_logo')->first();
        //if($pbx_logo){
        //$pbx_logo = 'https://'.session('instance')->domain_name .'/uploads/'.session('instance')->directory.'/305/'.$pbx_logo;
        //}
        //if(!empty($pbx_logo) && !empty($pbx_admin_menu_menu) && count($pbx_admin_menu_menu) > 0){
        //  $branding_logo = $pbx_logo;
        //}

        $data['workspace_filter_datasource'] = [];

        if (! empty($this->data['primary_role_id']) && $this->data['db_table'] == 'crm_staff_tasks' && (is_superadmin() || is_manager() || ! empty(session('extra_role_id')))) {
            $active_role_ids = \DB::table('crm_staff_tasks')->where('is_deleted', 0)->pluck('role_id')->unique()->toArray();
            if (is_superadmin()) {
                //  $roles = \DB::connection('default')->table('erp_user_roles')->whereIn('id',$active_role_ids)->select('id','name')->where('level','Admin')->orderBy('sort_order')->get()->toArray();
                $roles = \DB::connection('default')->table('erp_user_roles')->select('id', 'name')->where('level', 'Admin')->orderBy('sort_order')->get()->toArray();
            } elseif (is_manager()) {
                //  $roles = \DB::connection('default')->table('erp_user_roles')->whereIn('id',$active_role_ids)->select('id','name')->where('level','Admin')->orderBy('sort_order')->get()->toArray();
                $roles = \DB::connection('default')->table('erp_user_roles')->select('id', 'name')->where('id', '!=', 1)->where('level', 'Admin')->orderBy('sort_order')->get()->toArray();
            } else {
                $roles = \DB::connection('default')->table('erp_user_roles')->whereIn('id', session('role_ids'))->select('id', 'name')->where('level', 'Admin')->orderBy('sort_order')->get()->toArray();
            }
            if (is_superadmin()) {
                $all = (object) ['id' => 100, 'name' => 'All'];
                //array_unshift($roles,$all);
                $roles[] = $all;
            }

            if (count($roles) > 1) {
                $data['workspace_filter_datasource'] = $roles;
                $data['workspace_filter_placeholder'] = collect($roles)->where('id', session('role_id'))->pluck('name')->first();
                $data['workspace_filter_selected'] = collect($roles)->where('id', session('role_id'))->pluck('id')->first();
            }
        }

        $data['module_cards'] = \Erp::getModuleCards($this->data['module_id']);

        //}
        $data['module_footer_cards'] = \Erp::getModuleCards($this->data['module_id'], 1);
        /*
        $workspace_ids = \DB::connection('default')->table('erp_cruds')->where('is_workspace_module',1)->pluck('id')->toArray();
        if(in_array($this->data['module_id'],$workspace_ids)){
            $data['grid_charts'] = [];
            $data['chart_accordion'] = [];


            $response_items = [];

            $layouts = \DB::connection('default')->table('erp_grid_views')->where('is_deleted',0)->where('show_on_dashboard',1)->where('project_id','>','')->orderBy('module_id')->orderBy('name')->get();
            foreach($layouts as $i => $chart){
                if($chart->widget_type == 'Grid'){
                   $chart->chart_data = [];
                }else{
                    $chart->chart_data = get_chart_data($chart->id);
                    if($panel->widget_type == 'Pyramid'){
                    $chart->chart_data = array_values($chart->chart_data);
                    }
                }
                $chart->slug = app('erp_config')['modules']->where('id',$chart->module_id)->pluck('slug')->first();
                $data['grid_charts'][] = $chart;
                $data['chart_accordion'][] = (object) [
                'id' => $chart->id,
                'project_id' => $chart->project_id,
                'header' => $chart->name,
                'content' => '#chartcontent'.$chart->id,
                'layout_link' => $chart->slug.'?layout_id='.$chart->id
                ];
            }
        }
        */

        $workspace_ids[] = 334;
        $data['show_layouts_sidebar'] = true;
        $data['workspace_ids'] = $workspace_ids;
        $top_level_workspace_id = \DB::connection('default')->table('erp_menu')->where('workspace_render_id', '>', 0)->where('module_id', $this->data['module_id'])->pluck('workspace_render_id')->first();

        $data['worspace_context_menu'] = false;
        if ($top_level_workspace_id) {
            $data['worspace_context_menu'] = true;
        }
        $incentive_footer_html = '';
        $data['incentive_footer'] = '';
        if ($this->data['module_id'] == 2018) {
            $incentive_footer_html = get_incentive_footer(session('role_id'));
        }
        if ($incentive_footer_html > '') {
            $data['incentive_footer'] = $incentive_footer_html;
        } elseif (session('role_id') == 1 && $this->data['module_id'] == 2018) {
            $data['incentive_footer'] = 'Superadmin';
        }

        // $data['incentive_footer'] = '';

        if ($this->data['module_id'] == 2018) {
            $data['task_in_progress'] = \DB::table('crm_staff_tasks')->where('user_id', session('user_id'))->where('is_deleted', 0)->where('progress_status', 'In Progress')->count();
        }

        if (! empty($this->data['module_tooltip'])) {
            $data['module_tooltip'] = $this->data['module_tooltip'];
        }

        $data['is_workspace_module'] = false;
        if (in_array($this->data['module_id'], $workspace_ids)) {
            $data['is_workspace_module'] = true;
            if ($this->data['db_table'] == 'crm_staff_tasks') {
                $data['show_layouts_sidebar'] = false;
            }
        }

        // sidebar charts
        /*
        if($this->data['module_id'] == 1944){
            $data['charts'] = [];
            $data['chart_accordion'] = [];


            $response_items = [];

            $layouts = \DB::connection('default')->table('erp_grid_views')->where('is_deleted',0)->where('show_on_dashboard',1)->where('project_id','>','')->orderBy('module_id')->orderBy('name')->get();
            foreach($layouts as $i => $chart){
                if($chart->widget_type == 'Grid'){
                   $chart->chart_data = [];
                }else{
                    $chart->chart_data = get_chart_data($chart->id);
                    if($panel->widget_type == 'Pyramid'){
                    $chart->chart_data = array_values($chart->chart_data);
                    }
                }
                $chart->slug = app('erp_config')['modules']->where('id',$chart->module_id)->pluck('slug')->first();
                $data['charts'][] = $chart;
                $data['chart_accordion'][] = (object) [
                'id' => $chart->id,
                'project_id' => $chart->project_id,
                'header' => $chart->name,
                'content' => '#chartcontent'.$chart->id,
                'layout_link' => $chart->slug.'?layout_id='.$chart->id
                ];
            }
        }
        */

        $data['branding_logo'] = $branding_logo;

        if (! $data['serverside_model']) {
            $count = $this->model->getTotalCount();
            if ($count > 100000) {
                $data['serverside_model'] = 1;
                \DB::connection('default')->table('erp_cruds')->where('id', $this->data['module_id'])->update(['serverside_model' => 1]);
            }
        }

        if (! empty($request->remove_container)) {
            $data['remove_container'] = 1;
        }
        if (! empty($request->hide_toolbar)) {
            $data['hide_toolbar'] = 1;
        }

        if (str_contains($this->data['connection'], 'pbx')) {
            if (session('role_level') == 'Customer' && empty(session('service_account_domain_uuid'))) {
                $response = [
                    'status' => 'warning',
                    'message' => 'Place an order for a pbx extension to gain access to this page.',
                ];

                if (request()->ajax()) {
                    return response()->json($response);
                } else {
                    return redirect()->to('/')->with($response);
                }
            }
            // $favicon = \DB::connection('default')->table('erp_menu')->where('location', 'pbx')->pluck('favicon')->first();

            $data['is_pbx'] = true;
        } elseif (! empty($this->data['menu']->favicon)) {
            $data['favicon'] = uploads_url(499).$this->data['menu']->favicon;
            $data['is_pbx'] = false;
        }
        /*
        if(is_main_instance()){
        if (!empty($this->data['menu']->menu_icon)) {
            if(str_contains($this->data['menu']->menu_icon,'fab ')){
                $data['favicon'] = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/svgs/brands/'.str_replace(['fa-','fas ','fab ','far '],'',$this->data['menu']->menu_icon).'.svg';
            }else{
                $data['favicon'] = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/svgs/solid/'.str_replace(['fa-','fas ','fab ','far '],'',$this->data['menu']->menu_icon).'.svg';
            }
        }
        }
        */
        $cols = get_columns_from_schema($data['db_table']);
        $data['status_btn'] = false;
        if (in_array('status', $cols)) {
            $data['status_btn'] = true;
        }

        if ($this->data['db_table'] == 'crm_documents' && ! empty($request->id)) {
            $doc = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
            $link = generate_paynow_link($doc->account_id, $doc->total, true);
            if ($link && $doc->total > 0 && ($doc->doctype == 'Tax Invoice' || $doc->doctype == 'Order') && $doc->payment_status != 'Complete') {
                if (session('role_id') > 10) { // dont show paynow popup for admin
                    $data['payment_popup'] = 'document_popup/'.$request->id;
                }
            }
        }

        $data['module_urls'] = app('erp_config')['modules']->pluck('slug', 'id')->toArray();

        $data['cashregister_url'] = get_menu_url_from_table('acc_cashbook_transactions');
        $data['documents_url'] = get_menu_url_from_table('crm_documents');
        $data['pricelist_items_url'] = get_menu_url_from_table('crm_pricelist_items');
        $data['supplier_documents_url'] = get_menu_url_from_table('crm_supplier_documents');
        $data['supplier_payments_url'] = get_menu_url_from_table('acc_payment_suppliers');
        $data['accounts_url'] = get_menu_url_from_module_id(343);
        $data['debtors_url'] = get_menu_url_from_module_id(343);
        $data['opportunities_url'] = get_menu_url_from_module_id(1923);
        $data['users_url'] = get_menu_url_from_table('erp_users');
        $data['communications_url'] = get_menu_url_from_table('erp_communication_lines');
        $data['call_history_url'] = get_menu_url_from_table('erp_call_history');
        $data['subscriptions_url'] = get_menu_url_from_table('sub_services');
        $data['suppliers_url'] = get_menu_url_from_table('crm_suppliers');
        $data['layouts_url'] = get_menu_url_from_table('erp_grid_views');
        $data['forms_url'] = get_menu_url_from_table('erp_forms');
        $data['events_url'] = get_menu_url_from_table('erp_form_events');
        $data['reports_url'] = get_menu_url_from_table('erp_reports');
        $data['crud_url'] = get_menu_url_from_table('erp_cruds');
        $data['accounts_contact_url'] = get_menu_url_from_module_id(1810);
        $data['suppliers_contact_url'] = get_menu_url_from_module_id(1811);
        $data['guides_url'] = get_menu_url_from_module_id(1875);
        $data['kbitems_url'] = get_menu_url_from_module_id(1948);
        $data['module_cards_url'] = get_menu_url_from_module_id(1905);

        $data['module_fields_url'] = get_menu_url_from_table('erp_module_fields');
        $data['condition_styles_url'] = get_menu_url_from_table('erp_grid_styles');
        $data['condition_styles_templates'] = array_keys(get_site_colors_templates());
        $data['condition_styles_templates'][] = 'None';

        $data['account_contacts_url'] = get_menu_url_from_table('erp_users');
        $data['supplier_contacts_url'] = get_menu_url_from_table('crm_supplier_contacts');
        $data['module_log_url'] = get_menu_url_from_table('erp_module_log');

        $data['sms_panel_url'] = get_menu_url_from_table('isp_sms_messages');
        $data['hosting_panel_url'] = get_menu_url_from_table('isp_host_websites');
        $data['fibre_panel_url'] = get_menu_url_from_table('isp_data_fibre');
        $data['ip_panel_url'] = get_menu_url_from_table('isp_data_ip_ranges');
        $data['pbx_panel_url'] = get_menu_url_from_module_id(539);
        $data['kb_url'] = get_menu_url_from_table('crm_training_guides');
        $data['menu_manager_url'] = get_menu_url_from_table('erp_menu');

        if (session('instance')->directory == 'eldooffice') {
            $data['rentals_url'] = get_menu_url_from_table('crm_rental_leases');
        }

        if ($data['module_id'] == 200) {
            $data['available_pbx_numbers'] = get_available_pbx_phone_numbers();
        }

        $menu_params = [
            'app_id' => $data['app_id'],
            'module_id' => $data['module_id'],
            'menu_route' => $data['menu_route'],
            'menu_id' => $data['menu_id'],
            'connection' => $data['connection'],
        ];

        // $field_list = $this->data['db_module_fields']->pluck('field')->toArray();
        $data['grid_menu_menu'] = \ErpMenu::build_menu('grid_menu', $menu_params, $data['module_id']);

        $data['status_dropdown'] = get_status_dropdown($data['module_id']);

        if (session('role_level') == 'Admin') {
            $data['related_items_menu_menu'] = \ErpMenu::build_menu('related_items_menu', $menu_params, $data['module_id']);
        }
        $data['adminbtns_menu'] = \ErpMenu::build_menu('module_actions', $menu_params, $data['module_id']);

        if (session('role_level') == 'Admin') {
            // $data['grid_menu_context'] =  \ErpMenu::getAggridContextMenu($data['module_id'],$menu_params);

            $has_status_field = collect($this->data['module_fields'])->where('field', 'status')->count();
            if ($has_status_field) {
                $opt_values = collect($this->data['module_fields'])->where('field', 'status')->first();
                $opt_values = $opt_values['opts_values'];

                $opt_values = collect(explode(',', $opt_values))->unique()->filter()->toArray();
                $opt_values = array_diff($opt_values, ['Deleted']);

                $opt_values = collect($opt_values)->unique()->filter()->toArray();
                $data['context_statuses'] = $opt_values;
            }
        }

        if (session('role_level') == 'Admin') {
            $data['module_context_builder_menu'] = \ErpMenu::build_menu('context_builder', $menu_params);
        }

        if (! empty($request->from_iframe)) {
            $data['hide_page_header'] = 1;
            $data['remove_container'] = 1;
            $data['is_iframe'] = 1;
            $data['iframe'] = 1;
            $data['module_builder_menu'] = null;
        }

        if (! empty(request()->account_id)) {
            $ref_account = \DB::connection('default')->table('crm_accounts')->where('id', request()->account_id)->pluck('company')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            }
        }

        if (! empty(request()->reseller_user)) {
            $ref_account = \DB::connection('default')->table('crm_accounts')->where('id', request()->reseller_user)->pluck('company')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            }
        }

        if (! empty(request()->partner_id)) {
            $ref_account = \DB::connection('default')->table('crm_accounts')->where('id', request()->partner_id)->pluck('company')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            }
        }

        if (! empty(request()->domain_uuid)) {
            $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request()->domain_uuid)->pluck('domain_name')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            } else {
                $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_name', request()->domain_uuid)->pluck('domain_name')->first();
                if (! empty($ref_account)) {
                    $data['modal_ref'] = $ref_account;
                }
            }
        }

        if (! empty(request()->realm)) {
            $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request()->realm)->pluck('domain_name')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            } else {
                $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_name', request()->realm)->pluck('domain_name')->first();
                if (! empty($ref_account)) {
                    $data['modal_ref'] = $ref_account;
                }
            }
        }

        if (! empty(request()->user_context)) {
            $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request()->user_context)->pluck('domain_name')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            } else {
                $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_name', request()->user_context)->pluck('domain_name')->first();
                if (! empty($ref_account)) {
                    $data['modal_ref'] = $ref_account;
                }
            }
        }
        if (isset($data['modal_ref'])) {
            $data['note'] = $data['modal_ref'];
        }

        if ($this->data['db_table'] == 'call_records_outbound_lastmonth' && ! empty(session('cdr_archive_table'))) {
            $data['menu_name'] = ucwords(str_replace('_', ' ', session('cdr_archive_table')));
        }

        if (str_contains(request()->headers->get('referer'), 'token=')) {
            $parts = parse_url(request()->headers->get('referer'));

            parse_str($parts['query'], $query);
            $data['referer_token'] = $query['token'];
            $data['referer_url'] = $parts['scheme'].'://'.$parts['host'];
        }

        $data['doctypes'] = null;
        $data['check_doctype'] = false;
        $ledger_tables = \DB::connection('default')->table('acc_doctypes')->pluck('doctable')->unique()->toArray();
        if (in_array($this->data['db_table'], $ledger_tables)) {
            $data['doctypes'] = \DB::connection('default')->table('acc_doctypes')->get();
            $data['check_doctype'] = true;
        }

        if (! empty($data['grid_layout_id']) && ! empty($data['layout_settings'])) {
            $data['layout_init'] = $this->aggridLayoutData($data['grid_layout_id'], $this->data['grid_id'], true);
            //dddd($data['layout_init']);
        }
        if (! empty($data['layout_init']) && ! empty($data['layout_init']['columnDefs'])) {
            $data['columnDefs'] = $data['layout_init']['columnDefs'];
        } else {
            $data['columnDefs'] = $grid->getColumns();
        }

        $data['row_tooltips'] = collect($data['columnDefs'])->where('row_tooltip', 1)->count();

        $data['rowClassRules'] = [];
        foreach ($data['columnDefs'] as $colDef) {
            if (! empty($colDef['rowClassRules'])) {
                foreach ($colDef['rowClassRules'] as $k => $v) {
                    $data['rowClassRules'][$k] = $v;
                }
            }
        }

        $data['rowClassRules'] = (object) $data['rowClassRules'];

        $data['report_access'] = get_menu_access_from_module(488);
        $data['layout_access'] = get_menu_access_from_module(526);
        $data['module_fields_access'] = get_menu_access_from_module(749);
        // if(is_dev()){
        //  }
        $fields_module = \DB::connection('default')->table('erp_cruds')->where('id', 749)->get()->first();
        $data['fields_module_title'] = ucwords(str_replace('_', ' ', $fields_module->name));
        $data['fields_module_description'] = str_replace([PHP_EOL, "'"], ['<br>', ''], $fields_module->form_description);

        $data['form_description'] = '';

        $data['condition_styles_access'] = get_menu_access_from_module(761);

        $params = [
            'search' => '',
            'fstart' => 0,
            'flimit' => 'All',
            'gridsort' => '',
            'gridfilter' => '',
            'grid_layout_id' => '',
        ];

        $fields = $this->data['db_module_fields']->pluck('field')->toArray();
        $data['communications_panel'] = false;
        $data['communications_type'] = false;
        $data['show_statement_tab'] = false;
        if ($this->data['db_table'] == 'crm_accounts' || in_array('account_id', $fields)) {
            $data['communications_panel'] = true;
            $data['communications_type'] = 'account';
            $data['show_statement_tab'] = true;
        }

        if (in_array('domain_uuid', $fields) && $this->data['db_table'] != 'v_gateways') {
            $data['communications_panel'] = true;
            $data['communications_type'] = 'pbx';
        }

        if ($this->data['db_table'] != 'crm_products' && ($this->data['db_table'] == 'crm_suppliers' || in_array('supplier_id', $fields))) {
            $data['communications_panel'] = true;
            $data['communications_type'] = 'supplier';
            $data['show_statement_tab'] = true;
        }

        $data['show_products_tab'] = false;
        $data['show_subscriptions_tab'] = false;
        $data['is_subscription_module'] = false;

        if (in_array($this->data['app_id'], [8, 12, 14]) || ($this->data['db_table'] == 'crm_accounts' || in_array('account_id', $fields))) {
            $data['show_subscriptions_tab'] = true;
            $data['is_subscription_module'] = true;
        }
        if (is_main_instance()) {
            $data['show_subscriptions_tab'] = true;
        }

        if ($this->data['db_table'] == 'crm_product_categories' || $this->data['db_table'] == 'crm_products' || in_array('product_id', $fields)) {
            $data['show_products_tab'] = true;
        }

        if (in_array('domain_uuid', $fields)) {
            $data['show_subscriptions_tab'] = true;
            $data['is_subscription_module'] = true;
            $data['show_products_tab'] = false;
        }

        //$data['sidebar_module_events'] = sidebar_get_module_events($this->data['module_id']);
        $data['sidebar_module_events_json'] = sidebar_get_module_events_json($this->data['module_id']);

        // sidebar counts
        $data['events_count'] = \DB::table('erp_form_events')->where('module_id', $this->data['module_id'])->count();

        // voice context menus
        if (is_main_instance() && session('role_level') == 'Admin') {
            $data['voice_basic_menu'] = \DB::table('erp_menu')->where('location', 'services_menu')->where('unlisted', 0)->where('parent_id', 7547)->get();
            $data['voice_business_menu'] = \DB::table('erp_menu')->where('location', 'services_menu')->where('unlisted', 0)->where('parent_id', 6387)->get();
            $data['voice_enterprise_menu'] = \DB::table('erp_menu')->where('location', 'services_menu')->where('unlisted', 0)->where('parent_id', 7617)->get();
        }

        $data['has_sort'] = false;
        $data['sort_field'] = 'sort_order';
        $data['currency_fields'] = false;
        if ($this->data['module_id'] != 499 && ! $data['serverside_model'] && session('role_level') == 'Admin') {
            foreach ($data['columnDefs'] as $i => $colDef) {
                if ($colDef['field'] == 'sort_order') {
                    $data['has_sort'] = true;
                }
            }
        }
        $data['allow_sorting'] = false;
        if ($data['has_sort']) {
            if (session('role_id') == 1) {
                $data['allow_sorting'] = true;
            }

            if (is_dev()) {
                $data['allow_sorting'] = true;
            }
            if (session('role_level') == 'Admin' && $this->data['allow_admin_sorting']) {
                $data['allow_sorting'] = true;
            }
        }

        $data['pinned_totals'] = false;

        $data['pinned_total_cols'] = [];
        if (session('role_level') == 'Admin') {
            foreach ($this->data['module_fields'] as $i => $colDef) {
                if (! empty($colDef['pinned_row_total'])) {
                    $data['pinned_totals'] = true;
                    $data['pinned_total_cols'][] = $colDef['field'];
                }
            }
        }

        //  if(is_dev())

        $view_file = 'grid';

        if (! empty($this->data['tree_data_field'])) {
            foreach ($data['columnDefs'] as $i => $c) {
                if (isset($data['columnDefs'][$i]['aggFunc'])) {
                    unset($data['columnDefs'][$i]['aggFunc']);
                }
                if (isset($data['columnDefs'][$i]['defaultAggFunc'])) {
                    unset($data['columnDefs'][$i]['defaultAggFunc']);
                }
            }

            //  $view_file = 'tree_grid';
        }

        $data['master_detail'] = false;

        if ($data['detail_module_id'] > 0 && ! empty($data['detail_module_key'])) {
            if (! empty(session('show_deleted'.$data['detail_module_id']))) {
                session(['show_deleted'.$data['detail_module_id'] => 0]);
            }
            $sub_grid_url = get_menu_url($data['detail_module_id']);
            $sub_model = new ErpModel;
            $sub_model->setMenuData('detailmodule_'.$data['detail_module_id']);

            $sub_model_data = $sub_model->info;

            $grid_id = 'detail'.$grid_id;

            $sub_model_data['grid_id'] = $grid_id;
            $sub_grid = new \ErpGrid($sub_model_data);

            $detail_grid = $sub_grid->getGrid();

            $master_menu_name = $data['menu_name'];

            $fields = $sub_model_data['db_module_fields']->pluck('field')->toArray();
            if ($sub_model_data['db_table'] == 'crm_accounts' || in_array('account_id', $fields)) {
                $data['communications_panel'] = true;
                $data['communications_type'] = 'account';
            }

            if (! empty($data['layout_init']) && ! empty($data['layout_init']['detail_col_defs'])) {
                $data['detail_col_defs'] = $data['layout_init']['detail_col_defs'];
            } else {
                $data['detail_col_defs'] = $sub_grid->getColumns();
            }

            $data['detail_menu_route'] = 'detailmodule_'.$data['detail_module_id'];
            $data['detail_menu_name'] = $sub_model_data['menu_name'];
            $data['detail_grid']['kb_name'] = $sub_model_data['menu_name'];

            $data['master_detail'] = true;

            $data['detail_grid'] = $detail_grid;
            $data['detail_grid']['form_description'] = '';
            $data['detail_grid']['condition_styles_templates'] = $data['condition_styles_templates'];

            $data['detail_grid']['master_soft_delete'] = $data['soft_delete'];

            $data['detail_grid']['pinned_totals'] = false;

            $data['detail_grid']['row_tooltips'] = collect($data['detail_col_defs'])->where('row_tooltip', 1)->count();

            $data['detail_grid']['pinned_total_cols'] = [];

            if (session('role_level') == 'Admin') {
                foreach ($sub_model_data['module_fields'] as $i => $colDef) {
                    if (! empty($colDef['pinned_row_total'])) {
                        $data['detail_grid']['pinned_totals'] = true;
                        $data['detail_grid']['pinned_total_cols'][] = $colDef['field'];
                    }
                }
            }

            $data['detail_grid']['show_linked_modules'] = false;
            $linked_modules = \Erp::getLinkedModules($sub_model_data);
            if (count($linked_modules) > 0) {
                $data['detail_grid']['show_linked_modules'] = true;
                $data['detail_grid']['linked_modules'] = $linked_modules;
            }

            $data['detail_grid']['has_sort'] = false;
            $data['detail_grid']['sort_field'] = 'sort_order';
            if ($sub_model_data['module_id'] != 499 && ! $sub_model_data['serverside_model'] && session('role_level') == 'Admin') {
                foreach ($data['detail_col_defs'] as $i => $colDef) {
                    if ($colDef['field'] == 'sort_order') {
                        $data['detail_grid']['has_sort'] = true;
                        $data['detail_grid']['sort_field'] = 'sort_order';
                    }
                }
            }

            if (empty($data['detail_grid']['menu_name']) && ! empty($data['detail_grid']['layout_title'])) {
                $data['detail_grid']['menu_name'] = $data['detail_grid']['layout_title'];
            }

            $data['detail_grid']['master_module_key'] = $data['detail_module_key'];
            $data['detail_grid']['master_grid_id'] = $data['grid_id'];
            $data['master_grid_id'] = $data['grid_id'];
            $data['detail_grid']['master_module_id'] = $data['module_id'];
            $data['detail_grid']['grid_id'] = 'detail'.$data['grid_id'];
            $data['detail_grid']['master_grid_title'] = $data['menu_name'];
            $data['detail_grid']['master_menu_name'] = $master_menu_name;

            $data['detail_grid']['cell_styles'] = $sub_model_data['module_styles']->where('module_id', $data['detail_module_id'])->where('whole_row', 0);
            $data['detail_grid']['row_styles'] = $sub_model_data['module_styles']->where('module_id', $data['detail_module_id'])->where('whole_row', 1);

            $detail_menu_params = [
                'app_id' => $sub_model_data['app_id'],
                'module_id' => $sub_model_data['module_id'],
                'menu_id' => isset($sub_model_data['menu_id']) ? $sub_model_data['menu_id'] : '',
                'connection' => $sub_model_data['connection'],
            ];

            $data['detail_grid']['grid_menu_menu'] = \ErpMenu::build_menu('grid_menu', $detail_menu_params, $data['detail_module_id']);

            $data['detail_grid']['status_dropdown'] = get_status_dropdown($data['detail_module_id']);

            if (session('role_level') == 'Admin') {
                $data['detail_grid']['related_items_menu_menu'] = \ErpMenu::build_menu('related_items_menu', $detail_menu_params, $data['detail_module_id']);
            }
            $data['detail_grid']['adminbtns_menu'] = \ErpMenu::build_menu('module_actions', $detail_menu_params, $data['detail_module_id']);
            if (session('role_level') == 'Admin') {
                //$data['detail_grid']['grid_menu_context'] =  \ErpMenu::getAggridContextMenu($data['detail_module_id'],$detail_menu_params);

                $has_status_field = collect($sub_model_data['module_fields'])->where('field', 'status')->count();
                if ($has_status_field) {
                    $opt_values = collect($sub_model_data['module_fields'])->where('field', 'status')->first();
                    $opt_values = $opt_values['opts_values'];

                    $opt_values = collect(explode(',', $opt_values))->unique()->filter()->toArray();
                    $opt_values = array_diff($opt_values, ['Deleted']);

                    $opt_values = collect($opt_values)->unique()->filter()->toArray();
                    $data['detail_grid']['context_statuses'] = $opt_values;
                }
            }

            $data['detail_grid']['has_cell_editing'] = 0;
            if (session('role_level') == 'Admin') {
                $data['detail_grid']['has_cell_editing'] = collect($sub_model_data['module_fields'])->where('cell_editing', 1)->count();
            }

            if (session('role_level') == 'Admin') {
                $data['detail_grid']['module_context_builder_menu'] = \ErpMenu::build_menu('context_builder', $detail_menu_params);

                foreach ($data['detail_grid']['module_context_builder_menu'] as $i => $m) {
                    if ($m->id == 'menuitem_1234') {
                        unset($data['detail_grid']['module_context_builder_menu'][$i]);
                    }
                }
                $data['detail_grid']['module_context_builder_menu'] = array_values($data['detail_grid']['module_context_builder_menu']);

                $data['module_context_builder_menu'][] = ['text' => 'Detail Grid', 'title' => 'Detail Grid', 'items' => $data['detail_grid']['module_context_builder_menu']];
            }

            $data['detail_grid']['rowClassRules'] = [];
            foreach ($data['detail_col_defs'] as $colDef) {
                if (! empty($colDef['rowClassRules'])) {
                    foreach ($colDef['rowClassRules'] as $k => $v) {
                        $data['detail_grid']['rowClassRules'][$k] = $v;
                    }
                }
            }
            $data['detail_grid']['rowClassRules'] = (object) $data['detail_grid']['rowClassRules'];
            //if(is_dev()){dd($data['detail_grid']);}
        }

        //if (is_dev()) {
        // }

        if (! empty($request->iframe)) {
            $data['iframe'] = 1;
        }

        if (! empty($request->detail_field)) {
            $data['detail_field'] = $request->detail_field;
        }

        if (! empty($request->detail_value)) {
            $data['detail_value'] = $request->detail_value;
        }

        $data['default_grid_report_id'] = $default_grid_report_id;
        $data['dev_access'] = (check_access('1,31') || is_dev()) ? true : false;
        $data['date_filter_options'] = [
            'current day',
            'current week',
            'current month',
            'last hour',
            'last 3 hour',
            'last 6 hours',
            'last 12 hours',
            'first of current month',
            'previous month',
            'current month last year',
            'before six months ago',
            'last six months',
            'current year',
        ];

        if (! empty($this->data['tree_data_field'])) {
            foreach ($this->data['module_fields'] as $field) {
                if ($field['field'] == $this->data['tree_data_field']) {
                    $data['tree_data_header'] = $field['label'];
                }
            }
        }

        if (! empty(request()->session_calendar_user_id)) {
            session(['calendar_user_id' => request()->session_calendar_user_id]);
        }

        $data['cell_styles'] = $this->data['module_styles']->where('module_id', $this->data['module_id'])->where('whole_row', 0);
        $data['row_styles'] = $this->data['module_styles']->where('module_id', $this->data['module_id'])->where('whole_row', 1);
        $data['tree_data'] = false;

        // SETUP COMPARATORS FOR CUSTOM SORT

        if ($this->data['module_id'] == 1845 || $this->data['module_id'] == 760) {
            $data['menus_newtab'] = true;
        }

        $data['default_values'] = [];

        if (session('role_level') == 'Admin') {
            if (! empty(request()->report_id)) {
                $data['grid_report_id'] = request()->report_id;
            }

            if (! empty(request()->load_reports)) {
                $default_report = \DB::connection('default')->table('erp_reports')->where('module_id', $this->data['module_id'])->orderBy('default', 'desc')->orderBy('sort_order')->get()->first();
                if (! empty($default_report) && ! empty($default_report->id)) {
                    $data['grid_report_id'] = $default_report->id;
                    $data['grid_report_name'] = $default_report->name;
                }
            }
        }
        /*
        foreach($this->data['columnDefs'] as $colDef){
            foreach ($this->data['module_fields'] as $field) {
                if($field['default_value'] > ''){
                    if($field['field_type'] == 'select_module'){
                        // set default as text value
                        if(str_ends_with($field['default_value'],'_id')){
                            $session_key = str_replace('session_','',$field['default_value']);
                            $key = session($session_key);
                            foreach($this->data['columnDefs'] as $colDef){
                                if($colDef['field'] == )
                            }
                        }
                    }
                }
            }
        }
        */

        if ($this->data['module_id'] == 760 && is_dev()) {
            $report_datasources = [];
            $instances = \DB::connection('system')->table('erp_instances')->get();
            foreach ($instances as $instance) {
                $reports = \DB::connection($instance->db_connection)->table('erp_reports')->select('id as value', 'name as text')->get();
                $report_options = $reports->pluck('text')->toArray();

                $report_datasources[$instance->name] = ['filter_options' => $report_options, 'filter_datasources' => $reports];
            }
            $data['report_datasources'] = $report_datasources;
        }

        if ($data['grid_layout_id']) {
            $layout_data = $grid->getLayout($data['grid_layout_id']);

            if (! empty($layout_data) && ! empty($layout_data['layout']) && ! empty($layout_data['layout']->filterState)) {
                $data['init_filters'] = $layout_data['layout']->filterState;
                $data['init_layout_tracking'] = $layout_data['layout_tracking'];
            }
        }

        $data['show_linked_modules'] = false;
        $linked_modules = \Erp::getLinkedModules($this->data);

        if (count($linked_modules) > 0) {
            $data['show_linked_modules'] = true;
            $data['linked_modules'] = $linked_modules;
        }

        /// INITIAL DETAIL LAYOUT

        if ($data['master_detail']) {
            if (! empty($detail_grid['grid_layout_id']) && ! empty($detail_grid['layout_settings'])) {
                $detail_grid = $data['detail_grid'];
                foreach ($data['detail_col_defs'] as $i => $colDef) {
                    //$data['detail_col_defs'][$i]['resizable'] = false;
                    if ($colDef['field'] == 'sort_order') {
                        // remove row pinning
                        $data['detail_col_defs'][$i]['pinned'] = false;
                    }
                }

                $coldefs = collect($data['detail_col_defs']);

                $coldefs_visible = $coldefs->where('hide', false)->count();

                if ($coldefs_visible < 3) {
                    foreach ($data['detail_col_defs'] as $i => $def) {
                        $data['detail_col_defs'][$i]['hide'] = false;
                    }
                }

                $settings = $detail_grid['layout_settings']['layout'];
                if (! empty($settings) && ! empty($settings->colState)) {
                    foreach ($settings->colState as $colIndex => $col) {
                        if (empty($col->sort)) {
                            $settings->colState[$colIndex]->sort = null;
                        }
                    }
                }
                $detail_grid['layout_init'] = ['name' => $detail_grid['layout_title'], 'columnDefs' => $data['detail_col_defs'], 'layout_tracking' => $detail_grid['layout_settings']['layout_tracking'], 'auto_group_col_sort' => $detail_grid['layout_settings']['auto_group_col_sort'],  'settings' => json_encode($settings)];

                $detail_grid['sidebar_layouts'] = $data['sidebar_layouts'];

                $detail_grid['layout_menu_route'] = $data['menu_route'];
                $data['detail_grid'] = $detail_grid;
            }
        }

        $data['layout_menu_route'] = $data['menu_route'];

        if (! empty($request->layout_id)) {
            $data['layout_name'] = $data['layout_title'];
        }
        $datefield_datasource = [
            ['text' => 'None', 'value' => ''],
            ['text' => 'Current Day', 'value' => 'currentDay'],
            ['text' => 'Not Current Day', 'value' => 'notCurrentDay'],
            ['text' => 'Current Day and before', 'value' => 'lessEqualToday'],
            ['text' => 'Current Day and after', 'value' => 'greaterEqualToday'],
            ['text' => 'Current Week', 'value' => 'currentWeek'],
            ['text' => 'Current Month', 'value' => 'currentMonth'],
            ['text' => 'Current Year', 'value' => 'currentYear'],
            ['text' => 'Current Month Last Year', 'value' => 'currentMonthLastYear'],
            ['text' => 'Current Month Last Three Years', 'value' => 'currentMonthLastThreeYears'],
            ['text' => 'Next month and before', 'value' => 'previoulessEqualNextMonthsDay'],
            ['text' => 'Last Day', 'value' => 'previousDay'],
            ['text' => 'Last Week Day', 'value' => 'previousWeekDay'],
            ['text' => 'Last Month', 'value' => 'lastMonth'],
            ['text' => 'Last Month Last Three Years', 'value' => 'lastMonthLastThreeYears'],
            ['text' => 'Last Three Days', 'value' => 'lastThreeDays'],
            ['text' => 'Last Seven Days', 'value' => 'lastSevenDays'],
            ['text' => 'Last Month', 'value' => 'lastMonth'],
            ['text' => 'Last Three Months', 'value' => 'lastThreeMonths'],
            ['text' => 'Last Six Months', 'value' => 'lastSixMonths'],
            ['text' => 'Last Twelve Months', 'value' => 'lastTwelveMonths'],
            ['text' => 'Not Current Month', 'value' => 'notCurrentMonth'],
            ['text' => 'Not Last Three Days', 'value' => 'notlastThreeDays'],
            ['text' => 'Not Last Seven Days', 'value' => 'notlastSevenDays'],
            ['text' => 'Not Last Thirty Five Days', 'value' => 'notlastThirtyFiveDays'],
            ['text' => 'Not Last Thirty Days', 'value' => 'notlastThirtyDays'],
            ['text' => 'Not Last Sixty Days', 'value' => 'notlastSixtyDays'],
        ];
        $data['layout_field_filters'] = [];
        $fields = collect($this->data['module_fields'])->pluck('field')->toArray();
        $field_list = collect($this->data['module_fields'])->where('show_grid_filter', 1);
        foreach ($field_list as $field) {
            $field = (object) $field;
            if ($field->field_type == 'date' || $field->field_type == 'datetime') {
                $ds = $datefield_datasource;
            } else {
                $ds = get_module_field_options($this->data['module_id'], $field->field, false, true);
            }
            if ($ds && is_countable($ds) && count($ds) > 0) {
                $field_filter = (object) [
                    'field' => $field->field,
                    'field_type' => $field->field_type,
                    'label' => $field->label,
                    'ds' => $ds,
                ];
                $data['layout_field_filters'][] = $field_filter;
            }
        }

        $data['hide_toolbar_items'] = 0;
        if (! empty($request->hide_toolbar_items)) {
            $data['hide_toolbar_items'] = 1;
        }

        if ($this->data['module_id'] == 1944) {
            $data['user_stats'] = [];
            $users = \DB::connection('system')->table('erp_users')->where('id', '!=', 1)->where('account_id', 1)->where('is_deleted', 0)->get();
            $roles = \DB::connection('system')->table('erp_user_roles')->where('level', 'Admin')->orderBy('sort_order')->get();

            $data['roles'] = $roles;
            $current_tasks = get_staff_current_tasks();
        }

        //  if(is_dev()){
        //  $view_file = 'grid_dev';
        //  }

        if ($data['serverside_model']) {
            if ($request->return_view_data) {
                return $data;
            }

            return view('__app.grids.'.$view_file, $data);
        } else {
            $this->model->setMenuData($this->data['menu_route']);
            if (! empty(request()->id)) {
                session(['show_deleted'.$this->data['module_id'] => 1]);
            } else {
                session(['show_deleted'.$this->data['module_id'] => 0]);
            }
            $sql = $this->model->getClientSql($request);

            $sql_conn = $data['connection'];

            //$row_data = Cache::rememberForever(session('instance')->id.'row_data'.$this->data['db_table'].'_'.session('user_id'), function() use ($sql,$sql_conn) {
            //    return \DB::connection($sql_conn)->select($sql);
            //});

            // dddd($sql);
            $row_data = \DB::connection($sql_conn)->select($sql);
            $data['row_data'] = $row_data;
            $field_names = collect($this->data['module_fields'])->pluck('field')->toArray();
            if (in_array('product_id', $field_names) || in_array('product_category_id', $field_names)) {
                //$data['row_data'] = sort_product_rows($data['row_data']);
            }
            $data['row_data'] = $grid->formatAgGridData($data['row_data']);
            //dd($data['row_data'][0]);
            //set tree data field unique

            if (! empty($this->data['tree_data_key']) && ! empty($this->data['tree_data_field'])) {
                $data['tree_data'] = true;
                $data['row_data'] = rowdata_hierarchy($data['row_data'], $this->data['module_id']);
            }
            if (is_dev()) {
            }
            $data['hide_toolbar_items'] = 0;
            if (! empty($request->hide_toolbar_items)) {
                $data['hide_toolbar_items'] = 1;
            }

            if (isset($return_data)) {
                return $data;
            }
            if ($request->return_view_data) {
                return $data;
            }
            if (is_dev()) {
                //dd($data);
                //$view_file = 'dev.grid';
            }
            if (is_dev()) {
                //  return view('velzon.grid', $data);
                //dd($data['columnDefs']);
            }

            if (session('role_level') != 'Admin') {
                $data['has_sort'] = 0;
                if (isset($data['detail_grid'])) {
                    $data['detail_grid']['has_sort'] = 0;
                }
            }

            if ($this->data['module_id'] == 1944 && ! is_superadmin()) {
                $data['has_sort'] = 0;
                if (isset($data['detail_grid'])) {
                    $data['detail_grid']['has_sort'] = 0;
                }
            }

            $data['has_cell_editing'] = 0;
            if (session('role_level') == 'Admin') {
                $data['has_cell_editing'] = collect($this->data['module_fields'])->where('cell_editing', 1)->count();
            }

            if (is_dev()) {
                //  $data['has_cell_editing']  = 1;
            }

            $display_field = $this->data['db_module_fields']->where('display_field', 1)->pluck('field')->first();
            if (! $display_field) {
                $display_field = $this->data['db_module_fields']->where('field', 'name')->pluck('field')->first();
            }
            if (! $display_field) {
                $display_field = $this->data['db_module_fields']->where('field', 'title')->pluck('field')->first();
            }
            if (! $display_field) {
                $display_field = $this->data['module']->tree_data_field;
            }
            if (! $display_field) {
                $display_field = $this->data['db_key'];
            }
            $data['primary_field_name'] = $display_field;
            if (empty($data['primary_field_name'])) {
                $data['primary_field_name'] = $this->data['db_key'];
            }

            return view('__app.grids.'.$view_file, $data);
        }
    }

    public function miniGrid(Request $request)
    {
        if (! empty($request->tab_load)) {
            $request->request->remove('tab_load');
        }

        session()->forget('form_builder_redirect');

        if (! empty(session('show_deleted'.$this->data['module_id']))) {
            session(['show_deleted'.$this->data['module_id'] => 0]);
        }
        if (! empty(request()->id)) {
            session(['show_deleted'.$this->data['module_id'] => 1]);
        }

        $erp = new \DBEvent($this->data['module_id']);
        $erp->processOnLoad();

        try {
            \DB::connection($this->data['connection']);
        } catch (\Throwable $ex) {
            exception_log($ex);
            abort(500, 'DB connection error');
        }
        update_recent_modules($this->data['module_id']);
        $default_grid_report_id = \DB::connection('default')->table('erp_reports')->where('module_id', $this->data['module_id'])->where('default', 1)->pluck('id')->first();
        if (empty($default_grid_report_id)) {
            // $default_grid_report_id = create_default_report($this->data['module_id']);
        }

        if (! empty($request->app_id)) {
            session(['app_id_lookup' => $request->app_id]);
        } else {
            session()->forget('app_id_lookup');
        }

        if (! $this->data['access']['is_menu']) {
            $response = [
                'status' => 'error',
                'message' => 'No List Access',
            ];

            if (request()->ajax()) {
                return response()->json($response);
            } else {
                if (! empty(session('is_api_session'))) {
                    return 'No Access';
                }

                return redirect()->back()->with($response);
            }
        }

        if (! empty($request->module_id) && ($this->data['db_table'] == 'erp_module_fields')) {
            update_module_config_from_schema($request->module_id);
        } else {
            if (! $this->data['serverside_model']) {
                try {
                    $sql = $this->model->getClientSql($request);
                    $data = \DB::connection($this->data['connection'])->select($sql.' limit 1');
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    update_module_config_from_schema($this->data['module_id']);
                }
            }
        }

        $grid_id = $this->data['module_id'].'_'.rand().date('U');
        // aa('tabload');
        //  aa($this->data['module_id']);
        // aa($grid_id);
        $this->data['grid_id'] = $grid_id;
        $this->data['master_grid_id'] = $grid_id;
        $grid = new \ErpGrid($this->data, $request->all());
        $data = $grid->getGrid();

        $branding_logo = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', session('parent_id'))->pluck('logo')->first();

        if (file_exists(uploads_settings_path().$branding_logo)) {
            $branding_logo = settings_url().$branding_logo;
        }

        if (! $branding_logo) {
            $branding_logo = '';
        }
        //$pbx_logo = \DB::connection('default')->table('erp_instances')->where('id', session('instance')->id)->pluck('pbx_logo')->first();
        //if($pbx_logo){
        //$pbx_logo = 'https://'.session('instance')->domain_name .'/uploads/'.session('instance')->directory.'/305/'.$pbx_logo;
        //}
        //if(!empty($pbx_logo) && !empty($pbx_admin_menu_menu) && count($pbx_admin_menu_menu) > 0){
        //  $branding_logo = $pbx_logo;
        //}

        // charts

        $data['branding_logo'] = $branding_logo;

        if (! $data['serverside_model']) {
            $count = $this->model->getTotalCount();
            if ($count > 100000) {
                $data['serverside_model'] = 1;
                \DB::connection('default')->table('erp_cruds')->where('id', $this->data['module_id'])->update(['serverside_model' => 1]);
            }
        }

        if (! empty($request->remove_container)) {
            $data['remove_container'] = 1;
        }
        if (! empty($request->hide_toolbar)) {
            $data['hide_toolbar'] = 1;
        }

        if (str_contains($this->data['connection'], 'pbx')) {
            if (session('role_level') == 'Customer' && empty(session('service_account_domain_uuid'))) {
                $response = [
                    'status' => 'error',
                    'message' => 'No List Access',
                ];

                if (request()->ajax()) {
                    return response()->json($response);
                } else {
                    return redirect()->to('/')->with($response);
                }
            }
            // $favicon = \DB::connection('default')->table('erp_menu')->where('location', 'pbx')->pluck('favicon')->first();

            $data['is_pbx'] = true;
        } elseif (! empty($this->data['menu']->favicon)) {
            $data['favicon'] = uploads_url(499).$this->data['menu']->favicon;
            $data['is_pbx'] = false;
        }
        /*
        if(is_main_instance()){
        if (!empty($this->data['menu']->menu_icon)) {
            if(str_contains($this->data['menu']->menu_icon,'fab ')){
                $data['favicon'] = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/svgs/brands/'.str_replace(['fa-','fas ','fab ','far '],'',$this->data['menu']->menu_icon).'.svg';
            }else{
                $data['favicon'] = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/svgs/solid/'.str_replace(['fa-','fas ','fab ','far '],'',$this->data['menu']->menu_icon).'.svg';
            }
        }
        }
        */
        $cols = get_columns_from_schema($data['db_table']);
        $data['status_btn'] = false;
        if (in_array('status', $cols)) {
            $data['status_btn'] = true;
        }

        if ($this->data['db_table'] == 'crm_documents' && ! empty($request->id)) {
            $doc = \DB::table('crm_documents')->where('id', $request->id)->get()->first();
            $link = generate_paynow_link($doc->account_id, $doc->total, true);
            if ($link && $doc->total > 0 && ($doc->doctype == 'Tax Invoice' || $doc->doctype == 'Order') && $doc->payment_status != 'Complete') {
                if (session('role_id') > 10) { // dont show paynow popup for admin
                    $data['payment_popup'] = 'document_popup/'.$request->id;
                }
            }
        }

        $data['module_urls'] = app('erp_config')['modules']->pluck('slug', 'id')->toArray();

        $data['cashregister_url'] = get_menu_url_from_table('acc_cashbook_transactions');
        $data['documents_url'] = get_menu_url_from_table('crm_documents');
        $data['pricelist_items_url'] = get_menu_url_from_table('crm_pricelist_items');
        $data['supplier_documents_url'] = get_menu_url_from_table('crm_supplier_documents');
        $data['supplier_payments_url'] = get_menu_url_from_table('acc_payment_suppliers');
        $data['accounts_url'] = get_menu_url_from_module_id(343);
        $data['debtors_url'] = get_menu_url_from_module_id(343);
        $data['opportunities_url'] = get_menu_url_from_module_id(1923);
        $data['users_url'] = get_menu_url_from_table('erp_users');
        $data['communications_url'] = get_menu_url_from_table('erp_communication_lines');
        $data['call_history_url'] = get_menu_url_from_table('erp_call_history');
        $data['subscriptions_url'] = get_menu_url_from_table('sub_services');
        $data['suppliers_url'] = get_menu_url_from_table('crm_suppliers');
        $data['layouts_url'] = get_menu_url_from_table('erp_grid_views');
        $data['forms_url'] = get_menu_url_from_table('erp_forms');
        $data['reports_url'] = get_menu_url_from_table('erp_reports');
        $data['crud_url'] = get_menu_url_from_table('erp_cruds');
        $data['accounts_contact_url'] = get_menu_url_from_module_id(1810);
        $data['suppliers_contact_url'] = get_menu_url_from_module_id(1811);
        $data['guides_url'] = get_menu_url_from_module_id(1875);

        $data['module_fields_url'] = get_menu_url_from_table('erp_module_fields');
        $data['condition_styles_url'] = get_menu_url_from_table('erp_grid_styles');
        $data['condition_styles_templates'] = array_keys(get_site_colors_templates());
        $data['condition_styles_templates'][] = 'None';

        $data['account_contacts_url'] = get_menu_url_from_table('erp_users');
        $data['supplier_contacts_url'] = get_menu_url_from_table('crm_supplier_contacts');
        $data['module_log_url'] = get_menu_url_from_table('erp_module_log');

        $data['sms_panel_url'] = get_menu_url_from_table('isp_sms_messages');
        $data['hosting_panel_url'] = get_menu_url_from_table('isp_host_websites');
        $data['fibre_panel_url'] = get_menu_url_from_table('isp_data_fibre');
        $data['ip_panel_url'] = get_menu_url_from_table('isp_data_ip_ranges');
        $data['pbx_panel_url'] = get_menu_url_from_module_id(539);
        $data['kb_url'] = get_menu_url_from_table('crm_training_guides');
        $data['menu_manager_url'] = get_menu_url_from_table('erp_menu');

        if (session('instance')->directory == 'eldooffice') {
            $data['rentals_url'] = get_menu_url_from_table('crm_rental_leases');
        }

        if ($data['module_id'] == 200) {
            $data['available_pbx_numbers'] = get_available_pbx_phone_numbers();
        }

        $menu_params = [
            'app_id' => $data['app_id'],
            'module_id' => $data['module_id'],
            'menu_route' => $data['menu_route'],
            'menu_id' => $data['menu_id'],
            'connection' => $data['connection'],
        ];

        // $field_list = $this->data['db_module_fields']->pluck('field')->toArray();
        $data['grid_menu_menu'] = \ErpMenu::build_menu('grid_menu', $menu_params, $data['module_id']);

        $data['status_dropdown'] = get_status_dropdown($data['module_id']);

        if (session('role_level') == 'Admin') {
            $data['related_items_menu_menu'] = \ErpMenu::build_menu('related_items_menu', $menu_params, $data['module_id']);
        }
        $data['adminbtns_menu'] = \ErpMenu::build_menu('module_actions', $menu_params, $data['module_id']);

        if (session('role_level') == 'Admin') {
            // $data['grid_menu_context'] =  \ErpMenu::getAggridContextMenu($data['module_id'],$menu_params);

            $has_status_field = collect($this->data['module_fields'])->where('field', 'status')->count();
            if ($has_status_field) {
                $opt_values = collect($this->data['module_fields'])->where('field', 'status')->first();
                $opt_values = $opt_values['opts_values'];

                $opt_values = collect(explode(',', $opt_values))->unique()->filter()->toArray();
                $opt_values = array_diff($opt_values, ['Deleted']);

                $opt_values = collect($opt_values)->unique()->filter()->toArray();
                $data['context_statuses'] = $opt_values;
            }
        }

        if (session('role_level') == 'Admin') {
            $data['module_context_builder_menu'] = \ErpMenu::build_menu('context_builder', $menu_params);
        }

        if (! empty($request->from_iframe)) {
            $data['hide_page_header'] = 1;
            $data['remove_container'] = 1;
            $data['is_iframe'] = 1;
            $data['iframe'] = 1;
            $data['module_builder_menu'] = null;
        }

        if (! empty(request()->account_id)) {
            $ref_account = \DB::connection('default')->table('crm_accounts')->where('id', request()->account_id)->pluck('company')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            }
        }

        if (! empty(request()->reseller_user)) {
            $ref_account = \DB::connection('default')->table('crm_accounts')->where('id', request()->reseller_user)->pluck('company')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            }
        }

        if (! empty(request()->partner_id)) {
            $ref_account = \DB::connection('default')->table('crm_accounts')->where('id', request()->partner_id)->pluck('company')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            }
        }

        if (! empty(request()->domain_uuid)) {
            $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request()->domain_uuid)->pluck('domain_name')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            } else {
                $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_name', request()->domain_uuid)->pluck('domain_name')->first();
                if (! empty($ref_account)) {
                    $data['modal_ref'] = $ref_account;
                }
            }
        }

        if (! empty(request()->realm)) {
            $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request()->realm)->pluck('domain_name')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            } else {
                $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_name', request()->realm)->pluck('domain_name')->first();
                if (! empty($ref_account)) {
                    $data['modal_ref'] = $ref_account;
                }
            }
        }

        if (! empty(request()->user_context)) {
            $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', request()->user_context)->pluck('domain_name')->first();
            if (! empty($ref_account)) {
                $data['modal_ref'] = $ref_account;
            } else {
                $ref_account = \DB::connection('pbx')->table('v_domains')->where('domain_name', request()->user_context)->pluck('domain_name')->first();
                if (! empty($ref_account)) {
                    $data['modal_ref'] = $ref_account;
                }
            }
        }
        if ($data['modal_ref']) {
            $data['note'] = $data['modal_ref'];
        }

        if ($this->data['db_table'] == 'call_records_outbound_lastmonth' && ! empty(session('cdr_archive_table'))) {
            $data['menu_name'] = ucwords(str_replace('_', ' ', session('cdr_archive_table')));
        }

        if (str_contains(request()->headers->get('referer'), 'token=')) {
            $parts = parse_url(request()->headers->get('referer'));

            parse_str($parts['query'], $query);
            $data['referer_token'] = $query['token'];
            $data['referer_url'] = $parts['scheme'].'://'.$parts['host'];
        }

        $data['doctypes'] = null;
        $data['check_doctype'] = false;
        $ledger_tables = \DB::connection('default')->table('acc_doctypes')->pluck('doctable')->unique()->toArray();
        if (in_array($this->data['db_table'], $ledger_tables)) {
            $data['doctypes'] = \DB::connection('default')->table('acc_doctypes')->get();
            $data['check_doctype'] = true;
        }

        if (! empty($data['grid_layout_id']) && ! empty($data['layout_settings'])) {
            $data['layout_init'] = $this->aggridLayoutData($data['grid_layout_id'], $this->data['grid_id'], true);
            //dddd($data['layout_init']);
        }
        if (! empty($data['layout_init']) && ! empty($data['layout_init']['columnDefs'])) {
            $data['columnDefs'] = $data['layout_init']['columnDefs'];
        } else {
            $data['columnDefs'] = $grid->getColumns();
        }

        // if(is_dev()){
        //  }

        $data['rowClassRules'] = [];
        foreach ($data['columnDefs'] as $colDef) {
            if (! empty($colDef['rowClassRules'])) {
                foreach ($colDef['rowClassRules'] as $k => $v) {
                    $data['rowClassRules'][$k] = $v;
                }
            }
        }

        $data['rowClassRules'] = (object) $data['rowClassRules'];

        $data['report_access'] = get_menu_access_from_module(488);
        $data['layout_access'] = get_menu_access_from_module(526);
        $data['module_fields_access'] = get_menu_access_from_module(749);
        // if(is_dev()){
        //  }
        $fields_module = \DB::connection('default')->table('erp_cruds')->where('id', 749)->get()->first();
        $data['fields_module_title'] = ucwords(str_replace('_', ' ', $fields_module->name));
        $data['fields_module_description'] = str_replace([PHP_EOL, "'"], ['<br>', ''], $fields_module->form_description);

        $data['form_description'] = '';

        $data['condition_styles_access'] = get_menu_access_from_module(761);

        $params = [
            'search' => '',
            'fstart' => 0,
            'flimit' => 'All',
            'gridsort' => '',
            'gridfilter' => '',
            'grid_layout_id' => '',
        ];

        $fields = $this->data['db_module_fields']->pluck('field')->toArray();
        $data['communications_panel'] = false;
        $data['communications_type'] = false;
        if ($this->data['db_table'] == 'crm_accounts' || in_array('account_id', $fields)) {
            $data['communications_panel'] = true;
            $data['communications_type'] = 'account';
        }

        if (in_array('domain_uuid', $fields) && $this->data['db_table'] != 'v_gateways') {
            $data['communications_panel'] = true;
            $data['communications_type'] = 'pbx';
        }

        if ($this->data['db_table'] == 'crm_suppliers' || in_array('supplier_id', $fields)) {
            $data['communications_panel'] = true;
            $data['communications_type'] = 'supplier';
        }

        $data['has_sort'] = false;
        $data['sort_field'] = 'sort_order';
        $data['currency_fields'] = false;
        if ($this->data['module_id'] != 499 && ! $data['serverside_model'] && session('role_level') == 'Admin') {
            foreach ($data['columnDefs'] as $i => $colDef) {
                if ($colDef['field'] == 'sort_order') {
                    $data['has_sort'] = true;
                }
            }
        }
        $data['pinned_totals'] = false;

        $data['pinned_total_cols'] = [];
        if (session('role_level') == 'Admin') {
            foreach ($this->data['module_fields'] as $i => $colDef) {
                if (! empty($colDef['pinned_row_total'])) {
                    $data['pinned_totals'] = true;
                    $data['pinned_total_cols'][] = $colDef['field'];
                }
            }
        }

        //  if(is_dev())

        $view_file = 'grid';
        $data['master_detail'] = false;

        if ($data['detail_module_id'] > 0 && ! empty($data['detail_module_key'])) {
            if (! empty(session('show_deleted'.$data['detail_module_id']))) {
                session(['show_deleted'.$data['detail_module_id'] => 0]);
            }
            $sub_grid_url = get_menu_url($data['detail_module_id']);
            $sub_model = new ErpModel;
            $sub_model->setMenuData('detailmodule_'.$data['detail_module_id']);

            $sub_model_data = $sub_model->info;

            $grid_id = 'detail'.$grid_id;

            $sub_model_data['grid_id'] = $grid_id;
            $sub_grid = new \ErpGrid($sub_model_data);

            $detail_grid = $sub_grid->getGrid();

            $master_menu_name = $data['menu_name'];

            $fields = $sub_model_data['db_module_fields']->pluck('field')->toArray();
            if ($sub_model_data['db_table'] == 'crm_accounts' || in_array('account_id', $fields)) {
                $data['communications_panel'] = true;
                $data['communications_type'] = 'account';
            }

            if (! empty($data['layout_init']) && ! empty($data['layout_init']['detail_col_defs'])) {
                $data['detail_col_defs'] = $data['layout_init']['detail_col_defs'];
            } else {
                $data['detail_col_defs'] = $sub_grid->getColumns();
            }

            $data['detail_menu_route'] = 'detailmodule_'.$data['detail_module_id'];
            $data['detail_menu_name'] = $sub_model_data['menu_name'];
            $data['detail_grid']['kb_name'] = $sub_model_data['menu_name'];

            $data['master_detail'] = true;

            $data['detail_grid'] = $detail_grid;
            $data['detail_grid']['form_description'] = '';
            $data['detail_grid']['condition_styles_templates'] = $data['condition_styles_templates'];

            $data['detail_grid']['master_soft_delete'] = $data['soft_delete'];

            $data['detail_grid']['pinned_totals'] = false;

            $data['detail_grid']['pinned_total_cols'] = [];

            if (session('role_level') == 'Admin') {
                foreach ($sub_model_data['module_fields'] as $i => $colDef) {
                    if (! empty($colDef['pinned_row_total'])) {
                        $data['detail_grid']['pinned_totals'] = true;
                        $data['detail_grid']['pinned_total_cols'][] = $colDef['field'];
                    }
                }
            }

            $data['detail_grid']['show_linked_modules'] = false;
            $linked_modules = \Erp::getLinkedModules($sub_model_data);
            if (count($linked_modules) > 0) {
                $data['detail_grid']['show_linked_modules'] = true;
                $data['detail_grid']['linked_modules'] = $linked_modules;
            }

            $data['detail_grid']['has_sort'] = false;
            $data['detail_grid']['sort_field'] = 'sort_order';
            if ($sub_model_data['module_id'] != 499 && ! $sub_model_data['serverside_model'] && session('role_level') == 'Admin') {
                foreach ($data['detail_col_defs'] as $i => $colDef) {
                    if ($colDef['field'] == 'sort_order') {
                        $data['detail_grid']['has_sort'] = true;
                        $data['detail_grid']['sort_field'] = 'sort_order';
                    }
                }
            }

            if (empty($data['detail_grid']['menu_name']) && ! empty($data['detail_grid']['layout_title'])) {
                $data['detail_grid']['menu_name'] = $data['detail_grid']['layout_title'];
            }

            $data['detail_grid']['master_module_key'] = $data['detail_module_key'];
            $data['detail_grid']['master_grid_id'] = $data['grid_id'];
            $data['master_grid_id'] = $data['grid_id'];
            $data['detail_grid']['master_module_id'] = $data['module_id'];
            $data['detail_grid']['grid_id'] = 'detail'.$data['grid_id'];
            $data['detail_grid']['master_grid_title'] = $data['menu_name'];
            $data['detail_grid']['master_menu_name'] = $master_menu_name;

            $data['detail_grid']['cell_styles'] = $sub_model_data['module_styles']->where('module_id', $data['detail_module_id'])->where('whole_row', 0);
            $data['detail_grid']['row_styles'] = $sub_model_data['module_styles']->where('module_id', $data['detail_module_id'])->where('whole_row', 1);

            $detail_menu_params = [
                'app_id' => $sub_model_data['app_id'],
                'module_id' => $sub_model_data['module_id'],
                'menu_id' => $sub_model_data['menu_id'],
                'connection' => $sub_model_data['connection'],
            ];

            $data['detail_grid']['grid_menu_menu'] = \ErpMenu::build_menu('grid_menu', $detail_menu_params, $data['detail_module_id']);

            $data['detail_grid']['status_dropdown'] = get_status_dropdown($data['detail_module_id']);

            if (session('role_level') == 'Admin') {
                $data['detail_grid']['related_items_menu_menu'] = \ErpMenu::build_menu('related_items_menu', $detail_menu_params, $data['detail_module_id']);
            }

            $data['detail_grid']['adminbtns_menu'] = \ErpMenu::build_menu('module_actions', $detail_menu_params, $data['detail_module_id']);
            if (session('role_level') == 'Admin') {
                //$data['detail_grid']['grid_menu_context'] =  \ErpMenu::getAggridContextMenu($data['detail_module_id'],$detail_menu_params);

                $has_status_field = collect($sub_model_data['module_fields'])->where('field', 'status')->count();
                if ($has_status_field) {
                    $opt_values = collect($sub_model_data['module_fields'])->where('field', 'status')->first();
                    $opt_values = $opt_values['opts_values'];

                    $opt_values = collect(explode(',', $opt_values))->unique()->filter()->toArray();
                    $opt_values = array_diff($opt_values, ['Deleted']);

                    $opt_values = collect($opt_values)->unique()->filter()->toArray();
                    $data['detail_grid']['context_statuses'] = $opt_values;
                }
            }

            $data['detail_grid']['has_cell_editing'] = 0;
            if (session('role_level') == 'Admin') {
                $data['detail_grid']['has_cell_editing'] = collect($sub_model_data['module_fields'])->where('cell_editing', 1)->count();
            }

            if (session('role_level') == 'Admin') {
                $data['detail_grid']['module_context_builder_menu'] = \ErpMenu::build_menu('context_builder', $detail_menu_params);

                foreach ($data['detail_grid']['module_context_builder_menu'] as $i => $m) {
                    if ($m->id == 'menuitem_1234') {
                        unset($data['detail_grid']['module_context_builder_menu'][$i]);
                    }
                }
                $data['detail_grid']['module_context_builder_menu'] = array_values($data['detail_grid']['module_context_builder_menu']);

                $data['module_context_builder_menu'][] = ['text' => 'Detail Grid', 'title' => 'Detail Grid', 'items' => $data['detail_grid']['module_context_builder_menu']];
            }

            $data['detail_grid']['rowClassRules'] = [];
            foreach ($data['detail_col_defs'] as $colDef) {
                if (! empty($colDef['rowClassRules'])) {
                    foreach ($colDef['rowClassRules'] as $k => $v) {
                        $data['detail_grid']['rowClassRules'][$k] = $v;
                    }
                }
            }
            $data['detail_grid']['rowClassRules'] = (object) $data['detail_grid']['rowClassRules'];
            //if(is_dev()){dd($data['detail_grid']);}
        }

        //if (is_dev()) {
        // }

        if (! empty($request->iframe)) {
            $data['iframe'] = 1;
        }

        if (! empty($request->detail_field)) {
            $data['detail_field'] = $request->detail_field;
        }

        if (! empty($request->detail_value)) {
            $data['detail_value'] = $request->detail_value;
        }

        $data['default_grid_report_id'] = $default_grid_report_id;
        $data['dev_access'] = (check_access('1,31') || is_dev()) ? true : false;
        $data['date_filter_options'] = [
            'current day',
            'current week',
            'current month',
            'last hour',
            'last 3 hour',
            'last 6 hours',
            'last 12 hours',
            'first of current month',
            'previous month',
            'current month last year',
            'before six months ago',
            'last six months',
            'current year',
        ];

        if (! empty($this->data['tree_data_field'])) {
            foreach ($this->data['module_fields'] as $field) {
                if ($field['field'] == $this->data['tree_data_field']) {
                    $data['tree_data_header'] = $field['label'];
                }
            }
        }

        if (! empty(request()->session_calendar_user_id)) {
            session(['calendar_user_id' => request()->session_calendar_user_id]);
        }

        $data['cell_styles'] = $this->data['module_styles']->where('module_id', $this->data['module_id'])->where('whole_row', 0);
        $data['row_styles'] = $this->data['module_styles']->where('module_id', $this->data['module_id'])->where('whole_row', 1);
        $data['tree_data'] = false;

        // SETUP COMPARATORS FOR CUSTOM SORT

        if ($this->data['module_id'] == 1845 || $this->data['module_id'] == 760) {
            $data['menus_newtab'] = true;
        }

        $data['default_values'] = [];

        if (session('role_level') == 'Admin') {
            if (! empty(request()->report_id)) {
                $data['grid_report_id'] = request()->report_id;
            }

            if (! empty(request()->load_reports)) {
                $default_report = \DB::connection('default')->table('erp_reports')->where('module_id', $this->data['module_id'])->orderBy('default', 'desc')->orderBy('sort_order')->get()->first();
                if (! empty($default_report) && ! empty($default_report->id)) {
                    $data['grid_report_id'] = $default_report->id;
                    $data['grid_report_name'] = $default_report->name;
                }
            }
        }
        /*
        foreach($this->data['columnDefs'] as $colDef){
            foreach ($this->data['module_fields'] as $field) {
                if($field['default_value'] > ''){
                    if($field['field_type'] == 'select_module'){
                        // set default as text value
                        if(str_ends_with($field['default_value'],'_id')){
                            $session_key = str_replace('session_','',$field['default_value']);
                            $key = session($session_key);
                            foreach($this->data['columnDefs'] as $colDef){
                                if($colDef['field'] == )
                            }
                        }
                    }
                }
            }
        }
        */

        if ($this->data['module_id'] == 760 && is_dev()) {
            $report_datasources = [];
            $instances = \DB::connection('system')->table('erp_instances')->get();
            foreach ($instances as $instance) {
                $reports = \DB::connection($instance->db_connection)->table('erp_reports')->select('id as value', 'name as text')->get();
                $report_options = $reports->pluck('text')->toArray();

                $report_datasources[$instance->name] = ['filter_options' => $report_options, 'filter_datasources' => $reports];
            }
            $data['report_datasources'] = $report_datasources;
        }

        if ($data['grid_layout_id']) {
            $layout_data = $grid->getLayout($data['grid_layout_id']);

            if (! empty($layout_data) && ! empty($layout_data['layout']) && ! empty($layout_data['layout']->filterState)) {
                $data['init_filters'] = $layout_data['layout']->filterState;
                $data['init_layout_tracking'] = $layout_data['layout_tracking'];
            }
        }

        $data['show_linked_modules'] = false;
        $linked_modules = \Erp::getLinkedModules($this->data);

        if (count($linked_modules) > 0) {
            $data['show_linked_modules'] = true;
            $data['linked_modules'] = $linked_modules;
        }

        /// INITIAL DETAIL LAYOUT

        if ($data['master_detail']) {
            if (! empty($detail_grid['grid_layout_id']) && ! empty($detail_grid['layout_settings'])) {
                $detail_grid = $data['detail_grid'];
                foreach ($data['detail_col_defs'] as $i => $colDef) {
                    //$data['detail_col_defs'][$i]['resizable'] = false;
                    if ($colDef['field'] == 'sort_order') {
                        // remove row pinning
                        $data['detail_col_defs'][$i]['pinned'] = false;
                    }
                }

                $coldefs = collect($data['detail_col_defs']);

                $coldefs_visible = $coldefs->where('hide', false)->count();

                if ($coldefs_visible < 3) {
                    foreach ($data['detail_col_defs'] as $i => $def) {
                        $data['detail_col_defs'][$i]['hide'] = false;
                    }
                }

                $settings = $detail_grid['layout_settings']['layout'];
                if (! empty($settings) && ! empty($settings->colState)) {
                    foreach ($settings->colState as $colIndex => $col) {
                        if (empty($col->sort)) {
                            $settings->colState[$colIndex]->sort = null;
                        }
                    }
                }

                $detail_grid['layout_init'] = ['name' => $detail_grid['layout_title'], 'columnDefs' => $data['detail_col_defs'], 'layout_tracking' => $detail_grid['layout_settings']['layout_tracking'], 'auto_group_col_sort' => $detail_grid['layout_settings']['auto_group_col_sort'],  'settings' => json_encode($settings)];

                $detail_grid['sidebar_layouts'] = $data['sidebar_layouts'];

                $detail_grid['layout_menu_route'] = $data['menu_route'];
                $data['detail_grid'] = $detail_grid;
            }
        }

        $data['layout_menu_route'] = $data['menu_route'];

        if (! empty($request->layout_id)) {
            $data['layout_name'] = $data['layout_title'];
        }

        $datefield_datasource = [
            ['text' => 'None', 'value' => ''],
            ['text' => 'Current Day', 'value' => 'currentDay'],
            ['text' => 'Not Current Day', 'value' => 'notCurrentDay'],
            ['text' => 'Current Day and before', 'value' => 'lessEqualToday'],
            ['text' => 'Current Day and after', 'value' => 'greaterEqualToday'],
            ['text' => 'Current Week', 'value' => 'currentWeek'],
            ['text' => 'Current Month', 'value' => 'currentMonth'],
            ['text' => 'Current Year', 'value' => 'currentYear'],
            ['text' => 'Current Month Last Year', 'value' => 'currentMonthLastYear'],
            ['text' => 'Current Month Last Three Years', 'value' => 'currentMonthLastThreeYears'],

            ['text' => 'Next month and before', 'value' => 'previoulessEqualNextMonthsDay'],
            ['text' => 'Last Day', 'value' => 'previousDay'],
            ['text' => 'Last Week Day', 'value' => 'previousWeekDay'],
            ['text' => 'Last Month', 'value' => 'lastMonth'],
            ['text' => 'Last Month Last Three Years', 'value' => 'lastMonthLastThreeYears'],
            ['text' => 'Last Three Days', 'value' => 'lastThreeDays'],
            ['text' => 'Last Seven Days', 'value' => 'lastSevenDays'],
            ['text' => 'Last Month', 'value' => 'lastMonth'],
            ['text' => 'Last Three Months', 'value' => 'lastThreeMonths'],
            ['text' => 'Last Six Months', 'value' => 'lastSixMonths'],
            ['text' => 'Last Twelve Months', 'value' => 'lastTwelveMonths'],
            ['text' => 'Not Current Month', 'value' => 'notCurrentMonth'],
            ['text' => 'Not Last Three Days', 'value' => 'notlastThreeDays'],
            ['text' => 'Not Last Seven Days', 'value' => 'notlastSevenDays'],
            ['text' => 'Not Last Thirty Five Days', 'value' => 'notlastThirtyFiveDays'],
            ['text' => 'Not Last Thirty Days', 'value' => 'notlastThirtyDays'],
            ['text' => 'Not Last Sixty Days', 'value' => 'notlastSixtyDays'],
        ];
        $data['layout_field_filters'] = [];
        $fields = collect($this->data['module_fields'])->pluck('field')->toArray();
        $field_list = collect($this->data['module_fields'])->where('show_grid_filter', 1);
        foreach ($field_list as $field) {
            $field = (object) $field;
            if ($field->field_type == 'date' || $field->field_type == 'datetime') {
                $ds = $datefield_datasource;
            } else {
                $ds = get_module_field_options($this->data['module_id'], $field->field, false, true);
            }

            $field_filter = [
                'field' => $field->field,
                'field_type' => $field->field_type,
                'label' => $field->label,
                'ds' => $ds,
            ];
            $data['layout_field_filters'][] = $field_filter;
        }

        $data['hide_toolbar_items'] = 0;
        if (! empty($request->hide_toolbar_items)) {
            $data['hide_toolbar_items'] = 1;
        }

        if ($this->data['module_id'] == 1944) {
            $data['user_stats'] = [];
            $users = \DB::connection('system')->table('erp_users')->where('id', '!=', 1)->where('account_id', 1)->where('is_deleted', 0)->get();
            $roles = \DB::connection('system')->table('erp_user_roles')->where('level', 'Admin')->orderBy('sort_order')->get();

            $data['roles'] = $roles;
            $current_tasks = get_staff_current_tasks();
        }

        $view_file = 'mini_grid';
        if (! empty($request->chart_container)) {
            $data['chart_container'] = $request->chart_container;
        }
        if ($data['serverside_model']) {
            if ($request->return_view_data) {
                return $data;
            }

            return view('__app.grids.'.$view_file, $data);
        } else {
            $this->model->setMenuData($this->data['menu_route']);
            if (! empty(request()->id)) {
                session(['show_deleted'.$this->data['module_id'] => 1]);
            } else {
                session(['show_deleted'.$this->data['module_id'] => 0]);
            }
            $sql = $this->model->getClientSql($request);

            $sql_conn = $data['connection'];

            //$row_data = Cache::rememberForever(session('instance')->id.'row_data'.$this->data['db_table'].'_'.session('user_id'), function() use ($sql,$sql_conn) {
            //    return \DB::connection($sql_conn)->select($sql);
            //});

            // dddd($sql);
            $row_data = \DB::connection($sql_conn)->select($sql);
            $data['row_data'] = $row_data;
            $field_names = collect($this->data['module_fields'])->pluck('field')->toArray();
            if (in_array('product_id', $field_names) || in_array('product_category_id', $field_names)) {
                //$data['row_data'] = sort_product_rows($data['row_data']);
            }
            $data['row_data'] = $grid->formatAgGridData($data['row_data']);
            //dd($data['row_data'][0]);
            //set tree data field unique

            if (! empty($this->data['tree_data_key']) && ! empty($this->data['tree_data_field'])) {
                $data['tree_data'] = true;
                $data['row_data'] = rowdata_hierarchy($data['row_data'], $this->data['module_id']);
                //dd($data['row_data'][0]->hierarchy);
            }
            $data['hide_toolbar_items'] = 0;
            if (! empty($request->hide_toolbar_items)) {
                $data['hide_toolbar_items'] = 1;
            }

            if ($return_data) {
                return $data;
            }
            if ($request->return_view_data) {
                return $data;
            }

            if (session('role_level') != 'Admin') {
                $data['has_sort'] = 0;
                if (isset($data['detail_grid'])) {
                    $data['detail_grid']['has_sort'] = 0;
                }
            }

            if ($this->data['module_id'] == 1944 && ! is_superadmin()) {
                $data['has_sort'] = 0;
                if (isset($data['detail_grid'])) {
                    $data['detail_grid']['has_sort'] = 0;
                }
            }

            $data['has_cell_editing'] = 0;
            if (session('role_level') == 'Admin') {
                $data['has_cell_editing'] = collect($this->data['module_fields'])->where('cell_editing', 1)->count();
            }

            return view('__app.grids.'.$view_file, $data);
        }
    }

    public function kanban(Request $request, $route, $layout_id)
    {
        $kanban_settings = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();
        $data = $this->data;
        $mod_fields = collect($data['module_fields']);
        $data['layout_id'] = $layout_id;
        $data['kanban_col_field'] = $kanban_settings->kanban_column;
        $data['kanban_cols'] = explode(',', $kanban_settings->kanban_columns);
        $data['kanban_card_fields'] = $mod_fields->whereIn('field', explode(',', $kanban_settings->kanban_card_details));
        $data['kanban_card_title'] = $kanban_settings->kanban_card_title;
        $data['grid_id'] = $request->grid_id;

        $is_join_title = $mod_fields->where('field', $data['kanban_card_title'])->where('field_type', 'select_module')->count();
        if ($is_join_title) {
            $data['kanban_card_title'] = 'join_'.$data['kanban_card_title'];
        }

        return view('__app.grids.partials.kanban', $data);
    }

    public function kanbanData(Request $request)
    {
        $this->model->setMenuData($this->data['menu_route']);

        $layout = \DB::table('erp_grid_views')->where('id', $request->layout_id)->get()->first();
        $layout_state = json_decode($layout->aggrid_state);
        if (empty($layout_state->filterState)) {
            $filter_state = [];
        } else {
            $filter_state = (array) json_decode(json_encode($layout_state->filterState), true);
        }

        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');
        $request_object->request->add(['kanban_sql' => 1]);
        $request_object->request->add(['rowGroupCols' => []]);
        $request_object->request->add(['valueCols' => []]);
        $request_object->request->add(['groupKeys' => []]);

        if (! empty($request->where) && ! empty($request->where[0]) && ! empty($request->where[0]['value'])) {
            $request_object->request->add(['search' => $request->where[0]['value']]);
        }

        //   aa($filter_state);
        if ($filter_state) {
            foreach ($filter_state as $col => $state) {
                if ($state['filterType'] == 'set') {
                    foreach ($state['values'] as $i => $val) {
                        if (str_contains($val, ' - ')) {
                            $val_arr = explode(' - ', $val);

                            $filter_state[$col]['values'][$i] = $val_arr[0];
                        }
                    }
                }
            }

            //   aa($filter_state);
            $request_object->request->add(['filterModel' => $filter_state]);
        } else {
            $request_object->request->add(['filterModel' => []]);
        }
        if (! empty($layout_state->searchtext) && $layout_state->searchtext != ' ') {
            $request_object->request->add(['search' => $layout_state->searchtext]);
        }
        $sql = $this->model->getClientSql($request_object);
        $sql .= ' order by id asc';
        $sql_conn = $this->data['connection'];

        //$data = Cache::rememberForever(session('instance')->id.'row_data'.$this->data['db_table'].'_'.session('user_id'), function() use ($sql,$sql_conn) {
        // return \DB::connection($sql_conn)->select($sql);
        // });
        $data = \DB::connection($sql_conn)->select($sql);

        $grid = new \ErpGrid($this->data);
        $data = $grid->formatAgGridData($data);

        return response()->json($data);
    }

    public function kanbanUpdate(Request $request)
    {
        \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($request->keyColumn, $request->key)->update(['status' => $request->value['status']]);
    }

    public function linkedModules(Request $request)
    {
        if (! empty($request->selected_rows)) {
            $response = [];
            $id = $request->selected_rows[0][$this->data['db_key']];
            $row = (object) $this->model->getRow($id);

            return \Erp::getLinkedModules($this->data, $row);
        } else {
            $response = \Erp::getLinkedModules($this->data);
        }

        return response()->json($response);
    }

    public function aggridDetailData(Request $request)
    {
        // aa('aggridDetailData');
        $this->model->setMenuData($this->data['menu_route']);
        $sql = $this->model->getClientSql($request);

        $sql_conn = $this->data['connection'];
        //$data = Cache::rememberForever(session('instance')->id.'row_data'.$this->data['db_table'].'_'.session('user_id'), function() use ($sql,$sql_conn) {
        // return \DB::connection($sql_conn)->select($sql);
        // });
        $data = \DB::connection($sql_conn)->select($sql);
        $grid = new \ErpGrid($this->data);
        $data = $grid->formatAgGridData($data);

        return response()->json($data);
    }

    public function aggridDetailSearch(Request $request)
    {
        //  aa($request->all());
        $request->request->add(['search' => trim($request->search)]);

        $this->model->setMenuData($this->data['menu_route']);
        $sql = $this->model->getClientSql($request);

        //  aa($sql);
        $data = \DB::connection($this->data['connection'])->select($sql);
        //  aa($data);
        $values = collect($data)->pluck($request->search_key)->unique()->filter()->toArray();
        $values = array_values($values);

        // aa($request->search_key);
        // aa($values);
        return response()->json($values);
    }

    public function aggridRefreshData(Request $request, $return_data = false, $toggle_deleted_rows = false)
    {
        // clientside model refresh

        $erp = new \DBEvent($this->data['module_id']);
        $erp->processOnLoad();
        $this->model->setMenuData($this->data['menu_route']);

        if ($toggle_deleted_rows == 'yes') {
            $request->request->add(['show_deleted_rows' => 1]);
        }
        if ($toggle_deleted_rows == 'no') {
            $request->request->add(['hide_deleted_rows' => 1]);
        }
        $sql = $this->model->getClientSql($request);

        $data = \DB::connection($this->data['connection'])->select($sql);

        $grid = new \ErpGrid($this->data);

        //aa($sql);
        $data = $grid->formatAgGridData($data);

        if (! empty($this->data['tree_data_key']) && ! empty($this->data['tree_data_field'])) {
            $data = rowdata_hierarchy($data, $this->data['module_id']);
        }

        if ($return_data) {
            return $data;
        }

        return response()->json($data);
    }

    public function aggridRefreshRow(Request $request)
    {
        // clientside model refresh
        $erp = new \DBEvent($this->data['module_id']);
        $erp->processOnLoad();
        $this->model->setMenuData($this->data['menu_route']);
        $sql = $this->model->getClientSql($request);

        if (! empty($request->row_id)) {
            $sql_arr = explode(' where ', strtolower($sql));
            $query = $sql_arr[0].' where '.$this->data['db_table'].'.'.$this->data['db_key'].'="'.$request->row_id.'"';

            $data = \DB::connection($this->data['connection'])->select($query);
        } else {
            $data = \DB::connection($this->data['connection'])->select($sql);
        }
        $grid = new \ErpGrid($this->data);

        $data = $grid->formatAgGridData($data);

        return response()->json($data[0]);
    }

    public function aggridSidebarData(Request $request)
    {
        $data = [];
        $data['sidebar_layouts'] = \Erp::gridViews($this->data['menu_id'], $this->data['module_id'], $request->grid_id, $request->grid_layout_id);
        //aa($request->all());
        if ($request->grid_layout_id) {
            $current_layout = \DB::connection('default')->table('erp_grid_views')->where('id', $request->grid_layout_id)->get()->first();
            $data['name'] = $current_layout->name;
            $data['layout_type'] = $current_layout->layout_type;
            $data['track_layout'] = $current_layout->track_layout;
            $data['show_on_dashboard'] = $current_layout->show_on_dashboard;
        }

        if (session('role_level') == 'Admin') {
            $data['sidebar_reports'] = \Erp::getSidebarReports($this->data['module_id']);
            $data['sidebar_forms'] = \Erp::getSidebarForms($this->data['module_id']);
        }
        aa($data);

        return response()->json($data);
    }

    public function aggridData(Request $request)
    {
        $erp = new \DBEvent($this->data['module_id']);
        $erp->processOnLoad();
        $grid = new \ErpGrid($this->data);

        //aa($request->all());
        try {
            $results = $this->model->getData($request);
        } catch (\Throwable $ex) {
            exception_log($ex->getMessage());
            exception_log($ex->getTraceAsString());
            //debug_email('aggridData error. account_id '.session('account_id').'. user id '.session('user_id').' module id '.$this->data['module_id'].'. '.$ex->getMessage());
        }

        $results['rows'] = $grid->formatAgGridData($results['rows']);

        return response()->json($results);
    }

    public function aggridLayoutData($layout_id = false, $grid_ref = false, $init = false)
    {
        $request = request();

        if (! empty($request->grid_reference)) {
            $grid_ref = $request->grid_reference;
        }
        if (! empty($request->layout_id)) {
            $layout_id = $request->layout_id;
        }

        $views_menu = \Erp::gridViews($this->data['menu_id'], $this->data['module_id'], $grid_ref, $layout_id);

        if (! $layout_id || ! empty($request->menu_only)) {
            $response = ['menu' => json_encode($views_menu)];

            return response()->json($response);
        }
        $config = $this->data['module_layouts']->where('id', $layout_id)->first();

        $grid = new \ErpGrid($this->data, $request->query_string);

        $grid->setGridReference($grid_ref);
        $data = $grid->getLayout($layout_id);

        if ($data['pivotState'] && $data['pivotState']->colState) {
            foreach ($data['pivotState']->colState as $col) {
                if (! empty($col->aggFunc)) {
                    foreach ($data['layout']->colState as $i => $lcol) {
                        if ($lcol->colId == $col->colId) {
                            $data['layout']->colState[$i]->aggFunc = $col->aggFunc;
                        }
                    }
                }
            }
        }

        $data['columnDefs'] = $grid->getColumns();

        $has_sort_field = false;
        foreach ($data['columnDefs'] as $i => $colDef) {
            //$data['columnDefs'][$i]['resizable'] = false;
            if ($colDef['field'] == 'sort_order') {
                // remove row pinning
                // $data['columnDefs'][$i]['pinned'] = false;
                $has_sort_field = true;
            }
        }

        $coldefs = collect($data['columnDefs']);

        $coldefs_visible = $coldefs->where('hide', false)->count();

        if ($coldefs_visible < 3) {
            foreach ($data['columnDefs'] as $i => $def) {
                $data['columnDefs'][$i]['hide'] = false;
            }
        }

        $settings = $data['layout'];

        if (! empty($settings) && ! empty($settings->colState)) {
            foreach ($settings->colState as $i => $colstate) {
                if ($config->layout_type == 'Layout') {
                    $settings->colState[$i]->rowGroup = false;
                } else {
                    if (str_contains($colstate->colId, 'ag-Grid-AutoColumn')) {
                        $settings->colState[$i]->rowGroup = true;
                        $settings->colState[$i]->rowGroupIndex = $i;
                    } elseif (! empty($colstate->rowGroup)) {
                        if ($colstate->rowGroup == 'true') {
                            $settings->colState[$i]->rowGroup = true;
                            $settings->colState[$i]->rowGroupIndex = intval($colstate->rowGroupIndex);
                        }
                    }
                }
                if ($colstate->hide) {
                    $settings->colState[$i]->aggFunc = null;
                }

                if ($colstate->colId == 'is_deleted') {
                    $settings->colState[$i]->hide = 'true';
                }
                if ($config->layout_type == 'Layout') {
                    if ($has_sort_field) {
                        if ($colstate->colId == 'sort_order') {
                            // $settings->colState[$i]->sort = 'asc';
                            // $settings->colState[$i]->sortIndex = '0';
                            // $settings->colState[$i]->hide = 'true';
                        } else {
                            $settings->colState[$i]->sort = '';
                            $settings->colState[$i]->sortIndex = '';
                        }
                    } elseif (empty($settings->colState[$i]->sort)) {
                        $settings->colState[$i]->sort = '';
                        $settings->colState[$i]->sortIndex = '';
                    }
                }
            }
        }

        // https://www.ag-grid.com/javascript-data-grid/pivoting/#specifying-pivot-columns
        // When in pivot mode and not pivoting, only columns that have row group or aggregation active are included in the grid.
        // To add a column to the grid you either add it as a row group column or a value column. Setting visibility on a column has no impact when in pivot mode.

        $toggle_deleted_rows = 0;
        $remove_show_deleted = (! empty(session('remove_show_deleted'.$this->data['module_id']))) ? session('remove_show_deleted'.$this->data['module_id']) : 0;
        $new_remove_show_deleted = 0;
        if (! empty($settings) && ! empty($settings->filterState)) {
            foreach ($settings->filterState as $colIndex => $val) {
                if ($val->filterType == 'set' && $val->values[0] == '') {
                    $settings->filterState[$colIndex]->values[0] = null;
                }
            }
            //remove show deleted session if filter set

            foreach ($settings->filterState as $colIndex => $val) {
                if ($colIndex == 'is_deleted' && ! empty($val) && ! empty($val->values)) {
                    $new_remove_show_deleted = 1;
                    session(['remove_show_deleted'.$this->data['module_id'] => 1]);
                }
                if ($colIndex == 'status' && ! empty($val) && in_array($val->type, ['equals', 'contains']) && $val->filter == 'Deleted') {
                    session(['remove_show_deleted'.$this->data['module_id'] => 1]);
                    $new_remove_show_deleted = 1;
                }
            }
        }
        if ($remove_show_deleted && ! $new_remove_show_deleted) {
            session(['remove_show_deleted'.$this->data['module_id'] => 0]);
        }

        if ($new_remove_show_deleted != $remove_show_deleted) {
            $toggle_deleted_rows = 1;
        }
        if (! empty($config->aggrid_state) && str_contains($config->aggrid_state, '"rowGroup":"true"')) {
            $config->name .= ' Report';
        }

        if (! empty($this->data['tree_data_field'])) {
            foreach ($data['columnDefs'] as $i => $c) {
                if (isset($data['columnDefs'][$i]['aggFunc'])) {
                    unset($data['columnDefs'][$i]['aggFunc']);
                }
                if (isset($data['columnDefs'][$i]['defaultAggFunc'])) {
                    unset($data['columnDefs'][$i]['defaultAggFunc']);
                }
            }
        }

        if ($this->data['layout_tracking_per_user'] && ! empty($request->assigned_user_id)) {
            $assigned_user_id = $request->assigned_user_id;

            // add assigned user filter

            $has_salesman_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $config->module_id)->where('field', 'salesman_id')->count();
            if ($has_salesman_field) {
                $user_field_name = 'salesman_id';
            }
            $has_users_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $config->module_id)->where('field', 'user_id')->count();
            if ($has_users_field) {
                $user_field_name = 'user_id';
            }

            if ($user_field_name) {
                if (! $filter_state) {
                    $filter_state = [];
                }
                $username = \DB::connection('system')->table('erp_users')->where('id', $assigned_user_id)->pluck('full_name')->first();
                $settings->filterState['join_'.$user_field_name] = [
                    'values' => [$username],
                    'filterType' => 'set',
                ];
            }
        }

        //aa($data['pivotState']);
        //aa($settings);
        //aa($data['columnDefs']);
        $response = ['layout_id' => $layout_id, 'name' => $config->name, 'global_default' => $config->global_default, 'kanban_default' => $config->kanban_default, 'layout_type' => $config->layout_type, 'show_on_dashboard' => $config->show_on_dashboard, 'track_layout' => $config->track_layout, 'layout_type' => $config->layout_type, 'columnDefs' => $data['columnDefs'], 'auto_group_col_sort' => $data['auto_group_col_sort'], 'pivot_mode' => $config->pivot_mode, 'pivotState' => $data['pivotState'], 'settings' => json_encode($settings), 'menu' => json_encode($views_menu)];
        $response['show_opened_group'] = $config->show_opened_group;
        $response['group_include_total_footer'] = $config->group_include_total_footer;
        $response['group_include_footer'] = $config->group_include_footer;

        $response['chart_model'] = $config->chart_model;
        $response['show_deleted_rows'] = $toggle_deleted_rows;
        if ($toggle_deleted_rows) {
            $new_remove_show_deleted = ($new_remove_show_deleted) ? 'yes' : 'no';
            $response['new_row_data'] = $this->aggridRefreshData($request, true, $new_remove_show_deleted);
        }

        if ($this->data['detail_module_id'] > 0) {
            session(['remove_show_deleted'.$this->data['detail_module_id'] => 0]);
            // get detail grid
            $detail_layout_data = $grid->getDetailLayout($layout_id);
            $detail_settings = $detail_layout_data['layout'];

            $sub_model = new ErpModel;
            $sub_model->setMenuData('detailmodule_'.$this->data['detail_module_id']);

            $sub_model_data = $sub_model->info;

            $grid_id = 'detail'.$grid_ref;

            $sub_model_data['grid_id'] = $grid_ref;
            $sub_grid = new \ErpGrid($sub_model_data);

            $detail_grid = $sub_grid->getGrid();
            $sub_grid->setGridReference($grid_id);
            $master_menu_name = isset($this->data['menu_name']) ? $this->data['menu_name'] : '';
            $data['detail_col_defs'] = $sub_grid->getColumns();

            $has_sort_field = false;
            foreach ($data['detail_col_defs'] as $i => $colDef) {
                //$data['detail_col_defs'][$i]['resizable'] = false;

                if (! $has_sort_field && $colDef['field'] == 'sort_order') {
                    // remove row pinning
                    $data['detail_col_defs'][$i]['pinned'] = false;
                    $has_sort_field = true;
                }
            }

            $coldefs = collect($data['detail_col_defs']);

            $coldefs_visible = $coldefs->where('hide', false)->count();

            if ($coldefs_visible < 3) {
                foreach ($data['detail_col_defs'] as $i => $def) {
                    $data['detail_col_defs'][$i]['hide'] = false;
                }
            }

            $sort_field = 'sort_order';
            if (! empty($detail_settings) && ! empty($detail_settings->colState)) {
                foreach ($detail_settings->colState as $colIndex => $col) {
                    if ($col->colId == 'is_deleted') {
                        $detail_settings->colState[$colIndex]->hide = 'true';
                    }

                    if ($config->layout_type == 'Layout') {
                        if ($has_sort_field) {
                            if ($col->colId == $sort_field) {
                                $detail_settings->colState[$colIndex]->sort = 'asc';
                                $detail_settings->colState[$colIndex]->hide = 'true';
                                $detail_settings->colState[$colIndex]->sortIndex = 0;
                            } else {
                                $detail_settings->colState[$colIndex]->sort = '';
                                $detail_settings->colState[$colIndex]->sortIndex = '';
                            }
                        }
                    }

                    if (empty($col->sort)) {
                        $detail_settings->colState[$colIndex]->sort = null;
                    }

                    if (! empty($col->rowGroup)) {
                        foreach ($data['detail_col_defs'] as $j => $def) {
                            if ($col->colId == $def['field']) {
                                $data['detail_col_defs'][$j]['rowGroup'] = true;
                                $data['detail_col_defs'][$j]['rowGroupIndex'] = intval($col->rowGroupIndex);
                            }
                        }
                    }
                    if ($config->pivot_mode && $detail_settings->colState[$colIndex]->hide) {
                        $detail_settings->colState[$colIndex]->aggFunc = null;
                        foreach ($data['detail_col_defs'] as $j => $def) {
                            if ($col->colId == $def['field']) {
                                $data['detail_col_defs'][$j]['aggFunc'] = null;
                            }
                        }
                    }
                    foreach ($data['detail_col_defs'] as $j => $def) {
                        if ($col->colId == $def['field']) {
                            $data['detail_col_defs'][$j]['aggFunc'] = null;
                        }
                    }
                    $col->aggFunc = null;
                    $detail_settings->colState[$colIndex] = (array) $col;
                }
            }

            if (! empty($detail_settings) && ! empty($detail_settings->filterState)) {
                foreach ($detail_settings->filterState as $colIndex => $val) {
                    if ($val->filterType == 'set' && $val->values[0] == '') {
                        $detail_settings->filterState[$colIndex]->values[0] = null;
                    }
                    if ($val->filterType == 'set' && $val->values[0] === '0') {
                        $detail_settings->filterState[$colIndex]->values[0] = 0;
                    }
                    if ($val->filterType == 'set' && $val->values[0] === '1') {
                        $detail_settings->filterState[$colIndex]->values[0] = 1;
                    }
                }
            }

            $response['detail_col_defs'] = $data['detail_col_defs'];
            $response['detail_settings'] = json_encode($detail_settings);
        }

        if ($init == true) {
            return $response;
        } else {
            return response()->json($response);
        }
    }

    public function aggridLayoutSave(Request $request)
    {
        try {
            $layout_id = $request->layout_id;
            // if (!is_array($request->layout))
            $layout = json_decode($request->layout);
            $request->query_string = json_decode(json_encode($request->query_string));
            $colState = json_decode(json_encode($layout->colState), true);

            $has_grouping = false;
            if (! empty($colState)) {
                foreach ($colState as $key => $col) {
                    if ($col->rowGroup === 'true') {
                        $has_grouping = true;
                    }
                }
            }
            if ($request->layout_type == 'Layout' && $has_grouping) {
                \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['layout_type' => 'Report', 'chart_model' => '']);
                $request->layout_type = 'Report';
            }
            if ($request->layout_type != 'Layout' && ! $has_grouping) {
                \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['layout_type' => 'Layout', 'chart_model' => '']);
                $request->layout_type = 'Layout';
            }

            //aa($has_grouping );
            //aa($request->layout_type );

            //Copy non-custom layouts to other instances
            if (! empty($request->save_as_duplicate) && $request->save_as_duplicate === 'true') {
                $copy_layout = \DB::connection('default')->table('erp_grid_views')->where('id', $request->layout_id)->get()->first();
                $grid_views = \DB::connection('default')->table('erp_grid_views')
                    ->where('module_id', $copy_layout->module_id)
                    ->where('is_deleted', 0)
                    ->orderby('global_default', 'desc')
                    ->orderby('sort_order')
                    ->get();

                foreach ($grid_views as $i => $view) {
                    \DB::connection('default')->table('erp_grid_views')->where('id', $view->id)->update(['sort_order' => $i]);
                }

                $copy_layout = \DB::connection('default')->table('erp_grid_views')->where('id', $request->layout_id)->get()->first();

                $data = (array) $copy_layout;
                \DB::connection('default')->table('erp_grid_views')
                    ->where('module_id', $data['module_id'])
                    ->where('sort_order', '<=', $data['sort_order'])
                    ->decrement('sort_order');
                //$data['layout_type'] = 'Layout';
                // $data['track_layout'] = 0;

                unset($data['id']);
                unset($data['global_default']);

                unset($data['system_layout']);
                unset($data['main_instance_id']);
                $data['name'] .= ' Duplicate';
                $layout_id = \DB::connection('default')->table('erp_grid_views')->insertGetId($data);
            }

            if ($request->layout_type == 'Report') {
                if ($layout_id) {
                    if (! empty($request->chart_model) && $request->chart_model != '[]') {
                        \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['layout_type' => 'Report', 'chart_model' => $request->chart_model]);
                    } else {
                        \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['layout_type' => 'Report', 'chart_model' => '']);
                    }
                }
                if ($layout_id) {
                    $current_type = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->pluck('layout_type')->first();
                    if ($current_type != 'Report') {
                        \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['show_on_dashboard' => 0]);
                    }
                }
            } elseif ($request->type == 'card') {
                \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update(['show_card' => 1]);
            } else {
                if (empty($layout_id)) {
                    return json_alert('Empty layout id', 'error');
                } else {
                    $views_menu = \Erp::gridViews($this->data['menu_id'], $this->data['module_id'], $request->grid_reference, $layout_id);
                }
            }

            $layout_type = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->pluck('layout_type')->first();
            if (! empty($request->query_string)) {
                foreach ($request->query_string as $field) {
                    $joinfield = 'join_'.$field;
                    unset($layout->filterState->$field);
                    unset($layout->filterState->$joinfield);
                }
            }

            /*
            if (!empty($layout['filterState'])) {
                foreach ($layout['filterState'] as $key => $filter_state) {
                    if ($filter_state['filterType'] == 'set' && count($filter_state['values']) > 10) {
                        unset($layout['filterState'][$key]);
                    }
                }
            }
            */

            // set sort field
            $has_process_sort_field = $this->data['db_module_fields']->where('field', 'process_sort_order')->count();
            $has_sort_field = $this->data['db_module_fields']->where('field', 'sort_order')->count();
            $sort_field = '';
            if ($has_process_sort_field) {
                $sort_field = 'process_sort_order';
            } elseif ($has_sort_field) {
                $sort_field = 'sort_order';
            }

            if ($layout_type == 'Layout' && ! empty($sort_field) && ! empty($colState)) {
                // colstate = json_decode$colState);
                foreach ($colState as $key => $col) {
                    if ($col->colId == $sort_field) {
                        $colState[$key]['sort'] = 'asc';
                        $colState[$key]['sortIndex'] = '0';
                    } else {
                        $colState[$key]['sort'] = '';
                        $colState[$key]['sortIndex]'] = '';
                    }
                }
            }
            if ($request->layout_type == 'Report') {
                foreach ($colState as $key => $col) {
                    unset($colState[$key]['aggFunc']);
                }
            }

            // remove workspace filter dropdown from layout state
            if ($this->data['module_id'] == 2018) { //Workboard
                if (! empty($layout->filterState) && ! empty($layout->filterState->join_role_id)) {
                    unset($layout->filterState->join_role_id);
                }
            }
            $data = ['pivot_mode' => $request->pivot_mode, 'aggrid_state' => json_encode($layout)];

            if ($request->detail_layout) {
                $detail_layout = (array) json_decode($request->detail_layout);
                $has_process_sort_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['detail_module_id'])->where('field', 'process_sort_order')->count();
                $has_sort_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['detail_module_id'])->where('field', 'sort_order')->count();

                $sort_field = '';
                if ($has_process_sort_field) {
                    $sort_field = 'process_sort_order';
                } elseif ($has_sort_field) {
                    $sort_field = 'sort_order';
                }
                if ($layout_type == 'Layout' && $sort_field == '' && ! empty($detail_layout['colState'])) {
                    foreach ($detail_layout['colState'] as $key => $col) {
                        if ($col->colId == $sort_field) {
                            $detail_layout['colState'][$key]['sort'] = 'asc';
                            $detail_layout['colState'][$key]['sortIndex'] = '0';
                        } else {
                            $detail_layout['colState'][$key]['sort'] = '';
                            $detail_layout['colState'][$key]['sortIndex'] = '';
                        }
                    }
                }
                $data['detail_aggrid_state'] = json_encode($detail_layout);
            }

            \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update($data);
            if ($request->pivot_mode == 1) {
                $data = ['aggrid_pivot_state' => json_encode($request->pivot)];
                \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->update($data);
            }

            //update main instance layout
            if (! is_main_instance() && (is_superadmin() || is_dev())) {
                $layout = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->get()->first();

                if ($layout->main_instance_id && ! $layout->custom) {
                    $copy_data = $data;
                    unset($copy_data['id']);
                    unset($copy_data['main_instance_id']);
                    unset($copy_data['module_id']);
                    \DB::connection('system')->table('erp_grid_views')->where('id', $layout->main_instance_id)->update($copy_data);
                }
            }
            $grid_view = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->get()->first();

            // LINK GRID LAYOUT TO FORM

            /*
            if ($grid_view->global_default) {
                if (!empty($request->layout) && !empty($request->layout['colState'])) {
                    $i = 0;
                    foreach ($request->layout['colState'] as $col) {
                        $field = $col['colId'];


                        if (str_starts_with($col['colId'], 'join_')) {
                            $field = str_replace('join_', '', $field);
                        }
                        if ($col['hide'] === 'false') {
                            $tab = 'Basic';
                        } else {
                            continue;
                        }

                        \DB::connection('default')->table('erp_module_fields')
                        ->where('module_id', $this->data['module_id'])
                        ->where('field', $field)
                        ->update(['tab'=>$tab]);
                        if (!empty($col['pinned'])) {
                            continue;
                        }
                        \DB::connection('default')->table('erp_module_fields')
                        ->where('module_id', $this->data['module_id'])
                        ->where('field', $field)
                        ->update(['sort_order'=>$i]);
                        $i++;
                    }

                    foreach ($request->layout['colState'] as $col) {
                        $field = $col['colId'];
                        if (str_starts_with($col['colId'], 'join_')) {
                            $field = str_replace('join_', '', $field);
                        }
                        if ($col['hide'] === 'false') {
                            $tab = 'Basic';
                        } else {
                            continue;
                        }

                        if (!empty($col['pinned'])) {
                            \DB::connection('default')->table('erp_module_fields')
                            ->where('module_id', $this->data['module_id'])
                            ->where('field', $field)
                            ->update(['sort_order'=>$i]);
                            $i++;
                        }
                    }

                    foreach ($request->layout['colState'] as $col) {
                        $field = $col['colId'];
                        if (str_starts_with($col['colId'], 'join_')) {
                            $field = str_replace('join_', '', $field);
                        }
                        if ($col['hide'] === 'false') {
                            continue;
                        } else {
                            $tab = 'Advanced';
                        }
                        \DB::connection('default')->table('erp_module_fields')
                        ->where('module_id', $this->data['module_id'])
                        ->where('field', $field)
                        ->where('tab', 'Basic')
                        ->update(['tab'=>$tab]);

                        \DB::connection('default')->table('erp_module_fields')
                        ->where('module_id', $this->data['module_id'])
                        ->where('field', $field)
                        ->update(['sort_order'=>$i]);
                        $i++;
                    }
                    $i = 0;
                    $basic_fields = \DB::connection('default')->table('erp_module_fields')
                        ->where('module_id', $this->data['module_id'])
                        ->where('tab', 'Basic')
                        ->orderBy('sort_order')
                        ->get();
                    foreach ($basic_fields as $f) {
                        \DB::connection('default')->table('erp_module_fields')->where('id', $f->id)->update(['sort_order'=>$i]);
                        $i++;
                    }
                    $advanced_fields = \DB::connection('default')->table('erp_module_fields')
                        ->where('module_id', $this->data['module_id'])
                        ->where('tab', 'Advanced')
                        ->orderBy('sort_order')
                        ->get();
                    foreach ($advanced_fields as $f) {
                        \DB::connection('default')->table('erp_module_fields')->where('id', $f->id)->update(['sort_order'=>$i]);
                        $i++;
                    }
                    $tabs = \DB::connection('default')->table('erp_module_fields')
                        ->where('module_id', $this->data['module_id'])
                        ->whereNotIn('tab', ['Basic','Advanced'])
                        ->orderBy('sort_order')
                        ->pluck('tab')->unique()->filter()->toArray();

                    foreach ($tabs as $tab) {
                        $tab_fields = \DB::connection('default')->table('erp_module_fields')
                            ->where('module_id', $this->data['module_id'])
                            ->where('tab', $tab)
                            ->orderBy('sort_order')
                            ->get();

                        foreach ($tab_fields as $f) {
                            \DB::connection('default')->table('erp_module_fields')->where('id', $f->id)->update(['sort_order'=>$i]);
                            $i++;
                        }
                    }

                    $form_count = \DB::connection('default')->table('erp_forms')->where('module_id', $grid_view->module_id)->count();

                    $current_conn = \DB::getDefaultConnection();
                    set_db_connection('default');
                    //aa($grid_view->module_id);
                    //aa($layout_id);
                    if (!$form_count) {
                        formio_create_form_from_db($grid_view->module_id);
                    } else {
                        formio_create_form_from_db($grid_view->module_id, true);
                    }
                    set_db_connection($current_conn);
                }
            }
            */

            update_instances_layout($layout_id);
            module_log(526, $layout_id, 'layoutsaved');
            $data = ['status' => 'success', 'message' => 'Layout saved', 'layout_id' => $layout_id, 'menu' => json_encode($views_menu), 'name' => $grid_view->name, 'layout_type' => $grid_view->layout_type, 'track_layout' => $grid_view->track_layout, 'show_on_dashboard' => $grid_view->show_on_dashboard];
            if (! empty($request->detail_layout)) {
                $layout_data = $this->aggridLayoutData($layout_id, $request->grid_id, true);
                $data['detail_col_defs'] = $layout_data['detail_col_defs'];
                $data['detail_settings'] = $layout_data['detail_settings'];
            }

            return $data;
        } catch (\Throwable $ex) {
        }
    }

    public function aggridCommunicationsPanel(Request $request)
    {
        try {
            if (! empty($request->selected_rows)) {
                $response = [];
                $id = $request->selected_rows[0][$this->data['db_key']];
                $row = (object) $this->model->getRow($id);

                $module_links = [];
                foreach ($this->data['module_fields'] as $field) {
                    if (! empty($row->{$field['field']}) && $field['field_type'] == 'select_module') {
                        if ($field['field'] == 'partner_id' && $row->{$field['field']} == 1) {
                            continue;
                        }

                        if ($field['field'] == 'pricelist_id' && ! empty($row->partner_id) && $row->partner_id != 1) {
                            $module_id = 509;
                        } elseif ($field['field'] == 'pricelist_id') {
                            $module_id = 802;
                        } elseif ($field['field'] == 'partner_id') {
                            $module_id = 1812;
                        } else {
                            $module_id = \DB::connection('default')->table('erp_cruds')->where('db_table', $field['opt_db_table'])->pluck('id')->first();
                        }

                        $menu = \DB::connection('default')->table('erp_menu')->where('module_id', $module_id)->get()->first();
                        $url = $menu->slug;
                        $url .= '?'.$field['opt_db_key'].'='.$row->{$field['field']};
                        $module_links[] = ['text' => $menu->menu_name, 'url' => url($url), 'data_target' => 'view_modal'];
                    }
                }
                $response['module_links'] = $module_links;

                if ($request->communications_type == 'account') {
                    $account_id = false;
                    if ($this->data['connection'] == 'pbx' || $this->data['connection'] == 'pbx_cdr') {
                        $keys = array_keys($request->selected_rows[0]);

                        if (in_array('domain_uuid', $keys) && ! empty($request->selected_rows[0]['domain_uuid'])) {
                            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $request->selected_rows[0]['domain_uuid'])->where('erp', session('instance')->directory)->pluck('account_id')->first();
                        } elseif (in_array('join_domain_uuid', $keys) && ! empty($request->selected_rows[0]['join_domain_uuid'])) {
                            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_name', $request->selected_rows[0]['join_domain_uuid'])->where('erp', session('instance')->directory)->pluck('account_id')->first();
                        }
                        if (in_array('domain_name', $keys) && ! empty($request->selected_rows[0]['domain_name'])) {
                            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_name', $request->selected_rows[0]['domain_name'])->where('erp', session('instance')->directory)->pluck('account_id')->first();
                        }
                    } elseif ($this->data['db_table'] == 'crm_accounts') {
                        $account_id = $request->selected_rows[0]['id'];
                    } else {
                        $id = $request->selected_rows[0][$this->data['db_key']];
                        $row = (object) $this->model->getRow($id);
                        $account_id = $row->account_id;
                    }

                    if ($account_id) {
                        $account = dbgetaccount($account_id);
                        if (! $account) {
                            $account = (object) [];
                        }
                        $account->call_profits = 'none';
                        $account->pbx_balance = 'none';
                        $account->sms_balance = 'none';
                        if ($account->type == 'partner') {
                            /*
                            $call_profits = \DB::connection('pbx')->table('p_partners')->where('partner_id', $account->id)->pluck('voice_prepaid_profit')->count();
                            if ($call_profits) {
                                $account->call_profits = \DB::connection('pbx')->table('p_partners')->where('partner_id', $account->id)->pluck('voice_prepaid_profit')->first();
                            }
                            */
                        }

                        if (session('role_level') == 'Partner') {
                            /*
                            $call_profits = \DB::connection('pbx')->table('p_partners')->where('partner_id', session('account_id'))->pluck('voice_prepaid_profit')->count();
                            if ($call_profits) {
                                $account->call_profits = \DB::connection('pbx')->table('p_partners')->where('partner_id', session('account_id'))->pluck('voice_prepaid_profit')->first();
                            }
                            */
                        }

                        if ($account->type == 'customer' || $account->type == 'reseller_user') {
                            /*
                            $pbx = \DB::connection('pbx')->table('v_domains')->where('erp', session('instance')->directory)->where('account_id', $account->id)->count();
                            if ($pbx) {
                                $account->pbx_balance = \DB::connection('pbx')->table('v_domains')->where('erp', session('instance')->directory)->where('account_id', $account->id)->pluck('balance')->first();
                            }
                            */
                            $sms = \DB::connection('default')->table('sub_services')->whereIn('provision_type', ['bulk_sms_prepaid', 'bulk_sms'])->where('status', 'Enabled')->where('account_id', $account->id)->count();
                            if ($sms) {
                                $account->sms_balance = \DB::connection('default')->table('sub_services')->whereIn('provision_type', ['bulk_sms_prepaid', 'bulk_sms'])->where('status', 'Enabled')->where('account_id', $account->id)->pluck('current_usage')->first();
                            }
                        }
                        $contacts = get_account_contacts($account_id);

                        $response['id'] = $account_id;

                        $last_inbound_call = \DB::connection('default')
                            ->table('erp_communication_lines')
                            ->select('created_at')
                            ->where('type', 'Outbound Call')
                            ->where('account_id', 12)
                            ->where('call_account_id', $account_id)
                            ->orderby('created_at', 'desc')->pluck('created_at')->first();

                        if (! empty($last_inbound_call)) {
                            $account->last_inbound_call = date('d M Y, H:i', strtotime($last_inbound_call));
                        } else {
                            $account->last_inbound_call = '';
                        }

                        $last_outbound_call = \DB::connection('default')
                            ->table('erp_communication_lines')
                            ->select('created_at')
                            ->where('type', 'Inbound Call')
                            ->where('account_id', 12)
                            ->where('call_account_id', $account_id)
                            ->orderby('created_at', 'desc')->pluck('created_at')->first();

                        if (! empty($last_outbound_call)) {
                            $account->last_outbound_call = date('d M Y, H:i', strtotime($last_outbound_call));
                        } else {
                            $account->last_outbound_call = '';
                        }
                        $response['account'] = $account;

                        if ($account->partner_id != 1) {
                            $response['reseller'] = dbgetaccount($account->partner_id);
                        }

                        if ($account_id == 12) {
                            $response['sms_panel'] = 1;
                        } else {
                            $response['sms_panel'] = \DB::connection('default')->table('sub_services')->where('account_id', $account_id)->where('provision_type', 'bulk_sms_prepaid')->where('status', '!=', 'Deleted')->count();
                        }
                        $response['hosting_panel'] = \DB::connection('default')->table('isp_host_websites')->where('account_id', $account_id)->count();
                        $response['fibre_panel'] = \DB::connection('default')->table('isp_data_fibre')->where('account_id', $account_id)->count();
                        $response['ip_panel'] = \DB::connection('default')->table('isp_data_ip_ranges')->where('account_id', $account_id)->count();
                        $response['pbx_panel'] = \DB::connection('default')->table('isp_voice_pbx_domains')->where('account_id', $account_id)->count();

                        $response['contacts'] = $contacts;
                        $response['type'] = 'account';
                        $response['disable_access'] = false;
                        if (check_access('1,31') || is_parent_of($account_id)) {
                            $response['disable_access'] = true;
                        }
                    }
                }
                if ($request->communications_type == 'supplier') {
                    if ($this->data['db_table'] == 'crm_suppliers') {
                        $supplier_id = $request->selected_rows[0]['id'];
                    } else {
                        $id = $request->selected_rows[0][$this->data['db_key']];
                        $row = (object) $this->model->getRow($id);
                        $supplier_id = $row->supplier_id;
                    }
                    if ($supplier_id) {
                        $account = dbgetsupplier($supplier_id);
                        $contacts = get_supplier_contacts($supplier_id);
                        $response['id'] = $account_id;
                        $response['account'] = $account;
                        $response['contacts'] = $contacts;
                        $response['type'] = 'supplier';
                    }
                }

                return response()->json($response);
            }
        } catch (\Throwable $ex) {
            return response()->json([]);
        }

        return response()->json([]);
    }

    public function aggridReport(Request $request, $menu, $id)
    {
        if ($this->data['module_id'] != 488) {
            $response = [
                'status' => 'error',
                'message' => 'No List Access',
            ];

            if (request()->ajax()) {
                return response()->json($response);
            } else {
                return redirect()->back()->with($response);
            }
        }

        $data = $this->data;

        $report = \DB::connection('default')->table('erp_reports')->where('id', $id)->get()->first();

        if (empty($report) || empty($report->query_data)) {
        }
        $query_data = unserialize($report->query_data);
        if (empty($query_data['db_columns'])) {
        }
        $colDefs = [];

        $table_aliases = [];
        foreach ($query_data['db_tables'] as $table) {
            $alias = '';
            $table_name_arr = explode('_', $table);
            foreach ($table_name_arr as $table_name_slice) {
                $alias .= $table_name_slice[0];
            }

            if (in_array($alias, $table_aliases)) {
                $i = 1;
                while (in_array($alias, $table_aliases)) {
                    $alias .= $table_name_slice[$i];
                    $i++;
                }
            }
            if (str_contains($table, 'call_records')) {
                $alias = 'cdr';
            }

            $table_aliases[$table] = $alias;
        }

        $decimal_columns = [];
        $int_columns = [];
        foreach ($query_data['db_tables'] as $table) {
            $decimal_columns[$table] = get_columns_from_schema($table, ['decimal', 'float', 'double'], $report->connection);
            $int_columns[$table] = get_columns_from_schema($table, 'integer', $report->connection);
        }

        $cols_added = [];
        $sql = $report->sql_query;
        foreach ($query_data['db_tables'] as $table) {
            foreach ($query_data['db_columns'] as $i => $col) {
                $col_arr = explode('.', $col);
                if ($table == $col_arr[0]) {
                    if (! in_array($col_arr[1], $cols_added)) {
                        $cols_added[] = $col_arr[1];
                        $label = $col_arr[1];
                    } else {
                        $label = $table_aliases[$col_arr[0]].' '.$col_arr[1];
                        $label = str_replace('records_lastmonth ', '', $label);
                        $label = str_replace('records ', '', $label);
                    }

                    $sql_label = $table_aliases[$col_arr[0]].' '.$col_arr[1];
                    //$sql = str_replace($col." as '".$sql_label."'",$col,$sql);

                    $hide = false;
                    if ($i > 10) {
                        $hide = true;
                    }
                    $colDef = [
                        'field' => $sql_label,
                        'headerName' => $label,
                        'hide' => $hide,
                        'type' => 'defaultField',
                        'resizable' => true,
                    ];
                    if (in_array($col_arr[1], $decimal_columns[$col_arr[0]])) {
                        $colDef['type'] = 'currencyField';
                    }
                    if (in_array($col_arr[1], $int_columns[$col_arr[0]])) {
                        $colDef['type'] = 'intField';
                    }

                    $colDefs[] = $colDef;
                }
            }
        }

        $data['colDefs'] = $colDefs;
        $data['report_id'] = $id;
        $data['menu_name'] = $report->name;

        if ($report->connection == 'pbx' || $report->connection == 'pbx_cdr') {
            return view('__app.reports.report_server', $data);
        } else {
            $data['row_data'] = \DB::connection($report->connection)->select($report->sql_query);

            return view('__app.reports.report_client', $data);
        }
    }

    public function aggridReportConfig(Request $request, $id)
    {
        try {
            $data = $this->data;

            $report = \DB::connection('default')->table('erp_reports')->where('id', $id)->get()->first();

            if (empty($report) || empty($report->query_data)) {
                return response()->json(['error' => 'Report not set']);
            }
            $query_data = unserialize($report->query_data);
            if (empty($query_data['db_columns'])) {
                return response()->json(['error' => 'Report columns not set']);
            }
            $colDefs = [];

            $table_aliases = [];
            foreach ($query_data['db_tables'] as $table) {
                $alias = '';
                $table_name_arr = explode('_', $table);
                foreach ($table_name_arr as $table_name_slice) {
                    $alias .= $table_name_slice[0];
                }

                if (in_array($alias, $table_aliases)) {
                    $i = 1;
                    while (in_array($alias, $table_aliases)) {
                        $alias .= $table_name_slice[$i];
                        $i++;
                    }
                }
                if (str_contains($table, 'call_records')) {
                    $alias = 'cdr';
                }

                $table_aliases[$table] = $alias;
            }

            $decimal_columns = [];
            $int_columns = [];
            $date_columns = [];
            $datetime_columns = [];
            foreach ($query_data['db_tables'] as $table) {
                $decimal_columns[$table] = get_columns_from_schema($table, ['decimal', 'float', 'double'], $report->connection);
                $int_columns[$table] = get_columns_from_schema($table, 'integer', $report->connection);
                $date_columns[$table] = get_columns_from_schema($table, 'date', $report->connection);
                $datetime_columns[$table] = get_columns_from_schema($table, 'datetime', $report->connection);
            }

            $cols_added = [];
            $sql = $report->sql_query;
            foreach ($query_data['db_tables'] as $table) {
                foreach ($query_data['db_columns'] as $i => $col) {
                    $col_arr = explode('.', $col);
                    if ($table == $col_arr[0]) {
                        if (! in_array($col_arr[1], $cols_added)) {
                            $cols_added[] = $col_arr[1];
                            $label = $col_arr[1];
                        } else {
                            $label = $table_aliases[$col_arr[0]].' '.$col_arr[1];
                            $label = str_replace('records_lastmonth ', '', $label);
                            $label = str_replace('records ', '', $label);
                        }

                        $sql_label = $table_aliases[$col_arr[0]].' '.$col_arr[1];
                        //$sql = str_replace($col." as '".$sql_label."'",$col,$sql);

                        $hide = false;
                        if ($i > 10) {
                            $hide = true;
                        }
                        $colDef = [
                            'field' => $sql_label,
                            'headerName' => ucwords($label),
                            'hide' => $hide,
                            'type' => 'defaultField',
                            //'aggFunc' => 'value',
                            'resizable' => true,
                        ];
                        if (! empty($report->column_headers)) {
                            $column_headers = json_decode($report->column_headers);
                            foreach ($column_headers as $key => $val) {
                                if ($key == $colDef['field']) {
                                    $colDef['headerName'] = $val;
                                }
                            }
                        }

                        if (in_array($col_arr[1], $decimal_columns[$col_arr[0]])) {
                            $colDef['type'] = 'currencyField';
                            // $colDef['aggFunc'] = 'sum';
                        } elseif (in_array($col_arr[1], $int_columns[$col_arr[0]])) {
                            $colDef['type'] = 'intField';
                            // $colDef['aggFunc'] = 'sum';
                        } elseif (in_array($col_arr[1], $datetime_columns[$col_arr[0]])) {
                            $colDef['type'] = 'dateField';
                            // $colDef['aggFunc'] = 'sum';
                        } elseif (in_array($col_arr[1], $date_columns[$col_arr[0]])) {
                            $colDef['type'] = 'dateField';
                            // $colDef['aggFunc'] = 'sum';
                        } else {
                            $colDef['allowedAggFuncs'] = ['value'];
                        }

                        $colDefs[] = $colDef;
                    }
                }
            }

            $report_data['serverside_model'] = \DB::connection('default')->table('erp_cruds')->whereIn('db_table', $query_data['db_tables'])->where('serverside_model', 1)->count();

            $calc_fields = \DB::connection('default')->table('erp_report_calculated_fields')->where('report_id', $id)->get();
            $col_definitions = get_report_col_defs($id);
            $calc_field_functions = [];
            foreach ($calc_fields as $calc_field) {
                $expression = $calc_field->expression;
                foreach ($col_definitions as $cd) {
                    $expression = str_replace('['.$cd['headerName'].']', "params.data['".$cd['field']."']", $expression);
                }
                $calc_field_functions[$calc_field->colid] = $expression;
                $calc_field_coldef = ['colId' => $calc_field->colid, 'headerName' => $calc_field->colid, 'valueGetter' => $expression];
                if ($calc_field->field_type == 'Text') {
                    $calc_field_coldef['type'] = 'defaultField';
                }
                if ($calc_field->field_type == 'Boolean') {
                    $calc_field_coldef['type'] = 'booleanField';
                }
                if ($calc_field->field_type == 'Integer') {
                    $calc_field_coldef['type'] = 'intField';
                    $calc_field_coldef['cellClass'] = 'intFormat';
                }
                if ($calc_field->field_type == 'Currency') {
                    $calc_field_coldef['type'] = 'currencyField';
                    $calc_field_coldef['cellClass'] = 'currencyFormat';
                }
                $colDefs[] = $calc_field_coldef;
            }

            $report_data['calc_field_functions'] = $calc_field_functions;
            $report_data['colDefs'] = $colDefs;
            $report_data['report_id'] = $id;
            $report_data['name'] = $report->name;
            if (! $report_data['serverside_model']) {
                try {
                    $report_data['row_data'] = \DB::connection($report->connection)->select($report->sql_query);
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    // retry build report sql, if columns changed
                    try {
                        $erp_reports = new \ErpReports;
                        $erp_reports->setErpConnection(session('instance')->db_connection);
                        $sql = $erp_reports->reportSQL($report->id);
                        \DB::connection($connection)->table('erp_reports')->where('id', $report->id)->update(['sql_query' => $sql]);

                        $report_data['row_data'] = \DB::connection($report->connection)->select($report->sql_query);
                    } catch (\Throwable $ex) {
                        exception_log($ex);

                        // rebuild failed, return error
                        return json_alert('SQL error', 'error');
                    }
                }
                foreach ($report_data['row_data'] as $i => $row) {
                    foreach ($report_data['colDefs'] as $colDef) {
                        if ($colDef['type'] == 'currencyField') {
                            $report_data['row_data'][$i]->{$colDef['field']} = floatval($row->{$colDef['field']});
                        }
                    }
                }
            }

            return response()->json($report_data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_log($ex->getMessage());
        }
    }

    public function aggridReportClientData(Request $request, $menu, $id)
    {
        //aa($request->all());
        $report = \DB::table('erp_reports')->where('id', $id)->get()->first();
        $row_data = \DB::connection($report->connection)->select($report->sql_query);

        return response()->json($row_data);
    }

    public function aggridReportData(Request $request, $menu, $id)
    {
        //aa($request->all());
        if (empty($request->endRow)) {
            return response()->json([]);
        }
        $report_model = new \App\Models\ReportModel($id);
        $response = $report_model->getData($request);

        return response()->json($response);
    }

    public function aggridReportStateSave(Request $request)
    {
        //    aa($request->layout);
        $data = ['aggrid_state' => json_encode($request->layout)];
        \DB::table('erp_reports')->where('id', $request->report_id)->update($data);
    }

    public function aggridReportStateLoad(Request $request)
    {
        $state = \DB::table('erp_reports')->where('id', $request->report_id)->pluck('aggrid_state')->first();

        $state = json_decode($state);

        if (! empty($state->colState)) {
            foreach ($state->colState as $i => $col) {
                foreach ($col as $k => $v) {
                    if ($v == 'false') {
                        $state->colState[$i]->{$k} = false;
                    }
                    if ($v == 'true') {
                        $state->colState[$i]->{$k} = true;
                    }
                    if ($v == '') {
                        unset($state->colState[$i]->{$k});
                    } elseif ($k == 'rowGroupIndex') {
                        $state->colState[$i]->{$k} = intval($v);
                    }
                }
            }
        }

        //   aa($state->colState);

        return response()->json(json_encode($state));
    }

    public function aggridReportCalculatedFields(Request $request, $menu_route, $action, $report_id)
    {
        /*
        aa($request->all());
        aa($action);
        aa($report_id);
        */
        if ($action == 'view') {
            $data = [
                'report_id' => $report_id,
                'menu_route' => $menu_route,
            ];
            $report = \DB::connection('default')->table('erp_reports')->where('id', $report_id)->get()->first();

            if (empty($report) || empty($report->query_data)) {
                return response()->json(['error' => 'Report not set']);
            }
            $query_data = unserialize($report->query_data);
            if (empty($query_data['db_columns'])) {
                return response()->json(['error' => 'Report columns not set']);
            }

            return view('__app.grids.grid_calculated_fields', $data);
        }

        if ($action == 'list') {
            $calc_fields = \DB::connection('default')->table('erp_report_calculated_fields')->where('report_id', $report_id)->get();

            return response()->json($calc_fields);
        }

        if ($action == 'coldefs') {
            $colDefs = get_report_col_defs($report_id);

            return response()->json($colDefs);
        }

        if ($action == 'save') {
            $data = [
                'colId' => $request->colId,
                'expression' => $request->expression,
                'field_type' => $request->field_type,
                'report_id' => $report_id,
            ];
            if (! empty($request->id)) {
                \DB::connection('default')->table('erp_report_calculated_fields')->where('id', $request->id)->update($data);

                return response()->json(['id' => $request->id]);
            } else {
                $insert_id = \DB::connection('default')->table('erp_report_calculated_fields')->insertGetId($data);

                return response()->json(['id' => $insert_id]);
            }
        }

        if ($action == 'delete') {
            \DB::connection('default')->table('erp_report_calculated_fields')->where('id', $request->id)->delete();

            return response()->json(['deleted' => 1]);
        }
    }

    public function getLayoutData(Request $request)
    {
        $views_menu = \Erp::gridViews($this->data['menu_id'], $this->data['module_id'], $request->grid_reference, $request->layout_id);

        if (! $request->layout_id || ! empty($request->menu_only)) {
            $response = ['menu' => json_encode($views_menu)];

            return response()->json($response);
        }
        $config = $this->data['module_layouts']->where('id', $request->layout_id)->first();

        $grid = new \ErpGrid($this->data);

        $grid->setGridReference($request->grid_reference);
        $data = $grid->getGrid($request->layout_id);

        $settings = $data['layout_settings'];

        $response = ['name' => $config->name, 'settings' => json_encode($settings), 'menu' => json_encode($views_menu)];

        return response()->json($response);
    }

    public function data(Request $request)
    {
        $request_arr = $request->all();

        $grid = new \ErpGrid($this->data);

        if (empty($request->where) && ! empty($request_arr) && is_array($request_arr) && isset($request_arr[0]['isComplex'])) {
            $request->where = $request_arr;
        }

        if (empty($request->requiresCounts) && $request->where[0]['operator'] == 'notequal') {
            $ajax_data = (object) [
                'result' => [],
                'count' => 0,
            ];
            echo json_encode($ajax_data);
        } else {
            $params = [
                'search' => (! empty($request->search)) ? $request->search : '',
                'fstart' => intval((! empty($request->skip)) ? $request->skip : 0),
                'flimit' => intval((! empty($request->take)) ? $request->take : 10),
                'gridsort' => (! empty($request->sorted)) ? $request->sorted : '',
                'gridfilter' => (! empty($request->where)) ? $request->where : '',
                'grid_layout_id' => (! empty($request->grid_layout_id)) ? $request->grid_layout_id : '',
            ];

            if (! empty($request->is_export)) {
                $params['is_export'] = 1;
            }

            $params['groupby_field'] = '';

            //preview filter limit, send filter field with and group by
            /*https://www.syncfusion.com/forums/120056/how-do-you-send-additional-data-to-the-server-on-a-filterchoicerequest-when-using-an*/

            if (! empty($request->groupby)) {
                $grid_fields = $grid->getFields();
                foreach ($grid_fields as $field) {
                    if ($request->groupby == $field['field']) {
                        $params['groupby_field'] = $field['alias'].'.'.$request->groupby;
                    }
                }
            }
            if (! empty($params['groupby_field'])) {
                $params['flimit'] = 'All';
            }

            $results = $this->model->getRows($params);

            $total_rows = $results['total'];

            $ajax_data = (object) [];
            $ajax_data->result = $grid->formatGridData($results['rows']);
            $ajax_data->count = $total_rows;

            $preview_array = [];
            if (empty($params['groupby_field']) && ! empty($request->where) && empty($request->where[0]['condition'])) {
                if (! empty($request->requiresCounts) && $request->requiresCounts == true) {
                    $ajax_data = (object) [
                        'results' => $ajax_data->result,
                        'count' => count($ajax_data->result),
                    ];
                } else {
                    $ajax_data = $ajax_data->result;
                }

                return response()->json($ajax_data);
            } elseif (! empty($params['groupby_field'])) {
                if (! empty($request->requiresCounts) && $request->requiresCounts == true) {
                    $ajax_data = (object) [
                        'result' => $ajax_data->result,
                        'count' => count($ajax_data->result),
                    ];
                } else {
                    $ajax_data = $ajax_data->result;
                }
                if ($ajax_data->result) {
                    $results = [];
                    foreach ($ajax_data->result as $row) {
                        $row_data = (object) [$request->groupby => $row[$request->groupby]];
                        $results[] = $row_data;
                    }
                    $ajax_data->result = $results;
                }

                echo json_encode($ajax_data);
            } else {
                echo json_encode($ajax_data);
            }
        }
    }

    public function getRecordContacts(Request $request)
    {
        $account_type = $request->segment(3);
        $id = (is_numeric($request->segment(4))) ? $request->segment(4) : null;

        if (! $account_type) {
            return [];
        }
        if (! $id) {
            return [];
        }

        if ($id) {
            $row = (object) $this->model->getRow($id);

            if ($account_type == 'account') {
                if ($this->data['db_table'] == 'crm_accounts') {
                    $account_id = $id;
                } else {
                    $account_id = $row->account_id;
                }

                return \DB::connection('default')->table('erp_users')->where('account_id', $account_id)->get();
            }

            if ($account_type == 'supplier') {
                if ($this->data['db_table'] == 'crm_suppliers') {
                    $supplier_id = $id;
                } else {
                    $supplier_id = $row->supplier_id;
                }

                return \DB::connection('default')->table('crm_supplier_contacts')->where('supplier_id', $supplier_id)->get();
            }
        }
    }

    public function addRecordContact(Request $request)
    {
        $contact_id = 0;
        if ($request->row_id) {
            if ($request->account_type == 'account') {
                if ($this->data['db_table'] == 'crm_accounts') {
                    $account_id = $request->row_id;
                } else {
                    $row = (object) $this->model->getRow($request->row_id);
                    $account_id = $row->account_id;
                }
                $data = [
                    'account_id' => $account_id,
                    'type' => $request->type,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                ];

                $erp = new \DBEvent(1810);
                $result = $erp->save($data);

                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    return $result;
                } elseif (! is_array($result) || empty($result['id'])) {
                    return response()->json(['status' => 'warning', 'message' => $result]);
                }

                $contact_id = $result['id'];
            }

            if ($request->account_type == 'supplier') {
                if ($this->data['db_table'] == 'crm_suppliers') {
                    $supplier_id = $request->row_id;
                } else {
                    $row = (object) $this->model->getRow($request->row_id);
                    $supplier_id = $row->supplier_id;
                }
                $data = [
                    'supplier_id' => $supplier_id,
                    'type' => $request->type,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                ];

                $erp = new \DBEvent(1811);
                $result = $erp->save($data);
                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    return $result;
                } elseif (! is_array($result) || empty($result['id'])) {
                    return response()->json(['status' => 'warning', 'message' => $result]);
                }
                $contact_id = $result['id'];
            }
        }

        return json_alert('Contact Added', 'success', ['contact_id' => $contact_id]);
    }

    public function deleteRecordContact(Request $request)
    {
        if ($request->account_type == 'account') {
            \DB::connection('default')->table('erp_users')->where('type', '!=', 'Manager')->where('id', $request->contact_id)->delete();
        }

        if ($request->account_type == 'supplier') {
            \DB::connection('default')->table('crm_supplier_contacts')->where('id', $request->contact_id)->delete();
        }
    }

    public function getRecordNotes(Request $request)
    {
        $id = (is_numeric($request->segment(3))) ? $request->segment(3) : null;

        if (! $id) {
            return [];
        }

        return \DB::connection('default')->table('erp_module_notes')
            ->select('erp_module_notes.*', 'erp_users.full_name as username')
            ->join('erp_users', 'erp_users.id', '=', 'erp_module_notes.created_by')
            ->where('module_id', $this->data['module_id'])
            ->where('row_id', $id)
            ->where('erp_module_notes.is_deleted', 0)
            ->orderBy('created_at', 'desc')->get();
    }

    public function addRecordNote(Request $request)
    {
        $data = [
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => session('user_id'),
            'row_id' => $request->row_id,
            'module_id' => $request->module_id,
            'note' => $request->note,
            'is_deleted' => 0,
        ];

        \DB::connection('default')->table('erp_module_notes')->insert($data);
        $module = \DB::connection('default')->table('erp_cruds')->where('id', $request->module_id)->get()->first();
        $modules = \DB::connection('default')->table('erp_cruds')->get();
        $module_fields = \DB::connection('default')->table('erp_module_fields')->where('module_id', $request->module_id)->pluck('field')->toArray();

        $account_id_module_ids = \DB::connection('default')->table('erp_module_fields')->where('field', 'account_id')->pluck('module_id')->toArray();
        $account_id_module_ids[] = 343;

        if (in_array('last_note', $module_fields)) {
            //if(in_array('last_note_date',$module_fields)){
            //    \DB::connection($module->connection)->table($module->db_table)->update(['last_note_date'=>null,'last_note' => '']);
            //}else{
            //    \DB::connection($module->connection)->table($module->db_table)->update(['last_note' => '']);
            // }

            if (in_array('is_deleted', $module_fields)) {
                $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->where('is_deleted', 0)->get();
            } elseif (in_array('status', $module_fields)) {
                $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->where('status', '!=', 'Deleted')->get();
            } else {
                $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->get();
            }

            foreach ($records as $record) {
                if (in_array($this->data['module_id'], $account_id_module_ids)) {
                    $last_note = \DB::table('erp_module_notes')->whereIn('module_id', $account_id_module_ids)->where('row_id', $record->id)->orderBy('id', 'desc')->get()->first();
                } else {
                    $last_note = \DB::table('erp_module_notes')->where('module_id', $module->id)->where('row_id', $record->id)->orderBy('id', 'desc')->get()->first();
                }

                if ($last_note) {
                    //if(in_array($this->data['module_id'],$account_id_module_ids)){
                    $module_name = $modules->where('id', $last_note->module_id)->pluck('name')->first();
                    $last_note->note = $module_name.': '.$last_note->note;
                    // }
                    if (in_array('last_note_date', $module_fields)) {
                        \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $record->id)->update(['last_note_date' => $last_note->created_at, 'last_note' => date('Y-m-d', strtotime($last_note->created_at)).' '.$last_note->note]);
                    } else {
                        \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $record->id)->update(['last_note' => date('Y-m-d', strtotime($last_note->created_at)).' '.$last_note->note]);
                    }
                }
            }
        }

        populate_module_note_details();

        return json_alert('Note Added');
    }

    public function deleteRecordNote(Request $request)
    {
        \DB::connection('default')->table('erp_module_notes')->where('id', $request->note_id)->update(['is_deleted' => 1]);
    }

    public function getRecordFiles(Request $request)
    {
        $id = (is_numeric($request->segment(3))) ? $request->segment(3) : null;
        if (! $id) {
            return [];
        }
        $files = \DB::connection('default')->table('erp_module_files')
            ->select('erp_module_files.*', 'erp_users.full_name as username')
            ->join('erp_users', 'erp_users.id', '=', 'erp_module_files.created_by')
            ->where('module_id', $this->data['module_id'])
            ->where('row_id', $id)
            ->orderBy('created_at')->get();

        $filePath = uploads_path($this->data['module_id']);
        $fileUrl = uploads_url($this->data['module_id']);
        foreach ($files as $i => $f) {
            $files[$i]->url = $fileUrl.$f->file_name;
        }

        return $files;
    }

    public function addRecordFile(Request $request)
    {
        $row_id = $request->row_id;

        $module_id = (! empty($request->module_id)) ? $request->module_id : $this->data['module_id'];
        if ($request->communications_type == 'pbx') {
            $row_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $row_id)->pluck('account_id')->first();
            $module_id = 343;
        }

        $files = is_array($request->file('file_name')) ? $request->file('file_name') : [$request->file('file_name')];
        $filenames = [];

        foreach ($files as $file) {
            if (is_array($file)) {
                continue;
            }

            $file_type = $file->getMimeType();
            $file_extension = $file->getClientOriginalExtension();

            $destinationPath = uploads_path($module_id);
            if (! is_dir($destinationPath)) {
                mkdir($destinationPath);
            }
            $filename = $file->getClientOriginalName();

            $filename = str_replace([' ', ','], '_', $filename);

            $uploadSuccess = $file->move($destinationPath, $filename);
            if ($uploadSuccess) {
                $filenames[] = $filename;
            }
        }

        foreach ($filenames as $file_name) {
            $data = [
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => session('user_id'),
                'row_id' => $row_id,
                'module_id' => $module_id,
                'file_name' => $file_name,
            ];

            \DB::connection('default')->table('erp_module_files')->insert($data);
        }
    }

    public function deleteRecordFile(Request $request)
    {
        $file = \DB::connection('default')->table('erp_module_files')->where('id', $request->file_id)->get()->first();

        $destinationPath = uploads_path($this->data['module_id']);
        $file_path = $destinationPath.$file->file_name;

        if (file_exists($file_path)) {
            unlink($file_path);
        }
        \DB::connection('default')->table('erp_module_files')->where('id', $request->file_id)->delete();
    }

    public function getRecordChangeLog(Request $request)
    {
        $id = (is_numeric($request->segment(3))) ? $request->segment(3) : null;
        if (! $id) {
            return [];
        }

        $rows = \DB::connection('default')->table('erp_module_log')
            ->select('erp_module_log.*', 'erp_users.full_name as username')
            ->join('erp_users', 'erp_users.id', '=', 'erp_module_log.created_by')
            ->where('module_id', $this->data['module_id'])
            ->where('row_id', $id)
            ->where('action', '!=', 'updated')
            ->orderBy('id', 'desc')
            ->get()->unique('action');
        $row_values = $rows->values();

        $updates = \DB::connection('default')->table('erp_module_log')
            ->select('erp_module_log.*', 'erp_users.full_name as username')
            ->join('erp_users', 'erp_users.id', '=', 'erp_module_log.created_by')
            ->where('module_id', $this->data['module_id'])
            ->where('row_id', $id)
            ->where('action', 'updated')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $data = [];
        foreach ($updates as $r) {
            $data[] = $r;
        }
        foreach ($row_values as $r) {
            $data[] = $r;
        }

        return $data;
    }

    public function getView(Request $request)
    {
        $id = (is_numeric($request->segment(3))) ? $request->segment(3) : null;

        if (! $this->data['access']['is_view']) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'No Access']);
            } else {
                return Redirect::back()->with('message', 'No Access')->with('status', 'error');
            }
        }

        if ($id) {
            $row = (object) $this->model->getRow($id);
        }

        if (empty($row)) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Invalid Id']);
            } else {
                return Redirect::back()->with('message', 'Invalid Id')->with('status', 'error');
            }
        }

        if ($this->data['documents_module']) {
            $is_supplier = (str_contains($this->data['db_table'], 'supplier')) ? true : false;
            if (empty($row->id)) {
                return json_alert('Invalid Id. Record does not exists.', 'error');
            }
            $pdf_name = str_replace(' ', '_', ucfirst($row->doctype).' '.$row->id);
            $file = $pdf_name.'.pdf';

            if (check_access('21') && session('partner_id') != 1) {
                $file = 'Service_'.$file;
                $pdf = servicedocument_pdf($id);
            } elseif ($this->data['db_table'] == 'crm_supplier_import_documents') {
                $file = $pdf_name.'.pdf';
                $pdf = document_pdf($id, $is_supplier, false, true);
            } else {
                $file = $pdf_name.'.pdf';
                $pdf = document_pdf($id, $is_supplier);
            }

            $filename = attachments_path().$file;

            if (file_exists($filename)) {
                unlink($filename);
            }
            $pdf->save($filename);
            $this->data['doc_id'] = $row->id;
            $this->data['file'] = $file;
            $this->data['pdf'] = attachments_url().$file;
            $this->data['menu_name'] = $pdf_name;

            return view('__app.components.pdf', $this->data);
        }

        $form = new \ErpForm($this->data, $request);
        $id = $form->form_id;
        $form->setEditType('view');
        $row = $this->model->getRow($id);
        //$data = $form->getForm($row);
        $data = $this->data;
        $data['allow_edit'] = 0;
        if ($this->data['access']['is_edit'] == 1) {
            $data['allow_edit'] = 1;
            $data['form_record_id'] = $id;
        }

        $form_id = formio_get_form_id($this->data['module_id']);
        if ($form_id) {
            $form_config = \DB::connection('default')->table('erp_forms')->where('id', $form_id)->get()->first();
        }

        //$formio = false;
        if ($form_id && $form_config) {
            $data['form_id'] = $form_id;
            $data['form_readonly'] = true;
            $data['form_json'] = str_replace('https://api.form.io', 'https://'.session('instance')->domain_name, $form_config->form_json);

            $data['form_data'] = $row;
            $data['form_change_events'] = formio_get_events_from_json(json_decode($form_config->form_json));

            $form_type = 'view';
            $data['form_data'] = formio_format_form_data($form_type, $data['form_data'], $this->data['module_fields'], $this->data['db_table'], $this->data['db_key']);

            $data['form_json'] = formio_apply_form_permissions($form_type, $data['form_json'], $this->data['module_fields']);
            $data['form_json'] = json_encode($data['form_json']);

            $data['name_field'] = '';
            foreach ($this->data['module_fields'] as $field) {
                if (str_contains($field['field_type'], 'select') && empty($data['form_data'][$field['field']])) {
                    unset($data['form_data'][$field['field']]);
                }

                if (($field['field_type'] == 'file' || $field['field_type'] == 'image') && empty($data['form_data'][$field['field']])) {
                    unset($data['form_data'][$field['field']]);
                }

                if (($field['field_type'] == 'file' || $field['field_type'] == 'image') && ! empty($data['form_data'][$field['field']])) {
                    $remove_files = false;
                    $destinationPath = uploads_path($this->data['module_id']);

                    $file_names = explode(',', $data['form_data'][$field['field']]);
                    $files = [];
                    foreach ($file_names as $file_name) {
                        if (! empty($file_name)) {
                            $file_path = $destinationPath.'/'.$file_name;
                            if (file_exists($file_path)) {
                                $files[] = $file_name;
                            } else {
                                $remove_files = true;
                            }
                        }
                    }

                    $files_value = implode(',', $files);

                    if ($remove_files) {
                        \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $id)->update([$field['field'] => $files_value]);
                    }

                    if (count($files) > 0) {
                        $data['form_data'][$field['field']] = formio_get_file_info($field['id'], $files_value);
                    } else {
                        unset($data['form_data'][$field['field']]);
                    }
                }
                if ($field['label'] == 'Name') {
                    $data['name_field'] = $field['field'];
                }
            }

            return view('__app.forms.module_form', $data);
        }

        //dd($data);
        return view('__app.components.form', $data);
    }

    public function getEdit(Request $request)
    {
        // aa($request->all());
        $use_syncfusion = true;
        // if(is_dev()){
        //     $use_syncfusion = true;
        //  }
        // aa($request->all());
        session()->forget('event_db_record');
        if (! str_contains(url()->previous(), session('menu_route'))) {
            $request->attributes->add(['test_attr' => 1]);
        }

        session()->forget('switch_numbers_allocate_domain');
        session()->forget('item_ajax');
        session()->forget('product_ajax');
        session()->forget('voice_rates_ajax');
        if ($this->data['db_table'] == 'crm_pricelist_items' && ! empty($request->id)) {
            $item = \DB::connection('default')->table('crm_pricelist_items')->where('id', $request->id)->get()->first();
            $product_id = $item->product_id;
            if (! in_array($item->pricelist_id, [1, 2])) {
                $disable_reseller_markup = \DB::connection('default')->table('crm_products')->where('id', $product_id)->pluck('disable_reseller_markup')->first();

                if ($disable_reseller_markup) {
                    $error = 'Markup cannot be set on this item.';
                    if ($request->ajax()) {
                        return response()->json(['status' => 'warning', 'message' => $error]);
                    } else {
                        return Redirect::back()->with('message', $error)->with('status', 'warning');
                    }
                }
            }
        }
        if ($this->model == null) {
            $this->initModel($request->segment(1));
        }
        $form = new \ErpForm($this->data, $request->all());

        $id = $form->form_id;
        $webform = false;
        if (! empty(session('webform_module_id'))) {
            $webform = true;
        }

        if (! empty(session('webform_module_id')) && session('webform_module_id') != $this->data['module_id']) {
            $webform = false;
            session()->forget('webform_module_id');
            session()->forget('webform_id');
            session()->forget('webform_account_id');
            session()->forget('webform_subscription_id');
        }

        if (empty(session('webform_module_id'))) {
            $allow_access = true;

            if (! $id) {
                if ($this->data['access']['is_add'] == 0) {
                    $allow_access = false;
                }
            } else {
                if ($this->data['access']['is_edit'] == 0) {
                    $allow_access = false;
                }
            }
            if (! $allow_access) {
                if ($request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => 'No Access']);
                } else {
                    return Redirect::back()->with('message', 'No Access')->with('status', 'error');
                }
            }

            if ($id) {
                $record_access = $this->model->singleRecordAccess($id);

                if (! $record_access) {
                    if ($request->ajax()) {
                        return response()->json(['status' => 'error', 'message' => 'No Record Access']);
                    } else {
                        return Redirect::back()->with('message', 'No Access')->with('status', 'error');
                    }
                }
            }
        }

        if ($this->data['documents_module']) {
            return $this->getTransactionEdit($request, $id);
        }
        $row = $this->model->getRow($id);
        if ($webform || $use_syncfusion) {
            $data = $form->getForm($row);
            //aa($data['form_html']);
        } else {
            $data = $this->data;
        }

        $data['webform'] = $webform;

        $data['module_fields_url'] = get_menu_url_from_table('erp_module_fields');
        $data['form_description'] = str_replace([PHP_EOL, "'"], ['<br>', ''], $data['form_description']);
        $data['id'] = $id;
        if (! empty($request->is_iframe)) {
            $data['hide_page_header'] = 1;
            $data['remove_container'] = 1;
        }

        if (session('troubleshooter_form')) {
            return view('__app.components.form_troubleshooter', $data);
        }

        $data['form_title'] = $data['menu_name'];
        if (empty($data['form_title'])) {
            $data['form_title'] = ucwords(str_replace('_', ' ', $data['name']));
        }
        $name_field = $this->data['db_module_fields']->where('label', 'Name')->pluck('field')->first();

        if (! empty($name_field) && ! empty($row) && ! empty($row[$name_field])) {
            $data['form_title'] .= ' - '.$row[$name_field];
        } elseif (! empty($row) && ! empty($row['company'])) {
            $data['form_title'] .= ' - '.$row['company'];
        } elseif (! empty($row) && ! empty($row['code'])) {
            $data['form_title'] .= ' - '.$row['code'];
        } elseif (! empty($row) && ! empty($row['title'])) {
            $data['form_title'] .= ' - '.$row['title'];
        } elseif (! empty($row) && ! empty($row['name'])) {
            $data['form_title'] .= ' - '.$row['name'];
        } elseif (! empty($row) && ! empty($row['field'])) {
            $data['form_title'] .= ' - '.$row['field'];
        } elseif (! empty($row) && ! empty($row['id'])) {
            $data['form_title'] .= ' - Edit';
        }

        $formio = false;
        if (! $webform) {
            $form_id = formio_get_form_id($this->data['module_id']);
            if ($form_id) {
                $form_config = \DB::connection('default')->table('erp_forms')->where('id', $form_id)->get()->first();
            }
            if (! $use_syncfusion) {
                $formio = true;
            }
        }

        $data['field_labels'] = collect($this->data['module_fields'])->pluck('label', 'id');

        $fields_module = \DB::connection('default')->table('erp_cruds')->where('id', 749)->get()->first();
        $data['fields_module_title'] = ucwords(str_replace('_', ' ', $fields_module->name));
        $data['fields_module_description'] = str_replace([PHP_EOL, "'"], ['<br>', ''], $fields_module->form_description);

        // aa($webform);

        if (! $webform && $formio && $form_id && $form_config) {
            $data['form_id'] = $form_id;
            $data['form_readonly'] = false;

            $data['form_json'] = str_replace('https://api.form.io', 'https://'.session('instance')->domain_name, $form_config->form_json);
            // $row['product_category_id'] = 'Data - Internet Devices';
            $data['form_data'] = $row;
            $form_type = 'edit';
            if (empty($id)) {
                $form_type = 'add';
            }

            if (check_access('1') && ! empty(request()->form_role_id)) {
                $form_type = 'view';
                $data['form_readonly'] = true;
            }

            if (empty($id) && ! empty($request->filter_model)) {
                session(['filter_model' => json_decode($request->filter_model)]);
            } else {
                session(['filter_model' => null]);
            }

            //LINK GRID LAYOUT TO FORM

            if ($this->data['auto_form'] && ! empty($request->layout_id)) {
                $k = $data['form_json'];
                if (! empty($request->detail_layout)) {
                    $updated_form_json = formio_updated_tabs_from_layout($this->data['module_id'], $request->layout_id, $this->data['module_fields'], true);
                } else {
                    $updated_form_json = formio_updated_tabs_from_layout($this->data['module_id'], $request->layout_id, $this->data['module_fields']);
                }
                if ($updated_form_json !== false) {
                    $data['form_json'] = $updated_form_json;
                }
            }

            //aa(request()->all());
            $data['form_data'] = formio_format_form_data($form_type, $data['form_data'], $this->data['module_fields'], $this->data['db_table'], $this->data['db_key']);

            //aa($data['form_data']);
            // $data['form_json'] = formio_form_data_remove_defaults($form_type, $data['form_json'], $data['form_data'], $this->data['module_fields']);

            $data['form_json'] = formio_form_data_remove_defaults($form_type, $data['form_json'], $data['form_data'], $this->data['module_fields']);

            $data['form_change_events'] = formio_get_events_from_json(json_decode($form_config->form_json));

            $data['form_json'] = formio_apply_form_permissions($form_type, $data['form_json'], $this->data['module_fields']);

            $data['form_json'] = json_encode($data['form_json']);

            if (session('user_id') == 4194) {
            }
            $data['name_field'] = '';
            foreach ($this->data['module_fields'] as $field) {
                if (str_contains($field['field_type'], 'select') && empty($data['form_data'][$field['field']])) {
                    unset($data['form_data'][$field['field']]);
                }

                if ($field['field_type'] == 'boolean' && empty($data['form_data'][$field['field']])) {
                    $data['form_data'][$field['field']] = 0;
                }

                if (($field['field_type'] == 'file' || $field['field_type'] == 'image') && empty($data['form_data'][$field['field']])) {
                    unset($data['form_data'][$field['field']]);
                }

                if (($field['field_type'] == 'file' || $field['field_type'] == 'image') && ! empty($data['form_data'][$field['field']])) {
                    $remove_files = false;
                    $destinationPath = uploads_path($this->data['module_id']);

                    $file_names = explode(',', $data['form_data'][$field['field']]);
                    $files = [];
                    foreach ($file_names as $file_name) {
                        if (! empty($file_name)) {
                            $file_path = $destinationPath.'/'.$file_name;
                            if (file_exists($file_path)) {
                                $files[] = $file_name;
                            } else {
                                $remove_files = true;
                            }
                        }
                    }

                    $files_value = implode(',', $files);

                    if ($remove_files) {
                        \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $id)->update([$field['field'] => $files_value]);
                    }

                    if (count($files) > 0) {
                        $data['form_data'][$field['field']] = formio_get_file_info($field['id'], $files_value);
                    } else {
                        unset($data['form_data'][$field['field']]);
                    }
                }

                if ($field['label'] == 'Name') {
                    $data['name_field'] = $field['field'];
                }
            }
            $data['popup_form'] = (! empty($request->popup_form_field)) ? 1 : 0;
            $data['popup_form_field'] = (! empty($request->popup_form_field)) ? $request->popup_form_field : '';
            $data['popup_form_module_id'] = (! empty($request->popup_form_module_id)) ? $request->popup_form_module_id : '';
            if (session('role_level') == 'Admin') {
                $data['select_fields'] = collect($data['db_module_fields'])->where('field', '!=', 'created_by')->where('field', '!=', 'updated_by')->where('field_type', 'select_module')->where('opt_module_id', '>', 0);
            }
            //   if (is_dev()) {
            //   }
            // save current url for formbuilder
            session(['form_builder_redirect' => url()->current()]);

            return view('__app.forms.module_form', $data);
        }
        $data['context_tabs'] = $this->data['db_module_fields']->pluck('tab')->unique()->filter()->toArray();

        if (empty($data['context_tabs']) || count($data['context_tabs']) == 0) {
            $data['context_tabs'] = ['General', 'Other'];
        }

        if (! in_array('General', $data['context_tabs'])) {
            $data['context_tabs'][] = 'General';
        }
        if (! in_array('Other', $data['context_tabs'])) {
            $data['context_tabs'][] = 'Other';
        }
        $data['context_tabs'][] = 'New Tab';

        return view('__app.components.form', $data);
    }

    public function getTransactionEdit(Request $request, $id = null)
    {
        //pricelist_set_discounts();
        $doctable = 'crm_documents';
        $doclinestable = 'crm_document_lines';
        if ($this->data['db_table'] == 'crm_supplier_documents') {
            $doctable = 'crm_supplier_documents';
            $doclinestable = 'crm_supplier_document_lines';
        }
        if ($this->data['db_table'] == 'crm_supplier_import_documents') {
            $doctable = 'crm_supplier_import_documents';
            $doclinestable = 'crm_supplier_import_document_lines';
        }

        if ($doctable = 'crm_supplier_documents') {
            $this->data['cdr_destinations'] = ['all', 'mobile all', 'mobile cellc', 'mobile mtn', 'mobile vodacom', 'mobile telkom', 'fixed telkom', 'fixed other fixed liquid', 'fixed tollfree', 'fixed sharecall'];
        }

        if (! empty($id)) {
            $row = (object) $this->model->getRow($id);
            if ($row) {
                $doctype = \DB::table('acc_doctypes')->where('doctype', $row->doctype)->get()->first();

                if (! $doctype->editable) {
                    if ($request->ajax()) {
                        return response()->json(['status' => 'error', 'message' => 'Document type cannot be edited.']);
                    } else {
                        return redirect($this->data['menu_route'])->with('message', 'Document type cannot be edited.')->with('status', 'error');
                    }
                }

                if ($this->data['db_table'] == 'crm_supplier_documents' || $this->data['db_table'] == 'crm_supplier_import_documents') {
                    $account_id = $row->supplier_id;
                } else {
                    $account_id = $row->account_id;
                }

                $lines = \DB::table($doclinestable.' as cdl')
                    ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
                    ->select('cdl.*', 'cp.product_category_id', 'cp.sort_order')
                    ->where('cdl.document_id', $id)
                    ->get();

                $this->data['document'] = $row;
                if (! empty($lines) && count($lines) > 0) {
                    $this->data['document_lines'] = sort_product_rows($lines);
                } else {
                    $this->data['document_lines'] = [(object) $this->model->getColumnTable($doclinestable)];
                    $this->data['document_lines'][0]->qty = 0;
                    $this->data['document_lines'][0]->price = 0;
                }

                $this->data['salesman_id'] = $row->salesman_id;
                $this->data['transaction_type'] = $row->doctype;
                $this->data['doctype'] = $row->doctype;
            }
        } else {
            $this->data['doctype'] = '';
            $this->data['document'] = (object) $this->model->getColumnTable($doctable);
            $this->data['document_lines'] = [(object) $this->model->getColumnTable($doclinestable)];

            $this->data['document']->tax = 0;
            $this->data['document']->bill_frequency = 1;
            $this->data['document']->delivery_fee = 0;
            $this->data['document']->total = 0;
            $this->data['document_lines'][0]->qty = 0;
            $this->data['document_lines'][0]->price = 0;
            $this->data['document']->docdate = date('Y-m-d');

            $usd_exchange = convert_currency_usd_to_zar(1);
            $this->data['document']->exchange_rate = $usd_exchange;

            if (session('role_level') == 'Admin') {
                $this->data['document']->salesman_id = session('user_id');
            }

            $this->data['document']->reseller_user = 0;
        }

        $this->data['exchange_rate'] = $this->data['document']->exchange_rate;
        if (session('role_id') == '21') {
            $account_id = session('account_id');
        } elseif (session('role_id') == '11' && ! empty($request->account_id) && is_parent_of($request->account_id) && $request->account_id != 'session_account_id') {
            $account_id = $request->account_id;
        } elseif (session('role_level') == 'Admin' && ! empty($request->account_id)) {
            if ($request->account_id == 'session_account_id') {
                $account_id = session('account_id');
            } else {
                $account_id = $request->account_id;
            }
            if ($account_id === 1) {
                $account_id = 12;
            }
        }

        if (! empty($request->buy_product_id) && ! empty($request->account_id)) {
            $this->data['document_lines'][0]->product_id = $request->buy_product_id;
            if (session('role_level') == 'Admin') {
                $account = dbgetaccount($account_id);
                if ($account->partner_id != 1) {
                    $this->data['document']->reseller_user = $account_id;
                    $account_id = $account->partner_id;
                }
            }
        }

        $this->data['document_currency'] = 'ZAR';
        if ($this->data['db_table'] == 'crm_supplier_import_documents') {
            $this->data['import_shipments'] = \DB::table('crm_import_shipments')->orderBy('shipment_date', 'desc')->orderBy('id', 'desc')->get();
        }
        if (! empty($account_id)) {
            if ($this->data['db_table'] == 'crm_supplier_documents' || $this->data['db_table'] == 'crm_supplier_import_documents') {
                $account = dbgetsupplier($account_id);
                $this->data['account_id'] = $account->id;
                $this->data['document_currency'] = get_supplier_currency($account_id);
            } else {
                $account = dbgetaccount($account_id);
                $this->data['account_id'] = $account->id;
                $this->data['document_currency'] = get_account_currency($account_id);
            }
            $this->data['company'] = $account->company;
            $this->data['account_id'] = $account->id;
            $this->data['account_type'] = $account->type;
            $this->data['partner_id'] = $account->partner_id;
            if ($this->data['db_table'] == 'crm_supplier_documents' || $this->data['db_table'] == 'crm_supplier_import_documents') {
                $partner = dbgetaccount(1);
            } else {
                $partner = dbgetaccount($account->partner_id);
            }
            if ($this->data['db_table'] == 'crm_supplier_documents' || $this->data['db_table'] == 'crm_supplier_import_documents') {
                $this->data['vat_enabled'] = ($account->taxable) ? 1 : 0;
            } else {
                $this->data['vat_enabled'] = ($account->currency == 'USD') ? 0 : $partner->vat_enabled;
            }

            $this->data['document_footer'] = $partner->invoice_footer;
            if ($partner->id == 1) {
                $this->data['bank_details'] = get_payment_option('Bank Details')->payment_instructions;
            } else {
                $this->data['bank_details'] = '';
            }
            $this->data['customers'] = json_encode(['text' => $account->company, 'value' => $account->id]);
            if ($this->data['db_table'] == 'crm_supplier_documents') {
                $this->data['products'] = get_transaction_products($account_id, 'supplier');
            } elseif ($this->data['db_table'] == 'crm_supplier_import_documents') {
                $this->data['products'] = get_transaction_products($account_id, 'supplier', true);
            } else {
                $this->data['products'] = get_transaction_products($account_id, 'account');
            }
        } else {
            $this->data['account_type'] = '';

            if (session('role_id') == '11') {
                $this->data['partner_id'] = 1;
                $partner = dbgetaccount(1);
            } elseif (session('role_id') == '21') {
                $this->data['partner_id'] = session('parent_id');
                $partner = dbgetaccount(session('parent_id'));
            } else {
                $this->data['partner_id'] = session('account_id');
                $partner = dbgetaccount(session('account_id'));
            }
            $this->data['vat_enabled'] = $partner->vat_enabled;
            $this->data['document_footer'] = $partner->invoice_footer;

            if ($partner->id == 1) {
                $this->data['bank_details'] = get_payment_option('Bank Details')->payment_instructions;
            } else {
                $this->data['bank_details'] = '';
            }
            if ($this->data['db_table'] == 'crm_supplier_documents') {
                $this->data['products'] = get_transaction_products(1, 'supplier');
            } elseif ($this->data['db_table'] == 'crm_supplier_import_documents') {
                $this->data['products'] = get_transaction_products(1, 'supplier', true);
            } else {
                $this->data['products'] = [];
            }
        }

        if (check_access('11') || session('role_level') == 'Admin' && $this->data['name'] != 'supplierdocuments') {
            if (check_access('11')) {
                $accounts = \DB::table('crm_accounts')
                    ->select('id', 'company', 'type', 'payment_method', 'currency')
                    ->where('partner_id', session('account_id'))
                    ->where('status', '!=', 'Deleted')
                    ->where('id', '!=', 1)
                    ->orderBy('type')
                    ->orderBy('company')
                    ->get();
            } else {
                $accounts = \DB::table('crm_accounts')
                    ->select('id', 'company', 'type', 'payment_method', 'currency')
                    ->where('partner_id', $this->data['partner_id'])
                    ->where('status', '!=', 'Deleted')
                    ->where('id', '!=', 1)
                    ->orderBy('type')
                    ->orderBy('company')
                    ->get();
            }

            $list_accounts = [];
            foreach ($accounts as $acc) {
                $acc->type = $acc->type;
                $list_accounts[] = $acc;
            }
        }

        if (session('role_level') == 'Admin') {
            if ($this->data['db_table'] == 'crm_supplier_documents' || $this->data['db_table'] == 'crm_supplier_import_documents') {
                $suppliers = \DB::table('crm_suppliers')
                    ->select('id', 'company', 'taxable', \DB::raw('"Supplier" as type'), 'currency')
                    ->where('status', '!=', 'Deleted')
                    ->orderBy('company')
                    ->get();

                $list_accounts = [];
                foreach ($suppliers as $acc) {
                    $acc->type = $acc->type;
                    $list_accounts[] = $acc;
                }
            }
        }

        if (session('role_id') == '21') {
            $list_accounts = \DB::table('crm_accounts')->where('id', session('account_id'))->get();
        }

        $this->data['accounts'] = $list_accounts;

        $this->data['shipping_companies'] = \DB::table('crm_suppliers')
            ->select('id', 'company', 'taxable', \DB::raw('"Supplier" as type'), \DB::raw('"ZAR" as currency'))
            ->where('status', '!=', 'Deleted')
            ->where('shipping_company', 1)
            ->orderBy('company')
            ->get();

        $this->data['logo'] = '';

        if ($partner->logo > '' && file_exists(uploads_settings_path().$partner->logo)) {
            $this->data['logo'] = settings_url().$partner->logo;
        }

        $this->data['is_supplier'] = 0;
        if ($this->data['db_table'] == 'crm_supplier_documents') {
            $this->data['is_supplier'] = 1;
        }
        if ($this->data['db_table'] == 'crm_supplier_import_documents') {
            $this->data['is_supplier'] = 1;
        }

        $doctype_query = \DB::table('acc_doctypes');
        if ($this->data['db_table'] == 'crm_supplier_documents') {
            $doctype_query->where('doctype', '!=', 'Supplier Invoice');
        } else {
            $doctype_query->whereNotIn('doctype', ['Tax Invoice', 'Credit Note', 'Credit Note Draft']);
        }
        $doctype_query->where('doctable', $this->data['db_table']);

        $doctype_query->orderby('sort_order');
        $this->data['doctypes'] = $doctype_query->pluck('doctype')->toArray();
        $doctypes_from_module = \DB::table('acc_doctypes')->where('can_create', 1)->where('doctable', $this->data['db_table'])->pluck('doctype')->toArray();

        if ($doctypes_from_module && is_array($doctypes_from_module) && count($doctypes_from_module) > 0) {
            $this->data['doctypes'] = $doctypes_from_module;
        }

        if (empty($id) && in_array('Quotation', $doctypes_from_module)) {
            $default_doctype = 'Quotation';
            if ($account && ($account->type == 'customer' || $account->type == 'reseller')) {
                $this->data['doctypes'] = ['Quotation', 'Order'];
                $this->data['doctype'] = 'Quotation';
            }
        }

        if (empty($this->data['doctype'])) {
            $doctypes = collect($this->data['doctypes'])->toArray();
            $this->data['doctype'] = $default_doctype;
            if (! in_array($default_doctype, $doctypes)) {
                $this->data['doctype'] = $doctypes[0];
            }
        }

        if (! empty($request->buy_product_id) && ! empty($request->account_id)) {
            $this->data['doctype'] = 'Order';
        }
        if ($this->data['doctype'] == 'Credit Note') {
            $this->data['doctypes'] = ['Credit Note'];
        }

        if ($this->data['doctype'] == 'Tax Invoice') {
            $this->data['doctypes'] = ['Tax Invoice'];
        }

        if ($this->data['doctype'] == 'Credit Note Draft') {
            $this->data['doctypes'] = ['Credit Note Draft'];
            if (! empty($request->approve) && check_access('1')) {
                $this->data['doctype'] = 'Credit Note';
                $this->data['document']->doctype = 'Credit Note';
            }
        }

        if (is_superadmin()) {
            if (! in_array('Tax Invoice', $this->data['doctypes'])) {
                $this->data['doctypes'][] = 'Tax Invoice';
            }
            if (! in_array('Credit Note', $this->data['doctypes'])) {
                $this->data['doctypes'][] = 'Credit Note';
            }
        }

        $this->data['lead_doctypes'] = ['Quotation'];

        $this->data['form_accounts_url'] = get_menu_url_from_module_id(343);

        if ($account && $account->type == 'lead') {
            $this->data['doctypes'] = ['Quotation'];
            $this->data['doctype'] = 'Quotation';
        }

        if (is_superadmin() || is_dev()) {
            if (! empty($this->data['document']->salesman_id)) {
                $this->data['salesman_ids'] = \DB::table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->orwhere('id', $this->data['document']->salesman_id)->get();
            } else {
                $this->data['salesman_ids'] = \DB::table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->get();
            }
        } else {
            $this->data['salesman_ids'] = \DB::table('erp_users')->where('id', $this->data['document']->salesman_id)->get();
        }

        $this->data['partner_company'] = dbgetaccountcell($this->data['partner_id'], 'company');

        $categories = get_transaction_categories();
        $category_ids = collect($categories)->pluck('id')->toArray();
        $products = \DB::table('crm_products')->where('status', 'Enabled')->whereIn('product_category_id', $category_ids)->orderBy('sort_order')->get();

        $this->data['remove_monthly_totals'] = get_admin_setting('remove_monthly_totals');
        $this->data['tld_list'] = get_supported_tlds();
        $tld_prices = \DB::table('isp_hosting_tlds')->where('action', 'register')->where('api_register', 1)->orderBy('tld')->get();

        $this->data['tld_prices'] = [];
        foreach ($tld_prices as $tld_price) {
            $date = date('Y-m-d');
            $wholesale_full_price = currency($tld_price->wholesale_price);
            $retail_full_price = currency($tld_price->retail_price);

            $wholesale_price = ($wholesale_full_price / 12) * (12 - (intval(date('n', strtotime($date))) - 1));
            $retail_price = ($retail_full_price / 12) * (12 - (intval(date('n', strtotime($date))) - 1));

            $this->data['tld_prices'][] = (object) [
                'tld' => $tld_price->tld,
                'wholesale_full_price' => $wholesale_full_price,
                'retail_full_price' => $retail_full_price,
                'wholesale_price' => $wholesale_price,
                'retail_price' => $retail_price,
            ];
        }
        if (empty($request->doctype) && empty($request->buy_product_id)) {
            // $this->data['doctype'] = '';
        }
        $this->data['categories'] = get_transaction_categories();
        $this->data['categories_usd'] = get_transaction_categories_usd();
        $active_product_ids = \DB::table('crm_products as products')
            ->select('products.*', 'category.id as category_id', 'category.name as category', 'category.department as department')
            ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
            ->where('products.status', 'Enabled')
            ->where('category.is_deleted', 0)
            ->where('category.not_for_sale', 0)
            ->where('products.not_for_sale', 0)
            ->orderBy('category.sort_order')
            ->orderBy('products.sort_order')
            ->pluck('id')->toArray();

        $bundle_details = \DB::table('crm_product_bundle_details')->whereIn('product_id', $active_product_ids)->get();
        $bundle_ids = $bundle_details->pluck('product_bundle_id')->unique()->toArray();
        $this->data['product_bundles'] = \DB::table('crm_product_bundles')->whereIn('id', $bundle_ids)->get();
        $this->data['product_bundle_details'] = $bundle_details;

        $this->data['enable_discounts'] = get_admin_setting('enable_discounts');

        if (session('role_level') == 'Admin') {
            if ($this->data['module_id'] == 746) {
                $session_instance = session('instance');
                $session_instance->currency_symbol = '$';
                $session_instance->currency_decimals = 3;
                // $session_instance->currency = "USD";
                session(['instance' => $session_instance]);

                $this->data['vat_enabled'] = 0;

                return view('__app.components.transaction_import', $this->data);
            }
        }

        $this->data['currency_symbol'] = 'R';
        if (session('instance')->id == 6) {
            $this->data['currency_symbol'] = '$';
        }
        $this->data['remove_tax_fields'] = get_admin_setting('remove_tax_fields');

        if ($this->data['db_table'] == 'crm_supplier_documents' || $this->data['db_table'] == 'crm_supplier_import_documents') {
            $this->data['ledger_accounts'] = \DB::table('acc_ledger_accounts')->where('is_deleted', 0)->get();
        }
        if (is_dev()) {
            return view('__app.components.transaction_dev', $this->data);
        }

        return view('__app.components.transaction', $this->data);
    }

    public function validateForm($forms = [])
    {
        if (count($forms) <= 0) {
            $forms = $this->data['module_fields'];
        }

        $rules = [];
        foreach ($forms as $form) {
            if ($form['required']) {
                if ($form['field_type'] == 'email') {
                    $rules[$form['field']] = 'email';
                } elseif ($form['field_type'] == 'integer' || $form['field_type'] == 'currency') {
                    $rules[$form['field']] = 'numeric';
                } elseif ($form['field_type'] == 'date' || $form['field_type'] == 'datetime') {
                    $rules[$form['field']] = 'date';
                } else {
                    $rules[$form['field']] = 'required';
                }
            }
        }

        return $rules;
    }

    public function getImport(Request $request)
    {
        if (! $this->data['access']['is_import']) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'No Access']);
            } else {
                return Redirect::back()->with('message', 'No Access')->with('status', 'error');
            }
        }
        $form = new \ErpForm($this->data, $request->all());
        $data = $form->getImportForm();

        return view('__app.components.import', $data);
    }

    public function postImport(Request $request)
    {
        try {
            $data = $request->all();

            unset($data['import']);
            unset($data['_token']);

            if (! $this->data['access']['is_import']) {
                if ($request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => 'No Access']);
                } else {
                    return Redirect::back()->with('message', 'No Access')->with('status', 'error');
                }
            }

            ini_set('max_execution_time', 0);

            if (empty($request->file('import'))) {
                return json_alert('Please select file to Upload!', 'error');
            }

            $file = $request->file('import');
            $filename = $file->getClientOriginalName();

            $filename = file_name_formatted($filename);

            if (! str_ends_with($filename, '.xlsx')) {
                return json_alert('File needs to be xlsx format.', 'error');
            }

            $uploadSuccess = $file->move(uploads_path($this->data['module_id']), $filename);

            if (! $uploadSuccess) {
                return json_alert('Upload Failed!', 'error');
            }

            if ($this->data['module_id'] != 725) { // RATES COMPLETE
                $db = new \DBEvent($this->data['module_id']);
            } else {
                $data['gateway'] = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $data['gateway_uuid'])->pluck('gateway')->first();
            }

            shell_exec('chmod 777 '.uploads_path($this->data['module_id']).$filename);

            $usd_exchange = convert_currency_usd_to_zar(1);
            $records = (new FastExcel)->import(uploads_path($this->data['module_id']).$filename, function ($line) use ($data, $usd_exchange) {
                $line = array_change_key_case($line, CASE_LOWER);
                if (! empty($data) && is_array($data) && count($data) > 0) {
                    foreach ($data as $k => $v) {
                        $line[$k] = $v;
                    }
                }
                if ($this->data['module_id'] == 89) {
                    if (empty($line['number'])) {
                        return false;
                    }
                }

                if ($this->data['module_id'] == 739) {
                    if (! empty($line['call_time'])) {
                        $line['call_time'] = $line['call_time']->format('Y-m-d H:i:s');
                    }

                    if (! empty($line['charged_time'])) {
                        $line['charged_time'] = $line['charged_time']->format('H:i:s');
                    }

                    if (! empty($line['duration']) && empty($line['duration_minutes'])) {
                        $line['duration_minutes'] = $line['duration'] / 60;
                    }
                    if (empty($line['duration']) && empty(! $line['duration_minutes'])) {
                        $line['duration'] = $line['duration_minutes'] * 60;
                    }

                    if (! empty($line['destination'])) {
                        $line['description'] = $line['destination'];
                    }
                    if (isset($line['destination'])) {
                        unset($line['destination']);
                    }
                }

                foreach ($line as $k => $v) {
                    if (empty($k)) {
                        unset($line[$k]);
                    }
                }
                if ($this->data['module_id'] != 739) {
                    if (isset($line['admin_rate'])) {
                        $line['cost'] = $line['admin_rate'];
                        unset($line['admin_rate']);
                    }
                    if (isset($line['rate'])) {
                        $line['cost'] = $line['rate'];
                        unset($line['rate']);
                    }
                }

                if ($this->data['module_id'] == 725) {// RATES COMPLETE
                    if ($data['gateway'] == 'BITCO') {
                        $line['destination_id'] = str_replace(['^', '.', '*'], '', $line['destination_id']);
                        //$line['cost'] = $line['cost'] / 100;
                    }

                    $line['cost'] = trim(str_replace('R', '', $line['cost']));
                    if ($data['gateway'] == 'HUGE') {
                        $line['cost'] = trim(str_replace('ZAR', '', $line['cost']));
                        unset($line['prefix']);
                    }

                    if ($data['gateway'] == 'FIRSTNET') {
                        if (substr($line['destination_id'], 0, 1) == '0') {
                            $line['destination_id'] = substr($line['destination_id'], 1);
                            $line['destination_id'] = '27'.$line['destination_id'];
                        }
                        if ($line['destination_id'] == 112) {
                            $line['destination_id'] = '27'.$line['destination_id'];
                        }
                    }
                    if ($data['gateway'] == 'SESSION') {
                        if (str_contains($line['destination_id'], 'D')) {
                            return false;
                        }
                    }

                    if ($data['gateway'] == 'VOIPPRO') {
                        $line['currency'] = 'usd';
                    }

                    if ($data['gateway'] == 'LANDO') {
                        $line['currency'] = 'usd';
                    }
                    if ($data['gateway'] == 'MCM') {
                        $line['currency'] = 'usd';
                    }
                    if ($data['gateway'] == 'BRIDGEVOICE') {
                        $line['currency'] = 'usd';
                    }

                    if (! empty($line['country'])) {
                        $line['country'] = strtolower($line['country']);
                    }

                    if (! empty($line['destination'])) {
                        $line['destination'] = strtolower($line['destination']);
                    }

                    if (! empty($line['currency']) && $line['currency'] == 'usd') {
                        $line['cost'] = $line['cost'] * $usd_exchange;
                        unset($line['currency']);
                    }

                    unset($line['gateway']);
                    if (! empty($line['destination_id']) && is_numeric($line['destination_id'])) {
                        $line['cost'] = str_replace(',', '.', $line['cost']);
                        if (empty($line['cost'])) {
                            $line['cost'] = 0;
                        }
                        // international rates
                        $exists = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $line['gateway_uuid'])->where('destination_id', $line['destination_id'])->count();

                        if (! $exists) {
                            \DB::connection('pbx')->table('p_rates_complete')->insert($line);
                        } else {
                            \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $line['gateway_uuid'])->where('destination_id', $line['destination_id'])->update($line);
                        }
                    } elseif (! empty($line['destination_id']) && ! is_numeric($line['destination_id'])) {
                        /*
                            SOUTH AFRICA	GNT	Neotel Geographic
                            SOUTH AFRICA	GRNTL	Neotel Local
                            SOUTH AFRICA	GRTKL	Telkom Local
                            SOUTH AFRICA	GTK	Telkom Geographic
                            SOUTH AFRICA	MCC	SA Mobile - Cell C
                            SOUTH AFRICA	MMTN	SA Mobile - MTN
                            SOUTH AFRICA	MTK	SA Mobile - Telkom
                            SOUTH AFRICA	MVC	SA Mobile - Vodacom
                        */
                        if ($line['destination_id'] == 'GNT') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'like', '%neotel%')->get();
                        }

                        if ($line['destination_id'] == 'GTK') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'fixed telkom')->get();
                        }
                        if ($line['destination_id'] == 'MCC') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'mobile cellc')->get();
                        }
                        if ($line['destination_id'] == 'MMTN') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'mobile mtn')->get();
                        }
                        if ($line['destination_id'] == 'MTK') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'mobile telkom')->get();
                        }
                        if ($line['destination_id'] == 'MVC') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'mobile vodacom')->get();
                        }

                        if ($line['destination_id'] == 'VG') {
                            // fixed other
                            $local_destinations = ['fixed telkom', 'fixed liquid', 'mobile cellc', 'mobile mtn', 'mobile vodacom', 'fixed tollfree', 'fixed sharecall', 'mobile telkom'];
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', 'south africa')
                                ->where('id', 'not like', '2786%')
                                ->whereNotIn('destination', ['fixed tollfree', 'fixed sharecall'])
                                ->whereNotIn('destination', $local_destinations)
                                ->get();
                        }

                        if (! empty($destination_lines)) {
                            $destination_line_ids = collect($destination_lines)->pluck('id')->toArray();
                            \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $line_data['gateway_uuid'])->whereIn('destination_id', $destination_line_ids)->delete();
                            $network_lines = [];
                            foreach ($destination_lines as $destination_line) {
                                $line_data = $line;
                                $line_data['destination_id'] = $destination_line->id;
                                $line_data['destination'] = $destination_line->destination;
                                $network_lines[] = $line_data;
                            }

                            $chunks = collect($network_lines)->chunk(100);

                            foreach ($chunks as $chunk) {
                                \DB::connection('pbx')->table('p_rates_complete')->insertOrIgnore($chunk->toArray());
                            }
                        }
                    } elseif (! empty($line['destination_type'])) {
                        if ($line['destination_type'] == 'mobile') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'like', '%mobile%')->get();
                        }

                        if ($line['destination_type'] == 'fixed') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->where('destination', 'not like', '%mobile%')->get();
                        }
                        if ($line['destination_type'] == 'all') {
                            $destination_lines = \DB::connection('pbx')->table('p_rates_destinations')
                                ->where('country', strtolower($line['country']))->get();
                        }
                        unset($line['destination_type']);
                        foreach ($destination_lines as $destination_line) {
                            $line_data = $line;
                            $line_data['destination_id'] = $destination_line->id;
                            $line_data['destination'] = $destination_line->destination;

                            // international rates
                            $exists = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $line_data['gateway_uuid'])->where('destination_id', $line_data['destination_id'])->count();

                            if (! $exists) {
                                \DB::connection('pbx')->table('p_rates_complete')->insert($line_data);
                            } else {
                                \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $line_data['gateway_uuid'])->where('destination_id', $line_data['destination_id'])->update($line_data);
                            }
                        }
                    }
                } else {
                    foreach ($line as $k => $v) {
                        if (empty($v)) {
                            unset($line[$k]);
                        }
                    }
                    dbinsert($this->data['db_table'], $line);
                }
            });

            if ($records instanceof \Illuminate\Http\JsonResponse) {
                return $records;
            }
            if ($records[0] instanceof \Illuminate\Http\JsonResponse) {
                return $records[0];
            }
            $after_import_functions = \DB::connection('default')->table('erp_form_events')->where('module_id', $this->data['module_id'])->where('type', 'afterimport')->get();
            foreach ($after_import_functions as $after_import_function) {
                $function_name = $after_import_function->function_name;
                $function_name();
            }

            return json_alert('Csv Imported Successful !');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_log($ex->getMessage());
            exception_log($ex->getTraceAsString());

            return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
        }
    }

    public function getCellEditor(Request $request)
    {
        $row = $this->model->getRow($request->id);

        $field = false;

        foreach ($this->data['module_fields'] as $module_field) {
            $field_name = $request->field;
            if (str_starts_with($field_name, 'join_')) {
                $field_name = str_replace('join_', '', $field_name);
            }
            if ($module_field['field'] == $field_name) {
                $field = $module_field;
            }
        }

        if (! $field) {
            return '';
        }

        if ($this->data['module_id'] == 760) {
            $report_conn = \DB::connection('system')->table('erp_instances')->where('name', $request->task_company_name)->pluck('db_connection')->first();
            $this->data['report_filter_conn'] = $report_conn;
        }

        $form = new \ErpForm($this->data, $request->all());
        if (empty($request->id)) {
            $form->setEditType('add');
        } else {
            $form->setEditType('edit');
        }

        $form->setRow($row);

        $form_data = $form->getFormField($field, true);

        //  aa($form_data);
        return $form_data['form_html'].'<script>'.$form_data['form_script'].'</script>';
    }

    public function postSaveCell(Request $request)
    {
        $field_type = $this->data['db_module_fields']->where('field', $request->field)->pluck('field_type')->first();
        if (! empty($request->value)) {
            if ($field_type == 'date') {
                $request->value = date('Y-m-d', strtotime(str_replace('(South Africa Standard Time)', '', $request->value)));
            }
            if ($field_type == 'datetime') {
                $request->value = date('Y-m-d H:i:s', strtotime(str_replace('(South Africa Standard Time)', '', $request->value)));
            }
        }

        $row = $this->model->getRow($request->id);

        $cell = $request->field;
        if ($request->field == 'parent_id' && $request->value == $request->id) {
            return json_alert('Invalid drop node', 'warning');
        }
        $row[$cell] = $request->value;

        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');
        $request_object->request->add($row);
        $request_object->request->add(['post_save_cell' => 1]);

        $result = $this->postSave($request_object);

        return $result;
    }

    public function updateTreeData(Request $request)
    {
        \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->id)->update([$request->field => $request->value]);
        \DB::connection($this->data['connection'])->table($this->data['db_table'])->whereRaw($this->data['db_key'].'='.$this->data['tree_data_key'])->update([$this->data['tree_data_key'] => 0]);
    }

    public function postSaveRow(Request $request)
    {
        $post_data = $request->all();

        $row = [];
        $syncfusion_data = $request->syncfusion_data;
        unset($post_data['syncfusion_data']);
        foreach ($post_data as $field => $value) {
            $field_name = str_replace('join_', '', $field);
            $field_type = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['module_id'])->where('field', $field_name)->pluck('field_type')->first();
            if (isset($syncfusion_data[$field])) {
                $row[$field] = $syncfusion_data[$field];
            } elseif (str_starts_with($field, 'join_')) {
                $field = str_replace('join_', '', $field);
                if (isset($syncfusion_data[$field])) {
                    $row[$field] = $syncfusion_data[$field];
                } else {
                    $filter_datasource = get_module_field_options($this->data['module_id'], $field);
                    if (is_array($value)) {
                        $list = [];
                        foreach ($value as $v) {
                            $foreign_id = collect($filter_datasource)->where('text', $v)->pluck('value')->first();
                            $list[] = $foreign_id;
                        }
                        $row[$field] = implode(',', $list);
                    } else {
                        $foreign_id = collect($filter_datasource)->where('text', $value)->pluck('value')->first();
                        $row[$field] = $foreign_id;
                    }
                }
            } else {
                if ($field_type == 'boolean' && $value == 'No') {
                } else {
                    $row[$field] = $value;
                }
            }
        }

        foreach ($this->data['module_fields'] as $module_field) {
            $module_field = (object) $module_field;
            if ($module_field->field_type == 'none' && $row[$this->data['db_key']]) {
                $row[$module_field->field] = \DB::table($this->data['db_table'])->where($this->data['db_key'], $row[$this->data['db_key']])->pluck($module_field->field)->first();
            }
        }

        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');
        $request_object->request->add($row);

        $result = $this->postSave($request_object);

        return $result;
    }

    public function postSaveHeader(Request $request)
    {
        if (check_access('1,31')) {
            if ($request->grid_mode == 'layout') {
                \DB::connection('default')->table('erp_module_fields')->where('field', $request->field)->where('module_id', $this->data['module_id'])->update(['label' => $request->label]);
            } else {
                $report = \DB::connection('default')->table('erp_reports')->where('id', $request->report_id)->get()->first();
                $column_headers = json_decode($report->column_headers);
                if (empty($column_headers)) {
                    $column_headers = [];
                }
                $column_headers[$request->field] = $request->label;

                $column_headers = json_encode($column_headers);
                \DB::connection('default')->table('erp_reports')->where('id', $request->report_id)->update(['column_headers' => $column_headers]);
            }

            return json_alert('saved');
        }
    }

    public function postSave(Request $request)
    {
        try {
            // $formio = true;

            $new_record = 1;
            if (! empty($request->id)) {
                $new_record = 0;
            }

            $detail_edit = false;
            if (! empty($request->detail_edit)) {
                $detail_edit = true;
                $request->request->remove('detail_edit');
            }

            if (! empty($request->emailonly)) {
                email_document_pdf($request->id);

                return json_alert('Document Emailed');
            }

            $form_id = $this->data['db_key'];
            session(['menu_route' => $this->data['menu_route']]);
            if ($form_id == 'id' || str_contains($form_id, '_id')) {
                $id = (is_numeric($request->input($form_id))) ? $request->input($form_id) : null;
            } else {
                $id = $request->input($form_id);
            }

            if (empty(session('webform_module_id'))) {
                $allow_access = true;
                if (! $id) {
                    if ($this->data['access']['is_add'] == 0) {
                        $allow_access = false;
                    }
                } else {
                    if ($this->data['access']['is_edit'] == 0) {
                        $allow_access = false;
                    }
                }

                if (! empty($request->post_save_cell)) {
                    $allow_access = 1;
                    $request->request->remove('post_save_cell');
                }

                if ($this->data['tree_data_key'] && isset($request->insert_at_id)) {
                    $insert_at_row = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->insert_at_id)->get()->first();
                    $has_sort_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['module_id'])->where('field', 'sort_order')->count();
                    if ($has_sort_field) {
                        \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '>=', $insert_at_row->sort_order)->increment('sort_order');
                        $request->request->add(['sort_order' => $insert_at_row->sort_order]);
                    }

                    $request->request->add([$this->data['tree_data_key'] => $insert_at_row->{$this->data['tree_data_key']}]);
                    //$request->request->remove('insert_at_id');
                }

                if (! $allow_access) {
                    if ($request->ajax()) {
                        return response()->json(['status' => 'error', 'message' => 'No Access']);
                    } else {
                        return Redirect::back()->with('message', 'No Access')->with('status', 'error');
                    }
                }
            }
            $settings = [];
            if ($this->data['db_table'] == 'crm_supplier_import_documents' || $this->data['db_table'] == 'crm_supplier_documents' || $this->data['db_table'] == 'crm_documents') {
                $settings = ['validate_document' => true];
            }

            if ($this->data['edit_approval'] && session('role_level') == 'Admin' && ! is_superadmin()) {
                $display_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['module_id'])->where('display_field', 1)->pluck('field')->first();
                $reference = '';
                if ($this->data['module_id'] == 1837) {
                    $cashbook_name = \DB::table('acc_cashbook')->where('id', $request->cashbook_id)->pluck('name')->first();
                    if (! empty($id)) {
                        $title = $cashbook_name.' Edit '.$request->{$display_field};
                    } else {
                        $title = $cashbook_name.' Add '.$request->{$display_field};
                    }
                    $reference = $trx->total;
                    if (! empty($request->account_id)) {
                        $name = \DB::table('crm_accounts')->where('id', $request->account_id)->pluck('company')->first();
                        $reference .= ' '.$name;
                    }
                    if (! empty($request->supplier_id)) {
                        $name = \DB::table('crm_suppliers')->where('id', $request->supplier_id)->pluck('company')->first();
                        $reference .= ' '.$name;
                    }
                    if (! empty($request->ledger_account_id)) {
                        $name = \DB::table('acc_ledger_accounts')->where('id', $request->ledger_account_id)->pluck('name')->first();
                        $reference .= ' '.$name;
                    }
                } else {
                    if (! empty($id)) {
                        $title = $this->data['name'].' Edit '.$request->{$display_field};
                    } else {
                        $title = $this->data['name'].' Add '.$request->{$display_field};
                    }
                }

                $data = [
                    'module_id' => $this->data['module_id'],
                    'row_id' => $id,
                    'title' => $title,
                    'processed' => 0,
                    'requested_by' => get_user_id_default(),
                    'post_data' => json_encode($request->all()),
                ];
                (new \DBEvent)->setTable('crm_approvals')->save($data);

                return json_alert('Submitted for approval');
            }

            $db = new \DBEvent($this->data['module_id'], $settings);

            $result = $db->save($request);

            /*
            // update sorting on aftersave
            if (!empty($result) && is_array($result) && !empty($result['id'])) {

                if($this->data['connection'] == 'default'){


                    $has_sort_field = collect($this->data['module_fields'])->contains(function ($item) {
                        return $item['field'] === 'sort_order';
                    });

                    if($has_sort_field){

                        \DB::connection($this->data['connection'])->statement("SET @counter = 0;");
                        \DB::connection($this->data['connection'])->statement("UPDATE ".$this->data['db_table']."
                        SET sort_order = (@counter := @counter + 1)
                        ORDER BY sort_order;");
                    }

                }

            }
            */

            if ($result instanceof \Illuminate\Http\JsonResponse) {
                return $result;
            } elseif (! is_array($result) || empty($result['id'])) {
                return response()->json(['status' => 'warning', 'message' => $result]);
            }
            if (! empty(session('webform_module_id'))) {
                if (session('troubleshooter_form') || session('webform_module_id') == 580) {
                    return response()->json(['status' => 'success', 'message' => 'Thank you for your submission.', 'row_id' => $result['id']]);
                } else {
                    return response()->json(['status' => 'success', 'message' => 'The form has been saved.', 'reload' => '/', 'row_id' => $result['id']]);
                }
            } else {
                if ($this->data['module_id'] == 353) {
                    $doctype = \DB::table('crm_documents')->where('id', $result['id'])->pluck('doctype')->first();
                    if ($doctype == 'Tax Invoice') {
                        $activations = \DB::table('sub_activations')->where('invoice_id', $result['id'])->where('status', 'Pending')->count();
                        if ($activations) {
                            if (session('role_level') == 'Admin') {
                                $activations_url = get_menu_url_from_module_id(1901);
                            } else {
                                $activations_url = get_menu_url_from_module_id(554);
                            }

                            return response()->json(['status' => 'success', 'message' => 'Document saved.', 'new_tab' => '/'.$activations_url.'?invoice_id='.$result['id']]);
                        } else {
                            return response()->json(['status' => 'success', 'message' => 'Document saved.', 'new_tab' => '/'.$this->data['menu_route'].'?id='.$result['id']]);
                        }
                    } else {
                        return response()->json(['status' => 'success', 'message' => 'Document saved.', 'new_tab' => '/'.$this->data['menu_route'].'?id='.$result['id']]);
                    }
                }

                $format_response_event = \DB::connection('default')->table('erp_form_events')->where('module_id', $this->data['module_id'])->where('type', 'format_response')->get()->first();
                $refresh_module_id = $this->data['module_id'];
                if ($request->module_id) {
                    $refresh_module_id = $request->module_id;
                    $master_module_id = \DB::table('erp_cruds')->where('detail_module_id', $refresh_module_id)->pluck('id')->first();
                    if ($master_module_id) {
                        $refresh_module_id = $master_module_id;
                    }
                }

                if ($this->data['module_id'] == '1875') {
                    return json_alert('Saved.', 'success', ['reload_grid_config' => 'guides_accordion_refresh']);
                } elseif ($this->data['module_id'] == '749') {
                    return json_alert('Saved.', 'success', ['reload_grid_config' => 'reload_grid_config'.$refresh_module_id]);
                } elseif ($this->data['module_id'] == '761') {
                    $response = ['status' => 'success', 'message' => 'Record Saved.', 'reload_conditional_styles' => true, 'module_id' => $refresh_module_id];
                } elseif ($this->data['module_id'] == '526' && ! empty($request->module_id)) {
                    $view = \DB::table('erp_grid_views')->where('id', $result['id'])->get()->first();
                    $beforesave_row = session('event_db_record');

                    if (empty($id)) {
                        $response = ['status' => 'success', 'message' => 'Record Saved.', 'layout_create' => true, 'row_id' => $result['id']];
                    } else {
                        $response = ['status' => 'success', 'message' => 'Record Saved.', 'callback_function' => 'get_sidebar_data'.$beforesave_row->module_id, 'row_id' => $result['id']];
                    }
                } elseif ($this->data['module_id'] == '488' && ! empty($request->module_report)) {
                    $report_url = get_report_link($result['id']);
                    $response = ['status' => 'success', 'message' => 'Record Saved.', 'new_tab' => $report_url, 'row_id' => $result['id']];
                } elseif ($this->data['module_id'] == '488') {
                    $response = ['status' => 'success', 'message' => 'Record Saved.', 'callback_function' => 'load_report', 'row_id' => $result['id']];
                } elseif ($this->data['module_id'] == '799') {
                    $response = ['status' => 'success', 'message' => 'Record Saved.', 'callback_function' => 'get_sidebar_data'.$refresh_module_id];
                } elseif ($this->data['module_id'] == '348') {
                    $response = ['status' => 'success', 'message' => 'Record Saved.', 'callback_function' => 'ajax_refresh_color_scheme'];
                } elseif ($format_response_event) {
                    $format_response_event_function = $format_response_event->function_name;
                    $response = $format_response_event_function($request, $result['id']);
                } else {
                    $master_module_id = \DB::connection('default')->table('erp_cruds')->where('detail_module_id', $this->data['module_id'])->pluck('id')->first();
                    $response = [
                        'status' => 'success',
                        'message' => 'Record Saved.',
                        'row_id' => $result['id'],
                        'module_id' => $this->data['module_id'],
                        'close_dialog' => 1,
                    ];

                    if (! empty($master_module_id)) {
                        $response['master_module_id'] = $master_module_id;
                    }
                }

                if ($this->data['module_id'] == 500) {
                    $response['callback_function'] = 'refresh_guide_content'.$request->id;
                }

                if (empty($id)) {
                    $response['grid_refresh'] = 1;
                }

                if (! empty($request->popup_form_field)) {
                    $response['callback_function_data'] = 'update_formio_val'.$request->popup_form_module_id;
                    $response['formio_field'] = $request->popup_form_field;
                }

                return response()->json($response);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_log($ex->getMessage());
            exception_log($ex->getTraceAsString());

            return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
        }
    }

    public function postApproveTransaction(Request $request)
    {
        if ($this->model == null) {
            $this->initModel($request->segment(1));
        }
        $record_access = $this->model->singleRecordAccess($request->id);

        if (! $this->data['access']['is_approve'] || ! $record_access) {
            return response()->json(['status' => 'error', 'message' => 'No Access']);
        }

        $document = \DB::table($this->data['db_table'])->where('id', $request->id)->get()->first();
        $id = $request->id;

        $manager_approve_doctypes = \DB::table('acc_doctypes')->where('approve_manager', 1)->pluck('doctype')->toArray();
        $manager_admin = (is_superadmin() || is_manager()) ? true : false;
        //if(is_dev()){
        //   $manager_admin = false;
        //}
        if (! $manager_admin && in_array($document->doctype, $manager_approve_doctypes)) {
            \DB::table('crm_documents')->where('id', $request->id)->update(['approval_requested_by' => session('user_id')]);
            process_document_approvals();

            return json_alert('Approval Request submitted');
        }

        $documents_url = get_menu_url_from_table('crm_documents');
        $subscriptions_url = get_menu_url_from_table('sub_services');
        $delivery_url = get_menu_url_from_table('sub_activations');
        if ($document->doctype == 'Credit Note Draft' && empty($document->credit_note_reason)) {
            return json_alert('Credit Note Reason required', 'warning');
        }
        if ($document->doctype == 'Invoice' || $document->doctype == 'Tax Invoice') {
            return json_alert('Document already converted to a Invoice', 'error');
        }

        if ($document->doctype == 'Order') {
            if ($manager_admin || check_access(2)) {
            } else {
                return json_alert('Document cannot be converted, no access', 'error');
            }
        }

        if (($document->doctype == 'Quotation' || $document->doctype == 'Order' || $document->doctype == 'Tax Invoice') && $document->custom_prices && ! $document->custom_prices_approved) {
            return json_alert('Document cannot be converted, custom prices needs to be approved', 'error');
        }

        $allow_approve = ((! is_superadmin() && ! in_array($document->doctype, $manager_approve_doctypes)) || is_superadmin() || is_manager());

        if (! $allow_approve) {
            return json_alert('No approval access', 'error');
        }

        $lines_table = ($this->data['db_table'] == 'crm_supplier_documents') ? 'crm_supplier_document_lines' : 'crm_document_lines';
        $document_lines = \DB::table($lines_table)->where('document_id', $document->id)->get();
        $document_lines = sort_product_rows($document_lines);

        $transaction_request = (array) $document;
        unset($transaction_request['contract_period']);
        if (! str_contains($this->data['db_table'], 'supplier')) {
            $transaction_request['docdate'] = date('Y-m-d');
        }

        foreach ($document_lines as $index => $line) {
            $transaction_request['qty'][$index] = $line->qty;
            $transaction_request['price'][$index] = $line->price;
            $transaction_request['full_price'][$index] = $line->full_price;
            $transaction_request['product_id'][$index] = $line->product_id;
            $transaction_request['description'][$index] = $line->description;
            if ($lines_table == 'crm_document_lines') {
                $transaction_request['contract_period'][$index] = $line->contract_period;
                $transaction_request['domain_tld'][$index] = $line->domain_tld;
            }
            if (! empty($line->cdr_destination)) {
                $transaction_request['cdr_destination'][$index] = $line->cdr_destination;
            }
        }

        if ($document->doctype == 'Order') {
            if ($document->billing_type == 'Call Profits') {
                \DB::table('crm_documents')->where('id', $request->id)->update(['doctype' => 'Credit Note', 'completed' => 1]);

                return json_alert('Document created.');
            }
            if (! empty($document->billing_type) || $document->subscription_created == 1) {
                return json_alert('Already Provisioned.', 'warning');
            }
            if (date('Y-m-01') == date('Y-m-01', strtotime($document->docdate))) {
                \DB::table('crm_documents')->where('id', $request->id)->where('completed', 0)->update(['doctype' => 'Tax Invoice', 'completed' => 1]);
            } else {
                \DB::table('crm_documents')->where('id', $request->id)->where('completed', 0)->update(['doctype' => 'Tax Invoice', 'docdate' => date('Y-m-d'), 'completed' => 1]);
            }
            provision_auto($request->id);
            $transaction_request['doctype'] = 'Tax Invoice';
            $request_object = new \Illuminate\Http\Request;
            $request_object->setMethod('POST');
            foreach ($transaction_request as $key => $value) {
                $request_object->request->add([$key => $value]);
            }

            module_log(353, $id, 'updated', 'Tax Invoice approved');
            $result = $this->postSave($request_object);

            return $result;
        }

        if ($document->doctype == 'Quotation') {
            $transaction_request['doctype'] = 'Order';
            $request_object = new \Illuminate\Http\Request;
            $request_object->setMethod('POST');
            foreach ($transaction_request as $key => $value) {
                $request_object->request->add([$key => $value]);
            }

            module_log(353, $id, 'updated', 'Order approved');

            return $this->postSave($request_object);
        }

        if ($document->doctype == 'Credit Note Draft') {
            $transaction_request['doctype'] = 'Credit Note';
            $request_object = new \Illuminate\Http\Request;
            $request_object->setMethod('POST');
            foreach ($transaction_request as $key => $value) {
                $request_object->request->add([$key => $value]);
            }

            module_log(353, $id, 'updated', 'Credit Note approved');

            return $this->postSave($request_object);
        }

        if ($document->doctype == 'Supplier Order') {
            $transaction_request['doctype'] = 'Supplier Invoice';
            $request_object = new \Illuminate\Http\Request;
            $request_object->setMethod('POST');

            foreach ($transaction_request as $key => $value) {
                $request_object->request->add([$key => $value]);
            }

            module_log(354, $id, 'updated', 'Supplier Invoice approved');

            return $this->postSave($request_object);
        }
    }

    public function postDuplicate(Request $request)
    {
        $record_access = $this->model->singleRecordAccess($request->id);

        if (! $this->data['access']['is_add'] || ! $record_access) {
            return response()->json(['status' => 'error', 'message' => 'No Access']);
        }

        if (isset($request->code)) {
            $column = 'code';
        } else {
            $column = 'name';
        }

        $response = duplicate_row($this->data['db_table'], $request->id, $column);

        $newData = ['module_id' => $this->data['module_id']];
        $master_module_id = \DB::connection('default')->table('erp_cruds')->where('detail_module_id', $this->data['module_id'])->pluck('id')->first();

        if (! empty($master_module_id)) {
            $newData['master_module_id'] = $master_module_id;
        }
        if ($this->data['module_id'] == '488' || $this->data['module_id'] == '526' || $this->data['module_id'] == '799') {
            $row = $this->model->getRow($request->id);
            $newData['callback_function'] = 'get_sidebar_data'.$row['module_id'];
        }
        $data = $response->getData(true);

        $data = array_merge($data, $newData);

        $response->setData($data);

        return $response;
    }

    public function getCancelForm(Request $request, $menu, $id)
    {
        session(['menu_route' => $this->data['menu_route']]);
        $record_access = $this->model->singleRecordAccess($id);
        if (! $this->data['access']['is_delete'] || ! $record_access) {
            return response()->json(['status' => 'error', 'message' => 'No Access2']);
        }

        if ($this->data['db_table'] == 'sub_services') { // subscriptions
            $sub = \DB::table('sub_services')->where('id', $id)->get()->first();

            if ($sub->provision_type == 'domain_name') {
                return json_alert('Domain name is linked to hosting, please cancel hosting subscription.', 'error');
            }

            if (! empty($sub->to_cancel)) {
                return json_alert('Subscription already cancelled.', 'error');
            }
            if ($sub->status == 'Deleted') {
                return json_alert('Subscription deleted.', 'error');
            }
        }

        if ($this->data['db_table'] == 'crm_accounts') { // accounts
            $deleted = \DB::table('crm_accounts')->where(['id' => $id, 'status' => 'Deleted'])->count();

            if ($deleted) {
                return json_alert('Account already deleted.', 'error');
            }
            $cancelled = \DB::table('crm_accounts')->where(['id' => $id, 'account_status' => 'Cancelled'])->count();

            if ($cancelled) {
                return json_alert('Account already cancelled.', 'error');
            }
        }
        $data = $this->data;
        $data['id'] = $id;

        return view('__app.components.cancel_form', $data);
    }

    public function postCancelForm(Request $request)
    {
        // aa('postCancelForm');
        $id = $request->id;
        session(['menu_route' => $this->data['menu_route']]);
        $record_access = $this->model->singleRecordAccess($request->id);
        if (! $this->data['access']['is_delete'] || ! $record_access) {
            return response()->json(['status' => 'error', 'message' => 'No Access']);
        }

        $uploads_table = $this->data['db_table'];
        if (empty($uploads_table)) {
            return json_alert('No Access', 'error');
        }

        if ($this->data['db_table'] == 'crm_accounts') {
            if (empty($request->delete_reason)) {
                return json_alert('Please enter reason for cancellation', 'warning');
            }

            module_log(343, $id, 'Cancel', $request->delete_reason);
        }

        if ($this->data['db_table'] == 'sub_services') {
            if (empty($request->delete_reason)) {
                return json_alert('Please enter reason for cancellation', 'warning');
            }

            module_log(334, $request->id, 'cancelled', 'Cancel reason: '.$request->delete_reason);
        }

        if (is_superadmin() && ! empty($request->manager_delete)) {
            $request_object = new \Illuminate\Http\Request;
            $request_object->setMethod('POST');
            $request_object->request->add(['id' => $id]);
            $request_object->request->add(['manager_override' => 1]);
            $request_object->request->add(['from_cancel' => 1]);

            return $this->postDelete($request_object);
        } elseif (is_superadmin() && ! empty($request->manager_override)) {
            $request_object = new \Illuminate\Http\Request;
            $request_object->setMethod('POST');
            $request_object->request->add(['id' => $id]);
            $request_object->request->add(['manager_override' => 1]);

            return $this->postCancel($request_object);
        }

        try {
            $request_object = new \Illuminate\Http\Request;
            $request_object->setMethod('POST');
            $request_object->request->add(['id' => $id]);

            return $this->postCancel($request_object);
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function postCancel(Request $request)
    {
        //aa('postCancel');
        if ($this->model == null) {
            $this->initModel($request->segment(1));
        }
        if ($this->data['db_table'] != 'sub_services' && $this->data['db_table'] != 'crm_accounts') {
            return json_alert('No Access', 'warning');
        }

        try {
            session(['menu_route' => $this->data['menu_route']]);

            $record_access = $this->model->singleRecordAccess($request->id);

            if (! $this->data['access']['is_delete'] || ! $record_access) {
                return response()->json(['status' => 'error', 'message' => 'No Access']);
            }
            $id = $request->id;

            $row = \DB::table($this->data['db_table'])->where('id', $id)->get()->first();

            if ($this->data['db_table'] == 'sub_services') { // subscriptions
                if ($row->bundle_id) {
                    return json_alert('Bundle lines cannot be cancelled.', 'error');
                }

                // cannot cancel after 25th
                if (date('d') >= 25) {
                    // return json_alert('Subscription can only be cancelled the following month.');
                }

                if ($row->provision_type == 'domain_name') {
                    return json_alert('Domain name is linked to hosting, please cancel hosting subscription.', 'error');
                }

                $cancellation_period = get_admin_setting('cancellation_schedule');

                if ($row->status == 'Pending' || $cancellation_period == 'Immediately') {
                    $erp_subscriptions = new \ErpSubs;
                    $result = $erp_subscriptions->deleteSubscription($id);
                    if ($result !== true) {
                        return json_alert($result, 'error');
                    }

                    return json_alert('Subscription deleted.');
                } else {
                    $erp_subscriptions = new \ErpSubs;
                    $result = $erp_subscriptions->cancel($id);
                    if ($result !== true) {
                        return json_alert($result, 'error');
                    }

                    return json_alert('Subscription queued for cancellation.');
                }
            }

            if ($this->data['db_table'] == 'crm_accounts') { // accounts
                if (session('role_level') == 'Admin' || parent_of($id)) {
                    $deleted = \DB::table('crm_accounts')->where(['id' => $id, 'status' => 'Deleted'])->count();

                    if ($deleted) {
                        return json_alert('Account already deleted.', 'error');
                    }

                    $account = dbgetaccount($request->id);
                    if ($account->partner_id == 1) {
                        (new \DBEvent)->setAccountAging($request->id);
                    }

                    // if customer has no subscriptions and balance delete
                    /*
                    if ($row->balance == 0 && $row->subscriptions == 0 && $row->aging == 0) {
                        $db = new \DBEvent($this->data['module_id']);

                        $result = $db->deleteRecord($request);

                        if ($result instanceof \Illuminate\Http\JsonResponse) {
                            return $result;
                        } else {
                            return response()->json(['status' => 'error', 'message' => $result]);
                        }

                        return response()->json(['status' => 'success', 'message' => 'Record Deleted.']);
                    } */

                    $data_product_ids = get_data_product_ids();
                    $account_has_data = \DB::connection('default')->table('sub_services')->whereIn('product_id', $data_product_ids)->where('status', '!=', 'Deleted')->where('account_id', $id)->count();
                    $cancellation_period = get_admin_setting('cancellation_schedule');

                    if ($cancellation_period == 'Immediately') {
                        $cancel_date = date('Y-m-d');
                        if ($account_has_data) {
                            $cancel_date = date('Y-m-t', strtotime('+1 month'));
                        }
                    } elseif ($cancellation_period == 'This Month') {
                        $cancel_date = date('Y-m-t');
                        if ($account_has_data) {
                            $cancel_date = date('Y-m-t', strtotime('+1 month'));
                        }
                    } elseif ($cancellation_period == 'Next Month') {
                        $cancel_date = date('Y-m-t', strtotime('+1 month'));
                    }

                    $account = \DB::table('crm_accounts')->where('id', $id)->get()->first();
                    if (session('role_level') == 'Admin') {
                        if (is_superadmin() || $account->cancel_approved) {
                            \DB::table('crm_accounts')->where('id', $account->id)->update(['cancel_approved' => 1]);
                            if ($account->type == 'lead') {
                                $request_object = new \Illuminate\Http\Request;
                                $request_object->setMethod('POST');
                                $request_object->request->add(['id' => $id]);

                                return $this->postDelete($request_object);
                            } else {
                                if ($cancellation_period == 'Immediate') {
                                    // All movie magic cancellations must process immediately - Create a setting for cancellation period
                                    \DB::table('crm_accounts')->where('id', $id)->update(['account_status' => 'Cancelled', 'cancel_date' => date('Y-m-d')]);
                                    delete_account($id);

                                    return json_alert('Account deleted.');
                                } else {
                                    \DB::table('crm_accounts')->where('id', $id)->update(['account_status' => 'Cancelled', 'cancel_date' => $cancel_date]);
                                    $account = dbgetaccount($id);
                                    send_account_cancel_email($id);

                                    return json_alert('Account cancelled. Services will be deleted on '.$cancel_date.'.');
                                }
                            }
                        } else {
                            $exists = \DB::table('crm_approvals')->where('title', 'like', 'Account Cancel%')->where('module_id', 343)->where('row_id', $account->id)->count();
                            if (! $exists) {
                                $name = $account->company;
                                $data = [
                                    'module_id' => 343,
                                    'row_id' => $account->id,
                                    'title' => 'Account Cancel '.$name.' #'.$account->id,
                                    'processed' => 0,
                                    'requested_by' => get_user_id_default(),
                                ];
                                (new \DBEvent)->setTable('crm_approvals')->save($data);

                                module_log(343, $id, 'cancel request', 'Account cancel request');
                            }

                            return json_alert('Submitted for approval.', 'warning');
                        }
                    } else {
                        if ($account->type == 'lead') {
                            $request_object = new \Illuminate\Http\Request;
                            $request_object->setMethod('POST');
                            $request_object->request->add(['id' => $id]);

                            return $this->postDelete($request_object);
                        } else {
                            \DB::table('crm_accounts')->where('id', $id)->update(['cancel_approved' => 1, 'account_status' => 'Cancelled', 'cancel_date' => $cancel_date]);

                            $account = dbgetaccount($id);

                            send_account_cancel_email($id);

                            module_log(343, $id, 'cancelled', 'Account cancelled');

                            return json_alert('Account cancelled. Services will be deleted on '.$cancel_date.'.');
                        }
                    }
                }
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
        }
    }

    public function postRestore(Request $request)
    {
        $record_access = $this->model->singleRecordAccess($request->id);

        if (session('role_level') != 'Admin') {
            return response()->json(['status' => 'error', 'message' => 'No Access']);
        }

        if (! $this->data['access']['is_delete'] || ! $record_access) {
            return response()->json(['status' => 'error', 'message' => 'No Access']);
        }

        if ($this->data['soft_delete'] == 1) {
            \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->id)->update(['is_deleted' => 0]);
            \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->id)->update(['status' => 'Enabled']);
        } else {
            \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->id)->update(['status' => 'Enabled']);
            \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->id)->update(['is_deleted' => 0]);
        }

        return json_alert('Record restored');
    }

    public function postManagerDelete(Request $request)
    {
        $id = $request->id;
        if (is_superadmin() && ($this->data['db_table'] == 'crm_accounts' || $this->data['db_table'] == 'sub_services')) {
            if ($this->data['db_table'] == 'sub_services') {
                $erp_subscriptions = new \ErpSubs;
                $result = $erp_subscriptions->deleteSubscription($id, true);
                if ($result !== true) {
                    return json_alert($result, 'error');
                }

                return json_alert('Subscription deleted.');
            }
            if ($this->data['db_table'] == 'crm_accounts') {
                $cancel_date = date('Y-m-d');
                \DB::table('crm_accounts')->where('id', $id)->update(['cancel_approved' => 1, 'account_status' => 'Cancelled', 'cancel_date' => $cancel_date, 'deleted_at' => $cancel_date]);

                module_log(343, $id, 'cancelled', 'Account cancelled');

                return json_alert('Account Cancelled.');
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'No Access']);
        }
    }

    public function postDelete(Request $request)
    {
        try {
            if ($this->model == null) {
                $this->initModel($request->segment(1));
            }
            session(['menu_route' => $this->data['menu_route']]);
            if (! is_superadmin() && ! is_manager()) {
                $record_access = $this->model->singleRecordAccess($request->id);
                if (! $this->data['access']['is_delete'] || ! $record_access) {
                    return response()->json(['status' => 'error', 'message' => 'No Access']);
                }
            }
            $manager_override = false;
            if (! empty($request->manager_override) && is_superadmin()) {
                //   $manager_override = true;
            }
            $id = $request->id;

            $requires_delete_approval = 1;
            if (in_array($this->data['db_table'], ['crm_documents', 'crm_supplier_documents', 'crm_opportunities'])) {
                $requires_delete_approval = 0;
            } else {
                if (is_superadmin()) {
                    $superadmin_delete_approval = $this->data['superadmin_delete_approval'];
                    /*
                    if(!$superadmin_delete_approval){
                        $master_module_id = \DB::connection('default')->table('erp_cruds')->where('detail_module_id',$this->data['module_id'])->pluck('id')->first();
                        if($master_module_id){
                            $superadmin_delete_approval = \DB::connection('default')->table('erp_cruds')->where('id',$master_module_id)->pluck('superadmin_delete_approval')->first();
                        }
                    }
                    */

                    if (! $superadmin_delete_approval) {
                        $requires_delete_approval = 0;
                    }
                }
            }

            if (session('instance')->directory == 'hajj') {
                $requires_delete_approval = 0;
            }

            if (session('role_level') != 'Admin') {
                $requires_delete_approval = false;
            }
            if ($this->data['disable_delete_approval']) {
                $requires_delete_approval = false;
            }
            //((session('role_id')!=1 && $this->data['module_id'] == 1898) || (!is_superadmin() && $this->data['module_id'] != 1898)

            if ($requires_delete_approval) {
                $approved = \DB::connection('default')->table('crm_approvals')->where('module_id', $this->data['module_id'])->where('row_id', $id)->where('approved', 1)->count();

                if (! $approved) {
                    $e = \DB::connection('default')->table('crm_approvals')->where('module_id', $this->data['module_id'])->where('row_id', $id)->where('title', 'like', '%Delete%')->count();
                    if (! $e) {
                        $display_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['module_id'])->where('display_field', 1)->pluck('field')->first();
                        $display_val = '';
                        if (! empty($display_field)) {
                            $display_val = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('id', $id)->pluck($display_field)->first();
                            if ($display_field != 'company') {
                                $has_company_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['module_id'])->where('field', 'company')->count();
                                if ($has_company_field) {
                                    $company_val = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('id', $id)->pluck('company')->first();
                                    if (! empty($company_val)) {
                                        $display_val .= ' '.$company_val;
                                    }
                                }
                            }
                        }
                        $has_docdate_field = \DB::connection('default')->table('erp_module_fields')->where('module_id', $this->data['module_id'])->where('field', 'docdate')->count();
                        if ($has_docdate_field) {
                            $docdate_val = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('id', $id)->pluck('docdate')->first();
                        } else {
                            $docdate_val = '';
                        }
                        $data = [
                            'module_id' => $this->data['module_id'],
                            'row_id' => $id,
                            'title' => $this->data['menu_name'].' Delete - '.$display_val.' '.$docdate_val,
                            'processed' => 0,
                            'requested_by' => get_user_id_default(),
                        ];
                        (new \DBEvent)->setTable('crm_approvals')->save($data);

                        return json_alert('Submitted for approval.', 'warning');
                    } else {
                        return json_alert('Delete needs approval.', 'success');
                    }
                }
            }

            if ($this->data['db_table'] == 'sub_services') { // subscriptions
                $erp_subscriptions = new \ErpSubs;
                $result = $erp_subscriptions->deleteSubscription($id);
                if ($result === true) {
                    return json_alert('Subscription deleted.', 'success');
                } else {
                    return json_alert($result, 'error');
                }
            } else {
                $db = new \DBEvent($this->data['module_id']);
                $result = $db->deleteRecord($request);
                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    $response = json_alert('Record Deleted.', 'success');
                    if ($result->getData()->status != 'success') {
                        $response = $result;
                    }
                } else {
                    $response = response()->json(['status' => 'error', 'message' => $result]);
                }

                $newData = ['module_id' => $this->data['module_id']];

                $master_module_id = \DB::connection('default')->table('erp_cruds')->where('detail_module_id', $this->data['module_id'])->pluck('id')->first();
                if ($this->data['module_id'] == '488' || $this->data['module_id'] == '526' || $this->data['module_id'] == '799') {
                    $refresh_module_id = session('event_db_record')->module_id;
                    $master_module_id = \DB::table('erp_cruds')->where('detail_module_id', $refresh_module_id)->pluck('id')->first();
                    if ($master_module_id) {
                        $refresh_module_id = $master_module_id;
                    }
                    $newData['callback_function'] = 'get_sidebar_data'.$refresh_module_id;
                } elseif ($this->data['module_id'] == '761') {
                    $refresh_module_id = session('event_db_record')->module_id;
                    $master_module_id = \DB::table('erp_cruds')->where('detail_module_id', $refresh_module_id)->pluck('id')->first();
                    if ($master_module_id) {
                        $refresh_module_id = $master_module_id;
                    }

                    $newData['reload_conditional_styles'] = true;
                    $newData['module_id'] = $refresh_module_id;
                } elseif ($this->data['module_id'] == '749') {
                    $refresh_module_id = session('event_db_record')->module_id;
                    $master_module_id = \DB::table('erp_cruds')->where('detail_module_id', $refresh_module_id)->pluck('id')->first();
                    if ($master_module_id) {
                        $refresh_module_id = $master_module_id;
                    }
                    $newData['refresh_master_grid'] = 1;
                    $newData['reload_grid_config'] = 'reload_grid_config'.$refresh_module_id;
                } elseif (! empty($request->from_cancel)) {
                    $newData['close_dialog'] = 1;
                }
                if ($this->data['module_id'] == '1875') {
                    $newData['reload_grid_config'] = 'guides_accordion_refresh';
                }
                if (! empty($master_module_id)) {
                    $newData['master_module_id'] = $master_module_id;
                }

                if (! empty($master_module_id)) {
                    $newData['master_module_id'] = $master_module_id;
                }

                if (! empty($request->from_cancel)) {
                    $newData['close_dialog'] = 1;
                }
                $newData['grid_refresh'] = 1;
                $data = $response->getData(true);

                $data = array_merge($data, $newData);

                $response->setData($data);

                return $response;
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
        }
    }

    public function postSort(Request $request)
    {
        if ($this->data['db_table'] == 'isp_data_ip_ranges') {
            $ips = \DB::table('isp_data_ip_ranges')->get();
            $ip_list = [];
            foreach ($ips as $ip) {
                $ip_arr = explode('.', $ip->ip_range);
                $sort_ip = $ip_arr[2];
                $ip_list[] = ['id' => $ip->id, 'sort' => $sort_ip];
            }

            $ip_list = collect($ip_list)->sortBy('sort');
            foreach ($ip_list as $i => $r) {
                \DB::table('isp_data_ip_ranges')->where('id', $r['id'])->update(['sort_order' => $i]);
            }
        } elseif ($this->data['db_table'] == 'erp_form_events') {
            $row_module_id = \DB::table('erp_form_events')->where('id', $request->start_id)->pluck('module_id')->first();
            $start_sort = \DB::table('erp_form_events')->where('id', $request->start_id)->pluck('sort_order')->first();
            $target_sort = \DB::table('erp_form_events')->where('id', $request->target_id)->pluck('sort_order')->first();
            $first_sort = \DB::table('erp_form_events')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->first();
            $last_sort = \DB::table('erp_form_events')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->last();

            if ($target_sort == $first_sort || $target_sort == 0) {
                \DB::table('erp_form_events')->where('module_id', $row_module_id)->increment('sort_order');
                \DB::table('erp_form_events')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort == $last_sort) {
                \DB::table('erp_form_events')->where('module_id', $row_module_id)->decrement('sort_order');
                \DB::table('erp_form_events')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort < $start_sort) {
                \DB::table('erp_form_events')->where('module_id', $row_module_id)->where('sort_order', '>=', $target_sort)->increment('sort_order');
                \DB::table('erp_form_events')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } else {
                \DB::table('erp_form_events')->where('module_id', $row_module_id)->where('sort_order', '<=', $target_sort)->decrement('sort_order');
                \DB::table('erp_form_events')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            }

            $product_tabs = ['schedule', 'beforesave', 'aftersave', 'beforedelete', 'afterdelete', 'sql_filter'];

            $sort_order = 0;

            if (! empty($product_tabs) && count($product_tabs) > 0) {
                foreach ($product_tabs as $tab) {
                    $fields = \DB::table('erp_form_events')->where('module_id', $row_module_id)->where('type', $tab)->orderby('sort_order')->get();

                    foreach ($fields as $field) {
                        \DB::table('erp_form_events')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                        $sort_order++;
                    }
                }
                $fields = \DB::table('erp_form_events')->where('module_id', $row_module_id)->where('type', '')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('erp_form_events')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            } else {
                $fields = \DB::table('erp_form_events')->where('module_id', $row_module_id)->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('erp_form_events')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            }

            $rows = \DB::table('erp_form_events')->orderBy('module_id')->orderBy('sort_order')->get();
            foreach ($rows as $i => $r) {
                \DB::table('erp_form_events')->where('id', $r->id)->update(['sort_order' => $i]);
            }
        } elseif ($this->data['db_table'] == 'v_menu_items') {
            \DB::connection('pbx')->table('v_menu_items')->whereNull('menu_item_order')->update(['menu_item_order' => 0]);
            $start_sort = \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $request->start_id)->pluck('menu_item_order')->first();
            $target_sort = \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $request->target_id)->pluck('menu_item_order')->first();
            $first_sort = \DB::connection('pbx')->table('v_menu_items')->orderby('menu_item_order')->pluck('menu_item_order')->first();
            $last_sort = \DB::connection('pbx')->table('v_menu_items')->orderby('menu_item_order')->pluck('menu_item_order')->last();

            if ($target_sort == $first_sort || $target_sort == 0) {
                \DB::connection('pbx')->table('v_menu_items')->increment('menu_item_order');
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $request->start_id)->update(['menu_item_order' => $target_sort]);
            } elseif ($target_sort == $last_sort) {
                \DB::connection('pbx')->table('v_menu_items')->decrement('menu_item_order');
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $request->start_id)->update(['menu_item_order' => $target_sort]);
            } elseif ($target_sort < $start_sort) {
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_order', '>=', $target_sort)->increment('menu_item_order');
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $request->start_id)->update(['menu_item_order' => $target_sort]);
            } else {
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_order', '<=', $target_sort)->decrement('menu_item_order');
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $request->start_id)->update(['menu_item_order' => $target_sort]);
            }

            $parent_uuids = \DB::connection('pbx')->table('v_menu_items')->orderby('menu_item_order')->pluck('menu_item_parent_uuid')->filter()->unique()->toArray();
            $parent_uuids[] = '0438b504-8613-7887-c420-c837ffb20cb1';
            $menu_item_order = 0;

            if (! empty($parent_uuids) && count($parent_uuids) > 0) {
                foreach ($parent_uuids as $parent_uuid) {
                    \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $parent_uuid)->update(['menu_item_order' => $menu_item_order]);
                    $menu_item_order++;
                    $fields = \DB::connection('pbx')->table('v_menu_items')->where('menu_item_parent_uuid', $parent_uuid)->orderby('menu_item_order')->get();

                    foreach ($fields as $field) {
                        \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $field->menu_item_uuid)->update(['menu_item_order' => $menu_item_order]);
                        $menu_item_order++;
                    }
                }
            } else {
                $fields = \DB::connection('pbx')->table('v_menu_items')->orderby('menu_item_order')->get();

                foreach ($fields as $field) {
                    \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $field->menu_item_uuid)->update(['menu_item_order' => $menu_item_order]);
                    $menu_item_order++;
                }
            }
        } elseif ($this->data['db_table'] == 'erp_menu') {
        } elseif ($this->data['db_table'] == 'erp_module_fields') {
            $row_module_id = \DB::table('erp_module_fields')->where('id', $request->start_id)->pluck('module_id')->first();
            $start_sort = \DB::table('erp_module_fields')->where('id', $request->start_id)->pluck('sort_order')->first();
            $target_sort = \DB::table('erp_module_fields')->where('id', $request->target_id)->pluck('sort_order')->first();
            $first_sort = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->first();
            $last_sort = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->last();

            if ($target_sort == $first_sort || $target_sort == 0) {
                \DB::table('erp_module_fields')->where('module_id', $row_module_id)->increment('sort_order');
                \DB::table('erp_module_fields')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort == $last_sort) {
                \DB::table('erp_module_fields')->where('module_id', $row_module_id)->decrement('sort_order');
                \DB::table('erp_module_fields')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort < $start_sort) {
                \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('sort_order', '>=', $target_sort)->increment('sort_order');
                \DB::table('erp_module_fields')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } else {
                \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('sort_order', '<=', $target_sort)->decrement('sort_order');
                \DB::table('erp_module_fields')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            }

            $field_tabs = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('tab')->filter()->unique()->toArray();

            $sort_order = 0;

            if (! empty($field_tabs) && count($field_tabs) > 0) {
                foreach ($field_tabs as $tab) {
                    $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('visible', '!=', 'None')->where('tab', $tab)->orderby('sort_order')->get();

                    foreach ($fields as $field) {
                        \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                        $sort_order++;
                    }
                    $hidden_fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('visible', 'None')->where('tab', $tab)->orderby('sort_order')->get();

                    foreach ($hidden_fields as $field) {
                        \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                        $sort_order++;
                    }
                }
                $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('tab', '')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            } else {
                $fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('visible', '!=', 'None')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
                $hidden_fields = \DB::table('erp_module_fields')->where('module_id', $row_module_id)->where('visible', 'None')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('erp_module_fields')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            }

            formio_create_form_from_db($row_module_id, true);
        } elseif ($this->data['db_table'] == 'crm_aggregate_cards') {
            $id = $request->start_id;
            $replace_id = $request->target_id;
            $start_order = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->pluck('sort_order')->first();
            $target_order = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->target_id)->pluck('sort_order')->first();

            if ($target_order == null) {
                $target_order = 0;
            }

            if ($start_order > $target_order) {
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '<', $target_order)->decrement('sort_order');
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '>=', $target_order)->increment('sort_order');
            } else {
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '<=', $target_order)->decrement('sort_order');
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '>', $target_order)->increment('sort_order');
            }

            \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $id)->update(['sort_order' => $target_order]);

            $module_ids = \DB::table($this->data['db_table'])->orderBy('sort_order')->pluck('module_id')->unique()->toArray();
            $i = 0;
            foreach ($module_ids as $module_id) {
                $rows = \DB::table($this->data['db_table'])->where('module_id', $module_id)->orderBy('sort_order')->get();
                foreach ($rows as $r) {
                    \DB::table($this->data['db_table'])->where('id', $r->id)->update(['sort_order' => $i]);
                    $i++;
                }
            }
        } elseif ($this->data['db_table'] == 'crm_workflow_tracking') {
            $row_module_id = \DB::table($this->data['db_table'])->where('id', $request->start_id)->pluck('module_id')->first();
            $start_sort = \DB::table($this->data['db_table'])->where('id', $request->start_id)->pluck('sort_order')->first();
            $target_sort = \DB::table($this->data['db_table'])->where('id', $request->target_id)->pluck('sort_order')->first();
            $first_sort = \DB::table($this->data['db_table'])->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->first();
            $last_sort = \DB::table($this->data['db_table'])->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->last();

            if ($target_sort == $first_sort || $target_sort == 0) {
                \DB::table($this->data['db_table'])->where('module_id', $row_module_id)->increment('sort_order');
                \DB::table($this->data['db_table'])->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort == $last_sort) {
                \DB::table($this->data['db_table'])->where('module_id', $row_module_id)->decrement('sort_order');
                \DB::table($this->data['db_table'])->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort < $start_sort) {
                \DB::table($this->data['db_table'])->where('module_id', $row_module_id)->where('sort_order', '>=', $target_sort)->increment('sort_order');
                \DB::table($this->data['db_table'])->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } else {
                \DB::table($this->data['db_table'])->where('module_id', $row_module_id)->where('sort_order', '<=', $target_sort)->decrement('sort_order');
                \DB::table($this->data['db_table'])->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            }

            $role_ids = \DB::table($this->data['db_table'])->orderBy('sort_order')->pluck('role_id')->unique()->toArray();

            $i = 0;
            foreach ($role_ids as $role_id) {
                $module_ids = \DB::table($this->data['db_table'])->where('role_id', $role_id)->orderBy('sort_order')->pluck('module_id')->unique()->toArray();
                foreach ($module_ids as $module_id) {
                    $rows = \DB::table($this->data['db_table'])->where('role_id', $role_id)->where('module_id', $module_id)->orderBy('sort_order')->get();

                    foreach ($rows as $r) {
                        $i++;
                        \DB::table($this->data['db_table'])->where('id', $r->id)->update(['sort_order' => $i]);
                    }
                }
            }
        } elseif ($this->data['db_table'] == 'erp_grid_buttons') {
            $row_module_id = \DB::table('erp_grid_buttons')->where('id', $request->start_id)->pluck('module_id')->first();
            $start_sort = \DB::table('erp_grid_buttons')->where('id', $request->start_id)->pluck('sort_order')->first();
            $target_sort = \DB::table('erp_grid_buttons')->where('id', $request->target_id)->pluck('sort_order')->first();
            $first_sort = \DB::table('erp_grid_buttons')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->first();
            $last_sort = \DB::table('erp_grid_buttons')->where('module_id', $row_module_id)->orderby('sort_order')->pluck('sort_order')->last();

            if ($target_sort == $first_sort || $target_sort == 0) {
                \DB::table('erp_grid_buttons')->where('module_id', $row_module_id)->increment('sort_order');
                \DB::table('erp_grid_buttons')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort == $last_sort) {
                \DB::table('erp_grid_buttons')->where('module_id', $row_module_id)->decrement('sort_order');
                \DB::table('erp_grid_buttons')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort < $start_sort) {
                \DB::table('erp_grid_buttons')->where('module_id', $row_module_id)->where('sort_order', '>=', $target_sort)->increment('sort_order');
                \DB::table('erp_grid_buttons')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } else {
                \DB::table('erp_grid_buttons')->where('module_id', $row_module_id)->where('sort_order', '<=', $target_sort)->decrement('sort_order');
                \DB::table('erp_grid_buttons')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            }

            $rows = \DB::table($this->data['db_table'])->where('module_id', $row_module_id)->orderBy('sort_order')->get();
            foreach ($rows as $i => $r) {
                \DB::table($this->data['db_table'])->where('id', $r->id)->update(['sort_order' => $i]);
            }
        } elseif ($this->data['db_table'] == 'v_dialplans') {
            $id = $request->start_id;
            $replace_id = $request->target_id;
            $start_order = \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->pluck('dialplan_order')->first();
            $target_order = \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->target_id)->pluck('dialplan_order')->first();
            if ($target_order == null) {
                $target_order = 0;
            }
            if ($start_order >= $target_order) {
                $new_order = $target_order - 1;
            } else {
                $new_order = $target_order + 1;
            }

            \DB::table($this->data['db_table'])->where($this->data['db_key'], $id)->update(['dialplan_order' => $new_order]);

            $sort_order = 10;
            $fields = \DB::table($this->data['db_table'])->orderby('dialplan_order')->get();
            foreach ($fields as $field) {
                \DB::table($this->data['db_table'])->where('id', $field->id)->update(['dialplan_order' => $sort_order]);
                $sort_order++;
            }
        } elseif ($this->data['db_table'] == 'v_dialplan_details') {
            $id = $request->start_id;
            $replace_id = $request->target_id;
            $start_order = \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->pluck('dialplan_detail_order')->first();
            $target_order = \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->target_id)->pluck('dialplan_detail_order')->first();
            if ($target_order == null) {
                $target_order = 0;
            }
            if ($start_order >= $target_order) {
                $new_order = $target_order - 1;
            } else {
                $new_order = $target_order + 1;
            }

            \DB::table($this->data['db_table'])->where($this->data['db_key'], $id)->update(['dialplan_detail_order' => $new_order]);

            $sort_order = 10;
            $fields = \DB::table($this->data['db_table'])->orderby('dialplan_detail_order')->get();
            foreach ($fields as $field) {
                \DB::table($this->data['db_table'])->where('id', $field->id)->update(['dialplan_detail_order' => $sort_order]);
                $sort_order++;
            }
        } elseif ($this->data['db_table'] == 'hd_customer_faqs') {
            $start_sort = \DB::table('hd_customer_faqs')->where('id', $request->start_id)->pluck('sort_order')->first();
            $target_sort = \DB::table('hd_customer_faqs')->where('id', $request->target_id)->pluck('sort_order')->first();
            $first_sort = \DB::table('hd_customer_faqs')->orderby('sort_order')->pluck('sort_order')->first();
            $last_sort = \DB::table('hd_customer_faqs')->orderby('sort_order')->pluck('sort_order')->last();

            if ($target_sort == $first_sort || $target_sort == 0) {
                \DB::table('hd_customer_faqs')->increment('sort_order');
                \DB::table('hd_customer_faqs')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort == $last_sort) {
                \DB::table('hd_customer_faqs')->decrement('sort_order');
                \DB::table('hd_customer_faqs')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort < $start_sort) {
                \DB::table('hd_customer_faqs')->where('sort_order', '>=', $target_sort)->increment('sort_order');
                \DB::table('hd_customer_faqs')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } else {
                \DB::table('hd_customer_faqs')->where('sort_order', '<=', $target_sort)->decrement('sort_order');
                \DB::table('hd_customer_faqs')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            }

            $types = \DB::table('hd_customer_faqs')->orderby('sort_order')->pluck('type')->filter()->unique()->toArray();

            $sort_order = 0;

            if (! empty($types) && count($types) > 0) {
                foreach ($types as $type) {
                    $fields = \DB::table('hd_customer_faqs')->where('type', $type)->orderby('sort_order')->get();

                    foreach ($fields as $field) {
                        \DB::table('hd_customer_faqs')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                        $sort_order++;
                    }
                }
                $fields = \DB::table('hd_customer_faqs')->where('type', '')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('hd_customer_faqs')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            } else {
                $fields = \DB::table('hd_customer_faqs')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('hd_customer_faqs')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            }
        } elseif ($this->data['db_table'] == 'crm_server_management') {
            $start_sort = \DB::table('crm_server_management')->where('id', $request->start_id)->pluck('sort_order')->first();
            $target_sort = \DB::table('crm_server_management')->where('id', $request->target_id)->pluck('sort_order')->first();
            $first_sort = \DB::table('crm_server_management')->orderby('sort_order')->pluck('sort_order')->first();
            $last_sort = \DB::table('crm_server_management')->orderby('sort_order')->pluck('sort_order')->last();

            if ($target_sort == $first_sort || $target_sort == 0) {
                \DB::table('crm_server_management')->increment('sort_order');
                \DB::table('crm_server_management')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort == $last_sort) {
                \DB::table('crm_server_management')->decrement('sort_order');
                \DB::table('crm_server_management')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } elseif ($target_sort < $start_sort) {
                \DB::table('crm_server_management')->where('sort_order', '>=', $target_sort)->increment('sort_order');
                \DB::table('crm_server_management')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            } else {
                \DB::table('crm_server_management')->where('sort_order', '<=', $target_sort)->decrement('sort_order');
                \DB::table('crm_server_management')->where('id', $request->start_id)->update(['sort_order' => $target_sort]);
            }

            $departments = \DB::table('crm_server_management')->orderby('sort_order')->pluck('department')->filter()->unique()->toArray();

            $sort_order = 0;

            if (! empty($departments) && count($departments) > 0) {
                foreach ($departments as $department) {
                    $fields = \DB::table('crm_server_management')->where('department', $department)->orderby('sort_order')->get();

                    foreach ($fields as $field) {
                        \DB::table('crm_server_management')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                        $sort_order++;
                    }
                }
                $fields = \DB::table('crm_server_management')->where('department', '')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('crm_server_management')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            } else {
                $fields = \DB::table('crm_server_management')->orderby('sort_order')->get();

                foreach ($fields as $field) {
                    \DB::table('crm_server_management')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    $sort_order++;
                }
            }
        } elseif ($this->data['db_table'] == 'acc_ledger_accounts') {
            $id = $request->start_id;
            $replace_id = $request->target_id;
            $start_order = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->pluck('sort_order')->first();
            $target_order = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->target_id)->pluck('sort_order')->first();
            $categories = \DB::connection($this->data['connection'])->table('acc_ledger_account_categories')->orderBy('sort_order')->get();

            if ($target_order == null) {
                $target_order = 0;
            }

            if ($start_order > $target_order) {
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '<', $target_order)->decrement('sort_order');
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '>=', $target_order)->increment('sort_order');
            } else {
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '<=', $target_order)->decrement('sort_order');
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '>', $target_order)->increment('sort_order');
            }

            \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $id)->update(['sort_order' => $target_order]);

            $fields = \DB::connection($this->data['connection'])->table($this->data['db_table'])->select('id', 'sort_order')->orderby('sort_order')->get();
            foreach ($fields as $i => $field) {
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('id', $field->id)->update(['sort_order' => $i]);
            }
            $i = 0;
            foreach ($categories as $category) {
                $rows = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('ledger_account_category_id', $category->id)->orderby('sort_order')->get();
                foreach ($rows as $row) {
                    \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('id', $row->id)->update(['sort_order' => $i]);
                    $i++;
                }
            }
        } else {
            $id = $request->start_id;
            $replace_id = $request->target_id;
            $start_order = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->pluck('sort_order')->first();
            $target_order = \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $request->target_id)->pluck('sort_order')->first();

            if ($target_order == null) {
                $target_order = 0;
            }

            if ($start_order > $target_order) {
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '<', $target_order)->decrement('sort_order');
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '>=', $target_order)->increment('sort_order');
            } else {
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '<=', $target_order)->decrement('sort_order');
                \DB::connection($this->data['connection'])->table($this->data['db_table'])->where('sort_order', '>', $target_order)->increment('sort_order');
            }

            \DB::connection($this->data['connection'])->table($this->data['db_table'])->where($this->data['db_key'], $id)->update(['sort_order' => $target_order]);

            if ($this->data['db_table'] == 'crm_products' || $this->data['db_table'] == 'crm_product_categories') {
                update_products_and_categories_sort();
            }

            $cols = get_columns_from_schema($this->data['db_table'], null, $this->data['connection']);

            if (in_array('is_deleted', $cols)) {
                $i = 1;
                $rows = \DB::table($this->data['db_table'])->where('is_deleted', 0)->orderBy('sort_order')->get();
                foreach ($rows as $r) {
                    \DB::table($this->data['db_table'])->where('id', $r->id)->update(['sort_order' => $i]);
                    $i++;
                }
                /*
                $rows = \DB::table($this->data['db_table'])->where('is_deleted',1)->orderBy('sort_order')->get();
                foreach ($rows as $r) {
                    \DB::table($this->data['db_table'])->where('id', $r->id)->update(['sort_order' => $i]);
                    $i++;
                }
                */
            } else {
                $rows = \DB::table($this->data['db_table'])->orderBy('sort_order')->get();
                foreach ($rows as $i => $r) {
                    \DB::table($this->data['db_table'])->where('id', $r->id)->update(['sort_order' => $i]);
                }
            }
            // $start_sort = \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->pluck('sort_order')->first();
            // $first_sort = \DB::table($this->data['db_table'])->orderby('sort_order')->pluck('sort_order')->first();
            // $last_sort = \DB::table($this->data['db_table'])->orderby('sort_order')->pluck('sort_order')->last();
            // if ($target_sort == $first_sort) {
            //     \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->update(['sort_order' => $target_sort - 1]);
            // } elseif ($target_sort == $last_sort) {
            //     \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->update(['sort_order' => $target_sort + 1]);
            // } else {
            //     \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->start_id)->update(['sort_order' => $target_sort]);
            //     \DB::table($this->data['db_table'])->where($this->data['db_key'], $request->target_id)->update(['sort_order' => $start_sort]);
            // }
            // $sort_order = 0;
            // $fields = \DB::table($this->data['db_table'])->orderby('sort_order')->get();

            // foreach ($fields as $field) {
            //     \DB::table($this->data['db_table'])->where($this->data['db_key'], $field->id)->update(['sort_order' => $sort_order]);
            //     ++$sort_order;
            // }

            if ($this->data['db_table'] == 'p_rates_summary') {
                $rows = \DB::table($this->data['db_table'])->orderBy('sort_order')->get();
                foreach ($rows as $i => $r) {
                    \DB::table('p_rates_partner_items')->where('country', $r->country)->where('destination', $r->destination)->update(['sort_order' => $i]);
                }
            }

            if ($this->data['db_table'] == 'crm_staff_tasks') {
                update_workboard_sorting();
            }

            if ($this->data['db_table'] == 'crm_training_guides') {
                $rows = \DB::table($this->data['db_table'])->orderBy('project_id', 'asc')->orderBy('sort_order', 'asc')->get();
                foreach ($rows as $i => $r) {
                    \DB::table('crm_training_guides')->where('id', $r->id)->update(['sort_order' => $i]);
                }
            }
            if ($this->data['db_table'] == 'crm_module_cards') {
                $rows = \DB::table($this->data['db_table'])->where('is_deleted', 0)->orderBy('module_id', 'asc')->orderBy('sort_order', 'asc')->get();

                foreach ($rows as $i => $r) {
                    \DB::table('crm_module_cards')->where('id', $r->id)->update(['sort_order' => $i]);
                }
            }
        }
    }

    public function button(Request $request)
    {
        $current_conn = \DB::getDefaultConnection();

        set_db_connection();
        $button_id = $request->segment(3);
        $grid_id = $request->segment(4);

        $button = \DB::connection('default')->table('erp_menu')->where('id', $button_id)->get()->first();

        if (empty($button)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Id']);
        }

        try {
            if ($button->require_grid_id && $grid_id) {
                $request->request->add(['id' => $grid_id]);
            } elseif ($button->require_grid_id && ! $grid_id) {
                return response()->json(['status' => 'error', 'message' => 'Select a Record']);
            }
            $request->request->add(['db_table' => $this->data['db_table']]);

            $request->request->add(['button_data' => $button]);

            $button_conn = \DB::connection('default')->table('erp_cruds')->where('id', $button->render_module_id)->pluck('connection')->first();
            $request->request->add(['mod_id' => $button->render_module_id]);
            $request->request->add(['mod_conn' => $button_conn]);
            session(['mod_id' => $button->render_module_id]);
            session(['mod_conn' => $button_conn]);

            if ($button->in_iframe == 1 && $request->segment(5) == 1) {
                $data['menu_name'] = $button->menu_name;

                $data['iframe_url'] = '/'.$request->segment(1).'/'.$request->segment(2).'/'.$request->segment(3).'/'.$request->segment(4);

                $data['hide_page_header'] = 1;
                $data['remove_container'] = 1;
                $data['button_iframe'] = 1;

                return view('__app.components.iframe', $data);
            }

            if (! empty($button->module_id) || (! empty($button->url) && $button->url != '#')) {
                return button_menu_redirect($button, $request);
            } else {
                $function = $button->ajax_function_name;

                if (! function_exists($function)) {
                    debug_email('Button function missing: '.$button->menu_name, 'Button function missing: '.$button->menu_name.PHP_EOL.'Button id: '.$button_id);
                    if ($request->ajax()) {
                        return response()->json(['status' => 'error', 'message' => 'An error occurred']);
                    } else {
                        return Redirect::back()->with('message', 'An error occurred')->with('status', 'error');
                    }
                }

                $response = $function($request);

                if ($button->action_type == 'ajax' && $response instanceof \Illuminate\Http\JsonResponse) {
                    $newData = ['row_id' => $grid_id, 'module_id' => $this->data['module_id']];
                    $master_module_id = \DB::connection('default')->table('erp_cruds')->where('detail_module_id', $this->data['module_id'])->pluck('id')->first();

                    if (! empty($master_module_id)) {
                        $newData['master_module_id'] = $master_module_id;
                    }
                    $data = $response->getData(true);

                    $data = array_merge($data, $newData);
                    if (! empty($data['row_id']) && ! empty($grid_id)) {
                        $data['row_id'] = $grid_id;
                    }
                    $response->setData($data);
                }

                return $response;
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
            exception_email($ex, 'Button function '.$button->menu_name);

            $insert_data = [
                'created_date' => date('Y-m-d H:i:s'),
                'type' => 'button',
                'action' => 'button id: '.$button->id.' name:'.$button->menu_name.' function:'.$button->ajax_function_name,
                'result' => str_replace(PHP_EOL, ' ', $error),
                'success' => 0,
            ];

            \DB::connection('default')->table('erp_system_log')->insert($insert_data);

            return response()->json(['status' => 'error', 'message' => $error]);
        }
        set_db_connection($current_conn);
    }

    public function passwordConfirmedAction(Request $request)
    {
        try {
            session(['menu_route' => $this->data['menu_route']]);

            // confirm password
            $credentials = ['username' => \Auth::user()->username, 'password' => $request->password];

            if (! \Auth::validate($credentials)) {
                return json_alert('Password does not match', 'warning');
            }

            // parse action

            if ($this->data['db_table'] == 'sub_services') { // subscriptions
                $id = $request->subscription_id;
                $record_access = $this->model->singleRecordAccess($id);
                if (! $this->data['access']['is_delete'] || ! $record_access) {
                    return response()->json(['status' => 'error', 'message' => 'No Access']);
                }

                if ($request->action == 'delete') {
                    $erp_subscriptions = new \ErpSubs;
                    $result = $erp_subscriptions->deleteSubscription($id);
                    if ($result === true) {
                        return json_alert('Subscription deleted');
                    } else {
                        return json_alert($result, 'error');
                    }
                }

                if ($request->action == 'cancel') {
                    $erp_subscriptions = new \ErpSubs;
                    $result = $erp_subscriptions->cancel($id);
                    if ($result !== true) {
                        return $result;
                    }

                    return json_alert('Subscription queued for cancellation.');
                }
            } elseif ($this->data['db_table'] == 'crm_accounts') { // accounts
                $id = $request->account_id;
                $record_access = $this->model->singleRecordAccess($id);
                if (! $this->data['access']['is_delete'] || ! $record_access) {
                    return response()->json(['status' => 'error', 'message' => 'No Access']);
                }

                if ($request->action == 'delete') {
                    $request_data = ['id' => $id];
                    $request_obj = new \Illuminate\Http\Request($request_data);
                    $request_obj->setMethod('POST');

                    return $this->postDelete($request_obj);
                }

                if ($request->action == 'cancel') {
                    if (is_superadmin() || parent_of($id)) {
                        $deleted = \DB::table('crm_accounts')->where(['id' => $id, 'status' => 'Deleted'])->count();
                        if ($deleted) {
                            return json_alert('Account already deleted.', 'error');
                        } else {
                            $cancelled = \DB::table('crm_accounts')->where(['id' => $id, 'account_status' => 'Cancelled'])->count();

                            if ($cancelled) {
                                return json_alert('Account already cancelled.', 'error');
                            } else {
                                $account_has_fibre = \DB::connection('default')->table('sub_services')->where('provision_type', 'fibre')->where('status', '!=', 'Deleted')->where('account_id', $id)->count();
                                $cancel_date = date('Y-m-t', strtotime('+1 month'));
                                if (date('Y-m-d') < date('Y-m-25') && ! $account_has_fibre) {
                                    $cancel_date = date('Y-m-t');
                                }
                                \DB::table('crm_accounts')->where('id', $id)->update(['account_status' => 'Cancelled', 'cancel_date' => $cancel_date]);

                                $account = dbgetaccount($id);
                                send_account_cancel_email($id);

                                return json_alert('Account cancelled. Services will be deleted on '.$cancel_date.'.');
                            }
                        }
                    }
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'No Access']);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
        }
    }
}
