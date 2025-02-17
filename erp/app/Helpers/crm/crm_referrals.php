<?php

function create_referral($account_id, $referral_account_id)
{
    $data = [
        'created_at' => date('Y-m-d H:i:s'),
        'completed_at' => date('Y-m-d H:i:s'),
        'status' => 'Pending',
        'account_id' => $account_id,
        'referral_account_id' => $referral_account_id,
        'required_amount' => get_admin_setting('referral_required_amount'),
        'payout_amount' =>  get_admin_setting('referral_payout_amount'),
        'cash_id' => 0,
    ];
    return \DB::table('crm_referrals')->insertGetId($data);
}

function schedule_process_referrals()
{
    $referrals = \DB::table('crm_referrals')->where('status', 'Pending')->get();
    foreach ($referrals as $referral) {
        $account_deleted = \DB::table('crm_accounts')->where('id', $referral->account_id)->where('status', 'Deleted')->count();
        $referral_account_deleted = \DB::table('crm_accounts')->where('id', $referral->referral_account_id)->where('status', 'Deleted')->count();
        if ($account_deleted || $referral_account_deleted) {
            \DB::table('crm_referrals')->where('id', $referral->id)->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s')]);
            continue;
        }

        $tax_invoice_sum = \DB::table('crm_documents')->where('account_id', $referral->account_id)->where('doctype', 'Tax Invoice')->sum('total');
        if ($tax_invoice_sum > $referral->required_amount) {
            $company = \DB::table('crm_accounts')->where('id', $referral->account_id)->pluck('company')->first();
            $cash_id = create_cash_transaction($referral->referral_account_id, $referral->payout_amount, 'Referral Reward - '.$company);
            \DB::table('crm_referrals')->where('id', $referral->id)->update(['status'=>'Completed','cash_id'=>$cash_id,'completed_at'=>date('Y-m-d H:i:s')]);
        }
    }
}

function schedule_set_referral_links()
{
    \DB::table('crm_accounts')->update(['referral_link'=>'']);
    $required_amount = get_admin_setting('referral_required_amount');
    $payout_amount =  get_admin_setting('referral_payout_amount');
    if ($required_amount > 0 && $payout_amount > 0) {
        $account_ids = \DB::table('crm_accounts')->where('type', '!=', 'lead')->where('status', '!=', 'Deleted')->where('partner_id', 1)->pluck('id')->toArray();
        foreach ($account_ids as $account_id) {
            $refferal_link = generate_refferal_link($account_id);
            \DB::table('crm_accounts')->where('id', $account_id)->update(['referral_link'=>$refferal_link]);
        }
    }
}

function schedule_send_referral_link_emails()
{
    return false;

    $required_amount = get_admin_setting('referral_required_amount');
    $payout_amount =  get_admin_setting('referral_payout_amount');

    if ($required_amount > 0 && $payout_amount > 0) {
        $accounts = \DB::table('crm_accounts')->where('referral_link', '>', '')->get();
        foreach ($accounts as $account) {
            $data = [
               'refferal_payout' => $payout_amount,
               'refferal_amount' => $required_amount,
               'referral_link' => $account->referral_link,
               'function_name'=>'schedule_send_referral_link_emails'
            ];

            //erp_process_notification($account->id,$data);
        }
    }
}
