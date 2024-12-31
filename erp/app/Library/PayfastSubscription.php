<?php
/*
https://developers.payfast.co.za/api#recurring-billing
GET /subscriptions/:token/fetch
PUT /subscriptions/:token/pause
PUT /subscriptions/:token/unpause
PUT /subscriptions/:token/cancel
PATCH /subscriptions/:token/update
POST /subscriptions/:token/adhoc
*/

/*
20576152
i72y98wbgtcsf
Netstream786

Movie magic sandbox
Merchant ID:	10031307
Merchant Key:	egtvae67byv2k
egtvae67byv2k
*/

use PayFast\PayFastApi as PayFastApi;
use PayFast\PayFastPayment as PayFastPayment;

class PayfastSubscription extends Payfast
{
    public function __construct()
    {
        parent::__construct();
        $this->notify_url = url('/payfast_subscription_notify');
        $this->debug = false;
    }

    public function setAccount($account_id)
    {
        $this->account = dbgetaccount($account_id);
    }

    public function getForm($amount = 100)
    {
        try {
            if ($this->debug) {
                $payfast = new PayFastPayment([
                    'merchantId' => $this->sandbox_merchant_id,
                    'merchantKey' => $this->sandbox_merchant_key,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => true,
                ]);
            } else {
                $payfast = new PayFastPayment([
                    'merchantId' => $this->merchant_id,
                    'merchantKey' => $this->merchant_key,
                    'passPhrase' => $this->pass_phrase,
                ]);
            }

            $billing_date = date('Y-m-d', strtotime('first day of next month'));

            $data = [
                'merchant_id' => ($this->debug) ? $this->sandbox_merchant_id : $this->merchant_id,
                'merchant_key' => ($this->debug) ? $this->sandbox_merchant_key : $this->merchant_key,
                'return_url' => $this->return_url,
                'cancel_url' => $this->cancel_url,
                'notify_url' => $this->notify_url,
                'm_payment_id' => $this->m_payment_id,
                'amount' => currency($amount),
                'recurring_amount' => $this->account->subs_total,
                'item_name' => $this->reseller->company.' Subscription',
                'custom_int1' => $this->account->id,
                'payment_method' => 'cc',
                'subscription_type' => 1,
                'billing_date' => $billing_date,
                'frequency' => 3,
                'cycles' => 0,
            ];

            $form = $payfast->custom->createFormFields($data, ['value' => 'Subscribe Now', 'class' => 'btn']);

            // replace submit button with img
            $subscribe_btn = ' <div class="mt-2 text-right">
            <button type="submit" id="pf-submitbtn" ><img src="https://www.payfast.co.za/images/buttons/light-large-paynow.png" width="174" height="59" alt="Pay" title="Pay Now with PayFast" /></button>
            </div>';
            $form = str_replace('<input type="submit" value="Subscribe Now" value="Subscribe Now" class="btn" />', $subscribe_btn, $form);

            return $form;
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function getSignupForm($product, $amount, $name_first, $name_last)
    {

        $this->notify_url = url('/payfast_subscription_signup_notify');
        try {
            if ($this->debug) {
                $payfast = new PayFastPayment([
                    'merchantId' => $this->sandbox_merchant_id,
                    'merchantKey' => $this->sandbox_merchant_key,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => true,
                ]);
            } else {
                $payfast = new PayFastPayment([
                    'merchantId' => $this->merchant_id,
                    'merchantKey' => $this->merchant_key,
                    'passPhrase' => $this->pass_phrase,
                ]);
            }

            $billing_date = date('Y-m-d', strtotime('first day of next month'));

            $data = [
                'merchant_id' => ($this->debug) ? $this->sandbox_merchant_id : $this->merchant_id,
                'merchant_key' => ($this->debug) ? $this->sandbox_merchant_key : $this->merchant_key,
                'return_url' => $this->return_url,
                'cancel_url' => $this->cancel_url,
                'notify_url' => $this->notify_url,
                'm_payment_id' => $this->m_payment_id,
                'amount' => currency($product->price_tax * 100),
                'recurring_amount' => currency($product->price_tax * 100),
                'item_name' => $product->name,
                'item_description' => $product->description,
                'name_first' => $name_first,
                'name_last' => $name_last,
                'email_address' => $this->account->email_address,
                'custom_int1' => $this->account->id,
                'custom_int2' => $product->id,
                'payment_method' => 'cc',
                'subscription_type' => 1,
                'billing_date' => $billing_date,
                'frequency' => 3,
                'cycles' => 0,
            ];
            $form = $payfast->custom->createFormFields($data, ['value' => 'Subscribe Now', 'class' => 'btn']);

            // replace submit button with img
            $subscribe_btn = ' <div class="mt-2 text-right">
            <button type="submit" id="pf-submitbtn" ><img src="https://www.payfast.co.za/images/buttons/light-large-paynow.png" width="174" height="59" alt="Pay" title="Pay Now with PayFast" /></button>
            </div>';
            $form = str_replace('<input type="submit" value="Subscribe Now" value="Subscribe Now" class="btn" />', $subscribe_btn, $form);

            return $form;
        } catch (Exception $e) {
            return false;
        }
    }

    public function all()
    {
        try {
            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions;
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function get_all()
    {
        try {
            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions->fetch();
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function get($token)
    {
        try {
            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions->fetch($token);
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function pause($token)
    {
        try {
            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions->pause($token);
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function unpause($token)
    {
        try {
            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions->unpause($token);
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function cancel($token)
    {
        try {
            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions->cancel($token);
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function update($token, $data)
    {
        try {
            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions->update($token, $data);
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }

    public function set_billed_amount($token, $amount)
    {
        try {
            $amount = str_replace('.', '', currency($amount));

            $api = new PayFastApi(
                [
                    'merchantId' => $this->merchant_id,
                    'passPhrase' => $this->pass_phrase,
                    'testMode' => $this->debug,
                ]
            );

            return $api->subscriptions->update($token, ['amount' => $amount]);
        } catch (Exception $e) {
            return 'There was an exception: '.$e->getMessage();
        }
    }
}
