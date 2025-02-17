<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */

    public function boot()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
        Collection::macro('paginateLinks', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        
            $paginator = new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        
            return $paginator->links();
        });
        
        // \Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
        //      echo '<pre>';
        //      print_r([ $query->sql, $query->bindings, $query->time]);
        //      echo '</pre>';
        // });
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $rewrite_public_path = env('REWRITE_PUBLIC_PATH');
        if($rewrite_public_path){
            $this->app->bind('path.public', function () {
               return realpath(base_path().'/../html');
            });
        }
        
        try {
            $hostname = request()->root();

            $allowed_proxies = ['report','report_dashboard','processes','process_dashboard','dashboard_user','dashboard','dashboards_reports'];

            if (!empty($hostname) && $hostname == 'http://reports.turnkeyerp.io') {

                if (!empty(request()->token) && in_array(request()->segment(1), $allowed_proxies)) {

                    $token = \Erp::decode(request()->token);

                    if (empty($token['user_id']) && empty($token['guest'])) {
                        return \Redirect::back()->with('status', 'error')->with('message', 'Invalid Token');
                    }
                    $connection = $token['token'];
                    $instance = \DB::connection('system')->table('erp_instances')->where('db_connection', $connection)->get()->first();
                    if (!empty($instance) && !empty($instance->domain_name)) {
                        // \URL::forceRootUrl('https://'.$instance->domain_name);
                      //  \URL::forceScheme('https');
                    }
                } elseif (!empty(request()->cookie('connection'))) {

                    // get the encrypter service
                    $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);

                    // decrypt
                    $decryptedString = $encrypter->decrypt(request()->cookie('connection'), false);
                    $decryptedStringArr = explode('|', $decryptedString);
                    $db_connection = $decryptedStringArr[1];
                    $instance = \DB::connection('system')->table('erp_instances')->where('db_connection', $db_connection)->get()->first();
                    if (!empty($instance) && !empty($instance->domain_name)) {
                        \URL::forceRootUrl('https://'.$instance->domain_name);
                        \URL::forceScheme('https');
                    }
                }
            } elseif (!empty($hostname)) {
                $hostname = str_replace(['http://','https://'], '', $hostname);

                $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();
            }

            if (empty($instance) && isset($_SERVER['HTTP_HOST'])) { // if request does not contain hostname
                $hostname = $_SERVER['HTTP_HOST'];
                $instance = \DB::connection('system')->table('erp_instances')->where('domain_name', $hostname)->orwhere('alias', $hostname)->get()->first();
            }
            /*
            if (empty($instance) && isset($_SERVER['HTTP_HOST'])) { // check whitelabel domain
                $hostname = $_SERVER['HTTP_HOST'];
                $instance_configs = \DB::connection('system')->table('erp_instances')->get();
                foreach ($instance_configs as $instance_config) {
                    $host_found = \DB::connection($instance_config->db_connection)->table('crm_account_partner_settings')->where('whitelabel_domain', $hostname)->count();
                    if ($host_found) {
                        $instance = $instance_config;
                        break;
                    }
                }
            }
            */

            if (empty($instance)) {
               abort(500);
            }
            
            
            
            if(!empty(request()->cidb)){
             
                $instance = \DB::connection('system')->table('erp_instances')->where('db_connection', request()->cidb)->get()->first();
            }
           
            $instance_dir = $instance->db_connection;
            $directory = base_path().'/instance_config/'.$instance_dir;

            foreach (Finder::create()->in($directory)->name('*.php') as $file) {
                $this->mergeConfigFrom($file->getRealPath(), basename($file->getRealPath(), '.php'));
            }
            
            $this->app['config']->set('app.url', 'https://'.$instance->domain_name);
            
            config(['app.url' =>  'https://'.$instance->domain_name]);
        } catch (\Throwable $ex) {  
            exception_log($ex);
            abort(500);
        }
    }

    public function loadConfigForSchedule($instance_dir)
    {
        $instance_dir = strtolower(str_replace(' ', '_', $instance->name));
        $directory = base_path().'/instance_config/'.$instance_dir;

        foreach (Finder::create()->in($directory)->name('*.php') as $file) {
            $this->mergeConfigFrom($file->getRealPath(), basename($file->getRealPath(), '.php'));
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, $this->mergeConfig(require $path, $config));
    }

    /**
     * Merges the configs together and takes multi-dimensional arrays into account.
     *
     * @param  array  $original
     * @param  array  $merging
     * @return array
     */
    protected function mergeConfig(array $original, array $merging)
    {
        $array = array_merge($merging, $original);

        foreach ($original as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (! Arr::exists($merging, $key)) {
                continue;
            }

            if (is_numeric($key)) {
                continue;
            }

            $array[$key] = $this->mergeConfig($value, $merging[$key]);
        }

        return $array;
    }
}
