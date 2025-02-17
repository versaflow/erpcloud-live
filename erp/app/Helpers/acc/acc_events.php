<?php

function schedule_generate_stock_history_current_month(){
    $product_ids = \DB::table('acc_inventory')->where('docdate','LIKE',date('Y-m').'%')->pluck('product_id')->unique()->filter()->toArray();
    foreach($product_ids as $product_id){
        generate_stock_history($product_id);
    }
}

function generate_stock_history_current_month(){
    $product_ids = \DB::table('acc_inventory')->where('docdate','LIKE',date('Y-m').'%')->pluck('product_id')->unique()->filter()->toArray();
    foreach($product_ids as $product_id){
        generate_stock_history($product_id);
    }
}

function generate_stock_history($product_id = false){
  
    if($product_id){
        $stock_products = \DB::connection('default')->table('crm_products')->where('type','Stock')->where('id',$product_id)->pluck('id')->toArray();
    }else{
        $stock_products = \DB::connection('default')->table('crm_products')->where('type','Stock')->pluck('id')->toArray();
    }
  
    foreach($stock_products as $product_id){
        $doc_ids = \DB::connection('default')->table('crm_document_lines')->where('product_id',$product_id)->pluck('document_id')->unique()->filter()->toArray();
        if(count($doc_ids) > 0){
            \DB::connection('default')->table('acc_inventory')->where('document_id','>',0)->whereNotIn('document_id',$doc_ids)->where('product_id',$product_id)->delete();
        }
        $supplier_doc_ids = \DB::connection('default')->table('crm_supplier_document_lines')->where('product_id',$product_id)->pluck('document_id')->unique()->filter()->toArray();
        if(count($supplier_doc_ids) > 0){
            \DB::connection('default')->table('acc_inventory')->where('supplier_document_id','>',0)->whereNotIn('supplier_document_id',$supplier_doc_ids)->where('product_id',$product_id)->delete();
        }
        
        $qty_on_hand = 0;
        $sql = '(select cd.id, cd.doctype, cd.docdate, cdl.product_id, cdl.qty * -1 as qty_diff, "none" as new_cost from crm_documents cd join crm_document_lines cdl on cd.id = cdl.document_id 
                where cdl.product_id = '.$product_id.' and cd.doctype = "Tax Invoice")
                UNION ALL 
                (select cd.id, cd.doctype, cd.docdate, cdl.product_id, cdl.qty as qty_diff, "none" as new_cost from crm_documents cd join crm_document_lines cdl on cd.id = cdl.document_id 
                where cdl.product_id = '.$product_id.' and cd.doctype = "Credit Note" )
                UNION ALL 
                (select cd.id, doctype, docdate, cdl.product_id, cdl.qty as qty_diff, cdl.price+cdl.shipping_price as new_cost from crm_supplier_documents cd join crm_supplier_document_lines cdl on cd.id = cdl.document_id 
                where cdl.product_id = '.$product_id.' and cd.doctype = "Supplier Invoice" )
                UNION ALL 
                (select cd.id, doctype, docdate, cdl.product_id, cdl.qty * -1 as qty_diff, cdl.price+cdl.shipping_price as new_cost from crm_supplier_documents cd join crm_supplier_document_lines cdl on cd.id = cdl.document_id 
                where cdl.product_id = '.$product_id.' and cd.doctype = "Supplier Debit Note" )
                UNION ALL 
                (select id, "Inventory" as doctype, docdate, product_id, qty_new as qty_diff, cost_new as new_cost from acc_inventory 
                where product_id = '.$product_id.' and supplier_document_id=0 and document_id=0) 
                ORDER BY docdate asc, id asc';
              
        $lines = \DB::connection('default')->select($sql);
        
        // get product bundle activations
        $bundle_lines = [];
        foreach($lines as $i => $line){
            $product = dbgetrow('crm_products', 'id', $line->product_id);
            if($product->is_bundle){
              
                $activation_products = \DB::table('crm_product_bundle_activations')->where('bundle_product_id',$line->product_id)->get();
                foreach($activation_products as $activation_product){
                    $data = $line;
                    $data->product_id = $activation_product->product_id;
                    $data->qty_diff = ($line->qty_diff < 0) ? $activation_product->qty *-1 : $activation_product->qty;
                    $bundle_lines[] = $data;
                }
                unset($lines[$i]);
            }
        }
        
        foreach($bundle_lines as $bundle_line){
            $lines[] = $bundle_line;    
        }
        
        foreach($lines as $line){
             
             
            if($line->doctype == "Inventory"){
                $qty_on_hand = $line->qty_diff;
                continue;    
            }
            $qty_on_hand += $line->qty_diff;
            $data = [
                'docdate' => $line->docdate,
                'qty_new' => $qty_on_hand,  
                'product_id' => $line->product_id,  
                'doctype' => 'Inventory',
            ];
            
            if($line->doctype == "Tax Invoice" || $line->doctype == "Credit Note"){
                \DB::connection('default')->table('acc_inventory')
                ->where('document_id',$line->id)
                ->where('product_id',$line->product_id)
                ->delete();
                $data['document_id'] = $line->id;
                $data['approved'] = 1;
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['created_by'] = get_system_user_id();
                
                \DB::connection('default')->table('acc_inventory')->insert($data);
            }
            if($line->doctype == "Supplier Invoice" || $line->doctype == "Supplier Debit Note"){
                $shipping_invoice = \DB::table('crm_supplier_documents')->where('id',$line->id)->pluck('shipping_invoice')->first();
                if(!$shipping_invoice){
                    \DB::connection('default')->table('acc_inventory')
                    ->where('supplier_document_id',$line->id)
                    ->where('product_id',$line->product_id)
                    ->delete();
                    
                    $data['cost_change'] = $line->new_cost;
                    $data['cost_new'] = $line->new_cost;
                    $data['supplier_document_id'] = $line->id;
                    $data['approved'] = 1;
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $data['created_by'] = get_system_user_id();
                   
                    \DB::connection('default')->table('acc_inventory')->insert($data);
                }
            }
           
        }
        
        rebuild_inventory_totals($product_id);
    }
}

