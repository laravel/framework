<?php

namespace Illuminate\Http\Client;

class CircuitOpenException extends HttpClientException
{
    /**
     * The identifier of the open circuit.
     *
     * @var string
     */
    public $circuitKey;

    /**
     * The number of seconds until the circuit will allow a probe request.
     *
     * @var int
     */
    public $retryAfter;

    /**
     * Create a new circuit open exception instance.
     */
    public function __construct(string $circuitKey, int $retryAfter = 0)
    {
        parent::__construct("The circuit breaker for [{$circuitKey}] is open. Request was not dispatched.");

        $this->circuitKey = $circuitKey;
        $this->retryAfter = $retryAfter;
    }
}
