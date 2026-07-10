<?php declare(strict_types=1);

namespace EduSharingApiClient;

/**
 * Interface AboutApiCacheHandler
 *
 * Interface describes the handling of the about api cache
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
interface AboutApiCacheHandler
{
    public function getAboutApiCache(): array;
}
