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
    public function createUsage(string $ticket, string $containerId, string $resourceId, string $nodeId, string $nodeVersion = null): Usage {
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
        $data      = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
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
     * @return array
     * Returns an object containing a "detailsSnippet" representation
     * as well as the full node as provided by the REST API
     * Please refer to the edu-sharing REST documentation for more details
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     */
    public function getNodeByUsage(Usage $usage, string $displayMode = DisplayMode::INLINE, ?array $renderingParams = null, ?string $userId = null): array {
        $url = $this->base->baseUrl . '/rest/rendering/v1/details/-home-/' . rawurlencode($usage->nodeId);
        $url .= '?displayMode=' . rawurlencode($displayMode);
        if ($usage->nodeVersion !== null) {
            $url .= '&version=' . rawurlencode($usage->nodeVersion);
        }
        $headers = $this->getUsageSignatureHeaders($usage, $userId);
        error_log(json_encode($headers));
        $curl    = $this->base->handleCurlRequest($url, [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => json_encode($renderingParams),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        $data    = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
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
            if ($usage->nodeVersion !== null) {
                $params .= '&nodeVersion=' . urlencode($usage->nodeVersion);
            }
            $endpointBase           = $this->config->urlHandling->endpointURL . (str_contains($this->config->urlHandling->endpointURL, '?') ? '&' : '?');
            $contentUrl             = $endpointBase . 'mode=content' . $params;
            $data['url']            = [
                'content'  => $contentUrl,
                'download' => $endpointBase . 'mode=download' . $params
            ];
            $data['detailsSnippet'] = str_replace('{{{LMS_INLINE_HELPER_SCRIPT}}}', $contentUrl, $data['detailsSnippet']);
        }
    }

    /**
     * Function getRedirectUrl
     *
     * @param string $mode
     * @param Usage $usage
     * @param string|null $userId
     * @return string
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws Exception
     */
    public function getRedirectUrl(string $mode, Usage $usage, ?string $userId = null): string {
        $headers = $this->getUsageSignatureHeaders($usage);
        // DisplayMode::PRERENDER is used in order to differentiate for tracking and statistics
        $node    = $this->getNodeByUsage($usage, DisplayMode::PRERENDER, $userId);
        $params  = '';
        foreach ($headers as $header) {
            if (!str_starts_with($header, 'X-')) {
                continue;
            }
            $header = explode(': ', $header);
            $params .= '&' . $header[0] . '=' . urlencode($header[1]);
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

    /**
     * Function getPreview
     *
     * @param Usage $usage
     * @return CurlResult
     */
    public function getPreview(Usage $usage): CurlResult {
        $url = $this->base->baseUrl . '/preview?nodeId=' . rawurlencode($usage->nodeId) . '&maxWidth=400&maxHeight=400&crop=true';
        if ($usage->nodeVersion !== null) {
            $url .= '&version=' . rawurlencode($usage->nodeVersion);
        }
        $headers = $this->getUsageSignatureHeaders($usage);
        return $this->base->handleCurlRequest($url, [
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
    }
}
