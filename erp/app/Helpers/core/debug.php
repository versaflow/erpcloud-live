<?php
/*composer
COMPOSER_MEMORY_LIMIT=-1 composer require
run composer on different php version
/usr/bin/php80 /usr/local/bin/composer
/usr/bin/php74 /usr/local/bin/composer require livewire/turbolinks
/usr/bin/php74 /usr/local/bin/composer dump-autoload
/usr/bin/php74 artisan view:clear
/usr/bin/php74 artisan livewire:discover
/usr/bin/php74 artisan make:livewire counter
 //https://www.scrapingbee.com/blog/download-file-puppeteer/
// /usr/bin/php74 /usr/local/bin/composer require spatie/array-to-xml
//\Spatie\Browsershot\Browsershot::url('https://ct.versaflow.io/')->save('/home/erpcloud-live/htdocs/erp/storage/example.pdf');

*/

// select account_id, docid, doctype, sum(amount) as total from acc_ledgers where ledger_account_id = 5  group by account_id having total>0.1 or total < -0.1

function customer_control_check()
{
    $id = 11140;
    (new DBEvent)->setAccountAging($id, 1);
    $trx = get_debtor_transactions($id);
    $aging = build_aging($id);
    //     $suppplier_control = \DB::select("SELECT acc_ledgers.docid as 'al docid',
    //  acc_ledgers.docdate as 'al docdate',
    //  acc_ledgers.doctype as 'al doctype',
    //  acc_ledgers.amount as 'al amount',
    //  acc_ledger_accounts.name as 'ala name',
    //  crm_accounts.company as 'ca company',
    //  crm_accounts.status as 'ca status',
    //  crm_accounts.partner_id as 'ca partner_id',
    //  DATE_FORMAT(acc_ledgers.docdate,
    // '%Y-%m') as 'al docdate_period',
    //  CURDATE() as today
    // FROM 'acc_ledgers'
    // LEFT JOIN 'crm_accounts' on 'acc_ledgers'.'account_id' = 'crm_accounts'.'id'
    // LEFT JOIN 'acc_ledger_accounts' on 'acc_ledgers'.'ledger_account_id' = 'acc_ledger_accounts'.'id' where acc_ledgers.ledger_account_id=5 and acc_ledgers.account_id=".$id);

    //     $suppplier_control_sum = \DB::select("SELECT
    //  sum(acc_ledgers.amount) as 'al amount'
    // FROM 'acc_ledgers'
    // LEFT JOIN 'crm_accounts' on 'acc_ledgers'.'account_id' = 'crm_accounts'.'id'
    // LEFT JOIN 'acc_ledger_accounts' on 'acc_ledgers'.'ledger_account_id' = 'acc_ledger_accounts'.'id' where acc_ledgers.ledger_account_id=5 and acc_ledgers.account_id=".$id);

}

function roundup_cdr($amount, $currency)
{
    if (strtolower($currency) == 'zar') {
        $amount = str_replace(',', '', $amount);
        $amount = ceil($amount * 100) / 100;

        return number_format((float) $amount, 2, '.', '');
    } else {
        $amount = str_replace(',', '', $amount);
        $amount = ceil($amount * 1000) / 1000;

        return number_format((float) $amount, 3, '.', '');
    }
}

function debugdd()
{
    $ad_campaign = \DB::table('crm_ad_campaigns')->where('id', 110)->get()->first();
    $rs = get_facebook_ad_stats($ad_campaign);

}

function restore_charts()
{
    $charts = \DB::connection('backup_ct')->table('erp_grid_views')->where('chart_model', '>', '')->get();
    foreach ($charts as $c) {
        $e = \DB::connection('default')->table('erp_grid_views')->where('id', $c->id)->where('chart_model', '')->count();
        if ($e) {
            \DB::connection('default')->table('erp_grid_views')->where('id', $c->id)->update(['chart_model' => $c->chart_model]);
        }
    }
}

function azania_sms()
{

    $rows = file_to_array(public_path().'/azania_sms.xlsx');

    $uid = session('user_id');
    $n = date('Y-m-d H:i:s');
    foreach ($rows as $row) {
        $d = [
            'name' => $row['FirstName'].' '.$row['Surname'],
            'number' => '0'.$row['Cell Number requires 0 infront'],
            'created_at' => $n,
            'created_by' => $uid,
            'sms_list_id' => 820,
        ];

        \DB::table('isp_sms_list_numbers')->insert($d);
    }

    $api = new PanaceaApi;
    $api->setUsername('cloud_telecoms');
    $api->setPassword('147896');

    $result = $api->user_get_balance();
}

function update_workspace_module_fields()
{
    $workspace_ids = \DB::connection('default')->table('erp_cruds')->where('db_table', 'crm_staff_tasks')->pluck('id')->toArray();
    $fields = \DB::table('erp_module_fields')->whereIn('field', ['report_update_frequency', 'report_last_update'])->where('module_id', 2018)->get();
    foreach ($fields as $f) {
        $d = (array) $f;
        unset($d['id']);
        foreach ($workspace_ids as $module_id) {
            if ($module_id == 2018) {
                continue;
            }
            $d['module_id'] = $module_id;
            \DB::table('erp_module_fields')->updateOrInsert(['module_id' => $module_id, 'field' => $d['field']], $d);
        }
    }
}

function copy_board_grid_styles()
{
    // use field_ids
    $module_ids = [2033];
    \DB::table('erp_grid_styles')->whereIn('module_id', $module_ids)->delete();
    $rows = \DB::table('erp_grid_styles')->where('module_id', 1898)->get();
    foreach ($rows as $r) {
        $d = (array) $r;
        unset($d['id']);
        foreach ($module_ids as $module_id) {
            $d['module_id'] = $module_id;
            \DB::table('erp_grid_styles')->insert($d);
        }
    }
}

function pbx_whitelist_delete_duplicates()
{

    $conn = 'pbx';
    $table = 'table';
    $field = 'field';

    $rows = \DB::connection($conn)->select(" SELECT * FROM $table
    GROUP BY $field
    HAVING COUNT($field) > 1");

    foreach ($rows as $row) {
        \DB::connection($conn)->table($table)->where('id', '!=', $row->id)->where($field, $row->{$field})->delete();
    }
    \DB::connection('pbx_cdr')->statement('
    DELETE p1
    FROM p_callee_whitelist p1, p_callee_whitelist p2
    WHERE p1.id > p2.id
    AND p1.callee_id_number = p2.callee_id_number;');
}

function pbxunblock()
{

    $pbx = new FusionPBX;
    $r = $pbx->checkBlockedIP('192.143.225.211');
    $rr = $pbx->unblockIP('192.143.225.211');
}

function fix_outdated_db_schema()
{

    // $cmd = 'mysqldump -h 156.0.96.71 -u remote -pWebmin@786 --no-data telecloud | mysql -u remote -p34icZDyjCNEFA7wEmUus ahmedo';

    // $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);

    // ini_set('max_execution_time', '0');
    // DB::connection('personal')->statement('SET FOREIGN_KEY_CHECKS = 0');

    // $schema = get_complete_schema('personal_backup');
    // $schema_new = get_complete_schema('personal');
    // foreach($schema as $table => $cols){
    //     if($table != 'erp_module_fields'){
    //      //   continue;
    //     }
    //     if(\Schema::connection('personal')->hasTable($table)){
    //         \DB::connection('personal')->table($table)->truncate();
    //         $rows = \DB::connection('personal_backup')->table($table)->get();
    //         if ($rows->count() > 0) {
    //         $insertData = [];

    //         foreach ($rows as $row) {
    //             $newRow = [];

    //             foreach ($schema_new[$table] as $col) {
    //                 // Check if the column exists in the source schema
    //                 if (in_array($col,$schema[$table])) {
    //                     $newRow[$col] = $row->$col;
    //                 }
    //             }

    //             $insertData[] = $newRow;
    //         }

    //         if (!empty($insertData)) {
    //             try{
    //             $chunks = collect($insertData)->chunk(100);

    //             foreach ($chunks as $chunk){
    //             \DB::connection('personal')->table($table)->insert($chunk->toArray());
    //             }
    //             }catch(\Throwable $ex){
    //             }
    //         }
    //         }
    //     }
    // }

    // DB::connection('personal')->statement('SET FOREIGN_KEY_CHECKS = 1');
}

function session_fibre_bs()
{
    $arr = [
        ['product_id' => 616, 'detail' => '1100004114@ct.co.za'],
        ['product_id' => 1124, 'detail' => '1100004115@ct.co.za'],
        ['product_id' => 616, 'detail' => '1100004117@ct.co.za'],
        ['product_id' => 1124, 'detail' => '1100004118@ct.co.za'],
        ['product_id' => 1124, 'detail' => '1100004119@ct.co.za'],
        ['product_id' => 616, 'detail' => '1100004120@ct.co.za'],
        ['product_id' => 1126, 'detail' => '1100004121@ct.co.za'],
        ['product_id' => 1126, 'detail' => '1100004122@ct.co.za'],
        ['product_id' => 1124, 'detail' => '1100004123@ct.co.za'],
        ['product_id' => 616, 'detail' => '1100004126@ct.co.za'],
        ['product_id' => 616, 'detail' => '1100004148@ct.co.za'],
        ['product_id' => 1124, 'detail' => '1100004149@ct.co.za'],
        ['product_id' => 616, 'detail' => '1100004239@st-sp.co.za'],
        ['product_id' => 616, 'detail' => '1100004242@st-sp-l2.co.za'],
    ];

    foreach ($arr as $a) {

        $sub = \DB::table('sub_services')->where('detail', $a['detail'])->get()->first();
        $document_line = \DB::table('crm_document_lines')
            ->select('crm_document_lines.*', 'crm_documents.docdate')
            ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
            ->where('crm_documents.billing_type', 'Monthly')
            ->where('subscription_id', $sub->id)
            ->orderBy('crm_documents.id', 'desc')
            ->get()->first();
    }

    //update_document_total();

}

function remove_unlimited_airtime()
{

    $free_ext = 674;
    $free_ext_ul = 1393;
    $pbx_ext = 130;
    $pbx_ext_ul = 1394;
    $subs = \DB::table('sub_services')->where('product_id', 923)->where('status', '!=', 'Deleted')->get();

    $account_ids = $subs->pluck('account_id')->unique()->toArray();
    $adjustments = \DB::connection('pbx')->table('p_airtime_history')
        ->where('domain_uuid', $domain->domain_uuid)
        ->where('type', 'unlimited_airtime_to_extension')
        ->get();
    foreach ($adjustments as $adjustment) {
        $total = abs($adjustment->total);
        \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $adjustment->domain_uuid)->increment('balance', $total);
        \DB::connection('pbx')->table('p_airtime_history')->where('id', $adjustment->id)->delete();
    }

    foreach ($account_ids as $account_id) {
        $account = dbgetaccount($account_id);
        $domain = \DB::connection('pbx')->table('v_domains')->where('account_id', $account_id)->get()->first();
        if ($domain->domain_uuid) {
            $e = \DB::connection('pbx')->table('p_airtime_history')->where('domain_uuid', $domain->domain_uuid)->where('type', 'unlimited_airtime_to_extension')->count();
            if ($e) {
                continue;
            }
            $total = \DB::connection('pbx')->table('p_airtime_history')->where('created_at', 'like', date('Y-m').'%')->where('domain_uuid', $domain->domain_uuid)->where('type', 'contract')->pluck('total')->first();
            if ($total > 0) {
                $airtime_history = [
                    'created_at' => date('Y-m-01 H:i:s'),
                    'domain_uuid' => $domain->domain_uuid,
                    'total' => $total * -1,
                    'balance' => $domain->balance - $total,
                    'type' => 'unlimited_airtime_to_extension',

                ];

                \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
                \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain->domain_name)->decrement('balance', $total);
            }
        }
    }
}

function voip_rates_local()
{

    $rates = \DB::connection('pbx')->table('p_rates_destinations')->where('country', 'south africa')->get();
    foreach ($rates as $rate) {
        $c = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', '47564033-ae97-4c21-8c05-aa4b89dbdb7d')->where('destination_id', $rate->id)->count();
        if (! $c) {
            $data = [
                'gateway_uuid' => '47564033-ae97-4c21-8c05-aa4b89dbdb7d',
                'country' => $rate->country,
                'destination' => $rate->destination,
                'destination_id' => $rate->id,
                'cost' => 0.24,
                'status' => 'Enabled',
            ];
            if ($rate->destination == 'mobile vodacom') {
                $data['cost'] = 0.21;
            }
            if ($rate->destination == 'mobile mtn') {
                $data['cost'] = 0.21;
            }
            if ($rate->destination == 'mobile cellc') {
                $data['cost'] = 0.25;
            }

            if ($rate->destination == 'mobile telkom') {
                $data['cost'] = 0.28;
            }
            if ($rate->destination == 'fixed telkom') {
                $data['cost'] = 0.18;
            }
            \DB::connection('pbx')->table('p_rates_complete')->insert($data);
        }
    }
}

function duplicate_invoice($id)
{
    return false;
    $doc = \DB::table('crm_documents')->where('id', $id)->get()->first();

    $data = (array) $doc;
    unset($data['id']);
    $new_id = \DB::table('crm_documents')->insertGetId($data);
    $lines = \DB::table('crm_document_lines')->where('document_id', $id)->get();
    foreach ($lines as $l) {
        $ldata = (array) $l;
        unset($ldata['id']);
        $ldata['document_id'] = $new_id;
        \DB::table('crm_document_lines')->insert($ldata);
    }
}

