<?php

function schedule_process_easypanel_activations()
{
    $product_ids = \DB::table('sub_activations')->whereIn('provision_type', ['iptv', 'iptv_global'])->where('status', 'Pending')->where('step', 1)->pluck('product_id')->unique()->toArray();
    // aa($product_ids); // Log product IDs
    foreach ($product_ids as $product_id) {
        $activations = \DB::table('sub_activations')->where('product_id', $product_id)->whereIn('provision_type', ['iptv', 'iptv_global'])->where('status', 'Pending')->where('step', 1)->get();
        aa($activations); // Log activations
        foreach ($activations as $activation) {
            $post_data = ['easypanel_id' => 1, 'line_type' => 'line', 'package' => 62, 'description' => 'new line', 'username' => generate_strong_password(6), 'password' => generate_strong_password(6)];
            $post_data = json_encode($post_data);
            aa($post_data); // Log post data
            // $result = json_decode(easypanel_create_line($post_data));
            // $result->id = 1;
            // $post_data = json_decode($post_data);
            // $post_data->easypanel_id = 1; //$result->id;
            // vd(generate_strong_password(6));
            insert_new_line_iptv_center($post_data);

            $data = [
                'endpoint' => 'create-line',
                'request_type' => 'POST',
                'post_data' => json_encode($post_data),
                'success_callback_function' => 'insert_new_line_iptv_center',
                'success_callback_params' => json_encode(['product_id' => $product_id]),
            ];
            aa($data); // Log data before inserting into queue
            dbinsert('isp_iptv_api_queue', $data);
            $request_data = new \Illuminate\Http\Request;
            $id = $activation->id;
            $request_data->id = $id;
            aa($request_data); // Log request data
            app(\App\Http\Controllers\CustomController::class)->provisionService($request_data, 'sub_activations', $id);
        }
    }
}

function schedule_extend_easypanel_lines()
{
    $easypanel_lines = \DB::table('isp_data_iptv')->where('is_deleted', 0)->where('easypanel_status', 'Expired')->get();
    foreach ($easypanel_lines as $easypanel_line) {
        //extend for one month
        $data = [
            'endpoint' => 'extend/'.$easypanel_line->easypanel_id,
            'request_type' => 'POST',
            'post_data' => json_encode(['package' => 62]),
            'success_callback_function' => 'erp_extend',
            'row_id' => $easypanel_line->id,
        ];
        dbinsert('isp_iptv_api_queue', $data);
    }

    return json_alert('Request added to API queue');
}

function schedule_process_easypanel_activations_trial()
{
    $activations = \DB::table('sub_activations')->where('provision_type', 'iptv_trial')->where('status', 'Pending')->where('step', 1)->get();

    foreach ($activations as $activation) {
        $account = get_account($activation->account_id);
        $post_data = ['line_type' => 'line', 'package' => 7, 'description' => $account->company, 'username' => generate_strong_password(), 'password' => generate_strong_password()];

        $data = [
            'endpoint' => 'create-line/1',
            'request_type' => 'POST',
            'post_data' => json_encode($post_data),
            'success_callback_function' => 'erp_insert_new_trial',
            'success_callback_params' => json_encode(['product_id' => 822]),
        ];
        dbinsert('isp_iptv_api_queue', $data);

        $id = $activation->id;
        $request_data = new \Illuminate\Http\Request;
        $request_data->id = $id;
        app(\App\Http\Controllers\CustomController::class)->provisionService($request_data, 'sub_activations', $id);
    }
}

