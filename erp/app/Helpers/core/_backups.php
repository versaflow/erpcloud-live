<?php

function git_backups()
{
    if (! is_main_instance()) {
        return false;
    }
    // git push
    $weekend = (date('N', strtotime($date)) >= 6);
    if (! $weekend) {
        $time_start = date('Y-m-d 00:00');
        $time_end = date('Y-m-d 20:00');
        $now = date('Y-m-d H:i');

        if ($time_start <= $now and $time_end >= $now) {
            $cmd = '/home/erpcloud-live/htdocs/erp/zadmin/assets/gitpush.sh';
            // aa($cmd);
            $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
            system_log('backup', 'code erp git', $result, 'code', 'hourly');
        }
    }

    return true;
}

function database_backups()
{
    if (! is_main_instance()) {
        return true;
    }

    $erp_backups = new ErpBackups;

    $instances = \DB::table('erp_instances')->where('installed', 1)->where('installed', 1)->get();
    $erp_dbs = [];
    foreach ($instances as $i) {
        $conf = Config::get('database.connections.'.$i->db_connection);
        $erp_dbs[] = $conf['database'];
    }

    foreach ($erp_dbs as $erp_db) {
        $erp_backups->backup($erp_db);
        $erp_backups->deleteBackups($erp_db);
    }

    $erp_backups->setSourceServer('156.0.96.60', 'remote', 'Webmin@786');
    $pbx_dbs = ['cloudpbx', 'cdr'];
    foreach ($pbx_dbs as $pbx_db) {
        $erp_backups->backup($pbx_db);
        $erp_backups->deleteBackups($pbx_db);
    }

    // backup_nextcloud_db();
}

function backup_nextcloud_db()
{
    $file_name = 'nextcloud'.date('Y-m-d').'.sql';
    $remote_path = '/var/www/backups/'.$file_name;
    $local_path = '/home/_admin/nextcloud_backups/'.$file_name;

    // delete backups older than a month
    $cmd = 'find /var/www/backups -type f -mtime +30 -exec rm {} \;';
    $result = Erp::ssh('156.0.96.90', 'root', 'Ahmed777', $cmd);

    $cmd = 'export PGPASSWORD=UfSft1cjS#z4iSrPictPMFao@FNa@ && pg_dump --inserts -h 156.0.96.90 -U nextcloud_db_user nextcloud_db > '.$remote_path.' && unset PGPASSWORD';

    $result = Erp::ssh('156.0.96.90', 'root', 'Ahmed777', $cmd);

    $ssh = new \phpseclib\Net\SSH2('156.0.96.90');
    if ($ssh->login('root', 'Ahmed777')) {
        $scp = new \phpseclib\Net\SCP($ssh);

        $result = $scp->get($remote_path, $local_path);

        if (! $result) {
            create_github_issue('Nextcloud db could not be downloaded', 'remote path: '.$remote_path.', local path: '.$local_path);
        }
    }
}

function fusionpbx_postgres_db_backups()
{
    $cmd = 'PGPASSWORD="ZsjCFn3LU2d7dirL0eWxywIum4" pg_dump fusionpbx -U fusionpbx -h localhost > /var/www/_admin/db_backups/fusionpbx_'.date('Ymd').'.sql;';

    $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);

    $cmd = 'cd  /var/www/_admin/db_backups && ls -la';

    $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
    $backups = [];
    $lines = explode(PHP_EOL, $result);
    foreach ($lines as $l) {
        if (str_contains($l, 'fusionpbx_') && str_contains($l, '.sql')) {
            $words = explode(' ', $l);
            foreach ($words as $w) {
                if (str_starts_with($w, 'fusionpbx_')) {
                    $backups[] = $w;
                }
            }
        }
    }

    $backup_list = [];
    $backup_date = date('Y-m-d');
    for ($i = 0; $i < 2; $i++) {
        $backup_list[] = 'fusionpbx_'.date('Ymd', strtotime($backup_date)).'.sql';
        $backup_date = date('Ymd', strtotime($backup_date.' - 1 day'));
    }

    $backup_date = date('Y-m-d', strtotime('monday'));
    for ($i = 0; $i < 2; $i++) {
        $backup_list[] = 'fusionpbx_'.date('Ymd', strtotime($backup_date)).'.sql';
        $backup_date = date('Ymd', strtotime($backup_date.' - 1 week'));
    }
    $backup_date = date('Y-m-01');
    for ($i = 0; $i < 4; $i++) {
        $backup_list[] = 'fusionpbx_'.date('Ymd', strtotime($backup_date)).'.sql';
        $backup_date = date('Ymd', strtotime($backup_date.' - 1 month'));
    }

    if (! empty($backups) && count($backups) > 0) {
        foreach ($backups as $backup) {
            if (! in_array($backup, $backup_list)) {
                $cmd = 'cd  /home/erpcloud-live/htdocs/erp/zadmin/db_backups && rm '.$backup;
                $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
            }
        }
    }
}

