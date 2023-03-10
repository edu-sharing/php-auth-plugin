<?php

namespace EduSharing;

class EduSharingNodeHelperConfig
{
    public UrlHandling $urlHandling;

    /**
     * @param UrlHandling $urlHandling
     */
    public function __construct(UrlHandling $urlHandling)
    {
        $this->urlHandling = $urlHandling;
    }


}