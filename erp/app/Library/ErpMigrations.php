<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class ErpMigrations
{
    public function processMigration($id)
    {
        $migration = DB::table('erp_instance_migrations')->where('id', $id)->get()->first();
        $data = [
            'table_name' => str_replace([' ', '-'], '_', strtolower($migration->table_name)),
            'field_name' => str_replace([' ', '-'], '_', strtolower($migration->field_name)),
            'new_name' => str_replace([' ', '-'], '_', strtolower($migration->new_name)),
        ];

        DB::table('erp_instance_migrations')->where('id', $id)->update($data);
        $migration = DB::table('erp_instance_migrations')->where('id', $id)->get()->first();
        if (empty($migration->action)) {
            return json_alert('Action required', 'warning');
        }

        if (empty($migration->connection)) {
            return json_alert('Connection required', 'warning');
        }
        if ($migration->connection == 'default') {
            $migration->connection = \DB::connection('system')->table('erp_instances')->where('id', session('instance')->id)->pluck('db_connection')->first();
        }
        $conns = $this->getInstanceConnections($migration);
        $this->remote_connection = false;
        if (! in_array($migration->connection, $conns)) {
            $this->remote_connection = true;
        }

        if (empty($migration->table_name)) {
            return json_alert('Table required', 'warning');
        }

        if ($migration->action != 'table_add' && ! Schema::connection($migration->connection)->hasTable($migration->table_name)) {
            return 'Invalid connection, table does not exists';
        }

        if (($migration->action == 'column_rename' || $migration->action == 'column_drop') && ! Schema::connection($migration->connection)->hasColumn($migration->table_name, $migration->field_name)) {
            return 'Invalid connection, field does not exists';
        }

        if ($migration->action == 'column_add') {
            if (empty($migration->field_name)) {
                return json_alert('Field required', 'warning');
            }

            $result = $this->addColumn($migration);
        }

        if ($migration->action == 'column_type') {
            if (empty($migration->field_name)) {
                return json_alert('Field required', 'warning');
            }

            if (empty($migration->field_type)) {
                return json_alert('Field type required', 'warning');
            }
            $result = $this->changeColumn($migration);
        }

        if ($migration->action == 'column_rename') {
            if (empty($migration->field_name)) {
                return json_alert('Field required', 'warning');
            }

            if (empty($migration->new_name)) {
                return json_alert('New field name required', 'warning');
            }
            $result = $this->renameColumn($migration);
        }

        if ($migration->action == 'column_drop') {
            if (empty($migration->field_name)) {
                return json_alert('Field required', 'warning');
            }

            $result = $this->dropColumn($migration);
        }

        if ($migration->action == 'table_add') {
            $result = $this->addTable($migration);
        }

        if ($migration->action == 'table_rename') {
            if (empty($migration->new_name)) {
                return json_alert('New table name required', 'warning');
            }
            $result = $this->renameTable($migration);
        }

        if ($migration->action == 'table_drop') {
            $result = $this->dropTable($migration);
        }

        return $result;
    }

    private function getInstanceConnections($migration)
    {
        if (is_main_instance() && ! $this->isCustomSchema($migration)) {
            $instance_connections = DB::table('erp_instances')->where('id', 1)->orWhere('sync_erp', 1)->pluck('db_connection')->toArray();
        } else {
            $instance_connections = ['default'];
        }

        return $instance_connections;
    }

    private function isCustomSchema($migration)
    {
        $is_custom = (! empty($migration->custom)) ? 1 : 0;

        $main_instance = is_main_instance();
        if (! $main_instance) {
            $is_custom = true;
        }
        if (! $is_custom) {
            if (str_contains($migration->action, 'table')) {
                $is_custom = DB::table('erp_custom_schema')
                    ->where('table_name', $migration->table_name)
                    ->where('type', 'table')
                    ->count();
            } elseif (str_contains($migration->action, 'column')) {
                $is_custom = DB::table('erp_custom_schema')
                    ->where('field_name', $migration->field_name)
                    ->where('table_name', $migration->table_name)
                    ->where('type', 'column')
                    ->count();
            }
        }
        if ($is_custom) {
            $is_custom = true;
        }

        return $is_custom;
    }

    private function setCustomSchema($migration)
    {
        $custom = $this->isCustomSchema($migration);

        if ($custom) {
            if ($migration->action == 'column_add') {
                $data = ['field_name' => $migration->field_name, 'table_name' => $migration->table_name, 'type' => 'column'];
                DB::table('erp_custom_schema')
                    ->insert($data);
            }

            if ($migration->action == 'column_rename') {
                $data = ['field_name' => $migration->new_name];
                DB::table('erp_custom_schema')
                    ->where('table_name', $migration->table_name)
                    ->where('field_name', $migration->field_name)
                    ->where('type', 'column')
                    ->update($data);
            }

            if ($migration->action == 'column_drop') {
                DB::table('erp_custom_schema')
                    ->where('table_name', $migration->table_name)
                    ->where('field_name', $migration->field_name)
                    ->where('type', 'column')
                    ->delete();
            }

            if ($migration->action == 'table_add') {
                $data = ['table_name' => $migration->table_name, 'type' => 'table'];
                DB::table('erp_custom_schema')
                    ->insert($data);
            }

            if ($migration->action == 'table_rename') {
                $data = ['table_name' => $migration->new_name];
                DB::table('erp_custom_schema')
                    ->where('table_name', $migration->table_name)
                    ->where('type', 'table')
                    ->update($data);
            }

            if ($migration->action == 'table_drop') {
                DB::table('erp_custom_schema')
                    ->where('table_name', $migration->table_name)
                    ->where('type', 'table')
                    ->delete();
            }
        }

        return true;
    }

    private function addColumn($migration)
    {
        $success = '';
        $error = '';

        $instance_connections = $this->getInstanceConnections($migration);
        if (! empty($migration->success_result)) {
            $success = $migration->success_result;
        }
        if (! empty($migration->error_result)) {
            $error = $migration->error_result;
        }
        foreach ($instance_connections as $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            if ($this->remote_connection) {
                $schema_conn = $migration->connection;
            } else {
                $schema_conn = $conn;
            }

            try {
                $currency_decimals = DB::table('erp_instances')->where('db_connection', $conn)->pluck('currency_decimals')->first();
                if (empty($currency_decimals)) {
                    $currency_decimals = 2;
                }
                $migration->currency_decimals = 2;
                if (Schema::connection($schema_conn)->hasTable($migration->table_name) && ! Schema::connection($schema_conn)->hasColumn($migration->table_name, $migration->field_name)) {
                    Schema::connection($schema_conn)->table($migration->table_name, function (Blueprint $table) use ($migration, $conn) {
                        if ($migration->field_type == 'Text' || empty($migration->field_type)) {
                            if (isset($migration->default_value)) {
                                $table->text($migration->field_name, $length)->nullable()->default($migration->default_value);
                            } else {
                                $table->text($migration->field_name, $length)->nullable();
                            }
                        }

                        if ($migration->field_type == 'longText') {
                            if (isset($migration->default_value)) {
                                $table->longText($migration->field_name)->nullable()->default($migration->default_value);
                            } else {
                                $table->longText($migration->field_name)->nullable();
                            }
                        }

                        if ($migration->field_type == 'Varchar') {
                            $length = intval($migration->field_length);
                            $length = ($length) ? $length : 256;
                            if (isset($migration->default_value)) {
                                $table->string($migration->field_name, $length)->nullable()->default($migration->default_value);
                            } else {
                                $table->string($migration->field_name, $length)->nullable();
                            }
                        }

                        if ($migration->field_type == 'Decimal') {
                            if (isset($migration->default_value)) {
                                $table->double($migration->field_name, 10, $migration->currency_decimals)->default($migration->default_value);
                            } else {
                                $table->double($migration->field_name, 10, $migration->currency_decimals);
                            }
                        }

                        if ($migration->field_type == 'Integer') {
                            $length = intval($migration->field_length);
                            $length = ($length) ? $length : 11;
                            if (! isset($migration->default_value)) {
                                $migration->default_value = 0;
                            }
                            if (isset($migration->default_value)) {
                                $table->integer($migration->field_name)->length($length)->default($migration->default_value);
                            } else {
                                $table->integer($migration->field_name)->length($length);
                            }

                            if ($migration->field_name == 'module_id') {
                                //  $table->foreign('module_id')
                                //      ->references('id')->on('erp_cruds')
                                //     ->onDelete('cascade');
                            }
                        }

                        if ($migration->field_type == 'Date') {
                            $table->date($migration->field_name)->nullable();
                        }

                        if ($migration->field_type == 'DateTime') {
                            $table->dateTime($migration->field_name)->nullable();
                        }

                        if ($migration->field_type == 'Tiny Integer' && ! empty($migration->field_length) && $migration->field_length > 1) {
                            if (! isset($migration->default_value)) {
                                $migration->default_value = 0;
                            }
                            if (isset($migration->default_value)) {
                                $table->tinyInteger($migration->field_name)->length($migration->field_length)->default($migration->default_value);
                            } else {
                                $table->tinyInteger($migration->field_name)->length($migration->field_length)->default('0');
                            }
                        } elseif ($migration->field_type == 'Tiny Integer') {
                            if (! isset($migration->default_value)) {
                                $migration->default_value = 0;
                            }
                            if (str_contains($conn, 'pbx')) {
                                if (isset($migration->default_value)) {
                                    $table->smallInteger($migration->field_name)->default($migration->default_value);
                                } else {
                                    $table->smallInteger($migration->field_name)->default('0');
                                }
                            } else {
                                if (isset($migration->default_value)) {
                                    $table->boolean($migration->field_name)->default($migration->default_value);
                                } else {
                                    $table->boolean($migration->field_name)->default('0');
                                }
                            }
                        }
                    });
                }
                $success .= $conn.': processsed ';
                $this->setCustomSchema($migration);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $error .= 'Connection: '.$conn.' '.$ex->getMessage();
                break;
            }
            if ($this->remote_connection) {
                break;
            }
        }

        $data = [
            'success_result' => $success,
            'error_result' => $error,
            'completed' => ($error) ? 0 : 1,
            'processed' => 1,
        ];

        DB::table('erp_instance_migrations')->where('id', $migration->id)->update($data);
        if ($error) {
            return $error;
        }

        return true;
    }

    private function changeColumn($migration)
    {
        if (empty($migration->field_type)) {
            return false;
        }

        $success = '';
        $error = '';

        $instance_connections = $this->getInstanceConnections($migration);
        if (! empty($migration->success_result)) {
            $success = $migration->success_result;
        }
        if (! empty($migration->error_result)) {
            $error = $migration->error_result;
        }
        foreach ($instance_connections as $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            if ($this->remote_connection) {
                $schema_conn = $migration->connection;
            } else {
                $schema_conn = $conn;
            }
            try {
                $currency_decimals = DB::table('erp_instances')->where('db_connection', $conn)->pluck('currency_decimals')->first();
                if (empty($currency_decimals)) {
                    $currency_decimals = 2;
                }
                $migration->currency_decimals = 2;
                if (Schema::connection($schema_conn)->hasTable($migration->table_name) && Schema::connection($schema_conn)->hasColumn($migration->table_name, $migration->field_name)) {
                    Schema::connection($schema_conn)->table($migration->table_name, function (Blueprint $table) use ($migration, $conn) {
                        if ($migration->field_type == 'Text' || empty($migration->field_type)) {
                            if (isset($migration->default_value)) {
                                $table->text($migration->field_name, $length)->default($migration->default_value)->change();
                            } else {
                                $table->text($migration->field_name, $length)->change();
                            }
                        }

                        if ($migration->field_type == 'longText') {
                            if (isset($migration->default_value)) {
                                $table->longText($migration->field_name)->default($migration->default_value)->change();
                            } else {
                                $table->longText($migration->field_name)->change();
                            }
                        }

                        if ($migration->field_type == 'Varchar') {
                            $length = intval($migration->field_length);
                            $length = ($length) ? $length : 256;

                            if (isset($migration->default_value)) {
                                $table->string($migration->field_name, $length)->default($migration->default_value)->change();
                            } else {
                                $table->string($migration->field_name, $length)->change();
                            }
                        }

                        if ($migration->field_type == 'Decimal') {
                            if (isset($migration->default_value)) {
                                $table->double($migration->field_name, 10, $migration->currency_decimals)->default($migration->default_value)->change();
                            } else {
                                $table->double($migration->field_name, 10, $migration->currency_decimals)->change();
                            }
                        }

                        if ($migration->field_type == 'Integer') {
                            $length = intval($migration->field_length);
                            $length = ($length) ? $length : 11;

                            if (! isset($migration->default_value)) {
                                $migration->default_value = 0;
                            }
                            if (isset($migration->default_value)) {
                                $table->integer($migration->field_name)->length($length)->default($migration->default_value)->change();
                            } else {
                                $table->integer($migration->field_name)->length($length)->change();
                            }
                        }

                        if ($migration->field_type == 'Date') {
                            $table->date($migration->field_name)->default(null)->nullable()->change();
                        }

                        if ($migration->field_type == 'DateTime') {
                            $table->dateTime($migration->field_name)->default(null)->nullable()->change();
                        }

                        if ($migration->field_type == 'Tiny Integer' && ! empty($migration->field_length) && $migration->field_length > 1) {
                            if (! isset($migration->default_value)) {
                                $migration->default_value = 0;
                            }
                            if (isset($migration->default_value)) {
                                $table->tinyInteger($migration->field_name)->length($migration->field_length)->default($migration->default_value)->change();
                            } else {
                                $table->tinyInteger($migration->field_name)->length($migration->field_length)->default('0')->change();
                            }
                        } elseif ($migration->field_type == 'Tiny Integer') {
                            if (! isset($migration->default_value)) {
                                $migration->default_value = 0;
                            }
                            if (str_contains($conn, 'pbx')) {
                                if (isset($migration->default_value)) {
                                    $table->smallInteger($migration->field_name)->default($migration->default_value)->change();
                                } else {
                                    $table->smallInteger($migration->field_name)->default('0')->change();
                                }
                            } else {
                                if (isset($migration->default_value)) {
                                    $table->boolean($migration->field_name)->default($migration->default_value)->change();
                                } else {
                                    $table->boolean($migration->field_name)->default('0')->change();
                                }
                            }
                        }
                    });
                }

                $success .= $conn.': processsed ';
                $this->setCustomSchema($migration);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $error .= 'Connection: '.$conn.' '.$ex->getMessage();
                break;
            }

            if ($this->remote_connection) {
                break;
            }
        }

        $data = [
            'success_result' => $success,
            'error_result' => $error,
            'completed' => ($error) ? 0 : 1,
            'processed' => 1,
        ];

        DB::table('erp_instance_migrations')->where('id', $migration->id)->update($data);
        if ($error) {
            return $error;
        }

        return true;
    }

    private function dropColumn($migration)
    {
        $success = '';
        $error = '';

        $instance_connections = $this->getInstanceConnections($migration);
        if (! empty($migration->success_result)) {
            $success = $migration->success_result;
        }
        if (! empty($migration->error_result)) {
            $error = $migration->error_result;
        }
        foreach ($instance_connections as $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            if ($this->remote_connection) {
                $schema_conn = $migration->connection;
            } else {
                $schema_conn = $conn;
            }

            try {
                DB::connection($schema_conn)->statement('SET FOREIGN_KEY_CHECKS = 0');
                if (Schema::connection($schema_conn)->hasTable($migration->table_name)
                    && Schema::connection($schema_conn)->hasColumn($migration->table_name, $migration->field_name)) {
                    Schema::connection($schema_conn)->table($migration->table_name, function (Blueprint $table) use ($migration) {
                        $table->dropColumn($migration->field_name);
                    });
                }
                $success .= $conn.': processsed ';
                DB::connection($schema_conn)->statement('SET FOREIGN_KEY_CHECKS = 1');
                $this->setCustomSchema($migration);
            } catch (\Throwable $ex) {
                exception_log($ex);
                DB::connection($schema_conn)->statement('SET FOREIGN_KEY_CHECKS = 1');
                $error .= 'Connection: '.$conn.' '.$ex->getMessage();
                break;
            }

            if ($this->remote_connection) {
                break;
            }
        }

        $data = [
            'success_result' => $success,
            'error_result' => $error,
            'completed' => ($error) ? 0 : 1,
            'processed' => 1,
        ];

        DB::table('erp_instance_migrations')->where('id', $migration->id)->update($data);
        if ($error) {
            return $error;
        }

        return true;
    }

    private function renameColumn($migration)
    {
        $success = '';
        $error = '';

        $instance_connections = $this->getInstanceConnections($migration);
        if (! empty($migration->success_result)) {
            $success = $migration->success_result;
        }
        if (! empty($migration->error_result)) {
            $error = $migration->error_result;
        }

        foreach ($instance_connections as $i => $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            if ($this->remote_connection) {
                $schema_conn = $migration->connection;
            } else {
                $schema_conn = $conn;
            }

            if (Schema::connection($schema_conn)->hasTable($migration->table_name)) {
                if (Schema::connection($schema_conn)->hasColumn($migration->table_name, $migration->field_name)
                && ! Schema::connection($schema_conn)->hasColumn($migration->table_name, $migration->new_name)) {
                    Schema::connection($schema_conn)->table($migration->table_name, function (Blueprint $table) use ($migration) {
                        $table->renameColumn($migration->field_name, $migration->new_name);
                    });
                }
            }
            if ($this->remote_connection) {
                break;
            }
        }

        foreach ($instance_connections as $i => $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            $modules = \DB::connection($conn)->table('erp_cruds')->where('db_table', $migration->table_name)->pluck('id')->toArray();
            foreach ($modules as $module_id) {
                $label = ucwords(str_replace('_', ' ', $migration->new_name));
                \DB::connection($conn)->table('erp_module_fields')
                    ->where('module_id', $module_id)->where('field', $migration->field_name)
                    ->update(['field' => $migration->new_name, 'label' => $label]);
                $layouts = \DB::connection($conn)->table('erp_grid_views')->select('id', 'aggrid_state')->where('aggrid_state', 'like', '%'.$migration->field_name.'%')->where('module_id', $module_id)->get();
                foreach ($layouts as $l) {
                    //aggrid reserved properties
                    if (! in_array($migration->field_name, ['colId', 'width', 'hide', 'pinned', 'sort', 'sortIndex', 'aggFunc', 'rowGroup', 'rowGroupIndex', 'pivot', 'pivotIndex', 'flex'])) {
                        $data = [
                            'aggrid_state' => preg_replace('/\b'.$migration->field_name.'\b/', $migration->new_name, $l->aggrid_state),
                        ];
                        DB::connection($conn)->table('erp_grid_views')->where('id', $l->id)->update($data);
                    }
                }

            }

            $success .= $conn.': processsed ';
            $this->setCustomSchema($migration);
        }

        $data = [
            'success_result' => $success,
            'error_result' => $error,
            'completed' => ($error) ? 0 : 1,
            'processed' => 1,
        ];

        DB::table('erp_instance_migrations')->where('id', $migration->id)->update($data);
        if ($error) {
            return $error;
        }

        return true;
    }

    private function addTable($migration)
    {
        $success = '';
        $error = '';

        $instance_connections = $this->getInstanceConnections($migration);

        if (! empty($migration->success_result)) {
            $success = $migration->success_result;
        }
        if (! empty($migration->error_result)) {
            $error = $migration->error_result;
        }
        foreach ($instance_connections as $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            if ($this->remote_connection) {
                $schema_conn = $migration->connection;
            } else {
                $schema_conn = $conn;
            }
            try {
                Schema::connection($schema_conn)->create($migration->table_name, function (Blueprint $table) {
                    $table->increments('id');
                });
                $success .= $conn.': processsed ';
                $this->setCustomSchema($migration);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $error .= 'Connection: '.$conn.' '.$ex->getMessage();
                break;
            }
            if ($this->remote_connection) {
                break;
            }
        }

        $data = [
            'success_result' => $success,
            'error_result' => $error,
            'completed' => ($error) ? 0 : 1,
            'processed' => 1,
        ];

        DB::table('erp_instance_migrations')->where('id', $migration->id)->update($data);
        if ($error) {
            return $error;
        }

        return true;
    }

    private function dropTable($migration)
    {
        $success = '';
        $error = '';

        $instance_connections = $this->getInstanceConnections($migration);
        if (! empty($migration->success_result)) {
            $success = $migration->success_result;
        }
        if (! empty($migration->error_result)) {
            $error = $migration->error_result;
        }
        foreach ($instance_connections as $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            if ($this->remote_connection) {
                $schema_conn = $migration->connection;
            } else {
                $schema_conn = $conn;
            }
            try {
                Schema::connection($schema_conn)->dropIfExists($migration->table_name);
                $success .= $conn.': processsed ';
                $this->setCustomSchema($migration);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $error .= 'Connection: '.$conn.' '.$ex->getMessage();
                break;
            }

            if ($this->remote_connection) {
                break;
            }
        }

        $data = [
            'success_result' => $success,
            'error_result' => $error,
            'completed' => ($error) ? 0 : 1,
            'processed' => 1,
        ];

        DB::table('erp_instance_migrations')->where('id', $migration->id)->update($data);
        if ($error) {
            return $error;
        }

        return true;
    }

    public function renameTable($migration)
    {
        $table_name = $migration->table_name;
        $new_table_name = $migration->new_name;
        $success = '';
        $error = '';

        $instance_connections = $this->getInstanceConnections($migration);
        if (! empty($migration->success_result)) {
            $success = $migration->success_result;
        }
        if (! empty($migration->error_result)) {
            $error = $migration->error_result;
        }

        foreach ($instance_connections as $i => $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            if ($this->remote_connection) {
                $schema_conn = $migration->connection;
            } else {
                $schema_conn = $conn;
            }

            if (Schema::connection($schema_conn)->hasTable($table_name) && ! Schema::connection($schema_conn)->hasTable($new_table_name)) {
                Schema::connection($schema_conn)->rename($table_name, $new_table_name);
            }

            if ($this->remote_connection) {
                break;
            }
        }

        foreach ($instance_connections as $conn) {
            if (str_contains($success, $conn)) {
                continue;
            }

            try {
                $modules = DB::connection($conn)->table('erp_cruds')->where('db_table', $table_name)->orwhere('db_sql', 'LIKE', '%'.$table_name.'%')->get();
                $reports = DB::connection($conn)->table('erp_reports')->where('sql_query', 'LIKE', '%'.$table_name.'%')->get();

                foreach ($modules as $m) {
                    $data = [
                        'db_table' => preg_replace('/\b'.$table_name.'\b/', $new_table_name, $m->db_table),
                        'db_where' => preg_replace('/\b'.$table_name.'\b/', $new_table_name, $m->db_where),
                        'db_sql' => preg_replace('/\b'.$table_name.'\b/', $new_table_name, $m->db_sql),
                    ];
                    DB::connection($conn)->table('erp_cruds')->where('id', $m->id)->update($data);
                }

                DB::connection($conn)->table('erp_module_fields')->where('opt_db_table', $table_name)->update(['opt_db_table' => $new_table_name]);

                foreach ($reports as $r) {
                    $data = [
                        'sql_query' => preg_replace('/\b'.$table_name.'\b/', $new_table_name, $r->sql_query),
                        'sql_where' => preg_replace('/\b'.$table_name.'\b/', $new_table_name, $r->sql_where),
                    ];
                    $query_data = unserialize($r->query_data);
                    $search = $table_name;
                    $replace = $new_table_name;
                    array_walk_recursive(
                        $query_data,
                        function (&$value, $count, $params) {
                            $value = preg_replace('/\b'.$params['search'].'\b/', $params['replace'], $value);
                        },
                        ['search' => $search, 'replace' => $replace]
                    );
                    $data['query_data'] = serialize($query_data);
                    DB::connection($conn)->table('erp_reports')->where('id', $r->id)->update($data);
                }

                DB::connection($conn)->table('erp_module_fields')->where('alias', $table_name)->update(['alias' => $new_table_name]);

                $success .= $conn.': processsed ';
                $this->setCustomSchema($migration);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $error .= 'Connection: '.$conn.' '.$ex->getMessage();

                break;
            }
        }

        $data = [
            'success_result' => $success,
            'error_result' => $error,
            'completed' => ($error) ? 0 : 1,
            'processed' => 1,
        ];
        DB::table('erp_instance_migrations')->where('id', $migration->id)->update($data);
        if ($error) {
            return $error;
        }
        replace_code_references($migration->table_name, $migration->new_name);

        return true;
    }

    public function clearCache()
    {
        $process = new Process(['cd '.base_path().' && composer dump-autoload']);
        $process->run();

        $process = new Process(['cd '.base_path().' && php artisan view:clear']);
        $process->run();
    }

    public function process($cmd)
    {
        $process = new Process(['cd '.base_path().' && '.$cmd]);
        $process->run();
    }
}
