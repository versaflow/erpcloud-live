<?php

function wp_endpoints()
{
    $wp = new WordPress;

    $endpoints = $wp->getEndpoints();
}

function wp_orders()
{
    $wp = new WordPress;
    $orders = $wp->getOrders();

    foreach ($orders as $order) {
        if ($order['customer_id'] != 0) {
            $customer_ids[] = $order['customer_id'];
            $customers[] = $wp->getCustomerById($order['customer_id']);
        }
    }
}

/// https://portal.telecloud.co.za/helper/create_wp_customer
function create_wp_customer($account_id = 1969)
{
    $wp = new WordPress;
    $account = dbgetaccount($account_id);
    // use user_data for password/email/username
    // login info for customer must be the same on both systems
    $user_data = get_account_user($account_id);
    //dd($account,$user_data);

    $wordpress_customer = [

        //'username' =>$account->username,
        'first_name' => $account->full_name,
        'email' => $account->email,
        'date_created' => $account->created_at,
        'date_modified' => $account->updated_at,
        'address_1' => $account->address,
        'phone' => $account->phone,
        //'meta_data' => ['key'=> 'erp_id','value'=>$account->id]

    ];
    // format $wordpress_customer
    // customer meta data needs to include erp_account_id, same as markOrderProcessed
    // if erp_account_id is set correctly you can use getCustomerByErpId to get the customer returns false if doesnt exists

    // check https://woocommerce.com/document/woocommerce-rest-api/ to get required data for customer create
    // https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction
    try {
        if (! empty($account->website_id)) {
            //update customers
            $result = $wp->updateCustomer($wordpress_customer);
        } else {
            $result = $wp->createCustomer($wordpress_customer);
        }
        if (! empty($result['id'])) {
            dbset('crm_accounts', 'id', $account->id, ['website_id' => $result['id']]);
        }
        //dd($result);
    } catch (\Throwable $ex) {
        exception_log($ex);
        $customers = $wp->getCustomers();
    }
}
