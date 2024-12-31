<?php

function aftersave_bulk_email_add_queue($request)
{
    $bulk_mail = \DB::table('crm_bulk_emails')->where('id', $request->id)->get()->first();

    if ($bulk_mail->send_channel == 'Email' || $bulk_mail->send_channel == 'Email and SMS') {
        $list = \DB::table('crm_email_lists')->where('id', $request->email_list_id)->get()->first();
        $records = \DB::table('crm_email_list_records')->where('email_list_id', $request->email_list_id)->where('email', '>', '')->get();
        $list_total = $records->count();

        foreach ($records as $record) {
            if (! str_contains($list->name, 'Test') && ! $bulk_mail->send_to_unsubscribed) {
                $subscribed = \DB::table('crm_accounts')->where('id', $record->account_id)->pluck('newsletter')->first();
                if (! $subscribed) {
                    $list_total--;

                    continue;
                }
            }

            $data = [
                'bulk_email_id' => $request->id,
                'name' => $record->name,
                'email' => $record->email,
                'account_id' => $record->account_id,
                'created_by' => $bulk_mail->created_by,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'Queued',
            ];
            \DB::table('crm_bulk_email_records')->insert($data);
        }
        $e = \DB::table('crm_bulk_email_records')->where('bulk_email_id', $request->id)->where('email', 'ahmed@telecloud.co.za')->count();
        if (! $e) {
            $data = [
                'bulk_email_id' => $request->id,
                'name' => 'Ahmed',
                'email' => 'ahmed@telecloud.co.za',
                'account_id' => 1,
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'Queued',
            ];
            \DB::table('crm_bulk_email_records')->insert($data);
        }
        \DB::table('crm_bulk_emails')->where('id', $request->id)->update(['queued_total' => $list_total]);
    }

    if ($bulk_mail->send_channel == 'SMS' || $bulk_mail->send_channel == 'Email and SMS') {
        $msg = \DB::table('crm_newsletters')->where('id', $bulk_mail->newsletter_id)->pluck('sms_message')->first();
        // $newsletter_link = url('/newsletter_view/'.$bulk_mail->newsletter_id);

        // $msg = str_replace('[newsletter_link]', $newsletter_link, $msg);
        if (! $bulk_mail->send_to_unsubscribed) {
            $msg .= PHP_EOL.'Reply with STOP to unsubscribe';
        }
        $to = '';
        $list = \DB::table('crm_email_lists')->where('id', $request->email_list_id)->get()->first();
        $records = \DB::table('crm_email_list_records')->where('email_list_id', $request->email_list_id)->where('phone', '>', '')->get();
        $list_total = $records->count();

        foreach ($records as $record) {
            if (! str_contains($list->name, 'Test') && ! $bulk_mail->send_to_unsubscribed) {
                $subscribed = \DB::table('crm_accounts')->where('id', $record->account_id)->pluck('sms_subscribed')->first();
                if (! $subscribed) {
                    $list_total--;

                    continue;
                }
            }

            queue_sms(1, $record->phone, $msg);
        }

        \DB::table('crm_bulk_emails')->where('id', $request->id)->increment('queued_total', $list_total);
    }
}

function schedule_generate_mailing_lists()
{
    generate_mailing_lists();
}

function aftersave_email_set_sent_status($request)
{
    if (! empty($request->email_list_id)) {
        if ($request->email_list_id == 1) {
            \DB::table('crm_newsletters')->where('id', $request->newsletter_id)->update(['sent_test' => 1]);
        } else {
            \DB::table('crm_newsletters')->where('id', $request->newsletter_id)->update(['sent_to_customers' => 1]);
        }
    }
}

function button_newsletters_view_sent_emails($request)
{
    $bulk_mail_id = \DB::table('crm_bulk_emails')->where('newsletter_id', $request->id)->where('email_list_id', '!=', 1)->pluck('id')->first();

    if (empty($bulk_mail_id)) {
        return json_alert('Customer email not Sent');
    }

    $url = get_menu_url_from_module_id(770);

    return redirect()->to($url.'?bulk_email_id='.$bulk_mail_id);
}

