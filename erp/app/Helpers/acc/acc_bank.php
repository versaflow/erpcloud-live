<?php

function schedule_remove_account_cancellations(){
    // ACCOUNT CANCEL
    $approvals = \DB::table('crm_approvals')->where('processed',0)->where('module_id',343)->where('title','%Cancel%')->get();
    foreach($approvals as $approval){
        $account_id = $approval->row_id;
        $debtor_process_cancellation = \DB::table('crm_accounts')->where('id',$account_id)->pluck('debtor_process_cancellation')->first();
        if($debtor_process_cancellation){
            $payment_made = \DB::table('acc_cashbook_transactions')->where('account_id',$account_id)->where('docdate','>=',date('Y-m-d',strtotime('-7 days')))->count();
        
            if($payment_made){
                \DB::table('crm_accounts')->where('debtor_process_cancellation',1)->where('id', $account_id)->update(['debtor_process_cancellation'=>0,'cancelled'=>0,'cancel_approved' => 0, 'account_status' => \DB::raw('status'),'cancel_date' => null]);
                \DB::table('crm_approvals')->where('module_id',343)->where('row_id',$account_id)->where('title','%Cancel%')->delete();
            }
        }
    }
}

function remove_account_cancellations($account_id){
    
    $payment_made = \DB::table('acc_cashbook_transactions')->where('account_id',$account_id)->where('docdate','>=',date('Y-m-d',strtotime('-7 days')))->count();
    $debtor_process_cancellation = \DB::table('crm_accounts')->where('id',$account_id)->pluck('debtor_process_cancellation')->first();
       
    if($debtor_process_cancellation && $payment_made){
        \DB::table('crm_accounts')->where('debtor_process_cancellation',1)->where('id', $account_id)->update(['debtor_process_cancellation'=>0,'cancelled'=>0,'cancel_approved' => 0, 'account_status' => \DB::raw('status'),'cancel_date' => null]);
        \DB::table('crm_approvals')->where('module_id',343)->where('row_id',$account_id)->where('title','%Cancel%')->delete();
    }
}

function button_bank_upload_invoice($request)
{
    return view('__app.button_views.bank_invoice_upload', ['id' => $request->id]);
}
function button_bank_upload_statement($request)
{
    return view('__app.button_views.bank_statement_upload', ['id' => $request->id]);
}




function beforesave_general_journal_check_invoice_file($request)
{
    $trx = \DB::table('acc_general_journals')->where('id', $request->id)->get()->first();
    $tax = 0;
    if ($trx->ledger_account_id > 0) {
        $taxable = dbgetcell('acc_ledger_accounts', 'id', $trx->ledger_account_id, 'taxable');
        if ($taxable) {
            if ($trx->credit_amount > 3000) {
                if ((empty($request->invoice_file) || empty($request->file('invoice_file')))) {
                    return json_alert('Invoice File Required', 'error');
                }
            }
        }
    }
}



function button_bank_register_allocate_references($request)
{
    $result = allocate_bank_transactions();

    if ($result === true) {
        return json_alert('Transactions allocated.');
    } else {
        return $result;
    }
}





