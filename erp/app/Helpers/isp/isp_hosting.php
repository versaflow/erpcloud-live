<?php

function schedule_hosting_backups()
{
    $ix = new Interworx;
    $ix->deleteFailedBackups();
    $accounts = $ix->listAccounts();
    foreach ($accounts['payload'] as $siteworx) {
        $domain = $siteworx->domain;

        $ix->setDomain($domain);
        $input = [
            'type' => 'full',
            'location' => 'siteworx',
        ];
        $ix->createBackup($input);
        $account_id = \DB::table('isp_host_websites')->where('domain', $domain)->pluck('account_id')->first();
        $data['domain'] = $domain;
        $data['function_name'] = __FUNCTION__;
        $data['instructions'] = 'Login to your hosting control panel and access your backups from <br> Backups -> Management -> Click file name to download';
        erp_process_notification($account_id, $data, $function_variables);
    }
}

function hosting_set_domain_costs()
{
    $domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->pluck('domain')->toArray();
    $tld_list = [];
    foreach ($domains as $domain) {

        $tld_from_input = get_tld($domain->domain);
        if (! in_array($tld_from_input, $tld_list)) {
            $tld_list[] = $tld_from_input;
        }
    }
    foreach ($tld_list as $tld) {
        $tld_pricing = \DB::table('isp_hosting_tlds')->where('action', 'renew')->where('tld', $tld)->get()->first();

        \DB::table('isp_host_websites')->where('domain', 'like', $tld.'%')->update(['cost_ex' => $tld_pricing->price_zar]);
    }
}

function button_hosting_panel($request)
{

    $domain = \DB::table('isp_host_websites')->where('id', $request->id)->get()->first();

    return redirect()->to('hosting_login/'.$domain->account_id.'/'.$domain->id);
}

function button_hosting_panel_email($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    $domain = $sub->detail;

    $site = \DB::table('isp_host_websites')->where('domain', $domain)->get()->first();
    panel_to_siteworx($site->account_id, $site->domain, $site->package);
    $password = substr(\Erp::encode($domain), 0, 20);

    $url = 'https://host2.cloudtools.co.za:2443/siteworx/index?action=login&email='.$site->username.'&password='.$password.'&domain='.$domain;

    $data = [];

    $data['internal_function'] = 'hosting_panel_login';
    $data['hosting_url'] = 'siteworx_register://host2.cloudtools.co.za:2443/siteworx/';
    $data['hosting_login_url'] = $url;
    $data['username'] = $site->username;
    $data['password'] = $password;
    // $data['test_debug'] = 1;

    erp_process_notification($site->account_id, $data);

    return json_alert('Email sent');
}

function button_get_registrant_info($request)
{
    $website = \DB::table('isp_host_websites')->where('id', $request->id)->get()->first();

    if (! str_contains($website->domain, 'co.za')) {
        return json_alert('Lookup is only for co.za domains', 'warning');
    } else {
        $z = new Zacr('co.za');
        $r = $z->contact_info($website->account_id);
    }
}

function schedule_zacr_poll_truncate()
{
    \DB::table('isp_host_zacr')->truncate();
}

function button_zacr_poll_truncate($request)
{
    \DB::table('isp_host_zacr')->truncate();

    return json_alert('Done');
}

function button_zacr_poll_update($request)
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

                if ($zacr->poll_message_acknowledge($msg_id)) {
                    dbset('isp_host_zacr', 'id', $msg_id, ['acknowledged' => 1]);
                }

                $zacr->logout();
                if ($poll_count > 0) {
                    zacr_process_poll();
                }
            }
        }
    }

    return json_alert('Done');
}

function onload_hosting_details()
{
    $active_sitebuilder_sites = \DB::connection('default')->table('sub_services')->where('provision_type', 'sitebuilder')->where('status', '!=', 'Deleted')->pluck('detail')->toArray();
    $active_sites = \DB::connection('default')->table('sub_services')->where('provision_type', 'hosting')->where('status', '!=', 'Deleted')->pluck('detail')->toArray();

    // \DB::connection('default')->table('isp_host_websites')->update(['status' => 'Deleted']);
    \DB::connection('default')->table('isp_host_websites')->whereIn('domain', $active_sitebuilder_sites)->update(['status' => 'Enabled']);
    \DB::connection('default')->table('isp_host_websites')->whereIn('domain', $active_sites)->update(['status' => 'Enabled']);
}

function domain_available($domain)
{
    try {
        $tld = get_tld($domain);

        if ($tld == 'co.za') {
            // Creating default configured client
            $whois = Iodev\Whois\Factory::get()->createWhois();

            // Or define the same via assoc way
            $customServer = Iodev\Whois\Modules\Tld\TldServer::fromData([
                'zone' => '.co.za',
                'host' => 'coza-whois.registry.net.za',
            ]);

            // Add custom server to existing whois instance
            $whois->getTldModule()->addServers([$customServer]);
            // Checking availability
            if (! $whois->isDomainAvailable($domain)) {
                return 'No';
            }

            return 'Yes';
        } else {
            $namecheap = new Namecheap;
            $result = $namecheap->check($domain);

            if (! empty($result)) {
                $result = $namecheap->parseXml($result);

                if (! empty($result) && ! empty($result['CommandResponse']['DomainCheckResult']['@attributes']['Available'])) {
                    if ($result['CommandResponse']['DomainCheckResult']['@attributes']['IsPremiumName'] == 'true') {
                        return 'Premium';
                    } elseif ($result['CommandResponse']['DomainCheckResult']['@attributes']['Available'] == 'true') {
                        return 'Yes';
                    } else {
                        return 'No';
                    }
                }
            }
        }
    } catch (\Exception $ex) {
        debug_email('Domain lookup failed - '.$ex->getMessage());

        return 'Error';
    }
}

