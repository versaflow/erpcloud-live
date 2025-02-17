<?php

function schedule_copy_debtors_to_call_center(){
    return false;
    $sql = "UPDATE crm_accounts 
    JOIN crm_commitment_dates ON crm_commitment_dates.account_id=crm_accounts.id
    SET crm_accounts.commitment_date = crm_commitment_dates.commitment_date
    WHERE crm_commitment_dates.expired=0 AND crm_commitment_dates.approved=1  AND crm_commitment_dates.commitment_fulfilled=0";
    \DB::statement($sql);
    
    $processes = \DB::table('crm_staff_tasks')
    ->select('module_id','layout_id')
    ->where('module_id','!=',1923)
    ->where('instance_id',1)
    ->where('role_id',62)
    ->where('is_deleted',0)
    ->get();
    $layout_ids = $processes->pluck('layout_id')->unique()->filter()->toArray();
    $module_ids = $processes->pluck('module_id')->unique()->filter()->toArray();
    $module_fields = \DB::table('erp_module_fields')->whereIn('module_id',$module_ids)->get();
    
    
    $layouts = \DB::table('erp_grid_views')->select('id','module_id')->whereIn('id',$layout_ids)->where('track_layout',1)->get();
    foreach($layouts as $l){
    
        if($l->module_id == 343){  
            $account_field = 'id';
        }else{
            $account_field = 'account_id';
            $fields = $module_fields->where('module_id',$l->module_id)->pluck('field')->toArray();
            if(!in_array('account_id',$fields)){
                continue;
            }
        }
        
        
        
        $sql = get_layout_sql($l->id);
        $rows = \DB::select($sql);
        if(count($rows) == 0){
            
        }
        $rows = collect($rows);
        $row_ids = $rows->pluck($account_field)->toArray();
        \DB::table('crm_opportunities')->where('layout_id',$l->id)->whereNotIn('account_id',$row_ids)->update(['is_deleted' => 1]);
        foreach($rows as $row){
            $e = \DB::table('crm_opportunities')->where('layout_id',$l->id)->where('account_id',$row->{$account_field})->count();
            if(!$e){
                $data = [
                    'account_id' => $row->{$account_field},
                    'layout_id' => $l->id,
                    'status' => 'Layout',
                    'booked_call_date' => $row->commitment_date
                ];
                dbinsert('crm_opportunities',$data);
            }else{
                
                $data = [
                    'account_id' => $row->{$account_field},
                    'layout_id' => $l->id,
                    'booked_call_date' => $row->commitment_date,
                    'is_deleted' => 0,
                ];
                \DB::table('crm_opportunities')->where('layout_id',$l->id)->where('account_id',$row->{$account_field})->update($data);
            }
        }
    }
}


function beforesave_leads_check_commitment_date($request){
    if(!empty($request->status) &&  ($request->status == 'Commitment Date' || $request->status == 'Demo Scheduled') && empty($request->commitment_date)){
        return json_alert('Commitment date required','warning');
    }
}


function aftersave_leads_update_salesman($request){
    $beforesave_row = session('event_db_record');
	if ($beforesave_row->salesman_id != $request->salesman_id) {
	    \DB::table('crm_documents')->where('account_id',$request->account_id)->update(['salesman_id' => $request->salesman_id]);
	    \DB::table('crm_accounts')->where('id',$request->account_id)->update(['salesman_id' => $request->salesman_id]);
	}
}


function afterdelete_accounts_delete_document($request){
    $delete_row = session('event_db_record');
  
    $e = \DB::table('crm_documents')->whereIn('doctype',['Quotation','Order'])->where('id',$delete_row->document_id)->count();
    if($e){
        \DB::table('crm_document_lines')->where('document_id',$delete_row->document_id)->delete();  
        \DB::table('crm_documents')->where('id',$delete_row->document_id)->delete();    
    }
}



function beforesave_opps_check_commitment_date($request){
      
    if(!empty($request->booked_call_date)){
        $date = Carbon\Carbon::parse($request->booked_call_date);
        $now = Carbon\Carbon::today();
        $num_commitment_days = $date->diffInDays($now);
        if($num_commitment_days > 7){
            return 'Commitment date cannot be more than 7 days.';
        }
    }
    if (empty($request->new_record)) {
        $opp = \DB::table('crm_opportunities')->where('id',$request->id)->get()->first();
        if($opp->num_commitments >= 2){
            return 'Max commitments reached, cannot create more than 2 commitments.';
        }
    }
}

