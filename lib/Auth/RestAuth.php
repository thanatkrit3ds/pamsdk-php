<?php

namespace Mautic\Auth;

use Mautic\Exception\RequiredParameterMissingException;

/**
 * REST Authentication Client
 */
class RestAuth extends AbstractAuth
{
    /**
     * Api Key receive from PAM owner
     *
     * @var string
     */
    private $apiKey;

    /**
     * Api Secret receive from PAM owner
     *
     * @var string
     */
    private $apiSecret;

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return (!empty($this->apiKey) && !empty($this->apiSecret));
    }

    /**
     * @param string $apiKey              The Api Key to use for Authentication *Required*
     * @param string $apiSecret           The Api Secret to use                    *Required*
     *
     * @throws RequiredParameterMissingException
     */
    public function setup($apiKey, $apiSecret) {
        // we MUST have the api key and secret. No Blanks allowed!
        //
        // remove blanks else Empty doesn't work
        $apiKey = trim($apiKey);
        $apiSecret = trim($apiSecret);

        if (empty($apiKey) || empty($apiSecret)) {
            //Throw exception if the required parameters were not found
            $this->log('parameters did not include api key and/or api secret');
            throw new RequiredParameterMissingException('One or more required parameters was not supplied. Both api key and api secret required!');
        }

        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * @param       $url
     * @param array $headers
     * @param array $parameters
     * @param       $method
     * @param array $settings
     *
     * @return array
     */
    protected function prepareRequest($url, array $headers, array $parameters, $method, array $settings)
    {
        return array($headers, $parameters);
    }
}
