<?php

namespace Illuminate\Foundation\Testing;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Foundation\Testing\Assert as PHPUnit;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Testing\Constraints\SeeInOrder;

/**
 * @mixin \Illuminate\Http\Response
 */
class TestResponse
{
    use Tappable, Macroable {
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
     * @return $this
     */
    public function assertSuccessful()
    {
        PHPUnit::assertTrue(
            $this->isSuccessful(),
            'Response status code ['.$this->getStatusCode().'] is not a successful status code.'
        );

        return $this;
    }

    /**
     * Assert that the response has a 200 status code.
     *
     * @return $this
     */
    public function assertOk()
    {
        PHPUnit::assertTrue(
            $this->isOk(),
            'Response status code ['.$this->getStatusCode().'] does not match expected 200 status code.'
        );

        return $this;
    }

    /**
     * Assert that the response has a not found status code.
     *
     * @return $this
     */
    public function assertNotFound()
    {
        PHPUnit::assertTrue(
            $this->isNotFound(),
            'Response status code ['.$this->getStatusCode().'] is not a not found status code.'
        );

        return $this;
    }

    /**
     * Assert that the response has a forbidden status code.
     *
     * @return $this
     */
    public function assertForbidden()
    {
        PHPUnit::assertTrue(
            $this->isForbidden(),
            'Response status code ['.$this->getStatusCode().'] is not a forbidden status code.'
        );

        return $this;
    }

    /**
     * Assert that the response has an unauthorized status code.
     *
     * @return $this
     */
    public function assertUnauthorized()
    {
        $actual = $this->getStatusCode();

        PHPUnit::assertTrue(
            401 === $actual,
            'Response status code ['.$actual.'] is not an unauthorized status code.'
        );

        return $this;
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertStatus($status)
    {
        $actual = $this->getStatusCode();

        PHPUnit::assertTrue(
            $actual === $status,
            "Expected status code {$status} but received {$actual}."
        );

        return $this;
    }

    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param  string|null  $uri
     * @return $this
     */
    public function assertRedirect($uri = null)
    {
        PHPUnit::assertTrue(
            $this->isRedirect(), 'Response status code ['.$this->getStatusCode().'] is not a redirect status code.'
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
                $value, $this->headers->get($headerName),
                "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}]."
            );
        }

        return $this;
    }

    /**
     * Asserts that the response does not contains the given header.
     *
     * @param  string  $headerName
     * @return $this
     */
    public function assertHeaderMissing($headerName)
    {
        PHPUnit::assertFalse(
            $this->headers->has($headerName), "Unexpected header [{$headerName}] is present on response."
        );

        return $this;
    }

    /**
     * Assert that the current location header matches the given URI.
     *
     * @param  string  $uri
     * @return $this
     */
    public function assertLocation($uri)
    {
        PHPUnit::assertEquals(
            app('url')->to($uri), app('url')->to($this->headers->get('Location'))
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @return $this
     */
    public function assertPlainCookie($cookieName, $value = null)
    {
        $this->assertCookie($cookieName, $value, false);

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @param  bool  $encrypted
     * @param  bool  $unserialize
     * @return $this
     */
    public function assertCookie($cookieName, $value = null, $encrypted = true, $unserialize = false)
    {
        PHPUnit::assertNotNull(
            $cookie = $this->getCookie($cookieName),
            "Cookie [{$cookieName}] not present on response."
        );

        if (! $cookie || is_null($value)) {
            return $this;
        }

        $cookieValue = $cookie->getValue();

        $actual = $encrypted
            ? app('encrypter')->decrypt($cookieValue, $unserialize) : $cookieValue;

        PHPUnit::assertEquals(
            $value, $actual,
            "Cookie [{$cookieName}] was found, but value [{$actual}] does not match [{$value}]."
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and is expired.
     *
     * @param  string  $cookieName
     * @return $this
     */
    public function assertCookieExpired($cookieName)
    {
        PHPUnit::assertNotNull(
            $cookie = $this->getCookie($cookieName),
            "Cookie [{$cookieName}] not present on response."
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
     * @return $this
     */
    public function assertCookieNotExpired($cookieName)
    {
        PHPUnit::assertNotNull(
            $cookie = $this->getCookie($cookieName),
            "Cookie [{$cookieName}] not present on response."
        );

        $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

        PHPUnit::assertTrue(
            $expiresAt->greaterThan(Carbon::now()),
            "Cookie [{$cookieName}] is expired, it expired at [{$expiresAt}]."
        );

        return $this;
    }

    /**
     * Asserts that the response does not contains the given cookie.
     *
     * @param  string  $cookieName
     * @return $this
     */
    public function assertCookieMissing($cookieName)
    {
        PHPUnit::assertNull(
            $this->getCookie($cookieName),
            "Cookie [{$cookieName}] is present on response."
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
     * @return $this
     */
    public function assertSee($value)
    {
        PHPUnit::assertStringContainsString((string) $value, $this->getContent());

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the response.
     *
     * @param  array  $values
     * @return $this
     */
    public function assertSeeInOrder(array $values)
    {
        PHPUnit::assertThat($values, new SeeInOrder($this->getContent()));

        return $this;
    }

    /**
     * Assert that the given string is contained within the response text.
     *
     * @param  string  $value
     * @return $this
     */
    public function assertSeeText($value)
    {
        PHPUnit::assertStringContainsString((string) $value, strip_tags($this->getContent()));

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the response text.
     *
     * @param  array  $values
     * @return $this
     */
    public function assertSeeTextInOrder(array $values)
    {
        PHPUnit::assertThat($values, new SeeInOrder(strip_tags($this->getContent())));

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response.
     *
     * @param  string  $value
     * @return $this
     */
    public function assertDontSee($value)
    {
        PHPUnit::assertStringNotContainsString((string) $value, $this->getContent());

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response text.
     *
     * @param  string  $value
     * @return $this
     */
    public function assertDontSeeText($value)
    {
        PHPUnit::assertStringNotContainsString((string) $value, strip_tags($this->getContent()));

        return $this;
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * @param  array  $data
     * @param  bool  $strict
     * @return $this
     */
    public function assertJson(array $data, $strict = false)
    {
        PHPUnit::assertArraySubset(
            $data, $this->decodeResponseJson(), $strict, $this->assertJsonMessage($data)
        );

        return $this;
    }

    /**
     * Get the assertion message for assertJson.
     *
     * @param  array  $data
     * @return string
     */
    protected function assertJsonMessage(array $data)
    {
        $expected = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $actual = json_encode($this->decodeResponseJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return 'Unable to find JSON: '.PHP_EOL.PHP_EOL.
            "[{$expected}]".PHP_EOL.PHP_EOL.
            'within response JSON:'.PHP_EOL.PHP_EOL.
            "[{$actual}].".PHP_EOL.PHP_EOL;
    }

    /**
     * Assert that the response has the exact given JSON.
     *
     * @param  array  $data
     * @return $this
     */
    public function assertExactJson(array $data)
    {
        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        PHPUnit::assertEquals(json_encode(Arr::sortRecursive($data)), $actual);

        return $this;
    }

    /**
     * Assert that the response contains the given JSON fragment.
     *
     * @param  array  $data
     * @return $this
     */
    public function assertJsonFragment(array $data)
    {
        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = $this->jsonSearchStrings($key, $value);

            PHPUnit::assertTrue(
                Str::contains($actual, $expected),
                'Unable to find JSON fragment: '.PHP_EOL.PHP_EOL.
                '['.json_encode([$key => $value]).']'.PHP_EOL.PHP_EOL.
                'within'.PHP_EOL.PHP_EOL.
                "[{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the response does not contain the given JSON fragment.
     *
     * @param  array  $data
     * @param  bool   $exact
     * @return $this
     */
    public function assertJsonMissing(array $data, $exact = false)
    {
        if ($exact) {
            return $this->assertJsonMissingExact($data);
        }

        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $unexpected = $this->jsonSearchStrings($key, $value);

            PHPUnit::assertFalse(
                Str::contains($actual, $unexpected),
                'Found unexpected JSON fragment: '.PHP_EOL.PHP_EOL.
                '['.json_encode([$key => $value]).']'.PHP_EOL.PHP_EOL.
                'within'.PHP_EOL.PHP_EOL.
                "[{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the response does not contain the exact JSON fragment.
     *
     * @param  array  $data
     * @return $this
     */
    public function assertJsonMissingExact(array $data)
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
            'Found unexpected JSON fragment: '.PHP_EOL.PHP_EOL.
            '['.json_encode($data).']'.PHP_EOL.PHP_EOL.
            'within'.PHP_EOL.PHP_EOL.
            "[{$actual}]."
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
     * @return $this
     */
    public function assertJsonStructure(array $structure = null, $responseData = null)
    {
        if (is_null($structure)) {
            return $this->assertExactJson($this->json());
        }

        if (is_null($responseData)) {
            $responseData = $this->decodeResponseJson();
        }

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                PHPUnit::assertIsArray($responseData);

                foreach ($responseData as $responseDataItem) {
                    $this->assertJsonStructure($structure['*'], $responseDataItem);
                }
            } elseif (is_array($value)) {
                PHPUnit::assertArrayHasKey($key, $responseData);

                $this->assertJsonStructure($structure[$key], $responseData[$key]);
            } else {
                PHPUnit::assertArrayHasKey($value, $responseData);
            }
        }

        return $this;
    }

    /**
     * Assert that the response JSON has the expected count of items at the given key.
     *
     * @param  int  $count
     * @param  string|null  $key
     * @return $this
     */
    public function assertJsonCount(int $count, $key = null)
    {
        if ($key) {
            PHPUnit::assertCount(
                $count, data_get($this->json(), $key),
                "Failed to assert that the response count matched the expected {$count}"
            );

            return $this;
        }

        PHPUnit::assertCount($count,
            $this->json(),
            "Failed to assert that the response count matched the expected {$count}"
        );

        return $this;
    }

    /**
     * Assert that the response has the given JSON validation errors.
     *
     * @param  string|array  $errors
     * @return $this
     */
    public function assertJsonValidationErrors($errors)
    {
        $errors = Arr::wrap($errors);

        PHPUnit::assertNotEmpty($errors, 'No validation errors were provided.');

        $jsonErrors = $this->json()['errors'] ?? [];

        $errorMessage = $jsonErrors
                ? 'Response has the following JSON validation errors:'.
                        PHP_EOL.PHP_EOL.json_encode($jsonErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
                : 'Response does not have JSON validation errors.';

        foreach ($errors as $key => $value) {
            PHPUnit::assertArrayHasKey(
                (is_int($key)) ? $value : $key,
                $jsonErrors,
                "Failed to find a validation error in the response for key: '{$value}'".PHP_EOL.PHP_EOL.$errorMessage
            );

            if (! is_int($key)) {
                $hasError = false;

                foreach (Arr::wrap($jsonErrors[$key]) as $jsonErrorMessage) {
                    if (Str::contains($jsonErrorMessage, $value)) {
                        $hasError = true;

                        break;
                    }
                }

                if (! $hasError) {
                    PHPUnit::fail(
                        "Failed to find a validation error in the response for key and message: '$key' => '$value'".PHP_EOL.PHP_EOL.$errorMessage
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Assert that the response has no JSON validation errors for the given keys.
     *
     * @param  string|array|null  $keys
     * @return $this
     */
    public function assertJsonMissingValidationErrors($keys = null)
    {
        if ($this->getContent() === '') {
            PHPUnit::assertTrue(true);

            return $this;
        }

        $json = $this->json();

        if (! array_key_exists('errors', $json)) {
            PHPUnit::assertArrayNotHasKey('errors', $json);

            return $this;
        }

        $errors = $json['errors'];

        if (is_null($keys) && count($errors) > 0) {
            PHPUnit::fail(
                'Response has unexpected validation errors: '.PHP_EOL.PHP_EOL.
                json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        foreach (Arr::wrap($keys) as $key) {
            PHPUnit::assertFalse(
                isset($errors[$key]),
                "Found unexpected validation error for key: '{$key}'"
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
     * @return $this
     */
    public function assertViewIs($value)
    {
        $this->ensureResponseHasView();

        PHPUnit::assertEquals($value, $this->original->name());

        return $this;
    }

    /**
     * Assert that the response view has a given piece of bound data.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function assertViewHas($key, $value = null)
    {
        if (is_array($key)) {
            return $this->assertViewHasAll($key);
        }

        $this->ensureResponseHasView();

        if (is_null($value)) {
            PHPUnit::assertArrayHasKey($key, $this->original->gatherData());
        } elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->original->gatherData()[$key]));
        } elseif ($value instanceof Model) {
            PHPUnit::assertTrue($value->is($this->original->gatherData()[$key]));
        } else {
            PHPUnit::assertEquals($value, $this->original->gatherData()[$key]);
        }

        return $this;
    }

    /**
     * Assert that the response view has a given list of bound data.
     *
     * @param  array  $bindings
     * @return $this
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

        return $this->original->gatherData()[$key];
    }

    /**
     * Assert that the response view is missing a piece of bound data.
     *
     * @param  string  $key
     * @return $this
     */
    public function assertViewMissing($key)
    {
        $this->ensureResponseHasView();

        PHPUnit::assertArrayNotHasKey($key, $this->original->gatherData());

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
     * @return $this
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
        } elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->session()->get($key)));
        } else {
            PHPUnit::assertEquals($value, $this->session()->get($key));
        }

        return $this;
    }

    /**
     * Assert that the session has a given list of values.
     *
     * @param  array  $bindings
     * @return $this
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

        return $this;
    }

    /**
     * Assert that the session has a given value in the flashed input array.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function assertSessionHasInput($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if (is_int($k)) {
                    $this->assertSessionHasInput($v);
                } else {
                    $this->assertSessionHasInput($k, $v);
                }
            }

            return $this;
        }

        if (is_null($value)) {
            PHPUnit::assertTrue(
                $this->session()->getOldInput($key),
                "Session is missing expected key [{$key}]."
            );
        } elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->session()->getOldInput($key)));
        } else {
            PHPUnit::assertEquals($value, $this->session()->getOldInput($key));
        }

        return $this;
    }

    /**
     * Assert that the session has the given errors.
     *
     * @param  string|array  $keys
     * @param  mixed  $format
     * @param  string  $errorBag
     * @return $this
     */
    public function assertSessionHasErrors($keys = [], $format = null, $errorBag = 'default')
    {
        $this->assertSessionHas('errors');

        $keys = (array) $keys;

        $errors = $this->session()->get('errors')->getBag($errorBag);

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                PHPUnit::assertTrue($errors->has($value), "Session missing error: $value");
            } else {
                PHPUnit::assertContains($value, $errors->get($key, $format));
            }
        }

        return $this;
    }

    /**
     * Assert that the session is missing the given errors.
     *
     * @param  string|array  $keys
     * @param  string|null  $format
     * @param  string  $errorBag
     * @return $this
     */
    public function assertSessionDoesntHaveErrors($keys = [], $format = null, $errorBag = 'default')
    {
        $keys = (array) $keys;

        if (empty($keys)) {
            return $this->assertSessionHasNoErrors();
        }

        if (is_null($this->session()->get('errors'))) {
            PHPUnit::assertTrue(true);

            return $this;
        }

        $errors = $this->session()->get('errors')->getBag($errorBag);

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                PHPUnit::assertFalse($errors->has($value), "Session has unexpected error: $value");
            } else {
                PHPUnit::assertNotContains($value, $errors->get($key, $format));
            }
        }

        return $this;
    }

    /**
     * Assert that the session has no errors.
     *
     * @return $this
     */
    public function assertSessionHasNoErrors()
    {
        $hasErrors = $this->session()->has('errors');

        $errors = $hasErrors ? $this->session()->get('errors')->all() : [];

        PHPUnit::assertFalse(
            $hasErrors,
            'Session has unexpected errors: '.PHP_EOL.PHP_EOL.
            json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $this;
    }

    /**
     * Assert that the session has the given errors.
     *
     * @param  string  $errorBag
     * @param  string|array  $keys
     * @param  mixed  $format
     * @return $this
     */
    public function assertSessionHasErrorsIn($errorBag, $keys = [], $format = null)
    {
        return $this->assertSessionHasErrors($keys, $format, $errorBag);
    }

    /**
     * Assert that the session does not have a given key.
     *
     * @param  string|array  $key
     * @return $this
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
     * @return $this
     */
    public function dump()
    {
        $content = $this->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        dump($content);

        return $this;
    }

    /**
     * Dump the headers from the response.
     *
     * @return $this
     */
    public function dumpHeaders()
    {
        dump($this->headers->all());

        return $this;
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
}
