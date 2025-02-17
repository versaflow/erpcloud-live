<?php

use Symfony\Component\Process\Process;

class ErpReports
{
    public function setErpConnection($connection)
    {
        $this->erp_connection = $connection;
    }

    private function reportOrderJoins($data, $processed_joins = [], $join_arr = [], $iterator = 0)
    {
        extract($data);
        if (!empty($join_type) && count($join_type) > 0) {
            $selected_tables = $db_tables;
            foreach ($join_type as $i => $join) {
                $table_name_arr = explode('.', $join_table_1[$i]);
                $first_table = $table_name_arr[0];
                $table_name_arr = explode('.', $join_table_2[$i]);
                $second_table = $table_name_arr[0];

                if ($first_table == $selected_tables[0] || $second_table == $selected_tables[0] ||
                (in_array($first_table, $processed_joins) || in_array($second_table, $processed_joins))) {
                    $join_arr[] = [$join,$join_table_1[$i],$join_table_2[$i]];

                    $processed_joins[] = $first_table;
                    $processed_joins[] = $second_table;
                }
            }

            $reorder = false;

            foreach ($selected_tables as $j) {
                if (!in_array($j, $processed_joins)) {
                    $reorder = true;
                }
            }
            if ($reorder) {
                $iterator++;
                if ($iterator == 10) {
                    return $join_arr;
                }

                $join_arrs = $this->reportOrderJoins($data, $processed_joins, $join_arr, $iterator);
                foreach ($join_arrs as $j) {
                    $join_arr[] = $j;
                }
            }
        }
        return $join_arr;
    }

    public function reportFilterSQL($id)
    {
        $config = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('query_data')->first();
        $sql_where = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('sql_where')->first();
        $data = unserialize($config);

        extract($data);

        if (empty($db_conn)) {
            return null;
        }
        if (empty($db_tables) || count($db_tables) == 0) {
            return null;
        }
        if (empty($db_columns) || count($db_columns) == 0) {
            return null;
        }
        if (!empty($db_tables) && count($db_tables) > 1 && (empty($join_type) || count($join_type) == 0)) {
            return null;
        }

        $selected_tables[] = $db_tables[0];
        $query = \DB::connection($db_conn)->table($db_tables[0]);
        $join_arr = $this->reportOrderJoins($data);
        if (!empty($join_arr) && count($join_arr) > 0) {
            foreach ($join_arr as $join) {
                $table_name_arr = explode('.', $join[1]);
                $table_name = $table_name_arr[0];

                if (!in_array($table_name, $selected_tables)) {
                    $selected_tables[] = $table_name;
                } else {
                    $table_name = null;
                }
                if (empty($table_name)) {
                    $table_name_arr = explode('.', $join[2]);
                    $table_name = $table_name_arr[0];

                    if (!in_array($table_name, $selected_tables)) {
                        $selected_tables[] = $table_name;
                    } else {
                        $table_name = null;
                    }
                }

                if (!empty($table_name)) {
                    if ($join[0] == 'INNER JOIN') {
                        $query->join($table_name, $join[1], '=', $join[2]);
                    }
                    if ($join[0] == 'LEFT JOIN') {
                        $query->leftJoin($table_name, $join[1], '=', $join[2]);
                    }
                    if ($join[0] == 'RIGHT JOIN') {
                        $query->rightJoin($table_name, $join[1], '=', $join[2]);
                    }
                }
            }
        }

        foreach ($db_columns as $col) {
            $col_arr = explode('.', $col);
            $col = $col.' as `'.$col_arr[0].'__'.$col_arr[1].'`';
            $query->selectRaw($col);
        }
        $query->limit(1);
        $sql = querybuilder_to_sql($query);
        return $sql;
    }

    public function getDateMappings($id)
    {
        $date_cols = [];
        if ($this->erp_connection == session('instance')->db_connection) {
            $this->erp_connection = 'default';
        }
        $query = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('sql_query')->first();
        $config = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('query_data')->first();
        $sql_where = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('sql_where')->first();

        $query_error = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('query_error')->first();

        $data = unserialize($config);

        $db_conn = $data['db_conn'];
        $db_tables = $data['db_tables'];
        $date_columns = [];
        if (!empty($db_tables) && is_array($db_tables)) {
            natsort($db_tables);
        }
        if ($data['db_conn'] == 'default') {
            $data['db_conn'] = $this->erp_connection;
        }
        foreach ($db_tables as $table) {
            $cols = get_columns_from_schema($table, 'date', $db_conn);
            foreach ($cols as $c) {
                $date_columns[] = $table.'.'.$c;
            }
            $cols = get_columns_from_schema($table, 'datetime', $db_conn);
            foreach ($cols as $c) {
                $date_columns[] = $table.'.'.$c;
            }
        }

        $mappings = [];

        $table_aliases = $this->getTableAliases($db_tables);
        foreach ($date_columns as $date_column) {
            $col_arr = explode('.', $date_column);
            $label = $table_aliases[$col_arr[0]] . ' ' . $col_arr[1];
            if (str_contains($query, $label)) {
                $mappings[$label] = (object) ['type'=> "datetime"];
            }
            if (str_contains($query, $label.'_date')) {
                $mappings[$label.'_date'] = (object) ['type'=> "date string"];
            }
        }
        if (count($mappings) > 0) {
            $mappings['today'] = (object) ['type'=> "date string"];
        }

        return $mappings;
    }

