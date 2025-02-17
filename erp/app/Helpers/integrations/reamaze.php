<?php

function schedule_reamaze_call_log()
{
    $cdr = \DB::connection('pbx_cdr')->table('call_records_inbound')
        ->select('id', 'caller_id_number', 'duration_mins', 'start_time')
        ->where('domain_name', 'pbx.cloudtools.co.za')
        ->where('direction', 'inbound')
        ->where('duration', '>', 0)
        ->where('start_time', '>', date('Y-m-d H:i', strtotime('-1 month')))
        ->orderBy('start_time', 'desc')
        ->get();


    foreach ($cdr as $call) {
        $formatted_number = za_number_format($call->caller_id_number);
        if (!$formatted_number) {
            $formatted_number = $call->caller_id_number;
        }
        $body = "Duration: ".$call->duration_mins;
        $account = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('phone', $call->caller_id_number)->get()->first();

        if (!$account) {
            $account = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('contact_phone_1', $call->caller_id_number)->get()->first();
        }

        if (!$account) {
            $account = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('contact_phone_2', $call->caller_id_number)->get()->first();
        }
        if (!$account) {
            $account = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('contact_phone_3', $call->caller_id_number)->get()->first();
        }
        $name = 'from number';
        if ($account) {
            $body = 'Company: '. $account->company.' '.$body;
            $name = $account->company;
        }
        $post_data = [
            'id' => "inboundcdr-".$call->id,
            'body' => $body,
            'from' => ['name' => $name,'phone' =>  $formatted_number],
            'to' => ['name' => 'to number','phone' => "+27105007500"],
            'created_at' => $call->start_time
        ];

        reamaze_call_log($post_data);
    }
}

function reamaze_call_log($post_data)
{
    $json = json_encode($post_data);

    $shared_secret = '3571485042d5c1928ea95bdfc93a6b17d2413e99ffd3df5330c5a9510823b5e0';

    $calculated_hmac = base64_encode(hash_hmac('sha256', $json, $shared_secret, true));
    $url = 'https://cloudtelecoms.reamaze.com/incoming/voice';

    $api_request = \Httpful\Request::post($url);
    $api_request->addHeader('X-Reamaze-Hmac-SHA256', $calculated_hmac);
    $api_request->addHeader('Content-Type', 'application/json');

    $response = $api_request
        ->body($json)
        ->withoutStrictSsl()
        ->send();
}

function schedule_reamaze_import()
{
    $reamaze = new \Reamaze();
    $results = $reamaze->getCoversations();
    $conversations = collect($results->conversations);

    if ($results->page_count > 1) {
        $total_pages = $results->page_count;

        for ($i = 2;$i <= $total_pages; $i++) {
            $paged_results = $reamaze->getCoversations(['page' => $i]);
            $items = $paged_results->conversations;

            if (!empty($items) && is_array($items) && count($items) > 0) {
                $conversations = $conversations->merge($items);
            }
        }
    }

    $statuses = [
        0 => 'Unresolved',
        1 => 'Pending',
        2 => 'Resolved',
        3 => 'Spam',
        4 => 'Archived',
        5 => 'On Hold',
        6 => 'Auto-Resolved',
        7 => 'Chatbot Assigned',
        8 => 'Chatbot Resolved',
    ];

    $channels = [
        0 => 'Chat',
        1 => 'Email',
        2 => 'Twitter',
        3 => 'Facebook',
        6 => 'Classic Mode Chat',
        7 => 'API',
        8 => 'Instagram',
        9 => 'SMS',
        15 => 'WhatsApp',
        16 => 'Staff Outbound',
        17 => 'Contact Form',
    ];
    \DB::table('crm_reamaze')->update(['status'=> 'Archived']);

    foreach ($conversations as $conversation) {
        $exists = \DB::table('crm_reamaze')->where('slug', $conversation->slug)->where('created_at', date('Y-m-d H:i:s', strtotime($conversation->created_at)))->count();
        if (!$exists) {
            $from_name = '';
            $from_email = '';
            $category = '';
            $smtp = '';
            $staff = '';

            if (!empty($conversation->assignee) && !empty($conversation->assignee->name)) {
                $staff = $conversation->assignee->name;
            }
            if (!empty($conversation->author) && !empty($conversation->author->name)) {
                $from_name = $conversation->author->name;
            }
            if (!empty($conversation->author) && !empty($conversation->author->email)) {
                $from_email = $conversation->author->email;
            }
            if (!empty($conversation->category) && !empty($conversation->category->name)) {
                $category = $conversation->category->name;
            }
            if (!empty($conversation->external_data) && !empty($conversation->external_data->{'X-Detected-Origin'})) {
                $smtp = $conversation->external_data->{'X-Detected-Origin'};
            }
            $ch = $channels[$conversation->origin];
            if (empty($ch)) {
                $ch = 'Email';
            }
            $data = [
                'created_at' => date('Y-m-d H:i:s', strtotime($conversation->created_at)),
                'updated_at' => date('Y-m-d H:i:s', strtotime($conversation->updated_at)),
                'status' => $statuses[$conversation->status],
                'channel' => $ch,
                'subject' => $conversation->subject,
                'perma_url' => $conversation->perma_url,
                'smtp' => $smtp,
                'category' => $category,
                'from_email' => $from_email,
                'from_name' => $from_name,
                'message_count' => $conversation->message_count,
                'slug' => $conversation->slug,
                'staff' => $staff,
            ];

            \DB::table('crm_reamaze')->insert($data);
        } else {
            $data = ['status' => $statuses[$conversation->status]];
            \DB::table('crm_reamaze')->where('slug', $conversation->slug)->where('created_at', date('Y-m-d H:i:s', strtotime($conversation->created_at)))->update($data);
        }
    }
}
