<?php

function schedule_check_bounced_emails()
{

    return false;
    $api_key = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzY29wZXMiOlsicHJvdmlzaW9uaW5nIiwicHJpdmF0ZToqIiwic2hhcmVkOioiXSwiaWF0IjoxNjcxNjA5MzIyLCJpc3MiOiJmcm9udCIsInN1YiI6IjI0MmU5OGNhNjRjOWVlNDYwNDE5IiwianRpIjoiMDRiZTg4OTY5YmNhNzNkNCJ9.sxnTQNGFcdiPML4sUOOAtzSK-MrCSNcBtJLadK1QusQ';
    $FrontApp = new DrewM\FrontApp\FrontApp($api_key);

    $email_addresses = [];

    $url = 'conversations/search/Message Failure Delivery Notice. is:open';

    $conversations = $FrontApp->get($url);

    foreach ($conversations['_results'] as $conv) {
        $matches = null;
        $url = str_replace('https://cloud-telecoms-pty-ltd.api.frontapp.com/', '', $conv['_links']['related']['messages']);
        $messages = $FrontApp->get($url);

        foreach ($messages['_results'] as $msg) {

            $pattern = "/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i";
            preg_match_all($pattern, $msg['text'], $matches);
            if ($matches && is_array($matches) && count($matches) > 0) {
                foreach ($matches as $e) {
                    $email_addresses[] = $e[0];
                }
            }
        }

        // archive conversation
        $res = $FrontApp->patch('conversations/'.$conv['id'], ['status' => 'archived']);
    }

    $email_addresses = collect($email_addresses)->unique()->filter()->toArray();
    foreach ($email_addresses as $email) {

        $accounts = \DB::table('crm_accounts')->select('id', 'partner_id')->where('email', $email)->get();

        foreach ($accounts as $a) {
            if ($a->partner_id == 1) {
                \DB::table('crm_accounts')->where('id', $a->id)->update(['notification_type' => 'sms', 'newsletter' => 0]);
                module_log(343, $a->id, 'Faulty email address removed', $email);
            } else {
                $reseller_email = \DB::table('crm_accounts')->where('id', $a->partner_id)->pluck('email')->first();
                \DB::table('crm_accounts')->where('id', $a->id)->update(['email' => $reseller_email, 'newsletter' => 0]);
                module_log(343, $a->id, 'Faulty email address removed', $email);
            }
        }
    }
}

function schedule_frontapp_get_open_tickets()
{
    return false;

    // \DB::table('crm_frontapp_tickets')->truncate();
    $api_key = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzY29wZXMiOlsicHJvdmlzaW9uaW5nIiwicHJpdmF0ZToqIiwic2hhcmVkOioiXSwiaWF0IjoxNjcxNjA5MzIyLCJpc3MiOiJmcm9udCIsInN1YiI6IjI0MmU5OGNhNjRjOWVlNDYwNDE5IiwianRpIjoiMDRiZTg4OTY5YmNhNzNkNCJ9.sxnTQNGFcdiPML4sUOOAtzSK-MrCSNcBtJLadK1QusQ';
    $FrontApp = new DrewM\FrontApp\FrontApp($api_key);

    $email_addresses = [];
    $inbox_ids = frontapp_get_inbox_ids();
    $import = [];
    foreach ($inbox_ids as $inbox_id) {
        // Build filter options
        $url = 'inboxes/{'.$inbox_id.'}/channels/shared/tickets';

        $url = "inboxes/{$inbox_id}/channels/email/filters/unassigned?status=open";
        $url = "inboxes/{$inbox_id}/conversations/filters/unassigned?status=open";
        $conversations = $FrontApp->get($url);
        $system_user_id = get_system_user_id();
        /*
           foreach($conversations['_results'] as $l){
               $user_id = \DB::table('erp_users')->where('email',$l["assignee"]["email"])->pluck('id')->first();
               if(!$user_id){
                   $user_id = $system_user_id;
               }

               $data = [
                   'user_id' => $user_id,
                   'subject' => $l['subject']
               ];
               $import[] = $data;
             //  \DB::table('crm_frontapp_tickets')->insert($data);

           }*/
    }

}

function frontapp_get_inbox_ids()
{
    $api_key = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzY29wZXMiOlsicHJvdmlzaW9uaW5nIiwicHJpdmF0ZToqIiwic2hhcmVkOioiXSwiaWF0IjoxNjcxNjA5MzIyLCJpc3MiOiJmcm9udCIsInN1YiI6IjI0MmU5OGNhNjRjOWVlNDYwNDE5IiwianRpIjoiMDRiZTg4OTY5YmNhNzNkNCJ9.sxnTQNGFcdiPML4sUOOAtzSK-MrCSNcBtJLadK1QusQ';
    $FrontApp = new DrewM\FrontApp\FrontApp($api_key);

    $inbox_ids = [];

    $url = 'inboxes';
    $result = $FrontApp->get($url);
    foreach ($result['_results'] as $row) {
        if ($row['is_private'] == false) {
            $inbox_ids[] = $row['id'];
        }
    }

    return $inbox_ids;
}
