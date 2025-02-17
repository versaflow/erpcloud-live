<?php

// function str_replace_last( $search , $replace , $str ) {
//     if( ( $pos = strrpos( $str , $search ) ) !== false ) {
//         $search_length  = strlen( $search );
//         $str    = substr_replace( $str , $replace , $pos , $search_length );
//     }
//     return $str;
// }

function stripslashesFull($input)
{
    if (is_array($input)) {
        $input = array_map('stripslashesFull', $input);
    } elseif (is_object($input)) {
        $vars = get_object_vars($input);
        foreach ($vars as $k => $v) {
            $input->{$k} = stripslashesFull($v);
        }
    } else {
        $input = stripslashes($input);
    }

    return $input;
}

function get_previous_workday($dateString = false)
{
    if (!$dateString) {
        $dateString = date('Y-m-d');
    }
    $date = new DateTime($dateString);

    if ($date->format('N') >= 6) { // Check if it's a Saturday (6) or Sunday (7)
        $date->modify('last friday');
    } elseif ($date->format('N') == 1) { // Check if it's Monday (1)
        $date->modify('-3 days'); // Go back three days to last Friday
    } else {
        $date->modify('-1 day'); // Go back one day for other weekdays
    }

    return $date->format('Y-m-d');
}

if (!function_exists('str_ireplace_first')) {
    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    function str_ireplace_first($search, $replace, $subject)
    {
        if ($search == '') {
            return $subject;
        }

        $position = strpos(strtolower($subject), strtolower($search));

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }
}

if (!function_exists('str_ireplace_last')) {
    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    function str_ireplace_last($search, $replace, $subject)
    {
        return Str::replaceLast(strtolower($search), strtolower($replace), strtolower($subject));
    }
}

function db_conns()
{
    return  $conns = \DB::connection('system')->table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
}

function db_conns_sync()
{
    return  $conns = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('sync_erp', 1)->orWhere('id', 1)->pluck('db_connection')->toArray();
}

function db_conns_excluding_current()
{
    return  $conns = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', session('instance')->id)->pluck('db_connection')->toArray();
}

function db_conns_excluding_main()
{
    return  $conns = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', '!=', 1)->pluck('db_connection')->toArray();
}

function get_string_between($str, $from, $to)
{
    $sub = substr($str, strpos($str, $from) + strlen($from), strlen($str));

    return substr($sub, 0, strpos($sub, $to));
}

function file_to_array($file_path, $delimiter = false)
{
    if ($delimiter) {
        return (new Rap2hpoutre\FastExcel\FastExcel())->configureCsv($delimiter)->import($file_path);
    }

    return (new Rap2hpoutre\FastExcel\FastExcel())->import($file_path);
}

function check_instance_session()
{
    $si = session('instance');
    if (empty($si)) {
        $hostname = request()->root();
        $hostname = str_replace(['http://', 'https://'], '', $hostname);
        $instance = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();
        session(['instance' => $instance]);
        session(['app_ids' => get_installed_app_ids()]);
    }
}

function verify_za_id_number($id_number, $gender = '', $foreigner = 0)
{
    $validated = false;
    if (is_numeric($id_number) && strlen($id_number) === 13) {
        $errors = false;

        $num_array = str_split($id_number);

        // Validate the day and month

        $id_month = $num_array[2].$num_array[3];

        $id_day = $num_array[4].$num_array[5];

        if ($id_month < 1 || $id_month > 12) {
            $errors = true;
        }

        if ($id_day < 1 || $id_day > 31) {
            $errors = true;
        }

        // Validate gender

        $id_gender = $num_array[6] >= 5 ? 'male' : 'female';

        if ($gender && strtolower($gender) !== $id_gender) {
            $errors = true;
        }

        // Validate citizenship

        // citizenship as per id number
        $id_foreigner = $num_array[10];

        // citizenship as per submission
        if (($foreigner || $id_foreigner) && (int) $foreigner !== (int) $id_foreigner) {
            $errors = true;
        }

        /**********************************
            Check Digit Verification
        **********************************/

        // Declare the arrays
        $even_digits = [];
        $odd_digits = [];

        // Loop through modified $num_array, storing the keys and their values in the above arrays
        foreach ($num_array as $index => $digit) {
            if ($index === 0 || $index % 2 === 0) {
                $odd_digits[] = $digit;
            } else {
                $even_digits[] = $digit;
            }
        }

        // use array pop to remove the last digit from $odd_digits and store it in $check_digit
        $check_digit = array_pop($odd_digits);

        //All digits in odd positions (excluding the check digit) must be added together.
        $added_odds = array_sum($odd_digits);

        //All digits in even positions must be concatenated to form a 6 digit number.
        $concatenated_evens = implode('', $even_digits);

        //This 6 digit number must then be multiplied by 2.
        $evensx2 = $concatenated_evens * 2;

        // Add all the numbers produced from the even numbers x 2
        $added_evens = array_sum(str_split($evensx2));

        $sum = $added_odds + $added_evens;

        // get the last digit of the $sum
        $last_digit = substr($sum, -1);

        /* 10 - $last_digit
         * $verify_check_digit = 10 - (int)$last_digit; (Will break if $last_digit = 0)
         * Edit suggested by Ruan Luies
         * verify check digit is the resulting remainder of
         *  10 minus the last digit divided by 10
         */
        $last_digit = (int) $last_digit;
        $verify_check_digit = (10 - $last_digit) % 10;

        // test expected last digit against the last digit in $id_number submitted
        if ((int) $verify_check_digit !== (int) $check_digit) {
            $errors = true;
        }

        // if errors haven't been set to true by any one of the checks, we can change verified to true;
        if (!$errors) {
            $validated = true;
        }
    }

    return $validated;
}

function get_select_menu_by_role($row)
{
    $opt = [];

    $menus = \DB::table('erp_menu')
        ->select('erp_menu.id', 'erp_menu.menu_name')
        ->join('erp_menu_role_access', 'erp_menu_role_access.menu_id', '=', 'erp_menu.id')
        ->where('erp_menu_role_access.role_id', $row['id'])
        ->where('erp_menu_role_access.is_menu', 1)
        ->get();

    foreach ($menus as $menu) {
        $opt[$menu->id] = $menu->menu_name;
    }

    return $opt;
}

function get_user_id_default()
{
    if (!empty(session('user_id'))) {
        return session('user_id');
    } else {
        return get_system_user_id();
    }
}

function get_menu_url_from_table($table)
{
    if (!empty(session('role_id'))) {
        $form_ids = app('erp_config')['forms']->where('role_id', session('role_id'))->where('is_view', 1)->pluck('module_id')->toArray();

        return app('erp_config')['modules']
        ->whereIn('id', $form_ids)
        ->where('db_table', $table)
        ->pluck('slug')->first();
    } else {
        return app('erp_config')['modules']
        ->where('db_table', $table)
        ->pluck('slug')->first();
    }
}

function get_menu_url_from_module_id($module_id)
{
    return app('erp_config')['modules']
        ->where('id', $module_id)
        ->pluck('slug')->first();
}

function get_menu_url_from_id($menu_id)
{
    return app('erp_config')['menus']->where('id', $menu_id)->pluck('slug')->first();
}

function get_menu_url($module_id)
{
    $table = app('erp_config')['modules']->where('id', $module_id)->pluck('db_table')->first();

    return get_menu_url_from_table($table);
}

function is_detail_module($module_id)
{
    return app('erp_config')['modules']->where('detail_module_id', $module_id)->count();
}

function get_system_user_id($conn = 'default')
{
    //if (!empty(session('system_user_id'))) {
    //    return session('system_user_id');
    //}
    $system_user_id = \DB::connection($conn)->table('erp_users')->where('username', 'system')->where('account_id', 0)->pluck('id')->first();
    if (!$system_user_id) {
        $system_user_id = \DB::connection($conn)->table('erp_users')->insertGetId(['username' => 'system', 'full_name' => 'system', 'account_id' => 0, 'role_id' => 0, 'active' => 0]);
    }
    //if ($conn == 'default') {
    //    session(['system_user_id' => $system_user_id]);
    //}
    return $system_user_id;
}

function add_rollback_connection($conn)
{
    if (!empty(session('rollback_connections'))) {
        $rollback_connections = session('rollback_connections');
        $rollback_connections[] = $conn;
        \DB::connection($conn)->beginTransaction();
        session(['rollback_connections' => $rollback_connections]);
    }
}

function uploads_path($module_id = false)
{
    $instance_dir = session('instance')->directory;
    $path = public_path().'/uploads/'.$instance_dir.'/';
    if ($module_id) {
        $dir = $module_id;
        $path .= $dir.'/';
    }

    return $path;
}

function uploads_url($module_id = false)
{
    $path = uploads_path($module_id);
    $path = str_replace(public_path(), '', $path);
    $url = url($path).'/';

    return $url;
}

function uploads_settings_path()
{
    $instance_dir = session('instance')->directory;

    return public_path().uploads_path(348);
}

function settings_url()
{
    $path = uploads_path(348);
    $path = str_replace(public_path(), '', $path);

    return url($path).'/';
}

function uploads_documents_path()
{
    $instance_dir = session('instance')->directory;

    return uploads_path(353);
}

function uploads_supplier_documents_path()
{
    $instance_dir = session('instance')->directory;

    return uploads_path(354);
}

function uploads_emailbuilder_path()
{
    $instance_dir = session('instance')->directory;

    return uploads_path(556);
}

function uploads_newsletter_path()
{
    $instance_dir = session('instance')->directory;

    return uploads_path(768);
}

function email_images_path()
{
    $instance_dir = session('instance')->directory;

    return public_path().'/emails/images/'.$instance_dir.'/';
}

function email_images_url()
{
    $path = email_images_path();
    $path = str_replace(public_path(), '', $path);

    return url($path).'/';
}

function attachments_path()
{
    check_instance_session();
    $instance_dir = session('instance')->directory;
    if (empty($instance_dir)) {
        $instance_dir = 'telecloud';
    }

    return public_path().'/attachments/'.$instance_dir.'/';
}

function attachments_url()
{
    $path = attachments_path();
    $path = str_replace(public_path(), '', $path);

    return url($path).'/';
}

if (!function_exists('str_replace_array')) {
    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param string $search
     * @param string $subject
     *
     * @return string
     */
    function str_replace_array($search, array $replace, $subject)
    {
        foreach ($replace as $value) {
            $subject = str_replace_first($search, $value, $subject);
        }

        return $subject;
    }
}

