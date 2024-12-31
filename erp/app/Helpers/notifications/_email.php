<?php

function erp_email_send($account_id, $data = [], $function_variables = [], $conn = false)
{
    $current_conn = \DB::getDefaultConnection();
    $lookup_conn = $current_conn;
    $conn = $current_conn;
    $conns = \DB::connection('system')->table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
    if (! in_array($lookup_conn, $conns)) {
        $lookup_conn = 'default';
    }
    set_db_connection($conn);
    $data['year'] = date('Y');
    $data['email_logo_conn'] = $conn;
    $data['date'] = date('Y-m-d');
    $data['currency_symbol'] = get_account_currency_symbol($account_id);

    $remove_payment_options = \DB::table('erp_admin_settings')->where('id', 1)->pluck('remove_payment_options')->first();
    if (is_array($function_variables) && count($function_variables) > 0) {
        $data = array_merge($function_variables, $data);
    }
    $data['account_id'] = $account_id;
    $data['event_subject'] = '';
    if (! empty($data['function_name'])) {
        $data['notification_id'] = \DB::connection($lookup_conn)->table('erp_form_events')->where('function_name', $data['function_name'])->pluck('email_id')->first();
        $scheduled_event = \DB::connection($lookup_conn)->table('erp_form_events')->where('function_name', $data['function_name'])->where('type', 'schedule')->count();
        if ($scheduled_event && $data['notification_id']) {
            $data['event_subject'] = trim(ucwords(str_replace(['schedule', 'send'], '', str_replace('_', ' ', $data['function_name']))));
        }
    }

    if (! empty($data['internal_function'])) {
        $data['notification_id'] = \DB::connection($lookup_conn)->table('crm_email_manager')->where('internal_function', $data['internal_function'])->where('is_deleted', 0)->pluck('id')->first();
    }

    if (! empty($data['customer_type']) && $data['customer_type'] == 'supplier') {
        $account = \DB::table('crm_suppliers')->where('id', $account_id)->get()->first();
    } else {
        $account = dbgetaccount($account_id, $conn);
        $data['customer_type'] = 'account';
    }

    $reseller = dbgetaccount($account->partner_id, $conn);
    $data['partner_company'] = $reseller->company;

    if (empty($data['attachments'])) {
        $data['attachments'] = [];
    }

    if (str_contains($data['notification_id'], 'notification')) {
        $data['notification_id'] = str_replace('notification', '', $data['notification_id']);
    }

    $notification = \DB::table('crm_email_manager')->where('id', $data['notification_id'])->get()->first();

    if (empty($data['activation_email'])) {
        $notification = \DB::table('crm_email_manager')->where('id', $data['notification_id'])->get()->first();
    } else {
        $notification = \DB::table('crm_email_manager')->where('id', $data['notification_id'])->get()->first();
    }

    $faq_link = get_admin_setting('activation_email_faq_link');
    if (isset($email_data['activation_email']) && $email_data['activation_email'] && $faq_link) {
        $notification->message .= '<br><br>Please visit our Website FAQs if you need help with your setup. <br><a href="'.$faq_link.'" target="_blank">Click here to view FAQs.</a>';
    }

    $template = $notification;

    //ratesheets
    $ratesheet_email_ids = [316, 317, 339, 340, 361, 362, 363, 364];
    if (in_array($notification->id, $ratesheet_email_ids)) {
        if (! empty($account->pabx_domain)) {
            $ratesheet_id = \DB::connection('pbx')->table('v_domains')->where('domain_name', $account->pabx_domain)->pluck('ratesheet_id')->first();
        } elseif (session('instance')->id == 1) {
            $ratesheet_id = 1;
        } else {
            $ratesheet_id = 71;
        }

        if (session('instance')->id == 1) {
            if ($notification->id == 316) {
                $data['attachment'] = export_partner_rates($ratesheet_id);
            }

            if ($notification->id == 317) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'ZAR');
            }

            if ($notification->id == 339) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'USD');
            }

            if ($notification->id == 340) {
                $data['attachment'] = export_partner_rates($ratesheet_id);
            }

            if ($notification->id == 361) {
                $data['attachment'] = export_partner_rates_summary($ratesheet_id);
            }

            if ($notification->id == 362) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'ZAR', false);
            }

            if ($notification->id == 363) {
                $data['attachment'] = export_partner_rates_summary($ratesheet_id);
            }

            if ($notification->id == 364) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'USD', false);
            }
        }

        if (session('instance')->id != 1) {
            if ($notification->main_instance_id == 316) {
                $data['attachment'] = export_partner_rates($ratesheet_id);
            }

            if ($notification->main_instance_id == 317) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'ZAR');
            }

            if ($notification->main_instance_id == 339) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'USD');
            }

            if ($notification->main_instance_id == 340) {
                $data['attachment'] = export_partner_rates($ratesheet_id);
            }

            if ($notification->main_instance_id == 361) {
                $data['attachment'] = export_partner_rates_summary($ratesheet_id);
            }

            if ($notification->main_instance_id == 362) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'ZAR', false);
            }

            if ($notification->main_instance_id == 363) {
                $data['attachment'] = export_partner_rates_summary($ratesheet_id);
            }

            if ($notification->main_instance_id == 364) {
                $data['attachment'] = export_default_ratesheet('wholesale', 'USD', false);
            }
        }
    }

    if (! empty($data['attachment'])) {
        $data['attachments'][] = $data['attachment'];
    }
    $admin = dbgetaccount(1, $conn);

    $reseller = dbgetaccount($account->partner_id, $conn);
    if ($reseller->id != 1 && ! $reseller->email_monthly_billing && (str_contains($data['attachment'], 'Invoice') || str_contains($data['attachment'], 'Order'))) {
        return false;
    }

    if (empty($data['notification_id']) && ! $data['formatted']) {
        return false;
    }

    $data['delivery_confirmation'] = false;
    if ($reseller->id == 1 && ! empty($template->delivery_confirmation) && $template->delivery_confirmation == 1) {
        $data['delivery_confirmation'] = true;
    }

    if (! empty($template->attachment_file)) {
        $attachments = explode(',', $template->attachment_file);
        foreach ($attachments as $file) {
            if (file_exists(uploads_emailbuilder_path().$file)) {
                \File::copy(uploads_emailbuilder_path().$file, attachments_path().$file);
                $data['attachments'][] = $file;
            }
        }
    }

    if ($data['internal_function'] == 'ip_address_followup') {
        $data['attachments'][] = export_available_ipranges();
    }

    if ($template->id == 590) {
        $data['attachments'][] = 'Available Phone Numbers.xlsx';
    }

    if (! empty($template->attach_statement) || ! empty($data['attach_statement'])) {
        if (! empty($data['include_statement_reversals'])) {
            $pdf = statement_pdf($account->id, 0, 0, 1);
        } else {
            $pdf = statement_pdf($account->id);
        }
        $file = 'Statement_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $data['attachments'][] = $file;
    }
    if (! empty($template->attach_full_statement) || ! empty($data['attach_full_statement'])) {
        if (! empty($data['include_statement_reversals'])) {
            $pdf = statement_pdf($account->id, 1, 0, 1);
        } else {
            $pdf = statement_pdf($account->id, 1);
        }
        $file = 'Statement_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $data['attachments'][] = $file;
    }
    if (session('instance')->id != 11 && ! empty($template->attach_letter_of_demand)) {
        $pdf = collectionspdf($account->id, $template->id);
        $name = ucfirst(str_replace(' ', '_', $template->name));
        $file = $name.'_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }

        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        aa($pdf);
        $data['attachments'][] = $file;
        if (! str_contains($data['subject'], 'Letter of demand')) {
            $data['subject'] .= ' (Letter of demand)';
        }
    }
    if (! empty($template->attach_suspension_warning)) {
        $pdf = suspension_warning_pdf($account->id, $template->id);
        $name = ucfirst(str_replace(' ', '_', $template->name));
        $file = $name.'_'.$account->id.'_'.date('Y_m_d').'.pdf';
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);
        $data['attachments'][] = $file;
    }

    if (! empty($template->attach_orders)) {
        $quotes = \DB::connection('default')->table('crm_documents')->where('doctype', 'Quotation')->where('account_id', $account_id)->get();
        if (! empty($quotes)) {
            foreach ($quotes as $quote) {
                $pdf = document_pdf($quote->id);
                $file = str_replace(' ', '_', ucfirst($quote->doctype).' '.$quote->id).'.pdf';
                $filename = attachments_path().$file;
                if (file_exists($filename)) {
                    unlink($filename);
                }
                $pdf->setTemporaryFolder(attachments_path());
                $pdf->save($filename);
                $data['attachments'][] = $file;
            }
        }
        $orders = \DB::connection('default')->table('crm_documents')->where('doctype', 'Order')->where('account_id', $account_id)->get();
        if (! empty($orders)) {
            foreach ($orders as $order) {
                $pdf = document_pdf($order->id);
                $file = str_replace(' ', '_', ucfirst($order->doctype).' '.$order->id).'.pdf';
                $filename = attachments_path().$file;
                if (file_exists($filename)) {
                    unlink($filename);
                }
                $pdf->setTemporaryFolder(attachments_path());
                $pdf->save($filename);
                $data['attachments'][] = $file;
            }
        }
    }
    $data['notification'] = $notification;

    $message_template = new stdClass;
    $message_template->message = '{!! $msg !!}';
    $message_template->name = 'default';

    if (! empty($data['notification'])) {
        $message_template = $data['notification'];
        if (empty($data['form_submit'])) {
            if (! empty($data['user_email']) && erp_email_valid($data['user_email'])) {
                $data['user_email'] = erp_email_valid($data['user_email']);
            }

            $recipients = get_email_recipients($data['customer_type'], $data['notification'], $account, $reseller, $admin);

            foreach ($recipients as $key => $val) {
                if ($key == 'to_email' && empty($data[$key])) {
                    $data[$key] = $val;
                } elseif ($key != 'to_email') {
                    $data[$key] = $val;
                }
            }
        }
    }

    $admin_settings = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();
    if (! empty($data['use_accounts_email']) && $data['use_accounts_email'] == true) {
        $data['from_email'] = $admin_settings->notification_account;
    }

    if (! empty($data['notification'])) {
        $message_template = $data['notification'];

        if ($notification->from_email == 'No Reply') {
            $from_email_arr = explode('@', $admin_settings->notification_helpdesk);
            $data['from_email'] = 'no-reply@'.$from_email_arr[1];
        }

        if ($notification->from_email == 'Accounting') {
            $data['from_email'] = $admin_settings->notification_account;
        }

        if ($notification->from_email == 'helpdesk') {
            $data['from_email'] = $admin_settings->notification_helpdesk;
        }

        if ($notification->from_email == 'Marketing') {
            $data['from_email'] = $admin_settings->notification_marketing;
        }

        if (isset($admin_settings->notification_helpdesk) && $notification->from_email == 'Helpdesk') {
            $data['from_email'] = $admin_settings->notification_helpdesk;
        }
    }

    if (isset($data['from_email']) && erp_email_valid($data['from_email']) && $account->partner_id == 1) {
        $data['from_email'] = $data['from_email'];
    } elseif (erp_email_valid($message_template->from_email) && $account->partner_id == 1) {
        $data['from_email'] = $message_template->from_email;
    } elseif ($account->partner_id != 1) {
        $data['from_email'] = 'helpdesk.erpcloud.co.za';
    }
    $data['company'] = $account->company;
    if (empty($data['company'])) {
        $data['company'] = $account->contact;
    }

    $data['contact'] = $account->contact;
    $data['customer'] = $account;
    if (! empty($data['customer']) && empty($data['customer']->contact)) {
        $data['customer']->contact = $data['customer']->company;
    }

    if ($account->id == 1) {
        $data['customer']->contact = 'Ahmed Omar';
    }
    $data['account'] = $account;
    $data['reseller'] = $reseller;
    $data['reseller'] = $reseller;
    if ($account->id == 1) {
        $data['parent_company'] = $account->company;
    }
    if (empty($data['parent_company'])) {
        if (! empty($data['from_company'])) {
            $data['parent_company'] = $data['from_company'];
        } else {
            $data['parent_company'] = (! empty($message_template->from_company) && $account->partner_id == 1) ? $message_template->from_company : $reseller->company;
        }
    }
    $data['parent_company'] = str_replace('(Admin)', '', $data['parent_company']);
    $parent_logo = $reseller->logo;
    $data['partner_logo'] = '';
    if (file_exists(uploads_settings_path().$parent_logo)) {
        $data['partner_logo'] = settings_url().$parent_logo;
    }
    $data['parent_logo'] = $data['partner_logo'];
    if (! empty($data['pdf'])) {
        $data['pdf_name'] = (! empty($data['pdf_name'])) ? $data['pdf_name'] : $data['company'];
    }
    $data['subject'] = (! empty($data['subject'])) ? $data['subject'] : erp_email_blend($message_template->subject, $data);
    $data['paynow_button'] = '';
    if (isset($data['show_debit_order_link']) && $reseller->id == 1) {
        if ($data['show_debit_order_link']) {
            $data['paynow_button'] = '<br><br>Debit order required, click the link to submit your debit order and complete your order.<br> <b>'.$data['webform_link'].'</b>';
        } else {
            $data['paynow_button'] = generate_paynow_button($account->id, 100);
        }

    }

    // $data['afriphone_link'] = '<a href="https://play.google.com/store/apps/details?id=com.telecloud.phoneapp">Download Unlimited Mobile.</a>';

    if (! empty($data['escape_email_variables'])) {
        $message_template->message = str_replace('{{', '[', $message_template->message);
        $message_template->message = str_replace('}}', ']', $message_template->message);
    }

    $data['subject'] = erp_email_blend($data['subject'], $data);
    if (! empty($data['message'])) {
        $data['msg'] = $data['message'];
        $data['msg'] = erp_email_blend($message_template->message, $data);
    } else {
        $data['msg'] = erp_email_blend($message_template->message, $data);
    }
    if (! empty($data['disable_blend'])) {
        $data['msg'] = $message_template->message;
    }

    $template_file = '_emails.blank';

    //render gjs
    if (! empty($data['notification'])) {
        $data['subject'] = (! empty($data['subject'])) ? $data['subject'] : $data['notification']->name;
    }
    $data['subject'] = erp_email_blend($data['subject'], $data);

    $log_msg = $data['msg'];

    if (! empty($data['notification'])) {
        if (empty($data['formatted'])) {
            $data['html'] = get_email_html($account_id, $reseller->id, $data, $data['notification']);
            $data['css'] = '';
            $template_file = '_emails.gjs';
        }
    } else {
        $template_file = '_emails.reseller';
    }
    $data['html'] = str_replace('<div class="ms-editor-squiggler"></div>', '', $data['html']);
    $data['html'] = str_replace('<p></p> <br />', '', $data['html']);

    if (isset($data['formatted'])) {
        $template_file = '_emails.blank';
        $data['msg'] = $data['message'];
    }

    if (isset($data['form']) && $data['form'] > '') {
        $template_file = '_emails.blank';
    }
    $log_address = $data['to_email'];
    if (empty($data['notification'])) {
        if (empty($data['msg'])) {
            set_db_connection($current_conn);

            return erp_email_log($account_id, $data, $log_address, $data['subject'], $message_template->name, 0, '');
        }
    }

    if (empty($data['to_email']) && ! empty($data['user_email'])) {
        $data['to_email'] = $data['user_email'];
    }
    if (! empty($data['force_to_email'])) {
        $data['to_email'] = $data['force_to_email'];
    }

    if (! erp_email_valid($data['to_email'])) {
        set_db_connection($current_conn);

        return erp_email_log($account_id, $data, $log_address, $data['subject'], $message_template->name, 0, 'Invalid Email Address.');
    }

    if (! empty($message_template->template_file)) {
        $template_file = $message_template->template_file;
    }

    if (! empty($data['template_file'])) {
        $template_file = $data['template_file'];
    }

    //$data['use_alt_smtp'] =1; //icewarp down, use cloudtools
    try {
        /*Set smtp config from db*/
        $mail_config = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();
        if (! is_dev()) {
            // $data['bulk_smtp'] = 1;
        }
        $alt_smtp = false;

        $smtp_host = $mail_config->smtp_host;
        $smtp_port = $mail_config->smtp_port;
        $smtp_username = $mail_config->smtp_username;
        $smtp_password = $mail_config->smtp_password;
        $smtp_encryption = $mail_config->smtp_encryption;

        /*
        if (is_dev()) {
        }
        */

        if (! empty($smtp_host) && ! empty($smtp_port)
            && ! empty($smtp_username) && ! empty($smtp_password)) {
            \Config::set('mail.host', $smtp_host);
            \Config::set('mail.port', $smtp_port);
            \Config::set('mail.username', $smtp_username);
            \Config::set('mail.password', $smtp_password);
            if ($smtp_encryption == 'none') {
                $smtp_encryption = 'none';
            }

            \Config::set('mail.encryption', $smtp_encryption);

        }

        if (is_dev()) {
        }
        /*
        aa($smtp_host);
        aa($smtp_port);
        aa($smtp_username);
        aa($smtp_password);
        aa($smtp_encryption);
        */
        $data['smtp_host'] = \Config::get('mail.host');
        $data['msg'] = str_replace('czone.telecloud', 'czone.cloudtools', $data['msg']);
        // $data['msg'] = str_replace('portal.telecloud', 'czone.cloudtools', $data['msg']);
        if (empty($data['disable_blend'])) {
            $data['subject'] = erp_email_blend($data['subject'], $data);
        }

        if (isset($data['email_subject'])) {
            $data['subject'] = $data['email_subject'];
        }

        //$data['test_debug'] = 1;
        //$data['to_email'] = 'ahmed@telecloud.co.za';
        //$data['subject'] = 'test icewarp '.$data['subject'];
        //$data['test_debug'] = 1;
        if ($data['test_debug']) {
            $data['subject'] = 'Debug - '.$data['subject'];
            unset($data['user_email']);
            unset($data['cc_email']);
            unset($data['cc_emails']);
            unset($data['bcc_email']);
            unset($data['bcc_admin']);
            if (str_contains($data['test_debug'], '@')) {
                $data['to_email'] = $data['test_debug'];
            } else {

                $data['to_email'] = 'ahmed@telecloud.co.za';
                //  $data['cc_email'] = 'ahmed@telecloud.co.za';
                //$data['to_email'] = 'landmanahmed@gmail.com';
            }
        }

        if (isset($data['user_email']) && $data['user_email'] == $data['to_email']) {
            unset($data['user_email']);
            unset($data['cc_email']);
            unset($data['cc_emails']);
        }

        if (isset($data['cc_email']) && $data['cc_email'] == $data['to_email']) {
            unset($data['cc_email']);
        }

        if (isset($data['bcc_email']) && $data['bcc_email'] == $data['to_email']) {
            unset($data['bcc_email']);
        }

        if ($template_file == '_emails.gjs') {
            $data['html'] = str_ireplace('[newsletter_footer]', '', $data['html']);
        } else {
            $data['msg'] = str_ireplace('[newsletter_footer]', '', $data['msg']);
        }

        if (isset($data['from_email']) && $data['from_email'] == 'ahmed@telecloud.co.za') {
            $data['from_email'] = 'helpdesk@telecloud.co.za';
        }

        if (! empty($data['attachments']) && count($data['attachments']) > 1) {
            $data['attachments'] = collect($data['attachments'])->unique()->toArray();
        }

        if (! empty($data['files']) && count($data['files']) > 1) {
            $data['files'] = collect($data['files'])->unique()->toArray();
        }

        if ($data['force_to_email']) {
            unset($data['user_email']);
            unset($data['cc_email']);
            unset($data['cc_emails']);
            unset($data['bcc_email']);
            $data['to_email'] = $data['force_to_email'];
        }
        if (isset($data['force_cc_email'])) {
            $data['cc_email'] = $data['force_cc_email'];
        }

        if (! empty($data['bcc_admin'])) {
            $data['bcc_email'] = 'ahmed@telecloud.co.za';
        }

        if (! empty($data['cc_admin'])) {
            $data['cc_email'] = 'ahmed@telecloud.co.za';
        }
        if (! empty($data['cc_accounts'])) {
            $data['cc_email'] = 'kola@telecloud.co.za';
        }

        if (isset($data['cc_email'])) {
            if (empty($data['cc_emails'])) {
                $data['cc_emails'] = [$data['cc_email']];
            } else {
                if (! is_array($data['cc_emails'])) {
                    $data['cc_emails'] = [$data['cc_emails']];
                }
                $data['cc_emails'][] = $data['cc_email'];
            }
        }

        if (! empty($data['cc_helpdesk_email'])) {
            $data['cc_emails'] = [$data['cc_helpdesk_email']];
        }

        if (! empty($message_template) && ! empty($message_template->to_email)) {
            if ($message_template->to_email == 'Account - helpdesk' && ! empty($account->contact_name_2)) {
                $data['customer']->contact = $account->contact_name_2;
            }

            if ($message_template->to_email == 'Account - Accounting' && ! empty($account->contact_name_3)) {
                $data['customer']->contact = $account->contact_name_3;
            }

            if ($message_template->to_email == 'Reseller - helpdesk' && ! empty($reseller->contact_name_2)) {
                $data['customer']->contact = $reseller->contact_name_2;
            }

            if ($message_template->to_email == 'Reseller - Accounting' && ! empty($reseller->contact_name_3)) {
                $data['customer']->contact = $reseller->contact_name_3;
            }
        }

        $data['html'] = str_replace('class="img-container', 'class="img-container email-logo', $data['html']);

        $data['alt_smtp'] = $alt_smtp;

        if ($account->partner_id != 1) {
            $smtp_host = 'mail.cloudtools.co.za';
            $smtp_port = 587;
            $smtp_username = 'helpdesk@cloudtools.co.za';
            $smtp_password = 'Webmin786';
            $smtp_encryption = 'ssl';

            $data['reply_email'] = $reseller->email;
            $data['reply_company'] = $data['parent_company'];
            $data['from_email'] = $smtp_username;
        } else {
            $data['reply_email'] = isset($data['from_email']) ? $data['from_email'] : '';
            $data['reply_company'] = isset($data['parent_company']) ? $data['parent_company'] : '';
            //$data['from_email'] = $smtp_username;
        }

        // if(is_dev()){
        $data['use_symfony'] = 1;
        // }
        if (empty($data['from_email'])) {
            $data['from_email'] = $smtp_username;
        }

        if (str_contains($data['from_email'], '@telecloud')) {
            $data['from_email'] = $smtp_username;
        }
        //if(!empty($data['event_subject'])){
        //    $data['subject'] = $data['event_subject'];
        //}

        // if(is_dev()){

        // erp_email_log($account_id, $data, $data['to_email'], $data['subject'], '', 0, '');
        // return false;
        // }
        // return false;

        // if(is_dev()){
        //    $html = view($template_file, $data)->render();
        //    echo $html;
        //    erp_email_log($account_id, $data, $data['to_email'], $data['subject'], '', 0, '');
        //dd($data);
        //     return false;
        // }

        if (! empty($data['use_symfony'])) {
            $transport = Symfony\Component\Mailer\Transport::fromDsn('smtp://'.$smtp_username.':'.$smtp_password.'@'.$smtp_host.':'.$smtp_port.'?verify_peer=0');
            $mailer = new Symfony\Component\Mailer\Mailer($transport);
            $html = view($template_file, $data)->render();

            $email = (new Symfony\Component\Mime\Email)->subject($data['subject'])->html($html);
            $email->from(new Symfony\Component\Mime\Address($data['from_email'], $data['parent_company']));
            $email->to(new Symfony\Component\Mime\Address($data['to_email'], $data['company']));
            if (! empty($data['reply_email']) && ! empty($data['reply_company'])) {
                $email->replyTo(new Symfony\Component\Mime\Address($data['reply_email'], $data['reply_company']));
            }

            if (isset($data['cc_emails'])) {
                if (! empty($data['cc_emails']) && is_array($data['cc_emails']) && count($data['cc_emails']) > 0) {
                    foreach ($data['cc_emails'] as $cc_email) {
                        if ($cc_email != $data['to_email']) {
                            $email->cc($cc_email);
                        }
                    }
                }
            }

            if (isset($data['user_email'])) {
                $email->cc($data['user_email']);
            }

            if (isset($data['bcc_email'])) {
                $email->bcc($data['bcc_email']);
            }

            if (is_dev()) {
                $email->returnPath('ahmed@telecloud.co.za');
            }

            $email->subject($data['subject']);

            if (isset($data['delivery_confirmation'])) {
                $email->getHeaders()->addTextHeader('X-Confirm-Reading-To', 'helpdesk@telecloud.co.za');
                $email->getHeaders()->addTextHeader('Disposition-Notification-To', 'helpdesk@telecloud.co.za');
                $email->getHeaders()->addTextHeader('Return-Receipt-To', 'helpdesk@telecloud.co.za');
            }

            if (! empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    $email->attachFromPath(attachments_path().$attachment, $attachment);
                }
            }
            if (! empty($data['files']) && is_array($data['files'])) {
                foreach ($data['files'] as $file) {
                    $email->attachFromPath($file);
                }
            }
            try {
                $result = $mailer->send($email);

                if ($result == null) {
                    $log_msg = view($template_file, $data)->render();
                    erp_email_log($account_id, $data, $data['to_email'], $data['subject'], $log_msg, 1);
                    set_db_connection($current_conn);

                    return 'Sent';
                } else {
                    $log_msg = view($template_file, $data)->render();

                    erp_email_log($account_id, $data, $data['to_email'], $data['subject'], $log_msg, 0, $result);
                    set_db_connection($current_conn);

                    return $result;
                }
            } catch (Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                $result = $e->getMessage();

                $log_msg = view($template_file, $data)->render();

                erp_email_log($account_id, $data, $data['to_email'], $data['subject'], $log_msg, 0, $result);
                set_db_connection($current_conn);

                return $result;
            }
        } else {
            //////////////////////////////////////////////////////////////////////////////

            Mail::send($template_file, $data, function ($email) use ($data) {
                $email->from($data['from_email'], $data['parent_company']);
                $email->to($data['to_email'], $data['company']);

                if (! empty($data['reply_email']) && ! empty($data['reply_company'])) {
                    $email->replyTo($data['reply_email'], $data['reply_company']);
                }

                if (isset($data['cc_emails'])) {
                    if (! empty($data['cc_emails']) && is_array($data['cc_emails']) && count($data['cc_emails']) > 0) {
                        foreach ($data['cc_emails'] as $cc_email) {
                            if ($cc_email != $data['to_email']) {
                                $email->cc($cc_email);
                            }
                        }
                    }
                }

                if ($data['user_email']) {
                    $email->cc($data['user_email']);
                }

                if ($data['bcc_email']) {
                    $email->bcc($data['bcc_email']);
                }

                if (is_dev()) {
                    $email->setReturnPath('ahmed@telecloud.co.za');
                }

                $email->subject($data['subject']);
                $email->addPart('text test', 'text/plain');

                if ($data['delivery_confirmation']) {
                    $email->getHeaders()->addTextHeader('X-Confirm-Reading-To', 'helpdesk@telecloud.co.za');
                    $email->getHeaders()->addTextHeader('Disposition-Notification-To', 'helpdesk@telecloud.co.za');
                    $email->getHeaders()->addTextHeader('Return-Receipt-To', 'helpdesk@telecloud.co.za');
                }

                // bounce
                $email->getHeaders()->addTextHeader('Return-Path', 'helpdesk@telecloud.co.za');
                $email->getHeaders()->addTextHeader('X-Delivery-ID', $data['account_id'].' '.date('U'));
                $email->getHeaders()->addTextHeader('Message-ID', $data['account_id'].' '.date('U'));

                if (! empty($data['attachments']) && is_array($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        $email->attach(attachments_path().$attachment);
                    }
                }
                if (! empty($data['files']) && is_array($data['files'])) {
                    foreach ($data['files'] as $file) {
                        $email->attach($file);
                    }
                }
            });

            $msg_name = (! empty($message_template->name)) ? $message_template->name : $data['msg'];

            if (count(Mail::failures()) > 0) {
                $error_msg = '';
                foreach (Mail::failures() as $failure) {
                    $error_msg .= $failure;
                }
                $log_msg = view($template_file, $data)->render();
                $error = 'Email Failed - '.$data['to_email'].' - '.$error_msg;

                erp_email_log($account_id, $data, $data['to_email'], $data['subject'], $log_msg, 0, $error);
                set_db_connection($current_conn);

                return 'Error';
            } else {
                $log_msg = view($template_file, $data)->render();
                erp_email_log($account_id, $data, $data['to_email'], $data['subject'], $log_msg, 1);
                set_db_connection($current_conn);

                return 'Sent';
            }
        }
    } catch (\Throwable $ex) {
        exception_log($ex);
        erp_email_log($account_id, $data, $data['to_email'], $data['subject'], $log_msg, 0, $ex->getMessage());
        exception_email($ex, 'Email error '.date('Y-m-d H:i'));

        return $ex->getMessage();
    }
}

