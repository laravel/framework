<?php

namespace Illuminate\Queue;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class RemoteJobFailed extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $job
     * @param  string  $message
     * @param  \Illuminate\Http\Client\Response|null  $response
     * @param  \Throwable|null  $previous
     */
    public function __construct(
        public string $job,
        string $message,
        public ?Response $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $response?->status() ?? 0, $previous);
    }

    /**
     * Create a new exception instance from the service's response.
     *
     * @param  string  $job
     * @param  \Illuminate\Http\Client\Response  $response
     * @return self
     */
    public static function fromResponse(string $job, Response $response)
    {
        $message = $response->json('message');

        return new self($job, is_string($message)
            ? $message
            : "Remote job [{$job}] failed with status {$response->status()}.", $response);
    }
}
