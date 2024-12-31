<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class StoreController extends BaseController
{
    public function __construct() {}

    public function index(Request $request)
    {
        $data = [];
        $branding_logo = '';
        $logo = \DB::connection('default')->table('crm_account_partner_settings')->where('account_id', 1)->pluck('logo')->first();

        if (file_exists(uploads_settings_path().$logo)) {
            $branding_logo = settings_url().$logo;
        }
        $data['branding_logo'] = $branding_logo;
        if (session('account_id')) {
            $data['products'] = get_transaction_products(session('account_id'));
        } else {
            $data['products'] = get_transaction_products(1);
        }

        /*
           +"id": 764
        +"category_id": 214
        +"type": "Service"
        +"is_subscription": 1
        +"code": "Fibre4020openserve - Openserve Fibre 40/20 Mbps Uncapped"
        +"description": "Openserve Fibre 40/20 Mbps Uncapped 40Mbps download speed.20Mbps upload speed.Uncapped and unshaped data.FREE Wi-Fi Router and activation.incl. VAT"
        +"category": "Data - Fibre to the Home"
        +"price": "565.22"
        +"activation_description": ""
        +"provision_type": "fibre_product"
        +"full_price": "565.22"
        +"full_price_incl": "650.000"
        */
        return view('store.index', $data);
    }
}
