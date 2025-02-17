<?php

function schedule_provision_invoices()
{
    $ids = \DB::table('crm_documents')->where('doctype', 'Tax Invoice')->where('subscription_created', 0)->pluck('account_id')->toArray();
    foreach ($ids as $id) {
        provision_invoices($id);
    }
}

// add invoices to provisioning table
function provision_invoices($account_id)
{
    // aa('provision_invoices');
    $customer = dbgetaccount($account_id);

    if ($customer->status != 'Deleted' && ($customer->type == 'customer' || $customer->type == 'reseller_user')) {
        if ($customer->type == 'reseller_user') {
            $account_id = $customer->partner_id;
        }

        $invoices = \DB::table('crm_documents')
            ->where('account_id', $account_id)
            ->where('doctype', 'Tax Invoice')
            ->where('billing_type', '')
            ->where('subscription_created', 0)
            ->get();

        foreach ($invoices as $invoice) {
            vd($invoice->id);
            provision_auto($invoice->id);
        }
    }

    if ($customer->type == 'reseller' && $customer->id != 1) {
        $reseller_users = \DB::table('crm_accounts')->where('partner_id', $account_id)->where('status', 'Enabled')->get();
        foreach ($reseller_users as $reseller_user) {
            provision_invoices($reseller_user->id);
            // aa($reseller_user->id);
        }
    }
}

function provision_auto($document_id)
{
    $document = DB::table('crm_documents')->where('id', $document_id)->where('subscription_created', 0)->get()->first();

    // vd($document);
    if ($document && $document->doctype == 'Tax Invoice') {
        // invoice added to provisioning table
        create_provision_items($document);

        \DB::table('crm_documents')->where('id', $document_id)->update(['subscription_created' => 1]);
    }
    // assign_activations_to_salesaman();

    $sql = 'UPDATE sub_activations 
    JOIN crm_accounts ON sub_activations.account_id=crm_accounts.id
    SET sub_activations.partner_id = crm_accounts.partner_id';
    \DB::statement($sql);
}

