<?php

class EldoBilling
{
    ///////SCHEDULE

    public function monthly_billing($docdate = false, $date_activated_bill = false)
    {
        // exit;

        $rentals = \DB::table('crm_rental_leases')->where('status', 'Cancelled')->get();
        $rental_module_id = \DB::table('erp_cruds')->where('db_table', 'crm_rental_leases')->pluck('id')->first();
        foreach ($rentals as $rental) {
            \DB::table('crm_rental_leases')->where('id', $rental->id)->update(['status' => 'Deleted']);
            module_log($rental_module_id, $rental->id, 'deleted', 'Status changed Cancelled to Deleted');
        }
        $this->updateSubscriptions();
        $erp = new \DBEvent;
        $account_ids = [];
        set_time_limit(0);

        if (! $docdate) {
            $docdate = date('Y-09-01');
        }

        $billing = [
            'billing_date' => $docdate,
        ];

        $billing['name'] = $docdate.' Monthly';
        $e = \DB::table('acc_billing')->where($billing)->count();
        if (! $e) {
            \DB::table('acc_billing')->insert($billing);
        }

        $this->saveSubscriptionTable($docdate);

        // delete orders

        // $doc_ids = \DB::table('crm_documents')->where('docdate', $docdate)->whereIn('doctype', ['Order','Quotation'])->where('reversal_id', 0)->where('billing_type', 'Monthly')->pluck('id')->toArray();

        // foreach ($doc_ids as $doc_id) {
        //     \DB::table('crm_document_lines')->where('document_id', $doc_id)->delete();
        //     \DB::table('crm_documents')->where('id', $doc_id)->delete();
        //     \DB::table('acc_ledgers')->where('doctype', 'Tax Invoice')->where('docid', $doc_id)->delete();
        // }

        // credit tax invoices
        // $doc_ids = \DB::table('crm_documents')->where('docdate', $docdate)->where('doctype', 'Tax Invoice')->where('reversal_id', 0)->where('billing_type', 'Monthly')->pluck('id')->toArray();
        // foreach ($doc_ids as $doc_id) {
        //     create_credit_note_from_invoice($doc_id);
        // }

        $admin = dbgetrow('crm_accounts', 'id', 1);
        $service_balance = \DB::table('sub_service_balances')->where('is_deleted', 0)->where('id', '130')->orderBy('id', 'desc')->get()->first();
        $sql = 'SELECT * from crm_accounts where id = 1';
        $partners = DB::select($sql);
        $s_total = 0;
        $r_total = 0;
        foreach ($partners as $reseller) {
            //reseller
            $reseller = dbgetaccount($reseller->id);

            unset($partner_invoice);
            unset($partner_lines);
            $partner_invoice['discount'] = 0;
            $partner_invoice['total'] = 0;
            $partner_invoice['billing_type'] = 'Monthly';

            $partner_invoice['account_id'] = $reseller->id;
            $partner_invoice['docdate'] = $docdate;
            $partner_invoice['reference'] = 'Services: '.$docdate;

            $partner_invoice['bill'] = 1;
            $partner_invoice['id'] = dbinsert('crm_documents', $partner_invoice);

            $sql = "SELECT c.*,r.*, c.id as id, r.id as rental_id from crm_accounts as c 
        JOIN crm_rental_leases as r on c.id=r.account_id
        WHERE c.status != 'Deleted'  and r.status != 'Deleted' and c.partner_id = ".$reseller->id.' 
        order by r.rental_space_id desc';

            /*
            $sql = "SELECT c.*,r.*, c.id as id, r.id as rental_id from crm_accounts as c
            JOIN crm_rental_leases as r on c.id=r.account_id
            WHERE c.status != 'Deleted' and c.partner_id = ".$reseller->id.'  and  c.id=37
            group by c.id
            order by r.office_number desc';
            */
            $customers = DB::select($sql);

            $x = 0;
            unset($customer_invoices);
            $rent_invoices = 0;
            $rent_vat_invoices = 0;
            $sanitation_invoices = 0;
            $internet_invoices = 0;
            $water_invoices = 0;
            $armed_response_invoices = 0;
            $inv_totals = 0;
            foreach ($customers as $customer) {
                $account_ids[] = $customer->id;
                //  $e = \DB::table('crm_documents')->where('docdate', $docdate)->where('doctype', 'Tax Invoice')->where('account_id', $customer->id)->where('billing_type', 'Monthly')->count();
                //   if($e){
                //       continue;
                //   }
                $summary = $this->monthly_billing_customers($reseller, $customer, $docdate);
                $inv_totals += $summary['total'];
                $rent_invoices += $summary['rent'];
                $rent_vat_invoices += $summary['rent'] * 1.15;
                $sanitation_invoices += $summary['services'];
                $internet_invoices += $summary['internet'];
                $water_invoices += $summary['water'];
                $armed_response_invoices += $summary['armed_response'];

                /*
                */
            }

            $doc_ids = \DB::table('crm_documents')->where('docdate', $docdate)->where('billing_type', 'Monthly')->pluck('id')->toArray();
            $w_total = \DB::table('crm_document_lines')->whereIn('document_id', $doc_ids)->where('product_id', 14)->sum('price');
            $s_total = \DB::table('crm_document_lines')->whereIn('document_id', $doc_ids)->where('product_id', 2)->sum('price');

            $sanitation_bill = $service_balance->waste_management_sanitation;
            $water_bill = $service_balance->water_bill;
            $armed_response_bill = $service_balance->armed_response;

            $disabled_tenant_count = 0;
            $disabled_tenant_ids = [];
            $disabled_account_ids = \DB::table('crm_accounts')->where('status', 'Disabled')->pluck('id')->toArray();
            if (! empty($disabled_account_ids) && is_array($disabled_account_ids) && count($disabled_account_ids) > 0) {
                $disabled_tenant_count = \DB::table('crm_rental_leases')->whereIn('account_id', $disabled_account_ids)->count();
                $disabled_tenant_ids = \DB::table('crm_rental_leases')->whereIn('account_id', $disabled_account_ids)->pluck('id')->toArray();
            }
            if ($disabled_tenant_count > 0) {
                $total_taps = \DB::table('crm_rental_leases')->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')->where('crm_rental_leases.account_id', '>', 0)->where('account_id', '>', 0)->whereNotIn('id', $disabled_tenant_ids)->sum('taps');
                $total_fixed_tenants = \DB::table('crm_rental_leases')->where('account_id', '>', 0)->whereNotIn('id', $disabled_tenant_ids)->count();
            } else {
                $total_taps = \DB::table('crm_rental_leases')->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')->where('crm_rental_leases.account_id', '>', 0)->sum('taps');
                $total_fixed_tenants = \DB::table('crm_rental_leases')->where('account_id', '>', 0)->count();
            }

            if ($partner_invoice['total'] > 0) {
                if ($admin->vat_enabled == 1) {
                    $partner_invoice['tax'] = $partner_invoice['total'] * 0.15;
                    $partner_invoice['total'] = $partner_invoice['total'] + $partner_invoice['tax'];
                } else {
                    $partner_invoice['tax'] = 0;
                }
                dbset('crm_documents', 'id', $partner_invoice['id'], ['tax' => currency($partner_invoice['tax']), 'total' => currency($partner_invoice['total'])]);
            } else {
                \DB::table('crm_documents')->where('id', $partner_invoice['id'])->delete();
                \DB::table('crm_document_lines')->where('document_id', $partner_invoice['id'])->delete();
            }
        }

        $admin_invoices = \DB::table('crm_documents')->where('account_id', 1)->get();
        foreach ($admin_invoices as $admin_invoice) {
            \DB::table('crm_document_lines')->where('document_id', $admin_invoice->id)->delete();
        }
        \DB::table('crm_documents')->where('account_id', 1)->delete();
        // rebuild_ledger(date('Y-m'));

        $transactions = \DB::table('crm_documents')->where('docdate', $docdate)->where('billing_type', 'Monthly')->get();
        foreach ($transactions as $transaction) {
            $erp->setTable('crm_documents')->postDocument($transaction->id);
        }
        $erp->setDebtorBalance($account_ids);
        $erp->postDocumentCommit();

        set_document_lines_gp(null, $docdate);

        $billing_id = \DB::table('acc_billing')->orderBy('id', 'desc')->pluck('id')->first();
        verify_billing_summary($billing_id);

    }

