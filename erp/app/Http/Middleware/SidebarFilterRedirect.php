<?php

namespace App\Http\Middleware;

use Closure;

class SidebarFilterRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if ((! empty($request->telecloud_filter_domain) || ! empty($request->telecloud_filter_account))) {

            //  aa($request->all());

            $module_id = app('erp_config')['modules']->where('slug', $request->segment(1))->pluck('id')->first();

            $has_domain_name_field = false;
            $has_account_id_field = false;
            $has_domain_uuid_field = false;

            $has_domain_name_field = app('erp_config')['module_fields']->where('field', 'domain_name')->where('module_id', $module_id)->count();
            if (! $has_domain_name_field) {
                $has_account_id_field = app('erp_config')['module_fields']->where('field', 'account_id')->where('module_id', $module_id)->count();
                if (! $has_account_id_field) {
                    $has_domain_uuid_field = app('erp_config')['module_fields']->where('field', 'domain_uuid')->where('module_id', $module_id)->count();
                }
            }

            if ($has_domain_name_field) {
                if (! empty($request->telecloud_filter_account)) {
                    $domain_name = \DB::connection('pbx')->table('v_domains')->where('account_id', $request->telecloud_filter_account)->pluck('domain_name')->first();

                    $queryParams = $request->query->all();
                    unset($queryParams['telecloud_filter_account']);
                    $queryParams['domain_name'] = $domain_name;
                    $newUrl = $request->url().'?'.http_build_query($queryParams);

                    return redirect($newUrl);
                }
                if (! empty($request->telecloud_filter_domain)) {
                    $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $request->telecloud_filter_domain)->pluck('domain_name')->first();

                    $queryParams = $request->query->all();
                    unset($queryParams['telecloud_filter_domain']);
                    $queryParams['domain_name'] = $domain_name;
                    $newUrl = $request->url().'?'.http_build_query($queryParams);

                    return redirect($newUrl);
                }
            } elseif ($has_account_id_field) {
                if (! empty($request->telecloud_filter_account)) {

                    $account_id = $request->telecloud_filter_account;
                    $queryParams = $request->query->all();
                    unset($queryParams['telecloud_filter_account']);
                    $queryParams['account_id'] = $account_id;
                    $newUrl = $request->url().'?'.http_build_query($queryParams);

                    return redirect($newUrl);
                }
                if (! empty($request->telecloud_filter_domain)) {

                    $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $request->telecloud_filter_domain)->pluck('account_id')->first();

                    $queryParams = $request->query->all();
                    unset($queryParams['telecloud_filter_domain']);
                    $queryParams['account_id'] = $account_id;
                    $newUrl = $request->url().'?'.http_build_query($queryParams);

                    return redirect($newUrl);
                }
            } elseif ($has_domain_uuid_field) {
                if (! empty($request->telecloud_filter_account)) {

                    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('account_id', $request->telecloud_filter_account)->pluck('domain_uuid')->first();

                    $queryParams = $request->query->all();
                    unset($queryParams['telecloud_filter_account']);
                    $queryParams['domain_uuid'] = $domain_uuid;
                    $newUrl = $request->url().'?'.http_build_query($queryParams);

                    return redirect($newUrl);
                }
                if (! empty($request->telecloud_filter_domain)) {

                    $domain_uuid = $request->telecloud_filter_domain;
                    $queryParams = $request->query->all();
                    unset($queryParams['telecloud_filter_domain']);
                    $queryParams['domain_uuid'] = $domain_uuid;
                    $newUrl = $request->url().'?'.http_build_query($queryParams);

                    return redirect($newUrl);
                }
            }
        }

        return $next($request);
    }
}
