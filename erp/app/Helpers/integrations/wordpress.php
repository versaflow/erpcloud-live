<?php

function schedule_wordpress_export_products()
{
    //return false;
    if (session('instance')->id == 1 || session('instance')->id == 11) {
        try {
            //set_product_marketing_prices();
            $integrations = \DB::table('crm_wordpress_integrations')->where('status', 'Enabled')->get();

            foreach ($integrations as $i) {
                $wp = new WordPress($i->id);

                $wp->deleteCategories();
                $wp->deleteProducts();
                $wp->updateCategories();
                $wp->updateProducts();

                $wp->importImageLinks();
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }
}

function drop_duplicated_cloudtelecoms_wordpress_tables()
{
    $prefix = 'wpiq';
    $tables = get_tables_from_schema('cloudtelecoms_wordpress');
    foreach ($tables as $table) {
        if (! str_starts_with($table, $prefix)) {
            \Schema::connection('cloudtelecoms_wordpress')->dropIfExists($table);
        }
    }
}

function aftercommit_wordpress_export_product($request)
{
    //return false;
    if (session('instance')->id == 1 || session('instance')->id == 11) {
        try {
            //set_product_marketing_prices();
            $integrations = \DB::table('crm_wordpress_integrations')->where('status', 'Enabled')->get();

            foreach ($integrations as $i) {
                $wp = new WordPress($i->id);
                $wp->updateProducts($request->id);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }
}

function schedule_wordpress_import_orders()
{
    if (session('instance')->id == 1 || session('instance')->id == 11) {
        try {
            //set_product_marketing_prices();
            $integrations = \DB::table('crm_wordpress_integrations')->where('status', 'Enabled')->get();
            // vd($integrations);
            foreach ($integrations as $i) {
                $wp = new WordPress($i->id);
                $wp->importOrders();
                // vd($orders);
                $wp->updateDocumentReferences();
                // vd($docrefs);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }
}

/// https://portal.telecloud.co.za/helper/schedule_wordpress_export_products
function schedule_wordpress_export_customers()
{
    //return false;
    if (session('instance')->id == 1 || session('instance')->id == 11) {
        try {
            //set_product_marketing_prices();
            $integrations = \DB::table('crm_wordpress_integrations')->where('status', 'Enabled')->get();
            foreach ($integrations as $i) {
                $wp = new WordPress($i->id);
                $wp->exportCustomers();
                // vd($i);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }
}

function button_wordpress_export_products()
{
    if (session('instance')->id == 1) {
        try {
            //set_product_marketing_prices();

            $integrations = \DB::table('crm_wordpress_integrations')->where('id', 1)->where('status', 'Enabled')->get();
            foreach ($integrations as $i) {
                $wp = new WordPress($i->id);
                $wp->deleteCategories();
                $wp->deleteProducts();
                $wp->updateCategories();
                $wp->updateProducts();
                $wp->importImageLinks();
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    } elseif (session('instance')->id == 11) {
        try {
            //set_product_marketing_prices();

            $integrations = \DB::table('crm_wordpress_integrations')->where('status', 'Enabled')->get();
            foreach ($integrations as $i) {
                $wp = new WordPress($i->id);
                $wp->deleteCategories();
                $wp->deleteProducts();
                $wp->updateCategories();
                $wp->updateProducts();
                $wp->importImageLinks();
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }

    return json_alert('Done');
}

function button_bulkhub_update_shop_products($request)
{
    if (session('instance')->id == 1) {
        try {
            //set_product_marketing_prices();

            $integrations = \DB::table('crm_wordpress_integrations')->where('id', 3)->where('status', 'Enabled')->get();
            foreach ($integrations as $i) {
                $wp = new WordPress($i->id);
                $wp->deleteCategories();
                $wp->deleteProducts();
                $wp->updateCategories();
                $wp->updateProducts();
                $wp->importImageLinks();
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
        }
    }

    return json_alert('Done');
}

function schedule_wordpress_faq()
{
    //return false;
    $integrations = \DB::table('crm_wordpress_integrations')->where('status', 'Enabled')->get();
    foreach ($integrations as $i) {
        if (empty($i->import_faq)) {
            continue;
        }

        $wp_prefix = $i->db_prefix.'_';

        $store_db = $i->store_db;
        $post_ids = \DB::connection($store_db)->table($wp_prefix.'posts')->where('post_type', 'ufaq')->pluck('ID')->toArray();
        if (count($post_ids) > 0) {
            \DB::connection($store_db)->table($wp_prefix.'posts')->whereIn('ID', $post_ids)->delete();
            \DB::connection($store_db)->table($wp_prefix.'postmeta')->whereIn('post_id', $post_ids)->delete();
            \DB::connection($store_db)->table($wp_prefix.'term_relationships')->whereIn('object_id', $post_ids)->delete();
        }
        $faqs = \DB::connection('default')->table('hd_customer_faqs')->whereIn('level', ['Customer', 'Reseller'])->where('is_deleted', 0)->where('internal', 0)->get();

        foreach ($faqs as $faq) {
            if (empty($faq->website)) {
                continue;
            }
            if (! str_contains($i->store_url, $faq->website)) {
                continue;
            }

            $faq_category = $faq->type;
            $faq_content = $faq->content;
            $faq_title = $faq->name;
            $faq_data = [
                'post_author' => 1,
                'post_date' => date('Y-m-d H:i:s'),
                'post_date_gmt' => date('Y-m-d H:i:s'),
                'post_content' => '<!-- wp:paragraph -->'.$faq_content.'<!-- /wp:paragraph -->',
                'post_title' => $faq_title,
                'post_status' => 'publish',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_name' => seo_string($faq_title),
                'post_modified' => date('Y-m-d H:i:s'),
                'post_modified_gmt' => date('Y-m-d H:i:s'),
                'post_type' => 'ufaq',
            ];
            $post_id = \DB::connection($store_db)->table($wp_prefix.'posts')->insertGetId($faq_data);
            // update guid
            $guid = $i->store_url.'?post_type=ufaq&#038;p='.$post_id;
            \DB::connection($store_db)->table($wp_prefix.'posts')->where('ID', $post_id)->update(['guid' => $guid]);
            $category_seo = 'faq-'.seo_string($faq->type);
            $category_name = 'FAQ '.$faq->type;
            $category_id = \DB::connection($store_db)->table($wp_prefix.'terms')->where('name', $category_name)->pluck('term_id')->first();
            if (! $category_id) {
                $category_id = \DB::connection($store_db)->table($wp_prefix.'terms')->insertGetId(['name' => $category_name, 'slug' => $category_seo]);
            }
            $term_link = ['object_id' => $post_id, 'term_taxonomy_id' => $category_id];
            \DB::connection($store_db)->table($wp_prefix.'term_relationships')->updateOrInsert($term_link, $term_link);
            $meta_data = [
                '_yoast_wpseo_primary_ufaq-category' => $category_id,
                '_yoast_wpseo_metadesc' => substr($faq_content, 0, 155),
                '_yoast_wpseo_wordproof_timestamp' => '',
                '_yoast_wpseo_estimated-reading-time-minutes' => 1,
                'EWD_UFAQ_Post_Author' => 'admin',
                'ufaq_order' => 9999,
            ];

            foreach ($meta_data as $key => $key) {
                $faq_metadata = [
                    'post_id' => $post_id,
                    'meta_key' => $key,
                    'meta_value' => $key,
                ];
                \DB::connection($store_db)->table($wp_prefix.'postmeta')->insert($faq_metadata);
            }
        }
    }
}
