<?php namespace PAM\Sdk\Helpers;

use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

class Encryptor {

    public function encryptArray(array $array, $secret) {
        $json = json_encode($array);
        return $this->encryptText($json, $secret);
    }

    public function decryptArray($encryptedArrayString, $secret) {
        $json = $this->decryptText($encryptedArrayString, $secret);
        return json_decode($json, true);
    }

    private function encryptText($plainText, $secret) {
        $key = Key::loadFromAsciiSafeString($secret);
        return Crypto::encrypt($plainText, $key);
    }

    private function decryptText($encryptedText, $secret) {
        $key = Key::loadFromAsciiSafeString($secret);
        return Crypto::decrypt($encryptedText, $key);
    }
}