function get_stock_balance_approved($product_id, $docdate = false, $inventory_id = false, $output = 0)
{
    $docdate_filter = '';
    $docdate_filter_inventory = '';
    $inventory_id_filter = '';
    if ($docdate) {
        $docdate_filter = ' and cd.docdate <="'.$docdate.'" ';
        $docdate_filter_inventory = ' and docdate <="'.$docdate.'" ';
    }

    if ($inventory_id) {
        $inventory_id_filter = ' and id !="'.$inventory_id.'"';
    }

    $sql = 'select id, "Inventory" as doctype, docdate, product_id, qty_new, cost_new as new_cost,zero_cost,document_id,supplier_document_id from acc_inventory 
            where product_id = '.$product_id.'  '.$docdate_filter_inventory.' '.$inventory_id_filter.' and approved=1
            ORDER BY docdate asc, id asc';
          
    try {
        $lines = \DB::select($sql);
        
    } catch (\Throwable $ex) {  exception_log($ex);
        print_r($sql);
    }
    // exit;
    foreach ($lines as $line) {
        if ($output) {
        }
     
        if ($line->new_cost != "none") {  // set cost price to latest record on cost_ajustment, stock_adjustment or supplier_documents
            $cost_price = $line->new_cost;
        }
        if ($line->zero_cost) {  // set cost price to latest record on cost_ajustment, stock_adjustment or supplier_documents
            $cost_price = 0;
        }
        
       
        $qty_on_hand = $line->qty_new;
        
    }

    $data['qty_on_hand'] = $qty_on_hand;
    $data['cost_price'] = currency($cost_price);

    if ($output) {
    } else {
        return $data;
    }
}

