<?php

function aftersave_products_pricing_update_pricelist($request){
  //aa('aftercommit_products_pricing_update_pricelist');
    $product = \DB::table('crm_products')->where('id',$request->id)->get()->first();
    
   // $beforesave_row = session('event_db_record');
   
   // if($beforesave_row->selling_price_incl != $product->selling_price_incl || $beforesave_row->selling_price_excl != $product->selling_price_excl){
       
  
        $db = new DBEvent(508);
       
        $row = \DB::table('crm_pricelist_items')->where('pricelist_id',1)->where('product_id',$product->id)->get()->first();
       
        //if(empty($row) || empty($row->id)){
        //      return 'Pricelist item not found';  
        //}
        $data = (array) $row;
        $data['cost_price'] = $product->cost_price;
        $data['price'] = $product->selling_price_excl;
        $data['price_tax'] = $product->selling_price_incl;
        $data['markup'] = $product->markup;
     // aa($data);
        $result = $db->save($data);
//  aa($result);
        if($result && is_array($result) && $result['id']){
        }else{
            return $result;    
        }
   // }
    
}


/// AJAX
function ajax_products_pricing_set_pricing($request)
{

    if (!empty($request->id)) {
        $response = [];
        //COST selling_price_excl
        if (empty(session('item_ajax'))) {
            $item = \DB::table('crm_products')->where('id', $request->id)->get()->first();
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

        $old_price = currency($item->selling_price_excl);
        $selling_price_excl = currency($request->input('selling_price_excl'));

        $old_price_tax = currency($item->selling_price_incl);
        $selling_price_incl = currency($request->input('selling_price_incl'));

        $old_markup = intval($item->markup);
        $markup = intval($request->input('markup'));


        $remove_tax_fields = get_admin_setting('remove_tax_fields');
        $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', session('account_id'))->pluck('vat_enabled')->first();
        //MARKUP
        if ($markup != $old_markup) {
            //aa('markup changed');
            if (0 == $cost_price or $markup < 0) {
                $markup = 0;
            }
            $markup_amount = ($cost_price / 100) * $markup;
            $selling_price_excl = $cost_price + $markup_amount;
            $selling_price_incl = $selling_price_excl * 1.15;
        } elseif ($selling_price_excl != $old_price) {
            
            //aa('price excl changed');
            //aa($selling_price_excl);
            //aa($old_price);
            if ($selling_price_excl < $cost_price) {
                $selling_price_excl = $cost_price;
            }
            $selling_price_incl = $selling_price_excl * 1.15;
            if($remove_tax_fields || !$vat_enabled){
                $selling_price_incl = $selling_price_excl; 
            }
            $markup = intval(($cost_price > 0) ? ($selling_price_excl - $cost_price) * 100 / $cost_price : 0);
        } elseif ($selling_price_incl != $old_price_tax) {
            //aa('price incl changed');
            //aa($selling_price_incl);
            //aa($old_price_tax);
            if ($selling_price_incl < $cost_price) {
                $selling_price_incl = $cost_price;
            }
            $selling_price_excl = $selling_price_incl / 1.15;
            if($remove_tax_fields || !$vat_enabled){
                $selling_price_excl = $selling_price_incl; 
            }
            $markup = intval(($cost_price > 0) ? ($selling_price_excl - $cost_price) * 100 / $cost_price : 0);
        }

        //ROUND PRICES AND MARKUP
        //$selling_price_incl = round_price($selling_price_incl);

        $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', session('account_id'))->pluck('vat_enabled')->first();

        $response['markup'] = intval($markup);
        $response['selling_price_excl'] = currency($selling_price_excl);
        $response['selling_price_incl'] = currency($selling_price_incl);
        
        if($remove_tax_fields || !$vat_enabled){
            $response['selling_price_incl'] = $response['selling_price_excl'];  
        }
      
        $item = session('item_ajax');
        $item = (array) $item;

        
       
        $round_price = get_admin_setting('round_prices');
        
        if($item['id']){
            $airtime_product_ids = get_activation_type_product_ids('airtime_prepaid');
            $airtime_contract_ids = get_activation_type_product_ids('airtime_contract');
            if(in_array($item['id'],$airtime_product_ids) || in_array($item['id'],$airtime_contract_ids)){
                $round_price = false;
            }
        }
        
        if ($round_price) {
         
          if($remove_tax_fields || !$vat_enabled){
                $response['selling_price_excl'] = round_price($response['selling_price_excl']);
                $response['selling_price_incl'] = $response['selling_price_excl']; 
            }else{ 
                $response['selling_price_incl'] = round_price($response['selling_price_incl']);
                $response['selling_price_excl'] = $response['selling_price_incl'] / 1.15;
            }
            
         

            $response['markup'] = intval(($cost_price > 0) ? ($response['selling_price_excl'] - $cost_price) * 100 / $cost_price : 0);
        }
        

       

        foreach ($response as $k => $v) {
            $item[$k] = $v;
        }

     
        
        session(['item_ajax' => $item]);
        //aa($response);
        //aa($item);
        /// RETAIL END
        return $response;
    }
}



/// BEFORESAVE
function beforesave_products_pricing_check_markup($request)
{
   // aa($request->all());
    if (currency($request->selling_price_incl) < currency($request->cost_price)) {
       
        return 'Selling price needs to be more than cost price';
    }

    
    $beforesave_row = session('event_db_record');
  
    
    $minimum_markup = get_admin_setting('minimum_markup');
    if($request->cost_price > 0 && $request->markup < $minimum_markup){
        return  'Markup cannot be set to less than '.$minimum_markup.'.';
    }
    
    if (!empty($request->id)) {
        $response = [];
        
        $item = session('event_db_record');
        //COST selling_price_excl
        $old_cost_price = currency($item->cost_price);
        $cost_price = $request->input('cost_price');
        /// RETAIL START

        $old_price = currency($item->selling_price_excl);
        $selling_price_excl = currency($request->input('selling_price_excl'));

        $old_price_tax = currency($item->selling_price_incl);
        $selling_price_incl = currency($request->input('selling_price_incl'));

        $old_markup = intval($item->markup);
        $markup = intval($request->input('markup'));

        $remove_tax_fields = get_admin_setting('remove_tax_fields');
        $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', session('account_id'))->pluck('vat_enabled')->first();

        //MARKUP
        if ($selling_price_incl != $old_price_tax) {
            if ($selling_price_incl < $cost_price) {
                $selling_price_incl = $cost_price;
            }
            $selling_price_excl = $selling_price_incl / 1.15;
            
            if($remove_tax_fields || !$vat_enabled){
                $selling_price_excl = $selling_price_incl; 
            }
            $markup = intval(($cost_price > 0) ? ($selling_price_excl - $cost_price) * 100 / $cost_price : 0);
        }elseif ($selling_price_excl != $old_price) {
            if ($selling_price_excl < $cost_price) {
                $selling_price_excl = $cost_price;
            }
            $selling_price_incl = $selling_price_excl * 1.15;
            if($remove_tax_fields || !$vat_enabled){
                $selling_price_incl = $selling_price_excl; 
            }
            $markup = intval(($cost_price > 0) ? ($selling_price_excl - $cost_price) * 100 / $cost_price : 0);
        }elseif ($markup != $old_markup) {
            
            if (0 == $cost_price or $markup < 0) {
                $markup = 0;
            }
            $markup_amount = ($cost_price / 100) * $markup;
            $selling_price_excl = $cost_price + $markup_amount;
            $selling_price_incl = $selling_price_excl * 1.15;
        }

        //ROUND PRICES AND MARKUP
        //$selling_price_incl = round_price($selling_price_incl);



        $response['markup'] = intval($markup);
        $response['selling_price_excl'] = currency($selling_price_excl);
        $response['selling_price_incl'] = currency($selling_price_incl);
       
        $item = (array) $item;
        
        
        if($remove_tax_fields || !$vat_enabled){
            $response['selling_price_incl'] = $response['selling_price_excl'];  
        }


        $round_price = get_admin_setting('round_prices');
        
     
        if($item['id']){
            $airtime_product_ids = get_activation_type_product_ids('airtime_prepaid');
            $airtime_contract_ids = get_activation_type_product_ids('airtime_contract');
            if(in_array($item['id'],$airtime_product_ids) || in_array($item['id'],$airtime_contract_ids)){
                $round_price = false;
            }
        }
        
        if ($round_price) {
            if($remove_tax_fields || !$vat_enabled){
                $response['selling_price_excl'] = round_price($response['selling_price_excl']);
                $response['selling_price_incl'] = $response['selling_price_excl']; 
            }else{ 
                $response['selling_price_incl'] = round_price($response['selling_price_incl']);
                $response['selling_price_excl'] = $response['selling_price_incl'] / 1.15;
            }

            $response['markup'] = intval(($cost_price > 0) ? ($response['selling_price_excl'] - $cost_price) * 100 / $cost_price : 0);
        }
        
        foreach ($response as $k => $v) {
            $request->request->add([$k => $v]);
        }
    }
}

function schedule_products_pricing_update_pricelist(){

    $products = \DB::table('crm_products')
    ->select('crm_products.id','crm_products.updated_at','crm_products.selling_price_excl','crm_products.selling_price_incl','crm_products.markup')
    ->join('crm_pricelist_items','crm_pricelist_items.product_id','=','crm_products.id')
    ->where('crm_pricelist_items.pricelist_id',1)
    ->whereRaw(\DB::raw('crm_pricelist_items.updated_at < crm_products.updated_at'))->get();
   

    foreach($products as $product){
        $db = new DBEvent(508);
       
        $row = \DB::table('crm_pricelist_items')->where('pricelist_id',1)->where('product_id',$product->id)->get()->first();
       
        if(empty($row) || empty($row->id)){
            continue;
        }
        
       
        $data = (array) $row;
        $data['price'] = $product->selling_price_excl;
        $data['price_tax'] = $product->selling_price_incl;
        $data['markup'] = $product->markup;
     
        $result = $db->save($data);
    }
}