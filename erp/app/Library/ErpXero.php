<?php

class ErpXero
{
    public function __construct()
    {
        // https://developer.xero.com/documentation/bank-feeds-api/overview
        // https://github.com/calcinai/xero-php/issues/644
        if (empty(session('xero_token'))) {
            return false;
        }
    }

    public function getContacts()
    {
        $accessToken = session('xero_token');
        $tenantId = session('xero_tenants')[0]->tenantId;
        $xero = new \XeroPHP\Application($accessToken, $tenantId);
        return $xero->load(\XeroPHP\Models\Accounting\Contact::class)->execute();
    }

    public function getPayments()
    {
        $accessToken = session('xero_token');
        $tenantId = session('xero_tenants')[0]->tenantId;
        $xero = new \XeroPHP\Application($accessToken, $tenantId);
        return $xero->load(\XeroPHP\Models\Accounting\Payment::class)->execute();
    }
}