    public function updateSubscriptions()
    {
        set_all_rental_prices();
    }
    //// Monthly Billing helper functions

    public function monthly_billing_customers($reseller, $customer, $docdate)
    {
        $total = 0;
        $partner_total = 0;

        //CUSTOMER INVOICE
        $total = 0;
        $partner_total = 0;
        unset($invoice);
        unset($lines);
        $invoice['doctype'] = 'Quotation';
        $invoice['discount'] = 0;
        $invoice['total'] = 0;

        $invoice['billing_type'] = 'Monthly';
        $invoice['bill'] = 1;
        $invoice['account_id'] = $customer->id;
        $invoice['docdate'] = $docdate;
        $invoice['reference'] = date('M Y', strtotime($docdate));
        //    $invoice['docdate'] = date('Y-m-01');
        //    $invoice['reference'] = 'Services: '.date('Y-m-01');
        $invoice['subscription_created'] = 1;

        $office_number = \DB::table('crm_rental_spaces')->where('id', $customer->rental_space_id)->pluck('office_number')->first();
        if (! $office_number) {
            return false;
        }
        if ($office_number) {
            $invoice['reference'] .= ' Office #'.$office_number;
        }

        $invoice['id'] = dbinsert('crm_documents', $invoice);
        $summary = [];
        $subscriptions = DB::select("select * from sub_services where status!='Deleted' and sub_services.account_id = ".$customer->id.' and detail like "Office #'.$office_number.'%"');

        $summary['rent'] = 0;
        $summary['services'] = 0;
        $summary['water'] = 0;
        $summary['armed_response'] = 0;
        $summary['internet'] = 0;
        $summary['billboard'] = 0;
        $summary['address'] = 0;
        $summary['total'] = 0;
        $excluded_references = [];
        /*
        $excluded_rentals = \DB::table('crm_rental_leases')->where('exclude_rent_bill', 1)->get();
          foreach ($excluded_rentals as $excl_rent) {
              $excluded_references[] = 'Office '.$excl_rent->office_number.' Rent';
          }
        */
        if (count($subscriptions) > 0) {
            $lines = [];
            foreach ($subscriptions as $sub) {
                $exclude_bill = false;
                /*
                foreach ($excluded_references as $ref) {
                    if ($sub->detail == $ref) {
                        $exclude_bill = true;
                    }
                }
                if ($exclude_bill) {
                    continue;
                }
                */

                //rent
                if ($sub->product_id == 13) {
                    $summary['rent'] += $sub->price * $sub->qty;
                }

                //council services
                if ($sub->product_id == 2) {
                    $summary['services'] += $sub->price * $sub->qty;
                }

                //internet
                if ($sub->product_id == 15) {
                    $summary['internet'] += $sub->price * $sub->qty;
                }

                //water
                if ($sub->product_id == 14) {
                    $summary['water'] += $sub->price * $sub->qty;
                }

                //armed_response
                if ($sub->product_id == 148) {
                    $summary['armed_response'] += $sub->price * $sub->qty;
                }
                $qty = $sub->qty;
                if ($qty > 0) {
                    if ($customer->status == 'Pending' and $sub->product_id == 11) {
                        $line['document_id'] = $invoice['id'];
                        $line['product_id'] = $sub->product_id;
                        $line['qty'] = $qty;
                        $line['description'] = $sub->detail;
                        $line['price'] = currency($sub->price);

                        $invoice['total'] += $line['price'];
                        dbinsert('crm_document_lines', $line);
                    } else {
                        $line['document_id'] = $invoice['id'];
                        $line['product_id'] = $sub->product_id;
                        $line['qty'] = $qty;
                        $line['description'] = $sub->detail;
                        $line['price'] = currency($sub->price);

                        $invoice['total'] += $line['price'];
                        dbinsert('crm_document_lines', $line);
                    }
                }
            }
        }

        //CUSTOMER
        if ($invoice['total'] > 0) {
            $invoice['tax'] = $invoice['total'] * 0.15;
            $invoice['total'] = $invoice['total'] + $invoice['tax'];

            dbset('crm_documents', 'id', $invoice['id'], ['tax' => currency($invoice['tax']), 'total' => currency($invoice['total'])]);
        } else {
            \DB::table('crm_document_lines')->where('document_id', $invoice['id'])->delete();
            \DB::table('crm_documents')->where('id', $invoice['id'])->delete();
        }

        \DB::table('sub_services')->where('account_id', $customer->id)->where('product_id', 11)->delete();
        $summary['total'] = $invoice['total'];

        return $summary;
    }

    public function saveSubscriptionTable($docdate)
    {

        $table = 'sub_services_lastmonth';
        \Schema::connection('default')->dropIfExists($table);
        schema_clone_db_table($table, 'sub_services');
        $table = 'crm_accounts_lastmonth';
        \Schema::connection('default')->dropIfExists($table);
        schema_clone_db_table($table, 'crm_accounts');

        \DB::connection('default')->table('sub_services_lastmonth')->update(['created_at' => date('Y-m-d', strtotime($docdate.' -1 day'))]);

        if (session('instance')->id == 2) {
            \DB::table('sub_services_lastmonth')->update(['created_at' => date('Y-m-01', strtotime('-1 month'))]);
        }
    }
}
