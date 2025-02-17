<?php

// RUN ON PRODUCT AND ADMIN PRICELIST AFTERSAVE

function onload_set_pricelist_items_category(){
    $categories = \DB::table('crm_product_categories')->pluck('id')->toArray();
    foreach($categories as $category_id){
        $product_ids = \DB::table('crm_products')->where('product_category_id',$category_id)->pluck('id')->toArray();
        \DB::table('crm_pricelist_items')->whereIn('product_id',$product_ids)->update(['product_category_id'=>$category_id]);
    }
}

function onload_pricelist_items_set_currency(){
    $pricelists = \DB::table('crm_pricelists')->get();
    foreach($pricelists as $pricelist){
        \DB::table('crm_pricelist_items')->where('pricelist_id',$pricelist->id)->update(['currency'=>$pricelist->currency]);    
    }
}

function aftercommit_products_update_cost_prices($request)
{
    validate_pricelists_cost_price($request->id);
}

function aftersave_pricelist_items_update_cost_prices($request)
{
   
    $pricelist = \DB::table('crm_pricelists')->where('id', $request->pricelist_id)->get()->first();
    if ($pricelist->partner_id == 1) {
        validate_pricelists_cost_price($request->product_id);
    }
    update_pricelist_market_pricing();
}

function validate_pricelists_cost_price($product_id = false)
{
    $products = \DB::table('crm_products')->get();
    $exchange_rate = get_exchange_rate();
    foreach ($products as $product) {
        if ($product->cost_price == 0) {
            $cost_price_usd = 0;
        } else {
            $cost_price_usd = $product->cost_price * $exchange_rate;
        }

        \DB::table('crm_products')->where('id', $product->id)->update(['cost_price_usd'=>$cost_price_usd]);
    }

    $admin_only_category_ids = \DB::table('crm_product_categories')->where('customer_access', 0)->pluck('id')->toArray();
    $admin_only_product_ids = \DB::table('crm_products')->where('product_category_id', $admin_only_category_ids)->pluck('id')->toArray();

    $discontinued_category_ids = \DB::table('crm_product_categories')->where('not_for_sale', 1)->orWhere('is_deleted', 1)->pluck('id')->toArray();

    
    if(!$product_id){
        $deleted_category_ids = \DB::table('crm_product_categories')->where('is_deleted',1)->pluck('id')->toArray();
        foreach($deleted_category_ids as $deleted_category_id){
            \DB::table('crm_products')->where('product_category_id', $deleted_category_id)->update(['status'=>'Deleted']);
            $product_ids = \DB::table('crm_products')->where('product_category_id',$request->id)->pluck('id')->toArray();
            \DB::table('crm_pricelist_items')->whereIn('product_id', $product_ids)->update(['status'=>'Deleted']);
        }
        
        
        $enabled_product_ids = \DB::table('crm_products')->whereNotIn('product_category_id', $discontinued_category_ids)->where('status', 'Enabled')->pluck('id')->toArray(); 
        \DB::table('crm_pricelist_items')->whereIn('product_id', $enabled_product_ids)->update(['status' => 'Enabled']);
        \DB::table('crm_pricelist_items')->whereNotIn('product_id', $enabled_product_ids)->update(['status' => 'Deleted']);
    }
    
    if ($product_id) {
        $products = \DB::table('crm_products')->where('id', $product_id)->where('status', 'Enabled')->get();
    } else {
        $products = \DB::table('crm_products')->where('status', 'Enabled')->get();
    }


    // SET ADMIN COSTPRICES
    $admin_pricelists = \DB::table('crm_pricelists')->where('partner_id', 1)->get();
    foreach ($products as $product) {
        foreach ($admin_pricelists as $admin_pricelist) {
            $pricelist_item_exists = \DB::table('crm_pricelist_items')
            ->where('pricelist_id', $admin_pricelist->id)
            ->where('product_id', $product->id)
            ->count();
            $pricelist_item = \DB::table('crm_pricelist_items')
            ->where('pricelist_id', $admin_pricelist->id)
            ->where('product_id', $product->id)
            ->get()->first();

            $cost_price = ($admin_pricelist->currency == 'USD') ? $product->cost_price_usd : $product->cost_price;
          
            $markup = $admin_pricelist->default_markup;
            
            // update prices that are below 10 markup
            
            if($pricelist_item_exists){
                if($pricelist_item->price == 0 && $pricelist_item->cost_price == 0 && $cost_price > 0){
                }elseif($pricelist_item->markup < $admin_pricelist->default_markup){
                    $markup = $admin_pricelist->default_markup;
                }
            }
            
            
            $price = $cost_price + (($cost_price / 100) * $markup);
          

            $data = [
               'product_id' => $product->id,
               'pricelist_id' => $admin_pricelist->id,
               'cost_price' => $cost_price,
               'markup' => $markup,
               'price' => $price,
               'price_tax' => $price*1.15,
           
            ];


            $data['price_tax'] = round_price($data['price_tax']);
           
            $data['price'] = $data['price_tax']/1.15;

            /*
            elseif(session('instance')->id == 15){
            \DB::table('crm_pricelist_items')->where('id',$pricelist_item->id)->update($data);
            }
            */

            if (!$pricelist_item_exists) {
                \DB::table('crm_pricelist_items')->insert($data);
            }elseif($pricelist_item->id && $pricelist_item->price == 0 && $pricelist_item->cost_price == 0 && $cost_price > 0){
                \DB::table('crm_pricelist_items')->where('id',$pricelist_item->id)->update($data);
                
            
            // update prices that are below 10 markup
            }elseif($pricelist_item->id && $pricelist_item->markup < $admin_pricelist->default_markup  && $cost_price > 0){
               
              //  \DB::table('crm_pricelist_items')->where('id',$pricelist_item->id)->update($data);
     
            }  else {

                // update reseller costprice and markup
                $markup = intval(($cost_price > 0) ? ($pricelist_item->price - $cost_price) * 100 / $cost_price : 0);
             
                $data = [
                    'cost_price' => $cost_price,
                    'markup' => $markup,
                ];
                \DB::table('crm_pricelist_items')
                ->where('product_id', $product->id)
                ->where('pricelist_id', $admin_pricelist->id)
                ->update($data);
            }
        }
    }
    
    $airtime_prepaid_ids = get_activation_type_product_ids('airtime_prepaid');
    $airtime_contract_ids = get_activation_type_product_ids('airtime_contract');
    $unlimited_channel_ids = get_activation_type_product_ids('unlimited_channel');
    
    // SET WHOLESALE COSTPRICES
    $partner_pricelists = \DB::table('crm_pricelists')
    ->where('partner_id', '!=', 1)
    ->get();

    foreach ($products as $product) {
        foreach ($partner_pricelists as $partner_pricelist) {
            $reseller = dbgetaccount($partner_pricelist->partner_id);
            $partner_pricelist_id = $reseller->pricelist_id;
            $pricelist_item_exists = \DB::table('crm_pricelist_items')
            ->where('pricelist_id', $partner_pricelist->id)
            ->where('product_id', $product->id)
            ->count();

            $pricelist_item = \DB::table('crm_pricelist_items')
            ->where('pricelist_id', $partner_pricelist->id)
            ->where('product_id', $product->id)
            ->get()->first();


            $admin_pricelist = \DB::table('crm_pricelist_items')
            ->where('pricelist_id', $partner_pricelist_id)
            ->where('product_id', $product->id)
            ->get()->first();
            $cost_price = $admin_pricelist->reseller_price_tax;
            if(empty($cost_price)){
                $cost_price = 0;
            }

            $markup = $partner_pricelist->default_markup;

            $price = $cost_price + (($cost_price / 100) * $markup);

            $data = [
               'product_id' => $product->id,
               'pricelist_id' => $partner_pricelist->id,
               'cost_price' => $cost_price,
               'markup' => $markup,
               'price' => $price,
               'price_tax' => $price*1.15,
            ];
            
            if(
                (count($airtime_prepaid_ids) > 0 && in_array($product->id,$airtime_prepaid_ids)) || 
                (count($airtime_contract_ids) > 0 && in_array($product->id,$airtime_contract_ids)) || 
                (count($unlimited_channel_ids) > 0 && in_array($product->id,$unlimited_channel_ids))
            ){
                $data = (array) $admin_pricelist;
                unset($data['id']);
                $data['pricelist_id'] = $partner_pricelist->id;
            }



            if (!$pricelist_item_exists) {
                \DB::table('crm_pricelist_items')->insert($data);
            } elseif(
                (count($airtime_prepaid_ids) > 0 && in_array($product->id,$airtime_prepaid_ids)) || 
                (count($airtime_contract_ids) > 0 && in_array($product->id,$airtime_contract_ids)) || 
                (count($unlimited_channel_ids) > 0 && in_array($product->id,$unlimited_channel_ids))
            ){
                \DB::table('crm_pricelist_items')
                ->where('pricelist_id', $partner_pricelist->id)
                ->where('product_id', $product->id)->update($data);
            }elseif ($cost_price > $pricelist_item->price) {
                \DB::table('crm_pricelist_items')
                ->where('pricelist_id', $partner_pricelist->id)
                ->where('product_id', $product->id)->update($data);
            } else {
                // update reseller costprice and markup
                $markup = intval(($cost_price > 0) ? ($pricelist_item->price - $cost_price) * 100 / $cost_price : 0);
                $data = [
                    'cost_price' => $cost_price,
                    'markup' => $markup,
                ];
                try{
                    \DB::table('crm_pricelist_items')
                    ->where('product_id', $product->id)
                    ->where('pricelist_id', $partner_pricelist->id)
                    ->update($data);
                }catch(\Throwable $ex){
                    // retry incase of deadlock
                    sleep(1);
                     \DB::table('crm_pricelist_items')
                    ->where('product_id', $product->id)
                    ->where('pricelist_id', $partner_pricelist->id)
                    ->update($data);
                }
            }
        }
    }

    $partner_pricelist_ids = \DB::table('crm_pricelists')
    ->where('partner_id', '!=', 1)
    ->pluck('id')->toArray();


    \DB::table('crm_pricelist_items')
    ->whereIn('product_id', $admin_only_product_ids)
    ->whereIn('pricelist_id', $partner_pricelist_ids)
    ->update(['status'=>'Deleted']);

    $zar_admin_pricelist_id = \DB::table('crm_pricelists')->where('partner_id', 1)->where('default_pricelist', 1)->where('currency', 'ZAR')->pluck('id')->first();
    $usd_admin_pricelist_id = \DB::table('crm_pricelists')->where('partner_id', 1)->where('default_pricelist', 1)->where('currency', 'USD')->pluck('id')->first();
    $zar_pricelist_ids = \DB::table('crm_pricelists')->where('currency', 'ZAR')->pluck('id')->toArray();
    $usd_pricelist_ids = \DB::table('crm_pricelists')->where('currency', 'USD')->pluck('id')->toArray();


    $usd_pricelist_ids = \DB::table('crm_pricelists')->where('currency', 'USD')->pluck('id')->toArray();
    $usd_product_category_ids = \DB::table('crm_product_categories')->where('usd_active', 1)->where('is_deleted',0)->pluck('id')->toArray();
    $usd_product_ids = \DB::table('crm_products')->whereIn('product_category_id', $usd_product_category_ids)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    \DB::table('crm_pricelist_items')
        ->whereNotIn('product_id', $usd_product_ids)
        ->whereIn('pricelist_id', $usd_pricelist_ids)
        ->update(['status'=>'Deleted']);
    /*
    $vat_accounts = \DB::table('crm_account_partner_settings')->where('vat_enabled',0)->pluck('account_id')->toArray();
    $vat_pricelists = \DB::table('crm_pricelists')->whereIn('partner_id',$vat_accounts)->pluck('id')->toArray();
    $usd_pricelists = \DB::table('crm_pricelists')->where('currency','USD')->pluck('id')->toArray();
    \DB::table('crm_pricelist_items')->whereIn('pricelist_id',$vat_pricelists)->update(['price'=>\DB::raw('price_tax')]);
    \DB::table('crm_pricelist_items')->whereIn('pricelist_id',$usd_pricelists)->update(['price'=>\DB::raw('price_tax')]);
    */
    $remove_tax_fields = get_admin_setting('remove_tax_fields');
    if ($remove_tax_fields) {
        \DB::table('crm_pricelist_items')->update(['price_tax'=>\DB::raw('price')]);
    }
}