function aftersave_opps_create_commitment_dates($request){
    /*
    if(!empty($request->booked_call_date)){
        $beforesave_row = session('event_db_record');
        if($beforesave_row->booked_call_date != $request->booked_call_date){
            \DB::table('crm_opportunities')->where('id',$request->id)->increment('num_commitments');
            // if debtor accountability layout 
            $opp = \DB::table('crm_opportunities')->where('id',$request->id)->get()->first();
          
            if($opp && $opp->layout_id == 3435){
                
                $db = new DBEvent;
                $db->setTable('crm_commitment_dates');
                $account = dbgetaccount($opp->account_id);
                $amount = $account->balance;
                $data = [
                    'commitment_date' => $opp->booked_call_date,
                    'account_id' => $opp->account_id,
                    'amount' => $amount,
                ];
                $result = $db->save($data);
                if (is_array($result) && !empty($result['id'])) {
                    schedule_copy_debtors_to_call_center();
                }else{
                    if ($result instanceof \Illuminate\Http\JsonResponse) {
                        return $result;
                    }else{
                        return 'Commitment not saved.';
                    }
                }
            }
            
        }
    }
    */
}

function button_leads_import_from_accounts($request){
    $accounts = \DB::table('crm_accounts')->where('status','!=','Deleted')->where('type','lead')->get();
    foreach($accounts as $account){
        $e = \DB::table('crm_opportunities')->where('is_deleted',0)->where('account_id',$account->id)->count();
        if(!$e){
            $data = [
                'account_id' => $account->id,
                'status' => 'New Enquiry',
                'email' => $account->email,
                'phone' => $account->phone,
                'account_type' => 'lead',
            ];
            dbinsert('crm_opportunities',$data);
        }
    }
    return json_alert('Done');
}