function ss($query)
{
    $sql_query = str_replace_array('?', $query->getBindings(), $query->toSql());
    $sql_query = str_ireplace('SELECT ', 'SELECT ', $sql_query);
    $sql_query = str_ireplace('JOIN ', 'JOIN ', $sql_query);
    $sql_query = str_ireplace('OUTER ', 'OUTER ', $sql_query);
    $sql_query = str_ireplace('AND ', 'AND ', $sql_query);
    $sql_query = str_ireplace('OR ', 'OR ', $sql_query);
    $sql_query = str_ireplace('WHERE ', PHP_EOL.'WHERE ', $sql_query);
    $sql_query = str_ireplace('FROM ', PHP_EOL.'FROM ', $sql_query);
    $sql_query = str_ireplace('RIGHT ', PHP_EOL.'RIGHT ', $sql_query);
    $sql_query = str_ireplace('LEFT ', PHP_EOL.'LEFT ', $sql_query);
    $sql_query = str_ireplace('INNER ', PHP_EOL.'INNER ', $sql_query);
    $sql_query = str_ireplace(',', ','.PHP_EOL, $sql_query);

    return $sql_query;
}

function querybuilder_to_sql($builder)
{
    $addSlashes = str_replace('?', "'?'", $builder->toSql());
    $sql = vsprintf(str_replace('?', '%s', $addSlashes), $builder->getBindings());

    return $sql;
}

// function querybuilder_to_sql($query)
// {
//     $sql_query = str_replace_array('?', $query->getBindings(), $query->toSql());
//     $sql_query = str_ireplace('SELECT ', 'SELECT ', $sql_query);
//     $sql_query = str_ireplace(' JOIN ', ' JOIN ', $sql_query);
//     $sql_query = str_ireplace(' OUTER ', ' OUTER ', $sql_query);
//     $sql_query = str_ireplace(' AND ', ' AND ', $sql_query);
//     $sql_query = str_ireplace(' OR ', ' OR ', $sql_query);
//     $sql_query = str_ireplace(' WHERE ', PHP_EOL.'WHERE ', $sql_query);
//     $sql_query = str_ireplace(' FROM ', PHP_EOL.'FROM ', $sql_query);
//     $sql_query = str_ireplace(' RIGHT ', PHP_EOL.'RIGHT ', $sql_query);
//     $sql_query = str_ireplace(' LEFT ', PHP_EOL.'LEFT ', $sql_query);
//     $sql_query = str_ireplace(' INNER ', PHP_EOL.'INNER ', $sql_query);
//     $sql_query = str_ireplace(',', ','.PHP_EOL, $sql_query);
//     $sql_query = str_ireplace(array("\r", "\n"), '', $sql_query);
//     return $sql_query;
// }

function get_lat_long($address)
{
    $apikey = 'AIzaSyCPKVDAAN0qhtiPJAzksKcK7njtRSo03Po';
    $address = str_replace(' ', '+', $address);
    $region = 'ZA';
    $url = "https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&region=$region&key=$apikey";

    $json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&region=$region&key=$apikey");

    $json = json_decode($json);

    if ($json->{'status'} != 'OK') {
        return 'API Key Error';
    }
    $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
    $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};

    return $lat.','.$long;
}

function email_form($email_id, $account_id = 1, $email_data = [])
{
    $data = $email_data;
    $data['email_id'] = $email_id;
    $data['year'] = date('Y');
    $data['date'] = date('Y-m-d');
    $data['afriphone_link'] = '<a href="https://play.google.com/store/apps/details?id=com.cloudtelecoms.phoneapp">Download Unlimited Mobile.</a>';
    $data['currency_symbol'] = 'R';
    $data['currency_symbol'] = get_account_currency_symbol($account_id);

    $remove_payment_options = \DB::table('erp_admin_settings')->where('id', 1)->pluck('remove_payment_options')->first();
    if ($email_data['activation_email']) {
        $template = \DB::connection('default')->table('crm_email_manager')->where('id', $email_id)->get()->first();
    } else {
        $template = \DB::connection('default')->table('crm_email_manager')->where('id', $email_id)->get()->first();
    }
    $admin = dbgetaccount(1);
    if (!empty($email_data['customer_type']) && $email_data['customer_type'] == 'supplier') {
        $account = \DB::connection('default')->table('crm_suppliers')->where('id', $account_id)->get()->first();
        $reseller = dbgetaccount(1);
    } else {
        $account = dbgetaccount($account_id);
        $reseller = dbgetaccount($account->partner_id);
        $data['customer_type'] = 'account';
    }

    $faq_link = get_admin_setting('activation_email_faq_link');
    if ($email_data['activation_email'] && $faq_link) {
        $template->message .= '<br><br>Please visit our Website FAQs if you need help with your setup. <br><a href="'.$faq_link.'" target="_blank">Click here to view FAQs.</a>';
    }

    //$data['accounts'] = \DB::table('crm_accounts')->select('id','company','email','type')->where('status','!=','Deleted')->where('partner_id',session('account_id'))->where('id','!=',1)->get();
    $data['account_id'] = $account->id;
    $data['emailaddress'] = $account->email;

    $data['partner_company'] = $reseller->company;
    $data['parent_company'] = $reseller->company;
    $data['partner_email'] = $reseller->email;

    if ($template->internal_function == 'create_account_settings') {
        if ($data['user_id']) {
            $user = \DB::table('erp_users')->where('id', $data['user_id'])->where('account_id', $account_id)->get()->first();
        } else {
            $user = \DB::table('erp_users')->where('account_id', $account_id)->get()->first();
        }
        /////// SEND NEW LOGIN DETAILS
        $pass = generate_strong_password();
        $hashed_password = \Hash::make($pass);
        \DB::table('erp_users')->where('id', $user->id)->update(['password' => $hashed_password]);
        $user_email = $user->email;
        $account = dbgetaccount($user->account_id);
        if (1 == $account->partner_id) {
            $portal = 'http://'.$_SERVER['HTTP_HOST'];
        } else {
            $portal = 'http://'.session('instance')->alias;
        }
        $function_variables = get_defined_vars();
        $data['internal_function'] = 'create_account_settings';

        $data['username'] = $user->username;
        $data['password'] = $pass;
        $data['login_url'] = $portal;
        if (1 == $account->partner_id) {
            $data['portal_name'] = 'Cloud Telecoms';
        } else {
            $reseller = dbgetaccount($account->partner_id);
            $data['portal_name'] = $reseller->company;
        }
    } elseif ($template->internal_function == 's') {
    }

    $data['partner_logo'] = '';
    if (file_exists(uploads_settings_path().$reseller->logo)) {
        $data['partner_logo'] = settings_url().$reseller->logo;
    }
    $data['customer'] = $account;
    if (empty($data['customer']->contact)) {
        $data['customer']->contact = $data['customer']->company;
    }
    if ($email_id == 1) {
        if (empty($data['subject'])) {
            $data['subject'] = '';
        }
    } else {
        $data['subject'] = erp_email_blend($template->name, $data);
    }

    $formatted = true;

    // webform link
    if ($template->webform_module_id > 0) {
        $webform_data = [];
        $webform_data['module_id'] = $template->webform_module_id;
        $webform_data['account_id'] = $account_id;
        if (!empty($data['record_id'])) {
            $webform_data['id'] = $data['record_id'];
        }
        if (!empty($data['subscription_id'])) {
            $webform_data['subscription_id'] = $data['subscription_id'];
        }
        $link_data = \Erp::encode($webform_data);
        $link_name = \DB::connection('default')->table('erp_cruds')->where('id', $template->webform_module_id)->pluck('name')->first();
        if ($template->webform_module_id == 390) {
            $link_name = 'Debit Order Mandate';
        }
        $data['webform_link'] = '<a href="'.request()->root().'/webform/'.$link_data.'" >'.$link_name.'</a>';
    }

    $data['paynow_button'] = '';
    if ($template->add_payment_options && 1 == $account->partner_id && $data['customer_type'] = 'account') {
        if ($data['show_debit_order_link']) {
            $data['paynow_button'] = '<br><br>Debit order required, click the link to submit your debit order and complete your order.<br> <b>'.$data['webform_link'].'</b>';
        } else {
            $data['paynow_button'] = generate_paynow_button($account->id, 100);
        }
    }

    if (!empty($template) && !empty($template->message)) {
        $data['msg'] = erp_email_blend($template->message, $data);
    }
    $data['html'] = get_email_html($account_id, $reseller->id, $data, $template);
    $data['html'] = str_replace('<div class="ms-editor-squiggler"></div>', '', $data['html']);
    $data['html'] = str_replace('<p></p> <br />', '', $data['html']);

    $data['msg'] = str_replace('<ol><br />', '<ol>', $data['msg']);
    $data['msg'] = str_replace('</ol><br />', '</ol>', $data['msg']);
    $data['msg'] = str_replace('</li><br />', '</li>', $data['msg']);

    $data['html'] = str_replace('<ol><br />', '<ol>', $data['html']);
    $data['html'] = str_replace('</ol><br />', '</ol>', $data['html']);
    $data['html'] = str_replace('</li><br />', '</li>', $data['html']);
    //aa($template);
    //aa($data['msg']);
    $data['css'] = '';
    $template_file = '_emails.gjs';

    $template_list = [];
    if (session('role_level') == 'Admin') {
        $templates = \DB::connection('default')->table('crm_newsletters')->select('id', 'name', 'type')->where('is_deleted', 0)->orderBy('id', 'desc')->get();
    }

    if (!empty($templates) && count($templates) > 0) {
        foreach ($templates as $t) {
            $t->category = $t->type;
            $t->type = 'newsletter';
            $t->id = 'newsletter'.$t->id;
            $template_list[] = $t;
        }
    }

    if (session('role_level') == 'Admin') {
        $templates = \DB::connection('default')->table('crm_email_manager')->select('id', 'name', 'from_email', 'module_id')->where('is_deleted', 0)->where('email_form', 1)->orderBy('module_id')->orderBy('name')->get();
    }

    if (!empty($templates) && count($templates) > 0) {
        foreach ($templates as $t) {
            $module_name = \DB::connection('default')->table('erp_cruds')->where('id', $t->module_id)->pluck('name')->first();
            $t->category = 'System';
            $t->type = 'notification';
            $t->id = 'notification'.$t->id;
            $template_list[] = $t;
        }
    }

    if (session('role_level') == 'Admin') {
        $templates = \DB::connection('default')->table('crm_email_manager')->select('id', 'name', 'from_email', 'module_id')->where('is_deleted', 0)->where('ad_email', 1)->orderBy('module_id')->orderBy('name')->get();
    }

    if (!empty($templates) && count($templates) > 0) {
        foreach ($templates as $t) {
            $module_name = \DB::connection('default')->table('erp_cruds')->where('id', $t->module_id)->pluck('name')->first();
            $t->category = 'Ad Emails';
            $t->type = 'notification';
            $t->id = 'notification'.$t->id;
            $template_list[] = $t;
        }
    }

    if (session('role_level') == 'Admin') {
        $templates = \DB::connection('default')->table('crm_email_manager')->select('id', 'name', 'from_email', 'module_id')->where('is_deleted', 0)->where('debtor_email', 1)->orderBy('module_id')->orderBy('name')->get();
    }

    if (!empty($templates) && count($templates) > 0) {
        foreach ($templates as $t) {
            $module_name = \DB::connection('default')->table('erp_cruds')->where('id', $t->module_id)->pluck('name')->first();
            $t->category = 'Debtor Emails';
            $t->type = 'notification';
            $t->id = 'notification'.$t->id;
            $template_list[] = $t;
        }
    }

    if (in_array(7, session('app_ids'))) {
        if (is_superadmin()) {
            $articles = \DB::connection('default')->table('hd_customer_faqs')->where('is_deleted', 0)->get();
        } else {
            $articles = \DB::connection('default')->table('hd_customer_faqs')->where('is_deleted', 0)->where('internal', 0)->get();
        }
        foreach ($articles as $t) {
            $t->category = 'Customer FAQs';
            $t->type = 'faq';
            $t->id = 'faq'.$t->id;
            $template_list[] = $t;
        }
    }

    $data['templates'] = collect($template_list)->toArray();
    $data['message'] = view($template_file, $data);

    $admin = dbgetaccount(1);

    if (!empty($data['to_email'])) {
        $log_address = $data['to_email'];
    } elseif (erp_email_valid($template->to_email)) {
        $log_address = $template->to_email;
        $data['to_email'] = $template->to_email;
    } else {
        $log_address = $account->email;
        $data['to_email'] = $account->email;
    }

    if (!empty($data['user_email']) && erp_email_valid($data['user_email'])) {
        $data['user_email'] = erp_email_valid($data['user_email']);
        $data['to_email'] = $data['user_email'];
    }

    $admin_settings = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();
    $recipients = get_email_recipients($data['customer_type'], $template, $account, $reseller, $admin);
    foreach ($recipients as $key => $val) {
        $data[$key] = $val;
    }

    if ('No Reply' == $template->from_email) {
        $from_email_arr = explode('@', $admin_settings->notification_support);
        $data['from_email'] = 'no-reply@'.$from_email_arr[1];
    }

    if ('Accounts' == $template->from_email) {
        $data['from_email'] = $admin_settings->notification_account;
    }

    if ('Sales' == $template->from_email) {
        $data['from_email'] = $admin_settings->notification_sales;
    }

    if ('Marketing' == $template->from_email) {
        $data['from_email'] = $admin_settings->notification_marketing;
    }

    if ('Helpdesk' == $template->from_email) {
        $data['from_email'] = $admin_settings->notification_support;
    }

    if (!empty($data['force_to_email'])) {
        $data['to_email'] = $data['force_to_email'];
    }

    if (erp_email_valid($data['from_email']) && 1 == $account->partner_id) {
        $data['from_email'] = $data['from_email'];
    } elseif (erp_email_valid($message_template->from_email) && 1 == $account->partner_id) {
        $data['from_email'] = $message_template->from_email;
    } elseif (1 != $account->partner_id) {
        $data['from_email'] = 'helpdesk@erpcloud.co.za';
    }
    if (empty($data['attachments'])) {
        $data['attachments'] = [];
    }

    if (!empty($email_data['attachments']) && is_array($email_data['attachments']) && count($email_data['attachments']) > 0) {
        $data['attachments'] = $email_data['attachments'];
    }

    if (!empty($email_data['attachment'])) {
        $data['attachments'][] = $email_data['attachment'];
    }
    if (!empty($template->attachment_file)) {
        $attachments = explode(',', $template->attachment_file);
        foreach ($attachments as $file) {
            if (file_exists(uploads_emailbuilder_path().$file)) {
                \File::copy(uploads_emailbuilder_path().$file, attachments_path().$file);
                $data['attachments'][] = $file;
            }
        }
    }

    if ($template->internal_function == 'ip_address_followup') {
        $data['attachments'][] = export_available_ipranges();
    }

    if ($template->id == 590) {
        $data['attachments'][] = 'Available Phone Numbers.xlsx';
    }

    if (!empty($template->attach_statement)) {
        $pdf = statement_pdf($account->id);
        $file = 'Statement_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $data['attachments'][] = $file;
    }
    if (!empty($template->attach_full_statement)) {
        $pdf = statement_pdf($account->id, true);
        $file = 'Complete_Statement_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $data['attachments'][] = $file;
    }
    if (session('instance')->id != 11 && !empty($template->attach_letter_of_demand)) {
        $pdf = collectionspdf($account->id, $template->id);
        $name = ucfirst(str_replace(' ', '_', $template->name));
        $file = $name.'_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $data['attachments'][] = $file;

        if (!str_contains($data['subject'], 'Letter of demand')) {
            $data['subject'] .= ' (Letter of demand)';
        }
    }
    if (!empty($template->attach_cancellation_letter)) {
        $pdf = cancellationpdf($account->id);
        $file = 'Cancellation_letter_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }

        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);

        $data['attachments'][] = $file;
    }
    $data['notification_id'] = $email_id;

    $data['cc_emails'] = [];
    if (erp_email_valid($account->email)) {
        $data['cc_emails'][] = $account->email;
    }

    if (!empty($data['cc_email']) && $data['cc_email'] != $data['to_email']) {
        $data['cc_emails'][] = $data['cc_email'];
    }

    $data['cc_emails'] = collect($data['cc_emails'])->filter()->unique()->toArray();
    if (empty($data['cc_email']) && count($data['cc_emails']) > 0) {
        $data['cc_email'] = $data['cc_emails'][0];
    }

    if (!empty($data['cc_emails']) && count($data['cc_emails']) > 0) {
        foreach ($data['cc_emails'] as $i => $cc_email) {
            if ($cc_email == $data['to_email']) {
                unset($data['cc_emails'][$i]);
            }
        }
    }
    $data['cc_emails'] = array_values($data['cc_emails']);

    if ($data['bcc_email'] == $data['to_email']) {
        unset($data['bcc_email']);
    }
    $accounting_email = get_account_contact_email($account->id, 'Accounting');
    if ($template->add_payment_options && !empty($accounting_email)) {
        $data['cc_email'] = $accounting_email;
    }

    if ($data['cc_email'] == $data['to_email']) {
        unset($data['cc_email']);
    }
    if (session('instance')->id == 11) {
        //return redirect()->to('ticket_system_compose/'.$data['to_email']);
    }
    if ($data['bccemailaddress']) {
        $data['bcc_email'] = $data['bccemailaddress'];
    }
    if (!empty($data['provision_id'])) {
        $data['hide_form_tags'] = 1;
    }

    if (!empty($data['account_id'])) {
        if ($data['account_id'] == 1) {
            $data['to_email'] = '';
        }
        if (is_dev()) {
            return view('__app.components.email_dev', $data);
        } else {
            return view('__app.components.email', $data);
        }
    } else {
        echo 'Invalid Form';
    }
}

