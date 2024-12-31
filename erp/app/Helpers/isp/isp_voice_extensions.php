<?php

function send_unlimited_extension_list()
{
    return false;
    $account_ids = \DB::table('sub_services')->whereIn('product_id', [1393, 1394])->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
    foreach ($account_ids as $account_id) {

        $account = dbgetaccount($account_id);

        if ($account->partner_id == 1) {
            $domain = \DB::connection('pbx')->table('v_domains')->where('account_id', $account_id)->get()->first();
            $unlimited_exts = \DB::table('sub_services')->whereIn('product_id', [1393, 1394])->where('account_id', $domain->account_id)->where('status', '!=', 'Deleted')->pluck('detail')->toArray();
            $ul_exts = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->whereIn('extension', $unlimited_exts)->pluck('extension')->toArray();
            $exts = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->whereNotIn('extension', $unlimited_exts)->pluck('extension')->toArray();
            $unlimited_msg = '';

            $unlimited_msg .= 'Unlimited Extensions: <br>'.implode('<br>', $ul_exts);
            if (count($exts)) {
                $unlimited_msg .= '<br><br>Prepaid Extensions: <br>'.implode('<br>', $exts);
            }

            $data = [];
            $data['unlimited_msg'] = $unlimited_msg;
            $data['internal_function'] = 'unlimited_extension_list';
            //$data['test_debug'] = 1;
            erp_process_notification($account_id, $data);
        }
    }
}

function schedule_ext_set_wholesale_ext()
{
    $volume_domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->pluck('domain_uuid')->toArray();
    \DB::connection('pbx')->table('v_extensions')->whereIn('domain_uuid', $volume_domains)->update(['wholesale_ext' => 1]);
}

function beforesave_pbxextensions_validate_number($request)
{
    if (! empty($request->forward_no_answer_enabled) || ! empty($request->forward_all_enabled) || ! empty($request->forward_busy_enabled)) {
        if (empty($request->forward_all_destination)) {
            return 'Forward number is required when call forwarding is enabled.';
        }
    }

    $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();
    if (empty($request->toll_allow)) {
        request()->request->add(['toll_allow' => null]);
        \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->update(['toll_allow' => null]);
    }

    if (! empty($request->pin_number)) {
        $pin_number = intval(str_replace(' ', '', $request->pin_number));
        if (empty($pin_number)) {
            return 'Invalid pin number. Pin number needs to be numeric.';
        }
    }

    if (! empty($request->outbound_caller_id_number)) {
        $first_four = ['2760', '2761', '2762', '2763', '2770', '2771', '2772', '2773', '2774', '2776', '2777', '2778', '2779', '2781', '2782', '2783', '2784'];

        $outbound_caller_id_number = substr($request->outbound_caller_id_number, 0, 4);
        if (in_array($outbound_caller_id_number, $first_four)) {
            return 'A mobile number cannot be used as a Caller ID in a fixed line network.';
        }
    }
    $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ext->domain_uuid)->pluck('account_id')->first();

    if (! empty($request->forward_all_destination)) {
        $mobile = $request->forward_all_destination;
        try {
            $phone = phone($mobile, ['ZA', 'US', 'Auto']);
            $number = $phone->formatE164();
            $number = ltrim($number, '+');

            if (strlen($number) < 10 or strlen($number) > 11) {
                return 'Invalid forward phone number';
            }
            $linked = \DB::connection('pbx')->table('p_phone_numbers')->where('number', $number)->get();
            if (count($linked) > 0) {
                //    return 'Cannot forward to internal number.';
            }
            $number = $phone->formatForMobileDialingInCountry('ZA');
            request()->request->add(['forward_all_destination' => $number]);
        } catch (\Throwable $ex) {
            exception_log($ex);

            return 'Invalid forward phone number';
            // request()->request->add(['forward_all_destination'=>'']);
        }
    }

    if (! empty($request->mobile_app_number)) {
        $mobile = $request->mobile_app_number;

        try {
            $number = phone($mobile, ['ZA', 'US', 'Auto']);
            if ($number->isOfType('fixed_line')) {
                return 'You can not use a fixed line number for mobile app registrations.';
            }
            $number = $number->formatForMobileDialingInCountry('ZA');
            if (strlen($number) != 10) {
                return 'Invalid Unlimited Mobile Number Link number';
            }

            if (! empty($request->id)) {
                $exists = \DB::connection('pbx')->table('v_extensions')->where('id', '!=', $request->id)->where('mobile_app_number', $number)->count();
            } else {
                $exists = \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $number)->count();
            }

            if ($exists) {
                return 'Unlimited Mobile Number Link already in use.';
            }

            request()->request->add(['mobile_app_number' => $number]);
        } catch (\Throwable $ex) {
            exception_log($ex);

            return 'Invalid Unlimited Mobile Number Link number';
            //  request()->request->add(['mobile_app_number'=>'']);
        }
    }
}

