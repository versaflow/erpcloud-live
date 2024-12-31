<?php

function beforedelete_campaign_remove_from_accounts($request)
{
    \DB::table('crm_accounts')->where('campaign_id', $request->id)->update(['campaign_id' => 0]);
}
