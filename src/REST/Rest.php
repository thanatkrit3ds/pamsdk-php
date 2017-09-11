<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     MIT http://opensource.org/licenses/MIT
 */

namespace PAM\Sdk\REST;

use Mautic\QueryBuilder\QueryBuilder;
use Mautic\Auth\ApiAuth;
use Mautic\Auth\AuthInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PAM\Sdk\Http\HttpCookie;
use PAM\Sdk\DI;

/**
 * Base Rest class
 */
class Rest implements LoggerAwareInterface
{
    /**
     * Common endpoint for this REST
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Name of the array element where the list of items is
     *
     * @var string
     */
    protected $listName;

    /**
     * Name of the array element where the item data is
     *
     * @var string
     */
    protected $itemName;

    /**
     * Base URL for REST endpoints
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * @var ApiAuth
     */
    private $auth;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AuthInterface $auth
     * @param string        $baseUrl
     */
    public function __construct(AuthInterface $auth, $baseUrl = '')
    {
        $this->auth = $auth;
        $this->setBaseUrl($baseUrl);
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        // If a logger hasn't been set, use NullLogger
        if (!($this->logger instanceof LoggerInterface)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Set the base URL for REST endpoints
     *
     * @param string $url
     *
     * @return $this
     */
    public function setBaseUrl($url)
    {
        if (substr($url, -1) != '/') {
            $url .= '/';
        }

        if (substr($url,-4,4) != 'rest/') {
            $url .= 'rest/';
        }

        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Make the REST request
     *
     * @param        $endpoint
     * @param array  $parameters
     * @param string $method
     *
     * @return array
     * @throws \Exception
     */
    public function makeRequest($endpoint, array $parameters = array(), $method = 'GET')
    {
        $response = array();

        $url = $this->baseUrl.$endpoint;

        if (strpos($url, 'http') === false) {
            $error = array(
                'code'    => 500,
                'message' => sprintf(
                    'URL is incomplete.  Please use %s, set the base URL as the third argument to $MauticApi->newApi(), or make $endpoint a complete URL.',
                    __CLASS__.'setBaseUrl()'
                )
            );
        } else {
            try {
                $settings = [];
                $settings['headers'] = [
                    $this->createCookiesHttpHeader()
                ];

                $response = $this->auth->makeRequest($url, $parameters, $method, $settings);

                $this->getLogger()->debug('REST Response', array('response' => $response));

                if (!is_array($response)) {
                    $this->getLogger()->warning($response);

                    //assume an error
                    $error = array(
                        'code'    => 500,
                        'message' => $response
                    );
                }
            } catch (\Exception $e) {
                $this->getLogger()->error('Failed connecting to Mautic REST: '.$e->getMessage(), array('trace' => $e->getTraceAsString()));

                $error = array(
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage()
                );
            }
        }

        if (!empty($error)) {
            return array(
                'errors' => array($error)
            );
        } elseif (!empty($response['errors'])) {
            $this->getLogger()->error('Mautic REST returned errors: '.var_export($response['errors'], true));
        }

        // Ensure a code is present in the error array
        if (!empty($response['errors'])) {
            $info = $this->auth->getResponseInfo();
            foreach ($response['errors'] as $key => $error) {
                if (!isset($response['errors'][$key]['code'])) {
                    $response['errors'][$key]['code'] = $info['http_code'];
                }
            }
        }

        return $response;
    }

    /**
     * Returns HTTP Cookies
     *
     * @return array
     */
    private function createCookiesHttpHeader()
    {
        /**
         * @var $httpCookie \PAM\Http\HttpCookie
         */
        $httpCookie = DI::getInstance()->getService(DI::SERVICEID_HTTPCOOKIE);
        $allCookies = $httpCookie->getAll();
        $cookiesString = http_build_query($allCookies);
        return "Cookie: ".$cookiesString;
    }

    /**
     * Returns HTTP response info
     *
     * @return array
     */
    public function getResponseInfo()
    {
        return $this->auth->getResponseInfo();
    }
}
