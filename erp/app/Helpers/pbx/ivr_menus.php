<?php

function aftersave_ivr_menus($request)
{

    if (empty($request->ivr_menu_extension)) {
        $row = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $request->ivr_menu_uuid)->get()->first();

        $ivr_menu_extension = generate_ivr_extension($row);

        \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $request->ivr_menu_uuid)->update(['ivr_menu_extension' => $ivr_menu_extension]);
    }

    ivr_menu_update_dialplan_xml($request->ivr_menu_uuid);
}

function aftersave_ivr_menu_options($request)
{
    ivr_menu_update_dialplan_xml($request->ivr_menu_uuid);
}

function ivr_menu_update_dialplan_xml($ivr_menu_uuid)
{

    $ivr_menu_options = \DB::connection('pbx')->table('v_ivr_menu_options')->where('ivr_menu_uuid', $ivr_menu_uuid)->count();
    // if(!$ivr_menu_options){
    //     return false; // only rebuild cache and dialplan if ivr options exists
    // }
    $ivr_menu = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr_menu_uuid)->get()->first();

    extract((array) $ivr_menu);

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ivr_menu->domain_uuid)->pluck('domain_name')->first();

    \DB::connection('pbx')->table('v_ivr_menu_options')->where('ivr_menu_uuid', $ivr_menu_uuid)->update(['ivr_menu_option_action' => 'menu-exec-app', 'domain_uuid' => $ivr_menu->domain_uuid]);
    $ivr_menu_context = $domain_name;
    $data = [];

    if (! empty($ivr_menu_exit_data)) {
        $ivr_menu_exit_app = 'transfer';
        $data['ivr_menu_exit_app'] = $ivr_menu_exit_app;
        $data['ivr_menu_exit_data'] = $ivr_menu_exit_data;
    }
    if (! empty($ivr_menu_voice)) {
        $ivr_menu_language = 'en';
        $ivr_menu_dialect = 'us';
        $data['ivr_menu_language'] = $ivr_menu_language;
        $data['ivr_menu_dialect'] = $ivr_menu_dialect;
    }

    //build the xml dialplan
    $dialplan_xml = '<extension name="'.$ivr_menu_name.'" continue="false" uuid="'.$dialplan_uuid."\">\n";
    $dialplan_xml .= '	<condition field="destination_number" expression="^'.$ivr_menu_extension."\$\">\n";
    $dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";

    $dialplan_xml .= "		<action application=\"sleep\" data=\"1000\"/>\n";
    $dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
    $dialplan_xml .= '		<action application="set" data="ringback='.$ivr_menu_ringback."\"/>\n";
    if (! empty($ivr_menu_voice)) {
        $dialplan_xml .= '		<action application="set" data="default_language='.$ivr_menu_language."\" inline=\"true\"/>\n";
        $dialplan_xml .= '		<action application="set" data="default_dialect='.$ivr_menu_dialect."\" inline=\"true\"/>\n";
        $dialplan_xml .= '		<action application="set" data="default_voice='.$ivr_menu_voice."\" inline=\"true\"/>\n";
    }
    $dialplan_xml .= '		<action application="set" data="transfer_ringback='.$ivr_menu_ringback."\"/>\n";
    $dialplan_xml .= '		<action application="set" data="ivr_menu_uuid='.$ivr_menu_uuid."\"/>\n";

    $dialplan_xml .= '		<action application="ivr" data="'.$ivr_menu_uuid."\"/>\n";

    if (! empty($ivr_menu_exit_data)) {
        $dialplan_xml .= '		<action application="'.$ivr_menu_exit_app.'" data="'.$ivr_menu_exit_data."\"/>\n";
    }
    $dialplan_xml .= "	</condition>\n";
    $dialplan_xml .= "</extension>\n";

    //build the dialplan array
    $dialplan_data = [];

    $dialplan_data['domain_uuid'] = $domain_uuid;
    $dialplan_data['dialplan_uuid'] = $dialplan_uuid;
    $dialplan_data['dialplan_name'] = $ivr_menu_name;
    $dialplan_data['dialplan_number'] = $ivr_menu_extension;
    if (isset($ivr_menu_context)) {
        $dialplan_data['dialplan_context'] = $ivr_menu_context;
    }
    $dialplan_data['dialplan_continue'] = 'false';
    $dialplan_data['dialplan_xml'] = $dialplan_xml;
    $dialplan_data['dialplan_order'] = '101';
    $dialplan_data['dialplan_enabled'] = $ivr_menu_enabled;
    $dialplan_data['dialplan_description'] = $ivr_menu_description;
    $dialplan_data['app_uuid'] = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';

    $dialplan_data['dialplan_xml'] = $dialplan_xml;
    if (count($data) > 0) {
        \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', $ivr_menu_uuid)->update($data);
    }
    \DB::connection('pbx')->table('v_dialplans')->updateOrInsert(['dialplan_uuid' => $dialplan_uuid], $dialplan_data);

    //clear the cache
    $pbx = new \FusionPBX;
    $is_deleted = $pbx->portalCmd('delete_cache_item', 'dialplan:'.$domain_name);
    $is_deleted = $pbx->portalCmd('delete_cache_item', 'configuration:ivr.conf:'.$ivr_menu_uuid);

}

