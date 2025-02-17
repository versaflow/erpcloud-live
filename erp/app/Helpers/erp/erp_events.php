<?php

function aftersave_set_event_definitions($request)
{
    $app_id = \DB::table('erp_cruds')->where('id', $request->module_id)->pluck('app_id')->first();
    \DB::table('erp_form_events')->where('id', $request->id)->update(['app_id' => $app_id]);

    $function = \DB::table('erp_form_events')->where('id', $request->id)->pluck('function_name')->first();

    if (!empty(session('event_db_record'))) {
        $beforesave_row = session('event_db_record');
        if (!empty($beforesave_row->function_name)) {
            if ($beforesave_row->function_name != $function) {
                replace_code_references_helpers('function '.$beforesave_row->function_name, 'function '.$function);
            }
        }
    } elseif (!function_exists($function)) {
        $added = add_code_definition($function, 'request');
        if (!$added) {
            return 'Could not create function definition.';
        }
    }

    $events = \DB::table('erp_form_events')->get();
    foreach ($events as $event) {
        $function_definition = '';

        $function_name = $event->function_name;

        if (function_exists($function_name)) {
            $r = new \ReflectionFunction($function_name);
            $file = $r->getFileName();
            $helper = end(explode('/', $file));
            $startLine = $r->getStartLine();
            $function_definition = $helper.':'.$startLine.' - '.$function_name;
        }
        \DB::table('erp_form_events')->where('id', $event->id)->update(['function_definition' => $function_definition]);
    }
}


function schedule_events_update_run_time()
{
    try {
        $sql = "UPDATE erp_form_events
        SET run_time_seconds = SUBSTRING_INDEX(run_time, ':', 1) * 60 + SUBSTRING_INDEX(run_time, ':', -1)
        WHERE run_time > ''";
        \DB::statement($sql);
    } catch (\Throwable $ex) {
    }
}

function schedule_events_update_code_definitions()
{
    $events = \DB::table('erp_form_events')->get();
    foreach ($events as $e) {
        try {
            $code = get_function_code($e->function_name);
            if (!empty($code)) {
                \DB::table('erp_form_events')->where('id', $e->id)->update(['function_code' => $code]);
            }
        } catch (\Throwable $ex) {
        }
    }
}

function schedule_email_event_errors()
{
    $failed_events = \DB::table('erp_form_events')->whereNotNull('last_failed')->get();
    $msg = '';
    foreach ($failed_events as $failed_event) {
        $module_name = \DB::table('erp_cruds')->where('id', $failed_event->module_id)->pluck('name')->first();
        $msg .= 'Name: '.$failed_event->function_name.'<br>';
        $msg .= 'Module: '.$module_name.'<br>';
        $msg .= 'Run time: '.$failed_event->last_failed.'<br>';
        $msg .= 'Error: '.substr($failed_event->error, 0, 255).'...<br><br>';
    }

    if ($msg > '') {
        $data = [];
        $data['internal_function'] = 'failed_events';
        $data['failed_events'] = $msg;
        $data['force_to_email'] = 'ahmed@telecloud.co.za';
        $data['bcc_admin'] = 1;

        erp_process_notification(1, $data);
    }
    // \DB::table('erp_form_events')->whereNotNull('last_failed')->where('last_failed', 'not like', date('Y-m').'%')->update(['last_failed' => null, 'error' => '']);
}

