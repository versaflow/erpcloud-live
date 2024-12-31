<?php

namespace App\Http\Middleware;

use Closure;
use Exception;

class InstanceAccessMiddleware
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
        try {
            if ($request->route()->getActionName() != 'App\Http\Controllers\CoreController@getLogout') {
                if (isset(session('instance')->customer_erp) && session('instance')->customer_erp == null && !empty(session('role_id')) && ! empty(session('role_level'))) {
                    if (session('role_level') == 'Admin') {
                        $ct_usernames = \DB::connection('system')->table('erp_users')->where('account_id', 1)->pluck('username')->toArray();
                        if (in_array(session('username'), $ct_usernames)) {
                            $instance_access = get_admin_instance_access_session();

                            if (! in_array(session('instance')->id, $instance_access)) {

                                $instance = \DB::connection('system')->table('erp_instances')->whereIn('id', $instance_access)->get()->first();
                                $user_id = \DB::connection('system')->table('erp_users')->where('username', session('username'))->pluck('id')->first();
                                $login_url = 'https://'.$instance->domain_name.'/user/admin_login?user_id='.$user_id;

                                if ($instance) {
                                    return redirect()->to($login_url);
                                } else {
                                    return 'No instance access.';
                                }
                            }
                        }
                    }
                }
            }

            //global search
            //auto login admin if logged in main instance
            if (isset(session('instance')->customer_erp) && ! session('instance')->customer_erp && empty(session('user_id')) && ! empty($request->admin_user_id)) {

                $user = \DB::connection('system')->table('erp_users')->where('id', $request->admin_user_id)->get()->first();
                $role = \DB::table('erp_user_roles')->where('id', $user->role_id)->get()->first();
                if ($role->level == 'Admin') {
                    $logged_in = \DB::connection('system')->table('erp_user_sessions')->where('user_id', $user->id)->where('ip_address', request()->ip())->count();
                    if ($logged_in) {

                        $row = \DB::connection('default')->table('erp_users')->where('username', $user->username)->get()->first();
                        if ($row) {

                            // check admin instance access

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
                            session(['original_role_id' => $row->role_id]);
                        }
                    }
                }
            }

            return $next($request);

        } catch (\Throwable $ex) {
            exception_log($ex);
            throw new Exception($ex->getTraceAsString());
        }

    }
}
