<?php

namespace Illuminate\Http\Client;

use Illuminate\Contracts\Debug\ShouldntReport;

class UncacheableResponseException extends HttpClientException implements ShouldntReport
{
    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     */
    public function __construct(public Response $response)
    {
        parent::__construct('The HTTP client response is not cacheable.');
    }
}
