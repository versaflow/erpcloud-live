<?php

// PRICING STRATEGIES = Wholesale + Retail
// Sourced Services = 25% + 15%
// Internal Services = 60% + 40%
// Sourced Products = 25% + 15%
// Imported Products = 40% + 25%
// Call Rates = Manual

/// SQL



/// AJAX
function ajax_pricelist_items_set_pricing($request)
{
 //  aa($request->all());
    $admin_pricelists = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();
    if (!empty($request->id)) {
        $response = [];
        //COST PRICE
        if (empty(session('item_ajax'))) {
            $item = \DB::table('crm_pricelist_items')->where('id', $request->id)->get()->first();
            session(['item_ajax' => (array) $item]);
        } else {
            $item = (object) session('item_ajax');
        }
        foreach ($request->all() as $k => $v) {
            if ($k != 'changed_field' && $k != $request->changed_field) {
                $request->{$k} = $item->{$k};
            }
        }


        $old_cost_price = currency($item->cost_price);
        $cost_price = $request->input('cost_price');
        /// RETAIL START

        $old_price = currency($item->price);
        $price = currency($request->input('price'));

        $old_price_tax = currency($item->price_tax);
        $price_tax = currency($request->input('price_tax'));

        $old_markup = intval($item->markup);
        $markup = intval($request->input('markup'));

        //MARKUP
        if ($markup != $old_markup) {
            if (0 == $cost_price or $markup < 0) {
                $markup = 0;
            }
            $markup_amount = ($cost_price / 100) * $markup;
            $price = $cost_price + $markup_amount;
            $price_tax = $price * 1.15;
        } elseif ($price != $old_price) {
            if ($price < $cost_price) {
                $price = $cost_price;
            }
            $price_tax = $price * 1.15;
            $markup = intval(($cost_price > 0) ? ($price - $cost_price) * 100 / $cost_price : 0);
        } elseif ($price_tax != $old_price_tax) {
            if ($price_tax < $cost_price) {
                $price_tax = $cost_price;
            }
            $price = $price_tax / 1.15;
            $markup = intval(($cost_price > 0) ? ($price - $cost_price) * 100 / $cost_price : 0);
        }

        //ROUND PRICES AND MARKUP
        //$price_tax = round_price($price_tax);

        $remove_tax_fields = get_admin_setting('remove_tax_fields');
        $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', session('account_id'))->pluck('vat_enabled')->first();

        $response['markup'] = intval($markup);
        $response['price'] = currency($price);
        $response['price_tax'] = currency($price_tax);
        
        if($remove_tax_fields || !$vat_enabled){
            $response['price_tax'] = $response['price'];  
        }
      
        $item = session('item_ajax');
        $item = (array) $item;

        
       
        $round_price = get_admin_setting('round_prices');

        if ($round_price) {
          
          if($remove_tax_fields || !$vat_enabled){
                $response['price'] = round_price($response['price']);
                $response['price_tax'] = $response['price']; 
            }else{ 
                $response['price_tax'] = round_price($response['price_tax']);
                $response['price'] = $response['price_tax'] / 1.15;
              
            }
            

            $response['markup'] = intval(($cost_price > 0) ? ($response['price'] - $cost_price) * 100 / $cost_price : 0);
           
        }


        if (str_contains($request->changed_field, 'wholesale')) {
            foreach ($response as $k => $v) {
                if (!str_contains($k, 'wholesale')) {
                    unset($response[$k]);
                }
            }
        } else {
            foreach ($response as $k => $v) {
                if (str_contains($k, 'wholesale')) {
                    unset($response[$k]);
                }
            }
        }

        foreach ($response as $k => $v) {
            $item[$k] = $v;
        }

        if (!in_array($item['pricelist_id'], $admin_pricelists)) {
            foreach ($response as $k => $v) {
                if (str_contains($k, 'wholesale')) {
                    unset($response[$k]);
                }
            }
        }
        
        session(['item_ajax' => $item]);
       // aa($response);
        /// RETAIL END
        return $response;
    }
}



