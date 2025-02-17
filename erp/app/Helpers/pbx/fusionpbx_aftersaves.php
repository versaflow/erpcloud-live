<?php

function beforesave_check_pbx_subscription_type($request)
{
    try {
        $pbx_type = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $request->domain_uuid)->pluck('pbx_type')->first();
        if ($pbx_type == 'Phone Line') {
            return 'Phone line pbx domains do not have access to advanced features, please purchase TeleCloud PBX Extensions';
        }

        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $request->domain_uuid)->pluck('domain_name')->first();
        if (empty($domain_name)) {
            return 'Domain name required.';
        }
        if ($domain_name != '156.0.96.60') {
            if (!doesDomainResolve($domain_name)) {
                return 'Domain name is not active yet. DNS is still propagating.';
            }
        }
    } catch (\Throwable $ex) {
        return $ex->getMessage();
    }
}

function doesDomainResolve($domain)
{
    // Using gethostbyname to check if the domain resolves
    $resolvedIp = gethostbyname($domain);
    if ($resolvedIp === $domain) {
        return false;
    }

    // Optional: Using checkdnsrr to further verify the domain resolution
    if (!checkdnsrr($domain, 'A') && !checkdnsrr($domain, 'AAAA')) {
        return false;
    }

    return true;
}

function fusionpbx_edit_curl($url, $query_id, $post_data, $admin_request = false)
{
    if (str_contains($url, 'extension_edit')) {
        unset($post_data['id']);
    }

    $client = new GuzzleHttp\Client();
    $params['headers'] = ['erp_aftersave' => 1];

    if (!empty($post_data['domain_uuid']) && !$admin_request) {
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

    $params['query'] = ['id' => $query_id, 'key' => $key, 'api_key' => $key];
    $params['form_params'] = $post_data;

    aa($url);
    aa($params);
    $response = $client->post($url, $params);
    aa($response);

    $result = $response->getBody()->getContents();
    $found = preg_match('/\{([^}]*)\}/',$result, $results);
    $result = stripslashes($results[0]);
    // aa($result);
    $result = json_decode($result, false);

    if (!$result || !$result->status) {
        $return = json_alert('PBX Aftersave error', 'error');
    } elseif ($result->status != 'success') {
        $return = json_alert($result->message, 'warning');
    }

    if (!empty($return)) {
        return $return;
    }
}

function beforesave_check_extension_length($request)
{
    if (!is_numeric($request->extension) || empty($request->extension) || strlen($request->extension) != 3) {
        return 'Invalid extension';
    }
}

function aftersave_extension_curl($request)
{
    $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $request->id)->get()->first();

    $extension = (array) $ext;
    $beforesave_row = session('event_db_record');
    if ($beforesave_row->extension != $ext->extension) {
        //update subscription
        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ext->domain_uuid)->pluck('account_id')->first();
        \DB::connection('default')->table('sub_services')
        ->where('account_id', $account_id)
        ->where('provision_type', 'pbx_extension')
        ->where('detail', $beforesave_row->extension)
        ->update(['detail' => $ext->extension]);
    }

    /*
         \DB::connection('pbx')->table('v_extensions')->where('forward_all_enabled','0')->orWhereNull('forward_all_enabled')->update(['forward_all_enabled'=>'false']);
         \DB::connection('pbx')->table('v_extensions')->where('forward_busy_enabled','0')->orWhereNull('forward_busy_enabled')->update(['forward_busy_enabled'=>'false']);
         \DB::connection('pbx')->table('v_extensions')->where('forward_no_answer_enabled','0')->orWhereNull('forward_no_answer_enabled')->update(['forward_no_answer_enabled'=>'false']);
         \DB::connection('pbx')->table('v_extensions')->where('forward_user_not_registered_enabled','0')->orWhereNull('forward_user_not_registered_enabled')->update(['forward_user_not_registered_enabled'=>'false']);
    */

    $call_forward = [
        'forward_user_not_registered_enabled' => $ext->forward_no_answer_enabled,
        'forward_user_not_registered_destination' => $ext->forward_all_destination,
        'forward_busy_destination' => $ext->forward_all_destination,
        'forward_no_answer_destination' => $ext->forward_all_destination,
    ];

    \DB::connection('pbx')->table('v_extensions')->where('id', $ext->id)->update($call_forward);

    foreach ($request->all() as $k => $v) {
        if (isset($extension[$k])) {
            $extension[$k] = $v;
        }
    }

    return fusionpbx_edit_curl('http://156.0.96.60/app/extensions/extension_edit.php', $request->extension_uuid, $extension);
}

