<?php

namespace Illuminate\Testing;

use ArrayAccess;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Testing\Assert as PHPUnit;
use Illuminate\Testing\Constraints\SeeInOrder;
use Illuminate\Testing\Fluent\AssertableJson;
use LogicException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @mixin \Illuminate\Http\Response
 */
class TestResponse implements ArrayAccess
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
     * The collection of logged exceptions for the request.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $exceptions;

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
        $this->exceptions = new Collection;
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
            $this->statusMessageWithDetails('>=200, <300', $this->getStatusCode())
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
        return $this->assertStatus(200);
    }

    /**
     * Assert that the response has a 201 status code.
     *
     * @return $this
     */
    public function assertCreated()
    {
        return $this->assertStatus(201);
    }

    /**
     * Assert that the response has the given status code and no content.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertNoContent($status = 204)
    {
        $this->assertStatus($status);

        PHPUnit::assertEmpty($this->getContent(), 'Response content is not empty.');

        return $this;
    }

    /**
     * Assert that the response has a not found status code.
     *
     * @return $this
     */
    public function assertNotFound()
    {
        return $this->assertStatus(404);
    }

    /**
     * Assert that the response has a forbidden status code.
     *
     * @return $this
     */
    public function assertForbidden()
    {
        return $this->assertStatus(403);
    }

    /**
     * Assert that the response has an unauthorized status code.
     *
     * @return $this
     */
    public function assertUnauthorized()
    {
        return $this->assertStatus(401);
    }

    /**
     * Assert that the response has a 422 status code.
     *
     * @return $this
     */
    public function assertUnprocessable()
    {
        return $this->assertStatus(422);
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertStatus($status)
    {
        $message = $this->statusMessageWithDetails($status, $actual = $this->getStatusCode());

        PHPUnit::assertSame($actual, $status, $message);

        return $this;
    }

    /**
     * Get an assertion message for a status assertion containing extra details when available.
     *
     * @param  string|int  $expected
     * @param  string|int  $actual
     * @return string
     */
    protected function statusMessageWithDetails($expected, $actual)
    {
        $lastException = $this->exceptions->last();

        if ($lastException) {
            return $this->statusMessageWithException($expected, $actual, $lastException);
        }

        if ($this->baseResponse instanceof RedirectResponse) {
            $session = $this->baseResponse->getSession();

            if (! is_null($session) && $session->has('errors')) {
                return $this->statusMessageWithErrors($expected, $actual, $session->get('errors')->all());
            }
        }

        if ($this->baseResponse->headers->get('Content-Type') === 'application/json') {
            $testJson = new AssertableJsonString($this->getContent());

            if (isset($testJson['errors'])) {
                return $this->statusMessageWithErrors($expected, $actual, $testJson->json());
            }
        }

        return "Expected response status code [{$expected}] but received {$actual}.";
    }

    /**
     * Get an assertion message for a status assertion that has an unexpected exception.
     *
     * @param  string|int  $expected
     * @param  string|int  $actual
     * @param  \Throwable  $exception
     * @return string
     */
    protected function statusMessageWithException($expected, $actual, $exception)
    {
        $message = $exception->getMessage();

        $exception = (string) $exception;

        return <<<EOF
Expected response status code [$expected] but received $actual.

The following exception occurred during the request:

$exception

----------------------------------------------------------------------------------

$message

EOF;
    }

    /**
     * Get an assertion message for a status assertion that contained errors.
     *
     * @param  string|int  $expected
     * @param  string|int  $actual
     * @param  array  $errors
     * @return string
     */
    protected function statusMessageWithErrors($expected, $actual, $errors)
    {
        $errors = $this->baseResponse->headers->get('Content-Type') === 'application/json'
            ? json_encode($errors, JSON_PRETTY_PRINT)
            : implode(PHP_EOL, Arr::flatten($errors));

        return <<<EOF
Expected response status code [$expected] but received $actual.

The following errors occurred during the request:

$errors

EOF;
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
            $this->isRedirect(),
            $this->statusMessageWithDetails('201, 301, 302, 303, 307, 308', $this->getStatusCode()),
        );

        if (! is_null($uri)) {
            $this->assertLocation($uri);
        }

        return $this;
    }

    /**
     * Assert whether the response is redirecting to a URI that contains the given URI.
     *
     * @param  string  $uri
     * @return $this
     */
    public function assertRedirectContains($uri)
    {
        PHPUnit::assertTrue(
            $this->isRedirect(),
            $this->statusMessageWithDetails('201, 301, 302, 303, 307, 308', $this->getStatusCode()),
        );

        PHPUnit::assertTrue(
            Str::contains($this->headers->get('Location'), $uri), 'Redirect location ['.$this->headers->get('Location').'] does not contain ['.$uri.'].'
        );

        return $this;
    }

    /**
     * Assert whether the response is redirecting to a given signed route.
     *
     * @param  string|null  $name
     * @param  mixed  $parameters
     * @return $this
     */
    public function assertRedirectToSignedRoute($name = null, $parameters = [])
    {
        if (! is_null($name)) {
            $uri = route($name, $parameters);
        }

        PHPUnit::assertTrue(
            $this->isRedirect(),
            $this->statusMessageWithDetails('201, 301, 302, 303, 307, 308', $this->getStatusCode()),
        );

        $request = Request::create($this->headers->get('Location'));

        PHPUnit::assertTrue(
            $request->hasValidSignature(), 'The response is not a redirect to a signed route.'
        );

        if (! is_null($name)) {
            $expectedUri = rtrim($request->fullUrlWithQuery([
                'signature' => null,
                'expires' => null,
            ]), '?');

            PHPUnit::assertEquals(
                app('url')->to($uri), $expectedUri
            );
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
     * Asserts that the response does not contain the given header.
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
     * Assert that the response offers a file download.
     *
     * @param  string|null  $filename
     * @return $this
     */
    public function assertDownload($filename = null)
    {
        $contentDisposition = explode(';', $this->headers->get('content-disposition'));

        if (trim($contentDisposition[0]) !== 'attachment') {
            PHPUnit::fail(
                'Response does not offer a file download.'.PHP_EOL.
                'Disposition ['.trim($contentDisposition[0]).'] found in header, [attachment] expected.'
            );
        }

        if (! is_null($filename)) {
            if (isset($contentDisposition[1]) &&
                trim(explode('=', $contentDisposition[1])[0]) !== 'filename') {
                PHPUnit::fail(
                    'Unsupported Content-Disposition header provided.'.PHP_EOL.
                    'Disposition ['.trim(explode('=', $contentDisposition[1])[0]).'] found in header, [filename] expected.'
                );
            }

            $message = "Expected file [{$filename}] is not present in Content-Disposition header.";

            if (! isset($contentDisposition[1])) {
                PHPUnit::fail($message);
            } else {
                PHPUnit::assertSame(
                    $filename,
                    isset(explode('=', $contentDisposition[1])[1])
                        ? trim(explode('=', $contentDisposition[1])[1], " \"'")
                        : '',
                    $message
                );

                return $this;
            }
        } else {
            PHPUnit::assertTrue(true);

            return $this;
        }
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
            $cookie = $this->getCookie($cookieName, $encrypted && ! is_null($value), $unserialize),
            "Cookie [{$cookieName}] not present on response."
        );

        if (! $cookie || is_null($value)) {
            return $this;
        }

        $cookieValue = $cookie->getValue();

        PHPUnit::assertEquals(
            $value, $cookieValue,
            "Cookie [{$cookieName}] was found, but value [{$cookieValue}] does not match [{$value}]."
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
            $cookie = $this->getCookie($cookieName, false),
            "Cookie [{$cookieName}] not present on response."
        );

        $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

        PHPUnit::assertTrue(
            $cookie->getExpiresTime() !== 0 && $expiresAt->lessThan(Carbon::now()),
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
            $cookie = $this->getCookie($cookieName, false),
            "Cookie [{$cookieName}] not present on response."
        );

        $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

        PHPUnit::assertTrue(
            $cookie->getExpiresTime() === 0 || $expiresAt->greaterThan(Carbon::now()),
            "Cookie [{$cookieName}] is expired, it expired at [{$expiresAt}]."
        );

        return $this;
    }

    /**
     * Asserts that the response does not contain the given cookie.
     *
     * @param  string  $cookieName
     * @return $this
     */
    public function assertCookieMissing($cookieName)
    {
        PHPUnit::assertNull(
            $this->getCookie($cookieName, false),
            "Cookie [{$cookieName}] is present on response."
        );

        return $this;
    }

    /**
     * Get the given cookie from the response.
     *
     * @param  string  $cookieName
     * @param  bool  $decrypt
     * @param  bool  $unserialize
     * @return \Symfony\Component\HttpFoundation\Cookie|null
     */
    public function getCookie($cookieName, $decrypt = true, $unserialize = false)
    {
        foreach ($this->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                if (! $decrypt) {
                    return $cookie;
                }

                $decryptedValue = CookieValuePrefix::remove(
                    app('encrypter')->decrypt($cookie->getValue(), $unserialize)
                );

                return new Cookie(
                    $cookie->getName(),
                    $decryptedValue,
                    $cookie->getExpiresTime(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    $cookie->isSecure(),
                    $cookie->isHttpOnly(),
                    $cookie->isRaw(),
                    $cookie->getSameSite()
                );
            }
        }
    }

    /**
     * Assert that the given string or array of strings are contained within the response.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertSee($value, $escape = true)
    {
        $value = Arr::wrap($value);

        $values = $escape ? array_map('e', ($value)) : $value;

        foreach ($values as $value) {
            PHPUnit::assertStringContainsString((string) $value, $this->getContent());
        }

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the response.
     *
     * @param  array  $values
     * @param  bool  $escape
     * @return $this
     */
    public function assertSeeInOrder(array $values, $escape = true)
    {
        $values = $escape ? array_map('e', ($values)) : $values;

        PHPUnit::assertThat($values, new SeeInOrder($this->getContent()));

        return $this;
    }

    /**
     * Assert that the given string or array of strings are contained within the response text.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertSeeText($value, $escape = true)
    {
        $value = Arr::wrap($value);

        $values = $escape ? array_map('e', ($value)) : $value;

        tap(strip_tags($this->getContent()), function ($content) use ($values) {
            foreach ($values as $value) {
                PHPUnit::assertStringContainsString((string) $value, $content);
            }
        });

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the response text.
     *
     * @param  array  $values
     * @param  bool  $escape
     * @return $this
     */
    public function assertSeeTextInOrder(array $values, $escape = true)
    {
        $values = $escape ? array_map('e', ($values)) : $values;

        PHPUnit::assertThat($values, new SeeInOrder(strip_tags($this->getContent())));

        return $this;
    }

    /**
     * Assert that the given string or array of strings are not contained within the response.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertDontSee($value, $escape = true)
    {
        $value = Arr::wrap($value);

        $values = $escape ? array_map('e', ($value)) : $value;

        foreach ($values as $value) {
            PHPUnit::assertStringNotContainsString((string) $value, $this->getContent());
        }

        return $this;
    }

    /**
     * Assert that the given string or array of strings are not contained within the response text.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertDontSeeText($value, $escape = true)
    {
        $value = Arr::wrap($value);

        $values = $escape ? array_map('e', ($value)) : $value;

        tap(strip_tags($this->getContent()), function ($content) use ($values) {
            foreach ($values as $value) {
                PHPUnit::assertStringNotContainsString((string) $value, $content);
            }
        });

        return $this;
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * @param  array|callable  $value
     * @param  bool  $strict
     * @return $this
     */
    public function assertJson($value, $strict = false)
    {
        $json = $this->decodeResponseJson();

        if (is_array($value)) {
            $json->assertSubset($value, $strict);
        } else {
            $assert = AssertableJson::fromAssertableJsonString($json);

            $value($assert);

            if (Arr::isAssoc($assert->toArray())) {
                $assert->interacted();
            }
        }

        return $this;
    }

    /**
     * Assert that the expected value and type exists at the given path in the response.
     *
     * @param  string  $path
     * @param  mixed  $expect
     * @return $this
     */
    public function assertJsonPath($path, $expect)
    {
        $this->decodeResponseJson()->assertPath($path, $expect);

        return $this;
    }

    /**
     * Assert that the response has the exact given JSON.
     *
     * @param  array  $data
     * @return $this
     */
    public function assertExactJson(array $data)
    {
        $this->decodeResponseJson()->assertExact($data);

        return $this;
    }

    /**
     * Assert that the response has the similar JSON as given.
     *
     * @param  array  $data
     * @return $this
     */
    public function assertSimilarJson(array $data)
    {
        $this->decodeResponseJson()->assertSimilar($data);

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
        $this->decodeResponseJson()->assertFragment($data);

        return $this;
    }

    /**
     * Assert that the response does not contain the given JSON fragment.
     *
     * @param  array  $data
     * @param  bool  $exact
     * @return $this
     */
    public function assertJsonMissing(array $data, $exact = false)
    {
        $this->decodeResponseJson()->assertMissing($data, $exact);

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
        $this->decodeResponseJson()->assertMissingExact($data);

        return $this;
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
        $this->decodeResponseJson()->assertStructure($structure, $responseData);

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
        $this->decodeResponseJson()->assertCount($count, $key);

        return $this;
    }

    /**
     * Assert that the response has the given JSON validation errors.
     *
     * @param  string|array  $errors
     * @param  string  $responseKey
     * @return $this
     */
    public function assertJsonValidationErrors($errors, $responseKey = 'errors')
    {
        $errors = Arr::wrap($errors);

        PHPUnit::assertNotEmpty($errors, 'No validation errors were provided.');

        $jsonErrors = Arr::get($this->json(), $responseKey) ?? [];

        $errorMessage = $jsonErrors
                ? 'Response has the following JSON validation errors:'.
                        PHP_EOL.PHP_EOL.json_encode($jsonErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
                : 'Response does not have JSON validation errors.';

        foreach ($errors as $key => $value) {
            if (is_int($key)) {
                $this->assertJsonValidationErrorFor($value, $responseKey);

                continue;
            }

            $this->assertJsonValidationErrorFor($key, $responseKey);

            foreach (Arr::wrap($value) as $expectedMessage) {
                $errorMissing = true;

                foreach (Arr::wrap($jsonErrors[$key]) as $jsonErrorMessage) {
                    if (Str::contains($jsonErrorMessage, $expectedMessage)) {
                        $errorMissing = false;

                        break;
                    }
                }
            }

            if ($errorMissing) {
                PHPUnit::fail(
                    "Failed to find a validation error in the response for key and message: '$key' => '$expectedMessage'".PHP_EOL.PHP_EOL.$errorMessage
                );
            }
        }

        return $this;
    }

    /**
     * Assert the response has any JSON validation errors for the given key.
     *
     * @param  string  $key
     * @param  string  $responseKey
     * @return $this
     */
    public function assertJsonValidationErrorFor($key, $responseKey = 'errors')
    {
        $jsonErrors = Arr::get($this->json(), $responseKey) ?? [];

        $errorMessage = $jsonErrors
            ? 'Response has the following JSON validation errors:'.
            PHP_EOL.PHP_EOL.json_encode($jsonErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
            : 'Response does not have JSON validation errors.';

        PHPUnit::assertArrayHasKey(
            $key,
            $jsonErrors,
            "Failed to find a validation error in the response for key: '{$key}'".PHP_EOL.PHP_EOL.$errorMessage
        );

        return $this;
    }

    /**
     * Assert that the response has no JSON validation errors for the given keys.
     *
     * @param  string|array|null  $keys
     * @param  string  $responseKey
     * @return $this
     */
    public function assertJsonMissingValidationErrors($keys = null, $responseKey = 'errors')
    {
        if ($this->getContent() === '') {
            PHPUnit::assertTrue(true);

            return $this;
        }

        $json = $this->json();

        if (! Arr::has($json, $responseKey)) {
            PHPUnit::assertTrue(true);

            return $this;
        }

        $errors = Arr::get($json, $responseKey, []);

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
     * @return \Illuminate\Testing\AssertableJsonString
     *
     * @throws \Throwable
     */
    public function decodeResponseJson()
    {
        $testJson = new AssertableJsonString($this->getContent());

        $decodedResponse = $testJson->json();

        if (is_null($decodedResponse) || $decodedResponse === false) {
            if ($this->exception) {
                throw $this->exception;
            } else {
                PHPUnit::fail('Invalid JSON was returned from the route.');
            }
        }

        return $testJson;
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function json($key = null)
    {
        return $this->decodeResponseJson()->json($key);
    }

    /**
     * Assert that the response view equals the given value.
     *
     * @param  string  $value
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
            PHPUnit::assertTrue(Arr::has($this->original->gatherData(), $key));
        } elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value(Arr::get($this->original->gatherData(), $key)));
        } elseif ($value instanceof Model) {
            PHPUnit::assertTrue($value->is(Arr::get($this->original->gatherData(), $key)));
        } else {
            PHPUnit::assertEquals($value, Arr::get($this->original->gatherData(), $key));
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

        PHPUnit::assertFalse(Arr::has($this->original->gatherData(), $key));

        return $this;
    }

    /**
     * Ensure that the response has a view as its original content.
     *
     * @return $this
     */
    protected function ensureResponseHasView()
    {
        if (! $this->responseHasView()) {
            return PHPUnit::fail('The response is not a view.');
        }

        return $this;
    }

    /**
     * Determine if the original response is a view.
     *
     * @return bool
     */
    protected function responseHasView()
    {
        return isset($this->original) && $this->original instanceof View;
    }

    /**
     * Assert that the given keys do not have validation errors.
     *
     * @param  string|array|null  $keys
     * @param  string  $errorBag
     * @param  string  $responseKey
     * @return $this
     */
    public function assertValid($keys = null, $errorBag = 'default', $responseKey = 'errors')
    {
        if ($this->baseResponse->headers->get('Content-Type') === 'application/json') {
            return $this->assertJsonMissingValidationErrors($keys, $responseKey);
        }

        if ($this->session()->get('errors')) {
            $errors = $this->session()->get('errors')->getBag($errorBag)->getMessages();
        } else {
            $errors = [];
        }

        if (empty($errors)) {
            PHPUnit::assertTrue(true);

            return $this;
        }

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
     * Assert that the response has the given validation errors.
     *
     * @param  string|array|null  $errors
     * @param  string  $errorBag
     * @param  string  $responseKey
     * @return $this
     */
    public function assertInvalid($errors = null,
                                  $errorBag = 'default',
                                  $responseKey = 'errors')
    {
        if ($this->baseResponse->headers->get('Content-Type') === 'application/json') {
            return $this->assertJsonValidationErrors($errors, $responseKey);
        }

        $this->assertSessionHas('errors');

        $sessionErrors = $this->session()->get('errors')->getBag($errorBag)->getMessages();

        $errorMessage = $sessionErrors
                ? 'Response has the following validation errors in the session:'.
                        PHP_EOL.PHP_EOL.json_encode($sessionErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
                : 'Response does not have validation errors in the session.';

        foreach (Arr::wrap($errors) as $key => $value) {
            PHPUnit::assertArrayHasKey(
                (is_int($key)) ? $value : $key,
                $sessionErrors,
                "Failed to find a validation error in session for key: '{$value}'".PHP_EOL.PHP_EOL.$errorMessage
            );

            if (! is_int($key)) {
                $hasError = false;

                foreach (Arr::wrap($sessionErrors[$key]) as $sessionErrorMessage) {
                    if (Str::contains($sessionErrorMessage, $value)) {
                        $hasError = true;

                        break;
                    }
                }

                if (! $hasError) {
                    PHPUnit::fail(
                        "Failed to find a validation error for key and message: '$key' => '$value'".PHP_EOL.PHP_EOL.$errorMessage
                    );
                }
            }
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
                $this->session()->hasOldInput($key),
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
                PHPUnit::assertContains(is_bool($value) ? (string) $value : $value, $errors->get($key, $format));
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
     * Dump the content from the response and end the script.
     *
     * @return never
     */
    public function dd()
    {
        $this->dump();

        exit(1);
    }

    /**
     * Dump the headers from the response and end the script.
     *
     * @return never
     */
    public function ddHeaders()
    {
        $this->dumpHeaders();

        exit(1);
    }

    /**
     * Dump the session from the response and end the script.
     *
     * @param  string|array  $keys
     * @return never
     */
    public function ddSession($keys = [])
    {
        $this->dumpSession($keys);

        exit(1);
    }

    /**
     * Dump the content from the response.
     *
     * @param  string|null  $key
     * @return $this
     */
    public function dump($key = null)
    {
        $content = $this->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        if (! is_null($key)) {
            dump(data_get($content, $key));
        } else {
            dump($content);
        }

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
     * Dump the session from the response.
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function dumpSession($keys = [])
    {
        $keys = (array) $keys;

        if (empty($keys)) {
            dump($this->session()->all());
        } else {
            dump($this->session()->only($keys));
        }

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
     * Set the previous exceptions on the response.
     *
     * @param  \Illuminate\Support\Collection  $exceptions
     * @return $this
     */
    public function withExceptions(Collection $exceptions)
    {
        $this->exceptions = $exceptions;

        return $this;
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
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->responseHasView()
                    ? isset($this->original->gatherData()[$offset])
                    : isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->responseHasView()
                    ? $this->viewData($offset)
                    : $this->json()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
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
