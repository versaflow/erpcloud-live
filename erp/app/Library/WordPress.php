<?php

class WordPress
{
    protected $wc;
    protected $excluded_categories;
    protected $update_images;
    protected $config;

    public function __construct($integration_id)
    {
        $this->setWoocommerce($integration_id);
    }

    public function setWoocommerce($integration_id)
    {
        $config = \DB::table('crm_wordpress_integrations')->where('id', $integration_id)->get()->first();

        if (!$config || empty($config->store_url) || empty($config->consumer_key) || empty($config->consumer_secret)) {
            throw new \ErrorException('Integration details invalid');
        }

        $this->config = $config;
        $this->wc = new Automattic\WooCommerce\Client(
            $config->store_url,
            $config->consumer_key,
            $config->consumer_secret,
            [
                'version' => 'wc/v2',
                'verify_ssl' => false,
                'wp_api' => true,
                'query_string_auth' => false,
                'timeout' => 180,
            ]
        );

        // $this->wc = new Pixelpeter\Woocommerce\WoocommerceClient();

        $this->update_images = true;
        if ($config->storefront_id == 3) {
            $category_ids = \DB::table('crm_product_categories')
            ->where('not_for_sale', 0)
            ->where('is_deleted', 0)
            ->where('storefront_id', $config->storefront_id)
            //->whereIn('id', explode(',', $config->allowed_categories))
            ->pluck('id')->toArray();
        } elseif (session('instance')->id == 1) {
            $category_ids = \DB::table('crm_product_categories')
            ->where('not_for_sale', 0)
            ->where('is_deleted', 0)
            ->where('storefront_id', $config->storefront_id)
            ->whereIn('id', explode(',', $config->allowed_categories))
            ->pluck('id')->toArray();
        } else {
            $category_ids = \DB::table('crm_product_categories')
            ->where('not_for_sale', 0)
            ->where('is_deleted', 0)
            ->whereIn('id', explode(',', $config->allowed_categories))
            ->pluck('id')->toArray();
        }

        $this->allowed_category_ids = $category_ids;
        $this->wordpress_links = \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->get();

        return $this;
    }

    public function addWordpressLink($type, $erp_value, $wordpress_id, $document_processed = 0, $payment_method = '', $transaction_id = 0)
    {
        $data = [
            'integration_id' => $this->config->id,
            'type' => $type,
            'erp_value' => $erp_value,
            'wordpress_id' => $wordpress_id,
            'document_processed' => $document_processed,
            'payment_method' => $payment_method,
            'transaction_id' => $transaction_id,
        ];
        \DB::table('crm_wordpress_links')->insert($data);
    }

    public function deleteWordpressLink($type, $wordpress_id)
    {
        \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->where('type', $type)->where('wordpress_id', $wordpress_id)->delete();
        ($data);
    }

    public function updateImages($bool)
    {
        $this->update_images = $bool;
    }

    public function getCustomerById($id)
    {
        return $this->wc->get('customers/'.$id);
    }

    public function getCustomerByErpId($id)
    {
        $customers = $this->wc->get('customers');
        foreach ($customers as $customer) {
            foreach ($customer['meta_data'] as $meta) {
                if ($meta['key'] == 'erp_id' && $meta['value'] == $id) {
                    return $customer;
                }
            }
        }

        return false;
    }

    public function getEndpoints()
    {
        return $this->wc->get('');
    }

    public function getPostType($post_type)
    {
        return $this->wc->get($post_type);
    }

    public function getProducts()
    {
        return $this->wc->get('products');
    }

    public function getAllProducts()
    {
        $products = $this->wc->get('products', ['per_page' => 100]);
        $all_products = collect($products);
        $i = 1;
        while (count($products) == 100) {
            ++$i;

            $products = $this->wc->get('products', ['page' => $i, 'per_page' => 100]);
            $products = collect($products);
            $all_products = $all_products->merge($products);
        }

        return $all_products;
    }

    public function getCategories()
    {
        return $this->wc->get('products/categories');
    }

    public function getAllCategories()
    {
        $categories = $this->wc->get('products/categories', ['per_page' => 100]);
        $all_categories = collect($categories);
        $i = 1;
        while (count($categories) == 100) {
            ++$i;

            $categories = $this->wc->get('products/categories', ['page' => $i, 'per_page' => 100]);
            $categories = collect($categories);
            $all_categories = $all_categories->merge($categories);
        }

        return $all_categories;
    }

