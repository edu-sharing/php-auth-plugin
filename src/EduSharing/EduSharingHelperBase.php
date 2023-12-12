<?php declare(strict_types=1);

namespace EduSharingApiClient;

use Exception;

/**
 * Class EduSharingHelperBase
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class EduSharingHelperBase
{
    public string      $baseUrl;
    public string      $privateKey;
    public string      $appId;
    public string      $language = 'de';
    public CurlHandler $curlHandler;

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
        $baseUrl           = rtrim($baseUrl, '/');
        $this->baseUrl     = $baseUrl;
        $this->privateKey  = $privateKey;
        $this->appId       = $appId;
        $this->curlHandler = new DefaultCurlHandler();
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
     */
    public function sign(string $toSign): string {
        $privateKeyId = openssl_get_privatekey($this->privateKey);
        openssl_sign($toSign, $signature, $privateKeyId);
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
        $request    = $this->handleCurlRequest($this->baseUrl . '/rest/_about', [
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1
        ]);
        if ((int)$request->info["http_code"] === 200) {
            $result = json_decode($request->content, true, 512, JSON_THROW_ON_ERROR);
            if (version_compare($result["version"]["repository"], $minVersion) < 0) {
                throw new Exception("The edu-sharing version of the target repository is too low. Minimum required is " . $minVersion . "\n" . print_r(isset($result['version']) ? $result['version'] : $result, true));
            }
        } else {
            throw new Exception("The edu-sharing version could not be retrieved\n" . print_r($request->info, true));
        }
    }
}
