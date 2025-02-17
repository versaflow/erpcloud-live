<?php

function add_module_note($module_id,$row_id,$note){
   
    $data = [
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => session('user_id'), 
        'row_id' => $row_id, 
        'module_id' => $module_id, 
        'note' => $note, 
        'is_deleted' => 0,
    ];
  
    \DB::connection('default')->table('erp_module_notes')->insert($data);
    $module = \DB::connection('default')->table('erp_cruds')->where('id',$module_id)->get()->first();
    $modules = \DB::connection('default')->table('erp_cruds')->get();
    $module_fields = \DB::connection('default')->table('erp_module_fields')->where('module_id',$module_id)->pluck('field')->toArray();
   
    $account_id_module_ids = \DB::connection('default')->table('erp_module_fields')->where('field','account_id')->pluck('module_id')->toArray();
    $account_id_module_ids[] = 343;
   
    if(in_array('last_note',$module_fields)){
        //if(in_array('last_note_date',$module_fields)){
        //    \DB::connection($module->connection)->table($module->db_table)->update(['last_note_date'=>null,'last_note' => '']);
        //}else{
        //    \DB::connection($module->connection)->table($module->db_table)->update(['last_note' => '']);
        // }
        
        if(in_array('is_deleted',$module_fields)){
            $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->where('is_deleted',0)->get();
        } elseif(in_array('status',$module_fields)){
            $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->where('status','!=','Deleted')->get();
        }else{
            $records = \DB::connection($module->connection)->table($module->db_table)->select($module->db_key)->get();
        }
        
        foreach($records as $record){
            if(in_array($module_id,$account_id_module_ids)){
                $last_note = \DB::table('erp_module_notes')->whereIn('module_id',$account_id_module_ids)->where('row_id',$record->id)->orderBy('id','desc')->get()->first();
            }else{
                $last_note = \DB::table('erp_module_notes')->where('module_id',$module->id)->where('row_id',$record->id)->orderBy('id','desc')->get()->first();
            }
            
            if($last_note){
                if(in_array($module_id,$account_id_module_ids)){
                    $module_name = $modules->where('id',$last_note->module_id)->pluck('name')->first();
                    $last_note->note = $module_name.': '.$last_note->note;
                }
                if(in_array('last_note_date',$module_fields)){
                   
                    \DB::connection($module->connection)->table($module->db_table)->where($module->db_key,$record->id)->update(['last_note_date' => $last_note->created_at, 'last_note' => date('Y-m-d',strtotime($last_note->created_at)).' '.$last_note->note]);
                }else{
                  
                    \DB::connection($module->connection)->table($module->db_table)->where($module->db_key,$record->id)->update(['last_note' => date('Y-m-d',strtotime($last_note->created_at)).' '.$last_note->note]);
                }
            }
        }
    }
    populate_module_note_details();
}

function populate_module_note_details(){
    $module_ids = \DB::connection('default')->table('erp_module_notes')->pluck('module_id')->unique()->filter()->toArray();
    foreach($module_ids as $module_id){
        
        $c =  \DB::connection('default')->table('erp_cruds')->select('connection')->where('id',$module_id)->pluck('connection')->first();
        if($c!='default'){
            continue;
        }
        $db_table =  \DB::connection('default')->table('erp_cruds')->select('db_table')->where('id',$module_id)->pluck('db_table')->first();
        $module_fields =  \DB::connection('default')->table('erp_module_fields')->select('field','display_field')->where('module_id',$module_id)->get();
        $module_fields_list = $module_fields->pluck('field')->toArray();
        $display_field = $module_fields->where('display_field',1)->pluck('field')->first();
        $name_field = '';
        if(empty($name_field) && in_array('account_id',$module_fields_list)){
            $name_field = $db_table.'.account_id';
        }
        if(empty($name_field) && in_array('name',$module_fields_list)){
            $name_field = $db_table.'.name';
        }
        if(empty($name_field) && in_array('title',$module_fields_list)){
            $name_field = $db_table.'.title';
        }
        
        
        if(empty($name_field) && !empty($display_field)){
            $name_field = $db_table.'.'.$display_field;
        }
        
        if(empty($name_field)){
            continue;
        }
        
        if($name_field == $db_table.'.account_id'){
           $name_field = 'crm_accounts.company';
        }
       
        $sql = "UPDATE erp_module_notes
        JOIN $db_table ON erp_module_notes.row_id = $db_table.id";
        
        
        if($name_field == 'crm_accounts.company' && $db_table!='crm_accounts'){
        $sql .= " JOIN crm_accounts ON $db_table.account_id = crm_accounts.id";
        }
        
        $sql .= " SET ";
        $sql .= "erp_module_notes.row_name = $name_field,";
        $sql .= "erp_module_notes.details = CONCAT(
        'Created day: ', DATE_FORMAT($db_table.created_at, '%Y-%m-%d'),";
        
        if(in_array('total',$module_fields_list)){
        $sql .= "', Total: ', $db_table.total,";
        }
        $sql .= "', Name: ', $name_field
        ) WHERE erp_module_notes.module_id=$module_id";
       
        \DB::connection('default')->statement($sql);
    }
}

function populate_opportunity_note_details(){
    $sql = "UPDATE erp_module_notes
    JOIN crm_opportunities ON erp_module_notes.row_id = crm_opportunities.id
    JOIN crm_accounts ON crm_opportunities.account_id = crm_accounts.id
    SET erp_module_notes.details = CONCAT(
    'Created day: ', DATE_FORMAT(crm_opportunities.created_at, '%Y-%m-%d'), 
    ', Total: ', crm_opportunities.total, 
    ', Name: ', crm_accounts.company
    ) WHERE erp_module_notes.module_id=1923";
    \DB::connection('default')->statement($sql);
}