function schedule_queue_easypanel_updates()
{
    // create status requests to enable or disable
    $easypanel_lines = \DB::table('isp_data_iptv')->where('easypanel_id', '>', '')->where('is_deleted', 0)->get();
    foreach ($easypanel_lines as $iptv) {
        //Enable Easypanel lines
        if ($iptv->subscription_status == 'Enabled' && $iptv->easypanel_status != 'Enabled') {
            //https://heredns:port/api/wclient/v1/enable/[id]', $token, $username, $password, ['enable' => 1]
            $data = [
                'endpoint' => 'enable/'.$iptv->easypanel_id,
                'request_type' => 'POST',
                'post_data' => json_encode(['enable' => 1]),
                'success_callback_function' => 'easypanel_status_enabled',
                'row_id' => $iptv->id,
            ];
            dbinsert('isp_iptv_api_queue', $data);
        }

        //disable line
        if (($iptv->subscription_status == 'Disabled' && $iptv->easypanel_status == 'Enabled')) {
            //https://heredns:port/api/wclient/v1/enable/[id]', $token, $username, $password, ['enable' => 1]
            $data = [
                'endpoint' => 'enable/'.$iptv->easypanel_id,
                'request_type' => 'POST',
                'post_data' => json_encode(['enable' => 0]),
                'success_callback_function' => 'easypanel_status_disabled',
                'row_id' => $iptv->id,
            ];
            dbinsert('isp_iptv_api_queue', $data);
        }

        //Expire Disabled line
        if ($iptv->subscription_status == 'Expired') {
            //https://heredns:port/api/wclient/v1/enable/[id]', $token, $username, $password, ['enable' => 1]
            $data = [
                'endpoint' => 'enable/'.$iptv->easypanel_id,
                'request_type' => 'POST',
                'post_data' => json_encode(['enable' => 0]),
                'success_callback_function' => 'easypanel_status_expired',
                'row_id' => $iptv->id,
            ];
            dbinsert('isp_iptv_api_queue', $data);
        }
    }
}

function schedule_easypanel_api_process_queue()
{
    // $easypanel_activations = \DB::table('sub_activations')->where('provision_type','iptv')->where('status','Pending')->where('step',1)->count();
    // $easypanel_trial_activations = \DB::table('sub_activations')->where('provision_type','easypanel_trial')->where('status','Pending')->where('step',1)->count();
    // $num_easypanel_requests = \DB::table('isp_iptv_api_queue')->where('processed',0)->where('success_callback_function','insert_new_line_iptv_center')->count();
    // $num_easypanel_trial_requests = \DB::table('isp_iptv_api_queue')->where('processed',0)->where('success_callback_function','erp_insert_new_trial')->count();
    // if($num_easypanel_requests > $easypanel_activations){
    //     $delete_easypanel_requests = $num_easypanel_requests - $easypanel_activations;
    //     $ids = \DB::table('isp_iptv_api_queue')->where('processed',0)->where('success_callback_function','insert_new_line_iptv_center')->limit($delete_easypanel_requests)->pluck('id')->toArray();
    //     \DB::table('isp_iptv_api_queue')->whereIn('id',$ids)->delete();
    // }
    // if($num_easypanel_trial_requests > $easypanel_trial_activations){
    //     $delete_easypanel_trial_requests = $num_easypanel_trial_requests - $easypanel_trial_activations;
    //     $ids = \DB::table('isp_iptv_api_queue')->where('processed',0)->where('success_callback_function','erp_insert_new_trial')->limit($delete_easypanel_trial_requests)->pluck('id')->toArray();
    //     \DB::table('isp_iptv_api_queue')->whereIn('id',$ids)->delete();
    // }
    $requests = \DB::table('isp_iptv_api_queue')->where('processed', 0)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

    if (! $requests) {
        return false;
    } else {
        foreach ($requests as $request) {
            button_easypanel_api_process_queue($request);
        }

        return true;
    }

    // try{
    //     $params = [];
    //     if(!empty($request->post_data)){
    //         $params = json_decode($request->post_data,true);
    //     }

    //     $response = easypanel_api_requests($request->endpoint,$params,$request->request_type);
    //     $json = json_decode($response['result']);
    //     $completed = false;
    //     if(!empty($json) && isset($json->result) && $json->result == true){
    //         $completed = true;
    //     }
    //     if($response['code'] === 200){
    //         $completed = true;
    //     }

    //     if(!$completed){
    //          \DB::table('isp_iptv_api_queue')->where('id',$request->id)->update(['callback_error' => '','last_attempt' => date('Y-m-d H:i:s'),'api_response' => $response['result']]);
    //     }else{
    //          \DB::table('isp_iptv_api_queue')->where('id',$request->id)->update(['callback_error' => '','last_attempt' => date('Y-m-d H:i:s'),'processed' => 1,'api_response' => $response['result']]);

    //          if(!empty($request->success_callback_function) && function_exists($request->success_callback_function)){
    //             $fn = $request->success_callback_function;
    //             $json = json_decode($response['result']);
    //             if($request->success_callback_function == 'import_easypanel_lines'){
    //             $json = $json->data;
    //             }

    //             if(!empty($request->success_callback_params)){
    //                 $success_callback_params = json_decode($request->success_callback_params);

    //                 foreach($success_callback_params as $k => $v){
    //                     $json->{$k} = $v;
    //                 }
    //             }

    //             if(!empty($json) && !empty($request->row_id)){
    //                 $fn($json,$request->row_id);
    //             }else if(!empty($json) && empty($request->row_id)){
    //                 $fn($json);
    //             }else if(!empty($json) && !empty($request->row_id)){
    //                 $fn($request->row_id);
    //             }

    //          }
    //     }
    // //dd($response,$json);
    // }catch(\Throwable $ex){
    //     \DB::table('isp_iptv_api_queue')->where('id',$request->id)->update(['callback_error' => $ex->getMessage()]);
    //     //dd($response,$json,$request,$ex->getMessage());
    // }
}