function schedule_validate_partner_pricelists()
{
    $partners = \DB::table('crm_accounts')->where('type', 'reseller')->where('status', '!=', 'Deleted')->pluck('id')->toArray();
    foreach ($partners as $partner_id) {
        validate_partner_pricelists($partner_id);
    }
}

function validate_partner_pricelists($partner_id)
{
    $active_products = \DB::table('crm_products')->where('status', 'Enabled')->pluck('id')->toArray();
    \DB::table('crm_pricelist_items')->whereNotIn('product_id', $active_products)->delete();

    $pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', $partner_id)->pluck('id')->toArray();
    if (count($pricelist_ids) == 0) {
        $pricelist_ids[] =  setup_new_pricelist($partner_id);
    }

    $default_pricelists = \DB::table('crm_pricelists')->where('partner_id', $partner_id)->where('default_pricelist', 1)->get();
    foreach ($default_pricelists as $default_pricelist) {
        \DB::table('crm_accounts')
         ->where('currency', $default_pricelist->currency)
         ->where('partner_id', $partner_id)
         ->whereNotIn('pricelist_id', $pricelist_ids)
         ->update(['pricelist_id' => $default_pricelist->id]);
    }
}

function onload_pricelist_update_count($request)
{
    /*
   $active_partners = \DB::table('crm_accounts')->where('status','!=','Deleted')->where('type','reseller')->pluck('id')->toArray();
   $pricelist_ids = \DB::table('crm_pricelists')->whereNotIn('partner_id',$active_partners)->pluck('id')->toArray();
   \DB::table('crm_pricelist_items')->whereIn('pricelist_id',$pricelist_ids)->delete();
   \DB::table('crm_pricelists')->whereIn('id',$pricelist_ids)->delete();
   */
    $pricelists = \DB::table('crm_pricelists')->get();
    foreach ($pricelists as $pricelist) {
        $count = \DB::table('crm_accounts')->where('pricelist_id', $pricelist->id)->where('status', '!=', 'Deleted')->count();
        \DB::table('crm_pricelists')->where('id', $pricelist->id)->update(['allocated_count'=>$count]);
    }
}

