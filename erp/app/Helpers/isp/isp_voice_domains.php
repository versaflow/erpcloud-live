<?php

function schedule_pbx_set_suggested_airtime_package(){
    
    $domains = \DB::connection('pbx')->table('v_domains')->where('unlimited_channels',0)->where('account_id','>',0)->get();
    foreach($domains as $domain){
        $three_months_usage = 0;
        $num_months = 0;
        if($domain->created_at < date('Y-m-d',strtotime('-3 months'))){
            $table = 'call_records_outbound_'.strtolower(date("YM", strtotime('-3 months')));
            $three_months_usage += \DB::connection('backup_server')->table($table)->where('domain_name',$domain->domain_name)->sum('duration_mins');
            $num_months++;
        }
        if($domain->created_at < date('Y-m-d',strtotime('-2 months'))){
            $table = 'call_records_outbound_'.strtolower(date("YM", strtotime('-2 months')));
            $three_months_usage += \DB::connection('backup_server')->table($table)->where('domain_name',$domain->domain_name)->sum('duration_mins');
            $num_months++;
        }
        if($domain->created_at < date('Y-m-d',strtotime('-1 month'))){
            $table =  'call_records_outbound_lastmonth';
            $three_months_usage += \DB::connection('pbx_cdr')->table($table)->where('domain_name',$domain->domain_name)->sum('duration_mins');
            $num_months++;
        }
        
        if($num_months > 0){
            $three_months_average = currency($three_months_usage/$num_months);
        }
        
        $current_rand_package = \DB::connection('default')->table('sub_services')
        ->where('account_id',$domain->account_id)
        ->where('provision_type','airtime_contract')
        ->where('status', '!=', 'Deleted')
        ->sum('usage_allocation');
        $current_minutes_package = intval($current_rand_package*2);
        
        $optimal_package = false;
        if($three_months_average < ($current_minutes_package+100)  && $three_months_average > ($current_minutes_package-100)){
            $optimal_package = true;
        }
        if($three_months_average > 0){
            $suggested_minutes_package = ceil($three_months_average/100) * 100;
        }else{
            $suggested_minutes_package = 100;
        }
        $update_data = [
            'three_months_usage' => currency($three_months_usage),
            'three_months_average' => currency($three_months_average),
            'current_package' => $current_minutes_package.' minutes',
            'suggested_package' => $suggested_minutes_package.' minutes',
            'optimal_package' => $optimal_package,
        ];
        \DB::connection('pbx')->table('v_domains')->where('id',$domain->id)
        ->update($update_data);
        
    }
    
    \DB::connection('pbx')->table('v_domains')->where('unlimited_channels','>',0)->update(['current_package' => 'Unlimited','optimal_package' => 1, 'suggested_package' => '']);
    
}


function button_pbx_domain_rename($request)
{
    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('id', $request->id)->pluck('domain_uuid')->first();
    $url = 'http://156.0.96.60/core/domain_settings/domain_edit.php?id='.$domain_uuid.'&key='.session('pbx_api_key');
    return redirect()->to($url);
}

function pbx_add_domain($domain_name, $account_id, $pbx_type = 'Free')
{

    $e = \DB::connection('pbx')->table('v_domains')
    ->where('account_id', $account_id)
    ->count();
    if($e){
        $pbx = new FusionPBX();
        $domain_uuid = \DB::connection('pbx')->table('v_domains')
        ->where('account_id', $account_id)
        ->pluck('domain_uuid')->first();
        $pbx->importDomains($domain_uuid);
        return true;
    }
    // CREATE DOMAIN
    $account = dbgetaccount($account_id);
    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain_name)->pluck('domain_uuid')->first();
    $domain_count = \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain_name)->count();

    if ($domain_count > 0) {
        return 'The domain has already been used, please try again.';
    } else {
        //CREATE DOMAIN WITH DIALPLAN

        $domain_uuid = pbx_uuid('v_domains', 'domain_uuid');

        $domain_data = [
            'account_id' => $account_id,
            'domain_uuid' => $domain_uuid,
            'domain_name' => $domain_name,
            'ratesheet_id' => 1,
            'domain_enabled' => 'true',
            'domain_description' => 'new system',
            'status' => 'Enabled'
        ];
         
        $domain_data['created_at'] = date('Y-m-d H:i:s');
            
        \DB::connection('pbx')->table('v_domains')->insert($domain_data);

        //SET PARTNER
        $reseller = dbgetaccount($account->partner_id);
        \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain_name)->update(['partner_id' => $reseller->id,'partner_company' => $reseller->company]);

        //CREATE USER
        $salt = gen_uuid();
        $user_uuid = pbx_uuid('v_users', 'user_uuid');
        $password = md5($salt.generate_strong_password());

        $api_key = gen_uuid();
        $params = array(
            'user_uuid' => $user_uuid,
            'domain_uuid' => $domain_uuid,
            'username' => 'primary',
            'password' => $password,
            'salt' => $salt,
            'api_key' => $api_key,
            'user_enabled' => 'true',
        );
        \DB::connection('pbx')->table('v_users')->insert($params);

        $ratesheet_id = \DB::connection('pbx')->table('p_rates_partner')->where('is_default', 1)->pluck('id')->first();
        $update_data = [
                'account_id'=>$account->id,
                'partner_id'=>$account->partner_id,
                'ratesheet_id'=>$ratesheet_id,
                'company' => $account->company,
                'pbx_type' => $pbx_type,
        ];
        //if($account->partner_id == 1){
        //    $update_data['is_postpaid'] = 1;
        //    $update_data['postpaid_limit'] = 2000;
        //}
        \DB::connection('pbx')->table('v_domains')
            ->where('domain_uuid', $domain_uuid)
            ->update($update_data);
        $pbx = new FusionPBX();
        $pbx->importDomains($domain_uuid);
    }
    global_dialplan_remove_domain_dialplans();
    update_pbx_group_permissions();
    if(is_dev()){
        pbx_domain_add_aftersave($domain_uuid,$domain_name);
    }
}

