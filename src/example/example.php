<?php
/**
 * This is a sample file on how to use the edu-sharing remote library
 * Run this script for the first time to create a private/public keypair
 * On first run, a properties.xml file will be created
 * Upload this file to your target edu-sharing (Admin-Tools -> Remote Systems -> Choose XML-File)
 */
namespace EduSharingApiClientExample;
use EduSharingApiClient;

require_once __DIR__ . '/../vendor/autoload.php';

const APP_ID = 'sample-app';
const USERNAME = 'tester';

$privatekey = @file_get_contents(__DIR__ . '/data/private.key');
if(!$privatekey) {
    $key = \EduSharingApiClient\EduSharingHelper::generateKeyPair();
    // store the $key data inside your application, e.g. your database or plugin config
    file_put_contents(__DIR__ . '/data/' . APP_ID . '.properties.xml', EduSharingHelper::generateEduAppXMLData(APP_ID, $key['publickey']));
    file_put_contents(__DIR__ . '/data/private.key', $key['privatekey']);
    die('Wrote ' . APP_ID . '.properties.xml file. Upload it to edu-sharing, then run this script again');
} else {
    $key['privatekey'] = $privatekey;
}
if(count($argv) < 2) {
    die('This script should be called as follow: "example.php http://localhost:8080/edu-sharing [<node-id>]"');
}
// init the base class instance we use for all helpers
$base = new \EduSharingApiClient\EduSharingHelperBase($argv[1], $key['privatekey'], APP_ID);
$base->setLanguage('de');
$base->verifyCompatibility();

// authenticating (getting a ticket) and validating the given ticket
$authHelper = new EduSharingApiClient\EduSharingAuthHelper($base);
$ticket = $authHelper->getTicketForUser(USERNAME);
echo "Ticket validation result:\n";
print_r($authHelper->getTicketAuthenticationInfo($ticket));

if(count($argv) !== 3) {
    die('No node id given. Add a 3rd parameter to test create + fetching of nodes by usage');
}
$nodeHelper = new \EduSharingApiClient\EduSharingNodeHelper($base, new \EduSharingApiClient\EduSharingNodeHelperConfig(new \EduSharingApiClient\UrlHandling(false)));
$containerId = rand(1000,9999);
$resourceId = rand(1000,9999);
$usage = $nodeHelper->createUsage($ticket, $containerId, $resourceId, $argv[2]);
echo "\nUsage create result:\n";
print_r($usage);
echo "\nGet usage id by parameters:\n";
$usageId = $nodeHelper->getUsageIdByParameters($ticket, $argv[2], $containerId, $resourceId);
print_r($usageId);
$node = $nodeHelper->getNodeByUsage($usage);
echo "\nGet node by usage:\n";
print_r($node['node']['name']);

echo "\nDeleting usage.\n";
$nodeHelper->deleteUsage($argv[2], $usage->usageId);