function create_provision_items($invoice)
{
    // aa('create_provision_items');

    if ($invoice->subscription_created) {
        return false;
    }
    $current_conn = DB::getDefaultConnection();
    $system_user_id = get_system_user_id();
    if (session('instance')->id == 2) {
        return false;
    }

    if ($invoice->doctype == 'Credit Note' || $invoice->doctype == 'Quotation') {
        return false;
    }

    $invoice_query = \DB::table('crm_document_lines')->where('document_id', $invoice->id);
    $sql = querybuilder_to_sql($invoice_query);

    $invoice_lines = \DB::table('crm_document_lines')->where('document_id', $invoice->id)->get();

    // get product bundle activations
    // aa($invoice->id);
    $erp_subscription = new ErpSubs;

    //BUNDLES
    // $bundle_lines = [];
    // foreach($invoice_lines as $i => $line){
    //     // aa($line);
    //     $product = dbgetrow('crm_products', 'id', $line->product_id);
    //     if($product->is_bundle){
    //         // create pending bundle in subscription table

    //         $bundle_id = $erp_subscription->createSubscription($invoice->account_id, $product->id, 'bundle', $invoice->id, $invoice->bill_frequency);
    //         $activation_products = \DB::table('crm_product_bundle_activations')->where('bundle_product_id',$product->id)->get();
    //         foreach($activation_products as $activation_product){
    //             $data = clone($line);
    //             $data->product_id = $activation_product->product_id;
    //             $data->bundle_product_id = $product->id;
    //             $data->bundle_id = $bundle_id;
    //             $data->qty = $activation_product->qty;

    //             $bundle_lines[] = $data;
    //         }
    //         unset($invoice_lines[$i]);
    //     }
    // }

    // foreach($bundle_lines as $bundle_line){
    //     $invoice_lines[] = $bundle_line;
    // }

    if (! empty($invoice->reseller_user)) {
        $customer = dbgetaccount($invoice->reseller_user);
    } else {
        $customer = dbgetaccount($invoice->account_id);
    }

    $dispatch_created = \DB::table('sub_activations')->where('invoice_id', $invoice->id)->count();
    $topups_created = \DB::table('sub_service_topups')->where('invoice_id', $invoice->id)->count();
    $subscription_created = \DB::table('sub_services')->where('invoice_id', $invoice->id)->where('status', '!=', 'Pending')->count();

    // vd($dispatch_created);
    // vd($topups_created);
    // vd($subscription_created);

    if ($dispatch_created || $topups_created || $subscription_created) {
        return false;
    }

    $extension_product_ids = get_activation_type_product_ids('pbx_extension');
    $invoice_has_extensions = \DB::table('crm_document_lines')->where('document_id', $invoice->id)->whereIn('product_id', $extension_product_ids)->count();

    $sip_trunk_product_ids = get_activation_type_product_ids('sip_trunk');
    $invoice_has_sip_trunks = \DB::table('crm_document_lines')->where('document_id', $invoice->id)->whereIn('product_id', $sip_trunk_product_ids)->count();

    $processed_invoice_line_ids = [];
    if ($invoice_has_extensions || $invoice_has_sip_trunks) {
        foreach ($invoice_lines as $invoice_line) {

            if (in_array($invoice_line->product_id, $extension_product_ids) || in_array($invoice_line->product_id, $sip_trunk_product_ids)) {

                $product = dbgetrow('crm_products', 'id', $invoice_line->product_id);
                $provision_type = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();

                if ($provision_type == null) {
                    $provision_type = '';
                }
                $activation_type_id = $product->provision_plan_id;
                if (empty($activation_type_id)) {
                    $activation_type_id = 0;
                }

                if (product_provision_is_automated($product)) {
                    //voice airtime, sms, website design - only add one activation item

                    for ($i = 0; $i < $invoice_line->qty; $i++) {
                        $data = [
                            'account_id' => $customer->id,
                            'invoice_id' => $invoice->id,
                            'product_id' => $invoice_line->product_id,
                            'bill_frequency' => $invoice->bill_frequency,
                            'status' => 'Pending',
                            'created_at' => date('Y-m-d H:i:s'),
                            'created_by' => $invoice->created_by,
                            'provision_type' => $provision_type,
                            'activation_type_id' => $activation_type_id,
                            'step' => 1,
                            'invoice_line_id' => $invoice_line->id,
                        ];
                        if (! empty($invoice_line->bundle_product_id)) {
                            $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                        }

                        if (! empty($invoice_line->bundle_id)) {
                            $data['bundle_id'] = $invoice_line->bundle_id;
                        }

                        $id = dbinsert('sub_activations', $data);
                        $request_data = new \Illuminate\Http\Request;
                        $request_data->id = $id;

                        app('App\Http\Controllers\CustomController')->provisionService($request_data, 'sub_activations', $id);
                    }
                } else {
                    aa('2');

                    for ($i = 0; $i < $invoice_line->qty; $i++) {
                        $data = [
                            'account_id' => $customer->id,
                            'invoice_id' => $invoice->id,
                            'product_id' => $invoice_line->product_id,
                            'bill_frequency' => $invoice->bill_frequency,
                            'created_at' => date('Y-m-d H:i:s'),
                            'status' => 'Pending',
                            'created_by' => $invoice->created_by,
                            'provision_type' => $provision_type,
                            'activation_type_id' => $activation_type_id,
                            'step' => 1,
                            'invoice_line_id' => $invoice_line->id,
                        ];
                        if (! empty($invoice_line->bundle_product_id)) {
                            $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                        }

                        if (! empty($invoice_line->bundle_id)) {
                            $data['bundle_id'] = $invoice_line->bundle_id;
                        }

                        dbinsert('sub_activations', $data);
                    }
                }
                $processed_invoice_line_ids[] = $invoice_line->id;
            }
        }
    }

    foreach ($invoice_lines as $invoice_line) {
        // aa($invoice_line);
        if (in_array($invoice_line->id, $processed_invoice_line_ids)) {
            continue;
        }
        if ($invoice_line->product_id == 147) {
            continue;
        }

        $product = dbgetrow('crm_products', 'id', $invoice_line->product_id);

        $provision_type = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
        if (empty($provision_type)) {
            $provision_type = '';
        }

        $activation_type_id = $product->provision_plan_id;
        if (empty($activation_type_id)) {
            $activation_type_id = 0;
        }

        $airtime_prepaid_ids = get_activation_type_product_ids('airtime_prepaid');
        $airtime_contract_ids = get_activation_type_product_ids('airtime_contract');
        // dd($airtime_contract_ids);
        $website_design_ids = get_activation_type_product_ids('website_design');

        $bulk_sms_prepaid_ids = get_activation_type_product_ids('bulk_sms_prepaid');
        $bulk_sms_contract_ids = get_activation_type_product_ids('bulk_sms_contract');

        $iptv_reseller_credits_ids = get_activation_type_product_ids('iptv_reseller');

        if (empty($product->provision_plan_id) && $product->type == 'Stock') {
            $provision_type = 'product';
            if ($product->is_subscription) {
                $provision_type = 'products_monthly';
            }

            $activation_type_id = $product->provision_plan_id;
            if (empty($activation_type_id)) {
                $activation_type_id = 0;
            }
            for ($i = 0; $i < $invoice_line->qty; $i++) {
                $data = [
                    'account_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'product_id' => $invoice_line->product_id,
                    'bill_frequency' => $invoice->bill_frequency,
                    'qty' => 1,
                    'status' => 'Pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $invoice->created_by,
                    'step' => 1,
                    'provision_type' => $provision_type,
                    'activation_type_id' => $activation_type_id,
                    'invoice_line_id' => $invoice_line->id,
                ];

                if (! empty($invoice_line->bundle_product_id)) {
                    $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                }

                if (! empty($invoice_line->bundle_id)) {
                    $data['bundle_id'] = $invoice_line->bundle_id;
                }
                dbinsert('sub_activations', $data);
            }
        } elseif (! $product->is_subscription
        && ! in_array($invoice_line->product_id, $airtime_prepaid_ids) && ! in_array($invoice_line->product_id, $airtime_contract_ids)
        && ! in_array($invoice_line->product_id, $bulk_sms_prepaid_ids) && ! in_array($invoice_line->product_id, $bulk_sms_contract_ids) && ! in_array($invoice_line->product_id, $iptv_reseller_credits_ids)) {
            if ($product->type == 'Stock') {
                aa('1.1');
                aa($data);
                $activation_type_id = $product->provision_plan_id;
                if (empty($activation_type_id)) {
                    $activation_type_id = 0;
                    $provision_type = 'product';
                }

                $data = [
                    'account_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'product_id' => $invoice_line->product_id,
                    'bill_frequency' => $invoice->bill_frequency,
                    'qty' => $invoice_line->qty,
                    'status' => 'Pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $invoice->created_by,
                    'step' => 1,
                    'provision_type' => $provision_type,
                    'activation_type_id' => $activation_type_id,
                    'invoice_line_id' => $invoice_line->id,
                ];

                if (! empty($invoice_line->bundle_product_id)) {
                    $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                }

                if (! empty($invoice_line->bundle_id)) {
                    $data['bundle_id'] = $invoice_line->bundle_id;
                }

                dbinsert('sub_activations', $data);
            } elseif (in_array($invoice_line->product_id, $website_design_ids)) {
                aa('1.2');
                aa($data);

                $data = [
                    'account_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'product_id' => $invoice_line->product_id,
                    'bill_frequency' => $invoice->bill_frequency,
                    'qty' => $invoice_line->qty,
                    'status' => 'Pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $invoice->created_by,
                    'step' => 1,
                    'provision_type' => $provision_type,
                    'activation_type_id' => $activation_type_id,
                    'invoice_line_id' => $invoice_line->id,
                ];

                if (! empty($invoice_line->bundle_product_id)) {
                    $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                }

                if (! empty($invoice_line->bundle_id)) {
                    $data['bundle_id'] = $invoice_line->bundle_id;
                }

                dbinsert('sub_activations', $data);

            } else {
                aa('1.3');
                aa($data);
                for ($i = 0; $i < $invoice_line->qty; $i++) {
                    $data = [
                        'account_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'product_id' => $invoice_line->product_id,
                        'bill_frequency' => $invoice->bill_frequency,
                        'qty' => 1,
                        'status' => 'Pending',
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => $invoice->created_by,
                        'step' => 1,
                        'provision_type' => $provision_type,
                        'activation_type_id' => $activation_type_id,
                        'invoice_line_id' => $invoice_line->id,
                    ];

                    if (! empty($invoice_line->bundle_product_id)) {
                        $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                    }

                    if (! empty($invoice_line->bundle_id)) {
                        $data['bundle_id'] = $invoice_line->bundle_id;
                    }

                    dbinsert('sub_activations', $data);

                    // add domain to activation for sitebuilder product
                    if ($invoice_line->product_id == 856) {
                        $data['product_id'] = 760;
                        $data['provision_type'] = 'domain_name';
                        $data['activation_type_id'] = 8;
                        dbinsert('sub_activations', $data);
                    }
                }
            }
        } else {
            // automated provisioning
            if (empty($product->provision_plan_id)) {
                $provision_type = '';
            }
            if (product_provision_is_automated($product)) {
                //voice airtime, sms, website design - only add one activation item
                if (in_array($invoice_line->product_id, $airtime_contract_ids) || in_array($invoice_line->product_id, $airtime_prepaid_ids)
                || in_array($invoice_line->product_id, $bulk_sms_prepaid_ids) || in_array($invoice_line->product_id, $bulk_sms_contract_ids) || in_array($invoice_line->product_id, $iptv_reseller_credits_ids)) {

                    aa('2.1');
                    // aa($data);
                    $product = \DB::table('crm_products')->where('id', $invoice_line->product_id)->get()->first();
                    $package_amount = $product->provision_package;
                    if ($invoice->reference == 'Auto Airtime Allocation') {
                        $provision_amount = $invoice_line->qty * $invoice_line->price;
                    } elseif ($customer->partner_id != 1) {
                        $provision_amount = $invoice_line->qty * $package_amount;
                    } elseif ($invoice_line->price > $package_amount || $customer->currency == 'USD') {
                        $provision_amount = $invoice_line->qty * $invoice_line->price;
                        if ($customer->currency == 'ZAR') {
                            $provision_amount = $provision_amount * 1.15;
                        }
                    } else {
                        $provision_amount = $invoice_line->qty * $package_amount;
                    }

                    $data = [
                        'account_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'product_id' => $invoice_line->product_id,
                        'bill_frequency' => $invoice->bill_frequency,
                        'status' => 'Pending',
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => $invoice->created_by,
                        'step' => 1,
                        'provision_amount' => $provision_amount,
                        'activation_type_id' => $activation_type_id,
                        'invoice_line_id' => $invoice_line->id,
                    ];

                    if (! empty($invoice_line->bundle_product_id)) {
                        $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                    }

                    if (! empty($invoice_line->bundle_id)) {
                        $data['bundle_id'] = $invoice_line->bundle_id;
                    }

                    $subscription_id = \DB::table('sub_services')
                        ->where('account_id', $customer->id)->where('product_id', $invoice_line->product_id)
                        ->where('status', '!=', 'Deleted')->pluck('id')->first();

                    $data['subscription_id'] = $subscription_id;

                    $id = dbinsert('sub_service_topups', $data);

                    $request_data = new \Illuminate\Http\Request;
                    $request_data->id = $id;

                    if (empty($subscription_id) && ! $invoice_has_sip_trunks && ! $invoice_has_extensions) {
                        app('App\Http\Controllers\CustomController')->provisionService($request_data, 'sub_activations', $id);
                    }
                } else {
                    aa('2.2');
                    aa($data);
                    for ($i = 0; $i < $invoice_line->qty; $i++) {
                        $data = [
                            'account_id' => $customer->id,
                            'invoice_id' => $invoice->id,
                            'product_id' => $invoice_line->product_id,
                            'bill_frequency' => $invoice->bill_frequency,
                            'status' => 'Pending',
                            'created_at' => date('Y-m-d H:i:s'),
                            'created_by' => $invoice->created_by,
                            'provision_type' => $provision_type,
                            'activation_type_id' => $activation_type_id,
                            'step' => 1,
                            'invoice_line_id' => $invoice_line->id,
                        ];

                        if (! empty($invoice_line->bundle_product_id)) {
                            $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                        }

                        if (! empty($invoice_line->bundle_id)) {
                            $data['bundle_id'] = $invoice_line->bundle_id;
                        }

                        $id = dbinsert('sub_activations', $data);
                        if (! $invoice_has_sip_trunks && ! $invoice_has_extensions) {
                            $request_data = new \Illuminate\Http\Request;
                            $request_data->id = $id;

                            app('App\Http\Controllers\CustomController')->provisionService($request_data, 'sub_activations', $id);
                        }
                    }
                }
            } else {
                aa('2.3');
                aa($data);

                for ($i = 0; $i < $invoice_line->qty; $i++) {
                    $data = [
                        'account_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'product_id' => $invoice_line->product_id,
                        'bill_frequency' => $invoice->bill_frequency,
                        'created_at' => date('Y-m-d H:i:s'),
                        'status' => 'Pending',
                        'created_by' => $invoice->created_by,
                        'provision_type' => $provision_type,
                        'activation_type_id' => $activation_type_id,
                        'step' => 1,
                        'invoice_line_id' => $invoice_line->id,
                    ];

                    if (! empty($invoice_line->bundle_product_id)) {
                        $data['bundle_product_id'] = $invoice_line->bundle_product_id;
                    }

                    if (! empty($invoice_line->bundle_id)) {
                        $data['bundle_id'] = $invoice_line->bundle_id;
                    }
                    dbinsert('sub_activations', $data);
                }
            }
        }
        $sub = new ErpSubs;
        $sub->updateProductPrices($invoice_line->product_id);
    }

    \DB::table('sub_activations')->where('status', 'Pending')->whereIn('provision_type', ['domain_name', 'domain_name_international'])->delete();

    /*
    $account_ids = \DB::table('crm_accounts')->where('partner_id', 1)->pluck('id')->toArray();
    \DB::table('sub_activations')->update(['internal' => 0]);
    \DB::table('sub_activations')->whereIn('account_id', $account_ids)->update(['internal' => 1]);
    \DB::table('sub_activations')->where('product_id', 126)->update(['internal' => 1]);

    $product_ids = get_activation_type_product_ids('fibre');
    \DB::table('sub_activations')->whereIn('product_id', $product_ids)->update(['internal' => 1]);
    $product_ids = get_activation_type_product_ids('fibre_product');
    \DB::table('sub_activations')->whereIn('product_id', $product_ids)->update(['internal' => 1]);
    $product_ids = get_activation_type_product_ids('telkom_lte_sim_card');
    \DB::table('sub_activations')->whereIn('product_id', $product_ids)->update(['internal' => 1]);
    $product_ids = get_activation_type_product_ids('mtn_lte_sim_card');
    \DB::table('sub_activations')->whereIn('product_id', $product_ids)->update(['internal' => 1]);
    $product_ids = get_activation_type_product_ids('lte_sim_card');
    \DB::table('sub_activations')->whereIn('product_id', $product_ids)->update(['internal' => 1]);
    */
    \DB::table('sub_activations')->update(['internal' => 0]);
    $product_ids = get_activation_type_product_ids('number_porting');
    \DB::table('sub_activations')->whereIn('product_id', $product_ids)->update(['internal' => 1]);

}

