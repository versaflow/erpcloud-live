<?php

function schedule_eldo_monthly_billing()
{
    // if(date('d') == 25){
    $last_updated_date = \DB::table('sub_services')->orderBy('updated_at', 'desc')->pluck('updated_at')->first();
    $last_created_date = \DB::table('sub_services')->orderBy('created_at', 'desc')->pluck('created_at')->first();
    $process_billing = false;

    // if($last_created_date > date('Y-m-d',strtotime('-10 days'))){
    //     $process_billing = true;
    // }elseif($last_updated_date > date('Y-m-d',strtotime('-10 days'))){
    //     $process_billing = true;
    // }
    if ($process_billing) {
        $billing = new \EldoBilling;

        $billing->monthly_billing(date('Y-m-01'), strtotime('next month'));

        schedule_update_billing_details();
        $last_billing_id = \DB::table('acc_billing')->where('billing_type', 'Monthly')->orderBy('id', 'desc')->pluck('id')->first();
        \DB::table('acc_billing')->where('id', $last_billing_id)->update(['processed' => 1]);
        send_billing_summary($last_billing_id);
    } else {
        $user_id = \DB::table('erp_users')->where('username', 'ahmed@telecloud.co.za')->pluck('id')->first();
        staff_email($user_id, 'Services balances needs to be set to process Eldo Monthly Billing', 'Services balances needs to be set to process Eldo Monthly Billing', 'ahmed@telecloud.co.za');
    }
}

function delete_billing()
{
    $doc_ids = \DB::table('crm_documents')
        ->whereIn('doctype', ['Quotation'])
        ->where('docdate', date('Y-m-01', strtotime('+1 month')))
        ->where('billing_type', 'Monthly')
        ->pluck('id')->toArray();

    if (count($doc_ids) > 0) {
        foreach ($doc_ids as $doc_id) {
            \DB::table('crm_document_lines')->where('document_id', $doc_id)->delete();
            \DB::table('crm_documents')->where('id', $doc_id)->delete();
            \DB::table('acc_ledgers')->whereIn('doctype', ['Quotation'])->where('docid', $doc_id)->delete();
        }
    }
}

function button_create_bills($request)
{
    if (session('instance')->directory == 'eldooffice') {
        //$billing_date = \DB::table('acc_billing')->where('billing_type', 'Monthly')->where('processed', 1)->orderBy('id', 'desc')->pluck('billing_date')->first();
        $billing_date = \DB::table('acc_billing')->where('billing_type', 'Monthly')->where('billing_date', date('Y-m-01'))->orderBy('id', 'desc')->pluck('billing_date')->first();
        if ($billing_date != null) {
            $billing = new \EldoBilling;
            $billing->monthly_billing($billing_date);
            schedule_update_billing_details();
            $last_billing_id = \DB::table('acc_billing')->where('billing_type', 'Monthly')->orderBy('id', 'desc')->pluck('id')->first();
            \DB::table('acc_billing')->where('id', $last_billing_id)->update(['processed' => 1]);
            send_billing_summary($last_billing_id);
        }
    } else {
        schedule_create_bills();
    }

    return json_alert('Done');
}

