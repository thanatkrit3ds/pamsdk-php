<?php namespace PAM;

class Config {

    public function get($name){
        $configs = include('Config.php');
        return $configs[$name];
    }
}