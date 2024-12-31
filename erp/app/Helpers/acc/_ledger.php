<?php
/*
find faulty transactions
select docid, doctype, sum(amount) as total from acc_ledgers where doctype!='General Journal' group by doctype,docid having total>0.1 or total < -0.1
*/
/*
SELECT account_id, docid, doctype, sum(amount) as total ,
  YEAR(docdate) AS year_part,
    MONTH(docdate) AS month_part
FROM acc_ledgers
WHERE
ledger_account_id = 5

GROUP BY
    YEAR(docdate),
    MONTH(docdate)
ORDER BY
    year_part, month_part;
*/

function validate_customer_control()
{

    //repost_document_by_id('acc_cashbook_transactions',3);

    $db = new DBEvent;
    $doctypes = \DB::table('acc_doctypes')->get();
    $accounts = \DB::table('crm_accounts')->select('id', 'balance', 'currency')->where('partner_id', 1)->where('type', '!=', 'lead')->get();

    $ledgers = \DB::table('acc_ledgers')->where('doctype', '!=', 'General Journal')->where('ledger_account_id', 5)->groupBy('doctype', 'docid')->get();
    foreach ($ledgers as $l) {
        $doctable = $doctypes->where('doctype', $l->doctype)->pluck('doctable')->first();

        $total = \DB::table($doctable)->select('total')->where('id', $l->docid)->pluck('total')->first();
        if (abs(currency($total)) != abs(currency($l->original_amount))) {
        }
    }

    foreach ($accounts as $a) {

        $ledger_balance = \DB::table('acc_ledgers')->where('account_id', $a->id)->where('ledger_account_id', 5)->sum('original_amount');
        $diff = currency($ledger_balance) - currency($a->balance);
        if (abs($diff) > 1) {
            $trxs = get_debtor_transactions($a->id);

            foreach ($trxs as $trx) {
                $ledger_amount = \DB::table('acc_ledgers')->where('doctype', $trx->doctype)->where('docid', $trx->id)->where('account_id', $trx->account_id)->pluck('original_amount')->first();
                if (currency(abs($trx->total)) != currency(abs($ledger_amount))) {

                    // $doctable = $doctypes->where('doctype',$trx->doctype)->pluck('doctable')->first();
                    // $db->setTable($doctable)->postDocument($trx->id);

                }
            }
            $b = get_debtor_balance($a->id);
            \DB::table('crm_accounts')->where('id', $a->id)->update(['balance' => $b]);
            // $db->postDocumentCommit();
        }
    }
}

function schedule_validate_ledger()
{
    return false;
    $db = new DBEvent;

    $doctypes = \DB::table('acc_doctypes')->get();
    $rows = \DB::select('select docid, doctype, sum(amount) as total from acc_ledgers group by doctype,docid having total>0.1 or total < -0.1 ');

    foreach ($sql as $s) {

        $doctable = $doctypes->where('doctype', $s->doctype)->pluck('doctable')->first();
        $db->setTable($doctable)->postDocument($s->docid);
    }

    $db->postDocumentCommit();
}