function beforesave_events_check_event_type($request)
{
    if ($request->type == 'format_response') {
        if (!empty($request->id)) {
            $event_count = \DB::connection('default')->table('erp_form_events')->where('module_id', $request->module_id)->where('id', '!=', $request->id)->where('type', 'format_response')->count();
        } else {
            $event_count = \DB::connection('default')->table('erp_form_events')->where('module_id', $request->module_id)->where('type', 'format_response')->count();
        }
        if ($event_count != 0) {
            return 'Only one format_response event can be set per module';
        }
    }

    if ($request->type == 'schedule') {
        if (empty($request->frequency_type)) {
            return 'Frequency type required.';
        }

        if ($request->frequency_type == 'Minutely') {
            if (empty($request->frequency_minute)) {
                return 'Minute required.';
            }
        }

        if ($request->frequency_type == 'Hourly') {
            if (empty($request->frequency_hour)) {
                return 'Hour required.';
            }
        }

        if ($request->frequency_type == 'Daily') {
            if (empty($request->frequency_day)) {
                return 'Daily Frequency required.';
            }
            if (empty($request->frequency_time)) {
                return 'Start time required.';
            }
        }

        if ($request->frequency_type == 'Weekly') {
            if (empty($request->frequency_week)) {
                return 'Day required.';
            }
            if (empty($request->frequency_time)) {
                return 'Start time required.';
            }
        }

        if ($request->frequency_type == 'Monthly') {
            if (empty($request->frequency_month)) {
                return 'Day required.';
            }
            if (empty($request->frequency_time)) {
                return 'Start time required.';
            }
        }

        if (!empty($request->frequency_time)) {
            $minute = substr($request->frequency_time, 3, 2);

            if ($minute == '00' && $request->frequency_type != 'Minutely' && $request->frequency_type != 'Hourly' && $request->frequency_type != 'Yearly') {
                return 'Only Hourly schedules can run on the hour.';
            }
            $timeslot_used = false;

            if ($request->frequency_type != 'Minutely' && $request->frequency_type != 'Hourly' && $request->frequency_type != 'Yearly') {
                if (!empty($request->id)) {
                    $timeslot_used = \DB::table('erp_form_events')->where('id', '!=', $request->id)->where('frequency_time', $request->frequency_time)->count();
                } else {
                    $timeslot_used = \DB::table('erp_form_events')->where('frequency_time', $request->frequency_time)->count();
                }
            }

            if ($timeslot_used) {
                //   return 'Timeslot already in use.';
            }
        }
        if (!empty($request->frequency_time)) {
            $hour = date('H', strtotime($request->frequency_time));

            if ($hour == 4 && $request->function_name != 'schedule_servers_reboot') {
                return 'Event cannot run between 4am and 5am.';
            }
        }
    }
}

function aftersave_events_set_cron($request)
{
    $event = \DB::table('erp_form_events')->where('id', $request->id)->get()->first();
    $data = [];

    $data['frequency_type'] = $request->frequency_type;
    $data['frequency_cron'] = '';
    $data['frequency_minute'] = '';
    $data['frequency_hour'] = '';
    $data['frequency_day'] = '';
    $data['frequency_week'] = '';
    $data['frequency_month'] = '';
    $data['frequency_time'] = '';
    if ($event->type == 'schedule') {
        $data['frequency_minute'] = $request->frequency_minute;
        if ($event->frequency_type == 'Minutely') {
            if ($request->frequency_minute == 1) {
                $data['frequency_cron'] = '* * * * *';
            } else {
                $data['frequency_cron'] = '*/'.$request->frequency_minute.' * * * *';
            }
        }

        if ($event->frequency_type == 'Hourly') {
            $data['frequency_hour'] = $request->frequency_hour;
            if ($request->frequency_hour == 1) {
                $data['frequency_cron'] = '0 * * * *';
            } else {
                $data['frequency_cron'] = '0 */'.$request->frequency_hour.' * * *';
            }
        }
        if ($event->frequency_type == 'Daily') {
            $data['frequency_day'] = $request->frequency_day;
            $data['frequency_time'] = $request->frequency_time;
            $data['frequency_cron'] = date('i', strtotime($request->frequency_time));
            $data['frequency_cron'] .= ' '.date('H', strtotime($request->frequency_time));
            $data['frequency_cron'] .= ' * *';
            if ($data['frequency_day'] == 'Every Day') {
                $data['frequency_cron'] .= ' *';
            } else {
                $data['frequency_cron'] .= ' 1-5';
            }
        }
        if ($event->frequency_type == 'Weekly') {
            $data['frequency_week'] = $request->frequency_week;
            $data['frequency_time'] = $request->frequency_time;
            $data['frequency_cron'] = date('i', strtotime($request->frequency_time));
            $data['frequency_cron'] .= ' '.date('H', strtotime($request->frequency_time));
            $data['frequency_cron'] .= ' * *';
            $data['frequency_cron'] .= ' '.strtoupper(date('D', strtotime($request->frequency_week)));
        }

        if ($event->frequency_type == 'Monthly') {
            $data['frequency_month'] = $request->frequency_month;
            $data['frequency_time'] = $request->frequency_time;
            $data['frequency_cron'] = date('i', strtotime($request->frequency_time));
            $data['frequency_cron'] .= ' '.date('H', strtotime($request->frequency_time));
            $data['frequency_cron'] .= ' '.$request->frequency_month.' * *';
        }

        if ($event->frequency_type == 'Yearly') {
            $data['frequency_cron'] = '0 6 1 1 *';
        }
    }

    \DB::table('erp_form_events')->where('id', $request->id)->update($data);
}