function get_stock_balance($product_id, $docdate = false, $inventory_id = false, $output = 0)
{
    $docdate_filter = '';
    $docdate_filter_inventory = '';
    $inventory_id_filter = '';
    if ($docdate) {
        $docdate_filter = ' and cd.docdate <="'.$docdate.'" ';
        $docdate_filter_inventory = ' and docdate <="'.$docdate.'" ';
    }

    if ($inventory_id) {
        $inventory_id_filter = ' and id !="'.$inventory_id.'"';
    }

    $sql = 'select id, "Inventory" as doctype, docdate, product_id, qty_new,zero_cost, cost_new as new_cost from acc_inventory 
            where product_id = '.$product_id.'  '.$docdate_filter_inventory.' '.$inventory_id_filter.'
            ORDER BY docdate asc, id asc';
    try {
        $lines = \DB::select($sql);
    } catch (\Throwable $ex) {  exception_log($ex);
        print_r($sql);
    }
    // exit;
    foreach ($lines as $line) {
        if ($output) {
        }
     
        if ($line->new_cost != "none") {  // set cost price to latest record on cost_ajustment, stock_adjustment or supplier_documents
            $cost_price = $line->new_cost;
        }
        if ($line->zero_cost) {  // set cost price to latest record on cost_ajustment, stock_adjustment or supplier_documents
            $cost_price = 0;
        }
        
        if ($line->qty_new != "none") {  // set cost price to latest record on cost_ajustment, stock_adjustment or supplier_documents
            $qty_on_hand = $line->qty_new;
        }
    }

    $data['qty_on_hand'] = $qty_on_hand;
    $data['cost_price'] = currency($cost_price);

    if ($output) {
    } else {
        return $data;
    }
}
function get_stock_balance_history($product_id, $docdate = false, $inventory_id = false, $output = 0)
{
    $docdate_filter = '';
    $docdate_filter_inventory = '';
    $inventory_id_filter = '';
    if ($docdate) {
        $docdate_filter = ' and cd.docdate <="'.$docdate.'" ';
        $docdate_filter_inventory = ' and docdate <="'.$docdate.'" ';
    }

    if ($inventory_id) {
        $inventory_id_filter = ' and id ="'.$inventory_id.'"';
    }

    $sql = 'select id, "Inventory" as doctype, docdate, product_id, qty_change as qty_diff, cost_new as new_cost from acc_inventory 
            where product_id = '.$product_id.'  '.$docdate_filter_inventory.' '.$inventory_id_filter.' 
            and approved=1
            ORDER BY docdate asc, id asc';

    $lines = \DB::select($sql);
    // print_r($sql);
    // exit;
    foreach ($lines as $line) {
        if ($output) {
        }
        $qty_on_hand += $line->qty_diff;
        if ($line->new_cost != "none") {  // set cost price to latest record on cost_ajustment, stock_adjustment or supplier_documents
            $cost_price = $line->new_cost;
        }
    }

    $data['qty_on_hand'] = $qty_on_hand;
    $data['cost_price'] = currency($cost_price);

    if ($output) {
    } else {
        return $data;
    }
}

function get_creditor_transactions_sql()
{
    $sql = "select id, docdate, doctype, total, supplier_id, reference,  reversal_id from
        (select id, docdate, 'Payment' as doctype, total as total, supplier_id, reference, 0 as reversal_id from acc_cashbook_transactions where supplier_id > 0  
        UNION ALL
        select doctable.id, docdate, doctype, total*-1 as total, supplier_id, reference, reversal_id from crm_supplier_documents as doctable
        where  doctype = 'Supplier Debit Note'
        UNION ALL
        select id, docdate, doctype, total as total, supplier_id, reference, reversal_id from crm_supplier_documents 
        where  doctype = 'Supplier Invoice'
        UNION ALL
        select aj.id, ajt.docdate, ajt.doctype, debit_amount as total, supplier_id, reference, 0 as reversal_id from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id  
        where debit_amount > 0 and ledger_account_id=6
        UNION ALL
        select aj.id, ajt.docdate, ajt.doctype, credit_amount *-1 as total, supplier_id, reference, 0 as reversal_id from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id  
        where credit_amount > 0 and ledger_account_id=6
          ) crm_documents order by docdate";

    return $sql;
}