function schedule_import_registration_failures()
{
    $volume_domain_names = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->pluck('domain_name')->toArray();
    \DB::connection('pbx')->table('mon_registration_failures')->where('domain_name', '')->delete();
    \DB::connection('pbx')->table('mon_registration_failures')->where('domain_name', '156.0.96.62')->delete();
    if (count($volume_domain_names) > 0) {
        \DB::connection('pbx')->table('mon_registration_failures')->whereIn('domain_name', $volume_domain_names)->delete();
    }
    $cmd = "tail -10000 /var/log/freeswitch/freeswitch.log | grep 'SIP auth failure (REGISTER)'";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    $lines = explode(PHP_EOL, $result);
    $lines = collect($lines)->filter()->toArray();

    foreach ($lines as $line) {
        try {
            if (! str_contains($line, 'SIP auth failure (REGISTER)') || str_contains($line, 'DIALSTRING')) {
                continue;
            }
            $line_arr = explode(' from ip ', $line);
            $ip_address = trim($line_arr[1]);

            $domain_arr = explode("'reg' for [", $line_arr[0]);
            $domain_arr = explode('@', str_replace(']', '', $domain_arr[1]));

            $extension = $domain_arr[0];
            $domain_name = $domain_arr[1];
            if (in_array($domain_name, $volume_domain_names)) {
                continue;
            }
            $date_arr = explode(' ', $line_arr[0]);
            $date = $date_arr[0];
            $time_arr = explode('.', $date_arr[1]);
            $created_at = date('Y-m-d H:i:s', strtotime($date.$time_arr[0]));

            if ($created_at > date('Y-m-d H:i:s', strtotime('-2 hours'))) {
                $domain_uuid = \DB::connection('pbx')->table('v_domains')->select('domain_uuid')->where('domain_name', $domain_name)->pluck('domain_uuid')->first();
                $account_id = \DB::connection('pbx')->table('v_domains')->select('account_id')->where('domain_name', $domain_name)->pluck('account_id')->first();
                $extension_uuid = \DB::connection('pbx')->table('v_extensions')->select('extension_uuid')->where('extension', $extension)->where('domain_uuid', $domain_uuid)->pluck('extension_uuid')->first();

                if ($account_id) {
                    $data = [
                        'created_at' => $created_at,
                        'extension' => $extension,
                        'domain_name' => $domain_name,
                        'account_id' => $account_id,
                        'extension_uuid' => $extension_uuid,
                        'domain_uuid' => $domain_uuid,
                        'ip_address' => $ip_address,
                    ];

                    $id = \DB::connection('pbx')->table('mon_registration_failures')
                        ->where('created_at', 'like', date('Y-m-d', strtotime($created_at)).'%')
                        ->where('extension', $extension)
                        ->where('domain_name', $domain_name)
                        ->pluck('id')->first();
                    if ($id) {
                        \DB::connection('pbx')->table('mon_registration_failures')->where('id', $id)->update($data);
                    } else {
                        \DB::connection('pbx')->table('mon_registration_failures')->insert($data);
                    }
                }
            }
        } catch (\Throwable $ex) {
        }
    }

    // keep only last 10
    \DB::connection('pbx')->table('mon_registration_failures')->where('domain_name', 'um29.cloudtools.co.za')->delete();
    \DB::connection('pbx')->table('mon_registration_failures')->where('domain_name', '')->delete();
    \DB::connection('pbx')->table('mon_registration_failures')->whereNull('domain_name')->delete();
    $domains = \DB::connection('pbx')->table('mon_registration_failures')->pluck('domain_name')->toArray();
    foreach ($domains as $domain) {
        $reg_failure_ids = \DB::connection('pbx')->table('mon_registration_failures')->where('domain_name', $domain)->orderBy('created_at', 'desc')->limit(10)->pluck('id')->toArray();
        \DB::connection('pbx')->table('mon_registration_failures')->where('domain_name', $domain)->whereNotIn('id', $reg_failure_ids)->delete();
    }

    $domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($domains as $domain) {
        \DB::connection('pbx')->table('mon_registration_failures')->where('domain_name', $domain->domain_name)->update(['account_id' => $domain->account_id, 'domain_status' => $domain->status]);
    }

}

