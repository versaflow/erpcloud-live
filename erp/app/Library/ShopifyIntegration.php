<?php

use Shopify\Auth\FileSessionStorage;
use Shopify\Clients\Rest;
use Shopify\Context;

//https://shopify.dev/api/admin-rest
//https://shopify.dev/api/admin-rest/2022-10/resources/product
class ShopifyIntegration
{
    public $session;

    public $client;

    public $location_id;

    public $store_url;

    public $api_key;

    public $api_secret;

    public $access_token;

    public function __construct($integration_id)
    {
        $integration = \DB::table('crm_shopify_integrations')->where('id', $integration_id)->get()->first();

        $this->store_url = $integration->store_url;
        $this->api_key = $integration->api_key;
        $this->api_secret = $integration->api_secret;
        $this->access_token = $integration->access_token;
        if (trim($integration->allowed_categories) > '') {
            $allowed_categories = explode(',', $integration->allowed_categories);
            $this->allowed_categories = collect($allowed_categories)->filter()->toArray();
        } else {
            $integration->allowed_categories = false;
        }

        $basic_access_scopes = ['read_inventory', 'write_inventory', 'read_all_orders', 'read_orders', 'write_orders', 'read_draft_orders', 'write_draft_orders', 'read_customers', 'write_customers', 'read_products', 'write_products'];
        $access_scopes = [
            'read_all_orders',
            'read_assigned_fulfillment_orders',
            'write_assigned_fulfillment_orders',
            'read_checkouts',
            'write_checkouts',
            'read_content',
            'write_content',
            'read_customers',
            'write_customers',
            'read_customer_payment_methods',
            'read_discounts',
            'write_discounts',
            'read_draft_orders',
            'write_draft_orders',
            'read_files',
            'write_files',
            'read_fulfillments',
            'write_fulfillments',
            'read_gift_cards',
            'write_gift_cards',
            'read_inventory',
            'write_inventory',
            'read_legal_policies',
            'read_locales',
            'write_locales',
            'read_locations',
            'read_marketing_events',
            'write_marketing_events',
            'read_merchant_approval_signals',
            'read_merchant_managed_fulfillment_orders',
            'write_merchant_managed_fulfillment_orders',
            'read_orders',
            'write_orders',
            'read_payment_mandate',
            'write_payment_mandate',
            'read_payment_terms',
            'write_payment_terms',
            'read_price_rules',
            'write_price_rules',
            'read_products',
            'write_products',
            'read_product_listings',
            'read_publications',
            'write_publications',
            'read_purchase_options',
            'write_purchase_options',
            'read_reports',
            'write_reports',
            'read_resource_feedbacks',
            'write_resource_feedbacks',
            'read_script_tags',
            'write_script_tags',
            'read_shipping',
            'write_shipping',
            'read_shopify_payments_disputes',
            'read_shopify_payments_payouts',
            'read_own_subscription_contracts',
            'write_own_subscription_contracts',
            'read_returns',
            'write_returns',
            'read_themes',
            'write_themes',
            'read_translations',
            'write_translations',
            'read_third_party_fulfillment_orders',
            'write_third_party_fulfillment_orders',
            'read_users',
            'read_order_edits',
            'write_order_edits',
            'write_payment_gateways',
            'write_payment_sessions',
        ];

        Context::initialize(
            $this->api_key,
            $this->api_secret,
            $access_scopes,
            $this->store_url,
            new FileSessionStorage('/tmp/php_sessions')
        );
        $this->client = new Rest(
            $this->store_url,
            $this->access_token // shpat_***
        );

        $this->getInventoryLocation();

    }

    public function getShop()
    {
        return $this->client->get('shop');
    }

    public function getInventoryLocation()
    {

        $response = $this->client->get('locations');

        if ($response->getStatusCode() == 200) {
            $response_body = $response->getDecodedBody();
            $this->location_id = $response_body['locations']['0']['id'];
        }

        return $response;
    }

    public function getProducts()
    {
        $response = $this->client->get('products');

        if ($response->getStatusCode() == 200) {
            $product_list = $response->getDecodedBody();
            $serializedPageInfo = serialize($response->getPageInfo());
            //To get the next page.

            $pageInfo = unserialize($serializedPageInfo);
            while ($pageInfo != null && $pageInfo->hasNextPage()) {
                $response = $this->client->get('products', [], $pageInfo->getNextPageQuery());
                $serializedPageInfo = serialize($response->getPageInfo());

                $pageInfo = unserialize($serializedPageInfo);

                $product_list = array_merge_recursive($product_list, $response->getDecodedBody());
            }

            return $product_list;
        } else {
            return false;
        }
    }

    private function createProduct($product_id)
    {
        //https://shopify.dev/api/admin-rest/2022-10/resources/product

        // !IMPORTANT payfast requires sku to be set in specific format for subscriptions to work
        // subscriptions are limited to 1 subscription product per checkout
        // subscription product requires the subscription product tag for it to work with "Order limits" shopify app
        // https://apps.shopify.com/order-limits-minmaxify
        // https://support.payfast.co.za/portal/en/kb/articles/how-do-i-enable-subscription-payments-with-shopify
        // PF-MYREFERENCE-3-12-15000 sku

        $product = \DB::table('crm_products')->where('id', $product_id)->get()->first();

        $category = \DB::table('crm_product_categories')->where('id', $product->product_category_id)->get()->first();
        $category_name = $category->name;
        $vendor = session('instance')->name;

        $price_incl = \DB::table('crm_products')->where('id', $product_id)->pluck('selling_price_incl')->first();
        $manage_inventory = ($product->type == 'Stock' && ! $product->is_subscription) ? true : false;

        $sku = $product->code;

        $barcode = str_pad($product->id, 8, '0', STR_PAD_LEFT);
        if ($product->brand) {
            $vendor = $product->brand;
            $barcode = $product->code;
        }
        $tags = [$category_name];
        if ($product->is_subscription) {
            $sku = 'PF-'.strtoupper(str_replace('_', '', $product->code)).'-3-0-'.str_replace('.', '', currency($price_incl));
            $tags[] = 'Subscription';
        }

        $post_data = [
            'product' => [
                'title' => $product->name,
                'body_html' => ($product->description > '') ? $product->description : $product->name,
                'product_type' => $category_name,
                'vendor' => $vendor,
                'tags' => implode(',', $tags),
                'variant' => [
                    'title' => $product->name,
                    'barcode' => null,
                    'sku' => $sku,
                    'price' => currency($price_incl),
                    'compare_at_price' => currency($price_incl),
                    'taxable' => 1,
                    'requires_shipping' => $manage_inventory,
                    'inventory_management' => $manage_inventory ? 'shopify' : null,
                ],
            ],
        ];

        if ($product->upload_file) {
            $post_data['product']['images'] = [(object) ['src' => uploads_url(71).$product->upload_file]];
        }

        $response = $this->client->post('products', $post_data);

        if ($response->getStatusCode() == 201) {
            $response_body = $response->getDecodedBody();
            $meta = json_encode($response_body);
            $link_data = [
                'store_url' => $this->store_url,
                'type' => 'product',
                'erp_id' => $product_id,
                'shopify_id' => $response_body['product']['id'],
                'shopify_meta' => $meta,
            ];
            \DB::table('crm_shopify_links')->insert($link_data);

            if ($manage_inventory) {
                $this->setProductInventory($product_id, $product->qty_on_hand);
            }

            return true;
        }

        return false;
    }

