<?php

function schedule_update_billing_details()
{
    $billing = \DB::table('acc_billing')->where('billing_type', 'Monthly')->orderBy('id', 'desc')->get()->first();
    // if($billing->processed){ //Ahmed
    //     return false;
    // }
    $date = $billing->billing_date;
    $last_docdate = '2024-08-01'; //\DB::table('crm_documents')->whereIn('doctype', ['Quotation','Order','Tax Invoice'])->where('billing_type', 'Monthly')->orderBy('docdate', 'desc')->pluck('docdate')->first();

    // if ($last_docdate != $date) {
    //     return false;
    // }
    $demo_account_ids = [];
    if (session('instance')->directory == 'telecloud') {
        $demo_account_ids = [];
    }
    if (session('instance')->directory == 'eldooffice') {
        $rental_space_ids = \DB::table('crm_rental_spaces')->where('has_lease', 'Internal')->pluck('id')->toArray();
        $demo_account_ids = \DB::table('crm_rental_leases')->whereIn('rental_space_id', $rental_space_ids)->where('status', '!=', 'Deleted')->pluck('account_id')->unique()->toArray();
    }

    $last_docdate = \DB::table('crm_documents')->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])->where('billing_type', 'Monthly')->orderBy('docdate', 'desc')->pluck('docdate')->first();

    $list = [];

    $types = ['customer', 'reseller'];
    foreach ($types as $type) {
        if ($type == 'customer') {
            $account_ids = \DB::table('crm_accounts_lastmonth')
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
            $account_ids = \DB::table('crm_accounts_lastmonth')
                ->whereNotIn('id', $demo_account_ids)
                ->where('partner_id', '!=', 1)
                ->where('type', 'reseller_user')
                ->where('currency', 'ZAR')
                ->where('id', '!=', 1)
                ->where('status', '!=', 'Deleted')
                ->pluck('id')->toArray();
            $billing_account_ids = \DB::table('crm_accounts_lastmonth')
                ->whereNotIn('id', $demo_account_ids)
                ->where('type', 'reseller')
                ->where('currency', 'ZAR')
                ->where('id', '!=', 1)
                ->where('status', '!=', 'Deleted')
                ->pluck('id')->toArray();

        }

        if (session('instance')->directory == 'eldooffice') {
            $subs = \DB::table('sub_services_lastmonth')
                ->whereIn('account_id', $account_ids)
                ->where('bill_frequency', 1)
                ->where('status', '!=', 'Deleted')
                ->where('bundle_id', 0)
            // ->where('created_at','<',$billing->billing_date)
                ->get()->groupBy('product_id');
        } else {
            $subs = \DB::table('sub_services_lastmonth')
                ->whereIn('account_id', $account_ids)
                ->where('bill_frequency', 1)
                ->where('status', '!=', 'Deleted')
                ->where('bundle_id', 0)
                ->where('created_at', '<', $billing->billing_date)
                ->where('renews_at', '<', date('Y-m-t', strtotime($billing->billing_date.' +1 month')))
                ->get()->groupBy('product_id');
        }
        $docids = \DB::table('crm_documents')->whereIn('account_id', $billing_account_ids)->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])->where('billing_type', 'Monthly')->where('docdate', $last_docdate)->pluck('id')->toArray();

        //ELDO DEBUG

        // DEBUG
        //$product_id = 130;
        //$billed_subscription_ids = \DB::table('crm_document_lines')->whereIn('document_id',$docids)->where('product_id', $product_id)->pluck('subscription_id')->toArray();
        //$subscription_ids = \DB::table('sub_services_lastmonth')->whereIn('account_id',$account_ids)->where('product_id',$product_id)->where('status','!=','Deleted')->pluck('id')->toArray();
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
                $e = \DB::table('sub_services_lastmonth')->where('product_id', $product_id)->where('status', '!=', 'Deleted')->where('id', $n->id)->count();
                if (! $e) {
                    if ($n->provision_type == 'airtime_contract') {
                        $activation_qty += $n->qty;
                    } else {
                        $activation_qty++;
                    }
                }
            }

            $lsubs = \DB::table('sub_services_lastmonth')->whereIn('sub_services_lastmonth.account_id', $account_ids)
                ->where('bill_frequency', 1)
                ->where('bundle_id', 0)
                ->where('product_id', $product_id)
                ->where('status', '!=', 'Deleted')
                ->whereRaw('to_cancel=1 and cancel_date<"'.$billing->billing_date.'"')
                ->get();
            foreach ($lsubs as $n) {
                if ($n->provision_type == 'airtime_contract') {
                    $cancelled_qty += $n->qty;
                } else {
                    $cancelled_qty++;
                }
            }

            $subs_current = \DB::table('sub_services')->whereIn('sub_services.account_id', $account_ids)
                ->where('status', '!=', 'Deleted')
                ->where('bundle_id', 0)
                ->where('bill_frequency', 1)
                ->where('status', '!=', 'Deleted')
                ->whereRaw('(to_cancel=0 or (to_cancel=1 and cancel_date>"'.$billing->billing_date.'"))')
                ->where('created_at', '<', $billing->billing_date)
                ->where('product_id', $product_id)->get();
            foreach ($subs_current as $r) {
                if ($r->provision_type == 'airtime_contract') {
                    $current_qty = $r->qty;
                } else {
                    $current_qty = $r->qty;
                }
                $subs_current_qty += $current_qty;
            }

            $subs_total = \DB::table('sub_services_lastmonth')
                ->join('crm_products', 'sub_services_lastmonth.product_id', '=', 'crm_products.id')
                ->whereNotIn('sub_services_lastmonth.account_id', $demo_account_ids)
                ->whereIn('sub_services_lastmonth.account_id', $account_ids)
                ->where('crm_products.is_subscription', 1)
                ->where('sub_services_lastmonth.bundle_id', 0)
                ->where('sub_services_lastmonth.bill_frequency', 1)
                ->where('sub_services_lastmonth.status', '!=', 'Deleted')
                ->where('sub_services_lastmonth.status', '!=', 'Pending')
                ->where('sub_services_lastmonth.provision_type', 'NOT LIKE', '%prepaid%')
                ->where('sub_services_lastmonth.created_at', '<', $last_docdate)
                ->where('sub_services_lastmonth.product_id', $product_id)
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
                'billing_id' => $billing->id,
                'customer_type' => $type,
                'retail_price' => $retail_price,
                'reseller_price' => $reseller_price,
                'sort_order' => $product->sort_order,
                'reconciled' => ($billed_total == $subs_total) ? 1 : 0,
            ];
        }
    }

    // aa($list);
    \DB::table('sub_service_summary')->where('billing_id', $billing->id)->delete();
    \DB::table('sub_service_summary')->insert($list);
    update_billing_run_supplier_invoices($billing->id);
}