function pbx_domain_add_aftersave($domain_uuid,$domain_name){
    $post_data = [
        'domain_uuid' => $domain_uuid,
        'domain_name' => $domain_name,
        'domain_enabled' => 'true',
        'domain_description' => 'Created from erp',
    ];
    fusionpbx_edit_curl('http://156.0.96.60/core/domains/domain_edit.php', $domain_uuid, $post_data, true);
}

function account_has_pbx_subscription($account_id)
{
    $provision_types = \DB::table('sub_services')->select('provision_type')->groupBy('provision_type')->pluck('provision_type')->toArray();
    $pbx_types = [];
    foreach ($provision_types as $p) {
        if (str_contains($p, 'airtime') || str_contains($p, 'sip') || str_contains($p, 'phone_number')  || str_contains($p, 'extension')) {
            $pbx_types[] = $p;
        }
    }
    $count =  \DB::table('sub_services')->whereIn('provision_type', $pbx_types)->where('account_id', $account_id)->where('status', '!=', 'Deleted')->count();
    if ($count) {
        return true;
    }
    return false;
}

function pbx_delete_domain($domain_name, $account_id = '')
{
    $pbx = new FusionPBX();
    $pbx->clearRecordings($domain_name);

    if (!empty($domain_name)) {
        $domain_uuid = \DB::connection('pbx')
            ->table('v_domains')
            ->where('domain_name', $domain_name)
            ->pluck('domain_uuid')->first();
            
        $exts = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->get();
        foreach($exts as $ext){
            $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
            $result = $pbx->portalCmd('portal_aftersave_extension', $key);
        }
        $root_api_key = 'e2e4e9a0-c678-45a2-97a2-e24f9f2481fa';
        
		$token = \Erp::encode('cloudpbx'.date('Ymd'));
        $url = 'http://156.0.96.60/core/domains/domain_delete.php?action=delete&key='.$root_api_key.'&token='.$token.'&domain_uuid='.$domain_uuid;
        $status = curlPost($url, null, false);
    }

    if (!empty($account_id)) {
        \DB::table('isp_voice_pbx_domains')->where('account_id', $account_id)->delete();
    }
    $ix = new Interworx();
    $ix->deletePbxDnsRecord($domain_name);
}

function pabx_domain_name_exists($domain_name)
{
    $exists = \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain_name)->count();
    return $exists;
}