function get_whois_servers()
{
    $whoisservers = [
        'ac' => 'whois.nic.ac', // Ascension Island
        // ad - Andorra - no whois server assigned
        'ae' => 'whois.nic.ae', // United Arab Emirates
        'aero' => 'whois.aero',
        'af' => 'whois.nic.af', // Afghanistan
        'ag' => 'whois.nic.ag', // Antigua And Barbuda
        'ai' => 'whois.ai', // Anguilla
        'al' => 'whois.ripe.net', // Albania
        'am' => 'whois.amnic.net',  // Armenia
        // an - Netherlands Antilles - no whois server assigned
        // ao - Angola - no whois server assigned
        // aq - Antarctica (New Zealand) - no whois server assigned
        // ar - Argentina - no whois server assigned
        'arpa' => 'whois.iana.org',
        'as' => 'whois.nic.as', // American Samoa
        'asia' => 'whois.nic.asia',
        'at' => 'whois.nic.at', // Austria
        'au' => 'whois.aunic.net', // Australia
        // aw - Aruba - no whois server assigned
        'ax' => 'whois.ax', // Aland Islands
        'az' => 'whois.ripe.net', // Azerbaijan
        // ba - Bosnia And Herzegovina - no whois server assigned
        // bb - Barbados - no whois server assigned
        // bd - Bangladesh - no whois server assigned
        'be' => 'whois.dns.be', // Belgium
        'bg' => 'whois.register.bg', // Bulgaria
        'bi' => 'whois.nic.bi', // Burundi
        'biz' => 'whois.biz',
        'bj' => 'whois.nic.bj', // Benin
        // bm - Bermuda - no whois server assigned
        'bn' => 'whois.bn', // Brunei Darussalam
        'bo' => 'whois.nic.bo', // Bolivia
        'br' => 'whois.registro.br', // Brazil
        'bt' => 'whois.netnames.net', // Bhutan
        // bv - Bouvet Island (Norway) - no whois server assigned
        // bw - Botswana - no whois server assigned
        'by' => 'whois.cctld.by', // Belarus
        'bz' => 'whois.belizenic.bz', // Belize
        'ca' => 'whois.cira.ca', // Canada
        'cat' => 'whois.cat', // Spain
        'cc' => 'whois.nic.cc', // Cocos (Keeling) Islands
        'cd' => 'whois.nic.cd', // Congo, The Democratic Republic Of The
        // cf - Central African Republic - no whois server assigned
        'ch' => 'whois.nic.ch', // Switzerland
        'ci' => 'whois.nic.ci', // Cote d'Ivoire
        'ck' => 'whois.nic.ck', // Cook Islands
        'cl' => 'whois.nic.cl', // Chile
        // cm - Cameroon - no whois server assigned
        'cn' => 'whois.cnnic.net.cn', // China
        'co' => 'whois.nic.co', // Colombia
        'co.za' => 'whois.registry.net.za',
        'com' => 'whois.verisign-grs.com',
        'coop' => 'whois.nic.coop',
        // cr - Costa Rica - no whois server assigned
        // cu - Cuba - no whois server assigned
        // cv - Cape Verde - no whois server assigned
        // cw - Curacao - no whois server assigned
        'cx' => 'whois.nic.cx', // Christmas Island
        // cy - Cyprus - no whois server assigned
        'cz' => 'whois.nic.cz', // Czech Republic
        'de' => 'whois.denic.de', // Germany
        // dj - Djibouti - no whois server assigned
        'dk' => 'whois.dk-hostmaster.dk', // Denmark
        'dm' => 'whois.nic.dm', // Dominica
        // do - Dominican Republic - no whois server assigned
        'dz' => 'whois.nic.dz', // Algeria
        'ec' => 'whois.nic.ec', // Ecuador
        'edu' => 'whois.educause.edu',
        'ee' => 'whois.eenet.ee', // Estonia
        'eg' => 'whois.ripe.net', // Egypt
        // er - Eritrea - no whois server assigned
        'es' => 'whois.nic.es', // Spain
        // et - Ethiopia - no whois server assigned
        'eu' => 'whois.eu',
        'fi' => 'whois.ficora.fi', // Finland
        // fj - Fiji - no whois server assigned
        // fk - Falkland Islands - no whois server assigned
        // fm - Micronesia, Federated States Of - no whois server assigned
        'fo' => 'whois.nic.fo', // Faroe Islands
        'fr' => 'whois.nic.fr', // France
        // ga - Gabon - no whois server assigned
        'gd' => 'whois.nic.gd', // Grenada
        // ge - Georgia - no whois server assigned
        // gf - French Guiana - no whois server assigned
        'gg' => 'whois.gg', // Guernsey
        // gh - Ghana - no whois server assigned
        'gi' => 'whois2.afilias-grs.net', // Gibraltar
        'gl' => 'whois.nic.gl', // Greenland (Denmark)
        // gm - Gambia - no whois server assigned
        // gn - Guinea - no whois server assigned
        'gov' => 'whois.nic.gov',
        // gr - Greece - no whois server assigned
        // gt - Guatemala - no whois server assigned
        'gs' => 'whois.nic.gs', // South Georgia And The South Sandwich Islands
        // gu - Guam - no whois server assigned
        // gw - Guinea-bissau - no whois server assigned
        'gy' => 'whois.registry.gy', // Guyana
        'hk' => 'whois.hkirc.hk', // Hong Kong
        // hm - Heard and McDonald Islands (Australia) - no whois server assigned
        'hn' => 'whois.nic.hn', // Honduras
        'hr' => 'whois.dns.hr', // Croatia
        'ht' => 'whois.nic.ht', // Haiti
        'hu' => 'whois.nic.hu', // Hungary
        // id - Indonesia - no whois server assigned
        'ie' => 'whois.domainregistry.ie', // Ireland
        'il' => 'whois.isoc.org.il', // Israel
        'im' => 'whois.nic.im', // Isle of Man
        'in' => 'whois.inregistry.net', // India
        'info' => 'whois.afilias.net',
        'int' => 'whois.iana.org',
        'io' => 'whois.nic.io', // British Indian Ocean Territory
        'iq' => 'whois.cmc.iq', // Iraq
        'ir' => 'whois.nic.ir', // Iran, Islamic Republic Of
        'is' => 'whois.isnic.is', // Iceland
        'it' => 'whois.nic.it', // Italy
        'je' => 'whois.je', // Jersey
        // jm - Jamaica - no whois server assigned
        // jo - Jordan - no whois server assigned
        'jobs' => 'jobswhois.verisign-grs.com',
        'jp' => 'whois.jprs.jp', // Japan
        'ke' => 'whois.kenic.or.ke', // Kenya
        'kg' => 'www.domain.kg', // Kyrgyzstan
        // kh - Cambodia - no whois server assigned
        'ki' => 'whois.nic.ki', // Kiribati
        // km - Comoros - no whois server assigned
        // kn - Saint Kitts And Nevis - no whois server assigned
        // kp - Korea, Democratic People's Republic Of - no whois server assigned
        'kr' => 'whois.kr', // Korea, Republic Of
        // kw - Kuwait - no whois server assigned
        // ky - Cayman Islands - no whois server assigned
        'kz' => 'whois.nic.kz', // Kazakhstan
        'la' => 'whois.nic.la', // Lao People's Democratic Republic
        // lb - Lebanon - no whois server assigned
        // lc - Saint Lucia - no whois server assigned
        'li' => 'whois.nic.li', // Liechtenstein
        // lk - Sri Lanka - no whois server assigned
        'lt' => 'whois.domreg.lt', // Lithuania
        'lu' => 'whois.dns.lu', // Luxembourg
        'lv' => 'whois.nic.lv', // Latvia
        'ly' => 'whois.nic.ly', // Libya
        'ma' => 'whois.iam.net.ma', // Morocco
        // mc - Monaco - no whois server assigned
        'md' => 'whois.nic.md', // Moldova
        'me' => 'whois.nic.me', // Montenegro
        'mg' => 'whois.nic.mg', // Madagascar
        // mh - Marshall Islands - no whois server assigned
        'mil' => 'whois.nic.mil',
        // mk - Macedonia, The Former Yugoslav Republic Of - no whois server assigned
        'ml' => 'whois.dot.ml', // Mali
        // mm - Myanmar - no whois server assigned
        'mn' => 'whois.nic.mn', // Mongolia
        'mo' => 'whois.monic.mo', // Macao
        'mobi' => 'whois.dotmobiregistry.net',
        'mp' => 'whois.nic.mp', // Northern Mariana Islands
        // mq - Martinique (France) - no whois server assigned
        // mr - Mauritania - no whois server assigned
        'ms' => 'whois.nic.ms', // Montserrat
        // mt - Malta - no whois server assigned
        'mu' => 'whois.nic.mu', // Mauritius
        'museum' => 'whois.museum',
        // mv - Maldives - no whois server assigned
        // mw - Malawi - no whois server assigned
        'mx' => 'whois.mx', // Mexico
        'my' => 'whois.domainregistry.my', // Malaysia
        // mz - Mozambique - no whois server assigned
        'na' => 'whois.na-nic.com.na', // Namibia
        'name' => 'whois.nic.name',
        'nc' => 'whois.nc', // New Caledonia
        // ne - Niger - no whois server assigned
        'net' => 'whois.verisign-grs.net',
        'nf' => 'whois.nic.nf', // Norfolk Island
        'ng' => 'whois.nic.net.ng', // Nigeria
        // ni - Nicaragua - no whois server assigned
        'nl' => 'whois.domain-registry.nl', // Netherlands
        'no' => 'whois.norid.no', // Norway
        // np - Nepal - no whois server assigned
        // nr - Nauru - no whois server assigned
        'nu' => 'whois.nic.nu', // Niue
        'nz' => 'whois.srs.net.nz', // New Zealand
        'om' => 'whois.registry.om', // Oman
        'org' => 'whois.pir.org',
        'org.za' => 'org-whois.registry.net.za',
        // pa - Panama - no whois server assigned
        'pe' => 'kero.yachay.pe', // Peru
        'pf' => 'whois.registry.pf', // French Polynesia
        // pg - Papua New Guinea - no whois server assigned
        // ph - Philippines - no whois server assigned
        // pk - Pakistan - no whois server assigned
        'pl' => 'whois.dns.pl', // Poland
        'pm' => 'whois.nic.pm', // Saint Pierre and Miquelon (France)
        // pn - Pitcairn (New Zealand) - no whois server assigned
        'post' => 'whois.dotpostregistry.net',
        'pr' => 'whois.nic.pr', // Puerto Rico
        'pro' => 'whois.dotproregistry.net',
        // ps - Palestine, State of - no whois server assigned
        'pt' => 'whois.dns.pt', // Portugal
        'pw' => 'whois.nic.pw', // Palau
        // py - Paraguay - no whois server assigned
        'qa' => 'whois.registry.qa', // Qatar
        're' => 'whois.nic.re', // Reunion (France)
        'ro' => 'whois.rotld.ro', // Romania
        'rs' => 'whois.rnids.rs', // Serbia
        'ru' => 'whois.tcinet.ru', // Russian Federation
        // rw - Rwanda - no whois server assigned
        'sa' => 'whois.nic.net.sa', // Saudi Arabia
        'sb' => 'whois.nic.net.sb', // Solomon Islands
        'sc' => 'whois2.afilias-grs.net', // Seychelles
        // sd - Sudan - no whois server assigned
        'se' => 'whois.iis.se', // Sweden
        'sg' => 'whois.sgnic.sg', // Singapore
        'sh' => 'whois.nic.sh', // Saint Helena
        'si' => 'whois.arnes.si', // Slovenia
        'sk' => 'whois.sk-nic.sk', // Slovakia
        // sl - Sierra Leone - no whois server assigned
        'sm' => 'whois.nic.sm', // San Marino
        'sn' => 'whois.nic.sn', // Senegal
        'so' => 'whois.nic.so', // Somalia
        // sr - Suriname - no whois server assigned
        'st' => 'whois.nic.st', // Sao Tome And Principe
        'su' => 'whois.tcinet.ru', // Russian Federation
        // sv - El Salvador - no whois server assigned
        'sx' => 'whois.sx', // Sint Maarten (dutch Part)
        'sy' => 'whois.tld.sy', // Syrian Arab Republic
        // sz - Swaziland - no whois server assigned
        'tc' => 'whois.meridiantld.net', // Turks And Caicos Islands
        // td - Chad - no whois server assigned
        'tel' => 'whois.nic.tel',
        'tf' => 'whois.nic.tf', // French Southern Territories
        // tg - Togo - no whois server assigned
        'th' => 'whois.thnic.co.th', // Thailand
        'tj' => 'whois.nic.tj', // Tajikistan
        'tk' => 'whois.dot.tk', // Tokelau
        'tl' => 'whois.nic.tl', // Timor-leste
        'tm' => 'whois.nic.tm', // Turkmenistan
        'tn' => 'whois.ati.tn', // Tunisia
        'to' => 'whois.tonic.to', // Tonga
        'tp' => 'whois.nic.tl', // Timor-leste
        'tr' => 'whois.nic.tr', // Turkey
        'travel' => 'whois.nic.travel',
        // tt - Trinidad And Tobago - no whois server assigned
        'tv' => 'tvwhois.verisign-grs.com', // Tuvalu
        'tw' => 'whois.twnic.net.tw', // Taiwan
        'tz' => 'whois.tznic.or.tz', // Tanzania, United Republic Of
        'ua' => 'whois.ua', // Ukraine
        'ug' => 'whois.co.ug', // Uganda
        'uk' => 'whois.nic.uk', // United Kingdom
        'us' => 'whois.nic.us', // United States
        'uy' => 'whois.nic.org.uy', // Uruguay
        'uz' => 'whois.cctld.uz', // Uzbekistan
        // va - Holy See (vatican City State) - no whois server assigned
        'vc' => 'whois2.afilias-grs.net', // Saint Vincent And The Grenadines
        've' => 'whois.nic.ve', // Venezuela
        'vg' => 'whois.adamsnames.tc', // Virgin Islands, British
        // vi - Virgin Islands, US - no whois server assigned
        // vn - Viet Nam - no whois server assigned
        // vu - Vanuatu - no whois server assigned
        'wf' => 'whois.nic.wf', // Wallis and Futuna
        'ws' => 'whois.website.ws', // Samoa
        'xxx' => 'whois.nic.xxx',
        // ye - Yemen - no whois server assigned
        'yt' => 'whois.nic.yt', // Mayotte
        'yu' => 'whois.ripe.net'];

    return $whoisservers;
}

