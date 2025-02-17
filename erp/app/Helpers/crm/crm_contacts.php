<?php

function move_contacts_to_users(){
    $contacts = \DB::table('crm_account_contacts')
    ->join('crm_accounts','crm_accounts.id','=','crm_account_contacts.account_id')
    ->where('crm_accounts.status','!=','Deleted')
    ->where('crm_account_contacts.type','!=','Manager')
    ->get();
    foreach($contacts as $contact){
        $user = \DB::table('erp_users')->where('account_id',$contact->account_id)->get()->first();
        $data = (array) $user;
        $username = false;
        
        $email_in_use = \DB::table('erp_users')->where('username',$contact->email)->count();
        if(!$email_in_use){
            $username = $contact->email;
        }
        $phone_in_use = \DB::table('erp_users')->where('username',$contact->phone)->count();
        if(!$phone_in_use){
            $username = $contact->phone;
        }
        
        if($username){
            $data['username'] = $username;
            $data['type'] = 'Support';
            unset($data['id']);
            $data['full_name'] = $contact->name;
            if(empty($data['full_name'])){
                $data['full_name'] = $username;
            }
            $data['email'] = $contact->email;
            $data['phone'] = $contact->phone;
            \DB::table('erp_users')->insert($data);
        }
        
    }
}


function get_account_contact_phone($account_id, $type)
{
    return get_account_contact($account_id, $type, 'phone');
}

function get_account_contact_email($account_id, $type)
{
    return get_account_contact($account_id, $type, 'email');
}

function get_account_contact($account_id, $type, $field)
{
    return \DB::connection('default')->table('erp_users')->where('account_id', $account_id)->where('type', $type)->pluck($field)->first();
}

function get_account_contacts($account_id)
{
    return \DB::connection('default')->table('erp_users')->where('account_id', $account_id)->get();
}

function get_supplier_contact_phone($supplier_id, $type)
{
    return get_supplier_contact($supplier_id, $type, 'phone');
}

function get_supplier_contact_email($supplier_id, $type)
{
    return get_supplier_contact($supplier_id, $type, 'email');
}

function get_supplier_contact($supplier_id, $type, $field)
{
    return \DB::connection('default')->table('crm_supplier_contacts')->where('supplier_id', $supplier_id)->where('type', $type)->pluck($field)->first();
}

function get_supplier_contacts($supplier_id)
{
    return \DB::connection('default')->table('crm_supplier_contacts')->where('supplier_id', $supplier_id)->get();
}

function schedule_invalid_contacts_sms()
{
    $accounts = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->get();
    foreach ($accounts as $account) {
        $numbers = [];
        $phone_number = valid_za_mobile_number($account->phone);
        if ($phone_number) {
            $numbers[] = $phone_number;
        }
        $phone_number = valid_za_mobile_number($account->contact_phone_1);
        if ($phone_number) {
            $numbers[] = $phone_number;
        }
        $phone_number = valid_za_mobile_number($account->contact_phone_2);
        if ($phone_number) {
            $numbers[] = $phone_number;
        }
        $phone_number = valid_za_mobile_number($account->contact_phone_3);
        if ($phone_number) {
            $numbers[] = $phone_number;
        }
        foreach ($numbers as $num) {
            $undelivered = \DB::table('isp_sms_message_queue')
                ->where('panacea_id', '>', '')
                ->where('number', $num)
                ->where(function ($query) {
                    $query->where('status', 'Undelivered');
                    $query->orWhere('status', 'Failed at network');
                })
                ->count();

            if ($undelivered) {
                $exists = \DB::table('crm_invalid_contacts')->where('phone', $num)->where('account_id', $account->id)->count();
                if (!$exists) {
                    $data = [
                        'type' => 'sms',
                        'phone' => $num,
                        'account_id' => $account->id,
                        'created_at' => date('Y-m-d'),
                    ];
                    \DB::table('crm_invalid_contacts')->insert($data);
                }
            }
        }
    }
}





function schedule_invalid_contacts_email()
{
    // bounced emails checked manually
    /*
    try {
        $hostname = '{mail.cloudtelecoms.co.za:993/imap/ssl/novalidate-cert}INBOX';
        $username = 'sales@telecloud.co.za';
        $password = 'Webmin@786';
        $inbox = imap_open($hostname, $username, $password);
        //print_r(imap_errors());
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_email($ex, __FUNCTION__.' error');
        return false;
    }

    if (!$inbox) {
        return;
    }
    $emails = imap_search($inbox, 'UNSEEN');


    if (!empty($emails) && count($emails) > 0) {
        rsort($emails);
        foreach ($emails as $email_number) {
            $header = imap_fetchheader($inbox, $email_number);
            $header_arr = explode(PHP_EOL, $header);
            $email = false;
            foreach ($header_arr as $line) {
                if (str_contains($line, 'X-Failed-Recipients')) {
                    $email = trim(str_replace('X-Failed-Recipients: ', '', $line));
                }
            }
            if ($email) {
                $account_id = \DB::table('crm_accounts')->where('email', $email)->where('status', '!=', 'Deleted')->pluck('id')->first();
                if (!$account_id) {
                    $account_id = \DB::table('erp_users')->where('email', $email)->where('status', '!=', 'Deleted')->pluck('account_id')->first();
                }
                if (!$account_id) {
                    $account_id = 0;
                }
                $exists = \DB::table('crm_invalid_contacts')->where('email', $email)->where('account_id', $account_id)->count();
                if (!$exists) {
                    $data = [
                        'type' => 'email',
                        'email' => $email,
                        'account_id' => $account_id,
                        'created_at' => date('Y-m-d'),
                    ];
                    \DB::table('crm_invalid_contacts')->insert($data);
                }
            }


            imap_delete($inbox, $email_number);
        }
    }
    imap_expunge($inbox);
    imap_close($inbox);
    */
}


function schedule_invalid_contacts_update()
{
    \DB::table('crm_accounts')->update(['faulty_contact' => '']);
    $types = \DB::table('crm_invalid_contacts')->pluck('type')->unique()->filter()->toArray();
    foreach($types as $type){
        $account_ids = \DB::table('crm_invalid_contacts')->where('processed', '')->where('type', $type)->pluck('account_id')->unique()->toArray();
        \DB::table('crm_accounts')->whereIn('id', $account_ids)->update(['faulty_contact' => $type]);
    }
}
