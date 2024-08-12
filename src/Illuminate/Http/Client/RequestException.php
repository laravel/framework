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
     * Determine if the exception message should be verbose.
     *
     * @var bool
     */
    protected bool $isVerbose = false;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @param  bool  $isVerbose
     * @return void
     */
    public function __construct(Response $response, $isVerbose = false)
    {
        $this->response = $response;

        $this->isVerbose = $isVerbose;

        parent::__construct($this->prepareMessage($response), $response->status());
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

        if (! $this->isVerbose) {
            $psrResponse = Message::bodySummary($response->toPsrResponse());
        } else {
            $psrResponse = $response->toPsrResponse()->getBody()->getContents();
        }

        return is_null($psrResponse) ? $message : $message .= ":\n{$psrResponse}\n";
    }
}
