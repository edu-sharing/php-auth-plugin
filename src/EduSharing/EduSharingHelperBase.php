<?php declare(strict_types=1);

namespace EduSharingApiClient;

use Exception;
use InvalidArgumentException;
use JsonException;

/**
 * Class EduSharingHelperBase
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class EduSharingHelperBase
{
    public string               $baseUrl;
    public string               $privateKey;
    public string               $appId;
    public string               $language = 'de';
    public CurlHandler          $curlHandler;
    public string               $defaultAlgorithm = 'SHA1withRSA';
    public AboutApiCacheHandler $aboutApiCacheHandler;

    /**
     * @param string $baseUrl
     * The base url to your repository in the format "http://<host>/edu-sharing"
     * @param string $privateKey
     * Your app's private key. This must match the public key registered in the repo
     * @param string $appId
     * Your app id name (as registered in the edu-sharing repository)
     * @throws Exception
     */
    public function __construct(string $baseUrl, string $privateKey, string $appId) {
        if (!preg_match('/^([a-z]|[A-Z]|[0-9]|[-_]|[.])+$/', $appId)) {
            throw new InvalidAppIdException('The given app id contains invalid characters or symbols');
        }
        $baseUrl                    = rtrim($baseUrl, '/');
        $this->baseUrl              = $baseUrl;
        $this->privateKey           = $privateKey;
        $this->appId                = $appId;
        $this->curlHandler          = new DefaultCurlHandler();
        $this->aboutApiCacheHandler = new DefaultAboutApiCacheHandler($this);
    }

    /**
     * Function registerCurlHandler
     *
     * @param CurlHandler $handler
     * @return void
     */
    public function registerCurlHandler(CurlHandler $handler): void {
        $this->curlHandler = $handler;
    }

    /**
     * Function registerAboutApiCacheHandler
     *
     * @param AboutApiCacheHandler $handler
     * @return void
     */
    public function registerAboutApiCacheHandler(AboutApiCacheHandler $handler): void {
        $this->aboutApiCacheHandler = $handler;
    }

    /**
     * Function handleCurlRequest
     *
     * @param string $url
     * @param array $curlOptions
     * @return CurlResult
     */
    public function handleCurlRequest(string $url, array $curlOptions): CurlResult {
        return $this->curlHandler->handleCurlRequest($url, $curlOptions);
    }

    /**
     * Function setLanguage
     *
     * @param string $language
     * @return void
     */
    public function setLanguage(string $language): void {
        $this->language = $language;
    }

    /**
     * Function sign
     *
     * @param string $toSign
     * @return string
     * @throws Exception
     */
    public function sign(string $toSign): string {

        $algorithm = $this->getOpenSslAlgorithm($this->getAlgorithm());
        $privateKeyId = openssl_get_privatekey($this->privateKey);
        $success      = false;
        if ($privateKeyId !== false) {
            $success = openssl_sign($toSign, $signature, $privateKeyId, $algorithm);
        }
        if (!$success || !isset($signature)) {
            throw new Exception("Private key invalid or empty.");
        }
        return base64_encode($signature);
    }

    /**
     * will throw an exception if the given edu-sharing api is not compatible with this library version
     * i.e. you could call this in your configuration / setup
     *
     * @throws Exception
     */
    public function verifyCompatibility(): void {
        $minVersion = '8.0';
        $about = $this->getAboutCached();
        if (version_compare($about["version"]["repository"], $minVersion) < 0) {
            throw new Exception("The Edu-Sharing version of the connected repository is too low");
        }
    }

    /**
     * Function getAboutCached
     *
     * Retrieves the about info from the edu-sharing repository WITH caching
     * You should use this function in your application to retrieve the about info
     *
     * @throws JsonException
     */
    public function getAboutCached(): array {
        return $this->aboutApiCacheHandler->getAboutApiCache();
    }

    /**
     * Function getAbout
     *
     * Retrieves the about info from the edu-sharing repository WITHOUT caching
     * Use this in your AboutApiCacheHandler implementation to cache the result
     *
     * @throws JsonException
     * @throws Exception
     */
    public function getAbout(): array {
        $request    = $this->handleCurlRequest($this->baseUrl . '/rest/_about', [
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1
        ]);
        if ((int)$request->info["http_code"] === 200) {
            return json_decode($request->content, true, 512, JSON_THROW_ON_ERROR);
        }
        throw new Exception(
            "The edu-sharing about info could not be retrieved\n" . print_r($request->info, true));
    }

    /**
     * get the base url of the rendering service 2
     * if it is not available, null is returned
     *
     * @throws JsonException
     * @throws Exception
     */
    public function getRenderingServiceUrl(): ?string {
        $about = $this->getAboutCached();
        if (isset ($about['renderingService2']['url'])) {
            return $about['renderingService2']['url'];
        }
        return null;
    }

    /**
     * Converts the algorithm string provided by the repository to a PHP constant.
     * Null default is SHA1.
     *
     * @param string|null $algorithm
     * @return int
     */
    private function getOpenSslAlgorithm(?string $algorithm): int {
        if ($algorithm === null) {
            $algorithm = $this->defaultAlgorithm;
        }

        $normalized = strtoupper(str_replace(['-', '_'], '', $algorithm));

        return match ($normalized) {
            'SHA1WITHRSA', 'SHA1RSA', 'SHA1' => OPENSSL_ALGO_SHA1,
            'SHA224WITHRSA', 'SHA224RSA', 'SHA224' => OPENSSL_ALGO_SHA224,
            'SHA256WITHRSA', 'SHA256RSA', 'SHA256' => OPENSSL_ALGO_SHA256,
            'SHA384WITHRSA', 'SHA384RSA', 'SHA384' => OPENSSL_ALGO_SHA384,
            'SHA512WITHRSA', 'SHA512RSA', 'SHA512' => OPENSSL_ALGO_SHA512,
            default => throw new InvalidArgumentException("Unsupported signature algorithm: {$algorithm}"),
        };
    }

    /**
     * Function getRepoVersion
     *
     * @throws JsonException
     */
    public function getRepoVersion(): string {
        $about = $this->aboutApiCacheHandler->getAboutApiCache();
        return $about['version']['repository'];
    }

    /**
     * @throws JsonException
     */
    public function getAlgorithm(): string {
        return $this->aboutApiCacheHandler->getAboutApiCache()['defaultSignatureAlgorithm'] ?? $this->defaultAlgorithm;
    }
}