function onload_admin_pricing_set_markup()
{
    $admin_pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();

    \DB::table('crm_pricelist_items')
    ->whereIn('pricelist_id', $admin_pricelist_ids)
    ->where('cost_price', '>', 0)
    ->update([
        'markup' => \DB::raw('(price_tax - cost_price) * 100 / cost_price'),
        'reseller_markup' => \DB::raw('(reseller_price_tax - cost_price) * 100 / cost_price'),
        'markup_6' => \DB::raw('(price_tax_6 - cost_price) * 100 / cost_price'),
        'markup_12' => \DB::raw('(price_tax_12 - cost_price) * 100 / cost_price'),
        'markup_24' => \DB::raw('(price_tax_24 - cost_price) * 100 / cost_price'),
    ]);

    \DB::table('crm_pricelist_items')
    ->whereIn('pricelist_id', $admin_pricelist_ids)
    ->where('product_id', $request->id)
    ->where('reseller_price_tax', 0)
    ->update([
        'reseller_markup' => 0,
    ]);

    \DB::table('crm_pricelist_items')
    ->whereIn('pricelist_id', $admin_pricelist_ids)
    ->where('product_id', $request->id)
    ->where('price_tax_6', 0)
    ->update([
        'markup_6' => 0,
    ]);

    \DB::table('crm_pricelist_items')
    ->whereIn('pricelist_id', $admin_pricelist_ids)
    ->where('product_id', $request->id)
    ->where('price_tax_12', 0)
    ->update([
        'markup_12' => 0,
    ]);

    \DB::table('crm_pricelist_items')
    ->whereIn('pricelist_id', $admin_pricelist_ids)
    ->where('product_id', $request->id)
    ->where('price_tax_24', 0)
    ->update([
        'markup_24' => 0,
    ]);

}