function button_run_schedule($event)
{
    $function_name = $event->function_name;
    $function_name();

    return json_alert('Function called');
}

function button_form_events_run_schedule($request)
{
    $id = $request->id;
    $time_start = microtime(true);
    $workflow = \DB::table('erp_form_events')->where('id', $id)->get()->first();
    $module = \DB::table('erp_cruds')->where('id', $workflow->module_id)->get()->first();
    $function_name = $workflow->function_name;
    $error = false;
    try {
        $function_name();
    } catch (\Throwable $ex) {
        $error = $ex->getMessage();
    }
    $time_end = microtime(true);
    $duration = $time_end - $time_start;
    $data = ['last_run' => date('Y-m-d H:i:s')];
    if ($error) {
        $data['last_failed'] = date('Y-m-d H:i:s');
        $data['last_success'] = null;
        $data['error'] = $error;
    } else {
        $data['last_failed'] = null;
        $data['last_success'] = date('Y-m-d H:i:s');
        $data['error'] = '';
    }
    $result = \DB::table('erp_form_events')->where('id', $id)->update($data);

    if ($data['error'])
        return json_alert($data['error'], 'error');
    else 
        return json_alert('Function called');
}

function button_document_types_rebuild_ledger($request)
{
    $data = [];
    $months = [];
    $months[] = 'All';
    for ($i = 0; $i < 36; ++$i) {
        if ($i == 0) {
            $months[] = date('Y-m');
        } else {
            $months[] = date('Y-m', strtotime('- '.$i.' months'));
        }
    }
    $data['months'] = $months;
    $data['id'] = $request->id;

    return view('__app.button_views.ledger_rebuild', $data);
}

function repost_document_by_id($doctable, $id)
{
    $db = new DBEvent();
    $db->setTable($doctable)->postDocument($id);
    $db->postDocumentCommit();
}

function repost_document_by_ids($doctable, $ids)
{
    $db = new DBEvent();
    foreach ($ids as $id) {
        $db->setTable($doctable)->postDocument($id);
    }
    $db->postDocumentCommit();
}

function repost_document_by_account_id($account_id)
{
    $db = new DBEvent();
    $doctypes = \DB::table('acc_doctypes')->get();
    $ledgers = \DB::table('acc_ledgers')->where('account_id', $account_id)->get();
    foreach ($ledgers as $l) {
        $doctable = $doctypes->where('doctype', $l->doctype)->pluck('doctable')->first();
        $db->setTable($doctable)->postDocument($l->docid);
    }
    $db->postDocumentCommit();
}

