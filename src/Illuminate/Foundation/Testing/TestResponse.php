<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Support\Str;
use Illuminate\Http\Response;
use PHPUnit_Framework_Assert as PHPUnit;

class TestResponse extends Response
{
    /**
     * Convert the given response into a TestResponse.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return static
     */
    public static function fromBaseResponse($response)
    {
        $testResponse = new static(
            $response->getContent(), $response->status()
        );

        $testResponse->headers = $response->headers;

        return $testResponse;
    }

    /**
     * Assert that the response has an OK status code.
     *
     * @return void
     */
    public function assertHasStatus($status)
    {
        $actual = $this->getStatusCode();

        PHPUnit::assertTrue($this->isOk(), "Expected status code 200, got {$actual}.");
    }

    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param  string  $uri
     * @return void
     */
    public function assertIsRedirect($uri)
    {
        PHPUnit::assertTrue(
            $this->isRedirect(),
            'Response status code ['.$this->status().'] is not a redirect status code.'
        );

        PHPUnit::assertEquals(app('url')->to($uri), $this->headers->get('Location'));
    }

    /**
     * Asserts that the response contains the given header and equals the optional value.
     *
     * @param  string  $headerName
     * @param  mixed  $value
     * @return $this
     */
    public function assertHasHeader($headerName, $value = null)
    {
        PHPUnit::assertTrue(
            $this->headers->has($headerName), "Header [{$headerName}] not present on response."
        );

        if (! is_null($value)) {
            PHPUnit::assertEquals(
                $this->headers->get($headerName), $value,
                "Header [{$headerName}] was found, but value [{$this->headers->get($headerName)}] does not match [{$value}]."
            );
        }

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @return void
     */
    public function assertHasPlainCookie($cookieName, $value = null)
    {
        $this->seeCookie($cookieName, $value, false);
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @param  bool  $encrypted
     * @return void
     */
    public function assertHasCookie($cookieName, $value = null, $encrypted = true)
    {
        $headers = $this->headers;

        $exist = false;

        foreach ($headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                $exist = true;
                break;
            }
        }

        PHPUnit::assertTrue($exist, "Cookie [{$cookieName}] not present on response.");

        if (! $exist || is_null($value)) {
            return $this;
        }

        $cookieValue = $cookie->getValue();

        $actual = $encrypted
            ? app('encrypter')->decrypt($cookieValue) : $cookieValue;

        PHPUnit::assertEquals(
            $actual, $value,
            "Cookie [{$cookieName}] was found, but value [{$actual}] does not match [{$value}]."
        );
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * @param  array  $data
     * @return void
     */
    public function assertHasJson(array $data)
    {
        PHPUnit::assertArraySubset($data, $this->decodeResponseJson());
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @return array
     */
    protected function decodeResponseJson()
    {
        $decodedResponse = json_decode($this->getContent(), true);

        if (is_null($decodedResponse) || $decodedResponse === false) {
            PHPUnit::fail('Invalid JSON was returned from the route. Perhaps an exception was thrown?');
        }

        return $decodedResponse;
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

        return trim($expected);
    }
}
