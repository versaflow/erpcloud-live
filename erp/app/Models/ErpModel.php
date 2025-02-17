<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ErpModel extends Model
{
    public $module_name;
    public $table;
    public $primary_key;
    public $info;
    public $menu;
    public $module;
    public $join_order_fields;
    public $aliased_fields;
    public $sql_where_filter;
    public $joins;
    public $erp_conn;
    public $remove_grouping;
    public $workboard_query;
    public $alias_search;

    public function __construct($table = null, $erp_conn = null)
    {
        if(!$erp_conn){
            $this->erp_conn = 'default';
        }
        if ($table) {
            $module_id = \DB::connection($this->erp_conn)->table('erp_cruds')->where('db_table', $table)->pluck('id')->first();
            
            $this->setModelData($module_id);
        }
        $this->joins = [];
        $this->workboard_query = 0;
        $this->alias_search = 1;
    }
    
    public function setAliasSearch($alias_search){
        $this->alias_search = $alias_search;  
    }
    
    public function setWorkboardQuery($workboard_query){
        $this->workboard_query = $workboard_query;  
    }

    public function setSqlWhereFilter($sql_where_filter){
        $this->sql_where_filter = $sql_where_filter;  
    }

    public function setMenuData($menu)
    {
       
        if(str_starts_with($menu,'detailmodule_')){
          
            $detail_module_id = str_replace('detailmodule_','',$menu);
            $this->is_detail_module = true;
            $this->setModelData($detail_module_id); 
         
        }else{
            $current_conn = \DB::getDefaultConnection();
            $module_id = \DB::connection($this->erp_conn)->table('erp_cruds')->where('slug', $menu)->pluck('id')->first();
            $this->menu = app('erp_config')['menus']->where('module_id', $module_id)->where('menu_type', 'module')->first();
            
            $this->setModelData($module_id);
            if (!$this->menu) {
                return false;
            }
        }
    }

    public function setModelData($module_id, $conn = false)
    {
        if($conn == false){
            $conn = $this->erp_conn;
        }
        $this->module = \DB::connection($conn)->table('erp_cruds')->where('id', $module_id)->get()->first();
        if($this->module->connection=='default' && $conn!='default'){
            $this->module->connection = $conn;
        }
      
        if(str_contains($this->module->connection,'pbx')){
            $this->use_pg = false;    
        }else{
            $this->use_pg = false;        
        }

       
          
        $this->join_order_fields = [];
        $this->module_name = $this->module->name;
        $this->table = $this->module->db_table;
        $this->primary_key = $this->module->db_key;
        $this->makeInfo();
        $this->aliased_fields = \DB::connection($conn)->table('erp_module_fields')->where('aliased_field',1)->where('module_id',$module_id)->pluck('field')->toArray();
        $has_soft_delete_field = \DB::connection($conn)->table('erp_module_fields')->where('field','is_deleted')->where('module_id',$module_id)->count();
        $has_status_field = \DB::connection($conn)->table('erp_module_fields')->where('field','status')->where('module_id',$module_id)->count();
       
        if(empty(session('remove_show_deleted'.$module_id))){
       
            if(empty(session('show_deleted'.$module_id))){
              
                
                if($has_soft_delete_field){
       
                    if(!empty($this->module->db_where)){
                        $this->info['db_where'] = 'where ('.str_replace('where','',strtolower($this->module->db_where)).') and '.$this->module->db_table.'.is_deleted=0 ';   
                        $this->module->db_where = 'where ('.str_replace('where','',strtolower($this->module->db_where)).')  and '.$this->module->db_table.'.is_deleted=0 ';   
                    }else{
                        $this->info['db_where'] = ' where '.$this->module->db_table.'.is_deleted=0 '; 
                        $this->module->db_where = ' where '.$this->module->db_table.'.is_deleted=0 '; 
                    }
                }else if($has_status_field){
        
               
                    if(!empty($this->module->db_where)){
                        $this->info['db_where'] = 'where ('.str_replace('where','',strtolower($this->module->db_where)).') and '.$this->module->db_table.'.status!="Deleted" ';   
                        $this->module->db_where = 'where ('.str_replace('where','',strtolower($this->module->db_where)).')  and '.$this->module->db_table.'.status!="Deleted" ';   
                    }else{
                        $this->info['db_where'] = ' where '.$this->module->db_table.'.status!="Deleted" '; 
                        $this->module->db_where = ' where '.$this->module->db_table.'.status!="Deleted" '; 
                    }
                }
            }
        }
     
        if(!empty(session('toggle_workboard_completed_tasks')) && $this->module->id == 2018){
            $this->module->db_where = ' where '.$this->module->db_table.'.progress_status IN ("Done","Task Done") AND stop_time LIKE "'.date('Y-m-d').'%" '; 
            $this->info['db_where'] = ' where '.$this->module->db_table.'.progress_status IN ("Done","Task Done") AND stop_time LIKE "'.date('Y-m-d').'%" '; 
           
        }
        
        session(['module_connection' => $this->module->connection]);
        set_db_connection($this->module->connection);
    }

    /*aggrid query*/
    public function getSetFilterValues($request, $field)
    {
        $SQL = $this->buildSql($request, 'filter');
        ////////aa($this->module->connection);
        ////////aa($SQL);
        $results = \DB::connection($this->module->connection)->select($SQL);
        $filter_options = collect($results)->pluck($field);
        return $filter_options;
    }

    public function getClientSql($request = [])
    {
       //aa($request->all());
        if(!empty($request->hide_deleted_rows)){
                
                $has_soft_delete_field = \DB::connection($this->erp_conn)->table('erp_module_fields')->where('field','is_deleted')->where('module_id',$this->module->id)->count();
                $has_status_field = \DB::connection($this->erp_conn)->table('erp_module_fields')->where('field','status')->where('module_id',$this->module->id)->count();
                if($has_soft_delete_field){
       
                    if(!empty($this->module->db_where)){
                        $this->info['db_where'] = 'where ('.str_replace('where','',strtolower($this->module->db_where)).') and '.$this->module->db_table.'.is_deleted=0 ';   
                        $this->module->db_where = 'where ('.str_replace('where','',strtolower($this->module->db_where)).')  and '.$this->module->db_table.'.is_deleted=0 ';   
                    }else{
                        $this->info['db_where'] = ' where '.$this->module->db_table.'.is_deleted=0 '; 
                        $this->module->db_where = ' where '.$this->module->db_table.'.is_deleted=0 '; 
                    }
                }else if($has_status_field){
        
               
                    if(!empty($this->module->db_where)){
                        $this->info['db_where'] = 'where ('.str_replace('where','',strtolower($this->module->db_where)).') and '.$this->module->db_table.'.status!="Deleted" ';   
                        $this->module->db_where = 'where ('.str_replace('where','',strtolower($this->module->db_where)).')  and '.$this->module->db_table.'.status!="Deleted" ';   
                    }else{
                        $this->info['db_where'] = ' where '.$this->module->db_table.'.status!="Deleted" '; 
                        $this->module->db_where = ' where '.$this->module->db_table.'.status!="Deleted" '; 
                    }
                }
            
        }
        
        if(!empty($request->show_deleted_rows)){
            $this->info['db_where'] = $this->module->db_where;   
            $this->module->db_where = $this->module->db_where;   
        }
        
        $sql = $this->buildSql($request, 'clientside');
        
        //aa($sql);
        return $sql;
    }

    public function getTotalCount()
    {
        return \DB::connection($this->module->connection)->table($this->module->db_table)->count();
    }

    public function getData($request, $conn = false)
    {
       
        //aa($request->all());
       // foreach ($request->all() as $k => $v) {
            //    //////aa($k);
        //    //////aa($v);
       // }
        $SQL = $this->buildSql($request);
        //aa($request->all());
        //aa($SQL);
        if(!empty($request->dashboard_tracking)){
            $SQL = str_replace('select, ','select ',$SQL);
        }
        
        if(!empty($request->return_sql)){
        return $SQL;
        }
   
     //aa($this->joins);
        //////////aa($request->startRow);
        //////////aa($request->endRow);
        if(!$conn){
            $conn = $this->module->connection;
        }
       
        $results = \DB::connection($conn)->select($SQL);
    
        //if(is_dev()){
                $rowGroupCols = $request->rowGroupCols;
                $groupKeys = $request->groupKeys;
                $filterModel = $request->filterModel;
                $fields = $this->info['module_fields'];
                
                if ($this->isDoingGrouping($rowGroupCols, $groupKeys)) {
                   
                       $SQL_arr = explode('FROM',$SQL);
                       $SQL_arr[0] = 'SELECT count(*) AS total_rows ';
                       $count_limit_SQL = implode('FROM',$SQL_arr);
                       
                       $count_limit_SQL_arr = explode('LIMIT',$count_limit_SQL);
                       $count_SQL = $count_limit_SQL_arr[0];
                       $rowCount =  count(\DB::connection($this->module->connection)->select($count_SQL));
                   
                      //  $rowCount = count($results);
                    
                   
                }else{
                   // count sql 
                               
                    $count_SQL = $this->buildSql($request, 'count');
                
                    $count_SQL = str_replace($this->module->db_table.'.*,',$this->module->db_table.'.'.$this->module->db_key.',',$count_SQL);
                    $count_SQL = str_replace('*,','',$count_SQL);
        
                    $rowCount = \DB::connection($this->module->connection)->select($count_SQL)[0]->lastrow;
                   /*
                    $count_sql_arr = explode(" FROM ",$SQL);
                  
                    $count_sql = 'SELECT count('.$this->module->db_table.'.'.$this->module->db_key.') as total_count FROM '.$count_sql_arr[1];
                  
                    $count_sql_arr = explode(" LIMIT ",$count_sql);
                
                    $count_sql = $count_sql_arr[0];
                
                    
                    $rowCount = \DB::connection($this->module->connection)->select($count_sql)[0]->total_count;
                  */
                }
            
       // }else{
        
        
        //$count_SQL = $this->buildSql($request, 'count');
       ////aa($count_SQL);

       // $rowCount = \DB::connection($this->module->connection)->select($count_SQL)[0]->lastrow;
       // }
        ////////aa($rowCount);
   
        ////////aa(count($results));

        // //////aa($results);
        if(!empty($request->return_all_rows)){
        $resultsForPage =  $results;
        }else{
        $resultsForPage = $this->cutResultsToPageSize($request, $results);
        }
    
    
        if (($this->module->serverside_model) || $request->rowTotals == 1) {
            if(!$this->grouping){
               
               
                $pinned_totals = collect($this->info['module_fields'])->where('pinned_row_total',1)->count();
                if(!$pinned_totals){
                    $rowTotals = [];    
                }else{
                    $totals_SQL = $this->buildSql($request, 'totals');
                    //    //aa($totals_SQL);
                    $rowTotals = collect(\DB::connection($this->module->connection)->select($totals_SQL))->first();
                    $rowTotals = (array) $rowTotals;
                   // $rowTotals = [];  
                }
               
            }else{
              
        
            $totals_SQL = str_replace($this->limitSql,'',$SQL);
           
        
            
             $groupedTotals = \DB::connection($this->module->connection)->select($totals_SQL);
                $rowTotals = [];
                $total_fields = [];
                foreach($request->valueCols as $vCol){
                    if($vCol['aggFunc'] == 'sum'){
                        $total_field = $vCol['field'];   
                        $rowTotals[$total_field] = 0;
                        $total_fields[] = $total_field;
                    }    
                }
               
                foreach($groupedTotals as $groupedTotal){
                    foreach($total_fields as $total_field){
                        $rowTotals[$total_field] += $groupedTotal->{$total_field};    
                    }
                }
            }
            
            foreach($rowTotals as $k => $v){
                $rowTotals[$k] = currency($v);    
            }
                    
            $rowTotals = [$rowTotals];
          
            foreach($rowTotals[0] as $k => $v){
               foreach($this->info['module_fields'] as $f){
                   if('join_'.$f['field'] == $k || $f['field'] == $k){
                   
                   if($f['field_type'] == 'select_module' || !$f['pinned_row_total']){
                    
                    //   unset($rowTotals[0][$k]);
                   }
                   }
               }
            }
            /*
            foreach($request->groupKeys as $k => $val){
                foreach($resultsForPage as $i => $arr){
                unset($resultsForPage[$i]->{$request->rowGroupCols[$k]['field']});
                }
                
            }
            */
           
            return ['rows' => $resultsForPage, 'lastRow' => $rowCount, 'rowTotals' => $rowTotals];
        }
           
        return ['rows' => $resultsForPage, 'lastRow' => $rowCount];
       
    }
    

    public function getRowTotals($request)
    {
        ////aa($request->all());
        foreach ($request->all() as $k => $v) {
            //    //////aa($k);
        //    //////aa($v);
        }
        
        $rowGroupCols = $request->rowGroupCols;
        $groupKeys = $request->groupKeys;
        $filterModel = $request->filterModel;
        $fields = $this->info['module_fields'];
        $this->remove_grouping = true;
        //if ($this->isDoingGrouping($rowGroupCols, $groupKeys)) {
            
        //    $SQL = $this->buildSql($request);
       //     $results = \DB::connection($this->module->connection)->select($SQL);
        //    $rowCount = count($results);
           
        //    return $rowCount;
        //}
       

        ////////aa($resultsForPage);
    
            $totals_SQL = $this->buildSql($request, 'totals');
           
            if(!empty($request->return_sql)){
                return $totals_SQL;
            }
        //  //aa($totals_SQL);
            $rowTotals = \DB::connection($this->module->connection)->select($totals_SQL);
            //dddd($rowTotals);
            if(empty($request->layout_tracking) && empty($request->dashboard_tracking)){
            foreach($rowTotals[0] as $k => $v){
               foreach($this->info['module_fields'] as $f){
                   if(('join_'.$f['field'] == $k || $f['field'] == $k) && !$f['pinned_row_total']){
                       unset($rowTotals[0]->{$k});
                   }
               }
            }
            }
            
            
            return  $rowTotals;
        
    }

    public function buildSql($request, $query_type = 'data', $pivotCol = false)
    {
        ////aa('instance: '.session('instance')->id);
        ////aa('user: '.session('user_id'));
        //aa('module id: '.$this->module->id);
        ////aa('module: '.$this->module->name);
        //aa($request->all());
       //aa('$query_type: ',$query_type);
      // aa($request->all());
       //aa('call_trace');
       
        ////aa(generateCallTrace());
     
        $this->buildJoins($request);  
        
        $selectSql = $this->createSelectSql($request, $query_type);
        
       
      //aa($selectSql);
        if ($query_type == 'clientside') {
           //$whereSql = $this->createWhereSql($request);
            if(!empty($request->kanban_sql)){
                $whereSql = $this->createWhereSql($request);
                
                if($this->info['db_where']> '')
                $whereSql .= str_ireplace('where',' and',$this->info['db_where']);   
            }else{
                $whereSql = $this->info['db_where'];
            }
            ////////aa($whereSql);
            if (empty($whereSql)) {
                $whereSql = " WHERE 1=1 ";
            }
          
            if(!empty($this->sql_where_filter)){
                $whereSql .= ' and ('.$this->sql_where_filter.') ';    
            }
          //  aa($whereSql);
            
            $accountFilter = $this->accountFilter();

            $whereSql .= $accountFilter;
            $erpFilter = $this->erpFilter();
            $whereSql .= $erpFilter;
       
            if (!empty($request->search)) {
                $search_query = ' and  (';
                $fields = $this->info['module_fields'];
                $search_fields = [];
                foreach ($fields as $f) {
                    if(!$this->alias_search && ($f['field_type']=='select_module' || $f['aliased_field'] || $f['alias']!=$this->module->db_table)){
                        continue;
                    }
                    if (str_contains($f['field'], 'conf') || $f['field'] == 'query_data' || $f['field'] == 'sql_query' || $f['field'] == 'sql_where'
                    || $f['field'] == 'settings' || $f['field'] == 'calculated_fields') {
                        continue;
                    }
                    if (\Schema::connection($this->module->connection)->hasColumn($f['alias'], $f['field'])) {
                        //$col_type = get_column_type($f['alias'], $f['field'], $this->module->connection);
                        if ($f["field_type"] == 'select_module') {
                            $search_fields[] = 'join_'.$f['field']. ' LIKE "%'.$request->search.'%" ';
                        }else{
                            $search_fields[] = $f['alias'].'.'.$f['field']. ' LIKE "%'.$request->search.'%" ';
                        } 
                    }
        
                }
               

                $search_query .= implode(" || ", $search_fields);
                $search_query .= ') ';

                if (count($search_fields) > 0) {
                    $whereSql .= $search_query;
                }
            }

            if ($request->detail_field) {
                if($request->detail_field == 'product_category_id' && $this->module->id ==508){
                    $whereSql .= ' and crm_products.'.$request->detail_field.' = "'.$request->detail_value.'"';
                }else{
                    $whereSql .= ' and '.$this->table.'.'.$request->detail_field.' = "'.$request->detail_value.'"';
                }
            }
            ////////aa($request->all());
            ////////aa($whereSql);
            $groupBySql = '';
            $orderBySql = '';
            $limitSql = '';
        } elseif ($query_type == "pivot") {
            $whereSql = '';
            $groupBySql = 'group by '.$pivotCol['field'];
            $orderBySql = '';
            $limitSql = '';
        } elseif ($query_type == "totals") {
            $whereSql = $this->createWhereSql($request);

            if($this->info['db_where']> '')
            $whereSql .= str_ireplace('where',' and',$this->info['db_where']);
            if ($request->detail_field) {
                $whereSql .= ' and '.$request->detail_field.' = "'.$request->detail_value.'"';
            }
            $groupBySql = $this->createGroupBySql($request);
            $orderBySql = '';
            $limitSql = '';
        } else {
          
            $whereSql = '';
            $rowGroupCols = $request->rowGroupCols;
            $valueCols = $request->valueCols;
            $groupKeys = $request->groupKeys;
           // if (!$this->isDoingGrouping($rowGroupCols, $groupKeys)) {
            $whereSql = $this->createWhereSql($request);
           // }
           //aa($whereSql);
            if($this->info['db_where']> '')
                $whereSql .= str_ireplace('where',' and',$this->info['db_where']);
            
    ////aa($this->info['db_where']);
   // //aa($whereSql);
            if ($request->detail_field) {
                $whereSql .= ' and '.$request->detail_field.' = "'.$request->detail_value.'"';
            }
            if(!empty($request->workflow_tracking_layout_id)){
               
                $groupBySql = '';
                $orderBySql = '';
                $limitSql = '';
            }else{
                $groupBySql = $this->createGroupBySql($request);
                $orderBySql = $this->createOrderBySql($request);
                $limitSql = $this->createLimitSql($request);
            }
      
        }
   
     // aa($selectSql);
       // aa($orderBySql);
        foreach ($this->info['module_fields'] as $field) {
           
            if ($field['aliased_field'] && $field['virtual_field_expression']) {
              
               //$selectSql = str_replace($field['field'],$field['virtual_field_expression'], $selectSql);
                //$selectSql = str_replace('as '.$field['virtual_field_expression'], 'as '.$field['field'], $selectSql);
               // $whereSql = str_replace($field['field'],$field['virtual_field_expression'], $whereSql);
               // $groupBySql = str_replace($field['field'],$field['virtual_field_expression'], $groupBySql);
              //  $orderBySql = str_replace($this->module->db_table.'.'.$field['field'],$field['virtual_field_expression'], $orderBySql);
                
                //$orderBySql = str_replace($this->db_table.$field['virtual_field_expression'],$field['virtual_field_expression'], $orderBySql);
               // $orderBySql = str_replace($field['field'],$field['virtual_field_expression'], $orderBySql);
            }
        }
        /*
        foreach ($this->info['module_fields'] as $field) {
           
            if ($field['aliased_field'] && $this->module->db_table != $field['alias']) {
            
                $selectSql = str_replace($field['field'],$field['alias'].'.'.$field['field'], $selectSql);
                $selectSql = str_replace('as '.$field['alias'].'.'.$field['field'], 'as '.$field['field'], $selectSql);
                $whereSql = str_replace($field['field'],$field['alias'].'.'.$field['field'], $whereSql);
                $groupBySql = str_replace($field['field'],$field['alias'].'.'.$field['field'], $groupBySql);
                $orderBySql = str_replace($field['field'],$field['alias'].'.'.$field['field'], $orderBySql);
                
            }
        }
        
        $aliases = collect($this->info['module_fields'])->pluck('alias')->unique()->filter()->toArray();
       
        foreach ($aliases as $alias) {
           
           
            
            $selectSql = str_replace($alias.'.'.$alias,$alias, $selectSql);
           
            $whereSql = str_replace($alias.'.'.$alias,$alias, $whereSql);
            $groupBySql = str_replace($alias.'.'.$alias,$alias, $groupBySql);
            $orderBySql = str_replace($alias.'.'.$alias,$alias, $orderBySql);
                
            
        }
        */
        // aa($selectSql);
        if(!str_contains(strtolower($whereSql),'where')){
            $whereSql = ' WHERE '.$whereSql;
        }
        
        if (count($this->joins) > 0) {
          
            $SQL = $this->createJoinSql($query_type, $request, $selectSql, $whereSql, $groupBySql, $orderBySql, $limitSql);
          
            if ($query_type == 'count') {
           
              //  //aa($whereSql);
               // $SQL = "SELECT COUNT(*) AS lastrow from ( " . $SQL . " ) as total_count";
               
                //$SQL = "SELECT COUNT(*) AS lastrow from ( " . $selectSql . ' ' . $whereSql . $groupBySql . " ) as total_count";
            } 
        
        } else {
           
            if ($query_type == 'count') {
           
              //  //aa($whereSql);
                $SQL = "SELECT COUNT(*) AS lastrow from ( " . $selectSql . ' ' . $whereSql . $groupBySql . " ) as total_count";
            } else {
                $SQL = $selectSql . ' ' . $whereSql . $groupBySql . $orderBySql . $limitSql;
            }
            
        }
       
          

        $sql_parts = explode(' ', $SQL);
        $previous_part = '';
        
     
        foreach ($this->info['module_fields'] as $field) {
            foreach ($sql_parts as $i => $sql_part) {
                if ($previous_part != 'as' && ($sql_part == $field['field'] || str_contains($sql_part, '('.$field['field'])) ) {
                    if (empty($field['alias'])) {
                        $field['alias'] = $this->db_table;
                    }
                    if (!$field['aliased_field']) {
                        $sql_parts[$i] = str_replace($field['field'], $field['alias'].'.'.$field['field'], $sql_part);
                    }
                }
                $previous_part = $sql_part;
            }
        }
        
        $SQL = implode(' ', $sql_parts);
        // aa($SQL);
        $rowGroupCols = $request->rowGroupCols;
        $valueCols = $request->valueCols;
        $groupKeys = $request->groupKeys;
        
        if(!empty($rowGroupCols) && is_array($rowGroupCols)){
            $rowGroupColsFields = collect($rowGroupCols)->pluck('field')->filter()->unique()->toArray();
            if($this->use_pg && $this->isDoingGrouping($rowGroupCols, $groupKeys)){
                foreach($request->sortModel as $sort){
                    if(in_array($sort['colId'],$rowGroupColsFields)){
                        foreach ($this->info['module_fields'] as $field) {
                            if($field['field'] == $sort['colId']){
                                $SQL = str_replace($field['alias'].'.'.$field['field'], $field['field'], $SQL);
                            }
                        }
                    }
                }
            }
        }
        
        $SQL = $SQL;

        $SQL = str_ireplace("(select ".$this->table.".id from crm_pricelists", " (select id from crm_pricelists", $SQL);
        $SQL = str_ireplace("(select ".$this->table.".domain_uuid from v_domains", " (select domain_uuid from v_domains", $SQL);
        $SQL = str_ireplace("(select ".$this->table.".pricelist_id from crm_accounts", " (select pricelist_id from crm_accounts", $SQL);
        $SQL = str_ireplace("(select ".$this->table.".id from crm_accounts", " (select id from crm_accounts", $SQL);
        $SQL = str_ireplace(" in ('yes') ", " = 1 ", $SQL);
        $SQL = str_ireplace(" in ('no') ", " = 0 ", $SQL);

        if ($this->table == 'call_records_outbound_lastmonth' && !empty(session('cdr_archive_table'))) {
            $SQL = str_replace('call_records_outbound_lastmonth', session('cdr_archive_table'), $SQL);
            $crl = get_columns_from_schema('call_records_outbound_lastmonth', null, 'pbx_cdr');
            $crlv = get_columns_from_schema(session('cdr_archive_table'), null, 'pbx_cdr');

            usort($crl, function ($a, $b) {
                return strlen($b) <=> strlen($a);
            });
            foreach ($crl as $uc) {
                if (!in_array($uc, $crlv)) {
                    $SQL = str_replace(', '.session('cdr_archive_table').'.'.$uc, '', $SQL);
                    $SQL = str_replace(','.session('cdr_archive_table').'.'.$uc, '', $SQL);
                    $SQL = str_replace(session('cdr_archive_table').'.'.$uc, '', $SQL);
                }
            }
        }
        // //////aa($request->detail_field);
        // //////aa($request->detail_value);
        if ($request->detail_field) {
            //   //////aa($SQL);
        }
        $SQL = str_replace('"',"'",$SQL);
        $SQL = str_replace('||','or',$SQL);
       
     $SQL = str_replace('p_teleshield_routing.p_teleshield_routing.','p_teleshield_routing.',$SQL);
     $SQL = str_replace("'p_teleshield_routing.error'","'teleshield_error'",$SQL);
 
        return $SQL;
    }


    public function createSelectSql($request, $query_type)
    {
        $rowGroupCols = $request->rowGroupCols;
        $valueCols = $request->valueCols;
        $groupKeys = $request->groupKeys;
        
        $sortModel = $request->sortModel;
        //aa($request->all());
      
        
        if(!empty($sortModel)){
            foreach($sortModel as $sort){
                $field_selected = false;
                foreach($valueCols as $v){
                    if($sort['colId'] == $v['field']){
                        $field_selected = true;    
                    }    
                }
                if(!$field_selected){
                    $valueCols[] = [
                      'id' => $sort['colId'],
                      'displayName' => ucwords(str_replace('_',' ',$sort['colId'])), 
                      'field' => $sort['colId'], 
                      'aggFunc' => 'max',
                    ];
                }
            }
        }
        
       // //aa($query_type);
        if ($query_type == 'count' && $this->isDoingGrouping($rowGroupCols, $groupKeys)) {
            if(!empty($this->info['db_sql'])){
                $db_sql = str_replace_last('FROM ', '||', $this->info['db_sql']);
                //aa($db_sql);
                $db_sql_arr = explode('||',$db_sql);
                //aa($db_sql_arr);
              return $db_sql_arr[0].", max('".$this->primary_key."') FROM ".$db_sql_arr[1]; 
            }
            return "SELECT max('".$this->primary_key."') from ".$this->table; 
        }
        if ($query_type == 'totals') {
            
            
            $select_fields = $this->getSelectFields();
            foreach ($valueCols as $key => $value) {
                
                foreach ($select_fields as $i => $field) {
                    if ($field == $value['field'] || $field == $this->table.'.'.$value['field']) {
                      
                        unset($select_fields[$i]);
                    }
                }
                
                foreach ($this->info['module_fields'] as $field) {
                    if($field['field'] == $value['field'] && !empty($field['cell_expression'])){
                        $SQL = str_replace($field['alias'].'.'.$field['field'], $field['field'], $SQL);
                    }
                }
            }
            if (!empty($this->info['sql_function'])) {
                $function = $this->info['sql_function'];
                $select = $function();
            } else {
                $select = $this->info['db_sql'];
            }


            if (empty($select)) {
                if (is_array($select_fields) && count($select_fields) > 0) {
                    $select = 'SELECT '.implode(', ', $select_fields).' FROM '.$this->table;
                } else {
                    $select = 'SELECT '.$this->table.'.* FROM '.$this->table;
                }
            }
          
            $tables = $this->info['module_fields'];
            if (isset($tables[0]['sort_order'])) {
                usort($tables, '\Erp::_sortorder');
            }
////aa($select);
          
            $select = trim(preg_replace('/\s+/', ' ', $select));
            $select = str_replace(' from ', ' FROM ', $select);
            if (str_contains($select, 'union all') || str_contains($select, 'p_rates_summary as p1')) {
                $select = str_replace('"payment"', '"Payment"', $select);
                $select = str_replace(' FROM ', '||', $select);
            } else {
                $select = str_replace_last(' FROM ', '||', $select);
            }
            ////////aa($select);
            $select_arr = explode('||', $select);
            ////aa($select_arr);
            $value_select = '';
            $vCols = [];
        
            if(!empty($request->result_field_agg_func) && !empty($request->result_field)){
                
                $vCols[] =  $request->result_field_agg_func.'('.$this->table.'.'.$request->result_field.') as '.$request->result_field;
            }elseif(!empty($request->result_field)){
               
                $vCols[] =  'SUM('.$this->table.'.'.$request->result_field.') as '.$request->result_field;
            }else{
                foreach ($tables as $i => $grid) {
                    if (!empty($grid['pinned_row_total']) && in_array($grid['field_type'],['decimal','integer','currency']) && empty($grid['virtual_field_expression_aggregate']) && empty($grid['cell_expression'])) {
                     
                        $vCols[] =  'SUM(' . $grid['field'] . ') as ' . $grid['field'];
                    }
                }
            }
       
            if (count($vCols) > 0) {
                $value_select .= ' '.implode(', ', $vCols). ' ';
            }
            $final_select = 'SELECT '.$value_select.' FROM '.$select_arr[1];
            
           
            
            return $final_select;
        }

        if ($query_type != 'clientside' && $this->isDoingGrouping($rowGroupCols, $groupKeys)) {
            //////aa(2222);
            $select_fields = $this->getSelectFields();

           
            foreach ($valueCols as $key => $value) {
                foreach ($select_fields as $i => $field) {
                    if ($field == $value['field'] || $field == $this->table.'.'.$value['field']) {
                        //aa($select_fields[$i]);
                        unset($select_fields[$i]);
                    }
                }
            }
            if (!empty($this->info['sql_function'])) {
                $function = $this->info['sql_function'];
                $select = $function();
            } else {
                $select = $this->info['db_sql'];
            }


            //aa($valueCols);
            //aa($select_fields);
            if (empty($select)) {
                if (is_array($select_fields) && count($select_fields) > 0) {
                    $select = 'SELECT '.implode(', ', $select_fields).' FROM '.$this->table;
                } else {
                    $select = 'SELECT '.$this->table.'.* FROM '.$this->table;
                }
            }
            if ($query_type == "count") {
                return $select;
            }
            //aa($select);
            
            $select = trim(preg_replace('/\s+/', ' ', $select));
            $select = str_replace(' from ', ' FROM ', $select);
            if (str_contains($select, 'union all') || str_contains($select, 'p_rates_summary as p1')) {
                $select = str_replace('"payment"', '"Payment"', $select);
                $select = str_replace(' FROM ', '||', $select);
            } else {
                $select = str_replace_last(' FROM ', '||', $select);
            }
            ////////aa($select);
            $select_arr = explode('||', $select);
            $value_select = '';
            $vCols = [];
            
            if(!empty($request->pivot_col)){
                $vCols[] = $request->pivot_col;
            }
            
            //aa($vCols);
         ////aa($this->aliased_fields);
         ////aa($valueCols);
            foreach($rowGroupCols as $rowGroupCol){
                foreach($this->info['module_fields'] as $mf){
                    
                    if($rowGroupCol['field'] == $mf['field'] && $mf['virtual_field_expression_aggregate'] > ''){
                        $vCols[] = ''.$mf['virtual_field_expression_aggregate'].' as '.$rowGroupCol['field'];
                    }elseif($rowGroupCol['field'] == $mf['field'] && $mf['virtual_field_expression'] > ''){
                        $vCols[] = 'max('.$mf['virtual_field_expression'].') as '.$rowGroupCol['field'];
                    }elseif($rowGroupCol['field'] == $mf['field'] && !$mf['aliased_field']){
                        $vCols[] = 'max('.$rowGroupCol['field'].') as '.$rowGroupCol['field'];
                    }elseif($rowGroupCol['field'] == $mf['field'] && $mf['aliased_field']){
                        $vCols[] = 'max('.$mf['alias'].'.'.$rowGroupCol['field'].') as '.$rowGroupCol['field'];
                    }
                }
            }
            
            //aa($vCols);
            /*
            $sortModel = $request->sortModel;
    
            if ($sortModel) {
                foreach ($sortModel as $key=>$item) {
                foreach($this->info['module_fields'] as $mf){
                        
                            if($item['colId'] == $mf['field'] && !$mf['aliased_field']){
                  
                    $vCols[] = 'max('.$item['colId'].') as '.$item['colId'];
                            }
                }
                }
            }
            */
            foreach ($valueCols as $key => $value) {
                
                    ////aa($value);
                    
                if(!empty($this->aliased_fields) && count($this->aliased_fields) > 0){
                  
                    if(in_array($value['field'],$this->aliased_fields)){
                        foreach($this->info['module_fields'] as $mf){
                        
                        if($value['field'] == $mf['field']){
                            if($mf['virtual_field_expression_aggregate'] > ''){
                                $vCols[] =  '(' . $mf['virtual_field_expression_aggregate'] . ') as ' . $value['field'];
                           
                            }elseif(empty($mf['cell_expression']) && $value['aggFunc'] == 'value' && !$mf['aliased_field']) {
                               
                                $vCols[] =  '(' . $value['field'] . ') as ' . $value['field'];
                            }
                        continue;
                        }
                       
                    }    
                }
                }
                
                 foreach($this->info['module_fields'] as $mf){
                        
                    if(empty($mf['virtual_field_expression_aggregate']) && $value['field'] == $mf['field'] && empty($mf['cell_expression'])){
                       
                        $field_name = $value['field'];
                      
                        if($mf['aliased_field'] && $mf['virtual_field_expression']){
                            $field_name = $mf['virtual_field_expression'];
                        }elseif($mf['aliased_field']){
                            $field_name = $mf['alias'].'.'.$field_name;
                        }
                        if ($value['aggFunc'] == 'value') {
                        
                        $vCols[] =  '(' . $field_name . ') as ' . $value['field'];
                        } else {
                        
                        $vCols[] = $value['aggFunc'] . '(' . $field_name . ') as ' . $value['field'];
                        }
                    }
                 }
            }
          
            if (count($vCols) > 0) {
                $value_select .= implode(', ', $vCols). ' ';
            }
            
         
            $final_select = 'select '.$value_select.' FROM '.$select_arr[1];
          
            return $final_select;
        }

        if (!empty($this->info['sql_function'])) {
            $function = $this->info['sql_function'];
            $select = $function();
        } else {
            $select = $this->info['db_sql'];
        }

        if (empty($select)) {
            $select_fields = $this->getSelectFields();
          
            if (is_array($select_fields) && count($select_fields) > 0) {
                $select = 'SELECT '.implode(', ', $select_fields).' FROM '.$this->table;
            } else {
                $select = 'SELECT '.$this->table.'.* FROM '.$this->table;
            }
        }
        //aa($select);
        return $select;
    }

    public function buildJoins($request)
    {
        //aa('buildJoins '.$this->erp_conn);
         
        $joins = [];
       // aa($this->info['module_fields']);
        //$current_conn = \DB::getDefaultConnection();
        //aa($current_conn);
        $tables = $this->info['module_fields'];
        if (isset($tables[0]['sort_order'])) {
            usort($tables, '\Erp::_sortorder');
        }

        foreach ($tables as $i => $grid) {
            $join_field = [
                'selects' => [],
                'table_join' => '',
                'value' => '',
            ];

            if (!empty($grid['opt_db_table']) && !empty($grid['opt_db_display']) && !empty($grid['opt_db_key']) && 'select_module' == $grid['field_type']) {
                if(!empty($grid['opt_module_id'])){
                    $join_conn = \DB::connection($this->erp_conn)->table('erp_cruds')->where('id', $grid['opt_module_id'])->pluck('connection')->first();
                    if($join_conn != $this->info['connection']){
                      
                       // continue;
                    }
                }
                if (empty($grid['opts_multiple'])) {
                    $join_fields = explode(',', $grid['opt_db_display']);
                    if(!empty($request->layout_tracking)){
                    //    $join_fields = [$join_fields[0]]; 
                    }   
                
                    if (1 == count($join_fields)) {
                        $join_value = 'join'.$i.'.'.$join_fields[0];
                    } elseif (count($join_fields) > 1) {
                        if ($this->module->connection == 'shop') {
                            $concat_select = '(';
                            foreach ($join_fields as $jf) {
                                $concat_select .= 'join'.$i.'.'.$jf.' || " - " || ';
                            }
                            $concat_select = rtrim($concat_select, ' || " - " || ');
                            $concat_select .= ')';
                        } else {
                            $concat_select = 'CONCAT(';
                            foreach ($join_fields as $jf) {
                                $concat_select .= 'ifnull(join'.$i.'.'.$jf.',""), " - ",';
                            }
                            $concat_select = rtrim($concat_select, ', " - ",');
                            $concat_select .= ')';
                        }
                        $join_value = $concat_select;
                    }

                    $join_field['value'] = $join_value;
                    $join_field['selects'][] = 'TRIM('.$join_value .') as join_'.$grid['field'];




                    $join_field['table_join'] = ' LEFT JOIN '.$grid['opt_db_table'].' as join'.$i.' ON '.
                    'join'.$i.'.'.$grid['opt_db_key'].' = '.$grid['alias'].'.'.$grid['field'];

                    if ($grid['opt_db_where']) {
                        $where = $grid['opt_db_where'];
                        if (!empty($where) && !str_contains($where, '{{') && !str_contains($where, '{!!')) {

                            // @TODO replace fieldnames with table aliases
                            $join_cols = get_columns_from_schema($grid['opt_db_table'], $types = null, $this->module->connection);
                            foreach ($join_cols as $join_col) {
                                $where = preg_replace('/\b'.$join_col.'\b/', 'join'.$i.'.'.$join_col, $where);
                            }
                            $join_field['where'] = $where;
                        }
                    }
                    ////////aa('join_'.$grid['field']);
                    $joins['join_'.$grid['field']] = $join_field;
                }
            }
        }

        // aa($joins);
        $this->joins = $joins;
    }

    public function createJoinSql($query_type, $request, $selectSql, $whereSql, $groupBySql, $orderBySql, $limitSql)
    {
        $table_joins = [];
        $join_selects = [];

        ////////aa($this->joins);
        foreach ($this->joins as $join) {
            $table_joins[] = $join['table_join'];
            foreach ($join['selects'] as $select) {
                $join_selects[] = $select;
            }
        }


        $joinSQL = '';
        foreach ($table_joins as $join) {
            $joinSQL .= ' '.$join.' ';
        }

        // $selectSql
       
        $select = $selectSql;
        $select = trim(preg_replace('/\s+/', ' ', $select));
        $select = str_replace(' from ', ' FROM ', $select);
        if (str_contains($select, 'union all') || str_contains($select, 'p_rates_summary as p1')) {
            $select = str_replace('"payment"', '"Payment"', $select);
            $select = str_replace(' FROM ', '||', $select);
        } else {
            $select = str_replace_last(' FROM ', '||', $select);
        }
        $select_arr = explode('||', $select);
      
        $select_join_fields .= ', '.implode(',', $join_selects);

        $selectSql = $select_arr[0].$select_join_fields.' FROM '.$select_arr[1];
        $select_fields = explode(',',$select_arr[0]);
        $aliased_selects = [];
        /*
        aa($select_fields);
        foreach($select_fields as $f){
            if(str_contains($f,' as ')){
                aa($f);
                $farr = explode(' as ',$f);
                aa($farr);
                $whereSql = str_replace($farr[1], $farr[0], $whereSql);
                $orderBySql = str_replace($farr[1], $farr[0], $orderBySql);
                $groupBySql = str_replace($farr[1], $farr[0], $groupBySql);
            }
        }
        */
        ////////aa($selectSql);
        // $whereSql
        foreach ($this->joins as $field_name => $join) {
            if(!str_contains($field_name,'uuid')){
            $whereSql = str_replace($field_name." in ('')", str_replace("join_", "", $field_name)." = 0", $whereSql);
            }
            $whereSql = str_replace($field_name, $join['value'], $whereSql);
        }

        if ($query_type == 'count') {
            $SQL = "SELECT COUNT(*) AS lastrow from ( " . $selectSql . $joinSQL . ' ' . $whereSql . $groupBySql . " ) as total_count";
        } else {
            $SQL = $selectSql . $joinSQL . ' ' . $whereSql . $groupBySql . $orderBySql . $limitSql;
        }
        
        // aa($SQL);
        return $SQL;
    }

    public function createWhereSql($request)
    {
        $rowGroupCols = $request->rowGroupCols;
        $groupKeys = $request->groupKeys;
        $filterModel = $request->filterModel;
        $valueCols = $request->valueCols;


        //aa('createWhereSql');
        //aa($rowGroupCols);
        //aa($groupKeys);
        //aa($filterModel);
        $whereParts = [];
        if(!empty($groupKeys)){
            foreach ($groupKeys as $key => $value) {
                $colName = $rowGroupCols[$key]['field'];
                foreach($this->info['module_fields'] as $mf){
                    if($mf['aliased_field'] && !empty($mf['virtual_field_expression']) && ($colName == $mf['field'] || $colName == 'join_'.$mf['field']) && empty($mf['cell_expression'])){
                        $colName = $mf['virtual_field_expression'];
                    }
                }
                $whereParts[] = $colName . ' = "' . $value . '"';
            }
        }
        if(!empty($filterModel)){
            foreach ($filterModel as $key => $value) {
                ////////aa($key);
                ////////aa($value);
                $item = $filterModel[$key];
            
                //$value = addslashes($value);
                $wherePart = $this->createFilterSql($key, $value);
                
                foreach($this->info['module_fields'] as $mf){
                    if($mf['aliased_field'] && !empty($mf['virtual_field_expression']) && empty($mf['virtual_field_expression_aggregate']) && ($key == $mf['field'] || $key == 'join_'.$mf['field']) && empty($mf['cell_expression'])){
                        $wherePart = str_replace($key,$mf['virtual_field_expression'],$wherePart);
                    }
                }
                
                if ($this->isDoingGrouping($rowGroupCols, $groupKeys)) {
                    
                         
                    $db_field = collect($this->info['module_fields'])->where('field',$key)->first();
                    $processed = false;
                    foreach($valueCols as $vCol){
                        if($vCol['field'] == $key){
                            $processed = true;
                            if($vCol['aggFunc'] != 'count'){
                                if(empty($db_field['virtual_field_expression_aggregate'])){
                                    $whereParts[] = $wherePart;
                                }
                                
                            }
                        }
                    }
                    if(!$processed && empty($db_field['virtual_field_expression_aggregate'])){
                        $whereParts[] = $wherePart;
                    }
                }else{
                ////////aa($wherePart);
                    $whereParts[] = $wherePart;
                }
            }
        }
        

        if (count($whereParts) > 0) {
            $whereSql = " WHERE " . join(' and ', $whereParts);
        } else {
            $whereSql = " WHERE 1=1";
        }
        
      

        if (!empty($request->search)) {
          
            $search_query = ' and  (';
            $fields = $this->info['module_fields'];
            $search_fields = [];
            foreach ($fields as $f) {
                
               
                if(!$this->alias_search && ($f['field_type']=='select_module' || $f['aliased_field'] || $f['alias']!=$this->module->db_table)){
                    continue;
                   
                }
              
                //if(!in_array($f['field_type'],['text','select_custom','select_module','date'])){
                //    continue;    
                //}
                ////aa($f['field'].' '.$f['field_type']);
                if (str_contains($f['field'], 'conf') || $f['field'] == 'query_data' || $f['field'] == 'sql_query' || $f['field'] == 'sql_where'
                || $f['field'] == 'settings' || $f['field'] == 'calculated_fields') {
                 
                    continue;
                }
                
                if (\Schema::connection($this->module->connection)->hasColumn($f['alias'], $f['field']) ||  str_contains($f["virtual_field_expression"],'teleshield')) {
                    //$col_type = get_column_type($f['alias'], $f['field'], $this->module->connection);
                    if ($f["virtual_field_expression"] > "" && str_contains($f["virtual_field_expression"],'teleshield')) {
                        $search_fields[] = 'LOWER('.$f["virtual_field_expression"]. ') LIKE "%'.strtolower($request->search).'%" ';
                    } elseif ($f["field_type"] == 'select_module') {
                        $search_fields[] = 'LOWER(join_'.$f['field']. ') LIKE "%'.strtolower($request->search).'%" ';
                    } else {
                        $search_fields[] = 'LOWER('.$f['alias'].'.'.$f['field'].')  LIKE "%'.strtolower($request->search).'%" ';
                    } 
                }
            }

            $search_query .= implode(" || ", $search_fields);
            $search_query .= ') ';
          
            if (count($search_fields) > 0) {
                $whereSql .= $search_query;
            }
        }

        $accountFilter = $this->accountFilter();

        $whereSql .= $accountFilter;
        $erpFilter = $this->erpFilter();
        ////////aa($erpFilter);
        $whereSql .= $erpFilter;

        return $whereSql;
    }

    private function createLimitSql($request)
    {
        
        
        if (!isset($request->startRow) && !isset($request->endRow)) {
            return '';
        }
        $startRow = $request->startRow;
        $endRow = $request->endRow;
        $pageSize = $endRow - $startRow;
       
        $limitSql = ' LIMIT ' . ($pageSize + 1) . ' OFFSET ' . $startRow;
        $this->limitSql = $limitSql;
        return $limitSql;
    }

    private function createOrderBySql($request)
    {
        $sortParts = [];
        $rowGroupCols = $request->rowGroupCols;
        $groupKeys = $request->groupKeys;
        $sortModel = $request->sortModel;
       // aa($sortModel);
        $valueCols = $request->valueCols;
        if(!empty($sortModel)){
        foreach($sortModel as $sort){
            $field_selected = false;
            foreach($valueCols as $v){
                if($sort['colId'] == $v['field']){
                    $field_selected = true;    
                }    
            }
            if(!$field_selected){
                $valueCols[] = [
                  'id' => $sort['colId'],
                  'displayName' => ucwords(str_replace('_',' ',$sort['colId'])), 
                  'field' => $sort['colId'], 
                  'aggFunc' => 'max',
                ];
            }
        }
        }
        $grouping = $this->isDoingGrouping($rowGroupCols, $groupKeys);
        if($grouping){
            
            
           
            if ($sortModel) {
                foreach ($sortModel as $key=>$item) {
                    $sort_set = false;
                    foreach($valueCols as $value){
                     
                        if($item['colId'] == $value['field']){
                           
                            foreach($this->info['module_fields'] as $mf){
                            
                                if($value['aggFunc'] == 'value'  && $value['field'] == $mf['field'] && $mf['virtual_field_expression'] > ''){
                                   
                                    if($mf['virtual_field_expression'] > ''){
                                       
                                        $sortParts[] = '(' . $mf['virtual_field_expression'] . ') ' . ' ' . $item['sort'];
                                    }else{
                                        $sortParts[] = $value['field'] . ' ' . $item['sort'];
                                    }
                                    $sort_set = true;
                                }elseif($value['field'] == $mf['field'] && $mf['virtual_field_expression_aggregate'] > ''){
                                   
                                    $sortParts[] =  '(' . $mf['virtual_field_expression_aggregate'] . ') ' .  ' ' . $item['sort'];
                                    $sort_set = true;
                                }elseif($value['field'] == $mf['field'] && $mf['virtual_field_expression'] > ''){
                                    
                                    $sortParts[] =  '(' . $mf['virtual_field_expression'] . ') ' .  ' ' . $item['sort'];
                                    $sort_set = true;
                                }elseif($value['field'] == $mf['field']){
                                    if($value['aggFunc'] == 'value'){
                                    $sortParts[] =  '(' . $mf['alias'].'.'.$value['field'] . ')'.  ' ' . $item['sort'];
                                    }else{
                                    $sortParts[] = $value['aggFunc'] . '(' . $mf['alias'].'.'.$value['field'] . ')'.  ' ' . $item['sort'];
                                    }
                                    $sort_set = true;
                                }
                            }
                            
                        }
                    }
                    if(!$sort_set){
                        foreach($this->info['module_fields'] as $mf){
                          
                            if($item['colId'] == $mf['field'] || $item['colId'] == 'join_'.$mf['field']){ 
                         
                                $sortParts[] = $mf['alias'].'.'.$mf['field'] . ' ' . $item['sort'];  
                            }
                        }
                    }
                }
            }
               
        }else{
            $sortParts = [];
            if ($sortModel) {
                foreach ($sortModel as $key=>$item) {  
                               
                    $sortParts[] = $item['colId'] . ' ' . $item['sort'];
                }
            }
        }
      
        if (is_array($sortParts) && count($sortParts) > 0) {
            return ' order by ' . join(', ', $sortParts);
        } else {
            return '';
        }
    }

    private function createGroupBySql($request)
    {
        $rowGroupCols = $request->rowGroupCols;
        $groupKeys = $request->groupKeys;
        $valueCols = $request->valueCols;
      
        $filterModel = $request->filterModel;
        $fields = $this->info['module_fields'];
        //aa($rowGroupCols);
        //aa($groupKeys);
        //aa($valueCols);
        //aa($filterModel); 
        if ($this->isDoingGrouping($rowGroupCols, $groupKeys)) {
            $colsToGroupBy = [];

            $rowGroupCol = $rowGroupCols[count($groupKeys)];
            $colsToGroupBy[] = $rowGroupCol['field'];
            //aa($colsToGroupBy);
            foreach($this->info['module_fields'] as $mf){
                foreach($colsToGroupBy as $i => $c){
                    //aa($mf['field']);
                    //aa($c);
                    if($mf['aliased_field'] && !empty($mf['virtual_field_expression']) && ($c == $mf['field'] || $c == 'join_'.$mf['field']) && empty($mf['cell_expression'])){
                        $colsToGroupBy[$i] = $mf['virtual_field_expression'];
                    }elseif($mf['aliased_field'] && empty($mf['virtual_field_expression_aggregate']) && ($c == $mf['field'] || $c == 'join_'.$mf['field']) && empty($mf['cell_expression'])){
                        $colsToGroupBy[$i] = $mf['alias'].'.'.$mf['field'];
                    }
                }
            }
            
            foreach($colsToGroupBy as $i => $c){
                $colsToGroupBy[$i] = str_replace('join_','',$c);
                
            }
            //aa($colsToGroupBy);
            
            if(!empty($request->pivot_col)){
                $colsToGroupBy[] = $request->pivot_col;
            }
            foreach($colsToGroupBy as $i => $c){
                if(!str_contains($c,'.')){
                    $colsToGroupBy[$i] = $this->table.'.'.$c;
                }
            }
           
            $groupBySql = ' group by ' . join(', ', $colsToGroupBy);
            
            $havingParts = [];
            $havingSql = '';
            if(!empty($filterModel)){
                foreach ($filterModel as $key => $value) {
                    ////////aa($key);
                    ////////aa($value);
                    $item = $filterModel[$key];
                
                    //$value = addslashes($value);
                    $wherePart = $this->createFilterSql($key, $value);
                    if (count($groupKeys) == 0 && $this->isDoingGrouping($rowGroupCols, $groupKeys)) {
                        $db_field = collect($this->info['module_fields'])->where('field',$key)->first();
                        if(!empty($db_field['virtual_field_expression_aggregate'])){
                            $field_aggfunc = '(' . $db_field['virtual_field_expression_aggregate'] . ') ';
                            $havingParts[] = str_replace($key,$field_aggfunc,$wherePart);
                        }else{
                            foreach($valueCols as $vCol){
                                if($vCol['field'] == $key && $vCol['aggFunc']!='value'){
                                    $havingParts[] = str_replace($key,$vCol['aggFunc'].'('.$key.')',$wherePart);
                                }
                            }
                            //$havingParts[] = $wherePart;
                        }
                    }
                }
            }
            if (count($havingParts) > 0) {
                $havingSql = " HAVING " . join(' and ', $havingParts);
            } else {
                $havingSql = "";
            }
            return $groupBySql. ' ' .$havingSql;
        } else {
            // select all columns
            return '';
        }
    }

    private function createFilterSql($key, $item)
    {
      
        switch ($item['filterType']) {
            case 'text':
                if (isset($item['type']) and $item['type'] == 'domainsFilter') {
                    return $this->createDomainsFilterSql($key, $item['filter']);
                } else {
                    
                    if ($item['type'] === 'blank') {
                       
                        return $this->createBlankFilterSql($key);
                    } elseif ($item['type'] === 'notBlank') {
                        return $this->createNotBlankFilterSql($key);
                    }elseif ($item['filter'] === 'isnull') {
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
                //logger('unkonwn filter type: ' . $item['filterType']);
                return true;
        }
    }

    public function createDomainsFilterSql($key, $item)
    {
        $domains = array_map('trim', explode(',', $item));
        return $key .' in ('."'" . implode("', '", $domains) . "'".')';
    }

    public function createBlankFilterSql($key)
    {
        return '('.$key . ' is NULL or '.$key . ' ="") ';
    }

    public function createNotBlankFilterSql($key)
    {
        return $key . ' > ""';
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
        if ($key == 'join_module_id' && !empty($item['values']) && count($item['values']) > 0) {
            $l = [];
            foreach ($item['values'] as $v) {
                $l[] = str_replace(' ', '_', $v);
            }
            $item['values'] = $l;
        }

        $list = implode("', '", array_map('addslashes', $item['values']));
        $has_blank = false;
        foreach( $item['values'] as $v){
            if(empty($v)){
                $has_blank = true;
            }
        }
        $where = $key .' in ('."'" .$list . "'".')';
        if($has_blank){
            $where = '('.$where.' or '.$key.' is null)'; 
        }
        return $where;
    }

    private function createDateFilterSql($key, $item)
    {
        $item['dateFrom'] = str_replace('/','-',$item['dateFrom']);
        $item['dateTo'] = str_replace('/','-',$item['dateTo']);
        if(str_ends_with($item['dateFrom'],':00')){
            $item['dateFrom'] = substr($item['dateFrom'],0,-3);
        }
       
        if($this->use_pg){
            $curdate_fn = 'CURRENT_DATE';
            $previousday_fn = '(current_date - INTERVAL "1 day")';
            switch ($item['type']) {
                case 'equals':
                    return  'to_char('.$key.', "YYYY-MM-DD HH24:MI") LIKE "' . date('Y-m-d H:i', strtotime($item['dateFrom'])) . '%"';
                case 'notEqual':
                    return  'to_char('.$key.', "YYYY-MM-DD HH24:MI") NOT LIKE "' . date('Y-m-d H:i', strtotime($item['dateFrom'])) . '%"';
                case 'greaterThan':
                    return $key . ' > "' . date('Y-m-d H:i', strtotime($item['dateFrom'])) . '"';
                case 'lessThan':
                    return $key . ' < "' . date('Y-m-d H:i', strtotime($item['dateFrom'])) . '"';
                case 'inRange':
                    $toDate= $item['dateTo'];
                    $fromDate = $item['dateFrom'];
                    return " ( $key >= Date('$fromDate') AND $key <= Date('$toDate') ) ";
                    break;
                case 'notInRange':
                    $toDate= $item['dateTo'];
                    $fromDate = $item['dateFrom'];
                    return " ( $key < Date('$fromDate') or $key > Date('$toDate') ) ";
                    break;
                case 'notCurrentMonth':
                    return " (".$key." < date_trunc('month', current_date) )";
                    break;
                case 'currentMonth':
                    return " (".$key." >= date_trunc('month', current_date) )";
                    break;
                case 'currentMonth':
                    return " (".$key." >= date_trunc('month', current_date) )";
                    break;
                case 'currentMonthLastYear':
                    return " (DATE_FORMAT(".$key.", '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y-%m'))";
                    break;
                case 'currentMonthLastThreeYears':
                    return "  DATE_FORMAT(".$key.", '%Y-%m') BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 YEAR), '%Y-%m') AND DATE_FORMAT(NOW(), '%Y-%m')";
                    break;
                case 'lastMonth':
                    return " (".$key." >= date_trunc('month', current_date - interval '1' month) AND ".$key." < date_trunc('month', current_date)) ";
                    break;
                case 'currentWeek':
                    return  " extract('week' from ".$key.") = extract('week' from current_date)";
                    break;
                case 'currentDay':
                    return " DATE(".$key.") = ".$curdate_fn." ";
                    break;
                case 'notCurrentDay':
                    return " DATE(".$key.") != ".$curdate_fn." ";
                    break;
                case 'previousDay':
                    return " DATE(".$key.") = ".$previousday_fn." ";
                    break;
                case 'previousWeekDay':
                    return " DATE(".$key.") = (CASE WEEKDAY(CURRENT_DATE) 
                             WHEN 0 THEN SUBDATE(CURRENT_DATE,3)
                             WHEN 6 THEN SUBDATE(CURRENT_DATE,2) 
                             WHEN 5 THEN SUBDATE(CURRENT_DATE,1)
                             ELSE SUBDATE(CURRENT_DATE,1) 
                        END) ";
                    break;
                case 'lastThreeDays':
                    return $key." >= ( ".$curdate_fn." - INTERVAL '3 DAY') ";
                    break;
                case 'lastSevenDays':
                    return $key." >= ( ".$curdate_fn." - INTERVAL '7 DAY') ";
                    break;
                case 'lastThirtyDays':
                    return $key." >= ( ".$curdate_fn." - INTERVAL '30 DAY') ";
                    break;
                case 'lessEqualToday':
                    return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." <= ( ".$curdate_fn.") )";
                    break;
                case 'greaterEqualToday':
                    return $key." >= ( ".$curdate_fn.") ";
                    break;
                    
                case 'lessEqualNextMonth':
                    return $key." <= '".date('Y-m-d',strtotime('last day of next month'))."' ";
                    break;
                case 'notlastThreeDays':
                    return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL '3 DAY')) ";
                    break;
                case 'notlastSevenDays':
                    return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL '7 DAY')) ";
                    break;
                case 'notlastThirtyDays':
                    return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL '30 DAY')) ";
                    break;
                case 'notlastThirtyFiveDays':
                    return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL '32 DAY')) ";
                    break;
                case 'notlastSixtyDays':
                    return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL '60 DAY')) ";
                    break;
                case 'lastThreeMonths':
                    return ' '.$key." >= ( ".$curdate_fn." - INTERVAL 3 MONTH) ";
                    break;
                case 'lastSixMonths':
                    return ' '.$key." >= ( ".$curdate_fn." - INTERVAL 6 MONTH) ";
                    break;
                case 'lastTwelveMonths':
                    return ' '.$key." >= ( ".$curdate_fn." - INTERVAL 12 MONTH) ";
                    break;
                default:
                    //logger('unknown text filter type: ' . $item['dateFrom']);
                    return 'true';
            }
        }else{
            $curdate_fn = 'CURDATE()';
            $previousday_fn = 'SUBDATE(CURDATE(),1)';
            $notCurrentMonthDate = date('Y-m-d',strtotime('last day of last month'));
            switch ($item['type']) {
                    case 'blank':
                        return $key . ' is null';
                    case 'notBlank':
                        return $key . ' is not null';
                    case 'equals':
                        return $key . ' LIKE "' . date('Y-m-d H:i', strtotime($item['dateFrom'])) . '%"';
                    case 'contains':
                        return $key . ' LIKE "%' . date('Y-m', strtotime($item['dateFrom'])) . '%"';
                    case 'notEqual':
                        return $key . ' NOT LIKE "' . date('Y-m-d', strtotime($item['dateFrom'])) . '%"';
                    case 'greaterThan':
                        return $key . ' > "' . date('Y-m-d', strtotime($item['dateFrom'])) . '"';
                    case 'lessThan':
                        return $key . ' < "' . date('Y-m-d', strtotime($item['dateFrom'])) . '"';
                    case 'inRange':
                        $toDate= $item['dateTo'];
                        $fromDate = $item['dateFrom'];
                        return " ( $key >= Date('$fromDate') AND $key <= Date('$toDate') ) ";
                        break;
                    case 'notInRange':
                        $toDate= $item['dateTo'];
                        $fromDate = $item['dateFrom'];
                        return " ( $key < Date('$fromDate') or $key > Date('$toDate') ) ";
                        break;
                    case 'notCurrentMonth':
                        return " (DATE(".$key.") <  '".$notCurrentMonthDate."') ";
                        break;
                    case 'currentMonth':
                        return " ( YEAR(" . $key . ") = YEAR(NOW()) AND MONTH(" . $key . ") = MONTH(NOW()))";
                        break;
                    case 'currentMonthLastYear':
                        return " (DATE_FORMAT(".$key.", '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y-%m'))";
                        break;
                    case 'currentMonthLastThreeYears':
                        return "  DATE_FORMAT(".$key.", '%Y-%m') BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 YEAR), '%Y-%m') AND DATE_FORMAT(NOW(), '%Y-%m')";
                        break;
                    case 'currentYear':
                        return " (YEAR(" . $key . ") = YEAR(NOW())) ";
                        break;
                    case 'lastMonth':
                        return " (DATE(".$key.") >= DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y/%m/01' ) AND DATE(".$key.") < DATE_FORMAT( CURRENT_DATE, '%Y/%m/01' )) ";
                        break;
                    case 'currentWeek':
                        return  " YEARWEEK(".$key.") = YEARWEEK(NOW())";
                        break;
                    case 'currentDay':
                        return " DATE(".$key.") = ".$curdate_fn." ";
                        break;
                    case 'notCurrentDay':
                        return " DATE(".$key.") != ".$curdate_fn." ";
                        break;
                    case 'previousDay':
                        return " DATE(".$key.") = ".$previousday_fn." ";
                        break;
                    case 'previousWeekDay':
                    return " DATE(".$key.") = (CASE WEEKDAY(CURRENT_DATE) 
                             WHEN 0 THEN SUBDATE(CURRENT_DATE,3)
                             WHEN 6 THEN SUBDATE(CURRENT_DATE,2) 
                             WHEN 5 THEN SUBDATE(CURRENT_DATE,1)
                             ELSE SUBDATE(CURRENT_DATE,1) 
                        END) ";
                    break;
                    case 'lastThreeDays':
                        return $key." >= ( ".$curdate_fn." - INTERVAL 3 DAY) ";
                        break;
                    case 'lastSevenDays':
                        return $key." >= ( ".$curdate_fn." - INTERVAL 7 DAY) ";
                        break;
                    case 'lastThirtyDays':
                        return $key." >= ( ".$curdate_fn." - INTERVAL 30 DAY) ";
                        break;
                    case 'lessEqualToday':
                        return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." <= ( ".$curdate_fn.") )";
                        break;
                    case 'lessEqualNextMonth':
                        return $key." <= '".date('Y-m-d',strtotime('last day of next month'))."' ";
                    break;
                    case 'greaterEqualToday':
                        return $key." >= ( ".$curdate_fn.") ";
                        break;
                    case 'notlastThreeDays':
                        return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL 3 DAY)) ";
                        break;
                    case 'notlastSevenDays':
                        return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL 7 DAY)) ";
                        break;
                    case 'notlastThirtyDays':
                        return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL 30 DAY)) ";
                        break;
                    case 'notlastThirtyFiveDays':
                        return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL 35 DAY)) ";
                        break;
                    case 'notlastSixtyDays':
                        return '(('.$key . ' is NULL or '.$key . ' ="") or '.$key." < ( ".$curdate_fn." - INTERVAL 60 DAY)) ";
                        break;
                    case 'lastThreeMonths':
                        return ' '.$key." >= ( ".$curdate_fn." - INTERVAL 3 MONTH) ";
                        break;
                    case 'lastSixMonths':
                        return ' '.$key." >= ( ".$curdate_fn." - INTERVAL 6 MONTH) ";
                        break;
                    case 'lastTwelveMonths':
                        return ' '.$key." >= ( ".$curdate_fn." - INTERVAL 12 MONTH) ";
                        break;
                    default:
                        //logger('unknown text filter type: ' . $item['dateFrom']);
                        return 'true';
                }
        }
        
     
    }

    private function createTextFilterSql($key, $item)
    {
        $item['filter'] = strtolower($item['filter']);
        switch ($item['type']) {
            case 'equals':
                return 'LOWER('.$key . ') = "' . $item['filter'] . '"';
            case 'notEqual':
                return 'LOWER('.$key . ') != "' . $item['filter'] . '"';
            case 'contains':
                return 'LOWER('.$key . ') like "%' . $item['filter'] . '%"';
            case 'notContains':
                return 'LOWER('.$key . ') not like "%' . $item['filter'] . '%"';
            case 'startsWith':
                return 'LOWER('.$key . ') like "' . $item['filter'] . '%"';
            case 'endsWith':
                return 'LOWER('.$key . ') like "%' . $item['filter'] . '"';
            default:
                //logger('unknown text filter type: ' . $item['type']);
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
            case 'notInRange':
                return '(' . $key . ' < ' . $item['filter'] . ' or ' . $key . ' > ' . $item['filterTo'] . ')';
            default:
                //logger('unknown number filter type: ' . $item['type']);
                return 'true';
        }
    }

    private function isDoingGrouping($rowGroupCols, $groupKeys)
    {
        if($this->remove_grouping){
            return false;
        }
        if(empty($rowGroupCols)){
            $rowGroupCols = [];
        }
        if(empty($groupKeys)){
            $groupKeys = [];
        }
        // we are not doing grouping if at the lowest level. we are at the lowest level
        // if we are grouping by more columns than we have keys for (that means the user
        // has not expanded a lowest level group, OR we are not grouping at all).
        $grouping = count($rowGroupCols) > count($groupKeys);
        $this->grouping = $grouping;
        return $grouping;
    }

    private function cutResultsToPageSize($request, $results)
    {
        $pageSize = $request['endRow'] - $request['startRow'];

        if ($results && (sizeof($results) > $pageSize)) {
            return array_splice($results, 0, $pageSize);
        } else {
            return $results;
        }
    }

    /*aggrid query*/

    public function getRows($args, $return_sql = false)
    {
      

        extract($args);
        $table = $this->table;
        $key = $this->primary_key;

        $table = $this->table;

        if (!empty($this->info['sql_function'])) {
            $function = $this->info['sql_function'];
            $select = $function();
        } else {
            $select = $this->info['db_sql'];
        }

        if (empty($select)) {
            $select_fields = $this->getSelectFields();
            if (is_array($select_fields) && count($select_fields) > 0) {
                $select = 'SELECT '.implode(',', $select_fields).' FROM '.$this->table;
            } else {
                $select = 'SELECT '.$this->table.'.* FROM '.$this->table;
            }
        }

        $joins = $this->queryJoins();

        if (empty($fstart)) {
            $fstart = 0;
        }
        if ($flimit && 'All' != $flimit) {
            $limitConditional = "LIMIT $flimit OFFSET $fstart ";
        }

        $rows = array();

        $where = (!empty($this->info['db_where'])) ? $this->info['db_where'] : ' WHERE 1=1 ';

        // level access

        $where .= $this->accountFilter();
        $where .= $this->erpFilter();
        $filter_preview = false;
        if (!empty($gridfilter)) {
            foreach ($gridfilter as $filter) {
                if (empty($filter['condition'])) {
                    $filter_preview = $filter['field'];
                }
                $where .= $this->queryWhereAjax($filter);
            }
        }

        $hidden_filters = $this->getHiddenGridFilters($gridfilter);
        if (!empty($hidden_filters)) {
            foreach ($hidden_filters as $filter) {
                $where .= $this->queryWhereAjax($filter);
            }
        }

        if (!empty($search)) {
            $where .= $this->querySearch($search);
        }

        $orderConditional = '';
        $tables = $this->info['module_fields'];
        $table_list = get_tables_from_schema($this->module->connection);

        if (isset($tables[0]['sort_order'])) {
            usort($tables, '\Erp::_sortorder');
        }

        if (!empty($grid_layout_id)) {
            $grid_view = \DB::connection($this->erp_conn)->table('erp_grid_views')->where('id', $grid_layout_id)->get()->first();
            if (!empty($grid_view->settings)) {
                $settings = json_decode($grid_view->settings);

                if (!empty($settings->persistData)) {
                    $settings = $settings->persistData;
                    $settings = json_decode($settings);
                    if (!empty($settings->columns)) {
                        $reordered_fields = [];
                        foreach ($settings->columns as $i => $col) {
                            foreach ($tables as $i => $grid) {
                                if ($grid['field'] == $col->field) {
                                    $reordered_fields[] = $grid;
                                }
                            }
                        }
                        $tables = $reordered_fields;
                    }
                }
            }
        }


        $fields_sorted = [];
        foreach ($tables as $i => $grid) {
            if (!empty($gridsort)) {
                foreach ($gridsort as $sort) {
                    $direction = ('descending' == $sort['direction']) ? 'desc' : 'asc';
                    if ('select_module' == $grid['field_type'] && $grid['field'] == $sort['name'] && !empty($grid['opt_db_table'])) {
                        $orderby_fields = explode(',', $grid['opt_db_sortorder']);

                        if (isset($orderby_fields[0]) && '' == $orderby_fields[0]) {
                            $orderby_fields = null;
                        }

                        if (!empty($orderby_fields)) {
                            foreach ($orderby_fields as $orderby_join) {
                                if (!empty($this->join_order_fields[$orderby_join])) {
                                    $fields_sorted[] = $grid['field'];
                                    $orderConditional .= $this->join_order_fields[$orderby_join].' '.$direction.', ';
                                }
                            }
                        } else {
                            $fields_sorted[] = $grid['field'];
                            $orderConditional .= 'join_'.$sort['name'].' '.$direction.', ';
                        }
                    } elseif ($grid['field'] == $sort['name']) {
                        $fields_sorted[] = $grid['field'];
                        $alias = (!empty($grid['alias'])) ? $grid['alias'] : $this->table;
                        if (in_array($alias, $table_list)) {
                            $alias_field = $alias.'.'.$grid['field'];
                        } else {
                            $alias_field = $alias;
                        }
                        if ('allocated' == $grid['field'] || 'available' == $grid['field']) {
                            $orderConditional .= $grid['field'].' '.$direction.', ';
                        } else {
                            $orderConditional .= $alias_field.' '.$direction.', ';
                        }
                    }
                }
            }
        }



        if ($orderConditional > '') {
            if ($this->table == 'sub_services' && (empty($gridsort) || count($gridsort) > 1)) {
                $orderConditional = 'ORDER BY '.$orderConditional;
            } else {
                $orderConditional = 'ORDER BY '.$orderConditional;
            }
            $orderConditional = rtrim($orderConditional, ', ');
        }

        if ($this->table == 'v_domains' && !empty(session('debug_domain'))) {
            $orderConditional = 'ORDER BY v_domains.domain_debug desc,'.str_replace('ORDER BY ', '', $orderConditional);
        }

        $columns = $this->getTableFields();

        if (empty($orderConditional) && in_array('sort_order', $columns)) {
            $orderConditional = 'ORDER BY sort_order';
        }

        if (!empty($joins) && !empty($joins['join_selects'])) {
            $select = $select;
            $select = trim(preg_replace('/\s+/', ' ', $select));
            $select = str_replace(' from ', ' FROM ', $select);
            if (str_contains($select, 'union all') || str_contains($select, 'p_rates_summary as p1')) {
                $select = str_replace('"payment"', '"Payment"', $select);
                $select = str_replace(' FROM ', '||', $select);
            } else {
                $select = str_replace_last(' FROM ', '||', $select);
            }
            $select_arr = explode('||', $select);

            $select_join_fields .= ', '.implode(',', $joins['join_selects']);

            $select = $select_arr[0].$select_join_fields.' FROM '.$select_arr[1];
        }

        if (!empty($joins) && !empty($joins['joins'])) {
            foreach ($joins['joins'] as $join) {
                $join_query .= ' '.$join.' ';
            }
        }

        $where = rtrim($where, 'and ');
        $groupby = '';
        if (!empty($groupby_field)) {
            $filter_preview = true;
            $groupby .= ' group by '.$groupby_field.' ';
        }

        if (!empty($is_export) && str_contains($select, 'call_records') && $flimit == 10) {
            //  $where .= ' and '.$this->table.'.duration > 0 ';


            $limitConditional = "LIMIT 100000 OFFSET 0 ";
        } elseif ($is_export) {
            $limitConditional = "";
        }



        if ($filter_preview) {
            $query = $select.$join_query.' '.$where." {$params} $groupby";
        } else {
            $query = $select.$join_query.' '.$where." {$params} {$orderConditional}  {$limitConditional} ";
        }

        $count_query = $select.$join_query.' '.$where." {$params} ";
        $count_query = str_replace('p_ratesheet_compiled.', '', $count_query);
        $query = str_replace('p_ratesheet_compiled.', '', $query);

        if ($this->table == 'call_records_outbound_lastmonth' && !empty(session('cdr_archive_table'))) {
            $count_query = str_replace('call_records_outbound_lastmonth', session('cdr_archive_table'), $count_query);
            $query = str_replace('call_records_outbound_lastmonth', session('cdr_archive_table'), $query);
            $crl = get_columns_from_schema('call_records_outbound_lastmonth', null, 'pbx_cdr');
            $crlv = get_columns_from_schema(session('cdr_archive_table'), null, 'pbx_cdr');

            usort($crl, function ($a, $b) {
                return strlen($b) <=> strlen($a);
            });
            foreach ($crl as $uc) {
                if (!in_array($uc, $crlv)) {
                    $count_query = str_replace(','.session('cdr_archive_table').'.'.$uc, '', $count_query);
                    $query = str_replace(','.session('cdr_archive_table').'.'.$uc, '', $query);
                }
            }
        }

        if ($return_sql) {
            return $query;
        }

        //////////aa($query);

        $result = \DB::select($query);

        $count = \DB::select('SELECT COUNT(*) AS total from ('.$count_query.') as total_count');

        if (!empty($count[0]->total)) {
            $total = $count[0]->total;
        } else {
            $total = 0;
        }

        return $results = array('rows' => $result, 'total' => $total, 'query' => $query);
    }

    public function querySearch($search)
    {
        $where = '';
        $fields = $this->info['module_fields'];
      
        foreach ($search as $search_filter) {
            $search_filter = (array) $search_filter;
            $predicates = [];
            foreach ($search_filter['fields'] as $search_filter_field) {
                foreach ($fields as $f) {
                    if ($f['field'] == $search_filter_field) {
                        if (\Schema::connection($this->module->connection)->hasColumn($f['alias'], $f['field'])) {
                            $col_type = get_column_type($f['alias'], $f['field'], $this->module->connection);
                                       
                            if ($col_type == 'text' || $f["field_type"] == "date") {
                                $predicates[] = [
                                    'field' => $search_filter_field,
                                    'operator' => $search_filter['operator'],
                                    'value' => $search_filter['key'],
                                ];
                            }
                        }
                    }
                }
            }

            $filter = [
                'isComplex' => true,
                'condition' => 'or',
                'predicates' => $predicates
            ];

            $where .= $this->queryWhereAjax($filter);
        }

        return $where;
    }

    public function getSelectFields()
    {
        $select_fields = [];
        $fields = $this->info['module_fields'];

        foreach ($fields as $i => $grid) {
            if ($grid['field_type'] != 'none') {
                $access = $this->gridAccess();

                if ($access) {
                    if($grid['virtual_field_expression'] > '' && $grid['aliased_field']){
                       
                        $select_fields[] = $grid['virtual_field_expression'].' as '.$grid['field'];
                    }elseif(empty($grid['cell_expression'])){
                        $select_fields[] = $grid['alias'].'.'.$grid['field'];
                    }
                }
            }
        }
        $id_field = $this->table.'.id';
        $db_columns = $this->getTableFields();
        if (in_array('id', $db_columns) && !in_array($id_field, $select_fields)) {
            $select_fields[] = $id_field;
        }
      
        return $select_fields;
    }


    public function queryWhereAjax($filter, $condition = null)
    {
        if (!empty(request()->query_params)) {
            $query_params = request()->query_params;
        }
        if (!empty(request()->query_values)) {
            $query_values = request()->query_values;
        }

        $filter = (array) $filter;
        $request_filter = $filter;
        $current_params = url()->previous();
        $table_list = get_tables_from_schema($this->module->connection);

        $where = '';
        $wheres = [];

        if ($filter['isComplex'] && !empty($filter['predicates']) && is_array($filter['predicates'])) {
            foreach ($filter['predicates'] as $subfilter) {
                $wheres[] = $this->queryWhereAjax($subfilter, $filter['condition']);
            }

            if ('and' == $filter['condition']) {
                $where = '('.implode(' and ', $wheres).')';
            }
            if ('or' == $filter['condition']) {
                $where = '('.implode(' or ', $wheres).')';
            }
        }

        $tables = $this->info['module_fields'];
        if (isset($tables[0]['sort_order'])) {
            usort($tables, '\Erp::_sortorder');
        }

        if (str_contains($filter['field'], '_id') && empty($query_values)) {
            // $filter['operator'] = 'contains';
        }

        foreach ($tables as $i => $grid) {
            if ($grid['field'] == $filter['field'] && 'boolean' == $grid['field_type']) {
                $filter['operator'] = 'equal';
                $filter['value'] = ('Yes' == $filter['value'] || 'yes' == $filter['value']) ? 1 : 0;
                if ('pbx' == $this->info['connection'] && str_starts_with($this->table, 'v_')) {
                    if ($filter['value'] == 1) {
                        $filter['value'] = 'true';
                    } else {
                        $filter['value'] = 'false';
                    }
                }
            }

            if (!empty($grid['opt_db_table']) && $grid['field'] == $filter['field']) {
                $join_fields = explode(',', $grid['opt_db_display']);
                if(!empty(request()->layout_tracking)){
                $join_fields = [$join_fields[0]]; 
                }  
                $concat_fields = [];

                foreach ($join_fields as $index => $join_field) {
                    // filter on id not on search value

                    if ($filter['value'] == $query_params[$grid['field']]
                    && !empty($query_params) && is_numeric($query_params[$grid['field']])
                    && $grid['field'] == $filter['field'] && str_ends_with($filter['field'], '_id')) {
                        $filter['field'] = 'join'.$i.'.'.$grid['opt_db_key'];

                        if ($filter['operator'] == 'notequal') {
                            $filter['operator'] = 'notequal';
                        } else {
                            $filter['operator'] = 'equal';
                        }

                        if (!empty($query_params[$grid['field']])) {
                            $filter['value'] = $query_params[$grid['field']];
                        }
                        break;
                    } else {
                        if ($filter['operator'] == 'notequal') {
                            $filter['operator'] = 'notcontains';
                        } elseif ($filter['operator'] != 'greaterthan' && $filter['operator'] != 'notcontains' && empty($query_params)) {
                            $filter['operator'] = 'contains';
                        }
                        $concat_fields[] = 'join'.$i.'.'.$join_field;
                    }
                }

                if (1 == count($concat_fields)) {
                    $filter['field'] = $concat_fields[0];
                    if (!empty($query_values[$grid['field']])) {
                        $filter['value'] = $query_values[$grid['field']];
                    }
                } elseif (count($concat_fields) > 1) {
                    if ($this->module->connection == 'shop') {
                        $filter['field'] = '';
                        foreach ($concat_fields as $concat_field) {
                            $filter['field'] .= $concat_field.' || " - " || ';
                        }
                        $filter['field'] = rtrim($filter['field'], ' || " - " || ');
                    } else {
                        $filter['field'] = 'CONCAT(';
                        foreach ($concat_fields as $concat_field) {
                            $filter['field'] .= $concat_field.', " - ",';
                        }
                        $filter['field'] = rtrim($filter['field'], ', " - ",').')';
                    }
                    if (!empty($query_values[$grid['field']])) {
                        $filter['value'] = $query_values[$grid['field']];
                    }
                }
            } elseif ($grid['field'] == $filter['field']) {
                $alias = (!empty($grid['alias'])) ? $grid['alias'] : $this->table;
                if (in_array($alias, $table_list)) {
                    $alias_field = $alias.'.'.$grid['field'];
                } else {
                    $alias_field = $alias;
                }
                $filter['field'] = $alias_field;
            }
        }
        $filter['value'] = addslashes($filter['value']);

        if (false === $filter['isComplex'] || empty($filter['isComplex'])) {
            if ('equal' == $filter['operator']) {
                if ((str_contains($filter['field'], '.id') || str_contains($filter['field'], '_id')) && str_contains($filter['value'], ',')) {
                    //////aa($grid['field']);
                    $where .= $filter['field']." IN (".$filter['value'].") ";
                } elseif (empty($filter['value']) || $filter['value'] == 'null' || $filter['value'] == null) {
                    $where .= "(".$filter['field']." = '' or ".$filter['field']." is null )";
                } else {
                    $where .= $filter['field']." = '".$filter['value']."' ";
                }
            }

            if ('notequal' == $filter['operator']) {
                if (str_contains($filter['value'], '%')) {
                    $where .= $filter['field']." NOT LIKE '".$filter['value']."' ";
                } else {
                    $where .= $filter['field']." != '".$filter['value']."' ";
                }
            }
            if ('startswith' == $filter['operator']) {
                $where .= $filter['field']." LIKE '".$filter['value']."%' ";
            }
            if ('endswith' == $filter['operator']) {
                $where .= $filter['field']." LIKE '%".$filter['value']."' ";
            }
            if ('contains' == $filter['operator']) {
                if (empty($filter['value']) || $filter['value'] == 'null' || $filter['value'] == null) {
                    $where .= "(".$filter['field']." = '' or ".$filter['field']." is null )";
                } else {
                    if (str_contains($filter['field'], 'join') && $filter['field'] != $request_filter['field'] && empty($filter['value'])) {
                        $where .= $this->table.'.'.$request_filter['field']." is null ";
                    }
                    if ($request_filter['field'] == 'account_id' && empty($filter['value'])) {
                        $where =  $this->table.'.'.$request_filter['field']."=0 ";
                    } elseif ($filter['field'] != $request_filter['field'] && empty($filter['value'])) {
                        $where = " 1=1 ";
                    } else {
                        $where .= $filter['field']." LIKE '%".$filter['value']."%' ";
                    }
                }
            }

            if ('notcontains' == $filter['operator']) {
                if ($filter['field'] == 'join3.code') {
                    $where .= $filter['field']." NOT LIKE '%prepaid%' ";
                } else {
                    $where .= $filter['field']." NOT LIKE '%".$filter['value']."%' ";
                }
            }

            if ('lessthan' == $filter['operator']) {
                $where .= $filter['field']." < '".$filter['value']."' ";
            }

            if ('greaterthan' == $filter['operator']) {
                $where .= $filter['field']." > '".$filter['value']."' ";
            }

            if ('lessthanorequal' == $filter['operator']) {
                $where .= $filter['field']." <= '".$filter['value']."' ";
            }

            if ('greaterthanorequal' == $filter['operator']) {
                $where .= $filter['field']." >= '".$filter['value']."' ";
            }
        }

        if ('default' != $this->module->connection) {
            if (str_contains($filter['field'], 'parnter_id') || str_contains($filter['field'], 'account_id')) {
                $accounts_where = str_replace($filter['field'], 'company', $where);

                $account_ids = \DB::connection($this->erp_conn)->table('crm_accounts')->whereRaw($accounts_where)->pluck('id')->toArray();

                $where = '';
                if (!empty($account_ids) && is_array($account_ids)) {
                    if (0 == count($account_ids)) {
                        $where = '';
                    }
                    if (1 == count($account_ids)) {
                        $where = $filter['field'].'='.$account_ids[0];
                    }
                    if (count($account_ids) > 1) {
                        $where = $filter['field'].' in ('.implode(',', $account_ids).')';
                    }
                }
            }
        }

        if (null === $condition) {
            return ' and '.$where;
        } else {
            return $where;
        }
    }

    public function queryJoins()
    {
        
        $joins = [];
        $join_selects = [];

        $tables = $this->info['module_fields'];
        if (isset($tables[0]['sort_order'])) {
            usort($tables, '\Erp::_sortorder');
        }
        $join_order_fields = [];
        foreach ($tables as $i => $grid) {
            if ('sub_services' == $this->data['db_table'] && 'partner_id' == $grid['field']) {
                continue;
            }

            if (!empty($grid['opt_db_table'] && 'select_module' == $grid['field_type'])) {
                if (!empty($grid['opt_db_table'])) {
                    $join_fields = explode(',', $grid['opt_db_display']);
                     if(!empty(request()->layout_tracking)){
                $join_fields = [$join_fields[0]]; 
                }  
                    $orderby_fields = explode(',', $grid['opt_db_sortorder']);

                    if (!empty($orderby_fields) && !empty($orderby_fields[0])) {
                        foreach ($orderby_fields as $orderby) {
                            $join_selects[] = 'join'.$i.'.'.$orderby.' as orderby'.$i.'_'.$orderby;
                            $join_order_fields = [$orderby => 'orderby'.$i.'_'.$orderby];
                        }
                    }

                    if (!empty($join_fields)) {
                        if (1 == count($join_fields)) {
                            $join_selects[] = 'join'.$i.'.'.$join_fields[0].' as join_'.$grid['field'];
                        } elseif (count($join_fields) > 1) {
                            if ($this->module->connection == 'shop') {
                                $concat_select = '(';
                                foreach ($join_fields as $join_field) {
                                    $concat_select .= 'join'.$i.'.'.$join_field.' || " - " || ';
                                }
                                $concat_select = rtrim($concat_select, ' || " - " || ');
                                $concat_select .= ') as join_'.$grid['field'];
                            } else {
                                $concat_select = 'CONCAT(';
                                foreach ($join_fields as $join_field) {
                                    $concat_select .= 'join'.$i.'.'.$join_field.', " - ",';
                                }
                                $concat_select = rtrim($concat_select, ', " - ",');
                                $concat_select .= ') as join_'.$grid['field'];
                            }
                            $join_selects[] = $concat_select;
                        }
                    }
                    $joins[] = ' LEFT JOIN '.$grid['opt_db_table'].' as join'.$i.' ON '.
                    'join'.$i.'.'.$grid['opt_db_key'].' = '.$grid['alias'].'.'.$grid['field'];
                }
            }
        }
        $this->join_order_fields = $join_order_fields;
        return ['join_selects' => $join_selects, 'joins' => $joins];
    }

    public function erpFilter()
    {
        $db_columns = $this->getTableFields();

        if (!empty(session('app_id_lookup')) && in_array('module_id', $db_columns)) {
            $module_ids = \DB::connection($this->erp_conn)->table('erp_cruds')->where('app_id', session('app_id_lookup'))->pluck('id')->toArray();

            if (empty($module_ids) || count($module_ids) == 0) {
                return '';
            }

            $app_id_filters = [];
            foreach ($module_ids as $module_id) {
                $app_id_filters[] .= ' '.$this->table.'.module_id="'.$module_id.'" ';
            }
            return ' and ('.implode(' or ', $app_id_filters).') ';
        }

        return '';
    }

    public function accountFilter()
    {
        $db_columns = $this->getTableFields();
        if (14 == $this->module->app_id && !empty(session('sms_account_id')) && 1 != session('sms_account_id')) {
            if (in_array('account_id', $db_columns)) {
                return ' and '.$this->table.'.account_id='.session('sms_account_id');
            }
        }
        
        // workspace role user filter 
        if ($this->table == 'crm_staff_tasks' && (!is_superadmin() && !is_manager())) {
            
           return ' and crm_staff_tasks.role_id IN ('.implode(',',session('role_ids')).') ';
        }
         // workspace role user filter 
        if ($this->table == 'crm_staff_tasks' && is_manager()) {
            
           return ' and crm_staff_tasks.role_id!=1 ';
        }
        
       
        
        if ($this->table == 'crm_workflow_tracking' && !empty(session('user_id')) && !is_superadmin()) {
            return ' and crm_workflow_tracking.role_id IN ('.implode(',',session('role_ids')).') ';
        }
        
        if(session('instance')->id == 1){
        // activations admin only steps
        if($this->table == 'sub_activations' && (session('role_level') == 'Reseller' || session('role_level') == 'Customer')){
            // return ' and sub_activations.admin_only_step=0'; //BUG
        }
        
        // activations admin only steps
        if($this->table == 'sub_activations' && session('role_level') == 'Admin'){
        //    return ' and (partner_id=1 || (partner_id!=1 && sub_activations.admin_only_step=1))';
        }
        }

        if (empty(session('account_id')) || empty(session('user_id'))) {
           // return ' and 1=0';
        }
        
       

    
        if ($this->module->connection != 'freeswitch' && $this->module->app_id == 12) {
            if(!empty(session('service_account_id'))){
                if(empty(session('service_account_domain_uuid'))){
                    return 'and 1=0';
                }
                
                if (in_array('domain_uuid', $db_columns)) {
                    return ' and '.$this->table.'.domain_uuid="'.session('service_account_domain_uuid').'"';
                } elseif (in_array('account_id', $db_columns)) {
                    return ' and '.$this->table.'.account_id='.session('service_account_id');
                } elseif (in_array('domain_name', $db_columns)) {
                    return ' and '.$this->table.'.domain_name="'.session('service_account_domain_name').'"';
                }
                
            }else{
            
                if (session('role_level') == 'Partner') {
                    if (in_array('partner_id', $db_columns)) {
                        return ' and '.$this->table.'.partner_id='.session('account_id').'';
                    }elseif (in_array('domain_uuid', $db_columns)) {
                        return ' and '.$this->table.'.domain_uuid IN (select domain_uuid from v_domains where partner_id='.session('account_id').')';
                    } elseif (in_array('account_id', $db_columns)) {
                        return ' and '.$this->table.'.account_id IN (select account_id from v_domains where partner_id='.session('account_id').')';
                    } elseif (in_array('domain_name', $db_columns)) {
                        return ' and '.$this->table.'.domain_name IN (select domain_name from v_domains where partner_id='.session('account_id').')';
                    }
                }
                if (session('role_level') == 'Customer') {
                    if (in_array('account_id', $db_columns)) {
                        return ' and '.$this->table.'.account_id='.session('account_id');
                    } elseif (in_array('domain_uuid', $db_columns)) {
                        return ' and '.$this->table.'.domain_uuid IN (select domain_uuid from v_domains where account_id='.session('account_id').')';
                    } elseif (in_array('domain_name', $db_columns)) {
                        return ' and '.$this->table.'.domain_name IN (select domain_name from v_domains where account_id='.session('account_id').')';
                    }
                }
            
            
            }
        }  
      


        if ($this->module->connection == 'freeswitch' && session('pbx_domain') != '156.0.96.60' && session('pbx_domain') != '156.0.96.69' && session('pbx_domain') != '156.0.96.61') {
            if ($this->table == 'registrations') {
                return ' and realm="'.session('pbx_domain').'" ';
            }
            if ($this->table == 'channels') {
                return ' and initial_context="'.session('pbx_domain').'" ';
            }
        }

        if (session('role_level') == 'Admin' && 507 == $this->module->id) {
            return ' and pricelist_id = 1 ';
        } elseif (session('role_level') == 'Admin') {
            if(!empty(session('service_account_id'))){
                if ('crm_accounts' == $this->table) {
                    return ' and '.$this->table.'.id='.session('service_account_id');
                }elseif (in_array('account_id', $db_columns)) {
                    return ' and '.$this->table.'.account_id='.session('service_account_id');
                }
            }
            return '';
        }

        if (session('role_level') == 'Partner') {
            
            if(!empty(session('service_account_id'))){
                if ('crm_accounts' == $this->table) {
                    return ' and '.$this->table.'.id='.session('service_account_id');
                }elseif (in_array('account_id', $db_columns)) {
                    return ' and '.$this->table.'.account_id='.session('service_account_id');
                }
            }
            
            if ('crm_accounts' == $this->table && $this->menu->menu_type == 'module_form') {
                return ' and '.$this->table.'.id='.session('account_id');
            }
            if ('crm_accounts' == $this->table) {
                return ' and '.$this->table.'.partner_id='.session('account_id');
            }

            if (in_array('partner_id', $db_columns) && in_array('account_id', $db_columns)) {
                return ' and ('.$this->table.'.account_id='.session('account_id')
                .' or '.$this->table.'.partner_id='.session('account_id').')';
            }

            if (in_array('account_id', $db_columns)) {
                return ' and ('.$this->table.'.account_id IN (select id from crm_accounts where partner_id='.session('account_id').')'
                .' or '.$this->table.'.account_id ='.session('account_id').')';
            }

            if (in_array('partner_id', $db_columns)) {
                return ' and '.$this->table.'.partner_id='.session('account_id');
            }
        }

        if (session('role_level') == 'Customer') {
            if (588 == $this->module->id && (check_access('21') || (!empty(session('grid_role_id')) && session('grid_role_id') == 21))) {
                return ' and '.$this->table.'.ratesheet_id='.session('pbx_ratesheet_id');
            }

            if (507 == $this->module->id) {
                return ' and '.$this->table.'.pricelist_id IN (select pricelist_id from crm_accounts where id='.session('account_id').')';
            }

            if (508 == $this->module->id || 524 == $this->module->id) {
                return ' and pricelist_id IN (select id from crm_pricelists where partner_id='.session('account_id').')';
            }

            if ('crm_accounts' == $this->table) {
                return ' and '.$this->table.'.id='.session('account_id');
            }

            if (1 != session('parent_id')) {
                if ('crm_documents' == $this->table) {
                    return ' and '.$this->table.'.reseller_user='.session('account_id');
                }
            }

            if (in_array('partner_id', $db_columns) && 21 != session('role_id')) {
                return ' and '.$this->table.'.partner_id='.session('account_id');
            }

            if (in_array('account_id', $db_columns)) {
                return ' and '.$this->table.'.account_id='.session('account_id');
            }

            if (!empty(session('user_id')) && in_array('user_id', $db_columns)) {
                return ' and '.$this->table.'.user_id='.session('user_id');
            }
        }
    }

    public function singleRecordAccess($id)
    {
        if (is_superadmin() && ($this->module->connection == 'pbx' || $this->table == 'crm_staff_tasks')) {
            $where = '';
        } else {
            $where = $this->accountFilter();
            $where .= $this->erpFilter();
        }
    
        return \DB::connection($this->module->connection)->table($this->table)
            ->where($this->primary_key, $id)
            ->whereRaw('1=1 '.$where)
            ->count();
    }

    private function getHiddenGridFilters($get_filters = [])
    {
        $get_filter_fields = [];
        if (!empty($get_filters)) {
            foreach ($get_filters as $get_filter) {
                $get_filter_fields[] = $get_filter['field'];
            }
        }

        $filters = [];
        foreach ($this->info['module_fields'] as $i => $field) {
            $field_show = $this->gridAccess();
            if (!$field_show) {
                if (!empty($field['filter']) && !in_array($field['field'], $get_filter_fields)) {
                    if (str_ends_with($key, 'id') && is_numeric($val)) {
                        if (!empty($field['opt_db_table'])) {
                            $display = explode(',', $field['opt_db_display']);
                            $val = \DB::connection($this->erp_conn)->table($field['opt_db_table'])->where($field['opt_db_key'], $val)->pluck($display[0])->first();
                        }

                        $filters[] = (object) [
                            'field' => $key,
                            'filter_operator' => 'equal',
                            'value' => $val,
                            'isComplex' => false,
                        ];
                    } else {
                        $filters[] = (object) [
                            'field' => $field['field'],
                            'filter_operator' => 'contains',
                            'value' => $field['filter'],
                            'isComplex' => false,
                        ];
                    }
                }
            }
        }

        return $filters;
    }

    private function makeInfo()
    {
        $data = (array) $this->module;
        if ($data['db_table'] == 'crm_documents' || $data['db_table'] == 'crm_supplier_documents' || $data['db_table'] == 'crm_supplier_import_documents') {
            $data['documents_module'] = true;
        } else {
            $data['documents_module'] = false;
        }
        ////////aa('info module_id '.$data['id']);
        $data['module_id'] = $data['id'];
        $module_id = $data['module_id'];
        unset($data['id']);
        $module_fields = \DB::connection($this->erp_conn)->table('erp_module_fields')->where('module_id', $module_id)->orderBy('sort_order')->get();
     
        
        $module_styles = app('erp_config')['grid_styles']->where('module_id', $module_id);
       
        
        $module_layouts = app('erp_config')['layouts']->where('module_id', $module_id)->sortBy('sort_order');
       
   
        $data['db_module_fields'] = $module_fields;
        $data['module_layouts'] = $module_layouts;
        $data['module_styles'] = $module_styles;
        $data['module_fields'] = json_decode(json_encode($module_fields, true), true);
        $data['menu_route'] = $this->module->slug;
        
        $data['menu_name'] = $this->module->name;
        $data['module_name'] = $this->module->name;
        if(isset($this->menu)){
            $data['menu_location'] = $this->menu->location;
            $data['menu_id'] = $this->menu->id;
           // $data['menu_name'] = $this->menu->menu_name;
            $data['menu'] = $this->menu;
        }
        
        if(!empty($this->is_detail_module)){
         
            $data['menu_route'] = 'detailmodule_'.$this->module->id;
      
        }

        $query_string = request()->getQueryString();
        if (!empty($query_string)) {
            $module_filter = \DB::connection($this->erp_conn)->table('erp_menu')->where('module_id', $this->module->id)->where('menu_type', 'module_filter')->where('url', '?'.$query_string)->pluck('menu_name')->first();
            if ($module_filter) {
                $data['menu_name'] = $module_filter;
            }
        }
        if (request()->segment(1) == 'reports' && !empty(request()->role_id)) {
            $role_name = \DB::connection($this->erp_conn)->table('erp_user_roles')->where('id', request()->role_id)->pluck('name')->first();
            $data['menu_name'] = $role_name .' '. $data['menu_name'];
        }
        // filter module name
    
        $data['title'] = $data['menu_name'];
        $data['access'] = $this->validAccess();
       


        $this->info = $data;
    }

    private function gridAccess($access_groups = null)
    {
        return true;
    }

    public function getTableFields()
    {
        return get_columns_from_schema($this->table);
    }

    public function getRow($id)
    {
        return (array) \DB::connection($this->module->connection)->table($this->table)->where($this->primary_key, $id)->get()->first();
    }

    public function deleteRow($id)
    {
        $db_columns = $this->getTableFields();
        if (in_array('status', $db_columns)) {
            \DB::connection($this->erp_conn)->table($this->table)->where($this->primary_key, $id)->update(['status' => 'Deleted']);
            if (in_array('deleted_at', $db_columns)) {
                \DB::connection($this->erp_conn)->table($this->table)->where($this->primary_key, $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            }
        } else {
            \DB::connection($this->erp_conn)->table($this->table)->where($this->primary_key, $id)->delete();
        }
    }

    public function getColoumnInfo($result)
    {
        $pdo = \DB::getPdo();
        $res = $pdo->query($result);
        $i = 0;
        $coll = array();
        while ($i < $res->columnCount()) {
            $info = $res->getColumnMeta($i);
            $coll[] = $info;
            ++$i;
        }

        return $coll;
    }

    public function insertRow($data, $id)
    {
        $table = $this->table;
        $key = $this->primary_key;
        $db_columns = $this->getTableFields();
        if (empty($id)) {
            if (strstr($table, 'crm_account_') && !empty($data['account_id'])) {
                $id = $data['account_id'];
            }
        }

        if (null == $id) {
            // Insert Here
            if (in_array('created_at', $db_columns)) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            $id = \DB::connection($this->erp_conn)->table($table)->insertGetId($data, $this->primary_key);
        } else {
            // Update here
            if (in_array('updated_at', $db_columns)) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            \DB::connection($this->erp_conn)->table($table)->where($key, $id)->update($data);
        }

        return $id;
    }


    public function validAccess()
    {
       
        ////aa('validAccess1');
        ////aa(generateCallTrace());
        ////aa('validAccess2');
       
       
        session(['pbx_domain_level' => false]);

        if (!session('role_id')) {
            return false;
        }

        if (!module_access_subscriptions($this->module_name)) {
            return 'subscription';
        }



        $grid_role_id = false;

        if ($this->module->app_id == 12) {
            // reset pbx session for admin modules
           
            if (!is_superadmin() && empty(session('pbx_account_id'))) {
              
                $pbx = new \FusionPBX();
                $pbx->pbx_login();
            }
        }
    
        $grid_role_id = session('role_id');
        if ($this->module->app_id == 14 && session('sms_company') != 'SMS Admin') {
            $grid_role_id = session('role_id');
        } elseif ($this->module->id != 587  && $this->module->app_id == 12 && check_access('1,31') && session('pbx_domain') && session('pbx_domain') != '156.0.96.60' && session('pbx_domain') != '156.0.96.69' && session('pbx_domain') != '156.0.96.61') {
           // if (session('pbx_partner_level')) {
           //     $grid_role_id = 11;
           // } else {
          //      $grid_role_id = 21;
          //  }

           // session(['pbx_domain_level' => true]);
        } else {
            $grid_role_id = session('role_id');
        }
        $grid_role_ids = [$grid_role_id];
        if($grid_role_id == session('role_id')){
            $grid_role_ids = session('role_ids');
        }
        //if(!empty(session('extra_role_id'))){
        //    $grid_role_ids[] = session('extra_role_id');
        //}
      


        $module_access = app('erp_config')['forms']
        ->whereIn('role_id', $grid_role_ids)
        ->where('module_id', $this->module->id)
        ->first();
        $master_module_count = \DB::connection($this->erp_conn)->table('erp_cruds')->where('detail_module_id', $this->module->id)->count();
        if ($master_module_count) {
            $master_module_id =  \DB::connection($this->erp_conn)->table('erp_cruds')->where('detail_module_id', $this->module->id)->pluck('id')->first();
            $master_access_exists = app('erp_config')['forms']
            ->whereIn('role_id', $grid_role_ids)
            ->where('module_id', $master_module_id)
            ->where('is_view', 1)
            ->count();
            if($master_access_exists){
                $master_module_access = app('erp_config')['forms']
                ->whereIn('role_id', $grid_role_ids)
                ->where('module_id', $master_module_id)
                ->first();
                
                if(empty($module_access)){
                    $module_access = (object) [];
                }
                
                foreach($master_module_access as $k => $v){
                    if($master_module_access->{$k}){
                        $module_access->{$k} = 1;    
                    }    
                }  
            }
        }
        if($module_access && isset($module_access->is_view)){
            $module_access->is_menu = $module_access->is_view;    
        }
      
        $access = (object) [];
        $access->is_add = 0;
        $access->is_edit = 0;
        $access->is_delete = 0;
        $access->is_view = 0;
        $access->is_menu = 0;
        
      
        foreach($access as $k => $v){
            if($module_access->{$k}){
                $access->{$k} = 1;    
            }    
        }
        
        if(!empty($this->module->permissions) && $this->module->permissions != 'Full Access'){
            if($this->module->permissions == 'View'){
                $access->is_add = 0;
                $access->is_edit = 0;
                $access->is_delete = 0;
            }
            if($this->module->permissions == 'Add'){
                $access->is_edit = 0;
                $access->is_delete = 0;
            }
            if($this->module->permissions == 'Edit'){
                $access->is_add = 0;
                $access->is_delete = 0;
            }
            
            if($this->module->permissions == 'Add & Edit'){
                $access->is_delete = 0;
            }
        }
        if($this->module->db_table == 'crm_accounts' || $this->module->db_table == 'sub_services'){
            $access->is_delete = 1;
        }
        
        if(session('role_level') == 'Admin'){
            $access->is_approve = $access->is_edit;
        }
        
     
        if (is_manager()) {
            $access->is_view = 1;
        }

        if (check_access('1') && !empty(request()->form_role_id)) {
            $access->is_view = 1;
            $access->is_add = 1;
        }
      
        if(!$access->is_view && session('role_level') == 'Admin'){
 
            $module_ids = \DB::connection('default')->table('erp_favorites')->pluck('module_id')->filter()->unique()->toArray();
            if(in_array($this->module->id,$module_ids)){
       
                $access->is_view = 1;
                $access->is_menu = 1;
            }
        }
     


        session(['grid_role_id' => $grid_role_id]);
        if (!$access) {
            return false;
        }


        if ('crm_documents' == $this->table && 0 == session('enable_client_invoice_creation')) {
            $access->is_add = 0;
            $access->is_edit = 0;
        }
        
        if(is_superadmin() || is_dev()){
            $access->is_import = 1;    
        }
        
        //dd($access,$module_access,session('role_ids'),$this->module->id,$master_module_count,$master_module_id,$master_access_exists);

        return (array) $access;
    }

    public static function getColumnTable($table)
    {
        $list = get_columns_from_schema($table);
        $columns = [];
        foreach ($list as $column) {
            $columns[$column] = '';
        }

        return $columns;
    }

    public static function getTableList($db)
    {
        $t = array();
        $dbname = 'Tables_in_'.$db;
        foreach (\DB::select("SHOW TABLES FROM {$db}") as $table) {
            $t[$table->$dbname] = $table->$dbname;
        }

        return $t;
    }
}