function aftersave_pricelist_defaults($request)
{
    $partner_id = $request->partner_id;
    if (empty($request->partner_id)) {
        \DB::table('crm_pricelists')->where('id', $request->id)->update(['partner_id' => session('account_id')]);
        $partner_id = session('account_id');
    }

    // update defaults
    if (!empty($request->default_pricelist)) {
        if (1 == $partner_id) {
            \DB::table('crm_pricelists')->where('id', '!=', $request->id)->where('partner_id', $partner_id)->where('currency', $request->currency)->update(['default_pricelist' => 0]);
        } else {
            \DB::table('crm_pricelists')->where('id', '!=', $request->id)->where('partner_id', $partner_id)->update(['default_pricelist' => 0]);
        }
    }

    // populate new pricelist
    $pricelist_exists = \DB::table('crm_pricelist_items')->where('pricelist_id', $request->id)->count();
    if (!$pricelist_exists) {
        setup_new_pricelist($partner_id, $request->id);
    }
}


function beforedelete_pricelist_delete_valid($request)
{
    $pricelist = \DB::table('crm_pricelists')->where('id', $request->id)->get()->first();
    if ($pricelist->default_pricelist) {
        return 'You cannot delete a default pricelist';
    }

    // customers pricelist check
    $account_count = \DB::table('crm_accounts')->where('pricelist_id', $request->id)->where('status', '!=', 'Deleted')->count();
    if ($account_count > 0) {
        return 'You cannot delete an active pricelist.';
    }
}

function afterdelete_delete_pricelists_items($request)
{
    \DB::table('crm_pricelist_items')->where('pricelist_id', $request->id)->delete();
}


function button_pricelists_send_pricelist($request)
{
    $data['pricelist_id'] = $request->id;
    $data['send_to'] = [];
    $data['send_to'][] = \Auth::user()->email;
    $data['send_to'][] = 'All Customers';

    if (session('role_level') == 'Admin') {
        $data['send_to'][] = 'All Partners';
    }

    return view('__app.button_views.send_pricelist', $data);
}

function button_pricelists_reset_to_markup($request)
{
    $pricelist = \DB::table('crm_pricelists')->where('id', $request->id)->get()->first();
    $pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $pricelist->id)->get();
    $list = [];
    foreach ($pricelist_items as $item) {
        $cost_price = $item->cost_price;
        $markup = ($cost_price / 100) * $pricelist->default_markup;
        $price = $cost_price + $markup;
        $update_data['markup'] = ($item->cost_price > 0) ? $pricelist->default_markup : 0;
        $update_data['price'] = ($item->cost_price > 0) ? $price : 0;
        $update_data['price_tax'] = ($item->cost_price > 0) ? $price * 1.15 : 0;
        \DB::table('crm_pricelist_items')->where('id', $item->id)->update($update_data);
    }

    return json_alert('Reset complete');
}

function button_pricelists_reset_to_retail($request)
{
    $retail_pricelist_id = \DB::table('crm_pricelists')->where(['partner_id' => 1, 'type' => 'retail', 'default_pricelist' => 1])->pluck('id')->first();

    $pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $retail_pricelist_id)->get();

    foreach ($pricelist_items as $item) {
        $item = (array) $item;
        unset($item['id']);
        unset($item['pricelist_id']);

        $exists = \DB::table('crm_pricelist_items')
            ->where('pricelist_id', $request->id)
            ->where('product_id', $item['product_id'])
            ->count();

        if ($exists) {
            \DB::table('crm_pricelist_items')
                ->where('pricelist_id', $request->id)
                ->where('product_id', $item['product_id'])
                ->update($item);
        } else {
            $item['pricelist_id'] = $request->id;
            \DB::table('crm_pricelist_items')->insert($item);
        }
    }

    return json_alert('Reset complete');
}