function schedule_update_renewal_billing_details()
{
    $renewal_billings = \DB::table('acc_billing')
        ->where('billing_type', 'Renewal')
        ->limit(1)
        ->orderBy('id', 'desc')
        ->get();

    foreach ($renewal_billings as $billing) {
        $list = [];
        $date = $billing->billing_date;
        $docids = [];
        $account_ids = [];
        $docids = \DB::table('crm_documents')
            ->where('docdate', $date)
            ->whereIn('doctype', ['Quotation', 'Order', 'Tax Invoice'])
            ->where('billing_type', 'Renewal')
            ->pluck('id')->toArray();
        $docids_usd = [];

        $account_ids_usd = \DB::table('crm_accounts')
            ->where('status', '!=', 'Deleted')
        //->where('renewal_date_billing', 1)
            ->pluck('id')->toArray();

        $all_docids = $docids_usd;

        $subs = \DB::table('sub_services')->whereIn('account_id', $account_ids_usd)->where('last_invoice_date', $date)->where('bundle_id', 0)->where('status', '!=', 'Deleted')->get()->groupBy('product_id');

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
            $billed_total_usd = \DB::table('crm_document_lines')->whereIn('document_id', $docids_usd)->where('product_id', $product_id)->sum('sale_total');
            $prorata_total = \DB::table('crm_document_lines')->whereIn('document_id', $docids)->where('product_id', $product_id)->sum('prorata_difference');
            $prorata_total_usd = \DB::table('crm_document_lines')->whereIn('document_id', $docids_usd)->where('product_id', $product_id)->sum('prorata_difference');
            $billed_total += $prorata_total;
            $billed_total_usd += $prorata_total_usd;

            $subs_current_qty = 0;
            $activation_qty = 0;
            $cancelled_qty = 0;

            $csubs = \DB::table('sub_services')
                ->where('bill_frequency', '!=', 1)
                ->where('product_id', $product_id)
                ->where('status', '!=', 'Deleted')
                ->where('bundle_id', 0)
                ->get();
            foreach ($csubs as $n) {
                $e = \DB::table('sub_services')->whereIn('account_id', $account_ids_usd)->where('last_invoice_date', $date)->where('product_id', $product_id)->where('status', '!=', 'Deleted')->where('id', $n->id)->count();
                if (! $e) {
                    if ($n->provision_type == 'airtime_contract') {
                        $activation_qty += $n->qty;
                    } else {
                        $activation_qty++;
                    }
                }
            }

            $lsubs = \DB::table('sub_services')
                ->where('bill_frequency', '!=', 1)
                ->where('product_id', $product_id)
                ->where('status', '!=', 'Deleted')
                ->whereRaw('to_cancel=1 and cancel_date<"'.$billing->billing_date.'"')
                ->where('bundle_id', 0)
                ->get();
            foreach ($lsubs as $n) {

                if ($n->provision_type == 'airtime_contract') {
                    $cancelled_qty += $n->qty;
                } else {
                    $cancelled_qty++;
                }
            }

            //->where('bill_frequency','!=', 1)
            // ->where('bundle_id',0)
            $subs_current = \DB::table('sub_services')->whereIn('sub_services.account_id', $account_ids_usd)
                ->where('status', '!=', 'Deleted')
                ->whereRaw('(to_cancel=0 or (to_cancel=1 and cancel_date>"'.$billing->billing_date.'"))')
                ->where('product_id', $product_id)->get();
            foreach ($subs_current as $r) {
                $current_qty = 0;

                if ($r->provision_type == 'airtime_contract') {
                    $current_qty = $r->qty * $r->bill_frequency;
                } else {
                    $current_qty = $r->qty * $r->bill_frequency;
                }
                $subs_current_qty += $current_qty;
            }

            $subs_total = \DB::table('sub_services')
                ->join('crm_products', 'sub_services.product_id', '=', 'crm_products.id')
                ->whereIn('sub_services.account_id', $account_ids_usd)
                ->where('crm_products.is_subscription', 1)
                ->where('sub_services.bill_frequency', '!=', 1)
                ->where('sub_services.status', '!=', 'Deleted')
                ->where('sub_services.bundle_id', 0)
                ->where('sub_services.status', '!=', 'Pending')
                ->where('sub_services.provision_type', 'NOT LIKE', '%prepaid%')
                ->where('sub_services.created_at', '<', $date)
                ->where('sub_services.product_id', $product_id)
                ->where('last_invoice_date', $date)
                ->sum(\DB::raw('price'));

            $subs_total_usd = \DB::table('sub_services')
                ->join('crm_products', 'sub_services.product_id', '=', 'crm_products.id')
                ->whereIn('sub_services.account_id', $account_ids_usd)
                ->where('crm_products.is_subscription', 1)
                ->where('sub_services.bill_frequency', '!=', 1)
                ->where('sub_services.status', '!=', 'Deleted')
                ->where('sub_services.bundle_id', 0)
                ->where('sub_services.status', '!=', 'Pending')
                ->where('sub_services.provision_type', 'NOT LIKE', '%prepaid%')
                ->where('sub_services.created_at', '<', $date)
                ->where('sub_services.product_id', $product_id)
                ->where('last_invoice_date', $date)
                ->sum(\DB::raw('price'));
            if (abs($subs_total - $billed_total) < 1) {
                $subs_total = $billed_total;
            }

            if (abs($subs_total_usd - $billed_total_usd) < 1) {
                $subs_total_usd = $billed_total_usd;
            }
            $subs_total_usd = 0;
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
                'billing_id' => $billing->id,
                'retail_price' => $retail_price,
                'reseller_price' => $reseller_price,
                'sort_order' => $product->sort_order,
            ];
        }

        \DB::table('sub_service_summary')->where('billing_id', $billing->id)->delete();
        \DB::table('sub_service_summary')->insert($list);
        update_billing_run_supplier_invoices($billing->id);

    }
}

