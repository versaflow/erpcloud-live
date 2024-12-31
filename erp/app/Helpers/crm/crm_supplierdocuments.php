<?php

function schedule_import_movie_magic_invoices()
{
    if (is_main_instance()) {
        $account_id = 11068; //cloud telecoms account
        // $documents = \DB::connection('eldooffice')->table('crm_documents')->where('docdate','like',date('Y-m').'%')->where('account_id',8)->get();
        $documents = \DB::connection('moviemagic')->table('crm_documents')->where('docdate', '>=', date('Y-m-01', strtotime('-1 month')))->where('account_id', $account_id)->get();
        foreach ($documents as $doc) {
            $ref_exists = \DB::table('crm_supplier_documents')->where('supplier_id', 4920)->where('reference', $doc->reference)->count();
            if (! $ref_exists) {
                create_supplier_invoice(4920, $doc->total, $doc->reference, $doc->docdate);
            }
        }
    }
}

function schedule_import_eldo_office_invoices()
{

    $account_id = 8; //cloud telecoms account

    if (is_main_instance()) {
        // $documents = \DB::connection('eldooffice')->table('crm_documents')->where('docdate','like',date('Y-m').'%')->where('account_id',8)->get();
        $documents = \DB::connection('eldooffice')->table('crm_documents')->where('docdate', '>=', date('Y-m-01', strtotime('-1 month')))->where('account_id', $account_id)->get();
        foreach ($documents as $doc) {
            $ref_exists = \DB::table('crm_supplier_documents')->where('supplier_id', 4880)->where('reference', $doc->reference)->count();
            if (! $ref_exists) {
                create_supplier_invoice(4880, $doc->total, $doc->reference, $doc->docdate);
            }
        }
    }
}

function schedule_autopay_cloudtelecoms_accounts()
{
    if (is_main_instance()) {
        return false;
    }

    if (session('instance')->directory == 'eldooffice') {
        $account_id = 8; //cloud telecoms account
        $debtor_date = date('Y-m-01', strtotime('-1 month'));
        $debtor_balance = get_debtor_balance_upto_date($account_id, 'eldooffice', $debtor_date);
        if ($debtor_balance > 0) {
            create_cash_transaction($account_id, $debtor_balance, 'Paid from Cloud Telecoms');
        }
    }

    if (session('instance')->directory == 'moviemagic') {
        $account_id = 11068; //cloud telecoms account
        $debtor_date = date('Y-m-01', strtotime('-1 month'));
        $debtor_balance = get_debtor_balance_upto_date($account_id, 'eldooffice', $debtor_date);
        if ($debtor_balance > 0) {
            create_cash_transaction($account_id, $debtor_balance, 'Paid from Cloud Telecoms');
        }
    }
}

function aftercommit_set_supplier_products($request = false)
{
    \DB::table('crm_products')->update(['is_supplier_product' => 0]);
    $product_ids = \DB::table('crm_supplier_document_lines')
        ->select('product_id')
        ->join('crm_supplier_documents', 'crm_supplier_documents.id', '=', 'crm_supplier_document_lines.document_id')
        ->whereIn('crm_supplier_documents.doctype', ['Supplier Invoice', 'Supplier Debit Note'])
        ->where('product_id', '!=', 147)
        ->groupBy('product_id')->pluck('product_id')->unique()->toArray();
    \DB::table('crm_products')->whereIn('id', $product_ids)->update(['is_supplier_product' => 1]);

}

function aftersave_import_shipments_calculate_totals($request)
{

    import_shipment_update_totals($request->id);

    $import_shipment = \DB::table('crm_import_shipments')->where('id', $request->id)->get()->first();
    if ($import_shipment->processed) {
        $db = new DBEvent;
        $db->setTable('crm_supplier_import_documents');
        \DB::table('crm_supplier_import_documents')->where('import_shipment_id', $request->id)->update(['doctype' => 'Supplier Import Invoice']);
        $documents = \DB::table('crm_supplier_import_documents')->where('import_shipment_id', $request->id)->get();
        foreach ($documents as $document) {
            $document_lines = \DB::table('crm_supplier_import_document_lines')->where('document_id', $document->id)->get();
            $transaction_request = (array) $document;
            $transaction_request['tax'] = $transaction_request['import_tax'];
            unset($transaction_request['import_tax']);

            foreach ($document_lines as $index => $line) {
                $transaction_request['qty'][$index] = $line->qty;
                $transaction_request['price'][$index] = $line->price;
                $transaction_request['full_price'][$index] = $line->full_price;
                $transaction_request['product_id'][$index] = $line->product_id;
                $transaction_request['description'][$index] = $line->description;
                $transaction_request['shipment_share'][$index] = $line->shipment_share;
            }

            $result = $db->setProperties(['validate_document' => 1])->save($transaction_request);
        }
    }
}

