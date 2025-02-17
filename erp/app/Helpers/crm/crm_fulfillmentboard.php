<?php

function schedule_update_fulfillment_board(){
    return false;
    //$salesman_ids = get_salesman_user_ids();
    
    $salesman_ids = \DB::table('erp_users')->where('account_id',1)->whereIn('username',['jibril','kola','ismail'])->pluck('id')->toArray(); // jibril
  
    $salesman_ids = collect($salesman_ids)->filter()->unique()->toArray();
  
    $field_statuses = \DB::table('erp_module_fields')->where('module_id',554)->where('field','status')->pluck('opts_values')->first();
    $field_statuses = explode(',',$field_statuses);
    $statuses = \DB::table('sub_activations')->select('status')->groupBy('status')->pluck('status')->filter()->unique()->toArray();
    foreach($field_statuses as $fs){
        if(!in_array($fs,$statuses)){
            $statuses[] = $fs;
        }
    }
    \DB::table('crm_fulfillment_board')->whereNotIn('status',$statuses)->update(['is_deleted' => 1]);
    \DB::table('crm_fulfillment_board')->whereNotIn('salesman_id',$salesman_ids)->update(['is_deleted' => 1]);
    
    \DB::table('sub_activations')->update(['fulfillment_board_id'=> 0]);
    foreach($salesman_ids as $salesman_id){
        foreach($statuses as $status){
            if(in_array($status,['Deleted','Credited'])){
                continue;
            }
            $data = [
                'status' => $status,
                'salesman_id' => $salesman_id,
                'is_deleted' => 0,
            ];
            $wdata = [
                'status' => $status,
                'salesman_id' => $salesman_id,
            ];
            $total_rows = \DB::table('sub_activations')->where($wdata)->count();
            $data['total_rows'] = $total_rows;
            \DB::table('crm_fulfillment_board')->updateOrInsert($wdata,$data);
            $fulfillment_board_id = \DB::table('crm_fulfillment_board')->where($wdata)->pluck('id')->first();
           
            \DB::table('sub_activations')->where($wdata)->update(['fulfillment_board_id'=> $fulfillment_board_id]);
        }
    }
}
