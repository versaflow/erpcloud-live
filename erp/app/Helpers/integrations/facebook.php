<?php

function schedule_import_facebook_messages()
{
    if (!is_main_instance()) {
        return false;
    }

    // Your Facebook API credentials and` access token

    $comment_checked_ids = \DB::table('crm_meta_messages')->where('type', 'conversation')->where('comment_checked', 1)->pluck('comment_id')->toArray();
    \DB::table('crm_meta_messages')->where('type', 'conversation')->delete();
    $pages = \DB::table('crm_facebook_access_tokens')->where('is_deleted', 0)->get();
    foreach ($pages as $page) {
        try {
            // Your Facebook API credentials and access token
            $appId = env('FB_APP_ID');
            $appSecret = env('FB_APP_SECRET');
            $accessToken = trim($page->page_access_token);
            $pageId = $page->page_id;
            $page_name = $page->page;
            // page access token
            // https://developers.facebook.com/tools/explorer/?method=GET&path=107391411884195%2Fposts&version=v18.0

            // Initialize Guzzle client
            $client = new GuzzleHttp\Client([
            'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
            'timeout' => 30,
        ]);

            // Make a request to fetch all posts on the page
            $response = $client->get("$pageId/conversations", [
            'query' => [
                'fields' => 'messages{created_time,message,from},unread_count,link',
                'access_token' => $accessToken,
            ],
        ]);

            // Process the API response
            $data = json_decode($response->getBody(), true);

            // Extract post IDs from the response
            $conversations = $data['data'];
            foreach ($conversations as $conversation) {
                $has_reply = 0;
                $message_chain = '';
                foreach ($conversation['messages']['data'] as $i => $msg) {
                    if ($i == 0) {
                        $last_message_time = $msg['created_time'];
                    }
                    $message_chain .= $msg['message'].PHP_EOL.'From: '.$msg['from']['name'].' at '.date('Y-m-d H:i:s', strtotime($msg['created_time'])).PHP_EOL.PHP_EOL;
                    if ($msg['from']['name'] == 'Cloud Telecoms PTY LTD' || $msg['from']['name'] == 'MovieMagic.io' || $msg['from']['name'] == 'Bulkhub') {
                        $has_reply = 1;
                    }
                }
                $data = [
                'type' => 'conversation',
                'page' => $page_name,
                'post' => '',
                'comment' => $message_chain,
                'comment_id' => $conversation['id'],
                'unread_count' => $conversation['unread_count'],
                'has_reply' => $has_reply,
                'published_at' => date('Y-m-d H:i:s', strtotime($last_message_time)),
                'facebook_link' => 'https://business.facebook.com/'.$conversation['link'],
            ];
                if (in_array($data['comment_id'], $comment_checked_ids)) {
                    $data['comment_checked'] = 1;
                }
                $e = \DB::table('crm_meta_messages')->where('comment_id', $conversation['id'])->count();
                if (!$e) {
                    dbinsert('crm_meta_messages', $data);
                } else {
                    dbupdate('crm_meta_messages', ['comment_id' => $conversation['id']], $data);
                }
            }
        } catch (\Throwable $ex) {
            debug_email('facebook api messages error: '.$ex->getMessage());
        }
    }
}

function schedule_import_facebook_comments()
{
    if (!is_main_instance()) {
        return false;
    }
    // Your Facebook API credentials and access token
    // \DB::table('crm_meta_messages')->truncate();
    $pages = \DB::table('crm_facebook_access_tokens')->where('is_deleted', 0)->get();
    foreach ($pages as $page) {
        try {
            // Your Facebook API credentials and access token
            $appId = env('FB_APP_ID');
            $appSecret = env('FB_APP_SECRET');
            $accessToken = trim($page->page_access_token);
            $pageId = $page->page_id;
            $page_name = $page->page;
            // page access token
            // https://developers.facebook.com/tools/explorer/?method=GET&path=107391411884195%2Fposts&version=v18.0

            // Initialize Guzzle client
            $client = new GuzzleHttp\Client([
            'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
            'timeout' => 30,
        ]);

            // Make a request to fetch all posts on the page
            $response = $client->get("$pageId/posts", [
            'query' => [
                'fields' => 'id,message,comments',
                'limit' => 10,
                'access_token' => $accessToken,
            ],
        ]);

            // Process the API response
            $data = json_decode($response->getBody(), true);

            // Extract post IDs from the response
            $posts = $data['data'];

            // Fetch comments for each post
            foreach ($posts as $post) {
                $post_name = $post['message'];

                // Process comments for this post
                $commentsData = $post['comments']['data'];
                if (!empty($commentsData)) {
                    foreach ($commentsData as $comment) {
                        $e = \DB::table('crm_meta_messages')->where('comment_id', $comment['id'])->count();
                        if (!$e) {
                            $data = [
                            'type' => 'comment',
                            'page' => $page_name,
                            'post' => $post_name,
                            'comment' => $comment['message'],
                            'comment_id' => $comment['id'],
                            'published_at' => date('Y-m-d H:i:s', strtotime($comment['created_time'])),
                        ];
                            dbinsert('crm_meta_messages', $data);
                        }
                    }
                }
            }
        } catch (\Throwable $ex) {
            debug_email('facebook api comments error: '.$ex->getMessage());
        }
    }
}

