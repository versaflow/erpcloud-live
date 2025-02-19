<?php

class PanaceaApi
{
    public $url = 'http://api.panaceamobile.com/json';

    public $curl = false;

    public $debug = false;

    public $error = null;

    public $username = null;

    public $password = null;

    public $performActionsImmediately = true;

    public $queuedActions = array();

    public function __construct()
    {
        $ver = explode('.', phpversion());
        if (($ver[0] >= 5)) {
            $this->debug('Version OK '.implode('.', $ver));
            if (!function_exists('json_decode') || !function_exists('json_encode')) {
                $this->debug('You need the json_encode and json_decode functions to use this Class, JSON is available in PHP 5.2.0 and up for alternatives please see http://json.org');
                $this->debug('Your PHP version is '.implode('.', $ver).' '.__FILE__);
                die();
            }
        } else {
            $this->debug('You need at least PHP 5 to use this Class '.__FILE__);
            die();
        }
    }

    public function debug($str, $nl = true)
    {
        if ($this->debug) {
            echo $str;
            if ($nl) {
                echo "\n";
            }
        }
    }

    public function call_api($url)
    {
        if (function_exists('curl_init')) {
            if (false === $this->curl) {
                $this->curl = curl_init();
            } else {
                curl_close($this->curl);
                $this->curl = curl_init();
            }
            curl_setopt($this->curl, CURLOPT_HEADER, 1);
            curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Connection: keep-alive'));

            $result = curl_exec($this->curl);

            $size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
            $request_headers = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
            $response_headers = substr($result, 0, $size);
            $result = substr($result, $size);

            $this->debug('--- HTTP Request trace --- ');
            $this->debug($request_headers, false);
            $this->debug($response_headers, false);
            $this->debug($result);

            if (false !== $result) {
                return json_decode($result, true);
            }

            return false;
        } else {
            $this->debug('You need cURL to use this API Library');
        }

        return false;
    }

    public function call_api_action($method, $params, $authenticate = true)
    {
        if ($this->performActionsImmediately) {
            $url = $this->url.'?action='.urlencode($method->getName());
            if ($authenticate) {
                if (!is_null($this->password) && !is_null($this->username)) {
                    $url .= '&username='.urlencode($this->username);
                    $url .= '&password='.urlencode($this->password);
                } else {
                    $this->debug('You need to specify your username and password using setUsername() and setPassword()');

                    return false;
                }
            }
            $parameters = $method->getParameters();
            for ($i = 0; $i < count($params); ++$i) {
                if (!is_null($params[$i])) {
                    $url .= '&'.urlencode($parameters[$i]->getName()).'='.urlencode($params[$i]);
                }
            }

            return $this->call_api($url);
        } else {
            if (is_null($this->username) || is_null($this->password)) {
                $this->debug('You need to specify your username and password using setUsername() and setPassword() to perform bulk actions');

                return false;
            }
            $action = array(
                'command' => $method->getName(),
                'params' => array(),
            );

            $parameters = $method->getParameters();
            for ($i = 0; $i < count($params); ++$i) {
                $action['params'][$parameters[$i]->getName()] = $params[$i];
            }

            $this->queuedActions[] = $action;

            return true;
        }
    }

    public function execute_multiple()
    {
        if (function_exists('curl_init')) {
            if (false === $this->curl) {
                $this->curl = curl_init();
            } else {
                curl_close($this->curl);
                $this->curl = curl_init();
            }

            $url = $this->url.'?action=execute_multiple';
            $url .= '&username='.urlencode($this->username);
            $url .= '&password='.urlencode($this->password);

            curl_setopt($this->curl, CURLOPT_HEADER, 1);
            curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'data='.urlencode(json_encode($this->queuedActions)));
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Connection: keep-alive'));

            $result = curl_exec($this->curl);

            $size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
            $request_headers = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
            $response_headers = substr($result, 0, $size);
            $result = substr($result, $size);

            $this->debug('--- HTTP Request trace --- ');
            $this->debug($request_headers, false);
            $this->debug("\n");
            $this->debug($response_headers, false);
            $this->debug($result);

            if (false !== $result) {
                return json_decode($result, true);
            }

            return false;
        } else {
            $this->debug('You need cURL to use this API Library');
        }

        return false;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function performActionsImmediately($state)
    {
        $this->performActionsImmediately = $state;
    }

    public function message_send($to, $text, $from = null, $report_mask = 19, $report_url = null, $charset = null, $data_coding = null, $message_class = -1, $auto_detect_encoding = null)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function message_status($message_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function messages_get($last_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function user_get_balance()
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function batches_list()
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function batch_start($batch_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function batch_stop($batch_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function batch_check_status($batch_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function address_book_groups_get_list()
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function address_book_group_add($name)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function address_book_group_delete($group_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function address_book_contacts_get_list($group_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function address_book_contact_add($group_id, $phone_number, $first_name = null, $last_name = null)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function address_book_contact_delete($contact_id)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function address_book_contact_update($contact_id, $phone_number = null, $first_name = null, $last_name = null)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function user_authorize_application($application_name, $icon_url = null, $return_url = null)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args, false);
    }

    public function user_get_api_key($request_key)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args, false);
    }

    public function route_check_price($to)
    {
        $args = func_get_args();

        return $this->call_api_action(new ReflectionMethod(__CLASS__, __FUNCTION__), $args);
    }

    public function batch_create($name, $file, $throughput = 0, $filter = false, $file_type = 'csv', $start_time = null)
    {
        /* This function has special requirements in terms of streaming raw data, hence it calls the API directly */
        if (function_exists('curl_init')) {
            if (false === $this->curl) {
                $this->curl = curl_init();
            } else {
                curl_close($this->curl);
                $this->curl = curl_init();
            }

            if (!file_exists($file)) {
                $this->debug("File {$file} does not exist");

                return false;
            }

            if ('zip' != $file_type) {
                $data = 'data='.urlencode(file_get_contents($file));
            } else {
                $data = 'data='.urlencode(base64_encode(file_get_contents($file)));
            }

            $url = $this->url.'?action=batch_create';
            $url .= '&username='.urlencode($this->username);
            $url .= '&password='.urlencode($this->password);
            $url .= '&name='.urlencode($name);
            $url .= '&file_type='.urlencode($file_type);
            $url .= '&filter='.($filter ? 'true' : 'false');
            $url .= '&throughput='.$throughput;

            if (!is_null($start_time)) {
                $url .= '&start_time='.urlencode($start_time);
            }

            curl_setopt($this->curl, CURLOPT_HEADER, 1);
            curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Connection: keep-alive'));
            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

            $result = curl_exec($this->curl);

            $size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
            $request_headers = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
            $response_headers = substr($result, 0, $size);
            $result = substr($result, $size);

            $this->debug('--- HTTP Request trace --- ');
            $this->debug($request_headers, false);
            $this->debug($response_headers, false);
            $this->debug($result);

            if (false !== $result) {
                return json_decode($result, true);
            }

            return false;
        } else {
            $this->debug('You need cURL to use this API Library');
        }

        return false;
    }

    public function ok($result)
    {
        if (is_array($result)) {
            if (array_key_exists('status', $result)) {
                if ($result['status'] >= 0) {
                    return true;
                }
                $this->error = $result['message'];
            }
        } else {
            if (true === $result) {
                $this->error = 'Command queued';

                return true;
            }
            $this->error = 'Error communicating with API';
        }

        return false;
    }

    public function getError()
    {
        return $this->error;
    }
}
