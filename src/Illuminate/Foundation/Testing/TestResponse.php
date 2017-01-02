<?php

namespace Illuminate\Foundation\Testing;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
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

        if (isset($response->original)) {
            $testResponse->original = $response->original;
        }

        if (isset($response->exception)) {
            $testResponse->exception = $response->exception;
        }

        return $testResponse;
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param  int  $status
     * @return void
     */
    public function assertStatus($status)
    {
        $actual = $this->getStatusCode();

        PHPUnit::assertTrue(
            $actual === $status,
            "Expected status code {$status} but received {$actual}."
        );
    }

    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param  string  $uri
     * @return void
     */
    public function assertRedirect($uri)
    {
        PHPUnit::assertTrue(
            $this->isRedirect(), 'Response status code ['.$this->status().'] is not a redirect status code.'
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
    public function assertHeader($headerName, $value = null)
    {
        PHPUnit::assertTrue(
            $this->headers->has($headerName), "Header [{$headerName}] not present on response."
        );

        $actual = $this->headers->get($headerName);

        if (! is_null($value)) {
            PHPUnit::assertEquals(
                $this->headers->get($headerName), $value,
                "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}]."
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
    public function assertPlainCookie($cookieName, $value = null)
    {
        $this->assertCookie($cookieName, $value, false);
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @param  bool  $encrypted
     * @return void
     */
    public function assertCookie($cookieName, $value = null, $encrypted = true)
    {
        PHPUnit::assertTrue(
            $exists = $this->hasCookie($cookieName),
            "Cookie [{$cookieName}] not present on response."
        );

        if (! $exists || is_null($value)) {
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
     * Determine if the response has a given cookie.
     *
     * @param  string  $cookieName
     * @return bool
     */
    protected function hasCookie($cookieName)
    {
        foreach ($this->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return true;
            }
        }

        return false;
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
            if ($this->exception) {
                throw $this->exception;
            } else {
                PHPUnit::fail('Invalid JSON was returned from the route.');
            }
        }

        return $decodedResponse;
    }

    /**
     * Assert that the response view has a given piece of bound data.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function assertViewHas($key, $value = null)
    {
        if (is_array($key)) {
            return $this->assertViewHasAll($key);
        }

        $this->ensureResponseHasView();

        if (is_null($value)) {
            PHPUnit::assertArrayHasKey($key, $this->original->getData());
        } elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->original->$key));
        } else {
            PHPUnit::assertEquals($value, $this->original->$key);
        }
    }

    /**
     * Assert that the response view has a given list of bound data.
     *
     * @param  array  $bindings
     * @return void
     */
    public function assertViewHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertViewHas($value);
            } else {
                $this->assertViewHas($key, $value);
            }
        }
    }

    /**
     * Assert that the response view is missing a piece of bound data.
     *
     * @param  string  $key
     * @return void
     */
    public function assertViewMissing($key)
    {
        $this->ensureResponseHasView();

        PHPUnit::assertArrayNotHasKey($key, $this->original->getData());
    }

    /**
     * Ensure that the response has a view as its original content.
     *
     * @return void
     */
    protected function ensureResponseHasView()
    {
        if (! isset($this->original) || ! $this->original instanceof View) {
            return PHPUnit::fail('The response is not a view.');
        }
    }

    /**
     * Assert that the session has a given value.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function assertSessionHas($key, $value = null)
    {
        if (is_array($key)) {
            return $this->assertSessionHasAll($key);
        }

        if (is_null($value)) {
            PHPUnit::assertTrue(
                $this->session()->has($key),
                "Session is missing expected key [{$key}]."
            );
        } else {
            PHPUnit::assertEquals($value, app('session.store')->get($key));
        }
    }

    /**
     * Assert that the session has a given list of values.
     *
     * @param  array  $bindings
     * @return void
     */
    public function assertSessionHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertSessionHas($value);
            } else {
                $this->assertSessionHas($key, $value);
            }
        }
    }

    /**
     * Assert that the session does not have a given key.
     *
     * @param  string|array  $key
     * @return void
     */
    public function assertSessionMissing($key)
    {
        if (is_array($key)) {
            foreach ($key as $value) {
                $this->assertSessionMissing($value);
            }
        } else {
            PHPUnit::assertFalse(
                $this->session()->has($key),
                "Session has unexpected key [{$key}]."
            );
        }
    }

    /**
     * Get the current session store.
     *
     * @return \Illuminate\Session\Store
     */
    protected function session()
    {
        return app('session.store');
    }
}
