<?php

namespace Illuminate\Http\Client;

class ConnectionException extends HttpClientException
{
    /**
     * The context passed when creating the request.
     *
     * @var array<array-key, mixed>
     */
    public array $requestContext = [];
}
