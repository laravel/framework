<?php

namespace Illuminate\Testing\Fluent;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Testing\AssertableJsonString;
use PHPUnit\Framework\Assert as PHPUnit;

class AssertableJson implements Arrayable
{
    use Concerns\Has,
        Concerns\Matching,
        Concerns\Debugging,
        Concerns\Interaction,
        Macroable,
        Tappable;

    /**
     * The properties in the current scope.
     *
     * @var array
     */
    private $props;

    /**
     * The "dot" path to the current scope.
     *
     * @var string|null
     */
    private $path;

    /**
     * Create a new fluent, assertable JSON data instance.
     *
     * @param  array  $props
     * @param  string|null  $path
     * @return void
     */
    protected function __construct(array $props, string $path = null)
    {
        $this->path = $path;
        $this->props = $props;
    }

    /**
     * Compose the absolute "dot" path to the given key.
     *
     * @param  string  $key
     * @return string
     */
    protected function dotPath(string $key): string
    {
        if (is_null($this->path)) {
            return $key;
        }

        return implode('.', [$this->path, $key]);
    }

    /**
     * Retrieve a prop within the current scope using "dot" notation.
     *
     * @param  string|null  $key
     * @return mixed
     */
    protected function prop(string $key = null)
    {
        return Arr::get($this->props, $key);
    }

    /**
     * Instantiate a new "scope" at the path of the given key.
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return $this
     */
    protected function scope(string $key, Closure $callback): self
    {
        $props = $this->prop($key);
        $path = $this->dotPath($key);

        PHPUnit::assertIsArray($props, sprintf('Property [%s] is not scopeable.', $path));

        $scope = new self($props, $path);
        $callback($scope);
        $scope->interacted();

        return $this;
    }

    /**
     * Create a new instance from an array.
     *
     * @param  array  $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Create a new instance from a AssertableJsonString.
     *
     * @param  \Illuminate\Testing\AssertableJsonString  $json
     * @return static
     */
    public static function fromAssertableJsonString(AssertableJsonString $json): self
    {
        return self::fromArray($json->json());
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->props;
    }
}
