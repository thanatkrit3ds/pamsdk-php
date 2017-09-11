<?php namespace PAM\Sdk\Http;

class HttpCookie {

    public function get($key) {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
    }

    public function set($key, $value, $time = 1825) {
        setcookie($key, $value, time() + (86400 * $time), '/');
    }

    public function getAll() {
        return isset($_COOKIE) ? $_COOKIE : null;
    }

    public function delete($key) {
        if (isset($_COOKIE[$key])) {

            setcookie($key, '', time() - 3600, '/');

            return;
        }
    }
}