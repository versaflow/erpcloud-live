<?php

function aftersave_blocked_destinations_update_lcr($request)
{
    admin_rates_summary_set_lowest_active();
}
function afterdelete_blocked_destinations_update_lcr($request)
{
    admin_rates_summary_set_lowest_active();
}

function beforesave_rates_summary_check_disabled()
{
    if ($request->id) {
        $beforesave_row = session('event_db_record');
        if ($beforesave_row->status == 'Enabled' && $request->status == 'Disabled') {
            $rate = \DB::connection('pbx')->table('p_rates_summary')->where('id', $request->id)->get()->first();

            $num_rates = \DB::connection('pbx')->table('p_rates_summary')
                ->where('country', $rate->country)
                ->where('destination', $rate->destination)
                ->where('status', 'Enabled')
                ->where('id', '!=', $request->id)->count();

            if ($num_rates == 0) {
                return 'Rate cannot be disabled, each destination requires an active rate.';
            }
        }
    }
}

function import_rates_summary_from_rates_complete($gateway_uuid = false)
{
    /// IMPORT RATES COMPLETE START
    $local_destinations = ['fixed telkom', 'fixed liquid', 'mobile cellc', 'mobile mtn', 'mobile vodacom', 'fixed tollfree', 'fixed sharecall', 'mobile telkom'];
    // $lcr_countries = \DB::connection('pbx')->table('p_rates_summary_countries')->where('status', 'Enabled')->pluck('country')->toArray();
    $lcr_countries = \DB::connection('pbx')->table('p_rates_complete')->select('country')->groupBy('country')->pluck('country')->toArray();
    \DB::connection('pbx')->table('p_rates_summary')->whereNotIn('country', $lcr_countries)->delete();

    /// LOCAL RATES
    $rates = [];
    if ($gateway_uuid) {
        // $local_gateways = \DB::connection('pbx')->table('v_gateways')->where('use_rate', 1)->where('gateway_uuid', $gateway_uuid)->get();
        // $international_gateways = \DB::connection('pbx')->table('v_gateways')->where('use_rate_international', 1)->where('gateway_uuid', $gateway_uuid)->get();
        $local_gateways = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->get();
        $international_gateways = \DB::connection('pbx')->table('v_gateways')->where('gateway_uuid', $gateway_uuid)->get();
    } else {
        $local_gateways = \DB::connection('pbx')->table('v_gateways')->where('use_rate', 1)->get();
        $international_gateways = \DB::connection('pbx')->table('v_gateways')->where('use_rate_international', 1)->get();
    }
    /// LOCAL NETWORKS
    $destinations = \DB::connection('pbx')->table('p_rates_destinations')
        ->select('country', 'destination')
        ->where('country', 'south africa')
        ->whereIn('destination', $local_destinations)
        ->groupBy('country', 'destination')
        ->get();

    $unique_supplier_check = [];
    $existing_summary = \DB::connection('pbx')->table('p_rates_summary')->get();
    foreach ($existing_summary as $e) {
        $identifier = $e->gateway_uuid.$e->country.$e->destination;
        if (! in_array($identifier, $unique_supplier_check)) {
            $unique_supplier_check[] = $identifier;
            \DB::connection('pbx')->table('p_rates_summary')
                ->where('id', '!=', $e->id)
                ->where('gateway_uuid', $e->gateway_uuid)
                ->where('country', $e->country)
                ->where('destination', $e->destination)
                ->delete();
        }
    }

    foreach ($destinations as $d) {
        $data = (array) $d;
        $destination_ids = \DB::connection('pbx')->table('p_rates_destinations')
            ->where('country', $d->country)
            ->where('destination', $d->destination)
            ->pluck('id')
            ->toArray();

        foreach ($local_gateways as $gateway) {
            $max_rate = \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $gateway->gateway_uuid)->where('destination', $d->destination)->pluck('cost_limit')->first();
            if (! empty($max_rate) && $max_rate > 0) {
                $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->where('cost', '<', $max_rate)->orderBy('cost', 'desc')->get()->first();
                if (empty($rate)) {
                    $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->orderBy('cost', 'desc')->get()->first();

                    $rate->cost = 0;
                }
            } else {
                $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->orderBy('cost', 'desc')->get()->first();
            }

            if (! empty($rate)) {
                $rate = (array) $rate;
                unset($rate['destination']);
                $summary = array_merge($data, $rate);
                $rates[] = $summary;
            }
        }
    }
    /// FIXED OTHER
    $destination_ids = \DB::connection('pbx')->table('p_rates_destinations')
        ->where('country', 'south africa')
        ->where('id', 'not like', '2786%')
        ->whereNotIn('destination', ['fixed tollfree', 'fixed sharecall'])
        ->whereNotIn('destination', $local_destinations)
        ->pluck('id')->toArray();

    foreach ($local_gateways as $gateway) {
        $max_rate = \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $gateway->gateway_uuid)->where('destination', 'fixed other')->pluck('cost_limit')->first();
        if (! empty($max_rate) && $max_rate > 0) {
            $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->where('cost', '<', $max_rate)->orderBy('cost', 'desc')->get()->first();
            if (empty($rate)) {
                $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->orderBy('cost', 'desc')->get()->first();

                $rate->cost = 0;
            }
        } else {
            $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->orderBy('cost', 'desc')->get()->first();
        }
        if (! empty($rate)) {
            $rate = (array) $rate;
            unset($rate['destination']);
            $rate['destination'] = 'fixed other';
            $summary = $rate;

            $rates[] = $summary;
        }
    }
    /// FIXED OTHER
    $destination_ids = \DB::connection('pbx')->table('p_rates_destinations')
        ->where('country', 'south africa')
        ->where('id', 'like', '2786%')
        ->pluck('id')->toArray();

    foreach ($local_gateways as $gateway) {
        $max_rate = \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $gateway->gateway_uuid)->where('destination', 'fixed other')->pluck('cost_limit')->first();
        if (! empty($max_rate) && $max_rate > 0) {
            $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->where('cost', '<', $max_rate)->orderBy('cost', 'desc')->get()->first();
            if (empty($rate)) {
                $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->orderBy('cost', 'desc')->get()->first();

                $rate->cost = 0;
            }
        } else {
            $rate = \DB::connection('pbx')->table('p_rates_complete')->where('gateway_uuid', $gateway->gateway_uuid)->whereIn('destination_id', $destination_ids)->orderBy('cost', 'desc')->get()->first();
        }
        if (! empty($rate)) {
            $rate = (array) $rate;
            unset($rate['destination']);
            $rate['destination'] = 'fixed sharecall';
            $summary = $rate;

            $rates[] = $summary;
        }
    }

    foreach ($rates as $rate) {
        $rate = (object) $rate;

        $lcr = [
            'gateway_uuid' => $rate->gateway_uuid,
            'country' => strtolower($rate->country),
            'destination' => $rate->destination,
            'cost_zar' => currency($rate->cost),
        ];

        $exists = \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $rate->gateway_uuid)->where('destination', $rate->destination)->where('country', $rate->country)
            ->count();

        if (! $exists) {
            \DB::connection('pbx')->table('p_rates_summary')->insert($lcr);
        } else {
            \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $rate->gateway_uuid)->where('destination', $rate->destination)->where('country', $rate->country)->update($lcr);
        }
    }

    /// INTERNATIONAL RATES
    $mobile_rate_query = \DB::connection('pbx')->table('p_rates_complete')
        ->selectRaw('gateway_uuid,country,MAX(destination_id) as destination_id,MAX(cost) as cost')
        ->where('cost', '<', 10)
        ->whereIn('country', $lcr_countries)
        ->where('country', '!=', 'south africa')
        ->where('destination', 'like', '%mobile%')
        ->orderBy('cost', 'desc')
        ->groupBy('gateway_uuid')->groupBy('country');

    $rates = $mobile_rate_query->get();
    foreach ($rates as $rate) {
        $cost = $rate->cost;
        $max_rate = \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $rate->gateway_uuid)
            ->where('country', strtolower($rate->country))
            ->where('destination', 'mobile')
            ->pluck('cost_limit')->first();
        if (! empty($max_rate) && $max_rate > 0) {
            $cost = \DB::connection('pbx')->table('p_rates_complete')
                ->selectRaw('*,MAX(cost) as cost')
                ->where('country', strtolower($rate->country))
                ->where('gateway_uuid', $rate->gateway_uuid)
                ->where('destination', 'like', '%mobile%')
                ->where('cost', '<', $max_rate)
                ->orderBy('cost', 'desc')->pluck('cost')->first();
            if (empty($cost)) {
                $cost = 0;
            }
        }

        $lcr = [
            'gateway_uuid' => $rate->gateway_uuid,
            'country' => strtolower($rate->country),
            'destination' => 'mobile',
            'cost_zar' => currency($cost),
        ];

        $exists = \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $rate->gateway_uuid)
            ->where('destination', 'mobile')
            ->where('country', $rate->country)
            ->count();

        if (! $exists) {
            \DB::connection('pbx')->table('p_rates_summary')->insert($lcr);
        } else {
            \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $rate->gateway_uuid)->where('destination', 'mobile')->where('country', $rate->country)->update($lcr);
        }
    }

    $fixed_rate_query = \DB::connection('pbx')->table('p_rates_complete')
        ->selectRaw('gateway_uuid,country,MAX(destination_id) as destination_id,MAX(cost) as cost')
        ->where('cost', '<', 10)
        ->where('country', '!=', 'south africa')
        ->whereIn('country', $lcr_countries)
        ->where('destination', 'not like', '%mobile%')
        ->orderBy('cost', 'desc')
        ->groupBy('gateway_uuid')->groupBy('country');
    $rates = $fixed_rate_query->get();
    foreach ($rates as $rate) {
        $cost = $rate->cost;
        $max_rate = \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $rate->gateway_uuid)
            ->where('country', strtolower($rate->country))
            ->where('destination', 'fixed')
            ->pluck('cost_limit')->first();
        if (! empty($max_rate) && $max_rate > 0) {
            $cost = \DB::connection('pbx')->table('p_rates_complete')
                ->selectRaw('*,MAX(cost) as cost')
                ->where('country', strtolower($rate->country))
                ->where('gateway_uuid', $rate->gateway_uuid)
                ->where('destination', 'not like', '%mobile%')
                ->where('cost', '<', $max_rate)
                ->orderBy('cost', 'desc')->pluck('cost')->first();
            if (empty($cost)) {
                $cost = 0;
            }
        }

        $lcr = [
            'gateway_uuid' => $rate->gateway_uuid,
            'country' => strtolower($rate->country),
            'destination' => 'fixed',
            'cost_zar' => currency($cost),
        ];

        $exists = \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $rate->gateway_uuid)
            ->where('destination', 'fixed')
            ->where('country', $rate->country)
            ->count();
        if (! $exists) {
            \DB::connection('pbx')->table('p_rates_summary')->insert($lcr);
        } else {
            \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $rate->gateway_uuid)->where('destination', 'fixed')->where('country', $rate->country)->update($lcr);
        }
    }

    \DB::connection('pbx')->table('p_rates_summary')
        ->where('destination', 'premium')
        ->delete();
    $premium_rate_query = \DB::connection('pbx')->table('p_rates_complete')
        ->selectRaw('gateway_uuid,country,MAX(destination_id) as destination_id,MAX(cost) as cost')
        ->whereIn('country', $lcr_countries)
        ->where('country', '!=', 'south africa')
        ->orderBy('cost', 'desc')
        ->where('cost', '>', 10)
        ->groupBy('gateway_uuid')->groupBy('country');
    $rates = $premium_rate_query->get();
    foreach ($rates as $rate) {
        $cost = $rate->cost;
        $max_rate = \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $rate->gateway_uuid)
            ->where('country', strtolower($rate->country))
            ->where('destination', 'premium')
            ->pluck('cost_limit')->first();
        if (! empty($max_rate) && $max_rate > 0) {
            $cost = \DB::connection('pbx')->table('p_rates_complete')
                ->selectRaw('*,MAX(cost) as cost')
                ->where('country', strtolower($rate->country))
                ->where('gateway_uuid', $rate->gateway_uuid)
                ->where('cost', '<', $max_rate)
                ->orderBy('cost', 'desc')->pluck('cost')->first();
            if (empty($cost)) {
                $cost = 0;
            }
        }

        $lcr = [
            'gateway_uuid' => $rate->gateway_uuid,
            'country' => strtolower($rate->country),
            'destination' => 'premium',
            'cost_zar' => currency($cost),
        ];

        $exists = \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $rate->gateway_uuid)
            ->where('destination', 'premium')
            ->where('country', $rate->country)
            ->count();
        if (! $exists) {
            \DB::connection('pbx')->table('p_rates_summary')->insert($lcr);
        } else {
            \DB::connection('pbx')->table('p_rates_summary')->where('gateway_uuid', $rate->gateway_uuid)->where('destination', 'premium')->where('country', $rate->country)->update($lcr);
        }
    }

    /// IMPORT RATES COMPLETE END

    /// UPDATE SUMMARY SORT ORDER START
    $sort_order = 0;
    $lcr = \DB::connection('pbx')->table('p_rates_summary')->where('country', 'south africa')->orderBy('destination')->orderBy('gateway_uuid')->get();
    foreach ($lcr as $l) {
        \DB::connection('pbx')->table('p_rates_summary')->where('id', $l->id)->update(['sort_order' => $sort_order]);
        $sort_order++;
    }
    $lcr = \DB::connection('pbx')->table('p_rates_summary')->where('country', '!=', 'south africa')->orderBy('country')->orderBy('destination')->orderBy('gateway_uuid')->get();
    foreach ($lcr as $l) {
        \DB::connection('pbx')->table('p_rates_summary')->where('id', $l->id)->update(['sort_order' => $sort_order]);
        $sort_order++;
    }
    /// UPDATE SUMMARY SORT ORDER END

    /// SET MARKUPS START
    $currency_rate = get_exchange_rate(null, 'USD', 'ZAR');
    foreach ($local_gateways as $gateway) {
        $gateway_uuid = $gateway->gateway_uuid;
        \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $gateway_uuid)
            ->where('cost_zar', 0)
            ->update([
                'cost_usd' => 0,
            ]);
        \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $gateway_uuid)
            ->where('cost_zar', '>', 0)
            ->update([
                'cost_usd' => \DB::raw('cost_zar/'.$currency_rate),
            ]);
    }
    foreach ($international_gateways as $gateway) {
        $gateway_uuid = $gateway->gateway_uuid;
        \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $gateway_uuid)
            ->where('cost_zar', 0)
            ->update([
                'cost_usd' => 0,
            ]);
        \DB::connection('pbx')->table('p_rates_summary')
            ->where('gateway_uuid', $gateway_uuid)
            ->where('cost_zar', '>', 0)
            ->update([
                'cost_usd' => \DB::raw('cost_zar/'.$currency_rate),
            ]);
    }
    /// SET MARKUPS END
    admin_rates_summary_set_lowest_active();
}

