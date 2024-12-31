<?php

class Bulkhub
{
    public function getBulkhubId($type, $erp_id)
    {
        return \DB::connection('default')->table('crm_bulkhub_links')->where('type', $type)->where('erp_id', $erp_id)->pluck('bulkhub_id')->first();
    }

    public function getErpId($type, $bulkhub_id)
    {
        return \DB::connection('default')->table('crm_bulkhub_links')->where('type', $type)->where('bulkhub_id', $bulkhub_id)->pluck('erp_id')->first();
    }

    public function getAccountId($bulkhub_user_id)
    {
        $erp_user_id = $this->getErpId('user', $bulkhub_user_id);
        if (! $erp_user_id) {
            return false;
        } else {
            $account_id = \DB::table('erp_users')->where('id', $erp_user_id)->pluck('account_id')->first();
        }

        return $account_id;
    }

    public function addBulkhubLink($type, $erp_id, $bulkhub_id, $extra_fields = [])
    {
        $data = [
            'type' => $type,
            'erp_id' => $erp_id,
            'bulkhub_id' => $bulkhub_id,
        ];
        foreach ($extra_fields as $k => $v) {
            $data[$k] = $v;
        }
        \DB::connection('default')->table('crm_bulkhub_links')->updateOrInsert($data, $data);
    }

    public function removeBulkhubLink($where)
    {
        \DB::connection('default')->table('crm_bulkhub_links')->where($where)->delete();
    }