function beforesave_bank_references_check_unique($request)
{
    if (!empty($request->id)) {
        $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('id', '!=', $request->id)->where('reference', $request->reference)->count();
    } else {
        $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('reference', $request->reference)->count();
    }
    if ($exists) {
        return json_alert('Bank Reference already in use.', 'warning');
    }
    
    if (!empty($request->id)) {
        $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('id', '!=', $request->id)->where('reference', 'LIKE','%'.$request->reference.'%')->count();
    } else {
        $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('reference', 'LIKE','%'.$request->reference.'%')->count();
    }
    if ($exists) {
        return json_alert('Bank Reference cannot be a substring of another referencee.', 'warning');
    }
    
    
    
    $allocated_to = 0;

    if (!empty($request->ledger_account_id)) {
        $allocated_to++;
        // ledger accounts is one to many references
        /*
        if (!empty($request->id)) {
            $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('id', '!=', $request->id)->where('ledger_account_id', $request->ledger_account_id)->count();
        } else {
            $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('ledger_account_id', $request->ledger_account_id)->count();
        }
        if ($exists) {
            return json_alert('Only one reference per account allowed.', 'warning');
        }
        */
    }

    if (!empty($request->account_id)) {
        $allocated_to++;
        if (!empty($request->id)) {
            $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('id', '!=', $request->id)->where('account_id', $request->account_id)->count();
        } else {
            $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('account_id', $request->account_id)->count();
        }
        if ($exists) {
            return json_alert('Only one reference per account allowed.', 'warning');
        }
    }

    if (!empty($request->supplier_id)) {
        $allocated_to++;
        if (!empty($request->id)) {
            $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('id', '!=', $request->id)->where('supplier_id', $request->supplier_id)->count();
        } else {
            $exists = \DB::table('acc_bank_references')->where('is_deleted',0)->where('supplier_id', $request->supplier_id)->count();
        }
        if ($exists) {
            return json_alert('Only one reference per account allowed.', 'warning');
        }
    }

    if ($allocated_to == 0) {
        return json_alert('Select an account to allocate to.', 'warning');
    }

    if ($allocated_to > 1) {
        return json_alert('Cannot allocate to more than one account.', 'warning');
    }
}



function aftersave_allocate_transactions_by_reference($request)
{
    $row = \DB::connection('default')->table('acc_bank_references')->where('id', $request->id)->get()->first();
    $result = allocate_bank_transactions($row->reference);
    if ($result !== true) {
        return $result;
    }
}


function button_register_bank_delete_files($request)
{
    \DB::table('acc_cashbook_transactions')->where('id', $request->id)->update(['statement_file' => null, 'invoice_file' => null]);

    return json_alert('Files removed');
}