function get_submenu_ids($menu_id, $menu_collection = false)
{
    if (!$menu_collection) {
        $menu_collection = \DB::connection('default')->table('erp_menu')->get();
    }
    $menu_ids = $menu_collection->where('parent_id', $menu_id)->pluck('id')->toArray();
    $menu_ids = collect($menu_ids);
    $sub_menus = $menu_collection->where('parent_id', $menu_id);
    if (!empty($sub_menus) && count($sub_menus) > 0) {
        foreach ($sub_menus as $menu) {
            $sub_menu_ids = get_submenu_ids($menu->id, $menu_collection);
            $sub_menu_ids = collect($sub_menu_ids);
            $menu_ids = $menu_ids->merge($sub_menu_ids);
        }
    }

    $menu_ids = collect($menu_ids)->unique()->toArray();

    return $menu_ids;
}

function get_toplevel_menu_id($menu_id, $conn = 'default')
{
    $parent_id = \DB::connection($conn)->table('erp_menu')->where('id', $menu_id)->pluck('parent_id')->first();
    if ($parent_id == 0) {
        return $menu_id;
    } else {
        $exists = \DB::connection($conn)->table('erp_menu')->where('id', $parent_id)->count();
        if (!$exists) {
            return $menu_id;
        }

        return get_toplevel_menu_id($parent_id, $conn);
    }
}

function get_parentmenu_ids($submenu_id)
{
    $menu_ids = [];
    $parent_menu_id = \DB::connection('default')->table('erp_menu')->where('id', $submenu_id)->pluck('parent_id')->first();
    $menu_ids[] = $parent_menu_id;
    while ($parent_menu_id != 0) {
        $parent_menu_id = \DB::connection('default')->table('erp_menu')->where('id', $parent_menu_id)->pluck('parent_id')->first();
        $menu_ids[] = $parent_menu_id;
    }
    $menu_ids = collect($menu_ids)->filter()->unique()->toArray();

    return $menu_ids;
}

function role_ids_select($row)
{
    $row = (array) $row;
    $select = [];
    $roles = \DB::connection('default')->table('erp_user_roles')->get();
    foreach ($roles as $role) {
        if ($row['account_id'] == 1 && $role->level != 'Admin') {
            continue;
        }
        if (session('role_level') == 'Admin') {
        } else {
            if (session('role_id') != $role->id) {
                continue;
            }
        }
        $select[$role->id] = $role->name;
    }

    return $select;
}

function user_and_role_select($row)
{
    $row = (array) $row;
    $select = [];
    $users = \DB::connection('default')->table('erp_users')
    ->select('erp_users.id', 'erp_user_roles.name', 'erp_users.full_name')
    ->join('erp_user_roles', 'erp_user_roles.id', '=', 'erp_users.role_id')
    ->where('account_id', 1)
    ->orderBy('erp_user_roles.sort_order')
    ->orderBy('erp_users.full_name')
    ->get();
    foreach ($users as $user) {
        $select[$user->id] = $user->full_name.' - '.$user->name;
    }

    return $select;
}

function get_country_codes()
{
    $country_codes = countries_json();
    $opt = [];
    foreach ($country_codes as $cc) {
        $opt[$cc['code']] = ucwords(strtolower($cc['name']));
    }

    return $opt;
}

function get_country_codes_select()
{
    $list = [];
    $codes = get_country_codes();
    foreach ($codes as $k => $v) {
        $list[] = ['text' => $v, 'value' => $k];
    }

    return $list;
}

function get_countries_select()
{
    $country_codes = countries_json();
    $opt = [];
    foreach ($country_codes as $cc) {
        $opt[strtolower($cc['name'])] = ucwords(strtolower($cc['name']));
    }

    return $opt;
}

