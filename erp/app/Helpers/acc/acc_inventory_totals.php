<?php



function schedule_update_inventory_totals(){
    \DB::table('acc_inventory_totals')->truncate();
    $records = \DB::table('crm_products')
    ->select('crm_products.id as product_id','crm_product_categories.id as category_id','crm_products.type as type','qty_on_hand','cost_price','stock_value')
    ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
    ->where('crm_product_categories.is_deleted',0)
    ->where('crm_products.status','!=','Deleted')
    ->get();
    $totals = [];
    $adjustments = \DB::table('acc_inventory')->get();
    foreach($records as $record){
        $data = (array) $record;
        $data['last_update'] = $adjustments->where('product_id',$record->product_id)->sortByDesc('docdate')->pluck('docdate')->first();
        $data['last_manual_update'] = $adjustments->where('product_id',$record->product_id)->where('supplier_document_id',0)->where('document_id',0)->sortBydesc('docdate')->pluck('docdate')->first();
        $data['last_supplier_update'] = $adjustments->where('product_id',$record->product_id)->where('supplier_document_id','>',0)->sortBydesc('docdate')->pluck('docdate')->first();
        $data['last_invoice_update'] = $adjustments->where('product_id',$record->product_id)->where('document_id','>',0)->sortBydesc('docdate')->pluck('docdate')->first();
        $data['supplier_invoice_id'] = $adjustments->where('product_id',$record->product_id)->where('supplier_document_id','>',0)->sortBydesc('docdate')->pluck('supplier_document_id')->first();
        $data['invoice_id'] = $adjustments->where('product_id',$record->product_id)->where('document_id','>',0)->sortBydesc('docdate')->pluck('document_id')->first();

        $totals[] = $data;
    }
    
    \DB::table('acc_inventory_totals')->insert($totals);
}

function update_inventory_totals(){
    \DB::table('acc_inventory_totals')->truncate();
    $records = \DB::table('crm_products')
    ->select('crm_products.id as product_id','crm_product_categories.id as category_id','crm_products.type as type','qty_on_hand','cost_price','stock_value')
    ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
    ->where('crm_product_categories.is_deleted',0)
    ->where('crm_products.status','!=','Deleted')
    ->get();
    $totals = [];
    $adjustments = \DB::table('acc_inventory')->get();
    foreach($records as $record){
        $data = (array) $record;
        $data['last_update'] = $adjustments->where('product_id',$record->product_id)->sortByDesc('docdate')->pluck('docdate')->first();
        $data['last_manual_update'] = $adjustments->where('product_id',$record->product_id)->where('supplier_document_id',0)->where('document_id',0)->sortBydesc('docdate')->pluck('docdate')->first();
        $data['last_supplier_update'] = $adjustments->where('product_id',$record->product_id)->where('supplier_document_id','>',0)->sortBydesc('docdate')->pluck('docdate')->first();
        $data['last_invoice_update'] = $adjustments->where('product_id',$record->product_id)->where('document_id','>',0)->sortBydesc('docdate')->pluck('docdate')->first();
        $data['supplier_invoice_id'] = $adjustments->where('product_id',$record->product_id)->where('supplier_document_id','>',0)->sortBydesc('docdate')->pluck('supplier_document_id')->first();
        $data['invoice_id'] = $adjustments->where('product_id',$record->product_id)->where('document_id','>',0)->sortBydesc('docdate')->pluck('document_id')->first();
        $totals[] = $data;
    }
    
    \DB::table('acc_inventory_totals')->insert($totals);
    
}