<?php declare(strict_types=1);

namespace EduSharingApiClient;

use Exception;
use JsonException;

/**
 * Class EduSharingNodeHelper
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class EduSharingNodeHelper extends EduSharingHelperAbstract
{
    private EduSharingNodeHelperConfig $config;

    public function __construct(EduSharingHelperBase $base, EduSharingNodeHelperConfig $config) {
        parent::__construct($base);
        $this->config = $config;
    }

    /**
     * creates a usage for a given node
     * The given usage can later be used to fetch this node REGARDLESS of the actual user
     * The usage gives permanent access to this node and acts similar to a license
     * In order to be able to create an usage for a node, the current user (provided via the ticket)
     * MUST have CC_PUBLISH permissions on the given node id
     * @param string $ticket
     * A ticket with the user session who is creating this usage
     * @param string $containerId
     * A unique page / course id this usage refers to inside your system (e.g. a database id of the page you include the usage)
     * @param string $resourceId
     * The individual resource id on the current page or course this object refers to
     * (you may enumerate or use unique UUID's)
     * @param string $nodeId
     * The edu-sharing node id the usage shall be created for
     * @param string|null $nodeVersion
     * Optional: The fixed version this usage should refer to
     * If you leave it empty, the usage will always refer to the latest version of the node
     * @return Usage
     * An usage element you can use with @getNodeByUsage
     * Keep all data of this object stored inside your system!
     * @throws JsonException
     * @throws Exception
     */
    public function createUsage(string $ticket, string $containerId, string $resourceId, string $nodeId, ?string $nodeVersion = null): Usage {
        $headers   = $this->getSignatureHeaders($ticket);
        $headers[] = $this->getRESTAuthenticationHeader($ticket);
        $curl      = $this->base->handleCurlRequest($this->base->baseUrl . '/rest/usage/v1/usages/repository/-home-', [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => json_encode([
                'appId'       => $this->base->appId,
                'courseId'    => $containerId,
                'resourceId'  => $resourceId,
                'nodeId'      => $nodeId,
                'nodeVersion' => $nodeVersion,
            ], 512, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        $data = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
        if ((int)$curl->info['http_code'] === 403 || (!empty($data['error']) && $data['message'] === 'NO_CCPUBLISH_PERMISSION')) {
            throw new MissingRightsException("User missing publish rights.");
        }
        if (empty($data['parentNodeId']) || empty($data['nodeId'])) {
            error_log('Creating usage failed for node: ' . $nodeId . '. Returned content: ' . $curl->content);
            throw new Exception('creating usage failed: ' . $nodeId);
        }
        if ($curl->error === 0 && $curl->info['http_code'] ?? 0 === 200 && empty($data['error'])) {
            return new Usage($data['parentNodeId'], $nodeVersion, $containerId, $resourceId, $data['nodeId']);
        }
        throw new Exception('creating usage failed ' . $curl->info['http_code'] . ': ' . $data['error'] . ' ' . $data['message']);
    }

    /**
     * @DEPRECATED
     * Function getUsageIdByParameters
     *
     * Returns the id of an usage object for a given node, container & resource id of that usage
     * This is only relevant for legacy plugins which do not store the usage id and need to fetch it in order to delete an usage
     * @param string $ticket
     * A ticket with the user session who is creating this usage
     * @param string $containerId
     * A unique page / course id this usage refers to inside your system (e.g. a database id of the page you include the usage)
     * @param string $resourceId
     * The individual resource id on the current page or course this object refers to
     * (you may enumerate or use unique UUID's)
     * @return string|null
     * The id of the usage, or NULL if no usage with the given data was found
     * @throws Exception
     */
    public function getUsageIdByParameters(string $ticket, string $nodeId, string $containerId, string $resourceId): ?string {
        $headers   = $this->getSignatureHeaders($ticket);
        $headers[] = $this->getRESTAuthenticationHeader($ticket);
        $curl      = $this->base->handleCurlRequest($this->base->baseUrl . '/rest/usage/v1/usages/node/' . rawurlencode($nodeId), [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        $data      = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
        if ($curl->error === 0 && $curl->info['http_code'] ?? 0 === 200 && isset($data['usages'])) {
            foreach ($data['usages'] as $usage) {
                if ((string)$usage['appId'] === $this->base->appId && (string)$usage['courseId'] === $containerId && (string)$usage['resourceId'] === $resourceId) {
                    return isset($usage['nodeId']) ? (string)$usage['nodeId'] : null;
                }
            }
            return null;
        }
        throw new Exception('fetching usage list for course failed '
            . ($curl->info['http_code'] ?? 'unknown') . ': ' . ($data['error'] ?? 'unknown') . ' ' . ($data['message'] ?? 'unknown'));
    }

    /**
     * Function getSecuredNode
     *
     * retrieves the secured node for rendering via rendering service 2
     * @param string $ticket
     * A ticket with the user session who is creating this usage
     * @param string $nodeId
     * The node id
     * @param string $repoId
     * The repository id
     * @param string $version
     * @return SecuredNode
     * @throws JsonException
     */
    function getSecuredNode(string $ticket, string $nodeId, string $repoId, string $version): SecuredNode {
        $headers   = $this->getSignatureHeaders($ticket);
        $headers[] = $this->getRESTAuthenticationHeader($ticket);
        $url = $this->base->baseUrl . '/rest/node/v1/nodes/' . $repoId . '/' . $nodeId . '/metadata/secured?propertyFilter=-all-';
        if ($version !== '' && $version !== '0' && $version !== '-1') {
            $url .= '&version=' . rawurlencode($version);
        }
        $curl = $this->base->handleCurlRequest($url, [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        $data = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
        if ($curl->error === 0 && $curl->info['http_code'] ?? 0 === 200
            && isset($data['node']) && isset($data['jwt']) && isset($data['signedNode']) && isset($data['signature'])) {
            return new SecuredNode(
                node: $data['node'],
                securedNode: $data['signedNode'],
                jwt: $data['jwt'],
                signature: $data['signature'],
                previewUrl: ''
            );
        }
        throw new Exception('fetching secured node failed '
            . ($curl->info['http_code'] ?? 'unknown') . ': ' . ($data['error'] ?? 'unknown') . ' ' . ($data['message'] ?? 'unknown'));
    }

    /**
     * Function getSecuredNode
     *
     * retrieves the secured node for rendering via rendering service 2
     * If version is specified in usage, the secured node will be fetched for the specified version
     * The latest version will be fetched if version is either null, an empty string, "0" or "-1"
     *
     * @param Usage $usage
     * @return SecuredNode
     * @throws JsonException
     * @throws Exception
     */
    function getSecuredNodeByUsage(Usage $usage): SecuredNode {
        $headers   = $this->getUsageSignatureHeaders($usage);
        $url = $this->base->baseUrl . '/rest/node/v1/nodes/-home-/' . $usage->nodeId . '/metadata/secured';
        if ($usage->nodeVersion !== null && $usage->nodeVersion !== '' && $usage->nodeVersion !== '0' && $usage->nodeVersion !== '-1') {
            $url .= '?version=' . rawurlencode($usage->nodeVersion);
        }
        $curl = $this->base->handleCurlRequest($url, [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        $data = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);

        if ($curl->error === 0 && ($curl->info['http_code'] ?? 0) === 200
            && isset($data['node']) && isset($data['jwt']) && isset($data['signedNode']) && isset($data['signature'])) {
            return new SecuredNode(
                node: $data['node'],
                securedNode: $data['signedNode'],
                jwt: $data['jwt'],
                signature: $data['signature']
            );
        }
        throw new Exception('fetching secured node failed '
            . ($curl->info['http_code'] ?? 'unknown') . ': ' . ($data['error'] ?? 'unknown') . ' ' . ($data['message'] ?? 'unknown'));
    }

    /**
     * Function getNodeByUsageRendering2
     *
     * returns node without detailsSnippet (which is not available when legacy rendering is deactivated)
     * If version is specified in usage, thesecured node will be fetched for the specified version
     * The latest version will be fetched if version is either null, an empty string, "0" or "-1"
     *
     * @param Usage $usage
     * @return CurlResult
     */
    private function getNodeByUsageRendering2(Usage $usage): CurlResult {
        $headers   = $this->getUsageSignatureHeaders($usage);
        $url = $this->base->baseUrl . '/rest/node/v1/nodes/-home-/' . $usage->nodeId . '/metadata';
        if ($usage->nodeVersion !== null && $usage->nodeVersion !== '' && $usage->nodeVersion !== '0' && $usage->nodeVersion !== '-1') {
            $url .= '?version=' . rawurlencode($usage->nodeVersion);
        }
        return $this->base->handleCurlRequest($url, [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
    }

    /**
     * Function getNodeByUsageLegacy
     *
     * calls rendering API and returns node including detailsSnippet
     *
     * @param Usage $usage
     * @param string $displayMode
     * @param array|null $renderingParams
     * @param string|null $userId
     * @return CurlResult
     */
    private function getNodeByUsageLegacy(Usage $usage, string $displayMode = DisplayMode::INLINE, ?array $renderingParams = null, ?string $userId = null): CurlResult {
        $url = $this->base->baseUrl . '/rest/rendering/v1/details/-home-/' . rawurlencode($usage->nodeId);
        $url .= '?displayMode=' . rawurlencode($displayMode);
        if ($usage->nodeVersion) {
            $url .= '&version=' . rawurlencode($usage->nodeVersion);
        }
        $headers = $this->getUsageSignatureHeaders($usage, $userId);
        return $this->base->handleCurlRequest($url, [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => json_encode($renderingParams),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
    }

    /**
     * Function getNodeByUsage
     *
     * Loads the edu-sharing node referred by a given usage
     * @param Usage $usage
     * The usage, as previously returned by @createUsage
     * @param string $displayMode
     * The displayMode
     * This will ONLY change the content representation inside the "detailsSnippet" return value
     * @param array|null $renderingParams
     * @param string|null $userId
     * The userId can be included for tracking and statistics purposes
     * @param bool $rendering2
     * @return array
     * Returns an object containing a "detailsSnippet" representation
     * as well as the full node as provided by the REST API
     * Please refer to the edu-sharing REST documentation for more details
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws Exception
     */
    public function getNodeByUsage(Usage $usage, string $displayMode = DisplayMode::INLINE, ?array $renderingParams = null, ?string $userId = null, bool $rendering2 = false): array {
        if ($rendering2) {
            $curl = $this->getNodeByUsageRendering2($usage);
        } else {
            $curl = $this->getNodeByUsageLegacy($usage, $displayMode, $renderingParams, $userId);
        }
        $data = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
        $this->handleURLMapping($data, $usage);
        if ($curl->error === 0 && (int)($curl->info['http_code'] ?? 0) === 200) {
            return $data;
        }
        if ((int)($curl->info['http_code'] ?? 0) === 403) {
            throw new UsageDeletedException('the given usage is deleted and the requested node is not public');
        } else if ((int)($curl->info['http_code'] ?? 0) === 404) {
            throw new NodeDeletedException('the given node is already deleted ' . $curl->info['http_code'] . ': ' . $data['error'] . ' ' . $data['message']);
        } else {
            throw new Exception('fetching node by usage failed ' . $curl->info['http_code'] . ': ' . $data['error'] . ' ' . $data['message']);
        }
    }

    /**
     * Function deleteUsage
     *
     * Deletes the given usage
     * We trust that you've validated if the current user in your context is allowed to do so
     * There is no restriction in deleting usages even from foreign users, as long as they were generated by your app
     * Thus, this endpoint does not require any user ticket
     * @param string $nodeId
     * The edu-sharing node id this usage belongs to
     * @param string $usageId
     * The usage id
     * @throws UsageDeletedException
     * @throws Exception
     */
    public function deleteUsage(string $nodeId, string $usageId): void {
        $headers = $this->getSignatureHeaders($nodeId . $usageId);
        $curl    = $this->base->handleCurlRequest($this->base->baseUrl . '/rest/usage/v1/usages/node/' . rawurlencode($nodeId) . '/' . rawurlencode($usageId), [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        if ($curl->error === 0 && (int)($curl->info['http_code'] ?? 0) === 200) {
            return;
        }
        if ((int)($curl->info['http_code'] ?? 0) === 404) {
            throw new UsageDeletedException('the given usage is already deleted or does not exist');
        } else {
            throw new Exception('deleting usage failed with curl error ' . $curl->error);
        }
    }

    /**
     * Function handleURLMapping
     *
     * @param $data
     * @param Usage $usage
     */
    private function handleURLMapping(&$data, Usage $usage): void {
        if (!$this->config->urlHandling->enabled) {
            return;
        }
        if (isset($data['node'])) {
            $params = '&usageId=' . urlencode($usage->usageId) . '&nodeId=' . urlencode($usage->nodeId) . '&resourceId=' . urlencode($usage->resourceId) . '&containerId=' . urlencode($usage->containerId);
            if ($usage->nodeVersion) {
                $params .= '&nodeVersion=' . urlencode($usage->nodeVersion);
            }
            $endpointBase           = $this->config->urlHandling->endpointURL . (str_contains($this->config->urlHandling->endpointURL, '?') ? '&' : '?');
            $contentUrl             = $endpointBase . 'mode=content' . $params;
            $data['url']            = [
                'content'  => $contentUrl,
                'download' => $endpointBase . 'mode=download' . $params
            ];
            if (isset($data['detailsSnippet'])) {
                $data['detailsSnippet'] = str_replace('{{{LMS_INLINE_HELPER_SCRIPT}}}', $contentUrl, $data['detailsSnippet']);
                $data['detailsSnippet'] = str_replace('{{{TICKET}}}', '', $data['detailsSnippet']);
            }
        }
    }

    /**
     * Function getRedirectUrl
     *
     * @param string $mode
     * @param Usage $usage
     * @param array $additionalParams
     * Additional query params that shall be passed to the repository url (as key=>value structure)
     * @param string|null $userId
     * The user id. Note: Due to the current behaviour, this userId will currently NOT obeyed for the tracking results
     * of this method, the statistics/tracking when going into the full view will always be anonymous
     * @param bool $rendering2
     * @return string
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws Exception
     */
    public function getRedirectUrl(string $mode, Usage $usage, array $additionalParams = [], ?string $userId = null, bool $rendering2 = false): string {
        $headers = $this->getUsageSignatureHeaders($usage);
        // DisplayMode::PRERENDER is used in order to differentiate for tracking and statistics
        $node = $this->getNodeByUsage($usage, DisplayMode::PRERENDER, null, $userId, $rendering2);
        $params  = '';
        foreach ($headers as $header) {
            if (!str_starts_with($header, 'X-')) {
                continue;
            }
            $header = explode(': ', $header);
            $params .= '&' . $header[0] . '=' . urlencode($header[1]);
        }
        foreach($additionalParams as $key => $value) {
            foreach ($headers as $header) {
                if($header[0] === $key) {
                    continue(2);
                }
            }
            $params .= '&' . $key . '=' . urlencode($value);
        }
        if ($mode === 'content') {
            $url    = $node['node']['content']['url'] ?? '';
            $params .= '&closeOnBack=true';
        } else if ($mode === 'download') {
            $url = $node['node']['downloadUrl'] ?? '';
        } else {
            throw new Exception('Unknown parameter for mode: ' . $mode);
        }
        return $url . (str_contains($url, '?') ? '' : '?') . $params;
    }

    /**
     * Function getUsageSignatureHeaders
     *
     * @param Usage $usage
     * @param string|null $userId
     * @return array
     */
    private function getUsageSignatureHeaders(Usage $usage, ?string $userId = null): array {
        $headers   = $this->getSignatureHeaders($usage->usageId);
        $headers[] = 'X-Edu-Usage-Node-Id: ' . $usage->nodeId;
        $headers[] = 'X-Edu-Usage-Course-Id: ' . $usage->containerId;
        $headers[] = 'X-Edu-Usage-Resource-Id: ' . $usage->resourceId;
        if ($userId !== null) {
            $headers[] = 'X-Edu-User-Id: ' . $userId;
        }
        return $headers;
    }

    private function getPreviewBaseUrl(Usage $usage, PreviewSize $size = PreviewSize::SIZE_400_PX) {
        $sizeParam = '';
        if($size->value > 0) {
            $sizeParam = "&maxWidth=$size->value&maxHeight=$size->value&crop=true";
        }
        $url = $this->base->baseUrl . '/preview?nodeId=' . rawurlencode($usage->nodeId) . $sizeParam;
        if ($usage->nodeVersion) {
            $url .= '&version=' . rawurlencode($usage->nodeVersion);
        }
        return $url;
    }

    /**
     * Function getPreview
     *
     * gets the preview (inlucding potential secured headers) and returns it in $result->content as a binary object
     *
     * @param Usage $usage
     * @param PreviewSize $size
     * @return CurlResult
     */
    public function getPreview(Usage $usage, PreviewSize $size = PreviewSize::SIZE_400_PX): CurlResult {
        $url = $this->getPreviewBaseUrl($usage, $size);
        $headers = $this->getUsageSignatureHeaders($usage);
        return $this->base->handleCurlRequest($url, [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
    }
    /**
     * Function getPreview
     *
     * Same as getPreview, but will return a ready-to-send url to the client including the signed preview url
     * Use this method if you don't want to proxy the preview through your system
     *
     * @param Usage $usage
     * @param PreviewSize $size
     * @return String
     */
    public function getPreviewUrl(Usage $usage, PreviewSize $size = PreviewSize::SIZE_400_PX): String {
        $url = $this->getPreviewBaseUrl($usage, $size);
        $headers = $this->getUsageSignatureHeaders($usage);
        $params = '';
         foreach ($headers as $header) {
            if (!str_starts_with($header, 'X-')) {
                continue;
            }
            $header = explode(': ', $header);
            $params .= '&' . $header[0] . '=' . urlencode($header[1]);
        }
        return $url . $params;
    }
}
