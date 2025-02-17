<?php

function schedule_pricelist_set_discounts()
{
    pricelist_set_discounts();
}

function select_options_discount_qty($row)
{
    if ($row['type'] == 'Stock') {
        $options = ['5', '10', '15'];
    }
    if ($row['type'] == 'Service') {
        $options = ['6', '12', '24'];
    }
    if ($row['type'] == 'Product') {
        $options = ['5', '10', '15', '6', '12', '24'];
    }
    if ($row['type'] == 'Category') {
        $options = ['5', '10', '15', '6', '12', '24'];
    }
    $result = array_combine($options, $options);

    return $result;
}

function beforesave_discounts_check_required($request)
{
    if ($request->type == 'Category' && empty($request->product_category_id)) {
        return 'Category is required';
    }
    if ($request->type == 'Product' && empty($request->product_id)) {
        return 'Product is required';
    }

    if (! in_array($request->quantity, [1, 3, 6, 12, 24])) {
        return 'Can only use 1,3,6,12,24 qty';
    }
}
function afterdelete_discounts_update_pricelists($request)
{
    pricelist_set_discounts();
}

function aftersave_discounts_update_pricelists($request)
{
    //pricelist_set_discounts();
}

function aftercommit_products_pricelists_volume_pricing($request)
{
    $admin_pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();
    if ($request->cost_price == 0) {
        $reset = [

            'cost_price_6' => 0,
            'cost_price_12' => 0,
            'cost_price_24' => 0,
            'price_tax_6' => 0,
            'price_tax_12' => 0,
            'price_tax_24' => 0,
            'reseller_cost_price' => 0,
            'reseller_price_tax' => 0,
            'reseller_price_tax_12' => 0,
            'wholesale_price_tax' => 0,
            'wholesale_price_tax_12' => 0,

        ];
        \DB::table('crm_pricelist_items')
            ->whereIn('pricelist_id', $admin_pricelist_ids)
            ->where('product_id', $request->id)
            ->update($reset);
    } else {
        \DB::table('crm_pricelist_items')
            ->whereIn('pricelist_id', $admin_pricelist_ids)
            ->where('product_id', $request->id)
            ->where('cost_price', '>', 0)
            ->update([
                'markup' => \DB::raw('(price_tax - cost_price) * 100 / cost_price'),
                'markup_6' => \DB::raw('(price_tax_6 - cost_price) * 100 / cost_price'),
                'markup_12' => \DB::raw('(price_tax_12 - cost_price) * 100 / cost_price'),
                'markup_24' => \DB::raw('(price_tax_24 - cost_price) * 100 / cost_price'),
                'reseller_markup' => \DB::raw('(reseller_price_tax - cost_price) * 100 / cost_price'),
                'reseller_markup_12' => \DB::raw('(reseller_price_tax_12 - cost_price) * 100 / cost_price'),
                'wholesale_markup' => \DB::raw('(wholesale_price_tax - cost_price) * 100 / cost_price'),
                'wholesale_markup_12' => \DB::raw('(wholesale_price_tax_12 - cost_price) * 100 / cost_price'),
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
            ->where('reseller_price_tax_12', 0)
            ->update([
                'reseller_markup_12' => 0,
            ]);

        \DB::table('crm_pricelist_items')
            ->whereIn('pricelist_id', $admin_pricelist_ids)
            ->where('product_id', $request->id)
            ->where('wholesale_price_tax', 0)
            ->update([
                'wholesale_markup' => 0,
            ]);

        \DB::table('crm_pricelist_items')
            ->whereIn('pricelist_id', $admin_pricelist_ids)
            ->where('product_id', $request->id)
            ->where('wholesale_price_tax_12', 0)
            ->update([
                'wholesale_markup_12' => 0,
            ]);

    }
}

function button_discounts_pricelist_set_discounts($request)
{

    pricelist_set_discounts();

    return json_alert('Done');
}

function pricelist_set_discounts()
{

    $reset = [

        'cost_price_6' => 0,
        'cost_price_12' => 0,
        'cost_price_24' => 0,
        'price_tax_6' => 0,
        'price_tax_12' => 0,
        'price_tax_24' => 0,
        'reseller_cost_price' => 0,
        'reseller_price_tax' => 0,
        'reseller_price_tax_12' => 0,
        'wholesale_price_tax' => 0,
        'wholesale_price_tax_12' => 0,
    ];
    \DB::table('crm_pricelist_items')->update($reset);

    $admin_pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();
    $reseller_pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', '!=', 1)->pluck('id')->toArray();
    $stock_product_ids = \DB::table('crm_products')->where('is_subscription', 0)->pluck('id')->toArray();
    \DB::table('crm_discounts')->where('type', '!=', 'Category')->update(['product_category_id' => 0]);
    \DB::table('crm_discounts')->where('type', '!=', 'Product')->update(['product_id' => 0]);
    $discounts = \DB::table('crm_discounts')->get();
    foreach ($discounts as $discount) {
        if ($discount->quantity == 3) {
            continue;
        }
        $update_data = [];
        $cost_update_data = [];

        if ($discount->type == 'Stock') {
            $update_fields = [];

            if (in_array($discount->customer_type, ['Both', 'Customer'])) {
                if ($discount->quantity != 1) {
                    $update_fields[] = 'price_tax_'.$discount->quantity;
                }
            }
            if (in_array($discount->customer_type, ['Both', 'Reseller'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'reseller_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'reseller_price_tax_12';
                }
            }
            if (in_array($discount->customer_type, ['Both', 'Wholesale'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'wholesale_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'wholesale_price_tax_12';
                }
            }
            foreach ($update_fields as $update_field) {
                $update_data[$update_field] = \DB::raw('price_tax-((price_tax/100)*'.$discount->percentage_discount.')');
            }

            \DB::table('crm_pricelist_items')
                ->where('price_tax', '>', 0)
                ->whereIn('pricelist_id', $admin_pricelist_ids)
                ->whereIn('product_id', $stock_product_ids)
                ->update($update_data);

            foreach ($update_data as $k => $v) {
                $k = str_replace('price_tax', 'cost_price', $k);
                $cost_update_data[$k] = $v;
            }
            unset($cost_update_data['wholesale_cost_price']);
            unset($cost_update_data['wholesale_cost_price_12']);

            if (count($cost_update_data) > 0) {
                \DB::table('crm_pricelist_items')
                    ->where('cost_price', '>', 0)
                    ->whereIn('pricelist_id', $reseller_pricelist_ids)
                    ->whereIn('product_id', $stock_product_ids)
                    ->update($cost_update_data);
            }
        }

        if ($discount->type == 'Service') {
            $update_fields = [];

            if (in_array($discount->customer_type, ['Both', 'Customer'])) {
                if ($discount->quantity != 1) {
                    $update_fields[] = 'price_tax_'.$discount->quantity;
                }
            }
            if (in_array($discount->customer_type, ['Both', 'Reseller'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'reseller_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'reseller_price_tax_12';
                }
            }

            if (in_array($discount->customer_type, ['Both', 'Wholesale'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'wholesale_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'wholesale_price_tax_12';
                }
            }
            foreach ($update_fields as $update_field) {
                $update_data[$update_field] = \DB::raw('price_tax-((price_tax/100)*'.$discount->percentage_discount.')');
            }

            \DB::table('crm_pricelist_items')
                ->where('price_tax', '>', 0)
                ->whereIn('pricelist_id', $admin_pricelist_ids)
                ->whereNotIn('product_id', $stock_product_ids)
                ->update($update_data);
            foreach ($update_data as $k => $v) {
                $k = str_replace('price_tax', 'cost_price', $k);
                $cost_update_data[$k] = $v;
            }

            unset($cost_update_data['wholesale_cost_price']);
            unset($cost_update_data['wholesale_cost_price_12']);
            if (count($cost_update_data) > 0) {
                \DB::table('crm_pricelist_items')
                    ->where('cost_price', '>', 0)
                    ->whereIn('pricelist_id', $reseller_pricelist_ids)
                    ->whereNotIn('product_id', $stock_product_ids)
                    ->update($cost_update_data);
            }
        }
    }

    foreach ($discounts as $discount) {
        $update_data = [];
        if ($discount->type == 'Category' && $discount->product_category_id) {
            $update_fields = [];

            if (in_array($discount->customer_type, ['Both', 'Customer'])) {
                if ($discount->quantity != 1) {
                    $update_fields[] = 'price_tax_'.$discount->quantity;
                }
            }
            if (in_array($discount->customer_type, ['Both', 'Reseller'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'reseller_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'reseller_price_tax_12';
                }
            }

            if (in_array($discount->customer_type, ['Both', 'Wholesale'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'wholesale_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'wholesale_price_tax_12';
                }
            }
            $product_ids = \DB::table('crm_products')->where('product_category_id', $discount->product_category_id)->pluck('id')->toArray();
            foreach ($update_fields as $update_field) {
                $update_data[$update_field] = \DB::raw('price_tax-((price_tax/100)*'.$discount->percentage_discount.')');
            }

            \DB::table('crm_pricelist_items')
                ->where('price_tax', '>', 0)
                ->whereIn('pricelist_id', $admin_pricelist_ids)
                ->whereIn('product_id', $product_ids)
                ->update($update_data);

            foreach ($update_data as $k => $v) {
                $k = str_replace('price_tax', 'cost_price', $k);
                $cost_update_data[$k] = $v;
            }

            unset($cost_update_data['wholesale_cost_price']);
            unset($cost_update_data['wholesale_cost_price_12']);

            if (count($cost_update_data) > 0) {
                \DB::table('crm_pricelist_items')
                    ->where('cost_price', '>', 0)
                    ->whereIn('pricelist_id', $reseller_pricelist_ids)
                    ->whereIn('product_id', $product_ids)
                    ->update($cost_update_data);
            }
        }
    }

    foreach ($discounts as $discount) {
        $update_data = [];
        if ($discount->type == 'Product' && $discount->product_id) {

            $update_fields = [];

            if (in_array($discount->customer_type, ['Both', 'Customer'])) {
                if ($discount->quantity != 1) {
                    $update_fields[] = 'price_tax_'.$discount->quantity;
                }
            }
            if (in_array($discount->customer_type, ['Both', 'Reseller'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'reseller_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'reseller_price_tax_12';
                }
            }

            if (in_array($discount->customer_type, ['Both', 'Wholesale'])) {
                if ($discount->quantity == 1) {
                    $update_fields[] = 'wholesale_price_tax';
                }
                if ($discount->quantity == 12) {
                    $update_fields[] = 'wholesale_price_tax_12';
                }
            }
            foreach ($update_fields as $update_field) {
                $update_data[$update_field] = \DB::raw('price_tax-((price_tax/100)*'.$discount->percentage_discount.')');
            }

            \DB::table('crm_pricelist_items')
                ->where('price_tax', '>', 0)
                ->whereIn('pricelist_id', $admin_pricelist_ids)
                ->where('product_id', $discount->product_id)
                ->update($update_data);
            foreach ($update_data as $k => $v) {
                $k = str_replace('price_tax', 'cost_price', $k);
                $cost_update_data[$k] = $v;
            }

            unset($cost_update_data['wholesale_cost_price']);
            unset($cost_update_data['wholesale_cost_price_12']);

            if (count($cost_update_data) > 0) {
                \DB::table('crm_pricelist_items')
                    ->where('cost_price', '>', 0)
                    ->whereIn('pricelist_id', $reseller_pricelist_ids)
                    ->where('product_id', $discount->product_id)
                    ->update($cost_update_data);
            }
        }
    }

    $admin_pricelist_items = \DB::table('crm_pricelist_items')
        ->where('pricelist_id', 1)
        ->get();
    foreach ($admin_pricelist_items as $admin_pricelist_item) {
        \DB::table('crm_pricelist_items')
            ->whereNotIn('pricelist_id', $admin_pricelist_ids)
            ->where('product_id', $admin_pricelist_item->product_id)
            ->update(['cost_price' => $admin_pricelist_item->price_tax]);
    }

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->whereRaw('reseller_price_tax < cost_price*1.15')
        ->update(['reseller_price_tax' => \DB::raw('price_tax')]);

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->whereRaw('reseller_price_tax_12 <= cost_price')
        ->update(['reseller_price_tax_12' => \DB::raw('reseller_price_tax')]);

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->whereRaw('price_tax_6 < cost_price*1.15')
        ->update(['price_tax_6' => \DB::raw('price_tax')]); // set to retail if less than cost - reseller_price_tax is reseller pricing

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->whereRaw('price_tax_12 < cost_price*1.15')
        ->update(['price_tax_12' => \DB::raw('price_tax_6')]);

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->whereRaw('price_tax_24 < cost_price*1.15')
        ->update(['price_tax_24' => \DB::raw('price_tax_12')]);

    \DB::table('crm_pricelist_items')
        ->where('pricelist_id', 1)
        ->update([
            'price_tax_6' => \DB::raw('ROUND(price_tax_6)'),
            'price_tax_12' => \DB::raw('ROUND(price_tax_12)'),
            'price_tax_24' => \DB::raw('ROUND(price_tax_24)'),
            'reseller_price_tax' => \DB::raw('ROUND(reseller_price_tax)'),
            'reseller_price_tax_12' => \DB::raw('ROUND(reseller_price_tax_12)'),
            'wholesale_price_tax' => \DB::raw('ROUND(wholesale_price_tax)'),
            'wholesale_price_tax_12' => \DB::raw('ROUND(wholesale_price_tax_12)'),
            'reseller_cost_price' => \DB::raw('ROUND(reseller_cost_price)'),
            'cost_price_6' => \DB::raw('ROUND(cost_price_6)'),
            'cost_price_12' => \DB::raw('ROUND(cost_price_12)'),
            'cost_price_24' => \DB::raw('ROUND(cost_price_24)'),

        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('cost_price', '>', 0)
        ->update([
            'markup' => \DB::raw('(price_tax - cost_price) * 100 / cost_price'),
            'markup_6' => \DB::raw('(price_tax_6 - cost_price) * 100 / cost_price'),
            'markup_12' => \DB::raw('(price_tax_12 - cost_price) * 100 / cost_price'),
            'markup_24' => \DB::raw('(price_tax_24 - cost_price) * 100 / cost_price'),
            'reseller_markup' => \DB::raw('(reseller_price_tax - cost_price) * 100 / cost_price'),
            'reseller_markup_12' => \DB::raw('(reseller_price_tax_12 - cost_price) * 100 / cost_price'),
            'wholesale_markup' => \DB::raw('(wholesale_price_tax - cost_price) * 100 / cost_price'),
            'wholesale_markup_12' => \DB::raw('(wholesale_price_tax_12 - cost_price) * 100 / cost_price'),
        ]);

    // FALLBACK TO PREVIOUS PRICE IF MARKUP LESS 15%
    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('reseller_markup', '<', 15)
        ->where('reseller_price_tax', '>', 0)
        ->update(['reseller_price_tax' => \DB::raw('price_tax')]);

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('reseller_markup_12', '<', 15)
        ->where('reseller_price_tax_12', '>', 0)
        ->update(['reseller_price_tax_12' => \DB::raw('reseller_price_tax')]);

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('markup_6', '<', 15)
        ->where('price_tax_6', '>', 0)
        ->update(['price_tax_6' => \DB::raw('price_tax')]); // set to retail if less than cost - reseller_price_tax is reseller pricing

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('markup_12', '<', 15)
        ->where('price_tax_12', '>', 0)
        ->update(['price_tax_12' => \DB::raw('price_tax_6')]);

    \DB::table('crm_pricelist_items')
        ->where('cost_price', '>', 0)
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('markup_24', '<', 15)
        ->where('price_tax_24', '>', 0)
        ->update(['price_tax_24' => \DB::raw('price_tax_12')]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('cost_price', '>', 0)
        ->update([
            'markup' => \DB::raw('(price_tax - cost_price) * 100 / cost_price'),
            'markup_6' => \DB::raw('(price_tax_6 - cost_price) * 100 / cost_price'),
            'markup_12' => \DB::raw('(price_tax_12 - cost_price) * 100 / cost_price'),
            'markup_24' => \DB::raw('(price_tax_24 - cost_price) * 100 / cost_price'),
            'reseller_markup' => \DB::raw('(reseller_price_tax - cost_price) * 100 / cost_price'),
            'reseller_markup_12' => \DB::raw('(reseller_price_tax_12 - cost_price) * 100 / cost_price'),
            'wholesale_markup' => \DB::raw('(wholesale_price_tax - cost_price) * 100 / cost_price'),
            'wholesale_markup_12' => \DB::raw('(wholesale_price_tax_12 - cost_price) * 100 / cost_price'),
        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('price_tax_6', 0)
        ->update([
            'markup_6' => 0,
        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('price_tax_12', 0)
        ->update([
            'markup_12' => 0,
        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('price_tax_24', 0)
        ->update([
            'markup_24' => 0,
        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('reseller_price_tax', 0)
        ->update([
            'reseller_markup' => 0,
        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('reseller_price_tax_12', 0)
        ->update([
            'reseller_markup_12' => 0,
        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('wholesale_price_tax', 0)
        ->update([
            'wholesale_markup' => 0,
        ]);

    \DB::table('crm_pricelist_items')
        ->whereIn('pricelist_id', $admin_pricelist_ids)
        ->where('wholesale_price_tax_12', 0)
        ->update([
            'wholesale_markup_12' => 0,
        ]);

    set_reseller_pricing();
    // set to .99 pricing
    $airtime_product_ids = get_activation_type_product_ids('airtime_prepaid');
    $airtime_contract_ids = get_activation_type_product_ids('airtime_contract');
    $items = \DB::table('crm_pricelist_items')->whereIn('pricelist_id', $admin_pricelist_ids)->get();
    foreach ($items as $item) {
        if (! in_array($item->product_id, $airtime_product_ids) && ! in_array($item->product_id, $airtime_contract_ids)) {
            $data = [];
            foreach ($item as $k => $v) {
                if ($k == 'price_tax') {
                    continue;
                }

                if (str_contains($k, 'price_tax')) {
                    $data[$k] = round_price($v);
                }
            }
            if (count($data) > 0) {

                \DB::table('crm_pricelist_items')->where('id', $item->id)->update($data);
            }
        }
    }
}

function set_reseller_pricing()
{

    $products = \DB::table('crm_products')->where('status', 'Enabled')->get();

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
            if (empty($cost_price)) {
                $cost_price = 0;
            }

            $markup = $partner_pricelist->default_markup;

            $price = $cost_price + (($cost_price / 100) * $markup);

            $data = [
                'product_id' => $product->id,
                'pricelist_id' => $partner_pricelist->id,
                'cost_price' => $cost_price,
                'reseller_cost_price' => $cost_price,
                'markup' => $markup,
                'price' => $price,
                'price_tax' => $price * 1.15,
            ];

            if (! $pricelist_item_exists) {

                \DB::table('crm_pricelist_items')->insert($data);
            } elseif ($cost_price > $pricelist_item->price) {
                \DB::table('crm_pricelist_items')
                    ->where('pricelist_id', $partner_pricelist->id)
                    ->where('product_id', $product->id)->update($data);
            } else {
                // update reseller costprice and markup
                $markup = intval(($cost_price > 0) ? ($pricelist_item->price - $cost_price) * 100 / $cost_price : 0);
                $data = [
                    'cost_price' => $cost_price,
                    'markup' => $markup,
                    'wholesale_markup' => 0,
                ];
                try {
                    \DB::table('crm_pricelist_items')
                        ->where('product_id', $product->id)
                        ->where('pricelist_id', $partner_pricelist->id)
                        ->update($data);
                } catch (\Throwable $ex) {
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
}