function button_extensions_voicemail($request)
{
    $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();
    $id = \DB::connection('pbx')->table('v_voicemails')->where('voicemail_id', $ext->extension)->where('domain_uuid', $ext->domain_uuid)->pluck('id')->first();
    $voicemail_url = get_menu_url_from_table('v_voicemails');

    return Redirect::to($voicemail_url.'/edit/'.$id);
}

function aftersave_pbxextensions_set_pin_dialing($request)
{
    /*
    $ext = DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();
    $acc_arr = explode('.', $ext->accountcode);
    $dialplan_name = strtolower($acc_arr[0].'_'.$ext->extension.'_pin');
    $pin_number = intval(str_replace(' ', '', $ext->pin_number));

    if (empty($pin_number)) {
        // delete the dialplan
        $dialplan_uuid = DB::connection('pbx')->table('v_dialplans')->where('dialplan_name', $dialplan_name)->pluck('dialplan_uuid')->first();
        if ($dialplan_uuid) {
            DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $dialplan_uuid)->delete();
            DB::connection('pbx')->table('v_dialplan_details')->where('dialplan_uuid', $dialplan_uuid)->delete();
        }
    }

    if (!empty($pin_number)) {
        // create or update dialplan
        $dialplan_exists = DB::connection('pbx')->table('v_dialplans')->where('dialplan_name', $dialplan_name)->count();

        if ($dialplan_exists) {
            $dialplan_uuid = DB::connection('pbx')->table('v_dialplans')->where('dialplan_name', $dialplan_name)->pluck('dialplan_uuid')->first();
            $dialplan_details = ['dialplan_detail_data' => 'pin_number='.$pin_number];
            DB::connection('pbx')->table('v_dialplan_details')->where('dialplan_detail_data', 'LIKE', 'pin_number=%')->update($dialplan_details);
        } else {
            $dialplan_uuid = pbx_uuid('v_dialplans', 'dialplan_uuid');
            $dialplan = [
                'domain_uuid' => $ext->domain_uuid,
                'dialplan_uuid' => $dialplan_uuid,
                'app_uuid' => '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3',
                'dialplan_context' => $ext->accountcode,
                'dialplan_name' => $dialplan_name,
                'dialplan_continue' => 'false',
                'dialplan_order' => 80,
                'dialplan_enabled' => 'true',
            ];

            DB::connection('pbx')->table('v_dialplans')->insert($dialplan);

            $dialplan_detail_uuid = pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid');
            $dialplan_details = [
                'domain_uuid'  => $ext->domain_uuid,
                'dialplan_uuid' => $dialplan_uuid,
                'dialplan_detail_uuid' => $dialplan_detail_uuid,
                'dialplan_detail_tag' => 'condition',
                'dialplan_detail_type' => 'destination_number',
                'dialplan_detail_data' => '^.{7,22}$',
                'dialplan_detail_group' => 0,
                'dialplan_detail_order' => 5,
            ];
            DB::connection('pbx')->table('v_dialplan_details')->insert($dialplan_details);


            $dialplan_detail_uuid = pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid');
            $dialplan_details = [
                'domain_uuid'  => $ext->domain_uuid,
                'dialplan_uuid' => $dialplan_uuid,
                'dialplan_detail_uuid' => $dialplan_detail_uuid,
                'dialplan_detail_tag' => 'action',
                'dialplan_detail_type' => 'lua',
                'dialplan_detail_data' => 'pin_number.lua',
                'dialplan_detail_group' => 0,
                'dialplan_detail_order' => 15,
            ];
            DB::connection('pbx')->table('v_dialplan_details')->insert($dialplan_details);


            $dialplan_detail_uuid = pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid');
            $dialplan_details = [
                'domain_uuid'  => $ext->domain_uuid,
                'dialplan_uuid' => $dialplan_uuid,
                'dialplan_detail_uuid' => $dialplan_detail_uuid,
                'dialplan_detail_tag' => 'action',
                'dialplan_detail_type' => 'set',
                'dialplan_detail_data' => 'pin_number='.$pin_number,
                'dialplan_detail_group' => 0,
                'dialplan_detail_order' => 10,
            ];
            DB::connection('pbx')->table('v_dialplan_details')->insert($dialplan_details);


            $dialplan_detail_uuid = pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid');
            $dialplan_details = [
                'domain_uuid'  => $ext->domain_uuid,
                'dialplan_uuid' => $dialplan_uuid,
                'dialplan_detail_uuid' => $dialplan_detail_uuid,
                'dialplan_detail_tag' => 'action',
                'dialplan_detail_type' => 'lua',
                'dialplan_detail_data' => '/var/www/html/lua/cloud_out.lua',
                'dialplan_detail_group' => 0,
                'dialplan_detail_order' => 20,
            ];
            DB::connection('pbx')->table('v_dialplan_details')->insert($dialplan_details);


            $dialplan_detail_uuid = pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid');
            $dialplan_details = [
                'domain_uuid'  => $ext->domain_uuid,
                'dialplan_uuid' => $dialplan_uuid,
                'dialplan_detail_uuid' => $dialplan_detail_uuid,
                'dialplan_detail_tag' => 'condition',
                'dialplan_detail_type' => 'username',
                'dialplan_detail_data' => $ext->extension,
                'dialplan_detail_group' => 0,
                'dialplan_detail_order' => 7,
            ];
            DB::connection('pbx')->table('v_dialplan_details')->insert($dialplan_details);
        }
    }

    $extensions = \DB::connection('pbx')->table('v_extensions')->get();
    foreach ($extensions as $e) {
        foreach ($e as $key => $val) {
            if ($val === '') {
                \DB::connection('pbx')->table('v_extensions')->where($key, '')->update([$key => null]);
            }
        }
    }

    \DB::connection('pbx')->table('v_extensions')->update(['dial_string' => null]);
    \DB::connection('pbx')->table('v_extensions')->update(['description' => null]);
    \DB::connection('pbx')->table('v_extensions')->update(['directory_visible' => 'true']);
    \DB::connection('pbx')->table('v_extensions')->update(['directory_exten_visible' => 'true']);
    \DB::connection('pbx')->table('v_extensions')->update(['limit_max' => 10]);
    \DB::connection('pbx')->table('v_extensions')->update(['limit_destination' => 'error/user_busy']);
    \DB::connection('pbx')->table('v_extensions')->where('hold_music', '')->update(['hold_music' => null]);
    \DB::connection('pbx')->table('v_extensions')->whereNull('call_screen_enabled')->update(['call_screen_enabled' => 'false']);
    \DB::connection('pbx')->table('v_extensions')->whereNull('do_not_disturb')->update(['do_not_disturb' => 'false']);
    \DB::connection('pbx')->table('v_extensions')->whereNull('forward_all_enabled')->update(['forward_all_enabled' => 'false']);
    \DB::connection('pbx')->table('v_extensions')->whereNull('forward_busy_enabled')->update(['forward_busy_enabled' => 'false']);
    \DB::connection('pbx')->table('v_extensions')->whereNull('forward_no_answer_enabled')->update(['forward_no_answer_enabled' => 'false']);
    \DB::connection('pbx')->table('v_extensions')->whereNull('forward_user_not_registered_enabled')->update(['forward_user_not_registered_enabled' => 'false']);
    */
}