    public function updateCategories($id = false)
    {
        $this->wordpress_links = \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->get();
        //$this->updateImages(false);
        $categories = \DB::table('crm_product_categories');
        if ($id) {
            $categories->where('id', $id);
        }
        $categories->where('is_deleted', 0);
        $categories->whereIn('id', $this->allowed_category_ids);
        $categories->orderBy('sort_order');
        $categories = $categories->get();
        $departments = collect($categories)->pluck('department')->toArray();

        $i = 0;
        foreach ($departments as $i => $department) {
            aa($this->config->id);
            $department_id = $this->wordpress_links->where('type', 'department')->where('erp_value', $department)->where('integration_id', $this->config->id)->pluck('wordpress_id')->first();

            $data = [
                'name' => $department,
                'menu_order' => $i,
            ];
            try {
                if (!empty($department_id)) { // update
                    $result = $this->wc->put('products/categories/'.$department_id, $data);
                } else { // create
                    aa($data);
                    $result = $this->wc->post('products/categories', $data);
                    $department_id = $result['id'];
                    $this->addWordpressLink('department', $department, $result['id']);
                }
            } catch (\Throwable $ex) {
                exception_log($ex);
                $err = $ex->getMessage();
                //dd($data,$ex->getMessage(),$ex->getTraceAsString());
            }

            ++$i;

            $department_categories = collect($categories)->where('department', $department);

            foreach ($department_categories as $category) {
                // vd($category);
                $category_id = $this->wordpress_links->where('type', 'category')->where('erp_value', $category->id)->where('integration_id', $this->config->id)->pluck('wordpress_id')->first();
                $data = [
                    'name' => $category->name,
                    'description' => $category->slogan,
                    'parent' => $department_id,
                    'menu_order' => $category->sort_order,
                ];

                if ($this->update_images && !empty($category->website_image_1)) {
                    $file = $category->website_image_1;
                    if (!empty($file) && file_exists(uploads_path(72).$file)) {
                        $img_data = ['src' => uploads_url(72).$category->website_image_1];
                        $category_image_id = $this->wordpress_links->where('type', 'categoryimage')->where('erp_value', $category->id)->where('integration_id', $this->config->id)->pluck('wordpress_id')->first();
                        if ($category_image_id) {
                            $img_data['id'] = $category_image_id;
                        }
                        $data['image'] = $img_data;
                    }
                }

                try {
                    if (!empty($category_id)) { // update
                        $result = $this->wc->put('products/categories/'.$category_id, $data);
                    } else { // create
                        $result = $this->wc->post('products/categories', $data);

                        $this->addWordpressLink('category', $category->id, $result['id']);
                    }
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    $err = $ex->getMessage();
                    //dd($ex->getMessage(),$ex->getTraceAsString());
                }
            }
        }
    }

    public function importImageLinks()
    {
        \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->where('type', 'productimage')->delete();
        \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->where('type', 'categoryimage')->delete();
        $this->wordpress_links = \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->get();
        $products = $this->getAllProducts();
        foreach ($products as $product) {
            if (!empty($product['images'][0]['id'])) {
                $product_id = $this->wordpress_links->where('type', 'product')->where('wordpress_id', $product['id'])->where('integration_id', $this->config->id)->pluck('erp_value')->first();
                if ($product_id) {
                    $this->addWordpressLink('productimage', $product_id, $product['images'][0]['id']);
                }
            }
        }

        $categories = $this->getAllCategories();
        foreach ($categories as $category) {
            if (!empty($category['image']['id'])) {
                $category_id = $this->wordpress_links->where('type', 'category')->where('wordpress_id', $category['id'])->where('integration_id', $this->config->id)->pluck('erp_value')->first();
                if ($category_id) {
                    $this->addWordpressLink('categoryimage', $category_id, $category['image']['id']);
                }
            }
        }
    }

    public function getFeaturedProducts()
    {
    }

    public function getBestSellingProducts()
    {
    }

    public function getContractAttributeId()
    {
        $attribute_id = false;

        $attributes = $this->wc->get('products/attributes');
        $name = 'Contract';
        foreach ($attributes as $attribute) {
            if ($attribute['name'] == $name) {
                $attribute_id = $attribute['id'];
            }
        }

        if (!$attribute_id) {
            $data = [
            'name' => $name,
            'slug' => 'ct_'.strtolower(str_replace(' ', '_', $name)),
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => true,
            ];
            $result = $this->wc->post('products/attributes', $data);

            $attribute_id = $result['id'];
        }

        // set the attribute terms

        $attribute_terms = $this->wc->get('products/attributes/'.$attribute_id.'/terms');
        $attribute_term_names = [];
        foreach ($attribute_terms as $attribute_term) {
            $attribute_term_names[] = $attribute_term['name'];
        }

        if (!in_array('Monthly', $attribute_term_names)) {
            $data = [
                'name' => 'Monthly',
            ];

            $this->wc->post('products/attributes/'.$attribute_id.'/terms', $data);
        }

        if (!in_array('12 Month Contract', $attribute_term_names)) {
            $data = [
                'name' => '12 Month Contract',
            ];

            $this->wc->post('products/attributes/'.$attribute_id.'/terms', $data);
        }

        return $attribute_id;
    }