function debug_ledger()
{

    //  debug profitability - financials/income stament match
    $s = "SELECT 
    crm_documents.docdate,
    crm_documents.doctype,
    crm_document_lines.product_id,
    crm_document_lines.document_id,
    crm_document_lines.zar_sale_total
    FROM crm_document_lines
    LEFT JOIN `crm_documents` on `crm_document_lines`.`document_id` = `crm_documents`.`id`
    WHERE crm_documents.docdate LIKE '2023-10%' and crm_documents.doctype in ('Tax Invoice','Credit Note')";
    $rows = \DB::connection('default')->select($s);
    foreach ($rows as $r) {
        if ($r->zar_sale_total == 0) {

            $c = \DB::connection('default')->table('acc_ledgers')
                ->where('docid', $r->document_id)
                ->where('docdate', $r->docdate)
                ->where('doctype', $r->doctype)
                ->where('docdate', $r->docdate)
                ->where('product_id', $r->product_id)
                ->count();

            if ($c) {

            }

            continue;
        } else {
            $c = \DB::connection('default')->table('acc_ledgers')
                ->where('docid', $r->document_id)
                ->where('docdate', $r->docdate)
                ->where('doctype', $r->doctype)
                ->where('docdate', $r->docdate)
                ->where('product_id', $r->product_id)
                ->where('amount', $r->zar_sale_total * -1)
                ->count();

            if (! $c) {

            }
        }
    }

    /*
    1. check cashbook has correct doctypes for reallocated transactions
    2. find documents that arent balancing on the ledger
    3. check ledger totals for each period
    */

    $trxs1 = \DB::table('acc_cashbook_transactions')->where('doctype', 'Cashbook Supplier Payment')->whereNull('supplier_id')->get();

    $trxs2 = \DB::table('acc_cashbook_transactions')->where('doctype', 'Cashbook Expense')->whereNull('ledger_account_id')->get();

    $trxs3 = \DB::table('acc_cashbook_transactions')->where('doctype', 'Cashbook Customer Receipt')->whereNull('account_id')->get();

    // income statement balance sql
    $doctypes = \DB::table('acc_doctypes')->get();
    $sql = "SELECT docid,doctype FROM `acc_ledgers` where doctype!='General Journal' group by doctype,docid having sum(amount) > 1";
    $docs = \DB::select($sql);

    foreach ($docs as $doc) {
        $doctable = $doctypes->where('doctype', $doc->doctype)->pluck('doctable')->first();

        repost_document_by_id($doctable, $doc->docid);
    }
    $doctypes = \DB::table('acc_doctypes')->get();
    $sql = "SELECT docid,doctype FROM `acc_ledgers` where doctype!='General Journal' group by doctype,docid having sum(amount) < -1";
    $docs = \DB::select($sql);

    foreach ($docs as $doc) {
        $doctable = $doctypes->where('doctype', $doc->doctype)->pluck('doctable')->first();

        repost_document_by_id($doctable, $doc->docid);
    }
    $sql = "SELECT docid,doctype FROM `acc_ledgers`  where doctype!='General Journal'  group by doctype,docid having sum(amount) > 1";
    $docs = \DB::select($sql);
    foreach ($docs as $d) {
        if (in_array($d->doctype, ['Tax Invoice', 'Credit Note'])) {
            repost_document_by_id('crm_documents', $d->docid);
        }
    }

    // check_trail_balance_all_periods
    $periods = \DB::table('acc_ledger_totals')->pluck('period')->unique()->toArray();
    $faulty_periods = [];
    foreach ($periods as $period) {
        $period_total = \DB::table('acc_ledger_totals')->where('period', $period)->sum('total');
        if ($period_total > 5 || $period_total < -5) {
            $faulty_periods[] = $period;
        }
    }
    $trail_balance_msg = '';
    foreach ($faulty_periods as $period) {
        $ledger = \DB::table('acc_ledgers')->where('docdate', 'like', $period.'%')->select('doctype', \DB::raw('sum(amount) as total'))->groupBy('doctype')->get();

        foreach ($ledger as $balance) {
            if (abs($balance->total) > 5) {
                $trail_balance_msg .= $period.' '.$balance->doctype.' is not balancing. '.$balance->total.'<br>';
            }
        }
    }

}

function set_doctype_doc_no($doctype)
{

    $doctype_row = \DB::table('acc_doctypes')->where('doctype', $doctype)->get()->first();
    $cols = get_columns_from_schema($doctype_row->doctable);

    if (in_array('doc_no', $cols)) {

        $sql = 'SET @row_number = 10000';

        \DB::statement($sql);

        $sql = 'UPDATE '.$doctype_row->doctable." SET doc_no = @row_number:=@row_number+1 WHERE doctype='".$doctype."'";

        \DB::statement($sql);

        $sql = 'UPDATE acc_ledgers SET doc_no = (SELECT doc_no FROM '.$doctype_row->doctable." WHERE id=acc_ledgers.docid) WHERE doctype='".$doctype."'";
        \DB::statement($sql);

        $next_sequence_number = \DB::table($doctype_row->doctable)->max('doc_no');
        \DB::table('acc_doctypes')->where('doctype', $doctype)->update(['sequence_number' => $next_sequence_number]);
    }

}

function schedule_check_trial_balance()
{
    if (! is_main_instance()) {
        return false;
    }

    \DB::table('notifications')->where('data', 'LIKE', '%Trial balance is not balancing%')->delete();
    $db_conns = ['telecloud', 'eldooffice', 'moviemagic'];
    foreach ($db_conns as $c) {

        $ledger_total = \DB::connection($c)->table('acc_ledger_totals')->sum('total');
        $ledger = \DB::connection($c)->table('acc_ledgers')->select('doctype', \DB::raw('sum(amount) as total'))->groupBy('doctype')->having('total', '>', 1)->get();
        $trail_balance_msg = '';

        if (abs($ledger_total) > 50) {
            $trail_balance_msg .= 'Ledger total is not balancing. Trial Balance: '.$ledger_total.'<br><br>';
        }

        foreach ($ledger as $balance) {

            if (abs($balance->total) > 2) {
                $trail_balance_msg .= $balance->doctype.' is not balancing. '.$balance->total.'<br>';
            }
        }
        if ($trail_balance_msg > '') {
            $title = 'Trial balance is not balancing';

            if ($c == 'telecloud') {
                $title = 'Cloud Telecoms '.$title;
            }
            if ($c == 'moviemagic') {
                $title = 'Movie Magic '.$title;
            }
            if ($c == 'eldooffice') {
                $title = 'Eldo Office '.$title;
            }

            $data['subject'] = $title;
            $data['internal_function'] = 'debug_email';
            $data['exception_email'] = true;
            $data['to_email'] = 'ahmed@telecloud.co.za';
            //$data['cc_email'] = 'ahmed@telecloud.co.za';
            $data['form_submit'] = 1;
            //$data['test_debug'] = 1;
            $data['var'] = nl2br($trail_balance_msg);
            erp_process_notification(1, $data, $function_variables);

        }
    }
}