function button_extensions_show_blocked_ips($request)
{
    $cmd = 'cat /var/log/freeswitch/freeswitch.log | grep failure ';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $result_arr = explode(PHP_EOL, $result);
    echo '<div class="card card-body">';
    foreach ($result_arr as $r) {
        echo '<code>'.$r.'</code>';
    }
    echo '</div>';
}

function button_extensions_view_cdr($request)
{
    $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();

    $menu_name = get_menu_url_from_table('call_records_outbound');

    return Redirect::to($menu_name.'?extension='.$ext->extension);
}
function button_extensions_email_extension_details($request)
{
    $id = $request->id;
    $extension = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'email_extension_details')->pluck('id')->first();

    $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('account_id')->first();
    $customer = dbgetaccount($account_id);
    $data['username'] = $extension->extension;
    $data['password'] = $extension->password;
    $data['details'] = $extension;

    return email_form($email_id, $account_id, $data);
}

function check_mobile_app_number_extension($mobile_app_number)
{
    try {
        $number = phone($mobile_app_number, ['ZA', 'US', 'Auto']);
        if ($number->isOfType('fixed_line')) {
            return 'Invalid mobile app number';
        }
        $number = $number->formatForMobileDialingInCountry('ZA');

        $exists = \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $number)->count();
        if ($exists) {
            return 'Mobile app number already in use';
        }
    } catch (\Throwable $ex) {
        exception_log($ex);

        return 'Invalid mobile app number';
    }
}

