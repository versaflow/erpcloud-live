<?php
// deleted module
/*
function button_rates_summary_competitor_rates($request)
{
    $rate = \DB::connection('pbx')->table('p_rates_summary')->where('id', $request->id)->get()->first();

    $menu_name = get_menu_url_from_table('p_competitor_rates');
    return Redirect::to($menu_name.'?destination='.$rate->destination.'&country='.$rate->country);
}



function button_ratesheets_competitor_rates($request)
{
    $rate = \DB::connection('pbx')->table('p_rates_partner_items')->where('id', $request->id)->get()->first();

    $menu_name = get_menu_url_from_table('p_competitor_rates');
    return Redirect::to($menu_name.'?destination='.$rate->destination.'&country='.$rate->country);
}
function aftersave_set_rates_competitor_avg($request)
{
    
 $rates = \DB::connection('pbx')->table('p_rates_partner_items')->select('country', 'destination')->groupBy('country', 'destination')->get();
    foreach ($rates as $rate) {
        $competitor_retail_avg = \DB::connection('pbx')->table('p_competitor_rates')->where('destination', $rate->destination)->where('country', $rate->country)->where('type', 'retail')->where('rate', '>', 0)->avg('rate');

        if (!$competitor_retail_avg) {
            $competitor_retail_avg = 0.00;
        }
        $competitor_wholesale_avg = \DB::connection('pbx')->table('p_competitor_rates')->where('destination', $rate->destination)->where('country', $rate->country)->where('type', 'wholesale')->where('rate', '>', 0)->avg('rate');

        if (!$competitor_wholesale_avg) {
            $competitor_wholesale_avg = 0.00;
        }

        $usd_competitor_retail_avg = convert_currency_zar_to_usd($competitor_retail_avg);
        $usd_competitor_wholesale_avg = convert_currency_zar_to_usd($competitor_wholesale_avg);
      
        \DB::connection('pbx')->table('p_rates_partner_items')->where('currency','ZAR')->where('destination', $rate->destination)->where('country', $rate->country)
        ->update(['competitor_retail_avg' => $competitor_retail_avg,'competitor_wholesale_avg'=> $competitor_wholesale_avg]);
        \DB::connection('pbx')->table('p_rates_partner_items')->where('currency','USD')->where('destination', $rate->destination)->where('country', $rate->country)
        ->update(['competitor_retail_avg' => $usd_competitor_retail_avg,'competitor_wholesale_avg'=> $usd_competitor_wholesale_avg]);
    }
}


function afterdelete_set_rates_competitor_avg($request)
{
    $rates = \DB::connection('pbx')->table('p_rates_partner_items')->select('country', 'destination')->groupBy('country', 'destination')->get();
    foreach ($rates as $rate) {
        $competitor_retail_avg = \DB::connection('pbx')->table('p_competitor_rates')->where('destination', $rate->destination)->where('country', $rate->country)->where('type', 'retail')->where('rate', '>', 0)->avg('rate');

        if (!$competitor_retail_avg) {
            $competitor_retail_avg = 0.00;
        }
        $competitor_wholesale_avg = \DB::connection('pbx')->table('p_competitor_rates')->where('destination', $rate->destination)->where('country', $rate->country)->where('type', 'wholesale')->where('rate', '>', 0)->avg('rate');

        if (!$competitor_wholesale_avg) {
            $competitor_wholesale_avg = 0.00;
        }
        $usd_competitor_retail_avg = convert_currency_zar_to_usd($competitor_retail_avg);
        $usd_competitor_wholesale_avg = convert_currency_zar_to_usd($competitor_wholesale_avg);
      
        \DB::connection('pbx')->table('p_rates_partner_items')->where('currency','ZAR')->where('destination', $rate->destination)->where('country', $rate->country)
        ->update(['competitor_retail_avg' => $competitor_retail_avg,'competitor_wholesale_avg'=> $competitor_wholesale_avg]);
        \DB::connection('pbx')->table('p_rates_partner_items')->where('currency','USD')->where('destination', $rate->destination)->where('country', $rate->country)
        ->update(['competitor_retail_avg' => $usd_competitor_retail_avg,'competitor_wholesale_avg'=> $usd_competitor_wholesale_avg]);
    }
}

*/