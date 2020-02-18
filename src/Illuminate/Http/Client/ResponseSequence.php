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
     * @param  string|array  $body
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function push($body = '', int $status = 200, array $headers = [])
    {
        if (is_array($body)) {
            return $this->pushResponse(
                Factory::response(json_encode($body), $status, $headers)
            );
        }

        return $this->pushResponse(
            Factory::response($body, $status, $headers)
        );
    }

    /**
     * Push a response with the given status code to the sequence.
     *
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushStatus(int $status, array $headers = [])
    {
        return $this->pushResponse(
            Factory::response('', $status, $headers)
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
    public function pushFile(string $filePath, int $status = 200, array $headers = [])
    {
        $string = file_get_contents($filePath);

        return $this->pushResponse(
            Factory::response($string, $status, $headers)
        );
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
     * Get the next response in the sequence.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return array_shift($this->responses);
    }
}
