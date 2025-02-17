<?php

namespace App\Http\Middleware;

use Closure;

class TenantSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        try {

            // delete the cached config otherwise the multitenant will default to main instance and mergeconfigfrom will fail
            //$config_cache_file = '/home/versaflo/versaflow.io/erp/bootstrap/cache/config.php';
            //if (file_exists($config_cache_file)) {
            //    unlink($config_cache_file);
            //}

            if (! $request->ajax() && $request->isMethod('get')) {
                //session()->forget('troubleshooting');
            }

            if ($request->segment(1) != 'helpdesk' && $request->segment(1) != 'tickets') {
                session()->forget('troubleshooter_form');
            }

            $hostname = request()->root();

            // if($hostname == 'https://telecloud.erpcloud.co.za'){

            //    $fullUrlWithQuery = str_replace('telecloud.erpcloud.co.za','portal.telecloud.co.za',$request->fullUrl());
            //     return redirect()->to($fullUrlWithQuery);
            // }

            $hostname_match = false;
            if (! empty(session('instance'))) {
                if ($hostname == session('instance')->domain_name) {
                    $hostname_match = true;
                }
                if ($hostname == session('instance')->alias) {
                    $hostname_match = true;
                }
                if ($hostname == session('instance')->store_url) {
                    $hostname_match = true;
                }
            }

            if (empty(session('instance')) || empty(session('favicon'))) {
                $hostname_match = false;
            }

            if (! $hostname_match || ! empty(request()->connection_instance)) {
                if (! empty($hostname) && $hostname == 'http://reports.turnkeyerp.io') {
                    $allowed_proxies = ['report', 'report_dashboard', 'processes', 'process_dashboard', 'dashboard_user', 'dashboard', 'dashboards_reports'];

                    if (! empty($request->token) && in_array(request()->segment(1), $allowed_proxies)) {
                        $token = \Erp::decode($request->token);
                        if (empty($token['user_id']) && empty($token['guest'])) {
                            return \Redirect::back()->with('status', 'error')->with('message', 'Invalid Token');
                        }
                        $connection = $token['token'];
                        $user_id = $token['user_id'];
                        if (! empty($user_id) && empty(session('user_id'))) {
                            admin_user_login($user_id);
                        }

                        $instance = \DB::connection('system')->table('erp_instances')->where('db_connection', $connection)->get()->first();
                        session(['new_token_connection' => $connection]);

                        $cookie = $response->withCookie(cookie()->forever('connection', $connection));

                    } elseif (empty($request->token) && ! empty(session('new_token_connection'))) {
                        $instance = \DB::connection('system')->table('erp_instances')->where('db_connection', session('new_token_connection'))->get()->first();
                    } elseif (empty($request->token) && ! in_array(request()->segment(1), $allowed_proxies) && ! empty($request->cookie('connection'))) {
                        $instance = \DB::connection('system')->table('erp_instances')->where('db_connection', $request->cookie('connection'))->get()->first();
                    }
                } elseif (! empty($hostname)) {
                    $hostname = str_replace(['http://', 'https://'], '', $hostname);
                    $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();

                    // check store url
                    if (empty($instance)) {
                        $hostname = str_replace(['http://', 'https://'], '', $hostname);
                        $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('store_url', $hostname)->get()->first();
                    }
                }

                if (empty($instance)) { // if request does not contain hostname
                    $hostname = $_SERVER['HTTP_HOST'];
                    $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();
                }

                /*
                if (empty($instance)) { // check whitelabel domain
                    $hostname = $_SERVER['HTTP_HOST'];
                    $instance_configs = \DB::connection('system')->table('erp_instances')->where('installed', 1)->get();
                    foreach ($instance_configs as $instance_config) {
                        $host_found = \DB::connection($instance_config->db_connection)->table('crm_account_partner_settings')->where('whitelabel_domain', $hostname)->count();
                        if ($host_found) {
                            $instance = $instance_config;
                            break;
                        }
                    }
                }
                */

                if (empty($instance)) {
                    abort(403, 'Access denied');
                }

                if (! empty(request()->cidb)) {
                    $instance = \DB::connection('system')->table('erp_instances')->where('db_connection', request()->cidb)->get()->first();
                }

                $instance_dir = $instance->db_connection;
                $instance->directory = $instance_dir;
                $instance->app_ids = get_installed_app_ids();
                $currency = $instance->currency;
                if ($currency == 'ZAR') {
                    $instance->currency_symbol = 'R';
                } else {
                    $fmt = new \NumberFormatter("en-us@currency=$currency", \NumberFormatter::CURRENCY);
                    $instance->currency_symbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                }
                // template colors
                $admin_template_settings = \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('account_id', 1)->get()->first();

                $instance->sidebar_color = (! empty($admin_template_settings->sidebar_color)) ? $admin_template_settings->sidebar_color : '#000000';
                $instance->sidebar_text_color = (! empty($admin_template_settings->sidebar_text_color)) ? $admin_template_settings->sidebar_text_color : '#000000';

                $instance->first_row_color = (! empty($admin_template_settings->first_row_color)) ? $admin_template_settings->first_row_color : '#e9e9e9';
                $instance->first_row_buttons_color = (! empty($admin_template_settings->first_row_buttons_color)) ? $admin_template_settings->first_row_buttons_color : '#e9e9e9';

                $instance->second_row_color = (! empty($admin_template_settings->second_row_color)) ? $admin_template_settings->second_row_color : '#cccccc';
                $instance->second_row_buttons_color = (! empty($admin_template_settings->second_row_buttons_color)) ? $admin_template_settings->second_row_buttons_color : '#e9e9e9';
                $instance->second_row_text_color = (! empty($admin_template_settings->second_row_text_color)) ? $admin_template_settings->second_row_text_color : '#e9e9e9';

                $instance->services_modules_color = (! empty($admin_template_settings->services_modules_color)) ? $admin_template_settings->services_modules_color : '#cccccc';

                session(['app_ids' => get_installed_app_ids()]);
                if ($instance->installed) {
                    $favicon = $admin_template_settings->favicon;
                    session(['favicon' => settings_url().$favicon]);
                    if (! empty(session('role_id')) && check_access('1') && ! in_array(session('original_role_id'), [1, 7])) {
                        if (session('user_id') == 3696) {
                        }
                        \DB::connection($instance->db_connection)->table('erp_user_sessions')->where('user_id', session('user_id'))->delete();
                    }
                    if (! empty(session('role_id')) && session('role_id') < 11 && session('original_role_id') == 11) {
                        \DB::connection($instance->db_connection)->table('erp_user_sessions')->where('user_id', session('user_id'))->delete();
                    }
                    if (! empty(session('role_id')) && session('role_id') < 21 && session('original_role_id') == 21) {
                        \DB::connection($instance->db_connection)->table('erp_user_sessions')->where('user_id', session('user_id'))->delete();
                    }
                }
                session(['instance' => $instance]);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            abort(403, 'Access denied');
        }

        /*
        \Log::getMonolog()->pushHandler(
        (new \Monolog\Handler\StreamHandler(
        // Set the log path
        app()->storagePath().'/logs/errors.log',
        // Set the number of daily files you want to keep
        app()->make('config')->get('app.log_max_files', 5)
        ))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true, true))
        );
        */

        return $response;
    }
}