function import_shipment_update_totals($import_shipment_id)
{
    /*
        Line Ratio = Line total / Total Amount
        VAT = Vat Cost * Line Ratio
        Shipping = Shipping Cost * Line Ratio
    */
    $import_shipment = \DB::table('crm_import_shipments')->where('id', $import_shipment_id)->get()->first();
    if (! $import_shipment->processed) {
        $exchange_rate = get_exchange_rate($import_shipment->shipment_date, 'USD', 'ZAR');
        \DB::table('crm_supplier_import_documents')->where('import_shipment_id', $import_shipment_id)->update(['exchange_rate' => $exchange_rate]);
        $shipping_cost_usd = $import_shipment->shipping_cost / $exchange_rate;
        $vat_cost_usd = $import_shipment->vat_cost / $exchange_rate;
        $usd_data = [
            'shipping_cost_usd' => $shipping_cost_usd,
            'vat_cost_usd' => $vat_cost_usd,
            'exchange_rate' => $exchange_rate,
        ];
        \DB::table('crm_import_shipments')->where('id', $import_shipment_id)->update($usd_data);
        $docs = \DB::table('crm_supplier_import_documents')->where('import_shipment_id', $import_shipment_id)->where('is_deleted', 0)->get();
        if (! empty($import_shipment) && count($docs) > 0) {
            $doc_ids = $docs->pluck('id')->toArray();
            $import_sub_total_usd = \DB::table('crm_supplier_import_document_lines')
                ->select(\DB::raw('SUM(qty*price) as sub_total'))
                ->whereIn('document_id', $doc_ids)
                ->pluck('sub_total')
                ->first();

            $import_sub_total = $import_sub_total_usd * $exchange_rate;

            $import_grand_total = $import_sub_total + $import_shipment->shipping_cost + $import_shipment->vat_cost;
            $import_grand_total_usd = $import_sub_total_usd + $shipping_cost_usd + $vat_cost_usd;
            \DB::table('crm_import_shipments')
                ->where('id', $import_shipment_id)
                ->update(['total_amount' => $import_grand_total, 'total_amount_usd' => $import_grand_total_usd]);

            $grand_total = 0;
            $grand_total_usd = 0;

            foreach ($docs as $doc) {
                $doc_sub_total = \DB::table('crm_supplier_import_document_lines')
                    ->select(\DB::raw('SUM(qty*price) as sub_total'))
                    ->where('document_id', $doc->id)
                    ->pluck('sub_total')
                    ->first();

                $shipment_share_ratio = $doc_sub_total / $import_sub_total_usd;
                $shipping = $import_shipment->shipping_cost * $shipment_share_ratio;
                $shipping_usd = $shipping_cost_usd * $shipment_share_ratio;
                $import_tax = $import_shipment->vat_cost * $shipment_share_ratio;
                $import_tax_usd = $vat_cost_usd * $shipment_share_ratio;

                $total_rands = ($doc_sub_total * $exchange_rate) + $import_tax + $shipping;

                $total = $doc_sub_total + $shipping_usd + $import_tax_usd;
                $grand_total_usd += $total;
                $grand_total += $total_rands;
                $data = [
                    'shipping' => $shipping,
                    'shipping_usd' => $shipping_usd,
                    'import_tax' => $import_tax,
                    'import_tax_usd' => $import_tax_usd,
                    'shipment_share_ratio' => currency($shipment_share_ratio * 100),
                    'total' => currency($total),
                    'total_rands' => currency($total_rands),
                ];

                \DB::table('crm_supplier_import_documents')->where('id', $doc->id)->update($data);

                $doc_lines = \DB::table('crm_supplier_import_document_lines')
                    ->where('document_id', $doc->id)
                    ->get();
                foreach ($doc_lines as $doc_line) {
                    $line_total = $doc_line->qty * $doc_line->price;
                    $shipment_share = currency(($line_total / $doc_sub_total) * 100);
                    \DB::table('crm_supplier_import_document_lines')->where('id', $doc_line->id)->update(['shipment_share' => $shipment_share]);
                }
            }

            \DB::table('crm_import_shipments')
                ->where('id', $import_shipment_id)
                ->update(['total_amount' => $grand_total, 'total_amount_usd' => $grand_total_usd]);
        }
    }
}