function pbx_create_domain_company_name($customer)
{
    try {
        $pbx_domain = strtolower($customer->company);
        $domain_portal = (1 == $customer->partner_id) ? 'cloudtelecoms.co.za' : 'cloudtools.co.za';
        $domain_portal = 'cloudtools.co.za';
        $pbx_domain_arr = preg_split('/\s+/', $pbx_domain);

        $pbx_domain = $pbx_domain_arr[0];
        if (!empty($pbx_domain_arr[1])) {
            $pbx_domain .= $pbx_domain_arr[1];
        }

        if (strlen($pbx_domain) < 6) {
            if (!empty($pbx_domain_arr[2])) {
                $pbx_domain .= $pbx_domain_arr[2];
            }
        }

        if (strlen($pbx_domain) < 6) {
            if (!empty($pbx_domain_arr[3])) {
                $pbx_domain .= $pbx_domain_arr[3];
            }
        }

        $pbx_domain = preg_replace("/[\W_]+/u", '', $pbx_domain);
        $pbx_domain = substr($pbx_domain, 0, 30);
        $domain_exists = pabx_domain_name_exists($pbx_domain.'.'.$domain_portal);
        while ($domain_exists >= 1) {
            $pbx_domain = $pbx_domain.rand(0, 9);
            $domain_exists = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pbx_domain)->orWhere('domain_name', $pbx_domain.'.'.$domain_portal)->count();
            if (0 == $domain_exists) {
                $domain_exists = pabx_domain_name_exists($pbx_domain.'.'.$domain_portal);
            }
        }
        
        $pbx_domain_prefix = $pbx_domain;

        $ix = new Interworx();
        $result = $ix->addPbxDns($pbx_domain.'.'.$domain_portal);

        return $pbx_domain_prefix.'.'.$domain_portal;
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_email($ex, 'pbx dns create error');

        return false;
    }

    return false;
}

function schedule_pbx_call_stats()
{
    $domains = \DB::connection('pbx')->table('v_domains')->get();
    //$domains = \DB::connection('pbx')->table('v_domains')->where('domain_name','pbx.cloudtools.co.za')->get();
    \DB::connection('pbx')->table('p_call_stats')->where('period', date('Y-m'))->delete();
    foreach ($domains as $domain) {
        $inbound_calls_unique = \DB::connection('pbx_cdr')->table('call_records_inbound')
            ->where('direction', 'inbound')
            ->where('domain_name', $domain->domain_name)
            ->distinct('caller_id_number')
            ->count('caller_id_number');

        $inbound_calls = \DB::connection('pbx_cdr')->table('call_records_inbound')
            ->where('direction', 'inbound')
            ->where('domain_name', $domain->domain_name)
            ->count('caller_id_number');

        $outbound_calls_unique = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('direction', 'outbound')
            ->where('domain_name', $domain->domain_name)
            ->distinct('callee_id_number')
            ->count('callee_id_number');

        $outbound_calls = \DB::connection('pbx_cdr')->table('call_records_outbound')
            ->where('direction', 'outbound')
            ->where('domain_name', $domain->domain_name)
            ->count('callee_id_number');

        $total_calls = $outbound_calls + $inbound_calls;
        if ($total_calls > 0) {
            $inbound_percentage = ($inbound_calls/$total_calls) * 100;
            $outbound_percentage = ($outbound_calls/$total_calls) * 100;
        } else {
            $inbound_percentage = 0;
            $outbound_percentage = 0;
        }
        $data = [
            'domain_uuid' => $domain->domain_uuid,
            'inbound_calls' => $inbound_calls,
            'outbound_calls' => $outbound_calls,
            'inbound_calls_unique' => $inbound_calls_unique,
            'outbound_calls_unique' => $outbound_calls_unique,
            'inbound_percentage' => $inbound_percentage,
            'outbound_percentage' => $outbound_percentage,
            'period' => date('Y-m'),
        ];

        \DB::connection('pbx')->table('p_call_stats')->insert($data);
    }
}

// @todo update to interworx
function schedule_create_pbx_dns()
{
    $dns_domains = get_available_pbx_dns();
    $num_dns_records = count($dns_domains);
    $allocated_dns_records = \DB::connection('pbx')->table('v_domains')->where('domain_name', 'LIKE', 'um%')->count();
    $available_dns_records = $num_dns_records - $allocated_dns_records;
    $created_domains = [];

    if ($available_dns_records < 30) {
        // create pbx dns, always 10 available at anytime
        $num_domains_to_create = 30 - $available_dns_records;

        for ($i=1;$i<=$num_domains_to_create;$i++) {
            $domain_name = get_available_unlimited_mobile_pbx_domain_name($dns_domains, $created_domains);
            $interworx = new Interworx();

            $created_domains[] = $domain_name;
            $result = $interworx->addPbxDns($domain_name);
        }
    }
}

function get_available_unlimited_mobile_pbx_domain_name($dns_domains, $created_domains)
{
    $exists = true;
    $i = 1;


    while ($exists) {
        $domain_name = 'um'.$i.'.cloudtools.co.za';
        if (in_array($domain_name, $dns_domains)) {
            $exists = true;
            $i++;
        } elseif (in_array($domain_name, $created_domains)) {
            $exists = true;
            $i++;
        } else {
            $exists = \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain_name)->whereNotIn('domain_name', $dns_domains)->count();
            $i++;
        }
    }
    return $domain_name;
}