function schedule_set_easypanel_from_subscriptions()
{
    // \DB::table('isp_data_iptv')->update(['available_at'=>null]);
    // \DB::table('isp_data_iptv')->where('is_deleted',0)->where('account_id',0)->update(['available_at'=>date('Y-m-d',strtotime(' -1 month'))]);
    // $deleted_subscriptions = \DB::table('sub_services')->whereIn('provision_type',['iptv','iptv_global'])->where('status','Deleted')->orderBy('id','asc')->get();
    // foreach($deleted_subscriptions as $s){
    //     \DB::table('isp_data_iptv')->where('is_deleted',0)->where('account_id',0)->where('username',$s->detail)->update(['available_at'=>date('Y-m-d',strtotime($s->deleted_at.' +15 days'))]);
    // }

    $subscriptions = \DB::table('sub_services')->whereIn('provision_type', ['easypanel_trial', 'iptv', 'iptv_global'])->where('status', '!=', 'Deleted')->get();
    $subscription_ids = $subscriptions->pluck('id')->toArray();

    foreach ($subscriptions as $s) {
        \DB::table('isp_data_iptv')->where('username', $s->detail)->where('subscription_id', 0)->update(['subscription_id' => $s->id]);
    }

    foreach ($subscriptions as $s) {
        \DB::table('isp_data_iptv')->where('subscription_id', $s->id)->update(['product_id' => $s->product_id]);
    }

    foreach ($subscriptions as $s) {
        $e = \DB::table('isp_data_iptv')->where('subscription_id', $s->id)->count();
        if (! $e) {
        }
        \DB::table('isp_data_iptv')->where('subscription_id', $s->id)->update(['is_deleted' => 0, 'subscription_status' => $s->status]);
    }
    $easypanel_count = \DB::table('isp_data_iptv')->where('is_deleted', 0)->count();

    $iptvs = \DB::table('isp_data_iptv')->where('is_deleted', 0)->get();
    foreach ($iptvs as $iptv) {
        if ($iptv->expiry_date) {
            $date = Carbon\Carbon::parse($iptv->expiry_date);
            $now = Carbon\Carbon::today();

            $days_left = $date->diffInDays($now);
            if ($iptv->expiry_date < date('Y-m-d')) {
                $days_left = $days_left * -1;
            }

            \DB::table('isp_data_iptv')->where('id', $iptv->id)->update(['days_left' => $days_left]);
        }
        \DB::table('sub_services')->where('id', $iptv->subscription_id)->update(['detail' => $iptv->username]);
    }

    $subscriptions = \DB::table('sub_services')->whereIn('provision_type', ['easypanel_trial', 'iptv', 'iptv_global'])->where('status', '!=', 'Deleted')->get();
    foreach ($subscriptions as $s) {
        \DB::table('isp_data_iptv')->where('subscription_id', $s->id)->update(['account_id' => $s->account_id]);
    }
    $subscription_ids = \DB::table('sub_services')->where('status', '!=', 'Deleted')->whereIn('provision_type', ['easypanel_trial', 'iptv', 'iptv_global'])->pluck('id')->toArray();

    \DB::table('isp_data_iptv')->whereNotIn('subscription_id', $subscription_ids)->update(['is_deleted' => 1, 'subscription_id' => 0, 'account_id' => 0, 'subscription_status' => 'Deleted', 'easypanel_status' => 'Deleted']);

    // \DB::table('isp_data_iptv')->where('subscription_id',0)->where('expired',0)->where('available_at','<=',date('Y-m-d'))->update(['subscription_status'=>'Available']);

    // \DB::table('isp_data_iptv')->where('subscription_id',0)->where('expired',0)->where('available_at','>',date('Y-m-d'))->update(['subscription_status' => 'Redemption']);
    // \DB::table('isp_data_iptv')->where('subscription_id',0)->update(['subscription_status'=>'Expired']);

    // \DB::table('isp_data_iptv')->whereRaw('easypanel_status=subscription_status')->update(['status_match'=>1]);
    // \DB::table('isp_data_iptv')->whereRaw('easypanel_status!=subscription_status')->update(['status_match'=>0]);
    // \DB::table('isp_data_iptv')->whereIn('subscription_status',['Expired','Redemption'])->update(['status_match'=>1]);
    // \DB::table('isp_data_iptv')->where('is_deleted',1)->update(['status_match'=>1]);

    //  \DB::table('isp_data_iptv')->where('subscription_status','Deleted')
    //  ->where('trial',1)
    //  ->where('product_id',822)
    //  ->where('expiry_date','>',\DB::raw('DATE_ADD(created_at, INTERVAL 7 DAY)'))
    //  ->update(['trial' => 0,'product_id'=>808]);
    // // swap out expired lines for new line
    $expired_lines = \DB::table('isp_data_iptv')->where('subscription_status', 'Deleted')->where('is_deleted', 1)->get();
    foreach ($expired_lines as $iptv) {
        //     $easypanel_account = \DB::table('isp_data_iptv')
        //     ->where('trial',0)
        //     ->where('expired',0)
        //     ->where('product_id',$iptv->product_id)
        //     ->whereNotNull('available_at')
        //     ->where('available_at','<=',date('Y-m-d'))
        //     ->where('subscription_id',0)
        //     ->get()->first();

        if (! empty($easypanel_account)) {
            \DB::table('isp_data_iptv')->where('id', $iptv->id)->update(['account_id' => 0, 'subscription_id' => 0, 'subscription_status' => 'Deleted', 'is_deleted' => 1]);
            // \DB::table('isp_data_iptv')->where('id',$easypanel_account->id)->update(['account_id'=>$iptv->account_id,'subscription_id' => $iptv->subscription_id,'subscription_status' => $iptv->subscription_status]);
            \DB::table('sub_services')->where('id', $iptv->subscription_id)->update(['detail' => $easypanel_account->username]);
            // send_activation_email($iptv->subscription_id);
        }
    }
}

