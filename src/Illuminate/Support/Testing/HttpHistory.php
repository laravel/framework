<?php

namespace Illuminate\Support\Testing;

use Illuminate\Http\Client\Response;

class HttpHistory
{
    /** @var HttpRequest */
    public $request;

    /** @var Response */
    public $response;

    public $error;

    public $options;

    public function __construct(array $history)
    {
        $this->request = new HttpRequest($history['request']);

        $this->request->withData($history['options']['laravel_data']);

        $this->response = new Response($history['response']);

        $this->error = $history['error'];

        $this->options = $history['options'];
    }
}