function button_process_annual_retained_earnings($request)
{
    $years = \DB::table('acc_accounting_periods')->where('locked', 0)->pluck('accounting_period')->toArray();
    foreach ($years as $year) {
        $transaction_ids = DB::table('acc_general_journal_transactions')->where('docdate', 'LIKE', $year.'%')->pluck('id')->toArray();
        \DB::table('acc_general_journals')->whereIn('transaction_id', $transaction_ids)->where('reference', 'LIKE', 'Annual Closing Balance%')->delete();
        \DB::table('acc_general_journals')->whereIn('transaction_id', $transaction_ids)->where('reference', 'LIKE', 'Annual Opening Balance%')->delete();
        \DB::table('acc_general_journals')->whereIn('transaction_id', $transaction_ids)->where('reference', 'LIKE', 'Annual Retained Earnings%')->delete();
    }
    $trxs = \DB::table('acc_general_journal_transactions')->get();
    foreach ($trxs as $trx) {
        $c = \DB::table('acc_general_journals')->where('transaction_id', $trx->id)->count();
        if (! $c) {
            DB::table('acc_general_journal_transactions')->where('id', $trx->id)->delete();
        }
    }
    rebuild_ledger_doctype('General Journal');
    open_periods();
    $years = [];
    $year = '2017-02-28';
    while (date('Y', strtotime($year)) != date('Y', strtotime('+1 year'))) {
        if (date('L', strtotime($year))) {
            $year = date('Y-02-29', strtotime($year));
        }
        if (accounting_year_active($year)) {
            $years[] = $year;
        }
        $year = date('Y-02-28', strtotime($year.' +1 year'));
    }
    $db = new DBEvent;
    $ledger_accounts = \DB::table('acc_ledger_accounts')->where('ledger_account_category_id', '<', 30)->where('id', '!=', 11)->where('id', '!=', 100)->get();

    foreach ($years as $i => $year) {
        $docids = [];

        foreach ($ledger_accounts as $ledger_account) {
            $ledger_total = \DB::table('acc_ledgers')->where('docdate', '<=', $year)->where('ledger_account_id', $ledger_account->id)->sum('amount');

            if (! empty($ledger_total) && $ledger_total != 0) {
                $trx_data = [
                    'docdate' => $year,
                    'doctype' => 'General Journal',
                    'name' => 'Annual Retained Earnings: '.$year.' '.$ledger_account->name,
                ];

                $transaction_id = \DB::table('acc_general_journal_transactions')->insertGetId($trx_data);

                $data = [
                    'transaction_id' => $transaction_id,
                    'debit_amount' => abs($ledger_total),
                    'reference' => 'Annual Retained Earnings: '.$year.' '.$ledger_account->name,
                ];

                if ($ledger_total > 0) {
                    $data['ledger_account_id'] = 11;
                    $id = \DB::table('acc_general_journals')->insertGetId($data);
                    $db->setTable('acc_general_journals')->postDocument($id);

                    $data['credit_amount'] = $data['debit_amount'];
                    $data['debit_amount'] = 0;
                    $data['ledger_account_id'] = $ledger_account->id;
                    $id = \DB::table('acc_general_journals')->insertGetId($data);
                    $db->setTable('acc_general_journals')->postDocument($id);
                } else {
                    $data['ledger_account_id'] = $ledger_account->id;
                    $id = \DB::table('acc_general_journals')->insertGetId($data);
                    $db->setTable('acc_general_journals')->postDocument($id);

                    $data['credit_amount'] = $data['debit_amount'];
                    $data['debit_amount'] = 0;
                    $data['ledger_account_id'] = 11;
                    $id = \DB::table('acc_general_journals')->insertGetId($data);
                    $db->setTable('acc_general_journals')->postDocument($id);
                }

                $db->postDocumentCommit();
            }
        }
    }

    archive_ledger();
    schedule_period_check();
    rebuild_ledger_doctype('General Journal');

    return json_alert('Done');
}

