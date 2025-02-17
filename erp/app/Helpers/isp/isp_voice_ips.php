<?php

function schedule_blocked_ips_update()
{
    $cmd = "sudo iptables -L -n | awk '$1==\"REJECT\" && $4!=\"0.0.0.0/0\" {print $4}'";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    \DB::connection('pbx')->table('p_blocked_ips')->truncate();
    $blocked_ips_arr = explode(PHP_EOL, $result);
    $blocked_ips = collect($blocked_ips_arr)->filter()->unique()->toArray();
    foreach ($blocked_ips as $ip) {
        $domain_name = '';
        $domain_uuid = \DB::connection('pbx')->table('v_extensions')->where('network_ip', $ip)->pluck('domain_uuid')->first();
        if ($domain_uuid) {
            $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();
        }
        \DB::connection('pbx')->table('p_blocked_ips')->insert(['ip_addr' => $ip, 'domain_name' => $domain_name]);
    }
}

function button_blocked_ips_update($request)
{
    $cmd = "sudo iptables -L -n | awk '$1==\"REJECT\" && $4!=\"0.0.0.0/0\" {print $4}'";
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    \DB::connection('pbx')->table('p_blocked_ips')->truncate();
    $blocked_ips_arr = explode(PHP_EOL, $result);
    $blocked_ips = collect($blocked_ips_arr)->filter()->unique()->toArray();
    foreach ($blocked_ips as $ip) {
        $domain_name = '';
        $domain_uuid = \DB::connection('pbx')->table('v_extensions')->where('network_ip', $ip)->pluck('domain_uuid')->first();
        if ($domain_uuid) {
            $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();
        }
        \DB::connection('pbx')->table('p_blocked_ips')->insert(['ip_addr' => $ip, 'domain_name' => $domain_name]);
    }

    return json_alert('Done');
}

function button_domains_ips_unblock_ip($request)
{
    $pbx = new \FusionPBX;
    $row = \DB::connection('pbx')->table('p_blocked_ips')->where('id', $request->id)->get()->first();
    $result = $pbx->unblockIP($row->ip_addr);
    \DB::connection('pbx')->table('p_blocked_ips')->where('id', $request->id)->delete();

    return json_alert('IP has been unblocked.');
}

function button_blocked_ips_unblock_ip($request)
{
    $pbx = new \FusionPBX;
    $row = \DB::connection('pbx')->table('p_blocked_ips')->where('id', $request->id)->get()->first();
    $result = $pbx->unblockIP($row->ip_addr);
    \DB::connection('pbx')->table('p_blocked_ips')->where('id', $request->id)->delete();

    return json_alert('IP has been unblocked.');
}
