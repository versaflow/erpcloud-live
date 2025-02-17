<?php

function api_account_id($apikey)
{
    $account_id = \DB::table('erp_users')->where('api_token', $apikey)->pluck('account_id')->first();

    if (empty($account_id)) {
        return false;
    }

    return $account_id;
}

function api_user_id($apikey)
{
    $user_id = \DB::table('erp_users')->where('api_token', $apikey)->pluck('user_id')->first();

    if (empty($user_id)) {
        return false;
    }

    return $user_id;
}

function user_api_token($user_id)
{
    $api_token = \DB::table('erp_users')->where('id', $user_id)->pluck('api_token')->first();
    if (empty($api_token)) {
        return false;
    }

    return $api_token;
}

function user_set_api_token($user_id, $api_token)
{
    $updated = \DB::table('erp_users')->where('id', $user_id)->update(['api_token' => $api_token]);
    if (empty($updated)) {
        return false;
    }

    return $api_token;
}

function get_afriphone_faq()
{
    $storage_path = '/home/erp/storage/api';
    $faq_file = '/home/da12/domains/unlimitedmobile.co.za/html/data/faq/faq.json';
    $cmd = 'cp '.$faq_file.' '.$storage_path;
    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    $faq_json = Storage::disk('api')->get('faq.json');
    $json = json_decode($faq_json, true);

    $articles = [];
    foreach ($json['id'] as $i => $id) {
        if (! $json['itemType'][$i]) {
            $articles[] = ['title' => $json['title'][$i], 'text' => $json['subtitle'][$i]];
        }
    }

    //dd($articles);
    return $articles;
}
