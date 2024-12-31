<?php

function aftersave_call_center_queues($request)
{
    $call_center = \DB::connecttion('pbx')->table('v_call_center_queues')->where('call_center_queue_uuid', $request->call_center_queue_uuid)->get()->first();
    $post_data = $request->all();
    $result = fusionpbx_edit_curl('http://156.0.96.60/app/call_centers/call_center_queue_edit.php?', $call_center->call_center_queue_uuid, $post_data, true);
    if (! empty($result)) {
        return $result;
    }
}

function button_ccq_start($request)
{
    $call_center = \DB::connecttion('pbx')->table('v_call_center_queues')->where('call_center_queue_uuid', $request->call_center_queue_uuid)->get()->first();
    $domain_name = \DB::connecttion('pbx')->table('v_domains')->where('domain_uuid', $call_center->domain_uuid)->pluck('domain_name')->first();
    $cmd = 'callcenter_config queue load '.$call_center->queue_extension.'@'.$domain_name;

    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_call_center_queue', $cmd);

    return json_alert('Queue started');
}

function button_ccq_stop($request)
{
    $call_center = \DB::connecttion('pbx')->table('v_call_center_queues')->where('call_center_queue_uuid', $request->call_center_queue_uuid)->get()->first();
    $domain_name = \DB::connecttion('pbx')->table('v_domains')->where('domain_uuid', $call_center->domain_uuid)->pluck('domain_name')->first();
    $cmd = 'callcenter_config queue unload '.$call_center->queue_extension.'@'.$domain_name;

    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_call_center_queue', $cmd);

    return json_alert('Queue stopped');
}

function button_ccq_restart($request)
{
    $call_center = \DB::connecttion('pbx')->table('v_call_center_queues')->where('call_center_queue_uuid', $request->call_center_queue_uuid)->get()->first();
    $domain_name = \DB::connecttion('pbx')->table('v_domains')->where('domain_uuid', $call_center->domain_uuid)->pluck('domain_name')->first();
    $cmd = 'callcenter_config queue reload '.$call_center->queue_extension.'@'.$domain_name;

    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_call_center_queue', $cmd);

    return json_alert('Queue restarted');
}

function aftersave_call_broadcasts($request)
{
    $call_broadcast = \DB::connecttion('pbx')->table('v_call_broadcasts')->where('call_broadcast_uuid', $request->call_broadcast_uuid)->get()->first();
    $post_data = $request->all();
    $result = fusionpbx_edit_curl('http://156.0.96.60/app/call_broadcast/call_broadcast_edit.php?', $call_broadcast->call_broadcast_uuid, $post_data, true);
    if (! empty($result)) {
        return $result;
    }
}

function button_callbroadcast_stop($request)
{
    $call_broadcast = \DB::connecttion('pbx')->table('v_call_broadcasts')->where('call_broadcast_uuid', $request->call_broadcast_uuid)->get()->first();
    $uuid = $request->call_broadcast_uuid;
    $cmd = 'sched_del '.$uuid;
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_call_broadcast', $cmd);

    return json_alert('Callbroadcast stopped');
}

function button_callbroadcast_start($request)
{

    $pbx = new FusionPBX;
    $call_broadcast = \DB::connecttion('pbx')->table('v_call_broadcasts')->where('call_broadcast_uuid', $request->call_broadcast_uuid)->get()->first();
    $domain_name = \DB::connecttion('pbx')->table('v_domains')->where('domain_uuid', $call_broadcast->domain_uuid)->pluck('domain_name')->first();
    $domain_uuid = $call_broadcast->domain_uuid;
    $call_broadcast_uuid = $request->call_broadcast_uuid;
    extract((array) $call_broadcast);
    if (isset($broadcast_start_time) && is_numeric($broadcast_start_time)) {
        $sched_seconds = $broadcast_start_time;
    } else {
        $sched_seconds = '3';
    }
    //send the call broadcast
    if (strlen($broadcast_phone_numbers) > 0) {
        $broadcast_phone_number_array = explode("\n", $broadcast_phone_numbers);
        $count = 1;
        foreach ($broadcast_phone_number_array as $tmp_value) {
            //set the variables
            $tmp_value = str_replace(';', '|', $tmp_value);
            $tmp_value_array = explode('|', $tmp_value);

            //remove the number formatting
            $phone_1 = preg_replace('{\D}', '', $tmp_value_array[0]);

            if (is_numeric($phone_1)) {
                //get the dialplan variables and bridge statement
                //$dialplan = new dialplan;
                //$dialplan->domain_uuid = $_SESSION['domain_uuid'];
                //$dialplan->outbound_routes($phone_1);
                //$dialplan_variables = $dialplan->variables;
                //$bridge_array[0] = $dialplan->bridges;

                //prepare the string
                $channel_variables = 'ignore_early_media=true';
                $channel_variables .= ',origination_number='.$phone_1;
                $channel_variables .= ",origination_caller_id_name='$broadcast_caller_id_name'";
                $channel_variables .= ",origination_caller_id_number=$broadcast_caller_id_number";
                $channel_variables .= ',domain_uuid='.$domain_uuid;
                $channel_variables .= ',domain='.$domain_name;
                $channel_variables .= ',domain_name='.$domain_name;
                $channel_variables .= ",accountcode='$broadcast_accountcode'";
                $channel_variables .= ",toll_allow='$broadcast_toll_allow'";
                if ($broadcast_avmd == 'true') {
                    $channel_variables .= ",execute_on_answer='avmd start'";
                }
                //$origination_url = "{".$channel_variables."}".$bridge_array[0];
                $origination_url = '{'.$channel_variables.'}loopback/'.$phone_1.'/'.$domain_name;

                //get the context
                $context = $domain_name;

                //set the command
                $cmd = 'bgapi sched_api +'.$sched_seconds.' '.$call_broadcast_uuid.' bgapi originate '.$origination_url.' '.$broadcast_destination_data." XML $context";

                $result = $pbx->portalCmd('portal_call_broadcast', $cmd);

                //method 2
                //cmd_async($_SESSION['switch']['bin']['dir']."/fs_cli -x \"".$cmd."\";");

                //spread the calls out so that they are scheduled with different times
                if (strlen($broadcast_concurrent_limit) > 0 && strlen($broadcast_timeout) > 0) {
                    if ($broadcast_concurrent_limit == $count) {
                        $sched_seconds = $sched_seconds + $broadcast_timeout;
                        $count = 0;
                    }
                }

                $count++;
            }
        }
    }
    $pbx = new FusionPBX;
    $result = $pbx->portalCmd('portal_call_broadcast', $cmd);

    return json_alert('Callbroadcast start');
}

function aftersave_call_center_agents($request)
{
    $call_center = \DB::connecttion('pbx')->table('v_call_center_agents')->where('call_center_agent_uuid', $request->call_center_agent_uuid)->get()->first();
    $post_data = $request->all();
    $result = fusionpbx_edit_curl('http://156.0.96.60/app/call_centers/call_center_agent_edit.php?', $call_center->call_center_agent_uuid, $post_data, true);
    if (! empty($result)) {
        return $result;
    }
}