function afterdelete_supplier_document_delete_stock_adjustment($request)
{
    \DB::table('acc_inventory')->where('supplier_document_id', $request->id)->delete();
}

function aftersave_supplier_import_documents_create_invoice($request)
{

    $doc = \DB::table('crm_supplier_import_documents')->where('id', $request->id)->get()->first();
    $doc_sub_total = \DB::table('crm_supplier_import_document_lines')
        ->select(\DB::raw('SUM(qty*price) as sub_total'))
        ->where('document_id', $doc->id)
        ->pluck('sub_total')
        ->first();

    $doc_lines = \DB::table('crm_supplier_import_document_lines')
        ->where('document_id', $doc->id)
        ->get();
    foreach ($doc_lines as $doc_line) {
        $line_total = $doc_line->qty * $doc_line->price;
        $shipment_share = currency(($line_total / $doc_sub_total) * 100);
        \DB::table('crm_supplier_import_document_lines')->where('id', $doc_line->id)->update(['shipment_share' => $shipment_share]);
    }
    try {
        $cols = get_columns_from_schema('crm_supplier_documents');
        $process = true;
        if ($doc->is_deleted) {
            $process = false;
        } elseif (empty($doc->import_shipment_id)) {
            import_shipment_update_totals($doc->import_shipment_id);
            $process = false;
        }
        if ($doc->supplier_invoice_id) {
            \DB::table('crm_supplier_document_lines')->where('document_id', $doc->supplier_invoice_id)->delete();
            \DB::table('crm_supplier_documents')->where('id', $doc->supplier_invoice_id)->delete();
            \DB::table('acc_ledgers')->where('docid', $doc->supplier_invoice_id)->where('doctype', 'Supplier Invoice')->delete();
            \DB::table('crm_supplier_import_documents')->where('id', $request->id)->update(['supplier_invoice_id' => 0]);
        }
        if ($doc->shipping_supplier_invoice_id) {
            \DB::table('crm_supplier_document_lines')->where('document_id', $doc->shipping_supplier_invoice_id)->delete();
            \DB::table('crm_supplier_documents')->where('id', $doc->shipping_supplier_invoice_id)->delete();
            \DB::table('acc_ledgers')->where('docid', $doc->shipping_supplier_invoice_id)->where('doctype', 'Supplier Invoice')->delete();
            \DB::table('crm_supplier_import_documents')->where('id', $request->id)->update(['shipping_supplier_invoice_id' => 0]);
        }

        if ($process) {

            /// STOCK INVOICE
            $shipping_total = 0;
            $doc_lines = \DB::table('crm_supplier_import_document_lines')->where('document_id', $request->id)->get();
            $data = (array) $doc;

            unset($data['shipping_supplier_id']);
            if ($doc->supplier_invoice_id) {
                $data['id'] = $doc->supplier_invoice_id;
            }

            $data['total'] = $doc->total_rands - $doc->shipping - $doc->import_tax;

            $data['doctype'] = 'Supplier Invoice';
            foreach ($data as $k => $v) {
                if (! in_array($k, $cols)) {
                    unset($data[$k]);
                }
            }
            unset($data['id']);
            unset($data['exchange_rate']);

            $supplier_invoice_id = \DB::table('crm_supplier_documents')->insertGetId($data);

            \DB::table('crm_supplier_import_documents')->where('id', $doc->id)->update(['supplier_invoice_id' => $supplier_invoice_id]);

            foreach ($doc_lines as $dl) {
                $dl_data = (array) $dl;
                unset($dl_data['id']);
                $dl_data['document_id'] = $supplier_invoice_id;
                if ($dl_data['price'] != 0) {
                    $line_total = ($dl->qty * $dl->price);
                    $dl_data['price'] = $dl_data['price'] * $doc->exchange_rate;

                    $shipping_cost = $doc->shipping * ($dl_data['shipment_share'] / 100);
                    $shipping_per_unit = $shipping_cost / $dl->qty;
                    $shipping_total =

                    $dl_data['description'] = 'Price: '.currency($dl_data['price']).' | Shipping: '.currency($shipping_per_unit).' | Total:'.currency($dl_data['price'] + $shipping_per_unit).' | Shipping Line Total: '.currency($shipping_cost);
                    $dl_data['shipping_price'] = $shipping_per_unit;

                }
                unset($dl_data['shipment_share']);

                \DB::table('crm_supplier_document_lines')->insert($dl_data);
            }
            $document = \DB::table('crm_supplier_documents')->where('id', $supplier_invoice_id)->get()->first();
            $document_lines = \DB::table('crm_supplier_document_lines')->where('document_id', $supplier_invoice_id)->get();
            $transaction_request = (array) $document;
            foreach ($document_lines as $index => $line) {
                $transaction_request['qty'][$index] = $line->qty;
                $transaction_request['price'][$index] = $line->price;
                $transaction_request['shipping_price'][$index] = $line->shipping_price;
                $transaction_request['full_price'][$index] = $line->full_price;
                $transaction_request['product_id'][$index] = $line->product_id;
                $transaction_request['description'][$index] = $line->description;
            }
            $db = new DBEvent;
            $result = $db->setTable('crm_supplier_documents')->setProperties(['validate_document' => 1])->save($transaction_request);

            if (empty($result['id'])) {
                \DB::table('crm_approvals')->where('module_id', 1861)->where('row_id', $doc->import_shipment_id)->update(['processed' => 0, 'processed_at' => null, 'processed_by' => 0]);
                \DB::table('crm_import_shipments')->where('id', $doc->import_shipment_id)->update(['processed' => 0]);
            }

            /// SHIPPING INVOICE
            $doc_lines = \DB::table('crm_supplier_import_document_lines')->where('document_id', $request->id)->get();
            $data = (array) $doc;
            $data['supplier_id'] = $doc->shipping_supplier_id;
            $data['shipping_invoice'] = 1;
            unset($data['shipping_supplier_id']);
            if ($doc->shipping_supplier_invoice_id) {
                $data['id'] = $doc->shipping_supplier_invoice_id;
            }

            $data['total'] = $doc->shipping + $doc->import_tax;
            $data['tax'] = $doc->import_tax;

            $data['doctype'] = 'Supplier Invoice';
            foreach ($data as $k => $v) {
                if (! in_array($k, $cols)) {
                    unset($data[$k]);
                }
            }
            unset($data['id']);
            unset($data['exchange_rate']);

            $shipping_supplier_invoice_id = \DB::table('crm_supplier_documents')->insertGetId($data);

            \DB::table('crm_supplier_import_documents')->where('id', $doc->id)->update(['shipping_supplier_invoice_id' => $shipping_supplier_invoice_id]);

            foreach ($doc_lines as $dl) {
                $dl_data = (array) $dl;
                unset($dl_data['id']);
                $dl_data['document_id'] = $shipping_supplier_invoice_id;
                if ($dl_data['price'] != 0) {
                    $line_total = ($dl->qty * $dl->price);
                    $dl_data['price'] = $dl_data['price'] * $doc->exchange_rate;

                    $shipping_cost = $doc->shipping * ($dl_data['shipment_share'] / 100);
                    $shipping_per_unit = $shipping_cost / $dl->qty;

                    $dl_data['description'] = 'Price: '.currency($dl_data['price']).' | Shipping: '.currency($shipping_per_unit).' | Total:'.currency($dl_data['price'] + $shipping_per_unit).' | Shipping Line Total: '.currency($shipping_cost);
                    $dl_data['price'] = $shipping_per_unit;

                }
                unset($dl_data['shipment_share']);

                \DB::table('crm_supplier_document_lines')->insert($dl_data);
            }
            $document = \DB::table('crm_supplier_documents')->where('id', $shipping_supplier_invoice_id)->get()->first();
            $document_lines = \DB::table('crm_supplier_document_lines')->where('document_id', $shipping_supplier_invoice_id)->get();
            $transaction_request = (array) $document;
            foreach ($document_lines as $index => $line) {
                $transaction_request['qty'][$index] = $line->qty;
                $transaction_request['price'][$index] = $line->price;
                $transaction_request['shipping_price'][$index] = $line->shipping_price;
                $transaction_request['full_price'][$index] = $line->full_price;
                $transaction_request['product_id'][$index] = $line->product_id;
                $transaction_request['description'][$index] = $line->description;
            }
            $db = new DBEvent;
            $result = $db->setTable('crm_supplier_documents')->setProperties(['validate_document' => 1])->save($transaction_request);

            if (empty($result['id'])) {
                \DB::table('crm_approvals')->where('module_id', 1861)->where('row_id', $doc->import_shipment_id)->update(['processed' => 0, 'processed_at' => null, 'processed_by' => 0]);
                \DB::table('crm_import_shipments')->where('id', $doc->import_shipment_id)->update(['processed' => 0]);
            }

        }

    } catch (\Throwable $ex) {
        exception_log($ex);
        \DB::table('crm_approvals')->where('module_id', 1861)->where('row_id', $doc->import_shipment_id)->update(['processed' => 0, 'processed_at' => null, 'processed_by' => 0]);
        \DB::table('crm_import_shipments')->where('id', $doc->import_shipment_id)->update(['processed' => 0]);
    }
}

