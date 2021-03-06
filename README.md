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

### Install PAM script (Track page view)

 1. Create PAM Script from your backend code and echo it into HTML just before the ```</body>``` tag
 
    ```php
    $baseUrl = 'https://<your-pam-website>.com';
    $username = '<your-username>';
    $password = '<your-password>';
    $appId = '<your-app-id>';
    $secret = '<your-app-secret>';
    
    $sdk = new \PAM\Sdk($baseUrl, $username, $password, $appId, $secret);
    $pamScript = $sdk->createTrackingScript(
        [
            'field-1' => 'value-1',
            'field-2' => 'value-2',
            'field-N' => 'value-N',
            'content-tags' => $sdk->createTags(['content-tag1','content-tag2'])
        ]);
    ```
    
 1. If you use some kind of MVC framework so in your view should have something like this (the actual code will depends on your framework)
 
    ```html
    <html>
    <body>
        <h1>Hello, PAM</h1>
    
        {!! $pamScript !!}
    </body>
    </html>
    ```
  
 1. After install the script in HTML page then verify the script by inspecting the network request from your browser when load page; you will see the POST request /event call with JSON response id and sid
 
     ![Screen-shot of page-view event post request](/screenshots/inspect-event.png?raw=true "Screen-shot of page-view event post request")
     
     
### Make Form Submit

When you receive form submission from your website, you can forward those form data to PAM by calling submitForm method

```php 
$result = $sdk->submitForm(
            '1', //formId must match the formId received from PAM backend
            [
                'param-1' => 'value-1',
                'param-2' => 'value-2',
                ...
                'param-N' => 'value-N'
            ]);
```