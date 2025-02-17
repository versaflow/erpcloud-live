<?php

function generate_ringgroup_extension($row)
{
    $row = (object) $row;
    if (!empty($row->ring_group_extension)) {
        return $row->ring_group_extension;
    }
   
    $extension = pbx_generate_extension($row->domain_uuid, 1000);

    return $extension;
}

function ringgroup_timeout_select($row)
{
    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $domain_uuid = $row->domain_uuid;
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

    $ivr_menus = \DB::connection('pbx')->table('v_dialplans')->where('domain_uuid', $domain_uuid)->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->orderby('dialplan_number')->get();
    foreach ($ivr_menus as $ext) {
        $routing[$ext->dialplan_number.' XML '.$domain_name] = 'Time Condition - '.$ext->dialplan_name.' '.$ext->dialplan_number;
    }

    return $routing;
}

function ringgroup_destination_select($row)
{
    if (!empty($row) && !empty($row['ring_group_uuid'])) {
        $ring_group_uuid = $row['ring_group_uuid'];
    } else {
        $ring_group_uuid = request()->ring_group_uuid;
    }

    $ringgroup = \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $ring_group_uuid)->get()->first();
    $domain_uuid = $ringgroup->domain_uuid;

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();


    $routing = [];

    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
    foreach ($extensions as $ext) {
        $routing[$ext->extension] = 'Extension - '.$ext->description.' '.$ext->extension;
    }
    
    $keys = array_keys($routing);
    if(!empty($row) && !empty($row['destination_number']) && strlen($row['destination_number']) > 4 && !in_array($row['destination_number'],$routing)){
        $routing[$row['destination_number']] = 'External - '.$row['destination_number']; 
    }
    return $routing;
}

function aftersave_ringgroup($request)
{
    $ringgroup = \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->get()->first();
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ringgroup->domain_uuid)->pluck('domain_name')->first();
    if($ringgroup->ring_group_timeout == 'None'){
        \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->update(['ring_group_call_timeout' => NULL]);
    }else if($ringgroup->ring_group_timeout == 'Immediate'){
        \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->update(['ring_group_call_timeout' => 0]);
    }else{
        $seconds = trim(str_replace('seconds','',$ringgroup->ring_group_timeout));
        \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->update(['ring_group_call_timeout' => $seconds]);
    }
    
    if (!empty($request->ring_group_timeout_data)) {
        \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->update(['ring_group_context' => $domain_name, 'ring_group_timeout_app' => 'transfer']);
    } else {
        \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->update(['ring_group_context' => $domain_name, 'ring_group_timeout_data' => null,'ring_group_timeout_app' => null ]);
    }

    // create dialplan
    if (empty($ringgroup->dialplan_uuid)) {
        $dialplan_uuid = pbx_uuid('v_dialplans', 'dialplan_uuid');

        \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->update(['dialplan_uuid' => $dialplan_uuid]);
    } else {
        $dialplan_uuid = $ringgroup->dialplan_uuid;
    }

    $dialplan_xml = "<extension name=\"ring group\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
    $dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$ringgroup->ring_group_extension."$\">\n";
    $dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";
    $dialplan_xml .= "		<action application=\"set\" data=\"ring_group_uuid=".$ringgroup->ring_group_uuid."\"/>\n";
    $dialplan_xml .= "		<action application=\"lua\" data=\"app.lua ring_groups\"/>\n";
    $dialplan_xml .= "	</condition>\n";
    $dialplan_xml .= "</extension>\n";

    $dialplan_data = [
        'domain_uuid' => $ringgroup->domain_uuid,
        'app_uuid' => '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2',
        'dialplan_context' => $domain_name,
        'dialplan_name' => $ringgroup->ring_group_name,
        'dialplan_number' => $ringgroup->ring_group_extension,
        'dialplan_continue' => 'false',
        'dialplan_xml' => $dialplan_xml,
        'dialplan_order' => 101,
        'dialplan_enabled' => 'true',
        'dialplan_uuid' => $dialplan_uuid,
    ];

    if (!empty($ringgroup->dialplan_uuid)) {
        \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid', $ringgroup->dialplan_uuid)->update($dialplan_data);
    } else {
        \DB::connection('pbx')->table('v_dialplans')->insert($dialplan_data);
    }
    $pbx = new FusionPBX();
    $pbx->portalCmd('portal_ring_group_save', $domain_name);
}

function aftersave_ringgroup_destination($request)
{
    $ringgroup = \DB::connection('pbx')->table('v_ring_groups')->where('ring_group_uuid', $request->ring_group_uuid)->get()->first();
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ringgroup->domain_uuid)->pluck('domain_name')->first();
    \DB::connection('pbx')->table('v_ring_group_destinations')->where('ring_group_uuid', $request->ring_group_uuid)->update(['domain_uuid' => $ringgroup->domain_uuid]);
    $dest_count = \DB::connection('pbx')->table('v_ring_group_destinations')->where('ring_group_uuid', $request->ring_group_uuid)->count();
    \DB::connection('pbx')->table('v_ring_group_destinations')->whereNull('destination_delay')->update(['destination_delay' => 0]);
    \DB::connection('pbx')->table('v_ring_group_destinations')->whereNull('destination_timeout')->update(['destination_timeout' => 0]);
    $pbx = new FusionPBX();
    $pbx->portalCmd('portal_ring_group_save', $domain_name);
}
