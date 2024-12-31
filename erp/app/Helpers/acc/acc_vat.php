<?php

function button_supplier_documents_upload_invoice_file($request)
{
    return view('__app.banking.supplier_upload_invoice', ['id' => $request->id]);
}
function button_banking_details_upload_invoice_file($request)
{
    return view('__app.banking.banking_upload_invoice', ['id' => $request->id]);
}

function aftersave_generate_vat_report_details($request)
{
    generate_vat_report_details($request->id);
}

function generate_vat_report_details_all()
{
    $vat_report_ids = \DB::table('acc_vat_report')->pluck('id')->toArray();
    foreach ($vat_report_ids as $vat_report_id) {
        generate_vat_report_details($vat_report_id);
    }
}

function button_vat_report_detail_view_transaction($request)
{
    $detail = \DB::table('acc_vat_report_details')->select('docid', 'total_type')->where('id', $request->id)->get()->first();
    if ($detail->total_type == 'Output - Standard Rated Sales') {
        $url = get_menu_url_from_module_id(353);
    }
    if ($detail->total_type == 'Input - Supplier Invoices') {
        $url = get_menu_url_from_module_id(354);

    }
    if ($detail->total_type == 'Input - Bank Expense Invoices') {
        $url = get_menu_url_from_module_id(1837);
    }

    return redirect()->to($url.'?id='.$detail->docid);
}

function button_vat_report_detail_view_ledger($request)
{
    $detail = \DB::table('acc_vat_report_details')->select('docid', 'total_type', 'doctype')->where('id', $request->id)->get()->first();

    $url = get_menu_url_from_module_id(180);

    return redirect()->to($url.'?doctype='.$detail->doctype.'&docid='.$detail->docid);
}