function archive_ledger()
{
    $docdate = date('Y-02-28');
    if (date('L', strtotime($docdate))) {
        $docdate = date('Y-02-29', strtotime($docdate));
    }

    $years = [];
    $year = '2017-02-28';
    while (date('Y', strtotime($year)) != date('Y', strtotime('+1 year'))) {
        if (date('L', strtotime($year))) {
            $year = date('Y-02-29', strtotime($year));
        }
        if (accounting_year_active($year)) {
            $years[] = $year;
        }
        $year = date('Y-02-28', strtotime($year.' +1 year'));
    }

    foreach ($years as $i => $year) {
        $archive_table = 'acc_ledger_'.date('Y', strtotime($year));
        $opening_date = date('Y-03-01', strtotime($year.' -1 year'));
        $closing_date = date('Y-03-01', strtotime($year));

        \DB::statement('DROP TABLE IF EXISTS '.$archive_table);
        \DB::statement('CREATE TABLE '.$archive_table.' LIKE acc_ledgers');
        if ($i == 0) {
            \DB::statement('INSERT '.$archive_table.' SELECT * FROM acc_ledgers where docdate<"'.$closing_date.'"');
        } else {
            \DB::statement('INSERT '.$archive_table.' SELECT * FROM acc_ledgers where docdate>="'.$opening_date.'" and docdate<"'.$closing_date.'"');
        }
    }
}

function rebuild_ledger_doctype($doctype)
{
    $db = new DBEvent;
    \DB::table('acc_ledgers')->where('doctype', $doctype)->delete();
    $doctype = \DB::table('acc_doctypes')->where('doctype', $doctype)->get()->first();

    $docids = \DB::table($doctype->doctable)->where('doctype', $doctype->doctype)->pluck('id')->toArray();
    $db->setTable($doctype->doctable);
    foreach ($docids as $docid) {
        $db->postDocument($docid);
    }
    $db->postDocumentCommit();
}

function aftersave_doctypes_update_tables($request)
{
    if (! empty(session('event_db_record'))) {
        $beforesave_row = session('event_db_record');
        if ($beforesave_row->doctype != $request->doctype) {
            \DB::table($request->doctable)->where('doctype', $beforesave_row->doctype)->update(['doctype' => $request->doctype]);
        }
    }
}

function generate_doctype_details($connnection = 'default')
{
    $doctypes = \DB::connection($connnection)->table('acc_doctypes')->get();
    foreach ($doctypes as $doctype) {
        $i = 1;
        while ($i < 6) {
            $field = 'credit_account_'.$i;
            $value_field = 'value_'.$i;
            if (! empty($doctype->{$field})) {
                if (empty($doctype->{$value_field})) {
                    $document_field = '';
                } else {
                    $document_field = $doctype->{$value_field};
                }
                $data = [
                    'doctype_id' => $doctype->id,
                    'type' => 'Credit',
                    'document_value' => $document_field,
                    'ledger_account_id' => $doctype->{$field},
                ];
                \DB::connection($connnection)->table('acc_doctype_details')->insert($data);
            }

            $field = 'debit_account_'.$i;
            $value_field = 'value_'.$i;
            if (! empty($doctype->{$field})) {
                if (empty($doctype->{$value_field})) {
                    $document_field = '';
                } else {
                    $document_field = $doctype->{$value_field};
                }
                $data = [
                    'doctype_id' => $doctype->id,
                    'type' => 'Debit',
                    'document_value' => $document_field,
                    'ledger_account_id' => $doctype->{$field},
                ];
                \DB::connection($connnection)->table('acc_doctype_details')->insert($data);
            }
            $i++;
        }
    }
}

