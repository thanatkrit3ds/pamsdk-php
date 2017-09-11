<?php namespace PAM\Sdk;

use Curl\Curl;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Josantonius\Cookie\Cookie;
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;
use Mautic\Api\Emails;
use PAM\Sdk\Api\Forms;
use PAM\Sdk\Http\HttpRequest;
use PAM\Sdk\Http\HttpServer;
use PAM\Sdk\REST\Event;

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

    public function callRESTTracking(array $identityParameters, $pageTitle, $pageUrl=null, $pageReferrer=null, $pageLanguage=null, $timezoneOffset=-420, $platform='api') {

        $appId = $this->appId;
        $appSecret = $this->appSecret;

        /** @var \PAM\Helpers\TimeHelper $timeHelper */
        $timeHelper = DI::getInstance()->getService(DI::SERVICEID_TIMEHELPER);
        $identityParameters['timestamp'] = $timeHelper->now();

        $identityParameters = $this->removeNullAndEmptyValueFromArray($identityParameters);

        /** @var \PAM\Helpers\Encryptor $encryptor */
        $encryptor = DI::getInstance()->getService(DI::SERVICEID_ENCRYPTOR);
        $fieldsHash = $encryptor->encryptArray($identityParameters, $appSecret);

        $api = new Event($this->createRestAuth(), $this->pamBaseUrl);

        /** @var HttpServer $server */
        $server = DI::getInstance()->getService(DI::SERVICEID_HTTPSERVER);
        if($pageUrl == null){
            $pageUrl = $server->get('HTTP_REFERER');
        }

        $parameters = ['app_id'=>$appId,'updfh'=>$fieldsHash];
        $parameters['page_title'] = $pageTitle;
        $parameters['page_url'] = $pageUrl;
        if($pageReferrer != null){
            $parameters['page_referrer'] = $pageReferrer;
        }
        if($pageLanguage != null){
            $parameters['page_language'] = $pageLanguage;
        }
        if($timezoneOffset != null){
            $parameters['timezone_offset'] = $timezoneOffset;
        }
        if($platform != null){
            $parameters['platform'] = $platform;
        }
        $response = $api->sendEvent($parameters);

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

    private function createRestAuth(){

        $settings = $settings = [
            'apiKey'=>$this->appId,
            'apiSecret'=>$this->appSecret
        ];

        $initAuth = new ApiAuth();
        return $initAuth->newAuth($settings, 'RestAuth');
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