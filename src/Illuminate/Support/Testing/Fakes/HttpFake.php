<?php

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Testing\HttpHistory;
use PHPUnit\Framework\Assert as PHPUnit;

class HttpFake
{
    /**
     * The Guzzle handler stack that handles requests.
     *
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * The Guzzle mock handler that provides mocked responses.
     *
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * An array of all requests made.
     *
     * @var array
     */
    private $requestHistory = [];

    /**
     * The closure that generates a default response if the mock queue is empty.
     *
     * @var Closure|null
     */
    private $defaultResponseClosure;

    public function __construct()
    {
        $this->handlerStack = HandlerStack::create(
            $this->mockHandler = new MockHandler()
        );

        $this->handlerStack->push(
            Middleware::history($this->requestHistory)
        );
    }

    /**
     * Assert that a request was made matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertSent($callback)
    {
        PHPUnit::assertTrue(
            $this->history($callback)->count() > 0,
            'An expected request was not recorded.'
        );
    }

    /**
     * Assert the amount of responses in the mock queue.
     *
     * @param  int  $expected
     * @return void
     */
    public function assertMockQueueCount($expected)
    {
        PHPUnit::assertSame(
            $expected,
            $actual = $this->mockHandler->count(),
            "The Guzzle mock queue did not contain the expected amount of responses (expected: $expected, actual $actual)"
        );
    }

    /**
     * Assert that the mock queue is empty.
     *
     * @return void
     */
    public function assertMockQueueEmpty()
    {
        $this->assertMockQueueCount(0);
    }

    /**
     * Get a collection of all made requests filtered by an optional closure.
     *
     * @param  Closure|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function history($callback = null)
    {
        return collect($this->requestHistory)
            ->map(function ($history) {
                return new HttpHistory($history);
            })
            ->filter($callback ?: function () {
                return true;
            });
    }

    /**
     * Push a response to the mock queue.
     *
     * @param  mixed $response
     * @return $this
     */
    public function pushResponse($response)
    {
        $this->mockHandler->append($response);

        return $this;
    }

    /**
     * Push a response to the mock queue.
     *
     * @param  string|array  $body
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function push($body = '', int $status = 200, array $headers = [])
    {
        $body = is_array($body) ? json_encode($body) : $body;

        return $this->pushResponse(
            new Psr7Response($status, $headers, $body)
        );
    }

    /**
     * Push a response with the given status code to the mock queue.
     *
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushStatus(int $status, array $headers = [])
    {
        return $this->pushResponse(
            new Psr7Response($status, $headers, '')
        );
    }

    /**
     * Push response with the contents of a file as the body to the mock queue.
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
            new Psr7Response($status, $headers, $string)
        );
    }

    /**
     * Set a closure that is used to generate a response when the mock queue is empty.
     *
     * @param  Closure|null  $closure
     * @return $this
     */
    public function defaultResponse($closure = null)
    {
        $this->defaultResponseClosure = $closure ?: function () {
            return new Psr7Response(200, [], '');
        };

        return $this;
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->defaultResponseClosure && $this->mockHandler->count() === 0) {
            $this->pushResponse(($this->defaultResponseClosure)());
        }

        $pendingRequest = new PendingRequest(new Guzzle([
            'handler' => $this->handlerStack,
            'cookies' => true,
        ]));

        return $pendingRequest->{$method}(...$parameters);
    }
}