    public function reportSQL($id)
    {
        if ($this->erp_connection == session('instance')->db_connection) {
            $this->erp_connection = 'default';
        }
        $config = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('query_data')->first();
        $sql_where = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('sql_where')->first();

        $query_error = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->pluck('query_error')->first();
        
        if(str_contains($query_error,'Unknown column')){
            $sql_where_arr = explode(' ',$sql_where);
            foreach($sql_where_arr as $word){
                $output = preg_replace('#[^a-zA-Z_]#', '', $word);
                if($output == $word){
                    if(str_contains($query_error,$word)){
                        \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->update(['sql_where'=>'']);
                        $sql_where = '';
                        break;
                    }
                }    
            }
        }
        $data = unserialize($config);


        if (empty($data)) {
            return false;
        }

        extract($data);
        if ($db_conn == 'default') {
            $db_conn = $this->erp_connection;
        }
        if (empty($db_conn)) {
            return null;
        }
        if (empty($db_tables) || count($db_tables) == 0) {
            return null;
        }
        if (empty($db_columns) || count($db_columns) == 0) {
            return null;
        }
        if (!empty($db_tables) && count($db_tables) > 1 && (empty($join_type) || count($join_type) == 0)) {
            return null;
        }



        if (!empty($query_error) && str_contains($query_error, 'Column not found')) {
            $db_cols_update = [];
            $db_cols_needs_update = false;
            foreach ($db_columns as $i => $db_col) {
                $colname_arr = explode('.', $db_col);
                if (!\Schema::connection($db_conn)->hasColumn($colname_arr[0], $colname_arr[1])) {
                    unset($db_columns[$i]);
                    $db_cols_needs_update = true;
                } else {
                    $db_cols_update[] = $db_col;
                }
            }

            if ($db_cols_needs_update) {
                $update_data = $data;
                $update_data['db_columns'] = $db_cols_update;
                $config = serialize($update_data);
                \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->update(['query_data' => $config]);
            }
        }

        if (str_contains(strtolower($sql_where), 'where')) {
            $sql_where = str_replace('where', '', strtolower($sql_where));

            \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->update(['sql_where'=>$sql_where]);
        }

        if (!empty($customer_filter) && (in_array('crm_accounts', $db_tables) || in_array('v_domains', $db_tables))) {
            if (in_array('crm_accounts', $db_tables)) {
                if (empty($sql_where)) {
                    $sql_where = "crm_accounts.partner_id = 1 AND crm_accounts.type != 'lead' AND crm_accounts.type != 'reseller_user' AND crm_accounts.status != 'Deleted'";
                } elseif (!empty($sql_where) && !str_contains(strtolower($sql_where), 'crm_accounts.partner_id = 1')) {
                    $sql_where = $sql_where." and crm_accounts.partner_id = 1 AND crm_accounts.type != 'lead' AND crm_accounts.type != 'reseller_user' AND crm_accounts.status != 'Deleted'";
                }
            }
            if (in_array('v_domains', $db_tables)) {
                if (empty($sql_where)) {
                    $sql_where = "v_domains.partner_id = 1";
                } elseif (!empty($sql_where) && !str_contains(strtolower($sql_where), 'crm_accounts.partner_id = 1')) {
                    $sql_where = $sql_where." and v_domains.partner_id = 1";
                }
            }

            \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $id)->update(['sql_where'=>$sql_where]);
        }

       

        $selected_tables[] = $db_tables[0];
        $query = \DB::connection($db_conn)->table($db_tables[0]);
        $join_arr = $this->reportOrderJoins($data);
        if (!empty($join_arr) && count($join_arr) > 0) {
            foreach ($join_arr as $join) {
                $table_name_arr = explode('.', $join[1]);
                $table_name = $table_name_arr[0];

                if (!in_array($table_name, $selected_tables)) {
                    $selected_tables[] = $table_name;
                } else {
                    $table_name = null;
                }
                if (empty($table_name)) {
                    $table_name_arr = explode('.', $join[2]);
                    $table_name = $table_name_arr[0];

                    if (!in_array($table_name, $selected_tables)) {
                        $selected_tables[] = $table_name;
                    } else {
                        $table_name = null;
                    }
                }

                if (!empty($table_name)) {
                    if ($join[0] == 'INNER JOIN') {
                        $query->join($table_name, $join[1], '=', $join[2]);
                    }
                    if ($join[0] == 'LEFT JOIN') {
                        $query->leftJoin($table_name, $join[1], '=', $join[2]);
                    }
                    if ($join[0] == 'RIGHT JOIN') {
                        $query->rightJoin($table_name, $join[1], '=', $join[2]);
                    }
                }
            }
        }

        $date_columns = [];
        $datetime_columns = [];
        foreach ($db_tables as $table) {
            $cols = get_columns_from_schema($table, 'date', $db_conn);
            foreach ($cols as $c) {
                $date_columns[] = $table.'.'.$c;
            }
            $cols = get_columns_from_schema($table, 'datetime', $db_conn);
            foreach ($cols as $c) {
                $datetime_columns[] = $table.'.'.$c;
            }
        }

        $table_aliases = $this->getTableAliases($db_tables);
        $split_columns = [];
        foreach ($db_columns as $col) {
            $col_arr = explode('.', $col);
            if (!empty($date_columns) && count($date_columns) > 0 && in_array($col, $date_columns)) {
                $label = $table_aliases[$col_arr[0]] . ' ' . $col_arr[1];
                $label = str_replace('records_lastmonth ', '', $label);
                $label = str_replace('records ', '', $label);
                if ($col_arr[0] == 'call_records_outbound' && $col_arr[1] == 'hangup_time') {
                    $split_columns[] = "DATE_FORMAT(".$col_arr[0].".".$col_arr[1].",'%H') as '".$label."_hour'";
                    $split_columns[] = "DAYNAME(".$col_arr[0].".".$col_arr[1].") as '".$label."_dayname'";
                }


                //$col = $col." as '".$label."_splitdate'";
                //$split_columns[] = $col;
               /*
                $col = "DATE_FORMAT(".$col_arr[0].".".$col_arr[1].",'%Y-%m-%d') as '".$label."_fulldate'";
                $split_columns[] = $col;
                $col = "DATE_FORMAT(".$col_arr[0].".".$col_arr[1].",'%H') as '".$label."_hour'";
                $split_columns[] = $col;
                $col = "DATE_FORMAT(".$col_arr[0].".".$col_arr[1].",'%d') as '".$label."_day'";
                $split_columns[] = $col;
                $col = "DATE_FORMAT(".$col_arr[0].".".$col_arr[1].",'%m') as '".$label."_month'";
                $split_columns[] = $col;
                $col = "DATE_FORMAT(".$col_arr[0].".".$col_arr[1].",'%Y') as '".$label."_year'";
                $split_columns[] = $col;
                */
            }
        }

