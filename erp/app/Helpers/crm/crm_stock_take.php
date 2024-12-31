<?php

function set_stock_take_diff()
{
    \DB::table('crm_stock_take_details')->update(['qty_diff' => 0]);
    \DB::table('crm_stock_take_details')->update(['stock_value_difference' => 0]);

    \DB::table('crm_stock_take_details')->whereRaw('quantity=new_quantity')->update(['qty_diff' => 0]);
    \DB::table('crm_stock_take_details')->whereRaw('quantity=new_quantity')->update(['stock_value_difference' => 0]);
    \DB::table('crm_stock_take_details')->whereRaw('quantity!=new_quantity')->update(['qty_diff' => \DB::raw('new_quantity-quantity')]);
    \DB::table('crm_stock_take_details')->whereRaw('quantity!=new_quantity')->update(['stock_value_difference' => \DB::raw('new_stock_value-stock_value')]);

    $sql = 'UPDATE crm_stock_take_details 
    JOIN crm_products ON crm_stock_take_details.product_id=crm_products.id
    SET crm_stock_take_details.product_category_id=crm_products.product_category_id, crm_stock_take_details.sort_order=crm_products.sort_order';
    \DB::statement($sql);
}

function aftersave_stock_take_generate_lines($request)
{

    if (! empty($request->new_record)) {

        $stock_take = \DB::table('crm_stock_take')->where('id', $request->id)->get()->first();

        $products = \DB::table('crm_products')
            ->select('qty_on_hand', 'stock_value', 'id', 'product_category_id')
            ->where('type', 'Stock')
            ->where('frequency', 'once off')
            ->where('status', '!=', 'Deleted')
            ->orderBy('sort_order')
            ->get();

        foreach ($products as $product) {
            $data = [
                'stock_take_id' => $stock_take->id,
                'created_at' => $stock_take->created_at,
                'created_by' => $stock_take->created_by,
                'product_id' => $product->id,
                'product_category_id' => $product->product_category_id,
                'quantity' => $product->qty_on_hand,
                'stock_value' => $product->stock_value,
            ];

            \DB::table('crm_stock_take_details')->insert($data);
        }
    }

}

function afterdelete_stock_take_delete_adjustments($request)
{

    $line_ids = \DB::table('crm_stock_take_details')->where('stock_take_id', $request->id)->pluck('id')->toArray();
    $product_ids = \DB::table('crm_stock_take_details')->where('stock_take_id', $request->id)->pluck('product_id')->toArray();
    \DB::table('acc_inventory')->whereIn('stock_take_detail_id', $line_ids)->delete();
    \DB::table('crm_stock_take_details')->where('stock_take_id', $request->id)->delete();

    $erp = new DBEvent;
    $erp->setTable('acc_inventory');
    $erp->setStockBalance($product_ids);
}

function beforedelete_stock_take_detail_check($request)
{
    return 'Line cannot be deleted';
}

function beforesave_stock_take_detail_check($request)
{
    if (! empty($request->new_record)) {
        return 'Line cannot be added';
    }
}

function aftersave_stock_take_create_adjustments($request) {}

function aftersave_stock_take_line_update_header($request)
{

    $stock_take = \DB::table('crm_stock_take')->where('id', $request->stock_take_id)->get()->first();
    set_stock_take_diff();

    $adjustments = \DB::table('crm_stock_take_details')
        ->where('stock_take_id', $request->stock_take_id)
        ->sum('stock_value_difference');
    \DB::table('crm_stock_take')->where('id', $stock_take->id)->update(['adjustments' => $adjustments]);

    $total_lines = \DB::table('crm_stock_take_details')->where('stock_take_id', $request->stock_take_id)->count();

}

function button_stock_take_submit_approval($request)
{
    $stock_take = \DB::table('crm_stock_take')->where('id', $request->id)->get()->first();

    if ($stock_take->approved) {
        return json_alert('Stock take already approved');
    }

    if (is_superadmin() && ! is_dev()) {
        \DB::table('crm_stock_take')->where('id', $request->id)->update(['approved' => 1]);

        return json_alert('Stock take approved');
    } else {

        $data = [
            'module_id' => 1939,
            'row_id' => $stock_take->id,
            'title' => 'Stock take '.$stock_take->stock_take_date,
            'processed' => 0,
            'requested_by' => get_user_id_default(),
        ];
        $result = (new \DBEvent)->setTable('crm_approvals')->save($data);

        return json_alert('Stock take submitted for approval');
    }
}

function schedule_process_stock_take()
{

    $stock_take = \DB::table('crm_stock_take')->where('approved', 1)->where('processed', 0)->get()->first();
    if (! $stock_take || ! $stock_take->id) {
        return false;
    }
    $stock_take_details = \DB::table('crm_stock_take_details')->where('stock_take_id', $stock_take->id)->get();

    $inventory_ids = \DB::table('acc_inventory')->where('stock_take_id', $stock_take->id)->pluck('id')->toArray();

    if (count($stock_take_details) == count($inventory_ids)) {
        \DB::table('crm_stock_take')->where('id', $stock_take->id)->update(['processed' => 1]);
    }

    // reset stock take
    //\DB::table('crm_approvals')->where('module_id',703)->whereIn('row_id',$inventory_ids)->delete();
    //\DB::table('acc_inventory')->whereIn('id',$inventory_ids)->delete();

    $processed_ids = \DB::table('acc_inventory')->where('stock_take_id', $stock_take->id)->pluck('stock_take_detail_id')->toArray();
    $to_process_stock_take_details = \DB::table('crm_stock_take_details')->where('stock_take_id', $stock_take->id)->whereNotIn('id', $processed_ids)->limit(4)->get();

    $products = \DB::table('crm_products')->get();
    foreach ($to_process_stock_take_details as $stock_take_detail) {
        $product = $products->where('id', $stock_take_detail->product_id)->first();
        if (! $product || ! $product->id) {
            continue;
        }
        $e = \DB::table('acc_inventory')->where('stock_take_detail_id', $stock_take_detail->id)->where('stock_take_id', $stock_take_detail->stock_take_id)->count();
        if (! $e) {
            $db = new DBEvent;
            $db->setTable('acc_inventory');
            $data = [
                'product_id' => $product->id,
                'docdate' => date('Y-m-d H:i:s', strtotime($stock_take->stock_take_date)),
                'doctype' => 'Inventory',
                'qty_current' => $product->qty_on_hand,
                'qty_new' => $stock_take_detail->new_quantity,
                'cost_current' => $product->cost_price,
                'cost_new' => $product->cost_price,
                'user_id' => session('user_id'),
                'approved' => 1,
                'stock_take_detail_id' => $stock_take_detail->id,
                'stock_take_id' => $stock_take_detail->stock_take_id,
            ];

            $result = $db->save($data);

            if ($result && $result['id']) {
                $row = \DB::table('acc_inventory')->where('id', $result['id'])->get()->first();

            } else {
            }

            $stock_data = get_stock_balance($stock_take_detail->product_id);
            $new_stock_value = $stock_data['qty_on_hand'] * $stock_data['cost_price'];
            \DB::table('crm_stock_take_details')->where('id', $stock_take_detail->id)->update(['new_stock_value' => $new_stock_value]);
        }

    }
}
