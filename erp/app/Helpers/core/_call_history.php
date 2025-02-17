<?php

function schedule_set_call_history_created_by(){
    
    $sql = "UPDATE `erp_call_history`
    INNER JOIN `erp_users`
    ON `erp_call_history`.`extension` = `erp_users`.`pbx_extension`
    SET `erp_call_history`.`created_by` = `erp_users`.`id`
    WHERE
    `erp_call_history`.`extension` > ''
    AND `erp_users`.`account_id` = 1
    AND `erp_users`.`active` = 1
    AND `erp_users`.`is_deleted` = 0;";
    \DB::statement($sql);
    
    $sql = "UPDATE erp_call_history 
    JOIN crm_accounts ON erp_call_history.account_id=crm_accounts.id
    SET erp_call_history.account_salesman_id=crm_accounts.salesman_id";
    \DB::statement($sql);
        
}

function onload_set_call_history_created_by(){
    
    $sql = "UPDATE `erp_call_history`
    INNER JOIN `erp_users`
    ON `erp_call_history`.`extension` = `erp_users`.`pbx_extension`
    SET `erp_call_history`.`created_by` = `erp_users`.`id`
    WHERE
    `erp_call_history`.`extension` > ''
    AND `erp_users`.`account_id` = 1
    AND `erp_users`.`active` = 1
    AND `erp_users`.`is_deleted` = 0;";
    \DB::statement($sql);
    
    $sql = "UPDATE erp_call_history 
    JOIN crm_accounts ON erp_call_history.account_id=crm_accounts.id
    SET erp_call_history.account_salesman_id=crm_accounts.salesman_id";
    \DB::statement($sql);
        
}
function get_cdr_variable($cdr_id,$var){
    $cdr = \DB::connection('pbx_cdr')->table('call_records_outbound_variables')->where('call_records_outbound_id', $cdr_id)->get()->first();
    
    $variables = json_decode($cdr->variables);
    if($variables && $variables->{$var}){
        return $variables->{$var};
    }
    return false;
}