function email_queue_add($account_id, $data = [], $function_variables = [])
{
    $queue = [
        'created_at' => date('Y-m-d H:i:s'),
        'processed' => 0,
        'in_progress' => 0,
        'account_id' => $account_id,
        'mail_data' => json_encode($data, true),
        'function_variables' => json_encode($function_variables, true),
    ];

    if (! empty($data['notification_id'])) {
        $queue['email_id'] = $data['notification_id'];
    }

    $processed = 0;
    if (empty($data['ignore_queue_check'])) {
        if (! empty($data['notification_id']) && ! empty($account_id)) {
            $processed = \DB::table('erp_mail_queue')->where('account_id', $account_id)->where('email_id', $data['notification_id'])->where('created_at', '>=', date('Y-m-d'))->count();
        }
    }

    if (! $processed) {
        dbinsert('erp_mail_queue', $queue);
    }
}

function schedule_email_queue_process()
{
    $email_queue = \DB::table('erp_mail_queue')->where('processed', 0)->orderby('id', 'desc')->limit(50)->get();
    $queue_ids = collect($email_queue)->pluck('id')->toArray();
    \DB::table('erp_mail_queue')->whereIn('id', $queue_ids)->update(['in_progress' => 1]);
    try {
        foreach ($email_queue as $queue) {
            $mail_data = json_decode($queue->mail_data, true);
            $function_variables = json_decode($queue->function_variables, true);
            $mail_data['mail_queue_id'] = $queue->id;
            $result = erp_process_notification($queue->account_id, $mail_data, $function_variables);
            \DB::table('erp_mail_queue')->where('id', $queue->id)->update(['in_progress' => 0, 'processed' => 1, 'processed_at' => date('Y-m-d H:i:s')]);
        }
    } catch (\Throwable $ex) {
        \DB::table('erp_mail_queue')->whereIn('id', $queue_ids)->where('processed', 0)->update(['in_progress' => 0]);
    }
}

