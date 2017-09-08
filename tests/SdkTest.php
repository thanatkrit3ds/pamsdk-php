<?php

require_once('vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use PAM\Sdk;
use PAM\DI;
use PAM\Helpers\Encryptor;

class SdkTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public $sdk;

    public function __construct(){
        $pamUrl = 'https://pam-tesco.com';
        $username = '3ds';
        $password = 'interactive';
        $appId = '1978544d7488415980feeb56b1312a2a';
        $appSecret = 'def0000081ffc5d04b7e61894e7dc8bb6e4ba104f875c35185cac64526c96358bc3a5de1d4198d34a994d7c28ef9dec47120325aaf28c0c7ab7b79984f8adcc5b5014fa5';

        $this->sdk = new Sdk($pamUrl, $username, $password, $appId, $appSecret);
    }

    public function testCallRESTTracking_GivenCorrectApiKeyAndSecret_ExpectAllInformationHasBeenDelivered() {
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
            $assertPosts = $posts['app_id'] == '1978544d7488415980feeb56b1312a2a';
            $actualHash =  $posts['updfh'];

            $encryptor = new Encryptor();
            $secret = 'def0000081ffc5d04b7e61894e7dc8bb6e4ba104f875c35185cac64526c96358bc3a5de1d4198d34a994d7c28ef9dec47120325aaf28c0c7ab7b79984f8adcc5b5014fa5';
            $actualTrackingData = $encryptor->decryptArray($actualHash, $secret);

            $expectTrackingData = [
                'linemid'=>'123456789',
                'clubcard'=>'634009100131474921',
                'mobilenumber'=>'0999999999',
                'email'=>'chaiyapong@3dsinteractive.com',
                'content-tags' => 'abc,def',
                'timestamp' => '9999999999'
            ];

            //Compare 2 assoc array
            $isEqual = count($expectTrackingData) == count(array_intersect_assoc($expectTrackingData, $actualTrackingData));
            $assertPosts &= $isEqual;

            $headers = $args[CURLOPT_HTTPHEADER];
            $assertHeaders =
                in_array('Accept: application/json', $headers) &&
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

        //Setup Tracking data
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

        $result = $sdk->callRESTTracking($mockTrackingData);

        $mockApiResultArray = json_decode($mockApiResult, true);
        $this->assertEquals($mockApiResultArray, $result);
    }

    public function testFormSubmit_GivenUserNamePasswordCookiesAndPostVars_ExpectAllInformationHasBeenDelivered(){
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
            $username = '3ds';
            $password = 'interactive';
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

    public function testCreateTags_GivenTagsWithSpace_ExpectSpaceHasReplacedWithDash(){
        $sdk = $this->sdk;
        $tags = ['abc', 'def', 'ghi jkl'];
        $expectedTags = 'abc,def,ghi-jkl';
        $actualTags = $sdk->createTags($tags);
        $this->assertEquals($expectedTags, $actualTags);
    }

    public function testCreateTrackingScript_ExpectUpdatableFieldHasBeenCorrectlyEncrypted(){
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

        $appId = '1978544d7488415980feeb56b1312a2a';
        $secret = 'def0000081ffc5d04b7e61894e7dc8bb6e4ba104f875c35185cac64526c96358bc3a5de1d4198d34a994d7c28ef9dec47120325aaf28c0c7ab7b79984f8adcc5b5014fa5';
        $scriptUrl = 'https://pam-tesco.com/mtc.js';

        $actualScript = $sdk->createTrackingScript($mockTrackingData);

        //Get only actual hash part of script to test convert back to object (because the hash is not the same even we use the same value and secret key)
        $actualHash = str_replace("<script>(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)})(window,document,'script','$scriptUrl','mt');mt('send', 'pageview', {'app_id':'$appId','updfh':'", '', $actualScript);
        $actualHash = str_replace("'});</script>", '', $actualHash);

        $encryptor = new Encryptor();
        $actualTrackingData = $encryptor->decryptArray($actualHash, $secret);

        $this->assertEquals($cleanTrackingData, $actualTrackingData);
    }

    public function testCreateTrackingScript_GivenWrongDecryptionKey_ExpectExceptionHasThrown(){
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

        $actualScript = $sdk->createTrackingScript($mockTrackingData);

        $appId = '1978544d7488415980feeb56b1312a2a';
        $scriptUrl = 'https://pam-tesco.com/mtc.js';
        //Get only actual hash part of script to test convert back to object (because the hash is not the same even we use the same value and secret key)
        $actualHash = str_replace("<script>(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)})(window,document,'script','$scriptUrl','mt');mt('send', 'pageview', {'app_id':'$appId','updfh':'", '', $actualScript);
        $actualHash = str_replace("'});</script>", '', $actualHash);

        $encryptor = new Encryptor();
        $wrongSecret = 'def00000a15d3df86b460c23a55e45f6109114788743c29e76284b807371ac81b1d601ff0ad82009608bbeb1a280367f0e331a39882f5dc16c41683ad711e7299318236d';

        $this->expectException('Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException');
        $encryptor->decryptArray($actualHash, $wrongSecret);
    }

    public function tearDown() {
        Mockery::close();
    }
}