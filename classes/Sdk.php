<?php namespace PAM;

use Curl\Curl;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Josantonius\Cookie\Cookie;
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;
use Mautic\Api\Emails;
use PAM\Api\Forms;

class Sdk {

    public function submitForm($formId, $parameters) {
        $api = new Forms($this->createAuth(), $this->getBaseUrl());
        $response = $api->submit($formId, $parameters);
        return $response;
    }

    public function createTags(array $tags) {
        if($tags == null || count($tags) == 0) {
            return null;
        }
        $stringBuilder = '';
        for($i=0; $i<count($tags); $i++) {
            if($i == 0) {
                $stringBuilder .= str_replace(' ', '-', $tags[$i]);
            } else {
                $stringBuilder .= ','.str_replace(' ', '-', $tags[$i]);
            }
        }
        return $stringBuilder;
    }

    public function createTrackingScript(array $parameters) {
        /** @var \PAM\Config $config */
        $config = DI::getInstance()->getService(DI::SERVICEID_CONFIG);

        $scriptUrl = $config->get('pamBaseUrl').'/mtc.js';
        $appId = $config->get('appId');
        $appSecret = $config->get('appSecret');

        /** @var \PAM\Helpers\TimeHelper $timeHelper */
        $timeHelper = DI::getInstance()->getService(DI::SERVICEID_TIMEHELPER);
        $parameters['timestamp'] = $timeHelper->now();

        $parameters = $this->removeNullAndEmptyValueFromArray($parameters);

        /** @var \PAM\Helpers\Encryptor $encryptor */
        $encryptor = DI::getInstance()->getService(DI::SERVICEID_ENCRYPTOR);
        $fieldsHash = $encryptor->encryptArray($parameters, $appSecret);

        return "<script>(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)})(window,document,'script','$scriptUrl','mt');mt('send', 'pageview', {'app_id':'$appId','updfh':'$fieldsHash'});</script>";
    }

    private function removeNullAndEmptyValueFromArray(array $assocArray) {
        $newArray = [];
        foreach($assocArray as $key => $value) {
            if($value == null) {
                continue;
            }
            $value = trim($value);
            if(strlen($value) == 0){
                continue;
            }

            $newArray[$key] = $value;
        }

        return $newArray;
    }

    private function createAuth(){
        /** @var \PAM\Config $config */
        $config = DI::getInstance()->getService(DI::SERVICEID_CONFIG);
        $settings = array(
            'userName'=>$config->get('username'),
            'password'=>$config->get('password')
        );

        $initAuth = new ApiAuth();
        $auth = $initAuth->newAuth($settings, 'BasicAuth');
        return $auth;
    }

    private function getBaseUrl() {
        /** @var \PAM\Config $config */
        $config = DI::getInstance()->getService(DI::SERVICEID_CONFIG);
        $baseUrl = $config->get('pamBaseUrl');
        return $baseUrl;
    }
}