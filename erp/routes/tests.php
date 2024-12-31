<?php

use App\Http\Controllers\IntegrationsController;
use Illuminate\Support\Facades\Route;

Route::get('test_layout_export', function () {
    $file_path = export_billing_summary_layout(575);

    return response()->download($file_path, 'billing_text.xlsx');
});

Route::get('test_supportboard', function () {
    //define('SB_PATH', '/home/teleclou/helpdesk.telecloud.co.za/html');

    //require('/home/teleclou/helpdesk.telecloud.co.za/html/admin.php');
    return view('integrations.supportboard');
    //sb_component_admin();
})->middleware('globalviewdata');

Route::get('test_ckeditor', function () {
    return view('__app.test.ckeditor');
});
Route::get('render_pricelist', function ($account_id = 0) {
    return export_pricelist_storefront(1, 1);
});
Route::any('payslip_test', function () {
    $id = 119;
    $payroll = (array) \DB::table('hr_payroll')->where('id', $id)->get()->first();
    $company = dbgetaccount(1);
    $employee = \DB::table('hr_employees')->where('id', $payroll['employee_id'])->get()->first();
    $user = \DB::table('erp_users')->where('id', $employee->user_id)->get()->first();
    $payroll['company_name'] = str_replace('(Admin)', '', $company->company);
    $payroll['company_address'] = $company->address;
    $payroll['paydate'] = date('Y-m-d', strtotime($payroll['docdate']));
    $payroll['employee_code'] = '#'.str_pad($employee->id, 4, '0', STR_PAD_LEFT);
    $payroll['employee_name'] = $employee->name;
    $payroll['employee_id_number'] = $employee->id_number;
    $payroll['job_title'] = $employee->position;
    $payroll['start_date'] = date('Y-m-d', strtotime($employee->start_date));

    //// generate pdf payslip
    //$view = \View::make('__app.components.pages.payslip', $payroll);
    //$contents = (string) $view;
    $pdf = PDF::loadView('__app.components.pages.payslip', $payroll);
    $pdf_name = str_replace(' ', '_', 'Payslip '.date('Y-m-d', strtotime($payroll['docdate'])).'.pdf');

    $filename = attachments_path().$pdf_name;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->save($filename);

    $filename = attachments_url().$pdf_name;
    $data['pdf'] = $filename;
    $data['menu_name'] = $pdf_name;

    return view('__app.components.pdf', $data);
});

Route::any('toggle_dev_views', function () {

    if (! empty(session('use_dev_views'))) {
        session()->forget('use_dev_views');
    } else {
        session(['use_dev_views' => 1]);
    }

    return redirect()->to('customers');
});

Route::any('check_dev_views', function () {});

Route::any('export_bitco', function () {
    $rows = \DB::connection('pbx_cdr')->table('bitco_cdr')->get()->toArray();
    $excel_list = [];
    foreach ($rows as $row) {
        $excel_list[] = (array) $row;
    }
    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/bitco_cdr.xlsx', 'attachments');

    return redirect()->to(attachments_url().'bitco_cdr.xlsx');
});

Route::any('pr_view', function () {

    return export_pricelist(1, 'pdf', true, 'test.pdf');
});
Route::any('velzon_view', function () {

    $data = [];

    return view('velzon.grid', $data);
});

Route::any('session_user_id', function () {

    //  $r = is_dev();
    //$rr = is_superadmin();
    //dd($r,$rr);
});

Route::any('session_role_id', function () {

    $instance_access = get_admin_instance_access_session();
    $instance_ids = get_admin_instance_access();
    $list = [];
    $instance_list = get_instances_list();
    $i = 10000;

    foreach ($instance_list as $instance) {
        if ($instance->id == 1) {
            $main_instance = $instance;
        }
        if (session('instance')->id == $instance->id) {
            continue;
        }

        $user_exists = \DB::connection($instance->db_connection)->table('erp_users')->where('username', session('username'))->pluck('id')->first();
        if (! $user_exists) {
            continue;
        }

        $instance->login_url .= '&redirect_page='.request()->path();

        $instance_access = get_admin_instance_access_session();

        if (! in_array($instance->id, $instance_access)) {
            continue;
        }

        //$list[] = ['url' => $instance->login_url, 'menu_name' => $instance->name, 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        $list[] = ['menu_url' => $instance->login_url, 'menu_name' => $instance->name, 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];

        $i++;
    }
    if (is_dev() || session('role_id') == 1) {
        //    $list[] = ['url' => url('update_instances'), 'menu_name' => 'Update Instances','action_type'=>'ajax', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 0, 'childs' => []];
    }

});

