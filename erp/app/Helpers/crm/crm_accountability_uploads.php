<?php

function aftersave_accountability_process_form_uploads($request)
{
    $notfound_result = '';
    $import_result = '';
    $au = \DB::table('crm_accountability_imports')->where('id', $request->id)->get()->first();

    $form_a_debtor_status_id = get_accountability_debtor_status_id('Form A');
    $form_b_debtor_status_id = get_accountability_debtor_status_id('Form B');
    $collections_debtor_status_id = get_accountability_debtor_status_id('Form B');
    $debtors_file = null;

    $uploads_path = uploads_path(2042);
    if (! empty($au->small_collections_file) && file_exists($uploads_path.'/'.$au->small_collections_file)) {
        $file_content = file_get_contents($uploads_path.'/'.$au->small_collections_file);

        $debtors_file = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($file_content)));
        $keys = $debtors_file[0];

        unset($debtors_file[0]);
        foreach ($debtors_file as $i => $t) {
            $debtors_file[$i] = array_combine($keys, $t);
        }

        $debtors_file = collect($debtors_file)->sortByDesc('date')->unique('Company/Individual')->filter();
        foreach ($debtors_file as $i => $row) {
            if (empty($row['account number'])) {
                $account_number = \DB::table('crm_accountability_import_matches')->where('company', $company)->pluck('account_number_id')->first();
                if (empty($account_number)) {
                    unset($debtors_file[$i]);

                    $company = $row['Company/Individual'];
                    $company = str_replace([' QUERY', ' DISPUTE'], '', $company);
                    $notfound_result .= $company.' company not found.<br>';
                    $e = \DB::table('crm_accountability_import_matches')->where('company', $company)->count();
                    if (! $e) {
                        dbinsert('crm_accountability_import_matches', ['company' => $company]);
                    }
                } else {
                    $debtors_file[$i]['account number'] = $account_number;
                }
            }
        }
        $debtors_file = collect($debtors_file)->sortByDesc('date')->unique('account number')->filter();

        $import_result .= '<b>Collections Update</b><br>';
        foreach ($debtors_file as $row) {
            $company = $row['Company/Individual'];
            $company = str_replace([' QUERY', ' DISPUTE'], '', $company);
            $account_id = false;
            if (! empty($row['account number'])) {
                $account_id = \DB::table('crm_accounts')->where('id', $row['account number'])->pluck('id')->first();
            }

            if (! $account_id) {
                $account_id = \DB::table('crm_accountability_import_matches')->where('company', $company)->pluck('account_number_id')->first();
                if (! $account_id) {
                    $e = \DB::table('crm_accountability_import_matches')->where('company', $company)->count();
                    if (! $e) {
                        dbinsert('crm_accountability_import_matches', ['company' => $company]);
                    }
                    $num_accounts = \DB::table('crm_accounts')->where('company', $company)->count();
                    if ($num_accounts == 1) {
                        $account_id = \DB::table('crm_accounts')->where('company', $company)->pluck('id')->first();
                        dbset('crm_accountability_import_matches', 'company', $company, ['account_number_id' => $account_id]);
                    }
                }
            }

            if (! $account_id) {
                $notfound_result .= $company.' company not found.<br>';
            } else {
                $debtor_status_id = 0;

                $amount = currency(str_replace('R ', '', $row['Amount']));

                if ($amount < 5000) {
                    $debtor_status_id = $collections_debtor_status_id;
                }
                if ($amount > 5000) {
                    $debtor_status_id = $collections_debtor_status_id;
                }

                if ($debtor_status_id) {
                    $updated = \DB::table('crm_accounts')->where('id', $account_id)->update(['accountability_current_status_id' => $debtor_status_id]);

                    $import_result .= $company.'.<br>';
                } else {
                    $import_result .= $company.' Debtor status not found.<br>';
                }
            }
        }
    }
    unset($debtors_file);

    if (! empty($au->large_collections_file) && file_exists($uploads_path.'/'.$au->large_collections_file)) {
        $file_content = file_get_contents($uploads_path.'/'.$au->large_collections_file);

        $debtors_file = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($file_content)));
        $keys = $debtors_file[0];

        unset($debtors_file[0]);
        foreach ($debtors_file as $i => $t) {
            $debtors_file[$i] = array_combine($keys, $t);
        }

        $debtors_file = collect($debtors_file)->sortByDesc('date')->unique('Company/Individual')->filter();
        foreach ($debtors_file as $i => $row) {
            if (empty($row['account number'])) {
                $account_number = \DB::table('crm_accountability_import_matches')->where('company', $company)->pluck('account_number_id')->first();
                if (empty($account_number)) {
                    unset($debtors_file[$i]);

                    $company = $row['Company/Individual'];
                    $company = str_replace([' QUERY', ' DISPUTE'], '', $company);
                    $notfound_result .= $company.' company not found.<br>';
                    $e = \DB::table('crm_accountability_import_matches')->where('company', $company)->count();
                    if (! $e) {
                        dbinsert('crm_accountability_import_matches', ['company' => $company]);
                    }
                } else {
                    $debtors_file[$i]['account number'] = $account_number;
                }
            }
        }
        $debtors_file = collect($debtors_file)->sortByDesc('date')->unique('account number')->filter();

        $import_result .= '<b>Collections Update</b><br>';
        foreach ($debtors_file as $row) {
            $company = $row['Company/Individual'];
            $company = str_replace([' QUERY', ' DISPUTE'], '', $company);

            $account_id = false;
            if (! empty($row['account number'])) {
                $account_id = \DB::table('crm_accounts')->where('id', $row['account number'])->pluck('id')->first();
            }

            if (! $account_id) {
                $account_id = \DB::table('crm_accountability_import_matches')->where('company', $company)->pluck('account_number_id')->first();
                if (! $account_id) {
                    $e = \DB::table('crm_accountability_import_matches')->where('company', $company)->count();
                    if (! $e) {
                        dbinsert('crm_accountability_import_matches', ['company' => $company]);
                    }
                    $num_accounts = \DB::table('crm_accounts')->where('company', $company)->count();
                    if ($num_accounts == 1) {
                        $account_id = \DB::table('crm_accounts')->where('company', $company)->pluck('id')->first();
                        dbset('crm_accountability_import_matches', 'company', $company, ['account_number_id' => $account_id]);
                    }
                }
            }

            if (! $account_id) {
                $notfound_result .= $company.' company not found.<br>';
            } else {
                $debtor_status_id = 0;

                $amount = currency(str_replace('R ', '', $row['Amount']));

                if ($amount < 5000) {
                    $debtor_status_id = $collections_debtor_status_id;
                }
                if ($amount > 5000) {
                    $debtor_status_id = $collections_debtor_status_id;
                }

                if ($debtor_status_id) {
                    $updated = \DB::table('crm_accounts')->where('id', $account_id)->update(['accountability_current_status_id' => $debtor_status_id]);

                    $import_result .= $company.'.<br>';
                } else {
                    $import_result .= $company.' Debtor status not found.<br>';
                }
            }
        }
    }

    unset($debtors_file);

    if (! empty($au->forms_export_file) && file_exists($uploads_path.'/'.$au->forms_export_file)) {
        $file_content = file_get_contents($uploads_path.'/'.$au->forms_export_file);

        $debtors_file = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($file_content)));
        $keys = $debtors_file[0];

        unset($debtors_file[0]);
        foreach ($debtors_file as $i => $t) {
            $debtors_file[$i] = array_combine($keys, $t);
        }

        $debtors_file = collect($debtors_file)->sortByDesc('date')->unique('Company/Individual')->filter();
        foreach ($debtors_file as $i => $row) {
            if (empty($row['account number'])) {
                $account_number = \DB::table('crm_accountability_import_matches')->where('company', $company)->pluck('account_number_id')->first();
                if (empty($account_number)) {
                    unset($debtors_file[$i]);

                    $company = $row['Company/Individual'];
                    $company = str_replace([' QUERY', ' DISPUTE'], '', $company);
                    $notfound_result .= $company.' company not found.<br>';
                    $e = \DB::table('crm_accountability_import_matches')->where('company', $company)->count();
                    if (! $e) {
                        dbinsert('crm_accountability_import_matches', ['company' => $company]);
                    }
                } else {
                    $debtors_file[$i]['account number'] = $account_number;
                }
            }
        }
        $debtors_file = collect($debtors_file)->sortByDesc('date')->unique('account number')->filter();

        $notfound_result = '';
        $import_result = '<b>Form A and B Update</b><br>';
        foreach ($debtors_file as $row) {
            $company = $row['Company/Individual'];
            $company = str_replace([' QUERY', ' DISPUTE'], '', $company);

            $account_id = false;
            if (! empty($row['account number'])) {
                $account_id = \DB::table('crm_accounts')->where('id', $row['account number'])->pluck('id')->first();
            }

            if (! $account_id) {
                $account_id = \DB::table('crm_accountability_import_matches')->where('company', $company)->pluck('account_number_id')->first();
                if (! $account_id) {
                    $e = \DB::table('crm_accountability_import_matches')->where('company', $company)->count();
                    if (! $e) {
                        dbinsert('crm_accountability_import_matches', ['company' => $company]);
                    }
                    $num_accounts = \DB::table('crm_accounts')->where('company', $company)->count();
                    if ($num_accounts == 1) {
                        $account_id = \DB::table('crm_accounts')->where('company', $company)->pluck('id')->first();
                        dbset('crm_accountability_import_matches', 'company', $company, ['account_number_id' => $account_id]);
                    }
                }
            }

            if (! $account_id) {
                $notfound_result .= $company.' company not found.<br>';
            } else {
                $debtor_status_id = 0;

                if ($row['Form A'] == 'Yes') {
                    $debtor_status_id = $form_a_debtor_status_id;
                }
                if ($row['Form B'] == 'Yes') {
                    $debtor_status_id = $form_b_debtor_status_id;
                }
                if ($row['Form C'] == 'Yes') {
                    continue;
                }

                if ($debtor_status_id) {
                    $updated = \DB::table('crm_accounts')->where('id', $account_id)->update(['accountability_current_status_id' => $debtor_status_id]);

                    $import_result .= $company.' updated.<br>';
                } else {
                    $import_result .= $company.' Debtor status not found.<br>';
                }
            }
        }
    }

    if ($notfound_result > '') {
        $import_result = '<b>Companies not found: </b><br>'.$notfound_result.$import_result;
    }

    \DB::table('crm_accounts')->update(['accountability_match' => 0]);
    \DB::table('crm_accounts')->whereRaw('debtor_status_id=accountability_current_status_id')->update(['accountability_match' => 1]);

    \DB::table('crm_accountability_imports')->where('id', $request->id)->update(['import_result' => $import_result]);

}