function button_subscription_summary_manage($request)
{
    $summary = \DB::table('sub_service_summary')->where('id', $request->id)->get()->first();
    $provision_type = \DB::table('sub_services')->where('product_id', $summary->product_id)->pluck('provision_type')->first();

    if ($provision_type == 'pbx_extension' || $provision_type == 'sip_trunk') {
        $extensions = \DB::connection('pbx')->table('v_extensions')
            ->select('v_domains.account_id', 'v_extensions.extension', 'v_extensions.id')
            ->join('v_domains', 'v_domains.domain_uuid', '=', 'v_extensions.domain_uuid')
            ->get();
        foreach ($extensions as $ext) {
            $product_id = \DB::table('sub_services')->where('detail', $ext->extension)->where('account_id', $ext->account_id)->where('status', '!=', 'Deleted')->pluck('product_id')->first();

            \DB::connection('pbx')->table('v_extensions')->where('id', $ext->id)->update(['product_id' => $product_id]);
        }
        $menu_name = get_menu_url_from_table('v_extensions');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);
    }

    if ($provision_type == 'phone_number') {
        $numbers = \DB::connection('pbx')->table('p_phone_numbers')
            ->select('v_domains.account_id', 'p_phone_numbers.number', 'p_phone_numbers.id')
            ->join('v_domains', 'v_domains.domain_uuid', '=', 'p_phone_numbers.domain_uuid')
            ->get();
        foreach ($numbers as $number) {
            $product_id = \DB::table('sub_services')->where('detail', $number->number)->where('account_id', $number->account_id)->where('status', '!=', 'Deleted')->pluck('product_id')->first();

            \DB::connection('pbx')->table('p_phone_numbers')->where('id', $number->id)->update(['product_id' => $product_id]);
        }
        $menu_name = get_menu_url_from_table('p_phone_numbers');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);
    }

    if ($provision_type == 'hosting' || $provision_type == 'sitebuilder') {
        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $summary->product_id)->get();
        foreach ($subs as $s) {

            \DB::table('isp_host_websites')->where('domain', $s->detail)->update(['product_id' => $summary->product_id]);
        }
        $menu_name = get_menu_url_from_table('isp_host_websites');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);
    }

    if ($provision_type == 'domain_name') {
        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $summary->product_id)->get();
        foreach ($subs as $s) {

            \DB::table('isp_host_websites')->where('domain', $s->detail)->update(['domain_product_id' => $summary->product_id]);
        }
        $menu_name = get_menu_url_from_table('isp_host_websites');

        return redirect()->to($menu_name.'?domain_product_id='.$summary->product_id);
    }

    if ($provision_type == 'virtual_server') {
        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $summary->product_id)->get();
        foreach ($subs as $s) {

            \DB::table('isp_virtual_servers')->where('ip_addr', $s->detail)->update(['product_id' => $summary->product_id]);
        }
        $menu_name = get_menu_url_from_table('isp_virtual_servers');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);
    }

    if ($provision_type == 'fibre' || $provision_type == 'fibre_product') {

        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $summary->product_id)->get();
        foreach ($subs as $s) {

            \DB::table('isp_data_fibre')->where('username', $s->detail)->update(['product_id' => $summary->product_id]);
        }
        $menu_name = get_menu_url_from_table('isp_data_fibre');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);

    }

    if ($provision_type == 'lte_sim_card') {
        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $summary->product_id)->get();
        foreach ($subs as $s) {

            \DB::table('isp_data_lte_vodacom_accounts')->where('msisdn', $s->detail)->update(['product_id' => $summary->product_id]);
        }
        $menu_name = get_menu_url_from_table('isp_data_lte_vodacom_accounts');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);
    }
    if ($provision_type == 'mtn_lte_sim_card' || $provision_type == 'telkom_lte_sim_card') {
        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $summary->product_id)->get();
        foreach ($subs as $s) {

            \DB::table('isp_data_lte_axxess_accounts')->where('sim_serialNumber', $s->detail)->update(['product_id' => $summary->product_id]);
        }
        $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);
    }

    if (str_contains($provision_type, 'ip_range')) {
        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $summary->product_id)->get();
        foreach ($subs as $s) {

            \DB::table('isp_data_ip_ranges')->where('ip_range', $s->detail)->update(['product_id' => $summary->product_id]);
        }
        $menu_name = get_menu_url_from_table('isp_data_ip_ranges');

        return redirect()->to($menu_name.'?product_id='.$summary->product_id);
    }

    if ($provision_type == 'airtime_prepaid' || $provision_type == 'airtime_unlimited' || $provision_type == 'airtime_contract') {
        $menu_name = get_menu_url_from_table('v_domains');

        return redirect()->to($menu_name);
    }

    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?product_id='.$summary->product_id);

}
