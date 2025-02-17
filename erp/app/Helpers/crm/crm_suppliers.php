<?php

function aftersave_set_supplier_deleted_status($request)
{
    if ($request->status == 'Deleted') {
        \DB::table('crm_suppliers')->where('is_deleted', 1)->update(['status' => 'Deleted']);
    } else {
        \DB::table('crm_suppliers')->where('status', 'Enabled')->update(['is_deleted' => 0]);
    }
}

function schedule_supplier_recon_cdr()
{
    //\DB::table('crm_supplier_recon_details')->truncate();
    //\DB::table('crm_supplier_recons')->truncate();
    $supplier_ids = \DB::table('crm_supplier_documents')->where('docdate', '>=', date('Y-m-01', strtotime('first day last month')))->pluck('supplier_id')->unique()->toArray();
    $suppliers = \DB::table('crm_suppliers')->whereIn('id', $supplier_ids)->where('reconcile_monthly', 1)->get();

    $supplier_ids = $suppliers->pluck('id')->toArray();
    \DB::table('crm_supplier_recons')->whereNotIn('supplier_id', $supplier_ids)->update(['is_deleted' => 1]);

    // $suppliers = \DB::table('crm_suppliers')->where('id',4827)->get();

    $service_product_ids = \DB::table('crm_products')->pluck('id')->toArray();

    $months = [date('Y-m-01', strtotime(date('Y-m-01')))];
    $months[] = date('Y-m-01', strtotime(date('Y-m-01').' -1 month'));

    $exclude_product_ids = \DB::table('crm_products')->where('disable_recon', 1)->pluck('id')->toArray();

    // aa($months);
    \DB::table('crm_supplier_recons')->whereNull('invoice_date')->delete();
    $cdr_table = 'call_records_outbound';
    $fixed_destinations = ['fixed telkom', 'fixed liquid', 'mobile mtn', 'mobile vodacom', 'mobile cellc', 'mobile telkom'];
    foreach ($suppliers as $supplier) {
        // vd($supplier->id);
        // vd($supplier->company);
        $supplier_id = $supplier->id;
        foreach ($months as $month) {
            // vd($month);

            // if($supplier->id == 4829){

            //          aa($month);
            //     aa($supplier->company);
            //     aa($month);
            // }
            $wdata = [
                'supplier_id' => $supplier->id,
                'period' => date('Y-m', strtotime($month)),
            ];
            /*
            if($supplier->terms >= 30){
                $wdata['period'] =  date('Y-m', strtotime($month.' -1 month'));

                if($supplier->id == 4829){
                    // aa($month);
                    // aa($wdata['period']);
                }
            }
            */
            $supplier_recon_id = \DB::table('crm_supplier_recons')->where($wdata)->pluck('id')->first();
            // if($supplier->id == 4829){
            //     aa($supplier_recon_id);
            // }

            if ($supplier_recon_id) {
                \DB::table('crm_supplier_recon_details')->where('supplier_recon_id', $supplier_recon_id)->delete();
            }
            //aa($month);
            $header_data = [];
            // $reference = 'Billing: '.date('M Y', strtotime($month. '+1 month'));
            $reference = 'Billing: '.date('M Y', strtotime($month));
            //$cdr_month = date('Y-m-01', strtotime($month.' -1 month'));

            $cdr_month = date('Y-m-01', strtotime($month));
            if (date('Y-m', strtotime(date('Y-m-01'))) == date('Y-m', strtotime($cdr_month))) {
                $cdr_table = 'call_records_outbound';
                $cdr_last_table = 'call_records_outbound_lastmonth';
                $cdr_table_conn = 'pbx_cdr';
                $cdr_last_table_conn = 'pbx_cdr';
            } elseif (date('Y-m', strtotime(date('Y-m-01').' -1 month')) == date('Y-m', strtotime($cdr_month))) {
                $cdr_table = 'call_records_outbound_lastmonth';
                $cdr_last_table = 'call_records_outbound_'.strtolower(date('YM', strtotime($cdr_month.' -1 month')));
                $cdr_table_conn = 'pbx_cdr';
                $cdr_last_table_conn = 'backup_server';
            } elseif (date('Y-m-01', strtotime($cdr_month)) != date('Y-m-01')) {
                $cdr_table = 'call_records_outbound_'.strtolower(date('YM', strtotime($cdr_month)));
                $cdr_last_table = 'call_records_outbound_'.strtolower(date('YM', strtotime($cdr_month.' -1 month')));
                $cdr_table_conn = 'backup_server';
                $cdr_last_table_conn = 'backup_server';
            }

            $supplier_invoice_date = \DB::table('crm_supplier_documents')
                ->where('supplier_id', $supplier_id)
                ->where('docdate', 'LIKE', date('Y-m', strtotime($month)).'%')
                ->pluck('docdate')->first();

            if ($supplier_id == 4677) { //Afrinic
                $supplier_doc_id = \DB::table('crm_supplier_documents')
                    ->where('supplier_id', $supplier_id)
                    ->where('doctype', 'Supplier Invoice')
                    ->where('reversal_id', 0)
                    ->orderBy('id', 'desc')
                    ->pluck('id')->first();
                $supplier_doc_ids = [$supplier_doc_id];
            } else {
                $supplier_doc_ids = \DB::table('crm_supplier_documents')
                    ->where('supplier_id', $supplier_id)
                    ->where('docdate', 'LIKE', date('Y-m', strtotime($month)).'%')
                    ->where('doctype', 'Supplier Invoice')
                    ->where('reversal_id', 0)
                    ->pluck('id')->toArray();
            }

            //  if(count($supplier_doc_ids) == 0){
            //      if($supplier->id == 4829){
            //     //      aa($month);
            //     // aa('continue');
            // }
            //     //if($supplier_recon_id)
            //     //\DB::table('crm_supplier_recons')->where('id',$supplier_recon_id)->delete();
            //     continue;
            // }

            if (! $supplier_recon_id) {
                $data = [
                    'supplier_id' => $supplier->id,
                    'period' => date('Y-m', strtotime($month)),
                    'invoice_date' => $invoice_date,
                ];
                /* if($supplier->terms >= 30){
                    $data['period'] =  date('Y-m', strtotime($month.' -1 month'));
                } */
                $supplier_recon_id = \DB::table('crm_supplier_recons')->insertGetId($data);
            }

            $docids = \DB::table('crm_documents')->where('doctype', 'Tax Invoice')->where('billing_type', 'Monthly')->where('docdate', $month)->where('document_currency', 'ZAR')->pluck('id')->toArray();
            // vd($docids);
            $usd_docids = \DB::table('crm_documents')->where('doctype', 'Tax Invoice')->where('billing_type', 'Monthly')->where('docdate', $month)->where('document_currency', 'USD')->pluck('id')->toArray();

            $invoice_date = \DB::table('crm_documents')
                ->whereIn('id', $docids)
                ->pluck('docdate')->first();
            $header_data = ['invoice_date' => $invoice_date, 'supplier_invoice_date' => $supplier_invoice_date, 'difference' => 0, 'monthly_bill_cost' => 0, 'monthly_bill_total' => 0, 'supplier_invoice_total' => 0, 'cdr_total' => 0, 'supplier_invoice_qty' => 0, 'monthly_bill_qty' => 0];

            // RECONCILE MONTHLY SUBSCRIPTIONS
            $supplier_product_ids = \DB::table('crm_supplier_document_lines')
                ->join('crm_products', 'crm_products.id', '=', 'crm_supplier_document_lines.product_id')
                ->whereNotIn('crm_products.id', $exclude_product_ids)
                ->whereIn('document_id', $supplier_doc_ids)->where('cdr_destination', '')->where('product_id', '!=', 1689)->pluck('product_id')->unique()->toArray();

            // vd($supplier_product_ids);
            foreach ($supplier_product_ids as $product_id) {
                // vd($product_id);

                $supplier_invoice_total = \DB::table('crm_supplier_document_lines')
                    ->join('crm_supplier_documents', 'crm_supplier_documents.id', '=', 'crm_supplier_document_lines.document_id')
                    ->whereIn('document_id', $supplier_doc_ids)
                    ->where('product_id', $product_id)
                    ->where('cdr_destination', '')->where('product_id', '!=', 1689)
                    ->where('crm_supplier_documents.document_currency', 'ZAR')
                    ->sum(\DB::raw('price*qty'));

                $usd_supplier_invoice_total = \DB::table('crm_supplier_document_lines')
                    ->join('crm_supplier_documents', 'crm_supplier_documents.id', '=', 'crm_supplier_document_lines.document_id')
                    ->whereIn('document_id', $supplier_doc_ids)
                    ->where('product_id', $product_id)
                    ->where('cdr_destination', '')->where('product_id', '!=', 1689)
                    ->where('crm_supplier_documents.document_currency', 'USD')
                    ->sum(\DB::raw('price*qty'));
                if ($usd_supplier_invoice_total > 0) {
                    $supplier_invoice_total += convert_currency_usd_to_zar($usd_supplier_invoice_total);
                }
                if ($supplier_id == 4677) { //Afrinic
                    $supplier_invoice_total = $supplier_invoice_total / 12;
                }

                $header_data['supplier_invoice_total'] += $supplier_invoice_total;

                $supplier_qty_total = \DB::table('crm_supplier_document_lines')
                    ->whereIn('document_id', $supplier_doc_ids)
                    ->where('product_id', $product_id)
                    ->where('cdr_destination', '')->where('product_id', '!=', 1689)
                    ->sum('qty');

                if ($product_id == 1695) { //Xneelo Colocation
                    $category_ids = \DB::table('crm_product_categories')->where('department', 'Hosting')->pluck('id')->toArray();
                    $hosting_product_ids = \DB::table('crm_products')->whereIn('product_category_id', $category_ids)->pluck('id')->toArray();
                    $billed_total = \DB::table('crm_document_lines')
                        ->whereIn('document_id', $docids)
                        ->whereIn('product_id', $hosting_product_ids)
                        ->sum('qty');

                    $monthly_bill_cost = \DB::table('crm_document_lines')
                        ->whereIn('document_id', $docids)
                        ->whereIn('product_id', $hosting_product_ids)
                        ->sum('cost_total');

                    $monthly_bill_total = \DB::table('crm_document_lines')
                        ->whereIn('document_id', $docids)
                        ->whereIn('product_id', $hosting_product_ids)
                        ->sum(\DB::raw('price*qty'));

                } else {
                    $billed_total = \DB::table('crm_document_lines')
                        ->whereIn('document_id', $docids)
                        ->where('product_id', $product_id)
                        ->sum('qty');

                    $monthly_bill_cost = \DB::table('crm_document_lines')
                        ->whereIn('document_id', $docids)
                        ->where('product_id', $product_id)
                        ->sum('cost_total');

                    $monthly_bill_total = \DB::table('crm_document_lines')
                        ->whereIn('document_id', $docids)
                        ->where('product_id', $product_id)
                        ->sum(\DB::raw('price*qty'));
                }
                // vd('billed_total');
                // vd($billed_total);

                $unit_cost_price = \DB::table('crm_document_lines')
                    ->whereIn('document_id', $docids)
                    ->where('product_id', $product_id)
                    ->pluck('cost_price')->first();

                $supplier_unit_cost_price = \DB::table('crm_supplier_document_lines')
                    ->whereIn('document_id', $supplier_doc_ids)
                    ->where('product_id', $product_id)
                    ->where('cdr_destination', '')->where('product_id', '!=', 1689)
                    ->pluck('price')->first();

                if ($monthly_bill_total > 0) {
                    $monthly_bill_total = $monthly_bill_total * 1.15;
                }
                $usd_monthly_bill_total = \DB::table('crm_document_lines')
                    ->whereIn('document_id', $usd_docids)
                    ->where('product_id', $product_id)
                    ->sum(\DB::raw('price*qty'));
                if ($usd_monthly_bill_total > 0) {
                    $monthly_bill_total += convert_currency_usd_to_zar($usd_monthly_bill_total);
                }

                $header_data['monthly_bill_cost'] += $monthly_bill_cost;
                $header_data['monthly_bill_total'] += $monthly_bill_total;
                $cdr_total = 0;
                $header_data['supplier_invoice_qty'] += $supplier_qty_total;
                $header_data['monthly_bill_qty'] += $billed_total;

                $difference = $supplier_invoice_total - $monthly_bill_total * 1.15;

                if (empty($unit_cost_price)) {
                    $unit_cost_price = 0;
                }
                if (empty($supplier_unit_cost_price)) {
                    $supplier_unit_cost_price = 0;
                }

                $data = [
                    'supplier_id' => $supplier_id,
                    'period' => date('Y-m', strtotime($month)),
                ];
                /*
                if($supplier->terms >= 30){
                    $data['period'] =  date('Y-m', strtotime($month.' -1 month'));
                }
                */

                $detail_data = [
                    'supplier_recon_id' => $supplier_recon_id,
                    'product_id' => $product_id,
                    'supplier_invoice_qty' => $supplier_qty_total,
                    'supplier_invoice_total' => $supplier_invoice_total,
                    'monthly_bill_cost' => $monthly_bill_cost,
                    'monthly_bill_qty' => $billed_total,
                    'monthly_bill_total' => $monthly_bill_total,
                    'cdr_total' => $cdr_total,
                    'unit_cost_price' => $unit_cost_price,
                    'supplier_unit_cost_price' => $supplier_unit_cost_price,
                    'difference' => $difference,
                ];
                $header_data['difference'] += $difference;

                // vd($supplier_recon_id);
                \DB::table('crm_supplier_recon_details')->updateOrInsert(['supplier_recon_id' => $supplier_recon_id, 'product_id' => $product_id], $detail_data);
            }

            // RECONCILE MONTHLY CDR
            \DB::table('crm_supplier_document_lines')
                ->whereIn('document_id', $supplier_doc_ids)
                ->where('product_id', 1689)
                ->where('cdr_destination', '')->update(['cdr_destination' => 'all']);

            $supplier_cdr_destinations = \DB::table('crm_supplier_document_lines')
                ->whereIn('document_id', $supplier_doc_ids)
                ->where('product_id', 1689)
                ->pluck('cdr_destination')->filter()->unique()->toArray();

            if (count($supplier_cdr_destinations) > 0) {

                $data = [
                    'supplier_id' => $supplier_id,
                    'period' => date('Y-m', strtotime($month)),
                ];
                /*
                if($supplier->terms >= 30){
                    $data['period'] =  date('Y-m', strtotime($month.' -1 month'));
                }
                */

                foreach ($supplier_cdr_destinations as $cdr_destination) {

                    $supplier_invoice_total = \DB::table('crm_supplier_document_lines')
                        ->whereIn('document_id', $supplier_doc_ids)
                        ->where('cdr_destination', $cdr_destination)
                        ->sum(\DB::raw('price*qty'));

                    $header_data['supplier_invoice_total'] += $supplier_invoice_total;

                    $gateway_name = '';
                    if (str_contains(strtolower($supplier->company), 'vox')) {
                        $gateway_name = 'VOX';
                    }
                    if (str_contains(strtolower($supplier->company), 'bitco')) {
                        $gateway_name = 'BITCO';
                    }
                    if (str_contains(strtolower($supplier->company), 'vodacom')) {
                        $gateway_name = 'VODACOM';
                    }
                    if (str_contains(strtolower($supplier->company), 'session')) {
                        $gateway_name = 'SESSION';
                    }
                    if (str_contains(strtolower($supplier->company), 'tel africa')) {
                        $gateway_name = 'TELAFRICA';
                    }
                    if (str_contains(strtolower($supplier->company), 'bvs telecom')) {
                        $gateway_name = 'BVS';
                    }

                    $cdr_total = 0;

                    if ($gateway_name) {

                        $cdr_total_query = \DB::connection($cdr_table_conn)->table($cdr_table)->where('gateway', 'LIKE', $gateway_name.'%');

                        $gateway_name = strtoupper($gateway_name);
                        if ($gateway_name == 'BITCO') {

                            $bitco_date = date('Y-m-23', strtotime($cdr_month));

                            $bitco_date_last = date('Y-m-24', strtotime($cdr_month.'-1 month'));

                            if (\Schema::connection($cdr_table_conn)->hasTable($cdr_table)) {
                                if ($cdr_destination == 'all') {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->where('hangup_time', '<=', $bitco_date)->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'mobile all') {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->where('hangup_time', '<=', $bitco_date)->where('summary_destination', 'LIKE', '%mobile%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'fixed other fixed liquid') {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->where('hangup_time', '<=', $bitco_date)->whereIn('summary_destination', ['fixed other', 'fixed liquid'])->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } else {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->where('hangup_time', '<=', $bitco_date)->where('summary_destination', 'LIKE', '%'.$cdr_destination.'%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                }
                            }

                            if (\Schema::connection($cdr_last_table_conn)->hasTable($cdr_last_table)) {

                                if ($cdr_destination == 'all') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('hangup_time', '>=', $bitco_date_last)->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'mobile all') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('hangup_time', '>=', $bitco_date_last)->where('summary_destination', 'LIKE', '%mobile%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'fixed other fixed liquid') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('hangup_time', '>=', $bitco_date_last)->whereIn('summary_destination', ['fixed other', 'fixed liquid'])->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } else {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('hangup_time', '>=', $bitco_date_last)->where('summary_destination', 'LIKE', '%'.$cdr_destination.'%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                }
                            }

                        } elseif ($gateway_name == 'BVS') {

                            if (\Schema::connection($cdr_last_table_conn)->hasTable($cdr_last_table)) {
                                if ($cdr_destination == 'all') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'mobile all') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('summary_destination', 'LIKE', '%mobile%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'fixed other fixed liquid') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->whereIn('summary_destination', ['fixed other', 'fixed liquid'])->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } else {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('summary_destination', 'LIKE', '%'.$cdr_destination.'%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                }
                            }
                        } elseif ($gateway_name == 'VODACOM') {

                            if (\Schema::connection($cdr_last_table_conn)->hasTable($cdr_last_table)) {
                                if ($cdr_destination == 'all') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'mobile all') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('summary_destination', 'LIKE', '%mobile%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'fixed other fixed liquid') {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->whereIn('summary_destination', ['fixed other', 'fixed liquid'])->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } else {
                                    $cdr_total += \DB::connection($cdr_last_table_conn)->table($cdr_last_table)->where('summary_destination', 'LIKE', '%'.$cdr_destination.'%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                }
                            }
                        } else {
                            if (\Schema::connection($cdr_table_conn)->hasTable($cdr_table)) {
                                if ($cdr_destination == 'all') {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'mobile all') {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->where('summary_destination', 'LIKE', '%mobile%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } elseif ($cdr_destination == 'fixed other fixed liquid') {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->whereIn('summary_destination', ['fixed other', 'fixed liquid'])->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                } else {
                                    $cdr_total += \DB::connection($cdr_table_conn)->table($cdr_table)->where('summary_destination', 'LIKE', '%'.$cdr_destination.'%')->where('gateway', 'LIKE', $gateway_name.'%')->sum('gateway_cost');
                                }
                            }
                        }

                        $header_data['cdr_total'] += $cdr_total;
                        $supplier_qty_total = 0;
                        $billed_total = 0;
                        $monthly_bill_cost = 0;
                        $monthly_bill_total = 0;
                        $difference = $supplier_invoice_total - $cdr_total;
                    }
                    if (empty($unit_cost_price)) {
                        $unit_cost_price = 0;
                    }
                    if (empty($supplier_unit_cost_price)) {
                        $supplier_unit_cost_price = 0;
                    }

                    $detail_data = [
                        'supplier_recon_id' => $supplier_recon_id,
                        'product_id' => 1689,
                        'supplier_invoice_qty' => $supplier_qty_total,
                        'supplier_invoice_total' => $supplier_invoice_total,
                        'monthly_bill_cost' => $monthly_bill_cost,
                        'monthly_bill_qty' => $billed_total,
                        'monthly_bill_total' => $monthly_bill_total * 1.15,
                        'cdr_total' => $cdr_total,
                        'cdr_destination' => $cdr_destination,
                        'unit_cost_price' => $unit_cost_price,
                        'supplier_unit_cost_price' => $supplier_unit_cost_price,
                        'difference' => $difference,
                    ];
                    $header_data['difference'] += $difference;
                    \DB::table('crm_supplier_recon_details')->updateOrInsert(['supplier_recon_id' => $supplier_recon_id, 'cdr_destination' => $cdr_destination], $detail_data);
                }
            }

            // diff in percentage
            $header_data['difference'] = ($header_data['supplier_invoice_total'] > 0) ? $header_data['difference'] / $header_data['supplier_invoice_total'] * 100 : 0;
            // $header_data['difference']  = $header_data['difference'] * -1;
            \DB::table('crm_supplier_recons')->where('id', $supplier_recon_id)->update($header_data);
            \DB::table('crm_supplier_recons')->where('id', $supplier_recon_id)->update(['difference_total' => \DB::raw('monthly_bill_cost+cdr_total-supplier_invoice_total')]);

        }
    }
}

function schedule_set_supplier_balances()
{
    $db = new DBEvent;
    $supplier_ids = \DB::table('crm_suppliers')->pluck('id')->toArray();

    $db->setCreditorBalance($supplier_ids);

    \DB::table('crm_suppliers')->where('status', '!=', 'Deleted')->update(['is_deleted' => 0]);
    \DB::table('crm_suppliers')->where('status', 'Deleted')->update(['is_deleted' => 1]);
    \DB::table('crm_suppliers')->update(['payment_date' => null]);
    $suppliers = \DB::table('crm_suppliers')->where('balance', '>', 1)->where('status', '!=', 'Deleted')->get();
    foreach ($suppliers as $supplier) {
        $last_transaction_date = \DB::connection('default')->table('crm_supplier_documents')->select('docdate')->where('supplier_id', $supplier->id)->orderBy('docdate', 'desc')->pluck('docdate')->first();
        if (! $last_transaction_date) {
            $payment_date = date('Y-m-d');
        } else {
            $payment_date = $last_transaction_date;
        }
        if ($supplier->terms) {
            $payment_date = date('Y-m-d', strtotime($payment_date.' + '.$supplier->terms.' days'));
        }
        \DB::table('crm_suppliers')->where('id', $supplier->id)->update(['payment_date' => $payment_date]);
    }
}

function schedule_supplier_set_reconciled()
{
    supplier_set_reconciled();
}

function button_supplier_set_reconciled()
{
    supplier_set_reconciled();

    return json_alert('Done');
}

function supplier_set_reconciled()
{
    \DB::table('crm_suppliers')->update(['billing_reconciled' => 0]);

    $period = date('Y-m-01', strtotime('-1 month'));

    $suppliers = \DB::table('crm_suppliers')->where('reconcile_services', 1)->where('status', 'Deleted')->get();
    foreach ($suppliers as $supplier) {

        $last_recon = \DB::table('crm_supplier_recons')->where('supplier_id', $supplier->id)->orderBy('id', 'desc')->first();
        if (date('Y-m-01', strtotime($last_recon->period)) < $period) {
            continue;
        }

        if ($supplier->cdr_supplier) {
            $min = $last_recon->supplier_invoice_total - 2500;
            $max = $last_recon->supplier_invoice_total + 2500;
            if ($min <= $last_recon->cdr_total && $last_recon->cdr_total <= $max) {
                \DB::table('crm_suppliers')->where('id', $supplier->id)->update(['billing_reconciled' => 1, 'last_reconcile_date' => date('Y-m-d')]);
            }
        } else {
            if ($last_recon->monthly_bill_qty == $last_recon->supplier_invoice_qty) {
                \DB::table('crm_suppliers')->where('id', $supplier->id)->update(['billing_reconciled' => 1, 'last_reconcile_date' => date('Y-m-d')]);
            }
        }
    }
}

function button_supplier_monthly_recon()
{
    schedule_supplier_recon_cdr();

    return json_alert('Done');
}

function remove_inventory_adjustments($month)
{
    $erp = new DBEvent;
    $erp->setTable('acc_inventory');
    $suppliers = \DB::table('crm_suppliers')->get();
    $service_product_ids = \DB::table('crm_products')->where('type', 'Service')->where('code', '!=', 'various')->pluck('id')->toArray();

    foreach ($suppliers as $supplier) {
        $supplier_id = $supplier->id;
        $supplier_doc_ids = \DB::table('crm_supplier_documents')
            ->where('supplier_id', $supplier_id)
            ->where('docdate', 'LIKE', date('Y-m', strtotime($month)).'%')
            ->pluck('id')->toArray();
        $supplier_product_ids = \DB::table('crm_supplier_document_lines')
            ->whereIn('document_id', $supplier_doc_ids)
            ->whereIn('product_id', $service_product_ids)
            ->pluck('product_id')->filter()->unique()->toArray();

        \DB::table('acc_inventory')
            ->where('cost_change', '>', 0)
            ->where('qty_change', 0)
            ->where('docdate', '>=', $month)
            ->whereIn('product_id', $supplier_product_ids)->delete();
        $erp->setStockBalance($supplier_product_ids);
    }

    $reference = 'Billing: '.date('M Y', strtotime($month.'+1 month'));

    $docids = \DB::table('crm_documents')
        ->where('doctype', 'Tax Invoice')
        ->where('billing_type', 'Monthly')
        ->where('reference', $reference)
        ->pluck('id')->toArray();
    foreach ($docids as $docid) {
        set_document_lines_gp($docid);
    }
}

function button_suppliers_write_off($request)
{
    write_off_supplier_account($request->id);

    return json_alert('Supplier Written Off.');
}

function write_off_supplier_account_all()
{
    $accounts = \DB::table('crm_suppliers')
        ->where('status', 'Deleted')
        ->get();
    foreach ($accounts as $a) {
        write_off_supplier_account($a->id);
    }
}

function write_off_supplier_account($supplier_id)
{
    $erp = new DBEvent;
    $erp->setCreditorBalance($supplier_id);
    $supplier = dbgetsupplier($supplier_id);
    $supplier->balance = currency($supplier->balance);

    if ($supplier->balance != 0) {
        $trx_data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'General Journal',
            'name' => 'Written Off',
        ];

        $transaction_id = \DB::table('acc_general_journal_transactions')->insertGetId($trx_data);

        $amount = $supplier->balance;
        $data = [
            'transaction_id' => $transaction_id,
            'supplier_id' => $supplier_id,
            'debit_amount' => $amount,
            'reference' => 'Written Off',
            'ledger_account_id' => 51,
        ];
        $is_credit = false;
        if ($amount < 0) {
            $is_credit = true;
            $data['credit_amount'] = abs($amount);
            $data['debit_amount'] = 0;
        }

        $db = new DBEvent;
        $result = $db->setTable('acc_general_journals')->save($data);

        if ($is_credit) {
            $data['debit_amount'] = abs($amount);
            $data['credit_amount'] = 0;
        } else {
            $data['credit_amount'] = $amount;
            $data['debit_amount'] = 0;
        }

        $data['ledger_account_id'] = 6;
        $result = $db->setTable('acc_general_journals')->save($data);

        $erp->setCreditorBalance($supplier_id);
    }
}

