<?php

function onload_set_fibre_details(){
    $rows = \DB::table('isp_data_fibre')->get();
    foreach($rows as $row){
        $product_id = \DB::table('sub_services')->where('id',$row->subscription_id)->pluck('product_id')->first();
        $sub = \DB::table('sub_services')->where('id',$row->subscription_id)->get()->first();
        $annual = 0;
        if($sub->bill_frequency == 12){
            $annual = 1;
        }
        \DB::table('isp_data_fibre')->where('id',$row->id)->update(['product_id'=>$product_id,'last_billed'=>$sub->last_invoice_date,'billed_annually'=>$annual]);
    }
}


function schedule_sync_fibre_status()
{
    $axxess = new Axxess();
    $fibre_accounts = \DB::table('isp_data_fibre')->get();
    foreach ($fibre_accounts as $fibre) {
        $status = 'Enabled';
        $details = $axxess->getServiceById($fibre->guidServiceId);
        if (!empty($details->arrServices) && is_array($details->arrServices) && count($details->arrServices) > 0) {
            if ($details->arrServices[0]->intSuspendReasonId) {
                $status = 'Disabled';
            }
        } else {
            $status = 'Disabled';
        }

        \DB::table('sub_services')->where('detail', $details->arrServices[0]->strDescription)->where('status', '!=', 'Deleted')->update(['status' => $status]);
    }
}


function set_fibre_product_speed()
{
    $products = \DB::connection('default')->table('isp_data_products')->get();
    foreach ($products as $product) {
        $data = [];
        $description_arr = explode('/', $product->product);

        $download_arr = explode(' ', $description_arr[0]);
        $data['download_speed'] = end($download_arr);
        $upload_arr = explode(' ', $description_arr[1]);
        $data['upload_speed'] = str_replace('Mbps', '', $upload_arr[0]);
        \DB::connection('default')->table('isp_data_products')->where('id', $product->id)->update($data);
    }
}

function schedule_import_fibre_products()
{
    $axxess = new Axxess();
    $axxess->setAxxessProviderProducts();
    $axxess->lte_products_import();

    $products = \DB::connection('default')->table('isp_data_products')->get();
    foreach ($products as $product) {
        $data = [];
        $description_arr = explode('/', $product->product);

        $download_arr = explode(' ', $description_arr[0]);
        $data['download_speed'] = end($download_arr);
        $upload_arr = explode(' ', $description_arr[1]);
        $data['upload_speed'] = str_replace('Mbps', '', $upload_arr[0]);
        \DB::connection('default')->table('isp_data_products')->where('id', $product->id)->update($data);
    }
}
/*
function button_fibre_products_link_products($request)
{
    \DB::connection('default')->table('isp_data_products')->update(['product_id' => 0]);
    $products = \DB::connection('default')->table('isp_data_products')->get();
    foreach ($products as $product) {
        $linked_product_id = \DB::connection('default')->table('crm_products')->where('code', 'like', '%fibre%')->where('provision_package', $product->download_speed.'MBps')->where('status', 'Enabled')->pluck('id')->first();
        if ($linked_product_id) {
            \DB::connection('default')->table('isp_data_products')->where('id', $product->id)->update(['product_id' => $linked_product_id]);
        }
    }
}
*/
