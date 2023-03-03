<?php

abstract class EduSharingHelperAbstract {

    protected EduSharingHelperBase $base;
    public function __construct(
        EduSharingHelperBase $base
    ) {
        $this->base = $base;
    }

    /**
     * Generates the header to use for a given ticket to authenticate with any edu-sharing api endpoint
     * @param string $ticket
     * The ticket, obtained by @getTicketForUser
     * @return string
     */
    public function getRESTAuthenticationHeader(string $ticket): string
    {
        return 'Authorization: EDU-TICKET ' . $ticket;
    }

    protected function getSignatureHeaders(
        string $signString,
        string $accept = 'application/json',
        string $contentType = 'application/json'
    ): array
    {
        $ts = time() * 1000;
        $toSign = $this->base->appId . $signString . $ts;
        $signature = $this->sign($toSign);
        return [
            'Accept: ' . $accept,
            'Content-Type: ' . $contentType,
            'X-Edu-App-Id: ' . $this->base->appId,
            'X-Edu-App-Signed: ' . $toSign,
            'X-Edu-App-Sig: ' . $signature,
            'X-Edu-App-Ts: ' . $ts,
        ];
    }

    protected function sign(string $toSign): string
    {
        return $this->base->sign($toSign);
    }
}