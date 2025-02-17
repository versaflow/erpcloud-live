<?php

function schedule_process_pending_topups()
{
    $pending_topups = \DB::connection('default')->table('sub_service_topups')->where('status', 'Pending')->where('processing', 0)->limit(1)->get();

    if (count($pending_topups) > 0) {
        foreach ($pending_topups as $topup) {
            \DB::connection('default')->table('sub_service_topups')->where('id', $topup->id)->update(['processing' => 1]);
            $request_data = new \Illuminate\Http\Request;
            $request_data->id = $topup->id;
            $result = app('App\Http\Controllers\CustomController')->provisionService($request_data, 'sub_service_topups', $topup->id);
            // aa($result);
            if ($result) {
                \DB::connection('default')->table('sub_service_topups')->where('id', $topup->id)->update(['processing' => 1]);
            }
        }
    }
}

function button_activation_reset($request)
{
    \DB::table('sub_activations')->where('id', $request->id)->update(['is_deleted' => 0, 'step' => 1, 'detail' => '', 'status' => 'Pending']);
    \DB::table('sub_activation_steps')->where('provision_id', $request->id)->where('service_table', 'sub_activations')->delete();
    module_log(554, $request->id, 'reset');

    return json_alert('Done');
}

function onload_services_set_pbx_domain_names()
{
    \DB::table('sub_services')->update(['created_month' => \DB::raw(" DATE_FORMAT(created_at, '%Y-%m')")]);
    \DB::table('sub_services')->where('status', 'Deleted')->update(['deleted_month' => \DB::raw(" DATE_FORMAT(deleted_at, '%Y-%m')")]);
    $domains = \DB::connection('default')->table('isp_voice_pbx_domains')->get();
    $provision_types = ['unlimited_channel', 'phone_number', 'pbx_extension', 'pbx_extension_recording', 'sip_trunk', 'airtime_prepaid', 'airtime_unlimited', 'airtime_contract'];
    foreach ($domains as $domain) {
        $pbx_type = 'Basic';
        if ($domain->pabx_type == 'Phone Line') {
            $pbx_type = 'Basic';
        }
        if ($domain->pabx_type == 'PBX') {
            $pbx_type = 'Business';
        }
        if ($domain->pabx_type == 'Call Center') {
            $pbx_type = 'Enterprise';
        }
        \DB::table('sub_services')->whereIn('provision_type', $provision_types)->where('account_id', $domain->account_id)->update(['pbx_type' => $pbx_type, 'domain_uuid' => $domain->domain_uuid, 'pbx_domain' => $domain->pabx_domain]);
    }
}

/*
detail cell renderer

 init(params) {
    this.eGui = document.createElement('div');

    this.eGui.innerHTML = '';
    if(params.data.provision_type=='bundle'){

      var iframe_url = module_urls[1954]+'?bundle_id='+params.data.id+'&from_iframe=1';
      this.eGui.innerHTML = '<iframe src="'+iframe_url+'" width="100%" frameborder="0px" height="600px"  style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> ';

    }

  }

  getGui() {
    return this.eGui;
  }

  refresh(params) {
    return false;
  }

is row master

 return rowNode && rowNode.provision_type == 'bundle';

*/

function get_data_product_ids()
{
    return \DB::connection('default')->table('crm_products')
        ->join('crm_product_categories', 'crm_products.product_category_id', '=', 'crm_product_categories.id')
        ->where('crm_product_categories.department', 'Data')
        ->where('crm_products.status', 'Enabled')
        ->pluck('crm_products.id')->toArray();
}

function button_services_set_to_annual_billing($request)
{
    \DB::table('sub_services')->where('id', $request->id)->update(['bill_frequency' => 12]);

    return json_alert('Done');
}

function onload_services_set_actively_billed()
{

    $docdate = date('Y-m-01', strtotime('first day of next month'));
    \DB::table('sub_services')->update(['actively_billed' => 0]);
    \DB::table('sub_services')->where('status', '!=', 'Deleted')->whereRaw('to_cancel=0 or (to_cancel=1 and cancel_date>"'.$docdate.'")')->update(['actively_billed' => 1]);
    \DB::table('sub_services')->update(['total_excl' => \DB::raw('qty*price')]);
    \DB::table('sub_services')->update(['total_incl' => \DB::raw('qty*price_incl')]);
}

function schedule_update_services_usage()
{
    \DB::table('sub_services')->where('provision_type', 'airtime_contract')->update(['detail' => \DB::raw("CONCAT(FLOOR(usage_allocation*2),' minutes')")]);
    $sql = 'UPDATE sub_services 
    JOIN crm_products ON sub_services.product_id=crm_products.id
    SET sub_services.category_id = crm_products.product_category_id';
    \DB::statement($sql);
    DB::table('sub_services')->update(['not_used' => 0, 'usage_details' => '']);

    /// HOSTING + SITEBUILDER
    $result = (new Interworx)->listAllAccounts();
    DB::table('sub_services')->where('product_id', '!=', 600)->whereIn('provision_type', ['hosting', 'sitebuilder'])->update(['current_usage' => '']);
    foreach ($result['payload'] as $row) {
        $usage = ($row->max_storage / 100) * $row->storage_pct;

        $available = currency(($row->max_storage - $usage) / 1000);
        $max_storage = currency($row->max_storage / 1000);
        $not_used = 0;
        if ($row->storage_pct == 0) {
            $not_used = 1;
        }
        /*
        if ($row->storage_pct > 80) {
           $not_used = 'Over Usage';
        }
        if ($row->storage_pct < 4) {
           $not_used = 'Under Usage';
        }
        */
        $data = ['current_usage' => $available, 'usage_allocation' => $max_storage, 'usage_type' => 'GB', 'not_used' => $not_used, 'usage_details' => 'Percentage Used: '.currency($row->storage_pct)];
        DB::table('sub_services')->where('provision_type', 'hosting')->where('detail', $row->domain)->update($data);
    }

    // unlimited ext
    $subs = \DB::table('sub_services')->whereIn('product_id', [1393, 1394])->where('status', '!=', 'Deleted')->get();
    foreach ($subs as $sub) {
        $usage = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('unlimited_channels_usage')->first();
        if (empty($usage)) {
            $usage = 0;
        }
        $usage_average = ($usage == 0) ? 0 : ($usage / $subs->count());
        \DB::table('sub_services')->where('id', $sub->id)->update(['current_usage' => $usage_average, 'usage_details' => $usage_average.'/1500']);
    }
    /// PREPAID AIRTIME
    $subs = \DB::table('sub_services')->where('provision_type', 'airtime_prepaid')->orWhere('provision_type', 'airtime_contract')->get();
    foreach ($subs as $sub) {
        if ($sub->status != 'Deleted') {
            $not_used = 0;

            $duration = 0;
            try {
                $duration = DB::connection('pbx_cdr')->table('call_records_outbound')
                    ->where('hangup_time', '>=', date('Y-m-d', strtotime('-1 month')))
                    ->where('domain_name', $sub->detail)->sum('duration');
            } catch (\Throwable $ex) {
                exception_log($ex);
                $duration = 0;
            }

            if ($duration == 0) {
                $not_used = 1;
                $usage_details = '';
            } else {
                $minutes = $duration / 60;

                $usage_details = '';
            }

            \DB::table('sub_services')->where('id', $sub->id)->update(['not_used' => $not_used, 'usage_details' => $usage_details]);
        }
    }
    // PREPAID SMS
    $subs = \DB::table('sub_services')->where('provision_type', 'bulk_sms_prepaid')->where('status', '!=', 'Deleted')->get();
    foreach ($subs as $sub) {
        $not_used = 0;

        $sms_count = DB::table('isp_sms_message_queue')
            ->where('time_queued', '>=', date('Y-m-d', strtotime('-1 month')))
            ->where('account_id', $sub->account_id)
            ->count();
        if ($sms_count == 0) {
            $not_used = 1;
        }

        \DB::table('sub_services')->where('id', $sub->id)->update(['not_used' => $not_used, 'usage_details' => 'SMS: '.$sms_count]);
    }

    /// PHONE NUMBERS
    /*
    $subs = \DB::table('sub_services')->where('provision_type', 'phone_number')->where('status', '!=', 'Deleted')->get();
    foreach ($subs as $sub) {
        $not_used = 0;


        $inbound_calls = DB::connection('pbx_cdr')->table('call_records_inbound')
                ->where('hangup_time', '>=', date('Y-m-01'))
                ->where('callee_id_number', $sub->detail)->count();

        if ($inbound_calls == 0) {
            $not_used = 1;
        }

        \DB::table('sub_services')->where('id', $sub->id)->update(['not_used' => $not_used, 'usage_details' => 'Inbound Calls: '.$inbound_calls]);
    }
*/
    /// TELKOM LTE
    /*
    $a = new Axxess;
    $subs = \DB::table('sub_services')->where('provision_type', 'lte_sim_card')->where('status', '!=', 'Deleted')->get();
    foreach ($subs as $sub) {
        $guidServiceId = \DB::table('isp_data_lte_axxess_accounts')->where('reference',$sub->detail)->pluck('guidServiceId')->first();
        $axxess_usage = $a->getTelkomLteUsage($guidServiceId);
        $bytes = $axxess_usage->arrTelkomLteBandwidth->current_month->used;

        $bytes = intval($bytes);
        $gb_usage = human_filesize($bytes,2,'GB');
        $allocated = \DB::table('crm_products')->where('id',$sub->product_id)->pluck('provision_package')->first();
        $usage = str_replace('GB','',$gb_usage);
      //  \DB::table('sub_services')

    }
    */
    /// ACTIVE SUBSCRIPTIONS
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
}

