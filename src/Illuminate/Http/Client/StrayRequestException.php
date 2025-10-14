<?php

namespace Illuminate\Http\Client;

use RuntimeException;

class StrayRequestException extends RuntimeException
{
    /**
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        parent::__construct('Attempted request to ['.$uri.'] without a matching fake.');
    }
}
