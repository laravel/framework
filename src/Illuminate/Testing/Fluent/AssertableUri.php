<?php

namespace Illuminate\Testing\Fluent;

use BadMethodCallException;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @method $this hasFragment()
 * @method $this hasHost()
 * @method $this hasPass()
 * @method $this hasPath()
 * @method $this hasPort()
 * @method $this hasScheme()
 * @method $this hasUser()
 * @method $this whereFragment(string $value)
 * @method $this whereHost(string $value)
 * @method $this wherePass(string $value)
 * @method $this wherePath(string $value)
 * @method $this wherePort(string $value)
 * @method $this whereScheme(string $value)
 * @method $this whereUser(string $value)
 */
class AssertableUri
{
    use Concerns\Interaction;

    /**
     * An URI string is composed of 8 components.
     *
     * @link https://www.php.net/manual/en/function.parse-url.php
     *
     * @var array
     */
    protected $components = ['fragment', 'host', 'pass', 'path', 'port', 'query', 'scheme', 'user'];

    /**
     * Query string parameters of given URI.
     *
     * @var array
     */
    protected $query = [];

    /**
     * Containing any of the various components of the URI that are present.
     *
     * @var array
     */
    protected $uri;

    /**
     * Create a new fluent, assertable URI instance.
     *
     * @param  string  $uri
     * @return void
     */
    public function __construct(string $uri)
    {
        $uri = parse_url($uri);

        if (! empty($uri['query'])) {
            parse_str($uri['query'], $this->query);
        }

        $this->uri = $uri;
    }

    /**
     * Ensure that the given component exists.
     *
     * @param  string  $component
     * @return $this
     */
    protected function has($component)
    {
        PHPUnit::assertTrue(
            Arr::has($this->uri, $component),
            sprintf('URI component [%s] does not exist.', Str::ucfirst($component))
        );

        return $this;
    }

    /**
     * Ensure that the given query string exists.
     *
     * @param  string  $query
     * @return $this
     */
    public function hasQuery($query)
    {
        $this->has('query');

        $this->interactsWith($query);

        PHPUnit::assertTrue(
            Arr::has($this->query, $query),
            sprintf('Query [%s] does not exist.', $query)
        );

        return $this;
    }

    /**
     * Asserts that the URI component matches the expected value.
     *
     * @param  string  $component
     * @param  string  $value
     * @return $this
     */
    protected function where($component, $value)
    {
        $this->has($component);

        PHPUnit::assertSame(
            Arr::get($this->uri, $component),
            $value,
            sprintf('URI component [%s] does not match the expected value.', Str::ucfirst($component))
        );

        return $this;
    }

    /**
     * Asserts that certain/whole query matches the expected value.
     *
     * @param  string  $query
     * @param  string|\Closure|null  $value
     * @return $this
     */
    public function whereQuery(string $query, $value = null)
    {
        if (is_null($value)) {
            return $this->where('query', $query);
        }

        $expected = $this->prop($query);

        $this->hasQuery($query);

        if ($value instanceof Closure) {
            PHPUnit::assertTrue($value($expected));
        } else {
            PHPUnit::assertSame(
                $expected,
                $value,
                "Query [$query] does not match the expected value."
            );
        }

        return $this;
    }

    /**
     * Asserts that all queries have been interacted with.
     *
     * @return void
     */
    public function interacted(): void
    {
        PHPUnit::assertSame(
            [],
            array_diff(array_keys($this->prop()), $this->interacted),
            'Unexpected query were found on URI.'
        );
    }

    /**
     * Retrieve query string from URI using "dot" notation.
     *
     * @param  string|null  $key
     * @return mixed
     */
    protected function prop(string $key = null)
    {
        return Arr::get($this->query, $key);
    }

    /**
     * Pass other method calls down.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        $component = Str::remove(['where', 'has'], Str::lower($method));

        if (! in_array($component, $this->components)) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', self::class, $method
            ));
        }

        return Str::startsWith($method, 'where')
            ? $this->where($component, ...$arguments)
            : $this->has($component);
    }
}
