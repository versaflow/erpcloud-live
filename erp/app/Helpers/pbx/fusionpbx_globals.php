<?php

function schedule_clone_users_table_to_pbx()
{
    return false;
    if (! is_main_instance()) {
        return false;
    }
    \DB::connection('pbx')->table('erp_users')->truncate();
    $users = \DB::table('erp_users')->get();
    foreach ($users as $u) {
        $d = (array) $u;
        \DB::connection('pbx')->table('erp_users')->updateOrInsert(['id' => $u->id], $d);
    }
}

function select_options_ringbacks($row)
{

    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $ringtones = \DB::connection('pbx')->table('v_vars')->where('var_category', 'Ringtones')->orderBy('var_name')->pluck('var_name')->toArray();

    $tones = \DB::connection('pbx')->table('v_vars')->where('var_category', 'Ringtones')->orderBy('var_name')->pluck('var_name')->toArray();

    $domain_uuid = $row->domain_uuid;
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->pluck('domain_name')->first();

    $routing = [];

    $recordings = \DB::connection('pbx')->table('v_recordings')->where('domain_uuid', $domain_uuid)->orderby('recording_filename')->get();
    foreach ($recordings as $ext) {
        $routing[$ext->recording_filename] = 'Recording '.$ext->recording_filename;
    }

    $mohs = \DB::connection('pbx')->table('v_music_on_hold')->where('domain_uuid', $domain_uuid)->orderby('music_on_hold_name')->get();
    $routing['local_stream://default'] = 'Music on hold '.'Default';
    foreach ($mohs as $moh) {
        $routing['local_stream://'.$domain_name.'/'.$moh->music_on_hold_name] = 'Music on hold '.$moh->music_on_hold_name;
    }

    foreach ($ringtones as $ringtone) {
        $routing['${'.$ringtone.'}'] = 'Ringtone '.$ringtone;

    }

    foreach ($tones as $tone) {
        $routing['${'.$tone.'}'] = 'Tone '.$tone;

    }

    return $routing;

}

function move_unlimited_ext_to_channels()
{
    return false;
    $domain_uuids = \DB::connection('pbx')->table('v_extensions')->where('is_unlimited', 1)->pluck('domain_uuid')->unique()->toArray();
    $domains = \DB::connection('pbx')->table('v_domains')->whereIn('domain_uuid', $domain_uuids)->get();

    // set usages
    foreach ($domain_uuids as $domain_uuid) {
        $num_extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->where('is_unlimited', 1)->count();
        if ($num_extensions > 0) {
            $unlimited_channels_usage = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->where('is_unlimited', 1)->sum('unlimited_usage');
            $unlimited_channels_average = $unlimited_channels_usage / $num_extensions;
            \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->update(['unlimited_channels_usage' => $unlimited_channels_usage, 'unlimited_channels_average' => $unlimited_channels_average]);
        }
    }

    // duplicate subscriptions

    $subscriptions = \DB::table('sub_services')->where('product_id', 1394)->where('status', '!=', 'Deleted')->get();
    foreach ($subscriptions as $s) {
        $data = (array) $s;
        unset($data['id']);
        $data['product_id'] = 130;
        \DB::table('sub_services')->insert($data);

        \DB::table('sub_services')->where('id', $s->id)->update(['provision_type' => 'unlimited_channel', 'detail' => 'channel_'.$s->id]);
    }

    // channels to be half of unlimited extensions
    foreach ($domains as $domain) {

        $num_extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->where('is_unlimited', 1)->count();
        $num_channel_subscriptions = ceil($num_extensions / 2);
        $subscription_count = \DB::table('sub_services')->where('account_id', $domain->account_id)->where('provision_type', 'unlimited_channel')->count();

        if ($num_channel_subscriptions < $subscription_count) {
            $num_channel_subscriptions_to_remove = $subscription_count - $num_channel_subscriptions;
            $subs_to_remove = \DB::table('sub_services')->where('account_id', $domain->account_id)->where('provision_type', 'unlimited_channel')->limit($num_channel_subscriptions_to_remove)->get();

            foreach ($subs_to_remove as $sr) {
                \DB::table('sub_services')->where('id', $sr->id)->update(['status' => 'Deleted', 'deleted_at' => date('Y-m-d H:i:s')]);
            }
        }
    }
}
