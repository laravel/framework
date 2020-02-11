<?php

namespace Illuminate\Http\Client;

class PendingRequest
{
    /**
     * The request body format.
     *
     * @var string
     */
    protected $bodyFormat;

    /**
     * The request cookies.
     *
     * @var array
     */
    protected $cookies;

    /**
     * The transfer stats for the request.
     *
     * \GuzzleHttp\TransferStats
     */
    protected $transferStats;

    /**
     * The request options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The callbacks that should execute before the request is sent.
     *
     * @var array
     */
    protected $beforeSendingCallbacks;

    /**
     * The stub callables that will handle requests.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $expectations;

    /**
     * Create a new HTTP Client instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->asJson();

        $this->options = [
            'http_errors' => false,
        ];

        $this->beforeSendingCallbacks = collect([function ($request, $options) {
            $this->cookies = $options['cookies'];
        }]);
    }

    /**
     * Indicate the request contains JSON.
     *
     * @return $this
     */
    public function asJson()
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    /**
     * Indicate the request contains form parameters.
     *
     * @return $this
     */
    public function asForm()
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    /**
     * Indicate the request is a multi-part form request.
     *
     * @return $this
     */
    public function asMultipart()
    {
        return $this->bodyFormat('multipart');
    }

    /**
     * Specify the body format of the request.
     *
     * @param  string  $format
     * @return $this
     */
    public function bodyFormat(string $format)
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
    }

    /**
     * Specify the request's content type.
     *
     * @param  string  $contentType
     * @return $this
     */
    public function contentType(string $contentType)
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }

    /**
     * Indicate that JSON should be returned by the server.
     *
     * @return $this
     */
    public function acceptJson()
    {
        return $this->accept('application/json');
    }

    /**
     * Indicate the type of content that should be returned by the server.
     *
     * @param  string  $contentType
     * @return $this
     */
    public function accept($contentType)
    {
        return $this->withHeaders(['Accept' => $contentType]);
    }

    /**
     * Add the given headers to the request.
     *
     * @param  array  $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    /**
     * Specify the basic authentication username and password for the request.
     *
     * @param  string  $username
     * @param  string  $password
     * @return $this
     */
    public function withBasicAuth(string $username, string $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password],
            ]);
        });
    }

    /**
     * Specify the digest authentication username and password for the request.
     *
     * @param  string  $username
     * @param  string  $password
     * @return $this
     */
    public function withDigestAuth($username, $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password, 'digest'],
            ]);
        });
    }

    /**
     * Specify the cookies that should be included with the request.
     *
     * @param  array  $cookies
     * @return $this
     */
    public function withCookies(array $cookies)
    {
        return tap($this, function ($request) use ($cookies) {
            return $this->options = array_merge_recursive($this->options, [
                'cookies' => $cookies,
            ]);
        });
    }

    /**
     * Indicate that redirects should not be followed.
     *
     * @return $this
     */
    public function withoutRedirecting()
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'allow_redirects' => false,
            ]);
        });
    }

    /**
     * Indicate that TLS certificates should not be verified.
     *
     * @return $this
     */
    public function withoutVerifying()
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'verify' => false,
            ]);
        });
    }

    /**
     * Specify the timeout (in seconds) for the request.
     *
     * @param  int  $seconds
     * @return $this
     */
    public function timeout(int $seconds)
    {
        return tap($this, function () use ($seconds) {
            $this->options['timeout'] = $seconds;
        });
    }

    /**
     * Merge new options into the client.
     *
     * @param  array  $options
     * @return $this
     */
    public function withOptions(array $options)
    {
        return tap($this, function ($request) use ($options) {
            return $this->options = array_merge_recursive($this->options, $options);
        });
    }

    /**
     * Add a new "before sending" callback to the request.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function beforeSending($callback)
    {
        return tap($this, function () use ($callback) {
            $this->beforeSendingCallbacks[] = $callback;
        });
    }

    /**
     * Issue a GET request to the given URL.
     *
     * @param  string  $url
     * @param  array  $query
     * @return \Illuminate\Http\Client\Response
     */
    public function get(string $url, array $query = [])
    {
        return $this->send('GET', $url, [
            'query' => $query,
        ]);
    }

    /**
     * Issue a POST request to the given URL.
     *
     * @param  string  $url
     * @param  array  $data
     * @return \Illuminate\Http\Client\Response
     */
    public function post(string $url, array $data = [])
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a PATCH request to the given URL.
     *
     * @param  string  $url
     * @param  array  $data
     * @return \Illuminate\Http\Client\Response
     */
    public function patch($url, $data = [])
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a PUT request to the given URL.
     *
     * @param  string  $url
     * @param  array  $data
     * @return \Illuminate\Http\Client\Response
     */
    public function put($url, $data = [])
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a DELETE request to the given URL.
     *
     * @param  string  $url
     * @param  array  $data
     * @return \Illuminate\Http\Client\Response
     */
    public function delete($url, $data = [])
    {
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Send the request to the given URL.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return \Illuminate\Http\Client\Response
     */
    public function send(string $method, string $url, array $options)
    {
        try {
            return tap(new Response($this->buildClient()->request($method, $url, $this->mergeOptions([
                'query' => $this->parseQueryParams($url),
                'on_stats' => function ($transferStats) {
                    $this->transferStats = $transferStats;
                },
            ], $options))), function ($response) {
                $response->cookies = $this->cookies;
                $response->transferStats = $this->transferStats;
            });
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Build the Guzzle client.
     *
     * @return \GuzzleHttp\Client
     */
    public function buildClient()
    {
        return new \GuzzleHttp\Client([
            'handler' => $this->buildHandlerStack(),
            'cookies' => true,
        ]);
    }

    /**
     * Build the before sending handler stack.
     *
     * @return \GuzzleHttp\HandlerStack
     */
    public function buildHandlerStack()
    {
        return tap(\GuzzleHttp\HandlerStack::create(), function ($stack) {
            $stack->push($this->buildBeforeSendingHandler());
            $stack->push($this->buildStubHandler());
        });
    }

    /**
     * Build the before sending handler.
     *
     * @return \Closure
     */
    public function buildBeforeSendingHandler()
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request, $options), $options);
            };
        };
    }

    /**
     * Build the stub handler.
     *
     * @return \Closure
     */
    public function buildStubHandler()
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return ($this->expectations ?? collect())
                     ->map
                     ->__invoke(new Request($request), $options)
                     ->filter()
                     ->first(null, $handler($request, $options));
            };
        };
    }

    /**
     * Execute the "before sending" callbacks.
     *
     * @param  \GuzzleHttp\Psr7\RequestInterface
     * @param  array  $options
     * @return \Closure
     */
    public function runBeforeSendingCallbacks($request, array $options)
    {
        return tap($request, function ($request) use ($options) {
            $this->beforeSendingCallbacks->each->__invoke(new Request($request), $options);
        });
    }

    /**
     * Merge the given options with the current request options.
     *
     * @param  array  $options
     * @return array
     */
    public function mergeOptions(...$options)
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * Parse the query parameters in the given URL.
     *
     * @param  string  $url
     * @return array
     */
    public function parseQueryParams(string $url)
    {
        return tap([], function (&$query) use ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
        });
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function stub($callback)
    {
        $this->expectations = collect($callback);

        return $this;
    }
}
