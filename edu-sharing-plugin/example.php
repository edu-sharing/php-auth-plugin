<?php
/**
 * This is a sample file on how to use the edu-sharing authentication library
 * Run this script for the first time to create a private/public keypair
 * On first run, a properties.xml file will be created
 * Upload this file to your target edu-sharing (Admin-Tools -> Remote Systems -> Choose XML-File)
 */

if(count($argv) !== 2) {
    die('This script excepts exactly one command line argument: The full URL to your edu-sharing backend, e.g. "http://localhost:8080/edu-sharing"');
}
define('APP_ID', 'sample-app');
require_once "edu-sharing-helper.php";
require_once "edu-sharing-auth-helper.php";

$privatekey = @file_get_contents('private.key');
if(!$privatekey) {
    $key = EduSharingHelper::generateKeyPair();
    // store the $key data inside your application, e.g. your database or plugin config
    file_put_contents(APP_ID . '.properties.xml', EduSharingHelper::generateEduAppXMLData(APP_ID, $key['publickey']));
    file_put_contents('private.key', $key['privatekey']);
    die('Wrote ' . APP_ID . '.properties.xml file. Upload it to edu-sharing, then run the script again');
} else {
    $key["privatekey"] = $privatekey;
}

$authHelper = new EduSharingAuthHelper($argv[1], $key["privatekey"], APP_ID);
$ticket = $authHelper->getTicketForUser("tester");
print_r($authHelper->getTicketAuthenticationInfo($ticket));