<?php



function savepdf($pdf, $file)
{
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
}

function document_zar_pdf($id, $is_supplier = false, $return_html = false, $import_invoice = false)
{
    if ($is_supplier && $import_invoice) {
        $table = 'crm_supplier_import_documents';
        $lines_table = 'crm_supplier_import_document_lines';
    } elseif ($is_supplier) {
        $table = 'crm_supplier_documents';
        $lines_table = 'crm_supplier_document_lines';
    } else {
        $table = 'crm_documents';
        $lines_table = 'crm_document_lines';
    }

    $doc = \DB::table($table)->where('id', $id)->get()->first();
    if (!$doc || empty($doc)) {
        return false;
    }

    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $doc->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $doc->doctype;
    }

    $doclines = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->select('cdl.*', 'cp.code', 'cp.name', 'cp.is_subscription', 'cp.website_link', 'cp.product_category_id', 'cp.sort_order')
        ->where('cdl.document_id', $doc->id)
        ->get();

    $doclines = sort_document_lines($doclines);
    $from_import = 0;
    if ($is_supplier) {
        $account = dbgetsupplier($doc->supplier_id);
        $reseller = dbgetaccount(1);
        if ($table == 'crm_supplier_documents') {
            $from_import = \DB::table('crm_supplier_import_documents')->where('supplier_invoice_id', $id)->count();
        }
    } else {
        $account = dbgetaccount($doc->account_id);
        $reseller = dbgetaccount($account->partner_id);
    }
    if ($from_import) {
        $account->currency = 'USD';
    }

    $subscription_frequency = 'MONTHLY';
    if ($doc->billing_type == 'Annually') {
        $subscription_frequency = 'ANNUAL';
    }

    $data = [
        'doctype_label' => $doctype_label,
        'doc' => $doc,
        'doclines' => $doclines,
        'account' => $account,
        'reseller' => $reseller,
        'is_supplier' => $is_supplier,
        'subscription_frequency' => $subscription_frequency,
        'remove_monthly_totals' => get_admin_setting('remove_monthly_totals'),
    ];
    
    $due_date = date('Y/m/d',strtotime($doc->docdate.' + 7 days'));
    if(!$is_supplier){
        if($account->payment_type == 'Postpaid30Days'  || $account->payment_type == 'Internal'){
            $due_date = date('Y/m/d',strtotime($doc->docdate.' + 30 days'));
        }
    }
    $data['due_date'] = $due_date;
  

    $data['billing_period'] = date('Y-m-d', strtotime($doc->docdate)).' to '.date('Y-m-d', strtotime($doc->docdate." +".$doc->bill_frequency." months"));

    if ($doc->billing_type == 'Monthly') {
        $data['billing_period'] = date('Y-m-01', strtotime($doc->docdate)).' to '.date('Y-m-t', strtotime($doc->docdate));
    }
    if ($doc->billing_type == 'Annually') {
        $data['billing_period'] = date('Y-01-01', strtotime($doc->docdate)).' to '.date('Y-12-t', strtotime($doc->docdate));
    }
    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }

    $data['is_product'] = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->where('cdl.document_id', $doc->id)
        ->where('cp.type', 'Stock')
        ->count();

    $currency = $doc->exchange_currency;
    $data['exchange_currency'] = $currency;
    $data['exchange_rate'] = $doc->exchange_rate;

    $fmt = new NumberFormatter("en-us@currency=$currency", NumberFormatter::CURRENCY);
    $data['currency_symbol'] = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    $data['currency_symbol'] = 'R';

    $data['import_invoice'] = $import_invoice;
    if ($import_invoice) {
        $data['currency_symbol'] = '$';
    }

    if($reseller->id == 1){
        $data['bank_details'] = get_payment_option('Bank Details')->payment_instructions;
        $data['bank_details_usd'] = get_payment_option('Bank Details USD')->payment_instructions;
    }
    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => $reseller->company.' | '.$doc->doctype.' #'.$doc->id,
        'footer-right' => $doc->docdate.' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];
    if ($return_html) {
        $data['return_html'] = $return_html;
        return view('__app.components.pdfs.doc', $data);
    }
    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.doc', $data);
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);

    return $pdf;
}

