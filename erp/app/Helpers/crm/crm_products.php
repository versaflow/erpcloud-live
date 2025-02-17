<?php

function button_pricelist_update_sort_order($request){
    update_products_and_categories_sort();
    return json_alert('Done');
}

function update_products_and_categories_sort(){
    $enabled_product_categories = \DB::table('crm_product_categories')->where('is_deleted', 0)->orderBy('sort_order')->pluck('id')->toArray();
    $disabled_product_categories = \DB::table('crm_product_categories')->where('is_deleted', 1)->orderBy('sort_order')->pluck('id')->toArray();

    $i = 0;
    foreach ($enabled_product_categories as $category) {
        $i++;
        \DB::table('crm_product_categories')->where('id', $category)->update(['sort_order' => $i]);
    }
    
    foreach ($disabled_product_categories as $category) {
        $i++;
        \DB::table('crm_product_categories')->where('id', $category)->update(['sort_order' => $i]);
    }

    $i = 0;
    
    $storefront_ids = \DB::table('crm_product_categories')->orderby('sort_order')->pluck('storefront_id')->filter()->unique()->toArray();

    $sort_order = 0;
    
    if (!empty($storefront_ids) && count($storefront_ids) > 0) {
        foreach ($storefront_ids as $storefront_id) {
            $departments = \DB::table('crm_product_categories')->where('storefront_id',$storefront_id)->orderby('sort_order')->pluck('department')->filter()->unique()->toArray();
  
            if (!empty($departments) && count($departments) > 0) {
                foreach ($departments as $department) {
                    $fields = \DB::table('crm_product_categories')->select('id')->where('storefront_id',$storefront_id)->where('department', $department)->orderby('sort_order')->get();
                    
                    foreach ($fields as $field) {
                        \DB::table('crm_product_categories')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                        ++$sort_order;
                    }
                }
                $fields = \DB::table('crm_product_categories')->where('storefront_id',$storefront_id)->where('department', '')->orderby('sort_order')->get();
                
                foreach ($fields as $field) {
                    \DB::table('crm_product_categories')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    ++$sort_order;
                }
            }
        }
        $departments = \DB::table('crm_product_categories')->where('storefront_id',0)->orderby('sort_order')->pluck('department')->filter()->unique()->toArray();
   
        if (!empty($departments) && count($departments) > 0) {
            foreach ($departments as $department) {
                $fields = \DB::table('crm_product_categories')->select('id')->where('storefront_id',0)->where('department', $department)->orderby('sort_order')->get();
                
                foreach ($fields as $field) {
                    \DB::table('crm_product_categories')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                    ++$sort_order;
                }
            }
            $fields = \DB::table('crm_product_categories')->where('storefront_id',0)->where('department', '')->orderby('sort_order')->get();
            
            foreach ($fields as $field) {
                \DB::table('crm_product_categories')->where('id', $field->id)->update(['sort_order' => $sort_order]);
                ++$sort_order;
            }
        }
    }
    
    
    $i = 0;
    
        
    $products = \DB::table('crm_products')
    ->select('crm_products.id')
    ->join('crm_product_categories','crm_product_categories.id','=','crm_products.product_category_id')
    ->where('crm_product_categories.is_deleted', 0)
    ->orderBy('crm_product_categories.sort_order')
    ->orderBy('crm_products.sort_order')->pluck('crm_products.id')->toArray();
    
    foreach ($products as $product) {
        $i++;
        \DB::table('crm_products')->where('id', $product)->update(['sort_order' => $i]);
    }
    
    $sql = "UPDATE crm_pricelist_items 
    JOIN crm_products ON crm_pricelist_items.product_id=crm_products.id
    SET crm_pricelist_items.sort_order = crm_products.sort_order";
    \DB::statement($sql);
}

function aftersave_set_airtime_provision_package($request){
    if(empty($request->new_record)){
        $airtime_prepaid_ids = get_activation_type_product_ids('airtime_prepaid');
        if(in_array($request->id,$airtime_prepaid_ids)){
            \DB::table('crm_products')->whereIn('id',$airtime_prepaid_ids)->where('status','Enabled')->update(['provision_package'=>\DB::raw('selling_price_incl')]);
        }
        
        $airtime_contract_ids = get_activation_type_product_ids('airtime_contract');
        if(in_array($request->id,$airtime_contract_ids)){
            \DB::table('crm_products')->whereIn('id',$airtime_contract_ids)->where('status','Enabled')->update(['provision_package'=>\DB::raw('selling_price_incl')]);
        }
    }
}