function allocate_bank_transactions($reference = false)
{
    /*
    test query rollback
    unallocate 2987
    run sql
    run allocate_bank_transactions
    check rollback
    */

    $cashbook_bank_ids = \DB::table('acc_cashbook')->where('fnb_username', '>', '')->pluck('id')->toArray();
    $non_tax_ledger_accounts = \DB::table('acc_ledger_accounts')->where('taxable', 0)->pluck('id')->toArray();
    \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids)->whereIn('ledger_account_id', $non_tax_ledger_accounts)->update(['tax'=>0]);
   

    $transaction_query = \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids);
    if ($reference) {
        $transaction_query->where('reference', 'like', '%'.$reference.'%');
    }
    $transaction_query->where(function ($transaction_query) {
        $transaction_query->whereNull('account_id');
        $transaction_query->orWhere('account_id', 0);
    });
    $transaction_query->where(function ($transaction_query) {
        $transaction_query->whereNull('supplier_id');
        $transaction_query->orWhere('supplier_id', 0);
    });
    $transaction_query->where(function ($transaction_query) {
        $transaction_query->whereNull('ledger_account_id');
        $transaction_query->orWhere('ledger_account_id', 0);
    });

    $transactions = $transaction_query->get();

  


    $import_references = \DB::table('acc_bank_references')->where('is_deleted',0)->where('reference','not like','%NETCASH%')->where('reference','not like','%PAYFAST%')->whereRaw(\DB::raw('LENGTH(reference) >= 2'))->orderByRaw('LENGTH(reference) DESC')->get();

    $provider_transactions = [];


    foreach ($transactions as $transaction) {
        $import_reference = false;
        $processed = false;
        $bank_id = $transaction->id;



        if ($processed) {
            continue;
        }

        // process auto allocation
        foreach ($import_references as $auto_allocate) {
            if (strtolower($transaction->reference) === strtolower($auto_allocate->reference)) {
                $import_reference = $auto_allocate;
                break;
            }
        }

        if (empty($import_reference)) {
            foreach ($import_references as $auto_allocate) {
                if (str_contains(strtolower($transaction->reference), strtolower($auto_allocate->reference))) {
                    $import_reference = $auto_allocate;
                    break;
                }
            }
        }

        if (!empty($import_reference)) {
            if (!empty($import_reference->ledger_account_id)) {
                $trx = \DB::table('acc_cashbook_transactions')->where('id', $bank_id)->get()->first();
                $ledger_account_id = $import_reference->ledger_account_id;
                if (!empty($import_reference->control_account_id) && is_cashbook_ledger_account($import_reference->control_account_id)) {
                    $trx_data['control_account_id'] = $import_reference->control_account_id;


                    delete_journal_entry_by_cashbook_transaction_id($trx->id);
                    cashbook_control_transfer($import_reference->control_account_id, $trx->total, $trx->docdate, $trx->id);
                }

                $taxable = dbgetcell('acc_ledger_accounts', 'id', $ledger_account_id, 'taxable');

                if ($taxable) {
                    if ($trx->docdate <= '2018-03-31') {
                        $tax = $trx->total * 14 / 114;
                    } else {
                        $tax = $trx->total * 15 / 115;
                    }
                } else {
                    $tax = 0;
                }

                $trx_data = [];
                $trx_data['ledger_account_id'] = $ledger_account_id;
                $trx_data['doctype'] = 'Cashbook Expense';
                $trx_data['tax'] = $tax;
                $trx_data['reference_matched'] = 1;
                $trx_data['allocate_reference'] = $import_reference->reference;
                $trx_data['bank_reference_id'] = $import_reference->id;
                \DB::table('acc_cashbook_transactions')->where('id', $trx->id)->update($trx_data);
            } elseif (!empty($import_reference->account_id)) {
                $trx = \DB::table('acc_cashbook_transactions')->where('id', $bank_id)->get()->first();
                \DB::table('acc_cashbook_transactions')
                    ->where('id', $bank_id)
                    ->update(['doctype' => 'Cashbook Customer Receipt']);
                $account_id = $import_reference->account_id;
              
                $trx_data = ['account_id' => $account_id, 'doctype' => 'Cashbook Customer Receipt'];
                $trx_data['reference_matched'] = 1;
                $trx_data['allocate_reference'] = $import_reference->reference;
                $trx_data['bank_reference_id'] = $import_reference->id;
                \DB::table('acc_cashbook_transactions')
                    ->where('id', $trx->id)
                    ->update($trx_data);
                    
                remove_account_cancellations($account_id);
            } elseif (!empty($import_reference->supplier_id)) {
                $trx = \DB::table('acc_cashbook_transactions')->where('id', $bank_id)->get()->first();
                $supplier_id = $import_reference->supplier_id;
              
                $doctype = 'Cashbook Supplier Payment';

               
                $trx_data = ['supplier_id' => $supplier_id, 'doctype' => $doctype];
                $trx_data['reference_matched'] = 1;
                $trx_data['allocate_reference'] = $import_reference->reference;
                $trx_data['bank_reference_id'] = $import_reference->id;
                \DB::table('acc_cashbook_transactions')
                    ->where('id', $trx->id)
                    ->update($trx_data);
            }
            \DB::table('acc_bank_references')->where('id', $import_reference->id)->update(['last_bank_id' => $bank_id,'last_used' => date('Y-m-d H:i:s')]);
            $admin_subject = 'Reference: '.$trx->reference.' Auto Reference:'.$import_reference->reference;
            $admin_msg = '';
            
            $admin_msg .= 'Reference: '.$trx->reference;
            $admin_msg .= PHP_EOL.'Auto Reference: '.$import_reference->reference;
            $admin_msg .= PHP_EOL.'Total: '.$trx->total;
            $admin_msg .= PHP_EOL.'Docdate: '.$trx->docdate;
                
            if (!empty($import_reference->ledger_account_id)) {
                $admin_msg .= PHP_EOL.'Ledger Account: ';
                $admin_msg .= \DB::table('acc_ledger_accounts')->where('id',$import_reference->ledger_account_id)->pluck('name')->first();
            }elseif (!empty($import_reference->account_id)) {
                $admin_msg .= PHP_EOL.'Customer Account: ';
                $admin_msg .= \DB::table('crm_accounts')->where('id',$import_reference->account_id)->pluck('company')->first();
            }elseif (!empty($import_reference->supplier_id)) {
                $admin_msg .= PHP_EOL.'Supplier Account: ';
                $admin_msg .= \DB::table('crm_suppliers')->where('id',$import_reference->supplier_id)->pluck('company')->first();
            }
            
            $url = get_menu_url_from_table('acc_cashbook_transactions');
            $admin_msg .= PHP_EOL.'<a href="https://'.session('instance')->domain_name.'/'.$url.'?id='.$trx->id.'">View transaction</a>';
            admin_email($admin_subject,$admin_msg);
        }
    }

    $db = new DBEvent();
    $db->setTable('acc_cashbook_transactions');
    foreach ($transactions as $transaction) {
        $trx = \DB::table('acc_cashbook_transactions')->where('id',$transaction->id)->get()->first();
        
        if (!empty($trx->account_id)) {
            \DB::table('crm_documents')->where('account_id',$trx->account_id)->where('total',$trx->total)->where('doctype','Quotation')->update(['doctype' => 'Order']);
            $db->setDebtorBalance($trx->account_id);
        }
        if (!empty($trx->supplier_id)) {
            $db->setCreditorBalance($trx->supplier_id);
        }
        $db->postDocument($transaction->id);
    }
    $db->postDocumentCommit();

  
    

    $cashbooks = \DB::table('acc_cashbook')->where('allow_allocate', 1)->get();
    foreach ($cashbooks as $cashbook) {
        cashbook_reconcile($cashbook->id);
    }

    return true;
}