function process_email_queue()
{
    $email_queue = \DB::table('erp_mail_queue')->where('processed', 0)->orderby('id', 'desc')->limit(10)->get(); //->where('in_progress', 0)
    $queue_ids = collect($email_queue)->pluck('id')->toArray();
    \DB::table('erp_mail_queue')->whereIn('id', $queue_ids)->update(['in_progress' => 1]);
    try {
        foreach ($email_queue as $queue) {
            $mail_data = json_decode($queue->mail_data, true);
            $function_variables = json_decode($queue->function_variables, true);
            $mail_data['mail_queue_id'] = $queue->id;
            $result = erp_process_notification($queue->account_id, $mail_data, $function_variables);
            \DB::table('erp_mail_queue')->where('id', $queue->id)->update(['in_progress' => 0, 'processed' => 1, 'processed_at' => date('Y-m-d H:i:s')]);

        }
    } catch (\Throwable $ex) {
        \DB::table('erp_mail_queue')->whereIn('id', $queue_ids)->where('processed', 0)->update(['in_progress' => 0]);
    }
}

function resend_mail_from_history($id, $debug = false)
{

    $email = \DB::table('erp_communication_lines')->where('id', $id)->where('type', 'email')->get()->first();
    if (empty($email) || empty($email->id)) {
        return false;
    }
    \DB::table('erp_communication_lines')->where('id', $id)->update(['success' => 0]);
    $mail_config = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();

    $smtp_host = $mail_config->smtp_host;
    $smtp_port = $mail_config->smtp_port;
    $smtp_username = $mail_config->smtp_username;
    $smtp_password = $mail_config->smtp_password;
    $smtp_encryption = $mail_config->smtp_encryption;

    $template_file = '_emails.blank';
    $data['msg'] = $email->message;
    $account = dbgetaccount($email->account_id);
    $reseller = dbgetaccount($account->partner_id);

    $data['from_email'] = $email->source;
    $data['to_email'] = $email->destination;
    if (! empty($email->bcc_email)) {
        $data['bcc_email'] = $email->bcc_email;
    }
    if (! empty($email->cc_email)) {
        $data['cc_emails'] = explode(',', $email->cc_email);
    }
    if (! empty($email->attachments)) {
        $data['attachments'] = explode(',', $email->attachments);
    }

    if ($reseller->id != 1) {
        $data['reply_email'] = $reseller->email;
    } else {
        $data['reply_email'] = $data['from_email'];
    }
    $data['reply_company'] = $reseller->company;
    $data['parent_company'] = $reseller->company;
    $data['company'] = $account->company;

    $data['subject'] = $email->subject.' - '.$account->company.' '.date('M Y');

    if ($debug) {
        unset($data['bcc_email']);
        unset($data['cc_emails']);
        $data['to_email'] = 'landmanahmed@gmail.com';
        $data['cc_emails'] = ['ahmed@telecloud.co.za'];
        // $data['to_email'] = 'ahmed@telecloud.co.za';

        //  $smtp_username = 'ahmed@telecloud.co.za';
        //  $smtp_password = 'Ao@147896';
    }

    $transport = Symfony\Component\Mailer\Transport::fromDsn('smtp://'.$smtp_username.':'.$smtp_password.'@'.$smtp_host.':'.$smtp_port.'?verify_peer=0');
    $mailer = new Symfony\Component\Mailer\Mailer($transport);
    $html = view($template_file, $data)->render();
    $email = (new Symfony\Component\Mime\Email)->subject($data['subject'])->html($html);
    $email->from(new Symfony\Component\Mime\Address($data['from_email'], $data['parent_company']));
    $email->to(new Symfony\Component\Mime\Address($data['to_email'], $data['company']));

    if (! empty($data['reply_email']) && ! empty($data['reply_company'])) {
        $email->replyTo(new Symfony\Component\Mime\Address($data['reply_email'], $data['reply_company']));
    }

    if (isset($data['cc_emails'])) {
        if (! empty($data['cc_emails']) && is_array($data['cc_emails']) && count($data['cc_emails']) > 0) {
            foreach ($data['cc_emails'] as $cc_email) {
                if ($cc_email != $data['to_email']) {
                    $email->cc($cc_email);
                }
            }
        }
    }

    if ($data['bcc_email']) {
        $email->bcc($data['bcc_email']);
    }

    $email->subject($data['subject']);

    if (! empty($data['attachments']) && is_array($data['attachments'])) {
        foreach ($data['attachments'] as $attachment) {
            $email->attachFromPath(attachments_path().$attachment, $attachment);
        }
    }

    try {
        $result = $mailer->send($email);
        if ($result == null) {

            \DB::table('erp_communication_lines')->where('id', $id)->update(['success' => 1, 'error' => '']);

            return 'Sent';
        } else {
            \DB::table('erp_communication_lines')->where('id', $id)->update(['success' => 0, 'error' => $result]);

            return $result;
        }
    } catch (Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
        $result = $e->getMessage();
        \DB::table('erp_communication_lines')->where('id', $id)->update(['success' => 0, 'error' => $result]);

        return $result;
    }
}