function button_rebuild_closing_balances($request = false)
{
    try {
        $years = \DB::table('acc_accounting_periods')->where('locked', 0)->pluck('accounting_period')->toArray();
        foreach ($years as $year) {
            \DB::table('acc_closing_balances')->where('docdate', 'LIKE', $year.'%')->delete();
        }
        $docdates = [];
        $year = '2017-02-28';
        while (date('Y', strtotime($year)) != date('Y', strtotime('+1 year'))) {
            if (date('L', strtotime($year))) {
                $year = date('Y-02-29', strtotime($year));
            }
            if (accounting_year_active($year)) {
                $docdates[] = $year;
            }

            $year = date('Y-02-28', strtotime($year.' +1 year'));
        }

        $ledger_account_ids = \DB::table('acc_ledger_accounts')->whereIn('ledger_account_category_id', [31, 40])->pluck('id')->toArray();

        //  $ledger_account_ids = [56,44,55];
        foreach ($ledger_account_ids as $ledger_account_id) {

            //    \DB::table('acc_closing_balances')->where('ledger_account_id',$ledger_account_id)->delete();
            foreach ($docdates as $docdate) {

                $cashbook_transaction_ids = \DB::table('acc_general_journals')
                    ->where('ledger_account_id', 57)
                    ->pluck('id')->toArray();
                $cashbook_transaction_ledger_ids = \DB::table('acc_ledgers')
                    ->where('doctype', 'General Journal')
                    ->whereIn('docid', $cashbook_transaction_ids)
                    ->pluck('id')->toArray();
                $ledger_balance = \DB::table('acc_ledgers')
                    ->selectRaw(\DB::raw('sum(amount) as total'))
                    ->where('docdate', '<=', $docdate)
                    ->where('ledger_account_id', $ledger_account_id)
                    ->whereNotIn('id', $cashbook_transaction_ledger_ids)
                    ->pluck('total')->first();
                $recon_balance = 0;
                if ($ledger_account_id == 5) {
                    $sql = 'select crm_documents.id, crm_documents.docdate, crm_documents.doctype, sum(crm_documents.total) as total from
                (select  acc_cashbook_transactions.id, docdate, "Cashbook Customer Receipt" as doctype, total *-1 as total from acc_cashbook_transactions
                where doctype != "Partner User Payment" and api_status!="Invalid" and account_id > 0
                UNION ALL
                select doctable.id, docdate, doctype, total *-1 as total from crm_documents as doctable
                where doctype = "Credit Note" 
                UNION ALL
                select crm_documents.id, docdate, doctype, total as total  from crm_documents 
                where  (doctype = "Tax Invoice")
                UNION ALL 
                select aj.id, ajt.docdate, ajt.doctype, debit_amount as total from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=5 and account_id > 0 and debit_amount > 0
                UNION ALL 
                select aj.id, ajt.docdate, ajt.doctype, credit_amount*-1 as total from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=5 and account_id > 0 and credit_amount > 0
                ) crm_documents ';

                    $recon_balance_sql = \DB::select($sql.' where docdate <="'.$docdate.'"');
                    //print_r($sql);
                    if ($recon_balance_sql && $recon_balance_sql[0] && $recon_balance_sql[0]->total) {
                        $recon_balance = $recon_balance_sql[0]->total;
                    }

                    //  $recon_balance = \DB::table('crm_accounts')
                }
                if ($ledger_account_id == 6) {
                    $sql = 'select id, docdate, doctype, sum(total) as total   from
                (select id, docdate, "Cashbook Supplier Payment" as doctype, total*-1 as total from acc_cashbook_transactions where supplier_id > 0 
                UNION ALL
                select doctable.id, docdate, doctype, total as total from crm_supplier_documents as doctable
                where  doctype = "Supplier Debit Note"
                UNION ALL
                select id, docdate, doctype, total*-1 as total from crm_supplier_documents 
                where  doctype = "Supplier Invoice"
                UNION ALL 
                select aj.id, ajt.docdate, ajt.doctype, debit_amount as total from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=6 and supplier_id > 0 and debit_amount > 0
                UNION ALL 
                select aj.id, ajt.docdate, ajt.doctype, credit_amount*-1 as total from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=6 and supplier_id > 0 and credit_amount > 0
                ) crm_documents  ';
                    $recon_balance_sql = \DB::select($sql.' where docdate <="'.$docdate.'"');
                    if ($recon_balance_sql && $recon_balance_sql[0] && $recon_balance_sql[0]->total) {
                        $recon_balance = $recon_balance_sql[0]->total;
                    }
                }

                if ($ledger_account_id == 4) {
                    $recon_balance = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 9)->where('docdate', '<=', $docdate)->orderBy('docdate', 'desc')->orderBy('id', 'desc')->pluck('balance')->first();

                    if (empty($recon_balance)) {
                        $transaction_ids = DB::table('acc_general_journal_transactions')->where('docdate', '<=', $docdate)->pluck('id')->toArray();
                        $debit_balance = \DB::table('acc_general_journals')->whereIn('transaction_id', $transaction_ids)->where('ledger_account_id', 4)->sum('debit_amount');
                        $credit_balance = \DB::table('acc_general_journals')->whereIn('transaction_id', $transaction_ids)->where('ledger_account_id', 4)->sum('credit_amount');
                        $recon_balance = $debit_balance - $credit_balance;
                    }
                }

                if ($ledger_account_id == 2) {
                    $recon_balance = \DB::table('acc_cashbook_transactions')->where('cashbook_id', 8)->where('docdate', '<=', $docdate)->orderBy('docdate', 'desc')->orderBy('id', 'desc')->pluck('balance')->first();

                    if (empty($recon_balance)) {
                        $transaction_ids = DB::table('acc_general_journal_transactions')->where('docdate', '<=', $docdate)->pluck('id')->toArray();
                        $debit_balance = \DB::table('acc_general_journals')->whereIn('transaction_id', $transaction_ids)->where('ledger_account_id', 2)->sum('debit_amount');
                        $credit_balance = \DB::table('acc_general_journals')->whereIn('transaction_id', $transaction_ids)->where('ledger_account_id', 2)->sum('credit_amount');
                        $recon_balance = $debit_balance - $credit_balance;
                    }
                }

                if ($ledger_account_id == 32) {
                    $stock_product_ids = \DB::table('crm_products')->where('type', 'Stock')->pluck('id')->toArray();
                    if (count($stock_product_ids) > 0) {
                        $sql = 'select stock.id, stock.docdate, stock.product_id, sum(stock.stock_value) as total from
                ((select cd.id, cd.docdate, cdl.product_id, (cost_price*qty)*-1 as stock_value from crm_documents cd join crm_document_lines cdl on cd.id = cdl.document_id 
                where cd.doctype = "Tax Invoice")
                UNION ALL 
                (select cd.id, cd.docdate, cdl.product_id, (cost_price*qty) as stock_value from crm_documents cd join crm_document_lines cdl on cd.id = cdl.document_id 
                where cd.doctype = "Credit Note" )
                UNION ALL 
                (select cd.id, docdate, cdl.product_id, (price*qty) as stock_value from crm_supplier_documents cd join crm_supplier_document_lines cdl on cd.id = cdl.document_id 
                where cd.doctype = "Supplier Invoice")
                UNION ALL 
                (select cd.id, docdate, cdl.product_id, (price*qty)*-1 as stock_value from crm_supplier_documents cd join crm_supplier_document_lines cdl on cd.id = cdl.document_id 
                where cd.doctype = "Supplier Debit Note" )
                UNION ALL 
                (select aj.id, ajt.docdate,'.$stock_product_ids[0].' as product_id, debit_amount as stock_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=32 and debit_amount > 0)
                UNION ALL 
                (select aj.id, ajt.docdate,'.$stock_product_ids[0].' as product_id, credit_amount*-1 as stock_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=32 and credit_amount > 0)
                UNION ALL 
                (select id, docdate, product_id, total as stock_value from acc_inventory ))
                as stock join crm_products on crm_products.id=stock.product_id ';
                        $sql = $sql.' where crm_products.type="Stock" and docdate <="'.$docdate.'"';

                        $recon_balance_sql = \DB::select($sql);

                        if ($recon_balance_sql && $recon_balance_sql[0] && $recon_balance_sql[0]->total) {
                            $recon_balance = $recon_balance_sql[0]->total;
                        }
                    }
                }
                if ($ledger_account_id == 44) {
                    $sql = 'select tax.id, tax.doctype, tax.docdate, sum(tax.tax_value) as total from
                ((select id, doctype, docdate, total*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=44)
                UNION ALL 
                (select id, doctype, docdate, total*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=44)
                UNION ALL 
                (select aj.id, doctype, docdate, debit_amount as tax_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=44 and debit_amount > 0)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, credit_amount*-1 as tax_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=44 and credit_amount > 0)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, paye*-1 as tax_value from hr_payroll where status="Complete")
                )as tax ';
                    $recon_balance_sql = \DB::select($sql.' where docdate <="'.$docdate.'"');
                    if ($recon_balance_sql && $recon_balance_sql[0] && $recon_balance_sql[0]->total) {
                        $recon_balance = $recon_balance_sql[0]->total;
                    }
                }
                if ($ledger_account_id == 55) {
                    $sql = 'select tax.id, tax.doctype, tax.docdate, sum(tax.tax_value) as total from
                ((select id, doctype, docdate, total*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=55)
                UNION ALL 
                (select id, doctype, docdate, total*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=55)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, debit_amount as tax_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=55 and debit_amount > 0)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, credit_amount*-1 as tax_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=55 and credit_amount > 0)
                UNION ALL 
                (select id, doctype, docdate, (uif_employee+uif_company)*-1 as tax_value from hr_payroll where status="Complete")
                )as tax ';
                    $recon_balance_sql = \DB::select($sql.' where docdate <="'.$docdate.'"');
                    if ($recon_balance_sql && $recon_balance_sql[0] && $recon_balance_sql[0]->total) {
                        $recon_balance = $recon_balance_sql[0]->total;
                    }
                }
                if ($ledger_account_id == 8) {
                    $sql = 'select tax.id, tax.doctype, tax.docdate, sum(tax.tax_value) as total from
                (
                (select id, doctype, docdate, tax*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id!=8)
                UNION ALL 
                (select id, doctype, docdate, total*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=8)
                UNION ALL 
                (select id, doctype, docdate, tax*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id!=8)
                UNION ALL 
                (select id, doctype, docdate, total*-1 as tax_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=8)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, debit_amount as tax_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=8 and debit_amount > 0)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, credit_amount*-1 as tax_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=8 and credit_amount > 0)
                UNION ALL 
                (select id, doctype, docdate, tax*-1 as tax_value from crm_documents where doctype="Tax Invoice")
                UNION ALL 
                (select id, doctype, docdate, tax as tax_value from crm_documents where doctype="Credit Note")
                UNION ALL 
                (select id, doctype, docdate, tax as tax_value from crm_supplier_documents where doctype="Supplier Invoice")
                UNION ALL 
                (select id, doctype, docdate, tax*-1 as tax_value from crm_supplier_documents where doctype="Supplier Debit Note")
                )as tax ';
                    $recon_balance_sql = \DB::select($sql.' where docdate <="'.$docdate.'"');
                    if ($recon_balance_sql && $recon_balance_sql[0] && $recon_balance_sql[0]->total) {
                        $recon_balance = $recon_balance_sql[0]->total;
                    }
                }

                if ($ledger_account_id == 56) {
                    $sql = 'select total.id, total.doctype, total.docdate, sum(total.total_value) as total from
                (
                (select id, doctype, docdate, total*-1 as total_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=56)
                UNION ALL 
                (select id, doctype, docdate, total*-1 as total_value from acc_cashbook_transactions where doctype = "Cashbook Expense" and ledger_account_id=56)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, debit_amount as total_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=56 and debit_amount > 0)
                UNION ALL 
                (select aj.id, ajt.doctype, ajt.docdate, credit_amount*-1 as total_value from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id where ledger_account_id=56 and credit_amount > 0)
                UNION ALL 
                (select id, doctype, docdate, gross_salary as total_value from hr_payroll where status="Complete")
                )as total ';
                    $recon_balance_sql = \DB::select($sql.' where docdate <="'.$docdate.'"');
                    if ($recon_balance_sql && $recon_balance_sql[0] && $recon_balance_sql[0]->total) {
                        $recon_balance = $recon_balance_sql[0]->total;
                    }
                }

                if (empty($recon_balance)) {
                    $recon_balance = 0;
                }
                if (empty($ledger_balance)) {
                    $ledger_balance = 0;
                }
                $data = [
                    'docdate' => $docdate,
                    'ledger_account_id' => $ledger_account_id,
                    'ledger_balance' => $ledger_balance,
                    'recon_balance' => $recon_balance,
                ];

                \DB::table('acc_closing_balances')->insert($data);
            }
        }

        return json_alert('Done');
    } catch (\Throwable $ex) {
        exception_log($ex);
        $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
        trace($ex);

        return response()->json(['status' => 'error', 'message' => $error]);
    }
}