/*
whatsapp
Cloud Telecoms
ID: 165708506629824

Test WhatsApp Business Account
ID: 102781789316507

*/

function configureWhatsAppWebhook()
{
    $callbackUrl = url('whatsapp_webhook');
    $verifyToken = 'cloudtelecoms@786';
    $accessToken = env('FB_APP_ACCESS_TOKEN');
    $client = new GuzzleHttp\Client();
    $whatsappBusinessAccountId = 102781789316507;
    $url = "https://graph.facebook.com/v13.0/{$whatsappBusinessAccountId}/subscribed_apps";

    $params = [
        'subscribed_fields' => 'messages',
        'callback_url' => $callbackUrl,
        'verify_token' => $verifyToken,
        'access_token' => $accessToken,
    ];

    try {
        $response = $client->request('POST', $url, [
            'form_params' => $params,
        ]);

        echo 'Webhook configured successfully: '.$response->getBody();
    } catch (RequestException $e) {
        echo 'Error configuring webhook: '.$e->getMessage();
    }
}

function button_meta_import_messages($request)
{
    schedule_import_facebook_messages();

    return json_alert('Done');
}

function aftersave_facebook_tokens_get_longlived_tokens($request)
{
    $beforesave_row = session('event_db_record');
    if ($beforesave_row->user_shortlived_token != $request->user_shortlived_token) {
        \DB::table('crm_facebook_access_tokens')
        ->update(['user_shortlived_token' => $request->user_shortlived_token]);
        update_facebook_longlived_user_access_token($request->user_shortlived_token);
        update_facebook_page_access_tokens();
    }
}

function update_facebook_longlived_user_access_token($app_user_token)
{
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');

    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v13.0',
    ]);

    try {
        $d = ['client_id' => $app_id,
                'client_secret' => $app_secret,
                'grant_type' => 'fb_exchange_token',
                'fb_exchange_token' => $app_user_token, ];

        $response = $client->post('oauth/access_token', [
            'form_params' => [
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'grant_type' => 'fb_exchange_token',
                'fb_exchange_token' => $app_user_token,
            ],
        ]);

        $body = $response->getBody();

        $data = json_decode($body, true);
        \DB::table('crm_facebook_access_tokens')
        ->update([
            'user_longlived_token' => $data['access_token'],
            'user_token_expires_at' => date('Y-m-d H:i:s', strtotime('+ '.$data['expires_in'].' seconds')),
        ]);
    } catch (\Exception $e) {
    }
}

