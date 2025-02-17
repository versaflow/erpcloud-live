<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Matching\ValidatorInterface;

class _UriValidator implements ValidatorInterface
{
    public function matches(Route $route, Request $request)
    {
        $path = $request->path() == '/' ? '/' : '/'.$request->path();
        return preg_match(preg_replace('/$/', 'i', $route->getCompiled()->getRegex()), rawurldecode($path));
    }
}