function ledger_post_amount($doctype, $doctype_detail, $obj, $document = false)
{

    $math_str = $doctype_detail->document_value;
    $type = $doctype_detail->type;

    if ($math_str == 'cost_total') {
        if (isset($obj->cost_price) && $doctype->doctable == 'crm_documents') {
            $amount = currency($obj->qty * $obj->cost_price, 2);
        } elseif ($doctype->doctable == 'crm_supplier_documents') {
            $amount = currency($obj->qty * $obj->price, 2);
        }
        if (isset($obj->price) && $doctype->doctable == 'crm_documents') {
            if ($document && $document->bill_frequency > 1) {
                $amount = currency($document->bill_frequency * $amount);
            }
        }
        if ($doctype_detail->type == 'Credit' && $amount != 0) {
            $amount = $amount * -1;
        }

        return $amount;
    }

    if ($math_str == 'price') {
        if (isset($obj->price) && $doctype->doctable == 'crm_documents') {
            if ($document && $document->bill_frequency > 1) {
                $amount = currency($document->bill_frequency * $obj->qty * $obj->price, 2);
            } else {
                $amount = currency($obj->qty * $obj->price, 2);
            }
        } elseif (isset($obj->price) && $doctype->doctable == 'crm_supplier_documents') {
            $amount = currency($obj->qty * $obj->price);
        }
        if ($doctype_detail->type == 'Credit' && $amount != 0) {
            $amount = $amount * -1;
        }

        return $amount;
    }

    $subtract_arr = explode('-', $math_str);

    $amount = 0;
    foreach ($subtract_arr as $i => $subtract) {
        $addition_arr = explode('+', $subtract);

        if (count($addition_arr) > 1) {
            $addition_amount = null;
            foreach ($addition_arr as $addition) {
                $addition_amount += $obj->{$addition};
            }
        }

        if ($i == 0) {
            if ($addition_amount !== null) {
                $amount = $addition_amount;
            } else {
                $amount = $obj->{$subtract};
            }
        } else {
            if ($addition_amount !== null) {
                $amount -= $addition_amount;
            } else {
                $amount -= $obj->{$subtract};
            }
        }
    }
    if ($doctype_detail->type == 'Credit' && $amount != 0) {
        $amount = $amount * -1;
    }

    return $amount;
}

