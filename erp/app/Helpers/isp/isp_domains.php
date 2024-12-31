<?php

function schedule_domains_renew()
{
    $nc = new Namecheap;
    $zacr_balance = zacr_check_registrar_balance();

    if ($zacr_balance < 100) {
        admin_email('Domain Renewals not processed. ZACR balance insufficient.');

        return false;
    }

    $domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->where('to_delete', 0)->get();

    \DB::table('isp_host_websites')->update(['domain_expiry' => null]);
    foreach ($domains as $domain) {
        $domain_name = $domain->domain;

        if (is_local_domain($domain_name)) {
            $expiry_date = zacr_domain_expiry($domain_name);
            if (! $expiry_date || $expiry_date == '1970-01-01') {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => null]);
            } else {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => $expiry_date]);
            }
        } else {
            $expiry_date = namecheap_domain_expiry($domain_name);
            if (! $expiry_date || $expiry_date == '1970-01-01') {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => null]);
            } else {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => $expiry_date]);
            }
        }
    }

    $renewal_date = date('Y-m-d', strtotime('+2 weeks'));
    \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->where('to_delete', 0)->whereNotNull('domain_expiry')->where('domain_expiry', '<', $renewal_date)->update(['renew_error' => '']);
    $cancelled_domains = \DB::table('sub_services')->where('to_cancel', 1)->where('provision_type', 'domain_name')->where('status', '!=', 'Deleted')->pluck('detail')->toArray();

    $domains = \DB::table('isp_host_websites')->whereNotIn('domain', $cancelled_domains)->where('status', '!=', 'Deleted')->where('to_delete', 0)->whereNotNull('domain_expiry')->where('domain_expiry', '<', $renewal_date)->get();
    \DB::table('isp_host_websites')->whereNotIn('domain', $cancelled_domains)->where('status', '!=', 'Deleted')->where('to_delete', 0)->whereNotNull('domain_expiry')->where('domain_expiry', '<', $renewal_date)->update(['domain_status' => 'Expiring']);
    $domains_renew_result = [];
    foreach ($domains as $domain) {
        $domain_name = $domain->domain;

        if (is_local_domain($domain_name)) {
            $tld = get_tld($domain_name);
            $zacr = new Zacr($tld);
            $result = $zacr->domain_renew($domain_name);
            $domains_renew_result[$domain_name] = ($result['succcess']) ? 'Renewed.' : $result['result']['message'];
            $zacr->logout();
            $expiry_date = zacr_domain_expiry($domain_name);
            if ($expiry_date == '1970-01-01') {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => null]);
            } else {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => $expiry_date]);
            }
        } else {
            $result = $nc->renew($domain_name);
            $domains_renew_result[$domain_name] = (isset($result['CommandResponse']['DomainRenewResult']['Renew']) && $result['CommandResponse']['DomainRenewResult']['Renew']) ? 'Renewed.' : 'Error renewing domain.';
            if ($domains_renew_result[$domain_name] == 'Error renewing domain.') {
                admin_email($domain_name.' - Error renewing domain.', $domain_name.' - Error renewing domain.');
            }
            $expiry_date = namecheap_domain_expiry($domain_name);
            if ($expiry_date == '1970-01-01') {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => null]);
            } else {
                \DB::table('isp_host_websites')->where('domain', $domain_name)->update(['domain_expiry' => $expiry_date]);
            }
        }
    }

    \DB::table('isp_host_websites')->whereNotIn('domain', $cancelled_domains)->where('status', '!=', 'Deleted')->where('to_delete', 0)->whereNotNull('domain_expiry')->where('domain_expiry', '<=', $renewal_date)->update(['domain_status' => 'Expiring']);

    // \DB::table('isp_host_websites')->whereNotIn('domain', $cancelled_domains)->where('status', '!=', 'Deleted')->where('to_delete', 0)->whereNotNull('domain_expiry')->where('domain_expiry', '>', $renewal_date)->update(['domain_status' => 'Active']);

    if (count($domains_renew_result) > 0) {
        $msg = 'Domain renew result:<br>';
        foreach ($domains_renew_result as $k => $v) {
            $msg .= $k.': '.$v.'<br>';
            if ($v == 'Domain renewed successfully') {
                continue;
            }
            if ($v == 'Renewed.') {
                continue;
            }

            \DB::table('isp_host_websites')
                ->where('domain', $k)
                ->update(['renew_error' => $v]);
        }
        admin_email('Domains renewed successfully', $msg);
    } else {
        $domains = \DB::table('isp_host_websites')->where('domain', '!=', '96.0.156.in-addr.arpa')->where('status', '!=', 'Deleted')->where('to_delete', 0)->whereNull('domain_expiry')->get();
        foreach ($domains as $domain) {
            \DB::table('isp_host_websites')->where('domain', $domain->domain)->update(['renew_error' => 'Manual renew required for '.$domain->domain]);
            admin_email('Manual renew required for '.$domain->domain);
        }
    }
}

