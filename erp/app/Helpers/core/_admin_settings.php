<?php

function get_admin_settings()
{
    return \DB::connection('default')->table('erp_admin_settings')->where('id', 1)->get()->first();
}

function get_admin_setting($key)
{
    return \DB::connection('default')->table('erp_admin_settings')->where('id', 1)->pluck($key)->first();
}
function set_admin_setting($key, $val)
{
    return \DB::connection('default')->table('erp_admin_settings')->where('id', 1)->update([$key => $val]);
}