function get_creditor_transactions($supplier_id)
{
    $supplier_currency = \DB::table('crm_suppliers')->where('id',$supplier_id)->pluck('currency')->first();
    $sql = "select id, docdate, doctype, total, supplier_id, reference,  reversal_id, document_currency from
        (select id, docdate, 'Payment' as doctype, total as total, supplier_id, reference, 0 as reversal_id, document_currency from acc_cashbook_transactions 
        where supplier_id = '".$supplier_id."'  and approved = 1
        UNION ALL
        select doctable.id, docdate, doctype, total*-1 as total, supplier_id, reference, reversal_id, document_currency from crm_supplier_documents as doctable
        where doctable.supplier_id = '".$supplier_id."' and doctype = 'Supplier Debit Note'
        UNION ALL
        select id, docdate, doctype, total as total, supplier_id, reference, reversal_id, document_currency from crm_supplier_documents 
        where supplier_id = '".$supplier_id."' and doctype = 'Supplier Invoice'
        UNION ALL
        select aj.id, ajt.docdate, ajt.doctype, debit_amount  as total, supplier_id, reference, 0 as reversal_id,  document_currency from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
        where supplier_id = '".$supplier_id."' and debit_amount > 0 and ledger_account_id=6
        UNION ALL
        select aj.id, ajt.docdate, ajt.doctype, credit_amount*-1  as total, supplier_id, reference, 0 as reversal_id,  document_currency from acc_general_journals aj join acc_general_journal_transactions ajt on aj.transaction_id=ajt.id 
        where supplier_id = '".$supplier_id."' and credit_amount > 0 and ledger_account_id=6) 
        crm_documents order by docdate";

    $rows = \DB::select($sql);
       
    foreach($rows as $i => $row){
       
        if($row->document_currency != $supplier_currency){
           
            $converted_total = convert_currency(abs($row->total), $row->document_currency, $supplier_currency, $row->docdate); 
          
            $rows[$i]->total = ($row->total < 0) ? $converted_total*-1 : $converted_total;
           
        }
    }
    
    return $rows;
}

function get_creditor_balance($supplier_id = 0, $include_credit = true)
{
    $balance = 0;
    $rows = get_creditor_transactions($supplier_id);

    if ($rows) {
        foreach ($rows as $row) {
            $balance += $row->total;
        }
    }

    return currency($balance);
}

function get_creditor_aging($account_id)
{
    $aging = 0;
    \DB::connection('default')->table('crm_supplier_documents')
        ->where('doctype', 'Supplier Invoice')
        ->where('supplier_id', $account_id)
        ->update(['payment_status' => 'Pending']);
    \DB::connection('default')->table('crm_supplier_documents')
        ->where('doctype', 'Supplier Debit Note')
        ->where('supplier_id', $account_id)
        ->update(['payment_status' => 'Complete']);
   
    $payments_total = \DB::connection('default')->table('acc_cashbook_transactions')
        ->where('supplier_id', $account_id)
        ->where('approved',1)
        ->where('api_status','!=','Invalid')
        ->sum('total');
    
    $credit_total = \DB::connection('default')->table('crm_supplier_documents')
        ->where('supplier_id', $account_id)
        ->where('doctype', 'Supplier Debit Note')
        ->sum('total');
    
    $tax_invoices = \DB::connection('default')->table('crm_supplier_documents')
        ->select('id', 'total')
        ->where('supplier_id', $account_id)
        ->where('doctype', 'Supplier Invoice')
        ->orderby('docdate')->orderby('total')->get();
 
        
    $journals_debit_total = \DB::connection('default')->table('acc_general_journals')
        ->join('acc_general_journal_transactions','acc_general_journal_transactions.id','=','acc_general_journals.transaction_id')
        ->where('acc_general_journals.supplier_id', $account_id)
        ->where('acc_general_journal_transactions.approved', 1)
        ->where('acc_general_journals.ledger_account_id',6)
        ->sum('acc_general_journals.debit_amount');
        
    $journals_credit_total = \DB::connection('default')->table('acc_general_journals')
        ->join('acc_general_journal_transactions','acc_general_journal_transactions.id','=','acc_general_journals.transaction_id')
        ->where('acc_general_journals.supplier_id', $account_id)
        ->where('acc_general_journal_transactions.approved', 1)
        ->where('acc_general_journals.ledger_account_id',6)
        ->sum('acc_general_journals.credit_amount');
        
    $journals_total = $journals_credit_total - $journals_debit_total;
    
    $balance = $payments_total*-1 + $credit_total + $journals_total;
    /*
    $data['balance'] = get_creditor_balance($account_id);
    
    aa($data['balance']);
    aa($balance);
    $documents_total = \DB::connection('default')->table('crm_supplier_documents')
    ->select('id', 'total')
    ->where('supplier_id', $account_id)
    ->where('doctype', 'Supplier Invoice')
    ->sum('total');
    aa($documents_total);
    $calc_balance = $balance - $documents_total;
    aa($calc_balance);
    */
    if (!empty($tax_invoices)) {
        foreach ($tax_invoices as $doc) {
            $balance -= $doc->total;
               
            if ($balance >= -5 || 0 == $doc->total) {
                \DB::connection('default')->table('crm_supplier_documents')->where('id', $doc->id)->update(['payment_status' => 'Complete']);
            }
        }
    }
   
   


    $data = build_aging($account_id, 'supplier');
    //aa($data);
    $aging = 0;
    if (!empty($data) && is_array($data)) {
        if ($data['balance'] > 1) {
            $aging_date = \DB::connection('default')->table('crm_supplier_documents')
                ->where('doctype', 'Supplier Invoice')
                ->where('payment_status', 'Pending')
                ->where('supplier_id', $account_id)
                ->where('docdate', '<=', date('Y-m-d'))
                ->orderby('docdate')->pluck('docdate')->first();
                //aa($aging_date);
            if (!empty($aging_date)) {
                $date = Carbon\Carbon::parse($aging_date);
                $now = Carbon\Carbon::today();

                $aging = $date->diffInDays($now);
            }
        }
    }
    return $aging;
}

