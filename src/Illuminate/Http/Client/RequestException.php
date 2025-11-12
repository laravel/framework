<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Psr7\Message;

class RequestException extends HttpClientException
{
    /**
     * The response instance.
     *
     * @var \Illuminate\Http\Client\Response
     */
    public $response;

    /**
     * The truncation length for the exception message.
     *
     * @var int|false
     */
    public static $truncateAt = 120;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     */
    public function __construct(Response $response)
    {
        parent::__construct("HTTP request returned status code {$response->status()}", $response->status());

        $this->response = $response;
    }

    /**
     * Enable truncation of request exception messages.
     *
     * @return void
     */
    public static function truncate()
    {
        static::$truncateAt = 120;
    }

    /**
     * Set the truncation length for request exception messages.
     *
     * @param  int  $length
     * @return void
     */
    public static function truncateAt(int $length)
    {
        static::$truncateAt = $length;
    }

    /**
     * Disable truncation of request exception messages.
     *
     * @return void
     */
    public static function dontTruncate()
    {
        static::$truncateAt = false;
    }

    /**
     * Prepare the exception message.
     *
     * @return void
     */
    public function report(): void
    {
        $summary = static::$truncateAt
            ? Message::bodySummary($this->response->toPsrResponse(), static::$truncateAt)
            : Message::toString($this->response->toPsrResponse());

        $this->message = is_null($summary)
            ? $this->message
            : $this->message .= ":\n{$summary}\n";
    }
}