function schedule_update_oppurtunities(){
    
    /*
    
   $opps = \DB::table('crm_opportunities')->where('salesman_id','!=',5271)->get();
   $ids = $opps->pluck('id')->unique()->filter()->toArray();
   $docids = $opps->pluck('document_id')->unique()->filter()->toArray();
   $account_ids = $opps->pluck('account_id')->unique()->filter()->toArray();
   \DB::table('crm_opportunities')->whereIn('id',$ids)->update(['salesman_id' => 4194]);
   \DB::table('crm_documents')->whereIn('id',$docids)->update(['salesman_id' => 4194]);
   \DB::table('crm_accounts')->whereIn('id',$account_ids)->update(['salesman_id' => 4194]);
    */
    delete_lead_duplicates();
    // update salesman from documents
    $document_ids = \DB::table('crm_opportunities')->where('is_deleted',0)->where('document_id','>',0)->pluck('document_id')->unique()->toArray();
    $salesman_docs = \DB::table('crm_documents')->select('id','salesman_id','account_id','doctype')->whereIn('id',$document_ids)->get();
    foreach($salesman_docs as $salesman_doc){
        \DB::table('crm_opportunities')
        ->where('layout_id',0)
        ->where('document_id',$salesman_doc->id)->update(['doctype'=>$salesman_doc->doctype,'salesman_id'=>$salesman_doc->salesman_id]);
        \DB::table('crm_accounts')->where('id',$salesman_doc->account_id)->update(['salesman_id'=>$salesman_doc->salesman_id]);
    }
    
    
   // \DB::table('crm_opportunities')->truncate();
   
   
    $docs = \DB::table('crm_documents')
    ->select('crm_documents.*','crm_accounts.type as account_type')
    ->join('crm_accounts','crm_accounts.id','=','crm_documents.account_id')
    ->where('crm_accounts.type','!=','reseller')
    ->where('crm_accounts.partner_id',1)
    ->where('billing_type','Monthly')
    ->where('doctype','Quotation')->get();
    $monthly_doc_ids = $docs->pluck('id')->toArray();
     \DB::table('crm_opportunities')->where('document_id','>',0)->whereIn('document_id',$monthly_doc_ids)->delete();
    
    $docs = \DB::table('crm_documents')
    ->select('crm_documents.*','crm_accounts.type as account_type')
    ->join('crm_accounts','crm_accounts.id','=','crm_documents.account_id')
    ->where('crm_accounts.type','!=','reseller')
    ->where('crm_accounts.partner_id',1)
    ->where('billing_type','')
    ->whereIn('doctype',['Quotation','Order'])->get();
    $doc_ids = $docs->pluck('id')->toArray();
    \DB::table('crm_opportunities')->where('document_id','>',0)->whereNotIn('document_id',$doc_ids)->update(['is_deleted'=>1]);
    \DB::table('crm_opportunities')->where('document_id',0)->where('status','Quoted')->where('is_deleted',0)->update(['status'=>'New Enquiry']);
    // update existing opportunities
    \DB::table('crm_opportunities')
    ->where('layout_id',0)
    ->join('crm_accounts','crm_accounts.id','=','crm_opportunities.account_id')
    ->update(['crm_opportunities.account_type'=>\DB::raw('crm_accounts.type')]);
   
    \DB::table('crm_opportunities')
    ->where('layout_id',0)
    ->join('crm_documents','crm_documents.id','=','crm_opportunities.document_id')
    ->update(['crm_opportunities.doctype'=>\DB::raw('crm_documents.doctype'),'crm_opportunities.total'=>\DB::raw('crm_documents.total')]);
    
   
    
    
    // insert new documents
    foreach($docs as $d){
      
        $insert_data = [
            'account_id' => $d->account_id,
            'account_type' => $d->account_type,
            'document_id' => $d->id,
            'total' => $d->total,
            'doctype' => $d->doctype,
            'docdate' => $d->docdate,
            'booked_call_date' => $d->commitment_date,
            'salesman_id' => $d->salesman_id,
            'created_at' => $d->docdate,
            'created_by' => $d->created_by,
        ]; 
        $update_data = [
            'account_id' => $d->account_id,
            'account_type' => $d->account_type,
            'booked_call_date' => $d->commitment_date,
            'document_id' => $d->id,
            'total' => $d->total,
            'doctype' => $d->doctype,
            'docdate' => $d->docdate,
        ]; 
        
        if($d->doctype == 'Quotation'){
            $insert_data['status'] = 'Quoted';
        }
        if($d->doctype == 'Order'){
            $insert_data['status'] = 'Ordered';
        }
        
        if(empty($data['created_by'])){
            $insert_data['created_by'] = get_system_user_id();    
        }
        
      
        $opp_with_doc_id = \DB::table('crm_opportunities')->where('layout_id',0)->where('document_id','>',0)->where('account_id',$d->account_id)->where('is_deleted',0)->count();
        if($opp_with_doc_id > 0){
           
            continue;
        }
        $opp_with_account_id = \DB::table('crm_opportunities')->where('layout_id',0)->where('account_id',$d->account_id)->where('is_deleted',0)->count();
        if($opp_with_account_id > 0){
           \DB::table('crm_opportunities')->where('layout_id',0)->where('account_id',$d->account_id)->where('is_deleted',0)->whereNotIn('status',['Commitment Date','Unqualified','Uncontactable'])->update($update_data);
            continue;
        }
       
        $c = \DB::table('crm_opportunities')->where('layout_id',0)->where('document_id',0)->where('account_id',$d->account_id)->count();
        
        if($c){
          //  \DB::table('crm_opportunities')->where('document_id',0)->where('account_id',$d->account_id)->update($update_data);
        }else{
            $e = \DB::table('crm_opportunities')->where('layout_id',0)->where('document_id',$d->id)->count();
            if(!$e){ 
                \DB::table('crm_opportunities')->insert($insert_data);
            }
        }
    }
    
    
    $account_ids = \DB::table('crm_opportunities')->where('layout_id',0)->where('is_deleted',0)->pluck('account_id')->unique()->toArray();
    foreach($account_ids as $account_id){
        $id = \DB::table('crm_opportunities')->where('layout_id',0)->where('is_deleted',0)->where('account_id',$account_id)->orderBy('last_note_date','desc')->pluck('id')->first();
        \DB::table('crm_opportunities')->where('layout_id',0)->where('account_id',$account_id)->where('is_deleted',0)->where('id','!=',$id)->update(['is_deleted'=>1]);
    }
    
    
    \DB::table('crm_opportunities')->where('doctype','Tax Invoice')->update(['converted_to_invoice'=>1,'is_deleted'=>1]);
    
    // IMPORT LEADS WITHOUT QUOTES
    // remove deleted accounts
    $opp_account_ids =  \DB::table('crm_opportunities')->pluck('account_id')->unique()->filter()->toArray();
    foreach($opp_account_ids as $opp_account_id){
        $e = \DB::table('crm_accounts')->where('id',$opp_account_id)->count();
        if(!$e){
           \DB::table('crm_opportunities')->where('account_id',$opp_account_id)->delete(); 
        }
    }
    
    $leads = \DB::table('crm_accounts')->where('partner_id',1)->where('type','lead')->where('status','!=','Deleted')->get();
    foreach($leads as $lead){
        $exists = \DB::table('crm_opportunities')->where('layout_id',0)->where('account_id',$lead->id)->count();
        if(!$exists){
            $data = [
                'account_id' => $lead->id,
                'account_type' => $lead->type,
                'created_at' => $lead->created_at,
                'created_by' => $lead->created_by,
                'status' => 'New Enquiry',
            ]; 
            if(empty($data['created_by'])){
                $data['created_by'] = get_system_user_id();    
            }
            \DB::table('crm_opportunities')->insert($data);
        }
    }
    
    $sql = "UPDATE crm_accounts 
    JOIN crm_ad_channels ON crm_ad_channels.id=crm_accounts.marketing_channel_id
    SET crm_accounts.source = crm_ad_channels.name
    WHERE crm_accounts.source='' AND crm_accounts.marketing_channel_id > 0";
    \DB::statement($sql);   
    
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.form_name = crm_accounts.form_name,crm_opportunities.ad_form_id = crm_accounts.form_id,crm_opportunities.source = crm_accounts.source,crm_opportunities.phone = crm_accounts.phone,crm_opportunities.email = crm_accounts.email,crm_opportunities.last_call = crm_accounts.last_call";
    \DB::statement($sql);
    
    
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.is_deleted =1 WHERE crm_accounts.status='Deleted'";
    \DB::statement($sql);
    /*
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.created_at =crm_accounts.created_at WHERE crm_opportunities.created_at is null";
    \DB::statement($sql);
    */
    
    // Retrieve all salesman IDs
    // update salesman_id from call logs
    
    /*
    $salesmanIds = get_salesman_user_ids();
    $admin_users = \DB::table('erp_users')->whereIn('id',$salesmanIds)->get();
    $admin_users_exts = $admin_users->pluck('pbx_extension')->toArray();
    \DB::table('crm_opportunities')->where('status','New Enquiry')->update(['salesman_id'=>0]);
    $opps = \DB::table('crm_opportunities')->where('status','New Enquiry')->get();
    foreach($opps as $opp){
        $salesman_id = \DB::table('erp_call_history')->where('account_id',$opp->account_id)->whereIn('extension',$admin_users_exts)->orderBy('id','asc')->pluck('created_by')->first();
        if($salesman_id){
            \DB::table('crm_opportunities')->where('id',$opp->id)->update(['salesman_id'=>$salesman_id]);
            \DB::table('crm_accounts')->where('id',$opp->account_id)->update(['salesman_id'=>$salesman_id]);
        }else{
            \DB::table('crm_accounts')->where('id',$opp->account_id)->update(['salesman_id'=>0]);
        }
    }
    */
  
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.salesman_id = crm_accounts.salesman_id";
    \DB::statement($sql);
    $shopify_account_order_ids = \DB::table('crm_shopify_links')->where('type','order')->pluck('erp_id')->toArray();
    $account_ids = \DB::table('crm_documents')->whereIn('id',$shopify_account_order_ids)->pluck('account_id')->toArray();
    \DB::table('crm_accounts')->whereIn('id',$account_ids)->update(['source'=>'shopify']);
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.source = crm_accounts.source";
    \DB::statement($sql);
    
  
    \DB::table('crm_opportunities')->whereNotIn('status',['Layout','Unqualified','Uncontactable','Commitment Date'])->where('doctype','Quotation')->update(['status'=>'Quoted']);
    \DB::table('crm_opportunities')->whereNotIn('status',['Layout','Unqualified','Uncontactable','Commitment Date'])->where('doctype','Order')->update(['status'=>'Ordered']);
    \DB::table('crm_opportunities')->whereNotIn('status',['Layout','Unqualified','Uncontactable','Commitment Date'])->where('doctype','Tax Invoice')->update(['status'=>'Invoiced','is_deleted'=>1]);
    
    /*
    $doc_ids = \DB::table('crm_opportunities')->whereIn('doctype',['Quotation','Order'])->pluck('document_id')->toArray();
    
    $documents = \DB::table('crm_documents')->whereIn('id',$doc_ids)->where('docdate','<',date('Y-m-d',strtotime('-3 months')))->get();
    foreach($documents as $doc){
        \DB::table('crm_opportunities')->where('document_id',$doc->id)->update(['status'=>'Expired']);
        void_transaction('crm_documents', $doc->id, $doc->doctype);
    }
    */
    $leads = \DB::table('crm_opportunities')->where('layout_id',0)->where('is_deleted',0)->where('account_type','lead')->where('created_at','<',date('Y-m-d',strtotime('-3 months')))->get();
    foreach($leads as $lead){
        \DB::table('crm_opportunities')->where('layout_id',0)->where('id',$lead->id)->update(['is_deleted'=>1]);
        $doc_count = \DB::table('crm_documents')->where('account_id',$lead->account_id)->count();
        if($doc_count == 0){
            \DB::table('crm_accounts')->where('id',$lead->account_id)->where('created_at','<',date('Y-m-d',strtotime('-3 months')))->update(['is_deleted'=>1,'status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s')]);
        }
    }
    
    $leads = \DB::table('crm_opportunities')->where('layout_id',0)->where('is_deleted',0)->whereIn('status',['Layout','Unqualified','Uncontactable'])->get();
    foreach($leads as $lead){
        \DB::table('crm_opportunities')->where('layout_id',0)->where('id',$lead->id)->update(['is_deleted'=>1]);
        $doc_count = \DB::table('crm_documents')->where('account_id',$lead->account_id)->count();
        if($doc_count == 0 && $lead->account_type == 'lead'){
            \DB::table('crm_accounts')->where('id',$lead->account_id)->update(['is_deleted'=>1,'status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s')]);
        }
    }
    
    // assign to sales manager
   // \DB::table('crm_opportunities')->update(['no_note_set' => 1]);
   // \DB::table('crm_opportunities')->where('last_note_date','>',date('Y-m-d',strtotime('-8 days')))->update(['no_note_set' => 0]);
   // $opps = \DB::table('crm_opportunities')->where('last_note_date','<',date('Y-m-d',strtotime('-8 days')))->where('salesman_id','!=',4194)->get();
   // foreach($opps as $opp){
   //     \DB::table('crm_accounts')->where('id',$lead->account_id)->update(['salesman_id'=>4194]);
   //     \DB::table('crm_opportunities')->where('id',$lead->id)->update(['salesman_id'=>4194]);
   // }
    
}


