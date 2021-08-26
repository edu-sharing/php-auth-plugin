<?php

class EduSharingAuthHelper {
    public $baseUrl;
    public $privateKey;
    public $appId;

    /**
     * EduSharingAuthHelper constructor.
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
        $this->baseUrl=$baseUrl;
        $this->privateKey=$privateKey;
        $this->appId=$appId;
    }

    /**
     * Generates the header to use for a given ticket to authenticate with any edu-sharing api endpoint
     * @param string $ticket
     * The ticket, obtained by @getTicketForUser
     * @return string
     */
    public function getRESTAuthenticationHeader(string $ticket) {
        return 'Authorization: EDU-TICKET ' . $ticket;
    }

    /**
     * Gets detailed information about a ticket
     * Will throw an exception if the given ticket is not valid anymore
     * @param string $ticket
     * The ticket, obtained by @getTicketForUser
     * @return array
     * Detailed information about the current session
     * @throws Exception
     * Thrown if the ticket is not valid anymore
     */
    public function getTicketAuthenticationInfo(string $ticket) {
        $curl = curl_init($this->baseUrl . '/rest/authentication/v1/validateSession');
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                $this->getRESTAuthenticationHeader($ticket),
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => 1
        ]);
        $data = json_decode(curl_exec($curl), true);
        curl_close($curl);
        if($data['statusCode'] !== 'OK') {
            throw new Exception('The given ticket is not valid anymore');
        }
        return $data;
    }

    /**
     * Fetches the edu-sharing ticket for a given username
     * @param string $username
     * The username you want to generate a ticket for
     * @return string
     * The ticket, which you can use as an authentication header, see @getRESTAuthenticationHeader
     * @throws Exception
     */
    public function getTicketForUser(string $username) {
        $curl = curl_init($this->baseUrl . '/rest/authentication/v1/appauth/' . rawurlencode($username));
        $ts = time() * 1000;
        $toSign = $this->appId . $username . $ts;
        $signature = $this->sign($toSign);
        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Edu-App-Id: ' . $this->appId,
                'X-Edu-App-Signed: ' . $toSign,
                'X-Edu-App-Sig: ' . $signature,
                'X-Edu-App-Ts: ' . $ts,
            ]
        ]);
        $data = json_decode(curl_exec($curl), true);
        $err     = curl_errno( $curl );
        $info = curl_getinfo($curl);
        curl_close($curl);
        if ($err === 0 && $info["http_code"] === 200 && $data['userId'] === $username) {
            return $data['ticket'];
        } else {
            throw new Exception('edu-sharing ticket could not be retrieved: HTTP-Code ' .
                $info["http_code"] . ': ' . $data['error']);
        }

    }

    private function sign(string $toSign) {
        $pkeyid = openssl_get_privatekey($this->privateKey);
        openssl_sign($toSign, $signature, $pkeyid);
        $signature = base64_encode($signature);
        openssl_free_key($pkeyid);
        return $signature;
    }

}