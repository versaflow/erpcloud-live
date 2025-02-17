<?php

namespace App\Http\Middleware;

use Closure;
use Log, Exception, View;

class DevViews
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       
        if(!empty($request->use_dev_views) && (session('instance')->id == 12 || (!empty(session('use_dev_views')) && session('use_dev_views')))){
            
            $dev_views_path = base_path().'resources/dev_views';
            config(['view.paths' => $dev_views_path]);   
            $finder = new \Illuminate\View\FileViewFinder(app()['files'], [$dev_views_path]);
            View::setFinder($finder);
        }
        

        return $next($request);
    }
}