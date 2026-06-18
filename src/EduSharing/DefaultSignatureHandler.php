<?php declare(strict_types=1);

namespace EduSharingApiClient;

use Exception;

/**
 * Class DefaultSignatureHandler
 *
 * Default implementation of the signature handler. It tries to read the
 * signature algorithm advertised by the connected repository via its
 * "_about" endpoint and falls back to the base default algorithm
 * (SHA1withRSA) if the repository does not provide one.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class DefaultSignatureHandler implements SignatureHandler
{
    private EduSharingHelperBase $base;

    public function __construct(EduSharingHelperBase $base) {
        $this->base = $base;
    }

    public function getAlgorithm(): string {
        try {
            $about = $this->base->getAbout();
            if (isset($about['defaultSignatureAlgorithm'])) {
                return $about['defaultSignatureAlgorithm'];
            }
        } catch (Exception) {
            // Do nothing. Just use the default. this might happen if a legacy repository (< 10.0) is present
        }
        return $this->base->defaultAlgorithm;
    }
}