function button_suppliers_reconcile($request)
{
    return view('__app.button_views.supplier_recon', ['id' => $request->id]);
}

function button_suppliers_statement($request)
{
    return redirect()->to('/supplier_statement_pdf/'.$request->id);
}

function button_suppliers_full_statement($request)
{
    return redirect()->to('/supplier_full_statement/'.$request->id);
}

function button_suppliers_enable_supplier($request)
{
    $id = $request->id;
    if (check_access('1,31') || is_parent_of($id)) {
        $enabled = \DB::table('crm_suppliers')->where(['id' => $id, 'status' => 'Enabled'])->count();
        if ($enabled) {
            return json_alert('Account already enabled.', 'error');
        } else {
            \DB::table('crm_suppliers')->where('id', $id)->update(['status' => 'Enabled']);

            return json_alert('Account enabled.');
        }
    }
}

function button_suppliers_disable_supplier($request)
{
    $id = $request->id;
    $deleted = \DB::table('crm_suppliers')->where(['id' => $id, 'status' => 'Deleted'])->count();
    if ($deleted) {
        return json_alert('Account is deleted. Cannot be Disabled.', 'error');
    } else {
        if (check_access('1,31') || is_parent_of($id)) {
            $disabled = \DB::table('crm_suppliers')->where(['id' => $id, 'status' => 'Disabled'])->count();
            if ($disabled) {
                return json_alert('Account already disabled.', 'error');
            } else {
                \DB::table('crm_suppliers')->where('id', $id)->update(['status' => 'Disabled']);

                return json_alert('Account disabled.');
            }
        }
    }
}

