<?php

namespace App\Http\Middleware;

use Closure;

class StoreHostnameMiddleware
{
    public function handle($request, Closure $next)
    {
        // Get the current hostname
        $hostname = $request->getHost();

        // Check if the hostname is store.example.com
        if ($hostname === session('instance')->store_url) {
            // Check if the request is going to StoreController
            if (! $request->is('store*')) {
                return redirect('/store');
            }
        }

        // Allow access for other hostnames
        return $next($request);
    }
}