function document_pdf_new($id, $is_supplier = false, $return_html = false, $import_invoice = false)
{
    if ($is_supplier && $import_invoice) {
        $table = 'crm_supplier_import_documents';
        $lines_table = 'crm_supplier_import_document_lines';
    } elseif ($is_supplier) {
        $table = 'crm_supplier_documents';
        $lines_table = 'crm_supplier_document_lines';
    } else {
        $table = 'crm_documents';
        $lines_table = 'crm_document_lines';
    }

    $doc = \DB::table($table)->where('id', $id)->get()->first();
    if (!$doc || empty($doc)) {
        return false;
    }

    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $doc->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $doc->doctype;
    }

    $doclines = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->select('cdl.*', 'cp.code', 'cp.frequency', 'cp.name', 'cp.description as product_description', 'cp.is_subscription', 'cp.website_link', 'cp.product_category_id', 'cp.sort_order')
        ->where('cdl.document_id', $doc->id)
        ->get();
   if($doc->doctype == 'Quotation'){
    foreach($doclines as $i => $docline){
        if(!$docline->subscription_id){
            $doclines[$i]->description = $docline->description;  
            $doclines[$i]->product_description = $docline->product_description; 
            
        $doclines[$i]->product_description = str_replace('<ol>','<ul>',$doclines[$i]->product_description);
        $doclines[$i]->product_description = str_replace('</ol>','</ul>',$doclines[$i]->product_description);
        }else{
            
        }
    }
   }
    
    $subs = \DB::table('sub_services')->where('status','!=','Deleted')->get();  
    $products = \DB::table('sub_services')->where('status','!=','Deleted')->get();  
    // add contract period to lines
       
    foreach($doclines as $i => $docline){
        if($docline->subscription_id){
            $sub = $subs->where('id',$docline->subscription_id)->first();
            if(date('Y',strtotime($sub->date_activated)) == date('Y')){
                $start_date = date('Y-m-d',strtotime($sub->date_activated));
            }else{
                $start_date = date('Y').'-'.date('m-d',strtotime($sub->date_activated));
            }
            if($start_date > date('Y-m-d')){
                $start_date = date('Y-m-d',strtotime($start_date.' -1 year'));
            }
            if($sub->contract_period){
                $doclines[$i]->description .= PHP_EOL.'<br><b>Contract Period:<br> '.date('Y-m-d',strtotime($start_date)).' to '.date('Y-m-d',strtotime($start_date.' +'.$sub->contract_period.' months')).'</b>';
            }
        }else{
            $product = $products->where('id',$docline->product_id)->first();
           
        }
        $doclines[$i]->description = str_replace('<ol>','<ul>',$doclines[$i]->description);
        $doclines[$i]->description = str_replace('</ol>','</ul>',$doclines[$i]->description);
        if(!empty($docline->cdr_destination)){
            $doclines[$i]->description = PHP_EOL.$docline->cdr_destination;
        }
    }
    
    $once_off_count = $doclines->where('frequency','once off')->count();
    $doclines_count = $doclines->count();
   
    $doclines = sort_document_lines($doclines);
    $from_import = 0;
    if ($is_supplier) {
        $account = dbgetsupplier($doc->supplier_id);
        $reseller = dbgetaccount(1);
        if ($table == 'crm_supplier_documents') {
            $from_import = \DB::table('crm_supplier_import_documents')->where('supplier_invoice_id', $id)->count();
        }
    } else {
        $account = dbgetaccount($doc->account_id);
        $reseller = dbgetaccount($account->partner_id);
    }
    if ($from_import) {
        $account->currency = 'USD';
    }

    $subscription_frequency = 'MONTHLY';
    if ($doc->billing_type == 'Annually') {
        $subscription_frequency = 'ANNUAL';
    }

    $data = [
        'doctype_label' => $doctype_label,
        'doc' => $doc,
        'doclines' => $doclines,
        'account' => $account,
        'reseller' => $reseller,
        'is_supplier' => $is_supplier,
        'subscription_frequency' => $subscription_frequency,
        'remove_monthly_totals' => get_admin_setting('remove_monthly_totals'),
    ];
    
   
    $due_date = date('Y/m/d',strtotime($doc->docdate.' + 7 days'));
    if(!$is_supplier){
        if($account->payment_type == 'Postpaid30Days'  || $account->payment_type == 'Internal'){
            $due_date = date('Y/m/d',strtotime($doc->docdate.' + 30 days'));
        }
    }
    $data['due_date'] = $due_date;
   
    if($once_off_count == $doclines_count){
        $data['once_off'] = 1;
    }else{
        $data['once_off'] = 0;    
    }

    $data['billing_period'] = date('Y-m-d', strtotime($doc->docdate)).' to '.date('Y-m-d', strtotime($doc->docdate." +".$doc->bill_frequency." months"));


    if ($doc->billing_type == 'Monthly') {
        $data['billing_period'] = date('Y-m-01', strtotime($doc->docdate)).' to '.date('Y-m-t', strtotime($doc->docdate));
    }

    if ($doc->billing_type == 'Annually') {
        $data['billing_period'] = date('Y-01-01', strtotime($doc->docdate)).' to '.date('Y-12-t', strtotime($doc->docdate));
    }
 
    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }

    $data['is_product'] = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->where('cdl.document_id', $doc->id)
        ->where('cp.type', 'Stock')
        ->count();

    $currency = $doc->document_currency;

    $data['document_currency'] = $currency;


    $fmt = new NumberFormatter("en-us@currency=$currency", NumberFormatter::CURRENCY);
    $data['currency_symbol'] = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

    if ('ZAR' == $data['currency_symbol']) {
        $data['currency_symbol'] = 'R';
    }
    $data['import_invoice'] = $import_invoice;
    if ($import_invoice) {
        $data['currency_symbol'] = '$';
    }

    $data['remove_tax_fields'] = get_admin_setting('remove_tax_fields');
    
    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => $reseller->company.' | '.$doc->doctype.' #'.$doc->id,
        'footer-right' => $doc->docdate.' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];
   
    if ($return_html) {
        $data['return_html'] = $return_html;
        return view('__app.components.pdfs.invoices.invoice', $data);
    }


    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.invoices.invoice', $data);
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);

    return $pdf;
}
function document_pdf($id, $is_supplier = false, $return_html = false, $import_invoice = false)
{
    if ($is_supplier && $import_invoice) {
        $table = 'crm_supplier_import_documents';
        $lines_table = 'crm_supplier_import_document_lines';
    } elseif ($is_supplier) {
        $table = 'crm_supplier_documents';
        $lines_table = 'crm_supplier_document_lines';
    } else {
        $table = 'crm_documents';
        $lines_table = 'crm_document_lines';
    }

    $doc = \DB::table($table)->where('id', $id)->get()->first();
    if (!$doc || empty($doc)) {
        return false;
    }

    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $doc->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $doc->doctype;
    }

    $doclines = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->select('cdl.*', 'cp.code', 'cp.frequency', 'cp.name', 'cp.description as product_description', 'cp.is_subscription', 'cp.website_link', 'cp.product_category_id', 'cp.sort_order')
        ->where('cdl.document_id', $doc->id)
        ->get();
   
    if ($is_supplier) {
        $ledger_accounts = \DB::table('acc_ledger_accounts')->get();
    }
    
    foreach($doclines as $i => $docline){
        if(!$docline->subscription_id){
            $doclines[$i]->description = $docline->description;  
            $doclines[$i]->product_description = $docline->product_description; 
            
            $doclines[$i]->product_description = str_replace('<ol>','<ul>',$doclines[$i]->product_description);
            $doclines[$i]->product_description = str_replace('</ol>','</ul>',$doclines[$i]->product_description);
            $doclines[$i]->product_description = preg_replace("/\r|\n/", "",$doclines[$i]->product_description);
        }
        if(!in_array($doc->doctype,['Quotation','Order'])){
            $doclines[$i]->product_description = '';
        }
        if($doc->billing_type > ''){
            $doclines[$i]->product_description = '';
        }
        
        
        if($is_supplier && !empty($docline->ledger_account_id)){
            $doclines[$i]->description .= '<br>Ledger account: '.$ledger_accounts->where('id',$docline->ledger_account_id)->pluck('name')->first();
        }
        
    }
    
    
    $subs = \DB::table('sub_services')->where('status','!=','Deleted')->get();  
    $products = \DB::table('sub_services')->where('status','!=','Deleted')->get();  
    // add contract period to lines
       
    foreach($doclines as $i => $docline){
        
        if($docline->subscription_id){
            $doclines[$i]->description = nl2br($doclines[$i]->description);
           // $doclines[$i]->description = str_replace(' - ','<br>',$doclines[$i]->description);
            $sub = $subs->where('id',$docline->subscription_id)->first();
            if(date('Y',strtotime($sub->date_activated)) == date('Y')){
                $start_date = date('Y-m-d',strtotime($sub->date_activated));
            }else{
                $start_date = date('Y').'-'.date('m-d',strtotime($sub->date_activated));
            }
            if($start_date > date('Y-m-d')){
                $start_date = date('Y-m-d',strtotime($start_date.' -1 year'));
            }
            if($sub->contract_period > 1){
                $doclines[$i]->description .= PHP_EOL.'<br><b>Contract Period:<br> '.date('Y-m-d',strtotime($start_date)).' to '.date('Y-m-d',strtotime($start_date.' +'.$sub->contract_period.' months')).'</b>';
            }
        }else{
            $product = $products->where('id',$docline->product_id)->first();
        }
        $doclines[$i]->description = str_replace('<ol>','<ul>',$doclines[$i]->description);
        $doclines[$i]->description = str_replace('</ol>','</ul>',$doclines[$i]->description);
        $doclines[$i]->description = preg_replace("/\r|\n/", "",$doclines[$i]->description);
        if(!empty($docline->cdr_destination)){
            $doclines[$i]->description = PHP_EOL.$docline->cdr_destination;
        }
    }
    
    $once_off_count = $doclines->where('frequency','once off')->count();
    $doclines_count = $doclines->count();
   
    $doclines = sort_document_lines($doclines);
    $from_import = 0;
    if ($is_supplier) {
        $account = dbgetsupplier($doc->supplier_id);
        $reseller = dbgetaccount(1);
        if ($table == 'crm_supplier_documents') {
            $from_import = \DB::table('crm_supplier_import_documents')->where('supplier_invoice_id', $id)->count();
        }
    } else {
        $account = dbgetaccount($doc->account_id);
        $reseller = dbgetaccount($account->partner_id);
    }
    if ($from_import) {
        $account->currency = 'USD';
    }

    $subscription_frequency = 'MONTHLY';
    if ($doc->billing_type == 'Annually') {
        $subscription_frequency = 'ANNUAL';
    }
   

    $data = [
        'doctype_label' => $doctype_label,
        'doc' => $doc,
        'doclines' => $doclines,
        'account' => $account,
        'reseller' => $reseller,
        'is_supplier' => $is_supplier,
        'subscription_frequency' => $subscription_frequency,
        'remove_monthly_totals' => get_admin_setting('remove_monthly_totals'),
    ];
   
    $data['requires_debit_order'] = false;
    if($reseller->id == 1){
       $data['requires_debit_order'] = invoice_requires_debit_order($id);
    }
      
      
    $due_date = date('Y/m/d',strtotime($doc->docdate.' + 7 days'));
    if(!$is_supplier){
        if($account->payment_type == 'Postpaid30Days'  || $account->payment_type == 'Internal'){
            $due_date = date('Y/m/d',strtotime($doc->docdate.' + 30 days'));
        }
    }
    $data['due_date'] = $due_date;
     
    if($once_off_count == $doclines_count){
        $data['once_off'] = 1;
    }else{
        $data['once_off'] = 0;    
    }

    $data['billing_period'] = date('Y-m-d', strtotime($doc->docdate)).' to '.date('Y-m-d', strtotime($doc->docdate." +".$doc->bill_frequency." months"));


    if ($doc->billing_type == 'Monthly') {
        $data['billing_period'] = date('Y-m-01', strtotime($doc->docdate)).' to '.date('Y-m-t', strtotime($doc->docdate));
    }

    if ($doc->billing_type == 'Annually') {
        $data['billing_period'] = date('Y-01-01', strtotime($doc->docdate)).' to '.date('Y-12-t', strtotime($doc->docdate));
    }
 
    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }
    $data['is_product'] = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->where('cdl.document_id', $doc->id)
        ->where('cp.type', 'Stock')
        ->count();

    $currency = $doc->document_currency;

    $data['document_currency'] = $currency;


    $fmt = new NumberFormatter("en-us@currency=$currency", NumberFormatter::CURRENCY);
    $data['currency_symbol'] = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

    if ('ZAR' == $data['currency_symbol']) {
        $data['currency_symbol'] = 'R';
    }
    $data['import_invoice'] = $import_invoice;
    if ($import_invoice) {
        $data['currency_symbol'] = '$';
    }

    $data['remove_tax_fields'] = get_admin_setting('remove_tax_fields');
    if($reseller->id == 1){
        $data['bank_details'] = get_payment_option('Bank Details')->payment_instructions;
        $data['bank_details_usd'] = get_payment_option('Bank Details USD')->payment_instructions;
    }
    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => $reseller->company.' | '.$doc->doctype.' #'.$doc->id,
        'footer-right' => $doc->docdate.' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];

    if ($return_html) {
        $data['return_html'] = $return_html;
        return view('__app.components.pdfs.doc', $data);
    }


    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.doc', $data);
    // $pdf->setLogger(Log::channel('default'));
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);
    return $pdf;
}