function erp_email_blend($blend, $data)
{

    // $blend = str_replace('</p><p>','</p> <br /> <p>',$blend);

    return view(['template' => $blend])->with($data)->render();
}

function erp_email_log($account_id, $data, $email, $subject, $message, $success, $error = '')
{
    if ($account_id == 1) {
        $account_id = 0;
    }

    if (empty($subject)) {
        $subject = '';
    }

    $log = [
        'destination' => $email,
        'source' => isset($data['from_email']) ? $data['from_email'] : '',
        'subject' => $subject,
        'message' => $message,
        'success' => $success,
        'error' => $error,
        'created_at' => date('Y-m-d H:i:s'),
        'type' => 'email',
        'bcc_email' => '',
        'cc_email' => '',
        'attachments' => '',
    ];

    if (! empty($data['mail_queue_id'])) {
        $log['mail_queue_id'] = $data['mail_queue_id'];
    }
    if (! empty($data['smtp_host'])) {
        $log['smtp_host'] = $data['smtp_host'];
    }

    if (! empty($data['attachments']) && is_array($data['attachments']) && count($data['attachments']) > 0) {
        $log['attachments'] = implode(',', $data['attachments']);
    }

    if (session('instance')->id != 11 && $success && ! empty($data['notification']->attach_letter_of_demand)) {
        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['demand_sent' => 1]);
        \DB::connection('default')->table('crm_written_off')->where('account_id', $account_id)->update(['demand_sent' => 1]);
    }

    if (! empty($data['communication_id'])) {
        $log['communication_id'] = $data['communication_id'];
    } else {
        $logdata = [
            'subject' => $subject,
            'type' => 'email',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if (! empty($data['notification']->id)) {
            $logdata['email_id'] = $data['notification']->id;
        }
        if (! empty($data['notification']->to_email)) {
            $logdata['to_email'] = $data['notification']->to_email;
        }
        if (! empty($data['notification']->cc_email)) {
            $logdata['cc_email'] = $data['notification']->cc_email;
        }
        if (! empty($data['notification']->bcc_email)) {
            $logdata['bcc_email'] = $data['notification']->bcc_email;
        }

        $communication_id = \DB::table('erp_communications')->insertGetId($logdata);
        $data['communication_id'] = $communication_id;
        $log['communication_id'] = $communication_id;
    }

    if (isset($data['user_email']) && $data['user_email'] != $data['user_email']) {
        $log['cc_email'] .= $data['user_email'].', ';
    }

    if (isset($data['cc_emails'])) {
        if (! empty($data['cc_emails']) && is_array($data['cc_emails']) && count($data['cc_emails']) > 0) {
            foreach ($data['cc_emails'] as $cc_email) {
                if ($cc_email != $data['to_email']) {
                    $log['cc_email'] .= $cc_email.', ';
                    $sent_to[] = trim($cc_email);
                }
            }
        } elseif (! empty($data['cc_emails']) && ! is_array($data['cc_emails'])) {
            $log['cc_email'] .= $data['cc_emails'].', ';
            $sent_to[] = trim($data['cc_emails']);
        }
    } else {
        if (isset($data['cc_email']) && $data['cc_email']) {
            $log['cc_email'] .= $data['cc_email'].', ';
            $sent_to[] = trim($data['cc_email']);
        }
    }

    $log['cc_email'] = rtrim($log['cc_email'], ', ');
    if (isset($data['bcc_email'])) {
        $log['bcc_email'] = $data['bcc_email'];
    }

    $log['account_id'] = $account_id;

    if (! empty(session('user_id'))) {
        $log['created_by'] = get_user_id_default();
    }

    if (! empty($data['notification_id'])) {
        $log['email_id'] = $data['notification_id'];
    } elseif (! empty($data['notification']) && ! empty($data['notification']->id)) {
        $log['email_id'] = $data['notification']->id;
    }

    \DB::table('erp_communication_lines')->insert($log);

    if (! empty($data['communication_id'])) {
        $success_count = \DB::table('erp_communication_lines')->where('communication_id', $data['communication_id'])->where('success', 1)->count();
        $error_count = \DB::table('erp_communication_lines')->where('communication_id', $data['communication_id'])->where('success', 0)->count();
        $send_count = \DB::table('erp_communication_lines')->where('communication_id', $data['communication_id'])->count();
        \DB::table('erp_communications')->where('id', $data['communication_id'])->update(['send_count' => $send_count, 'success_count' => $success_count, 'error_count' => $error_count]);
    }

    if (! empty($log['account_id']) && $log['error'] == 'Invalid Email Address.') {
        if (! $success) {
            $exists = \DB::table('crm_invalid_contacts')->where('email', $data['to_email'])->where('account_id', $account_id)->count();
            if (! $exists && ! empty($data['to_email'])) {
                \DB::table('crm_accounts')->where('account_id', $account_id)->update(['notification_type' => 'sms']);
                $data = [
                    'type' => 'email',
                    'email' => $data['to_email'],
                    'phone' => '',
                    'account_id' => $account_id,
                    'created_at' => date('Y-m-d'),
                ];
                \DB::table('crm_invalid_contacts')->insert($data);
            }
        }
    }
    $success_msg = 'Email sent to <br>';
    $sent_to[] = trim($data['to_email']);
    if (isset($data['user_email'])) {
        $sent_to[] = trim($data['user_email']);
    }

    if (isset($data['cc_email'])) {
        $sent_to[] = trim($data['cc_email']);
    }

    if (isset($data['bcc_email'])) {
        $sent_to[] = trim($data['bcc_email']);
    }
    $sent_to = collect($sent_to)->unique()->toArray();
    $success_msg .= implode('<br>', $sent_to);

    if (empty($data['exception_email']) && session('role_level') == 'Admin') {
        session(['email_result' => ($success) ? 'success' : 'error']);
        session(['email_message' => ($success) ? $success_msg : $error]);
        session(['email_mod_id' => session('mod_id')]);
    }

    return $error;
}

