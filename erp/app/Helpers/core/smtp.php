<?php

function schedule_test_smtp(){
    if(is_main_instance()){
        $db_conns = db_conns();
        $smtp_errors = [];
        $instances = \DB::table('erp_instances')->where('installed',1)->where('installed',1)->get();
        foreach($instances as $instance){
           
            $mail_errors = \DB::connection($instance->db_connection)->table('erp_communication_lines')
            ->where('error','like','%stream_socket_client%')
            ->where('created_at','>',date('Y-m-d H:i:s',strtotime('-1 hour')))
            ->count();
            if($mail_errors){
                $smtp_errors[] = $instance->name. ' Mail errors - '.$mail_errors;
            }
            
        }
       
      
        if(count($smtp_errors) > 5){
            queue_sms(12, '0646839468', 'Check smtp settings. '.implode(', ',$smtp_errors), 1, 1);
        }
    }  
}