function button_pricelists_reset_to_wholesale($request)
{
    
    
}

function setup_new_pricelist($partner_id, $pricelist_id = null)
{
    //create new pricelist

    $reseller = dbgetaccount($partner_id);
    $currency = $reseller->currency;
    if (!$pricelist_id) {
        $reseller = dbgetaccount($partner_id);
        $currency = $reseller->currency;
        $pricelist_data = [
            'name' => 'Default new',
            'partner_id' => $partner_id,
            'default_pricelist' => 1,
            'currency' => $currency,
            'default_markup' => 15,
        ];
        $pricelist_id = \DB::table('crm_pricelists')->insertGetId($pricelist_data);
    }

    $admin_pricelist_id = \DB::table('crm_pricelists')->where('partner_id', 1)->where('default_pricelist', 1)->where('currency', $currency)->pluck('id')->first();
    $pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)->get();
    foreach ($pricelist_items as $pricelist_item) {
        $pricelist_item = (array) $pricelist_item;
        unset($pricelist_item['id']);
        $pricelist_item['pricelist_id'] = $pricelist_id;
        \DB::table('crm_pricelist_items')->insert($pricelist_item);
    }

    return $pricelist_id;
}


function pricelist_get_price_field_old($account, $product_id, $line_qty = 0, $bill_frequency = 1)
{
    $account_id = $account->id;
    $enable_discounts = get_admin_setting('enable_discounts');
    if (!$enable_discounts) {
        return 'price_tax';
    }

    // check subscription qty
    $account_type = $account->type;
    $product_type = \DB::table('crm_products')->select('type')->where('id', $product_id)->pluck('type')->first();
    $product_subscription = \DB::table('crm_products')->where('id', $product_id)->pluck('is_subscription')->first();
    
    
    $subscription_qty = 0;
    if($bill_frequency > 1 && $product_subscription){
        $line_qty = $bill_frequency;
    }else{
        if ($account_type == 'reseller') {
            $reseller_user_ids = \DB::table('crm_accounts')
            ->where('partner_id', $account_id)
            ->where('status', '!=', 'Deleted')
            ->pluck('id')
            ->toArray();
            // check reseller_user subscription totals
            if (count($reseller_user_ids) > 0) {
                $subscription_qty += \DB::table('sub_services')
                ->where('product_id', $product_id)
                ->whereIn('account_id', $reseller_user_ids)
                ->where('status', '!=', 'Deleted')
                ->sum(\DB::raw('bill_frequency*qty'));
            }
           
            if ($subscription_qty) {
                $line_qty += $subscription_qty;
            }
        } else {
            $subscription_qty += \DB::table('sub_services')
            ->where('product_id', $product_id)
            ->where('account_id', $account_id)
            ->where('status', '!=', 'Deleted')
            ->sum(\DB::raw('bill_frequency*qty'));
            
           
            if ($subscription_qty) {
                $line_qty += $subscription_qty;
            }
        }
    }
   
   
    if ($line_qty >= 24) {
        return 'price_tax_24';
    }
    
    if ($line_qty >= 12) {
        return 'price_tax_12';
    }
  
    if ($line_qty >= 6) {
        return 'price_tax_6';
    }
    
    if($account_type == 'reseller'){
        return 'reseller_price_tax';
    }

    return 'price_tax';
}

function pricelist_get_price_field($account, $product_id, $line_qty = 0, $bill_frequency = 1, $contract_period = 0)
{
    $account_id = $account->id;
    $enable_discounts = get_admin_setting('enable_discounts');
    if (!$enable_discounts) {
        return 'price_tax';
    }
      
    // check subscription qty
    $account_type = $account->type;
    
    if($account->use_wholesale_pricing == 1){
        if($bill_frequency == 12){
            return 'wholesale_price_tax_12';
        }
        if($contract_period == 12){
            return 'wholesale_price_tax_12';
        }
        return 'wholesale_price_tax';
    }elseif($account_type == 'reseller'){
        if($bill_frequency == 12){
            return 'reseller_price_tax_12';
        }
        if($contract_period == 12){
            return 'reseller_price_tax_12';
        }
        return 'reseller_price_tax';
    }else{
        if($bill_frequency == 12){
            return 'price_tax_12';
        }
        if($contract_period == 12){
            return 'price_tax_12';
        }
    }

    return 'price_tax';
}

