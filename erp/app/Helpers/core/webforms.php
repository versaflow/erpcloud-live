<?php

function get_webform_link($module_id,$account_id,$row_id = false){
     
    $webform_data = [];
    $webform_data['module_id'] = $module_id;
    $webform_data['account_id'] = $account_id;
    if(!empty($row_id))
    $webform_data['id'] = $row_id;
    $link_name = \DB::connection('default')->table('erp_cruds')->where('id',$module_id)->pluck('name')->first();
    if($module_id == 390){
    $link_name = 'Debit order mandate';
    }
    $link_data = \Erp::encode($webform_data);
    $webform_link = '<a href="'.url('/webform/'.$link_data).'" >'.$link_name.'</a>';
    return $webform_link;
}

function button_number_porting_send_submission($request)
{
    $number_porting_email = \DB::connection('pbx')->table('v_gateways')->where('number_porting',1)->pluck('number_porting_email')->first();
    $row = \DB::table('sub_forms_number_porting')->where('id', $request->id)->get()->first();
    $mail_data = [];

    $pdf_name = 'number_porting_request'.$request->id;
    $file = $pdf_name.'.pdf';
    $pdf = number_porting_pdf($request->id);
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
    $mail_data['attachments'] = get_record_attachments('sub_forms_number_porting', $request->id);
    $mail_data['attachments'][] = $pdf_name.'.pdf';
    $mail_data['force_to_email'] = $number_porting_email;
    $mail_data['cc_email'] = 'helpdesk@telecloud.co.za';
    $mail_data['subject'] = 'Number Porting Request';
    $mail_data['msg'] = 'Please see files attached.';
    $mail_data['message'] = 'Please see files attached.';

    return email_form(1, 12, $mail_data);
}



function get_record_attachments($table, $id)
{
    $attachments = [];
    $module_id = \DB::table('erp_cruds')->where('db_table', $table)->pluck('id')->first();
    $form_config = \DB::table('erp_module_fields')->where('field_type', 'file')->where('module_id', $module_id)->get();

    if (!empty($form_config) && count($form_config) > 0) {
        $row = \DB::table($table)->where('id', $id)->get()->first();

        foreach ($form_config as $i => $f) {
            if (!empty($row->{$f->field}) && ($f->field_type == 'image' || $f->field_type == 'signature'|| $f->field_type == 'file')) {
                $file_name = $row->{$f->field};
                $file_name_arr = explode('.', $file_name);
                $file_ext = end($file_name_arr);
                $new_file_name = $module_id."_".$id.$i.'.'.$file_ext;
                \File::copy(uploads_path($module_id).$file_name, attachments_path().$new_file_name);
                $attachments[] = $new_file_name;
            }
        }
    }

    return $attachments;
}
function number_porting_pdf($id)
{
    $row = \DB::table('sub_forms_number_porting')->where('id', $id)->get()->first();
    if (!$row) {
        return false;
    }
    $data = [];
    $data['row'] = (array) $row;
    if (!empty($row->account_id)) {
        $data['account'] = dbgetaccount($row->account_id);
    }
    $module = \DB::table('erp_cruds')->where('db_table', $table)->where('public_access', 1)->get()->first();
    $data['module'] = $module;
    $data['form_config'] = \DB::table('erp_module_fields')->where('module_id', $module->id)->orderby('sort_order')->get();
    $data['webform_title'] = $module->webform_title;
    $data['webform_text'] = $module->webform_text;
    $reseller = dbgetaccount(1);
    $data['reseller'] = $reseller;
    if (!empty($reseller->logo) && file_exists(uploads_settings_path().$reseller->logo)) {
        $data['logo_path'] = uploads_settings_path().$reseller->logo;
        $data['logo'] = settings_url().$reseller->logo;
    }
//dd($data);
    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-right' => ' Page [page] of [topage]',
        'footer-font-size' => 8,
    ];

    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.number_porting_template', $data);

    $pdf->setOptions($options);

    return $pdf;
}

function button_number_porting_forms_view_pdf($request)
{
    $pdf_name = 'number_porting_request'.$request->id;
    $file = $pdf_name.'.pdf';

    $pdf = number_porting_pdf($request->id);
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
    $data['pdf'] = attachments_url().$file;
    $data['menu_name'] = $pdf_name;

    return view('__app.components.pdf', $data);
}

function button_number_porting_send_form_link($request)
{
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'number_porting_form')->pluck('id')->first();
    $row = \DB::table('sub_forms_number_porting')->where('id', $request->id)->get()->first();
    $mail_data = [];
    $mail_data['product'] = 'Number Porting';
    $mail_data['record_id'] = $request->id;

    return email_form($email_id, $row->account_id, $mail_data);
}

function button_ecommerce_send_form_link($request)
{
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'button_ecommerce_send_form_link')->pluck('id')->first();
    $row = \DB::table('sub_forms_ecommerce')->where('id', $request->id)->get()->first();
    $mail_data = [];
    $mail_data['product'] = 'E-commerce';
    $mail_data['record_id'] = $request->id;

    return email_form($email_id, $row->account_id, $mail_data);
}