function beforesave_products_activation_required($request){
    if($request->type == 'Service' && empty($request->provision_plan_id)){
        return 'Activation plan required';    
    }    
}



function set_product_marketing_prices($product_id = false)
{
  
  /*
    $round_prices = get_admin_setting('round_prices');
    

    if (!$round_prices) {
        return false;
    }

    $remove_tax_fields = \DB::connection($conn)->table('erp_admin_settings')->where('id', 1)->pluck('remove_tax_fields')->first();
    if ($remove_tax_fields) {
        return false;
    }
    $conn = $instance->db_connection;
    if (session('instance')->db_connection == $conn) {
        $conn = 'default';
    }

    if (!$product_id) {
        $product_ids = \DB::connection($conn)->table('crm_products')->pluck('id')->toArray();
    } else {
        $product_ids = [$product_id];
    }
   
  
    // ROUND ALL PRICELISTS
    $pricelist_ids = \DB::connection($conn)->table('crm_pricelists')->where('partner_id', 1)->where('currency', 'ZAR')->pluck('id')->toArray();
    $pricelist_items = \DB::connection($conn)->table('crm_pricelist_items')->whereIn('pricelist_id', $pricelist_ids)->whereIn('product_id', $product_ids)->get();
    foreach ($pricelist_items as $pricelist_item) {
        $data = [];

       
        $data['price_tax'] = round_price($pricelist_item->price_tax);
        $data['price'] = $data['price_tax']/1.15;
        if (count($data) > 0) {
            \DB::connection($conn)->table('crm_pricelist_items')->where('id', $pricelist_item->id)->update($data);
        }
    }
    */
}

function set_product_marketing_prices_all($all_connections = false, $product_id = false)
{
    if ($all_connections) {
        $instance_connections = DB::connection('system')->table('erp_instances')->where('installed',1)->get();
    } else {
        $instance_connections = DB::connection('system')->table('erp_instances')->where('installed',1)->where('id', session('instance')->id)->get();
    }

    foreach ($instance_connections as $instance) {
       
        $conn = $instance->db_connection;
        if (session('instance')->db_connection == $conn) {
            $conn = 'default';
        }
        $round_prices = \DB::connection($conn)->table('erp_admin_settings')->where('id', 1)->pluck('round_prices')->first();
        if ($round_prices) {
            continue;
        }

        $remove_tax_fields = \DB::connection($conn)->table('erp_admin_settings')->where('id', 1)->pluck('remove_tax_fields')->first();
        if ($remove_tax_fields) {
            continue;
        }

        if (!$product_id) {
            $product_ids = \DB::connection($conn)->table('crm_products')->pluck('id')->toArray();
        } else {
            $product_ids = [$product_id];
        }
       
      
        // ROUND ALL PRICELISTS
        $pricelist_ids = \DB::connection($conn)->table('crm_pricelists')->where('partner_id', 1)->where('currency', 'ZAR')->pluck('id')->toArray();
        $pricelist_items = \DB::connection($conn)->table('crm_pricelist_items')->whereIn('pricelist_id', $pricelist_ids)->whereIn('product_id', $product_ids)->get();
        foreach ($pricelist_items as $pricelist_item) {
            $data = [];

           
            $data['price_tax'] = round_price($pricelist_item->price_tax);
            $data['price'] = $data['price_tax']/1.15;
            if (count($data) > 0) {
                \DB::connection($conn)->table('crm_pricelist_items')->where('id', $pricelist_item->id)->update($data);
            }
        }
    }
}


function button_products_delete_stock($request){
   
    $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
    if(!$product || !$product->id){
    return json_alert('Product not found','error');    
    }
    $db = new DBEvent;
    $db->setTable('acc_inventory');
    $data = [
        'product_id' => $product->id,
        'docdate' => date('Y-m-d H:i:s'),
        'doctype' => 'Inventory',
        'qty_current' => $product->qty,
        'qty_new' => 0,
        'cost_current' => $product->cost_price,
        'cost_new' => $product->cost_price,
        'user_id' => session('user_id'),
        'approved' => 0,    
    ];
 
    $result = $db->save($data);
    
   
    if($result && $result['id']){
          $row = \DB::table('acc_inventory')->where('id',$result['id'])->get()->first();
   
        return json_alert('Done');    
    }
    
   
    return $result;
}

