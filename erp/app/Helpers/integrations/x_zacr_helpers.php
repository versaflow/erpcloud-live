<?php

function zacr_process_poll()
{
    $tlds = ['co.za', 'org.za', 'net.za', 'web.za'];
    foreach ($tlds as $tld) {
        $zacr = new Zacr($tld);
        $registrar_id = $zacr->get_registrar_id();
        if ($registrar_id == 'cloudtel6r2k8g') {
            $poll = $zacr->poll_check();
            if ($poll['response']['poll_contains_message']) {
                $poll_count = $poll['poll_queue']['@attributes']['count'];
                $msg_id = $poll['poll_queue']['@attributes']['id'];
                $msg = $poll['poll_queue']['epp:msg'];
                $msg_date = date('Y-m-d H:i:s', strtotime($poll['poll_queue']['epp:qDate']));
                $sql = 'insert into isp_host_zacr (id,qdate,msg) VALUES ("'.$msg_id.'","'.$msg_date.'","'.$msg.'")';
                \DB::insert($sql);

                if (isset($poll['data']['domain:trnData']['domain:name'])) {
                    //reID Requesting registrar
                    //acID Acknowledging registrar
                    $transfer_data = $poll['data']['domain:trnData'];
                    $sql = 'update isp_host_zacr set
                    domain_name="'.$transfer_data['domain:name'].'",
                    domain_trstatus="'.$transfer_data['domain:trStatus'].'",
                    domain_reid="'.$transfer_data['domain:reID'].'",
                    domain_redate="'.date('Y-m-d H:i:s', strtotime($transfer_data['domain:reDate'])).'",
                    domain_acid="'.$transfer_data['domain:acID'].'",
                    domain_acdate="'.date('Y-m-d H:i:s', strtotime($transfer_data['domain:acDate'])).'"
                    where id="'.$msg_id.'"';

                    \DB::select($sql);

                    //handle transfer out requests
                    if ($transfer_data['domain:trStatus'] == 'pending' && $transfer_data['domain:acID'] == $registrar_id) {
                        $transfer_out = dbgetcell('isp_host_websites', 'domain', $transfer_data['domain:name'], 'transfer_out');
                        if ($transfer_out == 1) {
                            $zacr->domain_transfer_approve($transfer_data['domain:name']);
                        } else {
                            $zacr->domain_transfer_reject($transfer_data['domain:name']);
                        }
                    }

                    //complete transfer out requests
                    if ($transfer_data['domain:trStatus'] == 'clientApproved' && $transfer_data['domain:acID'] == $registrar_id) {
                        \DB::update('update isp_host_websites set transfer_out=0, to_delete=1 where domain="'.$transfer_data['domain:name'].'"');
                    }
                }

                $res = $zacr->poll_message_acknowledge($msg_id);
                if ($res) {
                    $result = dbset('isp_host_zacr', 'id', $msg_id, ['acknowledged' => 1]);
                }

                $zacr->logout();
                if ($poll_count > 0) {
                    zacr_process_poll();
                }
            }
        }
    }
}

function zacr_domain_info_all()
{
    $domains = \DB::table('isp_host_websites')->get();
    foreach ($domains as $domain) {
        zacr_domain_info($domain->domain);
    }
}

function zacr_domain_info($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_info($domain);
    $zacr->logout();

    return $result;
}

function zacr_domain_transfer_query($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_transfer_query($domain);
    $zacr->logout();

    return $result;
}

function zacr_domain_info_contacts($domain, $account_id)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->contact_info($account_id);
    $zacr->logout();

    return $result;
}

function zacr_domain_expiry($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_info($domain, 1);
    $zacr->logout();

    return $result;
}

function zacr_domain_check($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_check($domain);
    $zacr->logout();

    return $result;
}

function zacr_cancel_action($domain, $action)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_cancel_action($domain, $action);
    $zacr->logout();

    return $result;
}

function zacr_renew($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_renew($domain);
    $zacr->logout();

    return $result;
}

function zacr_domain_lock($domain, $lock = true)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_lock($domain, $lock);
    $zacr->logout();
}

