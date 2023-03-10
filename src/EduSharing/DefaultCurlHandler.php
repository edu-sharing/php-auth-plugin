<?php
namespace EduSharing;

/**
 * The default curl handler. It uses the native php curl functions
 * Use this as a reference for your custom curl library usage
 */
class DefaultCurlHandler extends \EduSharing\CurlHandler
{
    public function handleCurlRequest(string $url, array $curlOptions): \EduSharing\CurlResult
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOptions);
        $content = curl_exec($curl);
        $error     = curl_errno( $curl );
        $info = curl_getinfo($curl);
        curl_close($curl);
        return new \EduSharing\CurlResult($content, $error, $info);
    }
}