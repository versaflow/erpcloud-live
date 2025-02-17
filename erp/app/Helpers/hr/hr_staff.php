<?php

function get_incentive_footer($role_id)
{
    $incentive_footer_html = '';
    try {
        if (! is_main_instance()) {
            return '';
        }
        if (session('role_level') != 'Admin') {
            return '';
        }
        $role = \DB::connection('default')->table('erp_user_roles')->where('id', $role_id)->get()->first();
        $user = \DB::connection('default')->table('erp_users')->where('role_id', $role_id)->where('is_deleted', 0)->get()->first();

        if (is_superadmin()) {
            $user_id = $user->id;
        } else {
            $user_id = session('user_id');
        }
        if ($role_id == 54) {
            $user_id = 5704;
            $user = \DB::connection('default')->table('erp_users')->where('id', 5704)->where('is_deleted', 0)->get()->first();
        }

        $payroll_details = payroll_get_current_details();
        $payroll_date = $payroll_details['payroll_end_date'];
        $payroll_start_date = $payroll_details['start_date'];

        $yesterday = get_previous_workday();
        $rate_per_hour = currency_formatted($role->incentive_total_1 / 160, $role->incentive_currency);

        // if($role_id == 54){

        //     $commit_dates = \DB::table('erp_github_commits')->select(\DB::raw('DATE(committed_at) as commit_date'))
        //     ->where('committer_name','oyen-bright')
        //     ->where('committed_at','>=',$payroll_start_date)
        //     ->pluck('commit_date')->unique()->toArray();

        //     $hours_yesterday_q = \DB::connection('default')->table('crm_staff_timesheet')
        //     ->where('user_id',$user_id)
        //     ->where('created_day',$yesterday)
        //     ->whereIn('created_day',$commit_dates);

        //     $hours_yesterday = \DB::connection('default')->table('crm_staff_timesheet')
        //     ->where('user_id',$user_id)
        //     ->where('created_day',$yesterday)
        //     ->whereIn('created_day',$commit_dates)
        //     ->sum('duration');

        //     $hours_yesterday = round($hours_yesterday/60);

        //     $hours_today = \DB::connection('default')->table('crm_staff_timesheet')
        //     ->where('user_id',$user_id)
        //     ->where('created_day',date('Y-m-d'))
        //     ->whereIn('created_day',$commit_dates)
        //     ->sum('duration');
        //     $hours_today = round($hours_today/60);

        //     $hours_avg = \DB::connection('default')->table('crm_staff_timesheet')
        //     ->select(\DB::raw('sum(duration) as day_total'),'created_day')
        //     ->where('user_id',$user_id)
        //     ->where('created_day','!=',date('Y-m-d'))
        //     ->where('created_day','like',date('Y-m').'%')
        //     ->whereIn('created_day',$commit_dates)
        //     ->groupBy('created_day')->get();
        //     $hours_avg_day = $hours_avg->avg('day_total');
        //     $hours_avg_day = round($hours_avg_day/60);

        // }else{
        $hours_yesterday = \DB::connection('default')->table('crm_staff_timesheet')
            ->where('user_id', $user_id)->where('created_day', $yesterday)
            ->sum('duration');
        $hours_yesterday = round($hours_yesterday / 60, 2);

        $hours_today = \DB::connection('default')->table('crm_staff_timesheet')
            ->where('user_id', $user_id)->where('created_day', date('Y-m-d'))
            ->sum('duration');
        $hours_today = round($hours_today / 60, 2);

        $hours_avg = \DB::connection('default')->table('crm_staff_timesheet')
            ->select(\DB::raw('sum(duration) as day_total'), 'created_day')
            ->where('user_id', $user_id)
            ->where('created_day', '!=', date('Y-m-d'))
            ->where('created_day', 'like', date('Y-m').'%')
            ->groupBy('created_day')->get();
        $hours_avg_day = $hours_avg->avg('day_total');
        $hours_avg_day = round($hours_avg_day / 60, 2);
        // }

        $incentive_total = $role->incentive_total_1;
        if ($role->incentive_function_1 == 'incentive_timesheet') {
            $incentive_footer_html .= '<b>Basic</b>: '.$incentive_total.' | Rate per Hour = '.$rate_per_hour.' | Hours Yesterday: '.$hours_yesterday.' | Hours Today: '.$hours_today.' | Ave. Hour per Day: '.$hours_avg_day;
        } elseif ($role->incentive_function_1 == 'incentive_sales') {
            $incentive_sales = $role->incentive_total_1;
            $sales_target = 50000;
            $monthly_sales = \DB::table('crm_document_lines')
                ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
                ->where('salesman_id', $user_id)
                ->where('docdate', '>=', $payroll_start_date)
                ->where('billing_type', '')
                ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])->sum('zar_sale_total');

            if ($monthly_sales > $sales_target || $sales_target == 0) {
                $total = $incentive_sales;
                $percentage = 100;

            } else {
                if ($monthly_sales <= 0) {
                    $total = 0;
                    $percentage = 0;
                } else {
                    $total = $incentive_sales * ($monthly_sales / $sales_target);
                    $percentage = round(($monthly_sales / $sales_target) * 100, 2);
                }
            }

            $incentive_footer_html .= 'Sales: '.currency($monthly_sales).'/50000 | Percentage: '.$percentage.'% | Salary: '.currency_formatted($total);
        }

        if ($role->incentive_total_2 > 0) {
            $incentive_total = $role->incentive_total_2; //currency_formatted(,$role->incentive_currency);

            $incentive_footer_html .= '<br><b>Incentive: </b>'.$incentive_total.' | '.$role->incentive_2;
            if ($role->incentive_function_2 == 'incentive_github_commits') {

                $target_total = 0;
                $commits_total = 0;
                $target_per_day = 2;
                $target_total = $payroll_details['current_payroll_days'] * $target_per_day;

                if ($role->id == 54) {
                    $commits = \DB::connection('default')->table('erp_github_commits')
                        ->select(\DB::raw('count(*) as num_commits'), \DB::raw('DATE(committed_at) as created_day'), 'repo')
                        ->where('committer_name', 'oyen-bright')
                        ->where('committed_at', '>=', date('Y-m-d', strtotime($payroll_start_date)))
                        ->groupBy(\DB::raw('DATE(committed_at)'))
                        ->get();
                    $target_per_day = 1;
                } else {

                    $commits = \DB::connection('default')->table('erp_github_commits')
                        ->select(\DB::raw('count(*) as num_commits'), \DB::raw('DATE(committed_at) as created_day'), 'repo')
                        ->where('committer_name', '!=', 'oyen-bright')
                        ->where('committed_at', '>=', date('Y-m-d', strtotime($payroll_start_date)))
                        ->groupBy(\DB::raw('DATE(committed_at)'))
                        ->get();
                }

                $target_total = $payroll_details['current_payroll_days'] * $target_per_day;
                foreach ($commits as $commit) {
                    $commits_total += $commit->num_commits;
                }

                $commits_avg = ($target_total > 0) ? ($commits_total / $target_total) * 100 : 0;
                $incentive_footer_html .= ' | Current: '.$commits_total.'/'.$target_total.' | Ave. Daily Commits: '.$commits_avg;
            }
            if ($role->incentive_function_2 == 'incentive_products') {

                $monthly_products = \DB::table('crm_products')
                    ->where('created_at', '>=', date('Y-m-d', strtotime($payroll_start_date)))
                    ->where('created_by', $user_id)
                    ->count();
                $incentive_footer_html .= ' | Products created this month: '.$monthly_products;
            }
            if ($role->incentive_function_2 == 'incentive_sales') {

                $sales_total = \DB::table('crm_document_lines')
                    ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
                    ->where('salesman_id', $user_id)
                    ->where('docdate', '>=', $payroll_start_date)
                    ->where('billing_type', '')
                    ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])->sum('zar_sale_total');
                $incentive_footer_html .= ' | Sales: '.currency($sales_total).'/50000';
            }
        }
    } catch (\Throwable $ex) {
        Log::debug($ex);
    }

    return $incentive_footer_html;
}

