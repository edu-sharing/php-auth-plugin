<?php declare(strict_types=1);

namespace EduSharingApiClient;

use Exception;

/**
 * Class EduSharingAuthHelper
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class EduSharingAuthHelper extends EduSharingHelperAbstract
{

    /**
     * Function getTicketAuthenticationInfo
     *
     * Gets detailed information about a ticket
     * Will throw an exception if the given ticket is not valid anymore
     * @param string $ticket
     * The ticket, obtained by @getTicketForUser
     * @return array
     * Detailed information about the current session
     * @throws Exception
     * Thrown if the ticket is not valid anymore
     */
    public function getTicketAuthenticationInfo(string $ticket): array {
        $curl = $this->base->handleCurlRequest($this->base->baseUrl . '/rest/authentication/v1/validateSession', [
            CURLOPT_HTTPHEADER     => [
                $this->getRESTAuthenticationHeader($ticket),
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 5
        ]);
        if ($curl->content === '') {
            throw new Exception('No answer from repository. Possibly a timeout while trying to connect to ' . $this->base->baseUrl);
        }
        $data = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
        if ($data['statusCode'] !== 'OK') {
            throw new Exception('The given ticket is not valid anymore');
        }
        return $data;
    }

    /**
     * Function getTicketForUser
     *
     * Fetches the edu-sharing ticket for a given username
     * @param string $username
     * The username you want to generate a ticket for
     * @return string
     * The ticket, which you can use as an authentication header, see @getRESTAuthenticationHeader
     * @throws Exception
     */
    public function getTicketForUser(string $username): string {
        $curl = $this->base->handleCurlRequest($this->base->baseUrl . '/rest/authentication/v1/appauth/' . rawurlencode($username), [
            CURLOPT_POST           => 1,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $this->getSignatureHeaders($username),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 5
        ]);
        if ($curl->content === '') {
            throw new Exception('edu-sharing ticket could not be retrieved: HTTP-Code ' . $curl->info['http_code'] . ': ' . 'No answer from repository. Possibly a timeout while trying to connect to "' . $this->base->baseUrl . '"');
        }
        try {
            $data = json_decode($curl->content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            $data = [];
        }
        $responseOk = $curl->error === 0 && (int)$curl->info['http_code'] ?? 0 === 200;
        if ($responseOk && ($data['userId'] ?? '' === $username || substr($data['userId'], 0, strlen($username) + 1) === $username . '@')) {
            return $data['ticket'];
        }
        throw new AppAuthException($data['message'] ?? '');
    }
}