function export_vat_reports($period = '2023-05-01')
{
    $date_start = date('Y-m-01', strtotime($period));
    $date_end = date('Y-m-t', strtotime($period));

    $uniq_id = date('Ym', strtotime($period));
    //Output - Standard Sales
    $sql = "SELECT crm_documents.id as 'cd id', DATE_FORMAT(crm_documents.docdate, '%Y/%m/%d') as 'cd docdate',
    DATE_FORMAT(crm_documents.docdate, '%Y/%m') as 'cd docdate_month',
    DATE_FORMAT(crm_documents.docdate,'%Y-%m-%d') as 'cd docdate_period', crm_documents.doctype as 'cd doctype',
    (case crm_documents.doctype                    
    when 'Credit Note' then (crm_documents.tax * -1)                    
    else                    crm_documents.tax                    
    end) as 'cd tax', 
    (case crm_documents.doctype                    
    when 'Credit Note' then (crm_documents.total * -1)                   
    else                    crm_documents.total                    
    end) as 'cd total', crm_accounts.company as 'ca company', 
    crm_documents.doc_no as 'cd doc_no'    
    FROM `crm_documents`     
    LEFT JOIN `crm_accounts` on `crm_documents`.`account_id` = `crm_accounts`.`id`     
    WHERE crm_documents.doctype IN ('Tax Invoice','Credit Note') and crm_documents.total != '0' AND crm_documents.tax != 0  AND (crm_documents.docdate >='".$date_start."' and crm_documents.docdate <='".$date_end."')";

    $file_name = 'Output - Standard Sales'.$uniq_id.'.xlsx';
    $excel_list = \DB::select($sql);

    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'exports');

    //Input - Supplier Invoices
    $sql = "SELECT DATE_FORMAT(crm_supplier_documents.docdate, '%Y/%m/%d') as 'csd docdate',
    DATE_FORMAT(crm_supplier_documents.docdate, '%Y/%m') as 'csd docdate_month', DATE_FORMAT(crm_supplier_documents.docdate,'%Y-%m-%d') as 'csd docdate_period',
    crm_suppliers.company as 'cs company', crm_supplier_documents.id as 'csd id', crm_supplier_documents.doctype as 'csd doctype',
    (case crm_supplier_documents.doctype                    
    when 'Supplier Debit Note' 
    then (crm_supplier_documents.tax * -1)                    
    else                    crm_supplier_documents.tax                    
    end) as 'csd tax', 
    (case crm_supplier_documents.doctype                    
    when 'Supplier Debit Note' 
    then (crm_supplier_documents.total * -1)                    
    else crm_supplier_documents.total                    
    end) as 'csd total',
    crm_suppliers.id as 'cs id', crm_suppliers.status as 'cs status',
    crm_supplier_documents.doc_no as 'csd doc_no'
    FROM `crm_supplier_documents` 
    INNER JOIN `crm_suppliers` on `crm_supplier_documents`.`supplier_id` = `crm_suppliers`.`id`     
    WHERE crm_supplier_documents.tax != 0 AND crm_supplier_documents.doctype!='Supplier Order' 
    AND (crm_supplier_documents.docdate >='".$date_start."' and crm_supplier_documents.docdate <='".$date_end."')";

    $file_name = 'Input - Supplier Invoices'.$uniq_id.'.xlsx';
    $excel_list = \DB::select($sql);

    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'exports');

    //Input - Bank Expense Invoices
    $sql = "SELECT CONCAT(acc_ledger_account_categories.sort_order,' ',acc_ledger_account_categories.category) as 'alac category',
    acc_ledger_account_categories.id as 'alac id', acc_ledger_account_categories.sort_order as 'alac sort_order',
    acc_ledger_accounts.allow_payments as 'ala allow_payments', acc_ledger_accounts.id as 'ala id',
    acc_ledger_accounts.ledger_account_category_id as 'ala ledger_account_category_id', acc_ledger_accounts.name as 'ala name',
    acc_ledger_accounts.sort_order as 'ala sort_order', acc_ledger_accounts.target as 'ala target',
    acc_ledger_accounts.taxable as 'ala taxable', DATE_FORMAT(acc_cashbook_transactions.docdate, '%Y/%m/%d') as 'act docdate',
    DATE_FORMAT(acc_cashbook_transactions.docdate, '%Y/%m') as 'act docdate_month',
    DATE_FORMAT(acc_cashbook_transactions.docdate,'%Y-%m-%d') as 'act docdate_period', acc_cashbook_transactions.doctype as 'act doctype',
    acc_cashbook_transactions.id as 'act id', acc_cashbook_transactions.reference as 'act reference', acc_cashbook_transactions.tax as 'act tax',
    acc_cashbook_transactions.total as 'act total', acc_cashbook_transactions.doc_no as 'act doc_no'
    FROM `acc_ledger_account_categories`     
    LEFT JOIN `acc_ledger_accounts` on `acc_ledger_account_categories`.`id` = `acc_ledger_accounts`.`ledger_account_category_id` 
    RIGHT JOIN `acc_cashbook_transactions` on `acc_ledger_accounts`.`id` = `acc_cashbook_transactions`.`ledger_account_id`     
    WHERE acc_cashbook_transactions.tax!=0 AND acc_cashbook_transactions.total!=0 
    AND acc_cashbook_transactions.doctype='Cashbook Expense' 
    AND (acc_cashbook_transactions.docdate >='".$date_start."' and acc_cashbook_transactions.docdate <='".$date_end."')";

    $file_name = 'Input - Bank Expense Invoices'.$uniq_id.'.xlsx';
    $excel_list = \DB::select($sql);

    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'exports');

    //Output - Zero Rated Sales
    $sql = "SELECT crm_documents.id as 'cd id', DATE_FORMAT(crm_documents.docdate, '%Y/%m/%d') as 'cd docdate',
    DATE_FORMAT(crm_documents.docdate, '%Y/%m') as 'cd docdate_month', DATE_FORMAT(crm_documents.docdate,'%Y-%m-%d') as 'cd docdate_period',
    crm_documents.doctype as 'cd doctype', 
    (case crm_documents.doctype                    
    when 'Credit Note' 
    then (crm_documents.tax * -1)                    
    else crm_documents.tax  end) as 'cd tax',
    (case crm_documents.doctype                    
    when 'Credit Note' then (crm_documents.total * -1)                    
    else  crm_documents.total  end) as 'cd total',
    crm_accounts.company as 'ca company', 
    crm_documents.doc_no as 'cd doc_no' 
    FROM `crm_documents`     
    LEFT JOIN `crm_accounts` on `crm_documents`.`account_id` = `crm_accounts`.`id`     
    WHERE crm_documents.total != '0' AND crm_documents.tax = 0  
    AND crm_documents.doctype IN ('Tax Invoice','Credit Note') and (crm_documents.docdate >='".$date_start."' and crm_documents.docdate <='".$date_end."')";

    $file_name = 'Output - Zero Rated Sales'.$uniq_id.'.xlsx';
    $excel_list = \DB::select($sql);

    $export = new App\Exports\CollectionExport;
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'exports');
}

