<?php

function pinnumbers_extension_select($row)
{
    $row = (object) $row;
    if (empty($row) || empty($row->domain_uuid)) {
        return [];
    }
    $domain_uuid = $row->domain_uuid;
    $routing = [];

    $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
    foreach ($extensions as $ext) {
        $routing[$ext->extension] = $ext->extension;
    }

    return $routing;
}


function aftersave_pin_numbers_accountcode($request){
    
    $pin = \DB::connection('pbx')->table('v_pin_numbers')->where('pin_number_uuid',$request->pin_number_uuid)->get()->first();
    $domain_uuid = $pin->domain_uuid;
    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid',$domain_uuid)->pluck('domain_name')->first();
    \DB::connection('pbx')->table('v_pin_numbers')->where('pin_number_uuid',$request->pin_number_uuid)->update(['accountcode'=>$domain_name]);
}