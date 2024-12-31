<?php

function aftersave_time_condition_build_dialplan($request)
{
    if (empty($request->dialplan_uuid)) {
        return 'Dialplan uuid not set';
    }

    $presets = DB::connection('pbx')->table('p_time_condition_presets')->get();
    $dialplan = \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $request->dialplan_uuid)->get()->first();

    $dialplan_details = \DB::connection('pbx')->table('p_time_condition_details')->where('dialplan_uuid', $request->dialplan_uuid)->orderBy('group_priority')->get();
    DB::connection('pbx')->table('p_time_condition_details')->where('time_condition_preset_id', 11)->update(['group_priority' => 999]);
    // set group priority
    $group_number = 500;
    foreach ($dialplan_details as $d) {
        if ($d->group_priority != 999) {
            \DB::connection('pbx')->table('p_time_condition_details')->where('id', $d->id)->update(['group_priority' => $group_number]);
            $group_number += 10;
        }
    }

    // set domain_uuid
    \DB::connection('pbx')->table('p_time_condition_details')->where('dialplan_uuid', $request->dialplan_uuid)->update(['domain_uuid' => $dialplan->domain_uuid]);

    // get the dialplan details
    $dialplan_details = \DB::connection('pbx')->table('p_time_condition_details')->where('dialplan_uuid', $request->dialplan_uuid)->orderBy('group_priority')->get();

    \DB::connection('pbx')->table('v_dialplan_details')->where('dialplan_uuid', $request->dialplan_uuid)->delete();

    foreach ($dialplan_details as $dialplan_detail) {
        $order = 10;

        // FIRST CONDITION TIME CONDITION EXTENSION NUMBER
        $data = [
            'domain_uuid' => $dialplan->domain_uuid,
            'dialplan_uuid' => $dialplan->dialplan_uuid,
            'dialplan_detail_uuid' => pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid'),
            'dialplan_detail_tag' => 'condition',
            'dialplan_detail_type' => 'destination_number',
            'dialplan_detail_data' => '^'.$dialplan->dialplan_number.'$',
            'dialplan_detail_order' => $order,
            'dialplan_detail_group' => $dialplan_detail->group_priority,
        ];

        \DB::connection('pbx')->table('v_dialplan_details')->insert($data);
        $order += 10;

        // PRESET CONDITIONS
        if ($dialplan_detail->time_condition_preset_id != 11) {
            $preset_values = $presets->where('id', $dialplan_detail->time_condition_preset_id)->first();
            for ($i = 1; $i < 3; $i++) {
                if (! empty($preset_values->{'type_'.$i}) && ! empty($preset_values->{'value_'.$i})) {

                    // TRANSFER ACTION
                    $data = [
                        'domain_uuid' => $dialplan->domain_uuid,
                        'dialplan_uuid' => $dialplan->dialplan_uuid,
                        'dialplan_detail_uuid' => pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid'),
                        'dialplan_detail_tag' => 'condition',
                        'dialplan_detail_type' => $preset_values->{'type_'.$i},
                        'dialplan_detail_data' => $preset_values->{'value_'.$i},
                        'dialplan_detail_break' => 'never',
                        'dialplan_detail_group' => $dialplan_detail->group_priority,
                        'dialplan_detail_order' => $order,
                    ];

                    \DB::connection('pbx')->table('v_dialplan_details')->insert($data);
                    $order += 10;
                }
            }
        }

        // TRANSFER ACTION
        $data = [
            'domain_uuid' => $dialplan->domain_uuid,
            'dialplan_uuid' => $dialplan->dialplan_uuid,
            'dialplan_detail_uuid' => pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid'),
            'dialplan_detail_tag' => 'action',
            'dialplan_detail_type' => 'transfer',
            'dialplan_detail_data' => $dialplan_detail->action,
            'dialplan_detail_group' => $dialplan_detail->group_priority,
            'dialplan_detail_order' => $order,
        ];

        \DB::connection('pbx')->table('v_dialplan_details')->insert($data);

        $group_number += 10;
    }

    // FUSION PBX AFTERSAVE TO BUILD XML AND DELETE CACHE

    if (! empty($dialplan->domain_uuid)) {
        $url = 'http://156.0.96.60/app/time_conditions/time_condition_edit.php';
        $client = new GuzzleHttp\Client;
        $params['headers'] = ['erp_aftersave' => 1];

        $pbx_row = \DB::connection('pbx')->table('v_users as vu')
            ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
            ->where('vd.domain_uuid', $dialplan->domain_uuid)
            ->get()->first();
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $dialplan->domain_uuid)->pluck('domain_name')->first();
        $url = str_replace('156.0.96.60', $domain_name, $url);
        $key = $pbx_row->api_key;

        $params['query'] = ['domain_name' => $domain_name, 'dialplan_uuid' => $dialplan->dialplan_uuid, 'key' => $key, 'api_key' => $key];

        $params['form_params'] = $post_data;

        $response = $client->post($url, $params);

        $result = $response->getBody()->getContents();

        $result = json_decode($result, true);
        if (! $result || ! $result['status'] || (is_array($result) && count($result) == 0)) {
            $return = json_alert('PBX Aftersave error', 'error');
        } elseif ($result['status'] != 'success') {
            $return = json_alert($result['message'], 'warning');
        }

        if (! empty($return)) {
            return $return;
        }
    }

}