function countries_json()
{
    return json_decode('[{
    "name": "Afghanistan",
    "dial_code": "+93",
    "code": "AF"
  }, {
    "name": "Aland Islands",
    "dial_code": "+358",
    "code": "AX"
  }, {
    "name": "Albania",
    "dial_code": "+355",
    "code": "AL"
  }, {
    "name": "Algeria",
    "dial_code": "+213",
    "code": "DZ"
  }, {
    "name": "AmericanSamoa",
    "dial_code": "+1684",
    "code": "AS"
  }, {
    "name": "Andorra",
    "dial_code": "+376",
    "code": "AD"
  }, {
    "name": "Angola",
    "dial_code": "+244",
    "code": "AO"
  }, {
    "name": "Anguilla",
    "dial_code": "+1264",
    "code": "AI"
  }, {
    "name": "Antarctica",
    "dial_code": "+672",
    "code": "AQ"
  }, {
    "name": "Antigua and Barbuda",
    "dial_code": "+1268",
    "code": "AG"
  }, {
    "name": "Argentina",
    "dial_code": "+54",
    "code": "AR"
  }, {
    "name": "Armenia",
    "dial_code": "+374",
    "code": "AM"
  }, {
    "name": "Aruba",
    "dial_code": "+297",
    "code": "AW"
  }, {
    "name": "Australia",
    "dial_code": "+61",
    "code": "AU"
  }, {
    "name": "Austria",
    "dial_code": "+43",
    "code": "AT"
  }, {
    "name": "Azerbaijan",
    "dial_code": "+994",
    "code": "AZ"
  }, {
    "name": "Bahamas",
    "dial_code": "+1242",
    "code": "BS"
  }, {
    "name": "Bahrain",
    "dial_code": "+973",
    "code": "BH"
  }, {
    "name": "Bangladesh",
    "dial_code": "+880",
    "code": "BD"
  }, {
    "name": "Barbados",
    "dial_code": "+1246",
    "code": "BB"
  }, {
    "name": "Belarus",
    "dial_code": "+375",
    "code": "BY"
  }, {
    "name": "Belgium",
    "dial_code": "+32",
    "code": "BE"
  }, {
    "name": "Belize",
    "dial_code": "+501",
    "code": "BZ"
  }, {
    "name": "Benin",
    "dial_code": "+229",
    "code": "BJ"
  }, {
    "name": "Bermuda",
    "dial_code": "+1441",
    "code": "BM"
  }, {
    "name": "Bhutan",
    "dial_code": "+975",
    "code": "BT"
  }, {
    "name": "Bolivia, Plurinational State of bolivia",
    "dial_code": "+591",
    "code": "BO"
  }, {
    "name": "Bosnia and Herzegovina",
    "dial_code": "+387",
    "code": "BA"
  }, {
    "name": "Botswana",
    "dial_code": "+267",
    "code": "BW"
  }, {
    "name": "Brazil",
    "dial_code": "+55",
    "code": "BR"
  }, {
    "name": "British Indian Ocean Territory",
    "dial_code": "+246",
    "code": "IO"
  }, {
    "name": "Brunei Darussalam",
    "dial_code": "+673",
    "code": "BN"
  }, {
    "name": "Bulgaria",
    "dial_code": "+359",
    "code": "BG"
  }, {
    "name": "Burkina Faso",
    "dial_code": "+226",
    "code": "BF"
  }, {
    "name": "Burundi",
    "dial_code": "+257",
    "code": "BI"
  }, {
    "name": "Cambodia",
    "dial_code": "+855",
    "code": "KH"
  }, {
    "name": "Cameroon",
    "dial_code": "+237",
    "code": "CM"
  }, {
    "name": "Canada",
    "dial_code": "+1",
    "code": "CA"
  }, {
    "name": "Cape Verde",
    "dial_code": "+238",
    "code": "CV"
  }, {
    "name": "Cayman Islands",
    "dial_code": "+ 345",
    "code": "KY"
  }, {
    "name": "Central African Republic",
    "dial_code": "+236",
    "code": "CF"
  }, {
    "name": "Chad",
    "dial_code": "+235",
    "code": "TD"
  }, {
    "name": "Chile",
    "dial_code": "+56",
    "code": "CL"
  }, {
    "name": "China",
    "dial_code": "+86",
    "code": "CN"
  }, {
    "name": "Christmas Island",
    "dial_code": "+61",
    "code": "CX"
  }, {
    "name": "Cocos (Keeling) Islands",
    "dial_code": "+61",
    "code": "CC"
  }, {
    "name": "Colombia",
    "dial_code": "+57",
    "code": "CO"
  }, {
    "name": "Comoros",
    "dial_code": "+269",
    "code": "KM"
  }, {
    "name": "Congo",
    "dial_code": "+242",
    "code": "CG"
  }, {
    "name": "Congo, The Democratic Republic of the Congo",
    "dial_code": "+243",
    "code": "CD"
  }, {
    "name": "Cook Islands",
    "dial_code": "+682",
    "code": "CK"
  }, {
    "name": "Costa Rica",
    "dial_code": "+506",
    "code": "CR"
  }, {
    "name": "Cote d\'Ivoire",
    "dial_code": "+225",
    "code": "CI"
  }, {
    "name": "Croatia",
    "dial_code": "+385",
    "code": "HR"
  }, {
    "name": "Cuba",
    "dial_code": "+53",
    "code": "CU"
  }, {
    "name": "Cyprus",
    "dial_code": "+357",
    "code": "CY"
  }, {
    "name": "Czech Republic",
    "dial_code": "+420",
    "code": "CZ"
  }, {
    "name": "Denmark",
    "dial_code": "+45",
    "code": "DK"
  }, {
    "name": "Djibouti",
    "dial_code": "+253",
    "code": "DJ"
  }, {
    "name": "Dominica",
    "dial_code": "+1767",
    "code": "DM"
  }, {
    "name": "Dominican Republic",
    "dial_code": "+1849",
    "code": "DO"
  }, {
    "name": "Ecuador",
    "dial_code": "+593",
    "code": "EC"
  }, {
    "name": "Egypt",
    "dial_code": "+20",
    "code": "EG"
  }, {
    "name": "El Salvador",
    "dial_code": "+503",
    "code": "SV"
  }, {
    "name": "Equatorial Guinea",
    "dial_code": "+240",
    "code": "GQ"
  }, {
    "name": "Eritrea",
    "dial_code": "+291",
    "code": "ER"
  }, {
    "name": "Estonia",
    "dial_code": "+372",
    "code": "EE"
  }, {
    "name": "Ethiopia",
    "dial_code": "+251",
    "code": "ET"
  }, {
    "name": "Falkland Islands (Malvinas)",
    "dial_code": "+500",
    "code": "FK"
  }, {
    "name": "Faroe Islands",
    "dial_code": "+298",
    "code": "FO"
  }, {
    "name": "Fiji",
    "dial_code": "+679",
    "code": "FJ"
  }, {
    "name": "Finland",
    "dial_code": "+358",
    "code": "FI"
  }, {
    "name": "France",
    "dial_code": "+33",
    "code": "FR"
  }, {
    "name": "French Guiana",
    "dial_code": "+594",
    "code": "GF"
  }, {
    "name": "French Polynesia",
    "dial_code": "+689",
    "code": "PF"
  }, {
    "name": "Gabon",
    "dial_code": "+241",
    "code": "GA"
  }, {
    "name": "Gambia",
    "dial_code": "+220",
    "code": "GM"
  }, {
    "name": "Georgia",
    "dial_code": "+995",
    "code": "GE"
  }, {
    "name": "Germany",
    "dial_code": "+49",
    "code": "DE"
  }, {
    "name": "Ghana",
    "dial_code": "+233",
    "code": "GH"
  }, {
    "name": "Gibraltar",
    "dial_code": "+350",
    "code": "GI"
  }, {
    "name": "Greece",
    "dial_code": "+30",
    "code": "GR"
  }, {
    "name": "Greenland",
    "dial_code": "+299",
    "code": "GL"
  }, {
    "name": "Grenada",
    "dial_code": "+1473",
    "code": "GD"
  }, {
    "name": "Guadeloupe",
    "dial_code": "+590",
    "code": "GP"
  }, {
    "name": "Guam",
    "dial_code": "+1671",
    "code": "GU"
  }, {
    "name": "Guatemala",
    "dial_code": "+502",
    "code": "GT"
  }, {
    "name": "Guernsey",
    "dial_code": "+44",
    "code": "GG"
  }, {
    "name": "Guinea",
    "dial_code": "+224",
    "code": "GN"
  }, {
    "name": "Guinea-Bissau",
    "dial_code": "+245",
    "code": "GW"
  }, {
    "name": "Guyana",
    "dial_code": "+592",
    "code": "GY"
  }, {
    "name": "Haiti",
    "dial_code": "+509",
    "code": "HT"
  }, {
    "name": "Holy See (Vatican City State)",
    "dial_code": "+379",
    "code": "VA"
  }, {
    "name": "Honduras",
    "dial_code": "+504",
    "code": "HN"
  }, {
    "name": "Hong Kong",
    "dial_code": "+852",
    "code": "HK"
  }, {
    "name": "Hungary",
    "dial_code": "+36",
    "code": "HU"
  }, {
    "name": "Iceland",
    "dial_code": "+354",
    "code": "IS"
  }, {
    "name": "India",
    "dial_code": "+91",
    "code": "IN"
  }, {
    "name": "Indonesia",
    "dial_code": "+62",
    "code": "ID"
  }, {
    "name": "Iran, Islamic Republic of Persian Gulf",
    "dial_code": "+98",
    "code": "IR"
  }, {
    "name": "Iraq",
    "dial_code": "+964",
    "code": "IQ"
  }, {
    "name": "Ireland",
    "dial_code": "+353",
    "code": "IE"
  }, {
    "name": "Isle of Man",
    "dial_code": "+44",
    "code": "IM"
  }, {
    "name": "Israel",
    "dial_code": "+972",
    "code": "IL"
  }, {
    "name": "Italy",
    "dial_code": "+39",
    "code": "IT"
  }, {
    "name": "Jamaica",
    "dial_code": "+1876",
    "code": "JM"
  }, {
    "name": "Japan",
    "dial_code": "+81",
    "code": "JP"
  }, {
    "name": "Jersey",
    "dial_code": "+44",
    "code": "JE"
  }, {
    "name": "Jordan",
    "dial_code": "+962",
    "code": "JO"
  }, {
    "name": "Kazakhstan",
    "dial_code": "+7",
    "code": "KZ"
  }, {
    "name": "Kenya",
    "dial_code": "+254",
    "code": "KE"
  }, {
    "name": "Kiribati",
    "dial_code": "+686",
    "code": "KI"
  }, {
    "name": "Korea, Democratic People\'s Republic of Korea",
    "dial_code": "+850",
    "code": "KP"
  }, {
    "name": "Korea, Republic of South Korea",
    "dial_code": "+82",
    "code": "KR"
  }, {
    "name": "Kuwait",
    "dial_code": "+965",
    "code": "KW"
  }, {
    "name": "Kyrgyzstan",
    "dial_code": "+996",
    "code": "KG"
  }, {
    "name": "Laos",
    "dial_code": "+856",
    "code": "LA"
  }, {
    "name": "Latvia",
    "dial_code": "+371",
    "code": "LV"
  }, {
    "name": "Lebanon",
    "dial_code": "+961",
    "code": "LB"
  }, {
    "name": "Lesotho",
    "dial_code": "+266",
    "code": "LS"
  }, {
    "name": "Liberia",
    "dial_code": "+231",
    "code": "LR"
  }, {
    "name": "Libyan Arab Jamahiriya",
    "dial_code": "+218",
    "code": "LY"
  }, {
    "name": "Liechtenstein",
    "dial_code": "+423",
    "code": "LI"
  }, {
    "name": "Lithuania",
    "dial_code": "+370",
    "code": "LT"
  }, {
    "name": "Luxembourg",
    "dial_code": "+352",
    "code": "LU"
  }, {
    "name": "Macao",
    "dial_code": "+853",
    "code": "MO"
  }, {
    "name": "Macedonia",
    "dial_code": "+389",
    "code": "MK"
  }, {
    "name": "Madagascar",
    "dial_code": "+261",
    "code": "MG"
  }, {
    "name": "Malawi",
    "dial_code": "+265",
    "code": "MW"
  }, {
    "name": "Malaysia",
    "dial_code": "+60",
    "code": "MY"
  }, {
    "name": "Maldives",
    "dial_code": "+960",
    "code": "MV"
  }, {
    "name": "Mali",
    "dial_code": "+223",
    "code": "ML"
  }, {
    "name": "Malta",
    "dial_code": "+356",
    "code": "MT"
  }, {
    "name": "Marshall Islands",
    "dial_code": "+692",
    "code": "MH"
  }, {
    "name": "Martinique",
    "dial_code": "+596",
    "code": "MQ"
  }, {
    "name": "Mauritania",
    "dial_code": "+222",
    "code": "MR"
  }, {
    "name": "Mauritius",
    "dial_code": "+230",
    "code": "MU"
  }, {
    "name": "Mayotte",
    "dial_code": "+262",
    "code": "YT"
  }, {
    "name": "Mexico",
    "dial_code": "+52",
    "code": "MX"
  }, {
    "name": "Micronesia, Federated States of Micronesia",
    "dial_code": "+691",
    "code": "FM"
  }, {
    "name": "Moldova",
    "dial_code": "+373",
    "code": "MD"
  }, {
    "name": "Monaco",
    "dial_code": "+377",
    "code": "MC"
  }, {
    "name": "Mongolia",
    "dial_code": "+976",
    "code": "MN"
  }, {
    "name": "Montenegro",
    "dial_code": "+382",
    "code": "ME"
  }, {
    "name": "Montserrat",
    "dial_code": "+1664",
    "code": "MS"
  }, {
    "name": "Morocco",
    "dial_code": "+212",
    "code": "MA"
  }, {
    "name": "Mozambique",
    "dial_code": "+258",
    "code": "MZ"
  }, {
    "name": "Myanmar",
    "dial_code": "+95",
    "code": "MM"
  }, {
    "name": "Namibia",
    "dial_code": "+264",
    "code": "NA"
  }, {
    "name": "Nauru",
    "dial_code": "+674",
    "code": "NR"
  }, {
    "name": "Nepal",
    "dial_code": "+977",
    "code": "NP"
  }, {
    "name": "Netherlands",
    "dial_code": "+31",
    "code": "NL"
  }, {
    "name": "Netherlands Antilles",
    "dial_code": "+599",
    "code": "AN"
  }, {
    "name": "New Caledonia",
    "dial_code": "+687",
    "code": "NC"
  }, {
    "name": "New Zealand",
    "dial_code": "+64",
    "code": "NZ"
  }, {
    "name": "Nicaragua",
    "dial_code": "+505",
    "code": "NI"
  }, {
    "name": "Niger",
    "dial_code": "+227",
    "code": "NE"
  }, {
    "name": "Nigeria",
    "dial_code": "+234",
    "code": "NG"
  }, {
    "name": "Niue",
    "dial_code": "+683",
    "code": "NU"
  }, {
    "name": "Norfolk Island",
    "dial_code": "+672",
    "code": "NF"
  }, {
    "name": "Northern Mariana Islands",
    "dial_code": "+1670",
    "code": "MP"
  }, {
    "name": "Norway",
    "dial_code": "+47",
    "code": "NO"
  }, {
    "name": "Oman",
    "dial_code": "+968",
    "code": "OM"
  }, {
    "name": "Pakistan",
    "dial_code": "+92",
    "code": "PK"
  }, {
    "name": "Palau",
    "dial_code": "+680",
    "code": "PW"
  }, {
    "name": "Palestinian Territory, Occupied",
    "dial_code": "+970",
    "code": "PS"
  }, {
    "name": "Panama",
    "dial_code": "+507",
    "code": "PA"
  }, {
    "name": "Papua New Guinea",
    "dial_code": "+675",
    "code": "PG"
  }, {
    "name": "Paraguay",
    "dial_code": "+595",
    "code": "PY"
  }, {
    "name": "Peru",
    "dial_code": "+51",
    "code": "PE"
  }, {
    "name": "Philippines",
    "dial_code": "+63",
    "code": "PH"
  }, {
    "name": "Pitcairn",
    "dial_code": "+64",
    "code": "PN"
  }, {
    "name": "Poland",
    "dial_code": "+48",
    "code": "PL"
  }, {
    "name": "Portugal",
    "dial_code": "+351",
    "code": "PT"
  }, {
    "name": "Puerto Rico",
    "dial_code": "+1939",
    "code": "PR"
  }, {
    "name": "Qatar",
    "dial_code": "+974",
    "code": "QA"
  }, {
    "name": "Romania",
    "dial_code": "+40",
    "code": "RO"
  }, {
    "name": "Russia",
    "dial_code": "+7",
    "code": "RU"
  }, {
    "name": "Rwanda",
    "dial_code": "+250",
    "code": "RW"
  }, {
    "name": "Reunion",
    "dial_code": "+262",
    "code": "RE"
  }, {
    "name": "Saint Barthelemy",
    "dial_code": "+590",
    "code": "BL"
  }, {
    "name": "Saint Helena, Ascension and Tristan Da Cunha",
    "dial_code": "+290",
    "code": "SH"
  }, {
    "name": "Saint Kitts and Nevis",
    "dial_code": "+1869",
    "code": "KN"
  }, {
    "name": "Saint Lucia",
    "dial_code": "+1758",
    "code": "LC"
  }, {
    "name": "Saint Martin",
    "dial_code": "+590",
    "code": "MF"
  }, {
    "name": "Saint Pierre and Miquelon",
    "dial_code": "+508",
    "code": "PM"
  }, {
    "name": "Saint Vincent and the Grenadines",
    "dial_code": "+1784",
    "code": "VC"
  }, {
    "name": "Samoa",
    "dial_code": "+685",
    "code": "WS"
  }, {
    "name": "San Marino",
    "dial_code": "+378",
    "code": "SM"
  }, {
    "name": "Sao Tome and Principe",
    "dial_code": "+239",
    "code": "ST"
  }, {
    "name": "Saudi Arabia",
    "dial_code": "+966",
    "code": "SA"
  }, {
    "name": "Senegal",
    "dial_code": "+221",
    "code": "SN"
  }, {
    "name": "Serbia",
    "dial_code": "+381",
    "code": "RS"
  }, {
    "name": "Seychelles",
    "dial_code": "+248",
    "code": "SC"
  }, {
    "name": "Sierra Leone",
    "dial_code": "+232",
    "code": "SL"
  }, {
    "name": "Singapore",
    "dial_code": "+65",
    "code": "SG"
  }, {
    "name": "Slovakia",
    "dial_code": "+421",
    "code": "SK"
  }, {
    "name": "Slovenia",
    "dial_code": "+386",
    "code": "SI"
  }, {
    "name": "Solomon Islands",
    "dial_code": "+677",
    "code": "SB"
  }, {
    "name": "Somalia",
    "dial_code": "+252",
    "code": "SO"
  }, {
    "name": "South Africa",
    "dial_code": "+27",
    "code": "ZA"
  }, {
    "name": "South Sudan",
    "dial_code": "+211",
    "code": "SS"
  }, {
    "name": "South Georgia and the South Sandwich Islands",
    "dial_code": "+500",
    "code": "GS"
  }, {
    "name": "Spain",
    "dial_code": "+34",
    "code": "ES"
  }, {
    "name": "Sri Lanka",
    "dial_code": "+94",
    "code": "LK"
  }, {
    "name": "Sudan",
    "dial_code": "+249",
    "code": "SD"
  }, {
    "name": "Suriname",
    "dial_code": "+597",
    "code": "SR"
  }, {
    "name": "Svalbard and Jan Mayen",
    "dial_code": "+47",
    "code": "SJ"
  }, {
    "name": "Swaziland",
    "dial_code": "+268",
    "code": "SZ"
  }, {
    "name": "Sweden",
    "dial_code": "+46",
    "code": "SE"
  }, {
    "name": "Switzerland",
    "dial_code": "+41",
    "code": "CH"
  }, {
    "name": "Syrian Arab Republic",
    "dial_code": "+963",
    "code": "SY"
  }, {
    "name": "Taiwan",
    "dial_code": "+886",
    "code": "TW"
  }, {
    "name": "Tajikistan",
    "dial_code": "+992",
    "code": "TJ"
  }, {
    "name": "Tanzania, United Republic of Tanzania",
    "dial_code": "+255",
    "code": "TZ"
  }, {
    "name": "Thailand",
    "dial_code": "+66",
    "code": "TH"
  }, {
    "name": "Timor-Leste",
    "dial_code": "+670",
    "code": "TL"
  }, {
    "name": "Togo",
    "dial_code": "+228",
    "code": "TG"
  }, {
    "name": "Tokelau",
    "dial_code": "+690",
    "code": "TK"
  }, {
    "name": "Tonga",
    "dial_code": "+676",
    "code": "TO"
  }, {
    "name": "Trinidad and Tobago",
    "dial_code": "+1868",
    "code": "TT"
  }, {
    "name": "Tunisia",
    "dial_code": "+216",
    "code": "TN"
  }, {
    "name": "Turkey",
    "dial_code": "+90",
    "code": "TR"
  }, {
    "name": "Turkmenistan",
    "dial_code": "+993",
    "code": "TM"
  }, {
    "name": "Turks and Caicos Islands",
    "dial_code": "+1649",
    "code": "TC"
  }, {
    "name": "Tuvalu",
    "dial_code": "+688",
    "code": "TV"
  }, {
    "name": "Uganda",
    "dial_code": "+256",
    "code": "UG"
  }, {
    "name": "Ukraine",
    "dial_code": "+380",
    "code": "UA"
  }, {
    "name": "United Arab Emirates",
    "dial_code": "+971",
    "code": "AE"
  }, {
    "name": "United Kingdom",
    "dial_code": "+44",
    "code": "GB"
  }, {
    "name": "United States",
    "dial_code": "+1",
    "code": "US"
  }, {
    "name": "Uruguay",
    "dial_code": "+598",
    "code": "UY"
  }, {
    "name": "Uzbekistan",
    "dial_code": "+998",
    "code": "UZ"
  }, {
    "name": "Vanuatu",
    "dial_code": "+678",
    "code": "VU"
  }, {
    "name": "Venezuela, Bolivarian Republic of Venezuela",
    "dial_code": "+58",
    "code": "VE"
  }, {
    "name": "Vietnam",
    "dial_code": "+84",
    "code": "VN"
  }, {
    "name": "Virgin Islands, British",
    "dial_code": "+1284",
    "code": "VG"
  }, {
    "name": "Virgin Islands, U.S.",
    "dial_code": "+1340",
    "code": "VI"
  }, {
    "name": "Wallis and Futuna",
    "dial_code": "+681",
    "code": "WF"
  }, {
    "name": "Yemen",
    "dial_code": "+967",
    "code": "YE"
  }, {
    "name": "Zambia",
    "dial_code": "+260",
    "code": "ZM"
  }, {
    "name": "Zimbabwe",
    "dial_code": "+263",
    "code": "ZW"
  }]', true);
}

