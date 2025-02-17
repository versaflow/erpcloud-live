<?php

function schedule_delete_communication_history()
{
    //monthly 1st
    \DB::table('erp_communication_lines')->where('created_at', '<', date('Y-m-d', strtotime('-6 months')))->delete();
    \DB::table('erp_call_history')->where('created_at', '<', date('Y-m-d', strtotime('-6 months')))->delete();
}

function aftersave_set_account_notes($request)
{
    if ($request->type == 'note') {
        $note = date('Y-m-d H:i').' '.$request->subject;
        \DB::table('crm_accounts')->where('id', $request->account_id)->update(['notes' => $note]);
    }
    \DB::table('erp_communication_lines')->where('id', $request->id)->update(['success' => 1]);
}

function button_communications_view($request)
{
    $menu_name = get_menu_url_from_table('erp_communication_lines');
    $table = $request->db_table;
    if ($table == 'crm_accounts') {
        return Redirect::to($menu_name.'?account_id='.$request->id);
    } else {
        $conn = \DB::connection('default')->table('erp_cruds')->where('db_table', $table)->pluck('connection')->first();
        $account_id = \DB::connection($conn)->table($table)->where('id', $request->id)->pluck('account_id')->first();
        if (! $account_id) {
            return json_alert('Invalid Account Id', 'error');
        }

        return Redirect::to($menu_name.'?account_id='.$account_id);
    }
}

function button_communications_add($request)
{
    $table = $request->db_table;
    $menu_name = get_menu_url_from_table('erp_communication_lines');
    if ($table == 'crm_accounts') {
        return Redirect::to($menu_name.'/edit?account_id='.$request->id);
    } else {
        $conn = \DB::connection('default')->table('erp_cruds')->where('db_table', $table)->pluck('connection')->first();
        $account_id = \DB::connection($conn)->table($table)->where('id', $request->id)->pluck('account_id')->first();
        if (! $account_id) {
            return json_alert('Invalid Account Id', 'error');
        }

        return Redirect::to($menu_name.'/edit?account_id='.$account_id);
    }
}

function button_communications_edit_template($request)
{
    $email = \DB::table('erp_communication_lines')->where('id', $request->id)->get()->first();
    if (empty($email->email_id)) {
        return json_alert('No template id set for this email', 'warning');
    }

    return Redirect::to('/bee?id='.$email->email_id);
}

function button_communications_view_email($request)
{
    $email = \DB::table('erp_communication_lines')->where('id', $request->id)->get()->first();
    $attachments = explode(',', $email->attachments);
    $view = '<div class="container">';
    $view .= '<div class="card my-3">';
    $view .= '<div class="card-body" style="background-color: #f9f9f9;">';
    $view .= '<div class="row">';

    $view .= '<div class="col">';

    if (! empty($email->created_at)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>Date Sent</b></div><div class="col">'.$email->created_at.'</div></div>';
    }

    if (! empty($email->source)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>From Address</b></div><div class="col">'.$email->source.'</div></div>';
    }
    if (! empty($email->subject)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>Subject</b></div><div class="col">'.$email->subject.'</div></div>';
    }

    if (! empty($email->destination)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>To Address</b></div><div class="col">'.$email->destination.'</div></div>';
    }

    if (! empty($email->cc_email)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>CC Address</b></div><div class="col">'.$email->cc_email.'</div></div>';
    }

    if (! empty($email->bcc_email)) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>BCC Address</b></div><div class="col">'.$email->bcc_email.'</div></div>';
    }

    if (count($attachments) > 0) {
        $view .= '<div class="row mb-2"><div class="col-3"><b>Attachments</b></div><div class="col"> ';
        foreach ($attachments as $a) {
            $view .= '<a href="'.url(attachments_url().$a).'" target="_blank">'.$a.'</a><br>';
        }
        $view .= '</div></div>';
    }

    $view .= '</div>';
    $view .= '</div>';

    $view .= '</div>';

    $view .= '<div class="card-body border-top">';
    $view .= $email->message;

    $view .= '</div>';
    $view .= '</div>';
    $view .= '</div>';
    echo $view;
}