function button_general_journal_upload_invoice($request)
{
    return view('__app.button_views.journal_invoice', ['id' => $request->id]);
}

function button_documents_upload_invoice($request)
{
    return view('__app.button_views.documents_upload_invoice', ['id' => $request->id]);
}

function button_documents_manager_delete($request)
{
    $id = $request->id;
    \DB::table('crm_supplier_document_lines')->where('document_id', $id)->delete();
    \DB::table('crm_supplier_documents')->where('id', $id)->delete();
    \DB::table('crm_supplier_documents')->where('reversal_id', $id)->update(['reversal_id' => 0]);
    \DB::table('acc_ledgers')->where('docid', $id)->where('doctype', 'LIKE', '%Supplier%')->delete();

    return json_alert('Document Deleted.');
}

function create_supplier_debit_note($supplier_id, $total, $date, $reference = 'Debit Note')
{
    $db = new DBEvent;

    $data = [
        'docdate' => date('Y-m-d', strtotime($date)),
        'doctype' => 'Supplier Debit Note',
        'completed' => 1,
        'account_id' => $supplier_id,
        'total' => abs($total),
        'reference' => $reference,
        'qty' => [1],
        'price' => [abs($total)],
        'full_price' => [abs($total)],
        'product_id' => [147],
    ];

    $result = $db->setProperties(['validate_document' => 1])->setTable('crm_supplier_documents')->save($data);

    return $result;
}