function nameserver_update_glue()
{
    // $domain = 'cloudtools.co.za';
    $domain = 'cloudtools.co.za';
    $tld = get_tld($domain);
    $zacr = new Zacr($tld);
    $result = $zacr->nameserver_update_glue($domain);
    $zacr->logout();
}

function nameserver_update_all()
{
    // $domains = \DB::table('isp_host_websites')->where('domain', '!=', 'cloudtools.co.za')->where('status', '!=', 'Deleted')->get();
    $domains = \DB::table('isp_host_websites')->where('domain', 'erpcloud.co.za')->where('status', '!=', 'Deleted')->get();
    foreach ($domains as $domain) {
        $contact = dbgetaccount($domain->account_id);
        $tld = get_tld($domain->domain);
        if (is_local_domain($domain->domain)) {
            $zacr = new Zacr($tld);
            $result = $zacr->nameserver_update($domain->domain);
            $zacr->logout();
            if ($result['message'] == "Domain update action 'PendingUpdate' pending") {
                \DB::table('isp_host_websites')->where('domain', $domain->domain)->update(['to_update_nameservers' => 0]);
            } else {
                // pending update already in progress
            }
        } elseif ($domain->provider == 'namecheap') {
            $contact = dbgetaccount($domain->account_id);
            if ($domain->server == 'host3') {
                $result = namecheap_set_nameservers($domain->domain, 'host3.cloudtools.co.za,host4.cloudtools.co.za');
            } else {
                $result = namecheap_set_nameservers($domain->domain);
            }
            \DB::table('isp_host_websites')->where('domain', $domain->domain)->update(['to_update_nameservers' => 0]);
        }
    }
}

