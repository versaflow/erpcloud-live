<?php

function button_mailbox_test_connection($request)
{
    try {
        $account = \DB::table('crm_mailboxes')->where('id', $request->id)->get()->first();
        $account = (array) $account;
        $account['username'] = $account['mail_username'];
        $account['password'] = $account['mail_password'];
        unset($account['id']);
        unset($account['user_id']);
        unset($account['mail_username']);
        unset($account['mail_password']);
        $client = \MailClient::make($account);

        //Connect to the IMAP Server
        $client->connect();

        return json_alert('Connection valid');
    } catch (\Throwable $ex) {
        exception_log($ex);

        return json_alert(ucfirst($ex->getMessage()), 'error');
    }
}