function create_supplier_invoice($supplier_id, $amount, $reference, $docdate = false)
{
    $supplier_currency = \DB::table('crm_suppliers')->where('id', $supplier_id)->pluck('currency')->first();
    if (! $supplier_currency) {
        $supplier_currency = 'ZAR';
    }
    if ($amount != 0) {
        if (! $docdate) {
            $docdate = date('Y-m-d');
        }
        $db = new DBEvent;
        $data = [
            'docdate' => date('Y-m-d', strtotime($docdate)),
            'doctype' => 'Supplier Invoice',
            'document_currency' => $supplier_currency,
            'completed' => 1,
            'supplier_id' => $supplier_id,
            'total' => $amount,
            'tax' => $amount - ($amount / 1.15),
            'reference' => $reference,
            'qty' => [1],
            'price' => [$amount / 1.15],
            'full_price' => [$amount / 1.15],
            'product_id' => [147],
        ];

        if ($supplier_currency == 'USD') {
            $data['price'] = [$amount];
            $data['full_price'] = [$amount];
            $data['tax'] = 0;
        }

        $result = $db->setProperties(['validate_document' => 1])->setTable('crm_supplier_documents')->save($data);

        if (! is_array($result) || empty($result['id'])) {
            return false;
        } else {
            return true;
        }
    }

    return false;
}

function create_supplier_cash_payment($supplier_id, $amount, $reference, $docdate = false)
{
    $supplier_currency = \DB::table('crm_suppliers')->where('id', $supplier_id)->pluck('currency')->first();
    if (! $supplier_currency) {
        $supplier_currency = 'ZAR';
    }
    if ($amount != 0) {
        if (! $docdate) {
            $docdate = date('Y-m-d');
        }
        $db = new DBEvent;
        $data = [
            'docdate' => date('Y-m-d', strtotime($docdate)),
            'doctype' => 'Cashbook Supplier Payment',
            'supplier_id' => $supplier_id,
            'ledger_account_id' => 0,
            'account_id' => 0,
            'total' => $amount,
            'reference' => $reference,
            'cashbook_id' => 8,
            'document_currency' => $supplier_currency,
        ];

        if ($supplier_currency != 'ZAR') {
            $data['cashbook_id'] = 14;
        }

        $result = $db->setTable('acc_cashbook_transactions')->save($data);

        if (! is_array($result) || empty($result['id'])) {
            return false;
        }
    }

    return true;
}
