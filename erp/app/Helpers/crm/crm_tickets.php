<?php

function schedule_zendesk_import_tickets_old()
{
    return false;
    if (! is_main_instance()) {
        return false;
    }

    \DB::table('crm_tickets')->update(['completed' => 1]);
    $open_tickets = Zendesk::search()->find('type:ticket status:open')->results;

    foreach ($open_tickets as $open_ticket) {
        if ($open_ticket->assignee_id) {
            $assignee_email = Zendesk::users()->find($open_ticket->assignee_id)->user->email;

            $created_at = $open_ticket->created_at;
            $zendesk_id = $open_ticket->id;
            $subject = $open_ticket->subject;
            $user_id = \DB::table('erp_users')->where('email', $assignee_email)->where('account_id', 1)->pluck('id')->first();
            // if($user_id === 1){
            //     $user_id = 5520;
            //}
            $data = [
                'user_id' => $user_id,
                'subject' => $subject,
                'zendesk_id' => $zendesk_id,
                'created_at' => date('Y-m-d H:i:s', strtotime($created_at)),
                'completed' => 0,
            ];

            \DB::table('crm_tickets')->updateOrInsert(['zendesk_id' => $zendesk_id], $data);
        }
    }
}