function zacr_autorenew($domain, $status)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_autorenew($domain, $status);
    $zacr->logout();
    if (isset($result['extension_data'])) {
        return $result['extension_data']['cozad:cozaData']['cozad:detail']['@content'];
    } else {
        return $result['message'];
    }
}

/////////////////////////////////////////////////////////////////////////////

function zacr_register($domain, $customer, $nameservers = false)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_check($domain);

    if ($result['code'] == 1000 && $result['response']['available'] == 1) {
        $result = $zacr->domain_create($domain, $customer, $nameservers);
    }
    $zacr->logout();

    return $result;
}

///////////////////////////////////////////////////////////////////////////

function zacr_clear_poll_log()
{
    $sql = 'delete from isp_host_zacr';
    \DB::delete($sql);
}

function zacr_nameserver_update_flexerp($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->nameserver_update_flexerp($domain);

    return $result;
}

function zacr_nameserver_update($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->nameserver_update($domain);
    print_r($result);

    return $result;
}

function zacr_nameserver_update_host3($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);

    $nameservers = ['host3.cloudtools.co.za', 'host4.cloudtools.co.za'];
    $result = $zacr->nameserver_update($domain, $nameservers);

    return $result;
}

function zacr_transfer($domain)
{
    //initiate the transfer
    if ($domain->transfer_in == 1) {
        $tld = get_tld($domain->domain);

        $zacr = new Zacr($tld);

        $registrar_id = $zacr->get_registrar_id();

        $result = $zacr->domain_transfer_request($domain->domain);

        $zacr->logout();

        if ($result['code'] == '2304' || $result['code'] == '2300' || $result['code'] == '2106') {
            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_in' => '2']);
        }
        $data['response'] = $result['message'];

        return $data;
    } elseif ($domain->transfer_out == 2) {
        $tld = get_tld($domain->domain);
        $zacr = new Zacr($tld);
        $domain_info = $zacr->domain_info($domain->domain);
        $registrar = $domain_info['data']['domain:infData']['domain:clID'];
        $zacr->logout();

        if ($registrar == 'cloudtel6r2k8g') {
            $domains = dbset('isp_host_websites', 'domain', $domain->domain, ['to_update' => 1, 'transfer_in' => 0]);
            $data['response'] = true;
        } else {
            return $data['response'] = 'Your registrar is still '.$registrar.'.  Please talk to them.';
        }
    } elseif ($domain->transfer_in == 2) {
        $tld = get_tld($domain->domain);
        $zacr = new Zacr($tld);
        $domain_info = $zacr->domain_info($domain->domain);
        $registrar_id = $zacr->get_registrar_id();
        $domain_data = $domain_info['data']['domain:infData'];

        if ($domain_info['code'] == 1000 && (isset($domain_data['domain:clID']) && $domain_data['domain:clID'] == $registrar_id)) {
            $result = $zacr->nameserver_update($domain->domain);

            $contact = dbgetaccount($domain->account_id);
            $result = $zacr->domain_update_registrant($domain->domain, $contact);
            $zacr->logout();

            $data = [];
            $data['response'] = true;

            return $data;
        } else {

            $data['response'] = false;

            return $data;
        }
    } else {
        return json_encode($result['message']);
    }
}

function zacr_delete_domain($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $response = $zacr->domain_delete($domain);
    $zacr->logout();

    return $response;
}

function zacr_check_registrar_balance()
{
    $zacr = new Zacr('co.za');
    $result = $zacr->get_registrar_balance();
    $zacr->logout();

    $balance = $result['extension_data']['cozac:infData']['cozac:balance'];

    return $balance;
}

function zacr_cancel_update($domain)
{
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->domain_cancel_action($domain, 'PendingUpdate');
    // echo '<PRE>';
    // print_r($result);
    // echo '</PRE>';
    $zacr->logout();
}

function zacr_set_technical_contact()
{
    $r = zacr_domain_info('vehicledb.co.za');
    $zacr = new Zacr('co.za');
    $domains = \DB::table('isp_host_websites')->where('domain', 'vehicledb.co.za')->where('status', '!=', 'Deleted')->where('provider', 'zacr')->get();
    foreach ($domains as $domain) {
        $customer = dbgetaccount($domain->account_id);
        $r = $zacr->domain_update_contact_info($domain->domain, $customer);
    }
}
