<?php

use App\Http\Controllers\FileManagerController;
use Illuminate\Support\Facades\Route;

Route::any('filemanager', [FileManagerController::class, 'index']);
Route::any('filemanager_actions', [FileManagerController::class, 'actions']);
