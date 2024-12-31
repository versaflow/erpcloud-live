<?php

function button_iprange_listed_email($request)
{
    $ip_range = \DB::table('isp_data_ip_ranges')->where('id', $request->id)->get()->first();
    if (! empty($ip_range)) {
        $email_id = \DB::table('crm_email_manager')->where('internal_function', 'ip_range_listed')->pluck('id')->first();

        $data['ip_range'] = $ip_range->ip_range;

        return email_form($email_id, $ip_range->account_id, $data);
    } else {
    }
}
