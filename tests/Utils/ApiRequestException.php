<?php

namespace Illuminate\Tests\Utils;

use Exception;

class ApiRequestException extends Exception
{
    public function __construct(
        public string $uri,
        public int $statusCode,
        public string $reason,
        public string|array $responseBody = '',
    ) {
        parent::__construct("API request to {$uri} failed with status $statusCode $reason");
    }
}