function get_allowed_tld_types_from_invoice($invoice_id)
{
    $hosting_product_ids = get_activation_type_product_ids('hosting');
    $domain_name_product_ids = get_activation_type_product_ids('domain_name');

    $num_international_available = 0;
    $num_international_processed = 0;

    $num_local_available = 0;
    $num_local_processed = 0;

    $processed_domain_names = \DB::table('sub_activations')->whereIn('product_id', $hosting_product_ids)->where('invoice_id', $invoice_id)->where('detail', '>', '')->pluck('detail')->toArray();

    foreach ($processed_domain_names as $domain_name) {
        if (is_local_domain($domain_name)) {
            $num_local_processed++;
        } else {
            $num_international_processed++;
        }
    }

    $products = \DB::table('crm_products')->whereIn('id', $domain_name_product_ids)->get();
    foreach ($products as $product) {

        if ($product->provision_package == 'international') {
            $num_international_available = \DB::table('crm_document_lines')->where('document_id', $invoice_id)->where('product_id', $product->id)->sum('qty');
        }
        if ($product->provision_package == 'local') {
            $num_local_available = \DB::table('crm_document_lines')->where('document_id', $invoice_id)->where('product_id', $product->id)->sum('qty');
        }
    }

    $tlds_to_process = [
        'num_international_available' => $num_international_available,
        'num_international_processed' => $num_international_processed,
        'num_local_available' => $num_local_available,
        'num_local_processed' => $num_local_processed,
        'international' => $num_international_available - $num_international_processed,
        'local' => $num_local_available - $num_local_processed,
    ];

    return $tlds_to_process;

}

