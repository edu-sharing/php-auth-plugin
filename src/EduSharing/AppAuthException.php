<?php
namespace EduSharing;

class AppAuthException extends \Exception {
    public function __construct($message = '')
    {
        parent::__construct($this->getExplanation($message));
    }
    private function getExplanation($message): string
    {
        $KNOWN_ERRORS = [
            "the timestamp sent by your client was too old. Please check the clocks of both servers or increase the value 'message_offset_ms'/'message_send_offset_ms' in the app properties file"
            => ['MESSAGE SEND TIMESTAMP TO OLD', 'MESSAGE SEND TIMESTAMP newer than MESSAGE ARRIVED TIMESTAMP'],
            "The ip your client is using for request is not known by the repository. Please add the ip into your 'host_aliases' app properties file"
            => ['INVALID_HOST']
        ];
        foreach($KNOWN_ERRORS as $desc => $keys) {
            foreach($keys as $k) {
                if(str_contains($message, $k))
                return $desc . '(' . $message . ')';
            }
        }
        return $message;
    }
}
