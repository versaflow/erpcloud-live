<?php

function schedule_monthly_pricelist()
{
    //return false;
    $instance_dir = session('instance')->directory;
    schedule_export_pricing();
    
    // SEND PRICELISTS TO ADMIN RESELLERS
    $partners = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('type', 'reseller')->where('partner_id', 1)->where('currency','ZAR')->get();
    //$partners = \DB::table('crm_accounts')->where('id',1969)->where('status', '!=', 'Deleted')->where('type', 'reseller')->get();
    foreach ($partners as $partner) {
        $data = [];
        $data['internal_function'] = 'send_wholesale_pricelist';
       
        $data['test_debug'] = 1;
        email_queue_add($partner->id, $data);
        
    }
    /*
    // SEND PRICELISTS TO ADMIN CUSTOMERS
    $customers = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('type', 'customer')->where('partner_id', 1)->where('currency','ZAR')->get();
    //$customers = \DB::table('crm_accounts')->where('id',12)->where('status', '!=', 'Deleted')->where('type', 'customer')->where('partner_id', 1)->get();
    foreach ($customers as $customer) {
        $data = [];
        $data['internal_function'] = 'send_customer_pricelist';
        $data['files'] = [];
        $data['files'][] = '/home/erpcloud-live/htdocs/html/uploads/'.$instance_dir.'/pricing_exports/Pricelist_'.$customer->currency.'.pdf';
        if(is_main_instance()){
            $data['files'][] = '/home/erpcloud-live/htdocs/html/uploads/'.$instance_dir.'/pricing_exports/Call_Rates_Popular_'.$customer->currency.'.xlsx';
        }
        //$data['test_debug'] = 1;
        email_queue_add($customer->id, $data);
      
    }
    */
    /*
    $pricelists = \DB::table('crm_pricelists')->where('partner_id','!=', 1)->get();
    $pricelist_files = [];
    foreach ($pricelists as $pricelist) {
        $pricelist_files[$pricelist->id] = export_pricelist($pricelist->id);
    }
    
    $partner_ids = \DB::table('crm_account_partner_settings')->where('send_wholesale_pricelist_monthly', 1)->where('account_id', '!=', 1)->pluck('account_id')->toArray();
    foreach ($partner_ids as $partner_id) {

        // SEND PRICELISTS TO PARTNERUSERS
        $customers = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('type', 'reseller_user')->where('partner_id', $partner_id)->get();
        foreach ($customers as $customer) {
            $data = [];
            $data['internal_function'] = 'send_customer_pricelist';
            $data['attachments'] = [$pricelists[$customer->pricelist_id]];
            //$data['test_debug'] = 1;
            email_queue_add($customer->id, $data);
        }
    }
    */
}


// ALL FUNCTIONS THAT SENDS PRICELIST
