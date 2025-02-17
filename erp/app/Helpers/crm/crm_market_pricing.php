<?php

function aftersave_set_market_pricing_pricelist($request){
     $beforesave_row = session('event_db_record');
     if($beforesave_row->price_incl != $request->price_incl){
         if($request->price_incl == 0){
         \DB::table('crm_market_pricing')->where('id',$request->id)->update(['price_ex' => 0]);
         }else{
         \DB::table('crm_market_pricing')->where('id',$request->id)->update(['price_ex' => \DB::raw('price_incl/1.15')]);
         }
     }
     if($beforesave_row->price_ex != $request->price_ex){
          if($request->price_ex == 0){
         \DB::table('crm_market_pricing')->where('id',$request->id)->update(['price_incl' => 0]);
         }else{
         \DB::table('crm_market_pricing')->where('id',$request->id)->update(['price_incl' => \DB::raw('price_ex*1.15')]);
         }
     }
    update_pricelist_market_pricing();
}

function afterdelete_set_market_pricing_pricelist(){
    update_pricelist_market_pricing();
}


function update_pricelist_market_pricing(){
    $deleted_category_ids = \DB::table('crm_product_categories')->where('is_deleted',1)->pluck('id')->toArray();
    $deleted_product_ids = \DB::table('crm_products')->where('status','Deleted')->pluck('id')->toArray();

    
    \DB::table('crm_products')->update(['market_avg'=>0,'market_count'=>0,'market_diff'=>0]);
    $sql = "UPDATE crm_market_pricing
    SET product_category_id = (
      SELECT product_category_id
      FROM crm_products
      WHERE crm_market_pricing.matching_product_id = crm_products.id
    );";  
    \DB::statement($sql);
    
    \DB::table('crm_market_pricing')->where('is_deleted',0)->whereIn('product_category_id',$deleted_category_ids)->update(['is_deleted'=>1]);
    \DB::table('crm_market_pricing')->where('is_deleted',0)->whereIn('matching_product_id',$deleted_product_ids)->update(['is_deleted'=>1]);
    
    
    $sql = "UPDATE crm_products
    SET market_avg = (
      SELECT AVG(price_incl)
      FROM crm_market_pricing
      WHERE crm_market_pricing.matching_product_id = crm_products.id and crm_market_pricing.is_deleted=0
    ),
    market_count = (
      SELECT COUNT(*)
      FROM crm_market_pricing
      WHERE crm_market_pricing.matching_product_id = crm_products.id and crm_market_pricing.is_deleted=0
    );";  
    \DB::statement($sql);
    
    \DB::table('crm_products')->where('selling_price_excl',\DB::raw('market_avg'))->update(['market_diff'=>0]);
    \DB::table('crm_products')->where('market_avg',0)->update(['market_diff'=>0]);
    \DB::table('crm_products')->where('market_avg', '>',0)->where('selling_price_excl', '!=',\DB::raw('market_avg'))->update(['market_diff'=>\DB::raw('((selling_price_excl - market_avg) / market_avg) * 100')]);    
}

function button_products_set_price_to_market_avg($request)
{
    
    $row = \DB::table('crm_pricelist_items')->where('id',$request->id)->get()->first();
  
    
    $data = (array) $row;
    $db = new DBEvent(508);
    $data['price'] = $data['market_avg'];
  
    $result = $db->save($data);
   
    if($result && is_array($result) && $result['id']){
        return json_alert('Done');
    }
    
    return $result;
}