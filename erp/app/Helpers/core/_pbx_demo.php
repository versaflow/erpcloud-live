<?php

function button_send_pbx_demo_details($request)
{
    try {
        $account_id = $request->id;
        $pass = generate_password();
        \DB::connection('pbx')->table('v_domains')->where('domain_name', 'democustomer.cloudtools.co.za')->update(['balance' =>10]);
        $demo_account = \DB::connection('pbx')->table('v_domains')->where('domain_name', 'democustomer.cloudtools.co.za')->get()->first();
        $demo_extension = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $demo_account->domain_uuid)->where('extension', '101')->get()->first();

        \DB::connection('pbx')->table('v_extensions')->where('id', $demo_extension->id)->update(['password' => $pass]);

        $msg = "Sip Server: ".$demo_account->domain_name;
        $msg .= "<br>Extension: ".$demo_extension->extension;
        $msg .= "<br>Password: ".$pass;

        $data = [
            'details' => $msg,
            'internal_function' => 'demo_pbx_details',
            // 'test_debug' => 1,
        ];

        $pbx = new FusionPBX();
        $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $demo_extension->id)->get()->first();

        $key = 'directory:'.$ext->extension.'@'.$ext->user_context;
        $pbx->portalCmd('portal_aftersave_extension', $key);
        if (!empty($ext->cidr)) {
            $pbx->portalCmd('portal_reloadacl');
        }

        erp_process_notification($account_id, $data);
        return json_alert('Sent');
    } catch (\Throwable $ex) {  exception_log($ex);
        return json_alert($ex->getMessage(), 'error');
    }
}
