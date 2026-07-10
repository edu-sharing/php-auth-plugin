<?php declare(strict_types=1);

namespace EduSharingApiClient;

use EduSharingApiClient\AboutApiCacheHandler;

/**
 * Class DefaultAboutApiCacheHandler
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class DefaultAboutApiCacheHandler implements AboutApiCacheHandler
{
    private EduSharingHelperBase $base;

    public function __construct(EduSharingHelperBase $base) {
        $this->base = $base;
    }

    /**
     * @throws \JsonException
     */
    public function getAboutApiCache(): array {
        return $this->base->getAbout();
    }
}