function ledger_post_ledger_account_id($doctype, $doctype_detail, $obj, $stock_product_ids, $airtime_product_ids)
{
    /*
    119 - Airtime Sales
    120 - Airtime Cost of Sales
    54 - Service Sales
    53 - Service Cost of Sales
    1 - Product Sales
    34 - Product Cost of Sales
    */
    if ($doctype->doctable == 'acc_cashbook_transactions' && $doctype_detail->document_ledger_account == 'cashbook_id') {

        $ledger_account_id = \DB::connection('default')->table('acc_cashbook')->where('id', $obj->cashbook_id)->pluck('ledger_account_id')->first();
    } elseif ($doctype_detail->document_ledger_account && ! empty($obj->{$doctype_detail->document_ledger_account})) {

        $ledger_account_id = $obj->{$doctype_detail->document_ledger_account};
    } elseif ($doctype_detail->service_ledger_account_id && ! in_array($obj->product_id, $stock_product_ids)) {

        $ledger_account_id = $doctype_detail->service_ledger_account_id;
    } elseif ($doctype_detail->ledger_account_id) {

        $ledger_account_id = $doctype_detail->ledger_account_id;
    }
    if ($doctype->doctable == 'acc_cashbook_transactions' && $doctype_detail->ledger_account_id == 8 && $obj->total < 0) {
        // change to vat on purchases if vat
        $ledger_account_id = 9;
    }
    //if ($doctype_detail->service_ledger_account_id == 54 && in_array($obj->product_id, $airtime_product_ids)) {
    //    $ledger_account_id = 119;
    //}

    //if ($doctype_detail->service_ledger_account_id == 53 && in_array($obj->product_id, $airtime_product_ids)) {
    //    $ledger_account_id = 120;
    //}

    return $ledger_account_id;
}