function dev_gitpull()
{
    $cmd = 'cd /home/erpcloud-live/htdocs/erp && git pull';
    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
}

function pbx_script_backups()
{
    pbx_scripts_backup();
}

function pbx_scripts_backup()
{
    //rsync
    //https://www.atlantic.net/vps-hosting/how-to-use-rsync-copy-sync-files-servers/?__cf_chl_captcha_tk__=0d4f15087b401f855953dae1645193dc98e64dcb-1595926099-0-AQTplmOKaGH1NxnTrgmxEUUvtDkUVLkMmSKSG-pRU8NocJ8dvAhSX3wXA1b3_tJ03ZaH3xh-NSYPjfSe9dEavHYPJmxh3iiEIiRH5kAd1LMoXcOYORcWd2qH1UbsAgGYQdqgvVuJWGW3gybMbG4QhIHhzQiNHHXZXLPm07jGvO9Vel40sOIe8bym5sYDIjXcsrrEgNABPLjKU-AicuRNgni8NPWs2hMW92ZyKMUJw6lWBta0AbaGl67OKScFr_W-LUS8kJvOWguxyDGnRBCNNTXb7vEfBnIKPDllDdokLGpShbnycXfF09v9JDJW6xDyinwtuBubZrahHc7L47PwEoE3zAoEXOoe8Cs24JY0tTbr-HnnG4AvnYFZ6Rk586eTQHmkIW7l9_jTuVOpZhdE5u8d0kWF5GO6KyUKahdwwCAYy-NALZjKaghAXH2IEutTwHCssmhkAs04pHrJL5NKxtv1cXkRCp4urQMwEsyXB2sclTZDesgCbztBfbVjzeh9ODrJqus7iu26H0W37ziF-0kqcuqjcb7zTKGVDYFAou4pTnCtYNDw8f9OIsq3gR854Q
    //rsync -rtu --delete SOURCE root@x.x.x.x:/DESTINATION

    $script_folder = '/usr/share/freeswitch/scripts/';
    $backup_source = '/usr/share/freeswitch/scripts_backup_'.date('Ymd').'.zip';
    $backup_destination = '/var/www/fusionpbx/scripts_backup_'.date('Ymd').'.zip';

    $cmd = 'zip -r '.$backup_source.' '.$script_folder;
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $cmd = 'mv '.$backup_source.' '.$backup_destination;
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
}

function monthly_maintenance()
{
    if (! is_main_instance()) {
        return false;
    }

    // loop months starting 2020, delete month folders, format /archive/2020/Jan
    // recordings keep 7 days

    $cmd = 'mysqlcheck -h 127.0.0.1 -u root -pWebmin321 --auto-repair --check --all-databases && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script host1 repair all dbs', $result, 'script', 'monthly');

    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script host2 repair all dbs', $result, 'script', 'monthly');

    $cmd = 'find /var/lib/freeswitch/recordings/ -name "*.tar.gz" -mindepth 0 -mtime +60 -delete && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script pbx delete recording archives', $result, 'script', 'monthly');

    $cmd = 'find /var/log/freeswitch/freeswitch.log.* -mtime +7 -exec rm {} \; && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script pbx delete log files', $result, 'script', 'monthly');

    $cmd = 'find /var/lib/freeswitch/recordings/*/archive/*  -name "*.mp3" -mtime +90 -exec rm {} \; && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script pbx delete mp3 recordings', $result, 'script', 'monthly');

    $cmd = 'find /var/lib/freeswitch/recordings/*/archive/*  -name "*.wav" -mtime +90 -exec rm {} \; && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script pbx delete wav recordings', $result, 'script', 'monthly');

    $cmd = 'find /var/lib/freeswitch/storage/voicemail/default/*  -name "msg_*.wav" -mtime +90 -exec rm {} \;  && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script pbx delete voicemail wav', $result, 'script', 'monthly');

    $cmd = 'find /var/lib/freeswitch/storage/voicemail/default/*  -name "msg_*.mp3" -mtime +90 -exec rm {} \;  && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'script pbx delete voicemail mp3', $result, 'script', 'monthly');
    \DB::connection('pbx')->table('v_voicemail_messages')->where('created_epoch', '<', strtotime('-180 days'))->delete();

    mysql_cdr_rollover();
}