function rates_complete_set_summary_rate_id()
{
    $local_destinations = ['fixed telkom', 'fixed liquid', 'mobile cellc', 'mobile mtn', 'mobile vodacom', 'fixed tollfree', 'fixed sharecall', 'mobile telkom'];
    $local_destinations_placeholder = implode(',', array_map(function ($item) {
        return "'{$item}'";
    }, $local_destinations));

    $query = "
        UPDATE p_rates_complete prc
        JOIN p_rates_summary prs
        ON prc.country = prs.country
        AND prc.gateway_uuid = prs.gateway_uuid
        SET prc.summary_rate_id = prs.id
        WHERE
            (prs.country = 'south africa' AND (
                (prs.destination = 'fixed other' AND prc.destination NOT IN ($local_destinations_placeholder)) OR
                prc.destination = prs.destination
            )) OR
            (prs.country <> 'south africa' AND (
                (prs.destination = 'premium' AND prc.cost > 10) OR
                (prs.destination = 'mobile' AND prc.destination LIKE '%mobile%') OR
                (prs.destination = 'fixed' AND prc.destination NOT LIKE '%mobile%')
            ))
    ";
    //print_r($query);exit;
    \DB::connection('pbx')->statement($query);
    /*
    $local_destinations = ['fixed telkom','fixed liquid','mobile cellc','mobile mtn','mobile vodacom','fixed tollfree','fixed sharecall','mobile telkom'];



    $rates = \DB::connection('pbx')->table('p_rates_summary')->select('id','country','destination','gateway_uuid')->get();
    foreach($rates as $rate){
       $update_query = \DB::connection('pbx')->table('p_rates_complete')->where('country',$rate->country)->where('gateway_uuid',$rate->gateway_uuid);
       if($rate->country == 'south africa'){
           if($rate->destination == 'fixed other'){
               $update_query->whereNotIn('destination',$local_destinations);
           }else{
               $update_query->where('destination',$rate->destination);
           }
       }else{
           if($rate->destination == 'premium'){
               $update_query ->where('cost', '>', 10);
           }elseif($rate->destination == 'mobile'){
               $update_query->where('destination', 'like', '%mobile%');
           }elseif($rate->destination == 'fixed'){
               $update_query->where('destination', 'not like', '%mobile%');
           }
       }
       $update_query->update(['summary_rate_id' => $rate->id]);
    }
    */
}