function phonenumber_format($number = false)
{
    if ($number) {
        $formatted_number = phonenumber_valid($number);
        if ($formatted_number) {
            if ('27' == substr($formatted_number, 0, 2)) {
                $formatted_number = substr($formatted_number, 2);
            }
            if ('0' == substr($formatted_number, 0, 1)) {
                $formatted_number = substr($formatted_number, 1);
            }
            if (11 == strlen($formatted_number)) {
                return $formatted_number;
            } elseif (9 == strlen($formatted_number)) {
                return '27'.$formatted_number;
            }
        }
    }

    return false;
}

function phonenumber_valid($number = false)
{
    if ($number) {
        $number = str_replace([' ', '+', '(', ')', '-', '.', ',', '?'], '', $number);
        if (is_numeric($number) && (intval($number) == $number) && strlen($number) >= 9 && intval($number) > 0) {
            return za_number_numerics($number);
        }
    }

    return null;
}

function phonenumber_numerics($str)
{
    preg_match_all('/\d+/', $str, $matches);

    return implode($matches[0]);
}

function isTimestamp($string)
{
    try {
        // \DateTime::createFromFormat('D M d Y H:i:s e+', $string);
        new DateTime('@'.$string);
    } catch (Exception $e) {
        return false;
    }

    return true;
}