function set_employee_emails_from_users()
{
    $users = \DB::table('erp_users')->where('account_id', 1)->get();
    foreach ($users as $user) {
        \DB::table('hr_employees')->where('user_id', $user->id)->update(['next_cloud_email' => $user->email]);
    }
}

function aftersave_role_set_monthly_rate($request)
{
    $role = \DB::table('erp_user_roles')->where('id', $request->id)->get()->first();
    if ($role->level == 'Admin') {
        $user_ids = \DB::table('erp_users')->where('role_id', $role->id)->where('is_deleted', 0)->pluck('id')->toArray();
        \DB::table('hr_employees')->whereIn('user_id', $user_ids)->update(['monthly_rate' => $request->monthly_rate]);
    }
}

function aftersave_users_set_employee_user_id($request)
{

    if (! empty($request->new_record) && ! empty(session('employee_id'))) {
        \DB::table('hr_employees')->where('id', session('employee_id'))->update(['user_id' => $request->id]);
    }
}

function aftersave_employees_setup_new_user($request)
{
    if (! empty($request->new_record)) {
        $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();

        if (empty($staff->next_cloud_email)) {
            $name = explode(' ', strtolower($staff->name));
            $email = $name[0].'@telecloud.co.za';
            $pass = generate_password();
            \DB::table('hr_employees')->where('id', $request->id)->update(['next_cloud_email' => $email, 'next_cloud_password' => $pass]);
        }

        $user_id = \DB::table('erp_users')->where('email', $email)->pluck('id')->first();
        \DB::table('hr_employees')->where('id', $request->id)->update(['user_id' => $user_id]);

        $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
        if (empty($user_id)) {
            $data = [];
            $data['full_name'] = $staff->name;
            $data['account_id'] = 1;
            $data['active'] = 1;
            $data['role_id'] = 67; // default buying
            $data['username'] = $staff->next_cloud_email;
            $data['email'] = $staff->next_cloud_email;
            $data['phone'] = $staff->mobile;
            $data['password'] = \Hash::make($staff->next_cloud_password);

            $user_id = \DB::table('erp_users')->insertGetId($data);

            \DB::table('hr_employees')->where('id', $request->id)->update(['user_id' => $user_id]);

            $instance_access = ['user_id' => $user_id, 'instance_id' => 1];
            $e = \DB::table('erp_instance_user_access')->where($instance_access)->count();
            if (! $e) {
                dbinsert('erp_instance_user_access', $instance_access);
            }

            $instance_access = ['user_id' => $user_id, 'instance_id' => 2];
            $e = \DB::table('erp_instance_user_access')->where($instance_access)->count();
            if (! $e) {
                dbinsert('erp_instance_user_access', $instance_access);
            }

            $instance_access = ['user_id' => $user_id, 'instance_id' => 11];
            $e = \DB::table('erp_instance_user_access')->where($instance_access)->count();
            if (! $e) {
                dbinsert('erp_instance_user_access', $instance_access);
            }
            $result = (new \Interworx)->setServer('host1')->setDomain('telecloud.co.za')->createEmail($staff->next_cloud_email, $staff->next_cloud_password);
        }

        $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
        $email_id = \DB::table('crm_email_manager')->where('internal_function', 'employee_credentials')->pluck('id')->first();

        $data['username'] = $staff->next_cloud_email;
        $data['password'] = $staff->next_cloud_password;

        \DB::table('erp_users')->where('id', $staff->user_id)->update(['password' => \Hash::make($staff->next_cloud_password)]);

        $data['portal_name'] = session('instance')->name;
        $data['portal_url'] = 'https://'.session('instance')->domain_name;
        $data['force_to_email'] = $staff->personal_email;
        $data['cc_admin'] = 1;
        $data['internal_function'] = 'employee_credentials';
        erp_process_notification(1, $data);
    }
}