function schedule_import_easypanel_details()
{
    $lines = json_decode(easypanel_get_lines());
    $data = $lines->data;
    foreach ($data as $line) {
        $expiry_date = date('Y-m-d', strtotime($line->exp_date));
        $expiry_date = date('Y-m-d', strtotime($expiry_date.' - 1 day'));
        $expired = 0;
        if ($expiry_date < date('Y-m-d')) {
            $easypanel_status = 'Deleted';
        }
        if ($line->enabled) {
            $easypanel_status = 'Enabled';
        } elseif ($line->enabled == 0) {
            $easypanel_status = 'Disabled';
        }
        // vd($line);
        $date = Carbon\Carbon::parse($expiry_date);
        // vd($date);
        $now = Carbon\Carbon::today();
        // vd($now);

        $days_left = $date->diffInDays($now);
        if ($days_left < 2) {
            $easypanel_status = 'Expired';
        }
        // vd($line);
        $update_data = [
            'easypanel_id' => $line->id,
            'username' => $line->username,
            'password' => $line->password,
            'days_left' => $days_left,
            'expiry_date' => $expiry_date,
            'easypanel_status' => $easypanel_status,
        ];
        // DB::enableQueryLog();
        $result = \DB::table('isp_data_iptv')->updateOrInsert(['username' => $line->username], $update_data);
    }
}