function schedule_services_set_pbx_service_status()
{
    /*
        Service Status Column
        Show if an extension is registered
        Show the last inbound call to a Phone Number
    */

    if (in_array(12, session('app_ids'))) {
        \DB::table('sub_services')->update(['service_status' => '']);
        $exts = \DB::connection('pbx')->table('v_extensions')
            ->select('v_extensions.registration_status', 'v_domains.account_id', 'v_extensions.extension')
            ->join('v_domains', 'v_domains.domain_uuid', '=', 'v_extensions.domain_uuid')
            ->where('v_extensions.registration_status', '>', '')
            ->get();
        foreach ($exts as $ext) {
            \DB::table('sub_services')->where('detail', $ext->extension)->where('account_id', $ext->account_id)->update(['service_status' => $ext->registration_status]);
        }

        $pn = \DB::connection('pbx')->table('p_phone_numbers')
            ->select('p_phone_numbers.last_inbound_call', 'v_domains.account_id', 'p_phone_numbers.number')
            ->join('v_domains', 'v_domains.domain_uuid', '=', 'p_phone_numbers.domain_uuid')
            ->whereNotNull('p_phone_numbers.last_inbound_call')
            ->get();
        foreach ($pn as $p) {
            \DB::table('sub_services')->where('detail', $p->number)->where('account_id', $p->account_id)->update(['service_status' => $p->last_inbound_call]);
        }
    }

}

/* fibre and lte status match processes*/

function aftersave_set_fibre_lte_status_match()
{
    $subscriptions = \DB::table('sub_services')->where('provision_type', 'like', '%fibre%')->where('status', '!=', 'Deleted')->get();

    foreach ($subscriptions as $s) {
        \DB::table('isp_data_fibre')->where('subscription_id', $s->id)->update(['subscription_status' => $s->status]);
    }

    $subscriptions = \DB::table('sub_services')->where('provision_type', 'like', '%lte%')->where('status', '!=', 'Deleted')->get();

    foreach ($subscriptions as $s) {
        \DB::table('isp_data_lte_vodacom_accounts')->where('subscription_id', $s->id)->update(['subscription_status' => $s->status]);
        \DB::table('isp_data_lte_axxess_accounts')->where('subscription_id', $s->id)->update(['subscription_status' => $s->status]);
    }
    \DB::statement("UPDATE isp_data_fibre SET status_match=0 WHERE status!='Deleted' and external_status!=subscription_status");
    \DB::statement("UPDATE isp_data_fibre SET status_match=1 WHERE status!='Deleted' and external_status=subscription_status");
    \DB::statement("UPDATE isp_data_lte_vodacom_accounts SET status_match=0 WHERE status!='Deleted' and external_status!=subscription_status");
    \DB::statement("UPDATE isp_data_lte_vodacom_accounts SET status_match=1 WHERE status!='Deleted' and external_status=subscription_status");
    \DB::statement("UPDATE isp_data_lte_axxess_accounts SET status_match=0 WHERE status!='Deleted' and external_status!=subscription_status");
    \DB::statement("UPDATE isp_data_lte_axxess_accounts SET status_match=1 WHERE status!='Deleted' and external_status=subscription_status");
}
function schedule_set_fibre_lte_status_match()
{
    $subscriptions = \DB::table('sub_services')->where('provision_type', 'like', '%fibre%')->where('status', '!=', 'Deleted')->get();

    foreach ($subscriptions as $s) {
        \DB::table('isp_data_fibre')->where('subscription_id', $s->id)->update(['subscription_status' => $s->status]);
    }

    $subscriptions = \DB::table('sub_services')->where('provision_type', 'like', '%lte%')->where('status', '!=', 'Deleted')->get();

    foreach ($subscriptions as $s) {
        \DB::table('isp_data_lte_vodacom_accounts')->where('subscription_id', $s->id)->update(['subscription_status' => $s->status]);
        \DB::table('isp_data_lte_axxess_accounts')->where('subscription_id', $s->id)->update(['subscription_status' => $s->status]);
    }
    \DB::statement("UPDATE isp_data_fibre SET status_match=0 WHERE status!='Deleted' and external_status!=subscription_status");
    \DB::statement("UPDATE isp_data_fibre SET status_match=1 WHERE status!='Deleted' and external_status=subscription_status");
    \DB::statement("UPDATE isp_data_lte_vodacom_accounts SET status_match=0 WHERE status!='Deleted' and external_status!=subscription_status");
    \DB::statement("UPDATE isp_data_lte_vodacom_accounts SET status_match=1 WHERE status!='Deleted' and external_status=subscription_status");
    \DB::statement("UPDATE isp_data_lte_axxess_accounts SET status_match=0 WHERE status!='Deleted' and external_status!=subscription_status");
    \DB::statement("UPDATE isp_data_lte_axxess_accounts SET status_match=1 WHERE status!='Deleted' and external_status=subscription_status");
}

