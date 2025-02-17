<?php

function schedule_outbound_cdr_check()
{
    if (is_main_instance()) {
        if (date('H') >= 1 && date('H') <= 5) {
            $cdr_count = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('hangup_time', '>', date('Y-m-d H:i:s', strtotime('-40 minutes')))->limit(1)->count();
        } else {
            $cdr_count = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('hangup_time', '>', date('Y-m-d H:i:s', strtotime('-20 minutes')))->limit(1)->count();
        }

        if (! $cdr_count) {
            admin_email('CDR not inserting new records');
            queue_sms(12, '0824119555', 'CDR not inserting new records. '.date('Y-m-d H:i:s'), 1, 1);
        }
    }
}

function schedule_update_rejected_calls()
{

    \DB::connection('pbx_cdr')->table('call_records_rejected')->truncate();

    $sql = "INSERT IGNORE INTO call_records_rejected 
    SELECT *
    FROM call_records_outbound 
    WHERE hangup_time >= NOW() - INTERVAL 30 MINUTE
    AND hangup_time <= NOW()
    AND hangup_cause = 'CALL_REJECTED';";
    //dd($sql);
    \DB::connection('pbx_cdr')->statement($sql);
}

function schedule_create_cdr_log_tasks()
{

    $cmd = 'cat /var/www/html/lua/log_out.log';
    $log_out_log = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    $cmd = 'cat /var/www/html/lua/log_in.log';
    $log_in_log = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    $files = [];
    if ($log_out_log > '') {
        // Maximum length allowed for a MySQL TEXT field
        $maxTextLength = 65535;

        // Truncate the string if it exceeds the maximum length
        if (strlen($log_out_log) > $maxTextLength) {
            $truncatedString = substr($log_out_log, 0, $maxTextLength);
        } else {
            $truncatedString = $log_out_log;
        }
        $truncatedString = trim($truncatedString);
        if ($truncatedString > '') {
            admin_email('cdr log_out.log', $truncatedString);
        }
    }
    if ($log_in_log > '') {
        // Maximum length allowed for a MySQL TEXT field
        $maxTextLength = 65535;

        // Truncate the string if it exceeds the maximum length
        if (strlen($log_in_log) > $maxTextLength) {
            $truncatedString = substr($log_in_log, 0, $maxTextLength);
        } else {
            $truncatedString = $log_in_log;
        }
        $truncatedString = trim($truncatedString);
        if ($truncatedString > '') {
            admin_email('cdr log_in.log', $truncatedString);

        }
    }

    $cmd = 'echo "" > /var/www/html/lua/log_out.log';
    \Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    $cmd = 'echo "" > /var/www/html/lua/log_in.log';
    \Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

}