//////////////////////////////////////////////////////////////////////////////

function validate_websites()
{
    $sub = new ErpSubs;
    $subs = \DB::table('sub_services')->where('provision_type', 'hosting')->get();
    $sub_domains = \DB::table('sub_services')->where('provision_type', 'hosting')->pluck('detail')->toArray();

    $unsubscribed_domains = \DB::table('isp_host_websites')->whereNotIn('domain', $sub_domains)->get();
    foreach ($unsubscribed_domains as $domain) {
        $sub->createSubscription($domain->account_id, $domain->product_id, $domain->domain);
    }

    foreach ($subs as $sub) {
        $package = \DB::table('crm_products')->where('id', $sub->product_id)->pluck('provision_package')->first();
        $subscription_data = [
            'account_id' => $sub->account_id,
            'product_id' => $sub->product_id,
            'package' => $package,
            'status' => $sub->status,
        ];
        \DB::table('isp_host_websites')->where('domain', $sub->detail)->update($subscription_data);
    }
    $interworx = new Interworx;
    $interworx->setHosted();
}

function validate_domains()
{
    //$domains = \DB::table('isp_host_websites')->where('last_sync','<',date('Y-m-d',strtotime('- 1 week')))->orwhereNull('last_sync')->get();

    // $domains = \DB::table('isp_host_websites')->where('domain_expiry','0000-00-00')->get();
    // $domains = \DB::table('isp_host_websites')->where('domain','LIKE','%.co.za')->get();
    \DB::table('isp_host_websites')->where('domain_expiry', '<', date('Y-m-d'))->where('domain_status', 'Expiring')->update(['domain_status' => 'Expired']);
    $domains = \DB::table('isp_host_websites')->where('status', '!=', 'Deleted')->get();
    $unlisted_domains = [];
    $data = [];
    foreach ($domains as $domain_row) {
        $status = '';
        $status_comment = '';
        $domain = $domain_row->domain;
        $domain_data = false;

        if (is_local_domain($domain)) {
            $tld = get_tld($domain);
            $zacr = new Zacr($tld);
            $result = $zacr->domain_info($domain);
            $zacr->logout();

            if ($result['code'] == 1000) {
                if ($result['data']['domain:infData']['domain:clID'] == 'cloudtel6r2k8g') {
                    $status = $result['data']['domain:infData']['domain:status'][0]['@attributes']['s'];
                    if (empty($status)) {
                        $status = $result['data']['domain:infData']['domain:status']['@attributes']['s'];
                    }
                    $status_comment = '';
                    foreach ($result['data']['domain:infData']['domain:status'] as $status_info) {
                        if (! empty($status_info['@content'])) {
                            $status_comment = $status_info['@content'];
                        }
                    }
                    $domain_data = [
                        'last_sync' => date('Y-m-d H:i:s'),
                        'domain_expiry' => date('Y-m-d H:i:s', strtotime($result['data']['domain:infData']['domain:exDate'])),
                        'auto_renew' => ($result['extension_data']['cozad:infData']['cozad:autorenew'] == 'true') ? 1 : 0,
                        'provider' => 'zacr',
                        'provider_status_comment' => $status_comment,
                    ];
                }
            }

        } else {
            $result = namecheap_get_info($domain);
            if ($result['CommandResponse']['DomainGetInfoResult']['@attributes']['IsOwner'] == 'true') {
                $domain_data = [
                    'last_sync' => date('Y-m-d H:i:s'),
                    'domain_expiry' => date('Y-m-d H:i:s', strtotime($result['CommandResponse']['DomainGetInfoResult']['DomainDetails']['ExpiredDate'])),
                    'provider' => 'namecheap',
                ];
            }
        }

        if (! $domain_data) {
            $unlisted_domains[] = $domain;
        } else {
            $data[$domain] = $domain_data;
        }
    }

    if (! empty($data)) {
        foreach ($data as $domain => $update) {
            $subscription_status = \DB::table('sub_services')->where('detail', $domain)->pluck('status')->first();
            if (! $subscription_status) {
                $subscription_status = 'Deleted';
            }
            $update['status'] = $subscription_status;

            \DB::table('isp_host_websites')->where('domain', $domain)->update($update);
        }
    }

    if (! empty($unlisted_domains)) {
        foreach ($unlisted_domains as $domain) {
            $subscription_status = \DB::table('sub_services')->where('detail', $domain)->pluck('status')->first();
            if (! $subscription_status) {
                $subscription_status = 'Deleted';
            }
            \DB::table('isp_host_websites')->where('domain', $domain)
                ->update([
                    'last_sync' => date('Y-m-d H:i:s'),
                    'domain_expiry' => null,
                    'auto_renew' => 0,
                    'provider' => 'external',
                    'status' => $subscription_status,
                ]);
        }
    }

    \DB::table('isp_host_websites')->where('domain_expiry', '1970-01-01')->update(['domain_expiry' => null]);
    \DB::table('isp_host_websites')->where('domain_expiry', '>', date('Y-m-d'))->where('status', 'Deleted')->update(['product_id' => 0, 'domain_product_id' => 0, 'account_id' => 0]);

}

