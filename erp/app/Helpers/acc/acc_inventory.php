<?php


function get_inventory_cost_current($row = false)
{
    $row = (object) $row;
    if($row && isset($row->cost_current)){
        return $row->cost_current;
    }else if (!empty(request()->product_id)) {
        $product = \DB::table('crm_products')->where('id', request()->product_id)->get()->first();
        return $product->cost_price;
    }else{
        return 0;    
    }
}

function get_inventory_qty_current($row = false)
{

    $row = (object) $row;
    if($row && isset($row->qty_current)){
        return $row->qty_current;
    }else if (!empty(request()->product_id)) {
        $product = \DB::table('crm_products')->where('id', request()->product_id)->get()->first();
        return $product->qty_on_hand;
    }else{
        return 0;    
    }
}

function get_inventory_cost_new($row = false)
{
    $row = (object) $row;
    if($row && isset($row->cost_new)){
        return $row->cost_new;
    }else if (!empty(request()->product_id)) {
        $product = \DB::table('crm_products')->where('id', request()->product_id)->get()->first();
        return $product->cost_price;
    }else{
        return 0;    
    }
}

function get_inventory_qty_new($row = false)
{

    $row = (object) $row;
    if($row && isset($row->qty_new)){
        return $row->qty_new;
    }else if (!empty(request()->product_id)) {
        $product = \DB::table('crm_products')->where('id', request()->product_id)->get()->first();
        return $product->qty_on_hand;
    }else{
        return 0;    
    }
}


function beforesave_inventory_check_qty($request){
    $type = \DB::table('crm_products')->where('id', $request->product_id)->pluck('type')->first();
    $is_subscription = \DB::table('crm_products')->where('id', $request->product_id)->pluck('is_subscription')->first();
    if($type == 'Stock' && !$is_subscription){
        if($request->qty_new < 0){
            return 'Qty on hand cannot be set below zero';    
        }
    }
}

function ajax_inventory_get_stock_val($request)
{
    if (!empty($request->product_id)) {
        $product = \DB::table('crm_products')->where('id', $request->product_id)->get()->first();
        $response = ['cost_current' => $product->cost_price,'qty_current' => $product->qty_on_hand,'cost_new' => $product->cost_price,'qty_new' => $product->qty_on_hand];
     
        return $response;
    }
}

function rebuild_inventory_totals($product_id = false)
{
    $system_user_id = get_system_user_id();
    if (!$product_id) {
        $stock_product_ids = \DB::table('crm_products')->where('type', 'Stock')->pluck('id')->toArray();
    } else {
        $stock_product_ids = [$product_id];
    }

    // update ledger totals

    foreach ($stock_product_ids as $stock_product_id) {
        $product_type = \DB::table('crm_products')->where('id', $stock_product_id)->pluck('type')->first();
        $qty_current = 0;
        $qty_new = 0;
        if(isset($stock_balance)){
            unset($stock_balance);    
        }
        if(isset($cost_current)){
            unset($cost_current);    
        }
        if(isset($cost_new)){
            unset($cost_new);    
        }
        if(isset($total)){
            unset($total);    
        }
        
        $inventory_records = \DB::table('acc_inventory')->where('product_id', $stock_product_id)->where('approved',0)->orderBy('docdate', 'asc')->orderBy('id', 'asc')->get();
        foreach ($inventory_records as $record) {
            if($record->cost_change > 0){
                $data = ['cost_new' => $record->cost_change];
                \DB::table('acc_inventory')->where('id', $record->id)->update($data);
            }
        }
        $inventory_records = \DB::table('acc_inventory')->where('product_id', $stock_product_id)->where('approved',1)->orderBy('docdate', 'asc')->orderBy('id', 'asc')->get();
        $cost_current = 0;
        $cost_new = 0;
        $qty_current = 0;
        foreach ($inventory_records as $record) {
            $stock_balance = get_stock_balance($record->product_id, $record->docdate, $record->id);

           
            $cost_current = $stock_balance['cost_price'];
           
            
            
          
            $qty_new = $record->qty_new;
           
            
            if(!empty($record->cost_new)){
                $cost_new = $record->cost_new;
            }else{
                $cost_new = $cost_current;
            }
            
            $cost_diff = $cost_new - $cost_current;
            
            $qty_diff =  abs($qty_current - $qty_new);
            if($qty_new == 0 && $qty_current < 0){
                $qty_diff = $qty_current*-1;
                $r =4;
            }elseif($qty_new < 0){
                $r = 1;
                $qty_diff = $qty_diff * -1;    
            }elseif($qty_new < $qty_current){
                $r = 2;
                $qty_diff = $qty_diff * -1;    
            }elseif($qty_new > $qty_current){
                $r = 3;
                $qty_diff = $qty_new - $qty_current;    
            }
            if($record->id == 80209){
            }
            $total = 0;
            if($record->zero_cost){
              
                $total = currency(($cost_current*-1) * $qty_new);
            }elseif($qty_diff!=0){
               
                $total = currency($cost_new * $qty_diff);
            }elseif($cost_diff!=0){
              
                $total = currency($cost_diff * $qty_new);
            }
            
            
            
            $data = ['qty_current' => $qty_current, 'qty_change' => $qty_diff, 'cost_current' => $cost_current, 'cost_change' => $cost_diff, 'total' => $total];
          
           
            
            \DB::table('acc_inventory')->where('id', $record->id)->update($data);
            $qty_current = $qty_new;
            
        }
        
        
        $inventory_records = \DB::table('acc_inventory')->where('product_id', $stock_product_id)->where('approved',0)->orderBy('docdate', 'asc')->orderBy('id', 'asc')->get();
        foreach ($inventory_records as $record) {
            if($record->zero_cost){
                $data = ['cost_new' => 0];
                \DB::table('acc_inventory')->where('id', $record->id)->update($data);
            }elseif($record->cost_change > 0){
                $data = ['cost_new' => $record->cost_current];
                \DB::table('acc_inventory')->where('id', $record->id)->update($data);
            }else{
                $data = ['cost_new' => $record->cost_change];
                \DB::table('acc_inventory')->where('id', $record->id)->update($data);
            }
        }
    }
}


function beforesave_inventory_field_change_check($request)
{
    if (empty($request->qty_new) && empty($request->cost_new)) {
       // return 'Qty or Cost change required.';
    }
}
