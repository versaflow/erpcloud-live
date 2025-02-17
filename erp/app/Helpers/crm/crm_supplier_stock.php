<?php

function schedule_update_supplier_stock_availability(){
    
    $stock_product_ids = \DB::table('crm_products')->where('type','Stock')->pluck('id')->toArray();
    
    $rows = \DB::table('crm_document_lines')
    ->select('crm_document_lines.document_id','crm_document_lines.product_id','crm_documents.doctype as document_type',
    'crm_products.supplier_id','crm_products.qty_on_hand',
    'crm_document_lines.qty as qty_ordered')
    ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
    ->join('crm_products','crm_products.id','=','crm_document_lines.product_id')
    ->whereIn('crm_documents.doctype',['Quotation','Order'])
    ->whereIn('crm_products.id',$stock_product_ids)
    ->get();
   
    
    $doc_ids = $rows->pluck('document_id')->unique()->toArray();
    $stock_product_ids = $rows->pluck('product_id')->unique()->toArray();
    \DB::table('crm_supplier_stock')->whereNotIn('document_id',$doc_ids)->update(['is_deleted'=>1]);
    \DB::table('crm_supplier_stock')->whereNotIn('product_id',$stock_product_ids)->update(['is_deleted'=>1]);
    
    foreach($rows as $row){
        
        $w_data = ['document_id'=>$row->document_id,'product_id'=>$row->product_id];
        $data = (array) $row;
        $data['is_deleted'] = 0;
        $exists = \DB::table('crm_supplier_stock')->where($w_data)->count();
        if($exists){
             \DB::table('crm_supplier_stock')->where($w_data)->update($data);
        }else{
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = get_system_user_id();
            \DB::table('crm_supplier_stock')->insert($data);
        }
    }
    
    // update availability
    \DB::table('crm_supplier_stock')->where('is_deleted',0)->update(['total_qty_ordered' => 0,'total_qty_quoted'=>0]);
    \DB::statement("UPDATE crm_supplier_stock AS main
    JOIN (
        SELECT product_id, SUM(qty_ordered) AS total_ordered
        FROM crm_supplier_stock
        WHERE is_deleted = 0 AND document_type='Order'
        GROUP BY product_id
    ) AS sub ON main.product_id = sub.product_id
    SET main.total_qty_ordered = sub.total_ordered
    WHERE main.is_deleted = 0;");
    
    \DB::statement("UPDATE crm_supplier_stock AS main
    JOIN (
        SELECT product_id, SUM(qty_ordered) AS total_ordered
        FROM crm_supplier_stock
        WHERE is_deleted = 0 AND document_type='Quotation'
        GROUP BY product_id
    ) AS sub ON main.product_id = sub.product_id
    SET main.total_qty_quoted = sub.total_ordered
    WHERE main.is_deleted = 0;");
    
    \DB::statement("UPDATE crm_supplier_stock
    SET order_stock_status = 'In Stock'
    WHERE is_deleted = 0 AND total_qty_ordered <= qty_on_hand;");
    
    \DB::statement("UPDATE crm_supplier_stock
    SET order_stock_status = 'Need to order'
    WHERE is_deleted = 0 AND order_stock_status!='Ordered' AND total_qty_ordered > qty_on_hand;");
    
    
    \DB::statement("UPDATE crm_supplier_stock
    SET quote_stock_status = 'In Stock'
    WHERE is_deleted = 0 AND total_qty_quoted <= qty_on_hand;");
    
    \DB::statement("UPDATE crm_supplier_stock
    SET quote_stock_status = 'Need to order'
    WHERE is_deleted = 0 AND quote_stock_status!='Ordered' AND total_qty_quoted > qty_on_hand;");
    
    
}