function siteworx_delete($domain)
{
    $ix = new Interworx;
    $ix->setDomain($domain);
    $result = $ix->deleteAccount();
    if ($result['status'] == 0) {
        return $result['status'];
    } else {
        return $result['payload'];
    }
}

/////////////////////////////////////////////////////////////

function siteworx_register($domain_name, $package, $account_id, $server = 'host2')
{
    $domain_name = str_replace(['http://', 'https://', 'www.'], '', $domain_name);
    // $package = str_replace('_builder_', '_', $package);
    if (strpos($package, 'monthly') !== false) {
        $package_arr = explode('_', $package);
        $package = $package_arr[0].'_'.$package_arr[1];
    }
    $customer = dbgetaccount($account_id);
    $controller = '/nodeworx/siteworx';
    $action = 'add';
    $uniqname = str_replace('.', '', $domain_name);
    $uniqname = substr($uniqname, 0, 4);
    $uniqname = $uniqname.rand(0, 9);
    $password = generate_strong_password();
    $input = [
        'master_domain' => $domain_name,
        'master_domain_ipv4' => '156.0.96.71',
        'ipaddress' => '156.0.96.71',
        'nickname' => clean($customer->company),
        'uniqname' => $uniqname,
        'email' => $customer->email,
        'password' => substr(\Erp::encode($domain_name), 0, 20),
        'confirm_password' => substr(\Erp::encode($domain_name), 0, 20),
        'packagetemplate' => $package,
    ];

    if ($server == 'host2') {
        $input['ipaddress'] = '156.0.96.72';
        $input['master_domain_ipv4'] = '156.0.96.72';
        // return $input;
        $result = ns2_soap('', $controller, $action, $input);
    } else {
        $result = soap('', $controller, $action, $input);
    }

    if ((isset($result['status']) && $result['status'] == 0) || (isset($result['payload']) && strpos($result['payload'], 'Domain name already exists') > -1)) {
        return true;
    } else {
        return $result;
    }
}