function repost_documents($doctype_id = false, $period = 'all', $document_id = false)
{
    set_time_limit(0);
    $db = new DBEvent();

    if ('all' == $period) {
        $first_day = '1900-01-01';
        $last_day = '2100-01-01';
    } else {
        $first_day = date('Y-m-01', strtotime($period));
        $last_day = date('Y-m-t', strtotime($period));
    }

    if (!$doctype_id) {
        $doctables = \DB::select('select doctable, doctype from acc_doctypes where status!="Deleted" order by sort_order');
    } else {
        $doctables = \DB::select('select doctable, doctype from acc_doctypes where status!="Deleted" and id='.$doctype_id.' order by sort_order');
    }

    $first_transaction_date = \DB::table('acc_cashbook_transactions')->orderby('docdate', 'asc')->pluck('docdate')->first();
    // $doctables = \DB::select('select * from acc_doctypes where doctype = "Cashbook Expense"');
    foreach ($doctables as $doctable) {
        if ($doctable->doctable == 'acc_general_journals') {
            DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docdate', '>=', $first_day)->where('docdate', '<=', $last_day)->delete();
            $documents = DB::select('select * from '.$doctable->doctable.'  ');
        } else {
            if ($document_id) {
                DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docid', $document_id)->delete();
                $documents = DB::select('select * from '.$doctable->doctable.' where id = "'.$document_id.'" ');
            } elseif ($doctable->doctable == 'acc_ledger_accounts') {
                DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docdate', '>=', $first_day)->where('docdate', '<=', $last_day)->delete();
                $documents = DB::select('select * from '.$doctable->doctable.' where doctype = "'.$doctable->doctype.'" ');
            } else {
                DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docdate', '>=', $first_day)->where('docdate', '<=', $last_day)->delete();

                $documents = DB::select('select * from '.$doctable->doctable.' where doctype = "'.$doctable->doctype.'" and docdate >= "'.$first_day.'" and docdate <= "'.$last_day.'" ');
            }
        }
        $db->setTable($doctable->doctable);

        foreach ($documents as $document) {
            if ($doctable->doctable == 'acc_ledger_accounts') {
                $ledger = $db->postDocument($document->id, $first_transaction_date);
            } else {
                $ledger = $db->postDocument($document->id);
            }
        }

        $db->postDocumentCommit();
    }
}

function repost_documents_by_year($doctype_id, $year)
{
    set_time_limit(0);
    $db = new DBEvent();

    $first_day = $year.'-01-01';
    $last_day = $year.'-12-31';

    if (!$doctype_id) {
        $doctables = \DB::select('select doctable, doctype from acc_doctypes where status!="Deleted" order by sort_order');
    } else {
        $doctables = \DB::select('select doctable, doctype from acc_doctypes where status!="Deleted" and id='.$doctype_id.' order by sort_order');
    }

    $first_transaction_date = \DB::table('acc_cashbook_transactions')->orderby('docdate', 'asc')->pluck('docdate')->first();
    // $doctables = \DB::select('select * from acc_doctypes where doctype = "Cashbook Expense"');
    foreach ($doctables as $doctable) {
        if ($doctable->doctable == 'acc_general_journals') {
            DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docdate', '>=', $first_day)->where('docdate', '<=', $last_day)->delete();
            $documents = DB::select('select * from '.$doctable->doctable.' where docdate >= "'.$first_day.'" and docdate <= "'.$last_day.'" ');
        } else {
            if ($document_id) {
                DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docid', $document_id)->delete();
                $documents = DB::select('select * from '.$doctable->doctable.' where id = "'.$document_id.'" ');
            } elseif ($doctable->doctable == 'acc_ledger_accounts') {
                DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->delete();
                $documents = DB::select('select * from '.$doctable->doctable.' where doctype = "'.$doctable->doctype.'" ');
            } else {
                DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docdate', '>=', $first_day)->where('docdate', '<=', $last_day)->delete();

                $documents = DB::select('select * from '.$doctable->doctable.' where doctype = "'.$doctable->doctype.'" and docdate >= "'.$first_day.'" and docdate <= "'.$last_day.'" ');
            }
        }
        
        $db->setTable($doctable->doctable);
        // aa($documents);
        foreach ($documents as $document) {
            if ($doctable->doctable == 'acc_ledger_accounts') {
                $ledger = $db->postDocument($document->id, $first_transaction_date);
            } else {
                $ledger = $db->postDocument($document->id);
            }
        }

        $db->postDocumentCommit();
    }
}

