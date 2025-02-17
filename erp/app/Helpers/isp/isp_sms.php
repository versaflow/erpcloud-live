<?php

function schedule_email2sms()
{
    // exit;
    //session()->put('status', 'qwerty');
    $recipients = [];
    //$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
    try {
        //$hostname = '{mail.smssend.co.za:143}INBOX';
        $hostname = '{mail.smssend.co.za:143/novalidate-cert}INBOX';
        $username = 'catchall@smssend.co.za';
        $password = 'JollyGoalieCofferAlias92';
        $inbox = imap_open($hostname, $username, $password);
    } catch (\Throwable $ex) {  
        exception_log($ex);
        //exception_email($ex, 'SMS email2sms error');
        return false;
    }

    if (!$inbox) {
        return;
    }
    $emails = imap_search($inbox, 'UNSEEN');


    if (!empty($emails) && count($emails) > 0) {
        rsort($emails);
        foreach ($emails as $email_number) {
            $header = imap_headerinfo($inbox, $email_number);

            $email_sender = $header->from[0]->mailbox.'@'.$header->from[0]->host;


            $sender = dbgetcell('isp_sms_email', 'sending_email_address', $email_sender, 'account_id');

            $overview = imap_fetch_overview($inbox, $i, 0);
            $subject = $overview[0]->subject;
            if (!empty($subject) && str_contains($subject, '@')) {
                $subject_sender = dbgetcell('isp_sms_email', 'sending_email_address', $subject, 'account_id');
                if (!empty($subject_sender)) {
                    $sender = $subject_sender;
                }
            }

            if ($sender) {
                $body = imap_fetchbody($inbox, $email_number, 1);


                if (preg_match('/^([a-zA-Z0-9]{76} )+[a-zA-Z0-9]{76}$/', $body)) {
                    $body = base64_decode($body);
                }
                $recipients = [];
                $message = trim($body);

                $x = 0;
                foreach ($header->to as $recipient) {
                    $x = $x + 1;
                    $to = $recipient->mailbox;

                    if (ctype_digit($to) and strlen($to) > 9 and strlen($to) < 12) {
                        $recipients[] = $to;
                    }
                }

                if ($recipients) {
                    $sms_count = strlen($message) / 160;
                    $sms['account_id'] = $sender;
                    $sms['numbers'] = implode("\r\n", $recipients);
                    $sms['charactercount'] = strlen($message);
                    $sms['message'] = str_replace('=0A', "\n", $message);
                    $id = dbinsert('isp_sms_messages', $sms);
                    $request_data = new \Illuminate\Http\Request();
                    $request_data->id = $id;
                    $result = aftersave_send_sms($request_data);
                }
            }
            imap_delete($inbox, $email_number);
        }
    }
    imap_expunge($inbox);
    imap_close($inbox);
}


function schedule_sms_uptime_send()
{
    // sms notification
    queue_sms(12, '0658919100', 'sms downtime check', 1, 1);
}

function schedule_sms_uptime_check()
{
    // task notification
    try {
        $message_id = \DB::table('isp_sms_messages')
        ->where('queuetime', 'LIKE', date('Y-m-d').'%')
        ->where('numbers', '0658919100')
        ->where('message', 'sms downtime check')
        ->pluck('id')->first();
        $status = \DB::table('isp_sms_message_queue')->where('isp_sms_messages_id', $message_id)->pluck('status')->first();
        $error_description = \DB::table('isp_sms_message_queue')->where('isp_sms_messages_id', $message_id)->pluck('error_description')->first();

        if (empty($status)) {
            $status = 'Not found';
        }
        if (empty($error_description)) {
            $error_description = 'None';
        }
        if ($status != 'Sent') {
            $data = ['error_description' => $error_description, 'sms_status' => $status, 'function_name' => 'schedule_sms_uptime_check'];
            //  erp_process_notification(1,$data);
        }
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_email($ex, 'schedule_sms_uptime_check error '.date('Y-m-d H:i'));
    }
}

function aftersave_sms_lists_update_count($request)
{
    $lists = \DB::table('isp_sms_lists')->get();
    foreach ($lists as $list) {
        $count = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $list->id)->count();
        \DB::table('isp_sms_lists')->where('id', $list->id)->update(['list_total' => $count]);
    }
}
function aftersave_sms_lists_numbers_update_count($request)
{
    $lists = \DB::table('isp_sms_lists')->get();
    foreach ($lists as $list) {
        $count = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $list->id)->count();
        \DB::table('isp_sms_lists')->where('id', $list->id)->update(['list_total' => $count]);
    }
}

/// AJAX
function ajax_sms_template_message($request)
{
    $response = [];
    if ($request->sms_template_id) {
        $template = \DB::table('isp_sms_templates')->where('id', $request->sms_template_id)->get()->first();
        $response['message'] = $template->text;
    }

    return $response;
}