function afterdelete_time_condition_build_dialplan($request)
{

    if (empty($request->dialplan_uuid)) {
        return 'Dialplan uuid not set';
    }

    $presets = DB::connection('pbx')->table('p_time_condition_presets')->get();
    $dialplan = \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $request->dialplan_uuid)->get()->first();

    $dialplan_details = \DB::connection('pbx')->table('p_time_condition_details')->where('dialplan_uuid', $request->dialplan_uuid)->orderBy('group_priority')->get();
    DB::connection('pbx')->table('p_time_condition_details')->where('time_condition_preset_id', 11)->update(['group_priority' => 999]);
    // set group priority
    $group_number = 500;
    foreach ($dialplan_details as $d) {
        if ($d->group_priority != 999) {
            \DB::connection('pbx')->table('p_time_condition_details')->where('id', $d->id)->update(['group_priority' => $group_number]);
            $group_number += 10;
        }
    }

    // set domain_uuid
    \DB::connection('pbx')->table('p_time_condition_details')->where('dialplan_uuid', $request->dialplan_uuid)->update(['domain_uuid' => $dialplan->domain_uuid]);

    // get the dialplan details
    $dialplan_details = \DB::connection('pbx')->table('p_time_condition_details')->where('dialplan_uuid', $request->dialplan_uuid)->orderBy('group_priority')->get();

    \DB::connection('pbx')->table('v_dialplan_details')->where('dialplan_uuid', $request->dialplan_uuid)->delete();

    foreach ($dialplan_details as $dialplan_detail) {
        $order = 10;

        // FIRST CONDITION TIME CONDITION EXTENSION NUMBER
        $data = [
            'domain_uuid' => $dialplan->domain_uuid,
            'dialplan_uuid' => $dialplan->dialplan_uuid,
            'dialplan_detail_uuid' => pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid'),
            'dialplan_detail_tag' => 'condition',
            'dialplan_detail_type' => 'destination_number',
            'dialplan_detail_data' => '^'.$dialplan->dialplan_number.'$',
            'dialplan_detail_order' => $order,
            'dialplan_detail_group' => $dialplan_detail->group_priority,
        ];

        \DB::connection('pbx')->table('v_dialplan_details')->insert($data);
        $order += 10;

        // PRESET CONDITIONS
        if ($dialplan_detail->time_condition_preset_id != 11) {
            $preset_values = $presets->where('id', $dialplan_detail->time_condition_preset_id)->first();
            for ($i = 1; $i < 3; $i++) {
                if (! empty($preset_values->{'type_'.$i}) && ! empty($preset_values->{'value_'.$i})) {

                    // TRANSFER ACTION
                    $data = [
                        'domain_uuid' => $dialplan->domain_uuid,
                        'dialplan_uuid' => $dialplan->dialplan_uuid,
                        'dialplan_detail_uuid' => pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid'),
                        'dialplan_detail_tag' => 'condition',
                        'dialplan_detail_type' => $preset_values->{'type_'.$i},
                        'dialplan_detail_data' => $preset_values->{'value_'.$i},
                        'dialplan_detail_break' => 'never',
                        'dialplan_detail_group' => $dialplan_detail->group_priority,
                        'dialplan_detail_order' => $order,
                    ];

                    \DB::connection('pbx')->table('v_dialplan_details')->insert($data);
                    $order += 10;
                }
            }
        }

        // TRANSFER ACTION
        $data = [
            'domain_uuid' => $dialplan->domain_uuid,
            'dialplan_uuid' => $dialplan->dialplan_uuid,
            'dialplan_detail_uuid' => pbx_uuid('v_dialplan_details', 'dialplan_detail_uuid'),
            'dialplan_detail_tag' => 'action',
            'dialplan_detail_type' => 'transfer',
            'dialplan_detail_data' => $dialplan_detail->action,
            'dialplan_detail_group' => $dialplan_detail->group_priority,
            'dialplan_detail_order' => $order,
        ];

        \DB::connection('pbx')->table('v_dialplan_details')->insert($data);

        $group_number += 10;
    }

    // FUSION PBX AFTERSAVE TO BUILD XML AND DELETE CACHE

    if (! empty($dialplan->domain_uuid)) {
        $url = 'http://156.0.96.60/app/time_conditions/time_condition_edit.php';
        $client = new GuzzleHttp\Client;
        $params['headers'] = ['erp_aftersave' => 1];

        $pbx_row = \DB::connection('pbx')->table('v_users as vu')
            ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
            ->where('vd.domain_uuid', $dialplan->domain_uuid)
            ->get()->first();
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $dialplan->domain_uuid)->pluck('domain_name')->first();
        $url = str_replace('156.0.96.60', $domain_name, $url);
        $key = $pbx_row->api_key;

        $params['query'] = ['domain_name' => $domain_name, 'dialplan_uuid' => $dialplan->dialplan_uuid, 'key' => $key, 'api_key' => $key];

        $params['form_params'] = $post_data;

        $response = $client->post($url, $params);

        $result = $response->getBody()->getContents();

        $result = json_decode($result, true);
        if (! $result || ! $result['status'] || (is_array($result) && count($result) == 0)) {
            $return = json_alert('PBX Aftersave error', 'error');
        } elseif ($result['status'] != 'success') {
            $return = json_alert($result['message'], 'warning');
        }

        if (! empty($return)) {
            return $return;
        }
    }

}