function schedule_interworx_sync()
{
    if (session('instance')->id != 1) {
        return false;
    }
    \DB::table('isp_host_websites')->update(['sitebuilder' => 0]);
    $sitebuilder_domains = \DB::table('sub_services')->where('provision_type', 'sitebuilder')->where('status', '!=', 'Deleted')->pluck('detail')->toArray();

    \DB::table('isp_host_websites')->whereIn('domain', $sitebuilder_domains)->update(['sitebuilder' => 1]);

    $hosting_domains = \DB::table('sub_services')->whereIn('provision_type', ['hosting', 'sitebuilder'])->where('status', '!=', 'Deleted')->pluck('detail')->toArray();
    $hosting_subscriptions = \DB::table('sub_services')->whereIn('provision_type', ['hosting', 'sitebuilder'])->where('status', '!=', 'Deleted')->get();
    \DB::table('isp_host_websites')->whereNotIn('domain', $hosting_domains)->update(['status' => 'Deleted']);
    foreach ($hosting_subscriptions as $sub) {
        \DB::table('isp_host_websites')
            ->where('domain', $sub->detail)
            ->update(['product_id' => $sub->product_id, 'account_id' => $sub->account_id, 'subscription_id' => $sub->id, 'status' => $sub->status]);
    }
    $product_ids = \DB::table('isp_host_websites')->pluck('product_id')->filter()->unique()->toArray();
    foreach ($product_ids as $product_id) {
        $package = \DB::table('crm_products')->where('id', $product_id)->pluck('provision_package')->first();
        \DB::table('isp_host_websites')->where('product_id', $product_id)->update(['package' => $package]);
    }
    validate_domains();
    $interworx = new Interworx;
    $interworx->verifySubscriptions();
    $interworx->verifyAllSSL();
    $domains = $interworx->listAllDomains();
    foreach ($domains as $domain) {
        $interworx->setDomain($domain);
        $interworx->addMailDNS();
    }

    //$interworx->setDebug();
    $interworx->setHosted();
    $interworx->deleteFailedBackups();
    $interworx->rebuildBackupSchedule();
}

function schedule_hosting_status_queue()
{
    // HOSTING
    $domains = (new Interworx)->listAllAccounts();

    if ($domains['payload'] && count($domains['payload']) > 0) {
        $sites = \DB::table('sub_services')->whereIn('provision_type', ['hosting', 'sitebuilder'])->where('status', '!=', 'Deleted')->get();
        if (! empty($sites)) {
            foreach ($sites as $site) {
                $interworx_status = collect($domains['payload'])->where('domain', $site->detail)->pluck('status')->first();
                if ($interworx_status) {
                    $account_status = \DB::table('crm_accounts')->where('id', $site->account_id)->pluck('status')->first();
                    $domain = \DB::table('isp_host_websites')->where('domain', $site->detail)->get()->first();
                    if ($account_status == 'Enabled' && $interworx_status == 'inactive') {
                        $result = (new Interworx)->setServer($domain->server)->unsuspend($domain->domain);
                    }
                    if ($account_status == 'Disabled' && $interworx_status == 'active') {
                        $result = (new Interworx)->setServer($domain->server)->suspend($domain->domain);
                    }
                }
            }
        }
    }
}