function aftersave_set_provision_type_activations($request)
{
    \DB::table('crm_products')->where('frequency','monthly')->update(['is_subscription'=>1]);
    $product = dbgetrow('crm_products', 'id', $request->id);
    $provision_type = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();

    if (empty($provision_type)) {
        $provision_type = '';
    }


    $activation_ids = \DB::table('sub_activations')
    ->where('status_provision_type',0)
    ->where('subscription_id', 0)
    ->where('status', 'Pending')
    ->where('provision_type', '!=', $provision_type)
    ->where('product_id', $product->id)
    ->pluck('id')->toArray();
    
    \DB::table('sub_activations')
    ->whereIn('id', $activation_ids)
    ->update(['step'=> 1,'provision_type' => $provision_type]);
    
    \DB::table('sub_activation_steps')
    ->whereIn('provision_id', $activation_ids)
    ->where('service_table', 'sub_activations')->delete();
}


function button_products_provision($request)
{
    $activations_url = get_menu_url_from_table('sub_activation_types');
    $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
    $activation_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();

    return redirect()->to($activations_url.'?name='.$activation_name);
}


/// BEFORESAVE
function beforedelete_products_check_in_use($request)
{
    $subs = \DB::table('sub_services')->where('product_id', $request->id)->where('status', '!=', 'Deleted')->count();
    if ($subs > 0) {
        return 'Product cannot be deleted. Product has active subscriptions.';
    }

    $in_package = \DB::table('crm_product_bundle_activations')->where('product_id', $request->id)->count();
    if ($in_package) {
        return 'Product cannot be deleted. Product is active in product bundle.';
    }

    $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
    if ('Stock' == $product->type && 0 != $product->qty_on_hand) {
        return 'Cannot delete a product with stock on hand.';
    }
}




function beforesave_fix_code($request)
{
    request()->merge(['code' => str_replace(['_',' '], '-', strtoupper($request->code))]);
    if (!empty($request->id)) {
        $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
        $exists = \DB::table('crm_products')->where('id', '!=', $request->id)->where('code', $request->code)->count();
        if ($exists) {
            return 'Product code already in use';
        }
    } else {
        $exists = \DB::table('crm_products')->where('code', $request->code)->count();
        if ($exists) {
            return 'Product code already in use';
        }
    }
}





/// AFTERSAVE

function aftersave_products_stock_to_service_remove_stock($request)
{
    $beforesave_row = session('event_db_record');
    if($beforesave_row->type == 'Stock' && $request->type == 'Service'){
        
        $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
        if($product->qty_on_hand > 0){
            $system_user_id = get_system_user_id();
            $data = [
                'docdate' => date('Y-m-d H:i:s'),
                'cost_current' => $product->cost_price,
                'cost_new' => $product->cost_price,
                'qty_current' => $product->qty_on_hand,
                'qty_change' => $product->qty_on_hand*-1,    
                'qty_new' => 0,  
                'product_id' => $product->id,  
                'doctype' => 'Inventory',
                'user_id' => $system_user_id,  
                'approved' => 0,
            ];
            \DB::table('acc_inventory')->insert($data);
        }
    }
}

function aftersave_products_update_subscription_prices($request)
{
    $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
    $sub = new ErpSubs();
    $sub->updateProductPrices($product->id);
}



function aftersave_products_update_cost_prices($request)
{
    $beforesave_row = session('event_db_record');
    if(empty($request->cost_price)){
        $request->cost_price = 0;
    }
    $cost_price = currency($request->cost_price);
 
    if(empty($cost_price)){
        $cost_price= 0;
    }

    if (!empty($cost_price) && currency($beforesave_row->cost_price) != $cost_price) {
        
        $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
        $db = new DBEvent;
        $db->setTable('acc_inventory');
        $cost_price = (float) $cost_price;
        $data = [
            'docdate' => date('Y-m-d H:i:s'),
            'cost_current' => $beforesave_row->cost_price,
            'cost_new' => $cost_price,
            'qty_current' => $product->qty_on_hand,
            'qty_change' => 0,    
            'approved' => 1,    
            'qty_new' => $product->qty_on_hand,
            'product_id' => $product->id,  
            'doctype' => 'Inventory',
        ];
      
        if(empty($cost_price) || $cost_price == 0){
            $data['zero_cost'] = 1;
        }
      
        $result = $db->save($data);
     
        if (!is_array($result) || empty($result['id'])) {
            return json_alert('Cost Price adjustment could not be saved','error');
        }
    }
    \DB::connection('default')->table('crm_products')->where('cost_price',0)->update(['cost_price_incl'=>0]);
    \DB::connection('default')->table('crm_products')->where('cost_price','>',0)->update(['cost_price_incl'=>\DB::raw('cost_price*1.15')]);
}