function set_mobile_app_number_extension($account_id, $mobile_app_number, $extension)
{
    $account = dbgetaccount($account_id);
    try {
        $number = phone($mobile_app_number, ['ZA', 'US', 'Auto']);
        if ($number->isOfType('fixed_line')) {
            return false;
        }
        $number = $number->formatForMobileDialingInCountry('ZA');

        $exists = \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $number)->count();
        if (! $exists) {
            $manager_extension = 0;
            $manager_extension_set = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $account->domain_uuid)->where('manager_extension', 1)->count();
            if (! $manager_extension_set) {
                $manager_extension = 1;
            }
            \DB::connection('pbx')->table('v_extensions')
                ->where('domain_uuid', $account->domain_uuid)->where('extension', $extension)
                ->update(['mobile_app_number' => $number, 'manager_extension' => $manager_extension]);
        }
    } catch (\Throwable $ex) {
        exception_log($ex);

        return false;
    }

    return true;
}

function delete_unlinked_voicemails($server)
{
    $domain_uuids = \DB::connection($server)->table('v_domains')->pluck('domain_uuid')->toArray();
    \DB::connection($server)->table('v_voicemails')->whereNotIn('domain_uuid', $domain_uuids)->delete();
    \DB::connection($server)->table('v_voicemail_messages')->whereNotIn('domain_uuid', $domain_uuids)->delete();
    foreach ($domain_uuids as $domain_uuid) {
        $extensions = \DB::connection($server)->table('v_extensions')->where('domain_uuid', $domain_uuid)->pluck('extension')->toArray();
        \DB::connection($server)->table('v_voicemails')->where('domain_uuid', $domain_uuid)->whereNotIn('voicemail_id', $extensions)->delete();
        $voicemail_uuids = \DB::connection($server)->table('v_voicemails')->where('domain_uuid', $domain_uuid)->pluck('voicemail_uuid')->toArray();
        \DB::connection($server)->table('v_voicemail_messages')->where('domain_uuid', $domain_uuid)->whereNotIn('voicemail_uuid', $voicemail_uuids)->delete();
    }

    $extensions = \DB::connection($server)->table('v_extensions')->get();
    foreach ($extensions as $e) {
        $voicemail_count = \DB::connection($server)->table('v_voicemails')->where('voicemail_id', $e->extension)->where('domain_uuid', $e->domain_uuid)->count();
        if ($voicemail_count > 1) {
            $voicemail_uuid = \DB::connection($server)->table('v_voicemails')->where('voicemail_id', $e->extension)->where('domain_uuid', $e->domain_uuid)->pluck('voicemail_uuid')->first();
            $delete_voicemail_uuids = \DB::connection($server)->table('v_voicemails')->where('voicemail_id', $e->extension)->where('domain_uuid', $e->domain_uuid)->where('voicemail_uuid', '!=', $voicemail_uuid)->pluck('voicemail_uuid')->toArray();
            \DB::connection($server)->table('v_voicemails')->whereIn('voicemail_uuid', $delete_voicemail_uuids)->delete();
            \DB::connection($server)->table('v_voicemail_messages')->whereIn('voicemail_uuid', $delete_voicemail_uuids)->delete();
        }
    }
}

function global_dialplan_remove_domain_dialplans()
{
    //  $app_uuids = ['4b821450-926b-175a-af93-a03c441818b1','1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2','a5788e9b-58bc-bd1b-df59-fff5d51253ab'];
    //  $dialplan_uuids = \DB::connection('pbx')->table('v_dialplans')->whereNotNull('domain_uuid')->whereNotIn('app_uuid', $app_uuids)->pluck('dialplan_uuid')->toArray();
    //  \DB::connection('pbx')->table('v_dialplan_details')->whereIn('dialplan_uuid', $dialplan_uuids)->delete();
    //  \DB::connection('pbx')->table('v_dialplans')->whereNotNull('domain_uuid')->whereNotIn('app_uuid', $app_uuids)->delete();
}

function schedule_update_extension_count()
{
    $domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($domains as $domain) {
        $extension_count = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->count();
        \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain->domain_uuid)->update(['extensions' => $extension_count]);
    }
}

