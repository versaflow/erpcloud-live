<?php

function exception_log($ex)
{
    try {
        // $debug_backtrace = debug_backtrace();
        if (! is_string($ex)) {
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine().PHP_EOL.$ex->getTraceAsString();
            $error .= PHP_EOL.'>>> ALL_VARS: '.json_encode(get_defined_vars());
            \Log::debug($error);
        }
    } catch (\Throwable $ex) {
        $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine().PHP_EOL.$ex->getTraceAsString();
        \Log::debug($error);
    }
}

function module_log($module_id, $row_id, $action, $note = '')
{

    $current_conn = \DB::getDefaultConnection();
    set_db_connection($conn);
    if ($module_id && $row_id && $action) {
        if (Schema::hasTable('erp_module_log')) {
            $user_id = session('user_id');
            if (empty($user_id)) {
                $user_id = get_system_user_id();
            }
            $data = [
                'module_id' => $module_id,
                'row_id' => $row_id,
                'action' => $action,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $user_id,
            ];
            if ($note) {
                $data['note'] = $note;
            }
            if ($action == 'update' && ! empty($note)) {
                \DB::table('erp_module_log')->insert($data);
            } elseif ($action != 'update') {
                //\DB::table('erp_module_log')->updateOrInsert(['module_id'=>$module_id,'row_id'=>$row_id], $data);
                \DB::table('erp_module_log')->insert($data);
            }
        }
    }
    set_db_connection($current_conn);
}

function aa($var, $session_filter = true)
{
    $trace = '';
    try {
        $debug_backtrace = debug_backtrace();
        if (! empty($debug_backtrace) && $debug_backtrace[0] && $debug_backtrace[0]['file']) {
            $trace .= basename($debug_backtrace[0]['file']).': '.$debug_backtrace[0]['line'].' --> '.$debug_backtrace[0].' --> ';
        }
        $trace .= json_encode($var);
        \Log::debug($trace);

        if (str_contains(request()->header('User-Agent'), 'Edg/')) {
            if (php_sapi_name() !== 'cli') {
                if (! is_array($var) && ! is_string($var)) {
                    $var = print_r($var, true);
                }
                $log_var = false;
                if (empty(session()) || empty(session('user_id')) || empty(session('instance')->directory)) {
                    $log_var = true;
                } else {
                    if ($session_filter && session('user_id') == 3696 && session('instance')->directory == 'telecloud') {
                        $log_var = true;
                    } elseif ($session_filter && session('user_id') && session('instance')->directory != 'telecloud') {
                        $log_var = true;
                    } elseif (! $session_filter) {
                        $log_var = true;
                    }
                }
                if ($log_var) {
                    \Log::debug($var);
                    $trace = false;
                    $debug_backtrace = debug_backtrace();
                    if (! empty($debug_backtrace) && $debug_backtrace[0] && $debug_backtrace[0]['file']) {
                        $trace = $debug_backtrace[0]['file'].':'.$debug_backtrace[0]['line'];
                    }
                    if ($trace) {
                        \Log::debug($trace);
                    }
                }
            }
        }
    } catch (\Throwable $ex) {
        \Log::debug($ex);
    }
}

function system_log($type, $action, $result, $backup_type, $frequency, $success = null, $event_id = 0)
{
    $result = strtolower($result);
    if ($success === null) {
        $success = 1;
        if ((str_contains($action, 'git') && ! str_contains($result, 'insertions') && ! str_contains($result, 'files changed') && ! str_contains($result, 'Everything up-to-date')) || ! str_contains($action, 'git')) {
            if (str_contains($result, 'fail') || str_contains($result, 'denied') || str_contains($result, 'error') || str_contains($result, 'exception') || str_contains($result, 'No such file')) {
                $success = 0;
            }
        }
    }

    if (empty($frequency)) {
        $frequency = '';
    }
    if (! $action) {
        return false;
    }
    $insert_data = [
        'created_date' => date('Y-m-d H:i:s'),
        'type' => $type,
        'backup_type' => $backup_type,
        'frequency' => $frequency,
        'action' => $action,
        'result' => str_replace(PHP_EOL, ' ', $result),
        'success' => $success,
        'event_id' => $event_id,
    ];
    // aa($insert_data);

    //if($event_id){
    //    $insert_data['module_id'] = \DB::connection('default')->table('erp_form_events')->where('id',$event_id)->pluck('module_id')->first();
    //}

    \DB::connection('default')->table('erp_system_log')->insert($insert_data);
    if (is_main_instance()) {
        if ($type == 'backup' && $backup_type == 'database' && $success == 0) {
            //debug_email('Backup failed - '.$action);
            admin_email('Backup failed - '.$action);
        }
    }
}

function generateCallTrace()
{
    $e = new Exception;
    $trace = explode("\n", $e->getTraceAsString());
    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);
    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method
    $length = count($trace);
    $result = [];

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1).')'.substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    return "\t".implode("\n\t", $result);
}