function button_vat_recon_download_reports($request = false)
{

    $vat_submission = \DB::table('acc_vat_report')->where('id', $request->id)->get()->first();
    generate_vat_report_details($request->id);
    $format = 'xlsx';
    $uniq_id = date('Ym', strtotime($vat_submission->period));
    // EXPORT REPORTS
    export_vat_reports($vat_submission->period);

    // ZIP REPORTS

    $documents = \DB::table('crm_documents')
        ->select('id', 'doctype', 'doc_no')
        ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])
        ->where('docdate', '>=', date('Y-m-01', strtotime($vat_submission->period)))
        ->where('docdate', '<=', date('Y-m-t', strtotime($vat_submission->period)))
        ->where('total', '!=', 0)
        ->get();
    $supplier_documents = \DB::table('crm_supplier_documents')
        ->select('id', 'doctype', 'doc_no', 'supporting_document', 'tax')
        ->whereIn('doctype', ['Supplier Invoice', 'Supplier Debit Note'])
        ->where('docdate', '>=', date('Y-m-01', strtotime($vat_submission->period)))
        ->where('docdate', '<=', date('Y-m-t', strtotime($vat_submission->period)))
        ->get();
    $pdfs = [];
    foreach ($documents as $document) {
        $file = str_replace(' ', '_', ucfirst($document->doctype).' '.$document->doc_no).'.pdf';
        $filename = attachments_path().$file;
        if (! file_exists($filename)) {
            $pdf = document_pdf($document->id);
            $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
        }
        $pdfs[$file] = $filename;
    }

    foreach ($supplier_documents as $document) {
        if ($document->tax != 0) {
            $docname = str_replace(' ', '_', ucfirst($document->doctype).' '.$document->doc_no).' - ';
            $filePath = uploads_path(354);
            if ($document->supporting_document) {
                $pdfs[$docname.$document->supporting_document] = $filePath.$document->supporting_document;
            }
        }
    }

    $zip_filename = 'Vat Reports '.date('Y-m', strtotime($vat_submission->period)).'.zip';
    $zip = new ZipArchive;

    $filenames = [
        'Output - Standard Sales'.$uniq_id.'.xlsx',
        'Input - Supplier Invoices'.$uniq_id.'.xlsx',
        'Input - Bank Expense Invoices'.$uniq_id.'.xlsx',
        'Output - Zero Rated Sales'.$uniq_id.'.xlsx',
    ];

    if (true === ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
        foreach ($filenames as $filename) {
            $file_path = storage_path('exports').'/'.session('instance')->directory.'/'.$filename;

            $file = $filename;
            $zip->addFile($file_path, $file);
        }

        foreach ($pdfs as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();
    }

    // SAVE ZIP TO VAT SUBMISSION

    $uploads_path = uploads_path(675).$zip_filename;
    $move = File::move(public_path().'/'.$zip_filename, $uploads_path);

    \DB::table('acc_vat_report')->where('id', $request->id)->update(['zip_reports' => $zip_filename]);

    return json_alert('Done');
}

function button_update_vat_totals($request)
{
    $vat_reports = \DB::table('acc_vat_report')->get();
    foreach ($vat_reports as $vat_report) {
        set_vat_report_totals($vat_report->id);
    }
    build_vat_balance();

    return json_alert('Done');
}

function schedule_update_vat_reports()
{

    $vat_reports = \DB::table('acc_vat_report')->where('vat_submitted', 0)->get();
    foreach ($vat_reports as $vat_report) {
        set_vat_report_totals($vat_report->id);
        generate_vat_report_details($vat_report->id);
    }
    build_vat_balance();
}

function schedule_generate_vat_reports()
{

    $date_start = '2020-03-01';
    $date_end = date('Y-m-01');

    while ($date_start <= $date_end) {
        $vat_submission = \DB::table('acc_vat_report')->where('period', date('Y-m-01', strtotime($date_start)))->count();
        if (! $vat_submission) {
            $id = \DB::table('acc_vat_report')->insertGetId(['period' => date('Y-m-01', strtotime($date_start))]);
            generate_vat_report_details($id);
        }

        $date_start = date('Y-m-01', strtotime($date_start.' +1 month'));
    }
    $vat_reports = \DB::table('acc_vat_report')->where('totals_set', 0)->get();
    foreach ($vat_reports as $vat_report) {
        set_vat_report_totals($vat_report->id);
    }
    $vat_reports = \DB::table('acc_vat_report')->where('vat_submitted', 0)->get();
    foreach ($vat_reports as $vat_report) {
        generate_vat_report_details($vat_report->id);
    }
    build_vat_balance();

    $vat_reports = \DB::table('acc_vat_report')->get();
    foreach ($vat_reports as $vat_report) {
        $n = date('n', strtotime($vat_report->period));
        if ($n % 2 == 0) {
            \DB::table('acc_vat_report')->where('id', $vat_report->id)->update(['vat_period' => date('Y-m-01', strtotime($vat_report->period))]);
        } else {
            \DB::table('acc_vat_report')->where('id', $vat_report->id)->update(['vat_period' => date('Y-m-01', strtotime($vat_report->period.' +1 month'))]);
        }
    }
}

