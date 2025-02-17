<?php

function button_low_asr_ani_truncate($request)
{
    \DB::connection('pbx')->table('mon_low_asr_ani')->update(['is_deleted'=>1]);
    return json_alert('Done');
}

function button_low_asr_caller_truncate($request)
{
    \DB::connection('pbx')->table('mon_low_asr_caller')->update(['is_deleted'=>1]);
    return json_alert('Done');
}

function button_low_asr_callee_truncate($request)
{
    \DB::connection('pbx')->table('mon_low_asr_callee')->update(['is_deleted'=>1]);
    return json_alert('Done');
}

function button_24hour_block_truncate($request)
{
    \DB::connection('pbx')->table('mon_block_test_calls')->update(['is_deleted'=>1]);
    return json_alert('Done');
}