function rename_menus()
{
    return false;
    $name = 'related_items_menu';
    $new_name = 'related_items_menu';
    $db_conns = db_conns();

    foreach ($db_conns as $c) {
        \DB::connection($c)->table('erp_menu')->where('location', $name)->update(['location' => $new_name]);
        $module_fields = \DB::connection($c)->table('erp_module_fields')->where('module_id', 499)->where('display_logic', 'like', '%'.$name.'%')->get();
        foreach ($module_fields as $module_field) {
            $display_logic = str_replace($name, $new_name, $module_field->display_logic);
            \DB::connection($c)->table('erp_module_fields')->where('id', $module_field->id)->update(['display_logic' => $display_logic]);
        }

        $location_field = \DB::connection($c)->table('erp_module_fields')->where('module_id', 499)->where('field', 'location')->get()->first();
        $opts_values = str_replace($name, $new_name, $location_field->opts_values);
        \DB::connection($c)->table('erp_module_fields')->where('id', $location_field->id)->update(['opts_values' => $opts_values]);

    }
    // search replace all code references

}

function set_timestamps_from_log()
{
    $modules = \DB::table('erp_cruds')->where('connection', 'default')->where('db_table', '!=', 'erp_module_log')->where('time_stamps', 1)->get()->unique('db_table');

    foreach ($modules as $m) {

        $module_ids = \DB::table('erp_cruds')->where('connection', 'default')->where('db_table', $m->db_table)->pluck('id')->toArray();

        $module_ids_str = implode(',', $module_ids);
        $sql = 'UPDATE '.$m->db_table.' 
        JOIN erp_module_log ON '.$m->db_table.'.id=erp_module_log.row_id
        SET '.$m->db_table.'.created_at = erp_module_log.created_at
        WHERE '.$m->db_table.".created_at is null and erp_module_log.action='created' and erp_module_log.module_id=".$m->id;
        \DB::statement($sql);
        $sql = 'UPDATE '.$m->db_table.' 
        JOIN erp_module_log ON '.$m->db_table.'.id=erp_module_log.row_id
        SET '.$m->db_table.'.created_by = erp_module_log.created_by
        WHERE '.$m->db_table.".created_by=0 and erp_module_log.action='created' and erp_module_log.module_id=".$m->id;

        \DB::statement($sql);
    }
}

function merge_layouts()
{
    $c = 'default';

    \DB::connection($c)->table('erp_grid_views')->where('name', 'like', '%(D)%')->delete();
    $master_detail = \DB::connection($c)->table('erp_cruds')->where('detail_module_id', '>', 0)->get();
    foreach ($master_detail as $m) {

        $detail_layouts = \DB::connection($c)->table('erp_grid_views')->where('aggrid_state', '>', '')->where('global_default', 0)->where('module_id', $m->detail_module_id)->get();
        foreach ($detail_layouts as $d) {
            $data = (array) $d;
            $aggrid_state = \DB::connection($c)->table('erp_grid_views')->where('module_id', $m->id)->where('global_default', 1)->pluck('aggrid_state')->first();
            unset($data['id']);
            $data['name'] .= ' (D)';
            $data['aggrid_state'] = $aggrid_state;
            $data['detail_aggrid_state'] = $d->aggrid_state;
            $data['module_id'] = $m->id;
            \DB::connection($c)->table('erp_grid_views')->insert($data);
        }
        $detail_aggrid_state = \DB::connection($c)->table('erp_grid_views')->where('global_default', 1)->where('module_id', $m->detail_module_id)->pluck('aggrid_state')->first();
        if ($detail_aggrid_state > '') {
            \DB::connection($c)->table('erp_grid_views')->where('module_id', $m->id)->where('global_default', 1)->update(['detail_aggrid_state' => $detail_aggrid_state]);
            \DB::connection($c)->table('erp_grid_views')->where('module_id', $m->id)->where('detail_aggrid_state', '')->update(['detail_aggrid_state' => $detail_aggrid_state]);
        }

    }
}

function import_fb_leads()
{
    return false;
    $leads = file_to_array(public_path().'filesleads/ns1.csv');

    $rlist = [];
    foreach ($leads as $lead) {
        $data = [
            'full_name' => $lead['FULL_NAME'],
            'company' => $lead['FULL_NAME'],
            'email' => $lead['EMAIL'],
            'phone' => str_replace('p:', '', $lead['PHONE']),
            'created_at' => date('Y-m-d'),
            'source' => 'facebook',
            'post_data' => json_encode($lead),
            'external_id' => str_replace('l:', '', $lead['id']),
            'form_name' => $lead['form_name'],
            'form_id' => str_replace('f:', '', $lead['form_id']),
        ];

        $rlist[] = $data;
        $e = \DB::table('crm_marketing_leads')->where('external_id', $data['external_id'])->count();
        if (! $e) {
            \DB::table('crm_marketing_leads')->insert($data);
        }
    }
}

function session_number_compare()
{

    $gateways = \DB::connection('pbx')->table('v_gateways')->get();
    $numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('status', '!=', 'Deleted')->get();
    $session_db_numbers = $numbers->where('gateway_uuid', 'c924db4e-a881-44e8-b8da-a150e3cf4c52')->all();

    $domains = \DB::connection('pbx')->table('v_domains')->get();
    $volume_domain_uuids = $domains->where('cost_calculation', 'volume')->pluck('domain_uuid')->toArray();
    $session_numbers = file_to_array(public_path().'/session_numbers.xlsx');
    $session_numbers_list = $session_numbers->pluck('number')->toArray();
    $export_list = [];

    foreach ($session_numbers as $i => $session_number) {
        $row = $session_number;
        $exists = $numbers->where('number', $session_number['number'])->count();
        if ($exists) {
            $n = $numbers->where('number', $session_number['number'])->first();
            $allocated = 1;
            $gateway = $gateways->where('gateway_uuid', $n->gateway_uuid)->pluck('gateway')->first();
            $type = (in_array($n->domain_uuid, $volume_domain_uuids)) ? 'wholesale' : 'retail';
            $domain = $domains->where('domain_uuid', $n->domain_uuid)->pluck('domain_name')->first();
        } else {
            $allocated = 0;
            $gateway = '';
            $type = '';
            $domain = '';
        }
        $row['on_pbx'] = $allocated;
        $row['on_supplier'] = 1;
        $row['gateway'] = $gateway;
        $row['type'] = $type;
        $row['domain'] = $domain;
        $export_list[] = $row;
    }

    foreach ($session_db_numbers as $db_number) {
        if (! in_array($db_number->number, $session_numbers_list)) {

            $n = $db_number;
            $allocated = 1;
            $gateway = $gateways->where('gateway_uuid', $n->gateway_uuid)->pluck('gateway')->first();
            $type = (in_array($n->domain_uuid, $volume_domain_uuids)) ? 'wholesale' : 'retail';
            $domain = $domains->where('domain_uuid', $n->domain_uuid)->pluck('domain_name')->first();

            $row['number'] = $db_number->number;
            $row['customer'] = '';
            $row['reseller'] = '';
            $row['port_date'] = '';
            $row['on_pbx'] = $allocated;
            $row['on_supplier'] = 0;
            $row['gateway'] = $gateway;
            $row['type'] = $type;
            $row['domain'] = $domain;
            $export_list[] = $row;
        }

    }

    $export_list = collect($export_list);
    //$nn = $export_list->where('gateway','!=','SESSION')->all();
    //dd($nn);
    $export = new App\Exports\CollectionExport;
    $export->setData($export_list);

    Excel::store($export, session('instance')->directory.'/session_numbers_compared_db.xlsx', 'downloads');
}

function fix_postgres_primary_key()
{
    return false;
    Schema::connection('pbx_cdr')->table('call_records_outbound', function (Illuminate\Database\Schema\Blueprint $t) {
        // Add the Auto-Increment column
        $t->dropColumn('id');

    });
    Schema::connection('pbx_cdr')->table('call_records_outbound', function (Illuminate\Database\Schema\Blueprint $t) {
        // Add the Auto-Increment column
        $t->increments('id');

    });
}

function fix_cdr_monthly_usage()
{

    $domain_names = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('hangup_date', '<', '2023-03-01')->groupBy('domain_name')->pluck('domain_name')->toArray();
    $domains = \DB::connection('pbx')->table('v_domains')->whereIn('domain_name', $domain_names)->get();
    foreach ($domains as $domain) {
        $records = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('domain_name', $domain->domain_name)->where('hangup_date', '<', '2023-03-01')->get();
        foreach ($records as $row) {
            $row = (array) $row;
            $data = $row;
            foreach ($row as $k => $v) {
                if (is_null($v)) {
                    unset($data[$k]);
                }
            }
            unset($data['id']);
            $c = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
                ->where('domain_name', $domain->domain_name)
                ->where('hangup_time', $data['hangup_time'])
                ->where('caller_id_number', $data['caller_id_number'])
                ->where('callee_id_number', $data['callee_id_number'])
                ->count();
            if (! $c) {
                \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')->insert($data);
            }
        }
        \DB::connection('pbx_cdr')->table('call_records_outbound')->where('domain_name', $domain->domain_name)->where('hangup_date', '<', '2023-03-01')->delete();

        $sql = "WITH balance (id, value) AS (
        SELECT id, (sum(cost) over (order by hangup_date, id)) FROM call_records_outbound WHERE domain_name='".$domain->domain_name."' and hangup_date>'2023-02-28'
        )
        UPDATE call_records_outbound SET monthly_usage = balance.value FROM balance WHERE call_records_outbound.id = balance.id and domain_name='".$domain->domain_name."' and hangup_date>'2023-02-28';";
        \DB::connection('pbx_cdr')->statement($sql);
    }

}

function numbers_ranges_verify()
{

    $ranges = [];
    $gateways = \DB::connection('pbx')->table('v_gateways')->get();

    $last_id = \DB::connection('pbx')->table('p_phone_numbers')->where('status', '!=', 'Deleted')->orderby('id', 'asc')->pluck('id')->first();
    $n = \DB::connection('pbx')->table('p_phone_numbers')->where('status', '!=', 'Deleted')->orderby('id', 'desc')->get()->first();
    $prefix = substr($n->number, 0, 4);
    $i = 0;
    while ($i < 400 && $n->id != $last_id) {
        $nn = \DB::connection('pbx')->table('p_phone_numbers')->where('status', '!=', 'Deleted')->where('id', '<', $n->id)->where('number', 'not like', '%'.$prefix.'%')->orderby('id', 'desc')->get()->first();
        $end_number = \DB::connection('pbx')->table('p_phone_numbers')->where('status', '!=', 'Deleted')->where('id', '>', $nn->id)->where('number', 'like', '%'.$prefix.'%')->orderby('id', 'asc')->get()->first();
        $ranges[] = ['start' => $n->number, 'end' => $end_number->number, 'gateway' => $gateways->where('gateway_uuid', $n->gateway_uuid)->pluck('gateway')->first()];
        $n = $nn;
        $prefix = substr($n->number, 0, 4);
        $i++;
    }

}

function prefix_count()
{
    $l = [];
    $prefixes = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')->orderBy('prefix')->pluck('prefix')->unique()->filter()->toArray();
    foreach ($prefixes as $prefix) {
        $l[$prefix] = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')->whereNull('domain_uuid')->where('is_spam', 0)->where('prefix', $prefix)->count();
    }

}

function copy_energy_customers()
{
    dd(1);
    /*
     $products = \DB::table('crm_products')->where('id',1130)->get();
     foreach($products as $product){
         $data = (array) $product;
         \DB::connection('energy')->table('crm_products')->insert($data);
     }
     $products = \DB::table('crm_pricelist_items')->whereIn('pricelist_id',[1,2])->where('product_id',1130)->get();
     foreach($products as $product){
         $data = (array) $product;
         \DB::connection('energy')->table('crm_pricelist_items')->insert($data);
     }
*/
    \DB::connection('energy')->table('crm_document_lines')->delete();
    \DB::connection('energy')->table('crm_documents')->delete();
    \DB::connection('energy')->table('crm_accounts')->where('id', '>', 1)->delete();
    \DB::connection('energy')->table('erp_users')->where('account_id', '>', 1)->delete();
    \DB::connection('energy')->table('crm_marketing_leads')->truncate();
    $categories = \DB::table('crm_product_categories')->where('department', 'Energy')->where('is_deleted', 0)->pluck('id')->toArray();

    $products = \DB::table('crm_products')->whereIn('product_category_id', $categories)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    $docs = \DB::table('crm_document_lines')->whereIn('product_id', $products)->pluck('document_id')->unique()->toArray();
    $account_ids = \DB::table('crm_documents')->whereIn('id', $docs)->where('reversal_id', 0)->pluck('account_id')->unique()->toArray();
    $energy_account_ids = [];
    foreach ($account_ids as $id) {
        $ac = \DB::table('acc_cashbook_transactions')->where('account_id', $id)->count();

        $c = \DB::table('crm_documents')->whereNotIn('id', $docs)->where('account_id', $id)->count();
        $sc = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('account_id', $id)->count();
        if (! $c && ! $sc && ! $ac) {
            $energy_account_ids[] = $id;
        }
        if ($ac && ! $sc && ! $c) {
        }
    }
    $companies = \DB::table('crm_accounts')->whereIn('id', $energy_account_ids)->where('status', '!=', 'Deleted')->pluck('company')->toArray();

    \DB::table('crm_accounts')->whereIn('id', $energy_account_ids)->update(['energy_customer' => 1]);
    foreach ($energy_account_ids as $id) {
        copy_cloud_to_energy($id);
    }

    $leads = \DB::table('crm_marketing_leads')->where('energy_customer', 1)->get();
    foreach ($leads as $lead) {
        $data = (array) $lead;
        \DB::connection('energy')->table('crm_marketing_leads')->insert($data);
    }

}

function rebuild_ext()
{
    $customer = dbgetaccount(9950);
    \DB::connection('pbx')->table('v_extensions')->where('extension', 101)->where('domain_uuid', $customer->domain_uuid)->delete();
    pbx_add_extension($customer, 101);
}

