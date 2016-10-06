<?php
    namespace net\hdssolutions\php\net;
    
    final class Request {
        /**
         * [$parent description]
         * @var [type]
         */
        private $parent;

        /**
         * Local cURL resource
         * @var curl
         */
        private $resource;
        private $res_url = null;

        /**
         * Server response
         * @var mixed
         */
        private $response = null;

        public function __construct($parent, $url, $req_type = 'GET', $data = null, $data_type = 'url') {
            // save parent relation
            $this->parent = $parent;
            // create a new curl resource
            $this->resource = curl_init();
            // curl resource config
            $this->configure($url);
            // set request type
            $this->setRequestType($req_type);
            // set data
            $this->setData($req_type, $data, $data_type);
        }

        public function exec() {
            // execute request
            $this->response = curl_exec($this->resource);

            // check for errors
            if (curl_error($this->resource) !== '')
                // return false
                return false;

            // close curl resource
            curl_close($this->resource);

            // empty local attributes
            $this->resource = null;
            $this->res_url = null;

            // return true for success  
            return true;
        }

        public function getResponse() {
            // return server response
            return $this->response;
        }

        public function getError() {
            // check open resource
            if ($this->resource === null) return false;
            // return error
            return curl_error($this->resource);
        }

        public function getErrno() {
            // check open resource
            if ($this->resource === null) return false;
            // return error
            return curl_errno($this->resource);
        }

        private function configure($url) {
            // save base url for local use
            $this->res_url = $url;
            // URL destino
            curl_setopt($this->resource, CURLOPT_URL, $this->res_url);
            // Timeouts
            curl_setopt($this->resource, CURLOPT_CONNECTTIMEOUT, $this->parent->getConnectTimeout());
            curl_setopt($this->resource, CURLOPT_TIMEOUT, $this->parent->getTimeout());
            // force data return
            curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, true);
            // enable HTTP Auth
            if ($this->parent->isHttpAuthEnabled()) {
                curl_setopt($this->resource, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($this->resource, CURLOPT_USERPWD, $this->parent->getHttpAuth());
            }
            // enable SSL Verify
            if (!$this->parent->isSslVerifyEnabled()) {
                curl_setopt($this->resource, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($this->resource, CURLOPT_SSL_VERIFYHOST, false);
            }
            // enable Cookies
            if ($this->parent->getCookiesJar() !== null) {
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

        private function setData($req_type, $data, $data_type) {
            // check if we have POST data
            if ($data !== null) {
                if (in_array($req_type, [ 'GET', 'DELETE' ])) {
                    // append data to base URL
                    $this->res_url .= (parse_url($this->res_url, PHP_URL_QUERY) === null ? '?' : '&') . http_build_query($data);
                    // update request URL
                    curl_setopt($this->resource, CURLOPT_URL, $this->res_url);
                } else
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