function erp_email_valid($email_address)
{
    if (! empty($email_address) && filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
        return $email_address;
    } else {
        return false;
    }
}

function erp_email_unique($email_address)
{
    if (! erp_email_valid($email_address)) {
        return 'Invalid Email';
    }
    $account_email_exists = \DB::table('crm_accounts')->where('email', $email_address)->count();
    if ($account_email_exists) {
        return 'Email already registered';
    }

    return $email_address;
}

function erp_email_test()
{
    $data['notification_id'] = '344';
    $data['subject'] = 'check line breaks';
    $data['msg'] = '<p>test</p>';
    $data['add_unsubscribe'] = 1;
    // $data['to_email'] = 'bouncetest@tribulant.com';
    // $data['from_email'] = 'info@cloudtools.co.za';

    // $data['from_email'] = 'info@cloudtools.co.za';
    //
    $data['force_to_email'] = 'ahmed@telecloud.co.za';
    $data['cc_email'] = 'ahmed@telecloud.co.za';
    // $data['force_to_email'] = 'ahmed@telecloud.co.za';
    $data['force_to_email'] = 'landmanahmed@gmail.com';

    //$data['force_to_email'] = 'landmanahmed@gmail.com';
    $data['form_submit'] = 1;

    // $data['test_debug'] = 1;
    //$data['reply_email'] = 'helpdesk@telecloud.co.za';
    //  $data['reply_company'] = 'telecloud';
    //$data['bulk_smtp'] = 1;
    //  $data['use_alt_smtp'] = 1;
    $data['use_symfony'] = 1;
    $data['test_debug'] = 1;
    $r = erp_email_send(333, $data);
}

