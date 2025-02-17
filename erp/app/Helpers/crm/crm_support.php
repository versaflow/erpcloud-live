<?php

function button_process_support_wizard($request)
{
    //  try{
    $row = \DB::table('hd_support_tickets')->where('id', $request->id)->get()->first();
    if ($row->assigned_to_id != session('user_id')) {
        return json_alert('Ticket is assigned to another user.', 'warning', ['close_dialog' => 1]);
    }
    if ($row->email_sent) {
        return json_alert('Email already sent.', 'warning', ['close_dialog' => 1]);
    }
    $template = \DB::table('hd_support_ticket_templates')->where('id', $row->template_id)->get()->first();
    $checklist_items_completed = json_decode($row->checklist_items_completed);
    $checklist = explode(PHP_EOL, $template->checklist);
    $checklist_items = [];
    foreach ($checklist as $i => $v) {
        $checked = false;
        if ($checklist_items_completed && is_array($checklist_items_completed) && count($checklist_items_completed) > 0) {
            if (in_array(trim($v), $checklist_items_completed)) {
                $checked = true;
            }
        }
        $checklist_items[] = ['name' => trim($v), 'checked' => $checked];
    }
    $mail_data = ['hide_form_tags' => 1];
    $email_form = email_form($template->email_id, $row->account_id, $mail_data);
    $checklist = collect($checklist)->filter()->toArray();
    $data = [
        'row' => $row,
        'template' => $template,
        'checklist_items' => $checklist_items,
        'email_form' => $email_form,
    ];

    //dd($data);
    return view('__app.tui.process_support_ticket', $data);
    //}catch(\Throwable $ex){
    //    return json_alert($ex->getMessage(),'error');
    // }
}