// get pricing
function pricelist_get_price($account_id, $product_id, $line_qty = 0, $bill_frequency = 1, $contract_period = 0)
{
    $account = dbgetaccount($account_id);
  
    $enable_discounts = get_admin_setting('enable_discounts');
    $price_field = pricelist_get_price_field($account, $product_id, $line_qty, $bill_frequency,$contract_period);
    
    $full_price_incl = \DB::table('crm_pricelist_items')
    ->where('pricelist_id', $account->pricelist_id)
    ->where('product_id', $product_id)
    ->pluck($price_field)->first();

    $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', $account->partner_id)->pluck('vat_enabled')->first();
    $full_price = $full_price_incl/1.15;
    $remove_tax_fields = get_admin_setting('remove_tax_fields');
	if ($remove_tax_fields) {
	    $full_price = $full_price_incl;
	}
    if (!$vat_enabled) {
        $full_price_incl = $full_price;
    }
    $currency = get_account_currency($account->id);
    if ($currency == 'USD') {
        $full_price = $full_price_incl;
    }
    $full_price = $full_price * $bill_frequency;
    $full_price_incl = $full_price_incl * $bill_frequency;
    
    return (object) ['price' => $full_price, 'full_price' => $full_price, 'full_price_incl' => $full_price_incl];
}



function pricelist_get_lead_price($product_id)
{
    $full_price_incl = \DB::table('crm_pricelist_items')
    ->where('pricelist_id', 1)
    ->where('product_id', $product_id)
    ->pluck('price_tax')->first();

    $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', 1)->pluck('vat_enabled')->first();
    $full_price = $full_price_incl/1.15;
    $remove_tax_fields = get_admin_setting('remove_tax_fields');
	if ($remove_tax_fields) {
	    $full_price = $full_price_incl;
	}
    if (!$vat_enabled) {
        $full_price_incl = $full_price;
    }
    
    return (object) ['price' => $full_price, 'full_price' => $full_price, 'full_price_incl' => $full_price_incl];
}

function pricelist_get_supplier_price($product_id, $supplier_id)
{
    $currency = get_supplier_currency($supplier_id);
    if($currency == 'USD'){
        $product = \DB::table('crm_products')
            ->select('cost_price_usd as price')
            ->where('id', $product_id)
            ->get()->first();
    }else{
        $product = \DB::table('crm_products')
            ->select('cost_price as price')
            ->where('id', $product_id)
            ->get()->first();
    }
    $full_price = $product->price;
    $date = date('Y-m-d');
   

    return (object) ['frequency' => $frequency, 'full_price' => $full_price, 'price' => $full_price];
}

function limit_accounts_pricelist($options, $row)
{
    if (!empty($row['id'])) {
        $pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', $row['partner_id'])->pluck('id')->toArray();
    } else {
        $pricelist_ids = \DB::table('crm_pricelists')->where('default_pricelist', 1)->where('partner_id', session('account_id'))->pluck('id')->toArray();
    }

    $list = [];
    foreach ($options as $opt) {
        if (in_array($opt->id, $pricelist_ids)) {
            $list[] = $opt;
        }
    }

    return $list;
}

function export_reseller_pricelist($name = false)
{
    $reseller = dbgetaccount(1);
    $pricelist_items = \DB::table('crm_products as p')
        ->select('pc.department', 'pc.name as category', 'p.code', 'p.name', 'p.status', 'p.id', 'pc.id as category_id')
        ->join('crm_product_categories as pc', 'p.product_category_id', '=', 'pc.id')
        ->where('pc.is_deleted', 0)
        ->where('p.status', 'Enabled')
        ->orderby('pc.sort_order')
        ->orderby('p.sort_order')
        ->get();

    $company = string_clean($reseller->company);

    $file_title = $company.' - Reseller Pricelist '.date('Y-m-d');
    if ($name) {
        $file_title = $name;
    }

    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    $excel_list = [];
    $call_rate_list = [];
    $product_list = [];

    foreach ($pricelist_items as $item) {
        $price_item = \DB::table('crm_pricelist_items')->where('pricelist_id', 1)->where('product_id', $item->id)->get()->first();
        $price_ex = $price_item->reseller_price_tax/1.15;
        $price_inc = $price_item->reseller_price_tax;
        if (!$reseller->vat_enabled) {
            $price_ex = $price_inc;
        }
        if (str_contains(strtolower($item->code), 'rate')) {
            $call_rate_list[] = [
                'Category' => 'Call Rates',
                'Code' => $item->code,
                'Name' => $item->name,
                'Price Excl' => currency($price_ex),
                'Price Incl' => currency($price_inc),
            ];
        } else {
            if (800 != $item->category_id && $item->category_id != 953) {
                $product_list[] = [
                    'Category' => $item->department.' - '.$item->category,
                    'Code' => $item->code,
                    'Name' => $item->name,
                    'Price Excl' => currency($price_ex),
                    'Price Incl' => currency($price_inc),
                ];
            }
        }
    }


    foreach ($call_rate_list as $cr) {
        //  $excel_list[] = $cr;
    }

    $excel_list[] = [
        'Category' => '',
        'Code' => '',
        'Name' => '',
        'Price Excl' => '',
        'Price Incl' => '',
    ];

    foreach ($product_list as $cr) {
        $excel_list[] = $cr;
    }


    $export = new App\Exports\CollectionExport();
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

    return $file_name;
}