function servicedocument_pdf($id)
{
    $table = 'crm_documents';
    $lines_table = 'crm_document_lines';

    $doc = \DB::table($table)->where('id', $id)->get()->first();
    if (!$doc || empty($doc)) {
        return false;
    }

    $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $doc->doctype)->pluck('doctype_label')->first();
    if (empty($doctype_label)) {
        $doctype_label = $doc->doctype;
    }
    $doclines = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->select('cdl.*', 'cp.code', 'cp.name', 'cp.is_subscription', 'cp.product_category_id', 'cp.sort_order')
        ->where('cdl.document_id', $doc->id)
        ->get();

    $doclines = sort_product_rows($doclines);

    $account = dbgetaccount($doc->reseller_user);
    $reseller = dbgetaccount($account->partner_id);
    $data = [
        'doctype_label' => $doctype_label,
        'reseller_user' => true,
        'doc' => $doc,
        'doclines' => $doclines,
        'account' => $account,
        'reseller' => $reseller,
    ];

    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }
    $data['is_product'] = \DB::table($lines_table.' as cdl')
        ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
        ->where('cdl.document_id', $doc->id)
        ->where('cp.type', 'Stock')
        ->count();


    $data['currency_symbol'] = get_account_currency_symbol($account->id);


    $due_date = date('Y/m/d',strtotime($doc->docdate.' + 7 days'));
   
    if($account->payment_type == 'Postpaid30Days'  || $account->payment_type == 'Internal'){
        $due_date = date('Y/m/d',strtotime($doc->docdate.' + 30 days'));
    }
    
    $data['due_date'] = $due_date;
    

    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => $reseller->company.' | '.$doc->doctype.' #'.$doc->id,
        'footer-right' => $doc->docdate.' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];
    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.servicedoc', $data);
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);

    return $pdf;
}