function beforesave_newsletter_send_check($request)
{
    if (empty($request->newsletter_id)) {
        return 'Newsletter required';
    }

    $sent_list_ids_including_test = \DB::table('crm_bulk_emails')->where('newsletter_id', $request->newsletter_id)->pluck('email_list_id')->unique()->toArray();
    $sent_list_ids = \DB::table('crm_bulk_emails')->where('email_list_id', '!=', 1)->where('newsletter_id', $request->newsletter_id)->pluck('email_list_id')->unique()->toArray();

    if ($request->email_list_id != 1 && ! in_array(1, $sent_list_ids_including_test)) {
        return 'Newsletter needs to be sent to test list first.';
    }

    if ($request->email_list_id != 1 && count($sent_list_ids) > 1) {
        return 'Newsletter already sent out to customers, create a new newsletter.';
    }
}

function button_newsletters_view_email($request)
{
    $newsletter = \DB::table('crm_newsletters')->where('id', $request->id)->get()->first();
    $template_file = '_emails.newsletter';

    $account = dbgetaccount(12);
    // mail data
    $data = [];
    if ($newsletter->use_beefree_builder) {
        $data['html'] = \Erp::decode($newsletter->beefree_builder_html);
        $data['css'] = '';
    } else {
        $data['html'] = \Erp::decode($newsletter->stripo_html);
        $data['css'] = \Erp::decode($newsletter->stripo_css);
    }
    $data['html'] = $newsletter->email_html;
    $data['to_email'] = $account->email;
    $data['to_company'] = $account->company;
    $data['subject'] = $newsletter->name;

    // $data['paynow_button'] = '';
    // if (1 == $account->partner_id) {
    //     $paynow_button = generate_paynow_button($account->id, 100);
    //     $data['html'] = str_replace('[Paynow]', $paynow_button, $data['html']);
    // }

    $newsletter_footer = get_admin_setting('newsletter_footer');
    $newsletter_footer .= PHP_EOL.'[browserlink] | [unsubscribe]';
    // ubsubscribe link

    $newsletter_footer = str_ireplace('[footer]', '', $newsletter_footer);

    $browser_link = '<a href='.url('https://'.session('instance')->domain_name.'newsletter_view/'.$newsletter->id).'" target="_blank" style="font-size: 12px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 5px; background-color: #6666ff; border-top: 2px solid #6666ff; border-bottom: 2px solid #6666ff; border-right: 8px solid #6666ff; border-left: 8px solid #6666ff; display: inline-block;">View in browser</a>';
    $browser_link_url = 'https://'.session('instance')->domain_name.'/newsletter_view/'.$newsletter->id;
    $data['html'] = str_ireplace('https://#browserlink', $browser_link_url, $data['html']);
    $newsletter_footer = str_ireplace('https://#browserlink', $browser_link_url, $newsletter_footer);

    $data['html'] = str_ireplace('#browserlink', $browser_link_url, $data['html']);
    $newsletter_footer = str_ireplace('#browserlink', $browser_link_url, $newsletter_footer);

    $newsletter_footer = str_ireplace('[browserlink]', $browser_link, $newsletter_footer);

    $data['html'] = str_ireplace('[footer]', $newsletter_footer, $data['html']);

    $link_params = \Erp::encode(['account_id' => 12]);
    $unsubscribe_url = request()->root().'/mail_unsubscribe/'.$link_params;
    $unsubscribe_url = str_replace('http://', 'https://', $unsubscribe_url);
    //$unsubscribe_text = '<a href="'.$unsubscribe_url.'" target="_blank" style="font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #000; font-weight: bold; text-decoration: none; border-radius: 5px; background-color: #fff; border-top: 2px solid #fff; border-bottom: 2px solid #fff; border-right: 8px solid #fff; border-left: 8px solid #fff; display: inline-block;">Unsubscribe</a>';

    $data['html'] = str_ireplace('https://#unsubscribe', $unsubscribe_url, $data['html']);

    if (! empty($newsletter->attachment_file)) {
        $attachments = explode(',', $newsletter->attachment_file);
        foreach ($attachments as $file) {
            if (file_exists(uploads_newsletter_path().$file)) {
                \File::copy(uploads_newsletter_path().$file, attachments_path().$file);
                $data['attachments'][] = $file;
            }
        }
    }
    $admin = dbgetaccount(1);
    $admin_settings = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();
    $data['from_company'] = $admin->company;
    if ($newsletter->from_email == 'No Reply') {
        $from_email_arr = explode('@', $admin_settings->notification_support);
        $data['from_email'] = 'no-reply@'.$from_email_arr[1];
    }

    if ($newsletter->from_email == 'Accounting') {
        $data['from_email'] = $admin_settings->notification_account;
    }

    if ($newsletter->from_email == 'Sales') {
        $data['from_email'] = $admin_settings->notification_sales;
    }

    if ($newsletter->from_email == 'Marketing') {
        $data['from_email'] = $admin_settings->notification_marketing;
    }

    if ($newsletter->from_email == 'Helpdesk') {
        $data['from_email'] = $admin_settings->notification_support;
    }

    $html = view($template_file, $data)->render();

    return $html;
}