function sms_send_list_options($row)
{
    $opt = [];
    $account_id = session('sms_account_id');
    aa($account_id);
    if ($account_id == 1) {
        $account_id = 12;
    }
    $lists = \DB::table('isp_sms_lists')->where('public_list', 1)->orderBy('name')->get();
    foreach ($lists as $list) {
        $opt[$list->id] = $list->name.' - '.$list->list_total.' numbers (Public List, 2 credits per sms)';
    }

    $lists = \DB::table('isp_sms_lists')->where('public_list', 0)->where('account_id', $account_id)->orderBy('name')->get();
    foreach ($lists as $list) {
        $opt[$list->id] = $list->name.' - '.$list->list_total.' numbers';
    }
    return $opt;
}

function button_sms_list_download($request)
{
    $list = \DB::table('isp_sms_lists')->where('id', $request->id)->get()->first();
    $numbers = \DB::table('isp_sms_list_numbers')->select('name', 'number')->where('sms_list_id', $request->id)->get();

    $file_title = $list->name;
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $excel_list = [];

    foreach ($numbers as $n) {
        $excel_list[] = (array) $n;
    }


    $export = new App\Exports\CollectionExport();
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
    $file_path = attachments_path().$file_name;
    return response()->download($file_path, $file_name);
}

function button_sms_list_duplicate($request)
{
    $list = \DB::table('isp_sms_lists')->where('id', $request->id)->get()->first();
    $data = (array) $list;
    unset($data['id']);
    $data['name'] .= ' duplicate';
    $new_list_id = \DB::table('isp_sms_lists')->insertGetId($data);
    $numbers = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $list->id)->get();
    foreach ($numbers as $n) {
        $d = (array) $n;
        unset($d['id']);
        $d['sms_list_id'] = $new_list_id;
        \DB::table('isp_sms_list_numbers')->insert($d);
    }
    return json_alert('List duplicated.');
}

function afterimport_update_sms_list_count()
{
    $numbers = \DB::table('isp_sms_list_numbers')->get();
    foreach ($numbers as $n) {
        $valid_number = valid_za_mobile_number($n->number);
        if ($valid_number) {
            \DB::table('isp_sms_list_numbers')->where('id', $n->id)->update(['number' => $valid_number]);
        } else {
            \DB::table('isp_sms_list_numbers')->where('id', $n->id)->delete();
        }
    }


    $list_ids = \DB::table('isp_sms_lists')->pluck('id')->toArray();
    //delete duplicates

    $table = 'isp_sms_list_numbers';
    $field = 'number';
    foreach ($list_ids as $list_id) {
        $rows = \DB::connection($conn)->select(" SELECT * FROM `$table`
        WHERE sms_list_id=".$list_id."
        GROUP BY `$field`
        HAVING COUNT(`$field`) > 1");

        foreach ($rows as $row) {
            \DB::connection($conn)->table($table)->where('id', '!=', $row->id)->where($field, $row->{$field})->delete();
        }
    }

    $lists = \DB::table('isp_sms_lists')->get();
    foreach ($lists as $list) {
        $count = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $list->id)->count();
        \DB::table('isp_sms_lists')->where('id', $list->id)->update(['list_total' => $count]);
    }
}

function schedule_update_sms_list_count()
{
    /*
    $numbers = \DB::table('isp_sms_list_numbers')->get();
    foreach ($numbers as $n) {
        $valid_number = valid_za_mobile_number($n->number);
        if ($valid_number) {
            \DB::table('isp_sms_list_numbers')->where('id', $n->id)->update(['number' => $valid_number]);
        } else {
            \DB::table('isp_sms_list_numbers')->where('id', $n->id)->delete();
        }
    }


    $list_ids = \DB::table('isp_sms_lists')->pluck('id')->toArray();
    //delete duplicates

    $table = 'isp_sms_list_numbers';
    $field = 'number';
    foreach ($list_ids as $list_id) {
        $rows = \DB::connection($conn)->select(" SELECT * FROM `$table`
        WHERE sms_list_id=".$list_id."
        GROUP BY `$field`
        HAVING COUNT(`$field`) > 1");

        foreach ($rows as $row) {
            \DB::connection($conn)->table($table)->where('id', '!=', $row->id)->where($field, $row->{$field})->delete();
        }
    }

    */
    $lists = \DB::table('isp_sms_lists')->get();
    foreach ($lists as $list) {
        $count = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $list->id)->count();
        \DB::table('isp_sms_lists')->where('id', $list->id)->update(['list_total' => $count]);
    }
}