function schedule_create_bills()
{
    schedule_voice_monthly();
    //Delete cancelled accounts
    $accounts = \DB::table('crm_accounts')
        ->where('id', '!=', 12)
        ->where('id', '!=', 1)
        ->where('status', '!=', 'Deleted')
        ->where('account_status', 'Cancelled')
        ->where('cancel_date', '<=', date('Y-m-t'))
        ->get();

    foreach ($accounts as $account) {
        delete_account($account->id);
    }

    //Process Eldo manually
    if (session('instance')->directory == 'eldooffice') {
        return false;
    }

    $sub = new ErpSubs;
    $sub->updateProductPrices();
    set_time_limit(0);

    // Check $pricing_exists for subscription
    $product_ids = \DB::table('sub_services')->where('status', '!=', 'Deleted')->pluck('product_id')->unique()->toArray();
    foreach ($product_ids as $product_id) {
        $pricing_exists = \DB::table('crm_pricelist_items')->where('product_id', $product_id)->where('pricelist_id', 1)->count();
        if (! $pricing_exists) {
            $code = \DB::table('crm_products')->where('id', $product_id)->pluck('code')->first();
            $error = 'Monthly billing not generated, product pricing not set '.$product_id.' '.$code;
            debug_email($error);

            return $error;
        }
    }

    //Set USD Account variables
    $usd_account_ids = \DB::table('crm_accounts')->where('renewal_date_billing', 1)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    $docdate = date('Y-m-01'); //, strtotime('+1 month'));
    \DB::table('sub_services')->whereNotIn('account_id', $usd_account_ids)->where('bill_frequency', 0)->update(['bill_frequency' => 1]);
    \DB::table('sub_services')->whereNotIn('account_id', $usd_account_ids)->where('bill_frequency', 1)->update(['renews_at' => $docdate]);
    \DB::table('sub_services')->whereNotIn('account_id', $usd_account_ids)->whereNull('renews_at')->update(['renews_at' => $docdate]);

    $renewals = \DB::table('sub_services')->whereNotIn('account_id', $usd_account_ids)->where('status', '!=', 'Deleted')->where('renews_at', '<', $docdate)->get();
    foreach ($renewals as $renewal) {
        \DB::table('sub_services')->whereNotIn('account_id', $usd_account_ids)->where('id', $renewal->id)->update(['renews_at' => date('Y-m').'-'.date('d', strtotime($renewal->renews_at))]);
    }

    //Delete existing billing documents
    $doc_ids = \DB::table('crm_documents')
        ->whereIn('doctype', ['Order', 'Quotation'])
        ->where('docdate', $docdate)
        ->where('billing_type', 'Monthly')
        ->whereNotIn('account_id', $usd_account_ids)
        ->pluck('id')->toArray();
    // vd($doc_ids);
    if (count($doc_ids) > 0) {
        $subs = \DB::table('sub_services')->whereNotIn('account_id', $usd_account_ids)->where('bill_frequency', 1)->select('id', 'renews_at', 'last_invoice_date', 'bill_frequency')->where('status', '!=', 'Deleted')->get();
        foreach ($subs as $s) {
            if ($s->renews_at > $docdate) {
                $data = [
                    'renews_at' => date('Y-m-d', strtotime($s->renews_at.' -'.$s->bill_frequency.' month')),
                ];
                \DB::table('sub_services')->where('id', $s->id)->update($data);
            }
        }
        foreach ($doc_ids as $doc_id) {
            \DB::table('crm_document_lines')->where('document_id', $doc_id)->delete();
            \DB::table('crm_documents')->where('id', $doc_id)->delete();
            \DB::table('acc_ledgers')->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])->where('docid', $doc_id)->delete();
            \DB::table('acc_ledgers')->where('doctype', 'Credit Note')->where('docid', $doc_id)->delete();
        }
        \DB::table('acc_billing')->where('id', $last_billing_id)->update(['processed' => 0]);
    }

    // exit;
    //-------------------------------------- Billing starts here
    $billing = new ErpBilling;
    $billing->processCancellations($docdate);
    $billing->saveSubscriptionTable();
    $billing->setCustomerType('customer');
    $billing->monthly_billing($docdate);
    $billing->setCustomerType('reseller');
    $billing->monthly_billing($docdate);

    $billing_id = \DB::table('acc_billing')->orderBy('billing_date', 'desc')->orderBy('id', 'desc')->pluck('id')->first();

    \DB::table('acc_billing')->where('id', $billing_id)->update(['processed' => 1]);
    verify_billing_summary($billing_id);
    //schedule_process_contract_billing();
    schedule_update_billing_details();
    send_billing_summary($billing_id);
}

function button_update_last_bill_totals()
{
    schedule_update_last_bill_totals();

    return json_alert('Done');
}