function set_vat_report_totals($row_id)
{
    $vat_submission = \DB::table('acc_vat_report')->where('id', $row_id)->get()->first();
    $period = $vat_submission->period;
    $date_start = date('Y-m-01', strtotime($period));
    $date_end = date('Y-m-t', strtotime($period));

    $zero_rated_sales = \DB::table('crm_documents')
        ->select(
            \DB::raw(
                "SUM((case crm_documents.doctype
            when 'Credit Note' then (crm_documents.total * -1) - (crm_documents.tax * -1)
            else
            (crm_documents.total) - (crm_documents.tax)
            end)) as 'subtotal'"
            )
        )
        ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])
        ->where('total', '!=', 0)
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->where('tax', 0)
        ->pluck('subtotal')->first();
    $standard_rated_sales = \DB::table('crm_documents')
        ->select(
            \DB::raw(
                "SUM((case crm_documents.doctype
            when 'Credit Note' then (crm_documents.total * -1) - (crm_documents.tax * -1)
            else
            (crm_documents.total) - (crm_documents.tax)
            end)) as 'subtotal'"
            )
        )
        ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])
        ->where('total', '!=', 0)
        ->where('tax', '!=', 0)
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->pluck('subtotal')->first();

    $input_vat_suppliers = \DB::table('crm_supplier_documents')
        ->select(
            \DB::raw(
                "SUM((case crm_supplier_documents.doctype
            when 'Supplier Debit Note' then (crm_supplier_documents.total * -1) - (crm_supplier_documents.tax * -1)
            else
            (crm_supplier_documents.total) - (crm_supplier_documents.tax)
            end)) as 'subtotal'"
            )
        )
        ->whereIn('doctype', ['Supplier Invoice', 'Supplier Debit Note'])
        ->where('total', '!=', 0)
        ->where('tax', '!=', 0)
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->pluck('subtotal')->first();

    // cashbook ledger transactions
    $input_vat_journals = \DB::table('acc_cashbook_transactions')
        ->select(
            \DB::raw(
                "SUM((acc_cashbook_transactions.total*-1) - (acc_cashbook_transactions.tax*-1)) as 'subtotal'"
            )
        )
        ->where('ledger_account_id', '>', 0)
        ->where('doctype', 'Cashbook Expense')
        ->where('total', '!=', 0)
        ->where('tax', '!=', 0)
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->pluck('subtotal')->first();

    $input_vat_suppliers_tax = \DB::table('crm_supplier_documents')
        ->select(
            \DB::raw(
                "SUM((case crm_supplier_documents.doctype
            when 'Supplier Debit Note' then (crm_supplier_documents.tax * -1)
            else
            (crm_supplier_documents.tax)
            end)) as 'tax_payable'"
            )
        )
        ->whereIn('doctype', ['Supplier Invoice', 'Supplier Debit Note'])
        ->where('total', '!=', 0)
        ->where('tax', '!=', 0)
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->pluck('tax_payable')->first();

    $input_vat_journals_tax = \DB::table('acc_cashbook_transactions')
        ->where('ledger_account_id', '>', 0)
        ->where('doctype', 'Cashbook Expense')
   // ->where('reference', 'NOT LIKE', '%SARS VAT%')
   // ->where('reference', 'NOT LIKE', '%SARSEFLNG%')
        ->where('total', '!=', 0)
        ->where('tax', '!=', 0)
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->sum('tax');

    $standard_rated_sales_tax = \DB::table('crm_documents')
        ->select(
            \DB::raw(
                "SUM((case crm_documents.doctype
            when 'Credit Note' then (crm_documents.tax * -1)
            else
             (crm_documents.tax)
            end)) as 'tax_payable'"
            )
        )
        ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])
        ->where('total', '!=', 0)
        ->where('tax', '!=', 0)
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->pluck('tax_payable')->first();
    $input_vat_suppliers_tax = abs($input_vat_suppliers_tax);
    $input_vat_journals_tax = abs($input_vat_journals_tax);
    if ($input_vat_suppliers < 0) {
        $input_vat_suppliers_tax = $input_vat_suppliers_tax * -1;
    }
    if ($input_vat_journals_tax < 0) {
        $input_vat_journals_tax = $input_vat_journals_tax * -1;
    }

    $input_vat = $input_vat_suppliers_tax + $input_vat_journals_tax;

    $output_vat = $standard_rated_sales_tax;
    $amount_payable = $input_vat - $output_vat;

    $sars_vat = \DB::table('acc_cashbook_transactions')
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->where('reference', 'LIKE', '%SARS VAT%')
        ->sum('total');
    $sars_efiling = \DB::table('acc_cashbook_transactions')
        ->where('docdate', '>=', $date_start)
        ->where('docdate', '<=', $date_end)
        ->where('reference', 'LIKE', '%SARSEFLNG%')
        ->sum('total');

    $sars_payment = $sars_vat + $sars_efiling;

    $data = [
        'output_vat_zero_rated' => $zero_rated_sales,
        'output_vat_standard_rated' => $standard_rated_sales,
        'output_vat' => $output_vat,
        'input_vat_suppliers' => $input_vat_suppliers,
        'input_vat_journals' => $input_vat_journals,
        'input_vat_suppliers_tax' => $input_vat_suppliers_tax,
        'input_vat_journals_tax' => $input_vat_journals_tax,
        'input_vat' => $input_vat,
        'amount_payable' => $amount_payable,
        'sars_payment' => $sars_payment,
        'totals_set' => 1,
    ];

    \DB::table('acc_vat_report')->where('id', $row_id)->update($data);
}