function statement_zar_pdf($account_id, $full_statement = false, $is_supplier = false, $conn = false)
{
    if ($conn) {
        $current_conn = \DB::getDefaultConnection();
        set_db_connection($conn);
    }

    if ($is_supplier) {
        $account = dbgetsupplier($account_id);
    } else {
        $account = dbgetaccount($account_id);
    }

    if (empty($account)) {
        return false;
    }

    if ($is_supplier) {
        $lines = get_creditor_transactions($account_id);
    } else {
        $lines = get_debtor_transactions($account_id);
    }

    $doclines = [];
    $opening_balnce = 0;
    foreach ($lines as $line) {
        $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $line->doctype)->pluck('doctype_label')->first();
        if (empty($doctype_label)) {
            $doctype_label = $line->doctype;
        }
        $line->doctype_label = $doctype_label;
       
       
        if (!$is_supplier && 'reseller' == $account->type) {
            $reseller_user = \DB::table('crm_accounts')
                ->join('crm_documents', 'crm_documents.reseller_user', '=', 'crm_accounts.id')
                ->where('crm_documents.id', $line->id)
                ->pluck('company')->first();
            if ($reseller_user) {
                $line->reseller_user = $reseller_user;
            }
        }

        // remove exact credit notes
        /*
        if (!empty($line->reversal_id)) {
            if ($is_supplier) {
                $match = \DB::table('crm_supplier_documents')->where('id', $line->reversal_id)->where('total', abs($line->total))->count();
            } else {
                $match = \DB::table('crm_documents')->where('id', $line->reversal_id)->where('total', abs($line->total))->count();
            }
            if ($match) {
                continue;
            }
        }
        */
        //add statement lines
        if ($full_statement || (!$full_statement && date('Y-m-d', strtotime($line->docdate)) >= date('Y-m-d', strtotime('-90 days')))) {
            $doclines[] = $line;
        } elseif (!$full_statement) {
            $opening_balance += $line->total;
        }
    }

    if ($is_supplier) {
        $aging = build_aging($account_id, 'supplier');
    } else {
        $aging = build_aging($account_id);
    }

    if ($is_supplier) {
        $reseller = dbgetaccount(1);
    } else {
        $reseller = dbgetaccount($account->partner_id);
    }
    $data = [
        'account' => $account,
        'reseller' => $reseller,
        'doclines' => $doclines,
        'aging' => $aging,
        'opening_balance' => $opening_balance,
        'full_statement' => $full_statement,
        'is_supplier' => $is_supplier,
    ];


    $data['currency_symbol'] = 'R';


    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }

    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => 'Statement | '.$account->company,
        'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];
    if (!$is_supplier) {
        if (1 == $account->partner_id) {
            $balance = $account->balance;
            $amount = ($balance < 200) ? 200 : $balance;
        } 
    }

    if($reseller->id == 1){
        $data['bank_details'] = get_payment_option('Bank Details')->payment_instructions;
        $data['bank_details_usd'] = get_payment_option('Bank Details USD')->payment_instructions;
    }
    if ($return_html) {
        $data['return_html'] = $return_html;
        return view('__app.components.pdfs.statement', $data);
    }
    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.statement', $data);
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);


    if ($conn) {
        set_db_connection($current_conn);
    }
    return $pdf;
}

