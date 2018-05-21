<?php
    namespace net\hdssolutions\php\net;

    use CurlFile;

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
         * [$headers description]
         * @var array
         */
        private $headers = [];

        /**
         * [$request_headers description]
         * @var array
         */
        private $request_headers = [];

        /**
         * [$files description]
         * @var array
         */
        private $files = [];

        /**
         * Server response
         * @var mixed
         */
        private $response = null;

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

        public function exec() {
            // append data
            $this->setData();

            //
            $headers = [];
            //
            foreach ($this->request_headers as $key => $value)
                //
                $headers[] = "$key: $value";
            // add request headers
            curl_setopt($this->resource, CURLOPT_HTTPHEADER, $headers);

            // execute request
            $this->response = curl_exec($this->resource);

            // parse headers
            $hsize = curl_getinfo($this->resource, CURLINFO_HEADER_SIZE);
            $headers = array_map('trim', explode("\n", substr($this->response, 0, $hsize)));
            foreach ($headers as $header) {
                //
                if (strlen($header) == 0) continue;
                //
                if (!strpos($header, ':')) {
                    //
                    $this->headers[] = $header;
                    //
                    continue;
                }
                //
                list($key, $value) = explode(':', $header, 2);
                //
                $this->headers[$key] = trim($value);
            }

            // save response body
            $this->response = substr($this->response, $hsize);

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

        public function addHeader($key, $value) {
            //
            $this->request_headers[$key] = $value;
        }

        public function addFile($field, $file = null) {
            //
            if ($file === null) {
                // save file
                $file = $field;
                // replace field name
                $field = 'files';
            }
            //
            if (!isset($this->files[$field])) $this->files[$field] = [];
            //
            $this->files[$field][] = $file;
        }

        public function getHeaders() {
            //
            return $this->headers;
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
            // force headers return
            curl_setopt($this->resource, CURLOPT_HEADER, true);
            // user agent
            curl_setopt($this->resource, CURLOPT_USERAGENT, $this->parent->getUserAgent());
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
            // check if we have POST or Files data
            if ($this->data !== null || (count($this->files) > 0 && $this->data_type == 'file')) {
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
                            //
                            $this->request_headers = array_merge($this->request_headers, [
                                    'Content-Type: application/x-www-form-urlencoded',
                                    'Content-Length: '.strlen(http_build_query($this->data))
                                ]);
                            curl_setopt($this->resource, CURLOPT_POSTFIELDS, http_build_query($this->data));
                            break;
                        case 'json':
                            //
                            $this->request_headers = array_merge($this->request_headers, [
                                    'Content-Type: application/json',
                                    'Content-Length: '.strlen(json_encode($this->data))
                                ]);
                            curl_setopt($this->resource, CURLOPT_POSTFIELDS, json_encode($this->data));
                            break;
                        case 'file':
                            //
                            $this->request_headers = array_merge($this->request_headers, [
                                    'Content-Type: application/x-www-form-urlencoded'
                                ]);
                            // append files
                            $post = [];
                            foreach ($this->files as $field => $files)
                                foreach ($files as $key => $file)
                                    $post[$field . (count($files) > 1 ? "[$key]" : '')] = new CurlFile($file);
                            // append data
                            foreach ($this->data as $key => $value)
                                $post[$key] = $value;
                            curl_setopt($this->resource, CURLOPT_POSTFIELDS, $post);
                            break;
                        default:
                            throw new Exception("Unsupported or Invalid data type: \"{$this->data_type}\"");
                            break;
                    }
                }
            }
        }
    }