function button_debitorders_send_form_link($request)
{
   
    $row = \DB::table('acc_debit_orders')->where('id', $request->id)->get()->first();
    $mail_data = [];
    $mail_data['record_id'] = $request->id;
    $mail_data['internal_function'] = 'debit_order';
   
    $webform_data = [];
    $webform_data['module_id'] = 390;
    $webform_data['account_id'] = $row->account_id;
    $webform_data['id'] = $request->id;

    $link_data = \Erp::encode($webform_data);
    $mail_data['webform_link'] = '<a href="'.$request->root().'/webform/'.$link_data.'" >Debit order mandate</a>';
    $mail_data['show_debit_order_link'] = true;
    
     erp_process_notification($row->account_id, $mail_data);
     return json_alert('Sent');
}

function webform_pdf($table, $id)
{
    $row = \DB::table($table)->where('id', $id)->get()->first();
    if (!$row) {
        return false;
    }
    $data = [];
    $data['row'] = (array) $row;
    if (!empty($row->account_id)) {
        $data['account'] = dbgetaccount($row->account_id);
    }
    $module = \DB::table('erp_cruds')->where('db_table', $table)->where('public_access', 1)->get()->first();
    $data['module'] = $module;
    $data['form_config'] = \DB::table('erp_module_fields')->where('module_id', $module->id)->orderby('sort_order')->get();
    $data['webform_title'] = $module->webform_title;
    $data['webform_text'] = $module->webform_text;
//dd($data);
    //Set up our options to include our header and footer
    //The PDF doesn't render correctly without some of these
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-right' => ' Page [page] of [topage]',
        'footer-font-size' => 8,
    ];

    //Create our PDF with the main view and set the options
    $pdf = PDF::loadView('__app.components.pdfs.webform', $data);

    $pdf->setOptions($options);

    return $pdf;
}


function button_debit_order_forms_view_pdf($request)
{
    $pdf_name = 'debit_order_mandate_'.$request->id;
    $file = $pdf_name.'.pdf';

    $pdf = webform_pdf('acc_debit_orders', $request->id);
    $module_filename = uploads_path(390).$file;
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    if (file_exists($module_filename)) {
        unlink($module_filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);
    $pdf->save($module_filename);
    $data['pdf'] = attachments_url().$file;
    $data['menu_name'] = $pdf_name;
    $row = \DB::table('acc_debit_orders')->where('id', $request->id)->get()->first();

    if (empty($row->debit_order_mandate)) {
        \DB::table('acc_debit_orders')->where('id', $request->id)->update(['debit_order_mandate'=>$file]);
    }
    return view('__app.components.pdf', $data);
}

function aftersave_send_debit_order_submission($request)
{
    if (!empty($request->new_record)) {
        $pdf_name = 'debit_order_submission_'.$request->id;
        $file = $pdf_name.'.pdf';

        $pdf = webform_pdf('acc_debit_orders', $request->id);
        $filename = attachments_path().$file;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
        $data['type'] = 'Debit Order';
        if (!empty($request->account_id)) {
            $data['company_name'] = dbgetaccount($request->account_id)->company;
        }
        $data['attachments'] = get_record_attachments('acc_debit_orders', $request->id);
        $data['attachments'][] = $file;
        $data['internal_function'] = 'webform_submission';
        $data['cc_email'] = 'accounts@telecloud.co.za';
        $data['bcc_email'] = 'ahmed@telecloud.co.za';
        erp_process_notification(12, $data);
    }
}

function aftersave_send_number_porting_submission($request)
{
    
    $number_porting_email = \DB::connection('pbx')->table('v_gateways')->where('number_porting',1)->pluck('number_porting_email')->first();
    $pdf_name = 'number_porting_submission'.$request->id;
    $file = $pdf_name.'.pdf';

    $pdf = number_porting_pdf($request->id);
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
    $data['type'] = 'Number Porting';
    $data['attachments'] = get_record_attachments('sub_forms_number_porting', $request->id);
    $data['attachments'][] = $file;
    if (!empty($request->account_id)) {
        $data['company_name'] = dbgetaccount($request->account_id)->company;
    }
    $data['internal_function'] = 'webform_submission';
    $data['subject'] = 'Number Porting Request';
    $data['msg'] = 'Please see files attached.';
    $data['force_to_email'] = $number_porting_email;
    //$data['test_debug'] = 1;
   
    erp_process_notification(12, $data);
    
}

function aftersave_send_ecommerce_submission($request)
{
    $pdf_name = 'ecommerce_submission_'.$request->id;
    $file = $pdf_name.'.pdf';

    $pdf = webform_pdf('sub_forms_ecommerce', $request->id);
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
    $data['type'] = 'Ecommerce';
    $data['attachments'] = get_record_attachments('sub_forms_ecommerce', $request->id);
    $data['attachments'][] = $file;
    if (!empty($request->account_id)) {
        $data['company_name'] = dbgetaccount($request->account_id)->company;
    }
    $data['internal_function'] = 'webform_submission';
    erp_process_notification(12, $data);
}

function aftersave_send_lte_submission($request)
{
    $pdf_name = 'lte_submission_'.$request->id;
    $file = $pdf_name.'.pdf';

    $pdf = webform_pdf('sub_forms_lte', $request->id);
    $filename = attachments_path().$file;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);
    $data['type'] = 'LTE Sim Card';
    $data['attachments'] = get_record_attachments('sub_forms_lte', $request->id);
    $data['attachments'][] = $file;
    if (!empty($request->account_id)) {
        $data['company_name'] = dbgetaccount($request->account_id)->company;
    }
    $data['internal_function'] = 'webform_submission';
    $data['cc_email'] = 'neliswa.sango@vodacom.co.za';
    $data['bcc_email'] = 'ahmed@telecloud.co.za';
    erp_process_notification(1, $data);
}

