<?php
const APP_ID = 'sample-app';
define('BASE_URL_INTERNAL', getenv('BASE_URL_INTERNAL'));
define('BASE_URL_EXTERNAL', getenv('BASE_URL_EXTERNAL'));
const USERNAME = 'tester';


header('Accept: application/json');
header('Content-Type: application/json');

require_once '../edu-sharing-helper.php';
require_once '../edu-sharing-helper-base.php';
require_once '../edu-sharing-auth-helper.php';
require_once '../edu-sharing-node-helper.php';

$privatekey = @file_get_contents('private.key');
if(!$privatekey) {
    die('no private key');
} else {
    $key['privatekey'] = $privatekey;
}
$xml = file_get_contents(BASE_URL_INTERNAL . '/metadata?format=lms');
$xml = new SimpleXMLElement($xml);
foreach($xml->entry as $entry) {
    if($entry->attributes()['key'] == 'public_key') {
        $repositoryPublicKey = (string)$entry;
    }
}
// init the base class instance we use for all helpers
$base = new EduSharingHelperBase(BASE_URL_INTERNAL, $key['privatekey'], $repositoryPublicKey, APP_ID);
$nodeHelper = new EduSharingNodeHelper($base,
    new EduSharingNodeHelperConfig(
        new UrlHandling(true, 'example-api.php?action=REDIRECT')
    )
);
$postData = json_decode(file_get_contents('php://input'));
if($postData) {
    $action = $postData->action;
} else {
    $action = $_GET['action'];
}
$result = null;
try {
    $base->verifyCompatibility();
    if ($action === 'BASE_URL') {
        $result = BASE_URL_EXTERNAL;
    } else if ($action === 'GET_NODE') {
        $result = $nodeHelper->getNodeByUsage(
            new Usage(
                $postData->nodeId,
                $postData->nodeVersion,
                $postData->containerId,
                $postData->resourceId,
                $postData->usageId
            )
        );
    } else if ($action === 'REDIRECT') {
        // in a real application, you should check if the user is actually allowed to access this usage!
        $url = $nodeHelper->getRedirectUrl(
            USERNAME,
            $_GET['mode'],
            new Usage(
                $_GET['nodeId'],
                $_GET['nodeVersion'] ?? null,
                $_GET['containerId'],
                $_GET['resourceId'],
                $_GET['usageId'],
            )
        );
        header("Location: $url");
    } else if ($action === 'CREATE_USAGE') {
        $result = $nodeHelper->createUsage(
            $postData->ticket,
            $postData->containerId,
            $postData->resourceId,
            $postData->nodeId
        );
    } else if ($action === 'DELETE_USAGE') {
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
}catch(UsageDeletedException $e) {
    http_response_code(404);
    echo $e->getMessage();
}catch(NodeDeletedException $e) {
    http_response_code(404);
    echo $e->getMessage();
}catch(AppAuthException $e) {
    http_response_code(401);
    echo $e->getMessage();
}catch(Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}


