<?php

class SageOne
{
    public function __construct($debug = false)
    {
        //https://www.sage.com/en-za/sage-business-cloud/accounting/developer-api/
        //https://accounting.sageone.co.za/api/2.0.0
        $this->api_url = 'https://accounting.sageone.co.za/api/2.0.0/';
        $this->username = 'ahmed@telecloud.co.za';
        $this->password = 'Ao@147896';
        $this->api_key = base64_encode(urlencode($this->username.':'.$this->password));

        $this->debug = $debug;
    }

    public function getAccountList()
    {
        return $this->curl('Account/Get');
    }

    private function curl($endpoint, $args = [], $method = 'get')
    {
        // $args['apikey'] = $this->api_key;
        $url = $this->api_url . $endpoint;
        if ($this->debug == 'output') {
        }

        if ($this->debug == 'log') {
            exception_log($url);
            exception_log($method);
            exception_log($args);
        }


        if ($method == 'post') {
            $url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::post($url)
                ->addHeader('Basic', $this->api_key)
                ->body($args)
                ->withoutStrictSsl()
                ->send();
        }

        if ($method == 'put') {
            $url = $this->buildUrl($url, $args);
            $response = \Httpful\Request::put($url)
                ->addHeader('Basic', $this->api_key)
                ->body($args)
                ->withoutStrictSsl()
                ->send();
        }

        if ($method == 'get') {
            $url = $this->buildUrl($url, $args);

            $response = \Httpful\Request::get($url)
                ->addHeader('Basic', $this->api_key)
                ->send();
        }

        if ($this->debug == 'output') {
        }

        if ($this->debug == 'log') {
            exception_log($response);
        }

        if (!empty($response->body)) {
            return $response->body;
        } else {
            return (object) ['intCode' => $response->code];
        }
        return $response;
    }

    private function buildUrl($url, $data = array())
    {
        return $url . (empty($data) ? '' : '?' . http_build_query($data));
    }
}
