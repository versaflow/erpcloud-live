<?php




function send_user_guide_to_customer($account_id,$guide_id){
    $guide = \DB::table('crm_training_guides')->where('id',$guide_id)->get()->first();
    $data = [
        'internal_function' => 'send_user_guide_to_customer',
        'guide_title' => $guide->name,
        'guide_content' => '<h3>'.$guide->name.'</h3>'.$guide->guide,
    ];
    $result = erp_process_notification($account_id, $data);
}