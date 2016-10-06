<?php
    namespace net\hdssolutions\php\net;

    require_once __DIR__.'/Request.class.php';
    use net\hdssolutions\php\net\Request;
    
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

        public function setHttpAuth($user, $pass) {
            // enable HTTP Auth
            $this->ha_enabled = true;
            // save auth data
            $this->ha_user = $user;
            $this->ha_pass = $pass;
        }

        function getHttpAuth() {
            //
            return $this->ha_user . ':' . $this->ha_pass;
        }

        public function enableHttpAuth($enable = true) {
            // enable HTTP Auth
            $this->ha_enabled = $enable === true;
        }

        function isHttpAuthEnabled() {
            //
            return $this->ha_enabled;
        }

        public function enableSslVerify($enable = true) {
            // enable SSL verify
            $this->ssl_enabled = $enable === true;
        }

        function isSslVerifyEnabled() {
            //
            return $this->ssl_enabled;
        }

        public function setTimeout($timeout, $connect = 5) {
            // save timeouts
            $this->to_connect = $connect;
            $this->to_timeout = $timeout;
        }

        function getTimeout() {
            //
            return $this->to_timeout;
        }

        function getConnectTimeout() {
            //
            return $this->to_connect;
        }

        public function setCookiesJar($cookies_jar) {
            // save cookies jar file
            $this->cookies_jar = $cookies_jar;
        }

        function getCookiesJar() {
            //
            retrun $this->cookies_jar;
        }

        public function get($url, $data = null) {
            // return GET request
            return new Request($this, $url, 'GET', $data);
        }

        public function post($url, $data = null, $data_type = 'url') {
            // return POST request
            return new Request($this, $url, 'POST', $data, $data_type);
        }

        public function put($url, $data = null, $data_type = 'url') {
            // return PUT request
            return new Request($this, $url, 'PUT', $data, $data_type);
        }

        public function delete($url, $data = null) {
            // return DELETE request
            return new Request($this, $url, 'DELETE', $data);
        }
    }