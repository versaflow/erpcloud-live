<?php

function onload_set_event_email_module()
{
    $event_emails = \DB::table('erp_form_events')->where('email_id', '>', 0)->get();
    foreach ($event_emails as $event_email) {
        \DB::table('crm_email_manager')->where('id', $event_email->email_id)->update(['module_id' => $event_email->module_id]);
    }
}

function schedule_clear_storage()
{
    $attachments = \DB::connection('default')->table('erp_communication_lines')->where('attachments', '>', '')->pluck('attachments')->toArray();

    $attachment_list = [];
    foreach ($attachments as $a) {
        $files = explode(',', $a);
        foreach ($files as $f) {
            $attachment_list[] = attachments_path().$f;
        }
    }
    $attachment_list = collect($attachment_list)->unique()->toArray();
    //collect($attachment_list)->
    $filesForDelete = array_filter(glob(attachments_path().'*'), function ($file) use ($attachment_list) {
        if (str_contains($file, 'Ratesheet')) {
            return false;
        }
        if (str_contains($file, 'ratesheet')) {
            return false;
        }

        return in_array($file, $attachment_list) === false;
    });

    \File::delete($filesForDelete);
}

function aftersave_email_remove_encoded_characters($request)
{
    $email = \DB::connection('default')->table('crm_email_manager')->where('id', $request->id)->get()->first();
    $message = str_replace('&lt;', '<', str_replace('&gt;', '>', $email->message));
    //$message =  str_replace("\n", "<br />", $message);
    \DB::connection('default')->table('crm_email_manager')->where('id', $request->id)->update(['message' => $message]);
}

function schedule_clear_mail_queue()
{
    $date = date('Y-m-d', strtotime('-1 day'));
    \DB::table('erp_mail_queue')->where('created_at', '<', $date)->where('processed', 1)->delete();
}