function schedule_update_last_bill_totals()
{
    $docdate = date('Y-m-01'); //, strtotime('first day of next month'));
    $documents_created = \DB::table('crm_documents')->where('docdate', $docdate)->where('billing_type', 'Monthly')->count();
    // if ($documents_created) {
    //     return false;
    // }

    pricelist_set_discounts();
    $sub = new ErpSubs;
    $sub->updateProductPrices();

    $billing = [
        'billing_date' => $docdate,
        'billing_type' => 'Monthly',
    ];

    $billing['name'] = $docdate.' '.$billing['billing_type'];
    $billing_id = \DB::table('acc_billing')->where('billing_type', $billing['billing_type'])->where('billing_date', $docdate)->pluck('id')->first();

    if (! $billing_id) {
        $billing_id = \DB::table('acc_billing')->insertGetId($billing);
    }

    $billing = \DB::table('acc_billing')->where('id', $billing_id)->orderBy('id', 'desc')->get()->first();
    if ($billing->processed) {
        return false;
    }

    $date = $billing->billing_date;
    $demo_account_ids = [];
    if (session('instance')->directory == 'eldooffice') {
        $rental_space_ids = \DB::table('crm_rental_spaces')->where('has_lease', 'Internal')->pluck('id')->toArray();
        $demo_account_ids = \DB::table('crm_rental_leases')->whereIn('rental_space_id', $rental_space_ids)->where('status', '!=', 'Deleted')->pluck('account_id')->unique()->toArray();
    }

    $list = [];
    $types = ['customer', 'reseller'];
    foreach ($types as $type) {
        if ($type == 'customer') {
            $account_ids = \DB::table('crm_accounts')
                ->whereNotIn('id', $demo_account_ids)
                ->where('partner_id', 1)
                ->where('type', 'customer')
                ->where('currency', 'ZAR')
                ->where('id', '!=', 1)
                ->where('status', '!=', 'Deleted')
                ->pluck('id')->toArray();
            $billing_account_ids = $account_ids;
        }
        if ($type == 'reseller') {
            $account_ids = \DB::table('crm_accounts')
                ->whereNotIn('id', $demo_account_ids)
                ->where('partner_id', '!=', 1)
                ->where('type', 'reseller_user')
                ->where('currency', 'ZAR')
                ->where('id', '!=', 1)
                ->where('status', '!=', 'Deleted')
                ->pluck('id')->toArray();
            $billing_account_ids = \DB::table('crm_accounts')
                ->whereNotIn('id', $demo_account_ids)
                ->where('type', 'reseller')
                ->where('currency', 'ZAR')
                ->where('id', '!=', 1)
                ->where('status', '!=', 'Deleted')
                ->pluck('id')->toArray();
        }

        $subs = \DB::table('sub_services')
            ->whereIn('account_id', $account_ids)
            ->where('bill_frequency', 1)
            ->where('status', '!=', 'Deleted')
            ->where('bundle_id', 0)
            ->where('created_at', '<', $billing->billing_date)
            ->get()->groupBy('product_id');
        $docids = [];

        //ELDO DEBUG
        // DEBUG
        //$product_id = 130;
        //$billed_subscription_ids = \DB::table('crm_document_lines')->whereIn('document_id',$docids)->where('product_id', $product_id)->pluck('subscription_id')->toArray();
        //$subscription_ids = \DB::table('sub_services')->whereIn('account_id',$account_ids)->where('product_id',$product_id)->where('status','!=','Deleted')->pluck('id')->toArray();
        //$r = array_diff($subscription_ids,$billed_subscription_ids);
        //dd($r,$subscription_ids,$billed_subscription_ids);

        //foreach($all_docids as $docid){
        //set_document_lines_gp($docid);
        // }
        $billed_total = 0;
        $billed_total_usd = 0;
        $prorata_total = 0;
        $prorata_usd = 0;
        foreach ($subs as $product_id => $s) {
            $product = \DB::table('crm_products')->where('id', $product_id)->get()->first();
            if (! $product->is_subscription) {
                continue;
            }

            $supplier_id = 0;
            if ($product->reconcile_supplier) {
                $supplier_id = \DB::table('crm_supplier_document_lines')
                    ->join('crm_supplier_documents', 'crm_supplier_documents.id', '=', 'crm_supplier_document_lines.document_id')
                    ->where('crm_supplier_document_lines.product_id', $product->id)
                    ->where('crm_supplier_documents.docdate', '>', date('Y-m-01', strtotime($billing->date.' -2 months')))
                    ->limit(1)
                    ->orderBy('crm_supplier_documents.id', 'desc')
                    ->pluck('crm_supplier_documents.supplier_id')->first();
                if (empty($supplier_id)) {
                    $supplier_id = 0;
                }
            }

            $subs_qty = $s->sum('qty');
            $billed_qty = \DB::table('crm_document_lines')->whereIn('document_id', $docids)->where('product_id', $product_id)->sum('qty');
            $billed_total = \DB::table('crm_document_lines')->whereIn('document_id', $docids)->where('product_id', $product_id)->sum('sale_total');
            $billed_total_usd = 0;
            $prorata_total = \DB::table('crm_document_lines')->whereIn('document_id', $docids)->where('product_id', $product_id)->sum('prorata_difference');
            $prorata_total_usd = 0;
            $billed_total += $prorata_total;
            $billed_total_usd += $prorata_total_usd;

            $subs_current_qty = 0;
            $activation_qty = 0;
            $cancelled_qty = 0;

            $csubs = \DB::table('sub_services')->whereIn('sub_services.account_id', $account_ids)
                ->where('bill_frequency', 1)
                ->where('bundle_id', 0)
                ->where('product_id', $product_id)
                ->where('status', '!=', 'Deleted')
                ->get();

            foreach ($csubs as $n) {
                $e = \DB::table('sub_services')->where('product_id', $product_id)->where('status', '!=', 'Deleted')->where('id', $n->id)->count();
                if (! $e) {
                    if ($n->provision_type == 'airtime_contract') {
                        $activation_qty += $n->qty;
                    } else {
                        $activation_qty++;
                    }
                }
            }

            $lsubs = \DB::table('sub_services')->whereIn('sub_services.account_id', $account_ids)
                ->where('bill_frequency', 1)
                ->where('bundle_id', 0)
                ->whereRaw('to_cancel=1 and cancel_date<"'.$billing->billing_date.'"')
                ->where('product_id', $product_id)
                ->where('status', '!=', 'Deleted')
                ->get();
            foreach ($lsubs as $n) {
                if ($n->provision_type == 'airtime_contract') {
                    $cancelled_qty += $n->qty;
                } else {
                    $cancelled_qty++;
                }
            }

            $subs_current_qty = \DB::table('sub_services')->whereIn('sub_services.account_id', $account_ids)
                ->where('status', '!=', 'Deleted')
                ->where('bundle_id', 0)
                ->where('bill_frequency', 1)
                ->where('status', '!=', ' Deleted')
                ->whereRaw('(to_cancel=0 or (to_cancel = 1 and cancel_date > "'.$billing->billing_date.'"))')
                ->where('created_at', '<=', $billing->billing_date)
                ->where('product_id', $product_id)->count();

            // foreach ($subs_current as $r) {
            //     if ($r->provision_type == 'airtime_contract') {
            //         $current_qty = $r->qty;
            //     }else{
            //         $current_qty = $r->qty;
            //     }
            //     $subs_current_qty+=$current_qty;
            // }

            $subs_total = \DB::table('sub_services')
                ->join('crm_products', 'sub_services.product_id', '=', 'crm_products.id')
                ->whereNotIn('sub_services.account_id', $demo_account_ids)
                ->whereIn('sub_services.account_id', $account_ids)
                ->where('crm_products.is_subscription', 1)
                ->where('sub_services.bundle_id', 0)
                ->where('sub_services.bill_frequency', 1)
                ->where('sub_services.status', '!=', 'Deleted')
                ->where('sub_services.status', '!=', 'Pending')
                ->where('sub_services.provision_type', 'NOT LIKE', '%prepaid%')
                ->whereRaw('(to_cancel=0 or (to_cancel=1 and cancel_date>"'.$billing->billing_date.'"))')
                ->where('sub_services.created_at', '<', $date)
                ->where('sub_services.product_id', $product_id)
                ->sum(\DB::raw('price*qty'));

            $subs_total_usd = 0;
            if (abs($subs_total - $billed_total) < 1) {
                $subs_total = $billed_total;
            }

            if (abs($subs_total_usd - $billed_total_usd) < 1) {
                $subs_total_usd = $billed_total_usd;
            }
            $category_id = \DB::table('crm_products')->where('id', $product_id)->pluck('product_category_id')->first();
            $retail_price = \DB::table('crm_pricelist_items')->where('pricelist_id', 1)->where('product_id', $product_id)->pluck('price')->first();
            $reseller_price = \DB::table('crm_pricelist_items')->where('pricelist_id', 1)->where('product_id', $product_id)->pluck('reseller_price_tax')->first();
            $reseller_price = currency($reseller_price / 1.15);
            $list[] = [
                'product_id' => $product_id,
                'category_id' => $category_id,
                'subs_qty' => $subs_qty,
                'billed_qty' => $billed_qty,
                'subs_total' => $subs_total,
                'billed_total' => $billed_total,
                'prorata_total' => $prorata_total,
                'subs_total_usd' => $subs_total_usd,
                'billed_total_usd' => $billed_total_usd,
                'prorata_total_usd' => $prorata_total_usd,
                'supplier_id' => $supplier_id,
                'subs_current_qty' => $subs_current_qty,
                'activation_qty' => $activation_qty,
                'cancelled_qty' => $cancelled_qty,
                'retail_price' => $retail_price,
                'reseller_price' => $reseller_price,
                'billing_id' => $billing->id,
                'customer_type' => $type,
                'sort_order' => $product->sort_order,
            ];
        }
    }

    \DB::table('sub_service_summary')->where('billing_id', $billing->id)->delete();
    if (count($list) > 0) {
        \DB::table('sub_service_summary')->insert($list);
    }
}

