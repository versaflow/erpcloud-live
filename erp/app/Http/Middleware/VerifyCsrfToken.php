<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'website_contact_form',
        'sximoapi*',
        'sximo/code*',
        'sximo/module/source*',
        'sximo/module/code*',

    ];
}