function export_pricelist($pricelist_id, $format = 'pdf', $pricing_export = false, $file_name = false)
{
    $enable_discounts = get_admin_setting('enable_discounts');

    $admin_pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();
    $pricelist = \DB::table('crm_pricelists')->where('id', $pricelist_id)->get()->first();
   
    $reseller = \DB::table('crm_accounts')->where('id', $pricelist->partner_id)->get()->first();
    $pricelist_items = \DB::table('crm_pricelist_items as pi')
        ->select(
            'pc.department',
            'pc.name as category',
            'p.code',
            'p.name',
            'p.description',
            'p.type',
            'p.status',
            'p.frequency',
            'p.is_subscription',
            'pi.pricelist_id',
            'pi.price',
            'pi.price_tax',
            'pi.reseller_price_tax',
            'pi.price_tax_6',
            'pi.price_tax_12',
            'pi.price_tax_24',
            'p.id',
            'pc.id as category_id',
            'p.upload_file as image',
        )
        ->join('crm_products as p', 'p.id', '=', 'pi.product_id')
        ->join('crm_product_categories as pc', 'p.product_category_id', '=', 'pc.id')
        ->where('pi.pricelist_id', $pricelist_id)
        ->where('p.status', 'Enabled')
        ->where('pc.is_deleted', 0)
        ->where('pi.status', 'Enabled')
        ->where('pc.not_for_sale', 0)
        ->where('pc.customer_access', 1)
        ->orderby('pc.sort_order')
        ->orderby('p.sort_order')
        ->get();

    $company = string_clean($reseller->company);
    
    if(!$file_name){
    $file_name = string_clean($reseller->company).' Pricelist '.$pricelist_id.'-'.date('Y-m-d').'.'.$format;
    }
    if($pricing_export){
        $file_path = uploads_path().'/pricing_exports/'.$file_name;
    }else{
        $file_path = attachments_path().$file_name;
    }

    $data = [];
    $data['enable_discounts'] =  $enable_discounts;
    $data['product_categories'] = \DB::table('crm_product_categories')
        ->where('is_deleted', 0)
        ->where('not_for_sale', 0)
        ->where('customer_access', 1)
        ->orderby('sort_order')
        ->get();
    $data['pricelist_items'] = [];

    $products_path = uploads_url(71);
    foreach ($pricelist_items as $item) {
        if (str_contains(strtolower($item->code), 'rate')) {
            continue;
        } else {
            if (800 != $item->category_id && $item->category_id != 953) {
              
                $price = $item->price_tax;
                
                if($item->type != 'Stock'){
                    $item->image = '';
                }else{
                    if($item->image > ''){
                        $item->image = $products_path.$item->image;    
                    }else{
                        $item->image = '';
                    }
                }

                $item->price_tax = currency($item->price_tax);
                if (in_array($item->pricelist_id, $admin_pricelist_ids) && $enable_discounts) {
                    $item->reseller_price_tax = currency($item->reseller_price_tax);
                    $item->price_tax_6 = currency($item->price_tax_6);
                    $item->price_tax_12 = currency($item->price_tax_12);
                    $item->price_tax_24 = currency($item->price_tax_24);
                } else {
                    unset($item->reseller_price_tax);
                    unset($item->price_tax_6);
                    unset($item->price_tax_12);
                    unset($item->price_tax_24);
                }
                $item->description = str_replace(['<li>','•'],['<br><li>','<br>'],$item->description);
                $item->description = str_replace([PHP_EOL,'<br>'],[' ',' '],$item->description);
                $item->description = strip_tags($item->description);
                if(strlen($item->description) > 200){
                    $item->description = substr($item->description,0,200).'...';
                }
               
                $data['pricelist_items'][] = $item;
               
            }
        }
    }

    $data['pricelist_items'] = collect($data['pricelist_items'])->groupBy('category_id');
    $data['pricelist_product_items'] = collect($data['pricelist_product_items'])->groupBy('category_id');
    $data['currency'] = $pricelist->currency;
    $data['currency_symbol'] = get_currency_symbol($pricelist->currency);
    $bundles = \DB::table('crm_product_bundles')->where('is_deleted',0)->get();
    foreach($bundles as $i => $bundle){
        $bundles[$i]->lines =    \DB::table('crm_product_bundle_details')->where('product_bundle_id',$bundle->id)->get();  
    }
    $data['bundles'] = $bundles->groupBy('category_id');
    
 
    $admin = dbgetaccount($pricelist->partner_id);
    $data['admin'] = $admin;
    $data['logo_src'] = 'https://'. session('instance')->domain_name .'/uploads/'. session('instance')->directory .'/348/'.$admin->logo;
    $data['logo_path'] = uploads_path(348).$admin->logo;
    
    if ($format == 'pdf') {
        $options = [
            'page-size'=>'a4',
            'orientation' => 'portrait',
            'encoding' => 'UTF-8',
            'footer-left' => 'All prices include vat',
            'footer-right' =>  $admin->company.' | Page [page] of [topage]',
            'footer-font-size' => 8,
        ];
        //dd($admin);
        //if(is_dev())
        //return view('__app.exports.pricelist_pdf', $data);
        //Create our PDF with the main view and set the options
        $pdf = PDF::loadView('__app.exports.pricelist_pdf', $data);

        $pdf->setOptions($options);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $pdf->save($file_path);
    } else {
        $export = new App\Exports\ViewExport();
        $export->setViewFile('pricelist');
        $export->setViewData($data);
        
        if($pricing_exports){
            
            $instance_dir = session('instance')->directory;
            $result = Excel::store($export, $file_name, 'pricing_exports');
        }else{
            $result = Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
        }
    }
    return $file_name;
}