function schedule_update_domain_registrars()
{
    $zacr = new Zacr('co.za');
    $websites = \DB::table('isp_host_websites')->where('domain', 'like', '%co.za')->where('status', '!=', 'Deleted')->get();
    foreach ($websites as $website) {
        $account = dbgetaccount($website->account_id);
        $result = $zacr->domain_update_registrant($website->domain, $account);
    }
}

function button_domains_import_zacr($request)
{
    $data['provider'] = 'ZACR';
    $data['id'] = $request->id;

    return view('__app.button_views.domains_import', $data);
}

function button_domains_import_zacr_org($request)
{
    $data['provider'] = 'ZACR_org';
    $data['id'] = $request->id;

    return view('__app.button_views.domains_import', $data);
}

function button_domains_import_namecheap($request)
{
    $domains = namecheap_get_domains();
    foreach ($domains as $domain) {
        $domain_name = $domain['@attributes']['Name'];
        $expires = date('Y-m-d', strtotime($domain['@attributes']['Expires']));
        $c = \DB::table('isp_host_websites')->where('domain', $domain_name)->count();
        $data = [
            'domain' => $domain_name,
            'provider' => 'namecheap',
            'domain_expiry' => $expires,
            'domain_status' => 'Active',
        ];
        if (! $c) {
            \DB::table('isp_host_websites')->insert($data);
        } else {
            \DB::table('isp_host_websites')->where('domain', $domain_name)->update($data);
        }
    }
}

function button_domains_reconcile($request)
{
    if (! $data['reconciled']) {
        return json_alert('Domains not Reconciled', 'warning');
    }

    return json_alert('Reconciled');
}

function schedule_zacr_balance_check()
{
    $balance = zacr_check_registrar_balance();
    set_admin_setting('zacr_balance', $balance);
    if ($balance < 100) {
        $msg = 'ZACR current balance: R'.$balance;
        zacr_balance_email($msg);
    }
}

function zacr_balance_email($msg)
{
    $data = [];
    $data['internal_function'] = 'zacr_low_balance';
    $data['low_balance'] = $msg;
    // $data['test_debug'] = 1;
    erp_process_notification(1, $data);
}

function schedule_update_tld_pricing()
{
    $namecheap = new Namecheap;
    $namecheap->importPricing();
}

function aftersave_tld_pricing_set_markup()
{
    \DB::connection('default')->statement('UPDATE isp_hosting_tlds SET retail_price = price_zar + ((price_zar/100)* retail_markup)');
    \DB::connection('default')->statement('UPDATE isp_hosting_tlds SET wholesale_price = price_zar + ((price_zar/100)* wholesale_markup)');
}

function schedule_domains_lock()
{
    $locked_domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->where('locked', 1)->pluck('domain')->toArray();
    $domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->get();
    foreach ($domains as $domain) {
        if ($domain->provider == 'zacr') {
            $domain_info = zacr_domain_info($domain->domain);
            $locked = 0;
            if ($domain_info['data']['domain:infData']['domain:status']['@attributes']['s'] == 'clientHold') {
                $locked = 1;
            }
            \DB::table('isp_host_websites')->where('id', $domain->id)->update(['locked' => $locked]);
        }
        if ($domain->provider == 'namecheap') {
            $domain_info = namecheap_get_lock_status($domain->domain);

            $locked = 0;
            if ($domain_info == 'true') {
                $locked = 1;
            }
            \DB::table('isp_host_websites')->where('id', $domain->id)->update(['locked' => $locked]);
        }
    }

    $updated_locked_domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->where('locked', 1)->pluck('domain')->toArray();
    if (count($updated_locked_domains) > count($locked_domains)) {
        $domain_list = '';
        foreach ($updated_locked_domains as $d) {
            if (! in_array($d, $locked_domains)) {
                $domain_list .= $d.PHP_EOL;
            }
        }

        $data['domain_list'] = $domain_list;
        $data['function_name'] = __FUNCTION__;
        erp_process_notification(1, $data);
    }
}