function aftersave_vat_submissions_calculate_totals($request)
{
    $row = \DB::table('acc_vat_submissions')->where('id', $request->id)->get()->first();
    $standard_rated_output_total = currency($row->standard_rated_output + $row->standard_rated_output_2);
    $zero_rated_output_total = currency($row->zero_rated_output + $row->zero_rated_output_2);

    $input_total = currency($row->journals_input + $row->journals_input_2 + $row->suppliers_input + $row->suppliers_input_2);
    $output_vat = currency(($standard_rated_output_total * 15) /115);
    $input_vat = currency(($input_total* 15) /115);

    $balance = currency($input_vat - $output_vat);
    $data = [
        'zero_rated_output_total' => $zero_rated_output_total,
        'standard_rated_output_total' => $standard_rated_output_total,
        'output_vat' => $output_vat,
        'input_total' => $input_total,
        'input_vat' => $input_vat,
        'balance' => $balance,
    ];

    \DB::table('acc_vat_submissions')->where('id', $request->id)->update($data);
}

function schedule_update_bank_references(){
    
    
    // REMOVE DUPLICATES
    $bank_references = \DB::table('acc_bank_references')->where('account_id','>',0)->groupBy('account_id')->orderBy('last_used','desc')->get();
    foreach($bank_references as $bank_reference){
        \DB::table('acc_bank_references')->where('id','!=',$bank_reference->id)->where('account_id',$bank_reference->account_id)->update(['is_deleted' => 1]);
    }
    $bank_references = \DB::table('acc_bank_references')->where('supplier_id','>',0)->groupBy('supplier_id')->orderBy('last_used','desc')->get();
    foreach($bank_references as $bank_reference){
        \DB::table('acc_bank_references')->where('id','!=',$bank_reference->id)->where('supplier_id',$bank_reference->supplier_id)->update(['is_deleted' => 1]);
    }
    //$bank_references = \DB::table('acc_bank_references')->where('ledger_account_id','>',0)->groupBy('ledger_account_id')->orderBy('last_used','desc')->get();
    //foreach($bank_references as $bank_reference){
    //    \DB::table('acc_bank_references')->where('id','!=',$bank_reference->id)->where('ledger_account_id',$bank_reference->ledger_account_id)->delete();
    //}
    
    \DB::table('acc_bank_references')->where('automated_reference',1)->delete();
    
    $cashbook_bank_ids = \DB::table('acc_cashbook')->where('fnb_username', '>', '')->pluck('id')->toArray();
    foreach($cashbook_bank_ids as $cashbook_id){
        //\DB::table('acc_bank_references')->where('automated_reference',1)->update(['is_deleted' => 1]);
        
        $account_ids = \DB::table('acc_cashbook_transactions')
        ->select('account_id')
        ->where('cashbook_id',$cashbook_id)
        ->where('account_id','>',0)
        ->groupBy('account_id')->orderBy('id','desc')->pluck('account_id')->unique()->toArray();
        foreach($account_ids as $account_id){
            $last_trx = \DB::table('acc_cashbook_transactions')->select('id','reference')->where('account_id',$account_id)->orderBy('id','desc')->get()->first();
            $last_reference = $last_trx->reference;
            $last_bank_id = $last_trx->id;
            
            $refs =  \DB::table('acc_cashbook_transactions')->where('cashbook_id',$cashbook_id)->where('account_id',$account_id)->pluck('reference')->unique()->toArray();
            $automated_ref = findCommonSubstring($refs);
         
            
            if(!empty($automated_ref)){
                $e = \DB::table('acc_bank_references')->where('reference', $automated_ref)->count();
                if(!$e){
                   dbinsert('acc_bank_references',['last_bank_id'=>$last_bank_id,'account_id'=>$account_id,'reference'=>$automated_ref,'automated_reference' => 1]);
                }
            }
        }
        
        $supplier_ids = \DB::table('acc_cashbook_transactions')
        ->select('supplier_id')
        ->where('cashbook_id',$cashbook_id)
        ->where('supplier_id','>',0)
        ->groupBy('supplier_id')->orderBy('id','desc')->pluck('supplier_id')->unique()->toArray();
        foreach($supplier_ids as $supplier_id){
            $last_trx = \DB::table('acc_cashbook_transactions')->select('id','reference')->where('supplier_id',$supplier_id)->orderBy('id','desc')->get()->first();
            $last_reference = $last_trx->reference;
            $last_bank_id = $last_trx->id;
            
            $refs =  \DB::table('acc_cashbook_transactions')->where('cashbook_id',$cashbook_id)->where('supplier_id',$supplier_id)->pluck('reference')->unique()->toArray();
            $automated_ref = findCommonSubstring($refs);
            if(!empty($automated_ref)){
                $e = \DB::table('acc_bank_references')->where('reference', $automated_ref)->count();
                if(!$e){
                   dbinsert('acc_bank_references',['last_bank_id'=>$last_bank_id,'supplier_id'=>$supplier_id,'reference'=>$automated_ref,'automated_reference' => 1]);
                }
            }
        }
        
        
        $ledger_account_ids = \DB::table('acc_cashbook_transactions')
        ->select('ledger_account_id')
        ->where('cashbook_id',$cashbook_id)
        ->where('ledger_account_id','>',0)
        ->groupBy('ledger_account_id')->orderBy('id','desc')->pluck('ledger_account_id')->unique()->toArray();
        foreach($ledger_account_ids as $ledger_account_id){
            $last_trx = \DB::table('acc_cashbook_transactions')->select('id','reference')->where('ledger_account_id',$ledger_account_id)->orderBy('id','desc')->get()->first();
            $last_reference = $last_trx->reference;
            $last_bank_id = $last_trx->id;
            
            $refs =  \DB::table('acc_cashbook_transactions')->where('cashbook_id',$cashbook_id)->where('ledger_account_id',$ledger_account_id)->pluck('reference')->unique()->toArray();
            $automated_refs = findCommonSubstrings($refs);
          
            
            
            
            if(!empty($automated_refs) && count($automated_refs)){
                foreach($automated_refs as $automated_ref){
                    $automated_ref = trim($automated_ref);
                    $e = \DB::table('acc_bank_references')->where('reference', $automated_ref)->count();
                    if(!$e){
                       dbinsert('acc_bank_references',['last_bank_id'=>$last_bank_id,'ledger_account_id'=>$ledger_account_id,'reference'=>$automated_ref,'automated_reference' => 1]);
                    }
                }
            }
        }
    }
    
    
    // REMOVE DELETED
    $bank_references = \DB::table('acc_bank_references')->get();
    foreach ($bank_references as $br) {
        if (!empty($br->ledger_account_id)) {
            $exists = \DB::table('acc_ledger_accounts')->where('id', $br->ledger_account_id)->count();
            if (!$exists) {
                \DB::table('acc_bank_references')->where('id', $br->id)->update(['is_deleted' => 1]);
            }
        }
        if (!empty($br->account_id)) {
            $exists = \DB::table('crm_accounts')->where('partner_id', 1)->where('id','!=',1)->whereIn('type', ['customer','reseller'])->where('status', '!=', 'Deleted')->where('id', $br->account_id)->count();
            if (!$exists) {
                \DB::table('acc_bank_references')->where('id', $br->id)->update(['is_deleted' => 1]);
            }
        }
        if (!empty($br->supplier_id)) {
            $exists = \DB::table('crm_suppliers')->where('status', '!=', 'Deleted')->where('id', $br->supplier_id)->count();
            if (!$exists) {
                \DB::table('acc_bank_references')->where('id', $br->id)->update(['is_deleted' => 1]);
            }
        }
    }
   
    // remove references that are substrings of other references
    $references = DB::table('acc_bank_references')
    ->select('reference', 'id')
    ->where('is_deleted',0)
    ->get();
    
    $idsToDelete = [];
    
    foreach ($references as $reference) {
        foreach ($references as $potentialParent) {
            if ($reference->id !== $potentialParent->id && strpos($potentialParent->reference, $reference->reference) !== false) {
                $idsToDelete[] = $reference->id;
                break;
            }
        }
    }
    
    if (!empty($idsToDelete)) {
        DB::table('acc_bank_references')
            ->whereIn('id', $idsToDelete)
            ->update(['is_deleted' => 1]);
    }
    update_cashbook_references();
}


