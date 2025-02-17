<?php

class OpenXChange
{
    /*
        Create Context
        /opt/open-xchange/sbin/createcontext -A oxadminmaster -P Ao@147896 -N domain.co.za

        Change Context
        /opt/open-xchange/sbin/changecontext -A oxadminmaster -P Ao@147896 -c 2 -N arbee.co.za

        Create User
        /opt/open-xchange/sbin/createuser -c 2 -u user -d "Ismail" -g Ismail -s Arbee -p Mail123 -e ismail@arbee.co.za -q 5120 --access-combination-name=groupware_standard --imaplogin ismail@arbee.co.za

        Change User
        /opt/open-xchange/sbin/changeuser -c 2 -u user -d "Ismail" -g Ismail -s Arbee -p Mail123 -e ismail@arbee.co.za -q 5120 --access-combination-name=groupware_standard --imaplogin ismail@arbee.co.za

        Delete User
        /opt/open-xchange/sbin/deleteuser -c 2 -u user -A oxadminmaster -P Ao@147896

        Reload Config
        /opt/open-xchange/sbin/reloadconfiguration

        Import Contacts fields
        Company, Given name, Telephone business 1, Cellular telephone 1, Email 1
    */
    public $server_ip;

    public $ssh_user;

    public $ssh_pass;

    public $ox_user;

    public $ox_pass;

    public $client;

    public function __construct()
    {
        $this->server_ip = 'secure.office247.co.za';
        $this->ssh_user = 'remote';
        $this->ssh_pass = 'Webmin321';
        $this->ox_user = 'oxadminmaster';
        $this->ox_pass = 'Ao@147896';

        $ssh = new \phpseclib\Net\SSH2($this->server_ip);
        if (! $ssh->login($this->ssh_user, $this->ssh_pass)) {
            abort('500', 'SSH connection failed');
        }
        $this->client = $ssh;
    }

    private function call($command)
    {
        return $this->client->exec($command);
    }

    public function reload_conf()
    {
        return $this->call('/opt/open-xchange/sbin/reloadconfiguration');
    }

    public function create_context($domain)
    {
        return $this->call('/opt/open-xchange/sbin/createcontext -A '.$this->ox_user.' -P '.$this->ox_pass.' -N '.$domain);
    }

    public function change_context()
    {
        return $this->call('/opt/open-xchange/sbin/changecontext -A '.$this->ox_user.' -P '.$this->ox_pass.' -c 2 -N arbee.co.za');
    }

    public function create_user()
    {
        return $this->call('/opt/open-xchange/sbin/createuser -c 1 -u thuto -d "Thuto" -g Thuto -s Arbee -p Mail123 -e thuto@telecloud.co.za -q 20000 --access-combination-name=groupware_standard --imaplogin thuto@telecloud.co.za');
    }

    public function change_user()
    {
        return $this->call('/opt/open-xchange/sbin/changeuser -c 1 -u thuto -d "Thuto" -g Thuto -s Arbee -p Mail123 -e thuto@telecloud.co.za -q 20000 --access-combination-name=groupware_standard --imaplogin thuto@telecloud.co.za');
    }

    public function delete_user()
    {
        return $this->call('/opt/open-xchange/sbin/deleteuser -c 2 -u user -A '.$this->ox_user.' -P '.$this->ox_pass);
    }

    public function listcontext()
    {
        return $this->call('/opt/open-xchange/sbin/listcontext -A '.$this->ox_user.' -P '.$this->ox_pass);
    }

    public function getcontextid($domain)
    {
        $context_list = $this->listcontext();
        foreach ($context_list as $context) {
        }
    }
}