function generate_vat_zip_file($row_id)
{

    $customer_pdfs = [];
    $supplier_pdfs = [];
    $vat_submission = \DB::table('acc_vat_report')->where('id', $row_id)->get()->first();

    $format = 'xlsx';
    $uniq_id = date('Ym', strtotime($vat_submission->period));
    // EXPORT REPORTS
    export_vat_reports($vat_submission->period);

    // ZIP REPORTS

    $documents = \DB::table('crm_documents')
        ->select('id', 'doctype', 'doc_no')
        ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])
        ->where('docdate', '>=', date('Y-m-01', strtotime($vat_submission->period)))
        ->where('docdate', '<=', date('Y-m-t', strtotime($vat_submission->period)))
        ->where('total', '!=', 0)
        ->get();
    $supplier_documents = \DB::table('crm_supplier_documents')
        ->select('id', 'doctype', 'doc_no', 'supporting_document', 'tax')
        ->whereIn('doctype', ['Supplier Invoice', 'Supplier Debit Note'])
        ->where('docdate', '>=', date('Y-m-01', strtotime($vat_submission->period)))
        ->where('docdate', '<=', date('Y-m-t', strtotime($vat_submission->period)))
        ->get();

    foreach ($documents as $document) {
        $file = str_replace(' ', '_', ucfirst($document->doctype).' '.$document->doc_no).'.pdf';
        $filename = attachments_path().$file;
        if (! file_exists($filename)) {
            $pdf = document_pdf($document->id);
            $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
        }
        $customer_pdfs[$file] = $filename;
    }

    foreach ($supplier_documents as $document) {
        if ($document->tax != 0) {
            $docname = str_replace(' ', '_', ucfirst($document->doctype).' '.$document->doc_no).' - ';
            $filePath = uploads_path(354);
            if ($document->supporting_document) {
                $supplier_pdfs[$docname.$document->supporting_document] = $filePath.$document->supporting_document;
            }
        }
    }

    // EXCEL ZIP
    $zip_filename = 'Vat Excel Reports '.date('Y-m', strtotime($vat_submission->period)).'.zip';
    $zip = new ZipArchive;

    $filenames = [
        'Output - Standard Sales'.$uniq_id.'.xlsx',
        'Input - Supplier Invoices'.$uniq_id.'.xlsx',
        'Input - Bank Expense Invoices'.$uniq_id.'.xlsx',
        'Output - Zero Rated Sales'.$uniq_id.'.xlsx',
    ];

    if (true === ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
        foreach ($filenames as $filename) {
            $file_path = storage_path('exports').'/'.session('instance')->directory.'/'.$filename;

            $file = $filename;
            $zip->addFile($file_path, $file);
        }

        $zip->close();
    }
    $uploads_path = uploads_path(675).$zip_filename;
    $move = File::move(public_path().'/'.$zip_filename, $uploads_path);

    \DB::table('acc_vat_report')->where('id', $row_id)->update(['excel_zip' => $zip_filename]);

    // SUPPLIER DOCUMENTS ZIP
    $zip_filename = 'Vat Suppliers Documents '.date('Y-m', strtotime($vat_submission->period)).'.zip';
    $zip = new ZipArchive;

    if (true === ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
        foreach ($supplier_pdfs as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();
    }

    $uploads_path = uploads_path(675).$zip_filename;
    $move = File::move(public_path().'/'.$zip_filename, $uploads_path);

    \DB::table('acc_vat_report')->where('id', $row_id)->update(['supplier_documents_zip' => $zip_filename]);

    // CUSTOMER DOCUMENTS ZIP
    $zip_filename = 'Vat Customer Documents '.date('Y-m', strtotime($vat_submission->period)).'.zip';
    $zip = new ZipArchive;
    if (true === ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
        foreach ($customer_pdfs as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();
    }

    $uploads_path = uploads_path(675).$zip_filename;
    $move = File::move(public_path().'/'.$zip_filename, $uploads_path);

    \DB::table('acc_vat_report')->where('id', $row_id)->update(['customer_documents_zip' => $zip_filename]);
}

function generate_vat_report_details($vat_report_id)
{
    \DB::table('acc_vat_report_details')->where('vat_report_id', $vat_report_id)->delete();
    $period = \DB::table('acc_vat_report')->where('id', $vat_report_id)->pluck('period')->first();
    $date_start = date('Y-m-01', strtotime($period));
    $date_end = date('Y-m-t', strtotime($period));

    //Input - Supplier Invoices
    $sql = "SELECT DATE_FORMAT(crm_supplier_documents.docdate, '%Y/%m/%d') as 'docdate',
    crm_suppliers.company as 'allocated_to', crm_supplier_documents.id as 'docid', crm_supplier_documents.doctype as 'doctype',
    (case crm_supplier_documents.doctype                    
    when 'Supplier Debit Note' 
    then (crm_supplier_documents.tax * -1)                    
    else                    crm_supplier_documents.tax                    
    end) as 'tax', 
    (case crm_supplier_documents.doctype                    
    when 'Supplier Debit Note' 
    then (crm_supplier_documents.total * -1)                    
    else crm_supplier_documents.total                    
    end) as 'total'
    FROM `crm_supplier_documents` 
    INNER JOIN `crm_suppliers` on `crm_supplier_documents`.`supplier_id` = `crm_suppliers`.`id`     
    WHERE crm_supplier_documents.tax != 0 AND crm_supplier_documents.doctype!='Supplier Order' 
    AND (crm_supplier_documents.docdate >='".$date_start."' and crm_supplier_documents.docdate <='".$date_end."')";

    $rows = \DB::select($sql);
    foreach ($rows as $row) {
        $d = (array) $row;

        $d['total_type'] = 'Input - Supplier Invoices';
        $d['vat_report_id'] = $vat_report_id;
        \DB::table('acc_vat_report_details')->insert($d);
    }

    //Input - Bank Expense Invoices
    $sql = "SELECT acc_ledger_accounts.name as 'allocated_to',
    DATE_FORMAT(acc_cashbook_transactions.docdate, '%Y/%m/%d') as 'docdate',
    acc_cashbook_transactions.doctype as 'doctype',
    acc_cashbook_transactions.id as 'docid',
    acc_cashbook_transactions.tax as 'tax',
    acc_cashbook_transactions.total as 'total'
    FROM `acc_ledger_account_categories`     
    LEFT JOIN `acc_ledger_accounts` on `acc_ledger_account_categories`.`id` = `acc_ledger_accounts`.`ledger_account_category_id` 
    RIGHT JOIN `acc_cashbook_transactions` on `acc_ledger_accounts`.`id` = `acc_cashbook_transactions`.`ledger_account_id`     
    WHERE acc_cashbook_transactions.tax!=0 AND acc_cashbook_transactions.total!=0 
    AND acc_cashbook_transactions.doctype='Cashbook Expense' 
    AND (acc_cashbook_transactions.docdate >='".$date_start."' and acc_cashbook_transactions.docdate <='".$date_end."')";

    $rows = \DB::select($sql);
    foreach ($rows as $row) {
        $d = (array) $row;

        $d['total_type'] = 'Input - Bank Expense Invoices';
        $d['vat_report_id'] = $vat_report_id;
        \DB::table('acc_vat_report_details')->insert($d);
    }

    //Output - Standard Rated Sales
    $sql = "SELECT crm_documents.id as 'docid', DATE_FORMAT(crm_documents.docdate, '%Y/%m/%d') as 'docdate',
    crm_documents.doctype as 'doctype', 
    (case crm_documents.doctype                    
    when 'Credit Note' 
    then (crm_documents.tax * -1)                    
    else crm_documents.tax  end) as 'tax',
    (case crm_documents.doctype                    
    when 'Credit Note' then (crm_documents.total * -1)                    
    else  crm_documents.total  end) as 'total',
    crm_accounts.company as 'allocated_to'
    FROM `crm_documents`     
    LEFT JOIN `crm_accounts` on `crm_documents`.`account_id` = `crm_accounts`.`id`     
    WHERE crm_documents.total != '0' AND crm_documents.tax != 0 and crm_documents.doctype IN ('Tax Invoice','Credit Note')
    AND (crm_documents.docdate >='".$date_start."' and crm_documents.docdate <='".$date_end."')";

    $rows = \DB::select($sql);
    foreach ($rows as $row) {
        $d = (array) $row;

        $d['total_type'] = 'Output - Standard Rated Sales';
        $d['vat_report_id'] = $vat_report_id;
        \DB::table('acc_vat_report_details')->insert($d);
    }
}

function build_vat_balance()
{
    $vat_balance = \DB::table('acc_ledgers')->whereIn('ledger_account_id', [8, 9])->where('docdate', '<', '2019-10-01')->sum('amount');

    $vat_reports = \DB::table('acc_vat_report')->orderBy('period', 'asc')->get();
    foreach ($vat_reports as $vat_report) {
        $ledger_account_id = 8;
        $ledger_account_ids = [8, 9];
        $debit_balance_query = \DB::table('acc_general_journals as aj')
            ->join('acc_general_journal_transactions as ajt', 'aj.transaction_id', '=', 'ajt.id')
            ->whereIn('ledger_account_id', $ledger_account_ids)
            ->where('debit_amount', '>', 0);

        $credit_balance_query = \DB::table('acc_general_journals as aj')
            ->join('acc_general_journal_transactions as ajt', 'aj.transaction_id', '=', 'ajt.id')
            ->whereIn('ledger_account_id', $ledger_account_ids)
            ->where('credit_amount', '>', 0);

        $debit_balance = $debit_balance_query->where('ajt.docdate', 'like', date('Y-m', strtotime($vat_report->period)).'%')->sum('debit_amount');
        $credit_balance = $credit_balance_query->where('ajt.docdate', 'like', date('Y-m', strtotime($vat_report->period)).'%')->sum('credit_amount');

        $journal_adjustment = $debit_balance - $credit_balance;

        $vat_balance += $journal_adjustment;
        $vat_balance += $vat_report->amount_payable;
        $vat_balance -= $vat_report->sars_payment;

        \DB::table('acc_vat_report')->where('id', $vat_report->id)->update(['vat_balance' => $vat_balance]);
    }
}

function schedule_submit_vat_monthly()
{
    // monthly 1st

    schedule_period_check();
    // set period status
    $sql = "UPDATE acc_vat_report 
    JOIN acc_periods ON acc_periods.period=DATE_FORMAT(acc_vat_report.vat_period, '%Y-%m')
    SET acc_vat_report.period_status = acc_periods.status";
    \DB::statement($sql);

    $vat_reports = \DB::table('acc_vat_report')->where('vat_submitted', 0)->where('period_status', 'closed')->get();

    foreach ($vat_reports as $vat_report) {
        if (date('Y-m') == date('Y-m', strtotime($vat_report->period))) {
            continue;
        }
        set_vat_report_totals($vat_report->id);
        generate_vat_report_details($vat_report->id);
        generate_vat_zip_file($vat_report->id);
    }

    build_vat_balance();

    foreach ($vat_reports as $vat_report) {
        if (date('Y-m') == date('Y-m', strtotime($vat_report->period))) {
            continue;
        }
        if (empty($vat_report->excel_zip)) {
            continue;
        }
        if (empty($vat_report->customer_documents_zip)) {
            continue;
        }
        if (empty($vat_report->supplier_documents_zip)) {
            continue;
        }
        $excel_zip = uploads_path(675).$vat_report->excel_zip;
        if (! file_exists($excel_zip)) {
            continue;
        }
        $customer_documents_zip = uploads_path(675).$vat_report->customer_documents_zip;
        if (! file_exists($customer_documents_zip)) {
            continue;
        }
        $supplier_documents_zip = uploads_path(675).$vat_report->supplier_documents_zip;
        if (! file_exists($supplier_documents_zip)) {
            continue;
        }

        $to_email = 'asiya@amlaaccountants.co.za';
        //  $to_email = 'ahmed@telecloud.co.za';
        $mail_data = [];
        $mail_data['internal_function'] = 'monthly_vat_submission';
        $mail_data['period'] = date('Y-m', strtotime($vat_report->period));
        $mail_data['report_type'] = 'Excel Reports';

        $mail_data['files'] = [$excel_zip];
        $mail_data['force_to_email'] = $to_email;
        $mail_data['cc_admin'] = 1;
        //$mail_data['test_debug'] = 1;
        $excel_sent = erp_process_notification(1, $mail_data);

        $mail_data = [];
        $mail_data['internal_function'] = 'monthly_vat_submission';
        $mail_data['period'] = date('Y-m', strtotime($vat_report->period));
        $mail_data['report_type'] = 'Customer Documents';

        $mail_data['files'] = [$customer_documents_zip];
        $mail_data['force_to_email'] = $to_email;
        $mail_data['cc_admin'] = 1;
        //$mail_data['test_debug'] = 1;
        $customer_documents_sent = erp_process_notification(1, $mail_data);

        $mail_data = [];
        $mail_data['internal_function'] = 'monthly_vat_submission';
        $mail_data['period'] = date('Y-m', strtotime($vat_report->period));
        $mail_data['report_type'] = 'Supplier Documents';

        $mail_data['files'] = [$supplier_documents_zip];
        $mail_data['force_to_email'] = $to_email;
        $mail_data['cc_admin'] = 1;
        //$mail_data['test_debug'] = 1;
        $supplier_documents_sent = erp_process_notification(1, $mail_data);

        if ($excel_sent != 'Sent' || $customer_documents_sent != 'Sent' || $supplier_documents_sent != 'Sent') {
            debug_email('Vat not submitted. '.$excel_sent.' '.$customer_documents_sent.' '.$supplier_documents_sent);
        } else {
            // all emails sent
            \DB::table('acc_vat_report')->where('id', $vat_report->id)->update(['vat_submitted' => 1]);
        }
    }
}

function button_vat_compare_accountant_reports($request)
{
    $vat_report = \DB::table('acc_vat_report')->where('id', $request->id)->get()->first();

    $vat_report_ids = \DB::table('acc_vat_report')->where('vat_period', $vat_report->vat_period)->pluck('id')->toArray();
    if (empty($vat_report->accountant_input_report) || empty($vat_report->accountant_output_report)) {
        return json_alert('Accountant reports not uploaded', 'warning');
    }

    $accountant_input = uploads_path(675).$vat_report->accountant_input_report;
    $accountant_output = uploads_path(675).$vat_report->accountant_output_report;
    if (! file_exists($accountant_input) || ! file_exists($accountant_output)) {
        return json_alert('Accountant report files not found', 'warning');
    }

    $input_bank_transactions = \DB::table('acc_vat_report_details')->where('total_type', 'Input - Bank Expense Invoices')->whereIn('vat_report_id', $vat_report_ids)->get();
    $input_supplier_transactions = \DB::table('acc_vat_report_details')->where('total_type', 'Input - Supplier Invoices')->whereIn('vat_report_id', $vat_report_ids)->get();

    $input_report = file_to_array($accountant_input);
    foreach ($input_report as $i => $r) {
        if (empty($r['date'])) {
            unset($input_report[$i]);
        }
    }
    $input_report = collect($input_report)->filter()->toArray();

    foreach ($input_report as $row) {
        if (empty($row['date'])) {
            continue;
        }

        $row_date = Carbon::parse($row['date'])->toDateString();

        \DB::table('acc_vat_report_details')
            ->where('total_type', 'like', 'Input%')
            ->whereIn('vat_report_id', $vat_report_ids)
            ->where('docdate', $row_date)
            ->where('tax', currency($row['vat']))
            ->update(['accountant_report_verified' => 1, 'accountant_report_vat' => currency($row['vat'])]);

    }

    $output_report = file_to_array($accountant_output);
    foreach ($output_report as $i => $r) {
        if (empty($r['date'])) {
            unset($output_report[$i]);
        }
    }
    $output_report = collect($output_report)->filter()->toArray();

    foreach ($output_report as $row) {
        if (empty($row['date'])) {
            continue;
        }
        $docid = \DB::table('crm_documents')->where('doc_no', $row['doc_no'])->pluck('id')->first();
        $row_date = Carbon::parse($row['date'])->toDateString();

        \DB::table('acc_vat_report_details')
            ->where('total_type', 'like', 'output%')
            ->whereIn('vat_report_id', $vat_report_ids)
            ->where('docdate', $row_date)
            ->where('docid', $docid)
        //->where('tax',currency($row['vat']))
            ->update(['accountant_report_verified' => 1, 'accountant_report_vat' => currency($row['vat'])]);

        \DB::table('acc_vat_report_details')
            ->where('total_type', 'like', 'output%')
            ->whereIn('vat_report_id', $vat_report_ids)
            ->where('docdate', $row_date)
            ->where('tax', currency($row['vat']))
            ->update(['accountant_report_verified' => 1, 'accountant_report_vat' => currency($row['vat'])]);

    }
    \DB::table('acc_vat_report_details')
        ->whereIn('vat_report_id', $vat_report_ids)
        ->update(['accountant_report_diff' => \DB::raw('abs(tax) - abs(accountant_report_vat)')]);

    return json_alert('Done');
}