function erp_symfony_test()
{
    $data['notification_id'] = '89';
    $data['subject'] = 'check gmail inbox';
    $data['msg'] = '<p>test</p>';
    $data['add_unsubscribe'] = 1;
    // $data['to_email'] = 'bouncetest@tribulant.com';
    $data['from_email'] = 'helpdesk@telecloud.co.za';
    $data['to_email'] = 'ahmed@telecloud.co.za';
    $data['force_to_email'] = 'ahmed@telecloud.co.za';

    $data['form_submit'] = 1;
    $data['test_debug'] = 1;
    //$data['reply_email'] = 'helpdesk@telecloud.co.za';
    //  $data['reply_company'] = 'telecloud';
    //$data['bulk_smtp'] = 1;
    $data['use_alt_smtp'] = 0;
    $data['use_symfony'] = 1;
    $r = erp_email_send(1, $data);
}

function exception_email($ex, $subject, $post_data = false, $link = false)
{
    // aa($ex);
    // aa($subject);
    // aa($post_data);
    //return false;
    $current_conn = \DB::getDefaultConnection();

    set_db_connection();
    $exception = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
    $exception = date('Y-m-d H:i').'<br><br>'.$exception;
    if ($post_data && is_array($post_data)) {
        $post_msg = 'Post Data:<br>';
        foreach ($post_data as $k => $v) {
            $post_msg = $k.':'.$v.'<br>';
        }
        $exception = $post_msg.'<br><br>'.$exception;
    }

    if (! empty(session('user_id'))) {
        $user = \DB::connection('default')->table('erp_users')->where('id', session('user_id'))->get()->first();
        $exception = 'User Name: '.$user->full_name.' User Id: '.$user->id.'<br><br>'.$exception;
    }
    $stack_trace = nl2br($ex->getTraceAsString());
    $function_variables = get_defined_vars();

    $data['internal_function'] = __FUNCTION__;
    $data['exception_email'] = true;
    $data['test_debug'] = 1;
    if ($link) {
        $data['event_link'] = $link;
    }

    $data['force_to_email'] = 'ahmed@telecloud.co.za';
    //$data['force_to_email'] = 'landmanahmed@gmail.com';

    erp_process_notification(1, $data, $function_variables, 'default');
    set_db_connection($current_conn);
}

function debug_email($subject, $var = null)
{
    $current_conn = \DB::getDefaultConnection();

    set_db_connection();
    if (empty($var)) {
        $var = $subject;
    }

    $function_variables = get_defined_vars();
    $data['internal_function'] = 'debug_email';
    $data['exception_email'] = true;
    $data['test_debug'] = 1;
    $data['use_alt_smtp'] = 1;
    $data['var'] = $var;

    //$data['force_to_email'] = 'landmanahmed@gmail.com';
    erp_process_notification(1, $data, $function_variables);
    set_db_connection($current_conn);
}

function dev_email($subject, $var = null)
{
    $current_conn = \DB::getDefaultConnection();

    set_db_connection();
    if (empty($var)) {
        $var = $subject;
    }

    $function_variables = get_defined_vars();
    $data['internal_function'] = 'debug_email';
    $data['exception_email'] = true;
    $data['to_email'] = 'ahmed@telecloud.co.za';
    $data['form_submit'] = 1;
    $data['var'] = $var;
    $data['use_alt_smtp'] = 1;

    return erp_process_notification(1, $data, $function_variables);
    set_db_connection($current_conn);
}

function admin_email($subject, $var = null)
{
    $current_conn = \DB::getDefaultConnection();
    set_db_connection($current_conn);
    if (empty($var)) {
        $var = $subject;
    }
    $function_variables = get_defined_vars();
    $data['internal_function'] = 'debug_email';
    $data['exception_email'] = true;
    $data['to_email'] = 'ahmed@telecloud.co.za';
    //$data['cc_email'] = 'ahmed@telecloud.co.za';
    // $data['to_email'] = 'ahmed@telecloud.co.za';
    $data['form_submit'] = 1;
    $data['var'] = nl2br($var);

    return erp_process_notification(1, $data, $function_variables);
}

function staff_email($user_id, $subject, $msg, $cc_email = '')
{
    $current_conn = \DB::getDefaultConnection();

    set_db_connection();
    $user_email = \DB::table('erp_users')->where('id', $user_id)->where('account_id', 1)->pluck('email')->first();
    if ($user_email) {
        $data['subject'] = $subject;
        $data['message'] = nl2br($msg);
        $data['to_email'] = $user_email;
        if ($cc_email) {
            $data['cc_email'] = $cc_email;
        }
        $data['form_submit'] = 1;
        $data['formatted'] = 1;

        //$data['var'] = nl2br($msg);
        // $data['internal_function'] = 'debug_email';
        //$data['test_debug'] = $debug;
        return erp_process_notification(1, $data);
    }
    set_db_connection($current_conn);
}

function accounts_email($subject, $msg, $debug = 0)
{
    $data['subject'] = $subject;
    $data['message'] = nl2br($msg);
    $data['to_email'] = 'accounts@telecloud.co.za';
    $data['form_submit'] = 1;
    $data['formatted'] = 1;
    $data['test_debug'] = $debug;

    return erp_process_notification(1, $data);
}