function isDateTime($string)
{
    if (strtotime($string)) {
        return true;
    } else {
        return false;
    }
}

function get_installed_app_ids()
{
    if (is_main_instance()) {
        $conn = 'default';
    } else {
        $conn = 'system';
    }

    return \DB::connection($conn)->table('erp_instance_apps')->where('instance_id', session('instance')->id)->pluck('app_id')->toArray();
}

function get_app_ids($instance_id)
{
    return \DB::connection('system')->table('erp_instance_apps')->where('instance_id', $instance_id)->pluck('app_id')->toArray();
}

function round_up_to_nearest_n($int, $n)
{
    return intval(ceil($int / $n) * $n);
}
function round_up_to_nearest_n_float($int, $n)
{
    return floatval(ceil($int / $n) * $n);
}

function str_replace_json($search, $replace, $subject)
{
    return json_decode(str_replace($search, $replace, json_encode($subject)));
}

// convert returned usage data to human readable sizes
function human_filesize($bytes, $decimals = 2, $type = null)
{
    $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    if (!$type) {
        $factor = floor((strlen($bytes) - 1) / 3);
    } else {
        $factor = array_search($type, $size);
    }

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
}

function session_test()
{
    return (3696 == session('user_id') || 1 == session('user_id')) ? 1 : 0;
}

function is_superadmin()
{
    if (is_dev()) {
        return 1;
    }

    return app('erp_config')['roles']->where('id', session('role_id'))->pluck('super_admin')->first();
}

function is_manager()
{
    return app('erp_config')['roles']->where('id', session('role_id'))->pluck('manager')->first();
}
function is_dev_admin()
{
    // return false;
    if (empty(session('user_id')) || empty(session('instance'))) {
        return false;
    }
    if (session('user_id') == 1) {
        return true;
    }

    if (session('username') == 'ahmed@telecloud.co.za') {
        return true;
    }

    return false;
}

function is_dev()
{
    // return false;
    if (empty(session('user_id')) || empty(session('instance'))) {
        return false;
    }
    if (session('user_id') == 1 or session('user_id') == 5704) {
        return true;
    }

    if (session('username') == 'ahmed@telecloud.co.za') {
        return true;
    }

    return false;
}

function is_test()
{
    if (session('user_id') == 5132) {
        return true;
    }

    return false;
}

function gg_time($var, $reset = false)
{
    try {
        if (!is_array($var) && !is_string($var)) {
            $var = print_r($var, true);
        }
        $session_filter = false;
        $log_file = 'debug';
        if (empty(session('time_track')) || $reset) {
            session(['time_track' => microtime(true)]);
            $duration_log = '';
        } else {
            $time_start = session('time_track');
            session()->forget('time_track');
            $time_end = microtime(true);
            $duration = $time_end - $time_start;
            $duration_log = 'Duration: '.$duration;
        }

        // aa($var);
        // aa($duration_log);
    } catch (\Throwable $ex) {
        exception_log($ex);
    }
}

function put_permanent_env($key, $value, $path = false)
{
    if (!$path) {
        $path = app()->environmentFilePath();
    }

    $escaped = preg_quote('='.env($key), '/');

    file_put_contents($path, preg_replace(
        "/^{$key}{$escaped}/m",
        "{$key}={$value}",
        file_get_contents($path)
    ));
}

function setEnv($name, $value)
{
    $path = base_path('.env');
    if (file_exists($path)) {
        file_put_contents($path, str_replace(
            $name.'='.env($name), $name.'='.$value, file_get_contents($path)
        ));
    }
}

function is_main_instance()
{
    if (!empty(session('instance')) && session('instance')->id == 1) {
        return 1;
    }

    return 0;
}

function obj($array)
{
    return json_decode(json_encode($array));
}

function move_element_in_array(&$array, $a, $b)
{
    $p1 = array_splice($array, $a, 1);
    $p2 = array_splice($array, 0, $b);
    $array = array_merge($p2, $p1, $array);
}

function insert_element_in_array($array, $index, $val)
{
    $size = count($array); //because I am going to use this more than one time
    if (!is_int($index) || $index < 0 || $index > $size) {
        return -1;
    } else {
        $temp = array_slice($array, 0, $index);
        $temp[] = $val;

        return array_merge($temp, array_slice($array, $index, $size));
    }
}

function roundnum($num)
{
    if ($num > 40 and $num <= 300) {
        return round($num / 5) * 5;
    } elseif ($num > 300 and $num <= 1000) {
        return round($num / 50) * 50;
    } elseif ($num > 1000 and $num <= 10000) {
        return round($num / 100) * 100;
    } else {
        return $num;
    }
}

function success($msg)
{
    return Redirect::to('/')
        ->with('messagetext', $msg)->with('msgstatus', 'success');
}

function br2nl($input)
{
    return preg_replace('/<br\s?\/?>/ius', "\n", str_replace("\n", '', str_replace("\r", '', htmlspecialchars_decode($input))));
}

function random($length = 6)
{
    $char = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $char = str_shuffle($char);
    for ($i = 0, $rand = '', $l = strlen($char) - 1; $i < $length; ++$i) {
        $rand .= $char[mt_rand(0, $l)];
    }

    return $rand;
}

function formatNumber($number, $currency = 'ZAR')
{
    if ('USD' == $currency) {
        return number_format($number, 2, '.', ',');
    }

    return number_format($number, 0, '.', '.');
}

function parent_of($account_id, $partner_id = null)
{
    if (!$partner_id) {
        $partner_id = session('account_id');
    }

    return \DB::connection('default')->table('crm_accounts')->where('partner_id', $partner_id)->where('id', $account_id)->count();
}

function is_parent_of($account_id)
{
    $user_account_id = session('original_account_id');
    $parent_account_id = dbgetaccountcell($account_id, 'partner_id');

    if (1 == $user_account_id || $user_account_id == $parent_account_id) {
        return true;
    } else {
        return false;
    }
}

function is_child_of($parent_id = 0)
{
    if (0 == $parent_id) {
        $parent_id = session('parent_id');
    }
    $current_account_id = session('account_id');
    $child_customers = dbgetrows('crm_accounts', 'partner_id', $parent_id);
    if ($child_customers) {
        foreach ($child_customers as $child_customer) {
            if ($child_customer->id == $current_account_id) {
                return true;
            }
        }
    }

    return false;
}

function level($account_id)
{
    $level_id = dbgetcell('erp_users', 'account_id', $account_id, 'group_id');
    if (1 == $level_id || 2 == $level_id || 3 == $level_id) {
        return 'Admin';
    } elseif (11 == $level_id) {
        return 'Partner';
    } elseif (21 == $level_id) {
        return 'Customer';
    }
}

function is_original_user_internal()
{
    $group_id = \DB::select('select group_id from crm_accounts where id ='.session('original_id'));
    if (isset($group_id[0]) && $group_id[0]->group_id < 10) {
        return true;
    } else {
        return false;
    }
}