function statement_pdf($account_id, $full_statement = false, $is_supplier = false, $include_reversals = true)
{
   

    if ($is_supplier) {
        $account = dbgetsupplier($account_id);
    } else {
        $account = dbgetaccount($account_id);
    }

    if (empty($account)) {
       
        return false;
    }

    if ($is_supplier) {
        $lines = get_creditor_transactions($account_id);
    } else {
        $lines = get_debtor_transactions($account_id);
    }


    $doclines = [];
    $opening_balnce = 0;
    if(!$include_reversals){
        $lines = collect($lines);
        $skip_line_ids = [];
        foreach($lines as $l){
            if($l->reversal_id && !in_array($l->reversal_id,$skip_line_ids)){
                $reversal = $lines->where('id',$l->reversal_id)->first();
                if($reversal && abs(intval($reversal->total))==abs(intval($l->total))){
                    $skip_line_ids[] = $l->id;
                    $skip_line_ids[] = $l->reversal_id;
                }
            }    
        }
    }
   
    foreach ($lines as $line) {
        
        if(!$include_reversals && count($skip_line_ids) > 0){
            if(in_array($line->id,$skip_line_ids)){
                continue;
            }
        }
        
        $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $line->doctype)->pluck('doctype_label')->first();
        if (empty($doctype_label)) {
            $doctype_label = $line->doctype;
        }
        $line->doctype_label = $doctype_label;
      
      
        if (!$is_supplier && 'reseller' == $account->type) {
            $reseller_user = \DB::table('crm_accounts')
                ->join('crm_documents', 'crm_documents.reseller_user', '=', 'crm_accounts.id')
                ->where('crm_documents.id', $line->id)
                ->pluck('company')->first();
            if ($reseller_user) {
                $line->reseller_user = $reseller_user;
            }
        }  
     
        
        if ($line->doctype == 'Payment' || $line->doctype == 'Cashbook Supplier Payment' || $line->doctype == 'Cashbook Customer Receipt') {
            $cashbook_name = \DB::table('acc_cashbook_transactions')
            ->join('acc_cashbook','acc_cashbook_transactions.cashbook_id','=','acc_cashbook.id')
            ->where('acc_cashbook_transactions.id',$line->id)
            ->pluck('acc_cashbook.name')->first();
           
            if (!empty($cashbook_name)) {
                $line->reference .= ' - '.$cashbook_name;
            }
        }
      

        // remove exact credit notes
        /*
        if (!empty($line->reversal_id)) {
            if ($is_supplier) {
                $match = \DB::table('crm_supplier_documents')->where('id', $line->reversal_id)->where('total', abs($line->total))->count();
            } else {
                $match = \DB::table('crm_documents')->where('id', $line->reversal_id)->where('total', abs($line->total))->count();
            }
            if ($match) {
                continue;
            }
        }
        */
        //add statement lines
        if ($full_statement || (!$full_statement && date('Y-m-d', strtotime($line->docdate)) >= date('Y-m-d', strtotime('-90 days')))) {
            $doclines[] = $line;
        } elseif (!$full_statement) {
            $opening_balance += $line->total;
        }
    }

    if ($is_supplier) {
        $aging = build_aging($account_id, 'supplier');
        $closing_balance = $aging['balance'];
    } else {
        $aging = build_aging($account_id);
        $closing_balance = account_get_full_balance($account_id);
    }

    if ($is_supplier) {
        $reseller = dbgetaccount(1);
    } else {
        $reseller = dbgetaccount($account->partner_id);
    }
  
    
    $data = [
        'include_reversals' => $include_reversals,
        'account' => $account,
        'reseller' => $reseller,
        'doclines' => $doclines,
        'aging' => $aging,
        'opening_balance' => $opening_balance,
        'closing_balance' => $closing_balance,
        'full_statement' => $full_statement,
        'is_supplier' => $is_supplier,
    ];
    
    $due_date = date('Y-m-d', strtotime('-'.$account->aging.' days'));
    if($account->payment_type == 'Prepaid'){
        $num_due_days = 10 - $account->aging;
    }
    if($account->payment_type == 'Postpaid30Days'){
        $num_due_days = 30 - $account->aging;
    }
  
    if($account->payment_type == 'Internal'){
        $num_due_days = 0 - $account->aging;
    }
    if($num_due_days > 0){
        $due_date = date('Y-m-d',strtotime('+ '.$num_due_days.' days'));
    }elseif($num_due_days < 0){
        $due_date = date('Y-m-d');
    }
    
   
   
    $data['due_date'] = $due_date;
    if ($is_supplier) {
        $data['currency_symbol'] = get_supplier_currency_symbol($account->id);
        $currency = get_supplier_currency($account->id);
    } else {
        $data['currency_symbol'] = get_account_currency_symbol($account->id);
        $currency = get_account_currency($account->id);
    }


    $fmt = new NumberFormatter("en-us@currency=$currency", NumberFormatter::CURRENCY);
    $data['currency_symbol'] = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

    if ('ZAR' == $data['currency_symbol']) {
        $data['currency_symbol'] = 'R';
    }



    $data['currency_decimals'] = 2;


    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }

    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => 'Statement | '.$account->company,
        'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];

    if (!$is_supplier) {
        if (1 == $account->partner_id) {
            $balance = $account->balance;
            $amount = ($balance < 200) ? 200 : $balance;
        } 
    }
    
    if($reseller->id == 1){
        $data['bank_details'] = get_payment_option('Bank Details')->payment_instructions;
        $data['bank_details_usd'] = get_payment_option('Bank Details USD')->payment_instructions;
    }
    // aa($return_html);
    // aa($data);
    if ($return_html) {
        $data['return_html'] = $return_html;
        return view('__app.components.pdfs.statement', $data);
    }
    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.statement', $data);
    $pdf->setLogger(Log::channel('default'));
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);

    return $pdf;
}