function set_suppliers_balance()
{
    $supplier_ids = \DB::table('crm_suppliers')->pluck('id')->toArray();
    foreach ($supplier_ids as $supplier_id) {
        $data['balance'] = get_creditor_balance($supplier_id);
        \DB::table('crm_suppliers')->where('id', $supplier_id)->update($data);
    }
}

function schedule_suppliers_auto_reconcile()
{
    /*
    $docids = \DB::table('crm_supplier_documents')->where('reference','Auto Reconcile')->pluck('id')->toArray();
    \DB::table('acc_ledgers')->whereIn('docid',$docids)->where('doctype','Supplier Invoice')->delete();
    \DB::table('crm_supplier_document_lines')->whereIn('document_id',$docids)->delete();
    \DB::table('crm_supplier_documents')->whereIn('id',$docids)->delete();
    */

    $db = new DBEvent;

    $suppliers = \DB::table('crm_suppliers')
        ->where('reconcile_method', 'Auto')
        ->get();
    $supplier_ids = $suppliers->pluck('id')->toArray();

    $db->setCreditorBalance($supplier_ids);

    $suppliers = \DB::table('crm_suppliers')
        ->where('reconcile_method', 'Auto')
        ->get();

    foreach ($suppliers as $supplier) {
        $supplier_id = $supplier->id;
        $created = false;
        $supplier = \DB::table('crm_suppliers')->where('id', $supplier_id)->get()->first();
        $balance = $supplier->balance;

        if ($balance > 0) {

            $created = create_supplier_cash_payment($supplier_id, $balance, 'Auto Reconcile');
        }

        if ($balance < 0) {
            $created = create_supplier_invoice($supplier_id, $balance * -1, 'Auto Reconcile');
        }

        if ($created) {
            \DB::table('crm_suppliers')->where('id', $supplier_id)->update(['reconcile_date' => date('Y-m-d')]);
        }
    }

    $db->setCreditorBalance($supplier_ids);

}

function button_suppliers_convert_supplier_to_supplier($request) {}
