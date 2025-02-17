<?php

class FusionPBX
{
    public function pbx_login($account_id = null, $redirect = false)
    {
        session(['pbx_partner_level' => false]);

        if (empty($account_id) || $account_id == session('account_id')) {
            $account_id = session('account_id');
            if (session('role_id') == 11) {
                session(['pbx_partner_level' => true]);
            }
        }

    
        $account = dbgetaccount($account_id);
        if ($account->type == 'reseller' && $account_id != 1) {
            $account_id = \DB::connection('default')
                ->table('crm_accounts')
                ->join('isp_voice_pbx_domains', 'isp_voice_pbx_domains.account_id', '=', 'crm_accounts.id')
                ->where('crm_accounts.status', '!=', 'Deleted')
                ->where('crm_accounts.partner_id', $account_id)
                ->pluck('crm_accounts.id')
                ->first();
        }
        // set domain and group session to build menu
        if (session('account_id') == $account_id || parent_of($account_id) || session('role_level') == 'Admin') {
            if (1 == $account_id) {
                $pbx_domain = '156.0.96.60';
                if (session('instance')->directory != 'telecloud') {
                    $pbx_domain = '156.0.96.61';
                }
                $user = 'admin';
                $pbx_group = 'superadmin';
            } else {
                $voip = \DB::connection('pbx')->table('v_domains')->where('account_id', $account_id)->get()->first();
                $pbx_domain = $voip->domain_name;
                $pbx_group = $voip->pbx_type;
                $user = 'primary';
            }

            if (empty($pbx_domain)) {
                
                session(['pbx_domain' => '']);
                session(['pbx_group' => '']);
                session(['pbx_account_id' => 0]);
                session(['pbx_api_key' => '']);
                session(['pbx_domain_uuid' => '']);
                session(['pbx_ratesheet_id' => 0]);
                session(['pbx_server' => 'pbx']);
                if (request()->ajax()) {
               //     return json_alert('No Access.');
                } else {
               //     return \Redirect::back()->with('message', 'No Access.')->with('status', 'error');
                }
            }

            $pbx_row = \DB::connection('pbx')->table('v_users as vu')
                ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
                ->where('vd.domain_name', $pbx_domain)
                ->get()->first();

            session(['pbx_domain' => $pbx_domain]);
            session(['pbx_group' => $pbx_group]);
            session(['pbx_account_id' => $account_id]);
            session(['pbx_api_key' => $pbx_row->api_key]);
            session(['pbx_domain_uuid' => $pbx_row->domain_uuid]);
            session(['pbx_ratesheet_id' => $pbx_row->ratesheet_id]);
            session(['pbx_server' => 'pbx']);
        }
        if ($redirect) {
            $menu_name = get_menu_url_from_table('v_domains');
            $menu_name = url($menu_name);
    
            if ($account_id == 1) {
                $menu_name = get_menu_url_from_table('call_records_outbound');
            }
            return redirect()->to($menu_name);
        }
    }

    public function importDomains($domain_uuid = false)
    {
        if (!$domain_uuid) {
            //   return false;
        }

        if (!$domain_uuid) {
            //  \DB::connection('default')->table('isp_voice_pbx_domains')->delete();
        }

        if ($domain_uuid) {
            $pbx_domains = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->get();
        } else {
            $pbx_domains = \DB::connection('pbx')->table('v_domains')->where('account_id', '>', 0)->get();
        }

        foreach ($pbx_domains as $domain) {
            if ($domain->account_id) {
                $data = [
                    'pabx_domain' => $domain->domain_name,
                    'pabx_type' => $domain->pbx_type,
                    'erp' => $domain->erp,
                    'account_id' => $domain->account_id,
                    'domain_uuid' => $domain->domain_uuid,
                    'pbx_balance' => $domain->balance,
                    'server' => 'pbx'
                ];

                $exists =  \DB::connection('default')->table('isp_voice_pbx_domains')->where('domain_uuid', $domain->domain_uuid)->count();
                if (!$exists) {
                    \DB::connection('default')->table('isp_voice_pbx_domains')->insert($data);
                } else {
                    \DB::connection('default')->table('isp_voice_pbx_domains')->where('domain_uuid', $domain->domain_uuid)->update($data);
                }
            }
        }
    }
    
  
    
