<?php

function beforesave_rates_check_markup($request)
{
    try {
        if (!empty($request->id)) {
            $row = \DB::connection('pbx')->table('p_rates_partner_items')->where('id', $request->id)->get()->first();
            $currency = \DB::connection('pbx')->table('p_rates_partner')->where('id', $request->ratesheet_id)->pluck('currency')->first();
            if ($row->destination == 'onpbx') {
                return 'Markups cannot be set on this destination.';
            }

            $cost_price = $row->cost_price;

         
         
            if (empty($cost_price)) {
                $cost_price = 0;
            }

            if ($cost_price > 0) {
                $markup =  intval((($request->rate - $cost_price) * 100) / $cost_price);

                if ($cost_price > 0) {
                    if ($markup < 1) {
                        return 'Markup cannot be 0%.';
                    }
                    if ($markup > 600) {
                        return 'Markup cannot be more than 600%.';
                    }
                }
            }
        }
    } catch (\Throwable $ex) {  exception_log($ex);
        aa($ex->getMessage());
    }
}




function get_ratesheet_item_markup($row)
{
    $row = (object) $row;
    $pricelist_item_id = $row->id;
    $sql = \DB::connection('default')->table('erp_cruds')->where('id', 588)->pluck('db_sql')->first();
    $sql .= ' where p_rates_partner_items.id ="'.$pricelist_item_id.'"';
    $result = \DB::connection('pbx')->select($sql);
    return collect($result)->pluck('markup')->first();
}

function get_ratesheet_item_cost_price($row)
{
    $row = (object) $row;
    $pricelist_item_id = $row->id;

    $sql = \DB::connection('default')->table('erp_cruds')->where('id', 588)->pluck('db_sql')->first();
    $sql .= ' where p_rates_partner_items.id ="'.$pricelist_item_id.'"';

    $result = \DB::connection('pbx')->select($sql);
    return collect($result)->pluck('cost_price')->first();
}

