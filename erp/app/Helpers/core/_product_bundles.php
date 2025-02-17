<?php

function set_product_bundle_totals()
{
    $bundles = \DB::table('crm_product_bundles')->get();
    foreach ($bundles as $bundle) {
        $admin_pricelist_id = \DB::table('crm_pricelists')->where('default_pricelist', 1)->where('partner_id', 1)->where('currency', 'ZAR')->pluck('id')->first();

        $bundle_products = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle->id)->get();
        $description = 'Bundle Details:'.'<br>';
        $total = 0;
        $total_excl = 0;
        $cost_total = 0;
        $cost_bundle_total = 0;

        foreach ($bundle_products as $bundle_product) {
            $product = \DB::table('crm_products')->where('id', $bundle_product->product_id)->get()->first();
            $price_excl = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)
            ->where('product_id', $bundle_product->product_id)
            ->pluck('price')->first();
            $price = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)
            ->where('product_id', $bundle_product->product_id)
            ->pluck('price_tax')->first();
            $description .= 'Code: '.$product->code.' | Qty: '.$bundle_product->qty.'<br>';
            $total += $bundle_product->qty*$price;
            $total_excl += $bundle_product->qty*$price_excl;
            $cost_price = \DB::table('crm_products')->where('id', $bundle_product->product_id)->pluck('cost_price')->first();
            $cost_total += $cost_price;
            \DB::table('crm_product_bundle_details')->where('id', $bundle_product->id)
            ->update([
                'cost_price' => $cost_price,
                'cost_price_total' => $cost_price*$bundle_product->qty,
                'price' => $price,
                'price_excl' => $price_excl,
                'line_total' => $bundle_product->qty*$price,
                'markup' => intval(($cost_price > 0) ? ($price_excl - $cost_price) * 100 / $cost_price : 0)
            ]);
            
            $cost_bundle_total += $cost_price*$bundle_product->qty;
        }
        $markup = intval(($cost_bundle_total > 0) ? ($total_excl - $cost_bundle_total) * 100 / $cost_bundle_total : 0);
        \DB::table('crm_product_bundles')->where('id', $bundle->id)->update(['description'=>$description,'total_excl'=>$total_excl,'total'=>$total,'cost_total'=>$cost_bundle_total,'markup'=>$markup]);

        $bundle_products = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle->id)->get();
        $bundle_products = sort_product_rows($bundle_products);
        foreach ($bundle_products as $i => $bundle_product) {
            \DB::table('crm_product_bundle_details')->where('id', $bundle_product->id)->update(['product_sort' => $i]);
        }
    }
}

function button_promo_bundles_update_pricing()
{
    set_product_bundle_totals();
    return json_alert('Done');
}

function schedule_set_product_bundle_totals(){
    set_product_bundle_totals();
}

function afterdelete_product_bundle_delete_items($request)
{
    \DB::table('crm_product_bundle_details')->where('product_bundle_id', $request->id)->delete();
}

