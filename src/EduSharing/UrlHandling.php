<?php declare(strict_types = 1);

namespace EduSharing;

/**
 * Class UrlHandling
 *
 * configure if urls included in the responses should be automatically configured to redirect the user to edu-sharing
 * When set to false, you need to handle Download + Replacing of LMS_INLINE_HELPER_SCRIPT by yourself
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class UrlHandling
{

    public bool $enabled;
    public string $endpointURL;

    /**
     * UrlHandling constructor
     *
     * @param bool $enabled
     * @param string $endpointURL
     */
    public function __construct(bool $enabled, string $endpointURL = "") {
        $this->enabled     = $enabled;
        $this->endpointURL = $endpointURL;
    }


}