<?php

namespace EduSharing;

class Usage
{
    public string $nodeId;
    public string|null $nodeVersion;
    public string $containerId;
    public string $resourceId;
    public string $usageId;

    public function __construct($nodeId, $nodeVersion, $containerId, $resourceId, $usageId)
    {
        $this->nodeId = $nodeId;
        $this->nodeVersion = $nodeVersion;
        $this->containerId = $containerId;
        $this->resourceId = $resourceId;
        $this->usageId = $usageId;
    }

}