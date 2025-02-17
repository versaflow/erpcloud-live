<?php

use Httpful\Request as ApiRequest;

class OnlyOffice extends ApiCurl
{
    public function __construct($debug = false)
    {
        $this->service_url = 'http://ct.cloudtools.co.za/';
        $this->username = 'ahmed@telecloud.co.za';
        $this->password = 'Webmin786';
        $this->debug = $debug;
    }

    public function setDebug()
    {
        $this->debug = 'output';
        return $this;
    }

    public function getProjects()
    {
        return $this->curl("project");
    }

    /// CURL
    private function login()
    {
        $args["userName"] = $this->username;
        $args["password"] = $this->password;

        $response = $this->curl("authentication", $args, 'post');

        $this->auth_token = $response->response;
    }


    private function checkSession()
    {
        if (empty($this->auth_token) || (!empty($this->auth_token) && date('Y-m-d H:i:s', strtotime($this->auth_token->expires)) < date('Y-m-d H:i:s'))) {
            $this->login();
        }
    }

    protected function setCurlParams($endpoint, $args, $method)
    {
        if ($endpoint != 'authentication') {
            $this->checkSession();
        }
        $endpoint_url = $this->service_url . 'api/2.0/' . $endpoint . '.json' ;

        return ['endpoint_url' => $endpoint_url, 'args' => $args];
    }

    protected function setCurlHeaders($endpoint, $api_request)
    {
        if ($endpoint != 'authentication') {
            $api_request->addHeader('Authorization', $this->auth_token->token);
        }

        return $api_request;
    }


    protected function curl($endpoint, $args = [], $method = 'get')
    {
        try {
            $curl_params = $this->setCurlParams($endpoint, $args, $method);
            $url = $curl_params['endpoint_url'];
            if ($endpoint == 'authentication') {
                $url = $this->buildUrl($url, $args);
            }
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
                $api_request = $this->setCurlHeaders($endpoint, $api_request);
                $api_request->method(\Httpful\Http::POST);
                if ($endpoint != 'authentication') {
                    $api_request = $api_request->body($args);
                }
                $response = $api_request->send();
            }

            if ($method == 'put') {
                $url = $this->buildUrl($url, $args);
                $api_request = ApiRequest::put($url);
                $api_request = $this->setCurlHeaders($endpoint, $api_request);
                $response = $api_request->body($args)
                    ->withoutStrictSsl()
                    ->send();
            }

            if ($method == 'get') {
                $url = $this->buildUrl($url, $args);

                $api_request = ApiRequest::get($url);
                $api_request = $this->setCurlHeaders($endpoint, $api_request);
                $response = $api_request->send();
            }

            if ($method == 'delete') {
                $api_request = ApiRequest::delete($url);
                $api_request = $this->setCurlHeaders($endpoint, $api_request);
                $response = $api_request->send();
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
        } catch (\Throwable $ex) {  exception_log($ex);
            if ($this->debug == 'output') {
            }

            if ($this->debug == 'log') {
                exception_log($ex->getMessage());
                exception_log($ex->getTraceAsString());
            }
        }
    }
}