function pbx_pg_fix_increments()
{
    dd(1);

    $tables = get_tables_from_schema('postgres_pbx');
    //$tables = ['v_domains','v_extensions','p_phone_numbers'];
    foreach ($tables as $table) {
        if (! str_contains($table, '_copy') && ! str_contains($table, '_5')) {
            if ($table == 'p_rates_destinations') {
                continue;
            }
            $pgsql_schema = get_table_schema($table, 'postgres_pbx');
            $fields = array_keys($pgsql_schema);

            if (in_array('id', $fields)) {

                if (! Schema::connection('postgres_pbx')->hasColumn($table, 'id_backup')) {
                    Schema::connection('postgres_pbx')->table($table, function (Illuminate\Database\Schema\Blueprint $t) {
                        // Add the Auto-Increment column
                        $t->integer('id_backup')->default(0);

                    });

                    \DB::connection('postgres_pbx')->table($table)->update(['id_backup' => \DB::raw('id')]);
                }

                Schema::connection('postgres_pbx')->table($table, function (Illuminate\Database\Schema\Blueprint $t) {
                    // Add the Auto-Increment column
                    $t->dropColumn('id');

                });
                Schema::connection('postgres_pbx')->table($table, function (Illuminate\Database\Schema\Blueprint $t) {
                    // Add the Auto-Increment column
                    $t->increments('id');

                });
                \DB::connection('postgres_pbx')->statement('ALTER TABLE '.$table.' DROP CONSTRAINT '.$table.'_pkey');

                \DB::connection('postgres_pbx')->table($table)->update(['id' => \DB::raw('id_backup')]);
                \DB::connection('postgres_pbx')->statement('ALTER TABLE '.$table.'  ADD CONSTRAINT '.$table.'_pkey PRIMARY KEY ( id )');

            }

        }

    }

    $tables = get_tables_from_schema('postgres_pbx');
    //$tables = ['v_domains','v_extensions','p_phone_numbers'];
    foreach ($tables as $table) {
        if (! str_contains($table, '_copy') && ! str_contains($table, '_5')) {
            if ($table == 'p_rates_destinations') {
                continue;
            }
            $pgsql_schema = get_table_schema($table, 'postgres_pbx');
            $fields = array_keys($pgsql_schema);

            if (in_array('id_backup', $fields)) {

                if (Schema::connection('postgres_pbx')->hasColumn($table, 'id_backup')) {
                    Schema::connection('postgres_pbx')->table($table, function (Illuminate\Database\Schema\Blueprint $t) {
                        // Add the Auto-Increment column

                        $t->dropColumn('id_backup');
                    });
                }

            }

        }

    }
}

function pbx_pg_defaults()
{
    dd(1);

    $tables = get_tables_from_schema('pbx');
    //$tables = ['v_domains','v_extensions','p_phone_numbers'];
    foreach ($tables as $table) {
        if (! str_contains($table, '_copy')) {
            $mysql_schema = get_table_schema($table, 'pbx');
            $pgsql_schema = get_table_schema($table, 'postgres_pbx');
            foreach ($mysql_schema as $field => $settings) {
                if ($pgsql_schema[$field]['default'] == 'now()') {
                    $pgsql_schema[$field]['default'] = 'CURRENT_TIMESTAMP';
                }

                if ($settings['default'] !== null && $pgsql_schema[$field]['default'] != $settings['default']) {
                    if ($settings['default'] == '0000-00-00 00:00:00' || $settings['default'] == '0000-00-00') {
                        $sql = 'ALTER TABLE '.$table.' ALTER COLUMN '.$field.' DROP NOT NULL';
                        \DB::connection('postgres_pbx')->statement($sql);
                        $sql = 'ALTER TABLE '.$table.' ALTER COLUMN '.$field.' SET DEFAULT NULL';
                    } elseif ($settings['default'] == 'CURRENT_TIMESTAMP') {
                        $sql = 'ALTER TABLE '.$table.' ALTER COLUMN '.$field.' SET DEFAULT NOW()';
                    } elseif (is_numeric($settings['default'])) {
                        $sql = 'ALTER TABLE '.$table.' ALTER COLUMN '.$field.' SET DEFAULT '.$settings['default'];
                    } else {
                        $sql = 'ALTER TABLE '.$table.' ALTER COLUMN '.$field." SET DEFAULT '".$settings['default']."'";
                    }
                    //dd($sql);
                    \DB::connection('postgres_pbx')->statement($sql);
                }
            }
        }

    }
}

function pbx_postgres()
{
    // $r = \DB::connection('postgres_pbx')->table('v_devices')->limit(10)->get();
    /*
$r = get_table_schema('v_gateways', 'postgres_pbx');
foreach($r as $field => $setting){
     if($setting['type'] instanceof \Doctrine\DBAL\Types\StringType){
     }
}

dd($r);
*/
    $default_domain_uuid = '4ae2a2de-6473-4bc1-b307-a35a507a98b2';
    $new_domain_uuid = '29cd2aff-1946-4968-b515-79b36bd1b701';
    \DB::connection('postgres_pbx')->table('v_domains')->where('domain_uuid', $default_domain_uuid)->update(['domain_uuid' => $new_domain_uuid, 'domain_name' => '156.0.96.69']);

    $pbx_pg = get_complete_schema('postgres_pbx');

    $pbx = get_complete_schema('pbx');
    $pbx_tables = array_keys($pbx);

    foreach ($pbx_pg as $table => $cols) {

        if (! str_starts_with($table, 'v_')) {
            continue;
        }
        if (! in_array($table, $pbx_tables)) {
            continue;
        }
        if ($table != 'v_users') {
            continue;
        }
        $table_schema = get_table_schema($table, 'postgres_pbx');
        $c1 = \DB::connection('postgres_pbx')->table($table)->count();
        \DB::connection('postgres_pbx')->table($table)->truncate();
        $c2 = \DB::connection('postgres_pbx')->table($table)->count();
        $records = \DB::connection('pbx')->table($table)->get();
        foreach ($records as $row) {
            $data = (array) $row;
            if (isset($data['domain_uuid']) && $data['domain_uuid'] == $default_domain_uuid) {
                $data['domain_uuid'] = $new_domain_uuid;
                if ($table == 'v_domains') {
                    $data['domain_name'] = '156.0.96.69';
                }
            }
            foreach ($data as $k => $v) {
                if ($v == '156.0.96.60') {
                    $data[$k] = '156.0.96.69';
                }
                if ($v == '0000-00-00 00:00:00') {
                    $data[$k] = null;
                }
                if (str_ends_with($k, 'uuid') && empty($v)) {
                    $data[$k] = null;
                }
                if (empty($v)) {
                    foreach ($table_schema as $field => $setting) {
                        if ($field == $k) {
                            if ($setting['nullable']) {
                                $data[$k] = null;
                            } else {

                                if ($setting['type'] instanceof \Doctrine\DBAL\Types\StringType || $setting['type'] instanceof \Doctrine\DBAL\Types\TextType) {
                                    $data[$k] = '';
                                } else {
                                    $data[$k] = 0;
                                }
                            }

                        }
                    }
                }

                if (! in_array($k, $cols)) {
                    unset($data[$k]);
                }
            }

            try {
                \DB::connection('postgres_pbx')->table($table)->insert($data);
            } catch (\Throwable $ex) {
                if (str_contains($ex->getMessage(), 'duplicate key value violates unique constraint "v_default_settings_pkey"')) {
                    \DB::connection('pbx')->table($table)->where('default_setting_uuid', $data['default_setting_uuid'])->delete();
                }
            }
        }

        $c3 = \DB::connection('postgres_pbx')->table($table)->count();
    }

    // check schema
    /*
    $pbx = get_complete_schema('pbx');

    $pbx_pg = get_complete_schema('postgres_pbx');
    $pbx_pg_tables = array_keys($pbx_pg);


    foreach($pbx as $table => $cols){
        if(!in_array($table,$pbx_pg_tables)){
        }else{

            $cols_not_found = '';
            foreach($cols as $c){
                if(!in_array($c,$pbx_pg[$table])){
                    $cols_not_found .= $table.".".$c.' col not found'.PHP_EOL;

                }
            }

            if($cols_not_found > ''){

            }
        }
    }
    */

}

function test_porting_ftp()
{

    $directories2 = Storage::disk('porting_ftp')->allDirectories();
    $files2 = Storage::disk('porting_ftp')->files('DWNLDS');
}

function lti_numbers_lookup()
{

    dd(1);

    $list = '27100257898
27100257897
27100257896
27100257895
27100257894
27100257893
27100257892
27100257891
27100257890
27100257839
27210204495
27210204494
27210204493
27210204492
27210204491
27210204489
27210204488
27210204487
27210204479
27210204478
27310200080
27310200079
27310200078
27310200077
27310200076
27310200075
27310200074
27310200073
27310200071
27310200070';
    $numbers = explode(PHP_EOL, $list);

    $nn = \DB::connection('pbx')->table('p_phone_numbers')
        ->whereNull('domain_uuid')
        ->where('gateway_uuid', '47564033-ae97-4c21-8c05-aa4b89dbdb7d')
        ->where('wholesale_ext', 0)
        ->where('is_spam', 0)
        ->where('status', 'Enabled')
        ->whereNotIn('number', $numbers)
    //->where('number','like', '2731%')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->pluck('number')->toArray();

    foreach ($nn as $i) {
        echo $i.'<br>';
    }
}

function lti_numbers_remove($numbers = [])
{

    dd(1);

    $list = '27215698413
27770990300
27770990314
27770990310
27770990306
27770990307';
    $numbers = explode(PHP_EOL, $list);

    foreach ($numbers as $n) {
        $deleted_at = date('Y-m-d H:i:s');
        $num = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $n)->get()->first();
        if ($num->domain_uuid > '') {
            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('account_id')->first();
            \DB::table('sub_services')->where('detail', $num->number)->where('status', 'Deleted')->delete();
            \DB::table('sub_services')->where('detail', $num->number)->where('account_id', $account_id)->update(['status' => 'Deleted', 'deleted_at' => $deleted_at]);
        }
        \DB::connection('pbx')->table('p_phone_numbers')->where('number', $n)->where('status', 'Deleted')->update(['is_spam' => 0, 'domain_uuid' => null, 'number_routing' => null, 'routing_type' => null, 'wholesale_ext' => 0]);
        \DB::connection('pbx')->table('p_phone_numbers')->where('number', $n)->where('status', '!=', 'Deleted')->update(['is_spam' => 0, 'domain_uuid' => null, 'status' => 'Enabled', 'number_routing' => null, 'routing_type' => null, 'wholesale_ext' => 0]);

    }
    /// clear extension cache
    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $num->domain_uuid)->get();
    foreach ($extensions as $ext) {
        $pbx = new FusionPBX;
        $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
        $pbx->portalCmd('portal_aftersave_extension', $key);
    }

    \DB::connection('pbx')->table('p_phone_numbers')->whereNull('domain_uuid')->update(['wholesale_ext' => 0]);
}

function copy_db_structure()
{

    /*

    $db_conns = db_conns_excluding_main();




    //schema_clone_db_table('pr_mobile_app', 'pr_flex_erp');
    //schema_clone_db_table('pr_mobile_app_details', 'pr_flex_erp_details');

    foreach($db_conns as $c){

        $db = config('database.connections.'.$c.'.database');
        copy_erp_table('pr_mobile_app', 'flexerp_portal', $db);
        copy_erp_table('pr_mobile_app_details', 'flexerp_portal', $db);
    }

    */
}

function mod_access_main_menu()
{
    dd(1);

    $role_id = 42;
    $menu_id = 1704;
    $menu_ids = get_submenu_ids($menu_id);

    $menu_ids[] = 1704;

    $module_id_list = [];
    foreach ($menu_ids as $id) {
        $menu = \DB::table('erp_menu')->where('id', $id)->get()->first();
        if ($menu->module_id) {
            $module_id_list[] = $menu->module_id;
        }

        $grid_menu_module_ids = \DB::table('erp_menu')->where('render_module_id', $menu->module_id)->where('location', 'grid_menu')->pluck('module_id')->toArray();
        foreach ($grid_menu_module_ids as $tmid) {
            $module_id_list[] = $tmid;
        }

        $grid_menu_module_ids = \DB::table('erp_menu')->where('render_module_id', $menu->module_id)->where('location', 'related_items_menu')->pluck('module_id')->toArray();
        foreach ($grid_menu_module_ids as $tmid) {
            $module_id_list[] = $tmid;
        }
    }

    $module_id_list = collect($module_id_list)->unique()->filter()->toArray();
    $modules = \DB::table('erp_cruds')->whereIn('id', $module_id_list)->pluck('name')->toArray();

    $module_id_list[] = 1863;

    foreach ($module_id_list as $module_id) {
        // 1863 - home module
        $e = \DB::table('erp_forms')->where('module_id', $module_id)->where('role_id', $role_id)->count();
        if (! $e) {
            $data = \DB::table('erp_forms')->where('module_id', $module_id)->where('role_id', 1)->get()->first();
            $data = (array) $data;
            unset($data['id']);
            unset($data['is_delete']);
            unset($data['is_add']);
            unset($data['is_edit']);
            $data['role_id'] = $role_id;

            \DB::table('erp_forms')->insert($data);
        } else {
            \DB::table('erp_forms')->where('module_id', $module_id)->where('role_id', $role_id)->update(['is_view' => 1]);
        }
    }
    // 1863 - home module
    $module_id_list[] = 1863;
    // \DB::table('erp_forms')->whereNotIn('module_id',$module_id_list)->where('role_id',$role_id)->delete();
}