function get_product_inventory_history($product_id, $type = 'Manual')
{
    if($type == 'Manual'){
        $lines = \DB::table('acc_inventory')
        ->select('acc_inventory.*','crm_documents.doctype as customer_doctype','crm_supplier_documents.doctype as supplier_doctype')
        ->leftJoin('crm_documents','acc_inventory.document_id','=','crm_documents.id')
        ->leftJoin('crm_supplier_documents','acc_inventory.supplier_document_id','=','crm_supplier_documents.id')
        ->where('document_id',0)
        ->where('supplier_document_id',0)
        ->where('acc_inventory.product_id',$product_id)->orderBy('acc_inventory.docdate','desc')->orderBy('acc_inventory.id','desc')->limit(10)->get();
    }
    if($type == 'Invoice'){
        $lines = \DB::table('acc_inventory')
        ->select('acc_inventory.*','crm_documents.doctype as customer_doctype','crm_supplier_documents.doctype as supplier_doctype')
        ->leftJoin('crm_documents','acc_inventory.document_id','=','crm_documents.id')
        ->leftJoin('crm_supplier_documents','acc_inventory.supplier_document_id','=','crm_supplier_documents.id')
        ->where('document_id','>',0)
        ->where('acc_inventory.product_id',$product_id)->orderBy('acc_inventory.docdate','desc')->orderBy('acc_inventory.id','desc')->limit(10)->get();
    }
    if($type == 'Supplier Invoice'){
        $lines = \DB::table('acc_inventory')
        ->select('acc_inventory.*','crm_documents.doctype as customer_doctype','crm_supplier_documents.doctype as supplier_doctype')
        ->leftJoin('crm_documents','acc_inventory.document_id','=','crm_documents.id')
        ->leftJoin('crm_supplier_documents','acc_inventory.supplier_document_id','=','crm_supplier_documents.id')
        ->where('supplier_document_id','>',0)
        ->where('acc_inventory.product_id',$product_id)->orderBy('acc_inventory.docdate','desc')->orderBy('acc_inventory.id','desc')->limit(10)->get();
    }
    return $lines;
}

function get_product_qty_history($product_id)
{
   
    $lines = \DB::table('acc_inventory')
    ->select('acc_inventory.*','crm_documents.doctype as customer_doctype','crm_supplier_documents.doctype as supplier_doctype')
    ->leftJoin('crm_documents','acc_inventory.document_id','=','crm_documents.id')
    ->leftJoin('crm_supplier_documents','acc_inventory.supplier_document_id','=','crm_supplier_documents.id')
    ->where('qty_change','!=',0)
    ->where('acc_inventory.product_id',$product_id)->orderBy('acc_inventory.docdate','desc')->orderBy('acc_inventory.id','desc')->limit(20)->get();
   
    return $lines;
}

function get_product_cost_history($product_id)
{
   
    $lines = \DB::table('acc_inventory')
    ->select('acc_inventory.*','crm_documents.doctype as customer_doctype','crm_supplier_documents.doctype as supplier_doctype')
    ->leftJoin('crm_documents','acc_inventory.document_id','=','crm_documents.id')
    ->leftJoin('crm_supplier_documents','acc_inventory.supplier_document_id','=','crm_supplier_documents.id')
    ->where('cost_change','!=',0)
    ->where('acc_inventory.product_id',$product_id)->orderBy('acc_inventory.docdate','desc')->orderBy('acc_inventory.id','desc')->limit(20)->get();
   
    return $lines;
}


