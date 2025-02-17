<?php

/* DASHBOARD */

    
Route::any('dashboard', 'DashboardController@index');
// Route::any('pinned', 'DashboardController@pinnned');
// Route::any('home', 'DashboardController@pinnned');

Route::any('helpdesk_login', function(){
    $url = 'https://helpdesk.telecloud.co.za/admin.php';
    $user = \DB::table('erp_users')->where('id',session('user_id'))->where('is_deleted',0)->get()->first();
    if($user && !empty($user->webmail_email) && !empty($user->webmail_password)){
    $url .= "?login_email=".$user->webmail_email."&login_password=".$user->webmail_password;
    } 
    return redirect()->to($url);
});

Route::any('home_add/{module_id?}',function($module_id){
    try{
    
        $data = [
          'role_id' => session('role_id'),  
          'module_id' => $module_id,  
        ];
        dbinsert('erp_favorites',$data);
        return json_alert('Done');
    }catch(\Throwable $ex){
        exception_log($ex->getMessage());
    }
    
});

Route::any('pinned_iframe/{id?}', function ($id) {
    $tab = \DB::connection('default')->table('erp_favorites')->where('id', $id)->get()->first();
    if (empty($tab->link_url)) {
        echo 'Invalid URL';
        exit;
    }

    $tab->link_url = str_replace('http://','https://',$tab->link_url);
    
    
    if($tab->link_url == 'https://telecloud.co.za/webmail'){
        $url = 'https://telecloud.co.za/webmail';
        $employee = \DB::table('erp_users')->where('id',session('user_id'))->where('is_deleted',0)->get()->first();
        if($employee && !empty($employee->webmail_email) && !empty($employee->webmail_password)){
        $url .= "?_user=".$employee->webmail_email."&_pass=".$employee->webmail_password;
        }
        $tab->link_url = $url;
    }
    
    if($tab->link_url == 'https://helpdesk.telecloud.co.za/admin.php'){
        $url = 'https://helpdesk.telecloud.co.za/admin.php';
        $employee = \DB::table('erp_users')->where('id',session('user_id'))->where('is_deleted',0)->get()->first();
        if($employee && !empty($employee->webmail_email) && !empty($employee->webmail_password)){
        $url .= "?login_email=".$employee->webmail_email."&login_password=".$employee->webmail_password;
        }
        $tab->link_url = $url;
    }
    
    //$invalid_src = isIframeDisabled($tab->link_url);
    //if($invalid_src){
   //     echo 'Iframe blocked';
   //     exit;
  //  }
  
    $data = [
        'hide_page_header' => 1,
        'menu_name' => $tab->title,
        'iframe_url' => $tab->link_url,
    ];

    return view('__app.components.iframe', $data);
});

Route::any('save_dashboard_state', 'DashboardController@saveDashboardState');
Route::any('staff_dashboard', 'DashboardController@staffDashboard');
Route::any('getchartdata/{id?}', 'DashboardController@getChartData');
Route::any('removechart/{id?}', 'DashboardController@removeChart');

Route::any('save_dashboard_panels', 'DashboardController@saveDashboardPanels');

Route::any('dashboard_charts', 'DashboardController@aggridCharts');

Route::get('dashboard_charts_content/{role_id?}',function($role_id){
    $data = [];
    $data['aggrid_charts'] = \Erp::getDashboardGrids($role_id);
    return view('__app.charts.dashboard_ajax',$data);
});

Route::post('dashboard_charts_sort', function(){
    $charts = request()->charts;
   
    foreach($charts as $i => $id){
       
        \DB::connection('default')->table('erp_grid_views')->where('id',$id)->update(['dashboard_sort_order' => $i]);
    }
});


Route::any('dashboard_tracking_enable/{layout_id?}', function ($layout_id) {
    if (is_superadmin()) {
        $dashboard_sort_order = \DB::connection('default')->table('erp_grid_views')->max('dashboard_sort_order');
        $dashboard_sort_order++;
        
        $layout = \DB::connection('default')->table('erp_grid_views')->where('id',$layout_id)->get()->first();
        $role = get_workspace_role_from_module_id($layout->module_id);
           
        if($role && $role->id) {
            $role_id = $role->id;
        }else{
            $role_id = 1;
        }
        \DB::connection('default')->table('erp_grid_views')->where('id',$layout_id)->update(['show_on_dashboard'=>1,'dashboard_sort_order' => $dashboard_sort_order,'join_chart_role_id'=>$role_id]);
        return json_alert('Dashboard tracking enabled');
    }
});

Route::any('dashboard_tracking_disable/{layout_id?}', function ($layout_id) {
    if (is_superadmin()) {
        \DB::connection('default')->table('erp_grid_views')->where('id',$layout_id)->update(['show_on_dashboard'=>0,'join_chart_role_id'=>0]);
        return json_alert('Dashboard tracking disabled');
    }
});

Route::any('update_dashboard_sort_order/{layout_id?}/{old_index?}/{new_index?}',function($id,$old_index,$new_index){
 
    \DB::table('erp_grid_views')->where('show_on_dashboard',0)->update(['dashboard_sort_order'=>0]);
    $start_order =  $old_index;
    $target_order =  $new_index;
    
    if (null == $target_order) {
    $target_order = 0;
    }
    
    if ($start_order > $target_order) {
        \DB::table('erp_grid_views')->where('show_on_dashboard',1)->where('dashboard_sort_order', '<', $target_order)->decrement('dashboard_sort_order');
        \DB::table('erp_grid_views')->where('show_on_dashboard',1)->where('dashboard_sort_order', '>=', $target_order)->increment('dashboard_sort_order');
    } else {
        \DB::table('erp_grid_views')->where('show_on_dashboard',1)->where('dashboard_sort_order', '<=', $target_order)->decrement('dashboard_sort_order');
        \DB::table('erp_grid_views')->where('show_on_dashboard',1)->where('dashboard_sort_order', '>', $target_order)->increment('dashboard_sort_order');
    }
    
    
    \DB::table('erp_grid_views')->where('id', $id)->where('show_on_dashboard',1)->update(['dashboard_sort_order' => $target_order]);
    $order = \DB::table('erp_grid_views')->where('show_on_dashboard',1)->orderBy('dashboard_sort_order')->get();
    foreach($order as $i => $o){
        \DB::table('erp_grid_views')->where('id', $o->id)->update(['dashboard_sort_order' => $i]);
    }
    
});

Route::any('remove_dashboard/{layout_id?}',function($id){
    $module_id = \DB::table('erp_grid_views')->where('id', $id)->pluck('module_id')->first();
    $role_id = get_workspace_role_from_module_id($module_id);
    \DB::table('erp_grid_views')->where('id', $id)->update(['show_on_dashboard'=>0,'dashboard_sort_order' => 0]);
    return json_alert('Dashboard layout removed');
});