function button_hosting_details_lock_domain($request)
{
    $domain = \DB::table('isp_host_websites')->where('id', $request->id)->get()->first();
    if ($domain->provider == 'zacr') {
        $result = zacr_domain_lock($domain->domain);
        \DB::table('isp_host_websites')->where('id', $domain->id)->update(['locked' => 1]);
    }
    if ($domain->provider == 'namecheap') {
        $result = namecheap_set_lock_status($domain->domain, 1);
        \DB::table('isp_host_websites')->where('id', $domain->id)->update(['locked' => 1]);
    }
    module_log(217, $request->id, 'domain locked');

    return json_alert('Domain locked');
}

function button_hosting_details_unlock_domain($request)
{
    $domain = \DB::table('isp_host_websites')->where('id', $request->id)->get()->first();
    if ($domain->provider == 'zacr') {
        $result = zacr_domain_lock($domain->domain, false);
        \DB::table('isp_host_websites')->where('id', $domain->id)->update(['locked' => 0]);
    }

    if ($domain->provider == 'namecheap') {
        $result = namecheap_set_lock_status($domain->domain, 0);
        \DB::table('isp_host_websites')->where('id', $domain->id)->update(['locked' => 0]);
    }
    module_log(217, $request->id, 'domain unlocked');

    return json_alert('Domain unlocked');
}

function get_tld($domain_name)
{
    $pos = strpos($domain_name, '.');
    $length = strlen($domain_name);
    $domain = substr($domain_name, 0, $pos);
    $tld = substr($domain_name, $pos + 1, $length);

    return $tld;
}

function is_local_domain($domain)
{
    $tlds = ['.co.za', '.org.za', '.net.za', '.web.za'];
    foreach ($tlds as $tld) {
        if (str_ends_with($domain, $tld)) {
            return true;
        }
    }

    return false;
}

function valid_tld($domain)
{
    $tld = get_tld($domain);

    $tlds = get_supported_tlds();

    if (in_array($tld, $tlds)) {
        return true;
    }

    return false;
}

function get_supported_tlds()
{
    $register_tlds = \DB::connection('default')->table('isp_hosting_tlds')->where('action', 'register')->where('price_zar', '<', 100)->pluck('tld')->toArray();
    $transfer_tlds = \DB::connection('default')->table('isp_hosting_tlds')->where('action', 'transfer')->where('price_zar', '<', 100)->pluck('tld')->toArray();
    $renew_tlds = \DB::connection('default')->table('isp_hosting_tlds')->where('action', 'renew')->where('price_zar', '<', 100)->pluck('tld')->toArray();
    $allowed_tlds = [];

    foreach ($register_tlds as $tld) {
        if (in_array($tld, $transfer_tlds) && in_array($tld, $renew_tlds)) {
            $allowed_tlds[] = $tld;
        }
    }

    return $allowed_tlds;
}

function schedule_domains_contact_updates()
{
    $domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->get();
    foreach ($domains as $domain) {
        $contact = dbgetaccount($domain->account_id);
        $tld = get_tld($domain->domain);
        if (is_local_domain($domain->domain)) {
            $zacr = new Zacr($tld);
            $result = $zacr->domain_update_contact($domain->domain, $contact);
            $zacr->logout();
        } else {
            namecheap_set_contacts($domain->domain, $domain->account_id);
        }
    }
}

function schedule_set_domain_contact()
{
    $domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->get();
    foreach ($domains as $domain) {
        $contact = dbgetaccount($domain->account_id);
        $tld = get_tld($domain->domain);
        if (is_local_domain($domain->domain)) {
            $zacr = new Zacr($tld);
            $result = $zacr->domain_update_contact($domain->domain, $contact);
            $zacr->logout();
        } else {
            namecheap_set_contacts($domain->domain, $domain->account_id);
        }
    }
}