function generate_ivr_extension($row)
{
    $row = (object) $row;
    if (! empty($row->ivr_menu_extension)) {
        return $row->ivr_menu_extension;
    }

    $extension = pbx_generate_extension($row->domain_uuid, 1001);

    return $extension;
}

function ivr_recording_select($row)
{
    // aa($row);
    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $domain_uuid = $row->domain_uuid;
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();

    $routing = [];

    $recordings = \DB::connection('pbx')->table('v_recordings')->where('domain_uuid', $domain_uuid)->orderby('recording_filename')->get();
    foreach ($recordings as $ext) {
        $routing[$ext->recording_filename] = $ext->recording_filename;
    }

    return $routing;
}

function ivr_recording_ringback_select($row)
{
    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $domain_uuid = $row->domain_uuid;
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();

    $routing = [];

    $recordings = \DB::connection('pbx')->table('v_recordings')->where('domain_uuid', $domain_uuid)->orderby('recording_filename')->get();
    foreach ($recordings as $ext) {
        $routing[$ext->recording_filename] = $ext->recording_filename;
    }

    $cmd = 'ls /var/www/_admin/sounds/en/us/callie/ivr/48000';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $ivr_sounds = collect(explode(PHP_EOL, $result))->filter()->toArray();
    foreach ($ivr_sounds as $ivr_sound) {
        if (str_ends_with($ivr_sound, '.wav')) {
            $routing['ivr/'.$ivr_sound] = $ivr_sound;
        }
    }

    return $routing;
}

function ivr_recording_invalid_sound_select($row)
{
    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $domain_uuid = $row->domain_uuid;
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();

    $routing = [];

    $recordings = \DB::connection('pbx')->table('v_recordings')->where('domain_uuid', $domain_uuid)->orderby('recording_filename')->get();
    foreach ($recordings as $ext) {
        $routing[$ext->recording_filename] = $ext->recording_filename;
    }
    $cmd = 'ls /var/www/_admin/sounds/en/us/callie/ivr/48000';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $ivr_sounds = collect(explode(PHP_EOL, $result))->filter()->toArray();
    foreach ($ivr_sounds as $ivr_sound) {
        if (str_ends_with($ivr_sound, '.wav')) {
            $routing['ivr/'.$ivr_sound] = $ivr_sound;
        }
    }

    return $routing;
}

function ivr_timeout_select($row)
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

function ivr_destination_select($row)
{

    $row = (object) $row;

    $domain_uuid = $row->domain_uuid;
    if (! $domain_uuid && ! empty(request()->ivr_menu_uuid)) {
        $domain_uuid = \DB::connection('pbx')->table('v_ivr_menus')->where('ivr_menu_uuid', request()->ivr_menu_uuid)->pluck('domain_uuid')->first();
    }

    if (! $domain_uuid) {
        return [];
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

    $ivr_menus = \DB::connection('pbx')->table('v_ivr_menus')->where('domain_uuid', $domain_uuid)->orderby('ivr_menu_extension')->get();
    foreach ($ivr_menus as $ext) {
        $routing['transfer '.$ext->ivr_menu_extension.' XML '.$domain_name] = 'IVR Menu - '.$ext->ivr_menu_name.' '.$ext->ivr_menu_extension;
    }

    $ivr_menus = \DB::connection('pbx')->table('v_dialplans')->where('domain_uuid', $domain_uuid)->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->orderby('dialplan_number')->get();
    foreach ($ivr_menus as $ext) {
        $routing['transfer '.$ext->dialplan_number.' XML '.$domain_name] = 'Time Condition - '.$ext->dialplan_name.' '.$ext->dialplan_number;
    }

    $extensions = \DB::connection('pbx')->table('v_voicemails')->where('domain_uuid', $domain_uuid)->orderby('voicemail_id')->get();
    foreach ($extensions as $ext) {
        $routing['*99'.$ext->voicemail_id.' XML '.$domain_name] = 'Voicemail - '.$ext->voicemail_description.' '.$ext->voicemail_id;
    }

    return $routing;
}

function ivr_destination_options_curl($domain_uuid, $ivr_id)
{

    $url = 'http://156.0.96.60/app/ivr_menus/ivr_menu_edit.php';

    $client = new GuzzleHttp\Client;
    $params['headers'] = ['erp_aftersave' => 1];

    if (! empty($post_data['domain_uuid']) && ! $admin_request) {
        $pbx_row = \DB::connection('pbx')->table('v_users as vu')
            ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
            ->where('vd.domain_uuid', $post_data['domain_uuid'])
            ->get()->first();
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $post_data['domain_uuid'])->pluck('domain_name')->first();
        $url = str_replace('156.0.96.60', $domain_name, $url);
        $key = $pbx_row->api_key;
    } else {
        $key = 'e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
    }

    $params['query'] = ['id' => $ivr_id, 'get_options' => 1, 'key' => $key, 'api_key' => $key];

    // aa($url);
    // aa($params);
    $response = $client->get($url, $params);
    //aa($response);
    $result = $response->getBody()->getContents();

    //aa($result);
    $result = json_decode($result, true);

    return $result;
}
