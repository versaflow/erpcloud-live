<?php

class ErpSubs
{
    public $subscription;

    public $test_cache;

    public function __construct() {}

    public function setStatus($account_id, $status)
    {
        \DB::table('sub_services')->where('account_id', $account_id)->where('status', '!=', 'Deleted')->update(['status' => $status]);

        $app_ids = get_installed_app_ids();
        if (in_array(8, $app_ids)) {

            // FIBRE
            /*
            $fibre_accounts =  \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'fibre'])->where('status', '!=', 'Deleted')->get();
            if (!empty($fibre_accounts)) {
                $axxess = new Axxess();
                foreach ($fibre_accounts as $fibre_sub) {
                    $fibre = \DB::table('isp_data_fibre')->where('username', $fibre_sub->detail)->get()->first();

                    if($fibre->provider == 'Axxess'){
                        if ('Enabled' == $status) {
                            $axxess->funcLiftSuspend($fibre->guidServiceId);
                        }else{
                            $axxess->funcSuspend($fibre->guidServiceId);
                        }
                    }
                }
            }
            */
            $telkom_lte_accounts = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'telkom_lte_sim_card'])->where('status', '!=', 'Deleted')->get();
            if (! empty($telkom_lte_accounts)) {
                $axxess = new Axxess;
                foreach ($telkom_lte_accounts as $telkom_lte_account) {
                    $telkom_lte = \DB::table('isp_data_lte_axxess_accounts')->where('reference', $telkom_lte_account->detail)->orWhere('sim_serialNumber', $telkom_lte_account->detail)->get()->first();
                    if ($status == 'Enabled') {
                        $axxess->funcLiftSuspend($telkom_lte->guidServiceId);
                    } else {
                        $axxess->funcSuspend($telkom_lte->guidServiceId);
                    }
                }
            }
            $mtn_lte_accounts = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'mtn_lte_sim_card'])->where('status', '!=', 'Deleted')->get();
            if (! empty($mtn_lte_accounts)) {
                $axxess = new Axxess;
                foreach ($mtn_lte_accounts as $mtn_lte_account) {
                    $mtn_lte = \DB::table('isp_data_lte_axxess_accounts')->where('reference', $mtn_lte_account->detail)->orWhere('sim_serialNumber', $mtn_lte_account->detail)->get()->first();
                    if ($status == 'Enabled') {
                        $axxess->funcLiftSuspend($mtn_lte->guidServiceId);
                    } else {
                        $axxess->funcSuspend($mtn_lte->guidServiceId);
                    }
                }
            }

            // HOSTING
            $sites = \DB::table('sub_services')->where('account_id', $account_id)->whereIn('provision_type', ['hosting', 'sitebuilder'])->where('status', '!=', 'Deleted')->get();
            if (! empty($sites)) {
                foreach ($sites as $site) {
                    $product = \DB::table('crm_products')->where('id', $site->product_id)->get()->first();
                    $domain = \DB::table('isp_host_websites')->where('domain', $site->detail)->get()->first();
                    if ($status == 'Enabled') {
                        $result = (new Interworx)->setServer($domain->server)->unsuspend($domain->domain);
                    } else {
                        $result = (new Interworx)->setServer($domain->server)->suspend($domain->domain);
                    }
                }
            }

            // IP RANGES
            /*
            $ip_ranges = \DB::table('sub_services')->where('account_id', $account_id)->whereIn('provision_type', ['ip_range_gateway','ip_range_route'])->where('status', '!=', 'Deleted')->get();


            if (!empty($ip_ranges)) {
                foreach ($ip_ranges as $ip_range) {
                    set_ip_subscription_status($ip_range->detail, $status);
                }
            }
            */
        }
    }

    public function cancel($id)
    {
        $sub = \DB::table('sub_services')->where('id', $id)->get()->first();
        if ($sub->provision_type == 'bundle') {
            $bundle_subs = \DB::table('sub_services')->where('bundle_id', $id)->get();
            foreach ($bundle_subs as $bundle_sub) {
                $this->cancel($bundle_sub->id);
            }
        }

        if (! empty($sub->to_cancel)) {
            return 'Subscription already cancelled.';
        }
        if ($sub->status == 'Deleted') {
            return 'Subscription deleted.';
        }

        $data_product_ids = get_data_product_ids();
        $product_is_data = \DB::connection('default')->table('sub_services')->whereIn('product_id', $data_product_ids)->where('id', $id)->count();
        $cancellation_period = get_admin_setting('cancellation_schedule');

        if ($cancellation_period == 'Immediately') {
            $cancel_date = date('Y-m-d');
            if ($product_is_data) {
                $cancel_date = date('Y-m-t', strtotime('+1 month'));
            }
        } elseif ($cancellation_period == 'This Month') {
            $cancel_date = date('Y-m-t');
            if ($product_is_data) {
                $cancel_date = date('Y-m-t', strtotime('+1 month'));
            }
        } elseif ($cancellation_period == 'Next Month') {
            $cancel_date = date('Y-m-t', strtotime('+1 month'));
        }

        if ($sub->provision_type == 'lte_sim_card') {
            $account = dbgetaccount($sub->account_id);
            $data['detail'] = $sub->detail;
            $data['account_company'] = $account->company;
            $data['internal_function'] = 'lte_sim_card_cancel';
            $data['cc_email'] = 'neliswa.sango@vodacom.co.za';
            erp_process_notification(1, $data);
            $this->logAction($id, 'vodacom lte cancel success', 'vodacom lte cancel success');
        }

        if (str_contains($sub->provision_type, 'ip_range')) {
            \DB::table('isp_data_ip_ranges')->where('subscription_id', $id)->update(['renew' => 0]);
        }

        \DB::table('sub_services')->where('id', $id)->update(['to_cancel' => 1, 'cancel_date' => $cancel_date]);
        if ($sub->provision_type == 'hosting' || $sub->provision_type == 'sitebuilder') {
            \DB::table('sub_services')
                ->where(['account_id' => $sub->account_id, 'detail' => $sub->detail, 'provision_type' => 'domain_name'])->update(['to_cancel' => 1, 'cancel_date' => $cancel_date]);
            \DB::table('sub_services')
                ->where(['account_id' => $sub->account_id, 'detail' => $sub->detail, 'provision_type' => 'domain_name_international'])->update(['to_cancel' => 1, 'cancel_date' => $cancel_date]);
        }
        $this->logAction($id, 'Cancel');

        return true;
    }

    public function undoCancel($id)
    {
        $sub = \DB::table('sub_services')->where('id', $id)->get()->first();
        if ($sub->contract_period != 1) {
            if (date('Y-m-d') < date('Y-m-d', strtotime($sub->date_activated.' +'.$sub->contract_period.' months'))) {
                undo_create_subscription_cancellation_invoice($sub->id);
            }
        }
        if ($sub->provision_type == 'bundle') {
            $bundle_subs = \DB::table('sub_services')->where('bundle_id', $id)->get();
            foreach ($bundle_subs as $bundle_sub) {
                $this->undoCancel($bundle_sub->id);
            }
        }
        \DB::table('sub_services')->where('id', $id)->update(['to_cancel' => 0, 'cancel_date' => null]);

        $this->logAction($id, 'undoCancel');

        return true;
    }

    public function hold($id)
    {
        $account_id = \DB::table('sub_services')->where('id', $id)->pluck('account_id')->first();
        switch_account($account_id, 'Disabled');
        $subscriptions = \DB::table('sub_services')->where('account_id', $account_id)->pluck('id')->toArray();
        foreach ($subscriptions as $subscription_id) {
            \DB::table('sub_services')->where('id', $subscription_id)->update(['billing_on_hold' => 1]);
        }

        $this->logAction($id, 'hold');

        return true;
    }

    public function unhold($id)
    {
        $account_id = \DB::table('sub_services')->where('id', $id)->pluck('account_id')->first();
        switch_account($account_id, 'Enabled');
        $subscriptions = \DB::table('sub_services')->where('account_id', $account_id)->pluck('id')->toArray();
        foreach ($subscriptions as $subscription_id) {
            \DB::table('sub_services')->where('id', $subscription_id)->update(['billing_on_hold' => 0]);
        }

        $this->logAction($id, 'unhold');

        return true;
    }

    public function createSubscription($account_id, $product_id, $detail, $invoice_id = null, $bill_frequency = 1, $bundle_id = 0)
    {
        $product = \DB::table('crm_products')->where('id', $product_id)->get()->first();
        $name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();

        $status = 'Enabled';
        if ($product->is_bundle) {
            $bundle_count = \DB::table('sub_services')
                ->where('status', '!=', 'Deleted')
                ->where([
                    'account_id' => $account_id,
                    'product_id' => $product_id,
                ])->count();
            $bundle_count++;
            $detail = 'Bundle '.$product->code.' #'.$bundle_count;
            $name = 'bundle';
            $status = 'Pending';
        }

        if ($product_id == 730) {
            $exists = \DB::table('sub_services')
                ->where('status', '!=', 'Deleted')
                ->where([
                    'account_id' => $account_id,
                    'product_id' => $product_id,
                ])->count();
        } else {
            $exists = \DB::table('sub_services')
                ->where('status', '!=', 'Deleted')
                ->where([
                    'account_id' => $account_id,
                    'product_id' => $product_id,
                    'detail' => $detail,
                ])->count();
        }
        if ($exists) {
            return true;
        }

        $account = dbgetaccount($account_id);
        if (empty($name)) {
            $name = 'various';
        }
        $subscription_data = [
            'created_at' => date('Y-m-d H:i:s'),
            'status' => $status,
            'account_id' => $account_id,
            'product_id' => $product_id,
            'category_id' => $product->product_category_id,
            'detail' => $detail,
            'provision_type' => $name,
        ];
        if ($product->trial_subscription_days) {
            $subscription_data['trial_expiry_date'] = date('Y-m-d', strtotime(' +'.$product->trial_subscription_days.' days'));
        }

        $subscription_data['date_activated'] = date('Y-m-d H:i:s');
        $subscription_data['bill_frequency'] = $bill_frequency;

        if ($invoice_id) {
            $invoice_line_id = \DB::table('sub_activations')
                ->where('detail', $detail)
                ->where('invoice_id', $invoice_id)
                ->where('product_id', $product_id)->pluck('invoice_line_id')->first();
            $subscription_data['contract_period'] = \DB::table('crm_document_lines')
                ->where('id', $invoice_line_id)
                ->where('document_id', $invoice_id)
                ->pluck('contract_period')->first();
        }

        if (empty($subscription_data['contract_period'])) {
            $subscription_data['contract_period'] = 0;
        }
        $subscription_data['renews_at'] = date('Y-m-d H:i:s', strtotime('+'.$bill_frequency.' months'));

        if ($invoice_id) {
            $subscription_data['invoice_id'] = $invoice_id;
        }
        if ($bundle_id) {
            $subscription_data['bundle_id'] = $bundle_id;
        }

        $id = \DB::table('sub_services')->insertGetId($subscription_data);
        if ($name == 'hosting' || $name == 'sitebuilder') {
            $domain_data = $subscription_data;
            $tld = get_tld($subscription_data['detail']);
            //  if ($tld == 'co.za' || $tld == 'org.za') {
            $domain_data['product_id'] = 760;
            $domain_data['provision_type'] = 'domain_name';
            // } else {
            //     $domain_data['product_id'] = 855;
            //      $domain_data['provision_type'] = 'domain_name';
            //  }

            $exists = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $domain_data['product_id'])->where('detail', $domain_data['detail'])->count();
            if (! $exists) {
                $activation_id = \DB::table('sub_activations')->where('product_id', $domain_data['product_id'])->where('invoice_id', $invoice_id)->pluck('id')->first();
                if ($activation_id) {
                    \DB::table('sub_activations')->where('id', $activation_id)->update(['status' => 'Enabled']);
                }
                \DB::table('sub_services')->insert($domain_data);
            }
        }

        if ($name == 'iptv_addon') {
            //  set iptv addon
            $detail = str_replace('_addon_'.$product_id, '', $detail);
            \DB::connection('default')->table('isp_data_iptv')->where('username', $detail)->update(['addon_set' => 0]);
        }

        if ($id && ! empty($current_usage)) {
            \DB::table('sub_services')->where('id', $id)->update(['current_usage' => $current_usage]);
        }
        if ($id && ! empty($usage_type)) {
            \DB::table('sub_services')->where('id', $id)->update(['usage_type' => $usage_type]);
        }
        if ($id && ! empty($usage_allocation)) {
            \DB::table('sub_services')->where('id', $id)->update(['usage_allocation' => $usage_allocation]);
        }

        // activate bundle subscription
        if (! empty($bundle_id)) {
            $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $bundle_id)->where('status', 'Pending')->count();
            if ($pending_bundle_activations == 0) {
                \DB::table('sub_services')->where('id', $bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
            }
        }

        if (str_contains($name, 'ip_range')) {
            set_renewal_date_billing_accounts();
        }

        if ($subscription_data['provision_type'] == 'airtime_contract' || $subscription_data['provision_type'] == 'unlimited_channel') {
            schedule_update_pbx_domains_airtime_subscription_process();
        }

        $this->updateProductPricesBySubscription($id);
        $this->updateSubscriptionsTotal($account_id);
        $this->logAction($id, 'createSubscription');
        if ($account->domain_uuid) {
            $pbx = new FusionPBX;
            $pbx->setPbxType($account->domain_uuid);
            $pbx->setUnlimitedChannels();
        }

        return $id;
    }

    public function deleteSubscription($id, $approve_delete = false)
    {
        try {
            $sub = \DB::table('sub_services')->where('id', $id)->get()->first();
            $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
            if ($product->deactivate_plan_id > 0) {
                $name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();
                $deactivation_data = [
                    'provision_type' => $name,
                    'account_id' => $sub->account_id,
                    'product_id' => $sub->product_id,
                    'qty' => ($sub->qty) ? $sub->qty : 1,
                    'detail' => $sub->detail,
                    'subscription_id' => $sub->id,
                    'status_provision_type' => 1,
                ];
                $deactivation_exists = \DB::table('sub_activations')->where($deactivation_data)->count();

                if (! $deactivation_exists) {
                    $insert_data = $deactivation_data;
                    $insert_data['status'] = 'Pending';
                    $insert_data['created_at'] = date('Y-m-d H:i:s');
                    if ($approve_delete) {
                        $insert_data['status'] = 'Enabled';
                        \DB::table('sub_activations')->insert($insert_data);
                    } else {
                        \DB::table('sub_activations')->insert($insert_data);

                        return 'Deactivation process created.';
                    }
                }

                $deactivation_completed = \DB::table('sub_activations')->where($deactivation_data)->where('status', 'Pending')->count();
                if ($deactivation_completed) {
                    return 'Deactivation process not completed.';
                }
            }
            $account = dbgetaccount($sub->account_id);
            $this->subscription = $sub;
            $service_deleted = $this->deleteService($id);
            if ($service_deleted !== true) {
                return 'Service could not be deleted';
            }

            $duplicate = \DB::table('sub_services')
                ->where(['account_id' => $sub->account_id, 'product_id' => $sub->product_id, 'detail' => $sub->detail, 'status' => 'Deleted'])
                ->count();
            if ($duplicate) {
                \DB::table('sub_services')
                    ->where(['account_id' => $sub->account_id, 'product_id' => $sub->product_id, 'detail' => $sub->detail, 'status' => 'Deleted'])
                    ->delete();
            }

            if ($sub->provision_type == 'hosting' || $sub->provision_type == 'sitebuilder') {
                \DB::table('sub_services')
                    ->where(['account_id' => $sub->account_id, 'detail' => $sub->detail, 'provision_type' => 'domain_name'])->delete();
                \DB::table('sub_services')
                    ->where(['account_id' => $sub->account_id, 'detail' => $sub->detail, 'provision_type' => 'domain_name_international'])->delete();
            }

            \DB::table('sub_services')
                ->where('id', $id)
                ->update(['renews_at' => null, 'deleted_at' => date('Y-m-d H:i:s'), 'status' => 'Deleted', 'to_migrate' => 0, 'to_cancel' => 0, 'cancel_date' => null]);

            if ($sub->status == 'Pending') {
                $this->logAction($id, 'deletePendingSubscription');
            } else {
                $this->logAction($id, 'deleteSubscription');
            }
            $this->updateSubscriptionsTotal($sub->account_id);
            if ($account->domain_uuid) {
                $pbx = new FusionPBX;
                $pbx->setPbxType($account->domain_uuid);
                $pbx->setUnlimitedChannels();
            }

            return true;
        } catch (\Throwable $ex) {
            exception_log($ex);

            exception_email($ex, 'Subscription Delete failed at '.date('Y-m-d H:i'));
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
            $this->logAction($id, 'deleteError '.$error);
            $this->rollback();

            return 'Subscription Delete failed';
        }
    }

    public function migrate($id, $migrate_product_id)
    {
        $subscription = \DB::table('sub_services')->where('id', $id)->get()->first();
        /*
        if ($subscription->provision_type == 'pbx_extension') {
            $extension_ids = \DB::table('sub_services')
                ->where('account_id', $subscription->account_id)
                ->where('provision_type', 'pbx_extension')
                ->where('status', '!=', 'Deleted')
                ->where('migrate_product_id', 0)
                ->pluck('id')->toArray();
            \DB::table('sub_services')->whereIn('id', $extension_ids)->update(['to_migrate' => 1,'migrate_product_id' => $migrate_product_id]);

            $sip_trunk_ids = \DB::table('sub_services')
                ->where('account_id', $subscription->account_id)
                ->where('provision_type', 'sip_trunk')
                ->where('status', '!=', 'Deleted')
                ->where('migrate_product_id', 0)
                ->pluck('id')->toArray();
            \DB::table('sub_services')->whereIn('id', $sip_trunk_ids)->update(['to_migrate' => 1,'migrate_product_id' => $migrate_product_id]);
        } else {
            \DB::table('sub_services')->where('id', $id)->update(['to_migrate' => 1,'migrate_product_id' => $migrate_product_id]);
        }
        */
        \DB::table('sub_services')->where('id', $id)->update(['to_migrate' => 1, 'migrate_product_id' => $migrate_product_id]);

        $this->logAction($id, 'migrate');

        return true;
    }

    public function processMigrationByAccountId($account_id)
    {
        $migrate_ids = \DB::table('sub_services')->where('account_id', $account_id)->where('to_migrate', 1)->pluck('id')->toArray();
        foreach ($migrate_ids as $migrate_id) {
            $result = $this->processMigration($migrate_id);
            if ($result !== true) {
                return $result;
            }
        }

        return true;
    }

    public function processMigration($id)
    {
        $sub = \DB::table('sub_services')->where('to_migrate', 1)->where('id', $id)->get()->first();
        if ($sub->provision_type == 'airtime_contract') {
            return $this->processMigrationAirtime($id);
        }
        $this->logAction($id, 'processMigration');
        try {
            $sub = \DB::table('sub_services')->where('to_migrate', 1)->where('id', $id)->get()->first();
            $this->subscription = $sub;

            $account = dbgetaccount($sub->account_id);
            $account_id = $sub->account_id;
            $admin = dbgetaccount(1);

            $current_product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
            $new_product = \DB::table('crm_products')->where('id', $sub->migrate_product_id)->get()->first();

            $reseller_user = 0;
            $invoice_account_id = $account_id;
            if ($account->partner_id != 1) {
                $reseller_user = $account->id;
                $invoice_account_id = $account->partner_id;
            }

            // create invoice or credit note for subscription price difference

            $current_subscription_price = pricelist_get_price($invoice_account_id, $sub->product_id)->price;
            $new_subscription_price = pricelist_get_price($invoice_account_id, $sub->migrate_product_id)->price;

            $price_difference = currency($new_subscription_price - $current_subscription_price);

            $total = $price_difference;
            $tax = 0;

            if ($admin->vat_enabled) {
                $tax = $total * 0.15;
            }

            $total = $total + $tax;

            if ($total > 0) {
                $result = create_migration_invoice($invoice_account_id, $reseller_user, $new_product->id, $total, 'Migrate '.$current_product->code.' to '.$new_product->code);
                if (! is_array($result) || empty($result['id'])) {
                    abort(500, 'Migration invoice not created.');
                }
            }

            $name = \DB::table('sub_activation_types')->where('id', $new_product->provision_plan_id)->pluck('name')->first();

            if ($name == 'hosting' || $name == 'sitebuilder') {
                $domain = \DB::table('sub_services')->where('id', $sub->id)->pluck('detail')->first();
                $package = \DB::table('crm_products')->where('id', $new_product->id)->pluck('provision_package')->first();

                \DB::table('isp_host_websites')->where('domain', $domain)->update(['package' => $package]);
                $result = panel_to_siteworx($account_id, $domain, $package);
                if (! $result) {
                    return false;
                }
            }

            if ($name == 'pbx_extension' || $name == 'sip_trunk' || $name == 'unlimited_channel') {

                $pbx = new FusionPBX;
                $pbx->importDomains($account->domain_uuid);
                $pbx->setPbxType($account->domain_uuid);
                $pbx->setUnlimitedChannels();
            }

            if ($name == 'fibre_product') {

                $session_fibre_email = get_admin_setting('session_fibre_email');
                if (! $session_fibre_email) {
                    $session_fibre_email = 'ahmed@telecloud.co.za';
                }
                $data['formatted'] = 1;

                $data['subject'] = 'Fibre line migration';
                $data['message'] = 'Hi,
                
                The following fibre account needs to be migrated.
                
                Account Name: '.$sub->detail.'
                Current Package: '.$current_product->code.' - '.$current_product->name.'
                New Package:  '.$new_product->code.' - '.$new_product->name.'
                
                Regards,
                Cloud Telecoms';
                $data['message'] = nl2br($data['message']);
                $data['from_email'] = 'helpdesk@telecloud.co.za';
                $data['to_email'] = $session_fibre_email;
                $data['cc_email'] = 'kola@telecloud.co.za';

                //$data['test_debug'] = 1;
                erp_process_notification(1, $data);
            }

            if ($name == 'fibre') {

                /*
                // AXXESS FIBRE
                $fibre_account = \DB::table('isp_data_fibre')->where('subscription_id', $sub->id)->get()->first();
                $guidServiceId = $fibre_account->guidServiceId;
                $guidProductId = \DB::table('isp_data_products')
                    ->where('guidNetworkProviderId', $fibre_account->guidNetworkProviderId)
                    ->where('product_id', $sub->migrate_product_id)
                    ->pluck('guidProductId')->first();
                $axxess = new \Axxess();
                $result = $axxess->funcServiceChanges($guidServiceId, $guidProductId);
                if ($result->intCode != 200) {
                    dev_email('Fibre migrate failed', $result);
                    return false;
                }
                */
            }

            \DB::table('sub_services')
                ->where('id', $sub->id)
                ->update(['product_id' => $sub->migrate_product_id, 'to_migrate' => 0, 'migrate_product_id' => 0]);

            $this->updateProductPrices($sub->migrate_product_id);
            $this->updateSubscriptionsTotal($sub->account_id);

            $this->logAction($id, 'migrationComplete');

            return true;
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'Subscription Migration failed at '.date('Y-m-d H:i'));
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
            exception_log($error);
            $this->logAction($id, 'migrateError '.$error);
            $this->rollback();

            return 'Subscription Migration failed';
        }
    }

    public function processMigrationSMS($id)
    {
        $this->logAction($id, 'processMigration');
        try {
            $sub = \DB::table('sub_services')->where('to_migrate', 1)->where('id', $id)->get()->first();
            if (! $sub) {
                return false;
            }
            $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

            $this->subscription = $sub;

            $account = dbgetaccount($sub->account_id);
            $account_id = $sub->account_id;
            $admin = dbgetaccount(1);

            $reseller_user = 0;
            $invoice_account_id = $account_id;
            if ($account->partner_id != 1) {
                $reseller_user = $account->id;
                $invoice_account_id = $account->partner_id;
            }

            $new_qty = substr($sub->migrate_product_id, 3);
            $sub->migrate_product_id = 596;

            // create invoice or credit note for subscription price difference

            $package_amount = $product->provision_package;
            $current_qty = $sub->usage_allocation / $package_amount;
            $price = pricelist_get_price($invoice_account_id, $sub->product_id)->price;
            $migrate_code = 'Bulk SMS '.intval($package_amount * $current_qty);
            $new_migrate_code = 'Bulk SMS '.intval($package_amount * $new_qty);

            $current_subscription_price = $price * $current_qty;
            $new_subscription_price = $price * $new_qty;

            $price_difference = currency($new_subscription_price - $current_subscription_price);

            $total = $price_difference;
            $tax = 0;

            if ($admin->vat_enabled) {
                $tax = $total * 0.15;
            }

            $total = $total + $tax;

            if ($total > 0) {
                $result = create_migration_invoice($invoice_account_id, $reseller_user, 596, $total, 'Migrate '.$migrate_code.' to '.$new_migrate_code);

                if (! is_array($result) || empty($result['id'])) {
                    abort(500, 'Migration invoice not created.');
                }
            }

            $usage_balance = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)
                ->pluck('balance')->first();

            $new_prorata_amount = $new_subscription_price;

            $current_prorata_amount = $current_subscription_price;

            $prorata_amount = $new_prorata_amount - $current_prorata_amount;

            if ($prorata_amount > 0) {
                $usage_balance += $prorata_amount;
            } else {
                $usage_balance -= abs($prorata_amount);
            }
            if ($usage_balance < 0) {
                $usage_balance = 0;
            }
            $airtime_history = [
                'created_at' => date('Y-m-d H:i:s'),
                'erp' => session('instance')->directory,
                'domain_uuid' => $account->domain_uuid,
                'total' => $prorata_amount,
                'balance' => $usage_balance,
                'type' => 'airtime_migration',

            ];

            \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);

            $usage_type = 'Rand';

            $usage_allocation = intval($package_amount * $new_qty);
            $detail = $new_qty;

            \DB::table('sub_services')->where('id', $sub->id)
                ->update([
                    'usage_type' => $usage_type,
                    'current_usage' => $usage_balance,
                    'usage_allocation' => $usage_allocation,
                    'detail' => $detail,
                ]);
            if ($prorata_amount > 0) {
                \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->update(['balance' => $usage_balance]);
            }

            \DB::table('sub_services')
                ->where('id', $sub->id)
                ->update(['product_id' => 730, 'to_migrate' => 0, 'migrate_product_id' => 0]);

            $this->updateProductPrices(730);
            $this->updateSubscriptionsTotal($sub->account_id);

            $this->logAction($id, 'migrationComplete');

            return true;
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'Subscription Migration failed at '.date('Y-m-d H:i'));
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
            $this->logAction($id, 'migrateError '.$error);
            $this->rollback();

            return 'Subscription Migration failed';
        }
    }

    public function processMigrationAirtime($id)
    {
        $this->logAction($id, 'processMigration');
        try {
            $sub = \DB::table('sub_services')->where('to_migrate', 1)->where('id', $id)->get()->first();
            if (! $sub) {
                return false;
            }
            $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

            $this->subscription = $sub;

            $account = dbgetaccount($sub->account_id);
            $account_id = $sub->account_id;
            $admin = dbgetaccount(1);

            $reseller_user = 0;
            $invoice_account_id = $account_id;
            if ($account->partner_id != 1) {
                $reseller_user = $account->id;
                $invoice_account_id = $account->partner_id;
            }

            $new_qty = substr($sub->migrate_product_id, 3);
            $sub->migrate_product_id = 730;

            // create invoice or credit note for subscription price difference

            $package_amount = $product->provision_package;
            $current_qty = $sub->usage_allocation / $package_amount;
            $price = pricelist_get_price($invoice_account_id, $sub->product_id)->price;
            $migrate_code = 'Airtime Topup '.intval($package_amount * $current_qty);
            $new_migrate_code = 'Airtime Topup '.intval($package_amount * $new_qty);

            $current_subscription_price = $price * $current_qty;
            $new_subscription_price = $price * $new_qty;

            $price_difference = currency($new_subscription_price - $current_subscription_price);

            $total = $price_difference;
            $tax = 0;

            if ($admin->vat_enabled) {
                $tax = $total * 0.15;
            }

            $total = $total + $tax;

            if ($total > 0) {
                $result = create_migration_invoice($invoice_account_id, $reseller_user, 730, $total, 'Migrate '.$migrate_code.' to '.$new_migrate_code);

                if (! is_array($result) || empty($result['id'])) {
                    abort(500, 'Migration invoice not created.');
                }
            }

            $usage_balance = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('balance')->first();

            $new_prorata_amount = $new_subscription_price;

            $current_prorata_amount = $current_subscription_price;

            $prorata_amount = $new_prorata_amount - $current_prorata_amount;

            if ($prorata_amount > 0) {
                $usage_balance += $prorata_amount;
            } else {
                $usage_balance -= abs($prorata_amount);
            }
            if ($usage_balance < 0) {
                $usage_balance = 0;
            }
            $airtime_history = [
                'created_at' => date('Y-m-d H:i:s'),
                'erp' => session('instance')->directory,
                'domain_uuid' => $account->domain_uuid,
                'total' => $prorata_amount,
                'balance' => $usage_balance,
                'type' => 'airtime_migration',

            ];

            \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);

            $usage_type = 'Rand';

            $usage_allocation = intval($package_amount * $new_qty);
            $detail = intval($usage_allocation * 2).' minutes';

            \DB::table('sub_services')->where('id', $sub->id)
                ->update([
                    'usage_type' => $usage_type,
                    'current_usage' => $usage_balance,
                    'usage_allocation' => $usage_allocation,
                    'detail' => $detail,
                ]);
            if ($prorata_amount > 0) {
                \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->update(['balance' => $usage_balance]);
            }

            \DB::table('sub_services')
                ->where('id', $sub->id)
                ->update(['product_id' => 730, 'to_migrate' => 0, 'migrate_product_id' => 0]);

            $this->updateProductPrices(730);
            $this->updateSubscriptionsTotal($sub->account_id);

            $this->logAction($id, 'migrationComplete');

            return true;
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'Subscription Migration failed at '.date('Y-m-d H:i'));
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
            $this->logAction($id, 'migrateError '.$error);
            $this->rollback();

            return 'Subscription Migration failed';
        }
    }

    public function getAvailableMigrateProducts($id)
    {
        $sub = \DB::table('sub_services')->where('id', $id)->get()->first();

        $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
        $activation_type_ids = \DB::table('sub_activation_plans')->where('type', 'Debitorder')->where('status', 'Enabled')->pluck('activation_type_id')->unique()->toArray();
        if (count($activation_type_ids) > 0) {
            $debit_order_product_ids = \DB::table('crm_products')->where('status', 'Enabled')->whereIn('provision_plan_id', $activation_type_ids)->pluck('id')->toArray();
            if (in_array($product->id, $debit_order_product_ids)) {
                $active_debit_order = account_has_debit_order($sub->account_id);
                if (! $active_debit_order) {
                    return 'Subscription requires Debit Order';
                }
            }
        }

        $provision_plan = $sub->provision_type;

        $plans_migrate = ['hosting', 'pbx_extension', 'sip_trunk', 'airtime_contract', 'bulk_sms', 'fibre_product', 'unlimited_channel'];
        if (! in_array($provision_plan, $plans_migrate)) {
            return 'Subscription cannot be migrated';
        }

        if ($provision_plan == 'fibre') {
            // filter products based on guidNetworkProviderId
            $fibre_account = \DB::table('isp_data_fibre')->where('subscription_id', $id)->get()->first();
            $fibre_product_ids = \DB::table('isp_data_products')->where('guidNetworkProviderId', $fibre_account->guidNetworkProviderId)
                ->where('product_id', '>', 0)
                ->where('product_id', '!=', $product->id)
                ->pluck('product_id')->unique()->toArray();
            $available_products = \DB::table('crm_products as cp')
                ->select('cp.*')
                ->join('sub_activation_types as p', 'cp.provision_plan_id', '=', 'p.id')
                ->where('cp.is_subscription', $product->is_subscription)
                ->where('cp.id', '!=', $product->id)
                ->whereIn('cp.id', $fibre_product_ids)
                ->where('p.name', $provision_plan)
                ->where('cp.id', '!=', 674)
                ->where('cp.status', 'Enabled')
                ->orderBy('sort_order')
                ->get();
        } elseif ($provision_plan == 'hosting') {
            $available_products = \DB::table('crm_products as cp')
                ->select('cp.*')
                ->join('sub_activation_types as p', 'cp.provision_plan_id', '=', 'p.id')
                ->where('cp.is_subscription', $product->is_subscription)
                ->where('cp.id', '!=', $product->id)
                ->whereIn('p.name', ['hosting', 'sitebuilder'])
                ->where('cp.id', '!=', 674)
                ->where('cp.status', 'Enabled')
                ->orderBy('sort_order')
                ->get();
        } elseif ($provision_plan == 'airtime_contract') {
        } else {
            $available_products = \DB::table('crm_products as cp')
                ->select('cp.*')
                ->join('sub_activation_types as p', 'cp.provision_plan_id', '=', 'p.id')
                ->where('cp.is_subscription', $product->is_subscription)
                ->where('cp.id', '!=', $product->id)
                ->where('p.name', $provision_plan)
                //->where('cp.id', '!=', 674)
                ->where('cp.status', 'Enabled')
                ->orderBy('sort_order')
                ->get();
        }

        $account = dbgetaccount($sub->account_id);
        $account_id = $sub->account_id;
        $admin = dbgetaccount(1);

        $reseller_user = 0;
        $invoice_account_id = $account_id;
        if ($account->partner_id != 1) {
            $invoice_account_id = $account->partner_id;
        }

        $list = [];
        if ($provision_plan == 'airtime_contract') {
            $package_amount = $product->provision_package;
            $current_qty = $sub->usage_allocation / $package_amount;
            $price = pricelist_get_price($invoice_account_id, $sub->product_id)->full_price;
            $current_subscription_price = $price * $current_qty;
            for ($i = 1; $i < 21; $i++) {
                if ($i == $current_qty) {
                    continue;
                }
                $p = clone $product;
                $p->id = $product->id.$i;
                $p->price_diff = 0;

                $new_subscription_price = $price * $i;
                $p->price_current = $current_subscription_price;
                $p->price_new = $new_subscription_price;
                $p->code = 'airtime_topup_'.intval($package_amount * $i);

                $price_difference = currency($new_subscription_price - $current_subscription_price);

                $total = $price_difference;

                if ($total > 0) {
                    $p->price_diff = $total;
                }
                $list[] = $p;
            }
        } elseif ($provision_plan == 'bulk_sms') {
            $package_amount = $product->provision_package;
            $current_qty = $sub->usage_allocation / $package_amount;
            $price = pricelist_get_price($invoice_account_id, $sub->product_id)->full_price;
            $current_subscription_price = $price * $current_qty;
            for ($i = 1; $i < 21; $i++) {
                if ($i == $current_qty) {
                    continue;
                }
                $p = clone $product;
                $p->id = $product->id.$i;
                $p->price_diff = 0;

                $new_subscription_price = $price * $i;
                $p->price_current = $current_subscription_price;
                $p->price_new = $new_subscription_price;
                $p->code = 'bulk_sms_'.intval($package_amount * $i);

                $price_difference = currency($new_subscription_price - $current_subscription_price);

                $total = $price_difference;

                if ($total > 0) {
                    $p->price_diff = $total;
                }
                $list[] = $p;
            }
        } else {
            foreach ($available_products as $p) {
                $p->price_diff = 0;
                $current_subscription_price = pricelist_get_price($invoice_account_id, $sub->product_id)->full_price;
                $new_subscription_price = pricelist_get_price($invoice_account_id, $p->id)->full_price;
                $p->price_current = $current_subscription_price;
                $p->price_new = $new_subscription_price;

                $price_difference = currency($new_subscription_price - $current_subscription_price);

                $total = $price_difference;

                if ($total > 0) {
                    $p->price_diff = $total;
                }
                $list[] = $p;
            }
        }

        return $list;
    }

    public function updateProductPrices($product_id = false)
    {
        try {
            if ($product_id) {
                $subs = \DB::table('sub_services')->where('product_id', $product_id)->get();
            } else {
                $subs = \DB::table('sub_services')->get();
            }

            $admin_vat_enabled = \DB::table('crm_account_partner_settings')->where('account_id', 1)->pluck('vat_enabled')->first();

            foreach ($subs as $s) {

                $account = \DB::table('crm_accounts')->select('id', 'type', 'partner_id', 'currency')->where('id', $s->account_id)->get()->first();
                if ($account->partner_id != 1) {
                    $account = \DB::table('crm_accounts')->select('id', 'type', 'partner_id', 'currency')->where('id', $account->partner_id)->get()->first();
                }

                $product = \DB::table('crm_products')->where('id', $s->product_id)->get()->first();

                $contract_period = ($s->contract_period > 1) ? $s->contract_period : $s->bill_frequency;
                $pricing = $price = pricelist_get_price($account->id, $s->product_id, 1, $s->bill_frequency, $contract_period);
                $price = $pricing->full_price;
                $price_incl = $pricing->full_price_incl;
                $current_qty = 1;
                if ($s->provision_type == 'airtime_contract') {
                    $package_amount = $product->provision_package;
                    $current_qty = $s->usage_allocation / $package_amount;
                }

                if (empty($price)) {
                    $price = 0;
                }
                if (empty($price_incl)) {
                    $price_incl = 0;
                }
                if (! $s->manual_price) {
                    \DB::table('sub_services')->where('id', $s->id)->update(['qty' => $current_qty, 'price' => $price, 'price_incl' => $price_incl, 'total_incl' => $price_incl * $current_qty, 'total_excl' => $price * $current_qty]);
                }
            }
        } catch (Exception $e) {
            exception_log($e);
        }
    }

    public function updateProductPricesBySubscription($subscription_id)
    {

        $sub = \DB::table('sub_services')->where('id', $subscription_id)->get()->first();

        $admin_vat_enabled = \DB::table('crm_account_partner_settings')->where('account_id', 1)->pluck('vat_enabled')->first();

        $account = \DB::table('crm_accounts')->select('id', 'type', 'partner_id', 'currency')->where('id', $sub->account_id)->get()->first();
        if ($account->partner_id != 1) {
            $account = \DB::table('crm_accounts')->select('id', 'type', 'partner_id', 'currency')->where('id', $account->partner_id)->get()->first();
        }

        $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

        $contract_period = ($sub->contract_period > 1) ? $sub->contract_period : $sub->bill_frequency;
        $pricing = $price = pricelist_get_price($account->id, $sub->product_id, 1, $sub->bill_frequency, $contract_period);
        $price = $pricing->full_price;
        $price_incl = $pricing->full_price_incl;
        $current_qty = 1;
        if ($sub->provision_type == 'airtime_contract') {
            $package_amount = $product->provision_package;
            $current_qty = $sub->usage_allocation / $package_amount;
            $price = $price * $current_qty;
            $price_incl = $price_incl * $current_qty;
        }

        if (empty($price)) {
            $price = 0;
        }
        if (empty($price_incl)) {
            $price_incl = 0;
        }

        if (! $sub->manual_price && ! $sub->contract_period) {

            \DB::table('sub_services')->where('id', $sub->id)->update(['price' => $price, 'price_incl' => $price_incl, 'total_incl' => $price_incl * $current_qty, 'total_excl' => $price * $current_qty]);
        }

    }

    public function updateProductPricesByAccount($account_id)
    {

        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('account_id', $account_id)->get();
        foreach ($subs as $sub) {

            $admin_vat_enabled = \DB::table('crm_account_partner_settings')->where('account_id', 1)->pluck('vat_enabled')->first();

            $account = \DB::table('crm_accounts')->select('id', 'type', 'partner_id', 'currency')->where('id', $sub->account_id)->get()->first();
            if ($account->partner_id != 1) {
                $account = \DB::table('crm_accounts')->select('id', 'type', 'partner_id', 'currency')->where('id', $account->partner_id)->get()->first();
            }

            $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

            $contract_period = ($sub->contract_period > 1) ? $sub->contract_period : $sub->bill_frequency;
            $pricing = $price = pricelist_get_price($account->id, $sub->product_id, 1, $sub->bill_frequency, $contract_period);
            $price = $pricing->full_price;
            $price_incl = $pricing->full_price_incl;
            $current_qty = 1;
            if ($sub->provision_type == 'airtime_contract') {
                $package_amount = $product->provision_package;
                $current_qty = $sub->usage_allocation / $package_amount;
                $price = $price * $current_qty;
                $price_incl = $price_incl * $current_qty;
            }

            if (empty($price)) {
                $price = 0;
            }
            if (empty($price_incl)) {
                $price_incl = 0;
            }

            if (! $sub->manual_price && ! $sub->contract_period) {

                \DB::table('sub_services')->where('id', $sub->id)->update(['price' => $price, 'price_incl' => $price_incl, 'total_incl' => $price_incl * $current_qty, 'total_excl' => $price * $current_qty]);
            }
        }

    }

    public function convertLinesToPBX($account_id)
    {

        $unlimited_exts = \DB::table('sub_services')
            ->where('status', '!=', 'Deleted')
            ->where('product_id', 1394)
            ->where('account_id', $account_id)
            ->count();
        if ($unlimited_exts > 0) {
            // \DB::table('sub_services')
            // ->where('status','!=','Deleted')
            // ->where('product_id',1393)
            // ->where('account_id',$account_id)
            // ->update(['product_id' => 1394]);

            $account = dbgetaccount($account_id);
            $pbx = new FusionPBX;
            // $pbx->setPbxType($account->domain_uuid);
            $pbx->setUnlimitedChannels();
        }
    }

    public function updateSubscriptionsTotal($account_id)
    {
        if ($account_id === 1) {
            return false;
        }

        $this->convertLinesToPBX($account_id);
        \DB::table('crm_accounts')->where('id', $account_id)->update(['subs_total' => 0]);
        \DB::table('crm_accounts')->where('status', 'Deleted')->update(['subs_total' => 0]);
        $admin = dbgetaccount(1);
        $account = dbgetaccount($account_id);
        if ($account->partner_id != 1) {
            $account_id = $account->partner_id;
            $account = dbgetaccount($account->partner_id);
        }
        $account_ids = [$account_id];
        if ($account->type == 'reseller') {
            $partner_total = 0;
            $partner_pending_total = 0;
            $account_ids = \DB::table('crm_accounts')
                ->where('partner_id', $account_id)
                ->where('status', '!=', 'Deleted')
                ->pluck('id')->toArray();
        }

        foreach ($account_ids as $id) {
            $total = 0;
            $pending_total = 0;
            $subscriptions = \DB::table('sub_services')
                ->where('account_id', $id)
                ->where('status', '!=', 'Deleted')
                ->where('to_cancel', 0)
                ->where('provision_type', 'NOT LIKE', '%prepaid%')
                ->get();

            foreach ($subscriptions as $subscription) {
                $contract_period = ($subscription->contract_period > 1) ? $subscription->contract_period : $subscription->bill_frequency;
                $price = pricelist_get_price($id, $subscription->product_id, 1, $subscription->bill_frequency, $contract_period);

                $product = \DB::table('crm_products')->where('id', $subscription->product_id)->get()->first();
                if ($subscription->provision_type == 'airtime_contract') {
                    $package_amount = $product->provision_package;
                    $current_qty = $subscription->usage_allocation / $package_amount;
                    $price = $price * $current_qty;
                }

                $price_excl = $price->full_price;
                $price_incl = $price->full_price_incl;

                if (empty($price)) {
                    $price = 0;
                }
                if (empty($price_incl)) {
                    $price_incl = 0;
                }
                if (! $subscription->manual_price && ! $subscription->contract_period) {
                    \DB::table('sub_services')->where('id', $subscription->id)->update(['price' => $price_excl, 'price_incl' => $price_incl]);
                }

                $total += $price_excl;

                if ($account->type == 'reseller') {
                    $partner_price = pricelist_get_price($account_id, $subscription->product_id)->full_price;
                    if ($subscription->provision_type == 'airtime_contract') {
                        $partner_price = $partner_price * $current_qty;
                    }

                    $partner_total += $partner_price;
                }
            }

            if ($account->vat_enabled == 1 && $total > 0) {
                $tax = $total * 0.15;
                $total = $total + $tax;
            }

            \DB::table('crm_accounts')->where('id', $id)->update(['subs_total' => currency($total)]);

            if ($total > 0) {
                \DB::table('crm_accounts')->where('id', $id)->whereNotIn('payment_method', ['Debit Order', 'Payfast'])->update(['payment_method' => 'Bank']);
            }
        }

        if ($account->type == 'reseller') {
            if ($admin->vat_enabled == 1 && $partner_total > 0) {
                $partner_tax = $partner_total * 0.15;
                $partner_total = $partner_total + $partner_tax;
            }

            \DB::table('crm_accounts')->where('id', $account_id)->update(['subs_total' => currency($partner_total)]);
            if ($partner_total > 0) {
                \DB::table('crm_accounts')->where('id', $account_id)->whereNotIn('payment_method', ['Debit Order', 'Payfast'])->update(['payment_method' => 'Bank']);
            }
        }

        \DB::table('crm_accounts')->update(['subs_count' => 0]);
        $account_ids = \DB::table('sub_services')
            ->where('status', '!=', 'Deleted')
            ->pluck('account_id')->unique()->toArray();

        foreach ($account_ids as $id) {
            $active_subs = \DB::table('sub_services')
                ->where('status', '!=', 'Deleted')
                ->where('account_id', $id)
                ->count();
            \DB::table('crm_accounts')->where('id', $id)->update(['subs_count' => $active_subs]);
            $cancelled_subs = \DB::table('sub_services')
                ->where('status', '!=', 'Deleted')
                ->where('to_cancel', 1)
                ->where('account_id', $id)
                ->count();
        }

        $partners = \DB::table('crm_accounts')->where('type', 'reseller')->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        foreach ($partners as $pid) {
            $active_subs = \DB::table('crm_accounts')
                ->where('partner_id', $pid)
                ->sum('subs_count');
            \DB::table('crm_accounts')->where('id', $pid)->update(['subs_count' => $active_subs]);
        }
        $this->updatePayfastSubscription($account_id);
    }

    public function updatePayfastSubscription($id)
    {
        try {
            $account = dbgetaccount($id);
            if ($account->partner_id != 1) {
                return false;
            }

            $exists = \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->where('status', '!=', 'Deleted')->count();
            if ($exists) {
                $pf_sub = \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->where('status', '!=', 'Deleted')->orderBy('id', 'desc')->get()->first();

                $payment_option = get_payment_option('Payfast');
                $payfast_subscription = new PayfastSubscription;
                $payfast_subscription->setCredentials($payment_option->payfast_id, $payment_option->payfast_key, $payment_option->payfast_pass_phrase);
                // $payfast_subscription->setDebug();
                $send_mail = false;

                $account_balance = account_get_full_balance($account->id);
                if ($account->balance > 0) {
                    $bill_total = currency($account_balance);
                } else {
                    $bill_total = 0;
                }

                if (empty($bill_total) || $bill_total <= 0) {
                    $payfast_subscription->pause($pf_sub->token);
                    \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->update(['paused' => 1]);
                    \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->where('status', 'Enabled')->update(['bill_amount' => 0]);

                } else {

                    if ($pf_sub->paused) {

                        $response = $payfast_subscription->unpause($pf_sub->token);

                        if ($response && $response['code'] == 200 && ! empty($response['data']) && $response['data']['response'] === true) {
                            \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->update(['paused' => 0, 'bill_amount' => $bill_total]);
                        }

                    }
                    $result = $payfast_subscription->set_billed_amount($pf_sub->token, $bill_total);

                    $send_mail = true;
                    if (! $result || ! is_array($result) || ! $result['code']) {
                        $send_mail = false;
                        debug_email('Payfast subscription set billed amount failed. account_id: '.$id.' result:'.json_encode($result));
                    } else {
                        \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->where('status', 'Enabled')->update(['bill_amount' => $bill_total]);
                    }
                }
                if ($send_mail) {
                    try {
                        $mail_data = [
                            'subs_total' => $bill_total,
                            'bill_date' => date('Y-m-01', strtotime('+1 month')),
                            'internal_function' => 'payfast_subscription_update',
                        ];
                        erp_process_notification($id, $mail_data);
                    } catch (\Throwable $ex) {
                        exception_log($ex);
                    }
                }
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            //exception_email($ex, 'updatePayfastSubscription error');
        }
    }

    public function deletePayfastSubscription($id)
    {
        try {
            $account = dbgetaccount($id);
            if ($account->partner_id != 1) {
                return false;
            }

            $exists = \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->where('status', '!=', 'Deleted')->count();
            if ($exists) {
                $pf_sub = \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->where('status', '!=', 'Deleted')->get()->first();
                $payment_option = get_payment_option('Payfast');
                $payfast_subscription = new PayfastSubscription;
                $payfast_subscription->setCredentials($payment_option->payfast_id, $payment_option->payfast_key, $payment_option->payfast_pass_phrase);
                // $payfast_subscription->setDebug();
                $result = $payfast_subscription->cancel($pf_sub->token);
                try {
                    $mail_data = [
                        'internal_function' => 'payfast_subscription_cancelled',
                    ];
                    erp_process_notification($id, $mail_data);
                } catch (\Throwable $ex) {
                    exception_log($ex);
                }
                if ($result['code'] != 200 || $result['status'] != 'success') {
                    debug_email('DELETE PAYFAST SUBSCRIPTION failed. account_id: '.$id);
                }
            }
            \DB::table('acc_payfast_subscriptions')->where('account_id', $id)->update(['status' => 'Deleted']);
        } catch (\Throwable $ex) {
            exception_log($ex);

            exception_email($ex, 'DELETE PAYFAST SUBSCRIPTION error');
        }
    }

    public function verifySubscriptions($id = false)
    {
        // return false;
        if (session('instance')->db_connection == 'telecloud') {
            $subscription_count = [];
            $services_count = [];
            $iw = new Interworx;

            $subscription_types = \DB::table('sub_services as s')
                ->select('s.provision_type', 'pc.department')
                ->join('crm_products as p', 'p.id', '=', 's.product_id')
                ->join('crm_product_categories as pc', 'pc.id', '=', 'p.product_category_id')
                ->where('p.is_subscription', 1)
                ->where('s.provision_type', '>', '')
                ->where('s.provision_type', '!=', 'other')
                ->where('s.status', '!=', 'Deleted')
                ->where('s.status', '!=', 'Pending')
                ->groupBy('s.provision_type')
                ->orderby('pc.sort_order')
                ->orderby('p.sort_order')
                ->get();
            $departments = [];

            foreach ($subscription_types as $type) {
                $departments[$type->department] = [];
            }
            foreach ($subscription_types as $type) {
                $departments[$type->department][] = $type->provision_type;
            }

            $domain_uuids = \DB::connection('pbx')->table('v_domains')->pluck('domain_uuid')->toArray();
            $extensions = \DB::connection('pbx')->table('v_extensions')->whereNotNull('domain_uuid')->whereIn('domain_uuid', $domain_uuids)->get();
            $phone_numbers = \DB::connection('pbx')->table('p_phone_numbers')->whereNotNull('domain_uuid')->whereIn('domain_uuid', $domain_uuids)->get();

            $subscription_errors = 0;
            if ($id) {
                $subscriptions = \DB::table('sub_services')->where('id', $id)->where('status', '!=', 'Deleted')->where('status', '!=', 'Pending')->get();
            } else {
                $subscriptions = \DB::table('sub_services')->where('provision_type', 'pbx_extension')->where('status', '!=', 'Deleted')->where('status', '!=', 'Pending')->get();
            }

            foreach ($subscriptions as $subscription) {
                $account = dbgetaccount($subscription->account_id);
                if ($subscription->provision_type == 'phone_number') {
                    $exists = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $subscription->detail)->where('domain_uuid', $account->domain_uuid)->count();
                    if (! $exists) {
                        $subscription_errors++;
                    }
                }

                if ($subscription->provision_type == 'pbx_extension') {
                    $exists = \DB::connection('pbx')->table('v_extensions')->where('extension', $subscription->detail)->where('domain_uuid', $account->domain_uuid)->count();
                    if (! $exists) {
                        $subscription_errors++;
                    }
                }

                if ($subscription->provision_type == 'hosting') {
                    $hosting = \DB::table('isp_host_websites')->where('server', '!=', 'external')->where('domain', $subscription->detail)->where('account_id', $subscription->account_id)->get()->first();
                    if (empty($hosting)) {
                        $subscription_errors++;
                    } else {
                        $iw_result = $iw->setServer($hosting->server)->setDomain($hosting->domain)->getAccountInfo();

                        if (! is_array($iw_result)) {
                            $subscription_errors++;
                        }
                    }
                }
            }

            foreach ($departments as $department) {
                foreach ($department as $provision_type) {
                    $subscription_count[$provision_type] = $subscriptions->where('provision_type', $provision_type)->count();
                }
            }

            $services_count['phone_number'] = count($phone_numbers);
            foreach ($phone_numbers as $phone_number) {
                $exists = \DB::table('sub_services')->where('provision_type', 'phone_number')->where('detail', $phone_number->number)->where('status', '!=', 'Deleted')->count();
                if (! $exists) {
                    //$this->createSubscription($phone_number->account_id, 128, $phone_number->number);
                    $subscription_errors++;
                }
            }

            $services_count['pbx_extension'] = count($extensions);
            foreach ($extensions as $extension) {
                $exists = \DB::table('sub_services')->where('provision_type', 'pbx_extension')->where('detail', $extension->extension)->where('status', '!=', 'Deleted')->count();
                if (! $exists) {
                    //$this->createSubscription($extension->account_id, 139, $extension->extension);
                    $subscription_errors++;
                }
            }
            $fibre_count = 0;
            $axxess = new \Axxess;
            $clients = $axxess->getAllClients()->arrClients;
            foreach ($clients as $client) {
                $services = $axxess->getServicesByClient($client->guidClientId)->arrServices;
                if (! empty($services) && is_array($services) && count($services) > 0) {
                    foreach ($services as $service) {
                        if (empty($service->intSuspendReasonId) && str_contains($service->strDescription, '@ct')) {
                            $fibre_count++;
                            $exists = \DB::table('isp_data_fibre')->where('username', $service->strDescription)->count();
                            if (! $exists) {
                                $subscription_errors++;
                            }
                        }
                    }
                }
            }
            $services_count['fibre'] = $fibre_count;

            $hosting_count = 0;
            $hosting_accounts = $iw->listAllDomains();
            foreach ($hosting_accounts as $hosting_account) {
                $hosting_count++;
                $exists = \DB::table('sub_services')->where('provision_type', 'hosting')->where('detail', $hosting_account)->where('status', '!=', 'Deleted')->count();
                if (! $exists) {
                    $subscription_errors++;
                }
            }

            $services_count['hosting'] = $hosting_count;

            if ($subscription_errors) {
                // dev_email('Subscription errors, subscriptions need verification.', 'Run '.url('/helper/verify_subscriptions'));
            }

            if (date('Y-m-d') == date('Y-m-t')) {
                $subject = 'Subscription Verifications';
                $msg = '';
                $subscriptions_total = 0;
                foreach ($departments as $department => $provision_types) {
                    $msg .= '<br><b>'.$department.':</b> <br>';
                    foreach ($provision_types as $provision_type) {
                        $subscriptions_total += $subscription_count[$provision_type];
                        $label = ucwords(str_replace('_', ' ', $provision_type));
                        $msg .= $label.' Subscriptions: '.$subscription_count[$provision_type].'<br>';
                        if (isset($services_count[$provision_type])) {
                            $msg .= $label.' Services: '.$services_count[$provision_type].'<br>';
                        } else {
                            $msg .= $label.' Services: Manual Verification<br>';
                        }
                    }
                }
                $msg .= '<br><b>Subscriptions Total: '.$subscriptions_total.'</b> <br>';
                // admin_email($subject, $msg);
            }
        }
    }

    private function deleteService($id)
    {

        $sub = \DB::table('sub_services')->where('id', $id)->get()->first();
        if (empty($sub->detail)) {
            $this->logAction($id, 'deleteService');

            return true;
        }
        switch ($sub->provision_type) {
            case 'hosting':
                $update_data = [
                    'to_update_nameservers' => 0,
                    'to_update_contact' => 0,
                    'to_register' => 0,
                    'to_delete' => 1,
                    'transfer_in' => 0,
                    'transfer_out' => 0,
                ];
                \DB::table('isp_host_websites')
                    ->where('domain', $sub->detail)
                    ->update($update_data);
                break;
            case 'sitebuilder':
                $update_data = [
                    'to_update_nameservers' => 0,
                    'to_update_contact' => 0,
                    'to_register' => 0,
                    'to_delete' => 1,
                    'transfer_in' => 0,
                    'transfer_out' => 0,
                ];
                \DB::table('isp_host_websites')
                    ->where('domain', $sub->detail)
                    ->update($update_data);
                break;
            case 'lte_sim_card':
                $account = dbgetaccount($sub->account_id);
                $data['detail'] = $sub->detail;
                $data['company'] = $account->company;
                $data['internal_function'] = 'lte_sim_card_deleted';
                erp_process_notification(1, $data);
                break;
            case 'airtime_unlimited':

                break;
            case 'airtime_postpaid':
                $pabx_domain = dbgetaccountcell($sub->account_id, 'pabx_domain');
                $pbx_connection = get_pbx_connection($customer->pabx_domain);

                \DB::connection('pbx')->table('v_domains')->update(['is_postpaid' => 0, 'postpaid_limit' => 0]);
                break;
            case 'extension_recording':
                $extension = trim(str_replace(' Recording', '', $sub->detail));
                pbx_disable_recording($customer->pabx_domain, $extension);
                break;
            case 'pbx_extension':
                $pabx_domain = dbgetaccountcell($sub->account_id, 'pabx_domain');
                $pbx_connection = get_pbx_connection($customer->pabx_domain);
                $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
                \DB::connection('pbx')->table('v_extensions')->where(['extension' => $sub->detail, 'domain_uuid' => $domain_uuid])->delete();
                $ext_count = \DB::connection('pbx')->table('v_extensions')->where(['domain_uuid' => $domain_uuid])->count();
                \DB::connection('pbx')->table('v_domain_settings')->where('domain_uuid', $domain_uuid)->update(['domain_setting_value' => $ext_count]);
                delete_unlinked_voicemails($pbx_connection);
                break;
            case 'sip_trunk':
                $pabx_domain = dbgetaccountcell($sub->account_id, 'pabx_domain');
                $pbx_connection = get_pbx_connection($customer->pabx_domain);
                $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
                \DB::connection('pbx')->table('v_extensions')->where(['extension' => $sub->detail, 'domain_uuid' => $domain_uuid])->delete();
                $ext_count = \DB::connection('pbx')->table('v_extensions')->where(['domain_uuid' => $domain_uuid])->count();
                \DB::connection('pbx')->table('v_domain_settings')->where('domain_uuid', $domain_uuid)->update(['domain_setting_value' => $ext_count]);
                delete_unlinked_voicemails($pbx_connection);
                break;
            case 'phone_number':
                // unallocate phone number
                $pabx_domain = dbgetaccountcell($sub->account_id, 'pabx_domain');
                $pbx_connection = get_pbx_connection($customer->pabx_domain);
                \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->where('status', 'Deleted')->update(['domain_uuid' => null, 'number_routing' => null, 'routing_type' => null]);
                \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled', 'number_routing' => null, 'routing_type' => null]);

                $is_ported_number = \DB::table('sub_activations')->where('detail', $sub->detail)->where('product_id', 126)->count();
                if ($is_ported_number) {
                    $data = [
                        'account_id' => $sub->account_id,
                        'product_id' => $sub->product_id,
                        'invoice_id' => $sub->invoice_id,
                        'subscription_id' => $sub->id,
                        'detail' => $sub->detail,
                        'provision_type' => 'number_porting_deactivation',
                        'activation_type_id' => 46,
                        'status' => 'Pending',
                    ];
                    $c = \DB::table('sub_activations')->where('provision_type', 'number_porting_deactivation')->where('subscription_id', $sub->id)->count();
                    if (! $c) {
                        dbinsert('sub_activations', $data);
                    }
                }
                break;
            case 'fibre':
                \DB::table('isp_data_fibre')->where('username', $sub->detail)->update(['status' => 'Deleted']);
                $fibre = \DB::table('isp_data_fibre')->where('username', $sub->detail)->get()->first();
                if ($fibre->provider == 'Axxess') {
                    $axxess->deleteComboService($fibre->guidClientId, $fibre->guidServiceId, date('Y-m-d'));
                } else {
                    fibre_status_email($sub, 'Deleted');
                }
                break;
            case 'ip_range_gateway':
                // set_ip_subscription_status($sub->detail, 'Disabled');
                \DB::table('isp_data_ip_ranges')->where('ip_range', $sub->detail)->update(['account_id' => 0, 'subscription_id' => 0]);

                break;
            case 'ip_range_route':
                // set_ip_subscription_status($sub->detail, 'Disabled');
                \DB::table('isp_data_ip_ranges')->where('ip_range', $sub->detail)->update(['account_id' => 0, 'subscription_id' => 0]);

                break;

            case 'iptv':
                // unallocate iptv
                \DB::connection('default')->table('isp_data_iptv')->where('username', $sub->detail)->update(['account_id' => 0, 'subscription_status' => 'Available', 'subscription_id' => 0]);
                break;

            case 'iptv_addon':
                // unallocate iptv addon
                $detail = str_replace('_addon_'.$product->id, '', $sub->detail);
                \DB::connection('default')->table('isp_data_iptv')->where('username', $detail)->update(['addon_set' => 0]);
                break;

            case 'virtual_server':
                \DB::connection('default')->table('isp_virtual_servers')->where('subscription_id', $sub->id)->update(['is_deleted' => 1]);
                break;
            case 'default':
                break;
        }

        $pbx_subscriptions = account_has_pbx_subscription($sub->account_id);
        if (! $pbx_subscriptions) {
            $account = dbgetaccount($sub->account_id);
            pbx_delete_domain($account->pabx_domain, $sub->account_id);
        }

        $this->logAction($id, 'deleteService');

        return true;
    }

    private function rollback()
    {
        $data = (array) $this->subscription;
        if (! empty($data['id'])) {
            \DB::table('sub_services')->where('id', $data['id'])->update($data);
        }
    }

    private function logAction($subscription_id, $action, $action_details = '')
    {
        $subscription = \DB::table('sub_services')->where('id', $subscription_id)->get()->first();

        if ($action == 'migrationComplete') {
            $product = ucwords(str_replace('_', ' ', \DB::table('crm_products')->where('id', $subscription->product_id)->pluck('code')->first()));
            $data['internal_function'] = 'service_status_change';
            $data['status_change'] = 'Migrated';
            $data['status_description'] = 'Your subscription has been successfully been migrated to '.$product.'.';

            module_log(334, $subscription_id, 'updated', 'subscription migrated');
            erp_process_notification($subscription->account_id, $data);
        }

        if ($action == 'Cancel') {
            $data['internal_function'] = 'service_status_change';
            $data['status_change'] = 'Cancelled';
            $data['status_description'] = 'Your '.$subscription->provision_type.' '.$subscription->detail.' has been cancelled.';

            module_log(334, $subscription_id, 'cancelled', 'subscription cancelled');
            erp_process_notification($subscription->account_id, $data);
        }

        if ($action == 'undoCancel') {
            $data['internal_function'] = 'service_status_change';
            $data['status_change'] = 'Cancellation removed';
            $data['status_description'] = 'Your '.$subscription->provision_type.' '.$subscription->detail.' has been restored and the cancellation request is deleted.';

            module_log(334, $subscription_id, 'updated', 'subscription Cancellation removed');
            erp_process_notification($subscription->account_id, $data);
        }
        if ($action == 'deletePendingSubscription') {
            $data['internal_function'] = 'service_status_change';
            $data['status_change'] = 'Deleted';
            $data['status_description'] = 'Your pending subscription, '.$subscription->provision_type.' '.$subscription->detail.' has been deleted.';

            module_log(334, $subscription_id, 'updated', 'pending subscription deleted');
            erp_process_notification($subscription->account_id, $data);
        }
        if ($action == 'deleteSubscription') {
            $data['internal_function'] = 'service_status_change';
            $data['status_change'] = 'Deleted';
            $data['status_description'] = 'Your '.$subscription->provision_type.' '.$subscription->detail.' has been deleted.';

            module_log(334, $subscription_id, 'deleted', 'subscription deleted');
            erp_process_notification($subscription->account_id, $data);
        }
    }
}
