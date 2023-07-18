<?php declare(strict_types = 1);
namespace EduSharingApiClient;

/**
 * Class DefaultCurlHandler
 *
 * The default curl handler. It uses the native php curl functions
 * Use this as a reference for your custom curl library usage
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class DefaultCurlHandler extends CurlHandler
{
    /**
     * Function handleCurlRequest
     *
     * @param string $url
     * @param array $curlOptions
     * @return CurlResult
     */
    public function handleCurlRequest(string $url, array $curlOptions): CurlResult {
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOptions);
        $content = curl_exec($curl);
        $error   = curl_errno( $curl );
        $info    = curl_getinfo($curl);
        curl_close($curl);
        return new CurlResult(!is_string($content) ? '' : $content, $error, $info);
    }
}