    public function updateProducts($id = false)
    {
        //  $result =  $this->wc->get('products/1763/variations');
        $remove_tax_fields = get_admin_setting('remove_tax_fields');
        if ($remove_tax_fields) {
            $price_field = 'price';
        } else {
            $price_field = 'price_tax';
        }
        $this->wordpress_links = \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->get();
        $products = \DB::table('crm_products');
        $products->select('crm_products.*', 'crm_pricelist_items.'.$price_field.' as price', 'crm_product_categories.department');
        $products->join('crm_product_categories', 'crm_product_categories.id', '=', 'crm_products.product_category_id');
        $products->join('crm_pricelist_items', 'crm_pricelist_items.product_id', '=', 'crm_products.id');
        if ($id) {
            $products->where('crm_products.id', $id);
        }

        //$products->where('crm_products.product_category_id', 1012);
        $products->where('crm_products.status', 'Enabled');
        $products->where('crm_pricelist_items.pricelist_id', 1);

        $products->whereIn('crm_product_categories.id', $this->allowed_category_ids);

        $products = $products->get();

        $telkom_lte_product_ids = get_activation_type_product_ids('telkom_lte_sim_card');
        $mtn_lte_product_ids = get_activation_type_product_ids('mtn_lte_sim_card');

        $lte_product_ids = array_merge($telkom_lte_product_ids, $mtn_lte_product_ids);

        $linked_lte_product_ids = \DB::table('isp_data_lte_axxess_products')->pluck('product_id')->unique()->filter()->toArray();

        // get or create the 12 month contract variation
        // $contract_attribute_id = $this->getContractAttributeId();

        foreach ($products as $product) {
            try {
                if (in_array($product->id, $lte_product_ids) && !in_array($product->id, $linked_lte_product_ids)) {
                    continue;
                }

                if (str_contains($product->code, '_copy')) {
                    continue;
                }

                if (!in_array($product->product_category_id, $this->allowed_category_ids)) {
                    continue;
                }

                if (str_contains($product->code, 'rate')) {
                    continue;
                }
                $category_id = $this->wordpress_links->where('erp_value', $product->product_category_id)
            ->where('type', 'category')->where('integration_id', $this->config->id)->pluck('wordpress_id')->first();
                $product_id = $this->wordpress_links->where('type', 'product')->where('erp_value', $product->id)->where('integration_id', $this->config->id)->pluck('wordpress_id')->first();

                if ($product->product_bill_frequency > 1) {
                    $product->price = $product->price * $product->product_bill_frequency;
                }

                $data = [
                'sku' => $product->code,
                'name' => $product->name,
                'short_description' => $product->description,
                'description' => $product->description,
                'menu_order' => $product->sort_order,
                'categories' => [['id' => $category_id]],
                'regular_price' => (string) currency($product->price),
                'catalog_visibility' => 'visible',
                'stock_status' => 'instock',
                'type' => 'simple',
            ];

                if (str_contains($product->code, 'rate')) {
                    $data['type'] = 'external';
                }

                if ($product->not_for_sale) {
                    $data['type'] = 'external';
                }

                if ($data['type'] == 'external') {
                    $data['external_url'] = 'mailto:helpdesk@telecloud.co.za';
                    $data['button_text'] = 'Contact Us to place order';
                } else {
                    $data['external_url'] = '';
                    $data['button_text'] = '';
                }

                // if($product->is_subscription && $product->price > 0){

                //     $data['type'] = 'variable';

                //     $data['attributes'] = [
                //         [
                //             'name' => 'is_subscription',
                //             'position' => 0,
                //             'visible' => false,
                //             'variation' => false,
                //             'options' => ['no'], //['yes'], // Set the default option to 'yes' or 'no'
                //         ],
                //         // [
                //         //     'name' => 'contract',
                //         //     'position' => 0,
                //         //     'visible' => true,
                //         //     'variation' => true,
                //         //     'options' => ['no'], //($product->contract_period == 12) ? ['12 Month Contract','Monthly']: ['Monthly','12 Month Contract'], // Set the default option to 'yes' or 'no'
                //         // ],
                //     ];
                // }elseif($product->is_subscription){

                //     $data['attributes'] = [
                //         [
                //             'name' => 'is_subscription',
                //             'position' => 0,
                //             'visible' => false,
                //             'variation' => false,
                //             'options' => ['no'], // Set the default option to 'yes' or 'no'
                //         ],
                //     ];

                // }else{
                // $data['attributes'] = [];
                // }

                // meta data used for products shortcode sorting
                $meta_data = [];

                // featured - use stock value
                $meta_data[] = ['key' => 'stock_value', 'value' => $product->stock_value];

                // best sellers - sort from document lines
                $count_document_lines = \DB::connection('default')->table('crm_document_lines')->where('product_id', $product->id)->count();
                $meta_data[] = ['key' => 'num_sold', 'value' => $count_document_lines];
                $data['meta_data'] = [$meta_data];

                if ($this->update_images && !empty($product->upload_file)) {
                    $file = $product->upload_file;
                    if (!empty($file) && file_exists(uploads_path(71).$file)) {
                        $img_data = ['src' => uploads_url(71).$file];
                        $product_image_id = $this->wordpress_links->where('type', 'productimage')->where('erp_value', $product->id)->where('integration_id', $this->config->id)->pluck('wordpress_id')->first();
                        if ($product_image_id) {
                            $img_data['id'] = $product_image_id;
                        }
                        $data['images'] = [$img_data];
                    }
                }

                if (!empty($product_id)) { // update
                    $result = $this->wc->put('products/'.$product_id, $data);

                /*
                if(str_contains($result,'Error: Invalid ID')){
                    $result =  $this->wc->post('products', $data);

                }
                */
                } else {
                    // create

                    $result = $this->wc->post('products', $data);
                    $this->addWordpressLink('product', $product->id, $result['id']);
                }

                // // add contract variation
                // if($result['id'] && $product->is_subscription && $product->price > 0){
                //     $variants =  $this->wc->get('products/'.$result['id'].'/variations');
                //     $monthly_variant_id = false;
                //     $contract_variant_id = false;
                //     foreach($variants as $v){
                //         foreach($v['attributes'] as $attr){
                //             if($attr['name'] == 'contract' && $attr['option'] == '12 Month Contract'){
                //                 $contract_variant_id = $v['id'];
                //             }
                //         }
                //         foreach($v['attributes'] as $attr){
                //             if($attr['name'] == 'contract' && $attr['option'] == 'Monthly'){
                //                 $monthly_variant_id = $v['id'];
                //             }
                //         }
                //     }

                //         $price_12 = \DB::table('crm_pricelist_items')
                //         ->where('pricelist_id', 1)
                //         ->where('product_id', $product->id)
                //         ->pluck('price_tax_12')->first();
                //         $monthly_price = currency($product->price);
                //         if($product->product_bill_frequency > 1){
                //             $monthly_price = $monthly_price * $product->product_bill_frequency;
                //             $price_12 = $price_12 * $product->product_bill_frequency;
                //         }

                //         $contract_variant = [
                //             'regular_price' => $price_12,
                //             'attributes' => [
                //                 [
                //                     //'id' => $contract_attribute_id,
                //                     'name' => 'contract',
                //                     'option' => '12 Month Contract'
                //                 ]
                //             ]
                //         ];
                //         $monthly_variant = [
                //             'regular_price' => $monthly_price,
                //             'attributes' => [
                //                 [
                //                     //'id' => $contract_attribute_id,
                //                     'name' => 'contract',
                //                     'option' => 'Monthly'
                //                 ]
                //             ]
                //         ];

                //     if($contract_variant_id){
                //         $variant_result = $this->wc->put('products/'.$result['id'].'/variations/'.$contract_variant_id, $contract_variant);

                //     }else{
                //         $variant_result = $this->wc->post('products/'.$result['id'].'/variations', $contract_variant);

                //     }
                //     if($product->contract_period != 12){
                //         if($monthly_variant_id){
                //             $variant_result = $this->wc->put('products/'.$result['id'].'/variations/'.$monthly_variant_id, $monthly_variant);

                //         }else{
                //             $variant_result = $this->wc->post('products/'.$result['id'].'/variations', $monthly_variant);
                //         }
                //     }elseif($monthly_variant_id){
                //         $delete_result = $this->wc->delete('products/'.$result['id'].'/variations/'.$monthly_variant_id, ['force' => true]);
                //     }
                // }
            } catch (\Throwable $ex) {
                exception_log($ex);

                //dd($product_id,$category_id,$result);
            }
        }

        return $result;
    }

