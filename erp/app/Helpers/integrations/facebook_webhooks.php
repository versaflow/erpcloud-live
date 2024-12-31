<?php

function schedule_import_facebook_leads()
{
    $active_ads = \DB::table('crm_ad_campaigns')
        ->where('status', 'Published')
        ->where('form_id', '>', '')->get();
    // dd($active_ads);
    foreach ($active_ads as $ad) {
        if (! $ad->form_name) {
            $form = fb_get_form_details($ad->form_id);
            // dd($form);
            \DB::table('crm_ad_campaigns')->where('id', $ad->id)->update(['form_name' => $form['name']]);
        }
    }

    $active_ads = \DB::table('crm_ad_campaigns')
        ->where('status', 'Published')
        ->where('form_id', '>', '')->get();

    foreach ($active_ads as $active_ad) {
        $leads = fb_get_leads($active_ad->form_id);
        $campaign_id_set = false;

        // dd($leads['data']);
        foreach ($leads['data'] as $lead) {
            if ($lead['ad_id'] && ! $campaign_id_set) {
                $campaign_id_set = true;
                $ad_details = fb_get_ad_details($lead['ad_id']);
                \DB::table('crm_ad_campaigns')
                    ->where('id', $ad->id)
                    ->update(['facebook_campaign_id' => $ad_details['campaign_id']]);
            }

            $exists = \DB::table('crm_accounts')->where('external_id', $lead['id'])->count();
            if (! $exists) {
                $field_data = $lead['field_data'];

                $lead_data = collect($field_data)->mapWithKeys(function ($item) {
                    // aa($item);

                    return [strtolower($item['name']) => $item['values'][0]];
                })->toArray();

                $company = $lead_data['full_name'];
                if (! empty($lead_data['company_name'])) {
                    $company = $lead_data['company_name'];
                }

                $marketing_lead = [
                    'type' => 'lead',
                    'contact' => $lead_data['full_name'],
                    'company' => $company,
                    'status' => 'Enabled',
                    'pricelist_id' => 1,
                    'partner_id' => 1,
                    'email' => $lead_data['email'],
                    'phone' => $lead_data['phone'],
                    'external_id' => $lead['id'],
                    'form_name' => $active_ad->form_name,
                    'form_id' => $active_ad->form_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => get_system_user_id(),
                    'source' => 'Facebook',
                    'deal_status' => 'New Enquiry',
                ];

                if (! empty($lead_data['street_address'])) {
                    $marketing_lead['address'] = $lead_data['street_address'];
                }

                $account_currency = \DB::table('crm_ad_campaigns')->where('form_id', $post_data['form_id'])->pluck('account_currency')->first();
                if ($account_currency == 'USD') {
                    $marketing_lead['currency'] = 'USD';
                    $marketing_lead['pricelist_id'] = 2;
                } else {
                    $marketing_lead['currency'] = 'ZAR';
                    $marketing_lead['pricelist_id'] = 1;
                }

                $id = \DB::table('crm_accounts')->insertGetId($marketing_lead);
            }
        }
    }
    schedule_assign_customers_to_salesman();
    // button_leads_import_from_accounts(); -  Opportunities
}

//https://developers.facebook.com/docs/graph-api/webhooks/getting-started/webhooks-for-leadgen/

function fb_get_ad_details($ad_id)
{
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    $query = [
        'access_token' => $user_access_token,
        'fields' => 'campaign_id,account_id',
    ];

    // Make a request to fetch all pages
    $response = $client->get($ad_id, [
        'query' => $query,
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);

    return $data;
}

function fb_get_form_details($form_id)
{
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    $query = [
        'access_token' => $user_access_token,
    ];

    // Make a request to fetch all pages
    $response = $client->get($form_id, [
        'query' => $query,
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);

    return $data;
}

function fb_get_leads($ad_or_form_id, $created_time = false, $data = null)
{
    $app_id = env('FB_APP_ID');
    $app_secret = env('FB_APP_SECRET');
    $user_access_token = \DB::table('crm_facebook_access_tokens')->pluck('user_longlived_token')->first();
    // Initialize Guzzle client
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://graph.facebook.com/v12.0/', // Facebook Graph API version
        'timeout' => 30,
    ]);

    $query = [
        'access_token' => $user_access_token,
        'fields' => 'created_time,id,ad_id,form_id,form_name,field_data',
        'limit' => 400,
    ];

    // if ($created_time) {
    //     $query['filtering'] = json_encode([[
    //         'field' => 'time_created',
    //         'operator' => 'GREATER_THAN',
    //         'value' => strtotime($created_time),
    //     ]]);
    // }

    // if ($data) {
    //     $query = [
    //         'after' => $data['paging']['after'],
    //     ];
    // }
    // Make a request to fetch all pages
    $response = $client->get($ad_or_form_id.'/leads', [
        'query' => $query,
    ]);

    // Process the API response
    $data = json_decode($response->getBody(), true);
    // vd($data);
    if ($data['paging']['next']) {
        fb_get_leads($ad_or_form_id, $created_time, $data);
    }

    // dd($data);

    return $data;
}

function button_facebook_import_leads($request)
{
    $data = [];

    return view('__app.button_views.facebook_leads_import', $data);
}

function facebook_import_leads_from_form_id($form_id)
{
    $leads_imported = 0;
    $existing_leads = 0;
    try {
        $form = fb_get_form_details($form_id);
    } catch (\Throwable $ex) {
    }
    if (empty($form) || empty($form['id'])) {
        return ['message' => 'Invalid Form', 'status' => 'error'];
    }
    $form_name = $form['name'];
    $leads = fb_get_leads($form_id);

    foreach ($leads['data'] as $lead) {
        $exists = \DB::table('crm_accounts')->where('external_id', $lead['id'])->count();

        if (! $exists) {
            $field_data = $lead['field_data'];

            $lead_data = collect($field_data)->mapWithKeys(function ($item) {
                return [strtolower($item['name']) => $item['values'][0]];
            })->toArray();

            $company = $lead_data['full_name'];
            if (! empty($lead_data['company_name'])) {
                $company = $lead_data['company_name'];
            }

            $marketing_lead = [
                'type' => 'lead',
                'contact' => $lead_data['full_name'],
                'company' => $company,
                'status' => 'Enabled',
                'pricelist_id' => 1,
                'partner_id' => 1,
                'email' => $lead_data['email'],
                'phone' => $lead_data['phone'],
                'source' => 'Facebook',
                'external_id' => $lead['id'],
                'form_name' => $form_name,
                'form_id' => $form_id,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => get_system_user_id(),
            ];

            if (! empty($lead_data['street_address'])) {
                $marketing_lead['address'] = $lead_data['street_address'];
            }

            $account_currency = \DB::table('crm_ad_campaigns')->where('form_id', $post_data['form_id'])->pluck('account_currency')->first();
            if ($account_currency == 'USD') {
                $marketing_lead['currency'] = 'USD';
                $marketing_lead['pricelist_id'] = 2;
            } else {
                $marketing_lead['currency'] = 'ZAR';
                $marketing_lead['pricelist_id'] = 1;
            }

            $id = \DB::table('crm_accounts')->insertGetId($marketing_lead);
            $leads_imported++;
        } else {
            $existing_leads++;
        }
    }

    schedule_assign_customers_to_salesman();
    if ($leads_imported > 0) {
        return ['message' => $leads_imported.' Leads imported', 'status' => 'success'];
    } else {
        return ['message' => $existing_leads.' leads already exists. No new leads imported.', 'status' => 'success'];
    }
}