function ledger_validate_doctypes()
{
    $doctypes = \DB::table('acc_doctypes')->get();
}

function rebuild_supplier_ledger($supplier_id)
{
    $db = new DBEvent;

    $doctypes = \DB::table('acc_doctypes')->get();
    $data = [];
    foreach ($doctypes as $doctype) {
        $cols = get_columns_from_schema($doctype->doctable);
        if (in_array('supplier_id', $cols)) {
            $docids = \DB::table($doctype->doctable)->where('doctype', $doctype->doctype)->where('supplier_id', $supplier_id)->pluck('id')->toArray();
            $db->setTable($doctype->doctable);
            foreach ($docids as $docid) {
                $db->postDocument($docid);
            }
        }
    }
    $db->postDocumentCommit();
}

function rebuild_account_ledger($account_id)
{
    $db = new DBEvent;

    $doctypes = \DB::table('acc_doctypes')->get();
    $data = [];
    foreach ($doctypes as $doctype) {
        $cols = get_columns_from_schema($doctype->doctable);
        if (in_array('account_id', $cols)) {
            $docids = \DB::table($doctype->doctable)->where('doctype', $doctype->doctype)->where('account_id', $account_id)->pluck('id')->toArray();
            $db->setTable($doctype->doctable);
            foreach ($docids as $docid) {
                $db->postDocument($docid);
            }
        }
    }
    $db->postDocumentCommit();
}

function get_ledger_name($doctype, $docid, $doctable = false, $connection = 'default')
{
    $name = '';
    if (! $doctable) {
        $doctable = \DB::connection($connection)->table('acc_doctypes')->where('doctype', $doctype)->pluck('doctable')->first();
    }

    if ($doctype == 'Cashbook Customer Receipt' || $doctable == 'crm_documents') {
        $account_id = \DB::connection($connection)->table($doctable)->where('id', $docid)->pluck('account_id')->first();
        $name = \DB::connection($connection)->table('crm_accounts')->where('id', $account_id)->pluck('company')->first();
    }

    if (str_contains($doctype, 'Vendor') || str_contains($doctype, 'Expense') || str_contains($doctype, 'Cashbook Control Payment')) {
        $ledger_account_id = \DB::connection($connection)->table($doctable)->where('id', $docid)->pluck('ledger_account_id')->first();
        $name = \DB::connection($connection)->table('acc_ledger_accounts')->where('id', $ledger_account_id)->pluck('name')->first();
    }

    if (str_contains($doctype, 'Supplier')) {
        $supplier_id = \DB::connection($connection)->table($doctable)->where('id', $docid)->pluck('supplier_id')->first();
        $name = \DB::connection($connection)->table('crm_suppliers')->where('id', $supplier_id)->pluck('company')->first();
    }

    if ($doctype == 'General Journal') {
        $ledger_account_id = \DB::connection($connection)->table($doctable)->where('id', $docid)->pluck('ledger_account_id')->first();
        $name = \DB::connection($connection)->table('acc_ledger_accounts')->where('id', $ledger_account_id)->pluck('name')->first();
    }

    if ($doctype == 'Cost Adjustment' || $doctype == 'Stock Adjustment') {
        $product_id = \DB::connection($connection)->table($doctable)->where('id', $docid)->pluck('product_id')->first();
        $name = \DB::connection($connection)->table('crm_products')->where('id', $account_id)->pluck('code')->first();
    }

    return $name;
}

function get_ledger_reference($doctype, $docid, $doctable = false, $connection = 'default')
{
    $reference = '';
    if (! $doctable) {
        $doctable = \DB::connection($connection)->table('acc_doctypes')->where('doctype', $doctype)->pluck('doctable')->first();
    }

    if (Schema::hasColumn($doctable, 'reference')) {
        $reference = \DB::connection($connection)->table($doctable)->where('id', $docid)->pluck('reference')->first();
    }

    return $reference;
}