function schedule_domains_register_queue()
{
    //dd($zacr_balance);

    $processed = false;
    //   echo 'process_registers<br>';
    $domains = \DB::select('select * from isp_host_websites where to_register=1');

    if (count($domains) == 0) {
        return false;
    }
    $zacr_balance = get_admin_setting('zacr_balance');

    if ($zacr_balance < 100) {
        debug_email('Domain register can not be completed, zacr balance below 100');

        return false;
    }

    foreach ($domains as $domain) {

        $hosted = \DB::select('select hosted from isp_host_websites where domain="'.$domain->domain.'"')[0]->hosted;
        $account_id = $domain->account_id;
        $customer = dbgetaccount($account_id);
        if ($hosted == 0) {
            $result = siteworx_register($domain->domain, $domain->package, $domain->account_id);
            if ($result == 1) {
                \DB::update('update isp_host_websites set hosted = 1, server="host2" where domain="'.$domain->domain.'" ');
            }
        } else {
            if (is_local_domain($domain->domain)) {
                // $domain_info = zacr_domain_check($domain->domain);
                if ($domain->server == 'host3') {
                    $response = zacr_register($domain->domain, $customer, ['host3.cloudtools.co.za', 'host4.cloudtools.co.za']);
                } else {
                    $response = zacr_register($domain->domain, $customer);
                }
                \DB::table('isp_host_websites')->where('domain', $domain->domain)->update(['provider' => 'zacr']);
            } else {
                $domain_info = namecheap_get_info($domain->domain);

                if ($domain_info['CommandResponse']['DomainGetInfoResult']['@attributes']['IsOwner'] == 'true') {
                    $processed = true;
                    dbset('isp_host_websites', 'domain', $domain->domain, ['to_register' => 0]);

                    $account_id = $domain->account_id;
                    $function_variables = get_defined_vars();
                    $data['function_name'] = __FUNCTION__;
                    erp_process_notification($account_id, $data, $function_variables);
                } else {
                    if ($domain->server == 'host3') {
                        $response = namecheap_register($domain->domain, $domain->account_id, 'host3.cloudtools.co.za,host4.cloudtools.co.za');
                    } else {
                        $response = namecheap_register($domain->domain, $domain->account_id);
                    }
                }
                \DB::table('isp_host_websites')->where('domain', $domain->domain)->update(['provider' => 'namecheap']);
            }
            if (! $processed) {
                if (empty($response) || ! is_array($response) || (isset($response['CommandResponse']['DomainCreateResult']['Registered']) && $response['CommandResponse']['DomainCreateResult']['Registered'] == 'false')) {
                    $namecheap_errors = '';
                    if (! empty($response['Errors']['Error'])) {
                        foreach ($response['Errors']['Error'] as $err) {
                            if ($err['@content']) {
                                $namecheap_errors .= $err['@content'].PHP_EOL;
                            }
                        }
                    }
                    debug_email('Namecheap registration error. '.$domain->domain, $namecheap_errors);
                    accounts_email('Namecheap registration error. '.$domain->domain, $namecheap_errors);
                }

                if (is_local_domain($domain->domain) && $response['response']['code'] == '2201') {
                    debug_email('ZACR registration error. '.$domain->domain, $response['response']['msg']);
                } elseif (is_local_domain($domain->domain) && $response['response']['reason'] == 'In Use') {
                    // dbset('isp_host_websites', 'domain', $domain->domain, array('to_register' => 0));
                    $data['internal_function'] = 'domain_register_queue_error';
                    $data['domain'] = $domain->domain;
                    erp_process_notification($account_id, $data);
                } elseif ($result['CommandResponse']['DomainCreateResult']['Registered'] === true || (isset($response['code']) && $response['code'] == 1000) || (isset($response['message']) && strpos($response['message'], 'exists') > -1)) {
                    dbset('isp_host_websites', 'domain', $domain->domain, ['to_register' => 0]);
                    $account_id = $domain->account_id;
                    $function_variables = get_defined_vars();
                    $data['function_name'] = __FUNCTION__;
                    erp_process_notification($account_id, $data, $function_variables);
                }
            }
        }
    }
}

function schedule_domains_transfer_queue()
{
    $domains = \DB::select('select * from isp_host_websites join crm_accounts on isp_host_websites.account_id = crm_accounts.id where transfer_in > 0 or transfer_out > 0');
    if (count($domains) == 0) {

        return false;
    }
    $zacr_balance = get_admin_setting('zacr_balance');

    if ($zacr_balance < 100) {
        debug_email('Domain transfers can not be completed, zacr balance below 100');

        return false;
    }

    //echo 'process_transfers<br>';

    foreach ($domains as $domain) {
        echo $domain->domain.'<BR>';
        $hosted = \DB::table('isp_host_websites')->where('domain', $domain->domain)->pluck('hosted')->first();
        $customer = \DB::table('crm_accounts')->where('id', $domain->account_id)->get()->first();

        if ($hosted == 0) {
            $result = siteworx_register($domain->domain, $domain->package, $domain->account_id);

            if ($result == 1) {
                \DB::update('update isp_host_websites set hosted = 1, server="host2" where domain="'.$domain->domain.'"');
            }
        } else {

            if ($domain->transfer_in > 0 || $domain->transfer_out > 0) {

                if ($domain->transfer_in === 1) {

                    if (is_local_domain($domain->domain)) {
                        $response = zacr_transfer($domain);
                        if (! empty($response['response'])) {
                            if ($domain->transfer_in > 0) {
                                dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_in' => 2]);
                                dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_out' => 0]);
                                zacr_autorenew($domain->domain, 1);
                            } elseif ($domain->transfer_out > 0) {
                                zacr_delete_domain($domain->domain);
                            }

                            $account_id = $domain->account_id;
                            $function_variables = get_defined_vars();
                            $data['function_name'] = __FUNCTION__;
                            erp_process_notification($account_id, $data, $function_variables);
                        }

                    } else {
                        $response = namecheap_transfer($domain->domain, $domain->epp_key);
                        if (empty($response) || ! is_array($response) || (isset($response['CommandResponse']['DomainTransferCreateResult']['@attributes']['Transfer']) && $response['CommandResponse']['DomainTransferCreateResult']['@attributes']['Transfer'] == 'false')) {
                            debug_email('Namecheap transfer error. '.$domain->domain, $response);
                        } else {
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_in' => 2]);
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_id' => $response['CommandResponse']['DomainTransferCreateResult']['@attributes']['TransferID']]);
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status' => $response['CommandResponse']['DomainTransferCreateResult']['@attributes']['StatusID']]);
                        }
                    }
                } elseif ($domain->transfer_in == 2) {
                    if (is_local_domain($domain->domain)) {
                        $response = zacr_transfer($domain);

                        if (! empty($response['response']) && str_contains($response['response'], 'prohibits operation')) {
                            continue;
                        }

                        if ($response['response'] !== true) {

                            $response = zacr_domain_transfer_query($domain->domain);
                            $transfer_status = $response['data']['domain:trnData']['domain:trStatus'];

                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status' => 1]);
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status_message' => $transfer_status]);
                            //debug_email('Domain is still pending/transfer failed. Status: '.$transfer_status);

                            /*
                            Changes in Transfer Query Statuses

                            As a transfer is in progress it may undergo various status changes and indicate the most current status of the transfer.

                            The following status may show in the <domain:trStatus> element on a successful Transfer Query response:

                            1. pending: Indicates that no action has yet been taken, neither a registrar nor a registrant vote has been issued.
                            2. clientApproved: Indicates that the Registrar of Record has approved the transfer of the domain away from them.
                            3. clientRejected: Indicates that the Registrar of Record or the Registrant has rejected the transfer of the domain.

                            Note: Status changes can occurr in certain situations such as the Registrar of Record changing their vote or the Registrant changing their vote.

                            If a Registrant votes "no" then the status will be changed from "pending" to "clientRejected". If the Losing Registrar then votes "yes" , the status will change from "clientRejected" to "clientApproved" unitl the end of the transfer.


                            Last update: 23-02-2023 08:07:42
                            */
                        } else {
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_in' => 0]);
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status' => 0]);
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status_message' => '']);
                        }
                    } else {
                        $response = namecheap_get_transfer_status($domain->transfer_id);
                        $status_id = $response['CommandResponse']['DomainTransferGetStatusResult']['@attributes']['StatusID'];
                        $status_message = $response['CommandResponse']['DomainTransferGetStatusResult']['@attributes']['Status'];

                        dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status' => $status_id]);
                        dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status_message' => $status_message]);
                        if ($status_id == 5) {
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_in' => 0]);
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_out' => 0]);
                            if ($domain->server == 'host3') {
                                $response = namecheap_set_nameservers($domain->domain, 'host3.cloudtools.co.za,host4.cloudtools.co.za');
                            } else {
                                $response = namecheap_set_nameservers($domain->domain);
                            }
                            namecheap_set_contacts($domain->domain, $domain->account_id);
                        } else {
                            dbset('isp_host_websites', 'domain', $domain->domain, ['transfer_status' => $transfer_status]);
                        }
                    }
                }
            }
        }
    }
}