/*
function aftersave_set_recording_subscription($request)
{
    $extension = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();

    if (!empty($extension->user_record)) {
        $account_id =  \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('account_id')->first();
        $exists = \DB::table('sub_services')
            ->where('account_id', $account_id)
            ->where('provision_type', 'pbx_extension_recording')
            ->where('status', '!=', 'Deleted')
            ->where('detail', $extension->extension)
            ->count();
        if (!$exists) {
            $product_id = 131;
            $sub = new ErpSubs;
            $sub->createSubscription($account_id, $product_id, $extension->extension);
        } else {
            $subscription = \DB::table('sub_services')
                ->where('account_id', $account_id)
                ->where('provision_type', 'pbx_extension_recording')
                ->where('status', '!=', 'Deleted')
                ->where('detail', $extension->extension)
                ->get()->first();
            if (!empty($subscription->to_cancel)) {
                $sub = new ErpSubs;
                $sub->undoCancel($subscription->id);
            }
        }
    } else {
        $account_id =  \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('account_id')->first();
        $exists = \DB::table('sub_services')
            ->where('account_id', $account_id)
            ->where('provision_type', 'pbx_extension_recording')
            ->where('status', '!=', 'Deleted')
            ->where('detail', $extension->extension)
            ->count();
        if ($exists) {
            $subscription_id = \DB::table('sub_services')
                ->where('account_id', $account_id)
                ->where('provision_type', 'pbx_extension_recording')
                ->where('status', '!=', 'Deleted')
                ->where('detail', $extension->extension)
                ->pluck('id')->first();
            $sub = new ErpSubs;
            $sub->cancel($subscription_id);
        }
    }
}
*/
function set_recording_subscription($extension)
{
    if (! empty($extension->user_record)) {
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('account_id')->first();
        $exists = \DB::table('sub_services')
            ->where('account_id', $account_id)
            ->where('provision_type', 'pbx_extension_recording')
            ->where('status', '!=', 'Deleted')
            ->where('detail', $extension->extension)
            ->count();
        if (! $exists) {
            $product_id = 996;
            $sub = new ErpSubs;
            $sub->createSubscription($account_id, $product_id, $extension->extension);
        } else {
            $subscription = \DB::table('sub_services')
                ->where('account_id', $account_id)
                ->where('provision_type', 'pbx_extension_recording')
                ->where('status', '!=', 'Deleted')
                ->where('detail', $extension->extension)
                ->get()->first();
            if (! empty($subscription->to_cancel)) {
                $sub = new ErpSubs;
                $sub->undoCancel($subscription->id);
            }
        }
    } else {
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('account_id')->first();
        $exists = \DB::table('sub_services')
            ->where('account_id', $account_id)
            ->where('provision_type', 'pbx_extension_recording')
            ->where('status', '!=', 'Deleted')
            ->where('detail', $extension->extension)
            ->count();
        if ($exists) {
            $subscription_id = \DB::table('sub_services')
                ->where('account_id', $account_id)
                ->where('provision_type', 'pbx_extension_recording')
                ->where('status', '!=', 'Deleted')
                ->where('detail', $extension->extension)
                ->pluck('id')->first();
            $sub = new ErpSubs;
            $sub->cancel($subscription_id);
        }
    }
}

function button_extension_unblock_ip($request)
{

    $extension = \DB::connection('pbx')->table('v_extensions')->where('extension_uuid', $request->id)->get()->first();

    $ip_address = $extension->network_ip;

    if (empty($ip_address)) {
        return json_alert('IP Address required.', 'warning');
    }
    $pbx = new \FusionPBX;

    $blocked = $pbx->checkBlockedIP($ip_address);

    if (! $blocked) {
        return json_alert('Your IP is not being blocked.');
    }

    $result = $pbx->unblockIP($ip_address);

    $blocked = $pbx->checkBlockedIP($ip_address);
    $account = dbgetaccount($request->account_id);
    $domain = $account->pabx_domain;

    if ($account->partner_id != 1) {
        $account = dbgetaccount($account->partner_id);
    }
    /*
    $data['internal_function'] = 'pbx_ip_unblock';
    if($blocked){
        $data['unblock_msg'] = 'IP address '.$ip_address.' for '.$domain. ' could not be unblocked.<br> Please update your extension passwords and try again.';
    }else{
        $data['unblock_msg'] = 'IP address '.$ip_address.' for '.$domain. ' is unblocked.<br> Please update your extension passwords to prevent it from being blocked again.';
    }

    if($account->partner_id != 1){
        erp_process_notification($account->partner_id,$data);
    }else{
        erp_process_notification($account->id,$data);
    }
    */
    if ($blocked) {
        return json_alert('Your IP is being blocked. Please confirm your extension details.', 'warning', ['blocked' => $blocked]);
    } else {
        return json_alert('Your IP is not being blocked.');
    }
}
