<?php

use Httpful\Request as ApiRequest;

class ApiCurl
{
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    public function setUrl($url)
    {
        $this->service_url = $url;

        return $this;
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
                aa($url);
                aa($method);
                aa($args);
            }

            if ($method == 'post') {
                $api_request = ApiRequest::post($url);
                $api_request = $this->setCurlAuth($api_request);
                $response = $api_request->body($args)
                    ->withoutStrictSsl()
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

    protected function buildUrl($url, $data = [])
    {
        return $url.(empty($data) ? '' : '?'.http_build_query($data));
    }
}