function update_cashbook_references(){
    \DB::table('acc_cashbook_transactions')->update(['bank_reference_id'=>0,'allocate_reference'=>'']);
    $references = \DB::table('acc_bank_references')->where('is_deleted',0)->get();

    foreach($references as $ref){
        if(!empty($ref->ledger_account_id)){
        \DB::table('acc_cashbook_transactions')->where('ledger_account_id',$ref->ledger_account_id)
        ->whereIn('cashbook_id',[9,10])
        ->where('reference','like','%'.$ref->reference.'%')
        ->update(['allocate_reference'=>$ref->reference,'bank_reference_id'=>$ref->id]);
        }
        
        if(!empty($ref->account_id)){
        \DB::table('acc_cashbook_transactions')->where('account_id',$ref->account_id)
        ->whereIn('cashbook_id',[9,10])
        ->where('reference','like','%'.$ref->reference.'%')
        ->update(['allocate_reference'=>$ref->reference,'bank_reference_id'=>$ref->id]);
        }
        
        if(!empty($ref->supplier_id)){
                
            \DB::table('acc_cashbook_transactions')->where('supplier_id',$ref->supplier_id)
            ->whereIn('cashbook_id',[9,10])
            ->where('reference','like','%'.$ref->reference.'%')
            ->update(['allocate_reference'=>$ref->reference,'bank_reference_id'=>$ref->id]);
           
         
        }
    }
}


