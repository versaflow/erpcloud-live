<?php

/// IVR MENU
function beforesave_ivr_menu_check_extension($request)
{
    if (! is_numeric($request->ivr_menu_extension)) {
        return json_alert('Extension needs to be digits.', 'warning');
    }
}

function aftersave_ivr_menu_copy_audio_files($request)
{
    if (! empty($request->id)) {
        $ivr_menu_uuid = $request->id;
    } else {
        $ivr_menu_uuid = $request->ivr_menu_uuid;
    }
    $ivr = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr_menu_uuid)->get()->first();
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ivr->domain_uuid)->pluck('domain_name')->first();

    if (! empty($ivr->ivr_menu_greet_long)) {
        $file = $ivr->ivr_menu_greet_long;

        $file_path = uploads_path(775).$file;

        if (file_exists($file_path)) {
            $ssh = new \phpseclib\Net\SSH2('pbx.cloudtools.co.za');
            if ($ssh->login('root', 'Ahmed777')) {
                $scp = new \phpseclib\Net\SCP($ssh);
                $remote = '/var/lib/freeswitch/recordings/'.$domain_name.'/'.$file;

                $result = $scp->put($remote, $file_path, $scp->SOURCE_LOCAL_FILE);

                if ($result) {
                    $cmd = 'chown freeswitch:daemon '.$remote.' && chmod 777 '.$remote;
                    $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
                }
            }
        }
    }

    if (! empty($ivr->ivr_menu_invalid_sound)) {
        $file = $ivr->ivr_menu_invalid_sound;
        $file_path = uploads_path(775).$file;

        $file = strtolower(str_replace(' ', '_', $file));
        $file = strtolower(str_replace('-', '_', $file));
        \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr_menu_uuid)->update(['ivr_menu_invalid_sound' => $file]);
        if (file_exists($file_path)) {
            $ssh = new \phpseclib\Net\SSH2('pbx.cloudtools.co.za');
            if ($ssh->login('root', 'Ahmed777')) {
                $scp = new \phpseclib\Net\SCP($ssh);
                $remote = '/var/lib/freeswitch/recordings/'.$domain_name.'/'.$file;

                $result = $scp->put($remote, $file_path, $scp->SOURCE_LOCAL_FILE);

                if ($result) {
                    $cmd = 'chown freeswitch:daemon '.$remote.' && chmod 777 '.$remote;
                    $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
                }
            }
        }
    }
}

function aftersave_ivr_menu_set_dialplan($request)
{
    if (! empty($request->id)) {
        $ivr_menu_uuid = $request->id;
    } else {
        $ivr_menu_uuid = $request->ivr_menu_uuid;
    }

    $ivr = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr_menu_uuid)->get()->first();

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ivr->domain_uuid)->pluck('domain_name')->first();
    //build the xml dialplan
    if (empty($ivr->dialplan_uuid)) {
        $new_dialplan = true;
        $dialplan_uuid = pbx_uuid('v_dialplans', 'dialplan_uuid');
    } else {
        $new_dialplan = false;
        $dialplan_uuid = $ivr->dialplan_uuid;
    }

    $dialplan_xml = '<extension name="'.$ivr->ivr_menu_name.'" continue="false" uuid="'.$dialplan_uuid."\">\n";
    $dialplan_xml .= '	<condition field="destination_number" expression="^'.$ivr->ivr_menu_extension."\$\">\n";
    $dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";
    $dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
    $dialplan_xml .= "		<action application=\"sleep\" data=\"1000\"/>\n";
    $dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
    $dialplan_xml .= '		<action application="set" data="ringback='.$ivr->ivr_menu_ringback."\"/>\n";
    $dialplan_xml .= '		<action application="set" data="presence_id='.$ivr->ivr_menu_extension.'@'.$_SESSION['domain_name']."\"/>\n";

    $dialplan_xml .= '		<action application="set" data="transfer_ringback='.$ivr->ivr_menu_ringback."\"/>\n";
    $dialplan_xml .= '		<action application="set" data="ivr_menu_uuid='.$ivr->ivr_menu_uuid."\"/>\n";

    $dialplan_xml .= '		<action application="ivr" data="'.$ivr->ivr_menu_uuid."\"/>\n";

    $dialplan_xml .= '		<action application="'.$ivr->ivr_menu_exit_app.'" data="'.$ivr->ivr_menu_exit_data."\"/>\n";
    $dialplan_xml .= "	</condition>\n";
    $dialplan_xml .= "</extension>\n";

    //build the dialplan array
    $dialplan['domain_uuid'] = $ivr->domain_uuid;
    $dialplan['dialplan_uuid'] = $dialplan_uuid;
    $dialplan['dialplan_name'] = $ivr->ivr_menu_name;
    $dialplan['dialplan_number'] = $ivr->ivr_menu_extension;
    $dialplan['dialplan_context'] = $domain_name;
    $dialplan['dialplan_continue'] = 'false';
    $dialplan['dialplan_xml'] = $dialplan_xml;
    $dialplan['dialplan_order'] = '101';
    $dialplan['dialplan_enabled'] = 'true';
    $dialplan['dialplan_description'] = $ivr->ivr_menu_description;
    $dialplan['app_uuid'] = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';

    if ($new_dialplan) {
        \DB::connection('pbx')->table('v_dialplans')->insert($dialplan);
        \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr->ivr_menu_uuid)->update(['dialplan_uuid' => $dialplan_uuid]);
    } else {
        \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $dialplan_uuid)->update($dialplan);
    }

    $pbx = new FusionPBX;
    $pbx->portalCmd('portal_ivr_dialplan_save', $domain_name);
    $pbx->portalCmd('portal_ivr_conf_save', $ivr->ivr_menu_uuid);
}

