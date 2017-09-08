<?php namespace PAM\Http;

class HttpServer {
    public function get($key) {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }
}