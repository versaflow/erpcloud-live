<?php

class ErpBackups
{
    public function __construct()
    {
        $this->setBackupServer('156.0.96.50', 'remote', 'Webmin@786');
        $this->setSourceServer('156.0.96.121', 'remote', 'Webmin@786');
        $this->setSSHServer('156.0.96.121', 'root', 'Ahmed777');
    }

    public function setSSHServer($ssh_host, $ssh_user, $ssh_pass)
    {
        $this->ssh_host = $ssh_host;
        $this->ssh_user = $ssh_user;
        $this->ssh_pass = $ssh_pass;
    }

    public function setSourceServer($host, $user, $pass)
    {
        $this->source_host = $host;
        $this->source_user = $user;
        $this->source_pass = $pass;
    }

    public function setBackupServer($host, $user, $pass)
    {
        $this->backup_host = $host;
        $this->backup_user = $user;
        $this->backup_pass = $pass;
    }

    public function setSourceDB($source_db)
    {
        $this->source_db = $source_db;
    }

    public function getBackupDatabases()
    {
        if (! $this->source_db) {
            return false;
        }
        $db_backups = [];
        $databases = $this->queryBackupMySQL('show databases;');
        $databases = explode(PHP_EOL, $databases);
        foreach ($databases as $database) {
            if (str_contains($database, $this->source_db)) {
                $db_backups[] = $database;
            }
        }

        return $db_backups;
    }

    public function queryBackupMySQL($query, $database = false, $log_query = false)
    {
        if (str_contains($query, 'mysqldump')) {
            $cmd = $query.' | mysql -h '.$this->backup_host.' -u '.$this->backup_user.' -p'.$this->backup_pass;
        } else {
            $cmd = 'echo "'.$query.'" | mysql --compress -h '.$this->backup_host.' -u '.$this->backup_user.' -p'.$this->backup_pass;
        }

        if ($database) {
            $cmd .= ' '.$database;
        }

        //dd($cmd);
        $result = Erp::ssh($this->ssh_host, $this->ssh_user, $this->ssh_pass, $cmd);
        //aa($result);
        if (str_contains($result, 'database exists')) {
            $result = 'success';
        }

        if ($log_query) {
            system_log('backup', 'db '.$this->source_db, $result, 'database', 'daily');
        }

        return $result;
    }

    public function backup($database, $tables = false, $monthly_only = false, $archive_cdr = false)
    {
        if (! $monthly_only || ($monthly_only && date('Y-m-d') == date('Y-m-01'))) {
            $this->setSourceDB($database);
            $backup_name = $this->source_db.'_'.date('Ymd');
            if ($archive_cdr) {
                $backup_name = 'cdr_archives';
            } else {
                try {
                    $result = $this->queryBackupMySQL('create database '.$backup_name);
                } catch (\Throwable $ex) {
                    exception_log($ex->getMessage());
                }
            }
            if ($this->source_db == 'cdr') {
                $result = '';
                $cdr_tables = get_tables_from_schema('pbx_cdr');
                foreach ($cdr_tables as $table) {
                    if ($archive_cdr) {
                        $skip = true;
                        if (str_contains($table, 'call_records_') && (str_contains($table, date('Y')) || str_contains($table, date('Y', strtotime('-1 year'))))) {
                            $skip = false;
                        }
                        if ($skip) {
                            continue;
                        }
                    }
                    $cmd = 'mysqldump --compress --no-create-db -h '.$this->source_host.' -u '.$this->source_user.' -p'.$this->source_pass.' '.$this->source_db;
                    $cmd .= ' '.$table;
                    // aa($cmd);
                    $table_result = $this->queryBackupMySQL($cmd, $backup_name, false);
                    // aa($table_result);
                    $result .= $table_result;
                }

                system_log('backup', 'db '.$this->source_db, $result, 'database', 'daily');

                return $result;
            } else {
                $cmd = 'mysqldump --compress --no-create-db -h '.$this->source_host.' -u '.$this->source_user.' -p'.$this->source_pass.' '.$this->source_db;

                if ($tables) {
                    $cmd .= ' '.$tables;
                }
                $result = $this->queryBackupMySQL($cmd, $backup_name, true);
            }

            return $result;
        }

        return false;
    }

    public function getBackupList()
    {
        if (! $this->source_db) {
            return false;
        }
        $backup_list = [];
        $backup_date = date('Y-m-d');
        $daily_backups = 4;
        $weekly_backups = 2;
        $monthly_backups = 4;

        if ($this->source_db == 'cdr') {
            $weekly_backups = 1;
            $monthly_backups = 1;
            $daily_backups = 1;
        }

        for ($i = 0; $i < $daily_backups; $i++) {
            $backup_list[] = $this->source_db.'_'.date('Ymd', strtotime($backup_date));
            $backup_date = date('Ymd', strtotime($backup_date.' - 1 day'));
        }

        $backup_date = date('Y-m-d', strtotime('monday'));
        for ($i = 0; $i < $weekly_backups; $i++) {
            $backup_list[] = $this->source_db.'_'.date('Ymd', strtotime($backup_date));
            $backup_date = date('Ymd', strtotime($backup_date.' - 1 week'));
        }
        $backup_date = date('Y-m-01');
        for ($i = 0; $i < $monthly_backups; $i++) {
            $backup_list[] = $this->source_db.'_'.date('Ymd', strtotime($backup_date));
            $backup_date = date('Ymd', strtotime($backup_date.' - 1 month'));
        }

        return collect($backup_list)->unique()->toArray();
    }

    public function deleteBackups($database)
    {
        $this->setSourceDB($database);

        $backups = $this->getBackupDatabases();
        $backup_list = $this->getBackupList();

        foreach ($backups as $backup) {
            if ($backup == 'cdr_archives') {
                continue;
            }
            if (! in_array($backup, $backup_list)) {
                $this->queryBackupMySQL('drop database '.$backup);
            }
        }
    }
}