    public function deleteCategories()
    {
        $wp_categories = $this->getAllCategories();
        $wp_categories = collect($wp_categories);
        $wp_department_ids = $wp_categories->where('parent', 0)->pluck('id')->toArray();
        $wp_category_ids = $wp_categories->where('parent', '!=', 0)->pluck('id')->toArray();

        \DB::table('crm_wordpress_links')
         ->where('integration_id', $this->config->id)
         ->where('type', 'category')
         ->whereNotIn('erp_value', $this->allowed_category_ids)
         ->delete();

        \DB::table('crm_wordpress_links')
         ->where('integration_id', $this->config->id)
         ->where('type', 'department')
         ->whereNotIn('wordpress_id', $wp_department_ids)
         ->delete();

        \DB::table('crm_wordpress_links')
         ->where('integration_id', $this->config->id)
         ->where('type', 'category')
         ->whereNotIn('wordpress_id', $wp_category_ids)
         ->delete();

        foreach ($wp_categories as $wp_category) {
            $wp_category = (object) $wp_category;
            if ($wp_category->name == 'Uncategorised' || $wp_category->name == 'Uncategorized') {
                continue;
            }

            $exists = \DB::table('crm_wordpress_links')
            ->where('integration_id', $this->config->id)
            ->whereIn('type', ['department', 'category'])
            ->where('wordpress_id', $wp_category->id)->count();

            if ($exists) {
                continue;
            }

            try {
                $this->wc->delete('products/categories/'.$wp_category->id, ['id' => $wp_category->id, 'force' => true]);
                $this->deleteWordpressLink('department', $wp_category->id);
                $this->deleteWordpressLink('category', $wp_category->id);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $error = $ex->getMessage();
                if ($error == 'Error: Default product category cannot be deleted. [woocommerce_rest_cannot_delete]') {
                    continue;
                }
            }
        }
    }