function get_descendants()
{
    $descendants[] = session('account_id');
    $children = \DB::select('select id from crm_accounts where account_id ='.session('account_id').' and id > 1');
    foreach ($children as $child) {
        $descendants[] = $child->id;
        $grand_children = \DB::select('select id from crm_accounts where account_id ='.$child->id);
        foreach ($grand_children as $grandchild) {
            $descendants[] = $grandchild->id;
            $great_grand_children = \DB::select('select id from crm_accounts where account_id ='.$grandchild->id);
            foreach ($great_grand_children as $great_grand_child) {
                $descendants[] = $great_grand_child->id;
            }
        }
    }

    return $descendants;
}

function get_descendant_groups()
{
    if (session('gid') < 5) {
        $descendants = [1, 2, 3, 4, 10, 11, 12];
    } elseif (10 == session('gid')) {
        $descendants = [11, 12];
    } elseif (11 == session('gid')) {
        $descendants = [12];
    } else {
        $descendants = 0;
    }

    return $descendants;
}

function currency_formatted($amount, $currency = 'ZAR')
{
    $symbol = '';
    if ($currency == 'ZAR') {
        $symbol = 'R';
    }
    if ($currency == 'USD') {
        $symbol = '$';
    }

    $amount = str_replace(',', '', $amount);

    return $symbol.' '.number_format((float) $amount, 2, '.', '');
}

function currency($amount, $decimals = false)
{
    if (empty($decimals)) {
        $decimals = 2;
    }

    $amount = str_replace(',', '', $amount);

    return number_format((float) $amount, $decimals, '.', '');
}

function currency_usd($amount)
{
    $amount = str_replace(',', '', $amount);

    return number_format((float) $amount, 3, '.', '');
}

function array2json($arr)
{
    if (function_exists('json_encode')) {
        return json_encode($arr);
    } //Lastest versions of PHP already has this functionality.
    $parts = [];
    $is_view = false;

    //Find out if the given array is a numerical array
    $keys = array_keys($arr);
    $max_length = count($arr) - 1;
    if ((0 == $keys[0]) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1
        $is_view = true;
        for ($i = 0; $i < count($keys); ++$i) { //See if each key correspondes to its position
            if ($i != $keys[$i]) { //A key fails at position check.
                $is_view = false; //It is an associative array.
                break;
            }
        }
    }

    foreach ($arr as $key => $value) {
        if (is_array($value)) { //Custom handling for arrays
            if ($is_view) {
                $parts[] = array2json($value);
            } /* :RECURSION: */
            else {
                $parts[] = '"'.$key.'":'.array2json($value);
            } /* :RECURSION: */
        } else {
            $str = '';
            if (!$is_view) {
                $str = '"'.$key.'":';
            }

            //Custom handling for multiple data types
            if (is_numeric($value)) {
                $str .= $value;
            } //Numbers
            elseif (false === $value) {
                $str .= 'false';
            } //The booleans
            elseif (true === $value) {
                $str .= 'true';
            } else {
                $str .= '"'.addslashes($value).'"';
            } //All other things
            // :TODO: Is there any more datatype we should be in the lookout for? (Object?)

            $parts[] = $str;
        }
    }
    $json = implode(',', $parts);

    if ($is_view) {
        return '['.$json.']';
    } //Return numerical JSON

    return '{'.$json.'}'; //Return associative JSON
}

function stripNonNumeric($string)
{
    return preg_replace('/[^0-9]/', '', $string);
}

function gen_uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function input_field($label, $id, $value = '', $required = 0, $type = 'text', $columns = 'one', $placeholder_text = '', $help_text = '', $dependancy1 = '', $dependancy2 = '', $icon = false)
{
    $dynamic_field = ($dependancy1 || $dependancy2) ? 'dynamic-field' : '';
    $dependancy1 = ($dependancy1) ? 'data-df-target-1="'.$dependancy1.'"' : '';
    $dependancy2 = ($dependancy1) ? 'data-df-target-2="'.$dependancy2.'"' : '';

    $placeholder = ($placeholder_text) ? 'placeholder="'.$placeholder_text.'"' : '';
    $required = ($required) ? 'required' : '';
    $date_picker = '';

    if ('date' == $type) {
        $date_picker = 'date-picker';
        $type = 'text';
    }
    if ('number' == $type) {
        $type = 'float';
    }

    if ('two' == $columns) {
        echo '<div class="col-md-4">';
    }

    echo '<div class="form-group row" id="'.$id.'_div">';
    echo '<label class="control-label col-md-3">'.$label;
    if ($icon) {
        info_icon($label, $icon);
    }
    echo '</label>';
    echo '<div class="col-md-4">';

    if ('switch' == $type) {
        $checked = ($value) ? 'checked' : '';
        echo '<input type="checkbox" class="make-switch '.$dynamic_field.'" data-size="small" name='.$id.' id='.$id.' '.$checked.' '.$dependancy1.' '.$dependancy2.'>';
    } elseif ('textarea' == $type) {
        echo '<textarea class="form-control" '.$placeholder.' name="'.$id.'" id="'.$id.'" rows="10" '.$required.'>'.$value.'</textarea>';
    } else {
        if ($help_text) {
            echo '<div class="input-append input-group" style="margin-left:35px;width:50%"><input type="'.$type.'"  style="width:200px; " class="form-control'.$dynamic_field.' '.$date_picker.'"';
            echo $dependancy1.' '.$dependancy2.' '.$placeholder.' name="'.$id.'" id="'.$id.'" value="'.$value.'" '.$required.'>
            <label style="font-size:16px;background-color:lightgrey;" class="input-group-addon">'.$help_text.'</label></div>';
        } else {
            echo '<input type="'.$type.'" class="form-control '.$dynamic_field.' '.$date_picker.'"';
            echo $dependancy1.' '.$dependancy2.' '.$placeholder.' name="'.$id.'" id="'.$id.'" value="'.$value.'" '.$required.'>';
        }
    }

    echo '</div>';
    echo '</div>';

    if ('two' == $columns) {
        echo '</div>';
    }
}

function one_column_switch($title, $id, $value, $dependancy1 = '', $dependancy2 = '')
{
    input_field($title, $id, $value, 'switch', 'one', $dependancy1, $dependancy2);
}

function one_column_submit($name = 'submit')
{
    $id = 'btn_'.$name;
    echo "
    <div class='form-group row' style='margin-top:20px;'>
        <div class='col-md-4 col-md-offset-3'>
            <button value=$id class='form-control col-md-6 btn btn-info btnsubmit' style='color:black; min-width:200px' type='submit' name=".$id.' id='.$id.' />
                  '.ucwords(str_replace('_', ' ', $name)).'
              </button>
          </div>
    </div>';
}

function box_heading($title = '', $desc = '')
{
    echo "
    <div class='col-md-4 well' style='min-height:250px'>
        <fieldset>
                <legend class='text-left'>".ucwords(str_replace('_', ' ', $title))."<h5>$desc</h5></legend>";
}

function box_heading_close()
{
    echo '</fieldset></div>';
}

function one_column_hidden($field, $value)
{
    echo "<input type='hidden' id='".$field."' name='".$field."' value='".$value."' >";
}

function one_column_number_input($title, $id, $value, $required = 0, $placeholder = '')
{
    input_field($title, $id, $value, $required, 'number', 'one', $placeholder);
}
function form_open($action)
{
    echo "<form method='post' action='".$action."' name='form1' id='form1' class='form-horizontal form-row-seperated'>";
}

function form_close()
{
    echo '</form>';
}

function one_column_input($title, $id, $value = '', $required = 0, $placeholder = '', $aftertext = '', $dependancy1 = '', $dependancy2 = '')
{
    input_field($title, $id, $value, $required, 'text', 'one', $placeholder, $aftertext, $dependancy1, $dependancy2);
}

function generate_password()
{
    return rand(1, 999).rand(1, 999);
}

function getModule()
{
    $currentAction = \Route::currentRouteAction();
    list($controller, $method) = explode('@', $currentAction);
    // $controller now is "App\Http\Controllers\FooBarController"

    $controller = preg_replace('/.*\\\/', '', $controller);
    $module = str_replace('Controller', '', $controller);

    return $module;
}

function getController()
{
    $currentAction = \Route::currentRouteAction();
    list($controller, $method) = explode('@', $currentAction);
    $controller = preg_replace('/.*\\\/', '', $controller);

    return $controller;
}

function getMethod()
{
    $currentAction = \Route::currentRouteAction();
    list($controller, $method) = explode('@', $currentAction);

    return $method;
}

function generate_strong_password($length = 6, $add_dashes = false, $available_sets = 'lud')
{
    $sets = [];
    if (false !== strpos($available_sets, 'l')) {
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
    }
    if (false !== strpos($available_sets, 'u')) {
        $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    }
    if (false !== strpos($available_sets, 'd')) {
        $sets[] = '23456789';
    }
    if (false !== strpos($available_sets, 's')) {
        $sets[] = '!@#$%&*?';
    }
    $all = '';
    $password = '';
    foreach ($sets as $set) {
        $password .= $set[array_rand(str_split($set))];
        $all .= $set;
    }
    $all = str_split($all);
    for ($i = 0; $i < $length - count($sets); ++$i) {
        $password .= $all[array_rand($all)];
    }
    $password = str_shuffle($password);
    if (!$add_dashes) {
        return $password;
    }
    $dash_len = floor(sqrt($length));
    $dash_str = '';
    while (strlen($password) > $dash_len) {
        $dash_str .= substr($password, 0, $dash_len).'-';
        $password = substr($password, $dash_len);
    }
    $dash_str .= $password;

    return $dash_str;
}

function curlPost($url, $params = null, $post = true, $headers = null)
{
    $postData = '';

    //foreach($params as $k => $v)
    //{
    //$postData .= $k . '='.$v.'&';
    //}
    //rtrim($postData, '&');

    $ch = curl_init($url); // your URL to send array data
    //x($params);
    //DEBUG
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
    //$errorlog = fopen('/var/www/html/applications/menu/logs/errorlog.txt', 'w');
    //curl_setopt($ch, CURLOPT_STDERR, $errorlog);
    //echo ($url."<br>");
    //echo ($postData."<br>");

    if ($headers != null) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (null != $params) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } // Your array field
    curl_setopt($ch, CURLOPT_POST, $post);

    $result = curl_exec($ch);

    if (false === $result) {
        printf(
            "cUrl error (#%d): %s<br>\n",
            curl_errno($handle),
            htmlspecialchars(curl_error($handle))
        );
    }

    curl_close($ch);

    return $result;
}

function roundUpToAny($n, $x = 5)
{
    return round(($n + $x / 2) / $x) * $x;
}

function seo_string($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function slug_string($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

    return strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $string)); // Removes special chars.
}

function string_clean($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function file_name_formatted($filename)
{
    $filename_arr = explode('.', $filename);
    $file_format = end($filename_arr);

    $string = implode('.', $filename_arr);
    $string = str_replace('.'.$file_format, '', $string);
    $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string).'.'.$file_format; // Removes special chars.
}