function send_billing_summary($billing_id)
{
    schedule_update_billing_details();
    $bill = \DB::table('acc_billing')->where('id', $billing_id)->get()->first();

    $file_title = 'Billing '.$bill->billing_type.' '.$bill->billing_date;
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $file_path = export_billing_summary_layout($billing_id, $file_name);

    if (! file_exists($file_path)) {
        return json_alert('File not saved', 'error');
    }

    $data['internal_function'] = 'billing_summary';
    $data['billing_date'] = $bill->billing_date;
    $data['billing_type'] = $bill->billing_type;

    $data['attachments'][] = $file_name;
    $data['force_to_email'] = 'ahmed@telecloud.co.za';
    // $data['bcc_email'] = 'ahmed@telecloud.co.za';
    // $data['test_debug'] = 1;

    if (session('instance')->directory == 'eldooffice') {
        $rental_escalations = '';
        $escalations = \DB::table('crm_rental_escalations')->where('created_at', 'like', date('Y-m', strtotime($bill->billing_date).'%'))->get();
        if (count($escalations) == 0) {
            $rental_escalations = 'Rental Escalations: No rental escalations applied this month.';
        } else {
            $rental_escalations .= 'Rental Escalations:<br>';
            foreach ($escalations as $e) {
                $rental = \DB::table('crm_rental_leases')
                    ->select('crm_rental_leases.*', 'crm_rental_spaces.*', 'crm_rental_leases.id as id')
                    ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
                    ->where('crm_rental_leases.status', '!=', 'Deleted')
                    ->where('crm_rental_leases.id', $e->rental_space_id)->get()->first();
                $rental_escalations .= '<b>Office #'.$rental->office_number.'<b><br>';
                $rental_escalations .= 'Old Price'.$e->old_price.'<br>';
                $rental_escalations .= 'New Price'.$e->price.'<br>';
                $rental_escalations .= 'Next escalation date'.$rental->next_escalation_date.'<br><br>';
            }
        }
        $data['rental_escalations'] = $rental_escalations;
    }

    if (! $bill->approved) {
        if ($bill->billing_type == 'Monthly') {
            $data['approval_links'] = get_monthly_billing_approval_links($billing_id);
        }
        if ($bill->billing_type == 'Renewal') {
            $data['approval_links'] = get_renewal_billing_approval_links($billing_id);
        }
        $exists = \DB::table('crm_approvals')->where('row_id', $bill->id)->where('module_id', 744)->count();
        if (! $exists) {
            $approve_data = [
                'module_id' => 744,
                'row_id' => $bill->id,
                'title' => $bill->billing_type.' '.$bill->billing_date,
                'processed' => 0,
                'requested_by' => get_user_id_default(),
                'approval_file' => $file_name,
            ];
            $result = (new \DBEvent)->setTable('crm_approvals')->save($approve_data);
        }
        \File::copy($file_path, uploads_path(1859).$file_name);
    }

    $data['force_to_email'] = 'ahmed@telecloud.co.za';
    //    $data['test_debug'] = 1;

    erp_process_notification(1, $data);

    return true;
}