function schedule_axxess_lte_import_status()
{

    $axxess = new Axxess;
    $lte_accounts = \DB::table('isp_data_lte_axxess_accounts')->get();

    foreach ($lte_accounts as $lte_account) {
        $details = $axxess->getServiceById($lte_account->guidServiceId);
        if (isset($details->intCode) && $details->intCode == 200) {

            if (! empty($details->arrServices) && is_array($details->arrServices) && count($details->arrServices) > 0) {
                $intSuspendReasonId = (! empty($details->arrServices[0]->intSuspendReasonId)) ? $details->arrServices[0]->intSuspendReasonId : null;
                $external_status = 'Enabled';
                if ($intSuspendReasonId == 18) {
                    $external_status = 'Disabled';
                } elseif ($intSuspendReasonId) {
                    $external_status = 'Deleted';

                }
                \DB::table('isp_data_lte_axxess_accounts')->where('id', $lte_account->id)->update(['external_status' => $external_status]);
            } else {
                \DB::table('isp_data_lte_axxess_accounts')->where('id', $lte_account->id)->update(['external_status' => 'Deleted']);
            }
        }
    }

    \DB::table('isp_data_lte_axxess_accounts')->update(['subscription_status' => 'Deleted']);
    $subscriptions = \DB::table('sub_services')->where('provision_type', 'like', '%lte%')->where('status', '!=', 'Deleted')->get();

    foreach ($subscriptions as $s) {
        \DB::table('isp_data_lte_axxess_accounts')->where('subscription_id', $s->id)->update(['subscription_status' => $s->status]);
    }

    \DB::statement("UPDATE isp_data_lte_axxess_accounts SET status_match=0 WHERE status!='Deleted' and external_status!=subscription_status");
    \DB::statement("UPDATE isp_data_lte_axxess_accounts SET status_match=1 WHERE status!='Deleted' and external_status=subscription_status");
}

function schedule_set_subscription_status()
{
    if (! is_main_instance()) {
        return false;
    }
    $account_ids = \DB::table('sub_services')->where('status', '!=', 'Deleted')->pluck('account_id')->unique()->toArray();
    foreach ($account_ids as $account_id) {
        $account_status = \DB::table('crm_accounts')->where('id', $account_id)->pluck('status')->first();
        $c = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('status', '!=', $account_status)->where('account_id', $account_id)->count();
        if ($c) {
            $erp_subscriptions = new ErpSubs;
            $erp_subscriptions->setStatus($account_id, $account_status);
        }
    }

}

function button_hosting_open_subscription($request)
{
    $service = \DB::table('isp_host_websites')->where('id', $request->id)->get()->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?id='.$service->subscription_id);
}

function button_fibre_open_subscription($request)
{
    $service = \DB::table('isp_data_fibre')->where('id', $request->id)->get()->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?id='.$service->subscription_id);
}

function button_lte_open_subscription($request)
{
    $service = \DB::table('isp_data_lte_axxess_accounts')->where('id', $request->id)->get()->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?id='.$service->subscription_id);
}
function button_vodacom_lte_open_subscription($request)
{
    $service = \DB::table('isp_data_lte_vodacom_accounts')->where('id', $request->id)->get()->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?id='.$service->subscription_id);
}

function button_ip_ranges_open_subscription($request)
{
    $service = \DB::table('isp_data_ip_ranges')->where('id', $request->id)->get()->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?id='.$service->subscription_id);
}

function button_pbx_domains_open_subscriptions($request)
{
    $service = \DB::connection('pbx')->table('v_domains')->where('id', $request->id)->get()->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?pbx_domain='.$service->domain_name);
}

function button_extensions_open_subscription($request)
{
    $service = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();
    $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $service->domain_uuid)->pluck('account_id')->first();
    $id = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('detail', $service->extension)->where('account_id', $account_id)->pluck('id')->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?id='.$id);
}
function button_numbers_open_subscription($request)
{
    $service = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->get()->first();
    $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $service->domain_uuid)->pluck('account_id')->first();
    $id = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('detail', $service->extension)->where('account_id', $account_id)->pluck('id')->first();
    $menu_name = get_menu_url_from_table('sub_services');

    return redirect()->to($menu_name.'?id='.$id);
}

function button_services_view_details($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    if ($sub->provision_type == 'hosting' || $sub->provision_type == 'domain_name' || $sub->provision_type == 'sitebuilder') {
        $domain = \DB::table('isp_host_websites')->where('domain', $sub->detail)->get()->first();

        return redirect()->to('hosting_login/'.$sub->account_id.'/'.$domain->id);
    }

    if ($sub->provision_type == 'lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_vodacom_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'mtn_lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'telkom_lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'iptv' || $sub->provision_type == 'iptv_global') {
        $menu_name = get_menu_url_from_table('isp_data_iptv');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'fibre') {
        $menu_name = get_menu_url_from_table('isp_data_fibre');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->pbx_domain > '') {
        $menu_name = get_menu_url_from_table('v_domains');
        $pbx = new FusionPBX;
        $pbx->pbx_login($sub->account_id);
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('domain_name')->first();

        return redirect()->to($menu_name.'?domain_name='.$domain_name);
    }
    /*
    if ('phone_number' == $sub->provision_type) {
        $menu_name = get_menu_url_from_table('p_phone_numbers');
        $pbx = new FusionPBX();
        $pbx->pbx_login($sub->account_id);
        $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('domain_uuid')->first();
        return redirect()->to($menu_name.'?domain_uuid='.$domain_uuid.'&number='.$sub->detail);
    }
    if ('pbx_extension' == $sub->provision_type || 'sip_trunk' == $sub->provision_type) {
        $menu_name = get_menu_url_from_table('v_extensions');
        $pbx = new FusionPBX();
        $pbx->pbx_login($sub->account_id);
        $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('domain_uuid')->first();
        return redirect()->to($menu_name.'?domain_uuid='.$domain_name.'&extension='.$sub->detail);
    }
    if ('airtime_prepaid' == $sub->provision_type || 'airtime_unlimited' == $sub->provision_type || 'airtime_contract' == $sub->provision_type) {
        $menu_name = get_menu_url_from_table('v_domains');
        $pbx = new FusionPBX();
        $pbx->pbx_login($sub->account_id);
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('domain_name')->first();
        return redirect()->to($menu_name.'?domain_name='.$domain_name);
    }
    */
    if ($sub->provision_type == 'bulk_sms' || $sub->provision_type == 'bulk_sms_prepaid') {
        return redirect()->to('sms_panel/'.$sub->account_id);
    }
}

function button_services_view_details_fibre($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    if ($sub->provision_type == 'fibre') {
        $menu_name = get_menu_url_from_table('isp_data_fibre');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }

}

function button_services_view_details_phone_numbers($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    $menu_name = get_menu_url_from_module_id(1997);
    $pbx = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->get()->first();

    return redirect()->to($menu_name.'?domain_uuid='.$pbx->domain_uuid);

}

function button_services_view_details_pbx($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    if ($sub->provision_type == 'phone_number' || $sub->provision_type == 'pbx_extension' || $sub->provision_type == 'sip_trunk' || $sub->provision_type == 'airtime_prepaid' || $sub->provision_type == 'airtime_unlimited' || $sub->provision_type == 'airtime_contract') {
        $menu_name = get_menu_url_from_table('v_domains');
        $pbx = new FusionPBX;
        $pbx->pbx_login($sub->account_id);
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('account_id', $sub->account_id)->pluck('domain_name')->first();

        return redirect()->to($menu_name.'?domain_name='.$domain_name);
    }
}

function button_services_view_details_hosting($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    if ($sub->provision_type == 'hosting' || $sub->provision_type == 'domain_name' || $sub->provision_type == 'sitebuilder') {
        $domain = \DB::table('isp_host_websites')->where('domain', $sub->detail)->get()->first();

        return redirect()->to('hosting_login/'.$sub->account_id.'/'.$domain->id);
    }
}

function button_services_view_details_sms($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    if ($sub->provision_type == 'bulk_sms' || $sub->provision_type == 'bulk_sms_prepaid') {
        return redirect()->to('sms_panel/'.$sub->account_id);
    }
}

