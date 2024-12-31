<?php

namespace App\Http\Middleware;

use Closure;
use View;

class ShareDataForViews
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // share cached menus to all views
        if (! empty(session('role_id'))) {
            //update session user_id
            $current_module = \DB::connection('default')->table('erp_cruds')->select('id', 'connection', 'app_id')
                ->where('slug', request()->segment(1))->get()->first();

            // setup menu params
            if ($current_module) {

                $current_menu = \DB::connection('default')->table('erp_menu')->select('id', 'module_id', 'location', 'url')
                    ->where('module_id', $current_module->id)->where('menu_type', 'module')->where('active', 1)->get()->first();

                $menu_params = [];
                $menu_params['menu_url'] = isset($current_menu->url) ? $current_menu->url : '';
                $menu_params['module_id'] = isset($current_menu->module_id) ? $current_menu->module_id : '';
                $menu_params['connection'] = isset($current_module->connection) ? $current_module->connection : '';
                $menu_params['app_id'] = isset($current_module->app_id) ? $current_module->app_id : '';

                if ($current_menu->id) {
                    $menu_params['menu_id'] = $current_menu->id;
                }
            } else {

                $current_menu = \DB::connection('default')->table('erp_menu')->select('id', 'module_id', 'location', 'url')
                    ->where('slug', request()->segment(1))->where('menu_type', '!=', 'module')->where('active', 1)->get()->first();
                if ($current_menu) {
                    $menu_params = [];
                    $menu_params['menu_url'] = $current_menu->url;
                    $menu_params['module_id'] = $current_menu->module_id;
                    $menu_params['connection'] = $current_module->connection;
                    $menu_params['app_id'] = $current_module->app_id;

                    if ($current_menu->id) {
                        $menu_params['menu_id'] = $current_menu->id;
                    }
                }
            }

            if (empty($menu_params)) {
                $menu_params = ['url' => request()->segment(1)];
            }

            if (session('role_level') == 'Admin') {
                $main_menu_menu = \ErpMenu::build_menu('main_menu', $menu_params);
            }

            if (session('role_level') != 'Admin') {
                $customer_menu_menu = \ErpMenu::build_menu('customer_menu', $menu_params);
                View::share('customer_menu_menu', $customer_menu_menu);
            }
            if (session('role_level') == 'Admin') {
                $module_menu_menu = \ErpMenu::build_menu('module_menu', $menu_params, $current_module->id);
                View::share('module_menu_menu', $module_menu_menu);
            }

            $main_menu_menu = \ErpMenu::build_menu('main_menu', $menu_params);
            View::share('main_menu', $main_menu_menu);
            // $services_menu = \ErpMenu::build_menu('services_menu', $menu_params);
            // View::share('services_menu',$services_menu);

            $services_menu = \ErpMenu::build_menu('services_admin_menu', $menu_params);
            View::share('services_admin_menu', $services_menu);

            /*
            if(session('role_level') == 'Admin'){
                $main_menu_menu = \ErpMenu::build_menu('main_menu', $menu_params);
            }
            if(session('role_level')=='Admin'){
                View::share('main_menu',$main_menu_menu);
                $services_menu = \ErpMenu::build_menu('services_menu', $menu_params);
                View::share('services_menu',$services_menu);
            }elseif(session('role_level')!='Admin'){
                $services_menu = \ErpMenu::build_menu('services_menu', $menu_params);
                View::share('services_menu',$services_menu);
            }else{
                View::share('main_menu',$main_menu_menu);
            }
            */

            $color_scheme = [];
            $color_scheme['sidebar_color'] = (! empty(session('instance')->sidebar_color)) ? session('instance')->sidebar_color : '#e9e9e9';
            $color_scheme['sidebar_text_color'] = (! empty(session('instance')->sidebar_text_color)) ? session('instance')->sidebar_text_color : '#000000';

            $color_scheme['first_row_color'] = (! empty(session('instance')->first_row_color)) ? session('instance')->first_row_color : '#e9e9e9';
            $color_scheme['first_row_buttons_color'] = (! empty(session('instance')->first_row_buttons_color)) ? session('instance')->first_row_buttons_color : '#e9e9e9';

            $color_scheme['second_row_color'] = (! empty(session('instance')->second_row_color)) ? session('instance')->second_row_color : '#e9e9e9';
            $color_scheme['second_row_text_color'] = (! empty(session('instance')->second_row_text_color)) ? session('instance')->second_row_text_color : '#000000';
            $color_scheme['second_row_buttons_color'] = (! empty(session('instance')->second_row_buttons_color)) ? session('instance')->second_row_buttons_color : '#e9e9e9';

            if ($current_menu->location == 'top_left_menu') {

                $color_scheme['second_row_color'] = (! empty(session('instance')->services_modules_color)) ? session('instance')->services_modules_color : $color_scheme['second_row_color'];
            }

            View::share('color_scheme', $color_scheme);

            $top_right_menu = \ErpMenu::build_menu('top_right_menu', $menu_params);
            View::share('top_right_menu', $top_right_menu);
            $top_left_menu = \ErpMenu::build_menu('top_left_menu', $menu_params);

            View::share('top_left_menu', $top_left_menu);

            $is_webform = false;
            if (request()->segment(1) == 'webform') {
                $is_webform = true;
            }
            View::share('is_webform', $is_webform);

            if (session('instance')->id == 1 && session('parent_id') == 1) {
                $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->get()->first();
                $panel_logo = $main_instance->panel_logo;
                if ($panel_logo) {
                    $panel_logo = 'https://'.$main_instance->domain_name.'/uploads/'.$main_instance->db_connection.'/305/'.$panel_logo;

                    View::share('panel_logo', $panel_logo);
                }
            }

            /*
            if(session('instance')->id == 1 && is_superadmin() && session('role_level') == 'Admin'){
                $sidebar_cards = \Erp::getSidebarCards();
                View::share('sidebar_cards',$sidebar_cards);
            }
            */

            if (is_main_instance()) {

                $telecloud_menu = \ErpMenu::build_menu('telecloud_menu', $menu_params);

                View::share('telecloud_menu', $telecloud_menu);

                $sms_menu = \ErpMenu::build_menu('sms_menu', $menu_params);

                View::share('sms_menu', $sms_menu);
            }

            //View::share('pbx_panels',[]);
            //View::share('selected_panel_index',0);
            $branding_logo = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', session('parent_id'))->pluck('logo')->first();

            if (file_exists(uploads_settings_path().$branding_logo)) {
                $branding_logo = settings_url().$branding_logo;
            }

            if (! $branding_logo) {

                $branding_logo = '';
            }
            View::share('branding_logo', $branding_logo);

            if (session('role_level') == 'Admin') {
                $company_logins = get_erp_login_urls();
                View::share('company_logins', $company_logins);
            }

            $documents_url = get_menu_url_from_table('crm_documents');
            View::share('documents_url', $documents_url);
        }

        return $next($request);
    }
}
