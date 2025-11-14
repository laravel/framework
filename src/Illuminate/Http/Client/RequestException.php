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
        parent::__construct($this->prepareMessage($response), $response->status());

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
     * @param  \Illuminate\Http\Client\Response  $response
     * @return string
     */
    protected function prepareMessage(Response $response)
    {
        $message = "HTTP request returned status code {$response->status()}";

        $summary = static::$truncateAt
            ? Message::bodySummary($response->toPsrResponse(), static::$truncateAt)
            : Message::toString($response->toPsrResponse());

        return is_null($summary) ? $message : $message .= ":\n{$summary}\n";
    }
}
