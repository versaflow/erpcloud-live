<?php

use App\Http\Controllers\WebsiteApiController;
use Illuminate\Support\Facades\Route;

Route::any('wapi_import_products', [WebsiteApiController::class, 'importProducts']);
Route::any('wapi_get_customer', [WebsiteApiController::class, 'getCustomer']);