function update_sms_balances_from_invoices()
{
    $account_ids = \DB::table('sub_services')->where('provision_type', 'bulk_sms_prepaid')->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
    foreach ($account_ids as $account_id) {
        $sms_count = \DB::table('crm_document_lines')->select('crm_documents.doctype', 'crm_document_lines.*')
            ->join('crm_documents', 'crm_document_lines.document_id', '=', 'crm_documents.id')
            ->where('crm_documents.account_id', $account_id)
            ->where('crm_documents.doctype', 'Tax Invoice')
            ->where('crm_document_lines.product_id', 101)
            ->sum('qty');

        $credit_sms_count = \DB::table('crm_document_lines')->select('crm_documents.doctype', 'crm_document_lines.*')
            ->join('crm_documents', 'crm_document_lines.document_id', '=', 'crm_documents.id')
            ->where('crm_documents.account_id', $account_id)
            ->where('crm_documents.doctype', 'Credit Note')
            ->where('crm_document_lines.product_id', 101)
            ->sum('qty');

        $used_sms = \DB::table('isp_sms_message_queue')->where('account_id', $account_id)->where('status', '!=', 'Queued')->count();
        $balance = $sms_count - $credit_sms_count - $used_sms;

        $monthly_used_sms = \DB::table('isp_sms_message_queue')->where('account_id', $account_id)->where('time_queued', 'LIKE', date('Y-m').'%')->where('status', '!=', 'Queued')->count();
        \DB::table('sub_services')->where('account_id', $account_id)->where('provision_type', 'bulk_sms_prepaid')->where('status', '!=', 'Deleted')->where('current_usage', '<', $balance)->update(['current_usage' => $balance]);
    }
}

function validate_sms_lists()
{
    $lists = \DB::table('isp_sms_lists')->get();
    foreach ($lists as $list) {
        $list_count = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $list->id)->count();
        if (!$list_count) {
            \DB::table('isp_sms_lists')->where('id', $list->id)->delete();
        }
        $account_deleted = \DB::table('crm_accounts')->where('id', $list->account_id)->where('status', 'Deleted')->count();
        if ($account_deleted) {
            \DB::table('isp_sms_lists')->where('id', $list->id)->delete();
        }
    }
    $lists = \DB::table('isp_sms_lists')->get();
    $list_ids = $lists->pluck('id')->toArray();

    \DB::table('isp_sms_list_numbers')->whereNotIn('sms_list_id', $list_ids)->delete();
}

function schedule_get_sms_inbox()
{
    try {
        $api = new PanaceaApi();
        $api->setUsername('cloud_telecoms');
        $api->setPassword('147896');
        $last_id = \DB::table('isp_sms_inbox')->max('message_id');
        if (empty($last_id) && !is_main_instance()) {
            $last_id = \DB::connection('system')->table('isp_sms_inbox')->max('message_id');
        }
        if (empty($last_id)) {
            $last_id = 84148622;
        }
        $result = $api->messages_get($last_id);
      
        if ($result['status'] == 0 && $result['message'] =="OK") {
            foreach ($result['details'] as $reply) {
                if (!empty($reply['reply_msg_uuid'])) {
                    $msg_found = \DB::table('isp_sms_message_queue')->where('panacea_id', $reply['reply_msg_uuid'])->count();
                   
                    if ($msg_found) {
                        $sms_id = \DB::table('isp_sms_message_queue')->where('panacea_id', $reply['reply_msg_uuid'])->pluck('isp_sms_messages_id')->first();
                        $sms = \DB::table('isp_sms_messages')->where('id', $sms_id)->get()->first();
                        $exists = \DB::table('isp_sms_inbox')->where('message_id', $reply['id'])->count();
                        if(strtolower($reply['data']) == 'stop'){
                            $number = valid_za_mobile_number($reply['from']);
                            if($number){
                                $account_ids = \DB::table('crm_email_list_records')->where('phone',$number)->pluck('account_id')->unique()->filter()->toArray();
                               
                                $db_account_ids = \DB::table('crm_accounts')->where('phone',$number)->orWhere('phone',$reply['from'])->pluck('id')->unique()->filter()->toArray();
                                $account_ids = collect(array_merge($account_ids,$db_account_ids))->unique()->filter()->toArray();
                                if(count($account_ids) > 0){
                                    \DB::table('crm_accounts')->whereIn('id',$account_ids)->update(['sms_subscribed' => 0]);
                                }
                            }
                        }
                        
                        if (!$exists) {
                            $number = valid_za_mobile_number($reply['from']);
                            if (!$number) {
                                $number = $reply['from'];
                            }
                            $inbox = [
                                'account_id' => $sms->account_id,
                                'created_date' => $reply['created'],
                                'sender' => $number,
                                'message' => $reply['data'],
                                'panacea_id' => $reply['reply_msg_uuid'],
                                'original_message' => $sms->message,
                                'message_id' => $reply['id']
                            ];

                            \DB::table('isp_sms_inbox')->insert($inbox);


                            if ($sms->account_id == 12 || $sms->account_id == 1) {
                                if(strtolower($reply['data']) != 'stop'){
                                    $data['internal_function'] = 'sms_inbox_admin';
                                    $data['reply'] = $reply['data'];
                                    $data['original_message'] = $sms->message;
                                    $data['sender'] = $number;
                                    if(!empty($sms->to_account_id)){
                                        $account = \DB::table('crm_accounts')->select('company','type')->where('id',$sms->to_account_id)->get()->first();
                                        $data['to_account_company'] = $account->company;
                                        $data['to_account_type'] = $account->type;
                                        $data['to_account_id'] = $sms->to_account_id;
                                    }
                                    erp_process_notification(1, $data);
                                }
                            } else {
                                $data['internal_function'] = 'sms_inbox_customer';
                                $data['reply'] = $reply['data'];
                                $data['original_message'] = $sms->message;
                                $data['sender'] = $number;
                                erp_process_notification($sms->account_id, $data);
                            }
                        }
                    }
                }
            }
        } elseif (!empty($result)) {
            if (isset($result['status']) && isset($result['message'])) {
                debug_email("schedule_get_sms_inbox error | ".$result['status']." | ".$result['message']);
            } else {
                debug_email("schedule_get_sms_inbox error | check log");
            }
        }
    } catch (\Throwable $ex) {  exception_log($ex);
        $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
        debug_email("schedule_get_sms_inbox error | ".$error);
    }
}