    public function exportUser($account_id)
    {
        $now = date('Y-m-d H:i:s');
        $user = \DB::connection('default')->table('erp_users')->where('account_id', $account_id)->get()->first();
        if (! $user) {
            return false;
        }
        $bulkhub_user_id = $this->getBulkhubId('user', $user->id);
        if ($bulkhub_user_id) {
            return $bulkhub_user_id;
        }

        $account = dbgetaccount($account_id);
        if ($account->partner_id != 1) {
            return false;
        }

        if (isset($account->email) && filter_var($account->email, FILTER_VALIDATE_EMAIL)) {

            $exists = \DB::connection('bulkhub')->table('users')->where('email', $account->email)->count();
            if (! $exists) {
                $email = $account->email;
            }
        }
        if (! $email) {
            return false;
        }

        $data = [
            'name' => $account->company,
            'email' => $email,
            'password' => $user->password,
            'status' => 1,
            'phone' => $account->phone,
            'country_id' => 204,
            'state_id' => 936,
            'city_id' => 131230,
            'zip_code' => '0081',
            'address' => $account->address,
            'is_vendor' => ($account->type == 'reseller') ? 1 : 0,
            'email_verified' => 0,
            'agree_policy' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $bulkhub_user_id = \DB::connection('bulkhub')->table('users')->insertGetId($data);

        $this->addBulkhubLink('user', $user->id, $bulkhub_user_id);
        if ($account->type == 'reseller') {
            $vendor_data = [
                'user_id' => $bulkhub_user_id,
                'phone' => $account->phone,
                'email' => $account->email,
                'shop_name' => $account->company,
                'slug' => slug_string($account->company),
                'open_at' => '09:00',
                'closed_at' => '17:00',
                'address' => $account->address,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $bulkhub_vendor_id = \DB::connection('bulkhub')->table('vendors')->insertGetId($vendor_data);
            $this->addBulkhubLink('vendor', $account->id, $bulkhub_vendor_id);
        }

        return $bulkhub_user_id;
    }

    public function importUser($bulkhub_user_id)
    {
        $user = \DB::connection('bulkhub')->table('users')->where('id', $bulkhub_user_id)->get()->first();
        $erp_user_id = $this->getErpId('user', $user->id);
        if ($erp_user_id) {
            if ($user->is_vendor) {
                $account_id = \DB::connection('default')->table('erp_users')->where('id', $erp_user_id)->pluck('account_id')->first();
                $bulkhub_vendor_id = \DB::connection('bulkhub')->table('vendors')->where('user_id', $bulkhub_user_id)->pluck('id')->first();
                $this->addBulkhubLink('vendor', $account_id, $bulkhub_vendor_id);
            } else {
                $account_id = \DB::connection('default')->table('erp_users')->where('id', $erp_user_id)->pluck('account_id')->first();
                $this->removeBulkhubLink(['type' => 'vendor', 'erp_id' => $account_id]);
            }

            return $erp_user_id;
        }
        // find user by account email
        $account_id = \DB::connection('default')->table('crm_accounts')->where('status', '!=', 'Deleted')->where('email', $user->email)->pluck('id')->first();
        if ($account_id) {

            $erp_user_id = \DB::connection('default')->table('erp_users')->where('account_id', $account_id)->pluck('id')->first();
            if ($erp_user_id) {
                if ($user->is_vendor) {
                    $bulkhub_vendor_id = \DB::connection('bulkhub')->table('vendors')->where('user_id', $bulkhub_user_id)->pluck('id')->first();
                    $this->addBulkhubLink('vendor', $account_id, $bulkhub_vendor_id);
                } else {
                    $account_id = \DB::connection('default')->table('erp_users')->where('id', $erp_user_id)->pluck('account_id')->first();
                    $this->removeBulkhubLink(['type' => 'vendor', 'erp_id' => $account_id]);
                }
                $this->addBulkhubLink('user', $erp_user_id, $bulkhub_user_id);

                return $erp_user_id;
            }
        }
        // if no match found, create customer
        $customer_data = [];
        $customer_data['company'] = $customer_data['contact'] = $user->name;
        $customer_data['email'] = $user->email;
        $customer_data['phone'] = $user->phone;

        $address_arr = [];
        $address_arr[] = $user->address;

        $address_arr[] = \DB::connection('bulkhub')->table('cities')->where('id', $user->city_id)->pluck('name')->first();
        $address_arr[] = \DB::connection('bulkhub')->table('country_states')->where('id', $user->state_id)->pluck('name')->first();
        $address_arr[] = \DB::connection('bulkhub')->table('cities')->where('id', $user->city_id)->pluck('name')->first();
        $address_arr[] = $user->zip_code;
        $address_arr = collect($address_arr)->filter()->toArray();
        $customer_data['address'] = implode(',', $address_arr);
        if ($user->is_vendor) {
            $customer_data['is_vendor'] = 1;
            $account_id = create_customer($customer_data, 'reseller');
            $bulkhub_vendor_id = \DB::connection('bulkhub')->table('vendors')->where('user_id', $bulkhub_user_id)->pluck('id')->first();
            $this->addBulkhubLink('vendor', $account_id, $bulkhub_vendor_id);
        } else {
            $account_id = create_customer($customer_data, 'customer');
        }

        $erp_user_id = \DB::connection('default')->table('erp_users')->where('account_id', $account_id)->pluck('id')->first();

        $this->addBulkhubLink('user', $erp_user_id, $bulkhub_user_id);

        return $erp_user_id;

    }

    public function getDepartmentId($department)
    {
        $category_id = \DB::connection('bulkhub')->table('categories')->where('name', $department)->pluck('id')->first();
        if (! $category_id) {

            $now = date('Y-m-d H:i:s');
            $data = [
                'name' => $department,
                'slug' => slug_string($department),
                'icon' => '',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $category_id = \DB::connection('bulkhub')->table('categories')->insertGetId($data);
        } else {
            \DB::connection('bulkhub')->table('categories')->where('id', $category_id)->update(['status' => 1]);
        }

        return $category_id;
    }

    public function getCategoryId($category, $department_id)
    {
        $category_id = \DB::connection('bulkhub')->table('sub_categories')->where('name', $category)->pluck('id')->first();

        return $category_id;
    }

    public function updateCategories($categories)
    {
        foreach ($categories as $category) {

            $department_id = $this->getDepartmentId($category->department);
            $data = [
                'name' => $category->name,
                'slug' => slug_string($category->name),
                'sort_order' => $category->sort_order,
                'category_id' => $department_id,
                'status' => 1,
            ];

            if (! $data['sort_order']) {
                $data['sort_order'] = $last_sort_order;
            }
            if (! $data['sort_order']) {
                $data['sort_order'] = 100;
            }

            $now = date('Y-m-d H:i:s');
            $bulkhub_category_id = $this->getBulkhubId('category', $category->id);
            if ($bulkhub_category_id) {
                $data['id'] = $bulkhub_category_id;
                $data['updated_at'] = $now;
                \DB::connection('bulkhub')->table('sub_categories')->where('id', $bulkhub_category_id)->update($data);
            } else {
                $data['created_at'] = $now;
                $bulkhub_category_id = \DB::connection('bulkhub')->table('sub_categories')->insertGetId($data);
                $this->addBulkhubLink('category', $category->id, $bulkhub_category_id);
            }
            $last_sort_order = $data['sort_order'];
        }
        $departments = \DB::connection('default')->table('crm_product_categories')->where('is_deleted', 0)->where('storefront_id', 3)->orderBy('sort_order')->pluck('department')->unique()->toArray();
        foreach ($departments as $i => $department) {
            $department_id = $this->getDepartmentId($department);
            \DB::connection('bulkhub')->table('categories')->where('id', $department_id)->update(['sort_order' => $i]);
        }

    }

    public function exportProduct($product)
    {

        $department_id = $this->getDepartmentId($product->department);
        $category_id = $this->getCategoryId($product->category, $department_id);

        $data = [
            'name' => $product->name,
            'short_name' => $product->name,
            'slug' => slug_string($product->name),
            'thumb_image' => ($product->upload_file) ? 'uploads/custom-images/'.$product->upload_file : '',
            'banner_image' => ($product->upload_file) ? 'uploads/custom-images/'.$product->upload_file : '',
            'vendor_id' => 0,
            'category_id' => $department_id,
            'sub_category_id' => $category_id,
            'brand_id' => 16,
            'qty' => $product->qty_on_hand,
            'short_description' => nl2br(strip_tags(str_replace('&nbsp;', ' ', $product->description))),
            'long_description' => $product->description,
            'sku' => $product->code,
            'seo_title' => $product->name,
            'seo_description' => nl2br(strip_tags(str_replace('&nbsp;', ' ', $product->description))),
            'price' => currency($product->selling_price_incl),
            'tax_id' => 3,
            'is_specification' => 0,
            'show_homepage' => 1,
            'status' => 1,
            'offer_price' => null,
            'is_flash_deal' => 0,
        ];

        // bulk pricing
        if ($product->special_price_incl > 0) {
            $data['offer_price'] = $product->special_price_incl;
            $data['is_flash_deal'] = 1;
        }

        $pricelist_item = \DB::table('crm_pricelist_items')->where('product_id', $product->id)->where('pricelist_id', 1)->get()->first();
        $data['price_3'] = 0;
        $data['price_6'] = $pricelist_item->price_tax_6;
        $data['price_12'] = $pricelist_item->price_tax_12;
        $data['price_24'] = $pricelist_item->price_tax_24;

        $now = date('Y-m-d H:i:s');
        $bulkhub_product_id = $this->getBulkhubId('product', $product->id);
        if ($bulkhub_product_id) {
            $data['id'] = $bulkhub_product_id;
            $data['updated_at'] = $now;
            \DB::connection('bulkhub')->table('products')->where('id', $bulkhub_product_id)->update($data);
        } else {
            $data['created_at'] = $now;
            $bulkhub_product_id = \DB::connection('bulkhub')->table('products')->insertGetId($data);
            $this->addBulkhubLink('product', $product->id, $bulkhub_product_id);
        }
        if ($product->upload_file) {
            $product_image_data_where = [
                'product_id' => $bulkhub_product_id,
                'image' => 'uploads/custom-images/'.$product->upload_file,
            ];
            $product_image_data = [
                'product_id' => $bulkhub_product_id,
                'image' => 'uploads/custom-images/'.$product->upload_file,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            \DB::connection('bulkhub')->table('product_galleries')->updateOrInsert($product_image_data_where, $product_image_data);
        }
    }

    public function copyProductImages()
    {
        $cmd = 'cp '.public_path().'/uploads/telecloud/71/* /home/bulkhubc/bulkhub.co.za/html/public/uploads/custom-images';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    }

    public function updateShopProducts()
    {
        $categories = \DB::connection('default')->table('crm_product_categories')->where('is_deleted', 0)->where('storefront_id', 3)->orderBy('sort_order')->get();
        $this->updateCategories($categories);
        $category_ids = $categories->pluck('id')->toArray();
        $products = \DB::connection('default')->table('crm_products')
            ->select('crm_products.*', 'crm_product_categories.name as category', 'crm_product_categories.department')
            ->join('crm_product_categories', 'crm_product_categories.id', '=', 'crm_products.product_category_id')
            ->where('status', '!=', 'Deleted')
            ->whereIn('product_category_id', $category_ids)
            ->get();
        foreach ($products as $product) {
            $this->exportProduct($product);
        }
        $this->copyProductImages();
    }

    public function addSellerProduct($order_line)
    {
        $product_category_id = 1154; // Bulkhub Vendor Products
        $bulkhub_product = \DB::connection('bulkhub')->table('products')->where('id', $order_line->product_id)->get()->first();
        $code = ($bulkhub_product->sku > '') ? $bulkhub_product->sku : $bulkhub_product->name;
        $code = str_replace(['_', ' ', '-'], '', strtolower($code));
        $data = [
            'type' => 'Stock',
            'frequency' => 'once off',
            'product_category_id' => $product_category_id,
            'code' => $code,
            'name' => $bulkhub_product->name,
            'description' => $bulkhub_product->long_description,
            'selling_price_incl' => $order_line->unit_price,
            'selling_price_excl' => $order_line->unit_price - $order_line->vat,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => get_system_user_id(),

        ];
        if ($order_line->seller_id > 0) {
            $data['vendor_id'] = $this->getErpId('vendor', $bulkhub_product->vendor_id); // account_id
        }
        $erp_product_id = \DB::table('crm_products')->insertGetId($data);
        $this->addBulkhubLink('product', $erp_product_id, $bulkhub_product->id);
    }

    public function importOrders()
    {
        return false;
        $orders = \DB::connection('bulkhub')->table('orders')->get();
        $order_ids = [];
        foreach ($orders as $order) {

            $erp_order_id = $this->getErpId('order', $order->id);
            if ($erp_order_id) {
                continue;
            }
            $order_ids[] = $order;
        }

        foreach ($orders as $order) {
            try {

                $erp_order_id = $this->getErpId('order', $order->id);
                if ($erp_order_id) {
                    continue;
                }

                $account_id = $this->getAccountId($order->user_id);
                if (! $account_id) {
                    $this->importUser($order->user_id);
                    $account_id = $this->getAccountId($order->user_id);
                }
                if (! $account_id) {
                    debug_email('Bulkhub customer not found, bulkhub order id - '.$order->id);

                    continue;
                }

                // create document
                $db = new DBEvent;
                $data = [
                    'docdate' => date('Y-m-d', strtotime($order->created_at)),
                    'doctype' => 'Tax Invoice',
                    'doctype' => 'Tax Invoice',
                    'completed' => 1,
                    'account_id' => $account_id,
                    'total' => $order->amount_real_currency,
                    'tax' => $order->order_vat,
                    'reference' => 'Bulkhub Order #'.$order->id,
                    'billing_type' => '',
                    'qty' => [],
                    'price' => [],
                    'full_price' => [],
                    'product_id' => [],
                ];

                if (! $order->payment_status) {
                    $data['doctype'] = 'Order';
                }

                //$data['doctype'] = 'Quotation';

                $lines_count = 0;
                $heavy_shipping_qty = 0;
                $heavy_item_description = 'R200 per item';
                $order_lines = \DB::connection('bulkhub')->table('order_products')->where('order_id', $order->id)->get();
                foreach ($order_lines as $line_item) {

                    $heavy_item_shipping = DB::connection('bulkhub')->table('products')->where('heavy_item_shipping', 1)->where('id', $line_item->product_id)->count();
                    if ($heavy_item_shipping) {
                        $product_shipping_total = $line_item->qty * 200;
                        $heavy_shipping_qty += $line_item->qty;
                        $heavy_item_description .= PHP_EOL.$line_item->product_name.' x '.$line_item->qty.':'.$product_shipping_total;
                    }
                    $product_id = $this->getErpId('product', $line_item->product_id);
                    if (! $product_id) {
                        $this->addSellerProduct($line_item);
                        $product_id = $this->getErpId('product', $line_item->product_id);
                    }

                    if (! $product_id) {
                        continue;
                    }
                    $lines_count++;
                    $data['product_id'][] = $product_id;
                    $data['qty'][] = $line_item->qty;
                    $data['price'][] = $line_item->unit_price - $line_item->vat;
                    $data['full_price'][] = $line_item->unit_price - $line_item->vat;
                    $data['description'][] = $line_item->product_name;

                }

                if (count($order_lines) != $lines_count) {
                    debug_email('Bulkhub product not found, bulkhub order id - '.$order->id);

                    continue;
                }

                // add shipping cost as product
                if ($order->shipping_cost > 0) {
                    $data['product_id'][] = 831;
                    $data['description'][] = 'Standard shipping (excl Heavy items)';
                    $data['qty'][] = 1;
                    $data['price'][] = $order->shipping_cost / 1.15;
                    $data['full_price'][] = $order->shipping_cost / 1.15;
                }
                if ($order->heavy_shipping_cost > 0) {
                    $data['product_id'][] = 1403;
                    $data['description'][] = $heavy_item_description;
                    $data['qty'][] = $heavy_shipping_qty;
                    $data['price'][] = 200 / 1.15;
                    $data['full_price'][] = 200 / 1.15;
                }

                $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

                if (! is_array($result) || empty($result['id'])) {
                    debug_email('Bulkhub document save error, bulkhub order id - '.$order->id, $result);

                    continue;
                }

                $document_id = $result['id'];

                if ($document_id) {
                    $link_data = [
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'payment_id' => $order->transection_id,
                    ];
                    $this->addBulkhubLink('order', $document_id, $order->id, $link_data);
                }
            } catch (\Throwable $ex) {
                //dd($ex->getMessage(),$ex->getTraceAsString());
                exception_email($ex, 'Bulkhub order save error, bulkhub order id - '.$order->id);
            }
        }
    }

    public function updateCountryStateCity()
    {
        // https://github.com/dr5hn/countries-states-cities-database/tree/master
        $now = date('Y-m-d H:i:s');
        $list = file_get_contents(public_path().'countries_states_cities.json');
        $list = json_decode($list);
        $list = collect($list);

        $countries = $list;
        \DB::connection('bulkhub')->table('cities')->truncate();
        \DB::connection('bulkhub')->table('country_states')->truncate();
        \DB::connection('bulkhub')->table('countries')->truncate();

        foreach ($countries as $country) {

            $data = [
                'id' => $country->id,
                'name' => $country->name,
                'slug' => slug_string($country->name),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            \DB::connection('bulkhub')->table('countries')->insert($data);
            $states = collect($country->states);

            foreach ($states as $state) {

                $data = [
                    'id' => $state->id,
                    'name' => $state->name,
                    'slug' => slug_string($state->name),
                    'status' => 1,
                    'country_id' => $country->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                \DB::connection('bulkhub')->table('country_states')->insert($data);
                $cities = collect($state->cities);

                $cities = $cities->pluck('name', 'id');
                foreach ($cities as $city_id => $city) {
                    $data = [
                        'id' => $city_id,
                        'name' => $city,
                        'slug' => slug_string($city),
                        'status' => 1,
                        'country_state_id' => $state->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    \DB::connection('bulkhub')->table('cities')->insert($data);

                }
            }
        }
    }
}
