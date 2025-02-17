<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportModel extends Model
{
    public function __construct($report_id)
    {
        $this->report = \DB::connection('default')->table('erp_reports')->where('id', $report_id)->get()->first();
        $this->colDefs = $this->getColDefs();
    }

    public function getColDefs()
    {
        $report = $this->report;
        $query_data = unserialize($report->query_data);

        $colDefs = [];


        $table_aliases = [];
        foreach ($query_data['db_tables'] as $table) {
            $alias = '';
            $table_name_arr = explode('_', $table);
            foreach ($table_name_arr as $table_name_slice) {
                $alias .= $table_name_slice[0];
            }

            if (in_array($alias, $table_aliases)) {
                $i = 1;
                while (in_array($alias, $table_aliases)) {
                    $alias .= $table_name_slice[$i];
                    $i++;
                }
            }
            if (str_contains($table, 'call_records')) {
                $alias = 'cdr';
            }

            $table_aliases[$table] = $alias;
        }

        $decimal_columns = [];
        foreach ($query_data['db_tables'] as $table) {
            $float_columns = get_columns_from_schema($table, 'float', $report->connection);
            $double_columns = get_columns_from_schema($table, 'double', $report->connection);
            $decimal_columns[$table] = array_merge($float_columns, $double_columns);
        }

        $cols_added = [];
        $sql = $report->sql_query;
        foreach ($query_data['db_tables'] as $table) {
            foreach ($query_data['db_columns'] as $i => $col) {
                $col_arr = explode('.', $col);
                if ($table == $col_arr[0]) {
                    if (!in_array($col_arr[1], $cols_added)) {
                        $cols_added[] = $col_arr[1];
                        $label = $col_arr[1];
                    } else {
                        $label = $table_aliases[$col_arr[0]] . ' ' . $col_arr[1];
                        $label = str_replace('records_lastmonth ', '', $label);
                        $label = str_replace('records ', '', $label);
                    }

                    $sql_label = $table_aliases[$col_arr[0]] . ' ' . $col_arr[1];
                    //$sql = str_replace($col." as '".$sql_label."'",$col,$sql);

                    $hide = false;
                    if ($i > 10) {
                        $hide = true;
                    }
                    $colDef =  [
                        'db_col' => $col,
                        'field' => $sql_label,
                        'headerName' => $label,
                        'hide' => $hide,
                    ];
                    if (in_array($col_arr[1], $decimal_columns[$col_arr[0]])) {
                        $colDef['type'] = 'currencyField';
                    }

                    $colDefs[] = $colDef;
                }
            }
        }

        return $colDefs;
    }

    public function getData($request)
    {
        $report = $this->report;
        $sql = $this->getSQL($request);
        $sql = str_replace("'",'"',$sql);
        $sql = str_replace("`","",$sql);
        if($report->connection == 'pbx' || $report->connection == 'pbx_cdr'){
        $sql = $this->replaceMySQLFunctions($sql);
        }
        
        $rows = \DB::connection($report->connection)->select($sql);

        //$sql = $this->getCount($request);
        // aa($sql);
        $count = \DB::connection($report->connection)->select('select count(*) as lastRow from ('.$sql.') as temp');
        //aa($count);
        //aa($count[0]->lastRow);
        return ['rows' => $rows,'lastRow' => $count[0]->lastRow];
    }
    
    public function replaceMySQLFunctions($query)
    {
           
        // Define an array of MySQL functions and their corresponding PostgreSQL functions
        $mysqlFunctions = array(
            'date_format' => 'to_char',
            'curdate()' => 'current_date',
            'concat' => 'concat',
            'ifnull' => 'coalesce',
            'group_concat' => 'string_agg',
            'now' => 'current_timestamp',
            'unix_timestamp' => 'extract(epoch from current_timestamp)',
            'substring_index' => 'substring',
            'find_in_set' => 'position',
            'count' => 'count',
            'sum' => 'sum',
            'avg' => 'avg',
        );
        
        // Define an array of MySQL date format strings and their corresponding PostgreSQL date format strings
        $mysqlDateFormats = array(
            '%Y-%m-%dT%H:%i:%s' => 'YYYY-MM-DD HH24:MI:SS',
            '%Y-%m-%d %H:%i:%s' => 'YYYY-MM-DD HH24:MI:SS',
            '%Y-%m-%dT%H:%i' => 'YYYY-MM-DD HH24:MI', 
            '%Y-%m-%d %H:%i' => 'YYYY-MM-DD HH24:MI',
            '%Y-%m-%d' => 'YYYY-MM-DD',
            // Add more date format string mappings as needed
        );
        
        // Replace MySQL functions with PostgreSQL functions in the query
        foreach ($mysqlFunctions as $mysqlFunc => $postgresFunc) {
            $query = str_ireplace($mysqlFunc, $postgresFunc, $query);
        }
        
        // Replace MySQL date format strings with PostgreSQL date format strings in the query
        foreach ($mysqlDateFormats as $mysqlFormat => $postgresFormat) {
            $query = str_ireplace($mysqlFormat, $postgresFormat, $query);
        }
        foreach ($mysqlDateFormats as $mysqlFormat => $postgresFormat) {
            $query = str_ireplace('"'.$postgresFormat.'"', "'".$postgresFormat."'", $query);
        }
        
        return $query;
    }


    public function getSQL($request)
    {
        $report = $this->report;
        $sql = $report->sql_query;
        // query builder
        // aggrid request

        $rowGroupCols = $request->rowGroupCols;
        $valueCols = $request->input('valueCols');
        $groupKeys = $request->input('groupKeys');
        $sortModel = $request->sortModel;

        $grouping = $this->isDoingGrouping($rowGroupCols, $groupKeys);
        $aggregate_sorts = [];
        if ($grouping) {
            $sql = str_replace(' from ', ' FROM ', $sql);
            $sql = str_replace(' where ', ' WHERE ', $sql);
            $sql = trim(preg_replace('/\s+/', ' ', $sql));
            $sql = str_replace_last(' FROM ', '||', $sql);

            $sql_arr = explode('||', $sql);

            $where_arr = explode(' WHERE ', $sql_arr[1]);

            $colsToSelect = [];

            $rowGroupCol = $rowGroupCols[sizeof($groupKeys)];
            array_push($colsToSelect, $rowGroupCol['field']);

            foreach ($colsToSelect as $i => $groupcol) {
                foreach ($this->colDefs as $colDef) {
                    if ($colDef['field'] == $groupcol) {
                        $colsToSelect[$i] = $colDef['db_col']." as '".$colDef['field']."'";
                    }
                }
            }
            $cross_joins = [];
            $percentage_count = 1;
            foreach ($valueCols as $key => $value) {
                $val_field = $value['field'];
                foreach ($this->colDefs as $colDef) {
                    if ($colDef['field'] == $value['field']) {
                        $val_field = $colDef['db_col'];
                    }
                }
                if ($value['aggFunc'] == 'value') {
                    array_push($colsToSelect, $val_field." as '" . $value['field']."'");
                    continue;
                    //$aggregate_sorts[$val_field] =    "(" . $val_field . ")";
                    //array_push($colsToSelect,  "(" . $val_field . ") as '" . $value['field']."'");
                }
                if ($value['aggFunc'] == 'percentage') {
                    $val_field_label = str_replace('.', '', $val_field);
                    $val_field_arr = explode('.', $val_field);
                    /*
                    concat(round(( sum(call_records_outbound.duration_mins)/total.total * 100 ),2),'%') as 'cdr duration_mins_percentage',
                    CROSS JOIN ( select SUM(call_records_outbound.duration_mins) as total from call_records_outbound WHERE  call_records_outbound.hangup_date >= ( CURDATE() - INTERVAL 1 WEEK ) ) total
                    */
                    if (!empty($where_arr[1])) {
                        $cross_joins[] = ' CROSS JOIN (select sum('.$val_field.') as percentage'.$percentage_count.'total from '.$val_field_arr[0].' where '.$where_arr[1].' ) percentage'.$percentage_count.'table ';
                    } else {
                        $cross_joins[] = ' CROSS JOIN (select sum('.$val_field.') as percentage'.$percentage_count.'total from '.$val_field_arr[0].' ) percentage'.$percentage_count.'table ';
                    }
                    $aggregate_sorts[$val_field] =  $value['aggFunc'] . "(" . $val_field . ")";
                    $percentage_select =  ' concat(round(( sum('.$val_field.')/percentage'.$percentage_count.'table.percentage'.$percentage_count.'total * 100 ),2),"%") AS "'.$value['field'].'" ';
                    array_push($colsToSelect, $percentage_select);
                    $percentage_count++;
                } else {
                    $aggregate_sorts[$val_field] =  $value['aggFunc'] . "(" . $val_field . ")";
                    array_push($colsToSelect, $value['aggFunc'] . "(" . $val_field . ") as '" . $value['field']."'");
                }
            }

            $sql =  "select " . join(", ", $colsToSelect). ' from ';
            $where_arr = explode(' WHERE ', $sql_arr[1]);

            $sql .=$where_arr[0];
            foreach ($cross_joins as $cross_join) {
                $sql.= $cross_join;
            }
            $sql .=' WHERE '.$where_arr[1];
        }
        $whereSql = $this->createWhereSql($request,$sql);
        foreach ($this->colDefs as $col) {
            $whereSql = str_replace($col['field'], $col['db_col'], $whereSql);
        }
        if (str_contains($sql, ' WHERE ')) {
            $sql_arr = explode(' WHERE ', $sql);
            if($whereSql > ''){
            $sql = $sql_arr[0].' WHERE 1=1 and '.$whereSql.' and '.$sql_arr[1];
            }else{
            $sql = $sql_arr[0].' WHERE '.$sql_arr[1];
            }
        } else {
            $sql .= ' WHERE 1=1 and '.$whereSql;
        }
        if ($grouping) {
            $colsToGroupBy = [];

            $rowGroupCol = $rowGroupCols[count($groupKeys)];
            $colsToGroupBy[] = $rowGroupCol['field'];

            $groupBy = ' group by ' . join(', ', $colsToGroupBy);
        } else {
            $groupBy = '';
        }

        foreach ($this->colDefs as $col) {
            $groupBy = str_replace($col['field'], $col['db_col'], $groupBy);
        }
        $sql .= $groupBy;


        $sortParts = [];
        if ($sortModel) {
            foreach ($sortModel as $key=>$item) {
                $sortParts[] = $item['colId'] . ' ' . $item['sort'];
            }
        }

        if (count($sortParts) > 0) {
            $orderBy = ' order by ' . join(', ', $sortParts);
        } else {
            $orderBy = '';
        }

        foreach ($this->colDefs as $col) {
            $orderBy = str_replace($col['field'], $col['db_col'], $orderBy);
        }
        if (count($aggregate_sorts) > 0) {
            foreach ($aggregate_sorts as $key => $val) {
                $orderBy = str_replace($key, $val, $orderBy);
            }
        }

        $sql .= $orderBy;

        if (!isset($request->startRow) && !isset($request->endRow)) {
            $limit = '';
        } else {
            $startRow = $request->startRow;
            $endRow = $request->endRow;
            $pageSize = $endRow - $startRow;
            $limit = ' limit ' . ($pageSize + 1) . ' offset ' . $startRow;
        }

        if (!$grouping) {
            $sql .= $limit;
        }
        return $sql;
    }

    public function getCount($request)
    {
        $report = $this->report;
        $sql = $report->sql_query;
        $rowGroupCols = $request->rowGroupCols;
        $valueCols = $request->input('valueCols');
        $groupKeys = $request->input('groupKeys');
        $sortModel = $request->sortModel;

        $grouping = $this->isDoingGrouping($rowGroupCols, $groupKeys);


      
        $sql = trim(preg_replace('/\s+/', ' ', $sql));
        $sql = str_replace_last(' from ', '||', $sql);

        $sql_arr = explode('||', $sql);


        $sql =  'select count(*) as lastRow from '.$sql_arr[1];
        $sql = str_replace(' from ', ' FROM ', $sql);
        $sql = str_replace(' where ', ' WHERE ', $sql);

        $whereSql = $this->createWhereSql($request,$sql);
        foreach ($this->colDefs as $col) {
            $whereSql = str_replace($col['field'], $col['db_col'], $whereSql);
        }
        $sql .= $whereSql;

        if ($grouping) {
            $colsToGroupBy = [];

            $rowGroupCol = $rowGroupCols[count($groupKeys)];
            $colsToGroupBy[] = $rowGroupCol['field'];

            $groupBy = ' group by ' . join(', ', $colsToGroupBy);
        } else {
            $groupBy = '';
        }

        foreach ($this->colDefs as $col) {
            $groupBy = str_replace($col['field'], $col['db_col'], $groupBy);
        }
        $sql .= $groupBy;


        $sortParts = [];
        if ($sortModel) {
            foreach ($sortModel as $key=>$item) {
                $sortParts[] = $item['colId'] . ' ' . $item['sort'];
            }
        }

        if (count($sortParts) > 0) {
            $orderBy = ' order by ' . join(', ', $sortParts);
        } else {
            $orderBy = '';
        }

        foreach ($this->colDefs as $col) {
            $orderBy = str_replace($col['field'], $col['db_col'], $orderBy);
        }

        $sql .= $orderBy;

        return $sql;
    }


    private function isDoingGrouping($rowGroupCols, $groupKeys)
    {
        // we are not doing grouping if at the lowest level. we are at the lowest level
        // if we are grouping by more columns than we have keys for (that means the user
        // has not expanded a lowest level group, OR we are not grouping at all).
        return count($rowGroupCols) > count($groupKeys);
    }



    public function createWhereSql($request,$sql)
    {
        $rowGroupCols = $request->rowGroupCols;
        $groupKeys = $request->groupKeys;
        $filterModel = $request->filterModel;
        $whereParts = [];

        foreach ($groupKeys as $key => $value) {
            $colName = $rowGroupCols[$key]['field'];
            $whereParts[] = $colName . ' = "' . $value . '"';
        }

        foreach ($filterModel as $key => $value) {
            $item = $filterModel[$key];
            $whereParts[] = $this->createFilterSql($key, $value);
        }

        $whereSql = " ";
        if (count($whereParts) > 0) {
            $whereSql = " " . join(' and ', $whereParts);
        } 



        if (!empty($request->search)) {
            $search_query = ' and  (';
            foreach ($this->colDefs as $col) {
                $search_fields[] = $col['db_col']. ' LIKE "%'.$request->search.'%" ';
            }

            $search_query .= implode(" || ", $search_fields);
            $search_query .= ') ';
            if (count($search_fields) > 0) {
                $whereSql .= $search_query;
            }
        }
      

        return $whereSql;
    }

    private function createFilterSql($key, $item)
    {
        switch ($item['filterType']) {
            case 'text':
                if (isset($item['type']) and $item['type'] == 'domainsFilter') {
                    return $this->createDomainsFilterSql($key, $item['filter']);
                } else {
                    if ($item['filter'] === 'isnull') {
                        return $this->createNullFilterSql($key);
                    } elseif ($item['filter'] === 'isnotnull') {
                        return $this->createNotNullFilterSql($key);
                    } else {
                        return $this->createTextFilterSql($key, $item);
                    }
                }
                // no break
            case 'number':
                return $this->createNumberFilterSql($key, $item);
            case 'date':
                return $this->createDateFilterSql($key, $item);
            case 'set':
                return $this->createSetFilter($key, $item);
            default:
                logger('unkonwn filter type: ' . $item['filterType']);
        }
    }

    public function createDomainsFilterSql($key, $item)
    {
        $domains = array_map('trim', explode(',', $item));
        return $key .' in ('."'" . implode("', '", $domains) . "'".')';
    }

    public function createNullFilterSql($key)
    {
        return $key . ' is NULL';
    }

    public function createNotNullFilterSql($key)
    {
        return $key . ' is NOT NULL';
    }

    private function createSetFilter($key, $item)
    {
        $list = implode("', '", array_map('addslashes', $item['values']));
        return $key .' in ('."'" .$list . "'".')';
    }

    private function createDateFilterSql($key, $item)
    {
        switch ($item['type']) {
            case 'equals':
                return $key . ' = "' . $item['dateFrom'] . '"';
            case 'notEqual':
                return $key . ' != "' . $item['dateFrom'] . '"';
            case 'inRange':
                $toDate= $item['dateTo'];
                $fromDate = $item['dateFrom'];
                return " ( $key >= Date('$fromDate') AND $key <= Date('$toDate') ) ";
                break;
            default:
                logger('unknown text filter type: ' . $item['dateFrom']);
                return 'true';
        }
    }

    private function createTextFilterSql($key, $item)
    {
        switch ($item['type']) {
            case 'equals':
                return $key . ' = "' . $item['filter'] . '"';
            case 'notEqual':
                return $key . ' != "' . $item['filter'] . '"';
            case 'contains':
                return $key . ' like "%' . $item['filter'] . '%"';
            case 'notContains':
                return $key . ' not like "%' . $item['filter'] . '%"';
            case 'startsWith':
                return $key . ' like "' . $item['filter'] . '%"';
            case 'endsWith':
                return $key . ' like "%' . $item['filter'] . '"';
            default:
                logger('unknown text filter type: ' . $item['type']);
                return 'true';
        }
    }

    private function createNumberFilterSql($key, $item)
    {
        switch ($item['type']) {
            case 'equals':
                return $key . ' = ' . $item['filter'];
            case 'notEqual':
                return $key . ' != ' . $item['filter'];
            case 'greaterThan':
                return $key . ' > ' . $item['filter'];
            case 'greaterThanOrEqual':
                return $key . ' >= ' . $item['filter'];
            case 'lessThan':
                return $key . ' < ' . $item['filter'];
            case 'lessThanOrEqual':
                return $key . ' <= ' . $item['filter'];
            case 'inRange':
                return '(' . $key . ' >= ' . $item['filter'] . ' and ' . $key . ' <= ' . $item['filterTo'] . ')';
            default:
                logger('unknown number filter type: ' . $item['type']);
                return 'true';
        }
    }
}
