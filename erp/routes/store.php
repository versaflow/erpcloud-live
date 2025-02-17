<?php 

Route::any('store', 'StoreController@index');
Route::any('store/cart', 'StoreController@cart');
Route::any('store/checkout', 'StoreController@checkout');
Route::any('store/{produt_slug?}', 'StoreController@products');