// TIME CONDTION ROUTING SELECTS

function time_condition_exit_routing_select($row)
{
    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $domain_uuid = $row->domain_uuid;
    $routing = [];

    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
    foreach ($extensions as $ext) {
        $routing[$ext->extension] = 'Extension - '.$ext->description.' '.$ext->extension;
    }

    $ring_groups = \DB::connection('pbx')->table('v_ring_groups')->where('domain_uuid', $domain_uuid)->orderby('ring_group_extension')->get();
    foreach ($ring_groups as $ext) {
        $routing[$ext->ring_group_extension] = 'Ring Group - '.$ext->ring_group_name.' '.$ext->ring_group_extension;
    }

    $ivr_menus = \DB::connection('pbx')->table('v_ivr_menus')->where('domain_uuid', $domain_uuid)->orderby('ivr_menu_extension')->get();
    foreach ($ivr_menus as $ext) {
        $routing[$ext->ivr_menu_extension] = 'IVR Menu - '.$ext->ivr_menu_name.' '.$ext->ivr_menu_extension;
    }

    return $routing;
}

function time_condition_detail_routing_select($row)
{
    $row = (object) $row;

    $domain_uuid = $row->domain_uuid;
    if (! $domain_uuid && ! empty(request()->dialplan_uuid)) {
        $domain_uuid = \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', request()->dialplan_uuid)->pluck('domain_uuid')->first();
    }

    if (! $domain_uuid) {
        return [];
    }

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();

    $routing = [];

    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
    foreach ($extensions as $ext) {
        $routing[$ext->extension.' XML '.$domain_name] = 'Extension - '.$ext->description.' '.$ext->extension;
    }

    $extensions = \DB::connection('pbx')->table('v_voicemails')->where('domain_uuid', $domain_uuid)->orderby('voicemail_id')->get();
    foreach ($extensions as $ext) {
        $routing['*99'.$ext->voicemail_id.' XML '.$domain_name] = 'Voicemail - '.$ext->voicemail_description.' '.$ext->voicemail_id;
    }

    $ring_groups = \DB::connection('pbx')->table('v_ring_groups')->where('domain_uuid', $domain_uuid)->orderby('ring_group_extension')->get();
    foreach ($ring_groups as $ext) {
        $routing[$ext->ring_group_extension.' XML '.$domain_name] = 'Ring Group - '.$ext->ring_group_name.' '.$ext->ring_group_extension;
    }

    $ivr_menus = \DB::connection('pbx')->table('v_ivr_menus')->where('domain_uuid', $domain_uuid)->orderby('ivr_menu_extension')->get();
    foreach ($ivr_menus as $ext) {
        $routing[$ext->ivr_menu_extension.' XML '.$domain_name] = 'IVR Menu - '.$ext->ivr_menu_name.' '.$ext->ivr_menu_extension;
    }

    $recordings = \DB::connection('pbx')->table('v_recordings')->where('domain_uuid', $domain_uuid)->orderby('recording_filename')->get();
    foreach ($recordings as $recording) {
        $routing['streamfile.lua '.$recording->recording_filename] = $recording->recording_filename;
    }

    return $routing;
}