function statement_pdfhtml($account_id)
{
    if ($conn) {
        $current_conn = \DB::getDefaultConnection();
        set_db_connection($conn);
    }

    if ($is_supplier) {
        $account = dbgetsupplier($account_id);
    } else {
        $account = dbgetaccount($account_id);
    }

    if (empty($account)) {
        return false;
    }

    if ($is_supplier) {
        $lines = get_creditor_transactions($account_id);
    } else {
        $lines = get_debtor_transactions($account_id);
    }

    $doclines = [];
    $opening_balnce = 0;
    foreach ($lines as $line) {
        $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $line->doctype)->pluck('doctype_label')->first();
        if (empty($doctype_label)) {
            $doctype_label = $line->doctype;
        }
        $line->doctype_label = $doctype_label;
       
      
        if (!$is_supplier && 'reseller' == $account->type) {
            $reseller_user = \DB::table('crm_accounts')
                ->join('crm_documents', 'crm_documents.reseller_user', '=', 'crm_accounts.id')
                ->where('crm_documents.id', $line->id)
                ->pluck('company')->first();
            if ($reseller_user) {
                $line->reseller_user = $reseller_user;
            }
        }

        // remove exact credit notes
        /*
        if (!empty($line->reversal_id)) {
            if ($is_supplier) {
                $match = \DB::table('crm_supplier_documents')->where('id', $line->reversal_id)->where('total', abs($line->total))->count();
            } else {
                $match = \DB::table('crm_documents')->where('id', $line->reversal_id)->where('total', abs($line->total))->count();
            }
            if ($match) {
                continue;
            }
        }
        */
        //add statement lines
        if ($full_statement || (!$full_statement && date('Y-m-d', strtotime($line->docdate)) >= date('Y-m-d', strtotime('-90 days')))) {
            $doclines[] = $line;
        } elseif (!$full_statement) {
            $opening_balance += $line->total;
        }
    }

    if ($is_supplier) {
        $aging = build_aging($account_id, 'supplier');
    } else {
        $aging = build_aging($account_id);
    }

    if ($is_supplier) {
        $reseller = dbgetaccount(1);
    } else {
        $reseller = dbgetaccount($account->partner_id);
    }
    $data = [
        'account' => $account,
        'reseller' => $reseller,
        'doclines' => $doclines,
        'aging' => $aging,
        'opening_balance' => $opening_balance,
        'full_statement' => $full_statement,
        'is_supplier' => $is_supplier,
    ];
    if ($is_supplier) {
        $data['currency_symbol'] = get_supplier_currency_symbol($account->id);
    } else {
        $data['currency_symbol'] = get_account_currency_symbol($account->id);
    }

    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }

    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => 'Statement | '.$account->company,
        'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];

    if (1 == $account->partner_id) {
        $balance = $account->balance;
        $amount = ($balance < 200) ? 200 : $balance;
    } 
    
    if($reseller->id == 1){
        $data['bank_details'] = get_payment_option('Bank Details')->payment_instructions;
        $data['bank_details_usd'] = get_payment_option('Bank Details USD')->payment_instructions;
    }
    $data['is_view'] = 1;

    return view('__app.components.pdfs.statement', $data);
}

