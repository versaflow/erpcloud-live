<?php

function aftersave_categories_update_pricing_strategy($request)
{
    //  \DB::table('crm_products')
    //  ->where('product_category_id',$request->id)
    //  ->update(['pricing_strategy' => $request->pricing_strategy]);
    $sql = 'UPDATE crm_products 
    JOIN crm_product_categories ON crm_product_categories.id=crm_products.product_category_id
    SET crm_products.pricing_strategy = crm_product_categories.pricing_strategy';
    \DB::statement($sql);
}

function aftercommit_product_categories_set_product_pricing($request)
{
    /*
    $category = \DB::table('crm_product_categories')->where('id',$request->id)->get()->first();

    $beforesave_row = session('event_db_record');

    if($category->pricing_strategy == 'Fixed Markup' && $category->markup > 0){
        if (($category->pricing_strategy != $beforesave_row->pricing_strategy)
        || ($category->markup != $beforesave_row->markup)) {
            $products = \DB::table('crm_products')->where('status','!=','Deleted')->where('product_category_id',$category->id)->get();
            foreach($products as $product){
                $cost_price = $product->cost_price;
                $markup = $category->markup;


                $markup_amount = ($cost_price / 100) * $markup;
                $selling_price_excl = $cost_price + $markup_amount;
                $selling_price_incl = $selling_price_excl * 1.15;
                $data = (array) $product;
                $data['markup'] = $markup;
                $data['selling_price_excl'] = $selling_price_excl;
                $data['selling_price_incl'] = $selling_price_incl;
                $result = (new \DBEvent())->setTable('crm_products')->save($data);

            }
        }
    }
    */
}

function buttont_product_categories_apply_pricing_strategy($request)
{
    $category = \DB::table('crm_product_categories')->where('id', $request->id)->get()->first();
    if ($category->pricing_strategy == 'Fixed Markup' && $category->markup > 0) {

        $products = \DB::table('crm_products')->where('status', '!=', 'Deleted')->where('product_category_id', $category->id)->get();
        foreach ($products as $product) {
            $cost_price = $product->cost_price;
            $markup = $category->markup;

            $markup_amount = ($cost_price / 100) * $markup;
            $selling_price_excl = $cost_price + $markup_amount;
            $selling_price_incl = $selling_price_excl * 1.15;
            $data = (array) $product;
            $data['markup'] = $markup;
            $data['selling_price_excl'] = $selling_price_excl;
            $data['selling_price_incl'] = $selling_price_incl;
            $result = (new \DBEvent)->setTable('crm_products')->save($data);

        }
    }

    if ($category->pricing_strategy == 'Market Average') {

        $products = \DB::table('crm_products')->where('status', '!=', 'Deleted')->where('market_avg', '>', 0)->where('product_category_id', $category->id)->get();

        foreach ($products as $product) {

            $cost_price = $product->cost_price;
            $markup = intval(($cost_price > 0) ? ($product->market_avg - $cost_price) * 100 / $cost_price : 0);

            $markup_amount = ($cost_price / 100) * $markup;
            $selling_price_excl = $cost_price + $markup_amount;
            $selling_price_incl = $selling_price_excl * 1.15;
            $data = (array) $product;
            $data['markup'] = $markup;
            $data['selling_price_excl'] = $product->market_avg / 1.15;
            $data['selling_price_incl'] = $product->market_avg;

            $result = (new \DBEvent)->setTable('crm_products')->save($data);

        }
    }
}

function aftersave_category_set_product_status($request)
{
    $beforesave_row = session('event_db_record');
    $category_deleted = \DB::table('crm_product_categories')->where('id', $request->id)->pluck('is_deleted')->first();
    if (($request->not_for_sale != $beforesave_row->not_for_sale)
    || (($beforesave_row->status == 'Deleted' || $request->status == 'Deleted') && $request->status != $beforesave_row->status)
    || ($request->usd_active != $beforesave_row->usd_active)) {
        validate_pricelists_cost_price();
    }

    if ($category_deleted) {
        \DB::table('crm_products')->where('product_category_id', $request->id)->update(['status' => 'Deleted']);
        $product_ids = \DB::table('crm_products')->where('product_category_id', $request->id)->pluck('id')->toArray();
        \DB::table('crm_pricelist_items')->whereIn('product_id', $product_ids)->update(['status' => 'Deleted']);
    }
}

function afterdelete_category_set_product_status($request)
{
    \DB::table('crm_products')->where('product_category_id', $request->id)->update(['status' => 'Deleted']);
    validate_pricelists_cost_price();

    $e = \DB::table('crm_discounts')->where('product_category_id', $request->id)->count();
    if ($e) {
        \DB::table('crm_discounts')->where('product_category_id', $request->id)->delete();
        pricelist_set_discounts();

    }
}