function dow_abbr($dow_numeric)
{
    $dowMap = [
        1 => 'Sun',
        2 => 'Mon',
        3 => 'Tue',
        4 => 'Wed',
        5 => 'Thu',
        6 => 'Fri',
        7 => 'Sat',
    ];

    return $dowMap[$dow_numeric];
}

function convertToHoursMins($time, $format = '%02d:%02d')
{
    if ($time < 1) {
        return '00:00';
    }
    $hours = floor($time / 60);
    $minutes = ($time % 60);

    return sprintf($format, $hours, $minutes);
}

function get_diaplan_condition_name($tcds)
{
    $preset_name_arr = [];
    foreach ($tcds as $tcd) {
        if ($tcd->dialplan_detail_type == 'wday') {
            $arr = explode('-', $tcd->dialplan_detail_data);
            if ($arr[0] == $arr[1]) {
                $preset_name_arr[] = dow_abbr($arr[0]);
            } else {
                $preset_name_arr[] = dow_abbr($arr[0]).' to '.dow_abbr($arr[1]);
            }
        }
    }
    foreach ($tcds as $tcd) {
        if ($tcd->dialplan_detail_type == 'minute-of-day') {
            $arr = explode('-', $tcd->dialplan_detail_data);
            if ($arr[0] == $arr[1]) {
                $preset_name_arr[] = convertToHoursMins($arr[0]);
            } else {
                $preset_name_arr[] = convertToHoursMins($arr[0]).' to '.convertToHoursMins($arr[1]);
            }
        }
    }
    foreach ($tcds as $tcd) {
        if ($tcd->dialplan_detail_type == 'hour') {
            $arr = explode('-', $tcd->dialplan_detail_data);
            if ($arr[0] == $arr[1]) {
                $preset_name_arr[] = convertToHoursMins($arr[0] * 60);
            } else {
                $preset_name_arr[] = convertToHoursMins($arr[0] * 60).' to '.convertToHoursMins($arr[1] * 60);
            }
        }
    }

    return implode(' ', $preset_name_arr);
}