function button_staff_send_new_employee_form($request)
{

    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'new_employee_form')->pluck('id')->first();

    return email_form($email_id, 1);
}
function button_staff_send_credentials($request)
{

    $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
    if (empty($staff->user_id)) {
        return json_alert('User needs to be set', 'warning');
    }
    if (empty($staff->next_cloud_email)) {
        return json_alert('next_cloud email needs to be set', 'warning');
    }
    if (empty($staff->next_cloud_password)) {
        return json_alert('next_cloud password needs to be set', 'warning');
    }
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'employee_credentials')->pluck('id')->first();

    $data['username'] = $staff->next_cloud_email;
    $data['password'] = $staff->next_cloud_password;

    \DB::table('erp_users')->where('id', $staff->user_id)->update(['password' => \Hash::make($staff->next_cloud_password)]);

    $data['portal_name'] = session('instance')->name;
    $data['portal_url'] = 'https://'.session('instance')->domain_name;

    return email_form($email_id, 1, $data);
}

function beforesave_user_check_credentials($request)
{
    if (! empty($request->new_record)) {
        if (empty($request->next_cloud_email)) {
            return 'Next cloud email required';
        }
        if (empty($request->next_cloud_password)) {
            return 'Next cloud password required';
        }
    }
}

function button_staff_setup_user_and_email($request)
{

    $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();

    if (empty($staff->next_cloud_email)) {
        return json_alert('next_cloud email needs to be set', 'warning');
    }
    if (empty($staff->next_cloud_password)) {
        return json_alert('next_cloud password needs to be set', 'warning');
    }

    // create user
    if (empty($staff->user_id)) {
        $data = [];
        $data['full_name'] = $staff->name;
        $data['account_id'] = 1;
        $data['active'] = 1;
        $data['role_id'] = 61; // default accounting
        $data['username'] = $staff->next_cloud_email;
        $data['email'] = $staff->next_cloud_email;
        $data['phone'] = $staff->mobile;
        $data['password'] = \Hash::make($staff->next_cloud_password);

        $user_id = \DB::table('erp_users')->insertGetId($data);

        \DB::table('hr_employees')->where('id', $request->id)->update(['user_id' => $user_id]);

        $instance_access = ['user_id' => $user_id, 'instance_id' => 1];
        $e = \DB::table('erp_instance_user_access')->where($instance_access)->count();
        if (! $e) {
            dbinsert('erp_instance_user_access', $instance_access);
        }

        $instance_access = ['user_id' => $user_id, 'instance_id' => 2];
        $e = \DB::table('erp_instance_user_access')->where($instance_access)->count();
        if (! $e) {
            dbinsert('erp_instance_user_access', $instance_access);
        }

        $instance_access = ['user_id' => $user_id, 'instance_id' => 11];
        $e = \DB::table('erp_instance_user_access')->where($instance_access)->count();
        if (! $e) {
            dbinsert('erp_instance_user_access', $instance_access);
        }
    } else {
        $user_id = $staff->user_id;
        $data = [];
        $data['username'] = $staff->next_cloud_email;
        $data['email'] = $staff->next_cloud_email;
        $data['password'] = \Hash::make($staff->next_cloud_password);
        $data['webmail_email'] = $staff->next_cloud_email;
        $data['webmail_password'] = $staff->next_cloud_password;

        \DB::table('erp_users')->where('id', $staff->user_id)->update($data);
    }
    // create host2 email
    $result = (new \Interworx)->setServer('host1')->setDomain('telecloud.co.za')->createEmail($staff->next_cloud_email, $staff->next_cloud_password);
    // aa($result);
    if ($result['status'] != 0) {
        return json_alert('Host 2 error: '.$result['payload'], 'warning');
    }

    return json_alert('Done');
}

