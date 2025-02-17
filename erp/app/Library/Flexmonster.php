<?php

use Symfony\Component\Process\Process;

class Flexmonster
{
    public function __construct()
    {
        $this->setServer(session('instance')->id);
    }

    public function setServer($instance_id)
    {
        $this->instance_id = $instance_id;
        $this->port = '950'.$instance_id;
        if ($instance_id > 10) {
            $this->port = '95'.$instance_id;
        }
        $this->erp_conn = \DB::connection('system')->table('erp_instances')->where('id', $instance_id)->pluck('db_connection')->first();

        $this->server_base_path = '/home/_admin/flexmonster_base';
        $this->server_path = '/home/_admin/flexmonster_'.$this->port;

        $this->config_path = $this->server_path.'/flexmonster-config.json';
        if (! file_exists($this->server_path)) {
            $cmd = 'cp -a '.$this->server_base_path.'/. '.$this->server_path.'/';
            $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        }

        if (! file_exists($this->server_path.'/flexmonster-data-server-'.$this->port)) {
            $cmd = 'mv '.$this->server_path.'/flexmonster-data-server '.$this->server_path.'/flexmonster-data-server-'.$this->port;
            $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
            $cmd = 'chmod 777 '.$this->config_path;
            $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        }
    }

    public function getServerSideReports()
    {
        \DB::connection($this->erp_conn)->table('erp_reports')->where('fds', 1)->update(['report_port' => $this->port]);
        $reports = \DB::connection($this->erp_conn)->table('erp_reports')->where('fds', 1)->get();
        foreach ($reports as $report) {
            $report_conn = $report->connection;
            if ($report_conn == 'default') {
                $report_conn = $this->erp_conn;
            }
            $query_error = '';
            $invalid_query = 0;
            $report_index = $report_conn.'_'.$report->id;
            if (empty($report->sql_query)) {
                $invalid_query = 1;
                $query_error = 'Empty SQL';
            } else {
                $sql_query = str_replace(PHP_EOL, ' ', $report->sql_query);
                try {
                    $sql = $sql_query.' LIMIT 1';
                    $result = \DB::connection($report_conn)->select($sql);
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    $invalid_query = 1;
                    $query_error = $ex->getMessage();
                }
            }

            \DB::connection($this->erp_conn)->table('erp_reports')->where('id', $report->id)->update(['invalid_query' => $invalid_query, 'query_error' => $query_error, 'report_index' => $report_index]);
        }

        return \DB::connection($this->erp_conn)->table('erp_reports')->where('fds', 1)->where('invalid_query', 0)->where('report_index', '>', '')->get();
    }

    public function testReportQueries()
    {
        \DB::connection($this->erp_conn)->table('erp_reports')->update(['invalid_query' => 0, 'query_error' => '']);
        $reports = \DB::connection($this->erp_conn)->table('erp_reports')->get();
        foreach ($reports as $report) {
            $report_conn = $report->connection;
            if ($report_conn == 'default') {
                $report_conn = $this->erp_conn;
            }
            $query_error = '';
            $invalid_query = 0;
            $report_index = $report_conn.'_'.$report->id;
            if (empty($report->sql_query)) {
                $invalid_query = 1;
                $query_error = 'Empty SQL';
            } else {
                $sql_query = str_replace(PHP_EOL, ' ', $report->sql_query);
                try {
                    $sql = $sql_query.' LIMIT 1';
                    $result = \DB::connection($report_conn)->select($sql);
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    $invalid_query = 1;
                    $query_error = $ex->getMessage();
                }
            }

            \DB::connection($this->erp_conn)->table('erp_reports')->where('id', $report->id)->update(['invalid_query' => $invalid_query, 'query_error' => $query_error, 'report_index' => $report_index]);
        }

    }

