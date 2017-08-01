# PamSdk-PHP
Clien SDK to access PAM

## Requirements

 * PHP 5.6.0+

## Installation

 1. Include the library via Composer

    ```
    $ composer require pushandmotion/pamsdk-php:dev-master
    ```

 1. Include the Composer autoloader:

    ```php
    require __DIR__ . '/vendor/autoload.php';
    ```

## Usage

 1. Create PAM Script from your backend code and echo it into HTML before the </body> tag
 
    ```php
    $baseUrl = 'https://<your-pam-website>.com';
    $username = '<your-username>';
    $password = '<your-password>';
    $appId = '<your-app-id>';
    $secret = '<your-app-secret>';
    
    $sdk = new \PAM\Sdk($baseUrl, $username, $password, $appId, $secret);
    $pamScript = $sdk->createTrackingScript(
        [
            'linemid' => '12345',
            'mobilephone' => '0899999999',
            'email' => 'hello@world.com',
            'content-tags' => $sdk->createTags(['tag1','tag2','tag3'])
        ]);
    ```
  
 1. After install the script in HTML page then verify the script by inspecting the network request from your browser when load page; you will see the POST request /event call with JSON response id and sid
 
 ![Screen-shot of page-view event post request](/screenshots/inspect-event.png?raw=true "Screen-shot of page-view event post request")