function lti_numbers_replace($numbers = [])
{

    /*
    $gateway_uuids = ['c924db4e-a881-44e8-b8da-a150e3cf4c52'];
    $list = '27107460939
27107460937
27107460935
27107460924
27107460923
27107460922
27107460921
27107460920
27107460919
27107460918
27311095139
27311095138
27311095137
27311095136
27311095135
27311093169
27311093168
27311093167
27311093166
27311093165
27217458014
27217458013
27217458011
27217458010
27217458009
27217458008
27217458007
27217458006
27217458005
27217458004';
$numbers = explode(PHP_EOL,$list);
    $new_numbers = \DB::connection('pbx')->table('p_phone_numbers')->whereIn('gateway_uuid',$gateway_uuids)->where('status','Enabled')->whereNotIn('number',$numbers)
    ->where('prefix','021')->whereNull('domain_uuid')->where('is_spam',0)->orderBy('number')->limit(20)->pluck('number')->toArray();

    echo implode('<br>',$new_numbers);
dd(1);
  */

    $list = '27107861341
27875377502
27875372848
27875372844
27875372838
27871357198
27510105027
27510105025
27510105004
27140004284
27140004281
27128814467
27107460932
27104965669
27104965643';
    $numbers = explode(PHP_EOL, $list);

    $ext = 103;
    //dd($numbers,$ext);
    /*
    $gateway_uuids = ['c924db4e-a881-44e8-b8da-a150e3cf4c52'];


        $new_numbers_010 = \DB::connection('pbx')->table('p_phone_numbers')->whereIn('gateway_uuid',$gateway_uuids)->where('status','Enabled')
        ->where('prefix','010')->whereNull('domain_uuid')->where('is_spam',0)->orderBy('number')->limit(15)->get();

        $new_numbers_021 = \DB::connection('pbx')->table('p_phone_numbers')->whereIn('gateway_uuid',$gateway_uuids)->where('status','Enabled')
        ->where('prefix','021')->whereNull('domain_uuid')->where('is_spam',0)->orderBy('number')->limit(15)->get();
        $new_numbers_031 = \DB::connection('pbx')->table('p_phone_numbers')->whereIn('gateway_uuid',$gateway_uuids)->where('status','Enabled')
        ->where('prefix','031')->whereNull('domain_uuid')->where('is_spam',0)->orderBy('number')->limit(15)->get();

        $new_numbers = $new_numbers_010->merge($new_numbers_021);
        $new_numbers = $new_numbers->merge($new_numbers_031);

        $numbers = $new_numbers->pluck('number')->toArray();
    //dd($numbers,$new_numbers_010,$new_numbers_021,$new_numbers_031);
    */

    $new_numbers = \DB::connection('pbx')->table('p_phone_numbers')->whereIn('number', $numbers)->get();
    //dd($numbers,$new_numbers);
    foreach ($new_numbers as $num) {

        $c = 'pbx';
        \DB::connection($c)->table('p_phone_numbers')->where('id', $num->id)
            ->update(['routing_type' => 'extension', 'domain_uuid' => '0abfd39b-294e-40e1-a813-79799077edf0', 'number_routing' => $ext, 'wholesale_ext' => $ext]);
        $num->domain_uuid = '0abfd39b-294e-40e1-a813-79799077edf0';
        $sub_conn = 'default';
        //if ($routing_type == 'product') {
        if (! empty($num->domain_uuid)) {
            $account_id = \DB::connection($c)->table('v_domains')->where('domain_uuid', $num->domain_uuid)->pluck('account_id')->first();

            $sub_conn = 'default';

            $subs_count = \DB::connection($sub_conn)->table('sub_services')->where('detail', $num->number)->count();

            $phone_number = $num->number;
            if (substr($phone_number, 0, 4) == '2787' || substr($phone_number, 0, 3) == '087') {
                $subscription_product = 127; // 087
            } else {
                if (str_starts_with($phone_number, '2712786')) { // 012786
                    $subscription_product = 176;
                } elseif (str_starts_with($phone_number, '2710786')) { // 010786
                    $subscription_product = 176;
                } else { // geo
                    $subscription_product = 128;
                }
            }

            $subscription_data = [
                'account_id' => $account_id,
                'status' => 'Enabled',
                'provision_type' => 'phone_number',
                'detail' => $phone_number,
                'product_id' => $subscription_product,
                'created_at' => date('Y-m-d H:i:s'),
                'date_activated' => date('Y-m-d H:i:s'),
            ];

            if ($subs_count == 0) {
                \DB::connection($sub_conn)->table('sub_services')->insert($subscription_data);
            } elseif ($subs_count == 1) {
                \DB::connection($sub_conn)->table('sub_services')->where('detail', $num->number)->update($subscription_data);
            } else {
                \DB::connection($sub_conn)->table('sub_services')->where('detail', $num->number)->delete();
                \DB::connection($sub_conn)->table('sub_services')->insert($subscription_data);
            }
        }
    }

    $nlist = $new_numbers->pluck('number')->toArray();
    echo implode('<br>', $nlist);
}

function set_inbound_partner_ids()
{
    $domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($domains as $domain) {
        \DB::connection('pbx_cdr')->table('call_records_inbound')->where('account_id', $domain->account_id)->update(['partner_id' => $domain->partner_id]);
    }

}

function search_full_xml()
{
    $file_path = '/home/erp/storage/porting_input/DWNLDS/FCRDBDownload20221003151740.xml';
    $reader = new XMLReader;

    $reader->open($file_path);

    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'ActivatedNumber') {
            $ported_number = new SimpleXMLElement($reader->readOuterXML());

            if (! empty($ported_number->MSISDN)) {
                $msisdn = (string) $ported_number->MSISDN;
            }
            if ($msisdn == '27824119555') {
                aa($msisdn);
                aa($ported_number);
            }
            unset($msisdn);
            unset($ported_number);
            $reader->next('ActivatedNumber');
        }
    }
    $reader->close();
}

function voipalot_rate_import()
{
    $file = public_path().'voipalot2022.xlsx';
    $gateway_uuid = 'ddb0357e-1aff-4a1e-8982-984675169400';
    $rates = file_to_array($file);

    $usd_exchange = convert_currency_usd_to_zar(1);
    $filtered_rates = [];
    foreach ($rates as $rate) {
        $rate['destination'] = strtolower($rate['destination']);
        if (str_contains(strtolower($rate['destination']), 'non-eu-callerid')) {
            continue;
        }

        $cost = str_replace('$ ', '', $rate['rate']);
        $cost = $cost * $usd_exchange;
        if (str_contains($rate['destination'], '[')) {
            $dest_arr = explode('[', $rate['destination']);
            $country = trim($dest_arr[0]);
        } elseif (str_contains($rate['destination'], '(')) {
            $dest_arr = explode('(', $rate['destination']);
            $country = trim($dest_arr[0]);
        } else {
            $country = $rate['destination'];
        }
        $destination = $rate['destination'];
        $filtered_rates[] = ['country' => $country, 'destination' => $destination, 'cost' => $cost, 'usd_cost' => $rate['rate']];
    }

    foreach ($rates as $rate) {
        $rate['destination'] = strtolower($rate['destination']);
        if (! str_contains(strtolower($rate['destination']), 'non-eu-callerid')) {
            continue;
        }
        //dd($rate);
        $cost = str_replace('$ ', '', $rate['rate']);
        $cost = $cost * $usd_exchange;
        if (str_contains($rate['destination'], '[')) {
            $dest_arr = explode('[', $rate['destination']);
            $country = trim($dest_arr[0]);
        } elseif (str_contains($rate['destination'], '(')) {
            $dest_arr = explode('(', $rate['destination']);
            $country = trim($dest_arr[0]);
        } else {
            $country = $rate['destination'];
        }
        $destination = $rate['destination'];

        foreach ($filtered_rates as $i => $filtered_rate) {
            if ($filtered_rate['country'] == $country && str_contains($filtered_rate['destination'], 'landline') && str_contains($destination, 'landline')) {
                $filtered_rates[$i] = ['country' => $country, 'destination' => $destination, 'cost' => $cost, 'usd_cost' => $rate['rate']];
            }
            if ($filtered_rate['country'] == $country && str_contains($filtered_rate['destination'], 'mobile') && str_contains($destination, 'mobile')) {
                $filtered_rates[$i] = ['country' => $country, 'destination' => $destination, 'cost' => $cost, 'usd_cost' => $rate['rate']];
            }
        }
    }

    \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway_uuid)->delete();
    \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $gateway_uuid)->delete();

    foreach ($filtered_rates as $rate) {
        $country = $rate['country'];
        $cost = $rate['cost'];

        $destinations = [];
        if ($country == 'south africa') {
            if (str_contains($rate['destination'], 'mobile')) {
                $destinations = \DB::connection('pbx')->table('p_rates_destinations')
                    ->where('country', 'south africa')
                    ->whereIn('destination', ['mobile vodacom', 'mobile mtn', 'mobile cellc', 'mobile telkom'])
                    ->get();
            } else {
                $destinations = \DB::connection('pbx')->table('p_rates_destinations')
                    ->where('country', 'south africa')
                    ->where('destination', 'not like', '%mobile%')
                    ->get();
            }
        } else {
            if (str_contains($rate['destination'], 'mobile')) {
                $destinations = \DB::connection('pbx')->table('p_rates_destinations')
                    ->where('country', $country)
                    ->where('destination', 'like', '%mobile%')
                    ->get();
            } else {
                $destinations = \DB::connection('pbx')->table('p_rates_destinations')
                    ->where('country', $country)
                    ->where('destination', 'not like', '%mobile%')
                    ->get();
            }
        }

        if (! empty($destinations) && $destinations->count() > 0) {
            foreach ($destinations as $dest) {
                $data = [
                    'destination_id' => $dest->id,
                    'country' => $dest->country,
                    'destination' => $dest->destination,
                    'cost' => $cost,
                    'gateway_uuid' => $gateway_uuid,
                ];

                $c = \DB::connection('pbx')->table('p_rates_complete')->where('destination_id', $dest->id)->where('gateway_uuid', $gateway_uuid)->count();
                if (! $c) {
                    \DB::connection('pbx')->table('p_rates_complete')->insert($data);
                }
            }
        }
    }

    import_rates_summary_from_rates_complete($gateway_uuid);
    rates_complete_set_lowest_rate();
}

function copy_module_fields()
{
    $ts = ['erp_module_fields', 'erp_forms'];
    foreach ($ts as $t) {
        \DB::table($t)->where('module_id', 809)->delete();
        $fs = \DB::table($t)->where('module_id', 508)->get();
        foreach ($fs as $f) {
            $d = (array) $f;
            unset($d['id']);
            $d['module_id'] = 809;
            \DB::table($t)->insert($d);
        }
    }
}

function copy_mod()
{
    $id = 200;
    $conn = 'telecloud';
    $mod = \DB::connection($conn)->table('erp_cruds')->where('id', $id)->get()->first();

    $data = (array) $mod;

    $data['db_table'] = 'p_phone_numbers';
    unset($data['id']);

    $c = 'telecloud';
    $new_id = \DB::connection($c)->table('erp_cruds')->insertGetId($data);

    $fields = \DB::connection($conn)->table('erp_module_fields')->where('module_id', $id)->get();
    $forms = \DB::connection($conn)->table('erp_forms')->where('module_id', $id)->get();
    $views = \DB::connection($conn)->table('erp_grid_views')->where('module_id', $id)->get();
    $events = \DB::connection($conn)->table('erp_form_events')->where('type', '!=', 'schedule')->where('module_id', $id)->get();
    $menu = \DB::connection($conn)->table('erp_menu')->where('module_id', $id)->get();
    $buttons = \DB::connection($conn)->table('erp_menu')->where('render_module_id', $id)->get();

    foreach ($fields as $f) {
        $d = (array) $f;
        unset($d['id']);
        $d['module_id'] = $new_id;
        $d['alias'] = $data['db_table'];
        $cols = get_columns_from_schema('erp_module_fields', null, $c);
        foreach ($d as $k => $v) {
            if (! in_array($k, $cols)) {
                unset($d[$k]);
            }
        }
        \DB::connection($c)->table('erp_module_fields')->insert($d);
    }
    foreach ($forms as $f) {
        $d = (array) $f;
        unset($d['id']);
        $d['module_id'] = $new_id;
        \DB::connection($c)->table('erp_forms')->insert($d);
    }
    foreach ($views as $f) {
        $d = (array) $f;
        unset($d['id']);
        $d['module_id'] = $new_id;
        \DB::connection($c)->table('erp_grid_views')->insert($d);
    }
    foreach ($events as $f) {
        $d = (array) $f;
        unset($d['id']);
        $d['module_id'] = $new_id;
        \DB::connection($c)->table('erp_form_events')->insert($d);
    }
    foreach ($menu as $f) {
        $d = (array) $f;
        unset($d['id']);
        $d['module_id'] = $new_id;
        $mid = \DB::connection($c)->table('erp_menu')->insertGetId($d);
        $permissions = \DB::table('erp_menu_role_access')->where('menu_id', $f->id)->get();
        foreach ($permissions as $p) {
            $r = (array) $p;
            unset($r['id']);
            $r['menu_id'] = $mid;
            DB::connection($c)->table('erp_menu_role_access')->insert($r);
        }
    }

    foreach ($buttons as $f) {
        $d = (array) $f;
        unset($d['id']);
        $d['render_module_id'] = $new_id;
        $mid = \DB::connection($c)->table('erp_menu')->insertGetId($d);
        $permissions = \DB::connection($c)->table('erp_menu_role_access')->where('menu_id', $f->id)->get();
        foreach ($permissions as $p) {
            $r = (array) $p;
            unset($r['id']);
            $r['menu_id'] = $mid;
            DB::connection($c)->table('erp_menu_role_access')->insert($r);
        }
    }

}