function schedule_activations_notification()
{
    update_admin_only_activations();
    $account_ids = \DB::table('sub_activations')->where('sub_activations.status', 'Pending')->where('internal', 0)->where('is_deleted', 0)->where('admin_only_step', 0)->pluck('account_id')->unique()->toArray();
    foreach ($account_ids as $account_id) {
        $num_pending = \DB::table('sub_activations')->where('account_id', $account_id)->where('is_deleted', 0)->where('admin_only_step', 0)->where('sub_activations.status', 'Pending')->count();
        if ($num_pending > 0) {
            $activations = \DB::table('sub_activations')->where('account_id', $account_id)->where('is_deleted', 0)->where('admin_only_step', 0)->where('sub_activations.status', 'Pending')->orderBy('created_at')->get();
            $pending_list = '';
            foreach ($activations as $activation) {
                $product_code = \DB::table('crm_products')->where('id', $activation->product_id)->pluck('code')->first();
                $company = \DB::table('crm_accounts')->where('id', $activation->account_id)->pluck('company')->first();
                $pending_list .= '<br>Company: '.$company.',<br> Product: '.$product_code.',<br> Created: '.date('Y-m-d', strtotime($activation->created_at)).'<br>';
            }

            $user_id = \DB::table('erp_users')->where('account_id', $account_id)->where('is_deleted', 0)->pluck('id')->first();
            $login_data = [
                'account_id' => $account_id,
                'user_id' => $user_id,
                'route' => get_menu_url_from_module_id(554),
            ];
            $login_link_encoded = \Erp::encode($login_data);
            $login_link = '<a href="https://'.session('instance')->domain_name.'/autoLogin?login_data='.$login_link_encoded.'" style="text-decoration: none;background:green; color: white; text-align:center; padding:5px;">Click here to activate your services</a>';
            $data = [
                'num_pending' => $num_pending,
                'pending_list' => $pending_list,
                'function_name' => 'schedule_activations_notification',
                'login_link' => $login_link,
            ];
            //$data['test_debug'] = 1;
            $account = dbgetaccount($account_id);
            if ($account->partner_id != 1) {
                $account_id = $account->partner_id;
            }
            // $data['test_debug'] = 1;
            // cc customer care
            $data['cc_email'] = \DB::table('erp_users')->where('account_id', 1)->where('role_id', 64)->where('is_deleted', 0)->pluck('email')->first();
            erp_process_notification($account_id, $data);
        }
    }
}

