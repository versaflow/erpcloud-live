<?php

namespace App\Http\Middleware;

use Closure;

class StaffActiveTasks
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
        /*
        if(!empty(session('role_id')) && session('instance')->id == 1 ){
            //update session user_id

            if(!$request->ajax && session('role_level') == 'Admin' && !is_superadmin()){

                $staff_stats = get_staff_current_tasks();

                //session(['staff_stats' => $staff_stats]);
                if(empty($request->layout_id)){

                    $processes_url = get_menu_url_from_module_id(1945);
                    $projects_url = get_menu_url_from_module_id(1944);

                    $requires_redirect = false;
                    if($request->segment(1) != $projects_url && $request->segment(1) != $processes_url){

                        foreach($staff_stats as $staff_stat){
                            if($staff_stat['user_id'] == session('user_id')){
                                if(empty($staff_stat['task'])){
                                    $requires_redirect = true;
                                }
                            }
                        }
                    }

                    if($requires_redirect){
                        if (!session()->has('message')) {
                        //return redirect()->to($projects_url)->with('message', 'Task needs to be started.')->with('status', 'warning');
                        }
                    }
                }
            }
        }
*/
        return $next($request);
    }
}
