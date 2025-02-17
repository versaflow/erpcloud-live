<?php

function schedule_expire_trial_subscriptions()
{
    $trial_subs_query = \DB::table('sub_services')->where('status', '!=', 'Deleted');
    $trial_subs_query->where(function ($trial_subs_query) {
        $trial_subs_query->whereNotNull('trial_expiry_date');
        $trial_subs_query->orWhere('trial_expiry_date', '>', '');
    });

    $trial_subs = $trial_subs_query->where('trial_expiry_date', '<', date('Y-m-d'))->get();

    foreach ($trial_subs as $sub) {
        $data = [];
        \DB::table('sub_services')->where('id', $sub->id)->update(['status' => 'Deleted', 'deleted_at' => date('Y-m-d H:i:s')]);
        $account = dbgetaccount($sub->account_id);
        $product = ucwords(str_replace('_', ' ', \DB::table('crm_products')->where('id', $sub->product_id)->pluck('code')->first()));
        $data['internal_function'] = 'subscription_trial_expired';
        $data['account_company'] = $account->company;
        $data['account_phone'] = $account->phone;
        $data['account_email'] = $account->email;
        $data['subscription_detail'] = $sub->detail;
        $data['product_code'] = $product;
        module_log(334, $sub->id, 'updated', 'subscription trial expired');
        erp_process_notification($account->id, $data);
    }
}