function button_sms_inbox_optout($request)
{
    $inbox = \DB::table('isp_sms_inbox')->where('id', $request->id)->get()->first();
    $number = valid_za_mobile_number($inbox->sender);
    if (!$number) {
        $number = $inbox->sender;
    }
    $exists = \DB::table('isp_sms_optout')->where('number', $number)->count();
    if ($exists) {
        return json_alert('Number already in optout list', 'warning');
    }
    $data = [
        'created_at' => date('Y-m-d H:i:s'),
        'inbox_id' => $request->id,
        'number' => $number
    ];
    \DB::table('isp_sms_optout')->insert($data);

    return json_alert('Number added to optout list', 'success');
}

function queue_sms($account_id, $to, $msg, $priority = 0, $send_immediately = 0, $email_id = 0, $to_account_id = 0)
{
   
    if (!is_array($to)) {
        $to = [$to];
    }
    $numbers = $to;
    $to_numbers = implode(PHP_EOL, $to);


    $size = ceil(strlen($msg) / 160);
    $character_count = strlen($msg);
    $size = (0 == $size) ? 1 : $size;

    $qty = count($numbers);


    $schedule = date('Y-m-d H:i:s');
    $queue_data = [
        'message' => $msg,
        'numbers' => $to_numbers,
        'account_id' => $account_id,
        'priority' => $priority

    ];
    if ($email_id) {
        $queue_data['email_id'] = $email_id;
    }
    
    if ($to_account_id) {
        $queue_data['to_account_id'] = $to_account_id;
    }

    $id = \DB::connection('default')->table('isp_sms_messages')->insertGetId($queue_data);


    ///////SEND MESSAGE TO QUEUE AND PROCESS QUEUE
    $sms_data = \DB::connection('default')->table('isp_sms_messages')->where('id', $id)->get()->first();

    foreach ($numbers as $number) {
        $data['status'] = 'Queued';
        $number = trim(preg_replace('/\s\s+/', ' ', $number));

        $number = str_replace(' ', '', $number);
        if (!$number) {
            $qty--;
            $data['status'] = 'Invalid Number';
        }

        if (('0' == strpos($number, '0')) && (strlen($number) < 10)) {
            $qty--;
            $data['status'] = 'Invalid Number';
        }

        if ((strlen($number) < 10)) {
            $qty--;
            $data['status'] = 'Invalid Number';
        }

        if (str_starts_with($number, 0)) {
            $number = substr_replace($number, '27', 0, 1);
        }

        $data['isp_sms_messages_id'] = $id;
        $data['number'] = $number;
        $data['time_queued'] = $schedule;
        $data['account_id'] = $sms_data->account_id;
        \DB::connection('default')->table('isp_sms_message_queue')->insert($data);
    }

    $total_qty = $qty * $size;

    $update = array('queuetime' => date('Y-m-d H:i:s'), 'schedule' => $schedule, 'quantity' => $qty, 'size' => $size, 'total_qty' => $total_qty, 'charactercount' => $character_count);

    \DB::connection('default')->table('isp_sms_messages')->where('id', $id)->update($update);

    if ($send_immediately) {
        schedule_process_sms_queue($id);
    }
}