function beforesave_opps_check_status($request){
    $status = $request->status;
    $beforesave_row = session('event_db_record');
    if($status != $beforesave_row->status){
        if($status == 'Expired' || $status == 'Quoted' || $status == 'Ordered' || $status == 'Invoiced'){
            return $status . ' status cannot be selected manually';
        }
    }
}


//Sales - Call scheduled: Booked Call Date: Blank out all past dates daily. After save: Date can only be in the future. 

function beforesave_check_booked_call_date($request){
    if(!empty($request->booked_call_date)){
        if(date('Y-m-d H:i',strtotime($request->booked_call_date)) < date('Y-m-d  H:i')){
            return 'Booked called date cannot be in the past';
        }
    }
}

function schedule_clear_booked_call_date(){
    \DB::table('crm_opportunities')->where('booked_call_date','<',date('Y-m-d'))->update(['booked_call_date'=>null,'status' => 'Expired']);
}

function beforedelete_opps_check_delete_reason($request){
    
    $opp = \DB::table('crm_opportunities')->where('id', $request->id)->get()->first();
    if(empty($opp->delete_reason)){
        return 'Delete reason required';
    }
}


function copy_sales_processes(){
       // copy sales layouts
       /*
    $project_id = 75;
    $copy_project_id = 56;
    
    $processes = \DB::table('crm_staff_tasks')->where('project_id',$project_id)->where('is_deleted',0)->where('layout_id','>',0)->get();
    foreach($processes as $process){
        $layout_id = $process->layout_id;
        $layout = \DB::table('erp_grid_views')->where('id',$layout_id)->get()->first();
        $data = (array) $layout;
        $data['name'] = str_replace(' - Nani','',$layout->name).' - Jibril';
        unset($data['id']);
        $new_id = \DB::table('erp_grid_views')->insertGetId($data);
        $process_data = (array) $process;
        $process_data['layout_id'] = $new_id;
        $process_data['project_id'] = $copy_project_id;
        unset($process_data['id']);
        
        
        \DB::table('crm_staff_tasks')->insert($process_data);
        
    }
    */
}

