<?php

function schedule_populate_global_sales_report(){
     if(is_main_instance()){
        \DB::table('crm_global_sales_report')->truncate();
        $db_conns = ['telecloud','eldooffice','moviemagic'];
        $instances = \DB::table('erp_instances')->where('installed',1)->whereIn('db_connection',$db_conns)->get();
        $system_user_id = get_system_user_id();
        $system_users = \DB::connection('system')->table('erp_users')->where('account_id',1)->where('is_deleted',0)->get();
      
        foreach($instances as $instance){
            $conn = $instance->db_connection;
            $conn_users = \DB::connection($conn)->table('erp_users')->where('account_id',1)->where('is_deleted',0)->get();
            $rows = \DB::connection($conn)->select("SELECT 
 crm_document_lines.id,
 crm_document_lines.document_id,
 crm_document_lines.qty,
 crm_document_lines.gp,
 crm_document_lines.gpp,
 crm_document_lines.zar_cost_total,
 crm_document_lines.zar_sale_total,
 crm_documents.docdate,
 crm_documents.doctype,
 crm_documents.reference,
 crm_documents.ad_source,
 crm_documents.salesman_id,
 crm_documents.document_currency, 
 crm_products.code as product_code,
 crm_products.name as product_name,
 crm_products.type as product_type,
 crm_products.cost_price as product_cost_price,
 crm_products.selling_price_incl as product_selling_price,
 crm_product_categories.department as category_department,
 crm_product_categories.name as category_name,
 crm_accounts.company as account_name
FROM crm_document_lines
LEFT JOIN `crm_documents` on `crm_document_lines`.`document_id` = `crm_documents`.`id`
LEFT JOIN `crm_accounts` on `crm_documents`.`account_id` = `crm_accounts`.`id`
LEFT JOIN `crm_products` on `crm_document_lines`.`product_id` = `crm_products`.`id`
LEFT JOIN `crm_product_categories` on `crm_products`.`product_category_id` = `crm_product_categories`.`id`
WHERE (crm_documents.doctype = 'Tax Invoice' || crm_documents.doctype = 'Credit Note')");
            foreach($rows as $row){
                $data = (array) $row;
                unset($data['id']);
                
                $data['instance_id'] = $instance->id;
                
                $username = $conn_users->where('id',$row->salesman_id)->pluck('username')->first();
                $user_id = $system_users->where('username',$username)->plucK('id')->first();
                $data['salesman_id'] = $user_id;
                if(!$data['salesman_id']){
                    $data['salesman_id'] = $system_user_id;
                }
             
                \DB::table('crm_global_sales_report')->insert($data);
            }
        }
    }
}