function collectionspdf($account_id, $email_id)
{
    $account = dbgetaccount($account_id);
    $reseller = dbgetaccount($account->partner_id);
    if (empty($account)) {
        return false;
    }


    $data = [
        'account' => $account,
        'reseller' => $reseller,
        'company' => $account->company,
        'contact' => $account->contact,
    ];

    $email = \DB::table('crm_email_manager')->where('id', $email_id)->get()->first();
    $text = $email->letter_of_demand;
    $text = str_replace('&gt;', '>', $text);
    $data['pdf_text'] = erp_email_blend($text, $data);
    $data['pdf_title'] = $email->name;

    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }

    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => 'Letter of Demand | '.$account->company,
        'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];
    // return view('__app.components.pdfs.letter', $data)->render();

    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.letter', $data);
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);

    return $pdf;
}

function suspension_warning_pdf($account_id, $email_id)
{
    $account = dbgetaccount($account_id);
    $reseller = dbgetaccount($account->partner_id);
    if (empty($account)) {
        return false;
    }

    $bank_details = get_payment_option('Bank Details')->payment_instructions;

    $data = [
        'account' => $account,
        'reseller' => $reseller,
        'company' => $account->company,
        'contact' => $account->contact,
        'bank_details' => $bank_details,
    ];

    $email = \DB::table('crm_email_manager')->where('id', $email_id)->get()->first();
    $text = $email->letter_of_demand;
    $text = str_replace('&gt;', '>', $text);
    $data['pdf_text'] = erp_email_blend($text, $data);
    $data['pdf_title'] = $email->name;

    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }

    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => 'Suspension Warning | '.$account->company,
        'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];
    //return view('__app.components.pdfs.letter', $data)->render();

    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.suspension_warning', $data);
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->setOptions($options);

    return $pdf;
}