function verify_billing_summary($billing_id)
{
    // reconcile
    $billed_grand_total = \DB::table('sub_service_summary')->where('billing_id', $billing_id)->sum('billed_total');
    $billed_grand_total_usd = \DB::table('sub_service_summary')->where('billing_id', $billing_id)->sum('billed_total_usd');
    $subs_grand_total = \DB::table('sub_service_summary')->where('billing_id', $billing_id)->sum('subs_total');
    $subs_grand_total_usd = \DB::table('sub_service_summary')->where('billing_id', $billing_id)->sum('subs_total_usd');

    $reconciled = ($billed_grand_total_usd == $subs_grand_total_usd && $subs_grand_total == $billed_grand_total) ? 1 : 0;
    $billing = \DB::table('acc_billing')->where('id', $billing_id)->get()->first();
    $date = $billing->billing_date;

    $bills = \DB::table('crm_documents')->select('crm_documents.*')->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->where('docdate', $date)->where('billing_type', $billing->billing_type)->where('reversal_id', 0)->get();

    $bill_count = \DB::table('crm_documents')
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])->where('docdate', $date)->where('billing_type', $billing->billing_type)->where('reversal_id', 0)->count();

    \DB::table('acc_billing')->where('id', $billing_id)->update(['reconciled' => $reconciled, 'num_invoices' => $bill_count]);
}

function verify_bill_emails($billing_id)
{
    $billing = \DB::table('acc_billing')->where('id', $billing_id)->get()->first();
    if (! $billing->approved) {
        return false;
    }
    $date = $billing->billing_date;

    $bills = \DB::table('crm_documents')->select('crm_documents.*')->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->where('docdate', $date)->where('billing_type', $billing->billing_type)->where('reversal_id', 0)->get();

    $bill_count = \DB::table('crm_documents')
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])->where('docdate', $date)->where('billing_type', $billing->billing_type)->where('reversal_id', 0)->count();

    $bill_email_count = 0;
    $bill_email_success = 0;
    $bill_email_error = 0;
    $email_errors = [];

    foreach ($bills as $b) {
        $account = dbgetaccount($b->account_id);
        if ($account->notification_type == 'email') {
            $e = \DB::table('erp_communication_lines')->select('error', 'success', 'destination')->where('account_id', $b->account_id)->where('attachments', 'like', '%Invoice_'.$b->id.'%')->get()->first();
            if (! empty($e)) {
                $bill_email_count++;
                if ($e->success == 1) {
                    $bill_email_success++;
                } else {
                    $bill_email_error++;

                    $err = $account->company.' - '.$e->destination.' not delivered';
                    if (! empty($e->error)) {
                        $err .= ' - '.$e->error;
                    }
                    $email_errors[] = $err;
                }
            } else {
                $err = $account->company.' - '.$account->email.' not sent';
                $email_errors[] = $err;
                $bill_email_error++;
            }
        } else {
            $bill_email_count++;
            $bill_email_success++;
        }
    }

    $data = [
        'num_invoices' => $bill_count,
        'num_emails' => $bill_email_count,
        'num_emails_success' => $bill_email_success,
        'num_emails_error' => $bill_email_error,
        'email_errors' => $email_errors,
    ];

    \DB::table('acc_billing')->updateOrInsert(['billing_date' => $date, 'billing_type' => $billing->billing_type], $data);
}

function export_billing_summary_layout($billing_id, $file_name)
{
    $layout_id = 3562;
    $layout = \DB::table('erp_grid_views')->where('id', $layout_id)->get()->first();

    $module = \DB::table('erp_cruds')->where('id', $layout->module_id)->get()->first();
    $total_fields = \DB::table('erp_module_fields')->where('module_id', $module->detail_module_id)->whereIn('field_type', ['currency', 'decimal', 'integer'])->pluck('label')->toArray();
    $model = new \App\Models\ErpModel;
    $model->setModelData($module->detail_module_id);

    $grid_data = $model->info;
    $layout_state = json_decode($layout->detail_aggrid_state);
    if (empty($layout_state->colState)) {
        return json_alert('Layout state not set. Save layout and try again.', 'warning');
    }
    if (empty($layout_state->filterState)) {
        $filter_state = [];
    } else {
        $filter_state = (array) json_decode(json_encode($layout_state->filterState), true);
    }

    $filter_state['billing_id'] = [
        'filterType' => 'number',
        'type' => 'equals',
        'filter' => $billing_id,
    ];

    $sortModel = [];
    // $sort_fields = collect($layout_state->colState);

    $sort_fields = collect($layout_state->colState)->where('sortIndex', '!=', '')->sortBy('sortIndex');
    if (! empty($sort_fields) && count($sort_fields) > 0) {
        foreach ($sort_fields as $col) {
            if ($col->sortIndex != '') {
                $sortModel[] = [
                    'sort' => $col->sort,
                    'colId' => $col->colId,
                ];
            }
        }
    }

    $request_object = new \Illuminate\Http\Request;
    $request_object->setMethod('POST');
    $request_object->request->add(['return_all_rows' => 1]);
    $request_object->request->add(['startRow' => 0]);
    $request_object->request->add(['endRow' => 100000]);
    $request_object->request->add(['rowGroupCols' => []]);
    $request_object->request->add(['valueCols' => []]);
    $request_object->request->add(['groupKeys' => []]);
    $request_object->request->add(['sortModel' => $sortModel]);
    if ($filter_state) {
        $request_object->request->add(['filterModel' => $filter_state]);
    } else {
        $request_object->request->add(['filterModel' => []]);
    }
    if (! empty($layout_state->searchtext) && $layout_state->searchtext != ' ') {
        $request_object->request->add(['search' => $layout_state->searchtext]);
    }

    // aa($request_object->all());
    $sql_data = $model->getData($request_object);
    // aa($sql_data);
    $grid = new \ErpGrid($grid_data);
    $rows = $grid->formatAgGridData($sql_data['rows']);

    // format data for export
    $excel_data = [];
    $module_fields = collect($grid_data['module_fields']);
    // aa($layout_state);
    if ($layout_state->colState) {
        foreach ($layout_state->colState as $col) {
            // aa($rows);
            if ($col->hide == 'false') {
                foreach ($rows as $i => $excel_row) {
                    // aa($excel_row);

                    foreach ($excel_row as $k => $v) {
                        if ($k == $col->colId) {
                            $m_field = str_replace('join_', '', $k);
                            $label = $module_fields->where('field', $m_field)->pluck('label')->first();
                            $field_type = $module_fields->where('field', $m_field)->pluck('field_type')->first();
                            if ($field_type == 'boolean' && $v) {
                                $v = 'Yes';
                            }
                            if ($field_type == 'boolean' && ! $v) {
                                $v = 'No';
                            }
                            $excel_data[$i][$label] = $v;
                        }
                    }
                }
            }
        }
    }
    // aa($excel_data);

    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $export = new App\Exports\CollectionExport;
    $export->setTotalFields($total_fields);
    $export->setData($excel_data);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
    $file_path = attachments_path().$file_name;

    return $file_path;
}

