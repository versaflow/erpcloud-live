<?php

function button_multi_ledger_view_trx($request){
    if(!is_main_instance()){
        return false;
    }
    $trx = \DB::table('acc_multi_ledgers')->where('id',$request->id)->get()->first();
    $doctype_module_table = \DB::table('acc_doctypes')->where('doctype',$trx->doctype)->pluck('doctable')->first();
    $slug = get_menu_url_from_table($doctype_module_table);
    $instance_url = \DB::table('erp_instances')->where('id',$trx->instance_id)->pluck('domain_name')->first();
    return redirect()->to(url('https://'.$instance_url.'/'.$slug.'?id='.$trx->docid));
}

function button_import_multi_ledgers($request){
    if(!is_main_instance()){
        return false;
    }
    \DB::table('acc_multi_ledgers')->truncate();
    \DB::table('acc_multi_ledger_totals')->truncate();
    
    $instance_ids = [1,2,11];
    $instances = \DB::table('erp_instances')->where('installed',1)->whereIn('id',$instance_ids)->get();
    foreach($instances as $instance){
        $db_name = Config::get('database.connections.'.$instance->db_connection.'.database');
        
        $sql = "UPDATE ".$db_name.".acc_ledgers AS l
        JOIN ".$db_name.".acc_ledger_totals AS lt
        ON lt.period = DATE_FORMAT(l.docdate, '%Y-%m') AND lt.ledger_account_id = l.ledger_account_id
        SET l.ledger_total_id = lt.id";
        \DB::connection('core')->statement($sql);
       
        $sql = "INSERT INTO acc_multi_ledgers (docid, docdate, doctype, ledger_account_id, amount, name, reference, account_id, supplier_id, product_id, retained_earnings, ledger_total_id, original_amount, document_currency, doc_no, created_at, updated_at, created_by, updated_by, instance_id)
        SELECT docid, docdate, doctype, ledger_account_id, amount, name, reference, account_id, supplier_id, product_id, retained_earnings, ledger_total_id, original_amount, document_currency, doc_no, created_at, updated_at, created_by, updated_by, ".$instance->id." AS instance_id
        FROM ".$db_name.".acc_ledgers";
        \DB::connection('core')->statement($sql);
        $sql = "INSERT INTO acc_multi_ledger_totals (ledger_account_id, period, total, period_year, target, difference, period_month, target_achieved, ledger_account_category_id, manager_note, running_total, financial_year, period_date, instance_id)
        SELECT ledger_account_id, period, total, period_year, target, difference, period_month, target_achieved, ledger_account_category_id, manager_note, running_total, financial_year, period_date, ".$instance->id." AS instance_id
        FROM ".$db_name.".acc_ledger_totals";
       
        \DB::connection('core')->statement($sql);
        
        
        $sql = "UPDATE acc_multi_ledgers AS l
        JOIN acc_multi_ledger_totals AS lt
        ON lt.period = DATE_FORMAT(l.docdate, '%Y-%m') AND lt.ledger_account_id = l.ledger_account_id  AND lt.instance_id = l.instance_id
        SET l.ledger_total_id = lt.id
        WHERE l.instance_id=".$instance->id;
        \DB::connection('default')->statement($sql);
       
    }
    return json_alert('Done');
}

function schedule_update_multi_ledgers_daily(){
      if(!is_main_instance()){
        return false;
    }
    $periods = \DB::table('acc_periods')->where('period', '<=', date('Y-m'))->orderBy('period', 'desc')->limit(1)->pluck('period')->toArray();
    update_ledger_totals($periods);
    \DB::table('acc_multi_ledgers')->truncate();
    \DB::table('acc_multi_ledger_totals')->truncate();
    
    $instance_ids = [1,2,11];
    $instances = \DB::table('erp_instances')->where('installed',1)->whereIn('id',$instance_ids)->get();
    foreach($instances as $instance){
       
        $db_name = Config::get('database.connections.'.$instance->db_connection.'.database');
        
        $sql = "UPDATE ".$db_name.".acc_ledgers AS l
        JOIN ".$db_name.".acc_ledger_totals AS lt
        ON lt.period = DATE_FORMAT(l.docdate, '%Y-%m') AND lt.ledger_account_id = l.ledger_account_id
        SET l.ledger_total_id = lt.id";
        \DB::connection('core')->statement($sql);
       
        $sql = "INSERT INTO acc_multi_ledgers (docid, docdate, doctype, ledger_account_id, amount, name, reference, account_id, supplier_id, product_id, retained_earnings, ledger_total_id, original_amount, document_currency, doc_no, created_at, updated_at, created_by, updated_by, instance_id)
        SELECT docid, docdate, doctype, ledger_account_id, amount, name, reference, account_id, supplier_id, product_id, retained_earnings, ledger_total_id, original_amount, document_currency, doc_no, created_at, updated_at, created_by, updated_by, ".$instance->id." AS instance_id
        FROM ".$db_name.".acc_ledgers";
        \DB::connection('core')->statement($sql);
        
        $sql = "INSERT INTO acc_multi_ledger_totals (ledger_account_id, period, total, period_year, target, difference, period_month, target_achieved, ledger_account_category_id, manager_note, running_total, financial_year, period_date, instance_id)
        SELECT ledger_account_id, period, total, period_year, target, difference, period_month, target_achieved, ledger_account_category_id, manager_note, running_total, financial_year, period_date, ".$instance->id." AS instance_id
        FROM ".$db_name.".acc_ledger_totals";
       
        \DB::connection('core')->statement($sql);
        
        
        $sql = "UPDATE acc_multi_ledgers AS l
        JOIN acc_multi_ledger_totals AS lt
        ON lt.period = DATE_FORMAT(l.docdate, '%Y-%m') AND lt.ledger_account_id = l.ledger_account_id AND lt.instance_id = l.instance_id
        SET l.ledger_total_id = lt.id
        WHERE l.instance_id=".$instance->id;
        \DB::connection('default')->statement($sql);
    }
}


