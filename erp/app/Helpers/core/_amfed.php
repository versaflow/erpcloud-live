<?php

function button_donation_campaign_complete($request)
{
    \DB::table('crm_donation_campaigns')->where('id', $request->id)->update(['status'=>'Complete']);
    return json_alert('Campaign completed.');
}