function button_staff_setup_nextcloud($request)
{

    // $ curl -X POST http://admin:secret@example.com/ocs/v1.php/cloud/users -d userid="Frank" -d password="frankspassword" -H "OCS-APIRequest: true"
    // https://docs.nextcloud.com/server/latest/admin_manual/configuration_user/instruction_set_for_users.html

    $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
    if (empty($staff->next_cloud_email)) {
        return json_alert('next_cloud email needs to be set', 'warning');
    }
    if (empty($staff->next_cloud_password)) {
        return json_alert('next_cloud password needs to be set', 'warning');
    }

    $nextcloudUrl = 'https://office.cloudtelecoms.co.za';
    $adminUsername = 'ahmed@telecloud.co.za';
    $adminPassword = 'nimda786';

    // Endpoint for user creation
    $apiEndpoint = '/ocs/v1.php/cloud/users';

    // API URL
    $apiUrl = $nextcloudUrl.$apiEndpoint;

    // Guzzle client setup
    $client = new \GuzzleHttp\Client;

    // Data to be sent in the request
    $data = [
        'userid' => $staff->next_cloud_email,
        'password' => $staff->next_cloud_password,
        'displayName' => $staff->name,
        'email' => $staff->next_cloud_email,
        'groups' => ['Members'],
    ];

    // Options for the Guzzle request
    $options = [
        'auth' => [$adminUsername, $adminPassword],
        'form_params' => $data,
        'headers' => ['OCS-APIRequest' => 'true', 'Content-Type' => 'application/x-www-form-urlencoded'],
    ];

    // Make the request using Guzzle
    try {
        $response = $client->post($apiUrl, $options);
        $status_code = $response->getStatusCode();

        if (str_contains($response->getBody()->getContents(), '<id>'.$staff->next_cloud_email.'</id>') && str_contains($response->getBody()->getContents(), 'OK')) {
            return json_alert('User setup complete');
        } else {
            $status_codes = [
                101 => 'invalid input data',
                102 => 'username already exists',
                103 => 'unknown error occurred whilst adding the user',
                104 => 'group does not exist',
                105 => 'insufficient privileges for group',
                106 => 'no group specified (required for subadmins)',
                107 => 'all errors that contain a hint - for example “Password is among the 1,000,000 most common ones. Please make it unique.” (this code was added in 12.0.6 & 13.0.1)',
                108 => 'password and email empty. Must set password or an email',
                109 => 'invitation email cannot be send',
            ];
            if (isset($status_codes[$status_code])) {
                return json_alert($status_codes[$status_code], 'warning');
            } else {
                return json_alert($xmlString, 'warning');
            }
        }

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return json_alert('Guzzle request failed: '.$e->getMessage(), 'warning');
    }
}

