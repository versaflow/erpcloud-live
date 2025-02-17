<?php

function get_payment_options()
{
    return \DB::connection('default')->table('erp_payment_options')->where('is_deleted', 0)->get();
}

function get_payment_option($name)
{
    return \DB::connection('default')->table('erp_payment_options')->where('name', $name)->where('is_deleted', 0)->get()->first();
}