function schedule_pbx_domains_set_last_calls()
{
    $query = \DB::connection('pbx_cdr')->table('call_records_outbound')
        ->select('domain_name', 'start_time')
        ->orderBy('domain_name')
        ->orderByDesc('id')
        ->distinct('domain_name');

    $results = $query->get();
    foreach ($results as $r) {
        \DB::connection('pbx')->table('v_domains')->where('domain_name', $r->domain_name)->update(['last_outbound_call' => $r->start_time]);
    }
    $query = \DB::connection('pbx_cdr')->table('call_records_inbound')
        ->select('domain_name', 'start_time')
        ->orderBy('domain_name')
        ->orderByDesc('id')
        ->distinct('domain_name');

    $results = $query->get();
    foreach ($results as $r) {
        \DB::connection('pbx')->table('v_domains')->where('domain_name', $r->domain_name)->update(['last_inbound_call' => $r->start_time]);
    }
    \DB::connection('pbx')->statement('UPDATE p_phone_numbers SET last_outbound_call=null,last_inbound_call=null WHERE domain_uuid is null');
    \DB::connection('pbx')->statement('UPDATE p_phone_numbers AS pp
    JOIN v_domains AS v ON pp.domain_uuid = v.domain_uuid
    SET pp.last_outbound_call = v.last_outbound_call,
    pp.last_inbound_call = v.last_inbound_call;');
}

function schedule_outbound_cdr_set_rand_cost()
{
    if (is_main_instance()) {
        $usd_exchange_rate = get_exchange_rate(null, 'USD', 'ZAR');

        if ($usd_exchange_rate) {
            \DB::connection('pbx_cdr')->table('call_records_outbound')
                ->where('rand_cost', 0)
                ->where('cost', '>', 0)
                ->where('currency', 'usd')
                ->update(['rand_cost' => \DB::raw('cost*'.$usd_exchange_rate)]);
        }
        \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('rand_cost', 0)
            ->where('cost', '>', 0)
            ->where('currency', 'zar')
            ->update(['rand_cost' => \DB::raw('cost')]);

    }
}

function schedule_check_pbx_db()
{
    if (is_main_instance()) {

        try {
            $pbx_connect = \DB::connection('pbx')->table('v_domains')->select('domain_uuid')->limit(1)->count();
            setEnv('PBX_DB_OFFLINE', 0);
        } catch (\Throwable $ex) {
            setEnv('PBX_DB_OFFLINE', 1);
            queue_sms(12, '0824119555', 'PBX DB connection error . '.date('Y-m-d H:i:s').' '.$ex->getMessage(), 1, 1);
        }
    }
}

function schedule_cdr_archive_summary_update()
{
    $import_tables = ['call_records_outbound_lastmonth'];

    // uncomment to redo import all tables
    $cdr_archive_tables = get_tables_from_schema('pbx_cdr');
    $import_tables = [];
    foreach ($cdr_archive_tables as $table) {
        if ($table == 'call_records_outbound_lastmonth') {
            $import_tables[] = $table;
        } elseif (str_contains($table, date('Y'))) {
            // $import_tables[] = $table;
        }
    }

    foreach ($import_tables as $table) {
        if ($table == 'call_records_outbound_lastmonth') {
            $period = date('Y-m', strtotime('previous month'));
        } else {
            $period = date('Y-m', strtotime(str_replace('call_records_', '', $table)));
        }
        \DB::connection('pbx')->table('mon_cdr_archive_summary')->where('period', $period)->delete();

        $cols = get_columns_from_schema($table, null, 'pbx_cdr');
        $destination_field = null;
        if (in_array('dest_summary', $cols)) {
            $destination_field = 'dest_summary';
        } elseif (in_array('summary_destination', $cols)) {
            $destination_field = 'summary_destination';
        } elseif (in_array('product_code', $cols)) {
            $destination_field = 'product_code';
        }
        if ($destination_field) {
            if (! in_array('currency', $cols)) {
                $summaries = \DB::connection('pbx_cdr')->table($table)
                    ->select(\DB::raw('sum(cost) as cost'), \DB::raw('(sum(duration)/60) as minutes'), 'gateway', $destination_field.' as dest')
                    ->where('gateway', '>', '')
                    ->groupBy('gateway')->groupBy($destination_field)
                    ->get();
            } else {
                $summaries = \DB::connection('pbx_cdr')->table($table)
                    ->select(\DB::raw('sum(cost) as cost'), \DB::raw('(sum(duration)/60) as minutes'), 'gateway', 'currency', $destination_field.' as dest')
                    ->where('gateway', '>', '')
                    ->groupBy('gateway')->groupBy($destination_field)->groupBy('currency')
                    ->get();
            }
            foreach ($summaries as $summary) {
                $data = [];
                if (! in_array('currency', $cols)) {
                    $currency = 'zar';
                } else {
                    $currency = $summary->currency;
                }
                if (empty($currency)) {
                    $currency = 'zar';
                }
                $currency = strtolower($currency);
                $data['period'] = $period;
                $data['gateway'] = $summary->gateway;
                $data['destination'] = $summary->dest;
                $data['minutes'] = $summary->minutes;
                $data['cost'] = $summary->cost;
                $data['currency'] = $currency;

                \DB::connection('pbx')->table('mon_cdr_archive_summary')->insert($data);
            }
        }
    }
}

function vodacom_cdr()
{
    $filename = public_path().'/vodacom_cdr.xlsx';

    $records = (new \Rap2hpoutre\FastExcel\FastExcel)->import($filename, function ($line) {
        if (! empty($line['Call Time'])) {
            $data = [
                'call_datetime' => $line['Call Date']->format('Y-m-d').' '.date('H:i', strtotime($line['Call Time'])),
                'dialling_number' => trim($line['Dialling No']),
                'dialled_number' => trim($line['No Dialled']),
                'cost' => trim($line['Cost']),
                'duration' => trim($line['Duration']),
                'description' => trim($line['Call Description']),
                'vas_description' => trim($line['VAS Description']),
            ];

            \DB::connection('pbx_cdr')->table('vodacom_cdr')->insert($data);
        }
    });
}

function set_cdr_admin_cost()
{
    return false;
    $costs = \DB::connection('pbx')->table('p_rates_summary')->where('active', 1)->where('destination', 'mobile vodacom')->where('country', 'south africa')->get();
    foreach ($costs as $cost) {
        \DB::connection('pbx_cdr')->table('call_records_outbound')->where('currency', 'zar')->where('destination', $cost->destination)->update(['admin_rate' => $cost->cost_zar]);
        \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->where('currency', 'zar')->where('destination', $cost->destination)->update(['admin_rate' => $cost->cost_zar]);
        \DB::connection('pbx_cdr')->table('call_records_outbound')->where('currency', 'usd')->where('destination', $cost->destination)->update(['admin_rate' => $cost->cost_usd]);
        \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->where('currency', 'usd')->where('destination', $cost->destination)->update(['admin_rate' => $cost->cost_usd]);
        \DB::connection('pbx_cdr')->table('call_records_outbound')->where('destination', $cost->destination)->update(['gateway_rate' => $cost->cost_zar]);
        \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->where('destination', $cost->destination)->update(['gateway_rate' => $cost->cost_zar]);
    }

    \DB::connection('pbx_cdr')->table('call_records_outbound')->update(['gateway_cost' => \DB::raw('(ceil(((duration * gateway_rate) / 60)*100)/100)')]);
    \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->update(['gateway_cost' => \DB::raw('(ceil(((duration * gateway_rate) / 60)*100)/100)')]);
    \DB::connection('pbx_cdr')->table('call_records_outbound')->update(['admin_cost' => \DB::raw('((duration * admin_rate) / 60)')]);
    \DB::connection('pbx_cdr')->table('call_records_outbound')->update(['admin_gp' => \DB::raw('(cost - admin_cost)'), 'admin_gpp' => \DB::raw('((100 * (cost - admin_cost)) / admin_cost)')]);
    \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->update(['admin_cost' => \DB::raw('((duration * admin_rate) / 60)')]);
    \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->update(['admin_gp' => \DB::raw('(cost - admin_cost)'), 'admin_gpp' => \DB::raw('((100 * (cost - admin_cost)) / admin_cost)')]);
}

function button_gateways_export_cdr($request)
{
    $data['gateway_uuid'] = $request->id;
    $tables = get_tables_from_schema('pbx_cdr');
    $data['cdr_tables'] = [];
    foreach ($tables as $t) {
        if (! str_starts_with($t, 'call_records_')) {
            continue;
        }
        if (str_contains($t, 'inbound')) {
            continue;
        }
        $data['cdr_tables'][] = $t;
    }

    return view('__app.button_views.export_gateway_cdr', $data);
}

function export_cdr_gateway($gateway, $tables = false)
{
    ini_set('memory_limit', '4048M');
    ini_set('max_execution_time', 360);
    if (! is_array($tables)) {
        $tables = ['call_records_outbound'];
    }

    $file_title = $gateway.' Call Records';
    $records = [];
    foreach ($tables as $table) {
        $records[$table] = \DB::connection('pbx_cdr')->table($table)->where('gateway', $gateway)->get()->toArray();
    }
    if ($tables[0] == 'call_records_outbound') {
        $sheet_name = 'Call Records '.date('M Y');
    } elseif ($tables[0] == 'call_records_outbound_lastmonth') {
        $sheet_name = 'Call Records '.date('M Y', strtotime('-1 month'));
    } else {
        $month = str_replace('call_records_', '', $tables[0]);
        $sheet_name = 'Call Records '.date('M Y', strtotime($month));
    }

    $export = new App\Exports\CollectionExport;
    $export->setData($records[$table]);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

    return $file_title.'.xlsx';
}

function schedule_airtime_depleted_notification()
{
    $processed_domains = [];
    $pbx_domains = \DB::connection('pbx')->table('v_domains')->where('unlimited_channels', 0)->where('domain_name', '!=', 'lti.cloudtools.co.za')->pluck('domain_name')->toArray();

    $pbx_count = \DB::connection('default')->table('isp_voice_pbx_domains')->count();
    if (! $pbx_count) {
        return false;
    }

    $v_domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($v_domains as $v_domain) {
        \DB::connection('default')->table('isp_voice_pbx_domains')->where('pabx_domain', $v_domain->domain_name)->update(['pbx_balance' => $v_domain->balance]);
    }

    $hangup_cause = 'BLOCKED_NO_AIRTIME';

    $cdr_records = \DB::connection('pbx_cdr')->table('call_records_outbound')->select('domain_name')
        ->where('hangup_time', '>', date('Y-m-d H:i:s', strtotime('-1 hour')))->where('hangup_cause', $hangup_cause)
        ->whereIn('domain_name', $pbx_domains)
        ->groupBy('domain_name')->get();

    foreach ($cdr_records as $cdr) {
        $domain = \DB::connection('default')->table('isp_voice_pbx_domains')->where('pabx_domain', $cdr->domain_name)->get()->first();
        if ($domain) {
            $processed_domains[] = $cdr->domain_name;
            $data = [];
            $data['domain_name'] = $domain->pabx_domain;
            $data['function_name'] = __FUNCTION__;
            $account = dbgetaccount($domain->account_id);
            $account_id = $account->id;
            if ($account->partner_id != 1) {
                $account_id = $account->partner_id;
            }
            //$data['test_debug'] =1;
            $function_variables = [];
            $conn = $domain->erp;
            if ($domain->cost_calculation == 'volume') {
                $data['bcc_admin'] = true;
            }
            $data['airtime_msg'] = 'Your airtime is depleted.<br>Please login to the portal and purchase airtime.';
            erp_process_notification($account_id, $data, $function_variables, $conn);
        }
    }

    $domains = \DB::connection('pbx')->table('v_domains')
        ->whereNotIn('domain_name', $processed_domains)
        ->where('balance', '<', 10)
        ->whereIn('domain_name', $pbx_domains)
        ->get();

    foreach ($domains as $domain) {
        $data = [];
        $data['domain_name'] = $domain->domain_name;
        $data['function_name'] = __FUNCTION__;
        $account = dbgetaccount($domain->account_id);
        $account_id = $account->id;
        if ($account->partner_id != 1) {
            $account_id = $account->partner_id;
        }
        //$data['test_debug'] =1;
        $function_variables = [];
        $conn = $domain->erp;
        if ($domain->cost_calculation == 'volume') {
            $data['bcc_admin'] = true;
        }
        $data['email_subject'] = 'Low Airtime Balance - '.$domain->domain_name;
        $data['airtime_msg'] = '<b>Airtime Balance: '.$domain->balance.'</b> <br>Your airtime is low, please login to the portal or reply this email to purchase airtime.';
        //$data['test_debug'] = 1;
        erp_process_notification($account_id, $data, $function_variables, $conn);
    }
}

function button_outbound_cdr_export_customer_lastmonth($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }

    $file_name = export_cdr_table('reseller', session('account_id'), 'call_records_outbound_lastmonth', $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_outbound_cdr_export_customer($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }

    $file_name = export_cdr_table('reseller', session('account_id'), 'call_records_outbound', $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_outbound_cdr_export_partner($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }

    $file_name = export_cdr_table('reseller', session('account_id'), 'call_records_outbound', $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_outbound_cdr_export($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }
    $file_name = export_cdr_table('customer', session('pbx_account_id'), 'call_records_outbound', $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_inbound_cdr_export_partner($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }
    $file_name = export_cdr_table('reseller', session('account_id'), 'call_records_inbound', $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_inbound_cdr_export($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }
    $file_name = export_cdr_table('customer', session('pbx_account_id'), 'call_records_inbound', $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_outbound_last_cdr_export_partner($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }
    aa($request->all());
    aa($where);
    aa($request_object);
    $file_name = export_cdr_table('reseller', session('account_id'), 'call_records_outbound_lastmonth', $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_outbound_last_cdr_export($request)
{
    $where = false;
    $request_object = false;
    if (! empty($request->grid_filters)) {
        $where = json_decode($request->grid_filters, true);
    }

    if ($where) {
        $request_object = new \Illuminate\Http\Request;
        $request_object->setMethod('POST');

        $request_object->request->add($where);
    }
    aa($request->all());
    aa($where);
    aa($request_object);

    $table = 'call_records_outbound_lastmonth';

    $file_name = export_cdr_table('customer', session('pbx_account_id'), $table, $request_object);

    return json_alert(attachments_url().$file_name, 'reload');
}

function button_cdr_set_archive_table($request)
{
    $tables = get_tables_from_schema('pbx_cdr');
    foreach ($tables as $i => $table) {
        if (! str_contains($table, 'call_records_20')) {
            unset($tables[$i]);
        }
    }

    $archives = [];
    foreach ($tables as $i => $table) {
        $archives[] = ['table_name' => $table, 'table_label' => 'Call Records '.date('Y-m', strtotime(str_replace('call_records_', '', $table))), 'table_date' => date('Y-m', strtotime(str_replace('call_records_', '', $table)))];
    }

    $cdr_tables = collect($archives)->sortBy('table_date')->toArray();

    $data['cdr_tables'] = array_values($cdr_tables);

    return view('__app.button_views.cdr_archive_table', $data);
}

function button_cdr_archive_table_clear($request)
{
    session(['cdr_archive_table' => false]);

    return json_alert('Archive table cleared.', 'reload');
}

function schedule_wholesale_cdr_email()
{
    if (is_main_instance()) {
        // DAILY CALLS BY CAUSE
        $v_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->get();
        //$v_domains = \DB::connection('pbx')->table('v_domains')->where('domain_name', 'vca.cloudtools.co.za')->get();
        foreach ($v_domains as $domain) {
            $data = [];
            $file = export_hangupcause_cdr($domain->domain_name, date('Y-m-d H:i:s', strtotime('-24 hours')));
            if (! $file) {
                continue;
            }
            $data['files'][] = $file;
            $data['domain_name'] = $domain->domain_name;
            $data['function_name'] = __FUNCTION__;
            $account = dbgetaccount($domain->account_id);
            $account_id = $account->id;
            if ($account->partner_id != 1) {
                $account_id = $account->partner_id;
            }
            //$data['test_debug'] =1;
            $function_variables = [];
            // $data['bcc_admin'] = true;
            //$data['test_debug'] = 1;
            erp_process_notification($account_id, $data, $function_variables);

        }
    }
}

function export_hangupcause_cdr($domain, $hangup_time)
{
    ini_set('memory_limit', '4048M');
    ini_set('max_execution_time', 360);

    $records = \DB::connection('pbx_cdr')->table('call_records_outbound')->select('hangup_cause', \DB::raw('count(*) as call_count'), \DB::raw('sum(duration) as minutes'))
        ->where('hangup_time', '>', date('Y-m-d H:i:s', strtotime($hangup_time)))
        ->where('domain_name', $domain)
        ->groupBy('hangup_cause')
        ->orderBy('call_count')->orderBy('hangup_cause')->get()->toArray();

    if (count($records) == 0) {
        return false;
    }

    $file_title = 'Daily Calls by Cause - '.$domain;
    $file_name = $file_title.'.xlsx';
    $rows = json_decode(json_encode($records), true);

    foreach ($rows as $i => $row) {
        $rows[$i]['minutes'] = ($rows[$i]['minutes'] > 0) ? currency($rows[$i]['minutes'] / 60) : '0';
    }

    $export = new App\Exports\CollectionExport;
    $export->setData($rows);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

    return public_path('attachments/'.session('instance')->directory.'/').$file_name;
}

function button_pbxdomains_six_months_usage($request)
{

    $domain = \DB::connection('pbx')->table('v_domains')->where('id', $request->id)->get()->first();
    $domain_name = $domain->domain_name;
    $extension_list = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->pluck('extension')->toArray();
    $tables = get_tables_from_schema('pbx_cdr');
    $result = '<div class="card"><div class="card-header">'.$domain_name.' - Usage</div><div class="card-body">';

    $date_start = date('Y-m-01', strtotime('-1 months'));
    $date_end = date('Y-m-01', strtotime('-6 months'));

    $months[] = date('Y-m-01', strtotime($date_start));

    while ($date_start != $date_end) {
        $date_start = date('Y-m-01', strtotime($date_start.' -1 month'));
        $months[] = date('Y-m-01', strtotime($date_start));
    }

    foreach ($months as $month) {
        $cdr_month = date('Y-m-01', strtotime($month));
        if (date('Y-m', strtotime('-1 month')) == date('Y-m', strtotime($cdr_month))) {
            $cdr_table = 'call_records_outbound_lastmonth';
        } elseif (date('Y-m-01', strtotime($cdr_month)) != date('Y-m-01')) {
            $cdr_table = 'call_records_outbound_archive_'.strtolower(date('YM', strtotime($cdr_month)));
        }

        foreach ($extension_list as $extension) {
            if (\Schema::connection('pbx_cdr')->hasTable($cdr_table)) {
                $sec_total = \DB::connection('pbx_cdr')->table($cdr_table)
                    ->where('domain_name', $domain_name)
                    ->where('extension', $extension)
                    ->sum(\DB::raw('duration'));
                $lastmonth_minutes_total = $sec_total / 60;
                $result .= '<p>'.$extension.' Call Records '.date('Y-m', strtotime($cdr_month)).' - '.currency($lastmonth_minutes_total).' minutes</p>';
            }
        }
    }

    $result .= '</div></div>';
    echo $result;

}

function vox_import()
{

    //return false;
    set_time_limit(900);

    \DB::connection('pbx_cdr')->table('vox_cdr')->truncate();
    //dd(1);
    //return false;

    $csv = (new Rap2hpoutre\FastExcel\FastExcel)->import(public_path().'/vox.xlsx');
    foreach ($csv as $line) {

        $cost = trim(str_replace('R', '', $line['BillCost']));
        if (empty($cost)) {
            $cost = 0;
        }

        $data = [
            'call_date' => $line['CallConnected'],
            'caller_id_number' => trim($line['CallSource']),
            'callee_id_number' => trim($line['DialledNumber']),
            'vox_duration' => $line['Seconds'],
            'vox_rate' => 0,
            'vox_destination' => $line['Description'],
            'vox_cost' => $cost,
            'record_exists' => 0,
            'cdr_duration' => 0,
            'cdr_cost' => 0,
            'cost_match' => 0,
            'duration_match' => 0,
            'cdr_rate' => 0,

        ];

        $insert_data[] = $data;

    }

    $nlist = collect($insert_data); // Make a collection to use the chunk method

    // it will chunk the dataset in smaller collections containing 500 values each.
    // Play with the value to get best result
    $chunks = $nlist->chunk(500);

    foreach ($chunks as $chunk) {
        \DB::connection('pbx_cdr')->table('vox_cdr')->insert($chunk->toArray());
    }
    \DB::connection('pbx_cdr')->table('vox_cdr')->where('vox_cost', '>', 0)->update(['vox_rate' => \DB::raw('(vox_cost/vox_duration)*60')]);
    //  \DB::connection('pbx_cdr')->table('vox_cdr')->where('vox_cost','>',0)->update(['vox_rate'=>\DB::raw('vox_cost/vox_duration*60')]);
    /*
    $batchSize = 100; // Adjust the batch size based on your performance needs
     $limit = 1000;
DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth_copy')
     ->where('gateway', 'vox')
     ->limit($limit)
     ->chunkById($batchSize, function ($callRecords) {
         foreach ($callRecords as $record) {
             DB::connection('pbx_cdr')->table('vox_cdr')
                 ->where('checked', 0)
                 ->where('source', $record->caller_id_number)
                 ->where('destination', $record->callee_id_number)
                 ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, call_date, ?)) <= 240', [$record->start_time])
                 ->update([
                     'cdr_cost' => $record->gateway_cost,
                     'cdr_rate' => $record->gateway_rate,
                     'cdr_duration' => $record->duration,
                     'checked' => 1,
                 ]);
         }
     });
     */

    $sql = "UPDATE vox_cdr
    JOIN call_records_outbound_lastmonth_copy ON vox_cdr.caller_id_number = call_records_outbound_lastmonth_copy.caller_id_number
    AND vox_cdr.callee_id_number = call_records_outbound_lastmonth_copy.callee_id_number
    AND ABS(TIMESTAMPDIFF(SECOND, vox_cdr.call_date, call_records_outbound_lastmonth_copy.start_time)) <= 60
    AND call_records_outbound_lastmonth_copy.gateway = 'VOX'
    SET vox_cdr.cdr_cost = call_records_outbound_lastmonth_copy.gateway_cost,
    vox_cdr.cdr_rate = call_records_outbound_lastmonth_copy.gateway_rate,
    vox_cdr.cdr_destination = call_records_outbound_lastmonth_copy.destination,
    vox_cdr.cdr_summary_destination = call_records_outbound_lastmonth_copy.summary_destination,
    vox_cdr.cdr_duration = call_records_outbound_lastmonth_copy.duration,
    vox_cdr.checked = 1
    WHERE vox_cdr.checked = 0;";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE vox_cdr set record_exists=1 where checked=1;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE vox_cdr set cost_match=1 where cdr_cost=vox_cost;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE vox_cdr set duration_match=1 where cdr_duration=vox_duration;';
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_gnp_1 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '271%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_gnp_2 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '272%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_gnp_3 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '273%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_gnp_4 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '274%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_gnp_5 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '275%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_crdb_6 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '276%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_crdb_7 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '277%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.vox_cdr AS b
    JOIN porting.p_ported_numbers_crdb_8 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '278%';";
    \DB::connection('pbx_cdr')->statement($sql);

}

function bvs_import()
{

    //return false;
    set_time_limit(900);

    // \DB::connection('pbx_cdr')->table('bvs_cdr')->truncate();
    //return false;

    $csv = (new Rap2hpoutre\FastExcel\FastExcel)->import(public_path().'/bvs_last.xlsx');
    foreach ($csv as $line) {

        $cost = trim(str_replace('R', '', $line['Cost']));
        if (empty($cost)) {
            $cost = 0;
        }

        $data = [
            'call_date' => $line['Setup Time'],
            'callee_id_number' => $line['CLD'],
            'caller_id_number' => $line['CLI'],
            'bvs_duration' => $line['Duration, sec'],
            'bvs_rate' => $line['Minute Rate'],
            'bvs_destination' => $line['Description'],
            'bvs_cost' => $cost,
            'record_exists' => 0,
            'cdr_duration' => 0,
            'cdr_cost' => 0,
            'cost_match' => 0,
            'duration_match' => 0,
            'cdr_rate' => 0,

        ];

        $insert_data[] = $data;

    }

    $nlist = collect($insert_data); // Make a collection to use the chunk method

    // it will chunk the dataset in smaller collections containing 500 values each.
    // Play with the value to get best result
    $chunks = $nlist->chunk(500);

    foreach ($chunks as $chunk) {
        \DB::connection('pbx_cdr')->table('bvs_cdr')->insert($chunk->toArray());
    }

    //  \DB::connection('pbx_cdr')->table('bvs_cdr')->where('bvs_cost','>',0)->update(['bvs_rate'=>\DB::raw('bvs_cost/bvs_duration*60')]);
    /*
    $batchSize = 100; // Adjust the batch size based on your performance needs
     $limit = 1000;
DB::connection('pbx_cdr')->table('call_records_outbound_copy')
     ->where('gateway', 'BVS')
     ->limit($limit)
     ->chunkById($batchSize, function ($callRecords) {
         foreach ($callRecords as $record) {
             DB::connection('pbx_cdr')->table('bvs_cdr')
                 ->where('checked', 0)
                 ->where('source', $record->caller_id_number)
                 ->where('destination', $record->callee_id_number)
                 ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, call_date, ?)) <= 240', [$record->start_time])
                 ->update([
                     'cdr_cost' => $record->gateway_cost,
                     'cdr_rate' => $record->gateway_rate,
                     'cdr_duration' => $record->duration,
                     'checked' => 1,
                 ]);
         }
     });
     */

    $sql = "UPDATE bvs_cdr
    JOIN call_records_outbound_lastmonth_copy ON bvs_cdr.caller_id_number = call_records_outbound_lastmonth_copy.caller_id_number
    AND bvs_cdr.callee_id_number = call_records_outbound_lastmonth_copy.callee_id_number
    AND ABS(TIMESTAMPDIFF(SECOND, bvs_cdr.call_date, call_records_outbound_lastmonth_copy.start_time)) <= 10
    AND call_records_outbound_lastmonth_copy.gateway = 'BVS'
    SET bvs_cdr.cdr_cost = call_records_outbound_lastmonth_copy.gateway_cost,
    bvs_cdr.cdr_rate = call_records_outbound_lastmonth_copy.gateway_rate,
    bvs_cdr.cdr_destination = call_records_outbound_lastmonth_copy.destination,
    bvs_cdr.cdr_summary_destination = call_records_outbound_lastmonth_copy.summary_destination,
    bvs_cdr.cdr_duration = call_records_outbound_lastmonth_copy.duration,
    bvs_cdr.checked = 1
    WHERE bvs_cdr.checked = 0;";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE bvs_cdr set difference=bvs_cost-cdr_cost';
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE bvs_cdr set record_exists=1 where checked=1;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE bvs_cdr set cost_match=1 where cdr_cost=bvs_cost;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE bvs_cdr set duration_match=1 where cdr_duration=bvs_duration;';
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_gnp_1 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '271%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_gnp_2 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '272%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_gnp_3 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '273%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_gnp_4 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '274%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_gnp_5 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '275%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_crdb_6 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '276%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_crdb_7 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '277%';";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE cdr.bvs_cdr AS b
    JOIN porting.p_ported_numbers_crdb_8 AS p
    ON b.callee_id_number = p.msisdn
    SET b.ported_number = 1
    WHERE b.callee_id_number LIKE '278%';";
    \DB::connection('pbx_cdr')->statement($sql);

}

function schedule_bitco_import()
{

    //return false;
    set_time_limit(900);
    \DB::connection('pbx_cdr')->table('bitco_cdr')->truncate();
    //return false;

    $csv = (new Rap2hpoutre\FastExcel\FastExcel)->import(public_path().'/cloudtele-jhb.csv');
    foreach ($csv as $line) {

        $cost = trim(str_replace('R', '', $line['Cost']));
        if (empty($cost)) {
            $cost = 0;
        }

        $data = [
            'call_date' => $line['Date'],
            'source' => $line['Source Number'],
            'destination' => $line['Destination Number'],
            'bitco_duration' => $line['Duration'],
            'bitco_cost' => $cost,
            'record_exists' => 0,
            'cdr_duration' => 0,
            'cdr_cost' => 0,
            'cost_match' => 0,
            'duration_match' => 0,
            'cdr_rate' => 0,

        ];

        $insert_data[] = $data;

    }

    $nlist = collect($insert_data); // Make a collection to use the chunk method

    // it will chunk the dataset in smaller collections containing 500 values each.
    // Play with the value to get best result
    $chunks = $nlist->chunk(500);

    foreach ($chunks as $chunk) {
        \DB::connection('pbx_cdr')->table('bitco_cdr')->insert($chunk->toArray());
    }

    \DB::connection('pbx_cdr')->table('bitco_cdr')->where('bitco_cost', '>', 0)->update(['bitco_rate' => \DB::raw('bitco_cost/bitco_duration*60')]);

    $sql = 'UPDATE bitco_cdr
    SET cdr_cost = call_records_outbound_archive_2023apr.gateway_cost,
    cdr_rate = call_records_outbound_archive_2023apr.gateway_rate,
    cdr_duration = call_records_outbound_archive_2023apr.duration,
    checked = 1
    FROM call_records_outbound_archive_2023apr
    WHERE bitco_cdr.checked = 0 and bitco_cdr.source = call_records_outbound_archive_2023apr.caller_id_number
    AND bitco_cdr.destination = call_records_outbound_archive_2023apr.callee_id_number
    AND ABS(EXTRACT(EPOCH FROM (bitco_cdr.call_date - call_records_outbound_archive_2023apr.start_time))) <= 240;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE bitco_cdr
    SET cdr_cost = call_records_outbound_lastmonth.gateway_cost,
    cdr_rate = call_records_outbound_lastmonth.gateway_rate,
    cdr_duration = call_records_outbound_lastmonth.duration,
    checked = 1
    FROM call_records_outbound_lastmonth
    WHERE bitco_cdr.checked = 0 and bitco_cdr.source = call_records_outbound_lastmonth.caller_id_number
    AND bitco_cdr.destination = call_records_outbound_lastmonth.callee_id_number
    AND ABS(EXTRACT(EPOCH FROM (bitco_cdr.call_date - call_records_outbound_lastmonth.start_time))) <= 240;';
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = 'UPDATE bitco_cdr set record_exists=1 where checked=1;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE bitco_cdr set cost_match=1 where cdr_cost=bitco_cost;';
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = 'UPDATE bitco_cdr set duration_match=1 where cdr_duration=bitco_duration;';
    \DB::connection('pbx_cdr')->statement($sql);

}

/*
$cdr_table = 'call_records_outbound_lastmonth';
    $cdr_last_table = 'call_records_outbound_archive_'.strtolower(date('YM', strtotime(' -2 months')));


    $insert_data = [];
    $import_dates = [];
    $date_start = date('Y-m-23',strtotime('-2 months'));
    $date_end = date('Y-m-22',strtotime('-1 month'));
    while($date_start <= $date_end){
        $import_dates[] = $date_start;
        $date_start = date('Y-m-d',strtotime($date_start.' +1 day'));
    }
    $import_date = false;

    foreach($import_dates as $date){
         $exists = \DB::connection('pbx_cdr')->table('bitco_cdr')->where('call_date','like',$date.'%')->count();
         if(!$exists){

            $import_date = $date;
            break;
         }
    }
    if(!$import_date){
        return false;
    }
    $dates = [$import_date];

    $csv = (new Rap2hpoutre\FastExcel\FastExcel())->import('/home/erpcloud-live/htdocs/html/cloudtele-jhb.csv');

    $csv = collect($csv);
    $csv = $csv->filter(function ($item) use ($import_date) {

        // replace stristr with your choice of matching function

        return str_starts_with($item['Date'], $import_date);
    });

    $call_records = [];
    foreach($dates as $date){
        if(date('Y-m',strtotime('-1 month')) == date('Y-m',strtotime($date))){
            $cdr_table = 'call_records_outbound_lastmonth';
        }else{
            $cdr_table = 'call_records_outbound_archive_'.strtolower(date('YM', strtotime(' -2 months')));
        }
        $cdr_records = \DB::connection('pbx_cdr')->table($cdr_table)
        ->select('id','duration','gateway_rate','gateway_cost','start_time','callee_id_number','caller_id_number')
        ->where('gateway','BITCO')
        ->where('start_time','LIKE',$date.'%')
        ->get();
        $call_records[$date] = $cdr_records;
    }
  $insert_data = [];
    foreach($csv as $line){
        $line_date = date('Y-m-d',strtotime($line['Date']));
        if(in_array($line_date,$dates)){

    $cost = trim(str_replace('R','',$line['Cost']));
    if(empty($cost)){
        $cost = 0;
    }
    $data = [
        'call_date' => $line['Date'],
        'source' => $line['Source Number'],
        'destination' => $line['Destination Number'],
        'bitco_duration' => $line['Duration'],
        'bitco_cost' => $cost,
        'record_exists' => 0,
        'cdr_duration' => 0,
        'cdr_cost' => 0,
        'cost_match' => 0,
        'duration_match' => 0,
        'cdr_rate' => 0,

    ];

    if(date('Y-m',strtotime($line['Date'])) == date('Y-m', strtotime(' -1 month'))){
        $records = $cdr_records;
    }else{
        $records = $cdr_records_last;
    }
    $call_date = date('Y-m-d H',strtotime($data['call_date']));

    $startDate = date('Y-m-d H:i:s',strtotime($line['Date'].' -240 seconds'));
    $endDate = date('Y-m-d H:i:s',strtotime($line['Date'].' +240 seconds'));
    $cdr = $call_records[$line_date]->filter(function ($item) use ($startDate, $endDate) {
        $startTime = Carbon\Carbon::parse($item->start_time);
        return $startTime->between($startDate, $endDate);
    })
    ->where('callee_id_number',$line['Destination Number'])
    ->where('caller_id_number',$line['Source Number'])
    ->first();

    if($cdr && $cdr->id){
       $data['record_exists'] = 1;
       $data['cdr_duration'] = $cdr->duration;
       $data['cdr_cost'] = $cdr->gateway_cost;
       $data['cdr_rate'] = $cdr->gateway_rate;
       if($data['cdr_cost'] == $cost){
          $data['cost_match'] = 1;
       }
       if($data['bitco_duration'] == $data['cdr_duration']){
          $data['duration_match'] = 1;
       }
    }
    $insert_data[] = $data;
        }

}

    $nlist = collect($insert_data); // Make a collection to use the chunk method

    // it will chunk the dataset in smaller collections containing 500 values each.
    // Play with the value to get best result
    $chunks = $nlist->chunk(500);

    foreach ($chunks as $chunk)
    {
        \DB::connection('pbx_cdr')->table('bitco_cdr')->insert($chunk->toArray());
    }

    \DB::connection('pbx_cdr')->table('bitco_cdr')->where('bitco_cost','>',0)->update(['bitco_rate'=>\DB::raw('bitco_cost/bitco_duration*60')]);

*/

/*
function bitco_cdr_check_records(){

    $nov_cdr_records = \DB::connection('pbx_cdr')->table('call_records_2022nov')
    ->select('id','duration','gateway_rate','gateway_cost','start_time','callee_id_number','caller_id_number')
    ->where('gateway','BITCO')
    ->where('start_time','<','2022-11-23')
    ->get();
    $oct_cdr_records = \DB::connection('pbx_cdr')->table('call_records_2022oct')
    ->select('id','duration','gateway_rate','gateway_cost','start_time','callee_id_number','caller_id_number')
    ->where('gateway','BITCO')
    ->where('start_time','>','2022-10-22')
    ->get();

    $bitco_records = \DB::connection('pbx_cdr')->table('bitco_cdr')->where('record_exists',0)->where('checked',0)->limit(1000)->get();
    foreach($bitco_records as $r){

        //$cdr_table = 'call_records_'.strtolower(date('YM', strtotime($r->call_date)));
       // $cdr = \DB::connection('pbx_cdr')->table($cdr_table)
       // ->select('duration','gateway_cost','gateway_rate','id')
       // ->where('callee_id_number',$r->destination)
        //->where('caller_id_number',$r->source)
        //->where('start_time','>=',date('Y-m-d H:i:s',strtotime($r->call_date.' -10 seconds')))
        //->where('start_time','<=',date('Y-m-d H:i:s',strtotime($r->call_date.' +10 seconds')))
        //->get()->first();

        if(date('Y-m',strtotime($r->call_date)) == '2022-10'){
            $cdr = $oct_cdr_records
            ->where('callee_id_number',$r->destination)
            ->where('caller_id_number',$r->source)
            ->where('start_time','>=',date('Y-m-d H:i:s',strtotime($r->call_date.' -240 seconds')))
            ->where('start_time','<=',date('Y-m-d H:i:s',strtotime($r->call_date.' +240 seconds')))
            ->first();
        }else{

            $cdr = $nov_cdr_records
            ->where('callee_id_number',$r->destination)
            ->where('caller_id_number',$r->source)
            ->where('start_time','>=',date('Y-m-d H:i:s',strtotime($r->call_date.' -240 seconds')))
            ->where('start_time','<=',date('Y-m-d H:i:s',strtotime($r->call_date.' +240 seconds')))
            ->first();
        }
        //dd($r,$cdr);
        if($cdr && $cdr->id){
            $data = [];
            $data['record_exists'] = 1;
            $data['cdr_duration'] = $cdr->duration;
            $data['cdr_cost'] = $cdr->gateway_cost;
            $data['cdr_rate'] = $cdr->gateway_rate;
            if(abs($data['cdr_cost'] - $r->bitco_cost) < 0.01){
            $data['cost_match'] = 1;
            }
            if($data['cdr_duration'] == $r->bitco_duration){
            $data['duration_match'] = 1;
            }
            $data['checked'] = 1;
            \DB::connection('pbx_cdr')->table('bitco_cdr')->where('id',$r->id)->update($data);
        }else{
            \DB::connection('pbx_cdr')->table('bitco_cdr')->where('id',$r->id)->update(['checked'=>1]);
        }
    }
}
*/

function import_numbering_plan()
{
    \DB::connection('pbx_cdr')->table('p_numbering_plan')->truncate();
    $number_planning = file_to_array(public_path().'/telecloud/gnp_numbering_plan.xlsx');

    $number_planning = $number_planning->map(function ($item) {
        return collect($item)->mapWithKeys(function ($value, $key) {
            $newKey = strtolower(str_replace(' ', '_', trim($key)));

            return [$newKey => $value];
        })->all();
    });

    // Output the transformed collection
    $chunks = $number_planning->chunk(1000);

    foreach ($chunks as $chunk) {
        \DB::connection('pbx_cdr')->table('p_numbering_plan')->insert($chunk->toArray());
    }
    $number_planning = file_to_array(upload_path().'/telecloud/mnp_numbering_plan.xlsx');

    $number_planning = $number_planning->map(function ($item) {
        return collect($item)->mapWithKeys(function ($value, $key) {
            $newKey = strtolower(str_replace(' ', '_', trim($key)));

            return [$newKey => $value];
        })->all();
    });

    // Output the transformed collection
    $chunks = $number_planning->chunk(1000);

    foreach ($chunks as $chunk) {
        \DB::connection('pbx_cdr')->table('p_numbering_plan')->insert($chunk->toArray());
    }
    \DB::connection('pbx_cdr')->table('p_numbering_plan')->where('prefix', '')->delete();

    $routing_labels = \DB::connection('pbx_cdr')->table('p_routing_labels')->get();
    foreach ($routing_labels as $label) {
        \DB::connection('pbx_cdr')->table('p_routing_labels')->where('participant_id', $label->participant_id)->update(['participant_id' => trim($label->participant_id)]);
        \DB::connection('pbx_cdr')->table('p_numbering_plan')->where('participant_id', trim($label->participant_id))->update(['network' => $label->gnp_no]);
    }

    $participants = \DB::connection('pbx_cdr')->table('p_numbering_plan')->where('network', '')->groupBy('participant_id')->pluck('participant_id')->toArray();
    foreach ($participants as $participant) {
        \DB::connection('pbx_cdr')->table('p_numbering_plan')->where('participant_id', $participant)->update(['network' => ucwords(strtolower($participant))]);
    }

    // format prefixes
    \DB::connection('pbx_cdr')->table('p_numbering_plan')->update(['original_prefix' => \DB::raw('prefix')]);
    //\DB::connection('pbx_cdr')->table('p_numbering_plan')->update(['prefix' => \DB::raw('original_prefix')]);
    $sql = "UPDATE p_numbering_plan SET prefix= '27' || substr(prefix, 2)";
    \DB::connection('pbx_cdr')->statement($sql);
}

function rerate_cdr()
{

    // ADD DESTINATION IDS

    //\DB::connection('pbx_cdr')->table('call_records_outbound')->update(['destination_id'=>0]);
    /*
    $call_record_ids = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->where('start_time','>=','2023-08-24')->where('duration','>',0)->where('destination_id',0)->limit(5000)->pluck('id')->toArray();

    foreach($call_record_ids as $call_record_id){
    $sql = "UPDATE call_records_outbound_lastmonth AS o
        JOIN (
        SELECT
        o.id AS record_id,
        p.id AS destination_id
        FROM call_records_outbound_lastmonth AS o
        LEFT JOIN p_rates_destinations AS p ON o.callee_id_number LIKE CONCAT(p.id, '%')
        WHERE o.id =".$call_record_id." and o.destination_id = 0
        ORDER BY LENGTH(p.id) DESC
        LIMIT 1
        ) AS subquery
        ON o.id = subquery.record_id
        SET o.destination_id = subquery.destination_id
        WHERE o.id =".$call_record_id." and o.destination_id = 0 and o.duration > 0;";

        \DB::connection('pbx_cdr')->statement($sql);
    }
    */

    // Select records from p_rates_destinations in descending order

    // COST RATES
    /*
    $gateways = \DB::connection('pbx')->table('v_gateways')->get();
    foreach($gateways as $gateway){
        $sql = "UPDATE call_records_outbound_lastmonth AS c
        JOIN p_rates_complete AS p
        ON p.gateway_uuid = '". $gateway->gateway_uuid ."'
        AND p.destination_id = c.destination_id
        SET c.gateway_rate = p.cost
        WHERE c.gateway_rate = 0
        AND c.duration > 0
        AND c.gateway= '". $gateway->gateway ."';";
        \DB::connection('pbx_cdr')->statement($sql);
    }

    $sql = "UPDATE call_records_outbound_lastmonth
    SET admin_rate=gateway_rate";
    \DB::connection('pbx_cdr')->statement($sql);

    $sql = "UPDATE call_records_outbound_lastmonth
    SET gateway_cost=duration*(gateway_rate/60) where duration>0 and gateway_cost=0";
    \DB::connection('pbx_cdr')->statement($sql);
    $sql = "UPDATE call_records_outbound_lastmonth
    SET admin_cost=duration*(admin_rate/60) where duration>0 and admin_cost=0";
    \DB::connection('pbx_cdr')->statement($sql);

    // SELLING RATES
    $local_destinations = ['fixed telkom','fixed liquid','mobile cellc','mobile mtn','mobile vodacom','fixed tollfree','fixed sharecall','mobile telkom'];
    \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->where('summary_destination','')->where('country','south africa')->whereIn('destination',$local_destinations)->update(['summary_destination' => \DB::raw('destination')]);
    \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->where('summary_destination','')->where('country','south africa')->whereNotIn('destination',$local_destinations)->update(['summary_destination' => 'fixed other']);

   //fix_volume_rates_cdr
    */
}
/*
function cdr_set_rolling_balances(){
    $domains = ['vca.cloudtools.co.za'];

    foreach($domains as $d){
        $domain = \DB::connection('pbx')->table('v_domains')->where('domain_name',$d)->get()->first();
        $balances = \DB::connection('pbx')->table('p_airtime_history')->where('type','!=','airtime_usage_cdr')->where('domain_uuid',$domain->domain_uuid)->where('created_at','>','2024-05-01')->orderBy('created_at')->get();


        $total_cost = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('domain_name',$domain->domain_name)->sum('cost');
        $starting_balance = 7020.38 + $total_cost;
        $sql = "SET @running_total := ".$starting_balance.";";
        \DB::connection('pbx_cdr')->statement($sql);
        $sql = "UPDATE call_records_outbound cr
        SET cr.balance = (@running_total := @running_total - cr.cost)
        WHERE cr.domain_name = '".$domain->domain_name."'
        ORDER BY cr.hangup_time,cr.id;";

        \DB::connection('pbx_cdr')->statement($sql);
        if(count($balances) > 0){
            foreach($balances as $balance){
                $starting_balance =  \DB::connection('pbx_cdr')->table('call_records_outbound')->where('domain_name',$domain->domain_name)->where('duration','>',0)->where('hangup_time','<',$balance->created_at)->orderBy('hangup_time','desc')->pluck('balance')->first();
                $sql = "SET @running_total := ".$starting_balance.";";
                \DB::connection('pbx_cdr')->statement($sql);
                $sql = "UPDATE call_records_outbound cr
                SET cr.balance = (@running_total := @running_total - cr.cost)
                WHERE cr.domain_name = '".$domain->domain_name."'
                AND hangup_time>'".$balance->created_at."'
                ORDER BY cr.hangup_time,cr.id;";

                \DB::connection('pbx_cdr')->statement($sql);
            }
        }
    }
}
*/