/// BUTTONS
function button_products_inventory_history($request)
{
    $documents_url = get_menu_url_from_table('crm_documents');
    $supplier_documents_url = get_menu_url_from_table('crm_supplier_documents');
    $inventory_url = get_menu_url_from_table('acc_inventory');
    $product = \DB::table('crm_products')->where('id', $request->id)->get()->first();
    $product_id = $product->id;
    $lines = get_product_inventory_history($product_id);
   
    $stock_data = get_stock_balance($product_id);


    $stock_data['stock_value'] = $stock_data['qty_on_hand'] * $stock_data['cost_price'];
    if ('Stock' != $product->type) {
        $stock_data['qty_on_hand'] = 0;
        $stock_data['stock_value'] = 0;
    }

    $view .= '<div class="p-4">';
    $view .= '<h3>'.$product->code.'</h3><hr>';

  
    $view .= '<h6>Inventory History (Latest 10 adjustments)</h6>';

    $view .= '<table class="table table-sm">';
    $view .= '<thead><tr>';
    $view .= '<th>Document<th><th>Docdate</th><th>Quantity Change</th><th>Quantity New</th><th>Cost Price Excl Change</th>';
    $view .= '</tr></thead><tbody>';
    foreach ($lines as $d) {
        if (!empty($d->customer_doctype)) {
            $view .= '<tr><td><a href="/'.$documents_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">'.$d->customer_doctype.' #'.$d->document_id.'</a><td><td>'.$d->docdate.'</td><td>'.$d->qty_change.'</td>><td>'.$d->qty_new.'</td><td>'.currency($d->new_cost).'</td></tr>';
        } elseif (!empty($d->supplier_doctype)) {
            $view .= '<tr><td><a href="/'.$supplier_documents_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">'.$d->supplier_doctype.' #'.$d->supplier_document_id.'</a><td><td>'.$d->docdate.'</td><td>'.$d->qty_change.'</td><td>'.$d->qty_new.'</td><td>'.currency($d->new_cost).'</td></tr>';
        } else{
            $view .= '<tr><td><a href="/'.$inventory_url.'?id='.$d->id.'" target="_blank" data-target="view_modal">Inventory adjustment '.$d->id.'</a><td><td>'.$d->docdate.'</td><td>'.$d->qty_change.'</td><td>'.$d->qty_new.'</td><td>'.currency($d->new_cost).'</td></tr>';
        }
    }
    $view .= '</tbody></table>';
 
    $view .= '<h6>Inventory Current Value</h6>';

    $view .= '<table class="table table-sm">';
    $view .= '<thead><tr>';
    $view .= '<th>Stock Value</th><th>Qty on Hand</th><th>Cost Price Excl</th> <th>Cost Price Incl</th>';
    $view .= '</tr></thead><tbody>';

    $view .= '<tr><td>'.currency($stock_data['stock_value']).'</td><td>'.$stock_data['qty_on_hand'].'</td><td>'.currency($stock_data['cost_price']).'</td><td>'.currency($stock_data['cost_price']*1.15).'</td></tr>';

    $view .= '</tbody></table>';
    $view .= '</div>';

    echo $view;
}

function button_pricelists_reset_partner_pricelists($request)
{
    $retail_pricelist_id = \DB::table('crm_pricelists')->where(['partner_id' => 1, 'type' => 'retail', 'default_pricelist' => 1])->pluck('id')->first();

    $pricelist_items = \DB::table('crm_pricelist_items')->where('pricelist_id', $retail_pricelist_id)->get();
    $partner_pricelists = \DB::table('crm_pricelists')->where('partner_id', '!=', 1)->pluck('id')->toArray();
    foreach ($partner_pricelists as $partner_pricelist_id) {
        foreach ($pricelist_items as $item) {
            $item = (array) $item;
            unset($item['id']);
            unset($item['pricelist_id']);
            $exists = \DB::table('crm_pricelist_items')->where('pricelist_id', $retail_pricelist_id)->where('product_id', $item['product_id'])->count();
            if ($exists) {
                \DB::table('crm_pricelist_items')
                    ->where('pricelist_id', $partner_pricelist_id)
                    ->where('product_id', $item['product_id'])
                    ->update($item);
            } else {
                $item['pricelist_id'] = $partner_pricelist_id;
                \DB::table('crm_pricelist_items')->insert($item);
            }
        }
    }

    return json_alert('Reset complete');
}


