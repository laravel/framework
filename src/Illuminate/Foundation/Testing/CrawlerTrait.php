<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

trait CrawlerTrait
{
    use InteractsWithPages;

    /**
     * The last response returned by the application.
     *
     * @var \Illuminate\Http\Response
     */
    protected $response;

    /**
     * The current URL being viewed.
     *
     * @var string
     */
    protected $currentUri;

    /**
     * Additional server variables for the request.
     *
     * @var array
     */
    protected $serverVariables = [];

    /**
     * Visit the given URI with a JSON request.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        $this->call(
            $method, $uri, [], [], [], $this->transformHeadersToServerVars($headers), $content
        );

        return $this;
    }

    /**
     * Visit the given URI with a GET request.
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return $this
     */
    public function get($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('GET', $uri, [], [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('POST', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a PUT request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('PUT', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a PATCH request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function patch($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('PATCH', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a DELETE request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('DELETE', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Send the given request through the application.
     *
     * This method allows you to fully customize the entire Request object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function handle(Request $request)
    {
        $this->currentUri = $request->fullUrl();

        $this->response = $this->app->prepareResponse($this->app->handle($request));

        return $this;
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    protected function shouldReturnJson(array $data = null)
    {
        return $this->receiveJson($data);
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @return $this|null
     */
    protected function receiveJson($data = null)
    {
        $this->seeJson();

        if (! is_null($data)) {
            return $this->seeJson($data);
        }
    }

    /**
     * Assert that the response contains an exact JSON array.
     *
     * @param  array  $data
     * @return $this
     */
    public function seeJsonEquals(array $data)
    {
        $actual = json_encode(array_sort_recursive(
            json_decode($this->response->getContent(), true)
        ));

        $this->assertEquals(json_encode(array_sort_recursive($data)), $actual);

        return $this;
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @param  bool  $negate
     * @return $this
     */
    public function seeJson(array $data = null, $negate = false)
    {
        if (is_null($data)) {
            $this->assertJson(
                $this->response->getContent(), "JSON was not returned from [{$this->currentUri}]."
            );

            return $this;
        }

        return $this->seeJsonContains($data, $negate);
    }

    /**
     * Assert that the response doesn't contain JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    public function dontSeeJson(array $data = null)
    {
        return $this->seeJson($data, true);
    }

    /**
     * Assert that the response contains the given JSON.
     *
     * @param  array  $data
     * @param  bool  $negate
     * @return $this
     */
    protected function seeJsonContains(array $data, $negate = false)
    {
        $method = $negate ? 'assertFalse' : 'assertTrue';

        $actual = json_decode($this->response->getContent(), true);

        if (is_null($actual) || $actual === false) {
            return $this->fail('Invalid JSON was returned from the route. Perhaps an exception was thrown?');
        }

        $actual = json_encode(array_sort_recursive(
            (array) $actual
        ));

        foreach (array_sort_recursive($data) as $key => $value) {
            $expected = $this->formatToExpectedJson($key, $value);

            $this->{$method}(
                Str::contains($actual, $expected),
                ($negate ? 'Found unexpected' : 'Unable to find')." JSON fragment [{$expected}] within [{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Format the given key and value into a JSON string for expectation checks.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function formatToExpectedJson($key, $value)
    {
        $expected = json_encode([$key => $value]);

        if (Str::startsWith($expected, '{')) {
            $expected = substr($expected, 1);
        }

        if (Str::endsWith($expected, '}')) {
            $expected = substr($expected, 0, -1);
        }

        return $expected;
    }

    /**
     * Asserts that the status code of the response matches the given code.
     *
     * @param  int  $status
     * @return $this
     */
    protected function seeStatusCode($status)
    {
        $this->assertEquals($status, $this->response->getStatusCode());

        return $this;
    }

    /**
     * Asserts that the response contains the given header and equals the optional value.
     *
     * @param  string $headerName
     * @param  mixed $value
     * @return $this
     */
    protected function seeHeader($headerName, $value = null)
    {
        $headers = $this->response->headers;

        $this->assertTrue($headers->has($headerName), "Header [{$headerName}] not present on response.");

        if (! is_null($value)) {
            $this->assertEquals(
                $headers->get($headerName), $value,
                "Header [{$headerName}] was found, but value [{$headers->get($headerName)}] does not match [{$value}]."
            );
        }

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string $cookieName
     * @param  mixed $value
     * @return $this
     */
    protected function seeCookie($cookieName, $value = null)
    {
        $headers = $this->response->headers;

        $exist = false;

        foreach ($headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                $exist = true;
                break;
            }
        }

        $this->assertTrue($exist, "Cookie [{$cookieName}] not present on response.");

        if (! is_null($value)) {
            $this->assertEquals(
                $cookie->getValue(), $value,
                "Cookie [{$cookieName}] was found, but value [{$cookie->getValue()}] does not match [{$value}]."
            );
        }

        return $this;
    }

    /**
     * Define a set of server variables to be sent with the requests.
     *
     * @param  array  $server
     * @return $this
     */
    protected function withServerVariables(array $server)
    {
        $this->serverVariables = $server;

        return $this;
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        $this->currentUri = $this->prepareUrlForRequest($uri);

        $request = Request::create(
            $this->currentUri, $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );

        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        return $this->response = $response;
    }

    /**
     * Call the given HTTPS URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function callSecure($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->app['url']->secure(ltrim($uri, '/'));

        return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Call a controller action and return the Response.
     *
     * @param  string  $method
     * @param  string  $action
     * @param  array   $wildcards
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function action($method, $action, $wildcards = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->app['url']->action($action, $wildcards, true);

        return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Call a named route and return the Response.
     *
     * @param  string  $method
     * @param  string  $name
     * @param  array   $routeParameters
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function route($method, $name, $routeParameters = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->app['url']->route($name, $routeParameters);

        return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Turn the given URI into a fully qualified URL.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }

        if (! Str::startsWith($uri, 'http')) {
            $uri = $this->baseUrl.'/'.$uri;
        }

        return trim($uri, '/');
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array  $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (! starts_with($name, $prefix) && $name != 'CONTENT_TYPE') {
                $name = $prefix.$name;
            }

            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Dump the content from the last response.
     *
     * @return void
     */
    public function dump()
    {
        $content = $this->response->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        dd($content);
    }
}
