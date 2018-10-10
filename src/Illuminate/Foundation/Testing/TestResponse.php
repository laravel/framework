<?php

namespace Illuminate\Foundation\Testing;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Testing\Constraints\SeeInOrder;

/**
 * @mixin \Illuminate\Http\Response
 */
class TestResponse
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The response to delegate to.
     *
     * @var \Illuminate\Http\Response
     */
    public $baseResponse;

    /**
     * The streamed content of the response.
     *
     * @var string
     */
    protected $streamedContent;

    /**
     * Create a new test response instance.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function __construct($response)
    {
        $this->baseResponse = $response;
    }

    /**
     * Create a new TestResponse from another response.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return static
     */
    public static function fromBaseResponse($response)
    {
        return new static($response);
    }

    /**
     * Assert that the response has a successful status code.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertSuccessful(string $message = '')
    {
        PHPUnit::assertTrue(
            $this->isSuccessful(),
            $this->prependMessage(
                'Response status code ['.$this->getStatusCode().'] is not a successful status code.',
                $message
            )
        );

        return $this;
    }

    /**
     * Assert that the response has a 200 status code.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertOk(string $message = '')
    {
        PHPUnit::assertTrue(
            $this->isOk(),
            $this->prependMessage(
                'Response status code ['.$this->getStatusCode().'] does not match expected 200 status code.',
                $message
            )
        );

        return $this;
    }

    /**
     * Assert that the response has a not found status code.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertNotFound(string $message = '')
    {
        PHPUnit::assertTrue(
            $this->isNotFound(),
            $this->prependMessage(
                'Response status code ['.$this->getStatusCode().'] is not a not found status code.',
                $message
            )
        );

        return $this;
    }

    /**
     * Assert that the response has a forbidden status code.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertForbidden(string $message = '')
    {
        PHPUnit::assertTrue(
            $this->isForbidden(),
            $this->prependMessage(
                'Response status code ['.$this->getStatusCode().'] is not a forbidden status code.',
                $message
            )
        );

        return $this;
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param  int  $status
     * @param  string  $message
     * @return $this
     */
    public function assertStatus($status, string $message = '')
    {
        $actual = $this->getStatusCode();

        PHPUnit::assertTrue(
            $actual === $status,
            $this->prependMessage("Expected status code {$status} but received {$actual}.", $message)
        );

        return $this;
    }

    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param  string  $uri
     * @param  string  $message
     * @return $this
     */
    public function assertRedirect($uri = null, string $message = '')
    {
        PHPUnit::assertTrue(
            $this->isRedirect(),
            $this->prependMessage(
                'Response status code ['.$this->getStatusCode().'] is not a redirect status code.',
                $message
            )
        );

        if (! is_null($uri)) {
            $this->assertLocation($uri);
        }

        return $this;
    }

    /**
     * Asserts that the response contains the given header and equals the optional value.
     *
     * @param  string  $headerName
     * @param  mixed  $value
     * @param  string  $message
     * @return $this
     */
    public function assertHeader($headerName, $value = null, string $message = '')
    {
        PHPUnit::assertTrue(
            $this->headers->has($headerName),
            $this->prependMessage("Header [{$headerName}] not present on response.", $message)
        );

        $actual = $this->headers->get($headerName);

        if (! is_null($value)) {
            PHPUnit::assertEquals(
                $value, $this->headers->get($headerName),
                $this->prependMessage(
                    "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}].",
                    $message
                )
            );
        }

        return $this;
    }

    /**
     * Asserts that the response does not contains the given header.
     *
     * @param  string  $headerName
     * @param  string  $message
     * @return $this
     */
    public function assertHeaderMissing($headerName, string $message = '')
    {
        PHPUnit::assertFalse(
            $this->headers->has($headerName),
            $this->prependMessage("Unexpected header [{$headerName}] is present on response.", $message)
        );

        return $this;
    }

    /**
     * Assert that the current location header matches the given URI.
     *
     * @param  string  $uri
     * @param  string  $message
     * @return $this
     */
    public function assertLocation($uri, string $message = '')
    {
        PHPUnit::assertEquals(
            app('url')->to($uri), app('url')->to($this->headers->get('Location'), $message)
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @param  string  $message
     * @return $this
     */
    public function assertPlainCookie($cookieName, $value = null, string $message = '')
    {
        $this->assertCookie($cookieName, $value, false, false, $message);

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @param  bool  $encrypted
     * @param  bool  $unserialize
     * @param  string  $message
     * @return $this
     */
    public function assertCookie($cookieName,
                                 $value = null,
                                 $encrypted = true,
                                 $unserialize = false,
                                 string $message = '')
    {
        PHPUnit::assertNotNull(
            $cookie = $this->getCookie($cookieName),
            $this->prependMessage("Cookie [{$cookieName}] not present on response.", $message)
        );

        if (! $cookie || is_null($value)) {
            return $this;
        }

        $cookieValue = $cookie->getValue();

        $actual = $encrypted
            ? app('encrypter')->decrypt($cookieValue, $unserialize) : $cookieValue;

        PHPUnit::assertEquals(
            $value, $actual,
            $this->prependMessage(
                "Cookie [{$cookieName}] was found, but value [{$actual}] does not match [{$value}].",
                $message
            )
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and is expired.
     *
     * @param  string  $cookieName
     * @param  string  $message
     * @return $this
     */
    public function assertCookieExpired($cookieName, string $message)
    {
        PHPUnit::assertNotNull(
            $cookie = $this->getCookie($cookieName),
            $this->prependMessage("Cookie [{$cookieName}] not present on response.", $message)
        );

        $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

        PHPUnit::assertTrue(
            $expiresAt->lessThan(Carbon::now()),
            "Cookie [{$cookieName}] is not expired, it expires at [{$expiresAt}]."
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and is not expired.
     *
     * @param  string  $cookieName
     * @param  string  $message
     * @return $this
     */
    public function assertCookieNotExpired($cookieName, string $message)
    {
        PHPUnit::assertNotNull(
            $cookie = $this->getCookie($cookieName),
            $this->prependMessage("Cookie [{$cookieName}] not present on response.", $message)
        );

        $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

        PHPUnit::assertTrue(
            $expiresAt->greaterThan(Carbon::now()),
            $this->prependMessage("Cookie [{$cookieName}] is expired, it expired at [{$expiresAt}].", $message)
        );

        return $this;
    }

    /**
     * Asserts that the response does not contains the given cookie.
     *
     * @param  string  $cookieName
     * @param  string  $message
     * @return $this
     */
    public function assertCookieMissing($cookieName, string $message = '')
    {
        PHPUnit::assertNull(
            $this->getCookie($cookieName),
            $this->prependMessage("Cookie [{$cookieName}] is present on response.", $message)
        );

        return $this;
    }

    /**
     * Get the given cookie from the response.
     *
     * @param  string  $cookieName
     * @return \Symfony\Component\HttpFoundation\Cookie|null
     */
    protected function getCookie($cookieName)
    {
        foreach ($this->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return $cookie;
            }
        }
    }

    /**
     * Assert that the given string is contained within the response.
     *
     * @param  string  $value
     * @param  string  $message
     * @return $this
     */
    public function assertSee($value, string $message = '')
    {
        PHPUnit::assertContains((string) $value, $this->getContent(), $message);

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the response.
     *
     * @param  array  $values
     * @param  string  $message
     * @return $this
     */
    public function assertSeeInOrder(array $values, string $message = '')
    {
        PHPUnit::assertThat($values, new SeeInOrder($this->getContent()), $message);

        return $this;
    }

    /**
     * Assert that the given string is contained within the response text.
     *
     * @param  string  $value
     * @param  string  $message
     * @return $this
     */
    public function assertSeeText($value, string $message = '')
    {
        PHPUnit::assertContains((string) $value, strip_tags($this->getContent()), $message);

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the response text.
     *
     * @param  array  $values
     * @param  string  $message
     * @return $this
     */
    public function assertSeeTextInOrder(array $values, string $message = '')
    {
        PHPUnit::assertThat($values, new SeeInOrder(strip_tags($this->getContent())), $message);

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response.
     *
     * @param  string  $value
     * @param  string  $message
     * @return $this
     */
    public function assertDontSee($value, string $message = '')
    {
        PHPUnit::assertNotContains((string) $value, $this->getContent(), $message);

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response text.
     *
     * @param  string  $value
     * @param  string  $message
     * @return $this
     */
    public function assertDontSeeText($value, string $message = '')
    {
        PHPUnit::assertNotContains((string) $value, strip_tags($this->getContent()), $message);

        return $this;
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * @param  array  $data
     * @param  bool  $strict
     * @param  string  $message
     * @return $this
     */
    public function assertJson(array $data, $strict = false, string $message = '')
    {
        PHPUnit::assertArraySubset(
            $data, $this->decodeResponseJson(), $strict, $this->assertJsonMessage($data, $message)
        );

        return $this;
    }

    /**
     * Get the assertion message for assertJson.
     *
     * @param  array  $data
     * @param  string  $message
     * @return string
     */
    protected function assertJsonMessage(array $data, string $message = '')
    {
        $expected = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $actual = json_encode($this->decodeResponseJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $this->prependMessage(
            'Unable to find JSON: '.PHP_EOL.PHP_EOL.
            "[{$expected}]".PHP_EOL.PHP_EOL.
            'within response JSON:'.PHP_EOL.PHP_EOL.
            "[{$actual}].".PHP_EOL.PHP_EOL,
            $message
        );
    }

    /**
     * Assert that the response has the exact given JSON.
     *
     * @param  array  $data
     * @param  string  $message
     * @return $this
     */
    public function assertExactJson(array $data, string $message = '')
    {
        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        PHPUnit::assertEquals(json_encode(Arr::sortRecursive($data)), $actual, $message);

        return $this;
    }

    /**
     * Assert that the response contains the given JSON fragment.
     *
     * @param  array  $data
     * @param  string  $message
     * @return $this
     */
    public function assertJsonFragment(array $data, string $message = '')
    {
        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = $this->jsonSearchStrings($key, $value);

            PHPUnit::assertTrue(
                Str::contains($actual, $expected),
                $this->prependMessage(
                    'Unable to find JSON fragment: '.PHP_EOL.PHP_EOL.
                    '['.json_encode([$key => $value]).']'.PHP_EOL.PHP_EOL.
                    'within'.PHP_EOL.PHP_EOL.
                    "[{$actual}].",
                    $message
                )
            );
        }

        return $this;
    }

    /**
     * Assert that the response does not contain the given JSON fragment.
     *
     * @param  array  $data
     * @param  bool   $exact
     * @param  string  $message
     * @return $this
     */
    public function assertJsonMissing(array $data, $exact = false, string $message = '')
    {
        if ($exact) {
            return $this->assertJsonMissingExact($data, $message);
        }

        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $unexpected = $this->jsonSearchStrings($key, $value);

            PHPUnit::assertFalse(
                Str::contains($actual, $unexpected),
                $this->prependMessage(
                    'Found unexpected JSON fragment: '.PHP_EOL.PHP_EOL.
                    '['.json_encode([$key => $value]).']'.PHP_EOL.PHP_EOL.
                    'within'.PHP_EOL.PHP_EOL.
                    "[{$actual}].",
                    $message
                )
            );
        }

        return $this;
    }

    /**
     * Assert that the response does not contain the exact JSON fragment.
     *
     * @param  array  $data
     * @param  string  $message
     * @return $this
     */
    public function assertJsonMissingExact(array $data, string $message = '')
    {
        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $unexpected = $this->jsonSearchStrings($key, $value);

            if (! Str::contains($actual, $unexpected)) {
                return $this;
            }
        }

        PHPUnit::fail(
            $this->prependMessage(
                'Found unexpected JSON fragment: '.PHP_EOL.PHP_EOL.
                '['.json_encode($data).']'.PHP_EOL.PHP_EOL.
                'within'.PHP_EOL.PHP_EOL.
                "[{$actual}].",
                $message
            )
        );
    }

    /**
     * Get the strings we need to search for when examining the JSON.
     *
     * @param  string  $key
     * @param  string  $value
     * @return array
     */
    protected function jsonSearchStrings($key, $value)
    {
        $needle = substr(json_encode([$key => $value]), 1, -1);

        return [
            $needle.']',
            $needle.'}',
            $needle.',',
        ];
    }

    /**
     * Assert that the response has a given JSON structure.
     *
     * @param  array|null  $structure
     * @param  array|null  $responseData
     * @param  string  $message
     * @return $this
     */
    public function assertJsonStructure(array $structure = null, $responseData = null, string $message = '')
    {
        if (is_null($structure)) {
            return $this->assertExactJson($this->json());
        }

        if (is_null($responseData)) {
            $responseData = $this->decodeResponseJson();
        }

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                PHPUnit::assertInternalType('array', $responseData, $message);

                foreach ($responseData as $responseDataItem) {
                    $this->assertJsonStructure($structure['*'], $responseDataItem, $message);
                }
            } elseif (is_array($value)) {
                PHPUnit::assertArrayHasKey($key, $responseData);

                $this->assertJsonStructure($structure[$key], $responseData[$key], $message);
            } else {
                PHPUnit::assertArrayHasKey($value, $responseData, $message);
            }
        }

        return $this;
    }

    /**
     * Assert that the response JSON has the expected count of items at the given key.
     *
     * @param  int  $count
     * @param  string|null  $key
     * @param  string  $message
     * @return $this
     */
    public function assertJsonCount(int $count, $key = null, string $message = '')
    {
        if ($key) {
            PHPUnit::assertCount(
                $count, data_get($this->json(), $key),
                $this->prependMessage(
                    "Failed to assert that the response count matched the expected {$count}",
                    $message
                )
            );

            return $this;
        }

        PHPUnit::assertCount($count,
            $this->json(),
            $this->prependMessage(
                "Failed to assert that the response count matched the expected {$count}",
                $message
            )
        );

        return $this;
    }

    /**
     * Assert that the response has the given JSON validation errors for the given keys.
     *
     * @param  string|array  $keys
     * @param  string  $message
     * @return $this
     */
    public function assertJsonValidationErrors($keys, string $message = '')
    {
        $errors = $this->json()['errors'];

        foreach (Arr::wrap($keys) as $key) {
            PHPUnit::assertTrue(
                isset($errors[$key]),
                $this->prependMessage(
                    "Failed to find a validation error in the response for key: '{$key}'",
                    $message
                )
            );
        }

        return $this;
    }

    /**
     * Assert that the response has no JSON validation errors for the given keys.
     *
     * @param  string|array  $keys
     * @param  string  $message
     * @return $this
     */
    public function assertJsonMissingValidationErrors($keys, string $message = '')
    {
        $json = $this->json();

        if (! array_key_exists('errors', $json)) {
            PHPUnit::assertArrayNotHasKey('errors', $json, $message);

            return $this;
        }

        $errors = $json['errors'];

        foreach (Arr::wrap($keys) as $key) {
            PHPUnit::assertFalse(
                isset($errors[$key]),
                $this->prependMessage(
                    "Found unexpected validation error for key: '{$key}'",
                    $message
                )
            );
        }

        return $this;
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function decodeResponseJson($key = null)
    {
        $decodedResponse = json_decode($this->getContent(), true);

        if (is_null($decodedResponse) || $decodedResponse === false) {
            if ($this->exception) {
                throw $this->exception;
            } else {
                PHPUnit::fail('Invalid JSON was returned from the route.');
            }
        }

        return data_get($decodedResponse, $key);
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function json($key = null)
    {
        return $this->decodeResponseJson($key);
    }

    /**
     * Assert that the response view equals the given value.
     *
     * @param  string $value
     * @param  string  $message
     * @return $this
     */
    public function assertViewIs($value, string $message = '')
    {
        $this->ensureResponseHasView();

        PHPUnit::assertEquals($value, $this->original->getName(), $message);

        return $this;
    }

    /**
     * Assert that the response view has a given piece of bound data.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  string  $message
     * @return $this
     */
    public function assertViewHas($key, $value = null, string $message = '')
    {
        if (is_array($key)) {
            return $this->assertViewHasAll($key, $message);
        }

        $this->ensureResponseHasView();

        if (is_null($value)) {
            PHPUnit::assertArrayHasKey($key, $this->original->getData(), $message);
        } elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->original->$key), $message);
        } elseif ($value instanceof Model) {
            PHPUnit::assertTrue($value->is($this->original->$key), $message);
        } else {
            PHPUnit::assertEquals($value, $this->original->$key, $message);
        }

        return $this;
    }

    /**
     * Assert that the response view has a given list of bound data.
     *
     * @param  array  $bindings
     * @param  string  $message
     * @return $this
     */
    public function assertViewHasAll(array $bindings, string $message = '')
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertViewHas($value, $message);
            } else {
                $this->assertViewHas($key, $value, $message);
            }
        }

        return $this;
    }

    /**
     * Get a piece of data from the original view.
     *
     * @param  string  $key
     * @return mixed
     */
    public function viewData($key)
    {
        $this->ensureResponseHasView();

        return $this->original->$key;
    }

    /**
     * Assert that the response view is missing a piece of bound data.
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function assertViewMissing($key, string $message = '')
    {
        $this->ensureResponseHasView();

        PHPUnit::assertArrayNotHasKey($key, $this->original->getData(), $message);

        return $this;
    }

    /**
     * Ensure that the response has a view as its original content.
     *
     * @return $this
     */
    protected function ensureResponseHasView()
    {
        if (! isset($this->original) || ! $this->original instanceof View) {
            return PHPUnit::fail('The response is not a view.');
        }

        return $this;
    }

    /**
     * Assert that the session has a given value.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  string  $message
     * @return $this
     */
    public function assertSessionHas($key, $value = null, string $message = '')
    {
        if (is_array($key)) {
            return $this->assertSessionHasAll($key);
        }

        if (is_null($value)) {
            PHPUnit::assertTrue(
                $this->session()->has($key),
                $this->prependMessage("Session is missing expected key [{$key}].", $message)
            );
        } else {
            PHPUnit::assertEquals($value, $this->session()->get($key));
        }

        return $this;
    }

    /**
     * Assert that the session has a given list of values.
     *
     * @param  array  $bindings
     * @param  string  $message
     * @return $this
     */
    public function assertSessionHasAll(array $bindings, string $message = '')
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertSessionHas($value, $message);
            } else {
                $this->assertSessionHas($key, $value, $message);
            }
        }

        return $this;
    }

    /**
     * Assert that the session has the given errors.
     *
     * @param  string|array  $keys
     * @param  mixed  $format
     * @param  string  $errorBag
     * @param  string  $message
     * @return $this
     */
    public function assertSessionHasErrors($keys = [], $format = null, $errorBag = 'default', string $message = '')
    {
        $this->assertSessionHas('errors');

        $keys = (array) $keys;

        $errors = $this->session()->get('errors')->getBag($errorBag);

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                PHPUnit::assertTrue(
                    $errors->has($value),
                    $this->prependMessage("Session missing error: $value", $message)
                );
            } else {
                PHPUnit::assertContains($value, $errors->get($key, $format), $message);
            }
        }

        return $this;
    }

    /**
     * Assert that the session is missing the given errors.
     *
     * @param  string|array  $keys
     * @param  string  $format
     * @param  string  $errorBag
     * @param  string  $message
     * @return $this
     */
    public function assertSessionDoesntHaveErrors($keys = [],
                                                  $format = null,
                                                  $errorBag = 'default',
                                                  string $message = '')
    {
        $keys = (array) $keys;

        if (empty($keys)) {
            return $this->assertSessionMissing('errors');
        }

        $errors = $this->session()->get('errors')->getBag($errorBag);

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                PHPUnit::assertFalse(
                    $errors->has($value),
                    $this->prependMessage("Session has unexpected error: $value", $message)
                );
            } else {
                PHPUnit::assertNotContains($value, $errors->get($key, $format), $message);
            }
        }

        return $this;
    }

    /**
     * Assert that the session has no errors.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertSessionHasNoErrors(string $message = '')
    {
        $hasErrors = $this->session()->has('errors');

        $errors = $hasErrors ? $this->session()->get('errors')->all() : [];

        PHPUnit::assertFalse(
            $hasErrors,
            $this->prependMessage(
                'Session has unexpected errors: '.PHP_EOL.PHP_EOL.
                json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                $message
            )
        );

        return $this;
    }

    /**
     * Assert that the session has the given errors.
     *
     * @param  string  $errorBag
     * @param  string|array  $keys
     * @param  mixed  $format
     * @param  string  $message
     * @return $this
     */
    public function assertSessionHasErrorsIn($errorBag, $keys = [], $format = null, string $message = '')
    {
        return $this->assertSessionHasErrors($keys, $format, $errorBag, $message);
    }

    /**
     * Assert that the session does not have a given key.
     *
     * @param  string|array  $key
     * @param  string  $message
     * @return $this
     */
    public function assertSessionMissing($key, string $message = '')
    {
        if (is_array($key)) {
            foreach ($key as $value) {
                $this->assertSessionMissing($value, $message);
            }
        } else {
            PHPUnit::assertFalse(
                $this->session()->has($key),
                $this->prependMessage("Session has unexpected key [{$key}].", $message)
            );
        }

        return $this;
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

    /**
     * Dump the content from the response.
     *
     * @return void
     */
    public function dump()
    {
        $content = $this->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        dd($content);
    }

    /**
     * Get the streamed content from the response.
     *
     * @return string
     */
    public function streamedContent()
    {
        if (! is_null($this->streamedContent)) {
            return $this->streamedContent;
        }

        if (! $this->baseResponse instanceof StreamedResponse) {
            PHPUnit::fail('The response is not a streamed response.');
        }

        ob_start();

        $this->sendContent();

        return $this->streamedContent = ob_get_clean();
    }

    /**
     * Dynamically access base response parameters.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->baseResponse->{$key};
    }

    /**
     * Proxy isset() checks to the underlying base response.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __isset($key)
    {
        return isset($this->baseResponse->{$key});
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the base response.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $args);
        }

        return $this->baseResponse->{$method}(...$args);
    }

    /**
     * Prepend a message to the default one if its not empty.
     *
     * @param string $defaultMessage
     * @param string $message
     *
     * @return string
     */
    protected function prependMessage(string $defaultMessage, string $message): string
    {
        return ($message !== '' ? $message.PHP_EOL : '').$defaultMessage;
    }
}
