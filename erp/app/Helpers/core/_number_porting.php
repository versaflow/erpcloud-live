<?php

function schedule_porting_data_source_import()
{
    porting_data_source_import();
}

function schedule_porting_import_ftp()
{
    //return false;
    set_time_limit(0);
    if (! is_main_instance()) {
        return false;
    }

    try {
        $ftp = Storage::disk('porting_data_gnp_source');
        // vd($ftp);
        $directories = $ftp->allDirectories();
        // vd($directories);
        // $directories = ftp_nlist($ftp, '.');
        $ftp_valid = true;
    } catch (\Throwable $ex) {
        // vd($ex);
        exception_log($ex);
        $ftp_valid = false;
    }

    if (! $directories) {
        admin_email('Porting ftp down, no directories returned');

        return false;
    }

    if ($ftp_valid) {
        foreach ($directories as $directory) {
            if ($directory != 'DWNLDS') {
                continue;
            }
            $files = Storage::disk('porting_data_gnp_source')->files($directory);
        }
    }
    // vd($files);
    if (! $files) {
        admin_email('Porting ftp down, no files returned');

        return false;
    }

    if ($files) {
        foreach ($files as $file) {
            $processed = \DB::connection('default')->table('erp_system_log')->where('type', 'porting_import_gnp')->where('created_date', '>=', '2025-01-01')->where('action', $file)->where('result', 'success')->count();
            // vd($processed);
            if (! $processed && ! Storage::disk('porting_input')->exists($file)) {
                // vd($file);
                Storage::disk('porting_input')->writeStream($file, Storage::disk('porting_data_gnp_source')->readStream($file));
            }
            if (! $processed && ! Storage::disk('porting_data_gnp_local')->exists($file)) {
                // vd($file);
                Storage::disk('porting_data_gnp_local')->writeStream($file, Storage::disk('porting_data_gnp_source')->readStream($file));
                system_log('porting_import_gnp', $file, 'success', 'porting_import', 'Daily', 1);
            }
        }
    }

    try {
        $directories = Storage::disk('porting_data_mnp_source')->allDirectories();
        $ftp_valid = true;
    } catch (\Throwable $ex) {
        exception_log($ex);
        $ftp_valid = false;
    }

    if ($ftp_valid) {
        foreach ($directories as $directory) {
            if ($directory != 'DWNLDS') {
                continue;
            }

            $files = Storage::disk('porting_data_mnp_source')->files($directory);

            foreach ($files as $file) {
                $processed = \DB::connection('default')->table('erp_system_log')->where('type', 'porting_import_mnp')->where('created_date', '>=', '2025-01-01')->where('result', 'success')->where('action', $file)->where('result', 'success')->count();

                if (! $processed && ! Storage::disk('porting_input')->exists($file)) {
                    Storage::disk('porting_input')->writeStream($file, Storage::disk('porting_data_mnp_source')->readStream($file));
                }
                if (! $processed && ! Storage::disk('porting_data_mnp_local')->exists($file)) {
                    Storage::disk('porting_data_mnp_local')->writeStream($file, Storage::disk('porting_data_mnp_source')->readStream($file));
                    system_log('porting_import_mnp', $file, 'success', 'porting_import', 'Daily', 1);
                }
            }
        }
    }

    // insert to db
    //porting_data_source_import();
}

function split_xml_file($file)
{
    $cmd = 'cd /root/porting_input/DWNLDS && /home/_admin/splitxml.sh '.$file;
    // aa($cmd);
    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);

    // aa($result);
    return $result;
}