function button_sms_api_get_api_token($request)
{
    $account_id = session('sms_account_id');
    if ($account_id === 1) {
        return json_alert('Switch to customer to set api token', 'warning');
    }
    $token = user_api_token(session('user_id'));
    if (!$token) {
        $token_str = $account_id.date('Y-m-d H:i:s');
        $token = \Hash::make($token_str);
        $token = user_set_api_token(session('user_id'), $token);
    }
    echo '<div class="card mt-4">';

    echo '<div class="card-header">';
    echo '<h4>API TOKEN</h4>';
    echo '</div>';

    echo '<div class="card-body">';
    echo '<code>'.$token.'</code>';
    echo '</div>';
}

function send_sms($queue_id, $to, $text, $report_url = null)
{
    // https://www.panaceamobile.com/docs/default/PanaceaApi.html#message_send
    // https://www.panaceamobile.com/developers/sms-delivery-reports/
    //  $report_url = 'https://cloudtools.versaflow.io/panacea_sms_report?to='.$to.'&status=%d';

    if (!$report_url) {
        $report_url = 'https://portal.telecloud.co.za/sms_result?queue_id='.$queue_id.'&status=%d';
    }

    try {
        $from = null;
        $to = (string) str_replace(' ', '', $to);
        $text = $text;
        $report_mask = 19;
        $charset = 'UTF-8';
        $data_coding = null;
        $message_class;
        $auto_detect_encoding = 0;
        $api = new PanaceaApi();
        $api->setUsername('cloud_telecoms');
        $api->setPassword('147896');

        $result = $api->message_send($to, $text, $from = null, $report_mask, $report_url, $charset, $data_coding, $message_class, $auto_detect_encoding);


        if (!empty($result['details'])) {
            \DB::connection('default')->table('isp_sms_message_queue')->where('id', $queue_id)->update(['panacea_id' => $result['details']]);
        }
        if (!empty($result['message'])) {
            \DB::connection('default')->table('isp_sms_message_queue')->where('id', $queue_id)->update(['status' => $result['message']]);
        }
      
      
        return $result;
    } catch (\Throwable $ex) {  exception_log($ex);
        $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
    }
    // {"status":1,"message":"Sent","details":"8beda1a8-5c12-489f-0107-123000000003"}
    return false;
}

function smslist_select()
{
    $isp_sms_lists = \DB::table('isp_sms_lists')->where('account_id', session('account_id'))->get();
    $sms_select_options = '<option value="0">Select SMS List</option>';
    if ($isp_sms_lists && count($isp_sms_lists) > 0) {
        foreach ($isp_sms_lists as $sms_list) {
            $sms_select_options .= '<option value="'.$sms_list->id.'">'.$sms_list->name.'</option>';
        }
    }

    echo '<select name="sms_list" id="sms_list" class="form-control">'.$sms_select_options.'</select>';
}

function beforesave_number_validation($request)
{
    $id = (!empty($request->id)) ? $request->id : null;

    //validate name
    if (strlen($request->name) <= 1) {
        //  return 'Enter valid name';
    }

    //validate number
    try {
        $number = $request->number;

        $phone = phone($number, ['ZA','US','Auto']);


        if (empty($phone)) {
            return 'Invalid phone number: '.$number;
        }

        if ($phone->isOfType('fixed_line')) {
            return 'You can not use a fixed line number for sms: '.$number;
        }

        $phone = $phone->formatForMobileDialingInCountry('ZA');
        if (empty($phone) && strlen($phone) != 10) {
            return 'Invalid phone number: '.$number;
        }

        $request->request->add(['number' => $phone]);

        //check if num already exists
        $data = ['number' => $phone, 'sms_list_id' => $request->sms_list_id];

        $number_exists = dbcount('isp_sms_list_numbers', $data);
        if ($number_exists > 0) {
            return 'Duplicate number';
        }
    } catch (\Throwable $ex) {  exception_log($ex);
        return $ex->getMessage();
    }
}

function beforesave_send_sms($request)
{
    $row = (object) $request->all();

    if ($row->sms_list_id > 0) {
        $sql = 'select number from isp_sms_list_numbers where sms_list_id = '.$row->sms_list_id;
        $numbers = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $row->sms_list_id)->pluck('number')->toArray();
        $numbers = collect($numbers)->unique()->filter()->toArray();
        $qty = count($numbers);

        if ($qty == 0) {
            return 'Invalid List. No numbers set on list.';
        }
    }


    if (empty($numbers) || count($numbers) == 0) {
        $numbers = explode(PHP_EOL, $row->numbers);
        $numbers = collect($numbers)->unique()->filter()->toArray();
        $qty = count($numbers);
    }

    if (empty($numbers) || count($numbers) == 0) {
        return 'Numbers required';
    }

    foreach ($numbers as $number) {
        try {
            $phone = phone($number, ['ZA','US','Auto']);

            if (empty($phone)) {
                return 'Invalid phone number: '.$number;
            }

            if ($phone->isOfType('fixed_line')) {
                return 'You can not use a fixed line number for sms: '.$number;
            }

            $phone = $phone->formatForMobileDialingInCountry('ZA');
            if (empty($phone) && strlen($phone) != 10) {
                return 'Invalid phone number: '.$number;
            }
        } catch (\Throwable $ex) {  exception_log($ex);
            return 'Invalid phone number: '.$number;
        }
    }
}

