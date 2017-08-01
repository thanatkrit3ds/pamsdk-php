<?php namespace PAM;

use Mcustiel\DependencyInjection\DependencyInjectionService;
use PAM\Helpers\Encryptor;
use PAM\Helpers\TimeHelper;
use PAM\Http\HttpCookie;
use PAM\Http\HttpRequest;

class DI {

    const SERVICEID_ENCRYPTOR = 'pamsdk.encryptor';
    const SERVICEID_HTTPREQUEST = 'pamsdk.httprequest';
    const SERVICEID_HTTPCOOKIE = 'pamsdk.httpcookie';
    const SERVICEID_TIMEHELPER = 'pamsdk.timehelper';

    private $container;

    private function __construct(){
        $this->container = new DependencyInjectionService();
        $this->registerDefaultServices();
    }

    private function registerDefaultServices(){

        $this->registerService(self::SERVICEID_ENCRYPTOR, function() {
            return new Encryptor();
        });
        $this->registerService(self::SERVICEID_HTTPREQUEST, function() {
            return new HttpRequest();
        });
        $this->registerService(self::SERVICEID_HTTPCOOKIE, function() {
            return new HttpCookie();
        });
        $this->registerService(self::SERVICEID_TIMEHELPER, function() {
            return new TimeHelper();
        });
    }

    private static $diInstance;

    public static function getInstance(){
        if(self::$diInstance == null){
            self::$diInstance = new DI();
        }

        return self::$diInstance;
    }

    public function registerService($serviceId, $serviceFactoryHandler, $singleton = true){
        $this->container->register($serviceId, $serviceFactoryHandler, $singleton);
    }

    public function getService($serviceId){
        return $this->container->get($serviceId);
    }
}

