<?php
    namespace net\hdssolutions\php\net;
    
    final class Curl {
        /**
         * HTTP Auth
         */
        private $ha_enabled = false;
        private $ha_user = null;
        private $ha_pass = null;

        /**
         * SSL
         */
        private $ssl_enabled = true;

        /**
         * Timeouts
         */
        private $to_connect = 5;
        private $to_timeout = 60;

        /**
         * Cookies
         */
        private $cookies_jar = null;

        /**
         * Curl resource
         */
        private $resource = null;
        private $res_url = null;

        public function __construct() {

        }

        public function setHttpAuth($user, $pass) {
            // enable HTTP Auth
            $this->ha_enabled = true;
            // save auth data
            $this->ha_user = $user;
            $this->ha_pass = $pass;
        }

        public function enableHttpAuth($enable = true) {
            // enable HTTP Auth
            $this->ha_enabled = $enable;
        }

        public function enableSslVerify($enable = true) {
            // enable SSL verify
            $this->ssl_enabled = $enable;
        }

        public function setTimeout($timeout, $connect = 5) {
            // save timeouts
            $this->to_connect = $connect;
            $this->to_timeout = $timeout;
        }

        public function setCookiesJar($cookies_jar) {
            // save cookies jar file
            $this->cookies_jar = $cookies_jar;
        }

        public function get($url, $data = null) {
            // return GET request
            return $this->request($url, 'GET', $data);
        }

        public function post($url, $data = null, $data_type = 'url') {
            // return POST request
            return $this->request($url, 'POST', $data, $data_type);
        }

        public function put($url, $data = null, $data_type = 'url') {
            // return PUT request
            return $this->request($url, 'PUT', $data, $data_type);
        }

        public function delete($url, $data = null) {
            // return DELETE request
            return $this->request($url, 'DELETE', $data);
        }

        public function getError() {
            //
            if ($this->resource === null) return false;
            // return error
            return curl_error($this->resource);
        }

        public function getErrno() {
            //
            if ($this->resource === null) return false;
            // return error
            return curl_errno($this->resource);
        }

        private function request($url, $req_type = 'GET', $data = null, $data_type = 'url') {
            try {
                // create a new curl resource
                $this->resource = curl_init();
                // curl resource config
                $this->configure($url);
                // set request type
                $this->setRequestType($req_type);
                // set data
                $this->setData($req_type, $data, $data_type);
                // execute
                $response = curl_exec($this->resource);

                // check for errors
                if (curl_error($this->resource) !== '')
                    // return false
                    return false;

                // close curl resource
                curl_close($this->resource);

                // empty local attributes
                $this->resource = null;
                $this->res_url = null;

                // return response
                return $response;
            } catch (Exception $e) {
                return false;
            }
        }

        private function configure($url) {
            // save base url for local use
            $this->res_url = $url;
            // URL destino
            curl_setopt($this->resource, CURLOPT_URL, $url);
            // Timeouts
            curl_setopt($this->resource, CURLOPT_CONNECTTIMEOUT, $this->to_connect);
            curl_setopt($this->resource, CURLOPT_TIMEOUT, $this->to_timeout);
            //
            curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, true);
            // HTTP Auth
            if ($this->ha_enabled === true) {
                curl_setopt($this->resource, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($this->resource, CURLOPT_USERPWD, $this->ha_user . ':' . $this->ha_pass);
            }
            // SSL
            if ($this->ssl_enabled === false) {
                curl_setopt($this->resource, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($this->resource, CURLOPT_SSL_VERIFYHOST, false);
            }
            // Cookies
            if ($this->cookies_jar !== null) {
                curl_setopt($this->resource, CURLOPT_COOKIEJAR,  WS_COOKIES);
                curl_setopt($this->resource, CURLOPT_COOKIEFILE, WS_COOKIES);
            }
        }

        private function setRequestType($req_type) {
            // Request type
            switch ($req_type) {
                case 'GET':
                    break;
                case 'POST':
                    curl_setopt($this->resource, CURLOPT_POST, true);
                    break;
                case 'PUT':
                    curl_setopt($this->resource, CURLOPT_POST, true);
                    curl_setopt($this->resource, CURLOPT_CUSTOMREQUEST, 'PUT');
                    break;
                case 'DELETE':
                    curl_setopt($this->resource, CURLOPT_POST, true);
                    curl_setopt($this->resource, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
                default:
                    throw new Exception("Unsupported or Invalid request type: \"${req_type}\"");
                    break;
            }
        }

        private setData($req_type, $data, $data_type) {
            // check if we have POST data
            if ($data !== null) {
                if (in_array($req_type, [ 'GET', 'DELETE' ]))
                    // append data to base URL
                    $this->res_url .= (parse_url($this->res_url, PHP_URL_QUERY) === null ? '?' : '&') . http_build_query($data);
                else
                    // append data to POST fields
                    switch ($data_type) {
                        case 'url':
                            curl_setopt($this->resource, CURLOPT_POSTFIELDS, http_build_query($data));
                            break;
                        case 'json':
                            curl_setopt($this->resource, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
                            curl_setopt($this->resource, CURLOPT_POSTFIELDS, json_encode($data));
                            break;
                        default:
                            throw new Exception("Unsupported or Invalid data type: \"${data_type}\"");
                            break;
                    }
            }
        }
    }