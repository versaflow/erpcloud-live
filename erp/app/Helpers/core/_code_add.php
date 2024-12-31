<?php

function schedule_low_asr_ani()
{
    \DB::connection('pbx')->table('mon_low_asr_ani')->update(['is_deleted' => 1]);
    // $sql = "UPDATE p_phone_numbers set blocked = 0 where blocked = 1";
    // $check = \DB::connection('pbx')->update($sql);

    $unanswered_call_count = 0;
    $answered_call_count = 0;
    $call_count = 0;
    $duration = 0;
    $previous_caller_id_number = '';
    $previous_destination = '';

    $data_from = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $sql = "select distinct caller_id_number, id, ani, domain_name, destination, duration, hangup_cause, callee_id_number,hangup_time from call_records_outbound where 
        type = 'volume' and hangup_cause not like '%BLOCKED%' and hangup_cause <> 'SWITCH_CONGESTION' and hangup_cause <> 'NO_USER_RESPONSE' and 
        hangup_cause <> 'NORMAL_CLEARING' and hangup_cause <> 'ORIGINATOR_CANCEL' and hangup_cause <> 'UNALLOCATED_NUMBER' and hangup_cause not like '%NORMAL_TEMPORARY_FAILURE%' and hangup_cause <> 'CALL_REJECTED' 
        and hangup_time > '".$data_from."' order by ani desc";
    $records = DB::connection('pbx_cdr')->select($sql);

    foreach ($records as $record) {
        if ($previous_ani == '') {
            $previous_ani = $record->ani;
        }

        if ($record->ani == $previous_ani) {
            $call_count = $call_count + 1;
            if ($record->duration < 20) {
                $unanswered_call_count = $unanswered_call_count + 1;
            } else {
                $answered_call_count = $answered_call_count + 1;
                $duration = $duration + $record->duration;
            }
        }

        if ($record->ani != $previous_ani) {
            if ($call_count > 20) {
                $asr = currency($answered_call_count / $call_count * 100);

                if ($answered_call_count > 0) {
                    $acd = currency($duration / $answered_call_count);
                } else {
                    $acd = 0;
                }

                if ($asr < 10 or $acd < 10) {
                    $data = [
                        'time' => $record->hangup_time,
                        'domain_name' => $previous_domain_name,
                        'ani' => $previous_ani,
                        'destination' => $previous_destination,
                        'hangup_cause' => $previous_hangup_cause,
                        'caller_id_number' => $previous_caller_id_number,
                        'callee_id_number' => $previous_callee_id_number,
                        'asr' => (int) $asr,
                        'acd' => (int) $acd,
                        'num_calls' => $call_count,
                        'num_answered_calls' => $answered_call_count,
                    ];
                    $check = \DB::connection('pbx')->table('mon_low_asr_ani')->insertGetId($data);

                    // $sql = "UPDATE p_phone_numbers set blocked = 1 where number = '". $record->caller_id_number ."'";
                    // $check = \DB::connection('pbx')->update($sql);
                }
                $unanswered_call_count = 0;
                $answered_call_count = 0;
                $call_count = 0;
                $duration = 0;
            }
        }

        $previous_callee_id_number = $record->callee_id_number;
        $previous_caller_id_number = $record->caller_id_number;
        $previous_destination = $record->destination;
        $previous_hangup_cause = $record->hangup_cause;
        $previous_ani = $record->ani;
        $previous_domain_name = $record->domain_name;
    }
}

function button_rejected_calls_set_caller_id($request)
{
    $num = \DB::connection('pbx')->table('mon_rejected')->where('id', $request->id)->get()->first();
    $data['outbound_caller_id_number'] = $num->caller_id_number;
    $user_extension = \DB::table('erp_users')->where('id', session('user_id'))->whereNotNull('pbx_extension')->pluck('pbx_extension')->first();
    \DB::connection('pbx')->table('v_extensions')->where('extension', $user_extension)->where('user_context', 'pbx.cloudtools.co.za')->update($data);

    // \DB::connection('pbx')->table('v_extensions')->where('extension', '300')->where('user_context', 'pbx.cloudtools.co.za')->update($data);
    return json_alert('Done');
}

// schedule functions should not require the $request object

function blocked_calls()
{
    //     \DB::connection('pbx')->table('mon_rejected')->where('destination', 'mobile mtn')->delete();
    // \DB::connection('pbx')->table('mon_rejected')->where('destination', 'mobile telkom')->delete();
    // \DB::connection('pbx')->table('mon_rejected')->where('destination', 'fixed telkom')->delete();
    // $sql = "UPDATE p_phone_numbers set mtn_rejected = 0 where mtn_rejected = 0";
    // $check = \DB::connection('pbx')->update($sql);
    // $sql = "UPDATE p_phone_numbers set telkom_rejected = 0 where telkom_rejected = 0";
    // $check = \DB::connection('pbx')->update($sql);

    // $asr_msg = '';
    // $data_from = date('Y-m-d H:i:s', strtotime('-1 day'));
    // $sql = 'select id, type, hangup_time, caller_id_number, callee_id_number, ani, gateway, domain_name, destination, duration, hangup_cause from call_records_outbound where type = "wholesale" and caller_id_number <> "27111111111" and caller_id_number <> "passthrough" and gateway <> "SIMBANK" and ani > ""
    // and (destination in ("mobile mtn") and hangup_cause in ("SERVICE_NOT_IMPLEMENTED","CALL_REJECTED"))
    // and hangup_time > "'.$data_from.'" group by caller_id_number, destination desc order by hangup_time desc';

    // $records = DB::connection('pbx_cdr')->select($sql);
    // foreach ($records as $record) {
    //     $sql = "select count(*) as counter from mon_blocked where callee_id_number = '". $record->callee_id_number ."' and destination = '". $record->destination ."'";
    //     $counter = \DB::connection('pbx')->select($sql)[0]->counter;
    //     if ($counter == 0) {
    //         $data = [
    //             'datetime' => $record->hangup_time,
    //             'gateway' => $record->gateway,
    //             'domain_name' => $record->domain_name,
    //             'ani' => $record->ani,
    //             'destination' => $record->destination,
    //             'hangup_cause' => $record->hangup_cause,
    //             'caller_id_number' => $record->caller_id_number,
    //             'callee_id_number' => $record->callee_id_number
    //         ];
    //         \DB::connection('pbx')->table('mon_rejected')->insertGetId($data);

    //         if ($record->destination == 'mobile mtn') {
    //             $sql = "UPDATE p_phone_numbers set mtn_rejected = 1 where number = '". $record->caller_id_number ."'";
    //             $check = \DB::connection('pbx')->update($sql);
    //         }
    //         if ($record->destination == 'mobile telkom' or $record->destination == 'fixed telkom') {
    //             $sql = "UPDATE p_phone_numbers set telkom_rejected = 1 where number = '". $record->caller_id_number ."'";
    //             $check = \DB::connection('pbx')->update($sql);
    //         }
    //     }
    // }
}

function schedule_monthly_billing() {}