    public function deleteProducts()
    {
        $wp_products = $this->getAllProducts();
        $wp_products = collect($wp_products);
        $wp_product_ids = $wp_products->pluck('id')->toArray();

        \DB::table('crm_wordpress_links')
         ->where('integration_id', $this->config->id)
         ->where('type', 'product')
         ->whereNotIn('wordpress_id', $wp_product_ids)
         ->delete();

        $telkom_lte_product_ids = get_activation_type_product_ids('telkom_lte_sim_card');
        $mtn_lte_product_ids = get_activation_type_product_ids('mtn_lte_sim_card');

        $lte_product_ids = array_merge($telkom_lte_product_ids, $mtn_lte_product_ids);

        $linked_lte_product_ids = \DB::table('isp_data_lte_axxess_products')->pluck('product_id')->unique()->filter()->toArray();

        foreach ($wp_products as $wp_product) {
            $wp_product = (object) $wp_product;
            if ($wp_product->type == 'bopobb') {
                continue; // do not delete custom bundle products
            }
            if ($wp_product->name == 'Uncategorised') {
                continue;
            }

            $erp_value = \DB::table('crm_wordpress_links')
            ->where('integration_id', $this->config->id)
            ->where('type', 'product')
            ->where('wordpress_id', $wp_product->id)->pluck('erp_value')->first();

            if (!$erp_value) {
                $result = $this->wc->delete('products/'.$wp_product->id, ['id' => $wp_product->id, 'force' => true]);
            } else {
                $products = \DB::table('crm_products');
                $products->select('crm_products.*', 'crm_pricelist_items.price_tax as price', 'crm_product_categories.department');
                $products->join('crm_product_categories', 'crm_product_categories.id', '=', 'crm_products.product_category_id');
                $products->join('crm_pricelist_items', 'crm_pricelist_items.product_id', '=', 'crm_products.id');
                $products->where('crm_products.status', 'Enabled');
                $products->where('crm_products.id', $erp_value);
                $products->whereIn('crm_product_categories.id', $this->allowed_category_ids);
                $products->where('crm_pricelist_items.pricelist_id', 1);

                $valid_product = $products->count();
                if (in_array($erp_value, $lte_product_ids) && !in_array($erp_value, $linked_lte_product_ids)) {
                    $valid_product = false;
                }

                if ($valid_product) {
                    continue;
                }

                try {
                    $result = $this->wc->delete('products/'.$wp_product->id, ['id' => $wp_product->id, 'force' => true]);
                    $this->deleteWordpressLink('product', $wp_product->id);
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    $error = $ex->getMessage();
                }
            }
        }
    }

    public function getProduct($product_id)
    {
        return $this->wc->get('products/'.$product_id);
    }

    public function deleteProductImage($product_id)
    {
        /* https://developer.wordpress.org/rest-api/reference/media/#delete-a-media-item*/
        $product = $this->getProduct($product_id);
        //dd($product);
    }

    public function getProductPermalink($product_id)
    {
        $product = $this->getProduct($product_id);

        return $product['permalink'];
    }

    // IMPORT ORDERS

    public function getNewOrders()
    {
        $orders = $this->wc->get('orders');
        // $r = $this->markOrderUnprocessed(18309);
        $new_orders = [];

        foreach ($orders as $order) {
            if ($order['meta_data']['key'] == 'erp_id')
                continue;

            if ($order['status'] == 'cancelled') {
                continue;
            }
            if ($order['customer_id'] == 0) {
                $result = $this->wc->delete('orders/'.$order['id'], ['id' => $order['id'], 'force' => true]);
                continue;
            }

            $add_order = true;
            // if ($order['id'] == 18309) {
            // }
            if (count($order['meta_data']) > 0) {
                foreach ($order['meta_data'] as $meta) {
                    if ($meta['key'] == 'erp_id' && $meta['value'] > '') {
                        $add_order = false;
                    }
                }
            }
            if ($add_order) {
                $new_orders[] = $order;
            }
        }

        return $new_orders;
    }

    public function getOrder($order_id)
    {
        return $this->wc->get('orders/'.$order_id);
    }

