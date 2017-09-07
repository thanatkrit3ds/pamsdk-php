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

    private $pamBaseUrl;
    private $username;
    private $password;
    private $appId;
    private $appSecret;

    public function __construct($pamBaseUrl, $username, $password, $appId='', $appSecret=''){
        $this->pamBaseUrl = $pamBaseUrl;
        $this->username = $username;
        $this->password = $password;
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function submitForm($formId, $parameters) {
        $api = new Forms($this->createAuth(), $this->pamBaseUrl);
        $response = $api->submit($formId, $parameters);
        return $response;
    }

    public function callRESTTracking(array $parameters) {
        
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

        $scriptUrl = $this->pamBaseUrl.'/mtc.js';
        $appId = $this->appId;
        $appSecret = $this->appSecret;

        /** @var \PAM\Helpers\TimeHelper $timeHelper */
        $timeHelper = DI::getInstance()->getService(DI::SERVICEID_TIMEHELPER);
        $parameters['timestamp'] = $timeHelper->now();

        $parameters = $this->removeNullAndEmptyValueFromArray($parameters);

        /** @var \PAM\Helpers\Encryptor $encryptor */
        $encryptor = DI::getInstance()->getService(DI::SERVICEID_ENCRYPTOR);
        $fieldsHash = $encryptor->encryptArray($parameters, $appSecret);

        return "<script>(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)})(window,document,'script','$scriptUrl','mt');mt('send', 'pageview', {'app_id':'$appId','updfh':'$fieldsHash'});</script>";
    }

    public function createTrackingToken(array $parameters) {

        $appId = $this->appId;
        $appSecret = $this->appSecret;

        /** @var \PAM\Helpers\TimeHelper $timeHelper */
        $timeHelper = DI::getInstance()->getService(DI::SERVICEID_TIMEHELPER);
        $parameters['timestamp'] = $timeHelper->now();

        $parameters = $this->removeNullAndEmptyValueFromArray($parameters);

        /** @var \PAM\Helpers\Encryptor $encryptor */
        $encryptor = DI::getInstance()->getService(DI::SERVICEID_ENCRYPTOR);
        $fieldsHash = $encryptor->encryptArray($parameters, $appSecret);

        return ['updfh'=>$fieldsHash, 'pamserver'=>$this->pamBaseUrl];
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

        $settings = $settings = [
            'userName'=>$this->username,
            'password'=>$this->password
        ];

        $initAuth = new ApiAuth();
        $auth = $initAuth->newAuth($settings, 'BasicAuth');
        return $auth;
    }
}