function porting_data_source_import($counter = 0)
{
    return false;
    set_time_limit(0);
    if (! is_main_instance()) {
        return false;
    }
    if ($counter > 40) {
        return false;
    }
    // awk '/<ActivatedNumber/{f=1; out="file_"(++c)".xml"} f{print > out} /<\/ActivatedNumber>/{close(out); f=0}' FCRDBDownload20221003151740.xml

    /*
    awk -v maxRecs=1000 '
    /<ActivatedNumber>/ && ((++recNr % maxRecs) == 1) {
    close(out); out="fcrdbsplit_" (++fileNr) ".xml"
    }
    { print > out }
    ' FCRDBDownload20221003151740.xml
    */

    $storage_path = '/home/erp/storage/';
    $routing_labels = \DB::connection('pbx_porting')->table('p_routing_labels')->get();

    $directories = Storage::disk('porting_input')->allDirectories();

    foreach ($directories as $directory) {
        if ($directory != 'DWNLDS') {
            continue;
        }

        if ($directory != 'COMPLETE') {
            $files = Storage::disk('porting_input')->files($directory);
            $files = collect($files)->sort();

            $list = [];
            foreach ($files as $file) {
                $date = date('Y-m-d H:i', strtotime(stripNonNumeric($file)));
                $list[] = ['filename' => $file, 'date' => $date];
            }
            $list = collect($list)->sortBy('date');

            $files = $list->take(1);
        } else {
            $files = Storage::disk('porting_input')->files($directory);
            $files = collect($files)->sort();
            $list = [];
            foreach ($files as $file) {
                $list[] = ['filename' => $file];
            }
            $list = collect($list);

            $files = $list->take(1);
        }

        //    if($files->count() > 0){
        //       if ($directory == 'COMPLETE') {

        //     foreach ($files as $file_arr) {
        //         $filename = $file_arr['filename'];

        //         $content =  Storage::disk('porting_input')->get($filename);
        //         $file_arr = explode(PHP_EOL,$content);
        //         if(!str_contains($file_arr[0],'<?xml')){
        //             array_unshift($file_arr,'<ActivatedNumbers>');
        //             array_unshift($file_arr,'<CRDBData>');
        //             array_unshift($file_arr,'<?xml version="1.0" encoding="UTF-8"/?/>');
        //         }
        //         if(end($file_arr) == PHP_EOL){
        //             array_pop($file_arr);
        //         }
        //         if(!str_contains(end($file_arr),'CRDBData')){
        //             $file_arr[] ='</ActivatedNumbers>';
        //             $file_arr[] ='</CRDBData>';
        //         }
        //         $file_content = implode(PHP_EOL,$file_arr);

        //         Storage::disk('porting_input')->put($filename, $file_content);
        //         }
        //       }
        //     }

        $insert_data_list = [];

        foreach ($files as $file_arr) {
            $file = $file_arr['filename'];

            // SPLIT FULL FILES

            if (str_ends_with($file, '.xml') && (str_starts_with($file, 'DWNLDS/FCRDB') || str_starts_with($file, 'DWNLDS/FGNP'))) {
                $storage_path = Storage::disk('porting_input')->getDriver()->getAdapter()->getPathPrefix();
                $file_path = $file;
                $result = split_xml_file($storage_path.$file_path);

                if (trim($result) == 'Splitting completed.') {
                    $new_file_path = str_replace('DWNLDS', 'COMPLETE', $file_path);

                    Storage::disk('porting_input')->move($file_path, $new_file_path);

                    $counter++;
                    porting_data_source_import($counter);

                    continue;
                }
            }

            if (str_contains($file, 'GNP')) {
                $import_table = 'p_ported_numbers_gnp_';
            }
            if (str_contains($file, 'CRDB')) {
                $import_table = 'p_ported_numbers_crdb_';
            }
            if (str_contains($file, 'mysmallfile')) {
                $import_table = 'p_ported_numbers_crdb_';
            }
            // delete previous processed xml
            if (str_ends_with($file, '.xml')) {
                // check system_log if file has been processed
                $processed = \DB::connection('default')->table('erp_system_log')->where('type', 'porting')->where('action', $file)->where('result', 'success')->count();
                if ($processed) {
                    //Storage::disk('porting_input')->delete($file);

                    $counter++;
                    porting_data_source_import($counter);

                    continue;
                }
                $num_list = [];

                $storage_path = Storage::disk('porting_input')->getDriver()->getAdapter()->getPathPrefix();

                $file_path = $storage_path.$file;

                $reader = new XMLReader;

                $reader->open($file_path);

                while ($reader->read()) {
                    if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'ActivatedNumber') {
                        $ported_number = new SimpleXMLElement($reader->readOuterXML());

                        $msisdn = '';
                        $numbers_to_import = [];
                        if (! empty($ported_number->DNRanges->DNFrom)) {
                            $msisdn = (string) $ported_number->DNRanges->DNFrom;
                            $range_start = (string) $ported_number->DNRanges->DNFrom;
                            $range_end = (string) $ported_number->DNRanges->DNTo;
                            if ($range_start != $range_end) {
                                $numbers_to_import = range($range_start, $range_end);
                            } else {
                                $numbers_to_import[] = $range_start;
                            }
                        } elseif (! empty($ported_number->MSISDN)) {
                            $numbers_to_import[] = (string) $ported_number->MSISDN;
                        }
                        $rnoroute = (string) $ported_number->RNORoute;
                        $network = $routing_labels->where('routing_label', $insert_data['rnoroute'])->pluck('gnp_no')->first();
                        $destination = $routing_labels->where('routing_label', $insert_data['rnoroute'])->pluck('gnp_no')->first();
                        if ($rnoroute == 'D007') {
                            $destination = 'fixed liquid';
                        }
                        if ($rnoroute == 'D000') {
                            $destination = 'fixed telkom';
                        }
                        if ($rnoroute == 'D082') {
                            $destination = 'mobile vodacom';
                        }
                        if ($rnoroute == 'D083') {
                            $destination = 'mobile mtn';
                        }
                        if ($rnoroute == 'D084') {
                            $destination = 'mobile cellc';
                        }
                        if ($rnoroute == 'D004') {
                            $destination = 'mobile telkom';
                        }
                        if (empty($destination)) {
                            $destination = '';
                        }
                        if (empty($network)) {
                            $network = $destination;
                        }

                        foreach ($numbers_to_import as $msisdn) {
                            $transaction_time = null;
                            if (! empty($ported_number->TransTime)) {
                                $ported_date = date('Y-m-d H:i:s', strtotime($ported_number->TransTime));
                                if (! empty($ported_date)) {
                                    $transaction_time = $ported_date;
                                }
                            }

                            $table = $import_table.substr($msisdn, 2, 1);
                            $num_list[$table][] = $msisdn;
                            $insert_data = [
                                'idnumber' => (string) $ported_number->IDNumber,
                                'msisdn' => (string) $msisdn,
                                'rnoroute' => (string) $ported_number->RNORoute,
                                'action' => (string) $ported_number->Action,
                                'transtime' => $transaction_time,
                            ];

                            $insert_data['network'] = $network;
                            $insert_data['destination'] = $destination;
                            /*
                            $existing_record = \DB::connection('pbx_porting')->table($table)->where('msisdn',$insert_data['msisdn'])->get()->first();
                            if(!empty($existing_record) && !empty($existing_record->id)){


                                if($transaction_time){
                                    if(!$existing_record->transtime || date('Y-m-d',strtotime($existing_record->transtime)) > date('Y-m-d',strtotime($transaction_time))){
                                        \DB::connection('pbx_porting')->table($table)->updateOrInsert(['msisdn' => $msisdn],$insert_data);
                                    }

                                }else{
                                    $insert_data_list[$table][] = $insert_data;
                                }
                            }else{
                                $insert_data_list[$table][] = $insert_data;
                            }
                            */
                            $insert_data_list[$table][] = $insert_data;

                            //\DB::connection('pbx_porting')->table($table)->updateOrInsert(['msisdn' => $msisdn],$insert_data);
                        }
                        unset($ported_number);
                        unset($numbers_to_import);
                        $reader->next('ActivatedNumber');
                    }
                }

                //dd($num_list);
                $reader->close();
                /*
                foreach($num_list as $table => $nlist){

                    $nlist = collect($nlist); // Make a collection to use the chunk method

                    // it will chunk the dataset in smaller collections containing 500 values each.
                    // Play with the value to get best result
                    $chunks = $nlist->chunk(500);

                    foreach ($chunks as $chunk)
                    {
                        \DB::connection('pbx_porting')->table($table)->whereIn('msisdn',$chunk->toArray())->delete();
                    }
                }
                */
                //dd($insert_data_list);
                foreach ($insert_data_list as $table => $insert_data) {
                    $insert_data = collect($insert_data); // Make a collection to use the chunk method

                    // it will chunk the dataset in smaller collections containing 500 values each.
                    // Play with the value to get best result
                    $chunks = $insert_data->chunk(1000);

                    foreach ($chunks as $chunk) {
                        \DB::connection('pbx_porting')->table($table)->insert($chunk->toArray());
                    }
                }
                foreach ($insert_data_list['p_ported_numbers_gnp_1'] as $n) {
                    if ($n['idnumber'] == '20160315043649NEOTEL27113525000000013772') {
                    }
                }
                //Storage::disk('porting_input')->delete($file);
                //system_log('porting', $file, 'success', 'porting', 'Daily', 1);
                //Storage::disk('porting_input')->delete($file.'.gz');

                $counter++;
                porting_data_source_import($counter);
            }

            // extract xml
            if (str_ends_with($file, '.gz')) {
                $file_path = '/root/porting_input/'.$file;

                shell_exec('gunzip -f '.$file_path);
                $counter++;
                porting_data_source_import($counter);
            }
        }
    }
}