    public function markOrderProcessed($order_id, $erp_id)
    {
        $order = $this->wc->get('orders/'.$order_id);
        $data['meta_data'] = $order['meta_data'];
        $data['meta_data'][] = ['key' => 'erp_id', 'value' => $erp_id];

        return $this->wc->put('orders/'.$order_id, $data);
    }

    public function markOrderUnprocessed($order_id)
    {
        $order = $this->wc->get('orders/'.$order_id);
        $data['meta_data'] = $order['meta_data'];
        $data['meta_data'][] = ['key' => 'erp_id', 'value' => ''];

        return $this->wc->put('orders/'.$order_id, $data);
    }

    public function importOrders()
    {
        try {
            $this->wordpress_links = \DB::table('crm_wordpress_links')->where('integration_id', $this->config->id)->get();
            $new_orders = $this->getNewOrders();
            // dd($new_orders);
            $vat_disabled = get_admin_setting('remove_tax_fields');
            // vd($new_orders);
            foreach ($new_orders as $order) {
                // vd($order);
                if ($order['status'] == 'checkout-draft') {
                    continue;
                }
                // if (!is_main_instance() && $order['needs_payment']) {
                //     continue;
                // }

                $doctype = 'Order';
                if (!$order['needs_payment']) {
                    $doctype = 'Tax Invoice';
                }
                // $doctype = 'Quotation';
                //create order
                $this->importCustomer($order['customer_id']);
                $account_id = $this->getCustomerErpId($order['customer_id']);
                // vd($account_id);
                // vd($order['id']);

                if (!$account_id) {
                    debug_email('WordPress order could not be imported, invalid customer - order id: '.$order['id']);
                    continue;
                }

                //$doctype = 'Quotation';
                $address = $order['billing']['address_1']
                .', '.$order['billing']['address_2']
                .', '.$order['billing']['city']
                .', '.$order['billing']['state']
                .', '.$order['billing']['postcode']
                .', '.$order['billing']['country'];

                $data = [
                    'bill_frequency' => 1,
                    'docdate' => date('Y-m-d'), //, strtotime($order['date_created'])),
                    'doctype' => $doctype,
                    'completed' => 1,
                    'account_id' => $account_id,
                    'total' => $order['total'],
                    'tax' => $order['total'] - ($order['total'] / 1.15),
                    'reference' => 'Website Order #'.$order['id'].' - '.$order['payment_method_title'],
                    'qty' => [],
                    'price' => [],
                    'full_price' => [],
                    'product_id' => [],
                    'subscription_created' => 0,
                    'coverage_confirmed' => 1,
                    'coverage_address' => $address,
                    'website_note' => $order['customer_note'],
                ];
                if ($vat_disabled) {
                    $data['tax'] = 0;
                }

                foreach ($order['line_items'] as $line) {
                    // vd($line);
                    // vd($this->config->id);

                    $description = '';
                    $product_id = $this->wordpress_links->where('type', 'product')->where('wordpress_id', $line['product_id'])->where('integration_id', $this->config->id)->pluck('erp_value')->first();

                    // vd($product_id);
                    // if (!$product_id) {
                    //     // check if bundle product
                    //     $is_bundle = false;
                    //     // vd($meta_data);
                    //     foreach ($line['meta_data'] as $meta_data) {
                    //         if ($meta_data['key'] == '_bopobb_ids') {
                    //             $is_bundle = true;
                    //             $product_id = $meta_data['key'];
                    //             $description = $line['name'];
                    //         } else {
                    //             debug_email('WordPress order could not be imported, invalid product - order id: '.$order['id']);
                    //             continue 2;
                    //         }
                    //     }
                    // }
                    $data['description'][] = $description;
                    $data['product_id'][] = $product_id;
                    $data['qty'][] = $line['quantity'];
                    if ($vat_disabled) {
                        $data['price'][] = $line['total'];
                        $data['full_price'][] = $line['total'];
                    } else {
                        $data['price'][] = ($line['total'] > 0) ? $line['total'] / 1.15 : 0;
                        $data['full_price'][] = ($line['total'] > 0) ? $line['total'] / 1.15 : 0;
                    }

                    if (str_contains($line['name'], '- 12 Month Contract')) {
                        $data['contract_period'][] = 12;
                    } else {
                        $data['contract_period'][] = 1;
                    }
                    $db_product = \DB::table('crm_products')->where('id', $product_id)->pluck('contract_period')->first();
                    if ($db_product->contract_period == 12) {
                        $data['contract_period'][] = 12;
                    } else {
                        if (str_contains($line['name'], '- 12 Month Contract')) {
                            $data['contract_period'][] = 12;
                        } else {
                            $data['contract_period'][] = 1;
                        }
                    }
                }

                $db = new DBEvent();
                $result = $db->setTable('crm_documents')->setProperties(['validate_document' => 1])->save($data);
                vd($result);
                if (is_array($result) && !empty($result['id'])) {
                    $erp_id = $result['id'];
                    $check = $this->markOrderProcessed($order['id'], $erp_id);
                    vd($check);
                    $this->addWordpressLink('document', $result['id'], $order['id'], 0, $order['payment_method'], $order['transaction_id']);

                    if ($doctype == 'Tax Invoice' && $order['payment_method'] == 'payfast') {
                        // create payfast subscription
                        $payfast_subscription_token = false;
                        foreach ($order['meta_data'] as $meta_data) {
                            if ($meta_data['key'] == '_payfast_subscription_token' && $meta_data['value']) {
                                $payfast_subscription_token = $meta_data['value'];
                            }
                        }
                        if ($payfast_subscription_token) {
                            // create payfast subscription
                            $subscription_data = [
                                'created_at' => date('Y-m-d H:i:s'),
                                'token' => $payfast_subscription_token,
                                'account_id' => $account_id,
                                'status' => 'Enabled',
                            ];
                            dbinsert('acc_payfast_subscriptions', $subscription_data);
                        }

                        $this->createPayfastTransaction($account_id, $order);
                    }
                } else {
                    if ($result instanceof \Illuminate\Http\JsonResponse) {
                        debug_email('WordPress order could not be imported, document create error: Order Id:'.$order['id'].', '.$result->getData()->message);
                    } else {
                        debug_email('WordPress order could not be imported, document create error:  Order Id:'.$order['id']);
                    }
                }
            }
        } catch (\Exception $e) {
            exception_log($e);
        }
    }