function easypanel_create_line()
{
    $data = ['line_type' => 'line', 'package' => 62, 'description' => '', 'username' => 'test', 'password' => 'test123'];
    $r = gen_request('create-line', $data);

    return $r;
}

function import_easypanel_lines($data = null)
{
    if (! $data) {
        //     $iptvs = \DB::table('isp_data_iptv')->where('is_deleted',0)->get();
        $lines = json_decode(easypanel_get_lines());
        $data = $lines->data;
        //     }
    }

    $line_usernames = collect($data)->pluck('username')->toArray();
    if (count($line_usernames) > 0) {
        \DB::table('isp_data_iptv')->whereNotIn('username', $line_usernames)->update(['easypanel_status' => 'Deleted']);
    }
    // \DB::table('isp_data_iptv')->update(['days_left' => 0]);

    // exit;
    foreach ($data as $line) {
        $expiry_date = date('Y-m-d', strtotime($line->exp_date));
        $expiry_date = date('Y-m-d', strtotime($expiry_date.' - 1 day'));
        $expired = 0;
        if ($expiry_date < date('Y-m-d')) {
            $easypanel_status = 'Expired';
        }
        if ($line->enabled) {
            $easypanel_status = 'Enabled';
        } elseif ($line->disabled) {
            $easypanel_status = 'Disabled';
        }

        $date = Carbon\Carbon::parse($expiry_date);
        $now = Carbon\Carbon::today();

        $days_left = $date->diffInDays($now);
        if ($expiry_date < date('Y-m-d')) {
            $days_left = $days_left * -1;
        }
        $update_data = [
            'easypanel_id' => $line->id,
            'username' => $line->username,
            'password' => $line->password,
            'days_left' => $days_left,
            'expiry_date' => $expiry_date,
            'easypanel_status' => $easypanel_status,
        ];

        \DB::table('isp_data_iptv')->updateOrInsert(['username' => $line->username], $update_data);
    }
}

// function schedule_process_easypanel_activations() {
//     $product_ids = \DB::table('sub_activations')->whereIn('provision_type',['iptv','iptv_global'])->where('status','Pending')->where('step',1)->pluck('product_id')->unique()->toArray();
//     foreach($product_ids as $product_id) {
//         $activations = \DB::table('sub_activations')->where('product_id',$product_id)->whereIn('provision_type',['iptv','iptv_global'])->where('status','Pending')->where('step',1)->get();
//         foreach ($activations as $activation) {
//             $post_data = ["easypanel_id" => 1, "line_type" => "line", "package" => 62, "description" => 'new line', "username" => generate_strong_password(6), "password" => generate_strong_password(6)];
//             $post_data = json_encode($post_data);
//             vd($post_data);
//             // $result = json_decode(easypanel_create_line($post_data));
//             // $result->id = 1;
//             // $post_data = json_decode($post_data);
//             // $post_data->easypanel_id = 1; //$result->id;
//             // vd(generate_strong_password(6));
//             insert_new_line_iptv_center($post_data);

//             $data = [
//                 'endpoint' => 'create-line',
//                 'request_type' => 'POST',
//                 'post_data' => json_encode($post_data),
//                 'success_callback_function' => 'insert_new_line_iptv_center',
//                 'success_callback_params' => json_encode(['product_id' => $product_id])
//             ];
//             aa($data);
//             dbinsert('isp_iptv_api_queue',$data);
//             $request_data = new \Illuminate\Http\Request();
//             $id = $activation->id;
//             $request_data->id = $id;
//             aa($request_data);
//             app('App\Http\Controllers\CustomController')->provisionService($request_data, 'sub_activations', $id);
//         }
//     }
// }