function aftersave_send_sms($request)
{
    $id = (!empty($request->id)) ? $request->id : null;
    $new_record = (!empty($request->new_record)) ? 1 : 0;
    $request->request->remove('new_record');

    if (session('instance')->id == 1) {
        $sms = \DB::connection('default')->table('isp_sms_messages')->where('id', $request->id)->get()->first();
        if ($sms->account_id == 1) {
            \DB::connection('default')->table('isp_sms_messages')->where('id', $request->id)->update(['account_id' => 12]);
        }
    }

    ///////SEND MESSAGE TO QUEUE AND PROCESS QUEUE
    $sql = ' SELECT * FROM isp_sms_messages WHERE id = '.$id.'';
    $row = \DB::select($sql)[0];
    $schedule = $row->schedule;
    if (empty($schedule) || '0000-00-00 00:00:00' == $schedule) {
        $schedule = date('Y-m-d H:i:s');
    }

    $size = ceil(strlen($row->message) / 160);
    $character_count = strlen($row->message);
    $size = (0 == $size) ? 1 : $size;
    if ($row->sms_list_id > 0) {
        $sql = 'select number from isp_sms_list_numbers where sms_list_id = '.$row->sms_list_id;
        $numbers = \DB::table('isp_sms_list_numbers')->where('sms_list_id', $row->sms_list_id)->pluck('number')->toArray();
        $qty = count($numbers);
    }

    if (empty($numbers) || count($numbers) == 0) {
        $numbers = explode(PHP_EOL, $row->numbers);
        $qty = count($numbers);
    }


    $sms_data = \DB::table('isp_sms_messages')->where('id', $id)->get()->first();
    foreach ($numbers as $number) {
        $number = trim(preg_replace('/\s\s+/', ' ', $number));

        $number = str_replace(' ', '', $number);
        if (!$number) {
            $qty--;
            continue;
        }

        if (('0' == strpos($number, '0')) && (strlen($number) < 10)) {
            $qty--;
            continue;
        }

        if ((strlen($number) < 10)) {
            $qty--;
            continue;
        }

        if (str_starts_with($number, 0)) {
            $number = substr_replace($number, '27', 0, 1);
        }

        $data['status'] = 'Queued';
        $data['isp_sms_messages_id'] = $id;
        $data['number'] = $number;
        $data['time_queued'] = $schedule;
        $data['account_id'] = $sms_data->account_id;
        \DB::table('isp_sms_message_queue')->insert($data);
    }

    $total_qty = $qty * $size;
    dbset('isp_sms_messages', 'id', $id, array('queuetime' => date('Y-m-d H:i:s'), 'schedule' => $schedule, 'quantity' => $qty, 'size' => $size, 'total_qty' => $total_qty, 'charactercount' => $character_count));
    $request->request->add(['new_record' => $new_record]);
}

///////SCHEDULE
function schedule_sms_get_balance()
{
    $api = new PanaceaApi();
    $api->setUsername('cloud_telecoms');
    $api->setPassword('147896');

    $result = $api->user_get_balance();

    if (!empty($result) && isset($result['details'])) {
        \DB::table('erp_admin_settings')->where('id', 1)->update(['carrier_sms' => $result['details']]);
        if ($result['details'] < 100) {
            $data['details'] = 'Carrier SMS balance is: '.$result['details'].', only priority sms being sent.';

            $data['function_name'] = __FUNCTION__;
            //$data['debug'] = 1;
            erp_process_notification(1, $data);
        }
    }
}



function button_sms_process_queue()
{
    schedule_process_sms_queue();
}

function get_account_sms_balance($account_id)
{
    if (session('instance')->id == 1 && $account_id == 12) {
        return 20000;
    }
    return \DB::connection('default')->table('sub_services')
    ->where('account_id', $account_id)
    ->where('provision_type', 'bulk_sms_prepaid')
    ->where('status', '!=', 'Deleted')
    ->pluck('current_usage')->first();
}

function set_account_sms_balance($account_id, $balance)
{
    $used_sms = \DB::table('isp_sms_message_queue')->where('account_id', $account_id)->where('time_queued', 'LIKE', date('Y-m').'%')->where('status', '!=', 'Queued')->count();
    \DB::connection('default')->table('sub_services')
    ->where('account_id', $account_id)
    ->where('provision_type', 'bulk_sms_prepaid')
    ->where('status', '!=', 'Deleted')
    ->update(['current_usage'=>$balance]);
}

