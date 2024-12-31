<?php

namespace App\Http\Middleware;

use Closure;

class JsonResponseHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (method_exists($response, 'content')) {
            $content = json_decode($response->content(), true);

            //Check if the response is JSON
            if (! empty($request->segment(1)) && $request->segment(1) == 'api') {
                session()->forget('email_result');
                session()->forget('email_message');
                session()->forget('email_mod_id');
            } elseif (json_last_error() == JSON_ERROR_NONE) {
                if (! empty($content) && ! empty($content['status']) && ! empty($content['message']) && ! empty(session('email_result'))) {
                    $content['message'] .= '<br> Email '.ucfirst(session('email_result')).'<br>'.session('email_message');
                    $content = (object) $content;
                    $response->setContent(json_encode($content, true));

                    session()->forget('email_result');
                    session()->forget('email_message');
                    session()->forget('email_mod_id');
                }
            }
        }

        // $response = $next($request);
        return $response;
    }
}
