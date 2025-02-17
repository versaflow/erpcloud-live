<?php

function aftersave_update_store_logos($request)
{
    // if(is_main_instance()){

    //
    //     $instances = \DB::table('erp_instances')->get();

    //     $stores = \DB::table('crm_business_plan')->where('instance_id','>',0)->get();
    //     foreach($stores as $store){
    //         $instance = $instances->where('id',$store->instance_id)->first();
    //         if(!empty($store->logo) && file_exists(uploads_path(1879).$store->logo)){
    //             \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('account_id',1)->update(['logo' => $store->logo]);
    //             File::copy(uploads_path(1879).$store->logo, public_path().'/uploads/'.$instance->db_connection.'/348/'.$store->logo);

    //             \DB::connection('system')->table('erp_instances')->where('id',$store->instance_id)->update(['panel_logo' => $store->logo]);
    //             File::copy(uploads_path(1879).$store->logo, public_path().'/uploads/telecloud/305/'.$store->logo);
    //         }
    //         if(!empty($store->favicon) && file_exists(uploads_path(1879).$store->favicon)){
    //             \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('account_id',1)->update(['favicon' => $store->favicon]);
    //             File::copy(uploads_path(1879).$store->favicon, public_path().'/uploads/'.$instance->db_connection.'/348/'.$store->favicon);
    //         }
    //     }
    // }
}
