<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IpblockedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $config = config('sximo');
        if ($config['cnf_allowip'] != '') {
            $ipsAllow = explode(',', preg_replace('/\s+/', '', $config['cnf_allowip']));
            if (count($ipsAllow) >= 1) {
                //                echo count($ipsAllow);exit;
                if (! in_array($request->ip(), $ipsAllow)) {
                    return redirect('restric');
                }
            }
        }

        $ips = explode(',', preg_replace('/\s+/', '', $config['cnf_restrictip']));
        if (is_array($ips)) {
            if (in_array($request->ip(), $ips)) {
                return redirect('restric');
            }
        }

        if (! \Session::has('lang')) {
            \Session::put('lang', $config['cnf_lang']);
        }

        app()->setLocale(\Session::get('lang'));
        if (\Auth::check()) {
            if (! \Session::has('uid')) {
                $session = [
                    'gid' => Auth::user()->group_id,
                    'uid' => Auth::user()->id,
                    'eid' => Auth::user()->email,
                    'll' => Auth::user()->last_login,
                    'fid' => Auth::user()->contact,
                    'username' => Auth::user()->username,
                    'join' => Auth::user()->created_at,
                ];
                //  \Session::put('gid',Auth::user()->group_id);
                session($session);
            }
        }

        return $next($request);
    }
}
