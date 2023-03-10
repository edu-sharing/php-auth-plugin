<?php

namespace EduSharing;

class UrlHandling
{
    /**
     * configure if urls included in the responses should be automatically configured to redirect the user to edu-sharing
     * When set to false, you need to handle Download + Replacing of LMS_INLINE_HELPER_SCRIPT by yourself
     */
    public bool $enabled;
    public string $endpointURL;

    /**
     * @param $enabled
     * @param string $endpointURL
     */
    public function __construct(bool $enabled, string $endpointURL = "")
    {
        $this->enabled = $enabled;
        $this->endpointURL = $endpointURL;
    }


}