function clean($string)
{
    $string = str_replace(' ', '-', trim($string)); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function function_format($string)
{
    $string = str_replace(' ', '_', trim(strtolower($string))); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\_]/', '', $string); // Removes special chars.
}

function rmspaces($str)
{
    return preg_replace('/\s+/', '', $str);
}

function notify_message($msg, $status, $redirect = '')
{
    if (empty($redirect)) {
        Redirect::back()->with('messagetext', $msg)->with('msgstatus', $status)->send();
    } else {
        Redirect::to($redirect)->with('messagetext', $msg)->with('msgstatus', $status)->send();
    }
}

function api_success($msg, $extra = [])
{
    $json = ['message' => $msg];
    $json['status'] = 'SUCCESS';
    if (!empty($extra)) {
        $json = array_merge($json, $extra);
    }
    header('Access-Control-Allow-Origin: *');

    return response()->json($json);
}

function api_error($msg, $extra = [])
{
    $json = ['message' => $msg];
    $json['status'] = 'FAILURE';
    if (!empty($extra)) {
        $json = array_merge($json, $extra);
    }
    header('Access-Control-Allow-Origin: *');

    return response()->json($json, 400);
}

function api_abort($msg, $extra = [])
{
    $json = ['message' => $msg];
    $json['status'] = 'FAILURE';
    if (!empty($extra)) {
        $json = array_merge($json, $extra);
    }
    header('Access-Control-Allow-Origin: *');

    return response()->json($json, 500);
}

function json_alert($msg, $status = 'success', $extra = [])
{
    $json = ['status' => $status, 'message' => $msg];
    if (!empty($extra)) {
        $json = array_merge($json, $extra);
    }

    if (!headers_sent()) {
        header('Access-Control-Allow-Origin: *');
    }

    return response()->json($json);
}

function cors_json_alert($msg, $status = 'success', $extra = [])
{
    $json = ['status' => $status, 'message' => $msg];
    if (!empty($extra)) {
        $json = array_merge($json, $extra);
    }
    //header("Access-Control-Allow-Origin: *");

    return response()->json($json);
}

function json_msg($msg, $status = 'success')
{
    echo json_encode((object) ['status' => $status, 'message' => $msg]);
}

function format_button_text($button_title = '')
{
    if (!empty($button_title)) {
        $button_title = ltrim(preg_replace('/[A-Z]/', ' $0', $button_title));
    }

    return $button_title;
}

function nltextformat($var)
{
    $var = preg_replace('/\v+|\\\r\\\n/', '<br/>', $var);

    return $var;
}

function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k.' '.$v.($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }

    return $string ? implode(', ', $string).' ago' : 'just now';
}

function delTree($dir)
{
    if (file_exists($dir)) {
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }
    }
}

function array_search_partial($arr, $keyword)
{
    foreach ($arr as $index => $string) {
        if (false !== strpos($string, $keyword)) {
            return $index;
        }
    }

    return false;
}

function png2jpg($originalFile, $outputFile, $quality = 100)
{
    $image = imagecreatefrompng($originalFile);
    imagejpeg($image, $outputFile, $quality);
    imagedestroy($image);
}

function is_alpha_png($fn)
{
    return 6 == ord(@file_get_contents($fn, null, null, 25, 1));
}

function multiply($qty, $val)
{
    return currency($qty * $val);
}

function isTodayWeekend()
{
    return 'Saturday' == date('l') or 'Sunday' == date('l');
}

function array_insert(&$array, $position, $insert)
{
    if (is_int($position)) {
        array_splice($array, $position, 0, $insert);
    } else {
        $pos = array_search($position, array_keys($array));
        $array = array_merge(
            array_slice($array, 0, $pos),
            $insert,
            array_slice($array, $pos)
        );
    }
}

function module_url($module_id)
{
    return url(get_menu_url($module_id));
}

function mypricelist_url()
{
    $account = dbgetaccount(session('account_id'));
    if ($account->type == 'reseller') {
        if (!empty($account->pricelist_id)) {
            return module_url(507);
        } else {
            return module_url(582);
        }
    } else {
        return module_url(507);
    }
}

function multi_array_key_search($array, $keySearch, $value)
{
    foreach ($array as $key => $item) {
        if (str_contains($key, $keySearch) && $item == $value) {
            return true;
        } elseif (is_array($item) && multi_array_key_search($item, $keySearch, $value)) {
            return true;
        }
    }

    return false;
}

function schedule_cron_test_check()
{
}

function get_partner_logo($partner_id = false)
{
    if (!$partner_id) {
        return '';
    }
    $partner_settings = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', $partner_id)->get()->first();

    $settings_path = uploads_settings_path();

    if (!empty($partner_settings->logo) && file_exists($settings_path.$partner_settings->logo)) {
        return $partner_settings->logo;
    } else {
        return '';
    }
}

function menu_access($menu_id, $access = 'is_view')
{
    $access_groups = \DB::connection('default')->table('erp_menu_role_access')->where('menu_id', $menu_id)->where($access, 1)->pluck('role_id')->toArray();
    if (empty($access_groups) || (is_array($access_groups) && count($access_groups) == 0)) {
        return false;
    }

    $user_groups = session('role_id');

    if (in_array($user_group, $access_groups)) {
        return true;
    }

    return false;
}

function get_whitelabel_domain($partner_id)
{
    if ($partner_id) {
        $whitelabel_domain = session('instance')->domain_name;
    } else {
        $whitelabel_domain = session('instance')->alias;
        //$whitelabel_domain = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', $partner_id)->pluck('whitelabel_domain')->first();
        //if (empty($whitelabel_domain)) {
        //    $whitelabel_domain = session('instance')->alias;
        //}
    }
    if (empty($whitelabel_domain)) {
        $whitelabel_domain = 'https://'.session('instance')->alias;
    } else {
        $whitelabel_domain = 'https://'.$whitelabel_domain;
    }

    return url($whitelabel_domain);
}

function is_weekend()
{
    $weekend = (date('N') >= 6);
    if ($weekend) {
        return true;
    }

    return false;
}

function is_working_hours()
{
    $weekend = is_weekend();
    if (!$weekend) {
        $time_start = date('Y-m-d 08:00');

        $time_end = date('Y-m-d 17:00');
        $now = date('Y-m-d H:i');

        if ($time_start <= $now && $now <= $time_end && ($now < date('Y-m-d 12:30') || $now > date('Y-m-d 13:30'))) {
            return true;
        }
    }

    return false;
}

function get_builder_menu_id($module_id, $menu_id)
{
    $query_sting = request()->getQueryString();
    if (!empty($query_sting)) {
        $menu_id = \DB::connection('default')->table('erp_menu')->where('menu_type', 'module_filter')->where('module_id', $module_id)->where('url', 'LIKE', '%'.$query_string.'%')->pluck('id')->first();
    }

    return $menu_id;
}

function escape_str($string)
{
    return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function get_admin_instance_access_session()
{
    //return get_admin_instance_access();
    if (session()->has('admin_instance_ids')) {
        $instance_ids = session('admin_instance_ids');

        if (count($instance_ids) == 0) {
            $instance_ids = get_admin_instance_access();
            session(['admin_instance_ids' => $instance_ids]);
        }

        return $instance_ids;
    } else {
        $instance_ids = get_admin_instance_access();
        session(['admin_instance_ids' => $instance_ids]);

        return $instance_ids;
    }
}

function get_admin_instance_access($username = false)
{
    if (!$username) {
        $username = session('username');
    }

    $admin_user_id = \DB::connection('system')->table('erp_users')->where('username', $username)->pluck('id')->first();

    $instance_ids = \DB::connection('system')->table('erp_instance_user_access')->where('user_id', $admin_user_id)->pluck('instance_id')->toArray();
    /*
    $instance_ids = [];
    $instances = \DB::connection('system')->table('erp_instances')->where('installed',1)->get();
    foreach($instances as $instance){
        $exists = \DB::connection($instance->db_connection)->table('erp_users')->where('username',$username)->count();
        if($exists){
            $instance_ids[] = $instance->id;
        }
    }
    */
    return $instance_ids;
}

function get_instances_list()
{
    $list = [];
    $erp_instances = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('installed', 1)->orderBy('sort_order')->get();
    foreach ($erp_instances as $erp_instance) {
        $logo_link = '';
        $logo = \DB::connection($erp_instance->db_connection)->table('crm_account_partner_settings')->where('account_id', 1)->pluck('logo')->first();
        $dir = get_menu_url_from_table('crm_account_partner_settings');
        $path = public_path().'/uploads/'.$erp_instance->db_connection.'/'.$dir.'/';

        if (!empty($logo) && file_exists($path.$logo)) {
            $logo_link = $path.$logo;
        }

        if ($logo_link) {
            $link = str_replace(public_path(), '', $path.$logo);
            $erp_instance->logo = 'https://'.$erp_instance->domain_name.'/'.$link;
        }
        $erp_instance->login_url = 'https://'.$erp_instance->domain_name.'/user/admin_login?user_id='.session('user_id');
        $list[] = $erp_instance;
    }

    return $list;
}

function get_days_select()
{
    $fields = range(1, 31);

    $result = array_combine($fields, $fields);

    return $result;
}

function swap_var(&$x, &$y)
{
    $tmp = $x;
    $x = $y;
    $y = $tmp;
}

function secondsToTime($seconds)
{
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    $a = $dtF->diff($dtT)->format('%a');
    $h = $dtF->diff($dtT)->format('%h');
    $i = $dtF->diff($dtT)->format('%i');
    $s = $dtF->diff($dtT)->format('%s');
    if ($a > 0) {
        return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
    } elseif ($h > 0) {
        return $dtF->diff($dtT)->format('%h hours, %i minutes and %s seconds');
    } elseif ($i > 0) {
        return $dtF->diff($dtT)->format(' %i minutes and %s seconds');
    } else {
        return $dtF->diff($dtT)->format('%s seconds');
    }
}

function rentals_enabled()
{
    return \DB::connection('default')->table('erp_cruds')->where('db_table', 'crm_rental_leases')->count();
}

function roundToDigits($num, $suffix, $type = 'round')
{
    $pow = pow(10, floor(log($suffix, 10) + 1));

    return $type(($num - $suffix) / $pow) * $pow + $suffix;
}

function weekOfMonth($dateString)
{
    list($year, $month, $mday) = explode('-', $dateString);
    $firstWday = date('w', strtotime("$year-$month-1"));

    return (int) floor(($mday + $firstWday - 1) / 7) + 1;
}
if (!function_exists('escape')) {
    function escape($string)
    {
        return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        //return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