        if (count($split_columns) > 0) {
            foreach ($split_columns as $split_column) {
                $db_columns[] = $split_column;
            }
        }

        $db_columns[] = "CURDATE() as today";
        foreach ($db_columns as $col) {
            $col_selected = false;
            if (str_contains($col, 'where')) {
                continue;
            }
            if (!str_contains($col, ' as ')) {
                $col_arr = explode('.', $col);
                if (!\Schema::connection($db_conn)->hasColumn($col_arr[0], $col_arr[1])) {
                    continue;
                }
                $label = $table_aliases[$col_arr[0]] . ' ' . $col_arr[1];
                $label = str_replace('records_lastmonth ', '', $label);
                $label = str_replace('records ', '', $label);



                $col_type = get_column_type($col_arr[0], $col_arr[1], $db_conn);


                if ($col_type == 'boolean') {
                    $col = "CAST(".$col." as UNSIGNED) as '".$label."'";
                //$col = $col." as '".$label."'";
                } else {
                    $col = $col." as '".$label."'";
                }

                if ($col_arr[1] == 'created_at') {
                    $query->selectRaw("DATE(".$col_arr[0].".".$col_arr[1].") as '".$label."_date'");
                    $query->selectRaw("DATE_FORMAT(".$col_arr[0].".".$col_arr[1].", '%Y-%m-%dT%H:%i:%s') as '".$label."'");
                    $col_selected = true;
                }

                if ($col_arr[1] == 'created_date') {
                    $query->selectRaw("DAYNAME(".$col_arr[0].".".$col_arr[1].") as '".$label."_dayname'");
                    $query->selectRaw("DATE_FORMAT(".$col_arr[0].".".$col_arr[1].", '%Y-%m-%dT%H:%i:%s') as '".$label."'");
                    $col_selected = true;
                }
                if ($col_arr[1] == 'hangup_time') {
                    $query->selectRaw("DAYNAME(".$col_arr[0].".".$col_arr[1].") as '".$label."_dayname'");
                    $query->selectRaw("DATE_FORMAT(".$col_arr[0].".".$col_arr[1].", '%Y-%m-%dT%H:%i:%s') as '".$label."'");
                    $col_selected = true;
                }

                if ($col_arr[1] == 'docdate') {
                    $query->selectRaw("DATE_FORMAT(".$col_arr[0].".".$col_arr[1].", '%Y/%m/%d') as '".$label."'");
                    $query->selectRaw("DATE_FORMAT(".$col_arr[0].".".$col_arr[1].", '%Y/%m') as '".$label."_month'");
                    $col = "DATE_FORMAT(".$col_arr[0].".".$col_arr[1].",'%Y-%m-%d') as '".$label."_period'";
                }

                if ($col_type == "datetime") {
                    $query->selectRaw("DATE(".$col_arr[0].".".$col_arr[1].") as '".$label."_date'");
                    $query->selectRaw("DATE_FORMAT(".$col_arr[0].".".$col_arr[1].", '%Y-%m-%dT%H:%i:%s') as '".$label."'");

                    $col_selected = true;
                }

                if ($col_arr[0] == 'acc_ledger_account_categories' && $col_arr[1] == 'category') {
                    $col_selected = true;
                    $query->selectRaw("CONCAT(acc_ledger_account_categories.sort_order,' ',acc_ledger_account_categories.category) as '".$label."'");
                }

                if ($col_arr[0] == 'crm_supplier_documents' && $col_arr[1] == 'tax') {
                    $col_selected = true;
                    $query->selectRaw("(case crm_supplier_documents.doctype
                    when 'Supplier Debit Note' then (crm_supplier_documents.tax * -1)
                    else
                    crm_supplier_documents.tax
                    end) as '".$label."'");
                }

                if ($col_arr[0] == 'crm_products' && $col_arr[1] == 'type') {
                    $col_selected = false;
                    $query->selectRaw("(case crm_products.type
                    when 'Stock' then (1)
                    else
                    0
                    end) as 'is_product'");
                }

                if ($col_arr[0] == 'crm_supplier_documents' && $col_arr[1] == 'total') {
                    $col_selected = true;
                    $query->selectRaw("(case crm_supplier_documents.doctype
                    when 'Supplier Debit Note' then (crm_supplier_documents.total * -1)
                    else
                    crm_supplier_documents.total
                    end) as '".$label."'");
                }

                if ($col_arr[0] == 'crm_documents' && $col_arr[1] == 'total') {
                    $col_selected = true;
                    $query->selectRaw("(case crm_documents.doctype
                    when 'Credit Note' then (crm_documents.total * -1)
                    else
                    crm_documents.total
                    end) as '".$label."'");
                }
                if ($col_arr[0] == 'crm_documents' && $col_arr[1] == 'tax') {
                    $col_selected = true;
                    $query->selectRaw("(case crm_documents.doctype
                    when 'Credit Note' then (crm_documents.tax * -1)
                    else
                    crm_documents.tax
                    end) as '".$label."'");
                }
                // $col_arr = explode('.', $col);
               // $col = $col." as '".$col_arr[0]." ".$col_arr[1]."'";
            }
            if (!$col_selected) {
                $query->selectRaw($col);
            }
        }
        // remove where filters
        //$sql_where = '';
        if (!empty($date_filter_column) && !empty($date_filter_value)) {
            if ($date_filter_value == 'current day') {
                $date_filter = "(DATE(".$date_filter_column.") = CURDATE())";
            }
            if ($date_filter_value == 'current week') {
                $date_filter = $date_filter_column." >= ( CURDATE() - INTERVAL 1 WEEK )";
            }
            
            if ($date_filter_value == 'last hour') {
                $date_filter = $date_filter_column." >= ( NOW() - INTERVAL 1 HOUR )";
            }
            
            
            if ($date_filter_value == 'last 3 hours') {
                $date_filter = $date_filter_column." >= ( NOW() - INTERVAL 3 HOUR )";
            }
            
            if ($date_filter_value == 'last 6 hours') {
                $date_filter = $date_filter_column." >= ( NOW() - INTERVAL 6 HOUR )";
            }
            
            if ($date_filter_value == 'last 12 hours') {
                $date_filter = $date_filter_column." >= ( NOW() - INTERVAL 12 HOUR )";
            }
            
            if ($date_filter_value == 'current month') {
                $date_filter = "(DATE(".$date_filter_column.") between  DATE_FORMAT(NOW() ,'%Y-%m-01') AND NOW())";
            }

            if ($date_filter_value == 'first of current month') {
                $date_filter = $date_filter_column." = DATE_FORMAT( CURRENT_DATE, '%Y/%m/01' )";
            }

            if ($date_filter_value == 'previous month') {
                if ($date_filter_column == 'sub_services_lastmonth.created_at') {
                    $date_filter =  "(DATE(".$date_filter_column.") < DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y/%m/25' ))";
                } else {
                    $date_filter =  "(DATE(".$date_filter_column.") >= DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y/%m/01' ) AND DATE(".$date_filter_column.") < DATE_FORMAT( CURRENT_DATE, '%Y/%m/01' ))";
                }
            }

            if ($date_filter_value == 'current month last year') {
                $date_filter =  "(DATE(".$date_filter_column.") >= DATE_FORMAT( CURRENT_DATE - INTERVAL 1 YEAR, '%Y/%m/01' ) AND DATE(".$date_filter_column.") < LAST_DAY( CURRENT_DATE - INTERVAL 1 YEAR ))";
            }

            if ($date_filter_value == 'before six months ago') {
                $date_filter = $date_filter_column." <= ( CURDATE() - INTERVAL 6 MONTH )";
            }

            if ($date_filter_value == 'last six months') {
                $date_filter = $date_filter_column." >= ( CURDATE() - INTERVAL 6 MONTH )";
            }
            if ($date_filter_value == 'current year') {
                $date_filter = "(DATE(".$date_filter_column.") between DATE_FORMAT(NOW(),'%Y-01-01') AND NOW())";
            }

            if (str_contains($date_filter_value, 'monthfilter:')) {
                $date_filter_value = str_replace('monthfilter:', '', $date_filter_value);
                $date_filter = $date_filter_column.' LIKE "'.date('Y-m', strtotime($date_filter_value)).'%"';
            }


            if (!empty($date_filter)) {
                if (!empty($sql_where)) {
                    $sql_where .= ' and '.$date_filter;
                } else {
                    $sql_where = ' '.$date_filter;
                }
            }
        }


