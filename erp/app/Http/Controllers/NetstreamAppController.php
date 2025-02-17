<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class NetstreamAppController extends BaseController
{
    protected $request; // request as an attribute of the controllers

    protected $token;

    public function __construct(Request $request)
    {
        $this->request = $request; // Request becomes available for all the controller functions that call $this->request

        $this->middleware(function ($request, $next) {
            $validation = $this->validateToken();
            if ($validation !== true) {
                return api_error($validation);
            }

            $this->token = $request->api_token;
            $this->key = $request->key;

            return $next($request);
        });
    }

    private function validateToken()
    {

        if (empty($this->request->api_token)) {
            return 'Token required';
        }

        $user = \DB::connection('default')->table('erp_users')->where('api_token', $this->request->api_token)->get()->first();
        if (empty($user)) {
            return 'Invalid token';
        }

        return true;
    }
}
