<?php

class ErpBilling
{
    public $account_id;

    public $annual_billing;

    public $billing_frequency;

    public function __construct()
    {
        $this->annual_billing = false;
        $this->date_activated_billing = false;
        $this->customer_type = false;
        $this->delete_documents = true;
        $this->billing_frequency = 1;
        $this->billing_on_renewal = 0;
        $this->limit_account_id = false;
    }

    public function setCustomerType($customer_type)
    {
        $this->customer_type = $customer_type;
    }

    public function setBillOnRenewal($billing_on_renewal)
    {
        $this->billing_on_renewal = $billing_on_renewal;
    }

    public function setBillingFrequency($frequency)
    {
        $this->billing_frequency = $frequency;
    }

    public function limitAccountId($limit_account_id)
    {
        $this->limit_account_id = $limit_account_id;
    }

    public function monthly_billing($docdate = false)
    {
        ini_set('max_execution_time', '600');
        if (! $this->customer_type) {
            return false;
        }

        if (! $docdate) {
            $docdate = date('Y-m-01');
        }

        // $renewal_start = $docdate;
        // $renewal_end = $docdate;
        // if($this->billing_on_renewal){
        // $renewal_end = date('Y-m-01');
        // }

        $billing = [
            'billing_date' => $docdate,
            'billing_type' => 'Monthly',
        ];

        if ($this->billing_on_renewal) {
            $billing['billing_type'] = 'Renewal';
        }
        $billing['name'] = $docdate.' '.$billing['billing_type'];
        $billing_id = \DB::table('acc_billing')->where('billing_type', $billing['billing_type'])->where('billing_date', $docdate)->pluck('id')->first();

        if (! $billing_id) {
            $billing_id = \DB::table('acc_billing')->insertGetId($billing);
        }

        $admin = dbgetaccount(1);

        $db = new \DBEvent;
        $bills_created = false;

        \DB::table('sub_services')->whereNull('renews_at')->update(['renews_at' => $docdate]);
        $duedate = date('Y-m-10');
        $currency = 'ZAR';
        //Check Instance and renewal type
        if (session('instance')->id == 1) {
            if ($this->billing_on_renewal) { //305
                $account_ids = \DB::table('crm_accounts')->where('renewal_date_billing', 1)->where('id', '!=', 1)->where('type', $this->customer_type)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
            } else {
                $account_ids = \DB::table('crm_accounts')->where('renewal_date_billing', 0)->where('type', $this->customer_type)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
            }
        } else {
            if ($this->customer_type == 'reseller') {//TODO Ahmed
            } else {
            }
            if ($this->billing_on_renewal) {
                $account_ids = \DB::table('sub_services')->where('bill_frequency', '!=', 1)->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
                $account_ids = \DB::table('crm_accounts')->whereIn('id', $account_ids)->where('id', '!=', 1)->where('type', $this->customer_type)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
            } else {
                $account_ids = \DB::table('sub_services')->where('bill_frequency', 1)->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
                $account_ids = \DB::table('crm_accounts')->whereIn('id', $account_ids)->where('id', '!=', 1)->where('type', $this->customer_type)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
            }
        }
        if (empty($account_ids)) {
            return false;
        }
        $service_account_ids = $account_ids;
        if ($this->customer_type == 'reseller') {
            $service_account_ids = \DB::table('crm_accounts')->whereIn('partner_id', $account_ids)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        }

        //Get subscriptions
        if ($this->billing_on_renewal) {
            $subscriptions_query = \DB::table('sub_services')
                ->join('crm_products', 'sub_services.product_id', '=', 'crm_products.id')
                ->select(
                    'sub_services.id',
                    'sub_services.qty',
                    'sub_services.provision_type',
                    'sub_services.usage_allocation',
                    'sub_services.date_activated',
                    'sub_services.created_at',
                    'sub_services.account_id',
                    'sub_services.product_id',
                    'sub_services.bill_frequency',
                    'sub_services.detail',
                    'crm_products.name',
                    'crm_products.code',
                    'crm_products.provision_package',
                    'crm_products.is_subscription',
                    'crm_products.product_bill_frequency',
                    'sub_services.price',
                    'sub_services.price_incl',
                    'sub_services.invoice_id',
                    'sub_services.renews_at'
                )
                ->whereIn('sub_services.account_id', $service_account_ids)
                ->where('crm_products.is_subscription', 1)
                ->whereRaw('(sub_services.to_cancel=0 or (sub_services.to_cancel=1 and sub_services.cancel_date>"'.$docdate.'"))')
                ->where('sub_services.bundle_id', 0)
                ->where('sub_services.bill_frequency', $this->billing_frequency)
                ->where('sub_services.status', '!=', 'Deleted')
                ->where('sub_services.status', '!=', 'Pending')
                ->where('sub_services.provision_type', 'NOT LIKE', '%prepaid%')
                ->where('sub_services.created_at', '<', $docdate)
                ->where('sub_services.renews_at', '<=', $docdate);
        } else {
            $subscriptions_query = \DB::table('sub_services')
                ->join('crm_products', 'sub_services.product_id', '=', 'crm_products.id')
                ->select(
                    'sub_services.id',
                    'sub_services.qty',
                    'sub_services.provision_type',
                    'sub_services.usage_allocation',
                    'sub_services.date_activated',
                    'sub_services.created_at',
                    'sub_services.account_id',
                    'sub_services.product_id',
                    'sub_services.bill_frequency',
                    'sub_services.detail',
                    'crm_products.name',
                    'crm_products.code',
                    'crm_products.provision_package',
                    'crm_products.is_subscription',
                    'crm_products.product_bill_frequency',
                    'sub_services.price',
                    'sub_services.price_incl',
                    'sub_services.invoice_id',
                    'sub_services.renews_at'
                )
                ->whereIn('sub_services.account_id', $service_account_ids)
                ->where('crm_products.is_subscription', 1)
                ->whereRaw('(sub_services.to_cancel=0 or (sub_services.to_cancel=1 and sub_services.cancel_date > '.$docdate.'))')
                ->where('sub_services.bundle_id', 0)
                ->where('sub_services.bill_frequency', $this->billing_frequency)
                ->where('sub_services.status', '!=', 'Deleted')
                ->where('sub_services.status', '!=', 'Pending')
                ->where('sub_services.provision_type', 'NOT LIKE', '%prepaid%')
                ->where('sub_services.created_at', '<', $docdate)
                ->where('sub_services.renews_at', '<=', $docdate);
        }
        if (! empty($this->limit_account_id)) {
            $subscriptions_query->where('sub_services.account_id', $this->limit_account_id);
        }

        $sql = querybuilder_to_sql($subscriptions_query);

        $subscriptions = $subscriptions_query->get();
        // print_r($sql);

        $account_subscriptions = collect($subscriptions)->groupBy('account_id');

        $process_account_ids = [];
        $invoice_ids = [];
        foreach ($account_subscriptions as $account_id => $subscriptions) {
            //Check if documents exist
            $exists = \DB::table('crm_documents')->where('account_id', $account_id)->where('docdate', $docdate)->where('billing_type', 'Monthly')->count();
            if ($exists) {
                continue;
            }

            $num_subscriptions = count([$subscriptions]);
            unset($invoice);
            unset($line);
            if ($num_subscriptions > 0) {
                $customer = dbgetaccount($account_id);
                $reseller = dbgetaccount($customer->partner_id);
                $invoice['total'] = 0;

                $invoice['account_id'] = $customer->id;
                if ($reseller->id != 1) {
                    $invoice['account_id'] = $reseller->id;
                    $invoice['reseller_user'] = $customer->id;
                } else {
                    $invoice['account_id'] = $customer->id;
                    $invoice['reseller_user'] = 0;
                }

                if ($this->billing_on_renewal) {
                    //$invoice['doctype'] = 'Tax Invoice';
                    $invoice['doctype'] = 'Quotation';
                } else {
                    $invoice['doctype'] = 'Quotation';
                }

                $invoice['docdate'] = $docdate;
                $invoice['duedate'] = $duedate;
                $invoice['billing_type'] = ($this->billing_frequency == 1) ? 'Monthly' : 'Contract';
                if ($this->billing_on_renewal) {
                    $invoice['billing_type'] = 'Renewal';
                }
                $invoice['document_currency'] = ($customer->partner_id != 1) ? $reseller->currency : $customer->currency;
                $invoice['bill'] = 1;
                $invoice['completed'] = 1;
                $invoice['reference'] = 'Billing: '.date('M Y', strtotime($docdate));
                if ($this->billing_frequency == 12) {
                    $invoice['reference'] = 'Billing: Jan '.date('Y').' - Dec '.date('Y');
                } elseif ($this->billing_frequency == 24) {
                    $invoice['reference'] = 'Billing: Jan '.date('Y').' - Dec '.date('Y', strtotime('+ 1 year'));
                } elseif ($this->billing_frequency != 1) {
                    $invoice['reference'] = 'Billing: '.date('M Y', strtotime($docdate)).' - '.date('M Y', strtotime($docdate.' + '.$this->billing_frequency.' months'));
                }

                $account_currency = $customer->currency;

                if ($account_currency == 'USD') {
                    $date_format = 'd/m/Y';
                } else {
                    $date_format = 'Y-m-d';
                }

                $invoice['subscription_created'] = 1;
                $invoice['doc_no'] = \DB::table('crm_documents')->where('doctype', 'Tax Invoice')->max('doc_no');
                $invoice['doc_no']++;

                $invoice['id'] = dbinsert('crm_documents', $invoice);

                foreach ($subscriptions as $subscription) {
                    //CUSTOMER
                    $line = [];
                    $line['document_id'] = $invoice['id'];
                    $line['product_id'] = $subscription->product_id;
                    $line['qty'] = $subscription->qty;

                    if ($subscription->provision_type == 'bulk_sms') {
                        $package_amount = $subscription->provision_package;
                        $line['qty'] = $subscription->usage_allocation / $package_amount;
                    }

                    $product = dbgetrow('crm_products', 'id', $subscription->product_id);
                    $line['cost_price'] = $product->cost_price;
                    if ($this->billing_frequency != 1) {
                        if ($subscription->product_bill_frequency == $subscription->bill_frequency) {
                            $line['qty'] = $subscription->qty;
                        } elseif ($subscription->bill_frequency > 1) {
                            $line['qty'] = $subscription->qty * $subscription->bill_frequency;
                        }
                    }

                    $line['full_price'] = $line['price'] = $subscription->price_incl;

                    if ($customer->currency == 'ZAR' && $admin->vat_enabled == 1) {
                        $line['full_price'] = $line['full_price'] / 1.15;
                        $line['price'] = $line['price'] / 1.15;
                    }
                    if (! str_contains($subscription->detail, 'voice')) {
                        $line['description'] = $subscription->name.' - '.$subscription->detail;
                    } else {
                        $line['description'] = $subscription->name;
                    }

                    $renewal_date = $subscription->renews_at;
                    $billing_period_end = date($date_format, strtotime($renewal_date.' + '.$subscription->bill_frequency.' month'));
                    $billing_period_end = date($date_format, strtotime($billing_period_end.' -1 day'));
                    $invoice['description'] .= PHP_EOL.'Billing: '.date($date_format, strtotime($renewal_date)).' - '.$billing_period_end;

                    // PRORATA: DISCOUNT FOR INITIAL INVOICE
                    if (! $this->billing_on_renewal && $subscription->invoice_id && date('Y-m', strtotime($subscription->date_activated)) == date('Y-m', strtotime($docdate.' -1 month'))) {
                        // calculate prorata days from initial invoice
                        $invoice_doc_date = \DB::table('crm_documents')->where('id', $subscription->invoice_id)->pluck('docdate')->first();
                        $invoice_date_days = intval(date('t', strtotime($invoice_doc_date)));
                        $prorata_price = ($line['price'] / $invoice_date_days) * (intval(date('d', strtotime($invoice_doc_date))));
                        $line['prorata_difference'] = $prorata_price;
                        if ($prorata_price > 0) {
                            $line['price'] -= $line['prorata_difference'] / $line['qty'];

                            $line['description'] .= PHP_EOL.get_currency_symbol($customer->currency).' '.currency($line['prorata_difference'] / $line['qty']).' 1st month prorata.';
                        }
                    }

                    //CREATE DOCUMENT LINE
                    $line['subscription_id'] = $subscription->id;
                    $new_renewal_date = date('Y-m-01', strtotime($renewal_date.' + '.$subscription->bill_frequency.' month'));
                    $line['description'] .= PHP_EOL.$renewal_date.' - '.$new_renewal_date;
                    $line_id = dbinsert('crm_document_lines', $line);
                    $invoice['total'] += $line['qty'] * $line['price'];
                    \DB::table('sub_services')->where('id', $subscription->id)->update(['renews_at' => $new_renewal_date, 'last_invoice_date' => $docdate]);
                }

                //CUSTOMER

                $bills_created = true;
                $invoice_ids[] = $invoice['id'];
                if ($customer->currency == 'ZAR' && $admin->vat_enabled == 1) {
                    $invoice['tax'] = $invoice['total'] * 0.15;
                    $invoice['total'] = $invoice['total'] + $invoice['tax'];
                } else {
                    $invoice['tax'] = 0;
                }
                dbset('crm_documents', 'id', $invoice['id'], ['tax' => currency($invoice['tax']), 'total' => currency($invoice['total'])]);

                $db->setTable('crm_documents')->setServiceInvoice($invoice['id']);

                $process_account_ids[] = $account_id;
            }
        }

        $db->setTable('crm_documents');
        if (count($invoice_ids) == 0) {
            \DB::table('acc_billing')->where('id', $billing_id)->delete();
        }

        $monthly_account_ids = collect($process_account_ids)->unique()->toArray();
        $db->setDebtorBalance($monthly_account_ids);
        $db->postDocumentCommit();

        set_document_lines_gp(null, $docdate);
    }

    public function resetAdminBalance($docdate)
    {
        if (! is_main_instance()) {
            return false;
        }
    }

    public function processCancellations($docdate)
    {
        $erp_subscriptions = new ErpSubs;
        $subscription_ids = \DB::table('sub_services')->where('to_cancel', 1)->where('cancel_date', '<=', $docdate)->pluck('id')->toArray();

        if (! empty($subscription_ids) && is_array($subscription_ids) && count($subscription_ids) > 0) {
            foreach ($subscription_ids as $subscription_id) {
                $erp_subscriptions->deleteSubscription($subscription_id, true);
            }
        }
    }

    public function saveSubscriptionTable()
    {
        if (empty($this->annual_billing) && ! $this->billing_on_renewal) {
            $table = 'sub_services_lastmonth';
            \Schema::connection('default')->dropIfExists($table);
            schema_clone_db_table($table, 'sub_services');
            $table = 'crm_accounts_lastmonth';
            \Schema::connection('default')->dropIfExists($table);
            schema_clone_db_table($table, 'crm_accounts');
        }
    }
}
