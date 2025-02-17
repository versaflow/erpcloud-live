<?php

function opencart_import_customers_2()
{
    // exit;
    \DB::connection('opencart')->table('ocit_d_newsletter_subscriber')->truncate();
    \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')->truncate();
    //exit;
    /// LEADS

    $portal_leads = false;
    $sql = "SELECT * FROM crm_accounts where partner_id=1 and deleted='No'";
    $rows = \DB::select($sql);

    if ($rows && count($rows) > 0) {
        $portal_leads = $rows;
    }

    if ($portal_leads) {
        foreach ($portal_leads as $cc) {
            if ($cc->email>'') {
                $insert_data = array(
                    'firstname' => $cc->company,
                    'lastname' => $cc->contact,
                    'email' => $cc->email,
                    'telephone' => $cc->mobile,
                    'subscribed' => 1,
                    'store_id' => 0,
                    'language_id' => 1,
                    'portal_id' => $cc->id,
                    'portal_level' => 'Lead',
                );

                $subscriber_id = \DB::connection('opencart')->table('ocit_d_newsletter_subscriber')
                    ->insertGetId($insert_data);

                $link_data = array('subscriber_id'=>$subscriber_id,'subscriber_group_id'=>3);
                \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')->insertGetId($link_data);
            }
        }
    }


    /// CUSTOMERS

    $portal_customers = false;
    $sql = "SELECT * FROM crm_accounts WHERE id != 1 and id != 11 and id != 12 and (partner_id=1 or partner_id=11)";
    $rows = \DB::select($sql);

    if ($rows && count($rows) > 0) {
        $portal_customers = $rows;
    }

    if ($portal_customers) {
        foreach ($portal_customers as $cc) {
            if ($cc->email>'') {
                $insert_data = array(
                    'firstname' => $cc->company,
                    'lastname' => $cc->contact,
                    'email' => $cc->email,
                    'telephone' => $cc->mobile,
                    'subscribed' => 1,
                    'store_id' => 0,
                    'language_id' => 1,
                    'portal_id' => $cc->id,
                    'portal_level' => 'Customer',
                );

                $subscriber_id = \DB::connection('opencart')->table('ocit_d_newsletter_subscriber')
                    ->insertGetId($insert_data);

                $link_data = array('subscriber_id'=>$subscriber_id,'subscriber_group_id'=>4);
                \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')
                    ->insertGetId($link_data);
                $link_data = array('subscriber_id'=>$subscriber_id,'subscriber_group_id'=>8);
                \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')
                    ->insertGetId($link_data);
            }
        }
    }

    /// RESELLERS

    $portal_partners = false;
    $sql = "SELECT * FROM crm_accounts WHERE id != 1 and id != 11 and id != 12";
    $rows = \DB::select($sql);

    if ($rows && count($rows) > 0) {
        $portal_partners = $rows;
    }

    if ($portal_partners) {
        foreach ($portal_partners as $cc) {
            if ($cc->email>'') {
                $insert_data = array(
                    'firstname' => $cc->company,
                    'lastname' => $cc->contact,
                    'email' => $cc->email,
                    'telephone' => $cc->mobile,
                    'subscribed' => 1,
                    'store_id' => 0,
                    'language_id' => 1,
                    'portal_id' => $cc->id,
                    'portal_level' => 'Partner',
                );

                $subscriber_id = \DB::connection('opencart')->table('ocit_d_newsletter_subscriber')
                    ->insertGetId($insert_data);

                $link_data = array('subscriber_id'=>$subscriber_id,'subscriber_group_id'=>5);
                \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')
                    ->insertGetId($link_data);
                $link_data = array('subscriber_id'=>$subscriber_id,'subscriber_group_id'=>8);
                \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')
                    ->insertGetId($link_data);
            }
        }
    }

    /// PABX CUSTOMERS - UNLIMTED PBX CUSTOMERS

    $pabx_customers = false;
    $sql = "SELECT * FROM crm_accounts WHERE (pabx_type='PBX' or pabx_type='UnlimitedCallsPBX') and id != 1 and id != 11 and id != 12 and (partner_id=1 or partner_id=11)";
    $rows = \DB::select($sql);

    if ($rows && count($rows) > 0) {
        $pabx_customers = $rows;
    }

    if ($pabx_customers) {
        foreach ($pabx_customers as $cc) {
            $group = ($cc->pabx_type=='PBX') ? '6' : '7';
            $where = array('portal_id'=>$cc->id,'portal_level'=>'Customer');
            $subscriber_id = \DB::connection('opencart')->table('ocit_d_newsletter_subscriber')->where($where)->pluck('subscriber_id')->first();

            $link_data = array('subscriber_id'=>$subscriber_id,'subscriber_group_id'=>$group);
            \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')
                ->insertGetId($link_data);
        }
    }

    /// HOSTING CUSTOMERS

    $hosting_customers = false;
    $rows = DB::table('crm_accounts')
        ->join('isp_host_websites', 'crm_accounts.id', '=', 'isp_host_websites.account_id')
        ->select('crm_accounts.*')
        ->where('partner_id', 1)
        ->groupBy('isp_host_websites.account_id')
        ->get();


    if ($rows && count($rows) > 0) {
        $hosting_customers = $rows;
    }

    if ($hosting_customers) {
        foreach ($hosting_customers as $cc) {
            $group = 9;
            $where = array('portal_id'=>$cc->id,'portal_level'=>'Customer');
            $subscriber_id = \DB::connection('opencart')->table('ocit_d_newsletter_subscriber')->where($where)->pluck('subscriber_id')->first();

            $link_data = array('subscriber_id'=>$subscriber_id,'subscriber_group_id'=>$group);
            \DB::connection('opencart')->table('ocit_d_newsletter_subscriber_to_subscriber_group')
                ->insertGetId($link_data);
        }
    }
}

// function oc_test(){

// }