function button_services_view_details_lte($request)
{

    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    if ($sub->provision_type == 'lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_vodacom_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'mtn_lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
    if ($sub->provision_type == 'telkom_lte_sim_card') {
        $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

        return redirect()->to($menu_name.'?subscription_id='.$request->id);
    }
}

function button_services_view_details_ipranges($request)
{

    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    if (str_contains($sub->provision_type, 'ip_range')) {
        $menu_name = get_menu_url_from_table('isp_data_ip_ranges');

        return redirect()->to($menu_name.'?ip_range='.$sub->detail);
    }

}

function aftersave_subscription_set_price_incl($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    $account = dbgetaccount($sub->account_id);
    $reseller = dbgetaccount($account->partner_id);
    if ($account->currency == 'ZAR' && $reseller->vat_enabled == 1) {
        \DB::table('sub_services')->where('id', $request->id)->update(['price_incl' => $sub->price * 1.15]);
    }
}

function set_subscription_topup_amounts()
{
    \DB::table('sub_services')->update(['has_topups' => 0]);
    $topups = \DB::table('sub_service_topups')->get();
    foreach ($topups as $t) {
        $package_amount = \DB::table('crm_products')->where('id', $t->product_id)->pluck('provision_package')->first();
        $lines = \DB::table('crm_document_lines')->where('product_id', $t->product_id)->where('document_id', $t->invoice_id)->get();

        $amount = 0;
        foreach ($lines as $line) {
            $amount += $line->qty * $package_amount;
        }
        \DB::table('sub_service_topups')->where('id', $t->id)->update(['amount' => $amount]);
        \DB::table('sub_services')->where('id', $t->subscription_id)->update(['has_topups' => 1]);
    }
}

function aftersave_subscription_reconcile_set_qty($request)
{
    $result = '';
    $provider_qty_total = 0;
    $subscription_qty_total = 0;

    $billing_date = date('Y-m-01');

    foreach ($request->all() as $key => $qty) {
        if (empty($qty)) {
            $qty = 0;
        }
        $product_id = false;
        if (str_starts_with($key, 'fibre') || str_starts_with($key, 'lte')) {
            $product_id = \DB::table('crm_products')->where('code', $key)->pluck('id')->first();
            if ($product_id) {
                $subscription_qty = \DB::table('sub_services_lastmonth')->where('to_cancel', 0)->where('status', '!=', 'Pending')->where('status', '!=', 'Deleted')->where('product_id', $product_id)->count();
                $provider_qty_total += $qty;
                $subscription_qty_total += $subscription_qty;
                if ($qty != $subscription_qty) {
                    $result .= $key.' provider_qty: '.$qty.' subscription_qty: '.$subscription_qty.'. '.PHP_EOL;
                }
            } else {
                $result .= $key.' product_id not found.'.PHP_EOL;
            }
        }
    }
    $reconciled = 0;
    if ($provider_qty_total == $subscription_qty_total) {
        $reconciled = 1;
    }
    $data = [
        'faulty_qty' => $result,
        'reconciled' => $reconciled,
        'billing_period' => $billing_date,
    ];

    \DB::table('sub_service_reconcile')->where('id', $request->id)->update($data);
}

function button_subscriptions_toggle_prepaid_filter($request)
{
    if (empty(session('subs_prepaid_filter'))) {
        session(['subs_prepaid_filter' => true]);
    } else {
        session(['subs_prepaid_filter' => false]);
    }

    return json_alert('Filter updated.');
}

function verify_subscriptions()
{
    if (is_main_instance()) {
        $sub = new ErpSubs;
        $sub->verifySubscriptions();
    }
}

function create_prorata_invoice($account_id, $product_id, $detail)
{
    $docid = \DB::table('crm_documents')->where('reference', 'like', '%Product Activation%')->where('reference', 'like', '%'.$detail.'%')->pluck('id')->first();
    if ($docid) {
        return ['id' => $docid];
    }
    $reseller_user = 0;
    $account = dbgetaccount($account_id);
    if ($account->partner_id != 1) {
        $reseller_user = $account_id;
        $account_id = $account->partner_id;
    }
    $product_code = \DB::connection('default')->table('crm_products')->where('id', $product_id)->pluck('code')->first();
    $pricing = pricelist_get_price($account_id, $product_id, 1);
    $amount = $pricing->full_price;
    $full_price = $pricing->full_price;

    $admin = dbgetaccount(1);
    if ($admin->vat_enabled) {
        $amount = $amount * 1.15;
    }

    $reference = 'Product Activation - '.ucwords(str_replace('_', ' ', $product_code)).' - '.$detail;

    $data = [
        'docdate' => date('Y-m-d'),
        'doctype' => 'Tax Invoice',
        'completed' => 1,
        'account_id' => $account_id,
        'total' => $amount,
        'reference' => $reference,
        'qty' => [1],
        'price' => [$amount],
        'full_price' => [$full_price],
        'product_id' => [$product_id],
        'subscription_created' => 1,
    ];
    if ($reseller_user) {
        $data['reseller_user'] = $reseller_user;
    }
    $db = new DBEvent;

    return $db->setTable('crm_documents')->setProperties(['validate_document' => 1])->save($data);
}

function create_migration_invoice($account_id, $reseller_user, $product_id, $amount, $reference)
{
    $data = [
        'docdate' => date('Y-m-d'),
        'doctype' => 'Tax Invoice',
        'completed' => 1,
        'account_id' => $account_id,
        'reseller_user' => $reseller_user,
        'total' => $amount,
        'reference' => $reference,
        'qty' => [1],
        'price' => [$amount],
        'full_price' => [$amount],
        'product_id' => [$product_id],
        'subscription_created' => 1,
    ];
    $db = new DBEvent;

    return $db->setTable('crm_documents')->setProperties(['validate_document' => 1])->save($data);
}

function button_activations_product_ready($request)
{
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
    if (! in_array($sub->provision_type, ['product', 'products'])) {
        return json_alert('Incorrect activation type.', 'warning');
    }
    if ($sub->status != 'Pending') {
        return json_alert('Only pending activations can be set.', 'warning');
    }

    \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Ready for collection']);

    return json_alert('Ready for collection set.', 'success');

}

function button_activations_product_activation($request)
{
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
    // aa($sub);
    if (! in_array($sub->provision_type, ['product', 'products'])) {
        return json_alert('Incorrect activation type.', 'warning');
    }
    if (! $sub->printed) {
        return json_alert('Invoice needs to be printed.', 'warning');
    }
    // if (empty($sub->pod_file)) {
    //     return json_alert('POD file needs to be uploaded.', 'warning');
    //  }

    $product_plan = \DB::table('crm_products')->where('id', $sub->product_id)->pluck('provision_plan_id')->first();

    $product_monthly = \DB::table('crm_products')->where('is_subscription', 1)->where('id', $request->product_id)->count();
    if ($product_monthly) {
        $account = dbgetaccount($sub->account_id);
        if ($account->partner_id == 1) {
            $debit_order_active = account_has_authorised_debit_order($account->id);
        } else {
            $debit_order_active = account_has_authorised_debit_order($account->partner_id);
        }
        if (! $debit_order_active) {
            return json_alert('Debit order check failed. Please process a debit order.', 'warning');
        }
    }

    $activation_plan_exists = \DB::table('sub_activation_types')->where('id', $product_plan)->count();
    if ($product_monthly) {
        if (! $activation_plan_exists) {
            return json_alert('Activation plan does not exists, please contact admin.', 'warning');
        }

        return redirect()->to('provision?type=operations&id='.$request->id);
    } else {
        \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Enabled']);

        return json_alert('Activation complete.', 'success');
    }
}

function process_deactivations()
{
    $subs = \DB::table('sub_activations')->where('provision_type', 'deactivate')->where('status', 'Pending')->get();

    foreach ($subs as $sub) {
        if ($sub->status != 'Pending') {
            return json_alert('Status is not pending', 'warning');
        }
        if ($sub->provision_type != 'deactivate') {
            return json_alert('Incorrect provision_type', 'warning');
        }
        if (! $sub->subscription_id) {
            return json_alert('Subscription Id not set', 'warning');
        }
        $ErpSubs = new ErpSubs;
        \DB::table('sub_activations')->where('id', $sub->id)->update(['status' => 'Enabled']);
        $result = $ErpSubs->deleteSubscription($sub->subscription_id);
        if ($result !== true) {
            return json_alert($result, 'warning');
        }

        module_log(334, $sub->subscription_id, 'deactivated');
    }
}

function button_activation_credit($request)
{
    $sub = \DB::table('sub_activations')->where('id', $request->id)->get()->first();
    if (empty($sub->invoice_id)) {
        return json_alert('No Invoice Id set', 'warning');
    }
    create_credit_note_from_invoice($sub->invoice_id);
    \DB::table('sub_activations')->where('id', $request->id)->update(['status' => 'Deleted']);
    \DB::table('sub_activation_steps')->where('provision_id', $request->id)->where('service_table', 'sub_activations')->delete();
    module_log(554, $request->id, 'credit note created');

    return json_alert('Done');
}

function button_operations_upload_pod($request)
{
    $data['id'] = $request->id;

    return view('__app.button_views.deliveries_pod', $data);
}

function button_operations_view_invoice($request)
{
    $invoice_id = \DB::table('sub_activations')->where('id', $request->id)->pluck('invoice_id')->first();
    if (! $invoice_id) {
        return json_alert('No invoice attached', 'warning');
    }
    $exists = \DB::table('crm_documents')->where('id', $invoice_id)->count();
    if (! $exists) {
        return json_alert('No invoice attached', 'warning');
    }
    $menu_name = get_menu_url_from_table('crm_documents');

    return Redirect::to($menu_name.'/view/'.$invoice_id);
}

function button_operations_print_invoice($request)
{
    $invoice_id = \DB::table('sub_activations')->where('id', $request->id)->pluck('invoice_id')->first();
    if (! $invoice_id) {
        return json_alert('No invoice attached', 'warning');
    }
    $exists = \DB::table('crm_documents')->where('id', $invoice_id)->count();
    if (! $exists) {
        return json_alert('No invoice attached', 'warning');
    }
    $row = \DB::table('crm_documents')->where('id', $invoice_id)->get()->first();
    $pdf_name = str_replace(' ', '_', ucfirst($row->doctype).' '.$row->id);
    $file = $pdf_name.'.pdf';

    $pdf = document_pdf($row->id, false);
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);
    $url = attachments_url().$file;
    \DB::table('sub_activations')->where('id', $request->id)->update(['printed' => 1]);

    return json_alert('Done', 'success', ['print' => $url]);
}

