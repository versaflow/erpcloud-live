<?php

function button_mailqueue_truncate($request)
{
    \DB::table('erp_mail_queue')->truncate();
    return json_alert('Done');
}






function button_whatsapp_reset_from_accounts($request)
{
    \DB::table('crm_whatsapp_list')->truncate();

    $accounts = \DB::table('crm_accounts')->where('partner_id', 1)->where('status', '!=', 'Deleted')->get();
    $numbers = [];
    foreach ($accounts as $account) {
        $num_1 = valid_za_mobile_number($account->phone);
        if ($num_1) {
            $numbers[] = $num_1;
        }
        $num_2 = valid_za_mobile_number($account->contact_phone_2);
        if ($num_2) {
            $numbers[] = $num_2;
        }
        $num_3 = valid_za_mobile_number($account->contact_phone_3);
        if ($num_3) {
            $numbers[] = $num_3;
        }
    }

    foreach ($numbers as $n) {
        $data = [
            'mobile_number' => $n,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'Enabled'
        ];
        \DB::table('crm_whatsapp_list')->insert($data);
    }

    return json_alert('Done');
}

function valid_za_mobile_number($mobile_number = false)
{
    if (empty($mobile_number)) {
        return false;
    }
    try {
        $number = phone($mobile_number, ['ZA','US','Auto']);
        if ($number->isOfType('fixed_line')) {
            return false;
        }
        $number = $number->formatForMobileDialingInCountry('ZA');
        if (strlen($number) != 10) {
            return false;
        }
        return $number;
    } catch (\Throwable $ex) {  exception_log($ex);
        return false;
    }
}