function porting_split_ranges()
{
    $numbers = \DB::connection('pbx_porting')->table('p_ported_numbers')->whereRaw('msisdn != msisdn_to')->get();

    foreach ($numbers as $number) {
        $start = $number->msisdn;
        $end = $number->msisdn_to;
        $range = range($start, $end);
        foreach ($range as $n) {
            if ($n != $start) {
                $data = (array) $number;
                unset($data['id']);
                unset($data['type']);
                unset($data['msisdn_to']);
                aa($number);
                if ($number->type == 'mobile') {
                    $table = 'p_ported_numbers_crdb_';
                } else {
                    $table = 'p_ported_numbers_gnp_';
                }
                $table .= substr($number->msisdn, 2, 1);
                $data['msisdn'] = $n;
                $exists = \DB::connection('pbx_porting')->table($table)->where('msisdn', $n)->count();
                if (! $exists) {
                    \DB::connection('pbx_porting')->table($table)->insert($data);
                }
            }
        }
    }
}

function number_is_ported($number)
{
    if (substr($number, 0, 2) != '27') {
        return false;
    }
    $prefix = substr($number, 0, 3);

    $table = '';
    if ($prefix == '271') {
        $table = 'p_ported_numbers_gnp_1';
    }
    if ($prefix == '272') {
        $table = 'p_ported_numbers_gnp_2';
    }
    if ($prefix == '273') {
        $table = 'p_ported_numbers_gnp_3';
    }
    if ($prefix == '274') {
        $table = 'p_ported_numbers_gnp_4';
    }
    if ($prefix == '275') {
        $table = 'p_ported_numbers_gnp_5';
    }
    if ($prefix == '276') {
        $table = 'p_ported_numbers_crdb_6';
    }
    if ($prefix == '277') {
        $table = 'p_ported_numbers_crdb_7';
    }
    if ($prefix == '278') {
        $table = 'p_ported_numbers_crdb_8';
    }
    if (empty($table)) {
        return false;
    }
    $exists = \DB::connection('pbx_porting')->table($table)->where('msisdn', $number)->count();

    if ($exists >= 1) {
        return true;
    }

    return false;
}
/*
porting tables mainetance

DELETE FROM `call_records`.`p_ported_numbers_crdb_6`
WHERE (msisdn, action, id) NOT IN (
    SELECT msisdn, action, MAX(id) AS max_id
    FROM `call_records`.`p_ported_numbers_crdb_6`
    GROUP BY msisdn, action
);
*/
