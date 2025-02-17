<?php

// https://github.com/EvilFreelancer/routeros-api-php
// https://wiki.mikrotik.com/wiki/API_PHP_package
use RouterOS\Client as IpClient;
use RouterOS\Query as IpQuery;

class MicroTik
{
    public function __construct()
    {
        $this->client = new IpClient(config('routeros-api'));
    }

    public function getIpRouteSearch($ip)
    {
        $query = new IpQuery('/ip/route/print');
        $query->where('dst-address', $ip);

        return $this->client->query($query)->read();
    }

    public function getIpAddressList()
    {
        return $this->client->query('/ip/address/print')->read();
    }

    public function addIp($ip) {}

    public function enableIp($ip)
    {
        $ip_list = $this->getIpAddressList();
        $id = '';
        foreach ($ip_list as $ip_ref) {
            if ($ip_ref['address'] == $ip) {
                $id = $ip_ref['.id'];
            }
        }
        if (empty($id)) {
            return 'Ip not found';
        }
        $query = new IpQuery('/ip/address/set');
        $query->equal('.id', $id);
        $query->equal('disabled', 'false');

        return $this->client->query($query)->read();
    }

    public function disableIp($ip)
    {
        $id = '';

        $query = new IpQuery('/ip/address/set');
        $query->equal('dst-address', $ip);
        $query->equal('disabled', 'true');

        return $this->client->query($query)->read();
    }
}
