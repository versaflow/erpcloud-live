<?php

// old bulkhub integration, moved to wordpress


function schedule_bulkhub_update_shop_products(){
        return false;
    if(!is_main_instance()){
        return false;
    }
    $bulkhub = new Bulkhub;
    $bulkhub->updateShopProducts();
    generate_bulkhub_shopping_feeds();
}



function schedule_bulkhub_import_orders(){
    
        return false;
    if(!is_main_instance()){
        return false;
    }
    $bulkhub = new Bulkhub;
    $bulkhub->importOrders();
}

function bulkhub_remove_test_orders(){
    \DB::connection('default')->table('crm_bulkhub_links')->where('type','order')->delete();
}

function generate_bulkhub_shopping_feeds(){
    $google_file = 'bulkhub_google_feed.csv';
    $google_export_file = 'bulkhub_google_feed.csv';
   
    $google_filename = storage_path('exports').'/'.$google_file;
    $fb_file = 'bulkhub_facebook_feed.csv';
    $fb_filename = storage_path('exports').'/'.$fb_file;
    
    $products = \DB::connection('bulkhub')->table('products')->where('vendor_id',0)->where('status',1)->get();
    
    $fb_products_arr = [];
    $google_products_arr = [];
    foreach($products as $product){
        
        $category = \DB::connection('bulkhub')->table('categories')->where('id',$product->category_id)->pluck('name')->first();
        $data = [
            'id' => $product->id, 
            'title' => $product->seo_title, 
            'Description' => ($product->short_description > '') ? $product->short_description : $product->seo_title, 
            'Price' => $product->price, 
            'condition' => 'new', 
            'link' => 'https://bulkhub.co.za/product-detail/'.$product->slug, 
            'availability' => ($product->qty > 0) ? 'in_stock' : 'out_of_stock', 
            'image_link' => ($product->banner_image > '') ? 'https://bulkhub.co.za/'.str_replace(' ','%20',$product->banner_image) : '', 
            'brand' => 'Bulk Hub'
        ];
        
        foreach($data as $k => $v){
            $data[$k] = str_replace(",","",$v);
            $data[$k] = str_replace("'","",$v);
        }
    
        $fb_products_arr[] = $data;
        $data['Price'] = currency($data['Price']).' ZAR';
       
        $google_products_arr[] = $data;
    }
    $fb_products_list = collect($fb_products_arr);
    $google_products_list = collect($google_products_arr);
  
    (new Rap2hpoutre\FastExcel\FastExcel($google_products_list))->configureCsv("\t","@")->export($google_filename);
    (new Rap2hpoutre\FastExcel\FastExcel($fb_products_list))->configureCsv(',','"')->export($fb_filename);
    
    $replaced_content = file_get_contents($google_filename);
   
    file_put_contents($google_filename,str_replace('@','',$replaced_content));
    // copy exports to bulkhub
   
    if (file_exists($google_filename)) {
        $cmd = 'cp '.$google_filename.' /home/bulkhubc/bulkhub.co.za/html/public/'.$google_export_file;
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
   
    if (file_exists($fb_filename)) {
        $cmd = 'cp '.$fb_filename.' /home/bulkhubc/bulkhub.co.za/html/public/'.$fb_file;
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }

}

function bulkhub_pricecheck_feed(){
    
    $products = \DB::connection('bulkhub')->table('products')->where('vendor_id',0)->where('status',1)->get();
    
    $products_arr = [];
    
    foreach($products as $product){
    
        $category = \DB::connection('bulkhub')->table('categories')->where('id',$product->category_id)->pluck('name')->first();
        $subcategory = \DB::connection('bulkhub')->table('sub_categories')->where('id',$product->sub_category_id)->pluck('name')->first();
        $child_category = \DB::connection('bulkhub')->table('sub_categories')->where('id',$product->child_category_id)->pluck('name')->first();
        $category_name = $category;
        if($subcategory){
            $category_name .= ' > '.$subcategory;
        }
        if($child_category){
            $category_name .= ' > '.$child_category;
        }
        $data = [
            'Category' => $category_name,
            'ProductName' => $product->seo_title, 
            'Manufacturer' => 'Bulk Hub',
            'ShopSKU' => $product->id,
            'Description' => strip_tags(($product->short_description > '') ? $product->short_description : $product->seo_title), 
            'Price' => $product->price, 
            'ProductURL' => 'https://bulkhub.co.za/product-detail/'.$product->slug, 
            'ImageURL' => ($product->banner_image > '') ? 'https://bulkhub.co.za/'.str_replace(' ','%20',$product->banner_image) : '', 
            'StockAvailability' => ($product->qty > 0) ? 'In Stock' : 'Out of Stock', 
        ];
 
        
        foreach($data as $k => $v){
            $data[$k] = str_replace(",","",$v);
            $data[$k] = str_replace("'","",$v);
        }
        
        $products_arr[] = $data;
    }
    
    $array = [
        'Offer' => $products_arr,
    ];

    $result = Spatie\ArrayToXml\ArrayToXml::convert($array,'Offers');
    // Load the XML string into a DOMDocument
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($result);
    
    // Get the prettified XML as a string
    $xml = $dom->saveXML();
    
    // Replace &gt; with >
    $xml = str_replace('&gt;', '>', $xml);
    
    $pricecheck_file = 'bulkhub_pricecheck_feed.xml';
    $pricecheck_filename = storage_path('exports').'/'.$pricecheck_file;
    
    file_put_contents($pricecheck_filename,$xml);
    if (file_exists($pricecheck_filename)) {
        $cmd = 'cp '.$pricecheck_filename.' /home/bulkhubc/bulkhub.co.za/html/public/'.$pricecheck_file;
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }
}

/*
<?xml version="1.0" encoding='UTF-8'?>
<Offers>
<Offer>
<Category>Electronics > Photography > Digital Cameras</Category>
<ProductName>Canon EOS 350D</ProductName>
<Manufacturer>Canon</Manufacturer>
<ModelNumber>350D</ModelNumber>
<UPC>123131213</UPC>
<EAN>1234567891012</EAN>
<ShopSKU>20</ShopSKU
<Description>Takes great photos in dark conditions and is use primarily in the
semi-professional industry </Description>
<Price>12.50</Price>
<SalePrice>9.50</SalePrice>
<DeliveryCost>25.00</DeliveryCost>
<ProductURL>http://www.example.com/show_product.php?id=20</ProductURL>
<ImageURL>http://www.example.com/show_image.php?id=20</ImageURL>
<Notes>18-55mm lens kit, black</Notes>
<StockAvailability>In Stock</StockAvailability>
<StockLevel>3</StockLevel>
<is_mp>1</is_mp>
</Offer>
<Offer>
<Category>Books > Non-fiction > Autobiographies</Category>
<ProductName>In Black and White: The Jake White Story</ProductName>
<Manufacturer>Zebra Press</Manufacturer>
<ModelNumber>9781770220041</ModelNumber>
<UPC />
<EAN />
<ShopSKU>21</ShopSKU
<Description>In Black and White traces the life story of Springbok rugby coach
Jake White, right up to and including the 2007 Rugby World Cup. White's story
will both absorb and astound.</Description>
<Price>151.96</Price>
<DeliveryCost>29.00</DeliveryCost>
6
Ver: 2.6 - June 2017
<ProductURL>http://www.example.com/show_product.php?id=21</ProductURL>
<ImageURL>http://www.example.com/show_image.php?id=21</ImageURL>
<Notes />
<StockAvailability>Out of Stock</StockAvailability>
<Format>Soft Cover</Format>
<Attributes>
<Attribute>
<Name>Format</Name>
<Value>Soft Cover</Value>
</Attribute>
<Attribute>
<Name>ISBN</Name>
<Value>9781770220041</Value>
</Attribute>
</Attributes>
</Offer>
</Offers>

*/

/*
function schedule_update_bulkhub_shop_bagisto(){
    
    // BAGISTO
    
    $created_at = $updated_at = date('Y-m-d H:i:s');
    $products_img_path = uploads_path(71);
    // $bulkhub_schema = get_complete_schema('bulkhub');
    
    // DEPARTMENTS
    $categories = \DB::table('crm_product_categories')->where('storefront_id',3)->where('is_deleted',0)->orderBy('sort_order')->get();
    $departments = $categories->pluck('department')->unique()->toArray();
    
    // delete departments
    \DB::connection('bulkhub')->table('categories')->where('department','>','')->whereNotIn('department',$departments)->delete();
    $department_ids = [];
    
    foreach($departments as $i => $department){
        $bh_category_id = \DB::connection('bulkhub')->table('categories')->where('department','>','')->where('department',$department)->pluck('id')->first();
        if(!$bh_category_id){
            // insert new category
            $data = [
                'department' => $department, 
                'position' => $i, 
                'created_at' => $created_at,   
                'display_mode' => 'products_only',  
                'parent_id' => 1,     
                'status' => 1, 
            ];
            
            
            $bh_category_id = \DB::connection('bulkhub')->table('categories')->insertGetId($data);
            
            $data = [
                'name' => $department, 
                'slug' => strtolower(string_clean($department)),    
                'url_path' => strtolower(string_clean($department)),    
                'locale' => 'en', 
                'locale_id' => 1, 
                'category_id' => $bh_category_id,
                'meta_title' => '', 
                'meta_description' => '', 
                'meta_keywords' => '', 
            ];
            
            
            \DB::connection('bulkhub')->table('category_translations')->insert($data);
        }
        $department_ids[] = ['department'=>$department,'category_id'=>$bh_category_id];
    }
    $department_ids = collect($department_ids);
    // CATEGORIES
    $category_ids = $categories->pluck('id')->toArray();
    $deleted_category_ids = \DB::connection('bulkhub')->table('categories')->where('id','!=',1)->where('erp_id','>',0)->whereNotIn('erp_id',$category_ids)->pluck('id')->toArray();
  
    if(count($deleted_category_ids) > 0){
        // get deleted products linked to categories
        $deleted_product_ids = \DB::connection('bulkhub')->table('product_categories')->whereIn('category_id',$deleted_category_ids)->pluck('product_id')->toArray();
        if(count($deleted_product_ids) > 0){
            // delete products
            \DB::connection('bulkhub')->table('products')->whereIn('id',$deleted_product_ids)->delete();
        }
        // delete categories
        \DB::connection('bulkhub')->table('categories')->whereIn('id',$deleted_category_ids)->delete();
    }
    
    // categories in root to start at node ?14
   
    foreach($categories as $category){
        $bh_category_id = \DB::connection('bulkhub')->table('categories')->where('erp_id',$category->id)->pluck('id')->first();
        if($bh_category_id){
            // update existing category
            $data = [
                'erp_id' => $category->id, 
                'position' => $category->sort_order, 
                'updated_at' => $updated_at, 
                'display_mode' => 'products_only',  
                'parent_id' => $department_ids->where('department',$category->department)->pluck('category_id')->first(),     
                'status' => 1, 
            ];
          
            \DB::connection('bulkhub')->table('categories')->where('id',$bh_category_id)->update($data);
            
            $data = [
                'name' => $category->name, 
                'slug' => strtolower(string_clean($category->name)),    
                'url_path' => strtolower(string_clean($category->department).'/'.string_clean($category->name)),    
                'locale' => 'en', 
                'meta_title' => '', 
                'meta_description' => '', 
                'meta_keywords' => '', 
            ];
            
            \DB::connection('bulkhub')->table('category_translations')->where('category_id',$bh_category_id)->where('locale_id',1)->update($data);
            
        }else{
            // insert new category
            $data = [
                'erp_id' => $category->id, 
                'position' => $category->sort_order, 
                'created_at' => $created_at,   
                'display_mode' => 'products_only',  
                'parent_id' => $department_ids->where('department',$category->department)->pluck('category_id')->first(),          
                'status' => 1, 
            ];
            
            
            $bh_category_id = \DB::connection('bulkhub')->table('categories')->insertGetId($data);
            
            $data = [
                'name' => $category->name, 
                'slug' => strtolower(string_clean($category->name)),    
                'url_path' => strtolower(string_clean($category->department).'/'.string_clean($category->name)),   
                'locale' => 'en', 
                'locale_id' => 1, 
                'category_id' => $bh_category_id,
                'meta_title' => '', 
                'meta_description' => '', 
                'meta_keywords' => '', 
            ];
            
            
            \DB::connection('bulkhub')->table('category_translations')->insert($data);
        }
    }
    
    // update category nodes
    $bh_categories = \DB::connection('bulkhub')->table('categories')->where('erp_id','>',0)->orWhere('department','>','')->orderBy('position')->get();
    $bh_category_ids = $bh_categories->pluck('id')->toArray();
    \DB::connection('bulkhub')->table('category_filterable_attributes')->whereIn('category_id',$bh_category_ids)->delete();
    foreach($bh_category_ids as $category_id){
        \DB::connection('bulkhub')->table('category_filterable_attributes')->insert(['category_id'=>$category_id,'attribute_id'=>11]);   
    }
    
    $node_start = 14;
    foreach($bh_categories as $category){
        $data = [
          '_lft' => $node_start,
          '_rgt' => $node_start+1,
        ];
        
        \DB::connection('bulkhub')->table('categories')->where('id',$category->id)->update($data);
        $node_start++;
        $node_start++;
    }
    
    // update category root node end
    $max_node = \DB::connection('bulkhub')->table('categories')->where('id','!=',1)->max('_rgt');
    \DB::connection('bulkhub')->table('categories')->where('id',1)->update(['_rgt' => $max_node+1]);
    
    
    // PRODUCTS
    
    $products = \DB::table('crm_products')->whereIn('product_category_id',$category_ids)->where('not_for_sale',0)->where('status','!=','Deleted')->orderBy('sort_order')->get();
    $product_ids = $products->pluck('id')->toArray();
    $deleted_product_ids = \DB::connection('bulkhub')->table('products')->where('erp_id','>',0)->whereNotIn('erp_id',$product_ids)->pluck('id')->toArray();
    
    if(count($deleted_product_ids) > 0){
        // delete products
        \DB::connection('bulkhub')->table('products')->whereIn('id',$deleted_product_ids)->delete();
    }
    $customer_group_ids = \DB::connection('bulkhub')->table('customer_groups')->pluck('id')->toArray();
    foreach($products as $product){
        
        $bh_product_id = \DB::connection('bulkhub')->table('products')->where('erp_id',$product->id)->pluck('id')->first();
     
        if($bh_product_id){
            // update existing product
            $data = [
                'erp_id' => $product->id, 
                'sku' => $product->code, 
                'type' => ($product->type == 'Stock') ? 'simple' : 'virtual',
                'updated_at' => $updated_at,     
                'attribute_family_id' => 1,
            ];
            
            \DB::connection('bulkhub')->table('products')->where('id',$bh_product_id)->update($data);
            
            // pricing
            $pricing_data = [
                'min_price' => $product->selling_price_incl,
                'regular_min_price' => $product->selling_price_incl,
                'max_price' => $product->selling_price_incl,
                'regular_max_price' => $product->selling_price_incl, 
                'updated_at' => $updated_at,
            ];
            \DB::connection('bulkhub')->table('product_price_indices')->where('product_id',$bh_product_id)->update($pricing_data);
            
            // inventory
            $inv_data = [
                'channel_id' => 1,
                'product_id' => $bh_product_id,
                'qty' => $product->qty_on_hand,
                'updated_at' => $updated_at,
            ];
            \DB::connection('bulkhub')->table('product_inventory_indices')->where('product_id',$bh_product_id)->update($inv_data);
            $inv_data = [
                'inventory_source_id' => 1,
                'product_id' => $bh_product_id,
                'qty' => $product->qty_on_hand,
            ];
            \DB::connection('bulkhub')->table('product_inventories')->where('product_id',$bh_product_id)->update($inv_data);
            
            //images 
            $img_data = [
                'type' => 'images',
                'product_id' => $bh_product_id,
                'path' => 'product/'.$bh_product_id.'/'.$product->upload_file,
            ];
            \DB::connection('bulkhub')->table('product_images')->where('product_id',$bh_product_id)->update($img_data);
            
            
            // product description
            $product_data = [
                'sku' => $product->code,
                'type' => ($product->type == 'Stock') ? 'simple' : 'virtual',
                'product_number' => $product->code,
                'name' => $product->name,
                'description' => ($product->description > '') ? $product->description : $product->name,
                'url_key' => $product->code,
                'new' => (date('Y-m-01',strtotime($product->created_at)) == date('Y-m-01')) ? 1 : 0,
                'featured' => 0,
                'status' => 1,
                'price' => $product->selling_price_incl,
                'locale' => 'en',
                'channel' => 'default',
                'product_id' => $bh_product_id,
                'updated_at' => $updated_at,
                'visible_individually' => 1,
                'short_description' => $product->name,
                'attribute_family_id' => 1,
            ];
            
            \DB::connection('bulkhub')->table('product_flat')->where('product_id',$bh_product_id)->update($product_data);
        }else{
            // insert new product
            $data = [
                'erp_id' => $product->id, 
                'sku' => $product->code, 
                'type' => 'simple', 
                'created_at' => $created_at,   
                'attribute_family_id' => 1,     
            ];
            
            
            $bh_product_id = \DB::connection('bulkhub')->table('products')->insertGetId($data);
            
            // pricing
            foreach($customer_group_ids as $customer_group_id){
                $pricing_data = [
                    'customer_group_id' => $customer_group_id,
                    'product_id' => $bh_product_id,
                    'min_price' => $product->selling_price_incl,
                    'regular_min_price' => $product->selling_price_incl,
                    'max_price' => $product->selling_price_incl,
                    'regular_max_price' => $product->selling_price_incl, 
                    'created_at' => $created_at,
                ];
                \DB::connection('bulkhub')->table('product_price_indices')->insert($pricing_data);
            }
            
            // inventory
            $inv_data = [
                'channel_id' => 1,
                'product_id' => $bh_product_id,
                'qty' => $product->qty_on_hand,
                'created_at' => $created_at,
            ];
            \DB::connection('bulkhub')->table('product_inventory_indices')->insert($inv_data);
            $inv_data = [
                'inventory_source_id' => 1,
                'product_id' => $bh_product_id,
                'qty' => $product->qty_on_hand,
            ];
            \DB::connection('bulkhub')->table('product_inventories')->insert($inv_data);
            
            // images 
            $img_data = [
                'type' => 'images',
                'product_id' => $bh_product_id,
                'path' => 'product/'.$bh_product_id.'/'.$product->upload_file,
            ];
            \DB::connection('bulkhub')->table('product_images')->insert($img_data);
            
            // product description
            $product_data = [
                'sku' => $product->code,
                'type' => ($product->type == 'Stock') ? 'simple' : 'virtual',
                'product_number' => $product->code,
                'name' => $product->name,
                'description' => ($product->description > '') ? $product->description : $product->name,
                'url_key' => $product->code,
                'new' => (date('Y-m-01',strtotime($product->created_at)) == date('Y-m-01')) ? 1 : 0,
                'featured' => 0,
                'status' => 1,
                'price' => $product->selling_price_incl,
                'created_at' => $created_at,
                'locale' => 'en',
                'channel' => 'default',
                'product_id' => $bh_product_id,
                'visible_individually' => 1,
                'short_description' => $product->name,
                'attribute_family_id' => 1,
            ];
            
            \DB::connection('bulkhub')->table('product_flat')->insert($product_data);
        }
            
        // copy product images to host2
        if($product->upload_file > ''){
            $img_path = $products_img_path.$product->upload_file;
            $root_remote_path = '/home/bulk3/bulkhub.co.za/html/storage/app/public/product';
            $remote_path = $root_remote_path.'/'.$bh_product_id.'/'.$product->upload_file;
            if($product->upload_file && file_exists($img_path)){
           
                $ssh = new \phpseclib\Net\SSH2('host2.cloudtools.co.za');
                if ($ssh->login('root', 'Ahmed777')) {
                 
                    $cmd = 'cd /home/bulk3/bulkhub.co.za/html/storage/app/public/product && mkdir '.$bh_product_id;
                    Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
                    
                    $scp = new \phpseclib\Net\SCP($ssh);
                 
                    $result = $scp->put($remote_path, $img_path, $scp->SOURCE_LOCAL_FILE);
                    
                }
            }
        }
    }
    
    // rebuild product attributes
    $bh_product_ids = \DB::connection('bulkhub')->table('products')->where('erp_id','>',0)->pluck('id')->toArray();
    $bh_products = \DB::connection('bulkhub')->table('product_flat')->whereIn('product_id',$bh_product_ids)->get();
    \DB::connection('bulkhub')->table('product_attribute_values')->whereIn('product_id',$bh_product_ids)->delete();
    foreach($bh_products as $bh_product){
       
       
        $attr = [
            "attribute_id"=> 1,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "text_value"=> $bh_product->sku,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 2,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "channel"=> "default",
            "text_value"=> $bh_product->name,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 3,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "text_value"=> $bh_product->sku,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 4,
            "product_id"=> $bh_product->product_id,
            "channel"=> "default",
            "text_value"=> $bh_product->sku,
            "integer_value"=> 0,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 5,
            "product_id"=> $bh_product->product_id,
            "boolean_value"=> $bh_product->new,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        $attr = [
            "attribute_id"=> 6,
            "product_id"=> $bh_product->product_id,
            "boolean_value"=> $bh_product->featured,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 7,
            "product_id"=> $bh_product->product_id,
            "boolean_value"=> $bh_product->visible_individually,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        $attr = [
            "attribute_id"=> 8,
            "product_id"=> $bh_product->product_id,
            "boolean_value"=> $bh_product->status,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 9,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "channel"=> "default",
            "text_value"=> $bh_product->description,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        $attr = [
            "attribute_id"=> 10,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "channel"=> "default",
            "text_value"=> $bh_product->short_description,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        $attr = [
            "attribute_id"=> 11,
            "product_id"=> $bh_product->product_id,
            "float_value"=> $bh_product->price,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        $attr = [
            "attribute_id"=> 12,
            "product_id"=> $bh_product->product_id,
            "channel"=> "default",
            "float_value"=> $bh_product->cost_price,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 13,
            "product_id"=> $bh_product->product_id,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 14,
            "product_id"=> $bh_product->product_id,
            "channel"=> "default",
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 15,
            "product_id"=> $bh_product->product_id,
            "channel"=> "default",
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 16,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "channel"=> "default",
            "text_value"=> '',
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 17,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "channel"=> "default",
            "text_value"=> '',
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 18,
            "product_id"=> $bh_product->product_id,
            'locale' => 'en',
            "channel"=> "default",
            "text_value"=> '',
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 22,
            "product_id"=> $bh_product->product_id,
            "text_value"=> '1.0',
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 23,
            "product_id"=> $bh_product->product_id,
            "integer_value"=> 1,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 24,
            "product_id"=> $bh_product->product_id,
            "integer_value"=> 6,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 26,
            "product_id"=> $bh_product->product_id,
            "boolean_value"=> 0,
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
        
        $attr = [
            "attribute_id"=> 27,
            "product_id"=> $bh_product->product_id,
            "text_value"=> '',
        ];
        \DB::connection('bulkhub')->table('product_attribute_values')->insert($attr);
      
    }
    
    // product categories
    
    $bh_products = \DB::connection('bulkhub')->table('products')->whereIn('id',$bh_product_ids)->get();
    \DB::connection('bulkhub')->table('product_categories')->whereIn('product_id',$bh_product_ids)->delete();
    foreach($products as $product){
        $data = [
            'product_id' => $bh_products->where('erp_id',$product->id)->pluck('id')->first(),
            'category_id' => $bh_categories->where('erp_id',$product->product_category_id)->pluck('id')->first(),     
        ];
        \DB::connection('bulkhub')->table('product_categories')->insert($data);
        
        $department_id = $bh_categories->where('erp_id',$product->product_category_id)->pluck('parent_id')->first();
        if($department_id && $department_id !== 1){
            $data = [
                'product_id' => $bh_products->where('erp_id',$product->id)->pluck('id')->first(),
                'category_id' => $department_id,     
            ];
            \DB::connection('bulkhub')->table('product_categories')->insert($data);
        }
        
    }
    
    
    
    $cmd = 'chown bulk3:bulk3 '.$root_remote_path.' -R && chmod 775 '.$root_remote_path. ' -R';
    $permissions_result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    
}
*/