function update_facebook_page_access_tokens()
{
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    // Make a request to fetch all pages
    $response = $client->get('me/accounts', [
        'query' => [
            'access_token' => $user_access_token,
        ],
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);

    foreach ($data['data'] as $page) {
        \DB::table('crm_facebook_access_tokens')->where('page_id', $page['id'])->update(['page_access_token' => $page['access_token']]);
    }
}

function fb_get_accounts()
{
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    // Make a request to fetch all pages
    $response = $client->get('me/accounts', [
        'query' => [
            'access_token' => $user_access_token,
        ],
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);

    return $data;
}

function fb_get_ads()
{
    $fb_account_id = '1120234315492274';
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    // Make a request to fetch all pages
    $response = $client->get($fb_account_id.'/adsets', [
        'query' => [
            'access_token' => $user_access_token,
            'fields' => 'name',
        ],
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);

    return $data;
}

function fb_get_campaigns()
{
    $fb_account_id = '1120234315492274';
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    // Make a request to fetch all pages
    $response = $client->get('act_'.$fb_account_id.'/campaigns', [
        'query' => [
            'access_token' => $user_access_token,
            'fields' => 'name',
        ],
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);

    return $data;
}

function fb_get_whatsapp_accounts()
{
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    // Make a request to fetch all pages
    $response = $client->get('me/whatsapp_business_accounts', [
        'query' => [
            'access_token' => $user_access_token,
        ],
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);

    return $data;
}

function schedule_facebook_stats_update()
{
    $ad_campaigns = \DB::table('crm_ad_campaigns')->where('is_deleted', 0)->where('facebook_campaign_id', '>', '')->get();
    $facebook_channel_id = \DB::table('crm_ad_channels')->where('is_deleted', 0)->where('name', 'Facebook')->pluck('id')->first();
    foreach ($ad_campaigns as $ad_campaign) {
        $exists = \DB::table('crm_ad_campaign_details')->where('ad_campaign_id', $ad_campaign->id)->where('channel_id', $facebook_channel_id)->count();
        if (!$exists) {
            \DB::table('crm_ad_campaign_details')->insert(['ad_campaign_id' => $ad_campaign->id, 'channel_id' => $facebook_channel_id]);
        }

        $stats = get_facebook_ad_stats($ad_campaign);

        if ($stats && $stats[0] && $stats[0]['clicks']) {
            $stats = $stats[0];
            $ctr = ($stats['clicks'] > 0) ? ($stats['clicks'] / $stats['impressions']) * 100 : 0;
            $data = [
                'clicks' => $stats['clicks'],
                'reach' => $stats['reach'],
                'impressions' => $stats['impressions'],
                'ad_spend' => $stats['spend'],
                'ctr' => $ctr,
            ];
            \DB::table('crm_ad_campaign_details')->where('ad_campaign_id', $ad_campaign->id)->where('channel_id', $facebook_channel_id)->update($data);
            update_facebook_campaign_results($ad_campaign->facebook_campaign_id);
        }
    }
}

function update_facebook_campaign_results($campaign_id)
{
    // cost per click
    // num days between ad campaign launch date and end date
    $ad_campaign = \DB::table('crm_ad_campaigns')->where('facebook_campaign_id', $campaign_id)->get()->first();
    $facebook_channel_ids = \DB::table('crm_ad_channels')->where('name', 'Facebook')->pluck('id')->toArray();
    $ad_campaign_detail = \DB::table('crm_ad_campaign_details')->where('ad_campaign_id', $ad_campaign->id)->whereIn('channel_id', $facebook_channel_ids)->get()->first();
    $last_lead_created_date = null;
    $conversions = 0;
    $quotes = 0;
    $conversion_details = '';
    $quote_details = '';
    if ($ad_campaign->form_id) {
        $account_ids = \DB::connection('default')->table('crm_accounts')->where('form_id', $ad_campaign->form_id)->pluck('id')->toArray();
        \DB::table('crm_documents')->whereIn('account_id', $account_ids)->update(['ad_source' => $ad_campaign->name]);
        $num_leads = count($account_ids);
        if ($account_ids) {
            $doc_query = \DB::table('crm_documents')
            ->where('crm_documents.doctype', 'Tax Invoice')
            ->where('crm_documents.reversal_id', 0)
            ->where('crm_documents.billing_type', '')
            ->whereIn('crm_documents.account_id', $account_ids);

            $quotes_query = \DB::table('crm_documents')
            ->where('crm_documents.doctype', 'Quotation')
            ->where('crm_documents.reversal_id', 0)
            ->where('crm_documents.billing_type', '')
            ->whereIn('crm_documents.account_id', $account_ids);

            if ($ad_campaign && $ad_campaign->launch_date) {
                if (!empty($ad_campaign->launch_date)) {
                    $doc_query->where('crm_documents.docdate', '>=', $ad_campaign->launch_date);
                }
                if (!empty($ad_campaign->launch_date)) {
                    $quotes_query->where('crm_documents.docdate', '>=', $ad_campaign->launch_date);
                }
            }

            if ($ad_campaign && $ad_campaign->end_date) {
                if (!empty($ad_campaign->end_date)) {
                    $doc_query->where('crm_documents.docdate', '<=', date('Y-m-d', strtotime($ad_campaign->end_date.' +2 weeks')));
                }
                if (!empty($ad_campaign->end_date)) {
                    $quotes_query->where('crm_documents.docdate', '<=', date('Y-m-d', strtotime($ad_campaign->end_date.' +2 weeks')));
                }
            }
            $conversions = $doc_query->count();
            $quotes = $quotes_query->count();

            $conversion_docs = $doc_query->select('crm_documents.id', 'crm_accounts.company')->join('crm_accounts', 'crm_accounts.id', '=', 'crm_documents.account_id')->get();
            foreach ($conversion_docs as $conversion_doc) {
                $conversion_details .= $conversion_doc->company.' #'.$conversion_doc->id.PHP_EOL;
            }

            $quote_docs = $quotes_query->select('crm_documents.id', 'crm_accounts.company')->join('crm_accounts', 'crm_accounts.id', '=', 'crm_documents.account_id')->get();
            foreach ($quote_docs as $quote_doc) {
                $quote_details .= $quote_doc->company.' #'.$quote_doc->id.PHP_EOL;
            }

            $last_lead_created_date = \DB::connection('default')->table('crm_accounts')->where('form_id', $ad_campaign->form_id)->orderBy('created_at', 'desc')->pluck('created_at')->first();
        }
    }

    $campaign_days = 0;
    if ($ad_campaign && $ad_campaign->launch_date) {
        $launch_date = \Carbon\Carbon::parse($ad_campaign->launch_date);
        $campaign_days = $launch_date->diffInDays($ad_campaign->end_date);
        if (date('Y-m-d', strtotime($ad_campaign->end_date)) > date('Y-m-d')) {
            $campaign_days = $launch_date->diffInDays(\Carbon\Carbon::parse(date('Y-m-d')));
        }
    }

    $total_spend = $ad_campaign_detail->ad_spend;
    $cost_per_click = ($ad_campaign_detail->clicks > 0) ? $total_spend / $ad_campaign_detail->clicks : $total_spend;
    $cost_per_reach = ($ad_campaign_detail->reach > 0) ? $total_spend / $ad_campaign_detail->reach : $total_spend;
    $cost_per_conversion = ($conversions > 0) ? $total_spend / $conversions : $total_spend;
    $cost_per_lead = ($num_leads > 0) ? $total_spend / $num_leads : $total_spend;
    $conversion_percentage = ($conversions && $num_leads) ? intval(($conversions / $num_leads) * 100) : 0;
    $quote_percentage = ($quotes && $num_leads) ? intval(($quotes / $num_leads) * 100) : 0;
    $data = [
        'cost_per_click' => $cost_per_click,
        'cost_per_reach' => $cost_per_reach,
        'cost_per_conversion' => $cost_per_conversion,
        'conversions' => $conversions,
        'conversion_details' => $conversion_details,
        'conversion_percentage' => $conversion_percentage,
        'quotes' => $quotes,
        'quote_details' => $quote_details,
        'quote_percentage' => $quote_percentage,
        'num_leads' => $num_leads,
        'cost_per_lead' => $cost_per_lead,
        'last_lead_created_date' => $last_lead_created_date,
    ];

    \DB::table('crm_ad_campaign_details')->where('id', $ad_campaign_detail->id)->update($data);
}

function get_facebook_ad_stats($ad_campaign)
{
    $adAccountId = $ad_campaign->facebook_campaign_id;
    $accessToken = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    $client = new GuzzleHttp\Client();
    $url = 'https://graph.facebook.com/v19.0/'.$adAccountId.'/insights';

    $query = [
        //'level' => 'ad',
        'access_token' => $accessToken,
        'fields' => 'ad_id,impressions,clicks,spend,reach',
        //'time_range' => '{"since":"2022-01-01","until":"2022-01-31"}', // Modify the time range as needed
    ];

    if (!empty($ad_campaign->launch_date) && !empty($ad_campaign->end_date)) {
        $query['time_range'] = '{"since":"'.$ad_campaign->launch_date.'","until":"'.$ad_campaign->end_date.'"}';
    }

    try {
        $response = $client->get($url, [
            'query' => $query,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        //aa($statusCode);
        //aa($body);
        $data = [];
        if ($statusCode === 200) {
            $data = json_decode($body, true);
        } else {
            return false;
        }
        if (!empty($data['data'])) {
            return $data['data'];
        }

        return false;
    } catch (\Throwable $ex) {
        exception_log($ex);
        aa($ex->getMessage());
        aa($ex->getTraceAsString());

        return false;
    }
}

function schedule_weekly_update_facebook_token_expiry()
{
    if (!is_main_instance()) {
        return false;
    }
    $accessToken = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    $client = new GuzzleHttp\Client();
    $url = 'https://graph.facebook.com/v12.0/debug_token';

    try {
        $response = $client->get($url, [
            'query' => [
                'input_token' => $accessToken,
                'access_token' => $accessToken, // Your Facebook app access token
            ],
        ]);
        $data = null;
        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            $data = json_decode($response->getBody(), true);
        } else {
            //return false; // Error occurred
        }

        if (!empty($data['data']) && !empty($data['data']['data_access_expires_at'])) {
            $expiry = date('Y-m-d H:i:s', $data['data']['data_access_expires_at']);
        }
        if (!empty($expiry)) {
            \DB::table('crm_facebook_access_tokens')->where('user_longlived_token', $accessToken)->update(['user_token_expires_at' => $expiry]);
        }

        return false;
    } catch (\Throwable $e) {
        return false; // Error occurred
    }
}
