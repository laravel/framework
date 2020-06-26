<?php

namespace Illuminate\Http\Client;

use Exception;

class RequestException extends Exception
{
    /**
     * The response instance.
     *
     * @var \Illuminate\Http\Client\Response
     */
    public $response;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return void
     */
    public function __construct(Response $response)
    {
        $message = "HTTP request returned status code {$response->status()}";
        $summary = self::getResponseBodySummary($response);

        if ($summary !== null) {
            $message .= ":\n{$summary}\n";
        }

        parent::__construct($message, $response->status());
        $this->response = $response;
    }

    private static function getResponseBodySummary(Response $response, $truncateAt = 120)
    {
        $body = $response->toPsrResponse()->getBody();

        if (! $body->isSeekable() || ! $body->isReadable()) {
            return;
        }

        $size = $body->getSize();

        if ($size === 0) {
            return;
        }

        $summary = $body->read($truncateAt);
        $body->rewind();

        if ($size > $truncateAt) {
            $summary .= ' (truncated...)';
        }

        // Matches any printable character, including unicode characters:
        // letters, marks, numbers, punctuation, spacing, and separators.
        if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/', $summary)) {
            return;
        }

        return $summary;
    }
}
