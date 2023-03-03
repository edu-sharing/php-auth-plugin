<?php

class EduSharingHelperBase {
    public $baseUrl;
    public $privateKey;
    public $appId;
    public $language = 'de';
    private $curlHandler;
    /**
     * @param string $baseUrl
     * The base url to your repository in the format "http://<host>/edu-sharing"
     * @param string $privateKey
     * Your app's private key. This must match the public key registered in the repo
     * @param string $appId
     * Your app id name (as registered in the edu-sharing repository)
     */
    public function __construct(
        string $baseUrl,
        string $privateKey,
        string $appId
    ) {
        if(!preg_match('/^([a-z]|[A-Z]|[0-9]|[-_])+$/', $appId)) {
            throw new Exception('The given app id contains invalid characters or symbols');
        }
        if(substr($baseUrl, -1) === '/') {
            $baseUrl = substr($baseUrl, 0, -1);
        }
        $this->baseUrl=$baseUrl;
        $this->privateKey=$privateKey;
        $this->appId=$appId;
        $this->curlHandler=new DefaultCurlHandler();
    }

    public function registerCurlHandler(CurlHandler $handler) {
        $this->curlHandler = $handler;
    }

    public function handleCurlRequest(string $url, array $curlOptions) {
        return $this->curlHandler->handleCurlRequest($url, $curlOptions);
    }

    public function setLanguage(string $language) {
        $this->language = $language;
    }

    function sign(string $toSign) {
        $pkeyid = openssl_get_privatekey($this->privateKey);
        openssl_sign($toSign, $signature, $pkeyid);
        $signature = base64_encode($signature);
        @openssl_free_key($pkeyid);
        return $signature;
    }

    /**
     * will throw an exception if the given edu-sharing api is not compatible with this library version
     * i.e. you could call this in your configuration / setup
     */
    function verifyCompatibility() {
        $MIN_VERSION = "8.0";
        $result = json_decode($this->curlHandler->handleCurlRequest($this->baseUrl . '/rest/_about', [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => 1
        ])->content,
            true
        );
        if(version_compare($result["version"]["repository"], $MIN_VERSION) < 0) {
            throw new Exception("The edu-sharing version of the target repository is too low. Minimum required is " . $MIN_VERSION . "\n" . print_r($result["version"], true));
        }
    }

}