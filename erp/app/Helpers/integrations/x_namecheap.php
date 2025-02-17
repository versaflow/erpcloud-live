<?php

function namecheap_get_domains()
{
    $n = new Namecheap();
    $list = $n->getList();
    $list = $n->parseXml($list);

    $domain_array = $list['CommandResponse']['DomainGetListResult']['Domain'];
    $total_fetched = $list['CommandResponse']['Paging']['PageSize'] * $list['CommandResponse']['Paging']['CurrentPage'];


    while ($total_fetched < $list['CommandResponse']['Paging']['TotalItems']) {
        $page_number = $list['CommandResponse']['Paging']['CurrentPage']+1;
        $list = $n->getList('ALL', $page_number);
        $list = $n->parseXml($list);

        $domain_results = $list['CommandResponse']['DomainGetListResult']['Domain'];
        foreach ($domain_results as $domain) {
            $domain_array[] = $domain;
        }
        $total_fetched = $list['CommandResponse']['Paging']['PageSize'] * $list['CommandResponse']['Paging']['CurrentPage'];
    }
    return $domain_array;
}

function namecheap_register($domain, $account_id, $nameservers = 'host1.cloudtools.co.za,host2.cloudtools.co.za')
{
    $namecheap = new Namecheap();
    $result = $namecheap->register($domain, $account_id, $nameservers);
    $result = $namecheap->parseXml($result);
    return $result;
}

function namecheap_transfer($domain, $epp_code)
{
    $namecheap = new Namecheap();
    $result = $namecheap->transfer($domain, $epp_code);
    $result = $namecheap->parseXml($result);
    return $result;
}

function namecheap_get_transfer_status($transfer_id)
{
    $namecheap = new Namecheap();
    $result = $namecheap->getTransferStatus($transfer_id);
    $result = $namecheap->parseXml($result);
    return $result;
}

function namecheap_get_lock_status($domain)
{
    $n = new Namecheap();
    $r = $n->getRegistrarLock('easybuilder.io');
    $r = $n->parseXml($r);
    $lock_status = $r["CommandResponse"]["DomainGetRegistrarLockResult"]["@attributes"]["RegistrarLockStatus"];
    return $lock_status;
}

function namecheap_set_lock_status($domain, $lock)
{
    $n = new Namecheap();
    $r = $n->setRegistrarLock('easybuilder.io', $lock);
    $r = $n->parseXml($r);
    $lock_status = $r["CommandResponse"]["DomainSetRegistrarLockResult"]["@attributes"]["RegistrarLockStatus"];
    return $lock_status;
}

function namecheap_set_nameservers($domain, $nameservers = 'localhost,host2.cloudtools.co.za')
{
    $n = new Namecheap();
    $r = $n->setNameservers($domain, $nameservers);
    $r = $n->parseXml($r);

    return $r;
}

function namecheap_set_contacts($domain, $account_id)
{
    $n = new Namecheap();
    $r = $n->setContacts($account_id, $domain);
    $r = $n->parseXml($r);

    return $r;
}

function namecheap_get_info($domain)
{
    $namecheap = new Namecheap();
    $result = $namecheap->getInfo($domain);
    $result = $namecheap->parseXml($result);
    return $result;
}


function namecheap_domain_expiry($domain)
{
    $namecheap = new Namecheap();
    $result = $namecheap->getInfo($domain);
    $result = $namecheap->parseXml($result);
    if(isset($result["CommandResponse"]["DomainGetInfoResult"]["DomainDetails"]["ExpiredDate"])){
        return date('Y-m-d',strtotime($result["CommandResponse"]["DomainGetInfoResult"]["DomainDetails"]["ExpiredDate"]));    
    }
    
    return false;
}