function admin_rates_summary_set_lowest_active()
{
    try {
        // copy usa fixed to mobile
        \DB::connection('pbx')->table('p_rates_summary')->where('country', 'united states of america')->where('destination', 'mobile')->delete();
        $usa_rates = \DB::connection('pbx')->table('p_rates_summary')->where('country', 'united states of america')->where('destination', 'fixed')->get();
        foreach ($usa_rates as $rate) {
            $data = (array) $rate;
            unset($data['id']);
            $data['destination'] = 'mobile';
            \DB::connection('pbx')->table('p_rates_summary')->insert($data);
        }

        $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->get();
        foreach ($ratesheets as $ratesheet) {
            \DB::connection('pbx')->table('p_rates_partner_items')->where('ratesheet_id', $ratesheet->id)->update(['currency' => $ratesheet->currency]);
        }
        $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->pluck('gateway_uuid')->toArray();
        \DB::connection('pbx')->table('p_rates_complete')->whereNotIn('gateway_uuid', $gateway_uuids)->delete();
        $local_gateways = \DB::connection('pbx')->table('v_gateways')->where('use_rate', 1)->pluck('gateway_uuid')->toArray();

        \DB::connection('pbx')->table('p_rates_summary')->where('country', 'south africa')->whereNotIn('gateway_uuid', $local_gateways)->update(['status' => 'RATES_OFF']);
        \DB::connection('pbx')->table('p_rates_summary')->where('country', 'south africa')->whereIn('gateway_uuid', $local_gateways)->update(['status' => 'Enabled']);
        $international_gateways = \DB::connection('pbx')->table('v_gateways')->where('use_rate_international', 1)->pluck('gateway_uuid')->toArray();
        \DB::connection('pbx')->table('p_rates_summary')->where('country', '!=', 'south africa')->whereNotIn('gateway_uuid', $international_gateways)->update(['status' => 'RATES_OFF']);
        \DB::connection('pbx')->table('p_rates_summary')->where('country', '!=', 'south africa')->whereIn('gateway_uuid', $international_gateways)->update(['status' => 'Enabled']);

        $disabled_gateways = \DB::connection('pbx')->table('v_gateways')->where('enabled', 'false')->pluck('gateway_uuid')->toArray();

        \DB::connection('pbx')->table('p_rates_summary')->whereIn('gateway_uuid', $disabled_gateways)->update(['status' => 'GATEWAY_DISABLED']);
        \DB::connection('pbx')->table('p_rates_summary')->whereNotIn('gateway_uuid', $disabled_gateways)
            ->where('status', 'GATEWAY_DISABLED')
            ->update(['status' => 'Enabled']);

        \DB::connection('pbx')->table('p_rates_summary')->update(['lowest_rate' => 0]);
        \DB::connection('pbx')->statement('UPDATE p_rates_summary
JOIN (
    SELECT country, destination, MIN(cost_zar) AS mincost
    FROM p_rates_summary
    WHERE status="Enabled"
    GROUP BY country, destination
) AS p_rates_summary_min ON p_rates_summary.country = p_rates_summary_min.country AND p_rates_summary.destination = p_rates_summary_min.destination AND p_rates_summary.cost_zar = p_rates_summary_min.mincost
SET p_rates_summary.lowest_rate = 1;
');

        $rates = \DB::connection('pbx')->table('p_rates_summary')
            ->get()
            ->unique(function ($item) {
                return $item->country.$item->destination;
            });

        // process unblocked destinations
        $blocked_summary = \DB::connection('pbx')->table('p_rates_summary')->where('status', 'DESTINATION_BLOCKED')->get();
        foreach ($blocked_summary as $b) {
            $c = \DB::connection('pbx')->table('p_blocked_destinations')
                ->where('gateway_uuid', $b->gateway_uuid)
                ->where('destination', $b->destination)
                ->where('country', $b->country)
                ->count();
            if (! $c) {
                \DB::connection('pbx')->table('p_rates_summary')->where('id', $b->id)->update(['status' => 'Enabled']);
            }
        }

        $blocked_destinations = \DB::connection('pbx')->table('p_blocked_destinations')->get();
        foreach ($blocked_destinations as $b) {
            \DB::connection('pbx')->table('p_rates_summary')
                ->where('gateway_uuid', $b->gateway_uuid)
                ->where('destination', $b->destination)
                ->where('country', $b->country)
                ->update(['status' => 'DESTINATION_BLOCKED']);
        }

        foreach ($rates as $rate) {

            \DB::connection('pbx')->table('p_rates_summary')
                ->where('lowest_rate', 1)
                ->where('country', $rate->country)
                ->where('destination', $rate->destination)
                ->whereNotIn('status', ['GATEWAY_DISABLED', 'DESTINATION_BLOCKED', 'RATES_OFF'])
                ->update(['status' => 'Enabled']);

            $num_enabled = \DB::connection('pbx')->table('p_rates_summary')->where('status', 'Enabled')->where('country', $rate->country)->where('destination', $rate->destination)->count();

            if (! $num_enabled) {
                $lowest_rate_id = \DB::connection('pbx')->table('p_rates_summary')
                    ->where('lowest_rate', 1)
                    ->where('country', $rate->country)
                    ->where('destination', $rate->destination)
                    ->whereNotIn('status', ['GATEWAY_DISABLED', 'DESTINATION_BLOCKED', 'RATES_OFF'])
                    ->orderBy('lowest_rate', 'desc')
                    ->orderBy('cost_zar', 'asc')
                    ->pluck('id')->first();
                \DB::connection('pbx')->table('p_rates_summary')->where('id', $lowest_rate_id)->update(['status' => 'Enabled']);
            }

            $num_enabled = \DB::connection('pbx')->table('p_rates_summary')->where('status', 'Enabled')->where('country', $rate->country)->where('destination', $rate->destination)->count();

            if (! $num_enabled) {
                $lowest_rate_id = \DB::connection('pbx')->table('p_rates_summary')
                    ->where('lowest_rate', 0)
                    ->where('country', $rate->country)
                    ->where('destination', $rate->destination)
                    ->whereNotIn('status', ['GATEWAY_DISABLED', 'DESTINATION_BLOCKED', 'RATES_OFF'])
                    ->orderBy('lowest_rate', 'desc')
                    ->orderBy('cost_zar', 'asc')
                    ->pluck('id')->first();
                \DB::connection('pbx')->table('p_rates_summary')->where('id', $lowest_rate_id)->update(['status' => 'Enabled']);
            }

            $num_enabled = \DB::connection('pbx')->table('p_rates_summary')->where('status', 'Enabled')->where('country', $rate->country)->where('destination', $rate->destination)->count();

            if ($num_enabled > 1) {
                $lowest_rate_id = \DB::connection('pbx')->table('p_rates_summary')
                    ->where('country', $rate->country)
                    ->where('destination', $rate->destination)
                    ->where('status', 'Enabled')
                    ->orderBy('lowest_rate', 'desc')
                    ->orderBy('cost_zar', 'asc')
                    ->pluck('id')->first();
                \DB::connection('pbx')->table('p_rates_summary')
                    ->where('id', '!=', $lowest_rate_id)
                    ->where('country', $rate->country)
                    ->where('destination', $rate->destination)
                    ->whereNotIn('status', ['GATEWAY_DISABLED', 'DESTINATION_BLOCKED', 'RATES_OFF'])
                    ->update(['status' => 'Disabled']);
            }

        }

        $currency_rate = get_exchange_rate(null, 'USD', 'ZAR');
        \DB::connection('pbx')->table('p_rates_summary')
            ->where('cost_zar', 0)
            ->update([
                'cost_usd' => 0,
            ]);
        \DB::connection('pbx')->table('p_rates_summary')
            ->where('cost_zar', '>', 0)
            ->where('cost_usd', 0)
            ->update([
                'cost_usd' => \DB::raw('cost_zar/'.$currency_rate),
            ]);

        update_rates_selling_prices();

    } catch (\Throwable $ex) {
        admin_email('Rates summary set lowest rate failed', $ex->getMessage());
    }

    $sql = 'UPDATE p_rates_summary 
    JOIN v_gateways ON v_gateways.gateway_uuid=p_rates_summary.gateway_uuid
    SET p_rates_summary.gateway_name = v_gateways.gateway';
    \DB::connection('pbx')->statement($sql);
}

function button_rates_summary_enable($request)
{
    $rate = \DB::connection('pbx')->table('p_rates_summary')->where('id', $request->id)->get()->first();
    \DB::connection('pbx')->table('p_blocked_destinations')
        ->where('country', $rate->country)
        ->where('destination', $rate->destination)
        ->where('gateway_uuid', $rate->gateway_uuid)->delete();
    admin_rates_summary_set_lowest_active();
}
function button_rates_summary_disable($request)
{
    $rate = \DB::connection('pbx')->table('p_rates_summary')->where('id', $request->id)->get()->first();
    $data = [
        'country' => $rate->country,
        'destination' => $rate->destination,
        'gateway_uuid' => $rate->gateway_uuid,
    ];
    \DB::connection('pbx')->table('p_blocked_destinations')->updateOrInsert($data, $data);
    admin_rates_summary_set_lowest_active();

    return json_alert('Done');
}

//// BUTTONS

function button_rates_summary_import_from_rates_complete()
{
    import_rates_summary_from_rates_complete();

    return json_alert('Done');
}

function button_rates_summary_set_lowest_rate($request)
{
    admin_rates_summary_set_lowest_active();

    return json_alert('Done');
}

//// AJAX
function ajax_rates_set_pricing($request)
{
    if (! empty($request->id)) {
        $response = [];
        //COST PRICE
        if (empty(session('voice_rates_ajax'))) {
            $item = \DB::connection('pbx')->table('p_rates_summary')->where('id', $request->id)->get()->first();
            session(['voice_rates_ajax' => (array) $item]);
        } else {
            $item = (object) session('voice_rates_ajax');
        }
        foreach ($request->all() as $k => $v) {
            if ($k != 'changed_field' && $k != $request->changed_field) {
                $request->{$k} = $item->{$k};
            }
        }

        /// WHOLESALE START
        $old_admin_rate = currency($item->cost_zar);
        $admin_rate = $request->input('cost_zar');

        $old_wholesale_rate = currency($item->wholesale_rate_zar);
        $wholesale_rate = currency($request->input('wholesale_rate_zar'));

        $old_wholesale_markup = intval($item->wholesale_markup);
        $wholesale_markup = intval($request->input('wholesale_markup'));

        //MARKUP
        if ($wholesale_markup != $old_wholesale_markup) {
            if ($admin_rate == 0 or $wholesale_markup < 0) {
                $wholesale_markup = 0;
            }
            $wholesale_markup_amount = ($admin_rate / 100) * $wholesale_markup;
            $wholesale_rate = $admin_rate + $wholesale_markup_amount;
        }

        //SELLING PRICE
        if ($wholesale_rate != $old_wholesale_rate) {
            if ($wholesale_rate < $admin_rate) {
                $wholesale_rate = $admin_rate;
            }

            $wholesale_markup = intval(($admin_rate > 0) ? ($wholesale_rate - $admin_rate) * 100 / $admin_rate : 0);
        }

        $wholesale_markup = intval(($admin_rate > 0) ? ($wholesale_rate - $admin_rate) * 100 / $admin_rate : 0);

        $response['wholesale_markup'] = intval($wholesale_markup);
        $response['wholesale_rate_zar'] = currency($wholesale_rate);

        /// WHOLESALE END

        /// RETAIL START

        $old_retail_rate = currency($item->retail_rate_zar);
        $retail_rate = currency($request->input('retail_rate_zar'));

        $old_retail_markup = intval($item->retail_markup);
        $retail_markup = intval($request->input('retail_markup'));

        //MARKUP
        if ($retail_markup != $old_retail_markup) {
            if ($admin_rate == 0 or $retail_markup < 0) {
                $retail_markup = 0;
            }
            $retail_markup_amount = ($admin_rate / 100) * $retail_markup;
            $retail_rate = $admin_rate + $retail_markup_amount;
        }

        //SELLING PRICE
        if ($retail_rate != $old_retail_rate) {
            if ($retail_rate < $admin_rate) {
                $retail_rate = $admin_rate;
            }
            $retail_markup = intval(($admin_rate > 0) ? ($retail_rate - $admin_rate) * 100 / $admin_rate : 0);
        }

        $retail_markup = intval(($admin_rate > 0) ? ($retail_rate - $admin_rate) * 100 / $admin_rate : 0);

        $response['retail_markup'] = intval($retail_markup);
        $response['retail_rate_zar'] = currency($retail_rate);
        $item = session('voice_rates_ajax');
        $item = (array) $item;
        foreach ($response as $k => $v) {
            $item[$k] = $v;
        }

        session(['voice_rates_ajax' => $item]);

        /// RETAIL END
        return $response;
    }
}

//// EVENTS
function beforesave_rates_summary_check_markup($request) {}

function aftersave_rates_summary_active_unique($request)
{
    admin_rates_summary_set_lowest_active();
}

function aftersave_rates_summary_set_exchange_value($request)
{

    $currency_rate = get_exchange_rate();
    \DB::connection('pbx')->table('p_rates_summary')
        ->where('cost_zar', '>', 0)
        ->update([
            'cost_usd' => \DB::raw('cost_zar*'.$currency_rate),
        ]);

}

function select_options_rates_summary_destinations()
{
    $opts = \DB::connection('pbx')->table('p_rates_summary')->select(\DB::raw('CONCAT(country,"-",destination) as dest'))->groupBy('country')->groupBy('destination')->pluck('dest')->unique()->toArray();

    $result = array_combine($opts, $opts);

    return $result;
}

function update_rates_selling_prices()
{

    $summaries = \DB::connection('pbx')->table('p_rates_summary')->where('status', 'Enabled')->get();
    $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', 1)->get();

    //\DB::connection('pbx')->table('p_rates_partner_items')->update(['markup' => \DB::raw('(rate - cost_price) * 100 / cost_price')]);

    /*
    foreach ($ratesheets as $ratesheet) {

        $ratesheet_items = \DB::connection('pbx')->table('p_rates_partner_items')
        ->where('ratesheet_id', $ratesheet->id)
        ->get();
        foreach($ratesheet_items as $item){
            $summary_exists = \DB::connection('pbx')->table('p_rates_summary')
                ->where('country', $item->country)
                ->where('destination', $item->destination)
                ->where('status', 'Enabled')
                ->count();
            if(!$summary_exists && $item->destination!='onnet'){
                \DB::connection('pbx')->table('p_rates_partner_items')->where('id',$item->id)->delete();
            }
        }
    }
    */

    foreach ($summaries as $summary) {
        foreach ($ratesheets as $ratesheet) {

            $exists = \DB::connection('pbx')->table('p_rates_partner_items')
                ->where('country', $summary->country)
                ->where('destination', $summary->destination)
                ->where('ratesheet_id', $ratesheet->id)
                ->count();

            $destination_type = 'fixed';

            $default_markup = 40;
            if (str_contains($summary->destination, 'mobile')) {
                $destination_type = 'mobile';
            }
            if ($ratesheet->currency == 'USD') {
                $cost_price = $summary->cost_usd;
            }

            if ($ratesheet->currency == 'ZAR') {
                $cost_price = $summary->cost_zar;
            }

            // update cost
            \DB::connection('pbx')->table('p_rates_partner_items')
                ->where('country', $summary->country)
                ->where('destination', $summary->destination)
                ->where('ratesheet_id', $ratesheet->id)
                ->update(['cost_price' => $cost_price]);

            if ($summary->country != 'south africa') {
                $default_markup = $ratesheet->international_markup;
                /*
                $current_markup = \DB::connection('pbx')->table('p_rates_partner_items')
                ->where('country',$summary->country)
                ->where('ratesheet_id',$ratesheet->id)
                ->where('destination',$summary->destination)
                ->where('destination_type',$destination_type)
                ->pluck('markup')->first();
                if($current_markup){
                     if($current_markup > 100 || $current_markup < 40){

                     }else{
                    $default_markup = $current_markup;
                     }
                }
                */
            }

            if ($summary->country == 'south africa') {
                $default_markup = $ratesheet->international_markup;

                $markup_amount = ($cost_price / 100) * $default_markup;
                $rate = $cost_price + $markup_amount;

            } else {
                $markup_amount = ($cost_price / 100) * $default_markup;
                $rate = $cost_price + $markup_amount;
            }
            $data = [
                'partner_id' => $ratesheet->partner_id,
                'ratesheet_id' => $ratesheet->id,
                'country' => $summary->country,
                'destination_type' => $destination_type,
                'destination' => $summary->destination,
                'rate' => $rate,
                'cost_price' => $cost_price,
            ];

            if (! $exists) {
                \DB::connection('pbx')->table('p_rates_partner_items')->insert($data);
            } else {
                if ($summary->country != 'south africa') {
                    $current_rate = \DB::connection('pbx')->table('p_rates_partner_items')
                        ->where('country', $summary->country)
                        ->where('ratesheet_id', $ratesheet->id)
                        ->where('destination', $summary->destination)
                        ->where('destination_type', $destination_type)
                        ->pluck('rate')->first();
                    $current_markup = \DB::connection('pbx')->table('p_rates_partner_items')
                        ->where('country', $summary->country)
                        ->where('ratesheet_id', $ratesheet->id)
                        ->where('destination', $summary->destination)
                        ->where('destination_type', $destination_type)
                        ->pluck('markup')->first();
                    $update_data = [
                        'rate' => $rate,
                        'cost_price' => $cost_price,
                    ];

                    \DB::connection('pbx')->table('p_rates_partner_items')
                        ->where('country', $summary->country)
                        ->where('ratesheet_id', $ratesheet->id)
                        ->where('destination', $summary->destination)
                        ->where('destination_type', $destination_type)
                        ->update($update_data);
                }
            }
        }
    }

    \DB::connection('pbx')->table('p_rates_partner_items')->where('cost_price', '>', 0)->update(['markup' => \DB::raw('(rate - cost_price) * 100 / cost_price')]);
    ratesheets_set_volume_pricing();
}
