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
     * Push a response to the sequence.
     *
     * @param  mixed $response
     * @return $this
     */
    public function pushResponse($response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Push an empty response to the sequence.
     *
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushEmptyResponse($status = 200, $headers = [])
    {
        return $this->pushResponse(
            Factory::response('', $status, $headers)
        );
    }

    /**
     * Push response with a string body to the sequence.
     *
     * @param  string  $string
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushString($string, $status = 200, $headers = [])
    {
        return $this->pushResponse(
            Factory::response($string, $status, $headers)
        );
    }

    /**
     * Push response with a json body to the sequence.
     *
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushJson(array $data, $status = 200, $headers = [])
    {
        return $this->pushResponse(
            Factory::response(json_encode($data), $status, $headers)
        );
    }

    /**
     * Push response with the contents of a file as the body to the sequence.
     *
     * @param  string  $filePath
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushFile($filePath, $status = 200, $headers = [])
    {
        $string = file_get_contents($filePath);

        return $this->pushResponse(
            Factory::response($string, $status, $headers)
        );
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