function generate_time_condition_details_from_dialplans()
{

    $time_conditions = \DB::connection('pbx')->table('v_dialplans')->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->get();

    foreach ($time_conditions as $time_condition) {
        $time_condition_details = DB::connection('pbx')->table('v_dialplan_details')
            ->where('dialplan_uuid', $time_condition->dialplan_uuid)
            ->where('dialplan_detail_tag', 'condition')
            ->where('dialplan_detail_type', '!=', 'destination_number')
            ->where('dialplan_detail_group', '!=', 999)
            ->orderBy('dialplan_detail_order')
            ->get();
        $time_condition_group = collect($time_condition_details)->groupBy('dialplan_detail_group');
        foreach ($time_condition_group as $group => $tcds) {
            $preset_data = [];
            foreach ($tcds as $i => $tcd) {
                $j = $i + 1;
                $preset_data['type_'.$j] = $tcd->dialplan_detail_type;
                $preset_data['value_'.$j] = $tcd->dialplan_detail_data;
            }
            $insert_data = $preset_data;
            $insert_data['name'] = get_diaplan_condition_name($tcds);
            //$data = ['name' => $group];
            DB::connection('pbx')->table('p_time_condition_presets')->updateOrInsert($preset_data, $insert_data);
        }
    }

    \DB::connection('pbx')->table('p_time_condition_details')->truncate();
    foreach ($time_conditions as $time_condition) {
        $time_condition_details = DB::connection('pbx')->table('v_dialplan_details')
            ->where('dialplan_uuid', $time_condition->dialplan_uuid)
            ->where('dialplan_detail_tag', 'condition')
            ->where('dialplan_detail_type', '!=', 'destination_number')
            ->where('dialplan_detail_group', '!=', 999)
            ->orderBy('dialplan_detail_group')
            ->orderBy('dialplan_detail_order')
            ->get();
        $time_condition_group = collect($time_condition_details)->groupBy('dialplan_detail_group');

        foreach ($time_condition_group as $group => $tcds) {
            $preset_data = [];
            foreach ($tcds as $i => $tcd) {
                $j = $i + 1;
                $preset_data['type_'.$j] = $tcd->dialplan_detail_type;
                $preset_data['value_'.$j] = $tcd->dialplan_detail_data;
            }

            $time_condition_detail_preset_id = DB::connection('pbx')->table('p_time_condition_presets')->where($preset_data)->pluck('id')->first();

            $time_condition_detail_action = DB::connection('pbx')->table('v_dialplan_details')
                ->where('dialplan_uuid', $time_condition->dialplan_uuid)
                ->where('dialplan_detail_tag', 'action')
                ->where('dialplan_detail_group', $group)
                ->pluck('dialplan_detail_data')->first();

            $detail_data = [
                'time_condition_preset_id' => $time_condition_detail_preset_id,
                'action' => $time_condition_detail_action,
                'domain_uuid' => $time_condition->domain_uuid,
                'dialplan_uuid' => $time_condition->dialplan_uuid,
                'group_priority' => $group,
            ];

            DB::connection('pbx')->table('p_time_condition_details')->insert($detail_data);
        }

        // exit actions

        $time_condition_details = DB::connection('pbx')->table('v_dialplan_details')
            ->where('dialplan_uuid', $time_condition->dialplan_uuid)
            ->where('dialplan_detail_tag', 'condition')
            ->where('dialplan_detail_group', 999)
            ->orderBy('dialplan_detail_order')
            ->get();
        $time_condition_group = collect($time_condition_details)->groupBy('dialplan_detail_group');

        foreach ($time_condition_group as $group => $tcds) {
            $time_condition_detail_preset_id = 11;

            $time_condition_detail_action = DB::connection('pbx')->table('v_dialplan_details')
                ->where('dialplan_uuid', $time_condition->dialplan_uuid)
                ->where('dialplan_detail_tag', 'action')
                ->where('dialplan_detail_group', $group)
                ->pluck('dialplan_detail_data')->first();

            $detail_data = [
                'time_condition_preset_id' => $time_condition_detail_preset_id,
                'action' => $time_condition_detail_action,
                'domain_uuid' => $time_condition->domain_uuid,
                'dialplan_uuid' => $time_condition->dialplan_uuid,
                'group_priority' => $group,
            ];

            DB::connection('pbx')->table('p_time_condition_details')->insert($detail_data);
        }
    }

}