    public function updateProduct($product_id)
    {

        //https://shopify.dev/api/admin-rest/2022-10/resources/product
        $linked_product = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $product_id)->where('type', 'product')->get()->first();
        if (! $linked_product) {
            return false;
        }
        $meta = $linked_product->shopify_meta;
        if (! $meta) {
            return false;
        }
        $meta = json_decode($meta, true);
        if (! $meta) {
            return false;
        }
        $product = \DB::table('crm_products')->where('id', $product_id)->get()->first();

        $category = \DB::table('crm_product_categories')->where('id', $product->product_category_id)->get()->first();
        $category_name = $category->name;

        $vendor = session('instance')->name;
        $barcode = str_pad($product->id, 8, '0', STR_PAD_LEFT);
        if ($product->brand) {
            $vendor = $product->brand;
            $barcode = $product->code;
        }

        $price_incl = \DB::table('crm_products')->where('id', $product_id)->pluck('selling_price_incl')->first();
        $manage_inventory = ($product->type == 'Stock' && ! $product->is_subscription) ? true : false;
        $sku = $product->code;
        $tags = [$category_name];
        if ($product->is_subscription) {
            $sku = 'PF-'.strtoupper(str_replace('_', '', $product->code)).'-3-0-'.str_replace('.', '', currency($price_incl));
            $tags[] = 'Subscription';
        }

        $variant_id = $meta['product']['variants'][0]['id'];
        $shopify_id = $meta['product']['id'];
        $post_data = [
            'product' => [
                'id' => $shopify_id,
                'title' => $product->name,
                'body_html' => ($product->description > '') ? $product->description : $product->name,
                'product_type' => $category_name,
                'product_category' => ['ProductTaxonomyNodeId' => 4342],
                'vendor' => $vendor,
                'tags' => implode(',', $tags),
                'variant' => [
                    'id' => $variant_id,
                    'title' => $product->name,
                    'barcode' => null,
                    'sku' => $sku,
                    'price' => currency($price_incl),
                    'compare_at_price' => currency($price_incl),
                    'taxable' => 1,
                    'requires_shipping' => $manage_inventory,
                    'inventory_management' => $manage_inventory ? 'shopify' : null,
                ],
            ],
        ];
        /*
        if(is_dev() && $product->is_subscription){
             // add variant option for payfast period
             $post_data['product']['variant']['position'] = 1;
             $post_data['product']['variant']['option1'] = 'Monthly';
             $post_data['product']['variant']['option2'] = null;
             $post_data['product']['variant']['sku'] = 'PF-'.strtoupper(str_replace('_','',$product->code)).'-3-0-'.str_replace('.','',currency($price_incl));

            $annual_variant = [
                'title' => $product->name.' Annual',
                'barcode' => null,
                'sku' => null,
                'price' => currency($price_incl*12),
                'compare_at_price' => currency($price_incl*12),
                'taxable' => 1,
                'requires_shipping' => $manage_inventory,
                'inventory_management' => $manage_inventory ? "shopify" : null,
                'position' => 2,
                'option1' => 'Yearly',
                'option2' => null,
                'sku' => 'PF-'.strtoupper(str_replace('_','',$product->code)).'-6-0-'.str_replace('.','',currency($price_incl*12))
            ];

            if(!empty($meta["product"]["variants"][1]["id"])){
                $annual_variant['id'] = $meta["product"]["variants"][1]["id"];
            }
            $post_data['product']['variants'][] = $post_data['product']['variant'];
            $post_data['product']['variants'][] = $annual_variant;
            unset($post_data['product']['variant']);

        }
  */

        if ($product->upload_file) {
            $post_data['product']['images'] = [(object) ['src' => uploads_url(71).$product->upload_file]];
        }

        $response = $this->client->put('products/'.$shopify_id, $post_data);

        if ($response->getStatusCode() == 200) {
            $response_body = $response->getDecodedBody();

            $meta = json_encode($response_body);
            $link_data = [
                'type' => 'product',
                'erp_id' => $product_id,
                'shopify_id' => $response_body['product']['id'],
                'shopify_meta' => $meta,
            ];
            \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('shopify_id', $shopify_id)->where('type', 'product')->update($link_data);

            if ($manage_inventory) {
                $this->setProductInventory($product_id, $product->qty_on_hand);
            }

            return true;
        }