function button_services_reset_sitebuilder_ftp($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    $website = \DB::table('isp_host_websites')->where('account_id', $sub->account_id)->where('domain', $sub->detail)->get()->first();

    $iw = new Interworx;
    $ftp = $iw->setServer($website->server)->setDomain($website->domain)->siteBuilderFTP();

    \DB::table('isp_host_websites')->where('domain', $website->domain)->update(['ftp_user' => $ftp['user'], 'ftp_pass' => $ftp['pass']]);

    return json_alert('Sitebuilder FTP password reset.');
}

function button_services_send_fibre_details($request = false)
{
    try {
        $subs = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('provision_type', 'fibre')->get();
        foreach ($subs as $sub) {
            $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

            $details = \DB::table('isp_data_fibre')->where('account_id', $sub->account_id)->where('subscription_id', $sub->id)->get()->first();
            $data['username'] = $details->username;
            $data['password'] = $details->fibre_password;
            $data['details'] = $details;
            $wifi_username = explode('@', $details->username);
            $data['wifi_username'] = strtolower($wifi_username[0]);
            $data['wifi_password'] = 'zyx123';
            //$data['test_debug'] = 1;
            $data['internal_function'] = 'fibre_details';

            erp_process_notification($sub->account_id, $data);
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        //dev_email('subscription details email error');
        exception_log($ex->getMessage());
        exception_log($ex->getTraceAsString());

        return json_alert('Subscription details error', 'warning');
    }

    return json_alert('Done');
}

function button_services_send_sms($request)
{
    try {
        $id = $request->id;
        $sub = \DB::table('sub_services')->where('id', $id)->get()->first();

        $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

        if (empty($product)) {
            return json_alert('Invalid product', 'error');
        }

        $provision_plan = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->get()->first();
        $provision_plan_name = $provision_plan->name;
        $provision_plan_id = $provision_plan->id;
        if (empty($provision_plan_name)) {
            return json_alert('Invalid activation plan', 'error');
        }
        $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $provision_plan_id)->where('email_id', '>', '')->orderby('step', 'desc')->pluck('email_id')->first();
        if (empty($email_id)) {
            return json_alert('No setup instructions available for this product', 'error');
        }

        $message_template = \DB::table('crm_email_manager')->where('id', $email_id)->pluck('message')->first();
        $breaks = ['<br />', '<br>', '<br/>'];
        $message_template = html_entity_decode(strip_tags(str_ireplace($breaks, PHP_EOL, $message_template)));
        if (empty($message_template)) {
            return json_alert('Invalid sms message template', 'error');
        }

        $customer = dbgetaccount($sub->account_id);

        $phone_number = valid_za_mobile_number($customer->phone);
        if (! $phone_number) {
            $phone_number = valid_za_mobile_number($customer->contact_phone_1);
        }
        if (! $phone_number) {
            $phone_number = valid_za_mobile_number($customer->contact_phone_2);
        }
        if (! $phone_number) {
            $phone_number = valid_za_mobile_number($customer->contact_phone_3);
        }

        if (! $phone_number) {
            return json_alert('Invalid customer phone number', 'error');
        }

        $data['detail'] = $sub->detail;
        $data['product'] = ucwords(str_replace('_', ' ', $product->code));
        $data['product_code'] = $product->code;
        $data['product_description'] = $product->name;
        $data['portal_url'] = get_whitelabel_domain($customer->partner_id);

        $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer);
        if (! empty($activation_data) && count($activation_data) > 0) {
            foreach ($activation_data as $k => $v) {
                $data[$k] = $v;
            }
        }

        $sms_message = erp_email_blend($message_template, $data);

        $url = get_menu_url_from_table('isp_sms_messages').'/edit?account_id=12&message='.urlencode($sms_message).'&numbers='.$phone_number;

        return redirect()->to($url);
    } catch (\Throwable $ex) {
        exception_log($ex);
        //dev_email('subscription details email error');
        aa($ex->getMessage());
        aa($ex->getTraceAsString());

        return json_alert($ex->getMessage(), 'warning');
    }
}

function button_services_send_details($request)
{
    try {
        $id = $request->id;

        $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

        $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

        if (empty($product)) {
            return json_alert('Invalid product', 'error');
        }
        $provision_plan = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->get()->first();
        $provision_plan_name = $provision_plan->name;
        $provision_plan_id = $provision_plan->id;

        if (empty($provision_plan_name)) {
            return json_alert('Invalid activation plan', 'error');
        }
        $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $provision_plan_id)->where('email_id', '>', '')->orderby('step', 'desc')->pluck('email_id')->first();
        if (empty($email_id)) {
            return json_alert('No setup instructions available for this product', 'error');
        }

        $customer = dbgetaccount($sub->account_id);

        $data['detail'] = $sub->detail;
        $data['product'] = ucwords(str_replace('_', ' ', $product->code));
        $data['product_code'] = $product->code;
        $data['product_description'] = $product->name;
        $data['portal_url'] = get_whitelabel_domain($customer->partner_id);

        $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer);
        if (! empty($activation_data) && count($activation_data) > 0) {
            foreach ($activation_data as $k => $v) {
                $data[$k] = $v;
            }
        }

        $data['activation_email'] = true;

        return email_form($email_id, $sub->account_id, $data);
    } catch (\Throwable $ex) {
        exception_log($ex);
        //dev_email('subscription details email error');
        aa($ex->getMessage());
        aa($ex->getTraceAsString());

        return json_alert('Subscription details error', 'warning');
    }
}