function mysql_cdr_rollover()
{
    //return false;
    $db_name = 'cdr';

    // OUTBOUND
    $cdr_table = 'call_records_outbound';
    $cdr_last_table = 'call_records_outbound_lastmonth';
    $cdr_archive_table = 'call_records_outbound_'.strtolower(date('YM', strtotime('-2 month')));

    /// PBX
    // archive last month table
    if (\Schema::connection('pbx_cdr')->hasTable($cdr_last_table)) {
        $cmd = 'echo "use '.$db_name.'; RENAME TABLE '.$cdr_last_table.' TO '.$cdr_archive_table.'" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
        $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    }

    // create last month table
    $cmd = 'echo "use '.$db_name.'; RENAME TABLE '.$cdr_table.' TO '.$cdr_last_table.'" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    // create empty call_records table
    $cmd = 'echo "use '.$db_name.'; CREATE TABLE '.$cdr_table.' SELECT * FROM '.$cdr_last_table.' WHERE 1=0; ALTER TABLE '.$cdr_table.' MODIFY id int NOT NULL AUTO_INCREMENT PRIMARY KEY;" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'db pbx cdr archive', $result, 'database', 'monthly');

    \DB::connection('pbx_cdr')->statement('INSERT INTO '.$cdr_table.' SELECT * FROM '.$cdr_last_table." WHERE start_time LIKE '".date('Y-m')."%';");
    \DB::connection('pbx_cdr')->table($cdr_last_table)->where('start_time', 'LIKE', date('Y-m').'%')->delete();

    // INBOUND

    $cdr_table = 'call_records_inbound';
    $cdr_last_table = 'call_records_inbound_lastmonth';
    $cdr_archive_table = 'call_records_inbound_'.strtolower(date('YM', strtotime('-2 month')));

    /// PBX
    // archive last month table
    if (\Schema::connection('pbx_cdr')->hasTable($cdr_last_table)) {
        $cmd = 'echo "use '.$db_name.'; RENAME TABLE '.$cdr_last_table.' TO '.$cdr_archive_table.'" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
        $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    }

    // create last month table
    $cmd = 'echo "use '.$db_name.'; RENAME TABLE '.$cdr_table.' TO '.$cdr_last_table.'" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    // create empty call_records table
    $cmd = 'echo "use '.$db_name.'; CREATE TABLE '.$cdr_table.' SELECT * FROM '.$cdr_last_table.' WHERE 1=0; ALTER TABLE '.$cdr_table.' MODIFY id int NOT NULL AUTO_INCREMENT PRIMARY KEY;" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    system_log('backup', 'db pbx cdr inbound archive', $result, 'database', 'monthly');

    \DB::connection('pbx_cdr')->statement('INSERT INTO '.$cdr_table.' SELECT * FROM '.$cdr_last_table." WHERE start_time LIKE '".date('Y-m')."%';");
    \DB::connection('pbx_cdr')->table($cdr_last_table)->where('start_time', 'LIKE', date('Y-m').'%')->delete();

    $cmd = 'echo "use '.$db_name.'; TRUNCATE TABLE call_records_inbound_variables" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    $cmd = 'echo "use '.$db_name.'; TRUNCATE TABLE call_records_outbound_variables" | mysql -u root -pWebmin786 && echo "success: $?" || echo "fail: $?"';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);

    move_archive_tables_to_backup_server();
}

