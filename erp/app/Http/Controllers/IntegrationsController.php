<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Rap2hpoutre\FastExcel\FastExcel;
use Redirect;

class IntegrationsController extends BaseController
{
    public function __construct()
    {
        $this->middleware('cors');
    }

    public function whatsappWebhook(Request $request)
    {
        $VERIFY_TOKEN = 'cloudtelecoms';

        // Handle the verification request
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode']) && isset($_GET['hub_verify_token'])) {
            if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === $VERIFY_TOKEN) {
                echo $_GET['hub_challenge'];
                http_response_code(200);
            } else {
                http_response_code(403);
            }
            exit;
        }

        // Handle incoming messages
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            http_response_code(200);
            exit;
        }

        http_response_code(400);
    }

    public function zapierZendesk(Request $request)
    {
        $assignee_email = $request->assignee__email;
        $created_at = $request->ticket__created_at;
        $zendesk_id = $request->ticket__id;
        $subject = $request->subject;
        $user_id = \DB::table('erp_users')->where('email', $assignee_email)->where('account_id', 1)->pluck('id')->first();

        $data = [
            'user_id' => $user_id,
            'subject' => $subject,
            'zendesk_id' => $zendesk_id,
            'created_at' => date('Y-m-d H:i:s', strtotime($created_at)),
        ];
        if (in_array($request->ticket__status, ['solved', 'closed']) || in_array($request->status, ['solved', 'closed'])) {
            $data['completed'] = 1;
        }
        \DB::table('crm_tickets')->updateOrInsert(['zendesk_id' => $zendesk_id], $data);
    }

    public function zapierFacebookComments(Request $request) {}

    public function respondInbox(Request $request)
    {
        //85BQ3CbDHcBX5LssiKdsLK3TM/nl5uf3XbEChi54jaw=
        $post_data = $request->all();
        if (! empty($post_data['message']['message']['subject']) && str_contains($post_data['message']['message']['subject'], 'Altaro')) {
            $vm_backup_errors = '';
            $subject = $post_data['message']['message']['subject'];
            $success = (! str_contains($subject, 'Failed')) ? 1 : 0;
            if (! $success) {
                $vm_backup_errors .= $subject.PHP_EOL;
            }
            system_log('backup', 'Altaro VM Backup', $subject, 'vm', 'daily', $success);

            if ($vm_backup_errors > '') {
                admin_email($vm_backup_errors);
            }
        }
    }

    public function respondWebhooks(Request $request)
    {
        try {
            $post_data = (object) $request->all();
            $exists = \DB::table('crm_accounts')->where('company', $post_data->name)->where('phone', $post_data->phone)->where('email', $post_data->email)->count();
            if (! $exists) {
                $marketing_lead = [
                    'type' => 'lead',
                    'contact' => $post_data->name,
                    'company' => $post_data->name,
                    'status' => 'Enabled',
                    'pricelist_id' => 1,
                    'partner_id' => 1,
                    'email' => $post_data->email,

                    'phone' => $post_data->phone,
                    'source' => 'Respond Lead Form',
                    'external_id' => $request->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => get_system_user_id(),
                ];
                foreach ($marketing_lead as $k => $v) {
                    if (empty($v)) {
                        $marketing_lead[$k] = '';
                    }
                }
                $account_id = \DB::table('crm_accounts')->insertGetId($marketing_lead);
                $note = $post_data->notes;
                if (! empty($note)) {
                    dbinsert('erp_module_notes', ['note' => $note, 'row_id' => $account_id, 'module_id' => 343]);
                }
                schedule_assign_customers_to_salesman();
                $json = ['status' => 'success'];

                return response()->json($json, 200);
            } else {
                $json = ['status' => 'failed'];

                return response()->json($json, 500);
            }
        } catch (\Throwable $ex) {
            $json = ['status' => 'failed'];

            return response()->json($json, 500);
        }
    }

    public function zapierInstagram(Request $request)
    {
        $post_data = [];
        foreach ($request->all() as $k => $v) {
            $k = strtolower($k);
            $post_data[$k] = $v;
        }
        $source = $request->header('leadsource');

        if ($source == 'instagram') {
            $exists = \DB::table('crm_accounts')->where('external_id', $request->id)->count();
            if (! $exists) {
                $marketing_lead = [
                    'type' => 'lead',
                    'contact' => $post_data['full_name'],
                    'company' => $post_data['full_name'],
                    'status' => 'Enabled',
                    'pricelist_id' => 1,
                    'partner_id' => 1,
                    'email' => $post_data['email'],
                    'phone' => $post_data['phone'],
                    'source' => 'Instagram',
                    'external_id' => $request->id,
                    'form_name' => $post_data['form_name'],
                    'form_id' => $post_data['form_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => get_system_user_id(),
                ];
                $account_currency = \DB::table('crm_ad_campaigns')->where('form_id', $post_data['form_id'])->pluck('account_currency')->first();
                if ($account_currency == 'USD') {
                    $marketing_lead['currency'] = 'USD';
                    $marketing_lead['pricelist_id'] = 2;
                } else {
                    $marketing_lead['currency'] = 'ZAR';
                    $marketing_lead['pricelist_id'] = 1;
                }
                $marketing_channel_id = \DB::table('crm_ad_channels')->where('name', 'like', '%instagram%')->pluck('id')->first();

                if ($marketing_channel_id) {
                    $data['marketing_channel_id'] = $marketing_channel_id;
                }

                $id = \DB::table('crm_accounts')->insertGetId($marketing_lead);

                send_email_verification_link($id);
                schedule_assign_customers_to_salesman();
            }
        }
    }

    public function zapierWebhooks(Request $request)
    {
        $post_data = [];
        foreach ($request->all() as $k => $v) {
            $k = strtolower($k);
            $post_data[$k] = $v;
        }
        $source = $request->header('leadsource');

        if ($source == 'facebook') {
            $exists = \DB::table('crm_accounts')->where('external_id', $request->id)->count();
            $company = $post_data['full_name'];
            if (! empty($post_data['company_name'])) {
                $company = $post_data['company_name'];
            }

            if (! $exists) {
                $marketing_lead = [
                    'type' => 'lead',
                    'contact' => $post_data['full_name'],
                    'company' => $company,
                    'status' => 'Enabled',
                    'pricelist_id' => 1,
                    'partner_id' => 1,
                    'email' => $post_data['email'],
                    'phone' => $post_data['phone'],
                    'source' => 'Facebook',
                    'external_id' => $request->id,
                    'form_name' => $post_data['form_name'],
                    'form_id' => $post_data['form_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => get_system_user_id(),
                ];
                $account_currency = \DB::table('crm_ad_campaigns')->where('form_id', $post_data['form_id'])->pluck('account_currency')->first();
                if ($account_currency == 'USD') {
                    $marketing_lead['currency'] = 'USD';
                    $marketing_lead['pricelist_id'] = 2;
                } else {
                    $marketing_lead['currency'] = 'ZAR';
                    $marketing_lead['pricelist_id'] = 1;
                }
                // $marketing_channel_id = \DB::table('crm_ad_channels')->where('name','like', '%facebook%')->pluck('id')->first();

                // if($marketing_channel_id){
                //     $marketing_lead['marketing_channel_id'] = $marketing_channel_id;
                // }

                $id = \DB::table('crm_accounts')->insertGetId($marketing_lead);

                send_email_verification_link($id);
                schedule_assign_customers_to_salesman();
            }
        }
    }

    public function pabblyWebhooks(Request $request) {}

    public function pbxTextToSpeech(Request $request)
    {
        // TTS Script google cloud text to speech
        // /home/_admin/tts/tts.php

        if (empty($request->recording_name) || empty($request->recording_tts) || empty($request->domain_uuid) || empty($request->token)) {
            throw new \ErrorException('Post data invalid');
        }
        $token = \Erp::decode($request->token);
        if ($token != 'cloudpbx') {
            throw new \ErrorException('Token invalid');
        }

        $text = $request->recording_tts;
        $title = $request->recording_name;
        $file_title = str_replace(' ', '_', $request->recording_name).date('Y_m_d_H_i').'.wav';
        $tts_dir = '/home/_admin/tts';
        $tts_output_dir = '/home/_admin/tts/files/';

        $cmd = 'cd '.$tts_dir.' && php tts.php "'.$text.'" '.$tts_output_dir.$file_title.' "" "" wav';
        $result = \Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        if (! str_contains($result, 'Audio content written')) {
            throw new \ErrorException('Audio file could not be generated');
        }

        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $request->domain_uuid)->pluck('domain_name')->first();

        $file_path = $tts_output_dir.$file_title;
        if (file_exists($file_path)) {
            // create db entry
            $data = [
                'recording_uuid' => pbx_uuid('v_recordings', 'recording_uuid'),
                'domain_uuid' => $request->domain_uuid,
                'recording_filename' => $file_title,
                'recording_name' => $request->recording_name,
                'recording_description' => $request->recording_tts,
            ];
            \DB::connection('pbx')->table('v_recordings')->insert($data);
            $ssh = new \phpseclib\Net\SSH2('pbx.cloudtools.co.za');
            if ($ssh->login('root', 'Ahmed777')) {
                $scp = new \phpseclib\Net\SCP($ssh);
                $remote = '/var/lib/freeswitch/recordings/'.$domain_name.'/'.$file_title;
                $result = $scp->put($remote, $file_path, $scp->SOURCE_LOCAL_FILE);
                if ($result) {
                    $cmd = 'chown freeswitch:daemon '.$remote.' && chmod 777 '.$remote;
                    $permissions_result = \Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
                }
            }
        }
    }

    public function getFunctionCode(Request $request, $function_name)
    {
        $data['function_name'] = $function_name;
        $data['function_code'] = get_function_code($function_name);

        return view('__app.button_views._code_edit', $data);
    }

    public function postFunctionCode(Request $request)
    {
        if (empty($request->function_name) || empty($request->function_code)) {
            return json_alert('Function name and code required', 'warning');
        }

        if (substr_count($request->function_code, 'function ') > 1 && ! str_contains($request->function_code, 'where(function')) {
            return json_alert('Cannot declare more than one function', 'warning');
        }
        if (substr_count($request->function_code, 'function ') != 1 && ! str_contains($request->function_code, 'where(function')) {
            return json_alert('Function declaration invalid', 'warning');
        }
        $result = set_function_code($request->function_name, $request->function_code);

        if (! $result) {
            return json_alert('Code contains syntax errors.', 'error');
        }

        return json_alert('Code saved.');
    }

    public function contactFormNetstream()
    {
        /*  'formAction' => 'd41d8cd98f00b204e9800998ecf8427e',
        'first-name-prefix' => '',
        'WW91ciBuYW1l-1' => 'name',
        'WW91ciBFLW1haWw-2' => 'name@email.com',
        'UGhvbmUgTnVtYmVy-3' => 'phone',
        'UHJvZHVjdA-4' => '24 Hour Trial',
        'WW91ciBtZXNzYWdl-5' => 'message',
        */

        $post_data = (array) request()->all();
        if (! empty($post_data['WW91ciBuYW1l-1']) && ! empty($post_data['WW91ciBFLW1haWw-2']) && ! empty($post_data['g-recaptcha-response'])) {
            if (! $this->validateCaptcha($post_data['g-recaptcha-response'], '6LdnTdwiAAAAAFILMCiz50Zr0EYnOzE5CW3o2CWr', 'netstream.store')) {
                return redirect()->back();
            }
            $data = [
                'company' => $post_data['WW91ciBuYW1l-1'],
                'contact' => $post_data['WW91ciBuYW1l-1'],
                'phone' => $post_data['UGhvbmUgTnVtYmVy-3'],
                'email' => $post_data['WW91ciBFLW1haWw-2'],
                'status' => 'Enabled',
                'partner_id' => 1,
                'pricelist_id' => 1,
                'industry_id' => 220,
                'marketing_channel_id' => 43,
                'created_at' => date('Y-m-d H:i:s'),
                'type' => 'lead',
                'lead_score' => 'Hot',
            ];

            $exists = \DB::table('crm_accounts')->where('company', $data['company'])->where('created_at', 'like', date('Y-m-d').'%')->count();
            if (! $exists) {
                $id = \DB::table('crm_accounts')->insertGetId($data);

                module_log(343, $id, 'created');
                $note = 'Product: '.$post_data['UHJvZHVjdA-4'];
                if (! empty($post_data['WW91ciBtZXNzYWdl-5'])) {
                    $note .= '<br>Message: '.$post_data['WW91ciBtZXNzYWdl-5'];
                }

                $data = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => get_user_id_default(),
                    'row_id' => $id,
                    'module_id' => 343,
                    'note' => $note,
                    'is_deleted' => 0,
                ];

                \DB::connection('default')->table('erp_module_notes')->insert($data);
            }
        }

        return redirect()->back();
    }

    public function contactFormCloudtelecoms()
    {
        $post_data = (array) request()->all();

        if (! empty($post_data['RnVsbCBOYW1l-1']) && ! empty($post_data['RS1tYWls-2']) && ! empty($post_data['g-recaptcha-response'])) {
            if (! $this->validateCaptcha($post_data['g-recaptcha-response'], '6LetRdwiAAAAAM6OKrkfE19BJ4-tN42ysZ7TKwip', 'cloudtelecoms.co.za')) {
                return redirect()->back();
            }

            $data = [
                'company' => $post_data['RnVsbCBOYW1l-1'],
                'contact' => $post_data['RnVsbCBOYW1l-1'],
                'phone' => $post_data['UGhvbmUgTnVtYmVy-3'],
                'email' => $post_data['RS1tYWls-2'],
                'marketing_channel_id' => 43,
                'status' => 'Enabled',
                'partner_id' => 1,
                'pricelist_id' => 1,
                'industry_id' => 220,
                'created_at' => date('Y-m-d H:i:s'),
                'type' => 'lead',
            ];
            $exists = \DB::table('crm_accounts')->where('company', $data['company'])->where('created_at', 'like', date('Y-m-d').'%')->count();
            if (! $exists) {
                $id = \DB::table('crm_accounts')->insertGetId($data);

                module_log(343, $id, 'created');

                if (! empty($post_data['TWVzc2FnZQ-4'])) {
                    $note = '<br>Message: '.$post_data['TWVzc2FnZQ-4'];
                    $data = [
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => get_user_id_default(),
                        'row_id' => $id,
                        'module_id' => 343,
                        'note' => $note,
                        'is_deleted' => 0,
                    ];

                    \DB::connection('default')->table('erp_module_notes')->insert($data);
                }
            }
        }

        return redirect()->back();
    }

    public function validateCaptcha($captcha, $key, $hostname)
    {
        try {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = ['secret' => $key,
                'response' => $captcha,
            ];

            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                ],
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $result = json_decode($result);
            if ($result->success && $result->score == '0.9' && $result->hostname == $hostname) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    public function flexmonster($id, $company_id = false)
    {
        if (! $company_id) {
            $company_id = session('instance')->id;
        }

        $instance = \DB::connection('system')->table('erp_instances')->where('id', $company_id)->get()->first();
        $report = \DB::connection($instance->db_connection)->table('erp_reports')->where('id', $id)->get()->first();
        $report_conn = $report->connection;
        if ($report_conn == 'default') {
            $report_conn = $instance->db_connection;
        }
        $report_exists = \DB::connection($instance->db_connection)->table('erp_reports')->where('id', $id)->count();
        if (! $report_exists) {
            return json_alert('Invalid report id', 'error');
        }

        $querybuilder_url = url('/reports?query_builder_id='.$report->id);

        $report = \DB::connection($instance->db_connection)->table('erp_reports')->where('id', $id)->get()->first();
        if ($report->fds) {
            $flexmonster = new \Flexmonster;
            $flexmonster->loadIndexes();
            $flexmonster->dataServerRestart();
        }
        if (empty($report->sql_query)) {
            return redirect()->to($querybuilder_url);
        }

        $sql_query = str_replace(PHP_EOL, ' ', $report->sql_query);
        $valid_query = 1;
        try {
            $sql = $sql_query.' LIMIT 1';

            $result = \DB::connection($report_conn)->select($sql);
        } catch (\Throwable $ex) {
            exception_log($ex);
            $valid_query = 0;
            $query_error = $ex->getMessage();
        }

        if (! $valid_query) {
            exception_log('invalid query');

            return redirect()->to($querybuilder_url);
        }
        if ($company_id == session('instance')->id) {
            $user_id = session('user_id');
        } else {
            $user = \DB::table('erp_users')->where('id', session('user_id'))->get()->first();
            $ext_user = \DB::connection($instance->db_connection)->table('erp_users')->where('username', $user->username)->get()->first();
            $user_id = $ext_user->id;
        }
        $data = [
            'instance_id' => $company_id,
            'report_id' => $id,
            'user_id' => $user_id,
            'readonly' => 1,
        ];
        if (! empty(request()->editreport)) {
            $data['readonly'] = 0;
        }
        $token = \Erp::encode($data);
        if ($report->fds) {
            $url = 'http://reports.cloudtelecoms.io?token='.$token;
        } else {
            $url = 'https://reports.cloudtelecoms.io?token='.$token;
        }

        return redirect()->to($url);
    }

    public function flexmonsterLoad(Request $request)
    {
        try {
            if (empty($request->token)) {
                return json_alert('Token required', 'error');
            } else {
                $token = \Erp::decode($request->token);

                $db_conn = \DB::table('erp_instances')->where('id', $token['instance_id'])->pluck('db_connection')->first();
                if (! $db_conn) {
                    return json_alert('Invalid instance id', 'error');
                }

                if (empty($token['user_id']) || empty($token['instance_id']) || empty($token['report_id'])) {
                    return json_alert('Invalid token', 'error');
                }
                // check role
                $role_id = \DB::connection($db_conn)->table('erp_users')->where('id', $token['user_id'])->pluck('role_id')->first();
                if (! $role_id) {
                    return json_alert('Invalid user role', 'error');
                }

                $level = \DB::connection($db_conn)->table('erp_user_roles')->where('id', $role_id)->pluck('level')->first();
                if ($level != 'Admin') {
                    return json_alert('No access', 'error');
                }
            }

            $report_exists = \DB::connection($db_conn)->table('erp_reports')->where('id', $token['report_id'])->count();
            if (! $report_exists) {
                return json_alert('Invalid report id', 'error');
            }

            $querybuilder_url = url('/reports?query_builder_id='.$report->id);

            $report = \DB::connection($db_conn)->table('erp_reports')->where('id', $token['report_id'])->get()->first();
            if (empty($report->sql_query)) {
                return json_alert('Empty SQL', 'query_error', ['querybuilder_url' => $querybuilder_url]);
            }
            if ($report->connection == 'default') {
                $report->connection = $db_conn;
            }
            $sql_query = str_replace(PHP_EOL, ' ', $report->sql_query);
            $valid_query = 1;
            try {
                $sql = $sql_query.' LIMIT 1';

                $result = \DB::connection($report->connection)->select($sql);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $valid_query = 0;
                $query_error = $ex->getMessage();
            }

            if (! $valid_query) {
                return redirect()->to($querybuilder_url);
            }

            // REPORT DATA
            if ($report->fds) {
                $datasource = [
                    'type' => 'api',
                    'url' => 'http://156.0.96.71:'.$report->report_port,
                    'index' => $report->report_index,
                ];
            } else {
                $sql_conn = ($report->connection == 'default') ? $db_conn : $report->connection;

                $results = \DB::connection($sql_conn)->select($report->sql_query);

                $datasource = collect($results)->toArray();
            }

            // REPORT STATE
            $report_row = \DB::connection($db_conn)->table('erp_reports')->where('id', $report->id)->get()->first();
            $title = strtoupper($report_row->name);
            $subtitle = '';

            $query_data = unserialize($report_row->query_data);
            if (! empty($query_data['filter_period']) && ! empty($query_data['filter_period'])) {
                $title .= ' <br> Period: '.date('Y-m', strtotime($query_data['filter_period']));

                if (! empty($report_row->sql_where)) {
                    $subtitle .= ' <br> '.$report_row->sql_where;
                }
            } elseif (! empty($query_data['date_filter_column']) && ! empty($query_data['date_filter_value'])) {
                $title .= '('.$query_data['date_filter_column'].' = '.$query_data['date_filter_value'].')';

                if (! empty($report_row->sql_where)) {
                    $subtitle .= ' <br> '.$report_row->sql_where;
                }
            } elseif (! empty($report_row->sql_where)) {
                $subtitle .= $report_row->sql_where;
            }

            if ($subtitle) {
                $name = $title.'<br><span>'.str_replace('and ', '<br>and ', strtolower($subtitle)).'</span>';
            } else {
                $name = $title;
            }
            $report->title = $name;
            $config = \DB::connection($db_conn)->table('erp_reports')->where('id', $report->id)->pluck('report_config')->first();
            $report_state = json_decode($config);

            if (! empty($report_state)) {
                $report_config = $report_state->report;

                $report_config = json_decode($report_config);
                if (! empty($report_config->options)) {
                    $report_config->options->datePattern = 'yyyy-MM-dd';
                    $report_config->options->dateTimePattern = 'yyyy-MM-dd HH:mm';
                }
                $report_config->name = $name;
                if (! empty($report_config->options) && ! empty($report_config->options->grid) && ! empty($report_config->options->grid->title)) {
                    $report_config->options->grid->title = $name;
                }
            }
            // if(is_dev()){

            if (! empty($report_config)) {
                $erp_reports = new \ErpReports;
                $erp_reports->setErpConnection($db_conn);
                $mappings = $erp_reports->getDateMappings($report->id);
                if (! empty($mappings) && is_array($mappings) && count($mappings) > 0) {
                    $report_config->mapping = $mappings;
                }
            }
            //dd($report_config);
            //}
            $querybuilder_url = url('/reports?query_builder_id='.$report->id);
            //dd($mappings, $datasource[0]);
            $databases = \Config::get('database')['connections'];
            $connection_info = $databases[$report->connection];
            $connection_string = 'Server='.$connection_info['host'].';Port='.$connection_info['port'].';Uid='.$connection_info['username'].';Pwd='.$connection_info['password'].';Database='.$connection_info['database'].'; convert zero datetime=True';
            $response = [
                'querybuilder_url' => $querybuilder_url,
                'datasource' => $datasource,
                'status' => 'success',
                'instance_id' => $token['instance_id'],
                'report_id' => $token['report_id'],
                'report' => $report,
                'report_state' => json_encode($report_config),
                'connection_string' => $connection_string,
                'edit_access' => ($role_id == 1 || $role_id == 2 || $role_id == 34) ? true : false,
                'license_key' => 'Z7WC-XJDA5X-174651-3P1M1N-2M1F2Q-1W0X14-2Z295O-204A4S-4L255S-4V411N-3Q6K6U-0N',
            ];
            if ($token['readonly'] == 1) {
                $response['edit_access'] = false;
            }

            return response()->json($response);
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }

    public function flexmonsterSaveState(Request $request)
    {
        if (empty($request->token)) {
            return json_alert('Token required', 'error');
        } else {
            $token = \Erp::decode($request->token);

            $db_conn = \DB::table('erp_instances')->where('id', $token['instance_id'])->pluck('db_connection')->first();
            if (! $db_conn) {
                return json_alert('Invalid instance id', 'error');
            }

            if (empty($token['user_id']) || empty($token['instance_id']) || empty($token['report_id'])) {
                return json_alert('Invalid token', 'error');
            }
            // check role
            $role_id = \DB::connection($db_conn)->table('erp_users')->where('id', $token['user_id'])->pluck('role_id')->first();
            if (! $role_id) {
                return json_alert('Invalid user role', 'error');
            }

            $level = \DB::connection($db_conn)->table('erp_user_roles')->where('id', $role_id)->pluck('level')->first();
            if ($level != 'Admin') {
                return json_alert('No access', 'error');
            }
        }
        $report_exists = \DB::connection($db_conn)->table('erp_reports')->where('id', $token['report_id'])->count();
        if (! $report_exists) {
            return json_alert('Invalid report id', 'error');
        }
        $report = \DB::connection($db_conn)->table('erp_reports')->where('id', $token['report_id'])->get()->first();
        $post_data = $request->all();
        unset($post_data['token']);
        $report_state = json_encode($post_data);
        $report_state = str_replace('"false"', 'false', $report_state);
        $report_state = str_replace('"true"', 'true', $report_state);

        $report_config = (object) [
            'name' => $report->name,
            'type' => 'json',
            'report' => $report_state,
        ];

        \DB::connection($db_conn)->table('erp_reports')->where('id', $token['report_id'])->update(['report_config' => json_encode($report_config)]);
        // return json_alert('Saved');
    }

    public function flexmonsterExportSave(Request $request)
    {
        if (empty($request->token)) {
            return json_alert('Token required', 'error');
        } else {
            $token = \Erp::decode($request->token);

            $db_conn = \DB::table('erp_instances')->where('id', $token['instance_id'])->pluck('db_connection')->first();
            if (! $db_conn) {
                return json_alert('Invalid instance id', 'error');
            }

            if (empty($token['user_id']) || empty($token['instance_id']) || empty($token['report_id'])) {
                return json_alert('Invalid token', 'error');
            }
            // check role
            $role_id = \DB::connection($db_conn)->table('erp_users')->where('id', $token['user_id'])->pluck('role_id')->first();
            if (! $role_id) {
                return json_alert('Invalid user role', 'error');
            }

            $level = \DB::connection($db_conn)->table('erp_user_roles')->where('id', $role_id)->pluck('level')->first();
            if ($level != 'Admin') {
                return json_alert('No access', 'error');
            }
        }
        $report_exists = \DB::connection($db_conn)->table('erp_reports')->where('id', $token['report_id'])->count();
        if (! $report_exists) {
            return json_alert('Invalid report id', 'error');
        }

        try {
            $file_ext = $request->file_ext;
            $report = \DB::connection($db_conn)->table('erp_reports')->where('id', $token['report_id'])->get()->first();
            $report_name = $report->name;

            $data = [];
            $data['files'] = [];
            $field = 'filedata';

            if ($request->file($field)) {
                $file = $request->file($field);
                if ($token['uniq_id']) {
                    $filename = $token['instance_id'].'/'.$token['report_id'].$token['uniq_id'].'.'.$file_ext;
                } else {
                    $filename = $token['instance_id'].'/'.$token['report_id'].'.'.$file_ext;
                }
                \Storage::disk('reports')->put($filename, file_get_contents($file));
                $filepath = '/home/erp/storage/reports/'.$filename;
            }

            $data['files'][] = $filepath;

            if (! empty($data['files']) && count($data['files']) > 0) {
                $data['internal_function'] = 'report_emails';
                $data['frequency'] = ucfirst($report->email_frequency);
                $data['report_name'] = $report_name;
                //$data['test_debug'] = 1;
                // erp_process_notification(1, $data);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }

    public function mailManager(Request $request)
    {
        if ($request->isMethod('post')) {
            // for managing inbox, repliese, delete
        }

        if ($request->isMethod('get')) {
            // get mailbox based of session credentials
            // render mailinbox grid with form to reply

            $account = \DB::table('crm_mailboxes')->where('user_id', 3696)->get()->first();
            $account = (array) $account;
            $account['username'] = 'godney@telecloud.co.za';
            $account['password'] = 'Webmin321';
            unset($account['id']);
            unset($account['user_id']);
            unset($account['mail_username']);
            unset($account['mail_password']);
            $data['username'] = $account['username'];
            $data['usermail'] = $account['username'];
            $client = \MailClient::make($account);

            //Connect to the IMAP Server
            $client->connect();

            //Get all Mailboxes
            /** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
            $folders = $client->getFolders();
            $folders_json = [];
            $i = 1;
            foreach ($folders as $folder) {
                //Get all Messages of the current Mailbox $folder
                /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
                $messages = $folder->messages()->all()->get();
                $message_count = count($messages);

                /** @var \Webklex\PHPIMAP\Message $message */
                foreach ($messages as $message) {
                    $date = $message->getDate()[0];
                    $flags = [];
                    $flags_collection = $message->getFlags();
                    if (! empty($flags_collection)) {
                        $flags = $message->getFlags()->toArray();
                    }
                    $msg = [
                        'ContactID' => $message->getUid(),
                        'text' => $message->getFrom()[0]->personal,
                        'ContactTitle' => $message->getSubject()[0],
                        'Message' => ($message->hasHTMLBody()) ? $message->getHTMLBody() : $message->getTextBody(),
                        'Email' => $message->getFrom()[0]->mail,
                        'To' => $message->getTo()[0]->personal,
                        'ToMail' => $message->getTo()[0]->mail,
                        'Image' => 'styles/images/images/23.png',
                        'Time' => $date->format('g:i A'),
                        'Date' => $date->format('d/m/Y'),
                        'Day' => $date->format('d/m/Y'),
                        'Folder' => $folder->full_name,
                        'ReadStyle' => (in_array('Seen', $flags)) ? 'Read' : 'Unread',
                        'ReadTitle' => (in_array('Seen', $flags)) ? 'Mark as unread' : 'Mark as read',
                        'Flagged' => (in_array('Flagged', $flags)) ? 'Flagged' : 'None',
                        'FlagTitle' => (in_array('Flagged', $flags)) ? 'Remove the flag from this Message' : 'Flag this message',
                    ];

                    $msg['CC'] = [];
                    $cc = $message->getCc();
                    if (! empty($cc)) {
                        $cc = $cc->get();
                        foreach ($cc as $c) {
                            $msg['CC'][] = $c->personal;
                            $msg['CCMail'][] = $c->mail;
                        }
                    }

                    $msg['BCC'] = [];
                    $bcc = $message->getBcc();
                    if (! empty($bcc)) {
                        $bcc = $bcc->get();
                        foreach ($bcc as $b) {
                            $msg['BCC'][] = $b->personal;
                            $msg['BCCMail'][] = $b->mail;
                        }
                    }
                    $messages_json[] = $msg;
                }
                $folders_json[] = [
                    'ID' => $i,
                    'PID' => null,
                    'Name' => $folder->full_name,
                    'HasChild' => $folder->has_children,
                    'Count' => $message_count,
                ];
                $i++;
                $folders_json[] = $folder_data;
            }
            $data['folders'] = $folders_json;
            $data['messages'] = $messages_json;

            return view('__app.components.mail_manager', $data);
        }
    }

    public function agridData(Request $request)
    {
        $json = file_get_contents(public_path().'olympic-winners.json', true);
        $rows = json_decode($json);
        $total = count($rows);

        return response()->json(['rows' => $rows, 'lastRow' => $total]);
    }

    public function smsResult(Request $request)
    {
        $status = '';
        if ($request->status == 1) {
            $status = 'Delivered';
        }
        if ($request->status == 2) {
            $status = 'Undelivered';
        }
        if ($request->status == 4) {
            $status = 'Queued at network';
        }
        if ($request->status == 8) {
            $status = 'Sent to network';
        }
        if ($request->status == 16) {
            $status = 'Failed at network';
        }

        $num = \DB::connection('default')->table('isp_sms_message_queue')->where('id', $request->queue_id)->pluck('number')->first();
        \DB::connection('default')->table('isp_sms_message_queue')->where('id', $request->queue_id)->update(['status' => $status]);
    }

    public function tinymceImages(Request $request)
    {
        if ($request->file('file')) {
            $file = $request->file('file');

            $upload_path = public_path().'assets/tinymce_images/';
            $filename = $file->getClientOriginalName();

            $filename = str_replace([' ', ','], '_', $filename);

            $uploadSuccess = $file->move($upload_path, $filename);

            if ($uploadSuccess) {
                $upload_path = '/assets/tinymce_images/';
                $data['location'] = $upload_path.$filename;

                return response()->json($data);
            }
        }

        $data['location'] = '';

        return response()->json($data);
    }

    public function mailBox(Request $request)
    {
        if (empty(session('user_id'))) {
            $error = [
                'status' => 'error',
                'message' => 'Session expired.',
            ];
        }
        $mailbox_exists = \DB::table('crm_mailboxes')->where('user_id', 3696)->count();
        if (empty($mailbox_exists)) {
            $error = [
                'status' => 'error',
                'message' => 'Mailbox not found',
            ];
        }
        if (! empty($error)) {
            return redirect()->back()->with($error);
        }
        $data = ['menu_name' => 'Mailbox'];

        try {
            $account = \DB::table('crm_mailboxes')->where('user_id', 3696)->get()->first();
            $account = (array) $account;
            $account['username'] = $account['mail_username'];
            $account['password'] = $account['mail_password'];
            unset($account['id']);
            unset($account['user_id']);
            unset($account['mail_username']);
            unset($account['mail_password']);
            $data['username'] = $account['username'];
            $data['usermail'] = $account['username'];
            $client = \MailClient::make($account);

            //Connect to the IMAP Server
            $client->connect();

            //Get all Mailboxes
            /** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
            $folders = $client->getFolders();
            $folders_json = [];
            $i = 1;
            foreach ($folders as $folder) {
                //Get all Messages of the current Mailbox $folder
                /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
                $messages = $folder->messages()->all()->get();
                $message_count = count($messages);

                /** @var \Webklex\PHPIMAP\Message $message */
                foreach ($messages as $message) {
                    $date = $message->getDate()[0];
                    $flags = [];
                    $flags_collection = $message->getFlags();
                    if (! empty($flags_collection)) {
                        $flags = $message->getFlags()->toArray();
                    }
                    $msg = [
                        'ContactID' => $message->getUid(),
                        'text' => $message->getFrom()[0]->personal,
                        'ContactTitle' => $message->getSubject()[0],
                        'Message' => ($message->hasHTMLBody()) ? $message->getHTMLBody() : $message->getTextBody(),
                        'Email' => $message->getFrom()[0]->mail,
                        'To' => $message->getTo()[0]->personal,
                        'ToMail' => $message->getTo()[0]->mail,
                        'Image' => 'styles/images/images/23.png',
                        'Time' => $date->format('g:i A'),
                        'Date' => $date->format('d/m/Y'),
                        'Day' => $date->format('d/m/Y'),
                        'Folder' => $folder->full_name,
                        'ReadStyle' => (in_array('Seen', $flags)) ? 'Read' : 'Unread',
                        'ReadTitle' => (in_array('Seen', $flags)) ? 'Mark as unread' : 'Mark as read',
                        'Flagged' => (in_array('Flagged', $flags)) ? 'Flagged' : 'None',
                        'FlagTitle' => (in_array('Flagged', $flags)) ? 'Remove the flag from this Message' : 'Flag this message',
                    ];

                    $msg['CC'] = [];
                    $cc = $message->getCc();
                    if (! empty($cc)) {
                        $cc = $cc->get();
                        foreach ($cc as $c) {
                            $msg['CC'][] = $c->personal;
                            $msg['CCMail'][] = $c->mail;
                        }
                    }

                    $msg['BCC'] = [];
                    $bcc = $message->getBcc();
                    if (! empty($bcc)) {
                        $bcc = $bcc->get();
                        foreach ($bcc as $b) {
                            $msg['BCC'][] = $b->personal;
                            $msg['BCCMail'][] = $b->mail;
                        }
                    }
                    $messages_json[] = $msg;
                }
                $folders_json[] = [
                    'ID' => $i,
                    'PID' => null,
                    'Name' => $folder->full_name,
                    'HasChild' => $folder->has_children,
                    'Count' => $message_count,
                ];
                $i++;
                $folders_json[] = $folder_data;
            }
            $data['folders'] = $folders_json;
            $data['messages'] = $messages_json;
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
        }

        //dd($data);
        return view('__app.components.mailbox', $data);
    }

    public function mailBoxData(Request $request)
    {
        /* @var \Webklex\PHPIMAP\Client $client */
        try {
            $account = \DB::table('crm_mailboxes')->where('user_id', session('user_id'))->get()->first();
            $account = (array) $account;
            $account['username'] = $account['mail_username'];
            $account['password'] = $account['mail_password'];
            unset($account['id']);
            unset($account['user_id']);
            unset($account['mail_username']);
            unset($account['mail_password']);

            $client = \MailClient::make($account);

            //Connect to the IMAP Server
            $client->connect();

            //Get all Mailboxes
            /* @var \Webklex\PHPIMAP\Support\FolderCollection $folders */

            if (! empty($request->get_folders)) {
                $folders = $client->getFolders();
                $folders_json = [];
                $i = 1;
                foreach ($folders as $folder) {
                    $folders_json[] = [
                        'ID' => $i,
                        'PID' => null,
                        'Name' => $folder->full_name,
                        'HasChild' => $folder->has_children,
                        'Count' => '',
                    ];
                    $i++;
                }

                // echo $folders_json;
                return response()->json($folders_json);
            }

            if (! empty($request->get_emails)) {
                $messages_json = [];
                $folder = $client->getFolder($request->get_emails);

                //Get all Messages of the current Mailbox $folder
                /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
                $messages = $folder->messages()->all()->get();

                /** @var \Webklex\PHPIMAP\Message $message */
                foreach ($messages as $message) {
                    $date = $message->getDate()[0];
                    $flags = [];
                    $flags_collection = $message->getFlags();
                    if (! empty($flags_collection)) {
                        $flags = $message->getFlags()->toArray();
                    }
                    $msg = [
                        'ContactID' => $message->getUid(),
                        'text' => $message->getFrom()[0]->personal,
                        'ContactTitle' => $message->getSubject(),
                        'Message' => ($message->hasHTMLBody()) ? $message->getHTMLBody() : $message->getTextBody(),
                        'Email' => $message->getFrom()[0]->mail,
                        'To' => $message->getTo()[0]->personal,
                        'ToMail' => $message->getTo()[0]->mail,
                        'Image' => '',
                        'Time' => $date->format('g:i A'),
                        'Date' => $date->format('d/m/Y'),
                        'Day' => $date->format('d/m/Y'),
                        'Folder' => $request->get_emails,
                        'ReadStyle' => (in_array('Seen', $flags)) ? 'Read' : 'Unread',
                        'ReadTitle' => (in_array('Seen', $flags)) ? 'Mark as unread' : 'Mark as read',
                        'Flagged' => (in_array('Flagged', $flags)) ? 'Flagged' : 'None',
                        'FlagTitle' => (in_array('Flagged', $flags)) ? 'Remove the flag from this Message' : 'Flag this message',
                    ];

                    $msg['CC'] = [];
                    $cc = $message->getCc();
                    if (! empty($cc)) {
                        $cc = $cc->get();
                        foreach ($cc as $c) {
                            $msg['CC'][] = $c->personal;
                            $msg['CCMail'][] = $c->mail;
                        }
                    }

                    $msg['BCC'] = [];
                    $bcc = $message->getBcc();
                    if (! empty($bcc)) {
                        $bcc = $bcc->get();
                        foreach ($bcc as $b) {
                            $msg['BCC'][] = $b->personal;
                            $msg['BCCMail'][] = $b->mail;
                        }
                    }
                    $messages_json[] = $msg;
                }

                return response()->json($messages_json);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
        }
    }

    public function exportCdrByGateway(Request $request)
    {
        $gateway_name = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $request->gateway_uuid)->pluck('gateway')->first();
        $file_name = export_cdr_gateway($gateway_name, $request->cdr_tables);

        return json_alert(attachments_url().$file_name, 'reload');
    }

    public function diagram(Request $request, $id)
    {
        if ($request->isMethod('get')) {
            $diagram = \DB::table('crm_flowcharts')->where('id', $id)->get()->first();
            $data = (array) $diagram;
            $data['menu_name'] = ucwords($diagram->name).' Diagram';
            if (empty($diagram->xml)) {
                $data['xml'] = 'test.png';
            }

            return view('__app.components.diagram', $data);
        }
    }

    public function diagramSave(Request $request)
    {
        try {
            \DB::table('crm_flowcharts')->where('id', $request->id)->update(['xml' => $request->xml]);

            return json_alert('Saved');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function reportQuery(Request $request, $id)
    {
        $connection = 'default';

        if (! empty($request->report_connection)) {
            $connection = $request->report_connection;
        }
        if (! empty($request->company_id)) {
            $companies = \DB::table('erp_instances')->get();
            $erp = $companies->where('id', $request->company_id)->first();
            $connection = $erp->db_connection;
        }

        if (! is_superadmin()) {
            if ($request->ajax()) {
                return json_alert('No access', 'error');
            } else {
                return Redirect::back()->with(['status' => 'error', 'message' => 'Report Query needs to be set.']);
            }
        }

        $report = \DB::connection($connection)->table('erp_reports')->where('id', $id)->get()->first();

        $sql_query = \DB::connection($connection)->table('erp_reports')->where('id', $id)->pluck('sql_query')->first();
        $sql_where = \DB::connection($connection)->table('erp_reports')->where('id', $id)->pluck('sql_where')->first();
        $name = \DB::connection($connection)->table('erp_reports')->where('id', $id)->pluck('name')->first();
        $config = \DB::connection($connection)->table('erp_reports')->where('id', $id)->pluck('query_data')->first();
        $sql_json = \DB::connection($connection)->table('erp_reports')->where('id', $id)->pluck('rules_json')->first();
        $data = unserialize($config);

        if (! empty($data['db_columns']) && is_array($data['db_columns']) && count($data['db_columns']) > 0) {
            foreach ($data['db_columns'] as $i => $col) {
                $col_arr = explode('.', $col);
                $table = $col_arr[0];
                $field = $col_arr[1];
                $c = $report->connection;
                if ($c == 'default') {
                    $c = $connection;
                }
                if (! in_array($table, $data['db_tables'])) {
                    unset($data['db_columns'][$i]);
                }
                if (! \Schema::connection($c)->hasColumn($table, $field)) {
                    unset($data['db_columns'][$i]);
                }
            }
        }

        if (is_array($data['db_columns'])) {
            $data['db_columns'] = array_values($data['db_columns']);
        } else {
            $data['db_columns'] = [];
        }

        $request = $data;

        if (empty($data['join_type']) || count($data['join_type']) == 0) {
            $data['join_type'] = [''];
            $data['join_table_1'] = [''];
            $data['join_table_2'] = [''];
        }
        $data['connections'] = [];
        $connections = get_db_connections();
        foreach ($connections as $c) {
            $data['connections'][] = (object) ['text' => $c, 'value' => $c];
        }
        $erp_reports = new \ErpReports;
        $erp_reports->setErpConnection($connection);

        $data['tables_ds'] = $erp_reports->reportGetTables($request);
        $data['columns_ds'] = $erp_reports->reportGetColumns($request)['datasource'];
        $data['joins_ds'] = $erp_reports->reportGetJoinColumns($request);
        $data['filters_ds'] = $erp_reports->reportGetFilterColumns($request);
        $data['date_filter_columns'] = $erp_reports->reportGetDateColumns($request);
        $data['date_filter_values'] = [
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

        if (! empty($sql_json)) {
            $rules_json = json_decode($sql_json);

            if (! empty($rules_json->rules) && count($rules_json->rules) > 0) {
                foreach ($rules_json->rules as $i => $rule) {
                    if (str_contains($rule->field, '.')) {
                        foreach ($data['filters_ds'] as $filter_ds) {
                            $new_field_name = str_replace('.', '__', $rule->field);
                            if ($new_field_name == $filter_ds->field) {
                                $rules_json->rules[$i]->field = $filter_ds->field;
                                $rules_json->rules[$i]->label = $filter_ds->label;
                            }
                        }
                    }
                }
            }
            $sql_json = json_encode($rules_json);
        }

        $data['sql_json'] = $sql_json;
        $data['id'] = $id;
        $data['sql_query'] = $sql_query;
        $data['query_error'] = $report->query_error;
        $data['sql_where'] = $sql_where;
        $data['menu_name'] = $name;
        $data['connection'] = $connection;
        $filters_datasource = [];

        if (! empty($sql_query) && $report->invalid_query == 0) {
            $filter_sql = $erp_reports->reportFilterSQL($id);
            //dd($filter_sql);
            try {
                $filters_datasource = \DB::connection($report->connection)->select($filter_sql);
            } catch (\Throwable $ex) {
                exception_log($ex);
            }
        }
        $data['filters_datasource'] = $filters_datasource;
        $data['enable_filters'] = false;

        //dd($data);
        return view('__app.components.query', $data);
    }

    public function reportQueryDateFilter(Request $request)
    {
        try {
            if (! empty($request->report_id) && ! empty($request->date_filter_field) && ! empty($request->date_filter_value)) {
                $id = $request->report_id;
                $config = \DB::table('erp_reports')->where('id', $id)->pluck('query_data')->first();
                $query_data = unserialize($config);

                $query_data['date_filter_value'] = $request->date_filter_value;
                if ($request->date_filter_field == 'predefined') {
                    if (empty($query_data['date_filter_column'])) {
                        return json_alert('Date filter field not set', 'warning');
                    }
                } else {
                    $query_data['date_filter_column'] = $request->date_filter_field;
                }

                $report_model = new \App\Models\ReportModel($id);
                if ($request->date_filter_field != 'predefined') {
                    $colDefs = $report_model->colDefs;
                    // aa($colDefs);
                    foreach ($colDefs as $colDef) {
                        if ($colDef['field'] == $query_data['date_filter_column']) {
                            $query_data['date_filter_column'] = $colDef['db_col'];
                        }
                    }
                }

                \DB::table('erp_reports')->where('id', $id)->update(['query_data' => serialize($query_data)]);
                $erp_reports = new \ErpReports;
                $erp_reports->setErpConnection(session('instance')->db_connection);
                $sql = $erp_reports->reportSQL($id);

                if ($sql) {
                    \DB::connection('default')->table('erp_reports')->where('id', $id)->update(['sql_query' => $sql]);
                }

                return json_alert('Date filter saved');
            } else {
                return json_alert('Date filter not saved', 'error');
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function reportQuerySave(Request $request)
    {
        try {
            $connection = $request->report_connection;

            if (empty($connection)) {
                abort(500, 'Invalid connection');
            }

            $erp_reports = new \ErpReports;
            $erp_reports->setErpConnection($connection);

            if ($request->action == 'save') {
                if (! empty($request->sql_json)) {
                    $request->sql_json = json_encode($request->sql_json);
                }
                $data = $request->all();
                if (empty($data['db_tables']) || (is_array($data['db_tables']) && count($data['db_tables']) == 0)) {
                    return json_alert('Tables required', 'warning');
                }
                foreach ($data as $key => $val) {
                    if ($key == 'sql_where') {
                        unset($data[$key]);
                    }
                    if (str_contains($key, 'db_filters')) {
                        unset($data[$key]);
                    }
                }
                $num_tables = count($data['db_tables']);
                $required_joins = $num_tables - 1;

                \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['tables_used' => implode(',', $data['db_tables'])]);

                if (! empty($data['db_columns']) && is_array($data['db_columns']) && count($data['db_columns']) > 0) {
                    foreach ($data['db_columns'] as $i => $col) {
                        $col_arr = explode('.', $col);
                        $table = $col_arr[0];
                        $field = $col_arr[1];

                        if (! in_array($table, $data['db_tables'])) {
                            unset($data['db_columns'][$i]);
                        }
                    }
                }

                $config = serialize($data);
                \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['invalid_query' => 0, 'query_data' => $config]);

                if (empty($data['join_table_1']) || count($data['join_table_1']) < $required_joins || empty($data['join_table_1'][0])) {
                    $erp_reports->reportResetJoinsById($request->id);
                }

                $sql_where = \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->pluck('sql_where')->first();

                if (! empty($request->sql_where)) {
                    $request_where = str_replace('__', '.', $request->sql_where);
                    $request_where = str_replace('"', "'", $request->sql_where);
                    \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['sql_where' => $request_where]);
                } else {
                    \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['sql_where' => '']);
                }

                $sql = $erp_reports->reportSQL($request->id);

                $token = \Erp::encode($connection);

                \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['sql_query' => $sql]);
                \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['connection' => $data['db_conn']]);

                if (! empty($request->reset_joins) && $request->reset_joins == 1) {
                    $erp_reports->reportResetJoinsById($request->id);
                }
                /*
                $fds = \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->pluck('fds')->first();
                if ($fds) {
                    $flexmonster = new \Flexmonster();
                    $flexmonster->loadIndexes();
                    $flexmonster->dataServerRestart();
                }
                */

                return json_alert('Query saved.', 'success');
            }

            if ($request->action == 'save_rules') {
                $sql_json = '';
                if (! empty($request->sql_json)) {
                    if (! empty($request->sql_json['rules']) && count($request->sql_json['rules']) > 0) {
                        foreach ($request->sql_json['rules'] as $rule) {
                            if ($rule['type'] == 'date') {
                                $v = date('Y-m-d', strtotime($rule['value']));
                                $request->sql_where = str_replace($rule['value'], $v, $request->sql_where);
                            }
                        }
                    }
                    $sql_json = json_encode($request->sql_json);
                }
                $sql_where = str_replace('__', '.', $request->sql_where);

                \DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['rules_json' => $sql_json, 'sql_where' => $sql_where]);
            }

            if ($request->action == 'get_tables') {
                return $erp_reports->reportGetTables($request);
            }

            if ($request->action == 'get_columns') {
                return $erp_reports->reportGetColumns($request);
            }

            if ($request->action == 'get_date_columns') {
                return $erp_reports->reportGetDateColumns($request);
            }

            if ($request->action == 'get_filter_columns') {
                return $erp_reports->reportGetFilterColumns($request);
            }

            if ($request->action == 'get_join_columns') {
                return $erp_reports->reportGetJoinColumns($request);
            }

            if ($request->action == 'reset_joins') {
                return $erp_reports->reportResetJoinsByRequest($request);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function reportQueryReset(Request $request)
    {
        $connection = $request->report_connection;
        //\DB::connection($connection)->table('erp_reports')->where('id', $request->id)->update(['invalid_query' => 1,'sql_query' => null,'sql_where' => null, 'query_data' => null, 'settings' => null, 'rules_json' => null, 'report_config' => null]);

        return json_alert('Report Reset');
    }

    public function pbxTestCall(Request $request)
    {
        if (session('role_id') > 10) {
            return json_alert('No Access', 'warning');
        }

        $result = pbx_call($request->outbound_caller_id, 12, 'account', $request->number_to_call);
        if ($result === true) {
            return json_alert('Call sent to PBX');
        } else {
            return json_alert($result, 'error');
        }
    }

    public function clearCalleeIDNumber(Request $request)
    {
        // Clear Caller IDs where Callee IDs are inactive (They are only giving rejections)
        $callee_id_number = $request->callee_id_number;
        if (empty($callee_id_number)) {
            return json_alert('Callee ID Number required.', 'warning');
        }
        $call_records_count = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('hangup_cause', '!=', 'CALL_REJECTED')
            ->where('hangup_cause', '!=', 'NO_USER_RESPONSE')
            ->whereRaw(\DB::raw('TIMESTAMP(hangup_time) > TIMESTAMP(NOW()-INTERVAL 24 HOUR)'))
            ->where('callee_id_number', $callee_id_number)
            ->count();
        if ($call_records_count == 0) {
            \DB::connection('pbx')->table('mon_rejected_history')->where('callee_id_number', $callee_id_number)->delete();
            $networks = [
                'vodacom',
                'mtn',
                'cellc',
                'telkom_mobile',
                'telkom',
                'liquid',
            ];
            foreach ($networks as $network) {
                \DB::connection('pbx')->table('p_phone_numbers')->where($network, $callee_id_number)->update([$network => 1]);
            }
        }

        return json_alert('Callee ID Number cleared.');
    }

    public function lteSimswop(Request $request)
    {
        $lte = \DB::table('isp_data_lte_vodacom_accounts')->where('id', $request->id)->get()->first();
        \DB::table('isp_data_lte_vodacom_accounts')->where('id', $request->id)
            ->update(['msisdn_old' => $lte->msisdn, 'msisdn' => $request->new_lte, 'msisdn_change_date' => $request->confirmed_date]);
        \DB::table('sub_services')->where('id', $lte->subscription_id)
            ->update(['detail' => $request->new_lte]);
        $data['internal_function'] = 'lte_simswop';
        $data['bcc_email'] = 'neliswa.sango@vodacom.co.za';
        $data['old_number'] = $lte->msisdn;
        $data['new_number'] = $request->new_lte;
        $data['confirmed_date'] = $request->confirmed_date;
        erp_process_notification($lte->account_id, $data);
        $log = [
            'created_at' => date('Y-m-d H:i:s'),
            'change_date' => $request->confirmed_date,
            'subscription_id' => $lte->subscription_id,
            'created_by' => session('user_id'),
            'old_msisdn' => $lte->msisdn,
            'new_msisdn' => $request->new_lte,
        ];
        \DB::table('isp_data_lte_vodacom_simswop')->insert($log);

        return json_alert('Saved.');
    }

    public function knowledgebase(Request $request)
    {
        /// FIRST TAB - TROUBLESHOOTER
        $ts = \DB::connection('default')->table('hd_server_management')->orderby('sort_order')->get();
        $data['ts'] = collect($ts)->groupby('category');

        /// SECOND TAB - KNOWLEDGEBASE
        $kb = \DB::connection('default')->table('hd_knowledge_base')
            ->select('hd_knowledge_base.*', 'crm_product_categories.sort_order', 'crm_product_categories.name as category_name', 'crm_product_categories.department')
            ->join('crm_product_categories', 'crm_product_categories.id', '=', 'hd_knowledge_base.product_category_id')
            ->orderby('crm_product_categories.sort_order')->get();
        $knowledge_base = [];
        foreach ($kb as $k) {
            $k->category = $k->category_name;
            $knowledge_base[] = $k;
        }
        $data['kb'] = collect($knowledge_base)->groupby('category');

        session(['webform_module_id' => 580]);
        /// THIRD TAB - TICKET
        $menu_name = get_menu_url_from_table('hd_tickets');

        session(['troubleshooter_form' => true]);
        $request_data = new \Illuminate\Http\Request;
        $url = $menu_name.'/edit';

        $request_data->server->set('REQUEST_URI', $url);

        return view('__app.components.pages.knowledgebase', $data);
    }

    public function submitTicket(Request $request)
    {
        $menu_name = get_menu_url_from_table('hd_tickets');

        return redirect()->to($menu_name.'/edit');
    }

    public function helpdesk(Request $request)
    {
        if ($request->isMethod('get')) {
            $data = [
                'menu_name' => 'Helpdesk',
            ];

            session(['webform_module_id' => 580]);
            /// TICKET
            $menu_name = get_menu_url_from_table('hd_tickets');

            session(['troubleshooter_form' => true]);
            $request_data = new \Illuminate\Http\Request;
            $url = $menu_name.'/edit';

            $request_data->server->set('REQUEST_URI', $url);
            $data['ticket'] = app(\App\Http\Controllers\ModuleController::class)->getEdit($request_data);

            return view('__app.components.pages.helpdesk', $data);
        }

        if ($request->isMethod('post')) {
            if (empty(session('troubleshooting'))) {
                if (empty($request->option)) {
                    return json_alert('Please select an option.', 'warning');
                }
                $ts = \DB::table('crm_troubleshooter')->where('title', $request->option)->get()->first();
                $ts_opt = (object) ['id' => $ts->id];
                session(['troubleshooting' => $ts_opt]);

                return json_alert('', 'refresh_instant');
            } else {
                // Extension is not registering
                if (session('troubleshooting')->id == 6) {
                    if (empty($request->ip_address)) {
                        return json_alert('IP Address required.', 'warning');
                    }
                    $pbx = new \FusionPBX;
                    $blocked = $pbx->checkBlockedIP($request->ip_address);

                    $ts = \DB::table('crm_troubleshooter')->where('parent_id', session('troubleshooting')->id)->get()->first();
                    $ts_opt = (object) ['id' => $ts->id];
                    session(['troubleshooting' => $ts_opt]);
                    if ($blocked) {
                        $pbx->flushFail2Ban();

                        return json_alert('Your IP has been unblocked.', 'success', ['refresh' => 1]);
                    } else {
                        return json_alert('Your IP is not being blocked.', 'success', ['refresh' => 1]);
                    }
                } else {
                    if (empty($request->option)) {
                        return json_alert('Please select an option.', 'warning');
                    }
                    $ts = \DB::table('crm_troubleshooter')->where('title', $request->option)->get()->first();
                    $ts_opt = (object) ['id' => $ts->id];
                    session(['troubleshooting' => $ts_opt]);

                    return json_alert('', 'refresh_instant');
                }
            }
        }
    }

    public function domainsImport(Request $request)
    {
        ini_set('max_execution_time', 180);
        if (empty($_FILES)) {
            return json_alert('Invalid File', 'error');
        }

        if (empty($request->file('import'))) {
            return json_alert('Please select file to upload', 'error');
        }

        if (empty($request->provider)) {
            return json_alert('Provider required.', 'error');
        }
        $provider = $request->provider;
        $domains_dir = uploads_path(630);

        $file = $request->file('import');
        $filename = $file->getClientOriginalName();
        if (! str_ends_with($filename, '.csv') && $provider == 'SRSPlus') {
            return json_alert('Please upload .csv file', 'error');
        }
        if (! str_ends_with($filename, '.txt') && str_contains($provider, 'ZACR')) {
            return json_alert('Please upload .txt file', 'error');
        }

        if (str_ends_with($filename, '.txt')) {
            $filename = str_replace('.txt', '.csv', $filename);
        }

        $uploadSuccess = $file->move($domains_dir, $filename);

        if (! $uploadSuccess) {
            return json_alert('Upload Failed!', 'error');
        }

        $records = (new FastExcel)->import($domains_dir.$filename);

        $records_count = count($records);

        $zacr_domains = $records->pluck('domain')->toArray();

        foreach ($zacr_domains as $domain) {
            $sub = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('detail', $domain)->count();
            if (! $sub) {
                try {
                    $info = zacr_domain_info($domain);

                    $delete = false;
                    foreach ($info['data']['domain:infData']['domain:status'] as $s) {
                        if (! empty($s['s']) && $s['s'] == 'pendingDelete') {
                            $delete = true;
                        }

                        if (! empty($s['@attributes']['s']) && ($s['@attributes']['s'] == 'pendingDelete' || $s['@attributes']['s'] == 'inactive')) {
                            $delete = true;
                        }

                        if (! empty($s['@content']) && $s['@content'] == 'PendingManualSuspension') {
                            $delete = true;
                        }
                    }
                    if ($delete) {
                        $zacr_expiring_domains[] = $domain;
                        $autorenew_count--;
                    } else {
                        $data = [
                            'account_id' => 0,
                            'domain' => $domain,
                            'provider' => 'zacr',
                            'domain_status' => 'Active',
                        ];
                        \DB::table('isp_host_websites')->insert($data);
                    }
                } catch (\Throwable $ex) {
                    exception_log($ex);
                }
            } else {
                \DB::table('isp_host_websites')->where('domain', $domain)->update(['domain_status' => 'Active']);
            }
        }

        return json_alert('Domains imported.');
    }

    public function pbxNumberImport(Request $request)
    {
        $user_id = get_user_id_default();
        $conn = $request->conn;

        if (empty($request->number_from)) {
            return json_alert('Number range start required.', 'error');
        }
        if (empty($request->number_to)) {
            return json_alert('Number range end required.', 'error');
        }
        $number_start = trim($request->number_from);
        $number_end = trim($request->number_to) + 1;
        $provider = $request->provider;
        if (empty($number_start)) {
            return json_alert('Number range start required.', 'error');
        }
        if (empty($number_end)) {
            return json_alert('Number range end required.', 'error');
        }
        if (empty($request->provider)) {
            return json_alert('Provider required.', 'error');
        }
        if (strlen($number_start) != 11 || ! preg_match('/[0-9]+/', $number_start) == true) {
            return json_alert('Invalid number range start.', 'error');
        }

        if (strlen($number_end) != 11 || ! preg_match('/[0-9]+/', $number_end) == true) {
            return json_alert('Invalid number range end.', 'error');
        }

        $valid_prefixes = \DB::connection('pbx')->table('p_phone_numbers')->pluck('prefix')->unique()->filter()->toArray();

        $number_start_prefix = substr($number_start, 0, 4);
        $number_start_prefix = str_replace('27', 0, $number_start_prefix);
        $number_end_prefix = substr($number_end, 0, 4);
        $number_end_prefix = str_replace('27', 0, $number_end_prefix);
        /*
        if(!in_array($number_start_prefix,$valid_prefixes)){
            return json_alert('Invalid number range start Prefix.', 'error');
        }
        if(!in_array($number_end_prefix,$valid_prefixes)){
            return json_alert('Invalid number range end Prefix.', 'error');
        }
        */

        if ($number_start_prefix != $number_end_prefix) {
            return json_alert('Number range start and end Prefix does not match.', 'error');
        }

        if (substr($number_start, 0, 4) == '2787' || substr($number_start, 0, 3) == '087') {
            $product_id = 127; // 087
        } else {
            if (str_starts_with($number_start, '2712786')) { // 012786
                $product_id = 176;
            } elseif (str_starts_with($number_start, '2710786')) { // 010786
                $product_id = 176;
            } else { // geo
                $product_id = 128;
            }
        }

        $product_code = \DB::table('crm_products')->where('id', $product_id)->pluck('code')->first();
        if ($number_start != $number_end) {
            while ($number_start != $number_end) {
                $exists = \DB::connection($conn)->table('p_phone_numbers')->where('number', $number_start)->count();
                if (! $exists) {
                    if ($conn == 'pbx') {
                        $uuid = pbx_uuid('p_phone_numbers', 'number_uuid');
                    } else {
                        $uuid = switch_uuid('p_phone_numbers', 'number_uuid');
                    }
                    $prefix = substr($number_start, 0, 4);
                    $prefix = str_replace('27', 0, $prefix);
                    $new_number = $number_start;
                    if ($gateway_uuid == '0d0d2b47-af57-4b02-80ff-5cb787c865c0') {
                        //VODACOM
                        $new_number = '+'.$number_start;
                    }
                    $data = [
                        'number_uuid' => $uuid,
                        'number' => $new_number,
                        'status' => 'Enabled',
                        'gateway_uuid' => $provider,
                        'prefix' => $prefix,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => $user_id,
                        'product_id' => $product_id,
                        'product_code' => $product_code,
                    ];
                    \DB::connection($conn)->table('p_phone_numbers')->insert($data);
                }
                $number_start++;
            }
        } elseif ($number_start == $number_end) {
            $exists = \DB::connection($conn)->table('p_phone_numbers')->where('number', $number_start)->count();
            if (! $exists) {
                if ($conn == 'pbx') {
                    $uuid = pbx_uuid('p_phone_numbers', 'number_uuid');
                } else {
                    $uuid = switch_uuid('p_phone_numbers', 'number_uuid');
                }
                $prefix = substr($number_start, 0, 4);
                $prefix = str_replace('27', 0, $prefix);
                $data = [
                    'number_uuid' => $uuid,
                    'number' => $number_start,
                    'status' => 'Enabled',
                    'gateway_uuid' => $provider,
                    'prefix' => $prefix,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $user_id,
                ];
                \DB::connection($conn)->table('p_phone_numbers')->insert($data);
            }
        }
        set_phone_number_product_codes();

        return json_alert('Numbers imported');
    }

    public function pbxNumberChange(Request $request)
    {
        $erp = new \DBEvent;
        $current_number = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
        $new_number = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->number)->get()->first();
        if (empty($current_number->domain_uuid)) {
            return json_alert('No account set.', 'warning');
        }
        $current_account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $current_number->domain_uuid)->pluck('account_id')->first();
        $subscription_exists = \DB::connection('default')->table('sub_services')->where('account_id', $current_account_id)
            ->where('detail', $current_number->number)->where('status', '!=', 'Deleted')->count();
        if (! $subscription_exists) {
            return json_alert('Subscription not found.', 'warning');
        }

        $new_subscription_exists = \DB::connection('default')->table('sub_services')
            ->where('detail', $new_number->number)->where('status', '!=', 'Deleted')->count();
        if ($new_subscription_exists) {
            return json_alert('Subscription already exists for new number.', 'warning');
        }

        $new_data = [
            'routing_type' => $current_number->routing_type,
            'number_routing' => $current_number->number_routing,
            'wholesale_ext' => $current_number->wholesale_ext,
            'domain_uuid' => $current_number->domain_uuid,
        ];

        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->number)->update($new_data);

        $old_data = [
            'routing_type' => null,
            'number_routing' => null,
            'domain_uuid' => null,
            'wholesale_ext' => 0,
        ];
        if (! empty($request->spam_number)) {
            $old_data['status'] = 'Disabled';
            $old_data['is_spam'] = 1;
        }
        \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->update($old_data);

        \DB::connection('default')->table('sub_services')->where('account_id', $current_account_id)
            ->where('detail', $current_number->number)->where('status', '!=', 'Deleted')->update(['detail' => $new_number->number]);

        $exts = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $current_number->domain_uuid)->where('outbound_caller_id_number', $current_number->number)->get();

        foreach ($exts as $e) {
            $ext_data = (array) $e;
            $ext_data['outbound_caller_id_number'] = $new_number->number;
            $erp->setTable('v_extensions')->save($ext_data);
        }
        $phone_data = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->number)->get()->first();
        $phone_data = (array) $phone_data;
        $erp->setTable('p_phone_numbers')->save($phone_data);
        $phone_data = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
        $phone_data = (array) $phone_data;
        $erp->setTable('p_phone_numbers')->save($phone_data);

        $data = [];
        $data['internal_function'] = 'number_change_notification';
        $data['old_number'] = $current_number->number;
        $data['new_number'] = $new_number->number;
        erp_process_notification($current_account_id, $data);

        return json_alert('Phone number updated.');
    }

    public function invoiceList(Request $request)
    {
        if ($request->isMethod('post')) {
            if (empty($request->password) || $request->password != 'acc786') {
                return json_alert('Invalid password', 'warning');
            } else {
                session(['invoice_list' => true]);

                return json_alert('Redirecting...', 'success', ['reload' => url('accountant_access')]);
            }
        }

        if (! empty($request->logout)) {
            session()->forget('invoice_list');

            return redirect()->to('/accountant_access');
        }

        if (empty(session('invoice_list')) || session('invoice_list') !== true) {
            $data['verify_auth'] = true;
            $data['menu_name'] = 'Documents - Login';

            return view('__app.components.pages.invoice_list', $data);
        }
        if (! empty(session('invoice_list')) && session('invoice_list') === true) {
            $data['verify_auth'] = false;
            $data['menu_name'] = 'Documents';

            $supplier_invoice_months = \DB::table('crm_supplier_documents')->selectRaw("DATE_FORMAT(docdate,'%Y-%m') as docmonth,DATE_FORMAT(docdate,'%Y%m') as docmonthkey")->where('invoice_file', '>', '')->orderby('docdate', 'desc')->groupBy('docmonth')->pluck('docmonth', 'docmonthkey')->toArray();

            $data['supplier_invoice_months'] = $supplier_invoice_months;
            $data['supplier_invoices'] = [];
            $data['supplier_accordion'] = [];
            foreach ($supplier_invoice_months as $key => $invoice_month) {
                $data['supplier_invoices'][$key] = \DB::table('crm_supplier_documents as sd')
                    ->select('sd.*', 's.company')
                    ->join('crm_suppliers as s', 's.id', '=', 'sd.supplier_id')
                    ->where('sd.invoice_file', '>', '')->where('sd.docdate', 'LIKE', $invoice_month.'%')
                    ->orderby('sd.docdate', 'desc')->orderby('sd.id', 'desc')->get();
            }

            $ledger_invoice_months = \DB::table('acc_general_journals')->selectRaw("DATE_FORMAT(docdate,'%Y-%m') as docmonth,DATE_FORMAT(docdate,'%Y%m') as docmonthkey")->where('invoice_file', '>', '')->orderby('docdate', 'desc')->groupBy('docmonth')->pluck('docmonth', 'docmonthkey')->toArray();

            $data['ledger_invoice_months'] = $ledger_invoice_months;
            $data['ledger_invoices'] = [];
            $data['ledger_accordion'] = [];
            foreach ($ledger_invoice_months as $key => $invoice_month) {
                $data['ledger_invoices'][$key] = \DB::table('acc_general_journals as gj')
                    ->select('gj.*', 'cla.name as credit_account', 'dla.name as debit_account')
                    ->join('acc_ledger_accounts as dla', 'dla.id', '=', 'gj.ledger_account_id')
                    ->where('gj.invoice_file', '>', '')
                    ->where('gj.docdate', 'LIKE', $invoice_month.'%')
                    ->orderby('gj.docdate', 'desc')->orderby('gj.id', 'desc')->get();
            }

            return view('__app.components.pages.invoice_list', $data);
        }
    }

    public function mailUnsubscribe(Request $request, $encoded_link = null)
    {
        if ($request->isMethod('post')) {
            $link_data = \Erp::decode($encoded_link);

            $unsubscribe_note = '';
            if ($request->unsubscribereason == 'nolongerwant') {
                $unsubscribe_note = 'I no longer wish to receive your emails';
            }
            if ($request->unsubscribereason == 'irrelevantcontent') {
                $unsubscribe_note = 'Content is not relevant or interesting';
            }
            if ($request->unsubscribereason == 'toofrequent') {
                $unsubscribe_note = 'You are sending too frequently';
            }

            if (! empty($request->unsubscribereasonnotes)) {
                $unsubscribe_note .= ' - '.$request->unsubscribereasonnotes;
            }

            if (! empty($link_data['account_id'])) {
                \DB::table('erp_mail_queue')->where('account_id', $link_data['account_id'])->where('processed', 0)->delete();
                \DB::table('crm_accounts')->where('id', $link_data['account_id'])->update(['newsletter' => 0, 'unsubscribe_note' => $unsubscribe_note]);
            }

            return json_alert('You have unsubscribed from the mailing list.', 'success', ['reload' => url('/')]);
        } else {
            $link_data = \Erp::decode($encoded_link);

            return view('__app.components.pages.unsubscribe', ['encoded_link' => $encoded_link]);
        }
    }

    public function webForm(Request $request, $encoded_link = null)
    {
        // redirect invalid link
        if (empty(session('webform_module_id')) && ! $encoded_link) {
            return redirect()->to('/');
        }

        // decode the link
        if ($encoded_link) {
            $link_data = \Erp::decode($encoded_link);
            aa($link_data);
            if (empty($link_data['module_id'])) {
                return redirect()->to('/');
            }
            $valid_module = \DB::table('erp_cruds')->where('id', $link_data['module_id'])->where('public_access', 1)->count();
            if (! $valid_module) {
                return redirect()->to('/');
            }
            session(['webform_data' => $link_data]);
            session(['webform_module_id' => $link_data['module_id']]);

            if (! empty($link_data['account_id'])) {
                session(['webform_account_id' => $link_data['account_id']]);
            }
            if (! empty($link_data['is_contract'])) {
                session(['webform_is_contract' => $link_data['is_contract']]);
            }

            if (! empty($link_data['subscription_id'])) {
                session(['webform_subscription_id' => $link_data['subscription_id']]);
            }

            if (! empty($link_data['id'])) {
                session(['webform_id' => $link_data['id']]);
            }

            //   return redirect()->to('/webform');
            // }

            // set request data and call controller
            if (! empty(session('webform_module_id'))) {
                $menu_name = get_menu_url(session('webform_module_id'));

                $request_data = new \Illuminate\Http\Request;
                $url = $menu_name.'/edit';
                if (! empty(session('webform_id'))) {
                    $url .= '/'.session('webform_id');
                }
                if (! empty($link_data['account_id'])) {
                    $url .= '?account_id='.$link_data['account_id'];
                }

                $request_data->server->set('REQUEST_URI', $url);

                return app(\App\Http\Controllers\ModuleController::class)->getEdit($request_data);
            }
        }
    }

    public function checkFail2Ban(Request $request)
    {
        if (empty($request->ip_address)) {
            return json_alert('IP Address required.', 'warning');
        }
        $pbx = new \FusionPBX;
        /*
        $result = $pbx->unblockIP($request->ip_address);
        $blocked = $pbx->checkBlockedIP($request->ip_address);

        if (!$blocked) {
            return json_alert('Your IP is not being blocked.');
        }
        */
        $result = $pbx->unblockIP($request->ip_address);

        $blocked = $pbx->checkBlockedIP($request->ip_address);
        $account = dbgetaccount($request->account_id);
        $domain = $account->pabx_domain;

        if ($account->partner_id != 1) {
            $account = dbgetaccount($account->partner_id);
        }

        $data['internal_function'] = 'pbx_ip_unblock';
        if ($blocked) {
            $data['unblock_msg'] = 'IP address '.$request->ip_address.' for '.$domain.' could not be unblocked.<br> Please update your extension passwords and try again.';
        } else {
            $data['unblock_msg'] = 'IP address '.$request->ip_address.' for '.$domain.' is unblocked.<br> Please update your extension passwords to prevent it from being blocked again.';
        }

        //if($account->partner_id != 1){
        //    erp_process_notification($account->partner_id,$data);
        //}else{
        //    erp_process_notification($account->id,$data);
        //}

        if ($blocked) {
            return json_alert('Your IP is being blocked. Please confirm your extension details.', 'warning', ['blocked' => $blocked]);
        } else {
            return json_alert('Your IP is not being blocked.');
        }
    }

    public function cdrExport(Request $request)
    {
        if (empty($request->export_date)) {
            return json_alert('Select export month.', 'warning');
        }
        if (empty(session('pbx_account_id')) || session('pbx_account_id') == 1) {
            return json_alert('Switch to customer account to export cdr.', 'warning');
        }
        $file_name = export_cdr($request->connection, session('pbx_account_id'), $request->export_date);

        return json_alert(attachments_url().$file_name, 'reload');
    }

    public function checkAxxessLteCoverage(Request $request)
    {
        header('Access-Control-Allow-Origin: *');
        if (empty($request->addressinput) || empty($request->latlonginput)) {
            return json_alert('<b>Address required</b>', 'error');
        }

        $verified_latlong = get_lat_long($request->addressinput);
        if ($verified_latlong == 'API Key Error') {
            return json_alert('<b>API Key Error</b>', 'error');
        }

        if ($verified_latlong == ',') {
            $verified_latlong = '0,0';
        }

        if ($verified_latlong != $request->latlonginput) {
            $request->latlonginput = $verified_latlong;
        }
        $latlong = explode(',', $request->latlonginput);
        $mapdata = ['lat' => $latlong[0], 'long' => $latlong[1]];
        if ($request->latlonginput == '0,0') {
            return json_alert('<b>LTE coverage not available for this location. Invalid co-ordinates</b>', 'error', $mapdata);
        }
        $latlong = $request->latlonginput;
        $address = $request->addressinput;
        $latlong_arr = explode(',', $latlong);

        $axxess = new \Axxess;
        //$axxess = $axxess->setDebug();
        if ($request->provider == 'mtn') {
            $available = $axxess->checkMtnFixedLteAvailability($latlong_arr[0], $latlong_arr[1], $address, $request->bbox, $request->width, $request->height, $request->ico, $request->jco);
        }
        if ($request->provider == 'telkom') {
            $available = $axxess->checkTelkomLteAvailability($latlong_arr[0], $latlong_arr[1], $address);
        }
        if (empty($available) || $available->intCode != 200 || empty($available->arrAvailableProvidersGuids) || (is_array($available->arrAvailableProvidersGuids) && count($available->arrAvailableProvidersGuids) == 0)) {
            return json_alert('<b>'.$available->strMessage.'</b>', 'error', $mapdata);
        }

        return json_alert('<b>'.$available->strMessage.'</b>', 'success', $mapdata);
    }

    public function checkFibreCoverage(Request $request)
    {
        header('Access-Control-Allow-Origin: *');
        if (empty($request->addressinput) || empty($request->latlonginput)) {
            return json_alert('<b>Address required</b>', 'error');
        }

        $verified_latlong = get_lat_long($request->addressinput);
        if ($verified_latlong == 'API Key Error') {
            return json_alert('<b>API Key Error</b>', 'error');
        }

        if ($verified_latlong == ',') {
            $verified_latlong = '0,0';
        }

        if ($verified_latlong != $request->latlonginput) {
            $request->latlonginput = $verified_latlong;
        }
        $latlong = explode(',', $request->latlonginput);
        $mapdata = ['lat' => $latlong[0], 'long' => $latlong[1]];
        if ($request->latlonginput == '0,0') {
            return json_alert('<b>No fibre available for this location. Invalid co-ordinates</b>', 'error', $mapdata);
        }
        $latlong = $request->latlonginput;
        $address = $request->addressinput;
        $latlong_arr = explode(',', $latlong);

        $axxess = new \Axxess;
        //$axxess = $axxess->setDebug();
        $available = $axxess->checkFibreAvailability($latlong_arr[0], $latlong_arr[1], $address);
        if (empty($available) || $available->intCode != 200 || empty($available->arrAvailableProvidersGuids) || (is_array($available->arrAvailableProvidersGuids) && count($available->arrAvailableProvidersGuids) == 0)) {
            return json_alert('<b>A fibre provider is not available for this location.</b>', 'error', $mapdata);
        }

        $available_products = '';
        $available_products_arr = [];
        $show_provider = false;

        $processed_providers = [];

        foreach ($available->arrAvailableProvidersGuids as $provider) {
            if (! empty($provider->guidNetworkProviderId)) {
                $preorder = '';

                if ($provider->intPreOrder == 0) {
                    $provider_name = \DB::table('isp_data_products')
                        ->where('guidNetworkProviderId', $provider->guidNetworkProviderId)
                        ->pluck('provider')
                        ->first();
                    $available_products_arr = \DB::table('isp_data_products')
                        ->where('guidNetworkProviderId', $provider->guidNetworkProviderId)
                        ->where('product_id', '!=', 0)
                        ->where('status', 'Enabled')
                        ->orderBy('download_speed', 'asc')
                        ->get();
                }

                if (! in_array($provider_name, $processed_providers)) {
                    $processed_providers[] = $provider_name;
                    if (count($available_products_arr) > 0) {
                        $show_provider = true;
                        $available_products .= '<br><br><b>Provider: '.ucfirst($provider_name).$preorder.'</b>';
                    }

                    foreach ($available_products_arr as $ap) {
                        $product_name = $ap->product;
                        $product_name = str_ireplace($provider_name.' ', ' ', $product_name);

                        $available_products .= '<div style="color: #000;background-color: #f7f7f7;padding: 10px; border-radius: 5px;margin-top: 10px;">
                        <b>'.$product_name.'</b>
                        </div>
                        ';
                    }
                }
            }
        }

        if (! empty($show_provider)) {
            if (! empty($request->reseller) && $request->reseller == 1) {
                return json_alert('<b>Fibre products available for this location</b>', 'success', $mapdata);
            } else {
                return json_alert('<b>Fibre products available for this location</b>: '.$available_products, 'success', $mapdata);
            }
        } else {
            return json_alert('<b>A fibre provider is not available for this location.</b>', 'error', $mapdata);
        }
    }

    public function debitOrderCreate(Request $request)
    {
        $action_date_arr = explode('00:', $request->action_date);
        $action_date = date('Ymd', strtotime($action_date_arr[0]));

        $batch_date = date('YmdHi');
        $netcash = new \NetCash($batch_date);
        $netcash->setActionDate($action_date);
        if ($request->limit_id) {
            $netcash->setLimitID($request->limit_id);
            $netcash->setActionDate($action_date, $request->instruction);
        } else {
            $netcash->setActionDate($action_date, $request->instruction);
        }
        $storage_file = date('YmdHi').'.txt';
        $total = $netcash->generate($storage_file);
        $batch = \Storage::disk('debit_orders')->get($storage_file);
        $batch = [
            'batch' => $batch,
            'batch_file' => $storage_file,
            'action_date' => $action_date,
            'created_at' => date('Y-m-d H:i:s', strtotime($batch_date)),
            'total' => $total / 100,
            'limit_account_id' => $request->limit_id,
        ];
        $debit_order_id = \DB::table('acc_debit_order_batch')->insertGetId($batch);

        return json_alert('debit_order_batch?id='.$debit_order_id, 'reload');
    }

    public function debitOrderUpload(Request $request)
    {
        $id = $request->debit_order_id;
        $batch = \DB::table('acc_debit_order_batch')->where('id', $id)->get()->first();
        if ($batch->uploaded) {
            return json_alert('Batch already uploaded.', 'warning');
        }
        $batch_name_arr = explode('.', $batch->batch_file);
        $batch_name = $batch_name_arr[0];
        $netcash = new \NetCash($batch_name);
        $netcash->upload();
        $result_file = $batch_name.'result.txt';
        $result_token = \Storage::disk('debit_orders')->get($result_file);
        $update = [
            'result_file' => $result_file,
            'result_token' => $result_token,
            'uploaded' => 1,
        ];

        \DB::table('acc_debit_order_batch')->where('id', $id)->update($update);

        return json_alert('Batch uploaded.');
    }

    public function debitOrderReport(Request $request)
    {
        $id = $request->debit_order_id;
        $batch = \DB::table('acc_debit_order_batch')->where('id', $id)->get()->first();
        if (! empty($batch->result) && $batch->result != 'FILE NOT READY') {
            return json_alert('Batch report already generated.', 'warning');
        }
        $batch_name_arr = explode('.', $batch->batch_file);
        $batch_name = $batch_name_arr[0];
        $netcash = new \NetCash($batch_name);
        $result = $netcash->report();

        \DB::table('acc_debit_order_batch')->where('id', $id)->update(['result' => $result->RequestFileUploadReportResult]);
        $batch = \DB::table('acc_debit_order_batch')->where('id', $id)->get()->first();
        if (str_contains($batch->result, 'file not ready')) {
            return json_alert('File not ready.', 'warning');
        }

        if (str_contains($batch->result, 'Action date is not valid for this instruction')) {
            return json_alert('Action date is not valid for this instruction.', 'warning');
        }
        if (str_contains($batch->result, '#ERROR')) {
            return json_alert('Invalid Batch.', 'warning');
        }

        if (str_contains($batch->result, 'SUCCESSFUL') && ! str_contains($batch->result, 'UNSUCCESSFUL')) {
            $complete = $netcash->createTransactions($batch->id);
            if ($complete) {
                return json_alert('Transactions Created.');
            } else {
                return json_alert('Transactions already created.', 'warning');
            }
        }

        return json_alert('Batch report updated.');
    }

    public function documentPopup(Request $request, $id)
    {
        $doc = \DB::table('crm_documents')->where('id', $id)->get()->first();
        $product_ids = \DB::table('crm_document_lines')->where('document_id', $id)->pluck('product_id')->toArray();
        $num_products = \DB::table('crm_products')->whereIn('id', $product_ids)->where('type', 'Stock')->count();
        if ($num_products > 0) {
            $data['show_delivery'] = true;
        } else {
            $data['show_delivery'] = false;
        }
        $account = dbgetaccount($doc->account_id);

        $data['doc'] = $doc;
        $data['address'] = $account->address;
        $data['paynow_link'] = generate_paynow_link($doc->account_id, $doc->total, true);
        $data['delivery_options'] = collect(['Collection', 'Delivery']);
        $data['documents_url'] = get_menu_url_from_table('crm_documents');

        return view('__app.components.pages.document_popup', $data);
    }

    public function updateDocDelivery(Request $request)
    {
        \DB::table('crm_accounts')->where('id', $request->account_id)->update(['address' => $request->address]);
        \DB::table('crm_documents')->where('id', $request->doc_id)->update(['delivery' => $request->delivery]);

        return json_alert('Saved');
    }

    public function interworxEmail(Request $request)
    {
        $instance = \DB::table('erp_instances')->where('id', 1)->get()->first();
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
        session(['instance' => $instance]);
        $email_data = [];
        if ($request->type == 'email') {
            $account_id = \DB::table('isp_host_websites')->where('domain', $request->domain)->pluck('account_id')->first();
            if ($account_id) {
                $email_data['email_address'] = $request->username.'@'.$request->domain;
                $email_data['password'] = $request->password;
                $email_data['domain'] = $request->domain;
                //$email_data['test_debug'] = 1;
                $email_data['internal_function'] = 'send_interworx_email_details';

                erp_process_notification($account_id, $email_data, []);
            }
        }

        if ($request->type == 'ftp') {
            $account_id = \DB::table('isp_host_websites')->where('domain', $request->domain)->pluck('account_id')->first();
            if ($account_id) {
                // $email_data['test_debug'] = 1;
                $email_data['ftp'] = $request->user.'@'.$request->domain;
                $email_data['password'] = $request->password;
                $email_data['domain'] = $request->domain;
                $function_variables = get_defined_vars();
                $email_data['internal_function'] = 'send_interworx_ftp_details';
                erp_process_notification($account_id, $email_data, []);
            }
        }
    }

    public function payfastNetstreamSignupForm(Request $request)
    {
        try {
            $post_data = (object) $request->all();

            // VALIDATE FORM
            $error = '';
            if (empty($_POST['email'])) {
                $error = 'Email required';
            }
            if (empty($_POST['name_first'])) {
                $error = 'First Name required';
            }
            if (empty($_POST['name_last'])) {
                $error = 'Last Name required';
            }
            if (empty($_POST['product_id'])) {
                $error = 'Product required';
            }

            // PRODUCTS
            $products = \DB::table('crm_products')
                ->select('crm_products.id', 'crm_products.name', 'crm_products.description', 'crm_pricelist_items.price_tax')
                ->join('crm_pricelist_items', 'crm_pricelist_items.product_id', '=', 'crm_products.id')
                ->where('crm_products.code', 'like', 'netstream%')
                ->where('crm_products.status', '!=', 'Deleted')
                ->where('crm_pricelist_items.pricelist_id', 1)
                ->get();

            $selected_product = false;
            foreach ($products as $product) {
                if ($product->id == $_POST['product_id']) {
                    $selected_product = $product;
                }
            }
            if (! $selected_product) {
                $error = 'Invalid Product selected';
            }

            if ($error > '') {
                return $error;
            }

            $payfast_subscription = new PayfastSubscription;
            $payfast_subscription->setDebug(1);
            $form = $payfast_subscription->getSignupForm($selected_product, $_POST['name_first'], $_POST['name_last']);
            if (! $form) {
                return 'Unexpected Error, please try again later.';
            }

            return $form;
        } catch (\Throwable $ex) {
            exception_email('Payfast signup form error', $ex);

            return 'Unexpected Error, please try again later.';
        }
    }

    //// CREATE ACCOUNT, INVOICE, SUBSCRIPTION, PAYFAST SUBSCRIPTION
    public function payfastSignupSubscriptionResponse(Request $request)
    {
        // payfast_subscription_signup_notify

        $post_data = (object) $request->all();
        /*
        try {
            $post_data = (object) $request->all();

            // save token to db
            $exists = \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->count();
            if (!$exists) {
                $subscription_data = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'token' => $request->token,
                    'account_id' => $request->custom_int1,
                    'status' => 'Enabled'
                ];
                \DB::table('acc_payfast_subscriptions')->insert($subscription_data);
            }


            $created_at = date('Y-m-d H:i:s');
            $db = new \DBEvent();
            $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();

            $reference = $request->m_payment_id."_".$request->pf_payment_id;
            $account_id = $request->custom_int1;
            $amount = $request->amount_gross;

            if (empty($account_id)) {
                throw new \ErrorException("account not found.".$reference);
            }

            $account = dbgetaccount($account_id);
            if ($account->partner_id != 1) {
                return true; // reseller users payments
            }

            if ($post_data->payment_status != "COMPLETE") {
                throw new \ErrorException("payment_status is not Approved.".$post_data->payment_status);
            }
            if ($account_id == 12) {
                throw new \ErrorException("PayFast response is for demo, cloudtelecoms customer account.");
            }


            $payment_data = [
                'docdate' => date('Y-m-d', strtotime($created_at)),
                'total' => $amount,
                'reference' => $reference,
                'account_id' => $account_id,
                'source' => 'PayFast'
            ];

            if ($account->currency == 'USD') {
                $payment_data['total'] = convert_currency_zar_to_usd($payment_data['total']);
                $payment_data['document_currency'] = 'USD';
            }


            $pre_payment_exists = \DB::table('acc_cashbook_transactions')->where('reference', $reference)->count();

            if (!$pre_payment_exists) {


                if (isset($post_data->amount_fee)) {
                    $fee_data = [
                        'ledger_account_id' => 22,
                        'cashbook_id' => $cashbook->id,
                        'total' => abs($post_data->amount_fee),
                        'api_id' => $post_data->pf_payment_id,
                        'reference' => 'Payfast Fee '.$post_data->pf_payment_id,
                        'api_status' => 'Complete',
                        'doctype' => 'Cashbook Control Payment',
                        'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    ];

                    $fee_result = $db->setTable('acc_cashbook_transactions')->save($fee_data);

                    if (!is_array($fee_result) || empty($fee_result['id'])) {

                        throw new \ErrorException("Error inserting Payfast Fee into journals.".json_encode($fee_result));
                    }
                }

                $api_data = [
                    'api_status' => 'Complete',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'doctype' => 'Cashbook Customer Receipt',
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                ];

                $result = $db->setTable('acc_cashbook_transactions')->save($api_data);

                if (!is_array($result) || empty($result['id'])) {

                    if ($fee_result['id']) {
                        $db->setTable('acc_general_journals')->deleteRecord(['id' => $fee_result['id']]);
                    }
                    throw new \ErrorException("Error inserting to acc_cashbook_transactions.".json_encode($result));
                }

                $subscription_data = [
                    'last_billed_time' => date('Y-m-d H:i:s'),
                    'last_bill_amount' => $request->amount_gross,
                    'last_billed_status' => 'Completed',
                    'api_id' => $post_data->pf_payment_id,
                ];
                \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->update($subscription_data);
                \DB::table('acc_debit_orders')->where('account_id', $account_id)->update(['status' => 'Deleted']);
            }
        } catch (\Throwable $ex) {  exception_log($ex);
            try {
                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                    'api_error' => $ex->getMessage()
                ];
                foreach ($api_data as $k => $v) {
                    if (empty($v)) {
                        $api_data[$k] = 0;
                    }
                }
                dbinsert('acc_cashbook_transactions', $api_data);

                $subscription_data = [
                    'status' => $post_data->payment_status,
                    'last_billed_time' => date('Y-m-d H:i:s'),
                    'last_bill_amount' => $request->amount_gross,
                    'last_billed_status' => 'Failed',
                    'last_payment_id' => 0,
                    'bill_amount' => 0,
                    'api_id' => $post_data->pf_payment_id,
                    'error' => $ex->getMessage()
                ];
                \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->update($subscription_data);

                if (!str_contains($ex->getMessage(), 'OrderStatus is not Approved')) {
                    exception_email($ex, 'PayFast response error '.date('Y-m-d H:i:s'));
                }
            } catch (\Throwable $err) {
                if (empty($account_id)) {
                    $account_id = 0;
                }

                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => '',
                    'total' => 0,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s'),
                    'api_data' => serialize($post_data),
                    'api_id' => 0,
                    'api_error' => $err->getMessage()
                ];

                $subscription_data = [
                    'last_billed_time' => date('Y-m-d H:i:s'),
                    'last_bill_amount' => $request->amount_gross,
                    'last_billed_status' => 'Failed',
                    'last_payment_id' => 0,
                    'api_id' => $post_data->pf_payment_id,
                    'error' => $err->getMessage()
                ];
                \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->update($subscription_data);

                exception_email($err, 'PayFast response error '.date('Y-m-d H:i:s'));
                dbinsert('acc_cashbook_transactions', $api_data);
            }
        }
        */
    }

    //// PAYMENTS
    //  Sandbox
    // Merchant ID: 10004002
    // Merchant Key: q1cd2rdny4a53
    // Passphase: payfast

    public function payfastSubscriptionResponse(Request $request)
    {
        try {
            $post_data = (object) $request->all();

            // save token to db
            $exists = \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->count();
            if (! $exists) {
                $subscription_data = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'token' => $request->token,
                    'account_id' => $request->custom_int1,
                    'status' => 'Enabled',
                ];
                \DB::table('acc_payfast_subscriptions')->insert($subscription_data);
            }

            $created_at = date('Y-m-d H:i:s');
            $db = new \DBEvent;
            $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();

            $reference = $request->m_payment_id.'_'.$request->pf_payment_id;
            $account_id = $request->custom_int1;
            $amount = $request->amount_gross;

            if (empty($account_id)) {
                throw new \ErrorException('account not found.'.$reference);
            }

            $account = dbgetaccount($account_id);
            if ($account->partner_id != 1) {
                return true; // reseller users payments
            }

            if ($post_data->payment_status != 'COMPLETE') {
                throw new \ErrorException('payment_status is not Approved.'.$post_data->payment_status);
            }
            if ($account_id == 12) {
                throw new \ErrorException('PayFast response is for demo, cloudtelecoms customer account.');
            }

            $payment_data = [
                'docdate' => date('Y-m-d', strtotime($created_at)),
                'total' => $amount,
                'reference' => $reference,
                'account_id' => $account_id,
                'source' => 'PayFast',
            ];

            if ($account->currency == 'USD') {
                $payment_data['total'] = convert_currency_zar_to_usd($payment_data['total']);
                $payment_data['document_currency'] = 'USD';
            }

            $pre_payment_exists = \DB::table('acc_cashbook_transactions')->where('reference', $reference)->count();

            if (! $pre_payment_exists) {
                if (isset($post_data->amount_fee)) {
                    $fee_data = [
                        'ledger_account_id' => 22,
                        'cashbook_id' => $cashbook->id,
                        'total' => abs($post_data->amount_fee),
                        'api_id' => $post_data->pf_payment_id,
                        'reference' => 'Payfast Fee '.$post_data->pf_payment_id,
                        'api_status' => 'Complete',
                        'doctype' => 'Cashbook Control Payment',
                        'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    ];

                    $fee_result = $db->setTable('acc_cashbook_transactions')->save($fee_data);

                    if (! is_array($fee_result) || empty($fee_result['id'])) {
                        throw new \ErrorException('Error inserting Payfast Fee into journals.'.json_encode($fee_result));
                    }
                }

                $api_data = [
                    'api_status' => 'Complete',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'doctype' => 'Cashbook Customer Receipt',
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                ];

                $result = $db->setTable('acc_cashbook_transactions')->save($api_data);

                if (! is_array($result) || empty($result['id'])) {
                    if ($fee_result['id']) {
                        $db->setTable('acc_general_journals')->deleteRecord(['id' => $fee_result['id']]);
                    }
                    throw new \ErrorException('Error inserting to acc_cashbook_transactions.'.json_encode($result));
                }

                $subscription_data = [
                    'last_billed_time' => date('Y-m-d H:i:s'),
                    'last_bill_amount' => $request->amount_gross,
                    'last_billed_status' => 'Completed',
                    'api_id' => $post_data->pf_payment_id,
                ];
                \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->update($subscription_data);
                \DB::table('acc_debit_orders')->where('account_id', $account_id)->update(['status' => 'Deleted']);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            try {
                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                    'api_error' => $ex->getMessage(),
                ];
                foreach ($api_data as $k => $v) {
                    if (empty($v)) {
                        $api_data[$k] = 0;
                    }
                }
                dbinsert('acc_cashbook_transactions', $api_data);

                $subscription_data = [
                    'status' => $post_data->payment_status,
                    'last_billed_time' => date('Y-m-d H:i:s'),
                    'last_bill_amount' => $request->amount_gross,
                    'last_billed_status' => 'Failed',
                    'last_payment_id' => 0,
                    'bill_amount' => 0,
                    'api_id' => $post_data->pf_payment_id,
                    'error' => $ex->getMessage(),
                ];
                \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->update($subscription_data);

                if (! str_contains($ex->getMessage(), 'OrderStatus is not Approved')) {
                    exception_email($ex, 'PayFast response error '.date('Y-m-d H:i:s'));
                }
            } catch (\Throwable $err) {
                if (empty($account_id)) {
                    $account_id = 0;
                }

                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => '',
                    'total' => 0,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s'),
                    'api_data' => serialize($post_data),
                    'api_id' => 0,
                    'api_error' => $err->getMessage(),
                ];

                $subscription_data = [
                    'last_billed_time' => date('Y-m-d H:i:s'),
                    'last_bill_amount' => $request->amount_gross,
                    'last_billed_status' => 'Failed',
                    'last_payment_id' => 0,
                    'api_id' => $post_data->pf_payment_id,
                    'error' => $err->getMessage(),
                ];
                \DB::table('acc_payfast_subscriptions')->where('token', $request->token)->update($subscription_data);

                exception_email($err, 'PayFast response error '.date('Y-m-d H:i:s'));
                dbinsert('acc_cashbook_transactions', $api_data);
            }
        }

        // https://www.payfast.co.za/fees
        /*
        can charge amount_fee directly to bank charges
        need to check if flat withdrawal fee is listed on the bank or deducted from withdrawal
        */
    }

    public function stripeWebhookTestMode(Request $request)
    {
        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = 'whsec_aj5YGNyBKfjXiEaPvdHtjOW1sa0l2Usr';

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
            // Handle the event
            if ($event->type == 'checkout.session.completed' || $event->type == 'checkout.session.async_payment_succeeded') {
                // check if using testing api
                if (! $event->livemode) {
                    // check payment success
                    if ($event->data->object->payment_status == 'paid' &&
                    $event->data->object->status == 'complete' &&
                    $event->data->object->mode == 'payment') {
                        $amount_cents = $event->data->object->amount_total;
                        $amount = $amount_cents / 100;
                        $currency = $event->data->object->currency;
                        $account_id = $event->data->object->client_reference_id;
                        $stripe_id = $event->data->object->payment_intent;
                        $api_data = json_encode($event->data->object);

                        $exists = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 13)->where('stripe_id', $stripe_id)->count();
                        if (! $exists) {
                            $account = dbgetaccount($account_id);
                            if ($account && $account->id) {
                                $payment_data = [
                                    'docdate' => date('Y-m-d'),
                                    'cashbook_id' => 13,
                                    'doctype' => 'Cashbook Customer Receipt',
                                    'account_id' => $account_id,
                                    'document_currency' => strtoupper($currency),
                                    'total' => $amount,
                                    'stripe_id' => $stripe_id,
                                    'api_data' => $api_data,
                                    'approved' => 0,
                                    'reference' => 'Stripe Payment '.$stripe_id,
                                    'api_status' => 'Complete',
                                ];
                                $db = new \DBEvent;
                                $db->setTable('acc_cashbook_transactions')->save($payment_data);
                            }
                        }
                    }
                }
            } else {
            }
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        http_response_code(200);
    }

    public function stripeWebhook(Request $request)
    {
        $endpoint_secret = 'whsec_uhxXw8WPAtLupbf5IlKH8aD3A21uVXq2';

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
            // Handle the event
            if ($event->type == 'checkout.session.completed' || $event->type == 'checkout.session.async_payment_succeeded') {
                // check if using testing api
                if ($event->livemode) {
                    // check payment success
                    if ($event->data->object->payment_status == 'paid' &&
                    $event->data->object->status == 'complete' &&
                    $event->data->object->mode == 'payment') {
                        $amount_cents = $event->data->object->amount_total;
                        $amount = $amount_cents / 100;
                        $currency = $event->data->object->currency;
                        $account_id = $event->data->object->client_reference_id;
                        $stripe_id = $event->data->object->payment_intent;
                        $api_data = json_encode($event->data->object);
                        $exists = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 13)->where('stripe_id', $stripe_id)->count();
                        if (! $exists) {
                            $account = dbgetaccount($account_id);
                            if ($account && $account->id) {
                                $payment_data = [
                                    'docdate' => date('Y-m-d'),
                                    'cashbook_id' => 13,
                                    'doctype' => 'Cashbook Customer Receipt',
                                    'account_id' => $account_id,
                                    'document_currency' => strtoupper($currency),
                                    'total' => $amount,
                                    'stripe_id' => $stripe_id,
                                    'api_data' => $api_data,
                                    'approved' => 0,
                                    'reference' => 'Stripe Payment '.$stripe_id,
                                    'api_status' => 'Complete',
                                ];
                                $db = new \DBEvent;
                                $db->setTable('acc_cashbook_transactions')->save($payment_data);
                            }
                        }
                    }
                }
            } else {
            }
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        http_response_code(200);
    }

    public function payfastResponse(Request $request)
    {
        try {
            $post_data = (object) $request->all();
            $created_at = date('Y-m-d H:i:s');
            $db = new \DBEvent;
            $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();

            $reference = $request->m_payment_id;
            $account_id = $request->custom_int1;
            $amount = $request->amount_gross;

            if (empty($account_id)) {
                throw new \ErrorException('account not found.'.$reference);
            }

            $account = dbgetaccount($account_id);
            if ($account->partner_id != 1) {
                return true; // reseller users payments
            }

            if ($post_data->payment_status != 'COMPLETE') {
                throw new \ErrorException('payment_status is not Approved.'.$post_data->payment_status);
            }
            if ($account_id == 12) {
                //  throw new \ErrorException("PayFast response is for demo, cloudtelecoms customer account.");
            }

            $payment_data = [
                'docdate' => date('Y-m-d', strtotime($created_at)),
                'total' => $amount,
                'reference' => $reference,
                'account_id' => $account_id,
                'source' => 'PayFast',
            ];

            if ($account->currency == 'USD') {
                $payment_data['total'] = convert_currency_zar_to_usd($payment_data['total']);
                $payment_data['document_currency'] = 'USD';
            }

            $pre_payment_exists = \DB::table('acc_cashbook_transactions')->where('reference', $reference)->count();

            if (! $pre_payment_exists) {
                if (isset($post_data->amount_fee)) {
                    $fee_data = [
                        'ledger_account_id' => 22,
                        'cashbook_id' => $cashbook->id,
                        'total' => abs($post_data->amount_fee),
                        'api_id' => $post_data->pf_payment_id,
                        'reference' => 'Payfast Fee '.$post_data->pf_payment_id,
                        'api_status' => 'Complete',
                        'doctype' => 'Cashbook Control Payment',
                        'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    ];

                    $fee_result = $db->setTable('acc_cashbook_transactions')->save($fee_data);
                    if (! is_array($fee_result) || empty($fee_result['id'])) {
                        throw new \ErrorException('Error inserting Payfast Fee into journals.'.json_encode($fee_result));
                    }
                }

                $api_data = [
                    'api_status' => 'Complete',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'doctype' => 'Cashbook Customer Receipt',
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                ];

                $result = $db->setTable('acc_cashbook_transactions')->save($api_data);

                if (! is_array($result) || empty($result['id'])) {
                    if ($fee_result['id']) {
                        $db->setTable('acc_general_journals')->deleteRecord(['id' => $fee_result['id']]);
                    }
                    throw new \ErrorException('Error inserting to acc_cashbook_transactions.'.json_encode($result));
                }
            }
            $is_vehicledb = false;
            $currenturl = $request->url();
            if (str_contains($currenturl, 'vehicledb') || session('instance')->directory == 'vehicledb') {
                $is_vehicledb = true;
            }
            if ($is_vehicledb && ! empty($request->custom_int2)) {
                $result = create_vehicledb_invoice($account_id, $amount, $request->custom_int2, 'Credits purchased - Payfast');
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            try {
                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                    'api_error' => $ex->getMessage(),
                ];

                foreach ($api_data as $k => $v) {
                    if (empty($v)) {
                        $api_data[$k] = 0;
                    }
                }
                dbinsert('acc_cashbook_transactions', $api_data);

                if (! str_contains($ex->getMessage(), 'OrderStatus is not Approved')) {
                    exception_email($ex, 'PayFast response error '.date('Y-m-d H:i:s'));
                }
            } catch (\Throwable $err) {
                if (empty($account_id)) {
                    $account_id = 0;
                }

                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => '',
                    'total' => 0,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s'),
                    'api_data' => serialize($post_data),
                    'api_id' => 0,
                    'api_error' => $err->getMessage(),
                ];

                exception_email($err, 'PayFast response error '.date('Y-m-d H:i:s'));
                dbinsert('acc_cashbook_transactions', $api_data);
            }
        }

        // https://www.payfast.co.za/fees
        /*
        can charge amount_fee directly to bank charges
        need to check if flat withdrawal fee is listed on the bank or deducted from withdrawal
        */
    }

    public function appleResponse(Request $request)
    {

        $apiKey = $request->header('key');

        if ($apiKey !== 'opFldG94bgQudpVyciFpbG9gLp3vo5') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $post_data = (object) $request->all();

            $created_at = date('Y-m-d H:i:s');
            $db = new \DBEvent;
            $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();

            $reference = $request->m_payment_id;
            $account_id = $request->custom_int1;
            $amount = $request->amount_gross;

            if (empty($account_id)) {
                throw new \ErrorException('account not found.'.$reference);
            }

            $account = dbgetaccount($account_id);

            if ($account->partner_id != 1) {

                return true; // reseller users payments
            }

            if ($post_data->payment_status != 'COMPLETE') {
                throw new \ErrorException('payment_status is not Approved.'.$post_data->payment_status);
            }

            $payment_data = [
                'docdate' => date('Y-m-d', strtotime($created_at)),
                'total' => $amount,
                'reference' => $reference,
                'account_id' => $account_id,
                'source' => 'Apple',
            ];

            if ($account->currency == 'USD') {
                $payment_data['total'] = convert_currency_zar_to_usd($payment_data['total']);
                $payment_data['document_currency'] = 'USD';
            }

            $pre_payment_exists = \DB::table('acc_cashbook_transactions')->where('reference', $reference)->count();

            if (! $pre_payment_exists) {
                if (isset($post_data->amount_fee)) {
                    $fee_data = [
                        'ledger_account_id' => 22,
                        'cashbook_id' => $cashbook->id,
                        'total' => abs($post_data->amount_fee),
                        'api_id' => $post_data->pf_payment_id,
                        'reference' => 'Apple Fee '.$post_data->pf_payment_id,
                        'api_status' => 'Complete',
                        'doctype' => 'Cashbook Control Payment',
                        'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    ];

                    $fee_result = $db->setTable('acc_cashbook_transactions')->save($fee_data);

                    if (! is_array($fee_result) || empty($fee_result['id'])) {
                        aa('Error inserting Apple Fee into journals', ['fee_result' => $fee_result]);
                        throw new \ErrorException('Error inserting Apple Fee into journals.'.json_encode($fee_result));
                    }
                }

                $api_data = [
                    'api_status' => 'Complete',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'doctype' => 'Cashbook Customer Receipt',
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                ];

                $result = $db->setTable('acc_cashbook_transactions')->save($api_data);

                if (! is_array($result) || empty($result['id'])) {
                    if (isset($fee_result['id'])) {
                        $db->setTable('acc_general_journals')->deleteRecord(['id' => $fee_result['id']]);
                    }
                    throw new \ErrorException('Error inserting to acc_cashbook_transactions.'.json_encode($result));
                }
            }

            $is_telecloudMobile = false;
            $currenturl = $request->url();
            if (str_contains($currenturl, 'portal') || session('instance')->directory == 'telecloud') {
                $is_telecloudMobile = true;
            }

            if ($is_telecloudMobile && ! empty($request->custom_int2)) {
                addAirtime($account_id, $amount);
            }

            $is_vehicledb = false;
            $currenturl = $request->url();
            if (str_contains($currenturl, 'vehicledb') || session('instance')->directory == 'vehicledb') {
                $is_vehicledb = true;
            }
            if ($is_vehicledb && ! empty($request->custom_int2)) {
                $result = create_vehicledb_invoice($account_id, $amount, $request->custom_int2, 'Credits purchased - Apple');
            }

            return response()->json(['message' => 'success'], 200);
        } catch (\Throwable $ex) {

            return response()->json(['message' => 'success'], 200);

            aa('Exception caught from integrationContller', ['exception' => $ex]);
            exception_log($ex);
            try {
                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => $reference,
                    'total' => $amount,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
                    'api_data' => serialize($post_data),
                    'api_id' => $post_data->pf_payment_id,
                    'api_error' => $ex->getMessage(),
                ];

                foreach ($api_data as $k => $v) {
                    if (empty($v)) {
                        $api_data[$k] = 0;
                    }
                }
                dbinsert('acc_cashbook_transactions', $api_data);
                aa('API data inserted after exception', ['api_data' => $api_data]);

                if (! str_contains($ex->getMessage(), 'OrderStatus is not Approved')) {
                    exception_email($ex, 'Apple response error '.date('Y-m-d H:i:s'));
                }
            } catch (\Throwable $err) {
                aa('Exception caught in nested try', ['exception' => $err]);
                if (empty($account_id)) {
                    $account_id = 0;
                }

                $api_data = [
                    'api_status' => 'Invalid',
                    'account_id' => $account_id,
                    'reference' => '',
                    'total' => 0,
                    'cashbook_id' => $cashbook->id,
                    'docdate' => date('Y-m-d H:i:s'),
                    'api_data' => serialize($post_data),
                    'api_id' => 0,
                    'api_error' => $err->getMessage(),
                ];

                exception_email($err, 'Apple response error '.date('Y-m-d H:i:s'));
                dbinsert('acc_cashbook_transactions', $api_data);
                aa('API data inserted after nested exception', ['api_data' => $api_data]);
            }

            return response()->json(['message' => 'Unable to verify Payment'], 500);

        }

        aa('appleNotify method ended');
    }

    // public function payfastResponse(Request $request)
    // {
    //     aa('payfastResponse method started');
    //     try {
    //         $post_data = (object) $request->all();
    //         aa('Post data received', ['post_data' => $post_data]);

    //         $created_at = date('Y-m-d H:i:s');
    //         $db = new \DBEvent();
    //         $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();
    //         aa('Cashbook retrieved', ['cashbook' => $cashbook]);

    //         $reference = $request->m_payment_id;
    //         $account_id = $request->custom_int1;
    //         $amount = $request->amount_gross;

    //         if (empty($account_id)) {
    //             aa('Account ID not found', ['reference' => $reference]);
    //             throw new \ErrorException('account not found.'.$reference);
    //         }

    //         $account = dbgetaccount($account_id);
    //         aa('Account retrieved', ['account' => $account]);

    //         if ($account->partner_id != 1) {
    //             aa('Reseller user payment', ['account_id' => $account_id]);
    //             return true; // reseller users payments
    //         }

    //         if ($post_data->payment_status != 'COMPLETE') {
    //             aa('Payment status not approved', ['payment_status' => $post_data->payment_status]);
    //             throw new \ErrorException('payment_status is not Approved.'.$post_data->payment_status);
    //         }

    //         $payment_data = [
    //             'docdate' => date('Y-m-d', strtotime($created_at)),
    //             'total' => $amount,
    //             'reference' => $reference,
    //             'account_id' => $account_id,
    //             'source' => 'PayFast',
    //         ];

    //         if ($account->currency == 'USD') {
    //             $payment_data['total'] = convert_currency_zar_to_usd($payment_data['total']);
    //             $payment_data['document_currency'] = 'USD';
    //         }

    //         $pre_payment_exists = \DB::table('acc_cashbook_transactions')->where('reference', $reference)->count();

    //         if (!$pre_payment_exists) {
    //             aa('Pre-payment does not exist, proceeding with fee data check');
    //             if (isset($post_data->amount_fee)) {
    //                 aa('Amount fee is set', ['amount_fee' => $post_data->amount_fee]);
    //                 $fee_data = [
    //                     'ledger_account_id' => 22,
    //                     'cashbook_id' => $cashbook->id,
    //                     'total' => abs($post_data->amount_fee),
    //                     'api_id' => $post_data->pf_payment_id,
    //                     'reference' => 'Payfast Fee '.$post_data->pf_payment_id,
    //                     'api_status' => 'Complete',
    //                     'doctype' => 'Cashbook Control Payment',
    //                     'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
    //                 ];

    //                 $fee_result = $db->setTable('acc_cashbook_transactions')->save($fee_data);
    //                 aa('Fee data saved', ['fee_result' => $fee_result]);

    //                 if (!is_array($fee_result) || empty($fee_result['id'])) {
    //                     aa('Error inserting Payfast Fee into journals', ['fee_result' => $fee_result]);
    //                     throw new \ErrorException('Error inserting Payfast Fee into journals.'.json_encode($fee_result));
    //                 }
    //             }

    //             $api_data = [
    //                 'api_status' => 'Complete',
    //                 'account_id' => $account_id,
    //                 'reference' => $reference,
    //                 'total' => $amount,
    //                 'doctype' => 'Cashbook Customer Receipt',
    //                 'cashbook_id' => $cashbook->id,
    //                 'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
    //                 'api_data' => serialize($post_data),
    //                 'api_id' => $post_data->pf_payment_id,
    //             ];

    //             $result = $db->setTable('acc_cashbook_transactions')->save($api_data);
    //             aa('API data saved', ['result' => $result]);

    //             if (!is_array($result) || empty($result['id'])) {
    //                 if (isset($fee_result['id'])) {
    //                     $db->setTable('acc_general_journals')->deleteRecord(['id' => $fee_result['id']]);
    //                 }
    //                 aa('Error inserting to acc_cashbook_transactions', ['result' => $result]);
    //                 throw new \ErrorException('Error inserting to acc_cashbook_transactions.'.json_encode($result));
    //             }
    //         }

    //         aa('Process Crediting');
    //         $is_telecloudMobile = false;
    //         $currenturl = $request->url();
    //         aa('Current URL', ['url' => $currenturl]);
    //         if (str_contains($currenturl, 'portal')) {
    //             $is_telecloudMobile = true;
    //             aa('URL contains "portal"', ['is_telecloudMobile' => $is_telecloudMobile]);
    //         }

    //         if ($is_telecloudMobile && !empty($request->custom_int2)) {
    //             addAirtime($account_id, $amount);
    //             aa('telecloud mobile account credited',  ['account_id' => $account_id]);
    //         }

    //         $is_vehicledb = false;
    //         $currenturl = $request->url();
    //         if (str_contains($currenturl, 'vehicledb') || session('instance')->directory == 'vehicledb') {
    //             $is_vehicledb = true;
    //         }
    //         if ($is_vehicledb && !empty($request->custom_int2)) {
    //             $result = create_vehicledb_invoice($account_id, $amount, $request->custom_int2, 'Credits purchased - Payfast');
    //             aa('VehicleDB invoice created', ['result' => $result]);
    //         }

    //     } catch (\Throwable $ex) {
    //         aa('Exception caught from integrationContller', ['exception' => $ex]);
    //         exception_log($ex);
    //         try {
    //             $api_data = [
    //                 'api_status' => 'Invalid',
    //                 'account_id' => $account_id,
    //                 'reference' => $reference,
    //                 'total' => $amount,
    //                 'cashbook_id' => $cashbook->id,
    //                 'docdate' => date('Y-m-d H:i:s', strtotime($created_at)),
    //                 'api_data' => serialize($post_data),
    //                 'api_id' => $post_data->pf_payment_id,
    //                 'api_error' => $ex->getMessage(),
    //             ];

    //             foreach ($api_data as $k => $v) {
    //                 if (empty($v)) {
    //                     $api_data[$k] = 0;
    //                 }
    //             }
    //             dbinsert('acc_cashbook_transactions', $api_data);
    //             aa('API data inserted after exception', ['api_data' => $api_data]);

    //             if (!str_contains($ex->getMessage(), 'OrderStatus is not Approved')) {
    //                 exception_email($ex, 'PayFast response error '.date('Y-m-d H:i:s'));
    //             }
    //         } catch (\Throwable $err) {
    //             aa('Exception caught in nested try', ['exception' => $err]);
    //             if (empty($account_id)) {
    //                 $account_id = 0;
    //             }

    //             $api_data = [
    //                 'api_status' => 'Invalid',
    //                 'account_id' => $account_id,
    //                 'reference' => '',
    //                 'total' => 0,
    //                 'cashbook_id' => $cashbook->id,
    //                 'docdate' => date('Y-m-d H:i:s'),
    //                 'api_data' => serialize($post_data),
    //                 'api_id' => 0,
    //                 'api_error' => $err->getMessage(),
    //             ];

    //             exception_email($err, 'PayFast response error '.date('Y-m-d H:i:s'));
    //             dbinsert('acc_cashbook_transactions', $api_data);
    //             aa('API data inserted after nested exception', ['api_data' => $api_data]);
    //         }
    //     }

    //     aa('payfastResponse method ended');
    // }

    public function payNow(Request $request, $encoded_link)
    {
        //        abort(403);

        $data = decode_paynow_link($encoded_link);

        if (! $data || empty($data) || count($data) == 0) {
            return Redirect::to('/')->with('message', 'Invalid Link2')->with('status', 'error');
        }

        $customer_id = $data['account_id'];
        $amount = $data['amount'];

        if (! $customer_id && ! empty(session('account_id'))) {
            $customer_id = session('account_id');
        }

        if (! $customer_id) {
            return Redirect::to('/')
                ->with('message', 'No Access')->with('status', 'error');
        }

        $customer = dbgetaccount($customer_id);
        $reseller = dbgetaccount($customer->partner_id);

        if (empty($customer) || $customer->status == 'Deleted') {
            return Redirect::to('/')
                ->with('message', 'Invalid Account')->with('status', 'error');
        }

        $reseller = dbgetaccount($customer->partner_id);

        $data = [
            'menu_name' => 'Payment Options - '.$reseller->company,
            'customer' => $customer,
            'amount' => $amount,
            'reseller' => $reseller,
        ];
        $data['amount'] = currency($amount);
        $data['logo'] = '';
        if ($reseller->logo > '' && file_exists(uploads_settings_path().$reseller->logo)) {
            $data['logo'] = settings_url().$reseller->logo;
        }

        $webform_data = [];
        $webform_data['module_id'] = 390;
        $webform_data['account_id'] = $customer_id;
        $link_data = \Erp::encode($webform_data);

        $data['debit_order_link'] = request()->root().'/webform/'.$link_data;
        $data['tailwind_css'] = 1;

        return view('__app.components.pages.make_payment', $data);
    }

    public function domainSearchWebsite(Request $request)
    {
        // header("Access-Control-Allow-Origin: *");
        if (! empty($request->domain_name)) {
            $domain_name = $request->domain_name;
            $tld = get_tld($domain_name);
            $domain = $domain_name;
        } else {
            $domain_name = $request->domain;
            $tld = $request->tld;
            $domain = $domain_name.$tld;
        }
        $supported_tld = valid_tld($domain);

        if (! $supported_tld) {
            return cors_json_alert('Tld not supported', 'danger');
        }

        if (is_local_domain($domain)) {
            $result = zacr_domain_check($domain);
            if (isset($result['response']) && isset($result['response']['available']) && $result['response']['available'] == '1') {
                return cors_json_alert($domain.' is Available.');
            } else {
                return cors_json_alert($domain.' is Unavailable.', 'danger');
            }
        } else {
            $available = domain_available($domain);

            if ($available == 'Premium') {
                return cors_json_alert('Premium domain names cannot be ordered.', 'danger');
            }

            if ($available == 'No') {
                return cors_json_alert($domain.' is Unavailable.', 'danger');
            }

            if ($available == 'Yes') {
                return cors_json_alert($domain.' is Available.');
            }
        }
    }

    public function domainSearch(Request $request)
    {
        if ($request->isMethod('post')) {
            $domain_name = strtolower($request->domain_name);
            $tld = $request->tld;

            if (! is_valid_domain_name($request->domain_name)) {
                return json_alert('Invalid domain name', 'warning');
            }

            if (empty($domain_name) || empty($tld)) {
                return json_alert('Fill required inputs', 'warning');
            }
            $domain = $domain_name.'.'.$tld;
            $supported_tld = valid_tld($domain);
            if (! $supported_tld) {
                return json_alert('Tld not supported', 'warning');
            }

            $available = domain_available($domain);
            if ($available == 'Premium') {
                return json_alert('Premium domain names cannot be ordered.', 'warning');
            }

            if ($available == 'No') {
                return json_alert('Domain name '.$domain.' unavailable.', 'warning');
            }

            if ($available == 'Yes') {
                return json_alert('Domain '.$domain.' available.');
            }
        } else {
            $data['tlds'] = get_supported_tlds();
            $data['menu_name'] = 'Domain Search';
            $data['embed'] = false;
            if (! empty($request->embed)) {
                $data['embed'] = true;
            }

            return view('__app.components.pages.domain_search', $data);
        }
    }

    public function getsms_balances(Request $request)
    {
        $account_id = api_account_id($request['api_key']);

        if ($account_id) {
            $subscriptions = \DB::table('sub_services')->where('status'.'!=', 'Deleted')->where('account_id', $account_id)->get();
            if (! empty($subscriptions) && count($subscriptions) > 0) {
                $response['status'] = 'Success';
                $balances = [];
                foreach ($subscriptions as $sub) {
                    if ($sub->provision_type == 'bulk_sms_prepaid') {
                        $balances['bulk_sms_balance'] = $sub->current_usage;
                    }
                    if ($sub->provision_type == 'bulk_sms') {
                        $detail = strtolower(str_replace(' ', '_', $sub->detail)).'_balance';
                        $balances[$detail] = $sub->current_usage;
                    }
                }
                $response['balances'] = $balances;
            } else {
                $response['status'] = 'No valid sms subscriptions';
            }
        } else {
            $response['status'] = 'Invalid API Key';
        }

        return $response;
    }

    public function getsms_report(Request $request)
    {
        $sender = api_account_id($request['api_key']);
        if ($sender) {
            $sms = \DB::table('isp_sms_messages')->where('account_id', $sender)->where('id', $request['sms_id'])->get()->first();
            if ($sms) {
                $response['status'] = 'Success';
                $response['sms_id'] = $sms->id;
                $response['total_qty'] = $sms->total_qty;
                $response['delivered_qty'] = $sms->delivered_qty;

                return $response;
            } else {
                $response['status'] = 'Invalid SMS ID';
                $response['sms_id'] = $_REQUEST['sms_id'];

                return $response;
            }
        } else {
            $response['status'] = 'Invalid API Key';
            $response['sms_id'] = $sms_id;

            return $response;
        }
    }

    public function getsms_send(Request $request)
    {
        $sender = api_account_id($request['api_key']);

        if ($sender) {
            $db = new \DBEvent(85);
            $message = $request['message'];
            $numbers = explode(',', $request->numbers);
            foreach ($numbers as $to) {
                if (ctype_digit($to) and strlen($to) > 9 and strlen($to) < 12) {
                    $recipients[] = $to;
                }
            }
            if ($recipients && is_array($recipients) && count($recipients) > 0) {
                $sms_count = strlen($message) / 160;
                $sms['account_id'] = $sender;
                $sms['numbers'] = implode("\r\n", $recipients);
                $sms['charactercount'] = strlen($message);
                $sms['message'] = $message;
                $result = $db->save($sms);
                if (! is_array($result) || empty($result['id'])) {
                    $response['status'] = 'Invalid Number(s)';
                    $response['message'] = $result;
                    $response['sms_id'] = null;
                } else {
                    $response['status'] = 'Success';
                    $response['sms_id'] = $result['id'];
                }
            } else {
                $response['status'] = 'Invalid Number(s)';
                $response['sms_id'] = null;
            }
        } else {
            $response['status'] = 'Invalid API Key';
            $response['sms_id'] = null;
        }

        return $response;
    }
}