/// BEFORESAVE
function beforesave_check_markup($request)
{
   // aa($request->all());
    if (currency($request->price_tax) < currency($request->cost_price)) {
       
        return 'Selling price needs to be more than cost price';
    }

    $beforesave_row = session('event_db_record');
    $admin_pricelists = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();
    $minimum_markup = get_admin_setting('minimum_markup');
    if(in_array($request->pricelist_id,$admin_pricelists) && $request->cost_price > 0 && $request->markup < $minimum_markup){
        return  'Markup cannot be set to less than '.$minimum_markup.'.';
    }
    
    if (!empty($request->id)) {
        $response = [];
        
        $item = session('event_db_record');
        //COST PRICE
        $old_cost_price = currency($item->cost_price);
        $cost_price = $request->input('cost_price');
        /// RETAIL START

        $old_price = currency($item->price);
        $price = currency($request->input('price'));

        $old_price_tax = currency($item->price_tax);
        $price_tax = currency($request->input('price_tax'));

        $old_markup = intval($item->markup);
        $markup = intval($request->input('markup'));


        //MARKUP
        if ($markup != $old_markup) {
            
            if (0 == $cost_price or $markup < 0) {
                $markup = 0;
            }
            $markup_amount = ($cost_price / 100) * $markup;
            $price = $cost_price + $markup_amount;
            $price_tax = $price * 1.15;
        } elseif ($price != $old_price) {
            if ($price < $cost_price) {
                $price = $cost_price;
            }
            $price_tax = $price * 1.15;
            $markup = intval(($cost_price > 0) ? ($price - $cost_price) * 100 / $cost_price : 0);
        } elseif ($price_tax != $old_price_tax) {
            if ($price_tax < $cost_price) {
                $price_tax = $cost_price;
            }
            $price = $price_tax / 1.15;
            $markup = intval(($cost_price > 0) ? ($price - $cost_price) * 100 / $cost_price : 0);
        }

        //ROUND PRICES AND MARKUP
        //$price_tax = round_price($price_tax);



        $remove_tax_fields = get_admin_setting('remove_tax_fields');
        $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', session('account_id'))->pluck('vat_enabled')->first();
        $response['markup'] = intval($markup);
        $response['price'] = currency($price);
        $response['price_tax'] = currency($price_tax);
       
        $item = (array) $item;
        
        
        if($remove_tax_fields || !$vat_enabled){
            $response['price_tax'] = $response['price'];  
        }


        $round_price = get_admin_setting('round_prices');

        if ($round_price) {
            if($remove_tax_fields || !$vat_enabled){
                $response['price'] = round_price($response['price']);
                $response['price_tax'] = $response['price']; 
            }else{ 
                $response['price_tax'] = round_price($response['price_tax']);
                $response['price'] = $response['price_tax'] / 1.15;
            }

            $response['markup'] = intval(($cost_price > 0) ? ($response['price'] - $cost_price) * 100 / $cost_price : 0);
        }

        // aa($response);

        foreach ($response as $k => $v) {
            $request->request->add([$k => $v]);
        }
        
    }
}

/// AFTERSAVE

/// COMMIT
function aftercommit_pricelist_items_save_to_history($request)
{
    $user_id = get_user_id_default();
    $admin_pricelists = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();
    if (in_array($request->pricelist_id, $admin_pricelists)) {
        $beforesave_row = session('event_db_record');
        $row = \DB::table('crm_pricelist_items')->where('id', $request->id)->get()->first();

        if ($beforesave_row->price_tax != $row->price_tax) {
            $data = [
                'created_at' => date('Y-m-d H:i:s'),
                'pricelist_id' => $row->pricelist_id,
                'product_id' => $row->product_id,
                'old_price' => $beforesave_row->price_tax,
                'new_price' => $row->price_tax,
                'user_id' => $user_id,
                'type' => 'retail',
            ];

            \DB::table('crm_price_history')->insert($data);
        }
        
    }
}


/// SCHEDULE

/// HELPERS

function round_price($price)
{
    
 
   
    $round_price = get_admin_setting('round_prices');
    if ($round_price) {
     
        $policies = \DB::connection('default')->table('acc_pricing_policy')->orderBy('price_start')->get();
        foreach ($policies as $policy) {
            if ($price > $policy->price_start && $price < $policy->price_end) {
                if ($policy->rounding_type == 'round') {
                    if($policy->rounding == 9){
                        $price = ceil($price / 10) * 10;
                        $price = $price -1;
                        return currency($price);
                    }
                    $price = round(round($price / $policy->rounding) * $policy->rounding, 2);
                }
                if ($policy->rounding_type == 'ceil') {
                    $price = round(ceil($price / $policy->rounding) * $policy->rounding, 2);
                }
                if ($policy->rounding_type == 'floor') {
                    $price = round(floor($price / $policy->rounding) * $policy->rounding, 2);
                }
            } elseif ($price > $policy->price_start && empty($policy->price_end)) {
                if ($policy->rounding_type == 'round') {
                    if($policy->rounding == 9){
                        $price = ceil($price / 10) * 10;
                        $price = $price -1;
                        return currency($price);
                    }
                    $price = round(round($price / $policy->rounding) * $policy->rounding, 2);
                }
                if ($policy->rounding_type == 'ceil') {
                    $price = round(ceil($price / $policy->rounding) * $policy->rounding, 2);
                }
                if ($policy->rounding_type == 'floor') {
                    $price = round(floor($price / $policy->rounding) * $policy->rounding, 2);
                }
            }
        }
    }
    
    return $price;
}
