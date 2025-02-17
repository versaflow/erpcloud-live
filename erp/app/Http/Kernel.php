<?php

namespace App\Http;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\TenantSession::class,
            // \App\Http\Middleware\Cors::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\InstanceAccessMiddleware::class,
            \App\Http\Middleware\SidebarFilterRedirect::class,
            // \App\Http\Middleware\EncryptCookies::class,
            // \App\Http\Middleware\ShareDataForViews::class,
            //\App\Http\Middleware\StoreHostnameMiddleware::class,
            //\App\Http\Middleware\DevViews::class,
            \App\Http\Middleware\JsonResponseHandler::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'ipblocked' => \App\Http\Middleware\IpblockedMiddleware::class,
        'cors' => \App\Http\Middleware\Cors::class,
        'globalviewdata' => \App\Http\Middleware\ShareDataForViews::class,
        'tasksactive' => \App\Http\Middleware\StaffActiveTasks::class,

    ];

    protected function bootstrappers()
    {
        return array_merge(
            parent::bootstrappers(),
        );
    }
}
