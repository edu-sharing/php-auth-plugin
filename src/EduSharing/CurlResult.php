<?php

namespace EduSharing;

class CurlResult
{
    public string $content;
    public int $error;
    public array $info;

    public function __construct(
        string $content,
        int    $error,
        array  $info
    )
    {
        $this->content = $content;
        $this->error = $error;
        $this->info = $info;
    }
}