function button_easypanel_api_process_queue($request)
{
    // return false;
    $request = \DB::table('isp_iptv_api_queue')->where('processed', 0)->where('id', $request->id)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get()->first();
    if (! $request) {
        return false;
    }
    try {
        $params = [];
        if (! empty($request->post_data)) {
            $params = json_decode($request->post_data, true);
        }

        $response = easypanel_api_requests($request->endpoint, $params, $request->request_type);
        $json = json_decode($response['result']);
        $completed = false;
        if (! empty($json) && isset($json->result) && $json->result == true) {
            $completed = true;
        }
        if ($response['code'] === 200) {
            $completed = true;
        }

        if (! $completed) {
            \DB::table('isp_iptv_api_queue')->where('id', $request->id)->update(['callback_error' => '', 'last_attempt' => date('Y-m-d H:i:s'), 'api_response' => $response['result']]);
        } else {
            \DB::table('isp_iptv_api_queue')->where('id', $request->id)->update(['callback_error' => '', 'last_attempt' => date('Y-m-d H:i:s'), 'processed' => 1, 'api_response' => $response['result']]);

            if (! empty($request->success_callback_function) && function_exists($request->success_callback_function)) {
                $fn = $request->success_callback_function;
                $json = json_decode($response['result']);
                if ($request->success_callback_function == 'import_easypanel_lines') {
                    $json = $json->data;
                }

                if (! empty($json) && ! empty($request->row_id)) {
                    $fn($json, $request->row_id);
                } elseif (! empty($json) && empty($request->row_id)) {
                    $fn($json);
                } elseif (! empty($json) && ! empty($request->row_id)) {
                    $fn($request->row_id);
                }
            }
        }
        //dd($response,$json);
    } catch (\Throwable $ex) {
        \DB::table('isp_iptv_api_queue')->where('id', $request->id)->update(['callback_error' => $ex->getMessage()]);
        //dd($response,$json,$request,$ex->getMessage());
    }

    return json_alert('Done');
}

// function schedule_import_erp_details($request){
//     \DB::table('isp_data_iptv')->whereRaw('easypanel_status=subscription_status')->update(['status_match'=>1]);
//     \DB::table('isp_data_iptv')->whereRaw('easypanel_status!=subscription_status')->update(['status_match'=>0]);
//     \DB::table('isp_data_iptv')->whereIn('subscription_status',['Expired','Redemption'])->update(['status_match'=>1]);
//     \DB::table('isp_data_iptv')->where('is_deleted',1)->update(['status_match'=>1]);
// }

function button_easypanel_send_credentials_all($request)
{
    $subscription_ids = \DB::table('isp_data_iptv')->where('is_deleted', 0)->pluck('subscription_id')->toArray();
    foreach ($subscription_ids as $subscription_id) {
        send_activation_email($subscription_id);
    }

    return json_alert('Activation emails sent');
}
function button_easypanel_send_credentials($request)
{
    $subscription_id = \DB::table('isp_data_iptv')->where('id', $request->id)->where('is_deleted', 0)->pluck('subscription_id')->first();

    send_activation_email($subscription_id);

    return json_alert('Activation email sent');
}

// function insert_new_line_iptv_center($data){
//     aa($data);
//     $data = json_decode($data);
//     aa($data);
//     $expiry_date = date('Y-m-d',$data->expire_date);
//     $expired = 0;
//     if($expiry_date < date('Y-m-d')){
//         $easypanel_status = 'Expired';
//     }

//     if($line->enabled){
//         $easypanel_status = 'Enabled';
//     }else{
//         $easypanel_status = 'Disabled';
//     }

//     $date = Carbon\Carbon::parse($expiry_date);
//     $now = Carbon\Carbon::today();