function onload_activations_validate_status_activations()
{
    \DB::table('sub_activations')->whereNull('created_at')->update(['created_at' => date('Y-m-d H:i:s')]);
    $activations = \DB::table('sub_activations')->where('status', 'Pending')->where('provision_type', 'status_enabled')->get();
    foreach ($activations as $a) {
        $account = dbgetaccount($a->account_id);
        if ($account->status == 'Disabled' || $account->status == 'Deleted') {
            \DB::table('sub_activations')->where('id', $a->id)->delete();
        }
    }
    $activations = \DB::table('sub_activations')->where('status', 'Pending')->where('provision_type', 'status_disabled')->get();
    foreach ($activations as $a) {
        $account = dbgetaccount($a->account_id);
        if ($account->status == 'Enabled' || $account->status == 'Deleted') {
            \DB::table('sub_activations')->where('id', $a->id)->delete();
        }
    }
    if (session('instance')->id == 11) {
        $product_ids = \DB::table('crm_products')->where('provision_plan_id', 102)->pluck('id')->toArray();
        $deleted_subs = \DB::table('sub_services')->where('status', 'Deleted')->whereIn('product_id', $product_ids)->get();
        foreach ($deleted_subs as $deleted_sub) {
            $reenabled = \DB::table('sub_services')->where('status', '!=', 'Deleted')->whereIn('product_id', $product_ids)->where('detail', $deleted_sub->detail)->count();
            if ($reenabled) {
                \DB::table('sub_services')->where('id', $deleted_sub->id)->delete();
            }
        }
    }
}