function aftersave_blocked_references_copy($request){
    
    $blocked_references = \DB::table('acc_blocked_bank_references')->get();
    $db_conns = db_conns_sync();
    
    foreach($db_conns as $c){
        if($c!=session('instance')->db_connection){
          
            foreach($blocked_references as $row){
                $data = (array) $row;
                \DB::connection($c)->table('acc_blocked_bank_references')->updateOrInsert(['id'=>$row->id],$data);
            }
        }
    }
}


function findCommonSubstrings($references)
{
    // Initialize an empty array to store common substrings and their occurrences
    $commonSubstrings = [];
    $substrOccurrences = [];

    // Define the substrings to be removed (case-insensitive)
   
    $blocked_references = \DB::table('acc_blocked_bank_references')->where('is_deleted',0)->pluck('blocked_reference')->toArray();
    // Find common substrings and their occurrences across all references
    foreach ($references as $reference) {
        // Remove unwanted substrings from the reference
        $filteredReference = $reference;
        foreach ($blocked_references as $blocked_reference) {
            $filteredReference = str_ireplace($blocked_reference, '', $filteredReference);
        }

        // Split the filtered reference into words
        $words = array_filter(explode(' ', $filteredReference));

        // Iterate through each word in the filtered reference
        foreach ($words as $word) {
            // Skip if the word is too short or already in common substrings
            if (strlen($word) <= 3 || is_numeric(trim($word)) || in_array($word, $commonSubstrings)) {
                continue;
            }

            // Check if the word exists in all other references
            $occurrences = substr_count(implode(' ', $references), $word);
            if ($occurrences > 1) {
               
                $commonSubstrings[] = $word;
                $substrOccurrences[$word] = $occurrences;
                
            }
        }
    }

    return $commonSubstrings;
}