function schedule_renewal_billing()
{
    if (session('instance')->directory == 'eldooffice') {
        return false;
    }

    //$usd_account_ids = \DB::table('crm_accounts')->where('renewal_date_billing', 1)->where('status','!=','Deleted')->pluck('id')->toArray();

    //if(count($usd_account_ids) == 0){
    //    return false;
    //}
    //\DB::table('sub_services')->whereIn('account_id',$usd_account_ids)->where('bill_frequency',0)->update(['bill_frequency'=>1]);
    //\DB::table('sub_services')->whereIn('account_id',$usd_account_ids)->whereNull('renews_at')->update(['renews_at'=>$docdate]);

    $docdate = date('Y-m-01');
    // $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('renews_at', $docdate)->get();
    // foreach ($subs as $s) {
    //     $renewal_date = $s->renews_at;
    //     $new_renewal_date = date('Y-m-01', strtotime($renewal_date.' + '.$s->bill_frequency.' month'));
    //     \DB::table('sub_services')->where('id', $s->id)->update(['renews_at' => $new_renewal_date]);
    // }

    //Delete
    $doc_ids = \DB::table('crm_documents')
        ->whereIn('doctype', ['Quotation'])
        ->where('docdate', $docdate)
        ->where('billing_type', 'Renewal')
        ->pluck('id')->toArray();
    if (count($doc_ids) > 0) {
        // $subs = \DB::table('sub_services')->where('bill_frequency', '!=', 1)->select('id', 'renews_at', 'last_invoice_date', 'bill_frequency')->where('status', '!=', 'Deleted')->get();
        // foreach ($subs as $s) {
        //     if ($s->renews_at > $docdate) {
        //         $data = [
        //             'renews_at' => date('Y-m-d', strtotime($s->renews_at.' -'.$s->bill_frequency.' month')),
        //         ];
        //         \DB::table('sub_services')->where('id', $s->id)->update($data);
        //     }
        // }

        foreach ($doc_ids as $doc_id) {
            \DB::table('crm_document_lines')->where('document_id', $doc_id)->delete();
            \DB::table('crm_documents')->where('id', $doc_id)->delete();
            \DB::table('acc_ledgers')->whereIn('doctype', ['Quotation'])->where('docid', $doc_id)->delete();
            \DB::table('acc_ledgers')->where('doctype', 'Credit Note')->where('docid', $doc_id)->delete();
        }
    }

    //  $sub = new ErpSubs();
    //  $sub->updateProductPrices();

    $billing = new ErpBilling($docdate);
    $billing->setBillOnRenewal(1);
    $bill_frequencies = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('bill_frequency', '<>', 1)->pluck('bill_frequency')->unique()->toArray();
    // vd($bill_frequencies);
    foreach ($bill_frequencies as $bill_frequency) {
        $billing->setBillingFrequency($bill_frequency);
        $billing->setCustomerType('customer');
        $billing->monthly_billing($docdate);
        $billing->setCustomerType('reseller');
        $billing->monthly_billing($docdate);
    }

    $docs = \DB::table('crm_documents')->where('docdate', $docdate)->where('billing_type', 'Renewal')->count();
    if ($docs == 0) {
        \DB::table('acc_billing')->where('billing_date', $docdate)->where('billing_type', 'Renewal')->delete();
    }
    $billing_id = \DB::table('acc_billing')->where('billing_date', $docdate)->where('billing_type', 'Renewal')->pluck('id')->first();
    if ($billing_id) {
        \DB::table('acc_billing')->where('id', $billing_id)->update(['approved' => 0, 'processed' => 1]);
        schedule_update_renewal_billing_details();
        verify_billing_summary($billing_id);
        send_billing_summary($billing_id);
    }
}

