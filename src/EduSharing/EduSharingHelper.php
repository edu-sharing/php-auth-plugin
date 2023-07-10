<?php declare(strict_types = 1);
namespace EduSharing;

use Exception;

/**
 * Class EduSharingHelper
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class EduSharingHelper {
    /**
     * Function generateKeyPair
     *
     * generate a new key pair (private + public) to be registered in the edu-sharing repository
     * Store the data somewhere in your application, e.g. database
     * use the public key returned to register the application in edu-sharing
     * NOTE: This function will fail on windows-based systems!
     * @throws Exception
     */
    public static function generateKeyPair(): array {
        $res = openssl_pkey_new();
        !$res && throw new Exception('No result from openssl_pkey_new. Please check your php installation');
        openssl_pkey_export($res, $privateKey);
        $publicKeyData = openssl_pkey_get_details($res);
        $publicKey = $publicKeyData['key'];
        return [
            'privatekey' => $privateKey,
            'publickey'  => $publicKey
        ];
    }

    /**
     * Function generateEduAppXMLData
     *
     * Generates an edu-sharing compatible xml file for registering the application
     * This is a very basic function and is only intended for demonstration or manual use. Data is not escaped!
     */
    public static function generateEduAppXMLData(string $appId, string $publicKey, string $type = 'LMS', string $publicIP = '*'): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
                <!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd">
                <properties>
                    <entry key="appid">' . $appId . '</entry>
                    <entry key="public_key">' . $publicKey . '</entry>
                    <entry key="type">' . $type . '</entry>
                    <entry key="domain"></entry>
                    <!-- in case of wildcard host: Replace this, if possible, with the public ip from your service --> 
                    <entry key ="host">' . $publicIP . '</entry>
                    <!-- must be true -->
                    <entry key="trustedclient">true</entry> 
                </properties>
                ';
    }
}

