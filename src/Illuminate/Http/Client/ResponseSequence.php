<?php

namespace Illuminate\Http\Client;

class ResponseSequence
{
    /**
     * The responses in the sequence.
     *
     * @var array
     */
    protected $responses;

    /**
     * Create a new response sequence.
     *
     * @param  array  $responses
     * @return void
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    /**
     * Get the next response in the sequence.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return array_shift($this->responses);
    }
}