function schedule_process_sms_queue($sms_id = false)
{
    try {
        //return false;
        // $sms_id = 10097;

        $sms_queue = \DB::table('isp_sms_messages')->orderBy('id', 'desc')->limit(3)->get();

        $retry_failed_count = 0;
        foreach ($sms_queue as $retry_queue) {
            if ($retry_queue->retry_failed) {
                $retry_failed_count++;
            }
        }
        if ($retry_failed_count == 3) {
         
            // debug_email('sms queue error - retry failed');
            return false;
        }

        $carrier_balance = \DB::table('erp_admin_settings')->where('id', 1)->pluck('carrier_sms')->first();
        if (session('instance')->id == 1) {
            $balance = get_account_sms_balance($account_id);
            if ($balance < 1000) {
                set_account_sms_balance($account_id, 20000);
            }
        }

        $sms_accounts = \DB::connection('default')->table('sub_services')
            ->where('provision_type', 'bulk_sms_prepaid')
            ->where('status', '!=', 'Deleted')
            ->pluck('account_id')->toArray();
        if (empty($sms_accounts) || count($sms_accounts) == 0) {
            $sms_accounts = [1,12];
        }

        $processing =  \DB::table('isp_sms_messages')->where('processing', 1)->count();
        if ($processing) {
           
            return false;
        }
        $ids = \DB::table('isp_sms_messages')->where('message', 'number validation')->pluck('id')->toArray();
        //dd($ids);
        \DB::table('isp_sms_message_queue')->where('status', 'Expired')->whereIn('isp_sms_messages_id', $ids)->update(['status' => 'Queued','time_queued' =>date('Y-m-d H:i:s')]);



        \DB::table('isp_sms_message_queue')->where('status', 'Queued')->where('time_queued', '<', date('Y-m-d', strtotime('- 2 days')))->update(['status' => 'Expired']);


        $query = \DB::table('isp_sms_message_queue as smq');
        $query->select('sm.*', 'smq.id as smq_id', 'smq.number', 'smq.status', 'sm.total_qty', 'sm.size', 'sm.account_id');
        $query->join('isp_sms_messages as sm', 'smq.isp_sms_messages_id', '=', 'sm.id');

        $query->whereIn('sm.account_id', $sms_accounts);

       
        $query->where(function ($query) {
            $query->whereNull('schedule');
            $query->orWhere('schedule', '<=', date('Y-m-d H:i:s'));
        });

        $query->where(function ($query) {
            $query->where('smq.status', 'Queued');
            //$query->orWhere('smq.error_description', 'Out of credit');
            //$query->orWhere('smq.error_description', 'Customer out of credit');
        });

        if ($carrier_balance < 100) {
            $query->where('sm.priority', 1);
        }
        if ($sms_id) {
            $query->where('sm.id', $sms_id);
        }
        $query->orderBy('sm.priority', 'desc');
        //$sql = querybuilder_to_sql($query);
        //print_r($sql);
        $id_query = $query;
        $message_id = $id_query->limit(1)->pluck('sm.id')->first();

        if (!$message_id) {
            return false;
        }

        if ($message_id) {
            $query->where('sm.id', $message_id);
        }
        $results = $query->limit(200)->get();

        /*
        $sql = querybuilder_to_sql($query);
        */
        $last_processed_id = \DB::table('isp_sms_messages')->where('last_processed_id', '!=', 0)->pluck('last_processed_id')->first();

        if ($last_processed_id == $message_id) {
            //dd('SMS queue already processed '.$last_processed_id. ' - '.$message_id);
            //debug_email('SMS queue already processed '.$last_processed_id. ' - '.$message_id);
            //dd(2);
            return false;
        }


        \DB::table('isp_sms_messages')->where('id', $message_id)->update(['processing'=>1]);
        $retry_queue = false;


        foreach ($results as $row) {
            \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['error_description' => '']);
          

            \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['account_id' => $row->account_id]);
            $total_qty = $row->total_qty;
            $customer_balance = get_account_sms_balance($row->account_id);


            $customer_active = is_customer_active($row->account_id);
            $to = $row->number;

            if ($customer_active || $row->account_id == 1) {
                if (!is_numeric($to)) {
                    \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['status' => 'Error', 'error_description' => 'Number not numeric']);

                    continue;
                }
                $from = $row->account_id;

                $sms_cost = $row->size;
                if ($row->sms_list_id > 0) {
                    $public_list = \DB::table('isp_sms_lists')->where('id', $row->sms_list_id)->where('public_list', 1)->count();
                    if ($public_list) {
                        $sms_cost = $sms_cost * 2;
                    }
                }

                $new_customer_balance = floatval($customer_balance) - $sms_cost;

                if (!in_array($row->account_id,[1,12]) && $new_customer_balance <= 0) {
                    \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['status' => 'Error', 'error_description' => 'Customer out of credit']);
                    continue;
                }

                try {
                    $result = send_sms($row->smq_id, $to, $row->message);
                } catch (\Throwable $ex) {  exception_log($ex);
                    $result = false;
                    \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['status' => 'Error', 'error_description' => $ex->getMessage()]);
                    exception_email($ex, 'SMS error '.date('Y-m-d H:i'));
                }

                if (!empty($result) && isset($result) && isset($result['status'])) {
                    if ($result && 1 == $result['status'] && 'Sent' == $result['message']) {
                        $carrier_sms = \DB::table('erp_admin_settings')->where('id', 1)->pluck('carrier_sms')->first();
                        $carrier_sms -= $row->size;
                        \DB::table('erp_admin_settings')->where('id', 1)->update(['carrier_sms' => $carrier_sms]);
                        $customer_balance = floatval($customer_balance) - $sms_cost;


                        set_account_sms_balance($row->account_id, $customer_balance);
                        \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['status' => 'Sent']);


                        \DB::table('isp_sms_messages')->where('id', $row->id)->increment('customer_credits_used', $sms_cost);
                        \DB::table('isp_sms_messages')->where('id', $row->id)->increment('admin_credits_used', $row->size);
                    } elseif ($result && (1 != $result['status'] || 'Sent' != $result['message'])) {
                        \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['status' => 'Error', 'error_description' => $result['message']]);
                    } else {
                        \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['status' => 'Error', 'error_description' => 'Rejected by Service']);
                    }
                } else {
                    $retry_queue = true;
                    $err = 'sms queue paused';
                    if ($result && isset($result['status'])) {
                        $err .= ' | status '.$result['status'];
                    }
                    if ($result && isset($result['message'])) {
                        $err .= ' | message '.$result['message'];
                    }
                }

                \DB::table('isp_sms_messages')->where('id', $row->id)->update(['customer_credits' => $customer_balance]);
            }

            \DB::table('isp_sms_messages')->where('id', $row->id)->update(['api_response' => json_encode($result)]);
            $delivered = \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->where('status', 'Delivered')->count();
            \DB::table('isp_sms_messages')->where('id', $row->id)->update(['delivered_qty' => $delivered]);

            \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->where('status', 'Queued')->update(['status' => 'Processed','error_description' => '']);
        }



        \DB::table('isp_sms_messages')->update(['last_processed_id'=>$message_id]);
        if ($retry_queue) {
            $retry_count = \DB::table('isp_sms_messages')->where('id', $message_id)->pluck('retry_count')->first();
            if ($retry_count >= 5) {
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['retry_failed' => 1]);
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['processing'=>0,'processed'=>1]);
            } else {
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['processing'=>0,'processed'=>0]);
                \DB::table('isp_sms_messages')->where('id', $message_id)->increment('retry_count');
                \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->update(['status' => 'Queued']);
                $last_processed_id = $message_id-1;
                \DB::table('isp_sms_messages')->update(['last_processed_id'=>$last_processed_id]);
            }
        } else {
            $queued = \DB::table('isp_sms_message_queue')->where('id', $row->smq_id)->where('status', 'Queued')->count();
            if ($queued) {
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['processing'=>0,'processed'=>0]);
            } else {
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['processing'=>0,'processed'=>1]);
            }
        }

        if ($message_id) {
            $api = new PanaceaApi();
            $api->setUsername('cloud_telecoms');
            $api->setPassword('147896');

            $result = $api->user_get_balance();

            if ($result && $result['details']) {
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['admin_credits'=>$result['details']]);
            }
        }
    } catch (\Throwable $ex) {  exception_log($ex);
        if($message_id){
            $queued = \DB::table('isp_sms_message_queue')->where('isp_sms_messages_id', $message_id)->where('status', 'Queued')->count();
            if ($queued) {
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['processing'=>0,'processed'=>0]);
            } else {
                \DB::table('isp_sms_messages')->where('id', $message_id)->update(['processing'=>0,'processed'=>1]);
            }
        }

        exception_email($ex, __FUNCTION__.' error');
    }
}


function schedule_sms_list_fibre_customers()
{
    \DB::table('isp_sms_list_numbers')->where('sms_list_id', 819)->delete();
    $fibre_account_ids = \DB::table('sub_services')->where('provision_type', 'fibre')->where('status', '!=', 'Deleted')->pluck('account_id')->unique()->toArray();
    foreach ($fibre_account_ids as $fibre_account_id) {
        $account = dbgetaccount($fibre_account_id);
        \DB::table('isp_sms_list_numbers')->insert(['sms_list_id'=>819,'name'=>$account->company,'number'=>$account->phone]);
    }
}