function button_view_activation_steps($request)
{
    $steps = \DB::table('sub_activation_steps')->where('provision_id', $request->id)->orderby('id')->get();

    $html = '<table class="table">';
    $html .= '<tr><th>Step</th><th>Completed</th><th>Input</th></tr>';
    foreach ($steps as $i => $step) {
        $html .= '<tr>';
        $html .= '<td>';
        $html .= $i;
        $html .= '</td>';
        $html .= '<td>';
        $html .= $step->completed;
        $html .= '</td>';
        $html .= '<td>';
        $html .= $step->input;
        $html .= '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    echo $html;
}

// function build_provision_plan_functions(){
//     $file_name = app_path().'/Helpers/sub_activation_plans_forms.php';
//     $provision_forms = \DB::select('select * from crm_products where status = "Enabled" and provision_form > ""');

//     foreach($provision_forms as $product){
//         $function_name =  'provisionform_'.$product->code;
//         $function_code = $product->provision_form;
//         $function = 'function '.$function_name.'($provision){';
//         $function .= PHP_EOL.$function_code.PHP_EOL;
//         $function .= '}';
//         file_put_contents($file_name, PHP_EOL.$function.PHP_EOL , FILE_APPEND | LOCK_EX);
//     }
// }

function afterdelete_set_provision_status($request)
{
    $op = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    \DB::table('crm_documents')->where('id', $op->invoice_id)->update(['subscription_created' => 1]);
}

function product_provision_is_automated($product)
{
    if (empty($product->provision_plan_id)) {
        return false;
    }
    $provision_id = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('id')->first();

    $num_steps = \DB::table('sub_activation_plans')->where('activation_type_id', $provision_id)->where('status', 'Enabled')->count();
    $num_automated_steps = \DB::table('sub_activation_plans')->where('automated', 1)->where('activation_type_id', $provision_id)->where('status', 'Enabled')->count();

    if ($num_steps == $num_automated_steps) {
        return true;
    }

    return false;
}

function assign_activations_to_salesaman()
{

    \DB::table('sub_activations')->update(['salesman_id' => 0]);
    $sql = 'UPDATE sub_activations 
    JOIN crm_documents ON sub_activations.invoice_id=crm_documents.id
    SET sub_activations.salesman_id = crm_documents.salesman_id
    WHERE sub_activations.salesman_id=0';
    \DB::statement($sql);
    $sql = 'UPDATE sub_activations 
    JOIN crm_accounts ON sub_activations.account_id=crm_accounts.id
    SET sub_activations.salesman_id = crm_accounts.salesman_id
    WHERE sub_activations.salesman_id=0';
    \DB::statement($sql);
    /*
    //Fulfillment - Only Jibril and Ismail (Digital Marketing Products) - Add to processes

    $jibril_user_id = \DB::table('erp_users')->where('account_id',1)->where('username','jibril')->pluck('id')->first(); // jibril
    $ismail_user_id = \DB::table('erp_users')->where('account_id',1)->where('username','ismail')->pluck('id')->first(); // jibril
    $kola_user_id = \DB::table('erp_users')->where('account_id',1)->where('username','kola')->pluck('id')->first(); // jibril

    $marketing_product_ids = \DB::table('crm_products')->where('product_category_id',1151)->pluck('id')->toArray();

    \DB::table('sub_activations')->whereIn('product_id',$marketing_product_ids)->update(['salesman_id'=>$ismail_user_id]); // ismail
    \DB::table('sub_activations')->whereNotIn('product_id',$marketing_product_ids)->where('provision_type','like','%fibre%')->update(['salesman_id'=>$jibril_user_id]);
    \DB::table('sub_activations')->whereNotIn('product_id',$marketing_product_ids)->where('provision_type','like','%ip_range%')->update(['salesman_id'=>$jibril_user_id]);
    \DB::table('sub_activations')->whereNotIn('product_id',$marketing_product_ids)->update(['salesman_id'=>$kola_user_id]); // jibril

    schedule_update_fulfillment_board();
    return false;


    \DB::table('sub_activations')->update(['salesman_id'=>0]);
    $sql = "UPDATE sub_activations
    JOIN crm_documents ON sub_activations.invoice_id=crm_documents.id
    SET sub_activations.salesman_id = crm_documents.salesman_id
    WHERE sub_activations.salesman_id=0";
    \DB::statement($sql);
    $sql = "UPDATE sub_activations
    JOIN crm_accounts ON sub_activations.account_id=crm_accounts.id
    SET sub_activations.salesman_id = crm_accounts.salesman_id
    WHERE sub_activations.salesman_id=0";
    \DB::statement($sql);

    $user_id = \DB::table('erp_users')->where('account_id',1)->where('username','jibril')->pluck('id')->first(); // jibril
    \DB::table('sub_activations')->where('provision_type','like','%fibre%')->update(['salesman_id'=>$user_id]);
    \DB::table('sub_activations')->where('provision_type','like','%ip_range%')->update(['salesman_id'=>$user_id]);
    \DB::table('sub_activations')->where('salesman_id',0)->update(['salesman_id'=>$user_id]);


    \DB::table('sub_activations')->where('salesman_id',1)->update(['salesman_id'=>$user_id]);
    */
}

function provision_checklist_status($provision, $customer)
{
    if ($customer->partner_id != 1) {
        return true;
    }

    $document_id = $provision->document_id;
    $product_id = $provision->product_id;
    $checklist = \DB::table('crm_products')->where('id', $product_id)->pluck('provision_checklist')->first();

    if (empty($checklist)) {
        return true;
    }

    $checklist_status = $provision->checklist;
    $checklist_status = json_decode(trim($checklist_status), true);

    if (empty($checklist_status)) {
        return false;
    }

    $items = explode(PHP_EOL, $checklist);

    foreach ($items as $item) {
        $item_id = strtolower(str_replace(' ', '_', trim($item)));
        if (trim($item_id) != '') {
            //$item_id = preg_replace('#[^a-zA-Z_]#', '', $item_id);

            if (! empty($checklist_status[$item_id]) && $checklist_status[$item_id] == '1') {
                continue;
            } else {
                return false;
            }
        }
    }

    return true;
}

function build_provision_form($provision, $provision_plan, $service_table)
{
    $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
    $input = \DB::table('sub_activation_steps')
        ->where('service_table', $service_table)
        ->where('provision_id', $provision->id)
        ->where('provision_plan_id', $provision_plan->id)
        ->pluck('input')->first();
    $input = json_decode(trim($input), true);
    $function_name = function_format($provision_plan->name);
    $form_function = 'provision_'.$function_name.'_form';

    if (! empty($provision_plan->function_name)) {
        $form_function = $provision_plan->function_name.'_form';
    }

    if (! function_exists($form_function)) {
        return false;
    }
    $customer = dbgetaccount($provision->account_id);
    $form = '';

    $form .= $form_function($provision, $input, $product, $customer);

    return $form;
}

function build_provision_email($provision)
{
    $form = '';
    $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();

    if ($product->provision_email > '') {
        $product_name = ucwords(str_replace('_', ' ', $product->code));
        //if($provision->email_done)
        //$form .= '<a class="k-button" href="javascript://ajax" disabled>Send Email</a><br><br>';
        //else
        $form .= '<a class="k-button" href="javascript://ajax" onclick="sendprovisionemail();">Send Email</a><br><br>';

        $form .= '<script>
       
            function sendprovisionemail(){
             $(".modal").modal("hide");
                   BootstrapDialog.show({
                    id: "formModal",
                    title: "Send Provision Email",
                    message: $("<div></div>").load("email_form/provision/'.$provision->id.'"),
                    onhide: function(dialog) {
                        $("#formModal").html("");
                         tinyMCE.editors[0].editorManager.remove();
                    },
                }); 
                return false;
            }
       
        </script>';
    }

    return $form;
}

function build_provision_checklist($provision, $provision_plan, $service_table)
{
    //create checklists
    $form = '';
    if ($provision_plan->id == 46) {
        $checklist = [];
        $products = \DB::table('crm_document_lines as cdl')
            ->select('cdl.qty as qty', 'cp.*')
            ->join('crm_products as cp', 'cdl.product_id', '=', 'cp.id')
            ->where('cdl.document_id', $provision->invoice_id)
            ->get();
        $invoice = \DB::table('crm_documents')->where('id', $provision->invoice_id)->get()->first();
        foreach ($products as $product) {
            if ($product->type == 'Stock') {
                $checklist[] = $product->qty.' x '.$product->code.' - '.$product->name;
            }
        }
        $checklist[] = 'Delivery Type: '.$invoice->delivery;
        // $checklist[] = 'Delivery Fee: '.$invoice->delivery_fee;
    } else {
        $checklist = explode(PHP_EOL, $provision_plan->step_checklist);
    }
    $step_record = \DB::table('sub_activation_steps')
        ->where('service_table', $service_table)
        ->where('provision_plan_id', $provision_plan->id)
        ->where('provision_id', $provision->id)
        ->get()->first();
    $checklist_input = [];
    if (! empty($step_record) && ! empty($step_record->input)) {
        $checklist_input = json_decode($step_record->input);
    }

    foreach ($checklist as $i => $list_item) {
        $item_id = 'checklist_item_'.$i;
        $checked = (in_array($item_id, $checklist_input)) ? 'checked' : '';
        $form .= '<label for="'.$item_id.'" >'.$list_item.'</label><br>';
        $form .= '<input type="checkbox" name="'.$item_id.'" id="'.$item_id.'" '.$checked.' ><br>';
    }

    return $form;
}

function limit_provision_products($rows)
{
    $filtered_rows = [];
    foreach ($rows as $row) {
        if ($row->provision_plan_id == 46) {
            if (session('role_id') > 10) {
                continue;
            }
        }
        $filtered_rows[] = $row;
    }

    return $filtered_rows;
}

function get_activation_email_data($provision_plan_name, $sub, $customer, $provision = false, $service_table = false)
{
    $mail_data = [];
    $mail_data['activation_email'] = true;

    if ($provision_plan_name == 'pbx_extension') {
        $details = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $customer->domain_uuid)->where('extension', $sub->detail)->get()->first();
        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'v_extensions') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }
        $details->voicemail_password = \DB::connection('pbx')->table('v_voicemails')->where('voicemail_id', $details->extension)->where('domain_uuid', $details->domain_uuid)->pluck('voicemail_password')->first();
        $mail_data['username'] = $details->extension;
        $mail_data['password'] = $details->password;
        $mail_data['cidr'] = $details->cidr;
        $mail_data['domain_name'] = $details->accountcode;
        $mail_data['details'] = $details;
    }

    if ($provision_plan_name == 'sip_trunk') {
        $details = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $customer->domain_uuid)->where('extension', $sub->detail)->get()->first();
        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'v_extensions') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }
        $details->voicemail_password = \DB::connection('pbx')->table('v_voicemails')->where('voicemail_id', $details->extension)->where('domain_uuid', $details->domain_uuid)->pluck('voicemail_password')->first();
        $mail_data['username'] = $details->extension;
        $mail_data['password'] = $details->password;
        $mail_data['cidr'] = $details->cidr;
        $mail_data['domain_name'] = $details->accountcode;
        $mail_data['details'] = $details;
    }

    if ($provision_plan_name == 'phone_number') {
        $details = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->get()->first();
        if ($details->number_routing) {
            $mail_data['routing_type'] = ucwords(str_replace('_', ' ', $details->routing_type));
            $mail_data['number_routing'] = $details->number_routing;
        }
    }

    if ($provision_plan_name == 'fibre_product') {
        $details = \DB::table('isp_data_fibre')->where('account_id', $sub->account_id)->where('username', $sub->detail)->get()->first();
        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'isp_data_fibre') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }
        $mail_data['username'] = $details->username;
        $mail_data['password'] = $details->fibre_password;
        $mail_data['details'] = $details;
        $wifi_username = explode('@', $details->username);
        $mail_data['wifi_username'] = strtolower($wifi_username[0]);
        $mail_data['wifi_password'] = 'zyx123';

    }

    if ($provision_plan_name == 'fibre') {
        $details = \DB::table('isp_data_fibre')->where('account_id', $sub->account_id)->where('username', $sub->detail)->get()->first();
        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'isp_data_fibre') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }
        $mail_data['username'] = $details->username;
        $mail_data['password'] = $details->fibre_password;
        $mail_data['details'] = $details;
        $wifi_username = explode('@', $details->username);
        $mail_data['wifi_username'] = strtolower($wifi_username[0]);
        $mail_data['wifi_password'] = 'zyx123';
    }

    if (str_contains($provision_plan_name, 'iptv')) {
        $details = \DB::table('isp_data_iptv')->where('account_id', $sub->account_id)->where('username', $sub->detail)->get()->first();

        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'isp_data_iptv') {
                        $details = (object) $insert_data;

                    }
                }
            }
        }

        $mail_data['username'] = $details->username;
        $mail_data['password'] = $details->password;

    }

    if ($provision_plan_name == 'lte_sim_card') {
        $details = \DB::table('isp_data_lte_vodacom_accounts')->where('account_id', $sub->account_id)->where('msisdn', $sub->detail)->get()->first();
        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'isp_data_lte_vodacom_accounts') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }
        $mail_data['details'] = $details;
    }

    if ($provision_plan_name == 'sitebuilder' || $provision_plan_name == 'sitebuilderaddon') {
        $details = \DB::table('isp_host_websites')->where('account_id', $sub->account_id)->where('domain', $sub->detail)->get()->first();

        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'isp_host_websites') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }

        $mail_data['domain'] = $sub->detail;
        $mail_data['domain_login'] = $sub->detail.'/admin';
        $mail_data['username'] = $details->ftp_user;
        $mail_data['password'] = $details->ftp_pass;
    }

    if ($provision_plan_name == 'virtual_server') {
        $details = \DB::table('isp_data_virtual_servers')->where('account_id', $sub->account_id)->where('ip_addr', $sub->detail)->get()->first();

        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'isp_data_virtual_servers') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }
        $mail_data['ip_addr'] = $details->ip_addr;
        $mail_data['server_username'] = $details->server_username;
        $mail_data['server_pass'] = $details->server_pass;
        $mail_data['server_os'] = $details->server_os;

        $mail_data['admin_url'] = $details->admin_url;
        $mail_data['admin_password'] = $details->admin_password;
        $mail_data['admin_username'] = $details->admin_username;

    }
    if (str_contains($provision_plan_name, 'ip_range')) {
        $details = \DB::table('isp_data_ip_ranges')->where('account_id', $sub->account_id)->where('ip_range', $sub->detail)->get()->first();

        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'isp_data_ip_ranges') {
                        $details = (object) $insert_data;
                    }
                }
            }
        }

        $mail_data['loa_as_number'] = '';

        $iprange = $details;
        if ($provision_plan_name == 'ip_range_route') {
            $mail_data['loa_as_number'] = $details->loa_as_number;
            $file = 'IP Authorization Letter '.str_replace('/24', '', $iprange->ip_range).'.pdf';
            $filename = attachments_path().$file;
            if (file_exists($filename)) {
                unlink($filename);
            }
            $admin = dbgetaccount(1);
            $customer = dbgetaccount($iprange->account_id);
            $pdfdata = [
                'admin' => $admin,
                'helpdesk_email' => get_admin_setting('notification_support'),
                'customer' => $customer,
                'iprange' => $iprange,
            ];
            $pdfdata['loa_company'] = (! empty($iprange->loa_company)) ? $iprange->loa_company : $customer->company;
            $pdfdata['logo_path'] = uploads_settings_path().$admin->logo;
            $pdfdata['logo'] = settings_url().$admin->logo;
            $pdf = \PDF::loadView('__app.exports.ipranges_loa', $pdfdata);
            $options = [
                'orientation' => 'portrait',
                'encoding' => 'UTF-8',
                'footer-left' => 'Statement | '.$account->company,
                'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
                'footer-font-size' => 8,
            ];

            //return view('__app.exports.ipranges_loa', $data);

            $pdf->setOptions($options);

            $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
            $mail_data['attachment'] = $file;
        }
    }

    if ($provision_plan_name == 'hosting') {

        $domain = $sub->detail;

        $site = \DB::table('isp_host_websites')->where('domain', $domain)->get()->first();
        if ($site) {
            panel_to_siteworx($site->account_id, $site->domain, $site->package);
            $username = $site->username;
        }
        $password = substr(\Erp::encode($domain), 0, 20);

        if (empty($username)) {
            $username = $customer->email;
        }

        $url = 'https://host2.cloudtools.co.za:2443/siteworx/index?action=login&email='.$username.'&password='.$password.'&domain='.$domain;

        $mail_data['hosting_url'] = 'https://host2.cloudtools.co.za:2443/siteworx/';
        $mail_data['hosting_login_url'] = $url;
        $mail_data['username'] = $username;
        $mail_data['password'] = $password;
    }

    if ($provision_plan_name == 'airtime_prepaid') {

        $airtime_product_ids = get_activation_type_product_ids('airtime_prepaid');

        $invoice_lines = \DB::table('crm_document_lines')
            ->where('document_id', $provision->invoice_id)
            ->whereIn('product_id', $airtime_product_ids)
            ->get();
        $airtime = 0;
        foreach ($invoice_lines as $invoice_line) {
            $provision_price = $invoice_line->price;
            $airtime .= ($invoice_line->qty * $provision_price);
        }
        $mail_data['amount'] = currency($airtime);
    }

    if ($provision_plan_name == 'bulk_sms_prepaid') {
        $invoice_id = \DB::table('sub_service_topups')->where('account_id', $sub->account_id)->where('product_id', 101)->orderby('invoice_id', 'desc')->pluck('invoice_id')->first();
        if (! $invoice_id) {
            $invoice_id = $sub->invoice_id;
        }
        $invoice_line = \DB::table('crm_document_lines')->where(['document_id' => $invoice_id, 'product_id' => 101])->get()->first();

        $mail_data['amount'] = $invoice_line->qty;
    }

    if (str_contains($provision_plan_name, 'teamoffice')) {
        $details = \DB::table('crm_team_office_accounts')->where('account_id', $sub->account_id)->where('username', $sub->detail)->get()->first();

        if (empty($details) && $provision && $service_table) {
            $table_data_list = provision_get_table_data($provision->id, $service_table);
            if (! empty($table_data_list) && is_array($table_data_list)) {
                foreach ($table_data_list as $insert_table => $insert_data) {
                    if ($insert_table == 'crm_team_office_accounts') {
                        $details = (object) $insert_data;

                    }
                }
            }
        }

        $mail_data['domain_name'] = $details->domain_name;
        $mail_data['username'] = $details->username;
        $mail_data['password'] = $details->password;

    }

    // aa($mail_data);
    return $mail_data;
}
