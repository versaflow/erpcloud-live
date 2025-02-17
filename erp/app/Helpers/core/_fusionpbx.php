<?php

function isValidRegex($pattern)
{
    $valid = 1;

    if (@preg_match("/$pattern/", '') === false) {
        $valid = 0;
    }

    return $valid;
}

function beforesave_dialplan_details_validate_regex($request)
{
    if ($request->dialplan_detail_tag == 'condition') {
        $validRegex = isValidRegex($request->dialplan_detail_data);
        if (! $validRegex) {
            return 'Invalid regular expression';
        }
    }

}

function aftersave_extensions($request)
{

    $pbx = new FusionPBX;
    $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();

    $call_forward = [
        'forward_user_not_registered_enabled' => $ext->forward_no_answer_enabled,
        'forward_user_not_registered_destination' => $ext->forward_all_destination,
        'forward_busy_destination' => $ext->forward_all_destination,
        'forward_no_answer_destination' => $ext->forward_all_destination,
    ];

    \DB::connection('pbx')->table('v_extensions')->where('id', $ext->id)->update($call_forward);
    $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
    $result = $pbx->portalCmd('portal_aftersave_extension', $key);

    if (! empty($ext->cidr)) {
        $pbx->portalCmd('portal_reloadacl');
    }
}

function schedule_channels_monitor()
{
    onload_active_calls_update();
    if (! isTodayWeekend() && is_main_instance()) {
        $now = date('Y-m-d H:i');
        $start = date('Y-m-d 08:30');
        $end = date('Y-m-d 21:30');
        if ($start < $now && $now < $end) {
            $active_channels = \DB::connection('pbx')->table('mon_active_calls')->count();

            if ($active_channels < 5) {
                admin_email('Switch low channels alert.', 'Active channels are less than 5.');
            }
        }
    }
}

function afterdelete_dialplans_delete_details($request)
{
    if (! empty($request->dialplan_uuid)) {
        \DB::connection('pbx')->table('v_dialplan_details')->where('dialplan_uuid', $request->dialplan_uuid)->delete();
    }

    $pbx = new FusionPBX;
    $dialplan_params = '';
    if (! empty($dialplan->dialplan_context) && ! empty($dialplan->dialplan_uuid)) {
        $dialplan_params = $dialplan->dialplan_context.'__'.$dialplan->dialplan_uuid;
    } elseif (! empty($dialplan->dialplan_context)) {
        $dialplan_params = $dialplan->dialplan_context;
    }
    if (! empty($dialplan_params)) {

        $pbx->portalCmd('portal_aftersave_global_dialplan', $dialplan_params);
    }
}

function aftersave_dialplans($request)
{
    $dialplan = \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $request->dialplan_uuid)->get()->first();

    $pbx = new FusionPBX;
    $dialplan_params = '';
    if (! empty($dialplan->dialplan_context) && ! empty($dialplan->dialplan_uuid)) {
        $dialplan_params = $dialplan->dialplan_context.'__'.$dialplan->dialplan_uuid;
    } elseif (! empty($dialplan->dialplan_context)) {
        $dialplan_params = $dialplan->dialplan_context;
    }
    if (! empty($dialplan_params)) {

        $pbx->portalCmd('portal_aftersave_global_dialplan', $dialplan_params);
    }
}

function aftersave_dialplan_details($request)
{
    $dialplan_detail = \DB::connection('pbx')->table('v_dialplan_details')->where('dialplan_detail_uuid', $request->dialplan_detail_uuid)->get()->first();
    $dialplan = \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $dialplan_detail->dialplan_uuid)->get()->first();

    $pbx = new FusionPBX;
    $dialplan_params = '';
    if (! empty($dialplan->dialplan_context) && ! empty($dialplan->dialplan_uuid)) {
        $dialplan_params = $dialplan->dialplan_context.'__'.$dialplan->dialplan_uuid;
    } elseif (! empty($dialplan->dialplan_context)) {
        $dialplan_params = $dialplan->dialplan_context;
    }
    if (! empty($dialplan_params)) {

        $pbx->portalCmd('portal_aftersave_global_dialplan', $dialplan_params);
    }
}

function aftersave_sip_profile($request)
{
    $sip_profile = \DB::connection('pbx')->table('v_sip_profiles')->where('sip_profile_uuid', $request->sip_profile_uuid)->get()->first();
    $pbx = new FusionPBX;
    $pbx->portalCmd('portal_aftersave_sip_profile', $sip_profile->sip_profile_name);
}

function aftersave_extensions_update_subscriptions($request)
{
    if (empty($request->new_record)) {
        $beforesave_row = session('event_db_record');
        if ($beforesave_row->extension != $request->extension) {
            $domain_uuid = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->pluck('domain_uuid')->first();
            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('account_id')->first();
            \DB::connection('default')->table('sub_services')
                ->whereIn('provision_type', ['pbx_extension_recording', 'pbx_extension', 'sip_trunk'])
                ->where('account_id', $account_id)
                ->where('detail', $beforesave_row->extension)
                ->update(['detail' => $request->extension]);
        }
    }
}

function get_dialplan_app_uuids($row)
{

    $app_uuids = [
        '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' => 'outbound',
        'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' => 'inbound',
    ];

    //$app_keys = array_keys

    return $app_uuids;
}

function get_destination_countries()
{
    return \DB::connection('pbx')->table('p_rates_destinations')->groupBy('country')->pluck('country')->toArray();
}

function array_is_assoc(array $arr)
{
    if ($arr === []) {
        return false;
    }

    return array_keys($arr) !== range(0, count($arr) - 1);
}

function button_reload_acl($request)
{
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_reloadacl');

    return json_alert($result);
}

function aftersave_reload_acl($request)
{
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_reloadacl');
}

function musiconhold_select($row)
{
    $options = [];
    $options['{loops=-1}tone_stream://v=-30;%(2000,4000,440.0,480.0)'] = 'Standard Ringtone'; // Ahmed
    $options['local_stream://default'] = 'Default Music'; // Ahmed
    $sql = 'select ';
    $sql .= 'd.domain_name, m.* ';
    $sql .= 'from v_music_on_hold as m ';
    $sql .= 'left join v_domains as d ON d.domain_uuid = m.domain_uuid ';
    $sql .= "where (m.domain_uuid = '".$row['domain_uuid']."' or m.domain_uuid is null) ";
    $sql .= 'order by m.domain_uuid desc, music_on_hold_name asc, music_on_hold_rate asc ';
    $music_list = \DB::connection('pbx')->select($sql);

    if (count($music_list) > 0) {
        $previous_name = '';
        foreach ($music_list as $ml) {
            $ml = (array) $ml;
            if ($previous_name != $ml['music_on_hold_name']) {
                $name = '';
                if (strlen($ml['domain_uuid']) > 0) {
                    $name = $ml['domain_name'].'/';
                }
                $name .= $ml['music_on_hold_name'];
                $options['local_stream://'.$name] = $ml['music_on_hold_name'];
            }
            $previous_name = $ml['music_on_hold_name'];
        }
    }

    $sql = 'select recording_uuid, recording_filename, recording_base64 from v_recordings ';
    $sql .= "where domain_uuid = '".$row['domain_uuid']."' ";
    $recording_list = \DB::connection('pbx')->select($sql);
    foreach ($recording_list as $rl) {
        $rl = (array) $rl;
        $options['/var/lib/freeswitch/recordings/'.$row['accountcode'].'/'.$rl['recording_filename']] = $rl['recording_filename'];
    }

    return $options;
}