function schedule_verify_bill_emails()
{
    if (in_array(session('instance')->id, [1, 2, 11])) {
        $billings = \DB::table('acc_billing')->where('approved', 1)->where('email_summary_sent', 0)->where('billing_type', 'Monthly')->orderBy('id', 'desc')->limit(1)->get();

        foreach ($billings as $billing) {
            if ($billing->updated_at < date('Y-m-d H:i:s', strtotime('-2 hours'))) {
                verify_bill_emails($billing->id);
                send_billing_summary($billing->id);
                \DB::table('acc_billing')->where('id', $billing->id)->update(['email_summary_sent' => 1]);
            }
        }
    }
}

function set_renewal_date_billing_accounts()
{
    $account_ids = \DB::table('sub_services')->where('provision_type', 'like', 'ip_range%')->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
    \DB::table('crm_accounts')->whereIn('id', $account_ids)->update(['renewal_date_billing' => 1]);
}

function button_send_billing_summary($request)
{
    $result = send_billing_summary($request->id);
    if ($result !== true) {
        return $result;
    }

    return json_alert('Done');
}

function button_billing_verify_bill_emails($request)
{
    verify_bill_emails($request->id);

    return json_alert('Done');
}

function button_verify_billing_summary($request)
{
    schedule_update_billing_details();
    verify_billing_summary($request->id);

    update_billing_run_supplier_invoices($request->id);

    return json_alert('Done');
}

function button_billing_send_emails($request)
{
    email_monthly_billing($request->id);

    return json_alert('Done.');
}

function button_billing_process_billing($request)
{
    $data['docdate'] = date('Y-m-01', strtotime('first day of next month'));

    /*
    if (session('instance')->id == 2) {

        $service_balances = \DB::table('sub_service_balances')->where('is_deleted',0)->orderBy('id','desc')->get()->first();
        unset($service_balances->id);
        unset($service_balances->electricity_balance);
        unset($service_balances->citiq_balance);
        $data['service_balances'] = $service_balances;
    }
    */
    return view('__app.button_views.process_billing', $data);
}

function schedule_autopay_internal_accounts()
{
    $docdate = date('Y-m-d');

    $accounts = \DB::table('crm_accounts')->where('payment_type', 'Internal')->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    foreach ($accounts as $account) {
        $balance = get_debtor_balance($account->id);
        if ($balance > 0) {
            $amount = abs($account->balance);
            $cash_id = create_cash_transaction($account->id, $amount, 'Account balance paid for internal account', 1, $docdate);
        }
    }
}

function schedule_process_contract_billing()
{
    return false;

    if (session('instance')->directory == 'eldooffice') {
        return false;
    }
    $docdate = date('Y-m-d');
    $bill_frequencies = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('bill_frequency', '!=', 0)->where('bill_frequency', '!=', 1)->pluck('bill_frequency')->filter()->unique()->toArray();

    $billing = new ErpBilling($docdate);
    foreach ($bill_frequencies as $bill_frequency) {
        $billing->setBillingFrequency($bill_frequency);

        $billing->setCustomerType('customer');
        $billing->monthly_billing();
        $billing->setCustomerType('reseller');
        $billing->monthly_billing();
    }

    \DB::table('acc_billing')->where('billing_date', $docdate)->where('billing_type', 'Contract')->update(['processed' => 1]);
}

function get_renewal_billing_approval_links($billing_id)
{
    $billing = \DB::table('acc_billing')->where('billing_type', 'Renewal')->where('id', $billing_id)->orderBy('id', 'desc')->get()->first();
    if (empty($billing) || empty($billing->id)) {
        return '';
    }
    $date = $billing->billing_date;

    $bills = \DB::table('crm_documents')->select('crm_documents.*')->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->where('docdate', $date)->where('billing_type', 'Renewal')->where('reversal_id', 0)->get();

    $bill_count = \DB::table('crm_documents')
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])->where('docdate', $date)->where('billing_type', 'Monthly')->where('reversal_id', 0)->count();

    $billing = \DB::table('acc_billing')->orderBy('id', 'desc')->get()->first();
    $msg = '<br><br>';
    $approve_link_data = [
        'instance_id' => session('instance')->id,
        'token' => session('instance')->directory.'1',
        'billing_id' => $billing->id,
        'approve' => 1,
    ];
    $reject_link_data = [
        'instance_id' => session('instance')->id,
        'token' => session('instance')->directory.'1',
        'billing_id' => $billing->id,
        'reject' => 1,
    ];

    $approve_link = 'https://'.session('instance')->domain_name.'/renewal_billing_approval/'.Erp::encode($approve_link_data);
    $reject_link = 'https://'.session('instance')->domain_name.'/renewal_billing_approval/'.Erp::encode($reject_link_data);

    $msg .= '<a href="'.$approve_link.'" target="_blank" style="font-weight:bold;text-decoration: underline;">Approve Billing and send emails to customers.</a><br><br>';
    // $msg .= '<a href="'.$reject_link.'" target="_blank" style="font-weight:bold;text-decoration: underline;">Reject Billing and convert Tax Invoices to Orders.</a><br>';

    return $msg;
}