// SUPPORT BUTTONS PBX

function button_export_cdr($request)
{
    if (empty(session('pbx_account_id')) || session('pbx_account_id') == 1) {
        if (session('role_level') == 'Admin') {
            $file_name = export_cdr('pbx_cdr', 0);
            $file_path = attachments_path().$file_name;

            return json_alert(attachments_url().$file_name, 'reload');
        }

        return json_alert('Switch to customer account to export cdr.', 'warning');
    } else {
        $pbx = \DB::table('isp_voice_pbx_domains')->where('account_id', session('pbx_account_id'))->get()->first();
        $pbx_domain = $pbx->pabx_domain;
        $connection = 'pbx_cdr';
    }

    $data = [];
    $months = [];
    for ($i = 0; $i < 4; $i++) {
        if ($i == 0) {
            $date = date('Y-m');
        } else {
            $date = date('Y-m', strtotime('- '.$i.' months'));
        }
        if (date('Y-m-01', strtotime($date)) != date('Y-m-01')) {
            $table = 'call_records_'.strtolower(date('M', strtotime($date)));
        } else {
            $table = 'call_records_outbound';
        }

        if (date('Y-m', strtotime('-1 month')) == $date) {
            $exists = \DB::connection($connection)->table('call_records_outbound_lastmonth')
                ->select('domain_name')
                ->where('domain_name', $pbx_domain)
                ->where('direction', 'outbound')
                ->where('duration', '>', 0)
                ->count();

            if ($exists) {
                $months[] = $date;
            }
        } else {
            if (! \Schema::connection($connection)->hasTable($table)) {
                continue;
            }

            $exists = \DB::connection($connection)->table($table)
                ->select('domain_name')
                ->where('domain_name', $pbx_domain)
                ->where('direction', 'outbound')
                ->where('duration', '>', 0)
                ->count();

            if ($exists) {
                $months[] = $date;
            }
        }
    }

    $data['months'] = $months;
    $data['connection'] = $connection;

    return view('__app.button_views.cdr_export', $data);
}

// ACCOUNTING BUTTONS
function button_services_migrate_subscription($request)
{
    $subscription = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    if ($subscription->provision_type == 'airtime_contract') {
        $package_amount = \DB::table('crm_products')->where('id', $subscription->product_id)->pluck('provision_package')->first();
        $data = [
            'subscription' => $subscription,
            'subscription_id' => $request->id,
            'package_amount' => $package_amount,
        ];

        return view('__app.button_views.migrate_airtime', $data);
    } else {

        $sub = new ErpSubs;
        $available_products = $sub->getAvailableMigrateProducts($request->id);
        if (! is_array($available_products)) {
            return json_alert($available_products, 'warning');
        }
        if (is_array($available_products) && count($available_products) == 0) {
            return json_alert('No products available', 'warning');
        }
        $data = [
            'subscription' => $subscription,
            'subscription_id' => $request->id,
            'available_products' => $available_products,
        ];

        return view('__app.button_views.migrate_subscription', $data);

    }
}

function button_services_migrate_airtime($request)
{
    $sub = new ErpSubs;
    $available_products = $sub->getAvailableMigrateProducts($request->id);
    if (! is_array($available_products)) {
        return json_alert($available_products, 'warning');
    }
    if (is_array($available_products) && count($available_products) == 0) {
        return json_alert('No products available', 'warning');
    }
    $subscription = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    $data = [
        'subscription' => $subscription,
        'subscription_id' => $request->id,
        'available_products' => $available_products,
    ];

    return view('__app.button_views.migrate_subscription', $data);
}

// ADMIN BUTTONS
function button_hosting_details_control_panel($request)
{
    $sub = \DB::table('isp_host_websites')->where('id', $request->id)->get()->first();

    return redirect()->to('hosting_login/'.$sub->account_id.'/'.$sub->id);
}

function button_services_view_invoice($request)
{
    $invoice_id = \DB::table('sub_services')->where('id', $request->id)->pluck('invoice_id')->first();
    if (! $invoice_id) {
        return json_alert('No invoice attached', 'warning');
    }
    $exists = \DB::table('crm_documents')->where('id', $invoice_id)->count();
    if (! $exists) {
        return json_alert('No invoice attached', 'warning');
    }
    $menu_name = get_menu_url_from_table('crm_documents');

    return Redirect::to($menu_name.'/view/'.$invoice_id);
}

function button_subscriptiontopups_view_invoice($request)
{
    $invoice_id = \DB::table('sub_service_topups')->where('id', $request->id)->pluck('invoice_id')->first();
    if (! $invoice_id) {
        return json_alert('No invoice attached', 'warning');
    }
    $exists = \DB::table('crm_documents')->where('id', $invoice_id)->count();
    if (! $exists) {
        return json_alert('No invoice attached', 'warning');
    }
    $menu_name = get_menu_url_from_table('crm_documents');

    return Redirect::to($menu_name.'/view/'.$invoice_id);
}

function button_services_view_topups($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    $menu_name = get_menu_url_from_table('sub_service_topups');

    return Redirect::to($menu_name.'?account_id='.$sub->account_id.'&subscription_id='.$sub->id);
}

///////SCHEDULES

function schedule_statement_disabled_notification()
{
    $accounts = \DB::table('crm_accounts')->where('type', '!=', 'lead')->where('partner_id', 1)->where('status', '!=', 'Deleted')->get();
    //$accounts = \DB::table('crm_accounts')->where('id', 12)->get();

    foreach ($accounts as $account) {
        if ($account->balance > 0) {
            $pdf = statement_pdf($account->id);
            $file = 'Statement_'.$account->id.'_'.date('Y_m_d').'.pdf';
            $filename = attachments_path().$file;
            if (file_exists($filename)) {
                unlink($filename);
            }
            $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
            $data['attachment'] = $file;
            $data['account_id'] = $account->id;

            $function_variables = get_defined_vars();
            $data['function_name'] = __FUNCTION__;
            erp_process_notification($account->id, $data, $function_variables);
        }
    }
}

function schedule_statement_enabled_notification()
{
    $accounts = \DB::table('crm_accounts')->where('type', '!=', 'lead')->where('partner_id', 1)->where('status', '!=', 'Deleted')->get();
    //$accounts = \DB::table('crm_accounts')->where('id', 12)->get();
    foreach ($accounts as $account) {
        if ($account->balance < 0) {
            $pdf = statement_pdf($account->id);
            $file = 'Statement_'.$account->id.'_'.date('Y_m_d').'.pdf';
            $filename = attachments_path().$file;
            if (file_exists($filename)) {
                unlink($filename);
            }
            $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
            $data['attachment'] = $file;
            $data['account_id'] = $account->id;

            $function_variables = get_defined_vars();
            $data['function_name'] = __FUNCTION__;
            erp_process_notification($account->id, $data, $function_variables);
        }
    }
}

function schedule_monthly_statement()
{
    $accounts = \DB::table('crm_accounts')
        ->where('status', '!=', 'Deleted')
        ->where('type', '!=', 'lead')
        ->where('subs_total', 0)
        ->where('balance', '>', 5)
        ->where('partner_id', 1)
        ->get();

    foreach ($accounts as $account) {
        $data['function_name'] = 'schedule_monthly_statement';
        erp_process_notification($account->id, $data);
    }
}