function schedule_update_multi_debtors_daily(){
      if(!is_main_instance()){
        return false;
    }
    \DB::table('acc_multi_debtors')->truncate();

    $instance_ids = [1,2,11];
    $instances = \DB::table('erp_instances')->where('installed',1)->whereIn('id',$instance_ids)->get();
    foreach($instances as $instance){
       
        \DB::connection($instance->db_connection)->table('crm_accounts')->where('zar_balance','>',0)->where('aging','>',90)->update(['aging_group' => '90+']);
        \DB::connection($instance->db_connection)->table('crm_accounts')->where('zar_balance','>',0)->where('aging','<=',90)->where('aging','>=',61)->update(['aging_group' => '61-90']);
        \DB::connection($instance->db_connection)->table('crm_accounts')->where('zar_balance','>',0)->where('aging','<=',60)->where('aging','>=',31)->update(['aging_group' => '31-60']);
        \DB::connection($instance->db_connection)->table('crm_accounts')->where('zar_balance','>',0)->where('aging','<',31)->update(['aging_group' => '0-30']);
       
       
        $db_name = Config::get('database.connections.'.$instance->db_connection.'.database');
        $sql = "INSERT INTO acc_multi_debtors (account_id,company, account_status, written_off_balance, debtor_status_id, account_balance, account_deleted_at, last_note_date, last_note, is_deleted, aging, aging_group, accountability_match, accountability_current_status_id, currency, account_type, demand_sent, payment_type, has_address, created_at, updated_at, created_by, updated_by, instance_id)
        SELECT 
        crm_accounts.id,
        crm_accounts.company,
        crm_accounts.account_status,
        crm_accounts.written_off_balance,
        crm_accounts.debtor_status_id,
        crm_accounts.balance,
        crm_accounts.deleted_at,
        crm_accounts.last_note_date,
        crm_accounts.last_note,
        crm_accounts.is_deleted,
        crm_accounts.aging,
        crm_accounts.aging_group,
        crm_accounts.accountability_match, 
        crm_accounts.accountability_current_status_id, 
        crm_accounts.currency, 
        crm_accounts.type, 
        crm_accounts.demand_sent, 
        crm_accounts.payment_type, 
        crm_accounts.has_address, 
        crm_accounts.created_at, 
        crm_accounts.updated_at, 
        crm_accounts.created_by, crm_written_off.
        updated_by, 
        ".$instance->id." AS instance_id
        FROM ".$db_name.".crm_written_off JOIN ".$db_name.".crm_accounts ON crm_written_off.account_id=crm_accounts.id";
       
        \DB::connection('core')->statement($sql);
    }
}




    /*
    //COPY LAYOUTS
    \DB::table('erp_grid_views')->where('module_id',1974)->delete();
    $rs = \DB::table('erp_grid_views')->where('module_id',691)->get();
    foreach($rs as $r){
        $d = (array) $r;
        unset($d['id']);
        $d['module_id'] = 1974;
        \DB::table('erp_grid_views')->insert($d);
    }
    \DB::table('erp_grid_views')->where('module_id',1975)->delete();
    $rs = \DB::table('erp_grid_views')->where('module_id',180)->get();
    foreach($rs as $r){
        $d = (array) $r;
        unset($d['id']);
        $d['module_id'] = 1975;
        \DB::table('erp_grid_views')->insert($d);
    }
    \DB::table('erp_grid_views')->whereIn('module_id',[1974,1975])->update(['track_layout'=>0]);
    \DB::table('erp_grid_views')->whereIn('module_id',[1974,1975])->where('is_deleted',1)->delete();
    
    */