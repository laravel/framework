<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Client;
use Illuminate\Support\Traits\Macroable;

class PendingRequest
{
    use Macroable;

    /**
     * The request body format.
     *
     * @var string
     */
    protected $bodyFormat;

    /**
     * The pending files for the request.
     *
     * @var array
     */
    protected $pendingFiles = [];

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
     * @var Client
     */
    private $guzzle;

    /**
     * Create a new HTTP Client instance.
     *
     * @param  \GuzzleHttp\Client  $guzzle
     * @return void
     */
    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;

        $this->asJson();

        $this->options = [
            'http_errors' => false,
        ];
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
     * Attach a file to the request.
     *
     * @param  string  $name
     * @param  string  $contents
     * @param  string|null  $filename
     * @param  array  $headers
     * @return $this
     */
    public function attach($name, $contents, $filename = null, array $headers = [])
    {
        $this->asMultipart();

        $this->pendingFiles[] = array_filter([
            'name' => $name,
            'contents' => $contents,
            'headers' => $headers,
            'filename' => $filename,
        ]);

        return $this;
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
     * Specify an authorization token for the request.
     *
     * @param  string  $token
     * @param  string  $type
     * @return $this
     */
    public function withToken($token, $type = 'Bearer')
    {
        return $this->withHeaders([
            'Authorization' => trim($type.' '.$token),
        ]);
    }

    /**
     * Specify the cookies that should be included with the request.
     *
     * @param  \GuzzleHttp\Cookie\CookieJarInterface  $cookies
     * @return $this
     */
    public function withCookies($cookies)
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
        return $this->send('DELETE', $url, empty($data) ? [] : [
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
    public function send(string $method, string $url, array $options = [])
    {
        if (isset($options[$this->bodyFormat])) {
            $options[$this->bodyFormat] = array_merge(
                $options[$this->bodyFormat], $this->pendingFiles
            );
        }

        $this->pendingFiles = [];

        try {
            $options = $this->mergeOptions([
                'laravel_data' => $options[$this->bodyFormat] ?? [],
                'query' => $this->parseQueryParams($url),
                'on_stats' => function ($transferStats) {
                    $this->transferStats = $transferStats;
                },
            ], $options);

            return tap(new Response($this->guzzle->request($method, $url, $options)), function (Response $response) use ($options) {
                $response->cookies = $options['cookies'] ?? null;

                $response->transferStats = $this->transferStats;
            });
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
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
}