function button_opportunities_view_ad($request){
    
    $opp = \DB::table('crm_opportunities')->where('id',$request->id)->get()->first();
    if(!$opp->ad_form_id){
        return json_alert('Ad form id not set','warning');
    }
    $ad_id = \DB::table('crm_ad_campaigns')->where('form_id',$opp->ad_form_id)->pluck('id')->first();
    if(!$ad_id){
        return json_alert('Advert not found','warning');
    }
    $url = get_menu_url_from_table('crm_ad_campaigns');
    return redirect()->to($url.'?id='.$ad_id);
}

function beforesave_opps_check_salesman_id($request){
    /*
    $admin = (is_superadmin() || is_manager()) ? true : false;
    if(!$admin){
        if(!empty($request->salesman_id)){
            if(session('user_id')!=$request->salesman_id){
                return 'Only the assigned salesman can edit';
            }
        }
    }
    */
}


function delete_lead_duplicates(){
    $conn = 'default';
    $table = 'crm_accounts';
    $field = 'company';

    $rows = \DB::connection($conn)->select(" SELECT * FROM $table
    WHERE type='lead' and status!='Deleted'
    GROUP BY $field
    HAVING COUNT($field) > 1");
    if(count($rows) > 0){
        foreach ($rows as $row) {
            $dup = \DB::table('crm_accounts')
            ->where('id','!=',$row->id)
            ->where('type','lead')
            ->where('is_deleted',0)
            ->where('company',$row->company)
            ->get()->first();
            if($dup && $row->quote_total > $dup->quote_total){
                \DB::table('crm_accounts')
                ->where('id',$dup->id)
                ->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s'),'is_deleted'=>1]);
                \DB::table('crm_opportunities')->where('account_id',$dup->id)->update(['is_deleted'=>1]);
            }elseif($dup && $row->quote_total < $dup->quote_total){
                \DB::table('crm_accounts')
                ->where('id',$row->id)
                ->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s'),'is_deleted'=>1]);
                \DB::table('crm_opportunities')->where('account_id',$row->id)->update(['is_deleted'=>1]);
            }elseif($dup){
                \DB::table('crm_accounts')
                ->where('id',$row->id)
                ->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s'),'is_deleted'=>1]);
                \DB::table('crm_opportunities')->where('account_id',$row->id)->update(['is_deleted'=>1]);
            }
        }
    }
}

function aftersave_documents_update_opportunities($request){
    
    \DB::table('crm_opportunities')
    ->join('crm_documents','crm_documents.id','=','crm_opportunities.document_id')
    ->update(['crm_opportunities.doctype'=>\DB::raw('crm_documents.doctype'),'crm_opportunities.total'=>\DB::raw('crm_documents.total')]);
    
    $docs = \DB::table('crm_documents')
    ->where('crm_documents.id',$request->id)
    ->select('crm_documents.*','crm_accounts.type as account_type')
    ->join('crm_accounts','crm_accounts.id','=','crm_documents.account_id')
    ->where('crm_accounts.type','!=','reseller')
    ->where('crm_accounts.partner_id',1)
    ->whereIn('doctype',['Quotation','Order'])->get();
    
    // insert new documents
    foreach($docs as $d){
      
        $insert_data = [
            'account_id' => $d->account_id,
            'account_type' => $d->account_type,
            'document_id' => $d->id,
            'total' => $d->total,
            'doctype' => $d->doctype,
            'docdate' => $d->docdate,
            'salesman_id' => $d->salesman_id,
            'created_at' => $d->docdate,
            'created_by' => $d->created_by,
        ]; 
        $update_data = [
            'account_id' => $d->account_id,
            'account_type' => $d->account_type,
            'document_id' => $d->id,
            'total' => $d->total,
            'doctype' => $d->doctype,
            'docdate' => $d->docdate,
        ]; 
        
        if($d->doctype == 'Quotation'){
            $insert_data['status'] = 'Quoted';
        }
        if($d->doctype == 'Order'){
            $insert_data['status'] = 'Ordered';
        }
        
        if(empty($data['created_by'])){
            $insert_data['created_by'] = get_system_user_id();    
        }
        
      
        $opp_with_doc_id = \DB::table('crm_opportunities')->where('document_id','>',0)->where('account_id',$d->account_id)->where('is_deleted',0)->count();
        if($opp_with_doc_id > 0){
           
            continue;
        }
        $opp_with_account_id = \DB::table('crm_opportunities')->where('account_id',$d->account_id)->where('is_deleted',0)->count();
        if($opp_with_account_id > 0){
           \DB::table('crm_opportunities')->where('account_id',$d->account_id)->where('is_deleted',0)->whereNotIn('status',['Layout','Commitment Date','Unqualified','Uncontactable'])->update($update_data);
            continue;
        }
       
        $c = \DB::table('crm_opportunities')->where('document_id',0)->where('account_id',$d->account_id)->count();
        
        if($c){
          //  \DB::table('crm_opportunities')->where('document_id',0)->where('account_id',$d->account_id)->update($update_data);
        }else{
            $e = \DB::table('crm_opportunities')->where('document_id',$d->id)->count();
            if(!$e){ 
                \DB::table('crm_opportunities')->insert($insert_data);
            }
        }
    }
}




function aftersave_set_documents_saleman_id($request){
    
    if(is_superadmin()){
        $beforesave_row = session('event_db_record');
        if($beforesave_row->salesman_id != $request->salesman_id){
            if($beforesave_row->document_id){
                \DB::table('crm_documents')->where('id', $beforesave_row->document_id)->update(['salesman_id'=>$request->salesman_id]);
            }  
            if($beforesave_row->account_id){
                \DB::table('crm_accounts')->where('id', $beforesave_row->account_id)->update(['salesman_id'=>$request->salesman_id]);
            }
        }
    }
    
}

function afterdelete_oppurtunities_delete_lead($request){
  
    if($request->account_type == 'lead'){
        $has_documents = \DB::table('crm_documents')->where('account_id',$request->account_id)->count();
        $opportunity_count = \DB::table('crm_opportunities')->where('account_id',$request->account_id)->count();
        if(!$has_documents && $opportunity_count==1){
            \DB::table('crm_accounts')->where('id',$request->account_id)->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s')]);
        }
    }
}

function button_opportunities_create_quote($request){
  
    
    $url = get_menu_url_from_module_id(353);
    
 
    return Redirect::to($url.'/edit');
}
function button_opportunities_create_quote_row($request){
    $opp = \DB::table('crm_opportunities')->where('id',$request->id)->get()->first();
    
    $url = get_menu_url_from_module_id(353);
    
 
    return Redirect::to($url.'/edit?account_id='.$opp->account_id);
}

function button_opportunities_view_document($request){
    $opp = \DB::table('crm_opportunities')->where('id',$request->id)->get()->first();
    
    $url = get_menu_url_from_module_id(353);
    
 
    return Redirect::to($url.'/view/'.$opp->document_id);
}

function button_opportunities_approve_document($request){
    $opp = \DB::table('crm_opportunities')->where('id',$request->id)->get()->first();
   
   
    
    $id = $opp->document_id;
    $doctype = \DB::table('crm_documents')->where('id',$id)->pluck('doctype')->first();
    $data = ['id' => $id];
    
    $url = get_menu_url_from_module_id(353);
    $url .= '/approve';
  
    $request = Request::create($url,'post',$data); 
    $result = app('App\Http\Controllers\ModuleController')->postApproveTransaction($request);
    return $result;
}


function aftersave_opportunities_email_admin($request){
    $opp = \DB::table('crm_opportunities')->where('id',$request->id)->get()->first();
    
    if (!empty($request->new_record)) {
        if($opp->source == 'Manual'){
            $account = dbgetaccount($opp->account_id);
            $username = \DB::table('erp_users')->where('id',$opp->created_by)->pluck('full_name')->first();
            $data = [
                'lead_msg' => 'New Manual Lead created<br><br>Account: '.$account->company.'<br><br>'.'Created By: '.$username,
                'function_name' => __FUNCTION__,
                'account_id' => 1,
            ];
         
            erp_process_notification(1,$data);
        }
    }
}

function salesman_split_new_inquiries()
{
    // Retrieve all salesman IDs
    $salesmanIds = get_salesman_user_ids();
    // $salesmanIds = [5271];
    $totalSalesmen = count($salesmanIds);
    if (!$totalSalesmen) {
        return false;
    }
    $account_ids = \DB::table('crm_opportunities')->where('status','New Enquiry')->where('is_deleted',0)->pluck('account_id')->unique()->toArray();
   
     
    $index = 0;
    foreach($account_ids as $account_id){
       
        \DB::table('crm_opportunities')->where('account_id',$account_id)->update(['salesman_id' => $salesmanIds[$index]]);
        \DB::table('crm_accounts')->where('id',$account_id)->update(['salesman_id' => $salesmanIds[$index]]);
        $index++;
        if(!isset($salesmanIds[$index])){
            $index = 0;
        }
    }
    
     // update salesman from documents
    $document_ids = \DB::table('crm_opportunities')->where('is_deleted',0)->where('document_id','>',0)->pluck('document_id')->unique()->toArray();
    $salesman_docs = \DB::table('crm_documents')->select('id','salesman_id','account_id')->whereIn('id',$document_ids)->get();
    foreach($salesman_docs as $salesman_doc){
        \DB::table('crm_opportunities')->where('document_id',$salesman_doc->id)->update(['salesman_id'=>$salesman_doc->salesman_id]);
        \DB::table('crm_accounts')->where('id',$salesman_doc->account_id)->update(['salesman_id'=>$salesman_doc->salesman_id]);
    }
    
}

function salesman_split_all_opportunities()
{
    // Retrieve all salesman IDs
    $salesmanIds = get_salesman_user_ids();
   
    // $salesmanIds = [5271];
    $totalSalesmen = count($salesmanIds);
    if (!$totalSalesmen) {
        return false;
    }
    
    // update salesman from documents
    //$document_ids = \DB::table('crm_opportunities')->where('is_deleted',0)->where('document_id','>',0)->pluck('document_id')->unique()->toArray();
    //$salesman_docs = \DB::table('crm_documents')->select('id','salesman_id','account_id')->whereIn('id',$document_ids)->get();
    //foreach($salesman_docs as $salesman_doc){
    //     \DB::table('crm_opportunities')->where('document_id',$salesman_doc->id)->update(['salesman_id'=>$salesman_doc->salesman_id]);
    //    \DB::table('crm_accounts')->where('id',$salesman_doc->account_id)->update(['salesman_id'=>$salesman_doc->salesman_id]);
    //}
    \DB::table('crm_opportunities')->where('is_deleted',0)->update(['salesman_id'=>0]);
    $account_ids = \DB::table('crm_opportunities')->whereNotIn('salesman_id',$salesmanIds)->where('is_deleted',0)->pluck('account_id')->unique()->toArray();
   
     
    $index = 0;
    foreach($account_ids as $account_id){
       
        \DB::table('crm_opportunities')->where('account_id',$account_id)->update(['salesman_id' => $salesmanIds[$index]]);
        \DB::table('crm_accounts')->where('id',$account_id)->update(['salesman_id' => $salesmanIds[$index]]);
        \DB::table('crm_documents')->where('account_id',$account_id)->update(['salesman_id' => $salesmanIds[$index]]);
        $index++;
        if(!isset($salesmanIds[$index])){
            $index = 0;
        }
    }
    
    
}

function button_split_new_inquiries($request){
    if(!is_superadmin()){
        return json_alert('No Access','warning');
    }
    salesman_split_new_inquiries();
    return json_alert('Done');
}

function button_assign_customers_to_salesman($request){
    if(!is_superadmin()){
        return json_alert('No Access','warning');
    }
    salesman_split_all_opportunities();
    return json_alert('Done');
}

function remove_duplicate_notes(){
    return false;
   $sql = "DELETE n1 FROM erp_module_notes n1
INNER JOIN erp_module_notes n2 ON 
    n1.module_id = n2.module_id 
    AND ABS(TIMESTAMPDIFF(SECOND, n1.created_at, n2.created_at)) < 20 
    AND n1.row_id = n2.row_id 
    AND n1.id > n2.id
WHERE n1.module_id = 1923;";
\DB::connection('default')->statement($sql);
}

function set_last_call_from_cdr(){
    return false;
    \DB::table('crm_accounts')->where('last_call','2024-04-18 09:03:26')->update(['last_call' => null]);
      $sql = "UPDATE crm_accounts 
    JOIN crm_ad_channels ON crm_ad_channels.id=crm_accounts.marketing_channel_id
    SET crm_accounts.source = crm_ad_channels.name
    WHERE crm_accounts.source='' AND crm_accounts.marketing_channel_id > 0";
    \DB::statement($sql);   
    
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.form_name = crm_accounts.form_name,crm_opportunities.ad_form_id = crm_accounts.form_id,crm_opportunities.source = crm_accounts.source,crm_opportunities.phone = crm_accounts.phone,crm_opportunities.email = crm_accounts.email,crm_opportunities.last_call = crm_accounts.last_call";
    \DB::statement($sql); 
    
    $rows = \DB::connection('pbx_cdr')->table('call_records_outbound')
        ->select('domain_name', 'start_time','callee_id_number')
        ->where('domain_name','pbx.cloudtools.co.za')
        ->groupBy('callee_id_number')
        ->orderByDesc('id')->get();
    foreach($rows as $r){
        if(empty($r->callee_id_number)){
            continue;
        }
        $n = substr($r->callee_id_number,2);
        $account_ids = \DB::table('crm_accounts')->where('phone','like','%'.$n)->pluck('id')->toArray();
        if($account_ids){
            \DB::table('crm_accounts')->whereIn('id',$account_ids)->whereNull('last_call')->update(['last_call'=>$r->start_time]);
        }
    }
    
    $rows = \DB::connection('pbx_cdr')->table('call_records_outbound_lastmonth')
        ->select('domain_name', 'start_time','callee_id_number')
        ->where('domain_name','pbx.cloudtools.co.za')
        ->groupBy('callee_id_number')
        ->orderByDesc('id')->get();
    foreach($rows as $r){
        if(empty($r->callee_id_number)){
            continue;
        }
        $n = substr($r->callee_id_number,2);
        $account_ids = \DB::table('crm_accounts')->where('phone','like','%'.$n)->pluck('id')->toArray();
        if($account_ids){
            \DB::table('crm_accounts')->whereIn('id',$account_ids)->whereNull('last_call')->update(['last_call'=>$r->start_time]);
        }
    }
    
    $sql = "UPDATE crm_accounts 
    JOIN crm_ad_channels ON crm_ad_channels.id=crm_accounts.marketing_channel_id
    SET crm_accounts.source = crm_ad_channels.name
    WHERE crm_accounts.source='' AND crm_accounts.marketing_channel_id > 0";
    \DB::statement($sql);    
    
    
    $sql = "UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.form_name = crm_accounts.form_name,crm_opportunities.ad_form_id = crm_accounts.form_id,crm_opportunities.source = crm_accounts.source,crm_opportunities.phone = crm_accounts.phone,crm_opportunities.email = crm_accounts.email,crm_opportunities.last_call = crm_accounts.last_call";
    \DB::statement($sql);    

}