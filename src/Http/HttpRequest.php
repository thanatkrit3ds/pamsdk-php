<?php namespace PAM\Sdk\Http;

class HttpRequest {

    private $handle = null;

    public function init($url=null){
        if($url != null){
            $this->handle = curl_init($url);
        }else{
            $this->handle = curl_init();
        }
    }

    public function setOptions(array $values) {
        curl_setopt_array($this->handle, $values);
    }

    public function setOption($name, $value) {
        curl_setopt($this->handle, $name, $value);
    }

    public function execute() {
        return curl_exec($this->handle);
    }

    public function getInfo($name=null) {
        if($name != null){
            return curl_getinfo($this->handle, $name);
        }else{
            return curl_getinfo($this->handle);
        }
    }

    public function close() {
        curl_close($this->handle);
    }

    public static function createFile($filename, $mimetype = '', $postname = '') {
        if (!function_exists('curl_file_create')) {
            // For PHP < 5.5
            return "@$filename;filename="
            .($postname ?: basename($filename))
            .($mimetype ? ";type=$mimetype" : '');
        }

        // For PHP >= 5.5
        return curl_file_create($filename, $mimetype, $postname);
    }
}