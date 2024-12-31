<?php

function beforesave_check_whitelabel_domain($request)
{
    if (! empty($request->whitelabel_domain)) {
        $ip = gethostbyname($request->whitelabel_domain);
        if ($ip != '156.0.96.71') {
            return 'Invalid white label domain. You need to create a CNAME record that points to portal.telecloud.co.za';
        }
    }
}

function beforesave_check_afriphone_signup_code($request)
{
    if (! empty($request->afriphone_signup_code)) {
        $exists = \DB::table('crm_account_partner_settings')->where('id', '!=', $request->id)->where('afriphone_signup_code', $request->afriphone_signup_code)->count();
        if ($exists) {
            return 'Unlimited Mobile signup code already in use';
        }
    }
}

function aftersave_create_pointer_domain($request)
{
    /*
    if (!empty($request->whitelabel_domain) && $request->id != 1 && $request->account_id != 1) {
        $iw = new Interworx;
        $pointer_domains = $iw->listWhiteLabelDomains();
        if (!in_array($request->whitelabel_domain, $pointer_domains['payload'])) {
            $result = $iw->addWhiteLabelDomain($request->whitelabel_domain);
        }
    }
    */
}

function schedule_delete_pointer_domains()
{
    return false;
    if (session('instance')->directory == 'telecloud') {
        $instances = \DB::connection('default')->table('erp_instances')->where('installed', 1)->get();
        $valid_hostnames = [];
        foreach ($instances as $instance) {
            $whitelabel_domains = \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('whitelabel_domain', '>', '')->pluck('whitelabel_domain')->toArray();
            foreach ($whitelabel_domains as $w_domain) {
                $ip = gethostbyname($w_domain);

                if ($ip != '156.0.96.72') {
                    \DB::connection($instance->db_connection)->table('crm_account_partner_settings')->where('whitelabel_domain', $w_domain)->update(['whitelabel_domain' => '']);
                }
            }
        }

        $iw = new Interworx;
        $iw->deleteInvalidWhiteLabelDomains();
    }
}