function reset_menu_by_role()
{
    /*
    $role_id = 2;
    $role_ids = [1,2];
    $submenus = get_submenu_ids(1654);
    foreach($submenus as $id){
        $menu = \DB::table('erp_menu')->where('id',$id)->get()->first();
        \DB::table('erp_menu_role_access')->where('menu_id',$id)->whereNotIn('role_id',$role_ids)->update(['is_menu'=>0]);
        if($menu->module_id > 0){

            \DB::table('erp_forms')->where('module_id',$menu->module_id)->whereNotIn('role_id',$role_ids)->update(['is_view'=>0,'is_add'=>0,'is_edit'=>0,'is_delete'=>0]);
            $e =  \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',$role_id)->count();
            if(!$e){
                $data = \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',1)->get()->first();
                $data = (array) $data;
                unset($data['id']);
                unset($data['is_delete']);
                $data['role_id'] = $role_id;
                \DB::table('erp_forms')->insert($data);
            }
        }
    }


    $role_id = 6;
    $role_ids = [1,6];
    $submenus = get_submenu_ids(2568);
    foreach($submenus as $id){
        $menu = \DB::table('erp_menu')->where('id',$id)->get()->first();
        \DB::table('erp_menu_role_access')->where('menu_id',$id)->whereNotIn('role_id',$role_ids)->update(['is_menu'=>0]);
        if($menu->module_id > 0){

            \DB::table('erp_forms')->where('module_id',$menu->module_id)->whereNotIn('role_id',$role_ids)->update(['is_view'=>0,'is_add'=>0,'is_edit'=>0,'is_delete'=>0]);
            $e =  \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',$role_id)->count();
            if(!$e){
                $data = \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',1)->get()->first();
                $data = (array) $data;
                unset($data['id']);
                unset($data['is_delete']);
                $data['role_id'] = $role_id;
                \DB::table('erp_forms')->insert($data);
            }
        }
    }

    $role_id = 5;
    $role_ids = [1,5];
    $submenus = get_submenu_ids(1697);
    foreach($submenus as $id){
        $menu = \DB::table('erp_menu')->where('id',$id)->get()->first();
        \DB::table('erp_menu_role_access')->where('menu_id',$id)->whereNotIn('role_id',$role_ids)->update(['is_menu'=>0]);
        if($menu->module_id > 0){

            \DB::table('erp_forms')->where('module_id',$menu->module_id)->whereNotIn('role_id',$role_ids)->update(['is_view'=>0,'is_add'=>0,'is_edit'=>0,'is_delete'=>0]);
            $e =  \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',$role_id)->count();
            if(!$e){
                $data = \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',1)->get()->first();
                $data = (array) $data;
                unset($data['id']);
                unset($data['is_delete']);
                $data['role_id'] = $role_id;
                \DB::table('erp_forms')->insert($data);
            }
        }
    }

    $role_id = 8;
    $role_ids = [1,8];
    $submenus = get_submenu_ids(1732);
    foreach($submenus as $id){
        $menu = \DB::table('erp_menu')->where('id',$id)->get()->first();
        \DB::table('erp_menu_role_access')->where('menu_id',$id)->whereNotIn('role_id',$role_ids)->update(['is_menu'=>0]);
        if($menu->module_id > 0){

            \DB::table('erp_forms')->where('module_id',$menu->module_id)->whereNotIn('role_id',$role_ids)->update(['is_view'=>0,'is_add'=>0,'is_edit'=>0,'is_delete'=>0]);
            $e =  \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',$role_id)->count();
            if(!$e){
                $data = \DB::table('erp_forms')->where('module_id',$menu->module_id)->where('role_id',1)->get()->first();
                $data = (array) $data;
                unset($data['id']);
                unset($data['is_delete']);
                $data['role_id'] = $role_id;
                \DB::table('erp_forms')->insert($data);
            }
        }
    }
    */
}

function create_account_opening_balance($account_id, $balance)
{
    /*

    $accounts = \DB::connection('turnkey')->table('crm_accounts')
    ->where('status','!=','Deleted')
    ->where('type','customer')
    ->where('id','!=',10016)
    ->where('id','!=',9995)
    ->get();
    $schema = get_complete_schema('pbx');

    foreach($accounts as $account){

        $new_account_id = copy_turnkey_to_cloud($account->id);
        if($new_account_id){
            $exchange_rate = get_exchange_rate(date('Y-m-d'),'ZAR','USD');
            if($account->balance){
                $zar_balance = $account->balance / $exchange_rate;
                if($zar_balance){
                    create_account_opening_balance($new_account_id,$zar_balance);
                }
            }
            foreach($schema as $table => $cols){

                if(in_array('account_id',$cols)){
                    \DB::connection('pbx')->table($table)->where('account_id',$account->id)->update(['account_id' => $new_account_id]);
                }
            }
        }
    }


    */

    $trx_data = [
        'docdate' => date('Y-m-d'),
        'doctype' => 'General Journal',
        'name' => 'Account Opening Balance',
    ];

    $transaction_id = \DB::table('acc_general_journal_transactions')->insertGetId($trx_data);

    $amount = $debt_total;
    $data = [
        'transaction_id' => $transaction_id,
        'account_id' => $account_id,
        'debit_amount' => $balance,
        'reference' => 'Account Opening Balance',
        'ledger_account_id' => 5,
    ];

    $db = new DBEvent;
    $result = $db->setTable('acc_general_journals')->save($data);

    $data['credit_amount'] = $data['debit_amount'];
    $data['debit_amount'] = 0;
    $data['ledger_account_id'] = 51;
    $result = $db->setTable('acc_general_journals')->save($data);

    (new DBEvent)->setAccountAging($account_id, 1, false);
}