function helpdesk_email($subject, $msg, $debug = 0)
{
    $data['subject'] = $subject;
    $data['message'] = nl2br($msg);
    $data['to_email'] = 'helpdesk@telecloud.co.za';
    $data['form_submit'] = 1;
    $data['formatted'] = 1;
    $data['test_debug'] = $debug;

    return erp_process_notification(1, $data);
}

function support_email($subject, $msg, $debug = 0)
{
    $data['subject'] = $subject;
    $data['message'] = nl2br($msg);
    $data['to_email'] = 'helpdesk@telecloud.co.za';
    $data['form_submit'] = 1;
    $data['formatted'] = 1;
    $data['test_debug'] = $debug;

    return erp_process_notification(1, $data);
}

function get_email_logo($partner_id, $conn = 'default')
{
    $partner_settings = \DB::connection($conn)->table('crm_account_partner_settings')->where('account_id', $partner_id)->get()->first();

    $settings_path = uploads_settings_path();
    if ($conn != 'default') {
        $settings_path = str_replace(session('instance')->directory, $conn, $settings_path);
    }

    if (! empty($partner_settings->logo) && file_exists($settings_path.$partner_settings->logo)) {
        $email_logo = \DB::connection($conn)->table('crm_account_partner_settings')->where('account_id', $partner_id)->pluck('logo')->first();
    } else {
        $email_logo = '';
    }

    return settings_url().'/'.$email_logo;
}

function get_email_css()
{
    return \Storage::disk('templates')->get('notification_css.txt');
}

function get_email_html($account_id, $reseller_id, $data, $template = false)
{
    $account = dbgetaccount($account_id);
    $reseller_id = $account->partner_id;

    if ($reseller_id != 1) {
        $html = \Storage::disk('templates')->get(session('instance')->directory.'/notification_reseller_html.txt');
    } else {
        $html = \Storage::disk('templates')->get(session('instance')->directory.'/notification_html.txt');
    }

    // add footer to email html
    if ($reseller_id == 1) {
        $footer_img_file = \DB::connection('system')->table('crm_shopify_integrations')->where('instance_id', session('instance')->id)->pluck('email_template')->first();

        if (! empty($footer_img_file)) {
            $html = str_replace('[footer_image]', '<img style="max-width:600px" width="600px" src="https://portal.telecloud.co.za/uploads/telecloud/1879/'.$footer_img_file.'" />', $html);
        }
    } else {
        $html = str_replace('[footer_image]', '', $html);
    }

    $css = \Storage::disk('templates')->get(session('instance')->directory.'/notification_css.txt');
    $css = str_replace('transparent', '#fff', $css);
    $html .= '<style>'.$css.'</style>';

    $html = str_replace('&gt;', '>', $html);
    $html = str_replace('/get_email_logo.png', '/get_email_logo.png/{{$account_id}}', $html);
    if (! empty($data['email_logo_conn'])) {
        $email_logo = str_replace(request()->root(), '', get_email_logo($reseller_id, $data['email_logo_conn']));
        $email_logo = str_replace(session('instance')->directory, $data['email_logo_conn'], $email_logo);
    } else {
        $email_logo = str_replace(request()->root(), '', get_email_logo($reseller_id));
    }
    $html = str_replace('/get_email_logo.png/{{$account_id}}', $email_logo, $html);
    $data['helpdesk_logo'] = '';

    if (Schema::connection('default')->hasColumn('crm_account_partner_settings', 'helpdesk_logo')) {
        $helpdesk_logo = \DB::table('crm_account_partner_settings')->where('id', $reseller_id)->pluck('helpdesk_logo')->first();
        if (! empty($helpdesk_logo)) {
            $data['helpdesk_logo'] = '<a target="_blank"><img class="adapt-img" src="https://'.session('instance')->domain_name.'/uploads/'.session('instance')->directory.'/348/'.$helpdesk_logo.'" alt style="display: block;" width="260"></a>';
        }
    }
    //auto embed imgages

    $html = str_replace('<img ', '<img data-auto-embed ', $html);
    if ($data['msg']) {
        $data['msg'] = str_replace('<table', '<table border="1"', $data['msg']);
    }

    $currency_symbol = get_account_currency_symbol($account_id);
    $html = str_replace(' R{', $currency_symbol.'{', $html);
    $html = str_replace(' R ', $currency_symbol.' ', $html);

    $main_instance_domain = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('id', 1)->pluck('domain_name')->first();
    if (! is_main_instance()) {
        $html = str_replace($main_instance_domain, session('instance')->domain_name, $html);
    } elseif (! empty($data['email_logo_conn'])) {
        $instance_domain = \DB::connection('system')->table('erp_instances')->where('installed', 1)->where('db_connection', $data['email_logo_conn'])->pluck('domain_name')->first();
        if ($instance_domain) {
            $html = str_replace($main_instance_domain, $instance_domain, $html);
        }
    }
    $html_lines = explode(PHP_EOL, $html);
    $formatted_lines = [];
    foreach ($html_lines as $line) {
        $formatted_lines[] = $line;
    }
    $html = implode(PHP_EOL, $formatted_lines);
    $html = str_replace('uploads/default/348', 'uploads/'.session('instance')->directory.'/348', $html);
    $html = str_replace('/348//', '/348/', $html);
    $html = str_replace('bgcolor="rgba(0, 0, 0, 0)"', '', $html);

    //remove line breaks
    if ($data['msg']) {
        $data['msg'] = str_replace('<p>', '<p style="margin:0">', $data['msg']);
    }
    $html = str_replace('<p>', '<p style="margin:0">', $html);
    $html = str_replace('<ol><br />', '<ol>', $html);
    $html = str_replace('</ol><br />', '</ol>', $html);
    $html = str_replace('</li><br />', '</li>', $html);

    if ($reseller_id != 1) {
        //  $html = str_replace($main_instance_domain, 'cloudtools.turnkeyerp.io', $html);
    }

    return erp_email_blend($html, $data);
}

function basicmail($account_id, $subject, $msg = '', $template = '', $data = [], $attachData = null, $notify_parent = true)
{
    if (empty($data)) {
        $data = [];
    }
    $data['to'] = dbgetaccountcell($account_id, 'email');
    $data['company'] = dbgetaccountcell($account_id, 'company');
    $data['contact'] = dbgetaccountcell($account_id, 'contact');
    $partner_id = dbgetaccountcell($account_id, 'partner_id');
    $data['parent_email'] = dbgetaccountcell($partner_id, 'email');
    $parent_logo = dbgetaccountcell($partner_id, 'logo');
    $data['parent_logo'] = (! empty($parent_logo) && file_exists(uploads_settings_path().$parent_logo)) ? uploads_settings_path().$parent_logo : '';
    $data['parent_company'] = dbgetaccountcell($partner_id, 'company');

    $data['subject'] = $subject;
    $data['msg'] = $msg;
    $data['attachData'] = $attachData;
    $data['notify_parent'] = $notify_parent;
    if ($template == '') {
        $template = '_emails.basic';
    }

    if (isset($data['to']) && filter_var($data['to'], FILTER_VALIDATE_EMAIL)) {

        //////////////////////////////////////////////////////////////////////////////
        Mail::send($template, $data, function ($email) use ($data) {
            $email->from($data['from_email'], $data['parent_company']);
            $email->to($data['to'], $data['contact']);
            if ($data['notify_parent']) {
                $email->bcc($data['parent_email']);
            }
            $email->subject($data['subject']);
            if ($data['attachData'] != null && $data['attachData'] != '') {
                $email->attachData($data['attachData'], $data['company'].'.pdf');
            }
        });

        if (count(Mail::failures()) > 0) {
            $error = 'Email Failed- '.$data['to'].' - ';
            foreach (Mail::failures() as $mail_error) {
                $error .= $mail_error.' ';
            }

            return $error;
        } else {
            return 'Document sent to customers email - '.$data['to'];
        }
    } else {

        return 'Email Failed- '.$data['to'].' - invalid email';
    }
}