function aftersave_phone_numbers_curl($request)
{
    $domain_uuid = \DB::connection('pbx')->table('p_phone_numbers')->where('id', $request->id)->pluck('domain_uuid')->first();
    if ($domain_uuid) {
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();
        if ($domain_name) {
            $pbx = new \FusionPBX();
            $pbx->portalCmd('delete_cache_item', 'dialplan:'.$domain_name);
        }
    }
    // return fusionpbx_edit_curl('http://156.0.96.60/app/phone_numbers/phone_number_edit.php', $request->id, $request->all());
}

function aftersave_voicemail_curl($request)
{
    return fusionpbx_edit_curl('http://156.0.96.60/app/voicemails/voicemail_edit.php', $request->voicemail_uuid, $request->all());
}

function aftersave_recordings_curl($request)
{
    try {
        $recording_uuid = $request->id;
        $recording = \DB::connection('pbx')->table('v_recordings')
        ->select('v_recordings.*', 'v_domains.domain_name', 'v_domains.account_id')
        ->join('v_domains', 'v_domains.domain_uuid', '=', 'v_recordings.domain_uuid')
        ->where('recording_uuid', $recording_uuid)
        ->get()->first();

        if (!empty($request->id) && empty($request->recording_uuid)) {
            $request->request->add(['recording_uuid' => $request->id]);
        }
        $recording_name = $recording->recording_name;
        if (empty($recording_name) && !empty($request->recording_name)) {
            $recording_name = $request->recording_name;
        }
        $recordings_folder = '/var/lib/freeswitch/recordings/'.$recording->domain_name;
        $cmd = 'mkdir -p '.$recordings_folder.' && chmod 777 '.$recordings_folder;
        $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

        if (!$request->use_text_to_speech) {
            aa($request->all());
            aa($recording);
            aa($recording_uuid);
            $file = public_path().'/uploads/telecloud/1991/'.$recording->recording_filename;
            $recording_filename = $recording->recording_filename;
            if (!str_contains($recording->recording_filename, $recording->account_id)) {
                \DB::connection('pbx')->table('v_recordings')->where('recording_uuid', $recording_uuid)->update(['recording_filename' => $recording->account_id.$recording->recording_filename]);

                $upfile = public_path().'/uploads/telecloud/1991/'.$recording->recording_filename;
                $file = public_path().'/uploads/telecloud/1991/'.$recording->account_id.$recording->recording_filename;
                if (!file_exists($file) && file_exists($upfile)) {
                    File::move($upfile, $file);
                }
                $recording_filename = $recording->account_id.$recording->recording_filename;
            }

            $pbx_path = $recordings_folder.'/'.$recording_filename;
            copy_file_to_pbx($file, $pbx_path);
        } else {
            // aa($recording_uuid);
            // aa($request->use_text_to_speech);
            \DB::connection('pbx')->table('v_recordings')->where('recording_uuid', $recording_uuid)->update(['recording_filename' => str_replace(' ', '', $recording_name).'.mp3']);

            $result = fusionpbx_edit_curl('http://156.0.96.60/app/recordings/recording_edit.php', $recording_uuid, $request->all());
            if ($result) {
                return $result;
            }
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        if (str_contains($ex->getMessage(), 'Could not resolve host')) {
            \DB::connection('pbx')->table('v_recordings')->where('recording_uuid', $recording_uuid)->delete();
            return 'Pbx domain name not active. DNS busy propagating';
        }

        return $ex->getMessage();
    }
}

function copy_file_to_pbx($source_path, $destination_path)
{
    if (file_exists($source_path)) {
        $ssh = new \phpseclib\Net\SSH2('pbx.cloudtools.co.za');
        if ($ssh->login('root', 'Ahmed777')) {
            $scp = new \phpseclib\Net\SCP($ssh);

            $result = $scp->put($destination_path, $source_path, $scp->SOURCE_LOCAL_FILE);

            if ($result) {
                $cmd = 'chown freeswitch:daemon '.$destination_path.' && chmod 777 '.$destination_path;
                $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
            }
        }
    }
}
