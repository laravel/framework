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

    /**
     * Get a short summary from the body of response.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return string|null
     */
    private static function getResponseBodySummary(Response $response)
    {
        return \GuzzleHttp\Psr7\get_message_body_summary($response->toPsrResponse());
    }
}