function button_to_menu_all()
{
    return false;
    $db_conns = db_conns();

    foreach ($db_conns as $c) {
        $menu_ids = \DB::connection($c)->table('erp_menu')->where('location', 'grid_menu')->pluck('id')->toArray();
        \DB::connection($c)->table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->delete();
        \DB::connection($c)->table('erp_menu')->whereIn('id', $menu_ids)->delete();
        $module_ids = \DB::connection($c)->table('erp_cruds')->pluck('id')->toArray();
        foreach ($module_ids as $module_id) {
            button_to_menu($module_id, $c, $module_ids);
        }
    }
}
function button_to_menu($module_id, $conn, $module_ids)
{
    $role_ids = \DB::connection($conn)->table('erp_user_roles')->pluck('id')->filter()->unique()->toArray();
    $button_groups = \DB::connection($conn)->table('erp_grid_buttons')->where('module_id', $module_id)->pluck('button_group')->filter()->unique()->toArray();
    $app_id = \DB::connection($conn)->table('erp_cruds')->where('id', $module_id)->pluck('app_id')->first();
    foreach ($button_groups as $button_group) {
        //toplevel menuitem
        $toplevel_data = [
            'app_id' => $app_id,
            'render_module_id' => $module_id,
            'menu_name' => $button_group,
            'url' => '#',
            'menu_type' => 'link',
            'location' => 'grid_menu',
        ];
        $toplevel_id = \DB::connection($conn)->table('erp_menu')->insertGetId($toplevel_data);

        $buttons = \DB::connection($conn)->table('erp_grid_buttons')->where('module_id', $module_id)->where('button_group', $button_group)->get();
        $toplevel_access = [];
        foreach ($buttons as $button) {
            // menu item
            try {
                $menu_data = [
                    'app_id' => $app_id,
                    'render_module_id' => $module_id,
                    'menu_name' => $button->name,
                    'menu_type' => 'link',
                    'location' => 'grid_menu',
                    'require_grid_id' => $button->require_grid_id,
                    'grid_logic' => $button->read_only_logic,
                    'url_params' => $button->redirect_params,
                    'url' => $button->redirect_url,
                    'module_id' => $button->redirect_module_id,
                    'confirm_text' => $button->confirm,
                    'ajax_function_name' => $button->function_name,
                    'action_type' => $button->type,
                    'parent_id' => $toplevel_id,
                    'menu_icon' => $button->icon,
                ];
                if (empty($menu_data['module_id'])) {
                    unset($menu_data['module_id']);
                }

                if (! empty($button->redirect_module_id)) {
                    $menu_data['menu_type'] = 'module';
                }

                if (! empty($menu_data['module_id']) && ! in_array($menu_data['module_id'], $module_ids)) {
                    continue;
                }
                $menu_id = \DB::connection($conn)->table('erp_menu')->insertGetId($menu_data);
            } catch (\Throwable $ex) {
                exception_log($ex);
            }
            // menu item permission
            $access = collect(explode(',', $button->access))->filter()->unique()->toArray();
            foreach ($role_ids as $role_id) {
                $permission = [
                    'menu_id' => $menu_id,
                    'role_id' => $role_id,
                    'is_menu' => (in_array($role_id, $access)) ? 1 : 0,
                ];
                \DB::connection($conn)->table('erp_menu_role_access')->insert($permission);
                if ((in_array($role_id, $access))) {
                    $toplevel_access[] = $role_id;
                }
            }
        }
        // toplevel permission
        $access = collect($toplevel_access)->filter()->unique()->toArray();
        foreach ($role_ids as $role_id) {
            $permission = [
                'menu_id' => $toplevel_id,
                'role_id' => $role_id,
                'is_menu' => (in_array($role_id, $access)) ? 1 : 0,
            ];
            \DB::connection($conn)->table('erp_menu_role_access')->insert($permission);
        }
    }

    $buttons = \DB::connection($conn)->table('erp_grid_buttons')->where('button_group', '')->where('module_id', $module_id)->get();

    foreach ($buttons as $button) {
        // menu item
        try {
            $menu_data = [
                'app_id' => $app_id,
                'render_module_id' => $module_id,
                'menu_name' => $button->name,
                'menu_type' => 'link',
                'location' => 'grid_menu',
                'require_grid_id' => $button->require_grid_id,
                'grid_logic' => $button->read_only_logic,
                'url_params' => $button->redirect_params,
                'url' => $button->redirect_url,
                'module_id' => $button->redirect_module_id,
                'confirm_text' => $button->confirm,
                'ajax_function_name' => $button->function_name,
                'action_type' => $button->type,
                'menu_icon' => $button->icon,
            ];
            if (empty($menu_data['module_id'])) {
                unset($menu_data['module_id']);
            }
            if (! empty($button->redirect_module_id)) {
                $menu_data['menu_type'] = 'module';
            }

            if (! empty($menu_data['module_id']) && ! in_array($menu_data['module_id'], $module_ids)) {
                continue;
            }
            $menu_id = \DB::connection($conn)->table('erp_menu')->insertGetId($menu_data);
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
        // menu item permission
        $access = collect(explode(',', $button->access))->filter()->unique()->toArray();
        foreach ($role_ids as $role_id) {
            $permission = [
                'menu_id' => $menu_id,
                'role_id' => $role_id,
                'is_menu' => (in_array($role_id, $access)) ? 1 : 0,
            ];
            \DB::connection($conn)->table('erp_menu_role_access')->insert($permission);
        }
    }
}

function set_form_names()
{
    $modules = \DB::table('erp_cruds')->get();
    foreach ($modules as $m) {
        $menu_name = \DB::table('erp_menu')->where('module_id', $m->id)->where('menu_type', 'module')->pluck('menu_name')->first();
        if (! $menu_name) {
            $menu_name = \DB::table('erp_menu')->where('module_id', $m->id)->where('menu_type', 'module_form')->pluck('menu_name')->first();
        }
        if (! $menu_name) {
            $menu_name = ucwords(str_replace('_', ' ', $m->name));
        }
        \DB::table('erp_forms')->where('module_id', $m->id)->update(['name' => $menu_name]);
    }
}

function export_opps()
{
    /*
First name,Last name,Source,Campaign,Job title,Emails (Fill any one field: Emails or Mobile),Work,Mobile (Fill any one field: Emails or Mobile),Address,City,State,Zipcode,Country,Owner,Do not disturb,Medium,Keyword,Time zone,Facebook,Twitter,LinkedIn,Account name,Account Address ,Account City,Account State,Account Country,Account Zip code,Account Industry Type,Account Business Type,Account Number of Employees,Account Annual Revenue,Account Website,Account Phone,Account Facebook,Account Twitter,Account Linkedin,Account Territory,Account Owner,External Id,Lifecycle Stage,Contact Status
James,Sampleton (sample),Organic Search,Sample Campaign 1 ,CEO,jamessampleton2@gmail.com,(473)-160-8261,1-926-555-9503,1552 camp st,San Diego,CA,92093,USA,michaelsample@gmail.com,0,Blog,B2B Success,(GMT-7:00) Arizona,https://en-gb.facebook.com/people/James-Sampleton/100010618022475,https://twitter.com/jamessampleton,http://linkedin.com/pub/jane-sampleton/109/39/b0,Widgetz.io (sample),160-6802 Aliquet Rd.,New Haven,Connecticut,United States,68089,Insurance,Competitor,51-200,100000,widgetz.io,503-615-3947,100010587455650,janesampleton,jane-sampleton-0b0039109,NA,michaelsample@gmail.com,43215,Lead,New

    */
    $header_line = 'First name,Last name,Source,Campaign,Job title,Emails (Fill any one field: Emails or Mobile),Work,Mobile (Fill any one field: Emails or Mobile),Address,City,State,Zipcode,Country,Owner,Do not disturb,Medium,Keyword,Time zone,Facebook,Twitter,LinkedIn,Account name,Account Address ,Account City,Account State,Account Country,Account Zip code,Account Industry Type,Account Business Type,Account Number of Employees,Account Annual Revenue,Account Website,Account Phone,Account Facebook,Account Twitter,Account Linkedin,Account Territory,Account Owner,External Id,Lifecycle Stage,Contact Status';
    $data_line = 'James,Sampleton (sample),Organic Search,Sample Campaign 1 ,CEO,jamessampleton2@gmail.com,(473)-160-8261,1-926-555-9503,1552 camp st,San Diego,CA,92093,USA,michaelsample@gmail.com,0,Blog,B2B Success,(GMT-7:00) Arizona,https://en-gb.facebook.com/people/James-Sampleton/100010618022475,https://twitter.com/jamessampleton,http://linkedin.com/pub/jane-sampleton/109/39/b0,Widgetz.io (sample),160-6802 Aliquet Rd.,New Haven,Connecticut,United States,68089,Insurance,Competitor,51-200,100000,widgetz.io,503-615-3947,100010587455650,janesampleton,jane-sampleton-0b0039109,NA,michaelsample@gmail.com,43215,Lead,New';
    $header_arr = explode(',', $header_line);
    $data_arr = explode(',', $data_line);
    $csv = array_combine($header_arr, $data_arr);
}

function payfast_fix()
{
    $transactions = payfast_get_transactions_day('2022-06-13');

    $request = (array) $transactions[0];

    $data = [];
    foreach ($request as $k => $v) {
        $key = strtolower(str_replace(' ', '_', $k));
        $data[$key] = $v;
    }
    $data['payment_status'] = 'COMPLETE';
    $data['amount_fee'] = currency($data['fee']);
    $data['amount_gross'] = currency($data['gross']);
    $data['amount_net'] = currency($data['net']);
    $data['custom_int1'] = 12;
    $data['m_payment_id'] = '12_'.date('U', strtotime($data['date']));

    $request = new \Illuminate\Http\Request($data);
    $request->setMethod('POST');

    $r = app(\App\Http\Controllers\IntegrationsController::class)->payfastResponse($request);
}

function pbx_restore_account($account_id)
{

    //restoreaccount
    $pbx_tables = get_tables_from_schema('backup_pbx');
    $sub_accounts = \DB::table('crm_accounts')->where('id', $account_id)->get();
    foreach ($sub_accounts as $sub_account) {
        $pbxdomain = \DB::connection('backup_pbx')->table('v_domains')->where('account_id', $sub_account->id)->get()->first();
        if ($pbxdomain) {
            $table_data = [];
            foreach ($pbx_tables as $table) {
                $cols = get_columns_from_schema($table, null, 'backup_pbx');
                if (in_array('domain_uuid', $cols)) {
                    $table_data[$table] = \DB::connection('backup_pbx')->table($table)->where('domain_uuid', $pbxdomain->domain_uuid)->get();
                }
            }

            foreach ($table_data as $key => $data) {
                try {
                    if ($key == 'p_airtime_history') {
                        continue;
                    }
                    if (str_contains($key, '_copy') || $key == 'v_phone_numbers' || $key == 'v_database_transactions') {
                        continue;
                    }
                    foreach ($data as $row) {
                        if ($key == 'p_phone_numbers') {
                            \DB::connection('pbx')->table($key)->where('number', $row->number)->update(['domain_uuid' => $row->domain_uuid, 'status' => 'Enabled']);
                        } else {
                            if (! empty($row->id)) {
                                \DB::connection('pbx')->table($key)->where('id', $row->id)->count();
                                if (! $count) {
                                    $row = (array) $row;
                                    \DB::connection('pbx')->table($key)->insert($row);
                                }
                            } else {
                                $row = (array) $row;
                                \DB::connection('pbx')->table($key)->insert($row);
                            }
                        }
                    }
                } catch (\Throwable $ex) {
                    exception_log($ex);
                }
            }
        }
        \DB::table('sub_services')->where(
            'account_id',
            $sub_account->id
        )->where('deleted_at', '2024-05-25')->update(['status' => 'Enabled', 'deleted_at' => null]);
    }
}

function pbx_restore_phone_numbers($account_id)
{

    //restoreaccount
    $pbx_tables = get_tables_from_schema('backup_pbx');
    $sub_accounts = \DB::table('crm_accounts')->where('id', $account_id)->get();
    foreach ($sub_accounts as $sub_account) {
        $pbxdomain = \DB::connection('backup_pbx')->table('v_domains')->where('account_id', $sub_account->id)->get()->first();
        if ($pbxdomain) {
            $table_data = [];
            foreach ($pbx_tables as $table) {
                $cols = get_columns_from_schema($table, null, 'backup_pbx');
                if (in_array('domain_uuid', $cols)) {
                    $table_data[$table] = \DB::connection('backup_pbx')->table($table)->where('domain_uuid', $pbxdomain->domain_uuid)->get();
                }
            }

            foreach ($table_data as $key => $data) {
                try {
                    if ($key == 'p_airtime_history') {
                        continue;
                    }
                    if (str_contains($key, '_copy') || $key == 'v_phone_numbers' || $key == 'v_database_transactions') {
                        continue;
                    }
                    foreach ($data as $row) {
                        if ($key == 'p_phone_numbers') {
                            $update_data = (array) $row;
                            \DB::connection('pbx')->table($key)->where('number', $row->number)->update($update_data);
                        } else {

                        }
                    }
                } catch (\Throwable $ex) {
                    exception_log($ex);
                }
            }
        }
    }
}

function vca_cdr_compare()
{
    /*
    $from_date = \DB::connection('pbx_cdr')->table('vca_cdr')->where('centracom_cdr_id','>',0)->orderby('call_time','desc')->pluck('call_time')->first();
    $from_date = date('Y-m-d', strtotime($from_date));
    $to_date = date('Y-m-t', strtotime($from_date));
    $missing_dates = [];

    while ($from_date <= $to_date) {
       $c = \DB::connection('pbx_cdr')->table('centracom_cdr')->where('call_time', 'LIKE', $from_date.'%')->get();
       foreach($c as $cc){
           \DB::connection('pbx_cdr')->table('vca_cdr')->where('call_time','like',date('Y-m-d H:i',strtotime($cc->call_time. ' -2 hours')).'%')->where('dialed_number',$cc->to)->update(['centracom_cdr_id'=> $cc->id]);
       }

        $from_date = date('Y-m-d', strtotime($from_date.' +1 day'));
    }*/

    $vv = \DB::connection('pbx_cdr')->table('vca_cdr')->where('centracom_cdr_id', 0)->get();
    foreach ($vv as $v) {
        $c_id = \DB::connection('pbx_cdr')->table('centracom_cdr')
            ->where('call_time', 'like', date('Y-m-d H:i', strtotime($v->call_time.' +2 hours')).'%')
            ->where('to', $v->dialed_number)->plucK('id')->first();
        if ($c_id) {
            \DB::connection('pbx_cdr')->table('vca_cdr')->where('id', $v->id)->update(['centracom_cdr_id' => $c_id]);
        }
    }
}

function composer_require_package()
{
    $process = new Symfony\Component\Process\Process(['cd '.base_path().' && composer require dompdf']);
    $r = $process->run();
}

function clear_composer()
{
    $process = new Symfony\Component\Process\Process(['cd '.base_path().' && composer dump-autoload']);
    $r = $process->run();
}

function ledger_list()
{
    $ledger_account_ids = \DB::table('acc_ledgers')->where('docdate', '>=', '2019-03-01')->where('docdate', '<', '2020-03-01')->where('ledger_account_id', 5)->pluck('account_id')->unique()->toArray();
    $ledger_amount = \DB::table('acc_ledgers')->where('docdate', '<', '2020-03-01')->where('ledger_account_id', 5)->sum('amount');

    foreach ($ledger_account_ids as $account_id) {
        $ledger_amount = \DB::table('acc_ledgers')->where('docdate', '<', '2020-03-01')->where('ledger_account_id', 5)->where('account_id', $account_id)->sum('amount');
        if ($ledger_amount != 0) {
            $account = dbgetaccount($account_id);
        }
    }
}

function dalist()
{
    $da = new DirectAdmin;
    $domain_list = $da->getDomainList();
    foreach ($domain_list as $user => $domains) {
        foreach ($domains as $domain_name => $domain_data) {
            $domain_name = str_replace('_', '.', $domain_name);
            $deleted = \DB::table('sub_services')->where('status', 'Deleted')->where('detail', $domain_name)->count();
            if ($deleted) {
            }
        }
    }
}

function verify_schema()
{
    $main_schema = get_complete_schema('system');

    $schemas = [];
    $conns = \DB::table('erp_instances')->where('installed', 1)->where('id', '!=', 1)->pluck('db_connection')->toArray();
    foreach ($conns as $c) {
        $schemas[$c] = get_complete_schema($c);
    }

    foreach ($main_schema as $table => $cols) {
        foreach ($schemas as $conn => $schema) {
            if (isset($schema[$table])) {
                foreach ($cols as $col) {
                    if (! in_array($col, $schema[$table])) {
                    }
                }
            }
        }
    }
}

function delete_duplicates()
{
    // to add unique index
    /*
    SELECT * FROM 'table'
    GROUP BY 'column'
    HAVING COUNT('column') > 1
    */
    return false;

    $conn = 'pbx';
    $table = 'table';
    $field = 'field';

    $rows = \DB::connection($conn)->select(" SELECT * FROM $table
    GROUP BY $field
    HAVING COUNT($field) > 1");

    foreach ($rows as $row) {
        \DB::connection($conn)->table($table)->where('id', '!=', $row->id)->where($field, $row->{$field})->delete();
    }
}

function copy_sidebar_modules()
{
    $conns = \DB::table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
    foreach ($conns as $c) {
        //  \DB::connection($c)->table('erp_related_modules')->truncate();
        $buttons = \DB::connection($c)->table('erp_grid_buttons')->where('type', 'modal_view')->where('module_id', '>', 0)->where('require_grid_id', 0)->get();
        $i = 0;
        foreach ($buttons as $button) {
            $menu_id = \DB::connection($c)->table('erp_menu')->where('menu_type', 'LIKE', '%module%')->where('module_id', $button->module_id)->pluck('id')->first();
            $related_menu_id = \DB::connection($c)->table('erp_menu')->where('menu_type', 'LIKE', '%module%')->where('module_id', $button->redirect_module_id)->pluck('id')->first();
            if ($menu_id && $related_menu_id) {
                $i++;
                \DB::connection($c)->table('erp_grid_buttons')->where('id', $button->id)->delete();
                $data = [
                    'menu_id' => $menu_id,
                    'related_menu_id' => $related_menu_id,
                    'sort_order' => $i,
                ];
                \DB::connection($c)->table('erp_related_modules')->insert($data);
            }
        }
    }
}

function check_pbx_sub_numbers()
{
    $nums = \DB::table('sub_services')->where('provision_type', 'phone_number')->where('status', '!=', 'Deleted')->get();
    foreach ($nums as $n) {
        $a = dbgetaccount($n->account_id);

        $e = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $n->detail)->get()->first();
        if (! $e) {
        } else {
            $d = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $a->domain_uuid)->pluck('domain_name')->first();
            if ($d != $a->pabx_domain) {
            }
        }
    }
}

function dd_exception($ex)
{
    $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
}

function cloudtools_dns()
{
    $ix = new Interworx;
    $result = $ix->verifySubscriptions();

    // $dns_records = $ix->getPbxDnsRecords();
    // $dns_records = collect($dns_records)->pluck('host')->toArray();
    // $voice_domains = \DB::connection('pbx')->table('v_domains')->pluck('domain_name')->toArray();
    // foreach ($voice_domains as $voice_domain) {
    //     if (in_array($voice_domain, $dns_records)) {
    //     } else
    //         $result[] = $ix->addPbxDns($voice_domain);
    // }
}

function netcash_test()
{
    $n = new NetCash;

    // $r = $n->getStatementPollingId('2021-04-24');
    $r = $n->retrieveStatement('637548192000000000');
}

function reports_search_replace($searh_text, $replace_text)
{
    $instance_connections = DB::table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
    foreach ($instance_connections as $conn) {
        $reports = DB::connection($conn)->table('erp_reports')->where('sql_query', 'LIKE', '%'.$searh_text.'%')->get();

        foreach ($reports as $r) {
            $data = [
                'sql_query' => preg_replace('/\b'.$searh_text.'\b/', $replace_text, $r->sql_query),
                'sql_where' => preg_replace('/\b'.$searh_text.'\b/', $replace_text, $r->sql_where),
            ];
            $query_data = unserialize($r->query_data);
            $search = $searh_text;
            $replace = $replace_text;
            array_walk_recursive(
                $query_data,
                function (&$value, $count, $params) {
                    $value = preg_replace('/\b'.$params['search'].'\b/', $params['replace'], $value);
                },
                ['search' => $search, 'replace' => $replace]
            );
            $data['query_data'] = serialize($query_data);
            DB::connection($conn)->table('erp_reports')->where('id', $r->id)->update($data);
        }
    }
}

function running_total_sql()
{
    $sql = "SELECT d.date,
       @running_sum:=@running_sum + d.count AS running
  FROM (  SELECT date, COUNT(*) AS 'count'
            FROM table1
           WHERE date > '2011-09-29' AND applicationid = '123'
        GROUP BY date
        ORDER BY date ) d
  JOIN (SELECT @running_sum := 0 AS dummy) dummy;";
}

function restore_pbx_domain()
{
    return false;
    $old_domain_uuid = \DB::connection('backup_pbx')->table('v_domains')->where('account_id', 3675)->pluck('domain_uuid')->first();
    //dd($old_domain_uuid);
    $new_domain_uuid = '27adaaa0-ffa2-4db2-a01c-34fcf78b7284';
    $schema = get_complete_schema('pbx');
    /*
    foreach ($schema as $table => $columns) {
        if (in_array('domain_uuid', $columns)) {
            \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $old_domain_uuid)->update(['domain_uuid' => $new_domain_uuid]);
        }
    }

    $exts =  \DB::connection('backup_pbx')->table('v_follow_me')->where('domain_uuid', $old_domain_uuid)->get();
    foreach ($exts as $ext) {
        $data = (array) $ext;
        $data['domain_uuid'] = $new_domain_uuid;
        \DB::connection('pbx')->table('v_follow_me')->insert($data);
    }
    $exts =  \DB::connection('backup_pbx')->table('v_ivr_menus')->where('domain_uuid', $old_domain_uuid)->get();
    foreach ($exts as $ext) {
        $data = (array) $ext;
        $data['domain_uuid'] = $new_domain_uuid;
        \DB::connection('pbx')->table('v_ivr_menus')->insert($data);
    }
    $exts =  \DB::connection('backup_pbx')->table('v_dialplans')->where('domain_uuid', $old_domain_uuid)->get();
    foreach ($exts as $ext) {
        $data = (array) $ext;
        $data['domain_uuid'] = $new_domain_uuid;
        \DB::connection('pbx')->table('v_dialplans')->insert($data);
    }
    $exts =  \DB::connection('backup_pbx')->table('v_dialplan_details')->where('domain_uuid', $old_domain_uuid)->get();
    foreach ($exts as $ext) {
        $data = (array) $ext;
        $data['domain_uuid'] = $new_domain_uuid;
        \DB::connection('pbx')->table('v_dialplan_details')->insert($data);
    }
    */
    $exts = \DB::connection('backup_pbx')->table('v_recordings')->where('domain_uuid', $old_domain_uuid)->get();
    foreach ($exts as $ext) {
        $data = (array) $ext;
        $data['domain_uuid'] = $new_domain_uuid;
        \DB::connection('pbx')->table('v_recordings')->insert($data);
    }
}

