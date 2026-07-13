<?php
namespace EduSharingApiClient;

require_once __DIR__ . '/../vendor/autoload.php';
define('APP_ID', getenv('APP_ID') ?? 'sample-app');
define('BASE_URL_INTERNAL', getenv('BASE_URL_INTERNAL'));
define('BASE_URL_EXTERNAL', getenv('BASE_URL_EXTERNAL'));
const USERNAME = 'tester';


header('Accept: application/json');
header('Content-Type: application/json');

$privatekey = @file_get_contents('data/private.key');
if(!$privatekey) {
    die('no private key');
} else {
    $key['privatekey'] = $privatekey;
}
// init the base class instance we use for all helpers
$base = new EduSharingHelperBase(BASE_URL_INTERNAL, $key['privatekey'], APP_ID);
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
        $result = [
            "repository" => BASE_URL_EXTERNAL,
            "rendering" => $base->getRenderingServiceUrl()
        ];
    } else if ($action === 'GET_JWT') {
        // in a real application, you should check if the user is actually allowed to access this usage!
        $result = $nodeHelper->getSecuredNodeByUsage(
            new Usage(
                $postData->nodeId,
                $postData->nodeVersion,
                $postData->containerId,
                $postData->resourceId,
                $postData->usageId
            )
        );
    } else if ($action === 'GET_NODE') {
        // in a real application, you should check if the user is actually allowed to access this usage!
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
        $useRendering2 = false;
        try {
            $about = $nodeHelper->base->getAboutCached();
            $useRendering2 =  isset ($about['renderingService2']['url']);
        } catch (\Exception $e) {
            error_log("Exception: " . $e->getMessage());
        }
        $url = $nodeHelper->getRedirectUrl(
            mode: $_GET['mode'],
            usage: new Usage(
                $_GET['nodeId'],
                $_GET['nodeVersion'] ?? null,
                $_GET['containerId'],
                $_GET['resourceId'],
                $_GET['usageId'],
            ),
            rendering2: $useRendering2,
        );
        header("Location: $url");
    }  else if ($action === 'PREVIEW') {
        // in a real application, you should check if the user is actually allowed to access this usage!
        $data = $nodeHelper->getPreview(
            new Usage(
                $_GET['nodeId'],
                $_GET['nodeVersion'] ?? null,
                $_GET['containerId'],
                $_GET['resourceId'],
                $_GET['usageId'],
            )
        );
        header("Content-Type: image/*");
        echo $data->content;
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
        $authHelper = new \EduSharingApiClient\EduSharingAuthHelper($base);
        $ticket = $authHelper->getTicketForUser(USERNAME);
        $result = $ticket;
    }
    echo json_encode($result);
}catch(UsageDeletedException|NodeDeletedException $e) {
    http_response_code(404);
    echo $e->getMessage();
} catch(AppAuthException $e) {
    http_response_code(401);
    echo $e->getMessage();
}catch(\Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}


