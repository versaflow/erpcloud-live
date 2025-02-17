<?php

//https://www.reamaze.com/api

/*
curl 'https://{brand}.reamaze.io/api/v1/conversations' \
  -H 'Accept: application/json' -u {login-email}:{api-token}
*/
use Httpful\Request as ApiRequest;

class Reamaze extends ApiCurl
{
    public function __construct($debug = false)
    {
        $this->debug = $debug;
        $this->authuser = 'ahmed@telecloud.co.za';
        $this->authpass = '9a4fcc83814f3662ebfbe8f4c1c1bae7f8b988d0dfcccd3864a7dac190811c42';
        $this->service_url = 'https://cloudtelecoms.reamaze.io/api/v1/';
    }

    protected function setCurlAuth($api_request)
    {
        $api_request->authenticateWith($this->authuser, $this->authpass);

        return $api_request;
    }

    public function getCoversations($filters = false)
    {
        if (! empty($filters) && is_array($filters) && count($filters) > 0) {
            return $this->curl('conversations', $filters);
        } else {
            return $this->curl('conversations');
        }
    }

    protected function curl($endpoint, $args = [], $method = 'get')
    {
        try {
            $endpoint = $endpoint.'.json';
            $curl_params = $this->setCurlParams($endpoint, $args, $method);
            $url = $curl_params['endpoint_url'];
            $args = $curl_params['args'];

            if ($this->debug == 'output') {
            }

            if ($this->debug == 'log') {
                exception_log($url);
                exception_log($method);
                exception_log($args);
            }

            if ($method == 'post') {
                $api_request = ApiRequest::post($url);
                $api_request = $this->setCurlAuth($api_request);
                $api_request->method(\Httpful\Http::POST);

                $response = $api_request->body($args)
                    ->send();
            }

            if ($method == 'put') {
                $url = $this->buildUrl($url, $args);
                $api_request = ApiRequest::put($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->body($args)
                    ->withoutStrictSsl()
                    ->send();
            }

            if ($method == 'get') {
                $url = $this->buildUrl($url, $args);

                $api_request = ApiRequest::get($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->send();
            }

            if ($method == 'delete') {
                $api_request = ApiRequest::delete($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->send();
            }

            if ($this->debug == 'output') {
            }

            if ($this->debug == 'log') {
                exception_log($response);
            }

            if (! empty($response->body)) {
                return $response->body;
            } else {
                return (object) ['intCode' => $response->code];
            }

            return $response;
        } catch (\Throwable $ex) {
            exception_log($ex);
            if ($this->debug == 'output') {
            }

            if ($this->debug == 'log') {
                exception_log($ex->getMessage());
                exception_log($ex->getTraceAsString());
            }
        }
    }
}