function move_archive_tables_to_backup_server()
{
    // MOVE ARCHIVE TABLES TO BACKUP SERVER
    $erp_backups = new ErpBackups;

    $erp_backups->setSourceServer('156.0.96.60', 'remote', 'Webmin@786');
    $pbx_db = 'cdr';

    $erp_backups->backup($pbx_db, false, false, true);

    $cdr_tables = get_tables_from_schema('pbx_cdr');
    foreach ($cdr_tables as $cdr_table) {
        if (str_contains($cdr_table, 'call_records_') && (str_contains($cdr_table, date('Y')) || str_contains($cdr_table, date('Y', strtotime('-1 year'))))) {
            try {
                $cdr_count = \DB::connection('pbx_cdr')->table($cdr_table)->count();
            } catch (\Throwable $ex) {
                $cdr_count = 0;
            }
            try {
                $backup_cdr_count = \DB::connection('backup_server')->table($cdr_table)->count();
            } catch (\Throwable $ex) {
                $backup_cdr_count = 0;
            }
            if ($backup_cdr_count > 0 && $cdr_count == $backup_cdr_count) {
                \Schema::connection('pbx_cdr')->dropIfExists($cdr_table);
            }
        }
    }
}

function cdr_rollover()
{
    $debug = false;

    if ($debug) {
        $cdr_tables = ['call_records_inbound_test'];
    } else {
        $cdr_tables = ['call_records_outbound', 'call_records_inbound', 'call_records_outbound_variables'];
    }

    $truncate_tables = ['call_records_onnet', 'call_records_onnet_variables', 'call_records_inbound_variables'];
    foreach ($truncate_tables as $truncate_table) {
        if (\Schema::connection('pbx_cdr')->hasTable($truncate_table)) {
            \DB::connection('pbx_cdr')->table($truncate_table)->truncate();
        }
    }
    // DROP EXISTING BACKUP TABLES
    foreach ($cdr_tables as $cdr_table) {
        $backup_table = $cdr_table.'_backup';
        \Schema::connection('pbx_cdr')->dropIfExists($backup_table);
    }

    // CREATE LASTMONTH AND ARCHIVE TABLES

    foreach ($cdr_tables as $cdr_table) {
        $backup_table = $cdr_table.'_backup';

        if (! \Schema::connection('pbx_cdr')->hasTable($backup_table)) {
            \DB::connection('pbx_cdr')->statement('CREATE TABLE '.$backup_table.' LIKE  '.$cdr_table.' ;');
            \DB::connection('pbx_cdr')->statement('INSERT INTO '.$backup_table.' (SELECT * FROM '.$cdr_table.')');
        }
    }

    foreach ($cdr_tables as $cdr_table) {
        $backup_table = $cdr_table.'_backup';

        if (\Schema::connection('pbx_cdr')->hasTable($backup_table)) {
            $lastmonth_table = $cdr_table.'_lastmonth';
            $archive_table = $cdr_table.'_archive_'.strtolower(date('YM', strtotime('-2 month')));
            if (\Schema::connection('pbx_cdr')->hasTable($lastmonth_table)) {
                \DB::connection('pbx_cdr')->statement('ALTER TABLE '.$lastmonth_table.' RENAME TO '.$archive_table);
            }

            \DB::connection('pbx_cdr')->statement('ALTER TABLE '.$cdr_table.' RENAME TO '.$lastmonth_table);

            \DB::connection('pbx_cdr')->statement('CREATE TABLE '.$cdr_table.' LIKE  '.$backup_table.' ;');
        }
    }

    // VERIFY LAST MONTH TABLE
    /*
    foreach($cdr_tables as $cdr_table){
        $lastmonth_table = $cdr_table.'_lastmonth';
        $backup_table = $cdr_table.'_backup';
        $lastmonth_table_count = \DB::connection('pbx_cdr')->table($lastmonth_table)->count();
        $backup_table_count = \DB::connection('pbx_cdr')->table($backup_table)->count();

        if($lastmonth_table_count < $backup_table_count){
            \Schema::connection('pbx_cdr')->dropIfExists($cdr_table);
            \DB::connection('pbx_cdr')->statement('CREATE TABLE '.$cdr_table.' LIKE  '.$backup_table.' ;');
            \DB::connection('pbx_cdr')->statement('INSERT INTO '.$cdr_table.' (SELECT * FROM '.$backup_table.')');
            \Schema::connection('pbx_cdr')->dropIfExists($backup_table);
            debug_email('CDR Lastmonth table not verified '.$cdr_table.' restored');
        }else{
            \Schema::connection('pbx_cdr')->dropIfExists($backup_table);
        }
    }
    */

    // DROP ARCHIVE TABLES - OUTBOUND 6 MONTHS - INBOUND 6 MONTHS

    $schema_tables = get_tables_from_schema('pbx_cdr');
    foreach ($schema_tables as $schema_table) {
        if (str_contains($schema_table, '_archive_')) {
            $table_name_arr = explode('_archive_', $schema_table);
            $table_date = $table_name_arr[1];
            $table_date = date('Y-m-d', strtotime($table_date));
            if ($table_date < date('Y-m-d', strtotime('-6 months'))) {
                debug_email('CDR archive table should be dropped '.$schema_table);
                // \Schema::connection('pbx_cdr')->dropIfExists($schema_table);
            }
        }
    }
}