/// HELPERS


function sql_filter_sort_product_rows($rows)
{
    $rows = json_decode(json_encode($rows), true);

    usort($rows, function ($a, $b) {
        $asort = \DB::table('crm_product_categories')->where('id', $a['product_category_id'])->pluck('sort_order')->first();
        $bsort = \DB::table('crm_product_categories')->where('id', $b['product_category_id'])->pluck('sort_order')->first();

        if ($asort === $bsort) {
            $asort = $a['sort_order'];
            $bsort = $b['sort_order'];
        }

        return $asort <=> $bsort;
    });
    $rows = json_decode(json_encode($rows), false);

    return $rows;
}



function check_stock($product_id, $qty)
{
    $product = dbgetrow('crm_products', 'id', $product_id);
    if ('Stock' == $product->type and $product->qty_on_hand <= $qty) {
        return 'Please check stock availability before completion';
    }
}

function get_code_description($product_id)
{
    $product = \DB::table('crm_products')->where('id', $product_id)->get()->first();
    if (!empty($product)) {
        return $product->code.' - '.substr($product->name, 0, 40);
    } else {
        return '';
    }
}

function select_options_departments($row)
{
    $options = [];
    $departments = \DB::table('crm_product_categories')->select('department')->groupBy('department')->pluck('department')->toArray();
    foreach ($departments as $department) {
        $options[$department] = $department;
    }
    return $options;
}


function schedule_products_set_active_subscriptions()
{
    \DB::table('crm_products')->update(['active_subscriptions'=>0]);
    $product_ids = \DB::table('sub_services')->where('status', '!=', 'Deleted')->pluck('product_id')->unique()->toArray();
    foreach ($product_ids as $product_id) {
        $active_subscriptions = \DB::table('sub_services')->where('status', '!=', 'Deleted')->where('product_id', $product_id)->count();
        \DB::table('crm_products')->where('id', $product_id)->update(['active_subscriptions'=>$active_subscriptions]);
    }
}

function set_supplier_id_products(){
    $product_ids = \DB::table('crm_products')->where('status','!=','Deleted')->pluck('id')->toArray();
    
    foreach($product_ids as $product_id){
        $supplier_id = \DB::table('crm_supplier_document_lines')
        ->join('crm_supplier_documents','crm_supplier_document_lines.document_id','=','crm_supplier_documents.id')
        ->where('crm_supplier_document_lines.product_id',$product_id)
        ->orderBy('crm_supplier_documents.docdate','desc')
        ->orderBy('crm_supplier_documents.id','desc')
        ->pluck('crm_supplier_documents.supplier_id')->first();
        if($supplier_id){
            \DB::table('crm_products')->where('id',$product_id)->update(['supplier_id'=>$supplier_id]);    
        }
    }
}

/*
function copy_products(){
dd(1);
$products = \DB::connection('moviemagic')->table('crm_products')->where('status','!=','Deleted')->where('product_category_id',977)->get();
    foreach($products as $p){
        $d = (array) $p;
        unset($d['id']);
        $d['product_category_id'] = 1011;
        \DB::connection('energy')->table('crm_products')->where('code',$p->code)->update($d);
        $eid = \DB::connection('energy')->table('crm_products')->where('code',$p->code)->pluck('id')->first();
      
        \DB::connection('energy')->table('crm_pricelist_items')->where('product_id',$eid)->delete();
        $pricing = \DB::connection('moviemagic')->table('crm_pricelist_items')->whereIn('pricelist_id',[1,2])->where('product_id',$p->id)->get();
        foreach($pricing as $price){
            $dd = (array) $price;
            unset($dd['id']);
            $dd['product_id'] = $eid;
            \DB::connection('energy')->table('crm_pricelist_items')->updateOrInsert(['pricelist_id'=>$price->pricelist_id,'product_id'=>$eid],$dd);
        }
        \DB::connection('energy')->table('acc_inventory')->where('product_id',$eid)->delete();
        $pricing = \DB::connection('moviemagic')->table('acc_inventory')->where('product_id',$p->id)->get();
        foreach($pricing as $price){
            $dd = (array) $price;
            unset($dd['id']);
            $dd['product_id'] = $eid;
            \DB::connection('energy')->table('acc_inventory')->insert($dd);
        }
    }
}
*/