    public function updateDocumentReferences()
    {
        $orders = \DB::table('crm_wordpress_links')->where('type', 'document')->where('integration_id', $this->config->id)->get();
        foreach ($orders as $order) {
            \DB::table('crm_documents')->where('id', $order->erp_value)->update(['website_order_id' => $order->wordpress_id, 'website' => $this->config->store_url]);
        }
    }

    public function createPayfastTransaction($account_id, $order)
    {
        // assign payfast transaction to customer

        $cashbook = \DB::table('acc_cashbook')->where('id', 5)->get()->first();
        $payfast_amount_fee = 0;
        foreach ($order['meta_data'] as $meta_data) {
            if ($meta_data['key'] == 'payfast_amount_fee' && $meta_data['value']) {
                $payfast_amount_fee = $meta_data['value'];
            }
        }
        $payfast_amount_net = 0;
        foreach ($order['meta_data'] as $meta_data) {
            if ($meta_data['key'] == 'payfast_amount_net' && $meta_data['value']) {
                $payfast_amount_net = $meta_data['value'];
            }
        }

        if (!empty($order['transaction_id'])) {
            \DB::table('acc_cashbook_transactions')->where('cashbook_id', 5)->where('doctype', 'Cashbook Customer Receipt')->where('api_id', $order['transaction_id'])->delete();
        }

        if ($payfast_amount_fee) {
            $exists = \DB::table('acc_cashbook_transactions')->where('reference', 'LIKE', 'Payfast Fee%')->where('api_id', $order['transaction_id'])->count();
            if (!$exists) {
                $fee_data = [
            'ledger_account_id' => 22,
            'cashbook_id' => $cashbook->id,
            'total' => abs($payfast_amount_fee),
            'api_id' => $order['transaction_id'],
            'reference' => 'Payfast Fee '.$order['transaction_id'],
            'api_status' => 'Complete',
            'doctype' => 'Cashbook Control Payment',
            'docdate' => date('Y-m-d H:i:s'), //, strtotime($order['date_created'])),
            ];

                $fee_result = (new \DBEvent())->setTable('acc_cashbook_transactions')->save($fee_data);
                if (!is_array($fee_result) || empty($fee_result['id'])) {
                    debug_email(session('instance')->name.' Error processing payfast website order fee.', 'Error processing payfast website order.'.json_encode($fee_result));
                    throw new \ErrorException('Error inserting Payfast Fee into journals.'.json_encode($fee_result));
                }
            }
        }

        $api_data = [
            'doctype' => 'Cashbook Customer Receipt',
            'api_status' => 'Complete',
            'account_id' => $account_id,
            'reference' => 'Website Order #'.$order['id'],
            'total' => $order['total'],
            'cashbook_id' => $cashbook->id,
            'ledger_account_id' => null,
            'docdate' => date('Y-m-d H:i:s'), //, strtotime($order['date_created'])),
            'api_id' => $order['transaction_id'],
            'api_balance' => 0,
        ];

        $result = (new \DBEvent())->setTable('acc_cashbook_transactions')->save($api_data);

        if (!is_array($result) || empty($result['id'])) {
            debug_email(session('instance')->name.' Error processing payfast website order.', 'Error processing payfast website order.'.json_encode($result));
        }
    }

