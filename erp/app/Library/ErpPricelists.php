<?php

class ErpPricelists
{
    public function createAdminPricelist($type = false)
    {
        $partner_id = 1;
        if (! $pricelist_id) {
            $currency = get_account_currency($partner_id);
            $patner = dbgetaccount($partner_id);
            $name = $reseller->company;
            $pricelist_count = \DB::table('crm_pricelists')->where('partner_id', $partner_id)->count();
            $default_pricelist = ($pricelist_count) ? 0 : 1;
            $pricelist_data = [
                'name' => $name,
                'partner_id' => $partner_id,
                'default_pricelist' => $default_pricelist,
                'type' => 'retail',
                'default_markup' => 15,
                'currency' => $currency,
            ];
            $pricelist_id = \DB::table('crm_pricelists')->insertGetId($pricelist_data);
        }

        $pricelist_currency = \DB::table('crm_pricelists')->where('pricelist_id', $pricelist_id)->pluck('currency')->first();
        // get admin retail pricing
        $admin_retail_pricelist_id = \DB::table('crm_pricelist_items')
            ->where('partner_id', 1)
            ->where('default_pricelist', 1)
            ->where('type', 'retail')
            ->where('currency', $pricelist_currency)
            ->pluck('id')->first();

        $retail_pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)->get();

        $admin_wholesale_pricelist_id = \DB::table('crm_pricelist_items')
            ->where('partner_id', 1)
            ->where('default_pricelist', 1)
            ->where('type', 'wholesale')
            ->where('currency', $pricelist_currency)
            ->pluck('id')->first();

        $wholesale_pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)->get();

        $admin_cost = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)->get();

        $products = \DB::table('crm_products')->get();

        $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', $partner_id)->pluck('vat_enabled')->first();
        foreach ($pricelist_items as $pricelist_item) {
            $data = (array) $pricelist_item;
            unset($data['id']);
            $data['pricelist_id'] = $pricelist_id;

            // set costs and markup
            if ($partner_id == 1) {
                // get cost price from products
                if ($currency == 'USD') {
                    $cost_price = $products->where('id', $pricelist_item->product_id)->pluck('cost_price_usd')->first();
                } else {
                    $cost_price = $products->where('id', $pricelist_item->product_id)->pluck('cost_price')->first();
                }
            } else {
                // get cost price from wholesale pricelist
                $cost_price = $wholesale_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price')->first();
            }

            if ($vat_enabled) {
                $price = $price_tax = $retail_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price_tax')->first();
            } else {
                $price = $retail_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price')->first();
                $price_tax = $retail_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price_tax')->first();
            }
            $markup = intval(($cost_price > 0) ? ($price_tax - $cost_price) * 100 / $cost_price : 0);

            $data['cost_price'] = $cost_price;
            $data['price'] = $price;
            $data['price_tax'] = $price_tax;
            $data['markup'] = $markup;

            \DB::table('crm_pricelist_items')->insert($data);
        }

        return $pricelist_id;
    }

    public function createPartnerPricelist($partner_id, $pricelist_id = false)
    {
        if (! $pricelist_id) {
            $currency = get_account_currency($partner_id);
            $patner = dbgetaccount($partner_id);
            $name = $reseller->company;
            $pricelist_count = \DB::table('crm_pricelists')->where('partner_id', $partner_id)->count();
            $default_pricelist = ($pricelist_count) ? 0 : 1;
            $pricelist_data = [
                'name' => $name,
                'partner_id' => $partner_id,
                'default_pricelist' => $default_pricelist,
                'type' => 'retail',
                'default_markup' => 15,
                'currency' => $currency,
            ];
            $pricelist_id = \DB::table('crm_pricelists')->insertGetId($pricelist_data);
        }

        $pricelist_currency = \DB::table('crm_pricelists')->where('pricelist_id', $pricelist_id)->pluck('currency')->first();
        // get admin retail pricing
        $admin_retail_pricelist_id = \DB::table('crm_pricelist_items')
            ->where('partner_id', 1)
            ->where('default_pricelist', 1)
            ->where('type', 'retail')
            ->where('currency', $pricelist_currency)
            ->pluck('id')->first();

        $retail_pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)->get();

        $admin_wholesale_pricelist_id = \DB::table('crm_pricelist_items')
            ->where('partner_id', 1)
            ->where('default_pricelist', 1)
            ->where('type', 'wholesale')
            ->where('currency', $pricelist_currency)
            ->pluck('id')->first();

        $wholesale_pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)->get();

        $admin_cost = \DB::table('crm_pricelist_items')->where('pricelist_id', $admin_pricelist_id)->get();

        $products = \DB::table('crm_products')->get();

        $vat_enabled = \DB::table('crm_account_partner_settings')->where('id', $partner_id)->pluck('vat_enabled')->first();
        foreach ($pricelist_items as $pricelist_item) {
            $data = (array) $pricelist_item;
            unset($data['id']);
            $data['pricelist_id'] = $pricelist_id;

            // set costs and markup
            if ($partner_id == 1) {
                // get cost price from products
                if ($currency == 'USD') {
                    $cost_price = $products->where('id', $pricelist_item->product_id)->pluck('cost_price_usd')->first();
                } else {
                    $cost_price = $products->where('id', $pricelist_item->product_id)->pluck('cost_price')->first();
                }
            } else {
                // get cost price from wholesale pricelist
                $cost_price = $wholesale_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price')->first();
            }

            if ($vat_enabled) {
                $price = $price_tax = $retail_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price_tax')->first();
            } else {
                $price = $retail_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price')->first();
                $price_tax = $retail_pricelist_items->where('product_id', $pricelist_item->product_id)->pluck('price_tax')->first();
            }
            $markup = intval(($cost_price > 0) ? ($price_tax - $cost_price) * 100 / $cost_price : 0);

            $data['cost_price'] = $cost_price;
            $data['price'] = $price;
            $data['price_tax'] = $price_tax;
            $data['markup'] = $markup;

            \DB::table('crm_pricelist_items')->insert($data);
        }

        return $pricelist_id;
    }
}