// function button_create_zammad_agent($request){
//     if(!is_main_instance()){
//         return false;
//     }

//     $staff = \DB::table('hr_employees')->where('id',$request->id)->get()->first();
//     if(empty($staff->next_cloud_email)){
//         return json_alert('next_cloud email needs to be set','warning');
//     }
//     if(empty($staff->next_cloud_password)){
//         return json_alert('next_cloud password needs to be set','warning');
//     }

//     $client = new \ZammadAPIClient\Client([
//         'url'           => 'https://helpdesk.cloudtelecoms.co.za', // URL to your Zammad installation
//         'username'      => 'ahmed@telecloud.co.za',  // Username to use for authentication
//         'password'      => 'WEBmin@321',           // Password to use for authentication
//         // 'timeout'       => 15,                  // Sets timeout for requests, defaults to 5 seconds, 0: no timeout
//         // 'debug'         => true,                // Enables debug output
//         // 'verify'        => true,                // Enabled SSL verification. You can also give a path to a CA bundle file. Default is true.
//     ]);

//     $userData = [
//         'login' => $staff->next_cloud_email,
//         'password' => $staff->next_cloud_password,
//         'firstname' => $staff->name,
//         'lastname' => '-',
//         'email' => $staff->next_cloud_email,
//         'phone' => $staff->mobile,
//         'roles' => ['Agent'],
//     ];

//     $user = $client->resource( ZammadAPIClient\ResourceType::USER );
//     $user->setValues( $userData );

//     $user_result = $user->save();
//     return json_alert('Done');

// }

function button_staff_create_user_form($request)
{

    $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
    if (! empty($staff->user_id)) {
        return json_alert('User already set');
    }

    $url = get_menu_url_from_table('erp_users');
    session(['employee_id' => $staff->id]);

    return redirect()->to($url.'/edit?phone='.$staff->mobile.'&email='.$staff->next_cloud_email);
}

function button_staff_delete_user($request)
{

    $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
    if (empty($staff->user_id)) {
        return json_alert('User not set');
    }
    \DB::table('erp_users')->where('id', $staff->user_id)->update(['active' => 0, 'is_deleted' => 1]);

    return json_alert('Done');
}
function button_staff_edit_user($request)
{

    $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
    if (empty($staff->user_id)) {
        return json_alert('User not set');
    }

    $url = get_menu_url_from_table('erp_users');

    return redirect()->to($url.'/edit/'.$staff->user_id);
}

/*
function button_staff_add_user($request){


    $staff = \DB::table('hr_employees')->where('id',$request->id)->get()->first();
    if(!empty($staff->user_id)){
        return json_alert('User already set');
    }

    $url = get_menu_url_from_table('erp_users');
    $name_arr = explode(' ',$staff->name);
    $args = [
        'full_name' => $staff->name,
        'username' => strtolower($name_arr[0]),

    ];
    $url = $url.'/edit/'.http_build_query($args);

    return redirect()->to($url.'/edit/'.);
}
*/

function button_staff_add_supportboard_agent($request)
{
    if (! is_main_instance()) {
        return false;
    }

    $staff = \DB::table('hr_employees')->where('id', $request->id)->get()->first();
    if (empty($staff->next_cloud_email)) {
        return json_alert('next_cloud email needs to be set', 'warning');
    }
    if (empty($staff->next_cloud_password)) {
        return json_alert('next_cloud password needs to be set', 'warning');
    }

    if ($staff->user_id == 1 || $staff->user_id == 3696) {
        return json_alert('Done');
    }

    $name_parts = explode(' ', $staff->name);
    // Extract the last word as surname
    $surname = array_pop($name_parts);

    // The rest of the parts as first name
    $first_name = implode(' ', $name_parts);

    $data = [
        'token' => 'b7a0e33dc6067f7aadd57b68b41826ef94b6159a',
        'function' => 'add-user',
        'first_name' => $first_name,
        'last_name' => $surname,
        'email' => $staff->next_cloud_email,
        'password' => $staff->next_cloud_password,
        'user_type' => 'agent',
    ];
    $user_result = support_board_api($data);

    //aa($user_result);
    return json_alert('Done');

}
