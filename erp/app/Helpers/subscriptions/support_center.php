<?php

function update_support_center()
{

    $account_ids = \DB::table('sub_services')->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
    $accounts = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->get();
    foreach ($account_ids as $account_id) {

        $data = [
            'id' => $account_id,
            'account_id' => $account_id,
            'currency' => $accounts->where('id', $account_id)->pluck('currency')->first(),
        ];
        \DB::table('sub_support_center')->updateOrInsert(['id' => $data['id']], $data);
    }

    \DB::table('sub_support_center')->whereNotIn('id', $account_ids)->delete();

    $data = [
        'pbx_domain' => '',
        'airtime_balance' => '',
        'cost_calculation' => '',
        'pbx_type' => '',
        'sms_balance' => '',
    ];

    \DB::table('sub_support_center')->update($data);
    $v_domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($v_domains as $v) {
        $data = [
            'pbx_domain' => $v->domain_name,
            'airtime_balance' => currency($v->balance),
            'cost_calculation' => $v->cost_calculation,
            'pbx_type' => $v->pbx_type,
        ];

        \DB::table('sub_support_center')->where('account_id', $v->account_id)->update($data);
    }

    $sms_balances = \DB::connection('default')->table('sub_services')
        ->select('account_id', 'current_usage')
        ->where('provision_type', 'bulk_sms_prepaid')
        ->where('status', '!=', 'Deleted')
        ->get();

    foreach ($sms_balances as $sms_balance) {
        \DB::table('sub_support_center')->where('account_id', $sms_balance->account_id)->update(['sms_balance' => $sms_balance->current_usage]);
    }

    $header_lines = \DB::table('sub_support_center')->get();

    foreach ($header_lines as $header_line) {

        $total_subs = \DB::table('sub_services')->where('account_id', $header_line->account_id)->where('status', '!=', 'Deleted')->count();
        $cancelled_subs = \DB::table('sub_services')->where('account_id', $header_line->account_id)->where('to_cancel', 1)->where('status', '!=', 'Deleted')->count();
        \DB::table('sub_support_center')->where('id', $header_line->id)->update(['total_subscriptions' => $total_subs, 'cancelled_subscriptions' => $cancelled_subs]);
    }
}

function onload_update_support_center()
{
    update_support_center();
}
