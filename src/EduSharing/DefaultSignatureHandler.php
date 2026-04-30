<?php declare(strict_types=1);

namespace EduSharingApiClient;

/**
 * Class DefaultSignatureHandler
 *
 * Default implementation of the signature handler. Always uses SHA1withRSA algorithm as fallback.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class DefaultSignatureHandler implements SignatureHandler
{
    public string $defaultAlgorithm = 'SHA1withRSA';

    public function getAlgorithm(): string {
        return $this->defaultAlgorithm;
    }
}
