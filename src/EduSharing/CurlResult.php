<?php declare(strict_types = 1);

namespace EduSharing;

/**
 * Class CurlResult
 *
 * DTO class for curl results
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class CurlResult
{
    public string $content;
    public int $error;
    public array $info;

    /**
     * CurlResult Constructor
     *
     * @param string $content
     * @param int $error
     * @param array $info
     */
    public function __construct(string $content, int $error, array $info) {
        $this->content = $content;
        $this->error   = $error;
        $this->info    = $info;
    }
}