function afterdelete_ivr_menu_set_dialplan($request)
{
    \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $request->dialplan_uuid)->delete();
    \DB::connection('pbx')->table('v_ivr_menu_options')->where('ivr_menu_uuid', $request->ivr_menu_uuid)->delete();
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $request->domain_uuid)->pluck('domain_name')->first();
    $pbx = new FusionPBX;
    $pbx->portalCmd('portal_ivr_dialplan_save', $domain_name);
    $pbx->portalCmd('portal_ivr_conf_save', $request->ivr_menu_uuid);
}

/// IVR MENU DETAILS
function aftersave_ivr_menu_details_set_dialplan($request)
{
    $ivr = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $request->ivr_menu_uuid)->get()->first();
    \DB::connection('pbx')->table('v_ivr_menu_options')->where('ivr_menu_uuid', $request->ivr_menu_uuid)->update(['domain_uuid' => $ivr->domain_uuid]);

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ivr->domain_uuid)->pluck('domain_name')->first();
    if (! str_contains($request->ivr_menu_option_param, 'transfer ')) {
        \DB::connection('pbx')->table('v_ivr_menu_options')
            ->where('ivr_menu_option_uuid', $request->ivr_menu_option_uuid)
            ->update(['ivr_menu_option_param' => 'transfer '.$request->ivr_menu_option_param.' XML '.$domain_name]);
    }

    $pbx = new FusionPBX;
    $pbx->portalCmd('portal_ivr_dialplan_save', $domain_name);
    $pbx->portalCmd('portal_ivr_conf_save', $request->ivr_menu_uuid);
}

function afterdelete_ivr_menu_details_set_dialplan($request)
{
    $ivr = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $request->ivr_menu_uuid)->get()->first();

    \DB::connection('pbx')->table('v_ivr_menu_options')->where('ivr_menu_uuid', $request->ivr_menu_uuid)->update(['domain_uuid' => $ivr->domain_uuid]);
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ivr->domain_uuid)->pluck('domain_name')->first();
    $pbx = new FusionPBX;
    $pbx->portalCmd('portal_ivr_dialplan_save', $domain_name);
    $pbx->portalCmd('portal_ivr_conf_save', $request->ivr_menu_uuid);
}

// IVR MENU ROUTING SELECTS

function ivr_menu_exit_routing_select($row)
{
    $row = (object) $row;

    if (! empty(request()->domain_uuid)) {
        $domain_uuid = request()->domain_uuid;
    } elseif (! empty($row->domain_uuid)) {
        $domain_uuid = $row->domain_uuid;
    } else {
        $ivr_menu_uuid = request()->ivr_menu_uuid;
        $ivr = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr_menu_uuid)->get()->first();
        $domain_uuid = $ivr->domain_uuid;
    }

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();
    $routing = [];

    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
    foreach ($extensions as $ext) {
        $routing[$ext->extension.' XML '.$domain_name] = 'Extension - '.$ext->description.' '.$ext->extension;
    }

    $ring_groups = \DB::connection('pbx')->table('v_ring_groups')->where('domain_uuid', $domain_uuid)->orderby('ring_group_extension')->get();
    foreach ($ring_groups as $ext) {
        $routing[$ext->ring_group_extension.' XML '.$domain_name] = 'Ring Group - '.$ext->ring_group_name.' '.$ext->ring_group_extension;
    }

    $ivr_menus = \DB::connection('pbx')->table('v_dialplans')->where('domain_uuid', $domain_uuid)->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->orderby('dialplan_number')->get();
    foreach ($ivr_menus as $ext) {
        $routing[$ext->dialplan_number.' XML '.$domain_name] = 'Time Condition - '.$ext->dialplan_name.' '.$ext->dialplan_number;
    }

    return $routing;
}

function ivr_menu_details_routing_select($row)
{
    $row = (object) $row;

    if (! empty($row->domain_uuid)) {
        $domain_uuid = $row->domain_uuid;
    } else {
        $ivr_menu_uuid = request()->ivr_menu_uuid;
        $ivr = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr_menu_uuid)->get()->first();
        $domain_uuid = $ivr->domain_uuid;
    }

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();
    $routing = [];

    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
    foreach ($extensions as $ext) {
        $routing['transfer '.$ext->extension.' XML '.$domain_name] = 'Extension - '.$ext->description.' '.$ext->extension;
    }

    $ring_groups = \DB::connection('pbx')->table('v_ring_groups')->where('domain_uuid', $domain_uuid)->orderby('ring_group_extension')->get();
    foreach ($ring_groups as $ext) {
        $routing['transfer '.$ext->ring_group_extension.' XML '.$domain_name] = 'Ring Group - '.$ext->ring_group_name.' '.$ext->ring_group_extension;
    }

    $ivr_menus = \DB::connection('pbx')->table('v_dialplans')->where('domain_uuid', $domain_uuid)->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->orderby('dialplan_number')->get();
    foreach ($ivr_menus as $ext) {
        $routing['transfer '.$ext->dialplan_number.' XML '.$domain_name] = 'Time Condition - '.$ext->dialplan_name.' '.$ext->dialplan_number;
    }

    if (! empty($row->ivr_menu_option_param)) {
        if (! in_array($row->ivr_menu_option_param, array_keys($routing))) {
            $routing[$row->ivr_menu_option_param] = 'Custom - '.str_replace(['transfer ', ' XML '.$domain_name], '', $row->ivr_menu_option_param);
        }
    }

    return $routing;
}
