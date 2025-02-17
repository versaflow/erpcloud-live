<?php

use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::any('store', [StoreController::class, 'index']);
Route::any('store/cart', [StoreController::class, 'cart']);
Route::any('store/checkout', [StoreController::class, 'checkout']);
Route::any('store/{produt_slug?}', [StoreController::class, 'products']);