function aftercommit_accountability_rename_files($request)
{
    $au = \DB::table('crm_accountability_imports')->where('id', $request->id)->get()->first();

    $uploads_path = uploads_path(2042);
    if (! empty($au->small_collections_file) && file_exists($uploads_path.'/'.$au->small_collections_file)) {
        if (! str_starts_with($au->small_collections_file, 'upload'.$au->id)) {
            \DB::table('crm_accountability_imports')->where('id', $request->id)->update(['small_collections_file' => 'upload'.$au->id.$au->small_collections_file]);
            File::move($uploads_path.'/'.$au->small_collections_file, $uploads_path.'/'.'upload'.$au->id.$au->small_collections_file);
        }
    }

    if (! empty($au->large_collections_file) && file_exists($uploads_path.'/'.$au->large_collections_file)) {
        if (! str_starts_with($au->large_collections_file, 'upload'.$au->id)) {
            \DB::table('crm_accountability_imports')->where('id', $request->id)->update(['large_collections_file' => 'upload'.$au->id.$au->small_collections_file]);
            File::move($uploads_path.'/'.$au->large_collections_file, $uploads_path.'/'.'upload'.$au->id.$au->large_collections_file);
        }
    }

    if (! empty($au->forms_export_file) && file_exists($uploads_path.'/'.$au->forms_export_file)) {
        if (! str_starts_with($au->forms_export_file, 'upload'.$au->id)) {
            \DB::table('crm_accountability_imports')->where('id', $request->id)->update(['forms_export_file' => 'upload'.$au->id.$au->forms_export_file]);
            File::move($uploads_path.'/'.$au->forms_export_file, $uploads_path.'/'.'upload'.$au->id.$au->forms_export_file);
        }
    }
}
