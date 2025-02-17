<?php

// create submenu function to list pbx menu with groups access

// store v_domains account_ids for each groups in js array in button_selected js
function get_pbx_domain_groups_js()
{
    $groups = \DB::connection('pbx')->table('v_groups')->pluck('group_name')->unique()->filter()->toArray();
    $js = '';
    foreach ($groups as $group) {
        $domain_uuids = \DB::connection('pbx')->table('v_user_groups')->where('group_name', $group)->pluck('domain_uuid')->filter()->unique()->toArray();

        $account_ids = \DB::connection('pbx')->table('v_domains')->whereIn('domain_uuid', $domain_uuids)->pluck('account_id')->filter()->unique()->toArray();
        $domain_names = \DB::connection('pbx')->table('v_domains')->whereIn('domain_uuid', $domain_uuids)->pluck('domain_name')->filter()->unique()->toArray();
        if ($group == 'superadmin' && (is_superadmin() || is_dev())) {
            $account_ids[] = 12;
        }
        if (count($account_ids) > 0) {
            $js .= 'window["pbx_group_accountids'.str_replace(' ', '', strtolower($group)).'"] = ['.implode(',', $account_ids).']'.PHP_EOL;
            $js .= 'window["pbx_group_domainuuids'.str_replace(' ', '', strtolower($group)).'"] = ["'.implode('","', $domain_uuids).'"]'.PHP_EOL;
            $js .= 'window["pbx_group_domainnames'.str_replace(' ', '', strtolower($group)).'"] = ["'.implode('","', $domain_names).'"]'.PHP_EOL;
        } else {
            $js .= 'window["pbx_group_accountids'.str_replace(' ', '', strtolower($group)).'"] = []'.PHP_EOL;
            $js .= 'window["pbx_group_domainuuids'.str_replace(' ', '', strtolower($group)).'"] = []'.PHP_EOL;
            $js .= 'window["pbx_group_domainnames'.str_replace(' ', '', strtolower($group)).'"] = []'.PHP_EOL;
        }
        // $js.= 'console.log("'.str_replace(' ','',strtolower($group)).'",window["pbx_group'.str_replace(' ','',strtolower($group)).'"])'.PHP_EOL;
    }

    return $js;
}
