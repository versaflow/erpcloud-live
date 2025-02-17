<?php

function aftersave_callblock_set_number_format($request)
{

    $number = za_number_format($request->call_block_number);
    if (! $number) {
        return json_alert('Invalid call_block_number', 'warning');
    }
    \DB::connection('pbx')->table('v_call_block')->where('call_block_uuid', $request->call_block_uuid)->update(['call_block_number' => $number]);
    $call_block = \DB::connection('pbx')->table('v_call_block')->where('call_block_uuid', $request->call_block_uuid)->get()->first();

    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $call_block->domain_uuid)->pluck('domain_name')->first();

    //clear the cache
    $pbx = new \FusionPBX;
    $pbx->portalCmd('delete_cache_item', 'app:call_block:'.$domain_name.':'.$call_block->call_block_country_code.$call_block->call_block_number);
    $pbx->portalCmd('delete_cache_item', 'app:call_block:'.$domain_name.':'.$call_block->call_block_number);
}