    public function validateGroups()
    {
        $domains = \DB::connection('pbx')->table('v_domains')
        
        ->where('account_id', '>', 0)
        ->where('domain_name', '!=', '156.0.96.60')
        ->where('domain_name', '!=', '156.0.96.69')
        ->get();
        foreach ($domains as $domain) {
            $account = dbgetaccount($domain->account_id);
            if ($account->status == 'Deleted') {
                \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $account->domain_uuid)->where('status', 'Deleted')->update(['domain_uuid' => null,'number_routing' => null,'routing_type'=> null]);
                \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $account->domain_uuid)->where('status', '!=', 'Deleted')->update(['domain_uuid' => null, 'status' => 'Enabled','number_routing' => null,'routing_type'=> null]);

                pbx_delete_domain($account->pabx_domain, $account->id);
            }
        }

        $domain_uuids = \DB::connection('pbx')->table('v_domains')->pluck('domain_uuid')->toArray();
        $group_uuids =  \DB::connection('pbx')->table('v_groups')->pluck('group_uuid')->toArray();

        \DB::connection('pbx')->table('v_users')->whereNull('domain_uuid')->delete();
        \DB::connection('pbx')->table('v_users')->whereNotIn('domain_uuid', $domain_uuids)->delete();

        $user_uuids =  \DB::connection('pbx')->table('v_users')->pluck('user_uuid')->toArray();


        $groups = \DB::connection('pbx')->table('v_groups')->get();
        foreach ($groups as $group) {
            \DB::connection('pbx')->table('v_group_permissions')->where('group_uuid', $group->group_uuid)->update(['group_name' => $group->group_name]);
            \DB::connection('pbx')->table('v_menu_item_groups')->where('group_uuid', $group->group_uuid)->update(['group_name' => $group->group_name]);
            $domain_uuids = \DB::connection('pbx')->table('v_domains')->where('pbx_type', $group->group_name)->pluck('domain_uuid')->toArray();
            \DB::connection('pbx')->table('v_user_groups')->whereIn('domain_uuid', $domain_uuids)->update(['group_uuid'=> $group->group_uuid,'group_name' => $group->group_name]);
        }

        $domains = \DB::connection('pbx')->table('v_domains')
        
        ->where('account_id', '>', 0)
        ->where('domain_name', '!=', '156.0.96.60')
        ->where('domain_name', '!=', '156.0.96.69')
        ->get();

        foreach ($domains as $d) {
            $activations = \DB::connection($d->erp)->table('sub_activations')->where('account_id', $d->account_id)->whereIn('provision_type', ['pbx_extension','sip_trunk','phone_line'])->where('status', 'Pending')->count();
            if (!$activations) {
                $e_count = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)->count();
                $s_count = \DB::connection($d->erp)->table('sub_services')->where('account_id', $d->account_id)->whereIn('provision_type', ['pbx_extension','sip_trunk','phone_line'])->where('status', '!=', 'Deleted')->count();

                if (($e_count!=$s_count)) {
                    if ($e_count == 0 && $s_count == 0) {
                        $account = dbgetaccount($d->account_id);
                        provision_pbx_extension_default($account);
                    }

                    if ($e_count<$s_count) {
                        $customer = dbgetaccount($d->account_id);

                        $subs = \DB::connection($d->erp)->table('sub_services')
                            ->where('account_id', $d->account_id)
                            ->where('provision_type', 'pbx_extension')
                            ->where('status', '!=', 'Deleted')->get();
                        foreach ($subs as $sub) {
                            $exists = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)->where('extension', $sub->detail)->count();
                            if (!$exists) {
                                $extension_number = $sub->detail;
                                pbx_add_extension($customer, $extension_number);
                            }
                        }
                    }

                    if ($e_count>$s_count) {
                        $customer = dbgetaccount($d->account_id);

                        $exts = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $d->domain_uuid)->get();
                        foreach ($exts as $ext) {
                            $exists = \DB::connection($d->erp)->table('sub_services')
                                ->where('account_id', $d->account_id)
                                ->where('provision_type', 'pbx_extension')
                                ->where('status', '!=', 'Deleted')
                                ->where('detail', $ext->extension)
                                ->count();
                            if (!$exists) {
                                $erp = new ErpSubs();
                                $erp->createSubscription($d->account_id, 130, $ext->extension);
                            }
                        }
                    }
                }
            }
        }
    }

    public function setUnlimitedChannels()
    {
        $domains = \DB::connection('pbx')->table('v_domains')->get();
        $channel_product_ids = get_activation_type_product_ids('unlimited_channel');
        $channel_products = \DB::connection('default')->table('crm_products')->select('id','provision_package')->whereIn('id',$channel_product_ids)->get();
        foreach ($domains as $domain) {
            $unlimited_fup = 0;
            $num_channels = \DB::table('sub_services')->whereIn('product_id', $channel_product_ids)->where('account_id', $domain->account_id)->where('status', '!=','Deleted')->count();
            
            foreach($channel_products as $channel_product){
                $channel_product_qty = \DB::table('sub_services')->where('product_id', $channel_product->id)->where('account_id', $domain->account_id)->where('status', '!=','Deleted')->sum('qty');
                if ($channel_product_qty > 0)
                    $unlimited_fup += $channel_product_qty*$channel_product->provision_package;
            }

            \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain->domain_uuid)->update(['unlimited_fup'=>$unlimited_fup,'unlimited_channels'=>$num_channels]);
            if ($num_channels == 0) {
                \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain->domain_uuid)->update(['balance_notification'=>'Daily']);
            } else {
                \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain->domain_uuid)->update(['balance_notification'=>'None']);
            }
        }
    }

    public function setPbxType($domain_uuid)
    {
        $domain = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->get()->first();


       

        if (!$domain->account_id) {
            return false;
        }

        $type = 'Phone Line';
        $office_product_ids = \DB::connection('default')->table('crm_products')->where('provision_package', 'PBX')->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        $callcenter_product_ids = \DB::connection('default')->table('crm_products')->where('provision_package', 'Call Center')->where('status', '!=', 'Deleted')->pluck('id')->toArray();

        $type_office = \DB::connection('default')->table('sub_services')
        ->where('account_id', $domain->account_id)
        ->where('status', '!=', 'Deleted')
        ->whereIn('product_id', $office_product_ids)
        ->count();


        $type_callcenter = \DB::connection('default')->table('sub_services')
        ->where('account_id', $domain->account_id)
        ->where('status', '!=', 'Deleted')
        ->whereIn('product_id', $callcenter_product_ids)
        ->count();

        if ($type_office) {
            $type = 'PBX';
        }
        if ($type_callcenter) {
            $type = 'Call Center';
        }
       
       

        
        $account = dbgetaccount($domain->account_id);
        $reseller = dbgetaccount($account->partner_id);
        $update_data = [
            'pbx_type'=>$type,
            'partner_id' => $reseller->id,
            'partner_company' => $reseller->company,
            'company' => $account->company
        ];
      
        \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain->domain_uuid)->update($update_data);
        
        
        \DB::connection('default')->table('isp_voice_pbx_domains')->where('domain_uuid', $domain->domain_uuid)->update(['pabx_type'=>$type]);
        $group_uuid = \DB::connection('pbx')->table('v_groups')->where('group_name', $type)->pluck('group_uuid')->first();
        
        if($type == 'PBX'){
            //\DB::connection('default')->table('sub_services')
           // ->where('account_id', $domain->account_id)
            //->where('product_id', 674)
            //->update(['product_id'=>130]);
           // \DB::connection('default')->table('sub_services')
           // ->where('account_id', $domain->account_id)
            //->where('product_id', 1393)
           //->update(['product_id'=>1394]);
        }
        if($type == 'Phone Line'){
           // \DB::connection('default')->table('sub_services')
           // ->where('account_id', $domain->account_id)
           // ->where('product_id', 130)
          //  ->update(['product_id'=>674]);
            //\DB::connection('default')->table('sub_services')
            //->where('account_id', $domain->account_id)
            //->where('product_id', 1394)
            //->update(['product_id'=>1393]);
        }
        
        \DB::connection('default')->table('sub_services')
        ->where('provision_type', 'sip_trunk')
        ->where('product_id','!=', 674)
        ->update(['provision_type'=>'pbx_extension']);
        
        /*
        $voice_lines = \DB::connection('default')->table('sub_services')
            ->where('account_id', $domain->account_id)
            ->where('provision_type', 'sip_trunk')
            ->where('status', '!=', 'Deleted')
            ->where('product_id', 674)
            ->get();

        foreach ($voice_lines as $voice_line) {
            $extension_set = \DB::connection('default')->table('sub_services')
            ->where('account_id', $domain->account_id)
            ->where('provision_type', 'pbx_extension')
            ->where('status', '!=', 'Deleted')
            ->where('detail', $voice_line->detail)
            ->count();
            if ($extension_set) {
                \DB::connection('default')->table('sub_services')->where('id', $voice_line->id)->delete();
            }
        }

        $first_extension = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->orderBy('extension', 'asc')->pluck('extension')->first();
        $extension_count = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->count();

       

        if (!empty($first_extension) && $extension_count == 1) {
            \DB::connection('default')->table('sub_services')
                ->where('account_id', $domain->account_id)
                ->where('provision_type', 'pbx_extension')
                ->where('detail', $first_extension)
                ->where('status', '!=', 'Deleted')
                ->update(['product_id'=>674,'provision_type' => 'sip_trunk']);
        } elseif (!empty($first_extension) && $extension_count > 1) {
            if ($type == 'PBX') {
                \DB::connection('default')->table('sub_services')
                ->where('account_id', $domain->account_id)
                ->where('provision_type', 'sip_trunk')
                ->where('status', '!=', 'Deleted')
                ->update(['product_id'=>130,'provision_type' => 'pbx_extension']);
            }
            if ($type == 'Call Center') {
                \DB::connection('default')->table('sub_services')
                ->where('account_id', $domain->account_id)
                ->where('provision_type', 'sip_trunk')
                ->where('status', '!=', 'Deleted')
                ->update(['product_id'=>452,'provision_type' => 'pbx_extension']);
            }
        }
        */

        $groups = \DB::connection('pbx')->table('v_groups')->get();
        foreach ($groups as $group) {
            \DB::connection('pbx')->table('v_group_permissions')->where('group_uuid', $group->group_uuid)->update(['group_name' => $group->group_name]);
            \DB::connection('pbx')->table('v_menu_item_groups')->where('group_uuid', $group->group_uuid)->update(['group_name' => $group->group_name]);
            $domain_uuids = \DB::connection('pbx')->table('v_domains')->where('pbx_type', $group->group_name)->pluck('domain_uuid')->toArray();
            \DB::connection('pbx')->table('v_user_groups')->whereIn('domain_uuid', $domain_uuids)->update(['group_uuid'=> $group->group_uuid,'group_name' => $group->group_name]);
        }
    }

    public function updateGroupNames()
    {
        $groups = \DB::connection('pbx')->table('v_groups')->get();
        $schema = get_complete_schema('pbx');
        foreach ($schema as $table => $cols) {
            if ($table != 'v_groups' && in_array('group_uuid', $cols) && in_array('group_name', $cols)) {
                foreach ($groups as $group) {
                    \DB::connection('pbx')->table($table)->where('group_uuid', $group->group_uuid)->update(['group_name'=>$group->group_name]);
                }
            }
        }
        $user_groups = \DB::connection('pbx')->table('v_user_groups')->get();
        foreach ($user_groups as $user_group) {
            \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $user_group->domain_uuid)->update(['pbx_type'=>$user_group->group_name]);
        }
    }

    public function verify_number_subscriptions()
    {
        
        $erp = new ErpSubs();
        $domains = \DB::connection('pbx')->table('v_domains')->where('account_id', '>', 0)->get();

        foreach ($domains as $d) {
            $numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $d->domain_uuid)->where('status','!=','Deleted')->get();
            foreach ($numbers as $number) {
                $phone_number = $number->number;
                $sub = \DB::table('sub_services')->where('detail', $phone_number)->where('account_id', $d->account_id)->where('status', '!=', 'Deleted')->count();
                if (!$sub) {
                    if ('2787' == substr($phone_number, 0, 4) || '087' == substr($phone_number, 0, 3)) {
                        $subscription_product = 127; // 087
                    } else {
                        if (str_starts_with($phone_number, '2712786')) { // 012786
                            $subscription_product = 176;
                        } elseif (str_starts_with($phone_number, '2710786')) { // 010786
                            $subscription_product = 687;
                        } else { // geo
                            $subscription_product = 128;
                        }
                    }
                    $erp->createSubscription($d->account_id, $subscription_product, $phone_number);
                }
            }
        }
        
        $subs = \DB::table('sub_services')->where('provision_type','phone_number')->where('status','!=','Deleted')->get();
        foreach($subs as $s){
            $exists = \DB::connection('pbx')->table('p_phone_numbers')->where('number',$s->detail)->count();
            $status = \DB::connection('pbx')->table('p_phone_numbers')->where('number',$s->detail)->pluck('status')->first();
            if(!$exists || $status == 'Deleted'){
                \DB::table('sub_services')->where('id','!=',$s->id)->where('status','Deleted')->where('provision_type','phone_number')->where('detail',$s->detail)->delete();
                \DB::table('sub_services')->where('id',$s->id)->update(['status'=>'Deleted','deleted_at' => date('Y-m-d H:i:s')]);
                module_log(334,$s->id, 'deleted', 'deleted from p_phone_numbers');    
            }
        }
    }

    public function clearRecordings($domain)
    {
        if ($domain > '') {
            $exists = \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain)->count();
            if (!$exists) {
                $cmd = "rm -rf /var/lib/freeswitch/recordings/".$domain;
                Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
            }
        }
    }

    public function checkBlockedIP($ip)
    {
        $cmd = "fail2ban-client banned";
        //$cmd = "cat /usr/local/freeswitch/log/freeswitch.log | grep ".$ip." | grep failure ";
        //$cmd = "iptables -L | grep ".$ip;
        
        $blocked = false;
        $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
        $result = trim($result);
        $result = str_replace('\n','',$result);
        $result = str_replace("'",'"',$result);
        $result = json_decode($result);
        if(!empty($result)){
        foreach($result as $result_row){
            $result_row = (array) $result_row;
            if(isset($result_row["freeswitch-udp"]) && in_array($ip,$result_row["freeswitch-udp"])){
                $blocked = true;
            }
            if(isset($result_row["freeswitch-tcp"]) && in_array($ip,$result_row["freeswitch-tcp"])){
                $blocked = true;
            }
        }
        }
        return $blocked;
    }

    public function unblockIP($ip)
    {
        $cmd = "fail2ban-client set freeswitch-tcp unbanip ".$ip;
        $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
        $cmd = "fail2ban-client set freeswitch-ip-tcp unbanip ".$ip;
        $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
        $cmd = "fail2ban-client set freeswitch-udp unbanip ".$ip;
        $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
        $cmd = "fail2ban-client set freeswitch-ip-udp unbanip ".$ip;
        $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
        return $result;
    }

    public function flushFail2Ban()
    {
        if (!empty(session('blocked_pbx_ip'))) {
            $cmd = "rm /var/log/freeswitch/freeswitch.log* && service fail2ban restart";
            $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
            session()->forget('blocked_pbx_ip');
            return $result;
        }
        return false;
    }

    public function portalCmd($pbx_function, $pbx_param = false)
    {
        if ($pbx_function) {
            $cmd = 'php -f /var/www/html/lua/portal.php '.$pbx_function;
            if ($pbx_param) {
                $cmd .= ' '.$pbx_param;
            }
           // aa($cmd);
            $result = Erp::ssh('156.0.96.60', 'root', 'Ahmed777', $cmd);
           // aa($result);

            return $result;
        }
    }

    public function post_extension($data)
    {
        $pbx_url = '/app/extensions/extension_edit.php';
        $account = dbgetaccount($data['account_id']);
        $api_key = \DB::connection('pbx')->table('v_users as vu')
            ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
            ->where('vd.domain_name', $account->pabx_domain)
            ->pluck('api_key')->first();

        $url = 'http://'.$account->pabx_domain.$pbx_url.'?key='.$api_key;

        $result = \Httpful\Request::post($url)
            ->body($data)
            ->sendsType(\Httpful\Mime::FORM)
            ->send();

        if (200 == $result->code && !empty($result->body->url)) {
            return $result->body->url;
        }

        return false;
    }

    public function post_routing($data)
    {
        $pbx_url = '/app/destinations/destination_edit.php';
        $account = dbgetaccount($data['account_id']);
        $api_key = \DB::connection('pbx')->table('v_users as vu')
            ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
            ->where('vd.domain_name', $account->pabx_domain)
            ->where('vu.username', 'primary')
            ->pluck('api_key')->first();

        $url = 'http://'.$account->pabx_domain.$pbx_url.'?key='.$api_key;

        $result = \Httpful\Request::post($url)
            ->body($data)
            ->sendsType(\Httpful\Mime::FORM)
            ->send();

        if (200 == $result->code && !empty($result->body->url)) {
            return $result->body->url;
        }

        return false;
    }


    public function pbx_panels($partner_dropdown = false)
    {
        $panels = [];
        $pbx_domains = \DB::connection('pbx')->table('v_domains')->orderBy('domain_name')->get();
     
        if (session('role_level') == 'Partner') {
            $pbx_domains = $pbx_domains->where('partner_id',session('account_id'));
        }
        
        if (session('role_level') == 'Customer') {
            $pbx_domains = $pbx_domains->where('account_id',session('account_id'));
        }
            
        foreach ($pbx_domains as $pbx_panel) {
            $balance = $pbx_domains->where('account_id', $pbx_panel->account_id)->pluck('balance')->first();
            $currency_symbol = get_currency_symbol($pbx_panel->currency);

            $name = $pbx_panel->company.' - '.$pbx_panel->domain_name.' - '.$currency_symbol.currency($pbx_panel->balance);
            if($pbx_panel->domain_name == '156.0.96.60'){
                $name = $pbx_panel->domain_name;
            }

            $panels[] = (object) [
                'value' => $pbx_panel->id,
                'id' => $pbx_panel->id,
                'domain_name' => $pbx_panel->domain_name,
                'account_id' => $pbx_panel->account_id,
                'domain_uuid' => $pbx_panel->domain_uuid,
                'login_url' => url('pbx_panel_login/'.$pbx_panel->account_id),
                'name' => $name,
                'text' => $name,
            ];
        }
            
      
        return $panels;
    }
    
    public function call_profits($account_id = false)
    {
        if (!$account_id) {
            $account_id = session('account_id');
        }
        $is_partner = \DB::connection('default')->table('crm_accounts')->where('type', 'reseller')->where('id', $account_id)->count();
        if (!$is_partner) {
            return false;
        }

        return \DB::connection('pbx')->table('p_partners')->where('partner_id', $account_id)->pluck('voice_prepaid_profit')->first();
    }




    public function sort_menu($menu, $parent_uuid = null, $sorted_menu = [])
    {
        $menu = collect($menu);

        $menu_arr = $menu->where('menu_item_parent_uuid', $parent_uuid)->sortBy('menu_item_order')->all();

        if (!empty($menu_arr) && count($menu_arr) > 0) {
            foreach ($menu_arr as $menu_item) {
                $sorted_menu[] = $menu_item;

                $sorted_menu = $this->sort_menu($menu, $menu_item->menu_item_uuid, $sorted_menu);
            }
        }

        return $sorted_menu;
    }



    public function sms_panels()
    {
        $panels = [];
        if (session('role_level') == 'Admin') {
            $panels[] = (object) [
                'type' => 'Admin',
                'url' => url('sms_panel'),
                'name' => 'SMS Admin',
                'company' => 'Admin',
            ];

            $sms_panels = \DB::connection('default')->table('sub_services as s')
                ->select('s.account_id', 'c.company')
                ->join('crm_accounts as c', 'c.id', '=', 's.account_id')
                ->where('s.provision_type', 'LIKE', '%sms%')
                ->where('s.status', '!=', 'Deleted')
                ->where('c.status', '!=', 'Deleted')
                ->orderby('c.company')->get();
            foreach ($sms_panels as $sms_panel) {
                $panels[] = (object) [
                    'type' => 'SMS',
                    'url' => url('sms_panel/'.$sms_panel->account_id),
                    'name' => $sms_panel->company,
                ];
            }

            if (count($sms_panels) == 0) {
                $panels = [];
            }
        }

        if (check_access('11')) {
            $sms_panels = \DB::connection('default')->table('sub_services as s')
                ->select('s.account_id', 'c.company')
                ->join('crm_accounts as c', 'c.id', '=', 's.account_id')
                ->where('s.provision_type', 'LIKE', '%sms%')
                ->where('s.status', '!=', 'Deleted')
                ->where('c.status', '!=', 'Deleted')
                ->where('c.partner_id', session('account_id'))
                ->orderby('c.company')->get();

            foreach ($sms_panels as $sms_panel) {
                $panels[] = (object) [
                    'type' => 'SMS',
                    'url' => url('sms_panel/'.$sms_panel->account_id),
                    'name' => $sms_panel->company,
                ];
            }
        }

        if (check_access('21')) {
            $sms_panels = \DB::connection('default')->table('sub_services as s')
                ->select('s.account_id', 'c.company')
                ->join('crm_accounts as c', 'c.id', '=', 's.account_id')
                ->where('s.provision_type', 'LIKE', '%sms%')
                ->where('s.status', '!=', 'Deleted')
                ->where('c.status', '!=', 'Deleted')
                ->where('c.id', session('account_id'))
                ->orderby('c.company')->get();
            foreach ($sms_panels as $sms_panel) {
                $panels[] = (object) [
                    'type' => 'SMS',
                    'url' => url('sms_panel/'.$sms_panel->account_id),
                    'name' => $sms_panel->company,
                ];
            }
        }

        return $panels;
    }

    public function sms_login($account_id = null, $redirect = true)
    {
        if (empty($account_id)) {
            $account_id = session('account_id');
        }
        // set domain and group session to build menu
        if (session('account_id') == $account_id || parent_of($account_id) || session('role_level') == 'Admin') {
            if (1 == $account_id) {
                $company = 'SMS Admin';
            } else {
                $account = dbgetaccount($account_id);
                $company = $account->company;
            }

            if (1 != $account_id && $account->type == 'reseller') {
                $reseller_user_ids = \DB::table('crm_accounts')->where('partner_id', $account_id)->where('type', 'reseller_user')->where('status', '!=', 'Deleted')->pluck('id')->toArray();

                $sms_subscription = \DB::connection('default')->table('sub_services')
                    ->whereIn('account_id', $reseller_user_ids)->where('status', '!=', 'Deleted')->where('provision_type', 'LIKE', '%sms%')
                    ->count();
                if ($sms_subscription) {
                    $sms_account_id = \DB::connection('default')->table('sub_services')
                        ->whereIn('account_id', $reseller_user_ids)->where('status', '!=', 'Deleted')->where('provision_type', 'LIKE', '%sms%')
                        ->pluck('account_id')->first();
                    $account = dbgetaccount($sms_account_id);
                    $company = $account->company;
                    $account_id = $sms_account_id;
                } else {
                    if (request()->ajax()) {
                        return json_alert('Place an order for Bulk SMS Credits to gain access');
                    } else {
                        return \Redirect::back()->with('message', 'Place an order for Bulk SMS Credits to gain access.')->with('status', 'error');
                    }
                }
            } elseif (1 != $account_id) {
                $sms_subscription = \DB::connection('default')->table('sub_services')
                    ->where('account_id', $account_id)->where('status', '!=', 'Deleted')->where('provision_type', 'LIKE', '%sms%')
                    ->count();

                if (!$sms_subscription) {
                    if (request()->ajax()) {
                        return json_alert('Place an order for Bulk SMS Credits to gain access');
                    } else {
                        return \Redirect::back()->with('message', 'Place an order for Bulk SMS Credits to gain access.')->with('status', 'error');
                    }
                }
            }


            session(['sms_company' => $company]);
            session(['sms_account_id' => $account_id]);
        }
        if ($redirect === true) {
            $menu_name = get_menu_url_from_table('isp_sms_messages');
            return redirect()->to($menu_name);
        }

        if ($redirect) {
            return redirect()->to($redirect);
        }
    }

    public function pbx_balance()
    {
        $balance = \DB::connection('pbx')->table('v_domains')->where('account_id', session('pbx_account_id'))
            ->pluck('balance')->first();

        if (!$balance) {
            return false;
        }
        return currency($balance);
    }

    public function pbx_contract_balance()
    {
        $balance = \DB::connection('pbx')->table('v_domains')->where('account_id', session('pbx_account_id'))
            ->pluck('balance')->first();


        if (!$balance) {
            return false;
        }
        return currency($balance);
    }

    public function sms_balance()
    {
        $balance = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->where('account_id', session('sms_account_id'))->where('provision_type', 'bulk_sms_prepaid')->get()->first();
        if (!$balance) {
            return false;
        }
        return intval($balance->current_usage);
    }

    public function sms_contract_balance()
    {
        $balance = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->where('account_id', session('sms_account_id'))->where('provision_type', 'bulk_sms')->get()->first();
        if (!$balance) {
            return false;
        }
        return intval($balance->current_usage);
        ;
    }
}