        if (!empty(trim($sql_where))) {
            $sql_where = str_replace("= '1900-01-01'", "is null", $sql_where);

            $query->whereRaw($sql_where);
        }

        $sql = querybuilder_to_sql($query);

        if (str_contains($sql, 'sub_services_lastmonth')) {
            $table_name = 'crm_accounts';
            $new_table_name = 'crm_accounts_lastmonth';
            $sql = preg_replace('/\b'.$table_name.'\b/', $new_table_name, $sql);
        }

        return $sql;
    }

    public function reportGetTables($request)
    {
        $request = (object) $request;
        if (empty($request->db_conn)) {
            return [];
        }
        $tables = [];
        if ($request->db_conn == 'default') {
            $request->db_conn = $this->erp_connection;
        }
        $schema = get_tables_from_schema($request->db_conn);
        foreach ($schema as $table) {
            if (!str_contains($table, '_copy')) {
                $tables[] = (object) ['text' => $table, 'value' => $table];
            }
        }
        return $tables;
    }

    public function getTableAliases($tables)
    {
        $table_aliases = [];
        foreach ($tables as $table) {
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
        return $table_aliases;
    }

    public function reportGetColumns($request)
    {
        $request = (object) $request;

        if (empty($request->db_conn) || empty($request->db_tables)) {
            return [];
        }

        $response = [];
        $response['values'] = [];
        if (!empty($request->db_columns)) {
            $response['values'] = $request->db_columns;
        }
        $columns = [];
        $tables = $request->db_tables;
        natsort($tables);
        if ($request->db_conn == 'default') {
            $request->db_conn = $this->erp_connection;
        }

        $add_table_columns = [];
        if (!empty($request->db_tables) && !empty($request->source_db_tables)) {
            foreach ($request->db_tables as $dbt) {
                if (!in_array($dbt, $request->source_db_tables)) {
                    $add_table_columns[] =$dbt;
                }
            }
        }

        foreach ($tables as $table) {
            $table_columns = collect(get_columns_from_schema($table, null, $request->db_conn))->toArray();
            natsort($table_columns);

            foreach ($table_columns as $field) {
                $columns[] = (object) ['text' => $table.'.'.$field, 'value' => $table.'.'.$field];
                if (in_array($table, $add_table_columns)) {
                    $response['values'][] = $table.'.'.$field;
                }
            }
        }
        foreach ($response['values'] as $i => $col) {
            $col_arr = explode('.', $col);
            $table = $col_arr[0];
            $field = $col_arr[1];
            if (!in_array($table, $request->db_tables)) {
                unset($response['values'][$i]);
            }
        }
        $response['values'] = array_values($response['values']);
        $response['datasource'] = $columns;

        return $response;
    }

    public function reportGetDateColumns($request)
    {
        $request = (object) $request;
        if (empty($request->db_conn) || empty($request->db_tables) || count($request->db_tables) == 0) {
            return [];
        }
        $date_columns = [];
        $db_conn = $request->db_conn;
        $db_tables = $request->db_tables;
        natsort($db_tables);
        if ($request->db_conn == 'default') {
            $request->db_conn = $this->erp_connection;
        }
        foreach ($db_tables as $table) {
            $cols = get_columns_from_schema($table, 'date', $db_conn);
            foreach ($cols as $c) {
                $date_columns[] = $table.'.'.$c;
            }
            $cols = get_columns_from_schema($table, 'datetime', $db_conn);
            foreach ($cols as $c) {
                $date_columns[] = $table.'.'.$c;
            }
        }

        return $date_columns;
    }

    public function reportGetJoinColumns($request)
    {
        $columns = [];
        $joins_ds = [];
        $joined_on = [];
        $report_joins = \DB::connection('system')->table('erp_report_joins')->get();
        $request = (object) $request;
        if (empty($request->db_conn) || empty($request->db_tables)) {
            return [];
        }
        $columns = [];
        $tables = $request->db_tables;
        natsort($tables);

        $report_connection = $request->db_conn;
        if ($report_connection == 'default') {
            $report_connection = $this->erp_connection;
        }
        foreach ($tables as $table) {
            $table_columns = collect(get_columns_from_schema($table, null, $report_connection))->toArray();
            natsort($table_columns);
            foreach ($table_columns as $field) {
                if ($table=='acc_ledgers') {
                    $joins_ds[] = $table.'.'.$field;
                } elseif ($field == 'id' || str_ends_with($field, 'id')) {
                    $joins_ds[] = $table.'.'.$field;
                } elseif ($field == 'doctype') {
                    $joins_ds[] = $table.'.'.$field;
                }
            }


            $fields = get_columns_from_schema($table, null, $report_connection);
            foreach ($report_joins as $join) {
                $join_arr_1 = explode('.', $join->join_1);
                $join_arr_2 = explode('.', $join->join_2);
                $join_table_1 = $join_arr_1[0];
                $join_field_1 = $join_arr_1[1];
                $join_table_2 = $join_arr_2[0];
                $join_field_2 = $join_arr_2[1];
                if ($join_table_1 == '*') {
                    $join_table_1 = $table;
                }

                $j1 = $join_table_1.'.'.$join_field_1;
                $j2 = $join->join_2;

                if (!in_array($j1, $joined_on) && !in_array($j2, $joined_on) &&
               in_array($join_table_1, $tables) && in_array($join_table_2, $tables) &&
               in_array($join_field_1, $fields) &&
               $j1 != $j2) {
                    $joined_on[] = $j1;
                    $joined_on[] = $j2;
                    $joins_ds[] = $j1;
                    $joins_ds[] = $j2;
                }
            }
        }

        $joins_ds = collect($joins_ds)->unique()->toArray();
        natsort($joins_ds);
        foreach ($joins_ds as $jds) {
            $columns[] = (object) ['text' => $jds, 'value' => $jds];
        }

        return $columns;
    }

    public function reportGetFilterColumns($request)
    {
        $request = (object) $request;
        if (empty($request->db_conn) || empty($request->db_tables)) {
            return [];
        }
        $columns = [];
        $filter_columns = [];
        $tables = $request->db_tables;
        if (is_array($tables)) {
            natsort($tables);
        }
        $id = 1;

        if ($request->db_conn == 'default') {
            $request->db_conn = $this->erp_connection;
        }
        foreach ($tables as $table) {
            $integer_columns = get_columns_from_schema($table, 'integer', $request->db_conn);

            foreach ($integer_columns as $field) {
                $field = $table.'__'.$field;
                $label = '';

                $table_arr = explode('_', $table);
                foreach ($table_arr as $table_word) {
                    $label .= $table_word[0];
                }
                $label .= ' '.str_replace($table.'__', '', $field);

                $columns[] = $field;
                $filter_columns[] = (object) ['id' => $field, 'field' => $field,'label' => $label,'type' => 'number','validation' => (object)['min' => '-9999999999']];
                $id++;
            }

            $boolean_columns = get_columns_from_schema($table, 'boolean', $request->db_conn);

            foreach ($boolean_columns as $field) {
                $field = $table.'__'.$field;
                $label = '';

                $table_arr = explode('_', $table);
                foreach ($table_arr as $table_word) {
                    $label .= $table_word[0];
                }
                $label .= ' '.str_replace($table.'__', '', $field);

                $columns[] = $field;
                $filter_columns[] = (object) ['id' => $field,'field' => $field,'label' => $label,'type' => 'number','validation' => (object)['min' => '-9999999999']];
                $id++;
            }

            $decimal_columns = get_columns_from_schema($table, 'decimal', $request->db_conn);
            foreach ($decimal_columns as $field) {
                $field = $table.'__'.$field;
                $label = '';

                $table_arr = explode('_', $table);
                foreach ($table_arr as $table_word) {
                    $label .= $table_word[0];
                }
                $label .= ' '.str_replace($table.'__', '', $field);

                $columns[] = $field;
                $filter_columns[] = (object) ['id' => $field,'field' => $field,'label' => $label,'type' => 'number','validation' => (object)['min' => '-9999999999']];
                $id++;
            }

            $decimal_columns = get_columns_from_schema($table, 'float', $request->db_conn);
            foreach ($decimal_columns as $field) {
                $field = $table.'__'.$field;
                $label = '';

                $table_arr = explode('_', $table);
                foreach ($table_arr as $table_word) {
                    $label .= $table_word[0];
                }
                $label .= ' '.str_replace($table.'__', '', $field);

                $columns[] = $field;
                $filter_columns[] = (object) ['id' => $field,'field' => $field,'label' => $label,'type' => 'number','validation' => (object)['min' => '-9999999999']];
                $id++;
            }

            $date_columns = get_columns_from_schema($table, 'date', $request->db_conn);
            foreach ($date_columns as $field) {
                $field = $table.'__'.$field;
                $label = '';

                $table_arr = explode('_', $table);
                foreach ($table_arr as $table_word) {
                    $label .= $table_word[0];
                }
                $label .= ' '.str_replace($table.'__', '', $field);

                $columns[] = $field;
                $filter_columns[] = (object) ['id' => $field,'field' => $field,'label' => $label,'type' => 'date'];
                $id++;
            }

            $datetime_columns = get_columns_from_schema($table, 'datetime', $request->db_conn);
            foreach ($datetime_columns as $field) {
                $field = $table.'__'.$field;
                $label = '';

                $table_arr = explode('_', $table);
                foreach ($table_arr as $table_word) {
                    $label .= $table_word[0];
                }
                $label .= ' '.str_replace($table.'__', '', $field);

                $columns[] = $field;
                $filter_columns[] = (object) ['id' => $field,'field' => $field,'label' => $label,'type' => 'date'];
                $id++;
            }

            $filter_column_names = collect($filter_columns)->pluck('field')->toArray();
            $table_columns = get_columns_from_schema($table, null, $request->db_conn);
            foreach ($table_columns as $field) {
                $column_name = $field;
                $field = $table.'__'.$field;
                if (!in_array($field, $filter_column_names)) {
                    $show_values = false;
                    if ($request->db_conn != 'pbx_cdr' && $table!='erp_communication_lines') {
                        $values = \DB::connection($request->db_conn)->table($table)->select($column_name)->groupBy($column_name)->pluck($column_name)->toArray();
                        if ($column_name == 'doctype') {
                        }
                        if (count($values) > 0 && count($values) < 50) {
                            $show_values = true;
                        }
                    }
                    $label = '';

                    $table_arr = explode('_', $table);
                    foreach ($table_arr as $table_word) {
                        $label .= $table_word[0];
                    }
                    $label .= ' '.str_replace($table.'__', '', $field);

                    if ($show_values) {
                        $filter_columns[] = (object) ['id' => $field,'field' => $field,'label' => $label,'type' => 'string', 'values' => $values];
                    } else {
                        $filter_columns[] = (object) ['id' => $field,'field' => $field,'label' => $label,'type' => 'string'];
                    }
                    $id++;
                }
            }
        }

        usort($filter_columns, function ($a, $b) {
            return strnatcasecmp($a->label, $b->label);
        });
        return $filter_columns;
    }

    public function reportResetJoinsById($report_id)
    {
        $report = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $report_id)->get()->first();
        $report_joins = \DB::connection('system')->table('erp_report_joins')->get();
        //dd($report);
        $query_data = unserialize($report->query_data);

        $query_data['join_table_1'] = [];
        $query_data['join_type'] = [];
        $query_data['join_table_2'] = [];
        if (empty($query_data['join_tables_1_vals'])) {
            $query_data['join_tables_1_vals'] = [];
        }
        $report_connection = $report->connection;
        if ($report_connection == 'default') {
            $report_connection = $this->erp_connection;
        }
        $joined_on = [];
        $num_joins = count($query_data['db_tables']) - 1;
        if (!empty($query_data['db_tables']) && count($query_data['db_tables']) > 1) {
            foreach ($query_data['db_tables'] as $table) {
                $fields = get_columns_from_schema($table, null, $report_connection);
                foreach ($report_joins as $join) {
                    $join_arr_1 = explode('.', $join->join_1);
                    $join_arr_2 = explode('.', $join->join_2);
                    $join_table_1 = $join_arr_1[0];
                    $join_field_1 = $join_arr_1[1];
                    $join_table_2 = $join_arr_2[0];
                    $join_field_2 = $join_arr_2[1];
                    if ($join_table_1 == '*') {
                        $join_table_1 = $table;
                    }
                    $j1 = $join_table_1.'.'.$join_field_1;
                    $j2 = $join->join_2;
                    if (count($query_data['join_tables_1_vals']) < $num_joins && !in_array($j1, $joined_on) && !in_array($j2, $joined_on) && in_array($join_table_1, $query_data['db_tables']) && in_array($join_table_2, $query_data['db_tables']) && in_array($join_field_1, $fields)) {
                        $joined_on[] = $j1;
                        $joined_on[] = $j2;

                        $query_data['join_table_1'][] =$j1;
                        $query_data['join_type'][] = 'LEFT JOIN';
                        $query_data['join_table_2'][] = $j2;
                    }
                }
            }
        }

        $query_data = serialize($query_data);

        \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $report_id)->update(['query_data' => $query_data]);
    }

    public function reportResetJoinsByRequest($request)
    {
        $data = [];
        $report_joins = \DB::connection('system')->table('erp_report_joins')->get();
        //dd($report);
        $query_data = unserialize($report->query_data);
        $joins_ds = $this->reportGetJoinColumns($request);

        $query_data['join_tables_1_vals'] = [];
        $query_data['join_type'] = [];
        $query_data['join_tables_2_vals'] = [];

        $report_connection = $request->db_conn;
        if ($report_connection == 'default') {
            $report_connection = $this->erp_connection;
        }


        $joined_on = [];
        $valid_joins = collect($joins_ds)->pluck('text')->toArray();
        if (!is_array($request->db_tables) || empty($request->db_tables)) {
            $num_joins = 0;
        } else {
            $num_joins = count($request->db_tables) - 1;
        }
        if (!empty($request->db_tables) && count($request->db_tables) > 1) {
            foreach ($request->db_tables as $i => $table) {
                $next_table = $request->db_tables[$i+1];
                $next_table_joined = false;
                foreach ($valid_joins as $valid_join) {
                    if (count($query_data['join_tables_1_vals']) > 0) {
                        $right_table_joined = false;
                        foreach ($query_data['join_tables_2_vals'] as $i => $join_tables_2_val) {
                            if (str_contains($query_data['join_tables_1_vals'][$i], $table.'.')) {
                               
                                $right_table_joined = true;
                            }
                            if (str_contains($join_tables_2_val, $table.'.')) {
                              
                                $right_table_joined = true;
                            }
                        }
                        if ($right_table_joined) {
                            continue 2;
                        }
                    }

                    if (str_contains($valid_join, $next_table.'.')) {
                        if (!empty($next_table)) {
                            $varr = explode('.', $valid_join);
                            $join = \DB::connection('system')->table('erp_report_joins')->where('join_1', $valid_join)->get()->first();
                            if (!empty($join)) {
                                $j1 = $valid_join;
                                $j2 = $join->join_2;
                                $right_table_arr = explode('.', $j2);
                                $right_table = $right_table_arr[0];
                                if (count($query_data['join_tables_1_vals']) < $num_joins && $j2 && $j1!=$j2 && in_array($right_table, $request->db_tables) && !in_array($j1, $joined_on) && !in_array($j2, $joined_on)) {
                                    $query_data['join_tables_1_vals'][] =$j1;
                                    $query_data['join_type'][] = 'LEFT JOIN';
                                    $query_data['join_tables_2_vals'][] = $j2;
                                    $next_table_joined = true;

                                    $joined_on[] = $j1;
                                    $joined_on[] = $j2;

                                    continue;
                                }
                            }
                        }
                    }
                }
                if (!$next_table_joined) {
                    foreach ($valid_joins as $valid_join) {
                        if (count($query_data['join_tables_1_vals']) > 0) {
                            $right_table_joined = false;
                            foreach ($query_data['join_tables_2_vals'] as $i => $join_tables_2_val) {
                                if (str_contains($query_data['join_tables_1_vals'][$i], $table.'.')) {
                                    $right_table_joined = true;
                                }
                                if (str_contains($join_tables_2_val, $table.'.')) {
                                    $right_table_joined = true;
                                }
                            }
                            if ($right_table_joined) {
                                continue 2;
                            }
                        }

                        if (str_contains($valid_join, $table.'.')) {
                            $varr = explode('.', $valid_join);
                            $join = \DB::connection('system')->table('erp_report_joins')->where('join_1', $valid_join)->get()->first();
                            if (!empty($join)) {
                                $j1 = $valid_join;
                                $j2 = $join->join_2;
                                $right_table_arr = explode('.', $j2);
                                $right_table = $right_table_arr[0];
                                if (count($query_data['join_tables_1_vals']) < $num_joins && $j2 && $j1!=$j2 && in_array($right_table, $request->db_tables) && !in_array($j1, $joined_on) && !in_array($j2, $joined_on)) {
                                    $query_data['join_tables_1_vals'][] =$j1;
                                    $query_data['join_type'][] = 'LEFT JOIN';
                                    $query_data['join_tables_2_vals'][] = $j2;
                                    $joined_on[] = $j1;
                                    $joined_on[] = $j2;


                                    continue;
                                }
                            }
                            $join = \DB::connection('system')->table('erp_report_joins')->where('join_1', '*.'.$varr[1])->get()->first();
                            if (!empty($join)) {
                                $j1 = $valid_join;
                                $j2 = $join->join_2;
                                $right_table_arr = explode('.', $j2);
                                $right_table = $right_table_arr[0];
                                if (count($query_data['join_tables_1_vals']) < $num_joins && $j2 && $j1!=$j2 && in_array($right_table, $request->db_tables) && !in_array($j1, $joined_on) && !in_array($j2, $joined_on)) {
                                    $query_data['join_tables_1_vals'][] =$j1;
                                    $query_data['join_type'][] = 'LEFT JOIN';
                                    $query_data['join_tables_2_vals'][] = $j2;
                                    $joined_on[] = $j1;
                                    $joined_on[] = $j2;


                                    continue;
                                }
                            }

                            $join = \DB::connection('telecloud')->table('erp_report_joins')->where('join_2', $valid_join)->get()->first();

                            if (!empty($join)) {
                                $j1 = $valid_join;
                                $j2 = $join->join_1;


                                $right_table_arr = explode('.', $j2);
                                $right_table = $right_table_arr[0];
                                $wild_card_joined = false;

                                if (str_contains($j2, '*.') && !$wild_card_joined) {
                                    $j2 = false;
                                    foreach ($request->db_tables as $table2) {
                                        if ($table2 == $table) {
                                            continue;
                                        }
                                        foreach ($valid_joins as $valid_join2) {
                                            if ($valid_join2 == $valid_join) {
                                                continue;
                                            }


                                            if ($valid_join2 == $table2.'.'.$right_table_arr[1]) {
                                                $j2 = $table2.'.'.$right_table_arr[1];
                                                $wild_card_joined = true;
                                                $right_table = $table2;
                                            }
                                        }
                                    }
                                }

                                if (count($query_data['join_tables_1_vals']) < $num_joins && $j2 && $j1!=$j2 && in_array($right_table, $request->db_tables) && !in_array($j1, $joined_on) && !in_array($j2, $joined_on)) {
                                    $query_data['join_tables_1_vals'][] =$j1;
                                    $query_data['join_type'][] = 'LEFT JOIN';
                                    $query_data['join_tables_2_vals'][] = $j2;
                                    $joined_on[] = $j1;
                                    $joined_on[] = $j2;
                                }
                            }
                        }
                    }
                }
            }
        }

        $query_data['joins_ds'] = $joins_ds;

        return $query_data;
    }

    public function setReportDefaults($report_id)
    {
        \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $report_id)->update(['report_config' => '']);
        return true;
    }
}