function repost_invoices_by_year($year, $doctype = false)
{
    $db = new DBEvent();
    $doctype_id = 9;
    if ($doctype) {
        $doctables = \DB::select('select * from acc_doctypes where doctype = "'.$doctype.'"');
    } else {
        $doctables = \DB::select('select doctable, doctype from acc_doctypes where id='.$doctype_id.' order by sort_order');
    }
    foreach ($doctables as $doctable) {
        DB::table('acc_ledgers')->where('doctype', $doctable->doctype)->where('docdate', 'like', $year.'%')->delete();
        $documents = DB::select('select * from '.$doctable->doctable.' where doctype = "'.$doctable->doctype.'" and docdate like "'.$year.'%" ');

        foreach ($documents as $document) {
            $ledger = $db->setTable($doctable->doctable)->postDocument($document->id);
        }
        $db->postDocumentCommit();
    }
}

function schedule_repost_documents_weekly()
{
    $doctypes = \DB::table('acc_doctypes')->get();
    foreach ($doctypes as $doctype) {
        try {
            repost_documents($doctype->id, date('Y-m-d'));
        } catch (\Throwable $ex) {
            exception_email($ex, 'doctype repost failed - '.$doctype->doctype);
        }
    }
}

function repost_documents_complete()
{
    $doctypes = \DB::table('acc_doctypes')->get();
    foreach ($doctypes as $doctype) {
        repost_documents($doctype->id, 'all');
    }
}

function schedule_data_reconcile_reminder()
{
    if (session('instance')->directory == 'telecloud') {
        $data['function_name'] = __FUNCTION__;
        erp_process_notification(1, $data);
    }
}

function button_events_view_email($request)
{
    $event = \DB::table('erp_form_events')->where('id', $request->id)->get()->first();
    if ($event->email_id > 0) {
        $emails_url = get_menu_url_from_table('crm_email_manager');

        return redirect()->to($emails_url.'?id='.$event->email_id);
    }

    return json_alert('No email attached to event', 'warning');
}

function button_exceptions_view_stack($request)
{
    $log = \DB::connection('system')->table('erp_exception_log')->where('id', $request->id)->get()->first();

    echo '<div class="card">';
    echo '<div class="card-header">';
    echo $log->error_message.' - '.$log->created_at;
    echo '</div>';
    echo '<div class="card-body">';
    echo '<code>'.$log->stack_trace.'</code>';
    echo '</div>';
    echo '</div>';
}

function button_exceptions_clear($request)
{
    \DB::connection('system')->table('erp_exception_log')->truncate();

    return json_alert('Log cleared');
}

function schedule_reset_failed_events()
{
    \DB::connection('default')->table('erp_form_events')
        ->where('frequency_cron', '>', '')
        ->where('active', 1)
        ->where('type', 'schedule')->update(['started' => 0, 'completed' => 1]);
}

function beforesave_set_function_name($request)
{
    if (empty($request->name)) {
        return 'Name required';
    }
    if (empty($request->type)) {
        return 'Type required';
    }

    if (empty($request->function_name)) {
        if (!empty($request->id)) {
            $beforesave_row = session('event_db_record');
            $function_name = str_replace(' ', '_', strtolower(trim($request->type).' '.trim($request->name)));
            if ($beforesave_row->function_name != $function_name) {
                if (function_exists($function_name)) {
                    return 'Function name already in use';
                }
            }
        }

        $function_name = str_replace(' ', '_', strtolower(trim($request->type).' '.trim($request->name)));
        $request->request->add(['function_name' => $function_name]);
    }
}

function button_events_set_email_subject($request)
{
    $event = \DB::table('erp_form_events')->where('id', $request->id)->get()->first();
    \DB::table('crm_email_manager')->where('id', $event->email_id)->update(['name' => $event->name]);

    return json_alert('Done');
}