function button_services_usage_notification($request)
{
    set_airtime_balances_from_pbx();
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    if ($sub->provision_type == 'hosting' || $sub->provision_type == 'bulk_sms_prepaid' ||
        $sub->provision_type == 'bulk_sms' || $sub->provision_type == 'airtime_contract' || $sub->provision_type == 'airtime_prepaid') {
        $account_id = $sub->account_id;
        $product_name = \DB::table('crm_products')->where('id', $sub->product_id)->pluck('code')->first();
        $notification_title = ucwords(str_replace('_', ' ', $product_name));
        $detail = $sub->detail;
        if ($sub->provision_type == 'airtime_prepaid' || $sub->provision_type == 'airtime_contract') {
            $available = \DB::table('isp_voice_pbx_domains')->where('account_id', $sub->account_id)->pluck('pbx_balance')->first();
            $available = currency($available);
        } elseif (str_contains($sub->provision_type, 'sms')) {
            $available = intval($sub->current_usage.' '.$sub->usage_type);
        } else {
            if ($sub->current_usage > 0) {
                $used = currency($sub->current_usage).' '.$sub->usage_type;
            }
            $allocated = currency($sub->usage_allocation).' '.$sub->usage_type;
            $available = currency($sub->usage_allocation - abs($sub->current_usage)).' '.$sub->usage_type;
        }

        if ($sub->provision_type == 'hosting') {
            $percentage_used = currency((($sub->usage_allocation - $sub->current_usage) / $sub->usage_allocation) * 100);

            if ($percentage_used < 90) {
                $data['instructions'] = 'Your hosting storage usage is '.$percentage_used.'%.';
            } else {
                $data['instructions'] = 'Your hosting storage usage is above 90%, please clear deleted mailbox items to prevent usage errors on your website.';
            }
        }

        $function_variables = [];
        $data['internal_function'] = 'usage_notification';
        $data['available'] = $available;
        $data['used'] = $used;
        $data['allocated'] = $allocated;
        $data['notification_title'] = $notification_title;
        $data['detail'] = $sub->detail;
        //if ($sub->account_id == 9751) { //VCA
        //   $data['bcc_email'] = 'ahmed@telecloud.co.za';
        //}

        erp_process_notification($sub->account_id, $data, $function_variables);

        return json_alert('Usage notification sent.');
    } else {
        return json_alert('Usage notification cannot be sent for this subscription type.', 'warning');
    }
}

function schedule_service_notification()
{
    if (! is_main_instance()) {
        return false;
    }
    set_airtime_balances_from_pbx();

    $domains = \DB::connection('pbx')->table('v_domains')->where('unlimited_channels', 0)->where('balance_notification', '!=', 'None')->get();

    $first_week_day = date('Y-m-d', strtotime('monday this week'));
    foreach ($domains as $domain) {

        $data = [];
        if (date('Y-m-d') != date('Y-m-t') && $notification == 'Monthly') {
            continue;
        }
        if (date('Y-m-d') != $first_week_day && $notification == 'Weekly') {
            continue;
        }

        $available = currency($domain->balance);

        $function_variables = [];
        $data['internal_function'] = 'usage_notification';
        $data['available'] = $available;
        $data['used'] = 0;
        $data['allocated'] = 0;
        $data['notification_title'] = 'Airtime Balance';
        $data['detail'] = $domain->domain_name;
        //  if ($domain->account_id == 9751) { //VCA
        //      $data['bcc_email'] = 'ahmed@telecloud.co.za';
        //  }
        //$data['test_debug'] = 1;

        erp_process_notification($domain->account_id, $data, $function_variables);
    }

    $subs = \DB::table('sub_services')->where('provision_type', 'hosting')->where('status', '!=', 'Deleted')->get();

    foreach ($subs as $sub) {
        if ($sub->account_id == 12) {
            continue;
        }
        if (empty($sub->usage_allocation)) {

            continue;
        }
        if (empty($sub->current_usage)) {

            continue;
        }

        $account_id = $sub->account_id;
        $product_name = \DB::table('crm_products')->where('id', $sub->product_id)->pluck('code')->first();
        $notification_title = ucwords(str_replace('_', ' ', $product_name));
        $detail = $sub->detail;
        if ($sub->current_usage < 0) {
            $sub->current_usage = $sub->usage_allocation;
        }
        if ($sub->current_usage > 0) {
            $used = currency($sub->current_usage).' '.$sub->usage_type;
        }
        $allocated = currency($sub->usage_allocation).' '.$sub->usage_type;
        $available = currency($sub->usage_allocation - abs($sub->current_usage)).' '.$sub->usage_type;

        $percentage_used = currency((($sub->usage_allocation - $sub->current_usage) / $sub->usage_allocation) * 100);
        if ($sub->current_usage == $sub->usage_allocation) {
            $percentage_used = 100;
        }

        if ($percentage_used > 90) {

            $data['instructions'] = $sub->detail.' '.$sub->usage_details.' Your hosting storage usage is above 90%, please clear deleted mailbox items to prevent usage errors on your website.';

            $function_variables = [];
            $data['internal_function'] = 'usage_notification';
            $data['available'] = $available;
            $data['used'] = $used;
            $data['allocated'] = $allocated;
            $data['notification_title'] = $notification_title;
            $data['detail'] = $sub->detail;
            //if ($sub->account_id == 9751) { //VCA
            //   $data['bcc_email'] = 'ahmed@telecloud.co.za';
            //}

            erp_process_notification($sub->account_id, $data, $function_variables);
        }
    }
}

function schedule_subscription_totals()
{
    if (session('instance')->directory != 'eldooffice') {
        $sub = new ErpSubs;
        $sub->updateProductPrices();
        $account_ids = \DB::table('crm_accounts')->where('type', '!=', 'lead')->where('status', '!=', 'Deleted')->pluck('id');
        foreach ($account_ids as $id) {
            $sub->updateSubscriptionsTotal($id);
        }
    }
}

function schedule_cancel_subscriptions()
{
    $erp_subscriptions = new ErpSubs;
    $subscription_ids = \DB::table('sub_services')->where('to_cancel', 1)->where('cancel_date', '<=', date('Y-m-d'))->pluck('id')->toArray();
    if (! empty($subscription_ids) && is_array($subscription_ids) && count($subscription_ids) > 0) {
        foreach ($subscription_ids as $subscription_id) {
            $erp_subscriptions->deleteSubscription($subscription_id);
        }
    }
}

/// HELPER FUNCTIONS
function set_hosting_product_packages()
{
    $sites = \DB::table('sub_services')->whereIn('provision_type', ['hosting', 'sitebuilder'])->get();

    foreach ($sites as $site) {
        $package = \DB::table('crm_products')->where('id', $site->product_id)->pluck('provision_package')->first();
        \DB::table('isp_host_websites')->where('domain', $site->detail)->update(['package' => $package]);
    }
}

function button_subscriptions_port_out_number($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();
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

    return json_alert('Done');
}

function validate_ported_out_numbers()
{

    $ported_numbers = \DB::table('sub_activations')->where('product_id', 126)->pluck('detail')->toArray();
    foreach ($ported_numbers as $n) {
        $deleted = \DB::table('sub_services')->where('detail', $n)->where('status', 'Deleted')->count();
        $enabled = \DB::table('sub_services')->where('detail', $n)->where('status', '!=', 'Deleted')->count();
        $pbx_enabled = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $n)->where('status', '!=', 'Deleted')->count();

        if ($deleted && ! $enabled && $pbx_enabled) {
            \DB::connection('pbx')->table('p_phone_numbers')->where('number', $n)->update(['domain_uuid' => null, 'status' => 'Deleted', 'deleted_at' => $deleted_at, 'number_routing' => null, 'routing_type' => null]);
        }
    }
}

