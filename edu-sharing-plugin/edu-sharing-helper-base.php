<?php

class EduSharingHelperBase {
    public string $baseUrl;
    public string $privateKey;
    public string $repositoryPublicKey;
    public string $appId;
    public string $language = 'de';
    private CurlHandler $curlHandler;
    /**
     * @param string $baseUrl
     * The base url to your repository in the format "http://<host>/edu-sharing"
     * @param string $privateKey
     * Your app's private key. This must match the public key registered in the repo
     * @param string $repositoryPublicKey
     * The repositories public key. You can obtain it from the repo by calling
     * @param string $appId
     * Your app id name (as registered in the edu-sharing repository)
     */
    public function __construct(
        string $baseUrl,
        string $privateKey,
        string $repositoryPublicKey,
        string $appId
    ) {
        if(!preg_match('/^([a-z]|[A-Z]|[0-9]|[-_])+$/', $appId)) {
            throw new Exception('The given app id contains invalid characters or symbols');
        }
        if(str_ends_with($baseUrl, '/')) {
            $baseUrl = substr($baseUrl, 0, -1);
        }
        $this->baseUrl=$baseUrl;
        $this->privateKey=$privateKey;
        $this->repositoryPublicKey=$repositoryPublicKey;
        $this->appId=$appId;
        $this->curlHandler=new DefaultCurlHandler();
    }

    public function registerCurlHandler(CurlHandler $handler): void
    {
        $this->curlHandler = $handler;
    }

    public function handleCurlRequest(string $url, array $curlOptions): CurlResult
    {
        return $this->curlHandler->handleCurlRequest($url, $curlOptions);
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    function sign(string $toSign): string
    {
        $pkeyid = openssl_get_privatekey($this->privateKey);
        openssl_sign($toSign, $signature, $pkeyid);
        return base64_encode($signature);
    }
    public function encrypt(string $toEncrypt): string
    {
        $pkeyid = openssl_get_publickey($this->repositoryPublicKey);
        openssl_public_encrypt($toEncrypt, $crypted, $pkeyid);
        return base64_encode($crypted);
    }


    /**
     * will throw an exception if the given edu-sharing api is not compatible with this library version
     * i.e. you could call this in your configuration / setup
     */
    function verifyCompatibility(): void
    {
        $MIN_VERSION = '6.0';
        $request = $this->curlHandler->handleCurlRequest($this->baseUrl . '/rest/_about', [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => 1
        ]);
        if($request->info["http_code"] === 200) {
            $result = json_decode($request->content, true);
            if (version_compare($result["version"]["repository"], $MIN_VERSION) <= 0) {
                throw new Exception("The edu-sharing version of the target repository is too low. Minimum required is " . $MIN_VERSION . "\n" . print_r(isset($result['version']) ? $result['version'] : $result, true));
            }
        } else {
            throw new Exception("The edu-sharing version could not be retrieved\n" . print_r($request->info, true));
        }
    }

}