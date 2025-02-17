<?php

function widget_function_16()
{
    return \DB::table('crm_documents')->where('doctype', 'Tax Invoice')->where('docdate', 'like', date('Y-m').'%')->sum('total');
}
