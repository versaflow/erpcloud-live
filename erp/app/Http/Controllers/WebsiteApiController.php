<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WebsiteApiController extends BaseController
{
    public function __construct(Request $request)
    {
        $this->request = $request; // Request becomes available for all the controller functions that call $this->request

        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }

    public function importProducts()
    {
        $r = \DB::connection('shop')->table('products')->get();
        $r = \DB::connection('default')->table('crm_products')->get();
        return 'ok';
    }

    public function getCustomer()
    {
        return 'ok';
    }
}
