<?php

function get_branding_pricelist_links()
{
    $list = [];
    $i = 20000;
    $db_storefronts = \DB::connection('default')->table('crm_business_plan')->select('name', 'logo', 'id', 'helpdesk_email', 'email_template')->get();
    $admin_pricelists = \DB::connection('default')->table('crm_pricelists')->where('partner_id', 1)->get();
    foreach ($db_storefronts as $db_storefront) {

        foreach ($admin_pricelists as $admin_pricelist) {
            if ($admin_pricelist->currency == 'USD') {
                continue;
            }
            $file_name = $db_storefront->name.' Pricelist '.$admin_pricelist->currency;

            $list[] = ['url' => 'download_branding_pricelist/'.$admin_pricelist->id.'/'.$db_storefront->id, 'menu_name' => $file_name, 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        }

    }

    return $list;
}

function export_pricelist_storefront($pricelist_id, $storefront_id, $format = 'pdf')
{
    $db_storefront = \DB::table('crm_business_plan')->where('id', $storefront_id)->select('name', 'logo', 'id', 'helpdesk_email', 'email_template')->get()->first();

    $data = [];
    $data['branding'] = $db_storefront;
    $enable_discounts = get_admin_setting('enable_discounts');

    $admin_pricelist_ids = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('id')->toArray();
    $pricelist = \DB::table('crm_pricelists')->where('id', $pricelist_id)->get()->first();

    $reseller = \DB::table('crm_accounts')->where('id', $pricelist->partner_id)->get()->first();
    $pricelist_items = \DB::table('crm_pricelist_items as pi')
        ->select(
            'pc.department',
            'pc.storefront_id',
            'pc.name as category',
            'p.code',
            'p.name',
            'p.description',
            'p.type',
            'p.status',
            'p.frequency',
            'p.is_subscription',
            'pi.pricelist_id',
            'pi.price',
            'pi.price_tax',
            'pi.reseller_price_tax',
            'pi.price_tax_6',
            'pi.price_tax_12',
            'pi.reseller_price_tax_12',
            'pi.price_tax_24',
            'p.id',
            'pc.id as category_id',
            'p.upload_file as image',
        )
        ->join('crm_products as p', 'p.id', '=', 'pi.product_id')
        ->join('crm_product_categories as pc', 'p.product_category_id', '=', 'pc.id')
        ->where('pi.pricelist_id', $pricelist_id)
        ->where('p.status', 'Enabled')
        ->where('pc.is_deleted', 0)
        ->where('pi.status', 'Enabled')
        ->where('pc.not_for_sale', 0)
        ->where('pc.customer_access', 1)
        ->orderby('pc.sort_order')
        ->orderby('p.sort_order')
        ->get();
    $company = string_clean($reseller->company);

    $file_name = $db_storefront->name.' Pricelist '.$pricelist->currency.'.'.$format;

    $file_path = uploads_path().'/pricing_exports/'.$file_name;

    $data['enable_discounts'] = $enable_discounts;
    $data['product_categories'] = \DB::table('crm_product_categories')
        ->where('is_deleted', 0)
        ->where('not_for_sale', 0)
        ->where('customer_access', 1)
        ->orderby('sort_order')
        ->get();
    $data['pricelist_items'] = [];

    $products_path = uploads_url(71);
    foreach ($pricelist_items as $item) {
        $category_storefront_ids = explode(',', $item->storefront_id);
        if (! in_array($db_storefront->id, $category_storefront_ids)) {
            continue;
        }
        if (str_contains(strtolower($item->code), 'rate')) {
            continue;
        } else {
            if ($item->category_id != 800 && $item->category_id != 953) {

                $price = $item->price_tax;

                if ($item->image > '') {
                    $item->image = $products_path.$item->image;
                } else {
                    $item->image = '';
                }

                $item->price_tax = currency($item->price_tax);
                if (in_array($item->pricelist_id, $admin_pricelist_ids) && $enable_discounts) {
                    $item->reseller_price_tax = currency($item->reseller_price_tax);
                    $item->price_tax_6 = currency($item->price_tax_6);
                    $item->price_tax_12 = currency($item->price_tax_12);
                    $item->reseller_price_tax_12 = currency($item->reseller_price_tax_12);
                    $item->price_tax_24 = currency($item->price_tax_24);
                } else {
                    unset($item->reseller_price_tax);
                    unset($item->price_tax_6);
                    unset($item->price_tax_12);
                    unset($item->reseller_price_tax_12);
                    unset($item->price_tax_24);
                }

                $item->description = str_replace('<ul>'.PHP_EOL.'<li>', '<ul><li>', $item->description);
                $item->description = str_replace('</li>'.PHP_EOL.'<li>', '</li><li>', $item->description);
                $item->description = str_replace(['<li data-list="bullet">', '•'], ['<li>', '<br>'], $item->description);

                $item->description = str_replace([PHP_EOL], ['<br>'], $item->description);
                $item->description = str_replace('<br><br>', '<br>', $item->description);

                $item->description = strip_tags($item->description, '<br>');

                $item->description = trim($item->description, '<br>');
                $item->description = trim($item->description, " \t\n\r\0\x0B");
                $item->description = trim($item->description, '<br>');

                if (strlen($item->description) > 200) {
                    $item->description = substr($item->description, 0, 200).'...';
                }

                $data['pricelist_items'][] = $item;

            }
        }
    }

    $data['pricelist_items'] = collect($data['pricelist_items'])->groupBy('category_id');
    $data['pricelist_product_items'] = collect($data['pricelist_product_items'])->groupBy('category_id');
    $data['currency'] = $pricelist->currency;
    $data['currency_symbol'] = get_currency_symbol($pricelist->currency);
    $bundles = \DB::table('crm_product_bundles')->where('is_deleted', 0)->get();
    foreach ($bundles as $i => $bundle) {
        $bundles[$i]->lines = \DB::table('crm_product_bundle_details')->where('product_bundle_id', $bundle->id)->get();
    }
    $data['bundles'] = $bundles->groupBy('category_id');

    $admin = dbgetaccount($pricelist->partner_id);
    $data['admin'] = $admin;
    if ($db_storefront->logo == null) {
        $data['logo_src'] = 'https://'.session('instance')->domain_name.'/uploads/'.session('instance')->directory.'/1879/';
    } else {
        $data['logo_src'] = 'https://'.session('instance')->domain_name.'/uploads/'.session('instance')->directory.'/1879/'.$db_storefront->logo;
    }
    $data['logo_path'] = uploads_path(1879).$admin->logo;

    if ($format == 'pdf') {
        $options = [
            'page-size' => 'a4',
            'orientation' => 'portrait',
            'encoding' => 'UTF-8',
            'footer-left' => 'All prices include vat',
            'footer-right' => $admin->company.' | Page [page] of [topage]',
            'footer-font-size' => 8,
        ];
        // if(is_dev()){

        // return view('__app.exports.branding_pricelist_pdf', $data);
        //  }
        //Create our PDF with the main view and set the options
        if ($storefront_id == 3) {
            $pdf = PDF::loadView('__app.exports.bulkhub_pricelist_pdf', $data);
        } else {
            $pdf = PDF::loadView('__app.exports.branding_pricelist_pdf', $data);
        }
        $pdf->setOptions($options);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $pdf->save($file_path);
    } else {
        $export = new App\Exports\ViewExport;
        $export->setViewFile('pricelist');
        $export->setViewData($data);

        $instance_dir = session('instance')->directory;
        $result = Excel::store($export, $file_name, 'pricing_exports');
    }

    \File::copy($file_path, attachments_path().$file_name);

    return $file_name;
}