        /*
         if(is_dev()){
          }
       */
        return false;
    }

    public function deleteProduct($product_id)
    {
        $linked_product = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $product_id)->where('type', 'product')->get()->first();
        if (! $linked_product) {
            return false;
        }

        $response = $this->client->delete('products/'.$linked_product->shopify_id);

        if ($response->getStatusCode() == 200) {
            \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('shopify_id', $linked_product->shopify_id)->where('type', 'product')->delete();

            return true;
        }

        return false;
    }

    private function deleteProductByShopifyId($shopify_id)
    {
        $response = $this->client->delete('products/'.$shopify_id);

        if ($response->getStatusCode() == 200) {

            \DB::table('crm_shopify_links')
                ->where('store_url', $this->store_url)
                ->where('type', 'product')
                ->where('shopify_id', $shopify_id)->delete();

            return true;
        }

        return false;
    }

    private function createBundle($bundle_id)
    {
        //https://shopify.dev/api/admin-rest/2022-10/resources/product

        // !IMPORTANT payfast requires sku to be set in specific format for subscriptions to work
        // subscriptions are limited to 1 subscription product per checkout
        // subscription product requires the subscription product tag for it to work with "Order limits" shopify app
        // https://apps.shopify.com/order-limits-minmaxify
        // https://support.payfast.co.za/portal/en/kb/articles/how-do-i-enable-subscription-payments-with-shopify
        // PF-MYREFERENCE-3-12-15000 sku

        $bundle = \DB::table('crm_product_bundles')->where('id', $bundle_id)->get()->first();
        $bundle_lines = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle_id)->get();
        $first_product = $bundle_lines->first();

        $category = \DB::table('crm_product_categories')->where('id', $first_product->product_category_id)->get()->first();
        $category_name = $category->name;
        $vendor = session('instance')->name;

        $price_incl = $bundle->total;

        // check that all products in bundle are stock
        $bundle_descriptions = [];

        foreach ($bundle_lines as $bundle_line) {

            $product = \DB::table('crm_products')->where('id', $bundle_line->product_id)->get()->first();
            if (! isset($bundle_qty)) {
                $bundle_qty = $product->qty_on_hand;
            } else {
                if ($product->qty_on_hand < $bundle_qty) {
                    $bundle_qty = $product->qty_on_hand;
                }
            }

            $manage_inventory = ($product->type == 'Stock' && ! $product->is_subscription) ? true : false;
            if (! $manage_inventory) {
                return false;
            }
            $shopify_meta = \DB::table('crm_shopify_links')->where('type', 'product')->where('erp_id', $bundle_line->product_id)->pluck('shopify_meta')->first();
            $shopify_meta = json_decode($shopify_meta);
            $link = 'https://'.$this->store_url.'/products/'.$shopify_meta->product->handle;
            $bundle_descriptions[] = $product->name.' - Quantity: '.$bundle_line->qty.' - '.$link;
        }
        $bundle_description = implode('<br>', $bundle_descriptions);
        $manage_inventory = true;

        $sku = 'BUNDLE'.$bundle->id;
        $barcode = str_pad($bundle->id, 8, '0', STR_PAD_LEFT);
        $tags = [$category_name, 'Bundle'];

        $post_data = [
            'product' => [
                'title' => 'BUNDLE '.$bundle->name,
                'body_html' => $bundle_description,
                'product_type' => $category_name,
                'vendor' => $vendor,
                'tags' => implode(',', $tags),
                'variant' => [
                    'title' => 'BUNDLE '.$bundle->name,
                    'barcode' => null,
                    'sku' => null,
                    'price' => currency($price_incl),
                    'compare_at_price' => currency($price_incl),
                    'taxable' => 1,
                    'requires_shipping' => $manage_inventory,
                    'inventory_management' => $manage_inventory ? 'shopify' : null,

                ],
            ],
        ];

        if ($first_product->upload_file) {
            $post_data['product']['images'] = [(object) ['src' => uploads_url(71).$first_product->upload_file]];
        }

        $response = $this->client->post('products', $post_data);

        if ($response->getStatusCode() == 201) {
            $response_body = $response->getDecodedBody();
            $meta = json_encode($response_body);
            $link_data = [
                'store_url' => $this->store_url,
                'type' => 'bundle',
                'erp_id' => $bundle_id,
                'shopify_id' => $response_body['product']['id'],
                'shopify_meta' => $meta,
            ];
            \DB::table('crm_shopify_links')->insert($link_data);

            if ($manage_inventory) {
                $this->setBundleInventory($bundle_id, $bundle_qty);
            }

            return true;
        }

        return false;
    }

    private function updateBundle($bundle_id)
    {
        //https://shopify.dev/api/admin-rest/2022-10/resources/product

        // !IMPORTANT payfast requires sku to be set in specific format for subscriptions to work
        // subscriptions are limited to 1 subscription product per checkout
        // subscription product requires the subscription product tag for it to work with "Order limits" shopify app
        // https://apps.shopify.com/order-limits-minmaxify
        // https://support.payfast.co.za/portal/en/kb/articles/how-do-i-enable-subscription-payments-with-shopify
        // PF-MYREFERENCE-3-12-15000 sku
        $linked_product = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $bundle_id)->where('type', 'bundle')->get()->first();
        if (! $linked_product) {
            return false;
        }

        $meta = $linked_product->shopify_meta;
        if (! $meta) {
            return false;
        }
        $meta = json_decode($meta, true);
        if (! $meta) {
            return false;
        }
        $bundle = \DB::table('crm_product_bundles')->where('id', $bundle_id)->get()->first();
        $bundle_lines = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle_id)->get();
        $first_product = $bundle_lines->first();

        $category = \DB::table('crm_product_categories')->where('id', $first_product->product_category_id)->get()->first();
        $category_name = $category->name;
        $vendor = session('instance')->name;

        $price_incl = $bundle->total;

        // check that all products in bundle are stock
        $bundle_descriptions = [];

        foreach ($bundle_lines as $bundle_line) {

            $product = \DB::table('crm_products')->where('id', $bundle_line->product_id)->get()->first();

            if (! isset($bundle_qty)) {
                $bundle_qty = $product->qty_on_hand;
            } else {
                if ($product->qty_on_hand < $bundle_qty) {
                    $bundle_qty = $product->qty_on_hand;
                }
            }

            $manage_inventory = ($product->type == 'Stock' && ! $product->is_subscription) ? true : false;
            if (! $manage_inventory) {
                return false;
            }
            $shopify_meta = \DB::table('crm_shopify_links')->where('type', 'product')->where('erp_id', $bundle_line->product_id)->pluck('shopify_meta')->first();
            $shopify_meta = json_decode($shopify_meta);
            $link = 'https://'.$this->store_url.'/products/'.$shopify_meta->product->handle;
            $bundle_descriptions[] = $product->name.' - Quantity: '.$bundle_line->qty.' - '.$link;
        }

        $bundle_description = implode('<br>', $bundle_descriptions);

        $manage_inventory = true;

        $sku = 'BUNDLE'.$bundle->id;
        $barcode = str_pad($bundle->id, 8, '0', STR_PAD_LEFT);
        $tags = [$category_name, 'Bundle'];

        $variant_id = $meta['product']['variants'][0]['id'];
        $shopify_id = $meta['product']['id'];

        $post_data = [
            'product' => [
                'id' => $shopify_id,
                'title' => 'BUNDLE '.$bundle->name,
                'body_html' => $bundle_description,
                'product_type' => $category_name,
                'vendor' => $vendor,
                'tags' => implode(',', $tags),
                'variant' => [
                    'id' => $variant_id,
                    'title' => 'BUNDLE '.$bundle->name,
                    'barcode' => null,
                    'sku' => null,
                    'price' => currency($price_incl),
                    'compare_at_price' => currency($price_incl),
                    'taxable' => 1,
                    'requires_shipping' => $manage_inventory,
                    'inventory_management' => $manage_inventory ? 'shopify' : null,

                ],
            ],
        ];

        if ($first_product->upload_file) {
            $post_data['product']['images'] = [(object) ['src' => uploads_url(71).$first_product->upload_file]];
        }

        $response = $this->client->put('products/'.$shopify_id, $post_data);

        if ($response->getStatusCode() == 200) {
            $response_body = $response->getDecodedBody();

            $meta = json_encode($response_body);
            $link_data = [
                'type' => 'bundle',
                'erp_id' => $bundle_id,
                'shopify_id' => $response_body['product']['id'],
                'shopify_meta' => $meta,
            ];
            \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('shopify_id', $shopify_id)->where('type', 'product')->update($link_data);

            if ($manage_inventory) {
                $this->setBundleInventory($bundle_id, $bundle_qty);
            }

            return true;
        }

        return false;
    }

    private function deleteBundleByShopifyId($shopify_id)
    {
        $response = $this->client->delete('products/'.$shopify_id);

        if ($response->getStatusCode() == 200) {

            \DB::table('crm_shopify_links')
                ->where('store_url', $this->store_url)
                ->where('type', 'bundle')
                ->where('shopify_id', $shopify_id)->delete();

            return true;
        }

        return false;
    }

    private function setProductInventory($product_id, $qty_on_hand, $location_id = false)
    {
        $meta = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $product_id)->where('type', 'product')->pluck('shopify_meta')->first();
        if (! $meta) {
            return false;
        }
        $meta = json_decode($meta, true);
        if (! $meta) {
            return false;
        }

        $inventory_item_id = $meta['product']['variants'][0]['inventory_item_id'];

        if (! $inventory_item_id) {
            return false;
        }
        if (! $location_id) {
            $location_id = $this->location_id;
        }

        $post_data = [
            'available' => $qty_on_hand,
            'inventory_item_id' => $inventory_item_id,
            'location_id' => $location_id,
        ];

        $response = $this->client->post('inventory_levels/set', $post_data);

        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }

    private function setBundleInventory($bundle_id, $qty_on_hand, $location_id = false)
    {
        $meta = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $bundle_id)->where('type', 'bundle')->pluck('shopify_meta')->first();
        if (! $meta) {
            return false;
        }
        $meta = json_decode($meta, true);
        if (! $meta) {
            return false;
        }

        $inventory_item_id = $meta['product']['variants'][0]['inventory_item_id'];

        if (! $inventory_item_id) {
            return false;
        }
        if (! $location_id) {
            $location_id = $this->location_id;
        }

        $post_data = [
            'available' => $qty_on_hand,
            'inventory_item_id' => $inventory_item_id,
            'location_id' => $location_id,
        ];

        $response = $this->client->post('inventory_levels/set', $post_data);

        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }

    // USE COLLECTIONS TO GROUP PRODUCTS, TO ADD COLLECTIONS TO NAVIGATION - PRODUCT CATEGORIES
    public function getCollections()
    {
        $response = $this->client->get('smart_collections');

        if ($response->getStatusCode() == 200) {
            $smart_collections = $response->getDecodedBody();
            $serializedPageInfo = serialize($response->getPageInfo());
            //To get the next page.

            $pageInfo = unserialize($serializedPageInfo);
            while ($pageInfo != null && $pageInfo->hasNextPage()) {
                $response = $this->client->get('smart_collections', [], $pageInfo->getNextPageQuery());
                $serializedPageInfo = serialize($response->getPageInfo());

                $pageInfo = unserialize($serializedPageInfo);

                $smart_collections = array_merge_recursive($smart_collections, $response->getDecodedBody());
            }

            return $smart_collections;
        } else {
            return false;
        }
    }

    private function createCollection($category_id)
    {
        $category = \DB::table('crm_product_categories')->where('id', $category_id)->get()->first();

        $post_data = [
            'smart_collection' => [
                'title' => $category->name,
                'handle' => string_clean($category->name),
                'body_html' => $category->text_ad,
                'sort_order' => 'manual',
                'rules' => [
                    (object) [
                        'column' => 'type',
                        'relation' => 'equals',
                        'condition' => $category->name,
                    ],
                ],
            ],
        ];

        $response = $this->client->post('smart_collections', $post_data);

        if ($response->getStatusCode() == 201) {
            $response_body = $response->getDecodedBody();
            $meta = json_encode($response_body);
            $link_data = [
                'store_url' => $this->store_url,
                'type' => 'category',
                'erp_id' => $category_id,
                'shopify_id' => $response_body['smart_collection']['id'],
                'shopify_meta' => $meta,
            ];
            \DB::table('crm_shopify_links')->insert($link_data);

            return true;
        } else {
            return false;
        }
    }

    public function updateCollection($category_id)
    {
        $linked_category = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $category_id)->where('type', 'category')->get()->first();
        if (! $linked_category) {
            return false;
        }
        $meta = $linked_category->shopify_meta;
        if (! $meta) {
            return false;
        }
        $meta = json_decode($meta, true);
        if (! $meta) {
            return false;
        }

        $shopify_id = $meta['smart_collection']['id'];

        $category = \DB::table('crm_product_categories')->where('id', $category_id)->get()->first();

        $post_data = [
            'smart_collection' => [
                'id' => $shopify_id,
                'title' => $category->name,
                'handle' => string_clean($category->name),
                'sort_order' => 'manual',
                'rules' => [
                    (object) [
                        'column' => 'type',
                        'relation' => 'equals',
                        'condition' => $category->name,
                    ],
                ],
            ],
        ];

        $response = $this->client->put('smart_collections/'.$shopify_id, $post_data);

        if ($response->getStatusCode() == 200) {
            $response_body = $response->getDecodedBody();

            $meta = json_encode($response_body);
            $link_data = [
                'type' => 'category',
                'erp_id' => $category_id,
                'shopify_id' => $response_body['smart_collection']['id'],
                'shopify_meta' => $meta,
            ];
            \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('shopify_id', $shopify_id)->where('type', 'category')->update($link_data);

            return true;
        } else {

            return false;
        }

    }

    public function deleteCollection($category_id)
    {
        $linked_category = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $category_id)->where('type', 'category')->get()->first();
        if (! $linked_category) {
            return false;
        }

        $response = $this->client->delete('smart_collections/'.$linked_category->shopify_id);

        if ($response->getStatusCode() == 200) {
            \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('shopify_id', $linked_category->shopify_id)->where('type', 'category')->delete();

            return true;
        }

        return false;
    }

    public function sortCollection($category_id)
    {
        $shopify_id = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $category_id)->where('type', 'category')->pluck('shopify_id')->first();
        if (! $shopify_id) {
            return false;
        }
        $products = \DB::table('crm_products')->where('product_category_id', $category_id)->orderBy('sort_order')->pluck('id')->toArray();
        $product_ids = [];
        foreach ($products as $id) {
            $shopify_product_id = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('erp_id', $id)->where('type', 'product')->pluck('shopify_id')->first();
            if ($shopify_product_id) {
                $product_ids[] = $shopify_product_id;
            }
        }
        $data = [
            'smart_collection_id' => $shopify_id,
            'products' => $product_ids,
            'sort_order' => 'manual',
        ];

        $response = $this->client->put('smart_collections/'.$shopify_id.'/order', $data);

        return $response->getStatusCode();
    }

    public function sortCollections()
    {
        $category_ids = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'category')->pluck('erp_id')->toArray();
        foreach ($category_ids as $id) {
            $result = $this->sortCollection($id);
        }
    }

    public function deleteCollectionByShopifyId($shopify_id)
    {
        $response = $this->client->delete('smart_collections/'.$shopify_id);

        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 404) {
            \DB::table('crm_shopify_links')
                ->where('store_url', $this->store_url)
                ->where('type', 'category')
                ->where('shopify_id', $shopify_id)->delete();

            return true;
        }

        return false;
    }

    public function updateShop()
    {

        if (is_array($this->allowed_categories) && count($this->allowed_categories) > 0) {
            $category_ids = \DB::table('crm_product_categories')
                ->whereIn('id', $this->allowed_categories)
                ->where('not_for_sale', 0)
                ->where('customer_access', 1)
                ->where('is_deleted', 0)
                ->pluck('id')->toArray();
        } else {
            $category_ids = \DB::table('crm_product_categories')
                ->where('not_for_sale', 0)
                ->where('customer_access', 1)
                ->where('is_deleted', 0)
                ->pluck('id')->toArray();
        }

        $product_ids = \DB::table('crm_products')
            ->whereIn('product_category_id', $category_ids)
            ->where('not_for_sale', 0)
            ->where('status', 'Enabled')
            ->pluck('id')->toArray();

        // delete based on allowed categories

        $deleted_category_ids = \DB::table('crm_shopify_links')
            ->whereNotIn('erp_id', $category_ids)
            ->where('store_url', $this->store_url)
            ->where('type', 'category')
            ->pluck('shopify_id')->toArray();

        foreach ($deleted_category_ids as $id) {
            $this->deleteCollectionByShopifyId($id);
        }

        $category_ids = \DB::table('crm_product_categories')
            ->whereIn('id', $category_ids)
            ->where('not_for_sale', 0)
            ->where('customer_access', 1)
            ->where('is_deleted', 0)
            ->pluck('id')->toArray();

        $product_ids = \DB::table('crm_products')
            ->whereIn('product_category_id', $category_ids)
            ->where('not_for_sale', 0)
            ->where('status', 'Enabled')
            ->pluck('id')->toArray();

        $deleted_product_ids = \DB::table('crm_shopify_links')
            ->whereNotIn('erp_id', $product_ids)
            ->where('store_url', $this->store_url)
            ->where('type', 'product')
            ->pluck('shopify_id')->toArray();

        foreach ($deleted_product_ids as $id) {
            $this->deleteProductByShopifyId($id);
        }

        // get products/collections - delete unlinked resources
        $linked_product_ids = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'product')->pluck('shopify_id')->toArray();
        $linked_bundle_ids = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'bundle')->pluck('shopify_id')->toArray();
        $products = $this->getProducts();

        $shopify_ids = collect($products['products'])->pluck('id')->toArray();

        foreach ($shopify_ids as $id) {
            if (! in_array($id, $linked_product_ids) && ! in_array($id, $linked_bundle_ids)) {
                $this->deleteProductByShopifyId($id);
            }
        }

        foreach ($linked_product_ids as $id) {
            if (! in_array($id, $shopify_ids)) {
                \DB::table('crm_shopify_links')
                    ->where('store_url', $this->store_url)
                    ->where('type', 'product')
                    ->where('shopify_id', $id)->delete();
            }
        }

        foreach ($linked_bundle_ids as $id) {
            if (! in_array($id, $shopify_ids)) {
                \DB::table('crm_shopify_links')
                    ->where('store_url', $this->store_url)
                    ->where('type', 'bundle')
                    ->where('shopify_id', $id)->delete();
            }
        }

        $linked_category_ids = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'category')->pluck('shopify_id')->toArray();
        $collections = $this->getCollections();

        $bundle_collection_exists = collect($collections['smart_collections'])->where('title', 'Bundles')->count();
        $shopify_ids = collect($collections['smart_collections'])->where('title', '!=', 'Bundles')->pluck('id')->toArray();
        foreach ($shopify_ids as $id) {
            if (! in_array($id, $linked_category_ids)) {
                $this->deleteCollectionByShopifyId($id);
            }
        }

        foreach ($linked_category_ids as $id) {
            if (! in_array($id, $shopify_ids)) {
                \DB::table('crm_shopify_links')
                    ->where('store_url', $this->store_url)
                    ->where('type', 'category')
                    ->where('shopify_id', $id)->delete();
            }
        }

        $linked_categories = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'category')->pluck('erp_id')->toArray();
        // update/insert new items

        foreach ($category_ids as $category_id) {
            if (in_array($category_id, $linked_categories)) {
                $this->updateCollection($category_id);
            } else {
                $this->createCollection($category_id);
            }
        }
        $this->sortCollections();
        $linked_products = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'product')->pluck('erp_id')->toArray();
        // update/insert new items

        foreach ($product_ids as $product_id) {
            if (in_array($product_id, $linked_products)) {
                $this->updateProduct($product_id);
            } else {
                $this->createProduct($product_id);
            }
        }

        // bundles
        if (! $bundle_collection_exists) {
            $post_data = [
                'smart_collection' => [
                    'title' => 'Bundles',
                    'handle' => 'product-bundles',
                    'body_html' => 'Bundles',
                    'rules' => [
                        (object) [
                            'column' => 'tag',
                            'relation' => 'equals',
                            'condition' => 'Bundle',
                        ],
                    ],
                ],
            ];

            $response = $this->client->post('smart_collections', $post_data);
        }

        $bundle_ids = \DB::table('crm_product_bundles')
            ->where('is_deleted', 0)
            ->pluck('id')->toArray();

        // delete bundles
        $deleted_product_ids = \DB::table('crm_shopify_links')
            ->whereNotIn('erp_id', $bundle_ids)
            ->where('store_url', $this->store_url)
            ->where('type', 'bundle')
            ->pluck('shopify_id')->toArray();

        foreach ($deleted_product_ids as $id) {
            $this->deleteBundleByShopifyId($id, 1);
        }

        // add bundles
        $linked_bundles = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'bundle')->pluck('erp_id')->toArray();

        foreach ($bundle_ids as $bundle_id) {
            $valid_count = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle_id)->whereIn('product_id', $product_ids)->count();
            $bundle_count = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle_id)->count();
            if ($valid_count == $bundle_count) {
                if (in_array($bundle_id, $linked_bundles)) {
                    $this->updateBundle($bundle_id);
                } else {
                    $this->createBundle($bundle_id);
                }
            }
        }

    }

    public function updateProductMpn()
    {
        // https://community.shopify.com/c/shopify-apis-and-sdks/updating-product-seo-description-using-rest-api/m-p/1117801
        /*


        1. Get the metafields of the product: https://XXXXX.myshopify.com/admin/api/2021-01/products/6155434229913/metafields.json
        2. Filter the metafields above with namespace="global" and key="description_tag"
        3. If you have found the metafield in the step above, update the value with the new SEO description_tag
        4. Update using this endpoint => https://shopify.dev/docs/admin-api/rest/reference/metafield#update-2021-01, use the existing metafield id from above
        5. If the metafield has not been found at step 2, then create a new one https://shopify.dev/docs/admin-api/rest/reference/metafield#create-2021-01 (Create a new metafield for a Product resource)

        */

        $linked_products = \DB::table('crm_shopify_links')->where('erp_id', 999)->where('store_url', $this->store_url)->where('type', 'product')->get();
        foreach ($linked_products as $linked_product) {
            $product_code = \DB::table('crm_products')->where('id', $linked_product->erp_id)->pluck('code')->first();

            $lookup_response = $this->client->get('products/'.$linked_product->shopify_id.'/metafields');

            if ($lookup_response->getStatusCode() == 200) {
                $result = $lookup_response->getDecodedBody();
                $metafields = $result['metafields'];
                $mpn_id = false;
                foreach ($metafields as $m) {
                    if ($m['key'] == 'mpn') {
                        $mpn_id = $m['id'];
                    }
                }
                $product_code = null;
                if ($mpn_id) {

                    $post_data = [
                        'metafield' => [
                            'id' => $mpn_id,
                            'key' => 'mpn',
                            'namespace' => 'mm-google-shopping',
                            'value' => $product_code,
                            'type' => 'single_line_text_field',
                        ],
                    ];

                    $response = $this->client->put('products/'.$linked_product->shopify_id.'/metafields/'.$mpn_id, $post_data);

                } else {

                    $post_data = [
                        'metafield' => [
                            'key' => 'mpn',
                            'namespace' => 'mm-google-shopping',
                            'value' => $product_code,
                            'type' => 'single_line_text_field',
                        ],
                    ];

                    $response = $this->client->post('products/'.$linked_product->shopify_id.'/metafields', $post_data);
                }

            }
        }
    }

    //// ORDERS
    public function getOrders()
    {
        //https://shopify.dev/api/admin-rest/2022-10/resources/order
        $response = $this->client->get('orders');

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $response_body = $response->getDecodedBody();

        return $response_body;
    }

    public function getUnfulfilledOrders()
    {
        //https://shopify.dev/api/admin-rest/2022-10/resources/order
        $response = $this->client->get('orders', ['fulfillment_status' => 'unfulfilled']);

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $response_body = $response->getDecodedBody();

        return $response_body;
    }

    public function getOrder($order_id)
    {
        //https://shopify.dev/api/admin-rest/2022-10/resources/order
        $response = $this->client->get('orders/'.$order_id);

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $response_body = $response->getDecodedBody();

        return $response_body;
    }

    //// ORDERS
    public function getOrderTransaction($order_id)
    {
        //https://shopify.dev/api/admin-rest/2022-10/resources/transaction#get-orders-order-id-transactions
        $response = $this->client->get('orders/'.$order_id.'/transactions');

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $response_body = $response->getDecodedBody();

        return $response_body;
    }

    public function importOrders()
    {
        $remove_tax_fields = get_admin_setting('remove_tax_fields');
        $this->updateOrderPaymentStatus();
        $this->updateOrderFulfillments();
        //https://shopify.dev/api/admin-rest/2022-10/resources/order
        $response = $this->client->get('orders');

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $response_body = $response->getDecodedBody();

        foreach ($response_body['orders'] as $order) {
            try {

                if (! in_array($order['financial_status'], ['paid', 'pending'])) {

                    continue;
                }
                if ($order['source_name'] != 'web') {

                    continue;
                }

                $imported = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'order')->where('shopify_id', $order['id'])->count();
                if ($imported) {

                    continue;
                }

                $account_id = $this->getAccountId($order['customer']);
                if (! $account_id) {
                    debug_email('Shopify customer not found, shopify order id - '.$order['id']);

                    continue;
                }

                // create document
                $db = new DBEvent;
                $data = [
                    'docdate' => date('Y-m-d', strtotime($order['created_at'])),
                    'doctype' => ($order['financial_status'] == 'paid') ? 'Tax Invoice' : 'Order',
                    'completed' => 1,
                    'account_id' => $account_id,
                    'total' => $order['total_price'],
                    'tax' => (! $remove_tax_fields) ? $order['total_tax'] : 0,
                    'reference' => 'Shopify Order #'.$order['id'],
                    'billing_type' => '',
                    'qty' => [],
                    'price' => [],
                    'full_price' => [],
                    'product_id' => [],
                ];

                $lines_count = 0;
                foreach ($order['line_items'] as $line_item) {

                    $product_id = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'product')->where('shopify_id', $line_item['product_id'])->pluck('erp_id')->first();
                    $bundle_id = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'bundle')->where('shopify_id', $line_item['product_id'])->pluck('erp_id')->first();

                    if ($product_id) {
                        $lines_count++;
                        $data['product_id'][] = $product_id;
                        $data['qty'][] = $line_item['quantity'];
                        if ($line_item['taxable'] && ! $remove_tax_fields) {
                            $data['price'][] = $line_item['price'] / 1.15;
                            $data['full_price'][] = $line_item['price'] / 1.15;
                        } else {
                            $data['price'][] = $line_item['price'];
                            $data['full_price'][] = $line_item['price'];
                        }
                    } elseif ($bundle_id) {
                        $lines_count++;
                        $bundle_items = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle_id)->get();
                        foreach ($bundle_items as $bundle_item) {
                            $data['product_id'][] = $product_id;
                            $data['qty'][] = $line_item['quantity'] * $bundle_item->qty;
                            if ($line_item['taxable']) {
                                $data['price'][] = $bundle_item->price_excl;
                                $data['full_price'][] = $bundle_item->price_excl;
                            } else {
                                $data['price'][] = $bundle_item->price;
                                $data['full_price'][] = $bundle_item->price;
                            }
                        }
                    }
                }

                if (count($order['line_items']) != $lines_count) {
                    debug_email('Shopify product not found, shopify order id - '.$order['id']);

                    continue;
                }

                $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);
                if (! is_array($result) || empty($result['id'])) {
                    debug_email('Shopify document save error, shopify order id - '.$order['id'], $result);

                    continue;
                }

                $document_id = $result['id'];

                if ($document_id) {
                    $link_data = [
                        'store_url' => $this->store_url,
                        'type' => 'order',
                        'erp_id' => $document_id,
                        'shopify_id' => $order['id'],
                        'shopify_meta' => '',
                        'payment_method' => $order['gateway'],
                        'payment_status' => $order['financial_status'],
                    ];
                    \DB::table('crm_shopify_links')->insert($link_data);
                }
            } catch (\Throwable $ex) {
                exception_email($ex, 'Shopify order save error, shopify order id - '.$order['id']);
            }
        }

        return true;
    }

    private function getAccountId($shopify_customer)
    {

        // get account_id from customer shopify id if previously linked
        $account_id = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'customer')->where('shopify_id', $shopify_customer['id'])->pluck('erp_id')->first();
        if ($account_id) {
            return $account_id;
        }

        // get account_id from email address, link to new shopify customer
        $account_id = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->where('email', $shopify_customer['email'])->pluck('id')->first();
        if ($account_id) {
            $link_data = [
                'store_url' => $this->store_url,
                'type' => 'customer',
                'erp_id' => $account_id,
                'shopify_id' => $shopify_customer['id'],
                'shopify_meta' => '',
            ];
            \DB::table('crm_shopify_links')->insert($link_data);

            return $account_id;
        }

        // if no match found, create customer
        $customer_data = [];
        $customer_data['company'] = $customer_data['contact'] = $shopify_customer['first_name'].' '.$shopify_customer['last_name'];
        $customer_data['email'] = $shopify_customer['email'];
        $customer_data['phone'] = $shopify_customer['phone'];
        if (! $customer_data['phone'] && $shopify_customer['default_address']['phone']) {
            $customer_data['phone'] = $shopify_customer['default_address']['phone'];
        }
        $address_arr = [];
        $address_arr[] = $shopify_customer['default_address']['address1'];
        $address_arr[] = $shopify_customer['default_address']['address2'];
        $address_arr[] = $shopify_customer['default_address']['city'];
        $address_arr[] = $shopify_customer['default_address']['province'];
        $address_arr[] = $shopify_customer['default_address']['country'];
        $address_arr[] = $shopify_customer['default_address']['zip'];
        $address_arr = collect($address_arr)->filter()->toArray();
        $customer_data['address'] = implode(',', $address_arr);

        $account_id = create_customer($customer_data, 'customer');

        $link_data = [
            'store_url' => $this->store_url,
            'type' => 'customer',
            'erp_id' => $account_id,
            'shopify_id' => $shopify_customer['id'],
            'shopify_meta' => '',
        ];
        \DB::table('crm_shopify_links')->insert($link_data);

        return $account_id;
    }

    public function getFulfillmentOrders($order_id)
    {
        $response = $this->client->get('orders/'.$order_id.'/fulfillment_orders');

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $response_body = $response->getDecodedBody();

        return $response_body;
    }

    public function createFulfillment($fulfillment_order_id)
    {
        $post_data = [
            'fulfillment' => [
                'message' => 'Order processed.',
                'line_items_by_fulfillment_order' => [
                    [
                        'fulfillment_order_id' => $fulfillment_order_id,
                    ],
                ],
            ],
        ];
        $response = $this->client->post('fulfillments', $post_data);

        if ($response->getStatusCode() != 201) {
            return false;
        }

        return true;
    }

    public function updateOrderPaymentStatus()
    {
        // https://shopify.dev/api/admin-rest/2022-10/resources/fulfillment
        // https://shopify.dev/api/admin-rest/2022-10/resources/fulfillmentorder#get-orders-order-id-fulfillment-orders

        $orders = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'order')->where('payment_status', 'pending')->get();

        foreach ($orders as $order) {
            $doc = \DB::table('crm_documents')->where('id', $order->erp_id)->get()->first();
            if ($doc->doctype != 'Tax Invoice') {
                continue;
            }
            $post_data = [
                'transaction' => [
                    'order_id' => $order->shopify_id,
                    'gateway' => $order->payment_method,
                    'currency' => $doc->document_currency,
                    'amount' => $order->total,
                    'kind' => 'sale',
                    'source' => 'external',
                ],
            ];
            $response = $this->client->post('orders/'.$order->shopify_id.'/transactions', $post_data);

            if ($response->getStatusCode() == 201) {
                \DB::table('crm_shopify_links')->where('id', $order->id)->update(['payment_status' => 'paid']);
            }
        }
    }

    public function updateOrderFulfillments()
    {
        // https://shopify.dev/api/admin-rest/2022-10/resources/fulfillment
        // https://shopify.dev/api/admin-rest/2022-10/resources/fulfillmentorder#get-orders-order-id-fulfillment-orders

        $orders = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'order')->where('order_fulfilled', 0)->get();

        foreach ($orders as $order) {
            $pending_activations = \DB::table('sub_activations')->where('invoice_id', $order->erp_id)->where('status', 'Pending')->count();
            if ($pending_activations) {
                continue;
            }
            $fulfillment_order = $this->getFulfillmentOrders($order->shopify_id);

            if ($fulfillment_order['fulfillment_orders'][0]['id']) {
                $processed = $this->createFulfillment($fulfillment_order['fulfillment_orders'][0]['id']);
                if ($processed) {
                    \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('id', $order->id)->update(['order_fulfilled' => 1]);
                }
            }
        }

    }

    public function getAbandonedCheckoutsCount()
    {

        $count = 0;
        // https://shopify.dev/api/admin-rest/2022-10/resources/abandoned-checkouts#get-checkouts?limit=1
        // /admin/api/2022-10/checkouts.json?limit=1

        $response = $this->client->get('checkouts');

        if ($response->getStatusCode() != 200) {
            return $count;
        }

        $response_body = $response->getDecodedBody();

        return count($response_body['checkouts']);
    }

    public function getPaidOrders()
    {
        $response = $this->client->get('orders');

        $count = 0;

        if ($response->getStatusCode() != 200) {
            return $count;
        }

        $response_body = $response->getDecodedBody();

        foreach ($response_body['orders'] as $order) {

            if (! in_array($order['financial_status'], ['paid'])) {

                $count++;
            }
        }

        return $count;
    }

    public function importQuotations()
    {

        return false;
        // https://shopify.dev/api/admin-rest/2022-10/resources/abandoned-checkouts#get-checkouts?limit=1
        // /admin/api/2022-10/checkouts.json?limit=1

        $response = $this->client->get('checkouts');

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $response_body = $response->getDecodedBody();

        foreach ($response_body['checkouts'] as $order) {
            try {

                $imported = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'checkout')->where('shopify_id', $order['id'])->count();
                if ($imported) {
                    continue;
                }

                $account_id = $this->getAccountId($order['customer']);
                if (! $account_id) {
                    debug_email('Shopify customer not found, shopify order id - '.$order['id']);

                    continue;
                }

                // create document
                $db = new DBEvent;
                $data = [
                    'docdate' => date('Y-m-d', strtotime($order['created_at'])),
                    'doctype' => 'Quotation',
                    'completed' => 1,
                    'account_id' => $account_id,
                    'total' => $order['total_price'],
                    'tax' => $order['total_tax'],
                    'reference' => 'Shopify Order #'.$order['id'],
                    'billing_type' => '',
                    'qty' => [],
                    'price' => [],
                    'full_price' => [],
                    'product_id' => [],
                ];

                $lines_set = false;
                foreach ($order['line_items'] as $line_item) {

                    $product_id = \DB::table('crm_shopify_links')->where('store_url', $this->store_url)->where('type', 'product')->where('shopify_id', $line_item['product_id'])->pluck('erp_id')->first();

                    if (! $product_id) {
                        $lines_set = false;
                    } else {
                        $lines_set = true;
                        $data['product_id'][] = $product_id;
                        $data['qty'][] = $line_item['quantity'];
                        if ($line_item['taxable']) {
                            $data['price'][] = $line_item['price'] / 1.15;
                            $data['full_price'][] = $line_item['price'] / 1.15;
                        } else {
                            $data['price'][] = $line_item['price'];
                            $data['full_price'][] = $line_item['price'];
                        }
                    }
                }

                if (! $lines_set) {
                    debug_email('Shopify product not found, shopify order id - '.$order['id']);

                    continue;
                }

                $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

                if (! is_array($result) || empty($result['id'])) {
                    debug_email('Shopify document save error, shopify order id - '.$order['id'], $result);

                    continue;
                }

                $document_id = $result['id'];

                if ($document_id) {
                    $link_data = [
                        'store_url' => $this->store_url,
                        'type' => 'checkout',
                        'erp_id' => $document_id,
                        'shopify_id' => $order['id'],
                        'shopify_meta' => '',
                        'payment_method' => '',
                        'payment_status' => 'pending',
                    ];
                    \DB::table('crm_shopify_links')->insert($link_data);
                }

            } catch (\Throwable $ex) {
                exception_email($ex, 'Shopify quotation save error, shopify order id - '.$order['id']);
            }
        }

        return true;
    }
}
