<?php

namespace App\Http\Controllers;

use Rap2hpoutre\LaravelLogViewer\LogViewerController;
use Redirect;

class LogController extends LogViewerController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            if (session('role_id') > 10) {
                return Redirect::to('/')->with('status', 'error')->with('message', 'No Access');
            }

            return $next($request);
        });
    }
}