function supplier_control_check()
{
    $id = 4844;
    //(new DBEvent())->setAccountAging(9776,1);
    $trx = get_creditor_transactions($id);
    $aging = build_aging($id, 'supplier');
    $suppplier_control = \DB::select("SELECT acc_ledgers.docid as 'al docid',
    acc_ledgers.docdate as 'al docdate',
    acc_ledgers.doctype as 'al doctype',
    acc_ledgers.amount as 'al amount',
    acc_ledger_accounts.name as 'ala name',
    crm_suppliers.company as 'ca company',
    crm_suppliers.status as 'ca status',
    DATE_FORMAT(acc_ledgers.docdate,
    '%Y-%m') as 'al docdate_period',
    CURDATE() as today 
    FROM 'acc_ledgers' 
    LEFT JOIN 'crm_suppliers' on 'acc_ledgers'.'supplier_id' = 'crm_suppliers'.'id' 
    LEFT JOIN 'acc_ledger_accounts' on 'acc_ledgers'.'ledger_account_id' = 'acc_ledger_accounts'.'id' where acc_ledgers.ledger_account_id=6 and acc_ledgers.supplier_id=".$id);

    $suppplier_control_sum = \DB::select("SELECT 
    sum(acc_ledgers.amount) as 'al amount'
    FROM 'acc_ledgers' 
    LEFT JOIN 'crm_suppliers' on 'acc_ledgers'.'supplier_id' = 'crm_suppliers'.'id' 
    LEFT JOIN 'acc_ledger_accounts' on 'acc_ledgers'.'ledger_account_id' = 'acc_ledger_accounts'.'id' where acc_ledgers.ledger_account_id=6 and acc_ledgers.supplier_id=".$id);

}

function supplier_control_compare()
{
    $suppliers = \DB::table('crm_suppliers')->get();
    foreach ($suppliers as $supplier) {
        $aging = build_aging($supplier->id, 'supplier');

        \DB::table('crm_suppliers')->where('id', $supplier->id)->update(['balance' => $aging['balance']]);
    }

    $suppliers = \DB::table('crm_suppliers')->get();
    foreach ($suppliers as $supplier) {
        $suppplier_control_sum = \DB::select("SELECT 
        sum(acc_ledgers.amount) as 'amount'
        FROM 'acc_ledgers' 
        LEFT JOIN 'crm_suppliers' on 'acc_ledgers'.'supplier_id' = 'crm_suppliers'.'id' 
        LEFT JOIN 'acc_ledger_accounts' on 'acc_ledgers'.'ledger_account_id' = 'acc_ledger_accounts'.'id' 
        where acc_ledgers.ledger_account_id=6 and acc_ledgers.supplier_id=".$supplier->id);

        if (currency($suppplier_control_sum[0]->amount) != currency($supplier->balance)) {
        }
    }
}

function customer_control_compare()
{
    $customers = \DB::table('crm_accounts')->where('partner_id', 1)->get();

    foreach ($customers as $customer) {
        $customer_control_sum = \DB::select("SELECT 
        sum(acc_ledgers.amount) as 'amount'
        FROM 'acc_ledgers' 
        LEFT JOIN 'crm_accounts' on 'acc_ledgers'.'account_id' = 'crm_accounts'.'id' 
        LEFT JOIN 'acc_ledger_accounts' on 'acc_ledgers'.'ledger_account_id' = 'acc_ledger_accounts'.'id' 
        where acc_ledgers.ledger_account_id=5 and acc_ledgers.account_id=".$customer->id);

        if (currency($customer_control_sum[0]->amount) != currency($customer->balance)) {
        }
    }
}

function copy_archive_reports()
{
    $tables = get_tables_from_schema('pbx_cdr');
    foreach ($tables as $i => $table) {
        if (! str_contains($table, 'call_records_20')) {
            unset($tables[$i]);
        }
    }
    $tables[] = 'call_records_outbound_lastmonth';
    $search = 'call_records_2020aug';

    foreach ($tables as $table) {
        if ($table == $search) {
            continue;
        }
        $report = \DB::table('erp_reports')->where('connection', 'pbx_cdr')->where('id', 201)->get()->first();
        $row = (array) $report;
        $data = [];
        foreach ($row as $k => $v) {
            $data[$k] = str_replace($search, $table, $v);
        }
        $data['name'] .= $table;
        unset($data['id']);
        \DB::table('erp_reports')->where('id', $report->id)->insert($data);
    }
}

function yodlee_details() {}

function update_all_report_sql()
{
    $erp_reports = new \ErpReports;
    $instance_connections = DB::table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
    foreach ($instance_connections as $conn) {
        $erp_reports->setErpConnection($conn);
        $reports = \DB::connection($conn)->table('erp_reports')->where('sql_query', 'like', '%call_records_outbound%')->get();
        foreach ($reports as $report) {
            $sql = $erp_reports->reportSQL($report->id);
            \DB::connection($conn)->table('erp_reports')->where('id', $report->id)->update(['sql_query' => $sql]);
        }
    }
}

function website_import()
{
    $r = \DB::connection('shop')->table('customers')->get();

    $r = \DB::connection('shop')->table('orders')->get();
}

function update_report_server_ip()
{
    $conns = \DB::table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
    foreach ($conns as $c) {
        $reports = \DB::connection($c)->table('erp_reports')->get();
        foreach ($reports as $report) {
            $data = [
                'report_config' => str_replace('156.0.96.101', '156.0.96.73', $report->report_config),
            ];
            \DB::connection($c)->table('erp_reports')->where('id', $report->id)->update($data);
        }
    }
}

function update_nameservers()
{
    $nameservers = ['host1.cloudtools.co.za', 'host2.cloudtools.co.za'];
    $domains = ['alasr.co.za'];

    foreach ($domains as $domain) {
        $tld = get_tld($domain);
        $zacr = new Zacr($tld);
        $result = $zacr->nameserver_update($domain, $nameservers);
    }
}

function sms_notification()
{
    $msg = 'We are currently experiencing throughput issues with Openserve, our technical team is working on the issue. Thank you for your patience. Cloud Telecoms';
    $fibre_accounts = \DB::table('sub_services')->where('provision_type', 'fibre')->pluck('account_id')->unique()->toArray();
    foreach ($fibre_accounts as $account_id) {
        $account = dbgetaccount($account_id);
        // queue_sms(12,$account->phone,$msg);
    }
}

function restore_pbx_domain_from_backup()
{
    $domain_uuids = ['9d39861c-3387-40c4-ad6c-732b53f1e897'];
    $tables = get_tables_from_schema('backup_pbx');
    foreach ($tables as $table) {
        if (str_contains($table, 'p_airtime_history')) {
            continue;
        }
        if (str_contains($table, '_copy')) {
            continue;
        }
        if (str_contains($table, '_old')) {
            continue;
        }
        if (str_contains($table, 'phone_number')) {
            continue;
        }
        $cols = get_columns_from_schema($table, null, 'backup_pbx');
        if (in_array('domain_uuid', $cols)) {
            foreach ($domain_uuids as $domain_uuid) {
                $records = \DB::connection('backup_pbx')->table($table)->where('domain_uuid', $domain_uuid)->get();
                //dd($records);
                if (! empty($records) && count($records) > 0) {
                    foreach ($records as $row) {
                        $data = (array) $row;
                        unset($data['id']);
                        \DB::connection('pbx')->table($table)->insert($data);
                    }
                }
            }
        }
    }
}

function fix_permisssion_ids()
{
    $menu_permissions = \DB::table('erp_menu_role_access')->get();
    $instance_connections = DB::table('erp_instances')->where('installed', 1)->where('sync_erp', 1)->pluck('db_connection')->toArray();
    foreach ($instance_connections as $conn) {
        $role_ids = \DB::connection($conn)->table('erp_user_roles')->pluck('id')->toArray();
        \DB::connection($conn)->table('erp_menu_role_access')->whereNotIn('role_id', $role_ids)->delete();
        $menu_ids = \DB::connection($conn)->table('erp_menu')->pluck('id')->toArray();
        \DB::connection($conn)->table('erp_menu_role_access')->whereNotIn('menu_id', $menu_ids)->delete();
        foreach ($menu_permissions as $m) {
            $exists = \DB::connection($conn)->table('erp_menu_role_access')->where('menu_id', $m->menu_id)->where('role_id', $m->role_id)->count();
            if ($exists) {
                $data = (array) $m;
                \DB::connection($conn)->table('erp_menu_role_access')->where('menu_id', $m->menu_id)->where('role_id', $m->role_id)->update($data);
            }
        }
    }
}

function set_menu_permissions($menu_id)
{
    $user_groups = \DB::table('erp_user_roles')->pluck('id')->toArray();

    foreach ($user_groups as $user_group) {
        $is_menu = ($user_group < 10) ? 1 : 0;
        $data = [
            'menu_id' => $menu_id,
            'role_id' => $user_group,
            'is_menu' => $is_menu,
        ];
        \DB::table('erp_menu_role_access')->insert($data);
    }
}

function pbx_curl_delete($domain_name, $switch = false)
{
    $domain_uuid = \DB::connection('pbx')
        ->table('v_domains')
        ->where('domain_name', $domain_name)
        ->pluck('domain_uuid')->first();
    $root_api_key = 'e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
    $root_domain_uuid = '4ae2a2de-6473-4bc1-b307-a35a507a98b2';
    $root_api_key = 'e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
    if ($switch) {
        $url = 'http://156.0.96.61/core/domain_settings/domain_delete.php?key='.$root_api_key.'&domain_uuid='.$root_domain_uuid.'&id='.$domain_uuid;
    } else {
        $url = 'http://156.0.96.60/core/domain_settings/domain_delete.php?key='.$root_api_key.'&domain_uuid='.$root_domain_uuid.'&id='.$domain_uuid;
    }

    $status = curlPost($url, null, false);
}

function restore_pbx_balances()
{
    $domain_names = ['lti.cloudtools.co.za', 'reshetcall.cloudtools.co.za', 'vca.cloudtools.co.za'];
    $domains = \DB::connection('pbx')->table('v_domains')->whereIn('domain_name', $domain_names)->get();

    $start_time = '2021-09-28 21:00:01';
    $to_time = date('Y-m-d H:i');

    foreach ($domains as $d) {
        $calls_made = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('account_id', $d->account_id)->whereBetween('hangup_time', [$start_time, $to_time])->where('duration', '>', 0)->count();
        if ($calls_made) {
            $cost = \DB::connection('pbx_cdr')->table('call_records_outbound')
                ->select(\DB::raw("SUM((rate/60) * 'duration') AS total)"))
                ->where('account_id', $d->account_id)
                ->whereBetween('hangup_time', [$start_time, $to_time])
                ->where('duration', '>', 0)->pluck('total')->first();
            if ($cost) {
                \DB::connection('pbx')->table('v_domains')->where('domain_name', $d->domain_name)->decrement('balance', $cost);
            }
        }
    }
}

function v_dialplans_copy()
{
    $dialplans = \DB::connection('pbx')->table('v_dialplans_copy')->where('dialplan_name', 'LIKE', '%Global%')->orwhere('dialplan_name', 'dummy')->get();
    foreach ($dialplans as $d) {
        $dialplan_details = \DB::connection('pbx')->table('v_dialplan_details_copy')->where('dialplan_uuid', $d->dialplan_uuid)->get();
        foreach ($dialplan_details as $dd) {
            $ddata = (array) $dd;
            \DB::connection('pbx')->table('v_dialplan_details')->insert($ddata);
        }

        $data = (array) $d;
        \DB::connection('pbx')->table('v_dialplans')->insert($data);
    }
}

function delete_invalid_dialplans()
{
    $domains = \DB::connection('pbx')->table('v_domains')->pluck('domain_uuid')->toArray();
    \DB::connection('pbx')->table('v_dialplans')->whereNotNull('domain_uuid')->whereNotIn('domain_uuid', $domains)->delete();
    \DB::connection('pbx')->table('v_dialplan_details')->whereNotNull('domain_uuid')->whereNotIn('domain_uuid', $domains)->delete();
    $dialplans = \DB::connection('pbx')->table('v_dialplans')->pluck('dialplan_uuid')->toArray();
    \DB::connection('pbx')->table('v_dialplan_details')->whereNotIn('dialplan_uuid', $dialplans)->delete();
}

function restore_pbx_dialplans()
{
    $dialplans = \DB::connection('pbx_backup')->table('v_dialplans')->get();
    $dialplan_details = \DB::connection('pbx_backup')->table('v_dialplan_details')->get();
    \DB::connection('pbx')->table('v_dialplans')->truncate();
    \DB::connection('pbx')->table('v_dialplan_details')->truncate();
    foreach ($dialplans as $d) {
        $d = (array) $d;
        \DB::connection('pbx')->table('v_dialplans')->insert($d);
    }
    foreach ($dialplan_details as $d) {
        $d = (array) $d;
        \DB::connection('pbx')->table('v_dialplan_details')->insert($d);
    }
}

function update_instance_currency_fields()
{
    /*
    $instance = \DB::table('erp_instances')->where('installed',1)->where('id',$id)->get()->first();
    $conn = 'default';
    $currency_decimals = $instance->currency_decimals;
    $tables = get_tables_from_schema($conn);
    foreach($tables as $table){
        $columns = get_columns_from_schema($table, ['float','decimal'], $conn);
        foreach($columns as $column){
           \DB::connection($conn)->statement('ALTER TABLE '.$table.' MODIFY COLUMN '.$column.' DOUBLE(10,'.$currency_decimals.')');
        }
    }
    */
}

function fix_stock()
{
    $products = \DB::table('crm_products')->get();
    foreach ($products as $product) {
        echo $product->code;
        $product_type = \DB::table('crm_products')->where('id', $product->id)->pluck('type')->first();
        $data = get_stock_balance($product->id);
        if ($product_type == 'Stock') {
            $product = \DB::table('crm_products')->where('id', $product->id)->update($data);
        }
    }
}

function tmpfolderwritable()
{
    $handle = fopen('/tmp/phpwritetotmp.log', 'x');
    if ($handle) {
    } else {
    }
}

function pbx_delete_number()
{
    exit;
    $number = '27137950075';
    \DB::connection('pbx')->table('v_phone_numbers')->where('number', $number)->where('status', 'Deleted')->update(['domain_uuid' => null, 'account_id' => 0]);
    \DB::connection('pbx')->table('v_phone_numbers')->where('number', $number)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'account_id' => 0, 'status' => 'Enabled']);
    pbxrun("delete from v_destinations where destination_number = '".$number."'");
    pbxrun("delete from v_destination_numbers where id = '".$number."'");
    $uuid = pbxrun("select dialplan_uuid from v_dialplan_details where dialplan_detail_data like '%".$number."%'");
    if (! empty($uuid)) {
        pbxrun("delete from v_dialplan_details where dialplan_uuid = '".$uuid[0]->dialplan_uuid."'");
    } else {
        $error .= 'Could not obtain uuid, uuid did not delete from v_dialplan_details'."\n";
    }
}

function sql_transactions()
{
    // only works for data queries not for schema changes
    $id = 1;
    $migration = \DB::table('erp_instance_migrations')->where('id', $id)->get()->first();
    $instance_connections = \DB::table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
    $instance_connections[] = 'default';
    $rollback_connections = [];
    foreach ($instance_connections as $conn) {
        DB::connection($conn)->beginTransaction();
        try {
            // process queries
            DB::connection($conn)->table('test')->insert(['test' => 2]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error_result = 'Connection: '.$conn.' '.$ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();

            $rollback_connections[] = $conn;
        }
    }

    if (count($rollback_connections) > 0) {
        foreach ($rollback_connections as $conn) {
            DB::connection($conn)->rollback();
            DB::table('erp_instance_migrations')->where('id', $id)->update(['error_result' => $error_result, 'processed' => 0]);
        }

        return false;
    }

    // If we reach here, then
    // data is valid and working.
    // Commit the queries!
    foreach ($instance_connections as $conn) {
        DB::connection($conn)->commit();
    }

    \DB::table('erp_instance_migrations')->where('id', $id)->update(['processed' => 1]);

    return true;
}

function copy_table($table, $table_new, $conn = 'default')
{
    DB::connection($conn)->statement('CREATE TABLE '.$table_new.' LIKE '.$table.'; ');
}

function set_foreign_keys()
{
    $tables = get_tables_from_schema();
    foreach ($tables as $t) {
        $columns = get_columns_from_schema($t);
        Schema::table($t, function (\Blueprint $table) {
            $table->foreign('holding_id')->references('id')->on('holdings');
        });
    }
}

function bank_fix_tax()
{
    /*
    $erp = new DBEvent;
    $erp->setTable('acc_register_bank');
    $ledger_accounts = \DB::connection('default')->table('acc_ledger_accounts')->where('taxable', 1)->pluck('id')->toArray();
    $ledger_accounts[] = 8;
    $ledger_accounts = collect($ledger_accounts)->unique()->toArray();
    $bank_transactions = \DB::connection('default')->table('acc_register_bank')->whereIn('ledger_account_id', $ledger_accounts)->where('docdate','>','2019-02-28')->get();
    \DB::connection('default')->table('acc_register_bank')->update(['tax' => 0]);
    foreach ($bank_transactions as $trx) {

        $tax = currency($trx->total - ($trx->total/1.15));
        if ($trx->ledger_account_id == 8) {
            $tax = $trx->total;
        }

        $erp->postDocument($trx->id);
        \DB::connection('default')->table('acc_register_bank')->where('id', $trx->id)->update(['tax' => $tax]);
    }
   $erp->postDocumentCommit();
   */
}

function decimal_columns()
{
    $tables = get_tables_from_schema();

    foreach ($tables as $table) {
        $float_columns = get_columns_from_schema($table, 'float');
        $double_columns = get_columns_from_schema($table, 'double');
        if (! empty($float_columns)) {
            foreach ($float_columns as $col) {
                \Schema::table($table, function ($schema) use ($col) {
                    $schema->decimal($col, 10, 2)->change();
                });
            }
        }
        if (! empty($double_columns)) {
            foreach ($double_columns as $col) {
                \Schema::table($table, function ($schema) use ($col) {
                    $schema->decimal($col, 10, 2)->change();
                });
            }
        }
        if (! empty($float_columns) || ! empty($double_columns)) {
        }
    }
}

function float_columns()
{
    $tables = get_tables_from_schema();

    foreach ($tables as $table) {
        $decimal_columns = get_columns_from_schema($table, 'double');

        if (! empty($decimal_columns)) {
            foreach ($decimal_columns as $col) {
                \Schema::table($table, function ($schema) use ($col) {
                    $schema->float($col)->change();
                });
            }
        }
    }
}

function ahmed()
{
    $accounts = DB::select('select * from crm_accounts where status <> "Deleted" and id <> 1 and partner_id = 1 order by company');
    foreach ($accounts as $account) {
        switch_account($account->id);
    }
}

function copy_erp_table($table, $source_db, $copy_db, $data = false)
{

    if ($data) {
        $cmd = 'mysqldump -h 156.0.96.71 -u remote -pWebmin@786 '.$source_db.' '.$table.' | mysql -u remote -pWebmin@786 '.$copy_db;
    } else {
        $cmd = 'mysqldump -h 156.0.96.71 -u remote -pWebmin@786 --no-data '.$source_db.' '.$table.' | mysql -u remote -pWebmin@786 '.$copy_db;
    }
    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    //dd($cmd,$result);
}

function drop_copied_tables()
{
    $conns = db_conns();
    foreach ($conns as $c) {
        $table_names = [];
        $tables = get_tables_from_schema();
        foreach ($tables as $table) {
            if (str_contains($table, '_copy')) {
                $table_names[] = $table;
            }
        }
        $copy_tables[$c] = $table_names;
    }
    foreach ($copy_tables as $connection => $tables) {
        foreach ($tables as $table) {
            Schema::connection($connection)->dropIfExists($table);
        }
    }
}

function json_db_fields_example()
{
    $table = 'crm_test';
    \Schema::table($table, function (Illuminate\Database\Schema\Blueprint $table) {

        $table->json('testjsonfield');

    });
    \DB::table('crm_test')->insert(['testjsonfield' => json_encode([1, 2, 3])]);
    \DB::table('crm_test')->insert(['testjsonfield' => json_encode([3, 5, 6])]);

    $r = \DB::table('crm_test')->whereJsonContains('testjsonfield', 3)->get();

}

function import_vehicledb_vehicles()
{

    /*
    ini_set('max_execution_time', '0');
       $originalCollection = file_to_array('/home/erpcloud-live/htdocs/html/Lightstone.xlsx');


       $transformedCollection = $originalCollection->map(function ($subCollection) {
           return collect($subCollection)->mapWithKeys(function ($value, $key) {
               $key = str_replace('/','',$key);
               $formattedKey = str_replace(' ', '_', strtolower(trim($key)));
               return [$formattedKey => $value];
           });
       });
       \DB::connection('default')->table('crm_vehicle_models')->truncate();
       try{
           $chunks = $transformedCollection->chunk(500);

           foreach ($chunks as $chunk){
               \DB::connection('default')->table('crm_vehicle_models')->insert($chunk->toArray());
           }
       }catch(\Throwable $ex){
       }


   */
}

function populate_mon_blacklist()
{
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->pluck('domain_name')->toArray();

    foreach ($volume_domains as $volume_domain) {
        $sql = "INSERT INTO mon_blacklist (duration, gateway, domain_name, hangup_time, hangup_cause, caller_id_number, callee_id_number, destination, ani, ani_source, callee_source)
        SELECT 
        duration,
        gateway,
        domain_name,
        hangup_time,
        hangup_cause,
        caller_id_number,
        callee_id_number,
        destination,
        ani,
        ani_source,
        callee_source
        FROM call_records_outbound_lastmonth
        WHERE domain_name ='".$volume_domain."' and (duration=6 or duration=7 or duration=8)";

        \DB::connection('pbx_cdr')->statement($sql);
    }

}

function pbxerp_dbtables()
{
    //  $base_modules = \DB::table('erp_cruds')->where('app_id',18)->get();
    // $source_db = 'telecloud';
    // $copy_db = 'erp';
    // foreach($base_modules as $base_module){
    //     $table = $base_module->db_table;
    //     $cmd = 'mysqldump -h 156.0.96.71 -u telecloud -pB3roPJdl1DoxgHpKdeN3 --no-data '.$source_db.' '.$table.' | mysql -u remote -pWebmin@786 '.$copy_db;
    //     //dd($cmd);
    //     $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    // }
    // $tables = get_tables_from_schema('pbx_erp');

    // $modules = \DB::table('erp_cruds')->whereIn('db_table',$tables)->orWhereIn('connection',['pbx','pbx_cdr'])->get();
    // $module_ids = $modules->pluck('id')->toArray();
    // /*foreach($modules as $m){
    //     $data = (array) $m;
    //     \DB::connection('pbx_erp')->table('erp_cruds')->insert($data);
    // }
    // */
    // $conf_tables = ['erp_module_fields','erp_forms','erp_form_events','erp_grid_views','erp_grid_styles','erp_menu'];
    // foreach($conf_tables as $c){
    //     if($c == 'erp_menu'){
    //     $rows = \DB::table($c)->whereIn('module_id',$module_ids)->orWhereIn('render_module_id',$module_ids)->get();
    //     }else{
    //     $rows = \DB::table($c)->whereIn('module_id',$module_ids)->get();
    //     }
    //     foreach($rows as $r){
    //         $data = (array) $r;
    //         \DB::connection('pbx_erp')->table($c)->insert($data);
    //     }
    // }
}

function temp_iptv_billing()
{
    $subs = \DB::table('sub_services')->whereIn('product_id', [810, 811])->where('status', '!=', 'Deleted')->get();
    foreach ($subs as $s) {
        \DB::table('sub_services')->where('detail', $s->detail)->where('product_id', 808)->delete();
    }

    $db = new DBEvent;
    $db->setTable('crm_documents');
    $quotes = \DB::table('crm_documents')->where('reference', 'Billing: Mar 2024 - Jun 2024')->get();
    foreach ($quotes as $quote) {

        //update pricing
        $lines = \DB::table('crm_document_lines')->where('document_id', $quote->id)->get();
        foreach ($lines as $line) {
            if (! empty($line->subscription_id)) {
                $e = \DB::table('sub_services')->where('id', $line->subscription_id)->count();
                if (! $e) {
                    \DB::table('crm_document_lines')->where('id', $line->id)->delete();
                }
            }
        }
        $lines = \DB::table('crm_document_lines')->where('document_id', $quote->id)->get();
        $subtotal = 0;
        foreach ($lines as $l) {
            \DB::table('crm_document_lines')->where('id', $l->id)->update(['qty' => 1]);
            $l->qty = 1;
            $subtotal += $l->full_price * $l->qty;
            \DB::table('crm_document_lines')->where('id', $l->id)->update(['price' => $l->full_price, 'full_price' => $l->full_price]);
        }

        $admin = dbgetaccount(1);
        $total = $subtotal;
        $tax = 0;
        if ($admin->vat_enabled) {
            $total = $subtotal * 1.15;
            $tax = $total - $subtotal;
        }
        \DB::table('crm_documents')->where('id', $quote->id)->update(['tax' => $tax, 'total' => $total]);
        $db->postDocument($quote->id);
        //send email
        //if(date('Y-m-d',strtotime($quote->docdate)) < date('Y-m-01')){
        // email_document_pdf($quote->id);
        // }
    }
    $db->postDocumentCommit();
    //return 'Quotes sent';
    set_document_lines_gp('', '2024-03-28');
}

function tc_mobile_migrate()
{

    $accounts = \DB::table('crm_accounts')->where('id', 304103)->where('status', '!=', 'Deleted')->get();
    foreach ($accounts as $account) {
        $has_geo = \DB::table('sub_services')->where('product_id', 128)->where('account_id', $account->id)->where('status', '!=', 'Deleted')->count();
        if ($has_geo) {
            $numbers087 = \DB::table('sub_services')->where('product_id', 127)->where('account_id', $account->id)->where('status', '!=', 'Deleted')->pluck('detail')->toArray();
            foreach ($numbers087 as $n) {

                pbxnumbers_unallocate($n);
            }
        }
        \DB::table('sub_services')->where('product_id', 130)->where('account_id', $account->id)->where('status', '!=', 'Deleted')->update(['product_id' => 674]);
    }
}

/*
 $subs = \DB::table('sub_services')->whereIn('product_id',[674,127])->where('created_at','like','2024-04-25%')->get();
    $doc_ids = \DB::table('crm_documents')->where('reference','Billing: May 2024')->pluck('id')->toArray();
    $sub_doc_ids = \DB::table('crm_document_lines')->whereIn('subscription_id',$subs->pluck('id')->toArray())->whereIn('document_id',$doc_ids)->pluck('document_id')->unique()->toArray();

    foreach($subs as $s){
        $active_count = \DB::table('sub_services_lastmonth')->where('account_id',$s->account_id)->where('pbx_domain',$s->pbx_domain)->where('status','!=','Deleted')->count();
        if(!$active_count){
            \DB::table('crm_document_lines')->where()

        }
    }
*/
