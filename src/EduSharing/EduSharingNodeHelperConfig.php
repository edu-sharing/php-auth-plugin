<?php declare(strict_types = 1);

namespace EduSharing;

/**
 * Class EduSharingNodeHelperConfig
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class EduSharingNodeHelperConfig
{
    public UrlHandling $urlHandling;

    /**
     * EduSharingNodeHelperConfig constructor
     *
     * @param UrlHandling $urlHandling
     */
    public function __construct(UrlHandling $urlHandling) {
        $this->urlHandling = $urlHandling;
    }
}
