<?php

class ErpForm
{
    protected $data;
    protected $tabs;
 
    protected $row;
    protected $form_uses_tinymce = false;
    public $form_id;

    public function __construct($data, $params = [])
    {
        $this->data = $data;
        $this->params = (object) $params;

        if (isset($this->data['module_fields'][0]['sort_order'])) {
            usort($this->data['module_fields'], array($this, 'sortForm'));
        }
        $this->getFormId();
        
        $this->form_name = 'mod'.$this->data['module_id'];
        $this->data['form_name'] = $this->form_name;
        /*
        if($this->data['auto_form']){
        if(!empty(request()->layout_id)){
            $this->setFieldVisibilityFromLayout(request()->layout_id);
        }
        }
        */
        
        $this->data['module_fields'] = collect($this->data['module_fields'])->sortBy('sort_order')->toArray();
    }
    
    public function setFieldVisibilityFromLayout($layout_id){
        
        
        $layout = \DB::connection('default')->table('erp_grid_views')->where('id', $layout_id)->get()->first();
        $module_id = $this->data['module_id'];
      
        
        foreach($this->data['module_fields'] as $i => $f){
            if(str_contains($f['field_type'],'hidden')){
                $this->data['module_fields'][$i]['tab'] = 'General'; 
            }
        }
        
        $is_detail_layout = ($this->data['module_id'] != $layout->module_id) ? true : false;
        if($layout->hide_auto_form_tabs){
            $tabs = ['General'];
        }else{
            $tabs = ['General','Advanced'];
        }
       
        
        $module_tabs = \DB::connection('default')->table('erp_module_fields')->whereNotIn('tab', ['','General','Advanced'])->where('module_id', $module_id)->orderBy('sort_order')->pluck('tab')->filter()->unique()->toArray();
        if(!$layout->hide_auto_form_tabs){
            foreach ($module_tabs as $module_tab) {
                $tabs[] = $module_tab;
            }
        }
        
       
        
        $tabs = array_values($tabs);
        

        $layout_state = (!$is_detail_layout) ? $layout->aggrid_state : $layout->detail_aggrid_state;

        if ($layout_state) {
            $state = json_decode($layout_state);
            
            if ($state && $state->colState) {
                $sorted_columns = [];
                $last_sort = 0;
                foreach ($this->data['module_fields'] as $j => $field) {
                    $field_name = $field['field'];
                    if ($field['field_type'] == 'select_module') {
                        $field_name = 'join_'.$field_name;
                    }
                    
                    foreach ($state->colState as $i => $col) {
                        if ($col->colId == $field_name) {
                            $sorted_columns[] = $field['field'];
                          
                            if (!str_contains($field['field_type'],'hidden') && !$field['display_field'] && $field['tab'] == '') {
                                if($col->hide === "true"){
                                    $this->data['module_fields'][$j]['tab'] = 'Advanced';
                                }else{
                                    $this->data['module_fields'][$j]['tab'] = 'General';
                                }
                            } 
                            if($layout->hide_auto_form_tabs){
                                if ($col->hide === "true") {
                                    $this->data['module_fields'][$j]['visible'] = 'None';
                                }
                            }
                            
                            // if ($field['tab'] != 'General' && $col->hide === "false") {
                            //     $this->data['module_fields'][$j]['tab'] = 'General';
                            // }
                            
                            if ($col->hide === "false") {   
                                $sorted_columns[] = $field['field'];
                                $this->data['module_fields'][$j]['sort_order'] = $i;
                                $last_sort = $i;
                            }
                           
                        }
                    }
                }
                
               
                foreach ($this->data['module_fields'] as $j => $field) {
                    if(!in_array($field['field'], $sorted_columns)){
                     
                        $this->data['module_fields'][$j]['sort_order'] = $last_sort;
                        $last_sort++;
                        if($layout->hide_auto_form_tabs){
                        $this->data['module_fields'][$j]['visible'] = 'None';
                        }
                    }
                }
                
            }

        }
    }

    public function sortForm($a, $b)
    {
        if (isset($b['sort_order'])) {
            return strnatcmp($a['sort_order'], $b['sort_order']);
        }
    }

    public function setEditType($type)
    {
        $this->data['edit_type'] = $type;
    }

    public function getFormId()
    {
        if ('id' == $this->data['db_key'] || str_contains($this->data['db_key'], '_id')) {
            $id = (is_numeric(request()->segment(3))) ? request()->segment(3) : null;
        } else {
            $id = request()->segment(3);
        }

        if (empty($id) && empty(request()->segment(3)) && !empty(session('webform_id'))) {
            $id = session('webform_id');
        }

        if (empty($id) && 1 == $request->from_menu) {
            $columns = \Schema::getColumnListing($this->info['module_db']);
            if (!in_array('account_id', $columns)) {
                $id = session('account_id');
            } else {
                $num_records = \DB::table($this->info['module_db'])->where('account_id', session('account_id'))->count();
                if (1 == $num_records) {
                    $id = \DB::table($this->info['module_db'])->where('account_id', session('account_id'))->pluck('id')->first();
                }
            }
        }

        if (empty($id)) {
            $this->setEditType('add');
        } else {
            $this->setEditType('edit');
        }

        $this->form_id = $id;
    }

    public function setRow($row)
    {
        $this->data['form_script'] = '';
        $this->data['form_html'] = '';

        $this->row = $row;
    }

    public function getForm($row)
    {
        $this->row = $row;
        $this->getVisibleTabs();
       
        if(count($this->tabs) == 1){
            $this->data['form_html'] = '
                <div class="control-section card">
        		<div class="content-wrapper card-body p-4">
    		';
        }else{
            $this->data['form_html'] = '
                <div class="control-section card">
        		<div class="content-wrapper card-body p-0 pb-4">
    		';
        }

        if (!empty($this->params) && !empty($this->params->module_report)) {
            $this->data['form_html'] = '
               <input type="hidden" name="module_report" value="1"/>
    		';
        }
      
        $this->data['form_script'] = '';
        $this->data['form_validation'] = '';
        if (!empty($this->tabs) && count($this->tabs) > 1) {
           
            $this->data['form_html'] .= '<div id="'.$this->form_name.'Tab" ></div>';
            $i = 0;
            foreach ($this->tabs as $tab) {
                $tab_id = str_replace(' ', '', $tab);
                $visible = ($i > 0) ? 'style="display:none;"' : '';
                $this->data['form_html'] .= '<div id="'.$tab_id.'" '.$visible.' class="current-tab"><br>';
                $this->buildForm($tab);
            
                $this->data['form_html'] .= '</div>';
                ++$i;
            }

           
            $this->data['form_html'] .= '
            </div>
            </div>
        ';
        } else {
           
            $this->buildForm();
        }


        $this->data['form_script'] .= PHP_EOL.' form_edit_type = "'.$this->data['edit_type'].'"; '.PHP_EOL;
      

        if (!empty($this->data['form_validation'])) {
            $this->data['form_script'] .= '
                var validation_options = {
                    rules: {
                    '.$this->data['form_validation'].'
                    },
                    customPlacement: function(inputElement, error) {
                       $(inputElement).closest(".form-group").append(error);
                    }
                }
                '.$this->form_name."FormValidator = new ej.inputs.FormValidator('#".$this->form_name."FormAjax', validation_options);
            ";
        }

        $this->getTabScript();
        $this->getValidationScript();

        $data = $this->data;

        unset($data['module_fields']);


        return $data;
    }

    public function getImportForm()
    {
        $this->setEditType('add');

        $this->data['form_script'] = '';
        $this->data['form_html'] = '
            <div class="control-section my-3">
    		<div class="content-wrapper">
    		<a href="/download_import_file_sample/'.$this->data['module_id'].'" target="_blank">Download Import File Sample</a>
		';


        $this->buildForm(null, true);
        $this->data['form_html'] .= '
            </div>
            </div>
        ';
        $data = $this->data;

        unset($data['module_fields']);


        return $data;
    }

    private function getVisibleTabs()
    {
        $visible_tabs = [];
        $tabs = collect($this->data['module_fields'])->where('visible','!=','')->sortBy('sort_order')->pluck('tab')->unique()->all();
      
        $use_advanced = false;
        $empty_tab = false;
        foreach($tabs as $i => $tab){
            if($tab == ''){
                unset($tabs[$i]);
                $empty_tab = true;
            }
            if($tab == 'Advanced'){
                unset($tabs[$i]);
                $use_advanced = true;
            }
        }
        if($use_advanced){
            $tabs[] = 'Advanced';
        }
        if($empty_tab){
            $tabs[] = 'Superadmin';
        }
        
        $tabs = array_values($tabs);
        
        $tabs = array_values($tabs);
     
        if (empty($tabs) || 0 == count($tabs)) {
            return [];
        }

        foreach ($tabs as $tab) {
            $lookup_tab = $tab;
            if($tab == 'Superadmin'){
                $lookup_tab = '';
            }
           
            foreach ($this->data['module_fields'] as $field) {
                if ($field['tab'] == $tab || ($tab == 'Superadmin' && $field['tab'] == '')) {
                    $field_show = $this->formAccess($field);

                    if (!$field_show) {
                        continue;
                    }
                    if(empty($field['visible'])){
                        continue;
                    }
                    if($field['visible'] == "None"){
                        continue;
                    }
                    if(str_contains($field['field_type'],'hidden')){
                        continue;
                    }

                    if (!in_array($tab, $visible_tabs)) {
                        $visible_tabs[] = $tab;
                    }
                }
            }
        }

        if (!empty($visible_tabs)) {
            foreach ($this->data['module_fields'] as $field) {
                $field_show = $this->formAccess($field);

                if (!$field_show) {
                    continue;
                }
                
            }
        }
        
        if(in_array('General',$visible_tabs)){
            $str_to_move = 'General';
            
            // Find the index of the element to move
            $index_to_move = array_search($str_to_move, $visible_tabs);
            
            // Remove the element from its current position
            unset($visible_tabs[$index_to_move]);
            
            // Add the element to the beginning of the array
            array_unshift($visible_tabs, $str_to_move);
        }
        $this->data['num_tabs'] = count($visible_tabs);
        $this->tabs = $visible_tabs;
    }

    private function buildForm($tab = null, $import_form = false)
    {
       
        foreach ($this->data['module_fields'] as $field) {
          
            $add_to_html = (null == $tab || (!empty($field['tab']) && $field['tab'] == $tab) || (empty($field['tab']) && 'Superadmin' == $tab)) ? true : false;
            if($tab == 'General' && str_contains($field['field_type'],'hidden')){
                $add_to_html = true;
            }

            if (!$add_to_html) {
            
                continue;
            }
            $db_exists = ($field['alias'] == $this->data['db_table']) ? 1 : 0;
            if (!$db_exists) {
                   
                continue;
            }

            $field_show = $this->formAccess($field, $import_form);
          
            if (!$field_show) {
                      
                continue;
            }

            $this->getFormField($field);
        }
    }

    private function getFormFieldValidation($field)
    {
        /*
        https://ej2.syncfusion.com/javascript/documentation/form-validator/validation-rules/
        */
        extract($field);
        if ($required && 'file' != $field_type) {
            $this->data['form_validation'] .= $field.': { required: true },';
        } elseif ('date' == $field_type || 'datetime' == $field_type) {
            $this->data['form_validation'] .= $field.': { date: [true, "Enter valid date format"] },'.PHP_EOL;
        } elseif ('currency' == $field_type || 'integer' == $field_type) {
            $this->data['form_validation'] .= $field.': { number: [true, "Enter valid numeric value"] },'.PHP_EOL;
        }
    }