function ajax_partner_rates_set_pricing($request)
{
    if (!empty($request->id)) {
        $response = [];
        //COST PRICE
        $partner_id = \DB::connection('pbx')->table('p_rates_partner_items')->where('id', $request->id)->pluck('partner_id')->first();
        if (empty(session('voice_rates_ajax'))) {
            $item = \DB::connection('pbx')->table('p_rates_partner_items')->where('id', $request->id)->get()->first();
   
            session(['voice_rates_ajax' => (array) $item]);
        } else {
            $item = (object) session('voice_rates_ajax');
        }
        foreach ($request->all() as $k => $v) {
            if ($k != 'changed_field' && $k != $request->changed_field) {
                $request->{$k} = $item->{$k};
            }
        }

        $admin_rate = $item->cost_price;


        /// RETAIL START

        $old_retail_rate = currency($item->rate, 3);
        $retail_rate = currency($request->input('rate'), 3);

        $old_retail_markup = intval($item->markup);
        $retail_markup = intval($request->input('markup'));

        //MARKUP

        if ($retail_markup != $old_retail_markup) {
            if (0 == $admin_rate or $retail_markup < 0) {
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

        $response['markup'] = intval($retail_markup);
        $response['rate'] = currency($retail_rate, 3);
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

function beforesave_competitor_rates_set_tax($request)
{
    $conn = session('module_connection');
    if (empty($conn)) {
        $conn = 'pbx';
    }
    if (empty($request->id)) {
        if (empty($request->rate) && !empty($request->rate_inc)) {
            $rate = $request->rate_inc / 1.15;
            $request->request->add(['rate' => $rate]);
        } elseif (!empty($request->rate) && empty($request->rate_inc)) {
            $rate_inc = $request->rate * 1.15;
            $request->request->add(['rate_inc' => $rate_inc]);
        }
    } else {
        $item =  \DB::connection($conn)->table('p_competitor_rates')->where('id', $request->id)->get()->first();

        if (empty($request->rate) || (!empty($request->rate_inc) && currency($item->rate_inc) != currency($request->rate_inc))) {
            $rate = $request->rate_inc / 1.15;

            $request->request->add(['rate' => $rate]);
        } elseif (empty($request->rate_inc) || (!empty($request->rate) && currency($request->rate) != currency($request->rate))) {
            $rate_inc = $request->rate * 1.15;
            $request->request->add(['rate_inc' => $rate_inc]);
        }
    }
}





function button_itr_fix_block_list_destinations($request)
{
    $routes = \DB::connection('pbx')->table('c_blocked_ani_itr')->get();
    foreach ($routes as $route) {
        $route->destination = strtolower($route->destination);


        if (!str_contains($route->destination, 'mobile')) {
            if (str_contains($route->destination, 'telkom')) {
                \DB::connection('pbx')->table('c_blocked_ani_itr')->where('id', $route->id)->update(['destination' => 'fixed telkom']);
            } elseif (str_contains($route->destination, 'liquid') || str_contains($route->destination, 'neotel')) {
                \DB::connection('pbx')->table('c_blocked_ani_itr')->where('id', $route->id)->update(['destination' => 'fixed liquid']);
            } else {
                \DB::connection('pbx')->table('c_blocked_ani_itr')->where('id', $route->id)->update(['destination' => 'fixed other']);
            }
        } else {
            if (str_contains($route->destination, 'mobile') && str_contains($route->destination, 'mtn')) {
                \DB::connection('pbx')->table('c_blocked_ani_itr')->where('id', $route->id)->update(['destination' => 'mobile mtn']);
            }
            if (str_contains($route->destination, 'mobile') && str_contains($route->destination, 'telkom')) {
                \DB::connection('pbx')->table('c_blocked_ani_itr')->where('id', $route->id)->update(['destination' => 'mobile telkom']);
            }
            if (str_contains($route->destination, 'mobile') && str_contains($route->destination, 'vodacom')) {
                \DB::connection('pbx')->table('c_blocked_ani_itr')->where('id', $route->id)->update(['destination' => 'mobile vodacom']);
            }
            if (str_contains($route->destination, 'mobile') && (str_contains($route->destination, 'cellc') || str_contains($route->destination, 'cell c'))) {
                \DB::connection('pbx')->table('c_blocked_ani_itr')->where('id', $route->id)->update(['destination' => 'mobile cellc']);
            }
        }
    }
    return json_alert('Updated');
}


function set_ported_numbers_destinations()
{
    $routing_labels = \DB::connection('pbx_cdr')->table('p_routing_labels')->get();
    foreach ($routing_labels as $routing) {
        $destination = '';
        if ($routing->routing_label == 'D007') {
            $destination = 'fixed liquid';
        }
        if ($routing->routing_label == 'D000') {
            $destination = 'fixed telkom';
        }
        if ($routing->routing_label == 'D082') {
            $destination = 'mobile vodacom';
        }
        if ($routing->routing_label == 'D083') {
            $destination = 'mobile mtn';
        }
        if ($routing->routing_label == 'D084') {
            $destination = 'mobile cellc';
        }
        if ($routing->routing_label == 'D004') {
            $destination = 'mobile telkom';
        }

        if (empty($destination)) {
            $destination_lookup = \DB::connection('pbx')->table('p_rates_destinations')->where('country', 'south africa')->where('destination', 'LIKE', '%'.strtolower($routing->gnp_no).'%')->pluck('destination')->first();
            if (!empty($destination_lookup)) {
                $destination = $destination_lookup;
            }
        }
        \DB::connection('pbx_porting')->table('p_ported_numbers')->where('rnoroute', $routing->routing_label)->update(['destination' => $destination]);
    }
}

function select_domains_ratesheet_list($row)
{
    $row = (object) $row;
    if (!empty($row) && !empty($row->partner_id)) {
        $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id', $row->partner_id)->get();
    }

    if (!empty($ratesheets) && count($ratesheets) > 0) {
        foreach ($ratesheets as $ratesheet) {
            $options[$ratesheet->id] = $ratesheet->name;
        }
    }

    return $options;
}