Route::any('session_check', function () {});

Route::any('grid_tabstest', function () {
    $data = [];

    return view('__app.components.simplegrid', $data);
});
Route::any('grid_tabs', function () {

    $data = [];

    return view('__app.grids.grid_tabs', $data);
});
Route::any('website_ajax', function () {
    return json_alert('ok');
});

Route::any('mod_list', function () {
    if (check_access('1,31')) {
        $table = 'erp_menu';
        $field = 'module_id';

        $rows = \DB::connection('default')->select(" SELECT * FROM `$table`
    where module_id > '' 
    GROUP BY `$field`
    HAVING COUNT(`$field`) > 1");
        foreach ($rows as $r) {
            ddd($r->module_id);
            $menus = \DB::table('erp_menu')->where('module_id', $r->module_id)->get();
            foreach ($menus as $m) {
                ddd($m->menu_name);
                ddd($m->menu_type);
            }
        }
    }
});

Route::any('aggrid_demo_data', function () {
    $immutableStore = '[{"athlete":"Michael Phelps","age":23,"country":"United States","year":2008,"date":"24/08/2008","sport":"Swimming","gold":8,"silver":0,"bronze":0,"total":8},{"athlete":"Michael Phelps","age":19,"country":"United States","year":2004,"date":"29/08/2004","sport":"Swimming","gold":6,"silver":0,"bronze":2,"total":8},{"athlete":"Michael Phelps","age":27,"country":"United States","year":2012,"date":"12/08/2012","sport":"Swimming","gold":4,"silver":2,"bronze":0,"total":6},{"athlete":"Natalie Coughlin","age":25,"country":"United States","year":2008,"date":"24/08/2008","sport":"Swimming","gold":1,"silver":2,"bronze":3,"total":6},{"athlete":"Aleksey Nemov","age":24,"country":"Russia","year":2000,"date":"01/10/2000","sport":"Gymnastics","gold":2,"silver":1,"bronze":3,"total":6},{"athlete":"Alicia Coutts","age":24,"country":"Australia","year":2012,"date":"12/08/2012","sport":"Swimming","gold":1,"silver":3,"bronze":1,"total":5},{"athlete":"Missy Franklin","age":17,"country":"United States","year":2012,"date":"12/08/2012","sport":"Swimming","gold":4,"silver":0,"bronze":1,"total":5},{"athlete":"Ryan Lochte","age":27,"country":"United States","year":2012,"date":"12/08/2012","sport":"Swimming","gold":2,"silver":2,"bronze":1,"total":5},{"athlete":"Allison Schmitt","age":22,"country":"United States","year":2012,"date":"12/08/2012","sport":"Swimming","gold":3,"silver":1,"bronze":1,"total":5},{"athlete":"Natalie Coughlin","age":21,"country":"United States","year":2004,"date":"29/08/2004","sport":"Swimming","gold":2,"silver":2,"bronze":1,"total":5},{"athlete":"Ian Thorpe","age":17,"country":"Australia","year":2000,"date":"01/10/2000","sport":"Swimming","gold":3,"silver":2,"bronze":0,"total":5},{"athlete":"Dara Torres","age":33,"country":"United States","year":2000,"date":"01/10/2000","sport":"Swimming","gold":2,"silver":0,"bronze":3,"total":5},{"athlete":"Cindy Klassen","age":26,"country":"Canada","year":2006,"date":"26/02/2006","sport":"Speed Skating","gold":1,"silver":2,"bronze":2,"total":5},{"athlete":"Nastia Liukin","age":18,"country":"United States","year":2008,"date":"24/08/2008","sport":"Gymnastics","gold":1,"silver":3,"bronze":1,"total":5},{"athlete":"Marit Bjørgen","age":29,"country":"Norway","year":2010,"date":"28/02/2010","sport":"Cross Country Skiing","gold":3,"silver":1,"bronze":1,"total":5},{"athlete":"Sun Yang","age":20,"country":"China","year":2012,"date":"12/08/2012","sport":"Swimming","gold":2,"silver":1,"bronze":1,"total":4},{"athlete":"Kirsty Coventry","age":24,"country":"Zimbabwe","year":2008,"date":"24/08/2008","sport":"Swimming","gold":1,"silver":3,"bronze":0,"total":4},{"athlete":"Libby Lenton-Trickett","age":23,"country":"Australia","year":2008,"date":"24/08/2008","sport":"Swimming","gold":2,"silver":1,"bronze":1,"total":4},{"athlete":"Ryan Lochte","age":24,"country":"United States","year":2008,"date":"24/08/2008","sport":"Swimming","gold":2,"silver":0,"bronze":2,"total":4},{"athlete":"Inge de Bruijn","age":30,"country":"Netherlands","year":2004,"date":"29/08/2004","sport":"Swimming","gold":1,"silver":1,"bronze":2,"total":4},{"athlete":"Petria Thomas","age":28,"country":"Australia","year":2004,"date":"29/08/2004","sport":"Swimming","gold":3,"silver":1,"bronze":0,"total":4},{"athlete":"Ian Thorpe","age":21,"country":"Australia","year":2004,"date":"29/08/2004","sport":"Swimming","gold":2,"silver":1,"bronze":1,"total":4},{"athlete":"Inge de Bruijn","age":27,"country":"Netherlands","year":2000,"date":"01/10/2000","sport":"Swimming","gold":3,"silver":1,"bronze":0,"total":4},{"athlete":"Gary Hall Jr.","age":25,"country":"United States","year":2000,"date":"01/10/2000","sport":"Swimming","gold":2,"silver":1,"bronze":1,"total":4},{"athlete":"Michael Klim","age":23,"country":"Australia","year":2000,"date":"01/10/2000","sport":"Swimming","gold":2,"silver":2,"bronze":0,"total":4},{"athlete":"Jenny Thompson","age":27,"country":"United States","year":2000,"date":"01/10/2000","sport":"Swimming","gold":3,"silver":0,"bronze":1,"total":4},{"athlete":"Pieter van den Hoogenband","age":22,"country":"Netherlands","year":2000,"date":"01/10/2000","sport":"Swimming","gold":2,"silver":0,"bronze":2,"total":4},{"athlete":"An Hyeon-Su","age":20,"country":"South Korea","year":2006,"date":"26/02/2006","sport":"Short-Track Speed Skating","gold":3,"silver":0,"bronze":1,"total":4},{"athlete":"Aliya Mustafina","age":17,"country":"Russia","year":2012,"date":"12/08/2012","sport":"Gymnastics","gold":1,"silver":1,"bronze":2,"total":4},{"athlete":"Shawn Johnson","age":16,"country":"United States","year":2008,"date":"24/08/2008","sport":"Gymnastics","gold":1,"silver":3,"bronze":0,"total":4},{"athlete":"Dmitry Sautin","age":26,"country":"Russia","year":2000,"date":"01/10/2000","sport":"Diving","gold":1,"silver":1,"bronze":2,"total":4},{"athlete":"Leontien Zijlaard-van Moorsel","age":30,"country":"Netherlands","year":2000,"date":"01/10/2000","sport":"Cycling","gold":3,"silver":1,"bronze":0,"total":4},{"athlete":"Petter Northug Jr.","age":24,"country":"Norway","year":2010,"date":"28/02/2010","sport":"Cross Country Skiing","gold":2,"silver":1,"bronze":1,"total":4},{"athlete":"Ole Einar Bjørndalen","age":28,"country":"Norway","year":2002,"date":"24/02/2002","sport":"Biathlon","gold":4,"silver":0,"bronze":0,"total":4},{"athlete":"Janica Kostelic","age":20,"country":"Croatia","year":2002,"date":"24/02/2002","sport":"Alpine Skiing","gold":3,"silver":1,"bronze":0,"total":4},{"athlete":"Nathan Adrian","age":23,"country":"United States","year":2012,"date":"12/08/2012","sport":"Swimming","gold":2,"silver":1,"bronze":0,"total":3},{"athlete":"Yannick Agnel","age":20,"country":"France","year":2012,"date":"12/08/2012","sport":"Swimming","gold":2,"silver":1,"bronze":0,"total":3},{"athlete":"Brittany Elmslie","age":18,"country":"Australia","year":2012,"date":"12/08/2012","sport":"Swimming","gold":1,"silver":2,"bronze":0,"total":3}]';

    $immutableStore = json_decode($immutableStore, true);
    $list = [];
    $id = 1;
    foreach ($immutableStore as $i => $row) {
        $n = $row;
        $n['id'] = $id;
        $list[] = $n;
        $id++;
    }

    $data = ['rows' => $list, 'lastRow' => count($immutableStore)];

    return response()->json($data);
});

Route::any('kendoui', function () {
    $data = [];

    return view('__app.test.kendodrawer', $data);
});

Route::any('dev', function () {
    return generate_refferal_link(12);
});

Route::any('aggrid_demo', function () {
    $data = [];

    return view('__app.test.aggrid_demo', $data);
});

Route::any('agrid_demodata', [IntegrationsController::class, 'agridData']);

Route::any('test_exception', function () {
    // try {
    $r = 1 / 0;
    //  } catch (\Throwable $ex) {  exception_log($ex);
    //  exception_email($ex, 'test board error');
    //  }
});

Route::any('numbers_ranges_verify', function () {
    if (session('role_level') == 'Admin') {
        numbers_ranges_verify();
    }
});

Route::any('dd', function () {
    if (check_access('1,34') || is_dev()) {

        debugdd();
    }
});

Route::any('debugbar_on', function () {
    if (check_access('1,34') || is_dev()) {
        session(['show_debug_bar' => 1]);
    }
});

Route::any('debugbar_off', function () {
    if (check_access('1,34') || is_dev()) {
        session()->forget('show_debug_bar');
    }
    //return redirect()->to('/');
});

Route::any('test_fs', function () {
    if (session('role_id') == 1 || is_dev()) {
        echo 'will return gateway xml if successful<br>';
        $pbx = new FusionPBX;

        $result = $pbx->portalCmd('portal_sofia_status_gateway');
        $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

        $json = json_encode($xml);
        $gateways = json_decode($json, true);

    }
});

Route::any('pdf_viewer', function () {
    return view('__app.test.pdf_viewer');
});
Route::any('pdf_builder', function () {
    return view('__app.test.pdf_builder');
});

Route::any('voipshop', function () {
    // return view('__app.test.voipshop');
});
Route::any('voiprates', function () {
    // return view('__app.test.voiprates');
});

Route::any('yodlee_details', function () {
    $y = new Yodlee;
    $user = str_replace('_', '', session('instance')->directory);

    $y->setLoginName($user);
    ddd('getProviderAccounts');
    $r = $y->getProviderAccounts();
    ddd($r);
    ddd('getUser');
    $r = $y->getUser($user);
    ddd($r);
});

Route::any('webview', function () {
    $url = 'https://cloudtools.versaflow.io/api/getrates?api_token=$2y$10$lqmYL8H3cOz2InHe1ZWDGOpLh7z0aCn.a6vSSqnkTKVZ/tGb0G8q.&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O';

    return redirect()->to($url);
});

Route::any('iframe_test', function () {
    echo '<iframe src="https://reports.cloudtelecoms.io/?token=eyJ1bnN0YWmjZV91ZCoIMSw4cpVwbgJ0Xi3koj24Mjkmo4w4dXN3c391ZCoIMzYmNn0=" width="100%" frameborder="0px" height="400px" onerror="alert(\'Failed\')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> ';
});

Route::any('mailbox', [IntegrationsController::class, 'mailBox']);
Route::any('mailbox_data', [IntegrationsController::class, 'mailBoxData']);

Route::any('last_active/{id?}', function ($id) {
    if (is_dev()) {
        $ts = \DB::connection('default')->table('erp_user_sessions')->where('user_id', $id)->orderBy('last_activity', 'desc')->pluck('last_activity')->first();
        $r = date('Y-m-d H:i:s', $ts);
        echo $r;
    }
});