function send_extension_details($account_id, $domain_uuid, $extension)
{
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'email_extension_details')->pluck('id')->first();
    $ext = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->where('extension', $extension)->get()->first();

    $data['username'] = $ext->extension;
    $data['password'] = $ext->password;
    $data['details'] = $ext;
    $data['internal_function'] = 'email_extension_details';
    erp_process_notification($account_id, $data);

    return true;
}

function send_activation_email($subscription_id)
{

    $id = $subscription_id;

    $sub = \DB::table('sub_services')->where('id', $subscription_id)->get()->first();

    $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

    if (empty($product)) {
        return json_alert('Invalid product', 'error');
    }
    $provision_plan = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->get()->first();
    $provision_plan_name = $provision_plan->name;
    $provision_plan_id = $provision_plan->id;
    if (empty($provision_plan_name)) {
        return json_alert('Invalid activation plan', 'error');
    }
    $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $provision_plan_id)->where('email_id', '>', '')->orderby('step', 'desc')->pluck('email_id')->first();
    if (empty($email_id)) {
        return json_alert('No setup instructions available for this product', 'error');
    }

    $customer = dbgetaccount($sub->account_id);

    $data['notification_id'] = $email_id;
    $data['detail'] = $sub->detail;
    $data['product'] = ucwords(str_replace('_', ' ', $product->code));
    $data['product_code'] = $product->code;
    $data['product_description'] = $product->name;
    $data['portal_url'] = get_whitelabel_domain($customer->partner_id);

    $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer);
    if (! empty($activation_data) && count($activation_data) > 0) {
        foreach ($activation_data as $k => $v) {
            $data[$k] = $v;
        }
    }

    $data['activation_email'] = true;

    // if (1 != $customer->partner_id) {
    //     $mail_result = email_queue_add($customer->partner_id, $data);
    //  } else {
    //      $mail_result = email_queue_add($customer->id, $data);
    //   }

    // $data['test_debug']=1;
    $mail_result = erp_process_notification($customer->id, $data);

    return $mail_result;
}

function button_subscriptions_edit_extension($request)
{

    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

    $account_id = $sub->account_id;

    $pbx_row = \DB::connection('pbx')->table('v_users as vu')
        ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
        ->where('vd.account_id', $account_id)
        ->get()->first();

    $extension_uuid = \DB::connection('pbx')->table('v_extensions')->where('extension', $sub->detail)->where('domain_uuid', $pbx_row->domain_uuid)->pluck('extension_uuid')->first();
    $menu_item_link = '/app/extensions/extension_edit.php?id='.$extension_uuid;

    if ($pbx_row->api_key && $pbx_row->domain_name) {
        $url = 'http://'.$pbx_row->domain_name.$menu_item_link.'&key='.$pbx_row->api_key;
    }

    return redirect()->to($url);
}

function button_subscriptions_edit_phone_number($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    $account_id = $sub->account_id;

    $pbx_row = \DB::connection('pbx')->table('v_users as vu')
        ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
        ->where('vd.account_id', $account_id)
        ->get()->first();

    $phone_number_id = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->where('domain_uuid', $pbx_row->domain_uuid)->pluck('id')->first();
    $menu_item_link = '/app/phone_numbers/phone_number_edit.php?id='.$phone_number_id;

    if ($pbx_row->api_key && $pbx_row->domain_name) {
        $url = 'http://'.$pbx_row->domain_name.$menu_item_link.'&key='.$pbx_row->api_key;
    }

    return redirect()->to($url);
}

function button_services_open_service_center($request)
{
    try {
        $id = $request->id;

        $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();

        $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();

        if (empty($product)) {
            return json_alert('Invalid product', 'error');
        }
        $provision_plan_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
        if (empty($provision_plan_name)) {
            return json_alert('Invalid activation plan', 'error');
        }

        if ($provision_plan_name == 'pbx_extension' || $provision_plan_name == 'sip_trunk') {
            $account_id = $sub->account_id;

            $pbx_row = \DB::connection('pbx')->table('v_users as vu')
                ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
                ->where('vd.account_id', $account_id)
                ->get()->first();

            $extension_uuid = \DB::connection('pbx')->table('v_extensions')->where('extension', $sub->detail)->where('domain_uuid', $pbx_row->domain_uuid)->pluck('extension_uuid')->first();
            $menu_item_link = '/app/extensions/extension_edit.php?id='.$extension_uuid;

            if ($pbx_row->api_key && $pbx_row->domain_name) {
                $url = 'http://'.$pbx_row->domain_name.$menu_item_link.'&key='.$pbx_row->api_key;
            }

            return redirect()->to($url);
        }

        if ($provision_plan_name == 'phone_number') {
            $account_id = $sub->account_id;

            $pbx_row = \DB::connection('pbx')->table('v_users as vu')
                ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
                ->where('vd.account_id', $account_id)
                ->get()->first();

            $phone_number_id = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $sub->detail)->where('domain_uuid', $pbx_row->domain_uuid)->pluck('id')->first();
            $menu_item_link = '/app/phone_numbers/phone_number_edit.php?id='.$phone_number_id;

            if ($pbx_row->api_key && $pbx_row->domain_name) {
                $url = 'http://'.$pbx_row->domain_name.$menu_item_link.'&key='.$pbx_row->api_key;
            }

            return redirect()->to($url);
        }

        if ($provision_plan_name == 'fibre' || $provision_plan_name == 'fibre_product') {
            $details = \DB::table('isp_data_fibre')->where('account_id', $sub->account_id)->where('subscription_id', $sub->id)->get()->first();
            $route = \DB::table('erp_cruds')->where('db_table', 'isp_data_fibre')->pluck('slug')->first();

            return redirect()->to($route.'?subscription_id='.$sub->id);
        }

        if ($provision_plan_name == 'iptv' || $provision_plan_name == 'iptv_global') {
            $route = \DB::table('erp_cruds')->where('db_table', 'isp_data_iptv')->pluck('slug')->first();

            return redirect()->to($route.'?subscription_id='.$sub->id);
        }

        if ($provision_plan_name == 'lte_sim_card') {
            $route = \DB::table('erp_cruds')->where('db_table', 'isp_data_lte_vodacom_accounts')->pluck('slug')->first();

            return redirect()->to($route.'?subscription_id='.$sub->id);
        }

        if ($sub->provision_type == 'mtn_lte_sim_card') {
            $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

            return redirect()->to($menu_name.'?subscription_id='.$request->id);
        }
        if ($sub->provision_type == 'telkom_lte_sim_card') {
            $menu_name = get_menu_url_from_table('isp_data_lte_axxess_accounts');

            return redirect()->to($menu_name.'?subscription_id='.$request->id);
        }

        if ($provision_plan_name == 'hosting' || $provision_plan_name == 'sitebuilder') {
            $route = \DB::table('erp_cruds')->where('db_table', 'isp_host_websites')->pluck('slug')->first();

            return redirect()->to($route.'?subscription_id='.$sub->id);
        }

        if ($provision_plan_name == 'virtual_server') {
            $route = \DB::table('erp_cruds')->where('db_table', 'isp_virtual_servers')->pluck('slug')->first();

            return redirect()->to($route.'?subscription_id='.$sub->id);
        }

        return json_alert('Invalid provision type', 'warning');
    } catch (\Throwable $ex) {
        exception_log($ex);

        //dev_email('subscription details email error');
        return json_alert('Subscription details error', 'warning');
    }
}
