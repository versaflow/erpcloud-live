<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ScheduleList::class,
    ];

    protected function bootstrappers()
    {
        return array_merge(
            parent::bootstrappers(),
        );
    }

    /**
     * Define the application's command schedule.
     */
    public function schedule(Schedule $schedule)
    {
        // IMPORTANT ALL SCHEDULE FUNCTIONS SHOULD DD, DIE OR EXIT
        // session('instance')->id = 1;
        $instances = \DB::connection('system')->table('erp_instances')->where('installed', 1)->get();
        foreach ($instances as $instance) {
            $instance_dir = $instance->db_connection;
            $jobs = \DB::connection($instance_dir)->table('erp_form_events')
                ->where('frequency_cron', '>', '')
                ->where('active', 1)
                ->where('type', 'schedule')
                ->get();
            // print_r($jobs);
            foreach ($jobs as $job) {
                // if ($job->id == 1648)
                //     print_r($job);

                if (date('Y-m-d H:i:s', strtotime($job->last_run)) > date('Y-m-d H:i:s', strtotime('-5 minutes')) && $job->started == 1 && $job->completed == 0) {
                    continue;
                }

                if (!function_exists($job->function_name)) {
                    continue;
                }

                if (str_contains($job->function_name, 'teleshield')) {
                    $schedule->call(function () use ($job, $instance, $instance_dir) {
                        $this->kernel_run_command($job->id, $instance);
                    })
                    ->name($instance_dir.' - '.$job->id.' - '.$job->frequency_cron)
                    ->cron($job->frequency_cron);
                } elseif ('Minutely' == $job->frequency_type || 'Hourly' == $job->frequency_type) {
                    // do not run schedule during pbx reboot
                    $schedule->call(function () use ($job, $instance, $instance_dir) {
                        $this->kernel_run_command($job->id, $instance);
                    })
                    ->name($instance_dir.$job->id.' - '.$job->frequency_cron)
                    ->cron($job->frequency_cron);
                } else {
                    // move daily events 1 hour up to prevent overlap with reboots
                    if (!str_contains($job->function_name, 'reboot')) {
                        $cronParts = explode(' ', $job->frequency_cron);

                        // Update the hour part (assuming it is in the first position)
                        $hour = $cronParts[1];
                        if ($hour == '00' || $hour == '06') {
                            $job->frequency_cron = addHourtoCronFrequency($job->frequency_cron);
                        }
                    }

                    if ('Monthly' == $job->frequency_type) {
                        if (str_contains($job->frequency_cron, 31)) {
                            $job->frequency_cron = str_replace(31, date('t'), $job->frequency_cron);
                        }
                    }

                    if (str_contains($job->function_name, 'reboot')) {
                        $schedule->call(function () use ($job, $instance, $instance_dir) {
                            $this->kernel_run_command($job->id, $instance);
                        })
                        ->cron($job->frequency_cron)
                        ->name($instance_dir.' - '.$job->id.' - '.$job->frequency_cron);
                    } else {
                        $schedule->call(function () use ($job, $instance, $instance_dir) {
                            $this->kernel_run_command($job->id, $instance);
                        })
                        ->name($instance_dir.' - '.$job->id.' - '.$job->frequency_cron)
                        ->cron($job->frequency_cron);
                    }
                }
            }
        }
    }

    /**
     * Register the Closure based commands for the application.
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }

    public function kernel_run_command($id, $instance)
    {
        // delete the cached config otherwise the multitenant will default to main instance and mergeconfigfrom will fail
        print_r(' - Run command started'.PHP_EOL);
        $startTime = \Carbon\Carbon::now();

        // $config_cache_file = '/home/erpcloud-live/htdocs/erp/bootstrap/cache/config.php';
        // if (file_exists($config_cache_file)) {
        //     unlink($config_cache_file);
        // }

        // $instance_dir = $instance->db_connection;
        // if ($instance_dir == 'erpcloud') {
        //     return false;
        // }
        // $directory = base_path().'/instance_config/'.$instance_dir;

        // foreach (Finder::create()->in($directory)->name('*.php') as $file) {
        //     $this->mergeConfigFrom($file->getRealPath(), basename($file->getRealPath(), '.php'));
        // }

        // $this->app['config']->set('app.url', 'https://'.$instance->domain_name);

        // $configTables = [
        //     'modules' => \DB::connection($instance->db_connection)->table('erp_cruds')->get(),
        //     'menus' => \DB::connection($instance->db_connection)->table('erp_menu')->get(),
        //     'module_fields' => \DB::connection($instance->db_connection)->table('erp_module_fields')->get(),
        //     'users' => \DB::connection($instance->db_connection)->table('erp_users')->get(),
        //     'roles' => \DB::connection($instance->db_connection)->table('erp_user_roles')->get(),
        //     'forms' => \DB::connection($instance->db_connection)->table('erp_forms')->get(),
        //     'menu_access' => \DB::connection($instance->db_connection)->table('erp_menu_role_access')->get(),
        //     'layouts' => \DB::connection($instance->db_connection)->table('erp_grid_views')->get(),
        //     'grid_styles' => \DB::connection($instance->db_connection)->table('erp_grid_styles')->get(),
        //     // Add more config tables as needed
        // ];

        // $this->app->instance('erp_config', $configTables);

        // config(['app.url' => 'https://'.$instance->domain_name]);
        try {
            //CONNECT TO INSTANCE
            \DB::purge('default');
            $instance_dir = $instance->db_connection;
            $instance->directory = $instance_dir;
            $conns = \Config::get('database');

            foreach ($conns['connections'] as $name => $c) {
                if ($instance_dir == $name) {
                    \Config::set('database.connections.default', $c);
                }
            }
            \DB::reconnect('default');

            session(['instance' => $instance]);
            session(['app_ids' => get_installed_app_ids()]);

            $command = \DB::connection('default')->table('erp_form_events')->where('id', $id)->get()->first();
            // @pbxoffline
            // DISABLE PBX SCHEDULE FUNCTIONS
            $pbx_offline = env('PBX_DB_OFFLINE');
            if ($pbx_offline && $command->function_name != 'schedule_check_pbx_db') {
                $module_connection = \DB::connection('default')->table('erp_cruds')->where('id', $command->module_id)->pluck('connection')->first();
                if ($module_connection == 'pbx' || $module_connection == 'pbx_cdr') {
                    return false;
                }
            }

            $function_exists = true;
            if (empty($command) || empty($command->function_name)) {
                $function_exists = false;
            } else {
                $function_name = $command->function_name;
                if (!is_string($function_name) || !function_exists($function_name)) {
                    $function_exists = false;
                }
            }

            if (!$function_exists) {
                system_log('schedule', $id, 'function does not exists', 'schedule', '', 0, $id);
                return false;
            }

            $frequency = $command->frequency_type;
            \DB::connection('default')->table('erp_form_events')->where('id', $id)->update(['last_run' => date('Y-m-d H:i:s'), 'started' => 1, 'completed' => 0]);
            if ('Minutely' != $frequency) {
                system_log('schedule', $function_name.' started', 'success', 'schedule', $frequency, 1, $id);
            }

            // print_r($function_name);
            $function_name();
            // aa($frequency);
            // aa($id);
            // aa($frequency);
            if ('Minutely' != $frequency) {
                system_log('schedule', $function_name.' completed', 'success', 'schedule', $frequency, 1, $id);
            }

            $finishTime = \Carbon\Carbon::now();
            $duration = $finishTime->diff($startTime)->format('%I:%S');
            \DB::connection('default')->table('erp_form_events')->where('id', $id)->update(['started' => 0, 'completed' => 1, 'last_success' => date('Y-m-d H:i:s'), 'run_time' => $duration, 'last_failed' => null, 'error' => '']);
        } catch (\Throwable $ex) {
            exception_log($ex);
            if ($function_name != 'schedule_zammad_import_tickets' && $function_name != 'schedule_update_workboard_stats' && !str_contains($error, 'Deadlock found')) {
                create_github_issue(ucwords(str_replace('_', ' ', $function_name)).' Error ', $function_name.' '.session('instance')->name.': '.$id.' '.date('Y-m-d H:i').PHP_EOL.$error);
            }
            \DB::connection('default')->table('erp_form_events')->where('id', $id)->update(['started' => 0, 'completed' => 1, 'last_failed' => date('Y-m-d H:i:s'), 'run_time' => 0, 'error' => $error]);
            if (!$function_name) {
                system_log('schedule', $function_name, 'function not found, id: '.$id.', command: '.$command, 'schedule', $frequency, 0, $id);
            } else {
                system_log('schedule', $function_name, $ex->getMessage(), 'schedule', $frequency, 0, $id);
            }
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param string $path
     * @param string $key
     *
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
     * @return array
     */
    protected function mergeConfig(array $original, array $merging)
    {
        $array = array_merge($merging, $original);

        foreach ($original as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (!Arr::exists($merging, $key)) {
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
