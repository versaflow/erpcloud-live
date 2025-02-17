<?php

function onload_business_plans_import_domains(){
    $system_user_id = get_system_user_id();
    $domains = \DB::table('isp_host_websites')->where('account_id',12)->where('status','!=','Deleted')->pluck('domain')->toArray();
   
    foreach($domains as $domain){
        $e = \DB::table('crm_marketing_flow')->where('domain_name',$domain)->count();
        if(!$e){
            $data = [
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $system_user_id,
                'domain_name' => $domain,
            ];
            \DB::table('crm_marketing_flow')->insert($data);
        }
    }
}

function aftersave_business_plans_set_logos(){
    $companies = \DB::table('crm_marketing_flow')->where('creatopy_logo','>','')->where('instance_id','>','')->get();
    $main_instance = \DB::connection('system')->table('erp_instances')->where('installed',1)->where('id',1)->get()->first();
    foreach($companies as $c){
        $instance = \DB::connection('system')->table('erp_instances')->where('installed',1)->where('id',$c->instance_id)->get()->first();
        File::copy(public_path().'/'.$main_instance->db_connection.'/1932/'.$c->creatopy_logo, 
        public_path().'/uploads/'.$instance->db_connection.'/348/'.$c->creatopy_logo);
        \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('account_id',1)->update(['logo'=>$c->creatopy_logo]);
    }
}