function get_available_pbx_domain_name()
{
    $available_domains = get_available_pbx_dns();
    $allocated_domains = \DB::connection('pbx')->table('v_domains')->where('domain_name', 'LIKE', 'tc%')->pluck('domain_name')->toArray();
    if ($available_domains[0])
        foreach ($available_domains as $available_domain) {
            if (!in_array($available_domain, $allocated_domains)) {
                return $available_domain;
            }
        }
    else
        return false;
}


function get_available_pbx_dns()
{
    $interworx = new Interworx();
    $payload = $interworx->getAutomatedPbxDnsRecordsTC();
    $records = collect($payload)->pluck('host')->toArray();
    return $records;
}


function update_pbx_group_permissions()
{
    $domains = \DB::connection('pbx')->table('v_domains')->get();
    foreach ($domains as $domain) {
        $domain_uuid = $domain->domain_uuid;
        $group_name = $domain->pbx_type;
        $user_uuid = \DB::connection('pbx')->table('v_users')->where('domain_uuid', $domain_uuid)->pluck('user_uuid')->first();
        if (empty($user_uuid)) {

            //CREATE USER
            $salt = gen_uuid();
            $user_uuid = pbx_uuid('v_users', 'user_uuid');
            $password = md5($salt.generate_strong_password());

            $api_key = gen_uuid();
            $params = array(
            'user_uuid' => $user_uuid,
            'domain_uuid' => $domain_uuid,
            'username' => 'primary',
            'password' => $password,
            'salt' => $salt,
            'api_key' => $api_key,
            'user_enabled' => 'true',
            );
            \DB::connection('pbx')->table('v_users')->insert($params);
        }

        $assigned = \DB::connection('pbx')->table('v_user_groups')->where('user_uuid', $user_uuid)->count();
        if (!$assigned) {
            $group_uuid  = \DB::connection('pbx')->table('v_groups')->where('group_name', $group_name)->pluck('group_uuid')->first();
            $user_group_uuid = pbx_uuid('v_user_groups', 'user_group_uuid');
            $params = array(
                'user_group_uuid' => $user_group_uuid,
                'domain_uuid' => $domain_uuid,
                'group_name' => $group_name,
                'group_uuid' => $group_uuid,
                'user_uuid' => $user_uuid,
            );
            \DB::connection('pbx')->table('v_user_groups')->insert($params);
        }
    }
}


function schedule_cloudtools_wildcard_ssl(){
    
    // VirtualHost file needs to be edited to use /etc/cloudtools-ssl/ files
    /*
        <VirtualHost *:443>
        ServerName example.com
        ServerAlias *.example.com
        DocumentRoot /var/www/html
        
        SSLEngine on
        SSLCertificateFile /etc/cloudtools-ssl/cloudtools.co.za.crt
        SSLCertificateKeyFile /etc/cloudtools-ssl/cloudtools.co.za.priv.key
        SSLCertificateChainFile /etc/cloudtools-ssl/cloudtools.co.za.chain.crt
        
        # ... Additional Apache configuration ...
        </VirtualHost>
    */
    // GET SSL FROM CLOUDTOOLS SITEWORX
    
    // /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.priv.key
    // /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.crt
    // /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.csr
    // /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.chain.crt
    $cmd = 'cat /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.priv.key';
    $privkey = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
    $cmd = 'cat /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.crt';
    $crt = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    $cmd = 'cat /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.csr';
    $csr = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
    $cmd = 'cat /home/cloudtoo/var/cloudtools.co.za/ssl/cloudtools.co.za.chain.crt';
    $chaincrt = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
    // COPY SSL TO PBX
    
    $cmd = "echo '".$privkey."' > /etc/cloudtools-ssl/cloudtools.co.za.priv.key";
    $result = Erp::ssh("pbx.cloudtools.co.za", "root", "Ahmed777", $cmd);
   
    $cmd = "echo '".$crt."' > /etc/cloudtools-ssl/cloudtools.co.za.crt";
    Erp::ssh("pbx.cloudtools.co.za", "root", "Ahmed777", $cmd);

    $cmd = "echo '".$csr."' > /etc/cloudtools-ssl/cloudtools.co.za.csr";
    Erp::ssh("pbx.cloudtools.co.za", "root", "Ahmed777", $cmd);
    
    $cmd = "echo '".$chaincrt."' > /etc/cloudtools-ssl/cloudtools.co.za.chain.crt";
    Erp::ssh("pbx.cloudtools.co.za", "root", "Ahmed777", $cmd);
        
}