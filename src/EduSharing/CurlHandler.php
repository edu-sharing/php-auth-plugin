<?php declare(strict_types=1);

namespace EduSharingApiClient;

/**
 * Class CurlHandler
 *
 * Class that describes the handling of curl requests
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
abstract class CurlHandler
{
    public const    METHOD_GET  = 'get';
    public const    METHOD_POST = 'post';
    public const    METHOD_PUT  = 'put';
    protected const METHODS     = [self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT];

    protected string $method = 'get';

    /**
     * Function handleCurlRequest
     *
     * @param string $url the request url
     * @param array $curlOptions the curl options, assoc array same as in the default php curl implementation
     * @return CurlResult a result object containing the response content, error/status code and a curl info array
     */
    public abstract function handleCurlRequest(string $url, array $curlOptions): CurlResult;

    public function setMethod(string $method): void {
        if (in_array($method, self::METHODS, true)) {
            $this->method = $method;
        }
    }
}