    private function getTabScript($tabs = null)
    {
        $this->data['form_script'] .= '
        tab_count = '.count($this->tabs).';
      
        ';
        if (!empty($this->tabs) && count($this->tabs) > 1) {
          
            $this->data['form_script'] .= '
          
            $(document).ready(function() {
                formtabObj = new ej.navigations.Tab({
                items: [';
            foreach ($this->tabs as $tab) {
                $tab_id = str_replace(' ', '', $tab);
                if($tab == 'Superadmin' && !is_superadmin()){
                $this->data['form_script'] .= "{header: { text: '".$tab."'  }, content: '#".$tab_id."',cssClass: 'form-dnd-zone', disabled:true},";
                }else{
                $this->data['form_script'] .= "{header: { text: '".$tab."'  }, content: '#".$tab_id."',cssClass: 'form-dnd-zone'},";
                }
            }
            $this->data['form_script'] .= '],';
            if ($this->form_uses_tinymce) {
                $this->data['form_script'] .= '
                    selected: setTinyMceTabs,
                    created: function(args){
                        setTinyMceTabs();
                        this.keyConfigs["space"] = "";
                        this.keyModule.keyConfigs["space"] = "";
                      
                    },
                    selecting: function(args){
                        if(args.isSwiped){
                            args.cancel = true;
                        }
                    },
                ';
            }else{
                $this->data['form_script'] .= '
                    selecting: function(args){
                        if(args.isSwiped){
                            args.cancel = true;
                        }
                    },
                    created: function(args){
                        this.keyConfigs["space"] = "";
                        this.keyModule.keyConfigs["space"] = "";
                      
                    },
                ';
                
            }
          
            $this->data['form_script'] .= "
                });
                formtabObj.appendTo('#".$this->form_name."Tab');
                
            });
            ";
        } else {
            if ($this->form_uses_tinymce) {
                $this->data['form_script'] .= '
               
                $(document).ready(function() {
                    setTinyMce();
                });
                ';
            }
        }
    }

    private function getValidationScript()
    {
        $this->data['form_script'] .= '
            var validation_options = {
                rules: {
                '.$this->data['form_validation'].'
                },
                customPlacement: function(inputElement, error) {
                   $(inputElement).closest(".form-group").append(error);
                }
            }
            '.$this->form_name."FormValidator = new ej.inputs.FormValidator('#".$this->form_name."FormAjax', validation_options);
        ";
    }

    public function formAccess($field, $import_form = false)
    {
        $field_visible = '';
        if($field['visible'] == 'Add and Edit'){
            $field_visible = 'both';
        } 
        if($field['visible'] == 'Add'){
            $field_visible = 'add';
        } 
        if($field['visible'] == 'Edit'){
            $field_visible = 'edit';
        }
        if($field['visible'] == 'None'){
            $field_visible = '';
        }
        if ($this->info['module_db'] == 'crm_accounts' && $this->row['id'] == session('account_id') && 'pricelist_id' == $field['field']) {
            return false;
        }

        if ($this->info['module_db'] == 'crm_accounts' && $this->row['id'] == session('account_id') && 'status' == $field['field']) {
            return false;
        }

        if ($import_form) {
            if (empty($field['import'])) {
                return false;
            }
        } elseif (!empty(session('webform_module_id')) && (!in_array($field_visible,[$this->data['edit_type'],'both']) || empty($field['webform']))) {
            return false;
        } elseif (!in_array($field_visible,[$this->data['edit_type'],'both'])) {
            return false;
        }
        
        $remove_field = 0;
        if(!empty($field['level_access'])){
            $level_access = explode(',', $field['level_access']);
            $role_level = session('role_level');
            $role_id = session('role_id');

            $remove_field = 0;
            if (is_superadmin() && session('role_level')!='Partner' && session('role_level')!='Customer') {
                $role_level = 'Superadmin';
            }
            if ($role_level == 'Superadmin') {
                $remove_field = 1;
                if (in_array('Admin', $level_access) || in_array('Superadmin', $level_access)) {
                    $remove_field = 0;
                }
            } else {
                if (!in_array($role_level, $level_access)) {
                    $remove_field = 1;
                }
            }
        }
        
        
        if($remove_field && !empty($field['limit_access_readonly_id'])){
            $readonly_role_ids = explode(',', $field['limit_access_readonly_id']);
            $role_id = session('role_id');

            if (in_array($role_id, $readonly_role_ids)) {
                $remove_field = 0;
            }
        }
      
        if($remove_field){
            return false;
        }


        return true;
    }

    public function getFormField($field, $cell_editor = false)
    {
        $module_field = $field;
      
        $row = $this->row;

        if (session('role_level') == 'Admin') {
            $form_access = $this->formAccess($field);
            if ($form_access === 'hidden') {
                $field['readonly'] = 1;
                $field['readonly_edit'] = 1;
            }
        }


        $this->getFormFieldValidation($field);
        extract($field);
        $conf = null;
        if (empty($default_value) && 'date' == $field_type) {
            $default_value = '';
        }

        if (empty($default_value) && 'datetime' == $field_type) {
            $default_value = '';
        }
        if (empty($default_value) && 'time' == $field_type) {
            $default_value = '';
        }
        
        
        if (strtolower($default_value) == 'now' && 'date' == $field_type) {
            $default_value = date('Y-m-d');
        }

        if (strtolower($default_value) == 'now' && 'datetime' == $field_type) {
            $default_value = date('Y-m-d H:i:s');
        }



        if ($default_value == 'session_account_id') {
            $default_value = session('account_id');
            if (228 == $this->data['module_id'] && !empty(request()->account_id)) {
                $default_value = request()->account_id;
            }
        }

        if ($default_value == 'session_sms_account_id') {
            $default_value = session('sms_account_id');
        }


        if ($default_value == 'session_user_id') {
            $default_value = session('user_id');
        }


        if ($default_value == 'user_filter_id') {
            //$default_value = \DB::table('erp_users')->where('id', session('user_id'))->pluck('calendar_session_user_id')->first();
        }
        
        if ($default_value == 'user_workspace_id') {
            $role_id = session('role_id');
           
            $default_value = \DB::table('erp_users')->where('role_id', $role_id)->where('is_deleted',0)->pluck('id')->first();
        }
        
        if ($default_value == 'session_role_id' && $field == 'role_id') {
            
            $role_id = session('role_id');
            if(!empty(request('filter_model'))){
                $filter_model = json_decode(request('filter_model'));
                if(!empty($filter_model->join_role_id)){
                   if(!empty($filter_model->join_role_id->values) && !empty($filter_model->join_role_id->values[0])){
                       $role_id = \DB::connection('default')->table('erp_user_roles')->where('name',$filter_model->join_role_id->values[0])->pluck('id')->first();
                   }
                }
            }
           
          
            $default_value = $role_id;
        }
     
        
        if ($default_value == 'workboard_role_id') {
            $role_id = session('role_id');
          
            $default_value = $role_id;
        }

        if (!empty(session('webform_module_id')) && $field == 'account_id') {
            $default_value = session('webform_account_id');
        }

        if (!empty(session('webform_subscription_id')) && $field == 'subscription_id') {
            $default_value = session('webform_subscription_id');
        }
        if (!empty(session('webform_is_contract')) && $field == 'is_contract') {
            $default_value = session('webform_is_contract');
        }

        if ($default_value == 'default_mailing_list_id') {
            $default_value = get_default_mailing_list_id();
        }
        
        if($field == 'domain_uuid' && !empty(session('service_account_domain_uuid'))){
            $default_value = session('service_account_domain_uuid');
        }
        //aa(session('service_account_domain_uuid'));

        /* PBX EXTENSIONS */
        if (($this->data['db_table'] == 'v_ring_groups' && $field == 'ring_group_extension')
        || ($this->data['db_table'] == 'v_ivr_menus' && $field == 'ivr_menu_extension')
        || ($this->data['db_table'] == 'v_extensions'  && $field == 'extension')) {
            $readonly = '';
            if ('add' == $this->data['edit_type']) {
                $default_value = pbx_generate_extension(session('service_account_domain_uuid'), 1000);
            }
           
            
        }


        if (!empty($this->params->{$field}) && 'add' == $this->data['edit_type'] && $field != $this->data['db_key']) {
            $value = $this->params->{$field};
        } elseif ($default_value == 0 && 'add' == $this->data['edit_type'] && ('integer' == $field_type || 'currency' == $field_type)) {
            $value = $default_value;
        } elseif (!empty($default_value) && 'add' == $this->data['edit_type']) {
            $value = $default_value;
        } elseif ($row) {
            $value = $row[$field];
        } else {
            $value = '';
        }
        if ($field == 'user_id' && $this->data['module_id'] == 760 && 'add' == $this->data['edit_type']) {
            //    $value =  \DB::table('erp_users')->where('id', session('user_id'))->pluck('calendar_session_user_id')->first();
        }

        if ($this->data['module_id'] == 580 && empty($value)) {
            $account = dbgetaccount(session('account_id'));
            if ($field == 'name') {
                $value = $account->company;
            }
            if ($field == 'email') {
                $value = $account->email;
            }
        }
        $readonly_val = '';
        if (($readonly == 'Add' || $readonly == 'Add and Edit') && 'add' == $this->data['edit_type']) {
            $readonly_val = 'readonly=true';
        }
        if (($readonly == 'Edit' || $readonly == 'Add and Edit') && 'edit' == $this->data['edit_type']) {
            $readonly_val = 'readonly=true';
        }

        if ('view' == $this->data['edit_type']) {
            $readonly_val = 'readonly=true';
        }


        if(!empty($limit_access_readonly_id)){
            $readonly_role_ids = explode(',', $limit_access_readonly_id);
            $role_id = session('role_id');
           
            if (in_array($role_id, $readonly_role_ids)) {
                 $readonly_val = 'readonly=true';
            }
        }
        
        $readonly = $readonly_val;
       
        

        $modref = $this->data['module_id'];
        $field_id = $field.$modref;
        if (!empty($tooltip)) {
            $tip = '<span id="'.$field_id.'_tooltip" class="form_tooltip"><i class="far fa-question-circle"></i> </span>';
        } else {
            $tip = '';
        }

        $field_required = ($required) ? 'required' : '';
        $show_tooltip = false;
        $field_html = '';
       
        if (!str_contains($field_type, 'textarea')) {
            $this->data['form_html'] .= '<input type="hidden" id="hidden'.$field_id.'" value="'.$value.'" >';
        }


        if (!empty($value_function) && !empty($row) && function_exists($value_function)) {
            $value = $value_function($row);
        }

        // db -> add field
        if ($this->data['module_id'] == 527 && 'add' == $this->data['edit_type']) {
            if (!empty($this->params->from_menu) && !empty($this->params->module_id)) {
                if ($field == 'table_name') {
                    $value = \DB::connection('default')->table('erp_cruds')->where('id', $this->params->module_id)->pluck('db_table')->first();
                    // $readonly = 'readonly=true';
                }
            }
        }


        

        if (!empty(session('webform_module_id')) && $field == 'account_id') {
            $value = session('webform_account_id');
            $readonly_val = 'readonly=true';
            $readonly='Add and Edit';
        }

        if (!empty(session('webform_subscription_id')) && $field == 'subscription_id') {
            $value = session('webform_subscription_id');
            $readonly_val = 'readonly=true';
            $readonly='Add and Edit';
        }
        if($field_type == 'text' && $this->data['module_id'] == 749 && $field=='opt_db_where'){
            $field_type = 'field_db_where';
        }
        
        if($readonly == 'None'){
            $readonly = '';
        }
        
        //if(is_dev() && $field == 'display_logic'){
       //     $field_type = 'form_logic_query_builder';
       // }

        switch ($field_type) {
            case 'hidden':
                $field_html .= '<input type="hidden" name="'.$field.'" id="'.$field_id.'" value="'.$value.'" >';
                break;
            case 'hidden_ip':
                $field_html .= '<input type="hidden" name="'.$field.'" id="'.$field_id.'" value="'.request()->ip().'" >';
                break;
            case 'hidden_uuid':
                
                if (empty($value) && $this->data['db_key'] != $field) {
                    $value = pbx_uuid($this->data['db_table'], $field);
                    //$value = '';
                }
                $field_html .= '<input type="hidden" name="'.$field.'" id="'.$field_id.'" value="'.$value.'" >';
                break;
            case 'grid_logic_query_builder':
               
               
                $show_tooltip = true;
                $readonly_script = (!empty($readonly) && $readonly) ? PHP_EOL.'readonly: true,'.PHP_EOL : '';
                $field_html .= "<input type='hidden' name='".$field."' id='".$field_id."' value='".$value."'>";
                $field_html .= "<div id='".$field_id."qb'></div>";
                $columnData = query_builder_get_column_data($row['opt_module_id']);
               
                
                
                $this->data['form_script'] .= '
                var '.$field_id.' = new ej.querybuilder.QueryBuilder({
                columns: '.json_encode($columnData).',
                '.$readonly_script.'
                created: function(e){';
                if($value > ''){
                    $value = str_replace('"',"'",$value);
                $this->data['form_script'] .= '
                try{
                    this.setRulesFromSql("'.$value.'");
                }catch(e){}
                ';
                }
                $this->data['form_script'] .= '},
                change: function(e){
                    var sql = this.getSqlFromRules(this.getRules());
                    console.log(sql);
                    $("#'.$field_id.'").val(sql);
                },
                });
                '.$field_id.'.appendTo("#'.$field_id.'qb");
                 console.log('.$field_id.');
                ';
                
                break;
            case 'form_logic_query_builder':
               
               
              
                $show_tooltip = true;
                $readonly_script = (!empty($readonly) && $readonly) ? PHP_EOL.'readonly: true,'.PHP_EOL : '';
                $field_html .= "<input type='hidden' name='".$field."' id='".$field_id."' value='".$value."'>";
                $field_html .= "<div id='".$field_id."qb'></div>";
                $columnData = form_logic_query_builder_get_column_data($row['module_id']);
               
                
                // aa($value);
                $this->data['form_script'] .= '
                var '.$field_id.' = new ej.querybuilder.QueryBuilder({
                columns: '.json_encode($columnData).',
                '.$readonly_script.'
                created: function(e){';
                if($value > ''){
                    $value = str_replace('"',"'",$value);
                $this->data['form_script'] .= '
                try{
                console.log("value","'.$value.'")
                    var js_to_sql = parseJsRule("'.$value.'");
                console.log("js_to_sql",js_to_sql)
                    this.setRulesFromSql("'.$value.'");
                }catch(e){
                console.log("setRules",e)
                    
                }
                ';
                }
                $this->data['form_script'] .= '},
                change: function(e){
                    var sql = this.getSqlFromRules(this.getRules());
                    var sql_to_js = transformSqlToJs("'.$value.'");
                    console.log(sql);
                    $("#'.$field_id.'").val(sql);
                },
                });
                '.$field_id.'.appendTo("#'.$field_id.'qb");
                 console.log('.$field_id.');
                ';
                
                break;
            case 'field_db_where':
               
               
                if($this->data['module_id'] == 749 && !empty($row['opt_module_id'])){
                $show_tooltip = true;
                $readonly_script = (!empty($readonly) && $readonly) ? PHP_EOL.'readonly: true,'.PHP_EOL : '';
                $field_html .= "<input type='hidden' name='".$field."' id='".$field_id."' value='".$value."'>";
                $field_html .= "<div id='".$field_id."qb'></div>";
                $columnData = query_builder_get_column_data($row['opt_module_id']);
               
                
                
                $this->data['form_script'] .= '
                var '.$field_id.' = new ej.querybuilder.QueryBuilder({
                columns: '.json_encode($columnData).',
                '.$readonly_script.'
                created: function(e){';
                if($value > ''){
                    $value = str_replace('"',"'",$value);
                $this->data['form_script'] .= '
                try{
                    this.setRulesFromSql("'.$value.'");
                }catch(e){}
                ';
                }
                $this->data['form_script'] .= '},
                change: function(e){
                    var sql = this.getSqlFromRules(this.getRules());
                    console.log(sql);
                    $("#'.$field_id.'").val(sql);
                },
                });
                '.$field_id.'.appendTo("#'.$field_id.'qb");
                 console.log('.$field_id.');
                ';
                }
                break;
            case 'boolean':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'disabled: true,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.disabled = true;';
                $control_js_readonly_disabled = $field_id.'.disabled = false;';
              
                $change_script = '   change: function(){
               
					            $("#hidden'.$field_id.'").val('.$field_id.'.checked).trigger("change");
					        },';
                if ($cell_editor) {
                    if (!empty($value) && $value && $value !== 'false') {
                    $checkbox = '{ '.$change_script.$readonly_script."  name: '".$field."', checked: true }";
                    } else {
                    $checkbox = '{ '.$change_script.$readonly_script." name: '".$field."' }";
                    }
                }else{
                    if (!empty($value) && $value && $value !== 'false') {
                    $checkbox = '{ '.$change_script.$readonly_script." label: '', name: '".$field."', checked: true }";
                    } else {
                    $checkbox = '{ '.$change_script.$readonly_script." label: '', name: '".$field."' }";
                    }
                }
               
                $field_html .= '
                <div class="form-check">
                <input class="form-check-input" type="checkbox"  name="'.$field.'" id="'.$field_id.'" >
                </div>';
                
                $this->data['form_script'] .= '
    				ej.base.enableRipple(true);
    				
    				var '.$field_id.' = new ej.buttons.CheckBox('.$checkbox.');
    				
    				// Render initialized CheckBox.
    				'.$field_id.'.appendTo("#'.$field_id.'");
				';
			
            break;
            case 'float':
                $show_tooltip = true;
                $readonly_script = (!empty($readonly) && $readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $field_html .= "<input name='".$field."' id='".$field_id."'>";
                $format = '###########.00';
                $precision = 2;
                $val = currency($value);

                $this->data['form_script'] .= '
				    var '.$field_id.' = new ej.inputs.NumericTextBox({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script."
						format: '".$format."',
						showSpinButton: false,
						decimals: ".$precision.",
						value: '".$val."',
						change: function(e){
					
					       $('#hidden".$field_id."').val(".$field_id.".value).trigger('change');
					   },
				    });
				    ".$field_id.".appendTo('#".$field_id."');
			    ";
            break;
            case 'currency':
                $show_tooltip = true;
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';
                $readonly_script = (!empty($readonly) && $readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $field_html .= "<input name='".$field."' id='".$field_id."'>";
                $format = 'R ###########.00';



                $precision = 2;
                $val = currency($value);

                $currency = 'ZAR';
                $usd_ratehsheet = false;

                if ($this->data['module_id'] == 588) {
                    $format = 'R ###########.000';
                    if (!empty($row['erp']) && $row['erp'] == 'turnkey') {
                        $usd_ratehsheet = true;
                    }
                }

                if ($usd_ratehsheet || $currency == 'USD') {
                    $val = currency_usd($value);
                    $format = '$ ###########.000';
                    $precision = 3;
                    $val = $value;
                }
/*
                if ($currency_decimals > 2) {
                    $val = currency_usd($value);
                    $format = '$ ###########.000';
                    for ($i=0;$i<$currency_decimals;$i++) {
                        $format.='0';
                    }

                    $precision = $currency_decimals;
                    $val = $value;
                }
*/
                $this->data['form_script'] .= '
				    var '.$field_id.' = new ej.inputs.NumericTextBox({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script."
						format: '".$format."',
						showSpinButton: false,
						decimals: ".$precision.",
						value: '".$val."',
						change: function(e){
						if(e.isInteracted){";
                        if (!empty($change_logic)) {
                            if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                        
                        '.$field_id.'change_function();';
                            }
                        }

                        $this->data['form_script'] .= "	
						}
    					       $('#hidden".$field_id."').val(".$field_id.".value).trigger('change');
    					   },
				    });
				    ".$field_id.".appendTo('#".$field_id."');
			    ";
            break;
            case 'integer':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'int.enabled = false;';
                $control_js_readonly_disabled = $field_id.'int.enabled = true;';
              
                $field_html = "<input name='".$field."' id='".$field_id."'>";
                 
                $this->data['form_script'] .= '
				    var '.$field_id.' = new ej.inputs.NumericTextBox({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script."
						format: '###########',
						value: '".$value."',
						created: function(e){
						    $('#hidden".$field_id."').trigger('change');
						},
						change: function(e){
						if(e.isInteracted){";
                        if (!empty($change_logic)) {
                            if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                        
                        '.$field_id.'change_function();';
                            }
                        }

                        $this->data['form_script'] .= "	
						}
    					       $('#hidden".$field_id."').val(".$field_id.".value).trigger('change');
    					   },
    				    });
				    ".$field_id.".appendTo('#".$field_id."');
			    ";
            break;
            case 'date':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'datepicker.enabled = false;';
                $control_js_readonly_disabled = $field_id.'datepicker.enabled = true;';

                if ($value == 'now') {
                    $value = date('Y-m-d');
                }
                 
                $field_html.= "<input class='e-input datefield' type='text' name='".$field."' id='".$field_id."'>";
                
                $this->data['form_script'] .= '
				    var '.$field_id.' = new ej.calendars.DatePicker({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script."
				    	format: 'yyyy-MM-dd',
						value: '".$value."',
				    });
				    ".$field_id.".appendTo('#".$field_id."');
				";
            break;
            case 'datetime':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'datetimepicker.enabled = false;';
                $control_js_readonly_disabled = $field_id.'datetimepicker.enabled = true;';
                if ($value == 'now') {
                    $value = date('Y-m-d');
                }
                  
                $field_html .= "<input class='e-input datefield' type='text' name='".$field."' id='".$field_id."'>";
                
                $this->data['form_script'] .= '
				    var '.$field_id.' = new ej.calendars.DateTimePicker({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script."
				    	format: 'yyyy-MM-dd HH:mm',";
				if($field == 'commitment_time'){
				    $this->data['form_script'] .= "
                    //sets the min
                    min: new Date('".date('Y-m-d H:i')."'),
                    //sets the max
                    step: 1,
                    ";
				}
				    
				    	
				 $this->data['form_script'] .= "value: '".$value."',
			    	});
				    ".$field_id.".appendTo('#".$field_id."');
				";
            break;
            case 'time':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'datetimepicker.enabled = false;';
                $control_js_readonly_disabled = $field_id.'datetimepicker.enabled = true;';
                if ($value == 'now') {
                    $value = date('Y-m-d H:i');
                }
                $field_html .= "<input class='e-input datefield' type='text' name='".$field."' id='".$field_id."'>";
                $this->data['form_script'] .= '
				    var '.$field_id.' = new ej.calendars.TimePicker({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script."
				    	format: 'HH:mm',
				    	step: 15,
						value: '".$value."',
			    	});
				    ".$field_id.".appendTo('#".$field_id."');
				";
            break;
            case 'textarea_count':
                $show_tooltip = false;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';
                $field_html .= "
    				<textarea class='e-input' style='margin-right:30px' rows='10' name='".$field."' id='".$field_id."'>$value</textarea>
    				<span id='".$field_id."count' class='charcount'></span>
				";


                $this->data['form_script'] .= '
					var '.$field_id.' = new ej.inputs.TextBox({
					        cssClass: "form-control form-control-sm",
					        change: function(){
					       
					            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");
					            
					        },
					        created: function(){
					            countChars($("#'.$field_id.'"));
					        },
						'.$readonly_script.'
				        floatLabelType: "Auto",
				        placeholder: "'.$tooltip.'",
				    });
					'.$field_id.'.appendTo("#'.$field_id.'");
					
					
				';

                $this->data['form_script'] .= "	$('#".$field_id."').on('input propertychange paste keyup change', function() {
                   
                    countChars(this);
                });";
            break;
            case 'textarea':
                $show_tooltip = false;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';
                $field_html .= "<textarea class='e-input' style='margin-right:30px' rows='10' name='".$field."' id='".$field_id."' >$value</textarea>";


                $this->data['form_script'] .= '
					var '.$field_id.' = new ej.inputs.TextBox({
					        cssClass: "form-control form-control-sm",
					        change: function(){
					       
					            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");
					            
					        },
						'.$readonly_script.'
				        floatLabelType: "Auto",
				        placeholder: "'.$tooltip.'",
				    });
					'.$field_id.'.appendTo("#'.$field_id.'");
				';
            break;
            case 'icon':

                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

               


                 $field_html .= "<input id='".$field_id."' name='".$field."' type='text'  value='".$value."'/>";
                 $this->data['form_script'] .= "setIconPicker('".$field_id."','".$value."')";
               
                break;
           case 'image':

                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

              
                $module_name = strtolower(str_replace(' ', '_', $this->data['name']));

                $files = explode(',', $value);
                foreach ($files as $file_id => $file) {
                    if (!empty($file) && file_exists(uploads_path($this->data['module_id']).$file)) {
                       
                        $args = [
                            'module_id' => $this->data['module_id'],
                            'table' => $this->data['db_table'],
                            'field' => $field,
                            'id' => $row[$this->data['db_key']],
                            'value' => $file,
                        ];


                        $field_html .= '<img src="'.uploads_url($this->data['module_id']).$file.'" id="img'.$this->data['module_id'].$field_id.$file_id.'" border="0" style="max-width:200px; max-height:100px"/><br>';



                        if ($field!='logo') {
                            if ('view' != $this->data['edit_type']) {
                                $delete_url = url('/delete_module_file/'.\Erp::encode($args));
                                $field_html .= '<a href="javascript:void(0)" data-attr-delete-url="'.$delete_url.'" data-attr-file="img'.$this->data['module_id'].$field_id.$file_id.'" class="e-btn form-control form-control-sm delete-form-image"><b>Delete File</b></a><br><br>';
                            }
                        }
                    }
                }

                $field_html .= "<input type='file' id='".$field_id."' name='".$field."'/>";
                $multiple = ($module_field['opts_multiple']) ? 'true' : 'false';
                $this->data['form_script'] .= '
					var '.$field_id."dropElement = document.getElementsByClassName('control-fluid')[0];
					// Initialize the uploader component
					var ".$field_id.' = new ej.inputs.Uploader({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script.'
			            autoUpload: false,';
                        if (($module_field['opts_multiple'])) {
                            $this->data['form_script'] .= "  htmlAttributes: {name: '".$field."[]'}, ";
                            $this->data['form_script'] .= 'multiple: true,';
                        } else {
                            $this->data['form_script'] .= ' multiple: false,';
                        }

                $this->data['form_script'] .= 'dropArea: '.$field_id.'dropElement
					});
					'.$field_id.".appendTo('#".$field_id."');
				";
                break;
            case 'file':

                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

              
                $module_name = strtolower(str_replace(' ', '_', $this->data['name']));



                $files = explode(',', $value);
                foreach ($files as $file_id => $file) {
                    if (!empty($file) && file_exists(uploads_path($this->data['module_id']).$file)) {
                        $file_id = $row[$this->data['db_key']];
                        $args = [
                            'module_id' => $this->data['module_id'],
                            'table' => $this->data['db_table'],
                            'field' => $field,
                            'id' => $row[$this->data['db_key']],
                            'value' => $file,
                        ];


                        $field_html .= '<a target="new" href="'.uploads_url($this->data['module_id']).$file.'" id="file'.$this->data['module_id'].$field_id.$file_id.'" class="e-btn form-control form-control-sm mr-2"> '.$file.' </a>';



                        if ($field!='logo') {
                            if ('view' != $this->data['edit_type']) {
                                $delete_url = url('/delete_module_file/'.\Erp::encode($args));
                                $field_html .= '<a href="javascript:void(0)" data-attr-delete-url="'.$delete_url.'" data-attr-file="file'.$this->data['module_id'].$field_id.$file_id.'" class="e-btn form-control form-control-sm delete-form-file"><b>Delete File</b></a><br><br>';
                            }
                        }
                    }
                }

                $field_html .= "<input type='file' id='".$field_id."' name='".$field."'/>";
                $multiple = ($module_field['opts_multiple']) ? 'true' : 'false';
                $this->data['form_script'] .= '
					var '.$field_id."dropElement = document.getElementsByClassName('control-fluid')[0];
					// Initialize the uploader component
					var ".$field_id.' = new ej.inputs.Uploader({
			            cssClass: "form-control form-control-sm",
				    	'.$readonly_script.'
			            autoUpload: false,';
                        if (($module_field['opts_multiple'])) {
                            $this->data['form_script'] .= "  htmlAttributes: {name: '".$field."[]'}, ";
                            $this->data['form_script'] .= 'multiple: true,';
                        } else {
                            $this->data['form_script'] .= ' multiple: false,';
                        }

                $this->data['form_script'] .= 'dropArea: '.$field_id.'dropElement
					});
					'.$field_id.".appendTo('#".$field_id."');
				";
                break;

            case 'select_module':
                /*
                $use_remote_data_binding = true;
                if(is_dev()){
                    
                $use_remote_data_binding = false;
                }
                */
                $use_remote_data_binding = false;
                if($use_remote_data_binding){
                $value_found = false;
                if (empty($val) && !empty(request()->{$field})) {
                    $val = request()->{$field};
                }
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';

                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $datasource = [];
                $custom_value = $value;

                if ($module_field['opts_multiple']) {
                    
                    $field_html = '<input name="'.$field.'[]" id="'.$field_id.'" class="multiselect">';
                    

                    $this->data['form_script'] .= '
                    
                        var '.$field_id.'ds =    new ej.data.DataManager({
                            url: "/syncfusion_select_options/'.$id.'",
                            adaptor: new ej.data.UrlAdaptor
                        });
                    
                    
                        var '.$field_id.' = new  ej.dropdowns.MultiSelect({
                        dataSource: '.$field_id.'ds,
                        fields: {text: "text", value: "value"},
                        popupWidth: "400px",
                        popupHeight: "200px",
						created: function(e){
						    $("#hidden'.$field_id.'").trigger("change");
						},
                        '.$readonly_script."
                        mode: 'CheckBox',
                        showSelectAll: true,
                        selectAllText: 'Select All',";
                    if (!empty($value)) {
                        $this->data['form_script'] .= 'value: '.json_encode(explode(',', $value)).',';
                    }
                    $this->data['form_script'] .= "hideSelectedItem:false,
                        htmlAttributes: {name: '".$field."[]'}, 
                        });
                        ".$field_id.".appendTo('#".$field_id."');
                    ";
                } else {
                  
                    $field_html .= '<input name="'.$field.'" id="'.$field_id.'">';
                     
                    $this->data['form_script'] .= '
                    var '.$field_id.'ds =    new ej.data.DataManager({
                        url: "/syncfusion_select_options/'.$id.'",
                        adaptor: new ej.data.UrlAdaptor
                    });
                    
                    
                    var '.$field_id.' = new ej.dropdowns.DropDownList({
                        htmlAttributes: {name: "'.$field.'"}, 
			            cssClass: "form-control form-control-sm",';
                    
                    $this->data['form_script'] .= '
                    ignoreAccent: true,
                    allowFiltering: true,
					created: function(e){
					    setTimeout(function () {$("#hidden'.$field_id.'").val("'.$value.'").trigger("change")},300);
					},';
                    $this->data['form_script'] .= "             
                        filtering: function(e){
                        if(e.text == ''){
                        e.updateData(".$field_id.".dataSource);
                        }else{ 
                        var query = new ej.data.Query().select(['text','value']);
                        query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                        e.updateData(".$field_id.'.dataSource, query);
                        }
                        },
                    ';


                    if ('table_name527' == $field_id || 'table_name541' == $field_id) {
                        $this->data['form_script'] .= "
                        created: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";
                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                            
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                       
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },
                        change: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";

                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },";
                    } else {
                        $this->data['form_script'] .= 'change: function(e){
                            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");';

                        if (!empty($change_logic)) {
                            if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                        
                        '.$field_id.'change_function();';
                            }
                        }


                        $this->data['form_script'] .= ' },';
                    }

                    $this->data['form_script'] .= '
                    dataSource:  '.$field_id.'ds,
                    fields: {text: "text", value: "value"},
				    popupWidth: "400px",
                    popupHeight: "200px",
                    ';

                    $this->data['form_script'] .= $readonly_script;
                    if (!empty($value)) {
                        $this->data['form_script'] .= "
                    value: '".$value."',";
                    }

                    $this->data['form_script'] .= '
                    });
                    '.$field_id.".appendTo('#".$field_id."');
                    ";
                }
                    
                }else{
                $value_found = false;
                if (empty($val) && !empty(request()->{$field})) {
                    $val = request()->{$field};
                }
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';

                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $datasource = [];
                $custom_value = $value;
                
                if ('account_id' == $field) {
                    $datasource[] = (object) ['text' => '', 'value' => (string) '0'];
                } else {
                    if (!$module_field['opts_multiple']) {
                        $datasource[] = (object) ['text' => '', 'value' => (string) ''];
                    }
                }

                $conn = \DB::connection('default')->table('erp_cruds')->where('id', $this->data['module_id'])->pluck('connection')->first();
                $display_values = explode(',', $module_field['opt_db_display']);
                $tables = get_tables_from_schema();
                if (!in_array($module_field['opt_db_table'], $tables)) {
                    return false;
                }
                $select_query = \DB::connection($conn)->table($module_field['opt_db_table']);
                $select_fields = $display_values;
                $select_fields[] = $module_field['opt_db_key'];

                $select_query->select($select_fields);
                if ($module_field['opt_db_where']) {
                    $where = $module_field['opt_db_where'];
                    // if ('add' != $this->data['edit_type']) {
                    if (empty($row['module_id'])) {
                        $row['module_id'] = 0;
                    }
                    if ($this->data['module_id'] == 760 && $field == 'report_ids') {
                        if ($this->data['report_filter_conn'] == session('instance')->db_connection) {
                            $where = "connection='default' or connection='pbx' or connection='pbx_cdr'";
                        } elseif ($this->data['report_filter_conn']) {
                            $where = "connection='".$this->data['report_filter_conn']."'";
                        } elseif (empty($this->data['report_filter_conn']) && !empty($row['company_id'])) {
                            $report_conn = \DB::connection('system')->table('erp_instances')->where('id', $row['company_id'])->pluck('db_connection')->first();
                            if ($report_conn == session('instance')->db_connection) {
                                $where = "connection='default' or connection='pbx' or connection='pbx_cdr'";
                            } else {
                                $where = "connection='".$report_conn."'";
                            }
                        } elseif (empty($this->data['report_filter_conn'])) {
                            $where = '';
                        }
                    }

                    $where = view(['template' => $where])->with($row)->render();

                    // }
                    $where = str_replace('[module_id]', $this->data['module_id'], $where);
                    foreach (session()->all() as $k => $v) {
                        if (!is_object($v)) {
                            if ($field=='sms_list_id' && $v == 1) {
                                $v = 12;
                            }
                            $where = str_replace('[session_'.$k.']', $v, $where);
                        }
                    }

                    $select_query->whereRaw($where);
                }

                if (!empty($conf['account_filter'])) {
                    $filter_val = session('account_id');
                    if ('crm_pricelists'  == $module_field['opt_db_table']) {
                        $columns = get_columns_from_schema($module_field['opt_db_table']);
                        if (in_array('partner_id', $columns) && 11 == session('role_id')) {
                            $select_query->where('partner_id', $filter_val);
                        }
                    } else {
                        if (!empty(session('sms_account_id'))) {
                            $filter_val = session('sms_account_id');
                        } else {
                            $filter_val = session('account_id');
                        }
                        $columns = get_columns_from_schema($module_field['opt_db_table']);
                        if (in_array('partner_id', $columns) && 21 != session('role_id')) {
                            $select_query->where('partner_id', $filter_val);
                        } elseif (in_array('account_id', $columns)) {
                            $select_query->where('account_id', $filter_val);
                        }
                    }
                }


                if (!empty($module_field['opt_db_sortorder'])) {
                    $orderbys = explode(',', $module_field['opt_db_sortorder']);
                    foreach ($orderbys as $sort) {
                        if (!empty($sort)) {
                            $select_query->orderby(trim($sort));
                        }
                    }
                } elseif (!empty($display_values[0])) {
                    $select_query->orderby($display_values[0]);
                }

                if (!empty($conf['unique_filter'])) {
                    $select_query->orderby($module_field['opt_db_key']);
                }

                //$sql = querybuilder_to_sql($select_query);

                $select_list = $select_query->get();

                if (!empty($module_field['opt_db_filter_function'])){
                    $opt_db_filter_function = \DB::connection('default')->table('erp_form_events')->where('id',$module_field['opt_db_filter_function'])->pluck('function_name')->first();
                    if(!empty($opt_db_filter_function) && function_exists($opt_db_filter_function)) {
                        $select_list = $opt_db_filter_function($select_list, $row, $module_field['field']);
                    }
                }

                foreach ($select_list as $list_item) {
                    $option_label = '';
                    foreach ($display_values as $display_value) {
                        $list_label = $list_item->{$display_value};

                        if ('account_id' == $display_value) {
                            $account = dbgetaccount($list_label);
                            $list_label = $account->company;
                        } elseif (str_ends_with($display_value, '_id')) {
                            $list_label = $this->getJoinDisplayField($module_field['opt_db_table'], $display_value, $list_label);
                        }
                        $option_label .= $list_label.' - ';
                    }
                    $option_label = rtrim($option_label, ' - ');
                    $datasource[] = (object) ['text' => $option_label, 'value' => (string) $list_item->{$module_field['opt_db_key']}];
                    if (!$module_field['opts_multiple'] && !empty($value) && $value == $list_item->{$module_field['opt_db_key']}) {
                        $value_found = true;
                    }
                }

                if (!$module_field['opts_multiple']) {
                    if (!empty($value)) {
                        $val_found = false;

                        foreach ($datasource as $opt) {
                            if ($opt->value == $value) {
                                $val_found = true;
                            }
                        }

                        if (!$val_found) {
                            $value = '';
                        }
                    }
                    if (!$module_field['opts_multiple'] && !empty($value) && !$value_found) {
                        $value = '';
                    }
                }



                  

                if ($module_field['opts_multiple']) {
                    
                    $field_html = '<input name="'.$field.'[]" id="'.$field_id.'" class="multiselect">';
                    

                    $this->data['form_script'] .= '
                        var '.$field_id.' = new  ej.dropdowns.MultiSelect({
                        dataSource: '.json_encode($datasource).',
                        fields: {text: "text", value: "value"},
                        popupWidth: "400px",
                        popupHeight: "200px",
						created: function(e){
						    $("#hidden'.$field_id.'").trigger("change");
						},
                        '.$readonly_script."
                        mode: 'CheckBox',
                        showSelectAll: true,
                        selectAllText: 'Select All',";
                    if (!empty($value)) {
                        $this->data['form_script'] .= 'value: '.json_encode(explode(',', $value)).',';
                    }
                    $this->data['form_script'] .= "hideSelectedItem:false,
                        htmlAttributes: {name: '".$field."[]'}, 
                        });
                        ".$field_id.".appendTo('#".$field_id."');
                    ";
                } else {
                  
                    $field_html .= '<input name="'.$field.'" id="'.$field_id.'">';
                     
                        $this->data['form_script'] .= '
                    var '.$field_id.' = new ej.dropdowns.DropDownList({
                        htmlAttributes: {name: "'.$field.'"}, 
			            cssClass: "form-control form-control-sm",';
                    
                    $this->data['form_script'] .= '
                    ignoreAccent: true,
                    allowFiltering: true,
					created: function(e){
					    setTimeout(function () {$("#hidden'.$field_id.'").val("'.$value.'").trigger("change")},300);
					},';
                    $this->data['form_script'] .= "             
                        filtering: function(e){
                        if(e.text == ''){
                        e.updateData(".$field_id.".dataSource);
                        }else{ 
                        var query = new ej.data.Query().select(['text','value']);
                        query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                        e.updateData(".$field_id.'.dataSource, query);
                        }
                        },
                    ';


                    if ('table_name527' == $field_id || 'table_name541' == $field_id) {
                        $this->data['form_script'] .= "
                        created: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";
                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                            
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                       
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },
                        change: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";

                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },";
                    } else {
                        $this->data['form_script'] .= 'change: function(e){
                            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");';

                        if (!empty($change_logic)) {
                            if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                        
                        '.$field_id.'change_function();';
                            }
                        }


                        $this->data['form_script'] .= ' },';
                    }

                    $this->data['form_script'] .= '
                    dataSource: '.json_encode($datasource).',
                    fields: {text: "text", value: "value"},
				    popupWidth: "400px",
                    popupHeight: "200px",
                    ';

                    $this->data['form_script'] .= $readonly_script;
                    if (!empty($value)) {
                        $this->data['form_script'] .= "
                    value: '".$value."',";
                    }

                    $this->data['form_script'] .= '
                    });
                    '.$field_id.".appendTo('#".$field_id."');
                    ";
                }
                }
                break;


            case 'select_connections':

                $value_found = false;
                if (empty($val) && !empty(request()->{$field})) {
                    $val = request()->{$field};
                }
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';

                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $datasource = [];
                $custom_value = $value;

                if ('account_id' == $field) {
                    $datasource[] = (object) ['text' => '', 'value' => (string) '0'];
                } else {
                    if (!$module_field['opts_multiple']) {
                        $datasource[] = (object) ['text' => '', 'value' => (string) ''];
                    }
                }

                    $conns = get_db_connections();

                    foreach ($conns as $conn) {
                        $datasource[] = (object) ['text' => $conn, 'value' => (string) $conn];
                        if (!$module_field['opts_multiple'] && !empty($value) && $value == $conn) {
                            $value_found = true;
                        }
                    }
                    if (!$module_field['opts_multiple'] && !empty($value) && !$value_found) {
                        $value = '';
                    }



                

                if ($module_field['opts_multiple']) {
                    $field_html = '<input name="'.$field.'[]" id="'.$field_id.'" class="multiselect">';

                    $this->data['form_script'] .= '
                        var '.$field_id.' = new  ej.dropdowns.MultiSelect({
                        dataSource: '.json_encode($datasource).',
                        fields: {text: "text", value: "value"},
                        popupWidth: "400px",
                        popupHeight: "200px",
						created: function(e){
						    $("#hidden'.$field_id.'").trigger("change");
						},
                        '.$readonly_script."
                        mode: 'CheckBox',
                        showSelectAll: true,
                        selectAllText: 'Select All',";
                    if (!empty($value)) {
                        $this->data['form_script'] .= 'value: '.json_encode(explode(',', $value)).',';
                    }
                    $this->data['form_script'] .= "hideSelectedItem:false,
                        htmlAttributes: {name: '".$field."[]'}, 
                        });
                        ".$field_id.".appendTo('#".$field_id."');
                    ";
                } else {
                    $field_html = ' <input name="'.$field.'" id="'.$field_id.'">';
                   
                   
                        $this->data['form_script'] .= '
                    var '.$field_id.' = new ej.dropdowns.DropDownList({
			            cssClass: "form-control form-control-sm",
                        htmlAttributes: {name: "'.$field.'"}, ';
                    
                    $this->data['form_script'] .= '
                    ignoreAccent: true,
                    allowFiltering: true,
					created: function(e){
					    setTimeout(function () {$("#hidden'.$field_id.'").val("'.$value.'").trigger("change")},300);
					},';
                    $this->data['form_script'] .= "             
                        filtering: function(e){
                        if(e.text == ''){
                        e.updateData(".$field_id.".dataSource);
                        }else{ 
                        var query = new ej.data.Query().select(['text','value']);
                        query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                        e.updateData(".$field_id.'.dataSource, query);
                        }
                        },
                    ';

                    if ('table_name527' == $field_id || 'table_name541' == $field_id) {
                        $this->data['form_script'] .= "
                        created: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";
                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                            
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                       
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },
                        change: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";

                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },";
                    } else {
                        $this->data['form_script'] .= 'change: function(e){
                            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");';

                        if (!empty($change_logic)) {
                            if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                        
                        '.$field_id.'change_function();';
                            }
                        }


                        $this->data['form_script'] .= ' },';
                    }

                    $this->data['form_script'] .= '
                    dataSource: '.json_encode($datasource).',
                    fields: {text: "text", value: "value"},
				    popupWidth: "400px",
                    popupHeight: "200px",
                    ';

                    $this->data['form_script'] .= $readonly_script;
                    if (!empty($value)) {
                        $this->data['form_script'] .= "
                    value: '".$value."',";
                    }

                    $this->data['form_script'] .= '
                    });
                    '.$field_id.".appendTo('#".$field_id."');
                    ";
                }

                break;


            case 'select_tables':

                $value_found = false;
                if (empty($val) && !empty(request()->{$field})) {
                    $val = request()->{$field};
                }
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';

                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $datasource = [];
                $custom_value = $value;

                if ('account_id' == $field) {
                    $datasource[] = (object) ['text' => '', 'value' => (string) '0'];
                } else {
                    if (!$module_field['opts_multiple']) {
                        $datasource[] = (object) ['text' => '', 'value' => (string) ''];
                    }
                }

                    if (!empty($row) && !empty($row['connection'])) {
                        $tables = get_tables_from_schema($row['connection']);
                    } else {
                        $tables = get_tables_from_schema();
                    }
                    foreach ($tables as $table) {
                        $datasource[] = (object) ['text' => $table, 'value' => (string) $table];

                        if (!$module_field['opts_multiple'] && !empty($value) && $value == $table) {
                            $value_found = true;
                        }
                    }
                    if (!$module_field['opts_multiple'] && !empty($value) && !$value_found) {
                        $value = '';
                    }



                

                if ($module_field['opts_multiple']) {
                    $field_html = '<input name="'.$field.'[]" id="'.$field_id.'" class="multiselect">';

                    $this->data['form_script'] .= '
                        var '.$field_id.' = new  ej.dropdowns.MultiSelect({
                        dataSource: '.json_encode($datasource).',
                        fields: {text: "text", value: "value"},
                        popupWidth: "400px",
                        popupHeight: "200px",
						created: function(e){
						    $("#hidden'.$field_id.'").trigger("change");
						},
                        '.$readonly_script."
                        mode: 'CheckBox',
                        showSelectAll: true,
                        selectAllText: 'Select All',";
                    if (!empty($value)) {
                        $this->data['form_script'] .= 'value: '.json_encode(explode(',', $value)).',';
                    }
                    $this->data['form_script'] .= "hideSelectedItem:false,
                        htmlAttributes: {name: '".$field."[]'}, 
                        });
                        ".$field_id.".appendTo('#".$field_id."');
                    ";
                } else {
                    $field_html = '<input name="'.$field.'" id="'.$field_id.'">';
                   
                    
                        $this->data['form_script'] .= '
                    var '.$field_id.' = new ej.dropdowns.DropDownList({
			            cssClass: "form-control form-control-sm",
                        htmlAttributes: {name: "'.$field.'"}, ';
                    
                    $this->data['form_script'] .= '
                    htmlAttributes: {name: "'.$field.'"}, 
                    ignoreAccent: true,
                    allowFiltering: true,
					created: function(e){
					    setTimeout(function () {$("#hidden'.$field_id.'").val("'.$value.'").trigger("change")},300);
					},';
                    $this->data['form_script'] .= "  
                    
       
                        filtering: function(e){
                     
                        if(e.text == ''){
                        e.updateData(".$field_id.".dataSource);
                        }else{ 
                        var query = new ej.data.Query().select(['text','value']);
                        query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                      
                        e.updateData(".$field_id.".dataSource, query);
                        }
                        },
                    ";


                    $this->data['form_script'] .= 'change: function(e){
                        $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");';

                    if (!empty($change_logic)) {
                        if (str_starts_with($change_logic, 'function|')) {
                            $this->data['form_script'] .= '
                    
                    '.$field_id.'change_function();';
                        }
                    }


                    $this->data['form_script'] .= ' },';


                    $this->data['form_script'] .= '
                    dataSource: '.json_encode($datasource).',
                    fields: {text: "text", value: "value"},
				    popupWidth: "400px",
                    popupHeight: "200px",
                    ';

                    $this->data['form_script'] .= $readonly_script;
                    if (!empty($value)) {
                        $this->data['form_script'] .= "
                    value: '".$value."',";
                    }

                    $this->data['form_script'] .= '
                    });
                    '.$field_id.".appendTo('#".$field_id."');
                    ";
                }

                break;


            case 'select_function':

                $value_found = false;
                if (empty($val) && !empty(request()->{$field})) {
                    $val = request()->{$field};
                }
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';

                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $datasource = [];
                $custom_value = $value;

                if ('account_id' == $field) {
                    $datasource[] = (object) ['text' => '', 'value' => (string) '0'];
                } else {
                    if (!$module_field['opts_multiple']) {
                        $datasource[] = (object) ['text' => '', 'value' => (string) ''];
                    }
                }
                if (!$module_field['opts_function']) {
                    return '';
                }

                    $options = $module_field['opts_function']($row,$this->data['connection']);

                    if (!array_is_assoc($options)) {
                        foreach ($options as $text) {
                            $datasource[] = (object) ['text' => $text, 'value' => (string) $text];


                            if (!$module_field['opts_multiple'] && !empty($value) && $value == $text) {
                                $value_found = true;
                            }
                        }
                        if (!$module_field['opts_multiple'] && !empty($value) && !$value_found) {
                            $value = '';
                        }
                    } else {
                        foreach ($options as $key => $text) {
                            $datasource[] = (object) ['text' => $text, 'value' => (string) $key];


                            if (!$module_field['opts_multiple'] && !empty($value) && $value == $key) {
                                $value_found = true;
                            }
                        }
                        if (!$module_field['opts_multiple'] && !empty($value) && !$value_found) {
                            $value = '';
                        }
                    }



                   

                if ($module_field['opts_multiple']) {
                    $field_html = '<input name="'.$field.'[]" id="'.$field_id.'" class="multiselect">';

                    $this->data['form_script'] .= '
                        var '.$field_id.' = new  ej.dropdowns.MultiSelect({
                        dataSource: '.json_encode($datasource).',
                        fields: {text: "text", value: "value"},
                        popupWidth: "400px",
                        popupHeight: "200px",
						created: function(e){
						    $("#hidden'.$field_id.'").trigger("change");
						},
                        '.$readonly_script."
                        mode: 'CheckBox',
                        showSelectAll: true,
                        selectAllText: 'Select All',";
                    if (!empty($value)) {
                        $this->data['form_script'] .= 'value: '.json_encode(explode(',', $value)).',';
                    }
                    $this->data['form_script'] .= "hideSelectedItem:false,
                        htmlAttributes: {name: '".$field."[]'}, 
                        });
                        ".$field_id.".appendTo('#".$field_id."');
                    ";
                } else {
                    $field_html = '<input name="'.$field.'" id="'.$field_id.'">';
                   
                  
                        $this->data['form_script'] .= '
                    var '.$field_id.' = new ej.dropdowns.DropDownList({
			            cssClass: "form-control form-control-sm",
                        htmlAttributes: {name: "'.$field.'"}, ';
                    
                    $this->data['form_script'] .= '
                    ignoreAccent: true,
                    allowFiltering: true,
					created: function(e){
					    setTimeout(function () {$("#hidden'.$field_id.'").val("'.$value.'").trigger("change")},300);
					},';
                    $this->data['form_script'] .= "             
                        filtering: function(e){
                        if(e.text == ''){
                        e.updateData(".$field_id.".dataSource);
                        }else{ 
                        var query = new ej.data.Query().select(['text','value']);
                        query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                        e.updateData(".$field_id.'.dataSource, query);
                        }
                        },
                    ';

                    if ('table_name527' == $field_id || 'table_name541' == $field_id) {
                        $this->data['form_script'] .= "
                        created: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";
                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                            
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                       
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },
                        change: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";

                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },";
                    } else {
                        $this->data['form_script'] .= 'change: function(e){
                            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");';

                        if (!empty($change_logic)) {
                            if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                        
                        '.$field_id.'change_function();';
                            }
                        }


                        $this->data['form_script'] .= ' },';
                    }

                    $this->data['form_script'] .= '
                    dataSource: '.json_encode($datasource).',
                    fields: {text: "text", value: "value"},
				    popupWidth: "400px",
                    popupHeight: "200px",
                    ';

                    $this->data['form_script'] .= $readonly_script;
                    if (!empty($value)) {
                        $this->data['form_script'] .= "
                    value: '".$value."',";
                    }

                    $this->data['form_script'] .= '
                    });
                    '.$field_id.".appendTo('#".$field_id."');
                    ";
                }

                break;

            case 'select_custom':

                $value_found = false;
                if (empty($val) && !empty(request()->{$field})) {
                    $val = request()->{$field};
                }
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';

                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $datasource = [];
                $custom_value = $value;

                if ('account_id' == $field) {
                    $datasource[] = (object) ['text' => '', 'value' => (string) '0'];
                } else {
                    if (!$module_field['opts_multiple']) {
                        $datasource[] = (object) ['text' => '', 'value' => (string) ''];
                    }
                }

                    $select_list = explode(',', $module_field['opts_values']);
                    if(!empty($val) && !in_array($val,$select_list)){
                        $select_list[] = $val;
                    }
                    if(!empty($value) && !in_array($value,$select_list)){
                        $select_list[] = $value;
                    }
                    if (empty($select_list)) {
                        $select_list = [];
                    }
                    if ('table fields' != $select_list[0]) {
                        //natsort($select_list);
                        foreach ($select_list as $list_item) {
                            $list_item =  preg_replace('/^((?=^)(\s*))|((\s*)(?>$))/si', '', $list_item);
                            if ($field_id == 'action527') {
                                if (!empty($this->params->limit_tables) && !str_contains($list_item, 'table')) {
                                    continue;
                                }
                                if (!empty($this->params->limit_columns) && !str_contains($list_item, 'column')) {
                                    if ($list_item != 'table_rename') {
                                        continue;
                                    }
                                }
                            }
                            $datasource[] = (object) ['text' => str_replace('_', ' ', $list_item), 'value' => (string) $list_item];

                            if (!$module_field['opts_multiple'] && !empty($value) && $value == $list_item) {
                                $value_found = true;
                            }
                        }
                        if (!$module_field['opts_multiple'] && !empty($value) && !$value_found) {
                            $value = '';
                        }
                    }

                   
                    if($field == 'opts_values'){
                      
                        $select_list_row = explode(',', $row['opts_values']);
                        if (empty($select_list)) {
                            $select_list_row = [];
                        }
                        foreach($select_list_row as $slr){
                            $select_list[] = $slr;
                        }
                        
                    }
                    

                if ($module_field['opts_multiple']) {
                   
                    $field_html = '<input name="'.$field.'[]" id="'.$field_id.'" class="multiselect">';
                    

                    $this->data['form_script'] .= '
                        var '.$field_id.' = new  ej.dropdowns.MultiSelect({
                        dataSource: '.json_encode($datasource).',
                        fields: {text: "text", value: "value"},
                        popupWidth: "400px",
                        popupHeight: "200px",
						created: function(e){
						    $("#hidden'.$field_id.'").trigger("change");
						},
                        '.$readonly_script."
                        mode: 'CheckBox',
                        showSelectAll: true,
                        selectAllText: 'Select All',";
                    if (!empty($value)) {
                        $this->data['form_script'] .= 'value: '.json_encode(explode(',', $value)).',';
                    }
                    $this->data['form_script'] .= "hideSelectedItem:false,
                        htmlAttributes: {name: '".$field."[]'}, 
                        });
                        ".$field_id.".appendTo('#".$field_id."');
                    ";
                } else {
                   
                    $field_html = '<input name="'.$field.'" id="'.$field_id.'" >';
                    
               
             
                  
                        $this->data['form_script'] .= '
                    var '.$field_id.' = new ej.dropdowns.DropDownList({
			            cssClass: "form-control form-control-sm",
                        htmlAttributes: {name: "'.$field.'"}, ';
                    
                    $this->data['form_script'] .= '
                    ignoreAccent: true,
                    allowFiltering: true,
					created: function(e){
					    setTimeout(function () {$("#hidden'.$field_id.'").val("'.$value.'").trigger("change")},300);
					},';
                    $this->data['form_script'] .= "             
                        filtering: function(e){
                        if(e.text == ''){
                        e.updateData(".$field_id.".dataSource);
                        }else{ 
                        var query = new ej.data.Query().select(['text','value']);
                        query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                        e.updateData(".$field_id.'.dataSource, query);
                        }
                        },
                    ';

                    if ('table_name527' == $field_id || 'table_name541' == $field_id) {
                        $this->data['form_script'] .= "
                        created: function(e){
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";
                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                            
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                       
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },
                        change: function(e){
                       
                            if(table_name".$modref.".value == ''){
                                fields_datasource = [];
                                field_name".$modref.".value = '';
                                field_name".$modref.".dataSource = fields_datasource;
                                field_name".$modref.".enabled = true;
                                field_name".$modref.".dataBind();
                            }else{";

                        if (!empty(request()->connection)) {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value+'/".request()->connection."';";
                        } else {
                            $this->data['form_script'] .= "var table_url = '/module_manager/columnlist/".$this->data['module_id']."/'+table_name".$modref.".value;";
                        }
                        $this->data['form_script'] .= "
                                $.ajax({
                                    url: table_url,
                                    success: function(data){
                                        fields_datasource = data;
                                        
                                        field_name".$modref.".value = '';
                                        field_name".$modref.".dataSource = fields_datasource;
                                        field_name".$modref.".enabled = true;
                                        field_name".$modref.".dataBind();
                                    }
                                });
                            }
                        },";
                    } else {
                        $this->data['form_script'] .= 'change: function(e){
                            if(this.value == null){
                                this.value = "";
                            }
                            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");';

                        if (!empty($change_logic)) {
                            if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                        
                        '.$field_id.'change_function();';
                            }
                        }


                        $this->data['form_script'] .= ' },';
                    }

                    $this->data['form_script'] .= '
                    dataSource: '.json_encode($datasource).',
                    fields: {text: "text", value: "value"},
				    popupWidth: "400px",
                    popupHeight: "200px",
                    ';

                    $this->data['form_script'] .= $readonly_script;
                    if (!empty($value)) {
                        $this->data['form_script'] .= "
                    value: '".$value."',";
                    }

                    $this->data['form_script'] .= '
                    });
                    '.$field_id.".appendTo('#".$field_id."');
                    ";
                }

                break;
                
                
            case 'tags':

                $value_found = false;
                if (empty($val) && !empty(request()->{$field})) {
                    $val = request()->{$field};
                }
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';

                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $datasource = [];
                $custom_value = $value;

               

                $select_list = explode(',', $module_field['opts_values']);
                if (empty($select_list)) {
                    $select_list = [];
                }
                
               
                $select_list_row = explode(',', $row['opts_values']);
                if (empty($select_list)) {
                    $select_list_row = [];
                }
                foreach($select_list_row as $slr){
                    $select_list[] = $slr;
                }
                        
                    
                

                   
                $field_html = '<input name="'.$field.'[]" id="'.$field_id.'" class="multiselect">';
                

                $this->data['form_script'] .= '
                    var '.$field_id.' = new  ej.dropdowns.MultiSelect({
                    dataSource: '.json_encode($select_list).',
                    fields: {text: "text", value: "value"},
                    popupWidth: "400px",
                    popupHeight: "200px",
                    allowCustomValue: true,
					created: function(e){
					    $("#hidden'.$field_id.'").trigger("change");
					},
                    '.$readonly_script."
                    mode: 'Box',
                    showSelectAll: true,
                    selectAllText: 'Select All',";
                if (!empty($value)) {
                    $this->data['form_script'] .= 'value: '.json_encode(explode(',', $value)).',';
                }
                $this->data['form_script'] .= "hideSelectedItem:false,
                    htmlAttributes: {name: '".$field."[]'}, 
                    });
                    ".$field_id.".appendTo('#".$field_id."');
                ";
               
                break;

            case 'textarea_code':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL."e_{$field}.setReadOnly(true);".PHP_EOL : '';

                if ('0' != $required) {
                    $mandatory = 'required';
                }

                $field_html = "<textarea class='form-control form-control-sm input-sm editor {$extend_class}' name='{$field}' rows='25' id='{$field}'
    				{$mandatory}{$attribute} style='display: none;'>".$value."</textarea>
    				<div id='e_{$field}'></div>";

                $this->data['form_script'] .= "
    				var e_{$field} = ace.edit('e_{$field}');
    				e_{$field}.setTheme('ace/theme/textmate');
    				e_{$field}.session.setMode({path:\"ace/mode/php\", inline:true})
    				e_{$field}.setAutoScrollEditorIntoView(true);
    				e_{$field}.setOption('maxLines', 30);
    				e_{$field}.setOption('minLines', 10);
    				e_{$field}.setOption('minLines', 10);
    				".$readonly_script."
    				$('#{$field}').hide();
    				var t_{$field} = $('#{$field}');
    				e_{$field}.getSession().setValue(t_{$field}.val());
    				e_{$field}.getSession().on('change', function(){
    				t_{$field}.val(e_{$field}.getSession().getValue());
    				});
				";
                break;

            case 'textarea_editor':
                $show_tooltip = true;
                $this->form_uses_tinymce = true;
                $value = str_replace('`', "'", $value);
                if ('0' != $required) {
                    $mandatory = 'required';
                }
                if ('view' == $this->data['edit_type']) {
                    $field_html = $value;
                } else {
                    $field_html = "<textarea id='".$field_id."' name='".$field."' class='ckeditor'>$value</textarea>";
                }
                break;

            case 'hidden_user':
                $field_html = "<input type='hidden' name='{$field}' {$readonly} value=".session('user_id').' >';
                break;

            case 'hidden_account':
                $val = session('account_id');
                if (228 == $this->data['module_id'] && !empty(request()->account_id)) {
                    $val = request()->account_id;
                }
                $field_html = "<input type='hidden' name='{$field}' {$readonly} value=".$val.' >';
                break;

            case 'hidden_sms_account':
                $val = session('sms_account_id');

                $field_html = "<input type='hidden' name='{$field}' {$readonly} value=".$val.' >';
                break;

            case 'hidden_date':
                $field_html = "<input type='hidden' name='{$field}' {$readonly} value=".date('Y-m-d').' >';
                break;

             case 'password':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';
                $field_html = '<input class="e-input" type="text" style="margin-right:30px" name="'.$field.'" id="'.$field_id.'" '.$readonly.' '.$field_required.' autocomplete="non">';

                $this->data['form_script'] .= '
					var '.$field_id.' = new ej.inputs.TextBox({
						'.$readonly_script.'
			            cssClass: "form-control form-control-sm",
			            type: "password",
				    });
					'.$field_id.'.appendTo("#'.$field_id.'");
				';
            break;

            case 'email':
                $show_tooltip = true;
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                if ('view' == $this->data['edit_type']) {
              
                    if (!empty($value)) {
                        $field_html = '<i class="fas fa-envelope mr-2"></i><a href="mailto:'.$value.'" target="_blank">'.$value.'</a>';
                    }
                   
                } else {
                    $field_html = '<input class="e-input" type="text" style="margin-right:30px" name="'.$field.'" id="'.$field_id.'" value="'.$value.'" '.$readonly.' '.$field_required.' >';

                    $this->data['form_script'] .= '
    					var '.$field_id.' = new ej.inputs.TextBox({
    						'.$readonly_script.'
    						type: "email",
				            cssClass: "form-control form-control-sm",
    				    });
    					'.$field_id.'.appendTo("#'.$field_id.'");
    				';
                }
            break;
            case 'signature':

             
                 $module_name = strtolower(str_replace(' ', '_', $this->data['name']));
                $file =  $value;
                    if (!empty($file) && file_exists(uploads_path($this->data['module_id']).$file)) {
                        $field_html .= '
				 <img src="'.uploads_url($this->data['module_id']).$file.'" border="0" style="max-width:200px; max-height:100px"/><br>
				 <input type="hidden" name="'.$field.'" value="'.$value.'"/>
				 ';
                        $args = [
                            'module_id' => $this->data['module_id'],
                            'table' => $this->data['db_table'],
                            'field' => $field,
                            'id' => $row[$this->data['db_key']],
                            'value' => $file,
                        ];

                        if ($field!='logo') {
                            if ('view' != $this->data['edit_type']) {
                                $delete_url = url('/delete_module_file/'.\Erp::encode($args));
                                $field_html .= '<a href="javascript:void(0)" onclick="gridAjax(\''.$delete_url.'\')"><b>Delete File</b></a><br><br>';
                            }
                        }
                    } else {
                        $field_html = '<a id="erase-signature" class="btn btn-default" style="float:right" href="javascript:void(0);">Erase Signature</a>
                <div class="wrapper">
                <canvas id="signature-pad" class="signature-pad" width=300 height=200 style="border:1px solid #ccc"></canvas>
                <input type="hidden" name="'.$field.'" id="signature"/>
                </div>
                ';
                        $this->data['form_script'] .= '
                canvas = document.getElementById("signature-pad");
                signaturePad = new SignaturePad(canvas);
                $("#erase-signature").click(function(){
                signaturePad.clear();
                });
                
                ';
                    }

               
            break;
            case 'link':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';
                if ('view' == $this->data['edit_type']) {
                

                    if (!empty($value)) {
                        if (str_starts_with($value, '<a')) {
                            $value = $value;
                        } elseif (str_starts_with($value, 'http')) {
                            $value = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
                        } else {
                            $value = '<a href="http://'.$value.'" target="_blank">'.$value.'</a>';
                        }
                        $field_html = $value;
                    }
                 
                } else {
                    $field_html = '<input class="e-input" type="text"  name="'.$field.'" id="'.$field_id.'" value="'.$value.'" '.$readonly.' '.$field_required.' >';

                    $this->data['form_script'] .= '
    					var '.$field_id.' = new ej.inputs.TextBox({
					            cssClass: "form-control form-control-sm",
    					        change: function(){
    					            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");
    					        },
    						'.$readonly_script.'
    				    });
    					'.$field_id.'.appendTo("#'.$field_id.'");
    				';
                }
            break;
            case 'phone_number':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                if ('view' == $this->data['edit_type']) {
                  
                    if (!empty($value)) {
                        if (session('role_level') == 'Admin') {
                            $field_html =  '<i class="fas fa-phone mr-2"></i><a href="javascript:void(0);" onclick="gridAjax(\'/pbx_call/'.$value.'\')">'.$value.'</a>';
                        } else {
                            $field_html =  $value;
                        }
                    }


                   
                } else {
                    $field_html = '<input class="e-input" type="text" name="'.$field.'" id="'.$field_id.'" value="'.$value.'" '.$readonly.' '.$field_required.' >';

                    $this->data['form_script'] .= '
    					var '.$field_id.' = new ej.inputs.TextBox({
					            cssClass: "form-control form-control-sm",
    					        change: function(){
    					            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");
    					        },
    						'.$readonly_script.'
    				    });
    					'.$field_id.'.appendTo("#'.$field_id.'");
    				';
                }
            break;
            case 'colorpicker':
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';

                $field_html = '<input type="color" name="'.$field.'" id="'.$field_id.'" value="'.$value.'"  '.$readonly.' '.$field_required.' autocomplete="off">';


                $this->data['form_script'] .= '
					var '.$field_id.' = new ej.inputs.ColorPicker({
                        mode: "Palette",
                        modeSwitcher: false,
                        showButtons: false,
                        noColor: true,
                        value: "'.$value.'",
                        change: function(){
                            $("#'.$field_id.'").val(this.value);
                        },
                        created: function(args){
                          
                        }
				    });
					'.$field_id.'.appendTo("#'.$field_id.'");
				';
            break;
            default:
                $show_tooltip = true;
                $readonly_script = ($readonly) ? PHP_EOL.'enabled: false,'.PHP_EOL : '';
                $control_js_readonly_enabled = $field_id.'.enabled = false;';
                $control_js_readonly_disabled = $field_id.'.enabled = true;';
              
                $field_html = '<input class="e-input " type="text" name="'.$field.'" id="'.$field_id.'" value="'.$value.'" '.$readonly.' '.$field_required.' autocomplete="off">';
               

                $this->data['form_script'] .= '
					var '.$field_id.' = new ej.inputs.TextBox({
					        cssClass: "form-control form-control-sm",
					        change: function(){
					           
					            $("#hidden'.$field_id.'").val('.$field_id.'.value).trigger("change");
					            ';
                                if (!empty($change_logic)) {
                                if (str_starts_with($change_logic, 'function|')) {
                                $this->data['form_script'] .= '
                                
                                '.$field_id.'change_function();';
                                }
                                }
					            $this->data['form_script'].='
					        },
						'.$readonly_script.'
				    });
					'.$field_id.'.appendTo("#'.$field_id.'");
				';
            break;
        }

        if ($show_tooltip) {
            $this->data['form_script'] .= '
    				var '.$field_id.'tooltip = new ej.popups.Tooltip({
                        content: "'.$tooltip.'"
                    });
                    '.$field_id.'tooltip.appendTo("#'.$field_id.'_tooltip");
                ';
        }
      

        if (!empty($display_logic)) {
            $control_js = '
            // display logic
            if(';
            $dependent_fields = [];
            $display_logic_js = explode('{{', $display_logic);
            foreach ($display_logic_js as $dl) {
                if (str_contains($dl, '}}')) {
                    $dlf = explode('}}', $dl);

                    $dependent_fields[] = $dlf[0];
                }
            }

            $display_logic_control = str_replace('{{', '$("#hidden', $display_logic);

            $display_logic_control = str_replace('}}', $modref.'").val()', $display_logic_control);
            $control_js .= $display_logic_control;

            $control_js .= '){
                       $("#'.$field_id.'div").show();
                    }else{
                       $("#'.$field_id.'div").hide();
                    }
                    ';

            $this->data['form_script'] .= '
                        $(document).ready(function(){
                        '.$control_js.'
                        });


                    ';

            foreach ($dependent_fields as $df) {
                if (!empty($df)) {
                    $this->data['form_script'] .= '

                            $(document).on("change","#hidden'.$df.$modref.'",function(e){
                           
                                '.$control_js.'
                            });

                        ';
                }
            }
        
        }
        $readonly_logic = $read_only_logic;
        if (!empty($readonly_logic)) {
            $control_js = '
            // readonly logic
            if(';
            $dependent_fields = [];
            $readonly_logic_js = explode('{{', $readonly_logic);
            foreach ($readonly_logic_js as $dl) {
                if (str_contains($dl, '}}')) {
                    $dlf = explode('}}', $dl);

                    $dependent_fields[] = $dlf[0];
                }
            }

            $readonly_logic_control = str_replace('{{', '$("#hidden', $readonly_logic);
            $readonly_logic_control = str_replace('}}', $modref.'").val()', $readonly_logic_control);
            $control_js .= $readonly_logic_control;

            $control_js .= '){
                      '.$control_js_readonly_enabled.'
                    }else{
                     '.$control_js_readonly_disabled.'
                    }
                    ';

            $this->data['form_script'] .= '
                        $(document).ready(function(){
                        '.$control_js.'
                        });


                    ';

            foreach ($dependent_fields as $df) {
                if (!empty($df)) {
                    $this->data['form_script'] .= '

                            $(document).on("change","#hidden'.$df.$modref.'",function(e){

                                '.$control_js.'
                            });

                        ';
                }
            }
        }

        if (!empty($change_logic)) {
            if (str_starts_with($change_logic, 'function|')) {
                $ajax_function = str_replace('function|', '', $change_logic);

                $this->data['form_script'] .= '
                $("#'.$field_id.'").keypress(function() {
                    $(".dialogSubmitBtn").each(function(e) {
                        $(this).prop("disabled", true);
                    });
                });
                
               function '.$field_id.'change_function(){
                      
                        var form_id =  "'.$this->form_name.'FormAjax";

                        var form = $("#"+form_id);
                        var formData = new FormData(form[0]);
                        formData.append("changed_field","'.$field.'");
                        $.ajax({
                            method: "post",
                            url:"/form_change_ajax/'.$ajax_function.'",
                            data: formData,
                            contentType: false,
                            processData: false,
                            success:function(data){
                     
                                $.each(data, function(k, v) {
                                    var elem = document.getElementById(k+"'.$modref.'").ej2_instances[0];
                                    if(elem instanceof ej.dropdowns.MultiSelect){
                                    
                                        v = v.split(",").map(item => item.trim());
                                    }
                                    
                                   
                                    
                                    elem.value = v;
                                    elem.dataBind();';

                $this->data['form_script'] .= '  
                                if(k == "message"){
                                    $("#message85").trigger("change");
                                }
                                ';

                $this->data['form_script'] .= '               
                                });
                            }
                        });
                };
               function '.$field_id.'change_function_ds(){
                      
                        var form_id =  "'.$this->form_name.'FormAjax";

                        var form = $("#"+form_id);
                        var formData = new FormData(form[0]);
                        formData.append("changed_field","'.$field.'");
                        $.ajax({
                            method: "post",
                            url:"/form_change_ajax/'.$ajax_function.'",
                            data: formData,
                            contentType: false,
                            processData: false,
                            success:function(data){
                          
                                $.each(data, function(k, v) {
                                    var elem = document.getElementById(k+"'.$modref.'").ej2_instances[0];
                                    if(elem instanceof ej.dropdowns.MultiSelect){
                                     
                                        v = v.split(",").map(item => item.trim());
                                    }
                                    
                                   
                                    
                                    elem.value = v;
                                    elem.dataBind();';

                $this->data['form_script'] .= '  
                                if(k == "message"){
                                    $("#message85").trigger("change");
                                }
                                ';

                $this->data['form_script'] .= '               
                                });
                            }
                        });
                };

            ';
            } else {
                $this->data['form_script'] .= '
            
                $(document).on("change","#hidden'.$field_id.'",function(e){
                    '.$change_logic.'
                });
            
                ';
            }
        } elseif (session('role_level') == 'Admin' && $field_id == 'field_type749') {
            $this->data['form_script'] .= '
            
                $(document).on("change","#hidden'.$field_id.'",function(e){
                    if($(this).val().indexOf("select") != -1 || $(this).val() == "file" || $(this).val() == "image"){
                  
                        $("#connection'.$field_id.'").show();
                    }else{
                        $("#connection'.$field_id.'").hide();
                    }
                });
            
                ';
            $this->data['form_script'] .= '
            
                $(document).off("click","#connection'.$field_id.'").on("click","#connection'.$field_id.'",function(e){
                    var connection_url = "/module_manager/formfield/'.$row['module_id'].'/'.$row[$this->data['db_key']].'";
                
                    sidebarform("connection'.$field_id.'", connection_url);
                });
            
                ';
        }
        
        // select datasource update from dependent fields
        
        /* @todo
        remove set value if not in new datasource list
        */
        if(str_contains($field_type,'select') && !empty($opt_db_dependent_fields)){
            $dependent_fields = explode(',',$opt_db_dependent_fields);
          
       
            foreach($dependent_fields as $dependent_field){
                
                $this->data['form_script'] .=' 
                $(document).on("change","#hidden'.$dependent_field.$modref.'",function(e){
                   
                    
                 
                        var datasource_url = "/syncfusion_select_options/'.$id.'";
                        ';
                        foreach($dependent_fields as $dependent_field_val){
                            
                            $this->data['form_script'] .='
                            if('.$dependent_field_val.$modref.' && '.$dependent_field_val.$modref.'.value){
                                if(!$.contains(datasource_url, "?")){
                                    datasource_url += "?'.$dependent_field_val.'="+'.$dependent_field_val.$modref.'.value;
                                }else{
                                    datasource_url += "&'.$dependent_field_val.'="+'.$dependent_field_val.$modref.'.value;
                                }
                            }';
                        }
                        
                        
                       
                        $this->data['form_script'] .='
                   
                        $.ajax({
                            url: datasource_url,
                            dataType:"json",
                            success: function(data){
                   
                              
                                var allValuesPresent = false;
                                if(data.length > 0 && '.$field_id.'.value && '.$field_id.'.value.length > 0){
                                    var allValuesPresent = '.$field_id.'.value.every((str) =>
                                        data.some((obj) => obj.value === str)
                                    );
                                }
                             
                                if(!allValuesPresent){
                                 '.$field_id.'.value = null;   
                                }
                              
                                '.$field_id.'.dataSource = data;
                                '.$field_id.'.enabled = true;
                                '.$field_id.'.dataBind();
                                
                                setTimeout(function(){
                                  
                                    if(typeof '.$dependent_field.$modref.'change_function === "function"){
                                  
                                         '.$dependent_field.$modref.'change_function_ds();
                                    }
                                },1000);
                            }
                        });
                    
                })';
                
            }
        }

        if ($cell_editor) {
            return ['form_script' => $this->data['form_script'],'form_html' => $field_html];
        }
        
        if($this->data['module_id'] == 556 && $field == 'message'){
            $this->data['form_html'] .= '<div class="btn-group">
	    		<button type="button" class="btn btn-xs insert-blade" href="javascript:void(0);" id="blade-company">Company</button>
	    		<button type="button" class="btn btn-xs insert-blade" href="javascript:void(0);" id="blade-contact">Contact</button>
	    		<button type="button" class="btn btn-xs insert-blade" href="javascript:void(0);" id="blade-balance">Balance</button>
	    	</div><br>';
        }
        
        if(str_contains($field_type,'hidden')){
            $this->data['form_html'] .= $field_html;
        }else{
            $add_link = false;
            if(empty($readonly) && $field_type == 'select_module' && !empty($opt_module_id)){
                $add_access = app('erp_config')['forms']->where('module_id',$opt_module_id)->where('role_id',session('role_id'))->where('is_add',1)->count();
                if($add_access){
                    $add_link = get_menu_url_from_module_id($opt_module_id).'/edit';
                }
            }
            if($add_link){
                $this->data['form_html'] .= '<div class="row form-group align-items-center" id="'.$field_id.'div" data-id="'.$id.'" data-tab="'.$tab.'" data-field="'.$field.'">
               
                <div class="col col-md-2">
                <label for="'.$field_id.'" class="form-control-label form-dnd-label">'.$label.'</label> '.$tip.'
                </div>
                <div class="col col-md-9">
                '.$field_html.'
                </div>
                <div class="col col-md-1">
                <a class="btn btn-xs btn-icon btn-success mb-0" href="'.$add_link.'" data-target="sidebarformleft"><i class="fas fa-plus"></i></a>
                </div>
                </div>';
                
            }else{
                $this->data['form_html'] .= '<div class="row form-group align-items-center" id="'.$field_id.'div" data-id="'.$id.'" data-tab="'.$tab.'" data-field="'.$field.'">
               
                <div class="col col-md-2">
                <label for="'.$field_id.'" class="form-control-label form-dnd-label">'.$label.'</label> '.$tip.'
                </div>
                <div class="col col-md-10">
                '.$field_html.'
                </div>
                </div>';
            }
        }
    }

    private function getJoinDisplayField($table, $field, $val)
    {
        $grid_field = \DB::table('erp_module_fields')->where('field_type', 'select_module')->where('field', $field)->where('alias', $table)->get()->first();

        $conn = null;

        if (!empty($conn) && $conn['display'] && $conn['db_table']) {
            $select_fields = $conn['display'];

            $query = \DB::table($conn['db_table']);
            $query->select($select_fields);
            $query->where($conn['db_key'], $val);
            $result = $query->get()->first();

            foreach ($select_fields as $select_field) {
                $formatted_val .= $result->{$select_field}.' - ';
            }

            return rtrim($formatted_val, ' - ');
        }

        return $val;
    }
}