//     $days_left = $date->diffInDays($now);
//     if($expiry_date < date('Y-m-d')){
//         $days_left = $days_left*-1;
//     }
//     $product_id = 0;
//     if(!empty($data->product_id)){
//         $product_id = $data->product_id;
//     }
//     $insert_data = [
//         'bouquet_set' => 0,
//         'username' => $data->username,
//         'password' => $data->password,
//         'product_id' => $product_id,
//         'easypanel_id' => $data->easypanel_id,
//         'expiry_date' => $expiry_date,
//         'days_left' => $days_left,
//         'easypanel_status' => 'Enabled',
//     ];
//     $insert_data['table_data'] = $insert_data;
//     aa($insert_data);
//     dbinsert('isp_data_iptv',$insert_data);
// }

function insert_new_line_iptv_center($data)
{
    aa($data); // Log raw data
    $data = json_decode($data);
    aa($data); // Log decoded data

    $expiry_date = date('Y-m-d', $data->expire_date);
    $expired = 0;
    if ($expiry_date < date('Y-m-d')) {
        $easypanel_status = 'Expired';
    }

    if ($line->enabled) {
        $easypanel_status = 'Enabled';
    } else {
        $easyppanel_status = 'Disabled';
    }

    $date = Carbon\Carbon::parse($expiry_date);
    $now = Carbon\Carbon::today();

    $days_left = $date->diffInDays($now);
    if ($expiry_date < date('Y-m-d')) {
        $days_left = $days_left * -1;
    }

    $product_id = 0;
    if (! empty($data->product_id)) {
        $product_id = $data->product_id;
    }

    $insert_data = [
        'bouquet_set' => 0,
        'username' => $data->username,
        'password' => $data->password,
        'product_id' => $product_id,
        'easypanel_id' => $data->easypanel_id,
        'expiry_date' => $expiry_date,
        'days_left' => $days_left,
        'easypanel_status' => 'Enabled',
    ];
    $insert_data['table_data'] = $insert_data;
    aa($insert_data); // Log insert data
    dbinsert('isp_data_iptv', $insert_data);
}
function erp_extend($data, $row_id)
{
    \DB::table('isp_data_iptv')->where('id', $row_id)->update(['expiry_date' => date('Y-m-d', strtotime('+1 months')), 'easypanel_status' => 'Enabled']);
}

function easypanel_status_enabled($data, $row_id)
{
    \DB::table('isp_data_iptv')->where('id', $row_id)->update(['easypanel_status' => 'Enabled']);
}
function easypanel_status_disabled($data, $row_id)
{
    \DB::table('isp_data_iptv')->where('id', $row_id)->update(['easypanel_status' => 'Disabled']);
}

function easypanel_status_expired($data, $row_id)
{
    \DB::table('isp_data_iptv')->where('id', $row_id)->update(['easypanel_status' => 'Expired']);
}

function erp_insert_new_trial($data)
{
    $expiry_date = date('Y-m-d', $data->expire_date);
    $expired = 0;
    if ($expiry_date < date('Y-m-d')) {
        $expired = 1;
    }
    if ($line->enabled) {
        $easypanel_status = 'Enabled';
    } else {
        $easypanel_status = 'Disabled';
    }

    $date = Carbon\Carbon::parse($expiry_date);
    $now = Carbon\Carbon::today();

    $days_left = $date->diffInDays($now);
    if ($expiry_date < date('Y-m-d')) {
        $days_left = $days_left * -1;
    }
    $insert_data = [
        'bouquet_set' => 0,
        'username' => $data->username,
        'password' => $data->password,
        'easypanel_id' => $data->id,
        'expiry_date' => $expiry_date,
        'days_left' => $days_left,
        'easypanel_status' => 'Enabled',
        'trial' => 1,
        'product_id' => 822, // iptvglobaltrial
        'bouquet_set' => 1,
    ];

    dbinsert('isp_data_iptv', $insert_data);
}

function button_easypanel_create_new_line($request)
{
    $post_data = ['line_type' => 'line', 'package' => 3, 'description' => 'new line', 'username' => generate_strong_password(), 'password' => generate_strong_password()];

    $data = [
        'endpoint' => 'create-line',
        'request_type' => 'POST',
        'post_data' => json_encode($post_data),
        'success_callback_function' => 'insert_new_line_iptv_center',
    ];
    dbinsert('isp_iptv_api_queue', $data);

    return json_alert('New line added to api queue');
}