    public function importCustomer($customer_id)
    {
        // vd($customer_id);
        if (!in_array(session('instance')->id, [1, 11])) {
            return false;
        }

        // set dbconn to get user pass
        $store_db = $this->config->store_db;
        $customer = $this->getCustomersById($customer_id);
        $erp_id = $this->getCustomerErpId($customer['id']);
        if ($erp_id) {
            return true;
        }
        // vd($customer);
        if ($customer['orders_count'] > 0) {
            $details = $customer['billing'];
            $customer_data = [
                'contact' => implode(' ', [$details['first_name'], $details['last_name']]),
                'company' => (!empty($details['company'])) ? $details['company'].' - '.implode(' ', [$details['first_name'], $details['last_name']]) : implode(' ', [$details['first_name'], $details['last_name']]),
                'phone' => $details['phone'],
                'email' => $details['email'],
                'address' => implode(',', [$details['address_1'], $details['address_2'], $details['city'], $details['postcode'], $details['country']]),
                'type' => 'lead',
                'partner_id' => 1,
                'status' => 'Enabled',
                'marketing_channel_id' => 43,
                'pricelist_id' => 1,
                'debtor_status_id' => 1,
                'currency' => 'ZAR',
            ];

            if (empty(trim($customer_data['contact']))) {
                $customer_data['contact'] = $customer['username'];
            }
            if (empty(trim($customer_data['company']))) {
                $customer_data['company'] = $customer['username'];
            }
            if (empty($customer_data['email'])) {
                $customer_data['email'] = $customer['email'];
            }

            // create account on erp
            $account_id = \DB::table('crm_accounts')->insertGetId($customer_data);
            // vd($account_id);
            $disable_customer_login = get_admin_setting('disable_customer_login');
            if (!$disable_customer_login) {
                // get wp user data
                $user = \DB::connection($store_db)->table($this->config->users_table_name)->where('id', $customer['id'])->get()->first();

                // create user on erp
                $user_data = [
                    'full_name' => $customer_data['contact'],
                    'account_id' => $account_id,
                    'active' => 1,
                    'username' => $user->user_login,
                    'password' => $user->user_pass,
                    'email' => $user->user_email,
                    'created_at' => date('Y-m-d H:i:s'),
                    'role_id' => 21,
                ];
                \DB::table('erp_users')->insert($user_data);
            }

            $this->addWordpressLink('customer', $account_id, $customer['id']);
        }

        return true;
    }

    public function exportCustomers()
    {
        try {
            $store_db = $this->config->store_db;

            $linked_customers = \DB::table('crm_wordpress_links')
            ->where('integration_id', $this->config->id)
            ->where('type', 'customer')
            ->get();

            // vd($linked_customers);

            $erp_customers = \DB::table('crm_accounts')
            ->select('crm_accounts.*', 'erp_users.username', 'erp_users.password')
            ->leftJoin('erp_users', 'crm_accounts.id', '=', 'erp_users.account_id')
            ->where('crm_accounts.partner_id', 1)
            ->where('crm_accounts.id', '!=', 1)
            ->where('crm_accounts.status', '!=', 'Deleted')
            ->whereIn('crm_accounts.type', ['customer', 'reseller'])
            ->get();
            // dd($erp_customers);

            foreach ($erp_customers as $erp_customer) {
                // vd($erp_customer->id);
                $wp_id = $linked_customers->where('erp_value', $erp_customer->id)->pluck('wordpress_id')->first();
                // vd($wp_id);
                if ($wp_id) {
                    \DB::connection($store_db)->table($this->config->users_table_name)->where('id', $wp_id)->update(['user_pass' => $erp_customer->password]);
                } else {
                    if (!empty($erp_customer->username) && filter_var($erp_customer->username, FILTER_VALIDATE_EMAIL)) {
                        try {
                            // Customer data
                            $customerData = [
                                'email' => $erp_customer->username,
                                'phone' => $erp_customer->phone,
                                'first_name' => $erp_customer->contact,
                                'last_name' => $erp_customer->company,
                                'username' => $erp_customer->username,
                                'password' => $erp_customer->password,
                            ];

                            // Create the customer
                            $customer = $this->wc->post('customers', $customerData);
                            if ($customer['id']) {
                                //update password
                                \DB::connection($store_db)->table($this->config->users_table_name)->where('id', $customer['id'])->update(['user_pass' => $erp_customer->password]);
                                //create link
                                $this->addWordpressLink('customer', $erp_customer->id, $customer['id']);
                            }
                        } catch (\Throwable $ex) {
                        }
                    }
                }
            }
        } catch (\Throwable $ex) {
        }
    }

    public function getCustomersById($id)
    {
        return $this->wc->get('customers/'.$id);
    }

    public function getCustomerErpId($id)
    {
        return \DB::table('crm_wordpress_links')
        ->where('integration_id', $this->config->id)
        ->where('type', 'customer')
        ->where('wordpress_id', $id)
        ->pluck('erp_value')
        ->first();
    }
}