function button_communications_view_stats($request)
{
    $type = \DB::table('erp_communications')->where('id', $request->id)->pluck('type')->first();
    if ($type == 'sms') {
        $url = get_menu_url_from_table('isp_sms_messages');
    } else {
        $url = get_menu_url_from_table('erp_communication_lines');
    }

    return redirect()->to($url.'?communication_id='.$request->id);
}

function schedule_update_communications_headers()
{

    $emails = \DB::table('erp_communication_lines')
        ->selectRaw("*,DATE_FORMAT(created_at,'%Y-%m-%d') as created_date")
        ->where('type', 'email')
        ->where('communication_id', 0)
        ->where('email_id', 0)
        ->groupBy('subject')->groupBy('created_date')->get();
    foreach ($emails as $email) {
        $success_count = \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', 0)->where('subject', $email->subject)->where('success', 1)->count();
        $error_count = \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', 0)->where('subject', $email->subject)->where('success', 0)->count();
        $send_count = \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', 0)->where('subject', $email->subject)->count();
        $data = [
            'subject' => $email->subject,
            'type' => 'email',
            'created_at' => $email->created_at,
            'email_id' => $email->email_id,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'send_count' => $send_count,
        ];
        $id = \DB::table('erp_communications')->insertGetId($data);
        \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', 0)->where('subject', $email->subject)->update(['communication_id' => $id]);
    }

    $emails = \DB::table('erp_communication_lines')
        ->selectRaw("*,DATE_FORMAT(created_at,'%Y-%m-%d') as created_date")
        ->where('type', 'email')->where('communication_id', 0)
        ->groupBy('email_id')->groupBy('created_date')->get();
    foreach ($emails as $email) {
        $success_count = \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', $email->email_id)->where('success', 1)->count();
        $error_count = \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', $email->email_id)->where('success', 0)->count();
        $send_count = \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', $email->email_id)->count();
        $data = [
            'subject' => $email->subject,
            'type' => 'email',
            'created_at' => $email->created_at,
            'email_id' => $email->email_id,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'send_count' => $send_count,
        ];
        $id = \DB::table('erp_communications')->insertGetId($data);
        \DB::table('erp_communication_lines')->where('created_at', 'like', $email->created_date.'%')->where('email_id', $email->email_id)->update(['communication_id' => $id]);
    }

    $smses = \DB::table('isp_sms_messages')->get();
    foreach ($smses as $sms) {
        $success_count = \DB::table('isp_sms_message_queue')->where('isp_sms_messages_id', $sms->id)->where('status', 'Delivered')->count();
        \DB::table('isp_sms_messages')->where('id', $sms->id)->update(['delivered_qty' => $success_count]);
    }

    $smses = \DB::table('isp_sms_messages')
        ->selectRaw("*,DATE_FORMAT(queuetime,'%Y-%m-%d') as created_date")
        ->where('email_id', '>', 0)
        ->where('communication_id', 0)
        ->groupBy('email_id')->groupBy('created_date')->get();
    foreach ($smses as $sms) {
        $sms_ids = \DB::table('isp_sms_messages')->where('queuetime', 'like', $email->created_date.'%')->where('email_id', $email->email_id)->pluck('id')->toArray();
        $success_count = \DB::table('isp_sms_message_queue')->whereIn('isp_sms_messages_id', $sms_ids)->where('status', 'Delivered')->count();
        $error_count = \DB::table('isp_sms_message_queue')->whereIn('isp_sms_messages_id', $sms_ids)->where('status', '!=', 'Delivered')->count();
        $send_count = \DB::table('isp_sms_message_queue')->whereIn('isp_sms_messages_id', $sms_ids)->count();
        $data = [
            'subject' => substr($sms->message, 0, 100).'...',
            'type' => 'sms',
            'created_at' => $sms->created_at,
            'email_id' => $sms->email_id,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'send_count' => $send_count,
        ];
        $id = \DB::table('erp_communications')->insertGetId($data);
        \DB::table('isp_sms_messages')->whereIn('id', $sms_ids)->update(['communication_id' => $id]);
    }
}
