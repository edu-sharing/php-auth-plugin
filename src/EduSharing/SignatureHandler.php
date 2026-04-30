<?php declare(strict_types=1);

namespace EduSharingApiClient;

/**
 * Interface SignatureHandler
 *
 * Interface describes the handling of signature algorithms
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
interface SignatureHandler
{
    public function getAlgorithm(): string;
}