function schedule_git_config_backups()
{
    if (is_main_instance()) {
        $cmd = '/home/admin_git.sh && echo "success: $?" || echo "fail: $?"';
        $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
        system_log('backup', 'config host1', $result, 'code', 'weekly');

        $cmd = '/home/admin_git.sh && echo "success: $?" || echo "fail: $?"';
        $result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        system_log('backup', 'config host2', $result, 'code', 'weekly');

        //$cmd = '/var/www/git_admin.sh';
        //$result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        // system_log('backup', 'config pbx', $result, 'code', 'weekly');
    }
}

function searchEmailBySubjectAndDate($keyword)
{
    // Connect to the IMAP server
    $account = [];
    $account['host'] = 'mail.cloudtelecoms.co.za';
    $account['port'] = '993';
    $account['encryption'] = 'ssl';
    $account['validate_cert'] = 0;
    $account['authentication'] = 'imap';
    $account['username'] = 'helpdesk@telecloud.co.za';
    $account['password'] = 'nimda123';

    $client = \MailClient::make($account);
    $client->connect();

    // Get the inbox folder
    $inboxFolder = $client->getFolder('INBOX');

    // Search for emails within the last two days
    $searchDate = new \DateTime('-1 days');

    $emails = $inboxFolder->search()->since($searchDate)->get();

    // Array to store matching emails
    $matchingEmails = [];

    foreach ($emails as $email) {
        // Retrieve email details
        $subject = $email->getSubject();
        // Check if the subject contains the keyword
        if (stripos($subject, $keyword) !== false) {
            // Add the matching email to the result array
            $matchingEmails[] = $subject;
        }
    }

    // Disconnect from the IMAP server
    $client->disconnect();

    // Return the matching emails
    return $matchingEmails;
}

function restore_pbx_db()
{
    return false;
    //// copy database
    $source_db = 'cloudpbx_20210329';
    $copy_db = 'cloudpbx';

    $cmd = 'mysqldump -h 156.0.96.22 -u remote -pWebmin@321 '.$source_db.'  | mysql -u root -pWebmin786 '.$copy_db;
    //$cmd = 'mysql -u root -pWebmin786 '.$backup_name.' < /var/www/html/_admin/backups/voice_server-database-202003251346.backup';
    //$result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
}

function restore_pbx_table()
{
    return false;
    //// copy database
    $source_db = 'cloudpbx_backup';
    $copy_db = 'cloudpbx';
    $table = 'v_phone_numbers';
    $cmd = 'mysqldump -u root -pWebmin786 '.$source_db.' '.$table.' | mysql -u root -pWebmin786 '.$copy_db;
    //$cmd = 'mysql -u root -pWebmin786 '.$backup_name.' < /var/www/html/_admin/backups/voice_server-database-202003251346.backup';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
}

function restore_pbx_wholesale_balances()
{
    return false;
    $domains = \DB::connection('pbx')->table('v_domains')->where('cost_calculation', 'volume')->get();

    foreach ($domains as $domain) {
        $total = \DB::connection('pbx_cdr')->table('call_records_outbound')->where('domain_name', $domain->domain_name)->where('start_time', 'like', '2021-03-22%')->sum('cost');

        $airtime_history = [
            'created_at' => date('Y-m-01 H:i:s'),
            'domain_uuid' => $domain->domain_uuid,
            'total' => $total * -1,
            'balance' => $domain->balance - $total,
            'type' => 'airtime_correction',
        ];

        \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
        \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain->domain_name)->decrement('balance', $total);
    }
}