function get_monthly_billing_approval_links($billing_id)
{
    $billing = \DB::table('acc_billing')->where('billing_type', 'Monthly')->where('id', $billing_id)->orderBy('id', 'desc')->get()->first();
    if (empty($billing) || empty($billing->id)) {
        return '';
    }
    $date = $billing->billing_date;

    $bills = \DB::table('crm_documents')->select('crm_documents.*')->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->where('docdate', $date)->where('billing_type', 'Monthly')->where('reversal_id', 0)->get();

    $bill_count = \DB::table('crm_documents')
        ->join('crm_accounts', 'crm_documents.account_id', '=', 'crm_accounts.id')
        ->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])->where('docdate', $date)->where('billing_type', 'Monthly')->where('reversal_id', 0)->count();

    $billing = \DB::table('acc_billing')->where('id', $billing->id)->orderBy('id', 'desc')->get()->first();
    $msg = '<br><br>';
    $approve_link_data = [
        'instance_id' => session('instance')->id,
        'token' => session('instance')->directory.'1',
        'billing_id' => $billing->id,
        'approve' => 1,
    ];
    $reject_link_data = [
        'instance_id' => session('instance')->id,
        'token' => session('instance')->directory.'1',
        'billing_id' => $billing->id,
        'reject' => 1,
    ];

    $approve_link = 'https://'.session('instance')->domain_name.'/monthly_billing_approval/'.Erp::encode($approve_link_data);
    $reject_link = 'https://'.session('instance')->domain_name.'/monthly_billing_approval/'.Erp::encode($reject_link_data);

    $msg .= '<a href="'.$approve_link.'" target="_blank" style="font-weight:bold;text-decoration: underline;">Approve Billing and send emails to customers.</a><br><br>';
    // $msg .= '<a href="'.$reject_link.'" target="_blank" style="font-weight:bold;text-decoration: underline;">Reject Billing and convert Tax Invoices to Orders.</a><br>';

    return $msg;
}

function update_billing_run_supplier_invoices($billing_id = false)
{
    if ($billing_id) {
        $billing = \DB::table('acc_billing')->where('id', $billing_id)->get()->first();
    } else {
        $billing = \DB::table('acc_billing')->where('billing_type', 'Monthly')->orderBy('id', 'desc')->get()->first();
    }

    $date = $billing->billing_date;

    \DB::connection('default')->table('sub_service_summary')->where('billing_id', $billing->id)->update(['supplier_qty' => 0, 'supplier_total' => 0, 'requires_supplier_reconciliation' => 0, 'supplier_reconciled' => 0]);

    $service_products = \DB::table('crm_products')->where('reconcile_supplier', 1)->where('status', '!=', 'Deleted')->get();

    $service_product_ids = $service_products->pluck('id')->toArray();

    \DB::connection('default')->table('sub_service_summary')->where('billing_id', $billing->id)->whereIn('product_id', $service_product_ids)->update(['requires_supplier_reconciliation' => 1]);

    $subs = \DB::connection('default')->table('sub_service_summary')->where('billing_id', $billing->id)->whereIn('product_id', $service_product_ids)->get();
    $supplier_doc_ids = \DB::table('crm_supplier_documents')
        ->where('docdate', 'LIKE', date('Y-m', strtotime($date)).'%')
        ->pluck('id')->toArray();
    $supplier_total = 0;
    $billed_total = 0;

    foreach ($subs as $s) {
        $product_id = $s->product_id;
        $code = $service_products->where('id', $s->product_id)->pluck('code')->first();
        $supplier_invoice_total = \DB::table('crm_supplier_document_lines')
            ->whereIn('document_id', $supplier_doc_ids)
            ->where('product_id', $product_id)
            ->sum(\DB::raw('price*qty'));
        $supplier_qty_total = \DB::table('crm_supplier_document_lines')
            ->whereIn('document_id', $supplier_doc_ids)
            ->where('product_id', $product_id)
            ->sum('qty');
        \DB::connection('default')->table('sub_service_summary')->where('id', $s->id)->update(['supplier_qty' => $supplier_qty_total, 'supplier_total' => $supplier_invoice_total]);

        $billed_qty = \DB::connection('default')->table('sub_service_summary')->where('product_id', $product_id)->where('billing_id', $billing->id)->sum('billed_qty');
        $supplier_reconciled = ($supplier_qty_total == $billed_qty) ? 1 : 0;
        \DB::connection('default')->table('sub_service_summary')->where('id', $s->id)->update(['supplier_qty' => $supplier_qty_total, 'supplier_total' => $supplier_invoice_total, 'supplier_reconciled' => $supplier_reconciled]);
        $billed_total += $billed_qty;
        $supplier_total += $supplier_qty_total;
        $supplier_reconcile_result .= $code.' supplier: '.$supplier_qty_total.' billed: '.$billed_qty.PHP_EOL;
    }
    $supplier_qty = \DB::connection('default')->table('sub_service_summary')->where('billing_id', $billing->id)->whereIn('product_id', $service_product_ids)->sum('supplier_qty');
    $billed_qty = \DB::connection('default')->table('sub_service_summary')->where('billing_id', $billing->id)->whereIn('product_id', $service_product_ids)->sum('billed_qty');
    $supplier_reconciled = ($supplier_total == $billed_total) ? 1 : 0;

    \DB::table('acc_billing')->where('id', $billing->id)->update(['supplier_reconciled' => $supplier_reconciled, 'supplier_reconcile_result' => $supplier_reconcile_result]);
}