function afterdelete_send_ticket_closed($request)
{
    if ($request->account_id > '') {
        $data = [];
        $data['function_name'] = __FUNCTION__;
        erp_process_notification($request->account_id, $data);
    }
}

function button_tickets_process_ticket($request)
{
    \DB::table('hd_tickets')->where('id', $request->id)->update(['status' => 'Processed']);
    return json_alert('Ticket set to processed.');
}

function aftersave_send_feedback($request)
{
    if (empty($request->status) || $request->status == 'New') {
        $data = [];
        $data['function_name'] = __FUNCTION__;
        $data['feedback'] = 'Message:<br>'.$request->message;
        $account = dbgetaccount($request->account_id);
        $data['feedback'] .= '<br><br>
            Account: '.$account->company.'<br>
            Phone: '.$account->phone.'<br>
            Email: <a href="mailto:'.$account->email.'" >'.$account->email.'</a>
        ';
        $data['reply_email'] = $account->email;
        $data['ticket_id'] = $request->id;
        $data['reply_company'] = $account->company;
        //$data['test_debug'] = 1;
        erp_process_notification(12, $data);
    }
}

function beforesave_check_feeback_input($request)
{
    if ($request->type == 'Call Error') {
        if (empty($request->call_time) || empty($request->call_source_number) || empty($request->call_destination_number)) {
            return 'Please enter the call details.';
        }
    }

    if (empty($request->email) && empty($request->account_id)) {
        return 'Email required.';
    }

    if (empty($request->email) && !empty($request->account_id)) {
        $account = dbgetaccount($request->account_id);
        $request->request->add(['email' => $account->email]);
    }

    // match account
    if (!empty($request->email) && empty($request->account_id)) {
        $account_id = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('email', $request->email)->pluck('id')->first();
        $request->request->add(['account_id' => $account_id]);
    }
}

function button_tickets_view_message($request)
{
    $ticket = \DB::table('hd_tickets')->where('id', $request->id)->pluck('message')->first();
    echo '<div class=" m-3 card card-body">'.$ticket.'</div>';
}

function schedule_import_tickets()
{
    /*
    try {
        $inbox = imap_open("{imap.zoho.com:993/imap/ssl/novalidate-cert}", "helpdesk@telecloud.co.za", "Webmin321");
    } catch (\Throwable $ex) {  exception_log($ex);
        exception_email($ex, __FUNCTION__.' error');
        return false;
    }

    if (!$inbox) {
        return false;
    }

    $emails = imap_search($inbox, 'UNSEEN');

    if (!empty($emails) && count($emails) > 0) {
        $erp = new DBEvent;
        $erp->setTable('hd_tickets');
        rsort($emails);
        foreach ($emails as $email_number) {
            $header = imap_headerinfo($inbox, $email_number);

            $email_sender = $header->from[0]->mailbox.'@'.$header->from[0]->host;

            $body = getEmailBody($inbox, $email_number);
            $message = '';
            $message .= '<b>Subject: </b>' . $header->subject . '<br/><br/>';
            $message .= '<b>Body: </b><br/>';
            $message .= trim($body);
            $data = [
                'type' => 'Mailbox',
                'email' => $email_sender,
                'message' => $message,
                'status' => 'New',
            ];
            $erp->save($data);
            imap_delete($inbox, $email_number);
        }
    }
    imap_expunge($inbox);
    imap_close($inbox);
    */
}

function getEmailBody($uid, $imap)
{
    $body = $this->get_part($imap, $uid, "TEXT/HTML");
    // if HTML body is empty, try getting text body
    if ($body == "") {
        $body = $this->get_part($imap, $uid, "TEXT/PLAIN");
    }
    return $body;
}

function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false)
{
    if (!$structure) {
        $structure = imap_fetchstructure($imap, $uid, FT_UID);
    }
    if ($structure) {
        if ($mimetype == $this->get_mime_type($structure)) {
            if (!$partNumber) {
                $partNumber = 1;
            }
            $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
            switch ($structure->encoding) {
                case 3:
                    return imap_base64($text);
                case 4:
                    return imap_qprint($text);
                default:
                    return $text;
            }
        }

        // multipart
        if ($structure->type == 1) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber . ".";
                }
                $data = $this->get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                if ($data) {
                    return $data;
                }
            }
        }
    }
    return false;
}

function get_mime_type($structure)
{
    $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];

    if ($structure->subtype) {
        return $primaryMimetype[intval($structure->type)] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
}