function update_stock_balance_lines($id, $supplier = false)
{
    if ($supplier) {
        $document_lines = \DB::table('crm_supplier_document_lines')->where('document_id', $id)->get();
    } else {
        $document_lines = \DB::table('crm_document_lines')->where('document_id', $id)->get();
    }

    if (!empty($document_lines)) {
        foreach ($document_lines as $line) {
            update_stock_balance($line->product_id);
        }
    }
}

//// build_aging
function account_get_full_balance($account_id){
    $lines = get_debtor_transactions($account_id);
    $balance = 0;

    foreach ($lines as $line) {
       
    $balance += $line->total;
          
    }
    return $balance;
}

function build_aging($account_id, $type = '')
{
    if ('supplier' == $type) {
        $lines = get_creditor_transactions($account_id);
    } else {
        $lines = get_debtor_transactions($account_id);
    }
   

    $data['days120'] = 0;
    $data['days90'] = 0;
    $data['days60'] = 0;
    $data['days30'] = 0;
    $data['current'] = 0;
    $data['balance'] = 0;

    foreach ($lines as $line) {
        if($line->docdate <= date('Y-m-d')){
            $data['balance'] += $line->total;
            $data['balance'] = currency($data['balance']);
        }
    }


    foreach ($lines as $line) {
        if ($line->total > 0) {
            if (date('Y-m-d', strtotime($line->docdate)) <= date('Y-m-d', strtotime('-120 days'))) {
                $data['days120'] += $line->total;
            } elseif (date('Y-m-d', strtotime($line->docdate)) <= date('Y-m-d', strtotime('-90 days')) && date('Y-m-d', strtotime($line->docdate)) > date('Y-m-d', strtotime('-120 days'))) {
                $data['days90'] += $line->total;
            } elseif (date('Y-m-d', strtotime($line->docdate)) <= date('Y-m-d', strtotime('-60 days')) && date('Y-m-d', strtotime($line->docdate)) > date('Y-m-d', strtotime('-90 days'))) {
                $data['days60'] += $line->total;
            } elseif (date('Y-m-d', strtotime($line->docdate)) <= date('Y-m-d', strtotime('-30 days')) && date('Y-m-d', strtotime($line->docdate)) > date('Y-m-d', strtotime('-60 days'))) {
                $data['days30'] += $line->total;
            } elseif (date('Y-m-d', strtotime($line->docdate)) > date('Y-m-d', strtotime('-30 days'))) {
                $data['current'] += $line->total;
            }
        }
    }

    if ($balance - $data['days120']  < 0) {
        $balance = $balance - $data['days120'];
        $data['days120'] = 0;
    } else {
        $data['days120'] = $balance - $data['days120'];
    }

    if ($balance - $data['days90']  < 0) {
        $balance = $balance - $data['days90'];
        $data['days90'] = 0;
    } else {
        $data['days90'] = $balance - $data['days90'];
    }

    if ($balance - $data['days60']  < 0) {
        $balance = $balance - $data['days60'];
        $data['days60'] = 0;
    } else {
        $data['days60'] = $balance - $data['days60'];
    }

    if ($balance - $data['days30']  < 0) {
        $balance = $balance - $data['days30'];
        $data['days30'] = 0;
    } else {
        $data['days30'] = $balance - $data['days30'];
    }

    if ($balance - $data['current'] < 0) {
        $balance = $balance - $data['current'];
        $data['current'] = 0;
    } else {
        $data['current'] = $balance - $data['current'];
    }

    return $data;
}
