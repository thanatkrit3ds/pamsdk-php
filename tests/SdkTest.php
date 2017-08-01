<?php

require_once('vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use PAM\Sdk;
use PAM\DI;
use PAM\Config;
use PAM\Helpers\Encryptor;

class SdkTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public $sdk;

    public function __construct(){
        $this->sdk = new Sdk();
    }

    public function testFormSubmit(){
        $sdk = $this->sdk;

        //Mock Cookies
        $expectedCookies = [
            'mtc_id' => '2',
            'hello' => 'world'
        ];
        $mockCookies = Mockery::mock('\PAM\Http\HttpCookie');
        $mockCookies->shouldReceive('getAll')->once()->andReturn($expectedCookies)->once();
        $expectedCookiesString = 'mtc_id=2&hello=world';

        //Mock Request Endpoint
        $mockHttp = Mockery::mock('\PAM\Http\HttpRequest');
        $mockHttp->shouldReceive('init')->once();
        $mockHttp->shouldReceive('setOptions')->once()->with(Mockery::on(function($args) use ($expectedCookiesString) {

            //Assert Post Vars
            $posts = [];
            parse_str($args[CURLOPT_POSTFIELDS], $posts);
            $assertPosts =
                $posts['email'] == 'chaiyapong@3dsinteractive.com' &&
                $posts['gender'] == 1 &&
                $posts['age'] == 10;

            //Assert Headers
            /** @var \PAM\Config $config */
            $config = DI::getInstance()->getService(DI::SERVICEID_CONFIG);
            $username = $config->get('username');
            $password = $config->get('password');
            $expectedAuthHeader = 'Authorization: Basic ' . base64_encode($username.':'.$password);

            $headers = $args[CURLOPT_HTTPHEADER];
            $assertHeaders =
                    in_array('Accept: application/json', $headers) &&
                    in_array($expectedAuthHeader, $headers) &&
                    in_array('Cookie: '.$expectedCookiesString, $headers);

            return $assertPosts && $assertHeaders;
        }));
        $mockApiResult = '{"email":"chaiyapong@3dsinteractive.com"}';
        $mockHttp->shouldReceive('execute')->once()->andReturn($mockApiResult);
        $mockHttp->shouldReceive('getInfo')->once();
        $mockHttp->shouldReceive('close')->once();

        //Setup DI.
        $di = DI::getInstance();
        $di->registerService(DI::SERVICEID_HTTPREQUEST, function() use ($mockHttp){
            return $mockHttp;
        });
        $di->registerService(DI::SERVICEID_HTTPCOOKIE, function() use ($mockCookies){
            return $mockCookies;
        });

        $result = $sdk->submitForm('1', [
            'email'=>'chaiyapong@3dsinteractive.com',
            'gender'=>1,
            'age'=>10
        ]);

        $mockApiResultArray = json_decode($mockApiResult, true);
        $this->assertEquals($mockApiResultArray, $result);
    }

    public function testCreateTags(){
        $sdk = $this->sdk;
        $tags = ['abc', 'def', 'ghi jkl'];
        $expectedTags = 'abc,def,ghi-jkl';
        $actualTags = $sdk->createTags($tags);
        $this->assertEquals($expectedTags, $actualTags);
    }

    public function testCreateTrackingScript(){
        $sdk = $this->sdk;

        $mockTrackingData = [
            'linemid'=>'123456789',
            'clubcard'=>'634009100131474921',
            'mobilenumber'=>'0999999999',
            'email'=>'chaiyapong@3dsinteractive.com',
            'content-tags' => $sdk->createTags(['abc', 'def']),
            'appmid'=> null,
            'webmid'=> '  ', //empty string with more space
            'fbuid'=> ''
        ];

        $mockTimestamp = 9999999999;
        $mockTimeHelper = Mockery::mock('\PAM\Helpers\TimeHelper');
        $mockTimeHelper->shouldReceive('now')->andReturn($mockTimestamp);
        DI::getInstance()->registerService(DI::SERVICEID_TIMEHELPER, function() use($mockTimeHelper) {
            return $mockTimeHelper;
        });

        $cleanTrackingData = [
            'linemid'=>'123456789',
            'clubcard'=>'634009100131474921',
            'mobilenumber'=>'0999999999',
            'email'=>'chaiyapong@3dsinteractive.com',
            'content-tags' => 'abc,def',
            'timestamp' => '9999999999'
        ];

        /** @var \PAM\Config $config */
        $config = DI::getInstance()->getService(DI::SERVICEID_CONFIG);
        $appId = $config->get('appId');
        $secret = $config->get('appSecret');
        $scriptUrl = $config->get('pamBaseUrl').'/mtc.js';

        $actualScript = $sdk->createTrackingScript($mockTrackingData);

        //Get only actual hash part of script to test convert back to object (because the hash is not the same even we use the same value and secret key)
        $actualHash = str_replace("<script>(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)})(window,document,'script','$scriptUrl','mt');mt('send', 'pageview', {'app_id':'$appId','updfh':'", '', $actualScript);
        $actualHash = str_replace("'});</script>", '', $actualHash);

        $encryptor = new Encryptor();
        $actualTrackingData = $encryptor->decryptArray($actualHash, $secret);

        $this->assertEquals($cleanTrackingData, $actualTrackingData);
    }

    private function printResult($result){
        echo "\n".json_encode($result, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    public function tearDown() {
        Mockery::close();
    }
}