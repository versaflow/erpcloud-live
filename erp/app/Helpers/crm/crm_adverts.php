<?php

function schedule_ads_update_website_stats()
{

    $ads = \DB::table('crm_ad_campaigns')->where('status', 'Published')->get();
    foreach ($ads as $ad) {
        adverts_update_website_stats($ad->id);
    }
}

function adverts_update_website_stats($ad_id)
{
    // cost per click
    // num days between ad campaign launch date and end date
    $ad_campaign = \DB::table('crm_ad_campaigns')->where('id', $ad_id)->get()->first();
    $channel_id = \DB::table('crm_ad_channels')->where('website', 'telecloud.co.za')->pluck('id')->first();
    $detail_exists = \DB::table('crm_ad_campaign_details')->where('ad_campaign_id', $ad_campaign->id)->where('channel_id', $channel_id)->count();

    if (! $detail_exists) {
        \DB::table('crm_ad_campaign_details')->insert(['ad_campaign_id' => $ad_campaign->id, 'channel_id' => $channel_id]);
    }

    $ad_campaign_detail = \DB::table('crm_ad_campaign_details')->where('ad_campaign_id', $ad_campaign->id)->where('channel_id', $channel_id)->get()->first();
    $instance_conn = \DB::table('erp_instances')->where('id', $ad_campaign->instance_id)->pluck('db_connection')->first();

    $last_lead_created_date = null;
    $conversions = 0;
    $quotes = 0;
    $conversion_details = '';
    $quote_details = '';

    $erp_values = \DB::connection($instance_conn)->table('crm_wordpress_links')->where('type', 'customer')->pluck('erp_value')->toArray();
    $account_ids_query = \DB::connection($instance_conn)->table('crm_accounts')->whereIn('id', $erp_values);

    if ($ad_campaign && $ad_campaign->launch_date) {
        if (! empty($ad_campaign->launch_date)) {
            $account_ids_query->where('crm_accounts.created_at', '>=', $ad_campaign->launch_date);
        }
        if (! empty($ad_campaign->end_date)) {
            $account_ids_query->where('crm_accounts.created_at', '<=', $ad_campaign->end_date);
        }
    }

    $account_ids = $account_ids_query->pluck('id')->toArray();
    \DB::connection($instance_conn)->table('crm_documents')->whereIn('account_id', $account_ids)->update(['ad_source' => $ad_campaign->name]);
    $num_leads = count($account_ids);
    if ($account_ids) {
        $doc_query = \DB::connection($instance_conn)->table('crm_documents')
            ->where('crm_documents.doctype', 'Tax Invoice')
            ->where('crm_documents.reversal_id', 0)
            ->where('crm_documents.billing_type', '')
            ->whereIn('crm_documents.account_id', $account_ids);

        $quotes_query = \DB::connection($instance_conn)->table('crm_documents')
            ->where('crm_documents.doctype', 'Quotation')
            ->where('crm_documents.reversal_id', 0)
            ->where('crm_documents.billing_type', '')
            ->whereIn('crm_documents.account_id', $account_ids);

        if ($ad_campaign && $ad_campaign->launch_date) {
            if (! empty($ad_campaign->launch_date)) {
                $doc_query->where('crm_documents.docdate', '>=', $ad_campaign->launch_date);
            }
            if (! empty($ad_campaign->launch_date)) {
                $quotes_query->where('crm_documents.docdate', '>=', $ad_campaign->launch_date);
            }
        }

        if ($ad_campaign && $ad_campaign->end_date) {
            if (! empty($ad_campaign->end_date)) {
                $doc_query->where('crm_documents.docdate', '<=', date('Y-m-d', strtotime($ad_campaign->end_date.' +2 weeks')));
            }
            if (! empty($ad_campaign->end_date)) {
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

        $last_lead_created_date = \DB::connection($instance_conn)->table('crm_accounts')->whereIn('id', $account_ids)->orderBy('created_at', 'desc')->pluck('created_at')->first();
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

function button_adchannels_copy_to_workboard($request)
{
    $channels = \DB::table('crm_ad_channels')->where('is_deleted', 0)->orderBy('sort_order')->get()->groupBy('website');
    $sort_order = 0;
    foreach ($channels as $group => $list) {
        $data = [];

        $data['name'] = $group;

        $data['role_id'] = 35;
        $data['instance_id'] = session('instance')->id;
        $data['type'] = 'Task';
        $data['progress_status'] = 'Not Done';
        $data['sort_order'] = $sort_order;

        $group_id = \DB::table('crm_staff_tasks')->insertGetId($data);

        foreach ($list as $channel) {
            $data = [];

            $data['name'] = $channel->name;
            $data['parent_id'] = $group_id;

            $data['role_id'] = 35;
            $data['instance_id'] = session('instance')->id;
            $data['type'] = 'Task';
            $data['progress_status'] = 'Not Done';
            $data['sort_order'] = $sort_order;

            \DB::table('crm_staff_tasks')->insert($data);
            $sort_order++;
        }
        $sort_order++;
    }

    return json_alert('Done');
}

function get_latest_ads_links()
{
    $list = [];
    $i = 20000;
    $ads = \DB::connection('default')->table('crm_ad_campaigns')->select('name', 'ad_image_file', 'instance_id')->orderBy('instance_id')->where('is_deleted', 0)->where('ad_image_file', '>', '')->where('status', 'Published')->get();

    foreach ($ads as $ad) {
        $instance_name = \DB::connection('default')->table('erp_instances')->where('id', $ad->instance_id)->pluck('name')->first();
        $file_url = uploads_url(2031).$ad->ad_image_file;

        $list[] = ['url' => $file_url, 'menu_name' => $instance_name.' - '.$ad->name, 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];

    }

    return $list;
}

function button_create_newsletter_from_ad($request)
{
    $ad_id = $request->id;
    $ad = \DB::table('crm_ad_campaigns')
        ->where('ad_image_file', '>', '')
        ->where('id', $ad_id)
        ->get()->first();

    if (empty($ad)) {
        return json_alert('Invalid ad', 'warning');
    }

    $db = new DBEvent;

    $imagePath = uploads_path(2031).$ad->ad_image_file;
    if (! file_exists($imagePath)) {
        return json_alert('Ad image file not found', 'warning');
    }
    $imageData = file_get_contents($imagePath);
    $base64Image = base64_encode($imageData);
    $imgTag = '<img src="data:image/jpeg;base64,'.$base64Image.'" alt="'.$ad->name.'" style="max-width: 600px;">';

    $db = new DBEvent;
    $db->setTable('crm_newsletters');
    $data = [
        'name' => $ad->name,
        'email_html' => $imgTag,
        'type' => 'Advertising',
    ];

    $result = $db->save($data);

    if ($result && is_array($result) && $result['id']) {
        $menu_route = get_menu_url_from_table('crm_newsletters');

        return json_alert('Done', 'success', ['new_tab' => $menu_route.'?id='.$result['id']]);
    }

    return json_alert('Newsletter create error', 'warning');
}

function ad_channels_set_active_campaigns()
{
    \DB::table('crm_ad_channels')->update(['active_campaigns' => '']);
    $channels = \DB::table('crm_ad_channels')->where('is_deleted', 0)->where('status', 'Active')->get();
    foreach ($channels as $channel) {
        $active_campaigns = '';
        $campaing_names = \DB::table('crm_ad_campaigns')
            ->join('crm_ad_campaign_details', 'crm_ad_campaign_details.ad_campaign_id', '=', 'crm_ad_campaigns.id')
            ->where('crm_ad_campaign_details.is_deleted', 0)
            ->where('crm_ad_campaigns.is_deleted', 0)
            ->where('crm_ad_campaigns.status', 'Published')
            ->where('crm_ad_campaign_details.channel_id', $channel->id)
            ->pluck('crm_ad_campaigns.name')->unique()->toArray();
        if (! empty($campaing_names) && count($campaing_names) > 0) {
            $active_campaigns = implode(',', $campaing_names);
        }
        \DB::table('crm_ad_channels')->where('id', $channel->id)->update(['active_campaigns' => $active_campaigns]);
    }
}
