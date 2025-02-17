<?php

function onload_set_vodacom_lte_status()
{
    $active_ids = \DB::table('sub_services')->where('provision_type', 'lte_sim_card')->where('status', '!=', 'Deleted')->pluck('detail')->toArray();
    \DB::table('isp_data_lte_vodacom_accounts')->whereNotIn('msisdn', $active_ids)->update(['status' => 'Deleted']);
    \DB::table('isp_data_lte_vodacom_accounts')->whereIn('msisdn', $active_ids)->update(['status' => 'Enabled']);
}
function onload_set_telkom_lte_status()
{
    \DB::table('isp_data_lte_axxess_accounts')->update(['status'=>'Deleted']);
    $telkom_ltes = \DB::table('sub_services')->whereIn('provision_type', ['telkom_lte_sim_card','mtn_lte_sim_card'])->get();
    foreach ($telkom_ltes as $telkom_lte) {
        \DB::table('isp_data_lte_axxess_accounts')->where('reference', $telkom_lte->detail)->update(['status' =>$telkom_lte->status,'subscription_id' => $telkom_lte->id]);
        \DB::table('isp_data_lte_axxess_accounts')->where('sim_serialNumber', $telkom_lte->detail)->update(['status' =>$telkom_lte->status,'subscription_id' => $telkom_lte->id]);
    }
}

function button_fibre_update_axxess($request)
{
    $axxess = new Axxess();
    $axxess->import();
    return json_alert('Done');
}

function button_lte_sim_swop($request)
{
    $lte = \DB::table('isp_data_lte_vodacom_accounts')->where('id', $request->id)->get()->first();
    $data['lte'] = $lte;
    $data['id'] = $request->id;
    return view('__app.button_views.lte_simswop', $data);
}

function button_view_access_product($request)
{
    $fibre_account = \DB::table('isp_data_fibre')->where('subscription_id', $request->id)->get()->first();
    $axxess_product_id = \DB::table('isp_data_products')->where('guidProductId', $fibre_account->guidProductId)->pluck('id')->first();
    $menu_name = get_menu_url_from_table('isp_data_products');
    return redirect()->to(url($menu_name.'?id='.$axxess_product_id));
}

function fibre_status_email($subscription, $status)
{
    $account = dbgetaccount($subscription->account_id);
    $data = [];
    $data['account_company'] = $account->company;
    $data['detail'] = $subscription->detail;
    $data['status'] = $status;
    $data['internal_function'] = 'fibre_status_email';
    //$data['test_debug'] = 1;
    erp_process_notification(1, $data);
}