function directmail($email, $subject, $msg, $template = '', $data = null, $attachData = null)
{
    $data['to'] = $email;
    $data['subject'] = $subject;
    $data['msg'] = $msg;

    $data['company'] = dbgetaccountcell(1, 'company');
    $data['contact'] = dbgetaccountcell(1, 'contact');
    $data['parent_email'] = dbgetaccountcell(1, 'email');
    $parent_logo = dbgetaccountcell(1, 'logo');
    $data['parent_logo'] = (! empty($parent_logo) && file_exists(uploads_settings_path().$parent_logo)) ? uploads_settings_path().$parent_logo : '';
    $data['parent_company'] = dbgetaccountcell(1, 'company');

    if ($template == '') {
        $template = '_emails.direct';
    } //setup new template for directmail

    $data['template'] = $template;
    $data['attachData'] = $attachData;
    $mail_config = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();
    if (! empty($mail_config->smtp_host) && ! empty($mail_config->smtp_port)
    && ! empty($mail_config->smtp_username) && ! empty($mail_config->smtp_password)) {
        \Config::set('mail.host', $mail_config->smtp_host);
        \Config::set('mail.port', $mail_config->smtp_port);
        \Config::set('mail.username', $mail_config->smtp_username);
        \Config::set('mail.password', $mail_config->smtp_password);
        if ($mail_config->smtp_encryption == 'none') {
            $mail_config->smtp_encryption = '';
        }

        \Config::set('mail.encryption', $mail_config->smtp_encryption);

        $transport = (new Swift_SmtpTransport($mail_config->smtp_host, $mail_config->smtp_port))
            ->setStreamOptions(['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
        // set encryption
        if (isset($smtp_encryption) && $smtp_encryption != 'none') {
            $transport->setEncryption($mail_config->smtp_encryption);
        }
        // set username and password
        if (isset($mail_config->smtp_username)) {
            $transport->setUsername($mail_config->smtp_username);
            $transport->setPassword($mail_config->smtp_password);
        }
        // set new swift mailer
        Mail::setSwiftMailer(new Swift_Mailer($transport));
    }

    if (isset($data['to'])) {
        //////////////////////////////////////////////////////////////////////////////

        $result = Mail::send($template, $data, function ($email) use ($data) {
            $email->from('helpdesk@telecloud.co.za');
            $email->to($data['to']);
            $email->subject($data['subject']);
            if ($data['attachData'] != null) {
                $email->attachData($data['attachData'], $data['contact'].'.pdf');
            }
            if ($data['cc_email']) {
                $email->cc($data['cc_email']);
            }
        });

        if (Mail::failures()) {
        }
    }
}

function clean_email($email)
{
    return filter_var(strtolower($email), FILTER_SANITIZE_EMAIL);
}

function get_email_recipients($customer_type, $notification, $account, $reseller, $admin)
{
    if ($notification->id == 1) {
        $notification->to_email = 'Account - Manager';
    }

    $admin_settings = \DB::table('erp_admin_settings')->where('id', 1)->get()->first();
    $options = [
        'Logged in User',
        'Account - All',
        'Account - Manager',
        'Account - Accounting',
        'Account - helpdesk',
        'Reseller - All',
        'Reseller - Manager',
        'Reseller - Accounting',
        'Reseller - helpdesk',
        'Admin - Accounting',
        'Admin - helpdesk',
        'Admin - helpdesk',
        'Admin - Helpdesk',
        'Admin - Director',
        'Admin - Manager',
        'Admin - Developer',
        'RSAWEB',
    ];
    $recipients = ['to_email', 'cc_email', 'bcc_email'];
    if (! empty($notification->cc_email)) {
        $recipients[] = 'cc_email';
    }
    if (! empty($notification->bcc_email)) {
        $recipients[] = 'bcc_email';
    }
    $contact_emails = [];
    $reseller_contact_emails = [];
    if ($customer_type == 'supplier') {
        $contact_emails = get_supplier_contacts($account->id);
    }
    if ($customer_type == 'account') {
        $contact_emails = get_account_contacts($account->id);
        $reseller_contact_emails = get_account_contacts($account->partner_id);
    }
    $data = [];

    foreach ($recipients as $recipient) {
        foreach ($options as $opt) {
            if ($notification->{$recipient} == $opt) {
                if ($opt == 'Logged in User') {
                    $user_email = \DB::table('erp_users')->where('account_id', session('user_id'))->pluck('email')->first();
                    $data[$recipient] = $user_email;
                }

                if ($opt == 'RSAWEB') {
                    $data[$recipient] = 'justin.leendertz@rsaweb.net';
                }

                if ($opt == 'Account - All') {
                    $data['cc_emails'] = collect($contact_emails)->pluck('email')->filter()->unique()->toArray();
                    $data['to_email'] = $account->email;
                }

                if ($opt == 'Account - Manager') {
                    $data[$recipient] = $account->email;
                }

                if ($opt == 'Account - helpdesk') {
                    $data[$recipient] = $contact_emails->where('type', 'helpdesk')->pluck('email')->first();
                }
                if ($opt == 'Account - helpdesk') {
                    $data[$recipient] = $contact_emails->where('type', 'helpdesk')->pluck('email')->first();
                }

                if ($opt == 'Account - Accounting') {
                    $data[$recipient] = $contact_emails->where('type', 'Accounting')->pluck('email')->first();
                }

                if ($reseller->id != 1) {
                    if ($opt == 'Reseller - All') {
                        $data['cc_emails'] = collect([$reseller->email, $reseller->contact_email_2, $reseller->contact_email_3])->filter()->unique()->toArray();
                    }

                    if ($opt == 'Reseller - Manager') {
                        $data[$recipient] = $reseller->email;
                    }

                    if ($opt == 'Reseller - helpdesk') {
                        $data[$recipient] = $reseller_contact_emails->where('type', 'helpdesk')->pluck('email')->first();
                    }

                    if ($opt == 'Reseller - helpdesk') {
                        $data[$recipient] = $reseller_contact_emails->where('type', 'helpdesk')->pluck('email')->first();
                    }

                    if ($opt == 'Reseller - Accounting') {
                        $data[$recipient] = $reseller_contact_emails->where('type', 'Accounting')->pluck('email')->first();
                    }
                }

                if ($opt == 'Admin - Accounting') {
                    $data[$recipient] = $admin_settings->notification_account;
                }

                if ($opt == 'Admin - helpdesk') {
                    $data[$recipient] = $admin_settings->notification_helpdesk;
                }

                if ($opt == 'Admin - Helpdesk') {
                    $data[$recipient] = $admin_settings->notification_helpdesk;
                }

                if ($opt == 'Admin - Manager') {
                    $data[$recipient] = $admin_settings->notification_manager;
                }
                if ($opt == 'Admin - Director') {
                    $data[$recipient] = $admin_settings->notification_manager;
                }

                if ($opt == 'Admin - Developer') {
                    $data[$recipient] = $admin_settings->notification_developer;
                }

                if ($opt == 'Admin - Marketing') {
                    $data[$recipient] = $admin_settings->notification_marketing;
                }
            }
        }

        // defaults to account email, if recipient email invalid
        if (empty($data[$recipient]) && str_contains($notification->{$recipient}, 'Account -')) {
            $data[$recipient] = $account->email;
        }
        if ($reseller->id != 1) {
            if (empty($data[$recipient]) && str_contains($notification->{$recipient}, 'Reseller -')) {
                $data[$recipient] = $reseller->email;
            }
        }
    }

    return $data;
}

function set_instance_email_templates()
{

    $instances = \DB::table('erp_instances')->where('installed', 1)->get();
    foreach ($instances as $i) {
        if (! Storage::disk('templates')->exists($i->db_connection.'/notification_html.txt')) {
            \Storage::disk('templates')->put(
                $i->db_connection.'/notification_html.txt',
                \Storage::disk('templates')->get('notification_html.txt')
            );
        }

        if (! Storage::disk('templates')->exists($i->db_connection.'/notification_reseller_html.txt')) {
            \Storage::disk('templates')->put(
                $i->db_connection.'/notification_reseller_html.txt',
                \Storage::disk('templates')->get('notification_reseller_html.txt')
            );
        }

        if (! \Storage::disk('templates')->exists($i->db_connection.'/notification_css.txt')) {
            \Storage::disk('templates')->put(
                $i->db_connection.'/notification_css.txt',
                \Storage::disk('templates')->get('notification_css.txt')
            );
        }
    }

}
