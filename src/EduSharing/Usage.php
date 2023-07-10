<?php declare(strict_types = 1);

namespace EduSharing;

/**
 * Class Usage
 *
 * DTO class for usages
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 **/
class Usage
{
    public string $nodeId;
    public string|null $nodeVersion;
    public string $containerId;
    public string $resourceId;
    public string $usageId;

    /**
     * Usage constructor
     *
     * @param string $nodeId
     * @param string|null $nodeVersion
     * @param string $containerId
     * @param string $resourceId
     * @param string $usageId
     */
    public function __construct(string $nodeId, ?string $nodeVersion, string $containerId, string $resourceId, string $usageId) {
        $this->nodeId      = $nodeId;
        $this->nodeVersion = $nodeVersion;
        $this->containerId = $containerId;
        $this->resourceId  = $resourceId;
        $this->usageId     = $usageId;
    }
}
