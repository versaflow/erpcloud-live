<?php

use Httpful\Request as ApiRequest;

class CloudtelecomsApi
{
    public function __construct($debug = false)
    {
        $this->debug = $debug;
        $this->service_url = env('APP_URL').'/rest_api/';
    }

    protected function setCurlParams($endpoint, $args, $method)
    {
        $endpoint_url = $this->service_url.$endpoint;

        return ['endpoint_url' => $endpoint_url, 'args' => $args];
    }

    protected function setCurlAuth($api_request)
    {
        return $api_request;
    }

    public function call($endpoint, $args = [], $method = 'get')
    {
        return $this->curl($endpoint, $args, $method);
    }

    protected function curl($endpoint, $args = [], $method = 'get')
    {
        try {
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
                $response = $api_request->body($args)->sendsJson()
                    ->withoutStrictSsl()
                    ->send();
            }

            if ($method == 'put') {
                $url = $this->buildUrl($url, $args);
                $api_request = ApiRequest::put($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->body($args)->sendsJson()
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
                return (object) ['http_code' => $response->code, 'response' => $response->body];
            } else {
                return (object) ['http_code' => $response->code];
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

    protected function buildUrl($url, $data = [])
    {
        return $url.(empty($data) ? '' : '?'.http_build_query($data));
    }
}