function button_easypanel_create_new_trial($request)
{
    $post_data = ['line_type' => 'line', 'package' => 7, 'description' => 'trial line', 'username' => generate_strong_password(), 'password' => generate_strong_password()];

    $data = [
        'endpoint' => 'create-line/1',
        'request_type' => 'POST',
        'post_data' => json_encode($post_data),
        'success_callback_function' => 'erp_insert_new_trial',
    ];
    dbinsert('isp_iptv_api_queue', $data);

    return json_alert('New line added to api queue');
}

function import_easypanel_packages($data)
{
    try {
        $package_ids = collect($data)->pluck('id')->toArray();
        if (count($package_ids) > 0) {
            \DB::table('isp_iptv_packages')->whereNotIn('id', $package_ids)->delete();
        }
        foreach ($data as $package) {
            $package_data = (array) $package;
            $e = \DB::table('isp_iptv_packages')->where('id', $package->id)->count();
            if (! $e) {
                dbinsert('isp_iptv_packages', $package_data);
            } else {
                dbset('isp_iptv_packages', 'id', $package->id, $package_data);
            }
        }
    } catch (\Throwable $ex) {
    }
}

function easypanel_api_requests($endpoint, $params = [], $type = 'GET')
{
    try {
        $api_url = 'https://cms.easyip.xyz:443/api/wclient/v1/';
        $auth_user = 'cloudtelecoms';
        $auth_pass = 'Webmin786';
        $auth_token = '65aeb70670f1865aeb70670f1a65aeb70670f1b65aeb70670f1c';

        $client = new GuzzleHttp\Client(['allow_redirects' => true, 'verify' => false]);
        $URI = $api_url.$endpoint;
        if (count($params) > 0) {
            $params['body'] = json_encode($params);
        }
        $params['headers'] = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$auth_token,
            'username' => $auth_user,
            'password' => $auth_pass,
        ];
        $params['curl'] = [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ];
        if ($type == 'DELETE') {
            $response = $client->delete($URI, $params);
        } elseif ($type == 'POST') {
            $response = $client->post($URI, $params);
        } else {
            $response = $client->get($URI, $params);
        }

        $status_code = $response->getStatusCode();
        $message = $response->getBody()->getContents();

        //$json = json_decode($message);
        return ['code' => $status_code, 'result' => $message];
    } catch (\Throwable $ex) {
        return ['code' => 0, 'result' => $ex->getMessage()];
    }
}

function easypanel_get_packages()
{ //62 = 1 month
    $r = easypanel_api_requests('packages/0');
}

function easypanel_get_lines()
{
    // $url = 'http://cms.easy
    $r = gen_request('lines/lines?per_page=1000');

    return $r;
}

function easypanel_enable_line()
{
    gen_request('enable/167059', ['enable' => 1]);
}

function easypanel_disable_line()
{
    gen_request('enable/167059', ['enable' => 0]);
}
function easypanel_extend_line($username)
{
    $result = gen_request("extend/'.$username.'", ['package' => 3]); //One month, one connection

    return $result;
}

function easypanel_delete_line($username = false)
{
    // 170162	 nRDZSx3Q
    $r = easypanel_api_requests('delete/170162', [], 'POST');
}

function gen_request($endpoint, $post = [])
{
    // header('Content-Type: application/json');
    $url = 'https://cms.easyip.xyz/api/wclient/v1/'.$endpoint;
    $client_username = 'cloudtelecoms';
    $client_password = 'Webmin786';
    $token = '65aeb70670f1865aeb70670f1a65aeb70670f1b65aeb70670f1c';

    $ch = curl_init($url);
    $authorization = 'Authorization: Bearer '.$token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', $authorization, 'username: '.$client_username, 'password: '.$client_password]); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if (! empty($post)) {
        $post = json_encode($post);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($ch);

    if ($result === false) {
        echo 'Curl error: '.curl_error($ch);
    } else {
        // echo 'Operation completed without any errors';
    }

    curl_close($ch);

    return $result;
}
