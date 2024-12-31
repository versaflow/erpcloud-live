<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleManager extends Model
{
    protected $table = 'erp_cruds';

    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    public static function getTableList($module_id)
    {
        $module = \DB::table('erp_cruds')->where('id', $module_id)->get()->first();
        set_db_connection($module->connection);
        $list = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        set_db_connection();

        return $list;
    }

    public static function getTableFields($module_id, $table_name)
    {
        $module = \DB::table('erp_cruds')->where('id', $module_id)->get()->first();
        set_db_connection($module->connection);
        $schema = \DB::getDoctrineSchemaManager();
        $columns = $schema->listTableColumns($table_name);

        $fields = [];
        foreach ($columns as $column) {
            $fields[] = $column->getName();
        }
        set_db_connection();

        return $fields;
    }

    public static function getTableFieldsFromSQL($module_id, $sql)
    {
        $module = \DB::table('erp_cruds')->where('id', $module_id)->get()->first();
        set_db_connection($module->connection);
        try {
            $results = \DB::select($sql.' limit 1');

            $fields = [];
            foreach ($results[0] as $key => $val) {
                $fields[] = $key;
            }
            set_db_connection();

            return $fields;
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();
            exception_log($error);
            exception_log($ex->getTraceAsString());
            set_db_connection();

            return false;
        }
    }

    public static function getResultFunctions()
    {
        return \DB::table('erp_form_events')->where('type', 'sql_filter')->pluck('function_name')->toArray();
    }

    public static function getFields($module_id)
    {
        return \DB::table('erp_module_fields')->where('module_id', $module_id)->pluck('field')->toArray();
    }

    public static function getConfig($module_id, $field_id = null)
    {
        if ($field_id) {
            return \DB::table('erp_module_fields')->where('id', $field_id)->get()->first();
        }

        return \DB::table('erp_module_fields')->where('module_id', $module_id)->get();
    }

    public static function saveField($field_data, $db_table)
    {
        $field_data['field_type'] = $field_data['type'];
        unset($field_data['type']);

        if (empty($field_data->id)) {
            $field_data = (array) $field_data;

            $field_data['alias'] = $db_table;

            if ($field_data['field'] == 'id') {
                $field_data['field_type'] = 'hidden';
                $field_data['visible'] = 'Add and Edit';
            }

            //$field_data['access'] = 1;
            \DB::table('erp_module_fields')->insert($field_data);
        } else {
            $field_id = $field_data->id;
            unset($field_data->id);
            $field_data = (array) $field_data;
            if ($field_data['field'] == 'id') {
                $field_data['field_type'] = 'hidden';
                $field_data['visible'] = 'Add and Edit';
            }

            $field_data['alias'] = $db_table;

            \DB::table('erp_module_fields')->where('id', $field_id)->update($field_data);
        }
    }

    public static function deleteField($module_id, $field)
    {
        \DB::table('erp_module_fields')->where('module_id', $module_id)->where('field', $field)->delete();
    }
}