    public function loadIndexes()
    {
        $reports = $this->getServerSideReports();

        $config = [];
        $config['DataSources'] = [];
        $databases = Config::get('database')['connections'];

        $indexes = [];
        $connection_list = [];

        foreach ($reports as $report) {
            $db_conn = str_replace('_'.$report->id, '', $report->report_index);
            if (str_contains($db_conn, 'pbx') && $this->port != 9501) {
                continue;
            }
            if (! isset($indexes[$db_conn])) {
                $indexes[$db_conn] = [];
            }
            $sql_query = str_replace(PHP_EOL, ' ', $report->sql_query);
            $indexes[$db_conn][$report->report_index] = ['Query' => $sql_query];
            if (! in_array($db_conn, $connection_list)) {
                $connection_list[] = $db_conn;
            }
        }

        foreach ($connection_list as $connection) {
            $connection_info = $databases[$connection];
            $driver = 'mysql';
            if ($connection == 'pbx' || $connection == 'pbx_cdr') {
                $driver = 'pgsql';
            }
            $datasource = [
                'Type' => 'database',
                'DatabaseType' => $driver,
                'ConnectionString' => 'Server='.$connection_info['host'].';Port='.$connection_info['port'].';Uid='.$connection_info['username'].';Pwd='.$connection_info['password'].';Database='.$connection_info['database'].'; convert zero datetime=True',
                'Indexes' => $indexes[$connection],
            ];
            $config['DataSources'][] = $datasource;
        }

        $config['Security'] = [
            'Authorization' => [
                'Enabled' => false,
            ],
            'CORS' => [
                'AllowOrigin' => '*',
            ],
        ];
        /*
        $config["HTTPS"] = [
        "Enabled" => true,
        "Protocols" => "Http1AndHttp2",
        "Certificate" => [
        "Path" =>  "/usr/local/directadmin/data/users/admin/domains/turnkeyerp.io.cert",
        "AllowInvalid" =>  true
        ]
        ];
        */
        $config['DataStorageOptions'] = [
            'DataRefreshTime' => '30',
        ];
        $config['Port'] = $this->port;

        $json = json_encode($config, JSON_PRETTY_PRINT);

        file_put_contents($this->config_path, $json);
    }

    public function getLinuxPid($process)
    {
        $cmd = '/usr/sbin/pidof '.$process;

        $output = $this->runCommand($cmd, 'instant');

        return str_replace(PHP_EOL, '', trim($output));
    }

    private function runCommand($cmd, $output = false, $stream_success = '')
    {
        if (! $output) {
            $cmd .= ' > /dev/null 2>&1 &';
        }

        $process = Process::fromShellCommandline($cmd);

        $process->setTimeout(180);
        if (! $output) {
            // start executes shell command without waiting for response or a program that outputs a stream
            $process->start();

            return true;
        } elseif ($output == 'stream') {
            $process->start();

            foreach ($process as $type => $data) {
                if ($type === 'err') {
                } else {
                }

                if (str_contains($data, $stream_success)) {
                    return true;
                }
            }
        } elseif ($output == 'instant') {
            // executes shell command and returns response
            $process->run();

            return $process->getOutput();
        }
    }

    public function dataServerStop()
    {
        $process_id = $this->getLinuxPid('flexmonster-data-server-'.$this->port);

        if ($process_id) {
            $cmd = 'kill -9 '.$process_id;

            $this->runCommand($cmd);

            return true;
        }

        return false;
    }

    public function dataServerStatus()
    {
        $process_id = $this->getLinuxPid('flexmonster-data-server-'.$this->port);
        if ($process_id) {
            return true;
        }

        return false;
    }

    public function dataServerStart()
    {
        $process_id = $this->getLinuxPid('flexmonster-data-server-'.$this->port);

        if ($process_id) {
            return false;
        }
        $cmd = 'cd /home/_admin/flexmonster_'.$this->port.' && ./flexmonster-data-server-'.$this->port;

        return $this->runCommand($cmd, 'stream', 'DataServer.Startup|Content');
    }

    public function dataServerRestart()
    {
        $this->dataServerStop();
        $result = $this->dataServerStart();

        return $result;
    }
}
