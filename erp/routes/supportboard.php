<?php

Route::get('support', function () {

    $data['menu_name'] = 'Helpdesk';
    $data['module_id'] = 500;
    $data['is_supportboard'] = 1;

    $user_id = session('user_id');
    //if($user_id == 3696){
    //    $user_id = 1;
    //}
    $user = \DB::table('erp_users')->where('id', $user_id)->where('is_deleted', 0)->get()->first();

    if ($user && ! empty($user->webmail_email) && ! empty($user->webmail_password)) {
        $data['email'] = $user->webmail_email;
        $data['password'] = $user->webmail_password;
    }

    //dd($data);
    return view('integrations.supportboard', $data);
})->middleware('globalviewdata');

Route::get('supportboard_view', function () {
    $data = supportboard_view_data();
    $data['menu_name'] = 'Support Board';
    $data['module_id'] = 500;
    //$data['grid_id'] = 1;

    return view('integrations.supportboard_view', $data);
})->middleware('globalviewdata');

Route::get('supportboard_view_data', function () {
    return supportboard_view_data_ajax(request()->all());
});

Route::get('supportboard_email_piping', function () {

    require '/home/teleclou/helpdesk.telecloud.co.za/html/include/functions.php';
    sb_email_piping(true);
});

Route::get('supportboard_send_reply/{id?}', function ($conversation_id) {
    return view('integrations.supportboard_message', ['conversation_id' => $conversation_id]);
});

Route::post('supportboard_send_reply', function () {
    /*SB PHP API*/
    try {
        require '/home/teleclou/helpdesk.telecloud.co.za/html/include/functions.php';
        $conversation_id = request('conversation_id');
        $message = request('message');
        if (empty($conversation_id)) {
            return json_alert('Conversation id required.', 'warning');
        }

        if (empty($message)) {
            return json_alert('Message required.', 'warning');
        }
        /*
        sender_id Required	The ID of the user who sends the message.
        conversation_id Required	The conversation ID.
        message	The content of the message.
        attachments	Array of attachments. Array syntax: [["name", "link"], ["name", "link"], ...]. Replace name with the name of the attachment and link with the full URL of the attachment. It's up to you to upload attachments to a remote server, this argument only accepts the URL of the files already uploaded. Default: [];
        conversation_status	The status code of the conversation. Status codes: live = 0, waiting answer from user = 1, waiting answer from agent = 2, archive = 3, trash = 4. Set it to skip to leave the current conversation status.
        payload	Array of additional information. You can enter any value. Array syntax: { "key": value, "key": value, ... }. Use this attribute to set an event. Available events: delete-message, open-chat.
        queue	Set it to true if the queue is active in Settings > Miscellaneous > Queue. Default: false.
        recipient_id	The ID of the user who receive the message. Use this attribute to get the user language.
        */
        $erp_user_email = \DB::connection('default')->table('erp_users')->where('id', session('user_id'))->pluck('email')->first();
        $sb_user_id = \DB::connection('supportboard')->table('sb_users')->where('email', $erp_user_email)->whereIn('user_type', ['agent', 'admin'])->pluck('id')->first();
        if (! $sb_user_id) {
            return json_alert('Agent user id not found', 'warning');
        }

        $conversation = \DB::connection('supportboard')->table('sb_conversations')->where('id', $conversation_id)->get()->first();

        if (empty($conversation->user_id)) {
            return json_alert('User id required.', 'warning');
        }
        $user_id = $sb_user_id;

        $attachments = [];
        $conversation_status = 1;
        $payload = [];
        $queue = false;
        $recipient_id = $conversation->user_id;
        // aa(request()->all());
        // aa(request('attachments'));
        if (! empty(request('attachments'))) {
            $destinationPath = public_path('supportboard');
            $destinationUrl = url('supportboard');
            foreach (request('attachments') as $file) {
                $filename = $file->getClientOriginalName();

                $filename = $conversation_id.str_replace([' ', ','], '_', $filename);
                $uploadSuccess = $file->move($destinationPath, $filename);
                $attachments[] = [$filename, $destinationUrl.'/'.$filename];
            }
        }

        $response = sb_send_message($user_id, $conversation_id, $message, $attachments, $conversation_status, $payload, $queue, $recipient_id);
        // aa($response);
        // return json_alert('Message sent');
    } catch (\Throwable $ex) {
        exception_log($ex->getMessage());

        return json_alert('Message could not be sent, please contact admin.', 'warning');
    }
});