function schedule_domains_delete_queue()
{
    $domains = \DB::table('isp_host_websites')->where('to_delete', 1)->get();

    foreach ($domains as $domain) {
        if ($domain->hosted && $domain->host == 'host2') {
            siteworx_delete($domain->domain);
        }

        if ($domain->provider == 'zacr' || (! $domain->provider && str_ends_with($domain->domain, '.za'))) {
            $response = zacr_delete_domain($domain->domain);

            if ($response['code'] == 2304 && str_contains($response['message'], 'pendingUpdate')) {
                zacr_cancel_update($domain->domain);
            } elseif ($response['code'] == 2201 || $response['code'] == 2303 || ($response['code'] == 1001 && str_contains($response['message'], 'Command completed Successfull'))
            || (($response['code'] == 2304 && str_contains($response['message'], 'PendingManualDeletion')))) {
                \DB::table('isp_host_websites')->where('domain', $domain->domain)->update(['status' => 'Deleted', 'to_delete' => 0, 'hosted' => 0]);
            }
        } else {
            // namecheap does not provide a way to remove autorenew via api.
            $data = [];
            $data['provider'] = $domain->provider;
            $data['domain'] = $domain->domain;
            $data['internal_function'] = 'domain_name_cancel';
            erp_process_notification(1, $data);
            \DB::table('isp_host_websites')->where('domain', $domain->domain)->update(['status' => 'Deleted', 'to_delete' => 0, 'hosted' => 0]);
        }
    }
}

function beforesave_siteworx_sync($request)
{
    $id = (! empty($request->id)) ? $request->id : null;

    panel_to_siteworx($request->account_id, $request->domain, $request->package);
}

function siteworx_ip_add_all($ip_address = '156.0.96.71')
{
    $controller = '/nodeworx/siteworx';
    $action = 'listAccounts';

    $result = soap('', $controller, $action);
    foreach ($result['payload'] as $domain) {
        $domain = $domain->domain;

        $controller = '/nodeworx/siteworx';
        $action = 'addIp';
        $input = [
            'domain' => $domain,
            'ipv4' => $ip_address,
        ];
        $result = soap($domain, $controller, $action, $input);
    }
}

function siteworx_ip_delete_all($ip_address = '156.0.96.31')
{
    // $controller = '/nodeworx/siteworx';
    // $action = 'listAccounts';

    // $result = soap('', $controller, $action);
    // foreach ($result['payload'] as $domain) {
    //     $domain = $domain->domain;

    //     $controller = '/nodeworx/siteworx';
    //     $action = 'removeIp';
    //     $input = [
    //         'domain' => $domain,
    //         'ip' => $ip_address,
    //     ];
    //     $result = soap($domain, $controller, $action, $input);
    // }
}

function button_manage_mailboxes($request)
{
    $sub = \DB::table('sub_services')->where('id', $request->id)->get()->first();
    if ($sub->provision_type != 'sitebuilder' && $sub->provision_type != 'hosting') {
        return json_alert('Mailbox not available for this subscription.', 'error');
    }

    $data['subscription_id'] = $sub->id;
    $data['mailbox_accounts'] = [];
    $data['domain'] = $sub->detail;
    $data['account_id'] = $sub->account_id;

    if ($sub->provision_type == 'sitebuilder' || $sub->provision_type == 'hosting') {
        $ix = new Interworx;
        $emails = $ix->setDomain($sub->detail)->listEmailBoxes();

        if ($emails && $emails['payload'] && is_array($emails['payload']) && count($emails['payload']) > 0) {
            $data['mailbox_accounts'] = collect($emails['payload'])->pluck('username')->toArray();
        }

        $data['api'] = 'interworx';
        $data['mail_list'] = implode(',', $data['mailbox_accounts']);
    }

    return view('__app.button_views.manage_mailboxes', $data);
}