function get_default_mailing_list_id()
{
    $default_id = \DB::table('crm_email_lists')->where(['name' => 'All customers'])->pluck('id')->first();

    return $default_id;
}

function restore_email_lists_from_backup()
{
    \DB::table('crm_email_lists')->delete();
    \DB::table('crm_email_list_records')->delete();

    $crm_email_lists = \DB::connection('backup_ct')->table('crm_email_lists')->get();
    $crm_email_list_records = \DB::connection('backup_ct')->table('crm_email_list_records')->get();
    foreach ($crm_email_lists as $r) {
        $d = (array) $r;
        \DB::table('crm_email_lists')->insert($d);
    }
    foreach ($crm_email_list_records as $r) {
        $d = (array) $r;
        \DB::table('crm_email_list_records')->insert($d);
    }
    generate_mailing_lists();
}

function generate_mailing_lists()
{
    // delete existing lists
    $generated_ids = \DB::table('crm_email_lists')->pluck('id', 'name');
    $list_ids = \DB::table('crm_email_lists')->where('auto_generated', 1)->pluck('id')->toArray();
    \DB::table('crm_email_list_records')->whereIn('email_list_id', $list_ids)->delete();
    \DB::table('crm_email_lists')->whereIn('id', $list_ids)->delete();

    // test
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Test', 'id' => 1]);
    \DB::table('crm_email_list_records')->insert(['name' => 'Director', 'email' => 'ahmed@telecloud.co.za', 'phone' => '0824119555', 'email_list_id' => $list_id, 'account_id' => 1]);
    \DB::table('crm_email_list_records')->insert(['name' => 'Javaid', 'email' => 'javaid@telecloud.co.za', 'phone' => '0648321286', 'email_list_id' => $list_id, 'account_id' => 1]);

    // all and deleted customers
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'All customers and deleted customers', 'id' => $generated_ids['All customers and deleted customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // all customers
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'All active customers', 'id' => $generated_ids['All active customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }

        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // Voice customers
    $pbx_account_ids = \DB::connection('pbx')->table('v_domains')->pluck('account_id')->filter()->unique()->toArray();
    $reseller_ids = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', '!=', 1)->whereIn('id', $pbx_account_ids)->pluck('partner_id')->filter()->unique()->toArray();
    foreach ($reseller_ids as $reseller_id) {
        $pbx_account_ids[] = $reseller_id;
    }
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', 1)->whereIn('id', $pbx_account_ids)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Voice customers', 'id' => $generated_ids['Voice customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }
    // LTE customers
    $subs_account_ids = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->where('provision_type', 'LIKE', '%lte%')->pluck('account_id')->filter()->unique()->toArray();
    $reseller_ids = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', '!=', 1)->whereIn('id', $subs_account_ids)->pluck('partner_id')->filter()->unique()->toArray();
    foreach ($reseller_ids as $reseller_id) {
        $subs_account_ids[] = $reseller_id;
    }
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', 1)->whereIn('id', $subs_account_ids)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'LTE customers', 'id' => $generated_ids['LTE customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }
    // Fibre customers
    $subs_account_ids = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->where('provision_type', 'LIKE', '%fibre%')->pluck('account_id')->filter()->unique()->toArray();
    $reseller_ids = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', '!=', 1)->whereIn('id', $subs_account_ids)->pluck('partner_id')->filter()->unique()->toArray();
    foreach ($reseller_ids as $reseller_id) {
        $subs_account_ids[] = $reseller_id;
    }
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', 1)->whereIn('id', $subs_account_ids)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Fibre customers', 'id' => $generated_ids['Fibre customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // Unlimited pbx customers
    $subs_account_ids = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->whereIn('product_id', [1393, 1394])->pluck('account_id')->filter()->unique()->toArray();

    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', 1)->whereIn('id', $subs_account_ids)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Unlimited PBX customers', 'id' => $generated_ids['Unlimited PBX customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // IP Range customers
    $subs_account_ids = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->where('provision_type', 'LIKE', '%ip_range%')->pluck('account_id')->filter()->unique()->toArray();
    $reseller_ids = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', '!=', 1)->whereIn('id', $subs_account_ids)->pluck('partner_id')->filter()->unique()->toArray();
    foreach ($reseller_ids as $reseller_id) {
        $subs_account_ids[] = $reseller_id;
    }
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', 1)->whereIn('id', $subs_account_ids)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'IP Range customers', 'id' => $generated_ids['IP Range customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }
    // Hosting customers
    $subs_account_ids = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->whereIn('provision_type', ['hosting', 'sitebuilder'])->pluck('account_id')->filter()->unique()->toArray();
    $reseller_ids = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', '!=', 1)->whereIn('id', $subs_account_ids)->pluck('partner_id')->filter()->unique()->toArray();
    foreach ($reseller_ids as $reseller_id) {
        $subs_account_ids[] = $reseller_id;
    }
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('status', '!=', 'Deleted')->where('partner_id', 1)->whereIn('id', $subs_account_ids)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Hosting customers', 'id' => $generated_ids['Hosting customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // usd customers
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('currency', 'USD')->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'USD customers', 'id' => $generated_ids['USD customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // leads
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('type', 'lead')->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Leads', 'id' => $generated_ids['Leads']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // customers
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('type', 'customer')->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Customers', 'id' => $generated_ids['Customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // resellers
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->where('type', 'reseller')->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Resellers', 'id' => $generated_ids['Resellers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // resellers and customers
    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->whereIn('type', ['reseller', 'customer'])->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Resellers and customers', 'id' => $generated_ids['Resellers and customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    // Debit order customers
    $debit_account_ids = \DB::table('acc_debit_orders')->where('status', 'Enabled')->pluck('account_id')->toArray();

    $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->whereIn('id', $debit_account_ids)->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();
    $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Debit order customers', 'id' => $generated_ids['Debit order customers']]);
    foreach ($list_records as $row) {
        $phone = valid_za_mobile_number($row->phone);
        if (! $phone) {
            $phone = '';
        }
        \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
    }

    if (session('instance')->directory == 'eldooffice') {
        $list_records = \DB::table('crm_estate_agents')->select('name', 'email', 'id')->get();
        $list_id = \DB::table('crm_email_lists')->insertGetId(['auto_generated' => 1, 'status' => 'Enabled', 'name' => 'Estate Agents', 'id' => $generated_ids['Estate Agents']]);
        foreach ($list_records as $row) {
            $phone = valid_za_mobile_number($row->phone);
            if (! $phone) {
                $phone = '';
            }
            \DB::table('crm_email_list_records')->insert(['name' => $row->name, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => 1]);
        }
    }

    // Product categories
    /*
    $categories = \DB::table('crm_product_categories')->where('is_deleted',0)->get();
    \DB::table('crm_email_lists')->where('auto_generated', 1)->where('product_category_id', '>', 0)->update(['status'=> 'Deleted']);
    $list_ids = \DB::table('crm_email_lists')->where('auto_generated', 1)->where('product_category_id', '>', 0)->pluck('id')->toArray();
    \DB::table('crm_email_list_records')->whereIn('email_list_id', $list_ids)->delete();
    foreach ($categories as $category) {
        $product_ids = \DB::table('crm_products')->where('product_category_id', $category->id)->pluck('id')->toArray();
        if (count($product_ids) == 0) {
            continue;
        }

        $filter_ids = \DB::table('sub_services')->whereIn('product_id', $product_ids)->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
        $filter_partner_ids = \DB::table('crm_accounts')->whereIn('id', $filter_ids)->where('partner_id', '!=', 1)->pluck('partner_id')->unique()->toArray();
        $filter_ids = array_merge($filter_ids, $filter_partner_ids);
        $list_records = \DB::table('crm_accounts')->select('company', 'email', 'phone', 'id')->whereIn('id', $filter_ids)->where('status', '!=', 'Deleted')->where('partner_id', 1)->get();


        if ($list_records->count() > 0) {
            \DB::table('crm_email_lists')->where('auto_generated', 1)->where('product_category_id', $category->id)->update(['status'=> 'Enabled']);
        }
        $exists = \DB::table('crm_email_lists')->where('auto_generated', 1)->where('product_category_id', $category->id)->count();
        if ($exists) {
            $list_id = \DB::table('crm_email_lists')->where('auto_generated', 1)->where('product_category_id', $category->id)->pluck('id')->first();
            \DB::table('crm_email_lists')->where('auto_generated', 1)->where('product_category_id', $category->id)->update(['name'=> $category->name]);
            if ($list_records->count() > 0) {
                \DB::table('crm_email_lists')->where('auto_generated', 1)->where('product_category_id', $category->id)->update(['status'=> 'Enabled']);
            }
        } else {
            $list_id = \DB::table('crm_email_lists')->insertGetId(['product_category_id' => $category->id,'auto_generated' => 1, 'status' => 'Enabled', 'name' => $category->name]);
        }
        foreach ($list_records as $row) {
            \DB::table('crm_email_list_records')->insert(['name' => $row->company, 'email' => $row->email, 'phone' => $phone, 'email_list_id' => $list_id, 'account_id' => $row->id]);
        }
    }
    */

    $list_ids = \DB::table('crm_email_lists')->pluck('id')->toArray();
    foreach ($list_ids as $id) {
        $total = \DB::table('crm_email_list_records')->where('email', '')->delete();
        $unique_emails = \DB::table('crm_email_list_records')->where('email_list_id', $id)->pluck('email')->filter()->unique()->toArray();
        foreach ($unique_emails as $email) {
            $email_id = \DB::table('crm_email_list_records')->where('email_list_id', $id)->where('email', $email)->pluck('id')->first();
            \DB::table('crm_email_list_records')->where('email_list_id', $id)->where('id', '!=', $email_id)->where('email', $email)->delete();
        }
    }

    update_email_list_totals();
    \DB::table('crm_email_lists')->where('auto_generated', 1)->where('list_total', 0)->update(['status' => 'Deleted']);
}

function update_email_list_totals()
{
    $list_ids = \DB::table('crm_email_lists')->pluck('id')->toArray();
    foreach ($list_ids as $id) {
        $total = \DB::table('crm_email_list_records')->where('email_list_id', $id)->count();
        $email_total = \DB::table('crm_email_list_records')->where('email_list_id', $id)->where('email', '>', '')->count();
        $sms_total = \DB::table('crm_email_list_records')->where('email_list_id', $id)->where('phone', '>', '')->count();
        \DB::table('crm_email_lists')->where('id', $id)->update(['list_total' => $total, 'email_total' => $email_total, 'sms_total' => $sms_total]);
    }
}

function button_generate_mailing_lists($request)
{
    generate_mailing_lists();

    return json_alert('Done');
}

function aftersave_email_list_set_count($request)
{
    $id = $request->id;
    $total = \DB::table('crm_email_list_records')->where('email_list_id', $id)->count();
    \DB::table('crm_email_lists')->where('id', $id)->update(['list_total' => $total]);
}

function aftersave_email_list_records_set_count($request)
{
    $id = $request->email_list_id;
    $total = \DB::table('crm_email_list_records')->where('email_list_id', $id)->count();
    \DB::table('crm_email_lists')->where('id', $id)->update(['list_total' => $total]);
}

function button_bulk_email_reset_queue($request)
{
    $bulk_mail = \DB::table('crm_bulk_emails')->where('id', $request->id)->get()->first();
    \DB::table('crm_bulk_email_records')->where('bulk_email_id', $bulk_mail->id)->delete();
    if ($bulk_mail->send_channel == 'Email' || $bulk_mail->send_channel == 'Email and SMS') {
        aa('email');
        $list = \DB::table('crm_email_lists')->where('id', $request->email_list_id)->get()->first();
        $records = \DB::table('crm_email_list_records')->where('email_list_id', $request->email_list_id)->where('email', '>', '')->get();
        $list_total = $records->count();

        foreach ($records as $record) {
            if (! str_contains($list->name, 'Test') && ! $bulk_mail->send_to_unsubscribed) {
                $subscribed = \DB::table('crm_accounts')->where('id', $record->account_id)->pluck('newsletter')->first();
                if (! $subscribed) {
                    $list_total--;

                    continue;
                }
            }

            $data = [
                'bulk_email_id' => $request->id,
                'name' => $record->name,
                'email' => $record->email,
                'account_id' => $record->account_id,
                'created_by' => $bulk_mail->created_by,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'Queued',
            ];
            \DB::table('crm_bulk_email_records')->insert($data);
        }
        $e = \DB::table('crm_bulk_email_records')->where('bulk_email_id', $request->id)->where('email', 'ahmed@telecloud.co.za')->count();
        if (! $e) {
            $data = [
                'bulk_email_id' => $request->id,
                'name' => 'Ahmed',
                'email' => 'ahmed@telecloud.co.za',
                'account_id' => 1,
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'Queued',
            ];
            \DB::table('crm_bulk_email_records')->insert($data);
        }
        \DB::table('crm_bulk_emails')->where('id', $request->id)->update(['queued_total' => $list_total]);
    }

    return json_alert('Queue reset.');
}

function schedule_process_email_queue()
{
    //Path "/home/erpcloud-live/htdocs/html/attachments/telecloud/Cloud-Telecoms Customer Pricelist 1-2022-07-05.xlsx" is not readable.
    // set queue to processing
    $queue = \DB::table('crm_bulk_email_records')
        ->select('crm_bulk_email_records.*', 'crm_bulk_emails.newsletter_id', 'crm_bulk_emails.send_to_unsubscribed', 'crm_bulk_emails.attach_retail_pricelist', 'crm_bulk_emails.attach_wholesale_pricelist')
        ->join('crm_bulk_emails', 'crm_bulk_emails.id', '=', 'crm_bulk_email_records.bulk_email_id')
        ->where('crm_bulk_email_records.status', 'Queued')
        ->get();

    $needs_retail_pricelist = \DB::table('crm_bulk_email_records')
        ->select('crm_bulk_email_records.*', 'crm_bulk_emails.newsletter_id', 'crm_bulk_emails.send_to_unsubscribed', 'crm_bulk_emails.attach_retail_pricelist', 'crm_bulk_emails.attach_wholesale_pricelist')
        ->join('crm_bulk_emails', 'crm_bulk_emails.id', '=', 'crm_bulk_email_records.bulk_email_id')
        ->where('crm_bulk_email_records.status', 'Queued')
        ->where('crm_bulk_emails.attach_retail_pricelist', 1)
        ->count();

    $needs_wholesale_pricelist = \DB::table('crm_bulk_email_records')
        ->select('crm_bulk_email_records.*', 'crm_bulk_emails.newsletter_id', 'crm_bulk_emails.send_to_unsubscribed', 'crm_bulk_emails.attach_retail_pricelist', 'crm_bulk_emails.attach_wholesale_pricelist')
        ->join('crm_bulk_emails', 'crm_bulk_emails.id', '=', 'crm_bulk_email_records.bulk_email_id')
        ->where('crm_bulk_email_records.status', 'Queued')
        ->where('crm_bulk_emails.attach_wholesale_pricelist', 1)
        ->count();

    $sending_ids = \DB::table('crm_bulk_email_records')->where('status', 'Queued')->pluck('bulk_email_id')->filter()->unique()->toArray();
    \DB::table('crm_bulk_email_records')->where('status', 'Queued')->update(['status' => 'Processing']);
    if (! empty($queue) && count($queue) > 0) {
        $retail_pricelist = false;
        $wholesale_pricelist = false;
        //if ($needs_retail_pricelist) {
        //    $retail_pricelist = export_pricelist(1);
        //}

        //if ($needs_wholesale_pricelist) {
        //    $wholesale_pricelist = export_pricelist(1);
        //}

        foreach ($queue as $email) {
            send_newsletter($email, $retail_pricelist, $wholesale_pricelist);
        }
    }
    $queue_ids = \DB::table('crm_bulk_emails')->whereIn('id', $sending_ids)->orderBy('id', 'desc')->pluck('id')->toArray();
    foreach ($queue_ids as $queue_id) {
        $sent_total = \DB::table('crm_bulk_email_records')->where('status', 'Sent')->where('bulk_email_id', $queue_id)->count();
        \DB::table('crm_bulk_emails')->where('id', $queue_id)->update(['sent_total' => $sent_total]);
    }
    $queues = \DB::table('crm_bulk_emails')->whereIn('id', $sending_ids)->where('email_list_id', '!=', 1)->orderBy('id', 'desc')->get();
    foreach ($queues as $queue) {
        $failed_email_address = \DB::table('crm_bulk_email_records')->where('bulk_email_id', $queue->id)->where('status', 'Error')->pluck('email')->unique()->filter()->toArray();
        \DB::table('crm_newsletters')->where('id', $queue->newsletter_id)->update(['failed_email_addresses' => implode(',', $failed_email_address)]);
    }
}

function send_newsletter($email, $retail_pricelist = false, $wholesale_pricelist = false)
{
    try {
        $newsletter = \DB::table('crm_newsletters')->where('id', $email->newsletter_id)->get()->first();
        $template_file = '_emails.newsletter';

        // aa($data['html']);
        // mail data
        $data = [];
        // if ($newsletter->use_beefree_builder) {
        //     $data['html'] = \Erp::decode($newsletter->beefree_builder_html);
        //     $data['css'] = '';
        // } else {
        //     $data['html'] = \Erp::decode($newsletter->stripo_html);
        //     $data['css'] = \Erp::decode($newsletter->stripo_css);
        // }
        $data['html'] = $newsletter->email_html;
        aa($data['html']);

        $data['to_email'] = $email->email;
        $data['to_company'] = $email->name;
        $data['subject'] = $newsletter->name;

        $account = dbgetaccount($email->account_id);
        $account_id = $email->account_id;
        $reseller = dbgetaccount($account->partner_id);
        $data['customer'] = $account;
        $data['partner'] = $reseller;
        $data['partner_company'] = $reseller->company;
        $data['paynow_button'] = '';
        if ($account->partner_id == 1) {
            $paynow_button = generate_paynow_button($account->id, 100);

            $data['html'] = str_replace('[Paynow]', $paynow_button, $data['html']);
        }

        $newsletter_footer = get_admin_setting('newsletter_footer');
        $newsletter_footer .= PHP_EOL.'[browserlink] | [unsubscribe]';
        // ubsubscribe link
        if ($email->send_to_unsubscribed) {
            $newsletter_footer = str_ireplace('[footer]', '', $newsletter_footer);
        } else {
            $link_params = \Erp::encode(['account_id' => $email->account_id]);
            $unsubscribe_url = 'https://'.session('instance')->domain_name.'/mail_unsubscribe/'.$link_params;
            $unsubscribe_url = str_replace('http://', 'https://', $unsubscribe_url);
            //$unsubscribe_text = '<a href="'.$unsubscribe_url.'" target="_blank" style="font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #000; font-weight: bold; text-decoration: none; border-radius: 5px; background-color: #fff; border-top: 2px solid #fff; border-bottom: 2px solid #fff; border-right: 8px solid #fff; border-left: 8px solid #fff; display: inline-block;">Unsubscribe</a>';
            $data['html'] = str_ireplace('https://#unsubscribe', $unsubscribe_url, $data['html']);
            $newsletter_footer = str_ireplace('https://#unsubscribe', $unsubscribe_url, $newsletter_footer);
        }

        $browser_link = '<a href='.url('https://'.session('instance')->domain_name.'/newsletter_view/'.$newsletter->id).'" target="_blank" style="font-size: 12px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 5px; background-color: #6666ff; border-top: 2px solid #6666ff; border-bottom: 2px solid #6666ff; border-right: 8px solid #6666ff; border-left: 8px solid #6666ff; display: inline-block;">View in browser</a>';
        $browser_link_url = 'https://'.session('instance')->domain_name.'/newsletter_view/'.$newsletter->id;
        $data['html'] = str_ireplace('https://#browserlink', $browser_link_url, $data['html']);
        $newsletter_footer = str_ireplace('https://#browserlink', $browser_link_url, $newsletter_footer);
        $data['html'] = str_ireplace('#browserlink', $browser_link_url, $data['html']);
        $newsletter_footer = str_ireplace('#browserlink', $browser_link_url, $newsletter_footer);

        $newsletter_footer = str_ireplace('[browserlink]', $browser_link, $newsletter_footer);

        $data['html'] = str_ireplace('[footer]', $newsletter_footer, $data['html']);

        $data['msg'] = $data['html'];
        // aa($data['html']);
        // $data['html'] = get_email_html($account_id, $reseller->id, $data, $data['notification']);
        // aa($data['html']);

        $data['css'] = '';
        $template_file = '_emails.gjs';

        if (! empty($newsletter->attachment_file)) {
            $attachments = explode(',', $newsletter->attachment_file);
            foreach ($attachments as $file) {
                if (file_exists(uploads_newsletter_path().$file)) {
                    \File::copy(uploads_newsletter_path().$file, attachments_path().$file);
                    $data['attachments'][] = $file;
                }
            }
        }
        $admin = dbgetaccount(1);
        $admin_settings = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();
        $data['from_company'] = $admin->company;
        if ($newsletter->from_email == 'No Reply') {
            $from_email_arr = explode('@', $admin_settings->notification_support);
            $data['from_email'] = 'no-reply@'.$from_email_arr[1];
        }

        if ($newsletter->from_email == 'Accounting') {
            $data['from_email'] = $admin_settings->notification_account;
        }

        if ($newsletter->from_email == 'Sales') {
            $data['from_email'] = $admin_settings->notification_sales;
        }

        if ($newsletter->from_email == 'Marketing') {
            $data['from_email'] = $admin_settings->notification_marketing;
        }

        if ($newsletter->from_email == 'Helpdesk') {
            $data['from_email'] = $admin_settings->notification_support;
        }

        // set smtp
        $mail_config = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();

        $smtp_host = $mail_config->smtp_host;
        $smtp_port = $mail_config->smtp_port;
        $smtp_username = $mail_config->smtp_username;
        $smtp_password = $mail_config->smtp_password;
        $smtp_encryption = $mail_config->smtp_encryption;

        $data['from_email'] = $smtp_username;

        try {
            $transport = Symfony\Component\Mailer\Transport::fromDsn('smtp://'.$smtp_username.':'.$smtp_password.'@'.$smtp_host.':'.$smtp_port.'?verify_peer=0');
            $mailer = new Symfony\Component\Mailer\Mailer($transport);
            $html = view($template_file, $data)->render();

            $symfony_mail = (new Symfony\Component\Mime\Email)->subject($data['subject'])->html($html);
            $symfony_mail->from(new Symfony\Component\Mime\Address($data['from_email'], $data['from_company']));
            $symfony_mail->to(new Symfony\Component\Mime\Address($data['to_email'], $data['to_company']));
            $symfony_mail->subject($data['subject']);
            if (! empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    $symfony_mail->attachFromPath(attachments_path().$attachment, $attachment);
                }
            }
            if (! empty($retail_pricelist) && $email->attach_retail_pricelist) {
                $symfony_mail->attachFromPath(attachments_path().$retail_pricelist, $retail_pricelist);
            }
            if (! empty($wholesale_pricelist) && $email->attach_wholesale_pricelist) {
                $symfony_mail->attachFromPath(attachments_path().$wholesale_pricelist, $wholesale_pricelist);
            }

            $result = $mailer->send($symfony_mail);

            if (empty($result)) {
                \DB::table('crm_bulk_email_records')->where('id', $email->id)->update(['status' => 'Sent']);
            } else {
                $result = 'failed';
                \DB::table('crm_bulk_email_records')->where('id', $email->id)->update(['status' => 'Error', 'error' => $error]);
            }
        } catch (\Throwable $e) {
            $result = $e->getMessage();
            $error = $result;

            \DB::table('crm_bulk_email_records')->where('id', $email->id)->update(['status' => 'Error', 'error' => $error]);
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        $error = $ex->getMessage();

        \DB::table('crm_bulk_email_records')->where('id', $email->id)->update(['status' => 'Error', 'error' => $error]);
    }
}
