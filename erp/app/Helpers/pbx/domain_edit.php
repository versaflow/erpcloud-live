<?php

function aftersave_domain_edit_update_domain_name($request){
    if(!empty($request->new_domain_name)){ 
      
        if(!str_ends_with($request->new_domain_name,'.cloudtools.co.za')){
            \DB::connection('pbx')->table('v_domains')->where('id',$request->id)->update(['new_domain_name' => '']);
            return json_alert('Domain name needs to end with .cloudtools.co.za', 'warning');
        }
        
        \DB::connection('pbx')->table('v_domains')->where('id',$request->id)->update(['new_domain_name' => '']);
        $domain = \DB::connection('pbx')->table('v_domains')->where('id',$request->id)->get()->first();
        $ix = new Interworx();
        $result = $ix->addPbxDns($request->new_domain_name);
       
       
        if($result['status']!= 0 || $result['reply_code']!=201){
            if(!str_contains($result['payload'],'identical DNS record already exists')){
                return json_alert('Domain dns add failed', 'warning');
            }
        }
        
        $post_data = [
            'domain_uuid' => $domain->domain_uuid,
            'domain_name' => $request->new_domain_name,
            'domain_enabled' => 'true',
        ];
       
	
        $result = fusionpbx_edit_curl('http://156.0.96.60/core/domains/domain_edit.php?', $domain->domain_uuid, $post_data, true);
        if(!empty($result))
        return $result;
    }
    
   
    //core/domains/domain_edit.php?id=399eccad-2340-40b6-8d2a-43ed63b5615e
}