function schedule_pricelists_reseller_check_products()
{
    $product_ids = \DB::connection('default')->table('crm_products')->where('not_for_sale', 1)->pluck('id')->toArray();
    $pricelist_ids = \DB::connection('default')->table('crm_pricelists')->where('partner_id', '!=', 1)->pluck('id')->toArray();
    \DB::connection('default')->table('crm_pricelist_items')->whereIn('pricelist_id', $pricelist_ids)->whereIn('product_id', $product_ids)->delete();
}


function schedule_export_pricing(){
    if(!in_array(2,session('app_ids'))){
        return false;    
    }
    //set_product_marketing_prices();
    set_product_bundle_totals();
    pricelist_set_discounts();
    $email_uploads_path = uploads_emailbuilder_path();
    $instance_dir = session('instance')->directory;
    if (is_main_instance()) {
      
        // complete rates
        // all destinations has summary rates, no need to export call rates other
        /*
        $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id',1)->get();
        foreach($ratesheets as $ratesheet){
        export_partner_rates($ratesheet->id,true,'Call_Rates_Other_'.$ratesheet->currency.'.xlsx');
        File::copy('/home/erpcloud-live/htdocs/html/uploads/'.$instance_dir.'/pricing_exports/Call_Rates_Other_'.$ratesheet->currency.'.xlsx', '/home/erpcloud-live/htdocs/html/attachments/'.$instance_dir.'/Call_Rates_Other_'.$ratesheet->currency.'.xlsx');
        }
        */
        
        // summary rates
        $ratesheets = \DB::connection('pbx')->table('p_rates_partner')->where('partner_id',1)->get();
        foreach($ratesheets as $ratesheet){
            export_partner_rates_summary($ratesheet->id,true,'Call_Rates_Popular_'.$ratesheet->currency.'.xlsx');
        
            File::copy(uploads_path().'/pricing_exports/Call_Rates_Popular_'.$ratesheet->currency.'.xlsx', public_path().'/attachments/'.$instance_dir.'/Call_Rates_Popular_'.$ratesheet->currency.'.xlsx');
            File::copy(uploads_path().'/pricing_exports/Call_Rates_Popular_'.$ratesheet->currency.'.xlsx',$email_uploads_path.'/Call_Rates_'.$ratesheet->currency.'.xlsx');
            if($ratesheet->currency == 'ZAR'){
                \DB::table('crm_email_manager')->where('id',589)->update(['attachment_file' => 'Call_Rates_'.$ratesheet->currency.'.xlsx' ]);
            } 
            if($ratesheet->currency == 'USD'){
                \DB::table('crm_email_manager')->where('id',555)->update(['attachment_file' => 'Call_Rates_'.$ratesheet->currency.'.xlsx' ]);
            }
            
        }
    }
        
    $pricelists = \DB::connection('default')->table('crm_pricelists')->where('partner_id',1)->get();
    foreach($pricelists as $pricelist){
        export_pricelist($pricelist->id, 'pdf', true,'Pricelist_'.$pricelist->currency.'.pdf');
        File::copy(uploads_path().'/pricing_exports/Pricelist_'.$pricelist->currency.'.pdf', '/home/erpcloud-live/htdocs/html/attachments/'.$instance_dir.'/Pricelist_'.$pricelist->currency.'.pdf');

        
    }
    
    
    // $db_storefronts = \DB::connection('default')->table('crm_business_plan')->select('name','logo','id','helpdesk_email','email_template')->get();
   
    // $admin_pricelists = \DB::connection('default')->table('crm_pricelists')->where('partner_id', 1)->get();
    // foreach($db_storefronts as $db_storefront){
       
       
    //     foreach($admin_pricelists as $admin_pricelist){
    //         if($admin_pricelist->currency == 'USD'){
    //             continue;
    //         }
    //         $file_name = export_pricelist_storefront($admin_pricelist->id, $db_storefront->id,'pdf');
    //         $file_path = uploads_path().'/pricing_exports/'.$file_name;
    //         File::copy($file_path,$email_uploads_path.'/'.$file_name);
    //         if(!is_main_instance()){
    //             \DB::table('crm_email_manager')->where('internal_function','send_customer_pricelist')->update(['attachment_file' =>$file_name ]);
    //         }elseif(is_main_instance() && $db_storefront->id == 2){
    //             \DB::table('crm_email_manager')->where('internal_function','send_customer_pricelist')->update(['attachment_file' =>$file_name ]);
    //         }elseif(is_main_instance() && $db_storefront->id == 3){
    //             \DB::table('crm_email_manager')->where('internal_function','send_bulkhub_customer_pricelist')->update(['attachment_file' =>$file_name ]);
    //         }
    //     }
         
    // }
}