/*
join code backup

  public function reportResetJoinsById($report_id)
    {
        $report = \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $report_id)->get()->first();
        $report_joins = \DB::connection('system')->table('erp_report_joins')->get();
        //dd($report);
        $query_data = unserialize($report->query_data);

        $query_data['join_table_1'] = [];
        $query_data['join_type'] = [];
        $query_data['join_table_2'] = [];
        if (empty($query_data['join_tables_1_vals'])) {
            $query_data['join_tables_1_vals'] = [];
        }
        $report_connection = $report->connection;
        if ($report_connection == 'default') {
            $report_connection = $this->erp_connection;
        }
        $joined_on = [];
        $num_joins = count($query_data['db_tables']) - 1;
        if (!empty($query_data['db_tables']) && count($query_data['db_tables']) > 1) {
            foreach ($query_data['db_tables'] as $table) {
                $fields = get_columns_from_schema($table, null, $report_connection);
                foreach ($report_joins as $join) {
                    $join_arr_1 = explode('.', $join->join_1);
                    $join_arr_2 = explode('.', $join->join_2);
                    $join_table_1 = $join_arr_1[0];
                    $join_field_1 = $join_arr_1[1];
                    $join_table_2 = $join_arr_2[0];
                    $join_field_2 = $join_arr_2[1];
                    if ($join_table_1 == '*') {
                        $join_table_1 = $table;
                    }
                    $j1 = $join_table_1.'.'.$join_field_1;
                    $j2 = $join->join_2;
                    if (count($query_data['join_tables_1_vals']) < $num_joins && !in_array($j1, $joined_on) && !in_array($j2, $joined_on) && in_array($join_table_1, $query_data['db_tables']) && in_array($join_table_2, $query_data['db_tables']) && in_array($join_field_1, $fields)) {
                        if ($j1 != 'crm_accounts.id') {
                            $joined_on[] = $j1;
                        }
                        if ($j2 != 'crm_accounts.id') {
                            $joined_on[] = $j2;
                        }
                        $query_data['join_table_1'][] =$j1;
                        $query_data['join_type'][] = 'LEFT JOIN';
                        $query_data['join_table_2'][] = $j2;
                    }
                }
            }
        }

        $query_data = serialize($query_data);

        \DB::connection($this->erp_connection)->table('erp_reports')->where('id', $report_id)->update(['query_data' => $query_data]);
    }

    public function reportResetJoinsByRequest($request)
    {
        $data = [];
        $report_joins = \DB::connection('system')->table('erp_report_joins')->get();
        //dd($report);
        $query_data = unserialize($report->query_data);
        $joins_ds = $this->reportGetJoinColumns($request);

        $query_data['join_tables_1_vals'] = [];
        $query_data['join_type'] = [];
        $query_data['join_tables_2_vals'] = [];

        $report_connection = $request->db_conn;
        if ($report_connection == 'default') {
            $report_connection = $this->erp_connection;
        }


        $joined_on = [];
        $valid_joins = collect($joins_ds)->pluck('text')->toArray();
        if (!is_array($request->db_tables) || empty($request->db_tables)) {
            $num_joins = 0;
        } else {
            $num_joins = count($request->db_tables) - 1;
        }
        if (!empty($request->db_tables) && count($request->db_tables) > 1) {
            foreach ($request->db_tables as $table) {
                foreach ($valid_joins as $valid_join) {
                    if (str_contains($valid_join, $table.'.')) {
                        $varr = explode('.', $valid_join);
                        $join = \DB::connection('system')->table('erp_report_joins')->where('join_1', $valid_join)->get()->first();
                        if (!empty($join)) {
                            $j1 = $valid_join;
                            $j2 = $join->join_2;
                            $right_table_arr = explode('.', $j2);
                            $right_table = $right_table_arr[0];
                            if (count($query_data['join_tables_1_vals']) < $num_joins && $j2 && $j1!=$j2 && in_array($right_table, $request->db_tables) && !in_array($j1, $joined_on) && !in_array($j2, $joined_on)) {
                                if ($j1 != 'crm_accounts.id') {
                                    $joined_on[] = $j1;
                                }
                                if ($j2 != 'crm_accounts.id') {
                                    $joined_on[] = $j2;
                                }
                                $query_data['join_tables_1_vals'][] =$j1;
                                $query_data['join_type'][] = 'LEFT JOIN';
                                $query_data['join_tables_2_vals'][] = $j2;
                            }
                        }
                        $join = \DB::connection('system')->table('erp_report_joins')->where('join_1', '*.'.$varr[1])->get()->first();
                        if (!empty($join)) {
                            $j1 = $valid_join;
                            $j2 = $join->join_2;
                            $right_table_arr = explode('.', $j2);
                            $right_table = $right_table_arr[0];
                            if (count($query_data['join_tables_1_vals']) < $num_joins && $j2 && $j1!=$j2 && in_array($right_table, $request->db_tables) && !in_array($j1, $joined_on) && !in_array($j2, $joined_on)) {
                                if ($j1 != 'crm_accounts.id') {
                                    $joined_on[] = $j1;
                                }
                                if ($j2 != 'crm_accounts.id') {
                                    $joined_on[] = $j2;
                                }
                                $query_data['join_tables_1_vals'][] =$j1;
                                $query_data['join_type'][] = 'LEFT JOIN';
                                $query_data['join_tables_2_vals'][] = $j2;
                            }
                        }

                        $join = \DB::connection('telecloud')->table('erp_report_joins')->where('join_2', $valid_join)->get()->first();

                        if (!empty($join)) {
                            $j1 = $valid_join;
                            $j2 = $join->join_1;


                            $right_table_arr = explode('.', $j2);
                            $right_table = $right_table_arr[0];
                            $wild_card_joined = false;

                            if (str_contains($j2, '*.') && !$wild_card_joined) {
                                $j2 = false;
                                foreach ($request->db_tables as $table2) {
                                    if ($table2 == $table) {
                                        continue;
                                    }
                                    foreach ($valid_joins as $valid_join2) {
                                        if ($valid_join2 == $valid_join) {
                                            continue;
                                        }


                                        if ($valid_join2 == $table2.'.'.$right_table_arr[1]) {
                                            $j2 = $table2.'.'.$right_table_arr[1];
                                            $wild_card_joined = true;
                                            $right_table = $table2;
                                        }
                                    }
                                }
                            }

                            if (count($query_data['join_tables_1_vals']) < $num_joins && $j2 && $j1!=$j2 && in_array($right_table, $request->db_tables) && !in_array($j1, $joined_on) && !in_array($j2, $joined_on)) {
                                if ($j1 != 'crm_accounts.id') {
                                    $joined_on[] = $j1;
                                }
                                if ($j2 != 'crm_accounts.id') {
                                    $joined_on[] = $j2;
                                }
                                $query_data['join_tables_1_vals'][] =$j1;
                                $query_data['join_type'][] = 'LEFT JOIN';
                                $query_data['join_tables_2_vals'][] = $j2;
                            }
                        }
                    }
                }
            }
        }

        $query_data['joins_ds'] = $joins_ds;

        return $query_data;
    }

*/
