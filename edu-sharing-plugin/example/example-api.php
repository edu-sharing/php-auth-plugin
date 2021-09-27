<?php
define('APP_ID', 'sample-app');
define('BASE_URL', 'http://localhost:8080/edu-sharing');
define('USERNAME', 'tester');


header('Accept: application/json');
header('Content-Type: application/json');

require_once "../edu-sharing-helper.php";
require_once "../edu-sharing-helper-base.php";
require_once "../edu-sharing-auth-helper.php";
require_once "../edu-sharing-node-helper.php";

$privatekey = @file_get_contents('private.key');
if(!$privatekey) {
    die('no private key');
} else {
    $key["privatekey"] = $privatekey;
}
// init the base class instance we use for all helpers
$base = new EduSharingHelperBase(BASE_URL, $key["privatekey"], APP_ID);
$postData = json_decode(file_get_contents('php://input'));
$action = $postData->action;
$result = null;
if ($action === 'BASE_URL') {
    $result = BASE_URL;
} else if ($action === 'GET_NODE') {
        $nodeHelper = new EduSharingNodeHelper($base);
        $result = $nodeHelper->getNodeByUsage(
            new Usage(
                $postData->nodeId,
                $postData->nodeVersion,
                $postData->containerId,
                $postData->resourceId,
                $postData->usageId
            )
        );
} else if ($action === 'CREATE_USAGE') {
    $nodeHelper = new EduSharingNodeHelper($base);
    $result = $nodeHelper->createUsage(
        $postData->ticket,
        $postData->containerId,
        $postData->resourceId,
        $postData->nodeId
    );
} else if ($action === 'DELETE_USAGE') {
    $nodeHelper = new EduSharingNodeHelper($base);
    $nodeHelper->deleteUsage(
        $postData->nodeId,
        $postData->usageId
    );
} else if ($action === 'TICKET') {
    $authHelper = new EduSharingAuthHelper($base);
    $ticket = $authHelper->getTicketForUser(USERNAME);
    $result = $ticket;
}

echo json_encode($result);