function longestCommonSubstring($str1, $str2) {
    $maxLen = 0;
    $endIndex = 0;
    $len1 = strlen($str1);
    $len2 = strlen($str2);
    $dp = array_fill(0, $len1 + 1, array_fill(0, $len2 + 1, 0));

    for ($i = 1; $i <= $len1; $i++) {
        for ($j = 1; $j <= $len2; $j++) {
            if ($str1[$i - 1] === $str2[$j - 1]) {
                $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                if ($dp[$i][$j] > $maxLen) {
                    $maxLen = $dp[$i][$j];
                    $endIndex = $i - 1;
                }
            }
        }
    }

    $commonSubstring = substr($str1, $endIndex - $maxLen + 1, $maxLen);

    // Ensure that the common substring does not break words
    if (preg_match('/\b/', $commonSubstring[0]) === 0) {
        $commonSubstring = substr($commonSubstring, strpos($commonSubstring, ' ') + 1);
    }
    if (preg_match('/\b/', $commonSubstring[-1]) === 0) {
        $commonSubstring = substr($commonSubstring, 0, strrpos($commonSubstring, ' '));
    }

    return $commonSubstring;
}


function findCommonSubstring($references)
{
    // Fetch all references from the database
    if (count($references) < 2) {
        return '';
    }
    
    $blocked_references = \DB::table('acc_blocked_bank_references')->where('is_deleted',0)->pluck('blocked_reference')->toArray();
    // Initialize the common substring with the first reference
    $commonSubstring = $references[0];

   
    // Find the common substring across all references
    foreach ($references as $reference) {
        $filteredReference = $reference;
        foreach ($blocked_references as $blocked_reference) {
            $filteredReference = str_ireplace($blocked_reference, '', $filteredReference);
        }

        // Only compare if the filtered reference is not empty
        if (!empty($filteredReference)) {
            $commonSubstring = longestCommonSubstring($commonSubstring, $filteredReference);
        }

        // Early exit if no common substring is found
        if ($commonSubstring === '') {
            break;
        }
    }
    
    if(strlen(trim($commonSubstring)) <= 3 || is_numeric(trim($commonSubstring))){
        return '';
    }

    return trim($commonSubstring);
}
