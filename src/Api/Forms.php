<?php
namespace PAM\Sdk\Api;

use Mautic\Api\Forms as MauticForms;

class Forms extends MauticForms{

    public function submit($formId, $parameters){
        $parameters = array_merge(['formId'=>$formId], $parameters);
        return $this->makeRequest($this->endpoint.'/'.$formId.'/submit', $parameters, 'POST');
    }
}