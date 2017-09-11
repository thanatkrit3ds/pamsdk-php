<?php
namespace PAM\Sdk\REST;

class Event extends Rest{

    /**
     * {@inheritdoc}
     */
    protected $endpoint = 'event';

    public function sendEvent(array $parameters){

        return $this->makeRequest($this->endpoint, $parameters, 'POST');
    }
}