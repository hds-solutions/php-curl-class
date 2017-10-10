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

        /**
         * Custom headers
         * @var array
         */
        private $headers = [];

        private $data;
        private $data_type;

        public function __construct($parent, $url, $req_type = 'GET', $data = null, $data_type = 'url') {
            // save parent relation
            $this->parent = $parent;
            // create a new curl resource
            $this->resource = curl_init();
            // curl resource config
            $this->configure($url);
            // set request type
            $this->setRequestType($req_type);
            // save data & data_type
            $this->data = $data;
            $this->data_type = $data_type;
        }

        public function addHeader($key, $value) {
            //
            $this->headers[] = "$key: $value";
        }

        public function exec() {
            // append data
            $this->setData();

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
            // enable Proxy
            if ($this->parent->isProxyEnabled()) {
                curl_setopt($this->resource, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                curl_setopt($this->resource, CURLOPT_PROXY, $this->parent->getProxy());
            }
            // enable SSL Verify
            if (!$this->parent->isSslVerifyEnabled()) {
                curl_setopt($this->resource, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($this->resource, CURLOPT_SSL_VERIFYHOST, false);
            }
            // enable Cookies
            if ($this->parent->getCookiesJar() !== null) {
                curl_setopt($this->resource, CURLOPT_COOKIEJAR,  $this->parent->getCookiesJar());
                curl_setopt($this->resource, CURLOPT_COOKIEFILE, $this->parent->getCookiesJar());
            }
        }

        private function setRequestType($req_type) {
            // save request type
            $this->request_type = $req_type;

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

        private function setData() {
            // check if we have POST data
            if ($this->data !== null) {
                if (in_array($this->request_type, [ 'GET', 'DELETE' ])) {
                    // append data to base URL
                    $this->res_url .= (parse_url($this->res_url, PHP_URL_QUERY) === null ? '?' : '&') . http_build_query($this->data);
                    // update request URL
                    curl_setopt($this->resource, CURLOPT_URL, $this->res_url);
                } else {
                    // FIX: POST|PUT without body
                    $this->data = $this->data === null ? (object)[ '__ALLOW_POST_PUT_WITHOUT_BODY' => true ] : $this->data;
                    // append data to POST fields
                    switch ($this->data_type) {
                        case 'url':
                            curl_setopt($this->resource, CURLOPT_HTTPHEADER, array_merge($this->headers, [
                                    'Content-Type: application/x-www-form-urlencoded',
                                    'Content-Length: '.strlen(http_build_query($this->data))
                                ]));
                            curl_setopt($this->resource, CURLOPT_POSTFIELDS, http_build_query($this->data));
                            break;
                        case 'json':
                            curl_setopt($this->resource, CURLOPT_HTTPHEADER, array_merge($this->headers, [
                                    'Content-Type: application/json',
                                    'Content-Length: '.strlen(json_encode($this->data))
                                ]));
                            curl_setopt($this->resource, CURLOPT_POSTFIELDS, json_encode($this->data));
                            break;
                        default:
                            throw new Exception("Unsupported or Invalid data type: \"{$this->data_type}\"");
                            break;
                    }
                }
            }
        }
    }