<?php declare(strict_types=1);

namespace EduSharingApiClient;

class SecuredNode
{
    public array  $node;
    public string $securedNode;
    public string $jwt;
    public string $signature;
    public string $previewUrl;

    public function __construct(
        array $node,
        string $securedNode,
        string $jwt,
        string $signature,
        string $previewUrl = ''
    ) {
        $this->jwt         = $jwt;
        $this->securedNode = $securedNode;
        $this->node        = $node;
        $this->signature   = $signature;
        $this->previewUrl  = $previewUrl;
    }
}
